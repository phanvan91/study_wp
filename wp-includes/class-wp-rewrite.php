<?php
/**
 * API Rewrite: Lớp WP_Rewrite
 *
 * @package WordPress
 * @subpackage Rewrite
 * @since 1.5.0
 */

/**
 * Lớp cốt lõi được sử dụng để triển khai API rewrite.
 *
 * Lớp WordPress Rewrite ghi các quy tắc module rewrite vào tệp .htaccess.
 * Nó cũng xử lý phân tích yêu cầu để thiết lập đúng cho đối tượng WordPress Query.
 *
 * Rewrite, cùng với lớp WP, hoạt động như một bộ điều khiển phía trước cho WordPress.
 * Bạn có thể thêm các quy tắc để kích hoạt xem trang và xử lý bằng cách sử dụng
 * thành phần này. Chức năng đầy đủ của bộ điều khiển phía trước không tồn tại,
 * nghĩa là bạn không thể định nghĩa cách tải các tệp template dựa trên các
 * quy tắc rewrite.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class WP_Rewrite {
	/**
	 * Cấu trúc đường dẫn tĩnh cho bài viết.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $permalink_structure;

	/**
	 * Có thêm dấu gạch chéo cuối hay không.
	 *
	 * @since 2.2.0
	 * @var bool
	 */
	public $use_trailing_slashes;

	/**
	 * Cơ sở cho cấu trúc đường dẫn tĩnh tác giả (example.com/$author_base/authorname).
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $author_base = 'author';

	/**
	 * Cấu trúc đường dẫn tĩnh cho lưu trữ tác giả.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $author_structure;

	/**
	 * Cấu trúc đường dẫn tĩnh cho lưu trữ theo ngày.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $date_structure;

	/**
	 * Cấu trúc đường dẫn tĩnh cho trang.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $page_structure;

	/**
	 * Cơ sở của cấu trúc đường dẫn tĩnh tìm kiếm (example.com/$search_base/query).
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $search_base = 'search';

	/**
	 * Cấu trúc đường dẫn tĩnh cho tìm kiếm.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $search_structure;

	/**
	 * Cơ sở đường dẫn tĩnh bình luận.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $comments_base = 'comments';

	/**
	 * Cơ sở đường dẫn tĩnh phân trang.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $pagination_base = 'page';

	/**
	 * Cơ sở đường dẫn tĩnh phân trang bình luận.
	 *
	 * @since 4.2.0
	 * @var string
	 */
	public $comments_pagination_base = 'comment-page';

	/**
	 * Cơ sở đường dẫn tĩnh nguồn cấp dữ liệu.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $feed_base = 'feed';

	/**
	 * Cấu trúc đường dẫn tĩnh nguồn cấp bình luận.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $comment_feed_structure;

	/**
	 * Cấu trúc đường dẫn tĩnh yêu cầu nguồn cấp dữ liệu.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $feed_structure;

	/**
	 * Phần tĩnh của cấu trúc đường dẫn tĩnh bài viết.
	 *
	 * Nếu cấu trúc đường dẫn tĩnh là "/archive/%post_id%" thì front
	 * là "/archive/". Nếu cấu trúc đường dẫn tĩnh là "/%year%/%postname%/"
	 * thì front là "/".
	 *
	 * @since 1.5.0
	 * @var string
	 *
	 * @see WP_Rewrite::init()
	 */
	public $front;

	/**
	 * Tiền tố cho tất cả các cấu trúc đường dẫn tĩnh.
	 *
	 * Nếu đường dẫn tĩnh PATHINFO/index đang được sử dụng thì root là giá trị của
	 * `WP_Rewrite::$index` với dấu gạch chéo cuối được thêm vào. Ngược lại root
	 * sẽ trống.
	 *
	 * @since 1.5.0
	 * @var string
	 *
	 * @see WP_Rewrite::init()
	 * @see WP_Rewrite::using_index_permalinks()
	 */
	public $root = '';

	/**
	 * Tên của tệp index là điểm vào cho tất cả các yêu cầu.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $index = 'index.php';

	/**
	 * Tên biến sử dụng cho các kết quả khớp regex trong truy vấn đã viết lại.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $matches = '';

	/**
	 * Các quy tắc rewrite để khớp với yêu cầu nhằm tìm chuyển hướng hoặc truy vấn.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	public $rules;

	/**
	 * Các quy tắc bổ sung được thêm từ bên ngoài lớp rewrite.
	 *
	 * Những quy tắc không được tạo bởi lớp, xem add_rewrite_rule().
	 *
	 * @since 2.1.0
	 * @var string[]
	 */
	public $extra_rules = array();

	/**
	 * Các quy tắc bổ sung thuộc về đầu danh sách để khớp trước tiên.
	 *
	 * Những quy tắc không được tạo bởi lớp, xem add_rewrite_rule().
	 *
	 * @since 2.3.0
	 * @var string[]
	 */
	public $extra_rules_top = array();

	/**
	 * Các quy tắc không chuyển hướng đến index.php của WordPress.
	 *
	 * Các quy tắc này được ghi vào phần mod_rewrite của .htaccess,
	 * và được thêm bởi add_external_rule().
	 *
	 * @since 2.1.0
	 * @var string[]
	 */
	public $non_wp_rules = array();

	/**
	 * Các cấu trúc đường dẫn tĩnh bổ sung, ví dụ: chuyên mục, được thêm bởi add_permastruct().
	 *
	 * @since 2.1.0
	 * @var array[]
	 */
	public $extra_permastructs = array();

	/**
	 * Các điểm cuối (như /trackback/) được thêm bởi add_rewrite_endpoint().
	 *
	 * @since 2.1.0
	 * @var array[]
	 */
	public $endpoints;

	/**
	 * Có ghi tất cả các quy tắc mod_rewrite của WordPress vào tệp .htaccess hay không.
	 *
	 * Mặc định tắt, bật lên có thể in rất nhiều quy tắc rewrite
	 * vào tệp .htaccess.
	 *
	 * @since 2.0.0
	 * @var bool
	 *
	 * @see WP_Rewrite::mod_rewrite_rules()
	 */
	public $use_verbose_rules = false;

	/**
	 * Liệu đường dẫn tĩnh bài viết có thể bị nhầm lẫn với đường dẫn tĩnh của trang không?
	 *
	 * Nếu thẻ rewrite đầu tiên trong cấu trúc đường dẫn tĩnh bài viết là thẻ có thể
	 * khớp với tên trang (ví dụ: %postname% hoặc %author%) thì cờ này được
	 * đặt thành true. Trước WordPress 3.3, cờ này chỉ ra rằng mỗi trang
	 * sẽ có một bộ quy tắc được thêm vào đầu mảng quy tắc rewrite.
	 * Bây giờ nó báo cho WP::parse_request() kiểm tra xem URL khớp với
	 * permastruct trang có thực sự là một trang hay không trước khi chấp nhận.
	 *
	 * @since 2.5.0
	 * @var bool
	 *
	 * @see WP_Rewrite::init()
	 */
	public $use_verbose_page_rules = true;

	/**
	 * Các thẻ rewrite có thể sử dụng trong cấu trúc đường dẫn tĩnh.
	 *
	 * Các thẻ này được chuyển đổi thành biểu thức chính quy lưu trong
	 * `WP_Rewrite::$rewritereplace` và được viết lại thành các biến
	 * truy vấn được liệt kê trong WP_Rewrite::$queryreplace.
	 *
	 * Các thẻ bổ sung có thể được thêm bằng add_rewrite_tag().
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	public $rewritecode = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%postname%',
		'%post_id%',
		'%author%',
		'%pagename%',
		'%search%',
	);

	/**
	 * Các biểu thức chính quy được thay thế vào quy tắc rewrite thay cho
	 * các thẻ rewrite, xem WP_Rewrite::$rewritecode.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	public $rewritereplace = array(
		'([0-9]{4})',
		'([0-9]{1,2})',
		'([0-9]{1,2})',
		'([0-9]{1,2})',
		'([0-9]{1,2})',
		'([0-9]{1,2})',
		'([^/]+)',
		'([0-9]+)',
		'([^/]+)',
		'([^/]+?)',
		'(.+)',
	);

	/**
	 * Các biến truy vấn mà thẻ rewrite ánh xạ tới, xem WP_Rewrite::$rewritecode.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	public $queryreplace = array(
		'year=',
		'monthnum=',
		'day=',
		'hour=',
		'minute=',
		'second=',
		'name=',
		'p=',
		'author_name=',
		'pagename=',
		's=',
	);

	/**
	 * Các nguồn cấp dữ liệu mặc định được hỗ trợ.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	public $feeds = array( 'feed', 'rdf', 'rss', 'rss2', 'atom' );

	/**
	 * Xác định xem đường dẫn tĩnh có đang được sử dụng hay không.
	 *
	 * Có thể là module rewrite hoặc đường dẫn tĩnh trong chuỗi truy vấn HTTP.
	 *
	 * @since 1.5.0
	 *
	 * @return bool True nếu đường dẫn tĩnh được bật.
	 */
	public function using_permalinks() {
		return ! empty( $this->permalink_structure );
	}

	/**
	 * Xác định xem đường dẫn tĩnh có đang được sử dụng và module rewrite không được bật hay không.
	 *
	 * Có nghĩa là đường dẫn tĩnh được bật và index.php nằm trong URL.
	 *
	 * @since 1.5.0
	 *
	 * @return bool Liệu đường dẫn tĩnh có được bật và index.php có trong URL hay không.
	 */
	public function using_index_permalinks() {
		if ( empty( $this->permalink_structure ) ) {
			return false;
		}

		// Nếu index không nằm trong đường dẫn tĩnh, chúng ta đang sử dụng mod_rewrite.
		return preg_match( '#^/*' . $this->index . '#', $this->permalink_structure );
	}

	/**
	 * Xác định xem đường dẫn tĩnh có đang được sử dụng và module rewrite có được bật hay không.
	 *
	 * Sử dụng đường dẫn tĩnh và index.php không nằm trong URL.
	 *
	 * @since 1.5.0
	 *
	 * @return bool Liệu đường dẫn tĩnh có được bật và index.php KHÔNG có trong URL hay không.
	 */
	public function using_mod_rewrite_permalinks() {
		return $this->using_permalinks() && ! $this->using_index_permalinks();
	}

	/**
	 * Chỉ mục cho các kết quả khớp để sử dụng trong các hàm preg_*().
	 *
	 * Định dạng của chuỗi là, với giá trị thuộc tính matches rỗng, '$NUM'.
	 * 'NUM' sẽ được thay thế bằng giá trị trong tham số $number. Với
	 * thuộc tính matches không rỗng, giá trị của chuỗi trả về sẽ
	 * chứa giá trị đó của thuộc tính matches. Định dạng khi đó sẽ là
	 * '$MATCHES[NUM]', với MATCHES là giá trị trong thuộc tính và NUM là
	 * giá trị của tham số $number.
	 *
	 * @since 1.5.0
	 *
	 * @param int $number Số chỉ mục.
	 * @return string
	 */
	public function preg_index( $number ) {
		$match_prefix = '$';
		$match_suffix = '';

		if ( ! empty( $this->matches ) ) {
			$match_prefix = '$' . $this->matches . '[';
			$match_suffix = ']';
		}

		return "$match_prefix$number$match_suffix";
	}

	/**
	 * Lấy tất cả các trang và tệp đính kèm cho URI của trang.
	 *
	 * Các tệp đính kèm dành cho những trang có trang cha và sẽ được
	 * lấy ra.
	 *
	 * @since 2.5.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @return array Mảng URI trang là phần tử đầu tiên và URI tệp đính kèm là phần tử thứ hai.
	 */
	public function page_uri_index() {
		global $wpdb;

		// Lấy các trang theo thứ tự phân cấp, tức là con sau cha.
		$pages = $wpdb->get_results( "SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE post_type = 'page' AND post_status != 'auto-draft'" );
		$posts = get_page_hierarchy( $pages );

		// Nếu không có trang nào thì thoát nhanh.
		if ( ! $posts ) {
			return array( array(), array() );
		}

		// Bây giờ đảo ngược nó, vì chúng ta cần cha sau con để quy tắc rewrite hoạt động đúng.
		$posts = array_reverse( $posts, true );

		$page_uris            = array();
		$page_attachment_uris = array();

		foreach ( $posts as $id => $post ) {
			// URL => tên trang.
			$uri         = get_page_uri( $id );
			$attachments = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = %d", $id ) );
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment ) {
					$attach_uri                          = get_page_uri( $attachment->ID );
					$page_attachment_uris[ $attach_uri ] = $attachment->ID;
				}
			}

			$page_uris[ $uri ] = $id;
		}

		return array( $page_uris, $page_attachment_uris );
	}

	/**
	 * Lấy tất cả các quy tắc rewrite cho trang.
	 *
	 * @since 1.5.0
	 *
	 * @return string[] Quy tắc rewrite trang.
	 */
	public function page_rewrite_rules() {
		// Dấu .? thêm ở đầu ngăn xung đột với các biểu thức chính quy khác trong mảng quy tắc.
		$this->add_rewrite_tag( '%pagename%', '(.?.+?)', 'pagename=' );

		return $this->generate_rewrite_rules( $this->get_page_permastruct(), EP_PAGES, true, true, false, false );
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh ngày, với năm, tháng và ngày.
	 *
	 * Cấu trúc đường dẫn tĩnh cho ngày, nếu chưa được thiết lập phụ thuộc vào
	 * cấu trúc đường dẫn tĩnh. Nó có thể là một trong ba định dạng. Đầu tiên là năm,
	 * tháng, ngày; thứ hai là ngày, tháng, năm; và định dạng cuối cùng là tháng,
	 * ngày, năm. Chúng được đối chiếu với cấu trúc đường dẫn tĩnh để xác định
	 * cái nào được sử dụng. Nếu không có cái nào khớp, thì mặc định sẽ được dùng, đó là
	 * năm, tháng, ngày.
	 *
	 * Ngăn ID bài viết và đường dẫn tĩnh ngày chồng chéo nhau. Trong trường hợp
	 * post_id, đường dẫn tĩnh ngày sẽ được thêm tiền tố front permalink với
	 * 'date/' trước đường dẫn tĩnh thực tế để tạo thành cấu trúc đường dẫn tĩnh
	 * ngày đầy đủ.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh ngày khi thành công, false khi thất bại.
	 */
	public function get_date_permastruct() {
		if ( isset( $this->date_structure ) ) {
			return $this->date_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->date_structure = '';
			return false;
		}

		// Đường dẫn tĩnh ngày phải có năm, tháng và ngày được phân tách bằng dấu gạch chéo.
		$endians = array( '%year%/%monthnum%/%day%', '%day%/%monthnum%/%year%', '%monthnum%/%day%/%year%' );

		$this->date_structure = '';
		$date_endian          = '';

		foreach ( $endians as $endian ) {
			if ( str_contains( $this->permalink_structure, $endian ) ) {
				$date_endian = $endian;
				break;
			}
		}

		if ( empty( $date_endian ) ) {
			$date_endian = '%year%/%monthnum%/%day%';
		}

		/*
		 * Không cho phép các thẻ ngày và %post_id% chồng chéo trong cấu trúc
		 * đường dẫn tĩnh. Nếu chúng chồng chéo, di chuyển các thẻ ngày sang $front/date/.
		 */
		$front = $this->front;
		preg_match_all( '/%.+?%/', $this->permalink_structure, $tokens );
		$tok_index = 1;
		foreach ( (array) $tokens[0] as $token ) {
			if ( '%post_id%' === $token && ( $tok_index <= 3 ) ) {
				$front = $front . 'date/';
				break;
			}
			++$tok_index;
		}

		$this->date_structure = $front . $date_endian;

		return $this->date_structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh năm mà không có tháng và ngày.
	 *
	 * Lấy cấu trúc đường dẫn tĩnh ngày và loại bỏ các cấu trúc
	 * đường dẫn tĩnh tháng và ngày.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh năm khi thành công, false khi thất bại.
	 */
	public function get_year_permastruct() {
		$structure = $this->get_date_permastruct();

		if ( empty( $structure ) ) {
			return false;
		}

		$structure = str_replace( '%monthnum%', '', $structure );
		$structure = str_replace( '%day%', '', $structure );
		$structure = preg_replace( '#/+#', '/', $structure );

		return $structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh tháng mà không có ngày và có năm.
	 *
	 * Lấy cấu trúc đường dẫn tĩnh ngày và loại bỏ cấu trúc
	 * đường dẫn tĩnh ngày. Giữ lại cấu trúc đường dẫn tĩnh năm.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh Năm/Tháng khi thành công, false khi thất bại.
	 */
	public function get_month_permastruct() {
		$structure = $this->get_date_permastruct();

		if ( empty( $structure ) ) {
			return false;
		}

		$structure = str_replace( '%day%', '', $structure );
		$structure = preg_replace( '#/+#', '/', $structure );

		return $structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh ngày với tháng và năm.
	 *
	 * Giữ cấu trúc đường dẫn tĩnh ngày với đầy đủ năm, tháng và ngày.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh Năm/Tháng/Ngày khi thành công, false khi thất bại.
	 */
	public function get_day_permastruct() {
		return $this->get_date_permastruct();
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh cho chuyên mục.
	 *
	 * Nếu thuộc tính category_base không có giá trị, thì cấu trúc chuyên mục
	 * sẽ có giá trị thuộc tính front, theo sau là 'category', và cuối cùng
	 * '%category%'. Nếu có, thì thuộc tính root sẽ được sử dụng, cùng với
	 * giá trị thuộc tính category_base.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh chuyên mục khi thành công, false khi thất bại.
	 */
	public function get_category_permastruct() {
		return $this->get_extra_permastruct( 'category' );
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh cho thẻ.
	 *
	 * Nếu thuộc tính tag_base không có giá trị, thì cấu trúc thẻ sẽ có
	 * giá trị thuộc tính front, theo sau là 'tag', và cuối cùng '%tag%'. Nếu
	 * có, thì thuộc tính root sẽ được sử dụng, cùng với giá trị thuộc tính
	 * tag_base.
	 *
	 * @since 2.3.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh thẻ khi thành công, false khi thất bại.
	 */
	public function get_tag_permastruct() {
		return $this->get_extra_permastruct( 'post_tag' );
	}

	/**
	 * Lấy một cấu trúc đường dẫn tĩnh bổ sung theo tên.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name Tên cấu trúc đường dẫn tĩnh.
	 * @return string|false Chuỗi cấu trúc đường dẫn tĩnh khi thành công, false khi thất bại.
	 */
	public function get_extra_permastruct( $name ) {
		if ( empty( $this->permalink_structure ) ) {
			return false;
		}

		if ( isset( $this->extra_permastructs[ $name ] ) ) {
			return $this->extra_permastructs[ $name ]['struct'];
		}

		return false;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh tác giả.
	 *
	 * Cấu trúc đường dẫn tĩnh là thuộc tính front, cơ sở tác giả, và cuối cùng
	 * '/%author%'. Sẽ thiết lập thuộc tính author_structure và sau đó trả về nó
	 * mà không cố gắng thiết lập lại giá trị.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh tác giả khi thành công, false khi thất bại.
	 */
	public function get_author_permastruct() {
		if ( isset( $this->author_structure ) ) {
			return $this->author_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->author_structure = '';
			return false;
		}

		$this->author_structure = $this->front . $this->author_base . '/%author%';

		return $this->author_structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh tìm kiếm.
	 *
	 * Cấu trúc đường dẫn tĩnh là thuộc tính root, cơ sở tìm kiếm, và cuối cùng
	 * '/%search%'. Sẽ thiết lập thuộc tính search_structure và sau đó trả về nó
	 * mà không cố gắng thiết lập lại giá trị.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh tìm kiếm khi thành công, false khi thất bại.
	 */
	public function get_search_permastruct() {
		if ( isset( $this->search_structure ) ) {
			return $this->search_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->search_structure = '';
			return false;
		}

		$this->search_structure = $this->root . $this->search_base . '/%search%';

		return $this->search_structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh trang.
	 *
	 * Cấu trúc đường dẫn tĩnh là thuộc tính root, và '%pagename%'. Sẽ thiết lập
	 * thuộc tính page_structure và sau đó trả về nó mà không cố gắng thiết lập
	 * lại giá trị.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh trang khi thành công, false khi thất bại.
	 */
	public function get_page_permastruct() {
		if ( isset( $this->page_structure ) ) {
			return $this->page_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->page_structure = '';
			return false;
		}

		$this->page_structure = $this->root . '%pagename%';

		return $this->page_structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh nguồn cấp dữ liệu.
	 *
	 * Cấu trúc đường dẫn tĩnh là thuộc tính root, cơ sở nguồn cấp, và cuối cùng
	 * '/%feed%'. Sẽ thiết lập thuộc tính feed_structure và sau đó trả về nó
	 * mà không cố gắng thiết lập lại giá trị.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh nguồn cấp khi thành công, false khi thất bại.
	 */
	public function get_feed_permastruct() {
		if ( isset( $this->feed_structure ) ) {
			return $this->feed_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->feed_structure = '';
			return false;
		}

		$this->feed_structure = $this->root . $this->feed_base . '/%feed%';

		return $this->feed_structure;
	}

	/**
	 * Lấy cấu trúc đường dẫn tĩnh nguồn cấp bình luận.
	 *
	 * Cấu trúc đường dẫn tĩnh là thuộc tính root, thuộc tính cơ sở bình luận, cơ sở
	 * nguồn cấp và cuối cùng '/%feed%'. Sẽ thiết lập thuộc tính comment_feed_structure
	 * và sau đó trả về nó mà không cố gắng thiết lập lại giá trị.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false Cấu trúc đường dẫn tĩnh nguồn cấp bình luận khi thành công, false khi thất bại.
	 */
	public function get_comment_feed_permastruct() {
		if ( isset( $this->comment_feed_structure ) ) {
			return $this->comment_feed_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->comment_feed_structure = '';
			return false;
		}

		$this->comment_feed_structure = $this->root . $this->comments_base . '/' . $this->feed_base . '/%feed%';

		return $this->comment_feed_structure;
	}

	/**
	 * Thêm hoặc cập nhật các thẻ rewrite hiện có (ví dụ: %postname%).
	 *
	 * Nếu thẻ đã tồn tại, thay thế mẫu và truy vấn hiện có cho
	 * thẻ đó, ngược lại thêm thẻ mới.
	 *
	 * @since 1.5.0
	 *
	 * @see WP_Rewrite::$rewritecode
	 * @see WP_Rewrite::$rewritereplace
	 * @see WP_Rewrite::$queryreplace
	 *
	 * @param string $tag   Tên thẻ rewrite cần thêm hoặc cập nhật.
	 * @param string $regex Biểu thức chính quy thay thế cho thẻ trong quy tắc rewrite.
	 * @param string $query Chuỗi nối thêm vào truy vấn đã viết lại. Phải kết thúc bằng '='.
	 */
	public function add_rewrite_tag( $tag, $regex, $query ) {
		$position = array_search( $tag, $this->rewritecode, true );
		if ( false !== $position && null !== $position ) {
			$this->rewritereplace[ $position ] = $regex;
			$this->queryreplace[ $position ]   = $query;
		} else {
			$this->rewritecode[]    = $tag;
			$this->rewritereplace[] = $regex;
			$this->queryreplace[]   = $query;
		}
	}


	/**
	 * Xóa một thẻ rewrite hiện có.
	 *
	 * @since 4.5.0
	 *
	 * @see WP_Rewrite::$rewritecode
	 * @see WP_Rewrite::$rewritereplace
	 * @see WP_Rewrite::$queryreplace
	 *
	 * @param string $tag Tên thẻ rewrite cần xóa.
	 */
	public function remove_rewrite_tag( $tag ) {
		$position = array_search( $tag, $this->rewritecode, true );
		if ( false !== $position && null !== $position ) {
			unset( $this->rewritecode[ $position ] );
			unset( $this->rewritereplace[ $position ] );
			unset( $this->queryreplace[ $position ] );
		}
	}

	/**
	 * Tạo quy tắc rewrite từ cấu trúc đường dẫn tĩnh.
	 *
	 * Hàm WP_Rewrite chính để xây dựng danh sách quy tắc rewrite. Nội dung
	 * của hàm là sự kết hợp giữa phép thuật đen và biểu thức chính quy,
	 * vì vậy tốt nhất hãy bỏ qua nội dung và chuyển sang các tham số.
	 *
	 * @since 1.5.0
	 *
	 * @param string $permalink_structure Cấu trúc đường dẫn tĩnh.
	 * @param int    $ep_mask             Tùy chọn. Mặt nạ điểm cuối xác định điểm cuối nào được thêm vào cấu trúc.
	 *                                    Chấp nhận mặt nạ của:
	 *                                    - `EP_ALL`
	 *                                    - `EP_NONE`
	 *                                    - `EP_ALL_ARCHIVES`
	 *                                    - `EP_ATTACHMENT`
	 *                                    - `EP_AUTHORS`
	 *                                    - `EP_CATEGORIES`
	 *                                    - `EP_COMMENTS`
	 *                                    - `EP_DATE`
	 *                                    - `EP_DAY`
	 *                                    - `EP_MONTH`
	 *                                    - `EP_PAGES`
	 *                                    - `EP_PERMALINK`
	 *                                    - `EP_ROOT`
	 *                                    - `EP_SEARCH`
	 *                                    - `EP_TAGS`
	 *                                    - `EP_YEAR`
	 *                                    Mặc định `EP_NONE`.
	 * @param bool   $paged               Tùy chọn. Có nên thêm quy tắc phân trang lưu trữ cho cấu trúc không.
	 *                                    Mặc định true.
	 * @param bool   $feed                Tùy chọn. Có nên thêm quy tắc rewrite nguồn cấp cho cấu trúc không.
	 *                                    Mặc định true.
	 * @param bool   $forcomments         Tùy chọn. Có nên tạo quy tắc nguồn cấp dưới dạng truy vấn nguồn cấp bình luận không.
	 *                                    Mặc định false.
	 * @param bool   $walk_dirs           Tùy chọn. Có nên duyệt qua các 'thư mục' tạo nên cấu trúc
	 *                                    và xây dựng quy tắc rewrite cho từng cái không. Mặc định true.
	 * @param bool   $endpoints           Tùy chọn. Có nên áp dụng điểm cuối cho quy tắc rewrite đã tạo không.
	 *                                    Mặc định true.
	 * @return string[] Mảng quy tắc rewrite được đánh chỉ mục bởi mẫu regex.
	 */
	public function generate_rewrite_rules( $permalink_structure, $ep_mask = EP_NONE, $paged = true, $feed = true, $forcomments = false, $walk_dirs = true, $endpoints = true ) {
		// Xây dựng regex để khớp phần nguồn cấp của URL, giống như (feed|atom|rss|rss2)/?
		$feedregex2 = '';
		foreach ( (array) $this->feeds as $feed_name ) {
			$feedregex2 .= $feed_name . '|';
		}
		$feedregex2 = '(' . trim( $feedregex2, '|' ) . ')/?$';

		/*
		 * $feedregex giống hệt nhưng có thêm /feed/, vì vậy URL như <permalink>/feed/atom
		 * và <permalink>/atom đều có thể
		 */
		$feedregex = $this->feed_base . '/' . $feedregex2;

		// Xây dựng regex để khớp phần trackback và page/xx của URL.
		$trackbackregex = 'trackback/?$';
		$pageregex      = $this->pagination_base . '/?([0-9]{1,})/?$';
		$commentregex   = $this->comments_pagination_base . '-([0-9]{1,})/?$';
		$embedregex     = 'embed/?$';

		// Xây dựng mảng các regex điểm cuối để nối thêm => truy vấn để nối thêm.
		if ( $endpoints ) {
			$ep_query_append = array();
			foreach ( (array) $this->endpoints as $endpoint ) {
				// Khớp mọi thứ sau tên điểm cuối, nhưng cho phép không có gì xuất hiện ở đó.
				$epmatch = $endpoint[1] . '(/(.*))?/?$';

				// Cái này sẽ được nối thêm vào phần còn lại của truy vấn cho mỗi thư mục.
				$epquery                     = '&' . $endpoint[2] . '=';
				$ep_query_append[ $epmatch ] = array( $endpoint[0], $epquery );
			}
		}

		// Lấy mọi thứ cho đến thẻ rewrite đầu tiên.
		$front = substr( $permalink_structure, 0, strpos( $permalink_structure, '%' ) );

		// Xây dựng mảng các thẻ (lưu ý mảng nói trên nằm trong $tokens[0]).
		preg_match_all( '/%.+?%/', $permalink_structure, $tokens );

		$num_tokens = count( $tokens[0] );

		$index          = $this->index; // Có thể là 'index.php'.
		$feedindex      = $index;
		$trackbackindex = $index;
		$embedindex     = $index;

		/*
		 * Xây dựng danh sách từ mảng rewritecode và queryreplace, trông giống như
		 * tagname=$matches[i] trong đó i là $i hiện tại.
		 */
		$queries = array();
		for ( $i = 0; $i < $num_tokens; ++$i ) {
			if ( 0 < $i ) {
				$queries[ $i ] = $queries[ $i - 1 ] . '&';
			} else {
				$queries[ $i ] = '';
			}

			$query_token    = str_replace( $this->rewritecode, $this->queryreplace, $tokens[0][ $i ] ) . $this->preg_index( $i + 1 );
			$queries[ $i ] .= $query_token;
		}

		// Lấy cấu trúc, trừ đi phần thừa (những thứ không phải thẻ) ở phía trước.
		$structure = $permalink_structure;
		if ( '/' !== $front ) {
			$structure = str_replace( $front, '', $structure );
		}

		/*
		 * Tạo danh sách các thư mục để duyệt qua, tạo quy tắc rewrite cho mỗi cấp
		 * ví dụ, một $structure là /%year%/%monthnum%/%postname% sẽ tạo
		 * quy tắc rewrite cho /%year%/, /%year%/%monthnum%/ và /%year%/%monthnum%/%postname%
		 */
		$structure = trim( $structure, '/' );
		$dirs      = $walk_dirs ? explode( '/', $structure ) : array( $structure );
		$num_dirs  = count( $dirs );

		// Loại bỏ dấu gạch chéo ở phía trước của $front.
		$front = preg_replace( '|^/+|', '', $front );

		// Vòng lặp xử lý chính.
		$post_rewrite = array();
		$struct       = $front;
		for ( $j = 0; $j < $num_dirs; ++$j ) {
			// Lấy cấu trúc cho thư mục này, và loại bỏ dấu gạch chéo ở phía trước.
			$struct .= $dirs[ $j ] . '/'; // Tích lũy. Xem chú thích gần explode('/', $structure) ở trên.
			$struct  = ltrim( $struct, '/' );

			// Thay thế các thẻ bằng biểu thức chính quy.
			$match = str_replace( $this->rewritecode, $this->rewritereplace, $struct );

			// Tạo danh sách các thẻ, và lưu số lượng vào $num_toks.
			$num_toks = preg_match_all( '/%.+?%/', $struct, $toks );

			// Lấy chuỗi 'tagname=$matches[i]'.
			$query = ( ! empty( $num_toks ) && isset( $queries[ $num_toks - 1 ] ) ) ? $queries[ $num_toks - 1 ] : '';

			// Thiết lập $ep_mask_specific dùng để khớp các loại URL cụ thể hơn.
			switch ( $dirs[ $j ] ) {
				case '%year%':
					$ep_mask_specific = EP_YEAR;
					break;
				case '%monthnum%':
					$ep_mask_specific = EP_MONTH;
					break;
				case '%day%':
					$ep_mask_specific = EP_DAY;
					break;
				default:
					$ep_mask_specific = EP_NONE;
			}

			// Tạo truy vấn cho /page/xx.
			$pagematch = $match . $pageregex;
			$pagequery = $index . '?' . $query . '&paged=' . $this->preg_index( $num_toks + 1 );

			// Create query for /comment-page-xx.
			$commentmatch = $match . $commentregex;
			$commentquery = $index . '?' . $query . '&cpage=' . $this->preg_index( $num_toks + 1 );

			if ( get_option( 'page_on_front' ) ) {
				// Create query for Root /comment-page-xx.
				$rootcommentmatch = $match . $commentregex;
				$rootcommentquery = $index . '?' . $query . '&page_id=' . get_option( 'page_on_front' ) . '&cpage=' . $this->preg_index( $num_toks + 1 );
			}

			// Create query for /feed/(feed|atom|rss|rss2|rdf).
			$feedmatch = $match . $feedregex;
			$feedquery = $feedindex . '?' . $query . '&feed=' . $this->preg_index( $num_toks + 1 );

			// Create query for /(feed|atom|rss|rss2|rdf) (see comment near creation of $feedregex).
			$feedmatch2 = $match . $feedregex2;
			$feedquery2 = $feedindex . '?' . $query . '&feed=' . $this->preg_index( $num_toks + 1 );

			// Create query and regex for embeds.
			$embedmatch = $match . $embedregex;
			$embedquery = $embedindex . '?' . $query . '&embed=true';

			// If asked to, turn the feed queries into comment feed ones.
			if ( $forcomments ) {
				$feedquery  .= '&withcomments=1';
				$feedquery2 .= '&withcomments=1';
			}

			// Start creating the array of rewrites for this dir.
			$rewrite = array();

			// ...adding on /feed/ regexes => queries.
			if ( $feed ) {
				$rewrite = array(
					$feedmatch  => $feedquery,
					$feedmatch2 => $feedquery2,
					$embedmatch => $embedquery,
				);
			}

			// ...and /page/xx ones.
			if ( $paged ) {
				$rewrite = array_merge( $rewrite, array( $pagematch => $pagequery ) );
			}

			// Only on pages with comments add ../comment-page-xx/.
			if ( EP_PAGES & $ep_mask || EP_PERMALINK & $ep_mask ) {
				$rewrite = array_merge( $rewrite, array( $commentmatch => $commentquery ) );
			} elseif ( EP_ROOT & $ep_mask && get_option( 'page_on_front' ) ) {
				$rewrite = array_merge( $rewrite, array( $rootcommentmatch => $rootcommentquery ) );
			}

			// Do endpoints.
			if ( $endpoints ) {
				foreach ( (array) $ep_query_append as $regex => $ep ) {
					// Add the endpoints on if the mask fits.
					if ( $ep[0] & $ep_mask || $ep[0] & $ep_mask_specific ) {
						$rewrite[ $match . $regex ] = $index . '?' . $query . $ep[1] . $this->preg_index( $num_toks + 2 );
					}
				}
			}

			// If we've got some tags in this dir.
			if ( $num_toks ) {
				$post = false;
				$page = false;

				/*
				 * Check to see if this dir is permalink-level: i.e. the structure specifies an
				 * individual post. Do this by checking it contains at least one of 1) post name,
				 * 2) post ID, 3) page name, 4) timestamp (year, month, day, hour, second and
				 * minute all present). Set these flags now as we need them for the endpoints.
				 */
				if ( str_contains( $struct, '%postname%' )
					|| str_contains( $struct, '%post_id%' )
					|| str_contains( $struct, '%pagename%' )
					|| ( str_contains( $struct, '%year%' )
						&& str_contains( $struct, '%monthnum%' )
						&& str_contains( $struct, '%day%' )
						&& str_contains( $struct, '%hour%' )
						&& str_contains( $struct, '%minute%' )
						&& str_contains( $struct, '%second%' ) )
				) {
					$post = true;
					if ( str_contains( $struct, '%pagename%' ) ) {
						$page = true;
					}
				}

				if ( ! $post ) {
					// For custom post types, we need to add on endpoints as well.
					foreach ( get_post_types( array( '_builtin' => false ) ) as $ptype ) {
						if ( str_contains( $struct, "%$ptype%" ) ) {
							$post = true;

							// This is for page style attachment URLs.
							$page = is_post_type_hierarchical( $ptype );
							break;
						}
					}
				}

				// If creating rules for a permalink, do all the endpoints like attachments etc.
				if ( $post ) {
					// Create query and regex for trackback.
					$trackbackmatch = $match . $trackbackregex;
					$trackbackquery = $trackbackindex . '?' . $query . '&tb=1';

					// Create query and regex for embeds.
					$embedmatch = $match . $embedregex;
					$embedquery = $embedindex . '?' . $query . '&embed=true';

					// Trim slashes from the end of the regex for this dir.
					$match = rtrim( $match, '/' );

					// Get rid of brackets.
					$submatchbase = str_replace( array( '(', ')' ), '', $match );

					// Add a rule for at attachments, which take the form of <permalink>/some-text.
					$sub1 = $submatchbase . '/([^/]+)/';

					// Add trackback regex <permalink>/trackback/...
					$sub1tb = $sub1 . $trackbackregex;

					// And <permalink>/feed/(atom|...)
					$sub1feed = $sub1 . $feedregex;

					// And <permalink>/(feed|atom...)
					$sub1feed2 = $sub1 . $feedregex2;

					// And <permalink>/comment-page-xx
					$sub1comment = $sub1 . $commentregex;

					// And <permalink>/embed/...
					$sub1embed = $sub1 . $embedregex;

					/*
					 * Add another rule to match attachments in the explicit form:
					 * <permalink>/attachment/some-text
					 */
					$sub2 = $submatchbase . '/attachment/([^/]+)/';

					// And add trackbacks <permalink>/attachment/trackback.
					$sub2tb = $sub2 . $trackbackregex;

					// Feeds, <permalink>/attachment/feed/(atom|...)
					$sub2feed = $sub2 . $feedregex;

					// And feeds again on to this <permalink>/attachment/(feed|atom...)
					$sub2feed2 = $sub2 . $feedregex2;

					// And <permalink>/comment-page-xx
					$sub2comment = $sub2 . $commentregex;

					// And <permalink>/embed/...
					$sub2embed = $sub2 . $embedregex;

					// Create queries for these extra tag-ons we've just dealt with.
					$subquery        = $index . '?attachment=' . $this->preg_index( 1 );
					$subtbquery      = $subquery . '&tb=1';
					$subfeedquery    = $subquery . '&feed=' . $this->preg_index( 2 );
					$subcommentquery = $subquery . '&cpage=' . $this->preg_index( 2 );
					$subembedquery   = $subquery . '&embed=true';

					// Do endpoints for attachments.
					if ( ! empty( $endpoints ) ) {
						foreach ( (array) $ep_query_append as $regex => $ep ) {
							if ( $ep[0] & EP_ATTACHMENT ) {
								$rewrite[ $sub1 . $regex ] = $subquery . $ep[1] . $this->preg_index( 3 );
								$rewrite[ $sub2 . $regex ] = $subquery . $ep[1] . $this->preg_index( 3 );
							}
						}
					}

					/*
					 * Now we've finished with endpoints, finish off the $sub1 and $sub2 matches
					 * add a ? as we don't have to match that last slash, and finally a $ so we
					 * match to the end of the URL
					 */
					$sub1 .= '?$';
					$sub2 .= '?$';

					/*
					 * Post pagination, e.g. <permalink>/2/
					 * Previously: '(/[0-9]+)?/?$', which produced '/2' for page.
					 * When cast to int, returned 0.
					 */
					$match = $match . '(?:/([0-9]+))?/?$';
					$query = $index . '?' . $query . '&page=' . $this->preg_index( $num_toks + 1 );

					// Not matching a permalink so this is a lot simpler.
				} else {
					// Close the match and finalize the query.
					$match .= '?$';
					$query  = $index . '?' . $query;
				}

				/*
				 * Create the final array for this dir by joining the $rewrite array (which currently
				 * only contains rules/queries for trackback, pages etc) to the main regex/query for
				 * this dir
				 */
				$rewrite = array_merge( $rewrite, array( $match => $query ) );

				// If we're matching a permalink, add those extras (attachments etc) on.
				if ( $post ) {
					// Add trackback.
					$rewrite = array_merge( array( $trackbackmatch => $trackbackquery ), $rewrite );

					// Add embed.
					$rewrite = array_merge( array( $embedmatch => $embedquery ), $rewrite );

					// Add regexes/queries for attachments, attachment trackbacks and so on.
					if ( ! $page ) {
						// Require <permalink>/attachment/stuff form for pages because of confusion with subpages.
						$rewrite = array_merge(
							$rewrite,
							array(
								$sub1        => $subquery,
								$sub1tb      => $subtbquery,
								$sub1feed    => $subfeedquery,
								$sub1feed2   => $subfeedquery,
								$sub1comment => $subcommentquery,
								$sub1embed   => $subembedquery,
							)
						);
					}

					$rewrite = array_merge(
						array(
							$sub2        => $subquery,
							$sub2tb      => $subtbquery,
							$sub2feed    => $subfeedquery,
							$sub2feed2   => $subfeedquery,
							$sub2comment => $subcommentquery,
							$sub2embed   => $subembedquery,
						),
						$rewrite
					);
				}
			}
			// Add the rules for this dir to the accumulating $post_rewrite.
			$post_rewrite = array_merge( $rewrite, $post_rewrite );
		}

		// The finished rules. phew!
		return $post_rewrite;
	}

	/**
	 * Generates rewrite rules with permalink structure and walking directory only.
	 *
	 * Shorten version of WP_Rewrite::generate_rewrite_rules() that allows for shorter
	 * list of parameters. See the method for longer description of what generating
	 * rewrite rules does.
	 *
	 * @since 1.5.0
	 *
	 * @see WP_Rewrite::generate_rewrite_rules() See for long description and rest of parameters.
	 *
	 * @param string $permalink_structure The permalink structure to generate rules.
	 * @param bool   $walk_dirs           Optional. Whether to create list of directories to walk over.
	 *                                    Default false.
	 * @return array An array of rewrite rules keyed by their regex pattern.
	 */
	public function generate_rewrite_rule( $permalink_structure, $walk_dirs = false ) {
		return $this->generate_rewrite_rules( $permalink_structure, EP_NONE, false, false, false, $walk_dirs );
	}

	/**
	 * Constructs rewrite matches and queries from permalink structure.
	 *
	 * Runs the action {@see 'generate_rewrite_rules'} with the parameter that is an
	 * reference to the current WP_Rewrite instance to further manipulate the
	 * permalink structures and rewrite rules. Runs the {@see 'rewrite_rules_array'}
	 * filter on the full rewrite rule array.
	 *
	 * There are two ways to manipulate the rewrite rules, one by hooking into
	 * the {@see 'generate_rewrite_rules'} action and gaining full control of the
	 * object or just manipulating the rewrite rule array before it is passed
	 * from the function.
	 *
	 * @since 1.5.0
	 *
	 * @return string[] An associative array of matches and queries.
	 */
	public function rewrite_rules() {
		$rewrite = array();

		if ( empty( $this->permalink_structure ) ) {
			return $rewrite;
		}

		// robots.txt -- only if installed at the root.
		$home_path      = parse_url( home_url() );
		$robots_rewrite = ( empty( $home_path['path'] ) || '/' === $home_path['path'] ) ? array( 'robots\.txt$' => $this->index . '?robots=1' ) : array();

		// favicon.ico -- only if installed at the root.
		$favicon_rewrite = ( empty( $home_path['path'] ) || '/' === $home_path['path'] ) ? array( 'favicon\.ico$' => $this->index . '?favicon=1' ) : array();

		// sitemap.xml -- only if installed at the root.
		$sitemap_rewrite = ( empty( $home_path['path'] ) || '/' === $home_path['path'] ) ? array( 'sitemap\.xml' => $this->index . '??sitemap=index' ) : array();

		// Old feed and service files.
		$deprecated_files = array(
			'.*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\.php$' => $this->index . '?feed=old',
			'.*wp-app\.php(/.*)?$' => $this->index . '?error=403',
		);

		// Registration rules.
		$registration_pages = array();
		if ( is_multisite() && is_main_site() ) {
			$registration_pages['.*wp-signup.php$']   = $this->index . '?signup=true';
			$registration_pages['.*wp-activate.php$'] = $this->index . '?activate=true';
		}

		// Deprecated.
		$registration_pages['.*wp-register.php$'] = $this->index . '?register=true';

		// Post rewrite rules.
		$post_rewrite = $this->generate_rewrite_rules( $this->permalink_structure, EP_PERMALINK );

		/**
		 * Filters rewrite rules used for "post" archives.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $post_rewrite Array of rewrite rules for posts, keyed by their regex pattern.
		 */
		$post_rewrite = apply_filters( 'post_rewrite_rules', $post_rewrite );

		// Date rewrite rules.
		$date_rewrite = $this->generate_rewrite_rules( $this->get_date_permastruct(), EP_DATE );

		/**
		 * Filters rewrite rules used for date archives.
		 *
		 * Likely date archives would include `/yyyy/`, `/yyyy/mm/`, and `/yyyy/mm/dd/`.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $date_rewrite Array of rewrite rules for date archives, keyed by their regex pattern.
		 */
		$date_rewrite = apply_filters( 'date_rewrite_rules', $date_rewrite );

		// Root-level rewrite rules.
		$root_rewrite = $this->generate_rewrite_rules( $this->root . '/', EP_ROOT );

		/**
		 * Filters rewrite rules used for root-level archives.
		 *
		 * Likely root-level archives would include pagination rules for the homepage
		 * as well as site-wide post feeds (e.g. `/feed/`, and `/feed/atom/`).
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $root_rewrite Array of root-level rewrite rules, keyed by their regex pattern.
		 */
		$root_rewrite = apply_filters( 'root_rewrite_rules', $root_rewrite );

		// Comments rewrite rules.
		$comments_rewrite = $this->generate_rewrite_rules( $this->root . $this->comments_base, EP_COMMENTS, false, true, true, false );

		/**
		 * Filters rewrite rules used for comment feed archives.
		 *
		 * Likely comments feed archives include `/comments/feed/` and `/comments/feed/atom/`.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $comments_rewrite Array of rewrite rules for the site-wide comments feeds, keyed by their regex pattern.
		 */
		$comments_rewrite = apply_filters( 'comments_rewrite_rules', $comments_rewrite );

		// Search rewrite rules.
		$search_structure = $this->get_search_permastruct();
		$search_rewrite   = $this->generate_rewrite_rules( $search_structure, EP_SEARCH );

		/**
		 * Filters rewrite rules used for search archives.
		 *
		 * Likely search-related archives include `/search/search+query/` as well as
		 * pagination and feed paths for a search.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $search_rewrite Array of rewrite rules for search queries, keyed by their regex pattern.
		 */
		$search_rewrite = apply_filters( 'search_rewrite_rules', $search_rewrite );

		// Author rewrite rules.
		$author_rewrite = $this->generate_rewrite_rules( $this->get_author_permastruct(), EP_AUTHORS );

		/**
		 * Filters rewrite rules used for author archives.
		 *
		 * Likely author archives would include `/author/author-name/`, as well as
		 * pagination and feed paths for author archives.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $author_rewrite Array of rewrite rules for author archives, keyed by their regex pattern.
		 */
		$author_rewrite = apply_filters( 'author_rewrite_rules', $author_rewrite );

		// Pages rewrite rules.
		$page_rewrite = $this->page_rewrite_rules();

		/**
		 * Filters rewrite rules used for "page" post type archives.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $page_rewrite Array of rewrite rules for the "page" post type, keyed by their regex pattern.
		 */
		$page_rewrite = apply_filters( 'page_rewrite_rules', $page_rewrite );

		// Extra permastructs.
		foreach ( $this->extra_permastructs as $permastructname => $struct ) {
			if ( is_array( $struct ) ) {
				if ( count( $struct ) === 2 ) {
					$rules = $this->generate_rewrite_rules( $struct[0], $struct[1] );
				} else {
					$rules = $this->generate_rewrite_rules( $struct['struct'], $struct['ep_mask'], $struct['paged'], $struct['feed'], $struct['forcomments'], $struct['walk_dirs'], $struct['endpoints'] );
				}
			} else {
				$rules = $this->generate_rewrite_rules( $struct );
			}

			/**
			 * Filters rewrite rules used for individual permastructs.
			 *
			 * The dynamic portion of the hook name, `$permastructname`, refers
			 * to the name of the registered permastruct.
			 *
			 * Possible hook names include:
			 *
			 *  - `category_rewrite_rules`
			 *  - `post_format_rewrite_rules`
			 *  - `post_tag_rewrite_rules`
			 *
			 * @since 3.1.0
			 *
			 * @param string[] $rules Array of rewrite rules generated for the current permastruct, keyed by their regex pattern.
			 */
			$rules = apply_filters( "{$permastructname}_rewrite_rules", $rules );

			if ( 'post_tag' === $permastructname ) {

				/**
				 * Filters rewrite rules used specifically for Tags.
				 *
				 * @since 2.3.0
				 * @deprecated 3.1.0 Use {@see 'post_tag_rewrite_rules'} instead.
				 *
				 * @param string[] $rules Array of rewrite rules generated for tags, keyed by their regex pattern.
				 */
				$rules = apply_filters_deprecated( 'tag_rewrite_rules', array( $rules ), '3.1.0', 'post_tag_rewrite_rules' );
			}

			$this->extra_rules_top = array_merge( $this->extra_rules_top, $rules );
		}

		// Put them together.
		if ( $this->use_verbose_page_rules ) {
			$this->rules = array_merge( $this->extra_rules_top, $robots_rewrite, $favicon_rewrite, $sitemap_rewrite, $deprecated_files, $registration_pages, $root_rewrite, $comments_rewrite, $search_rewrite, $author_rewrite, $date_rewrite, $page_rewrite, $post_rewrite, $this->extra_rules );
		} else {
			$this->rules = array_merge( $this->extra_rules_top, $robots_rewrite, $favicon_rewrite, $sitemap_rewrite, $deprecated_files, $registration_pages, $root_rewrite, $comments_rewrite, $search_rewrite, $author_rewrite, $date_rewrite, $post_rewrite, $page_rewrite, $this->extra_rules );
		}

		/**
		 * Fires after the rewrite rules are generated.
		 *
		 * @since 1.5.0
		 *
		 * @param WP_Rewrite $wp_rewrite Current WP_Rewrite instance (passed by reference).
		 */
		do_action_ref_array( 'generate_rewrite_rules', array( &$this ) );

		/**
		 * Filters the full set of generated rewrite rules.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $rules The compiled array of rewrite rules, keyed by their regex pattern.
		 */
		$this->rules = apply_filters( 'rewrite_rules_array', $this->rules );

		return $this->rules;
	}

	/**
	 * Retrieves the rewrite rules.
	 *
	 * The difference between this method and WP_Rewrite::rewrite_rules() is that
	 * this method stores the rewrite rules in the 'rewrite_rules' option and retrieves
	 * it. This prevents having to process all of the permalinks to get the rewrite rules
	 * in the form of caching.
	 *
	 * @since 1.5.0
	 *
	 * @return string[] Array of rewrite rules keyed by their regex pattern.
	 */
	public function wp_rewrite_rules() {
		$this->rules = get_option( 'rewrite_rules' );
		if ( empty( $this->rules ) ) {
			$this->refresh_rewrite_rules();
		}

		return $this->rules;
	}

	/**
	 * Refreshes the rewrite rules, saving the fresh value to the database.
	 *
	 * If the {@see 'wp_loaded'} action has not occurred yet, will postpone saving to the database.
	 *
	 * @since 6.4.0
	 */
	private function refresh_rewrite_rules() {
		$this->rules   = '';
		$this->matches = 'matches';

		$this->rewrite_rules();

		if ( ! did_action( 'wp_loaded' ) ) {
			/*
			 * It is not safe to save the results right now, as the rules may be partial.
			 * Need to give all rules the chance to register.
			 */
			add_action( 'wp_loaded', array( $this, 'flush_rules' ) );
		} else {
			update_option( 'rewrite_rules', $this->rules );
		}
	}

	/**
	 * Retrieves mod_rewrite-formatted rewrite rules to write to .htaccess.
	 *
	 * Does not actually write to the .htaccess file, but creates the rules for
	 * the process that will.
	 *
	 * Will add the non_wp_rules property rules to the .htaccess file before
	 * the WordPress rewrite rules one.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function mod_rewrite_rules() {
		if ( ! $this->using_permalinks() ) {
			return '';
		}

		$site_root = parse_url( site_url() );
		if ( isset( $site_root['path'] ) ) {
			$site_root = trailingslashit( $site_root['path'] );
		}

		$home_root = parse_url( home_url() );
		if ( isset( $home_root['path'] ) ) {
			$home_root = trailingslashit( $home_root['path'] );
		} else {
			$home_root = '/';
		}

		$rules  = "<IfModule mod_rewrite.c>\n";
		$rules .= "RewriteEngine On\n";
		$rules .= "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
		$rules .= "RewriteBase $home_root\n";

		// Prevent -f checks on index.php.
		$rules .= "RewriteRule ^index\.php$ - [L]\n";

		// Add in the rules that don't redirect to WP's index.php (and thus shouldn't be handled by WP at all).
		foreach ( (array) $this->non_wp_rules as $match => $query ) {
			// Apache 1.3 does not support the reluctant (non-greedy) modifier.
			$match = str_replace( '.+?', '.+', $match );

			$rules .= 'RewriteRule ^' . $match . ' ' . $home_root . $query . " [QSA,L]\n";
		}

		if ( $this->use_verbose_rules ) {
			$this->matches = '';
			$rewrite       = $this->rewrite_rules();
			$num_rules     = count( $rewrite );
			$rules        .= "RewriteCond %{REQUEST_FILENAME} -f [OR]\n" .
				"RewriteCond %{REQUEST_FILENAME} -d\n" .
				"RewriteRule ^.*$ - [S=$num_rules]\n";

			foreach ( (array) $rewrite as $match => $query ) {
				// Apache 1.3 does not support the reluctant (non-greedy) modifier.
				$match = str_replace( '.+?', '.+', $match );

				if ( str_contains( $query, $this->index ) ) {
					$rules .= 'RewriteRule ^' . $match . ' ' . $home_root . $query . " [QSA,L]\n";
				} else {
					$rules .= 'RewriteRule ^' . $match . ' ' . $site_root . $query . " [QSA,L]\n";
				}
			}
		} else {
			$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\n" .
				"RewriteCond %{REQUEST_FILENAME} !-d\n" .
				"RewriteRule . {$home_root}{$this->index} [L]\n";
		}

		$rules .= "</IfModule>\n";

		/**
		 * Filters the list of rewrite rules formatted for output to an .htaccess file.
		 *
		 * @since 1.5.0
		 *
		 * @param string $rules mod_rewrite Rewrite rules formatted for .htaccess.
		 */
		$rules = apply_filters( 'mod_rewrite_rules', $rules );

		/**
		 * Filters the list of rewrite rules formatted for output to an .htaccess file.
		 *
		 * @since 1.5.0
		 * @deprecated 1.5.0 Use the {@see 'mod_rewrite_rules'} filter instead.
		 *
		 * @param string $rules mod_rewrite Rewrite rules formatted for .htaccess.
		 */
		return apply_filters_deprecated( 'rewrite_rules', array( $rules ), '1.5.0', 'mod_rewrite_rules' );
	}

	/**
	 * Retrieves IIS7 URL Rewrite formatted rewrite rules to write to web.config file.
	 *
	 * Does not actually write to the web.config file, but creates the rules for
	 * the process that will.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $add_parent_tags Optional. Whether to add parent tags to the rewrite rule sets.
	 *                              Default false.
	 * @return string IIS7 URL rewrite rule sets.
	 */
	public function iis7_url_rewrite_rules( $add_parent_tags = false ) {
		if ( ! $this->using_permalinks() ) {
			return '';
		}
		$rules = '';
		if ( $add_parent_tags ) {
			$rules .= '<configuration>
	<system.webServer>
		<rewrite>
			<rules>';
		}

		$rules .= '
			<rule name="WordPress: ' . esc_attr( home_url() ) . '" patternSyntax="Wildcard">
				<match url="*" />
					<conditions>
						<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
						<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
					</conditions>
				<action type="Rewrite" url="index.php" />
			</rule>';

		if ( $add_parent_tags ) {
			$rules .= '
			</rules>
		</rewrite>
	</system.webServer>
</configuration>';
		}

		/**
		 * Filters the list of rewrite rules formatted for output to a web.config.
		 *
		 * @since 2.8.0
		 *
		 * @param string $rules Rewrite rules formatted for IIS web.config.
		 */
		return apply_filters( 'iis7_url_rewrite_rules', $rules );
	}

	/**
	 * Adds a rewrite rule that transforms a URL structure to a set of query vars.
	 *
	 * Any value in the $after parameter that isn't 'bottom' will result in the rule
	 * being placed at the top of the rewrite rules.
	 *
	 * @since 2.1.0
	 * @since 4.4.0 Array support was added to the `$query` parameter.
	 *
	 * @param string       $regex Regular expression to match request against.
	 * @param string|array $query The corresponding query vars for this rewrite rule.
	 * @param string       $after Optional. Priority of the new rule. Accepts 'top'
	 *                            or 'bottom'. Default 'bottom'.
	 */
	public function add_rule( $regex, $query, $after = 'bottom' ) {
		if ( is_array( $query ) ) {
			$external = false;
			$query    = add_query_arg( $query, 'index.php' );
		} else {
			$index = ! str_contains( $query, '?' ) ? strlen( $query ) : strpos( $query, '?' );
			$front = substr( $query, 0, $index );

			$external = $front !== $this->index;
		}

		// "external" = it doesn't correspond to index.php.
		if ( $external ) {
			$this->add_external_rule( $regex, $query );
		} else {
			if ( 'bottom' === $after ) {
				$this->extra_rules = array_merge( $this->extra_rules, array( $regex => $query ) );
			} else {
				$this->extra_rules_top = array_merge( $this->extra_rules_top, array( $regex => $query ) );
			}
		}
	}

	/**
	 * Adds a rewrite rule that doesn't correspond to index.php.
	 *
	 * @since 2.1.0
	 *
	 * @param string $regex Regular expression to match request against.
	 * @param string $query The corresponding query vars for this rewrite rule.
	 */
	public function add_external_rule( $regex, $query ) {
		$this->non_wp_rules[ $regex ] = $query;
	}

	/**
	 * Adds an endpoint, like /trackback/.
	 *
	 * @since 2.1.0
	 * @since 3.9.0 $query_var parameter added.
	 * @since 4.3.0 Added support for skipping query var registration by passing `false` to `$query_var`.
	 *
	 * @see add_rewrite_endpoint() for full documentation.
	 * @global WP $wp Current WordPress environment instance.
	 *
	 * @param string      $name      Name of the endpoint.
	 * @param int         $places    Endpoint mask describing the places the endpoint should be added.
	 *                               Accepts a mask of:
	 *                               - `EP_ALL`
	 *                               - `EP_NONE`
	 *                               - `EP_ALL_ARCHIVES`
	 *                               - `EP_ATTACHMENT`
	 *                               - `EP_AUTHORS`
	 *                               - `EP_CATEGORIES`
	 *                               - `EP_COMMENTS`
	 *                               - `EP_DATE`
	 *                               - `EP_DAY`
	 *                               - `EP_MONTH`
	 *                               - `EP_PAGES`
	 *                               - `EP_PERMALINK`
	 *                               - `EP_ROOT`
	 *                               - `EP_SEARCH`
	 *                               - `EP_TAGS`
	 *                               - `EP_YEAR`
	 * @param string|bool $query_var Optional. Name of the corresponding query variable. Pass `false` to
	 *                               skip registering a query_var for this endpoint. Defaults to the
	 *                               value of `$name`.
	 */
	public function add_endpoint( $name, $places, $query_var = true ) {
		global $wp;

		// For backward compatibility, if null has explicitly been passed as `$query_var`, assume `true`.
		if ( true === $query_var || null === $query_var ) {
			$query_var = $name;
		}
		$this->endpoints[] = array( $places, $name, $query_var );

		if ( $query_var ) {
			$wp->add_query_var( $query_var );
		}
	}

	/**
	 * Adds a new permalink structure.
	 *
	 * A permalink structure (permastruct) is an abstract definition of a set of rewrite rules;
	 * it is an easy way of expressing a set of regular expressions that rewrite to a set of
	 * query strings. The new permastruct is added to the WP_Rewrite::$extra_permastructs array.
	 *
	 * When the rewrite rules are built by WP_Rewrite::rewrite_rules(), all of these extra
	 * permastructs are passed to WP_Rewrite::generate_rewrite_rules() which transforms them
	 * into the regular expressions that many love to hate.
	 *
	 * The `$args` parameter gives you control over how WP_Rewrite::generate_rewrite_rules()
	 * works on the new permastruct.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name   Name for permalink structure.
	 * @param string $struct Permalink structure (e.g. category/%category%)
	 * @param array  $args   {
	 *     Optional. Arguments for building rewrite rules based on the permalink structure.
	 *     Default empty array.
	 *
	 *     @type bool $with_front  Whether the structure should be prepended with `WP_Rewrite::$front`.
	 *                             Default true.
	 *     @type int  $ep_mask     The endpoint mask defining which endpoints are added to the structure.
	 *                             Accepts a mask of:
	 *                             - `EP_ALL`
	 *                             - `EP_NONE`
	 *                             - `EP_ALL_ARCHIVES`
	 *                             - `EP_ATTACHMENT`
	 *                             - `EP_AUTHORS`
	 *                             - `EP_CATEGORIES`
	 *                             - `EP_COMMENTS`
	 *                             - `EP_DATE`
	 *                             - `EP_DAY`
	 *                             - `EP_MONTH`
	 *                             - `EP_PAGES`
	 *                             - `EP_PERMALINK`
	 *                             - `EP_ROOT`
	 *                             - `EP_SEARCH`
	 *                             - `EP_TAGS`
	 *                             - `EP_YEAR`
	 *                             Default `EP_NONE`.
	 *     @type bool $paged       Whether archive pagination rules should be added for the structure.
	 *                             Default true.
	 *     @type bool $feed        Whether feed rewrite rules should be added for the structure. Default true.
	 *     @type bool $forcomments Whether the feed rules should be a query for a comments feed. Default false.
	 *     @type bool $walk_dirs   Whether the 'directories' making up the structure should be walked over
	 *                             and rewrite rules built for each in-turn. Default true.
	 *     @type bool $endpoints   Whether endpoints should be applied to the generated rules. Default true.
	 * }
	 */
	public function add_permastruct( $name, $struct, $args = array() ) {
		// Back-compat for the old parameters: $with_front and $ep_mask.
		if ( ! is_array( $args ) ) {
			$args = array( 'with_front' => $args );
		}

		if ( func_num_args() === 4 ) {
			$args['ep_mask'] = func_get_arg( 3 );
		}

		$defaults = array(
			'with_front'  => true,
			'ep_mask'     => EP_NONE,
			'paged'       => true,
			'feed'        => true,
			'forcomments' => false,
			'walk_dirs'   => true,
			'endpoints'   => true,
		);

		$args = array_intersect_key( $args, $defaults );
		$args = wp_parse_args( $args, $defaults );

		if ( $args['with_front'] ) {
			$struct = $this->front . $struct;
		} else {
			$struct = $this->root . $struct;
		}

		$args['struct'] = $struct;

		$this->extra_permastructs[ $name ] = $args;
	}

	/**
	 * Removes a permalink structure.
	 *
	 * @since 4.5.0
	 *
	 * @param string $name Name for permalink structure.
	 */
	public function remove_permastruct( $name ) {
		unset( $this->extra_permastructs[ $name ] );
	}

	/**
	 * Removes rewrite rules and then recreate rewrite rules.
	 *
	 * Calls WP_Rewrite::wp_rewrite_rules() after removing the 'rewrite_rules' option.
	 * If the function named 'save_mod_rewrite_rules' exists, it will be called.
	 *
	 * @since 2.0.1
	 *
	 * @param bool $hard Whether to update .htaccess (hard flush) or just update rewrite_rules option (soft flush). Default is true (hard).
	 */
	public function flush_rules( $hard = true ) {
		static $do_hard_later = null;

		// Prevent this action from running before everyone has registered their rewrites.
		if ( ! did_action( 'wp_loaded' ) ) {
			add_action( 'wp_loaded', array( $this, 'flush_rules' ) );
			$do_hard_later = ( isset( $do_hard_later ) ) ? $do_hard_later || $hard : $hard;
			return;
		}

		if ( isset( $do_hard_later ) ) {
			$hard = $do_hard_later;
			unset( $do_hard_later );
		}

		$this->refresh_rewrite_rules();

		/**
		 * Filters whether a "hard" rewrite rule flush should be performed when requested.
		 *
		 * A "hard" flush updates .htaccess (Apache) or web.config (IIS).
		 *
		 * @since 3.7.0
		 *
		 * @param bool $hard Whether to flush rewrite rules "hard". Default true.
		 */
		if ( ! $hard || ! apply_filters( 'flush_rewrite_rules_hard', true ) ) {
			return;
		}
		if ( function_exists( 'save_mod_rewrite_rules' ) ) {
			save_mod_rewrite_rules();
		}
		if ( function_exists( 'iis7_save_url_rewrite_rules' ) ) {
			iis7_save_url_rewrite_rules();
		}
	}

	/**
	 * Sets up the object's properties.
	 *
	 * The 'use_verbose_page_rules' object property will be set to true if the
	 * permalink structure begins with one of the following: '%postname%', '%category%',
	 * '%tag%', or '%author%'.
	 *
	 * @since 1.5.0
	 */
	public function init() {
		$this->extra_rules         = array();
		$this->non_wp_rules        = array();
		$this->endpoints           = array();
		$this->permalink_structure = get_option( 'permalink_structure' );
		$this->front               = substr( $this->permalink_structure, 0, strpos( $this->permalink_structure, '%' ) );
		$this->root                = '';

		if ( $this->using_index_permalinks() ) {
			$this->root = $this->index . '/';
		}

		unset( $this->author_structure );
		unset( $this->date_structure );
		unset( $this->page_structure );
		unset( $this->search_structure );
		unset( $this->feed_structure );
		unset( $this->comment_feed_structure );

		$this->use_trailing_slashes = str_ends_with( $this->permalink_structure, '/' );

		// Enable generic rules for pages if permalink structure doesn't begin with a wildcard.
		if ( preg_match( '/^[^%]*%(?:postname|category|tag|author)%/', $this->permalink_structure ) ) {
			$this->use_verbose_page_rules = true;
		} else {
			$this->use_verbose_page_rules = false;
		}
	}

	/**
	 * Sets the main permalink structure for the site.
	 *
	 * Will update the 'permalink_structure' option, if there is a difference
	 * between the current permalink structure and the parameter value. Calls
	 * WP_Rewrite::init() after the option is updated.
	 *
	 * Fires the {@see 'permalink_structure_changed'} action once the init call has
	 * processed passing the old and new values
	 *
	 * @since 1.5.0
	 *
	 * @param string $permalink_structure Permalink structure.
	 */
	public function set_permalink_structure( $permalink_structure ) {
		if ( $this->permalink_structure !== $permalink_structure ) {
			$old_permalink_structure = $this->permalink_structure;
			update_option( 'permalink_structure', $permalink_structure );

			$this->init();

			/**
			 * Fires after the permalink structure is updated.
			 *
			 * @since 2.8.0
			 *
			 * @param string $old_permalink_structure The previous permalink structure.
			 * @param string $permalink_structure     The new permalink structure.
			 */
			do_action( 'permalink_structure_changed', $old_permalink_structure, $permalink_structure );
		}
	}

	/**
	 * Sets the category base for the category permalink.
	 *
	 * Will update the 'category_base' option, if there is a difference between
	 * the current category base and the parameter value. Calls WP_Rewrite::init()
	 * after the option is updated.
	 *
	 * @since 1.5.0
	 *
	 * @param string $category_base Category permalink structure base.
	 */
	public function set_category_base( $category_base ) {
		if ( get_option( 'category_base' ) !== $category_base ) {
			update_option( 'category_base', $category_base );
			$this->init();
		}
	}

	/**
	 * Sets the tag base for the tag permalink.
	 *
	 * Will update the 'tag_base' option, if there is a difference between the
	 * current tag base and the parameter value. Calls WP_Rewrite::init() after
	 * the option is updated.
	 *
	 * @since 2.3.0
	 *
	 * @param string $tag_base Tag permalink structure base.
	 */
	public function set_tag_base( $tag_base ) {
		if ( get_option( 'tag_base' ) !== $tag_base ) {
			update_option( 'tag_base', $tag_base );
			$this->init();
		}
	}

	/**
	 * Constructor - Calls init(), which runs setup.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->init();
	}
}
