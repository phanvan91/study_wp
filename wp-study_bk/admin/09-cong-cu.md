# 09 - Công Cụ (Tools)

> **Source chính**: `wp-admin/tools.php`, `wp-admin/import.php`, `wp-admin/export.php`, `wp-admin/site-health.php`
> **Dành cho**: Laravel Developer muốn hiểu hệ thống Tools trong WordPress
> **Tương đương Laravel**: Artisan commands + Health Check packages + GDPR compliance

---

## Mục Lục

1. [Tổng Quan Tools](#1-tổng-quan-tools)
2. [Available Tools (tools.php)](#2-available-tools)
3. [Import](#3-import)
4. [Export](#4-export)
5. [Site Health](#5-site-health)
6. [Site Health Info (Debug)](#6-site-health-info)
7. [GDPR Tools](#7-gdpr-tools)
8. [DB: Tools Lưu Gì?](#8-db-tools-lưu-gì)
9. [Hooks Tools](#9-hooks-tools)
10. [So Sánh Laravel](#10-so-sánh-laravel)

---

## 1. Tổng Quan Tools

### URLs Trong Admin

| URL | Chức năng | Capability |
|-----|-----------|------------|
| `/wp-admin/tools.php` | Available Tools (trang chính) | `edit_posts` |
| `/wp-admin/import.php` | Import content | `import` |
| `/wp-admin/export.php` | Export content | `export` |
| `/wp-admin/site-health.php` | Site Health Status | `view_site_health_checks` |
| `/wp-admin/site-health.php?tab=debug` | Site Health Info | `view_site_health_checks` |
| `/wp-admin/export-personal-data.php` | Export Personal Data (GDPR) | `export_others_personal_data` |
| `/wp-admin/erase-personal-data.php` | Erase Personal Data (GDPR) | `erase_others_personal_data` |

### Source Files

| File | Kích thước | Vai trò |
|------|-----------|---------|
| `wp-admin/tools.php` | ~3.5KB | Trang chính Tools, hiện Categories/Tags Converter |
| `wp-admin/import.php` | ~7.6KB | Màn hình Import, liệt kê các importers có sẵn |
| `wp-admin/export.php` | ~12KB | Màn hình Export, form chọn nội dung xuất |
| `wp-admin/site-health.php` | ~11KB | Site Health status + info tabs |
| `wp-admin/site-health-info.php` | ~4KB | Redirect/include cho tab Info |
| `wp-admin/includes/class-wp-site-health.php` | ~3626 dòng | Class chính Site Health |
| `wp-admin/includes/class-wp-debug-data.php` | ~2001 dòng | Class debug info |
| `wp-admin/export-personal-data.php` | ~7.8KB | GDPR Export Personal Data |
| `wp-admin/erase-personal-data.php` | ~7.4KB | GDPR Erase Personal Data |
| `wp-admin/includes/export.php` | - | Core export function `export_wp()` |

### Menu Structure

```
Tools (tools.php)
  ├── Available Tools (tools.php)
  ├── Import (import.php)
  ├── Export (export.php)
  ├── Site Health (site-health.php)
  ├── Export Personal Data (export-personal-data.php)
  └── Erase Personal Data (erase-personal-data.php)
```

---

## 2. Available Tools

### Source & Cấu Trúc

```php
// Source: /wp-admin/tools.php

// File này khá đơn giản, chỉ hiện 2 thứ:
// 1. Categories and Tags Converter (card)
// 2. Hook tool_box cho plugins thêm tools

require_once __DIR__ . '/admin.php';

$title = __( 'Tools' );

// Hiển thị card Categories and Tags Converter
if ( current_user_can( 'import' ) ) :
    $cats = get_taxonomy( 'category' );
    $tags = get_taxonomy( 'post_tag' );
    if ( current_user_can( $cats->cap->manage_terms ) || current_user_can( $tags->cap->manage_terms ) ) :
        ?>
        <div class="card">
            <h2 class="title"><?php _e( 'Categories and Tags Converter' ); ?></h2>
            <p>
            <?php
                printf(
                    __( 'If you want to convert your categories to tags (or vice versa), use the <a href="%s">Categories and Tags Converter</a> available from the Import screen.' ),
                    'import.php'
                );
            ?>
            </p>
        </div>
        <?php
    endif;
endif;

// Hook cho plugins thêm tool boxes
do_action( 'tool_box' );
```

### Categories and Tags Converter

- Chuyển đổi qua lại giữa Categories và Tags
- Cần cài plugin WordPress Importer từ WordPress.org
- Link "Categories and Tags Converter" dẫn tới `import.php`
- Plugin thực tế: `wordpress-importer` plugin

### Thêm Custom Tool Box

```php
// Plugin có thể thêm tool vào trang Available Tools
add_action( 'tool_box', function() {
    ?>
    <div class="card">
        <h2 class="title">Công Cụ Dọn Dẹp Database</h2>
        <p>Xóa post revisions, orphaned metadata, và transients đã hết hạn.</p>
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'my_cleanup_action' ); ?>
            <input type="hidden" name="action" value="my_db_cleanup" />
            <p>
                <label>
                    <input type="checkbox" name="clean_revisions" value="1" checked />
                    Xóa post revisions
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="clean_transients" value="1" checked />
                    Xóa transients hết hạn
                </label>
            </p>
            <?php submit_button( 'Dọn Dẹp', 'secondary' ); ?>
        </form>
    </div>
    <?php
});

// Xử lý form submit
add_action( 'admin_post_my_db_cleanup', function() {
    check_admin_referer( 'my_cleanup_action' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Không có quyền.' );
    }

    global $wpdb;
    $cleaned = 0;

    if ( ! empty( $_POST['clean_revisions'] ) ) {
        $cleaned += $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );
    }

    if ( ! empty( $_POST['clean_transients'] ) ) {
        $cleaned += $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()"
        );
    }

    wp_redirect( admin_url( 'tools.php?cleaned=' . $cleaned ) );
    exit;
});
```

### Redirect Logic Trong tools.php

```php
// Source: /wp-admin/tools.php

// tools.php cũng xử lý redirect cho các URL cũ (backward compatibility)

// URL cũ: tools.php?page=export_personal_data
// → Redirect 301 tới: export-personal-data.php
if ( isset( $_GET['page'] ) ) {
    if ( 'export_personal_data' === $_GET['page'] ) {
        wp_redirect( admin_url( 'export-personal-data.php' ), 301 );
        exit;
    } elseif ( 'remove_personal_data' === $_GET['page'] ) {
        wp_redirect( admin_url( 'erase-personal-data.php' ), 301 );
        exit;
    }
}

// URL cũ: tools.php?wp-privacy-policy-guide
// → Redirect 301 tới: options-privacy.php?tab=policyguide
if ( isset( $_GET['wp-privacy-policy-guide'] ) ) {
    wp_redirect( admin_url( 'options-privacy.php?tab=policyguide' ), 301 );
    exit;
}
```

---

## 3. Import

### Source & Flow

```php
// Source: /wp-admin/import.php

define( 'WP_LOAD_IMPORTERS', true ); // Flag cho importers
require_once __DIR__ . '/admin.php';

// Kiểm tra quyền
if ( ! current_user_can( 'import' ) ) {
    wp_die( __( 'Sorry, you are not allowed to import content into this site.' ) );
}

// Lấy danh sách popular importers từ WordPress.org API
if ( current_user_can( 'install_plugins' ) ) {
    $popular_importers = wp_get_popular_importers();
} else {
    $popular_importers = array();
}

// Lấy danh sách importers đã cài
$importers = get_importers();

// Merge: nếu popular importer chưa cài → tạo dummy link tới plugin installer
foreach ( $popular_importers as $pop_importer => $pop_data ) {
    if ( isset( $importers[ $pop_importer ] ) ) {
        continue;
    }
    $importers[ $pop_data['importer-id'] ] = array(
        $pop_data['name'],
        $pop_data['description'],
        'install' => $pop_data['plugin-slug'],
    );
}
```

### Available Importers

| Importer | Mô tả | Plugin slug |
|----------|--------|-------------|
| WordPress | Import posts, pages, comments từ WXR file | `wordpress-importer` |
| Blogger | Import từ Google Blogger | `blogger-importer` |
| LiveJournal | Import từ LiveJournal | `livejournal-importer` |
| Movable Type | Import từ Movable Type / TypePad | `movabletype-importer` |
| Tumblr | Import từ Tumblr | `tumblr-importer` |
| RSS | Import từ RSS feed | `rss-importer` |
| Categories and Tags Converter | Chuyển đổi category ↔ tag | `wordpress-importer` |

### WordPress Importer (WXR)

WordPress Importer là plugin quan trọng nhất, xử lý file WXR (WordPress eXtended RSS):

```
File .xml (WXR format) chứa:
├── Authors (users)
├── Categories
├── Tags
├── Custom Taxonomies
├── Posts (tất cả post types)
│   ├── Post meta (custom fields)
│   ├── Comments
│   │   └── Comment meta
│   └── Attachments (media files)
└── Navigation Menu Items
```

Khi import:
1. Upload file WXR (.xml)
2. Map authors (gán bài viết cho user hiện có hoặc tạo mới)
3. Chọn download attachments (tải file media từ server cũ)
4. WordPress Importer tạo posts, pages, terms, comments...

### Đăng Ký Custom Importer

```php
// Source: /wp-includes/import.php

// Đăng ký importer mới
register_importer(
    'my_csv_importer',               // ID
    'CSV Importer',                   // Tên hiển thị
    'Import sản phẩm từ file CSV',    // Mô tả
    'my_csv_importer_callback'        // Callback function
);

function my_csv_importer_callback() {
    // Hiển thị UI import
    // Xử lý upload file
    // Parse CSV
    // Insert dữ liệu vào WordPress

    $step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 0;

    switch ( $step ) {
        case 0:
            // Bước 1: Upload form
            echo '<div class="wrap">';
            echo '<h2>Import Sản Phẩm Từ CSV</h2>';
            echo '<form method="post" enctype="multipart/form-data" action="admin.php?import=my_csv_importer&step=1">';
            wp_nonce_field( 'import-csv' );
            echo '<p><label>Chọn file CSV: <input type="file" name="csv_file" accept=".csv" /></label></p>';
            echo '<p class="submit"><input type="submit" class="button button-primary" value="Upload và Import" /></p>';
            echo '</form></div>';
            break;

        case 1:
            // Bước 2: Xử lý import
            check_admin_referer( 'import-csv' );

            $file = wp_import_handle_upload();
            if ( isset( $file['error'] ) ) {
                echo '<p class="error">' . esc_html( $file['error'] ) . '</p>';
                return;
            }

            $csv_file = $file['file'];
            $handle = fopen( $csv_file, 'r' );
            $imported = 0;

            // Bỏ qua header row
            fgetcsv( $handle );

            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                $post_id = wp_insert_post([
                    'post_title'   => sanitize_text_field( $row[0] ),
                    'post_content' => wp_kses_post( $row[1] ),
                    'post_status'  => 'publish',
                    'post_type'    => 'product',
                ]);

                if ( ! is_wp_error( $post_id ) ) {
                    update_post_meta( $post_id, '_price', floatval( $row[2] ) );
                    $imported++;
                }
            }

            fclose( $handle );
            wp_import_cleanup( $file['id'] );

            echo '<div class="wrap">';
            echo '<h2>Import Hoàn Tất</h2>';
            printf( '<p>Đã import thành công <strong>%d</strong> sản phẩm.</p>', $imported );
            echo '</div>';
            break;
    }
}
```

### Hooks Import

```php
// Trước khi bắt đầu import
do_action( 'import_start' );

// Sau khi import xong
do_action( 'import_end' );

// Filter danh sách importers hiển thị
// Có thể thêm/xóa importers
apply_filters( 'wp_importers', $importers );
```

---

## 4. Export

### Source & Flow

```php
// Source: /wp-admin/export.php

require_once __DIR__ . '/admin.php';

// Kiểm tra quyền
if ( ! current_user_can( 'export' ) ) {
    wp_die( __( 'Sorry, you are not allowed to export the content of this site.' ) );
}

// Load export API
require_once ABSPATH . 'wp-admin/includes/export.php';

// Khi nhấn "Download Export File"
if ( isset( $_GET['download'] ) ) {
    $args = array();

    if ( ! isset( $_GET['content'] ) || 'all' === $_GET['content'] ) {
        $args['content'] = 'all';
    } elseif ( 'posts' === $_GET['content'] ) {
        $args['content'] = 'post';

        // Filter theo category
        if ( $_GET['cat'] ) {
            $args['category'] = (int) $_GET['cat'];
        }

        // Filter theo author
        if ( $_GET['post_author'] ) {
            $args['author'] = (int) $_GET['post_author'];
        }

        // Filter theo date range
        if ( $_GET['post_start_date'] || $_GET['post_end_date'] ) {
            $args['start_date'] = $_GET['post_start_date'];
            $args['end_date']   = $_GET['post_end_date'];
        }

        // Filter theo status
        if ( $_GET['post_status'] ) {
            $args['status'] = $_GET['post_status'];
        }
    } elseif ( 'pages' === $_GET['content'] ) {
        $args['content'] = 'page';
        // Tương tự filters cho pages...
    } elseif ( 'attachment' === $_GET['content'] ) {
        $args['content'] = 'attachment';
        // Filters cho media: start_date, end_date
    } else {
        // Custom post type
        $args['content'] = sanitize_key( $_GET['content'] );
    }

    // Gọi core export function
    export_wp( $args );
    die();
}
```

### Export Options UI

Form export cho phép chọn:

```
Content to export:
  ○ All content
     (Posts, Pages, Custom Post Types, Comments, Custom Fields,
      Terms, Navigation Menus, Custom Posts)

  ○ Posts
     ├── Categories: [Dropdown chọn category]
     ├── Authors: [Dropdown chọn author]
     ├── Date range: [Start Month] → [End Month]
     └── Status: [Published/Draft/All]

  ○ Pages
     ├── Authors: [Dropdown chọn author]
     ├── Date range: [Start Month] → [End Month]
     └── Status: [Published/Draft/All]

  ○ Media
     └── Date range: [Start Month] → [End Month]

  ○ [Custom Post Type tên]
     (Mỗi CPT đăng ký sẽ hiện thêm 1 option)
```

### WXR File Format

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<!-- WordPress eXtended RSS (WXR) file -->
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/"
>
<channel>
    <title>My Website</title>
    <link>https://example.com</link>
    <description>Website description</description>
    <wp:wxr_version>1.2</wp:wxr_version>
    <wp:base_site_url>https://example.com</wp:base_site_url>
    <wp:base_blog_url>https://example.com</wp:base_blog_url>

    <!-- Authors -->
    <wp:author>
        <wp:author_id>1</wp:author_id>
        <wp:author_login>admin</wp:author_login>
        <wp:author_email>admin@example.com</wp:author_email>
        <wp:author_display_name>Admin</wp:author_display_name>
    </wp:author>

    <!-- Categories -->
    <wp:category>
        <wp:term_id>2</wp:term_id>
        <wp:category_nicename>tin-tuc</wp:category_nicename>
        <wp:category_parent></wp:category_parent>
        <wp:cat_name><![CDATA[Tin Tức]]></wp:cat_name>
    </wp:category>

    <!-- Tags -->
    <wp:tag>
        <wp:term_id>5</wp:term_id>
        <wp:tag_slug>wordpress</wp:tag_slug>
        <wp:tag_name><![CDATA[WordPress]]></wp:tag_name>
    </wp:tag>

    <!-- Posts -->
    <item>
        <title>Bài viết mẫu</title>
        <link>https://example.com/bai-viet-mau/</link>
        <pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate>
        <dc:creator>admin</dc:creator>
        <content:encoded><![CDATA[Nội dung bài viết...]]></content:encoded>
        <wp:post_id>42</wp:post_id>
        <wp:post_date>2024-01-01 10:00:00</wp:post_date>
        <wp:post_type>post</wp:post_type>
        <wp:status>publish</wp:status>
        <category domain="category" nicename="tin-tuc"><![CDATA[Tin Tức]]></category>
        <category domain="post_tag" nicename="wordpress"><![CDATA[WordPress]]></category>

        <!-- Custom Fields -->
        <wp:postmeta>
            <wp:meta_key>_custom_field</wp:meta_key>
            <wp:meta_value>Custom value</wp:meta_value>
        </wp:postmeta>

        <!-- Comments -->
        <wp:comment>
            <wp:comment_id>10</wp:comment_id>
            <wp:comment_author>Nguyen Van A</wp:comment_author>
            <wp:comment_author_email>a@example.com</wp:comment_author_email>
            <wp:comment_content><![CDATA[Bình luận hay!]]></wp:comment_content>
            <wp:comment_approved>1</wp:comment_approved>
            <wp:comment_date>2024-01-02 15:30:00</wp:comment_date>
        </wp:comment>
    </item>
</channel>
</rss>
```

### Core Export Function

```php
// Source: /wp-admin/includes/export.php

function export_wp( $args = array() ) {
    // Default args
    $defaults = array(
        'content'    => 'all',     // all, post, page, attachment, hoặc CPT slug
        'author'     => false,     // User ID
        'category'   => false,     // Category ID
        'start_date' => false,     // Y-m format
        'end_date'   => false,     // Y-m format
        'status'     => false,     // publish, draft, etc.
    );
    $args = wp_parse_args( $args, $defaults );

    // Set headers cho download
    header( 'Content-Description: File Transfer' );
    header( 'Content-Disposition: attachment; filename=' . $sitename . '.WordPress.' . date( 'Y-m-d' ) . '.xml' );
    header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

    // Generate XML
    // Query posts, terms, nav menus theo args
    // Output XML format WXR
}
```

### Hooks Export

```php
// Thêm filter options vào Export form
add_action( 'export_filters', function() {
    ?>
    <p>
        <label>
            <input type="radio" name="content" value="product" />
            Sản phẩm
        </label>
    </p>
    <ul id="product-filters" class="export-filters">
        <li>
            <label>Danh mục sản phẩm:
                <?php wp_dropdown_categories([
                    'taxonomy' => 'product_cat',
                    'name'     => 'product_cat',
                    'show_option_all' => 'Tất cả',
                ]); ?>
            </label>
        </li>
    </ul>
    <?php
});

// Filter nội dung export
add_filter( 'export_args', function( $args ) {
    // Modify export args
    return $args;
});

// Action khi export chạy
do_action( 'export_wp', $args );
```

---

## 5. Site Health

### Tổng Quan

Từ WordPress 5.1, Site Health cung cấp diagnostic tools để kiểm tra tình trạng website.

```php
// Source: /wp-admin/site-health.php

require_once __DIR__ . '/admin.php';

// Kiểm tra quyền
if ( ! current_user_can( 'view_site_health_checks' ) ) {
    wp_die( __( 'Sorry, you are not allowed to access site health information.' ), '', 403 );
}

// Load class
if ( ! class_exists( 'WP_Site_Health' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
}

// 2 tabs điều hướng
$tabs = array(
    ''      => _x( 'Status', 'Site Health' ),
    'debug' => _x( 'Info', 'Site Health' ),
);

// Filter cho phép plugins thêm tabs
$tabs = apply_filters( 'site_health_navigation_tabs', $tabs );

// Lấy instance
$health_check_site_status = WP_Site_Health::get_instance();
```

### Tab Status - Health Checks

Site Health chạy các bài test và hiển thị kết quả:

```
🟢 Good      - Mọi thứ ổn
🟡 Recommended - Nên cải thiện
🔴 Critical   - Cần xử lý ngay
```

#### Direct Tests (chạy ngay khi load trang)

| Test | Loại | Kiểm tra |
|------|------|----------|
| `wordpress_version` | critical | WordPress có phiên bản mới nhất? |
| `plugin_version` | critical | Plugins có cần update? |
| `theme_version` | critical | Themes có cần update? |
| `php_version` | critical | PHP version có được hỗ trợ? |
| `php_extensions` | critical | Có đủ PHP extensions? |
| `sql_server` | critical | MySQL/MariaDB version? |
| `utf8mb4_support` | recommended | DB có hỗ trợ utf8mb4? |
| `https_status` | critical | Site đã dùng HTTPS? |
| `ssl_support` | critical | SSL certificate hợp lệ? |
| `scheduled_events` | recommended | WP-Cron có chạy đúng? |
| `http_requests` | recommended | Có block HTTP requests không? |
| `rest_availability` | recommended | REST API có hoạt động? |
| `debug_enabled` | recommended | WP_DEBUG có đang bật? |
| `file_uploads` | critical | Có thể upload files? |
| `plugin_theme_auto_updates` | recommended | Auto-updates có bật? |
| `persistent_object_cache` | recommended | Có dùng object cache? (từ WP 6.1) |

#### Async Tests (chạy bằng AJAX sau khi trang load)

```php
// Các test này chạy async vì cần request ra ngoài, có thể chậm
// JavaScript gọi từng test qua admin-ajax.php

// Tests async mặc định:
'dotorg_communication'  → Kết nối được wordpress.org không?
'background_updates'    → Background updates có hoạt động?
'loopback_requests'     → Loopback requests (site gọi lại chính mình) có OK?
'https_status'          → HTTPS check chi tiết
'authorization_header'  → Authorization header có bị server chặn?
'page_cache'            → Có page cache không? (từ WP 6.1)
```

### WP_Site_Health Class

```php
// Source: /wp-admin/includes/class-wp-site-health.php
// ~3626 dòng, class rất lớn!

class WP_Site_Health {
    private static $instance = null;

    // Singleton pattern
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Mỗi test trả về array format chuẩn:
    // [
    //     'label'       => 'Tên hiển thị kết quả',
    //     'status'      => 'good' | 'recommended' | 'critical',
    //     'badge'       => [
    //         'label' => 'Category Label',
    //         'color' => 'blue' | 'green' | 'red' | 'orange' | 'purple' | 'gray',
    //     ],
    //     'description' => '<p>Mô tả chi tiết HTML</p>',
    //     'actions'     => '<a href="#">Link hành động</a>',
    //     'test'        => 'test_identifier',
    // ]
}
```

### Custom Health Check

```php
// Thêm custom test
add_filter( 'site_status_tests', function( $tests ) {

    // === Test Direct (chạy ngay) ===
    $tests['direct']['my_api_check'] = [
        'label' => 'Kiểm tra kết nối API',
        'test'  => 'my_api_health_check',  // Tên function callback
    ];

    // === Test Async (chạy bằng AJAX) ===
    $tests['async']['my_async_check'] = [
        'label'             => 'Kiểm tra external service',
        'test'              => 'my_async_health_check',  // REST endpoint hoặc AJAX action
        'has_rest'          => true,                      // Dùng REST API
        'async_direct_test' => 'my_async_direct_test',   // Hoặc function callback
    ];

    return $tests;
});

// Implement direct test
function my_api_health_check() {
    $api_key = get_option( 'my_plugin_api_key' );

    if ( empty( $api_key ) ) {
        return [
            'label'       => 'API Key chưa được cấu hình',
            'status'      => 'critical',
            'badge'       => [
                'label' => 'My Plugin',
                'color' => 'red',
            ],
            'description' => '<p>Plugin cần API key để hoạt động. Vui lòng cấu hình trong Settings.</p>',
            'actions'     => sprintf(
                '<a href="%s">Cấu hình API Key</a>',
                admin_url( 'options-general.php?page=my-plugin' )
            ),
            'test'        => 'my_api_check',
        ];
    }

    // Kiểm tra API có hoạt động
    $response = wp_remote_get( 'https://api.example.com/status', [
        'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
        'timeout' => 5,
    ] );

    if ( is_wp_error( $response ) ) {
        return [
            'label'       => 'Không kết nối được API',
            'status'      => 'critical',
            'badge'       => [
                'label' => 'My Plugin',
                'color' => 'red',
            ],
            'description' => '<p>Lỗi: ' . esc_html( $response->get_error_message() ) . '</p>',
            'actions'     => '',
            'test'        => 'my_api_check',
        ];
    }

    $code = wp_remote_retrieve_response_code( $response );

    if ( 200 === $code ) {
        return [
            'label'       => 'API kết nối thành công',
            'status'      => 'good',
            'badge'       => [
                'label' => 'My Plugin',
                'color' => 'green',
            ],
            'description' => '<p>Kết nối API hoạt động bình thường.</p>',
            'actions'     => '',
            'test'        => 'my_api_check',
        ];
    }

    return [
        'label'       => 'API trả về lỗi (HTTP ' . $code . ')',
        'status'      => 'recommended',
        'badge'       => [
            'label' => 'My Plugin',
            'color' => 'orange',
        ],
        'description' => '<p>API hoạt động nhưng trả về mã lỗi. Kiểm tra API key.</p>',
        'actions'     => '',
        'test'        => 'my_api_check',
    ];
}
```

### Health Score

```php
// WordPress tính điểm sức khỏe tổng thể:
// - Nếu có bất kỳ test critical nào → "Should be improved"
// - Nếu có tests recommended → "Should be improved" hoặc "Good"
// - Nếu tất cả good → "Great"

// Điểm hiển thị dạng vòng tròn (circle) trên trang Status
// Màu: xanh (good), cam (should improve), đỏ (critical issues)
```

---

## 6. Site Health Info

### Source

```php
// Source: /wp-admin/site-health.php (tab=debug)
// Class: /wp-admin/includes/class-wp-debug-data.php (~2001 dòng)

// Tab Info hiển thị chi tiết về cấu hình website
// Có nút "Copy site info to clipboard" để dán khi xin support
```

### Sections Debug Info

| Section | Nội dung |
|---------|----------|
| **WordPress** | Version, site URL, home URL, multisite, permalinks, HTTPS, user count, timezone, language |
| **Directories and Sizes** | WordPress root, wp-content, uploads, themes, plugins, database size, total size |
| **Active Theme** | Name, version, author, parent theme, features supported |
| **Active Plugins** | List tất cả plugins active: name, version, author, auto-update status |
| **Inactive Plugins** | Plugins đã cài nhưng chưa active |
| **Must-Use Plugins** | MU-plugins (wp-content/mu-plugins/) |
| **Drop-ins** | Drop-in files (advanced-cache.php, object-cache.php, etc.) |
| **Media Handling** | Active editor (GD/Imagick), supported formats, max upload size |
| **Server** | PHP version, PHP SAPI, PHP max input variables, cURL version, SUHOSIN, Imagick, OpenSSL, is your server behind a proxy |
| **Database** | Extension (mysqli), server version, client version, database user, database host, database name, table prefix, DB charset, DB collation |
| **WordPress Constants** | WP_HOME, WP_SITEURL, WP_DEBUG, WP_CACHE, DISABLE_WP_CRON, WP_MEMORY_LIMIT, WP_MAX_MEMORY_LIMIT, DISALLOW_FILE_EDIT, DISALLOW_FILE_MODS |
| **File System Permissions** | Writable status cho: wp-content, uploads, plugins, themes |

### Thêm Custom Debug Section

```php
add_filter( 'debug_information', function( $debug_info ) {
    $debug_info['my-plugin'] = [
        'label'       => 'My Plugin Info',
        'description' => 'Thông tin debug cho plugin của tôi.',
        'fields'      => [
            'version' => [
                'label' => 'Plugin Version',
                'value' => '2.1.0',
            ],
            'api_status' => [
                'label' => 'API Connection',
                'value' => 'Connected',
            ],
            'cache_driver' => [
                'label' => 'Cache Driver',
                'value' => 'Redis',
            ],
            'items_synced' => [
                'label'   => 'Items Synced',
                'value'   => 1500,
                'private' => true, // Ẩn khi copy to clipboard
            ],
            'last_sync' => [
                'label' => 'Last Sync',
                'value' => date( 'Y-m-d H:i:s', get_option( 'my_plugin_last_sync', 0 ) ),
            ],
        ],
    ];

    return $debug_info;
});
```

### Copy to Clipboard Format

Khi nhấn "Copy site info to clipboard", output dạng text thuần:

```
### wp-core ###

version: 6.7
site_language: vi
user_language: vi
timezone: Asia/Ho_Chi_Minh
permalink: /%postname%/
https_status: true
multisite: false
user_count: 15

### wp-active-theme ###

name: Twenty Twenty-Four (flavor flavor flavor)
version: 1.3
author: the WordPress team

### wp-plugins-active (5) ###

WooCommerce: version: 9.0.0, author: Automattic
```

---

## 7. GDPR Tools

### Tổng Quan

Từ WordPress 4.9.6, WordPress bổ sung các công cụ tuân thủ GDPR (General Data Protection Regulation) của EU.

### Export Personal Data

```php
// Source: /wp-admin/export-personal-data.php

// Capability: export_others_personal_data
if ( ! current_user_can( 'export_others_personal_data' ) ) {
    wp_die( __( 'Sorry, you are not allowed to export personal data on this site.' ) );
}
```

**Flow:**

```
1. Admin nhập email user → tạo request
2. WordPress gửi email xác nhận cho user
3. User click link xác nhận trong email
4. Admin quay lại trang → nhấn "Email Data" hoặc "Download Personal Data"
5. WordPress thu thập data từ core + plugins
6. Tạo file ZIP chứa HTML export
7. Gửi email cho user với link download (hoặc admin download trực tiếp)
```

**Dữ liệu export mặc định:**

```
- User profile: email, username, display name, nickname, first/last name, bio
- Community Events Location: IP address
- Comments: author, email, IP, user agent, date, content, URL
- Media: URLs file đã upload
- Session Tokens: login info, IP, expiration, user agent
```

### Erase Personal Data

```php
// Source: /wp-admin/erase-personal-data.php

// Capability: erase_others_personal_data
if ( ! current_user_can( 'erase_others_personal_data' ) ) {
    wp_die( __( 'Sorry, you are not allowed to erase personal data on this site.' ) );
}
```

**Flow:**

```
1. Admin nhập email user → tạo request
2. WordPress gửi email xác nhận cho user
3. User click link xác nhận
4. Admin quay lại trang → nhấn "Erase Personal Data"
5. WordPress gọi erasers từ core + plugins
6. Xóa/anonymize dữ liệu cá nhân
7. Gửi email thông báo user đã xóa xong
```

**Dữ liệu erase mặc định:**

```
- Comments: anonymize (đổi author thành "Anonymous", xóa email, IP, user agent)
- User data: KHÔNG xóa user account, chỉ xóa personal data
```

### Đăng Ký Custom Exporter

```php
// Plugin phải đăng ký exporter để data của plugin cũng được export

add_filter( 'wp_privacy_personal_data_exporters', function( $exporters ) {
    $exporters['my-plugin-orders'] = [
        'exporter_friendly_name' => 'Đơn Hàng (My Plugin)',
        'callback'               => 'my_plugin_orders_exporter',
    ];
    return $exporters;
});

function my_plugin_orders_exporter( $email_address, $page = 1 ) {
    $per_page = 100;
    $export_items = [];

    // Tìm user theo email
    $user = get_user_by( 'email', $email_address );
    if ( ! $user ) {
        return [
            'data' => [],
            'done' => true,
        ];
    }

    // Query orders của user
    $orders = get_posts([
        'post_type'   => 'shop_order',
        'author'      => $user->ID,
        'numberposts' => $per_page,
        'offset'      => ( $page - 1 ) * $per_page,
    ]);

    foreach ( $orders as $order ) {
        $export_items[] = [
            'group_id'          => 'orders',
            'group_label'       => 'Đơn Hàng',
            'group_description' => 'Danh sách đơn hàng của bạn.',
            'item_id'           => 'order-' . $order->ID,
            'data'              => [
                [
                    'name'  => 'Mã đơn hàng',
                    'value' => '#' . $order->ID,
                ],
                [
                    'name'  => 'Ngày đặt',
                    'value' => $order->post_date,
                ],
                [
                    'name'  => 'Trạng thái',
                    'value' => $order->post_status,
                ],
                [
                    'name'  => 'Tổng tiền',
                    'value' => get_post_meta( $order->ID, '_order_total', true ) . ' VND',
                ],
            ],
        ];
    }

    // Kiểm tra còn data không (pagination)
    $done = count( $orders ) < $per_page;

    return [
        'data' => $export_items,
        'done' => $done,
    ];
}
```

### Đăng Ký Custom Eraser

```php
add_filter( 'wp_privacy_personal_data_erasers', function( $erasers ) {
    $erasers['my-plugin-orders'] = [
        'eraser_friendly_name' => 'Đơn Hàng (My Plugin)',
        'callback'             => 'my_plugin_orders_eraser',
    ];
    return $erasers;
});

function my_plugin_orders_eraser( $email_address, $page = 1 ) {
    $user = get_user_by( 'email', $email_address );

    if ( ! $user ) {
        return [
            'items_removed'  => 0,
            'items_retained' => 0,
            'messages'       => [],
            'done'           => true,
        ];
    }

    $per_page = 100;
    $items_removed  = 0;
    $items_retained = 0;
    $messages       = [];

    $orders = get_posts([
        'post_type'   => 'shop_order',
        'author'      => $user->ID,
        'numberposts' => $per_page,
        'offset'      => ( $page - 1 ) * $per_page,
    ]);

    foreach ( $orders as $order ) {
        $order_status = $order->post_status;

        // Đơn hàng đã hoàn thành > 1 năm → xóa
        if ( 'wc-completed' === $order_status ) {
            $order_date = strtotime( $order->post_date );
            if ( $order_date < strtotime( '-1 year' ) ) {
                // Anonymize thay vì xóa (giữ cho báo cáo)
                wp_update_post([
                    'ID'          => $order->ID,
                    'post_author' => 0,
                ]);
                delete_post_meta( $order->ID, '_billing_email' );
                delete_post_meta( $order->ID, '_billing_phone' );
                delete_post_meta( $order->ID, '_billing_address_1' );
                $items_removed++;
            } else {
                $items_retained++;
                $messages[] = sprintf(
                    'Đơn hàng #%d được giữ lại (chưa quá 1 năm).',
                    $order->ID
                );
            }
        } else {
            // Đơn hàng chưa hoàn thành → giữ lại
            $items_retained++;
        }
    }

    $done = count( $orders ) < $per_page;

    return [
        'items_removed'  => $items_removed,
        'items_retained' => $items_retained,
        'messages'       => $messages,
        'done'           => $done,
    ];
}
```

### Privacy Requests Trong DB

```php
// Privacy requests lưu dưới dạng custom post type 'user_request'
// Trong bảng wp_posts:

// post_type  = 'user_request'
// post_name  = action type: 'export_personal_data' hoặc 'remove_personal_data'
// post_title = email address
// post_status:
//   - 'request-pending'    → Đang chờ xác nhận
//   - 'request-confirmed'  → User đã xác nhận
//   - 'request-failed'     → Thất bại
//   - 'request-completed'  → Hoàn thành

// Post meta:
// _wp_user_request_type           → 'export_personal_data' hoặc 'remove_personal_data'
// _wp_user_request_confirmed_timestamp → Timestamp xác nhận
// _wp_user_request_completed_timestamp → Timestamp hoàn thành
```

---

## 8. DB: Tools Lưu Gì?

### Tóm Tắt Lưu Trữ

| Tính năng | Bảng | Chi tiết |
|-----------|------|----------|
| Export | File tạm | Tạo file XML (.wxr) tạm → download → xóa |
| Import | File tạm + posts/meta | Upload file → parse → insert vào wp_posts, wp_postmeta, wp_terms... |
| Site Health | wp_options | `health-check-site-status-result` (kết quả check cuối) |
| Privacy Requests | wp_posts | post_type = `user_request` |
| Privacy Export Files | File tạm | ZIP file trong uploads, có expiry |
| Site Health Transients | wp_options | `_transient_health-check-site-status-result` |

### Options Liên Quan

```php
// Site Health lưu kết quả test cuối cùng
get_option( 'health-check-site-status-result' );
// → Serialized array kết quả test
// Dùng để hiển thị badge notification trên menu "Site Health"
// Ví dụ: "1 critical issue" badge

// Transient cho async tests
get_transient( 'health-check-site-status-result' );
```

---

## 9. Hooks Tools

### Available Tools

```php
// Thêm tool box vào trang Available Tools
do_action( 'tool_box' );
```

### Import / Export

```php
// Import events
do_action( 'import_start' );
do_action( 'import_end' );

// Export
do_action( 'export_wp', array $args );
add_action( 'export_filters', 'my_export_filters' );   // Thêm filter UI
apply_filters( 'export_args', $args );                  // Modify export args
```

### Site Health

```php
// Thêm custom tests
apply_filters( 'site_status_tests', $tests );

// Thêm debug info sections
apply_filters( 'debug_information', $debug_info );

// Thêm tabs navigation
apply_filters( 'site_health_navigation_tabs', $tabs );

// Kết quả test (async test result)
apply_filters( 'site_status_test_result', $result );

// Filter WordPress cập nhật badge notification
// Site Health lưu kết quả để hiện badge trên menu
apply_filters( 'site_status_test_result', $result );
```

### GDPR / Privacy

```php
// Đăng ký exporters
apply_filters( 'wp_privacy_personal_data_exporters', $exporters );

// Đăng ký erasers
apply_filters( 'wp_privacy_personal_data_erasers', $erasers );

// Email gửi cho user khi có request
apply_filters( 'user_request_action_email_content', $email_text, $request_data );
apply_filters( 'user_request_action_email_subject', $subject, $sitename, $request_data );
apply_filters( 'user_request_action_email_headers', $headers, $sitename, $request_data );

// Khi request được xác nhận
do_action( 'user_request_action_confirmed', $request_id );

// Sau khi export personal data hoàn tất
do_action( 'wp_privacy_personal_data_export_file_created',
    $archive_pathname, $archive_url, $html_report_pathname, $request_id, $json_report_pathname );

// Sau khi erase personal data hoàn tất
do_action( 'wp_privacy_personal_data_erased', $request_id );
```

---

## 10. So Sánh Laravel

### Import / Export

| WordPress | Laravel |
|-----------|---------|
| WXR file (.xml) | CSV/Excel (Laravel Excel package) |
| WordPress Importer plugin | Custom import command / job |
| `register_importer()` | Artisan command `make:import` |
| Export form UI built-in | Tự build hoặc dùng package |
| `export_wp()` function | `Excel::download()` |
| Import/Export qua admin UI | Thường qua CLI hoặc custom admin |

### Site Health

| WordPress | Laravel |
|-----------|---------|
| Site Health built-in | Package: spatie/laravel-health |
| `site_status_tests` filter | Health::checks() |
| Async AJAX tests | Queue-based checks |
| Debug Info page | `php artisan about` (Laravel 9+) |
| `debug_information` filter | Custom info providers |

### GDPR

| WordPress | Laravel |
|-----------|---------|
| Export/Erase Personal Data built-in | Tự implement hoặc package |
| `wp_privacy_personal_data_exporters` | Tự build exporter logic |
| `wp_privacy_personal_data_erasers` | Tự build eraser logic |
| Email confirmation flow built-in | Tự build email flow |
| `user_request` post type | Custom table/model |

### Key Differences

```
1. WordPress có GDPR tools built-in, Laravel không có
   → Laravel dev phải tự implement hoặc dùng package

2. WordPress import/export dùng XML (WXR), Laravel thường dùng CSV/Excel
   → WXR chứa relationships (posts + meta + comments + terms)
   → CSV thường flat, cần nhiều files hoặc complex mapping

3. Site Health trong WordPress là visual dashboard
   → Laravel health checks thường chạy trong CLI hoặc monitoring tools

4. WordPress export chạy synchronous (download ngay)
   → Laravel thường dùng queue cho export lớn

5. WordPress tools có UI admin built-in
   → Laravel dev cần tự build admin UI hoặc dùng packages (Filament, Nova)
```

---

## Tổng Kết

### Files Quan Trọng

```
/wp-admin/tools.php                          → Trang Available Tools chính
/wp-admin/import.php                         → Màn hình Import
/wp-admin/export.php                         → Màn hình Export
/wp-admin/includes/export.php                → Core function export_wp()
/wp-admin/site-health.php                    → Site Health (Status + Info tabs)
/wp-admin/includes/class-wp-site-health.php  → Class WP_Site_Health (~3626 dòng)
/wp-admin/includes/class-wp-debug-data.php   → Class WP_Debug_Data (~2001 dòng)
/wp-admin/export-personal-data.php           → GDPR Export
/wp-admin/erase-personal-data.php            → GDPR Erase
/wp-includes/import.php                      → register_importer(), get_importers()
```

### Khi Nào Dùng Gì

```
Cần migrate nội dung từ site khác?
  → Import (WordPress Importer plugin + WXR file)

Cần backup nội dung?
  → Export (tạo WXR file)

Cần kiểm tra server/config?
  → Site Health Status (tests) + Info (debug)

Cần tuân thủ GDPR?
  → Export Personal Data + Erase Personal Data
  → Đăng ký exporters/erasers trong plugin

Cần thêm tool custom?
  → Hook 'tool_box' hoặc tạo admin page riêng
```
