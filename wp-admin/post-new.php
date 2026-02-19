<?php
/**
 * Màn hình quản trị Tạo bài viết mới.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Tải WordPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

/**
 * @global string       $post_type        Loại bài viết toàn cục.
 * @global WP_Post_Type $post_type_object Đối tượng loại bài viết toàn cục.
 * @global WP_Post      $post             Đối tượng bài viết toàn cục.
 */
global $post_type, $post_type_object, $post;

if ( ! isset( $_GET['post_type'] ) ) {
	$post_type = 'post';
} elseif ( in_array( $_GET['post_type'], get_post_types( array( 'show_ui' => true ) ), true ) ) {
	$post_type = $_GET['post_type'];
} else {
	wp_die( __( 'Invalid post type.' ) );
}
$post_type_object = get_post_type_object( $post_type );

if ( 'post' === $post_type ) {
	$parent_file  = 'edit.php';
	$submenu_file = 'post-new.php';
} elseif ( 'attachment' === $post_type ) {
	if ( wp_redirect( admin_url( 'media-new.php' ) ) ) {
		exit;
	}
} else {
	$submenu_file = "post-new.php?post_type=$post_type";
	if ( isset( $post_type_object ) && $post_type_object->show_in_menu && true !== $post_type_object->show_in_menu ) {
		$parent_file = $post_type_object->show_in_menu;
		// Nếu không có mục post-new.php cho loại bài viết này thì sao?
		if ( ! isset( $_registered_pages[ get_plugin_page_hookname( "post-new.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) ) {
			if ( isset( $_registered_pages[ get_plugin_page_hookname( "edit.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) ) {
				// Quay lại edit.php cho loại bài viết đó, nếu tồn tại.
				$submenu_file = "edit.php?post_type=$post_type";
			} else {
				// Nếu không, bỏ cuộc và tô sáng mục cha.
				$submenu_file = $parent_file;
			}
		}
	} else {
		$parent_file = "edit.php?post_type=$post_type";
	}
}

$title = $post_type_object->labels->add_new_item;

$editing = true;

if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to create posts as this user.' ) . '</p>',
		403
	);
}

$post    = get_default_post_to_edit( $post_type, true );
$post_ID = $post->ID;

/** Bộ lọc này được ghi chú trong wp-admin/post.php */
if ( apply_filters( 'replace_editor', false, $post ) !== true ) {
	if ( use_block_editor_for_post( $post ) ) {
		require ABSPATH . 'wp-admin/edit-form-blocks.php';
	} else {
		wp_enqueue_script( 'autosave' );
		require ABSPATH . 'wp-admin/edit-form-advanced.php';
	}
} else {
	// Đánh dấu rằng chúng ta không tải trình soạn thảo khối.
	$current_screen = get_current_screen();
	$current_screen->is_block_editor( false );
}

require_once ABSPATH . 'wp-admin/admin-footer.php';
