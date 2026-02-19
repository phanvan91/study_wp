# Phân Tích Cấu Trúc Source Code WordPress

Tài liệu hướng dẫn đọc và hiểu source code WordPress. Phân tích chi tiết cấu trúc thư mục, các file quan trọng, global objects, và design patterns.

---

## Mục Lục

1. [Tổng Quan Cấu Trúc Thư Mục Gốc WordPress](#1-tổng-quan-cấu-trúc-thư-mục-gốc-wordpress)
2. [Thư Mục wp-admin/](#2-thư-mục-wp-admin)
3. [Thư Mục wp-includes/](#3-thư-mục-wp-includes)
4. [Thư Mục wp-content/](#4-thư-mục-wp-content)
5. [Các File Cấu Hình Quan Trọng](#5-các-file-cấu-hình-quan-trọng)
6. [Các Global Objects Quan Trọng](#6-các-global-objects-quan-trọng)
7. [Các Design Patterns Trong WordPress](#7-các-design-patterns-trong-wordpress)
8. [Cách Đọc Source Code WordPress Hiệu Quả](#8-cách-đọc-source-code-wordpress-hiệu-quả)

---

## 1. Tổng Quan Cấu Trúc Thư Mục Gốc WordPress

### 1.1. Sơ Đồ Cấu Trúc

```
wordpress/
├── index.php
├── wp-activate.php
├── wp-blog-header.php
├── wp-comments-post.php
├── wp-config-sample.php
├── wp-cron.php
├── wp-links-opml.php
├── wp-load.php
├── wp-login.php
├── wp-mail.php
├── wp-settings.php
├── wp-signup.php
├── wp-trackback.php
├── xmlrpc.php
├── wp-admin/
├── wp-content/
└── wp-includes/
```

### 1.2. Giải Thích Từng File Gốc

#### index.php - Điểm Vào Chính (Entry Point)

Đây là file đầu tiên được web server gọi khi truy cập website. Nó không làm gì ngoài việc định nghĩa constant `WP_USE_THEMES` và load `wp-blog-header.php`.

```php
<?php
// Báo cho WordPress load theme và xuất ra.
define( 'WP_USE_THEMES', true );

// Load môi trường và template của WordPress.
require __DIR__ . '/wp-blog-header.php';
```

**Tại sao cần file này?** Vì web server (Apache, Nginx) mặc định tìm `index.php` trong thư mục gốc. File này đóng vai trò như "cửa trước" (front door) của ứng dụng.

#### wp-blog-header.php - Bộ Điều Phối Chính

File này thực hiện 3 bước quan trọng theo thứ tự:

```php
<?php
if ( ! isset( $wp_did_header ) ) {
    $wp_did_header = true;

    // Bước 1: Load toàn bộ thư viện WordPress
    require_once __DIR__ . '/wp-load.php';

    // Bước 2: Thiết lập WordPress query (phân tích URL, truy vấn database)
    wp();

    // Bước 3: Load template của theme để hiển thị
    require_once ABSPATH . WPINC . '/template-loader.php';
}
```

Biến `$wp_did_header` đảm bảo file chỉ được thực thi một lần duy nhất, tránh trường hợp load trùng lặp.

#### wp-load.php - Bootstrap File

File này làm hai việc chính:
- Định nghĩa hằng số `ABSPATH` (đường dẫn tuyệt đối đến thư mục WordPress)
- Tìm và load file `wp-config.php`

```php
<?php
// Định nghĩa ABSPATH là thư mục chứa file này
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

// Tìm wp-config.php ở thư mục hiện tại hoặc thư mục cha
if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
    require_once ABSPATH . 'wp-config.php';
} elseif ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
    // wp-config.php có thể đặt ở thư mục cha để bảo mật
    require_once dirname( ABSPATH ) . '/wp-config.php';
} else {
    // Hiển thị thông báo lỗi yêu cầu cài đặt
}
```

**Lưu ý quan trọng:** WordPress cho phép đặt `wp-config.php` ở thư mục cha của thư mục WordPress. Đây là kỹ thuật bảo mật để file cấu hình không nằm trong web root.

#### wp-settings.php - Quy Trình Khởi Động (Boot Sequence)

Đây là file quan trọng nhất trong quy trình khởi động. Nó load toàn bộ thư viện WordPress theo thứ tự cụ thể. Chi tiết sẽ được phân tích ở [Mục 5.3](#53-wp-settingsphp---boot-sequence).

#### wp-config-sample.php - Mẫu Cấu Hình

File mẫu để tạo `wp-config.php`. Chứa các thiết lập cơ bản:

```php
<?php
// Thiết lập database
define( 'DB_NAME', 'database_name_here' );
define( 'DB_USER', 'username_here' );
define( 'DB_PASSWORD', 'password_here' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );

// Khóa bảo mật (Security Keys)
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );

// Prefix bảng database
$table_prefix = 'wp_';

// Chế độ debug
define( 'WP_DEBUG', false );

// Load wp-settings.php để khởi động WordPress
require_once ABSPATH . 'wp-settings.php';
```

#### wp-login.php - Xử Lý Đăng Nhập

Xử lý toàn bộ quy trình xác thực người dùng:
- Hiển thị form đăng nhập
- Xử lý đăng nhập/đăng xuất
- Quên mật khẩu và đặt lại mật khẩu

```php
<?php
// Ví dụ: Hook vào quy trình đăng nhập
add_filter( 'authenticate', 'my_custom_auth', 30, 3 );
function my_custom_auth( $user, $username, $password ) {
    if ( is_blocked_ip( $_SERVER['REMOTE_ADDR'] ) ) {
        return new WP_Error( 'blocked', 'IP của bạn đã bị chặn.' );
    }
    return $user;
}

// Hook sau khi đăng nhập thành công
add_action( 'wp_login', 'my_after_login', 10, 2 );
function my_after_login( $user_login, $user ) {
    error_log( "User {$user_login} đã đăng nhập lúc " . current_time( 'mysql' ) );
}
```

#### wp-comments-post.php - Xử Lý Gửi Bình Luận

Nhận và xử lý bình luận từ form comment của người dùng. Kiểm tra spam, validation, và lưu vào database.

```php
<?php
// Ví dụ: Hook kiểm tra bình luận trước khi lưu
add_filter( 'preprocess_comment', 'my_check_comment' );
function my_check_comment( $commentdata ) {
    if ( strlen( $commentdata['comment_content'] ) < 10 ) {
        wp_die( 'Bình luận quá ngắn, vui lòng viết ít nhất 10 ký tự.' );
    }
    return $commentdata;
}
```

#### wp-cron.php - Hệ Thống Cron của WordPress

WordPress không sử dụng cron thực sự của hệ điều hành. Thay vào đó, mỗi khi có người truy cập site, WordPress kiểm tra xem có tác vụ nào cần chạy không.

```php
<?php
// Ví dụ: Đăng ký một cron event
add_action( 'my_daily_cleanup', 'do_daily_cleanup' );
function do_daily_cleanup() {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_timeout_%'
         AND option_value < UNIX_TIMESTAMP()"
    );
}

// Lên lịch chạy hàng ngày
if ( ! wp_next_scheduled( 'my_daily_cleanup' ) ) {
    wp_schedule_event( time(), 'daily', 'my_daily_cleanup' );
}
```

**Lưu ý:** Trong môi trường production, nên tắt WP-Cron và sử dụng system cron thay thế:

```php
// Trong wp-config.php
define( 'DISABLE_WP_CRON', true );
```

#### wp-activate.php - Kích Hoạt Tài Khoản

Sử dụng trong WordPress Multisite để kích hoạt tài khoản người dùng mới sau khi đăng ký.

#### wp-signup.php - Đăng Ký Tài Khoản

Xử lý đăng ký tài khoản mới trong WordPress Multisite. Hiển thị form đăng ký và xử lý dữ liệu.

#### wp-mail.php - Xử Lý Email Posting

Cho phép tạo bài viết thông qua email. Đây là tính năng cũ (legacy) và ít được sử dụng trong thực tế.

#### wp-links-opml.php - Xuất Liên Kết OPML

Xuất danh sách liên kết (blogroll) theo định dạng OPML. Đây là tính năng từ thời kỳ đầu của blogging.

#### wp-trackback.php - Xử Lý Trackback

Xử lý trackback từ các blog khác. Trackback là cơ chế thông báo khi một blog khác liên kết đến bài viết của bạn. Đây là tính năng cũ và nên tắt vì lý do bảo mật.

#### xmlrpc.php - XML-RPC API

Cung cấp API XML-RPC để các ứng dụng bên ngoài tương tác với WordPress. Nhiều chuyên gia bảo mật khuyên nên tắt file này nếu không sử dụng.

```php
<?php
// Tắt XML-RPC trong functions.php
add_filter( 'xmlrpc_enabled', '__return_false' );
```

---

## 2. Thư Mục wp-admin/

Thư mục `wp-admin/` chứa toàn bộ giao diện quản trị (admin dashboard) của WordPress.

### 2.1. Sơ Đồ Cấu Trúc wp-admin/

```
wp-admin/
├── admin.php              # Điểm vào chính của mỗi trang admin
├── admin-ajax.php         # Xử lý AJAX requests
├── admin-post.php         # Xử lý form POST từ admin
├── admin-header.php       # Header của trang admin
├── admin-footer.php       # Footer của trang admin
├── edit.php               # Danh sách bài viết
├── post.php               # Xử lý tạo/sửa bài viết
├── post-new.php           # Tạo bài viết mới
├── edit-tags.php          # Quản lý taxonomy terms
├── upload.php             # Thư viện media
├── options.php            # Xử lý lưu cấu hình
├── options-general.php    # Trang cấu hình chung
├── users.php              # Quản lý người dùng
├── plugins.php            # Quản lý plugins
├── themes.php             # Quản lý themes
├── widgets.php            # Quản lý widgets
├── nav-menus.php          # Quản lý menu
├── customize.php          # WordPress Customizer
├── includes/              # Các file hỗ trợ admin
├── css/                   # Stylesheets của admin
├── js/                    # JavaScript của admin
└── images/                # Hình ảnh của admin
```

### 2.2. Các File Quan Trọng

#### admin.php - Điểm Vào Admin

Mỗi trang trong admin panel đều load file này đầu tiên. Nó thực hiện:
- Load WordPress environment
- Kiểm tra quyền truy cập (authentication)
- Thiết lập admin context

```php
<?php
// Quy trình load của admin.php (đơn giản hóa):

// 1. Load WordPress
require_once dirname( __DIR__ ) . '/wp-load.php';

// 2. Kiểm tra người dùng đã đăng nhập chưa
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
    exit;
}

// 3. Load các thư viện admin
require_once ABSPATH . 'wp-admin/includes/admin.php';

// 4. Fire action để plugins có thể hook vào
do_action( 'admin_init' );
```

#### edit.php - Danh Sách Bài Viết (Post List)

Hiển thị danh sách bài viết dạng bảng (table) với các chức năng lọc, tìm kiếm, và thao tác hàng loạt.

```php
<?php
// Ví dụ: Thêm cột tùy chỉnh vào danh sách bài viết
add_filter( 'manage_posts_columns', 'my_custom_columns' );
function my_custom_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        if ( $key === 'title' ) {
            $new_columns['views'] = 'Lượt Xem';
        }
    }
    return $new_columns;
}

add_action( 'manage_posts_custom_column', 'my_custom_column_data', 10, 2 );
function my_custom_column_data( $column, $post_id ) {
    if ( $column === 'views' ) {
        $views = get_post_meta( $post_id, '_post_views', true );
        echo $views ? number_format( $views ) : '0';
    }
}
```

#### post.php và post-new.php - Tạo/Sửa Bài Viết

`post-new.php` tạo bài viết mới (auto-draft), `post.php` xử lý việc lưu và cập nhật bài viết.

```php
<?php
// Ví dụ: Hook vào quy trình lưu bài viết
add_action( 'save_post', 'my_save_post_handler', 10, 3 );
function my_save_post_handler( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['my_custom_field'] ) ) {
        update_post_meta(
            $post_id,
            '_my_custom_field',
            sanitize_text_field( $_POST['my_custom_field'] )
        );
    }
}
```

#### options.php - Xử Lý Lưu Cấu Hình

Nhận dữ liệu POST từ các trang cấu hình và lưu vào bảng `wp_options`.

```php
<?php
// Ví dụ: Đăng ký trang cấu hình tùy chỉnh
add_action( 'admin_menu', 'my_options_page' );
function my_options_page() {
    add_options_page(
        'Cấu Hình Plugin',
        'Plugin Của Tôi',
        'manage_options',
        'my-plugin-settings',
        'my_options_page_html'
    );
}

add_action( 'admin_init', 'my_settings_init' );
function my_settings_init() {
    register_setting( 'my_plugin_group', 'my_plugin_options' );

    add_settings_section(
        'my_plugin_section',
        'Cấu Hình Chung',
        'my_section_callback',
        'my-plugin-settings'
    );

    add_settings_field(
        'api_key',
        'API Key',
        'my_api_key_field_callback',
        'my-plugin-settings',
        'my_plugin_section'
    );
}
```

#### users.php - Quản Lý Người Dùng

Hiển thị danh sách người dùng, cho phép thêm/sửa/xóa và phân quyền.

```php
<?php
// Ví dụ: Thêm custom field vào trang profile người dùng
add_action( 'show_user_profile', 'my_user_profile_fields' );
add_action( 'edit_user_profile', 'my_user_profile_fields' );
function my_user_profile_fields( $user ) {
    $phone = get_user_meta( $user->ID, 'phone_number', true );
    ?>
    <h3>Thông Tin Bổ Sung</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone_number">Số Điện Thoại</label></th>
            <td>
                <input type="text" name="phone_number" id="phone_number"
                       value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', 'my_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_user_profile_fields' );
function my_save_user_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'phone_number', sanitize_text_field( $_POST['phone_number'] ) );
}
```

#### plugins.php - Quan Ly Plugins

Hiển thị danh sách plugins đã cài đặt. Cho phép kích hoạt, vô hiệu hóa, cập nhật, và xóa plugins.

```php
<?php
// Ví dụ: Thêm liên kết tùy chỉnh vào trang plugins
add_filter( 'plugin_action_links_my-plugin/my-plugin.php', 'my_plugin_links' );
function my_plugin_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=my-plugin-settings' ) . '">Cấu Hình</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
```

#### themes.php - Quan Ly Themes

Hiển thị các theme đã cài đặt, cho phép kích hoạt, xem trước, và cài đặt theme mới.

#### admin-ajax.php - Xu Ly AJAX

Đây là endpoint chính cho tất cả các AJAX request trong WordPress admin (và cả frontend). Mỗi AJAX request cần gửi kèm tham số `action`.

```php
<?php
// PHÍA SERVER: Đăng ký AJAX handler
add_action( 'wp_ajax_my_action', 'my_ajax_handler' );        // User đã đăng nhập
add_action( 'wp_ajax_nopriv_my_action', 'my_ajax_handler' ); // User chưa đăng nhập

function my_ajax_handler() {
    check_ajax_referer( 'my_nonce_action', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Không có quyền.' );
    }

    $post_id = intval( $_POST['post_id'] );
    $result  = update_post_meta( $post_id, '_liked', true );

    if ( $result ) {
        wp_send_json_success( array( 'message' => 'Đã thích bài viết!' ) );
    } else {
        wp_send_json_error( 'Không thể xử lý.' );
    }
}

// Đăng ký script và truyền biến sang JavaScript
add_action( 'wp_enqueue_scripts', 'my_enqueue_ajax_scripts' );
function my_enqueue_ajax_scripts() {
    wp_enqueue_script( 'my-ajax-script', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array( 'jquery' ) );
    wp_localize_script( 'my-ajax-script', 'my_plugin_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'my_nonce_action' ),
    ) );
}
```

### 2.3. Thu Muc wp-admin/includes/

Chứa các file hỗ trợ cho admin:

```
wp-admin/includes/
├── admin.php                  # Load các file admin utilities
├── class-wp-list-table.php    # Class hiển thị bảng dữ liệu
├── class-wp-screen.php        # Class quản lý màn hình admin
├── dashboard.php              # Các widget dashboard
├── file.php                   # Xử lý file (upload, edit)
├── image.php                  # Xử lý hình ảnh
├── media.php                  # Thư viện media
├── plugin.php                 # Utilities cho plugin management
├── post.php                   # Utilities cho post management
├── schema.php                 # Cấu trúc database
├── template.php               # Template functions cho admin
├── upgrade.php                # Xử lý nâng cấp WordPress
└── user.php                   # Utilities cho user management
```

---

## 3. Thu Muc wp-includes/

Thư mục `wp-includes/` là **core library** của WordPress. Chứa tất cả các class, function, và API mà WordPress sử dụng.

**Nguyên tắc:** KHÔNG BAO GIỜ sửa trực tiếp các file trong `wp-includes/`. Mọi thay đổi sẽ bị mất khi cập nhật WordPress. Sử dụng hooks để tùy chỉnh hành vi.

### 3.1. Cac Class Chinh

#### class-wp.php - Lop WordPress Chinh

Class `WP` là trung tâm điều phối của WordPress. Nó xử lý việc phân tích URL request, thiết lập query variables, gửi headers, và thực hiện main query.

```php
<?php
// Cấu trúc đơn giản hóa của class WP:
class WP {
    public $public_query_vars = array(
        'm', 'p', 'posts', 'w', 'cat', 's', 'search',
        'author', 'year', 'monthnum', 'day', 'name',
        'category_name', 'tag', 'feed', 'page', 'paged',
        'post_type', 'pagename', 'page_id', 'error'
    );

    public $private_query_vars = array(
        'offset', 'posts_per_page', 'post_status',
        'category__in', 'tag__in', 'post__in',
        'post_mime_type', 'fields'
    );

    public $query_vars   = array();
    public $query_string = '';
    public $request      = '';
    public $matched_rule = '';

    public function main( $query_args = '' ) {
        $this->init();
        $this->parse_request();    // Phân tích URL
        $this->send_headers();     // Gửi HTTP headers
        $this->query_posts();      // Truy vấn posts
        $this->handle_404();       // Xử lý lỗi 404
        $this->register_globals();
        do_action_ref_array( 'wp', array( &$this ) );
    }
}

// Hàm global wp() gọi class này:
function wp( $query_vars = '' ) {
    global $wp;
    $wp->main( $query_vars );
}
```

#### class-wp-query.php - Lop Truy Van

Class `WP_Query` la trai tim cua he thong truy van WordPress. No chuyen doi cac tham so truy van thanh SQL va tra ve ket qua.

```php
<?php
// Vi du 1: Truy van co ban
$query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'category_name'  => 'tin-tuc',
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<h2>' . get_the_title() . '</h2>';
        echo '<div>' . get_the_excerpt() . '</div>';
    }
    wp_reset_postdata();
}

// Vi du 2: Truy van phuc tap voi meta query
$products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'     => '_price',
            'value'   => array( 100000, 500000 ),
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ),
        array(
            'key'     => '_stock_status',
            'value'   => 'instock',
        ),
    ),
    'tax_query' => array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => 'dien-thoai',
        ),
    ),
) );

// Vi du 3: Thay doi main query thong qua hook
add_action( 'pre_get_posts', 'my_modify_main_query' );
function my_modify_main_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        $query->set( 'posts_per_page', 20 );
        $query->set( 'category__not_in', array( 5 ) );
    }
}
```

#### class-wp-user.php - Lop Nguoi Dung

Đại diện cho một người dùng trong hệ thống. Chứa thông tin cá nhân, vai trò, và quyền hạn.

```php
<?php
$user = new WP_User( 1 );
echo $user->user_login;
echo $user->user_email;
echo $user->display_name;

if ( $user->has_cap( 'edit_posts' ) ) {
    echo 'Người dùng có quyền chỉnh sửa bài viết.';
}

// Các vai trò mặc định của WordPress:
// - administrator: Toàn quyền
// - editor: Quản lý nội dung
// - author: Viết và quản lý bài viết của mình
// - contributor: Viết bài nhưng không được xuất bản
// - subscriber: Chỉ đọc

// Ví dụ: Tạo vai trò tùy chỉnh
add_role( 'shop_manager', 'Quản Lý Cửa Hàng', array(
    'read'           => true,
    'edit_posts'     => true,
    'delete_posts'   => true,
    'publish_posts'  => true,
    'upload_files'   => true,
) );
```

#### class-wp-post.php - Lop Bai Viet

Đại diện cho một bài viết (post, page, hoặc bất kỳ custom post type nào).

```php
<?php
// Các thuộc tính của WP_Post:
// $post->ID              - ID bài viết
// $post->post_author     - ID tác giả
// $post->post_date       - Ngày tạo
// $post->post_content    - Nội dung
// $post->post_title      - Tiêu đề
// $post->post_excerpt    - Tóm tắt
// $post->post_status     - Trạng thái (publish, draft, private, pending...)
// $post->post_type       - Loại (post, page, attachment, custom...)
// $post->post_name       - Slug URL
// $post->post_parent     - ID bài viết cha
// $post->menu_order      - Thứ tự hiển thị

// Ví dụ: Đăng ký Custom Post Type
add_action( 'init', 'register_product_post_type' );
function register_product_post_type() {
    register_post_type( 'product', array(
        'labels' => array(
            'name'          => 'Sản Phẩm',
            'singular_name' => 'Sản Phẩm',
            'add_new'       => 'Thêm Mới',
            'add_new_item'  => 'Thêm Sản Phẩm Mới',
            'edit_item'     => 'Chỉnh Sửa Sản Phẩm',
        ),
        'public'       => true,
        'has_archive'  => true,
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'      => array( 'slug' => 'san-pham' ),
        'menu_icon'    => 'dashicons-cart',
        'show_in_rest' => true,
    ) );
}
```

#### class-wp-rewrite.php - Lop URL Rewrite

Quản lý việc chuyển đổi URL đẹp (pretty permalinks) thành các tham số truy vấn.

```php
<?php
// Ví dụ: Thêm rewrite rule tùy chỉnh
add_action( 'init', 'my_custom_rewrite_rules' );
function my_custom_rewrite_rules() {
    add_rewrite_rule(
        'san-pham/([^/]+)/([^/]+)/?$',
        'index.php?post_type=product&product_cat=$matches[1]&name=$matches[2]',
        'top'
    );
    add_rewrite_tag( '%product_cat%', '([^/]+)' );
}
// Sau khi thêm rewrite rule, cần flush:
// Vào Settings > Permalinks và nhấn Save
```

#### class-wp-hook.php - Lop Hook (Nen Tang Cua Plugin API)

Class này implement toàn bộ hệ thống hook (action và filter) của WordPress.

```php
<?php
// Cấu trúc của WP_Hook (đơn giản hóa):
final class WP_Hook implements Iterator, ArrayAccess {
    public $callbacks = array();
    // Cấu trúc:
    // array(
    //     10 => array(
    //         'my_function' => array(
    //             'function'      => 'my_function',
    //             'accepted_args' => 1,
    //         ),
    //     ),
    //     20 => array( ... ),
    // )

    public function add_filter( $hook_name, $callback, $priority, $accepted_args ) { }
    public function apply_filters( $value, $args ) { }
}

// Ví dụ thực tế:
// 1. Đăng ký filter
add_filter( 'the_content', 'my_add_disclaimer', 20 );
function my_add_disclaimer( $content ) {
    if ( is_single() ) {
        $content .= '<p><em>Bài viết chỉ mang tính tham khảo.</em></p>';
    }
    return $content;
}

// 2. Tạo hook tùy chỉnh trong plugin/theme
function my_process_order( $order_id ) {
    $order_data = get_order_data( $order_id );
    $order_data = apply_filters( 'my_plugin_order_data', $order_data, $order_id );
    do_action( 'my_plugin_order_processed', $order_id, $order_data );
}
```

#### class-wpdb.php - Lop Database

Class `wpdb` là lớp trừu tượng hóa database của WordPress.

```php
<?php
global $wpdb;

// 1. Truy vấn an toàn với prepare()
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s LIMIT %d",
        'product', 'publish', 10
    )
);

// 2. Lấy một dòng
$user = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$wpdb->users} WHERE user_email = %s", 'user@example.com' )
);

// 3. Lấy một giá trị
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish'" );

// 4. Chèn dữ liệu
$wpdb->insert(
    $wpdb->prefix . 'my_custom_table',
    array( 'name' => 'Sản phẩm mới', 'price' => 150000, 'created_at' => current_time( 'mysql' ) ),
    array( '%s', '%d', '%s' )
);
$new_id = $wpdb->insert_id;

// 5. Cập nhật dữ liệu
$wpdb->update(
    $wpdb->prefix . 'my_custom_table',
    array( 'price' => 200000 ),
    array( 'id' => $new_id ),
    array( '%d' ),
    array( '%d' )
);

// 6. Xóa dữ liệu
$wpdb->delete( $wpdb->prefix . 'my_custom_table', array( 'id' => $new_id ), array( '%d' ) );

// 7. Tạo bảng tùy chỉnh
function my_create_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'my_custom_table';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL DEFAULT '',
        price bigint(20) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        KEY price (price)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'my_create_table' );
```

### 3.2. REST API

#### rest-api.php va class-wp-rest-server.php

WordPress REST API cho phép tương tác với WordPress thông qua HTTP requests, trả về dữ liệu JSON.

```php
<?php
// Đăng ký REST API endpoint tùy chỉnh
add_action( 'rest_api_init', 'my_register_rest_routes' );
function my_register_rest_routes() {
    // GET /wp-json/my-plugin/v1/products
    register_rest_route( 'my-plugin/v1', '/products', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'my_get_products',
        'permission_callback' => '__return_true',
        'args' => array(
            'per_page' => array(
                'default'           => 10,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0 && $param <= 100;
                },
            ),
        ),
    ) );

    // GET /wp-json/my-plugin/v1/products/(?P<id>\d+)
    register_rest_route( 'my-plugin/v1', '/products/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'my_get_single_product',
        'permission_callback' => '__return_true',
    ) );
}

function my_get_products( WP_REST_Request $request ) {
    $per_page = $request->get_param( 'per_page' );
    $query    = new WP_Query( array(
        'post_type'      => 'product',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
    ) );

    $products = array();
    foreach ( $query->posts as $post ) {
        $products[] = array(
            'id'    => $post->ID,
            'title' => $post->post_title,
            'price' => get_post_meta( $post->ID, '_price', true ),
        );
    }
    return new WP_REST_Response( $products, 200 );
}

function my_get_single_product( WP_REST_Request $request ) {
    $post = get_post( $request->get_param( 'id' ) );
    if ( ! $post || $post->post_type !== 'product' ) {
        return new WP_Error( 'not_found', 'Sản phẩm không tồn tại.', array( 'status' => 404 ) );
    }
    return new WP_REST_Response( array(
        'id'      => $post->ID,
        'title'   => $post->post_title,
        'content' => apply_filters( 'the_content', $post->post_content ),
    ), 200 );
}
```

### 3.3. Formatting va Security

#### formatting.php - Xử Lý Định Dạng Văn Bản

```php
<?php
// Escape dữ liệu để ngăn XSS
$safe = esc_html( '<script>alert("XSS")</script>' );
echo '<input value="' . esc_attr( $user_input ) . '">';
echo '<a href="' . esc_url( $url ) . '">Liên kết</a>';

// Làm sạch dữ liệu đầu vào
$clean = sanitize_text_field( $_POST['name'] );
$email = sanitize_email( $_POST['email'] );
$file  = sanitize_file_name( $_POST['filename'] );
$slug  = sanitize_title( 'Tiêu Đề Bài Viết Tiếng Việt' );

// Tự động tạo đoạn văn
$formatted = wpautop( "Dòng 1\n\nDòng 2\nDòng 3" );
```

#### kses.php - HTML Filtering (Security)

KSES (KSES Strips Evil Scripts) lọc HTML để ngăn chặn XSS attack.

```php
<?php
$allowed = array(
    'a'      => array( 'href' => true, 'title' => true ),
    'strong' => array(),
    'em'     => array(),
    'p'      => array( 'class' => true ),
);
$safe_html = wp_kses( $user_html, $allowed );

// Sử dụng các preset có sẵn:
$post_safe = wp_kses_post( $html );
```

### 3.4. Plugin API

#### plugin.php - API Cho Plugin System

```php
<?php
// FILTERS - Thay đổi dữ liệu
add_filter( $hook_name, $callback, $priority, $accepted_args );
apply_filters( $hook_name, $value, ...$args );

// ACTIONS - Thực thi hành động
add_action( $hook_name, $callback, $priority, $accepted_args );
do_action( $hook_name, ...$args );

// PLUGIN MANAGEMENT
register_activation_hook( $file, $callback );
register_deactivation_hook( $file, $callback );
register_uninstall_hook( $file, $callback );

// Ví dụ: Vòng đời của một plugin
register_activation_hook( __FILE__, function() {
    add_option( 'my_plugin_version', '1.0.0' );
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'my_plugin_cron' );
    flush_rewrite_rules();
} );
```

### 3.5. Template System

#### template.php va template-loader.php

```php
<?php
// Thứ bậc template của WordPress (template hierarchy):
// Trang chủ:     front-page.php -> home.php -> index.php
// Bài viết đơn:  single-{type}-{slug}.php -> single-{type}.php -> single.php -> singular.php -> index.php
// Trang:         {custom}.php -> page-{slug}.php -> page-{id}.php -> page.php -> singular.php -> index.php
// Danh mục:      category-{slug}.php -> category-{id}.php -> category.php -> archive.php -> index.php
// Tag:           tag-{slug}.php -> tag-{id}.php -> tag.php -> archive.php -> index.php
// Tìm kiếm:      search.php -> index.php
// 404:           404.php -> index.php

// Logic của template-loader.php (đơn giản hóa):
if ( is_404() ) {
    $template = get_404_template();
} elseif ( is_search() ) {
    $template = get_search_template();
} elseif ( is_single() ) {
    $template = get_single_template();
} elseif ( is_page() ) {
    $template = get_page_template();
} elseif ( is_category() ) {
    $template = get_category_template();
} elseif ( is_archive() ) {
    $template = get_archive_template();
}

$template = apply_filters( 'template_include', $template );
include $template;
```

### 3.6. Taxonomy

#### taxonomy.php - He Thong Phan Loai

```php
<?php
// Đăng ký Custom Taxonomy
add_action( 'init', 'register_product_taxonomy' );
function register_product_taxonomy() {
    register_taxonomy( 'product_cat', 'product', array(
        'labels' => array(
            'name'          => 'Danh Mục Sản Phẩm',
            'singular_name' => 'Danh Mục',
            'add_new_item'  => 'Thêm Danh Mục Mới',
        ),
        'hierarchical'      => true,   // true = như Category, false = như Tag
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => array( 'slug' => 'danh-muc-san-pham' ),
    ) );
}

$terms     = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$post_terms = wp_get_post_terms( $post_id, 'product_cat' );
wp_set_post_terms( $post_id, array( 5, 10 ), 'product_cat' );
```

### 3.7. User va Capabilities

#### user.php va capabilities.php

```php
<?php
// Kiểm tra đăng nhập
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    echo 'Xin chào, ' . $current_user->display_name;
}

// Tạo người dùng mới
$user_id = wp_insert_user( array(
    'user_login' => 'newuser',
    'user_pass'  => wp_generate_password(),
    'user_email' => 'new@example.com',
    'role'       => 'author',
) );

// Kiểm tra quyền
if ( current_user_can( 'manage_options' ) ) {
    echo 'Bạn là Administrator.';
}
if ( current_user_can( 'edit_post', $post_id ) ) {
    echo 'Bạn có thể chỉnh sửa bài viết này.';
}

// Thêm quyền tùy chỉnh cho vai trò
$role = get_role( 'editor' );
$role->add_cap( 'manage_products' );
```

### 3.8. HTTP API

#### class-wp-http.php - Xử Lý HTTP Requests

```php
<?php
// GET request
$response = wp_remote_get( 'https://api.example.com/data' );
if ( is_wp_error( $response ) ) {
    echo 'Lỗi: ' . $response->get_error_message();
} else {
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
}

// POST request với headers
$response = wp_remote_post( 'https://api.example.com/orders', array(
    'headers' => array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_token,
    ),
    'body'    => wp_json_encode( array( 'product_id' => 123, 'quantity' => 2 ) ),
    'timeout' => 30,
) );
```

### 3.9. Cache System

#### cache.php va class-wp-object-cache.php

```php
<?php
// Object Cache cơ bản
wp_cache_set( 'my_key', $data, 'my_group', 3600 );
$data = wp_cache_get( 'my_key', 'my_group' );
wp_cache_delete( 'my_key', 'my_group' );

// Transient API - Cache lưu trong database
set_transient( 'my_api_data', $api_data, 12 * HOUR_IN_SECONDS );
$cached = get_transient( 'my_api_data' );
if ( false === $cached ) {
    $cached = my_fetch_api_data();
    set_transient( 'my_api_data', $cached, 12 * HOUR_IN_SECONDS );
}

// Ví dụ: Cache kết quả truy vấn phức tạp
function get_popular_posts( $count = 5 ) {
    $cache_key = 'popular_posts_' . $count;
    $posts     = get_transient( $cache_key );

    if ( false === $posts ) {
        $posts = new WP_Query( array(
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'meta_key'       => '_post_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ) );
        set_transient( $cache_key, $posts, HOUR_IN_SECONDS );
    }
    return $posts;
}

// Xóa cache khi có bài viết mới
add_action( 'save_post', function() {
    delete_transient( 'popular_posts_5' );
    delete_transient( 'popular_posts_10' );
} );
```

### 3.10. Block Editor (Gutenberg)

#### blocks.php và block-patterns.php

```php
<?php
// Đăng ký block tùy chỉnh (phía server)
add_action( 'init', 'my_register_blocks' );
function my_register_blocks() {
    register_block_type( __DIR__ . '/blocks/my-block', array(
        'render_callback' => 'my_block_render',
    ) );
}

function my_block_render( $attributes, $content ) {
    $title = $attributes['title'] ?? 'Tiêu đề mặc định';
    return sprintf(
        '<div class="my-custom-block"><h3>%s</h3><div>%s</div></div>',
        esc_html( $title ),
        wp_kses_post( $content )
    );
}

// Đăng ký block pattern
add_action( 'init', 'my_register_patterns' );
function my_register_patterns() {
    register_block_pattern( 'my-plugin/hero-section', array(
        'title'       => 'Hero Section',
        'description' => 'Phần hero với hình nền và tiêu đề.',
        'categories'  => array( 'featured' ),
        'content'     => '<!-- wp:cover {"overlayColor":"primary"} -->
            <div class="wp-block-cover">
                <div class="wp-block-cover__inner-container">
                    <!-- wp:heading {"textAlign":"center","level":1} -->
                    <h1 class="has-text-align-center">Chào Mừng</h1>
                    <!-- /wp:heading -->
                </div>
            </div>
            <!-- /wp:cover -->',
    ) );
}
```

---

## 4. Thu Muc wp-content/

Thư mục `wp-content/` là nơi duy nhất mà người dùng nên thay đổi.

### 4.1. Cau Truc

```
wp-content/
├── plugins/           # Cac plugin da cai dat
├── themes/            # Cac theme da cai dat
├── uploads/           # File media do nguoi dung tai len
│   ├── 2024/
│   │   ├── 01/
│   │   ├── 02/
│   │   └── ...
│   └── 2025/
├── mu-plugins/        # Must-Use Plugins (tu dong kich hoat)
├── languages/         # Cac file ngon ngu (.mo, .po)
├── upgrade/           # Thu muc tam khi nang cap
└── index.php          # File bao mat
```

### 4.2. Thu Muc plugins/

Mỗi plugin nằm trong thư mục riêng:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: Mô tả ngắn gọn về plugin.
 * Version:     1.0.0
 * Author:      Tên Tác Giả
 * Text Domain: my-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MY_PLUGIN_VERSION', '1.0.0' );
define( 'MY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MY_PLUGIN_DIR . 'includes/class-my-plugin.php';

add_action( 'plugins_loaded', function() {
    $plugin = new My_Plugin();
    $plugin->run();
} );
```

### 4.3. Thu Muc themes/

Cấu trúc cơ bản của một theme:

```
themes/my-theme/
├── style.css              # File bat buoc - chua theme header
├── functions.php          # File cau hinh theme
├── index.php              # Template mac dinh (bat buoc)
├── header.php             # Header template
├── footer.php             # Footer template
├── sidebar.php            # Sidebar template
├── single.php             # Template bai viet don
├── page.php               # Template trang
├── archive.php            # Template trang archive
├── search.php             # Template ket qua tim kiem
├── 404.php                # Template trang 404
├── template-parts/        # Cac phan template tai su dung
└── assets/                # CSS, JS, images
```

### 4.4. Thu Muc uploads/

Luu tru file media do nguoi dung tai len, to chuc theo nam/thang.

```php
<?php
$upload_dir = wp_upload_dir();
// $upload_dir['basedir'] = /path/to/wp-content/uploads
// $upload_dir['baseurl'] = https://example.com/wp-content/uploads
// $upload_dir['path']    = /path/to/wp-content/uploads/2025/01
// $upload_dir['url']     = https://example.com/wp-content/uploads/2025/01
```

### 4.5. Thu Muc mu-plugins/ (Must-Use Plugins)

Các plugin trong thư mục này tự động kích hoạt và KHÔNG THỂ vô hiệu hóa từ giao diện admin.

```php
<?php
// File: wp-content/mu-plugins/security-headers.php
add_action( 'send_headers', function() {
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-XSS-Protection: 1; mode=block' );
} );
```

### 4.6. Thu Muc languages/

Chứa các file dịch ngôn ngữ:
- `.po` - file nguồn có thể đọc được (Portable Object)
- `.mo` - file đã biên dịch (Machine Object)

```php
<?php
load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

echo __( 'Xin chào', 'my-plugin' );
_e( 'Xin chào', 'my-plugin' );
echo _n( '%d bài viết', '%d bài viết', $count, 'my-plugin' );
```

---

## 5. Cac File Cau Hinh Quan Trong

### 5.1. wp-config.php - File Cau Hinh Chinh

#### Các Constant Database

```php
<?php
define( 'DB_NAME', 'wordpress_db' );
define( 'DB_USER', 'wp_user' );
define( 'DB_PASSWORD', 'strong_pass' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wp_';
```

#### Các Constant Bảo Mật

```php
<?php
define( 'AUTH_KEY',         'khoa-duy-nhat-1' );
define( 'SECURE_AUTH_KEY',  'khoa-duy-nhat-2' );
define( 'LOGGED_IN_KEY',    'khoa-duy-nhat-3' );
define( 'NONCE_KEY',        'khoa-duy-nhat-4' );
define( 'AUTH_SALT',        'muoi-duy-nhat-1' );
define( 'SECURE_AUTH_SALT', 'muoi-duy-nhat-2' );
define( 'LOGGED_IN_SALT',   'muoi-duy-nhat-3' );
define( 'NONCE_SALT',       'muoi-duy-nhat-4' );

define( 'FORCE_SSL_ADMIN', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );
```

#### Các Constant Debug

```php
<?php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', true );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );
```

#### Các Constant Hiệu Suất

```php
<?php
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'DISABLE_WP_CRON', true );
define( 'WP_POST_REVISIONS', 5 );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_CACHE', true );
```

#### Các Constant URL

```php
<?php
define( 'WP_HOME', 'https://example.com' );
define( 'WP_SITEURL', 'https://example.com' );
define( 'WP_CONTENT_DIR', '/path/to/custom-content' );
define( 'WP_CONTENT_URL', 'https://example.com/custom-content' );
```

### 5.2. .htaccess - Cau Hinh Apache

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress

# Chan truy cap wp-config.php
<Files wp-config.php>
    Order allow,deny
    Deny from all
</Files>

# Chan duyet thu muc
Options -Indexes
```

**Logic:** Neu file hoac thu muc thuc khong ton tai tren server, chuyen tat ca request ve `index.php` de WordPress xu ly routing.

### 5.3. wp-settings.php - Boot Sequence

Đây là file quan trọng nhất trong quy trình khởi động. Quy trình chi tiết:

```
wp-settings.php Boot Sequence:
|
|-- 1. Định nghĩa WPINC = 'wp-includes'
|-- 2. Load version.php, compat.php, load.php
|-- 3. Kiểm tra phiên bản PHP và MySQL
|-- 4. Load recovery mode classes
|-- 5. Load default-constants.php
|-- 6. Load plugin.php (Hook API có sẵn từ đây)
|
|-- 7. wp_initial_constants() - WP_MEMORY_LIMIT, WP_DEBUG, WP_CONTENT_DIR
|-- 8. wp_register_fatal_error_handler()
|-- 9. date_default_timezone_set('UTC')
|-- 10. wp_maintenance() - Kiểm tra chế độ bảo trì
|
|-- 11. Load các file sớm: formatting.php, functions.php, class-wp.php, class-wp-error.php
|-- 12. require_wp_db() - Load class wpdb, kết nối database
|-- 13. wp_start_object_cache()
|-- 14. Load default-filters.php
|
|-- ** Nếu SHORTINIT = true -> DỪNG Ở ĐÂY **
|
|-- 15. Load L10n (ngôn ngữ)
|-- 16. wp_not_installed() - Kiểm tra đã cài đặt chưa
|
|-- 17. Load PHẦN LỚN WordPress:
|       capabilities.php, class-wp-roles.php, class-wp-user.php,
|       class-wp-query.php, theme.php, template.php,
|       user.php, post.php, taxonomy.php, rewrite.php,
|       kses.php, shortcodes.php, media.php, http.php,
|       class-wp-http.php, widgets.php, nav-menu.php,
|       rest-api.php + tất cả REST endpoints,
|       blocks.php, block-patterns.php
|
|-- 18. do_action('muplugins_loaded')  -- MU-plugins đã sẵn sàng
|-- 19. Load các active plugins
|-- 20. Load pluggable.php
|-- 21. do_action('plugins_loaded')    -- Tất cả plugins đã load
|
|-- 22. Tạo global objects: $wp, $wp_rewrite, $wp_roles
|-- 23. do_action('setup_theme')
|-- 24. Load theme functions.php
|-- 25. do_action('after_setup_theme') -- Theme đã sẵn sàng
|
|-- 26. Tạo $wp_the_query, $wp_query
|-- 27. do_action('init')              -- MỌI THỨ ĐÃ SẴN SÀNG
|-- 28. do_action('wp_loaded')         -- WORDPRESS ĐÃ LOAD XONG
```

**Thứ tự các action hooks chính:**

```php
<?php
// 1. muplugins_loaded  - Sau khi MU-plugins load
// 2. plugins_loaded    - Sau khi tất cả plugins load
// 3. setup_theme       - Trước khi theme load
// 4. after_setup_theme - Sau khi theme load
// 5. init              - Mọi thứ đã sẵn sàng
// 6. wp_loaded         - WordPress đã hoàn tất load
// 7. wp                - Sau khi parse request và query (chỉ frontend)
// 8. template_redirect - Trước khi chọn template (chỉ frontend)
// 9. wp_head           - Trong <head>
// 10. wp_footer        - Trước </body>
// 11. shutdown         - Sau khi output đã gửi

// Ví dụ: Chọn hook phù hợp
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    register_nav_menus( array( 'primary' => 'Menu Chính' ) );
} );

add_action( 'init', function() {
    // Đăng ký post types, taxonomies
} );

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'my-style', get_stylesheet_uri() );
    wp_enqueue_script( 'my-script', get_template_directory_uri() . '/js/main.js' );
} );
```

---

## 6. Cac Global Objects Quan Trong

WordPress sử dụng nhiều biến global để lưu trữ trạng thái và cung cấp truy cập đến các hệ thống con.

### 6.1. $wpdb - Database Object

```php
<?php
global $wpdb;

echo $wpdb->prefix;        // 'wp_'
echo $wpdb->posts;         // 'wp_posts'
echo $wpdb->postmeta;      // 'wp_postmeta'
echo $wpdb->users;         // 'wp_users'
echo $wpdb->usermeta;      // 'wp_usermeta'
echo $wpdb->options;       // 'wp_options'
echo $wpdb->comments;      // 'wp_comments'
echo $wpdb->terms;         // 'wp_terms'
echo $wpdb->term_taxonomy; // 'wp_term_taxonomy'

// Kiểm tra lỗi truy vấn cuối cùng
if ( $wpdb->last_error ) {
    error_log( 'Database error: ' . $wpdb->last_error );
    error_log( 'Query: ' . $wpdb->last_query );
}
echo $wpdb->rows_affected;
echo $wpdb->insert_id;
```

### 6.2. $wp_query - Query Object Chinh

```php
<?php
global $wp_query;

// Kiểm tra loại trang hiện tại
$wp_query->is_home();
$wp_query->is_front_page();
$wp_query->is_single();
$wp_query->is_page();
$wp_query->is_archive();
$wp_query->is_category();
$wp_query->is_search();
$wp_query->is_404();

echo $wp_query->found_posts;    // Tổng số bài viết tìm thấy
echo $wp_query->max_num_pages;  // Tổng số trang
echo $wp_query->post_count;     // Số bài viết trong trang hiện tại

$posts      = $wp_query->posts;
$query_vars = $wp_query->query_vars;
```

### 6.3. $wp - WordPress Environment Object

```php
<?php
global $wp;

echo $wp->request;       // Đường dẫn request (ví dụ: '2025/01/hello-world')
echo $wp->query_string;  // Query string đã phân tích
echo $wp->matched_rule;  // Rewrite rule đã khớp
echo $wp->matched_query; // Query đã khớp

// Them custom query variable
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'my_custom_var';
    return $vars;
} );
// Truy cap: get_query_var( 'my_custom_var' )
```

### 6.4. $wp_rewrite - Rewrite Object

```php
<?php
global $wp_rewrite;

echo $wp_rewrite->permalink_structure;
// Vi du: '/%year%/%monthnum%/%day%/%postname%/'

if ( $wp_rewrite->using_permalinks() ) {
    echo 'Đang sử dụng pretty permalinks.';
}

$rules = $wp_rewrite->wp_rewrite_rules();
// Mang: regex => query
// Vi du: 'category/(.+?)/?$' => 'index.php?category_name=$matches[1]'
```

### 6.5. $wp_roles - Roles Object

```php
<?php
global $wp_roles;

$roles = $wp_roles->get_names();
// array( 'administrator' => 'Administrator', 'editor' => 'Editor', ... )

$admin_role = $wp_roles->get_role( 'administrator' );
// $admin_role->capabilities = array( 'switch_themes' => true, ... )

$wp_roles->add_role( 'custom_role', 'Vai trò Tùy Chỉnh', array(
    'read'       => true,
    'edit_posts' => true,
) );
$wp_roles->add_cap( 'editor', 'manage_custom_content' );
```

### 6.6. $wp_filter va $wp_actions - Hook System

```php
<?php
global $wp_filter, $wp_actions;

// $wp_filter chứa TẤT CẢ các hooks (cả actions và filters)
// $wp_actions đếm số lần mỗi action được gọi

// Kiểm tra một hook có callbacks nào không
if ( isset( $wp_filter['the_content'] ) ) {
    $callbacks = $wp_filter['the_content']->callbacks;
    foreach ( $callbacks as $priority => $hooks ) {
        echo "Priority $priority:\n";
        foreach ( $hooks as $id => $hook ) {
            if ( is_array( $hook['function'] ) ) {
                if ( is_object( $hook['function'][0] ) ) {
                    echo '  - ' . get_class( $hook['function'][0] ) . '::' . $hook['function'][1] . "\n";
                } else {
                    echo '  - ' . $hook['function'][0] . '::' . $hook['function'][1] . "\n";
                }
            } else {
                echo '  - ' . $hook['function'] . "\n";
            }
        }
    }
}

// Kiểm tra một action đã được gọi chưa
if ( did_action( 'init' ) ) {
    echo 'Action init đã được gọi ' . $wp_actions['init'] . ' lần.';
}
```

---

## 7. Cac Design Patterns Trong WordPress

### 7.1. Singleton Pattern

Singleton đảm bảo một class chỉ có duy nhất một instance.

```php
<?php
// Ví dụ từ WordPress core: WP_Block_Type_Registry
class WP_Block_Type_Registry {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Áp dụng trong plugin của bạn:
class My_Plugin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    private function __clone() {}
    public function __wakeup() {
        throw new Exception( 'Không thể unserialize singleton.' );
    }
}

$plugin = My_Plugin::get_instance();
```

### 7.2. Observer Pattern (Hook System)

Đây là pattern quan trọng nhất trong WordPress. Hệ thống hooks là implement của Observer pattern.

```php
<?php
// WordPress core (Subject - phát sự kiện):
function wp_insert_post( $postarr ) {
    // ... logic chèn bài viết ...
    do_action( 'save_post', $post_id, $post, $update );
    return $post_id;
}

// Plugin A (Observer 1):
add_action( 'save_post', 'plugin_a_notify_admin', 10, 3 );
function plugin_a_notify_admin( $post_id, $post, $update ) {
    if ( ! $update && $post->post_status === 'publish' ) {
        wp_mail( get_option( 'admin_email' ), 'Bài viết mới', $post->post_title );
    }
}

// Plugin B (Observer 2):
add_action( 'save_post', 'plugin_b_clear_cache', 20, 1 );
function plugin_b_clear_cache( $post_id ) {
    wp_cache_delete( 'front_page_posts', 'my_cache_group' );
}

// FILTER PATTERN - cho phép thay đổi dữ liệu qua chuỗi xử lý:
// WordPress core:
function get_the_title( $post = 0 ) {
    $title = $post->post_title;
    $title = apply_filters( 'the_title', $title, $post->ID );
    return $title;
}

// Plugin thay đổi title:
add_filter( 'the_title', 'my_modify_title', 10, 2 );
function my_modify_title( $title, $post_id ) {
    if ( is_sticky( $post_id ) ) {
        $title = '[Ghim] ' . $title;
    }
    return $title;
}
```

### 7.3. Registry Pattern

Registry pattern lưu trữ và quản lý các đối tượng theo tên (key).

```php
<?php
// POST TYPE REGISTRY
register_post_type( 'product', array( /* ... */ ) );
$post_type_obj  = get_post_type_object( 'product' );
$all_post_types = get_post_types( array( 'public' => true ), 'objects' );

// TAXONOMY REGISTRY
register_taxonomy( 'product_cat', 'product', array( /* ... */ ) );
$taxonomy       = get_taxonomy( 'product_cat' );

// BLOCK TYPE REGISTRY (Singleton + Registry)
$registry = WP_Block_Type_Registry::get_instance();
$registry->register( 'my-plugin/my-block', array( /* ... */ ) );
$block_type = $registry->get_registered( 'my-plugin/my-block' );
$all_blocks = $registry->get_all_registered();

// Tự tạo Registry:
class My_Service_Registry {
    private static $services = array();

    public static function register( $name, $callback ) {
        self::$services[ $name ] = $callback;
    }

    public static function get( $name ) {
        if ( isset( self::$services[ $name ] ) ) {
            if ( is_callable( self::$services[ $name ] ) ) {
                self::$services[ $name ] = call_user_func( self::$services[ $name ] );
            }
            return self::$services[ $name ];
        }
        return null;
    }
}

My_Service_Registry::register( 'mailer', function() {
    return new My_Mailer_Service( get_option( 'smtp_settings' ) );
} );
$mailer = My_Service_Registry::get( 'mailer' );
```

### 7.4. Factory Pattern

Factory pattern tạo đối tượng mà không cần biết chính xác class nào sẽ được tạo.

```php
<?php
// WP_Widget_Factory - Tạo và quản lý widgets
global $wp_widget_factory;
$wp_widget_factory->register( 'WP_Widget_Recent_Posts' );

// wp_insert_post() hoạt động như factory cho posts
$post_id = wp_insert_post( array(
    'post_title'   => 'Bài Viết Mới',
    'post_content' => 'Nội dung...',
    'post_status'  => 'publish',
    'post_type'    => 'post',
) );

// WP_Http sử dụng factory để chọn transport
// Tự động chọn giữa cURL và streams dựa trên môi trường
$http = new WP_Http();

// Áp dụng:
class Notification_Factory {
    public static function create( $type, $data ) {
        switch ( $type ) {
            case 'email':
                return new Email_Notification( $data );
            case 'sms':
                return new SMS_Notification( $data );
            default:
                $notification = apply_filters( 'my_plugin_notification_factory', null, $type, $data );
                if ( $notification ) {
                    return $notification;
                }
                throw new InvalidArgumentException( "Loại thông báo không hợp lệ: $type" );
        }
    }
}

// Plugin khác có thể mở rộng:
add_filter( 'my_plugin_notification_factory', function( $notification, $type, $data ) {
    if ( $type === 'telegram' ) {
        return new Telegram_Notification( $data );
    }
    return $notification;
}, 10, 3 );
```

### 7.5. Template Method Pattern

```php
<?php
// WP_Widget sử dụng pattern này
// Lớp cha định nghĩa khung, lớp con implement chi tiết
abstract class WP_Widget {
    abstract public function widget( $args, $instance );
    abstract public function update( $new_instance, $old_instance );
    abstract public function form( $instance );
}

class My_Widget extends WP_Widget {
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        echo '<h3>' . esc_html( $instance['title'] ) . '</h3>';
        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? '';
        printf(
            '<p><label for="%1$s">Tiêu đề:</label>
            <input class="widefat" id="%1$s" name="%2$s" value="%3$s" /></p>',
            esc_attr( $this->get_field_id( 'title' ) ),
            esc_attr( $this->get_field_name( 'title' ) ),
            esc_attr( $title )
        );
    }
}
```

---

## 8. Cách Đọc Source Code WordPress Hiệu Quả

### 8.1. Chiến Lược Đọc Code

#### Bước 1: Bắt Đầu Từ Entry Point

```
Luồng request của WordPress:

Browser -> index.php
           -> wp-blog-header.php
              -> wp-load.php
                 -> wp-config.php
                    -> wp-settings.php (BOOT SEQUENCE)
              -> wp() (parse request, query database)
              -> template-loader.php (chọn và load template)
           -> Output HTML cho browser
```

#### Bước 2: Sử Dụng Xdebug Để Trace

```php
<?php
// Trong wp-config.php:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'SAVEQUERIES', true );

// Đặt breakpoint tại các điểm quan trọng:
// - wp-settings.php (wp_initial_constants)
// - wp-includes/class-wp.php method main()
// - wp-includes/class-wp-query.php method get_posts()
// - wp-includes/template-loader.php
```

#### Bước 3: Đọc Theo Chức Năng

```php
<?php
// Ví dụ: Hiểu cách WordPress lưu bài viết
// 1. Tìm function: wp_insert_post() trong wp-includes/post.php
// 2. Đọc từ đầu đến cuối, ghi chú các bước
// 3. Theo dõi các hooks: do_action('save_post')
// 4. Kiểm tra database: bảng wp_posts và wp_postmeta
```

#### Bước 4: Sử Dụng Các Hàm Debug

```php
<?php
// 1. Ghi log
error_log( print_r( $variable, true ) );

// 2. Debug hooks - xem tất cả callbacks của một hook
function debug_hook( $hook_name ) {
    global $wp_filter;
    if ( isset( $wp_filter[ $hook_name ] ) ) {
        foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
            echo "Priority $priority:\n";
            foreach ( $callbacks as $callback ) {
                $func = $callback['function'];
                if ( is_array( $func ) ) {
                    if ( is_object( $func[0] ) ) {
                        echo '  ' . get_class( $func[0] ) . '->' . $func[1] . "()\n";
                    } else {
                        echo '  ' . $func[0] . '::' . $func[1] . "()\n";
                    }
                } elseif ( $func instanceof Closure ) {
                    echo "  Closure\n";
                } else {
                    echo "  $func()\n";
                }
            }
        }
    }
}

// 3. Debug SQL queries
add_action( 'shutdown', function() {
    if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
        global $wpdb;
        foreach ( $wpdb->queries as $query ) {
            error_log( $query[0] . ' | ' . $query[1] . 's' );
        }
    }
} );

// 4. Debug template đang được sử dụng
add_filter( 'template_include', function( $template ) {
    error_log( 'Template: ' . $template );
    return $template;
} );
```

### 8.2. Các File Nên Đọc Trước

Danh sách theo thứ tự ưu tiên:

```
1.  index.php                          # Điểm vào
2.  wp-blog-header.php                 # Luồng chính
3.  wp-load.php                        # Bootstrap
4.  wp-settings.php                    # Boot sequence (QUAN TRỌNG NHẤT)
5.  wp-includes/plugin.php             # Hook API
6.  wp-includes/class-wp-hook.php      # Cài đặt Hook
7.  wp-includes/class-wp.php           # Class WP chính
8.  wp-includes/class-wp-query.php     # Truy vấn
9.  wp-includes/template-loader.php    # Chọn template
10. wp-includes/class-wpdb.php         # Database
11. wp-includes/formatting.php         # Xử lý văn bản
12. wp-includes/post.php               # API cho posts
13. wp-includes/user.php               # API cho users
14. wp-includes/taxonomy.php           # API cho taxonomies
15. wp-includes/rest-api.php           # REST API
```

### 8.3. Các Mẹo Hữu Ích

```php
<?php
// 1. Sử dụng Query Monitor plugin
// Hiển thị: SQL queries, hooks, HTTP requests, template info

// 2. Đọc PHPDoc comments
// WordPress có comments rất chi tiết
// Đọc @since để biết function có từ phiên bản nào

// 3. Sử dụng IDE với "Go to Definition"
// PhpStorm hoặc VS Code với PHP Intelephense

// 4. Tham khảo developer.wordpress.org cho tài liệu chính thức
```

### 8.4. Sơ Đồ Tổng Quan Kiến Trúc

```
+------------------------------------------------------------------+
|                        BROWSER REQUEST                            |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|  index.php -> wp-blog-header.php -> wp-load.php -> wp-config.php |
|                                                  -> wp-settings.php
|  [BOOTSTRAP PHASE]                                                |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|  wp() -> WP::main()                                              |
|     -> parse_request()    : Phân tích URL                         |
|     -> send_headers()     : Gửi HTTP headers                      |
|     -> query_posts()      : WP_Query -> wpdb -> MySQL            |
|     -> handle_404()       : Kiểm tra 404                          |
|  [REQUEST PROCESSING PHASE]                                       |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|  template-loader.php                                              |
|     -> Xác định loại trang (is_single, is_page, is_archive...)   |
|     -> Tìm template theo hierarchy                                |
|     -> apply_filters('template_include', $template)               |
|     -> include $template                                          |
|  [TEMPLATE LOADING PHASE]                                         |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|  Theme Template (single.php, page.php, archive.php...)           |
|     -> get_header()       : Load header.php                       |
|     -> The Loop            : Hiển thị nội dung                    |
|     -> get_sidebar()      : Load sidebar.php                      |
|     -> get_footer()       : Load footer.php                       |
|  [RENDERING PHASE]                                                |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|                        HTML RESPONSE                              |
+------------------------------------------------------------------+
```

---

## Kết Luận

Những điểm chính cần nhớ khi đọc source code WordPress:

1. **Mọi thứ bắt đầu từ index.php** và đi qua wp-blog-header.php, wp-load.php, wp-settings.php.

2. **Hook system (actions và filters) là nền tảng** của toàn bộ kiến trúc. Hiểu hooks là hiểu WordPress.

3. **Không bao giờ sửa core files** (wp-admin, wp-includes). Sử dụng hooks, plugins, và child themes để tùy chỉnh.

4. **wp-settings.php là bản đồ** của quy trình khởi động. Đọc file này để biết mọi thứ được load khi nào và ở đâu.

5. **Global objects ($wpdb, $wp_query, $wp, $wp_rewrite)** là các điểm truy cập chính đến các hệ thống con.

6. **Đọc code theo chức năng**, không có đọc toàn bộ. Tập trung vào một luồng xử lý cụ thể và theo dõi nó từ đầu đến cuối.

7. **Sử dụng công cụ debug** (Xdebug, Query Monitor, error_log) để xem code thực thi như thế nào trong thực tế.

---

## Tài Liệu Tham Khảo

- WordPress Developer Resources: https://developer.wordpress.org/
- WordPress Code Reference: https://developer.wordpress.org/reference/
- WordPress Trac (Source Browser): https://core.trac.wordpress.org/browser
- Make WordPress Core: https://make.wordpress.org/core/
