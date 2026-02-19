<?php
/**
 * API Bình luận: Lớp WP_Comment
 *
 * @package WordPress
 * @subpackage Comments
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để tổ chức bình luận thành các đối tượng được khởi tạo với các thành viên được xác định.
 *
 * @since 4.4.0
 */
#[AllowDynamicProperties]
final class WP_Comment {

	/**
	 * ID bình luận.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_ID;

	/**
	 * ID bài viết mà bình luận thuộc về.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_post_ID = '0';

	/**
	 * Tên tác giả bình luận.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_author = '';

	/**
	 * Địa chỉ email tác giả bình luận.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_author_email = '';

	/**
	 * URL tác giả bình luận.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_author_url = '';

	/**
	 * Địa chỉ IP tác giả bình luận (định dạng IPv4).
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_author_IP = '';

	/**
	 * Ngày bình luận theo định dạng YYYY-MM-DD HH:MM:SS.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_date = '0000-00-00 00:00:00';

	/**
	 * Ngày bình luận theo GMT với định dạng YYYY-MM-DD HH:MM:SS.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_date_gmt = '0000-00-00 00:00:00';

	/**
	 * Nội dung bình luận.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_content;

	/**
	 * Số điểm karma của bình luận.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_karma = '0';

	/**
	 * Trạng thái phê duyệt bình luận.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_approved = '1';

	/**
	 * User agent HTTP của tác giả bình luận.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_agent = '';

	/**
	 * Loại bình luận.
	 *
	 * @since 4.4.0
	 * @since 5.5.0 Giá trị mặc định đổi thành `comment`.
	 * @var string
	 */
	public $comment_type = 'comment';

	/**
	 * ID bình luận cha.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $comment_parent = '0';

	/**
	 * ID tác giả bình luận.
	 *
	 * Chuỗi số, vì lý do tương thích.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $user_id = '0';

	/**
	 * Các bình luận con.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected $children;

	/**
	 * Các bình luận con đã được nạp cho đối tượng bình luận này hay chưa.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $populated_children = false;

	/**
	 * Các trường bài viết.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected $post_fields = array( 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count' );

	/**
	 * Lấy instance WP_Comment.
	 *
	 * @since 4.4.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
	 *
	 * @param int $id ID bình luận.
	 * @return WP_Comment|false Đối tượng bình luận, false nếu không tìm thấy.
	 */
	public static function get_instance( $id ) {
		global $wpdb;

		$comment_id = (int) $id;
		if ( ! $comment_id ) {
			return false;
		}

		$_comment = wp_cache_get( $comment_id, 'comment' );

		if ( ! $_comment ) {
			$_comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_ID = %d LIMIT 1", $comment_id ) );

			if ( ! $_comment ) {
				return false;
			}

			wp_cache_add( $_comment->comment_ID, $_comment, 'comment' );
		}

		return new WP_Comment( $_comment );
	}

	/**
	 * Hàm khởi tạo.
	 *
	 * Gán các thuộc tính từ biến đối tượng.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Comment $comment Đối tượng bình luận.
	 */
	public function __construct( $comment ) {
		foreach ( get_object_vars( $comment ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Chuyển đổi đối tượng sang mảng.
	 *
	 * @since 4.4.0
	 *
	 * @return array Đối tượng dưới dạng mảng.
	 */
	public function to_array() {
		return get_object_vars( $this );
	}

	/**
	 * Lấy các bình luận con của một bình luận.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args {
	 *     Mảng tham số dùng để truyền cho get_comments() và xác định định dạng.
	 *
	 *     @type string $format        Định dạng giá trị trả về. 'tree' cho cây phân cấp, 'flat' cho mảng phẳng.
	 *                                 Mặc định 'tree'.
	 *     @type string $status        Trạng thái bình luận để giới hạn kết quả. Chấp nhận 'hold' (`comment_status=0`),
	 *                                 'approve' (`comment_status=1`), 'all', hoặc trạng thái bình luận tùy chỉnh.
	 *                                 Mặc định 'all'.
	 *     @type string $hierarchical  Có bao gồm các bình luận con trong kết quả không.
	 *                                 'threaded' trả về cây, với các bình luận con được lưu
	 *                                 trong thuộc tính `children` trên đối tượng `WP_Comment`.
	 *                                 'flat' trả về mảng phẳng các bình luận tìm được cộng thêm bình luận con.
	 *                                 Truyền `false` để bỏ qua các bình luận con.
	 *                                 Tham số này bị bỏ qua (ép thành `false`) khi `$fields` là 'ids' hoặc 'counts'.
	 *                                 Chấp nhận 'threaded', 'flat', hoặc false. Mặc định: 'threaded'.
	 *     @type string|array $orderby Trạng thái bình luận hoặc mảng trạng thái. Để dùng 'meta_value'
	 *                                 hoặc 'meta_value_num', `$meta_key` cũng phải được định nghĩa.
	 *                                 Để sắp xếp theo mệnh đề `$meta_query` cụ thể, dùng
	 *                                 khóa mảng của mệnh đề đó. Chấp nhận 'comment_agent',
	 *                                 'comment_approved', 'comment_author',
	 *                                 'comment_author_email', 'comment_author_IP',
	 *                                 'comment_author_url', 'comment_content', 'comment_date',
	 *                                 'comment_date_gmt', 'comment_ID', 'comment_karma',
	 *                                 'comment_parent', 'comment_post_ID', 'comment_type',
	 *                                 'user_id', 'comment__in', 'meta_value', 'meta_value_num',
	 *                                 giá trị của $meta_key, và các khóa mảng của
	 *                                 `$meta_query`. Cũng chấp nhận false, mảng rỗng, hoặc
	 *                                 'none' để tắt mệnh đề `ORDER BY`.
	 * }
	 * @return WP_Comment[] Mảng các đối tượng `WP_Comment`.
	 */
	public function get_children( $args = array() ) {
		$defaults = array(
			'format'       => 'tree',
			'status'       => 'all',
			'hierarchical' => 'threaded',
			'orderby'      => '',
		);

		$_args           = wp_parse_args( $args, $defaults );
		$_args['parent'] = $this->comment_ID;

		if ( is_null( $this->children ) ) {
			if ( $this->populated_children ) {
				$this->children = array();
			} else {
				$this->children = get_comments( $_args );
			}
		}

		if ( 'flat' === $_args['format'] ) {
			$children = array();
			foreach ( $this->children as $child ) {
				$child_args           = $_args;
				$child_args['format'] = 'flat';
				// get_children() tự động đặt lại giá trị này.
				unset( $child_args['parent'] );

				$children = array_merge( $children, array( $child ), $child->get_children( $child_args ) );
			}
		} else {
			$children = $this->children;
		}

		return $children;
	}

	/**
	 * Thêm bình luận con vào bình luận.
	 *
	 * Được sử dụng bởi `WP_Comment_Query` khi nạp hàng loạt các bình luận con.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Comment $child Bình luận con.
	 */
	public function add_child( WP_Comment $child ) {
		$this->children[ $child->comment_ID ] = $child;
	}

	/**
	 * Lấy bình luận con theo ID.
	 *
	 * @since 4.4.0
	 *
	 * @param int $child_id ID của bình luận con.
	 * @return WP_Comment|false Trả về đối tượng bình luận nếu tìm thấy, ngược lại trả về false.
	 */
	public function get_child( $child_id ) {
		if ( isset( $this->children[ $child_id ] ) ) {
			return $this->children[ $child_id ];
		}

		return false;
	}

	/**
	 * Thiết lập cờ 'populated_children'.
	 *
	 * Cờ này quan trọng để đảm bảo rằng gọi `get_children()` trên bình luận không có con sẽ không
	 * kích hoạt các truy vấn cơ sở dữ liệu không cần thiết.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $set Các bình luận con đã được nạp hay chưa.
	 */
	public function populated_children( $set ) {
		$this->populated_children = (bool) $set;
	}

	/**
	 * Xác định xem thuộc tính không công khai có được thiết lập hay không.
	 *
	 * Nếu `$name` khớp với trường bài viết, bài viết của bình luận sẽ được tải và giá trị bài viết được kiểm tra.
	 *
	 * @since 4.4.0
	 *
	 * @param string $name Thuộc tính cần kiểm tra.
	 * @return bool Thuộc tính có được thiết lập hay không.
	 */
	public function __isset( $name ) {
		if ( in_array( $name, $this->post_fields, true ) && 0 !== (int) $this->comment_post_ID ) {
			$post = get_post( $this->comment_post_ID );
			return property_exists( $post, $name );
		}

		return false;
	}

	/**
	 * Phương thức getter ma thuật.
	 *
	 * Nếu `$name` khớp với trường bài viết, bài viết của bình luận sẽ được tải và giá trị bài viết được trả về.
	 *
	 * @since 4.4.0
	 *
	 * @param string $name Tên thuộc tính.
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( in_array( $name, $this->post_fields, true ) ) {
			$post = get_post( $this->comment_post_ID );
			return $post->$name;
		}
	}
}
