<?php
/**
 * Trình xử lý tải file phía máy chủ từ wp-plupload hoặc các phương thức tải lên bất đồng bộ khác.
 *
 * @package WordPress
 * @subpackage Administration
 */

if ( isset( $_REQUEST['action'] ) && 'upload-attachment' === $_REQUEST['action'] ) {
	define( 'DOING_AJAX', true );
}

if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

/** Nạp Bootstrap WordPress */
require_once dirname( __DIR__ ) . '/wp-load.php';

require_once ABSPATH . 'wp-admin/admin.php';

header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );

if ( isset( $_REQUEST['action'] ) && 'upload-attachment' === $_REQUEST['action'] ) {
	require ABSPATH . 'wp-admin/includes/ajax-actions.php';

	send_nosniff_header();
	nocache_headers();

	wp_ajax_upload_attachment();
	die( '0' );
}

if ( ! current_user_can( 'upload_files' ) ) {
	wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
}

// Chỉ lấy form chi tiết cho attachment đó.
if ( isset( $_REQUEST['attachment_id'] ) && (int) $_REQUEST['attachment_id'] && $_REQUEST['fetch'] ) {
	$id   = (int) $_REQUEST['attachment_id'];
	$post = get_post( $id );
	if ( 'attachment' !== $post->post_type ) {
		wp_die( __( 'Invalid post type.' ) );
	}

	switch ( $_REQUEST['fetch'] ) {
		case 3:
			?>
			<div class="media-item-wrapper">
				<div class="attachment-details">
					<?php
					$thumb_url = wp_get_attachment_image_src( $id, 'thumbnail', true );
					if ( $thumb_url ) {
						echo '<img class="pinkynail" src="' . esc_url( $thumb_url[0] ) . '" alt="" />';
					}

					// Tiêu đề không nên bao giờ rỗng, nhưng dùng tên file phòng trường hợp.
					$file     = get_attached_file( $post->ID );
					$file_url = wp_get_attachment_url( $post->ID );
					$title    = $post->post_title ? $post->post_title : wp_basename( $file );
					?>
					<div class="filename new">
						<span class="media-list-title"><strong><?php echo esc_html( wp_html_excerpt( $title, 60, '&hellip;' ) ); ?></strong></span>
						<span class="media-list-subtitle"><?php echo esc_html( wp_basename( $file ) ); ?></span>
						<div class="attachment-tools">
							<?php
							if ( current_user_can( 'edit_post', $id ) ) {
								echo '<a class="edit-attachment" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . _x( 'Edit', 'media item' ) . '</a>';
							} else {
								echo '<span class="edit-attachment">' . _x( 'Success', 'media item' ) . '</span>';
							}
							?>
							<span class="media-item-copy-container copy-to-clipboard-container edit-attachment">
								<button type="button" class="button button-small copy-attachment-url"
									data-clipboard-text="<?php echo esc_url( $file_url ); ?>"
								><?php _e( 'Copy URL to clipboard' ); ?></button>
								<span class="success hidden" aria-hidden="true"><?php _e( 'Copied!' ); ?></span>
							</span>
						</div>
					</div>
				</div>
			</div>
			<?php
			break;
		case 2:
			add_filter( 'attachment_fields_to_edit', 'media_single_attachment_fields_to_edit', 10, 2 );
			echo get_media_item(
				$id,
				array(
					'send'   => false,
					'delete' => true,
				)
			);
			break;
		default:
			add_filter( 'attachment_fields_to_edit', 'media_post_single_attachment_fields_to_edit', 10, 2 );
			echo get_media_item( $id );
			break;
	}
	exit;
}

check_admin_referer( 'media-form' );

$post_id = 0;
if ( isset( $_REQUEST['post_id'] ) ) {
	$post_id = absint( $_REQUEST['post_id'] );
	if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		$post_id = 0;
	}
}

$id = media_handle_upload( 'async-upload', $post_id );
if ( is_wp_error( $id ) ) {
	$button_unique_id = uniqid( 'dismiss-' );
	$message          = sprintf(
		'%s <strong>%s</strong><br />%s',
		sprintf(
			'<button type="button" id="%s" class="dismiss button-link">%s</button>',
			esc_attr( $button_unique_id ),
			__( 'Dismiss' )
		),
		sprintf(
			/* translators: %s: Name of the file that failed to upload. */
			__( '&#8220;%s&#8221; has failed to upload.' ),
			esc_html( $_FILES['async-upload']['name'] )
		),
		esc_html( $id->get_error_message() )
	);
	wp_admin_notice(
		$message,
		array(
			'additional_classes' => array( 'error-div', 'error' ),
			'paragraph_wrap'     => false,
		)
	);
	echo "<script>jQuery( 'button#{$button_unique_id}' ).on( 'click', function() {jQuery(this).parents('div.media-item').slideUp(200, function(){jQuery(this).remove();})});</script>\n";
	exit;
}

if ( $_REQUEST['short'] ) {
	// Phản hồi dạng ngắn - chỉ ID attachment.
	echo $id;
} else {
	// Phản hồi dạng dài - khối HTML lớn.
	$type = $_REQUEST['type'];

	/**
	 * Lọc ID trả về của attachment đã tải lên.
	 *
	 * Phần động của tên hook, `$type`, tham chiếu đến loại attachment.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `async_upload_audio`
	 *  - `async_upload_file`
	 *  - `async_upload_image`
	 *  - `async_upload_video`
	 *
	 * @since 2.5.0
	 *
	 * @param int $id ID attachment đã tải lên.
	 */
	echo apply_filters( "async_upload_{$type}", $id );
}
