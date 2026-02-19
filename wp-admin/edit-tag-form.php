<?php
/**
 * Form chỉnh sửa thẻ để nhúng vào các bảng quản trị.
 *
 * @package WordPress
 * @subpackage Administration
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Các hook tương thích ngược.
if ( 'category' === $taxonomy ) {
	/**
	 * Kích hoạt trước form Chỉnh sửa Chuyên mục.
	 *
	 * @since 2.1.0
	 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_pre_edit_form'} thay thế.
	 *
	 * @param WP_Term $tag Đối tượng term chuyên mục hiện tại.
	 */
	do_action_deprecated( 'edit_category_form_pre', array( $tag ), '3.0.0', '{$taxonomy}_pre_edit_form' );
} elseif ( 'link_category' === $taxonomy ) {
	/**
	 * Kích hoạt trước form Chỉnh sửa Chuyên mục Liên kết.
	 *
	 * @since 2.3.0
	 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_pre_edit_form'} thay thế.
	 *
	 * @param WP_Term $tag Đối tượng term chuyên mục liên kết hiện tại.
	 */
	do_action_deprecated( 'edit_link_category_form_pre', array( $tag ), '3.0.0', '{$taxonomy}_pre_edit_form' );
} else {
	/**
	 * Kích hoạt trước form Chỉnh sửa Thẻ.
	 *
	 * @since 2.5.0
	 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_pre_edit_form'} thay thế.
	 *
	 * @param WP_Term $tag Đối tượng term thẻ hiện tại.
	 */
	do_action_deprecated( 'edit_tag_form_pre', array( $tag ), '3.0.0', '{$taxonomy}_pre_edit_form' );
}

$wp_http_referer = ! empty( $_REQUEST['wp_http_referer'] ) ? sanitize_url( $_REQUEST['wp_http_referer'] ) : '';
$wp_http_referer = remove_query_arg( array( 'action', 'message', 'tag_ID' ), $wp_http_referer );

// Cũng được sử dụng bởi Chỉnh sửa Thẻ.
require_once ABSPATH . 'wp-admin/includes/edit-tag-messages.php';

/**
 * Kích hoạt trước form Chỉnh sửa Term cho tất cả taxonomy.
 *
 * Phần động của tên hook, `$taxonomy`, tham chiếu đến
 * slug của taxonomy.
 *
 * Các tên hook có thể bao gồm:
 *
 *  - `category_pre_edit_form`
 *  - `post_tag_pre_edit_form`
 *
 * @since 3.0.0
 *
 * @param WP_Term $tag      Đối tượng term taxonomy hiện tại.
 * @param string  $taxonomy Slug $taxonomy hiện tại.
 */
do_action( "{$taxonomy}_pre_edit_form", $tag, $taxonomy ); ?>

<div class="wrap">
<h1><?php echo $tax->labels->edit_item; ?></h1>

<?php
$class = ( isset( $_REQUEST['error'] ) ) ? 'error' : 'success';

if ( $message ) {
	$message = '<p><strong>' . $message . '</strong></p>';
	if ( $wp_http_referer ) {
		$message .= sprintf(
			'<p><a href="%1$s">%2$s</a></p>',
			esc_url( wp_validate_redirect( sanitize_url( $wp_http_referer ), admin_url( 'term.php?taxonomy=' . $taxonomy ) ) ),
			esc_html( $tax->labels->back_to_items )
		);
	}

	wp_admin_notice(
		$message,
		array(
			'type'           => $class,
			'id'             => 'message',
			'paragraph_wrap' => false,
		)
	);
}
?>

<div id="ajax-response"></div>

<form name="edittag" id="edittag" method="post" action="edit-tags.php" class="validate"
<?php
/**
 * Kích hoạt bên trong thẻ form Chỉnh sửa Term.
 *
 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
 *
 * Các tên hook có thể bao gồm:
 *
 *  - `category_term_edit_form_tag`
 *  - `post_tag_term_edit_form_tag`
 *
 * @since 3.7.0
 */
do_action( "{$taxonomy}_term_edit_form_tag" );
?>
>
<input type="hidden" name="action" value="editedtag" />
<input type="hidden" name="tag_ID" value="<?php echo esc_attr( $tag_ID ); ?>" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
<?php
wp_original_referer_field( true, 'previous' );
wp_nonce_field( 'update-tag_' . $tag_ID );

/**
 * Kích hoạt ở đầu form Chỉnh sửa Term.
 *
 * Tại thời điểm này, các trường ẩn bắt buộc và nonce đã được xuất ra.
 *
 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
 *
 * Các tên hook có thể bao gồm:
 *
 *  - `category_term_edit_form_top`
 *  - `post_tag_term_edit_form_top`
 *
 * @since 4.5.0
 *
 * @param WP_Term $tag      Đối tượng term taxonomy hiện tại.
 * @param string  $taxonomy Slug $taxonomy hiện tại.
 */
do_action( "{$taxonomy}_term_edit_form_top", $tag, $taxonomy );

$tag_name_value = '';
if ( isset( $tag->name ) ) {
	$tag_name_value = esc_attr( $tag->name );
}
?>
	<table class="form-table" role="presentation">
		<tr class="form-field form-required term-name-wrap">
			<th scope="row"><label for="name"><?php _ex( 'Name', 'term name' ); ?></label></th>
			<td><input name="name" id="name" type="text" value="<?php echo $tag_name_value; ?>" size="40" aria-required="true" aria-describedby="name-description" />
			<p class="description" id="name-description"><?php echo $tax->labels->name_field_description; ?></p></td>
		</tr>
		<tr class="form-field term-slug-wrap">
			<th scope="row"><label for="slug"><?php _e( 'Slug' ); ?></label></th>
			<?php
			/**
			 * Lọc slug có thể chỉnh sửa cho bài viết hoặc term.
			 *
			 * Lưu ý: Đây là hook đa mục đích, được sử dụng cho cả
			 * URI bài viết có thể chỉnh sửa và slug term.
			 *
			 * @since 2.6.0
			 * @since 4.4.0 Đã thêm tham số `$tag`.
			 *
			 * @param string          $slug Slug có thể chỉnh sửa. Sẽ là slug term hoặc URI bài viết
			 *                              tùy thuộc vào ngữ cảnh đánh giá.
			 * @param WP_Term|WP_Post $tag  Đối tượng term hoặc bài viết.
			 */
			$slug = isset( $tag->slug ) ? apply_filters( 'editable_slug', $tag->slug, $tag ) : '';
			?>
			<td><input name="slug" id="slug" type="text" value="<?php echo esc_attr( $slug ); ?>" size="40" aria-describedby="slug-description" />
			<p class="description" id="slug-description"><?php echo $tax->labels->slug_field_description; ?></p></td>
		</tr>
<?php if ( is_taxonomy_hierarchical( $taxonomy ) ) : ?>
		<tr class="form-field term-parent-wrap">
			<th scope="row"><label for="parent"><?php echo esc_html( $tax->labels->parent_item ); ?></label></th>
			<td>
				<?php
				$dropdown_args = array(
					'hide_empty'       => 0,
					'hide_if_empty'    => false,
					'taxonomy'         => $taxonomy,
					'name'             => 'parent',
					'orderby'          => 'name',
					'selected'         => $tag->parent,
					'exclude_tree'     => $tag->term_id,
					'hierarchical'     => true,
					'show_option_none' => __( 'None' ),
					'aria_describedby' => 'parent-description',
				);

				/** This filter is documented in wp-admin/edit-tags.php */
				$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxonomy, 'edit' );
				wp_dropdown_categories( $dropdown_args );
				?>
				<?php if ( 'category' === $taxonomy ) : ?>
					<p class="description" id="parent-description"><?php _e( 'Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.' ); ?></p>
				<?php else : ?>
					<p class="description" id="parent-description"><?php echo $tax->labels->parent_field_description; ?></p>
				<?php endif; ?>
			</td>
		</tr>
<?php endif; // is_taxonomy_hierarchical() ?>
		<tr class="form-field term-description-wrap">
			<th scope="row"><label for="description"><?php _e( 'Description' ); ?></label></th>
			<td><textarea name="description" id="description" rows="5" cols="50" class="large-text" aria-describedby="description-description"><?php echo $tag->description; // textarea_escaped ?></textarea>
			<p class="description" id="description-description"><?php echo $tax->labels->desc_field_description; ?></p></td>
		</tr>
		<?php
		// Các hook tương thích ngược.
		if ( 'category' === $taxonomy ) {
			/**
			 * Kích hoạt sau khi các trường form Chỉnh sửa Chuyên mục được hiển thị.
			 *
			 * @since 2.9.0
			 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_edit_form_fields'} thay thế.
			 *
			 * @param WP_Term $tag Đối tượng term chuyên mục hiện tại.
			 */
			do_action_deprecated( 'edit_category_form_fields', array( $tag ), '3.0.0', '{$taxonomy}_edit_form_fields' );
		} elseif ( 'link_category' === $taxonomy ) {
			/**
			 * Kích hoạt sau khi các trường form Chỉnh sửa Chuyên mục Liên kết được hiển thị.
			 *
			 * @since 2.9.0
			 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_edit_form_fields'} thay thế.
			 *
			 * @param WP_Term $tag Đối tượng term chuyên mục liên kết hiện tại.
			 */
			do_action_deprecated( 'edit_link_category_form_fields', array( $tag ), '3.0.0', '{$taxonomy}_edit_form_fields' );
		} else {
			/**
			 * Kích hoạt sau khi các trường form Chỉnh sửa Thẻ được hiển thị.
			 *
			 * @since 2.9.0
			 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_edit_form_fields'} thay thế.
			 *
			 * @param WP_Term $tag Đối tượng term thẻ hiện tại.
			 */
			do_action_deprecated( 'edit_tag_form_fields', array( $tag ), '3.0.0', '{$taxonomy}_edit_form_fields' );
		}
		/**
		 * Kích hoạt sau khi các trường form Chỉnh sửa Term được hiển thị.
		 *
		 * Phần động của tên hook, `$taxonomy`, tham chiếu đến
		 * slug taxonomy.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `category_edit_form_fields`
		 *  - `post_tag_edit_form_fields`
		 *
		 * @since 3.0.0
		 *
		 * @param WP_Term $tag      Đối tượng term taxonomy hiện tại.
		 * @param string  $taxonomy Slug taxonomy hiện tại.
		 */
		do_action( "{$taxonomy}_edit_form_fields", $tag, $taxonomy );
		?>
	</table>
<?php
// Các hook tương thích ngược.
if ( 'category' === $taxonomy ) {
	/** Action này được ghi nhận trong wp-admin/edit-tags.php */
	do_action_deprecated( 'edit_category_form', array( $tag ), '3.0.0', '{$taxonomy}_add_form' );
} elseif ( 'link_category' === $taxonomy ) {
	/** Action này được ghi nhận trong wp-admin/edit-tags.php */
	do_action_deprecated( 'edit_link_category_form', array( $tag ), '3.0.0', '{$taxonomy}_add_form' );
} else {
	/**
	 * Kích hoạt ở cuối form Chỉnh sửa Term.
	 *
	 * @since 2.5.0
	 * @deprecated 3.0.0 Sử dụng {@see '{$taxonomy}_edit_form'} thay thế.
	 *
	 * @param WP_Term $tag Đối tượng term taxonomy hiện tại.
	 */
	do_action_deprecated( 'edit_tag_form', array( $tag ), '3.0.0', '{$taxonomy}_edit_form' );
}
/**
 * Kích hoạt ở cuối form Chỉnh sửa Term cho tất cả taxonomy.
 *
 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
 *
 * Các tên hook có thể bao gồm:
 *
 *  - `category_edit_form`
 *  - `post_tag_edit_form`
 *
 * @since 3.0.0
 *
 * @param WP_Term $tag      Đối tượng term taxonomy hiện tại.
 * @param string  $taxonomy Slug taxonomy hiện tại.
 */
do_action( "{$taxonomy}_edit_form", $tag, $taxonomy );
?>

<div class="edit-tag-actions">

	<?php submit_button( __( 'Update' ), 'primary', null, false ); ?>

	<?php if ( current_user_can( 'delete_term', $tag->term_id ) ) : ?>
		<span id="delete-link">
			<a class="delete" href="<?php echo esc_url( admin_url( wp_nonce_url( "edit-tags.php?action=delete&taxonomy=$taxonomy&tag_ID=$tag->term_id", 'delete-tag_' . $tag->term_id ) ) ); ?>"><?php _e( 'Delete' ); ?></a>
		</span>
	<?php endif; ?>

</div>

</form>
</div>

<?php if ( ! wp_is_mobile() ) : ?>
<script type="text/javascript">
try{document.forms.edittag.name.focus();}catch(e){}
</script>
	<?php
endif;
