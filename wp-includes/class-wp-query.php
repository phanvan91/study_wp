<?php
/**
 * Query API: Lớp WP_Query
 *
 * @package WordPress
 * @subpackage Query
 * @since 4.7.0
 */

/**
 * Lớp Query của WordPress.
 *
 * @link https://developer.wordpress.org/reference/classes/wp_query/
 *
 * @since 1.5.0
 * @since 4.5.0 Đã loại bỏ thuộc tính `$comments_popup`.
 */
#[AllowDynamicProperties]
class WP_Query {

	/**
	 * Các biến truy vấn được thiết lập bởi người dùng.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $query;

	/**
	 * Các biến truy vấn, sau khi phân tích.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * Truy vấn taxonomy, được truyền vào get_tax_sql().
	 *
	 * @since 3.1.0
	 * @var WP_Tax_Query|null Một thể hiện truy vấn taxonomy.
	 */
	public $tax_query;

	/**
	 * Bộ chứa truy vấn metadata.
	 *
	 * @since 3.2.0
	 * @var WP_Meta_Query Một thể hiện truy vấn meta.
	 */
	public $meta_query = false;

	/**
	 * Bộ chứa truy vấn ngày tháng.
	 *
	 * @since 3.7.0
	 * @var WP_Date_Query Một thể hiện truy vấn ngày tháng.
	 */
	public $date_query = false;

	/**
	 * Giữ dữ liệu cho một đối tượng đơn lẻ được truy vấn.
	 *
	 * Giữ nội dung của bài viết, trang, chuyên mục, tệp đính kèm.
	 *
	 * @since 1.5.0
	 * @var WP_Term|WP_Post_Type|WP_Post|WP_User|null
	 */
	public $queried_object;

	/**
	 * ID của đối tượng được truy vấn.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $queried_object_id;

	/**
	 * Câu lệnh SQL cho truy vấn cơ sở dữ liệu.
	 *
	 * @since 2.0.1
	 * @var string
	 */
	public $request;

	/**
	 * Mảng các đối tượng bài viết hoặc ID bài viết.
	 *
	 * @since 1.5.0
	 * @var WP_Post[]|int[]
	 */
	public $posts;

	/**
	 * Số lượng bài viết cho truy vấn hiện tại.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $post_count = 0;

	/**
	 * Chỉ mục của mục hiện tại trong vòng lặp.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $current_post = -1;

	/**
	 * Cho biết người gọi đang ở trước vòng lặp hay không.
	 *
	 * @since 6.3.0
	 * @var bool
	 */
	public $before_loop = true;

	/**
	 * Cho biết vòng lặp đã bắt đầu và người gọi đang ở trong vòng lặp hay không.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * Bài viết hiện tại.
	 *
	 * Thuộc tính này không được điền khi tham số `fields` được đặt thành
	 * `ids` hoặc `id=>parent`.
	 *
	 * @since 1.5.0
	 * @var WP_Post|null
	 */
	public $post;

	/**
	 * Danh sách bình luận cho bài viết hiện tại.
	 *
	 * @since 2.2.0
	 * @var WP_Comment[]
	 */
	public $comments;

	/**
	 * Số lượng bình luận cho các bài viết.
	 *
	 * @since 2.2.0
	 * @var int
	 */
	public $comment_count = 0;

	/**
	 * Chỉ mục của bình luận trong vòng lặp bình luận.
	 *
	 * @since 2.2.0
	 * @var int
	 */
	public $current_comment = -1;

	/**
	 * Đối tượng bình luận hiện tại.
	 *
	 * @since 2.2.0
	 * @var WP_Comment
	 */
	public $comment;

	/**
	 * Số lượng bài viết tìm được cho truy vấn hiện tại.
	 *
	 * Nếu mệnh đề limit không được sử dụng, bằng với $post_count.
	 *
	 * @since 2.1.0
	 * @var int
	 */
	public $found_posts = 0;

	/**
	 * Số lượng trang.
	 *
	 * @since 2.1.0
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * Số lượng trang bình luận.
	 *
	 * @since 2.7.0
	 * @var int
	 */
	public $max_num_comment_pages = 0;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho một bài viết đơn lẻ hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_single = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho bản xem trước hay không.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	public $is_preview = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho một trang hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_page = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho một lưu trữ hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_archive = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo ngày hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_date = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo năm hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_year = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo tháng hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_month = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo ngày hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_day = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho một thời gian cụ thể hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_time = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo tác giả hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_author = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo chuyên mục hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_category = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo thẻ hay không.
	 *
	 * @since 2.3.0
	 * @var bool
	 */
	public $is_tag = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ theo taxonomy hay không.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	public $is_tax = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho tìm kiếm hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_search = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho feed hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_feed = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho feed bình luận hay không.
	 *
	 * @since 2.2.0
	 * @var bool
	 */
	public $is_comment_feed = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lời gọi endpoint trackback hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_trackback = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho trang chủ của website hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_home = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho trang Chính sách Bảo mật hay không.
	 *
	 * @since 5.2.0
	 * @var bool
	 */
	public $is_privacy_policy = false;

	/**
	 * Cho biết truy vấn hiện tại không tìm thấy kết quả nào hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_404 = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho nội dung nhúng hay không.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	public $is_embed = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho kết quả phân trang và không phải trang đầu tiên hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_paged = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho trang giao diện quản trị hay không.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_admin = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho trang tệp đính kèm hay không.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	public $is_attachment = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho một bài viết đơn lẻ hiện có thuộc bất kỳ loại bài viết nào
	 * (bài viết, tệp đính kèm, trang, loại bài viết tùy chỉnh).
	 *
	 * @since 2.1.0
	 * @var bool
	 */
	public $is_singular = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho tệp robots.txt hay không.
	 *
	 * @since 2.1.0
	 * @var bool
	 */
	public $is_robots = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho tệp favicon.ico hay không.
	 *
	 * @since 5.4.0
	 * @var bool
	 */
	public $is_favicon = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho trang page_for_posts hay không.
	 *
	 * Về cơ bản, đây là trang chủ nếu tùy chọn không được thiết lập cho trang chủ tĩnh.
	 *
	 * @since 2.1.0
	 * @var bool
	 */
	public $is_posts_page = false;

	/**
	 * Cho biết truy vấn hiện tại có phải là cho lưu trữ loại bài viết hay không.
	 *
	 * @since 3.1.0
	 * @var bool
	 */
	public $is_post_type_archive = false;

	/**
	 * Lưu trữ trạng thái ->query_vars dưới dạng md5(serialize( $this->query_vars ) ) để biết
	 * liệu có cần phân tích lại vì có gì đó đã thay đổi hay không.
	 *
	 * @since 3.1.0
	 * @var bool|string
	 */
	private $query_vars_hash = false;

	/**
	 * Cho biết các biến truy vấn đã thay đổi kể từ lời gọi parse_query() ban đầu hay chưa.
	 * Được dùng để phát hiện các thay đổi biến truy vấn qua hook pre_get_posts.
	 *
	 * @since 3.1.1
	 * @var bool
	 */
	private $query_vars_changed = true;

	/**
	 * Đã thiết lập nếu hình thu nhỏ bài viết được lưu trong bộ nhớ đệm.
	 *
	 * @since 3.2.0
	 * @var bool
	 */
	public $thumbnails_cached = false;

	/**
	 * Kiểm soát liệu truy vấn tệp đính kèm có nên bao gồm tên tệp hay không.
	 *
	 * @since 6.0.3
	 * @var bool
	 */
	protected $allow_query_attachment_by_filename = false;

	/**
	 * Danh sách từ dừng tìm kiếm đã được lưu trong bộ nhớ đệm.
	 *
	 * @since 3.7.0
	 * @var array
	 */
	private $stopwords;

	private $compat_fields = array( 'query_vars_hash', 'query_vars_changed' );

	private $compat_methods = array( 'init_query_flags', 'parse_tax_query' );

	/**
	 * Khóa bộ nhớ đệm được tạo bởi truy vấn.
	 *
	 * Khóa bộ nhớ đệm được tạo bởi phương thức ::generate_cache_key() sau khi
	 * truy vấn đã được chuẩn hóa.
	 *
	 * @since 6.8.0
	 * @var string
	 */
	private $query_cache_key = '';

	/**
	 * Đặt lại các cờ truy vấn về false.
	 *
	 * Các cờ truy vấn là thông tin trang mà WordPress có thể xác định được.
	 *
	 * @since 2.0.0
	 */
	private function init_query_flags() {
		$this->is_single            = false;
		$this->is_preview           = false;
		$this->is_page              = false;
		$this->is_archive           = false;
		$this->is_date              = false;
		$this->is_year              = false;
		$this->is_month             = false;
		$this->is_day               = false;
		$this->is_time              = false;
		$this->is_author            = false;
		$this->is_category          = false;
		$this->is_tag               = false;
		$this->is_tax               = false;
		$this->is_search            = false;
		$this->is_feed              = false;
		$this->is_comment_feed      = false;
		$this->is_trackback         = false;
		$this->is_home              = false;
		$this->is_privacy_policy    = false;
		$this->is_404               = false;
		$this->is_paged             = false;
		$this->is_admin             = false;
		$this->is_attachment        = false;
		$this->is_singular          = false;
		$this->is_robots            = false;
		$this->is_favicon           = false;
		$this->is_posts_page        = false;
		$this->is_post_type_archive = false;
	}

	/**
	 * Khởi tạo các thuộc tính đối tượng và thiết lập giá trị mặc định.
	 *
	 * @since 1.5.0
	 */
	public function init() {
		unset( $this->posts );
		unset( $this->query );
		$this->query_vars = array();
		unset( $this->queried_object );
		unset( $this->queried_object_id );
		$this->post_count   = 0;
		$this->current_post = -1;
		$this->in_the_loop  = false;
		$this->before_loop  = true;
		unset( $this->request );
		unset( $this->post );
		unset( $this->comments );
		unset( $this->comment );
		$this->comment_count         = 0;
		$this->current_comment       = -1;
		$this->found_posts           = 0;
		$this->max_num_pages         = 0;
		$this->max_num_comment_pages = 0;

		$this->init_query_flags();
	}

	/**
	 * Phân tích lại các biến truy vấn.
	 *
	 * @since 1.5.0
	 */
	public function parse_query_vars() {
		$this->parse_query();
	}

	/**
	 * Điền vào các biến truy vấn chưa tồn tại trong tham số.
	 *
	 * @since 2.1.0
	 * @since 4.5.0 Đã loại bỏ biến truy vấn công khai `comments_popup`.
	 *
	 * @param array $query_vars Các biến truy vấn đã được định nghĩa.
	 * @return array Các biến truy vấn đầy đủ với các biến chưa định nghĩa được điền giá trị rỗng.
	 */
	public function fill_query_vars( $query_vars ) {
		$keys = array(
			'error',
			'm',
			'p',
			'post_parent',
			'subpost',
			'subpost_id',
			'attachment',
			'attachment_id',
			'name',
			'pagename',
			'page_id',
			'second',
			'minute',
			'hour',
			'day',
			'monthnum',
			'year',
			'w',
			'category_name',
			'tag',
			'cat',
			'tag_id',
			'author',
			'author_name',
			'feed',
			'tb',
			'paged',
			'meta_key',
			'meta_value',
			'preview',
			's',
			'sentence',
			'title',
			'fields',
			'menu_order',
			'embed',
		);

		foreach ( $keys as $key ) {
			if ( ! isset( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = '';
			}
		}

		$array_keys = array(
			'category__in',
			'category__not_in',
			'category__and',
			'post__in',
			'post__not_in',
			'post_name__in',
			'tag__in',
			'tag__not_in',
			'tag__and',
			'tag_slug__in',
			'tag_slug__and',
			'post_parent__in',
			'post_parent__not_in',
			'author__in',
			'author__not_in',
			'search_columns',
		);

		foreach ( $array_keys as $key ) {
			if ( ! isset( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = array();
			}
		}

		return $query_vars;
	}

	/**
	 * Phân tích chuỗi truy vấn và thiết lập các biến boolean loại truy vấn.
	 *
	 * @since 1.5.0
	 * @since 4.2.0 Giới thiệu khả năng sắp xếp theo các mệnh đề cụ thể của `$meta_query`, bằng cách truyền
	 *              khóa mảng của mệnh đề vào `$orderby`.
	 * @since 4.4.0 Giới thiệu tham số `$post_name__in` và `$title`. `$s` được cập nhật để hỗ trợ loại trừ
	 *              các từ tìm kiếm, bằng cách thêm dấu gạch ngang phía trước.
	 * @since 4.5.0 Đã loại bỏ tham số `$comments_popup`.
	 *              Giới thiệu các tham số `$comment_status` và `$ping_status`.
	 *              Giới thiệu cú pháp `RAND(x)` cho `$orderby`, cho phép giá trị seed số nguyên cho sắp xếp ngẫu nhiên.
	 * @since 4.6.0 Thêm hỗ trợ 'post_name__in' cho `$orderby`. Giới thiệu tham số `$lazy_load_term_meta`.
	 * @since 4.9.0 Giới thiệu tham số `$comment_count`.
	 * @since 5.1.0 Giới thiệu tham số `$meta_compare_key`.
	 * @since 5.3.0 Giới thiệu tham số `$meta_type_key`.
	 * @since 6.1.0 Giới thiệu tham số `$update_menu_item_cache`.
	 * @since 6.2.0 Giới thiệu tham số `$search_columns`.
	 *
	 * @param string|array $query {
	 *     Tùy chọn. Mảng hoặc chuỗi các tham số truy vấn.
	 *
	 *     @type int             $attachment_id          ID bài viết tệp đính kèm. Dùng cho post_type 'attachment'.
	 *     @type int|string      $author                 ID tác giả, hoặc danh sách ID phân cách bằng dấu phẩy.
	 *     @type string          $author_name            'user_nicename' của người dùng.
	 *     @type int[]           $author__in             Mảng các ID tác giả để truy vấn.
	 *     @type int[]           $author__not_in         Mảng các ID tác giả không truy vấn.
	 *     @type bool            $cache_results          Có lưu bộ nhớ đệm thông tin bài viết hay không. Mặc định true.
	 *     @type int|string      $cat                    ID chuyên mục hoặc danh sách ID phân cách bằng dấu phẩy (chuyên mục này hoặc con của nó).
	 *     @type int[]           $category__and          Mảng các ID chuyên mục (AND in).
	 *     @type int[]           $category__in           Mảng các ID chuyên mục (OR in, không bao gồm con).
	 *     @type int[]           $category__not_in       Mảng các ID chuyên mục (NOT in).
	 *     @type string          $category_name          Dùng slug chuyên mục (không phải tên, chuyên mục này hoặc con của nó).
	 *     @type array|int       $comment_count          Lọc kết quả theo số lượng bình luận. Cung cấp số nguyên để khớp
	 *                                                   chính xác số lượng bình luận. Cung cấp mảng với số nguyên 'value'
	 *                                                   và toán tử 'compare' ('=', '!=', '>', '>=', '<', '<=' ) để
	 *                                                   so sánh với comment_count theo cách cụ thể.
	 *     @type string          $comment_status         Trạng thái bình luận.
	 *     @type int             $comments_per_page      Số lượng bình luận trả về mỗi trang.
	 *                                                   Mặc định là tùy chọn 'comments_per_page'.
	 *     @type array           $date_query             Mảng liên kết các tham số WP_Date_Query.
	 *                                                   Xem WP_Date_Query::__construct().
	 *     @type int             $day                    Ngày trong tháng. Mặc định rỗng. Chấp nhận số 1-31.
	 *     @type bool            $exact                  Có tìm kiếm theo từ khóa chính xác hay không. Mặc định false.
	 *     @type string          $fields                 Các trường bài viết để truy vấn. Chấp nhận:
	 *                                                   - '' Trả về mảng các đối tượng bài viết đầy đủ (`WP_Post[]`).
	 *                                                   - 'ids' Trả về mảng các ID bài viết (`int[]`).
	 *                                                   - 'id=>parent' Trả về mảng liên kết các ID bài viết cha,
	 *                                                     được đánh khóa theo ID bài viết (`int[]`).
	 *                                                   Mặc định ''.
	 *     @type int             $hour                   Giờ trong ngày. Mặc định rỗng. Chấp nhận số 0-23.
	 *     @type int|bool        $ignore_sticky_posts    Có bỏ qua bài viết ghim hay không. Đặt thành false
	 *                                                   sẽ loại trừ bài ghim khỏi 'post__in'. Chấp nhận 1|true, 0|false.
	 *                                                   Mặc định false.
	 *     @type int             $m                      Kết hợp NămTháng. Chấp nhận bất kỳ năm bốn chữ số và tháng
	 *                                                   từ 01-12. Mặc định rỗng.
	 *     @type string|string[] $meta_key               Khóa meta hoặc các khóa meta để lọc.
	 *     @type string|string[] $meta_value             Giá trị meta hoặc các giá trị meta để lọc.
	 *     @type string          $meta_compare           Toán tử MySQL dùng để so sánh giá trị meta.
	 *                                                   Xem WP_Meta_Query::__construct() để biết các giá trị chấp nhận và giá trị mặc định.
	 *     @type string          $meta_compare_key       Toán tử MySQL dùng để so sánh khóa meta.
	 *                                                   Xem WP_Meta_Query::__construct() để biết các giá trị chấp nhận và giá trị mặc định.
	 *     @type string          $meta_type              Kiểu dữ liệu MySQL mà cột meta_value sẽ được CAST sang để so sánh.
	 *                                                   Xem WP_Meta_Query::__construct() để biết các giá trị chấp nhận và giá trị mặc định.
	 *     @type string          $meta_type_key          Kiểu dữ liệu MySQL mà cột meta_key sẽ được CAST sang để so sánh.
	 *                                                   Xem WP_Meta_Query::__construct() để biết các giá trị chấp nhận và giá trị mặc định.
	 *     @type array           $meta_query             Mảng liên kết các tham số WP_Meta_Query.
	 *                                                   Xem WP_Meta_Query::__construct() để biết các giá trị chấp nhận.
	 *     @type int             $menu_order             Thứ tự menu của các bài viết.
	 *     @type int             $minute                 Phút trong giờ. Mặc định rỗng. Chấp nhận số 0-59.
	 *     @type int             $monthnum               Tháng hai chữ số. Mặc định rỗng. Chấp nhận số 1-12.
	 *     @type string          $name                   Slug bài viết.
	 *     @type bool            $nopaging               Hiển thị tất cả bài viết (true) hoặc phân trang (false). Mặc định false.
	 *     @type bool            $no_found_rows          Có bỏ qua đếm tổng số hàng tìm được hay không. Bật có thể cải thiện
	 *                                                   hiệu suất. Mặc định false.
	 *     @type int             $offset                 Số lượng bài viết bỏ qua trước khi lấy dữ liệu.
	 *     @type string          $order                  Chỉ định thứ tự tăng dần hoặc giảm dần của bài viết. Mặc định 'DESC'.
	 *                                                   Chấp nhận 'ASC', 'DESC'.
	 *     @type string|array    $orderby                Sắp xếp bài viết lấy được theo tham số. Có thể truyền một hoặc nhiều tùy chọn.
	 *                                                   Để dùng 'meta_value', hoặc 'meta_value_num', phải định nghĩa
	 *                                                   'meta_key=keyname'. Để sắp xếp theo mệnh đề `$meta_query` cụ thể,
	 *                                                   dùng khóa mảng của mệnh đề đó. Chấp nhận:
	 *                                                   - 'none'
	 *                                                   - 'name'
	 *                                                   - 'author'
	 *                                                   - 'date'
	 *                                                   - 'title'
	 *                                                   - 'modified'
	 *                                                   - 'menu_order'
	 *                                                   - 'parent'
	 *                                                   - 'ID'
	 *                                                   - 'rand'
	 *                                                   - 'relevance'
	 *                                                   - 'RAND(x)' (trong đó 'x' là giá trị seed số nguyên)
	 *                                                   - 'comment_count'
	 *                                                   - 'meta_value'
	 *                                                   - 'meta_value_num'
	 *                                                   - 'post__in'
	 *                                                   - 'post_name__in'
	 *                                                   - 'post_parent__in'
	 *                                                   - Các khóa mảng của `$meta_query`.
	 *                                                   Mặc định là 'date', ngoại trừ khi đang thực hiện tìm kiếm,
	 *                                                   khi đó mặc định là 'relevance'.
	 *     @type int             $p                      ID bài viết.
	 *     @type int             $page                   Hiển thị số bài viết sẽ xuất hiện trên trang X của
	 *                                                   trang chủ tĩnh.
	 *     @type int             $paged                  Số trang hiện tại.
	 *     @type int             $page_id                ID trang.
	 *     @type string          $pagename               Slug trang.
	 *     @type string          $perm                   Hiển thị bài viết nếu người dùng có quyền phù hợp.
	 *     @type string          $ping_status            Trạng thái ping.
	 *     @type int[]           $post__in               Mảng các ID bài viết để lấy, bài viết ghim sẽ được bao gồm.
	 *     @type int[]           $post__not_in           Mảng các ID bài viết không lấy. Lưu ý: chuỗi các ID phân cách
	 *                                                   bằng dấu phẩy sẽ KHÔNG hoạt động.
	 *     @type string          $post_mime_type         Kiểu mime của bài viết. Dùng cho post_type 'attachment'.
	 *     @type string[]        $post_name__in          Mảng các slug bài viết mà kết quả phải khớp.
	 *     @type int             $post_parent            ID trang để lấy các trang con. Dùng 0 để chỉ lấy
	 *                                                   các trang cấp cao nhất.
	 *     @type int[]           $post_parent__in        Mảng chứa các ID trang cha để truy vấn trang con.
	 *     @type int[]           $post_parent__not_in    Mảng chứa các ID trang cha không truy vấn trang con.
	 *     @type string|string[] $post_type              Slug loại bài viết (chuỗi) hoặc mảng các slug loại bài viết.
	 *                                                   Mặc định 'any' nếu dùng 'tax_query'.
	 *     @type string|string[] $post_status            Trạng thái bài viết (chuỗi) hoặc mảng các trạng thái bài viết.
	 *     @type int             $posts_per_page         Số lượng bài viết cần truy vấn. Dùng -1 để yêu cầu tất cả bài viết.
	 *     @type int             $posts_per_archive_page Số lượng bài viết cần truy vấn cho trang lưu trữ. Ghi đè
	 *                                                   'posts_per_page' khi is_archive(), hoặc is_search() trả về true.
	 *     @type string          $s                      Từ khóa tìm kiếm. Thêm dấu gạch ngang trước từ sẽ
	 *                                                   loại trừ bài viết khớp với từ đó. Ví dụ, 'pillow -sofa' sẽ
	 *                                                   trả về bài viết chứa 'pillow' nhưng không chứa 'sofa'. Ký tự
	 *                                                   dùng để loại trừ có thể thay đổi bằng bộ lọc
	 *                                                   'wp_query_search_exclusion_prefix'.
	 *     @type string[]        $search_columns         Mảng tên cột cần tìm kiếm. Chấp nhận 'post_title',
	 *                                                   'post_excerpt' và 'post_content'. Mặc định mảng rỗng.
	 *     @type int             $second                 Giây trong phút. Mặc định rỗng. Chấp nhận số 0-59.
	 *     @type bool            $sentence               Có tìm kiếm theo cụm từ hay không. Mặc định false.
	 *     @type bool            $suppress_filters       Có chặn các bộ lọc hay không. Mặc định false.
	 *     @type string          $tag                    Slug thẻ. Phân cách bằng dấu phẩy (một trong các), phân cách bằng dấu cộng (tất cả).
	 *     @type int[]           $tag__and               Mảng các ID thẻ (AND in).
	 *     @type int[]           $tag__in                Mảng các ID thẻ (OR in).
	 *     @type int[]           $tag__not_in            Mảng các ID thẻ (NOT in).
	 *     @type int             $tag_id                 ID thẻ hoặc danh sách ID phân cách bằng dấu phẩy.
	 *     @type string[]        $tag_slug__and          Mảng các slug thẻ (AND in).
	 *     @type string[]        $tag_slug__in           Mảng các slug thẻ (OR in). trừ khi 'ignore_sticky_posts' là
	 *                                                   true. Lưu ý: chuỗi các ID phân cách bằng dấu phẩy sẽ KHÔNG hoạt động.
	 *     @type array           $tax_query              Mảng liên kết các tham số WP_Tax_Query.
	 *                                                   Xem WP_Tax_Query::__construct().
	 *     @type string          $title                  Tiêu đề bài viết.
	 *     @type bool            $update_post_meta_cache Có cập nhật bộ nhớ đệm meta bài viết hay không. Mặc định true.
	 *     @type bool            $update_post_term_cache Có cập nhật bộ nhớ đệm term bài viết hay không. Mặc định true.
	 *     @type bool            $update_menu_item_cache Có cập nhật bộ nhớ đệm mục menu hay không. Mặc định false.
	 *     @type bool            $lazy_load_term_meta    Có tải lười meta của term hay không. Đặt thành false sẽ
	 *                                                   vô hiệu hóa việc nạp trước bộ nhớ đệm cho meta term, để mỗi lần
	 *                                                   gọi get_term_meta() sẽ truy vấn cơ sở dữ liệu.
	 *                                                   Mặc định theo giá trị của `$update_post_term_cache`.
	 *     @type int             $w                      Số tuần trong năm. Mặc định rỗng. Chấp nhận số 0-53.
	 *     @type int             $year                   Năm bốn chữ số. Mặc định rỗng. Chấp nhận bất kỳ năm bốn chữ số nào.
	 * }
	 */
	public function parse_query( $query = '' ) {
		if ( ! empty( $query ) ) {
			$this->init();
			$this->query      = wp_parse_args( $query );
			$this->query_vars = $this->query;
		} elseif ( ! isset( $this->query ) ) {
			$this->query = $this->query_vars;
		}

		$this->query_vars         = $this->fill_query_vars( $this->query_vars );
		$qv                       = &$this->query_vars;
		$this->query_vars_changed = true;

		if ( ! empty( $qv['robots'] ) ) {
			$this->is_robots = true;
		} elseif ( ! empty( $qv['favicon'] ) ) {
			$this->is_favicon = true;
		}

		if ( ! is_scalar( $qv['p'] ) || (int) $qv['p'] < 0 ) {
			$qv['p']     = 0;
			$qv['error'] = '404';
		} else {
			$qv['p'] = (int) $qv['p'];
		}

		$qv['page_id']  = is_scalar( $qv['page_id'] ) ? absint( $qv['page_id'] ) : 0;
		$qv['year']     = is_scalar( $qv['year'] ) ? absint( $qv['year'] ) : 0;
		$qv['monthnum'] = is_scalar( $qv['monthnum'] ) ? absint( $qv['monthnum'] ) : 0;
		$qv['day']      = is_scalar( $qv['day'] ) ? absint( $qv['day'] ) : 0;
		$qv['w']        = is_scalar( $qv['w'] ) ? absint( $qv['w'] ) : 0;
		$qv['m']        = is_scalar( $qv['m'] ) ? preg_replace( '|[^0-9]|', '', $qv['m'] ) : '';
		$qv['paged']    = is_scalar( $qv['paged'] ) ? absint( $qv['paged'] ) : 0;
		$qv['cat']      = preg_replace( '|[^0-9,-]|', '', $qv['cat'] ); // Mảng hoặc danh sách phân cách bằng dấu phẩy gồm các số nguyên dương hoặc âm.
		$qv['author']   = is_scalar( $qv['author'] ) ? preg_replace( '|[^0-9,-]|', '', $qv['author'] ) : ''; // Danh sách phân cách bằng dấu phẩy gồm các số nguyên dương hoặc âm.
		$qv['pagename'] = is_scalar( $qv['pagename'] ) ? trim( $qv['pagename'] ) : '';
		$qv['name']     = is_scalar( $qv['name'] ) ? trim( $qv['name'] ) : '';
		$qv['title']    = is_scalar( $qv['title'] ) ? trim( $qv['title'] ) : '';

		if ( is_scalar( $qv['hour'] ) && '' !== $qv['hour'] ) {
			$qv['hour'] = absint( $qv['hour'] );
		} else {
			$qv['hour'] = '';
		}

		if ( is_scalar( $qv['minute'] ) && '' !== $qv['minute'] ) {
			$qv['minute'] = absint( $qv['minute'] );
		} else {
			$qv['minute'] = '';
		}

		if ( is_scalar( $qv['second'] ) && '' !== $qv['second'] ) {
			$qv['second'] = absint( $qv['second'] );
		} else {
			$qv['second'] = '';
		}

		if ( is_scalar( $qv['menu_order'] ) && '' !== $qv['menu_order'] ) {
			$qv['menu_order'] = absint( $qv['menu_order'] );
		} else {
			$qv['menu_order'] = '';
		}

		// Giới hạn trên khá lớn, có thể quá lớn, cho độ dài chuỗi tìm kiếm.
		if ( ! is_scalar( $qv['s'] ) || ( ! empty( $qv['s'] ) && strlen( $qv['s'] ) > 1600 ) ) {
			$qv['s'] = '';
		}

		// Tương thích ngược. Ánh xạ subpost thành attachment.
		if ( is_scalar( $qv['subpost'] ) && '' != $qv['subpost'] ) {
			$qv['attachment'] = $qv['subpost'];
		}
		if ( is_scalar( $qv['subpost_id'] ) && '' != $qv['subpost_id'] ) {
			$qv['attachment_id'] = $qv['subpost_id'];
		}

		$qv['attachment_id'] = is_scalar( $qv['attachment_id'] ) ? absint( $qv['attachment_id'] ) : 0;

		if ( ( '' !== $qv['attachment'] ) || ! empty( $qv['attachment_id'] ) ) {
			$this->is_single     = true;
			$this->is_attachment = true;
		} elseif ( '' !== $qv['name'] ) {
			$this->is_single = true;
		} elseif ( $qv['p'] ) {
			$this->is_single = true;
		} elseif ( '' !== $qv['pagename'] || ! empty( $qv['page_id'] ) ) {
			$this->is_page   = true;
			$this->is_single = false;
		} else {
			// Tìm kiếm các truy vấn lưu trữ. Ngày tháng, chuyên mục, tác giả, tìm kiếm, lưu trữ loại bài viết.

			if ( isset( $this->query['s'] ) ) {
				$this->is_search = true;
			}

			if ( '' !== $qv['second'] ) {
				$this->is_time = true;
				$this->is_date = true;
			}

			if ( '' !== $qv['minute'] ) {
				$this->is_time = true;
				$this->is_date = true;
			}

			if ( '' !== $qv['hour'] ) {
				$this->is_time = true;
				$this->is_date = true;
			}

			if ( $qv['day'] ) {
				if ( ! $this->is_date ) {
					$date = sprintf( '%04d-%02d-%02d', $qv['year'], $qv['monthnum'], $qv['day'] );
					if ( $qv['monthnum'] && $qv['year'] && ! wp_checkdate( $qv['monthnum'], $qv['day'], $qv['year'], $date ) ) {
						$qv['error'] = '404';
					} else {
						$this->is_day  = true;
						$this->is_date = true;
					}
				}
			}

			if ( $qv['monthnum'] ) {
				if ( ! $this->is_date ) {
					if ( 12 < $qv['monthnum'] ) {
						$qv['error'] = '404';
					} else {
						$this->is_month = true;
						$this->is_date  = true;
					}
				}
			}

			if ( $qv['year'] ) {
				if ( ! $this->is_date ) {
					$this->is_year = true;
					$this->is_date = true;
				}
			}

			if ( $qv['m'] ) {
				$this->is_date = true;
				if ( strlen( $qv['m'] ) > 9 ) {
					$this->is_time = true;
				} elseif ( strlen( $qv['m'] ) > 7 ) {
					$this->is_day = true;
				} elseif ( strlen( $qv['m'] ) > 5 ) {
					$this->is_month = true;
				} else {
					$this->is_year = true;
				}
			}

			if ( $qv['w'] ) {
				$this->is_date = true;
			}

			$this->query_vars_hash = false;
			$this->parse_tax_query( $qv );

			foreach ( $this->tax_query->queries as $tax_query ) {
				if ( ! is_array( $tax_query ) ) {
					continue;
				}

				if ( isset( $tax_query['operator'] ) && 'NOT IN' !== $tax_query['operator'] ) {
					switch ( $tax_query['taxonomy'] ) {
						case 'category':
							$this->is_category = true;
							break;
						case 'post_tag':
							$this->is_tag = true;
							break;
						default:
							$this->is_tax = true;
					}
				}
			}
			unset( $tax_query );

			if ( empty( $qv['author'] ) || ( '0' == $qv['author'] ) ) {
				$this->is_author = false;
			} else {
				$this->is_author = true;
			}

			if ( '' !== $qv['author_name'] ) {
				$this->is_author = true;
			}

			if ( ! empty( $qv['post_type'] ) && ! is_array( $qv['post_type'] ) ) {
				$post_type_obj = get_post_type_object( $qv['post_type'] );
				if ( ! empty( $post_type_obj->has_archive ) ) {
					$this->is_post_type_archive = true;
				}
			}

			if ( $this->is_post_type_archive || $this->is_date || $this->is_author || $this->is_category || $this->is_tag || $this->is_tax ) {
				$this->is_archive = true;
			}
		}

		if ( '' != $qv['feed'] ) {
			$this->is_feed = true;
		}

		if ( '' != $qv['embed'] ) {
			$this->is_embed = true;
		}

		if ( '' != $qv['tb'] ) {
			$this->is_trackback = true;
		}

		if ( '' != $qv['paged'] && ( (int) $qv['paged'] > 1 ) ) {
			$this->is_paged = true;
		}

		// Nếu đang xem trước trong màn hình soạn thảo.
		if ( '' != $qv['preview'] ) {
			$this->is_preview = true;
		}

		if ( is_admin() ) {
			$this->is_admin = true;
		}

		if ( str_contains( $qv['feed'], 'comments-' ) ) {
			$qv['feed']         = str_replace( 'comments-', '', $qv['feed'] );
			$qv['withcomments'] = 1;
		}

		$this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;

		if ( $this->is_feed && ( ! empty( $qv['withcomments'] ) || ( empty( $qv['withoutcomments'] ) && $this->is_singular ) ) ) {
			$this->is_comment_feed = true;
		}

		if ( ! ( $this->is_singular || $this->is_archive || $this->is_search || $this->is_feed
				|| ( wp_is_serving_rest_request() && $this->is_main_query() )
				|| $this->is_trackback || $this->is_404 || $this->is_admin || $this->is_robots || $this->is_favicon ) ) {
			$this->is_home = true;
		}

		// Sửa `is_*` cho 'page_on_front' và 'page_for_posts'.
		if ( $this->is_home && 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
			$_query = wp_parse_args( $this->query );
			// 'pagename' có thể được thiết lập nhưng rỗng tùy thuộc vào quy tắc rewrite khớp. Bỏ qua 'pagename' rỗng.
			if ( isset( $_query['pagename'] ) && '' === $_query['pagename'] ) {
				unset( $_query['pagename'] );
			}

			unset( $_query['embed'] );

			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage' ) ) ) {
				$this->is_page = true;
				$this->is_home = false;
				$qv['page_id'] = get_option( 'page_on_front' );
				// Sửa <!--nextpage--> cho 'page_on_front'.
				if ( ! empty( $qv['paged'] ) ) {
					$qv['page'] = $qv['paged'];
					unset( $qv['paged'] );
				}
			}
		}

		if ( '' !== $qv['pagename'] ) {
			$this->queried_object = get_page_by_path( $qv['pagename'] );

			if ( $this->queried_object && 'attachment' === $this->queried_object->post_type ) {
				if ( preg_match( '/^[^%]*%(?:postname)%/', get_option( 'permalink_structure' ) ) ) {
					// Xem thử có bài viết nào cũng có cùng slug hay không.
					$post = get_page_by_path( $qv['pagename'], OBJECT, 'post' );
					if ( $post ) {
						$this->queried_object = $post;
						$this->is_page        = false;
						$this->is_single      = true;
					}
				}
			}

			if ( ! empty( $this->queried_object ) ) {
				$this->queried_object_id = (int) $this->queried_object->ID;
			} else {
				unset( $this->queried_object );
			}

			if ( 'page' === get_option( 'show_on_front' ) && isset( $this->queried_object_id ) && get_option( 'page_for_posts' ) == $this->queried_object_id ) {
				$this->is_page       = false;
				$this->is_home       = true;
				$this->is_posts_page = true;
			}

			if ( isset( $this->queried_object_id ) && get_option( 'wp_page_for_privacy_policy' ) == $this->queried_object_id ) {
				$this->is_privacy_policy = true;
			}
		}

		if ( $qv['page_id'] ) {
			if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) == $qv['page_id'] ) {
				$this->is_page       = false;
				$this->is_home       = true;
				$this->is_posts_page = true;
			}

			if ( get_option( 'wp_page_for_privacy_policy' ) == $qv['page_id'] ) {
				$this->is_privacy_policy = true;
			}
		}

		if ( ! empty( $qv['post_type'] ) ) {
			if ( is_array( $qv['post_type'] ) ) {
				$qv['post_type'] = array_map( 'sanitize_key', array_unique( $qv['post_type'] ) );
				sort( $qv['post_type'] );
			} else {
				$qv['post_type'] = sanitize_key( $qv['post_type'] );
			}
		}

		if ( ! empty( $qv['post_status'] ) ) {
			if ( is_array( $qv['post_status'] ) ) {
				$qv['post_status'] = array_map( 'sanitize_key', array_unique( $qv['post_status'] ) );
				sort( $qv['post_status'] );
			} else {
				$qv['post_status'] = preg_replace( '|[^a-z0-9_,-]|', '', $qv['post_status'] );
			}
		}

		if ( $this->is_posts_page && ( ! isset( $qv['withcomments'] ) || ! $qv['withcomments'] ) ) {
			$this->is_comment_feed = false;
		}

		$this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;
		// Hoàn tất việc sửa `is_*` cho 'page_on_front' và 'page_for_posts'.

		if ( '404' == $qv['error'] ) {
			$this->set_404();
		}

		$this->is_embed = $this->is_embed && ( $this->is_singular || $this->is_404 );

		$this->query_vars_hash    = md5( serialize( $this->query_vars ) );
		$this->query_vars_changed = false;

		/**
		 * Kích hoạt sau khi các biến truy vấn chính đã được phân tích.
		 *
		 * @since 1.5.0
		 *
		 * @param WP_Query $query Thể hiện WP_Query (truyền theo tham chiếu).
		 */
		do_action_ref_array( 'parse_query', array( &$this ) );
	}

	/**
	 * Phân tích các biến truy vấn liên quan đến taxonomy.
	 *
	 * Để tương thích ngược, phương thức này không được đánh dấu là protected. Xem [28987].
	 *
	 * @since 3.1.0
	 *
	 * @param array $q Các biến truy vấn. Truyền theo tham chiếu.
	 */
	public function parse_tax_query( &$q ) {
		if ( ! empty( $q['tax_query'] ) && is_array( $q['tax_query'] ) ) {
			$tax_query = $q['tax_query'];
		} else {
			$tax_query = array();
		}

		if ( ! empty( $q['taxonomy'] ) && ! empty( $q['term'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $q['taxonomy'],
				'terms'    => array( $q['term'] ),
				'field'    => 'slug',
			);
		}

		foreach ( get_taxonomies( array(), 'objects' ) as $taxonomy => $t ) {
			if ( 'post_tag' === $taxonomy ) {
				continue; // Được xử lý ở phía dưới trong khối $q['tag'].
			}

			if ( $t->query_var && ! empty( $q[ $t->query_var ] ) ) {
				$tax_query_defaults = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
				);

				if ( ! empty( $t->rewrite['hierarchical'] ) ) {
					$q[ $t->query_var ] = wp_basename( $q[ $t->query_var ] );
				}

				$term = $q[ $t->query_var ];

				if ( ! is_array( $term ) ) {
					$term = explode( ',', $term );
					$term = array_map( 'trim', $term );
				}
				sort( $term );
				$term = implode( ',', $term );

				if ( str_contains( $term, '+' ) ) {
					$terms = preg_split( '/[+]+/', $term );
					foreach ( $terms as $term ) {
						$tax_query[] = array_merge(
							$tax_query_defaults,
							array(
								'terms' => array( $term ),
							)
						);
					}
				} else {
					$tax_query[] = array_merge(
						$tax_query_defaults,
						array(
							'terms' => preg_split( '/[,]+/', $term ),
						)
					);
				}
			}
		}

		// Nếu chuỗi truy vấn 'cat' là mảng, gộp nó lại.
		if ( is_array( $q['cat'] ) ) {
			$q['cat'] = implode( ',', $q['cat'] );
		}

		// Xử lý chuyên mục.

		if ( ! empty( $q['cat'] ) && ! $this->is_singular ) {
			$cat_in     = array();
			$cat_not_in = array();

			$cat_array = preg_split( '/[,\s]+/', urldecode( $q['cat'] ) );
			$cat_array = array_map( 'intval', $cat_array );
			sort( $cat_array );
			$q['cat'] = implode( ',', $cat_array );

			foreach ( $cat_array as $cat ) {
				if ( $cat > 0 ) {
					$cat_in[] = $cat;
				} elseif ( $cat < 0 ) {
					$cat_not_in[] = abs( $cat );
				}
			}

			if ( ! empty( $cat_in ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'category',
					'terms'            => $cat_in,
					'field'            => 'term_id',
					'include_children' => true,
				);
			}

			if ( ! empty( $cat_not_in ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'category',
					'terms'            => $cat_not_in,
					'field'            => 'term_id',
					'operator'         => 'NOT IN',
					'include_children' => true,
				);
			}
			unset( $cat_array, $cat_in, $cat_not_in );
		}

		if ( ! empty( $q['category__and'] ) && 1 === count( (array) $q['category__and'] ) ) {
			$q['category__and'] = (array) $q['category__and'];
			if ( ! isset( $q['category__in'] ) ) {
				$q['category__in'] = array();
			}
			$q['category__in'][] = absint( reset( $q['category__and'] ) );
			unset( $q['category__and'] );
		}

		if ( ! empty( $q['category__in'] ) ) {
			$q['category__in'] = array_map( 'absint', array_unique( (array) $q['category__in'] ) );
			sort( $q['category__in'] );
			$tax_query[] = array(
				'taxonomy'         => 'category',
				'terms'            => $q['category__in'],
				'field'            => 'term_id',
				'include_children' => false,
			);
		}

		if ( ! empty( $q['category__not_in'] ) ) {
			$q['category__not_in'] = array_map( 'absint', array_unique( (array) $q['category__not_in'] ) );
			sort( $q['category__not_in'] );
			$tax_query[] = array(
				'taxonomy'         => 'category',
				'terms'            => $q['category__not_in'],
				'operator'         => 'NOT IN',
				'include_children' => false,
			);
		}

		if ( ! empty( $q['category__and'] ) ) {
			$q['category__and'] = array_map( 'absint', array_unique( (array) $q['category__and'] ) );
			sort( $q['category__and'] );
			$tax_query[] = array(
				'taxonomy'         => 'category',
				'terms'            => $q['category__and'],
				'field'            => 'term_id',
				'operator'         => 'AND',
				'include_children' => false,
			);
		}

		// Nếu chuỗi truy vấn 'tag' là mảng, gộp nó lại.
		if ( is_array( $q['tag'] ) ) {
			$q['tag'] = implode( ',', $q['tag'] );
		}

		// Xử lý thẻ.

		if ( '' !== $q['tag'] && ! $this->is_singular && $this->query_vars_changed ) {
			if ( str_contains( $q['tag'], ',' ) ) {
				// @todo Handle normalizing `tag` query string.
				$tags = preg_split( '/[,\r\n\t ]+/', $q['tag'] );
				foreach ( (array) $tags as $tag ) {
					$tag                 = sanitize_term_field( 'slug', $tag, 0, 'post_tag', 'db' );
					$q['tag_slug__in'][] = $tag;
					sort( $q['tag_slug__in'] );
				}
			} elseif ( preg_match( '/[+\r\n\t ]+/', $q['tag'] ) || ! empty( $q['cat'] ) ) {
				$tags = preg_split( '/[+\r\n\t ]+/', $q['tag'] );
				foreach ( (array) $tags as $tag ) {
					$tag                  = sanitize_term_field( 'slug', $tag, 0, 'post_tag', 'db' );
					$q['tag_slug__and'][] = $tag;
				}
			} else {
				$q['tag']            = sanitize_term_field( 'slug', $q['tag'], 0, 'post_tag', 'db' );
				$q['tag_slug__in'][] = $q['tag'];
				sort( $q['tag_slug__in'] );
			}
		}

		if ( ! empty( $q['tag_id'] ) ) {
			$q['tag_id'] = absint( $q['tag_id'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag_id'],
			);
		}

		if ( ! empty( $q['tag__in'] ) ) {
			$q['tag__in'] = array_map( 'absint', array_unique( (array) $q['tag__in'] ) );
			sort( $q['tag__in'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag__in'],
			);
		}

		if ( ! empty( $q['tag__not_in'] ) ) {
			$q['tag__not_in'] = array_map( 'absint', array_unique( (array) $q['tag__not_in'] ) );
			sort( $q['tag__not_in'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag__not_in'],
				'operator' => 'NOT IN',
			);
		}

		if ( ! empty( $q['tag__and'] ) ) {
			$q['tag__and'] = array_map( 'absint', array_unique( (array) $q['tag__and'] ) );
			sort( $q['tag__and'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag__and'],
				'operator' => 'AND',
			);
		}

		if ( ! empty( $q['tag_slug__in'] ) ) {
			$q['tag_slug__in'] = array_map( 'sanitize_title_for_query', array_unique( (array) $q['tag_slug__in'] ) );
			sort( $q['tag_slug__in'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag_slug__in'],
				'field'    => 'slug',
			);
		}

		if ( ! empty( $q['tag_slug__and'] ) ) {
			$q['tag_slug__and'] = array_map( 'sanitize_title_for_query', array_unique( (array) $q['tag_slug__and'] ) );
			sort( $q['tag_slug__and'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag_slug__and'],
				'field'    => 'slug',
				'operator' => 'AND',
			);
		}

		$this->tax_query = new WP_Tax_Query( $tax_query );

		/**
		 * Kích hoạt sau khi các biến truy vấn liên quan đến taxonomy đã được phân tích.
		 *
		 * @since 3.7.0
		 *
		 * @param WP_Query $query Thể hiện WP_Query.
		 */
		do_action( 'parse_tax_query', $this );
	}

	/**
	 * Tạo SQL cho mệnh đề WHERE dựa trên các từ tìm kiếm được truyền vào.
	 *
	 * @since 3.7.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param array $q Các biến truy vấn.
	 * @return string Mệnh đề WHERE.
	 */
	protected function parse_search( &$q ) {
		global $wpdb;

		$search = '';

		// Thêm dấu gạch chéo sẽ làm hỏng việc nhóm dấu ngoặc kép nếu làm sớm, nên thực hiện sau.
		$q['s'] = stripslashes( $q['s'] );
		if ( empty( $_GET['s'] ) && $this->is_main_query() ) {
			$q['s'] = urldecode( $q['s'] );
		}
		// Không có ngắt dòng trong các trường <input />.
		$q['s']                  = str_replace( array( "\r", "\n" ), '', $q['s'] );
		$q['search_terms_count'] = 1;
		if ( ! empty( $q['sentence'] ) ) {
			$q['search_terms'] = array( $q['s'] );
		} else {
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches ) ) {
				$q['search_terms_count'] = count( $matches[0] );
				$q['search_terms']       = $this->parse_search_terms( $matches[0] );
				// Nếu chuỗi tìm kiếm chỉ có từ ngắn hoặc từ dừng, hoặc dài hơn 10 từ, khớp như câu.
				if ( empty( $q['search_terms'] ) || count( $q['search_terms'] ) > 9 ) {
					$q['search_terms'] = array( $q['s'] );
				}
			} else {
				$q['search_terms'] = array( $q['s'] );
			}
		}

		$n                         = ! empty( $q['exact'] ) ? '' : '%';
		$searchand                 = '';
		$q['search_orderby_title'] = array();

		$default_search_columns = array( 'post_title', 'post_excerpt', 'post_content' );
		$search_columns         = ! empty( $q['search_columns'] ) ? $q['search_columns'] : $default_search_columns;
		if ( ! is_array( $search_columns ) ) {
			$search_columns = array( $search_columns );
		}

		/**
		 * Lọc các cột để tìm kiếm trong truy vấn WP_Query.
		 *
		 * Các cột được hỗ trợ là `post_title`, `post_excerpt` và `post_content`.
		 * Tất cả đều được bao gồm theo mặc định.
		 *
		 * @since 6.2.0
		 *
		 * @param string[] $search_columns Mảng tên các cột cần tìm kiếm.
		 * @param string   $search         Văn bản đang được tìm kiếm.
		 * @param WP_Query $query          Thể hiện WP_Query hiện tại.
		 */
		$search_columns = (array) apply_filters( 'post_search_columns', $search_columns, $q['s'], $this );

		// Chỉ sử dụng các cột tìm kiếm được hỗ trợ.
		$search_columns = array_intersect( $search_columns, $default_search_columns );
		if ( empty( $search_columns ) ) {
			$search_columns = $default_search_columns;
		}

		/**
		 * Lọc tiền tố chỉ định rằng một từ tìm kiếm nên bị loại trừ khỏi kết quả.
		 *
		 * @since 4.7.0
		 *
		 * @param string $exclusion_prefix Tiền tố. Mặc định '-'. Trả về
		 *                                 giá trị rỗng sẽ vô hiệu hóa loại trừ.
		 */
		$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' );

		foreach ( $q['search_terms'] as $term ) {
			// Nếu có $exclusion_prefix, các từ có tiền tố này sẽ bị loại trừ.
			$exclude = $exclusion_prefix && str_starts_with( $term, $exclusion_prefix );
			if ( $exclude ) {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			} else {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			}

			if ( $n && ! $exclude ) {
				$like                        = '%' . $wpdb->esc_like( $term ) . '%';
				$q['search_orderby_title'][] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
			}

			$like = $n . $wpdb->esc_like( $term ) . $n;

			$search_columns_parts = array();
			foreach ( $search_columns as $search_column ) {
				$search_columns_parts[ $search_column ] = $wpdb->prepare( "({$wpdb->posts}.$search_column $like_op %s)", $like );
			}

			if ( ! empty( $this->allow_query_attachment_by_filename ) ) {
				$search_columns_parts['attachment'] = $wpdb->prepare( "(sq1.meta_value $like_op %s)", $like );
			}

			$search .= "$searchand(" . implode( " $andor_op ", $search_columns_parts ) . ')';

			$searchand = ' AND ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() ) {
				$search .= " AND ({$wpdb->posts}.post_password = '') ";
			}
		}

		return $search;
	}

	/**
	 * Kiểm tra xem các từ có phù hợp để tìm kiếm hay không.
	 *
	 * Sử dụng mảng các từ dừng (terms) được loại trừ khỏi việc khớp
	 * từ riêng biệt khi tìm kiếm bài viết. Danh sách từ dừng tiếng Anh
	 * là danh sách gần đúng của các công cụ tìm kiếm, và có thể dịch được.
	 *
	 * @since 3.7.0
	 *
	 * @param string[] $terms Mảng các từ cần kiểm tra.
	 * @return string[] Các từ không phải từ dừng.
	 */
	protected function parse_search_terms( $terms ) {
		$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
		$checked    = array();

		$stopwords = $this->get_search_stopwords();

		foreach ( $terms as $term ) {
			// Giữ khoảng trắng trước/sau khi từ dùng cho khớp chính xác.
			if ( preg_match( '/^".+"$/', $term ) ) {
				$term = trim( $term, "\"'" );
			} else {
				$term = trim( $term, "\"' " );
			}

			// Tránh các ký tự A-Z đơn lẻ và dấu gạch ngang đơn lẻ.
			if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
				continue;
			}

			if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) ) {
				continue;
			}

			$checked[] = $term;
		}

		return $checked;
	}

	/**
	 * Lấy danh sách từ dừng được sử dụng khi phân tích các từ tìm kiếm.
	 *
	 * @since 3.7.0
	 *
	 * @return string[] Các từ dừng.
	 */
	protected function get_search_stopwords() {
		if ( isset( $this->stopwords ) ) {
			return $this->stopwords;
		}

		/*
		 * translators: Đây là danh sách phân cách bằng dấu phẩy các từ rất phổ biến nên được loại trừ khỏi tìm kiếm,
		 * như a, an, và the. Chúng thường được gọi là "từ dừng". Bạn không nên đơn giản dịch từng từ
		 * sang ngôn ngữ của mình. Thay vào đó, hãy tìm và cung cấp các từ dừng được chấp nhận phổ biến trong ngôn ngữ của bạn.
		 */
		$words = explode(
			',',
			_x(
				'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
				'Comma-separated list of search stopwords in your language'
			)
		);

		$stopwords = array();
		foreach ( $words as $word ) {
			$word = trim( $word, "\r\n\t " );
			if ( $word ) {
				$stopwords[] = $word;
			}
		}

		/**
		 * Lọc các từ dừng được sử dụng khi phân tích các từ tìm kiếm.
		 *
		 * @since 3.7.0
		 *
		 * @param string[] $stopwords Mảng các từ dừng.
		 */
		$this->stopwords = apply_filters( 'wp_search_stopwords', $stopwords );
		return $this->stopwords;
	}

	/**
	 * Tạo SQL cho điều kiện ORDER BY dựa trên các từ tìm kiếm được truyền vào.
	 *
	 * @since 3.7.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param array $q Các biến truy vấn.
	 * @return string Mệnh đề ORDER BY.
	 */
	protected function parse_search_order( &$q ) {
		global $wpdb;

		if ( $q['search_terms_count'] > 1 ) {
			$num_terms = count( $q['search_orderby_title'] );

			// Nếu các từ tìm kiếm chứa truy vấn phủ định, không cần sắp xếp theo khớp câu.
			$like = '';
			if ( ! preg_match( '/(?:\s|^)\-/', $q['s'] ) ) {
				$like = '%' . $wpdb->esc_like( $q['s'] ) . '%';
			}

			$search_orderby = '';

			// Khớp câu trong 'post_title'.
			if ( $like ) {
				$search_orderby .= $wpdb->prepare( "WHEN {$wpdb->posts}.post_title LIKE %s THEN 1 ", $like );
			}

			/*
			 * Giới hạn hợp lý, sắp xếp như câu khi có nhiều hơn 6 từ
			 * (ít tìm kiếm nào dài hơn 6 từ và hầu hết tiêu đề cũng không).
			 */
			if ( $num_terms < 7 ) {
				// Tất cả các từ trong tiêu đề.
				$search_orderby .= 'WHEN ' . implode( ' AND ', $q['search_orderby_title'] ) . ' THEN 2 ';
				// Bất kỳ từ nào trong tiêu đề, không cần khi $num_terms == 1.
				if ( $num_terms > 1 ) {
					$search_orderby .= 'WHEN ' . implode( ' OR ', $q['search_orderby_title'] ) . ' THEN 3 ';
				}
			}

			// Khớp câu trong 'post_content' và 'post_excerpt'.
			if ( $like ) {
				$search_orderby .= $wpdb->prepare( "WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 4 ", $like );
				$search_orderby .= $wpdb->prepare( "WHEN {$wpdb->posts}.post_content LIKE %s THEN 5 ", $like );
			}

			if ( $search_orderby ) {
				$search_orderby = '(CASE ' . $search_orderby . 'ELSE 6 END)';
			}
		} else {
			// Tìm kiếm từ đơn hoặc câu.
			$search_orderby = reset( $q['search_orderby_title'] ) . ' DESC';
		}

		return $search_orderby;
	}

	/**
	 * Chuyển đổi bí danh orderby đã cho (nếu được phép) thành giá trị có tiền tố bảng phù hợp.
	 *
	 * @since 4.0.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param string $orderby Bí danh cho trường cần sắp xếp.
	 * @return string|false Giá trị có tiền tố bảng dùng trong mệnh đề ORDER. False nếu không hợp lệ.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		// Dùng để lọc giá trị.
		$allowed_keys = array(
			'post_name',
			'post_author',
			'post_date',
			'post_title',
			'post_modified',
			'post_parent',
			'post_type',
			'name',
			'author',
			'date',
			'title',
			'modified',
			'parent',
			'type',
			'ID',
			'menu_order',
			'comment_count',
			'rand',
			'post__in',
			'post_parent__in',
			'post_name__in',
		);

		$primary_meta_key   = '';
		$primary_meta_query = false;
		$meta_clauses       = $this->meta_query->get_clauses();
		if ( ! empty( $meta_clauses ) ) {
			$primary_meta_query = reset( $meta_clauses );

			if ( ! empty( $primary_meta_query['key'] ) ) {
				$primary_meta_key = $primary_meta_query['key'];
				$allowed_keys[]   = $primary_meta_key;
			}

			$allowed_keys[] = 'meta_value';
			$allowed_keys[] = 'meta_value_num';
			$allowed_keys   = array_merge( $allowed_keys, array_keys( $meta_clauses ) );
		}

		// Nếu RAND() chứa giá trị seed, làm sạch và thêm vào các khóa được phép.
		$rand_with_seed = false;
		if ( preg_match( '/RAND\(([0-9]+)\)/i', $orderby, $matches ) ) {
			$orderby        = sprintf( 'RAND(%s)', (int) $matches[1] );
			$allowed_keys[] = $orderby;
			$rand_with_seed = true;
		}

		if ( ! in_array( $orderby, $allowed_keys, true ) ) {
			return false;
		}

		$orderby_clause = '';

		switch ( $orderby ) {
			case 'post_name':
			case 'post_author':
			case 'post_date':
			case 'post_title':
			case 'post_modified':
			case 'post_parent':
			case 'post_type':
			case 'ID':
			case 'menu_order':
			case 'comment_count':
				$orderby_clause = "{$wpdb->posts}.{$orderby}";
				break;
			case 'rand':
				$orderby_clause = 'RAND()';
				break;
			case $primary_meta_key:
			case 'meta_value':
				if ( ! empty( $primary_meta_query['type'] ) ) {
					$orderby_clause = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
				} else {
					$orderby_clause = "{$primary_meta_query['alias']}.meta_value";
				}
				break;
			case 'meta_value_num':
				$orderby_clause = "{$primary_meta_query['alias']}.meta_value+0";
				break;
			case 'post__in':
				if ( ! empty( $this->query_vars['post__in'] ) ) {
					$orderby_clause = "FIELD({$wpdb->posts}.ID," . implode( ',', array_map( 'absint', $this->query_vars['post__in'] ) ) . ')';
				}
				break;
			case 'post_parent__in':
				if ( ! empty( $this->query_vars['post_parent__in'] ) ) {
					$orderby_clause = "FIELD( {$wpdb->posts}.post_parent," . implode( ', ', array_map( 'absint', $this->query_vars['post_parent__in'] ) ) . ' )';
				}
				break;
			case 'post_name__in':
				if ( ! empty( $this->query_vars['post_name__in'] ) ) {
					$post_name__in        = array_map( 'sanitize_title_for_query', $this->query_vars['post_name__in'] );
					$post_name__in_string = "'" . implode( "','", $post_name__in ) . "'";
					$orderby_clause       = "FIELD( {$wpdb->posts}.post_name," . $post_name__in_string . ' )';
				}
				break;
			default:
				if ( array_key_exists( $orderby, $meta_clauses ) ) {
					// $orderby tương ứng với một mệnh đề meta_query.
					$meta_clause    = $meta_clauses[ $orderby ];
					$orderby_clause = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
				} elseif ( $rand_with_seed ) {
					$orderby_clause = $orderby;
				} else {
					// Mặc định: sắp xếp theo trường bài viết.
					$orderby_clause = "{$wpdb->posts}.post_" . sanitize_key( $orderby );
				}

				break;
		}

		return $orderby_clause;
	}

	/**
	 * Phân tích biến truy vấn 'order' và chuyển đổi sang ASC hoặc DESC nếu cần.
	 *
	 * @since 4.0.0
	 *
	 * @param string $order Biến truy vấn 'order'.
	 * @return string Biến truy vấn 'order' đã được làm sạch.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Thiết lập thuộc tính 404 và lưu trạng thái feed của truy vấn.
	 *
	 * @since 2.0.0
	 */
	public function set_404() {
		$is_feed = $this->is_feed;

		$this->init_query_flags();
		$this->is_404 = true;

		$this->is_feed = $is_feed;

		/**
		 * Kích hoạt sau khi lỗi 404 được kích hoạt.
		 *
		 * @since 5.5.0
		 *
		 * @param WP_Query $query Thể hiện WP_Query (truyền theo tham chiếu).
		 */
		do_action_ref_array( 'set_404', array( $this ) );
	}

	/**
	 * Lấy giá trị của một biến truy vấn.
	 *
	 * @since 1.5.0
	 * @since 3.9.0 Giới thiệu tham số `$default_value`.
	 *
	 * @param string $query_var     Khóa biến truy vấn.
	 * @param mixed  $default_value Tùy chọn. Giá trị trả về nếu biến truy vấn chưa được thiết lập.
	 *                              Mặc định chuỗi rỗng.
	 * @return mixed Nội dung của biến truy vấn.
	 */
	public function get( $query_var, $default_value = '' ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}

		return $default_value;
	}

	/**
	 * Thiết lập giá trị của một biến truy vấn.
	 *
	 * @since 1.5.0
	 *
	 * @param string $query_var Khóa biến truy vấn.
	 * @param mixed  $value     Giá trị biến truy vấn.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[ $query_var ] = $value;
	}

	/**
	 * Lấy mảng các bài viết dựa trên biến truy vấn.
	 *
	 * Có một số bộ lọc và hành động có thể dùng để chỉnh sửa
	 * truy vấn cơ sở dữ liệu bài viết.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @return WP_Post[]|int[] Mảng các đối tượng bài viết hoặc ID bài viết.
	 */
	public function get_posts() {
		global $wpdb;

		$this->parse_query();

		/**
		 * Kích hoạt sau khi đối tượng biến truy vấn được tạo, nhưng trước khi truy vấn thực tế được chạy.
		 *
		 * Lưu ý: Nếu sử dụng các thẻ điều kiện, hãy dùng phiên bản phương thức trong thể hiện được truyền
		 * (ví dụ: $this->is_main_query() thay vì is_main_query()). Điều này bởi vì các hàm
		 * như is_main_query() kiểm tra đối với thể hiện $wp_query toàn cục, không phải thể hiện được truyền.
		 *
		 * @since 2.0.0
		 *
		 * @param WP_Query $query Thể hiện WP_Query (truyền theo tham chiếu).
		 */
		do_action_ref_array( 'pre_get_posts', array( &$this ) );

		// Viết tắt.
		$q = &$this->query_vars;

		// Điền lại trong trường hợp 'pre_get_posts' đã hủy thiết lập một số biến.
		$q = $this->fill_query_vars( $q );

		/**
		 * Lọc xem truy vấn tệp đính kèm có nên bao gồm tên tệp hay không.
		 *
		 * @since 6.0.3
		 *
		 * @param bool $allow_query_attachment_by_filename Có bao gồm tên tệp hay không.
		 */
		$this->allow_query_attachment_by_filename = apply_filters( 'wp_allow_query_attachment_by_filename', false );
		remove_all_filters( 'wp_allow_query_attachment_by_filename' );

		// Phân tích truy vấn meta.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $q );

		// Đặt cờ nếu hook 'pre_get_posts' đã thay đổi các biến truy vấn.
		$hash = md5( serialize( $this->query_vars ) );
		if ( $hash !== $this->query_vars_hash ) {
			$this->query_vars_changed = true;
			$this->query_vars_hash    = $hash;
		}
		unset( $hash );

		// Trước tiên, xóa một số biến.
		$distinct         = '';
		$whichauthor      = '';
		$whichmimetype    = '';
		$where            = '';
		$limits           = '';
		$join             = '';
		$search           = '';
		$groupby          = '';
		$post_status_join = false;
		$page             = 1;

		if ( isset( $q['caller_get_posts'] ) ) {
			_deprecated_argument(
				'WP_Query',
				'3.1.0',
				sprintf(
					/* translators: 1: caller_get_posts, 2: ignore_sticky_posts */
					__( '%1$s is deprecated. Use %2$s instead.' ),
					'<code>caller_get_posts</code>',
					'<code>ignore_sticky_posts</code>'
				)
			);

			if ( ! isset( $q['ignore_sticky_posts'] ) ) {
				$q['ignore_sticky_posts'] = $q['caller_get_posts'];
			}
		}

		if ( ! isset( $q['ignore_sticky_posts'] ) ) {
			$q['ignore_sticky_posts'] = false;
		}

		if ( ! isset( $q['suppress_filters'] ) ) {
			$q['suppress_filters'] = false;
		}

		if ( ! isset( $q['cache_results'] ) ) {
			$q['cache_results'] = true;
		}

		if ( ! isset( $q['update_post_term_cache'] ) ) {
			$q['update_post_term_cache'] = true;
		}

		if ( ! isset( $q['update_menu_item_cache'] ) ) {
			$q['update_menu_item_cache'] = false;
		}

		if ( ! isset( $q['lazy_load_term_meta'] ) ) {
			$q['lazy_load_term_meta'] = $q['update_post_term_cache'];
		} elseif ( $q['lazy_load_term_meta'] ) { // Tải lười meta term chỉ hoạt động nếu bộ nhớ đệm term đã được nạp trước.
			$q['update_post_term_cache'] = true;
		}

		if ( ! isset( $q['update_post_meta_cache'] ) ) {
			$q['update_post_meta_cache'] = true;
		}

		if ( ! isset( $q['post_type'] ) ) {
			if ( $this->is_search ) {
				$q['post_type'] = 'any';
			} else {
				$q['post_type'] = '';
			}
		}
		$post_type = $q['post_type'];
		if ( empty( $q['posts_per_page'] ) ) {
			$q['posts_per_page'] = get_option( 'posts_per_page' );
		}
		if ( isset( $q['showposts'] ) && $q['showposts'] ) {
			$q['showposts']      = (int) $q['showposts'];
			$q['posts_per_page'] = $q['showposts'];
		}
		if ( ( isset( $q['posts_per_archive_page'] ) && 0 != $q['posts_per_archive_page'] ) && ( $this->is_archive || $this->is_search ) ) {
			$q['posts_per_page'] = $q['posts_per_archive_page'];
		}
		if ( ! isset( $q['nopaging'] ) ) {
			if ( -1 == $q['posts_per_page'] ) {
				$q['nopaging'] = true;
			} else {
				$q['nopaging'] = false;
			}
		}

		if ( $this->is_feed ) {
			// Điều này ghi đè 'posts_per_page'.
			if ( ! empty( $q['posts_per_rss'] ) ) {
				$q['posts_per_page'] = $q['posts_per_rss'];
			} else {
				$q['posts_per_page'] = get_option( 'posts_per_rss' );
			}
			$q['nopaging'] = false;
		}

		$q['posts_per_page'] = (int) $q['posts_per_page'];
		if ( $q['posts_per_page'] < -1 ) {
			$q['posts_per_page'] = abs( $q['posts_per_page'] );
		} elseif ( 0 === $q['posts_per_page'] ) {
			$q['posts_per_page'] = 1;
		}

		if ( ! isset( $q['comments_per_page'] ) || 0 == $q['comments_per_page'] ) {
			$q['comments_per_page'] = get_option( 'comments_per_page' );
		}

		if ( $this->is_home && ( empty( $this->query ) || 'true' === $q['preview'] ) && ( 'page' === get_option( 'show_on_front' ) ) && get_option( 'page_on_front' ) ) {
			$this->is_page = true;
			$this->is_home = false;
			$q['page_id']  = get_option( 'page_on_front' );
		}

		if ( isset( $q['page'] ) ) {
			$q['page'] = is_scalar( $q['page'] ) ? absint( trim( $q['page'], '/' ) ) : 0;
		}

		// Nếu true, buộc tắt SQL_CALC_FOUND_ROWS ngay cả khi có mệnh đề giới hạn.
		if ( isset( $q['no_found_rows'] ) ) {
			$q['no_found_rows'] = (bool) $q['no_found_rows'];
		} else {
			$q['no_found_rows'] = false;
		}

		switch ( $q['fields'] ) {
			case 'ids':
				$fields = "{$wpdb->posts}.ID";
				break;
			case 'id=>parent':
				$fields = "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent";
				break;
			case '':
				/*
				 * Đặt giá trị mặc định thành 'all'.
				 *
				 * Điều này được sử dụng trong `WP_Query::the_post` để xác định
				 * liệu toàn bộ đối tượng bài viết đã được truy vấn hay chưa.
				 */
				$q['fields'] = 'all';
				// Tiếp tục xuống.
			default:
				$fields = "{$wpdb->posts}.*";
		}

		if ( '' !== $q['menu_order'] ) {
			$where .= " AND {$wpdb->posts}.menu_order = " . $q['menu_order'];
		}
		// Tham số "m" dùng cho tháng nhưng chấp nhận datetime với độ chính xác khác nhau.
		if ( $q['m'] ) {
			$where .= " AND YEAR({$wpdb->posts}.post_date)=" . substr( $q['m'], 0, 4 );
			if ( strlen( $q['m'] ) > 5 ) {
				$where .= " AND MONTH({$wpdb->posts}.post_date)=" . substr( $q['m'], 4, 2 );
			}
			if ( strlen( $q['m'] ) > 7 ) {
				$where .= " AND DAYOFMONTH({$wpdb->posts}.post_date)=" . substr( $q['m'], 6, 2 );
			}
			if ( strlen( $q['m'] ) > 9 ) {
				$where .= " AND HOUR({$wpdb->posts}.post_date)=" . substr( $q['m'], 8, 2 );
			}
			if ( strlen( $q['m'] ) > 11 ) {
				$where .= " AND MINUTE({$wpdb->posts}.post_date)=" . substr( $q['m'], 10, 2 );
			}
			if ( strlen( $q['m'] ) > 13 ) {
				$where .= " AND SECOND({$wpdb->posts}.post_date)=" . substr( $q['m'], 12, 2 );
			}
		}

		// Xử lý các tham số ngày tháng riêng lẻ khác.
		$date_parameters = array();

		if ( '' !== $q['hour'] ) {
			$date_parameters['hour'] = $q['hour'];
		}

		if ( '' !== $q['minute'] ) {
			$date_parameters['minute'] = $q['minute'];
		}

		if ( '' !== $q['second'] ) {
			$date_parameters['second'] = $q['second'];
		}

		if ( $q['year'] ) {
			$date_parameters['year'] = $q['year'];
		}

		if ( $q['monthnum'] ) {
			$date_parameters['monthnum'] = $q['monthnum'];
		}

		if ( $q['w'] ) {
			$date_parameters['week'] = $q['w'];
		}

		if ( $q['day'] ) {
			$date_parameters['day'] = $q['day'];
		}

		if ( $date_parameters ) {
			$date_query = new WP_Date_Query( array( $date_parameters ) );
			$where     .= $date_query->get_sql();
		}
		unset( $date_parameters, $date_query );

		// Xử lý các truy vấn ngày tháng phức tạp.
		if ( ! empty( $q['date_query'] ) ) {
			$this->date_query = new WP_Date_Query( $q['date_query'] );
			$where           .= $this->date_query->get_sql();
		}

		// Nếu có post_type VÀ nó không phải post_type "any".
		if ( ! empty( $q['post_type'] ) && 'any' !== $q['post_type'] ) {
			foreach ( (array) $q['post_type'] as $_post_type ) {
				$ptype_obj = get_post_type_object( $_post_type );
				if ( ! $ptype_obj || ! $ptype_obj->query_var || empty( $q[ $ptype_obj->query_var ] ) ) {
					continue;
				}

				if ( ! $ptype_obj->hierarchical ) {
					// Loại bài viết không phân cấp có thể sử dụng trực tiếp 'name'.
					$q['name'] = $q[ $ptype_obj->query_var ];
				} else {
					// Loại bài viết phân cấp sẽ hoạt động thông qua 'pagename'.
					$q['pagename'] = $q[ $ptype_obj->query_var ];
					$q['name']     = '';
				}

				// Chỉ có thể có một yêu cầu cho slug, đây là lý do name & pagename bị ghi đè ở trên.
				break;
			} // Kết thúc foreach.
			unset( $ptype_obj );
		}

		if ( '' !== $q['title'] ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title = %s", stripslashes( $q['title'] ) );
		}

		// Các tham số liên quan đến 'post_name'.
		if ( '' !== $q['name'] ) {
			$q['name'] = sanitize_title_for_query( $q['name'] );
			$where    .= " AND {$wpdb->posts}.post_name = '" . $q['name'] . "'";
		} elseif ( '' !== $q['pagename'] ) {
			if ( isset( $this->queried_object_id ) ) {
				$reqpage = $this->queried_object_id;
			} else {
				if ( 'page' !== $q['post_type'] ) {
					foreach ( (array) $q['post_type'] as $_post_type ) {
						$ptype_obj = get_post_type_object( $_post_type );
						if ( ! $ptype_obj || ! $ptype_obj->hierarchical ) {
							continue;
						}

						$reqpage = get_page_by_path( $q['pagename'], OBJECT, $_post_type );
						if ( $reqpage ) {
							break;
						}
					}
					unset( $ptype_obj );
				} else {
					$reqpage = get_page_by_path( $q['pagename'] );
				}
				if ( ! empty( $reqpage ) ) {
					$reqpage = $reqpage->ID;
				} else {
					$reqpage = 0;
				}
			}

			$page_for_posts = get_option( 'page_for_posts' );
			if ( ( 'page' !== get_option( 'show_on_front' ) ) || empty( $page_for_posts ) || ( $reqpage != $page_for_posts ) ) {
				$q['pagename'] = sanitize_title_for_query( wp_basename( $q['pagename'] ) );
				$q['name']     = $q['pagename'];
				$where        .= " AND ({$wpdb->posts}.ID = '$reqpage')";
				$reqpage_obj   = get_post( $reqpage );
				if ( is_object( $reqpage_obj ) && 'attachment' === $reqpage_obj->post_type ) {
					$this->is_attachment = true;
					$post_type           = 'attachment';
					$q['post_type']      = 'attachment';
					$this->is_page       = true;
					$q['attachment_id']  = $reqpage;
				}
			}
		} elseif ( '' !== $q['attachment'] ) {
			$q['attachment'] = sanitize_title_for_query( wp_basename( $q['attachment'] ) );
			$q['name']       = $q['attachment'];
			$where          .= " AND {$wpdb->posts}.post_name = '" . $q['attachment'] . "'";
		} elseif ( is_array( $q['post_name__in'] ) && ! empty( $q['post_name__in'] ) ) {
			$q['post_name__in'] = array_map( 'sanitize_title_for_query', $q['post_name__in'] );
			// Sao chép mảng trước khi sắp xếp để cho phép mệnh đề orderby.
			$post_name__in_for_where = array_unique( $q['post_name__in'] );
			sort( $post_name__in_for_where );
			$post_name__in = "'" . implode( "','", $post_name__in_for_where ) . "'";
			$where        .= " AND {$wpdb->posts}.post_name IN ($post_name__in)";
		}

		// Nếu tệp đính kèm được yêu cầu theo số, cho phép nó ghi đè bất kỳ số bài viết nào.
		if ( $q['attachment_id'] ) {
			$q['p'] = absint( $q['attachment_id'] );
		}

		// Nếu số bài viết được chỉ định, tải bài viết đó.
		if ( $q['p'] ) {
			$where .= " AND {$wpdb->posts}.ID = " . $q['p'];
		} elseif ( $q['post__in'] ) {
			// Sao chép mảng trước khi sắp xếp để cho phép mệnh đề orderby.
			$post__in_for_where = $q['post__in'];
			$post__in_for_where = array_unique( array_map( 'absint', $post__in_for_where ) );
			sort( $post__in_for_where );
			$post__in = implode( ',', array_map( 'absint', $post__in_for_where ) );
			$where   .= " AND {$wpdb->posts}.ID IN ($post__in)";
		} elseif ( $q['post__not_in'] ) {
			sort( $q['post__not_in'] );
			$post__not_in = implode( ',', array_map( 'absint', $q['post__not_in'] ) );
			$where       .= " AND {$wpdb->posts}.ID NOT IN ($post__not_in)";
		}

		if ( is_numeric( $q['post_parent'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_parent = %d ", $q['post_parent'] );
		} elseif ( $q['post_parent__in'] ) {
			// Sao chép mảng trước khi sắp xếp để cho phép mệnh đề orderby.
			$post_parent__in_for_where = $q['post_parent__in'];
			$post_parent__in_for_where = array_unique( array_map( 'absint', $post_parent__in_for_where ) );
			sort( $post_parent__in_for_where );
			$post_parent__in = implode( ',', array_map( 'absint', $post_parent__in_for_where ) );
			$where          .= " AND {$wpdb->posts}.post_parent IN ($post_parent__in)";
		} elseif ( $q['post_parent__not_in'] ) {
			sort( $q['post_parent__not_in'] );
			$post_parent__not_in = implode( ',', array_map( 'absint', $q['post_parent__not_in'] ) );
			$where              .= " AND {$wpdb->posts}.post_parent NOT IN ($post_parent__not_in)";
		}

		if ( $q['page_id'] ) {
			if ( ( 'page' !== get_option( 'show_on_front' ) ) || ( get_option( 'page_for_posts' ) != $q['page_id'] ) ) {
				$q['p'] = $q['page_id'];
				$where  = " AND {$wpdb->posts}.ID = " . $q['page_id'];
			}
		}

		// Nếu mẫu tìm kiếm được chỉ định, tải các bài viết khớp.
		if ( strlen( $q['s'] ) ) {
			$search = $this->parse_search( $q );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Lọc SQL tìm kiếm được sử dụng trong mệnh đề WHERE của WP_Query.
			 *
			 * @since 3.0.0
			 *
			 * @param string   $search SQL tìm kiếm cho mệnh đề WHERE.
			 * @param WP_Query $query  Đối tượng WP_Query hiện tại.
			 */
			$search = apply_filters_ref_array( 'posts_search', array( $search, &$this ) );
		}

		// Taxonomy.
		if ( ! $this->is_singular ) {
			$this->parse_tax_query( $q );

			$clauses = $this->tax_query->get_sql( $wpdb->posts, 'ID' );

			$join  .= $clauses['join'];
			$where .= $clauses['where'];
		}

		if ( $this->is_tax ) {
			if ( empty( $post_type ) ) {
				// Tìm kiếm toàn diện cho các loại bài viết đã đăng ký của taxonomy được truy vấn.
				$post_type  = array();
				$taxonomies = array_keys( $this->tax_query->queried_terms );
				foreach ( get_post_types( array( 'exclude_from_search' => false ) ) as $pt ) {
					$object_taxonomies = 'attachment' === $pt ? get_taxonomies_for_attachments() : get_object_taxonomies( $pt );
					if ( array_intersect( $taxonomies, $object_taxonomies ) ) {
						$post_type[] = $pt;
					}
				}
				if ( ! $post_type ) {
					$post_type = 'any';
				} elseif ( count( $post_type ) === 1 ) {
					$post_type = $post_type[0];
				} else {
					// Sắp xếp loại bài viết để đảm bảo tạo cùng khóa bộ nhớ đệm.
					sort( $post_type );
				}

				$post_status_join = true;
			} elseif ( in_array( 'attachment', (array) $post_type, true ) ) {
				$post_status_join = true;
			}
		}

		/*
		 * Đảm bảo rằng các biến 'taxonomy', 'term', 'term_id', 'cat', và
		 * 'category_name' được thiết lập để tương thích ngược.
		 */
		if ( ! empty( $this->tax_query->queried_terms ) ) {

			/*
			 * Thiết lập 'taxonomy', 'term', và 'term_id' thành
			 * taxonomy đầu tiên không phải 'post_tag' hoặc 'category'.
			 */
			if ( ! isset( $q['taxonomy'] ) ) {
				foreach ( $this->tax_query->queried_terms as $queried_taxonomy => $queried_items ) {
					if ( empty( $queried_items['terms'][0] ) ) {
						continue;
					}

					if ( ! in_array( $queried_taxonomy, array( 'category', 'post_tag' ), true ) ) {
						$q['taxonomy'] = $queried_taxonomy;

						if ( 'slug' === $queried_items['field'] ) {
							$q['term'] = $queried_items['terms'][0];
						} else {
							$q['term_id'] = $queried_items['terms'][0];
						}

						// Lấy cái đầu tiên tìm thấy.
						break;
					}
				}
			}

			// 'cat', 'category_name', 'tag_id'.
			foreach ( $this->tax_query->queried_terms as $queried_taxonomy => $queried_items ) {
				if ( empty( $queried_items['terms'][0] ) ) {
					continue;
				}

				if ( 'category' === $queried_taxonomy ) {
					$the_cat = get_term_by( $queried_items['field'], $queried_items['terms'][0], 'category' );
					if ( $the_cat ) {
						$this->set( 'cat', $the_cat->term_id );
						$this->set( 'category_name', $the_cat->slug );
					}
					unset( $the_cat );
				}

				if ( 'post_tag' === $queried_taxonomy ) {
					$the_tag = get_term_by( $queried_items['field'], $queried_items['terms'][0], 'post_tag' );
					if ( $the_tag ) {
						$this->set( 'tag_id', $the_tag->term_id );
					}
					unset( $the_tag );
				}
			}
		}

		if ( ! empty( $this->tax_query->queries ) || ! empty( $this->meta_query->queries ) || ! empty( $this->allow_query_attachment_by_filename ) ) {
			$groupby = "{$wpdb->posts}.ID";
		}

		// Xử lý tác giả/người dùng.

		if ( ! empty( $q['author'] ) && '0' != $q['author'] ) {
			$q['author'] = addslashes_gpc( '' . urldecode( $q['author'] ) );
			$authors     = array_unique( array_map( 'intval', preg_split( '/[,\s]+/', $q['author'] ) ) );
			sort( $authors );
			foreach ( $authors as $author ) {
				$key         = $author > 0 ? 'author__in' : 'author__not_in';
				$q[ $key ][] = abs( $author );
			}
			$q['author'] = implode( ',', $authors );
		}

		if ( ! empty( $q['author__not_in'] ) ) {
			if ( is_array( $q['author__not_in'] ) ) {
				$q['author__not_in'] = array_unique( array_map( 'absint', $q['author__not_in'] ) );
				sort( $q['author__not_in'] );
			}
			$author__not_in = implode( ',', (array) $q['author__not_in'] );
			$where         .= " AND {$wpdb->posts}.post_author NOT IN ($author__not_in) ";
		} elseif ( ! empty( $q['author__in'] ) ) {
			if ( is_array( $q['author__in'] ) ) {
				$q['author__in'] = array_unique( array_map( 'absint', $q['author__in'] ) );
				sort( $q['author__in'] );
			}
			$author__in = implode( ',', array_map( 'absint', array_unique( (array) $q['author__in'] ) ) );
			$where     .= " AND {$wpdb->posts}.post_author IN ($author__in) ";
		}

		// Xử lý tác giả cho URL thân thiện.

		if ( '' !== $q['author_name'] ) {
			if ( str_contains( $q['author_name'], '/' ) ) {
				$q['author_name'] = explode( '/', $q['author_name'] );
				if ( $q['author_name'][ count( $q['author_name'] ) - 1 ] ) {
					$q['author_name'] = $q['author_name'][ count( $q['author_name'] ) - 1 ]; // Không có dấu gạch chéo cuối.
				} else {
					$q['author_name'] = $q['author_name'][ count( $q['author_name'] ) - 2 ]; // Có dấu gạch chéo cuối.
				}
			}
			$q['author_name'] = sanitize_title_for_query( $q['author_name'] );
			$q['author']      = get_user_by( 'slug', $q['author_name'] );
			if ( $q['author'] ) {
				$q['author'] = $q['author']->ID;
			}
			$whichauthor .= " AND ({$wpdb->posts}.post_author = " . absint( $q['author'] ) . ')';
		}

		// Khớp theo số lượng bình luận.
		if ( isset( $q['comment_count'] ) ) {
			// Số lượng bình luận dạng số được chuyển đổi sang dạng mảng.
			if ( is_numeric( $q['comment_count'] ) ) {
				$q['comment_count'] = array(
					'value' => (int) $q['comment_count'],
				);
			}

			if ( isset( $q['comment_count']['value'] ) ) {
				$q['comment_count'] = array_merge(
					array(
						'compare' => '=',
					),
					$q['comment_count']
				);

				// Giá trị mặc định cho toán tử so sánh không hợp lệ là '='.
				$compare_operators = array( '=', '!=', '>', '>=', '<', '<=' );
				if ( ! in_array( $q['comment_count']['compare'], $compare_operators, true ) ) {
					$q['comment_count']['compare'] = '=';
				}

				$where .= $wpdb->prepare( " AND {$wpdb->posts}.comment_count {$q['comment_count']['compare']} %d", $q['comment_count']['value'] );
			}
		}

		// Xử lý MIME-Type cho duyệt tệp đính kèm.

		if ( isset( $q['post_mime_type'] ) && '' !== $q['post_mime_type'] ) {
			$whichmimetype = wp_post_mime_type_where( $q['post_mime_type'], $wpdb->posts );
		}
		$where .= $search . $whichauthor . $whichmimetype;

		if ( ! empty( $this->allow_query_attachment_by_filename ) ) {
			$join .= " LEFT JOIN {$wpdb->postmeta} AS sq1 ON ( {$wpdb->posts}.ID = sq1.post_id AND sq1.meta_key = '_wp_attached_file' )";
		}

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( 'post', $wpdb->posts, 'ID', $this );
			$join   .= $clauses['join'];
			$where  .= $clauses['where'];
		}

		$rand = ( isset( $q['orderby'] ) && 'rand' === $q['orderby'] );
		if ( ! isset( $q['order'] ) ) {
			$q['order'] = $rand ? '' : 'DESC';
		} else {
			$q['order'] = $rand ? '' : $this->parse_order( $q['order'] );
		}

		// Các giá trị orderby này nên bỏ qua tham số 'order'.
		$force_asc = array( 'post__in', 'post_name__in', 'post_parent__in' );
		if ( isset( $q['orderby'] ) && in_array( $q['orderby'], $force_asc, true ) ) {
			$q['order'] = '';
		}

		// Sắp xếp theo.
		if ( empty( $q['orderby'] ) ) {
			/*
			 * Boolean false hoặc mảng rỗng sẽ xóa ORDER BY,
			 * trong khi để giá trị chưa thiết lập hoặc rỗng sẽ đặt giá trị mặc định.
			 */
			if ( isset( $q['orderby'] ) && ( is_array( $q['orderby'] ) || false === $q['orderby'] ) ) {
				$orderby = '';
			} else {
				$orderby = "{$wpdb->posts}.post_date " . $q['order'];
			}
		} elseif ( 'none' === $q['orderby'] ) {
			$orderby = '';
		} else {
			$orderby_array = array();
			if ( is_array( $q['orderby'] ) ) {
				foreach ( $q['orderby'] as $_orderby => $order ) {
					$orderby = addslashes_gpc( urldecode( $_orderby ) );
					$parsed  = $this->parse_orderby( $orderby );

					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed . ' ' . $this->parse_order( $order );
				}
				$orderby = implode( ', ', $orderby_array );

			} else {
				$q['orderby'] = urldecode( $q['orderby'] );
				$q['orderby'] = addslashes_gpc( $q['orderby'] );

				foreach ( explode( ' ', $q['orderby'] ) as $i => $orderby ) {
					$parsed = $this->parse_orderby( $orderby );
					// Chỉ cho phép một số giá trị nhất định để đảm bảo an toàn.
					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed;
				}
				$orderby = implode( ' ' . $q['order'] . ', ', $orderby_array );

				if ( empty( $orderby ) ) {
					$orderby = "{$wpdb->posts}.post_date " . $q['order'];
				} elseif ( ! empty( $q['order'] ) ) {
					$orderby .= " {$q['order']}";
				}
			}
		}

		// Sắp xếp kết quả tìm kiếm theo mức độ liên quan chỉ khi không có "orderby" khác được chỉ định trong truy vấn.
		if ( ! empty( $q['s'] ) ) {
			$search_orderby = '';
			if ( ! empty( $q['search_orderby_title'] ) && ( empty( $q['orderby'] ) && ! $this->is_feed ) || ( isset( $q['orderby'] ) && 'relevance' === $q['orderby'] ) ) {
				$search_orderby = $this->parse_search_order( $q );
			}

			if ( ! $q['suppress_filters'] ) {
				/**
				 * Lọc mệnh đề ORDER BY khi sắp xếp kết quả tìm kiếm.
				 *
				 * @since 3.7.0
				 *
				 * @param string   $search_orderby The ORDER BY clause.
				 * @param WP_Query $query          The current WP_Query instance.
				 */
				$search_orderby = apply_filters( 'posts_search_orderby', $search_orderby, $this );
			}

			if ( $search_orderby ) {
				$orderby = $orderby ? $search_orderby . ', ' . $orderby : $search_orderby;
			}
		}

		if ( is_array( $post_type ) && count( $post_type ) > 1 ) {
			$post_type_cap = 'multiple_post_type';
		} else {
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			$post_type_object = get_post_type_object( $post_type );
			if ( empty( $post_type_object ) ) {
				$post_type_cap = $post_type;
			}
		}

		if ( isset( $q['post_password'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_password = %s", $q['post_password'] );
			if ( empty( $q['perm'] ) ) {
				$q['perm'] = 'readable';
			}
		} elseif ( isset( $q['has_password'] ) ) {
			$where .= sprintf( " AND {$wpdb->posts}.post_password %s ''", $q['has_password'] ? '!=' : '=' );
		}

		if ( ! empty( $q['comment_status'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.comment_status = %s ", $q['comment_status'] );
		}

		if ( ! empty( $q['ping_status'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ping_status = %s ", $q['ping_status'] );
		}

		$skip_post_status = false;
		if ( 'any' === $post_type ) {
			$in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) );
			if ( empty( $in_search_post_types ) ) {
				$post_type_where  = ' AND 1=0 ';
				$skip_post_status = true;
			} else {
				$post_type_where = " AND {$wpdb->posts}.post_type IN ('" . implode( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "')";
			}
		} elseif ( ! empty( $post_type ) && is_array( $post_type ) ) {
			// Sort post types to ensure same cache key generation.
			sort( $post_type );
			$post_type_where = " AND {$wpdb->posts}.post_type IN ('" . implode( "', '", esc_sql( $post_type ) ) . "')";
		} elseif ( ! empty( $post_type ) ) {
			$post_type_where  = $wpdb->prepare( " AND {$wpdb->posts}.post_type = %s", $post_type );
			$post_type_object = get_post_type_object( $post_type );
		} elseif ( $this->is_attachment ) {
			$post_type_where  = " AND {$wpdb->posts}.post_type = 'attachment'";
			$post_type_object = get_post_type_object( 'attachment' );
		} elseif ( $this->is_page ) {
			$post_type_where  = " AND {$wpdb->posts}.post_type = 'page'";
			$post_type_object = get_post_type_object( 'page' );
		} else {
			$post_type_where  = " AND {$wpdb->posts}.post_type = 'post'";
			$post_type_object = get_post_type_object( 'post' );
		}

		$edit_cap = 'edit_post';
		$read_cap = 'read_post';

		if ( ! empty( $post_type_object ) ) {
			$edit_others_cap  = $post_type_object->cap->edit_others_posts;
			$read_private_cap = $post_type_object->cap->read_private_posts;
		} else {
			$edit_others_cap  = 'edit_others_' . $post_type_cap . 's';
			$read_private_cap = 'read_private_' . $post_type_cap . 's';
		}

		$user_id = get_current_user_id();

		$q_status = array();
		if ( $skip_post_status ) {
			$where .= $post_type_where;
		} elseif ( ! empty( $q['post_status'] ) ) {

			$where .= $post_type_where;

			$statuswheres = array();
			$q_status     = $q['post_status'];
			if ( ! is_array( $q_status ) ) {
				$q_status = explode( ',', $q_status );
			}
			sort( $q_status );
			$r_status = array();
			$p_status = array();
			$e_status = array();
			if ( in_array( 'any', $q_status, true ) ) {
				foreach ( get_post_stati( array( 'exclude_from_search' => true ) ) as $status ) {
					if ( ! in_array( $status, $q_status, true ) ) {
						$e_status[] = "{$wpdb->posts}.post_status <> '$status'";
					}
				}
			} else {
				foreach ( get_post_stati() as $status ) {
					if ( in_array( $status, $q_status, true ) ) {
						if ( 'private' === $status ) {
							$p_status[] = "{$wpdb->posts}.post_status = '$status'";
						} else {
							$r_status[] = "{$wpdb->posts}.post_status = '$status'";
						}
					}
				}
			}

			if ( empty( $q['perm'] ) || 'readable' !== $q['perm'] ) {
				$r_status = array_merge( $r_status, $p_status );
				unset( $p_status );
			}

			if ( ! empty( $e_status ) ) {
				$statuswheres[] = '(' . implode( ' AND ', $e_status ) . ')';
			}
			if ( ! empty( $r_status ) ) {
				if ( ! empty( $q['perm'] ) && 'editable' === $q['perm'] && ! current_user_can( $edit_others_cap ) ) {
					$statuswheres[] = "({$wpdb->posts}.post_author = $user_id " . 'AND (' . implode( ' OR ', $r_status ) . '))';
				} else {
					$statuswheres[] = '(' . implode( ' OR ', $r_status ) . ')';
				}
			}
			if ( ! empty( $p_status ) ) {
				if ( ! empty( $q['perm'] ) && 'readable' === $q['perm'] && ! current_user_can( $read_private_cap ) ) {
					$statuswheres[] = "({$wpdb->posts}.post_author = $user_id " . 'AND (' . implode( ' OR ', $p_status ) . '))';
				} else {
					$statuswheres[] = '(' . implode( ' OR ', $p_status ) . ')';
				}
			}
			if ( $post_status_join ) {
				$join .= " LEFT JOIN {$wpdb->posts} AS p2 ON ({$wpdb->posts}.post_parent = p2.ID) ";
				foreach ( $statuswheres as $index => $statuswhere ) {
					$statuswheres[ $index ] = "($statuswhere OR ({$wpdb->posts}.post_status = 'inherit' AND " . str_replace( $wpdb->posts, 'p2', $statuswhere ) . '))';
				}
			}
			$where_status = implode( ' OR ', $statuswheres );
			if ( ! empty( $where_status ) ) {
				$where .= " AND ($where_status)";
			}
		} elseif ( ! $this->is_singular ) {
			if ( 'any' === $post_type ) {
				$queried_post_types = get_post_types( array( 'exclude_from_search' => false ) );
			} elseif ( is_array( $post_type ) ) {
				$queried_post_types = $post_type;
			} elseif ( ! empty( $post_type ) ) {
				$queried_post_types = array( $post_type );
			} else {
				$queried_post_types = array( 'post' );
			}

			if ( ! empty( $queried_post_types ) ) {
				sort( $queried_post_types );
				$status_type_clauses = array();

				foreach ( $queried_post_types as $queried_post_type ) {

					$queried_post_type_object = get_post_type_object( $queried_post_type );

					$type_where = '(' . $wpdb->prepare( "{$wpdb->posts}.post_type = %s AND (", $queried_post_type );

					// Public statuses.
					$public_statuses = get_post_stati( array( 'public' => true ) );
					$status_clauses  = array();
					foreach ( $public_statuses as $public_status ) {
						$status_clauses[] = "{$wpdb->posts}.post_status = '$public_status'";
					}
					$type_where .= implode( ' OR ', $status_clauses );

					// Add protected states that should show in the admin all list.
					if ( $this->is_admin ) {
						$admin_all_statuses = get_post_stati(
							array(
								'protected'              => true,
								'show_in_admin_all_list' => true,
							)
						);
						foreach ( $admin_all_statuses as $admin_all_status ) {
							$type_where .= " OR {$wpdb->posts}.post_status = '$admin_all_status'";
						}
					}

					// Add private states that are visible to current user.
					if ( is_user_logged_in() && $queried_post_type_object instanceof WP_Post_Type ) {
						$read_private_cap = $queried_post_type_object->cap->read_private_posts;
						$private_statuses = get_post_stati( array( 'private' => true ) );
						foreach ( $private_statuses as $private_status ) {
							$type_where .= current_user_can( $read_private_cap ) ? " \nOR {$wpdb->posts}.post_status = '$private_status'" : " \nOR ({$wpdb->posts}.post_author = $user_id AND {$wpdb->posts}.post_status = '$private_status')";
						}
					}

					$type_where .= '))';

					$status_type_clauses[] = $type_where;
				}

				if ( ! empty( $status_type_clauses ) ) {
					$where .= ' AND (' . implode( ' OR ', $status_type_clauses ) . ')';
				}
			} else {
				$where .= ' AND 1=0 ';
			}
		} else {
			$where .= $post_type_where;
		}

		/*
		 * Apply filters on where and join prior to paging so that any
		 * manipulations to them are reflected in the paging by day queries.
		 */
		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where', array( $where, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join', array( $join, &$this ) );
		}

		// Paging.
		if ( empty( $q['nopaging'] ) && ! $this->is_singular ) {
			$page = absint( $q['paged'] );
			if ( ! $page ) {
				$page = 1;
			}

			// If 'offset' is provided, it takes precedence over 'paged'.
			if ( isset( $q['offset'] ) && is_numeric( $q['offset'] ) ) {
				$q['offset'] = absint( $q['offset'] );
				$pgstrt      = $q['offset'] . ', ';
			} else {
				$pgstrt = absint( ( $page - 1 ) * $q['posts_per_page'] ) . ', ';
			}
			$limits = 'LIMIT ' . $pgstrt . $q['posts_per_page'];
		}

		// Comments feeds.
		if ( $this->is_comment_feed && ! $this->is_singular ) {
			if ( $this->is_archive || $this->is_search ) {
				$cjoin    = "JOIN {$wpdb->posts} ON ( {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID ) $join ";
				$cwhere   = "WHERE comment_approved = '1' $where";
				$cgroupby = "{$wpdb->comments}.comment_id";
			} else { // Other non-singular, e.g. front.
				$cjoin    = "JOIN {$wpdb->posts} ON ( {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID )";
				$cwhere   = "WHERE ( post_status = 'publish' OR ( post_status = 'inherit' AND post_type = 'attachment' ) ) AND comment_approved = '1'";
				$cgroupby = '';
			}

			if ( ! $q['suppress_filters'] ) {
				/**
				 * Filters the JOIN clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cjoin The JOIN clause of the query.
				 * @param WP_Query $query The WP_Query instance (passed by reference).
				 */
				$cjoin = apply_filters_ref_array( 'comment_feed_join', array( $cjoin, &$this ) );

				/**
				 * Filters the WHERE clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cwhere The WHERE clause of the query.
				 * @param WP_Query $query  The WP_Query instance (passed by reference).
				 */
				$cwhere = apply_filters_ref_array( 'comment_feed_where', array( $cwhere, &$this ) );

				/**
				 * Filters the GROUP BY clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cgroupby The GROUP BY clause of the query.
				 * @param WP_Query $query    The WP_Query instance (passed by reference).
				 */
				$cgroupby = apply_filters_ref_array( 'comment_feed_groupby', array( $cgroupby, &$this ) );

				/**
				 * Filters the ORDER BY clause of the comments feed query before sending.
				 *
				 * @since 2.8.0
				 *
				 * @param string   $corderby The ORDER BY clause of the query.
				 * @param WP_Query $query    The WP_Query instance (passed by reference).
				 */
				$corderby = apply_filters_ref_array( 'comment_feed_orderby', array( 'comment_date_gmt DESC', &$this ) );

				/**
				 * Filters the LIMIT clause of the comments feed query before sending.
				 *
				 * @since 2.8.0
				 *
				 * @param string   $climits The JOIN clause of the query.
				 * @param WP_Query $query   The WP_Query instance (passed by reference).
				 */
				$climits = apply_filters_ref_array( 'comment_feed_limits', array( 'LIMIT ' . get_option( 'posts_per_rss' ), &$this ) );
			}

			$cgroupby = ( ! empty( $cgroupby ) ) ? 'GROUP BY ' . $cgroupby : '';
			$corderby = ( ! empty( $corderby ) ) ? 'ORDER BY ' . $corderby : '';
			$climits  = ( ! empty( $climits ) ) ? $climits : '';

			$comments_request = "SELECT $distinct {$wpdb->comments}.comment_ID FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits";

			$key          = md5( $comments_request );
			$last_changed = wp_cache_get_last_changed( 'comment' ) . ':' . wp_cache_get_last_changed( 'posts' );

			$cache_key   = "comment_feed:$key:$last_changed";
			$comment_ids = wp_cache_get( $cache_key, 'comment-queries' );
			if ( false === $comment_ids ) {
				$comment_ids = $wpdb->get_col( $comments_request );
				wp_cache_add( $cache_key, $comment_ids, 'comment-queries' );
			}
			_prime_comment_caches( $comment_ids );

			// Convert to WP_Comment.
			/** @var WP_Comment[] */
			$this->comments      = array_map( 'get_comment', $comment_ids );
			$this->comment_count = count( $this->comments );

			$post_ids = array();

			foreach ( $this->comments as $comment ) {
				$post_ids[] = (int) $comment->comment_post_ID;
			}

			$post_ids = implode( ',', $post_ids );
			$join     = '';
			if ( $post_ids ) {
				$where = "AND {$wpdb->posts}.ID IN ($post_ids) ";
			} else {
				$where = 'AND 0';
			}
		}

		$pieces = array( 'where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits' );

		/*
		 * Apply post-paging filters on where and join. Only plugins that
		 * manipulate paging queries should use these hooks.
		 */
		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * Specifically for manipulating paging queries.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where_paged', array( $where, &$this ) );

			/**
			 * Filters the GROUP BY clause of the query.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $groupby The GROUP BY clause of the query.
			 * @param WP_Query $query   The WP_Query instance (passed by reference).
			 */
			$groupby = apply_filters_ref_array( 'posts_groupby', array( $groupby, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * Specifically for manipulating paging queries.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join_paged', array( $join, &$this ) );

			/**
			 * Filters the ORDER BY clause of the query.
			 *
			 * @since 1.5.1
			 *
			 * @param string   $orderby The ORDER BY clause of the query.
			 * @param WP_Query $query   The WP_Query instance (passed by reference).
			 */
			$orderby = apply_filters_ref_array( 'posts_orderby', array( $orderby, &$this ) );

			/**
			 * Filters the DISTINCT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $distinct The DISTINCT clause of the query.
			 * @param WP_Query $query    The WP_Query instance (passed by reference).
			 */
			$distinct = apply_filters_ref_array( 'posts_distinct', array( $distinct, &$this ) );

			/**
			 * Filters the LIMIT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $limits The LIMIT clause of the query.
			 * @param WP_Query $query  The WP_Query instance (passed by reference).
			 */
			$limits = apply_filters_ref_array( 'post_limits', array( $limits, &$this ) );

			/**
			 * Filters the SELECT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $fields The SELECT clause of the query.
			 * @param WP_Query $query  The WP_Query instance (passed by reference).
			 */
			$fields = apply_filters_ref_array( 'posts_fields', array( $fields, &$this ) );

			/**
			 * Filters all query clauses at once, for convenience.
			 *
			 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
			 * fields (SELECT), and LIMIT clauses.
			 *
			 * @since 3.1.0
			 *
			 * @param string[] $clauses {
			 *     Associative array of the clauses for the query.
			 *
			 *     @type string $where    The WHERE clause of the query.
			 *     @type string $groupby  The GROUP BY clause of the query.
			 *     @type string $join     The JOIN clause of the query.
			 *     @type string $orderby  The ORDER BY clause of the query.
			 *     @type string $distinct The DISTINCT clause of the query.
			 *     @type string $fields   The SELECT clause of the query.
			 *     @type string $limits   The LIMIT clause of the query.
			 * }
			 * @param WP_Query $query   The WP_Query instance (passed by reference).
			 */
			$clauses = (array) apply_filters_ref_array( 'posts_clauses', array( compact( $pieces ), &$this ) );

			$where    = isset( $clauses['where'] ) ? $clauses['where'] : '';
			$groupby  = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';
			$join     = isset( $clauses['join'] ) ? $clauses['join'] : '';
			$orderby  = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
			$distinct = isset( $clauses['distinct'] ) ? $clauses['distinct'] : '';
			$fields   = isset( $clauses['fields'] ) ? $clauses['fields'] : '';
			$limits   = isset( $clauses['limits'] ) ? $clauses['limits'] : '';
		}

		/**
		 * Fires to announce the query's current selection parameters.
		 *
		 * For use by caching plugins.
		 *
		 * @since 2.3.0
		 *
		 * @param string $selection The assembled selection query.
		 */
		do_action( 'posts_selection', $where . $groupby . $orderby . $limits . $join );

		/*
		 * Filters again for the benefit of caching plugins.
		 * Regular plugins should use the hooks above.
		 */
		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where_request', array( $where, &$this ) );

			/**
			 * Filters the GROUP BY clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $groupby The GROUP BY clause of the query.
			 * @param WP_Query $query   The WP_Query instance (passed by reference).
			 */
			$groupby = apply_filters_ref_array( 'posts_groupby_request', array( $groupby, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join_request', array( $join, &$this ) );

			/**
			 * Filters the ORDER BY clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $orderby The ORDER BY clause of the query.
			 * @param WP_Query $query   The WP_Query instance (passed by reference).
			 */
			$orderby = apply_filters_ref_array( 'posts_orderby_request', array( $orderby, &$this ) );

			/**
			 * Filters the DISTINCT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $distinct The DISTINCT clause of the query.
			 * @param WP_Query $query    The WP_Query instance (passed by reference).
			 */
			$distinct = apply_filters_ref_array( 'posts_distinct_request', array( $distinct, &$this ) );

			/**
			 * Filters the SELECT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $fields The SELECT clause of the query.
			 * @param WP_Query $query  The WP_Query instance (passed by reference).
			 */
			$fields = apply_filters_ref_array( 'posts_fields_request', array( $fields, &$this ) );

			/**
			 * Filters the LIMIT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $limits The LIMIT clause of the query.
			 * @param WP_Query $query  The WP_Query instance (passed by reference).
			 */
			$limits = apply_filters_ref_array( 'post_limits_request', array( $limits, &$this ) );

			/**
			 * Filters all query clauses at once, for convenience.
			 *
			 * For use by caching plugins.
			 *
			 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
			 * fields (SELECT), and LIMIT clauses.
			 *
			 * @since 3.1.0
			 *
			 * @param string[] $clauses {
			 *     Associative array of the clauses for the query.
			 *
			 *     @type string $where    The WHERE clause of the query.
			 *     @type string $groupby  The GROUP BY clause of the query.
			 *     @type string $join     The JOIN clause of the query.
			 *     @type string $orderby  The ORDER BY clause of the query.
			 *     @type string $distinct The DISTINCT clause of the query.
			 *     @type string $fields   The SELECT clause of the query.
			 *     @type string $limits   The LIMIT clause of the query.
			 * }
			 * @param WP_Query $query  The WP_Query instance (passed by reference).
			 */
			$clauses = (array) apply_filters_ref_array( 'posts_clauses_request', array( compact( $pieces ), &$this ) );

			$where    = isset( $clauses['where'] ) ? $clauses['where'] : '';
			$groupby  = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';
			$join     = isset( $clauses['join'] ) ? $clauses['join'] : '';
			$orderby  = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
			$distinct = isset( $clauses['distinct'] ) ? $clauses['distinct'] : '';
			$fields   = isset( $clauses['fields'] ) ? $clauses['fields'] : '';
			$limits   = isset( $clauses['limits'] ) ? $clauses['limits'] : '';
		}

		if ( ! empty( $groupby ) ) {
			$groupby = 'GROUP BY ' . $groupby;
		}
		if ( ! empty( $orderby ) ) {
			$orderby = 'ORDER BY ' . $orderby;
		}

		$found_rows = '';
		if ( ! $q['no_found_rows'] && ! empty( $limits ) ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		/*
		 * Beginning of the string is on a new line to prevent leading whitespace.
		 *
		 * The additional indentation of subsequent lines is to ensure the SQL
		 * queries are identical to those generated when splitting queries. This
		 * improves caching of the query by ensuring the same cache key is
		 * generated for the same database queries functionally.
		 *
		 * See https://core.trac.wordpress.org/ticket/56841.
		 * See https://github.com/WordPress/wordpress-develop/pull/6393#issuecomment-2088217429
		 */
		$old_request =
			"SELECT $found_rows $distinct $fields
					 FROM {$wpdb->posts} $join
					 WHERE 1=1 $where
					 $groupby
					 $orderby
					 $limits";

		$this->request = $old_request;

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the completed SQL query before sending.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $request The complete SQL query.
			 * @param WP_Query $query   The WP_Query instance (passed by reference).
			 */
			$this->request = apply_filters_ref_array( 'posts_request', array( $this->request, &$this ) );
		}

		/**
		 * Filters the posts array before the query takes place.
		 *
		 * Return a non-null value to bypass WordPress' default post queries.
		 *
		 * Filtering functions that require pagination information are encouraged to set
		 * the `found_posts` and `max_num_pages` properties of the WP_Query object,
		 * passed to the filter by reference. If WP_Query does not perform a database
		 * query, it will not have enough information to generate these values itself.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Post[]|int[]|null $posts Return an array of post data to short-circuit WP's query,
		 *                                    or null to allow WP to run its normal queries.
		 * @param WP_Query             $query The WP_Query instance (passed by reference).
		 */
		$this->posts = apply_filters_ref_array( 'posts_pre_query', array( null, &$this ) );

		/*
		 * Ensure the ID database query is able to be cached.
		 *
		 * Random queries are expected to have unpredictable results and
		 * cannot be cached. Note the space before `RAND` in the string
		 * search, that to ensure against a collision with another
		 * function.
		 *
		 * If `$fields` has been modified by the `posts_fields`,
		 * `posts_fields_request`, `post_clauses` or `posts_clauses_request`
		 * filters, then caching is disabled to prevent caching collisions.
		 */
		$id_query_is_cacheable = ! str_contains( strtoupper( $orderby ), ' RAND(' );

		$cacheable_field_values = array(
			"{$wpdb->posts}.*",
			"{$wpdb->posts}.ID, {$wpdb->posts}.post_parent",
			"{$wpdb->posts}.ID",
		);

		if ( ! in_array( $fields, $cacheable_field_values, true ) ) {
			$id_query_is_cacheable = false;
		}

		if ( $q['cache_results'] && $id_query_is_cacheable ) {
			$new_request = str_replace( $fields, "{$wpdb->posts}.*", $this->request );
			$cache_key   = $this->generate_cache_key( $q, $new_request );

			$cache_found = false;
			if ( null === $this->posts ) {
				$cached_results = wp_cache_get( $cache_key, 'post-queries', false, $cache_found );

				if ( $cached_results ) {
					/** @var int[] */
					$post_ids = array_map( 'intval', $cached_results['posts'] );

					$this->post_count    = count( $post_ids );
					$this->found_posts   = $cached_results['found_posts'];
					$this->max_num_pages = $cached_results['max_num_pages'];

					if ( 'ids' === $q['fields'] ) {
						$this->posts = $post_ids;

						return $this->posts;
					} elseif ( 'id=>parent' === $q['fields'] ) {
						_prime_post_parent_id_caches( $post_ids );

						$post_parent_cache_keys = array();
						foreach ( $post_ids as $post_id ) {
							$post_parent_cache_keys[] = 'post_parent:' . (string) $post_id;
						}

						/** @var int[] */
						$post_parents = wp_cache_get_multiple( $post_parent_cache_keys, 'posts' );

						foreach ( $post_parents as $cache_key => $post_parent ) {
							$obj              = new stdClass();
							$obj->ID          = (int) str_replace( 'post_parent:', '', $cache_key );
							$obj->post_parent = (int) $post_parent;

							$this->posts[] = $obj;
						}

						return $post_parents;
					} else {
						_prime_post_caches( $post_ids, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
						/** @var WP_Post[] */
						$this->posts = array_map( 'get_post', $post_ids );
					}
				}
			}
		}

		if ( 'ids' === $q['fields'] ) {
			if ( null === $this->posts ) {
				$this->posts = $wpdb->get_col( $this->request );
			}

			/** @var int[] */
			$this->posts      = array_map( 'intval', $this->posts );
			$this->post_count = count( $this->posts );
			$this->set_found_posts( $q, $limits );

			if ( $q['cache_results'] && $id_query_is_cacheable ) {
				$cache_value = array(
					'posts'         => $this->posts,
					'found_posts'   => $this->found_posts,
					'max_num_pages' => $this->max_num_pages,
				);

				wp_cache_set( $cache_key, $cache_value, 'post-queries' );
			}

			return $this->posts;
		}

		if ( 'id=>parent' === $q['fields'] ) {
			if ( null === $this->posts ) {
				$this->posts = $wpdb->get_results( $this->request );
			}

			$this->post_count = count( $this->posts );
			$this->set_found_posts( $q, $limits );

			/** @var int[] */
			$post_parents       = array();
			$post_ids           = array();
			$post_parents_cache = array();

			foreach ( $this->posts as $key => $post ) {
				$this->posts[ $key ]->ID          = (int) $post->ID;
				$this->posts[ $key ]->post_parent = (int) $post->post_parent;

				$post_parents[ (int) $post->ID ] = (int) $post->post_parent;
				$post_ids[]                      = (int) $post->ID;

				$post_parents_cache[ 'post_parent:' . (string) $post->ID ] = (int) $post->post_parent;
			}
			// Prime post parent caches, so that on second run, there is not another database query.
			wp_cache_add_multiple( $post_parents_cache, 'posts' );

			if ( $q['cache_results'] && $id_query_is_cacheable ) {
				$cache_value = array(
					'posts'         => $post_ids,
					'found_posts'   => $this->found_posts,
					'max_num_pages' => $this->max_num_pages,
				);

				wp_cache_set( $cache_key, $cache_value, 'post-queries' );
			}

			return $post_parents;
		}

		$is_unfiltered_query = $old_request === $this->request && "{$wpdb->posts}.*" === $fields;

		if ( null === $this->posts ) {
			$split_the_query = (
				$is_unfiltered_query
				&& (
					wp_using_ext_object_cache()
					|| ( ! empty( $limits ) && $q['posts_per_page'] < 500 )
				)
			);

			/**
			 * Filters whether to split the query.
			 *
			 * Splitting the query will cause it to fetch just the IDs of the found posts
			 * (and then individually fetch each post by ID), rather than fetching every
			 * complete row at once. One massive result vs. many small results.
			 *
			 * @since 3.4.0
			 * @since 6.6.0 Added the `$old_request` and `$clauses` parameters.
			 *
			 * @param bool     $split_the_query Whether or not to split the query.
			 * @param WP_Query $query           The WP_Query instance.
			 * @param string   $old_request     The complete SQL query before filtering.
			 * @param string[] $clauses {
			 *     Associative array of the clauses for the query.
			 *
			 *     @type string $where    The WHERE clause of the query.
			 *     @type string $groupby  The GROUP BY clause of the query.
			 *     @type string $join     The JOIN clause of the query.
			 *     @type string $orderby  The ORDER BY clause of the query.
			 *     @type string $distinct The DISTINCT clause of the query.
			 *     @type string $fields   The SELECT clause of the query.
			 *     @type string $limits   The LIMIT clause of the query.
			 * }
			 */
			$split_the_query = apply_filters( 'split_the_query', $split_the_query, $this, $old_request, compact( $pieces ) );

			if ( $split_the_query ) {
				// First get the IDs and then fill in the objects.

				// Beginning of the string is on a new line to prevent leading whitespace. See https://core.trac.wordpress.org/ticket/56841.
				$this->request =
					"SELECT $found_rows $distinct {$wpdb->posts}.ID
					 FROM {$wpdb->posts} $join
					 WHERE 1=1 $where
					 $groupby
					 $orderby
					 $limits";

				/**
				 * Filters the Post IDs SQL request before sending.
				 *
				 * @since 3.4.0
				 *
				 * @param string   $request The post ID request.
				 * @param WP_Query $query   The WP_Query instance.
				 */
				$this->request = apply_filters( 'posts_request_ids', $this->request, $this );

				$post_ids = $wpdb->get_col( $this->request );

				if ( $post_ids ) {
					$this->posts = $post_ids;
					$this->set_found_posts( $q, $limits );
					_prime_post_caches( $post_ids, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
				} else {
					$this->posts = array();
				}
			} else {
				$this->posts = $wpdb->get_results( $this->request );
				$this->set_found_posts( $q, $limits );
			}
		}

		// Convert to WP_Post objects.
		if ( $this->posts ) {
			/** @var WP_Post[] */
			$this->posts = array_map( 'get_post', $this->posts );
		}

		$unfiltered_posts = $this->posts;

		if ( $q['cache_results'] && $id_query_is_cacheable && ! $cache_found ) {
			$post_ids = wp_list_pluck( $this->posts, 'ID' );

			$cache_value = array(
				'posts'         => $post_ids,
				'found_posts'   => $this->found_posts,
				'max_num_pages' => $this->max_num_pages,
			);

			wp_cache_set( $cache_key, $cache_value, 'post-queries' );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the raw post results array, prior to status checks.
			 *
			 * @since 2.3.0
			 *
			 * @param WP_Post[] $posts Array of post objects.
			 * @param WP_Query  $query The WP_Query instance (passed by reference).
			 */
			$this->posts = apply_filters_ref_array( 'posts_results', array( $this->posts, &$this ) );
		}

		if ( ! empty( $this->posts ) && $this->is_comment_feed && $this->is_singular ) {
			/** This filter is documented in wp-includes/query.php */
			$cjoin = apply_filters_ref_array( 'comment_feed_join', array( '', &$this ) );

			/** This filter is documented in wp-includes/query.php */
			$cwhere = apply_filters_ref_array( 'comment_feed_where', array( "WHERE comment_post_ID = '{$this->posts[0]->ID}' AND comment_approved = '1'", &$this ) );

			/** This filter is documented in wp-includes/query.php */
			$cgroupby = apply_filters_ref_array( 'comment_feed_groupby', array( '', &$this ) );
			$cgroupby = ( ! empty( $cgroupby ) ) ? 'GROUP BY ' . $cgroupby : '';

			/** This filter is documented in wp-includes/query.php */
			$corderby = apply_filters_ref_array( 'comment_feed_orderby', array( 'comment_date_gmt DESC', &$this ) );
			$corderby = ( ! empty( $corderby ) ) ? 'ORDER BY ' . $corderby : '';

			/** This filter is documented in wp-includes/query.php */
			$climits = apply_filters_ref_array( 'comment_feed_limits', array( 'LIMIT ' . get_option( 'posts_per_rss' ), &$this ) );

			$comments_request = "SELECT {$wpdb->comments}.comment_ID FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits";

			$comment_key          = md5( $comments_request );
			$comment_last_changed = wp_cache_get_last_changed( 'comment' );

			$comment_cache_key = "comment_feed:$comment_key:$comment_last_changed";
			$comment_ids       = wp_cache_get( $comment_cache_key, 'comment-queries' );
			if ( false === $comment_ids ) {
				$comment_ids = $wpdb->get_col( $comments_request );
				wp_cache_add( $comment_cache_key, $comment_ids, 'comment-queries' );
			}
			_prime_comment_caches( $comment_ids );

			// Convert to WP_Comment.
			/** @var WP_Comment[] */
			$this->comments      = array_map( 'get_comment', $comment_ids );
			$this->comment_count = count( $this->comments );
		}

		// Check post status to determine if post should be displayed.
		if ( ! empty( $this->posts ) && ( $this->is_single || $this->is_page ) ) {
			$status = get_post_status( $this->posts[0] );

			if ( 'attachment' === $this->posts[0]->post_type && 0 === (int) $this->posts[0]->post_parent ) {
				$this->is_page       = false;
				$this->is_single     = true;
				$this->is_attachment = true;
			}

			// If the post_status was specifically requested, let it pass through.
			if ( ! in_array( $status, $q_status, true ) ) {
				$post_status_obj = get_post_status_object( $status );

				if ( $post_status_obj && ! $post_status_obj->public ) {
					if ( ! is_user_logged_in() ) {
						// User must be logged in to view unpublished posts.
						$this->posts = array();
					} else {
						if ( $post_status_obj->protected ) {
							// User must have edit permissions on the draft to preview.
							if ( ! current_user_can( $edit_cap, $this->posts[0]->ID ) ) {
								$this->posts = array();
							} else {
								$this->is_preview = true;
								if ( 'future' !== $status ) {
									$this->posts[0]->post_date = current_time( 'mysql' );
								}
							}
						} elseif ( $post_status_obj->private ) {
							if ( ! current_user_can( $read_cap, $this->posts[0]->ID ) ) {
								$this->posts = array();
							}
						} else {
							$this->posts = array();
						}
					}
				} elseif ( ! $post_status_obj ) {
					// Post status is not registered, assume it's not public.
					if ( ! current_user_can( $edit_cap, $this->posts[0]->ID ) ) {
						$this->posts = array();
					}
				}
			}

			if ( $this->is_preview && $this->posts && current_user_can( $edit_cap, $this->posts[0]->ID ) ) {
				/**
				 * Filters the single post for preview mode.
				 *
				 * @since 2.7.0
				 *
				 * @param WP_Post  $post_preview  The Post object.
				 * @param WP_Query $query         The WP_Query instance (passed by reference).
				 */
				$this->posts[0] = get_post( apply_filters_ref_array( 'the_preview', array( $this->posts[0], &$this ) ) );
			}
		}

		// Put sticky posts at the top of the posts array.
		$sticky_posts = get_option( 'sticky_posts' );
		if ( $this->is_home && $page <= 1 && is_array( $sticky_posts ) && ! empty( $sticky_posts ) && ! $q['ignore_sticky_posts'] ) {
			$num_posts     = count( $this->posts );
			$sticky_offset = 0;
			// Loop over posts and relocate stickies to the front.
			for ( $i = 0; $i < $num_posts; $i++ ) {
				if ( in_array( $this->posts[ $i ]->ID, $sticky_posts, true ) ) {
					$sticky_post = $this->posts[ $i ];
					// Remove sticky from current position.
					array_splice( $this->posts, $i, 1 );
					// Move to front, after other stickies.
					array_splice( $this->posts, $sticky_offset, 0, array( $sticky_post ) );
					// Increment the sticky offset. The next sticky will be placed at this offset.
					++$sticky_offset;
					// Remove post from sticky posts array.
					$offset = array_search( $sticky_post->ID, $sticky_posts, true );
					unset( $sticky_posts[ $offset ] );
				}
			}

			// If any posts have been excluded specifically, Ignore those that are sticky.
			if ( ! empty( $sticky_posts ) && ! empty( $q['post__not_in'] ) ) {
				$sticky_posts = array_diff( $sticky_posts, $q['post__not_in'] );
			}

			// Fetch sticky posts that weren't in the query results.
			if ( ! empty( $sticky_posts ) ) {
				$stickies = get_posts(
					array(
						'post__in'               => $sticky_posts,
						'post_type'              => $post_type,
						'post_status'            => 'publish',
						'posts_per_page'         => count( $sticky_posts ),
						'suppress_filters'       => $q['suppress_filters'],
						'cache_results'          => $q['cache_results'],
						'update_post_meta_cache' => $q['update_post_meta_cache'],
						'update_post_term_cache' => $q['update_post_term_cache'],
						'lazy_load_term_meta'    => $q['lazy_load_term_meta'],
					)
				);

				foreach ( $stickies as $sticky_post ) {
					array_splice( $this->posts, $sticky_offset, 0, array( $sticky_post ) );
					++$sticky_offset;
				}
			}
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the array of retrieved posts after they've been fetched and
			 * internally processed.
			 *
			 * @since 1.5.0
			 *
			 * @param WP_Post[] $posts Array of post objects.
			 * @param WP_Query  $query The WP_Query instance (passed by reference).
			 */
			$this->posts = apply_filters_ref_array( 'the_posts', array( $this->posts, &$this ) );
		}

		/*
		 * Ensure that any posts added/modified via one of the filters above are
		 * of the type WP_Post and are filtered.
		 */
		if ( $this->posts ) {
			$this->post_count = count( $this->posts );

			/** @var WP_Post[] */
			$this->posts = array_map( 'get_post', $this->posts );

			if ( $q['cache_results'] ) {
				if ( $is_unfiltered_query && $unfiltered_posts === $this->posts ) {
					update_post_caches( $this->posts, $post_type, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
				} else {
					$post_ids = wp_list_pluck( $this->posts, 'ID' );
					_prime_post_caches( $post_ids, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
				}
			}

			/** @var WP_Post */
			$this->post = reset( $this->posts );
		} else {
			$this->post_count = 0;
			$this->posts      = array();
		}

		if ( ! empty( $this->posts ) && $q['update_menu_item_cache'] ) {
			update_menu_item_cache( $this->posts );
		}

		if ( $q['lazy_load_term_meta'] ) {
			wp_queue_posts_for_term_meta_lazyload( $this->posts );
		}

		return $this->posts;
	}

	/**
	 * Sets up the amount of found posts and the number of pages (if limit clause was used)
	 * for the current query.
	 *
	 * @since 3.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array  $q      Query variables.
	 * @param string $limits LIMIT clauses of the query.
	 */
	private function set_found_posts( $q, $limits ) {
		global $wpdb;

		/*
		 * Bail if posts is an empty array. Continue if posts is an empty string,
		 * null, or false to accommodate caching plugins that fill posts later.
		 */
		if ( $q['no_found_rows'] || ( is_array( $this->posts ) && ! $this->posts ) ) {
			return;
		}

		if ( ! empty( $limits ) ) {
			/**
			 * Filters the query to run for retrieving the found posts.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $found_posts_query The query to run to find the found posts.
			 * @param WP_Query $query             The WP_Query instance (passed by reference).
			 */
			$found_posts_query = apply_filters_ref_array( 'found_posts_query', array( 'SELECT FOUND_ROWS()', &$this ) );

			$this->found_posts = (int) $wpdb->get_var( $found_posts_query );
		} else {
			if ( is_array( $this->posts ) ) {
				$this->found_posts = count( $this->posts );
			} else {
				if ( null === $this->posts ) {
					$this->found_posts = 0;
				} else {
					$this->found_posts = 1;
				}
			}
		}

		/**
		 * Filters the number of found posts for the query.
		 *
		 * @since 2.1.0
		 *
		 * @param int      $found_posts The number of posts found.
		 * @param WP_Query $query       The WP_Query instance (passed by reference).
		 */
		$this->found_posts = (int) apply_filters_ref_array( 'found_posts', array( $this->found_posts, &$this ) );

		if ( ! empty( $limits ) ) {
			$this->max_num_pages = (int) ceil( $this->found_posts / $q['posts_per_page'] );
		}
	}

	/**
	 * Sets up the next post and iterate current post index.
	 *
	 * @since 1.5.0
	 *
	 * @return WP_Post Next post.
	 */
	public function next_post() {

		++$this->current_post;

		/** @var WP_Post */
		$this->post = $this->posts[ $this->current_post ];
		return $this->post;
	}

	/**
	 * Sets up the current post.
	 *
	 * Retrieves the next post, sets up the post, sets the 'in the loop'
	 * property to true.
	 *
	 * @since 1.5.0
	 *
	 * @global WP_Post $post Global post object.
	 */
	public function the_post() {
		global $post;

		if ( ! $this->in_the_loop ) {
			if ( 'all' === $this->query_vars['fields'] ) {
				// Full post objects queried.
				$post_objects = $this->posts;
			} else {
				if ( 'ids' === $this->query_vars['fields'] ) {
					// Post IDs queried.
					$post_ids = $this->posts;
				} else {
					// Only partial objects queried, need to prime the cache for the loop.
					$post_ids = array_reduce(
						$this->posts,
						function ( $carry, $post ) {
							if ( isset( $post->ID ) ) {
								$carry[] = $post->ID;
							}

							return $carry;
						},
						array()
					);
				}
				_prime_post_caches( $post_ids, $this->query_vars['update_post_term_cache'], $this->query_vars['update_post_meta_cache'] );
				$post_objects = array_map( 'get_post', $post_ids );
			}
			update_post_author_caches( $post_objects );
		}

		$this->in_the_loop = true;
		$this->before_loop = false;

		if ( -1 === $this->current_post ) { // Loop has just started.
			/**
			 * Fires once the loop is started.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			do_action_ref_array( 'loop_start', array( &$this ) );
		}

		$post = $this->next_post();

		// Ensure a full post object is available.
		if ( 'all' !== $this->query_vars['fields'] ) {
			if ( 'ids' === $this->query_vars['fields'] ) {
				// Post IDs queried.
				$post = get_post( $post );
			} elseif ( isset( $post->ID ) ) {
				/*
				 * Partial objecct queried.
				 *
				 * The post object was queried with a partial set of
				 * fields, populate the entire object for the loop.
				 */
				$post = get_post( $post->ID );
			}
		}

		// Set up the global post object for the loop.
		$this->setup_postdata( $post );
	}

	/**
	 * Determines whether there are more posts available in the loop.
	 *
	 * Calls the {@see 'loop_end'} action when the loop is complete.
	 *
	 * @since 1.5.0
	 *
	 * @return bool True if posts are available, false if end of the loop.
	 */
	public function have_posts() {
		if ( $this->current_post + 1 < $this->post_count ) {
			return true;
		} elseif ( $this->current_post + 1 === $this->post_count && $this->post_count > 0 ) {
			/**
			 * Fires once the loop has ended.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			do_action_ref_array( 'loop_end', array( &$this ) );

			// Do some cleaning up after the loop.
			$this->rewind_posts();
		} elseif ( 0 === $this->post_count ) {
			$this->before_loop = false;

			/**
			 * Fires if no results are found in a post query.
			 *
			 * @since 4.9.0
			 *
			 * @param WP_Query $query The WP_Query instance.
			 */
			do_action( 'loop_no_results', $this );
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Rewinds the posts and resets post index.
	 *
	 * @since 1.5.0
	 */
	public function rewind_posts() {
		$this->current_post = -1;
		if ( $this->post_count > 0 ) {
			$this->post = $this->posts[0];
		}
	}

	/**
	 * Iterates current comment index and returns WP_Comment object.
	 *
	 * @since 2.2.0
	 *
	 * @return WP_Comment Comment object.
	 */
	public function next_comment() {
		++$this->current_comment;

		/** @var WP_Comment */
		$this->comment = $this->comments[ $this->current_comment ];
		return $this->comment;
	}

	/**
	 * Sets up the current comment.
	 *
	 * @since 2.2.0
	 *
	 * @global WP_Comment $comment Global comment object.
	 */
	public function the_comment() {
		global $comment;

		$comment = $this->next_comment();

		if ( 0 === $this->current_comment ) {
			/**
			 * Fires once the comment loop is started.
			 *
			 * @since 2.2.0
			 */
			do_action( 'comment_loop_start' );
		}
	}

	/**
	 * Determines whether there are more comments available.
	 *
	 * Automatically rewinds comments when finished.
	 *
	 * @since 2.2.0
	 *
	 * @return bool True if comments are available, false if no more comments.
	 */
	public function have_comments() {
		if ( $this->current_comment + 1 < $this->comment_count ) {
			return true;
		} elseif ( $this->current_comment + 1 === $this->comment_count ) {
			$this->rewind_comments();
		}

		return false;
	}

	/**
	 * Rewinds the comments, resets the comment index and comment to first.
	 *
	 * @since 2.2.0
	 */
	public function rewind_comments() {
		$this->current_comment = -1;
		if ( $this->comment_count > 0 ) {
			$this->comment = $this->comments[0];
		}
	}

	/**
	 * Sets up the WordPress query by parsing query string.
	 *
	 * @since 1.5.0
	 *
	 * @see WP_Query::parse_query() for all available arguments.
	 *
	 * @param string|array $query URL query string or array of query arguments.
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	public function query( $query ) {
		$this->init();
		$this->query      = wp_parse_args( $query );
		$this->query_vars = $this->query;
		return $this->get_posts();
	}

	/**
	 * Retrieves the currently queried object.
	 *
	 * If queried object is not set, then the queried object will be set from
	 * the category, tag, taxonomy, posts page, single post, page, or author
	 * query variable. After it is set up, it will be returned.
	 *
	 * @since 1.5.0
	 *
	 * @return WP_Term|WP_Post_Type|WP_Post|WP_User|null The queried object.
	 */
	public function get_queried_object() {
		if ( isset( $this->queried_object ) ) {
			return $this->queried_object;
		}

		$this->queried_object    = null;
		$this->queried_object_id = null;

		if ( $this->is_category || $this->is_tag || $this->is_tax ) {
			if ( $this->is_category ) {
				$cat           = $this->get( 'cat' );
				$category_name = $this->get( 'category_name' );

				if ( $cat ) {
					$term = get_term( $cat, 'category' );
				} elseif ( $category_name ) {
					$term = get_term_by( 'slug', $category_name, 'category' );
				}
			} elseif ( $this->is_tag ) {
				$tag_id = $this->get( 'tag_id' );
				$tag    = $this->get( 'tag' );

				if ( $tag_id ) {
					$term = get_term( $tag_id, 'post_tag' );
				} elseif ( $tag ) {
					$term = get_term_by( 'slug', $tag, 'post_tag' );
				}
			} else {
				// For other tax queries, grab the first term from the first clause.
				if ( ! empty( $this->tax_query->queried_terms ) ) {
					$queried_taxonomies = array_keys( $this->tax_query->queried_terms );
					$matched_taxonomy   = reset( $queried_taxonomies );
					$query              = $this->tax_query->queried_terms[ $matched_taxonomy ];

					if ( ! empty( $query['terms'] ) ) {
						if ( 'term_id' === $query['field'] ) {
							$term = get_term( reset( $query['terms'] ), $matched_taxonomy );
						} else {
							$term = get_term_by( $query['field'], reset( $query['terms'] ), $matched_taxonomy );
						}
					}
				}
			}

			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				$this->queried_object    = $term;
				$this->queried_object_id = (int) $term->term_id;

				if ( $this->is_category && 'category' === $this->queried_object->taxonomy ) {
					_make_cat_compat( $this->queried_object );
				}
			}
		} elseif ( $this->is_post_type_archive ) {
			$post_type = $this->get( 'post_type' );

			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}

			$this->queried_object = get_post_type_object( $post_type );
		} elseif ( $this->is_posts_page ) {
			$page_for_posts = get_option( 'page_for_posts' );

			$this->queried_object    = get_post( $page_for_posts );
			$this->queried_object_id = (int) $this->queried_object->ID;
		} elseif ( $this->is_singular && ! empty( $this->post ) ) {
			$this->queried_object    = $this->post;
			$this->queried_object_id = (int) $this->post->ID;
		} elseif ( $this->is_author ) {
			$author      = (int) $this->get( 'author' );
			$author_name = $this->get( 'author_name' );

			if ( $author ) {
				$this->queried_object_id = $author;
			} elseif ( $author_name ) {
				$user = get_user_by( 'slug', $author_name );

				if ( $user ) {
					$this->queried_object_id = $user->ID;
				}
			}

			$this->queried_object = get_userdata( $this->queried_object_id );
		}

		return $this->queried_object;
	}

	/**
	 * Retrieves the ID of the currently queried object.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_queried_object_id() {
		$this->get_queried_object();

		if ( isset( $this->queried_object_id ) ) {
			return $this->queried_object_id;
		}

		return 0;
	}

	/**
	 * Constructor.
	 *
	 * Sets up the WordPress query, if parameter is not empty.
	 *
	 * @since 1.5.0
	 *
	 * @see WP_Query::parse_query() for all available arguments.
	 *
	 * @param string|array $query URL query string or array of vars.
	 */
	public function __construct( $query = '' ) {
		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Makes private properties readable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return $this->$name;
		}
	}

	/**
	 * Makes private properties checkable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return isset( $this->$name );
		}

		return false;
	}

	/**
	 * Makes private/protected methods readable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name      Method to call.
	 * @param array  $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->compat_methods, true ) ) {
			return $this->$name( ...$arguments );
		}
		return false;
	}

	/**
	 * Determines whether the query is for an existing archive page.
	 *
	 * Archive pages include category, tag, author, date, custom post type,
	 * and custom taxonomy based archives.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Query::is_category()
	 * @see WP_Query::is_tag()
	 * @see WP_Query::is_author()
	 * @see WP_Query::is_date()
	 * @see WP_Query::is_post_type_archive()
	 * @see WP_Query::is_tax()
	 *
	 * @return bool Whether the query is for an existing archive page.
	 */
	public function is_archive() {
		return (bool) $this->is_archive;
	}

	/**
	 * Determines whether the query is for an existing post type archive page.
	 *
	 * @since 3.1.0
	 *
	 * @param string|string[] $post_types Optional. Post type or array of posts types
	 *                                    to check against. Default empty.
	 * @return bool Whether the query is for an existing post type archive page.
	 */
	public function is_post_type_archive( $post_types = '' ) {
		if ( empty( $post_types ) || ! $this->is_post_type_archive ) {
			return (bool) $this->is_post_type_archive;
		}

		$post_type = $this->get( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object ) {
			return false;
		}

		return in_array( $post_type_object->name, (array) $post_types, true );
	}

	/**
	 * Determines whether the query is for an existing attachment page.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|int[]|string[] $attachment Optional. Attachment ID, title, slug, or array of such
	 *                                              to check against. Default empty.
	 * @return bool Whether the query is for an existing attachment page.
	 */
	public function is_attachment( $attachment = '' ) {
		if ( ! $this->is_attachment ) {
			return false;
		}

		if ( empty( $attachment ) ) {
			return true;
		}

		$attachment = array_map( 'strval', (array) $attachment );

		$post_obj = $this->get_queried_object();
		if ( ! $post_obj ) {
			return false;
		}

		if ( in_array( (string) $post_obj->ID, $attachment, true ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_title, $attachment, true ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_name, $attachment, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Determines whether the query is for an existing author archive page.
	 *
	 * If the $author parameter is specified, this function will additionally
	 * check if the query is for one of the authors specified.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|int[]|string[] $author Optional. User ID, nickname, nicename, or array of such
	 *                                          to check against. Default empty.
	 * @return bool Whether the query is for an existing author archive page.
	 */
	public function is_author( $author = '' ) {
		if ( ! $this->is_author ) {
			return false;
		}

		if ( empty( $author ) ) {
			return true;
		}

		$author_obj = $this->get_queried_object();
		if ( ! $author_obj ) {
			return false;
		}

		$author = array_map( 'strval', (array) $author );

		if ( in_array( (string) $author_obj->ID, $author, true ) ) {
			return true;
		} elseif ( in_array( $author_obj->nickname, $author, true ) ) {
			return true;
		} elseif ( in_array( $author_obj->user_nicename, $author, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether the query is for an existing category archive page.
	 *
	 * If the $category parameter is specified, this function will additionally
	 * check if the query is for one of the categories specified.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|int[]|string[] $category Optional. Category ID, name, slug, or array of such
	 *                                            to check against. Default empty.
	 * @return bool Whether the query is for an existing category archive page.
	 */
	public function is_category( $category = '' ) {
		if ( ! $this->is_category ) {
			return false;
		}

		if ( empty( $category ) ) {
			return true;
		}

		$cat_obj = $this->get_queried_object();
		if ( ! $cat_obj ) {
			return false;
		}

		$category = array_map( 'strval', (array) $category );

		if ( in_array( (string) $cat_obj->term_id, $category, true ) ) {
			return true;
		} elseif ( in_array( $cat_obj->name, $category, true ) ) {
			return true;
		} elseif ( in_array( $cat_obj->slug, $category, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether the query is for an existing tag archive page.
	 *
	 * If the $tag parameter is specified, this function will additionally
	 * check if the query is for one of the tags specified.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|int[]|string[] $tag Optional. Tag ID, name, slug, or array of such
	 *                                       to check against. Default empty.
	 * @return bool Whether the query is for an existing tag archive page.
	 */
	public function is_tag( $tag = '' ) {
		if ( ! $this->is_tag ) {
			return false;
		}

		if ( empty( $tag ) ) {
			return true;
		}

		$tag_obj = $this->get_queried_object();
		if ( ! $tag_obj ) {
			return false;
		}

		$tag = array_map( 'strval', (array) $tag );

		if ( in_array( (string) $tag_obj->term_id, $tag, true ) ) {
			return true;
		} elseif ( in_array( $tag_obj->name, $tag, true ) ) {
			return true;
		} elseif ( in_array( $tag_obj->slug, $tag, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether the query is for an existing custom taxonomy archive page.
	 *
	 * If the $taxonomy parameter is specified, this function will additionally
	 * check if the query is for that specific $taxonomy.
	 *
	 * If the $term parameter is specified in addition to the $taxonomy parameter,
	 * this function will additionally check if the query is for one of the terms
	 * specified.
	 *
	 * @since 3.1.0
	 *
	 * @global WP_Taxonomy[] $wp_taxonomies Registered taxonomies.
	 *
	 * @param string|string[]           $taxonomy Optional. Taxonomy slug or slugs to check against.
	 *                                            Default empty.
	 * @param int|string|int[]|string[] $term     Optional. Term ID, name, slug, or array of such
	 *                                            to check against. Default empty.
	 * @return bool Whether the query is for an existing custom taxonomy archive page.
	 *              True for custom taxonomy archive pages, false for built-in taxonomies
	 *              (category and tag archives).
	 */
	public function is_tax( $taxonomy = '', $term = '' ) {
		global $wp_taxonomies;

		if ( ! $this->is_tax ) {
			return false;
		}

		if ( empty( $taxonomy ) ) {
			return true;
		}

		$queried_object = $this->get_queried_object();
		$tax_array      = array_intersect( array_keys( $wp_taxonomies ), (array) $taxonomy );
		$term_array     = (array) $term;

		// Check that the taxonomy matches.
		if ( ! ( isset( $queried_object->taxonomy ) && count( $tax_array ) && in_array( $queried_object->taxonomy, $tax_array, true ) ) ) {
			return false;
		}

		// Only a taxonomy provided.
		if ( empty( $term ) ) {
			return true;
		}

		return isset( $queried_object->term_id ) &&
			count(
				array_intersect(
					array( $queried_object->term_id, $queried_object->name, $queried_object->slug ),
					$term_array
				)
			);
	}

	/**
	 * Determines whether the current URL is within the comments popup window.
	 *
	 * @since 3.1.0
	 * @deprecated 4.5.0
	 *
	 * @return false Always returns false.
	 */
	public function is_comments_popup() {
		_deprecated_function( __FUNCTION__, '4.5.0' );

		return false;
	}

	/**
	 * Determines whether the query is for an existing date archive.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for an existing date archive.
	 */
	public function is_date() {
		return (bool) $this->is_date;
	}

	/**
	 * Determines whether the query is for an existing day archive.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for an existing day archive.
	 */
	public function is_day() {
		return (bool) $this->is_day;
	}

	/**
	 * Determines whether the query is for a feed.
	 *
	 * @since 3.1.0
	 *
	 * @param string|string[] $feeds Optional. Feed type or array of feed types
	 *                                         to check against. Default empty.
	 * @return bool Whether the query is for a feed.
	 */
	public function is_feed( $feeds = '' ) {
		if ( empty( $feeds ) || ! $this->is_feed ) {
			return (bool) $this->is_feed;
		}

		$qv = $this->get( 'feed' );
		if ( 'feed' === $qv ) {
			$qv = get_default_feed();
		}

		return in_array( $qv, (array) $feeds, true );
	}

	/**
	 * Determines whether the query is for a comments feed.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for a comments feed.
	 */
	public function is_comment_feed() {
		return (bool) $this->is_comment_feed;
	}

	/**
	 * Determines whether the query is for the front page of the site.
	 *
	 * This is for what is displayed at your site's main URL.
	 *
	 * Depends on the site's "Front page displays" Reading Settings 'show_on_front' and 'page_on_front'.
	 *
	 * If you set a static page for the front page of your site, this function will return
	 * true when viewing that page.
	 *
	 * Otherwise the same as {@see WP_Query::is_home()}.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for the front page of the site.
	 */
	public function is_front_page() {
		// Most likely case.
		if ( 'posts' === get_option( 'show_on_front' ) && $this->is_home() ) {
			return true;
		} elseif ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' )
			&& $this->is_page( get_option( 'page_on_front' ) )
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Determines whether the query is for the blog homepage.
	 *
	 * This is the page which shows the time based blog content of your site.
	 *
	 * Depends on the site's "Front page displays" Reading Settings 'show_on_front' and 'page_for_posts'.
	 *
	 * If you set a static page for the front page of your site, this function will return
	 * true only on the page you set as the "Posts page".
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Query::is_front_page()
	 *
	 * @return bool Whether the query is for the blog homepage.
	 */
	public function is_home() {
		return (bool) $this->is_home;
	}

	/**
	 * Determines whether the query is for the Privacy Policy page.
	 *
	 * This is the page which shows the Privacy Policy content of your site.
	 *
	 * Depends on the site's "Change your Privacy Policy page" Privacy Settings 'wp_page_for_privacy_policy'.
	 *
	 * This function will return true only on the page you set as the "Privacy Policy page".
	 *
	 * @since 5.2.0
	 *
	 * @return bool Whether the query is for the Privacy Policy page.
	 */
	public function is_privacy_policy() {
		if ( get_option( 'wp_page_for_privacy_policy' )
			&& $this->is_page( get_option( 'wp_page_for_privacy_policy' ) )
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Determines whether the query is for an existing month archive.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for an existing month archive.
	 */
	public function is_month() {
		return (bool) $this->is_month;
	}

	/**
	 * Determines whether the query is for an existing single page.
	 *
	 * If the $page parameter is specified, this function will additionally
	 * check if the query is for one of the pages specified.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Query::is_single()
	 * @see WP_Query::is_singular()
	 *
	 * @param int|string|int[]|string[] $page Optional. Page ID, title, slug, path, or array of such
	 *                                        to check against. Default empty.
	 * @return bool Whether the query is for an existing single page.
	 */
	public function is_page( $page = '' ) {
		if ( ! $this->is_page ) {
			return false;
		}

		if ( empty( $page ) ) {
			return true;
		}

		$page_obj = $this->get_queried_object();
		if ( ! $page_obj ) {
			return false;
		}

		$page = array_map( 'strval', (array) $page );

		if ( in_array( (string) $page_obj->ID, $page, true ) ) {
			return true;
		} elseif ( in_array( $page_obj->post_title, $page, true ) ) {
			return true;
		} elseif ( in_array( $page_obj->post_name, $page, true ) ) {
			return true;
		} else {
			foreach ( $page as $pagepath ) {
				if ( ! strpos( $pagepath, '/' ) ) {
					continue;
				}

				$pagepath_obj = get_page_by_path( $pagepath );

				if ( $pagepath_obj && ( $pagepath_obj->ID === $page_obj->ID ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determines whether the query is for a paged result and not for the first page.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for a paged result.
	 */
	public function is_paged() {
		return (bool) $this->is_paged;
	}

	/**
	 * Determines whether the query is for a post or page preview.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for a post or page preview.
	 */
	public function is_preview() {
		return (bool) $this->is_preview;
	}

	/**
	 * Determines whether the query is for the robots.txt file.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for the robots.txt file.
	 */
	public function is_robots() {
		return (bool) $this->is_robots;
	}

	/**
	 * Determines whether the query is for the favicon.ico file.
	 *
	 * @since 5.4.0
	 *
	 * @return bool Whether the query is for the favicon.ico file.
	 */
	public function is_favicon() {
		return (bool) $this->is_favicon;
	}

	/**
	 * Determines whether the query is for a search.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for a search.
	 */
	public function is_search() {
		return (bool) $this->is_search;
	}

	/**
	 * Determines whether the query is for an existing single post.
	 *
	 * Works for any post type excluding pages.
	 *
	 * If the $post parameter is specified, this function will additionally
	 * check if the query is for one of the Posts specified.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Query::is_page()
	 * @see WP_Query::is_singular()
	 *
	 * @param int|string|int[]|string[] $post Optional. Post ID, title, slug, path, or array of such
	 *                                        to check against. Default empty.
	 * @return bool Whether the query is for an existing single post.
	 */
	public function is_single( $post = '' ) {
		if ( ! $this->is_single ) {
			return false;
		}

		if ( empty( $post ) ) {
			return true;
		}

		$post_obj = $this->get_queried_object();
		if ( ! $post_obj ) {
			return false;
		}

		$post = array_map( 'strval', (array) $post );

		if ( in_array( (string) $post_obj->ID, $post, true ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_title, $post, true ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_name, $post, true ) ) {
			return true;
		} else {
			foreach ( $post as $postpath ) {
				if ( ! strpos( $postpath, '/' ) ) {
					continue;
				}

				$postpath_obj = get_page_by_path( $postpath, OBJECT, $post_obj->post_type );

				if ( $postpath_obj && ( $postpath_obj->ID === $post_obj->ID ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Determines whether the query is for an existing single post of any post type
	 * (post, attachment, page, custom post types).
	 *
	 * If the $post_types parameter is specified, this function will additionally
	 * check if the query is for one of the Posts Types specified.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Query::is_page()
	 * @see WP_Query::is_single()
	 *
	 * @param string|string[] $post_types Optional. Post type or array of post types
	 *                                    to check against. Default empty.
	 * @return bool Whether the query is for an existing single post
	 *              or any of the given post types.
	 */
	public function is_singular( $post_types = '' ) {
		if ( empty( $post_types ) || ! $this->is_singular ) {
			return (bool) $this->is_singular;
		}

		$post_obj = $this->get_queried_object();
		if ( ! $post_obj ) {
			return false;
		}

		return in_array( $post_obj->post_type, (array) $post_types, true );
	}

	/**
	 * Determines whether the query is for a specific time.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for a specific time.
	 */
	public function is_time() {
		return (bool) $this->is_time;
	}

	/**
	 * Determines whether the query is for a trackback endpoint call.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for a trackback endpoint call.
	 */
	public function is_trackback() {
		return (bool) $this->is_trackback;
	}

	/**
	 * Determines whether the query is for an existing year archive.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is for an existing year archive.
	 */
	public function is_year() {
		return (bool) $this->is_year;
	}

	/**
	 * Determines whether the query is a 404 (returns no results).
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether the query is a 404 error.
	 */
	public function is_404() {
		return (bool) $this->is_404;
	}

	/**
	 * Determines whether the query is for an embedded post.
	 *
	 * @since 4.4.0
	 *
	 * @return bool Whether the query is for an embedded post.
	 */
	public function is_embed() {
		return (bool) $this->is_embed;
	}

	/**
	 * Determines whether the query is the main query.
	 *
	 * @since 3.3.0
	 *
	 * @global WP_Query $wp_the_query WordPress Query object.
	 *
	 * @return bool Whether the query is the main query.
	 */
	public function is_main_query() {
		global $wp_the_query;
		return $wp_the_query === $this;
	}

	/**
	 * Sets up global post data.
	 *
	 * @since 4.1.0
	 * @since 4.4.0 Added the ability to pass a post ID to `$post`.
	 *
	 * @global int     $id
	 * @global WP_User $authordata
	 * @global string  $currentday
	 * @global string  $currentmonth
	 * @global int     $page
	 * @global array   $pages
	 * @global int     $multipage
	 * @global int     $more
	 * @global int     $numpages
	 *
	 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
	 * @return true True when finished.
	 */
	public function setup_postdata( $post ) {
		global $id, $authordata, $currentday, $currentmonth, $page, $pages, $multipage, $more, $numpages;

		if ( ! ( $post instanceof WP_Post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return;
		}

		$elements = $this->generate_postdata( $post );
		if ( false === $elements ) {
			return;
		}

		$id           = $elements['id'];
		$authordata   = $elements['authordata'];
		$currentday   = $elements['currentday'];
		$currentmonth = $elements['currentmonth'];
		$page         = $elements['page'];
		$pages        = $elements['pages'];
		$multipage    = $elements['multipage'];
		$more         = $elements['more'];
		$numpages     = $elements['numpages'];

		/**
		 * Fires once the post data has been set up.
		 *
		 * @since 2.8.0
		 * @since 4.1.0 Introduced `$query` parameter.
		 *
		 * @param WP_Post  $post  The Post object (passed by reference).
		 * @param WP_Query $query The current Query object (passed by reference).
		 */
		do_action_ref_array( 'the_post', array( &$post, &$this ) );

		return true;
	}

	/**
	 * Generates post data.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
	 * @return array|false Elements of post or false on failure.
	 */
	public function generate_postdata( $post ) {

		if ( ! ( $post instanceof WP_Post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return false;
		}

		$id = (int) $post->ID;

		$authordata = get_userdata( $post->post_author );

		$currentday   = false;
		$currentmonth = false;

		$post_date = $post->post_date;
		if ( ! empty( $post_date ) && '0000-00-00 00:00:00' !== $post_date ) {
			// Avoid using mysql2date for performance reasons.
			$currentmonth = substr( $post_date, 5, 2 );
			$day          = substr( $post_date, 8, 2 );
			$year         = substr( $post_date, 2, 2 );

			$currentday = sprintf( '%s.%s.%s', $day, $currentmonth, $year );
		}

		$numpages  = 1;
		$multipage = 0;
		$page      = $this->get( 'page' );
		if ( ! $page ) {
			$page = 1;
		}

		/*
		 * Force full post content when viewing the permalink for the $post,
		 * or when on an RSS feed. Otherwise respect the 'more' tag.
		 */
		if ( get_queried_object_id() === $post->ID && ( $this->is_page() || $this->is_single() ) ) {
			$more = 1;
		} elseif ( $this->is_feed() ) {
			$more = 1;
		} else {
			$more = 0;
		}

		$content = $post->post_content;
		if ( str_contains( $content, '<!--nextpage-->' ) ) {
			$content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
			$content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
			$content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );

			// Remove the nextpage block delimiters, to avoid invalid block structures in the split content.
			$content = str_replace( '<!-- wp:nextpage -->', '', $content );
			$content = str_replace( '<!-- /wp:nextpage -->', '', $content );

			// Ignore nextpage at the beginning of the content.
			if ( str_starts_with( $content, '<!--nextpage-->' ) ) {
				$content = substr( $content, 15 );
			}

			$pages = explode( '<!--nextpage-->', $content );
		} else {
			$pages = array( $post->post_content );
		}

		/**
		 * Filters the "pages" derived from splitting the post content.
		 *
		 * "Pages" are determined by splitting the post content based on the presence
		 * of `<!-- nextpage -->` tags.
		 *
		 * @since 4.4.0
		 *
		 * @param string[] $pages Array of "pages" from the post content split by `<!-- nextpage -->` tags.
		 * @param WP_Post  $post  Current post object.
		 */
		$pages = apply_filters( 'content_pagination', $pages, $post );

		$numpages = count( $pages );

		if ( $numpages > 1 ) {
			if ( $page > 1 ) {
				$more = 1;
			}
			$multipage = 1;
		} else {
			$multipage = 0;
		}

		$elements = compact( 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages' );

		return $elements;
	}

	/**
	 * Generates cache key.
	 *
	 * @since 6.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array  $args Query arguments.
	 * @param string $sql  SQL statement.
	 * @return string Cache key.
	 */
	protected function generate_cache_key( array $args, $sql ) {
		global $wpdb;

		unset(
			$args['cache_results'],
			$args['fields'],
			$args['lazy_load_term_meta'],
			$args['update_post_meta_cache'],
			$args['update_post_term_cache'],
			$args['update_menu_item_cache'],
			$args['suppress_filters']
		);

		if ( empty( $args['post_type'] ) ) {
			if ( $this->is_attachment ) {
				$args['post_type'] = 'attachment';
			} elseif ( $this->is_page ) {
				$args['post_type'] = 'page';
			} else {
				$args['post_type'] = 'post';
			}
		} elseif ( 'any' === $args['post_type'] ) {
			$args['post_type'] = array_values( get_post_types( array( 'exclude_from_search' => false ) ) );
		}
		$args['post_type'] = (array) $args['post_type'];
		// Sort post types to ensure same cache key generation.
		sort( $args['post_type'] );

		/*
		 * Sort arrays that can be used for ordering prior to cache key generation.
		 *
		 * These arrays are sorted in the query generator for the purposes of the
		 * WHERE clause but the arguments are not modified as they can be used for
		 * the orderby clase.
		 *
		 * Their use in the orderby clause will generate a different SQL query so
		 * they can be sorted for the cache key generation.
		 */
		$sortable_arrays_with_int_values = array(
			'post__in',
			'post_parent__in',
		);
		foreach ( $sortable_arrays_with_int_values as $key ) {
			if ( isset( $args[ $key ] ) && is_array( $args[ $key ] ) ) {
				$args[ $key ] = array_unique( array_map( 'absint', $args[ $key ] ) );
				sort( $args[ $key ] );
			}
		}

		// Sort and unique the 'post_name__in' for cache key generation.
		if ( isset( $args['post_name__in'] ) && is_array( $args['post_name__in'] ) ) {
			$args['post_name__in'] = array_unique( $args['post_name__in'] );
			sort( $args['post_name__in'] );
		}

		if ( isset( $args['post_status'] ) ) {
			$args['post_status'] = (array) $args['post_status'];
			// Sort post status to ensure same cache key generation.
			sort( $args['post_status'] );
		}

		// Add a default orderby value of date to ensure same cache key generation.
		if ( ! isset( $q['orderby'] ) ) {
			$args['orderby'] = 'date';
		}

		$placeholder = $wpdb->placeholder_escape();
		array_walk_recursive(
			$args,
			/*
			 * Replace wpdb placeholders with the string used in the database
			 * query to avoid unreachable cache keys. This is necessary because
			 * the placeholder is randomly generated in each request.
			 *
			 * $value is passed by reference to allow it to be modified.
			 * array_walk_recursive() does not return an array.
			 */
			static function ( &$value ) use ( $wpdb, $placeholder ) {
				if ( is_string( $value ) && str_contains( $value, $placeholder ) ) {
					$value = $wpdb->remove_placeholder_escape( $value );
				}
			}
		);

		ksort( $args );

		// Replace wpdb placeholder in the SQL statement used by the cache key.
		$sql = $wpdb->remove_placeholder_escape( $sql );
		$key = md5( serialize( $args ) . $sql );

		$last_changed = wp_cache_get_last_changed( 'posts' );
		if ( ! empty( $this->tax_query->queries ) ) {
			$last_changed .= wp_cache_get_last_changed( 'terms' );
		}

		$this->query_cache_key = "wp_query:$key:$last_changed";
		return $this->query_cache_key;
	}

	/**
	 * After looping through a nested query, this function
	 * restores the $post global to the current post in this query.
	 *
	 * @since 3.7.0
	 *
	 * @global WP_Post $post Global post object.
	 */
	public function reset_postdata() {
		if ( ! empty( $this->post ) ) {
			$GLOBALS['post'] = $this->post;
			$this->setup_postdata( $this->post );
		}
	}

	/**
	 * Lazyloads term meta for posts in the loop.
	 *
	 * @since 4.4.0
	 * @deprecated 4.5.0 See wp_queue_posts_for_term_meta_lazyload().
	 *
	 * @param mixed $check
	 * @param int   $term_id
	 * @return mixed
	 */
	public function lazyload_term_meta( $check, $term_id ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		return $check;
	}

	/**
	 * Lazyloads comment meta for comments in the loop.
	 *
	 * @since 4.4.0
	 * @deprecated 4.5.0 See wp_lazyload_comment_meta().
	 *
	 * @param mixed $check
	 * @param int   $comment_id
	 * @return mixed
	 */
	public function lazyload_comment_meta( $check, $comment_id ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		return $check;
	}
}
