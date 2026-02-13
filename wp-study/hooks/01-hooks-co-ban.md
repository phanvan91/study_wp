# WordPress Hooks - Kiến Thức Cơ Bản

## Mục Lục

1. [Hooks là gì?](#1-hooks-là-gì)
2. [Event-Driven Architecture trong WordPress](#2-event-driven-architecture-trong-wordpress)
3. [Action Hooks vs Filter Hooks](#3-action-hooks-vs-filter-hooks)
4. [add_action() và do_action()](#4-add_action-và-do_action)
5. [add_filter() và apply_filters()](#5-add_filter-và-apply_filters)
6. [remove_action() và remove_filter()](#6-remove_action-và-remove_filter)
7. [has_action() và has_filter()](#7-has_action-và-has_filter)
8. [Priority - Độ ưu tiên và thứ tự thực thi](#8-priority---độ-ưu-tiên-và-thứ-tự-thực-thi)
9. [Accepted Arguments](#9-accepted-arguments)
10. [Anonymous Functions (Closures)](#10-anonymous-functions-closures)
11. [So sánh với Laravel Events/Listeners](#11-so-sánh-với-laravel-eventslisteners)
12. [Best Practices](#12-best-practices)

---

## 1. Hooks là gì?

### Khái niệm

**Hooks** là cơ chế cốt lõi của WordPress cho phép bạn "móc" (hook) code của mình vào các điểm cụ thể trong quá trình WordPress xử lý request. Thay vì sửa trực tiếp code core của WordPress, bạn sử dụng hooks để thêm, thay đổi hoặc xóa chức năng.

Nếu bạn đến từ Laravel, hãy nghĩ hooks như **Events & Listeners** - WordPress "phát" (emit) các sự kiện tại các thời điểm nhất định, và code của bạn "lắng nghe" (listen) những sự kiện đó.

### Tại sao WordPress dùng Hooks?

```
Vấn đề: Bạn muốn thêm Google Analytics vào <head> của trang
```

**Cách SAI - Sửa trực tiếp file core:**
```php
// File: wp-includes/general-template.php (ĐỪNG LÀM THẾ NÀY!)
function wp_head() {
    echo '<script>/* Google Analytics */</script>'; // Sẽ mất khi update WordPress
    do_action( 'wp_head' );
}
```

**Cách ĐÚNG - Dùng Hooks:**
```php
// File: wp-content/themes/your-theme/functions.php
// Hoặc: wp-content/plugins/your-plugin/your-plugin.php

/**
 * Thêm Google Analytics tracking code vào <head>
 * Code này sẽ không bị mất khi update WordPress
 */
add_action( 'wp_head', 'my_add_google_analytics' );
function my_add_google_analytics() {
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    <?php
}
```

### Lợi ích chính của Hooks

| Lợi ích | Giải thích |
|---------|-----------|
| **An toàn khi update** | Code của bạn nằm riêng, không bị ghi đè khi update WordPress |
| **Modular** | Plugin/Theme có thể thêm/bớt chức năng mà không ảnh hưởng nhau |
| **Extensible** | Plugin của bạn có thể tạo hooks để plugin khác mở rộng |
| **Dễ debug** | Biết chính xác code nào chạy tại thời điểm nào |

---

## 2. Event-Driven Architecture trong WordPress

### Mô hình hoạt động

```
WordPress Core chạy    →    Gặp Hook Point    →    Thực thi Callbacks đã đăng ký
                                                           ↓
                              do_action('init')    →    callback_1() (priority 5)
                                                        callback_2() (priority 10)
                                                        callback_3() (priority 20)
```

### Dòng chảy đơn giản

```
┌──────────────────────────────────────────────────────────┐
│                    WordPress Request                      │
│                                                           │
│  1. Load wp-config.php                                    │
│  2. Load wp-settings.php                                  │
│  3. Load plugins         → do_action('plugins_loaded')    │
│  4. Load theme           → do_action('after_setup_theme') │
│  5. Init                 → do_action('init')              │
│  6. Parse request        → do_action('parse_request')     │
│  7. Run query            → apply_filters('pre_get_posts') │
│  8. Load template        → do_action('template_redirect') │
│  9. Output header        → do_action('wp_head')           │
│  10. Output content      → apply_filters('the_content')   │
│  11. Output footer       → do_action('wp_footer')         │
│  12. Shutdown            → do_action('shutdown')           │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

### Ví dụ thực tế - Đăng ký chức năng vào các thời điểm khác nhau

```php
<?php
/**
 * Plugin Name: My Hook Demo
 * Description: Demo cách hooks hoạt động theo thứ tự
 */

// 1. Chạy khi tất cả plugin đã load xong
// Dùng để: tương tác với plugin khác, khởi tạo text domain
add_action( 'plugins_loaded', function() {
    // Load ngôn ngữ cho plugin
    load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});

// 2. Chạy khi theme đã load
// Dùng để: đăng ký support cho theme features
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
});

// 3. Chạy khi WordPress đã khởi tạo xong
// Dùng để: đăng ký post types, taxonomies, shortcodes
add_action( 'init', function() {
    register_post_type( 'product', array(
        'label'  => 'Sản phẩm',
        'public' => true,
    ));
});

// 4. Chạy khi xuất thẻ <head>
// Dùng để: thêm meta tags, scripts inline
add_action( 'wp_head', function() {
    echo '<meta name="author" content="My Name">';
});

// 5. Filter nội dung bài viết trước khi hiển thị
// Dùng để: thêm nội dung, sửa đổi text
add_filter( 'the_content', function( $content ) {
    if ( is_single() ) {
        $content .= '<p><strong>Cảm ơn bạn đã đọc!</strong></p>';
    }
    return $content;
});
```

---

## 3. Action Hooks vs Filter Hooks

### Sự khác biệt cốt lõi

| Đặc điểm | Action Hook | Filter Hook |
|-----------|-------------|-------------|
| **Mục đích** | Thực thi hành động (side effect) | Biến đổi dữ liệu |
| **Trả về giá trị** | Không cần return | **Bắt buộc** phải return |
| **Nhận tham số** | Tham số để sử dụng | Tham số đầu tiên là giá trị cần filter |
| **Đăng ký** | `add_action()` | `add_filter()` |
| **Kích hoạt** | `do_action()` | `apply_filters()` |
| **Laravel tương đương** | Event/Listener | Middleware (pipeline) |

### Action Hook - Thực thi hành động

```php
<?php
// ACTION: "Khi user đăng nhập, hãy LÀM GÌ ĐÓ"
// Không cần trả về giá trị
add_action( 'wp_login', 'my_after_login', 10, 2 );
function my_after_login( $user_login, $user ) {
    // Ghi log đăng nhập (side effect - tác dụng phụ)
    error_log( "User {$user_login} đã đăng nhập lúc " . current_time( 'mysql' ) );

    // Gửi email thông báo (side effect)
    wp_mail( 'admin@example.com', 'Đăng nhập mới', "User: {$user_login}" );

    // KHÔNG cần return
}
```

### Filter Hook - Biến đổi dữ liệu

```php
<?php
// FILTER: "Trước khi hiển thị tiêu đề, hãy THAY ĐỔI NÓ"
// BẮT BUỘC phải trả về giá trị
add_filter( 'the_title', 'my_modify_title', 10, 2 );
function my_modify_title( $title, $post_id ) {
    // Thêm icon vào trước tiêu đề bài viết sticky
    if ( is_sticky( $post_id ) ) {
        $title = '⭐ ' . $title;
    }

    // BẮT BUỘC phải return - nếu không, tiêu đề sẽ biến mất!
    return $title;
}
```

### So sánh trực quan

```
ACTION HOOK (do_action):
┌─────────────┐     ┌────────────┐     ┌────────────┐
│ WordPress    │────→│ Callback 1 │────→│ Callback 2 │────→ (tiếp tục)
│ do_action()  │     │ Ghi log    │     │ Gửi email  │
└─────────────┘     └────────────┘     └────────────┘
   Không có giá trị truyền qua giữa các callbacks

FILTER HOOK (apply_filters):
┌─────────────┐     ┌────────────┐     ┌────────────┐     ┌──────────┐
│ WordPress    │────→│ Callback 1 │────→│ Callback 2 │────→│ Kết quả  │
│ "Hello"      │     │ "Hello!"   │     │ "HELLO!"   │     │ "HELLO!" │
└─────────────┘     └────────────┘     └────────────┘     └──────────┘
   Giá trị được truyền qua và biến đổi bởi mỗi callback
```

---

## 4. add_action() và do_action()

### Cú pháp đầy đủ

```php
<?php
/**
 * add_action() - Đăng ký một callback để chạy khi hook được kích hoạt
 *
 * @param string   $hook_name      Tên của hook
 * @param callable $callback        Hàm sẽ được gọi
 * @param int      $priority        Thứ tự ưu tiên (mặc định: 10)
 * @param int      $accepted_args   Số tham số callback nhận (mặc định: 1)
 * @return true    Luôn trả về true
 */
add_action( $hook_name, $callback, $priority, $accepted_args );

/**
 * do_action() - Kích hoạt một hook, chạy tất cả callbacks đã đăng ký
 *
 * @param string $hook_name    Tên của hook
 * @param mixed  ...$args      Các tham số truyền cho callbacks
 */
do_action( $hook_name, ...$args );
```

### Ví dụ 1: Hook cơ bản nhất

```php
<?php
// File: wp-content/themes/mytheme/functions.php

// Đăng ký callback cho hook 'init'
// Hook 'init' được WordPress tự kích hoạt khi khởi tạo xong
add_action( 'init', 'my_custom_init' );

function my_custom_init() {
    // Code ở đây chạy khi WordPress khởi tạo xong
    // Đây là nơi lý tưởng để đăng ký Custom Post Types

    register_post_type( 'book', array(
        'labels' => array(
            'name'          => 'Sách',
            'singular_name' => 'Cuốn sách',
            'add_new'       => 'Thêm sách mới',
            'add_new_item'  => 'Thêm cuốn sách mới',
            'edit_item'     => 'Sửa sách',
            'view_item'     => 'Xem sách',
        ),
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true, // Hỗ trợ Gutenberg editor
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'    => 'dashicons-book',
        'rewrite'      => array( 'slug' => 'sach' ),
    ));
}
```

### Ví dụ 2: do_action() với tham số

```php
<?php
// File: wp-content/plugins/my-ecommerce/my-ecommerce.php

/**
 * Plugin Name: My E-Commerce
 * Description: Plugin bán hàng đơn giản minh họa do_action()
 */

// Hàm xử lý đặt hàng
function my_process_order( $order_data ) {
    // Bước 1: Validate dữ liệu
    $order_id = wp_insert_post( array(
        'post_type'   => 'shop_order',
        'post_title'  => 'Đơn hàng #' . time(),
        'post_status' => 'publish',
    ));

    // Bước 2: Lưu thông tin đơn hàng
    update_post_meta( $order_id, '_customer_name', sanitize_text_field( $order_data['name'] ) );
    update_post_meta( $order_id, '_customer_email', sanitize_email( $order_data['email'] ) );
    update_post_meta( $order_id, '_order_total', floatval( $order_data['total'] ) );

    // Bước 3: Kích hoạt hook để plugin/theme khác có thể xử lý thêm
    // Ví dụ: gửi email, ghi log, cập nhật kho, gửi notification...
    do_action( 'my_ecommerce_order_placed', $order_id, $order_data );

    return $order_id;
}

// Plugin khác có thể "hook" vào sự kiện đặt hàng:

// Gửi email xác nhận
add_action( 'my_ecommerce_order_placed', 'my_send_order_confirmation', 10, 2 );
function my_send_order_confirmation( $order_id, $order_data ) {
    $to      = $order_data['email'];
    $subject = 'Xác nhận đơn hàng #' . $order_id;
    $message = sprintf(
        "Xin chào %s,\n\nĐơn hàng #%d của bạn đã được tiếp nhận.\nTổng tiền: %s VNĐ\n\nCảm ơn bạn!",
        $order_data['name'],
        $order_id,
        number_format( $order_data['total'] )
    );

    wp_mail( $to, $subject, $message );
}

// Ghi log đơn hàng
add_action( 'my_ecommerce_order_placed', 'my_log_order', 20, 2 );
function my_log_order( $order_id, $order_data ) {
    error_log( sprintf(
        '[My E-Commerce] Đơn hàng mới #%d - Khách: %s - Tổng: %s VNĐ',
        $order_id,
        $order_data['name'],
        number_format( $order_data['total'] )
    ));
}
```

### Ví dụ 3: Các hook admin thường dùng

```php
<?php
// Thêm menu trong admin
add_action( 'admin_menu', 'my_add_admin_menu' );
function my_add_admin_menu() {
    // Thêm menu cấp 1
    add_menu_page(
        'Quản lý cửa hàng',         // Tiêu đề trang
        'Cửa hàng',                  // Text hiển thị trong menu
        'manage_options',             // Capability cần có
        'my-shop',                    // Menu slug (URL)
        'my_shop_page_callback',      // Hàm render nội dung trang
        'dashicons-store',            // Icon
        30                            // Vị trí trong menu
    );

    // Thêm submenu
    add_submenu_page(
        'my-shop',                    // Parent slug
        'Đơn hàng',                  // Tiêu đề trang
        'Đơn hàng',                  // Text menu
        'manage_options',             // Capability
        'my-shop-orders',            // Menu slug
        'my_shop_orders_callback'     // Callback
    );
}

function my_shop_page_callback() {
    echo '<div class="wrap">';
    echo '<h1>Quản lý cửa hàng</h1>';
    echo '<p>Đây là trang quản lý cửa hàng của bạn.</p>';
    echo '</div>';
}

function my_shop_orders_callback() {
    echo '<div class="wrap">';
    echo '<h1>Danh sách đơn hàng</h1>';
    echo '</div>';
}
```

---

## 5. add_filter() và apply_filters()

### Cú pháp đầy đủ

```php
<?php
/**
 * add_filter() - Đăng ký callback để filter (lọc/biến đổi) một giá trị
 *
 * @param string   $hook_name      Tên filter hook
 * @param callable $callback        Hàm filter
 * @param int      $priority        Thứ tự ưu tiên (mặc định: 10)
 * @param int      $accepted_args   Số tham số (mặc định: 1)
 * @return true
 */
add_filter( $hook_name, $callback, $priority, $accepted_args );

/**
 * apply_filters() - Áp dụng tất cả filter callbacks lên một giá trị
 *
 * @param string $hook_name    Tên filter hook
 * @param mixed  $value        Giá trị cần filter
 * @param mixed  ...$args      Các tham số bổ sung
 * @return mixed               Giá trị sau khi đã được filter
 */
$filtered_value = apply_filters( $hook_name, $value, ...$args );
```

### Ví dụ 1: Filter nội dung bài viết

```php
<?php
// File: wp-content/plugins/my-content-enhancer/my-content-enhancer.php

/**
 * Plugin Name: My Content Enhancer
 * Description: Tự động thêm nội dung vào bài viết
 */

// Filter 1: Thêm thông tin tác giả cuối bài viết
add_filter( 'the_content', 'my_add_author_bio', 10, 1 );
function my_add_author_bio( $content ) {
    // Chỉ thêm khi xem bài viết đơn (single post), không phải trang archive
    if ( ! is_single() ) {
        return $content; // Trả về nguyên bản nếu không phải single post
    }

    // Lấy thông tin tác giả
    $author_id   = get_the_author_meta( 'ID' );
    $author_name = get_the_author_meta( 'display_name' );
    $author_desc = get_the_author_meta( 'description' );
    $author_url  = get_author_posts_url( $author_id );

    // Tạo HTML cho author bio
    $author_box = sprintf(
        '<div class="author-bio" style="border:1px solid #ddd; padding:20px; margin-top:30px; border-radius:5px;">
            <h3>Về tác giả</h3>
            <p><strong><a href="%s">%s</a></strong></p>
            <p>%s</p>
        </div>',
        esc_url( $author_url ),
        esc_html( $author_name ),
        esc_html( $author_desc )
    );

    // Nối author bio vào SAU nội dung
    // LƯU Ý: Phải return, nếu quên return, nội dung bài viết sẽ biến mất!
    return $content . $author_box;
}

// Filter 2: Tự động thêm Table of Contents
add_filter( 'the_content', 'my_add_table_of_contents', 5, 1 );
function my_add_table_of_contents( $content ) {
    if ( ! is_single() ) {
        return $content;
    }

    // Tìm tất cả heading h2 trong nội dung
    preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches );

    if ( empty( $matches[1] ) || count( $matches[1] ) < 3 ) {
        return $content; // Không đủ heading để tạo TOC
    }

    // Tạo Table of Contents
    $toc = '<div class="table-of-contents" style="background:#f5f5f5; padding:15px; margin-bottom:20px; border-radius:5px;">';
    $toc .= '<h3>Mục lục</h3><ol>';

    foreach ( $matches[1] as $index => $heading ) {
        $slug = sanitize_title( $heading );
        $toc .= sprintf( '<li><a href="#%s">%s</a></li>', $slug, wp_strip_all_tags( $heading ) );

        // Thêm id vào heading trong nội dung để anchor link hoạt động
        $content = str_replace(
            $matches[0][ $index ],
            sprintf( '<h2 id="%s">%s</h2>', $slug, $heading ),
            $content
        );
    }

    $toc .= '</ol></div>';

    // Chèn TOC vào ĐẦU nội dung
    return $toc . $content;
}
```

### Ví dụ 2: Filter excerpt (đoạn trích)

```php
<?php
// Thay đổi độ dài excerpt
add_filter( 'excerpt_length', 'my_custom_excerpt_length' );
function my_custom_excerpt_length( $length ) {
    // $length mặc định là 55 (từ)
    // Trả về số từ mới cho excerpt
    return 30; // Rút ngắn xuống 30 từ
}

// Thay đổi dấu [...] cuối excerpt
add_filter( 'excerpt_more', 'my_custom_excerpt_more' );
function my_custom_excerpt_more( $more ) {
    // $more mặc định là ' [&hellip;]'
    // Thay bằng link "Đọc tiếp"
    return sprintf(
        '... <a href="%s" class="read-more">Đọc tiếp &raquo;</a>',
        esc_url( get_permalink() )
    );
}
```

### Ví dụ 3: Filter query bài viết

```php
<?php
// pre_get_posts: Filter cực kỳ mạnh để thay đổi query WordPress
add_filter( 'pre_get_posts', 'my_modify_main_query' );
function my_modify_main_query( $query ) {
    // QUAN TRỌNG: Chỉ modify main query, không phải custom query
    // Và chỉ ở frontend, không phải admin
    if ( ! $query->is_main_query() || is_admin() ) {
        return $query;
    }

    // Trang chủ: Chỉ hiển thị 5 bài mới nhất, bỏ qua sticky posts
    if ( $query->is_home() ) {
        $query->set( 'posts_per_page', 5 );
        $query->set( 'ignore_sticky_posts', true );
    }

    // Trang tìm kiếm: Chỉ tìm trong post, không tìm page
    if ( $query->is_search() ) {
        $query->set( 'post_type', 'post' );
    }

    // Trang archive theo category: Sắp xếp theo title A-Z
    if ( $query->is_category() ) {
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }

    return $query;
}
```

---

## 6. remove_action() và remove_filter()

### Cú pháp

```php
<?php
/**
 * remove_action() - Gỡ bỏ một callback đã đăng ký với action hook
 *
 * @param string   $hook_name  Tên hook
 * @param callable $callback   Callback cần gỡ
 * @param int      $priority   Priority đã dùng khi add (PHẢI KHỚP!)
 * @return bool    True nếu gỡ thành công
 */
remove_action( $hook_name, $callback, $priority );

/**
 * remove_filter() - Gỡ bỏ một callback đã đăng ký với filter hook
 * Tham số tương tự remove_action()
 */
remove_filter( $hook_name, $callback, $priority );
```

### Ví dụ 1: Gỡ bỏ action từ WordPress core

```php
<?php
// File: functions.php

// Gỡ bỏ emoji scripts (tăng tốc trang)
// WordPress tự thêm emoji support, nhưng hầu hết theme không cần
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

// Gỡ bỏ WordPress version trong <head> (bảo mật)
// <meta name="generator" content="WordPress 6.x" /> sẽ bị xóa
remove_action( 'wp_head', 'wp_generator' );

// Gỡ bỏ RSD link (không cần nếu không dùng XML-RPC)
remove_action( 'wp_head', 'rsd_link' );

// Gỡ bỏ wlwmanifest link (Windows Live Writer - không ai dùng nữa)
remove_action( 'wp_head', 'wlwmanifest_link' );

// Gỡ bỏ shortlink
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
```

### Ví dụ 2: Gỡ bỏ filter từ plugin khác

```php
<?php
// Giả sử plugin "Super SEO" thêm filter vào the_title:
// add_filter( 'the_title', 'super_seo_modify_title', 10 );

// Bạn muốn gỡ bỏ filter đó
// QUAN TRỌNG: Priority phải KHỚP CHÍNH XÁC với khi add (ở đây là 10)
add_action( 'init', function() {
    remove_filter( 'the_title', 'super_seo_modify_title', 10 );
});
```

### Ví dụ 3: Gỡ bỏ tất cả callbacks của một hook

```php
<?php
/**
 * remove_all_actions() - Gỡ TẤT CẢ callbacks của một action hook
 * remove_all_filters() - Gỡ TẤT CẢ callbacks của một filter hook
 *
 * CẨN THẬN: Hàm này rất mạnh, có thể gây hỏng chức năng
 * Chỉ dùng khi thực sự cần thiết
 */

// Gỡ tất cả callbacks của wp_head (ĐỪNG LÀM NHƯ NÀY trong thực tế!)
// remove_all_actions( 'wp_head' );

// Gỡ tất cả callbacks ở priority cụ thể (an toàn hơn)
// remove_all_actions( 'wp_head', 10 );
```

### Lưu ý quan trọng khi remove

```php
<?php
// LỖI THƯỜNG GẶP #1: Priority không khớp
add_action( 'init', 'my_function', 15 );       // Thêm ở priority 15
remove_action( 'init', 'my_function', 10 );     // Gỡ ở priority 10 → KHÔNG HOẠT ĐỘNG!
remove_action( 'init', 'my_function', 15 );     // Gỡ ở priority 15 → OK!

// LỖI THƯỜNG GẶP #2: Remove quá sớm
// Nếu plugin A thêm hook trong 'plugins_loaded', bạn phải remove SAU đó
// Cách sai:
remove_action( 'init', 'plugin_a_function', 10 ); // Chạy ngay - plugin A chưa add!

// Cách đúng: Remove trong hook chạy sau
add_action( 'after_setup_theme', function() {
    remove_action( 'init', 'plugin_a_function', 10 );
}, 20 );

// LỖI THƯỜNG GẶP #3: Remove anonymous function
add_action( 'init', function() {
    echo 'Hello';
}, 10 );
// KHÔNG THỂ remove anonymous function!
// remove_action( 'init', ???, 10 ); // Không có tên để tham chiếu!
```

---

## 7. has_action() và has_filter()

### Cú pháp và công dụng

```php
<?php
/**
 * has_action() - Kiểm tra xem một callback có được đăng ký với hook không
 *
 * @param string        $hook_name  Tên hook
 * @param callable|false $callback  Callback cần kiểm tra (tùy chọn)
 * @return bool|int     - false nếu không có
 *                       - true nếu có (khi không truyền $callback)
 *                       - int (priority) nếu có callback cụ thể
 */
has_action( $hook_name, $callback );
has_filter( $hook_name, $callback );
```

### Ví dụ thực tế

```php
<?php
// Kiểm tra xem hook 'init' có callback nào không
if ( has_action( 'init' ) ) {
    // Có ít nhất 1 callback đã đăng ký với 'init'
    error_log( 'Hook init có callbacks đã đăng ký' );
}

// Kiểm tra xem một function cụ thể có đăng ký với hook không
$priority = has_action( 'wp_head', 'wp_generator' );
if ( false !== $priority ) {
    // wp_generator đã đăng ký với wp_head, priority = $priority
    error_log( "wp_generator đăng ký ở priority: {$priority}" );
}

// Ứng dụng thực tế: Chỉ thêm callback nếu chưa có
if ( ! has_filter( 'the_content', 'my_custom_content_filter' ) ) {
    add_filter( 'the_content', 'my_custom_content_filter' );
}

// Ứng dụng: Kiểm tra xem plugin khác có hook vào không
// Ví dụ: Kiểm tra WooCommerce có active không
add_action( 'plugins_loaded', function() {
    if ( has_action( 'woocommerce_loaded' ) ) {
        // WooCommerce đang active, có thể tương tác
        error_log( 'WooCommerce detected!' );
    }
});
```

---

## 8. Priority - Độ ưu tiên và thứ tự thực thi

### Quy tắc

- Priority mặc định: **10**
- Số **nhỏ hơn** chạy **trước**
- Số **lớn hơn** chạy **sau**
- Cùng priority: chạy theo thứ tự đăng ký (FIFO - First In First Out)
- Range thường dùng: 1 đến 999 (PHP_INT_MAX cho "chạy cuối cùng")

### Ví dụ minh họa

```php
<?php
// Đăng ký 5 callbacks với cùng hook 'wp_head' nhưng khác priority

// Priority 1: Chạy ĐẦU TIÊN
add_action( 'wp_head', 'my_critical_meta_tags', 1 );
function my_critical_meta_tags() {
    echo '<!-- 1. Critical meta tags (priority 1) -->' . "\n";
    echo '<meta charset="UTF-8">' . "\n";
}

// Priority 5: Chạy thứ 2
add_action( 'wp_head', 'my_seo_tags', 5 );
function my_seo_tags() {
    echo '<!-- 2. SEO tags (priority 5) -->' . "\n";
    echo '<meta name="description" content="Mô tả trang">' . "\n";
}

// Priority 10 (mặc định): Chạy thứ 3
add_action( 'wp_head', 'my_default_head' );
function my_default_head() {
    echo '<!-- 3. Default priority (priority 10) -->' . "\n";
}

// Priority 99: Chạy thứ 4
add_action( 'wp_head', 'my_analytics_scripts', 99 );
function my_analytics_scripts() {
    echo '<!-- 4. Analytics (priority 99) -->' . "\n";
    echo '<script>/* Google Analytics */</script>' . "\n";
}

// Priority 999: Chạy GẦN CUỐI CÙNG
add_action( 'wp_head', 'my_debug_output', 999 );
function my_debug_output() {
    echo '<!-- 5. Debug output - gần cuối cùng (priority 999) -->' . "\n";
}
```

### Output trong `<head>`:
```html
<!-- 1. Critical meta tags (priority 1) -->
<meta charset="UTF-8">
<!-- 2. SEO tags (priority 5) -->
<meta name="description" content="Mô tả trang">
<!-- 3. Default priority (priority 10) -->
<!-- 4. Analytics (priority 99) -->
<script>/* Google Analytics */</script>
<!-- 5. Debug output - gần cuối cùng (priority 999) -->
```

### Filter với Priority - Chuỗi biến đổi

```php
<?php
// Xem cách giá trị bị biến đổi qua từng filter

// Filter 1: Chạy trước (priority 5) - Thêm prefix
add_filter( 'the_title', 'my_add_prefix', 5 );
function my_add_prefix( $title ) {
    return '[Blog] ' . $title;
    // "Hello World" → "[Blog] Hello World"
}

// Filter 2: Chạy sau (priority 10) - Viết hoa
add_filter( 'the_title', 'my_uppercase_title', 10 );
function my_uppercase_title( $title ) {
    return strtoupper( $title );
    // "[Blog] Hello World" → "[BLOG] HELLO WORLD"
}

// Filter 3: Chạy cuối (priority 20) - Cắt ngắn
add_filter( 'the_title', 'my_truncate_title', 20 );
function my_truncate_title( $title ) {
    if ( strlen( $title ) > 20 ) {
        return substr( $title, 0, 20 ) . '...';
    }
    return $title;
    // "[BLOG] HELLO WORLD" (19 ký tự) → giữ nguyên (không quá 20)
}

// Kết quả cuối cùng: "[BLOG] HELLO WORLD"
```

---

## 9. Accepted Arguments

### Giải thích

Tham số thứ 4 của `add_action()` / `add_filter()` cho WordPress biết callback của bạn muốn nhận bao nhiêu tham số.

```php
<?php
// do_action truyền 3 tham số:
do_action( 'save_post', $post_id, $post_object, $update );

// Callback chỉ nhận 1 tham số (mặc định):
add_action( 'save_post', 'my_save_v1' );
function my_save_v1( $post_id ) {
    // Chỉ có $post_id
    // $post_object và $update KHÔNG có
}

// Callback nhận 2 tham số:
add_action( 'save_post', 'my_save_v2', 10, 2 );
function my_save_v2( $post_id, $post_object ) {
    // Có $post_id VÀ $post_object
    // $update KHÔNG có
}

// Callback nhận 3 tham số (tất cả):
add_action( 'save_post', 'my_save_v3', 10, 3 );
function my_save_v3( $post_id, $post_object, $update ) {
    // Có tất cả: $post_id, $post_object, $update

    // $update = true nếu đang UPDATE bài cũ
    // $update = false nếu đang tạo bài MỚI

    if ( $update ) {
        error_log( "Bài viết #{$post_id} đã được cập nhật" );
    } else {
        error_log( "Bài viết #{$post_id} vừa được tạo mới" );
    }
}
```

### Ví dụ thực tế: Xử lý khi lưu bài viết

```php
<?php
/**
 * Tự động gửi email thông báo khi có bài viết mới được publish
 */
add_action( 'transition_post_status', 'my_notify_new_post', 10, 3 );
function my_notify_new_post( $new_status, $old_status, $post ) {
    // accepted_args = 3 để nhận cả 3 tham số

    // Chỉ xử lý khi bài viết chuyển sang trạng thái 'publish'
    // VÀ trạng thái cũ KHÔNG phải 'publish' (tránh trigger khi update)
    if ( 'publish' !== $new_status || 'publish' === $old_status ) {
        return;
    }

    // Chỉ xử lý cho post type 'post' (không phải page, attachment, etc.)
    if ( 'post' !== $post->post_type ) {
        return;
    }

    // Gửi email thông báo
    $admin_email = get_option( 'admin_email' );
    $post_title  = $post->post_title;
    $post_url    = get_permalink( $post->ID );

    $subject = sprintf( '[%s] Bài viết mới: %s', get_bloginfo( 'name' ), $post_title );
    $message = sprintf(
        "Một bài viết mới đã được xuất bản:\n\n" .
        "Tiêu đề: %s\n" .
        "Tác giả: %s\n" .
        "Link: %s\n",
        $post_title,
        get_the_author_meta( 'display_name', $post->post_author ),
        $post_url
    );

    wp_mail( $admin_email, $subject, $message );
}
```

---

## 10. Anonymous Functions (Closures)

### Sử dụng Closure làm callback

```php
<?php
// Cách 1: Named function (khuyến khích cho code lớn)
add_action( 'init', 'my_init_function' );
function my_init_function() {
    // Code xử lý
}

// Cách 2: Anonymous function (tiện cho code ngắn, đơn giản)
add_action( 'init', function() {
    // Code xử lý
});

// Cách 3: Arrow function (PHP 7.4+, cho code 1 dòng)
add_filter( 'excerpt_length', fn( $length ) => 25 );
```

### Khi nào dùng Anonymous Function?

```php
<?php
// TỐT: Code ngắn, đơn giản, không cần remove sau này
add_action( 'wp_head', function() {
    echo '<meta name="theme-color" content="#0073aa">';
});

// TỐT: Dùng closure để "đóng gói" biến
$custom_message = 'Chào mừng bạn đến với website!';
add_action( 'wp_footer', function() use ( $custom_message ) {
    // use() cho phép truy cập biến bên ngoài closure
    echo '<div class="welcome-message">' . esc_html( $custom_message ) . '</div>';
});

// TỐT: Filter đơn giản
add_filter( 'login_headerurl', function() {
    return home_url(); // Login logo link về trang chủ thay vì wordpress.org
});

add_filter( 'login_headertext', function() {
    return get_bloginfo( 'name' ); // Text cho login logo
});
```

### Cảnh báo: Không thể remove Anonymous Function

```php
<?php
// KHÔNG TỐT nếu bạn cần cho phép plugin/theme khác gỡ bỏ hook này
add_action( 'wp_head', function() {
    echo '<script>/* tracking code */</script>';
}, 10 );

// Plugin khác KHÔNG THỂ remove callback trên vì không có tên hàm!
// remove_action( 'wp_head', ???, 10 ); // Không thể!

// GIẢI PHÁP: Dùng named function nếu cần cho phép remove
add_action( 'wp_head', 'my_tracking_code', 10 );
function my_tracking_code() {
    echo '<script>/* tracking code */</script>';
}
// Bây giờ plugin khác có thể:
// remove_action( 'wp_head', 'my_tracking_code', 10 );
```

### Closure nâng cao: Dùng với biến

```php
<?php
/**
 * Tạo shortcode động dùng closure
 * [greeting name="An"] → "Xin chào An! Chúc một ngày tốt lành."
 */
$greeting_template = 'Xin chào %s! Chúc một ngày tốt lành.';

add_shortcode( 'greeting', function( $atts ) use ( $greeting_template ) {
    $atts = shortcode_atts( array(
        'name' => 'bạn',
    ), $atts, 'greeting' );

    return sprintf( $greeting_template, esc_html( $atts['name'] ) );
});

/**
 * Tạo nhiều filter tương tự nhau dùng vòng lặp + closure
 */
$social_meta = array(
    'og:site_name' => get_bloginfo( 'name' ),
    'og:locale'    => 'vi_VN',
    'og:type'      => 'website',
);

foreach ( $social_meta as $property => $content ) {
    // Mỗi vòng lặp tạo 1 closure riêng, "đóng gói" $property và $content
    add_action( 'wp_head', function() use ( $property, $content ) {
        printf(
            '<meta property="%s" content="%s">' . "\n",
            esc_attr( $property ),
            esc_attr( $content )
        );
    });
}
```

---

## 11. So sánh với Laravel Events/Listeners

### Bảng so sánh chi tiết

| WordPress | Laravel | Giải thích |
|-----------|---------|-----------|
| `do_action('event_name')` | `Event::dispatch(new EventName())` | Phát sự kiện |
| `add_action('event_name', 'callback')` | `EventServiceProvider` hoặc `Event::listen()` | Đăng ký listener |
| `apply_filters('filter_name', $value)` | Middleware Pipeline | Biến đổi dữ liệu qua chuỗi xử lý |
| `add_filter('filter_name', 'callback')` | `$next($request)` trong Middleware | Xử lý và truyền tiếp |
| `remove_action()` | Không có tương đương trực tiếp | Gỡ bỏ listener |
| Priority (1-999) | Listener `$priority` property | Thứ tự thực thi |
| `has_action()` | `Event::hasListeners()` | Kiểm tra có listener không |

### So sánh code cụ thể

**Laravel: Event khi user đăng ký**

```php
// Laravel: app/Events/UserRegistered.php
class UserRegistered {
    public $user;

    public function __construct(User $user) {
        $this->user = $user;
    }
}

// Laravel: app/Listeners/SendWelcomeEmail.php
class SendWelcomeEmail {
    public function handle(UserRegistered $event) {
        Mail::to($event->user->email)->send(new WelcomeMail($event->user));
    }
}

// Laravel: app/Providers/EventServiceProvider.php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmail::class,
        LogRegistration::class,
    ],
];

// Laravel: Phát sự kiện
event(new UserRegistered($user));
```

**WordPress: Hook khi user đăng ký**

```php
<?php
// WordPress: functions.php hoặc plugin file

// Đăng ký listener cho hook 'user_register'
// WordPress tự phát hook này khi user mới được tạo
add_action( 'user_register', 'my_send_welcome_email', 10, 1 );
function my_send_welcome_email( $user_id ) {
    $user = get_userdata( $user_id );

    $subject = 'Chào mừng bạn đến với ' . get_bloginfo( 'name' );
    $message = sprintf(
        "Xin chào %s,\n\nCảm ơn bạn đã đăng ký tài khoản.\n\nTrân trọng!",
        $user->display_name
    );

    wp_mail( $user->user_email, $subject, $message );
}

add_action( 'user_register', 'my_log_registration', 20, 1 );
function my_log_registration( $user_id ) {
    $user = get_userdata( $user_id );
    error_log( "User mới đăng ký: {$user->user_login} ({$user->user_email})" );
}
```

### So sánh Filter với Middleware

**Laravel Middleware Pipeline:**
```php
// Laravel: Request đi qua chuỗi middleware
// Request → Auth → Throttle → CSRF → Controller → Response

class TrimStrings extends Middleware {
    public function handle($request, Closure $next) {
        // Xử lý TRƯỚC
        $request = $this->trim($request);

        // Chuyển tiếp (như return trong filter)
        return $next($request);
    }
}
```

**WordPress Filter Pipeline:**
```php
<?php
// WordPress: Dữ liệu đi qua chuỗi filter callbacks
// "Hello" → trim → uppercase → add_prefix → "[PREFIX] HELLO"

// Giống middleware, mỗi filter nhận giá trị, xử lý, và trả về
add_filter( 'the_title', 'my_trim_title', 5 );
function my_trim_title( $title ) {
    return trim( $title ); // Xử lý và trả về (như $next($request))
}

add_filter( 'the_title', 'my_uppercase_title', 10 );
function my_uppercase_title( $title ) {
    return strtoupper( $title );
}
```

### Điểm khác biệt chính

```
Laravel:
- Type-safe: Event là class, có IDE autocompletion
- Có thể queue listener (chạy background)
- Listener là class riêng biệt, dễ test
- Cấu trúc rõ ràng: Event → Listener

WordPress:
- Đơn giản hơn: chỉ cần string hook name + callback
- Flexible hơn: callback có thể là function, closure, method
- Có thể remove/replace bất kỳ hook nào (kể cả core)
- Không cần boilerplate code (không cần tạo Event class)
- Không có queue (tất cả chạy synchronous)
```

---

## 12. Best Practices

### 1. Đặt tên prefix cho functions

```php
<?php
// SAI: Tên quá chung, dễ conflict với plugin khác
function init_settings() { }
function modify_title( $title ) { return $title; }

// ĐÚNG: Thêm prefix unique
function myplugin_init_settings() { }
function myplugin_modify_title( $title ) { return $title; }
```

### 2. Luôn return giá trị trong Filter

```php
<?php
// SAI: Quên return → giá trị bị mất!
add_filter( 'the_content', function( $content ) {
    $content .= '<p>Thêm nội dung</p>';
    // THIẾU return → $content = null → nội dung bài viết BIẾN MẤT!
});

// ĐÚNG: Luôn return
add_filter( 'the_content', function( $content ) {
    $content .= '<p>Thêm nội dung</p>';
    return $content; // Bắt buộc!
});
```

### 3. Kiểm tra điều kiện trước khi xử lý

```php
<?php
add_action( 'save_post', 'my_save_post_handler', 10, 3 );
function my_save_post_handler( $post_id, $post, $update ) {
    // 1. Bỏ qua autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 2. Bỏ qua revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // 3. Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 4. Kiểm tra nonce (bảo mật)
    if ( ! isset( $_POST['my_nonce'] ) || ! wp_verify_nonce( $_POST['my_nonce'], 'my_save_action' ) ) {
        return;
    }

    // 5. Chỉ xử lý post type mong muốn
    if ( 'post' !== $post->post_type ) {
        return;
    }

    // OK - An toàn để xử lý
    update_post_meta( $post_id, '_my_custom_field', sanitize_text_field( $_POST['my_field'] ?? '' ) );
}
```

### 4. Dùng named function thay vì anonymous khi cần remove

```php
<?php
// Nếu code của bạn cần cho phép override/remove → dùng named function
add_action( 'wp_footer', 'mytheme_footer_credits' );
function mytheme_footer_credits() {
    echo '<p>Powered by My Theme</p>';
}
// Child theme có thể: remove_action( 'wp_footer', 'mytheme_footer_credits' );

// Nếu code đơn giản và không cần remove → closure OK
add_action( 'wp_head', function() {
    echo '<meta name="robots" content="noindex">';
});
```

### 5. Sanitize và Escape

```php
<?php
// Luôn sanitize input, escape output
add_filter( 'the_content', function( $content ) {
    $custom_text = get_option( 'my_custom_footer_text', '' );

    // esc_html() để escape HTML entities, tránh XSS
    $content .= '<p>' . esc_html( $custom_text ) . '</p>';

    return $content;
});
```

### 6. Tránh gọi hook quá sớm

```php
<?php
// SAI: Gọi WordPress functions trước khi WordPress sẵn sàng
// echo get_bloginfo('name'); // Có thể lỗi nếu chạy quá sớm

// ĐÚNG: Đặt trong hook phù hợp
add_action( 'init', function() {
    $site_name = get_bloginfo( 'name' );
    // Sử dụng $site_name ở đây
});
```

### Tổng kết

| Hàm | Loại | Mục đích |
|-----|------|----------|
| `add_action()` | Action | Đăng ký callback cho action hook |
| `do_action()` | Action | Kích hoạt action hook |
| `remove_action()` | Action | Gỡ bỏ callback đã đăng ký |
| `has_action()` | Action | Kiểm tra callback có đăng ký không |
| `add_filter()` | Filter | Đăng ký callback cho filter hook |
| `apply_filters()` | Filter | Kích hoạt filter hook, trả về giá trị đã filter |
| `remove_filter()` | Filter | Gỡ bỏ filter callback |
| `has_filter()` | Filter | Kiểm tra filter callback có đăng ký không |

---

> **Tiếp theo:** [02 - Action Hooks Quan Trọng](02-action-hooks-quan-trong.md) - Danh sách chi tiết các Action Hooks hay dùng nhất trong WordPress.
