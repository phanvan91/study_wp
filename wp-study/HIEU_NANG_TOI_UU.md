# Tối Ưu Hiệu Năng WordPress - Hướng Dẫn Chi Tiết

## Mục lục

1. [Tổng quan hiệu năng WordPress](#1-tong-quan-hieu-nang-wordpress)
2. [Object Cache](#2-object-cache)
3. [Page Cache](#3-page-cache)
4. [Database Optimization](#4-database-optimization)
5. [Image Optimization](#5-image-optimization)
6. [Minify CSS/JS](#6-minify-cssjs)
7. [CDN - Content Delivery Network](#7-cdn---content-delivery-network)
8. [PHP Optimization](#8-php-optimization)
9. [Profiling - Đo lường hiệu năng](#9-profiling---do-luong-hieu-nang)
10. [Caching trong Plugin/Theme Development](#10-caching-trong-plugintheme-development)
11. [Async/Defer Scripts](#11-asyncdefer-scripts)
12. [Database Query Best Practices](#12-database-query-best-practices)
13. [Checklist tối ưu hiệu năng](#13-checklist-toi-uu-hieu-nang)

---

## 1. Tổng quan hiệu năng WordPress

### Tại sao hiệu năng quan trọng?

```
- Trải nghiệm người dùng: Trang tải chậm -> người dùng rời đi
- SEO: Google dùng Core Web Vitals làm yếu tố xếp hạng
- Tỷ lệ chuyển đổi: Mỗi giây chậm hơn -> giảm 7% conversion
- Chi phí server: Tối ưu tốt -> ít tài nguyên hơn -> tiết kiệm chi phí
```

### Các chỉ số hiệu năng cần theo dõi

```
Core Web Vitals (Google):
  - LCP (Largest Contentful Paint): < 2.5 giây
  - FID (First Input Delay): < 100ms
  - CLS (Cumulative Layout Shift): < 0.1
  - INP (Interaction to Next Paint): < 200ms (thay thế FID)

Chỉ số khác:
  - TTFB (Time to First Byte): < 200ms
  - FCP (First Contentful Paint): < 1.8 giây
  - Total page size: < 3MB
  - HTTP requests: < 50
  - DOM size: < 1500 nodes
```

### Quy trình xử lý request của WordPress

```
1. Request đến server
2. Web server (Nginx/Apache) nhận request
3. PHP xử lý:
   a. Load WordPress core
   b. Load plugins (active)
   c. Load theme
   d. Xử lý routing
   e. Query database (MySQL)
   f. Render HTML
4. Response trả về cho browser
5. Browser tải CSS, JS, images
6. Browser render trang

Mỗi bước trên đều có thể tối ưu được!
```

### Tổng quan các lớp cache

```
Browser Cache (client-side)
  |
CDN Cache (edge servers)
  |
Page Cache (full HTML)
  |
Object Cache (query results, computed data)
  |
OPcache (compiled PHP)
  |
Database Cache (MySQL query cache)
```

---

## 2. Object Cache

### Object Cache là gì?

Object Cache lưu kết quả của các phép tính, query database vào bộ nhớ tạm (RAM) để không phải tính lại mỗi request. WordPress có sẵn hệ thống object cache nhưng mặc định chỉ lưu trong 1 request (non-persistent).

### WP_Object_Cache (Mặc định)

```php
/**
 * WordPress Object Cache có sẵn - chỉ hoạt động trong 1 request
 * Hữu ích khi cùng 1 data được gọi nhiều lần trong 1 page load
 */

// Lưu vào cache
wp_cache_set( 'my_key', $data, 'my_group', 3600 );

// Lấy từ cache
$data = wp_cache_get( 'my_key', 'my_group' );

// Xóa cache
wp_cache_delete( 'my_key', 'my_group' );

// Thêm mới (chỉ thêm nếu chưa tồn tại)
wp_cache_add( 'my_key', $data, 'my_group', 3600 );

// Thay thế (chỉ thay thế nếu đã tồn tại)
wp_cache_replace( 'my_key', $new_data, 'my_group', 3600 );

// Xóa toàn bộ cache
wp_cache_flush();

// Tăng giá trị
wp_cache_incr( 'counter', 1, 'my_group' );

// Giảm giá trị
wp_cache_decr( 'counter', 1, 'my_group' );
```

### Ví dụ sử dụng WP_Object_Cache

```php
/**
 * Cache kết quả query nặng
 */
function get_popular_products( $limit = 10 ) {
    $cache_key   = 'popular_products_' . $limit;
    $cache_group = 'my_products';

    // Thử lấy từ cache
    $products = wp_cache_get( $cache_key, $cache_group );

    if ( false === $products ) {
        // Cache miss - query database
        $products = new WP_Query( array(
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'meta_key'       => '_product_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ) );

        // Lưu vào cache
        wp_cache_set( $cache_key, $products, $cache_group, 3600 );
    }

    return $products;
}
```

### Redis - Persistent Object Cache

Redis lưu cache trong RAM, tồn tại giữa các request (persistent). Đây là giải pháp hiệu quả nhất cho object cache.

```bash
# Cài đặt Redis trên Ubuntu
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Kiểm tra Redis
redis-cli ping
# PONG

# Cài đặt Redis Object Cache plugin
wp plugin install redis-cache --activate

# Hoặc dùng Predis (PHP library)
composer require predis/predis
```

```php
// wp-config.php - Cấu hình Redis
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_PASSWORD', '' );           // Nếu có mật khẩu
define( 'WP_REDIS_DATABASE', 0 );             // Redis database index (0-15)
define( 'WP_REDIS_TIMEOUT', 1 );              // Timeout kết nối (giây)
define( 'WP_REDIS_READ_TIMEOUT', 1 );
define( 'WP_REDIS_MAXTTL', 86400 );           // TTL tối đa (giây)

// Prefix để phân biệt nhiều site dùng chung Redis
define( 'WP_REDIS_PREFIX', 'mysite_' );

// Các group không cache
define( 'WP_REDIS_IGNORED_GROUPS', array(
    'counts',
    'plugins',
    'themes',
) );

// Các group không dùng persistent cache
define( 'WP_REDIS_UNFLUSHABLE_GROUPS', array(
    'user_meta',
) );
```

```bash
# Kích hoạt Redis Object Cache
wp redis enable

# Kiểm tra trạng thái
wp redis status

# Flush Redis cache
wp redis flush
```

### Memcached - Persistent Object Cache

```bash
# Cài đặt Memcached
sudo apt-get install memcached php-memcached
sudo systemctl enable memcached

# Cài đặt object-cache.php
# Tải file object-cache.php từ plugin và đặt vào wp-content/
```

```php
// wp-config.php - Cấu hình Memcached
define( 'WP_CACHE', true );

// Nếu dùng nhiều Memcached servers
$memcached_servers = array(
    'default' => array(
        '127.0.0.1:11211',
    ),
);
```

### Transients API

Transients tương tự object cache nhưng lưu trong database (bảng wp_options). Khi có persistent object cache (Redis/Memcached), transients tự động dùng object cache thay vì database.

```php
/**
 * Transients API - Cache có thời hạn
 */

// Lưu transient
set_transient( 'my_data', $value, HOUR_IN_SECONDS );

// Lấy transient
$value = get_transient( 'my_data' );
// Trả về false nếu hết hạn hoặc không tồn tại

// Xóa transient
delete_transient( 'my_data' );

// Các hằng số thời gian có sẵn:
// MINUTE_IN_SECONDS  = 60
// HOUR_IN_SECONDS    = 3600
// DAY_IN_SECONDS     = 86400
// WEEK_IN_SECONDS    = 604800
// MONTH_IN_SECONDS   = 2592000
// YEAR_IN_SECONDS    = 31536000

// Site transients (cho multisite)
set_site_transient( 'my_data', $value, DAY_IN_SECONDS );
get_site_transient( 'my_data' );
delete_site_transient( 'my_data' );
```

### Ví dụ thực tế với Transients

```php
/**
 * Cache danh sách sản phẩm nổi bật với Transients
 */
function get_featured_products() {
    // Thử lấy từ transient
    $products = get_transient( 'featured_products' );

    if ( false === $products ) {
        // Transient hết hạn hoặc chưa có - query mới
        $query = new WP_Query( array(
            'post_type'      => 'product',
            'posts_per_page' => 8,
            'meta_query'     => array(
                array(
                    'key'   => '_is_featured',
                    'value' => '1',
                ),
            ),
        ) );

        $products = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $products[] = array(
                    'id'        => get_the_ID(),
                    'title'     => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url( get_the_ID(), 'medium' ),
                    'price'     => get_post_meta( get_the_ID(), '_product_price', true ),
                );
            }
            wp_reset_postdata();
        }

        // Lưu transient - hết hạn sau 1 giờ
        set_transient( 'featured_products', $products, HOUR_IN_SECONDS );
    }

    return $products;
}

/**
 * Xóa transient khi sản phẩm thay đổi
 * Để transient được tạo lại với dữ liệu mới
 */
function clear_featured_products_cache( $post_id ) {
    if ( get_post_type( $post_id ) === 'product' ) {
        delete_transient( 'featured_products' );
    }
}
add_action( 'save_post', 'clear_featured_products_cache' );
add_action( 'delete_post', 'clear_featured_products_cache' );
add_action( 'trash_post', 'clear_featured_products_cache' );

/**
 * Cache API response từ bên ngoài
 */
function get_exchange_rates() {
    $rates = get_transient( 'exchange_rates' );

    if ( false === $rates ) {
        $response = wp_remote_get( 'https://api.exchangerate.host/latest?base=USD' );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body  = wp_remote_retrieve_body( $response );
        $rates = json_decode( $body, true );

        // API rate limit: cache 6 giờ
        set_transient( 'exchange_rates', $rates, 6 * HOUR_IN_SECONDS );
    }

    return $rates;
}

/**
 * Cache menu navigation
 */
function get_cached_nav_menu( $location ) {
    $cache_key = 'nav_menu_' . $location;
    $menu_html = get_transient( $cache_key );

    if ( false === $menu_html ) {
        $menu_html = wp_nav_menu( array(
            'theme_location' => $location,
            'echo'           => false,
        ) );

        set_transient( $cache_key, $menu_html, 12 * HOUR_IN_SECONDS );
    }

    return $menu_html;
}

// Xóa cache menu khi menu thay đổi
function clear_menu_cache() {
    $locations = get_nav_menu_locations();
    foreach ( $locations as $location => $menu_id ) {
        delete_transient( 'nav_menu_' . $location );
    }
}
add_action( 'wp_update_nav_menu', 'clear_menu_cache' );
```

### So sánh Object Cache, Transients, và Options

```
+-------------------+------------------+-------------------+------------------+
| Tính năng         | Object Cache     | Transients        | Options          |
+-------------------+------------------+-------------------+------------------+
| Lưu trữ           | RAM (với Redis)  | Database/RAM      | Database         |
| Hết hạn           | Có               | Có                | Không            |
| Persistent        | Có (với plugin)  | Có                | Có               |
| Tốc độ            | Rất nhanh        | Nhanh (với Redis) | Chậm hơn         |
| Sử dụng           | Cache tạm thời   | Cache có thời hạn | Cài đặt vĩnh viễn|
| Autoload          | Không            | Không             | Có thể           |
+-------------------+------------------+-------------------+------------------+

Khi có Redis/Memcached:
  - Transients tự động lưu vào object cache (không dùng database)
  - Object Cache là lựa chọn tốt nhất cho dữ liệu tạm thời
  - Options chỉ dùng cho cấu hình vĩnh viễn
```

---

## 3. Page Cache

### Page Cache là gì?

Page Cache lưu toàn bộ HTML của trang đã được render. Khi có request tiếp theo đến cùng trang, server trả về HTML đã lưu thay vì chạy lại toàn bộ PHP và query database.

### Cách hoạt động

```
Không có Page Cache:
  Request -> PHP -> WordPress -> Database -> Render HTML -> Response
  Thời gian: 200-2000ms

Có Page Cache:
  Request -> Tìm HTML đã cache -> Response
  Thời gian: 5-50ms
```

### WP Super Cache

```php
// wp-config.php
define( 'WP_CACHE', true );
define( 'WPCACHEHOME', '/var/www/html/wp-content/plugins/wp-super-cache/' );
```

```bash
# Cài đặt
wp plugin install wp-super-cache --activate

# Cấu hình cơ bản:
# Settings > WP Super Cache
# - Caching: ON
# - Cache Delivery Method: Expert (mod_rewrite)
```

### W3 Total Cache

```bash
# Cài đặt
wp plugin install w3-total-cache --activate

# Các tính năng:
# - Page Cache
# - Minify (CSS, JS, HTML)
# - Database Cache
# - Object Cache
# - Browser Cache
# - CDN
```

### Nginx FastCGI Cache (Hiệu quả nhất)

FastCGI Cache lưu cache ở lớp web server, không cần PHP xử lý gì cả.

```nginx
# /etc/nginx/conf.d/fastcgi-cache.conf

# Định nghĩa vùng cache
fastcgi_cache_path /var/run/nginx-cache levels=1:2
    keys_zone=WORDPRESS:100m
    inactive=60m
    max_size=512m;

# Định nghĩa cache key
fastcgi_cache_key "$scheme$request_method$host$request_uri";

# Điều kiện không cache
fastcgi_cache_use_stale error timeout invalid_header http_500;
fastcgi_ignore_headers Cache-Control Expires Set-Cookie;
```

```nginx
# /etc/nginx/sites-available/wordpress.conf

server {
    listen 80;
    server_name example.com;
    root /var/www/html;

    # Biến kiểm soát cache
    set $skip_cache 0;

    # Không cache POST requests
    if ($request_method = POST) {
        set $skip_cache 1;
    }

    # Không cache URLs có query string
    if ($query_string != "") {
        set $skip_cache 1;
    }

    # Không cache các trang admin, login, ...
    if ($request_uri ~* "/wp-admin/|/wp-login.php|/xmlrpc.php|sitemap(_index)?.xml") {
        set $skip_cache 1;
    }

    # Không cache khi đã đăng nhập hoặc có cookie giỏ hàng
    if ($http_cookie ~* "comment_author|wordpress_[a-f0-9]+|wp-postpass|wordpress_no_cache|wordpress_logged_in|woocommerce_items_in_cart") {
        set $skip_cache 1;
    }

    # Không cache trang WooCommerce
    if ($request_uri ~* "/cart/|/checkout/|/my-account/") {
        set $skip_cache 1;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        # FastCGI Cache
        fastcgi_cache WORDPRESS;
        fastcgi_cache_valid 200 301 302 60m;   # Cache 60 phút
        fastcgi_cache_valid 404 1m;            # Cache 404 trong 1 phút
        fastcgi_cache_bypass $skip_cache;
        fastcgi_no_cache $skip_cache;

        # Header để debug
        add_header X-FastCGI-Cache $upstream_cache_status;
        # HIT = lấy từ cache
        # MISS = không có trong cache, đã tạo mới
        # BYPASS = bị bỏ qua (do skip_cache)
    }
}
```

```bash
# Xóa FastCGI Cache
sudo rm -rf /var/run/nginx-cache/*
sudo systemctl reload nginx
```

```php
/**
 * Xóa Nginx FastCGI Cache khi cập nhật bài viết
 */
function purge_nginx_cache( $post_id ) {
    $permalink = get_permalink( $post_id );

    if ( ! $permalink ) {
        return;
    }

    // Xóa cache của URL cụ thể
    $cache_path = '/var/run/nginx-cache/';

    // Cách 1: Xóa toàn bộ cache
    if ( is_dir( $cache_path ) ) {
        array_map( 'unlink', glob( $cache_path . '*/*/*' ) );
    }

    // Cách 2: Dùng Nginx helper plugin
    // wp plugin install nginx-helper --activate
}
add_action( 'save_post', 'purge_nginx_cache' );
add_action( 'comment_post', 'purge_nginx_cache' );
add_action( 'switch_theme', function() {
    // Xóa toàn bộ cache khi đổi theme
    array_map( 'unlink', glob( '/var/run/nginx-cache/*/*/*' ) );
} );
```

### Lưu ý khi sử dụng Page Cache

```
1. Không cache cho người dùng đã đăng nhập
2. Không cache trang giỏ hàng, thanh toán (WooCommerce)
3. Không cache khi có query string (tìm kiếm, filter)
4. Cần có cơ chế xóa cache khi nội dung thay đổi
5. Chú ý với dynamic content (giỏ hàng, user-specific data)
   -> Dùng AJAX để tải dynamic content riêng
6. Test kỹ trang cache và trang không cache
```

---

## 4. Database Optimization

### Tối ưu query WordPress

```php
/**
 * Tránh N+1 query problem
 */

// KHÔNG TỐT: N+1 queries
$posts = get_posts( array( 'posts_per_page' => 20 ) );
foreach ( $posts as $post ) {
    $author = get_userdata( $post->post_author );    // 1 query cho mỗi post
    $meta   = get_post_meta( $post->ID );            // 1 query cho mỗi post
    $terms  = get_the_terms( $post->ID, 'category' ); // 1 query cho mỗi post
    // Tổng: 1 + 20*3 = 61 queries!
}

// TỐT HƠN: Sử dụng update_post_caches() hoặc WP_Query với cache
$query = new WP_Query( array(
    'posts_per_page'         => 20,
    'update_post_meta_cache' => true,   // Mặc định true - cache meta
    'update_post_term_cache' => true,   // Mặc định true - cache terms
) );
// WordPress tự động batch query meta và terms
// Tổng: 3 queries (posts + meta + terms)

// KHÔNG TỐT: Query tất cả fields khi chỉ cần ID
$posts = new WP_Query( array(
    'posts_per_page' => 100,
    // Lấy tất cả data của 100 posts
) );

// TỐT HƠN: Chỉ lấy những gì cần
$post_ids = new WP_Query( array(
    'posts_per_page' => 100,
    'fields'         => 'ids',           // Chỉ lấy ID
    'no_found_rows'  => true,            // Không đếm tổng số (nhanh hơn)
    'update_post_meta_cache' => false,   // Không cần meta
    'update_post_term_cache' => false,   // Không cần terms
) );
```

### Tối ưu Database

```sql
-- Kiểm tra kích thước các bảng
SELECT
    table_name AS 'Table',
    ROUND(data_length / 1024 / 1024, 2) AS 'Data (MB)',
    ROUND(index_length / 1024 / 1024, 2) AS 'Index (MB)',
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Total (MB)',
    table_rows AS 'Rows'
FROM information_schema.tables
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC;

-- Xóa revisions cũ
DELETE FROM wp_posts WHERE post_type = 'revision';

-- Xóa auto-drafts
DELETE FROM wp_posts WHERE post_status = 'auto-draft';

-- Xóa orphaned postmeta
DELETE pm FROM wp_postmeta pm
LEFT JOIN wp_posts p ON p.ID = pm.post_id
WHERE p.ID IS NULL;

-- Xóa orphaned commentmeta
DELETE cm FROM wp_commentmeta cm
LEFT JOIN wp_comments c ON c.comment_ID = cm.comment_id
WHERE c.comment_ID IS NULL;

-- Xóa orphaned term relationships
DELETE tr FROM wp_term_relationships tr
LEFT JOIN wp_posts p ON p.ID = tr.object_id
WHERE p.ID IS NULL;

-- Xóa expired transients
DELETE FROM wp_options
WHERE option_name LIKE '_transient_timeout_%'
AND option_value < UNIX_TIMESTAMP();

DELETE FROM wp_options
WHERE option_name LIKE '_transient_%'
AND option_name NOT LIKE '_transient_timeout_%'
AND option_name IN (
    SELECT REPLACE(option_name, '_transient_timeout_', '_transient_')
    FROM (SELECT option_name FROM wp_options
          WHERE option_name LIKE '_transient_timeout_%'
          AND option_value < UNIX_TIMESTAMP()) AS expired
);

-- Xóa spam comments
DELETE FROM wp_comments WHERE comment_approved = 'spam';

-- Xóa comments trong thùng rác
DELETE FROM wp_comments WHERE comment_approved = 'trash';

-- Optimize tables
OPTIMIZE TABLE wp_posts, wp_postmeta, wp_options, wp_comments, wp_commentmeta, wp_terms, wp_term_taxonomy, wp_term_relationships;
```

### Thêm Index cho custom meta

```php
/**
 * Thêm database index cho meta_key thường query
 */
function mytheme_add_custom_indexes() {
    global $wpdb;

    // Kiểm tra index đã tồn tại chưa
    $indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_product_price'" );

    if ( empty( $indexes ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX idx_product_price (meta_key(20), meta_value(20))" );
    }
}
register_activation_hook( __FILE__, 'mytheme_add_custom_indexes' );
```

### Giới hạn revisions

```php
// wp-config.php

// Giới hạn số revisions
define( 'WP_POST_REVISIONS', 5 );

// Tắt revisions hoàn toàn (không khuyến khích)
// define( 'WP_POST_REVISIONS', false );

// Tăng khoảng cách autosave (mặc định 60 giây)
define( 'AUTOSAVE_INTERVAL', 120 ); // 2 phút
```

### Tối ưu autoloaded options

```php
/**
 * Kiểm tra và tối ưu autoloaded options
 * Autoloaded options được load mỗi request, nên cần giữ nhỏ
 */

// Kiểm tra tổng kích thước autoloaded options
function check_autoload_size() {
    global $wpdb;

    $size = $wpdb->get_var(
        "SELECT SUM(LENGTH(option_value))
         FROM {$wpdb->options}
         WHERE autoload = 'yes'"
    );

    // Khuyến nghị: dưới 1MB
    return size_format( $size );
}

// Tìm options autoloaded lớn nhất
function find_large_autoloaded_options() {
    global $wpdb;

    return $wpdb->get_results(
        "SELECT option_name, LENGTH(option_value) as size
         FROM {$wpdb->options}
         WHERE autoload = 'yes'
         ORDER BY size DESC
         LIMIT 20"
    );
}

// Tắt autoload cho options không cần thiết
function fix_autoload_options() {
    global $wpdb;

    $options_to_fix = array(
        'widget_%',
        'cron',
        'rewrite_rules',
        // Thêm các option không cần autoload
    );

    foreach ( $options_to_fix as $option_pattern ) {
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->options}
             SET autoload = 'no'
             WHERE option_name LIKE %s AND autoload = 'yes'",
            $option_pattern
        ) );
    }
}
```

---

## 5. Image Optimization

### Compression (Nén hình ảnh)

```php
/**
 * Thay đổi chất lượng JPEG khi upload
 * Mặc định WordPress nén 82%
 */
function mytheme_jpeg_quality( $quality ) {
    return 80; // 80% là cân bằng tốt giữa chất lượng và dung lượng
}
add_filter( 'jpeg_quality', 'mytheme_jpeg_quality' );
add_filter( 'wp_editor_set_quality', 'mytheme_jpeg_quality' );

/**
 * Đăng ký các kích thước hình ảnh cần thiết
 * Chỉ tạo các size thực sự cần dùng
 */
function mytheme_image_sizes() {
    // Xóa các size mặc định không cần
    remove_image_size( '1536x1536' );
    remove_image_size( '2048x2048' );

    // Thêm các size cần thiết
    add_image_size( 'product-thumbnail', 300, 300, true );  // Crop chính xác
    add_image_size( 'product-medium', 600, 400, true );
    add_image_size( 'hero-banner', 1920, 600, true );

    // Cập nhật các size mặc định
    update_option( 'thumbnail_size_w', 150 );
    update_option( 'thumbnail_size_h', 150 );
    update_option( 'medium_size_w', 600 );
    update_option( 'medium_size_h', 600 );
    update_option( 'large_size_w', 1200 );
    update_option( 'large_size_h', 1200 );
}
add_action( 'after_setup_theme', 'mytheme_image_sizes' );

/**
 * Tắt việc tạo các size không cần thiết
 */
function mytheme_disable_unwanted_sizes( $sizes ) {
    unset( $sizes['medium_large'] ); // 768px
    return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'mytheme_disable_unwanted_sizes' );
```

### Lazy Loading

```php
/**
 * WordPress 5.5+ tự động thêm loading="lazy" cho images
 * Tùy chỉnh thêm:
 */

// Tắt lazy loading cho hình đầu tiên (LCP image)
function mytheme_disable_lazy_first_image( $value, $image, $context ) {
    static $count = 0;
    $count++;

    // Không lazy load 2 hình đầu tiên (thường là hero image)
    if ( $count <= 2 ) {
        return false; // Không thêm loading="lazy"
    }

    return $value;
}
add_filter( 'wp_img_tag_add_loading_attr', 'mytheme_disable_lazy_first_image', 10, 3 );

// Thêm fetchpriority="high" cho LCP image
function mytheme_add_fetchpriority( $attr, $attachment, $size ) {
    static $count = 0;
    $count++;

    if ( $count === 1 ) {
        $attr['fetchpriority'] = 'high';
        unset( $attr['loading'] ); // Bỏ lazy loading
    }

    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'mytheme_add_fetchpriority', 10, 3 );
```

### WebP Support

```php
/**
 * WordPress 5.8+ hỗ trợ upload WebP
 * Để chuyển đổi tự động, cần plugin hoặc custom code
 */

// Cho phép upload WebP
function mytheme_allow_webp( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter( 'mime_types', 'mytheme_allow_webp' );

/**
 * Sử dụng thẻ <picture> để hỗ trợ WebP với fallback
 */
function mytheme_picture_tag( $image_url, $alt = '', $class = '' ) {
    // Kiểm tra có file WebP không
    $webp_url = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $image_url );
    $webp_path = str_replace(
        wp_get_upload_dir()['baseurl'],
        wp_get_upload_dir()['basedir'],
        $webp_url
    );

    $html = '<picture>';

    if ( file_exists( $webp_path ) ) {
        $html .= '<source srcset="' . esc_url( $webp_url ) . '" type="image/webp">';
    }

    $html .= '<img src="' . esc_url( $image_url ) . '"'
           . ' alt="' . esc_attr( $alt ) . '"'
           . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' )
           . ' loading="lazy">';

    $html .= '</picture>';

    return $html;
}
```

### Responsive Images

```php
/**
 * WordPress tự động tạo srcset và sizes cho img
 * Tùy chỉnh sizes attribute:
 */
function mytheme_custom_image_sizes_attr( $sizes, $size, $image_src, $image_meta, $attachment_id ) {
    // Tùy chỉnh sizes cho layout cụ thể
    $sizes = '(max-width: 576px) 100vw, (max-width: 992px) 50vw, 33vw';
    return $sizes;
}
add_filter( 'wp_calculate_image_sizes', 'mytheme_custom_image_sizes_attr', 10, 5 );

// Sử dụng trong template
// wp_get_attachment_image() tự động tạo srcset
echo wp_get_attachment_image( $image_id, 'large', false, array(
    'sizes' => '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 800px',
) );
```

### Plugin tối ưu hình ảnh khuyên dùng

```
1. ShortPixel Image Optimizer
   - Nén JPEG, PNG, WebP
   - Tự động chuyển đổi WebP
   - Có API cho developer

2. Imagify
   - 3 mức nén: Normal, Aggressive, Ultra
   - Tự động tối ưu khi upload

3. EWWW Image Optimizer
   - Xử lý trên server (không cần API key)
   - Hỗ trợ WebP
   - Lazy loading
```

---

## 6. Minify CSS/JS

### Loại bỏ CSS/JS không cần thiết

```php
/**
 * Bỏ các script/style không cần trên trang cụ thể
 */
function mytheme_dequeue_unnecessary_assets() {
    // Bỏ Contact Form 7 trên các trang không có form
    if ( ! is_page( 'lien-he' ) && ! is_page( 'contact' ) ) {
        wp_dequeue_style( 'contact-form-7' );
        wp_dequeue_script( 'contact-form-7' );
    }

    // Bỏ WooCommerce CSS/JS trên trang không phải shop
    if ( function_exists( 'is_woocommerce' ) ) {
        if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
            wp_dequeue_style( 'woocommerce-general' );
            wp_dequeue_style( 'woocommerce-layout' );
            wp_dequeue_style( 'woocommerce-smallscreen' );
            wp_dequeue_script( 'wc-cart-fragments' );
            wp_dequeue_script( 'woocommerce' );
            wp_dequeue_script( 'wc-add-to-cart' );
        }
    }

    // Bỏ block library CSS nếu không dùng Gutenberg
    // wp_dequeue_style( 'wp-block-library' );
    // wp_dequeue_style( 'wp-block-library-theme' );

    // Bỏ jQuery migrate (phần lớn theme mới không cần)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.min.js' ), array(), null, true );
    }

    // Bỏ emoji scripts
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'wp_enqueue_scripts', 'mytheme_dequeue_unnecessary_assets', 100 );

/**
 * Bỏ các meta tags không cần thiết trong head
 */
function mytheme_clean_head() {
    remove_action( 'wp_head', 'wp_generator' );                  // WordPress version
    remove_action( 'wp_head', 'wlwmanifest_link' );              // Windows Live Writer
    remove_action( 'wp_head', 'rsd_link' );                      // Really Simple Discovery
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );          // Shortlink
    remove_action( 'wp_head', 'rest_output_link_wp_head' );      // REST API link
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' ); // oEmbed discovery
    remove_action( 'wp_head', 'wp_resource_hints', 2 );          // DNS prefetch
    remove_action( 'wp_head', 'feed_links', 2 );                 // Feed links
    remove_action( 'wp_head', 'feed_links_extra', 3 );           // Extra feed links
}
add_action( 'init', 'mytheme_clean_head' );
```

### Inline Critical CSS

```php
/**
 * Inline CSS quan trọng (above-the-fold) để render nhanh hơn
 */
function mytheme_inline_critical_css() {
    $critical_css = file_get_contents( get_template_directory() . '/assets/css/critical.css' );
    if ( $critical_css ) {
        echo '<style id="critical-css">' . $critical_css . '</style>';
    }
}
add_action( 'wp_head', 'mytheme_inline_critical_css', 1 );

/**
 * Defer tải CSS không quan trọng
 */
function mytheme_defer_non_critical_css( $html, $handle, $href, $media ) {
    // Các file CSS cần defer
    $defer_handles = array( 'mytheme-style', 'mytheme-components' );

    if ( in_array( $handle, $defer_handles ) ) {
        // Dùng preload + onload trick
        $html = '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">'
              . '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';
    }

    return $html;
}
add_filter( 'style_loader_tag', 'mytheme_defer_non_critical_css', 10, 4 );
```

### Plugin Minify khuyên dùng

```
1. Autoptimize
   - Minify HTML, CSS, JS
   - Combine files
   - Inline critical CSS
   - Defer non-critical CSS/JS

2. WP Rocket (trả phí)
   - Page cache + Minify + Lazy load
   - Preload cache
   - Database optimization
   - CDN integration

3. Asset CleanUp (Asset Manager)
   - Quản lý tài nguyên theo từng trang
   - Bỏ scripts/styles không cần
   - Phân tích tài nguyên trên mỗi trang
```

---

## 7. CDN - Content Delivery Network

### CDN là gì?

CDN phân phối nội dung tĩnh (images, CSS, JS) từ các server gần người dùng nhất, giảm thời gian tải trang.

```
Không có CDN:
  User (Việt Nam) --> Server (Mỹ) = 200ms latency

Có CDN:
  User (Việt Nam) --> CDN Edge (Singapore) = 30ms latency
```

### Cấu hình CDN trong WordPress

```php
// wp-config.php hoặc functions.php

// Cách 1: Thay đổi URL của wp-content
define( 'WP_CONTENT_URL', 'https://cdn.example.com/wp-content' );

// Cách 2: Dùng filter
function mytheme_cdn_url( $url ) {
    $site_url = get_site_url();
    $cdn_url  = 'https://cdn.example.com';

    // Chỉ thay cho static files
    if ( preg_match( '/\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|eot)(\?.*)?$/i', $url ) ) {
        $url = str_replace( $site_url, $cdn_url, $url );
    }

    return $url;
}
add_filter( 'wp_get_attachment_url', 'mytheme_cdn_url' );
add_filter( 'style_loader_src', 'mytheme_cdn_url' );
add_filter( 'script_loader_src', 'mytheme_cdn_url' );

// Cách 3: Dùng plugin (khuyên dùng)
// - WP Rocket có tính năng CDN tích hợp
// - CDN Enabler (free)
// - W3 Total Cache có cấu hình CDN
```

### Các dịch vụ CDN phổ biến

```
Miễn phí:
  - Cloudflare (Free plan): DNS + CDN + SSL + WAF
  - BunnyCDN (giá rẻ): 0.01$/GB

Trả phí:
  - AWS CloudFront
  - Google Cloud CDN
  - KeyCDN
  - StackPath
```

### Cấu hình Cloudflare

```
1. Đăng ký Cloudflare (free)
2. Thêm domain
3. Đổi nameserver sang Cloudflare
4. Cấu hình trong Cloudflare Dashboard:
   - SSL: Full (Strict)
   - Caching Level: Standard
   - Browser Cache TTL: 1 month
   - Always Use HTTPS: On
   - Auto Minify: HTML, CSS, JS
   - Brotli Compression: On

5. Cài đặt plugin "Cloudflare" trong WordPress
   - Cấu hình API key
   - Tự động purge cache khi cập nhật nội dung
```

### Preload và Prefetch

```php
/**
 * Thêm resource hints cho hiệu năng tốt hơn
 */
function mytheme_resource_hints( $hints, $relation_type ) {
    // DNS Prefetch - Giải quyết DNS trước
    if ( 'dns-prefetch' === $relation_type ) {
        $hints[] = '//cdn.example.com';
        $hints[] = '//fonts.googleapis.com';
        $hints[] = '//fonts.gstatic.com';
        $hints[] = '//www.google-analytics.com';
    }

    // Preconnect - Kết nối trước (DNS + TCP + TLS)
    if ( 'preconnect' === $relation_type ) {
        $hints[] = array(
            'href'        => 'https://cdn.example.com',
            'crossorigin' => 'anonymous',
        );
        $hints[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => '',
        );
    }

    return $hints;
}
add_filter( 'wp_resource_hints', 'mytheme_resource_hints', 10, 2 );

/**
 * Preload fonts và LCP image
 */
function mytheme_preload_assets() {
    // Preload font
    echo '<link rel="preload" href="' . get_template_directory_uri()
       . '/assets/fonts/inter.woff2" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";

    // Preload LCP image (hình hero)
    if ( is_front_page() ) {
        echo '<link rel="preload" href="' . get_template_directory_uri()
           . '/assets/images/hero.webp" as="image" type="image/webp">' . "\n";
    }
}
add_action( 'wp_head', 'mytheme_preload_assets', 1 );
```

---

## 8. PHP Optimization

### OPcache

OPcache lưu trữ bytecode của PHP đã compile, không cần compile lại mỗi request.

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
; hoặc php.ini

; Bật OPcache
opcache.enable=1
opcache.enable_cli=0

; Bộ nhớ: 256MB cho site lớn
opcache.memory_consumption=256

; Số lượng file cache tối đa
opcache.max_accelerated_files=20000

; Kiểm tra file thay đổi: 0 = không kiểm tra (production)
; 2 = kiểm tra (development)
opcache.validate_timestamps=0
; Nếu = 0, cần restart PHP-FPM khi thay đổi code
; Nếu = 1, đặt opcache.revalidate_freq

; Tần suất kiểm tra (giây) - chỉ có tác dụng khi validate_timestamps=1
opcache.revalidate_freq=60

; Interned strings buffer
opcache.interned_strings_buffer=16

; Ghi log
opcache.log_verbosity_level=1

; Preloading (PHP 7.4+)
; opcache.preload=/var/www/html/preload.php
; opcache.preload_user=www-data

; JIT Compiler (PHP 8.0+)
opcache.jit=1255
opcache.jit_buffer_size=128M
```

```bash
# Kiểm tra OPcache đã bật chưa
php -i | grep opcache

# Restart PHP-FPM sau khi thay đổi
sudo systemctl restart php8.2-fpm
```

### PHP-FPM Tuning

```ini
; /etc/php/8.2/fpm/pool.d/www.conf

; Process Manager
pm = dynamic
; static = số lượng process cố định
; dynamic = tự động scale
; ondemand = tạo process khi cần

; Tối đa processes
pm.max_children = 30
; Tính toán: RAM khả dụng / RAM mỗi process
; Ví dụ: 4GB RAM / 50MB mỗi process = 80 max (nhưng nên đặt thấp hơn)

pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
; Restart process sau 500 requests để tránh memory leak

; Timeout
request_terminate_timeout = 60s

; Memory limit
php_admin_value[memory_limit] = 256M
```

### PHP Version

```
Lựa chọn PHP version:
  - PHP 8.0: Nhanh hơn 7.4 khoảng 10-15%
  - PHP 8.1: Nhanh hơn 8.0, hỗ trợ Fibers, Enums
  - PHP 8.2: Nhanh hơn 8.1, Readonly classes
  - PHP 8.3: Nhanh hơn 8.2, Typed class constants

Lời khuyên:
  - Luôn dùng phiên bản PHP mới nhất mà WordPress và plugins hỗ trợ
  - Kiểm tra tương thích trước khi upgrade
  - PHP 8.2+ là khuyến nghị cho WordPress 6.4+
```

### Memory Limit

```php
// wp-config.php

// Tăng memory limit
define( 'WP_MEMORY_LIMIT', '256M' );

// Memory limit cho admin
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
```

---

## 9. Profiling - Đo lường hiệu năng

### Query Monitor (Plugin miễn phí)

```bash
# Cài đặt
wp plugin install query-monitor --activate

# Query Monitor hiển thị:
# - Tất cả database queries (thời gian, caller, duplicate)
# - PHP errors và warnings
# - HTTP API requests
# - Hooks và actions
# - Conditional checks
# - Enqueued scripts và styles
# - Template hierarchy
# - Environment info
```

### Debug Bar

```php
// wp-config.php - Bật debug mode
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );      // Ghi log vào wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false );  // Không hiển thị lỗi trên frontend
define( 'SCRIPT_DEBUG', true );       // Dùng file JS/CSS chưa minify
define( 'SAVEQUERIES', true );        // Lưu tất cả queries (chỉ dùng khi debug)
```

### Đo lường thời gian trong code

```php
/**
 * Đo lường thời gian thực thi
 */
function mytheme_measure_performance() {
    // Cách 1: Dùng timer_start/timer_stop (có sẵn trong WordPress)
    timer_start();
    // ... code cần đo ...
    $time = timer_stop( 0, 5 ); // 0 = không echo, 5 = số thập phân
    error_log( "Thời gian: {$time} giây" );

    // Cách 2: Dùng microtime
    $start = microtime( true );
    // ... code cần đo ...
    $end = microtime( true );
    $duration = round( ( $end - $start ) * 1000, 2 ); // Miliseconds
    error_log( "Thời gian: {$duration}ms" );

    // Cách 3: Đo memory
    $memory_before = memory_get_usage();
    // ... code cần đo ...
    $memory_after = memory_get_usage();
    $memory_used = $memory_after - $memory_before;
    error_log( "Memory sử dụng: " . size_format( $memory_used ) );

    // Cách 4: Đo số queries
    global $wpdb;
    $queries_before = $wpdb->num_queries;
    // ... code cần đo ...
    $queries_after = $wpdb->num_queries;
    error_log( "Số queries: " . ( $queries_after - $queries_before ) );
}

/**
 * Hiển thị thông tin debug trong footer (chỉ cho admin)
 */
function mytheme_debug_footer() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    ?>
    <!-- Performance Debug -->
    <!--
    Page generated in <?php timer_stop( 1, 5 ); ?> seconds
    <?php echo $wpdb->num_queries; ?> database queries
    Memory peak: <?php echo size_format( memory_get_peak_usage() ); ?>
    -->
    <?php
}
add_action( 'wp_footer', 'mytheme_debug_footer' );
```

### Xdebug Profiling

```ini
; php.ini - Cấu hình Xdebug profiling
[xdebug]
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug
xdebug.profiler_output_name=cachegrind.out.%p.%H.%R

; Chỉ profile khi có trigger (không profile mỗi request)
xdebug.start_with_request=trigger
; Thêm XDEBUG_TRIGGER=1 vào URL hoặc cookie để bật profiler
```

```bash
# Xem kết quả profiling
# Cài đặt KCachegrind (Linux) hoặc QCachegrind (Mac)
sudo apt-get install kcachegrind

# Mở file profiling
kcachegrind /tmp/xdebug/cachegrind.out.*
```

### New Relic

```ini
; php.ini - Cấu hình New Relic
[newrelic]
extension = newrelic.so
newrelic.appname = "My WordPress Site"
newrelic.license = "YOUR_LICENSE_KEY"
newrelic.framework = "wordpress"
```

### Công cụ đo lường bên ngoài

```
1. Google PageSpeed Insights
   URL: https://pagespeed.web.dev/
   - Đo LCP, FID, CLS, INP
   - Đề xuất cải thiện cụ thể

2. GTmetrix
   URL: https://gtmetrix.com/
   - Phân tích chi tiết waterfall
   - So sánh hiệu năng theo thời gian

3. WebPageTest
   URL: https://www.webpagetest.org/
   - Test từ nhiều vị trí
   - Filmstrip view
   - Waterfall chi tiết

4. Chrome DevTools
   - Network tab: Xem waterfall
   - Performance tab: Flame chart
   - Lighthouse: Audit tổng hợp
   - Coverage tab: Tìm CSS/JS không sử dụng
```

---

## 10. Caching trong Plugin/Theme Development

### Sử dụng set_transient()

```php
/**
 * Pattern cache với transient
 */
function mytheme_get_cached_data( $key, $callback, $expiration = HOUR_IN_SECONDS ) {
    $data = get_transient( $key );

    if ( false === $data ) {
        $data = call_user_func( $callback );

        if ( $data !== false && $data !== null ) {
            set_transient( $key, $data, $expiration );
        }
    }

    return $data;
}

// Sử dụng
$products = mytheme_get_cached_data( 'featured_products', function() {
    $query = new WP_Query( array(
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'meta_key'       => '_featured',
        'meta_value'     => '1',
    ) );
    return $query->posts;
}, 2 * HOUR_IN_SECONDS );
```

### Sử dụng wp_cache_set() với Object Cache

```php
/**
 * Cache với Object Cache API
 */
function get_product_stats() {
    $cache_key   = 'product_stats';
    $cache_group = 'mytheme';

    $stats = wp_cache_get( $cache_key, $cache_group );

    if ( false === $stats ) {
        global $wpdb;

        $stats = array(
            'total'        => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'" ),
            'in_stock'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_key = '_product_status' AND pm.meta_value = 'in_stock'" ),
            'avg_price'    => $wpdb->get_var( "SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2))) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_key = '_product_price'" ),
            'generated_at' => current_time( 'mysql' ),
        );

        wp_cache_set( $cache_key, $stats, $cache_group, 30 * MINUTE_IN_SECONDS );
    }

    return $stats;
}
```

### Cache Invalidation (Xóa cache đúng lúc)

```php
/**
 * Hệ thống xóa cache thông minh
 */
class MythemeCache {

    /**
     * Đăng ký các hook để xóa cache khi dữ liệu thay đổi
     */
    public static function init() {
        // Khi bài viết thay đổi
        add_action( 'save_post', array( __CLASS__, 'on_post_change' ), 10, 2 );
        add_action( 'delete_post', array( __CLASS__, 'on_post_change' ) );
        add_action( 'trash_post', array( __CLASS__, 'on_post_change' ) );

        // Khi term (taxonomy) thay đổi
        add_action( 'created_term', array( __CLASS__, 'on_term_change' ), 10, 3 );
        add_action( 'edited_term', array( __CLASS__, 'on_term_change' ), 10, 3 );
        add_action( 'delete_term', array( __CLASS__, 'on_term_change' ), 10, 3 );

        // Khi option thay đổi
        add_action( 'updated_option', array( __CLASS__, 'on_option_change' ), 10, 3 );

        // Khi menu thay đổi
        add_action( 'wp_update_nav_menu', array( __CLASS__, 'on_menu_change' ) );
    }

    public static function on_post_change( $post_id, $post = null ) {
        if ( ! $post ) {
            $post = get_post( $post_id );
        }

        if ( ! $post || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        $post_type = $post->post_type;

        // Xóa cache liên quan đến post type này
        delete_transient( 'latest_' . $post_type );
        delete_transient( 'featured_' . $post_type );
        delete_transient( $post_type . '_count' );

        wp_cache_delete( $post_type . '_stats', 'mytheme' );

        // Xóa cache của các taxonomy terms
        $taxonomies = get_object_taxonomies( $post_type );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_the_terms( $post_id, $taxonomy );
            if ( $terms && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    delete_transient( $taxonomy . '_' . $term->slug . '_posts' );
                }
            }
        }

        // Hook cho plugin/theme khác muốn xóa cache
        do_action( 'mytheme_cache_post_changed', $post_id, $post_type );
    }

    public static function on_term_change( $term_id, $tt_id, $taxonomy ) {
        wp_cache_delete( $taxonomy . '_terms', 'mytheme' );
        delete_transient( $taxonomy . '_tree' );

        do_action( 'mytheme_cache_term_changed', $term_id, $taxonomy );
    }

    public static function on_option_change( $option_name, $old_value, $new_value ) {
        // Xóa cache khi cài đặt thay đổi
        if ( strpos( $option_name, 'mytheme_' ) === 0 ) {
            wp_cache_delete( 'mytheme_settings', 'mytheme' );
        }
    }

    public static function on_menu_change( $menu_id ) {
        $locations = get_nav_menu_locations();
        foreach ( $locations as $location => $id ) {
            if ( $id === $menu_id ) {
                delete_transient( 'nav_menu_' . $location );
            }
        }
    }

    /**
     * Xóa toàn bộ cache của plugin/theme
     */
    public static function flush_all() {
        global $wpdb;

        // Xóa tất cả transients của theme
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_mytheme_%'
             OR option_name LIKE '_transient_timeout_mytheme_%'"
        );

        // Xóa object cache group
        wp_cache_flush();

        do_action( 'mytheme_cache_flushed' );
    }
}

MythemeCache::init();
```

### Fragment Caching

```php
/**
 * Cache một phần (fragment) của trang
 * Hữu ích cho sidebar, widgets, menu, ...
 */
function mytheme_fragment_cache( $key, $ttl, $callback ) {
    $output = get_transient( 'fragment_' . $key );

    if ( false === $output ) {
        ob_start();
        call_user_func( $callback );
        $output = ob_get_clean();

        set_transient( 'fragment_' . $key, $output, $ttl );
    }

    echo $output;
}

// Sử dụng trong template
mytheme_fragment_cache( 'sidebar_popular_posts', HOUR_IN_SECONDS, function() {
    ?>
    <div class="popular-posts">
        <h3>Bài Viết Phổ Biến</h3>
        <?php
        $popular = new WP_Query( array(
            'posts_per_page' => 5,
            'meta_key'       => 'post_views_count',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ) );

        while ( $popular->have_posts() ) : $popular->the_post();
        ?>
            <article>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <span><?php echo get_post_meta( get_the_ID(), 'post_views_count', true ); ?> lượt xem</span>
            </article>
        <?php
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
    <?php
} );
```

---

## 11. Async/Defer Scripts

### Sự khác biệt

```
Normal: <script src="file.js"></script>
  -> Block parsing HTML cho đến khi tải và chạy xong JS
  -> Chậm nhất

Async: <script src="file.js" async></script>
  -> Tải JS song song với parsing HTML
  -> Chạy ngay khi tải xong (có thể block parsing)
  -> Thứ tự không đảm bảo

Defer: <script src="file.js" defer></script>
  -> Tải JS song song với parsing HTML
  -> Chạy SAU KHI HTML parsing xong
  -> Thứ tự được đảm bảo
  -> Tốt nhất cho hiệu năng

Timeline:
Normal: [Download JS] -> [Execute JS] -> [Parse HTML] -> ...
Async:  [Parse HTML + Download JS] -> [Execute JS] -> [Parse HTML tiếp]
Defer:  [Parse HTML + Download JS] -----> [DOMContentLoaded] -> [Execute JS]
```

### Thêm async/defer trong WordPress

```php
/**
 * Thêm defer cho các scripts
 */
function mytheme_defer_scripts( $tag, $handle, $src ) {
    // Các scripts cần defer
    $defer_scripts = array(
        'mytheme-main',
        'mytheme-slider',
        'mytheme-analytics',
        'comment-reply',
    );

    // Các scripts cần async
    $async_scripts = array(
        'google-analytics',
        'facebook-pixel',
    );

    if ( in_array( $handle, $defer_scripts ) ) {
        return str_replace( ' src', ' defer src', $tag );
    }

    if ( in_array( $handle, $async_scripts ) ) {
        return str_replace( ' src', ' async src', $tag );
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'mytheme_defer_scripts', 10, 3 );

/**
 * WordPress 6.3+ có hỗ trợ strategy (async/defer) native
 */
function mytheme_enqueue_scripts_with_strategy() {
    // Defer strategy (khuyên dùng)
    wp_enqueue_script(
        'mytheme-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        '1.0.0',
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    // Async strategy
    wp_enqueue_script(
        'mytheme-analytics',
        get_template_directory_uri() . '/assets/js/analytics.js',
        array(),
        '1.0.0',
        array(
            'strategy'  => 'async',
            'in_footer' => false,
        )
    );
}
add_action( 'wp_enqueue_scripts', 'mytheme_enqueue_scripts_with_strategy' );
```

### Load scripts trong footer

```php
/**
 * Chuyển scripts từ header xuống footer
 */
function mytheme_move_scripts_to_footer() {
    // Chuyển jQuery xuống footer
    wp_deregister_script( 'jquery' );
    wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.min.js' ), array(), null, true );

    // Enqueue tất cả scripts trong footer
    wp_enqueue_script(
        'mytheme-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array( 'jquery' ),
        '1.0.0',
        true  // true = in footer
    );
}
add_action( 'wp_enqueue_scripts', 'mytheme_move_scripts_to_footer' );
```

---

## 12. Database Query Best Practices

### Quy tắc vàng

```php
/**
 * 1. LUÔN sử dụng $wpdb->prepare() cho user input
 */
global $wpdb;

// KHÔNG TỐT - SQL Injection risk
$results = $wpdb->get_results(
    "SELECT * FROM {$wpdb->posts} WHERE post_title = '$user_input'"
);

// TỐT
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_title = %s",
        $user_input
    )
);

/**
 * 2. Chỉ lấy những column cần thiết
 */
// KHÔNG TỐT
$wpdb->get_results( "SELECT * FROM {$wpdb->posts}" );

// TỐT
$wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts}" );

/**
 * 3. LUÔN có LIMIT
 */
// KHÔNG TỐT
$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta}" );

// TỐT
$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} LIMIT 100" );

/**
 * 4. Tránh query trong vòng lặp
 */
// KHÔNG TỐT: N queries
$post_ids = array( 1, 2, 3, 4, 5 );
foreach ( $post_ids as $id ) {
    $meta = get_post_meta( $id, '_price', true ); // 1 query mỗi lần
}

// TỐT: 1 query
$meta_values = $wpdb->get_results( $wpdb->prepare(
    "SELECT post_id, meta_value
     FROM {$wpdb->postmeta}
     WHERE meta_key = %s
     AND post_id IN (" . implode( ',', array_map( 'intval', $post_ids ) ) . ")",
    '_price'
) );

/**
 * 5. Sử dụng WP_Query thay vì query trực tiếp khi có thể
 */
// WP_Query tự động cache, sanitize, và xử lý phức tạp
$query = new WP_Query( array(
    'post_type'      => 'product',
    'posts_per_page' => 10,
    'no_found_rows'  => true,     // Bỏ qua SQL_CALC_FOUND_ROWS
    'fields'         => 'ids',    // Chỉ lấy ID
) );

/**
 * 6. Sử dụng no_found_rows khi không cần phân trang
 */
// WP_Query mặc định chạy SQL_CALC_FOUND_ROWS để đếm tổng số bài viết
// Nếu không cần phân trang, tắt đi:
$query = new WP_Query( array(
    'post_type'      => 'product',
    'posts_per_page' => 5,
    'no_found_rows'  => true,    // Nhanh hơn đáng kể
) );

/**
 * 7. Tránh meta_query phức tạp - Dùng taxonomy thay thế
 */
// CHẬM: meta_query phải scan toàn bộ wp_postmeta
$query = new WP_Query( array(
    'meta_query' => array(
        array(
            'key'     => 'color',
            'value'   => 'red',
            'compare' => '=',
        ),
    ),
) );

// NHANH HƠN: tax_query dùng index tốt hơn
// Chuyển "color" thành taxonomy thay vì meta
$query = new WP_Query( array(
    'tax_query' => array(
        array(
            'taxonomy' => 'product_color',
            'field'    => 'slug',
            'terms'    => 'red',
        ),
    ),
) );

/**
 * 8. Cache kết quả query nặng
 */
function get_expensive_data() {
    $cache_key = 'expensive_query_result';
    $result = wp_cache_get( $cache_key );

    if ( false === $result ) {
        global $wpdb;

        $result = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as price,
                    (SELECT COUNT(*) FROM {$wpdb->comments} c WHERE c.comment_post_ID = p.ID) as comment_count
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_price'
             WHERE p.post_type = 'product'
             AND p.post_status = 'publish'
             ORDER BY CAST(pm.meta_value AS DECIMAL(10,2)) DESC
             LIMIT 50"
        );

        wp_cache_set( $cache_key, $result, '', HOUR_IN_SECONDS );
    }

    return $result;
}

/**
 * 9. Sử dụng update_post_meta_cache và update_post_term_cache
 */
// Khi lấy nhiều posts và cần meta/terms của tất cả
$posts = get_posts( array(
    'post_type'      => 'product',
    'posts_per_page' => 50,
) );

// Batch cache meta cho tất cả posts (1 query thay vì 50)
$post_ids = wp_list_pluck( $posts, 'ID' );
update_meta_cache( 'post', $post_ids );

// Sau đó, get_post_meta() sẽ lấy từ cache, không query nữa
foreach ( $posts as $post ) {
    $price = get_post_meta( $post->ID, '_price', true ); // Từ cache
}

/**
 * 10. Dùng autoload cho options không cần thiết
 */
// Khi thêm option, chỉ autoload khi thực sự cần load mỗi request
add_option( 'my_large_option', $data, '', 'no' ); // autoload = no

// Cập nhật option đã có
// Không thể thay đổi autoload bằng update_option
// Phải dùng SQL trực tiếp hoặc xóa và thêm lại
```

---

## 13. Checklist tối ưu hiệu năng

### Server

```
[ ] PHP 8.2+ được cài đặt
[ ] OPcache được bật và cấu hình đúng
[ ] PHP-FPM được tối ưu (pm settings)
[ ] MySQL/MariaDB được tối ưu (InnoDB buffer pool, query cache)
[ ] Nginx/Apache được cấu hình tối ưu
[ ] HTTP/2 hoặc HTTP/3 được bật
[ ] Gzip/Brotli compression được bật
[ ] SSL/TLS được cấu hình (HTTPS)
[ ] Keep-alive được bật
```

### Caching

```
[ ] Page Cache được bật (WP Super Cache / Nginx FastCGI Cache)
[ ] Object Cache được bật (Redis hoặc Memcached)
[ ] Browser Cache được cấu hình (Cache-Control headers)
[ ] CDN được cấu hình cho static files
[ ] Transients được sử dụng cho dữ liệu tạm thời
[ ] OPcache được bật cho PHP
```

### Database

```
[ ] Giới hạn revisions (WP_POST_REVISIONS = 5)
[ ] Xóa revisions cũ
[ ] Xóa auto-drafts
[ ] Xóa orphaned meta
[ ] Xóa expired transients
[ ] Xóa spam comments
[ ] OPTIMIZE TABLE định kỳ
[ ] Autoloaded options < 1MB
[ ] Index được thêm cho custom meta queries
[ ] Tránh N+1 queries
```

### Frontend

```
[ ] Minify CSS và JS
[ ] Combine CSS/JS files (cẩn thận với HTTP/2)
[ ] Critical CSS được inline
[ ] Non-critical CSS được defer
[ ] JS được defer hoặc async
[ ] JS được load trong footer
[ ] Bỏ CSS/JS không cần trên từng trang
[ ] Bỏ emoji scripts (nếu không cần)
[ ] Bỏ các meta tags không cần
[ ] Font được preload
[ ] Font dùng font-display: swap
```

### Images

```
[ ] Hình ảnh được nén (80% quality cho JPEG)
[ ] WebP được sử dụng
[ ] Lazy loading được bật
[ ] fetchpriority="high" cho LCP image
[ ] Responsive images (srcset/sizes)
[ ] Chỉ tạo các image sizes cần thiết
[ ] SVG được dùng cho icons
[ ] Không có hình ảnh quá lớn (max 2000px width)
```

### WordPress

```
[ ] WordPress phiên bản mới nhất
[ ] Plugins được cập nhật
[ ] Bỏ plugins không cần
[ ] Theme được tối ưu
[ ] Debug mode tắt trên production
[ ] CONCATENATE_SCRIPTS được bật trên admin
[ ] WP-Cron được thay bằng system cron
[ ] XML-RPC được tắt (nếu không cần)
[ ] REST API được giới hạn (nếu cần)
[ ] Heartbeat API được tối ưu
```

### Monitoring

```
[ ] Uptime monitoring (UptimeRobot, Pingdom)
[ ] Error logging (WP_DEBUG_LOG)
[ ] Performance monitoring (New Relic, Query Monitor)
[ ] Google Search Console được kiểm tra Core Web Vitals
[ ] PageSpeed Insights score > 90
[ ] GTmetrix grade A
```

### Security (Liên quan hiệu năng)

```
[ ] Firewall (WAF) - Cloudflare, Wordfence
[ ] Rate limiting cho login
[ ] Disable directory browsing
[ ] Block bad bots (tiết kiệm bandwidth)
[ ] DDoS protection (Cloudflare)
```

### Script kiểm tra nhanh

```bash
#!/bin/bash
# quick-perf-check.sh

WP_PATH="/var/www/html/wordpress"

echo "=== KIỂM TRA HIỆU NĂNG NHANH ==="
echo ""

# PHP version
echo "PHP Version: $(php -v | head -1)"

# OPcache
echo "OPcache: $(php -r 'echo opcache_get_status() ? "ON" : "OFF";' 2>/dev/null || echo 'Không kiểm tra được')"

# WordPress version
echo "WordPress: $(wp core version --path="$WP_PATH" 2>/dev/null)"

# Plugins
echo "Plugins active: $(wp plugin list --status=active --format=count --path="$WP_PATH" 2>/dev/null)"

# Database size
echo "Database size: $(wp db size --path="$WP_PATH" 2>/dev/null)"

# Autoloaded options size
echo "Autoloaded options: $(wp db query "SELECT ROUND(SUM(LENGTH(option_value))/1024/1024, 2) as 'MB' FROM $(wp db prefix --path="$WP_PATH" 2>/dev/null)options WHERE autoload='yes';" --path="$WP_PATH" 2>/dev/null | tail -1) MB"

# Revisions count
echo "Revisions: $(wp post list --post_type=revision --format=count --path="$WP_PATH" 2>/dev/null)"

# Transients count
echo "Transients: $(wp db query "SELECT COUNT(*) FROM $(wp db prefix --path="$WP_PATH" 2>/dev/null)options WHERE option_name LIKE '_transient_%';" --path="$WP_PATH" 2>/dev/null | tail -1)"

# WP_DEBUG
echo "WP_DEBUG: $(wp config get WP_DEBUG --path="$WP_PATH" 2>/dev/null)"

echo ""
echo "=== HOÀN TẤT ==="
```

---

## Tài Liệu Tham Khảo

- [WordPress Performance](https://developer.wordpress.org/advanced-administration/performance/)
- [Google Web Vitals](https://web.dev/vitals/)
- [Redis Object Cache](https://wordpress.org/plugins/redis-cache/)
- [Query Monitor](https://wordpress.org/plugins/query-monitor/)
