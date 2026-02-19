<?php
/**
 * Cấu hình cơ bản cho WordPress
 *
 * Script tạo wp-config.php sử dụng file này trong quá trình cài đặt.
 * Bạn không cần sử dụng website, có thể sao chép file này thành "wp-config.php"
 * và điền các giá trị.
 *
 * File này chứa các cấu hình sau:
 *
 * * Cài đặt cơ sở dữ liệu
 * * Khóa bí mật
 * * Tiền tố bảng cơ sở dữ liệu
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Cài đặt cơ sở dữ liệu - Bạn có thể lấy thông tin này từ nhà cung cấp hosting ** //
/** Tên cơ sở dữ liệu cho WordPress */
define( 'DB_NAME', 'database_name_here' );

/** Tên người dùng cơ sở dữ liệu */
define( 'DB_USER', 'username_here' );

/** Mật khẩu cơ sở dữ liệu */
define( 'DB_PASSWORD', 'password_here' );

/** Tên máy chủ cơ sở dữ liệu */
define( 'DB_HOST', 'localhost' );

/** Bộ ký tự cơ sở dữ liệu sử dụng khi tạo bảng. */
define( 'DB_CHARSET', 'utf8' );

/** Kiểu collate của cơ sở dữ liệu. Không thay đổi nếu không chắc chắn. */
define( 'DB_COLLATE', '' );

/**#@+
 * Khóa xác thực và salt duy nhất.
 *
 * Thay đổi thành các chuỗi duy nhất khác nhau! Bạn có thể tạo chúng bằng
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ dịch vụ khóa bí mật WordPress.org}.
 *
 * Bạn có thể thay đổi chúng bất cứ lúc nào để vô hiệu hóa tất cả cookies hiện có.
 * Điều này sẽ buộc tất cả người dùng phải đăng nhập lại.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

/**#@-*/

/**
 * Tiền tố bảng cơ sở dữ liệu WordPress.
 *
 * Bạn có thể có nhiều bản cài đặt trong một cơ sở dữ liệu nếu đặt cho mỗi bản
 * một tiền tố duy nhất. Chỉ sử dụng số, chữ cái và dấu gạch dưới!
 *
 * Khi cài đặt, các bảng cơ sở dữ liệu được tạo với tiền tố đã chỉ định.
 * Thay đổi giá trị này sau khi WordPress đã cài đặt sẽ khiến site nghĩ
 * rằng chưa được cài đặt.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * Dành cho nhà phát triển: Chế độ debug WordPress.
 *
 * Thay đổi thành true để bật hiển thị thông báo trong quá trình phát triển.
 * Khuyến nghị mạnh rằng các nhà phát triển plugin và theme nên sử dụng WP_DEBUG
 * trong môi trường phát triển của họ.
 *
 * Để biết thêm về các hằng số khác có thể dùng để debug,
 * hãy xem tài liệu.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Thêm các giá trị tùy chỉnh giữa dòng này và dòng "ngừng chỉnh sửa". */



/* Vậy là xong, ngừng chỉnh sửa! Chúc xuất bản vui vẻ. */

/** Đường dẫn tuyệt đối đến thư mục WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Thiết lập biến WordPress và các file được nạp. */
require_once ABSPATH . 'wp-settings.php';
