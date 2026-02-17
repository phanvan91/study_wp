# WordPress Hooks Lifecycle - Vòng Đời Thực Thi

## Mục Lục

1. [Giới thiệu](#1-giới-thiệu)
2. [WordPress Loading Sequence](#2-wordpress-loading-sequence)
3. [Frontend Request Lifecycle](#3-frontend-request-lifecycle)
4. [Admin Request Lifecycle](#4-admin-request-lifecycle)
5. [AJAX Request Lifecycle](#5-ajax-request-lifecycle)
6. [REST API Request Lifecycle](#6-rest-api-request-lifecycle)
7. [Cron Request Lifecycle](#7-cron-request-lifecycle)
8. [Login Page Lifecycle](#8-login-page-lifecycle)
9. [Hook Execution Order Chi Tiết](#9-hook-execution-order-chi-tiết)
10. [Debugging Hooks](#10-debugging-hooks)
11. [Best Practices](#11-best-practices)

---

## 1. Giới thiệu

Hiểu thứ tự thực thi hooks là yếu tố then chốt để viết code WordPress hiệu quả. Nếu bạn đăng ký code vào sai hook, code sẽ không chạy hoặc chạy ở thời điểm không mong muốn.

### So sánh với Laravel

```
Laravel Request Lifecycle:
    index.php → bootstrap → kernel → middleware → routing → controller → response

WordPress Request Lifecycle:
    index.php → wp-config → wp-settings → plugins → theme → init → query → template → output

    Mỗi bước đều có hooks để bạn can thiệp!
```

---

## 2. WordPress Loading Sequence

### Tổng quan quy trình

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     WORDPRESS LOADING SEQUENCE                          │
│                                                                         │
│  ┌─── PHASE 1: Bootstrap ──────────────────────────────────────────┐   │
│  │  index.php → wp-blog-header.php → wp-load.php                   │   │
│  │  → wp-config.php (constants, DB config)                         │   │
│  │  → wp-settings.php (bắt đầu load WordPress)                    │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 2: Core Load ─────────────────────────────────────────┐   │
│  │  Load WordPress core files (functions, classes, etc.)            │   │
│  │  → wp-includes/default-filters.php                              │   │
│  │  → wp-includes/plugin.php (Hook system)                         │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 3: MU-Plugins ───────────────────────────────────────┐    │
│  │  Load must-use plugins                                          │   │
│  │  ══> do_action('muplugins_loaded')                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 4: Plugins ──────────────────────────────────────────┐    │
│  │  Load active plugins (từ wp_options 'active_plugins')           │   │
│  │  ══> do_action('plugins_loaded')                                │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 5: Theme ────────────────────────────────────────────┐    │
│  │  Load active theme (functions.php)                              │   │
│  │  ══> do_action('after_setup_theme')                             │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 6: Init ─────────────────────────────────────────────┐    │
│  │  ══> do_action('init')                                          │   │
│  │  ══> do_action('widgets_init')                                  │   │
│  │  ══> do_action('wp_loaded')                                     │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 7: Query & Template ─────────────────────────────────┐    │
│  │  Parse request → Run main query → Load template                 │   │
│  │  ══> do_action('wp') → do_action('template_redirect')           │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              ↓                                          │
│  ┌─── PHASE 8: Output ───────────────────────────────────────────┐    │
│  │  ══> do_action('wp_head')                                       │   │
│  │  ══> apply_filters('the_content')                               │   │
│  │  ══> do_action('wp_footer')                                     │   │
│  │  ══> do_action('shutdown')                                      │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Frontend Request Lifecycle

### Sơ đồ chi tiết - Khi user truy cập trang

```
USER REQUEST: https://example.com/bai-viet-moi/
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 1: BOOTSTRAP                                                 │
│                                                                      │
│  index.php                                                           │
│    └→ wp-blog-header.php                                            │
│         ├→ wp-load.php                                              │
│         │    └→ wp-config.php (DB_NAME, DB_USER, etc.)             │
│         │         └→ wp-settings.php                                │
│         │                                                            │
│  wp-settings.php bắt đầu load theo thứ tự:                         │
│                                                                      │
│  1. ══> do_action('mu_plugins_loaded')        [mu-plugins done]     │
│  2. ══> do_action('registered_taxonomy')      [cho mỗi taxonomy]    │
│  3. ══> do_action('registered_post_type')     [cho mỗi post type]   │
│  4. Load active plugins...                                           │
│  5. ══> do_action('plugins_loaded')           [tất cả plugins done] │
│  6. Load active theme functions.php...                               │
│  7. ══> do_action('after_setup_theme')        [theme done]          │
│  8. ══> do_action('init')                     [WordPress init xong] │
│  9. ══> do_action('widgets_init')             [widgets registered]   │
│  10.══> do_action('wp_loaded')                [mọi thứ đã load]    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 2: QUERY                                                      │
│                                                                      │
│  wp() function:                                                      │
│  11.══> do_action('parse_request')            [phân tích URL]       │
│  12.══> do_action('send_headers')             [gửi HTTP headers]    │
│  13.══> do_action('parse_query')              [phân tích query vars] │
│  14.══> apply_filters('pre_get_posts')        [sửa query trước]     │
│  15.══> Chạy SQL query lấy posts                                    │
│  16.══> do_action('the_post')                 [cho mỗi post]        │
│  17.══> do_action('wp')                       [query xong]          │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 3: TEMPLATE                                                   │
│                                                                      │
│  18.══> do_action('template_redirect')        [trước load template] │
│  19.══> apply_filters('template_include')     [chọn template file]  │
│  20.  Load template file (single.php, page.php, etc.)               │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 4: OUTPUT                                                     │
│                                                                      │
│  header.php:                                                         │
│  21.══> do_action('wp_enqueue_scripts')       [CSS/JS registration] │
│  22.══> do_action('wp_head')                  [thẻ <head>]          │
│  23.══> do_action('wp_body_open')             [sau <body>]          │
│                                                                      │
│  Template content:                                                   │
│  24.══> apply_filters('the_title')            [tiêu đề]             │
│  25.══> apply_filters('the_content')          [nội dung]            │
│  26.══> apply_filters('the_excerpt')          [đoạn trích]          │
│  27.══> do_action('comment_form')             [form bình luận]      │
│                                                                      │
│  Sidebar:                                                            │
│  28.══> do_action('dynamic_sidebar')          [widgets]             │
│                                                                      │
│  footer.php:                                                         │
│  29.══> do_action('wp_footer')                [trước </body>]       │
│  30.══> do_action('wp_print_footer_scripts')  [inline JS footer]    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 5: SHUTDOWN                                                   │
│                                                                      │
│  31.══> do_action('shutdown')                 [sau khi output xong] │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 4. Admin Request Lifecycle

### Sơ đồ - Khi truy cập /wp-admin/

```
USER REQUEST: https://example.com/wp-admin/edit.php
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 1-6: Giống Frontend (mu_plugins_loaded → wp_loaded)          │
│                                                                      │
│  1-10. Giống Frontend...                                             │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ADMIN-SPECIFIC HOOKS                                                │
│                                                                      │
│  wp-admin/admin.php:                                                 │
│  A1.══> do_action('admin_init')               [admin bắt đầu]      │
│  A2.══> do_action('admin_menu')               [build menu]          │
│  A3.══> do_action('admin_bar_menu')           [admin bar]           │
│                                                                      │
│  Trang cụ thể (edit.php, post.php, etc.):                           │
│  A4.══> do_action('load-{page}')              [vd: load-edit.php]   │
│  A5.══> do_action('admin_notices')            [thông báo]           │
│  A6.══> do_action('admin_enqueue_scripts')    [CSS/JS admin]        │
│  A7.══> do_action('admin_head')               [<head> admin]        │
│                                                                      │
│  Render admin page:                                                  │
│  A8.══> do_action('admin_footer')             [footer admin]        │
│  A9.══> do_action('admin_print_footer_scripts') [footer scripts]    │
│                                                                      │
│  A10.══> do_action('shutdown')                [kết thúc]            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Hook đặc biệt cho từng trang admin

```php
<?php
// Hook load-{page} chạy trước khi render từng trang admin cụ thể

// Chỉ chạy khi mở trang edit.php (danh sách bài viết)
add_action( 'load-edit.php', function() {
    // Code chỉ chạy ở trang danh sách bài viết
    // Dùng cho: thêm screen options, help tabs, bulk actions
});

// Chỉ chạy khi mở trang post.php hoặc post-new.php (editor)
add_action( 'load-post.php', function() {
    // Code chỉ chạy ở trang editor bài viết
});

add_action( 'load-post-new.php', function() {
    // Code chỉ chạy khi tạo bài viết mới
});

// Chỉ chạy ở trang Settings > General
add_action( 'load-options-general.php', function() {
    // Thêm custom section vào Settings > General
});
```

---

## 5. AJAX Request Lifecycle

### Sơ đồ

```
AJAX REQUEST: POST /wp-admin/admin-ajax.php
              data: { action: 'my_save_data', nonce: '...', ... }
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 1-6: Bootstrap → wp_loaded (giống nhau)                      │
│                                                                      │
│  Nhưng KHÔNG load theme template                                     │
│  Chỉ load: core + plugins + theme functions.php                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  AJAX-SPECIFIC                                                       │
│                                                                      │
│  admin-ajax.php:                                                     │
│  1. ══> do_action('admin_init')               [admin init]          │
│                                                                      │
│  2. Xác định action từ $_REQUEST['action']                          │
│                                                                      │
│  3. Nếu user ĐÃ đăng nhập:                                         │
│     ══> do_action('wp_ajax_{action}')                               │
│     Ví dụ: do_action('wp_ajax_my_save_data')                       │
│                                                                      │
│  4. Nếu user CHƯA đăng nhập:                                        │
│     ══> do_action('wp_ajax_nopriv_{action}')                        │
│     Ví dụ: do_action('wp_ajax_nopriv_my_save_data')                │
│                                                                      │
│  5. Callback xử lý → wp_send_json() → wp_die()                     │
│                                                                      │
│  LƯU Ý: Không có wp_head(), wp_footer(), template hooks!           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Code minh họa AJAX lifecycle

```php
<?php
// AJAX handler được gọi SAU admin_init nhưng TRƯỚC bất kỳ output nào
add_action( 'wp_ajax_my_save_settings', 'my_ajax_save_settings' );
function my_ajax_save_settings() {
    // Tại thời điểm này:
    // - WordPress core đã load XONG
    // - Tất cả plugins đã load XONG
    // - Theme functions.php đã load XONG
    // - admin_init đã chạy XONG
    // - NHƯNG: Không có template, không có wp_head/wp_footer

    // 1. Verify nonce
    check_ajax_referer( 'my_settings_nonce', 'security' );

    // 2. Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Không có quyền.', 403 );
    }

    // 3. Xử lý dữ liệu
    $setting_value = sanitize_text_field( $_POST['setting_value'] ?? '' );
    update_option( 'my_setting', $setting_value );

    // 4. Trả về response
    wp_send_json_success( array(
        'message' => 'Đã lưu thành công!',
        'value'   => $setting_value,
    ));
    // wp_send_json_success() tự gọi wp_die()
}
```

---

## 6. REST API Request Lifecycle

### Sơ đồ

```
REST REQUEST: GET /wp-json/wp/v2/posts
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 1-6: Bootstrap → wp_loaded (giống nhau)                      │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  REST API-SPECIFIC                                                   │
│                                                                      │
│  wp-includes/rest-api.php:                                           │
│  R1.══> do_action('rest_api_init')            [đăng ký routes]      │
│                                                                      │
│  WP_REST_Server::dispatch():                                         │
│  R2.══> apply_filters('rest_pre_dispatch')    [trước dispatch]      │
│  R3.══> apply_filters('rest_request_before_callbacks')              │
│                                                                      │
│  Chạy callback (Controller):                                        │
│  R4.══> Permission check (permission_callback)                      │
│  R5.══> Main callback                                                │
│                                                                      │
│  R6.══> apply_filters('rest_request_after_callbacks')               │
│  R7.══> apply_filters('rest_post_dispatch')   [sau dispatch]        │
│                                                                      │
│  Response:                                                           │
│  R8.══> apply_filters('rest_pre_echo_response')                     │
│  R9.  Gửi JSON response                                             │
│                                                                      │
│  LƯU Ý:                                                             │
│  - Có define('REST_REQUEST', true)                                   │
│  - Không có template, wp_head, wp_footer                            │
│  - admin_init KHÔNG chạy                                            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Code minh họa REST lifecycle hooks

```php
<?php
// Chạy khi REST API init
add_action( 'rest_api_init', function() {
    // Đăng ký routes
    register_rest_route( 'myplugin/v1', '/stats', array(
        'methods'             => 'GET',
        'callback'            => 'my_get_stats',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ));

    // Thêm custom field vào REST response của posts
    register_rest_field( 'post', 'view_count', array(
        'get_callback' => function( $post_arr ) {
            return intval( get_post_meta( $post_arr['id'], '_view_count', true ) );
        },
        'update_callback' => function( $value, $post ) {
            update_post_meta( $post->ID, '_view_count', intval( $value ) );
        },
        'schema' => array(
            'type'        => 'integer',
            'description' => 'Số lượt xem bài viết',
        ),
    ));
});

// Filter TRƯỚC khi dispatch (có thể cache, rate limit)
add_filter( 'rest_pre_dispatch', function( $result, $server, $request ) {
    // Rate limiting cho REST API
    $ip    = $_SERVER['REMOTE_ADDR'];
    $key   = 'rest_rate_' . md5( $ip );
    $count = get_transient( $key );

    if ( false === $count ) {
        set_transient( $key, 1, MINUTE_IN_SECONDS );
    } elseif ( $count > 60 ) {
        // Quá 60 requests/phút
        return new WP_Error(
            'rate_limit_exceeded',
            'Quá nhiều requests. Vui lòng đợi 1 phút.',
            array( 'status' => 429 )
        );
    } else {
        set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
    }

    return $result;
}, 10, 3 );

// Filter SAU khi dispatch (modify response)
add_filter( 'rest_post_dispatch', function( $response, $server, $request ) {
    // Thêm custom headers
    $response->header( 'X-Powered-By', 'My Plugin v1.0' );
    $response->header( 'X-Request-Time', microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] );

    return $response;
}, 10, 3 );

function my_get_stats( WP_REST_Request $request ) {
    return new WP_REST_Response( array(
        'total_posts'    => wp_count_posts()->publish,
        'total_pages'    => wp_count_posts( 'page' )->publish,
        'total_comments' => wp_count_comments()->approved,
        'total_users'    => count_users()['total_users'],
    ), 200 );
}
```

---

## 7. Cron Request Lifecycle

### Sơ đồ

```
CRON TRIGGER: Visitor truy cập site → wp-cron.php (spawned request)
              Hoặc: real cron chạy wget /wp-cron.php
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 1-6: Bootstrap → wp_loaded (giống nhau)                      │
│                                                                      │
│  define('DOING_CRON', true) được set                                │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  CRON-SPECIFIC                                                       │
│                                                                      │
│  wp-cron.php:                                                        │
│  C1. Lấy danh sách scheduled events từ wp_options ('cron')          │
│  C2. Kiểm tra events nào đã đến lúc chạy                           │
│  C3. Với mỗi event đến hạn:                                         │
│      ══> do_action( $hook_name, $args )                             │
│      Ví dụ: do_action('my_daily_cleanup_event')                     │
│                                                                      │
│  LƯU Ý:                                                             │
│  - Không có user context (get_current_user_id() = 0)                │
│  - Không có frontend/admin output                                    │
│  - Không có template hooks                                           │
│  - DOING_CRON = true                                                │
│  - admin_init KHÔNG chạy                                            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Kiểm tra context trong cron

```php
<?php
add_action( 'my_cron_task', 'my_cron_handler' );
function my_cron_handler() {
    // Xác nhận đang chạy trong cron
    if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
        return; // Không phải cron → bỏ qua
    }

    // Không có user → không dùng current_user_can()
    // Không có output → dùng error_log() để debug

    error_log( '[Cron] Task bắt đầu: ' . current_time( 'mysql' ) );

    // Xử lý task...

    error_log( '[Cron] Task hoàn thành: ' . current_time( 'mysql' ) );
}
```

---

## 8. Login Page Lifecycle

### Sơ đồ

```
USER REQUEST: https://example.com/wp-login.php
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PHASE 1-6: Bootstrap → wp_loaded (giống nhau)                      │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│  LOGIN-SPECIFIC                                                      │
│                                                                      │
│  wp-login.php:                                                       │
│  L1.══> do_action('login_init')               [login page init]     │
│  L2.══> do_action('login_enqueue_scripts')    [CSS/JS login page]   │
│  L3.══> do_action('login_head')               [<head> login page]   │
│  L4.══> do_action('login_header')             [header login page]   │
│                                                                      │
│  Nếu form submitted:                                                 │
│  L5.══> apply_filters('authenticate')         [xác thực]            │
│  L6.══> do_action('wp_login')                 [đăng nhập OK]        │
│     hoặc                                                             │
│  L6.══> do_action('wp_login_failed')          [đăng nhập thất bại]  │
│                                                                      │
│  Sau đăng nhập thành công:                                           │
│  L7.══> apply_filters('login_redirect')       [redirect]            │
│                                                                      │
│  L8.══> do_action('login_footer')             [footer login page]   │
│                                                                      │
│  LƯU Ý:                                                             │
│  - Không có wp_head()/wp_footer() (dùng login_head/login_footer)    │
│  - Không có admin hooks                                              │
│  - Không có template hooks                                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 9. Hook Execution Order Chi Tiết

### Bảng thứ tự thực thi đầy đủ cho Frontend

```
#  | Hook Name                    | Type    | Khi nào
---|------------------------------|---------|------------------------------------------
1  | muplugins_loaded            | Action  | Mu-plugins loaded
2  | registered_taxonomy         | Action  | Mỗi taxonomy đăng ký
3  | registered_post_type        | Action  | Mỗi post type đăng ký
4  | plugins_loaded              | Action  | Tất cả plugins loaded
5  | sanitize_comment_cookies    | Action  | Sanitize cookies
6  | setup_theme                 | Action  | Trước load theme
7  | load_textdomain             | Action  | Load ngôn ngữ core
8  | after_setup_theme           | Action  | Theme loaded
9  | auth_cookie_valid           | Action  | Cookie hợp lệ
10 | set_current_user            | Action  | Current user set
11 | init                        | Action  | WordPress init xong
12 | widgets_init                | Action  | Widgets registered
13 | wp_loaded                   | Action  | Mọi thứ loaded
14 | parse_request               | Action  | Parse URL
15 | send_headers                | Action  | Gửi HTTP headers
16 | parse_query                 | Action  | Parse query vars
17 | pre_get_posts               | Action  | Trước chạy query
18 | posts_selection             | Action  | Posts selected
19 | wp                          | Action  | Main query xong
20 | template_redirect           | Action  | Trước load template
21 | get_header                  | Action  | Trước load header.php
22 | wp_enqueue_scripts          | Action  | Register CSS/JS
23 | wp_head                     | Action  | Trong <head>
24 | wp_body_open                | Action  | Sau <body>
25 | loop_start                  | Action  | Bắt đầu The Loop
26 | the_post                    | Action  | Mỗi post trong loop
27 | loop_end                    | Action  | Kết thúc The Loop
28 | get_sidebar                 | Action  | Trước load sidebar
29 | dynamic_sidebar             | Action  | Render widgets
30 | get_footer                  | Action  | Trước load footer.php
31 | wp_footer                   | Action  | Trước </body>
32 | wp_print_footer_scripts     | Action  | Footer scripts
33 | shutdown                    | Action  | Kết thúc PHP
```

---

## 10. Debugging Hooks

### 10.1 Log tất cả hooks đang chạy

```php
<?php
/**
 * Plugin Name: Hook Logger
 * Description: Ghi log tất cả hooks để debug
 * CHỈ DÙNG TRONG DEVELOPMENT - XÓA TRƯỚC KHI LÊN PRODUCTION!
 */

// Phương pháp 1: Dùng 'all' hook (log MỌI hook)
add_action( 'all', 'my_log_all_hooks' );
function my_log_all_hooks( $hook_name = '' ) {
    // Bỏ qua các hook lặp lại quá nhiều
    $skip_hooks = array( 'gettext', 'gettext_with_context', 'attribute_escape', 'sanitize_title' );

    if ( in_array( $hook_name, $skip_hooks, true ) ) {
        return;
    }

    // Chỉ log khi có query parameter ?debug_hooks=1
    if ( ! isset( $_GET['debug_hooks'] ) ) {
        return;
    }

    static $count = 0;
    $count++;

    error_log( sprintf(
        '[Hook #%d] %s',
        $count,
        $hook_name
    ));
}
```

### 10.2 Kiểm tra hook có đang chạy không

```php
<?php
// doing_action() - Kiểm tra action có đang thực thi không
// Hữu ích khi hàm có thể được gọi từ nhiều ngữ cảnh

function my_flexible_function() {
    if ( doing_action( 'save_post' ) ) {
        // Được gọi từ save_post hook
        error_log( 'Gọi từ save_post' );
    }

    if ( doing_action( 'wp_ajax_my_action' ) ) {
        // Được gọi từ AJAX handler
        error_log( 'Gọi từ AJAX' );
    }

    if ( doing_action( 'rest_api_init' ) ) {
        // Được gọi từ REST API
        error_log( 'Gọi từ REST API' );
    }
}
```

### 10.3 did_action() - Kiểm tra action đã chạy chưa

```php
<?php
// did_action() trả về số lần action đã được kích hoạt

function my_check_init_status() {
    // Kiểm tra 'init' đã chạy chưa
    if ( did_action( 'init' ) ) {
        // init đã chạy rồi → có thể dùng mọi WordPress functions
        echo 'Init đã chạy ' . did_action( 'init' ) . ' lần';
    } else {
        // init chưa chạy → cẩn thận, một số functions chưa sẵn sàng
        echo 'Init chưa chạy!';
    }
}

// Ứng dụng thực tế: Đảm bảo code chỉ chạy sau init
function my_safe_function() {
    if ( ! did_action( 'init' ) ) {
        // Nếu init chưa chạy, đăng ký để chạy sau
        add_action( 'init', __FUNCTION__ );
        return;
    }

    // OK - init đã chạy, an toàn để dùng WordPress functions
    $post_types = get_post_types();
    // ...
}
```

### 10.4 current_action() và current_filter()

```php
<?php
// current_action() / current_filter() - Lấy tên hook đang chạy

// Dùng khi 1 callback đăng ký với nhiều hooks
add_action( 'wp_head', 'my_multi_hook_callback' );
add_action( 'wp_footer', 'my_multi_hook_callback' );
add_action( 'admin_head', 'my_multi_hook_callback' );

function my_multi_hook_callback() {
    $current = current_action(); // Trả về tên hook đang chạy

    switch ( $current ) {
        case 'wp_head':
            echo '<!-- Code cho wp_head -->';
            break;
        case 'wp_footer':
            echo '<!-- Code cho wp_footer -->';
            break;
        case 'admin_head':
            echo '<!-- Code cho admin_head -->';
            break;
    }
}
```

### 10.5 Debug tool: Liệt kê tất cả callbacks của một hook

```php
<?php
/**
 * Xem tất cả callbacks đã đăng ký cho 1 hook cụ thể
 * Dùng: my_debug_hook('wp_head');
 */
function my_debug_hook( $hook_name ) {
    global $wp_filter;

    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        error_log( "Hook '{$hook_name}' không có callbacks nào." );
        return;
    }

    $hook = $wp_filter[ $hook_name ];

    error_log( "=== Callbacks cho hook: {$hook_name} ===" );

    foreach ( $hook->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $id => $callback_data ) {
            $callback = $callback_data['function'];

            // Xác định loại callback
            if ( is_string( $callback ) ) {
                $name = $callback;
            } elseif ( is_array( $callback ) ) {
                if ( is_object( $callback[0] ) ) {
                    $name = get_class( $callback[0] ) . '->' . $callback[1];
                } else {
                    $name = $callback[0] . '::' . $callback[1];
                }
            } elseif ( $callback instanceof Closure ) {
                $ref = new ReflectionFunction( $callback );
                $name = 'Closure in ' . $ref->getFileName() . ':' . $ref->getStartLine();
            } else {
                $name = 'Unknown';
            }

            error_log( sprintf(
                '  Priority %d: %s (accepted_args: %d)',
                $priority,
                $name,
                $callback_data['accepted_args']
            ));
        }
    }
}

// Sử dụng: Đặt trong init để debug
add_action( 'wp_loaded', function() {
    if ( isset( $_GET['debug_hook'] ) && current_user_can( 'manage_options' ) ) {
        my_debug_hook( sanitize_text_field( $_GET['debug_hook'] ) );
    }
});
// Truy cập: ?debug_hook=wp_head → xem error log
```

### 10.6 Query Monitor plugin hooks panel

```php
<?php
// Query Monitor là plugin debug tốt nhất cho WordPress
// Nó tự động hiển thị:
// - Tất cả hooks đã chạy
// - Thời gian thực thi mỗi callback
// - Callbacks đăng ký cho mỗi hook
// - Hooks đang active

// Cách sử dụng: Install plugin Query Monitor từ WordPress.org
// Hoặc cài qua WP-CLI:
// wp plugin install query-monitor --activate

// Để debug trong code, bạn cũng có thể dùng:
add_action( 'shutdown', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Hiển thị số lần mỗi action quan trọng đã chạy
    $important_actions = array(
        'init', 'wp_loaded', 'wp', 'wp_head', 'wp_footer',
        'the_post', 'save_post', 'shutdown',
    );

    error_log( '=== Hook Execution Summary ===' );
    foreach ( $important_actions as $action ) {
        $count = did_action( $action );
        error_log( sprintf( '  %s: %d lần', $action, $count ) );
    }
});
```

### 10.7 Đo thời gian thực thi hook callback

```php
<?php
/**
 * Wrapper để đo thời gian thực thi callback
 */
function my_timed_action( $hook, $callback, $priority = 10, $args = 1 ) {
    add_action( $hook, function() use ( $hook, $callback ) {
        $start = microtime( true );

        // Gọi callback gốc
        call_user_func_array( $callback, func_get_args() );

        $elapsed = microtime( true ) - $start;
        $elapsed_ms = round( $elapsed * 1000, 2 );

        // Log nếu chậm (> 10ms)
        if ( $elapsed_ms > 10 ) {
            $callback_name = is_string( $callback ) ? $callback : 'closure';
            error_log( sprintf(
                '[Performance] Hook: %s | Callback: %s | Time: %sms',
                $hook,
                $callback_name,
                $elapsed_ms
            ));
        }
    }, $priority, $args );
}

// Sử dụng:
my_timed_action( 'init', 'my_heavy_init_function' );
my_timed_action( 'wp_head', 'my_complex_meta_output', 5 );
```

---

## 11. Best Practices

### 1. Chọn hook phù hợp với mục đích

```php
<?php
// === ĐĂNG KÝ (Post Types, Taxonomies, etc.) ===
// Dùng: init
add_action( 'init', function() {
    register_post_type( 'product', array( /* ... */ ) );
    register_taxonomy( 'brand', 'product', array( /* ... */ ) );
    add_shortcode( 'my_shortcode', 'my_callback' );
});

// === TƯƠNG TÁC GIỮA PLUGINS ===
// Dùng: plugins_loaded
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        // Tương tác với WooCommerce
    }
});

// === THEME SETUP ===
// Dùng: after_setup_theme
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array( /* ... */ ) );
});

// === ADMIN MENU ===
// Dùng: admin_menu (KHÔNG dùng init!)
add_action( 'admin_menu', function() {
    add_menu_page( /* ... */ );
});

// === ENQUEUE ASSETS ===
// Frontend: wp_enqueue_scripts
// Admin:    admin_enqueue_scripts
// Login:    login_enqueue_scripts
```

### 2. Kiểm tra context để tránh side effects

```php
<?php
add_action( 'init', function() {
    // Kiểm tra đang ở đâu
    if ( is_admin() ) {
        // Admin context
    }

    if ( wp_doing_ajax() ) {
        // AJAX context
    }

    if ( wp_doing_cron() ) {
        // Cron context - không có user
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        // REST API context
    }

    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        // WP-CLI context
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        // Autosave context
    }
});
```

### 3. Không thực thi logic nặng ở hook sớm

```php
<?php
// SAI: Query database ở plugins_loaded (quá sớm, chạy mỗi request)
add_action( 'plugins_loaded', function() {
    $results = $wpdb->get_results( "SELECT * FROM big_table" ); // Chậm!
});

// ĐÚNG: Defer logic nặng đến khi thực sự cần
add_action( 'template_redirect', function() {
    // Chỉ chạy ở frontend, khi template sắp load
    if ( is_singular( 'product' ) ) {
        // Chỉ query khi xem trang sản phẩm
        $related = get_posts( array( /* ... */ ) );
    }
});
```

### 4. Hiểu rằng một số hooks chạy nhiều lần

```php
<?php
// the_post chạy cho MỖI bài viết trong loop
add_action( 'the_post', function( $post ) {
    // Chạy 10 lần nếu có 10 bài viết!
    // Tránh logic nặng ở đây
});

// save_post có thể chạy nhiều lần khi lưu 1 bài
add_action( 'save_post', function( $post_id ) {
    // Có thể chạy cho: bài gốc + revision + autosave
    // LUÔN kiểm tra revision và autosave!
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }

    // Tránh infinite loop khi gọi wp_update_post() trong save_post
    remove_action( 'save_post', __FUNCTION__ );
    wp_update_post( array( 'ID' => $post_id, /* ... */ ) );
    add_action( 'save_post', __FUNCTION__ );
});
```

### 5. Sử dụng tools debug

```
Các công cụ debug hooks hữu ích:

1. Query Monitor plugin  - Hiển thị chi tiết hooks, queries, errors
2. Debug Bar plugin      - Toolbar debug trong admin
3. error_log()           - Ghi vào wp-content/debug.log
4. WP_DEBUG constant     - Bật chế độ debug trong wp-config.php

// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );      // Ghi log vào /wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false ); // Không hiển thị errors trên trang
define( 'SCRIPT_DEBUG', true );      // Load unminified core JS/CSS
```

---

> **Tiếp theo:** [05 - Custom Hooks](05-custom-hooks.md) - Tạo hooks riêng cho plugin/theme của bạn.
