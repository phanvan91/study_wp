<?php
/**
 * Xuất định dạng OPML XML để lấy các links được định nghĩa trong quản trị link.
 * Có thể được sử dụng để xuất links từ một blog sang blog khác. Links không được
 * xuất bởi WordPress export, vì vậy file này xử lý điều đó.
 *
 * File này không được thêm mặc định vào các trang theme WordPress khi xuất
 * feed links. Nó sẽ phải được thêm thủ công để trình duyệt và người dùng nhận biết
 * rằng file này tồn tại.
 *
 * @package WordPress
 */

require_once __DIR__ . '/wp-load.php';

header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
$link_cat = '';
if ( ! empty( $_GET['link_cat'] ) ) {
	$link_cat = $_GET['link_cat'];
	if ( ! in_array( $link_cat, array( 'all', '0' ), true ) ) {
		$link_cat = absint( (string) urldecode( $link_cat ) );
	}
}

echo '<?xml version="1.0"?' . ">\n";
?>
<opml version="1.0">
	<head>
		<title>
		<?php
			/* translators: %s: Tiêu đề trang. */
			printf( __( 'Links for %s' ), esc_attr( get_bloginfo( 'name', 'display' ) ) );
		?>
		</title>
		<dateCreated><?php echo gmdate( 'D, d M Y H:i:s' ); ?> GMT</dateCreated>
		<?php
		/**
		 * Kích hoạt trong header OPML.
		 *
		 * @since 3.0.0
		 */
		do_action( 'opml_head' );
		?>
	</head>
	<body>
<?php
if ( empty( $link_cat ) ) {
	$cats = get_categories(
		array(
			'taxonomy'     => 'link_category',
			'hierarchical' => 0,
		)
	);
} else {
	$cats = get_categories(
		array(
			'taxonomy'     => 'link_category',
			'hierarchical' => 0,
			'include'      => $link_cat,
		)
	);
}

foreach ( (array) $cats as $cat ) :
	/** Filter này được ghi chép trong wp-includes/bookmark-template.php */
	$catname = apply_filters( 'link_category', $cat->name );

	?>
<outline type="category" title="<?php echo esc_attr( $catname ); ?>">
	<?php
	$bookmarks = get_bookmarks( array( 'category' => $cat->term_id ) );
	foreach ( (array) $bookmarks as $bookmark ) :
		/**
		 * Lọc text tiêu đề link OPML outline.
		 *
		 * @since 2.2.0
		 *
		 * @param string $title Text tiêu đề OPML outline.
		 */
		$title = apply_filters( 'link_title', $bookmark->link_name );
		?>
<outline text="<?php echo esc_attr( $title ); ?>" type="link" xmlUrl="<?php echo esc_url( $bookmark->link_rss ); ?>" htmlUrl="<?php echo esc_url( $bookmark->link_url ); ?>" updated="
							<?php
							if ( '0000-00-00 00:00:00' !== $bookmark->link_updated ) {
								echo $bookmark->link_updated;
							}
							?>
" />
		<?php
	endforeach; // $bookmarks
	?>
</outline>
	<?php
endforeach; // $cats
?>
</body>
</opml>
