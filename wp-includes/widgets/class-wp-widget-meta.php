<?php
/**
 * Widget API: Lớp WP_Widget_Meta
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.4.0
 */

/**
 * Lớp cốt lõi dùng để triển khai widget Meta.
 *
 * Hiển thị đăng nhập/đăng xuất, liên kết nguồn cấp RSS, v.v.
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 */
class WP_Widget_Meta extends WP_Widget {

	/**
	 * Thiết lập một phiên bản widget Meta mới.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget_meta',
			'description'                 => __( 'Login, RSS, &amp; WordPress.org links.' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct( 'meta', __( 'Meta' ), $widget_ops );
	}

	/**
	 * Xuất nội dung cho phiên bản widget Meta hiện tại.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args     Các tham số hiển thị bao gồm 'before_title', 'after_title',
	 *                        'before_widget', và 'after_widget'.
	 * @param array $instance Cài đặt cho phiên bản widget Meta hiện tại.
	 */
	public function widget( $args, $instance ) {
		$default_title = __( 'Meta' );
		$title         = ! empty( $instance['title'] ) ? $instance['title'] : $default_title;

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
		?>

		<ul>
			<?php wp_register(); ?>
			<li><?php wp_loginout(); ?></li>
			<li><a href="<?php echo esc_url( get_bloginfo( 'rss2_url' ) ); ?>"><?php _e( 'Entries feed' ); ?></a></li>
			<li><a href="<?php echo esc_url( get_bloginfo( 'comments_rss2_url' ) ); ?>"><?php _e( 'Comments feed' ); ?></a></li>

			<?php
			/**
			 * Lọc HTML mục danh sách "WordPress.org" trong widget Meta.
			 *
			 * @since 3.6.0
			 * @since 4.9.0 Thêm tham số `$instance`.
			 *
			 * @param string $html     HTML mặc định cho mục danh sách WordPress.org.
			 * @param array  $instance Mảng cài đặt cho widget hiện tại.
			 */
			echo apply_filters(
				'widget_meta_poweredby',
				sprintf(
					'<li><a href="%1$s">%2$s</a></li>',
					esc_url( __( 'https://wordpress.org/' ) ),
					__( 'WordPress.org' )
				),
				$instance
			);

			wp_meta();
			?>

		</ul>

		<?php
		if ( 'html5' === $format ) {
			echo '</nav>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Xử lý cập nhật cài đặt cho phiên bản widget Meta hiện tại.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance Cài đặt mới cho phiên bản này do người dùng nhập qua
	 *                            WP_Widget::form().
	 * @param array $old_instance Cài đặt cũ cho phiên bản này.
	 * @return array Cài đặt đã cập nhật để lưu.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Xuất biểu mẫu cài đặt cho widget Meta.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Cài đặt hiện tại.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<?php
	}
}
