<?php
/**
 * API Bài viết: Lớp WP_Post
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để triển khai đối tượng WP_Post.
 *
 * @since 3.5.0
 *
 * @property string $page_template
 *
 * @property-read int[]    $ancestors
 * @property-read int[]    $post_category
 * @property-read string[] $tags_input
 */
#[AllowDynamicProperties]
final class WP_Post {

	/**
	 * ID bài viết.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $ID;

	/**
	 * ID tác giả bài viết.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_author = '0';

	/**
	 * Thời gian xuất bản theo giờ địa phương của bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * Thời gian xuất bản theo GMT của bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * Nội dung bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_content = '';

	/**
	 * Tiêu đề bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_title = '';

	/**
	 * Tóm tắt bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 * Trạng thái bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * Cho phép bình luận hay không.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 * Cho phép ping hay không.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 * Mật khẩu bài viết dạng văn bản thuần.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_password = '';

	/**
	 * Slug của bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_name = '';

	/**
	 * Các URL đang chờ ping.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $to_ping = '';

	/**
	 * Các URL đã được ping.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $pinged = '';

	/**
	 * Thời gian chỉnh sửa theo giờ địa phương của bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 * Thời gian chỉnh sửa theo GMT của bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * Trường tiện ích trong DB cho nội dung bài viết.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_content_filtered = '';

	/**
	 * ID bài viết cha.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 * Định danh duy nhất cho bài viết, không nhất thiết là URL, dùng làm GUID cho feed.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $guid = '';

	/**
	 * Trường dùng để sắp xếp bài viết.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * Loại bài viết, như post hoặc page.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * Kiểu MIME của tệp đính kèm.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 * Số bình luận đã cache.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $comment_count = '0';

	/**
	 * Lưu trữ mức độ làm sạch dữ liệu của đối tượng bài viết.
	 *
	 * Không tương ứng với trường nào trong DB.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $filter;

	/**
	 * Lấy instance WP_Post.
	 *
	 * @since 3.5.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
	 *
	 * @param int $post_id ID bài viết.
	 * @return WP_Post|false Đối tượng bài viết, false nếu không tìm thấy.
	 */
	public static function get_instance( $post_id ) {
		global $wpdb;

		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return false;
		}

		$_post = wp_cache_get( $post_id, 'posts' );

		if ( ! $_post ) {
			$_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id ) );

			if ( ! $_post ) {
				return false;
			}

			$_post = sanitize_post( $_post, 'raw' );
			wp_cache_add( $_post->ID, $_post, 'posts' );
		} elseif ( empty( $_post->filter ) || 'raw' !== $_post->filter ) {
			$_post = sanitize_post( $_post, 'raw' );
		}

		return new WP_Post( $_post );
	}

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_Post|object $post Đối tượng bài viết.
	 */
	public function __construct( $post ) {
		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Kiểm tra thuộc tính có tồn tại không.
	 *
	 * @since 3.5.0
	 *
	 * @param string $key Thuộc tính cần kiểm tra.
	 * @return bool
	 */
	public function __isset( $key ) {
		if ( 'ancestors' === $key ) {
			return true;
		}

		if ( 'page_template' === $key ) {
			return true;
		}

		if ( 'post_category' === $key ) {
			return true;
		}

		if ( 'tags_input' === $key ) {
			return true;
		}

		return metadata_exists( 'post', $this->ID, $key );
	}

	/**
	 * Lấy giá trị thuộc tính.
	 *
	 * @since 3.5.0
	 *
	 * @param string $key Khóa cần lấy.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( 'page_template' === $key && $this->__isset( $key ) ) {
			return get_post_meta( $this->ID, '_wp_page_template', true );
		}

		if ( 'post_category' === $key ) {
			if ( is_object_in_taxonomy( $this->post_type, 'category' ) ) {
				$terms = get_the_terms( $this, 'category' );
			}

			if ( empty( $terms ) ) {
				return array();
			}

			return wp_list_pluck( $terms, 'term_id' );
		}

		if ( 'tags_input' === $key ) {
			if ( is_object_in_taxonomy( $this->post_type, 'post_tag' ) ) {
				$terms = get_the_terms( $this, 'post_tag' );
			}

			if ( empty( $terms ) ) {
				return array();
			}

			return wp_list_pluck( $terms, 'name' );
		}

		// Các giá trị còn lại cần được lọc.
		if ( 'ancestors' === $key ) {
			$value = get_post_ancestors( $this );
		} else {
			$value = get_post_meta( $this->ID, $key, true );
		}

		if ( $this->filter ) {
			$value = sanitize_post_field( $key, $value, $this->ID, $this->filter );
		}

		return $value;
	}

	/**
	 * {@Missing Summary}
	 *
	 * @since 3.5.0
	 *
	 * @param string $filter Bộ lọc.
	 * @return WP_Post
	 */
	public function filter( $filter ) {
		if ( $this->filter === $filter ) {
			return $this;
		}

		if ( 'raw' === $filter ) {
			return self::get_instance( $this->ID );
		}

		return sanitize_post( $this, $filter );
	}

	/**
	 * Chuyển đổi đối tượng sang mảng.
	 *
	 * @since 3.5.0
	 *
	 * @return array Đối tượng dưới dạng mảng.
	 */
	public function to_array() {
		$post = get_object_vars( $this );

		foreach ( array( 'ancestors', 'page_template', 'post_category', 'tags_input' ) as $key ) {
			if ( $this->__isset( $key ) ) {
				$post[ $key ] = $this->__get( $key );
			}
		}

		return $post;
	}
}
