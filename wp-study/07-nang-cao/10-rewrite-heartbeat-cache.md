# Rewrite API, Heartbeat API & Object Cache Nâng Cao

## Mục lục

1. [Rewrite API - Custom URL Rules](#1-rewrite-api---custom-url-rules)
2. [add_rewrite_rule() chi tiết](#2-add_rewrite_rule-chi-tiet)
3. [add_rewrite_endpoint()](#3-add_rewrite_endpoint)
4. [Ví dụ thực tế: Custom API không dùng REST](#4-vi-du-thuc-te-custom-api-khong-dung-rest)
5. [Heartbeat API - Real-time Communication](#5-heartbeat-api---real-time-communication)
6. [Heartbeat: Custom Data Exchange](#6-heartbeat-custom-data-exchange)
7. [Heartbeat: Admin Notifications Real-time](#7-heartbeat-admin-notifications-real-time)
8. [Heartbeat: Performance Control](#8-heartbeat-performance-control)
9. [Object Cache Nâng Cao](#9-object-cache-nang-cao)
10. [Cache Patterns thực tế](#10-cache-patterns-thuc-te)
11. [Fragment Caching](#11-fragment-caching)
12. [So sánh với Laravel](#12-so-sanh-voi-laravel)

---

## 1. Rewrite API - Custom URL Rules

### Tổng quan

```
WordPress Rewrite API cho phép:
  - Tạo pretty URLs tùy chỉnh
  - Map URL patterns → query variables
  - Tạo custom endpoints cho post types
  - Tạo virtual pages (không cần tạo page thật)

Quy trình:
  URL request: /products/laptop/reviews/
       ↓
  Rewrite Rules: Regex match → query vars
       ↓
  WordPress: index.php?product_name=laptop&endpoint=reviews
       ↓
  template_redirect: Xử lý và render
```

### Flush Rewrite Rules

```php
<?php
/**
 * QUAN TRỌNG: Chỉ flush rewrite rules khi:
 * 1. Plugin activation
 * 2. Theme activation
 * 3. Thay đổi permalink settings
 *
 * KHÔNG BAO GIỜ flush trong init, admin_init, hoặc mỗi page load!
 * → Gây chậm vì phải viết lại .htaccess mỗi request.
 */

// ✅ ĐÚNG: Trong activation hook
register_activation_hook( __FILE__, function() {
    my_plugin_register_rewrite_rules(); // Đăng ký rules trước
    flush_rewrite_rules();              // Rồi flush
} );

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules(); // Xóa rules khi deactivate
} );

// ❌ SAI: Flush mỗi khi init
add_action( 'init', function() {
    flush_rewrite_rules(); // KHÔNG LÀM ĐIỀU NÀY!
} );
```

---

## 2. add_rewrite_rule() chi tiết

### 2.1. Cú pháp cơ bản

```php
<?php
/**
 * add_rewrite_rule( $regex, $redirect, $after )
 *
 * $regex:    Pattern match URL (PCRE regex)
 * $redirect: Query string redirect (index.php?...)
 * $after:    'top' (ưu tiên cao) hoặc 'bottom' (ưu tiên thấp)
 */

add_action( 'init', function() {

    // Ví dụ 1: /portfolio/web-design/ → index.php?portfolio=web-design
    add_rewrite_rule(
        '^portfolio/([^/]+)/?$',           // Regex
        'index.php?portfolio=$matches[1]', // Redirect
        'top'                              // Priority
    );

    // Ví dụ 2: /events/2024/01/ → index.php?post_type=event&year=2024&month=01
    add_rewrite_rule(
        '^events/([0-9]{4})/([0-9]{2})/?$',
        'index.php?post_type=event&year=$matches[1]&monthnum=$matches[2]',
        'top'
    );

    // Ví dụ 3: /user/john/orders/ → index.php?author_name=john&user_tab=orders
    add_rewrite_rule(
        '^user/([^/]+)/([^/]+)/?$',
        'index.php?author_name=$matches[1]&user_tab=$matches[2]',
        'top'
    );

    // Ví dụ 4: Pagination: /blog/page/2/
    add_rewrite_rule(
        '^blog/page/([0-9]+)/?$',
        'index.php?post_type=post&paged=$matches[1]',
        'top'
    );
} );

// Đăng ký custom query vars (WordPress chỉ chấp nhận vars đã whitelist)
add_filter( 'query_vars', function( array $vars ): array {
    $vars[] = 'portfolio';
    $vars[] = 'user_tab';
    return $vars;
} );
```

### 2.2. add_rewrite_tag()

```php
<?php
/**
 * add_rewrite_tag() đăng ký placeholder trong permalink structure.
 * Dùng cho custom post type permalink.
 */

add_action( 'init', function() {
    // Đăng ký tag %product_category%
    add_rewrite_tag(
        '%product_category%',    // Tag name (phải có %...%)
        '([^/]+)',               // Regex match
        'product_category='      // Query var prefix
    );

    // Đăng ký custom post type với permalink structure chứa tag
    register_post_type( 'product', array(
        'public'      => true,
        'has_archive' => 'products',
        'rewrite'     => array(
            'slug' => 'products/%product_category%', // /products/electronics/iphone-15/
        ),
        'supports'    => array( 'title', 'editor', 'thumbnail' ),
    ) );
} );

// Replace %product_category% trong permalink
add_filter( 'post_type_link', function( string $link, WP_Post $post ): string {
    if ( 'product' !== $post->post_type ) {
        return $link;
    }

    $terms = wp_get_object_terms( $post->ID, 'product_category' );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        $link = str_replace( '%product_category%', $terms[0]->slug, $link );
    } else {
        $link = str_replace( '%product_category%', 'uncategorized', $link );
    }

    return $link;
}, 10, 2 );
```

---

## 3. add_rewrite_endpoint()

```php
<?php
/**
 * add_rewrite_endpoint() thêm endpoint vào cuối URL.
 * Ví dụ: /my-post/amp/ hoặc /my-post/print/ hoặc /my-account/orders/
 *
 * Endpoint places:
 *   EP_PERMALINK  → Sau single post: /post-slug/endpoint/
 *   EP_PAGES      → Sau pages: /page-slug/endpoint/
 *   EP_ATTACHMENT  → Sau attachments
 *   EP_ROOT       → Sau root: /endpoint/
 *   EP_ALL        → Tất cả
 */

add_action( 'init', function() {
    // /any-post/amp/ → hiển thị AMP version
    add_rewrite_endpoint( 'amp', EP_PERMALINK );

    // /any-post/print/ → hiển thị print version
    add_rewrite_endpoint( 'print', EP_PERMALINK | EP_PAGES );

    // /my-account/orders/ → tab orders trong my-account page
    add_rewrite_endpoint( 'orders', EP_PAGES );
    add_rewrite_endpoint( 'downloads', EP_PAGES );
    add_rewrite_endpoint( 'edit-profile', EP_PAGES );
} );

// Xử lý endpoint
add_action( 'template_redirect', function() {
    global $wp_query;

    // Kiểm tra endpoint 'amp' có trong query không
    if ( isset( $wp_query->query_vars['amp'] ) ) {
        // Load AMP template
        get_template_part( 'template-parts/amp' );
        exit;
    }

    // Endpoint 'print'
    if ( isset( $wp_query->query_vars['print'] ) ) {
        get_template_part( 'template-parts/print' );
        exit;
    }
} );

// Lấy giá trị endpoint
add_filter( 'the_content', function( string $content ): string {
    $orders_value = get_query_var( 'orders' );

    if ( $orders_value !== '' ) {
        // URL: /my-account/orders/  → $orders_value = ''
        // URL: /my-account/orders/2/ → $orders_value = '2' (page 2)
        return my_render_orders_tab( $orders_value );
    }

    return $content;
} );
```

---

## 4. Ví dụ thực tế: Custom API không dùng REST

```php
<?php
/**
 * Tạo lightweight API endpoint dùng Rewrite API.
 * Nhanh hơn REST API vì không load REST infrastructure.
 *
 * URL: /api/products/?category=electronics&sort=price
 */

class Lightweight_API {

    public static function register(): void {
        add_action( 'init', array( self::class, 'add_rules' ) );
        add_filter( 'query_vars', array( self::class, 'add_query_vars' ) );
        add_action( 'template_redirect', array( self::class, 'handle_request' ) );
    }

    public static function add_rules(): void {
        add_rewrite_rule(
            '^api/([^/]+)/?$',
            'index.php?my_api_endpoint=$matches[1]',
            'top'
        );
    }

    public static function add_query_vars( array $vars ): array {
        $vars[] = 'my_api_endpoint';
        return $vars;
    }

    public static function handle_request(): void {
        $endpoint = get_query_var( 'my_api_endpoint' );
        if ( empty( $endpoint ) ) {
            return;
        }

        // Set JSON headers
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Cache-Control: public, max-age=300' ); // Cache 5 phút

        $response = match ( $endpoint ) {
            'products' => self::handle_products(),
            'search'   => self::handle_search(),
            'stats'    => self::handle_stats(),
            default    => array( 'error' => 'Unknown endpoint', 'status' => 404 ),
        };

        $status = $response['status'] ?? 200;
        unset( $response['status'] );

        http_response_code( $status );
        echo wp_json_encode( $response );
        exit;
    }

    private static function handle_products(): array {
        $category = sanitize_text_field( $_GET['category'] ?? '' );
        $sort     = sanitize_key( $_GET['sort'] ?? 'date' );
        $page     = max( 1, absint( $_GET['page'] ?? 1 ) );

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'paged'          => $page,
        );

        if ( $category ) {
            $args['tax_query'] = array( array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category,
            ) );
        }

        $args['orderby'] = match ( $sort ) {
            'price' => 'meta_value_num',
            'title' => 'title',
            default => 'date',
        };
        if ( $sort === 'price' ) {
            $args['meta_key'] = '_price';
        }

        $query = new WP_Query( $args );

        return array(
            'products'    => array_map( function( $post ) {
                return array(
                    'id'    => $post->ID,
                    'title' => get_the_title( $post ),
                    'slug'  => $post->post_name,
                    'price' => get_post_meta( $post->ID, '_price', true ),
                    'image' => get_the_post_thumbnail_url( $post, 'medium' ),
                );
            }, $query->posts ),
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $page,
        );
    }

    private static function handle_search(): array {
        $q = sanitize_text_field( $_GET['q'] ?? '' );
        if ( empty( $q ) ) {
            return array( 'error' => 'Missing search query', 'status' => 400 );
        }

        $posts = get_posts( array(
            's'              => $q,
            'post_type'      => array( 'post', 'page', 'product' ),
            'post_status'    => 'publish',
            'posts_per_page' => 10,
        ) );

        return array(
            'query'   => $q,
            'results' => array_map( function( $post ) {
                return array(
                    'title'     => get_the_title( $post ),
                    'url'       => get_permalink( $post ),
                    'type'      => $post->post_type,
                    'excerpt'   => wp_trim_words( $post->post_content, 20 ),
                );
            }, $posts ),
            'count'   => count( $posts ),
        );
    }

    private static function handle_stats(): array {
        // Cần authentication
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            return array( 'error' => 'Unauthorized', 'status' => 403 );
        }

        return array(
            'posts'    => (int) wp_count_posts()->publish,
            'pages'    => (int) wp_count_posts( 'page' )->publish,
            'comments' => (int) wp_count_comments()->approved,
            'users'    => (int) count_users()['total_users'],
        );
    }
}

Lightweight_API::register();
```

---

## 5. Heartbeat API - Real-time Communication

### Tổng quan

```
WordPress Heartbeat API:
  - AJAX polling mỗi 15-120 giây (configurable)
  - Client (JavaScript) → Server (PHP) → Client
  - Dùng cho: auto-save, post locking, login session refresh
  - File: wp-includes/js/heartbeat.js

Quy trình:
  Browser                          Server
    │                                │
    ├── POST /wp-admin/admin-ajax.php ──→ (action: heartbeat)
    │   {data: {...client data...}}  │
    │                                │
    │ ←── Response JSON ─────────────┤
    │   {data: {...server data...}}  │
    │                                │
    │   ... chờ 15-60 giây ...       │
    │                                │
    ├── POST (lặp lại) ─────────────→│
    │                                │
```

### Built-in Heartbeat features

```
1. Post Locking:
   - Khi user A đang edit post → user B thấy "This post is being edited by A"
   - Heartbeat kiểm tra mỗi 15 giây: "user A còn edit không?"

2. Auto-save:
   - Tự động save draft mỗi 60 giây
   - Gửi content qua Heartbeat → server save revision

3. Session expiry:
   - Khi login session sắp hết → hiện popup "Session expiring"
   - Heartbeat kiểm tra session còn valid không

4. Auth check:
   - Khi bị logout (ở tab khác) → hiện popup "Please log in again"
```

---

## 6. Heartbeat: Custom Data Exchange

### 6.1. Gửi data từ Client → Server

```javascript
/**
 * File: assets/js/admin-heartbeat.js
 *
 * Gửi custom data qua Heartbeat.
 */

// Enqueue data TRƯỚC khi Heartbeat tick
jQuery(document).on('heartbeat-send', function(event, data) {
    // data là object sẽ được gửi lên server
    data.my_plugin_check_orders = true;
    data.my_plugin_current_page = window.location.pathname;
    data.my_plugin_last_check = Date.now();
});

// Nhận response từ server SAU khi Heartbeat tick
jQuery(document).on('heartbeat-tick', function(event, data) {
    // data là object server trả về

    if (data.my_plugin_new_orders) {
        const count = data.my_plugin_new_orders;
        // Update badge trên menu
        jQuery('#my-plugin-order-count').text(count);

        if (count > 0) {
            // Hiện notification
            jQuery('#my-plugin-notification')
                .text(count + ' đơn hàng mới!')
                .fadeIn()
                .delay(5000)
                .fadeOut();
        }
    }

    if (data.my_plugin_server_time) {
        jQuery('#server-time').text(data.my_plugin_server_time);
    }
});
```

### 6.2. Xử lý trên Server

```php
<?php
/**
 * Xử lý Heartbeat request trên server.
 *
 * heartbeat_received: Nhận data từ client, trả data về client.
 * Fires trong admin context (logged-in users).
 */

add_filter( 'heartbeat_received', function( array $response, array $data ): array {
    // $data = data từ client (heartbeat-send event)
    // $response = data sẽ trả về client (heartbeat-tick event)

    // Kiểm tra đơn hàng mới
    if ( ! empty( $data['my_plugin_check_orders'] ) ) {
        $new_orders = wc_get_orders( array(
            'status'       => 'processing',
            'date_created' => '>' . ( time() - 300 ), // 5 phút gần nhất
            'return'       => 'ids',
        ) );

        $response['my_plugin_new_orders'] = count( $new_orders );
    }

    // Trả server time
    $response['my_plugin_server_time'] = current_time( 'H:i:s' );

    return $response;
}, 10, 2 );

/**
 * heartbeat_nopriv_received: Cho users CHƯA đăng nhập.
 * Dùng ít, vì Heartbeat chủ yếu chạy trong admin.
 */
add_filter( 'heartbeat_nopriv_received', function( array $response, array $data ): array {
    if ( ! empty( $data['my_plugin_check_stock'] ) ) {
        $product_id = absint( $data['my_plugin_check_stock'] );
        $product    = wc_get_product( $product_id );
        if ( $product ) {
            $response['my_plugin_stock_status'] = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
            $response['my_plugin_stock_qty']    = $product->get_stock_quantity();
        }
    }
    return $response;
}, 10, 2 );
```

### 6.3. Enqueue JavaScript

```php
<?php
add_action( 'admin_enqueue_scripts', function( string $hook_suffix ) {
    // Chỉ load trên trang cần thiết
    if ( 'index.php' !== $hook_suffix && 'edit.php' !== $hook_suffix ) {
        return;
    }

    // wp-heartbeat là dependency (đảm bảo Heartbeat JS loaded)
    wp_enqueue_script(
        'my-plugin-heartbeat',
        plugins_url( 'assets/js/admin-heartbeat.js', __FILE__ ),
        array( 'jquery', 'heartbeat' ),
        '1.0.0',
        true
    );
} );
```

---

## 7. Heartbeat: Admin Notifications Real-time

```php
<?php
/**
 * Ví dụ hoàn chỉnh: Hệ thống notification real-time trong admin.
 */

class Admin_Notifications {

    public static function register(): void {
        add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
        add_filter( 'heartbeat_received', array( self::class, 'check_notifications' ), 10, 2 );
        add_action( 'admin_footer', array( self::class, 'render_container' ) );
    }

    public static function enqueue_scripts(): void {
        wp_enqueue_script(
            'admin-notifications',
            plugins_url( 'assets/js/notifications.js', __FILE__ ),
            array( 'jquery', 'heartbeat' ),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'admin-notifications',
            plugins_url( 'assets/css/notifications.css', __FILE__ ),
            array(),
            '1.0.0'
        );
    }

    public static function check_notifications( array $response, array $data ): array {
        if ( empty( $data['my_notifications_last_check'] ) ) {
            return $response;
        }

        $user_id    = get_current_user_id();
        $last_check = absint( $data['my_notifications_last_check'] );

        // Lấy notifications mới hơn last_check
        global $wpdb;
        $notifications = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}my_notifications
             WHERE user_id = %d AND created_at > %s AND is_read = 0
             ORDER BY created_at DESC LIMIT 10",
            $user_id,
            date( 'Y-m-d H:i:s', $last_check )
        ) );

        $unread_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}my_notifications
             WHERE user_id = %d AND is_read = 0",
            $user_id
        ) );

        $response['my_notifications'] = array(
            'items'        => array_map( function( $n ) {
                return array(
                    'id'      => $n->id,
                    'type'    => $n->type,
                    'message' => $n->message,
                    'url'     => $n->url,
                    'time'    => human_time_diff( strtotime( $n->created_at ) ) . ' trước',
                );
            }, $notifications ),
            'unread_count' => (int) $unread_count,
        );

        return $response;
    }

    public static function render_container(): void {
        ?>
        <div id="my-notification-panel" style="display:none;">
            <div class="notification-badge"><span id="notification-count">0</span></div>
            <div class="notification-dropdown">
                <div id="notification-list"></div>
            </div>
        </div>
        <?php
    }

    /**
     * API: Tạo notification (gọi từ bất kỳ đâu trong code).
     */
    public static function create( int $user_id, string $type, string $message, string $url = '' ): void {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'my_notifications',
            array(
                'user_id'    => $user_id,
                'type'       => $type,
                'message'    => $message,
                'url'        => $url,
                'is_read'    => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%s' )
        );
    }
}

Admin_Notifications::register();

// Sử dụng
Admin_Notifications::create( 1, 'order', 'Đơn hàng #456 mới!', admin_url( 'post.php?post=456&action=edit' ) );
```

---

## 8. Heartbeat: Performance Control

```php
<?php
/**
 * Heartbeat có thể gây load server cao nếu nhiều users online.
 * Mỗi user = 1 AJAX request mỗi 15-60 giây.
 * 100 users online = 100-400 requests/phút.
 */

// 1. Thay đổi tần suất Heartbeat
add_filter( 'heartbeat_settings', function( array $settings ): array {
    // Default: 60 giây trong admin, 120 giây ở frontend
    // Min: 15 giây, Max: 120 giây

    $settings['interval'] = 60; // Mỗi 60 giây (default)

    // Tăng lên 120 giây để giảm load
    // $settings['interval'] = 120;

    return $settings;
} );

// 2. Tắt Heartbeat ở frontend (chỉ cần trong admin)
add_action( 'init', function() {
    if ( ! is_admin() ) {
        wp_deregister_script( 'heartbeat' );
    }
} );

// 3. Tắt Heartbeat trên các trang admin không cần
add_action( 'admin_enqueue_scripts', function( string $hook ) {
    // Chỉ cần Heartbeat trên post editor (cho auto-save, post locking)
    $allowed_pages = array( 'post.php', 'post-new.php' );

    if ( ! in_array( $hook, $allowed_pages, true ) ) {
        wp_deregister_script( 'heartbeat' );
    }
} );

// 4. Giới hạn tần suất theo trang
add_filter( 'heartbeat_settings', function( array $settings ): array {
    global $pagenow;

    if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
        $settings['interval'] = 15; // Post editor: 15 giây (auto-save nhanh)
    } else {
        $settings['interval'] = 120; // Các trang khác: 2 phút
    }

    return $settings;
} );

// 5. Tắt Heartbeat cho non-admin users
add_action( 'init', function() {
    if ( is_admin() && ! current_user_can( 'edit_posts' ) ) {
        wp_deregister_script( 'heartbeat' );
    }
} );
```

---

## 9. Object Cache Nâng Cao

### 9.1. wp_cache_* Functions

```php
<?php
/**
 * WordPress Object Cache API.
 *
 * Mặc định: Non-persistent (chỉ tồn tại trong 1 request).
 * Với Redis/Memcached drop-in: Persistent (tồn tại across requests).
 *
 * Khác với Transients:
 *   - Transients: Lưu trong wp_options (DB), có expiration
 *   - Object Cache: Lưu trong memory (Redis/Memcached), nhanh hơn nhiều
 */

// ── CƠ BẢN ─────────────────────────────────────────────────────

// Set: Lưu vào cache (ghi đè nếu đã có)
wp_cache_set(
    'my_key',              // Key
    $data,                 // Value (any type: string, array, object)
    'my-plugin',           // Group (namespace tránh conflict)
    3600                   // Expiration (giây), 0 = không hết hạn
);

// Get: Lấy từ cache
$data = wp_cache_get( 'my_key', 'my-plugin' );
// Trả về false nếu không tìm thấy (CẢNH BÁO: false cũng là valid value)

// Get với found flag (phân biệt "không có" vs "giá trị là false")
$found = false;
$data  = wp_cache_get( 'my_key', 'my-plugin', false, $found );
if ( ! $found ) {
    // Key không tồn tại trong cache
}

// Delete: Xóa 1 key
wp_cache_delete( 'my_key', 'my-plugin' );

// Add: Chỉ set nếu CHƯA CÓ (atomic operation, tránh race condition)
$added = wp_cache_add( 'my_key', $data, 'my-plugin', 3600 );
// Trả về false nếu key đã tồn tại

// Replace: Chỉ set nếu ĐÃ CÓ
wp_cache_replace( 'my_key', $new_data, 'my-plugin', 3600 );

// Increment/Decrement (atomic)
wp_cache_incr( 'counter', 1, 'my-plugin' );  // +1
wp_cache_decr( 'counter', 1, 'my-plugin' );  // -1

// Flush toàn bộ cache
wp_cache_flush();

// WordPress 6.1+: Flush chỉ 1 group
wp_cache_flush_group( 'my-plugin' );
```

### 9.2. Cache Groups

```php
<?php
/**
 * Cache Groups: Namespace cho cache keys.
 * Mỗi plugin nên dùng group riêng.
 *
 * Groups đặc biệt của WordPress:
 *   - 'options':     get_option() cache
 *   - 'posts':       get_post() cache
 *   - 'post_meta':   get_post_meta() cache
 *   - 'terms':       get_term() cache
 *   - 'users':       get_userdata() cache
 *   - 'user_meta':   get_user_meta() cache
 *   - 'transient':   get_transient() cache
 *   - 'site-transient': get_site_transient() cache
 */

class My_Cache {

    private const GROUP = 'my-plugin';
    private const TTL   = 3600; // 1 giờ

    /**
     * Get or Set pattern (cache-aside).
     * Kiểm tra cache → nếu có return → nếu không, compute, cache, return.
     */
    public static function remember( string $key, callable $callback, int $ttl = self::TTL ) {
        $found = false;
        $data  = wp_cache_get( $key, self::GROUP, false, $found );

        if ( $found ) {
            return $data;
        }

        $data = $callback();
        wp_cache_set( $key, $data, self::GROUP, $ttl );

        return $data;
    }

    /**
     * Xóa cache cho 1 key.
     */
    public static function forget( string $key ): void {
        wp_cache_delete( $key, self::GROUP );
    }

    /**
     * Xóa toàn bộ cache của plugin.
     */
    public static function flush(): void {
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( self::GROUP );
        }
        // Fallback: không flush được group → increment version
        // (technique: dùng version number trong key)
    }
}

// Sử dụng
$popular_posts = My_Cache::remember( 'popular_posts_widget', function() {
    return get_posts( array(
        'meta_key'       => 'post_views',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'posts_per_page' => 10,
    ) );
}, 1800 ); // Cache 30 phút

// Invalidate khi post updated
add_action( 'save_post', function() {
    My_Cache::forget( 'popular_posts_widget' );
} );
```

---

## 10. Cache Patterns thực tế

### 10.1. Cache Stampede Prevention (Lock Pattern)

```php
<?php
/**
 * Cache Stampede: Khi cache hết hạn, 100 requests đồng thời
 * cùng query database → overload.
 *
 * Giải pháp: Chỉ 1 request rebuild cache, còn lại dùng stale data.
 */

class Cache_With_Lock {

    /**
     * Get data với lock protection.
     */
    public static function get_with_lock(
        string $key,
        string $group,
        callable $callback,
        int $ttl = 3600,
        int $lock_ttl = 30
    ) {
        // Thử lấy từ cache
        $found = false;
        $data  = wp_cache_get( $key, $group, false, $found );
        if ( $found ) {
            return $data;
        }

        // Cache miss → thử acquire lock
        $lock_key = $key . '_lock';
        $locked   = wp_cache_add( $lock_key, true, $group, $lock_ttl );

        if ( ! $locked ) {
            // Không lấy được lock → request khác đang rebuild
            // Trả về stale data nếu có
            $stale = wp_cache_get( $key . '_stale', $group );
            if ( false !== $stale ) {
                return $stale;
            }

            // Không có stale → phải chờ (hoặc compute)
            usleep( 100000 ); // 100ms
            return wp_cache_get( $key, $group );
        }

        try {
            // Có lock → rebuild cache
            $data = $callback();

            // Lưu fresh data
            wp_cache_set( $key, $data, $group, $ttl );

            // Lưu stale copy (TTL dài hơn) cho stampede protection
            wp_cache_set( $key . '_stale', $data, $group, $ttl * 2 );

            return $data;
        } finally {
            // Luôn release lock
            wp_cache_delete( $lock_key, $group );
        }
    }
}

// Sử dụng
$stats = Cache_With_Lock::get_with_lock(
    'site_stats',
    'my-plugin',
    function() {
        global $wpdb;
        // Query nặng...
        return $wpdb->get_row(
            "SELECT
                (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish') as posts,
                (SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1') as comments,
                (SELECT COUNT(*) FROM {$wpdb->users}) as users"
        );
    },
    300  // 5 phút
);
```

### 10.2. Cache-aside cho External API

```php
<?php
/**
 * Cache response từ API bên ngoài.
 * Tránh gọi API mỗi request.
 */

function my_get_exchange_rates(): array {
    $cache_key = 'exchange_rates';

    // 1. Kiểm tra object cache
    $rates = wp_cache_get( $cache_key, 'my-plugin' );
    if ( false !== $rates ) {
        return $rates;
    }

    // 2. Kiểm tra transient (persistent cache fallback)
    $rates = get_transient( 'my_plugin_exchange_rates' );
    if ( false !== $rates ) {
        // Set vào object cache cho requests tiếp theo (nhanh hơn transient)
        wp_cache_set( $cache_key, $rates, 'my-plugin', 3600 );
        return $rates;
    }

    // 3. Gọi API
    $response = wp_remote_get( 'https://api.exchangerate-api.com/v4/latest/USD', array(
        'timeout' => 10,
    ) );

    if ( is_wp_error( $response ) ) {
        // API fail → trả về stale data hoặc default
        $stale = get_option( 'my_plugin_rates_backup', array() );
        return $stale;
    }

    $rates = json_decode( wp_remote_retrieve_body( $response ), true )['rates'] ?? array();

    // 4. Cache ở nhiều layers
    wp_cache_set( $cache_key, $rates, 'my-plugin', 3600 );       // Object cache: 1 giờ
    set_transient( 'my_plugin_exchange_rates', $rates, 3600 );    // Transient: 1 giờ
    update_option( 'my_plugin_rates_backup', $rates );            // DB: permanent backup

    return $rates;
}
```

---

## 11. Fragment Caching

```php
<?php
/**
 * Fragment Caching: Cache từng phần HTML output.
 * Hữu ích cho: sidebar widgets, navigation menus, footer...
 */

class Fragment_Cache {

    /**
     * Cache output của callback.
     *
     * @param string   $key      Cache key.
     * @param callable $callback Function tạo HTML (echo output).
     * @param int      $ttl      TTL in seconds.
     * @return void Echoes cached or fresh HTML.
     */
    public static function render( string $key, callable $callback, int $ttl = 3600 ): void {
        $html = wp_cache_get( $key, 'fragments' );

        if ( false !== $html ) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        // Capture output
        ob_start();
        $callback();
        $html = ob_get_clean();

        // Cache HTML
        wp_cache_set( $key, $html, 'fragments', $ttl );

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Invalidate fragment.
     */
    public static function invalidate( string $key ): void {
        wp_cache_delete( $key, 'fragments' );
    }
}

// ── SỬ DỤNG TRONG TEMPLATE ─────────────────────────────────────

// sidebar.php
Fragment_Cache::render( 'sidebar_popular_posts', function() {
    $posts = get_posts( array(
        'meta_key'       => 'views',
        'orderby'        => 'meta_value_num',
        'posts_per_page' => 5,
    ) );
    ?>
    <div class="popular-posts">
        <h3>Bài Viết Phổ Biến</h3>
        <ul>
            <?php foreach ( $posts as $post ) : ?>
                <li>
                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
                        <?php echo esc_html( get_the_title( $post ) ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}, 1800 ); // Cache 30 phút

// footer.php
Fragment_Cache::render( 'footer_content', function() {
    ?>
    <footer>
        <div class="footer-widgets">
            <?php dynamic_sidebar( 'footer-1' ); ?>
        </div>
        <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
    </footer>
    <?php
}, 86400 ); // Cache 24 giờ

// Invalidate khi có thay đổi
add_action( 'save_post', function() {
    Fragment_Cache::invalidate( 'sidebar_popular_posts' );
} );

add_action( 'update_option_sidebars_widgets', function() {
    Fragment_Cache::invalidate( 'footer_content' );
} );
```

---

## 12. So sánh với Laravel

### Rewrite API vs Laravel Routing

| Tính năng | WordPress Rewrite | Laravel Routing |
|-----------|------------------|-----------------|
| **Định nghĩa** | `add_rewrite_rule()` regex | `Route::get('/url', fn)` |
| **URL params** | `add_rewrite_tag('%tag%')` | `{param}` |
| **Middleware** | Không có (dùng hooks) | `Route::middleware('auth')` |
| **Named routes** | Không có | `Route::name('user.profile')` |
| **Groups** | Không có | `Route::group()`, `Route::prefix()` |
| **Cache** | flush_rewrite_rules() | `php artisan route:cache` |
| **REST** | `register_rest_route()` | `Route::apiResource()` |
| **Regex** | PCRE trong rule | `where('id', '[0-9]+')` |

### Heartbeat vs Laravel Broadcasting

| Tính năng | WordPress Heartbeat | Laravel Broadcasting |
|-----------|-------------------|---------------------|
| **Protocol** | HTTP polling (AJAX) | WebSocket (Pusher/Soketi) |
| **Latency** | 15-120 giây | Real-time (< 1 giây) |
| **Direction** | Client poll server | Server push to client |
| **Scalability** | Kém (1 AJAX/user/tick) | Tốt (1 WS connection/user) |
| **Setup** | Built-in | Cần Pusher/Soketi + Echo |
| **Use case** | Admin notifications | Chat, live updates, notifications |

### Object Cache vs Laravel Cache

| Tính năng | WordPress Object Cache | Laravel Cache |
|-----------|----------------------|---------------|
| **API** | `wp_cache_get/set/delete()` | `Cache::get/put/forget()` |
| **Groups** | `wp_cache_set($key, $val, 'group')` | `Cache::tags(['group'])` |
| **Remember** | Custom implement | `Cache::remember($key, $ttl, fn)` |
| **Flush group** | `wp_cache_flush_group()` (6.1+) | `Cache::tags('group')->flush()` |
| **Drivers** | Drop-in: object-cache.php | Config: redis, memcached, file, database |
| **Lock** | `wp_cache_add()` (atomic) | `Cache::lock('key')->get(fn)` |
| **Increment** | `wp_cache_incr()` | `Cache::increment()` |
| **Default** | Non-persistent (in-memory) | File-based |

---

## Tổng kết

| Chủ đề | Hàm/API quan trọng |
|--------|-------------------|
| **Rewrite rules** | `add_rewrite_rule()`, `add_rewrite_tag()`, `add_rewrite_endpoint()` |
| **Query vars** | `query_vars` filter, `get_query_var()` |
| **Flush** | `flush_rewrite_rules()` (chỉ trong activation hook!) |
| **Heartbeat send** | JS: `heartbeat-send` event |
| **Heartbeat receive** | PHP: `heartbeat_received` filter |
| **Heartbeat control** | `heartbeat_settings` filter, `wp_deregister_script('heartbeat')` |
| **Cache get/set** | `wp_cache_get()`, `wp_cache_set()`, `wp_cache_add()` |
| **Cache groups** | Group parameter trong tất cả wp_cache_* functions |
| **Cache flush** | `wp_cache_flush()`, `wp_cache_flush_group()` (6.1+) |
| **Fragment cache** | `ob_start()` + `wp_cache_set()` pattern |
| **Stampede prevent** | `wp_cache_add()` lock + stale data fallback |

---

[← Quay lại: Headless WordPress](./09-headless-wordpress.md) | [Quay lại mục lục →](./index.md)
