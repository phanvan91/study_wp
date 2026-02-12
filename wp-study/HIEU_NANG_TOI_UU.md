# Toi Uu Hieu Nang WordPress

## Muc Luc

1. [Tong quan](#1-tong-quan)
2. [Object Cache](#2-object-cache)
3. [Page Cache](#3-page-cache)
4. [Transients API](#4-transients-api)
5. [Database Optimization](#5-database-optimization)
6. [Image Optimization](#6-image-optimization)
7. [Minify va Concat CSS/JS](#7-minify-va-concat-cssjs)
8. [CDN](#8-cdn)
9. [PHP Optimization](#9-php-optimization)
10. [Profiling va Debug](#10-profiling-va-debug)
11. [Caching trong Plugin/Theme](#11-caching-trong-plugintheme)
12. [Query Optimization](#12-query-optimization)
13. [Checklist toi uu](#13-checklist-toi-uu)

---

## 1. Tong Quan

### Cac yeu to anh huong hieu nang

```
                    ┌──────────────────┐
   Nguoi dung ────> │   CDN / Cache    │
                    └───────┬──────────┘
                            │
                    ┌───────▼──────────┐
                    │   Web Server     │ (Nginx/Apache)
                    │   Page Cache     │
                    └───────┬──────────┘
                            │
                    ┌───────▼──────────┐
                    │   PHP / OPcache  │
                    │   Object Cache   │
                    └───────┬──────────┘
                            │
                    ┌───────▼──────────┐
                    │   Database       │
                    │   (MySQL)        │
                    └──────────────────┘
```

### Muc tieu

- **Time to First Byte (TTFB):** < 200ms
- **Tong thoi gian tai trang:** < 3 giay
- **PageSpeed Score:** > 90

---

## 2. Object Cache

Object cache luu ket qua queries va tinh toan trong memory de khong phai tinh lai.

### WordPress Object Cache mac dinh

WordPress co object cache san nhung **chi ton tai trong 1 request** (non-persistent).

```php
// Luu vao cache
wp_cache_set( 'my_key', $data, 'my_group', 3600 );

// Lay tu cache
$data = wp_cache_get( 'my_key', 'my_group' );
if ( false === $data ) {
    // Cache miss - query lai
    $data = expensive_query();
    wp_cache_set( 'my_key', $data, 'my_group', 3600 );
}

// Xoa cache
wp_cache_delete( 'my_key', 'my_group' );

// Xoa toan bo nhom
wp_cache_flush();
```

### Persistent Object Cache (Redis/Memcached)

Cai Redis hoac Memcached de object cache ton tai giua cac requests.

**Redis:**
```php
// wp-config.php
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_DATABASE', 0 );
```

Cai plugin "Redis Object Cache" hoac "Object Cache Pro" de kich hoat.

---

## 3. Page Cache

Page cache luu toan bo HTML output de tra ve ngay ma khong can chay PHP.

### Nginx FastCGI Cache (hieu qua nhat)

```nginx
# nginx.conf
fastcgi_cache_path /var/run/nginx-cache levels=1:2
    keys_zone=WORDPRESS:100m inactive=60m;
fastcgi_cache_key "$scheme$request_method$host$request_uri";

server {
    # Bo qua cache cho admin, logged-in users
    set $skip_cache 0;
    if ($request_uri ~* "/wp-admin/|/wp-login.php") {
        set $skip_cache 1;
    }
    if ($http_cookie ~* "wordpress_logged_in") {
        set $skip_cache 1;
    }

    location ~ \.php$ {
        fastcgi_cache WORDPRESS;
        fastcgi_cache_valid 200 60m;
        fastcgi_cache_bypass $skip_cache;
        fastcgi_no_cache $skip_cache;
        add_header X-Cache-Status $upstream_cache_status;
    }
}
```

### Plugin Cache pho bien

- **WP Super Cache** - Don gian, mien phi
- **W3 Total Cache** - Nhieu tinh nang
- **WP Rocket** - Premium, de dung

---

## 4. Transients API

Transients la cach cache du lieu co thoi han trong WordPress.

```php
/**
 * Vi du: Cache ket qua API call
 */
function get_weather_data( $city ) {
    // Kiem tra cache
    $cache_key = 'weather_' . sanitize_key( $city );
    $data = get_transient( $cache_key );

    if ( false !== $data ) {
        return $data;  // Tra ve tu cache
    }

    // Cache miss - goi API
    $response = wp_remote_get( "https://api.weather.com/data?city={$city}" );
    if ( is_wp_error( $response ) ) {
        return false;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    // Luu cache 30 phut
    set_transient( $cache_key, $data, 30 * MINUTE_IN_SECONDS );

    return $data;
}

// Xoa cache khi can
delete_transient( 'weather_hanoi' );
```

### Hang so thoi gian WordPress

```php
MINUTE_IN_SECONDS  // 60
HOUR_IN_SECONDS    // 3600
DAY_IN_SECONDS     // 86400
WEEK_IN_SECONDS    // 604800
MONTH_IN_SECONDS   // 2592000
YEAR_IN_SECONDS    // 31536000
```

---

## 5. Database Optimization

### Toi uu wp_options

```php
// Xoa transients het han
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_timeout_%'
     AND option_value < UNIX_TIMESTAMP()"
);
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_%'
     AND option_name NOT LIKE '_transient_timeout_%'
     AND option_name NOT IN (
         SELECT CONCAT('_transient_', SUBSTRING(option_name, 21))
         FROM (SELECT option_name FROM {$wpdb->options}
               WHERE option_name LIKE '_transient_timeout_%'
               AND option_value >= UNIX_TIMESTAMP()) AS active_timeouts
     )"
);
```

### Autoload optimization

```php
// Chi autoload options thuc su can
// Khi tao option, set autoload = 'no' neu khong can load moi request
update_option( 'my_large_option', $data, false );  // autoload = false

// Kiem tra options dang autoload
$wpdb->get_results(
    "SELECT option_name, LENGTH(option_value) as size
     FROM {$wpdb->options}
     WHERE autoload = 'yes'
     ORDER BY size DESC
     LIMIT 20"
);
```

### Don dep database

```sql
-- Xoa revisions cu
DELETE FROM wp_posts WHERE post_type = 'revision'
AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Xoa post meta mo coi
DELETE FROM wp_postmeta
WHERE post_id NOT IN (SELECT ID FROM wp_posts);

-- Xoa comment meta mo coi
DELETE FROM wp_commentmeta
WHERE comment_id NOT IN (SELECT comment_ID FROM wp_comments);

-- Toi uu bang
OPTIMIZE TABLE wp_posts, wp_postmeta, wp_options, wp_comments;
```

---

## 6. Image Optimization

### Trong functions.php

```php
// Tat cac kich thuoc anh khong can
function remove_unused_image_sizes() {
    remove_image_size( 'medium_large' );  // 768px
    remove_image_size( '1536x1536' );
    remove_image_size( '2048x2048' );
}
add_action( 'init', 'remove_unused_image_sizes' );

// Gioi han kich thuoc anh lon nhat
add_filter( 'big_image_size_threshold', function() {
    return 2560;  // pixels
} );
```

### Lazy Loading

WordPress 5.5+ tu dong them `loading="lazy"` cho images. Co the tuy chinh:

```php
// Tat lazy loading cho anh dau tien (above the fold)
add_filter( 'wp_img_tag_add_loading_attr', function( $value, $image, $context ) {
    // Khong lazy load cho anh trong header
    if ( 'the_content' === $context ) {
        static $count = 0;
        $count++;
        if ( $count <= 1 ) {
            return false;  // Khong them loading="lazy" cho anh dau tien
        }
    }
    return $value;
}, 10, 3 );
```

### WebP Support

```php
// Cho phep upload WebP
add_filter( 'mime_types', function( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
} );
```

---

## 7. Minify va Concat CSS/JS

### Trong functions.php

```php
/**
 * Loai bo version query string (cache tot hon)
 */
function remove_version_query( $src ) {
    if ( strpos( $src, 'ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'remove_version_query' );
add_filter( 'script_loader_src', 'remove_version_query' );

/**
 * Defer / Async scripts
 */
function add_defer_attribute( $tag, $handle ) {
    $defer_scripts = array( 'my-script', 'analytics' );
    if ( in_array( $handle, $defer_scripts, true ) ) {
        return str_replace( ' src', ' defer src', $tag );
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'add_defer_attribute', 10, 2 );

/**
 * Loai bo scripts/styles khong can
 */
function remove_unnecessary_assets() {
    // Loai bo jQuery migrate (neu khong can)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', false );
    }

    // Loai bo block library CSS (neu khong dung Gutenberg o frontend)
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );

    // Loai bo emoji scripts
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'wp_enqueue_scripts', 'remove_unnecessary_assets', 100 );
```

---

## 8. CDN

### Cau hinh CDN co ban

```php
// wp-config.php - Doi URL media sang CDN
define( 'WP_CONTENT_URL', 'https://cdn.example.com/wp-content' );

// Hoac dung filter
add_filter( 'wp_get_attachment_url', function( $url ) {
    return str_replace(
        'https://example.com/wp-content/uploads',
        'https://cdn.example.com/wp-content/uploads',
        $url
    );
} );
```

---

## 9. PHP Optimization

### OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.save_comments=1
```

### PHP Version

- PHP 8.x nhanh hon PHP 7.x tu 10-30%
- Luon dung phien ban PHP moi nhat ma WordPress ho tro

---

## 10. Profiling va Debug

### Query Monitor Plugin

Plugin mien phi hien thi:
- So luong va thoi gian SQL queries
- Hooks dang chay
- HTTP API calls
- PHP errors
- Conditional tags
- Enqueued scripts/styles

### WP_DEBUG va SAVEQUERIES

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'SAVEQUERIES', true );

// Trong code - xem tat ca queries
global $wpdb;
echo '<pre>';
print_r( $wpdb->queries );
echo '</pre>';
// Moi query: array( SQL, thoi gian, call stack )
```

### Do thoi gian thuc thi

```php
$start = microtime( true );

// Code can do
expensive_operation();

$elapsed = microtime( true ) - $start;
error_log( sprintf( 'Operation took %.4f seconds', $elapsed ) );
```

---

## 11. Caching Trong Plugin/Theme

### Pattern cache chuan

```php
function get_expensive_data( $key ) {
    // 1. Kiem tra object cache (nhanh nhat)
    $data = wp_cache_get( $key, 'my_plugin' );
    if ( false !== $data ) {
        return $data;
    }

    // 2. Kiem tra transient (persistent)
    $data = get_transient( 'my_plugin_' . $key );
    if ( false !== $data ) {
        wp_cache_set( $key, $data, 'my_plugin' );
        return $data;
    }

    // 3. Query database
    $data = actual_expensive_query( $key );

    // 4. Luu vao ca 2 layers
    wp_cache_set( $key, $data, 'my_plugin' );
    set_transient( 'my_plugin_' . $key, $data, HOUR_IN_SECONDS );

    return $data;
}

// Xoa cache khi du lieu thay doi
function invalidate_cache( $key ) {
    wp_cache_delete( $key, 'my_plugin' );
    delete_transient( 'my_plugin_' . $key );
}
```

### Cache fragment trong template

```php
function get_cached_sidebar() {
    $cache = get_transient( 'sidebar_html' );
    if ( false !== $cache ) {
        echo $cache;
        return;
    }

    ob_start();
    // Render sidebar (nhieu widget, queries)
    dynamic_sidebar( 'sidebar-1' );
    $html = ob_get_clean();

    set_transient( 'sidebar_html', $html, HOUR_IN_SECONDS );
    echo $html;
}
```

---

## 12. Query Optimization

### Cac ky thuat toi uu

```php
// 1. Chi lay fields can thiet
$ids = new WP_Query( array(
    'fields'         => 'ids',     // Chi lay ID, khong load post objects
    'posts_per_page' => 100,
) );

// 2. Tat pagination count neu khong can
$query = new WP_Query( array(
    'no_found_rows' => true,       // Bo query COUNT(*)
    'posts_per_page' => 5,
) );

// 3. Tat meta/term cache neu khong dung
$query = new WP_Query( array(
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
) );

// 4. Tranh meta_query tren bang lon (cham)
// Thay vao do, dung taxonomy hoac custom table

// 5. Su dung post__in de gioi han scope
$query = new WP_Query( array(
    'post__in' => $limited_ids,    // Chi query trong tap nho
) );
```

---

## 13. Checklist Toi Uu

### Server

- [ ] PHP 8.x
- [ ] OPcache bat
- [ ] Persistent object cache (Redis/Memcached)
- [ ] Page cache (Nginx FastCGI / Varnish)
- [ ] HTTPS + HTTP/2
- [ ] Gzip / Brotli compression
- [ ] CDN cho static assets

### WordPress

- [ ] WordPress phien ban moi nhat
- [ ] Xoa plugins/themes khong dung
- [ ] Gioi han so revisions (WP_POST_REVISIONS)
- [ ] Tat pingbacks/trackbacks
- [ ] Tat XML-RPC (neu khong dung)
- [ ] Dung server cron thay vi WP cron

### Database

- [ ] Toi uu tables (OPTIMIZE TABLE)
- [ ] Don dep revisions, transients cu
- [ ] Kiem tra autoload options
- [ ] Them index cho custom tables

### Frontend

- [ ] Minify CSS/JS
- [ ] Lazy loading images
- [ ] Toi uu hinh anh (compress, WebP)
- [ ] Defer/async JavaScript
- [ ] Loai bo CSS/JS khong can
- [ ] Preload critical resources
- [ ] Critical CSS inline

### Cong cu do luong

- [Google PageSpeed Insights](https://pagespeed.web.dev/)
- [GTmetrix](https://gtmetrix.com/)
- [WebPageTest](https://www.webpagetest.org/)
- [Query Monitor](https://wordpress.org/plugins/query-monitor/)

---

## Tai Lieu Tham Khao

- [WordPress Performance](https://developer.wordpress.org/advanced-administration/performance/)
- [Google Web Vitals](https://web.dev/vitals/)
- [Redis Object Cache](https://wordpress.org/plugins/redis-cache/)
