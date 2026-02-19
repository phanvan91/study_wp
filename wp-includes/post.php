<?php
/**
 * API Bài viết Cốt lõi
 *
 * @package WordPress
 * @subpackage Post
 */

//
// Đăng ký Post Type.
//

/**
 * Tạo các post type ban đầu khi action 'init' được kích hoạt.
 *
 * Xem {@see 'init'}.
 *
 * @since 2.9.0
 */
function create_initial_post_types() {
	WP_Post_Type::reset_default_labels();

	register_post_type(
		'post',
		array(
			'labels'                => array(
				'name_admin_bar' => _x( 'Post', 'add new from admin bar' ),
			),
			'public'                => true,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'            => 'post.php?post=%d', /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-admin-post',
			'hierarchical'          => false,
			'rewrite'               => false,
			'query_var'             => false,
			'delete_with_user'      => true,
			'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'post-formats' ),
			'show_in_rest'          => true,
			'rest_base'             => 'posts',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		)
	);

	register_post_type(
		'page',
		array(
			'labels'                => array(
				'name_admin_bar' => _x( 'Page', 'add new from admin bar' ),
			),
			'public'                => true,
			'publicly_queryable'    => false,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'            => 'post.php?post=%d', /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'capability_type'       => 'page',
			'map_meta_cap'          => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-admin-page',
			'hierarchical'          => true,
			'rewrite'               => false,
			'query_var'             => false,
			'delete_with_user'      => true,
			'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes', 'custom-fields', 'comments', 'revisions' ),
			'show_in_rest'          => true,
			'rest_base'             => 'pages',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		)
	);

	register_post_type(
		'attachment',
		array(
			'labels'                => array(
				'name'           => _x( 'Media', 'post type general name' ),
				'name_admin_bar' => _x( 'Media', 'add new from admin bar' ),
				'add_new'        => __( 'Add Media File' ),
				'edit_item'      => __( 'Edit Media' ),
				'view_item'      => ( '1' === get_option( 'wp_attachment_pages_enabled' ) ) ? __( 'View Attachment Page' ) : __( 'View Media File' ),
				'attributes'     => __( 'Attachment Attributes' ),
			),
			'public'                => true,
			'show_ui'               => true,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'            => 'post.php?post=%d', /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'capability_type'       => 'post',
			'capabilities'          => array(
				'create_posts' => 'upload_files',
			),
			'map_meta_cap'          => true,
			'menu_icon'             => 'dashicons-admin-media',
			'hierarchical'          => false,
			'rewrite'               => false,
			'query_var'             => false,
			'show_in_nav_menus'     => false,
			'delete_with_user'      => true,
			'supports'              => array( 'title', 'author', 'comments' ),
			'show_in_rest'          => true,
			'rest_base'             => 'media',
			'rest_controller_class' => 'WP_REST_Attachments_Controller',
		)
	);
	add_post_type_support( 'attachment:audio', 'thumbnail' );
	add_post_type_support( 'attachment:video', 'thumbnail' );

	register_post_type(
		'revision',
		array(
			'labels'           => array(
				'name'          => __( 'Revisions' ),
				'singular_name' => __( 'Revision' ),
			),
			'public'           => false,
			'_builtin'         => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'       => 'revision.php?revision=%d', /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'capability_type'  => 'post',
			'map_meta_cap'     => true,
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'can_export'       => false,
			'delete_with_user' => true,
			'supports'         => array( 'author' ),
		)
	);

	register_post_type(
		'nav_menu_item',
		array(
			'labels'                => array(
				'name'          => __( 'Navigation Menu Items' ),
				'singular_name' => __( 'Navigation Menu Item' ),
			),
			'public'                => false,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'hierarchical'          => false,
			'rewrite'               => false,
			'delete_with_user'      => false,
			'query_var'             => false,
			'map_meta_cap'          => true,
			'capability_type'       => array( 'edit_theme_options', 'edit_theme_options' ),
			'capabilities'          => array(
				// Quyền Meta.
				'edit_post'              => 'edit_post',
				'read_post'              => 'read_post',
				'delete_post'            => 'delete_post',
				// Quyền Nguyên thủy.
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
				'read'                   => 'read',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'edit_private_posts'     => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'menu-items',
			'rest_controller_class' => 'WP_REST_Menu_Items_Controller',
		)
	);

	register_post_type(
		'custom_css',
		array(
			'labels'           => array(
				'name'          => __( 'Custom CSS' ),
				'singular_name' => __( 'Custom CSS' ),
			),
			'public'           => false,
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'delete_with_user' => false,
			'can_export'       => true,
			'_builtin'         => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'supports'         => array( 'title', 'revisions' ),
			'capabilities'     => array(
				'delete_posts'           => 'edit_theme_options',
				'delete_post'            => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'edit_post'              => 'edit_css',
				'edit_posts'             => 'edit_css',
				'edit_others_posts'      => 'edit_css',
				'edit_published_posts'   => 'edit_css',
				'read_post'              => 'read',
				'read_private_posts'     => 'read',
				'publish_posts'          => 'edit_theme_options',
			),
		)
	);

	register_post_type(
		'customize_changeset',
		array(
			'labels'           => array(
				'name'               => _x( 'Changesets', 'post type general name' ),
				'singular_name'      => _x( 'Changeset', 'post type singular name' ),
				'add_new'            => __( 'Add Changeset' ),
				'add_new_item'       => __( 'Add Changeset' ),
				'new_item'           => __( 'New Changeset' ),
				'edit_item'          => __( 'Edit Changeset' ),
				'view_item'          => __( 'View Changeset' ),
				'all_items'          => __( 'All Changesets' ),
				'search_items'       => __( 'Search Changesets' ),
				'not_found'          => __( 'No changesets found.' ),
				'not_found_in_trash' => __( 'No changesets found in Trash.' ),
			),
			'public'           => false,
			'_builtin'         => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'map_meta_cap'     => true,
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'can_export'       => false,
			'delete_with_user' => false,
			'supports'         => array( 'title', 'author' ),
			'capability_type'  => 'customize_changeset',
			'capabilities'     => array(
				'create_posts'           => 'customize',
				'delete_others_posts'    => 'customize',
				'delete_post'            => 'customize',
				'delete_posts'           => 'customize',
				'delete_private_posts'   => 'customize',
				'delete_published_posts' => 'customize',
				'edit_others_posts'      => 'customize',
				'edit_post'              => 'customize',
				'edit_posts'             => 'customize',
				'edit_private_posts'     => 'customize',
				'edit_published_posts'   => 'do_not_allow',
				'publish_posts'          => 'customize',
				'read'                   => 'read',
				'read_post'              => 'customize',
				'read_private_posts'     => 'customize',
			),
		)
	);

	register_post_type(
		'oembed_cache',
		array(
			'labels'           => array(
				'name'          => __( 'oEmbed Responses' ),
				'singular_name' => __( 'oEmbed Response' ),
			),
			'public'           => false,
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'delete_with_user' => false,
			'can_export'       => false,
			'_builtin'         => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'supports'         => array(),
		)
	);

	register_post_type(
		'user_request',
		array(
			'labels'           => array(
				'name'          => __( 'User Requests' ),
				'singular_name' => __( 'User Request' ),
			),
			'public'           => false,
			'_builtin'         => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'can_export'       => false,
			'delete_with_user' => false,
			'supports'         => array(),
		)
	);

	register_post_type(
		'wp_block',
		array(
			'labels'                => array(
				'name'                     => _x( 'Patterns', 'post type general name' ),
				'singular_name'            => _x( 'Pattern', 'post type singular name' ),
				'add_new'                  => __( 'Add Pattern' ),
				'add_new_item'             => __( 'Add Pattern' ),
				'new_item'                 => __( 'New Pattern' ),
				'edit_item'                => __( 'Edit Block Pattern' ),
				'view_item'                => __( 'View Pattern' ),
				'view_items'               => __( 'View Patterns' ),
				'all_items'                => __( 'All Patterns' ),
				'search_items'             => __( 'Search Patterns' ),
				'not_found'                => __( 'No patterns found.' ),
				'not_found_in_trash'       => __( 'No patterns found in Trash.' ),
				'filter_items_list'        => __( 'Filter patterns list' ),
				'items_list_navigation'    => __( 'Patterns list navigation' ),
				'items_list'               => __( 'Patterns list' ),
				'item_published'           => __( 'Pattern published.' ),
				'item_published_privately' => __( 'Pattern published privately.' ),
				'item_reverted_to_draft'   => __( 'Pattern reverted to draft.' ),
				'item_scheduled'           => __( 'Pattern scheduled.' ),
				'item_updated'             => __( 'Pattern updated.' ),
			),
			'public'                => false,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'show_ui'               => true,
			'show_in_menu'          => false,
			'rewrite'               => false,
			'show_in_rest'          => true,
			'rest_base'             => 'blocks',
			'rest_controller_class' => 'WP_REST_Blocks_Controller',
			'capability_type'       => 'block',
			'capabilities'          => array(
				// Bạn cần có quyền sửa bài viết để đọc block ở dạng thô.
				'read'                   => 'edit_posts',
				// Bạn cần có quyền xuất bản bài viết để tạo block.
				'create_posts'           => 'publish_posts',
				'edit_posts'             => 'edit_posts',
				'edit_published_posts'   => 'edit_published_posts',
				'delete_published_posts' => 'delete_published_posts',
				// Cho phép đưa bài nháp vào thùng rác.
				'delete_posts'           => 'delete_posts',
				'edit_others_posts'      => 'edit_others_posts',
				'delete_others_posts'    => 'delete_others_posts',
			),
			'map_meta_cap'          => true,
			'supports'              => array(
				'title',
				'excerpt',
				'editor',
				'revisions',
				'custom-fields',
			),
		)
	);

	$template_edit_link = 'site-editor.php?' . build_query(
		array(
			'postType' => '%s',
			'postId'   => '%s',
			'canvas'   => 'edit',
		)
	);

	register_post_type(
		'wp_template',
		array(
			'labels'                          => array(
				'name'                  => _x( 'Templates', 'post type general name' ),
				'singular_name'         => _x( 'Template', 'post type singular name' ),
				'add_new'               => __( 'Add Template' ),
				'add_new_item'          => __( 'Add Template' ),
				'new_item'              => __( 'New Template' ),
				'edit_item'             => __( 'Edit Template' ),
				'view_item'             => __( 'View Template' ),
				'all_items'             => __( 'Templates' ),
				'search_items'          => __( 'Search Templates' ),
				'parent_item_colon'     => __( 'Parent Template:' ),
				'not_found'             => __( 'No templates found.' ),
				'not_found_in_trash'    => __( 'No templates found in Trash.' ),
				'archives'              => __( 'Template archives' ),
				'insert_into_item'      => __( 'Insert into template' ),
				'uploaded_to_this_item' => __( 'Uploaded to this template' ),
				'filter_items_list'     => __( 'Filter templates list' ),
				'items_list_navigation' => __( 'Templates list navigation' ),
				'items_list'            => __( 'Templates list' ),
				'item_updated'          => __( 'Template updated.' ),
			),
			'description'                     => __( 'Templates to include in your theme.' ),
			'public'                          => false,
			'_builtin'                        => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'                      => $template_edit_link, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'has_archive'                     => false,
			'show_ui'                         => false,
			'show_in_menu'                    => false,
			'show_in_rest'                    => true,
			'rewrite'                         => false,
			'rest_base'                       => 'templates',
			'rest_controller_class'           => 'WP_REST_Templates_Controller',
			'autosave_rest_controller_class'  => 'WP_REST_Template_Autosaves_Controller',
			'revisions_rest_controller_class' => 'WP_REST_Template_Revisions_Controller',
			'late_route_registration'         => true,
			'capability_type'                 => array( 'template', 'templates' ),
			'capabilities'                    => array(
				'create_posts'           => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'edit_private_posts'     => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'read'                   => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
			),
			'map_meta_cap'                    => true,
			'supports'                        => array(
				'title',
				'slug',
				'excerpt',
				'editor',
				'revisions',
				'author',
			),
		)
	);

	register_post_type(
		'wp_template_part',
		array(
			'labels'                          => array(
				'name'                  => _x( 'Template Parts', 'post type general name' ),
				'singular_name'         => _x( 'Template Part', 'post type singular name' ),
				'add_new'               => __( 'Add Template Part' ),
				'add_new_item'          => __( 'Add Template Part' ),
				'new_item'              => __( 'New Template Part' ),
				'edit_item'             => __( 'Edit Template Part' ),
				'view_item'             => __( 'View Template Part' ),
				'all_items'             => __( 'Template Parts' ),
				'search_items'          => __( 'Search Template Parts' ),
				'parent_item_colon'     => __( 'Parent Template Part:' ),
				'not_found'             => __( 'No template parts found.' ),
				'not_found_in_trash'    => __( 'No template parts found in Trash.' ),
				'archives'              => __( 'Template part archives' ),
				'insert_into_item'      => __( 'Insert into template part' ),
				'uploaded_to_this_item' => __( 'Uploaded to this template part' ),
				'filter_items_list'     => __( 'Filter template parts list' ),
				'items_list_navigation' => __( 'Template parts list navigation' ),
				'items_list'            => __( 'Template parts list' ),
				'item_updated'          => __( 'Template part updated.' ),
			),
			'description'                     => __( 'Template parts to include in your templates.' ),
			'public'                          => false,
			'_builtin'                        => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'                      => $template_edit_link, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'has_archive'                     => false,
			'show_ui'                         => false,
			'show_in_menu'                    => false,
			'show_in_rest'                    => true,
			'rewrite'                         => false,
			'rest_base'                       => 'template-parts',
			'rest_controller_class'           => 'WP_REST_Templates_Controller',
			'autosave_rest_controller_class'  => 'WP_REST_Template_Autosaves_Controller',
			'revisions_rest_controller_class' => 'WP_REST_Template_Revisions_Controller',
			'late_route_registration'         => true,
			'map_meta_cap'                    => true,
			'capabilities'                    => array(
				'create_posts'           => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'edit_private_posts'     => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'read'                   => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
			),
			'supports'                        => array(
				'title',
				'slug',
				'excerpt',
				'editor',
				'revisions',
				'author',
			),
		)
	);

	register_post_type(
		'wp_global_styles',
		array(
			'label'                           => _x( 'Global Styles', 'post type general name' ),
			'description'                     => __( 'Global styles to include in themes.' ),
			'public'                          => false,
			'_builtin'                        => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'                      => '/site-editor.php?canvas=edit', /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'show_ui'                         => false,
			'show_in_rest'                    => true,
			'rewrite'                         => false,
			'rest_base'                       => 'global-styles',
			'rest_controller_class'           => 'WP_REST_Global_Styles_Controller',
			'revisions_rest_controller_class' => 'WP_REST_Global_Styles_Revisions_Controller',
			'late_route_registration'         => true,
			'capabilities'                    => array(
				'read'                   => 'edit_posts',
				'create_posts'           => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
			),
			'map_meta_cap'                    => true,
			'supports'                        => array(
				'title',
				'editor',
				'revisions',
			),
		)
	);
	// Vô hiệu hóa endpoint tự động lưu cho global styles.
	remove_post_type_support( 'wp_global_styles', 'autosave' );

	$navigation_post_edit_link = 'site-editor.php?' . build_query(
		array(
			'postId'   => '%s',
			'postType' => 'wp_navigation',
			'canvas'   => 'edit',
		)
	);

	register_post_type(
		'wp_navigation',
		array(
			'labels'                => array(
				'name'                  => _x( 'Navigation Menus', 'post type general name' ),
				'singular_name'         => _x( 'Navigation Menu', 'post type singular name' ),
				'add_new'               => __( 'Add Navigation Menu' ),
				'add_new_item'          => __( 'Add Navigation Menu' ),
				'new_item'              => __( 'New Navigation Menu' ),
				'edit_item'             => __( 'Edit Navigation Menu' ),
				'view_item'             => __( 'View Navigation Menu' ),
				'all_items'             => __( 'Navigation Menus' ),
				'search_items'          => __( 'Search Navigation Menus' ),
				'parent_item_colon'     => __( 'Parent Navigation Menu:' ),
				'not_found'             => __( 'No Navigation Menu found.' ),
				'not_found_in_trash'    => __( 'No Navigation Menu found in Trash.' ),
				'archives'              => __( 'Navigation Menu archives' ),
				'insert_into_item'      => __( 'Insert into Navigation Menu' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Navigation Menu' ),
				'filter_items_list'     => __( 'Filter Navigation Menu list' ),
				'items_list_navigation' => __( 'Navigation Menus list navigation' ),
				'items_list'            => __( 'Navigation Menus list' ),
			),
			'description'           => __( 'Navigation menus that can be inserted into your site.' ),
			'public'                => false,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'_edit_link'            => $navigation_post_edit_link, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'has_archive'           => false,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_rest'          => true,
			'rewrite'               => false,
			'map_meta_cap'          => true,
			'capabilities'          => array(
				'edit_others_posts'      => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'create_posts'           => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'edit_private_posts'     => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
			),
			'rest_base'             => 'navigation',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports'              => array(
				'title',
				'editor',
				'revisions',
			),
		)
	);

	register_post_type(
		'wp_font_family',
		array(
			'labels'                => array(
				'name'          => __( 'Font Families' ),
				'singular_name' => __( 'Font Family' ),
			),
			'public'                => false,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'hierarchical'          => false,
			'capabilities'          => array(
				'read'                   => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
				'create_posts'           => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
			),
			'map_meta_cap'          => true,
			'query_var'             => false,
			'rewrite'               => false,
			'show_in_rest'          => true,
			'rest_base'             => 'font-families',
			'rest_controller_class' => 'WP_REST_Font_Families_Controller',
			'supports'              => array( 'title' ),
		)
	);

	register_post_type(
		'wp_font_face',
		array(
			'labels'                => array(
				'name'          => __( 'Font Faces' ),
				'singular_name' => __( 'Font Face' ),
			),
			'public'                => false,
			'_builtin'              => true, /* chỉ sử dụng nội bộ. không dùng khi đăng ký post type của bạn. */
			'hierarchical'          => false,
			'capabilities'          => array(
				'read'                   => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
				'create_posts'           => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
			),
			'map_meta_cap'          => true,
			'query_var'             => false,
			'rewrite'               => false,
			'show_in_rest'          => true,
			'rest_base'             => 'font-families/(?P<font_family_id>[\d]+)/font-faces',
			'rest_controller_class' => 'WP_REST_Font_Faces_Controller',
			'supports'              => array( 'title' ),
		)
	);

	register_post_status(
		'publish',
		array(
			'label'       => _x( 'Published', 'post status' ),
			'public'      => true,
			'_builtin'    => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of published posts. */
			'label_count' => _n_noop(
				'Published <span class="count">(%s)</span>',
				'Published <span class="count">(%s)</span>'
			),
		)
	);

	register_post_status(
		'future',
		array(
			'label'       => _x( 'Scheduled', 'post status' ),
			'protected'   => true,
			'_builtin'    => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of scheduled posts. */
			'label_count' => _n_noop(
				'Scheduled <span class="count">(%s)</span>',
				'Scheduled <span class="count">(%s)</span>'
			),
		)
	);

	register_post_status(
		'draft',
		array(
			'label'         => _x( 'Draft', 'post status' ),
			'protected'     => true,
			'_builtin'      => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of draft posts. */
			'label_count'   => _n_noop(
				'Draft <span class="count">(%s)</span>',
				'Drafts <span class="count">(%s)</span>'
			),
			'date_floating' => true,
		)
	);

	register_post_status(
		'pending',
		array(
			'label'         => _x( 'Pending', 'post status' ),
			'protected'     => true,
			'_builtin'      => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of pending posts. */
			'label_count'   => _n_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>'
			),
			'date_floating' => true,
		)
	);

	register_post_status(
		'private',
		array(
			'label'       => _x( 'Private', 'post status' ),
			'private'     => true,
			'_builtin'    => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of private posts. */
			'label_count' => _n_noop(
				'Private <span class="count">(%s)</span>',
				'Private <span class="count">(%s)</span>'
			),
		)
	);

	register_post_status(
		'trash',
		array(
			'label'                     => _x( 'Trash', 'post status' ),
			'internal'                  => true,
			'_builtin'                  => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of trashed posts. */
			'label_count'               => _n_noop(
				'Trash <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>'
			),
			'show_in_admin_status_list' => true,
		)
	);

	register_post_status(
		'auto-draft',
		array(
			'label'         => 'auto-draft',
			'internal'      => true,
			'_builtin'      => true, /* chỉ sử dụng nội bộ. */
			'date_floating' => true,
		)
	);

	register_post_status(
		'inherit',
		array(
			'label'               => 'inherit',
			'internal'            => true,
			'_builtin'            => true, /* chỉ sử dụng nội bộ. */
			'exclude_from_search' => false,
		)
	);

	register_post_status(
		'request-pending',
		array(
			'label'               => _x( 'Pending', 'request status' ),
			'internal'            => true,
			'_builtin'            => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of pending requests. */
			'label_count'         => _n_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>'
			),
			'exclude_from_search' => false,
		)
	);

	register_post_status(
		'request-confirmed',
		array(
			'label'               => _x( 'Confirmed', 'request status' ),
			'internal'            => true,
			'_builtin'            => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of confirmed requests. */
			'label_count'         => _n_noop(
				'Confirmed <span class="count">(%s)</span>',
				'Confirmed <span class="count">(%s)</span>'
			),
			'exclude_from_search' => false,
		)
	);

	register_post_status(
		'request-failed',
		array(
			'label'               => _x( 'Failed', 'request status' ),
			'internal'            => true,
			'_builtin'            => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of failed requests. */
			'label_count'         => _n_noop(
				'Failed <span class="count">(%s)</span>',
				'Failed <span class="count">(%s)</span>'
			),
			'exclude_from_search' => false,
		)
	);

	register_post_status(
		'request-completed',
		array(
			'label'               => _x( 'Completed', 'request status' ),
			'internal'            => true,
			'_builtin'            => true, /* chỉ sử dụng nội bộ. */
			/* translators: %s: Number of completed requests. */
			'label_count'         => _n_noop(
				'Completed <span class="count">(%s)</span>',
				'Completed <span class="count">(%s)</span>'
			),
			'exclude_from_search' => false,
		)
	);
}

/**
 * Lấy đường dẫn tệp đính kèm dựa trên ID tệp đính kèm.
 *
 * Mặc định đường dẫn sẽ đi qua filter {@see 'get_attached_file'}, nhưng
 * truyền `true` vào tham số `$unfiltered` sẽ trả về đường dẫn không qua filter.
 *
 * Hàm hoạt động bằng cách lấy giá trị post meta `_wp_attached_file`.
 * Đây là hàm tiện ích để tránh phải tra cứu tên meta và cung cấp
 * cơ chế gửi tên tệp đính kèm qua filter.
 *
 * @since 2.0.0
 *
 * @param int  $attachment_id ID tệp đính kèm.
 * @param bool $unfiltered    Tùy chọn. Có bỏ qua filter {@see 'get_attached_file'} hay không.
 *                            Mặc định false.
 * @return string|false Đường dẫn tệp nơi tệp đính kèm nên ở, false nếu không có.
 */
function get_attached_file( $attachment_id, $unfiltered = false ) {
	$file = get_post_meta( $attachment_id, '_wp_attached_file', true );

	// Nếu tệp là đường dẫn tương đối, thêm thư mục upload vào đầu.
	if ( $file && ! str_starts_with( $file, '/' ) && ! preg_match( '|^.:\\\|', $file ) ) {
		$uploads = wp_get_upload_dir();
		if ( false === $uploads['error'] ) {
			$file = $uploads['basedir'] . "/$file";
		}
	}

	if ( $unfiltered ) {
		return $file;
	}

	/**
	 * Lọc tệp đính kèm dựa trên ID đã cho.
	 *
	 * @since 2.1.0
	 *
	 * @param string|false $file          Đường dẫn tệp nơi tệp đính kèm nên ở, false nếu không có.
	 * @param int          $attachment_id ID tệp đính kèm.
	 */
	return apply_filters( 'get_attached_file', $file, $attachment_id );
}

/**
 * Cập nhật đường dẫn tệp đính kèm dựa trên ID tệp đính kèm.
 *
 * Dùng để cập nhật đường dẫn tệp của tệp đính kèm, sử dụng tên post meta
 * `_wp_attached_file` để lưu trữ đường dẫn của tệp đính kèm.
 *
 * @since 2.1.0
 *
 * @param int    $attachment_id ID tệp đính kèm.
 * @param string $file          Đường dẫn tệp cho tệp đính kèm.
 * @return int|bool ID Meta nếu khóa `_wp_attached_file` không tồn tại cho tệp đính kèm.
 *                  True nếu cập nhật thành công, false nếu thất bại hoặc nếu giá trị `$file`
 *                  truyền vào hàm giống với giá trị đã có trong cơ sở dữ liệu.
 */
function update_attached_file( $attachment_id, $file ) {
	if ( ! get_post( $attachment_id ) ) {
		return false;
	}

	/**
	 * Lọc đường dẫn đến tệp đính kèm cần cập nhật.
	 *
	 * @since 2.1.0
	 *
	 * @param string $file          Đường dẫn đến tệp đính kèm cần cập nhật.
	 * @param int    $attachment_id ID tệp đính kèm.
	 */
	$file = apply_filters( 'update_attached_file', $file, $attachment_id );

	$file = _wp_relative_upload_path( $file );
	if ( $file ) {
		return update_post_meta( $attachment_id, '_wp_attached_file', $file );
	} else {
		return delete_post_meta( $attachment_id, '_wp_attached_file' );
	}
}

/**
 * Trả về đường dẫn tương đối đến tệp đã tải lên.
 *
 * Đường dẫn tương đối so với thư mục upload hiện tại.
 *
 * @since 2.9.0
 * @access private
 *
 * @param string $path Đường dẫn đầy đủ đến tệp.
 * @return string Đường dẫn tương đối nếu thành công, đường dẫn không thay đổi nếu thất bại.
 */
function _wp_relative_upload_path( $path ) {
	$new_path = $path;

	$uploads = wp_get_upload_dir();
	if ( str_starts_with( $new_path, $uploads['basedir'] ) ) {
			$new_path = str_replace( $uploads['basedir'], '', $new_path );
			$new_path = ltrim( $new_path, '/' );
	}

	/**
	 * Lọc đường dẫn tương đối đến tệp đã tải lên.
	 *
	 * @since 2.9.0
	 *
	 * @param string $new_path Đường dẫn tương đối đến tệp.
	 * @param string $path     Đường dẫn đầy đủ đến tệp.
	 */
	return apply_filters( '_wp_relative_upload_path', $new_path, $path );
}

/**
 * Lấy tất cả các bài viết con của bài viết cha theo ID.
 *
 * Thông thường, nếu không có cải tiến nào, các bài con sẽ áp dụng cho trang. Trong
 * ngữ cảnh hoạt động nội bộ của WordPress, trang, bài viết và tệp đính kèm
 * dùng chung một bảng, nên chức năng này có thể áp dụng cho bất kỳ loại nào.
 * Cần lưu ý rằng mặc dù hàm này không hoạt động trên bài viết, nhưng điều đó
 * không có nghĩa là nó không thể hoạt động trên bài viết. Bạn nên biết rõ
 * ngữ cảnh bạn muốn lấy các bài con.
 *
 * Tệp đính kèm cũng có thể là con của một bài viết, nên nếu đó là câu
 * khẳng định chính xác (cần được xác minh), thì có thể lấy tất cả các tệp
 * đính kèm cho một bài viết. Tệp đính kèm đã thay đổi kể từ phiên bản 2.5,
 * nên điều này có thể không chính xác, nhưng nó là ví dụ chung về những gì có thể làm.
 *
 * Các tham số được liệt kê làm mặc định dành cho hàm này và cả hàm
 * get_posts(). Các tham số được kết hợp với giá trị mặc định của get_children
 * và sau đó được truyền cho hàm get_posts(), chấp nhận thêm các tham số khác.
 * Bạn có thể thay thế các giá trị mặc định trong hàm này, được liệt kê bên dưới
 * và các tham số bổ sung được liệt kê trong hàm get_posts().
 *
 * 'post_parent' là tham số quan trọng nhất và cần chú ý đặc biệt đến
 * tham số $args. Nếu bạn truyền đối tượng hoặc số nguyên, thì chỉ
 * 'post_parent' được lấy và mọi thứ khác bị mất. Nếu bạn không chỉ định
 * tham số nào, thì hệ thống giả định bạn đang ở trong Vòng lặp (The Loop)
 * và post parent sẽ được lấy từ bài viết hiện tại.
 *
 * Tham số 'post_parent' là ID để lấy các bài con. 'numberposts' là số
 * lượng bài viết cần lấy với mặc định là '-1', dùng để lấy tất cả bài viết.
 * Đặt số lớn hơn 0 sẽ chỉ lấy đúng số lượng bài viết đó.
 *
 * Tham số 'post_type' và 'post_status' có thể dùng để chọn tiêu chí
 * bài viết cần lấy. 'post_type' có thể là bất kỳ giá trị nào, nhưng các
 * post type của WordPress là 'post', 'pages' và 'attachments'. Tham số
 * 'post_status' sẽ chấp nhận bất kỳ trạng thái bài viết nào trong bảng quản trị.
 *
 * @since 2.0.0
 *
 * @see get_posts()
 * @todo Kiểm tra tính hợp lệ của mô tả.
 *
 * @global WP_Post $post Đối tượng bài viết toàn cục.
 *
 * @param mixed  $args   Tùy chọn. Tham số do người dùng định nghĩa để thay thế giá trị mặc định. Mặc định rỗng.
 * @param string $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                       tương ứng với đối tượng WP_Post, mảng liên kết, hoặc mảng số.
 *                       Mặc định OBJECT.
 * @return WP_Post[]|array[]|int[] Mảng các đối tượng bài viết, mảng, hoặc ID, tùy thuộc vào `$output`.
 */
function get_children( $args = '', $output = OBJECT ) {
	$kids = array();
	if ( empty( $args ) ) {
		if ( isset( $GLOBALS['post'] ) ) {
			$args = array( 'post_parent' => (int) $GLOBALS['post']->post_parent );
		} else {
			return $kids;
		}
	} elseif ( is_object( $args ) ) {
		$args = array( 'post_parent' => (int) $args->post_parent );
	} elseif ( is_numeric( $args ) ) {
		$args = array( 'post_parent' => (int) $args );
	}

	$defaults = array(
		'numberposts' => -1,
		'post_type'   => 'any',
		'post_status' => 'any',
		'post_parent' => 0,
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	$children = get_posts( $parsed_args );

	if ( ! $children ) {
		return $kids;
	}

	if ( ! empty( $parsed_args['fields'] ) ) {
		return $children;
	}

	update_post_cache( $children );

	foreach ( $children as $key => $child ) {
		$kids[ $child->ID ] = $children[ $key ];
	}

	if ( OBJECT === $output ) {
		return $kids;
	} elseif ( ARRAY_A === $output ) {
		$weeuns = array();
		foreach ( (array) $kids as $kid ) {
			$weeuns[ $kid->ID ] = get_object_vars( $kids[ $kid->ID ] );
		}
		return $weeuns;
	} elseif ( ARRAY_N === $output ) {
		$babes = array();
		foreach ( (array) $kids as $kid ) {
			$babes[ $kid->ID ] = array_values( get_object_vars( $kids[ $kid->ID ] ) );
		}
		return $babes;
	} else {
		return $kids;
	}
}

/**
 * Lấy thông tin mở rộng của bài viết (<!--more-->).
 *
 * Không nên có khoảng trắng sau dấu gạch ngang thứ hai và trước từ
 * 'more'. Có thể có văn bản hoặc khoảng trắng sau từ 'more', nhưng sẽ không
 * được tham chiếu.
 *
 * Mảng trả về có các khóa 'main', 'extended' và 'more_text'. Main chứa văn bản trước
 * thẻ `<!--more-->`. Khóa 'extended' chứa nội dung sau
 * comment `<!--more-->`. Khóa 'more_text' chứa văn bản "Đọc thêm" tùy chỉnh.
 *
 * @since 1.0.0
 *
 * @param string $post Nội dung bài viết.
 * @return string[] {
 *     Thông tin mở rộng của bài viết.
 *
 *     @type string $main      Nội dung trước thẻ more.
 *     @type string $extended  Nội dung sau thẻ more.
 *     @type string $more_text Văn bản đọc thêm tùy chỉnh, hoặc chuỗi rỗng.
 * }
 */
function get_extended( $post ) {
	// Khớp các liên kết more kiểu mới.
	if ( preg_match( '/<!--more(.*?)?-->/', $post, $matches ) ) {
		list($main, $extended) = explode( $matches[0], $post, 2 );
		$more_text             = $matches[1];
	} else {
		$main      = $post;
		$extended  = '';
		$more_text = '';
	}

	// Khoảng trắng đầu và cuối.
	$main      = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $main );
	$extended  = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $extended );
	$more_text = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $more_text );

	return array(
		'main'      => $main,
		'extended'  => $extended,
		'more_text' => $more_text,
	);
}

/**
 * Lấy dữ liệu bài viết theo ID bài viết hoặc đối tượng bài viết.
 *
 * Xem sanitize_post() để biết các giá trị $filter tùy chọn. Ngoài ra, tham số
 * `$post` phải được truyền dưới dạng biến vì nó được truyền theo tham chiếu.
 *
 * @since 1.5.1
 *
 * @global WP_Post $post Đối tượng bài viết toàn cục.
 *
 * @param int|WP_Post|null $post   Tùy chọn. ID bài viết hoặc đối tượng bài viết. `null`, `false`, `0` và các giá trị
 *                                 falsey khác của PHP trả về bài viết toàn cục hiện tại trong vòng lặp. ID bài viết
 *                                 hợp lệ về số nhưng trỏ đến bài không tồn tại trả về `null`. Mặc định global $post.
 * @param string           $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                                 tương ứng với đối tượng WP_Post, mảng liên kết, hoặc mảng số.
 *                                 Mặc định OBJECT.
 * @param string           $filter Tùy chọn. Loại filter cần áp dụng. Chấp nhận 'raw', 'edit', 'db',
 *                                 hoặc 'display'. Mặc định 'raw'.
 * @return WP_Post|array|null Kiểu tương ứng với $output nếu thành công hoặc null nếu thất bại.
 *                            Khi $output là OBJECT, một instance `WP_Post` được trả về.
 */
function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
	if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post'];
	}

	if ( $post instanceof WP_Post ) {
		$_post = $post;
	} elseif ( is_object( $post ) ) {
		if ( empty( $post->filter ) ) {
			$_post = sanitize_post( $post, 'raw' );
			$_post = new WP_Post( $_post );
		} elseif ( 'raw' === $post->filter ) {
			$_post = new WP_Post( $post );
		} else {
			$_post = WP_Post::get_instance( $post->ID );
		}
	} else {
		$_post = WP_Post::get_instance( $post );
	}

	if ( ! $_post ) {
		return null;
	}

	$_post = $_post->filter( $filter );

	if ( ARRAY_A === $output ) {
		return $_post->to_array();
	} elseif ( ARRAY_N === $output ) {
		return array_values( $_post->to_array() );
	}

	return $_post;
}

/**
 * Lấy các ID tổ tiên của một bài viết.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng bài viết.
 * @return int[] Mảng các ID tổ tiên hoặc mảng rỗng nếu không có.
 */
function get_post_ancestors( $post ) {
	$post = get_post( $post );

	if ( ! $post || empty( $post->post_parent ) || $post->post_parent === $post->ID ) {
		return array();
	}

	$ancestors = array();

	$id          = $post->post_parent;
	$ancestors[] = $id;

	while ( $ancestor = get_post( $id ) ) {
		// Phát hiện vòng lặp: Nếu tổ tiên đã được thấy trước đó, thoát.
		if ( empty( $ancestor->post_parent ) || $ancestor->post_parent === $post->ID
			|| in_array( $ancestor->post_parent, $ancestors, true )
		) {
			break;
		}

		$id          = $ancestor->post_parent;
		$ancestors[] = $id;
	}

	return $ancestors;
}

/**
 * Lấy dữ liệu từ một trường bài viết dựa trên ID bài viết.
 *
 * Ví dụ về trường bài viết là 'post_type', 'post_status', 'post_content',
 * v.v. và dựa trên thuộc tính hoặc tên khóa của đối tượng bài viết.
 *
 * Các giá trị ngữ cảnh dựa trên các hàm filter taxonomy và
 * các giá trị được hỗ trợ nằm trong các hàm đó.
 *
 * @since 2.3.0
 * @since 4.5.0 Tham số `$post` được đặt là tùy chọn.
 *
 * @see sanitize_post_field()
 *
 * @param string      $field   Tên trường bài viết.
 * @param int|WP_Post $post    Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @param string      $context Tùy chọn. Cách lọc trường. Chấp nhận 'raw', 'edit', 'db',
 *                             hoặc 'display'. Mặc định 'display'.
 * @return string Giá trị của trường bài viết nếu thành công, chuỗi rỗng nếu thất bại.
 */
function get_post_field( $field, $post = null, $context = 'display' ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	if ( ! isset( $post->$field ) ) {
		return '';
	}

	return sanitize_post_field( $field, $post->$field, $post->ID, $context );
}

/**
 * Lấy kiểu mime của tệp đính kèm dựa trên ID.
 *
 * Hàm này có thể dùng với bất kỳ post type nào, nhưng hợp lý nhất với
 * tệp đính kèm.
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @return string|false Kiểu mime nếu thành công, false nếu thất bại.
 */
function get_post_mime_type( $post = null ) {
	$post = get_post( $post );

	if ( is_object( $post ) ) {
		return $post->post_mime_type;
	}

	return false;
}

/**
 * Lấy trạng thái bài viết dựa trên ID bài viết.
 *
 * Nếu ID bài viết là của tệp đính kèm, thì trạng thái bài viết cha sẽ được
 * trả về thay thế.
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @return string|false Trạng thái bài viết nếu thành công, false nếu thất bại.
 */
function get_post_status( $post = null ) {
	// Chuẩn hóa đối tượng bài viết nếu cần, bỏ qua chuẩn hóa nếu được gọi từ get_sample_permalink().
	if ( ! $post instanceof WP_Post || ! isset( $post->filter ) || 'sample' !== $post->filter ) {
		$post = get_post( $post );
	}

	if ( ! is_object( $post ) ) {
		return false;
	}

	$post_status = $post->post_status;

	if (
		'attachment' === $post->post_type &&
		'inherit' === $post_status
	) {
		if (
			0 === $post->post_parent ||
			! get_post( $post->post_parent ) ||
			$post->ID === $post->post_parent
		) {
			// Tệp đính kèm chưa gắn với trạng thái inherit được giả định là đã xuất bản.
			$post_status = 'publish';
		} elseif ( 'trash' === get_post_status( $post->post_parent ) ) {
			// Lấy trạng thái cha trước khi đưa vào thùng rác.
			$post_status = get_post_meta( $post->post_parent, '_wp_trash_meta_status', true );

			if ( ! $post_status ) {
				// Giả định là đã xuất bản như trên.
				$post_status = 'publish';
			}
		} else {
			$post_status = get_post_status( $post->post_parent );
		}
	} elseif (
		'attachment' === $post->post_type &&
		! in_array( $post_status, array( 'private', 'trash', 'auto-draft' ), true )
	) {
		/*
		 * Đảm bảo tệp đính kèm không kế thừa có trạng thái được phép là 'private', 'trash', 'auto-draft'.
		 * Điều này để phù hợp với logic trong wp_insert_post().
		 *
		 * Lưu ý: 'inherit' được loại trừ khỏi kiểm tra này vì nó được giải quyết thành
		 * trạng thái của bài viết cha trong khối logic phía trên.
		 */
		$post_status = 'publish';
	}

	/**
	 * Lọc trạng thái bài viết.
	 *
	 * @since 4.4.0
	 * @since 5.7.0 Post type tệp đính kèm giờ cũng được truyền qua filter này.
	 *
	 * @param string  $post_status Trạng thái bài viết.
	 * @param WP_Post $post        Đối tượng bài viết.
	 */
	return apply_filters( 'get_post_status', $post_status, $post );
}

/**
 * Lấy tất cả các trạng thái bài viết được WordPress hỗ trợ.
 *
 * Bài viết có một tập hợp giới hạn các giá trị trạng thái hợp lệ, hàm này cung cấp
 * các giá trị post_status và mô tả của chúng.
 *
 * @since 2.5.0
 *
 * @return string[] Mảng các nhãn trạng thái bài viết được đánh khóa theo trạng thái.
 */
function get_post_statuses() {
	$status = array(
		'draft'   => __( 'Draft' ),
		'pending' => __( 'Pending Review' ),
		'private' => __( 'Private' ),
		'publish' => __( 'Published' ),
	);

	return $status;
}

/**
 * Lấy tất cả các trạng thái trang được WordPress hỗ trợ.
 *
 * Trang có một tập hợp giới hạn các giá trị trạng thái hợp lệ, hàm này cung cấp
 * các giá trị post_status và mô tả của chúng.
 *
 * @since 2.5.0
 *
 * @return string[] Mảng các nhãn trạng thái trang được đánh khóa theo trạng thái.
 */
function get_page_statuses() {
	$status = array(
		'draft'   => __( 'Draft' ),
		'private' => __( 'Private' ),
		'publish' => __( 'Published' ),
	);

	return $status;
}

/**
 * Trả về các trạng thái cho yêu cầu quyền riêng tư.
 *
 * @since 4.9.6
 * @access private
 *
 * @return string[] Mảng các nhãn trạng thái yêu cầu quyền riêng tư được đánh khóa theo trạng thái.
 */
function _wp_privacy_statuses() {
	return array(
		'request-pending'   => _x( 'Pending', 'request status' ),      // Đang chờ xác nhận từ người dùng.
		'request-confirmed' => _x( 'Confirmed', 'request status' ),    // Người dùng đã xác nhận hành động.
		'request-failed'    => _x( 'Failed', 'request status' ),       // Người dùng xác nhận hành động thất bại.
		'request-completed' => _x( 'Completed', 'request status' ),    // Quản trị viên đã xử lý yêu cầu.
	);
}

/**
 * Đăng ký trạng thái bài viết. Không sử dụng trước init.
 *
 * Một hàm đơn giản để tạo hoặc sửa đổi trạng thái bài viết dựa trên
 * các tham số đã cho. Hàm sẽ chấp nhận một mảng (tham số tùy chọn thứ hai),
 * cùng với một chuỗi cho tên trạng thái bài viết.
 *
 * Các đối số có tiền tố gạch dưới _ không nên được plugin và theme sử dụng.
 *
 * @since 3.0.0
 *
 * @global stdClass[] $wp_post_statuses Chèn đối tượng trạng thái bài viết mới vào danh sách
 *
 * @param string       $post_status Tên trạng thái bài viết.
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số trạng thái bài viết.
 *
 *     @type bool|string $label                     Tên mô tả cho trạng thái bài viết được đánh dấu
 *                                                  để dịch. Mặc định là giá trị của $post_status.
 *     @type array|false $label_count               Văn bản số nhiều nooped từ _n_noop() để cung cấp dạng
 *                                                  số ít và số nhiều của nhãn cho đếm. Mặc định false
 *                                                  nghĩa là đối số `$label` sẽ được dùng cho cả
 *                                                  dạng số ít và số nhiều của nhãn này.
 *     @type bool        $exclude_from_search       Có loại trừ bài viết với trạng thái này
 *                                                  khỏi kết quả tìm kiếm không. Mặc định là giá trị của $internal.
 *     @type bool        $_builtin                  Trạng thái có phải là tích hợp sẵn không. Chỉ dùng cho lõi.
 *                                                  Mặc định false.
 *     @type bool        $public                    Bài viết với trạng thái này có nên hiển thị
 *                                                  ở giao diện người dùng không. Mặc định false.
 *     @type bool        $internal                  Trạng thái chỉ dùng nội bộ hay không.
 *                                                  Mặc định false.
 *     @type bool        $protected                 Bài viết với trạng thái này có nên được bảo vệ không.
 *                                                  Mặc định false.
 *     @type bool        $private                   Bài viết với trạng thái này có nên là riêng tư không.
 *                                                  Mặc định false.
 *     @type bool        $publicly_queryable        Bài viết với trạng thái này có nên được truy vấn
 *                                                  công khai không. Mặc định là giá trị của $public.
 *     @type bool        $show_in_admin_all_list    Có bao gồm bài viết trong danh sách chỉnh sửa cho
 *                                                  post type của chúng không. Mặc định là giá trị ngược
 *                                                  của $internal.
 *     @type bool        $show_in_admin_status_list Hiển thị trong danh sách trạng thái với số đếm bài viết ở
 *                                                  đầu danh sách chỉnh sửa,
 *                                                  ví dụ: Tất cả (12) | Đã xuất bản (9) | Trạng thái tùy chỉnh (2)
 *                                                  Mặc định là giá trị ngược của $internal.
 *     @type bool        $date_floating             Bài viết có ngày tạo nổi hay không.
 *                                                  Mặc định false.
 * }
 * @return object
 */
function register_post_status( $post_status, $args = array() ) {
	global $wp_post_statuses;

	if ( ! is_array( $wp_post_statuses ) ) {
		$wp_post_statuses = array();
	}

	// Các đối số có tiền tố gạch dưới được dành riêng cho sử dụng nội bộ.
	$defaults = array(
		'label'                     => false,
		'label_count'               => false,
		'exclude_from_search'       => null,
		'_builtin'                  => false,
		'public'                    => null,
		'internal'                  => null,
		'protected'                 => null,
		'private'                   => null,
		'publicly_queryable'        => null,
		'show_in_admin_status_list' => null,
		'show_in_admin_all_list'    => null,
		'date_floating'             => null,
	);
	$args     = wp_parse_args( $args, $defaults );
	$args     = (object) $args;

	$post_status = sanitize_key( $post_status );
	$args->name  = $post_status;

	// Thiết lập các giá trị mặc định khác nhau.
	if ( null === $args->public && null === $args->internal && null === $args->protected && null === $args->private ) {
		$args->internal = true;
	}

	if ( null === $args->public ) {
		$args->public = false;
	}

	if ( null === $args->private ) {
		$args->private = false;
	}

	if ( null === $args->protected ) {
		$args->protected = false;
	}

	if ( null === $args->internal ) {
		$args->internal = false;
	}

	if ( null === $args->publicly_queryable ) {
		$args->publicly_queryable = $args->public;
	}

	if ( null === $args->exclude_from_search ) {
		$args->exclude_from_search = $args->internal;
	}

	if ( null === $args->show_in_admin_all_list ) {
		$args->show_in_admin_all_list = ! $args->internal;
	}

	if ( null === $args->show_in_admin_status_list ) {
		$args->show_in_admin_status_list = ! $args->internal;
	}

	if ( null === $args->date_floating ) {
		$args->date_floating = false;
	}

	if ( false === $args->label ) {
		$args->label = $post_status;
	}

	if ( false === $args->label_count ) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular,WordPress.WP.I18n.NonSingularStringLiteralPlural
		$args->label_count = _n_noop( $args->label, $args->label );
	}

	$wp_post_statuses[ $post_status ] = $args;

	return $args;
}

/**
 * Lấy đối tượng trạng thái bài viết theo tên.
 *
 * @since 3.0.0
 *
 * @global stdClass[] $wp_post_statuses Danh sách trạng thái bài viết.
 *
 * @see register_post_status()
 *
 * @param string $post_status Tên của trạng thái bài viết đã đăng ký.
 * @return stdClass|null Đối tượng trạng thái bài viết.
 */
function get_post_status_object( $post_status ) {
	global $wp_post_statuses;

	if ( empty( $wp_post_statuses[ $post_status ] ) ) {
		return null;
	}

	return $wp_post_statuses[ $post_status ];
}

/**
 * Lấy danh sách các trạng thái bài viết.
 *
 * @since 3.0.0
 *
 * @global stdClass[] $wp_post_statuses Danh sách trạng thái bài viết.
 *
 * @see register_post_status()
 *
 * @param array|string $args     Tùy chọn. Mảng hoặc chuỗi các đối số trạng thái bài viết để so sánh với
 *                               các thuộc tính của đối tượng toàn cục `$wp_post_statuses`. Mặc định mảng rỗng.
 * @param string       $output   Tùy chọn. Kiểu trả về, 'names' hoặc 'objects'. Mặc định 'names'.
 * @param string       $operator Tùy chọn. Phép toán logic cần thực hiện. 'or' nghĩa là chỉ cần một phần tử
 *                               từ mảng khớp; 'and' nghĩa là tất cả phần tử phải khớp.
 *                               Mặc định 'and'.
 * @return string[]|stdClass[] Danh sách tên hoặc đối tượng trạng thái bài viết.
 */
function get_post_stati( $args = array(), $output = 'names', $operator = 'and' ) {
	global $wp_post_statuses;

	$field = ( 'names' === $output ) ? 'name' : false;

	return wp_filter_object_list( $wp_post_statuses, $args, $operator, $field );
}

/**
 * Xác định xem post type có phân cấp hay không.
 *
 * Giá trị trả về false cũng có thể có nghĩa là post type không tồn tại.
 *
 * @since 3.0.0
 *
 * @see get_post_type_object()
 *
 * @param string $post_type Tên post type.
 * @return bool Post type có phân cấp hay không.
 */
function is_post_type_hierarchical( $post_type ) {
	if ( ! post_type_exists( $post_type ) ) {
		return false;
	}

	$post_type = get_post_type_object( $post_type );
	return $post_type->hierarchical;
}

/**
 * Xác định xem một post type đã được đăng ký hay chưa.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 3.0.0
 *
 * @see get_post_type_object()
 *
 * @param string $post_type Tên post type.
 * @return bool Post type đã được đăng ký hay chưa.
 */
function post_type_exists( $post_type ) {
	return (bool) get_post_type_object( $post_type );
}

/**
 * Lấy post type của bài viết hiện tại hoặc bài viết đã cho.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @return string|false          Post type nếu thành công, false nếu thất bại.
 */
function get_post_type( $post = null ) {
	$post = get_post( $post );
	if ( $post ) {
		return $post->post_type;
	}

	return false;
}

/**
 * Lấy đối tượng post type theo tên.
 *
 * @since 3.0.0
 * @since 4.6.0 Đối tượng trả về giờ là instance của `WP_Post_Type`.
 *
 * @global array $wp_post_types Danh sách các post type.
 *
 * @see register_post_type()
 *
 * @param string $post_type Tên của post type đã đăng ký.
 * @return WP_Post_Type|null Đối tượng WP_Post_Type nếu tồn tại, null nếu không.
 */
function get_post_type_object( $post_type ) {
	global $wp_post_types;

	if ( ! is_scalar( $post_type ) || empty( $wp_post_types[ $post_type ] ) ) {
		return null;
	}

	return $wp_post_types[ $post_type ];
}

/**
 * Lấy danh sách tất cả các đối tượng post type đã đăng ký.
 *
 * @since 2.9.0
 *
 * @global array $wp_post_types Danh sách các post type.
 *
 * @see register_post_type() để biết các đối số được chấp nhận.
 *
 * @param array|string $args     Tùy chọn. Mảng các đối số key => value để so khớp với
 *                               các đối tượng post type. Mặc định mảng rỗng.
 * @param string       $output   Tùy chọn. Kiểu trả về. 'names'
 *                               hoặc 'objects'. Mặc định 'names'.
 * @param string       $operator Tùy chọn. Phép toán logic cần thực hiện. 'or' nghĩa là chỉ cần một
 *                               phần tử từ mảng khớp; 'and' nghĩa là tất cả phần tử phải khớp;
 *                               'not' nghĩa là không phần tử nào được khớp. Mặc định 'and'.
 * @return string[]|WP_Post_Type[] Mảng tên hoặc đối tượng post type.
 */
function get_post_types( $args = array(), $output = 'names', $operator = 'and' ) {
	global $wp_post_types;

	$field = ( 'names' === $output ) ? 'name' : false;

	return wp_filter_object_list( $wp_post_types, $args, $operator, $field );
}

/**
 * Đăng ký một post type.
 *
 * Lưu ý: Việc đăng ký post type không nên được hook trước action
 * {@see 'init'}. Ngoài ra, bất kỳ kết nối taxonomy nào cũng nên được
 * đăng ký qua đối số `$taxonomies` để đảm bảo tính nhất quán
 * khi các hook như {@see 'parse_query'} hoặc {@see 'pre_get_posts'}
 * được sử dụng.
 *
 * Post type có thể hỗ trợ bất kỳ số lượng tính năng lõi tích hợp nào như
 * meta box, trường tùy chỉnh, ảnh đại diện bài viết, trạng thái bài viết,
 * bình luận, và nhiều hơn nữa. Xem đối số `$supports` để biết danh sách đầy đủ
 * các tính năng được hỗ trợ.
 *
 * @since 2.9.0
 * @since 3.0.0 Đối số `show_ui` giờ được áp dụng trên màn hình tạo bài viết mới.
 * @since 4.4.0 Đối số `show_ui` giờ được áp dụng trên màn hình danh sách post type
 *              và màn hình chỉnh sửa bài viết.
 * @since 4.6.0 Đối tượng post type trả về giờ là instance của `WP_Post_Type`.
 * @since 4.7.0 Giới thiệu đối số `show_in_rest`, `rest_base` và `rest_controller_class`
 *              để đăng ký post type trong REST API.
 * @since 5.0.0 Đối số `template` và `template_lock` được thêm vào.
 * @since 5.3.0 Đối số `supports` giờ chấp nhận mảng đối số cho một tính năng.
 * @since 5.9.0 Đối số `rest_namespace` được thêm vào.
 *
 * @global array $wp_post_types Danh sách các post type.
 *
 * @param string       $post_type Khóa post type. Không được vượt quá 20 ký tự và chỉ chứa
 *                                ký tự chữ thường, dấu gạch ngang và gạch dưới. Xem sanitize_key().
 * @param array|string $args {
 *     Mảng hoặc chuỗi các đối số để đăng ký post type.
 *
 *     @type string       $label                           Tên post type hiển thị trong menu. Thường ở dạng số nhiều.
 *                                                         Mặc định là giá trị của $labels['name'].
 *     @type string[]     $labels                          Mảng các nhãn cho post type này. Nếu không đặt, nhãn bài viết
 *                                                         được kế thừa cho loại không phân cấp và nhãn trang
 *                                                         cho loại phân cấp. Xem get_post_type_labels() để biết danh sách đầy đủ
 *                                                         các nhãn được hỗ trợ.
 *     @type string       $description                     Mô tả ngắn gọn về post type.
 *                                                         Mặc định rỗng.
 *     @type bool         $public                          Post type có dùng công khai qua giao diện quản trị
 *                                                         hoặc bởi người dùng front-end không. Mặc dù các thiết lập mặc định
 *                                                         của $exclude_from_search, $publicly_queryable, $show_ui,
 *                                                         và $show_in_nav_menus được kế thừa từ $public, mỗi cái không
 *                                                         phụ thuộc vào mối quan hệ này và kiểm soát một mục đích rất cụ thể.
 *                                                         Mặc định false.
 *     @type bool         $hierarchical                    Post type có phân cấp hay không (ví dụ: trang). Mặc định false.
 *     @type bool         $exclude_from_search             Có loại trừ bài viết của post type này khỏi kết quả
 *                                                         tìm kiếm front-end không. Mặc định là giá trị ngược của $public.
 *     @type bool         $publicly_queryable              Có thể thực hiện truy vấn trên front-end cho post type
 *                                                         như một phần của parse_request() không. Các endpoint bao gồm:
 *                                                          * ?post_type={post_type_key}
 *                                                          * ?{post_type_key}={single_post_slug}
 *                                                          * ?{post_type_query_var}={single_post_slug}
 *                                                         Nếu không đặt, mặc định được kế thừa từ $public.
 *     @type bool         $show_ui                         Có tạo và cho phép UI để quản lý post type này trong
 *                                                         trang quản trị không. Mặc định là giá trị của $public.
 *     @type bool|string  $show_in_menu                    Hiển thị post type ở đâu trong menu quản trị. Để hoạt động, $show_ui
 *                                                         phải là true. Nếu true, post type hiển thị trong menu cấp cao nhất
 *                                                         riêng. Nếu false, không hiển thị menu. Nếu là chuỗi của menu cấp cao
 *                                                         đã có ('tools.php' hoặc 'edit.php?post_type=page', ví dụ),
 *                                                         post type sẽ được đặt làm menu con của menu đó.
 *                                                         Mặc định là giá trị của $show_ui.
 *     @type bool         $show_in_nav_menus               Cho phép chọn post type này trong menu điều hướng.
 *                                                         Mặc định là giá trị của $public.
 *     @type bool         $show_in_admin_bar               Cho phép post type này hiển thị qua thanh quản trị. Mặc định là giá trị
 *                                                         của $show_in_menu.
 *     @type bool         $show_in_rest                    Có bao gồm post type trong REST API không. Đặt true
 *                                                         để post type có sẵn trong trình soạn thảo block.
 *     @type string       $rest_base                       Thay đổi URL cơ sở của route REST API. Mặc định là $post_type.
 *     @type string       $rest_namespace                  Thay đổi URL namespace của route REST API. Mặc định là wp/v2.
 *     @type string       $rest_controller_class           Tên lớp controller REST API. Mặc định là 'WP_REST_Posts_Controller'.
 *     @type string|bool  $autosave_rest_controller_class  Tên lớp controller REST API. Mặc định là 'WP_REST_Autosaves_Controller'.
 *     @type string|bool  $revisions_rest_controller_class Tên lớp controller REST API. Mặc định là 'WP_REST_Revisions_Controller'.
 *     @type bool         $late_route_registration         Cờ để chỉ định controller REST API cho tự động lưu / bản sửa đổi
 *                                                         nên được đăng ký trước/sau controller post type.
 *     @type int          $menu_position                   Vị trí trong thứ tự menu mà post type nên xuất hiện. Để hoạt động,
 *                                                         $show_in_menu phải là true. Mặc định null (ở cuối).
 *     @type string       $menu_icon                       URL đến biểu tượng dùng cho menu này. Truyền SVG mã hóa base64
 *                                                         sử dụng data URI, sẽ được tô màu phù hợp với bảng màu
 *                                                         -- nên bắt đầu bằng 'data:image/svg+xml;base64,'. Truyền tên
 *                                                         của lớp trợ giúp Dashicons để dùng biểu tượng font, ví dụ
 *                                                         'dashicons-chart-pie'. Truyền 'none' để trống div.wp-menu-image
 *                                                         để có thể thêm biểu tượng qua CSS. Mặc định dùng biểu tượng bài viết.
 *     @type string|array $capability_type                 Chuỗi dùng để xây dựng quyền đọc, sửa và xóa.
 *                                                         Có thể truyền dạng mảng để cho phép dạng số nhiều thay thế khi dùng
 *                                                         đối số này làm cơ sở để xây dựng quyền, ví dụ
 *                                                         array('story', 'stories'). Mặc định 'post'.
 *     @type string[]     $capabilities                    Mảng quyền cho post type này. $capability_type được dùng
 *                                                         làm cơ sở để xây dựng quyền theo mặc định.
 *                                                         Xem get_post_type_capabilities().
 *     @type bool         $map_meta_cap                    Có sử dụng xử lý meta capability mặc định nội bộ không.
 *                                                         Mặc định false.
 *     @type array|false  $supports                        (Các) tính năng lõi mà post type hỗ trợ. Đóng vai trò bí danh cho việc gọi
 *                                                         add_post_type_support() trực tiếp. Các tính năng lõi gồm 'title',
 *                                                         'editor', 'comments', 'revisions', 'trackbacks', 'author', 'excerpt',
 *                                                         'page-attributes', 'thumbnail', 'custom-fields', và 'post-formats'.
 *                                                         Ngoài ra, tính năng 'revisions' quyết định post type có lưu bản sửa đổi không,
 *                                                         tính năng 'autosave' quyết định post type có được tự động lưu không,
 *                                                         và tính năng 'comments' quyết định số đếm bình luận có hiển thị
 *                                                         trên màn hình chỉnh sửa không. Vì lý do tương thích ngược,
 *                                                         thêm hỗ trợ 'editor' cũng ngầm thêm hỗ trợ 'autosave'. Tính năng cũng có thể
 *                                                         được chỉ định dạng mảng đối số để cung cấp thông tin bổ sung
 *                                                         về việc hỗ trợ tính năng đó.
 *                                                         Ví dụ: `array( 'my_feature', array( 'field' => 'value' ) )`.
 *                                                         Nếu false, không tính năng nào được thêm.
 *                                                         Mặc định là mảng chứa 'title' và 'editor'.
 *     @type callable     $register_meta_box_cb            Cung cấp hàm callback thiết lập các meta box cho
 *                                                         form chỉnh sửa. Thực hiện lời gọi remove_meta_box() và add_meta_box() trong
 *                                                         callback. Mặc định null.
 *     @type string[]     $taxonomies                      Mảng các định danh taxonomy sẽ được đăng ký cho
 *                                                         post type. Taxonomy có thể đăng ký sau với register_taxonomy()
 *                                                         hoặc register_taxonomy_for_object_type().
 *                                                         Mặc định mảng rỗng.
 *     @type bool|string  $has_archive                     Có nên có lưu trữ post type không, hoặc nếu là chuỗi, slug
 *                                                         lưu trữ cần dùng. Sẽ tạo quy tắc rewrite phù hợp nếu
 *                                                         $rewrite được bật. Mặc định false.
 *     @type bool|array   $rewrite                         {
 *         Kích hoạt xử lý rewrite cho post type này. Để ngăn rewrite, đặt false.
 *         Mặc định true, dùng $post_type làm slug. Để chỉ định quy tắc rewrite, có thể truyền
 *         mảng với bất kỳ khóa nào sau đây:
 *
 *         @type string $slug       Tùy chỉnh slug permastruct. Mặc định là khóa $post_type.
 *         @type bool   $with_front Permastruct có nên được thêm tiền tố WP_Rewrite::$front không.
 *                                  Mặc định true.
 *         @type bool   $feeds      Permastruct feed có nên được xây dựng cho post type này không.
 *                                  Mặc định là giá trị của $has_archive.
 *         @type bool   $pages      Permastruct có nên hỗ trợ phân trang không. Mặc định true.
 *         @type int    $ep_mask    Mặt nạ endpoint cần gán. Nếu không chỉ định và permalink_epmask được đặt,
 *                                  kế thừa từ $permalink_epmask. Nếu không chỉ định và permalink_epmask
 *                                  chưa đặt, mặc định là EP_PERMALINK.
 *     }
 *     @type string|bool  $query_var                      Đặt khóa query_var cho post type này. Mặc định là khóa $post_type.
 *                                                        Nếu false, post type không thể tải tại
 *                                                        ?{query_var}={post_slug}. Nếu chỉ định dạng chuỗi, truy vấn
 *                                                        ?{query_var_string}={post_slug} sẽ hợp lệ.
 *     @type bool         $can_export                     Có cho phép xuất post type này không. Mặc định true.
 *     @type bool         $delete_with_user               Có xóa bài viết của loại này khi xóa người dùng không.
 *                                                          * Nếu true, bài viết của loại này thuộc người dùng sẽ được chuyển
 *                                                            vào Thùng rác khi người dùng bị xóa.
 *                                                          * Nếu false, bài viết của loại này thuộc người dùng sẽ *không*
 *                                                            bị đưa vào thùng rác hoặc xóa.
 *                                                          * Nếu không đặt (mặc định), bài viết bị đưa vào thùng rác nếu post type hỗ trợ
 *                                                            tính năng 'author'. Ngược lại bài viết không bị đưa vào thùng rác hoặc xóa.
 *                                                        Mặc định null.
 *     @type array        $template                       Mảng các block dùng làm trạng thái khởi tạo mặc định cho phiên
 *                                                        soạn thảo. Mỗi mục nên là mảng chứa tên block và
 *                                                        thuộc tính tùy chọn. Mặc định mảng rỗng.
 *     @type string|false $template_lock                  Template block có nên bị khóa nếu $template được đặt không.
 *                                                        * Nếu đặt 'all', người dùng không thể chèn block mới,
 *                                                          di chuyển block hiện có và xóa block.
 *                                                        * Nếu đặt 'insert', người dùng có thể di chuyển block hiện có
 *                                                          nhưng không thể chèn block mới và xóa block.
 *                                                        Mặc định false.
 *     @type bool         $_builtin                       CHỈ DÙNG NỘI BỘ! True nếu post type này là loại gốc hoặc
 *                                                        "tích hợp sẵn". Mặc định false.
 *     @type string       $_edit_link                     CHỈ DÙNG NỘI BỘ! Đoạn URL dùng cho liên kết chỉnh sửa của
 *                                                        post type này. Mặc định 'post.php?post=%d'.
 * }
 * @return WP_Post_Type|WP_Error Đối tượng post type đã đăng ký nếu thành công,
 *                               đối tượng WP_Error nếu thất bại.
 */
function register_post_type( $post_type, $args = array() ) {
	global $wp_post_types;

	if ( ! is_array( $wp_post_types ) ) {
		$wp_post_types = array();
	}

	// Làm sạch tên post type.
	$post_type = sanitize_key( $post_type );

	if ( empty( $post_type ) || strlen( $post_type ) > 20 ) {
		_doing_it_wrong( __FUNCTION__, __( 'Post type names must be between 1 and 20 characters in length.' ), '4.2.0' );
		return new WP_Error( 'post_type_length_invalid', __( 'Post type names must be between 1 and 20 characters in length.' ) );
	}

	$post_type_object = new WP_Post_Type( $post_type, $args );
	$post_type_object->add_supports();
	$post_type_object->add_rewrite_rules();
	$post_type_object->register_meta_boxes();

	$wp_post_types[ $post_type ] = $post_type_object;

	$post_type_object->add_hooks();
	$post_type_object->register_taxonomies();

	/**
	 * Kích hoạt sau khi một post type được đăng ký.
	 *
	 * @since 3.3.0
	 * @since 4.6.0 Chuyển đổi tham số `$post_type` để chấp nhận đối tượng `WP_Post_Type`.
	 *
	 * @param string       $post_type        Post type.
	 * @param WP_Post_Type $post_type_object Các đối số dùng để đăng ký post type.
	 */
	do_action( 'registered_post_type', $post_type, $post_type_object );

	/**
	 * Kích hoạt sau khi một post type cụ thể được đăng ký.
	 *
	 * Phần động của tên filter, `$post_type`, tham chiếu đến khóa post type.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `registered_post_type_post`
	 *  - `registered_post_type_page`
	 *
	 * @since 6.0.0
	 *
	 * @param string       $post_type        Post type.
	 * @param WP_Post_Type $post_type_object Các đối số dùng để đăng ký post type.
	 */
	do_action( "registered_post_type_{$post_type}", $post_type, $post_type_object );

	return $post_type_object;
}

/**
 * Hủy đăng ký một post type.
 *
 * Không thể dùng để hủy đăng ký các post type tích hợp sẵn.
 *
 * @since 4.5.0
 *
 * @global array $wp_post_types Danh sách các post type.
 *
 * @param string $post_type Post type cần hủy đăng ký.
 * @return true|WP_Error True nếu thành công, WP_Error nếu thất bại hoặc post type không tồn tại.
 */
function unregister_post_type( $post_type ) {
	global $wp_post_types;

	if ( ! post_type_exists( $post_type ) ) {
		return new WP_Error( 'invalid_post_type', __( 'Invalid post type.' ) );
	}

	$post_type_object = get_post_type_object( $post_type );

	// Không cho phép hủy đăng ký các post type nội bộ.
	if ( $post_type_object->_builtin ) {
		return new WP_Error( 'invalid_post_type', __( 'Unregistering a built-in post type is not allowed' ) );
	}

	$post_type_object->remove_supports();
	$post_type_object->remove_rewrite_rules();
	$post_type_object->unregister_meta_boxes();
	$post_type_object->remove_hooks();
	$post_type_object->unregister_taxonomies();

	unset( $wp_post_types[ $post_type ] );

	/**
	 * Kích hoạt sau khi một post type đã được hủy đăng ký.
	 *
	 * @since 4.5.0
	 *
	 * @param string $post_type Khóa post type.
	 */
	do_action( 'unregistered_post_type', $post_type );

	return true;
}

/**
 * Xây dựng đối tượng chứa tất cả quyền của post type từ đối tượng post type.
 *
 * Quyền post type sử dụng đối số 'capability_type' làm cơ sở, nếu quyền
 * không được đặt trong mảng đối số 'capabilities' hoặc nếu đối số
 * 'capabilities' không được cung cấp.
 *
 * Đối số capability_type có thể được đăng ký tùy chọn dạng mảng, với giá trị
 * đầu tiên là dạng số ít và giá trị thứ hai là dạng số nhiều, ví dụ array('story', 'stories').
 * Nếu không, 's' sẽ được thêm vào giá trị để tạo dạng số nhiều. Sau khi
 * đăng ký, capability_type sẽ luôn là chuỗi của giá trị dạng số ít.
 *
 * Mặc định, tám khóa được chấp nhận trong mảng capabilities:
 *
 * - edit_post, read_post, và delete_post là các meta capability, sau đó
 *   thường được ánh xạ đến các primitive capability tương ứng tùy thuộc vào
 *   ngữ cảnh, tức là bài viết đang được sửa/đọc/xóa và người dùng hoặc
 *   vai trò đang được kiểm tra. Do đó các quyền này thường không được cấp
 *   trực tiếp cho người dùng hoặc vai trò.
 *
 * - edit_posts - Kiểm soát việc các đối tượng của post type này có thể được chỉnh sửa không.
 * - edit_others_posts - Kiểm soát việc các đối tượng thuộc loại này của người dùng khác
 *   có thể được chỉnh sửa không. Nếu post type không hỗ trợ tác giả, quyền này sẽ
 *   hoạt động như edit_posts.
 * - delete_posts - Kiểm soát việc các đối tượng của post type này có thể bị xóa không.
 * - publish_posts - Kiểm soát việc xuất bản các đối tượng của post type này.
 * - read_private_posts - Kiểm soát việc các đối tượng riêng tư có thể được đọc không.
 *
 * Năm primitive capability này được kiểm tra trong lõi ở nhiều vị trí khác nhau.
 * Ngoài ra còn có sáu primitive capability khác không được tham chiếu
 * trực tiếp trong lõi, ngoại trừ trong map_meta_cap(), hàm này lấy ba
 * meta capability đã đề cập ở trên và chuyển đổi chúng thành một hoặc nhiều primitive
 * capability phải được kiểm tra với người dùng hoặc vai trò, tùy thuộc vào ngữ cảnh.
 *
 * - read - Kiểm soát việc các đối tượng của post type này có thể được đọc không.
 * - delete_private_posts - Kiểm soát việc các đối tượng riêng tư có thể bị xóa không.
 * - delete_published_posts - Kiểm soát việc các đối tượng đã xuất bản có thể bị xóa không.
 * - delete_others_posts - Kiểm soát việc các đối tượng của người dùng khác có thể
 *   bị xóa không. Nếu post type không hỗ trợ tác giả, quyền này sẽ
 *   hoạt động như delete_posts.
 * - edit_private_posts - Kiểm soát việc các đối tượng riêng tư có thể được chỉnh sửa không.
 * - edit_published_posts - Kiểm soát việc các đối tượng đã xuất bản có thể được chỉnh sửa không.
 *
 * Các quyền bổ sung này chỉ được sử dụng trong map_meta_cap(). Do đó, chúng
 * chỉ được gán mặc định nếu post type được đăng ký với đối số 'map_meta_cap'
 * đặt là true (mặc định là false).
 *
 * @since 3.0.0
 * @since 5.4.0 'delete_posts' được bao gồm trong quyền mặc định.
 *
 * @see register_post_type()
 * @see map_meta_cap()
 *
 * @param object $args Các đối số đăng ký post type.
 * @return object Đối tượng chứa tất cả quyền dưới dạng biến thành viên.
 */
function get_post_type_capabilities( $args ) {
	if ( ! is_array( $args->capability_type ) ) {
		$args->capability_type = array( $args->capability_type, $args->capability_type . 's' );
	}

	// Cơ sở dạng số ít cho meta capability, cơ sở dạng số nhiều cho primitive capability.
	list( $singular_base, $plural_base ) = $args->capability_type;

	$default_capabilities = array(
		// Quyền Meta.
		'edit_post'          => 'edit_' . $singular_base,
		'read_post'          => 'read_' . $singular_base,
		'delete_post'        => 'delete_' . $singular_base,
		// Quyền nguyên thủy được sử dụng ngoài map_meta_cap():
		'edit_posts'         => 'edit_' . $plural_base,
		'edit_others_posts'  => 'edit_others_' . $plural_base,
		'delete_posts'       => 'delete_' . $plural_base,
		'publish_posts'      => 'publish_' . $plural_base,
		'read_private_posts' => 'read_private_' . $plural_base,
	);

	// Quyền nguyên thủy được sử dụng trong map_meta_cap():
	if ( $args->map_meta_cap ) {
		$default_capabilities_for_mapping = array(
			'read'                   => 'read',
			'delete_private_posts'   => 'delete_private_' . $plural_base,
			'delete_published_posts' => 'delete_published_' . $plural_base,
			'delete_others_posts'    => 'delete_others_' . $plural_base,
			'edit_private_posts'     => 'edit_private_' . $plural_base,
			'edit_published_posts'   => 'edit_published_' . $plural_base,
		);
		$default_capabilities             = array_merge( $default_capabilities, $default_capabilities_for_mapping );
	}

	$capabilities = array_merge( $default_capabilities, $args->capabilities );

	// Quyền tạo bài viết mặc định ánh xạ đến edit_posts:
	if ( ! isset( $capabilities['create_posts'] ) ) {
		$capabilities['create_posts'] = $capabilities['edit_posts'];
	}

	// Ghi nhớ các meta capability để tham chiếu sau này.
	if ( $args->map_meta_cap ) {
		_post_type_meta_capabilities( $capabilities );
	}

	return (object) $capabilities;
}

/**
 * Lưu trữ hoặc trả về danh sách các meta cap của post type cho map_meta_cap().
 *
 * @since 3.1.0
 * @access private
 *
 * @global array $post_type_meta_caps Dùng để lưu trữ các meta capability.
 *
 * @param string[] $capabilities Các meta capability của post type.
 */
function _post_type_meta_capabilities( $capabilities = null ) {
	global $post_type_meta_caps;

	foreach ( $capabilities as $core => $custom ) {
		if ( in_array( $core, array( 'read_post', 'delete_post', 'edit_post' ), true ) ) {
			$post_type_meta_caps[ $custom ] = $core;
		}
	}
}

/**
 * Xây dựng đối tượng chứa tất cả nhãn của post type từ đối tượng post type.
 *
 * Các khóa được chấp nhận trong mảng nhãn của đối tượng post type:
 *
 * - `name` - Tên chung cho post type, thường ở dạng số nhiều. Giống và được ghi đè
 *          bởi `$post_type_object->label`. Mặc định là 'Posts' / 'Pages'.
 * - `singular_name` - Tên cho một đối tượng của post type này. Mặc định là 'Post' / 'Page'.
 * - `add_new` - Nhãn để thêm mục mới. Mặc định là 'Add Post' / 'Add Page'.
 * - `add_new_item` - Nhãn để thêm mục đơn lẻ mới. Mặc định là 'Add Post' / 'Add Page'.
 * - `edit_item` - Nhãn để chỉnh sửa mục đơn lẻ. Mặc định là 'Edit Post' / 'Edit Page'.
 * - `new_item` - Nhãn cho tiêu đề trang mục mới. Mặc định là 'New Post' / 'New Page'.
 * - `view_item` - Nhãn để xem mục đơn lẻ. Mặc định là 'View Post' / 'View Page'.
 * - `view_items` - Nhãn để xem kho lưu trữ post type. Mặc định là 'View Posts' / 'View Pages'.
 * - `search_items` - Nhãn để tìm kiếm các mục. Mặc định là 'Search Posts' / 'Search Pages'.
 * - `not_found` - Nhãn dùng khi không tìm thấy mục nào. Mặc định là 'No posts found' / 'No pages found'.
 * - `not_found_in_trash` - Nhãn dùng khi không có mục nào trong Thùng rác. Mặc định là 'No posts found in Trash' /
 *                        'No pages found in Trash'.
 * - `parent_item_colon` - Nhãn dùng làm tiền tố cho cha của các mục phân cấp. Không dùng cho post type
 *                       không phân cấp. Mặc định là 'Parent Page:'.
 * - `all_items` - Nhãn biểu thị tất cả mục trong liên kết menu con. Mặc định là 'All Posts' / 'All Pages'.
 * - `archives` - Nhãn cho kho lưu trữ trong menu điều hướng. Mặc định là 'Post Archives' / 'Page Archives'.
 * - `attributes` - Nhãn cho hộp meta thuộc tính. Mặc định là 'Post Attributes' / 'Page Attributes'.
 * - `insert_into_item` - Nhãn cho nút khung media. Mặc định là 'Insert into post' / 'Insert into page'.
 * - `uploaded_to_this_item` - Nhãn cho bộ lọc khung media. Mặc định là 'Uploaded to this post' /
 *                           'Uploaded to this page'.
 * - `featured_image` - Nhãn cho tiêu đề hộp meta ảnh đại diện. Mặc định là 'Featured image'.
 * - `set_featured_image` - Nhãn để đặt ảnh đại diện. Mặc định là 'Set featured image'.
 * - `remove_featured_image` - Nhãn để xóa ảnh đại diện. Mặc định là 'Remove featured image'.
 * - `use_featured_image` - Nhãn trong khung media để sử dụng ảnh đại diện. Mặc định là 'Use as featured image'.
 * - `menu_name` - Nhãn cho tên menu. Mặc định giống `name`.
 * - `filter_items_list` - Nhãn cho tiêu đề ẩn của các chế độ xem bảng. Mặc định là 'Filter posts list' /
 *                       'Filter pages list'.
 * - `filter_by_date` - Nhãn cho bộ lọc ngày trong bảng danh sách. Mặc định là 'Filter by date'.
 * - `items_list_navigation` - Nhãn cho tiêu đề ẩn phân trang bảng. Mặc định là 'Posts list navigation' /
 *                           'Pages list navigation'.
 * - `items_list` - Nhãn cho tiêu đề ẩn của bảng. Mặc định là 'Posts list' / 'Pages list'.
 * - `item_published` - Nhãn dùng khi mục được xuất bản. Mặc định là 'Post published.' / 'Page published.'
 * - `item_published_privately` - Nhãn dùng khi mục được xuất bản ở chế độ riêng tư.
 *                              Mặc định là 'Post published privately.' / 'Page published privately.'
 * - `item_reverted_to_draft` - Nhãn dùng khi mục được chuyển về bản nháp.
 *                            Mặc định là 'Post reverted to draft.' / 'Page reverted to draft.'
 * - `item_trashed` - Nhãn dùng khi mục được chuyển vào Thùng rác. Mặc định là 'Post trashed.' / 'Page trashed.'
 * - `item_scheduled` - Nhãn dùng khi mục được lên lịch xuất bản. Mặc định là 'Post scheduled.' /
 *                    'Page scheduled.'
 * - `item_updated` - Nhãn dùng khi mục được cập nhật. Mặc định là 'Post updated.' / 'Page updated.'
 * - `item_link` - Tiêu đề cho biến thể block liên kết điều hướng. Mặc định là 'Post Link' / 'Page Link'.
 * - `item_link_description` - Mô tả cho biến thể block liên kết điều hướng. Mặc định là 'A link to a post.' /
 *                             'A link to a page.'
 *
 * Ở trên, giá trị mặc định đầu tiên dành cho post type không phân cấp (như bài viết)
 * và giá trị thứ hai dành cho post type phân cấp (như trang).
 *
 * Lưu ý: Để đặt nhãn dùng trong thông báo quản trị post type, xem filter {@see 'post_updated_messages'}.
 *
 * @since 3.0.0
 * @since 4.3.0 Thêm nhãn `featured_image`, `set_featured_image`, `remove_featured_image`,
 *              và `use_featured_image`.
 * @since 4.4.0 Thêm nhãn `archives`, `insert_into_item`, `uploaded_to_this_item`, `filter_items_list`,
 *              `items_list_navigation`, và `items_list`.
 * @since 4.6.0 Chuyển đổi tham số `$post_type` để chấp nhận đối tượng `WP_Post_Type`.
 * @since 4.7.0 Thêm nhãn `view_items` và `attributes`.
 * @since 5.0.0 Thêm nhãn `item_published`, `item_published_privately`, `item_reverted_to_draft`,
 *              `item_scheduled`, và `item_updated`.
 * @since 5.7.0 Thêm nhãn `filter_by_date`.
 * @since 5.8.0 Thêm nhãn `item_link` và `item_link_description`.
 * @since 6.3.0 Thêm nhãn `item_trashed`.
 * @since 6.4.0 Thay đổi giá trị mặc định cho nhãn `add_new` để bao gồm loại nội dung.
 *              Điều này khớp với `add_new_item` và cung cấp thêm ngữ cảnh cho khả năng truy cập tốt hơn.
 * @since 6.6.0 Thêm nhãn `template_name`.
 * @since 6.7.0 Khôi phục giá trị mặc định trước 6.4.0 cho nhãn `add_new` và cập nhật tài liệu.
 *              Cập nhật cách sử dụng trong lõi để tham chiếu `add_new_item`.
 *
 * @access private
 *
 * @param object|WP_Post_Type $post_type_object Đối tượng post type.
 * @return object Đối tượng chứa tất cả nhãn dưới dạng biến thành viên.
 */
function get_post_type_labels( $post_type_object ) {
	$nohier_vs_hier_defaults = WP_Post_Type::get_default_labels();

	$nohier_vs_hier_defaults['menu_name'] = $nohier_vs_hier_defaults['name'];

	$labels = _get_custom_object_labels( $post_type_object, $nohier_vs_hier_defaults );

	if ( ! isset( $post_type_object->labels->template_name ) && isset( $post_type_object->labels->singular_name ) ) {
			/* translators: %s: Post type name. */
			$labels->template_name = sprintf( __( 'Single item: %s' ), $post_type_object->labels->singular_name );
	}

	$post_type = $post_type_object->name;

	$default_labels = clone $labels;

	/**
	 * Lọc các nhãn của một post type cụ thể.
	 *
	 * Phần động của tên hook, `$post_type`, tham chiếu đến
	 * slug của post type.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `post_type_labels_post`
	 *  - `post_type_labels_page`
	 *  - `post_type_labels_attachment`
	 *
	 * @since 3.5.0
	 *
	 * @see get_post_type_labels() để biết danh sách đầy đủ các nhãn.
	 *
	 * @param object $labels Đối tượng chứa các nhãn cho post type dưới dạng biến thành viên.
	 */
	$labels = apply_filters( "post_type_labels_{$post_type}", $labels );

	// Đảm bảo rằng các nhãn đã lọc chứa tất cả giá trị mặc định cần thiết.
	$labels = (object) array_merge( (array) $default_labels, (array) $labels );

	return $labels;
}

/**
 * Xây dựng đối tượng chứa các nhãn của đối tượng tùy chỉnh (post type, taxonomy)
 * từ một đối tượng tùy chỉnh.
 *
 * @since 3.0.0
 * @access private
 *
 * @param object $data_object             Đối tượng tùy chỉnh.
 * @param array  $nohier_vs_hier_defaults Nhãn mặc định phân cấp so với không phân cấp.
 * @return object Đối tượng chứa nhãn cho đối tượng tùy chỉnh đã cho.
 */
function _get_custom_object_labels( $data_object, $nohier_vs_hier_defaults ) {
	$data_object->labels = (array) $data_object->labels;

	if ( isset( $data_object->label ) && empty( $data_object->labels['name'] ) ) {
		$data_object->labels['name'] = $data_object->label;
	}

	if ( ! isset( $data_object->labels['singular_name'] ) && isset( $data_object->labels['name'] ) ) {
		$data_object->labels['singular_name'] = $data_object->labels['name'];
	}

	if ( ! isset( $data_object->labels['name_admin_bar'] ) ) {
		$data_object->labels['name_admin_bar'] =
			isset( $data_object->labels['singular_name'] )
			? $data_object->labels['singular_name']
			: $data_object->name;
	}

	if ( ! isset( $data_object->labels['menu_name'] ) && isset( $data_object->labels['name'] ) ) {
		$data_object->labels['menu_name'] = $data_object->labels['name'];
	}

	if ( ! isset( $data_object->labels['all_items'] ) && isset( $data_object->labels['menu_name'] ) ) {
		$data_object->labels['all_items'] = $data_object->labels['menu_name'];
	}

	if ( ! isset( $data_object->labels['archives'] ) && isset( $data_object->labels['all_items'] ) ) {
		$data_object->labels['archives'] = $data_object->labels['all_items'];
	}

	$defaults = array();
	foreach ( $nohier_vs_hier_defaults as $key => $value ) {
		$defaults[ $key ] = $data_object->hierarchical ? $value[1] : $value[0];
	}

	$labels              = array_merge( $defaults, $data_object->labels );
	$data_object->labels = (object) $data_object->labels;

	return (object) $labels;
}

/**
 * Thêm menu con cho các post type.
 *
 * @access private
 * @since 3.1.0
 */
function _add_post_type_submenus() {
	foreach ( get_post_types( array( 'show_ui' => true ) ) as $ptype ) {
		$ptype_obj = get_post_type_object( $ptype );
		// Chỉ menu con.
		if ( ! $ptype_obj->show_in_menu || true === $ptype_obj->show_in_menu ) {
			continue;
		}
		add_submenu_page( $ptype_obj->show_in_menu, $ptype_obj->labels->name, $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts, "edit.php?post_type=$ptype" );
	}
}

/**
 * Đăng ký hỗ trợ các tính năng nhất định cho một post type.
 *
 * Tất cả tính năng lõi đều liên kết trực tiếp với một khu vực chức năng của màn hình
 * chỉnh sửa, như trình soạn thảo hoặc meta box. Các tính năng bao gồm: 'title', 'editor',
 * 'comments', 'revisions', 'trackbacks', 'author', 'excerpt', 'page-attributes',
 * 'thumbnail', 'custom-fields', và 'post-formats'.
 *
 * Ngoài ra, tính năng 'revisions' quyết định post type có lưu bản sửa đổi không,
 * tính năng 'autosave' quyết định post type có được tự động lưu không,
 * và tính năng 'comments' quyết định số đếm bình luận có hiển thị
 * trên màn hình chỉnh sửa không.
 *
 * Tham số thứ ba, tùy chọn, cũng có thể được truyền cùng với tính năng để cung cấp
 * thông tin bổ sung về việc hỗ trợ tính năng đó.
 *
 * Ví dụ sử dụng:
 *
 *     add_post_type_support( 'my_post_type', 'comments' );
 *     add_post_type_support( 'my_post_type', array(
 *         'author', 'excerpt',
 *     ) );
 *     add_post_type_support( 'my_post_type', 'my_feature', array(
 *         'field' => 'value',
 *     ) );
 *
 * @since 3.0.0
 * @since 5.3.0 Chính thức hóa tham số `...$args` đã có và đã được ghi nhận
 *              bằng cách thêm vào chữ ký hàm.
 *
 * @global array $_wp_post_type_features
 *
 * @param string       $post_type Post type cần thêm tính năng.
 * @param string|array $feature   Tính năng được thêm, chấp nhận mảng các chuỗi
 *                                tính năng hoặc một chuỗi đơn.
 * @param mixed        ...$args   Các đối số bổ sung tùy chọn để truyền cùng với một số tính năng.
 */
function add_post_type_support( $post_type, $feature, ...$args ) {
	global $_wp_post_type_features;

	$features = (array) $feature;
	foreach ( $features as $feature ) {
		if ( $args ) {
			$_wp_post_type_features[ $post_type ][ $feature ] = $args;
		} else {
			$_wp_post_type_features[ $post_type ][ $feature ] = true;
		}
	}
}

/**
 * Gỡ bỏ hỗ trợ một tính năng từ post type.
 *
 * @since 3.0.0
 *
 * @global array $_wp_post_type_features
 *
 * @param string $post_type Post type cần gỡ bỏ tính năng.
 * @param string $feature   Tính năng được gỡ bỏ.
 */
function remove_post_type_support( $post_type, $feature ) {
	global $_wp_post_type_features;

	unset( $_wp_post_type_features[ $post_type ][ $feature ] );
}

/**
 * Lấy tất cả tính năng của post type.
 *
 * @since 3.4.0
 *
 * @global array $_wp_post_type_features
 *
 * @param string $post_type Post type.
 * @return array Danh sách các tính năng mà post type hỗ trợ.
 */
function get_all_post_type_supports( $post_type ) {
	global $_wp_post_type_features;

	if ( isset( $_wp_post_type_features[ $post_type ] ) ) {
		return $_wp_post_type_features[ $post_type ];
	}

	return array();
}

/**
 * Kiểm tra xem post type có hỗ trợ một tính năng đã cho hay không.
 *
 * @since 3.0.0
 *
 * @global array $_wp_post_type_features
 *
 * @param string $post_type Post type đang được kiểm tra.
 * @param string $feature   Tính năng đang được kiểm tra.
 * @return bool Post type có hỗ trợ tính năng đã cho hay không.
 */
function post_type_supports( $post_type, $feature ) {
	global $_wp_post_type_features;

	return ( isset( $_wp_post_type_features[ $post_type ][ $feature ] ) );
}

/**
 * Lấy danh sách tên các post type hỗ trợ một tính năng cụ thể.
 *
 * @since 4.5.0
 *
 * @global array $_wp_post_type_features Tính năng của post type.
 *
 * @param array|string $feature  Tính năng đơn hoặc mảng các tính năng mà post type cần hỗ trợ.
 * @param string       $operator Tùy chọn. Phép toán logic cần thực hiện. 'or' nghĩa là
 *                               chỉ cần một phần tử từ mảng khớp; 'and'
 *                               nghĩa là tất cả phần tử phải khớp; 'not' nghĩa là không phần tử nào
 *                               được khớp. Mặc định 'and'.
 * @return string[] Danh sách tên các post type.
 */
function get_post_types_by_support( $feature, $operator = 'and' ) {
	global $_wp_post_type_features;

	$features = array_fill_keys( (array) $feature, true );

	return array_keys( wp_filter_object_list( $_wp_post_type_features, $features, $operator ) );
}

/**
 * Cập nhật post type cho ID bài viết.
 *
 * Bộ nhớ đệm trang hoặc bài viết sẽ được xóa cho ID bài viết.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int    $post_id   Tùy chọn. ID bài viết cần thay đổi post type. Mặc định 0.
 * @param string $post_type Tùy chọn. Post type. Chấp nhận 'post' hoặc 'page'
 *                          cùng nhiều loại khác. Mặc định 'post'.
 * @return int|false Số dòng đã thay đổi. Nên là 1 nếu thành công và 0 nếu thất bại.
 */
function set_post_type( $post_id = 0, $post_type = 'post' ) {
	global $wpdb;

	$post_type = sanitize_post_field( 'post_type', $post_type, $post_id, 'db' );
	$return    = $wpdb->update( $wpdb->posts, array( 'post_type' => $post_type ), array( 'ID' => $post_id ) );

	clean_post_cache( $post_id );

	return $return;
}

/**
 * Xác định xem một post type có được coi là "có thể xem" hay không.
 *
 * Đối với các post type tích hợp sẵn như bài viết và trang, giá trị 'public' sẽ được đánh giá.
 * Đối với tất cả các loại khác, giá trị 'publicly_queryable' sẽ được sử dụng.
 *
 * @since 4.4.0
 * @since 4.5.0 Thêm khả năng truyền tên post type ngoài đối tượng.
 * @since 4.6.0 Chuyển đổi tham số `$post_type` để chấp nhận đối tượng `WP_Post_Type`.
 * @since 5.9.0 Thêm hook `is_post_type_viewable` để lọc kết quả.
 *
 * @param string|WP_Post_Type $post_type Tên hoặc đối tượng post type.
 * @return bool Post type có nên được coi là có thể xem hay không.
 */
function is_post_type_viewable( $post_type ) {
	if ( is_scalar( $post_type ) ) {
		$post_type = get_post_type_object( $post_type );

		if ( ! $post_type ) {
			return false;
		}
	}

	if ( ! is_object( $post_type ) ) {
		return false;
	}

	$is_viewable = $post_type->publicly_queryable || ( $post_type->_builtin && $post_type->public );

	/**
	 * Lọc xem post type có được coi là "có thể xem" hay không.
	 *
	 * Giá trị đã lọc trả về phải là kiểu boolean để đảm bảo
	 * `is_post_type_viewable()` chỉ trả về boolean. Sự nghiêm ngặt này
	 * được thiết kế để duy trì tương thích ngược và bảo vệ chống lại
	 * lỗi kiểu tiềm ẩn trong PHP 8.1+. Các giá trị không phải boolean (kể cả falsey
	 * và truthy) sẽ khiến hàm trả về false.
	 *
	 * @since 5.9.0
	 *
	 * @param bool         $is_viewable Post type có "có thể xem" hay không (kiểu nghiêm ngặt).
	 * @param WP_Post_Type $post_type   Đối tượng post type.
	 */
	return true === apply_filters( 'is_post_type_viewable', $is_viewable, $post_type );
}

/**
 * Xác định xem trạng thái bài viết có được coi là "có thể xem" hay không.
 *
 * Đối với trạng thái bài viết tích hợp sẵn như publish và private, giá trị 'public' sẽ được đánh giá.
 * Đối với tất cả các trạng thái khác, giá trị 'publicly_queryable' sẽ được sử dụng.
 *
 * @since 5.7.0
 * @since 5.9.0 Thêm hook `is_post_status_viewable` để lọc kết quả.
 *
 * @param string|stdClass $post_status Tên hoặc đối tượng trạng thái bài viết.
 * @return bool Trạng thái bài viết có nên được coi là có thể xem hay không.
 */
function is_post_status_viewable( $post_status ) {
	if ( is_scalar( $post_status ) ) {
		$post_status = get_post_status_object( $post_status );

		if ( ! $post_status ) {
			return false;
		}
	}

	if (
		! is_object( $post_status ) ||
		$post_status->internal ||
		$post_status->protected
	) {
		return false;
	}

	$is_viewable = $post_status->publicly_queryable || ( $post_status->_builtin && $post_status->public );

	/**
	 * Lọc xem trạng thái bài viết có được coi là "có thể xem" hay không.
	 *
	 * Giá trị đã lọc trả về phải là kiểu boolean để đảm bảo
	 * `is_post_status_viewable()` chỉ trả về boolean. Sự nghiêm ngặt này
	 * được thiết kế để duy trì tương thích ngược và bảo vệ chống lại
	 * lỗi kiểu tiềm ẩn trong PHP 8.1+. Các giá trị không phải boolean (kể cả falsey
	 * và truthy) sẽ khiến hàm trả về false.
	 *
	 * @since 5.9.0
	 *
	 * @param bool     $is_viewable Trạng thái bài viết có "có thể xem" hay không (kiểu nghiêm ngặt).
	 * @param stdClass $post_status Đối tượng trạng thái bài viết.
	 */
	return true === apply_filters( 'is_post_status_viewable', $is_viewable, $post_status );
}

/**
 * Xác định xem bài viết có thể xem công khai hay không.
 *
 * Bài viết được coi là có thể xem công khai nếu cả trạng thái bài viết và post type
 * đều có thể xem.
 *
 * @since 5.7.0
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @return bool Bài viết có thể xem công khai hay không.
 */
function is_post_publicly_viewable( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$post_type   = get_post_type( $post );
	$post_status = get_post_status( $post );

	return is_post_type_viewable( $post_type ) && is_post_status_viewable( $post_status );
}

/**
 * Xác định xem bài viết có thể nhúng (embed) hay không.
 *
 * @since 6.8.0
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng `WP_Post`. Mặc định global $post.
 * @return bool Bài viết có nên được coi là có thể nhúng hay không.
 */
function is_post_embeddable( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$post_type = get_post_type_object( $post->post_type );

	if ( ! $post_type ) {
		return false;
	}

	$is_embeddable = $post_type->embeddable;

	/**
	 * Lọc xem bài viết có thể nhúng hay không.
	 *
	 * @since 6.8.0
	 *
	 * @param bool    $is_embeddable Bài viết có thể nhúng hay không.
	 * @param WP_Post $post          Đối tượng bài viết.
	 */
	return apply_filters( 'is_post_embeddable', $is_embeddable, $post );
}

/**
 * Lấy mảng các bài viết mới nhất, hoặc bài viết khớp với tiêu chí đã cho.
 *
 * Để biết thêm thông tin về các đối số được chấp nhận, xem tài liệu
 * {@link https://developer.wordpress.org/reference/classes/wp_query/
 * WP_Query} trong Sổ tay Nhà phát triển.
 *
 * Các đối số `$ignore_sticky_posts` và `$no_found_rows` bị bỏ qua bởi
 * hàm này và cả hai đều được đặt thành `true`.
 *
 * Các giá trị mặc định như sau:
 *
 * @since 1.2.0
 *
 * @see WP_Query
 * @see WP_Query::parse_query()
 *
 * @param array $args {
 *     Tùy chọn. Các đối số để lấy bài viết. Xem WP_Query::parse_query() để biết tất cả đối số có sẵn.
 *
 *     @type int        $numberposts      Tổng số bài viết cần lấy. Là bí danh của `$posts_per_page`
 *                                        trong WP_Query. Chấp nhận -1 để lấy tất cả. Mặc định 5.
 *     @type int|string $category         ID chuyên mục hoặc danh sách ID phân cách bằng dấu phẩy (chuyên mục này hoặc con của nó).
 *                                        Là bí danh của `$cat` trong WP_Query. Mặc định 0.
 *     @type int[]      $include          Mảng ID bài viết cần lấy, bài viết ghim sẽ được bao gồm.
 *                                        Là bí danh của `$post__in` trong WP_Query. Mặc định mảng rỗng.
 *     @type int[]      $exclude          Mảng ID bài viết không lấy. Mặc định mảng rỗng.
 *     @type bool       $suppress_filters Có bỏ qua các filter hay không. Mặc định true.
 * }
 * @return WP_Post[]|int[] Mảng các đối tượng bài viết hoặc ID bài viết.
 */
function get_posts( $args = null ) {
	$defaults = array(
		'numberposts'      => 5,
		'category'         => 0,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'include'          => array(),
		'exclude'          => array(),
		'meta_key'         => '',
		'meta_value'       => '',
		'post_type'        => 'post',
		'suppress_filters' => true,
	);

	$parsed_args = wp_parse_args( $args, $defaults );
	if ( empty( $parsed_args['post_status'] ) ) {
		$parsed_args['post_status'] = ( 'attachment' === $parsed_args['post_type'] ) ? 'inherit' : 'publish';
	}
	if ( ! empty( $parsed_args['numberposts'] ) && empty( $parsed_args['posts_per_page'] ) ) {
		$parsed_args['posts_per_page'] = $parsed_args['numberposts'];
	}
	if ( ! empty( $parsed_args['category'] ) ) {
		$parsed_args['cat'] = $parsed_args['category'];
	}
	if ( ! empty( $parsed_args['include'] ) ) {
		$incposts                      = wp_parse_id_list( $parsed_args['include'] );
		$parsed_args['posts_per_page'] = count( $incposts );  // Chỉ số lượng bài viết được bao gồm.
		$parsed_args['post__in']       = $incposts;
	} elseif ( ! empty( $parsed_args['exclude'] ) ) {
		$parsed_args['post__not_in'] = wp_parse_id_list( $parsed_args['exclude'] );
	}

	$parsed_args['ignore_sticky_posts'] = true;
	$parsed_args['no_found_rows']       = true;

	$get_posts = new WP_Query();
	return $get_posts->query( $parsed_args );
}

//
// Các hàm post meta.
//

/**
 * Thêm trường meta cho bài viết đã cho.
 *
 * Dữ liệu meta bài viết được gọi là "Trường Tùy chỉnh" trên Màn hình Quản trị.
 *
 * @since 1.5.0
 *
 * @param int    $post_id    ID bài viết.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Giá trị metadata. Mảng và đối tượng được lưu dạng serialize và
 *                           sẽ được trả về cùng kiểu khi lấy ra. Các kiểu dữ liệu khác sẽ
 *                           được lưu dạng chuỗi trong cơ sở dữ liệu:
 *                           - false được lưu và trả về dạng chuỗi rỗng ('')
 *                           - true được lưu và trả về dạng '1'
 *                           - số (cả integer và float) được lưu và trả về dạng chuỗi
 *                           Phải có thể serialize nếu không phải kiểu vô hướng.
 * @param bool   $unique     Tùy chọn. Có không thêm cùng khóa hay không.
 *                           Mặc định false.
 * @return int|false ID Meta nếu thành công, false nếu thất bại.
 */
function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
	// Đảm bảo meta được thêm vào bài viết, không phải bản sửa đổi.
	$the_post = wp_is_post_revision( $post_id );
	if ( $the_post ) {
		$post_id = $the_post;
	}

	return add_metadata( 'post', $post_id, $meta_key, $meta_value, $unique );
}

/**
 * Xóa trường meta bài viết cho ID bài viết đã cho.
 *
 * Bạn có thể so khớp dựa trên khóa, hoặc khóa và giá trị. Xóa dựa trên khóa và
 * giá trị sẽ tránh việc xóa metadata trùng lặp có cùng khóa. Nó cũng
 * cho phép xóa tất cả metadata khớp với khóa, nếu cần.
 *
 * @since 1.5.0
 *
 * @param int    $post_id    ID bài viết.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Tùy chọn. Giá trị metadata. Nếu được cung cấp,
 *                           chỉ các dòng khớp với giá trị mới bị xóa.
 *                           Phải có thể serialize nếu không phải kiểu vô hướng. Mặc định rỗng.
 * @return bool True nếu thành công, false nếu thất bại.
 */
function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {
	// Đảm bảo meta được xóa từ bài viết, không phải từ bản sửa đổi.
	$the_post = wp_is_post_revision( $post_id );
	if ( $the_post ) {
		$post_id = $the_post;
	}

	return delete_metadata( 'post', $post_id, $meta_key, $meta_value );
}

/**
 * Lấy trường meta bài viết cho ID bài viết đã cho.
 *
 * @since 1.5.0
 *
 * @param int    $post_id ID bài viết.
 * @param string $key     Tùy chọn. Khóa meta cần lấy. Mặc định,
 *                        trả về dữ liệu cho tất cả các khóa. Mặc định rỗng.
 * @param bool   $single  Tùy chọn. Có trả về giá trị đơn hay không.
 *                        Tham số này không có tác dụng nếu `$key` không được chỉ định.
 *                        Mặc định false.
 * @return mixed Mảng các giá trị nếu `$single` là false.
 *               Giá trị của trường meta nếu `$single` là true.
 *               False cho `$post_id` không hợp lệ (không phải số, bằng không, hoặc giá trị âm).
 *               Mảng rỗng nếu ID bài viết hợp lệ nhưng không tồn tại được truyền và `$single` là false.
 *               Chuỗi rỗng nếu ID bài viết hợp lệ nhưng không tồn tại được truyền và `$single` là true.
 *               Lưu ý: Các giá trị không serialize được trả về dạng chuỗi:
 *               - giá trị false được trả về dạng chuỗi rỗng ('')
 *               - giá trị true được trả về dạng '1'
 *               - số (cả integer và float) được trả về dạng chuỗi
 *               Mảng và đối tượng giữ nguyên kiểu gốc.
 */
function get_post_meta( $post_id, $key = '', $single = false ) {
	return get_metadata( 'post', $post_id, $key, $single );
}

/**
 * Cập nhật trường meta bài viết dựa trên ID bài viết đã cho.
 *
 * Sử dụng tham số `$prev_value` để phân biệt giữa các trường meta có cùng
 * khóa và ID bài viết.
 *
 * Nếu trường meta cho bài viết không tồn tại, nó sẽ được thêm mới và ID được trả về.
 *
 * Có thể dùng thay thế cho add_post_meta().
 *
 * @since 1.5.0
 *
 * @param int    $post_id    ID bài viết.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Giá trị metadata. Phải có thể serialize nếu không phải kiểu vô hướng.
 * @param mixed  $prev_value Tùy chọn. Giá trị trước đó cần kiểm tra trước khi cập nhật.
 *                           Nếu chỉ định, chỉ cập nhật các mục metadata hiện có với
 *                           giá trị này. Ngược lại, cập nhật tất cả các mục. Mặc định rỗng.
 * @return int|bool ID Meta nếu khóa chưa tồn tại, true nếu cập nhật thành công,
 *                  false nếu thất bại hoặc nếu giá trị truyền vào hàm
 *                  giống với giá trị đã có trong cơ sở dữ liệu.
 */
function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
	// Đảm bảo meta được cập nhật cho bài viết, không phải cho bản sửa đổi.
	$the_post = wp_is_post_revision( $post_id );
	if ( $the_post ) {
		$post_id = $the_post;
	}

	return update_metadata( 'post', $post_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Xóa mọi thứ từ post meta khớp với khóa meta đã cho.
 *
 * @since 2.3.0
 *
 * @param string $post_meta_key Khóa cần tìm khi xóa.
 * @return bool Khóa meta bài viết có được xóa khỏi cơ sở dữ liệu hay không.
 */
function delete_post_meta_by_key( $post_meta_key ) {
	return delete_metadata( 'post', null, $post_meta_key, '', true );
}

/**
 * Đăng ký khóa meta cho bài viết.
 *
 * @since 4.9.8
 *
 * @param string $post_type Post type cần đăng ký khóa meta. Truyền chuỗi rỗng
 *                          để đăng ký khóa meta cho tất cả post type hiện có.
 * @param string $meta_key  Khóa meta cần đăng ký.
 * @param array  $args      Dữ liệu dùng để mô tả khóa meta khi đăng ký. Xem
 *                          {@see register_meta()} để biết danh sách đối số được hỗ trợ.
 * @return bool True nếu khóa meta được đăng ký thành công, false nếu không.
 */
function register_post_meta( $post_type, $meta_key, array $args ) {
	$args['object_subtype'] = $post_type;

	return register_meta( 'post', $meta_key, $args );
}

/**
 * Hủy đăng ký khóa meta cho bài viết.
 *
 * @since 4.9.8
 *
 * @param string $post_type Post type mà khóa meta hiện đang được đăng ký. Truyền
 *                          chuỗi rỗng nếu khóa meta được đăng ký cho tất cả
 *                          post type hiện có.
 * @param string $meta_key  Khóa meta cần hủy đăng ký.
 * @return bool True nếu thành công, false nếu khóa meta chưa được đăng ký trước đó.
 */
function unregister_post_meta( $post_type, $meta_key ) {
	return unregister_meta_key( 'post', $meta_key, $post_type );
}

/**
 * Lấy các trường meta bài viết, dựa trên ID bài viết.
 *
 * Các trường meta bài viết được lấy từ bộ nhớ đệm khi có thể,
 * nên hàm được tối ưu để gọi nhiều lần.
 *
 * @since 1.2.0
 *
 * @param int $post_id Tùy chọn. ID bài viết. Mặc định là ID của global `$post`.
 * @return mixed Mảng các giá trị.
 *               False cho `$post_id` không hợp lệ (không phải số, bằng không, hoặc giá trị âm).
 *               Chuỗi rỗng nếu ID bài viết hợp lệ nhưng không tồn tại được truyền vào.
 */
function get_post_custom( $post_id = 0 ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	return get_post_meta( $post_id );
}

/**
 * Lấy tên các trường meta cho bài viết.
 *
 * Nếu không có trường meta, thì không có gì (null) sẽ được trả về.
 *
 * @since 1.2.0
 *
 * @param int $post_id Tùy chọn. ID bài viết. Mặc định là ID của global `$post`.
 * @return array|void Mảng các khóa, nếu lấy được.
 */
function get_post_custom_keys( $post_id = 0 ) {
	$custom = get_post_custom( $post_id );

	if ( ! is_array( $custom ) ) {
		return;
	}

	$keys = array_keys( $custom );
	if ( $keys ) {
		return $keys;
	}
}

/**
 * Lấy giá trị cho trường tùy chỉnh bài viết.
 *
 * Các tham số không nên được coi là tùy chọn. Tất cả các trường meta bài viết
 * sẽ được lấy và chỉ các giá trị khóa trường meta được trả về.
 *
 * @since 1.2.0
 *
 * @param string $key     Tùy chọn. Khóa trường meta. Mặc định rỗng.
 * @param int    $post_id Tùy chọn. ID bài viết. Mặc định là ID của global `$post`.
 * @return array|null Giá trị trường meta.
 */
function get_post_custom_values( $key = '', $post_id = 0 ) {
	if ( ! $key ) {
		return null;
	}

	$custom = get_post_custom( $post_id );

	return isset( $custom[ $key ] ) ? $custom[ $key ] : null;
}

/**
 * Xác định xem bài viết có phải là bài ghim hay không.
 *
 * Bài viết ghim nên nằm ở đầu Vòng lặp (The Loop). Nếu ID bài viết không
 * được cung cấp, thì ID Vòng lặp cho bài viết hiện tại sẽ được dùng.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.7.0
 *
 * @param int $post_id Tùy chọn. ID bài viết. Mặc định là ID của global `$post`.
 * @return bool Bài viết có phải là bài ghim hay không.
 */
function is_sticky( $post_id = 0 ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$stickies = get_option( 'sticky_posts' );

	if ( is_array( $stickies ) ) {
		$stickies  = array_map( 'intval', $stickies );
		$is_sticky = in_array( $post_id, $stickies, true );
	} else {
		$is_sticky = false;
	}

	/**
	 * Lọc xem bài viết có phải là bài ghim hay không.
	 *
	 * @since 5.3.0
	 *
	 * @param bool $is_sticky Bài viết có phải bài ghim hay không.
	 * @param int  $post_id   ID bài viết.
	 */
	return apply_filters( 'is_sticky', $is_sticky, $post_id );
}

/**
 * Làm sạch mọi trường bài viết.
 *
 * Nếu ngữ cảnh là 'raw', thì đối tượng hoặc mảng bài viết sẽ được làm sạch
 * tối thiểu cho các trường kiểu số nguyên.
 *
 * @since 2.3.0
 *
 * @see sanitize_post_field()
 *
 * @param object|WP_Post|array $post    Đối tượng hoặc mảng bài viết.
 * @param string               $context Tùy chọn. Cách làm sạch các trường bài viết.
 *                                      Chấp nhận 'raw', 'edit', 'db', 'display',
 *                                      'attribute', hoặc 'js'. Mặc định 'display'.
 * @return object|WP_Post|array Đối tượng hoặc mảng bài viết đã được làm sạch (sẽ có
 *                              cùng kiểu với `$post`).
 */
function sanitize_post( $post, $context = 'display' ) {
	if ( is_object( $post ) ) {
		// Kiểm tra xem bài viết đã được lọc cho ngữ cảnh này chưa.
		if ( isset( $post->filter ) && $context === $post->filter ) {
			return $post;
		}
		if ( ! isset( $post->ID ) ) {
			$post->ID = 0;
		}
		foreach ( array_keys( get_object_vars( $post ) ) as $field ) {
			$post->$field = sanitize_post_field( $field, $post->$field, $post->ID, $context );
		}
		$post->filter = $context;
	} elseif ( is_array( $post ) ) {
		// Kiểm tra xem bài viết đã được lọc cho ngữ cảnh này chưa.
		if ( isset( $post['filter'] ) && $context === $post['filter'] ) {
			return $post;
		}
		if ( ! isset( $post['ID'] ) ) {
			$post['ID'] = 0;
		}
		foreach ( array_keys( $post ) as $field ) {
			$post[ $field ] = sanitize_post_field( $field, $post[ $field ], $post['ID'], $context );
		}
		$post['filter'] = $context;
	}
	return $post;
}

/**
 * Làm sạch trường bài viết dựa trên ngữ cảnh.
 *
 * Các giá trị ngữ cảnh có thể là: 'raw', 'edit', 'db', 'display', 'attribute' và
 * 'js'. Ngữ cảnh 'display' được sử dụng mặc định. Ngữ cảnh 'attribute' và 'js'
 * được xử lý như 'display' khi gọi filter.
 *
 * @since 2.3.0
 * @since 4.4.0 Giống `sanitize_post()`, `$context` mặc định là 'display'.
 *
 * @param string $field   Tên trường đối tượng bài viết.
 * @param mixed  $value   Giá trị đối tượng bài viết.
 * @param int    $post_id ID bài viết.
 * @param string $context Tùy chọn. Cách làm sạch trường. Các giá trị có thể là 'raw', 'edit',
 *                        'db', 'display', 'attribute' và 'js'. Mặc định 'display'.
 * @return mixed Giá trị đã làm sạch.
 */
function sanitize_post_field( $field, $value, $post_id, $context = 'display' ) {
	$int_fields = array( 'ID', 'post_parent', 'menu_order' );
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}

	// Các trường chứa mảng số nguyên.
	$array_int_fields = array( 'ancestors' );
	if ( in_array( $field, $array_int_fields, true ) ) {
		$value = array_map( 'absint', $value );
		return $value;
	}

	if ( 'raw' === $context ) {
		return $value;
	}

	$prefixed = false;
	if ( str_contains( $field, 'post_' ) ) {
		$prefixed        = true;
		$field_no_prefix = str_replace( 'post_', '', $field );
	}

	if ( 'edit' === $context ) {
		$format_to_edit = array( 'post_content', 'post_excerpt', 'post_title', 'post_password' );

		if ( $prefixed ) {

			/**
			 * Lọc giá trị của một trường bài viết cụ thể để chỉnh sửa.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến tên
			 * trường bài viết. Các tên filter có thể bao gồm:
			 *
			 *  - `edit_post_author`
			 *  - `edit_post_date`
			 *  - `edit_post_date_gmt`
			 *  - `edit_post_content`
			 *  - `edit_post_title`
			 *  - `edit_post_excerpt`
			 *  - `edit_post_status`
			 *  - `edit_post_password`
			 *  - `edit_post_name`
			 *  - `edit_post_modified`
			 *  - `edit_post_modified_gmt`
			 *  - `edit_post_content_filtered`
			 *  - `edit_post_parent`
			 *  - `edit_post_type`
			 *  - `edit_post_mime_type`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Giá trị của trường bài viết.
			 * @param int   $post_id ID bài viết.
			 */
			$value = apply_filters( "edit_{$field}", $value, $post_id );

			/**
			 * Lọc giá trị của một trường bài viết cụ thể để chỉnh sửa.
			 *
			 * Chỉ áp dụng cho các trường bài viết có tên bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field_no_prefix`, tham chiếu đến
			 * tên trường bài viết không có tiền tố `post_`. Các tên filter có thể bao gồm:
			 *
			 *  - `author_edit_pre`
			 *  - `date_edit_pre`
			 *  - `date_gmt_edit_pre`
			 *  - `content_edit_pre`
			 *  - `title_edit_pre`
			 *  - `excerpt_edit_pre`
			 *  - `status_edit_pre`
			 *  - `password_edit_pre`
			 *  - `name_edit_pre`
			 *  - `modified_edit_pre`
			 *  - `modified_gmt_edit_pre`
			 *  - `content_filtered_edit_pre`
			 *  - `parent_edit_pre`
			 *  - `type_edit_pre`
			 *  - `mime_type_edit_pre`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Giá trị của trường bài viết.
			 * @param int   $post_id ID bài viết.
			 */
			$value = apply_filters( "{$field_no_prefix}_edit_pre", $value, $post_id );
		} else {
			/**
			 * Lọc giá trị của một trường bài viết cụ thể để chỉnh sửa.
			 *
			 * Chỉ áp dụng cho các trường bài viết không có tiền tố `post_`.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến
			 * tên trường bài viết. Các tên filter có thể bao gồm:
			 *
			 *  - `edit_post_ID`
			 *  - `edit_post_ping_status`
			 *  - `edit_post_pinged`
			 *  - `edit_post_to_ping`
			 *  - `edit_post_comment_count`
			 *  - `edit_post_comment_status`
			 *  - `edit_post_guid`
			 *  - `edit_post_menu_order`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Giá trị của trường bài viết.
			 * @param int   $post_id ID bài viết.
			 */
			$value = apply_filters( "edit_post_{$field}", $value, $post_id );
		}

		if ( in_array( $field, $format_to_edit, true ) ) {
			if ( 'post_content' === $field ) {
				$value = format_to_edit( $value, user_can_richedit() );
			} else {
				$value = format_to_edit( $value );
			}
		} else {
			$value = esc_attr( $value );
		}
	} elseif ( 'db' === $context ) {
		if ( $prefixed ) {

			/**
			 * Lọc giá trị của một trường bài viết cụ thể trước khi lưu.
			 *
			 * Chỉ áp dụng cho các trường bài viết có tên bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến tên
			 * trường bài viết. Các tên filter có thể bao gồm:
			 *
			 *  - `pre_post_author`
			 *  - `pre_post_date`
			 *  - `pre_post_date_gmt`
			 *  - `pre_post_content`
			 *  - `pre_post_title`
			 *  - `pre_post_excerpt`
			 *  - `pre_post_status`
			 *  - `pre_post_password`
			 *  - `pre_post_name`
			 *  - `pre_post_modified`
			 *  - `pre_post_modified_gmt`
			 *  - `pre_post_content_filtered`
			 *  - `pre_post_parent`
			 *  - `pre_post_type`
			 *  - `pre_post_mime_type`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Giá trị của trường bài viết.
			 */
			$value = apply_filters( "pre_{$field}", $value );

			/**
			 * Lọc giá trị của một trường cụ thể trước khi lưu.
			 *
			 * Chỉ áp dụng cho các trường bài viết có tên bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field_no_prefix`, tham chiếu đến
			 * tên trường bài viết không có tiền tố `post_`. Các tên filter có thể bao gồm:
			 *
			 *  - `author_save_pre`
			 *  - `date_save_pre`
			 *  - `date_gmt_save_pre`
			 *  - `content_save_pre`
			 *  - `title_save_pre`
			 *  - `excerpt_save_pre`
			 *  - `status_save_pre`
			 *  - `password_save_pre`
			 *  - `name_save_pre`
			 *  - `modified_save_pre`
			 *  - `modified_gmt_save_pre`
			 *  - `content_filtered_save_pre`
			 *  - `parent_save_pre`
			 *  - `type_save_pre`
			 *  - `mime_type_save_pre`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Giá trị của trường bài viết.
			 */
			$value = apply_filters( "{$field_no_prefix}_save_pre", $value );
		} else {
			/**
			 * Lọc giá trị của một trường cụ thể trước khi lưu.
			 *
			 * Chỉ áp dụng cho các trường bài viết có tên bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field_no_prefix`, tham chiếu đến
			 * tên trường bài viết không có tiền tố `post_`. Các tên filter có thể bao gồm:
			 *
			 *  - `pre_post_ID`
			 *  - `pre_post_comment_status`
			 *  - `pre_post_ping_status`
			 *  - `pre_post_to_ping`
			 *  - `pre_post_pinged`
			 *  - `pre_post_guid`
			 *  - `pre_post_menu_order`
			 *  - `pre_post_comment_count`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Giá trị của trường bài viết.
			 */
			$value = apply_filters( "pre_post_{$field}", $value );

			/**
			 * Lọc giá trị của một trường bài viết cụ thể trước khi lưu.
			 *
			 * Chỉ áp dụng cho các trường bài viết có tên *không* bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến tên
			 * trường bài viết. Các tên filter có thể bao gồm:
			 *
			 *  - `ID_pre`
			 *  - `comment_status_pre`
			 *  - `ping_status_pre`
			 *  - `to_ping_pre`
			 *  - `pinged_pre`
			 *  - `guid_pre`
			 *  - `menu_order_pre`
			 *  - `comment_count_pre`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Giá trị của trường bài viết.
			 */
			$value = apply_filters( "{$field}_pre", $value );
		}
	} else {

		// Sử dụng filter hiển thị theo mặc định.
		if ( $prefixed ) {

			/**
			 * Lọc giá trị của một trường bài viết cụ thể để hiển thị.
			 *
			 * Chỉ áp dụng cho các trường bài viết có tên bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến tên
			 * trường bài viết. Các tên filter có thể bao gồm:
			 *
			 *  - `post_author`
			 *  - `post_date`
			 *  - `post_date_gmt`
			 *  - `post_content`
			 *  - `post_title`
			 *  - `post_excerpt`
			 *  - `post_status`
			 *  - `post_password`
			 *  - `post_name`
			 *  - `post_modified`
			 *  - `post_modified_gmt`
			 *  - `post_content_filtered`
			 *  - `post_parent`
			 *  - `post_type`
			 *  - `post_mime_type`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed  $value   Giá trị của trường bài viết có tiền tố.
			 * @param int    $post_id ID bài viết.
			 * @param string $context Ngữ cảnh cách làm sạch trường.
			 *                        Chấp nhận 'raw', 'edit', 'db', 'display',
			 *                        'attribute', hoặc 'js'. Mặc định 'display'.
			 */
			$value = apply_filters( "{$field}", $value, $post_id, $context );
		} else {
			/**
			 * Lọc giá trị của một trường bài viết cụ thể để hiển thị.
			 *
			 * Chỉ áp dụng cho tên trường bài viết *không* bắt đầu bằng `post_`.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến tên
			 * trường bài viết. Các tên filter có thể bao gồm:
			 *
			 *  - `post_ID`
			 *  - `post_comment_status`
			 *  - `post_ping_status`
			 *  - `post_to_ping`
			 *  - `post_pinged`
			 *  - `post_guid`
			 *  - `post_menu_order`
			 *  - `post_comment_count`
			 *
			 * @since 2.3.0
			 *
			 * @param mixed  $value   Giá trị của trường bài viết không có tiền tố.
			 * @param int    $post_id ID bài viết.
			 * @param string $context Ngữ cảnh cách làm sạch trường.
			 *                        Chấp nhận 'raw', 'edit', 'db', 'display',
			 *                        'attribute', hoặc 'js'. Mặc định 'display'.
			 */
			$value = apply_filters( "post_{$field}", $value, $post_id, $context );
		}

		if ( 'attribute' === $context ) {
			$value = esc_attr( $value );
		} elseif ( 'js' === $context ) {
			$value = esc_js( $value );
		}
	}

	// Khôi phục kiểu cho các trường số nguyên sau esc_attr().
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}
	return $value;
}

/**
 * Ghim bài viết.
 *
 * Bài viết ghim nên được hiển thị ở đầu trang chủ.
 *
 * @since 2.7.0
 *
 * @param int $post_id ID bài viết.
 */
function stick_post( $post_id ) {
	$post_id  = (int) $post_id;
	$stickies = get_option( 'sticky_posts' );
	$updated  = false;

	if ( ! is_array( $stickies ) ) {
		$stickies = array();
	} else {
		$stickies = array_unique( array_map( 'intval', $stickies ) );
	}

	if ( ! in_array( $post_id, $stickies, true ) ) {
		$stickies[] = $post_id;
		$updated    = update_option( 'sticky_posts', array_values( $stickies ) );
	}

	if ( $updated ) {
		/**
		 * Kích hoạt khi bài viết đã được thêm vào danh sách ghim.
		 *
		 * @since 4.6.0
		 *
		 * @param int $post_id ID của bài viết đã được ghim.
		 */
		do_action( 'post_stuck', $post_id );
	}
}

/**
 * Bỏ ghim bài viết.
 *
 * Bài viết ghim nên được hiển thị ở đầu trang chủ.
 *
 * @since 2.7.0
 *
 * @param int $post_id ID bài viết.
 */
function unstick_post( $post_id ) {
	$post_id  = (int) $post_id;
	$stickies = get_option( 'sticky_posts' );

	if ( ! is_array( $stickies ) ) {
		return;
	}

	$stickies = array_values( array_unique( array_map( 'intval', $stickies ) ) );

	if ( ! in_array( $post_id, $stickies, true ) ) {
		return;
	}

	$offset = array_search( $post_id, $stickies, true );
	if ( false === $offset ) {
		return;
	}

	array_splice( $stickies, $offset, 1 );

	$updated = update_option( 'sticky_posts', $stickies );

	if ( $updated ) {
		/**
		 * Kích hoạt khi bài viết đã được gỡ khỏi danh sách ghim.
		 *
		 * @since 4.6.0
		 *
		 * @param int $post_id ID của bài viết đã được bỏ ghim.
		 */
		do_action( 'post_unstuck', $post_id );
	}
}

/**
 * Trả về khóa bộ nhớ đệm cho wp_count_posts() dựa trên các đối số được truyền.
 *
 * @since 3.9.0
 * @access private
 *
 * @param string $type Tùy chọn. Post type cần lấy số đếm. Mặc định 'post'.
 * @param string $perm Tùy chọn. 'readable' hoặc rỗng. Mặc định rỗng.
 * @return string Khóa bộ nhớ đệm.
 */
function _count_posts_cache_key( $type = 'post', $perm = '' ) {
	$cache_key = 'posts-' . $type;

	if ( 'readable' === $perm && is_user_logged_in() ) {
		$post_type_object = get_post_type_object( $type );

		if ( $post_type_object && ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
			$cache_key .= '_' . $perm . '_' . get_current_user_id();
		}
	}

	return $cache_key;
}

/**
 * Đếm số bài viết của một post type và xem người dùng có quyền xem hay không.
 *
 * Hàm này cung cấp phương pháp hiệu quả để tìm số lượng bài viết
 * theo loại mà blog có. Phương pháp khác là đếm số mục trong
 * get_posts(), nhưng phương pháp đó có nhiều chi phí xử lý. Do đó,
 * khi phát triển cho phiên bản 2.5+, hãy sử dụng hàm này thay thế.
 *
 * Tham số $perm kiểm tra giá trị 'readable' và nếu người dùng có thể đọc
 * bài viết riêng tư, nó sẽ hiển thị cho người dùng đã đăng nhập.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $type Tùy chọn. Post type cần lấy số đếm. Mặc định 'post'.
 * @param string $perm Tùy chọn. 'readable' hoặc rỗng. Mặc định rỗng.
 * @return stdClass Đối tượng chứa số lượng bài viết cho mỗi trạng thái,
 *                  hoặc đối tượng rỗng nếu post type không tồn tại.
 */
function wp_count_posts( $type = 'post', $perm = '' ) {
	global $wpdb;

	if ( ! post_type_exists( $type ) ) {
		return new stdClass();
	}

	$cache_key = _count_posts_cache_key( $type, $perm );

	$counts = wp_cache_get( $cache_key, 'counts' );
	if ( false !== $counts ) {
		// Có thể đã lưu cache trước khi mọi trạng thái được đăng ký.
		foreach ( get_post_stati() as $status ) {
			if ( ! isset( $counts->{$status} ) ) {
				$counts->{$status} = 0;
			}
		}

		/** This filter is documented in wp-includes/post.php */
		return apply_filters( 'wp_count_posts', $counts, $type, $perm );
	}

	$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";

	if ( 'readable' === $perm && is_user_logged_in() ) {
		$post_type_object = get_post_type_object( $type );
		if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
			$query .= $wpdb->prepare(
				" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
				get_current_user_id()
			);
		}
	}

	$query .= ' GROUP BY post_status';

	$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
	$counts  = array_fill_keys( get_post_stati(), 0 );

	foreach ( $results as $row ) {
		$counts[ $row['post_status'] ] = $row['num_posts'];
	}

	$counts = (object) $counts;
	wp_cache_set( $cache_key, $counts, 'counts' );

	/**
	 * Lọc số đếm bài viết theo trạng thái cho post type hiện tại.
	 *
	 * @since 3.7.0
	 *
	 * @param stdClass $counts Đối tượng chứa số đếm bài viết theo trạng thái
	 *                         cho post_type hiện tại.
	 * @param string   $type   Post type.
	 * @param string   $perm   Quyền để xác định bài viết có 'đọc được'
	 *                         bởi người dùng hiện tại hay không.
	 */
	return apply_filters( 'wp_count_posts', $counts, $type, $perm );
}

/**
 * Đếm số tệp đính kèm theo (các) kiểu mime.
 *
 * Nếu bạn đặt tham số mime_type tùy chọn, thì mảng vẫn sẽ được
 * trả về, nhưng chỉ có mục bạn đang tìm. Hàm không cho bạn
 * số lượng tệp đính kèm là con của bài viết. Bạn có thể lấy thông tin đó
 * bằng cách đếm số con mà bài viết có.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string|string[] $mime_type Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy của
 *                                   các mẫu MIME. Mặc định rỗng.
 * @return stdClass Đối tượng chứa số đếm tệp đính kèm theo kiểu mime.
 */
function wp_count_attachments( $mime_type = '' ) {
	global $wpdb;

	$cache_key = sprintf(
		'attachments%s',
		! empty( $mime_type ) ? ':' . str_replace( '/', '_', implode( '-', (array) $mime_type ) ) : ''
	);

	$counts = wp_cache_get( $cache_key, 'counts' );

	if ( false === $counts ) {
		$and   = wp_post_mime_type_where( $mime_type );
		$count = $wpdb->get_results( "SELECT post_mime_type, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' $and GROUP BY post_mime_type", ARRAY_A );

		$counts = array();
		foreach ( (array) $count as $row ) {
			$counts[ $row['post_mime_type'] ] = $row['num_posts'];
		}
		$counts['trash'] = $wpdb->get_var( "SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'trash' $and" );

		wp_cache_set( $cache_key, (object) $counts, 'counts' );
	}

	/**
	 * Lọc số đếm tệp đính kèm theo kiểu mime.
	 *
	 * @since 3.7.0
	 *
	 * @param stdClass        $counts    Đối tượng chứa số đếm tệp đính kèm theo
	 *                                   kiểu mime.
	 * @param string|string[] $mime_type Mảng hoặc danh sách phân cách bằng dấu phẩy của các mẫu MIME.
	 */
	return apply_filters( 'wp_count_attachments', (object) $counts, $mime_type );
}

/**
 * Lấy các kiểu mime bài viết mặc định.
 *
 * @since 2.9.0
 * @since 5.3.0 Thêm các nhóm kiểu mime 'Documents', 'Spreadsheets', và 'Archives'.
 *
 * @return array Danh sách các kiểu mime bài viết.
 */
function get_post_mime_types() {
	$post_mime_types = array(   // mảng( tính từ, danh từ )
		'image'       => array(
			__( 'Images' ),
			__( 'Manage Images' ),
			/* translators: %s: Number of images. */
			_n_noop(
				'Image <span class="count">(%s)</span>',
				'Images <span class="count">(%s)</span>'
			),
		),
		'audio'       => array(
			_x( 'Audio', 'file type group' ),
			__( 'Manage Audio' ),
			/* translators: %s: Number of audio files. */
			_n_noop(
				'Audio <span class="count">(%s)</span>',
				'Audio <span class="count">(%s)</span>'
			),
		),
		'video'       => array(
			_x( 'Video', 'file type group' ),
			__( 'Manage Video' ),
			/* translators: %s: Number of video files. */
			_n_noop(
				'Video <span class="count">(%s)</span>',
				'Video <span class="count">(%s)</span>'
			),
		),
		'document'    => array(
			__( 'Documents' ),
			__( 'Manage Documents' ),
			/* translators: %s: Number of documents. */
			_n_noop(
				'Document <span class="count">(%s)</span>',
				'Documents <span class="count">(%s)</span>'
			),
		),
		'spreadsheet' => array(
			__( 'Spreadsheets' ),
			__( 'Manage Spreadsheets' ),
			/* translators: %s: Number of spreadsheets. */
			_n_noop(
				'Spreadsheet <span class="count">(%s)</span>',
				'Spreadsheets <span class="count">(%s)</span>'
			),
		),
		'archive'     => array(
			_x( 'Archives', 'file type group' ),
			__( 'Manage Archives' ),
			/* translators: %s: Number of archives. */
			_n_noop(
				'Archive <span class="count">(%s)</span>',
				'Archives <span class="count">(%s)</span>'
			),
		),
	);

	$ext_types  = wp_get_ext_types();
	$mime_types = wp_get_mime_types();

	foreach ( $post_mime_types as $group => $labels ) {
		if ( in_array( $group, array( 'image', 'audio', 'video' ), true ) ) {
			continue;
		}

		if ( ! isset( $ext_types[ $group ] ) ) {
			unset( $post_mime_types[ $group ] );
			continue;
		}

		$group_mime_types = array();
		foreach ( $ext_types[ $group ] as $extension ) {
			foreach ( $mime_types as $exts => $mime ) {
				if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
					$group_mime_types[] = $mime;
					break;
				}
			}
		}
		$group_mime_types = implode( ',', array_unique( $group_mime_types ) );

		$post_mime_types[ $group_mime_types ] = $labels;
		unset( $post_mime_types[ $group ] );
	}

	/**
	 * Lọc danh sách mặc định các kiểu mime bài viết.
	 *
	 * @since 2.5.0
	 *
	 * @param array $post_mime_types Danh sách mặc định các kiểu mime bài viết.
	 */
	return apply_filters( 'post_mime_types', $post_mime_types );
}

/**
 * Kiểm tra kiểu MIME so với danh sách.
 *
 * Nếu tham số `$wildcard_mime_types` là chuỗi, nó phải là danh sách phân cách
 * bằng dấu phẩy. Nếu `$real_mime_types` là chuỗi, nó cũng được phân cách bằng dấu phẩy
 * để tạo danh sách.
 *
 * @since 2.5.0
 *
 * @param string|string[] $wildcard_mime_types Các kiểu mime, ví dụ `audio/mpeg`, `image` (giống `image/*`),
 *                                             hoặc `flash` (giống `*flash*`).
 * @param string|string[] $real_mime_types     Giá trị kiểu mime thực của bài viết.
 * @return array mảng(wildcard=>mảng(kiểu thực)).
 */
function wp_match_mime_types( $wildcard_mime_types, $real_mime_types ) {
	$matches = array();
	if ( is_string( $wildcard_mime_types ) ) {
		$wildcard_mime_types = array_map( 'trim', explode( ',', $wildcard_mime_types ) );
	}
	if ( is_string( $real_mime_types ) ) {
		$real_mime_types = array_map( 'trim', explode( ',', $real_mime_types ) );
	}

	$patternses = array();
	$wild       = '[-._a-z0-9]*';

	foreach ( (array) $wildcard_mime_types as $type ) {
		$mimes = array_map( 'trim', explode( ',', $type ) );
		foreach ( $mimes as $mime ) {
			$regex = str_replace( '__wildcard__', $wild, preg_quote( str_replace( '*', '__wildcard__', $mime ) ) );

			$patternses[][ $type ] = "^$regex$";

			if ( ! str_contains( $mime, '/' ) ) {
				$patternses[][ $type ] = "^$regex/";
				$patternses[][ $type ] = $regex;
			}
		}
	}
	asort( $patternses );

	foreach ( $patternses as $patterns ) {
		foreach ( $patterns as $type => $pattern ) {
			foreach ( (array) $real_mime_types as $real ) {
				if ( preg_match( "#$pattern#", $real )
					&& ( empty( $matches[ $type ] ) || false === array_search( $real, $matches[ $type ], true ) )
				) {
					$matches[ $type ][] = $real;
				}
			}
		}
	}

	return $matches;
}

/**
 * Chuyển đổi các kiểu MIME thành SQL.
 *
 * @since 2.5.0
 *
 * @param string|string[] $post_mime_types Danh sách các kiểu mime hoặc chuỗi phân cách bằng dấu phẩy
 *                                         của các kiểu mime.
 * @param string          $table_alias     Tùy chọn. Chỉ định bí danh bảng, nếu cần.
 *                                         Mặc định rỗng.
 * @return string Mệnh đề SQL AND cho việc tìm kiếm mime.
 */
function wp_post_mime_type_where( $post_mime_types, $table_alias = '' ) {
	$where     = '';
	$wildcards = array( '', '%', '%/%' );
	if ( is_string( $post_mime_types ) ) {
		$post_mime_types = array_map( 'trim', explode( ',', $post_mime_types ) );
	}

	$where_clauses = array();

	foreach ( (array) $post_mime_types as $mime_type ) {
		$mime_type = preg_replace( '/\s/', '', $mime_type );
		$slashpos  = strpos( $mime_type, '/' );
		if ( false !== $slashpos ) {
			$mime_group    = preg_replace( '/[^-*.a-zA-Z0-9]/', '', substr( $mime_type, 0, $slashpos ) );
			$mime_subgroup = preg_replace( '/[^-*.+a-zA-Z0-9]/', '', substr( $mime_type, $slashpos + 1 ) );
			if ( empty( $mime_subgroup ) ) {
				$mime_subgroup = '*';
			} else {
				$mime_subgroup = str_replace( '/', '', $mime_subgroup );
			}
			$mime_pattern = "$mime_group/$mime_subgroup";
		} else {
			$mime_pattern = preg_replace( '/[^-*.a-zA-Z0-9]/', '', $mime_type );
			if ( ! str_contains( $mime_pattern, '*' ) ) {
				$mime_pattern .= '/*';
			}
		}

		$mime_pattern = preg_replace( '/\*+/', '%', $mime_pattern );

		if ( in_array( $mime_type, $wildcards, true ) ) {
			return '';
		}

		if ( str_contains( $mime_pattern, '%' ) ) {
			$where_clauses[] = empty( $table_alias ) ? "post_mime_type LIKE '$mime_pattern'" : "$table_alias.post_mime_type LIKE '$mime_pattern'";
		} else {
			$where_clauses[] = empty( $table_alias ) ? "post_mime_type = '$mime_pattern'" : "$table_alias.post_mime_type = '$mime_pattern'";
		}
	}

	if ( ! empty( $where_clauses ) ) {
		$where = ' AND (' . implode( ' OR ', $where_clauses ) . ') ';
	}

	return $where;
}

/**
 * Đưa vào thùng rác hoặc xóa bài viết hoặc trang.
 *
 * Khi bài viết và trang bị xóa vĩnh viễn, mọi thứ liên kết với nó
 * cũng bị xóa. Bao gồm bình luận, trường meta bài viết, và các term
 * liên kết với bài viết.
 *
 * Bài viết hoặc trang được chuyển vào Thùng rác thay vì xóa vĩnh viễn trừ khi
 * Thùng rác bị vô hiệu hóa, mục đã ở trong Thùng rác, hoặc $force_delete là true.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 * @see wp_delete_attachment()
 * @see wp_trash_post()
 *
 * @param int  $post_id      Tùy chọn. ID bài viết. Mặc định 0.
 * @param bool $force_delete Tùy chọn. Có bỏ qua Thùng rác và buộc xóa hay không.
 *                           Mặc định false.
 * @return WP_Post|false|null Dữ liệu bài viết nếu thành công, false hoặc null nếu thất bại.
 */
function wp_delete_post( $post_id = 0, $force_delete = false ) {
	global $wpdb;

	$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d", $post_id ) );

	if ( ! $post ) {
		return $post;
	}

	$post = get_post( $post );

	if ( ! $force_delete
		&& ( 'post' === $post->post_type || 'page' === $post->post_type )
		&& 'trash' !== get_post_status( $post_id ) && EMPTY_TRASH_DAYS
	) {
		return wp_trash_post( $post_id );
	}

	if ( 'attachment' === $post->post_type ) {
		return wp_delete_attachment( $post_id, $force_delete );
	}

	/**
	 * Lọc xem việc xóa bài viết có nên diễn ra hay không.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Post|false|null $delete       Có tiếp tục xóa hay không.
	 * @param WP_Post            $post         Đối tượng bài viết.
	 * @param bool               $force_delete Có bỏ qua Thùng rác hay không.
	 */
	$check = apply_filters( 'pre_delete_post', null, $post, $force_delete );
	if ( null !== $check ) {
		return $check;
	}

	/**
	 * Kích hoạt trước khi bài viết bị xóa, tại đầu wp_delete_post().
	 *
	 * @since 3.2.0
	 * @since 5.5.0 Thêm tham số `$post`.
	 *
	 * @see wp_delete_post()
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( 'before_delete_post', $post_id, $post );

	delete_post_meta( $post_id, '_wp_trash_meta_status' );
	delete_post_meta( $post_id, '_wp_trash_meta_time' );

	wp_delete_object_term_relationships( $post_id, get_object_taxonomies( $post->post_type ) );

	$parent_data  = array( 'post_parent' => $post->post_parent );
	$parent_where = array( 'post_parent' => $post_id );

	if ( is_post_type_hierarchical( $post->post_type ) ) {
		// Trỏ các trang con của trang này đến trang cha, đồng thời xóa bộ nhớ đệm của các trang con bị ảnh hưởng.
		$children_query = $wpdb->prepare(
			"SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s",
			$post_id,
			$post->post_type
		);

		$children = $wpdb->get_results( $children_query );

		if ( $children ) {
			$wpdb->update( $wpdb->posts, $parent_data, $parent_where + array( 'post_type' => $post->post_type ) );
		}
	}

	// Thực hiện truy vấn thô. wp_get_post_revisions() được lọc.
	$revision_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'revision'", $post_id )
	);

	// Sử dụng lại wp_delete_post (qua wp_delete_post_revision). Đảm bảo mọi dữ liệu meta/đặt sai chỗ được dọn dẹp.
	foreach ( $revision_ids as $revision_id ) {
		wp_delete_post_revision( $revision_id );
	}

	// Trỏ tất cả tệp đính kèm của bài viết này lên một cấp.
	$wpdb->update( $wpdb->posts, $parent_data, $parent_where + array( 'post_type' => 'attachment' ) );

	wp_defer_comment_counting( true );

	$comment_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d ORDER BY comment_ID DESC", $post_id )
	);

	foreach ( $comment_ids as $comment_id ) {
		wp_delete_comment( $comment_id, true );
	}

	wp_defer_comment_counting( false );

	$post_meta_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d ", $post_id )
	);

	foreach ( $post_meta_ids as $mid ) {
		delete_metadata_by_mid( 'post', $mid );
	}

	/**
	 * Kích hoạt ngay trước khi bài viết bị xóa khỏi cơ sở dữ liệu.
	 *
	 * Phần động của tên hook, `$post->post_type`, tham chiếu đến
	 * slug của post type.
	 *
	 * @since 6.6.0
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( "delete_post_{$post->post_type}", $post_id, $post );

	/**
	 * Kích hoạt ngay trước khi bài viết bị xóa khỏi cơ sở dữ liệu.
	 *
	 * @since 1.2.0
	 * @since 5.5.0 Thêm tham số `$post`.
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( 'delete_post', $post_id, $post );

	$result = $wpdb->delete( $wpdb->posts, array( 'ID' => $post_id ) );
	if ( ! $result ) {
		return false;
	}

	/**
	 * Kích hoạt ngay sau khi bài viết bị xóa khỏi cơ sở dữ liệu.
	 *
	 * Phần động của tên hook, `$post->post_type`, tham chiếu đến
	 * slug của post type.
	 *
	 * @since 6.6.0
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( "deleted_post_{$post->post_type}", $post_id, $post );

	/**
	 * Kích hoạt ngay sau khi bài viết bị xóa khỏi cơ sở dữ liệu.
	 *
	 * @since 2.2.0
	 * @since 5.5.0 Thêm tham số `$post`.
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( 'deleted_post', $post_id, $post );

	clean_post_cache( $post );

	if ( is_post_type_hierarchical( $post->post_type ) && $children ) {
		foreach ( $children as $child ) {
			clean_post_cache( $child );
		}
	}

	wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) );

	/**
	 * Kích hoạt sau khi bài viết bị xóa, tại cuối wp_delete_post().
	 *
	 * @since 3.2.0
	 * @since 5.5.0 Thêm tham số `$post`.
	 *
	 * @see wp_delete_post()
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( 'after_delete_post', $post_id, $post );

	return $post;
}

/**
 * Đặt lại các cài đặt page_on_front, show_on_front, và page_for_post khi
 * trang được liên kết bị xóa hoặc đưa vào thùng rác.
 *
 * Cũng đảm bảo bài viết không còn là bài ghim.
 *
 * @since 3.7.0
 * @access private
 *
 * @param int $post_id ID bài viết.
 */
function _reset_front_page_settings_for_post( $post_id ) {
	$post = get_post( $post_id );

	if ( 'page' === $post->post_type ) {
		/*
		 * Nếu trang được định nghĩa trong tùy chọn page_on_front hoặc post_for_posts,
		 * điều chỉnh các tùy chọn tương ứng.
		 */
		if ( (int) get_option( 'page_on_front' ) === $post->ID ) {
			update_option( 'show_on_front', 'posts' );
			update_option( 'page_on_front', 0 );
		}
		if ( (int) get_option( 'page_for_posts' ) === $post->ID ) {
			update_option( 'page_for_posts', 0 );
		}
	}

	unstick_post( $post->ID );
}

/**
 * Chuyển bài viết hoặc trang vào Thùng rác.
 *
 * Nếu Thùng rác bị vô hiệu hóa, bài viết hoặc trang sẽ bị xóa vĩnh viễn.
 *
 * @since 2.9.0
 *
 * @see wp_delete_post()
 *
 * @param int $post_id Tùy chọn. ID bài viết. Mặc định là ID của global `$post`
 *                     nếu `EMPTY_TRASH_DAYS` bằng true.
 * @return WP_Post|false|null Dữ liệu bài viết nếu thành công, false hoặc null nếu thất bại.
 */
function wp_trash_post( $post_id = 0 ) {
	if ( ! EMPTY_TRASH_DAYS ) {
		return wp_delete_post( $post_id, true );
	}

	$post = get_post( $post_id );

	if ( ! $post ) {
		return $post;
	}

	if ( 'trash' === $post->post_status ) {
		return false;
	}

	$previous_status = $post->post_status;

	/**
	 * Lọc xem việc đưa bài viết vào thùng rác có nên diễn ra hay không.
	 *
	 * @since 4.9.0
	 * @since 6.3.0 Thêm tham số `$previous_status`.
	 *
	 * @param bool|null $trash           Có tiếp tục đưa vào thùng rác hay không.
	 * @param WP_Post   $post            Đối tượng bài viết.
	 * @param string    $previous_status Trạng thái của bài viết sắp bị đưa vào thùng rác.
	 */
	$check = apply_filters( 'pre_trash_post', null, $post, $previous_status );

	if ( null !== $check ) {
		return $check;
	}

	/**
	 * Kích hoạt trước khi bài viết được gửi vào Thùng rác.
	 *
	 * @since 3.3.0
	 * @since 6.3.0 Thêm tham số `$previous_status`.
	 *
	 * @param int    $post_id         ID bài viết.
	 * @param string $previous_status Trạng thái của bài viết sắp bị đưa vào thùng rác.
	 */
	do_action( 'wp_trash_post', $post_id, $previous_status );

	add_post_meta( $post_id, '_wp_trash_meta_status', $previous_status );
	add_post_meta( $post_id, '_wp_trash_meta_time', time() );

	$post_updated = wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'trash',
		)
	);

	if ( ! $post_updated ) {
		return false;
	}

	wp_trash_post_comments( $post_id );

	/**
	 * Kích hoạt sau khi bài viết được gửi vào Thùng rác.
	 *
	 * @since 2.9.0
	 * @since 6.3.0 Thêm tham số `$previous_status`.
	 *
	 * @param int    $post_id         ID bài viết.
	 * @param string $previous_status Trạng thái của bài viết tại thời điểm bị đưa vào thùng rác.
	 */
	do_action( 'trashed_post', $post_id, $previous_status );

	return $post;
}

/**
 * Khôi phục bài viết từ Thùng rác.
 *
 * @since 2.9.0
 * @since 5.6.0 Bài viết khôi phục giờ được trả về trạng thái 'draft' theo mặc định, ngoại trừ
 *              tệp đính kèm được trả về trạng thái gốc 'inherit'.
 *
 * @param int $post_id Tùy chọn. ID bài viết. Mặc định là ID của global `$post`.
 * @return WP_Post|false|null Dữ liệu bài viết nếu thành công, false hoặc null nếu thất bại.
 */
function wp_untrash_post( $post_id = 0 ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return $post;
	}

	$post_id = $post->ID;

	if ( 'trash' !== $post->post_status ) {
		return false;
	}

	$previous_status = get_post_meta( $post_id, '_wp_trash_meta_status', true );

	/**
	 * Lọc xem việc khôi phục bài viết từ thùng rác có nên diễn ra hay không.
	 *
	 * @since 4.9.0
	 * @since 5.6.0 Thêm tham số `$previous_status`.
	 *
	 * @param bool|null $untrash         Có tiếp tục khôi phục hay không.
	 * @param WP_Post   $post            Đối tượng bài viết.
	 * @param string    $previous_status Trạng thái của bài viết tại thời điểm bị đưa vào thùng rác.
	 */
	$check = apply_filters( 'pre_untrash_post', null, $post, $previous_status );
	if ( null !== $check ) {
		return $check;
	}

	/**
	 * Kích hoạt trước khi bài viết được khôi phục từ Thùng rác.
	 *
	 * @since 2.9.0
	 * @since 5.6.0 Thêm tham số `$previous_status`.
	 *
	 * @param int    $post_id         ID bài viết.
	 * @param string $previous_status Trạng thái của bài viết tại thời điểm bị đưa vào thùng rác.
	 */
	do_action( 'untrash_post', $post_id, $previous_status );

	$new_status = ( 'attachment' === $post->post_type ) ? 'inherit' : 'draft';

	/**
	 * Lọc trạng thái được gán cho bài viết khi được khôi phục từ thùng rác.
	 *
	 * Mặc định các bài viết được khôi phục sẽ được gán trạng thái 'draft'. Trả về giá trị của `$previous_status`
	 * để gán trạng thái mà bài viết có trước khi bị đưa vào thùng rác. Hàm `wp_untrash_post_set_previous_status()`
	 * có sẵn cho mục đích này.
	 *
	 * Trước WordPress 5.6.0, các bài viết được khôi phục luôn được gán trạng thái gốc.
	 *
	 * @since 5.6.0
	 *
	 * @param string $new_status      Trạng thái mới của bài viết đang được khôi phục.
	 * @param int    $post_id         ID của bài viết đang được khôi phục.
	 * @param string $previous_status Trạng thái của bài viết tại thời điểm bị đưa vào thùng rác.
	 */
	$post_status = apply_filters( 'wp_untrash_post_status', $new_status, $post_id, $previous_status );

	delete_post_meta( $post_id, '_wp_trash_meta_status' );
	delete_post_meta( $post_id, '_wp_trash_meta_time' );

	$post_updated = wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => $post_status,
		)
	);

	if ( ! $post_updated ) {
		return false;
	}

	wp_untrash_post_comments( $post_id );

	/**
	 * Kích hoạt sau khi bài viết được khôi phục từ Thùng rác.
	 *
	 * @since 2.9.0
	 * @since 5.6.0 Thêm tham số `$previous_status`.
	 *
	 * @param int    $post_id         ID bài viết.
	 * @param string $previous_status Trạng thái của bài viết tại thời điểm bị đưa vào thùng rác.
	 */
	do_action( 'untrashed_post', $post_id, $previous_status );

	return $post;
}

/**
 * Chuyển các bình luận của bài viết vào Thùng rác.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @return mixed|void False nếu thất bại.
 */
function wp_trash_post_comments( $post = null ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_id = $post->ID;

	/**
	 * Kích hoạt trước khi các bình luận được gửi vào Thùng rác.
	 *
	 * @since 2.9.0
	 *
	 * @param int $post_id ID bài viết.
	 */
	do_action( 'trash_post_comments', $post_id );

	$comments = $wpdb->get_results( $wpdb->prepare( "SELECT comment_ID, comment_approved FROM $wpdb->comments WHERE comment_post_ID = %d", $post_id ) );

	if ( ! $comments ) {
		return;
	}

	// Lưu trạng thái hiện tại cho mỗi bình luận vào bộ nhớ đệm.
	$statuses = array();
	foreach ( $comments as $comment ) {
		$statuses[ $comment->comment_ID ] = $comment->comment_approved;
	}
	add_post_meta( $post_id, '_wp_trash_meta_comments_status', $statuses );

	// Đặt trạng thái cho tất cả bình luận thành post-trashed.
	$result = $wpdb->update( $wpdb->comments, array( 'comment_approved' => 'post-trashed' ), array( 'comment_post_ID' => $post_id ) );

	clean_comment_cache( array_keys( $statuses ) );

	/**
	 * Kích hoạt sau khi các bình luận được gửi vào Thùng rác.
	 *
	 * @since 2.9.0
	 *
	 * @param int   $post_id  ID bài viết.
	 * @param array $statuses Mảng các trạng thái bình luận.
	 */
	do_action( 'trashed_post_comments', $post_id, $statuses );

	return $result;
}

/**
 * Khôi phục các bình luận của bài viết từ Thùng rác.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định global $post.
 * @return true|void
 */
function wp_untrash_post_comments( $post = null ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_id = $post->ID;

	$statuses = get_post_meta( $post_id, '_wp_trash_meta_comments_status', true );

	if ( ! $statuses ) {
		return true;
	}

	/**
	 * Kích hoạt trước khi các bình luận được khôi phục cho bài viết từ Thùng rác.
	 *
	 * @since 2.9.0
	 *
	 * @param int $post_id ID bài viết.
	 */
	do_action( 'untrash_post_comments', $post_id );

	// Khôi phục mỗi bình luận về trạng thái gốc của nó.
	$group_by_status = array();
	foreach ( $statuses as $comment_id => $comment_status ) {
		$group_by_status[ $comment_status ][] = $comment_id;
	}

	foreach ( $group_by_status as $status => $comments ) {
		// Kiểm tra an toàn. Trường hợp này không nên xảy ra.
		if ( 'post-trashed' === $status ) {
			$status = '0';
		}
		$comments_in = implode( ', ', array_map( 'intval', $comments ) );
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->comments SET comment_approved = %s WHERE comment_ID IN ($comments_in)", $status ) );
	}

	clean_comment_cache( array_keys( $statuses ) );

	delete_post_meta( $post_id, '_wp_trash_meta_comments_status' );

	/**
	 * Kích hoạt sau khi các bình luận được khôi phục cho bài viết từ Thùng rác.
	 *
	 * @since 2.9.0
	 *
	 * @param int $post_id ID bài viết.
	 */
	do_action( 'untrashed_post_comments', $post_id );
}

/**
 * Lấy danh sách các chuyên mục cho một bài viết.
 *
 * Lớp tương thích cho theme và plugin. Cũng là lớp trừu tượng đơn giản
 * tránh sự phức tạp của lớp taxonomy.
 *
 * @since 2.1.0
 *
 * @see wp_get_object_terms()
 *
 * @param int   $post_id Tùy chọn. ID bài viết. Không mặc định là ID của
 *                       global $post. Mặc định 0.
 * @param array $args    Tùy chọn. Tham số truy vấn chuyên mục. Mặc định mảng rỗng.
 *                       Xem WP_Term_Query::__construct() để biết các đối số được hỗ trợ.
 * @return array|WP_Error Danh sách chuyên mục. Nếu đối số `$fields` được truyền qua `$args` là 'all' hoặc
 *                        'all_with_object_id', mảng các đối tượng WP_Term được trả về. Nếu `$fields`
 *                        là 'ids', mảng các ID chuyên mục. Nếu `$fields` là 'names', mảng tên chuyên mục.
 *                        Đối tượng WP_Error nếu taxonomy 'category' không tồn tại.
 */
function wp_get_post_categories( $post_id = 0, $args = array() ) {
	$post_id = (int) $post_id;

	$defaults = array( 'fields' => 'ids' );
	$args     = wp_parse_args( $args, $defaults );

	$cats = wp_get_object_terms( $post_id, 'category', $args );
	return $cats;
}

/**
 * Lấy các thẻ (tag) cho một bài viết.
 *
 * Chỉ có một giá trị mặc định cho hàm này, gọi là 'fields' và mặc định
 * được đặt thành 'all'. Có các giá trị mặc định khác có thể ghi đè trong
 * wp_get_object_terms().
 *
 * @since 2.3.0
 *
 * @param int   $post_id Tùy chọn. ID bài viết. Không mặc định là ID của
 *                       global $post. Mặc định 0.
 * @param array $args    Tùy chọn. Tham số truy vấn thẻ. Mặc định mảng rỗng.
 *                       Xem WP_Term_Query::__construct() để biết các đối số được hỗ trợ.
 * @return array|WP_Error Mảng các đối tượng WP_Term nếu thành công hoặc mảng rỗng nếu không tìm thấy thẻ.
 *                        Đối tượng WP_Error nếu taxonomy 'post_tag' không tồn tại.
 */
function wp_get_post_tags( $post_id = 0, $args = array() ) {
	return wp_get_post_terms( $post_id, 'post_tag', $args );
}

/**
 * Lấy các term cho một bài viết.
 *
 * @since 2.8.0
 *
 * @param int             $post_id  Tùy chọn. ID bài viết. Không mặc định là ID của
 *                                  global $post. Mặc định 0.
 * @param string|string[] $taxonomy Tùy chọn. Slug taxonomy hoặc mảng các slug
 *                                  cần lấy term. Mặc định 'post_tag'.
 * @param array           $args     {
 *     Tùy chọn. Tham số truy vấn term. Xem WP_Term_Query::__construct() để biết các đối số được hỗ trợ.
 *
 *     @type string $fields Các trường term cần lấy. Mặc định 'all'.
 * }
 * @return array|WP_Error Mảng các đối tượng WP_Term nếu thành công hoặc mảng rỗng nếu không tìm thấy term.
 *                        Đối tượng WP_Error nếu `$taxonomy` không tồn tại.
 */
function wp_get_post_terms( $post_id = 0, $taxonomy = 'post_tag', $args = array() ) {
	$post_id = (int) $post_id;

	$defaults = array( 'fields' => 'all' );
	$args     = wp_parse_args( $args, $defaults );

	$tags = wp_get_object_terms( $post_id, $taxonomy, $args );

	return $tags;
}

/**
 * Lấy một số bài viết gần đây.
 *
 * @since 1.0.0
 *
 * @see get_posts()
 *
 * @param array  $args   Tùy chọn. Các đối số để lấy bài viết. Mặc định mảng rỗng.
 * @param string $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT hoặc ARRAY_A,
 *                       tương ứng với đối tượng WP_Post hoặc mảng liên kết.
 *                       Mặc định ARRAY_A.
 * @return array|false Mảng các bài viết gần đây, kiểu của mỗi phần tử được xác định
 *                     bởi tham số `$output`. Mảng rỗng nếu thất bại.
 */
function wp_get_recent_posts( $args = array(), $output = ARRAY_A ) {

	if ( is_numeric( $args ) ) {
		_deprecated_argument( __FUNCTION__, '3.1.0', __( 'Passing an integer number of posts is deprecated. Pass an array of arguments instead.' ) );
		$args = array( 'numberposts' => absint( $args ) );
	}

	// Đặt các đối số mặc định.
	$defaults = array(
		'numberposts'      => 10,
		'offset'           => 0,
		'category'         => 0,
		'orderby'          => 'post_date',
		'order'            => 'DESC',
		'include'          => '',
		'exclude'          => '',
		'meta_key'         => '',
		'meta_value'       => '',
		'post_type'        => 'post',
		'post_status'      => 'draft, publish, future, pending, private',
		'suppress_filters' => true,
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	$results = get_posts( $parsed_args );

	// Tương thích ngược. Trước phiên bản 3.1, bài viết được mong đợi trả về trong mảng.
	if ( ARRAY_A === $output ) {
		foreach ( $results as $key => $result ) {
			$results[ $key ] = get_object_vars( $result );
		}
		return $results ? $results : array();
	}

	return $results ? $results : false;
}

/**
 * Chèn hoặc cập nhật bài viết.
 *
 * Nếu tham số $postarr có 'ID' được đặt giá trị, bài viết sẽ được cập nhật.
 *
 * Bạn có thể đặt ngày bài viết thủ công bằng cách đặt giá trị cho khóa 'post_date'
 * và 'post_date_gmt'. Bạn có thể đóng hoặc mở bình luận bằng cách
 * đặt giá trị cho khóa 'comment_status'.
 *
 * @since 1.0.0
 * @since 2.6.0 Thêm tham số `$wp_error` để cho phép trả về WP_Error khi thất bại.
 * @since 4.2.0 Thêm hỗ trợ mã hóa emoji trong tiêu đề, nội dung và trích dẫn bài viết.
 * @since 4.4.0 Mảng 'meta_input' giờ có thể được truyền vào `$postarr` để thêm dữ liệu meta bài viết.
 * @since 5.6.0 Thêm tham số `$fire_after_hooks`.
 *
 * @see sanitize_post()
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param array $postarr {
 *     Mảng các phần tử tạo nên bài viết để cập nhật hoặc chèn.
 *
 *     @type int    $ID                    ID bài viết. Nếu khác 0,
 *                                         bài viết có ID đó sẽ được cập nhật. Mặc định 0.
 *     @type int    $post_author           ID của người dùng đã thêm bài viết. Mặc định là
 *                                         ID người dùng hiện tại.
 *     @type string $post_date             Ngày của bài viết. Mặc định là thời gian hiện tại.
 *     @type string $post_date_gmt         Ngày của bài viết theo múi giờ GMT. Mặc định là
 *                                         giá trị của `$post_date`.
 *     @type string $post_content          Nội dung bài viết. Mặc định rỗng.
 *     @type string $post_content_filtered Nội dung bài viết đã lọc. Mặc định rỗng.
 *     @type string $post_title            Tiêu đề bài viết. Mặc định rỗng.
 *     @type string $post_excerpt          Trích dẫn bài viết. Mặc định rỗng.
 *     @type string $post_status           Trạng thái bài viết. Mặc định 'draft'.
 *     @type string $post_type             Post type. Mặc định 'post'.
 *     @type string $comment_status        Bài viết có chấp nhận bình luận không. Chấp nhận 'open' hoặc 'closed'.
 *                                         Mặc định là giá trị tùy chọn 'default_comment_status'.
 *     @type string $ping_status           Bài viết có chấp nhận ping không. Chấp nhận 'open' hoặc 'closed'.
 *                                         Mặc định là giá trị tùy chọn 'default_ping_status'.
 *     @type string $post_password         Mật khẩu để truy cập bài viết. Mặc định rỗng.
 *     @type string $post_name             Tên bài viết. Mặc định là tiêu đề bài viết đã làm sạch
 *                                         khi tạo bài viết mới.
 *     @type string $to_ping               Danh sách URL cần ping phân cách bằng dấu cách hoặc xuống dòng.
 *                                         Mặc định rỗng.
 *     @type string $pinged                Danh sách URL đã ping phân cách bằng dấu cách hoặc xuống dòng.
 *                                         Mặc định rỗng.
 *     @type int    $post_parent           Đặt bài viết cha nếu có. Mặc định 0.
 *     @type int    $menu_order            Thứ tự hiển thị bài viết. Mặc định 0.
 *     @type string $post_mime_type        Kiểu mime của bài viết. Mặc định rỗng.
 *     @type string $guid                  ID Duy nhất Toàn cầu để tham chiếu bài viết. Mặc định rỗng.
 *     @type int    $import_id             ID bài viết sẽ dùng khi chèn bài viết mới.
 *                                         Nếu chỉ định, không được trùng với ID bài viết đã có. Mặc định 0.
 *     @type int[]  $post_category         Mảng ID chuyên mục.
 *                                         Mặc định là giá trị tùy chọn 'default_category'.
 *     @type array  $tags_input            Mảng tên thẻ, slug, hoặc ID. Mặc định rỗng.
 *     @type array  $tax_input             Mảng các term taxonomy được đánh khóa theo tên taxonomy.
 *                                         Nếu taxonomy phân cấp, danh sách term cần là
 *                                         mảng ID term hoặc chuỗi ID phân cách bằng dấu phẩy.
 *                                         Nếu taxonomy không phân cấp, danh sách term có thể là mảng
 *                                         chứa tên hoặc slug term, hoặc chuỗi tên hoặc slug phân cách
 *                                         bằng dấu phẩy. Vì trong taxonomy phân cấp,
 *                                         term con có thể có cùng tên với term cha khác nhau,
 *                                         nên cách duy nhất để kết nối chúng là dùng ID. Mặc định rỗng.
 *     @type array  $meta_input            Mảng giá trị meta bài viết được đánh khóa theo khóa meta. Mặc định rỗng.
 *     @type string $page_template         Template trang sẽ dùng.
 * }
 * @param bool  $wp_error         Tùy chọn. Có trả về WP_Error khi thất bại hay không. Mặc định false.
 * @param bool  $fire_after_hooks Tùy chọn. Có kích hoạt các hook sau khi chèn hay không. Mặc định true.
 * @return int|WP_Error ID bài viết nếu thành công. Giá trị 0 hoặc WP_Error nếu thất bại.
 */
function wp_insert_post( $postarr, $wp_error = false, $fire_after_hooks = true ) {
	global $wpdb;

	// Lưu mảng gốc chưa làm sạch để truyền vào các filter.
	$unsanitized_postarr = $postarr;

	$user_id = get_current_user_id();

	$defaults = array(
		'post_author'           => $user_id,
		'post_content'          => '',
		'post_content_filtered' => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => 'draft',
		'post_type'             => 'post',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_parent'           => 0,
		'menu_order'            => 0,
		'guid'                  => '',
		'import_id'             => 0,
		'context'               => '',
		'post_date'             => '',
		'post_date_gmt'         => '',
	);

	$postarr = wp_parse_args( $postarr, $defaults );

	unset( $postarr['filter'] );

	$postarr = sanitize_post( $postarr, 'db' );

	// Đang cập nhật hay tạo mới?
	$post_id = 0;
	$update  = false;
	$guid    = $postarr['guid'];

	if ( ! empty( $postarr['ID'] ) ) {
		$update = true;

		// Lấy ID bài viết và GUID.
		$post_id     = $postarr['ID'];
		$post_before = get_post( $post_id );

		if ( is_null( $post_before ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'invalid_post', __( 'Invalid post ID.' ) );
			}
			return 0;
		}

		$guid            = get_post_field( 'guid', $post_id );
		$previous_status = get_post_field( 'post_status', $post_id );
	} else {
		$previous_status = 'new';
		$post_before     = null;
	}

	$post_type = empty( $postarr['post_type'] ) ? 'post' : $postarr['post_type'];

	$post_title   = $postarr['post_title'];
	$post_content = $postarr['post_content'];
	$post_excerpt = $postarr['post_excerpt'];

	if ( isset( $postarr['post_name'] ) ) {
		$post_name = $postarr['post_name'];
	} elseif ( $update ) {
		// Khi cập nhật, không thay đổi post_name nếu không được cung cấp dưới dạng đối số.
		$post_name = $post_before->post_name;
	}

	$maybe_empty = 'attachment' !== $post_type
		&& ! $post_content && ! $post_title && ! $post_excerpt
		&& post_type_supports( $post_type, 'editor' )
		&& post_type_supports( $post_type, 'title' )
		&& post_type_supports( $post_type, 'excerpt' );

	/**
	 * Lọc xem bài viết có nên được coi là "rỗng" hay không.
	 *
	 * Bài viết được coi là "rỗng" nếu cả hai điều kiện:
	 * 1. Post type hỗ trợ các trường tiêu đề, trình soạn thảo và trích dẫn
	 * 2. Các trường tiêu đề, trình soạn thảo và trích dẫn đều rỗng
	 *
	 * Trả về giá trị truthy từ filter sẽ hiệu quả ngắn mạch
	 * việc chèn bài viết mới và trả về 0. Nếu $wp_error là true, WP_Error
	 * sẽ được trả về thay thế.
	 *
	 * @since 3.3.0
	 *
	 * @param bool  $maybe_empty Bài viết có nên được coi là "rỗng" hay không.
	 * @param array $postarr     Mảng dữ liệu bài viết.
	 */
	if ( apply_filters( 'wp_insert_post_empty_content', $maybe_empty, $postarr ) ) {
		if ( $wp_error ) {
			return new WP_Error( 'empty_content', __( 'Content, title, and excerpt are empty.' ) );
		} else {
			return 0;
		}
	}

	$post_status = empty( $postarr['post_status'] ) ? 'draft' : $postarr['post_status'];

	if ( 'attachment' === $post_type && ! in_array( $post_status, array( 'inherit', 'private', 'trash', 'auto-draft' ), true ) ) {
		$post_status = 'inherit';
	}

	if ( ! empty( $postarr['post_category'] ) ) {
		// Lọc bỏ các term rỗng.
		$post_category = array_filter( $postarr['post_category'] );
	} elseif ( $update && ! isset( $postarr['post_category'] ) ) {
		$post_category = $post_before->post_category;
	}

	// Đảm bảo đặt chuyên mục hợp lệ.
	if ( empty( $post_category ) || 0 === count( $post_category ) || ! is_array( $post_category ) ) {
		// 'post' yêu cầu ít nhất một chuyên mục.
		if ( 'post' === $post_type && 'auto-draft' !== $post_status ) {
			$post_category = array( get_option( 'default_category' ) );
		} else {
			$post_category = array();
		}
	}

	/*
	 * Không cho phép cộng tác viên đặt slug bài viết cho bài viết đang chờ duyệt.
	 *
	 * Với bài viết mới kiểm tra quyền primitive, với cập nhật kiểm tra quyền meta.
	 */
	if ( 'pending' === $post_status ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $update && $post_type_object && ! current_user_can( $post_type_object->cap->publish_posts ) ) {
			$post_name = '';
		} elseif ( $update && ! current_user_can( 'publish_post', $post_id ) ) {
			$post_name = '';
		}
	}

	/*
	 * Tạo tên bài viết hợp lệ. Bài viết nháp và đang chờ duyệt được phép có
	 * tên bài viết rỗng.
	 */
	if ( empty( $post_name ) ) {
		if ( ! in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ), true ) ) {
			$post_name = sanitize_title( $post_title );
		} else {
			$post_name = '';
		}
	} else {
		// Khi cập nhật, cần kiểm tra xem có đang dùng ngữ cảnh làm sạch cũ, cố định hay không.
		$check_name = sanitize_title( $post_name, '', 'old-save' );

		if ( $update
			&& strtolower( urlencode( $post_name ) ) === $check_name
			&& get_post_field( 'post_name', $post_id ) === $check_name
		) {
			$post_name = $check_name;
		} else { // Bài viết mới, hoặc slug đã thay đổi.
			$post_name = sanitize_title( $post_name );
		}
	}

	/*
	 * Giải quyết ngày bài viết từ bất kỳ chuỗi ngày bài viết hoặc ngày GMT nào được cung cấp;
	 * nếu không có, ngày sẽ được đặt thành hiện tại.
	 */
	$post_date = wp_resolve_post_date( $postarr['post_date'], $postarr['post_date_gmt'] );

	if ( ! $post_date ) {
		if ( $wp_error ) {
			return new WP_Error( 'invalid_date', __( 'Invalid date.' ) );
		} else {
			return 0;
		}
	}

	if ( empty( $postarr['post_date_gmt'] ) || '0000-00-00 00:00:00' === $postarr['post_date_gmt'] ) {
		if ( ! in_array( $post_status, get_post_stati( array( 'date_floating' => true ) ), true ) ) {
			$post_date_gmt = get_gmt_from_date( $post_date );
		} else {
			$post_date_gmt = '0000-00-00 00:00:00';
		}
	} else {
		$post_date_gmt = $postarr['post_date_gmt'];
	}

	if ( $update || '0000-00-00 00:00:00' === $post_date ) {
		$post_modified     = current_time( 'mysql' );
		$post_modified_gmt = current_time( 'mysql', 1 );
	} else {
		$post_modified     = $post_date;
		$post_modified_gmt = $post_date_gmt;
	}

	if ( 'attachment' !== $post_type ) {
		$now = gmdate( 'Y-m-d H:i:s' );

		if ( 'publish' === $post_status ) {
			if ( strtotime( $post_date_gmt ) - strtotime( $now ) >= MINUTE_IN_SECONDS ) {
				$post_status = 'future';
			}
		} elseif ( 'future' === $post_status ) {
			if ( strtotime( $post_date_gmt ) - strtotime( $now ) < MINUTE_IN_SECONDS ) {
				$post_status = 'publish';
			}
		}
	}

	// Trạng thái bình luận.
	if ( empty( $postarr['comment_status'] ) ) {
		if ( $update ) {
			$comment_status = 'closed';
		} else {
			$comment_status = get_default_comment_status( $post_type );
		}
	} else {
		$comment_status = $postarr['comment_status'];
	}

	// Các biến này cần thiết cho compact() sau đó.
	$post_content_filtered = $postarr['post_content_filtered'];
	$post_author           = isset( $postarr['post_author'] ) ? $postarr['post_author'] : $user_id;
	$ping_status           = empty( $postarr['ping_status'] ) ? get_default_comment_status( $post_type, 'pingback' ) : $postarr['ping_status'];
	$to_ping               = isset( $postarr['to_ping'] ) ? sanitize_trackback_urls( $postarr['to_ping'] ) : '';
	$pinged                = isset( $postarr['pinged'] ) ? $postarr['pinged'] : '';
	$import_id             = isset( $postarr['import_id'] ) ? $postarr['import_id'] : 0;

	/*
	 * Filter 'wp_insert_post_parent' mong đợi tất cả các biến đều có mặt.
	 * Trước đây, các biến này đã được trích xuất sẵn.
	 */
	if ( isset( $postarr['menu_order'] ) ) {
		$menu_order = (int) $postarr['menu_order'];
	} else {
		$menu_order = 0;
	}

	$post_password = isset( $postarr['post_password'] ) ? $postarr['post_password'] : '';
	if ( 'private' === $post_status ) {
		$post_password = '';
	}

	if ( isset( $postarr['post_parent'] ) ) {
		$post_parent = (int) $postarr['post_parent'];
	} else {
		$post_parent = 0;
	}

	$new_postarr = array_merge(
		array(
			'ID' => $post_id,
		),
		compact( array_diff( array_keys( $defaults ), array( 'context', 'filter' ) ) )
	);

	/**
	 * Lọc bài viết cha -- dùng để kiểm tra và ngăn chặn vòng lặp phân cấp.
	 *
	 * @since 3.1.0
	 *
	 * @param int   $post_parent ID bài viết cha.
	 * @param int   $post_id     ID bài viết.
	 * @param array $new_postarr Mảng dữ liệu bài viết đã phân tích.
	 * @param array $postarr     Mảng dữ liệu bài viết đã làm sạch, nhưng không thay đổi gì khác.
	 */
	$post_parent = apply_filters( 'wp_insert_post_parent', $post_parent, $post_id, $new_postarr, $postarr );

	/*
	 * Nếu bài viết đang được khôi phục từ thùng rác và có slug mong muốn được lưu trong post meta,
	 * gán lại nó.
	 */
	if ( 'trash' === $previous_status && 'trash' !== $post_status ) {
		$desired_post_slug = get_post_meta( $post_id, '_wp_desired_post_slug', true );

		if ( $desired_post_slug ) {
			delete_post_meta( $post_id, '_wp_desired_post_slug' );
			$post_name = $desired_post_slug;
		}
	}

	// Nếu bài viết trong thùng rác có slug mong muốn, thay đổi nó và để bài viết này dùng.
	if ( 'trash' !== $post_status && $post_name ) {
		/**
		 * Lọc có thêm hậu tố `__trashed` vào bài viết trong thùng rác khớp tên bài viết đang cập nhật hay không.
		 *
		 * @since 5.4.0
		 *
		 * @param bool   $add_trashed_suffix Có cố gắng thêm hậu tố hay không.
		 * @param string $post_name          Tên bài viết đang được cập nhật.
		 * @param int    $post_id            ID bài viết.
		 */
		$add_trashed_suffix = apply_filters( 'add_trashed_suffix_to_trashed_posts', true, $post_name, $post_id );

		if ( $add_trashed_suffix ) {
			wp_add_trashed_suffix_to_post_name_for_trashed_posts( $post_name, $post_id );
		}
	}

	// Khi đưa bài viết hiện có vào thùng rác, thay đổi slug để cho phép bài viết không ở thùng rác sử dụng nó.
	if ( 'trash' === $post_status && 'trash' !== $previous_status && 'new' !== $previous_status ) {
		$post_name = wp_add_trashed_suffix_to_post_name_for_post( $post_id );
	}

	$post_name = wp_unique_post_slug( $post_name, $post_id, $post_status, $post_type, $post_parent );

	// Không bỏ dấu gạch chéo.
	$post_mime_type = isset( $postarr['post_mime_type'] ) ? $postarr['post_mime_type'] : '';

	// Mong đợi có dấu gạch chéo (tất cả!).
	$data = compact(
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_content_filtered',
		'post_title',
		'post_excerpt',
		'post_status',
		'post_type',
		'comment_status',
		'ping_status',
		'post_password',
		'post_name',
		'to_ping',
		'pinged',
		'post_modified',
		'post_modified_gmt',
		'post_parent',
		'menu_order',
		'post_mime_type',
		'guid'
	);

	$emoji_fields = array( 'post_title', 'post_content', 'post_excerpt' );

	foreach ( $emoji_fields as $emoji_field ) {
		if ( isset( $data[ $emoji_field ] ) ) {
			$charset = $wpdb->get_col_charset( $wpdb->posts, $emoji_field );

			if ( 'utf8' === $charset ) {
				$data[ $emoji_field ] = wp_encode_emoji( $data[ $emoji_field ] );
			}
		}
	}

	if ( 'attachment' === $post_type ) {
		/**
		 * Lọc dữ liệu tệp đính kèm trước khi được cập nhật hoặc thêm vào cơ sở dữ liệu.
		 *
		 * @since 3.9.0
		 * @since 5.4.1 Thêm tham số `$unsanitized_postarr`.
		 * @since 6.0.0 Thêm tham số `$update`.
		 *
		 * @param array $data                Mảng dữ liệu tệp đính kèm đã có dấu gạch chéo, làm sạch và xử lý.
		 * @param array $postarr             Mảng dữ liệu tệp đính kèm đã có dấu gạch chéo và làm sạch, nhưng chưa xử lý.
		 * @param array $unsanitized_postarr Mảng dữ liệu tệp đính kèm đã có dấu gạch chéo nhưng *chưa làm sạch* và chưa xử lý
		 *                                   như ban đầu được truyền vào wp_insert_post().
		 * @param bool  $update              Đây có phải là tệp đính kèm hiện có đang được cập nhật hay không.
		 */
		$data = apply_filters( 'wp_insert_attachment_data', $data, $postarr, $unsanitized_postarr, $update );
	} else {
		/**
		 * Lọc dữ liệu bài viết đã có dấu gạch chéo ngay trước khi được chèn vào cơ sở dữ liệu.
		 *
		 * @since 2.7.0
		 * @since 5.4.1 Thêm tham số `$unsanitized_postarr`.
		 * @since 6.0.0 Thêm tham số `$update`.
		 *
		 * @param array $data                Mảng dữ liệu bài viết đã có dấu gạch chéo, làm sạch và xử lý.
		 * @param array $postarr             Mảng dữ liệu bài viết đã làm sạch (và có dấu gạch chéo) nhưng không thay đổi gì khác.
		 * @param array $unsanitized_postarr Mảng dữ liệu bài viết đã có dấu gạch chéo nhưng *chưa làm sạch* và chưa xử lý
		 *                                   như ban đầu được truyền vào wp_insert_post().
		 * @param bool  $update              Đây có phải là bài viết hiện có đang được cập nhật hay không.
		 */
		$data = apply_filters( 'wp_insert_post_data', $data, $postarr, $unsanitized_postarr, $update );
	}

	$data  = wp_unslash( $data );
	$where = array( 'ID' => $post_id );

	if ( $update ) {
		/**
		 * Kích hoạt ngay trước khi bài viết hiện có được cập nhật trong cơ sở dữ liệu.
		 *
		 * @since 2.5.0
		 *
		 * @param int   $post_id ID bài viết.
		 * @param array $data    Mảng dữ liệu bài viết đã bỏ dấu gạch chéo.
		 */
		do_action( 'pre_post_update', $post_id, $data );

		if ( false === $wpdb->update( $wpdb->posts, $data, $where ) ) {
			if ( $wp_error ) {
				if ( 'attachment' === $post_type ) {
					$message = __( 'Could not update attachment in the database.' );
				} else {
					$message = __( 'Could not update post in the database.' );
				}

				return new WP_Error( 'db_update_error', $message, $wpdb->last_error );
			} else {
				return 0;
			}
		}
	} else {
		// Nếu có ID được gợi ý, sử dụng nó nếu chưa tồn tại.
		if ( ! empty( $import_id ) ) {
			$import_id = (int) $import_id;

			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $import_id ) ) ) {
				$data['ID'] = $import_id;
			}
		}

		if ( false === $wpdb->insert( $wpdb->posts, $data ) ) {
			if ( $wp_error ) {
				if ( 'attachment' === $post_type ) {
					$message = __( 'Could not insert attachment into the database.' );
				} else {
					$message = __( 'Could not insert post into the database.' );
				}

				return new WP_Error( 'db_insert_error', $message, $wpdb->last_error );
			} else {
				return 0;
			}
		}

		$post_id = (int) $wpdb->insert_id;

		// Sử dụng $post_id mới được tạo.
		$where = array( 'ID' => $post_id );
	}

	if ( empty( $data['post_name'] ) && ! in_array( $data['post_status'], array( 'draft', 'pending', 'auto-draft' ), true ) ) {
		$data['post_name'] = wp_unique_post_slug( sanitize_title( $data['post_title'], $post_id ), $post_id, $data['post_status'], $post_type, $post_parent );

		$wpdb->update( $wpdb->posts, array( 'post_name' => $data['post_name'] ), $where );
		clean_post_cache( $post_id );
	}

	if ( is_object_in_taxonomy( $post_type, 'category' ) ) {
		wp_set_post_categories( $post_id, $post_category );
	}

	if ( isset( $postarr['tags_input'] ) && is_object_in_taxonomy( $post_type, 'post_tag' ) ) {
		wp_set_post_tags( $post_id, $postarr['tags_input'] );
	}

	// Thêm term mặc định cho tất cả các taxonomy tùy chỉnh liên kết.
	if ( 'auto-draft' !== $post_status ) {
		foreach ( get_object_taxonomies( $post_type, 'object' ) as $taxonomy => $tax_object ) {

			if ( ! empty( $tax_object->default_term ) ) {

				// Lọc bỏ các term rỗng.
				if ( isset( $postarr['tax_input'][ $taxonomy ] ) && is_array( $postarr['tax_input'][ $taxonomy ] ) ) {
					$postarr['tax_input'][ $taxonomy ] = array_filter( $postarr['tax_input'][ $taxonomy ] );
				}

				// Danh sách taxonomy tùy chỉnh được truyền ghi đè danh sách hiện có nếu không rỗng.
				$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
				if ( ! empty( $terms ) && empty( $postarr['tax_input'][ $taxonomy ] ) ) {
					$postarr['tax_input'][ $taxonomy ] = $terms;
				}

				if ( empty( $postarr['tax_input'][ $taxonomy ] ) ) {
					$default_term_id = get_option( 'default_term_' . $taxonomy );
					if ( ! empty( $default_term_id ) ) {
						$postarr['tax_input'][ $taxonomy ] = array( (int) $default_term_id );
					}
				}
			}
		}
	}

	// Hỗ trợ kiểu mới cho tất cả taxonomy tùy chỉnh.
	if ( ! empty( $postarr['tax_input'] ) ) {
		foreach ( $postarr['tax_input'] as $taxonomy => $tags ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );

			if ( ! $taxonomy_obj ) {
				/* translators: %s: Taxonomy name. */
				_doing_it_wrong( __FUNCTION__, sprintf( __( 'Invalid taxonomy: %s.' ), $taxonomy ), '4.4.0' );
				continue;
			}

			// mảng = phân cấp, chuỗi = không phân cấp.
			if ( is_array( $tags ) ) {
				$tags = array_filter( $tags );
			}

			if ( current_user_can( $taxonomy_obj->cap->assign_terms ) ) {
				wp_set_post_terms( $post_id, $tags, $taxonomy );
			}
		}
	}

	if ( ! empty( $postarr['meta_input'] ) ) {
		foreach ( $postarr['meta_input'] as $field => $value ) {
			update_post_meta( $post_id, $field, $value );
		}
	}

	$current_guid = get_post_field( 'guid', $post_id );

	// Đặt GUID.
	if ( ! $update && '' === $current_guid ) {
		$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post_id ) ), $where );
	}

	if ( 'attachment' === $postarr['post_type'] ) {
		if ( ! empty( $postarr['file'] ) ) {
			update_attached_file( $post_id, $postarr['file'] );
		}

		if ( ! empty( $postarr['context'] ) ) {
			add_post_meta( $post_id, '_wp_attachment_context', $postarr['context'], true );
		}
	}

	// Đặt hoặc xóa ảnh đại diện.
	if ( isset( $postarr['_thumbnail_id'] ) ) {
		$thumbnail_support = current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) || 'revision' === $post_type;

		if ( ! $thumbnail_support && 'attachment' === $post_type && $post_mime_type ) {
			if ( wp_attachment_is( 'audio', $post_id ) ) {
				$thumbnail_support = post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
			} elseif ( wp_attachment_is( 'video', $post_id ) ) {
				$thumbnail_support = post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
			}
		}

		if ( $thumbnail_support ) {
			$thumbnail_id = (int) $postarr['_thumbnail_id'];
			if ( -1 === $thumbnail_id ) {
				delete_post_thumbnail( $post_id );
			} else {
				set_post_thumbnail( $post_id, $thumbnail_id );
			}
		}
	}

	clean_post_cache( $post_id );

	$post = get_post( $post_id );

	if ( ! empty( $postarr['page_template'] ) ) {
		$post->page_template = $postarr['page_template'];
		$page_templates      = wp_get_theme()->get_page_templates( $post );

		if ( 'default' !== $postarr['page_template'] && ! isset( $page_templates[ $postarr['page_template'] ] ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'invalid_page_template', __( 'Invalid page template.' ) );
			}

			update_post_meta( $post_id, '_wp_page_template', 'default' );
		} else {
			update_post_meta( $post_id, '_wp_page_template', $postarr['page_template'] );
		}
	}

	if ( 'attachment' !== $postarr['post_type'] ) {
		wp_transition_post_status( $data['post_status'], $previous_status, $post );
	} else {
		if ( $update ) {
			/**
			 * Kích hoạt khi một tệp đính kèm hiện có đã được cập nhật.
			 *
			 * @since 2.0.0
			 *
			 * @param int $post_id ID tệp đính kèm.
			 */
			do_action( 'edit_attachment', $post_id );

			$post_after = get_post( $post_id );

			/**
			 * Kích hoạt khi một tệp đính kèm hiện có đã được cập nhật.
			 *
			 * @since 4.4.0
			 *
			 * @param int     $post_id      ID bài viết.
			 * @param WP_Post $post_after   Đối tượng bài viết sau khi cập nhật.
			 * @param WP_Post $post_before  Đối tượng bài viết trước khi cập nhật.
			 */
			do_action( 'attachment_updated', $post_id, $post_after, $post_before );
		} else {

			/**
			 * Kích hoạt khi một tệp đính kèm đã được thêm.
			 *
			 * @since 2.0.0
			 *
			 * @param int $post_id ID tệp đính kèm.
			 */
			do_action( 'add_attachment', $post_id );
		}

		return $post_id;
	}

	if ( $update ) {
		/**
		 * Kích hoạt khi một bài viết hiện có đã được cập nhật.
		 *
		 * Phần động của tên hook, `$post->post_type`, tham chiếu đến
		 * slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `edit_post_post`
		 *  - `edit_post_page`
		 *
		 * @since 5.1.0
		 *
		 * @param int     $post_id ID bài viết.
		 * @param WP_Post $post    Đối tượng bài viết.
		 */
		do_action( "edit_post_{$post->post_type}", $post_id, $post );

		/**
		 * Kích hoạt khi một bài viết hiện có đã được cập nhật.
		 *
		 * @since 1.2.0
		 *
		 * @param int     $post_id ID bài viết.
		 * @param WP_Post $post    Đối tượng bài viết.
		 */
		do_action( 'edit_post', $post_id, $post );

		$post_after = get_post( $post_id );

		/**
		 * Kích hoạt khi một bài viết hiện có đã được cập nhật.
		 *
		 * @since 3.0.0
		 *
		 * @param int     $post_id      ID bài viết.
		 * @param WP_Post $post_after   Đối tượng bài viết sau khi cập nhật.
		 * @param WP_Post $post_before  Đối tượng bài viết trước khi cập nhật.
		 */
		do_action( 'post_updated', $post_id, $post_after, $post_before );
	}

	/**
	 * Kích hoạt khi một bài viết đã được lưu.
	 *
	 * Phần động của tên hook, `$post->post_type`, tham chiếu đến
	 * slug loại bài viết.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `save_post_post`
	 *  - `save_post_page`
	 *
	 * @since 3.7.0
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 * @param bool    $update  Có phải bài viết hiện có đang được cập nhật hay không.
	 */
	do_action( "save_post_{$post->post_type}", $post_id, $post, $update );

	/**
	 * Kích hoạt khi một bài viết đã được lưu.
	 *
	 * @since 1.5.0
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 * @param bool    $update  Có phải bài viết hiện có đang được cập nhật hay không.
	 */
	do_action( 'save_post', $post_id, $post, $update );

	/**
	 * Kích hoạt khi một bài viết đã được lưu.
	 *
	 * @since 2.0.0
	 *
	 * @param int     $post_id ID bài viết.
	 * @param WP_Post $post    Đối tượng bài viết.
	 * @param bool    $update  Có phải bài viết hiện có đang được cập nhật hay không.
	 */
	do_action( 'wp_insert_post', $post_id, $post, $update );

	if ( $fire_after_hooks ) {
		wp_after_insert_post( $post, $update, $post_before );
	}

	return $post_id;
}

/**
 * Cập nhật bài viết với dữ liệu mới.
 *
 * Ngày không cần phải được đặt cho bài nháp. Bạn có thể đặt ngày và nó sẽ
 * không bị ghi đè.
 *
 * @since 1.0.0
 * @since 3.5.0 Thêm tham số `$wp_error` để cho phép trả về WP_Error khi thất bại.
 * @since 5.6.0 Thêm tham số `$fire_after_hooks`.
 *
 * @param array|object $postarr          Tùy chọn. Dữ liệu bài viết. Mảng phải được escape,
 *                                       đối tượng thì không. Xem wp_insert_post() để biết các đối số được chấp nhận.
 *                                       Mặc định mảng rỗng.
 * @param bool         $wp_error         Tùy chọn. Có trả về WP_Error khi thất bại hay không. Mặc định false.
 * @param bool         $fire_after_hooks Tùy chọn. Có kích hoạt các hook sau khi chèn hay không. Mặc định true.
 * @return int|WP_Error ID bài viết nếu thành công. Giá trị 0 hoặc WP_Error nếu thất bại.
 */
function wp_update_post( $postarr = array(), $wp_error = false, $fire_after_hooks = true ) {
	if ( is_object( $postarr ) ) {
		// Bài viết chưa escape được truyền vào.
		$postarr = get_object_vars( $postarr );
		$postarr = wp_slash( $postarr );
	}

	// Đầu tiên, lấy tất cả các trường gốc.
	$post = get_post( $postarr['ID'], ARRAY_A );

	if ( is_null( $post ) ) {
		if ( $wp_error ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post ID.' ) );
		}
		return 0;
	}

	// Escape dữ liệu lấy từ CSDL.
	$post = wp_slash( $post );

	// Danh sách chuyên mục được truyền vào sẽ ghi đè danh sách hiện có nếu không rỗng.
	if ( isset( $postarr['post_category'] ) && is_array( $postarr['post_category'] )
		&& count( $postarr['post_category'] ) > 0
	) {
		$post_cats = $postarr['post_category'];
	} else {
		$post_cats = $post['post_category'];
	}

	// Bài nháp không nên được gán ngày trừ khi người dùng chủ động làm vậy.
	if ( isset( $post['post_status'] )
		&& in_array( $post['post_status'], array( 'draft', 'pending', 'auto-draft' ), true )
		&& empty( $postarr['edit_date'] ) && ( '0000-00-00 00:00:00' === $post['post_date_gmt'] )
	) {
		$clear_date = true;
	} else {
		$clear_date = false;
	}

	// Gộp các trường cũ và mới, trường mới ghi đè trường cũ.
	$postarr                  = array_merge( $post, $postarr );
	$postarr['post_category'] = $post_cats;
	if ( $clear_date ) {
		$postarr['post_date']     = current_time( 'mysql' );
		$postarr['post_date_gmt'] = '';
	}

	if ( 'attachment' === $postarr['post_type'] ) {
		return wp_insert_attachment( $postarr, false, 0, $wp_error );
	}

	// Bỏ qua tham số 'tags_input' nếu nó giống với thẻ bài viết hiện tại.
	if ( isset( $postarr['tags_input'] ) && is_object_in_taxonomy( $postarr['post_type'], 'post_tag' ) ) {
		$tags      = get_the_terms( $postarr['ID'], 'post_tag' );
		$tag_names = array();

		if ( $tags && ! is_wp_error( $tags ) ) {
			$tag_names = wp_list_pluck( $tags, 'name' );
		}

		if ( $postarr['tags_input'] === $tag_names ) {
			unset( $postarr['tags_input'] );
		}
	}

	return wp_insert_post( $postarr, $wp_error, $fire_after_hooks );
}

/**
 * Xuất bản bài viết bằng cách chuyển đổi trạng thái bài viết.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng bài viết.
 */
function wp_publish_post( $post ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	if ( 'publish' === $post->post_status ) {
		return;
	}

	$post_before = get_post( $post->ID );

	// Đảm bảo ít nhất một term được áp dụng cho các taxonomy có term mặc định.
	foreach ( get_object_taxonomies( $post->post_type, 'object' ) as $taxonomy => $tax_object ) {
		// Bỏ qua taxonomy nếu không có term mặc định được đặt.
		if (
			'category' !== $taxonomy &&
			empty( $tax_object->default_term )
		) {
			continue;
		}

		// Không sửa đổi các term đã được đặt trước đó.
		if ( ! empty( get_the_terms( $post, $taxonomy ) ) ) {
			continue;
		}

		if ( 'category' === $taxonomy ) {
			$default_term_id = (int) get_option( 'default_category', 0 );
		} else {
			$default_term_id = (int) get_option( 'default_term_' . $taxonomy, 0 );
		}

		if ( ! $default_term_id ) {
			continue;
		}
		wp_set_post_terms( $post->ID, array( $default_term_id ), $taxonomy );
	}

	$wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => $post->ID ) );

	clean_post_cache( $post->ID );

	$old_status        = $post->post_status;
	$post->post_status = 'publish';
	wp_transition_post_status( 'publish', $old_status, $post );

	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( "edit_post_{$post->post_type}", $post->ID, $post );

	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( 'edit_post', $post->ID, $post );

	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( "save_post_{$post->post_type}", $post->ID, $post, true );

	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( 'save_post', $post->ID, $post, true );

	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( 'wp_insert_post', $post->ID, $post, true );

	wp_after_insert_post( $post, true, $post_before );
}

/**
 * Xuất bản bài viết tương lai và đảm bảo ID bài viết có trạng thái future.
 *
 * Được gọi bởi sự kiện cron 'publish_future_post'. Biện pháp bảo vệ này ngăn cron
 * xuất bản bài nháp, v.v.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng bài viết.
 */
function check_and_publish_future_post( $post ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	if ( 'future' !== $post->post_status ) {
		return;
	}

	$time = strtotime( $post->post_date_gmt . ' GMT' );

	// Ồ, ai đó đã hành động quá sớm!
	if ( $time > time() ) {
		wp_clear_scheduled_hook( 'publish_future_post', array( $post->ID ) ); // Xóa mọi thứ khác trong hệ thống.
		wp_schedule_single_event( $time, 'publish_future_post', array( $post->ID ) );
		return;
	}

	// wp_publish_post() không trả về giá trị có ý nghĩa.
	wp_publish_post( $post->ID );
}

/**
 * Sử dụng wp_checkdate để trả về giá trị lịch Gregorian hợp lệ cho post_date.
 * Nếu post_date không được cung cấp, trước tiên kiểm tra post_date_gmt nếu có,
 * sau đó quay về sử dụng thời gian hiện tại.
 *
 * Vì mục đích tương thích ngược trong wp_insert_post, post_date rỗng và
 * post_date_gmt không hợp lệ sẽ tiếp tục trả về '1970-01-01 00:00:00' thay vì false.
 *
 * @since 5.7.0
 *
 * @param string $post_date     Ngày theo định dạng mysql (`Y-m-d H:i:s`).
 * @param string $post_date_gmt Ngày GMT theo định dạng mysql (`Y-m-d H:i:s`).
 * @return string|false Chuỗi ngày lịch Gregorian hợp lệ, hoặc false nếu thất bại.
 */
function wp_resolve_post_date( $post_date = '', $post_date_gmt = '' ) {
	// Nếu ngày rỗng, đặt ngày thành thời gian hiện tại.
	if ( empty( $post_date ) || '0000-00-00 00:00:00' === $post_date ) {
		if ( empty( $post_date_gmt ) || '0000-00-00 00:00:00' === $post_date_gmt ) {
			$post_date = current_time( 'mysql' );
		} else {
			$post_date = get_date_from_gmt( $post_date_gmt );
		}
	}

	// Xác thực ngày.
	$month = (int) substr( $post_date, 5, 2 );
	$day   = (int) substr( $post_date, 8, 2 );
	$year  = (int) substr( $post_date, 0, 4 );

	$valid_date = wp_checkdate( $month, $day, $year, $post_date );

	if ( ! $valid_date ) {
		return false;
	}
	return $post_date;
}

/**
 * Tính toán slug duy nhất cho bài viết, khi được cung cấp slug mong muốn và một số chi tiết bài viết.
 *
 * @since 2.8.0
 *
 * @global wpdb       $wpdb       Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $slug        Slug mong muốn (post_name).
 * @param int    $post_id     ID bài viết.
 * @param string $post_status Không kiểm tra tính duy nhất nếu bài viết vẫn là nháp hoặc đang chờ duyệt.
 * @param string $post_type   Loại bài viết.
 * @param int    $post_parent ID bài viết cha.
 * @return string Slug duy nhất cho bài viết, dựa trên $post_name (với hậu tố -1, -2, v.v.)
 */
function wp_unique_post_slug( $slug, $post_id, $post_status, $post_type, $post_parent ) {
	if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ), true )
		|| ( 'inherit' === $post_status && 'revision' === $post_type ) || 'user_request' === $post_type
	) {
		return $slug;
	}

	/**
	 * Lọc slug bài viết trước khi nó được tạo thành duy nhất.
	 *
	 * Trả về giá trị không null sẽ bỏ qua quá trình
	 * tạo slug duy nhất, trả về giá trị được truyền thay thế.
	 *
	 * @since 5.1.0
	 *
	 * @param string|null $override_slug Giá trị trả về bỏ qua.
	 * @param string      $slug          Slug mong muốn (post_name).
	 * @param int         $post_id       ID bài viết.
	 * @param string      $post_status   Trạng thái bài viết.
	 * @param string      $post_type     Loại bài viết.
	 * @param int         $post_parent   ID bài viết cha.
	 */
	$override_slug = apply_filters( 'pre_wp_unique_post_slug', null, $slug, $post_id, $post_status, $post_type, $post_parent );
	if ( null !== $override_slug ) {
		return $override_slug;
	}

	global $wpdb, $wp_rewrite;

	$original_slug = $slug;

	$feeds = $wp_rewrite->feeds;
	if ( ! is_array( $feeds ) ) {
		$feeds = array();
	}

	if ( 'attachment' === $post_type ) {
		// Slug tệp đính kèm phải duy nhất trên tất cả các loại.
		$check_sql       = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_id ) );

		/**
		 * Lọc xem slug bài viết có phải là slug tệp đính kèm xấu hay không.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $bad_slug Slug có xấu khi dùng làm slug tệp đính kèm hay không.
		 * @param string $slug     Slug bài viết.
		 */
		$is_bad_attachment_slug = apply_filters( 'wp_unique_post_slug_is_bad_attachment_slug', false, $slug );

		if ( $post_name_check
			|| in_array( $slug, $feeds, true ) || 'embed' === $slug
			|| $is_bad_attachment_slug
		) {
			$suffix = 2;
			do {
				$alt_post_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_id ) );
				++$suffix;
			} while ( $post_name_check );
			$slug = $alt_post_name;
		}
	} elseif ( is_post_type_hierarchical( $post_type ) ) {
		if ( 'nav_menu_item' === $post_type ) {
			return $slug;
		}

		/*
		 * Slug trang phải duy nhất trong cây riêng của chúng. Trang nằm trong không gian tên
		 * riêng biệt so với bài viết nên slug trang được phép trùng với slug bài viết.
		 */
		$check_sql       = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type IN ( %s, 'attachment' ) AND ID != %d AND post_parent = %d LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_type, $post_id, $post_parent ) );

		/**
		 * Lọc xem slug bài viết có phải là slug bài viết phân cấp xấu hay không.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $bad_slug    Slug bài viết có xấu trong ngữ cảnh bài viết phân cấp hay không.
		 * @param string $slug        Slug bài viết.
		 * @param string $post_type   Loại bài viết.
		 * @param int    $post_parent ID bài viết cha.
		 */
		$is_bad_hierarchical_slug = apply_filters( 'wp_unique_post_slug_is_bad_hierarchical_slug', false, $slug, $post_type, $post_parent );

		if ( $post_name_check
			|| in_array( $slug, $feeds, true ) || 'embed' === $slug
			|| preg_match( "@^($wp_rewrite->pagination_base)?\d+$@", $slug )
			|| $is_bad_hierarchical_slug
		) {
			$suffix = 2;
			do {
				$alt_post_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_type, $post_id, $post_parent ) );
				++$suffix;
			} while ( $post_name_check );
			$slug = $alt_post_name;
		}
	} else {
		// Slug bài viết phải duy nhất trên tất cả các bài viết.
		$check_sql       = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_type, $post_id ) );

		$post = get_post( $post_id );

		// Ngăn chặn slug bài viết mới có thể dẫn đến URL xung đột với lưu trữ theo ngày.
		$conflicts_with_date_archive = false;
		if ( 'post' === $post_type && ( ! $post || $post->post_name !== $slug ) && preg_match( '/^[0-9]+$/', $slug ) ) {
			$slug_num = (int) $slug;

			if ( $slug_num ) {
				$permastructs   = array_values( array_filter( explode( '/', get_option( 'permalink_structure' ) ) ) );
				$postname_index = array_search( '%postname%', $permastructs, true );

				/*
				* Các xung đột ngày tiềm ẩn như sau:
				*
				* - Bất kỳ số nguyên nào ở vị trí permastruct đầu tiên có thể là năm.
				* - Số nguyên từ 1 đến 12 theo sau 'year' xung đột với 'monthnum'.
				* - Số nguyên từ 1 đến 31 theo sau 'monthnum' xung đột với 'day'.
				*/
				if ( 0 === $postname_index ||
					( $postname_index && '%year%' === $permastructs[ $postname_index - 1 ] && 13 > $slug_num ) ||
					( $postname_index && '%monthnum%' === $permastructs[ $postname_index - 1 ] && 32 > $slug_num )
				) {
					$conflicts_with_date_archive = true;
				}
			}
		}

		/**
		 * Lọc xem slug bài viết có xấu khi dùng làm slug phẳng hay không.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $bad_slug  Slug bài viết có xấu khi dùng làm slug phẳng hay không.
		 * @param string $slug      Slug bài viết.
		 * @param string $post_type Loại bài viết.
		 */
		$is_bad_flat_slug = apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type );

		if ( $post_name_check
			|| in_array( $slug, $feeds, true ) || 'embed' === $slug
			|| $conflicts_with_date_archive
			|| $is_bad_flat_slug
		) {
			$suffix = 2;
			do {
				$alt_post_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_type, $post_id ) );
				++$suffix;
			} while ( $post_name_check );
			$slug = $alt_post_name;
		}
	}

	/**
	 * Lọc slug bài viết duy nhất.
	 *
	 * @since 3.3.0
	 *
	 * @param string $slug          Slug bài viết.
	 * @param int    $post_id       ID bài viết.
	 * @param string $post_status   Trạng thái bài viết.
	 * @param string $post_type     Loại bài viết.
	 * @param int    $post_parent   ID bài viết cha.
	 * @param string $original_slug Slug bài viết gốc.
	 */
	return apply_filters( 'wp_unique_post_slug', $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug );
}

/**
 * Cắt ngắn slug bài viết.
 *
 * @since 3.6.0
 * @access private
 *
 * @see utf8_uri_encode()
 *
 * @param string $slug   Slug cần cắt ngắn.
 * @param int    $length Tùy chọn. Độ dài tối đa của slug. Mặc định 200 (ký tự).
 * @return string Slug đã cắt ngắn.
 */
function _truncate_post_slug( $slug, $length = 200 ) {
	if ( strlen( $slug ) > $length ) {
		$decoded_slug = urldecode( $slug );
		if ( $decoded_slug === $slug ) {
			$slug = substr( $slug, 0, $length );
		} else {
			$slug = utf8_uri_encode( $decoded_slug, $length, true );
		}
	}

	return rtrim( $slug, '-' );
}

/**
 * Thêm thẻ vào bài viết.
 *
 * @see wp_set_post_tags()
 *
 * @since 2.3.0
 *
 * @param int          $post_id Tùy chọn. ID bài viết. Không mặc định là ID của $post toàn cục.
 * @param string|array $tags    Tùy chọn. Mảng các thẻ để đặt cho bài viết, hoặc chuỗi các thẻ
 *                              phân cách bằng dấu phẩy. Mặc định rỗng.
 * @return array|false|WP_Error Mảng các ID term bị ảnh hưởng. WP_Error hoặc false nếu thất bại.
 */
function wp_add_post_tags( $post_id = 0, $tags = '' ) {
	return wp_set_post_tags( $post_id, $tags, true );
}

/**
 * Đặt thẻ cho bài viết.
 *
 * @since 2.3.0
 *
 * @see wp_set_object_terms()
 *
 * @param int          $post_id Tùy chọn. ID bài viết. Không mặc định là ID của $post toàn cục.
 * @param string|array $tags    Tùy chọn. Mảng các thẻ để đặt cho bài viết, hoặc chuỗi các thẻ
 *                              phân cách bằng dấu phẩy. Mặc định rỗng.
 * @param bool         $append  Tùy chọn. Nếu true, không xóa thẻ hiện tại, chỉ thêm vào. Nếu false,
 *                              thay thế thẻ bằng thẻ mới. Mặc định false.
 * @return array|false|WP_Error Mảng ID term taxonomy của các term bị ảnh hưởng. WP_Error hoặc false nếu thất bại.
 */
function wp_set_post_tags( $post_id = 0, $tags = '', $append = false ) {
	return wp_set_post_terms( $post_id, $tags, 'post_tag', $append );
}

/**
 * Đặt các term cho bài viết.
 *
 * @since 2.8.0
 *
 * @see wp_set_object_terms()
 *
 * @param int          $post_id  Tùy chọn. ID bài viết. Không mặc định là ID của $post toàn cục.
 * @param string|array $terms    Tùy chọn. Mảng các term để đặt cho bài viết, hoặc chuỗi các term
 *                               phân cách bằng dấu phẩy. Taxonomy phân cấp phải luôn truyền ID thay vì
 *                               tên để các term con cùng tên nhưng khác cha không bị nhầm lẫn.
 *                               Mặc định rỗng.
 * @param string       $taxonomy Tùy chọn. Tên taxonomy. Mặc định 'post_tag'.
 * @param bool         $append   Tùy chọn. Nếu true, không xóa term hiện tại, chỉ thêm vào. Nếu false,
 *                               thay thế term bằng term mới. Mặc định false.
 * @return array|false|WP_Error Mảng ID term taxonomy của các term bị ảnh hưởng. WP_Error hoặc false nếu thất bại.
 */
function wp_set_post_terms( $post_id = 0, $terms = '', $taxonomy = 'post_tag', $append = false ) {
	$post_id = (int) $post_id;

	if ( ! $post_id ) {
		return false;
	}

	if ( empty( $terms ) ) {
		$terms = array();
	}

	if ( ! is_array( $terms ) ) {
		$comma = _x( ',', 'tag delimiter' );
		if ( ',' !== $comma ) {
			$terms = str_replace( $comma, ',', $terms );
		}
		$terms = explode( ',', trim( $terms, " \n\t\r\0\x0B," ) );
	}

	/*
	 * Taxonomy phân cấp phải luôn truyền ID thay vì tên để
	 * các term con cùng tên nhưng khác cha không bị nhầm lẫn.
	 */
	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		$terms = array_unique( array_map( 'intval', $terms ) );
	}

	return wp_set_object_terms( $post_id, $terms, $taxonomy, $append );
}

/**
 * Đặt chuyên mục cho bài viết.
 *
 * Nếu không có chuyên mục nào được cung cấp, chuyên mục mặc định sẽ được sử dụng.
 *
 * @since 2.1.0
 *
 * @param int       $post_id         Tùy chọn. ID bài viết. Không mặc định là ID
 *                                   của $post toàn cục. Mặc định 0.
 * @param int[]|int $post_categories Tùy chọn. Danh sách ID chuyên mục, hoặc ID của một chuyên mục đơn.
 *                                   Mặc định mảng rỗng.
 * @param bool      $append          Nếu true, không xóa chuyên mục hiện tại, chỉ thêm vào.
 *                                   Nếu false, thay thế chuyên mục bằng chuyên mục mới.
 * @return array|false|WP_Error Mảng ID term taxonomy của các chuyên mục bị ảnh hưởng. WP_Error hoặc false nếu thất bại.
 */
function wp_set_post_categories( $post_id = 0, $post_categories = array(), $append = false ) {
	$post_id     = (int) $post_id;
	$post_type   = get_post_type( $post_id );
	$post_status = get_post_status( $post_id );

	// Nếu $post_categories chưa phải là mảng, chuyển nó thành mảng.
	$post_categories = (array) $post_categories;

	if ( empty( $post_categories ) ) {
		/**
		 * Lọc các loại bài viết (ngoài 'post') yêu cầu chuyên mục mặc định.
		 *
		 * @since 5.5.0
		 *
		 * @param string[] $post_types Mảng tên các loại bài viết. Mặc định mảng rỗng.
		 */
		$default_category_post_types = apply_filters( 'default_category_post_types', array() );

		// Bài viết thông thường luôn yêu cầu chuyên mục mặc định.
		$default_category_post_types = array_merge( $default_category_post_types, array( 'post' ) );

		if ( in_array( $post_type, $default_category_post_types, true )
			&& is_object_in_taxonomy( $post_type, 'category' )
			&& 'auto-draft' !== $post_status
		) {
			$post_categories = array( get_option( 'default_category' ) );
			$append          = false;
		} else {
			$post_categories = array();
		}
	} elseif ( 1 === count( $post_categories ) && '' === reset( $post_categories ) ) {
		return true;
	}

	return wp_set_post_terms( $post_id, $post_categories, 'category', $append );
}

/**
 * Kích hoạt các action liên quan đến chuyển đổi trạng thái bài viết.
 *
 * Khi bài viết được lưu, trạng thái bài viết được "chuyển đổi" từ trạng thái này sang trạng thái khác,
 * mặc dù điều này không phải lúc nào cũng có nghĩa là trạng thái thực sự đã thay đổi trước và sau
 * khi lưu. Hàm này kích hoạt một số action hook liên quan đến chuyển đổi đó:
 * action chung {@see 'transition_post_status'}, cũng như các hook động
 * {@see '$old_status_to_$new_status'} và {@see '$new_status_$post->post_type'}. Lưu ý
 * rằng hàm không chuyển đổi đối tượng bài viết trong cơ sở dữ liệu.
 *
 * Ví dụ: Khi xuất bản bài viết lần đầu, trạng thái bài viết có thể chuyển đổi
 * từ 'draft' – hoặc trạng thái khác – sang 'publish'. Tuy nhiên, nếu bài viết đã
 * được xuất bản và chỉ đang được cập nhật, trạng thái "cũ" và "mới" đều có thể là 'publish'
 * trước và sau khi chuyển đổi.
 *
 * @since 2.3.0
 *
 * @param string  $new_status Chuyển sang trạng thái bài viết này.
 * @param string  $old_status Trạng thái bài viết trước đó.
 * @param WP_Post $post Dữ liệu bài viết.
 */
function wp_transition_post_status( $new_status, $old_status, $post ) {
	/**
	 * Kích hoạt khi bài viết được chuyển đổi từ trạng thái này sang trạng thái khác.
	 *
	 * @since 2.3.0
	 *
	 * @param string  $new_status Trạng thái bài viết mới.
	 * @param string  $old_status Trạng thái bài viết cũ.
	 * @param WP_Post $post       Đối tượng bài viết.
	 */
	do_action( 'transition_post_status', $new_status, $old_status, $post );

	/**
	 * Kích hoạt khi bài viết được chuyển đổi từ trạng thái này sang trạng thái khác.
	 *
	 * Phần động của tên hook, `$new_status` và `$old_status`,
	 * tham chiếu đến trạng thái bài viết cũ và mới tương ứng.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `draft_to_publish`
	 *  - `publish_to_trash`
	 *  - `pending_to_draft`
	 *
	 * @since 2.3.0
	 *
	 * @param WP_Post $post Đối tượng bài viết.
	 */
	do_action( "{$old_status}_to_{$new_status}", $post );

	/**
	 * Kích hoạt khi bài viết được chuyển đổi từ trạng thái này sang trạng thái khác.
	 *
	 * Phần động của tên hook, `$new_status` và `$post->post_type`,
	 * tham chiếu đến trạng thái bài viết mới và loại bài viết tương ứng.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `draft_post`
	 *  - `future_post`
	 *  - `pending_post`
	 *  - `private_post`
	 *  - `publish_post`
	 *  - `trash_post`
	 *  - `draft_page`
	 *  - `future_page`
	 *  - `pending_page`
	 *  - `private_page`
	 *  - `publish_page`
	 *  - `trash_page`
	 *  - `publish_attachment`
	 *  - `trash_attachment`
	 *
	 * Xin lưu ý: Khi action này được hook bằng một trạng thái bài viết cụ thể (như
	 * 'publish', dưới dạng `publish_{$post->post_type}`), nó sẽ kích hoạt cả khi bài viết
	 * lần đầu chuyển đổi sang trạng thái đó từ trạng thái khác, cũng như khi
	 * cập nhật bài viết sau đó (trạng thái cũ và mới đều giống nhau).
	 *
	 * Do đó, nếu bạn chỉ muốn kích hoạt callback khi bài viết lần đầu
	 * chuyển sang một trạng thái, hãy sử dụng hook {@see 'transition_post_status'} thay thế.
	 *
	 * @since 2.3.0
	 * @since 5.9.0 Thêm tham số `$old_status`.
	 *
	 * @param int     $post_id    ID bài viết.
	 * @param WP_Post $post       Đối tượng bài viết.
	 * @param string  $old_status Trạng thái bài viết cũ.
	 */
	do_action( "{$new_status}_{$post->post_type}", $post->ID, $post, $old_status );
}

/**
 * Kích hoạt các action sau khi bài viết, term và meta data đã được lưu.
 *
 * @since 5.6.0
 *
 * @param int|WP_Post  $post        ID hoặc đối tượng bài viết đã được lưu.
 * @param bool         $update      Có phải bài viết hiện có đang được cập nhật hay không.
 * @param null|WP_Post $post_before Null cho bài viết mới, đối tượng WP_Post trước
 *                                  khi cập nhật cho bài viết được cập nhật.
 */
function wp_after_insert_post( $post, $update, $post_before ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_id = $post->ID;

	/**
	 * Kích hoạt sau khi bài viết, term và meta data đã được lưu.
	 *
	 * @since 5.6.0
	 *
	 * @param int          $post_id     ID bài viết.
	 * @param WP_Post      $post        Đối tượng bài viết.
	 * @param bool         $update      Có phải bài viết hiện có đang được cập nhật hay không.
	 * @param null|WP_Post $post_before Null cho bài viết mới, đối tượng WP_Post trước
	 *                                  khi cập nhật cho bài viết được cập nhật.
	 */
	do_action( 'wp_after_insert_post', $post_id, $post, $update, $post_before );
}

//
// Các hàm bình luận, trackback và pingback.
//

/**
 * Thêm URL vào danh sách đã ping.
 *
 * @since 1.5.0
 * @since 4.7.0 `$post` có thể là đối tượng WP_Post.
 * @since 4.7.0 `$uri` có thể là mảng URI.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Post  $post ID bài viết hoặc đối tượng bài viết.
 * @param string|array $uri  URI ping hoặc mảng URI.
 * @return int|false Số hàng đã được cập nhật.
 */
function add_ping( $post, $uri ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$pung = trim( $post->pinged );
	$pung = preg_split( '/\s/', $pung );

	if ( is_array( $uri ) ) {
		$pung = array_merge( $pung, $uri );
	} else {
		$pung[] = $uri;
	}
	$new = implode( "\n", $pung );

	/**
	 * Lọc URL ping mới để thêm cho bài viết đã cho.
	 *
	 * @since 2.0.0
	 *
	 * @param string $new URL ping mới cần thêm.
	 */
	$new = apply_filters( 'add_ping', $new );

	$return = $wpdb->update( $wpdb->posts, array( 'pinged' => $new ), array( 'ID' => $post->ID ) );
	clean_post_cache( $post->ID );
	return $return;
}

/**
 * Lấy các enclosure đã được đính kèm cho bài viết.
 *
 * @since 1.5.0
 *
 * @param int $post_id ID bài viết.
 * @return string[] Mảng các enclosure cho bài viết đã cho.
 */
function get_enclosed( $post_id ) {
	$custom_fields = get_post_custom( $post_id );
	$pung          = array();
	if ( ! is_array( $custom_fields ) ) {
		return $pung;
	}

	foreach ( $custom_fields as $key => $val ) {
		if ( 'enclosure' !== $key || ! is_array( $val ) ) {
			continue;
		}
		foreach ( $val as $enc ) {
			$enclosure = explode( "\n", $enc );
			$pung[]    = trim( $enclosure[0] );
		}
	}

	/**
	 * Lọc danh sách các enclosure đã được đính kèm cho bài viết đã cho.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $pung    Mảng các enclosure cho bài viết đã cho.
	 * @param int      $post_id ID bài viết.
	 */
	return apply_filters( 'get_enclosed', $pung, $post_id );
}

/**
 * Lấy các URL đã được ping cho bài viết.
 *
 * @since 1.5.0
 *
 * @since 4.7.0 `$post` có thể là đối tượng WP_Post.
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng.
 * @return string[]|false Mảng các URL đã ping cho bài viết đã cho, false nếu không tìm thấy bài viết.
 */
function get_pung( $post ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$pung = trim( $post->pinged );
	$pung = preg_split( '/\s/', $pung );

	/**
	 * Lọc danh sách URL đã được ping cho bài viết đã cho.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $pung Mảng các URL đã ping cho bài viết đã cho.
	 */
	return apply_filters( 'get_pung', $pung );
}

/**
 * Lấy các URL cần được ping.
 *
 * @since 1.5.0
 * @since 4.7.0 `$post` có thể là đối tượng WP_Post.
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng bài viết.
 * @return string[]|false Danh sách URL chưa được ping.
 */
function get_to_ping( $post ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$to_ping = sanitize_trackback_urls( $post->to_ping );
	$to_ping = preg_split( '/\s/', $to_ping, -1, PREG_SPLIT_NO_EMPTY );

	/**
	 * Lọc danh sách URL chưa được ping cho bài viết đã cho.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $to_ping Danh sách URL chưa được ping.
	 */
	return apply_filters( 'get_to_ping', $to_ping );
}

/**
 * Thực hiện trackback cho danh sách URL.
 *
 * @since 1.0.0
 *
 * @param string $tb_list Danh sách URL phân cách bằng dấu phẩy.
 * @param int    $post_id ID bài viết.
 */
function trackback_url_list( $tb_list, $post_id ) {
	if ( ! empty( $tb_list ) ) {
		// Lấy dữ liệu bài viết.
		$postdata = get_post( $post_id, ARRAY_A );

		// Tạo đoạn trích.
		$excerpt = strip_tags( $postdata['post_excerpt'] ? $postdata['post_excerpt'] : $postdata['post_content'] );

		if ( strlen( $excerpt ) > 255 ) {
			$excerpt = substr( $excerpt, 0, 252 ) . '&hellip;';
		}

		$trackback_urls = explode( ',', $tb_list );
		foreach ( (array) $trackback_urls as $tb_url ) {
			$tb_url = trim( $tb_url );
			trackback( $tb_url, wp_unslash( $postdata['post_title'] ), $excerpt, $post_id );
		}
	}
}

//
// Các hàm trang.
//

/**
 * Lấy danh sách ID trang.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @return string[] Danh sách ID trang dưới dạng chuỗi.
 */
function get_all_page_ids() {
	global $wpdb;

	$page_ids = wp_cache_get( 'all_page_ids', 'posts' );
	if ( ! is_array( $page_ids ) ) {
		$page_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'page'" );
		wp_cache_add( 'all_page_ids', $page_ids, 'posts' );
	}

	return $page_ids;
}

/**
 * Lấy dữ liệu trang theo ID trang hoặc đối tượng trang.
 *
 * Sử dụng get_post() thay vì get_page().
 *
 * @since 1.5.1
 * @deprecated 3.5.0 Sử dụng get_post()
 *
 * @param int|WP_Post $page   Đối tượng trang hoặc ID trang. Truyền theo tham chiếu.
 * @param string      $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                            tương ứng với đối tượng WP_Post, mảng liên kết, hoặc mảng số.
 *                            Mặc định OBJECT.
 * @param string      $filter Tùy chọn. Cách lọc giá trị trả về. Chấp nhận 'raw',
 *                            'edit', 'db', 'display'. Mặc định 'raw'.
 * @return WP_Post|array|null WP_Post hoặc mảng nếu thành công, null nếu thất bại.
 */
function get_page( $page, $output = OBJECT, $filter = 'raw' ) {
	return get_post( $page, $output, $filter );
}

/**
 * Lấy trang theo đường dẫn.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string       $page_path Đường dẫn trang.
 * @param string       $output    Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                                tương ứng với đối tượng WP_Post, mảng liên kết, hoặc mảng số.
 *                                Mặc định OBJECT.
 * @param string|array $post_type Tùy chọn. Loại bài viết hoặc mảng loại bài viết. Mặc định 'page'.
 * @return WP_Post|array|null WP_Post (hoặc mảng) nếu thành công, hoặc null nếu thất bại.
 */
function get_page_by_path( $page_path, $output = OBJECT, $post_type = 'page' ) {
	global $wpdb;

	$last_changed = wp_cache_get_last_changed( 'posts' );

	$hash      = md5( $page_path . serialize( $post_type ) );
	$cache_key = "get_page_by_path:$hash:$last_changed";
	$cached    = wp_cache_get( $cache_key, 'post-queries' );
	if ( false !== $cached ) {
		// Trường hợp đặc biệt: '0' là `$page_path` không hợp lệ.
		if ( '0' === $cached || 0 === $cached ) {
			return;
		} else {
			return get_post( $cached, $output );
		}
	}

	$page_path     = rawurlencode( urldecode( $page_path ) );
	$page_path     = str_replace( '%2F', '/', $page_path );
	$page_path     = str_replace( '%20', ' ', $page_path );
	$parts         = explode( '/', trim( $page_path, '/' ) );
	$parts         = array_map( 'sanitize_title_for_query', $parts );
	$escaped_parts = esc_sql( $parts );

	$in_string = "'" . implode( "','", $escaped_parts ) . "'";

	if ( is_array( $post_type ) ) {
		$post_types = $post_type;
	} else {
		$post_types = array( $post_type, 'attachment' );
	}

	$post_types          = esc_sql( $post_types );
	$post_type_in_string = "'" . implode( "','", $post_types ) . "'";
	$sql                 = "
		SELECT ID, post_name, post_parent, post_type
		FROM $wpdb->posts
		WHERE post_name IN ($in_string)
		AND post_type IN ($post_type_in_string)
	";

	$pages = $wpdb->get_results( $sql, OBJECT_K );

	$revparts = array_reverse( $parts );

	$found_id = 0;
	foreach ( (array) $pages as $page ) {
		if ( $page->post_name === $revparts[0] ) {
			$count = 0;
			$p     = $page;

			/*
			 * Duyệt qua các phần đường dẫn đã cho từ phải sang trái,
			 * đảm bảo mỗi phần khớp với tổ tiên bài viết.
			 */
			while ( 0 !== (int) $p->post_parent && isset( $pages[ $p->post_parent ] ) ) {
				++$count;
				$parent = $pages[ $p->post_parent ];
				if ( ! isset( $revparts[ $count ] ) || $parent->post_name !== $revparts[ $count ] ) {
					break;
				}
				$p = $parent;
			}

			if ( 0 === (int) $p->post_parent
				&& count( $revparts ) === $count + 1
				&& $p->post_name === $revparts[ $count ]
			) {
				$found_id = $page->ID;
				if ( $page->post_type === $post_type ) {
					break;
				}
			}
		}
	}

	// Chúng ta cache cả kết quả không tìm thấy lẫn tìm thấy.
	wp_cache_set( $cache_key, $found_id, 'post-queries' );

	if ( $found_id ) {
		return get_post( $found_id, $output );
	}

	return null;
}

/**
 * Xác định các trang con cháu của ID trang đã cho trong danh sách đối tượng trang.
 *
 * Các trang con cháu được xác định từ mảng `$pages` truyền vào hàm. Không thực hiện truy vấn cơ sở dữ liệu.
 *
 * @since 1.5.1
 *
 * @param int       $page_id ID trang.
 * @param WP_Post[] $pages   Danh sách đối tượng trang để xác định các trang con cháu.
 * @return WP_Post[] Danh sách trang con.
 */
function get_page_children( $page_id, $pages ) {
	// Xây dựng bảng băm ID -> trang con.
	$children = array();
	foreach ( (array) $pages as $page ) {
		$children[ (int) $page->post_parent ][] = $page;
	}

	$page_list = array();

	// Bắt đầu tìm kiếm bằng cách xem các trang con trực tiếp.
	if ( isset( $children[ $page_id ] ) ) {
		// Luôn bắt đầu từ cuối ngăn xếp để bảo toàn thứ tự `$pages` gốc.
		$to_look = array_reverse( $children[ $page_id ] );

		while ( $to_look ) {
			$p           = array_pop( $to_look );
			$page_list[] = $p;
			if ( isset( $children[ $p->ID ] ) ) {
				foreach ( array_reverse( $children[ $p->ID ] ) as $child ) {
					// Thêm vào ngăn xếp `$to_look` để đi sâu xuống cây.
					$to_look[] = $child;
				}
			}
		}
	}

	return $page_list;
}

/**
 * Sắp xếp các trang với trang con dưới trang cha trong danh sách phẳng.
 *
 * Sử dụng cấu trúc phụ trợ để giữ quan hệ cha-con và
 * chạy với độ phức tạp O(N).
 *
 * @since 2.0.0
 *
 * @param WP_Post[] $pages   Mảng bài viết (truyền theo tham chiếu).
 * @param int       $page_id Tùy chọn. ID trang cha. Mặc định 0.
 * @return string[] Mảng tên bài viết đánh khóa theo ID và sắp xếp theo phân cấp. Trang con theo ngay sau trang cha.
 */
function get_page_hierarchy( &$pages, $page_id = 0 ) {
	if ( empty( $pages ) ) {
		return array();
	}

	$children = array();
	foreach ( (array) $pages as $p ) {
		$parent_id                = (int) $p->post_parent;
		$children[ $parent_id ][] = $p;
	}

	$result = array();
	_page_traverse_name( $page_id, $children, $result );

	return $result;
}

/**
 * Duyệt và trả về tất cả tên bài viết con lồng nhau của trang gốc.
 *
 * $children chứa quan hệ cha-con.
 *
 * @since 2.9.0
 * @access private
 *
 * @see _page_traverse_name()
 *
 * @param int      $page_id  ID trang.
 * @param array    $children Quan hệ cha-con (truyền theo tham chiếu).
 * @param string[] $result   Mảng tên trang đánh khóa theo ID (truyền theo tham chiếu).
 */
function _page_traverse_name( $page_id, &$children, &$result ) {
	if ( isset( $children[ $page_id ] ) ) {
		foreach ( (array) $children[ $page_id ] as $child ) {
			$result[ $child->ID ] = $child->post_name;
			_page_traverse_name( $child->ID, $children, $result );
		}
	}
}

/**
 * Xây dựng đường dẫn URI cho trang.
 *
 * Trang con sẽ nằm trong "thư mục" dưới tên bài viết trang cha.
 *
 * @since 1.5.0
 * @since 4.6.0 Tham số `$page` được đặt là tùy chọn.
 *
 * @param WP_Post|object|int $page Tùy chọn. ID trang hoặc đối tượng WP_Post. Mặc định global $post.
 * @return string|false URI trang, false nếu lỗi.
 */
function get_page_uri( $page = 0 ) {
	if ( ! $page instanceof WP_Post ) {
		$page = get_post( $page );
	}

	if ( ! $page ) {
		return false;
	}

	$uri = $page->post_name;

	foreach ( $page->ancestors as $parent ) {
		$parent = get_post( $parent );
		if ( $parent && $parent->post_name ) {
			$uri = $parent->post_name . '/' . $uri;
		}
	}

	/**
	 * Lọc URI của trang.
	 *
	 * @since 4.4.0
	 *
	 * @param string  $uri  URI trang.
	 * @param WP_Post $page Đối tượng trang.
	 */
	return apply_filters( 'get_page_uri', $uri, $page );
}

/**
 * Lấy mảng các trang (hoặc mục loại bài viết phân cấp).
 *
 * @since 1.5.0
 * @since 6.3.0 Sử dụng WP_Query nội bộ.
 *
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số để lấy trang.
 *
 *     @type int          $child_of     ID trang để trả về các trang con và cháu. Lưu ý: Giá trị
 *                                      của `$hierarchical` không ảnh hưởng đến việc `$child_of` trả về
 *                                      kết quả phân cấp hay không. Mặc định 0, hoặc không giới hạn.
 *     @type string       $sort_order   Cách sắp xếp trang đã lấy. Chấp nhận 'ASC', 'DESC'. Mặc định 'ASC'.
 *     @type string       $sort_column  Cột nào để sắp xếp trang, phân cách bằng dấu phẩy. Chấp nhận 'post_author',
 *                                      'post_date', 'post_title', 'post_name', 'post_modified', 'menu_order',
 *                                      'post_modified_gmt', 'post_parent', 'ID', 'rand', 'comment_count'.
 *                                      'post_' có thể bỏ qua cho bất kỳ giá trị nào bắt đầu bằng nó.
 *                                      Mặc định 'post_title'.
 *     @type bool         $hierarchical Có trả về trang theo phân cấp hay không. Nếu false kết hợp với
 *                                      `$child_of` cũng false, cả hai đối số sẽ bị bỏ qua.
 *                                      Mặc định true.
 *     @type int[]        $exclude      Mảng ID trang cần loại trừ. Mặc định mảng rỗng.
 *     @type int[]        $include      Mảng ID trang cần bao gồm. Không thể dùng với `$child_of`,
 *                                      `$parent`, `$exclude`, `$meta_key`, `$meta_value`, hoặc `$hierarchical`.
 *                                      Mặc định mảng rỗng.
 *     @type string       $meta_key     Chỉ bao gồm trang có meta key này. Mặc định rỗng.
 *     @type string       $meta_value   Chỉ bao gồm trang có meta value này. Yêu cầu `$meta_key`.
 *                                      Mặc định rỗng.
 *     @type string       $authors      Danh sách ID tác giả phân cách bằng dấu phẩy. Mặc định rỗng.
 *     @type int          $parent       ID trang để trả về các trang con trực tiếp. Mặc định -1, hoặc không giới hạn.
 *     @type string|int[] $exclude_tree Chuỗi phân cách bằng dấu phẩy hoặc mảng ID trang cần loại trừ.
 *                                      Mặc định mảng rỗng.
 *     @type int          $number       Số trang cần trả về. Mặc định 0, hoặc tất cả trang.
 *     @type int          $offset       Số trang cần bỏ qua trước khi trả về. Yêu cầu `$number`.
 *                                      Mặc định 0.
 *     @type string       $post_type    Loại bài viết cần truy vấn. Mặc định 'page'.
 *     @type string|array $post_status  Danh sách phân cách bằng dấu phẩy hoặc mảng trạng thái bài viết cần bao gồm.
 *                                      Mặc định 'publish'.
 * }
 * @return WP_Post[]|false Mảng các trang (hoặc mục loại bài viết phân cấp). Boolean false nếu
 *                         loại bài viết được chỉ định không phân cấp hoặc trạng thái được chỉ định không
 *                         được hỗ trợ bởi loại bài viết.
 */
function get_pages( $args = array() ) {
	$defaults = array(
		'child_of'     => 0,
		'sort_order'   => 'ASC',
		'sort_column'  => 'post_title',
		'hierarchical' => 1,
		'exclude'      => array(),
		'include'      => array(),
		'meta_key'     => '',
		'meta_value'   => '',
		'authors'      => '',
		'parent'       => -1,
		'exclude_tree' => array(),
		'number'       => '',
		'offset'       => 0,
		'post_type'    => 'page',
		'post_status'  => 'publish',
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	$number       = (int) $parsed_args['number'];
	$offset       = (int) $parsed_args['offset'];
	$child_of     = (int) $parsed_args['child_of'];
	$hierarchical = $parsed_args['hierarchical'];
	$exclude      = $parsed_args['exclude'];
	$meta_key     = $parsed_args['meta_key'];
	$meta_value   = $parsed_args['meta_value'];
	$parent       = $parsed_args['parent'];
	$post_status  = $parsed_args['post_status'];

	// Đảm bảo loại bài viết là phân cấp.
	$hierarchical_post_types = get_post_types( array( 'hierarchical' => true ) );
	if ( ! in_array( $parsed_args['post_type'], $hierarchical_post_types, true ) ) {
		return false;
	}

	if ( $parent > 0 && ! $child_of ) {
		$hierarchical = false;
	}

	// Đảm bảo có trạng thái bài viết hợp lệ.
	if ( ! is_array( $post_status ) ) {
		$post_status = explode( ',', $post_status );
	}
	if ( array_diff( $post_status, get_post_stati() ) ) {
		return false;
	}

	$query_args = array(
		'orderby'                => 'post_title',
		'order'                  => 'ASC',
		'post__not_in'           => wp_parse_id_list( $exclude ),
		'meta_key'               => $meta_key,
		'meta_value'             => $meta_value,
		'posts_per_page'         => -1,
		'offset'                 => $offset,
		'post_type'              => $parsed_args['post_type'],
		'post_status'            => $post_status,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
	);

	if ( ! empty( $parsed_args['include'] ) ) {
		$child_of = 0; // Bỏ qua các tham số child_of, parent, exclude, meta_key và meta_value khi dùng include.
		$parent   = -1;
		unset( $query_args['post__not_in'], $query_args['meta_key'], $query_args['meta_value'] );
		$hierarchical           = false;
		$query_args['post__in'] = wp_parse_id_list( $parsed_args['include'] );
	}

	if ( ! empty( $parsed_args['authors'] ) ) {
		$post_authors = wp_parse_list( $parsed_args['authors'] );

		if ( ! empty( $post_authors ) ) {
			$query_args['author__in'] = array();
			foreach ( $post_authors as $post_author ) {
				// Đây là ID tác giả hay tên đăng nhập tác giả?
				if ( 0 === (int) $post_author ) {
					$post_author = get_user_by( 'login', $post_author );
					if ( empty( $post_author ) ) {
						continue;
					}
					if ( empty( $post_author->ID ) ) {
						continue;
					}
					$post_author = $post_author->ID;
				}
				$query_args['author__in'][] = (int) $post_author;
			}
		}
	}

	if ( is_array( $parent ) ) {
		$post_parent__in = array_map( 'absint', (array) $parent );
		if ( ! empty( $post_parent__in ) ) {
			$query_args['post_parent__in'] = $post_parent__in;
		}
	} elseif ( $parent >= 0 ) {
		$query_args['post_parent'] = $parent;
	}

	/*
	 * Duy trì tương thích ngược cho khóa `sort_column`.
	 * Ngoài `WP_Query`, nó đã hỗ trợ trường `post_modified_gmt`, nên logic này sẽ chuyển đổi
	 * thành `post_modified` để cho kết quả cùng thứ tự khi hai ngày trong các trường khớp nhau.
	 */
	$orderby = wp_parse_list( $parsed_args['sort_column'] );
	$orderby = array_map(
		static function ( $orderby_field ) {
			$orderby_field = trim( $orderby_field );
			if ( 'post_modified_gmt' === $orderby_field || 'modified_gmt' === $orderby_field ) {
				$orderby_field = str_replace( '_gmt', '', $orderby_field );
			}
			return $orderby_field;
		},
		$orderby
	);
	if ( $orderby ) {
		$query_args['orderby'] = array_fill_keys( $orderby, $parsed_args['sort_order'] );
	}

	$order = $parsed_args['sort_order'];
	if ( $order ) {
		$query_args['order'] = $order;
	}

	if ( ! empty( $number ) ) {
		$query_args['posts_per_page'] = $number;
	}

	/**
	 * Lọc đối số truy vấn truyền cho WP_Query trong get_pages.
	 *
	 * @since 6.3.0
	 *
	 * @param array $query_args  Mảng đối số truyền cho WP_Query.
	 * @param array $parsed_args Mảng đối số get_pages().
	 */
	$query_args = apply_filters( 'get_pages_query_args', $query_args, $parsed_args );

	$pages = new WP_Query();
	$pages = $pages->query( $query_args );

	if ( $child_of || $hierarchical ) {
		$pages = get_page_children( $child_of, $pages );
	}

	if ( ! empty( $parsed_args['exclude_tree'] ) ) {
		$exclude = wp_parse_id_list( $parsed_args['exclude_tree'] );
		foreach ( $exclude as $id ) {
			$children = get_page_children( $id, $pages );
			foreach ( $children as $child ) {
				$exclude[] = $child->ID;
			}
		}

		$num_pages = count( $pages );
		for ( $i = 0; $i < $num_pages; $i++ ) {
			if ( in_array( $pages[ $i ]->ID, $exclude, true ) ) {
				unset( $pages[ $i ] );
			}
		}
	}

	/**
	 * Lọc danh sách trang đã lấy.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Post[] $pages       Mảng đối tượng trang.
	 * @param array     $parsed_args Mảng đối số get_pages().
	 */
	return apply_filters( 'get_pages', $pages, $parsed_args );
}

//
// Các hàm tệp đính kèm.
//

/**
 * Xác định xem URI tệp đính kèm có phải là cục bộ và thực sự là tệp đính kèm hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.0.0
 *
 * @param string $url URL cần kiểm tra.
 * @return bool True nếu thành công, false nếu thất bại.
 */
function is_local_attachment( $url ) {
	if ( ! str_contains( $url, home_url() ) ) {
		return false;
	}
	if ( str_contains( $url, home_url( '/?attachment_id=' ) ) ) {
		return true;
	}

	$id = url_to_postid( $url );
	if ( $id ) {
		$post = get_post( $id );
		if ( 'attachment' === $post->post_type ) {
			return true;
		}
	}
	return false;
}

/**
 * Chèn tệp đính kèm.
 *
 * Nếu bạn đặt 'ID' trong tham số $args, điều đó có nghĩa là bạn đang
 * cập nhật và cố gắng cập nhật tệp đính kèm. Bạn cũng có thể đặt
 * tên hoặc tiêu đề tệp đính kèm bằng cách đặt khóa 'post_name' hoặc 'post_title'.
 *
 * Bạn có thể đặt ngày cho tệp đính kèm thủ công bằng cách đặt giá trị các khóa
 * 'post_date' và 'post_date_gmt'.
 *
 * Theo mặc định, bình luận sẽ sử dụng cài đặt mặc định cho việc bình luận
 * có được cho phép hay không. Bạn có thể đóng thủ công hoặc giữ mở bằng
 * cách đặt giá trị cho khóa 'comment_status'.
 *
 * @since 2.0.0
 * @since 4.7.0 Thêm tham số `$wp_error` để cho phép trả về WP_Error khi thất bại.
 * @since 5.6.0 Thêm tham số `$fire_after_hooks`.
 *
 * @see wp_insert_post()
 *
 * @param string|array $args             Đối số để chèn tệp đính kèm.
 * @param string|false $file             Tùy chọn. Tên tệp. Mặc định false.
 * @param int          $parent_post_id   Tùy chọn. ID bài viết cha hoặc 0 nếu không có cha. Mặc định 0.
 * @param bool         $wp_error         Tùy chọn. Có trả về WP_Error khi thất bại hay không. Mặc định false.
 * @param bool         $fire_after_hooks Tùy chọn. Có kích hoạt các hook sau khi chèn hay không. Mặc định true.
 * @return int|WP_Error ID tệp đính kèm nếu thành công. Giá trị 0 hoặc WP_Error nếu thất bại.
 */
function wp_insert_attachment( $args, $file = false, $parent_post_id = 0, $wp_error = false, $fire_after_hooks = true ) {
	$defaults = array(
		'file'        => $file,
		'post_parent' => 0,
	);

	$data = wp_parse_args( $args, $defaults );

	if ( ! empty( $parent_post_id ) ) {
		$data['post_parent'] = $parent_post_id;
	}

	$data['post_type'] = 'attachment';

	return wp_insert_post( $data, $wp_error, $fire_after_hooks );
}

/**
 * Đưa vào thùng rác hoặc xóa tệp đính kèm.
 *
 * Khi tệp đính kèm bị xóa vĩnh viễn, tệp cũng sẽ bị loại bỏ.
 * Việc xóa loại bỏ tất cả trường post meta, taxonomy, bình luận, v.v. liên quan
 * đến tệp đính kèm (ngoại trừ bài viết chính).
 *
 * Tệp đính kèm được chuyển vào Thùng rác thay vì xóa vĩnh viễn trừ khi Thùng rác
 * cho phương tiện bị tắt, mục đã ở trong Thùng rác, hoặc $force_delete là true.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int  $post_id      ID tệp đính kèm.
 * @param bool $force_delete Tùy chọn. Có bỏ qua Thùng rác và buộc xóa hay không.
 *                           Mặc định false.
 * @return WP_Post|false|null Dữ liệu bài viết nếu thành công, false hoặc null nếu thất bại.
 */
function wp_delete_attachment( $post_id, $force_delete = false ) {
	global $wpdb;

	$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d", $post_id ) );

	if ( ! $post ) {
		return $post;
	}

	$post = get_post( $post );

	if ( 'attachment' !== $post->post_type ) {
		return false;
	}

	if ( ! $force_delete && EMPTY_TRASH_DAYS && MEDIA_TRASH && 'trash' !== $post->post_status ) {
		return wp_trash_post( $post_id );
	}

	/**
	 * Lọc xem việc xóa tệp đính kèm có nên được thực hiện hay không.
	 *
	 * @since 5.5.0
	 *
	 * @param WP_Post|false|null $delete       Có tiếp tục xóa hay không.
	 * @param WP_Post            $post         Đối tượng bài viết.
	 * @param bool               $force_delete Có bỏ qua Thùng rác hay không.
	 */
	$check = apply_filters( 'pre_delete_attachment', null, $post, $force_delete );
	if ( null !== $check ) {
		return $check;
	}

	delete_post_meta( $post_id, '_wp_trash_meta_status' );
	delete_post_meta( $post_id, '_wp_trash_meta_time' );

	$meta         = wp_get_attachment_metadata( $post_id );
	$backup_sizes = get_post_meta( $post->ID, '_wp_attachment_backup_sizes', true );
	$file         = get_attached_file( $post_id );

	if ( is_multisite() && is_string( $file ) && ! empty( $file ) ) {
		clean_dirsize_cache( $file );
	}

	/**
	 * Kích hoạt trước khi tệp đính kèm bị xóa, ở đầu wp_delete_attachment().
	 *
	 * @since 2.0.0
	 * @since 5.5.0 Thêm tham số `$post`.
	 *
	 * @param int     $post_id ID tệp đính kèm.
	 * @param WP_Post $post    Đối tượng bài viết.
	 */
	do_action( 'delete_attachment', $post_id, $post );

	wp_delete_object_term_relationships( $post_id, array( 'category', 'post_tag' ) );
	wp_delete_object_term_relationships( $post_id, get_object_taxonomies( $post->post_type ) );

	// Xóa tất cả cho bất kỳ bài viết nào.
	delete_metadata( 'post', null, '_thumbnail_id', $post_id, true );

	wp_defer_comment_counting( true );

	$comment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d ORDER BY comment_ID DESC", $post_id ) );
	foreach ( $comment_ids as $comment_id ) {
		wp_delete_comment( $comment_id, true );
	}

	wp_defer_comment_counting( false );

	$post_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d ", $post_id ) );
	foreach ( $post_meta_ids as $mid ) {
		delete_metadata_by_mid( 'post', $mid );
	}

	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( 'delete_post', $post_id, $post );
	$result = $wpdb->delete( $wpdb->posts, array( 'ID' => $post_id ) );
	if ( ! $result ) {
		return false;
	}
	/** Action này được ghi tài liệu trong wp-includes/post.php */
	do_action( 'deleted_post', $post_id, $post );

	wp_delete_attachment_files( $post_id, $meta, $backup_sizes, $file );

	clean_post_cache( $post );

	return $post;
}

/**
 * Deletes all files that belong to the given attachment.
 *
 * @since 4.9.7
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $post_id      Attachment ID.
 * @param array  $meta         The attachment's meta data.
 * @param array  $backup_sizes The meta data for the attachment's backup images.
 * @param string $file         Absolute path to the attachment's file.
 * @return bool True on success, false on failure.
 */
function wp_delete_attachment_files( $post_id, $meta, $backup_sizes, $file ) {
	global $wpdb;

	$uploadpath = wp_get_upload_dir();
	$deleted    = true;

	if ( ! empty( $meta['thumb'] ) ) {
		// Don't delete the thumb if another attachment uses it.
		if ( ! $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s AND post_id <> %d", '%' . $wpdb->esc_like( $meta['thumb'] ) . '%', $post_id ) ) ) {
			$thumbfile = str_replace( wp_basename( $file ), $meta['thumb'], $file );

			if ( ! empty( $thumbfile ) ) {
				$thumbfile = path_join( $uploadpath['basedir'], $thumbfile );
				$thumbdir  = path_join( $uploadpath['basedir'], dirname( $file ) );

				if ( ! wp_delete_file_from_directory( $thumbfile, $thumbdir ) ) {
					$deleted = false;
				}
			}
		}
	}

	// Remove intermediate and backup images if there are any.
	if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		$intermediate_dir = path_join( $uploadpath['basedir'], dirname( $file ) );

		foreach ( $meta['sizes'] as $size => $sizeinfo ) {
			$intermediate_file = str_replace( wp_basename( $file ), $sizeinfo['file'], $file );

			if ( ! empty( $intermediate_file ) ) {
				$intermediate_file = path_join( $uploadpath['basedir'], $intermediate_file );

				if ( ! wp_delete_file_from_directory( $intermediate_file, $intermediate_dir ) ) {
					$deleted = false;
				}
			}
		}
	}

	if ( ! empty( $meta['original_image'] ) ) {
		if ( empty( $intermediate_dir ) ) {
			$intermediate_dir = path_join( $uploadpath['basedir'], dirname( $file ) );
		}

		$original_image = str_replace( wp_basename( $file ), $meta['original_image'], $file );

		if ( ! empty( $original_image ) ) {
			$original_image = path_join( $uploadpath['basedir'], $original_image );

			if ( ! wp_delete_file_from_directory( $original_image, $intermediate_dir ) ) {
				$deleted = false;
			}
		}
	}

	if ( is_array( $backup_sizes ) ) {
		$del_dir = path_join( $uploadpath['basedir'], dirname( $meta['file'] ) );

		foreach ( $backup_sizes as $size ) {
			$del_file = path_join( dirname( $meta['file'] ), $size['file'] );

			if ( ! empty( $del_file ) ) {
				$del_file = path_join( $uploadpath['basedir'], $del_file );

				if ( ! wp_delete_file_from_directory( $del_file, $del_dir ) ) {
					$deleted = false;
				}
			}
		}
	}

	if ( ! wp_delete_file_from_directory( $file, $uploadpath['basedir'] ) ) {
		$deleted = false;
	}

	return $deleted;
}

/**
 * Retrieves attachment metadata for attachment ID.
 *
 * @since 2.1.0
 * @since 6.0.0 The `$filesize` value was added to the returned array.
 *
 * @param int  $attachment_id Attachment post ID. Defaults to global $post.
 * @param bool $unfiltered    Optional. If true, filters are not run. Default false.
 * @return array|false {
 *     Attachment metadata. False on failure.
 *
 *     @type int    $width      The width of the attachment.
 *     @type int    $height     The height of the attachment.
 *     @type string $file       The file path relative to `wp-content/uploads`.
 *     @type array  $sizes      Keys are size slugs, each value is an array containing
 *                              'file', 'width', 'height', and 'mime-type'.
 *     @type array  $image_meta Image metadata.
 *     @type int    $filesize   File size of the attachment.
 * }
 */
function wp_get_attachment_metadata( $attachment_id = 0, $unfiltered = false ) {
	$attachment_id = (int) $attachment_id;

	if ( ! $attachment_id ) {
		$post = get_post();

		if ( ! $post ) {
			return false;
		}

		$attachment_id = $post->ID;
	}

	$data = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

	if ( ! $data ) {
		return false;
	}

	if ( $unfiltered ) {
		return $data;
	}

	/**
	 * Filters the attachment meta data.
	 *
	 * @since 2.1.0
	 *
	 * @param array $data          Array of meta data for the given attachment.
	 * @param int   $attachment_id Attachment post ID.
	 */
	return apply_filters( 'wp_get_attachment_metadata', $data, $attachment_id );
}

/**
 * Updates metadata for an attachment.
 *
 * @since 2.1.0
 *
 * @param int   $attachment_id Attachment post ID.
 * @param array $data          Attachment meta data.
 * @return int|false False if $post is invalid.
 */
function wp_update_attachment_metadata( $attachment_id, $data ) {
	$attachment_id = (int) $attachment_id;

	$post = get_post( $attachment_id );

	if ( ! $post ) {
		return false;
	}

	/**
	 * Filters the updated attachment meta data.
	 *
	 * @since 2.1.0
	 *
	 * @param array $data          Array of updated attachment meta data.
	 * @param int   $attachment_id Attachment post ID.
	 */
	$data = apply_filters( 'wp_update_attachment_metadata', $data, $post->ID );
	if ( $data ) {
		return update_post_meta( $post->ID, '_wp_attachment_metadata', $data );
	} else {
		return delete_post_meta( $post->ID, '_wp_attachment_metadata' );
	}
}

/**
 * Retrieves the URL for an attachment.
 *
 * @since 2.1.0
 *
 * @global string $pagenow The filename of the current screen.
 *
 * @param int $attachment_id Optional. Attachment post ID. Defaults to global $post.
 * @return string|false Attachment URL, otherwise false.
 */
function wp_get_attachment_url( $attachment_id = 0 ) {
	global $pagenow;

	$attachment_id = (int) $attachment_id;

	$post = get_post( $attachment_id );

	if ( ! $post ) {
		return false;
	}

	if ( 'attachment' !== $post->post_type ) {
		return false;
	}

	$url = '';
	// Get attached file.
	$file = get_post_meta( $post->ID, '_wp_attached_file', true );
	if ( $file ) {
		// Get upload directory.
		$uploads = wp_get_upload_dir();
		if ( $uploads && false === $uploads['error'] ) {
			// Check that the upload base exists in the file location.
			if ( str_starts_with( $file, $uploads['basedir'] ) ) {
				// Replace file location with url location.
				$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file );
			} elseif ( str_contains( $file, 'wp-content/uploads' ) ) {
				// Get the directory name relative to the basedir (back compat for pre-2.7 uploads).
				$url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $file ) ) . wp_basename( $file );
			} else {
				// It's a newly-uploaded file, therefore $file is relative to the basedir.
				$url = $uploads['baseurl'] . "/$file";
			}
		}
	}

	/*
	 * If any of the above options failed, Fallback on the GUID as used pre-2.7,
	 * not recommended to rely upon this.
	 */
	if ( ! $url ) {
		$url = get_the_guid( $post->ID );
	}

	// On SSL front end, URLs should be HTTPS.
	if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow ) {
		$url = set_url_scheme( $url );
	}

	/**
	 * Filters the attachment URL.
	 *
	 * @since 2.1.0
	 *
	 * @param string $url           URL for the given attachment.
	 * @param int    $attachment_id Attachment post ID.
	 */
	$url = apply_filters( 'wp_get_attachment_url', $url, $post->ID );

	if ( ! $url ) {
		return false;
	}

	return $url;
}

/**
 * Retrieves the caption for an attachment.
 *
 * @since 4.6.0
 *
 * @param int $post_id Optional. Attachment ID. Default is the ID of the global `$post`.
 * @return string|false Attachment caption on success, false on failure.
 */
function wp_get_attachment_caption( $post_id = 0 ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	if ( 'attachment' !== $post->post_type ) {
		return false;
	}

	$caption = $post->post_excerpt;

	/**
	 * Filters the attachment caption.
	 *
	 * @since 4.6.0
	 *
	 * @param string $caption Caption for the given attachment.
	 * @param int    $post_id Attachment ID.
	 */
	return apply_filters( 'wp_get_attachment_caption', $caption, $post->ID );
}

/**
 * Retrieves URL for an attachment thumbnail.
 *
 * @since 2.1.0
 * @since 6.1.0 Changed to use wp_get_attachment_image_url().
 *
 * @param int $post_id Optional. Attachment ID. Default is the ID of the global `$post`.
 * @return string|false Thumbnail URL on success, false on failure.
 */
function wp_get_attachment_thumb_url( $post_id = 0 ) {
	$post_id = (int) $post_id;

	/*
	 * This uses image_downsize() which also looks for the (very) old format $image_meta['thumb']
	 * when the newer format $image_meta['sizes']['thumbnail'] doesn't exist.
	 */
	$thumbnail_url = wp_get_attachment_image_url( $post_id, 'thumbnail' );

	if ( empty( $thumbnail_url ) ) {
		return false;
	}

	/**
	 * Filters the attachment thumbnail URL.
	 *
	 * @since 2.1.0
	 *
	 * @param string $thumbnail_url URL for the attachment thumbnail.
	 * @param int    $post_id       Attachment ID.
	 */
	return apply_filters( 'wp_get_attachment_thumb_url', $thumbnail_url, $post_id );
}

/**
 * Verifies an attachment is of a given type.
 *
 * @since 4.2.0
 *
 * @param string      $type Attachment type. Accepts `image`, `audio`, `video`, or a file extension.
 * @param int|WP_Post $post Optional. Attachment ID or object. Default is global $post.
 * @return bool True if an accepted type or a matching file extension, false otherwise.
 */
function wp_attachment_is( $type, $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$file = get_attached_file( $post->ID );

	if ( ! $file ) {
		return false;
	}

	if ( str_starts_with( $post->post_mime_type, $type . '/' ) ) {
		return true;
	}

	$check = wp_check_filetype( $file );

	if ( empty( $check['ext'] ) ) {
		return false;
	}

	$ext = $check['ext'];

	if ( 'import' !== $post->post_mime_type ) {
		return $type === $ext;
	}

	switch ( $type ) {
		case 'image':
			$image_exts = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'webp', 'avif', 'heic' );
			return in_array( $ext, $image_exts, true );

		case 'audio':
			return in_array( $ext, wp_get_audio_extensions(), true );

		case 'video':
			return in_array( $ext, wp_get_video_extensions(), true );

		default:
			return $type === $ext;
	}
}

/**
 * Determines whether an attachment is an image.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.1.0
 * @since 4.2.0 Modified into wrapper for wp_attachment_is() and
 *              allowed WP_Post object to be passed.
 *
 * @param int|WP_Post $post Optional. Attachment ID or object. Default is global $post.
 * @return bool Whether the attachment is an image.
 */
function wp_attachment_is_image( $post = null ) {
	return wp_attachment_is( 'image', $post );
}

/**
 * Retrieves the icon for a MIME type or attachment.
 *
 * @since 2.1.0
 * @since 6.5.0 Added the `$preferred_ext` parameter.
 *
 * @param string|int $mime          MIME type or attachment ID.
 * @param string     $preferred_ext File format to prefer in return. Default '.png'.
 * @return string|false Icon, false otherwise.
 */
function wp_mime_type_icon( $mime = 0, $preferred_ext = '.png' ) {
	if ( ! is_numeric( $mime ) ) {
		$icon = wp_cache_get( "mime_type_icon_$mime" );
	}

	// Check if preferred file format variable is present and is a validly formatted file extension.
	if ( ! empty( $preferred_ext ) && is_string( $preferred_ext ) && ! str_starts_with( $preferred_ext, '.' ) ) {
		$preferred_ext = '.' . strtolower( $preferred_ext );
	}

	$post_id = 0;
	if ( empty( $icon ) ) {
		$post_mimes = array();
		if ( is_numeric( $mime ) ) {
			$mime = (int) $mime;
			$post = get_post( $mime );
			if ( $post ) {
				$post_id = (int) $post->ID;
				$file    = get_attached_file( $post_id );
				$ext     = preg_replace( '/^.+?\.([^.]+)$/', '$1', $file );
				if ( ! empty( $ext ) ) {
					$post_mimes[] = $ext;
					$ext_type     = wp_ext2type( $ext );
					if ( $ext_type ) {
						$post_mimes[] = $ext_type;
					}
				}
				$mime = $post->post_mime_type;
			} else {
				$mime = 0;
			}
		} else {
			$post_mimes[] = $mime;
		}

		$icon_files = wp_cache_get( 'icon_files' );

		if ( ! is_array( $icon_files ) ) {
			/**
			 * Filters the icon directory path.
			 *
			 * @since 2.0.0
			 *
			 * @param string $path Icon directory absolute path.
			 */
			$icon_dir = apply_filters( 'icon_dir', ABSPATH . WPINC . '/images/media' );

			/**
			 * Filters the icon directory URI.
			 *
			 * @since 2.0.0
			 *
			 * @param string $uri Icon directory URI.
			 */
			$icon_dir_uri = apply_filters( 'icon_dir_uri', includes_url( 'images/media' ) );

			/**
			 * Filters the array of icon directory URIs.
			 *
			 * @since 2.5.0
			 *
			 * @param string[] $uris Array of icon directory URIs keyed by directory absolute path.
			 */
			$dirs       = apply_filters( 'icon_dirs', array( $icon_dir => $icon_dir_uri ) );
			$icon_files = array();
			$all_icons  = array();
			while ( $dirs ) {
				$keys = array_keys( $dirs );
				$dir  = array_shift( $keys );
				$uri  = array_shift( $dirs );
				$dh   = opendir( $dir );
				if ( $dh ) {
					while ( false !== $file = readdir( $dh ) ) {
						$file = wp_basename( $file );
						if ( str_starts_with( $file, '.' ) ) {
							continue;
						}

						$ext = strtolower( substr( $file, -4 ) );
						if ( ! in_array( $ext, array( '.svg', '.png', '.gif', '.jpg' ), true ) ) {
							if ( is_dir( "$dir/$file" ) ) {
								$dirs[ "$dir/$file" ] = "$uri/$file";
							}
							continue;
						}
						$all_icons[ "$dir/$file" ] = "$uri/$file";
						if ( $ext === $preferred_ext ) {
							$icon_files[ "$dir/$file" ] = "$uri/$file";
						}
					}
					closedir( $dh );
				}
			}
			// If directory only contained icons of a non-preferred format, return those.
			if ( empty( $icon_files ) ) {
				$icon_files = $all_icons;
			}
			wp_cache_add( 'icon_files', $icon_files, 'default', 600 );
		}

		$types = array();
		// Icon wp_basename - extension = MIME wildcard.
		foreach ( $icon_files as $file => $uri ) {
			$types[ preg_replace( '/^([^.]*).*$/', '$1', wp_basename( $file ) ) ] =& $icon_files[ $file ];
		}

		if ( ! empty( $mime ) ) {
			$post_mimes[] = substr( $mime, 0, strpos( $mime, '/' ) );
			$post_mimes[] = substr( $mime, strpos( $mime, '/' ) + 1 );
			$post_mimes[] = str_replace( '/', '_', $mime );
		}

		$matches            = wp_match_mime_types( array_keys( $types ), $post_mimes );
		$matches['default'] = array( 'default' );

		foreach ( $matches as $match => $wilds ) {
			foreach ( $wilds as $wild ) {
				if ( ! isset( $types[ $wild ] ) ) {
					continue;
				}

				$icon = $types[ $wild ];
				if ( ! is_numeric( $mime ) ) {
					wp_cache_add( "mime_type_icon_$mime", $icon );
				}
				break 2;
			}
		}
	}

	/**
	 * Filters the mime type icon.
	 *
	 * @since 2.1.0
	 *
	 * @param string $icon    Path to the mime type icon.
	 * @param string $mime    Mime type.
	 * @param int    $post_id Attachment ID. Will equal 0 if the function passed
	 *                        the mime type.
	 */
	return apply_filters( 'wp_mime_type_icon', $icon, $mime, $post_id );
}

/**
 * Checks for changed slugs for published post objects and save the old slug.
 *
 * The function is used when a post object of any type is updated,
 * by comparing the current and previous post objects.
 *
 * If the slug was changed and not already part of the old slugs then it will be
 * added to the post meta field ('_wp_old_slug') for storing old slugs for that
 * post.
 *
 * The most logically usage of this function is redirecting changed post objects, so
 * that those that linked to an changed post will be redirected to the new post.
 *
 * @since 2.1.0
 *
 * @param int     $post_id     Post ID.
 * @param WP_Post $post        The post object.
 * @param WP_Post $post_before The previous post object.
 */
function wp_check_for_changed_slugs( $post_id, $post, $post_before ) {
	// Don't bother if it hasn't changed.
	if ( $post->post_name === $post_before->post_name ) {
		return;
	}

	// We're only concerned with published, non-hierarchical objects.
	if ( ! ( 'publish' === $post->post_status || ( 'attachment' === $post->post_type && 'inherit' === $post->post_status ) )
		|| is_post_type_hierarchical( $post->post_type )
	) {
		return;
	}

	$old_slugs = (array) get_post_meta( $post_id, '_wp_old_slug' );

	// If we haven't added this old slug before, add it now.
	if ( ! empty( $post_before->post_name ) && ! in_array( $post_before->post_name, $old_slugs, true ) ) {
		add_post_meta( $post_id, '_wp_old_slug', $post_before->post_name );
	}

	// If the new slug was used previously, delete it from the list.
	if ( in_array( $post->post_name, $old_slugs, true ) ) {
		delete_post_meta( $post_id, '_wp_old_slug', $post->post_name );
	}
}

/**
 * Checks for changed dates for published post objects and save the old date.
 *
 * The function is used when a post object of any type is updated,
 * by comparing the current and previous post objects.
 *
 * If the date was changed and not already part of the old dates then it will be
 * added to the post meta field ('_wp_old_date') for storing old dates for that
 * post.
 *
 * The most logically usage of this function is redirecting changed post objects, so
 * that those that linked to an changed post will be redirected to the new post.
 *
 * @since 4.9.3
 *
 * @param int     $post_id     Post ID.
 * @param WP_Post $post        The post object.
 * @param WP_Post $post_before The previous post object.
 */
function wp_check_for_changed_dates( $post_id, $post, $post_before ) {
	$previous_date = gmdate( 'Y-m-d', strtotime( $post_before->post_date ) );
	$new_date      = gmdate( 'Y-m-d', strtotime( $post->post_date ) );

	// Don't bother if it hasn't changed.
	if ( $new_date === $previous_date ) {
		return;
	}

	// We're only concerned with published, non-hierarchical objects.
	if ( ! ( 'publish' === $post->post_status || ( 'attachment' === $post->post_type && 'inherit' === $post->post_status ) )
		|| is_post_type_hierarchical( $post->post_type )
	) {
		return;
	}

	$old_dates = (array) get_post_meta( $post_id, '_wp_old_date' );

	// If we haven't added this old date before, add it now.
	if ( ! empty( $previous_date ) && ! in_array( $previous_date, $old_dates, true ) ) {
		add_post_meta( $post_id, '_wp_old_date', $previous_date );
	}

	// If the new slug was used previously, delete it from the list.
	if ( in_array( $new_date, $old_dates, true ) ) {
		delete_post_meta( $post_id, '_wp_old_date', $new_date );
	}
}

/**
 * Retrieves the private post SQL based on capability.
 *
 * This function provides a standardized way to appropriately select on the
 * post_status of a post type. The function will return a piece of SQL code
 * that can be added to a WHERE clause; this SQL is constructed to allow all
 * published posts, and all private posts to which the user has access.
 *
 * @since 2.2.0
 * @since 4.3.0 Added the ability to pass an array to `$post_type`.
 *
 * @param string|array $post_type Single post type or an array of post types. Currently only supports 'post' or 'page'.
 * @return string SQL code that can be added to a where clause.
 */
function get_private_posts_cap_sql( $post_type ) {
	return get_posts_by_author_sql( $post_type, false );
}

/**
 * Retrieves the post SQL based on capability, author, and type.
 *
 * @since 3.0.0
 * @since 4.3.0 Introduced the ability to pass an array of post types to `$post_type`.
 *
 * @see get_private_posts_cap_sql()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string|string[] $post_type   Single post type or an array of post types.
 * @param bool            $full        Optional. Returns a full WHERE statement instead of just
 *                                     an 'andalso' term. Default true.
 * @param int             $post_author Optional. Query posts having a single author ID. Default null.
 * @param bool            $public_only Optional. Only return public posts. Skips cap checks for
 *                                     $current_user.  Default false.
 * @return string SQL WHERE code that can be added to a query.
 */
function get_posts_by_author_sql( $post_type, $full = true, $post_author = null, $public_only = false ) {
	global $wpdb;

	if ( is_array( $post_type ) ) {
		$post_types = $post_type;
	} else {
		$post_types = array( $post_type );
	}

	$post_type_clauses = array();
	foreach ( $post_types as $post_type ) {
		$post_type_obj = get_post_type_object( $post_type );

		if ( ! $post_type_obj ) {
			continue;
		}

		/**
		 * Filters the capability to read private posts for a custom post type
		 * when generating SQL for getting posts by author.
		 *
		 * @since 2.2.0
		 * @deprecated 3.2.0 The hook transitioned from "somewhat useless" to "totally useless".
		 *
		 * @param string $cap Capability.
		 */
		$cap = apply_filters_deprecated( 'pub_priv_sql_capability', array( '' ), '3.2.0' );

		if ( ! $cap ) {
			$cap = current_user_can( $post_type_obj->cap->read_private_posts );
		}

		// Only need to check the cap if $public_only is false.
		$post_status_sql = "post_status = 'publish'";

		if ( false === $public_only ) {
			if ( $cap ) {
				// Does the user have the capability to view private posts? Guess so.
				$post_status_sql .= " OR post_status = 'private'";
			} elseif ( is_user_logged_in() ) {
				// Users can view their own private posts.
				$id = get_current_user_id();
				if ( null === $post_author || ! $full ) {
					$post_status_sql .= " OR post_status = 'private' AND post_author = $id";
				} elseif ( $id === (int) $post_author ) {
					$post_status_sql .= " OR post_status = 'private'";
				} // Else none.
			} // Else none.
		}

		$post_type_clauses[] = "( post_type = '" . $post_type . "' AND ( $post_status_sql ) )";
	}

	if ( empty( $post_type_clauses ) ) {
		return $full ? 'WHERE 1 = 0' : '1 = 0';
	}

	$sql = '( ' . implode( ' OR ', $post_type_clauses ) . ' )';

	if ( null !== $post_author ) {
		$sql .= $wpdb->prepare( ' AND post_author = %d', $post_author );
	}

	if ( $full ) {
		$sql = 'WHERE ' . $sql;
	}

	return $sql;
}

/**
 * Retrieves the most recent time that a post on the site was published.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is the date when the last post was posted.
 * The 'gmt' is when the last post was posted in GMT formatted date.
 *
 * @since 0.71
 * @since 4.4.0 The `$post_type` argument was added.
 *
 * @param string $timezone  Optional. The timezone for the timestamp. Accepts 'server', 'blog', or 'gmt'.
 *                          'server' uses the server's internal timezone.
 *                          'blog' uses the `post_date` field, which proxies to the timezone set for the site.
 *                          'gmt' uses the `post_date_gmt` field.
 *                          Default 'server'.
 * @param string $post_type Optional. The post type to check. Default 'any'.
 * @return string The date of the last post, or false on failure.
 */
function get_lastpostdate( $timezone = 'server', $post_type = 'any' ) {
	$lastpostdate = _get_last_post_time( $timezone, 'date', $post_type );

	/**
	 * Filters the most recent time that a post on the site was published.
	 *
	 * @since 2.3.0
	 * @since 5.5.0 Added the `$post_type` parameter.
	 *
	 * @param string|false $lastpostdate The most recent time that a post was published,
	 *                                   in 'Y-m-d H:i:s' format. False on failure.
	 * @param string       $timezone     Location to use for getting the post published date.
	 *                                   See get_lastpostdate() for accepted `$timezone` values.
	 * @param string       $post_type    The post type to check.
	 */
	return apply_filters( 'get_lastpostdate', $lastpostdate, $timezone, $post_type );
}

/**
 * Gets the most recent time that a post on the site was modified.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is just when the last post was modified.
 * The 'gmt' is when the last post was modified in GMT time.
 *
 * @since 1.2.0
 * @since 4.4.0 The `$post_type` argument was added.
 *
 * @param string $timezone  Optional. The timezone for the timestamp. See get_lastpostdate()
 *                          for information on accepted values.
 *                          Default 'server'.
 * @param string $post_type Optional. The post type to check. Default 'any'.
 * @return string The timestamp in 'Y-m-d H:i:s' format, or false on failure.
 */
function get_lastpostmodified( $timezone = 'server', $post_type = 'any' ) {
	/**
	 * Pre-filter the return value of get_lastpostmodified() before the query is run.
	 *
	 * @since 4.4.0
	 *
	 * @param string|false $lastpostmodified The most recent time that a post was modified,
	 *                                       in 'Y-m-d H:i:s' format, or false. Returning anything
	 *                                       other than false will short-circuit the function.
	 * @param string       $timezone         Location to use for getting the post modified date.
	 *                                       See get_lastpostdate() for accepted `$timezone` values.
	 * @param string       $post_type        The post type to check.
	 */
	$lastpostmodified = apply_filters( 'pre_get_lastpostmodified', false, $timezone, $post_type );

	if ( false !== $lastpostmodified ) {
		return $lastpostmodified;
	}

	$lastpostmodified = _get_last_post_time( $timezone, 'modified', $post_type );
	$lastpostdate     = get_lastpostdate( $timezone, $post_type );

	if ( $lastpostdate > $lastpostmodified ) {
		$lastpostmodified = $lastpostdate;
	}

	/**
	 * Filters the most recent time that a post on the site was modified.
	 *
	 * @since 2.3.0
	 * @since 5.5.0 Added the `$post_type` parameter.
	 *
	 * @param string|false $lastpostmodified The most recent time that a post was modified,
	 *                                       in 'Y-m-d H:i:s' format. False on failure.
	 * @param string       $timezone         Location to use for getting the post modified date.
	 *                                       See get_lastpostdate() for accepted `$timezone` values.
	 * @param string       $post_type        The post type to check.
	 */
	return apply_filters( 'get_lastpostmodified', $lastpostmodified, $timezone, $post_type );
}

/**
 * Gets the timestamp of the last time any post was modified or published.
 *
 * @since 3.1.0
 * @since 4.4.0 The `$post_type` argument was added.
 * @access private
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $timezone  The timezone for the timestamp. See get_lastpostdate().
 *                          for information on accepted values.
 * @param string $field     Post field to check. Accepts 'date' or 'modified'.
 * @param string $post_type Optional. The post type to check. Default 'any'.
 * @return string|false The timestamp in 'Y-m-d H:i:s' format, or false on failure.
 */
function _get_last_post_time( $timezone, $field, $post_type = 'any' ) {
	global $wpdb;

	if ( ! in_array( $field, array( 'date', 'modified' ), true ) ) {
		return false;
	}

	$timezone = strtolower( $timezone );

	$key = "lastpost{$field}:$timezone";
	if ( 'any' !== $post_type ) {
		$key .= ':' . sanitize_key( $post_type );
	}

	$date = wp_cache_get( $key, 'timeinfo' );
	if ( false !== $date ) {
		return $date;
	}

	if ( 'any' === $post_type ) {
		$post_types = get_post_types( array( 'public' => true ) );
		array_walk( $post_types, array( $wpdb, 'escape_by_ref' ) );
		$post_types = "'" . implode( "', '", $post_types ) . "'";
	} else {
		$post_types = "'" . sanitize_key( $post_type ) . "'";
	}

	switch ( $timezone ) {
		case 'gmt':
			$date = $wpdb->get_var( "SELECT post_{$field}_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ({$post_types}) ORDER BY post_{$field}_gmt DESC LIMIT 1" );
			break;
		case 'blog':
			$date = $wpdb->get_var( "SELECT post_{$field} FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ({$post_types}) ORDER BY post_{$field}_gmt DESC LIMIT 1" );
			break;
		case 'server':
			$add_seconds_server = gmdate( 'Z' );
			$date               = $wpdb->get_var( "SELECT DATE_ADD(post_{$field}_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ({$post_types}) ORDER BY post_{$field}_gmt DESC LIMIT 1" );
			break;
	}

	if ( $date ) {
		wp_cache_set( $key, $date, 'timeinfo' );

		return $date;
	}

	return false;
}

/**
 * Updates posts in cache.
 *
 * @since 1.5.1
 *
 * @param WP_Post[] $posts Array of post objects (passed by reference).
 */
function update_post_cache( &$posts ) {
	if ( ! $posts ) {
		return;
	}

	$data = array();
	foreach ( $posts as $post ) {
		if ( empty( $post->filter ) || 'raw' !== $post->filter ) {
			$post = sanitize_post( $post, 'raw' );
		}
		$data[ $post->ID ] = $post;
	}
	wp_cache_add_multiple( $data, 'posts' );
}

/**
 * Will clean the post in the cache.
 *
 * Cleaning means delete from the cache of the post. Will call to clean the term
 * object cache associated with the post ID.
 *
 * This function not run if $_wp_suspend_cache_invalidation is not empty. See
 * wp_suspend_cache_invalidation().
 *
 * @since 2.0.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|WP_Post $post Post ID or post object to remove from the cache.
 */
function clean_post_cache( $post ) {
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	wp_cache_delete( $post->ID, 'posts' );
	wp_cache_delete( 'post_parent:' . (string) $post->ID, 'posts' );
	wp_cache_delete( $post->ID, 'post_meta' );

	clean_object_term_cache( $post->ID, $post->post_type );

	wp_cache_delete( 'wp_get_archives', 'general' );

	/**
	 * Fires immediately after the given post's cache is cleaned.
	 *
	 * @since 2.5.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	do_action( 'clean_post_cache', $post->ID, $post );

	if ( 'page' === $post->post_type ) {
		wp_cache_delete( 'all_page_ids', 'posts' );

		/**
		 * Fires immediately after the given page's cache is cleaned.
		 *
		 * @since 2.5.0
		 *
		 * @param int $post_id Post ID.
		 */
		do_action( 'clean_page_cache', $post->ID );
	}

	wp_cache_set_posts_last_changed();
}

/**
 * Updates post, term, and metadata caches for a list of post objects.
 *
 * @since 1.5.0
 *
 * @param WP_Post[] $posts             Array of post objects (passed by reference).
 * @param string    $post_type         Optional. Post type. Default 'post'.
 * @param bool      $update_term_cache Optional. Whether to update the term cache. Default true.
 * @param bool      $update_meta_cache Optional. Whether to update the meta cache. Default true.
 */
function update_post_caches( &$posts, $post_type = 'post', $update_term_cache = true, $update_meta_cache = true ) {
	// No point in doing all this work if we didn't match any posts.
	if ( ! $posts ) {
		return;
	}

	update_post_cache( $posts );

	$post_ids = array();
	foreach ( $posts as $post ) {
		$post_ids[] = $post->ID;
	}

	if ( ! $post_type ) {
		$post_type = 'any';
	}

	if ( $update_term_cache ) {
		if ( is_array( $post_type ) ) {
			$ptypes = $post_type;
		} elseif ( 'any' === $post_type ) {
			$ptypes = array();
			// Just use the post_types in the supplied posts.
			foreach ( $posts as $post ) {
				$ptypes[] = $post->post_type;
			}
			$ptypes = array_unique( $ptypes );
		} else {
			$ptypes = array( $post_type );
		}

		if ( ! empty( $ptypes ) ) {
			update_object_term_cache( $post_ids, $ptypes );
		}
	}

	if ( $update_meta_cache ) {
		update_postmeta_cache( $post_ids );
	}
}

/**
 * Updates post author user caches for a list of post objects.
 *
 * @since 6.1.0
 *
 * @param WP_Post[] $posts Array of post objects.
 */
function update_post_author_caches( $posts ) {
	/*
	 * cache_users() is a pluggable function so is not available prior
	 * to the `plugins_loaded` hook firing. This is to ensure against
	 * fatal errors when the function is not available.
	 */
	if ( ! function_exists( 'cache_users' ) ) {
		return;
	}

	$author_ids = wp_list_pluck( $posts, 'post_author' );
	$author_ids = array_map( 'absint', $author_ids );
	$author_ids = array_unique( array_filter( $author_ids ) );

	cache_users( $author_ids );
}

/**
 * Updates parent post caches for a list of post objects.
 *
 * @since 6.1.0
 *
 * @param WP_Post[] $posts Array of post objects.
 */
function update_post_parent_caches( $posts ) {
	$parent_ids = wp_list_pluck( $posts, 'post_parent' );
	$parent_ids = array_map( 'absint', $parent_ids );
	$parent_ids = array_unique( array_filter( $parent_ids ) );

	if ( ! empty( $parent_ids ) ) {
		_prime_post_caches( $parent_ids, false );
	}
}

/**
 * Updates metadata cache for a list of post IDs.
 *
 * Performs SQL query to retrieve the metadata for the post IDs and updates the
 * metadata cache for the posts. Therefore, the functions, which call this
 * function, do not need to perform SQL queries on their own.
 *
 * @since 2.1.0
 *
 * @param int[] $post_ids Array of post IDs.
 * @return array|false An array of metadata on success, false if there is nothing to update.
 */
function update_postmeta_cache( $post_ids ) {
	return update_meta_cache( 'post', $post_ids );
}

/**
 * Will clean the attachment in the cache.
 *
 * Cleaning means delete from the cache. Optionally will clean the term
 * object cache associated with the attachment ID.
 *
 * This function will not run if $_wp_suspend_cache_invalidation is not empty.
 *
 * @since 3.0.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int  $id          The attachment ID in the cache to clean.
 * @param bool $clean_terms Optional. Whether to clean terms cache. Default false.
 */
function clean_attachment_cache( $id, $clean_terms = false ) {
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	$id = (int) $id;

	wp_cache_delete( $id, 'posts' );
	wp_cache_delete( $id, 'post_meta' );

	if ( $clean_terms ) {
		clean_object_term_cache( $id, 'attachment' );
	}

	/**
	 * Fires after the given attachment's cache is cleaned.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id Attachment ID.
	 */
	do_action( 'clean_attachment_cache', $id );
}

//
// Hooks.
//

/**
 * Hook for managing future post transitions to published.
 *
 * @since 2.3.0
 * @access private
 *
 * @see wp_clear_scheduled_hook()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Previous post status.
 * @param WP_Post $post       Post object.
 */
function _transition_post_status( $new_status, $old_status, $post ) {
	global $wpdb;

	if ( 'publish' !== $old_status && 'publish' === $new_status ) {
		// Reset GUID if transitioning to publish and it is empty.
		if ( '' === get_the_guid( $post->ID ) ) {
			$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post->ID ) ), array( 'ID' => $post->ID ) );
		}

		/**
		 * Fires when a post's status is transitioned from private to published.
		 *
		 * @since 1.5.0
		 * @deprecated 2.3.0 Use {@see 'private_to_publish'} instead.
		 *
		 * @param int $post_id Post ID.
		 */
		do_action_deprecated( 'private_to_published', array( $post->ID ), '2.3.0', 'private_to_publish' );
	}

	// If published posts changed clear the lastpostmodified cache.
	if ( 'publish' === $new_status || 'publish' === $old_status ) {
		foreach ( array( 'server', 'gmt', 'blog' ) as $timezone ) {
			wp_cache_delete( "lastpostmodified:$timezone", 'timeinfo' );
			wp_cache_delete( "lastpostdate:$timezone", 'timeinfo' );
			wp_cache_delete( "lastpostdate:$timezone:{$post->post_type}", 'timeinfo' );
		}
	}

	if ( $new_status !== $old_status ) {
		wp_cache_delete( _count_posts_cache_key( $post->post_type ), 'counts' );
		wp_cache_delete( _count_posts_cache_key( $post->post_type, 'readable' ), 'counts' );
	}

	// Always clears the hook in case the post status bounced from future to draft.
	wp_clear_scheduled_hook( 'publish_future_post', array( $post->ID ) );
}

/**
 * Hook used to schedule publication for a post marked for the future.
 *
 * The $post properties used and must exist are 'ID' and 'post_date_gmt'.
 *
 * @since 2.3.0
 * @access private
 *
 * @param int     $deprecated Not used. Can be set to null. Never implemented. Not marked
 *                            as deprecated with _deprecated_argument() as it conflicts with
 *                            wp_transition_post_status() and the default filter for _future_post_hook().
 * @param WP_Post $post       Post object.
 */
function _future_post_hook( $deprecated, $post ) {
	wp_clear_scheduled_hook( 'publish_future_post', array( $post->ID ) );
	wp_schedule_single_event( strtotime( get_gmt_from_date( $post->post_date ) . ' GMT' ), 'publish_future_post', array( $post->ID ) );
}

/**
 * Hook to schedule pings and enclosures when a post is published.
 *
 * Uses XMLRPC_REQUEST and WP_IMPORTING constants.
 *
 * @since 2.3.0
 * @access private
 *
 * @param int $post_id The ID of the post being published.
 */
function _publish_post_hook( $post_id ) {
	if ( defined( 'XMLRPC_REQUEST' ) ) {
		/**
		 * Fires when _publish_post_hook() is called during an XML-RPC request.
		 *
		 * @since 2.1.0
		 *
		 * @param int $post_id Post ID.
		 */
		do_action( 'xmlrpc_publish_post', $post_id );
	}

	if ( defined( 'WP_IMPORTING' ) ) {
		return;
	}

	if ( get_option( 'default_pingback_flag' ) ) {
		add_post_meta( $post_id, '_pingme', '1', true );
	}
	add_post_meta( $post_id, '_encloseme', '1', true );

	$to_ping = get_to_ping( $post_id );
	if ( ! empty( $to_ping ) ) {
		add_post_meta( $post_id, '_trackbackme', '1' );
	}

	if ( ! wp_next_scheduled( 'do_pings' ) ) {
		wp_schedule_single_event( time(), 'do_pings' );
	}
}

/**
 * Returns the ID of the post's parent.
 *
 * @since 3.1.0
 * @since 5.9.0 The `$post` parameter was made optional.
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return int|false Post parent ID (which can be 0 if there is no parent),
 *                   or false if the post does not exist.
 */
function wp_get_post_parent_id( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || is_wp_error( $post ) ) {
		return false;
	}

	return (int) $post->post_parent;
}

/**
 * Checks the given subset of the post hierarchy for hierarchy loops.
 *
 * Prevents loops from forming and breaks those that it finds. Attached
 * to the {@see 'wp_insert_post_parent'} filter.
 *
 * @since 3.1.0
 *
 * @see wp_find_hierarchy_loop()
 *
 * @param int $post_parent ID of the parent for the post we're checking.
 * @param int $post_id     ID of the post we're checking.
 * @return int The new post_parent for the post, 0 otherwise.
 */
function wp_check_post_hierarchy_for_loops( $post_parent, $post_id ) {
	// Nothing fancy here - bail.
	if ( ! $post_parent ) {
		return 0;
	}

	// New post can't cause a loop.
	if ( ! $post_id ) {
		return $post_parent;
	}

	// Can't be its own parent.
	if ( $post_parent === $post_id ) {
		return 0;
	}

	// Now look for larger loops.
	$loop = wp_find_hierarchy_loop( 'wp_get_post_parent_id', $post_id, $post_parent );
	if ( ! $loop ) {
		return $post_parent; // No loop.
	}

	// Setting $post_parent to the given value causes a loop.
	if ( isset( $loop[ $post_id ] ) ) {
		return 0;
	}

	// There's a loop, but it doesn't contain $post_id. Break the loop.
	foreach ( array_keys( $loop ) as $loop_member ) {
		wp_update_post(
			array(
				'ID'          => $loop_member,
				'post_parent' => 0,
			)
		);
	}

	return $post_parent;
}

/**
 * Sets the post thumbnail (featured image) for the given post.
 *
 * @since 3.1.0
 *
 * @param int|WP_Post $post         Post ID or post object where thumbnail should be attached.
 * @param int         $thumbnail_id Thumbnail to attach.
 * @return int|bool Post meta ID if the key didn't exist (ie. this is the first time that
 *                  a thumbnail has been saved for the post), true on successful update,
 *                  false on failure or if the value passed is the same as the one that
 *                  is already in the database.
 */
function set_post_thumbnail( $post, $thumbnail_id ) {
	$post         = get_post( $post );
	$thumbnail_id = absint( $thumbnail_id );
	if ( $post && $thumbnail_id && get_post( $thumbnail_id ) ) {
		if ( wp_get_attachment_image( $thumbnail_id, 'thumbnail' ) ) {
			return update_post_meta( $post->ID, '_thumbnail_id', $thumbnail_id );
		} else {
			return delete_post_meta( $post->ID, '_thumbnail_id' );
		}
	}
	return false;
}

/**
 * Removes the thumbnail (featured image) from the given post.
 *
 * @since 3.3.0
 *
 * @param int|WP_Post $post Post ID or post object from which the thumbnail should be removed.
 * @return bool True on success, false on failure.
 */
function delete_post_thumbnail( $post ) {
	$post = get_post( $post );
	if ( $post ) {
		return delete_post_meta( $post->ID, '_thumbnail_id' );
	}
	return false;
}

/**
 * Deletes auto-drafts for new posts that are > 7 days old.
 *
 * @since 3.4.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function wp_delete_auto_drafts() {
	global $wpdb;

	// Cleanup old auto-drafts more than 7 days old.
	$old_posts = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status = 'auto-draft' AND DATE_SUB( NOW(), INTERVAL 7 DAY ) > post_date" );
	foreach ( (array) $old_posts as $delete ) {
		// Force delete.
		wp_delete_post( $delete, true );
	}
}

/**
 * Queues posts for lazy-loading of term meta.
 *
 * @since 4.5.0
 *
 * @param WP_Post[] $posts Array of WP_Post objects.
 */
function wp_queue_posts_for_term_meta_lazyload( $posts ) {
	$post_type_taxonomies = array();
	$prime_post_terms     = array();
	foreach ( $posts as $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			continue;
		}

		if ( ! isset( $post_type_taxonomies[ $post->post_type ] ) ) {
			$post_type_taxonomies[ $post->post_type ] = get_object_taxonomies( $post->post_type );
		}

		foreach ( $post_type_taxonomies[ $post->post_type ] as $taxonomy ) {
			$prime_post_terms[ $taxonomy ][] = $post->ID;
		}
	}

	$term_ids = array();
	if ( $prime_post_terms ) {
		foreach ( $prime_post_terms as $taxonomy => $post_ids ) {
			$cached_term_ids = wp_cache_get_multiple( $post_ids, "{$taxonomy}_relationships" );
			if ( is_array( $cached_term_ids ) ) {
				$cached_term_ids = array_filter( $cached_term_ids );
				foreach ( $cached_term_ids as $_term_ids ) {
					// Backward compatibility for if a plugin is putting objects into the cache, rather than IDs.
					foreach ( $_term_ids as $term_id ) {
						if ( is_numeric( $term_id ) ) {
							$term_ids[] = (int) $term_id;
						} elseif ( isset( $term_id->term_id ) ) {
							$term_ids[] = (int) $term_id->term_id;
						}
					}
				}
			}
		}
		$term_ids = array_unique( $term_ids );
	}

	wp_lazyload_term_meta( $term_ids );
}

/**
 * Updates the custom taxonomies' term counts when a post's status is changed.
 *
 * For example, default posts term counts (for custom taxonomies) don't include
 * private / draft posts.
 *
 * @since 3.3.0
 * @access private
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function _update_term_count_on_transition_post_status( $new_status, $old_status, $post ) {
	// Update counts for the post's terms.
	foreach ( (array) get_object_taxonomies( $post->post_type ) as $taxonomy ) {
		$tt_ids = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'tt_ids' ) );
		wp_update_term_count( $tt_ids, $taxonomy );
	}
}

/**
 * Adds any posts from the given IDs to the cache that do not already exist in cache.
 *
 * @since 3.4.0
 * @since 6.1.0 This function is no longer marked as "private".
 *
 * @see update_post_cache()
 * @see update_postmeta_cache()
 * @see update_object_term_cache()
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int[] $ids               ID list.
 * @param bool  $update_term_cache Optional. Whether to update the term cache. Default true.
 * @param bool  $update_meta_cache Optional. Whether to update the meta cache. Default true.
 */
function _prime_post_caches( $ids, $update_term_cache = true, $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'posts' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_posts = $wpdb->get_results( sprintf( "SELECT $wpdb->posts.* FROM $wpdb->posts WHERE ID IN (%s)", implode( ',', $non_cached_ids ) ) );

		if ( $fresh_posts ) {
			// Despite the name, update_post_cache() expects an array rather than a single post.
			update_post_cache( $fresh_posts );
		}
	}

	if ( $update_meta_cache ) {
		update_postmeta_cache( $ids );
	}

	if ( $update_term_cache ) {
		$post_types = array_map( 'get_post_type', $ids );
		$post_types = array_unique( $post_types );
		update_object_term_cache( $ids, $post_types );
	}
}

/**
 * Prime the cache containing the parent ID of various post objects.
 *
 * @since 6.4.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int[] $ids ID list.
 */
function _prime_post_parent_id_caches( array $ids ) {
	global $wpdb;

	$ids = array_filter( $ids, '_validate_cache_id' );
	$ids = array_unique( array_map( 'intval', $ids ), SORT_NUMERIC );

	if ( empty( $ids ) ) {
		return;
	}

	$cache_keys = array();
	foreach ( $ids as $id ) {
		$cache_keys[ $id ] = 'post_parent:' . (string) $id;
	}

	$cached_data = wp_cache_get_multiple( array_values( $cache_keys ), 'posts' );

	$non_cached_ids = array();
	foreach ( $cache_keys as $id => $cache_key ) {
		if ( false === $cached_data[ $cache_key ] ) {
			$non_cached_ids[] = $id;
		}
	}

	if ( ! empty( $non_cached_ids ) ) {
		$fresh_posts = $wpdb->get_results( sprintf( "SELECT $wpdb->posts.ID, $wpdb->posts.post_parent FROM $wpdb->posts WHERE ID IN (%s)", implode( ',', $non_cached_ids ) ) );

		if ( $fresh_posts ) {
			$post_parent_data = array();
			foreach ( $fresh_posts as $fresh_post ) {
				$post_parent_data[ 'post_parent:' . (string) $fresh_post->ID ] = (int) $fresh_post->post_parent;
			}

			wp_cache_add_multiple( $post_parent_data, 'posts' );
		}
	}
}

/**
 * Adds a suffix if any trashed posts have a given slug.
 *
 * Store its desired (i.e. current) slug so it can try to reclaim it
 * if the post is untrashed.
 *
 * For internal use.
 *
 * @since 4.5.0
 * @access private
 *
 * @param string $post_name Post slug.
 * @param int    $post_id   Optional. Post ID that should be ignored. Default 0.
 */
function wp_add_trashed_suffix_to_post_name_for_trashed_posts( $post_name, $post_id = 0 ) {
	$trashed_posts_with_desired_slug = get_posts(
		array(
			'name'         => $post_name,
			'post_status'  => 'trash',
			'post_type'    => 'any',
			'nopaging'     => true,
			'post__not_in' => array( $post_id ),
		)
	);

	if ( ! empty( $trashed_posts_with_desired_slug ) ) {
		foreach ( $trashed_posts_with_desired_slug as $_post ) {
			wp_add_trashed_suffix_to_post_name_for_post( $_post );
		}
	}
}

/**
 * Adds a trashed suffix for a given post.
 *
 * Store its desired (i.e. current) slug so it can try to reclaim it
 * if the post is untrashed.
 *
 * For internal use.
 *
 * @since 4.5.0
 * @access private
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param WP_Post $post The post.
 * @return string New slug for the post.
 */
function wp_add_trashed_suffix_to_post_name_for_post( $post ) {
	global $wpdb;

	$post = get_post( $post );

	if ( str_ends_with( $post->post_name, '__trashed' ) ) {
		return $post->post_name;
	}
	add_post_meta( $post->ID, '_wp_desired_post_slug', $post->post_name );
	$post_name = _truncate_post_slug( $post->post_name, 191 ) . '__trashed';
	$wpdb->update( $wpdb->posts, array( 'post_name' => $post_name ), array( 'ID' => $post->ID ) );
	clean_post_cache( $post->ID );
	return $post_name;
}

/**
 * Sets the last changed time for the 'posts' cache group.
 *
 * @since 5.0.0
 */
function wp_cache_set_posts_last_changed() {
	wp_cache_set_last_changed( 'posts' );
}

/**
 * Gets all available post MIME types for a given post type.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $type
 * @return string[] An array of MIME types.
 */
function get_available_post_mime_types( $type = 'attachment' ) {
	global $wpdb;

	/**
	 * Filters the list of available post MIME types for the given post type.
	 *
	 * @since 6.4.0
	 *
	 * @param string[]|null $mime_types An array of MIME types. Default null.
	 * @param string        $type       The post type name. Usually 'attachment' but can be any post type.
	 */
	$mime_types = apply_filters( 'pre_get_available_post_mime_types', null, $type );

	if ( ! is_array( $mime_types ) ) {
		$mime_types = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_mime_type FROM $wpdb->posts WHERE post_type = %s AND post_mime_type != ''", $type ) );
	}

	// Remove nulls from returned $mime_types.
	return array_values( array_filter( $mime_types ) );
}

/**
 * Retrieves the path to an uploaded image file.
 *
 * Similar to `get_attached_file()` however some images may have been processed after uploading
 * to make them suitable for web use. In this case the attached "full" size file is usually replaced
 * with a scaled down version of the original image. This function always returns the path
 * to the originally uploaded image file.
 *
 * @since 5.3.0
 * @since 5.4.0 Added the `$unfiltered` parameter.
 *
 * @param int  $attachment_id Attachment ID.
 * @param bool $unfiltered Optional. Passed through to `get_attached_file()`. Default false.
 * @return string|false Path to the original image file or false if the attachment is not an image.
 */
function wp_get_original_image_path( $attachment_id, $unfiltered = false ) {
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}

	$image_meta = wp_get_attachment_metadata( $attachment_id );
	$image_file = get_attached_file( $attachment_id, $unfiltered );

	if ( empty( $image_meta['original_image'] ) ) {
		$original_image = $image_file;
	} else {
		$original_image = path_join( dirname( $image_file ), $image_meta['original_image'] );
	}

	/**
	 * Filters the path to the original image.
	 *
	 * @since 5.3.0
	 *
	 * @param string $original_image Path to original image file.
	 * @param int    $attachment_id  Attachment ID.
	 */
	return apply_filters( 'wp_get_original_image_path', $original_image, $attachment_id );
}

/**
 * Retrieves the URL to an original attachment image.
 *
 * Similar to `wp_get_attachment_url()` however some images may have been
 * processed after uploading. In this case this function returns the URL
 * to the originally uploaded image file.
 *
 * @since 5.3.0
 *
 * @param int $attachment_id Attachment post ID.
 * @return string|false Attachment image URL, false on error or if the attachment is not an image.
 */
function wp_get_original_image_url( $attachment_id ) {
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}

	$image_url = wp_get_attachment_url( $attachment_id );

	if ( ! $image_url ) {
		return false;
	}

	$image_meta = wp_get_attachment_metadata( $attachment_id );

	if ( empty( $image_meta['original_image'] ) ) {
		$original_image_url = $image_url;
	} else {
		$original_image_url = path_join( dirname( $image_url ), $image_meta['original_image'] );
	}

	/**
	 * Filters the URL to the original attachment image.
	 *
	 * @since 5.3.0
	 *
	 * @param string $original_image_url URL to original image.
	 * @param int    $attachment_id      Attachment ID.
	 */
	return apply_filters( 'wp_get_original_image_url', $original_image_url, $attachment_id );
}

/**
 * Filters callback which sets the status of an untrashed post to its previous status.
 *
 * This can be used as a callback on the `wp_untrash_post_status` filter.
 *
 * @since 5.6.0
 *
 * @param string $new_status      The new status of the post being restored.
 * @param int    $post_id         The ID of the post being restored.
 * @param string $previous_status The status of the post at the point where it was trashed.
 * @return string The new status of the post.
 */
function wp_untrash_post_set_previous_status( $new_status, $post_id, $previous_status ) {
	return $previous_status;
}

/**
 * Returns whether the post can be edited in the block editor.
 *
 * @since 5.0.0
 * @since 6.1.0 Moved to wp-includes from wp-admin.
 *
 * @param int|WP_Post $post Post ID or WP_Post object.
 * @return bool Whether the post can be edited in the block editor.
 */
function use_block_editor_for_post( $post ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	// We're in the meta box loader, so don't use the block editor.
	if ( is_admin() && isset( $_GET['meta-box-loader'] ) ) {
		check_admin_referer( 'meta-box-loader', 'meta-box-loader-nonce' );
		return false;
	}

	$use_block_editor = use_block_editor_for_post_type( $post->post_type );

	/**
	 * Filters whether a post is able to be edited in the block editor.
	 *
	 * @since 5.0.0
	 *
	 * @param bool    $use_block_editor Whether the post can be edited or not.
	 * @param WP_Post $post             The post being checked.
	 */
	return apply_filters( 'use_block_editor_for_post', $use_block_editor, $post );
}

/**
 * Returns whether a post type is compatible with the block editor.
 *
 * The block editor depends on the REST API, and if the post type is not shown in the
 * REST API, then it won't work with the block editor.
 *
 * @since 5.0.0
 * @since 6.1.0 Moved to wp-includes from wp-admin.
 *
 * @param string $post_type The post type.
 * @return bool Whether the post type can be edited with the block editor.
 */
function use_block_editor_for_post_type( $post_type ) {
	if ( ! post_type_exists( $post_type ) ) {
		return false;
	}

	if ( ! post_type_supports( $post_type, 'editor' ) ) {
		return false;
	}

	$post_type_object = get_post_type_object( $post_type );
	if ( $post_type_object && ! $post_type_object->show_in_rest ) {
		return false;
	}

	/**
	 * Filters whether a post is able to be edited in the block editor.
	 *
	 * @since 5.0.0
	 *
	 * @param bool   $use_block_editor  Whether the post type can be edited or not. Default true.
	 * @param string $post_type         The post type being checked.
	 */
	return apply_filters( 'use_block_editor_for_post_type', true, $post_type );
}

/**
 * Registers any additional post meta fields.
 *
 * @since 6.3.0 Adds `wp_pattern_sync_status` meta field to the wp_block post type so an unsynced option can be added.
 *
 * @link https://github.com/WordPress/gutenberg/pull/51144
 */
function wp_create_initial_post_meta() {
	register_post_meta(
		'wp_block',
		'wp_pattern_sync_status',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'single'            => true,
			'type'              => 'string',
			'show_in_rest'      => array(
				'schema' => array(
					'type' => 'string',
					'enum' => array( 'partial', 'unsynced' ),
				),
			),
		)
	);
}
