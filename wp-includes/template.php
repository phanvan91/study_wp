<?php
/**
 * Các hàm tải template.
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Lấy đường dẫn đến một template.
 *
 * Được sử dụng để nhanh chóng lấy đường dẫn của một template mà không cần phần
 * mở rộng file. Nó cũng sẽ kiểm tra theme cha, nếu file tồn tại, bằng cách
 * sử dụng locate_template(). Cho phép định vị template tổng quát hơn
 * mà không cần sử dụng các hàm get_*_template() khác.
 *
 * @since 1.5.0
 *
 * @param string   $type      Tên file không có phần mở rộng.
 * @param string[] $templates Danh sách tùy chọn các template ứng viên.
 * @return string Đường dẫn đầy đủ đến file template.
 */
function get_query_template( $type, $templates = array() ) {
	$type = preg_replace( '|[^a-z0-9-]+|', '', $type );

	if ( empty( $templates ) ) {
		$templates = array( "{$type}.php" );
	}

	/**
	 * Lọc danh sách tên file template được tìm kiếm khi lấy template để sử dụng.
	 *
	 * Phần động của tên hook, `$type`, đề cập đến tên file -- trừ phần mở rộng
	 * và bất kỳ ký tự không phải chữ-số nào phân tách các từ -- của file cần tải.
	 * Phần tử cuối cùng trong mảng luôn là template dự phòng cho loại truy vấn này.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `404_template_hierarchy`
	 *  - `archive_template_hierarchy`
	 *  - `attachment_template_hierarchy`
	 *  - `author_template_hierarchy`
	 *  - `category_template_hierarchy`
	 *  - `date_template_hierarchy`
	 *  - `embed_template_hierarchy`
	 *  - `frontpage_template_hierarchy`
	 *  - `home_template_hierarchy`
	 *  - `index_template_hierarchy`
	 *  - `page_template_hierarchy`
	 *  - `paged_template_hierarchy`
	 *  - `privacypolicy_template_hierarchy`
	 *  - `search_template_hierarchy`
	 *  - `single_template_hierarchy`
	 *  - `singular_template_hierarchy`
	 *  - `tag_template_hierarchy`
	 *  - `taxonomy_template_hierarchy`
	 *
	 * @since 4.7.0
	 *
	 * @param string[] $templates Danh sách các template ứng viên, theo thứ tự ưu tiên giảm dần.
	 */
	$templates = apply_filters( "{$type}_template_hierarchy", $templates );

	$template = locate_template( $templates );

	$template = locate_block_template( $template, $type, $templates );

	/**
	 * Lọc đường dẫn của template được truy vấn theo loại.
	 *
	 * Phần động của tên hook, `$type`, đề cập đến tên file -- trừ phần mở rộng
	 * và bất kỳ ký tự không phải chữ-số nào phân tách các từ -- của file cần tải.
	 * Hook này cũng áp dụng cho các loại file khác nhau được tải như một phần của Hệ thống phân cấp Template.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `404_template`
	 *  - `archive_template`
	 *  - `attachment_template`
	 *  - `author_template`
	 *  - `category_template`
	 *  - `date_template`
	 *  - `embed_template`
	 *  - `frontpage_template`
	 *  - `home_template`
	 *  - `index_template`
	 *  - `page_template`
	 *  - `paged_template`
	 *  - `privacypolicy_template`
	 *  - `search_template`
	 *  - `single_template`
	 *  - `singular_template`
	 *  - `tag_template`
	 *  - `taxonomy_template`
	 *
	 * @since 1.5.0
	 * @since 4.8.0 Các tham số `$type` và `$templates` đã được thêm vào.
	 *
	 * @param string   $template  Đường dẫn đến template. Xem locate_template().
	 * @param string   $type      Tên file đã được làm sạch không có phần mở rộng.
	 * @param string[] $templates Danh sách các template ứng viên, theo thứ tự ưu tiên giảm dần.
	 */
	return apply_filters( "{$type}_template", $template, $type, $templates );
}

/**
 * Lấy đường dẫn của template index trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'index'.
 *
 * @since 3.0.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template index.
 */
function get_index_template() {
	return get_query_template( 'index' );
}

/**
 * Lấy đường dẫn của template 404 trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là '404'.
 *
 * @since 1.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template 404.
 */
function get_404_template() {
	return get_query_template( '404' );
}

/**
 * Lấy đường dẫn của template lưu trữ trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'archive'.
 *
 * @since 1.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template lưu trữ.
 */
function get_archive_template() {
	$post_types = array_filter( (array) get_query_var( 'post_type' ) );

	$templates = array();

	if ( count( $post_types ) === 1 ) {
		$post_type   = reset( $post_types );
		$templates[] = "archive-{$post_type}.php";
	}
	$templates[] = 'archive.php';

	return get_query_template( 'archive', $templates );
}

/**
 * Lấy đường dẫn của template lưu trữ loại bài viết trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'archive'.
 *
 * @since 3.7.0
 *
 * @see get_archive_template()
 *
 * @return string Đường dẫn đầy đủ đến file template lưu trữ.
 */
function get_post_type_archive_template() {
	$post_type = get_query_var( 'post_type' );
	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}

	$obj = get_post_type_object( $post_type );
	if ( ! ( $obj instanceof WP_Post_Type ) || ! $obj->has_archive ) {
		return '';
	}

	return get_archive_template();
}

/**
 * Lấy đường dẫn của template tác giả trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. author-{nicename}.php
 * 2. author-{id}.php
 * 3. author.php
 *
 * Ví dụ:
 *
 * 1. author-john.php
 * 2. author-1.php
 * 3. author.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'author'.
 *
 * @since 1.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template tác giả.
 */
function get_author_template() {
	$author = get_queried_object();

	$templates = array();

	if ( $author instanceof WP_User ) {
		$templates[] = "author-{$author->user_nicename}.php";
		$templates[] = "author-{$author->ID}.php";
	}
	$templates[] = 'author.php';

	return get_query_template( 'author', $templates );
}

/**
 * Lấy đường dẫn của template chuyên mục trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. category-{slug}.php
 * 2. category-{id}.php
 * 3. category.php
 *
 * Ví dụ:
 *
 * 1. category-news.php
 * 2. category-2.php
 * 3. category.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'category'.
 *
 * @since 1.5.0
 * @since 4.7.0 Dạng giải mã của `category-{slug}.php` đã được thêm vào đầu hệ thống
 *              phân cấp template khi slug chuyên mục chứa ký tự đa byte.
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template chuyên mục.
 */
function get_category_template() {
	$category = get_queried_object();

	$templates = array();

	if ( ! empty( $category->slug ) ) {

		$slug_decoded = urldecode( $category->slug );
		if ( $slug_decoded !== $category->slug ) {
			$templates[] = "category-{$slug_decoded}.php";
		}

		$templates[] = "category-{$category->slug}.php";
		$templates[] = "category-{$category->term_id}.php";
	}
	$templates[] = 'category.php';

	return get_query_template( 'category', $templates );
}

/**
 * Lấy đường dẫn của template thẻ trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. tag-{slug}.php
 * 2. tag-{id}.php
 * 3. tag.php
 *
 * Ví dụ:
 *
 * 1. tag-wordpress.php
 * 2. tag-3.php
 * 3. tag.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'tag'.
 *
 * @since 2.3.0
 * @since 4.7.0 Dạng giải mã của `tag-{slug}.php` đã được thêm vào đầu hệ thống
 *              phân cấp template khi slug thẻ chứa ký tự đa byte.
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template thẻ.
 */
function get_tag_template() {
	$tag = get_queried_object();

	$templates = array();

	if ( ! empty( $tag->slug ) ) {

		$slug_decoded = urldecode( $tag->slug );
		if ( $slug_decoded !== $tag->slug ) {
			$templates[] = "tag-{$slug_decoded}.php";
		}

		$templates[] = "tag-{$tag->slug}.php";
		$templates[] = "tag-{$tag->term_id}.php";
	}
	$templates[] = 'tag.php';

	return get_query_template( 'tag', $templates );
}

/**
 * Lấy đường dẫn của template thuật ngữ taxonomy tùy chỉnh trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. taxonomy-{taxonomy_slug}-{term_slug}.php
 * 2. taxonomy-{taxonomy_slug}.php
 * 3. taxonomy.php
 *
 * Ví dụ:
 *
 * 1. taxonomy-location-texas.php
 * 2. taxonomy-location.php
 * 3. taxonomy.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'taxonomy'.
 *
 * @since 2.5.0
 * @since 4.7.0 Dạng giải mã của `taxonomy-{taxonomy_slug}-{term_slug}.php` đã được thêm vào đầu
 *              hệ thống phân cấp template khi slug thuật ngữ chứa ký tự đa byte.
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template thuật ngữ taxonomy tùy chỉnh.
 */
function get_taxonomy_template() {
	$term = get_queried_object();

	$templates = array();

	if ( ! empty( $term->slug ) ) {
		$taxonomy = $term->taxonomy;

		$slug_decoded = urldecode( $term->slug );
		if ( $slug_decoded !== $term->slug ) {
			$templates[] = "taxonomy-$taxonomy-{$slug_decoded}.php";
		}

		$templates[] = "taxonomy-$taxonomy-{$term->slug}.php";
		$templates[] = "taxonomy-$taxonomy.php";
	}
	$templates[] = 'taxonomy.php';

	return get_query_template( 'taxonomy', $templates );
}

/**
 * Lấy đường dẫn của template ngày trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'date'.
 *
 * @since 1.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template ngày.
 */
function get_date_template() {
	return get_query_template( 'date' );
}

/**
 * Lấy đường dẫn của template trang chủ trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'home'.
 *
 * @since 1.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template trang chủ.
 */
function get_home_template() {
	$templates = array( 'home.php', 'index.php' );

	return get_query_template( 'home', $templates );
}

/**
 * Lấy đường dẫn của template trang đầu trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'frontpage'.
 *
 * @since 3.0.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template trang đầu.
 */
function get_front_page_template() {
	$templates = array( 'front-page.php' );

	return get_query_template( 'frontpage', $templates );
}

/**
 * Lấy đường dẫn của template trang Chính sách Bảo mật trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'privacypolicy'.
 *
 * @since 5.2.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template chính sách bảo mật.
 */
function get_privacy_policy_template() {
	$templates = array( 'privacy-policy.php' );

	return get_query_template( 'privacypolicy', $templates );
}

/**
 * Lấy đường dẫn của template trang trong template hiện tại hoặc template cha.
 *
 * Lưu ý: Đối với theme khối, hãy sử dụng hàm locate_block_template() thay thế.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. {Page Template}.php
 * 2. page-{page_name}.php
 * 3. page-{id}.php
 * 4. page.php
 *
 * Ví dụ:
 *
 * 1. page-templates/full-width.php
 * 2. page-about.php
 * 3. page-4.php
 * 4. page.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'page'.
 *
 * @since 1.5.0
 * @since 4.7.0 Dạng giải mã của `page-{page_name}.php` đã được thêm vào đầu hệ thống
 *              phân cấp template khi tên trang chứa ký tự đa byte.
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template trang.
 */
function get_page_template() {
	$id       = get_queried_object_id();
	$template = get_page_template_slug();
	$pagename = get_query_var( 'pagename' );

	if ( ! $pagename && $id ) {
		/*
		 * Nếu một trang tĩnh được đặt làm trang đầu, $pagename sẽ không được thiết lập.
		 * Lấy nó từ đối tượng được truy vấn.
		 */
		$post = get_queried_object();
		if ( $post ) {
			$pagename = $post->post_name;
		}
	}

	$templates = array();
	if ( $template && 0 === validate_file( $template ) ) {
		$templates[] = $template;
	}
	if ( $pagename ) {
		$pagename_decoded = urldecode( $pagename );
		if ( $pagename_decoded !== $pagename ) {
			$templates[] = "page-{$pagename_decoded}.php";
		}
		$templates[] = "page-{$pagename}.php";
	}
	if ( $id ) {
		$templates[] = "page-{$id}.php";
	}
	$templates[] = 'page.php';

	return get_query_template( 'page', $templates );
}

/**
 * Lấy đường dẫn của template tìm kiếm trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'search'.
 *
 * @since 1.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template tìm kiếm.
 */
function get_search_template() {
	return get_query_template( 'search' );
}

/**
 * Lấy đường dẫn của template bài viết đơn trong template hiện tại hoặc template cha.
 * Áp dụng cho bài viết đơn, tệp đính kèm đơn, và loại bài viết tùy chỉnh đơn.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. {Post Type Template}.php
 * 2. single-{post_type}-{post_name}.php
 * 3. single-{post_type}.php
 * 4. single.php
 *
 * Ví dụ:
 *
 * 1. templates/full-width.php
 * 2. single-post-hello-world.php
 * 3. single-post.php
 * 4. single.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'single'.
 *
 * @since 1.5.0
 * @since 4.4.0 `single-{post_type}-{post_name}.php` đã được thêm vào đầu hệ thống phân cấp template.
 * @since 4.7.0 Dạng giải mã của `single-{post_type}-{post_name}.php` đã được thêm vào đầu
 *              hệ thống phân cấp template khi tên bài viết chứa ký tự đa byte.
 * @since 4.7.0 `{Post Type Template}.php` đã được thêm vào đầu hệ thống phân cấp template.
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template bài viết đơn.
 */
function get_single_template() {
	$object = get_queried_object();

	$templates = array();

	if ( ! empty( $object->post_type ) ) {
		$template = get_page_template_slug( $object );
		if ( $template && 0 === validate_file( $template ) ) {
			$templates[] = $template;
		}

		$name_decoded = urldecode( $object->post_name );
		if ( $name_decoded !== $object->post_name ) {
			$templates[] = "single-{$object->post_type}-{$name_decoded}.php";
		}

		$templates[] = "single-{$object->post_type}-{$object->post_name}.php";
		$templates[] = "single-{$object->post_type}.php";
	}

	$templates[] = 'single.php';

	return get_query_template( 'single', $templates );
}

/**
 * Lấy đường dẫn template nhúng trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. embed-{post_type}-{post_format}.php
 * 2. embed-{post_type}.php
 * 3. embed.php
 *
 * Ví dụ:
 *
 * 1. embed-post-audio.php
 * 2. embed-post.php
 * 3. embed.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'embed'.
 *
 * @since 4.5.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template nhúng.
 */
function get_embed_template() {
	$object = get_queried_object();

	$templates = array();

	if ( ! empty( $object->post_type ) ) {
		$post_format = get_post_format( $object );
		if ( $post_format ) {
			$templates[] = "embed-{$object->post_type}-{$post_format}.php";
		}
		$templates[] = "embed-{$object->post_type}.php";
	}

	$templates[] = 'embed.php';

	return get_query_template( 'embed', $templates );
}

/**
 * Lấy đường dẫn của template số ít trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'singular'.
 *
 * @since 4.3.0
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template số ít
 */
function get_singular_template() {
	return get_query_template( 'singular' );
}

/**
 * Lấy đường dẫn của template tệp đính kèm trong template hiện tại hoặc template cha.
 *
 * Hệ thống phân cấp cho template này như sau:
 *
 * 1. {mime_type}-{sub_type}.php
 * 2. {sub_type}.php
 * 3. {mime_type}.php
 * 4. attachment.php
 *
 * Ví dụ:
 *
 * 1. image-jpeg.php
 * 2. jpeg.php
 * 3. image.php
 * 4. attachment.php
 *
 * Hệ thống phân cấp template và đường dẫn template có thể được lọc qua các hook động
 * {@see '$type_template_hierarchy'} và {@see '$type_template'}, trong đó `$type` là 'attachment'.
 *
 * @since 2.0.0
 * @since 4.3.0 Thứ tự logic kiểu mime đã được đảo ngược để hệ thống phân cấp hợp lý hơn.
 *
 * @see get_query_template()
 *
 * @return string Đường dẫn đầy đủ đến file template tệp đính kèm.
 */
function get_attachment_template() {
	$attachment = get_queried_object();

	$templates = array();

	if ( $attachment ) {
		if ( str_contains( $attachment->post_mime_type, '/' ) ) {
			list( $type, $subtype ) = explode( '/', $attachment->post_mime_type );
		} else {
			list( $type, $subtype ) = array( $attachment->post_mime_type, '' );
		}

		if ( ! empty( $subtype ) ) {
			$templates[] = "{$type}-{$subtype}.php";
			$templates[] = "{$subtype}.php";
		}
		$templates[] = "{$type}.php";
	}
	$templates[] = 'attachment.php';

	return get_query_template( 'attachment', $templates );
}

/**
 * Thiết lập các biến toàn cục được sử dụng để tải template.
 *
 * @since 6.5.0
 *
 * @global string $wp_stylesheet_path Đường dẫn đến thư mục stylesheet của theme hiện tại.
 * @global string $wp_template_path   Đường dẫn đến thư mục template của theme hiện tại.
 */
function wp_set_template_globals() {
	global $wp_stylesheet_path, $wp_template_path;

	$wp_stylesheet_path = get_stylesheet_directory();
	$wp_template_path   = get_template_directory();
}

/**
 * Lấy tên của file template có ưu tiên cao nhất mà tồn tại.
 *
 * Tìm kiếm trong thư mục stylesheet trước thư mục template và
 * wp-includes/theme-compat để các theme kế thừa từ theme cha
 * có thể chỉ cần ghi đè một file.
 *
 * @since 2.7.0
 * @since 5.5.0 Tham số `$args` đã được thêm vào.
 *
 * @global string $wp_stylesheet_path Đường dẫn đến thư mục stylesheet của theme hiện tại.
 * @global string $wp_template_path   Đường dẫn đến thư mục template của theme hiện tại.
 *
 * @param string|array $template_names (Các) file template cần tìm kiếm, theo thứ tự.
 * @param bool         $load           Nếu true, file template sẽ được tải nếu tìm thấy.
 * @param bool         $load_once      Sử dụng require_once hay require. Không có tác dụng nếu `$load` là false.
 *                                     Mặc định true.
 * @param array        $args           Tùy chọn. Các đối số bổ sung được truyền vào template.
 *                                     Mặc định mảng rỗng.
 * @return string Tên file template nếu tìm thấy.
 */
function locate_template( $template_names, $load = false, $load_once = true, $args = array() ) {
	global $wp_stylesheet_path, $wp_template_path;

	if ( ! isset( $wp_stylesheet_path ) || ! isset( $wp_template_path ) ) {
		wp_set_template_globals();
	}

	$is_child_theme = is_child_theme();

	$located = '';
	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name ) {
			continue;
		}
		if ( file_exists( $wp_stylesheet_path . '/' . $template_name ) ) {
			$located = $wp_stylesheet_path . '/' . $template_name;
			break;
		} elseif ( $is_child_theme && file_exists( $wp_template_path . '/' . $template_name ) ) {
			$located = $wp_template_path . '/' . $template_name;
			break;
		} elseif ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
			$located = ABSPATH . WPINC . '/theme-compat/' . $template_name;
			break;
		}
	}

	if ( $load && '' !== $located ) {
		load_template( $located, $load_once, $args );
	}

	return $located;
}

/**
 * Nạp file template với môi trường WordPress.
 *
 * Các biến toàn cục được thiết lập cho file template để đảm bảo rằng môi trường
 * WordPress có sẵn từ bên trong hàm. Các biến truy vấn cũng có sẵn.
 *
 * @since 1.5.0
 * @since 5.5.0 Tham số `$args` đã được thêm vào.
 *
 * @global array      $posts
 * @global WP_Post    $post          Đối tượng bài viết toàn cục.
 * @global bool       $wp_did_header
 * @global WP_Query   $wp_query      Đối tượng truy vấn WordPress.
 * @global WP_Rewrite $wp_rewrite    Thành phần viết lại WordPress.
 * @global wpdb       $wpdb          Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @global string     $wp_version
 * @global WP         $wp            Thể hiện môi trường WordPress hiện tại.
 * @global int        $id
 * @global WP_Comment $comment       Đối tượng bình luận toàn cục.
 * @global int        $user_ID
 *
 * @param string $_template_file Đường dẫn đến file template.
 * @param bool   $load_once      Sử dụng require_once hay require. Mặc định true.
 * @param array  $args           Tùy chọn. Các đối số bổ sung được truyền vào template.
 *                               Mặc định mảng rỗng.
 */
function load_template( $_template_file, $load_once = true, $args = array() ) {
	global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

	if ( is_array( $wp_query->query_vars ) ) {
		/*
		 * Việc sử dụng extract() này không thể bị loại bỏ. Có nhiều cách mà các template
		 * có thể phụ thuộc vào các biến mà nó tạo ra, và không có cách nào để
		 * phát hiện và đánh dấu lỗi thời.
		 *
		 * Truyền cờ EXTR_SKIP là tùy chọn an toàn nhất, đảm bảo các biến toàn cục
		 * và biến hàm không thể bị ghi đè.
		 */
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $wp_query->query_vars, EXTR_SKIP );
	}

	if ( isset( $s ) ) {
		$s = esc_attr( $s );
	}

	/**
	 * Kích hoạt trước khi một file template được tải.
	 *
	 * @since 6.1.0
	 *
	 * @param string $_template_file Đường dẫn đầy đủ đến file template.
	 * @param bool   $load_once      Sử dụng require_once hay require.
	 * @param array  $args           Các đối số bổ sung được truyền vào template.
	 */
	do_action( 'wp_before_load_template', $_template_file, $load_once, $args );

	if ( $load_once ) {
		require_once $_template_file;
	} else {
		require $_template_file;
	}

	/**
	 * Kích hoạt sau khi một file template được tải.
	 *
	 * @since 6.1.0
	 *
	 * @param string $_template_file Đường dẫn đầy đủ đến file template.
	 * @param bool   $load_once      Sử dụng require_once hay require.
	 * @param array  $args           Các đối số bổ sung được truyền vào template.
	 */
	do_action( 'wp_after_load_template', $_template_file, $load_once, $args );
}
