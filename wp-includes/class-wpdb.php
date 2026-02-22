<?php
/**
 * Lớp trừu tượng truy cập cơ sở dữ liệu WordPress.
 *
 * Mã nguồn gốc từ {@link http://php.justinvincent.com Justin Vincent (justin@visunet.ie)}
 *
 * @package WordPress
 * @subpackage Database
 * @since 0.71
 */

/**
 * @since 0.71
 */
define( 'EZSQL_VERSION', 'WP1.25' );

/**
 * @since 0.71
 */
define( 'OBJECT', 'OBJECT' );
// phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase
define( 'object', 'OBJECT' ); // Tương thích ngược.

/**
 * @since 2.5.0
 */
define( 'OBJECT_K', 'OBJECT_K' );

/**
 * @since 0.71
 */
define( 'ARRAY_A', 'ARRAY_A' );

/**
 * @since 0.71
 */
define( 'ARRAY_N', 'ARRAY_N' );

/**
 * Lớp trừu tượng truy cập cơ sở dữ liệu WordPress.
 *
 * Lớp này được sử dụng để tương tác với cơ sở dữ liệu mà không cần dùng các câu lệnh SQL thô.
 * Theo mặc định, WordPress sử dụng lớp này để khởi tạo đối tượng toàn cục $wpdb, cung cấp
 * quyền truy cập vào cơ sở dữ liệu WordPress.
 *
 * Có thể thay thế lớp này bằng lớp của riêng bạn bằng cách đặt biến toàn cục $wpdb
 * trong file wp-content/db.php thành lớp của bạn. Lớp wpdb vẫn sẽ được include, vì vậy bạn có thể
 * kế thừa nó hoặc đơn giản là dùng lớp của riêng bạn.
 *
 * @link https://developer.wordpress.org/reference/classes/wpdb/
 *
 * @since 0.71
 */
#[AllowDynamicProperties]
class wpdb {

	/**
	 * Có hiển thị lỗi SQL/DB hay không.
	 *
	 * Mặc định là hiển thị lỗi nếu cả WP_DEBUG và WP_DEBUG_DISPLAY đều có giá trị true.
	 *
	 * @since 0.71
	 *
	 * @var bool
	 */
	public $show_errors = false;

	/**
	 * Có ẩn lỗi trong quá trình khởi động DB hay không. Mặc định false.
	 *
	 * @since 2.5.0
	 *
	 * @var bool
	 */
	public $suppress_errors = false;

	/**
	 * Lỗi gặp phải trong truy vấn cuối cùng.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	public $last_error = '';

	/**
	 * Số lượng truy vấn đã thực hiện.
	 *
	 * @since 1.2.0
	 *
	 * @var int
	 */
	public $num_queries = 0;

	/**
	 * Số hàng được trả về bởi truy vấn cuối cùng.
	 *
	 * @since 0.71
	 *
	 * @var int
	 */
	public $num_rows = 0;

	/**
	 * Số hàng bị ảnh hưởng bởi truy vấn cuối cùng.
	 *
	 * @since 0.71
	 *
	 * @var int
	 */
	public $rows_affected = 0;

	/**
	 * ID được tạo cho cột AUTO_INCREMENT bởi truy vấn cuối cùng (thường là INSERT).
	 *
	 * @since 0.71
	 *
	 * @var int
	 */
	public $insert_id = 0;

	/**
	 * Truy vấn cuối cùng đã thực hiện.
	 *
	 * @since 0.71
	 *
	 * @var string
	 */
	public $last_query;

	/**
	 * Kết quả của truy vấn cuối cùng.
	 *
	 * @since 0.71
	 *
	 * @var stdClass[]|null
	 */
	public $last_result;

	/**
	 * Kết quả truy vấn cơ sở dữ liệu.
	 *
	 * Các giá trị có thể:
	 *
	 * - Đối tượng `mysqli_result` cho các truy vấn SELECT, SHOW, DESCRIBE, hoặc EXPLAIN thành công
	 * - `true` cho các loại truy vấn khác thành công
	 * - `null` nếu chưa thực hiện truy vấn nào hoặc kết quả đã bị xóa
	 * - `false` nếu truy vấn trả về lỗi
	 *
	 * @since 0.71
	 *
	 * @var mysqli_result|bool|null
	 */
	protected $result;

	/**
	 * Thông tin cột được lưu trong bộ nhớ đệm, để kiểm tra độ tin cậy dữ liệu trước khi chèn.
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	protected $col_meta = array();

	/**
	 * Bộ ký tự đã tính toán được đánh khóa theo tên bảng.
	 *
	 * @since 4.2.0
	 *
	 * @var string[]
	 */
	protected $table_charset = array();

	/**
	 * Liệu các trường văn bản trong truy vấn hiện tại có cần được kiểm tra độ tin cậy hay không.
	 *
	 * @since 4.2.0
	 *
	 * @var bool
	 */
	protected $check_current_query = true;

	/**
	 * Cờ để đảm bảo chúng ta không gặp vấn đề đệ quy khi kiểm tra collation.
	 *
	 * @since 4.2.0
	 *
	 * @see wpdb::check_safe_collation()
	 * @var bool
	 */
	private $checking_collation = false;

	/**
	 * Thông tin đã lưu về cột của bảng.
	 *
	 * @since 0.71
	 *
	 * @var array
	 */
	protected $col_info;

	/**
	 * Nhật ký các truy vấn đã được thực thi, cho mục đích gỡ lỗi.
	 *
	 * @since 1.5.0
	 * @since 2.5.0 Phần tử thứ ba trong mỗi nhật ký truy vấn được thêm để ghi lại các hàm gọi.
	 * @since 5.1.0 Phần tử thứ tư trong mỗi nhật ký truy vấn được thêm để ghi lại thời gian bắt đầu.
	 * @since 5.3.0 Phần tử thứ năm trong mỗi nhật ký truy vấn được thêm để ghi lại dữ liệu tùy chỉnh.
	 *
	 * @var array[] {
	 *     Mảng các mảng chứa thông tin về các truy vấn đã thực thi.
	 *
	 *     @type array ...$0 {
	 *         Dữ liệu cho mỗi truy vấn.
	 *
	 *         @type string $0 Câu lệnh SQL của truy vấn.
	 *         @type float  $1 Tổng thời gian thực hiện truy vấn, tính bằng giây.
	 *         @type string $2 Danh sách các hàm gọi, phân cách bởi dấu phẩy.
	 *         @type float  $3 Mốc thời gian Unix tại thời điểm bắt đầu truy vấn.
	 *         @type array  $4 Dữ liệu tùy chỉnh của truy vấn.
	 *     }
	 * }
	 */
	public $queries;

	/**
	 * Số lần thử kết nối lại trước khi dừng. Mặc định 5.
	 *
	 * @since 3.9.0
	 *
	 * @see wpdb::check_connection()
	 * @var int
	 */
	protected $reconnect_retries = 5;

	/**
	 * Tiền tố bảng WordPress.
	 *
	 * Bạn có thể đặt giá trị này để có nhiều bản cài đặt WordPress trong một cơ sở dữ liệu.
	 * Lý do thứ hai là cho các biện pháp bảo mật có thể.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	public $prefix = '';

	/**
	 * Tiền tố bảng cơ sở của WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $base_prefix;

	/**
	 * Liệu các truy vấn cơ sở dữ liệu đã sẵn sàng để bắt đầu thực thi hay chưa.
	 *
	 * @since 2.3.2
	 *
	 * @var bool
	 */
	public $ready = false;

	/**
	 * ID Blog.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public $blogid = 0;

	/**
	 * ID Site.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public $siteid = 0;

	/**
	 * Danh sách các bảng WordPress theo từng site.
	 *
	 * @since 2.5.0
	 *
	 * @see wpdb::tables()
	 * @var string[]
	 */
	public $tables = array(
		'posts',
		'comments',
		'links',
		'options',
		'postmeta',
		'terms',
		'term_taxonomy',
		'term_relationships',
		'termmeta',
		'commentmeta',
	);

	/**
	 * Danh sách các bảng WordPress đã ngừng sử dụng.
	 *
	 * 'categories', 'post2cat', and 'link2cat' were deprecated in 2.3.0, db version 5539.
	 *
	 * @since 2.9.0
	 *
	 * @see wpdb::tables()
	 * @var string[]
	 */
	public $old_tables = array( 'categories', 'post2cat', 'link2cat' );

	/**
	 * Danh sách các bảng toàn cục của WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @see wpdb::tables()
	 * @var string[]
	 */
	public $global_tables = array( 'users', 'usermeta' );

	/**
	 * Danh sách các bảng toàn cục của Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @see wpdb::tables()
	 * @var string[]
	 */
	public $ms_global_tables = array(
		'blogs',
		'blogmeta',
		'signups',
		'site',
		'sitemeta',
		'registration_log',
	);

	/**
	 * Danh sách các bảng toàn cục Multisite của WordPress đã ngừng sử dụng.
	 *
	 * @since 6.1.0
	 *
	 * @see wpdb::tables()
	 * @var string[]
	 */
	public $old_ms_global_tables = array( 'sitecategories' );

	/**
	 * Bảng Bình luận WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $comments;

	/**
	 * Bảng Metadata Bình luận WordPress.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	public $commentmeta;

	/**
	 * Bảng Liên kết WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $links;

	/**
	 * Bảng Tùy chọn WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $options;

	/**
	 * Bảng Metadata Bài viết WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $postmeta;

	/**
	 * Bảng Bài viết WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $posts;

	/**
	 * Bảng Thuật ngữ WordPress.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $terms;

	/**
	 * Bảng Quan hệ Thuật ngữ WordPress.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $term_relationships;

	/**
	 * Bảng Phân loại Thuật ngữ WordPress.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $term_taxonomy;

	/**
	 * Bảng Meta Thuật ngữ WordPress.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $termmeta;

	//
	// Các bảng Toàn cục và Multisite
	//

	/**
	 * Bảng Metadata Người dùng WordPress.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $usermeta;

	/**
	 * Bảng Người dùng WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $users;

	/**
	 * Bảng Blog Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $blogs;

	/**
	 * Bảng Metadata Blog Multisite.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public $blogmeta;

	/**
	 * Bảng Nhật ký Đăng ký Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $registration_log;

	/**
	 * Bảng Đăng ký Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $signups;

	/**
	 * Bảng Site Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $site;

	/**
	 * Bảng Thuật ngữ toàn Site Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $sitecategories;

	/**
	 * Bảng Metadata Site Multisite.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $sitemeta;

	/**
	 * Các ký hiệu định dạng cho cột DB.
	 *
	 * Các cột không được liệt kê ở đây mặc định là %s. Được khởi tạo trong quá trình tải WP.
	 * Khóa là tên cột, giá trị là kiểu định dạng: 'ID' => '%d'.
	 *
	 * @since 2.8.0
	 *
	 * @see wpdb::prepare()
	 * @see wpdb::insert()
	 * @see wpdb::update()
	 * @see wpdb::delete()
	 * @see wp_set_wpdb_vars()
	 * @var array
	 */
	public $field_types = array();

	/**
	 * Bộ ký tự cột bảng cơ sở dữ liệu.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public $charset;

	/**
	 * Collation cột bảng cơ sở dữ liệu.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public $collate;

	/**
	 * Tên người dùng cơ sở dữ liệu.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	protected $dbuser;

	/**
	 * Mật khẩu cơ sở dữ liệu.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $dbpassword;

	/**
	 * Tên cơ sở dữ liệu.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $dbname;

	/**
	 * Máy chủ cơ sở dữ liệu.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $dbhost;

	/**
	 * Handle cơ sở dữ liệu.
	 *
	 * Các giá trị có thể:
	 *
	 * - Đối tượng `mysqli` trong hoạt động bình thường
	 * - `null` nếu kết nối chưa được thiết lập hoặc đã bị đóng
	 * - `false` nếu kết nối đã thất bại
	 *
	 * @since 0.71
	 *
	 * @var mysqli|false|null
	 */
	protected $dbh;

	/**
	 * Mô tả dạng văn bản của lần gọi query/get_row/get_var cuối cùng.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $func_call;

	/**
	 * Liệu MySQL có được sử dụng làm engine cơ sở dữ liệu hay không.
	 *
	 * Được đặt thành true trong wpdb::db_connect(), theo mặc định. Giá trị này được sử dụng khi kiểm tra
	 * phiên bản MySQL yêu cầu cho WordPress. Thông thường, một file drop-in thay thế
	 * cơ sở dữ liệu (db.php) sẽ bỏ qua các kiểm tra này, nhưng đặt giá trị này thành true
	 * sẽ buộc các kiểm tra phải thực hiện.
	 *
	 * @since 3.3.0
	 *
	 * @var bool
	 */
	public $is_mysql = null;

	/**
	 * Danh sách các chế độ SQL không tương thích.
	 *
	 * @since 3.9.0
	 *
	 * @var string[]
	 */
	protected $incompatible_modes = array(
		'NO_ZERO_DATE',
		'ONLY_FULL_GROUP_BY',
		'STRICT_TRANS_TABLES',
		'STRICT_ALL_TABLES',
		'TRADITIONAL',
		'ANSI',
	);

	/**
	 * Tương thích ngược, khi wpdb::prepare() không bọc ngoặc kép cho các placeholder có định dạng/argnum.
	 *
	 * Điều này thường được sử dụng cho tên bảng/trường (trước khi %i được hỗ trợ), và đôi khi cho định dạng chuỗi, ví dụ:
	 *
	 *     $wpdb->prepare( 'WHERE `%1$s` = "%2$s something %3$s" OR %1$s = "%4$-10s"', 'field_1', 'a', 'b', 'c' );
	 *
	 * Nhưng điều này rủi ro, ví dụ quên thêm dấu ngoặc kép, dẫn đến lỗ hổng SQL Injection:
	 *
	 *     $wpdb->prepare( 'WHERE (id = %1s) OR (id = %2$s)', $_GET['id'], $_GET['id'] ); // ?id=id
	 *
	 * Tính năng này được giữ lại trong khi các tác giả plugin cập nhật mã nguồn để sử dụng các cách tiếp cận an toàn hơn:
	 *
	 *     $_GET['key'] = 'a`b';
	 *
	 *     $wpdb->prepare( 'WHERE %1s = %s',        $_GET['key'], $_GET['value'] ); // WHERE a`b = 'value'
	 *     $wpdb->prepare( 'WHERE `%1$s` = "%2$s"', $_GET['key'], $_GET['value'] ); // WHERE `a`b` = "value"
	 *
	 *     $wpdb->prepare( 'WHERE %i = %s',         $_GET['key'], $_GET['value'] ); // WHERE `a``b` = 'value'
	 *
	 * Trong khi chuyển sang false sẽ ổn với các truy vấn không sử dụng placeholder có định dạng/argnum,
	 * các trường hợp còn lại rất có thể sẽ dẫn đến lỗi SQL (tốt, theo một cách nào đó):
	 *
	 *     $wpdb->prepare( 'WHERE %1$s = "%2$-10s"', 'my_field', 'my_value' );
	 *     true  = WHERE my_field = "my_value  "
	 *     false = WHERE 'my_field' = "'my_value  '"
	 *
	 * Nhưng có thể có một số truy vấn dẫn đến lỗ hổng SQL Injection:
	 *
	 *     $wpdb->prepare( 'WHERE id = %1$s', $_GET['id'] ); // ?id=id
	 *
	 * Vì vậy có thể cần một giai đoạn `_doing_it_wrong()`, sau khi biết rằng mọi người có thể sử dụng
	 * placeholder định danh (%i), nhưng trước khi tính năng này bị vô hiệu hóa hoặc loại bỏ.
	 *
	 * @since 6.2.0
	 * @var bool
	 */
	private $allow_unsafe_unquoted_parameters = true;

	/**
	 * Liệu có sử dụng extension mysqli thay cho mysql hay không. Thuộc tính này không còn được sử dụng vì extension mysql
	 * không còn được hỗ trợ.
	 *
	 * Mặc định true.
	 *
	 * @since 3.9.0
	 * @since 6.4.0 Thuộc tính này đã bị loại bỏ.
	 * @since 6.4.1 Thuộc tính này đã được khôi phục và giá trị mặc định được thay đổi thành true.
	 *              Thuộc tính này không còn được sử dụng trong lõi nhưng có thể được truy cập từ bên ngoài.
	 *
	 * @var bool
	 */
	private $use_mysqli = true;

	/**
	 * Liệu chúng ta đã kết nối thành công tại một thời điểm nào đó hay chưa.
	 *
	 * @since 3.9.0
	 *
	 * @var bool
	 */
	private $has_connected = false;

	/**
	 * Thời điểm truy vấn cuối cùng được thực hiện.
	 *
	 * Chỉ được đặt khi `SAVEQUERIES` được định nghĩa và có giá trị truthy.
	 *
	 * @since 1.5.0
	 *
	 * @var float
	 */
	public $time_start = null;

	/**
	 * Lỗi SQL cuối cùng gặp phải.
	 *
	 * @since 2.5.0
	 *
	 * @var WP_Error|string
	 */
	public $error = null;

	/**
	 * Kết nối đến máy chủ cơ sở dữ liệu và chọn cơ sở dữ liệu.
	 *
	 * Thực hiện việc thiết lập thực tế
	 * các thuộc tính của lớp và kết nối đến cơ sở dữ liệu.
	 *
	 * @since 2.0.8
	 *
	 * @link https://core.trac.wordpress.org/ticket/3354
	 *
	 * @param string $dbuser     Tên người dùng cơ sở dữ liệu.
	 * @param string $dbpassword Mật khẩu cơ sở dữ liệu.
	 * @param string $dbname     Tên cơ sở dữ liệu.
	 * @param string $dbhost     Máy chủ cơ sở dữ liệu.
	 */
	public function __construct(
		$dbuser,
		#[\SensitiveParameter]
		$dbpassword,
		$dbname,
		$dbhost
	) {
		if ( WP_DEBUG && WP_DEBUG_DISPLAY ) {
			$this->show_errors();
		}

		$this->dbuser     = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname     = $dbname;
		$this->dbhost     = $dbhost;

		// Quá trình tạo wp-config.php sẽ kết nối thủ công khi sẵn sàng.
		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return;
		}

		$this->db_connect();
	}

	/**
	 * Cho phép đọc các thuộc tính private để tương thích ngược.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name Thành viên private cần lấy, và tùy chọn xử lý.
	 * @return mixed Thành viên private.
	 */
	public function __get( $name ) {
		if ( 'col_info' === $name ) {
			$this->load_col_info();
		}

		return $this->$name;
	}

	/**
	 * Cho phép gán giá trị cho các thuộc tính private để tương thích ngược.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  Thành viên private cần gán.
	 * @param mixed  $value Giá trị cần gán.
	 */
	public function __set( $name, $value ) {
		$protected_members = array(
			'col_meta',
			'table_charset',
			'check_current_query',
			'allow_unsafe_unquoted_parameters',
		);
		if ( in_array( $name, $protected_members, true ) ) {
			return;
		}
		$this->$name = $value;
	}

	/**
	 * Cho phép kiểm tra sự tồn tại của các thuộc tính private để tương thích ngược.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name Thành viên private cần kiểm tra.
	 * @return bool Liệu thành viên đã được gán hay chưa.
	 */
	public function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Cho phép hủy gán các thuộc tính private để tương thích ngược.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  Thành viên private cần hủy gán.
	 */
	public function __unset( $name ) {
		unset( $this->$name );
	}

	/**
	 * Đặt giá trị cho $this->charset và $this->collate.
	 *
	 * @since 3.1.0
	 */
	public function init_charset() {
		$charset = '';
		$collate = '';

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$charset = 'utf8';
			if ( defined( 'DB_COLLATE' ) && DB_COLLATE ) {
				$collate = DB_COLLATE;
			} else {
				$collate = 'utf8_general_ci';
			}
		} elseif ( defined( 'DB_COLLATE' ) ) {
			$collate = DB_COLLATE;
		}

		if ( defined( 'DB_CHARSET' ) ) {
			$charset = DB_CHARSET;
		}

		$charset_collate = $this->determine_charset( $charset, $collate );

		$this->charset = $charset_collate['charset'];
		$this->collate = $charset_collate['collate'];
	}

	/**
	 * Xác định bộ ký tự và collation tốt nhất để sử dụng dựa trên bộ ký tự và collation cho trước.
	 *
	 * Ví dụ, khi có thể, utf8mb4 nên được sử dụng thay vì utf8.
	 *
	 * @since 4.6.0
	 *
	 * @param string $charset Bộ ký tự cần kiểm tra.
	 * @param string $collate Collation cần kiểm tra.
	 * @return array {
	 *     Bộ ký tự và collation phù hợp nhất để sử dụng.
	 *
	 *     @type string $charset Bộ ký tự.
	 *     @type string $collate Collation.
	 * }
	 */
	public function determine_charset( $charset, $collate ) {
		if ( ( ! ( $this->dbh instanceof mysqli ) ) || empty( $this->dbh ) ) {
			return compact( 'charset', 'collate' );
		}

		if ( 'utf8' === $charset ) {
			$charset = 'utf8mb4';
		}

		if ( 'utf8mb4' === $charset ) {
			// _general_ đã lỗi thời, nên chúng ta có thể nâng cấp lên _unicode_ thay thế.
			if ( ! $collate || 'utf8_general_ci' === $collate ) {
				$collate = 'utf8mb4_unicode_ci';
			} else {
				$collate = str_replace( 'utf8_', 'utf8mb4_', $collate );
			}
		}

		// _unicode_520_ là collation tốt hơn, chúng ta nên sử dụng nó khi có sẵn.
		if ( $this->has_cap( 'utf8mb4_520' ) && 'utf8mb4_unicode_ci' === $collate ) {
			$collate = 'utf8mb4_unicode_520_ci';
		}

		return compact( 'charset', 'collate' );
	}

	/**
	 * Đặt bộ ký tự cho kết nối.
	 *
	 * @since 3.1.0
	 *
	 * @param mysqli $dbh     Kết nối được trả về bởi `mysqli_connect()`.
	 * @param string $charset Tùy chọn. Bộ ký tự. Mặc định null.
	 * @param string $collate Tùy chọn. Collation. Mặc định null.
	 */
	public function set_charset( $dbh, $charset = null, $collate = null ) {
		if ( ! isset( $charset ) ) {
			$charset = $this->charset;
		}
		if ( ! isset( $collate ) ) {
			$collate = $this->collate;
		}
		if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
			$set_charset_succeeded = true;

			if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
				$set_charset_succeeded = mysqli_set_charset( $dbh, $charset );
			}

			if ( $set_charset_succeeded ) {
				$query = $this->prepare( 'SET NAMES %s', $charset );
				if ( ! empty( $collate ) ) {
					$query .= $this->prepare( ' COLLATE %s', $collate );
				}
				mysqli_query( $dbh, $query );
			}
		}
	}

	/**
	 * Thay đổi chế độ SQL hiện tại, và đảm bảo tính tương thích với WordPress.
	 *
	 * Nếu không truyền chế độ nào, hàm sẽ đảm bảo các chế độ máy chủ MySQL hiện tại là tương thích.
	 *
	 * @since 3.9.0
	 *
	 * @param array $modes Tùy chọn. Danh sách các chế độ SQL cần đặt. Mặc định mảng rỗng.
	 */
	public function set_sql_mode( $modes = array() ) {
		if ( empty( $modes ) ) {
			$res = mysqli_query( $this->dbh, 'SELECT @@SESSION.sql_mode' );

			if ( empty( $res ) ) {
				return;
			}

			$modes_array = mysqli_fetch_array( $res );

			if ( empty( $modes_array[0] ) ) {
				return;
			}

			$modes_str = $modes_array[0];

			if ( empty( $modes_str ) ) {
				return;
			}

			$modes = explode( ',', $modes_str );
		}

		$modes = array_change_key_case( $modes, CASE_UPPER );

		/**
		 * Lọc danh sách các chế độ SQL không tương thích cần loại trừ.
		 *
		 * @since 3.9.0
		 *
		 * @param array $incompatible_modes Mảng các chế độ không tương thích.
		 */
		$incompatible_modes = (array) apply_filters( 'incompatible_sql_modes', $this->incompatible_modes );

		foreach ( $modes as $i => $mode ) {
			if ( in_array( $mode, $incompatible_modes, true ) ) {
				unset( $modes[ $i ] );
			}
		}

		$modes_str = implode( ',', $modes );

		mysqli_query( $this->dbh, "SET SESSION sql_mode='$modes_str'" );
	}

	/**
	 * Đặt tiền tố bảng cho các bảng WordPress.
	 *
	 * @since 2.5.0
	 *
	 * @param string $prefix          Tên chữ-số cho tiền tố mới.
	 * @param bool   $set_table_names Tùy chọn. Liệu tên các bảng, ví dụ wpdb::$posts,
	 *                                có nên được cập nhật hay không. Mặc định true.
	 * @return string|WP_Error Tiền tố cũ hoặc WP_Error khi có lỗi.
	 */
	public function set_prefix( $prefix, $set_table_names = true ) {

		if ( preg_match( '|[^a-z0-9_]|i', $prefix ) ) {
			return new WP_Error( 'invalid_db_prefix', 'Invalid database prefix' );
		}

		$old_prefix = is_multisite() ? '' : $prefix;

		if ( isset( $this->base_prefix ) ) {
			$old_prefix = $this->base_prefix;
		}

		$this->base_prefix = $prefix;

		if ( $set_table_names ) {
			foreach ( $this->tables( 'global' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}

			if ( is_multisite() && empty( $this->blogid ) ) {
				return $old_prefix;
			}

			$this->prefix = $this->get_blog_prefix();

			foreach ( $this->tables( 'blog' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}

			foreach ( $this->tables( 'old' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}
		}
		return $old_prefix;
	}

	/**
	 * Đặt ID blog.
	 *
	 * @since 3.0.0
	 *
	 * @param int $blog_id
	 * @param int $network_id Tùy chọn. ID mạng. Mặc định 0.
	 * @return int ID blog trước đó.
	 */
	public function set_blog_id( $blog_id, $network_id = 0 ) {
		if ( ! empty( $network_id ) ) {
			$this->siteid = $network_id;
		}

		$old_blog_id  = $this->blogid;
		$this->blogid = $blog_id;

		$this->prefix = $this->get_blog_prefix();

		foreach ( $this->tables( 'blog' ) as $table => $prefixed_table ) {
			$this->$table = $prefixed_table;
		}

		foreach ( $this->tables( 'old' ) as $table => $prefixed_table ) {
			$this->$table = $prefixed_table;
		}

		return $old_blog_id;
	}

	/**
	 * Lấy tiền tố blog.
	 *
	 * @since 3.0.0
	 *
	 * @param int $blog_id Tùy chọn. ID blog để lấy tiền tố bảng.
	 *                     Mặc định là ID blog hiện tại.
	 * @return string Tiền tố blog.
	 */
	public function get_blog_prefix( $blog_id = null ) {
		if ( is_multisite() ) {
			if ( null === $blog_id ) {
				$blog_id = $this->blogid;
			}

			$blog_id = (int) $blog_id;

			if ( defined( 'MULTISITE' ) && ( 0 === $blog_id || 1 === $blog_id ) ) {
				return $this->base_prefix;
			} else {
				return $this->base_prefix . $blog_id . '_';
			}
		} else {
			return $this->base_prefix;
		}
	}

	/**
	 * Trả về một mảng các bảng WordPress.
	 *
	 * Cũng cho phép `CUSTOM_USER_TABLE` và `CUSTOM_USER_META_TABLE` ghi đè các bảng users
	 * và usermeta của WordPress mà thường được xác định bởi tiền tố.
	 *
	 * Tham số `$scope` có thể nhận một trong các giá trị sau:
	 *
	 * - 'all' - trả về các bảng 'all' và 'global'. Không trả về các bảng cũ.
	 * - 'blog' - trả về các bảng cấp blog cho blog được truy vấn.
	 * - 'global' - trả về các bảng toàn cục cho bản cài đặt, chỉ trả về bảng multisite trên multisite.
	 * - 'ms_global' - trả về các bảng toàn cục multisite, bất kể bản cài đặt hiện tại có phải multisite hay không.
	 * - 'old' - trả về các bảng đã ngừng sử dụng.
	 *
	 * @since 3.0.0
	 * @since 6.1.0 `old` giờ bao gồm các bảng toàn cục multisite đã ngừng sử dụng chỉ trên multisite.
	 *
	 * @uses wpdb::$tables
	 * @uses wpdb::$old_tables
	 * @uses wpdb::$global_tables
	 * @uses wpdb::$ms_global_tables
	 * @uses wpdb::$old_ms_global_tables
	 *
	 * @param string $scope   Tùy chọn. Các giá trị có thể bao gồm 'all', 'global', 'ms_global', 'blog',
	 *                        hoặc 'old'. Mặc định 'all'.
	 * @param bool   $prefix  Tùy chọn. Liệu có bao gồm tiền tố bảng hay không. Nếu yêu cầu tiền tố blog,
	 *                        thì các bảng users và usermeta tùy chỉnh sẽ được ánh xạ. Mặc định true.
	 * @param int    $blog_id Tùy chọn. blog_id để thêm tiền tố. Chỉ sử dụng khi yêu cầu tiền tố.
	 *                        Mặc định là `wpdb::$blogid`.
	 * @return string[] Tên các bảng. Khi yêu cầu tiền tố, khóa là tên bảng không có tiền tố.
	 */
	public function tables( $scope = 'all', $prefix = true, $blog_id = 0 ) {
		switch ( $scope ) {
			case 'all':
				$tables = array_merge( $this->global_tables, $this->tables );
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}
				break;
			case 'blog':
				$tables = $this->tables;
				break;
			case 'global':
				$tables = $this->global_tables;
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}
				break;
			case 'ms_global':
				$tables = $this->ms_global_tables;
				break;
			case 'old':
				$tables = $this->old_tables;
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->old_ms_global_tables );
				}
				break;
			default:
				return array();
		}

		if ( $prefix ) {
			if ( ! $blog_id ) {
				$blog_id = $this->blogid;
			}
			$blog_prefix   = $this->get_blog_prefix( $blog_id );
			$base_prefix   = $this->base_prefix;
			$global_tables = array_merge( $this->global_tables, $this->ms_global_tables );
			foreach ( $tables as $k => $table ) {
				if ( in_array( $table, $global_tables, true ) ) {
					$tables[ $table ] = $base_prefix . $table;
				} else {
					$tables[ $table ] = $blog_prefix . $table;
				}
				unset( $tables[ $k ] );
			}

			if ( isset( $tables['users'] ) && defined( 'CUSTOM_USER_TABLE' ) ) {
				$tables['users'] = CUSTOM_USER_TABLE;
			}

			if ( isset( $tables['usermeta'] ) && defined( 'CUSTOM_USER_META_TABLE' ) ) {
				$tables['usermeta'] = CUSTOM_USER_META_TABLE;
			}
		}

		return $tables;
	}

	/**
	 * Chọn cơ sở dữ liệu sử dụng kết nối cơ sở dữ liệu hiện tại hoặc được cung cấp.
	 *
	 * Tên cơ sở dữ liệu sẽ được thay đổi dựa trên kết nối cơ sở dữ liệu hiện tại.
	 * Khi thất bại, quá trình thực thi sẽ dừng lại và hiển thị lỗi DB.
	 *
	 * @since 0.71
	 *
	 * @param string $db  Tên cơ sở dữ liệu.
	 * @param mysqli $dbh Tùy chọn. Kết nối cơ sở dữ liệu.
	 *                    Mặc định là handle cơ sở dữ liệu hiện tại.
	 */
	public function select( $db, $dbh = null ) {
		if ( is_null( $dbh ) ) {
			$dbh = $this->dbh;
		}

		$success = mysqli_select_db( $dbh, $db );

		if ( ! $success ) {
			$this->ready = false;
			if ( ! did_action( 'template_redirect' ) ) {
				wp_load_translations_early();

				$message = '<h1>' . __( 'Cannot select database' ) . "</h1>\n";

				$message .= '<p>' . sprintf(
					/* translators: %s: Database name. */
					__( 'The database server could be connected to (which means your username and password is okay) but the %s database could not be selected.' ),
					'<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>'
				) . "</p>\n";

				$message .= "<ul>\n";
				$message .= '<li>' . __( 'Are you sure it exists?' ) . "</li>\n";

				$message .= '<li>' . sprintf(
					/* translators: 1: Database user, 2: Database name. */
					__( 'Does the user %1$s have permission to use the %2$s database?' ),
					'<code>' . htmlspecialchars( $this->dbuser, ENT_QUOTES ) . '</code>',
					'<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>'
				) . "</li>\n";

				$message .= '<li>' . sprintf(
					/* translators: %s: Database name. */
					__( 'On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?' ),
					htmlspecialchars( $db, ENT_QUOTES )
				) . "</li>\n";

				$message .= "</ul>\n";

				$message .= '<p>' . sprintf(
					/* translators: %s: Support forums URL. */
					__( 'If you do not know how to set up a database you should <strong>contact your host</strong>. If all else fails you may find help at the <a href="%s">WordPress support forums</a>.' ),
					__( 'https://wordpress.org/support/forums/' )
				) . "</p>\n";

				$this->bail( $message, 'db_select_fail' );
			}
		}
	}

	/**
	 * Không sử dụng, đã ngừng hỗ trợ.
	 *
	 * Sử dụng esc_sql() hoặc wpdb::prepare() thay thế.
	 *
	 * @since 2.8.0
	 * @deprecated 3.6.0 Sử dụng wpdb::prepare()
	 * @see wpdb::prepare()
	 * @see esc_sql()
	 *
	 * @param string $data
	 * @return string
	 */
	public function _weak_escape( $data ) {
		if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __METHOD__, '3.6.0', 'wpdb::prepare() or esc_sql()' );
		}
		return addslashes( $data );
	}

	/**
	 * Escape thực sự sử dụng mysqli_real_escape_string().
	 *
	 * @since 2.8.0
	 *
	 * @see mysqli_real_escape_string()
	 *
	 * @param string $data Chuỗi cần escape.
	 * @return string Chuỗi đã được escape.
	 */
	public function _real_escape( $data ) {
		if ( ! is_scalar( $data ) ) {
			return '';
		}

		if ( $this->dbh ) {
			$escaped = mysqli_real_escape_string( $this->dbh, $data );
		} else {
			$class = get_class( $this );

			wp_load_translations_early();
			/* translators: %s: Database access abstraction class, usually wpdb or a class extending wpdb. */
			_doing_it_wrong( $class, sprintf( __( '%s must set a database connection for use with escaping.' ), $class ), '3.6.0' );

			$escaped = addslashes( $data );
		}

		return $this->add_placeholder_escape( $escaped );
	}

	/**
	 * Escape dữ liệu. Hoạt động với mảng.
	 *
	 * @since 2.8.0
	 *
	 * @uses wpdb::_real_escape()
	 *
	 * @param string|array $data Dữ liệu cần escape.
	 * @return string|array Dữ liệu đã được escape, cùng kiểu với dữ liệu đầu vào.
	 */
	public function _escape( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[ $k ] = $this->_escape( $v );
				} else {
					$data[ $k ] = $this->_real_escape( $v );
				}
			}
		} else {
			$data = $this->_real_escape( $data );
		}

		return $data;
	}

	/**
	 * Không sử dụng, đã ngừng hỗ trợ.
	 *
	 * Sử dụng esc_sql() hoặc wpdb::prepare() thay thế.
	 *
	 * @since 0.71
	 * @deprecated 3.6.0 Sử dụng wpdb::prepare()
	 * @see wpdb::prepare()
	 * @see esc_sql()
	 *
	 * @param string|array $data Dữ liệu cần escape.
	 * @return string|array Dữ liệu đã được escape, cùng kiểu với dữ liệu đầu vào.
	 */
	public function escape( $data ) {
		if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __METHOD__, '3.6.0', 'wpdb::prepare() or esc_sql()' );
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[ $k ] = $this->escape( $v, 'recursive' );
				} else {
					$data[ $k ] = $this->_weak_escape( $v, 'internal' );
				}
			}
		} else {
			$data = $this->_weak_escape( $data, 'internal' );
		}

		return $data;
	}

	/**
	 * Escape nội dung theo tham chiếu để chèn vào cơ sở dữ liệu, vì mục đích bảo mật.
	 *
	 * @uses wpdb::_real_escape()
	 *
	 * @since 2.3.0
	 *
	 * @param string $data Chuỗi cần escape.
	 */
	public function escape_by_ref( &$data ) {
		if ( ! is_float( $data ) ) {
			$data = $this->_real_escape( $data );
		}
	}

	/**
	 * Bọc ngoặc một định danh cho cơ sở dữ liệu MySQL, ví dụ tên bảng/trường.
	 *
	 * @since 6.2.0
	 *
	 * @param string $identifier Định danh cần escape.
	 * @return string Định danh đã được escape.
	 */
	public function quote_identifier( $identifier ) {
		return '`' . $this->_escape_identifier_value( $identifier ) . '`';
	}

	/**
	 * Escape giá trị định danh mà không thêm dấu ngoặc bao quanh.
	 *
	 * - Các ký tự được phép trong định danh có ngoặc bao gồm toàn bộ Unicode
	 *   Basic Multilingual Plane (BMP), ngoại trừ U+0000.
	 * - Để bọc ngoặc chính định danh, bạn cần nhân đôi ký tự, ví dụ `a``b`.
	 *
	 * @since 6.2.0
	 *
	 * @link https://dev.mysql.com/doc/refman/8.0/en/identifiers.html
	 *
	 * @param string $identifier Định danh cần escape.
	 * @return string Định danh đã được escape.
	 */
	private function _escape_identifier_value( $identifier ) {
		return str_replace( '`', '``', $identifier );
	}

	/**
	 * Chuẩn bị một truy vấn SQL để thực thi an toàn.
	 *
	 * Sử dụng cú pháp tương tự `sprintf()`. Các placeholder sau có thể được sử dụng trong chuỗi truy vấn:
	 *
	 * - `%d` (số nguyên)
	 * - `%f` (số thực)
	 * - `%s` (chuỗi)
	 * - `%i` (định danh, ví dụ tên bảng/trường)
	 *
	 * Tất cả các placeholder PHẢI được để không có dấu ngoặc kép trong chuỗi truy vấn. Một đối số tương ứng
	 * PHẢI được truyền cho mỗi placeholder.
	 *
	 * Lưu ý: Có một ngoại lệ cho quy tắc trên: để tương thích ngược,
	 * các placeholder chuỗi có đánh số hoặc định dạng (ví dụ `%1$s`, `%5s`) sẽ không được thêm dấu ngoặc kép
	 * bởi hàm này, nên cần được truyền kèm dấu ngoặc kép phù hợp bao quanh.
	 *
	 * Ký tự phần trăm (`%`) trong chuỗi truy vấn phải được viết dưới dạng `%%`. Các ký tự đại diện phần trăm
	 * (ví dụ, để sử dụng trong cú pháp LIKE) phải được truyền qua đối số thay thế chứa
	 * chuỗi LIKE hoàn chỉnh, chúng không thể được chèn trực tiếp vào chuỗi truy vấn.
	 * Xem thêm wpdb::esc_like().
	 *
	 * Các đối số có thể được truyền riêng lẻ cho phương thức, hoặc dưới dạng một mảng
	 * chứa tất cả đối số. Không hỗ trợ kết hợp cả hai cách.
	 *
	 * Ví dụ:
	 *
	 *     $wpdb->prepare(
	 *         "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d OR `other_field` LIKE %s",
	 *         array( 'foo', 1337, '%bar' )
	 *     );
	 *
	 *     $wpdb->prepare(
	 *         "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s",
	 *         'foo'
	 *     );
	 *
	 * @since 2.3.0
	 * @since 5.3.0 Chính thức hóa tham số `...$args` đã có và đã được tài liệu hóa
	 *              bằng cách cập nhật chữ ký hàm. Tham số thứ hai được đổi
	 *              từ `$args` thành `...$args`.
	 * @since 6.2.0 Thêm `%i` cho định danh, ví dụ tên bảng hoặc trường.
	 *              Kiểm tra hỗ trợ qua `wpdb::has_cap( 'identifier_placeholders' )`.
	 *              Điều này giữ tương thích với `sprintf()`, vì phiên bản C sử dụng
	 *              `%d` và `$i` cho số nguyên có dấu, trong khi PHP chỉ hỗ trợ `%d`.
	 *
	 * @link https://www.php.net/sprintf Mô tả cú pháp.
	 *
	 * @param string      $query   Câu lệnh truy vấn với các placeholder kiểu `sprintf()`.
	 * @param array|mixed $args    Mảng các biến để thay thế vào các placeholder của truy vấn
	 *                             nếu được gọi với mảng đối số, hoặc biến đầu tiên
	 *                             để thay thế vào placeholder nếu được gọi với
	 *                             các đối số riêng lẻ.
	 * @param mixed       ...$args Các biến tiếp theo để thay thế vào các placeholder của truy vấn
	 *                             nếu được gọi với các đối số riêng lẻ.
	 * @return string|void Chuỗi truy vấn đã được làm sạch, nếu có truy vấn cần chuẩn bị.
	 */
	public function prepare( $query, ...$args ) {
		if ( is_null( $query ) ) {
			return;
		}

		/*
		 * Đây không nhằm mục đích chống lỗi hoàn toàn -- nhưng sẽ phát hiện được các cách sử dụng sai rõ ràng.
		 *
		 * Lưu ý: str_contains() không được sử dụng ở đây, vì file này có thể được include
		 * trực tiếp bên ngoài lõi WordPress, ví dụ bởi HyperDB, trong trường hợp đó
		 * các polyfill từ wp-includes/compat.php không được tải.
		 */
		if ( false === strpos( $query, '%' ) ) {
			wp_load_translations_early();
			_doing_it_wrong(
				'wpdb::prepare',
				sprintf(
					/* translators: %s: wpdb::prepare() */
					__( 'The query argument of %s must have a placeholder.' ),
					'wpdb::prepare()'
				),
				'3.9.0'
			);
		}

		/*
		 * Chỉ định các định dạng được phép trong placeholder. Các định dạng sau được cho phép:
		 *
		 * - Chỉ định dấu, ví dụ $+d
		 * - Placeholder có đánh số, ví dụ %1$s
		 * - Chỉ định đệm, bao gồm ký tự đệm tùy chỉnh, ví dụ %05s, %'#5s
		 * - Chỉ định căn chỉnh, ví dụ %05-s
		 * - Chỉ định độ chính xác, ví dụ %.2f
		 */
		$allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

		/*
		 * Nếu placeholder %s đã có dấu ngoặc kép bao quanh, việc xóa dấu ngoặc kép hiện có
		 * và chèn lại đảm bảo dấu ngoặc kép nhất quán.
		 *
		 * Để tương thích ngược, điều này chỉ áp dụng cho %s, không áp dụng cho các placeholder như %1$s,
		 * thường được sử dụng ở giữa các chuỗi dài hơn, hoặc làm placeholder tên bảng.
		 */
		$query = str_replace( "'%s'", '%s', $query ); // Loại bỏ dấu ngoặc đơn hiện có.
		$query = str_replace( '"%s"', '%s', $query ); // Loại bỏ dấu ngoặc kép hiện có.

		// Escape các ký tự phần trăm chưa được escape (tức là bất kỳ thứ gì không được nhận dạng).
		$query = preg_replace( "/%(?:%|$|(?!($allowed_format)?[sdfFi]))/", '%%\\1', $query );

		// Trích xuất các placeholder từ truy vấn.
		$split_query = preg_split( "/(^|[^%]|(?:%%)+)(%(?:$allowed_format)?[sdfFi])/", $query, -1, PREG_SPLIT_DELIM_CAPTURE );

		$split_query_count = count( $split_query );

		/*
		 * Split luôn trả về 1 giá trị trước placeholder đầu tiên (kể cả khi $query = "%s"),
		 * sau đó 3 giá trị bổ sung cho mỗi placeholder.
		 */
		$placeholder_count = ( ( $split_query_count - 1 ) / 3 );

		// Nếu các đối số được truyền dưới dạng mảng, như trong vsprintf(), di chuyển chúng lên.
		$passed_as_array = ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) );
		if ( $passed_as_array ) {
			$args = $args[0];
		}

		$new_query       = '';
		$key             = 2; // Khóa 0 và 1 trong $split_query chứa các giá trị trước placeholder đầu tiên.
		$arg_id          = 0;
		$arg_identifiers = array();
		$arg_strings     = array();

		while ( $key < $split_query_count ) {
			$placeholder = $split_query[ $key ];

			$format = substr( $placeholder, 1, -1 );
			$type   = substr( $placeholder, -1 );

			if ( 'f' === $type && true === $this->allow_unsafe_unquoted_parameters
				/*
				 * Lưu ý: str_ends_with() không được sử dụng ở đây, vì file này có thể được include
				 * trực tiếp bên ngoài lõi WordPress, ví dụ bởi HyperDB, trong trường hợp đó
				 * các polyfill từ wp-includes/compat.php không được tải.
				 */
				&& '%' === substr( $split_query[ $key - 1 ], -1, 1 )
			) {

				/*
				 * Trước WP 6.2, RegEx "buộc số thực không phụ thuộc locale" không
				 * chuyển đổi "%%%f" thành "%%%F" (lưu ý chữ F viết hoa).
				 * Điều này là do nó không kiểm tra xem "%" đứng đầu đã được escape chưa.
				 * Và vì RegEx "Escape các phần trăm chưa escape" sử dụng "[sdF]" trong
				 * phép khẳng định phủ định nhìn trước, khi có số lẻ "%", nó thêm
				 * một "%" bổ sung, cho ra "%%%%f" đã escape đầy đủ (không phải placeholder).
				 */

				$s = $split_query[ $key - 2 ] . $split_query[ $key - 1 ];
				$k = 1;
				$l = strlen( $s );
				while ( $k <= $l && '%' === $s[ $l - $k ] ) {
					++$k;
				}

				$placeholder = '%' . ( $k % 2 ? '%' : '' ) . $format . $type;

				--$placeholder_count;

			} else {

				// Buộc số thực không phụ thuộc locale.
				if ( 'f' === $type ) {
					$type        = 'F';
					$placeholder = '%' . $format . $type;
				}

				if ( 'i' === $type ) {
					$placeholder = '`%' . $format . 's`';
					// Sử dụng strpos() đơn giản do đã kiểm tra trước đó (ví dụ $allowed_format).
					$argnum_pos = strpos( $format, '$' );

					if ( false !== $argnum_pos ) {
						// sprintf() argnum bắt đầu từ 1, $arg_id từ 0.
						$arg_identifiers[] = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
					} else {
						$arg_identifiers[] = $arg_id;
					}
				} elseif ( 'd' !== $type && 'F' !== $type ) {
					/*
					 * Tức là ( 's' === $type ), trong đó 'd' và 'F' giữ nguyên $placeholder,
					 * và chúng ta đảm bảo escape chuỗi được sử dụng làm mặc định an toàn (ví dụ kể cả khi là 'x').
					 */
					$argnum_pos = strpos( $format, '$' );

					if ( false !== $argnum_pos ) {
						$arg_strings[] = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
					} else {
						$arg_strings[] = $arg_id;
					}

					/*
					 * Chuỗi không có dấu ngoặc kép để tương thích ngược (nguy hiểm).
					 * Thứ nhất, "các placeholder chuỗi có đánh số hoặc định dạng (ví dụ %1$s, %5s)".
					 * Thứ hai, nếu "%s" có "%" đứng trước, kể cả khi không liên quan (ví dụ "LIKE '%%%s%%'").
					 */
					if ( true !== $this->allow_unsafe_unquoted_parameters
						/*
						 * Lưu ý: str_ends_with() không được sử dụng ở đây, vì file này có thể được include
						 * trực tiếp bên ngoài lõi WordPress, ví dụ bởi HyperDB, trong trường hợp đó
						 * các polyfill từ wp-includes/compat.php không được tải.
						 */
						|| ( '' === $format && '%' !== substr( $split_query[ $key - 1 ], -1, 1 ) )
					) {
						$placeholder = "'%" . $format . "s'";
					}
				}
			}

			// Nối (-2), các ký tự đứng đầu (-1), sau đó $placeholder mới.
			$new_query .= $split_query[ $key - 2 ] . $split_query[ $key - 1 ] . $placeholder;

			$key += 3;
			++$arg_id;
		}

		// Thay thế $query; và thêm các ký tự $query còn lại, hoặc chỉ mục 0 nếu không có placeholder.
		$query = $new_query . $split_query[ $key - 2 ];

		$dual_use = array_intersect( $arg_identifiers, $arg_strings );

		if ( count( $dual_use ) > 0 ) {
			wp_load_translations_early();

			$used_placeholders = array();

			$key    = 2;
			$arg_id = 0;
			// Phân tích lại (chỉ sử dụng khi có lỗi).
			while ( $key < $split_query_count ) {
				$placeholder = $split_query[ $key ];

				$format = substr( $placeholder, 1, -1 );

				$argnum_pos = strpos( $format, '$' );

				if ( false !== $argnum_pos ) {
					$arg_pos = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
				} else {
					$arg_pos = $arg_id;
				}

				$used_placeholders[ $arg_pos ][] = $placeholder;

				$key += 3;
				++$arg_id;
			}

			$conflicts = array();
			foreach ( $dual_use as $arg_pos ) {
				$conflicts[] = implode( ' and ', $used_placeholders[ $arg_pos ] );
			}

			_doing_it_wrong(
				'wpdb::prepare',
				sprintf(
					/* translators: %s: A list of placeholders found to be a problem. */
					__( 'Arguments cannot be prepared as both an Identifier and Value. Found the following conflicts: %s' ),
					implode( ', ', $conflicts )
				),
				'6.2.0'
			);

			return;
		}

		$args_count = count( $args );

		if ( $args_count !== $placeholder_count ) {
			if ( 1 === $placeholder_count && $passed_as_array ) {
				/*
				 * Nếu truy vấn được truyền chỉ mong đợi một đối số,
				 * nhưng số lượng đối số sai được gửi dưới dạng mảng, thoát.
				 */
				wp_load_translations_early();
				_doing_it_wrong(
					'wpdb::prepare',
					__( 'The query only expected one placeholder, but an array of multiple placeholders was sent.' ),
					'4.9.0'
				);

				return;
			} else {
				/*
				 * Nếu không có đúng số lượng placeholder,
				 * nhưng chúng được truyền dưới dạng đối số riêng lẻ,
				 * hoặc chúng ta mong đợi nhiều đối số trong một mảng, phát cảnh báo.
				 */
				wp_load_translations_early();
				_doing_it_wrong(
					'wpdb::prepare',
					sprintf(
						/* translators: 1: Number of placeholders, 2: Number of arguments passed. */
						__( 'The query does not contain the correct number of placeholders (%1$d) for the number of arguments passed (%2$d).' ),
						$placeholder_count,
						$args_count
					),
					'4.8.3'
				);

				/*
				 * Nếu không có đủ đối số để khớp với các placeholder,
				 * trả về chuỗi rỗng để tránh lỗi nghiêm trọng trên PHP 8.
				 */
				if ( $args_count < $placeholder_count ) {
					$max_numbered_placeholder = 0;

					for ( $i = 2, $l = $split_query_count; $i < $l; $i += 3 ) {
						// Giả định số đứng đầu là cho placeholder có đánh số, ví dụ '%3$s'.
						$argnum = (int) substr( $split_query[ $i ], 1 );

						if ( $max_numbered_placeholder < $argnum ) {
							$max_numbered_placeholder = $argnum;
						}
					}

					if ( ! $max_numbered_placeholder || $args_count < $max_numbered_placeholder ) {
						return '';
					}
				}
			}
		}

		$args_escaped = array();

		foreach ( $args as $i => $value ) {
			if ( in_array( $i, $arg_identifiers, true ) ) {
				$args_escaped[] = $this->_escape_identifier_value( $value );
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$args_escaped[] = $value;
			} else {
				if ( ! is_scalar( $value ) && ! is_null( $value ) ) {
					wp_load_translations_early();
					_doing_it_wrong(
						'wpdb::prepare',
						sprintf(
							/* translators: %s: Value type. */
							__( 'Unsupported value type (%s).' ),
							gettype( $value )
						),
						'4.8.2'
					);

					// Giữ lại hành vi cũ, trong đó các giá trị được escape dưới dạng chuỗi.
					$value = '';
				}

				$args_escaped[] = $this->_real_escape( $value );
			}
		}

		$query = vsprintf( $query, $args_escaped );

		return $this->add_placeholder_escape( $query );
	}

	/**
	 * Nửa đầu của quá trình escape các ký tự đặc biệt `%` và `_` của `LIKE` trước khi chuẩn bị SQL.
	 *
	 * Chỉ sử dụng trước wpdb::prepare() hoặc esc_sql(). Đảo ngược thứ tự rất nguy hiểm cho bảo mật.
	 *
	 * Ví dụ Prepared Statement:
	 *
	 *     $wild = '%';
	 *     $find = 'only 43% of planets';
	 *     $like = $wild . $wpdb->esc_like( $find ) . $wild;
	 *     $sql  = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_content LIKE %s", $like );
	 *
	 * Ví dụ chuỗi Escape:
	 *
	 *     $sql  = esc_sql( $wpdb->esc_like( $input ) );
	 *
	 * @since 4.0.0
	 *
	 * @param string $text Văn bản thô cần escape. Dữ liệu đầu vào do người dùng nhập
	 *                     không nên có thêm hoặc bị xóa dấu gạch chéo ngược.
	 * @return string Văn bản dưới dạng cụm từ LIKE. Đầu ra chưa an toàn cho SQL.
	 *                Gọi wpdb::prepare() hoặc wpdb::_real_escape() tiếp theo.
	 */
	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	/**
	 * Hiển thị lỗi SQL/DB.
	 *
	 * @since 0.71
	 *
	 * @global array $EZSQL_ERROR Lưu trữ thông tin lỗi của truy vấn và chuỗi lỗi.
	 *
	 * @param string $str Lỗi cần hiển thị.
	 * @return void|false Void nếu hiển thị lỗi được bật, false nếu bị tắt.
	 */
	public function print_error( $str = '' ) {
		global $EZSQL_ERROR;

		if ( ! $str ) {
			$str = mysqli_error( $this->dbh );
		}

		$EZSQL_ERROR[] = array(
			'query'     => $this->last_query,
			'error_str' => $str,
		);

		if ( $this->suppress_errors ) {
			return false;
		}

		$caller = $this->get_caller();
		if ( $caller ) {
			// Không dịch, vì nội dung này chỉ xuất hiện trong nhật ký lỗi.
			$error_str = sprintf( 'WordPress database error %1$s for query %2$s made by %3$s', $str, $this->last_query, $caller );
		} else {
			$error_str = sprintf( 'WordPress database error %1$s for query %2$s', $str, $this->last_query );
		}

		error_log( $error_str );

		// Chúng ta có đang hiển thị lỗi không?
		if ( ! $this->show_errors ) {
			return false;
		}

		wp_load_translations_early();

		// Nếu có lỗi thì ghi nhận nó.
		if ( is_multisite() ) {
			$msg = sprintf(
				"%s [%s]\n%s\n",
				__( 'WordPress database error:' ),
				$str,
				$this->last_query
			);

			if ( defined( 'ERRORLOGFILE' ) ) {
				error_log( $msg, 3, ERRORLOGFILE );
			}
			if ( defined( 'DIEONDBERROR' ) ) {
				wp_die( $msg );
			}
		} else {
			$str   = htmlspecialchars( $str, ENT_QUOTES );
			$query = htmlspecialchars( $this->last_query, ENT_QUOTES );

			printf(
				'<div id="error"><p class="wpdberror"><strong>%s</strong> [%s]<br /><code>%s</code></p></div>',
				__( 'WordPress database error:' ),
				$str,
				$query
			);
		}
	}

	/**
	 * Bật hiển thị lỗi cơ sở dữ liệu.
	 *
	 * Hàm này chỉ nên được sử dụng để bật hiển thị lỗi.
	 * Sử dụng wpdb::hide_errors() để ẩn lỗi.
	 *
	 * @since 0.71
	 *
	 * @see wpdb::hide_errors()
	 *
	 * @param bool $show Tùy chọn. Có hiển thị lỗi hay không. Mặc định true.
	 * @return bool Liệu hiển thị lỗi trước đó có đang bật hay không.
	 */
	public function show_errors( $show = true ) {
		$errors            = $this->show_errors;
		$this->show_errors = $show;
		return $errors;
	}

	/**
	 * Tắt hiển thị lỗi cơ sở dữ liệu.
	 *
	 * Theo mặc định, lỗi cơ sở dữ liệu không được hiển thị.
	 *
	 * @since 0.71
	 *
	 * @see wpdb::show_errors()
	 *
	 * @return bool Liệu hiển thị lỗi trước đó có đang bật hay không.
	 */
	public function hide_errors() {
		$show              = $this->show_errors;
		$this->show_errors = false;
		return $show;
	}

	/**
	 * Bật hoặc tắt chế độ ẩn lỗi cơ sở dữ liệu.
	 *
	 * Theo mặc định, lỗi cơ sở dữ liệu được ẩn.
	 *
	 * @since 2.5.0
	 *
	 * @see wpdb::hide_errors()
	 *
	 * @param bool $suppress Tùy chọn. Có ẩn lỗi hay không. Mặc định true.
	 * @return bool Liệu chế độ ẩn lỗi trước đó có đang bật hay không.
	 */
	public function suppress_errors( $suppress = true ) {
		$errors                = $this->suppress_errors;
		$this->suppress_errors = (bool) $suppress;
		return $errors;
	}

	/**
	 * Xóa kết quả truy vấn đã lưu trong bộ nhớ đệm.
	 *
	 * @since 0.71
	 */
	public function flush() {
		$this->last_result   = array();
		$this->col_info      = null;
		$this->last_query    = null;
		$this->rows_affected = 0;
		$this->num_rows      = 0;
		$this->last_error    = '';

		if ( $this->result instanceof mysqli_result ) {
			mysqli_free_result( $this->result );
			$this->result = null;

			// Kiểm tra an toàn trước khi sử dụng handle.
			if ( empty( $this->dbh ) || ! ( $this->dbh instanceof mysqli ) ) {
				return;
			}

			// Xóa sạch mọi kết quả từ truy vấn đa câu lệnh.
			while ( mysqli_more_results( $this->dbh ) ) {
				mysqli_next_result( $this->dbh );
			}
		}
	}

	/**
	 * Kết nối đến và chọn cơ sở dữ liệu.
	 *
	 * Nếu `$allow_bail` là false, việc thiếu kết nối cơ sở dữ liệu cần được xử lý thủ công.
	 *
	 * @since 3.0.0
	 * @since 3.9.0 Thêm tham số $allow_bail.
	 *
	 * @param bool $allow_bail Tùy chọn. Cho phép hàm dừng thực thi. Mặc định true.
	 * @return bool True khi kết nối thành công, false khi thất bại.
	 */
	public function db_connect( $allow_bail = true ) {
		$this->is_mysql = true;

		$client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		/*
		 * Tắt báo lỗi MySQLi vì WordPress tự xử lý lỗi.
		 * Điều này do giá trị mặc định đã thay đổi từ `MYSQLI_REPORT_OFF`
		 * sang `MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT` trong PHP 8.1.
		 */
		mysqli_report( MYSQLI_REPORT_OFF );

		$this->dbh = mysqli_init();

		$host    = $this->dbhost;
		$port    = null;
		$socket  = null;
		$is_ipv6 = false;

		$host_data = $this->parse_db_host( $this->dbhost );
		if ( $host_data ) {
			list( $host, $port, $socket, $is_ipv6 ) = $host_data;
		}

		/*
		 * Nếu sử dụng thư viện `mysqlnd`, địa chỉ IPv6 cần được bao trong
		 * dấu ngoặc vuông, trong khi không cần khi sử dụng thư viện `libmysqlclient`.
		 * @see https://bugs.php.net/bug.php?id=67563
		 */
		if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
			$host = "[$host]";
		}

		if ( WP_DEBUG ) {
			mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
		} else {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
		}

		if ( $this->dbh->connect_errno ) {
			$this->dbh = null;
		}

		if ( ! $this->dbh && $allow_bail ) {
			wp_load_translations_early();

			// Tải mẫu lỗi DB tùy chỉnh, nếu có.
			if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
				require_once WP_CONTENT_DIR . '/db-error.php';
				die();
			}

			$message = '<h1>' . __( 'Error establishing a database connection' ) . "</h1>\n";

			$message .= '<p>' . sprintf(
				/* translators: 1: wp-config.php, 2: Database host. */
				__( 'This either means that the username and password information in your %1$s file is incorrect or that contact with the database server at %2$s could not be established. This could mean your host&#8217;s database server is down.' ),
				'<code>wp-config.php</code>',
				'<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
			) . "</p>\n";

			$message .= "<ul>\n";
			$message .= '<li>' . __( 'Are you sure you have the correct username and password?' ) . "</li>\n";
			$message .= '<li>' . __( 'Are you sure you have typed the correct hostname?' ) . "</li>\n";
			$message .= '<li>' . __( 'Are you sure the database server is running?' ) . "</li>\n";
			$message .= "</ul>\n";

			$message .= '<p>' . sprintf(
				/* translators: %s: Support forums URL. */
				__( 'If you are unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress support forums</a>.' ),
				__( 'https://wordpress.org/support/forums/' )
			) . "</p>\n";

			$this->bail( $message, 'db_connect_fail' );

			return false;
		} elseif ( $this->dbh ) {
			if ( ! $this->has_connected ) {
				$this->init_charset();
			}

			$this->has_connected = true;

			$this->set_charset( $this->dbh );

			$this->ready = true;
			$this->set_sql_mode();
			$this->select( $this->dbname, $this->dbh );

			return true;
		}

		return false;
	}

	/**
	 * Phân tích cài đặt DB_HOST để diễn giải cho mysqli_real_connect().
	 *
	 * mysqli_real_connect() không hỗ trợ tham số host bao gồm cổng hoặc socket
	 * như mysql_connect(). Hàm này mô phỏng cách mysql_connect() phát hiện cổng
	 * và/hoặc file socket.
	 *
	 * @since 4.9.0
	 *
	 * @param string $host Cài đặt DB_HOST cần phân tích.
	 * @return array|false {
	 *     Mảng chứa host, cổng, socket và
	 *     có phải địa chỉ IPv6 hay không, theo thứ tự đó.
	 *     False nếu không thể phân tích host.
	 *
	 *     @type string      $0 Tên host.
	 *     @type string|null $1 Cổng.
	 *     @type string|null $2 Socket.
	 *     @type bool        $3 Có phải địa chỉ IPv6 hay không.
	 * }
	 */
	public function parse_db_host( $host ) {
		$socket  = null;
		$is_ipv6 = false;

		// Đầu tiên tách tham số socket từ bên phải, nếu có.
		$socket_pos = strpos( $host, ':/' );
		if ( false !== $socket_pos ) {
			$socket = substr( $host, $socket_pos + 1 );
			$host   = substr( $host, 0, $socket_pos );
		}

		/*
		 * Cần kiểm tra địa chỉ IPv6 trước.
		 * Một địa chỉ IPv6 luôn chứa ít nhất hai dấu hai chấm.
		 */
		if ( substr_count( $host, ':' ) > 1 ) {
			$pattern = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#';
			$is_ipv6 = true;
		} else {
			// Có vẻ đang xử lý địa chỉ IPv4.
			$pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';
		}

		$matches = array();
		$result  = preg_match( $pattern, $host, $matches );

		if ( 1 !== $result ) {
			// Không thể phân tích địa chỉ, dừng lại.
			return false;
		}

		$host = ! empty( $matches['host'] ) ? $matches['host'] : '';
		// Cổng MySQLi không thể là chuỗi; phải là null hoặc số nguyên.
		$port = ! empty( $matches['port'] ) ? absint( $matches['port'] ) : null;

		return array( $host, $port, $socket, $is_ipv6 );
	}

	/**
	 * Kiểm tra kết nối đến cơ sở dữ liệu vẫn hoạt động. Nếu không, thử kết nối lại.
	 *
	 * Nếu hàm này không thể kết nối lại, nó sẽ buộc dừng thực thi, hoặc nếu được gọi
	 * sau khi hook {@see 'template_redirect'} đã được kích hoạt, trả về false thay thế.
	 *
	 * Nếu `$allow_bail` là false, việc thiếu kết nối cơ sở dữ liệu cần được xử lý thủ công.
	 *
	 * @since 3.9.0
	 *
	 * @param bool $allow_bail Tùy chọn. Cho phép hàm dừng thực thi. Mặc định true.
	 * @return bool|void True nếu kết nối vẫn hoạt động.
	 */
	public function check_connection( $allow_bail = true ) {
		// Kiểm tra xem kết nối có còn sống không.
		if ( ! empty( $this->dbh ) && mysqli_query( $this->dbh, 'DO 1' ) !== false ) {
			return true;
		}

		$error_reporting = false;

		// Tắt cảnh báo, vì chúng ta không muốn thấy hàng loạt thông báo "không thể kết nối".
		if ( WP_DEBUG ) {
			$error_reporting = error_reporting();
			error_reporting( $error_reporting & ~E_WARNING );
		}

		for ( $tries = 1; $tries <= $this->reconnect_retries; $tries++ ) {
			/*
			 * Ở lần thử cuối cùng, bật lại cảnh báo. Chúng ta muốn thấy một lần duy nhất
			 * thông báo "không thể kết nối" trên màn hình bail(), nếu nó xuất hiện.
			 */
			if ( $this->reconnect_retries === $tries && WP_DEBUG ) {
				error_reporting( $error_reporting );
			}

			if ( $this->db_connect( false ) ) {
				if ( $error_reporting ) {
					error_reporting( $error_reporting );
				}

				return true;
			}

			sleep( 1 );
		}

		/*
		 * Nếu template_redirect đã xảy ra, thì quá muộn cho wp_die()/dead_db().
		 * Hãy cứ trả về và hy vọng điều tốt nhất.
		 */
		if ( did_action( 'template_redirect' ) ) {
			return false;
		}

		if ( ! $allow_bail ) {
			return false;
		}

		wp_load_translations_early();

		$message = '<h1>' . __( 'Error reconnecting to the database' ) . "</h1>\n";

		$message .= '<p>' . sprintf(
			/* translators: %s: Database host. */
			__( 'This means that the contact with the database server at %s was lost. This could mean your host&#8217;s database server is down.' ),
			'<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
		) . "</p>\n";

		$message .= "<ul>\n";
		$message .= '<li>' . __( 'Are you sure the database server is running?' ) . "</li>\n";
		$message .= '<li>' . __( 'Are you sure the database server is not under particularly heavy load?' ) . "</li>\n";
		$message .= "</ul>\n";

		$message .= '<p>' . sprintf(
			/* translators: %s: Support forums URL. */
			__( 'If you are unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress support forums</a>.' ),
			__( 'https://wordpress.org/support/forums/' )
		) . "</p>\n";

		// Không thể kết nối lại, nên tốt hơn là dừng thực thi.
		$this->bail( $message, 'db_connect_fail' );

		/*
		 * Gọi dead_db() nếu bail không dừng, vì cơ sở dữ liệu này không còn hoạt động.
		 * Nó đã ngừng hoạt động (ít nhất là tạm thời).
		 */
		dead_db();
	}

	/**
	 * Thực hiện truy vấn cơ sở dữ liệu, sử dụng kết nối cơ sở dữ liệu hiện tại.
	 *
	 * Thông tin thêm có thể tìm thấy trên trang tài liệu.
	 *
	 * @since 0.71
	 *
	 * @link https://developer.wordpress.org/reference/classes/wpdb/
	 *
	 * @param string $query Truy vấn cơ sở dữ liệu.
	 * @return int|bool Boolean true cho các truy vấn CREATE, ALTER, TRUNCATE và DROP. Số hàng
	 *                  bị ảnh hưởng/được chọn cho tất cả các truy vấn khác. Boolean false khi lỗi.
	 */
	public function query( $query ) {
		if ( ! $this->ready ) {
			$this->check_current_query = true;
			return false;
		}

		/**
		 * Lọc truy vấn cơ sở dữ liệu.
		 *
		 * Một số truy vấn được thực hiện trước khi các plugin được tải,
		 * và do đó không thể được lọc bằng phương thức này.
		 *
		 * @since 2.1.0
		 *
		 * @param string $query Truy vấn cơ sở dữ liệu.
		 */
		$query = apply_filters( 'query', $query );

		if ( ! $query ) {
			$this->insert_id = 0;
			return false;
		}

		$this->flush();

		// Ghi nhật ký cách hàm được gọi.
		$this->func_call = "\$db->query(\"$query\")";

		// Nếu đang ghi vào cơ sở dữ liệu, đảm bảo truy vấn sẽ ghi an toàn.
		if ( $this->check_current_query && ! $this->check_ascii( $query ) ) {
			$stripped_query = $this->strip_invalid_text_from_query( $query );
			/*
			 * strip_invalid_text_from_query() có thể thực hiện truy vấn, nên chúng ta cần
			 * xóa bộ đệm lại, chỉ để đảm bảo mọi thứ đã sạch.
			 */
			$this->flush();
			if ( $stripped_query !== $query ) {
				$this->insert_id  = 0;
				$this->last_query = $query;

				wp_load_translations_early();

				$this->last_error = __( 'WordPress database error: Could not perform query because it contains invalid data.' );

				return false;
			}
		}

		$this->check_current_query = true;

		// Theo dõi truy vấn cuối cùng để gỡ lỗi.
		$this->last_query = $query;

		$this->_do_query( $query );

		// Máy chủ cơ sở dữ liệu đã mất kết nối, thử kết nối lại.
		$mysql_errno = 0;

		if ( $this->dbh instanceof mysqli ) {
			$mysql_errno = mysqli_errno( $this->dbh );
		} else {
			/*
			 * $dbh đã được định nghĩa, nhưng không phải kết nối thực.
			 * Đã xảy ra lỗi nghiêm trọng, hãy thử kết nối lại.
			 */
			$mysql_errno = 2006;
		}

		if ( empty( $this->dbh ) || 2006 === $mysql_errno ) {
			if ( $this->check_connection() ) {
				$this->_do_query( $query );
			} else {
				$this->insert_id = 0;
				return false;
			}
		}

		// Nếu có lỗi thì ghi nhận lại.
		if ( $this->dbh instanceof mysqli ) {
			$this->last_error = mysqli_error( $this->dbh );
		} else {
			$this->last_error = __( 'Unable to retrieve the error message from MySQL' );
		}

		if ( $this->last_error ) {
			// Xóa insert_id khi insert tiếp theo thất bại.
			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = 0;
			}

			$this->print_error();
			return false;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = mysqli_affected_rows( $this->dbh );

			// Ghi nhận insert_id.
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = mysqli_insert_id( $this->dbh );
			}

			// Trả về số hàng bị ảnh hưởng.
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;

			if ( $this->result instanceof mysqli_result ) {
				while ( $row = mysqli_fetch_object( $this->result ) ) {
					$this->last_result[ $num_rows ] = $row;
					++$num_rows;
				}
			}

			// Ghi nhật ký và trả về số hàng được chọn.
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	/**
	 * Hàm nội bộ để thực hiện lệnh gọi mysqli_query().
	 *
	 * @since 3.9.0
	 *
	 * @see wpdb::query()
	 *
	 * @param string $query Truy vấn cần chạy.
	 */
	private function _do_query( $query ) {
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->timer_start();
		}

		if ( ! empty( $this->dbh ) ) {
			$this->result = mysqli_query( $this->dbh, $query );
		}

		++$this->num_queries;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->log_query(
				$query,
				$this->timer_stop(),
				$this->get_caller(),
				$this->time_start,
				array()
			);
		}
	}

	/**
	 * Ghi nhật ký dữ liệu truy vấn.
	 *
	 * @since 5.3.0
	 *
	 * @param string $query           Câu lệnh SQL của truy vấn.
	 * @param float  $query_time      Tổng thời gian thực hiện truy vấn, tính bằng giây.
	 * @param string $query_callstack Danh sách các hàm gọi, phân cách bằng dấu phẩy.
	 * @param float  $query_start     Dấu thời gian Unix tại thời điểm bắt đầu truy vấn.
	 * @param array  $query_data      Dữ liệu truy vấn tùy chỉnh.
	 */
	public function log_query( $query, $query_time, $query_callstack, $query_start, $query_data ) {
		/**
		 * Lọc dữ liệu tùy chỉnh được ghi cùng với truy vấn.
		 *
		 * Nên cẩn thận khi thay đổi bất kỳ dữ liệu nào trong này, khuyến khích thêm thông tin
		 * bổ sung cần lưu trữ về truy vấn dưới dạng phần tử mới trong mảng kết hợp.
		 *
		 * @since 5.3.0
		 *
		 * @param array  $query_data      Dữ liệu truy vấn tùy chỉnh.
		 * @param string $query           Câu lệnh SQL của truy vấn.
		 * @param float  $query_time      Tổng thời gian thực hiện truy vấn, tính bằng giây.
		 * @param string $query_callstack Danh sách các hàm gọi, phân cách bằng dấu phẩy.
		 * @param float  $query_start     Dấu thời gian Unix tại thời điểm bắt đầu truy vấn.
		 */
		$query_data = apply_filters( 'log_query_custom_data', $query_data, $query, $query_time, $query_callstack, $query_start );

		$this->queries[] = array(
			$query,
			$query_time,
			$query_callstack,
			$query_start,
			$query_data,
		);
	}

	/**
	 * Tạo và trả về chuỗi thoát placeholder để sử dụng trong các truy vấn trả về bởi ::prepare().
	 *
	 * @since 4.8.3
	 *
	 * @return string Chuỗi để thoát các placeholder.
	 */
	public function placeholder_escape() {
		static $placeholder;

		if ( ! $placeholder ) {
			// Các bản cài WP cũ có thể chưa định nghĩa AUTH_SALT.
			$salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : (string) rand();

			$placeholder = '{' . hash_hmac( 'sha256', uniqid( $salt, true ), $salt ) . '}';
		}

		/*
		 * Thêm bộ lọc để xóa chuỗi thoát placeholder. Sử dụng ưu tiên 0, để bất cứ thứ gì
		 * khác được gắn vào bộ lọc này sẽ nhận được truy vấn đã xóa chuỗi placeholder.
		 */
		if ( false === has_filter( 'query', array( $this, 'remove_placeholder_escape' ) ) ) {
			add_filter( 'query', array( $this, 'remove_placeholder_escape' ), 0 );
		}

		return $placeholder;
	}

	/**
	 * Thêm chuỗi thoát placeholder, để thoát bất cứ thứ gì giống placeholder của printf().
	 *
	 * @since 4.8.3
	 *
	 * @param string $query Truy vấn cần thoát.
	 * @return string Truy vấn với chuỗi thoát placeholder được chèn vào nơi cần thiết.
	 */
	public function add_placeholder_escape( $query ) {
		/*
		 * Để ngăn trả về bất cứ thứ gì dù chỉ mơ hồ giống placeholder,
		 * chúng ta thay thế mọi ký tự % tìm được.
		 */
		return str_replace( '%', $this->placeholder_escape(), $query );
	}

	/**
	 * Xóa các chuỗi thoát placeholder khỏi truy vấn.
	 *
	 * @since 4.8.3
	 *
	 * @param string $query Truy vấn cần xóa placeholder.
	 * @return string Truy vấn đã xóa placeholder.
	 */
	public function remove_placeholder_escape( $query ) {
		return str_replace( $this->placeholder_escape(), '%', $query );
	}

	/**
	 * Chèn một hàng vào bảng.
	 *
	 * Ví dụ:
	 *
	 *     $wpdb->insert(
	 *         'table',
	 *         array(
	 *             'column1' => 'foo',
	 *             'column2' => 'bar',
	 *         )
	 *     );
	 *     $wpdb->insert(
	 *         'table',
	 *         array(
	 *             'column1' => 'foo',
	 *             'column2' => 1337,
	 *         ),
	 *         array(
	 *             '%s',
	 *             '%d',
	 *         )
	 *     );
	 *
	 * @since 2.5.0
	 *
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string          $table  Tên bảng.
	 * @param array           $data   Dữ liệu cần chèn (dạng cặp cột => giá trị).
	 *                                Cả cột và giá trị `$data` đều nên là "thô" (không được escape SQL).
	 *                                Gửi giá trị null sẽ đặt cột thành NULL - định dạng tương ứng
	 *                                sẽ bị bỏ qua trong trường hợp này.
	 * @param string[]|string $format Tùy chọn. Mảng các định dạng được ánh xạ tới mỗi giá trị trong `$data`.
	 *                                Nếu là chuỗi, định dạng đó sẽ được dùng cho tất cả giá trị trong `$data`.
	 *                                Định dạng là một trong '%d', '%f', '%s' (số nguyên, số thực, chuỗi).
	 *                                Nếu bỏ qua, tất cả giá trị trong `$data` sẽ được xử lý như chuỗi trừ khi
	 *                                được chỉ định khác trong wpdb::$field_types. Mặc định null.
	 * @return int|false Số hàng được chèn, hoặc false khi lỗi.
	 */
	public function insert( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

	/**
	 * Thay thế một hàng trong bảng hoặc chèn nếu chưa tồn tại, dựa trên PRIMARY KEY hoặc chỉ mục UNIQUE.
	 *
	 * REPLACE hoạt động giống hệt INSERT, ngoại trừ nếu một hàng cũ trong bảng có cùng giá trị với hàng mới
	 * cho PRIMARY KEY hoặc chỉ mục UNIQUE, hàng cũ sẽ bị xóa trước khi hàng mới được chèn.
	 *
	 * Ví dụ:
	 *
	 *     $wpdb->replace(
	 *         'table',
	 *         array(
	 *             'ID'      => 123,
	 *             'column1' => 'foo',
	 *             'column2' => 'bar',
	 *         )
	 *     );
	 *     $wpdb->replace(
	 *         'table',
	 *         array(
	 *             'ID'      => 456,
	 *             'column1' => 'foo',
	 *             'column2' => 1337,
	 *         ),
	 *         array(
	 *             '%d',
	 *             '%s',
	 *             '%d',
	 *         )
	 *     );
	 *
	 * @since 3.0.0
	 *
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string          $table  Tên bảng.
	 * @param array           $data   Dữ liệu cần chèn (dạng cặp cột => giá trị).
	 *                                Cả cột và giá trị `$data` đều nên là "thô" (không được escape SQL).
	 *                                Cần có khóa chính hoặc chỉ mục duy nhất để thực hiện thao tác thay thế.
	 *                                Gửi giá trị null sẽ đặt cột thành NULL - định dạng tương ứng
	 *                                sẽ bị bỏ qua trong trường hợp này.
	 * @param string[]|string $format Tùy chọn. Mảng các định dạng được ánh xạ tới mỗi giá trị trong `$data`.
	 *                                Nếu là chuỗi, định dạng đó sẽ được dùng cho tất cả giá trị trong `$data`.
	 *                                Định dạng là một trong '%d', '%f', '%s' (số nguyên, số thực, chuỗi).
	 *                                Nếu bỏ qua, tất cả giá trị trong `$data` sẽ được xử lý như chuỗi trừ khi
	 *                                được chỉ định khác trong wpdb::$field_types. Mặc định null.
	 * @return int|false Số hàng bị ảnh hưởng, hoặc false khi lỗi.
	 */
	public function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}

	/**
	 * Hàm trợ giúp cho insert và replace.
	 *
	 * Chạy truy vấn insert hoặc replace dựa trên tham số `$type`.
	 *
	 * @since 3.0.0
	 *
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string          $table  Tên bảng.
	 * @param array           $data   Dữ liệu cần chèn (dạng cặp cột => giá trị).
	 *                                Cả cột và giá trị `$data` đều nên là "thô" (không được escape SQL).
	 *                                Gửi giá trị null sẽ đặt cột thành NULL - định dạng tương ứng
	 *                                sẽ bị bỏ qua trong trường hợp này.
	 * @param string[]|string $format Tùy chọn. Mảng các định dạng được ánh xạ tới mỗi giá trị trong `$data`.
	 *                                Nếu là chuỗi, định dạng đó sẽ được dùng cho tất cả giá trị trong `$data`.
	 *                                Định dạng là một trong '%d', '%f', '%s' (số nguyên, số thực, chuỗi).
	 *                                Nếu bỏ qua, tất cả giá trị trong `$data` sẽ được xử lý như chuỗi trừ khi
	 *                                được chỉ định khác trong wpdb::$field_types. Mặc định null.
	 * @param string          $type   Tùy chọn. Loại thao tác. Có thể là 'INSERT' hoặc 'REPLACE'.
	 *                                Mặc định 'INSERT'.
	 * @return int|false Số hàng bị ảnh hưởng, hoặc false khi lỗi.
	 */
	public function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		$this->insert_id = 0;

		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ), true ) ) {
			return false;
		}

		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}

		$formats = array();
		$values  = array();
		foreach ( $data as $value ) {
			if ( is_null( $value['value'] ) ) {
				$formats[] = 'NULL';
				continue;
			}

			$formats[] = $value['format'];
			$values[]  = $value['value'];
		}

		$fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
		$formats = implode( ', ', $formats );

		$sql = "$type INTO `$table` ($fields) VALUES ($formats)";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	/**
	 * Cập nhật một hàng trong bảng.
	 *
	 * Ví dụ:
	 *
	 *     $wpdb->update(
	 *         'table',
	 *         array(
	 *             'column1' => 'foo',
	 *             'column2' => 'bar',
	 *         ),
	 *         array(
	 *             'ID' => 1,
	 *         )
	 *     );
	 *     $wpdb->update(
	 *         'table',
	 *         array(
	 *             'column1' => 'foo',
	 *             'column2' => 1337,
	 *         ),
	 *         array(
	 *             'ID' => 1,
	 *         ),
	 *         array(
	 *             '%s',
	 *             '%d',
	 *         ),
	 *         array(
	 *             '%d',
	 *         )
	 *     );
	 *
	 * @since 2.5.0
	 *
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string       $table           Tên bảng.
	 * @param array        $data            Dữ liệu cần cập nhật (dạng cặp cột => giá trị).
	 *                                      Cả cột và giá trị $data đều nên là "thô" (không được escape SQL).
	 *                                      Gửi giá trị null sẽ đặt cột thành NULL - định dạng tương ứng
	 *                                      sẽ bị bỏ qua trong trường hợp này.
	 * @param array        $where           Mảng đặt tên các mệnh đề WHERE (dạng cặp cột => giá trị).
	 *                                      Nhiều mệnh đề sẽ được nối bằng AND.
	 *                                      Cả cột và giá trị $where đều nên là "thô".
	 *                                      Gửi giá trị null sẽ tạo so sánh IS NULL - định dạng tương ứng
	 *                                      sẽ bị bỏ qua trong trường hợp này.
	 * @param string[]|string $format       Tùy chọn. Mảng các định dạng được ánh xạ tới mỗi giá trị trong $data.
	 *                                      Nếu là chuỗi, định dạng đó sẽ được dùng cho tất cả giá trị trong $data.
	 *                                      Định dạng là một trong '%d', '%f', '%s' (số nguyên, số thực, chuỗi).
	 *                                      Nếu bỏ qua, tất cả giá trị trong $data sẽ được xử lý như chuỗi trừ khi
	 *                                      được chỉ định khác trong wpdb::$field_types. Mặc định null.
	 * @param string[]|string $where_format Tùy chọn. Mảng các định dạng được ánh xạ tới mỗi giá trị trong $where.
	 *                                      Nếu là chuỗi, định dạng đó sẽ được dùng cho tất cả mục trong $where.
	 *                                      Định dạng là một trong '%d', '%f', '%s' (số nguyên, số thực, chuỗi).
	 *                                      Nếu bỏ qua, tất cả giá trị trong $where sẽ được xử lý như chuỗi trừ khi
	 *                                      được chỉ định khác trong wpdb::$field_types. Mặc định null.
	 * @return int|false Số hàng được cập nhật, hoặc false khi lỗi.
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) ) {
			return false;
		}

		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}
		$where = $this->process_fields( $table, $where, $where_format );
		if ( false === $where ) {
			return false;
		}

		$fields     = array();
		$conditions = array();
		$values     = array();
		foreach ( $data as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$fields[] = "`$field` = NULL";
				continue;
			}

			$fields[] = "`$field` = " . $value['format'];
			$values[] = $value['value'];
		}
		foreach ( $where as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . $value['format'];
			$values[]     = $value['value'];
		}

		$fields     = implode( ', ', $fields );
		$conditions = implode( ' AND ', $conditions );

		$sql = "UPDATE `$table` SET $fields WHERE $conditions";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	/**
	 * Xóa một hàng trong bảng.
	 *
	 * Ví dụ:
	 *
	 *     $wpdb->delete(
	 *         'table',
	 *         array(
	 *             'ID' => 1,
	 *         )
	 *     );
	 *     $wpdb->delete(
	 *         'table',
	 *         array(
	 *             'ID' => 1,
	 *         ),
	 *         array(
	 *             '%d',
	 *         )
	 *     );
	 *
	 * @since 3.4.0
	 *
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string          $table        Tên bảng.
	 * @param array           $where        Mảng đặt tên các mệnh đề WHERE (dạng cặp cột => giá trị).
	 *                                      Nhiều mệnh đề sẽ được nối bằng AND.
	 *                                      Cả cột và giá trị $where đều nên là "thô".
	 *                                      Gửi giá trị null sẽ tạo so sánh IS NULL - định dạng tương ứng
	 *                                      sẽ bị bỏ qua trong trường hợp này.
	 * @param string[]|string $where_format Tùy chọn. Mảng các định dạng được ánh xạ tới mỗi giá trị trong $where.
	 *                                      Nếu là chuỗi, định dạng đó sẽ được dùng cho tất cả mục trong $where.
	 *                                      Định dạng là một trong '%d', '%f', '%s' (số nguyên, số thực, chuỗi).
	 *                                      Nếu bỏ qua, tất cả giá trị trong $data sẽ được xử lý như chuỗi trừ khi
	 *                                      được chỉ định khác trong wpdb::$field_types. Mặc định null.
	 * @return int|false Số hàng bị xóa, hoặc false khi lỗi.
	 */
	public function delete( $table, $where, $where_format = null ) {
		if ( ! is_array( $where ) ) {
			return false;
		}

		$where = $this->process_fields( $table, $where, $where_format );
		if ( false === $where ) {
			return false;
		}

		$conditions = array();
		$values     = array();
		foreach ( $where as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . $value['format'];
			$values[]     = $value['value'];
		}

		$conditions = implode( ' AND ', $conditions );

		$sql = "DELETE FROM `$table` WHERE $conditions";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	/**
	 * Xử lý mảng các cặp trường/giá trị và định dạng trường.
	 *
	 * Đây là phương thức trợ giúp cho các phương thức CRUD của wpdb, nhận các cặp trường/giá trị
	 * cho insert, update và mệnh đề where. Phương thức này trước tiên ghép mỗi giá trị
	 * với một định dạng. Sau đó xác định bộ ký tự của trường đó, sử dụng nó
	 * để xác định xem có văn bản không hợp lệ nào bị loại bỏ không. Nếu có,
	 * thì việc xử lý trường bị từ chối và truy vấn thất bại.
	 *
	 * @since 4.2.0
	 *
	 * @param string          $table  Tên bảng.
	 * @param array           $data   Mảng giá trị được đánh khóa theo tên trường.
	 * @param string[]|string $format Định dạng hoặc các định dạng được ánh xạ tới giá trị trong dữ liệu.
	 * @return array|false Mảng các trường chứa giá trị và định dạng đã ghép cặp.
	 *                     False cho các giá trị không hợp lệ.
	 */
	protected function process_fields( $table, $data, $format ) {
		$data = $this->process_field_formats( $data, $format );
		if ( false === $data ) {
			return false;
		}

		$data = $this->process_field_charsets( $data, $table );
		if ( false === $data ) {
			return false;
		}

		$data = $this->process_field_lengths( $data, $table );
		if ( false === $data ) {
			return false;
		}

		$converted_data = $this->strip_invalid_text( $data );

		if ( $data !== $converted_data ) {

			$problem_fields = array();
			foreach ( $data as $field => $value ) {
				if ( $value !== $converted_data[ $field ] ) {
					$problem_fields[] = $field;
				}
			}

			wp_load_translations_early();

			if ( 1 === count( $problem_fields ) ) {
				$this->last_error = sprintf(
					/* translators: %s: Database field where the error occurred. */
					__( 'WordPress database error: Processing the value for the following field failed: %s. The supplied value may be too long or contains invalid data.' ),
					reset( $problem_fields )
				);
			} else {
				$this->last_error = sprintf(
					/* translators: %s: Database fields where the error occurred. */
					__( 'WordPress database error: Processing the values for the following fields failed: %s. The supplied values may be too long or contain invalid data.' ),
					implode( ', ', $problem_fields )
				);
			}

			return false;
		}

		return $data;
	}

	/**
	 * Chuẩn bị mảng các cặp giá trị/định dạng được truyền cho các phương thức CRUD của wpdb.
	 *
	 * @since 4.2.0
	 *
	 * @param array           $data   Mảng giá trị được đánh khóa theo tên trường.
	 * @param string[]|string $format Định dạng hoặc các định dạng được ánh xạ tới giá trị trong dữ liệu.
	 * @return array {
	 *     Mảng giá trị và định dạng được đánh khóa theo tên trường.
	 *
	 *     @type mixed  $value  Giá trị cần định dạng.
	 *     @type string $format Định dạng được ánh xạ tới giá trị.
	 * }
	 */
	protected function process_field_formats( $data, $format ) {
		$formats          = (array) $format;
		$original_formats = $formats;

		foreach ( $data as $field => $value ) {
			$value = array(
				'value'  => $value,
				'format' => '%s',
			);

			if ( ! empty( $format ) ) {
				$value['format'] = array_shift( $formats );
				if ( ! $value['format'] ) {
					$value['format'] = reset( $original_formats );
				}
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$value['format'] = $this->field_types[ $field ];
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * Thêm bộ ký tự trường vào mảng trường/giá trị/định dạng được tạo bởi wpdb::process_field_formats().
	 *
	 * @since 4.2.0
	 *
	 * @param array $data {
	 *     Mảng giá trị và định dạng được đánh khóa theo tên trường,
	 *     như được trả về từ phương thức wpdb::process_field_formats().
	 *
	 *     @type array ...$0 {
	 *         Giá trị và định dạng cho trường này.
	 *
	 *         @type mixed  $value  Giá trị cần định dạng.
	 *         @type string $format Định dạng được ánh xạ tới giá trị.
	 *     }
	 * }
	 * @param string $table Tên bảng.
	 * @return array|false {
	 *     Cùng mảng dữ liệu với khóa 'charset' bổ sung, hoặc false nếu
	 *     không tìm được bộ ký tự cho bảng.
	 *
	 *     @type array ...$0 {
	 *         Giá trị, định dạng và bộ ký tự cho trường này.
	 *
	 *         @type mixed        $value   Giá trị cần định dạng.
	 *         @type string       $format  Định dạng được ánh xạ tới giá trị.
	 *         @type string|false $charset Bộ ký tự được sử dụng cho giá trị.
	 *     }
	 * }
	 */
	protected function process_field_charsets( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				/*
				 * Có thể bỏ qua trường này nếu biết nó không phải chuỗi.
				 * Kiểm tra %d/%f thay vì ! %s vì sprintf() của nó có thể nhận thêm.
				 */
				$value['charset'] = false;
			} else {
				$value['charset'] = $this->get_col_charset( $table, $field );
				if ( is_wp_error( $value['charset'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * Đối với trường chuỗi, ghi nhận độ dài chuỗi tối đa mà trường có thể lưu an toàn.
	 *
	 * @since 4.2.1
	 *
	 * @param array $data {
	 *     Mảng giá trị, định dạng và bộ ký tự được đánh khóa theo tên trường,
	 *     như được trả về từ phương thức wpdb::process_field_charsets().
	 *
	 *     @type array ...$0 {
	 *         Giá trị, định dạng và bộ ký tự cho trường này.
	 *
	 *         @type mixed        $value   Giá trị cần định dạng.
	 *         @type string       $format  Định dạng được ánh xạ tới giá trị.
	 *         @type string|false $charset Bộ ký tự được sử dụng cho giá trị.
	 *     }
	 * }
	 * @param string $table Tên bảng.
	 * @return array|false {
	 *     Cùng mảng dữ liệu với khóa 'length' bổ sung, hoặc false nếu
	 *     không tìm được thông tin cho bảng.
	 *
	 *     @type array ...$0 {
	 *         Giá trị, định dạng, bộ ký tự và độ dài cho trường này.
	 *
	 *         @type mixed        $value   Giá trị cần định dạng.
	 *         @type string       $format  Định dạng được ánh xạ tới giá trị.
	 *         @type string|false $charset Bộ ký tự được sử dụng cho giá trị.
	 *         @type array|false  $length  {
	 *             Thông tin về độ dài tối đa của giá trị.
	 *             False nếu cột không có độ dài.
	 *
	 *             @type string $type   Một trong 'byte' hoặc 'char'.
	 *             @type int    $length Độ dài cột.
	 *         }
	 *     }
	 * }
	 */
	protected function process_field_lengths( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				/*
				 * Có thể bỏ qua trường này nếu biết nó không phải chuỗi.
				 * Kiểm tra %d/%f thay vì ! %s vì sprintf() của nó có thể nhận thêm.
				 */
				$value['length'] = false;
			} else {
				$value['length'] = $this->get_col_length( $table, $field );
				if ( is_wp_error( $value['length'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * Lấy một giá trị từ cơ sở dữ liệu.
	 *
	 * Thực thi truy vấn SQL và trả về giá trị từ kết quả SQL.
	 * Nếu kết quả SQL chứa nhiều hơn một cột và/hoặc nhiều hơn một hàng,
	 * giá trị tại cột và hàng được chỉ định sẽ được trả về. Nếu $query là null,
	 * giá trị tại cột và hàng chỉ định từ kết quả SQL trước đó được trả về.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Tùy chọn. Truy vấn SQL. Mặc định null, sử dụng kết quả từ truy vấn trước.
	 * @param int         $x     Tùy chọn. Cột của giá trị cần trả về. Đánh số từ 0. Mặc định 0.
	 * @param int         $y     Tùy chọn. Hàng của giá trị cần trả về. Đánh số từ 0. Mặc định 0.
	 * @return string|null Kết quả truy vấn cơ sở dữ liệu (dạng chuỗi), hoặc null khi thất bại.
	 */
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->func_call = "\$db->get_var(\"$query\", $x, $y)";

		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		}

		// Trích xuất biến từ kết quả đã lưu trong bộ nhớ đệm dựa trên giá trị x,y.
		if ( ! empty( $this->last_result[ $y ] ) ) {
			$values = array_values( get_object_vars( $this->last_result[ $y ] ) );
		}

		// Nếu có giá trị thì trả về, không thì trả về null.
		return ( isset( $values[ $x ] ) && '' !== $values[ $x ] ) ? $values[ $x ] : null;
	}

	/**
	 * Lấy một hàng từ cơ sở dữ liệu.
	 *
	 * Thực thi truy vấn SQL và trả về hàng từ kết quả SQL.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query  Truy vấn SQL.
	 * @param string      $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
	 *                            tương ứng với đối tượng stdClass, mảng kết hợp, hoặc mảng số,
	 *                            theo thứ tự. Mặc định OBJECT.
	 * @param int         $y      Tùy chọn. Hàng cần trả về. Đánh số từ 0. Mặc định 0.
	 * @return array|object|null|void Kết quả truy vấn theo định dạng chỉ định bởi $output hoặc null khi thất bại.
	 */
	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";

		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		} else {
			return null;
		}

		if ( ! isset( $this->last_result[ $y ] ) ) {
			return null;
		}

		if ( OBJECT === $output ) {
			return $this->last_result[ $y ] ? $this->last_result[ $y ] : null;
		} elseif ( ARRAY_A === $output ) {
			return $this->last_result[ $y ] ? get_object_vars( $this->last_result[ $y ] ) : null;
		} elseif ( ARRAY_N === $output ) {
			return $this->last_result[ $y ] ? array_values( get_object_vars( $this->last_result[ $y ] ) ) : null;
		} elseif ( OBJECT === strtoupper( $output ) ) {
			// Back compat for OBJECT being previously case-insensitive.
			return $this->last_result[ $y ] ? $this->last_result[ $y ] : null;
		} else {
			$this->print_error( ' $db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N' );
		}
	}

	/**
	 * Retrieves one column from the database.
	 *
	 * Executes a SQL query and returns the column from the SQL result.
	 * If the SQL result contains more than one column, the column specified is returned.
	 * If $query is null, the specified column from the previous SQL result is returned.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int         $x     Optional. Column to return. Indexed from 0. Default 0.
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	public function get_col( $query = null, $x = 0 ) {
		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		}

		$new_array = array();
		// Extract the column values.
		if ( $this->last_result ) {
			for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
				$new_array[ $i ] = $this->get_var( null, $x, $i );
			}
		}
		return $new_array;
	}

	/**
	 * Retrieves an entire SQL result set from the database (i.e., many rows).
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string $query  SQL query.
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                       With one of the first three, return an array of rows indexed
	 *                       from 0 by SQL result row number. Each row is an associative array
	 *                       (column => value, ...), a numerically indexed array (0 => value, ...),
	 *                       or an object ( ->column = value ), respectively. With OBJECT_K,
	 *                       return an associative array of row objects keyed by the value
	 *                       of each row's first column's value. Duplicate keys are discarded.
	 *                       Default OBJECT.
	 * @return array|object|null Database query results.
	 */
	public function get_results( $query = null, $output = OBJECT ) {
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		} else {
			return null;
		}

		$new_array = array();
		if ( OBJECT === $output ) {
			// Return an integer-keyed array of row objects.
			return $this->last_result;
		} elseif ( OBJECT_K === $output ) {
			/*
			 * Return an array of row objects with keys from column 1.
			 * (Duplicates are discarded.)
			 */
			if ( $this->last_result ) {
				foreach ( $this->last_result as $row ) {
					$var_by_ref = get_object_vars( $row );
					$key        = array_shift( $var_by_ref );
					if ( ! isset( $new_array[ $key ] ) ) {
						$new_array[ $key ] = $row;
					}
				}
			}
			return $new_array;
		} elseif ( ARRAY_A === $output || ARRAY_N === $output ) {
			// Return an integer-keyed array of...
			if ( $this->last_result ) {
				if ( ARRAY_N === $output ) {
					foreach ( (array) $this->last_result as $row ) {
						// ...integer-keyed row arrays.
						$new_array[] = array_values( get_object_vars( $row ) );
					}
				} else {
					foreach ( (array) $this->last_result as $row ) {
						// ...column name-keyed row arrays.
						$new_array[] = get_object_vars( $row );
					}
				}
			}
			return $new_array;
		} elseif ( strtoupper( $output ) === OBJECT ) {
			// Back compat for OBJECT being previously case-insensitive.
			return $this->last_result;
		}
		return null;
	}

	/**
	 * Retrieves the character set for the given table.
	 *
	 * @since 4.2.0
	 *
	 * @param string $table Table name.
	 * @return string|WP_Error Table character set, WP_Error object if it couldn't be found.
	 */
	protected function get_table_charset( $table ) {
		$tablekey = strtolower( $table );

		/**
		 * Filters the table charset value before the DB is checked.
		 *
		 * Returning a non-null value from the filter will effectively short-circuit
		 * checking the DB for the charset, returning that value instead.
		 *
		 * @since 4.2.0
		 *
		 * @param string|WP_Error|null $charset The character set to use, WP_Error object
		 *                                      if it couldn't be found. Default null.
		 * @param string               $table   The name of the table being checked.
		 */
		$charset = apply_filters( 'pre_get_table_charset', null, $table );
		if ( null !== $charset ) {
			return $charset;
		}

		if ( isset( $this->table_charset[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		$charsets = array();
		$columns  = array();

		$table_parts = explode( '.', $table );
		$table       = '`' . implode( '`.`', $table_parts ) . '`';
		$results     = $this->get_results( "SHOW FULL COLUMNS FROM $table" );
		if ( ! $results ) {
			return new WP_Error( 'wpdb_get_table_charset_failure', __( 'Could not retrieve table charset.' ) );
		}

		foreach ( $results as $column ) {
			$columns[ strtolower( $column->Field ) ] = $column;
		}

		$this->col_meta[ $tablekey ] = $columns;

		foreach ( $columns as $column ) {
			if ( ! empty( $column->Collation ) ) {
				list( $charset ) = explode( '_', $column->Collation );

				$charsets[ strtolower( $charset ) ] = true;
			}

			list( $type ) = explode( '(', $column->Type );

			// A binary/blob means the whole query gets treated like this.
			if ( in_array( strtoupper( $type ), array( 'BINARY', 'VARBINARY', 'TINYBLOB', 'MEDIUMBLOB', 'BLOB', 'LONGBLOB' ), true ) ) {
				$this->table_charset[ $tablekey ] = 'binary';
				return 'binary';
			}
		}

		// utf8mb3 is an alias for utf8.
		if ( isset( $charsets['utf8mb3'] ) ) {
			$charsets['utf8'] = true;
			unset( $charsets['utf8mb3'] );
		}

		// Check if we have more than one charset in play.
		$count = count( $charsets );
		if ( 1 === $count ) {
			$charset = key( $charsets );
		} elseif ( 0 === $count ) {
			// No charsets, assume this table can store whatever.
			$charset = false;
		} else {
			// More than one charset. Remove latin1 if present and recalculate.
			unset( $charsets['latin1'] );
			$count = count( $charsets );
			if ( 1 === $count ) {
				// Only one charset (besides latin1).
				$charset = key( $charsets );
			} elseif ( 2 === $count && isset( $charsets['utf8'], $charsets['utf8mb4'] ) ) {
				// Two charsets, but they're utf8 and utf8mb4, use utf8.
				$charset = 'utf8';
			} else {
				// Two mixed character sets. ascii.
				$charset = 'ascii';
			}
		}

		$this->table_charset[ $tablekey ] = $charset;
		return $charset;
	}

	/**
	 * Retrieves the character set for the given column.
	 *
	 * @since 4.2.0
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @return string|false|WP_Error Column character set as a string. False if the column has
	 *                               no character set. WP_Error object if there was an error.
	 */
	public function get_col_charset( $table, $column ) {
		$tablekey  = strtolower( $table );
		$columnkey = strtolower( $column );

		/**
		 * Filters the column charset value before the DB is checked.
		 *
		 * Passing a non-null value to the filter will short-circuit
		 * checking the DB for the charset, returning that value instead.
		 *
		 * @since 4.2.0
		 *
		 * @param string|null|false|WP_Error $charset The character set to use. Default null.
		 * @param string                     $table   The name of the table being checked.
		 * @param string                     $column  The name of the column being checked.
		 */
		$charset = apply_filters( 'pre_get_col_charset', null, $table, $column );
		if ( null !== $charset ) {
			return $charset;
		}

		// Skip this entirely if this isn't a MySQL database.
		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		if ( empty( $this->table_charset[ $tablekey ] ) ) {
			// This primes column information for us.
			$table_charset = $this->get_table_charset( $table );
			if ( is_wp_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		// If still no column information, return the table charset.
		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		// If this column doesn't exist, return the table charset.
		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		// Return false when it's not a string column.
		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ]->Collation ) ) {
			return false;
		}

		list( $charset ) = explode( '_', $this->col_meta[ $tablekey ][ $columnkey ]->Collation );
		return $charset;
	}

	/**
	 * Retrieves the maximum string length allowed in a given column.
	 *
	 * The length may either be specified as a byte length or a character length.
	 *
	 * @since 4.2.1
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @return array|false|WP_Error {
	 *     Array of column length information, false if the column has no length (for
	 *     example, numeric column), WP_Error object if there was an error.
	 *
	 *     @type string $type   One of 'byte' or 'char'.
	 *     @type int    $length The column length.
	 * }
	 */
	public function get_col_length( $table, $column ) {
		$tablekey  = strtolower( $table );
		$columnkey = strtolower( $column );

		// Skip this entirely if this isn't a MySQL database.
		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			// This primes column information for us.
			$table_charset = $this->get_table_charset( $table );
			if ( is_wp_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return false;
		}

		$typeinfo = explode( '(', $this->col_meta[ $tablekey ][ $columnkey ]->Type );

		$type = strtolower( $typeinfo[0] );
		if ( ! empty( $typeinfo[1] ) ) {
			$length = trim( $typeinfo[1], ')' );
		} else {
			$length = false;
		}

		switch ( $type ) {
			case 'char':
			case 'varchar':
				return array(
					'type'   => 'char',
					'length' => (int) $length,
				);

			case 'binary':
			case 'varbinary':
				return array(
					'type'   => 'byte',
					'length' => (int) $length,
				);

			case 'tinyblob':
			case 'tinytext':
				return array(
					'type'   => 'byte',
					'length' => 255,        // 2^8 - 1
				);

			case 'blob':
			case 'text':
				return array(
					'type'   => 'byte',
					'length' => 65535,      // 2^16 - 1
				);

			case 'mediumblob':
			case 'mediumtext':
				return array(
					'type'   => 'byte',
					'length' => 16777215,   // 2^24 - 1
				);

			case 'longblob':
			case 'longtext':
				return array(
					'type'   => 'byte',
					'length' => 4294967295, // 2^32 - 1
				);

			default:
				return false;
		}
	}

	/**
	 * Checks if a string is ASCII.
	 *
	 * The negative regex is faster for non-ASCII strings, as it allows
	 * the search to finish as soon as it encounters a non-ASCII character.
	 *
	 * @since 4.2.0
	 *
	 * @param string $input_string String to check.
	 * @return bool True if ASCII, false if not.
	 */
	protected function check_ascii( $input_string ) {
		if ( function_exists( 'mb_check_encoding' ) ) {
			if ( mb_check_encoding( $input_string, 'ASCII' ) ) {
				return true;
			}
		} elseif ( ! preg_match( '/[^\x00-\x7F]/', $input_string ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the query is accessing a collation considered safe on the current version of MySQL.
	 *
	 * @since 4.2.0
	 *
	 * @param string $query The query to check.
	 * @return bool True if the collation is safe, false if it isn't.
	 */
	protected function check_safe_collation( $query ) {
		if ( $this->checking_collation ) {
			return true;
		}

		// We don't need to check the collation for queries that don't read data.
		$query = ltrim( $query, "\r\n\t (" );
		if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $query ) ) {
			return true;
		}

		// All-ASCII queries don't need extra checking.
		if ( $this->check_ascii( $query ) ) {
			return true;
		}

		$table = $this->get_table_from_query( $query );
		if ( ! $table ) {
			return false;
		}

		$this->checking_collation = true;
		$collation                = $this->get_table_charset( $table );
		$this->checking_collation = false;

		// Tables with no collation, or latin1 only, don't need extra checking.
		if ( false === $collation || 'latin1' === $collation ) {
			return true;
		}

		$table = strtolower( $table );
		if ( empty( $this->col_meta[ $table ] ) ) {
			return false;
		}

		// If any of the columns don't have one of these collations, it needs more confidence checking.
		$safe_collations = array(
			'utf8_bin',
			'utf8_general_ci',
			'utf8mb3_bin',
			'utf8mb3_general_ci',
			'utf8mb4_bin',
			'utf8mb4_general_ci',
		);

		foreach ( $this->col_meta[ $table ] as $col ) {
			if ( empty( $col->Collation ) ) {
				continue;
			}

			if ( ! in_array( $col->Collation, $safe_collations, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Strips any invalid characters based on value/charset pairs.
	 *
	 * @since 4.2.0
	 *
	 * @param array $data Array of value arrays. Each value array has the keys 'value', 'charset', and 'length'.
	 *                    An optional 'ascii' key can be set to false to avoid redundant ASCII checks.
	 * @return array|WP_Error The $data parameter, with invalid characters removed from each value.
	 *                        This works as a passthrough: any additional keys such as 'field' are
	 *                        retained in each value array. If we cannot remove invalid characters,
	 *                        a WP_Error object is returned.
	 */
	protected function strip_invalid_text( $data ) {
		$db_check_string = false;

		foreach ( $data as &$value ) {
			$charset = $value['charset'];

			if ( is_array( $value['length'] ) ) {
				$length                  = $value['length']['length'];
				$truncate_by_byte_length = 'byte' === $value['length']['type'];
			} else {
				$length = false;
				/*
				 * Since we have no length, we'll never truncate. Initialize the variable to false.
				 * True would take us through an unnecessary (for this case) codepath below.
				 */
				$truncate_by_byte_length = false;
			}

			// There's no charset to work with.
			if ( false === $charset ) {
				continue;
			}

			// Column isn't a string.
			if ( ! is_string( $value['value'] ) ) {
				continue;
			}

			$needs_validation = true;
			if (
				// latin1 can store any byte sequence.
				'latin1' === $charset
			||
				// ASCII is always OK.
				( ! isset( $value['ascii'] ) && $this->check_ascii( $value['value'] ) )
			) {
				$truncate_by_byte_length = true;
				$needs_validation        = false;
			}

			if ( $truncate_by_byte_length ) {
				mbstring_binary_safe_encoding();
				if ( false !== $length && strlen( $value['value'] ) > $length ) {
					$value['value'] = substr( $value['value'], 0, $length );
				}
				reset_mbstring_encoding();

				if ( ! $needs_validation ) {
					continue;
				}
			}

			// utf8 can be handled by regex, which is a bunch faster than a DB lookup.
			if ( ( 'utf8' === $charset || 'utf8mb3' === $charset || 'utf8mb4' === $charset ) && function_exists( 'mb_strlen' ) ) {
				$regex = '/
					(
						(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
						|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
						|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
						|   [\xE1-\xEC][\x80-\xBF]{2}
						|   \xED[\x80-\x9F][\x80-\xBF]
						|   [\xEE-\xEF][\x80-\xBF]{2}';

				if ( 'utf8mb4' === $charset ) {
					$regex .= '
						|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
						|    [\xF1-\xF3][\x80-\xBF]{3}
						|    \xF4[\x80-\x8F][\x80-\xBF]{2}
					';
				}

				$regex         .= '){1,40}                          # ...one or more times
					)
					| .                                  # anything else
					/x';
				$value['value'] = preg_replace( $regex, '$1', $value['value'] );

				if ( false !== $length && mb_strlen( $value['value'], 'UTF-8' ) > $length ) {
					$value['value'] = mb_substr( $value['value'], 0, $length, 'UTF-8' );
				}
				continue;
			}

			// We couldn't use any local conversions, send it to the DB.
			$value['db']     = true;
			$db_check_string = true;
		}
		unset( $value ); // Remove by reference.

		if ( $db_check_string ) {
			$queries = array();
			foreach ( $data as $col => $value ) {
				if ( ! empty( $value['db'] ) ) {
					// We're going to need to truncate by characters or bytes, depending on the length value we have.
					if ( isset( $value['length']['type'] ) && 'byte' === $value['length']['type'] ) {
						// Using binary causes LEFT() to truncate by bytes.
						$charset = 'binary';
					} else {
						$charset = $value['charset'];
					}

					if ( $this->charset ) {
						$connection_charset = $this->charset;
					} else {
						$connection_charset = mysqli_character_set_name( $this->dbh );
					}

					if ( is_array( $value['length'] ) ) {
						$length          = sprintf( '%.0f', $value['length']['length'] );
						$queries[ $col ] = $this->prepare( "CONVERT( LEFT( CONVERT( %s USING $charset ), $length ) USING $connection_charset )", $value['value'] );
					} elseif ( 'binary' !== $charset ) {
						// If we don't have a length, there's no need to convert binary - it will always return the same result.
						$queries[ $col ] = $this->prepare( "CONVERT( CONVERT( %s USING $charset ) USING $connection_charset )", $value['value'] );
					}

					unset( $data[ $col ]['db'] );
				}
			}

			$sql = array();
			foreach ( $queries as $column => $query ) {
				if ( ! $query ) {
					continue;
				}

				$sql[] = $query . " AS x_$column";
			}

			$this->check_current_query = false;
			$row                       = $this->get_row( 'SELECT ' . implode( ', ', $sql ), ARRAY_A );
			if ( ! $row ) {
				return new WP_Error( 'wpdb_strip_invalid_text_failure', __( 'Could not strip invalid text.' ) );
			}

			foreach ( array_keys( $data ) as $column ) {
				if ( isset( $row[ "x_$column" ] ) ) {
					$data[ $column ]['value'] = $row[ "x_$column" ];
				}
			}
		}

		return $data;
	}

	/**
	 * Strips any invalid characters from the query.
	 *
	 * @since 4.2.0
	 *
	 * @param string $query Query to convert.
	 * @return string|WP_Error The converted query, or a WP_Error object if the conversion fails.
	 */
	protected function strip_invalid_text_from_query( $query ) {
		// We don't need to check the collation for queries that don't read data.
		$trimmed_query = ltrim( $query, "\r\n\t (" );
		if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $trimmed_query ) ) {
			return $query;
		}

		$table = $this->get_table_from_query( $query );
		if ( $table ) {
			$charset = $this->get_table_charset( $table );
			if ( is_wp_error( $charset ) ) {
				return $charset;
			}

			// We can't reliably strip text from tables containing binary/blob columns.
			if ( 'binary' === $charset ) {
				return $query;
			}
		} else {
			$charset = $this->charset;
		}

		$data = array(
			'value'   => $query,
			'charset' => $charset,
			'ascii'   => false,
			'length'  => false,
		);

		$data = $this->strip_invalid_text( array( $data ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data[0]['value'];
	}

	/**
	 * Strips any invalid characters from the string for a given table and column.
	 *
	 * @since 4.2.0
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @param string $value  The text to check.
	 * @return string|WP_Error The converted string, or a WP_Error object if the conversion fails.
	 */
	public function strip_invalid_text_for_column( $table, $column, $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$charset = $this->get_col_charset( $table, $column );
		if ( ! $charset ) {
			// Not a string column.
			return $value;
		} elseif ( is_wp_error( $charset ) ) {
			// Bail on real errors.
			return $charset;
		}

		$data = array(
			$column => array(
				'value'   => $value,
				'charset' => $charset,
				'length'  => $this->get_col_length( $table, $column ),
			),
		);

		$data = $this->strip_invalid_text( $data );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data[ $column ]['value'];
	}

	/**
	 * Finds the first table name referenced in a query.
	 *
	 * @since 4.2.0
	 *
	 * @param string $query The query to search.
	 * @return string|false The table name found, or false if a table couldn't be found.
	 */
	protected function get_table_from_query( $query ) {
		// Remove characters that can legally trail the table name.
		$query = rtrim( $query, ';/-#' );

		// Allow (select...) union [...] style queries. Use the first query's table name.
		$query = ltrim( $query, "\r\n\t (" );

		// Strip everything between parentheses except nested selects.
		$query = preg_replace( '/\((?!\s*select)[^(]*?\)/is', '()', $query );

		// Quickly match most common queries.
		if ( preg_match(
			'/^\s*(?:'
				. 'SELECT.*?\s+FROM'
				. '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
				. '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
				. '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
				. '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:.+?FROM)?'
			. ')\s+((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)/is',
			$query,
			$maybe
		) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		// SHOW TABLE STATUS and SHOW TABLES WHERE Name = 'wp_posts'
		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES).+WHERE\s+Name\s*=\s*("|\')((?:[0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)\\1/is', $query, $maybe ) ) {
			return $maybe[2];
		}

		/*
		 * SHOW TABLE STATUS LIKE and SHOW TABLES LIKE 'wp\_123\_%'
		 * This quoted LIKE operand seldom holds a full table name.
		 * It is usually a pattern for matching a prefix so we just
		 * strip the trailing % and unescape the _ to get 'wp_123_'
		 * which drop-ins can use for routing these SQL statements.
		 */
		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES)\s+(?:WHERE\s+Name\s+)?LIKE\s*("|\')((?:[\\\\0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)%?\\1/is', $query, $maybe ) ) {
			return str_replace( '\\_', '_', $maybe[2] );
		}

		// Big pattern for the rest of the table-related queries.
		if ( preg_match(
			'/^\s*(?:'
				. '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
				. '|DESCRIBE|DESC|EXPLAIN|HANDLER'
				. '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
				. '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|REPAIR).*\s+TABLE'
				. '|TRUNCATE(?:\s+TABLE)?'
				. '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
				. '|ALTER(?:\s+IGNORE)?\s+TABLE'
				. '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
				. '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
				. '|DROP\s+INDEX.*\s+ON'
				. '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
				. '|(?:GRANT|REVOKE).*ON\s+TABLE'
				. '|SHOW\s+(?:.*FROM|.*TABLE)'
			. ')\s+\(*\s*((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)\s*\)*/is',
			$query,
			$maybe
		) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		return false;
	}

	/**
	 * Loads the column metadata from the last query.
	 *
	 * @since 3.5.0
	 */
	protected function load_col_info() {
		if ( $this->col_info ) {
			return;
		}

		$num_fields = mysqli_num_fields( $this->result );

		for ( $i = 0; $i < $num_fields; $i++ ) {
			$this->col_info[ $i ] = mysqli_fetch_field( $this->result );
		}
	}

	/**
	 * Retrieves column metadata from the last query.
	 *
	 * @since 0.71
	 *
	 * @param string $info_type  Optional. Possible values include 'name', 'table', 'def', 'max_length',
	 *                           'not_null', 'primary_key', 'multiple_key', 'unique_key', 'numeric',
	 *                           'blob', 'type', 'unsigned', 'zerofill'. Default 'name'.
	 * @param int    $col_offset Optional. 0: col name. 1: which table the col's in. 2: col's max length.
	 *                           3: if the col is numeric. 4: col's type. Default -1.
	 * @return mixed Column results.
	 */
	public function get_col_info( $info_type = 'name', $col_offset = -1 ) {
		$this->load_col_info();

		if ( $this->col_info ) {
			if ( -1 === $col_offset ) {
				$i         = 0;
				$new_array = array();
				foreach ( (array) $this->col_info as $col ) {
					$new_array[ $i ] = $col->{$info_type};
					++$i;
				}
				return $new_array;
			} else {
				return $this->col_info[ $col_offset ]->{$info_type};
			}
		}
	}

	/**
	 * Starts the timer, for debugging purposes.
	 *
	 * @since 1.5.0
	 *
	 * @return true
	 */
	public function timer_start() {
		$this->time_start = microtime( true );
		return true;
	}

	/**
	 * Stops the debugging timer.
	 *
	 * @since 1.5.0
	 *
	 * @return float Total time spent on the query, in seconds.
	 */
	public function timer_stop() {
		return ( microtime( true ) - $this->time_start );
	}

	/**
	 * Wraps errors in a nice header and footer and dies.
	 *
	 * Will not die if wpdb::$show_errors is false.
	 *
	 * @since 1.5.0
	 *
	 * @param string $message    The error message.
	 * @param string $error_code Optional. A computer-readable string to identify the error.
	 *                           Default '500'.
	 * @return void|false Void if the showing of errors is enabled, false if disabled.
	 */
	public function bail( $message, $error_code = '500' ) {
		if ( $this->show_errors ) {
			$error = '';

			if ( $this->dbh instanceof mysqli ) {
				$error = mysqli_error( $this->dbh );
			} elseif ( mysqli_connect_errno() ) {
				$error = mysqli_connect_error();
			}

			if ( $error ) {
				$message = '<p><code>' . $error . "</code></p>\n" . $message;
			}

			wp_die( $message );
		} else {
			if ( class_exists( 'WP_Error', false ) ) {
				$this->error = new WP_Error( $error_code, $message );
			} else {
				$this->error = $message;
			}

			return false;
		}
	}

	/**
	 * Closes the current database connection.
	 *
	 * @since 4.5.0
	 *
	 * @return bool True if the connection was successfully closed,
	 *              false if it wasn't, or if the connection doesn't exist.
	 */
	public function close() {
		if ( ! $this->dbh ) {
			return false;
		}

		$closed = mysqli_close( $this->dbh );

		if ( $closed ) {
			$this->dbh           = null;
			$this->ready         = false;
			$this->has_connected = false;
		}

		return $closed;
	}

	/**
	 * Determines whether MySQL database is at least the required minimum version.
	 *
	 * @since 2.5.0
	 *
	 * @global string $required_mysql_version The required MySQL version string.
	 * @return void|WP_Error
	 */
	public function check_database_version() {
		global $required_mysql_version;
		$wp_version = wp_get_wp_version();

		// Make sure the server has the required MySQL version.
		if ( version_compare( $this->db_version(), $required_mysql_version, '<' ) ) {
			/* translators: 1: WordPress version number, 2: Minimum required MySQL version number. */
			return new WP_Error( 'database_version', sprintf( __( '<strong>Error:</strong> WordPress %1$s requires MySQL %2$s or higher' ), $wp_version, $required_mysql_version ) );
		}
	}

	/**
	 * Determines whether the database supports collation.
	 *
	 * Called when WordPress is generating the table scheme.
	 *
	 * Use `wpdb::has_cap( 'collation' )`.
	 *
	 * @since 2.5.0
	 * @deprecated 3.5.0 Use wpdb::has_cap()
	 *
	 * @return bool True if collation is supported, false if not.
	 */
	public function supports_collation() {
		_deprecated_function( __FUNCTION__, '3.5.0', 'wpdb::has_cap( \'collation\' )' );
		return $this->has_cap( 'collation' );
	}

	/**
	 * Retrieves the database character collate.
	 *
	 * @since 3.5.0
	 *
	 * @return string The database character collate.
	 */
	public function get_charset_collate() {
		$charset_collate = '';

		if ( ! empty( $this->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $this->charset";
		}
		if ( ! empty( $this->collate ) ) {
			$charset_collate .= " COLLATE $this->collate";
		}

		return $charset_collate;
	}

	/**
	 * Determines whether the database or WPDB supports a particular feature.
	 *
	 * Capability sniffs for the database server and current version of WPDB.
	 *
	 * Database sniffs are based on the version of MySQL the site is using.
	 *
	 * WPDB sniffs are added as new features are introduced to allow theme and plugin
	 * developers to determine feature support. This is to account for drop-ins which may
	 * introduce feature support at a different time to WordPress.
	 *
	 * @since 2.7.0
	 * @since 4.1.0 Added support for the 'utf8mb4' feature.
	 * @since 4.6.0 Added support for the 'utf8mb4_520' feature.
	 * @since 6.2.0 Added support for the 'identifier_placeholders' feature.
	 * @since 6.6.0 The `utf8mb4` feature now always returns true.
	 *
	 * @see wpdb::db_version()
	 *
	 * @param string $db_cap The feature to check for. Accepts 'collation', 'group_concat',
	 *                       'subqueries', 'set_charset', 'utf8mb4', 'utf8mb4_520',
	 *                       or 'identifier_placeholders'.
	 * @return bool True when the database feature is supported, false otherwise.
	 */
	public function has_cap( $db_cap ) {
		$db_version     = $this->db_version();
		$db_server_info = $this->db_server_info();

		/*
		 * Account for MariaDB version being prefixed with '5.5.5-' on older PHP versions.
		 *
		 * Note: str_contains() is not used here, as this file can be included
		 * directly outside of WordPress core, e.g. by HyperDB, in which case
		 * the polyfills from wp-includes/compat.php are not loaded.
		 */
		if ( '5.5.5' === $db_version && false !== strpos( $db_server_info, 'MariaDB' )
			&& PHP_VERSION_ID < 80016 // PHP 8.0.15 or older.
		) {
			// Strip the '5.5.5-' prefix and set the version to the correct value.
			$db_server_info = preg_replace( '/^5\.5\.5-(.*)/', '$1', $db_server_info );
			$db_version     = preg_replace( '/[^0-9.].*/', '', $db_server_info );
		}

		switch ( strtolower( $db_cap ) ) {
			case 'collation':    // @since 2.5.0
			case 'group_concat': // @since 2.7.0
			case 'subqueries':   // @since 2.7.0
				return version_compare( $db_version, '4.1', '>=' );
			case 'set_charset':
				return version_compare( $db_version, '5.0.7', '>=' );
			case 'utf8mb4':      // @since 4.1.0
				return true;
			case 'utf8mb4_520': // @since 4.6.0
				return version_compare( $db_version, '5.6', '>=' );
			case 'identifier_placeholders': // @since 6.2.0
				/*
				 * As of WordPress 6.2, wpdb::prepare() supports identifiers via '%i',
				 * e.g. table/field names.
				 */
				return true;
		}

		return false;
	}

	/**
	 * Retrieves a comma-separated list of the names of the functions that called wpdb.
	 *
	 * @since 2.5.0
	 *
	 * @return string Comma-separated list of the calling functions.
	 */
	public function get_caller() {
		return wp_debug_backtrace_summary( __CLASS__ );
	}

	/**
	 * Retrieves the database server version.
	 *
	 * @since 2.7.0
	 *
	 * @return string|null Version number on success, null on failure.
	 */
	public function db_version() {
		return preg_replace( '/[^0-9.].*/', '', $this->db_server_info() );
	}

	/**
	 * Returns the version of the MySQL server.
	 *
	 * @since 5.5.0
	 *
	 * @return string Server version as a string.
	 */
	public function db_server_info() {
		return mysqli_get_server_info( $this->dbh );
	}
}
