<?php
/**
 * Widget API: Lớp WP_Widget_Tag_Cloud
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.4.0
 */

/**
 * Lớp cốt lõi dùng để triển khai widget Đám mây thẻ.
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 */
class WP_Widget_Tag_Cloud extends WP_Widget {

	/**
	 * Thiết lập một phiên bản widget Đám mây thẻ mới.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'description'                 => __( 'A cloud of your most used tags.' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct( 'tag_cloud', __( 'Tag Cloud' ), $widget_ops );
	}

	/**
	 * Xuất nội dung cho phiên bản widget Đám mây thẻ hiện tại.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args     Các tham số hiển thị bao gồm 'before_title', 'after_title',
	 *                        'before_widget', và 'after_widget'.
	 * @param array $instance Cài đặt cho phiên bản widget Đám mây thẻ hiện tại.
	 */
	public function widget( $args, $instance ) {
		$current_taxonomy = $this->_get_current_taxonomy( $instance );

		if ( ! empty( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			if ( 'post_tag' === $current_taxonomy ) {
				$title = __( 'Tags' );
			} else {
				$tax   = get_taxonomy( $current_taxonomy );
				$title = $tax->labels->name;
			}
		}

		$default_title = $title;

		$show_count = ! empty( $instance['count'] );

		$tag_cloud = wp_tag_cloud(
			/**
			 * Lọc taxonomy được sử dụng trong widget Đám mây thẻ.
			 *
			 * @since 2.8.0
			 * @since 3.0.0 Thêm danh sách thả xuống taxonomy.
			 * @since 4.9.0 Thêm tham số `$instance`.
			 *
			 * @see wp_tag_cloud()
			 *
			 * @param array $args     Các tham số dùng cho widget đám mây thẻ.
			 * @param array $instance Mảng cài đặt cho widget hiện tại.
			 */
			apply_filters(
				'widget_tag_cloud_args',
				array(
					'taxonomy'   => $current_taxonomy,
					'echo'       => false,
					'show_count' => $show_count,
				),
				$instance
			)
		);

		if ( empty( $tag_cloud ) ) {
			return;
		}

		/** Bộ lọc này được ghi chú tại wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';

		/** Bộ lọc này được ghi chú tại wp-includes/widgets/class-wp-nav-menu-widget.php */
		$format = apply_filters( 'navigation_widgets_format', $format );

		if ( 'html5' === $format ) {
			// Tiêu đề có thể bị lọc: Loại bỏ HTML và đảm bảo aria-label không bao giờ rỗng.
			$title      = trim( strip_tags( $title ) );
			$aria_label = $title ? $title : $default_title;
			echo '<nav aria-label="' . esc_attr( $aria_label ) . '">';
		}

		echo '<div class="tagcloud">';

		echo $tag_cloud;

		echo "</div>\n";

		if ( 'html5' === $format ) {
			echo '</nav>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Xử lý cập nhật cài đặt cho phiên bản widget Đám mây thẻ hiện tại.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance Cài đặt mới cho phiên bản này do người dùng nhập qua
	 *                            WP_Widget::form().
	 * @param array $old_instance Cài đặt cũ cho phiên bản này.
	 * @return array Cài đặt để lưu hoặc bool false để hủy lưu.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance             = array();
		$instance['title']    = sanitize_text_field( $new_instance['title'] );
		$instance['count']    = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['taxonomy'] = stripslashes( $new_instance['taxonomy'] );
		return $instance;
	}

	/**
	 * Xuất biểu mẫu cài đặt widget Đám mây thẻ.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Cài đặt hiện tại.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$count = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
		$taxonomies       = get_taxonomies( array( 'show_tagcloud' => true ), 'object' );
		$current_taxonomy = $this->_get_current_taxonomy( $instance );

		switch ( count( $taxonomies ) ) {

			// Không tìm thấy taxonomy nào hỗ trợ đám mây thẻ, hiển thị thông báo lỗi.
			case 0:
				?>
				<input type="hidden" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" value="" />
				<p>
					<?php _e( 'The tag cloud will not be displayed since there are no taxonomies that support the tag cloud widget.' ); ?>
				</p>
				<?php
				break;

			// Chỉ tìm thấy một taxonomy hỗ trợ đám mây thẻ, không cần hiển thị danh sách chọn.
			case 1:
				$keys     = array_keys( $taxonomies );
				$taxonomy = reset( $keys );
				?>
				<input type="hidden" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" value="<?php echo esc_attr( $taxonomy ); ?>" />
				<?php
				break;

			// Tìm thấy nhiều hơn một taxonomy hỗ trợ đám mây thẻ, hiển thị danh sách chọn.
			default:
				?>
				<p>
					<label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ); ?></label>
					<select class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
					<?php foreach ( $taxonomies as $taxonomy => $tax ) : ?>
						<option value="<?php echo esc_attr( $taxonomy ); ?>" <?php selected( $taxonomy, $current_taxonomy ); ?>>
							<?php echo esc_html( $tax->labels->name ); ?>
						</option>
					<?php endforeach; ?>
					</select>
				</p>
				<?php
		}

		if ( count( $taxonomies ) > 0 ) {
			?>
			<p>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" <?php checked( $count, true ); ?> />
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show tag counts' ); ?></label>
			</p>
			<?php
		}
	}

	/**
	 * Lấy taxonomy cho phiên bản widget Đám mây thẻ hiện tại.
	 *
	 * @since 4.4.0
	 *
	 * @param array $instance Cài đặt hiện tại.
	 * @return string Tên taxonomy hiện tại nếu được đặt, ngược lại là 'post_tag'.
	 */
	public function _get_current_taxonomy( $instance ) {
		if ( ! empty( $instance['taxonomy'] ) && taxonomy_exists( $instance['taxonomy'] ) ) {
			return $instance['taxonomy'];
		}

		return 'post_tag';
	}
}
