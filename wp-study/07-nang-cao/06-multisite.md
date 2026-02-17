# WordPress Multisite - Hướng Dẫn Chi Tiết

## Mục lục

1. [Tổng quan Multisite](#1-tong-quan-multisite)
2. [Cài đặt và cấu hình Multisite](#2-cai-dat-va-cau-hinh-multisite)
3. [Quản lý Sites trong Network](#3-quan-ly-sites-trong-network)
4. [switch_to_blog() và Cross-Site Operations](#4-switch_to_blog-va-cross-site-operations)
5. [Network Options vs Site Options](#5-network-options-vs-site-options)
6. [Multisite Hooks quan trọng](#6-multisite-hooks-quan-trong)
7. [Plugin Network-Aware](#7-plugin-network-aware)
8. [Must-Use (MU) Plugins](#8-must-use-mu-plugins)
9. [Network Admin Pages](#9-network-admin-pages)
10. [Cross-Site Query](#10-cross-site-query)
11. [Domain Mapping](#11-domain-mapping)
12. [Ví dụ thực tế: Shared Content Plugin](#12-vi-du-thuc-te-shared-content-plugin)
13. [Ví dụ thực tế: Network Dashboard Widget](#13-vi-du-thuc-te-network-dashboard-widget)
14. [So sánh với Laravel Multi-Tenancy](#14-so-sanh-voi-laravel-multi-tenancy)

---

## 1. Tổng quan Multisite

### Multisite là gì?

```
WordPress Multisite = 1 cài đặt WordPress → quản lý NHIỀU websites

Cấu trúc:
┌─────────────────────────────────────────────────┐
│              Network (Super Admin)                │
│                                                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │  Site 1   │  │  Site 2   │  │  Site 3   │      │
│  │ (Main)    │  │ (Blog)    │  │ (Shop)    │      │
│  │ Admin: A  │  │ Admin: B  │  │ Admin: C  │      │
│  └──────────┘  └──────────┘  └──────────┘       │
│                                                   │
│  Chia sẻ: WordPress core, plugins, themes         │
│  Riêng biệt: Content, users*, uploads, settings   │
└─────────────────────────────────────────────────┘

* Users có thể chia sẻ across sites
```

### Subdomain vs Subdirectory

```
Subdomain:
  - site1.example.com
  - site2.example.com
  - shop.example.com
  → Cần wildcard DNS: *.example.com → server IP
  → Phù hợp: Network websites khác nhau hoàn toàn

Subdirectory:
  - example.com/site1/
  - example.com/site2/
  - example.com/shop/
  → Không cần DNS đặc biệt
  → Phù hợp: Blog network, nhóm trang con
  → LƯU Ý: Chỉ chọn được khi cài đặt lần đầu (< 1 tháng)
```

### Khi nào dùng Multisite?

```
NÊN dùng:
  ✅ Trường đại học: mỗi khoa 1 website
  ✅ Công ty: website chính + blog + shop
  ✅ Agency: quản lý nhiều websites khách hàng
  ✅ Franchise: cùng brand, mỗi chi nhánh 1 site
  ✅ Multilingual: mỗi ngôn ngữ 1 site

KHÔNG NÊN dùng:
  ❌ Chỉ cần 1-2 websites (overhead không đáng)
  ❌ Websites hoàn toàn khác nhau (khác plugins, themes)
  ❌ Cần hosting riêng biệt cho từng site
  ❌ Clients cần tự quản lý plugins/themes
```

---

## 2. Cài đặt và cấu hình Multisite

### 2.1. Bật Multisite trong wp-config.php

```php
<?php
/**
 * File: wp-config.php
 *
 * Bước 1: Thêm TRƯỚC dòng "That's all, stop editing!"
 */

/* Bật Multisite */
define( 'WP_ALLOW_MULTISITE', true );

/**
 * Bước 2: Vào Dashboard → Tools → Network Setup → Install
 * WordPress sẽ tạo code cho bạn copy vào wp-config.php và .htaccess
 *
 * Bước 3: Thêm code WordPress tạo ra:
 */

/* Multisite Configuration */
define( 'MULTISITE',             true );
define( 'SUBDOMAIN_INSTALL',     false );  // true = subdomain, false = subdirectory
define( 'DOMAIN_CURRENT_SITE',   'example.com' );
define( 'PATH_CURRENT_SITE',     '/' );
define( 'SITE_ID_CURRENT_SITE',  1 );
define( 'BLOG_ID_CURRENT_SITE',  1 );

/**
 * Tùy chọn thêm:
 */
// Cho phép tạo tối đa bao nhiêu sites
define( 'BLOG_COUNT',            100 );

// Upload quota mỗi site (MB)
define( 'BLOG_UPLOAD_SPACE',     100 );

// Cho phép upload file types
define( 'UPLOAD_FILETYPES',      'jpg jpeg png gif mp3 mp4 pdf doc docx' );

// Max file upload size (KB)
define( 'FILEUPLOAD_MAXK',       10240 ); // 10MB
```

### 2.2. Cấu hình .htaccess

```apache
# File: .htaccess (Subdirectory install)

RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]

# Add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . index.php [L]
```

```apache
# File: .htaccess (Subdomain install)

RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]

# Add a trailing slash to /wp-admin
RewriteRule ^wp-admin$ wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule . index.php [L]
```

### 2.3. Database Schema Multisite

```
Bảng chung (network-wide):
  wp_users              ← Users chia sẻ across all sites
  wp_usermeta           ← User metadata
  wp_blogs              ← Danh sách tất cả sites
  wp_blog_versions      ← DB version mỗi site
  wp_site               ← Network info
  wp_sitemeta           ← Network options
  wp_registration_log   ← Log đăng ký
  wp_signups            ← Pending signups

Bảng riêng mỗi site (prefix = wp_{blog_id}_):
  wp_posts        → wp_2_posts, wp_3_posts...
  wp_postmeta     → wp_2_postmeta, wp_3_postmeta...
  wp_comments     → wp_2_comments...
  wp_options      → wp_2_options...
  wp_terms        → wp_2_terms...
  wp_termmeta     → wp_2_termmeta...
  wp_term_taxonomy       → wp_2_term_taxonomy...
  wp_term_relationships  → wp_2_term_relationships...
  wp_links        → wp_2_links...

LƯU Ý: Site 1 (main) dùng prefix wp_ (không có số)
```

---

## 3. Quản lý Sites trong Network

### 3.1. get_sites() - WP_Site_Query

```php
<?php
/**
 * get_sites() là API chính để query sites trong network.
 * Tương đương WP_Query nhưng cho sites.
 * Có từ WordPress 4.6+
 */

// Lấy tất cả sites
$sites = get_sites();

// Lấy sites với điều kiện
$sites = get_sites( array(
    'number'     => 20,               // Limit
    'offset'     => 0,                // Offset
    'orderby'    => 'registered',     // Sắp xếp: 'id', 'domain', 'path', 'registered', 'last_updated'
    'order'      => 'DESC',
    'network_id' => get_current_network_id(), // Network ID
    'public'     => 1,                // Chỉ sites public
    'archived'   => 0,
    'mature'     => 0,
    'spam'       => 0,
    'deleted'    => 0,
    'search'     => 'blog',           // Tìm trong domain và path
    'site__in'   => array( 1, 2, 5 ), // Chỉ lấy site IDs cụ thể
    'site__not_in' => array( 3 ),     // Loại trừ site IDs
) );

// Mỗi $site là object WP_Site
foreach ( $sites as $site ) {
    echo $site->blog_id;              // Site ID
    echo $site->domain;               // Domain: example.com
    echo $site->path;                 // Path: /blog/
    echo $site->registered;           // Ngày tạo
    echo $site->last_updated;         // Lần cập nhật cuối
    echo $site->public;               // 1 = public
    echo $site->archived;             // 1 = archived
    echo $site->spam;                 // 1 = spam
    echo $site->deleted;              // 1 = deleted
    echo $site->blogname;             // Tên blog (from options)
    echo $site->siteurl;              // Full URL
    echo $site->home;                 // Home URL
}

// Đếm tổng sites
$count = get_sites( array(
    'count'  => true,
    'public' => 1,
) );
echo "Tổng sites public: {$count}";

// Lấy thông tin 1 site cụ thể
$site = get_site( 2 );  // Site ID = 2
$details = get_blog_details( 2 );
echo $details->blogname;
echo $details->siteurl;
```

### 3.2. Tạo và xóa Site programmatically

```php
<?php
/**
 * Tạo site mới trong network.
 */

// WordPress 5.1+: wpmu_create_blog()
$blog_id = wpmu_create_blog(
    'example.com',           // Domain
    '/newsite/',             // Path (subdirectory) hoặc 'newsite.example.com' (subdomain)
    'Site Mới',              // Title
    get_current_user_id(),   // Admin user ID
    array(                   // Meta (options cho site mới)
        'public' => 1,
    ),
    get_current_network_id() // Network ID
);

if ( is_wp_error( $blog_id ) ) {
    echo 'Lỗi: ' . $blog_id->get_error_message();
} else {
    echo "Site mới tạo thành công! ID: {$blog_id}";

    // Cài đặt thêm cho site mới
    switch_to_blog( $blog_id );
    update_option( 'blogdescription', 'Mô tả site mới' );
    update_option( 'permalink_structure', '/%postname%/' );
    restore_current_blog();
}

/**
 * Xóa site (deactivate).
 * LƯU Ý: Không xóa database tables, chỉ đánh dấu deleted.
 */
// Deactivate (soft delete)
update_blog_status( $blog_id, 'deleted', 1 );

// Archive
update_blog_status( $blog_id, 'archived', 1 );

// Xóa hoàn toàn (xóa cả tables) - NGUY HIỂM
wpmu_delete_blog( $blog_id, true ); // true = drop tables
```

---

## 4. switch_to_blog() và Cross-Site Operations

### 4.1. Cú pháp cơ bản

```php
<?php
/**
 * switch_to_blog() cho phép thao tác dữ liệu của site khác.
 *
 * QUAN TRỌNG:
 * - LUÔN gọi restore_current_blog() sau khi xong
 * - switch_to_blog() có thể lồng nhau (stack-based)
 * - Nếu không restore → memory leak, data corruption
 */

// Pattern chuẩn
$target_blog_id = 3;

switch_to_blog( $target_blog_id );

// Bây giờ tất cả WordPress functions hoạt động trên site 3
$posts = get_posts( array( 'numberposts' => 5 ) );
$option = get_option( 'blogname' );
$url = get_site_url();

// LUÔN restore
restore_current_blog();

// Bây giờ đã quay lại site ban đầu
```

### 4.2. Pattern an toàn với try/finally

```php
<?php
/**
 * Pattern an toàn: dùng try/finally để đảm bảo restore.
 */

function my_get_posts_from_site( int $blog_id, int $count = 5 ): array {
    switch_to_blog( $blog_id );

    try {
        $posts = get_posts( array(
            'numberposts' => $count,
            'post_status' => 'publish',
        ) );

        // Chuẩn bị data trước khi restore (vì permalink phụ thuộc blog context)
        $result = array();
        foreach ( $posts as $post ) {
            $result[] = array(
                'id'        => $post->ID,
                'title'     => get_the_title( $post ),
                'permalink' => get_permalink( $post ),
                'date'      => get_the_date( 'd/m/Y', $post ),
                'excerpt'   => get_the_excerpt( $post ),
                'thumbnail' => get_the_post_thumbnail_url( $post, 'medium' ),
            );
        }

        return $result;
    } finally {
        restore_current_blog();
    }
}

// Sử dụng
$blog2_posts = my_get_posts_from_site( 2, 10 );
$blog3_posts = my_get_posts_from_site( 3, 5 );
```

### 4.3. Lồng switch_to_blog()

```php
<?php
/**
 * switch_to_blog() hoạt động theo stack.
 * Mỗi switch push vào stack, mỗi restore pop ra.
 */

echo get_current_blog_id(); // 1

switch_to_blog( 2 );
echo get_current_blog_id(); // 2

    switch_to_blog( 3 );
    echo get_current_blog_id(); // 3

    restore_current_blog();
    echo get_current_blog_id(); // 2 (quay lại level trước)

restore_current_blog();
echo get_current_blog_id(); // 1 (quay lại ban đầu)

/**
 * ms_is_switched() kiểm tra có đang ở blog khác không.
 */
if ( ms_is_switched() ) {
    echo 'Đang ở site khác (đã switch)';
}
```

### 4.4. Những gì THAY ĐỔI và KHÔNG thay đổi khi switch

```php
<?php
/**
 * THAY ĐỔI khi switch_to_blog():
 *   ✅ $wpdb->prefix (wp_2_, wp_3_...)
 *   ✅ get_option() / update_option() → đọc/ghi bảng options của site đích
 *   ✅ get_posts() / WP_Query → query bảng posts của site đích
 *   ✅ get_permalink() → URL theo site đích
 *   ✅ get_site_url() / get_home_url()
 *   ✅ wp_upload_dir() → thư mục uploads của site đích
 *   ✅ get_current_blog_id()
 *
 * KHÔNG thay đổi:
 *   ❌ Loaded plugins (vẫn là plugins của site ban đầu)
 *   ❌ Current theme (vẫn là theme của site ban đầu)
 *   ❌ Hooks đã registered (không re-fire init, plugins_loaded...)
 *   ❌ Current user (vẫn là user hiện tại)
 *   ❌ Global variables (ngoại trừ $wpdb->prefix)
 *   ❌ Object cache (shared across sites)
 *
 * → CẢNH BÁO: Nếu site 2 dùng plugin X mà site 1 không có,
 *   functions của plugin X sẽ KHÔNG available khi switch_to_blog(2)
 */
```

---

## 5. Network Options vs Site Options

```php
<?php
/**
 * Network Options: Lưu trong bảng wp_sitemeta, chia sẻ toàn network.
 * Site Options: Lưu trong bảng wp_{blog_id}_options, riêng mỗi site.
 */

// ── NETWORK OPTIONS (toàn network) ─────────────────────────────

// Lưu
add_network_option( null, 'my_plugin_network_setting', 'value' );
// null = network hiện tại, hoặc truyền network_id

// Đọc
$value = get_network_option( null, 'my_plugin_network_setting', 'default' );

// Cập nhật
update_network_option( null, 'my_plugin_network_setting', 'new_value' );

// Xóa
delete_network_option( null, 'my_plugin_network_setting' );

// ── SITE OPTIONS (riêng mỗi site) ──────────────────────────────

// Hoạt động bình thường, mỗi site có options riêng
add_option( 'my_plugin_site_setting', 'value' );
$value = get_option( 'my_plugin_site_setting', 'default' );
update_option( 'my_plugin_site_setting', 'new_value' );
delete_option( 'my_plugin_site_setting' );

// ── KHI NÀO DÙNG GÌ? ──────────────────────────────────────────
/*
Network Options:
  - License key (1 key cho toàn network)
  - API credentials chung
  - Network-wide settings (maintenance mode, global announcement)
  - Plugin version (để migration)

Site Options:
  - Cài đặt giao diện riêng mỗi site
  - Nội dung riêng (featured posts, banner...)
  - Per-site configuration
*/

// ── VÍ DỤ: Plugin settings hybrid ──────────────────────────────

class My_Network_Plugin_Settings {

    /**
     * Lấy setting: ưu tiên site → fallback network.
     */
    public static function get( string $key, $default = null ) {
        // Kiểm tra site có override không
        $site_value = get_option( "my_plugin_{$key}" );
        if ( false !== $site_value ) {
            return $site_value;
        }

        // Fallback về network default
        return get_network_option( null, "my_plugin_{$key}", $default );
    }

    /**
     * Lưu setting cho network (Super Admin).
     */
    public static function set_network( string $key, $value ): void {
        update_network_option( null, "my_plugin_{$key}", $value );
    }

    /**
     * Lưu setting override cho site (Site Admin).
     */
    public static function set_site( string $key, $value ): void {
        update_option( "my_plugin_{$key}", $value );
    }
}

// Super Admin set mặc định cho toàn network
My_Network_Plugin_Settings::set_network( 'color_scheme', 'blue' );

// Site Admin override cho site của mình
My_Network_Plugin_Settings::set_site( 'color_scheme', 'red' );

// Lấy giá trị → sẽ trả về 'red' (site override)
$color = My_Network_Plugin_Settings::get( 'color_scheme' ); // 'red'
```

---

## 6. Multisite Hooks quan trọng

### 6.1. Hooks khi tạo/xóa Site

```php
<?php
/**
 * wp_initialize_site (WordPress 5.1+)
 * Fire sau khi site mới được tạo và tables đã sẵn sàng.
 * Thay thế wpmu_new_blog (deprecated từ 5.1).
 */
add_action( 'wp_initialize_site', function( WP_Site $new_site, array $args ) {
    $blog_id = $new_site->blog_id;

    switch_to_blog( $blog_id );

    try {
        // Tạo default pages
        wp_insert_post( array(
            'post_title'   => 'Giới Thiệu',
            'post_content' => '<!-- wp:paragraph --><p>Chào mừng đến website mới!</p><!-- /wp:paragraph -->',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );

        wp_insert_post( array(
            'post_title'   => 'Liên Hệ',
            'post_content' => '<!-- wp:paragraph --><p>Thông tin liên hệ.</p><!-- /wp:paragraph -->',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );

        // Set default options
        update_option( 'blogdescription', 'Website mới trong hệ thống' );
        update_option( 'permalink_structure', '/%postname%/' );
        update_option( 'default_comment_status', 'closed' );
        update_option( 'timezone_string', 'Asia/Ho_Chi_Minh' );
        update_option( 'date_format', 'd/m/Y' );

        // Activate default theme
        switch_theme( 'developer-starter' );

        // Tạo custom tables cho plugin
        my_plugin_create_tables();

    } finally {
        restore_current_blog();
    }
}, 10, 2 );

/**
 * wp_delete_site (WordPress 5.1+)
 * Fire TRƯỚC khi site bị xóa.
 */
add_action( 'wp_delete_site', function( WP_Site $old_site ) {
    $blog_id = $old_site->blog_id;

    // Cleanup plugin data cho site này
    switch_to_blog( $blog_id );
    try {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_plugin_data" );
    } finally {
        restore_current_blog();
    }

    error_log( "Site #{$blog_id} deleted, plugin data cleaned up." );
} );

/**
 * wp_validate_site_data
 * Validate data trước khi tạo/update site.
 */
add_filter( 'wp_validate_site_data', function( WP_Error $errors, array $data, $old_site ) {
    // Giới hạn: không cho tạo site có path chứa "admin"
    if ( isset( $data['path'] ) && strpos( $data['path'], 'admin' ) !== false ) {
        $errors->add( 'invalid_path', 'Path không được chứa "admin".' );
    }
    return $errors;
}, 10, 3 );
```

### 6.2. Hooks cho User trong Multisite

```php
<?php
/**
 * add_user_to_blog
 * Fire khi user được thêm vào 1 site (assign role).
 */
add_action( 'add_user_to_blog', function( int $user_id, string $role, int $blog_id ) {
    // Gửi email chào mừng
    $user = get_userdata( $user_id );
    $blog = get_blog_details( $blog_id );

    wp_mail(
        $user->user_email,
        sprintf( 'Bạn đã được thêm vào %s', $blog->blogname ),
        sprintf(
            "Xin chào %s,\n\nBạn đã được thêm vào website %s với vai trò %s.\n\nĐăng nhập: %s",
            $user->display_name,
            $blog->blogname,
            $role,
            $blog->siteurl . '/wp-login.php'
        )
    );
}, 10, 3 );

/**
 * remove_user_from_blog
 * Fire khi user bị xóa khỏi site.
 */
add_action( 'remove_user_from_blog', function( int $user_id, int $blog_id ) {
    error_log( "User #{$user_id} removed from blog #{$blog_id}" );
}, 10, 2 );

/**
 * Kiểm tra user thuộc site nào.
 */
$user_id = get_current_user_id();

// Lấy tất cả sites mà user thuộc về
$user_blogs = get_blogs_of_user( $user_id );
foreach ( $user_blogs as $blog ) {
    echo $blog->blogname . ' (ID: ' . $blog->userblog_id . ')';
}

// Kiểm tra user có thuộc site cụ thể không
$is_member = is_user_member_of_blog( $user_id, 3 ); // site ID = 3

// Thêm user vào site
add_user_to_blog( 3, $user_id, 'editor' );

// Xóa user khỏi site
remove_user_from_blog( $user_id, 3 );
```

### 6.3. Network Admin Hooks

```php
<?php
/**
 * network_admin_menu
 * Thêm menu vào Network Admin dashboard.
 */
add_action( 'network_admin_menu', function() {
    add_menu_page(
        'My Network Plugin',
        'My Plugin',
        'manage_network_options',  // Capability cho Super Admin
        'my-network-plugin',
        'my_network_plugin_page',
        'dashicons-admin-multisite',
        30
    );
} );

/**
 * Phân biệt admin hooks:
 *
 * admin_menu          → Menu trong Site Admin
 * network_admin_menu  → Menu trong Network Admin
 * user_admin_menu     → Menu trong User Admin (Multisite)
 *
 * admin_init          → Chạy trên mọi admin page (site + network)
 * network_admin_edit  → Chỉ chạy trên network admin
 */
```

---

## 7. Plugin Network-Aware

### 7.1. Kiểm tra Multisite

```php
<?php
/**
 * Viết plugin hoạt động trên cả single site và multisite.
 */

class My_Network_Aware_Plugin {

    public static function init(): void {
        // Kiểm tra có phải multisite không
        if ( ! is_multisite() ) {
            // Single site: hoạt động bình thường
            add_action( 'admin_menu', array( self::class, 'add_site_menu' ) );
            return;
        }

        // Multisite: thêm cả network menu
        add_action( 'admin_menu', array( self::class, 'add_site_menu' ) );
        add_action( 'network_admin_menu', array( self::class, 'add_network_menu' ) );
    }

    /**
     * Kiểm tra plugin được activate ở level nào.
     */
    public static function is_network_activated(): bool {
        if ( ! is_multisite() ) {
            return false;
        }

        // Kiểm tra trong danh sách network-activated plugins
        $network_plugins = get_site_option( 'active_sitewide_plugins', array() );
        return isset( $network_plugins[ plugin_basename( MY_PLUGIN_FILE ) ] );
    }

    public static function add_site_menu(): void {
        add_options_page(
            'My Plugin Settings',
            'My Plugin',
            'manage_options',
            'my-plugin',
            array( self::class, 'render_site_settings' )
        );
    }

    public static function add_network_menu(): void {
        add_submenu_page(
            'settings.php',
            'My Plugin Network Settings',
            'My Plugin',
            'manage_network_options',
            'my-plugin-network',
            array( self::class, 'render_network_settings' )
        );
    }

    public static function render_site_settings(): void {
        echo '<div class="wrap"><h1>Site Settings</h1>';
        // Site-specific settings form...
        echo '</div>';
    }

    public static function render_network_settings(): void {
        echo '<div class="wrap"><h1>Network Settings</h1>';
        // Network-wide settings form...
        echo '</div>';
    }
}

My_Network_Aware_Plugin::init();
```

### 7.2. Activation cho Multisite

```php
<?php
/**
 * Khi plugin network-activated, activation hook chỉ fire cho main site.
 * Phải tự loop qua tất cả sites.
 */

register_activation_hook( __FILE__, 'my_plugin_activate' );

function my_plugin_activate( bool $network_wide ): void {
    if ( is_multisite() && $network_wide ) {
        // Network activation: chạy cho tất cả sites
        $sites = get_sites( array( 'fields' => 'ids' ) );
        foreach ( $sites as $blog_id ) {
            switch_to_blog( $blog_id );
            my_plugin_single_activate();
            restore_current_blog();
        }
    } else {
        // Single site activation
        my_plugin_single_activate();
    }
}

function my_plugin_single_activate(): void {
    // Tạo tables, set options...
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $table   = $wpdb->prefix . 'my_plugin_data';

    dbDelta( "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        value LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) {$charset};" );

    add_option( 'my_plugin_version', '1.0.0' );
}

/**
 * Hook vào site mới được tạo → auto-activate plugin.
 */
add_action( 'wp_initialize_site', function( WP_Site $new_site ) {
    // Chỉ chạy nếu plugin đang network-activated
    $network_plugins = get_site_option( 'active_sitewide_plugins', array() );
    if ( ! isset( $network_plugins[ plugin_basename( __FILE__ ) ] ) ) {
        return;
    }

    switch_to_blog( $new_site->blog_id );
    my_plugin_single_activate();
    restore_current_blog();
}, 100 );
```

---

## 8. Must-Use (MU) Plugins

```php
<?php
/**
 * Must-Use Plugins:
 * - Đặt trong wp-content/mu-plugins/
 * - TỰ ĐỘNG active trên TẤT CẢ sites trong network
 * - Không thể deactivate từ admin
 * - Load TRƯỚC plugins thường
 * - Không hỗ trợ subdirectories (chỉ file .php ở root)
 *
 * Dùng cho:
 *   - Security rules bắt buộc
 *   - Custom login/authentication
 *   - Network-wide modifications
 *   - Performance tuning
 *   - Disable features cho toàn network
 */

// File: wp-content/mu-plugins/network-security.php

/**
 * MU-Plugin: Network Security Rules
 * Áp dụng cho tất cả sites, không thể tắt.
 */

// 1. Force SSL admin cho tất cả sites
if ( ! defined( 'FORCE_SSL_ADMIN' ) ) {
    define( 'FORCE_SSL_ADMIN', true );
}

// 2. Disable file editing trong admin
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}

// 3. Giới hạn login attempts
add_filter( 'authenticate', function( $user, $username, $password ) {
    if ( empty( $username ) ) {
        return $user;
    }

    $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $key   = 'login_attempts_' . md5( $ip );
    $count = (int) get_site_transient( $key );

    if ( $count >= 5 ) {
        return new WP_Error(
            'too_many_attempts',
            sprintf(
                'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau %d phút.',
                15
            )
        );
    }

    return $user;
}, 30, 3 );

add_action( 'wp_login_failed', function( $username ) {
    $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $key   = 'login_attempts_' . md5( $ip );
    $count = (int) get_site_transient( $key );
    set_site_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
} );

// 4. Disable XML-RPC cho toàn network
add_filter( 'xmlrpc_enabled', '__return_false' );

// 5. Security headers
add_action( 'send_headers', function() {
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-XSS-Protection: 1; mode=block' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
} );
```

```php
<?php
// File: wp-content/mu-plugins/network-branding.php

/**
 * MU-Plugin: Network Branding
 * Custom login page, admin footer cho toàn network.
 */

// Custom login logo
add_action( 'login_enqueue_scripts', function() {
    $logo_url = network_site_url( '/wp-content/mu-plugins/assets/network-logo.png' );
    echo '<style>
        #login h1 a {
            background-image: url(' . esc_url( $logo_url ) . ') !important;
            background-size: contain;
            width: 200px;
            height: 80px;
        }
    </style>';
} );

// Custom admin footer
add_filter( 'admin_footer_text', function() {
    return sprintf(
        'Powered by <strong>%s</strong> Network &mdash; %s',
        esc_html( get_network()->site_name ),
        esc_html( date( 'Y' ) )
    );
} );
```

---

## 9. Network Admin Pages

```php
<?php
/**
 * Tạo trang cài đặt trong Network Admin.
 */

class My_Network_Settings_Page {

    private const PAGE_SLUG = 'my-network-settings';
    private const NONCE     = 'my_network_settings_nonce';

    public static function register(): void {
        add_action( 'network_admin_menu', array( self::class, 'add_menu' ) );
        add_action( 'network_admin_edit_' . self::PAGE_SLUG, array( self::class, 'save_settings' ) );
    }

    public static function add_menu(): void {
        add_submenu_page(
            'settings.php',
            'My Plugin - Network Settings',
            'My Plugin',
            'manage_network_options',
            self::PAGE_SLUG,
            array( self::class, 'render_page' )
        );
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_die( 'Không có quyền truy cập.' );
        }

        $license_key     = get_network_option( null, 'my_plugin_license_key', '' );
        $maintenance_mode = get_network_option( null, 'my_plugin_maintenance', false );
        $allowed_themes  = get_network_option( null, 'my_plugin_allowed_themes', array() );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'My Plugin - Network Settings', 'my-plugin' ); ?></h1>

            <?php
            // Hiện thông báo save thành công
            if ( isset( $_GET['updated'] ) ) {
                echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
            }
            ?>

            <!-- Network Admin settings phải POST đến edit.php -->
            <form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=' . self::PAGE_SLUG ) ); ?>">
                <?php wp_nonce_field( self::NONCE ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="license_key">License Key</label></th>
                        <td>
                            <input type="text" id="license_key" name="license_key"
                                   value="<?php echo esc_attr( $license_key ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="maintenance">Maintenance Mode</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="maintenance" value="1"
                                       <?php checked( $maintenance_mode ); ?> />
                                Bật chế độ bảo trì cho toàn network
                            </label>
                        </td>
                    </tr>
                </table>

                <h2>Sites trong Network</h2>
                <table class="widefat striped">
                    <thead>
                        <tr><th>ID</th><th>URL</th><th>Tên</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ( get_sites() as $site ) : ?>
                            <tr>
                                <td><?php echo esc_html( $site->blog_id ); ?></td>
                                <td><a href="<?php echo esc_url( $site->siteurl ); ?>"><?php echo esc_html( $site->siteurl ); ?></a></td>
                                <td><?php echo esc_html( $site->blogname ); ?></td>
                                <td><?php echo $site->deleted ? '🔴 Deleted' : '🟢 Active'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button( 'Save Network Settings' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Xử lý form submit.
     * Network Admin dùng pattern khác Site Admin:
     * POST → edit.php?action=slug → callback → redirect
     */
    public static function save_settings(): void {
        check_admin_referer( self::NONCE );

        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        // Save network options
        update_network_option(
            null,
            'my_plugin_license_key',
            sanitize_text_field( $_POST['license_key'] ?? '' )
        );

        update_network_option(
            null,
            'my_plugin_maintenance',
            ! empty( $_POST['maintenance'] )
        );

        // Redirect về settings page
        wp_safe_redirect(
            add_query_arg(
                array( 'page' => self::PAGE_SLUG, 'updated' => 'true' ),
                network_admin_url( 'settings.php' )
            )
        );
        exit;
    }
}

My_Network_Settings_Page::register();
```

---

## 10. Cross-Site Query

### 10.1. Lấy bài viết từ nhiều sites

```php
<?php
/**
 * Lấy bài viết mới nhất từ tất cả sites trong network.
 * Hữu ích cho: trang chủ network, feed tổng hợp...
 */

function my_get_network_recent_posts( int $per_site = 3, int $total = 10 ): array {
    $all_posts = array();
    $sites     = get_sites( array(
        'public'   => 1,
        'archived' => 0,
        'deleted'  => 0,
        'spam'     => 0,
    ) );

    foreach ( $sites as $site ) {
        switch_to_blog( $site->blog_id );

        $posts = get_posts( array(
            'numberposts' => $per_site,
            'post_status' => 'publish',
            'post_type'   => 'post',
        ) );

        foreach ( $posts as $post ) {
            $all_posts[] = array(
                'blog_id'   => $site->blog_id,
                'blog_name' => get_bloginfo( 'name' ),
                'blog_url'  => get_site_url(),
                'post_id'   => $post->ID,
                'title'     => get_the_title( $post ),
                'permalink' => get_permalink( $post ),
                'date'      => get_the_date( 'Y-m-d H:i:s', $post ),
                'timestamp' => strtotime( $post->post_date ),
                'excerpt'   => get_the_excerpt( $post ),
                'thumbnail' => get_the_post_thumbnail_url( $post, 'medium' ),
                'author'    => get_the_author_meta( 'display_name', $post->post_author ),
            );
        }

        restore_current_blog();
    }

    // Sắp xếp theo ngày mới nhất
    usort( $all_posts, function( $a, $b ) {
        return $b['timestamp'] - $a['timestamp'];
    } );

    // Giới hạn tổng số
    return array_slice( $all_posts, 0, $total );
}

// Sử dụng
$recent = my_get_network_recent_posts( 3, 10 );
foreach ( $recent as $post ) {
    printf(
        '<article>
            <h3><a href="%s">%s</a></h3>
            <span class="meta">%s — %s</span>
        </article>',
        esc_url( $post['permalink'] ),
        esc_html( $post['title'] ),
        esc_html( $post['blog_name'] ),
        esc_html( $post['date'] )
    );
}
```

### 10.2. Direct SQL Cross-Site (performance tốt hơn)

```php
<?php
/**
 * Direct SQL: Nhanh hơn switch_to_blog loop,
 * nhưng bypass WordPress filters/hooks.
 * Dùng khi cần performance cao.
 */

function my_get_network_posts_sql( int $limit = 20 ): array {
    global $wpdb;

    $sites = get_sites( array( 'fields' => 'ids', 'public' => 1, 'deleted' => 0 ) );
    $union_parts = array();

    foreach ( $sites as $blog_id ) {
        $prefix = $wpdb->get_blog_prefix( $blog_id );

        $union_parts[] = $wpdb->prepare(
            "SELECT
                %d AS blog_id,
                p.ID AS post_id,
                p.post_title,
                p.post_date,
                p.post_author
             FROM {$prefix}posts p
             WHERE p.post_status = 'publish'
             AND p.post_type = 'post'",
            $blog_id
        );
    }

    if ( empty( $union_parts ) ) {
        return array();
    }

    $sql = implode( ' UNION ALL ', $union_parts );
    $sql .= $wpdb->prepare( ' ORDER BY post_date DESC LIMIT %d', $limit );

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return $wpdb->get_results( $sql );
}
```

---

## 11. Domain Mapping

```php
<?php
/**
 * WordPress Multisite hỗ trợ domain mapping native từ 4.5+.
 * Mỗi site có thể dùng domain riêng.
 *
 * Setup:
 * 1. DNS: custom-domain.com → server IP
 * 2. Web server: thêm custom-domain.com vào server config
 * 3. WordPress: Network Admin → Sites → Edit → Site Address = custom domain
 */

// Nginx config cho domain mapping
/*
server {
    listen 80;
    server_name *.example.com custom-domain.com another-domain.com;
    root /var/www/html;

    # ... standard WordPress config
}
*/

/**
 * Sunrise.php: Tùy chỉnh domain resolution.
 * File: wp-content/sunrise.php
 * Bật trong wp-config.php: define('SUNRISE', true);
 */

// wp-config.php
// define( 'SUNRISE', true );

// wp-content/sunrise.php (ví dụ đơn giản)
// WordPress sẽ load file này rất sớm trong quá trình bootstrap

/**
 * Helper: Lấy blog ID từ custom domain.
 */
function my_get_blog_id_by_domain( string $domain ): ?int {
    global $wpdb;

    $blog_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = %s LIMIT 1",
        $domain
    ) );

    return $blog_id ? (int) $blog_id : null;
}

/**
 * Hook: Redirect www sang non-www cho tất cả sites.
 */
add_action( 'template_redirect', function() {
    $host = sanitize_text_field( $_SERVER['HTTP_HOST'] ?? '' );
    if ( strpos( $host, 'www.' ) === 0 ) {
        $no_www = substr( $host, 4 );
        wp_safe_redirect(
            str_replace( $host, $no_www, home_url( $_SERVER['REQUEST_URI'] ) ),
            301
        );
        exit;
    }
} );
```

---

## 12. Ví dụ thực tế: Shared Content Plugin

```php
<?php
/**
 * Plugin: Hiển thị bài viết từ site chính trên các sub-sites.
 * Ví dụ: Announcements, News, Policies hiển thị trên tất cả sites.
 */

class Network_Shared_Content {

    private const MAIN_BLOG_ID = 1;
    private const CACHE_KEY    = 'network_shared_content';
    private const CACHE_GROUP  = 'my-plugin';

    public static function register(): void {
        add_shortcode( 'network_announcements', array( self::class, 'render_announcements' ) );
        add_action( 'widgets_init', array( self::class, 'register_widget' ) );

        // Xóa cache khi main site publish/update post
        add_action( 'save_post', array( self::class, 'invalidate_cache' ), 10, 2 );
    }

    /**
     * Shortcode: [network_announcements count="5" category="thong-bao"]
     */
    public static function render_announcements( $atts ): string {
        $atts = shortcode_atts( array(
            'count'    => 5,
            'category' => '',
        ), $atts );

        $posts = self::get_shared_posts( (int) $atts['count'], $atts['category'] );

        if ( empty( $posts ) ) {
            return '<p>Không có thông báo mới.</p>';
        }

        $html = '<div class="network-announcements">';
        $html .= '<h3>Thông Báo Từ Hệ Thống</h3>';
        $html .= '<ul>';

        foreach ( $posts as $post ) {
            $html .= sprintf(
                '<li><a href="%s" target="_blank">%s</a> <span class="date">(%s)</span></li>',
                esc_url( $post['permalink'] ),
                esc_html( $post['title'] ),
                esc_html( $post['date'] )
            );
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Lấy bài viết từ main site với caching.
     */
    private static function get_shared_posts( int $count, string $category ): array {
        $cache_key = self::CACHE_KEY . '_' . md5( $count . $category );
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

        if ( false !== $cached ) {
            return $cached;
        }

        switch_to_blog( self::MAIN_BLOG_ID );

        try {
            $args = array(
                'numberposts' => $count,
                'post_type'   => 'post',
                'post_status' => 'publish',
            );

            if ( ! empty( $category ) ) {
                $args['category_name'] = sanitize_title( $category );
            }

            $posts  = get_posts( $args );
            $result = array();

            foreach ( $posts as $post ) {
                $result[] = array(
                    'title'     => get_the_title( $post ),
                    'permalink' => get_permalink( $post ),
                    'date'      => get_the_date( 'd/m/Y', $post ),
                    'excerpt'   => get_the_excerpt( $post ),
                );
            }

            // Cache 1 giờ
            wp_cache_set( $cache_key, $result, self::CACHE_GROUP, HOUR_IN_SECONDS );

            return $result;
        } finally {
            restore_current_blog();
        }
    }

    /**
     * Xóa cache khi publish/update bài trên main site.
     */
    public static function invalidate_cache( int $post_id, WP_Post $post ): void {
        if ( get_current_blog_id() !== self::MAIN_BLOG_ID ) {
            return;
        }
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Xóa tất cả cache có prefix
        wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
    }

    public static function register_widget(): void {
        register_widget( 'Network_Announcements_Widget' );
    }
}

Network_Shared_Content::register();

/**
 * Widget hiển thị announcements.
 */
class Network_Announcements_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'network_announcements',
            'Network Announcements',
            array( 'description' => 'Hiển thị thông báo từ site chính.' )
        );
    }

    public function widget( $args, $instance ): void {
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $instance['title'] ?? 'Thông Báo' ) . $args['after_title'];
        echo do_shortcode( sprintf(
            '[network_announcements count="%d"]',
            absint( $instance['count'] ?? 5 )
        ) );
        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title = $instance['title'] ?? 'Thông Báo';
        $count = $instance['count'] ?? 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Tiêu đề:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">Số bài:</label>
            <input type="number" class="tiny-text"
                   id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                   value="<?php echo absint( $count ); ?>" min="1" max="20" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ): array {
        return array(
            'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
            'count' => absint( $new_instance['count'] ?? 5 ),
        );
    }
}
```

---

## 13. Ví dụ thực tế: Network Dashboard Widget

```php
<?php
/**
 * Dashboard widget trong Network Admin hiển thị thống kê toàn network.
 */

class Network_Stats_Dashboard {

    public static function register(): void {
        add_action( 'wp_network_dashboard_setup', array( self::class, 'add_dashboard_widget' ) );
    }

    public static function add_dashboard_widget(): void {
        wp_add_dashboard_widget(
            'network_stats_widget',
            'Network Statistics',
            array( self::class, 'render_widget' )
        );
    }

    public static function render_widget(): void {
        $stats = self::get_network_stats();

        echo '<div class="network-stats">';
        echo '<style>
            .network-stats .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
            .network-stats .stat-card { background: #f0f0f1; padding: 15px; border-radius: 4px; text-align: center; }
            .network-stats .stat-number { font-size: 28px; font-weight: 700; color: #1d2327; }
            .network-stats .stat-label { font-size: 12px; color: #646970; text-transform: uppercase; }
        </style>';

        echo '<div class="stat-grid">';

        $cards = array(
            array( 'number' => $stats['total_sites'],    'label' => 'Sites' ),
            array( 'number' => $stats['total_users'],    'label' => 'Users' ),
            array( 'number' => $stats['total_posts'],    'label' => 'Posts' ),
            array( 'number' => $stats['total_pages'],    'label' => 'Pages' ),
            array( 'number' => $stats['total_comments'], 'label' => 'Comments' ),
            array( 'number' => $stats['disk_usage'],     'label' => 'Disk Usage' ),
        );

        foreach ( $cards as $card ) {
            printf(
                '<div class="stat-card"><div class="stat-number">%s</div><div class="stat-label">%s</div></div>',
                esc_html( $card['number'] ),
                esc_html( $card['label'] )
            );
        }

        echo '</div>';

        // Bảng top sites
        echo '<h4>Top Sites (by posts)</h4>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Site</th><th>Posts</th><th>Users</th></tr></thead><tbody>';

        foreach ( $stats['site_details'] as $site ) {
            printf(
                '<tr><td><a href="%s">%s</a></td><td>%d</td><td>%d</td></tr>',
                esc_url( $site['admin_url'] ),
                esc_html( $site['name'] ),
                $site['posts'],
                $site['users']
            );
        }

        echo '</tbody></table></div>';
    }

    private static function get_network_stats(): array {
        // Cache 15 phút (thống kê không cần real-time)
        $cached = get_site_transient( 'network_stats_data' );
        if ( false !== $cached ) {
            return $cached;
        }

        $sites        = get_sites( array( 'deleted' => 0, 'spam' => 0 ) );
        $total_posts  = 0;
        $total_pages  = 0;
        $total_comments = 0;
        $site_details = array();

        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );

            $post_count    = (int) wp_count_posts()->publish;
            $page_count    = (int) wp_count_posts( 'page' )->publish;
            $comment_count = (int) wp_count_comments()->approved;
            $user_count    = count_users()['total_users'];

            $total_posts    += $post_count;
            $total_pages    += $page_count;
            $total_comments += $comment_count;

            $site_details[] = array(
                'name'      => get_bloginfo( 'name' ),
                'url'       => get_site_url(),
                'admin_url' => admin_url(),
                'posts'     => $post_count,
                'pages'     => $page_count,
                'users'     => $user_count,
            );

            restore_current_blog();
        }

        // Sắp xếp sites theo số posts
        usort( $site_details, fn( $a, $b ) => $b['posts'] - $a['posts'] );

        $stats = array(
            'total_sites'    => count( $sites ),
            'total_users'    => get_user_count(),
            'total_posts'    => $total_posts,
            'total_pages'    => $total_pages,
            'total_comments' => $total_comments,
            'disk_usage'     => size_format( get_dirsize( ABSPATH . 'wp-content/uploads' ) ),
            'site_details'   => array_slice( $site_details, 0, 10 ),
        );

        set_site_transient( 'network_stats_data', $stats, 15 * MINUTE_IN_SECONDS );

        return $stats;
    }
}

Network_Stats_Dashboard::register();
```

---

## 14. So sánh với Laravel Multi-Tenancy

### Bảng so sánh

| Tính năng | WordPress Multisite | Laravel (spatie/laravel-multitenancy) |
|-----------|-------------------|--------------------------------------|
| **Cơ chế** | 1 WordPress → nhiều sites | 1 Laravel app → nhiều tenants |
| **Database** | Shared DB, mỗi site prefix riêng | Shared DB hoặc DB riêng per tenant |
| **Users** | Shared users table | Tuỳ cấu hình (shared hoặc riêng) |
| **Routing** | Subdomain/subdirectory tự động | Middleware tenant resolution |
| **Config** | wp-config.php + network options | config/multitenancy.php |
| **Switch context** | `switch_to_blog($id)` | `Tenant::find($id)->makeCurrent()` |
| **Current tenant** | `get_current_blog_id()` | `Tenant::current()` |
| **Query scope** | Tự động theo blog prefix | `BelongsToTenant` trait |
| **Plugins/Packages** | Network-activated hoặc per-site | Shared across tenants |
| **Storage** | `wp-content/uploads/sites/{id}/` | `storage/app/tenant-{id}/` |
| **Admin** | Network Admin dashboard | Custom admin hoặc Nova |
| **Setup** | Bật `MULTISITE` constant | `composer require spatie/laravel-multitenancy` |

### So sánh code

```php
<?php
// ── LARAVEL MULTI-TENANCY ──────────────────────────────────────

// config/multitenancy.php
return [
    'tenant_model' => App\Models\Tenant::class,
    'current_tenant_container_key' => 'currentTenant',
    'actions' => [
        SwitchTenantDatabaseAction::class,
        PrefixCacheAction::class,
    ],
];

// Tạo tenant
$tenant = Tenant::create(['name' => 'Acme Corp', 'domain' => 'acme.example.com']);

// Switch context
$tenant->makeCurrent();
$posts = Post::all(); // Query scoped to tenant

// Cross-tenant query
Tenant::find('acme')->execute(function () {
    $users = User::all(); // Lấy users của tenant 'acme'
});

// ── WORDPRESS MULTISITE ────────────────────────────────────────

// Tạo site
$blog_id = wpmu_create_blog('example.com', '/acme/', 'Acme Corp', $user_id);

// Switch context
switch_to_blog($blog_id);
$posts = get_posts(); // Query scoped to site
restore_current_blog();

// Cross-site query
switch_to_blog(3);
$users = get_users();
restore_current_blog();
```

---

## Tổng kết

| Chủ đề | Hàm/API quan trọng |
|--------|-------------------|
| Kiểm tra Multisite | `is_multisite()` |
| Lấy sites | `get_sites()`, `get_site()`, `get_blog_details()` |
| Switch context | `switch_to_blog()`, `restore_current_blog()` |
| Network options | `get_network_option()`, `update_network_option()` |
| Tạo site | `wpmu_create_blog()` |
| User management | `get_blogs_of_user()`, `add_user_to_blog()`, `is_user_member_of_blog()` |
| Hooks | `wp_initialize_site`, `wp_delete_site`, `network_admin_menu` |
| Capabilities | `manage_network`, `manage_network_options`, `manage_sites` |
| MU Plugins | `wp-content/mu-plugins/` (auto-loaded) |
| Super Admin | `is_super_admin()`, `grant_super_admin()` |

---

[← Quay lại: Cron & Background Jobs](./05-cron-va-background-jobs.md) | [Tiếp: Testing & CI/CD →](./07-testing-va-cicd.md)
