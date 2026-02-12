# Bao Mat WordPress

## Muc Luc

1. [Tong quan](#1-tong-quan)
2. [Sanitization - Lam sach du lieu dau vao](#2-sanitization---lam-sach-du-lieu-dau-vao)
3. [Escaping - An toan hoa du lieu dau ra](#3-escaping---an-toan-hoa-du-lieu-dau-ra)
4. [Validation - Kiem tra du lieu](#4-validation---kiem-tra-du-lieu)
5. [Nonces - Chong CSRF](#5-nonces---chong-csrf)
6. [Capability Checks - Kiem tra quyen](#6-capability-checks---kiem-tra-quyen)
7. [SQL Injection Prevention](#7-sql-injection-prevention)
8. [XSS Prevention](#8-xss-prevention)
9. [File Upload Security](#9-file-upload-security)
10. [WordPress Security Constants](#10-wordpress-security-constants)
11. [Hardening wp-config.php](#11-hardening-wp-configphp)
12. [.htaccess Security](#12-htaccess-security)
13. [Checklist bao mat](#13-checklist-bao-mat)

---

## 1. Tong Quan

### Nguyen tac co ban

- **Khong tin tuong bat ky du lieu nao tu ben ngoai** (user input, API, database)
- **Sanitize input, escape output** - Lam sach khi nhan, an toan hoa khi hien thi
- **Nguyen tac quyen toi thieu** (Least Privilege) - Chi cap quyen can thiet
- **Defense in Depth** - Nhieu lop bao ve

### Luong du lieu an toan

```
User Input → Validate → Sanitize → Process → Store in DB
DB Data → Retrieve → Escape → Display
```

---

## 2. Sanitization - Lam Sach Du Lieu Dau Vao

Sanitization lam sach du lieu truoc khi luu vao database.

### Cac ham sanitize pho bien

```php
// Text thuan
$name = sanitize_text_field( $_POST['name'] );
// Loai bo tags, extra spaces, octets

// Email
$email = sanitize_email( $_POST['email'] );
// Chi giu lai ky tu hop le cho email

// URL
$url = esc_url_raw( $_POST['url'] );
// Lam sach URL (dung khi LUU, khong phai khi hien thi)

// Textarea (nhieu dong)
$bio = sanitize_textarea_field( $_POST['bio'] );
// Giong sanitize_text_field nhung giu line breaks

// Filename
$file = sanitize_file_name( $_FILES['upload']['name'] );
// Loai bo ky tu dac biet, spaces

// HTML content (cho phep HTML an toan)
$content = wp_kses_post( $_POST['content'] );
// Chi cho phep HTML tags an toan cho post content

// HTML voi rules tuy chinh
$allowed = array(
    'a'      => array( 'href' => array(), 'title' => array() ),
    'strong' => array(),
    'em'     => array(),
    'p'      => array(),
);
$safe_html = wp_kses( $_POST['html'], $allowed );

// Integer
$id = absint( $_POST['id'] );
// Tra ve so nguyen duong (>= 0)

// Key (slug-like)
$key = sanitize_key( $_POST['key'] );
// Chi giu chu thuong, so, dashes, underscores

// Title
$title = sanitize_title( $_POST['title'] );
// Tao slug tu title

// Hex color
$color = sanitize_hex_color( $_POST['color'] );
// Chi chap nhan format #fff hoac #ffffff
```

### Vi du thuc te

```php
// Xu ly form dang ky
function process_registration() {
    // Kiem tra nonce
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'register_user' ) ) {
        wp_die( 'Invalid request.' );
    }

    // Sanitize moi truong du lieu
    $data = array(
        'username' => sanitize_user( $_POST['username'] ),
        'email'    => sanitize_email( $_POST['email'] ),
        'name'     => sanitize_text_field( $_POST['name'] ),
        'website'  => esc_url_raw( $_POST['website'] ),
        'bio'      => sanitize_textarea_field( $_POST['bio'] ),
        'age'      => absint( $_POST['age'] ),
    );

    // Validate sau khi sanitize
    if ( empty( $data['username'] ) || empty( $data['email'] ) ) {
        wp_die( 'Username va email la bat buoc.' );
    }

    if ( ! is_email( $data['email'] ) ) {
        wp_die( 'Email khong hop le.' );
    }

    // Luu vao database (da sach)
    wp_insert_user( $data );
}
```

---

## 3. Escaping - An Toan Hoa Du Lieu Dau Ra

Escaping bao ve chong XSS khi hien thi du lieu.

### Quy tac: Escape MUON NHAT co the (ngay truoc khi output)

```php
// === TRONG HTML TEXT ===
echo esc_html( $user_name );
// Chuyen: < > & " ' thanh HTML entities
// Dung cho: noi dung text thong thuong

// === TRONG HTML ATTRIBUTES ===
echo '<input value="' . esc_attr( $value ) . '">';
// Dung cho: value, title, alt, class, id, ...

// === TRONG URLs ===
echo '<a href="' . esc_url( $url ) . '">Link</a>';
// Kiem tra protocol (chi cho http, https, mailto, ...)
// Dung cho: href, src, action, ...

// === TRONG JAVASCRIPT ===
echo '<script>var name = "' . esc_js( $name ) . '";</script>';
// Escape cho JavaScript strings

// === HTML AN TOAN (cho post content) ===
echo wp_kses_post( $content );
// Cho phep HTML tags an toan, loai bo scripts

// === Cac ham ket hop echo ===
esc_html_e( 'Text', 'textdomain' );      // echo + escape + translate
echo esc_html__( 'Text', 'textdomain' );  // escape + translate (return)
```

### Bang tom tat

| Ngu canh | Ham | Vi du |
|----------|-----|-------|
| HTML text | `esc_html()` | `<p><?php echo esc_html($var); ?></p>` |
| HTML attribute | `esc_attr()` | `<input value="<?php echo esc_attr($var); ?>">` |
| URL | `esc_url()` | `<a href="<?php echo esc_url($var); ?>">` |
| JavaScript | `esc_js()` | `<script>var x = "<?php echo esc_js($var); ?>";</script>` |
| HTML content | `wp_kses_post()` | `<div><?php echo wp_kses_post($content); ?></div>` |
| CSS | `safecss_filter_attr()` | Cho phep CSS an toan |

### Sai vs Dung

```php
// SAI - Khong escape
echo '<p>' . $user_input . '</p>';                    // XSS!
echo '<a href="' . $url . '">Link</a>';              // XSS!
echo '<input value="' . $value . '">';                // XSS!

// DUNG - Co escape
echo '<p>' . esc_html( $user_input ) . '</p>';
echo '<a href="' . esc_url( $url ) . '">Link</a>';
echo '<input value="' . esc_attr( $value ) . '">';
```

---

## 4. Validation - Kiem Tra Du Lieu

Validation kiem tra du lieu co dung format/range khong.

```php
// Kiem tra email hop le
if ( ! is_email( $email ) ) {
    return new WP_Error( 'invalid_email', 'Email khong hop le.' );
}

// Kiem tra URL hop le
if ( ! wp_http_validate_url( $url ) ) {
    return new WP_Error( 'invalid_url', 'URL khong hop le.' );
}

// Kiem tra so trong khoang
$age = absint( $_POST['age'] );
if ( $age < 1 || $age > 150 ) {
    return new WP_Error( 'invalid_age', 'Tuoi khong hop le.' );
}

// Kiem tra gia tri trong danh sach
$status = sanitize_text_field( $_POST['status'] );
$valid_statuses = array( 'active', 'inactive', 'pending' );
if ( ! in_array( $status, $valid_statuses, true ) ) {
    return new WP_Error( 'invalid_status', 'Trang thai khong hop le.' );
}

// Kiem tra file type
$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif' );
if ( ! in_array( $_FILES['image']['type'], $allowed_types, true ) ) {
    return new WP_Error( 'invalid_type', 'Chi cho phep JPEG, PNG, GIF.' );
}
```

---

## 5. Nonces - Chong CSRF

### Nonce trong form

```php
// TAO FORM voi nonce
function my_form() {
    ?>
    <form method="post">
        <?php wp_nonce_field( 'my_action_name', 'my_nonce_field' ); ?>
        <input type="text" name="data" />
        <button type="submit">Gui</button>
    </form>
    <?php
}

// XU LY FORM - kiem tra nonce
function handle_form() {
    // Cach 1: wp_verify_nonce
    if ( ! isset( $_POST['my_nonce_field'] ) ||
         ! wp_verify_nonce( $_POST['my_nonce_field'], 'my_action_name' ) ) {
        wp_die( 'Security check failed.' );
    }

    // Cach 2: check_admin_referer (cho admin pages)
    check_admin_referer( 'my_action_name', 'my_nonce_field' );

    // Xu ly du lieu...
}
```

### Nonce trong URL

```php
// Tao URL voi nonce
$url = wp_nonce_url(
    admin_url( 'admin.php?page=my-page&action=delete&id=5' ),
    'delete_item_5'
);
echo '<a href="' . esc_url( $url ) . '">Xoa</a>';

// Kiem tra nonce tu URL
check_admin_referer( 'delete_item_5' );
```

### Nonce trong AJAX

```php
// PHP - tao nonce
wp_localize_script( 'my-script', 'myData', array(
    'nonce' => wp_create_nonce( 'my_ajax_nonce' ),
) );

// JavaScript - gui nonce
fetch(myData.ajaxUrl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'my_action',
        nonce: myData.nonce,
        data: 'value'
    })
});

// PHP - kiem tra nonce
function my_ajax_handler() {
    check_ajax_referer( 'my_ajax_nonce', 'nonce' );
    // Xu ly...
}
```

---

## 6. Capability Checks - Kiem Tra Quyen

```php
// Kiem tra truoc moi hanh dong quan trong
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Ban khong co quyen thuc hien hanh dong nay.' );
}

// Kiem tra quyen tren doi tuong cu the
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    wp_die( 'Ban khong co quyen sua bai viet nay.' );
}
```

### Cac capability pho bien

| Capability | Roles |
|-----------|-------|
| `manage_options` | Administrator |
| `edit_others_posts` | Administrator, Editor |
| `publish_posts` | Administrator, Editor, Author |
| `edit_posts` | Administrator, Editor, Author, Contributor |
| `read` | Tat ca roles |
| `upload_files` | Administrator, Editor, Author |
| `delete_posts` | Administrator, Editor, Author, Contributor |
| `edit_pages` | Administrator, Editor |

### Trong meta box

```php
function save_my_meta( $post_id ) {
    // 1. Kiem tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 2. Kiem tra nonce
    if ( ! wp_verify_nonce( $_POST['my_nonce'] ?? '', 'save_my_meta' ) ) {
        return;
    }

    // 3. Kiem tra quyen
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 4. Sanitize va luu
    $value = sanitize_text_field( $_POST['my_field'] ?? '' );
    update_post_meta( $post_id, '_my_field', $value );
}
add_action( 'save_post', 'save_my_meta' );
```

---

## 7. SQL Injection Prevention

### LUON dung $wpdb->prepare()

```php
global $wpdb;

// DUNG
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_author = %d AND post_status = %s",
        $author_id,
        'publish'
    )
);

// DUNG - voi LIKE
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_title LIKE %s",
        '%' . $wpdb->esc_like( $search_term ) . '%'
    )
);

// DUNG - voi IN
$ids = array( 1, 2, 3 );
$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID IN ($placeholders)",
        ...$ids
    )
);

// SAI - SQL Injection!
$wpdb->query( "SELECT * FROM wp_posts WHERE ID = $id" );
$wpdb->query( "SELECT * FROM wp_posts WHERE post_title = '$title'" );
```

---

## 8. XSS Prevention

### Cac diem can escape

```php
// 1. Hien thi du lieu tu database
echo '<h1>' . esc_html( get_the_title() ) . '</h1>';

// 2. Hien thi du lieu tu $_GET, $_POST
echo '<p>Tim kiem: ' . esc_html( $_GET['s'] ?? '' ) . '</p>';

// 3. Hien thi du lieu trong attribute
echo '<div class="' . esc_attr( $class ) . '">';

// 4. Hien thi URL
echo '<a href="' . esc_url( $link ) . '">Click</a>';

// 5. Inline JavaScript
wp_localize_script( 'my-script', 'data', array(
    'value' => $user_input  // WordPress tu dong escape
) );
```

---

## 9. File Upload Security

```php
function handle_file_upload() {
    // 1. Kiem tra nonce
    check_admin_referer( 'file_upload', 'upload_nonce' );

    // 2. Kiem tra quyen
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_die( 'Khong co quyen upload.' );
    }

    // 3. Kiem tra file
    if ( empty( $_FILES['my_file'] ) ) {
        return;
    }

    // 4. Kiem tra MIME type
    $file = $_FILES['my_file'];
    $allowed = array( 'image/jpeg', 'image/png', 'application/pdf' );

    $file_info = wp_check_filetype( $file['name'] );
    if ( ! in_array( $file_info['type'], $allowed, true ) ) {
        wp_die( 'Loai file khong duoc phep.' );
    }

    // 5. Kiem tra kich thuoc (5MB)
    if ( $file['size'] > 5 * 1024 * 1024 ) {
        wp_die( 'File qua lon (toi da 5MB).' );
    }

    // 6. Su dung WordPress media handler
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_handle_upload( 'my_file', 0 );

    if ( is_wp_error( $attachment_id ) ) {
        wp_die( $attachment_id->get_error_message() );
    }

    return $attachment_id;
}
```

---

## 10. WordPress Security Constants

Dat trong `wp-config.php`:

```php
// Tat chinh sua file tu admin (plugin editor, theme editor)
define( 'DISALLOW_FILE_EDIT', true );

// Tat cai dat/cap nhat plugin/theme tu admin
define( 'DISALLOW_FILE_MODS', true );

// Bat buoc SSL cho admin
define( 'FORCE_SSL_ADMIN', true );

// Thoi gian het han cookie dang nhap
define( 'AUTH_COOKIE_EXPIRATION', 2 * DAY_IN_SECONDS );

// Gioi han revisions
define( 'WP_POST_REVISIONS', 5 );

// Auto-update core (chi security)
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// Block external HTTP requests (neu khong can)
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'WP_ACCESSIBLE_HOSTS', 'api.wordpress.org,downloads.wordpress.org' );
```

---

## 11. Hardening wp-config.php

```php
// 1. Di chuyen wp-config.php len 1 cap thu muc
// WordPress tu dong tim wp-config.php o thu muc cha

// 2. Doi table prefix
$table_prefix = 'wp_abc123_';  // Thay vi 'wp_'

// 3. Security Keys manh
// Tao tai: https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'SECURE_AUTH_KEY',  'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'LOGGED_IN_KEY',    'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'NONCE_KEY',        'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'AUTH_SALT',        'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'SECURE_AUTH_SALT', 'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'LOGGED_IN_SALT',   'chuoi-ngau-nhien-dai-va-phuc-tap' );
define( 'NONCE_SALT',       'chuoi-ngau-nhien-dai-va-phuc-tap' );
```

---

## 12. .htaccess Security

```apache
# Chan truy cap wp-config.php
<Files wp-config.php>
    Order Allow,Deny
    Deny from all
</Files>

# Chan truy cap .htaccess
<Files .htaccess>
    Order Allow,Deny
    Deny from all
</Files>

# Tat directory listing
Options -Indexes

# Chan truy cap file PHP trong uploads
<Directory "wp-content/uploads">
    <Files "*.php">
        Order Allow,Deny
        Deny from all
    </Files>
</Directory>

# Chan truy cap xmlrpc.php (neu khong dung)
<Files xmlrpc.php>
    Order Allow,Deny
    Deny from all
</Files>

# Bao ve chong hotlinking
RewriteEngine On
RewriteCond %{HTTP_REFERER} !^$
RewriteCond %{HTTP_REFERER} !^https?://(www\.)?example\.com [NC]
RewriteRule \.(jpg|jpeg|png|gif)$ - [F,NC]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

---

## 13. Checklist Bao Mat

### Khi viet code

- [ ] Sanitize tat ca input tu $_GET, $_POST, $_REQUEST, $_FILES
- [ ] Escape tat ca output voi esc_html, esc_attr, esc_url
- [ ] Su dung nonce cho moi form va AJAX request
- [ ] Kiem tra capability truoc moi hanh dong
- [ ] Dung $wpdb->prepare() cho moi SQL query co bien
- [ ] Validate du lieu (type, range, format)
- [ ] Kiem tra file type va size khi upload

### Khi cau hinh server

- [ ] Su dung HTTPS (SSL)
- [ ] DISALLOW_FILE_EDIT = true
- [ ] Doi table prefix
- [ ] Tao Security Keys manh
- [ ] Chan truy cap wp-config.php
- [ ] Tat directory listing
- [ ] Cap nhat WordPress, plugins, themes thuong xuyen
- [ ] Su dung mat khau manh
- [ ] Gioi han so lan dang nhap that bai
- [ ] Backup dinh ky

### Khi phat trien plugin/theme

- [ ] Tuan thu WordPress Coding Standards
- [ ] Su dung prefix cho tat ca functions, classes, hooks
- [ ] Khong luu mat khau dang plain text
- [ ] Khong ghi thong tin nhay cam vao log
- [ ] Test voi WP_DEBUG bat
- [ ] Review code bao mat truoc khi release

---

## Tai Lieu Tham Khao

- [WordPress Plugin Security](https://developer.wordpress.org/plugins/security/)
- [Data Validation](https://developer.wordpress.org/plugins/security/data-validation/)
- [Nonces](https://developer.wordpress.org/plugins/security/nonces/)
- [OWASP WordPress Security](https://owasp.org/www-project-web-security-testing-guide/)
