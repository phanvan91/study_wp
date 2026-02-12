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
    // wp-config.php co the dat o thu muc cha de bao mat
    require_once dirname( ABSPATH ) . '/wp-config.php';
} else {
    // Hien thi thong bao loi yeu cau cai dat
}
```

**Luu y quan trong:** WordPress cho phep dat `wp-config.php` o thu muc cha cua thu muc WordPress. Day la ky thuat bao mat de file cau hinh khong nam trong web root.

#### wp-settings.php - Quy Trinh Khoi Dong (Boot Sequence)

Day la file quan trong nhat trong quy trinh khoi dong. No load toan bo thu vien WordPress theo thu tu cu the. Chi tiet se duoc phan tich o [Muc 5.3](#53-wp-settingsphp---boot-sequence).

#### wp-config-sample.php - Mau Cau Hinh

File mau de tao `wp-config.php`. Chua cac thiet lap co ban:

```php
<?php
// Thiet lap database
define( 'DB_NAME', 'database_name_here' );
define( 'DB_USER', 'username_here' );
define( 'DB_PASSWORD', 'password_here' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );

// Khoa bao mat (Security Keys)
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );

// Prefix bang database
$table_prefix = 'wp_';

// Che do debug
define( 'WP_DEBUG', false );

// Load wp-settings.php de khoi dong WordPress
require_once ABSPATH . 'wp-settings.php';
```

#### wp-login.php - Xu Ly Dang Nhap

Xu ly toan bo quy trinh xac thuc nguoi dung:
- Hien thi form dang nhap
- Xu ly dang nhap/dang xuat
- Quen mat khau va dat lai mat khau

```php
<?php
// Vi du: Hook vao quy trinh dang nhap
add_filter( 'authenticate', 'my_custom_auth', 30, 3 );
function my_custom_auth( $user, $username, $password ) {
    if ( is_blocked_ip( $_SERVER['REMOTE_ADDR'] ) ) {
        return new WP_Error( 'blocked', 'IP cua ban da bi chan.' );
    }
    return $user;
}

// Hook sau khi dang nhap thanh cong
add_action( 'wp_login', 'my_after_login', 10, 2 );
function my_after_login( $user_login, $user ) {
    error_log( "User {$user_login} da dang nhap luc " . current_time( 'mysql' ) );
}
```

#### wp-comments-post.php - Xu Ly Gui Binh Luan

Nhan va xu ly binh luan tu form comment cua nguoi dung. Kiem tra spam, validation, va luu vao database.

```php
<?php
// Vi du: Hook kiem tra binh luan truoc khi luu
add_filter( 'preprocess_comment', 'my_check_comment' );
function my_check_comment( $commentdata ) {
    if ( strlen( $commentdata['comment_content'] ) < 10 ) {
        wp_die( 'Binh luan qua ngan, vui long viet it nhat 10 ky tu.' );
    }
    return $commentdata;
}
```

#### wp-cron.php - He Thong Cron cua WordPress

WordPress khong su dung cron thuc su cua he dieu hanh. Thay vao do, moi khi co nguoi truy cap site, WordPress kiem tra xem co tac vu nao can chay khong.

```php
<?php
// Vi du: Dang ky mot cron event
add_action( 'my_daily_cleanup', 'do_daily_cleanup' );
function do_daily_cleanup() {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_timeout_%'
         AND option_value < UNIX_TIMESTAMP()"
    );
}

// Len lich chay hang ngay
if ( ! wp_next_scheduled( 'my_daily_cleanup' ) ) {
    wp_schedule_event( time(), 'daily', 'my_daily_cleanup' );
}
```

**Luu y:** Trong moi truong production, nen tat WP-Cron va su dung system cron thay the:

```php
// Trong wp-config.php
define( 'DISABLE_WP_CRON', true );
```

#### wp-activate.php - Kich Hoat Tai Khoan

Su dung trong WordPress Multisite de kich hoat tai khoan nguoi dung moi sau khi dang ky.

#### wp-signup.php - Dang Ky Tai Khoan

Xu ly dang ky tai khoan moi trong WordPress Multisite. Hien thi form dang ky va xu ly du lieu.

#### wp-mail.php - Xu Ly Email Posting

Cho phep tao bai viet thong qua email. Day la tinh nang cu (legacy) va it duoc su dung trong thuc te.

#### wp-links-opml.php - Xuat Lien Ket OPML

Xuat danh sach lien ket (blogroll) theo dinh dang OPML. Day la tinh nang tu thoi ky dau cua blogging.

#### wp-trackback.php - Xu Ly Trackback

Xu ly trackback tu cac blog khac. Trackback la co che thong bao khi mot blog khac lien ket den bai viet cua ban. Day la tinh nang cu va nen tat vi ly do bao mat.

#### xmlrpc.php - XML-RPC API

Cung cap API XML-RPC de cac ung dung ben ngoai tuong tac voi WordPress. Nhieu chuyen gia bao mat khuyen nen tat file nay neu khong su dung.

```php
<?php
// Tat XML-RPC trong functions.php
add_filter( 'xmlrpc_enabled', '__return_false' );
```

---

## 2. Thu Muc wp-admin/

Thu muc `wp-admin/` chua toan bo giao dien quan tri (admin dashboard) cua WordPress.

### 2.1. So Do Cau Truc wp-admin/

```
wp-admin/
├── admin.php              # Diem vao chinh cua moi trang admin
├── admin-ajax.php         # Xu ly AJAX requests
├── admin-post.php         # Xu ly form POST tu admin
├── admin-header.php       # Header cua trang admin
├── admin-footer.php       # Footer cua trang admin
├── edit.php               # Danh sach bai viet
├── post.php               # Xu ly tao/sua bai viet
├── post-new.php           # Tao bai viet moi
├── edit-tags.php          # Quan ly taxonomy terms
├── upload.php             # Thu vien media
├── options.php            # Xu ly luu cau hinh
├── options-general.php    # Trang cau hinh chung
├── users.php              # Quan ly nguoi dung
├── plugins.php            # Quan ly plugins
├── themes.php             # Quan ly themes
├── widgets.php            # Quan ly widgets
├── nav-menus.php          # Quan ly menu
├── customize.php          # WordPress Customizer
├── includes/              # Cac file ho tro admin
├── css/                   # Stylesheets cua admin
├── js/                    # JavaScript cua admin
└── images/                # Hinh anh cua admin
```

### 2.2. Cac File Quan Trong

#### admin.php - Diem Vao Admin

Moi trang trong admin panel deu load file nay dau tien. No thuc hien:
- Load WordPress environment
- Kiem tra quyen truy cap (authentication)
- Thiet lap admin context

```php
<?php
// Quy trinh load cua admin.php (don gian hoa):

// 1. Load WordPress
require_once dirname( __DIR__ ) . '/wp-load.php';

// 2. Kiem tra nguoi dung da dang nhap chua
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
    exit;
}

// 3. Load cac thu vien admin
require_once ABSPATH . 'wp-admin/includes/admin.php';

// 4. Fire action de plugins co the hook vao
do_action( 'admin_init' );
```

#### edit.php - Danh Sach Bai Viet (Post List)

Hien thi danh sach bai viet dang bang (table) voi cac chuc nang loc, tim kiem, va thao tac hang loat.

```php
<?php
// Vi du: Them cot tuy chinh vao danh sach bai viet
add_filter( 'manage_posts_columns', 'my_custom_columns' );
function my_custom_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        if ( $key === 'title' ) {
            $new_columns['views'] = 'Luot Xem';
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

#### post.php va post-new.php - Tao/Sua Bai Viet

`post-new.php` tao bai viet moi (auto-draft), `post.php` xu ly viec luu va cap nhat bai viet.

```php
<?php
// Vi du: Hook vao quy trinh luu bai viet
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

#### options.php - Xu Ly Luu Cau Hinh

Nhan du lieu POST tu cac trang cau hinh va luu vao bang `wp_options`.

```php
<?php
// Vi du: Dang ky trang cau hinh tuy chinh
add_action( 'admin_menu', 'my_options_page' );
function my_options_page() {
    add_options_page(
        'Cau Hinh Plugin',
        'Plugin Cua Toi',
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
        'Cau Hinh Chung',
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

#### users.php - Quan Ly Nguoi Dung

Hien thi danh sach nguoi dung, cho phep them/sua/xoa va phan quyen.

```php
<?php
// Vi du: Them custom field vao trang profile nguoi dung
add_action( 'show_user_profile', 'my_user_profile_fields' );
add_action( 'edit_user_profile', 'my_user_profile_fields' );
function my_user_profile_fields( $user ) {
    $phone = get_user_meta( $user->ID, 'phone_number', true );
    ?>
    <h3>Thong Tin Bo Sung</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone_number">So Dien Thoai</label></th>
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

Hien thi danh sach plugins da cai dat. Cho phep kich hoat, vo hieu hoa, cap nhat, va xoa plugins.

```php
<?php
// Vi du: Them lien ket tuy chinh vao trang plugins
add_filter( 'plugin_action_links_my-plugin/my-plugin.php', 'my_plugin_links' );
function my_plugin_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=my-plugin-settings' ) . '">Cau Hinh</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
```

#### themes.php - Quan Ly Themes

Hien thi cac theme da cai dat, cho phep kich hoat, xem truoc, va cai dat theme moi.

#### admin-ajax.php - Xu Ly AJAX

Day la endpoint chinh cho tat ca cac AJAX request trong WordPress admin (va ca frontend). Moi AJAX request can gui kem tham so `action`.

```php
<?php
// PHIA SERVER: Dang ky AJAX handler
add_action( 'wp_ajax_my_action', 'my_ajax_handler' );        // User da dang nhap
add_action( 'wp_ajax_nopriv_my_action', 'my_ajax_handler' ); // User chua dang nhap

function my_ajax_handler() {
    check_ajax_referer( 'my_nonce_action', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Khong co quyen.' );
    }

    $post_id = intval( $_POST['post_id'] );
    $result  = update_post_meta( $post_id, '_liked', true );

    if ( $result ) {
        wp_send_json_success( array( 'message' => 'Da thich bai viet!' ) );
    } else {
        wp_send_json_error( 'Khong the xu ly.' );
    }
}

// Dang ky script va truyen bien sang JavaScript
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

Chua cac file ho tro cho admin:

```
wp-admin/includes/
├── admin.php                  # Load cac file admin utilities
├── class-wp-list-table.php    # Class hien thi bang du lieu
├── class-wp-screen.php        # Class quan ly man hinh admin
├── dashboard.php              # Cac widget dashboard
├── file.php                   # Xu ly file (upload, edit)
├── image.php                  # Xu ly hinh anh
├── media.php                  # Thu vien media
├── plugin.php                 # Utilities cho plugin management
├── post.php                   # Utilities cho post management
├── schema.php                 # Cau truc database
├── template.php               # Template functions cho admin
├── upgrade.php                # Xu ly nang cap WordPress
└── user.php                   # Utilities cho user management
```

---

## 3. Thu Muc wp-includes/

Thu muc `wp-includes/` la **core library** cua WordPress. Chua tat ca cac class, function, va API ma WordPress su dung.

**Nguyen tac:** KHONG BAO GIO sua truc tiep cac file trong `wp-includes/`. Moi thay doi se bi mat khi cap nhat WordPress. Su dung hooks de tuy chinh hanh vi.

### 3.1. Cac Class Chinh

#### class-wp.php - Lop WordPress Chinh

Class `WP` la trung tam dieu phoi cua WordPress. No xu ly viec phan tich URL request, thiet lap query variables, gui headers, va thuc hien main query.

```php
<?php
// Cau truc don gian hoa cua class WP:
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
        $this->parse_request();    // Phan tich URL
        $this->send_headers();     // Gui HTTP headers
        $this->query_posts();      // Truy van posts
        $this->handle_404();       // Xu ly loi 404
        $this->register_globals();
        do_action_ref_array( 'wp', array( &$this ) );
    }
}

// Ham global wp() goi class nay:
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

Dai dien cho mot nguoi dung trong he thong. Chua thong tin ca nhan, vai tro, va quyen han.

```php
<?php
$user = new WP_User( 1 );
echo $user->user_login;
echo $user->user_email;
echo $user->display_name;

if ( $user->has_cap( 'edit_posts' ) ) {
    echo 'Nguoi dung co quyen chinh sua bai viet.';
}

// Cac vai tro mac dinh cua WordPress:
// - administrator: Toan quyen
// - editor: Quan ly noi dung
// - author: Viet va quan ly bai viet cua minh
// - contributor: Viet bai nhung khong duoc xuat ban
// - subscriber: Chi doc

// Vi du: Tao vai tro tuy chinh
add_role( 'shop_manager', 'Quan Ly Cua Hang', array(
    'read'           => true,
    'edit_posts'     => true,
    'delete_posts'   => true,
    'publish_posts'  => true,
    'upload_files'   => true,
) );
```

#### class-wp-post.php - Lop Bai Viet

Dai dien cho mot bai viet (post, page, hoac bat ky custom post type nao).

```php
<?php
// Cac thuoc tinh cua WP_Post:
// $post->ID              - ID bai viet
// $post->post_author     - ID tac gia
// $post->post_date       - Ngay tao
// $post->post_content    - Noi dung
// $post->post_title      - Tieu de
// $post->post_excerpt    - Tom tat
// $post->post_status     - Trang thai (publish, draft, private, pending...)
// $post->post_type       - Loai (post, page, attachment, custom...)
// $post->post_name       - Slug URL
// $post->post_parent     - ID bai viet cha
// $post->menu_order      - Thu tu hien thi

// Vi du: Dang ky Custom Post Type
add_action( 'init', 'register_product_post_type' );
function register_product_post_type() {
    register_post_type( 'product', array(
        'labels' => array(
            'name'          => 'San Pham',
            'singular_name' => 'San Pham',
            'add_new'       => 'Them Moi',
            'add_new_item'  => 'Them San Pham Moi',
            'edit_item'     => 'Chinh Sua San Pham',
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

Quan ly viec chuyen doi URL dep (pretty permalinks) thanh cac tham so truy van.

```php
<?php
// Vi du: Them rewrite rule tuy chinh
add_action( 'init', 'my_custom_rewrite_rules' );
function my_custom_rewrite_rules() {
    add_rewrite_rule(
        'san-pham/([^/]+)/([^/]+)/?$',
        'index.php?post_type=product&product_cat=$matches[1]&name=$matches[2]',
        'top'
    );
    add_rewrite_tag( '%product_cat%', '([^/]+)' );
}
// Sau khi them rewrite rule, can flush:
// Vao Settings > Permalinks va nhan Save
```

#### class-wp-hook.php - Lop Hook (Nen Tang Cua Plugin API)

Class nay implement toan bo he thong hook (action va filter) cua WordPress.

```php
<?php
// Cau truc cua WP_Hook (don gian hoa):
final class WP_Hook implements Iterator, ArrayAccess {
    public $callbacks = array();
    // Cau truc:
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

// Vi du thuc te:
// 1. Dang ky filter
add_filter( 'the_content', 'my_add_disclaimer', 20 );
function my_add_disclaimer( $content ) {
    if ( is_single() ) {
        $content .= '<p><em>Bai viet chi mang tinh tham khao.</em></p>';
    }
    return $content;
}

// 2. Tao hook tuy chinh trong plugin/theme
function my_process_order( $order_id ) {
    $order_data = get_order_data( $order_id );
    $order_data = apply_filters( 'my_plugin_order_data', $order_data, $order_id );
    do_action( 'my_plugin_order_processed', $order_id, $order_data );
}
```

#### class-wpdb.php - Lop Database

Class `wpdb` la lop truu tuong hoa database cua WordPress.

```php
<?php
global $wpdb;

// 1. Truy van an toan voi prepare()
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s LIMIT %d",
        'product', 'publish', 10
    )
);

// 2. Lay mot dong
$user = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$wpdb->users} WHERE user_email = %s", 'user@example.com' )
);

// 3. Lay mot gia tri
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish'" );

// 4. Chen du lieu
$wpdb->insert(
    $wpdb->prefix . 'my_custom_table',
    array( 'name' => 'San pham moi', 'price' => 150000, 'created_at' => current_time( 'mysql' ) ),
    array( '%s', '%d', '%s' )
);
$new_id = $wpdb->insert_id;

// 5. Cap nhat du lieu
$wpdb->update(
    $wpdb->prefix . 'my_custom_table',
    array( 'price' => 200000 ),
    array( 'id' => $new_id ),
    array( '%d' ),
    array( '%d' )
);

// 6. Xoa du lieu
$wpdb->delete( $wpdb->prefix . 'my_custom_table', array( 'id' => $new_id ), array( '%d' ) );

// 7. Tao bang tuy chinh
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

WordPress REST API cho phep tuong tac voi WordPress thong qua HTTP requests, tra ve du lieu JSON.

```php
<?php
// Dang ky REST API endpoint tuy chinh
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
        return new WP_Error( 'not_found', 'San pham khong ton tai.', array( 'status' => 404 ) );
    }
    return new WP_REST_Response( array(
        'id'      => $post->ID,
        'title'   => $post->post_title,
        'content' => apply_filters( 'the_content', $post->post_content ),
    ), 200 );
}
```

### 3.3. Formatting va Security

#### formatting.php - Xu Ly Dinh Dang Van Ban

```php
<?php
// Escape du lieu de ngan XSS
$safe = esc_html( '<script>alert("XSS")</script>' );
echo '<input value="' . esc_attr( $user_input ) . '">';
echo '<a href="' . esc_url( $url ) . '">Lien ket</a>';

// Lam sach du lieu dau vao
$clean = sanitize_text_field( $_POST['name'] );
$email = sanitize_email( $_POST['email'] );
$file  = sanitize_file_name( $_POST['filename'] );
$slug  = sanitize_title( 'Tieu De Bai Viet Tieng Viet' );

// Tu dong tao doan van
$formatted = wpautop( "Dong 1\n\nDong 2\nDong 3" );
```

#### kses.php - HTML Filtering (Security)

KSES (KSES Strips Evil Scripts) loc HTML de ngan chan XSS attack.

```php
<?php
$allowed = array(
    'a'      => array( 'href' => true, 'title' => true ),
    'strong' => array(),
    'em'     => array(),
    'p'      => array( 'class' => true ),
);
$safe_html = wp_kses( $user_html, $allowed );

// Su dung cac preset co san:
$post_safe = wp_kses_post( $html );
```

### 3.4. Plugin API

#### plugin.php - API Cho Plugin System

```php
<?php
// FILTERS - Thay doi du lieu
add_filter( $hook_name, $callback, $priority, $accepted_args );
apply_filters( $hook_name, $value, ...$args );

// ACTIONS - Thuc thi hanh dong
add_action( $hook_name, $callback, $priority, $accepted_args );
do_action( $hook_name, ...$args );

// PLUGIN MANAGEMENT
register_activation_hook( $file, $callback );
register_deactivation_hook( $file, $callback );
register_uninstall_hook( $file, $callback );

// Vi du: Vong doi cua mot plugin
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
// Thu bac template cua WordPress (template hierarchy):
// Trang chu:     front-page.php -> home.php -> index.php
// Bai viet don:  single-{type}-{slug}.php -> single-{type}.php -> single.php -> singular.php -> index.php
// Trang:         {custom}.php -> page-{slug}.php -> page-{id}.php -> page.php -> singular.php -> index.php
// Danh muc:      category-{slug}.php -> category-{id}.php -> category.php -> archive.php -> index.php
// Tag:           tag-{slug}.php -> tag-{id}.php -> tag.php -> archive.php -> index.php
// Tim kiem:      search.php -> index.php
// 404:           404.php -> index.php

// Logic cua template-loader.php (don gian hoa):
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
// Dang ky Custom Taxonomy
add_action( 'init', 'register_product_taxonomy' );
function register_product_taxonomy() {
    register_taxonomy( 'product_cat', 'product', array(
        'labels' => array(
            'name'          => 'Danh Muc San Pham',
            'singular_name' => 'Danh Muc',
            'add_new_item'  => 'Them Danh Muc Moi',
        ),
        'hierarchical'      => true,   // true = nhu Category, false = nhu Tag
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
// Kiem tra dang nhap
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    echo 'Xin chao, ' . $current_user->display_name;
}

// Tao nguoi dung moi
$user_id = wp_insert_user( array(
    'user_login' => 'newuser',
    'user_pass'  => wp_generate_password(),
    'user_email' => 'new@example.com',
    'role'       => 'author',
) );

// Kiem tra quyen
if ( current_user_can( 'manage_options' ) ) {
    echo 'Ban la Administrator.';
}
if ( current_user_can( 'edit_post', $post_id ) ) {
    echo 'Ban co the chinh sua bai viet nay.';
}

// Them quyen tuy chinh cho vai tro
$role = get_role( 'editor' );
$role->add_cap( 'manage_products' );
```

### 3.8. HTTP API

#### class-wp-http.php - Xu Ly HTTP Requests

```php
<?php
// GET request
$response = wp_remote_get( 'https://api.example.com/data' );
if ( is_wp_error( $response ) ) {
    echo 'Loi: ' . $response->get_error_message();
} else {
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
}

// POST request voi headers
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
// Object Cache co ban
wp_cache_set( 'my_key', $data, 'my_group', 3600 );
$data = wp_cache_get( 'my_key', 'my_group' );
wp_cache_delete( 'my_key', 'my_group' );

// Transient API - Cache luu trong database
set_transient( 'my_api_data', $api_data, 12 * HOUR_IN_SECONDS );
$cached = get_transient( 'my_api_data' );
if ( false === $cached ) {
    $cached = my_fetch_api_data();
    set_transient( 'my_api_data', $cached, 12 * HOUR_IN_SECONDS );
}

// Vi du: Cache ket qua truy van phuc tap
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

// Xoa cache khi co bai viet moi
add_action( 'save_post', function() {
    delete_transient( 'popular_posts_5' );
    delete_transient( 'popular_posts_10' );
} );
```

### 3.10. Block Editor (Gutenberg)

#### blocks.php va block-patterns.php

```php
<?php
// Dang ky block tuy chinh (phia server)
add_action( 'init', 'my_register_blocks' );
function my_register_blocks() {
    register_block_type( __DIR__ . '/blocks/my-block', array(
        'render_callback' => 'my_block_render',
    ) );
}

function my_block_render( $attributes, $content ) {
    $title = $attributes['title'] ?? 'Tieu de mac dinh';
    return sprintf(
        '<div class="my-custom-block"><h3>%s</h3><div>%s</div></div>',
        esc_html( $title ),
        wp_kses_post( $content )
    );
}

// Dang ky block pattern
add_action( 'init', 'my_register_patterns' );
function my_register_patterns() {
    register_block_pattern( 'my-plugin/hero-section', array(
        'title'       => 'Hero Section',
        'description' => 'Phan hero voi hinh nen va tieu de.',
        'categories'  => array( 'featured' ),
        'content'     => '<!-- wp:cover {"overlayColor":"primary"} -->
            <div class="wp-block-cover">
                <div class="wp-block-cover__inner-container">
                    <!-- wp:heading {"textAlign":"center","level":1} -->
                    <h1 class="has-text-align-center">Chao Mung</h1>
                    <!-- /wp:heading -->
                </div>
            </div>
            <!-- /wp:cover -->',
    ) );
}
```

---

## 4. Thu Muc wp-content/

Thu muc `wp-content/` la noi duy nhat ma nguoi dung nen thay doi.

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

Moi plugin nam trong thu muc rieng:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: Mo ta ngan gon ve plugin.
 * Version:     1.0.0
 * Author:      Ten Tac Gia
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

Cau truc co ban cua mot theme:

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

Cac plugin trong thu muc nay tu dong kich hoat va KHONG THE vo hieu hoa tu giao dien admin.

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

Chua cac file dich ngon ngu:
- `.po` - file nguon co the doc duoc (Portable Object)
- `.mo` - file da bien dich (Machine Object)

```php
<?php
load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

echo __( 'Xin chao', 'my-plugin' );
_e( 'Xin chao', 'my-plugin' );
echo _n( '%d bai viet', '%d bai viet', $count, 'my-plugin' );
```

---

## 5. Cac File Cau Hinh Quan Trong

### 5.1. wp-config.php - File Cau Hinh Chinh

#### Cac Constant Database

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

#### Cac Constant Bao Mat

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

#### Cac Constant Debug

```php
<?php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', true );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );
```

#### Cac Constant Hieu Suat

```php
<?php
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'DISABLE_WP_CRON', true );
define( 'WP_POST_REVISIONS', 5 );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_CACHE', true );
```

#### Cac Constant URL

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

Day la file quan trong nhat trong quy trinh khoi dong. Quy trinh chi tiet:

```
wp-settings.php Boot Sequence:
|
|-- 1. Dinh nghia WPINC = 'wp-includes'
|-- 2. Load version.php, compat.php, load.php
|-- 3. Kiem tra phien ban PHP va MySQL
|-- 4. Load recovery mode classes
|-- 5. Load default-constants.php
|-- 6. Load plugin.php (Hook API co san tu day)
|
|-- 7. wp_initial_constants() - WP_MEMORY_LIMIT, WP_DEBUG, WP_CONTENT_DIR
|-- 8. wp_register_fatal_error_handler()
|-- 9. date_default_timezone_set('UTC')
|-- 10. wp_maintenance() - Kiem tra che do bao tri
|
|-- 11. Load cac file som: formatting.php, functions.php, class-wp.php, class-wp-error.php
|-- 12. require_wp_db() - Load class wpdb, ket noi database
|-- 13. wp_start_object_cache()
|-- 14. Load default-filters.php
|
|-- ** Neu SHORTINIT = true -> DUNG O DAY **
|
|-- 15. Load L10n (ngon ngu)
|-- 16. wp_not_installed() - Kiem tra da cai dat chua
|
|-- 17. Load PHAN LON WordPress:
|       capabilities.php, class-wp-roles.php, class-wp-user.php,
|       class-wp-query.php, theme.php, template.php,
|       user.php, post.php, taxonomy.php, rewrite.php,
|       kses.php, shortcodes.php, media.php, http.php,
|       class-wp-http.php, widgets.php, nav-menu.php,
|       rest-api.php + tat ca REST endpoints,
|       blocks.php, block-patterns.php
|
|-- 18. do_action('muplugins_loaded')  -- MU-plugins da san sang
|-- 19. Load cac active plugins
|-- 20. Load pluggable.php
|-- 21. do_action('plugins_loaded')    -- Tat ca plugins da load
|
|-- 22. Tao global objects: $wp, $wp_rewrite, $wp_roles
|-- 23. do_action('setup_theme')
|-- 24. Load theme functions.php
|-- 25. do_action('after_setup_theme') -- Theme da san sang
|
|-- 26. Tao $wp_the_query, $wp_query
|-- 27. do_action('init')              -- MOI THU DA SAN SANG
|-- 28. do_action('wp_loaded')         -- WORDPRESS DA LOAD XONG
```

**Thu tu cac action hooks chinh:**

```php
<?php
// 1. muplugins_loaded  - Sau khi MU-plugins load
// 2. plugins_loaded    - Sau khi tat ca plugins load
// 3. setup_theme       - Truoc khi theme load
// 4. after_setup_theme - Sau khi theme load
// 5. init              - Moi thu da san sang
// 6. wp_loaded         - WordPress da hoan tat load
// 7. wp                - Sau khi parse request va query (chi frontend)
// 8. template_redirect - Truoc khi chon template (chi frontend)
// 9. wp_head           - Trong <head>
// 10. wp_footer        - Truoc </body>
// 11. shutdown         - Sau khi output da gui

// Vi du: Chon hook phu hop
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    register_nav_menus( array( 'primary' => 'Menu Chinh' ) );
} );

add_action( 'init', function() {
    // Dang ky post types, taxonomies
} );

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'my-style', get_stylesheet_uri() );
    wp_enqueue_script( 'my-script', get_template_directory_uri() . '/js/main.js' );
} );
```

---

## 6. Cac Global Objects Quan Trong

WordPress su dung nhieu bien global de luu tru trang thai va cung cap truy cap den cac he thong con.

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

// Kiem tra loi truy van cuoi cung
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

// Kiem tra loai trang hien tai
$wp_query->is_home();
$wp_query->is_front_page();
$wp_query->is_single();
$wp_query->is_page();
$wp_query->is_archive();
$wp_query->is_category();
$wp_query->is_search();
$wp_query->is_404();

echo $wp_query->found_posts;    // Tong so bai viet tim thay
echo $wp_query->max_num_pages;  // Tong so trang
echo $wp_query->post_count;     // So bai viet trong trang hien tai

$posts      = $wp_query->posts;
$query_vars = $wp_query->query_vars;
```

### 6.3. $wp - WordPress Environment Object

```php
<?php
global $wp;

echo $wp->request;       // Duong dan request (vi du: '2025/01/hello-world')
echo $wp->query_string;  // Query string da phan tich
echo $wp->matched_rule;  // Rewrite rule da khop
echo $wp->matched_query; // Query da khop

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
    echo 'Dang su dung pretty permalinks.';
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

$wp_roles->add_role( 'custom_role', 'Vai Tro Tuy Chinh', array(
    'read'       => true,
    'edit_posts' => true,
) );
$wp_roles->add_cap( 'editor', 'manage_custom_content' );
```

### 6.6. $wp_filter va $wp_actions - Hook System

```php
<?php
global $wp_filter, $wp_actions;

// $wp_filter chua TAT CA cac hooks (ca actions va filters)
// $wp_actions dem so lan moi action duoc goi

// Kiem tra mot hook co callbacks nao khong
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

// Kiem tra mot action da duoc goi chua
if ( did_action( 'init' ) ) {
    echo 'Action init da duoc goi ' . $wp_actions['init'] . ' lan.';
}
```

---

## 7. Cac Design Patterns Trong WordPress

### 7.1. Singleton Pattern

Singleton dam bao mot class chi co duy nhat mot instance.

```php
<?php
// Vi du tu WordPress core: WP_Block_Type_Registry
class WP_Block_Type_Registry {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Ap dung trong plugin cua ban:
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
        throw new Exception( 'Khong the unserialize singleton.' );
    }
}

$plugin = My_Plugin::get_instance();
```

### 7.2. Observer Pattern (Hook System)

Day la pattern quan trong nhat trong WordPress. He thong hooks la implement cua Observer pattern.

```php
<?php
// WordPress core (Subject - phat su kien):
function wp_insert_post( $postarr ) {
    // ... logic chen bai viet ...
    do_action( 'save_post', $post_id, $post, $update );
    return $post_id;
}

// Plugin A (Observer 1):
add_action( 'save_post', 'plugin_a_notify_admin', 10, 3 );
function plugin_a_notify_admin( $post_id, $post, $update ) {
    if ( ! $update && $post->post_status === 'publish' ) {
        wp_mail( get_option( 'admin_email' ), 'Bai viet moi', $post->post_title );
    }
}

// Plugin B (Observer 2):
add_action( 'save_post', 'plugin_b_clear_cache', 20, 1 );
function plugin_b_clear_cache( $post_id ) {
    wp_cache_delete( 'front_page_posts', 'my_cache_group' );
}

// FILTER PATTERN - cho phep thay doi du lieu qua chuoi xu ly:
// WordPress core:
function get_the_title( $post = 0 ) {
    $title = $post->post_title;
    $title = apply_filters( 'the_title', $title, $post->ID );
    return $title;
}

// Plugin thay doi title:
add_filter( 'the_title', 'my_modify_title', 10, 2 );
function my_modify_title( $title, $post_id ) {
    if ( is_sticky( $post_id ) ) {
        $title = '[Ghim] ' . $title;
    }
    return $title;
}
```

### 7.3. Registry Pattern

Registry pattern luu tru va quan ly cac doi tuong theo ten (key).

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

// Tu tao Registry:
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

Factory pattern tao doi tuong ma khong can biet chinh xac class nao se duoc tao.

```php
<?php
// WP_Widget_Factory - Tao va quan ly widgets
global $wp_widget_factory;
$wp_widget_factory->register( 'WP_Widget_Recent_Posts' );

// wp_insert_post() hoat dong nhu factory cho posts
$post_id = wp_insert_post( array(
    'post_title'   => 'Bai Viet Moi',
    'post_content' => 'Noi dung...',
    'post_status'  => 'publish',
    'post_type'    => 'post',
) );

// WP_Http su dung factory de chon transport
// Tu dong chon giua cURL va streams dua tren moi truong
$http = new WP_Http();

// Ap dung:
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
                throw new InvalidArgumentException( "Loai thong bao khong hop le: $type" );
        }
    }
}

// Plugin khac co the mo rong:
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
// WP_Widget su dung pattern nay
// Lop cha dinh nghia khung, lop con implement chi tiet
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
            '<p><label for="%1$s">Tieu de:</label>
            <input class="widefat" id="%1$s" name="%2$s" value="%3$s" /></p>',
            esc_attr( $this->get_field_id( 'title' ) ),
            esc_attr( $this->get_field_name( 'title' ) ),
            esc_attr( $title )
        );
    }
}
```

---

## 8. Cach Doc Source Code WordPress Hieu Qua

### 8.1. Chien Luoc Doc Code

#### Buoc 1: Bat Dau Tu Entry Point

```
Luong request cua WordPress:

Browser -> index.php
           -> wp-blog-header.php
              -> wp-load.php
                 -> wp-config.php
                    -> wp-settings.php (BOOT SEQUENCE)
              -> wp() (parse request, query database)
              -> template-loader.php (chon va load template)
           -> Output HTML cho browser
```

#### Buoc 2: Su Dung Xdebug De Trace

```php
<?php
// Trong wp-config.php:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'SAVEQUERIES', true );

// Dat breakpoint tai cac diem quan trong:
// - wp-settings.php (wp_initial_constants)
// - wp-includes/class-wp.php method main()
// - wp-includes/class-wp-query.php method get_posts()
// - wp-includes/template-loader.php
```

#### Buoc 3: Doc Theo Chuc Nang

```php
<?php
// Vi du: Hieu cach WordPress luu bai viet
// 1. Tim function: wp_insert_post() trong wp-includes/post.php
// 2. Doc tu dau den cuoi, ghi chu cac buoc
// 3. Theo doi cac hooks: do_action('save_post')
// 4. Kiem tra database: bang wp_posts va wp_postmeta
```

#### Buoc 4: Su Dung Cac Ham Debug

```php
<?php
// 1. Ghi log
error_log( print_r( $variable, true ) );

// 2. Debug hooks - xem tat ca callbacks cua mot hook
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

// 4. Debug template dang duoc su dung
add_filter( 'template_include', function( $template ) {
    error_log( 'Template: ' . $template );
    return $template;
} );
```

### 8.2. Cac File Nen Doc Truoc

Danh sach theo thu tu uu tien:

```
1.  index.php                          # Diem vao
2.  wp-blog-header.php                 # Luong chinh
3.  wp-load.php                        # Bootstrap
4.  wp-settings.php                    # Boot sequence (QUAN TRONG NHAT)
5.  wp-includes/plugin.php             # Hook API
6.  wp-includes/class-wp-hook.php      # Cai dat Hook
7.  wp-includes/class-wp.php           # Class WP chinh
8.  wp-includes/class-wp-query.php     # Truy van
9.  wp-includes/template-loader.php    # Chon template
10. wp-includes/class-wpdb.php         # Database
11. wp-includes/formatting.php         # Xu ly van ban
12. wp-includes/post.php               # API cho posts
13. wp-includes/user.php               # API cho users
14. wp-includes/taxonomy.php           # API cho taxonomies
15. wp-includes/rest-api.php           # REST API
```

### 8.3. Cac Meo Huu Ich

```php
<?php
// 1. Su dung Query Monitor plugin
// Hien thi: SQL queries, hooks, HTTP requests, template info

// 2. Doc PHPDoc comments
// WordPress co comments rat chi tiet
// Doc @since de biet function co tu phien ban nao

// 3. Su dung IDE voi "Go to Definition"
// PhpStorm hoac VS Code voi PHP Intelephense

// 4. Tham khao developer.wordpress.org cho tai lieu chinh thuc
```

### 8.4. So Do Tong Quan Kien Truc

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
|     -> parse_request()    : Phan tich URL                         |
|     -> send_headers()     : Gui HTTP headers                      |
|     -> query_posts()      : WP_Query -> wpdb -> MySQL            |
|     -> handle_404()       : Kiem tra 404                          |
|  [REQUEST PROCESSING PHASE]                                       |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|  template-loader.php                                              |
|     -> Xac dinh loai trang (is_single, is_page, is_archive...)   |
|     -> Tim template theo hierarchy                                |
|     -> apply_filters('template_include', $template)               |
|     -> include $template                                          |
|  [TEMPLATE LOADING PHASE]                                         |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|  Theme Template (single.php, page.php, archive.php...)           |
|     -> get_header()       : Load header.php                       |
|     -> The Loop            : Hien thi noi dung                    |
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

## Ket Luan

Nhung diem chinh can nho khi doc source code WordPress:

1. **Moi thu bat dau tu index.php** va di qua wp-blog-header.php, wp-load.php, wp-settings.php.

2. **Hook system (actions va filters) la nen tang** cua toan bo kien truc. Hieu hooks la hieu WordPress.

3. **Khong bao gio sua core files** (wp-admin, wp-includes). Su dung hooks, plugins, va child themes de tuy chinh.

4. **wp-settings.php la ban do** cua quy trinh khoi dong. Doc file nay de biet moi thu duoc load khi nao va o dau.

5. **Global objects ($wpdb, $wp_query, $wp, $wp_rewrite)** la cac diem truy cap chinh den cac he thong con.

6. **Doc code theo chuc nang**, khong co doc toan bo. Tap trung vao mot luong xu ly cu the va theo doi no tu dau den cuoi.

7. **Su dung cong cu debug** (Xdebug, Query Monitor, error_log) de xem code thuc thi nhu the nao trong thuc te.

---

## Tai Lieu Tham Khao

- WordPress Developer Resources: https://developer.wordpress.org/
- WordPress Code Reference: https://developer.wordpress.org/reference/
- WordPress Trac (Source Browser): https://core.trac.wordpress.org/browser
- Make WordPress Core: https://make.wordpress.org/core/
