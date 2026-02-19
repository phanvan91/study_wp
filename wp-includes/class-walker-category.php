<?php
/**
 * API Taxonomy: Lớp Walker_Category
 *
 * @package WordPress
 * @subpackage Template
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để tạo danh sách HTML các chuyên mục.
 *
 * @since 2.1.0
 *
 * @see Walker
 */
class Walker_Category extends Walker {

	/**
	 * Loại mà lớp xử lý.
	 *
	 * @since 2.1.0
	 * @var string
	 *
	 * @see Walker::$tree_type
	 */
	public $tree_type = 'category';

	/**
	 * Các trường cơ sở dữ liệu được sử dụng.
	 *
	 * @since 2.1.0
	 * @var string[]
	 *
	 * @see Walker::$db_fields
	 * @todo Tách rời phần này
	 */
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);

	/**
	 * Bắt đầu danh sách trước khi các phần tử được thêm vào.
	 *
	 * @since 2.1.0
	 *
	 * @see Walker::start_lvl()
	 *
	 * @param string $output Dùng để nối thêm nội dung. Truyền theo tham chiếu.
	 * @param int    $depth  Tùy chọn. Độ sâu của chuyên mục. Dùng để thụt lề tab. Mặc định 0.
	 * @param array  $args   Tùy chọn. Mảng các tham số. Chỉ nối thêm nội dung nếu tham số style
	 *                       có giá trị 'list'. Xem wp_list_categories(). Mặc định mảng rỗng.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		if ( 'list' !== $args['style'] ) {
			return;
		}

		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent<ul class='children'>\n";
	}

	/**
	 * Kết thúc danh sách sau khi các phần tử được thêm vào.
	 *
	 * @since 2.1.0
	 *
	 * @see Walker::end_lvl()
	 *
	 * @param string $output Dùng để nối thêm nội dung. Truyền theo tham chiếu.
	 * @param int    $depth  Tùy chọn. Độ sâu của chuyên mục. Dùng để thụt lề tab. Mặc định 0.
	 * @param array  $args   Tùy chọn. Mảng các tham số. Chỉ nối thêm nội dung nếu tham số style
	 *                       có giá trị 'list'. Xem wp_list_categories(). Mặc định mảng rỗng.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		if ( 'list' !== $args['style'] ) {
			return;
		}

		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	/**
	 * Bắt đầu xuất phần tử.
	 *
	 * @since 2.1.0
	 * @since 5.9.0 Đổi tên `$category` thành `$data_object` và `$id` thành `$current_object_id`
	 *              để khớp với lớp cha hỗ trợ tham số đặt tên trong PHP 8.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string  $output            Dùng để nối thêm nội dung (truyền theo tham chiếu).
	 * @param WP_Term $data_object       Đối tượng dữ liệu chuyên mục.
	 * @param int     $depth             Tùy chọn. Độ sâu chuyên mục so với cha. Mặc định 0.
	 * @param array   $args              Tùy chọn. Mảng các tham số. Xem wp_list_categories().
	 *                                   Mặc định mảng rỗng.
	 * @param int     $current_object_id Tùy chọn. ID của chuyên mục hiện tại. Mặc định 0.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		// Khôi phục tên mô tả, cụ thể hơn để sử dụng trong phương thức này.
		$category = $data_object;

		/** Bộ lọc này được ghi chú tài liệu trong wp-includes/category-template.php */
		$cat_name = apply_filters( 'list_cats', esc_attr( $category->name ), $category );

		// Không tạo phần tử nếu tên chuyên mục rỗng.
		if ( '' === $cat_name ) {
			return;
		}

		$atts         = array();
		$atts['href'] = get_term_link( $category );

		if ( $args['use_desc_for_title'] && ! empty( $category->description ) ) {
			/**
			 * Lọc mô tả chuyên mục để hiển thị.
			 *
			 * @since 1.2.0
			 *
			 * @param string  $description Mô tả chuyên mục.
			 * @param WP_Term $category    Đối tượng chuyên mục.
			 */
			$atts['title'] = strip_tags( apply_filters( 'category_description', $category->description, $category ) );
		}

		/**
		 * Lọc các thuộc tính HTML áp dụng cho phần tử neo của mục danh sách chuyên mục.
		 *
		 * @since 5.2.0
		 *
		 * @param array   $atts {
		 *     Các thuộc tính HTML áp dụng cho phần tử `<a>` của mục danh sách, chuỗi rỗng bị bỏ qua.
		 *
		 *     @type string $href  Thuộc tính href.
		 *     @type string $title Thuộc tính title.
		 * }
		 * @param WP_Term $category          Đối tượng dữ liệu term.
		 * @param int     $depth             Độ sâu chuyên mục, dùng để đệm.
		 * @param array   $args              Mảng các tham số.
		 * @param int     $current_object_id ID của chuyên mục hiện tại.
		 */
		$atts = apply_filters( 'category_list_link_attributes', $atts, $category, $depth, $args, $current_object_id );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$link = sprintf(
			'<a%s>%s</a>',
			$attributes,
			$cat_name
		);

		if ( ! empty( $args['feed_image'] ) || ! empty( $args['feed'] ) ) {
			$link .= ' ';

			if ( empty( $args['feed_image'] ) ) {
				$link .= '(';
			}

			$link .= '<a href="' . esc_url( get_term_feed_link( $category, $category->taxonomy, $args['feed_type'] ) ) . '"';

			if ( empty( $args['feed'] ) ) {
				/* translators: %s: Tên chuyên mục. */
				$alt = ' alt="' . sprintf( __( 'Feed for all posts filed under %s' ), $cat_name ) . '"';
			} else {
				$alt   = ' alt="' . $args['feed'] . '"';
				$name  = $args['feed'];
				$link .= empty( $args['title'] ) ? '' : $args['title'];
			}

			$link .= '>';

			if ( empty( $args['feed_image'] ) ) {
				$link .= $name;
			} else {
				$link .= "<img src='" . esc_url( $args['feed_image'] ) . "'$alt" . ' />';
			}

			$link .= '</a>';

			if ( empty( $args['feed_image'] ) ) {
				$link .= ')';
			}
		}

		if ( ! empty( $args['show_count'] ) ) {
			$link .= ' (' . number_format_i18n( $category->count ) . ')';
		}

		if ( 'list' === $args['style'] ) {
			$output     .= "\t<li";
			$css_classes = array(
				'cat-item',
				'cat-item-' . $category->term_id,
			);

			if ( ! empty( $args['current_category'] ) ) {
				// 'current_category' có thể là mảng, nên dùng `get_terms()`.
				$_current_terms = get_terms(
					array(
						'taxonomy'   => $category->taxonomy,
						'include'    => $args['current_category'],
						'hide_empty' => false,
					)
				);

				foreach ( $_current_terms as $_current_term ) {
					if ( $category->term_id === $_current_term->term_id ) {
						$css_classes[] = 'current-cat';
						$link          = str_replace( '<a', '<a aria-current="page"', $link );
					} elseif ( $category->term_id === $_current_term->parent ) {
						$css_classes[] = 'current-cat-parent';
					}

					while ( $_current_term->parent ) {
						if ( $category->term_id === $_current_term->parent ) {
							$css_classes[] = 'current-cat-ancestor';
							break;
						}

						$_current_term = get_term( $_current_term->parent, $category->taxonomy );
					}
				}
			}

			/**
			 * Lọc danh sách các lớp CSS để bao gồm với mỗi chuyên mục trong danh sách.
			 *
			 * @since 4.2.0
			 *
			 * @see wp_list_categories()
			 *
			 * @param string[] $css_classes Mảng các lớp CSS được áp dụng cho mỗi mục danh sách.
			 * @param WP_Term  $category    Đối tượng dữ liệu chuyên mục.
			 * @param int      $depth       Độ sâu trang, dùng để đệm.
			 * @param array    $args        Mảng các tham số của wp_list_categories().
			 */
			$css_classes = implode( ' ', apply_filters( 'category_css_class', $css_classes, $category, $depth, $args ) );
			$css_classes = $css_classes ? ' class="' . esc_attr( $css_classes ) . '"' : '';

			$output .= $css_classes;
			$output .= ">$link\n";
		} elseif ( isset( $args['separator'] ) ) {
			$output .= "\t$link" . $args['separator'] . "\n";
		} else {
			$output .= "\t$link<br />\n";
		}
	}

	/**
	 * Kết thúc xuất phần tử, nếu cần.
	 *
	 * @since 2.1.0
	 * @since 5.9.0 Đổi tên `$page` thành `$data_object` để khớp với lớp cha hỗ trợ tham số đặt tên trong PHP 8.
	 *
	 * @see Walker::end_el()
	 *
	 * @param string $output      Dùng để nối thêm nội dung (truyền theo tham chiếu).
	 * @param object $data_object Đối tượng dữ liệu chuyên mục. Không sử dụng.
	 * @param int    $depth       Tùy chọn. Độ sâu chuyên mục. Không sử dụng.
	 * @param array  $args        Tùy chọn. Mảng các tham số. Chỉ dùng 'list' để xác định có nối
	 *                            vào output hay không. Xem wp_list_categories(). Mặc định mảng rỗng.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = array() ) {
		if ( 'list' !== $args['style'] ) {
			return;
		}

		$output .= "</li>\n";
	}
}
