<?php
/**
 * Widget API: Lớp WP_Widget_Recent_Comments
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.4.0
 */

/**
 * Lớp cốt lõi dùng để triển khai widget Bình luận gần đây.
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 */
class WP_Widget_Recent_Comments extends WP_Widget {

	/**
	 * Thiết lập một phiên bản widget Bình luận gần đây mới.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget_recent_comments',
			'description'                 => __( 'Your site&#8217;s most recent comments.' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct( 'recent-comments', __( 'Recent Comments' ), $widget_ops );
		$this->alt_option_name = 'widget_recent_comments';

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_head', array( $this, 'recent_comments_style' ) );
		}
	}

	/**
	 * Xuất các kiểu mặc định cho widget Bình luận gần đây.
	 *
	 * @since 2.8.0
	 */
	public function recent_comments_style() {
		/**
		 * Lọc các kiểu mặc định của widget Bình luận gần đây.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $active  Widget có đang hoạt động hay không. Mặc định true.
		 * @param string $id_base ID của widget.
		 */
		if ( ! current_theme_supports( 'widgets' ) // Bản vá tạm thời #14876.
			|| ! apply_filters( 'show_recent_comments_widget_style', true, $this->id_base ) ) {
			return;
		}

		$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';

		printf(
			'<style%s>.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>',
			$type_attr
		);
	}

	/**
	 * Xuất nội dung cho phiên bản widget Bình luận gần đây hiện tại.
	 *
	 * @since 2.8.0
	 * @since 5.4.0 Tạo ID HTML duy nhất cho phần tử `<ul>`
	 *              nếu có nhiều hơn một phiên bản được hiển thị trên trang.
	 *
	 * @param array $args     Các tham số hiển thị bao gồm 'before_title', 'after_title',
	 *                        'before_widget', và 'after_widget'.
	 * @param array $instance Cài đặt cho phiên bản widget Bình luận gần đây hiện tại.
	 */
	public function widget( $args, $instance ) {
		static $first_instance = true;

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$output = '';

		$default_title = __( 'Recent Comments' );
		$title         = ( ! empty( $instance['title'] ) ) ? $instance['title'] : $default_title;

		/** Bộ lọc này được ghi chú tại wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}

		$comments = get_comments(
			/**
			 * Lọc các tham số cho widget Bình luận gần đây.
			 *
			 * @since 3.4.0
			 * @since 4.9.0 Thêm tham số `$instance`.
			 *
			 * @see WP_Comment_Query::query() để biết thông tin về các tham số được chấp nhận.
			 *
			 * @param array $comment_args Mảng các tham số dùng để truy xuất bình luận gần đây.
			 * @param array $instance     Mảng cài đặt cho widget hiện tại.
			 */
			apply_filters(
				'widget_comments_args',
				array(
					'number'      => $number,
					'status'      => 'approve',
					'post_status' => 'publish',
				),
				$instance
			)
		);

		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		$recent_comments_id = ( $first_instance ) ? 'recentcomments' : "recentcomments-{$this->number}";
		$first_instance     = false;

		$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';

		/** Bộ lọc này được ghi chú tại wp-includes/widgets/class-wp-nav-menu-widget.php */
		$format = apply_filters( 'navigation_widgets_format', $format );

		if ( 'html5' === $format ) {
			// Tiêu đề có thể bị lọc: Loại bỏ HTML và đảm bảo aria-label không bao giờ rỗng.
			$title      = trim( strip_tags( $title ) );
			$aria_label = $title ? $title : $default_title;
			$output    .= '<nav aria-label="' . esc_attr( $aria_label ) . '">';
		}

		$output .= '<ul id="' . esc_attr( $recent_comments_id ) . '">';
		if ( is_array( $comments ) && $comments ) {
			// Nạp trước bộ nhớ đệm cho các bài viết liên quan. (Nạp trước bộ nhớ đệm term bài viết nếu cần cho đường dẫn tĩnh.)
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );

			foreach ( (array) $comments as $comment ) {
				$output .= '<li class="recentcomments">';
				$output .= sprintf(
					/* translators: Widget bình luận. 1: Tác giả bình luận, 2: Liên kết bài viết. */
					_x( '%1$s on %2$s', 'widgets' ),
					'<span class="comment-author-link">' . get_comment_author_link( $comment ) . '</span>',
					'<a href="' . esc_url( get_comment_link( $comment ) ) . '">' . get_the_title( $comment->comment_post_ID ) . '</a>'
				);
				$output .= '</li>';
			}
		}
		$output .= '</ul>';

		if ( 'html5' === $format ) {
			$output .= '</nav>';
		}

		$output .= $args['after_widget'];

		echo $output;
	}

	/**
	 * Xử lý cập nhật cài đặt cho phiên bản widget Bình luận gần đây hiện tại.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance Cài đặt mới cho phiên bản này do người dùng nhập qua
	 *                            WP_Widget::form().
	 * @param array $old_instance Cài đặt cũ cho phiên bản này.
	 * @return array Cài đặt đã cập nhật để lưu.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		return $instance;
	}

	/**
	 * Xuất biểu mẫu cài đặt cho widget Bình luận gần đây.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Cài đặt hiện tại.
	 */
	public function form( $instance ) {
		$title  = isset( $instance['title'] ) ? $instance['title'] : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of comments to show:' ); ?></label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" />
		</p>
		<?php
	}

	/**
	 * Xóa bộ nhớ đệm của widget Bình luận gần đây.
	 *
	 * @since 2.8.0
	 *
	 * @deprecated 4.4.0 Bộ nhớ đệm phân đoạn đã được loại bỏ để thay bằng truy vấn tách biệt.
	 */
	public function flush_widget_cache() {
		_deprecated_function( __METHOD__, '4.4.0' );
	}
}
