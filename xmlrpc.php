<?php
/**
 * Hỗ trợ giao thức XML-RPC cho WordPress.
 *
 * @package WordPress
 */

/**
 * Có phải đây là một yêu cầu XML-RPC hay không.
 *
 * @var bool
 */
define( 'XMLRPC_REQUEST', true );

// Loại bỏ các cookie không cần thiết được gửi bởi một số client nhúng trong trình duyệt.
$_COOKIE = array();

// $HTTP_RAW_POST_DATA đã bị loại bỏ trong PHP 5.6 và bị xóa trong PHP 7.0.
// phpcs:disable PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
	$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
}

// Sửa lỗi cho mozBlog và các trường hợp khác khi '<?xml' không nằm ở dòng đầu tiên.
$HTTP_RAW_POST_DATA = trim( $HTTP_RAW_POST_DATA );
// phpcs:enable

/** Include bootstrap để thiết lập môi trường WordPress */
require_once __DIR__ . '/wp-load.php';

if ( isset( $_GET['rsd'] ) ) { // https://cyber.harvard.edu/blogs/gems/tech/rsd.html
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
	echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>';
	?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
	<service>
		<engineName>WordPress</engineName>
		<engineLink>https://wordpress.org/</engineLink>
		<homePageLink><?php bloginfo_rss( 'url' ); ?></homePageLink>
		<apis>
			<api name="WordPress" blogID="1" preferred="true" apiLink="<?php echo site_url( 'xmlrpc.php', 'rpc' ); ?>" />
			<api name="Movable Type" blogID="1" preferred="false" apiLink="<?php echo site_url( 'xmlrpc.php', 'rpc' ); ?>" />
			<api name="MetaWeblog" blogID="1" preferred="false" apiLink="<?php echo site_url( 'xmlrpc.php', 'rpc' ); ?>" />
			<api name="Blogger" blogID="1" preferred="false" apiLink="<?php echo site_url( 'xmlrpc.php', 'rpc' ); ?>" />
			<?php
			/**
			 * Kích hoạt khi thêm các API vào điểm cuối Really Simple Discovery (RSD).
			 *
			 * @link https://cyber.harvard.edu/blogs/gems/tech/rsd.html
			 *
			 * @since 3.5.0
			 */
			do_action( 'xmlrpc_rsd_apis' );
			?>
		</apis>
	</service>
</rsd>
	<?php
	exit;
}

require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . WPINC . '/class-IXR.php';
require_once ABSPATH . WPINC . '/class-wp-xmlrpc-server.php';

/**
 * Các bài viết được gửi qua giao diện XML-RPC sẽ nhận tiêu đề này.
 *
 * @name post_default_title
 * @var string
 */
$post_default_title = '';

/**
 * Lọc lớp được sử dụng để xử lý các yêu cầu XML-RPC.
 *
 * @since 3.1.0
 *
 * @param string $class Tên lớp máy chủ XML-RPC.
 */
$wp_xmlrpc_server_class = apply_filters( 'wp_xmlrpc_server_class', 'wp_xmlrpc_server' );
$wp_xmlrpc_server       = new $wp_xmlrpc_server_class();

// Kích hoạt yêu cầu.
$wp_xmlrpc_server->serve_request();

exit;

/**
 * logIO() - Ghi thông tin nhật ký vào tệp.
 *
 * @since 1.2.0
 * @deprecated 3.4.0 Sử dụng error_log()
 * @see error_log()
 *
 * @global int|bool $xmlrpc_logging Có bật ghi nhật ký XML-RPC hay không.
 *
 * @param string $io  Đầu vào hay đầu ra.
 * @param string $msg Thông tin mô tả lý do ghi nhật ký.
 */
function logIO( $io, $msg ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'error_log()' );
	if ( ! empty( $GLOBALS['xmlrpc_logging'] ) ) {
		error_log( $io . ' - ' . $msg );
	}
}
