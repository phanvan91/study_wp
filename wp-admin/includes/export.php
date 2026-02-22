<?php
/**
 * API Quản trị Xuất dữ liệu WordPress.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Số phiên bản cho định dạng xuất.
 *
 * Tăng số này khi có thay đổi có thể ảnh hưởng đến tính tương thích.
 *
 * @since 2.5.0
 */
define( 'WXR_VERSION', '1.2' );

/**
 * Tạo tệp xuất WXR để tải về.
 *
 * Hành vi mặc định là xuất tất cả nội dung, tuy nhiên lưu ý rằng nội dung bài viết chỉ
 * được xuất cho các loại bài viết có đối số `can_export` được bật. Mọi bài viết với
 * trạng thái 'auto-draft' sẽ bị bỏ qua.
 *
 * @since 2.1.0
 * @since 5.7.0 Thêm các trường `post_modified` và `post_modified_gmt` vào tệp xuất.
 *
 * @global wpdb    $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @global WP_Post $post Đối tượng bài viết toàn cục.
 *
 * @param array $args {
 *     Tùy chọn. Đối số để tạo tệp xuất WXR để tải về. Mặc định mảng rỗng.
 *
 *     @type string $content    Loại nội dung để xuất. Nếu được đặt, chỉ nội dung bài viết của loại bài viết này
 *                              sẽ được xuất. Chấp nhận 'all', 'post', 'page', 'attachment', hoặc một custom post
 *                              đã định nghĩa. Nếu cung cấp custom post type không hợp lệ, mọi loại bài viết có
 *                              `can_export` được bật sẽ được xuất thay thế. Nếu cung cấp custom post type hợp lệ
 *                              nhưng `can_export` bị tắt, thì 'posts' sẽ được xuất thay thế. Khi cung cấp 'all',
 *                              chỉ các loại bài viết có `can_export` được bật mới được xuất. Mặc định 'all'.
 *     @type string $author     Tác giả để xuất nội dung. Chỉ dùng khi `$content` là 'post', 'page', hoặc
 *                              'attachment'. Chấp nhận false (tất cả) hoặc ID tác giả cụ thể. Mặc định false (tất cả).
 *     @type string $category   Chuyên mục (slug) để xuất nội dung. Chỉ dùng khi `$content` là 'post'. Nếu
 *                              được đặt, chỉ nội dung bài viết thuộc `$category` sẽ được xuất. Chấp nhận false
 *                              hoặc slug chuyên mục cụ thể. Mặc định false (tất cả chuyên mục).
 *     @type string $start_date Ngày bắt đầu để xuất nội dung. Định dạng ngày mong đợi là 'Y-m-d'. Chỉ dùng
 *                              khi `$content` là 'post', 'page' hoặc 'attachment'. Mặc định false (từ đầu).
 *     @type string $end_date   Ngày kết thúc để xuất nội dung. Định dạng ngày mong đợi là 'Y-m-d'. Chỉ dùng khi
 *                              `$content` là 'post', 'page' hoặc 'attachment'. Mặc định false (ngày xuất bản mới nhất).
 *     @type string $status     Trạng thái bài viết để xuất. Chỉ dùng khi `$content` là 'post' hoặc 'page'.
 *                              Chấp nhận false (tất cả trạng thái trừ 'auto-draft'), hoặc trạng thái cụ thể,
 *                              ví dụ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit',
 *                              hoặc 'trash'. Mặc định false (tất cả trạng thái trừ 'auto-draft').
 * }
 */
function export_wp( $args = array() ) {
	global $wpdb, $post;

	$defaults = array(
		'content'    => 'all',
		'author'     => false,
		'category'   => false,
		'start_date' => false,
		'end_date'   => false,
		'status'     => false,
	);
	$args     = wp_parse_args( $args, $defaults );

	/**
	 * Kích hoạt khi bắt đầu xuất, trước khi gửi bất kỳ header nào.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args Mảng các đối số xuất.
	 */
	do_action( 'export_wp', $args );

	$sitename = sanitize_key( get_bloginfo( 'name' ) );
	if ( ! empty( $sitename ) ) {
		$sitename .= '.';
	}
	$date        = gmdate( 'Y-m-d' );
	$wp_filename = $sitename . 'WordPress.' . $date . '.xml';
	/**
	 * Lọc tên tệp xuất.
	 *
	 * @since 4.4.0
	 *
	 * @param string $wp_filename Tên tệp để tải về.
	 * @param string $sitename    Tên trang web.
	 * @param string $date        Ngày hôm nay, đã định dạng.
	 */
	$filename = apply_filters( 'export_wp_filename', $wp_filename, $sitename, $date );

	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

	if ( 'all' !== $args['content'] && post_type_exists( $args['content'] ) ) {
		$ptype = get_post_type_object( $args['content'] );
		if ( ! $ptype->can_export ) {
			$args['content'] = 'post';
		}

		$where = $wpdb->prepare( "{$wpdb->posts}.post_type = %s", $args['content'] );
	} else {
		$post_types = get_post_types( array( 'can_export' => true ) );
		$esses      = array_fill( 0, count( $post_types ), '%s' );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$where = $wpdb->prepare( "{$wpdb->posts}.post_type IN (" . implode( ',', $esses ) . ')', $post_types );
	}

	if ( $args['status'] && ( 'post' === $args['content'] || 'page' === $args['content'] ) ) {
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_status = %s", $args['status'] );
	} else {
		$where .= " AND {$wpdb->posts}.post_status != 'auto-draft'";
	}

	$join = '';
	if ( $args['category'] && 'post' === $args['content'] ) {
		$term = term_exists( $args['category'], 'category' );
		if ( $term ) {
			$join   = "INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
			$where .= $wpdb->prepare( " AND {$wpdb->term_relationships}.term_taxonomy_id = %d", $term['term_taxonomy_id'] );
		}
	}

	if ( in_array( $args['content'], array( 'post', 'page', 'attachment' ), true ) ) {
		if ( $args['author'] ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_author = %d", $args['author'] );
		}

		if ( $args['start_date'] ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_date >= %s", gmdate( 'Y-m-d', strtotime( $args['start_date'] ) ) );
		}

		if ( $args['end_date'] ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_date < %s", gmdate( 'Y-m-d', strtotime( '+1 month', strtotime( $args['end_date'] ) ) ) );
		}
	}

	// Lấy ảnh chụp nhanh các ID bài viết, phòng trường hợp thay đổi trong quá trình xuất.
	$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} $join WHERE $where" );

	// Lấy ID các tệp đính kèm của mỗi bài viết, trừ khi tất cả nội dung đã được xuất.
	if ( ! in_array( $args['content'], array( 'all', 'attachment' ), true ) ) {
		// Mảng chứa tất cả ID bổ sung (tệp đính kèm và ảnh đại diện).
		$additional_ids = array();

		// Tạo bản sao mảng ID bài viết để tránh thay đổi mảng gốc.
		$processing_ids = $post_ids;

		while ( $next_posts = array_splice( $processing_ids, 0, 20 ) ) {
			$posts_in     = array_map( 'absint', $next_posts );
			$placeholders = array_fill( 0, count( $posts_in ), '%d' );

			// Tạo chuỗi cho các placeholder.
			$in_placeholder = implode( ',', $placeholders );

			// Chuẩn bị câu lệnh SQL cho ID tệp đính kèm.
			$attachment_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT ID
				FROM $wpdb->posts
				WHERE post_parent IN ($in_placeholder) AND post_type = 'attachment'
					",
					$posts_in
				)
			);

			$thumbnails_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT meta_value
				FROM $wpdb->postmeta
				WHERE $wpdb->postmeta.post_id IN ($in_placeholder)
				AND $wpdb->postmeta.meta_key = '_thumbnail_id'
					",
					$posts_in
				)
			);

			$additional_ids = array_merge( $additional_ids, $attachment_ids, $thumbnails_ids );
		}

		// Gộp các ID bổ sung trở lại với các ID bài viết gốc sau khi xử lý tất cả bài viết
		$post_ids = array_unique( array_merge( $post_ids, $additional_ids ) );
	}

	/*
	 * Chuẩn bị các term được yêu cầu, rỗng trừ khi bài viết được lọc theo chuyên mục
	 * hoặc tất cả nội dung.
	 */
	$cats  = array();
	$tags  = array();
	$terms = array();
	if ( isset( $term ) && $term ) {
		$cat  = get_term( $term['term_id'], 'category' );
		$cats = array( $cat->term_id => $cat );
		unset( $term, $cat );
	} elseif ( 'all' === $args['content'] ) {
		$categories = (array) get_categories( array( 'get' => 'all' ) );
		$tags       = (array) get_tags( array( 'get' => 'all' ) );

		$custom_taxonomies = get_taxonomies( array( '_builtin' => false ) );
		$custom_terms      = (array) get_terms(
			array(
				'taxonomy' => $custom_taxonomies,
				'get'      => 'all',
			)
		);

		// Sắp xếp chuyên mục theo thứ tự sao cho không có con nào đứng trước cha.
		while ( $cat = array_shift( $categories ) ) {
			if ( ! $cat->parent || isset( $cats[ $cat->parent ] ) ) {
				$cats[ $cat->term_id ] = $cat;
			} else {
				$categories[] = $cat;
			}
		}

		// Sắp xếp term theo thứ tự sao cho không có con nào đứng trước cha.
		while ( $t = array_shift( $custom_terms ) ) {
			if ( ! $t->parent || isset( $terms[ $t->parent ] ) ) {
				$terms[ $t->term_id ] = $t;
			} else {
				$custom_terms[] = $t;
			}
		}

		unset( $categories, $custom_taxonomies, $custom_terms );
	}

	/**
	 * Bọc chuỗi cho trước trong thẻ XML CDATA.
	 *
	 * @since 2.1.0
	 *
	 * @param string $str Chuỗi cần bọc trong thẻ XML CDATA.
	 * @return string
	 */
	function wxr_cdata( $str ) {
		if ( ! seems_utf8( $str ) ) {
			$str = utf8_encode( $str );
		}
		// $str = ent2ncr(esc_html($str));
		$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

		return $str;
	}

	/**
	 * Trả về URL của trang web.
	 *
	 * @since 2.5.0
	 *
	 * @return string URL trang web.
	 */
	function wxr_site_url() {
		if ( is_multisite() ) {
			// Multisite: URL cơ sở.
			return network_home_url();
		} else {
			// WordPress (trang đơn): URL trang web.
			return get_bloginfo_rss( 'url' );
		}
	}

	/**
	 * Xuất thẻ XML cat_name từ đối tượng chuyên mục cho trước.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Term $category Đối tượng Chuyên mục.
	 */
	function wxr_cat_name( $category ) {
		if ( empty( $category->name ) ) {
			return;
		}

		echo '<wp:cat_name>' . wxr_cdata( $category->name ) . "</wp:cat_name>\n";
	}

	/**
	 * Xuất thẻ XML category_description từ đối tượng chuyên mục cho trước.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Term $category Đối tượng Chuyên mục.
	 */
	function wxr_category_description( $category ) {
		if ( empty( $category->description ) ) {
			return;
		}

		echo '<wp:category_description>' . wxr_cdata( $category->description ) . "</wp:category_description>\n";
	}

	/**
	 * Xuất thẻ XML tag_name từ đối tượng thẻ cho trước.
	 *
	 * @since 2.3.0
	 *
	 * @param WP_Term $tag Đối tượng Thẻ.
	 */
	function wxr_tag_name( $tag ) {
		if ( empty( $tag->name ) ) {
			return;
		}

		echo '<wp:tag_name>' . wxr_cdata( $tag->name ) . "</wp:tag_name>\n";
	}

	/**
	 * Xuất thẻ XML tag_description từ đối tượng thẻ cho trước.
	 *
	 * @since 2.3.0
	 *
	 * @param WP_Term $tag Đối tượng Thẻ.
	 */
	function wxr_tag_description( $tag ) {
		if ( empty( $tag->description ) ) {
			return;
		}

		echo '<wp:tag_description>' . wxr_cdata( $tag->description ) . "</wp:tag_description>\n";
	}

	/**
	 * Xuất thẻ XML term_name từ đối tượng term cho trước.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_Term $term Đối tượng Term.
	 */
	function wxr_term_name( $term ) {
		if ( empty( $term->name ) ) {
			return;
		}

		echo '<wp:term_name>' . wxr_cdata( $term->name ) . "</wp:term_name>\n";
	}

	/**
	 * Xuất thẻ XML term_description từ đối tượng term cho trước.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_Term $term Đối tượng Term.
	 */
	function wxr_term_description( $term ) {
		if ( empty( $term->description ) ) {
			return;
		}

		echo "\t\t<wp:term_description>" . wxr_cdata( $term->description ) . "</wp:term_description>\n";
	}

	/**
	 * Xuất các thẻ XML term meta cho đối tượng term cho trước.
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param WP_Term $term Đối tượng term.
	 */
	function wxr_term_meta( $term ) {
		global $wpdb;

		$termmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->termmeta WHERE term_id = %d", $term->term_id ) );

		foreach ( $termmeta as $meta ) {
			/**
			 * Lọc xem có nên bỏ qua chọn lọc term meta dùng cho xuất WXR hay không.
			 *
			 * Trả về giá trị truthy từ bộ lọc sẽ bỏ qua đối tượng meta hiện tại
			 * khỏi việc xuất.
			 *
			 * @since 4.6.0
			 *
			 * @param bool   $skip     Có bỏ qua phần term meta hiện tại hay không. Mặc định false.
			 * @param string $meta_key Khóa meta hiện tại.
			 * @param object $meta     Đối tượng meta hiện tại.
			 */
			if ( ! apply_filters( 'wxr_export_skip_termmeta', false, $meta->meta_key, $meta ) ) {
				printf( "\t\t<wp:termmeta>\n\t\t\t<wp:meta_key>%s</wp:meta_key>\n\t\t\t<wp:meta_value>%s</wp:meta_value>\n\t\t</wp:termmeta>\n", wxr_cdata( $meta->meta_key ), wxr_cdata( $meta->meta_value ) );
			}
		}
	}

	/**
	 * Xuất danh sách tác giả có bài viết.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param int[] $post_ids Tùy chọn. Mảng ID bài viết để lọc truy vấn.
	 */
	function wxr_authors_list( ?array $post_ids = null ) {
		global $wpdb;

		if ( ! empty( $post_ids ) ) {
			$post_ids = array_map( 'absint', $post_ids );
			$and      = 'AND ID IN ( ' . implode( ', ', $post_ids ) . ')';
		} else {
			$and = '';
		}

		$authors = array();
		$results = $wpdb->get_results( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status != 'auto-draft' $and" );
		foreach ( (array) $results as $result ) {
			$authors[] = get_userdata( $result->post_author );
		}

		$authors = array_filter( $authors );

		foreach ( $authors as $author ) {
			echo "\t<wp:author>";
			echo '<wp:author_id>' . (int) $author->ID . '</wp:author_id>';
			echo '<wp:author_login>' . wxr_cdata( $author->user_login ) . '</wp:author_login>';
			echo '<wp:author_email>' . wxr_cdata( $author->user_email ) . '</wp:author_email>';
			echo '<wp:author_display_name>' . wxr_cdata( $author->display_name ) . '</wp:author_display_name>';
			echo '<wp:author_first_name>' . wxr_cdata( $author->first_name ) . '</wp:author_first_name>';
			echo '<wp:author_last_name>' . wxr_cdata( $author->last_name ) . '</wp:author_last_name>';
			echo "</wp:author>\n";
		}
	}

	/**
	 * Xuất tất cả các term menu điều hướng.
	 *
	 * @since 3.1.0
	 */
	function wxr_nav_menu_terms() {
		$nav_menus = wp_get_nav_menus();
		if ( empty( $nav_menus ) || ! is_array( $nav_menus ) ) {
			return;
		}

		foreach ( $nav_menus as $menu ) {
			echo "\t<wp:term>";
			echo '<wp:term_id>' . (int) $menu->term_id . '</wp:term_id>';
			echo '<wp:term_taxonomy>nav_menu</wp:term_taxonomy>';
			echo '<wp:term_slug>' . wxr_cdata( $menu->slug ) . '</wp:term_slug>';
			wxr_term_name( $menu );
			echo "</wp:term>\n";
		}
	}

	/**
	 * Xuất danh sách các term taxonomy, dưới dạng thẻ XML, liên kết với một bài viết.
	 *
	 * @since 2.3.0
	 */
	function wxr_post_taxonomy() {
		$post = get_post();

		$taxonomies = get_object_taxonomies( $post->post_type );
		if ( empty( $taxonomies ) ) {
			return;
		}
		$terms = wp_get_object_terms( $post->ID, $taxonomies );

		foreach ( (array) $terms as $term ) {
			echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . wxr_cdata( $term->name ) . "</category>\n";
		}
	}

	/**
	 * Xác định xem có nên bỏ qua chọn lọc post meta dùng cho xuất WXR hay không.
	 *
	 * @since 3.3.0
	 *
	 * @param bool   $return_me Có bỏ qua post meta hiện tại hay không. Mặc định false.
	 * @param string $meta_key  Khóa meta.
	 * @return bool
	 */
	function wxr_filter_postmeta( $return_me, $meta_key ) {
		if ( '_edit_lock' === $meta_key ) {
			$return_me = true;
		}
		return $return_me;
	}
	add_filter( 'wxr_export_skip_postmeta', 'wxr_filter_postmeta', 10, 2 );

	echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . "\" ?>\n";

	?>
<!-- This is a WordPress eXtended RSS file generated by WordPress as an export of your site. -->
<!-- It contains information about your site's posts, pages, comments, categories, and other content. -->
<!-- You may use this file to transfer that content from one site to another. -->
<!-- This file is not intended to serve as a complete backup of your site. -->

<!-- To import this information into a WordPress site follow these steps: -->
<!-- 1. Log in to that site as an administrator. -->
<!-- 2. Go to Tools: Import in the WordPress admin panel. -->
<!-- 3. Install the "WordPress" importer from the list. -->
<!-- 4. Activate & Run Importer. -->
<!-- 5. Upload this file using the form provided on that page. -->
<!-- 6. You will first be asked to map the authors in this export file to users -->
<!--    on the site. For each author, you may choose to map to an -->
<!--    existing user on the site or to create a new user. -->
<!-- 7. WordPress will then import each of the posts, pages, comments, categories, etc. -->
<!--    contained in this file into your site. -->

	<?php the_generator( 'export' ); ?>
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/"
>

<channel>
	<title><?php bloginfo_rss( 'name' ); ?></title>
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<pubDate><?php echo gmdate( 'D, d M Y H:i:s +0000' ); ?></pubDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<wp:wxr_version><?php echo WXR_VERSION; ?></wp:wxr_version>
	<wp:base_site_url><?php echo wxr_site_url(); ?></wp:base_site_url>
	<wp:base_blog_url><?php bloginfo_rss( 'url' ); ?></wp:base_blog_url>

	<?php wxr_authors_list( $post_ids ); ?>

	<?php foreach ( $cats as $c ) : ?>
	<wp:category>
		<wp:term_id><?php echo (int) $c->term_id; ?></wp:term_id>
		<wp:category_nicename><?php echo wxr_cdata( $c->slug ); ?></wp:category_nicename>
		<wp:category_parent><?php echo wxr_cdata( $c->parent ? $cats[ $c->parent ]->slug : '' ); ?></wp:category_parent>
		<?php
		wxr_cat_name( $c );
		wxr_category_description( $c );
		wxr_term_meta( $c );
		?>
	</wp:category>
	<?php endforeach; ?>
	<?php foreach ( $tags as $t ) : ?>
	<wp:tag>
		<wp:term_id><?php echo (int) $t->term_id; ?></wp:term_id>
		<wp:tag_slug><?php echo wxr_cdata( $t->slug ); ?></wp:tag_slug>
		<?php
		wxr_tag_name( $t );
		wxr_tag_description( $t );
		wxr_term_meta( $t );
		?>
	</wp:tag>
	<?php endforeach; ?>
	<?php foreach ( $terms as $t ) : ?>
	<wp:term>
		<wp:term_id><?php echo (int) $t->term_id; ?></wp:term_id>
		<wp:term_taxonomy><?php echo wxr_cdata( $t->taxonomy ); ?></wp:term_taxonomy>
		<wp:term_slug><?php echo wxr_cdata( $t->slug ); ?></wp:term_slug>
		<wp:term_parent><?php echo wxr_cdata( $t->parent ? $terms[ $t->parent ]->slug : '' ); ?></wp:term_parent>
		<?php
		wxr_term_name( $t );
		wxr_term_description( $t );
		wxr_term_meta( $t );
		?>
	</wp:term>
	<?php endforeach; ?>
	<?php
	if ( 'all' === $args['content'] ) {
		wxr_nav_menu_terms();
	}
	?>

	<?php
	/** This action is documented in wp-includes/feed-rss2.php */
	do_action( 'rss2_head' );
	?>

	<?php
	if ( $post_ids ) {
		/**
		 * @global WP_Query $wp_query WordPress Query object.
		 */
		global $wp_query;

		// Giả lập đang trong vòng lặp.
		$wp_query->in_the_loop = true;

		// Lấy 20 bài viết mỗi lần thay vì tải toàn bộ bảng vào bộ nhớ.
		while ( $next_posts = array_splice( $post_ids, 0, 20 ) ) {
			$where = 'WHERE ID IN (' . implode( ',', $next_posts ) . ')';
			$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

			// Bắt đầu vòng lặp.
			foreach ( $posts as $post ) {
				setup_postdata( $post );

				/**
				 * Lọc tiêu đề bài viết dùng cho xuất WXR.
				 *
				 * @since 5.7.0
				 *
				 * @param string $post_title Tiêu đề của bài viết hiện tại.
				 */
				$title = wxr_cdata( apply_filters( 'the_title_export', $post->post_title ) );

				/**
				 * Lọc nội dung bài viết dùng cho xuất WXR.
				 *
				 * @since 2.5.0
				 *
				 * @param string $post_content Nội dung của bài viết hiện tại.
				 */
				$content = wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );

				/**
				 * Lọc đoạn trích bài viết dùng cho xuất WXR.
				 *
				 * @since 2.6.0
				 *
				 * @param string $post_excerpt Đoạn trích của bài viết hiện tại.
				 */
				$excerpt = wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );

				$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
				?>
	<item>
		<title><?php echo $title; ?></title>
		<link><?php the_permalink_rss(); ?></link>
		<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
		<dc:creator><?php echo wxr_cdata( get_the_author_meta( 'login' ) ); ?></dc:creator>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
		<description></description>
		<content:encoded><?php echo $content; ?></content:encoded>
		<excerpt:encoded><?php echo $excerpt; ?></excerpt:encoded>
		<wp:post_id><?php echo (int) $post->ID; ?></wp:post_id>
		<wp:post_date><?php echo wxr_cdata( $post->post_date ); ?></wp:post_date>
		<wp:post_date_gmt><?php echo wxr_cdata( $post->post_date_gmt ); ?></wp:post_date_gmt>
		<wp:post_modified><?php echo wxr_cdata( $post->post_modified ); ?></wp:post_modified>
		<wp:post_modified_gmt><?php echo wxr_cdata( $post->post_modified_gmt ); ?></wp:post_modified_gmt>
		<wp:comment_status><?php echo wxr_cdata( $post->comment_status ); ?></wp:comment_status>
		<wp:ping_status><?php echo wxr_cdata( $post->ping_status ); ?></wp:ping_status>
		<wp:post_name><?php echo wxr_cdata( $post->post_name ); ?></wp:post_name>
		<wp:status><?php echo wxr_cdata( $post->post_status ); ?></wp:status>
		<wp:post_parent><?php echo (int) $post->post_parent; ?></wp:post_parent>
		<wp:menu_order><?php echo (int) $post->menu_order; ?></wp:menu_order>
		<wp:post_type><?php echo wxr_cdata( $post->post_type ); ?></wp:post_type>
		<wp:post_password><?php echo wxr_cdata( $post->post_password ); ?></wp:post_password>
		<wp:is_sticky><?php echo (int) $is_sticky; ?></wp:is_sticky>
				<?php	if ( 'attachment' === $post->post_type ) : ?>
		<wp:attachment_url><?php echo wxr_cdata( wp_get_attachment_url( $post->ID ) ); ?></wp:attachment_url>
	<?php endif; ?>
				<?php wxr_post_taxonomy(); ?>
				<?php
				$postmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );
				foreach ( $postmeta as $meta ) :
					/**
					 * Lọc xem có nên bỏ qua chọn lọc post meta dùng cho xuất WXR hay không.
					 *
					 * Trả về giá trị truthy từ bộ lọc sẽ bỏ qua đối tượng meta hiện tại
					 * khỏi việc xuất.
					 *
					 * @since 3.3.0
					 *
					 * @param bool   $skip     Có bỏ qua post meta hiện tại hay không. Mặc định false.
					 * @param string $meta_key Khóa meta hiện tại.
					 * @param object $meta     Đối tượng meta hiện tại.
					 */
					if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta->meta_key, $meta ) ) {
						continue;
					}
					?>
		<wp:postmeta>
		<wp:meta_key><?php echo wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
		<wp:meta_value><?php echo wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
		</wp:postmeta>
					<?php
	endforeach;

				$_comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved <> 'spam'", $post->ID ) );
				$comments  = array_map( 'get_comment', $_comments );
				foreach ( $comments as $c ) :
					?>
		<wp:comment>
			<wp:comment_id><?php echo (int) $c->comment_ID; ?></wp:comment_id>
			<wp:comment_author><?php echo wxr_cdata( $c->comment_author ); ?></wp:comment_author>
			<wp:comment_author_email><?php echo wxr_cdata( $c->comment_author_email ); ?></wp:comment_author_email>
			<wp:comment_author_url><?php echo sanitize_url( $c->comment_author_url ); ?></wp:comment_author_url>
			<wp:comment_author_IP><?php echo wxr_cdata( $c->comment_author_IP ); ?></wp:comment_author_IP>
			<wp:comment_date><?php echo wxr_cdata( $c->comment_date ); ?></wp:comment_date>
			<wp:comment_date_gmt><?php echo wxr_cdata( $c->comment_date_gmt ); ?></wp:comment_date_gmt>
			<wp:comment_content><?php echo wxr_cdata( $c->comment_content ); ?></wp:comment_content>
			<wp:comment_approved><?php echo wxr_cdata( $c->comment_approved ); ?></wp:comment_approved>
			<wp:comment_type><?php echo wxr_cdata( $c->comment_type ); ?></wp:comment_type>
			<wp:comment_parent><?php echo (int) $c->comment_parent; ?></wp:comment_parent>
			<wp:comment_user_id><?php echo (int) $c->user_id; ?></wp:comment_user_id>
					<?php
					$c_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->commentmeta WHERE comment_id = %d", $c->comment_ID ) );
					foreach ( $c_meta as $meta ) :
						/**
						 * Lọc xem có nên bỏ qua chọn lọc comment meta dùng cho xuất WXR hay không.
						 *
						 * Trả về giá trị truthy từ bộ lọc sẽ bỏ qua đối tượng meta hiện tại
						 * khỏi việc xuất.
						 *
						 * @since 4.0.0
						 *
						 * @param bool   $skip     Có bỏ qua comment meta hiện tại hay không. Mặc định false.
						 * @param string $meta_key Khóa meta hiện tại.
						 * @param object $meta     Đối tượng meta hiện tại.
						 */
						if ( apply_filters( 'wxr_export_skip_commentmeta', false, $meta->meta_key, $meta ) ) {
							continue;
						}
						?>
	<wp:commentmeta>
	<wp:meta_key><?php echo wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
			<wp:meta_value><?php echo wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
			</wp:commentmeta>
					<?php	endforeach; ?>
		</wp:comment>
			<?php	endforeach; ?>
		</item>
				<?php
			}
		}
	}
	?>
</channel>
</rss>
	<?php
}
