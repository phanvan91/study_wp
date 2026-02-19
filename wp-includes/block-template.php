<?php
/**
 * Các hàm tải block template.
 *
 * @package WordPress
 */

/**
 * Thêm các hook cần thiết để xử lý yêu cầu '_wp-find-template'.
 *
 * @access private
 * @since 5.9.0
 */
function _add_template_loader_filters() {
	if ( isset( $_GET['_wp-find-template'] ) && current_theme_supports( 'block-templates' ) ) {
		add_action( 'pre_get_posts', '_resolve_template_for_new_post' );
	}
}

/**
 * Hiển thị màn hình cảnh báo cho các block template rỗng.
 *
 * @since 6.8.0
 *
 * @param WP_Block_Template $block_template Đối tượng block template.
 * @return string HTML của màn hình cảnh báo.
 */
function wp_render_empty_block_template_warning( $block_template ) {
	wp_enqueue_style( 'wp-empty-template-alert' );
	return sprintf(
		/* translators: %1$s: Block template title. %2$s: Empty template warning message. %3$s: Edit template link. %4$s: Edit template button label. */
		'<div id="wp-empty-template-alert">
			<h2>%1$s</h2>
			<p>%2$s</p>
			<a href="%3$s" class="wp-element-button">
				%4$s
			</a>
		</div>',
		esc_html( $block_template->title ),
		__( 'This page is blank because the template is empty. You can reset or customize it in the Site Editor.' ),
		get_edit_post_link( $block_template->wp_id, 'site-editor' ),
		__( 'Edit template' )
	);
}

/**
 * Tìm block template có độ ưu tiên bằng hoặc cao hơn file template PHP cho trước.
 *
 * Bên trong, hàm này truyền nội dung block cần được sử dụng bởi template canvas thông qua biến toàn cục.
 *
 * @since 5.8.0
 * @since 6.3.0 Thêm biến toàn cục `$_wp_current_template_id` để chỉnh sửa template hiện tại trực tiếp từ admin bar.
 *
 * @global string $_wp_current_template_content
 * @global string $_wp_current_template_id
 *
 * @param string   $template  Đường dẫn đến template. Xem locate_template().
 * @param string   $type      Tên file đã được sanitize không có phần mở rộng.
 * @param string[] $templates Danh sách các template ứng viên, theo thứ tự ưu tiên giảm dần.
 * @return string Đường dẫn đến file canvas Site Editor template, hoặc template PHP dự phòng.
 */
function locate_block_template( $template, $type, array $templates ) {
	global $_wp_current_template_content, $_wp_current_template_id;

	if ( ! current_theme_supports( 'block-templates' ) ) {
		return $template;
	}

	if ( $template ) {
		/*
		 * locate_template() đã tìm thấy template PHP tại đường dẫn được chỉ định bởi $template.
		 * Điều đó có nghĩa là chúng ta có ứng viên dự phòng nếu không thể tìm thấy block template
		 * với độ ưu tiên cao hơn.
		 *
		 * Do đó, trước khi tìm kiếm block themes phù hợp, chúng ta rút ngắn danh sách
		 * các template ứng viên cho phù hợp.
		 */

		// Xác định vị trí của $template (không có đường dẫn thư mục theme) trong $templates.
		$relative_template_path = str_replace(
			array( get_stylesheet_directory() . '/', get_template_directory() . '/' ),
			'',
			$template
		);
		$index                  = array_search( $relative_template_path, $templates, true );

		// Nếu thuật toán phân cấp template đã xác định thành công file template PHP,
		// chúng ta chỉ xem xét block templates có độ ưu tiên cao hơn hoặc bằng.
		$templates = array_slice( $templates, 0, $index + 1 );
	}

	$block_template = resolve_block_template( $type, $templates, $template );

	if ( $block_template ) {
		$_wp_current_template_id = $block_template->id;

		if ( empty( $block_template->content ) ) {
			if ( is_user_logged_in() ) {
				$_wp_current_template_content = wp_render_empty_block_template_warning( $block_template );
			} else {
				if ( $block_template->has_theme_file ) {
					// Hiển thị nội dung từ template theme nếu người dùng chưa đăng nhập.
					$theme_template               = _get_block_template_file( 'wp_template', $block_template->slug );
					$_wp_current_template_content = file_get_contents( $theme_template['path'] );
				} else {
					$_wp_current_template_content = $block_template->content;
				}
			}
		} elseif ( ! empty( $block_template->content ) ) {
			$_wp_current_template_content = $block_template->content;
		}
		if ( isset( $_GET['_wp-find-template'] ) ) {
			wp_send_json_success( $block_template );
		}
	} else {
		if ( $template ) {
			return $template;
		}

		if ( 'index' === $type ) {
			if ( isset( $_GET['_wp-find-template'] ) ) {
				wp_send_json_error( array( 'message' => __( 'No matching template found.' ) ) );
			}
		} else {
			return ''; // Để trình tải template tiếp tục tìm kiếm templates.
		}
	}

	// Thêm hooks cho template canvas.
	// Thêm thẻ meta viewport.
	add_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );

	// Render thẻ title với nội dung, bất kể theme có hỗ trợ title-tag hay không.
	remove_action( 'wp_head', '_wp_render_title_tag', 1 );    // Xóa render thẻ title có điều kiện...
	add_action( 'wp_head', '_block_template_render_title_tag', 1 ); // ...và làm cho nó không điều kiện.

	// File này sẽ được include thay vì file template của theme.
	return ABSPATH . WPINC . '/template-canvas.php';
}

/**
 * Trả về 'wp_template' chính xác để render cho loại template yêu cầu.
 *
 * @access private
 * @since 5.8.0
 * @since 5.9.0 Thêm tham số `$fallback_template`.
 *
 * @param string   $template_type      Loại template hiện tại.
 * @param string[] $template_hierarchy Phân cấp template hiện tại, sắp xếp theo thứ tự ưu tiên.
 * @param string   $fallback_template  Template PHP dự phòng để sử dụng nếu không tìm thấy block template phù hợp.
 * @return WP_Block_Template|null Đối tượng template, hoặc null nếu không tìm thấy.
 */
function resolve_block_template( $template_type, $template_hierarchy, $fallback_template ) {
	if ( ! $template_type ) {
		return null;
	}

	if ( empty( $template_hierarchy ) ) {
		$template_hierarchy = array( $template_type );
	}

	$slugs = array_map(
		'_strip_template_file_suffix',
		$template_hierarchy
	);

	// Tìm tất cả các 'wp_template' post tiềm năng khớp với phân cấp.
	$query     = array(
		'slug__in' => $slugs,
	);
	$templates = get_block_templates( $query );

	// Sắp xếp các templates theo thứ tự ưu tiên slug.
	// Xây dựng bản đồ slug template theo thứ tự ưu tiên trong phân cấp hiện tại.
	$slug_priorities = array_flip( $slugs );

	usort(
		$templates,
		static function ( $template_a, $template_b ) use ( $slug_priorities ) {
			return $slug_priorities[ $template_a->slug ] - $slug_priorities[ $template_b->slug ];
		}
	);

	$theme_base_path        = get_stylesheet_directory() . DIRECTORY_SEPARATOR;
	$parent_theme_base_path = get_template_directory() . DIRECTORY_SEPARATOR;

	// Theme đang hoạt động có phải là child theme không, và template PHP dự phòng có thuộc về nó không?
	if (
		str_starts_with( $fallback_template, $theme_base_path ) &&
		! str_contains( $fallback_template, $parent_theme_base_path )
	) {
		$fallback_template_slug = substr(
			$fallback_template,
			// Vị trí bắt đầu của slug.
			strpos( $fallback_template, $theme_base_path ) + strlen( $theme_base_path ),
			// Xóa hậu tố '.php'.
			-4
		);

		// Slug của block template ứng viên có giống với slug template PHP dự phòng không?
		if (
			count( $templates ) &&
			$fallback_template_slug === $templates[0]->slug &&
			'theme' === $templates[0]->source
		) {
			// Rất tiếc, chúng ta không thể tin tưởng $templates[0]->theme, vì nó luôn
			// được đặt thành slug của theme đang hoạt động bởi _build_block_template_result_from_file(),
			// ngay cả khi block template thực sự đến từ theme cha của theme đang hoạt động.
			// (Lý do là chúng ta muốn nó được liên kết với theme đang hoạt động
			// -- không phải theme cha -- khi chúng ta chỉnh sửa và lưu vào DB dưới dạng wp_template CPT.)
			// Thay vào đó, chúng ta sử dụng _get_block_template_file() để xác định vị trí file block template.
			$template_file = _get_block_template_file( 'wp_template', $fallback_template_slug );
			if ( $template_file && get_template() === $template_file['theme'] ) {
				// Block template thuộc về theme cha, vì vậy chúng ta
				// phải ưu tiên template PHP của child theme.
				array_shift( $templates );
			}
		}
	}

	return count( $templates ) ? $templates[0] : null;
}

/**
 * Hiển thị thẻ title với nội dung, bất kể theme có hỗ trợ title-tag hay không.
 *
 * @access private
 * @since 5.8.0
 *
 * @see _wp_render_title_tag()
 */
function _block_template_render_title_tag() {
	echo '<title>' . wp_get_document_title() . '</title>' . "\n";
}

/**
 * Trả về markup cho template hiện tại.
 *
 * @access private
 * @since 5.8.0
 *
 * @global string   $_wp_current_template_id
 * @global string   $_wp_current_template_content
 * @global WP_Embed $wp_embed                     Đối tượng WordPress Embed.
 * @global WP_Query $wp_query                     Đối tượng WordPress Query.
 *
 * @return string Markup block template.
 */
function get_the_block_template_html() {
	global $_wp_current_template_id, $_wp_current_template_content, $wp_embed, $wp_query;

	if ( ! $_wp_current_template_content ) {
		if ( is_user_logged_in() ) {
			return '<h1>' . esc_html__( 'No matching template found' ) . '</h1>';
		}
		return;
	}

	$content = $wp_embed->run_shortcode( $_wp_current_template_content );
	$content = $wp_embed->autoembed( $content );
	$content = shortcode_unautop( $content );
	$content = do_shortcode( $content );

	/*
	 * Hầu hết block themes bỏ qua các block `core/query` và `core/post-template` trong template nội dung singular.
	 * Mặc dù về mặt kỹ thuật vẫn hoạt động vì template nội dung singular luôn chỉ cho một bài viết, nhưng nó dẫn đến
	 * vòng lặp truy vấn chính không bao giờ được vào, gây ra lỗi trong lõi và hệ sinh thái plugin.
	 *
	 * Giải pháp tạm thời bên dưới đảm bảo vòng lặp được bắt đầu ngay cả với những template singular đó. Vòng lặp while
	 * theo định nghĩa chỉ đi qua một lần lặp, tức là `do_blocks()` chỉ được gọi một lần. Các kiểm tra bảo vệ bổ sung
	 * được bao gồm để đảm bảo vòng lặp truy vấn chính không bị can thiệp và thực sự chỉ bao gồm một bài viết duy nhất.
	 *
	 * Ngay cả khi block template chứa block `core/query` và `core/post-template` tham chiếu đến vòng lặp truy vấn chính,
	 * nó sẽ không gây ra lỗi vì nó sẽ sử dụng một instance nhân bản và đi qua cùng vòng lặp của một bài viết duy nhất,
	 * bên trong vòng lặp truy vấn chính thực tế.
	 *
	 * Logic đặc biệt này nên được bỏ qua nếu template hiện tại không đến từ theme hiện tại, trong trường hợp đó
	 * nó đã được chèn bởi plugin bằng cách chiếm quyền cơ chế tải block template. Trong trường hợp đó, logic tùy chỉnh
	 * hoàn toàn có thể được áp dụng, không thể dự đoán và do đó an toàn hơn nếu bỏ qua xử lý đặc biệt này.
	 */
	if (
		$_wp_current_template_id &&
		str_starts_with( $_wp_current_template_id, get_stylesheet() . '//' ) &&
		is_singular() &&
		1 === $wp_query->post_count &&
		have_posts()
	) {
		while ( have_posts() ) {
			the_post();
			$content = do_blocks( $content );
		}
	} else {
		$content = do_blocks( $content );
	}

	$content = wptexturize( $content );
	$content = convert_smilies( $content );
	$content = wp_filter_content_tags( $content, 'template' );
	$content = str_replace( ']]>', ']]&gt;', $content );

	// Bọc block template trong .wp-site-blocks để cho phép các style con cháu cụ thể
	// (ví dụ: `.wp-site-blocks > *`).
	return '<div class="wp-site-blocks">' . $content . '</div>';
}

/**
 * Render thẻ meta 'viewport'.
 *
 * Được gắn vào {@see 'wp_head'} để tách đầu ra của nó khỏi template canvas mặc định.
 *
 * @access private
 * @since 5.8.0
 */
function _block_template_viewport_meta_tag() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
}

/**
 * Xóa hậu tố .php hoặc .html khỏi tên file template.
 *
 * @access private
 * @since 5.8.0
 *
 * @param string $template_file Tên file template.
 * @return string Tên file template không có phần mở rộng.
 */
function _strip_template_file_suffix( $template_file ) {
	return preg_replace( '/\.(php|html)$/', '', $template_file );
}

/**
 * Xóa chi tiết bài viết khỏi block context khi render block template.
 *
 * @access private
 * @since 5.8.0
 *
 * @param array $context Context mặc định.
 *
 * @return array Context đã được lọc.
 */
function _block_template_render_without_post_block_context( $context ) {
	/*
	 * Khi tải template trực tiếp mà không thông qua trang phân giải nó,
	 * context ID và loại bài viết cấp cao nhất được đặt thành template đó.
	 * Templates chỉ là cấu trúc của site, và chúng không nên có sẵn
	 * dưới dạng context bài viết vì các block như Post Content sẽ đệ quy vô hạn.
	 */
	if ( isset( $context['postType'] ) && 'wp_template' === $context['postType'] ) {
		unset( $context['postId'] );
		unset( $context['postType'] );
	}

	return $context;
}

/**
 * Thiết lập WP_Query hiện tại để trả về các bài viết auto-draft.
 *
 * Trạng thái auto-draft chỉ ra một bài viết mới, vì vậy cho phép instance WP_Query
 * trả về bài viết auto-draft cho việc phân giải template khi chỉnh sửa bài viết mới.
 *
 * @access private
 * @since 5.9.0
 *
 * @param WP_Query $wp_query Instance WP_Query hiện tại, truyền theo tham chiếu.
 */
function _resolve_template_for_new_post( $wp_query ) {
	if ( ! $wp_query->is_main_query() ) {
		return;
	}

	remove_filter( 'pre_get_posts', '_resolve_template_for_new_post' );

	// Trang.
	$page_id = isset( $wp_query->query['page_id'] ) ? $wp_query->query['page_id'] : null;

	// Bài viết, bao gồm cả loại bài viết tùy chỉnh.
	$p = isset( $wp_query->query['p'] ) ? $wp_query->query['p'] : null;

	$post_id = $page_id ? $page_id : $p;
	$post    = get_post( $post_id );

	if (
		$post &&
		'auto-draft' === $post->post_status &&
		current_user_can( 'edit_post', $post->ID )
	) {
		$wp_query->set( 'post_status', 'auto-draft' );
	}
}

/**
 * Đăng ký một block template.
 *
 * @since 6.7.0
 *
 * @param string       $template_name  Tên template dạng `plugin_uri//template_name`.
 * @param array|string $args           {
 *     @type string        $title                 Tùy chọn. Tiêu đề của template sẽ hiển thị trong Site Editor
 *                                                và các phần tử giao diện khác.
 *     @type string        $description           Tùy chọn. Mô tả template sẽ hiển thị trong Site Editor.
 *     @type string        $content               Tùy chọn. Nội dung mặc định của template sẽ được sử dụng khi
 *                                                template được render hoặc chỉnh sửa trong trình soạn thảo.
 *     @type string[]      $post_types            Tùy chọn. Mảng các loại bài viết mà template nên có sẵn.
 *     @type string        $plugin                Tùy chọn. Slug của plugin đăng ký template.
 * }
 * @return WP_Block_Template|WP_Error Đối tượng template đã đăng ký khi thành công, đối tượng WP_Error khi thất bại.
 */
function register_block_template( $template_name, $args = array() ) {
	return WP_Block_Templates_Registry::get_instance()->register( $template_name, $args );
}

/**
 * Hủy đăng ký một block template.
 *
 * @since 6.7.0
 *
 * @param string $template_name Tên template dạng `plugin_uri//template_name`.
 * @return WP_Block_Template|WP_Error Đối tượng template đã hủy đăng ký khi thành công, đối tượng WP_Error khi thất bại hoặc nếu
 *                                    template không tồn tại.
 */
function unregister_block_template( $template_name ) {
	return WP_Block_Templates_Registry::get_instance()->unregister( $template_name );
}
