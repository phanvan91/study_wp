# 01 - Tổng Quan WordPress Admin

> **Source chính**: `wp-admin/admin.php` (421 dòng, 16KB)
> **URL**: `/wp-admin/`
> **Laravel tương đương**: Admin Panel (Nova, Filament, Backpack)

---

## Mục Lục

1. [WordPress Admin là gì?](#1-wordpress-admin-là-gì)
2. [Admin Bootstrap Flow](#2-admin-bootstrap-flow)
3. [Admin Menu System](#3-admin-menu-system)
4. [Admin Hooks Quan Trọng](#4-admin-hooks-quan-trọng)
5. [WP_Screen Class](#5-wp_screen-class)
6. [Admin Notices](#6-admin-notices)
7. [Admin AJAX & admin-post.php](#7-admin-ajax--admin-postphp)
8. [Setup Cấu Hình Đầu Tiên](#8-setup-cấu-hình-đầu-tiên)
9. [So sánh với Laravel](#9-so-sánh-với-laravel)

---

## 1. WordPress Admin là gì?

WordPress Admin là hệ thống quản trị nội dung **built-in** của WordPress. Khi bạn cài đặt WordPress, admin panel đã sẵn sàng ngay lập tức - không cần cài thêm bất kỳ package nào.

### URL truy cập

```
https://your-site.com/wp-admin/           → Dashboard (redirect tới index.php)
https://your-site.com/wp-admin/index.php  → Dashboard trực tiếp
https://your-site.com/wp-login.php        → Trang đăng nhập
```

### So sánh với Laravel

Trong Laravel, để có admin panel bạn cần:

```php
// Laravel - Cần cài package bên ngoài
composer require laravel/nova        // ~$99/site/year
composer require filament/filament   // Miễn phí
composer require backpack/crud       // Freemium

// Sau đó cấu hình routes, middleware, resources...
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Tự viết hoặc dùng package
});
```

Trong WordPress, admin panel đã có sẵn:

```php
// WordPress - Đã built-in sẵn, không cần cài gì thêm
// Chỉ cần truy cập /wp-admin/ là xong

// Muốn thêm trang mới vào admin:
add_action('admin_menu', function() {
    add_menu_page('Tiêu đề trang', 'Menu Label', 'manage_options', 'my-page', 'my_callback');
});
```

### Cấu trúc thư mục Admin

```
wp-admin/
├── admin.php              ← Bootstrap chính (16KB, 421 dòng)
├── admin-header.php       ← HTML header (12KB, 325 dòng)
├── admin-footer.php       ← HTML footer (4KB, 119 dòng)
├── admin-ajax.php         ← AJAX endpoint
├── admin-post.php         ← POST handler (85 dòng)
├── menu.php               ← Menu definitions (20KB, 423 dòng)
├── index.php              ← Dashboard page
├── includes/              ← Tất cả admin API functions
│   ├── admin.php          ← Load tất cả admin APIs (102 dòng)
│   └── ... (50+ files)
├── css/                   ← Admin stylesheets
├── js/                    ← Admin JavaScript
└── images/                ← Admin images
```

---

## 2. Admin Bootstrap Flow

Đây là phần quan trọng nhất để hiểu WordPress Admin hoạt động như thế nào. Mỗi khi bạn truy cập bất kỳ trang admin nào, luồng xử lý sau sẽ chạy.

### Luồng tổng quan

```
Trình duyệt → GET /wp-admin/index.php
  │
  ▼
wp-admin/index.php
  │
  ├── require wp-admin/admin.php          ← BOOTSTRAP CHÍNH
  │     │
  │     ├── define('WP_ADMIN', true)      ← Đánh dấu đang trong admin
  │     ├── define('WP_BLOG_ADMIN', true) ← Đánh dấu blog admin (không phải network)
  │     │
  │     ├── require wp-load.php           ← Load toàn bộ WordPress core
  │     │     ├── wp-config.php           ← Database config
  │     │     ├── wp-settings.php         ← Core setup
  │     │     │     ├── Load WPINC files
  │     │     │     ├── Database connection
  │     │     │     ├── Load plugins
  │     │     │     ├── do_action('init')
  │     │     │     └── do_action('wp_loaded')
  │     │     └── WordPress sẵn sàng
  │     │
  │     ├── nocache_headers()             ← Ngăn cache trang admin
  │     │
  │     ├── Check DB upgrade              ← Kiểm tra cần nâng cấp DB không
  │     │
  │     ├── require includes/admin.php    ← LOAD TẤT CẢ ADMIN APIs
  │     │     ├── includes/admin-filters.php
  │     │     ├── includes/bookmark.php
  │     │     ├── includes/comment.php
  │     │     ├── includes/file.php
  │     │     ├── includes/image.php
  │     │     ├── includes/media.php
  │     │     ├── includes/import.php
  │     │     ├── includes/misc.php
  │     │     ├── includes/class-wp-privacy-policy-content.php
  │     │     ├── includes/options.php
  │     │     ├── includes/plugin.php
  │     │     ├── includes/post.php
  │     │     ├── includes/class-wp-screen.php
  │     │     ├── includes/screen.php
  │     │     ├── includes/taxonomy.php
  │     │     ├── includes/template.php
  │     │     ├── includes/class-wp-list-table.php
  │     │     ├── includes/class-wp-list-table-compat.php
  │     │     ├── includes/list-table.php
  │     │     ├── includes/theme.php
  │     │     ├── includes/privacy-tools.php
  │     │     ├── includes/class-wp-privacy-requests-table.php
  │     │     ├── includes/class-wp-privacy-data-export-requests-list-table.php
  │     │     ├── includes/class-wp-privacy-data-removal-requests-list-table.php
  │     │     ├── includes/user.php
  │     │     ├── includes/class-wp-site-icon.php
  │     │     ├── includes/update.php
  │     │     └── includes/deprecated.php
  │     │
  │     ├── auth_redirect()               ← KIỂM TRA ĐĂNG NHẬP
  │     │     └── Chưa login → redirect tới wp-login.php
  │     │
  │     ├── set_screen_options()           ← Thiết lập screen options
  │     │
  │     ├── wp_enqueue_script('common')   ← Enqueue script chung
  │     │
  │     ├── require menu.php              ← BUILD ADMIN MENU
  │     │     ├── Đăng ký $menu array
  │     │     ├── Đăng ký $submenu array
  │     │     └── Đăng ký post type menus
  │     │
  │     ├── do_action('admin_init')        ← HOOK ADMIN_INIT
  │     │
  │     ├── Xử lý plugin pages
  │     ├── Set current screen
  │     └── do_action('load-{$pagenow}')  ← HOOK LOAD PAGE
  │
  ├── Business logic của trang            ← Code riêng của index.php
  │     ├── require includes/dashboard.php
  │     ├── wp_dashboard_setup()
  │     └── wp_enqueue_script('dashboard')
  │
  ├── require admin-header.php            ← HTML HEADER
  │     ├── <html>, <head>
  │     ├── Admin page title
  │     ├── do_action('admin_enqueue_scripts')  ← Enqueue CSS/JS
  │     ├── wp_print_styles()
  │     ├── wp_print_head_scripts()
  │     ├── do_action('admin_head')        ← HOOK HEAD
  │     ├── </head>, <body>
  │     ├── Admin Bar (Toolbar)
  │     ├── Admin Menu (Sidebar)
  │     └── Mở content area
  │
  ├── Page content (HTML output)           ← Nội dung trang
  │
  └── require admin-footer.php             ← HTML FOOTER
        ├── Footer text
        ├── do_action('admin_footer')       ← HOOK FOOTER
        ├── wp_print_footer_scripts()
        ├── do_action('admin_print_footer_scripts')
        └── </body>, </html>
```

### Chi tiết file `wp-admin/admin.php`

**Source**: `wp-admin/admin.php` - 421 dòng, 16KB

Đây là file bootstrap chính, được `require` bởi hầu hết mọi trang admin.

```php
// wp-admin/admin.php (trích đoạn quan trọng)

// Bước 1: Đánh dấu đang trong admin
if ( ! defined( 'WP_ADMIN' ) ) {
    define( 'WP_ADMIN', true );
}
if ( ! defined( 'WP_NETWORK_ADMIN' ) ) {
    define( 'WP_NETWORK_ADMIN', false );
}
if ( ! defined( 'WP_USER_ADMIN' ) ) {
    define( 'WP_USER_ADMIN', false );
}
if ( ! WP_NETWORK_ADMIN && ! WP_USER_ADMIN ) {
    define( 'WP_BLOG_ADMIN', true );
}

// Bước 2: Load WordPress core
require_once dirname( __DIR__ ) . '/wp-load.php';

// Bước 3: Ngăn cache
nocache_headers();

// Bước 4: Kiểm tra DB upgrade
if ( get_option( 'db_upgraded' ) ) {
    flush_rewrite_rules();
    update_option( 'db_upgraded', false, true );
    do_action( 'after_db_upgrade' );
}

// Bước 5: Load tất cả admin APIs
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Bước 6: Kiểm tra authentication
auth_redirect();

// Bước 7: Schedule cleanup tasks
if ( ! wp_next_scheduled( 'wp_scheduled_delete' ) && ! wp_installing() ) {
    wp_schedule_event( time(), 'daily', 'wp_scheduled_delete' );
}

// Bước 8: Screen options
set_screen_options();

// Bước 9: Enqueue common scripts
wp_enqueue_script( 'common' );

// Bước 10: Global variables
global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;

// Bước 11: Load admin menu
if ( WP_NETWORK_ADMIN ) {
    require ABSPATH . 'wp-admin/network/menu.php';
} elseif ( WP_USER_ADMIN ) {
    require ABSPATH . 'wp-admin/user/menu.php';
} else {
    require ABSPATH . 'wp-admin/menu.php';  // ← Menu chính
}

// Bước 12: Raise memory limit cho admin
if ( current_user_can( 'manage_options' ) ) {
    wp_raise_memory_limit( 'admin' );
}

// Bước 13: Fire admin_init hook
do_action( 'admin_init' );
```

### So sánh với Laravel Bootstrap

```php
// Laravel bootstrap flow (tương đương):
// public/index.php
//   → bootstrap/app.php
//     → Kernel::handle()
//       → Middleware Pipeline (auth, etc.)
//         → Route dispatch
//           → Controller method
//             → View rendering

// WordPress admin bootstrap flow:
// wp-admin/index.php
//   → wp-admin/admin.php
//     → wp-load.php (= bootstrap/app.php)
//     → includes/admin.php (= load service providers)
//     → auth_redirect() (= auth middleware)
//     → menu.php (= routes/web.php)
//     → admin_init hook (= middleware 'after' phase)
//   → admin-header.php (= layout header)
//   → Page content (= view)
//   → admin-footer.php (= layout footer)
```

### Chi tiết file `wp-admin/includes/admin.php`

**Source**: `wp-admin/includes/admin.php` - 102 dòng

File này load tất cả admin API files. Trong Laravel, việc này tương đương với việc register tất cả Service Providers.

```php
// wp-admin/includes/admin.php (toàn bộ nội dung chính)

/** WordPress Administration Hooks */
require_once ABSPATH . 'wp-admin/includes/admin-filters.php';

/** WordPress Bookmark Administration API */
require_once ABSPATH . 'wp-admin/includes/bookmark.php';

/** WordPress Comment Administration API */
require_once ABSPATH . 'wp-admin/includes/comment.php';

/** WordPress Administration File API */
require_once ABSPATH . 'wp-admin/includes/file.php';

/** WordPress Image Administration API */
require_once ABSPATH . 'wp-admin/includes/image.php';

/** WordPress Media Administration API */
require_once ABSPATH . 'wp-admin/includes/media.php';

/** WordPress Import Administration API */
require_once ABSPATH . 'wp-admin/includes/import.php';

/** WordPress Misc Administration API */
require_once ABSPATH . 'wp-admin/includes/misc.php';

/** WordPress Privacy Policy Content */
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';

/** WordPress Options Administration API */
require_once ABSPATH . 'wp-admin/includes/options.php';

/** WordPress Plugin Administration API */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/** WordPress Post Administration API */
require_once ABSPATH . 'wp-admin/includes/post.php';

/** WordPress Administration Screen API */
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';

/** WordPress Taxonomy Administration API */
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

/** WordPress Template Administration API */
require_once ABSPATH . 'wp-admin/includes/template.php';

/** WordPress List Table Administration API */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
require_once ABSPATH . 'wp-admin/includes/list-table.php';

/** WordPress Theme Administration API */
require_once ABSPATH . 'wp-admin/includes/theme.php';

/** WordPress Privacy Functions */
require_once ABSPATH . 'wp-admin/includes/privacy-tools.php';

/** Privacy List Table classes */
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-requests-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-export-requests-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-removal-requests-list-table.php';

/** WordPress User Administration API */
require_once ABSPATH . 'wp-admin/includes/user.php';

/** WordPress Site Icon API */
require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';

/** WordPress Update Administration API */
require_once ABSPATH . 'wp-admin/includes/update.php';

/** WordPress Deprecated Administration API */
require_once ABSPATH . 'wp-admin/includes/deprecated.php';

/** WordPress Multisite support API */
if ( is_multisite() ) {
    require_once ABSPATH . 'wp-admin/includes/ms-admin-filters.php';
    require_once ABSPATH . 'wp-admin/includes/ms.php';
    require_once ABSPATH . 'wp-admin/includes/ms-deprecated.php';
}
```

### Chi tiết file `wp-admin/admin-header.php`

**Source**: `wp-admin/admin-header.php` - 325 dòng, 12KB

File này render phần HTML header của mọi trang admin: `<html>`, `<head>`, admin bar, sidebar menu.

```php
// wp-admin/admin-header.php (trích đoạn quan trọng)

// Set Content-Type header
header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );

// Global variables cho header
global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow,
    $update_title, $total_update_count, $parent_file, $typenow;

// Set current screen nếu chưa có
if ( empty( $current_screen ) ) {
    set_current_screen();
}

// Tạo admin page title
get_admin_page_title();
$title = strip_tags( $title );

// Output HTML
?>
<!DOCTYPE html>
<html <?php echo get_language_attributes(); ?>>
<head>
<meta charset="<?php echo get_option('blog_charset'); ?>" />
<title><?php echo $admin_title; ?></title>
<?php
// Enqueue scripts hook - QUAN TRỌNG: dùng hook này để thêm CSS/JS
do_action( 'admin_enqueue_scripts', $hook_suffix );

// Print styles và scripts
do_action( "admin_print_styles-{$hook_suffix}" );
do_action( 'admin_print_styles' );
do_action( "admin_print_scripts-{$hook_suffix}" );
do_action( 'admin_print_scripts' );

// Admin head hook
do_action( "admin_head-{$hook_suffix}" );
do_action( 'admin_head' );
?>
</head>
<body class="<?php echo $admin_body_class; ?>">
<?php
// Admin bar
_wp_admin_bar_init();

// Sidebar menu
require_once ABSPATH . 'wp-admin/menu-header.php';
// → Render toàn bộ sidebar menu
?>
```

### Chi tiết file `wp-admin/admin-footer.php`

**Source**: `wp-admin/admin-footer.php` - 119 dòng, 4KB

```php
// wp-admin/admin-footer.php (trích đoạn)

// Footer actions
do_action( 'in_admin_footer' );

// "Thank you for creating with WordPress" text
echo apply_filters( 'admin_footer_text', $text );

// WordPress version
echo apply_filters( 'update_footer', '' );

// Admin footer hook
do_action( "admin_footer-{$hook_suffix}" );
do_action( 'admin_footer' );

// Print footer scripts
do_action( 'admin_print_footer_scripts' );
do_action( "admin_print_footer_scripts-{$hook_suffix}" );

</body>
</html>
```

---

## 3. Admin Menu System

### Source file

**Source**: `wp-admin/menu.php` - 423 dòng, 20KB

### Cấu trúc dữ liệu Menu

WordPress admin menu được lưu trong 2 global arrays:

```php
// $menu - Menu items cấp 1 (top-level)
// Cấu trúc mỗi phần tử:
$menu[$position] = array(
    0 => 'Menu item name',       // Tên hiển thị
    1 => 'capability',           // Quyền cần có để thấy
    2 => 'menu-slug.php',        // URL file (hoặc slug)
    3 => 'Page title',           // Tiêu đề trang
    4 => 'CSS classes',          // CSS classes
    5 => 'menu-id',              // HTML ID
    6 => 'dashicons-icon',       // Icon (Dashicons class)
);

// $submenu - Menu items cấp 2 (sub-menu)
$submenu['parent-slug.php'][$position] = array(
    0 => 'Sub item name',       // Tên hiển thị
    1 => 'capability',          // Quyền cần có
    2 => 'sub-slug.php',        // URL hoặc slug
);
```

### Built-in Menu Items và Position

Đây là danh sách menu mặc định WordPress đăng ký trong `wp-admin/menu.php`:

```php
// Position 2: Dashboard
$menu[2] = array(
    __('Dashboard'), 'read', 'index.php', '',
    'menu-top menu-top-first menu-icon-dashboard',
    'menu-dashboard', 'dashicons-dashboard'
);
$submenu['index.php'][0]  = array( __('Home'), 'read', 'index.php' );
$submenu['index.php'][10] = array( __('Updates'), 'update_core', 'update-core.php' );

// Position 4: Separator 1
$menu[4] = array( '', 'read', 'separator1', '', 'wp-menu-separator' );

// Position 5: Posts (được đăng ký qua vòng lặp post types)
// → edit.php
//   → All Posts: edit.php
//   → Add New: post-new.php
//   → Categories: edit-tags.php?taxonomy=category
//   → Tags: edit-tags.php?taxonomy=post_tag

// Position 10: Media
$menu[10] = array(
    __('Media'), 'upload_files', 'upload.php', '',
    'menu-top menu-icon-media', 'menu-media', 'dashicons-admin-media'
);
$submenu['upload.php'][5]  = array( __('Library'), 'upload_files', 'upload.php' );
$submenu['upload.php'][10] = array( __('Add Media File'), 'upload_files', 'media-new.php' );

// Position 15: Links (ẩn mặc định từ WP 3.5+)
$menu[15] = array(
    __('Links'), 'manage_links', 'link-manager.php', '',
    'menu-top menu-icon-links', 'menu-links', 'dashicons-admin-links'
);

// Position 20: Pages (đăng ký qua vòng lặp post types)
// → edit.php?post_type=page

// Position 25: Comments
$menu[25] = array(
    __('Comments'), 'edit_posts', 'edit-comments.php', '',
    'menu-top menu-icon-comments', 'menu-comments', 'dashicons-admin-comments'
);

// Position 59: Separator 2
$menu[59] = array( '', 'read', 'separator2', '', 'wp-menu-separator' );

// Position 60: Appearance
$menu[60] = array(
    __('Appearance'), $appearance_cap, 'themes.php', '',
    'menu-top menu-icon-appearance', 'menu-appearance', 'dashicons-admin-appearance'
);
$submenu['themes.php'][5]  = array( __('Themes'), $appearance_cap, 'themes.php' );
$submenu['themes.php'][6]  = array( __('Editor'), 'edit_theme_options', 'site-editor.php' );
$submenu['themes.php'][7]  = array( __('Customize'), 'customize', $customize_url );
$submenu['themes.php'][10] = array( __('Menus'), 'edit_theme_options', 'nav-menus.php' );

// Position 65: Plugins
$menu[65] = array(
    __('Plugins'), 'activate_plugins', 'plugins.php', '',
    'menu-top menu-icon-plugins', 'menu-plugins', 'dashicons-admin-plugins'
);
$submenu['plugins.php'][5]  = array( __('Installed Plugins'), 'activate_plugins', 'plugins.php' );
$submenu['plugins.php'][10] = array( __('Add Plugin'), 'install_plugins', 'plugin-install.php' );
$submenu['plugins.php'][15] = array( __('Plugin File Editor'), 'edit_plugins', 'plugin-editor.php' );

// Position 70: Users
$menu[70] = array(
    __('Users'), 'list_users', 'users.php', '',
    'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users'
);
$submenu['users.php'][5]  = array( __('All Users'), 'list_users', 'users.php' );
$submenu['users.php'][10] = array( __('Add New User'), 'create_users', 'user-new.php' );
$submenu['users.php'][15] = array( __('Profile'), 'read', 'profile.php' );

// Position 75: Tools
$menu[75] = array(
    __('Tools'), 'edit_posts', 'tools.php', '',
    'menu-top menu-icon-tools', 'menu-tools', 'dashicons-admin-tools'
);
$submenu['tools.php'][5]  = array( __('Available Tools'), 'edit_posts', 'tools.php' );
$submenu['tools.php'][10] = array( __('Import'), 'import', 'import.php' );
$submenu['tools.php'][15] = array( __('Export'), 'export', 'export.php' );
$submenu['tools.php'][25] = array( __('Site Health'), 'view_site_health_checks', 'site-health.php' );

// Position 80: Settings
$menu[80] = array(
    __('Settings'), 'manage_options', 'options-general.php', '',
    'menu-top menu-icon-settings', 'menu-settings', 'dashicons-admin-settings'
);
$submenu['options-general.php'][10] = array( __('General'), 'manage_options', 'options-general.php' );
$submenu['options-general.php'][15] = array( __('Writing'), 'manage_options', 'options-writing.php' );
$submenu['options-general.php'][20] = array( __('Reading'), 'manage_options', 'options-reading.php' );
$submenu['options-general.php'][25] = array( __('Discussion'), 'manage_options', 'options-discussion.php' );
$submenu['options-general.php'][30] = array( __('Media'), 'manage_options', 'options-media.php' );
$submenu['options-general.php'][40] = array( __('Permalinks'), 'manage_options', 'options-permalink.php' );
$submenu['options-general.php'][45] = array( __('Privacy'), 'manage_options', 'options-privacy.php' );

// Position 99: Separator cuối
$menu[99] = array( '', 'read', 'separator-last', '', 'wp-menu-separator' );
```

### Thêm Menu Tùy Chỉnh

#### Thêm top-level menu

```php
// Trong plugin hoặc functions.php
add_action('admin_menu', function() {
    add_menu_page(
        'Quản Lý Sản Phẩm',           // Page title (hiện trên <title>)
        'Sản Phẩm',                    // Menu title (hiện trên sidebar)
        'manage_options',               // Capability cần có
        'my-products',                  // Menu slug (unique)
        'my_products_page_callback',    // Callback function render nội dung
        'dashicons-cart',               // Icon (Dashicons class)
        30                              // Position (giữa Media 10 và Pages 20)
    );
});

function my_products_page_callback() {
    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    echo '<div class="wrap">';
    echo '<h1>Quản Lý Sản Phẩm</h1>';
    echo '<p>Nội dung trang quản lý sản phẩm ở đây.</p>';
    echo '</div>';
}
```

**So sánh Laravel**: Tương đương khi bạn định nghĩa route và controller trong Filament:

```php
// Laravel Filament
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 3;
}
```

#### Thêm submenu

```php
add_action('admin_menu', function() {
    // Menu cha
    add_menu_page(
        'Cửa Hàng', 'Cửa Hàng', 'manage_options',
        'my-shop', 'my_shop_dashboard', 'dashicons-store', 30
    );

    // Submenu 1: Dashboard (mặc định trùng menu cha)
    add_submenu_page(
        'my-shop',                   // Parent slug
        'Bảng Điều Khiển',           // Page title
        'Bảng Điều Khiển',           // Menu title
        'manage_options',            // Capability
        'my-shop',                   // Menu slug (trùng parent = default)
        'my_shop_dashboard'          // Callback
    );

    // Submenu 2: Sản phẩm
    add_submenu_page(
        'my-shop', 'Danh Sách Sản Phẩm', 'Sản Phẩm',
        'manage_options', 'my-shop-products', 'my_shop_products'
    );

    // Submenu 3: Đơn hàng
    add_submenu_page(
        'my-shop', 'Đơn Hàng', 'Đơn Hàng',
        'manage_options', 'my-shop-orders', 'my_shop_orders'
    );

    // Submenu 4: Cài đặt
    add_submenu_page(
        'my-shop', 'Cài Đặt Cửa Hàng', 'Cài Đặt',
        'manage_options', 'my-shop-settings', 'my_shop_settings'
    );
});
```

#### Thêm submenu vào menu có sẵn của WordPress

```php
add_action('admin_menu', function() {
    // Thêm vào Settings
    add_options_page(
        'Cài Đặt Plugin',  // Page title
        'Plugin Của Tôi',   // Menu title
        'manage_options',   // Capability
        'my-plugin-settings', // Slug
        'my_plugin_settings_page' // Callback
    );

    // Thêm vào Tools
    add_management_page(
        'Công Cụ Plugin', 'Plugin Tools', 'manage_options',
        'my-plugin-tools', 'my_plugin_tools_page'
    );

    // Thêm vào Posts
    add_submenu_page(
        'edit.php', 'Import Bài Viết', 'Import',
        'edit_posts', 'my-import', 'my_import_page'
    );

    // Thêm vào Pages
    add_submenu_page(
        'edit.php?post_type=page', 'Template Pages', 'Templates',
        'edit_pages', 'my-templates', 'my_templates_page'
    );
});
```

#### Xóa/ẩn menu items

```php
add_action('admin_menu', function() {
    // Xóa menu cấp 1
    remove_menu_page('edit-comments.php');   // Xóa Comments
    remove_menu_page('link-manager.php');    // Xóa Links
    remove_menu_page('tools.php');           // Xóa Tools

    // Xóa submenu
    remove_submenu_page('themes.php', 'theme-editor.php');   // Xóa Theme Editor
    remove_submenu_page('plugins.php', 'plugin-editor.php'); // Xóa Plugin Editor
    remove_submenu_page('options-general.php', 'options-writing.php'); // Xóa Settings > Writing
}, 999); // Priority cao để chắc chắn chạy sau khi menu đã được build
```

#### Sắp xếp lại menu

```php
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', function($menu_order) {
    return array(
        'index.php',           // Dashboard
        'separator1',
        'edit.php',            // Posts
        'edit.php?post_type=page', // Pages
        'upload.php',          // Media
        'separator2',
        'themes.php',          // Appearance
        'plugins.php',         // Plugins
        'users.php',           // Users
        'tools.php',           // Tools
        'options-general.php', // Settings
    );
});
```

### Global Variables liên quan đến Menu

```php
// Được khai báo và sử dụng trong admin.php / menu.php
global $menu;                  // Array chứa tất cả top-level menu items
global $submenu;               // Array chứa tất cả submenu items
global $_wp_menu_nopriv;       // Menu items mà user hiện tại không có quyền
global $_wp_submenu_nopriv;    // Submenu items mà user hiện tại không có quyền
global $_wp_last_object_menu;  // Index của menu object cuối cùng (mặc định: 25)
global $pagenow;               // Tên file PHP hiện tại (ví dụ: 'edit.php')
global $plugin_page;           // Slug của plugin page hiện tại
global $typenow;               // Post type hiện tại
global $taxnow;                // Taxonomy hiện tại
global $hook_suffix;           // Hook suffix cho trang hiện tại
```

---

## 4. Admin Hooks Quan Trọng

### Hook 1: `admin_init`

**Khi nào chạy**: Đầu tiên trong mỗi admin request, sau khi WordPress đã load xong, trước khi render bất cứ output nào.

**Lưu ý**: Hook này cũng chạy trên `admin-ajax.php` và `admin-post.php`, không chỉ trên trang admin thường.

**Source**: `wp-admin/admin.php` dòng 176

```php
// wp-admin/admin.php, dòng 176
do_action( 'admin_init' );
```

**Ví dụ sử dụng**:

```php
// Đăng ký settings
add_action('admin_init', function() {
    register_setting('my_options_group', 'my_option_name', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    add_settings_section(
        'my_settings_section',
        'Cài Đặt Plugin',
        function() { echo '<p>Cấu hình plugin của bạn.</p>'; },
        'my-plugin-settings'
    );

    add_settings_field(
        'my_field',
        'Tên Cửa Hàng',
        function() {
            $value = get_option('my_option_name', '');
            echo '<input type="text" name="my_option_name" value="' . esc_attr($value) . '" class="regular-text">';
        },
        'my-plugin-settings',
        'my_settings_section'
    );
});

// Redirect user không có quyền
add_action('admin_init', function() {
    if (!current_user_can('manage_options') && !wp_doing_ajax()) {
        // Chỉ cho admin truy cập
        // wp_redirect(home_url());
        // exit;
    }
});
```

**Laravel tương đương**: Tương đương với middleware `boot()` hoặc Service Provider `boot()`.

### Hook 2: `admin_menu`

**Khi nào chạy**: Khi admin menu đang được xây dựng. Dùng để đăng ký menu items.

**Source**: `wp-admin/includes/menu.php` (file nội bộ xử lý after menu.php loaded)

```php
// Đăng ký menu
add_action('admin_menu', function() {
    add_menu_page(
        'Tiêu Đề Trang',
        'Menu Label',
        'manage_options',
        'my-plugin',
        'my_plugin_page',
        'dashicons-admin-generic',
        30
    );
});
```

**Priority**: Dùng priority cao (ví dụ 999) khi muốn xóa menu items vì cần chạy sau khi tất cả menu đã được đăng ký.

### Hook 3: `admin_enqueue_scripts`

**Khi nào chạy**: Khi WordPress đang enqueue scripts/styles cho admin. Đây là hook đúng để thêm CSS/JS vào admin.

**Source**: `wp-admin/admin-header.php`

```php
// wp-admin/admin-header.php
do_action( 'admin_enqueue_scripts', $hook_suffix );
```

**Ví dụ sử dụng**:

```php
add_action('admin_enqueue_scripts', function($hook_suffix) {
    // Chỉ load trên trang cụ thể
    if ($hook_suffix !== 'toplevel_page_my-plugin') {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'my-plugin-admin',                          // Handle (unique ID)
        plugins_url('css/admin.css', __FILE__),     // URL
        array(),                                     // Dependencies
        '1.0.0'                                      // Version
    );

    // Enqueue JS
    wp_enqueue_script(
        'my-plugin-admin',
        plugins_url('js/admin.js', __FILE__),
        array('jquery', 'wp-element'),               // Dependencies
        '1.0.0',
        true                                          // In footer
    );

    // Truyền data từ PHP sang JS
    wp_localize_script('my-plugin-admin', 'myPluginData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('my_plugin_nonce'),
        'strings' => array(
            'confirm' => 'Bạn có chắc chắn?',
            'success' => 'Thành công!',
        ),
    ));
});
```

**Laravel tương đương**: Tương đương `@push('styles')` và `@push('scripts')` trong Blade templates, hoặc Vite/Mix asset compilation.

### Hook 4: `admin_head`

**Khi nào chạy**: Trong `<head>` của admin HTML, sau khi scripts/styles đã enqueue.

**Source**: `wp-admin/admin-header.php`

```php
// Thêm inline CSS/JS vào <head>
add_action('admin_head', function() {
    ?>
    <style>
        /* Ẩn nút Help */
        #contextual-help-link-wrap { display: none !important; }

        /* Custom CSS cho admin */
        .my-plugin-highlight {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
        }
    </style>
    <?php
});
```

### Hook 5: `admin_footer`

**Khi nào chạy**: Trước `</body>` trong admin HTML.

**Source**: `wp-admin/admin-footer.php`

```php
add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Custom admin JS
        console.log('Admin footer loaded');
    });
    </script>
    <?php
});
```

### Hook 6: `admin_notices`

**Khi nào chạy**: Ngay sau admin header, trước nội dung trang. Dùng để hiển thị thông báo.

```php
add_action('admin_notices', function() {
    // Chỉ hiện trên trang cụ thể
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_my-plugin') {
        return;
    }

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>Cài đặt đã được lưu thành công!</p>';
    echo '</div>';
});
```

Chi tiết ở phần [Admin Notices](#6-admin-notices).

### Hook 7: `admin_bar_menu`

**Khi nào chạy**: Khi admin bar (toolbar trên cùng) đang được xây dựng.

```php
add_action('admin_bar_menu', function($wp_admin_bar) {
    $wp_admin_bar->add_node(array(
        'id'     => 'my-plugin',
        'title'  => 'Plugin Của Tôi',
        'href'   => admin_url('admin.php?page=my-plugin'),
        'parent' => false,  // Top level
    ));

    // Sub-item
    $wp_admin_bar->add_node(array(
        'id'     => 'my-plugin-settings',
        'title'  => 'Cài Đặt',
        'href'   => admin_url('admin.php?page=my-plugin-settings'),
        'parent' => 'my-plugin',
    ));
}, 100);
```

### Hook 8: `current_screen`

**Khi nào chạy**: Ngay sau khi xác định screen hiện tại (WP_Screen object).

```php
add_action('current_screen', function($screen) {
    // $screen->id    = 'edit-post', 'dashboard', 'toplevel_page_my-plugin', etc.
    // $screen->base  = 'edit', 'dashboard', 'toplevel_page_my-plugin', etc.
    // $screen->post_type = 'post', 'page', etc.

    if ($screen->id === 'edit-post') {
        // Đang ở trang danh sách bài viết
        add_filter('manage_posts_columns', 'my_custom_columns');
    }
});
```

### Hook 9: `load-{$page}`

**Khi nào chạy**: Khi load một trang admin cụ thể. `{$page}` là tên file PHP.

```php
// Chạy khi load trang edit.php (danh sách bài viết)
add_action('load-edit.php', function() {
    // Xử lý trước khi render
    // Ví dụ: export CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        // ... export logic
        exit;
    }
});

// Chạy khi load trang post-new.php (tạo bài mới)
add_action('load-post-new.php', function() {
    // Pre-fill dữ liệu
});

// Chạy khi load trang plugin custom
add_action('load-toplevel_page_my-plugin', function() {
    // Setup cho trang plugin
    // Thêm help tabs, screen options, etc.
});
```

### Hook 10: `admin_post_{$action}`

**Khi nào chạy**: Khi submit form POST đến `admin-post.php` với action cụ thể.

```php
// Xử lý form POST
add_action('admin_post_my_form_action', function() {
    // Kiểm tra nonce
    check_admin_referer('my_form_nonce');

    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        wp_die('Không có quyền.');
    }

    // Xử lý dữ liệu
    $name = sanitize_text_field($_POST['name']);
    update_option('my_name', $name);

    // Redirect
    wp_redirect(admin_url('admin.php?page=my-plugin&saved=1'));
    exit;
});
```

**Form HTML tương ứng**:

```html
<form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('my_form_nonce'); ?>
    <input type="hidden" name="action" value="my_form_action">
    <input type="text" name="name" value="">
    <button type="submit">Lưu</button>
</form>
```

### Hook 11: `wp_ajax_{$action}` / `wp_ajax_nopriv_{$action}`

**Khi nào chạy**: Khi có AJAX request đến `admin-ajax.php`.

- `wp_ajax_{$action}` - Chỉ cho user đã đăng nhập
- `wp_ajax_nopriv_{$action}` - Cho user chưa đăng nhập

```php
// AJAX handler cho user đã đăng nhập
add_action('wp_ajax_my_ajax_action', function() {
    // Kiểm tra nonce
    check_ajax_referer('my_nonce', 'nonce');

    // Kiểm tra quyền
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Không có quyền', 403);
    }

    // Xử lý
    $post_id = intval($_POST['post_id']);
    $result = update_post_meta($post_id, '_my_field', sanitize_text_field($_POST['value']));

    if ($result) {
        wp_send_json_success(array('message' => 'Đã lưu!'));
    } else {
        wp_send_json_error('Lỗi khi lưu');
    }
});
```

Chi tiết ở phần [Admin AJAX](#7-admin-ajax--admin-postphp).

### Bảng tổng hợp hooks và thứ tự chạy

```
Request đến admin page
  │
  ├── 1. init                        (wp-settings.php)
  ├── 2. admin_init                  (admin.php:176)
  ├── 3. admin_menu                  (menu.php xong → admin.php)
  ├── 4. current_screen              (admin.php → set_current_screen)
  ├── 5. load-{$pagenow}            (admin.php cuối)
  ├── 6. admin_enqueue_scripts      (admin-header.php)
  ├── 7. admin_print_styles         (admin-header.php)
  ├── 8. admin_print_scripts        (admin-header.php)
  ├── 9. admin_head                  (admin-header.php)
  ├── 10. admin_notices             (admin-header.php)
  ├── 11. [Page content rendered]
  ├── 12. in_admin_footer           (admin-footer.php)
  ├── 13. admin_footer              (admin-footer.php)
  └── 14. admin_print_footer_scripts (admin-footer.php)
```

---

## 5. WP_Screen Class

### Source

**Source**: `wp-admin/includes/class-wp-screen.php` - 1359 dòng

### Giới thiệu

`WP_Screen` là class đại diện cho "screen" (trang) hiện tại trong admin. Mỗi trang admin có một WP_Screen object riêng, chứa thông tin về trang đó.

### Lấy screen hiện tại

```php
// Lấy WP_Screen object hiện tại
$screen = get_current_screen();

// Phải gọi sau hook 'current_screen' hoặc 'admin_init'
// KHÔNG thể gọi quá sớm (trước khi screen được set)
```

### Properties quan trọng

```php
$screen = get_current_screen();

$screen->id;          // Unique ID: 'edit-post', 'dashboard', 'toplevel_page_my-plugin'
$screen->base;        // Base type: 'edit', 'post', 'dashboard', 'toplevel_page_my-plugin'
$screen->action;      // Action: 'add' cho *-new.php, '' cho các trang khác
$screen->post_type;   // Post type: 'post', 'page', '' (nếu không liên quan post type)
$screen->taxonomy;    // Taxonomy: 'category', 'post_tag', '' (nếu không liên quan)
$screen->parent_file; // Parent menu file: 'edit.php', 'options-general.php'
$screen->parent_base; // Parent base: 'edit', 'options-general'
$screen->is_block_editor; // Boolean: có dùng block editor không
```

### Screen IDs phổ biến

```php
// Một số screen ID thường gặp:
'dashboard'                    // Dashboard
'edit-post'                    // All Posts (danh sách)
'post'                         // Edit Post (sửa bài)
'edit-page'                    // All Pages (danh sách)
'page'                         // Edit Page (sửa trang)
'upload'                       // Media Library
'edit-comments'                // Comments
'themes'                       // Appearance > Themes
'plugins'                      // Plugins
'users'                        // Users
'tools_page_site-health'       // Site Health
'options-general'              // Settings > General
'options-reading'              // Settings > Reading
'options-permalink'            // Settings > Permalinks
'toplevel_page_my-plugin'      // Plugin page (top-level)
'my-plugin_page_my-sub-page'   // Plugin page (sub-menu)
```

### Screen Options

Screen Options cho phép user tùy chỉnh hiển thị của trang admin.

```php
// Thêm Screen Option: số items per page
add_action('load-edit.php', function() {
    $screen = get_current_screen();
    $screen->add_option('per_page', array(
        'label'   => 'Số bài viết mỗi trang',
        'default' => 20,
        'option'  => 'edit_post_per_page',
    ));
});

// Lưu giá trị screen option
add_filter('set-screen-option', function($status, $option, $value) {
    if ($option === 'my_items_per_page') {
        return $value;
    }
    return $status;
}, 10, 3);
```

### Help Tabs

```php
add_action('load-toplevel_page_my-plugin', function() {
    $screen = get_current_screen();

    $screen->add_help_tab(array(
        'id'      => 'my-help-overview',
        'title'   => 'Tổng Quan',
        'content' => '<p>Đây là plugin quản lý sản phẩm. Bạn có thể...</p>',
    ));

    $screen->add_help_tab(array(
        'id'      => 'my-help-usage',
        'title'   => 'Hướng Dẫn Sử Dụng',
        'callback' => function() {
            echo '<p>1. Nhấn "Thêm Mới" để tạo sản phẩm...</p>';
            echo '<p>2. Nhập thông tin sản phẩm...</p>';
        },
    ));

    // Help sidebar
    $screen->set_help_sidebar(
        '<p><strong>Tài liệu:</strong></p>' .
        '<p><a href="https://example.com/docs" target="_blank">Đọc tài liệu</a></p>' .
        '<p><a href="https://example.com/support" target="_blank">Hỗ trợ</a></p>'
    );
});
```

### DB: Screen Options lưu ở đâu?

Screen options được lưu trong bảng `wp_usermeta`:

```
Bảng: wp_usermeta
┌──────────┬─────────┬────────────────────────────────┬────────────┐
│ umeta_id │ user_id │ meta_key                       │ meta_value │
├──────────┼─────────┼────────────────────────────────┼────────────┤
│ 1        │ 1       │ edit_post_per_page             │ 20         │
│ 2        │ 1       │ upload_per_page                │ 40         │
│ 3        │ 1       │ edit_comments_per_page          │ 20         │
│ 4        │ 1       │ manageedit-postcolumnshidden   │ a:2:{...}  │
│ 5        │ 1       │ meta-box-order_post            │ a:4:{...}  │
│ 6        │ 1       │ screen_layout_post             │ 2          │
│ 7        │ 1       │ closedpostboxes_post           │ a:1:{...}  │
└──────────┴─────────┴────────────────────────────────┴────────────┘
```

---

## 6. Admin Notices

### Giới thiệu

Admin Notices là hệ thống hiển thị thông báo trên trang admin. Chúng xuất hiện ngay dưới tiêu đề trang, trước nội dung chính.

### Các loại notice

```php
// Notice Thành Công (màu xanh lá)
add_action('admin_notices', function() {
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>Cài đặt đã được lưu thành công!</p>';
    echo '</div>';
});

// Notice Lỗi (màu đỏ)
add_action('admin_notices', function() {
    echo '<div class="notice notice-error">';
    echo '<p>Lỗi: Không thể kết nối đến API.</p>';
    echo '</div>';
});

// Notice Cảnh Báo (màu vàng)
add_action('admin_notices', function() {
    echo '<div class="notice notice-warning is-dismissible">';
    echo '<p>Cảnh báo: License sắp hết hạn trong 7 ngày.</p>';
    echo '</div>';
});

// Notice Thông Tin (màu xanh dương)
add_action('admin_notices', function() {
    echo '<div class="notice notice-info">';
    echo '<p>Phiên bản mới 2.0 đã sẵn sàng. <a href="#">Cập nhật ngay</a></p>';
    echo '</div>';
});
```

### CSS Classes

```
notice              → Base class (bắt buộc)
notice-success      → Viền xanh lá (green)
notice-error        → Viền đỏ (red)
notice-warning      → Viền vàng (yellow/orange)
notice-info         → Viền xanh dương (blue)
is-dismissible      → Có nút X để đóng (WordPress tự thêm JS)
```

### Notice có điều kiện

```php
add_action('admin_notices', function() {
    // Chỉ hiện trên trang cụ thể
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_my-plugin') {
        return;
    }

    // Chỉ hiện khi vừa lưu settings
    if (!isset($_GET['settings-updated'])) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>Cài đặt plugin đã được lưu!</p>';
    echo '</div>';
});
```

### Notice Dismissible vĩnh viễn (lưu vào DB)

```php
// Hiện notice, cho phép ẩn vĩnh viễn
add_action('admin_notices', function() {
    // Kiểm tra xem đã ẩn chưa
    if (get_user_meta(get_current_user_id(), 'my_plugin_notice_dismissed', true)) {
        return;
    }

    echo '<div class="notice notice-info" id="my-plugin-notice">';
    echo '<p>Chào mừng bạn đến với Plugin! ';
    echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=dismiss_my_notice'), 'dismiss_notice')) . '">Ẩn thông báo này</a>';
    echo '</p></div>';
});

// Xử lý ẩn notice
add_action('admin_post_dismiss_my_notice', function() {
    check_admin_referer('dismiss_notice');
    update_user_meta(get_current_user_id(), 'my_plugin_notice_dismissed', true);
    wp_redirect(wp_get_referer());
    exit;
});
```

### So sánh Laravel

```php
// Laravel: Flash message
return redirect()->back()->with('success', 'Đã lưu thành công!');

// Trong Blade:
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

// WordPress: Admin notice
add_action('admin_notices', function() {
    if (isset($_GET['saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Đã lưu!</p></div>';
    }
});
```

---

## 7. Admin AJAX & admin-post.php

WordPress có 2 endpoint chính để xử lý requests từ admin:

### 7.1 Admin AJAX (`admin-ajax.php`)

**Source**: `wp-admin/admin-ajax.php` - Endpoint cho tất cả AJAX requests

**URL**: `https://your-site.com/wp-admin/admin-ajax.php`

#### Flow xử lý

```
JS gửi AJAX request
  → POST /wp-admin/admin-ajax.php?action=my_action
    │
    ├── define('DOING_AJAX', true)
    ├── require wp-load.php
    ├── require includes/admin.php
    ├── require includes/ajax-actions.php
    ├── do_action('admin_init')
    │
    ├── Nếu user đã đăng nhập:
    │   └── do_action('wp_ajax_my_action')
    │
    └── Nếu user chưa đăng nhập:
        └── do_action('wp_ajax_nopriv_my_action')
```

#### Ví dụ hoàn chỉnh: AJAX trong admin

**Bước 1: Đăng ký handler PHP**

```php
// Trong plugin file hoặc functions.php

// Handler cho user đã đăng nhập
add_action('wp_ajax_get_product_data', function() {
    // 1. Kiểm tra nonce
    check_ajax_referer('my_plugin_nonce', 'nonce');

    // 2. Kiểm tra quyền
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Không có quyền'), 403);
    }

    // 3. Lấy và validate input
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error(array('message' => 'ID không hợp lệ'), 400);
    }

    // 4. Xử lý logic
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product') {
        wp_send_json_error(array('message' => 'Không tìm thấy sản phẩm'), 404);
    }

    $price = get_post_meta($product_id, '_price', true);

    // 5. Trả về response
    wp_send_json_success(array(
        'id'    => $product->ID,
        'title' => $product->post_title,
        'price' => $price,
        'status' => $product->post_status,
    ));
});

// Handler cho user chưa đăng nhập (nếu cần, ví dụ: tìm kiếm trên frontend)
add_action('wp_ajax_nopriv_search_products', function() {
    $keyword = sanitize_text_field($_GET['keyword'] ?? '');
    $products = get_posts(array(
        'post_type' => 'product',
        's' => $keyword,
        'posts_per_page' => 10,
    ));

    $results = array_map(function($p) {
        return array('id' => $p->ID, 'title' => $p->post_title);
    }, $products);

    wp_send_json_success($results);
});
```

**Bước 2: Enqueue JS và truyền data**

```php
add_action('admin_enqueue_scripts', function($hook_suffix) {
    if ($hook_suffix !== 'toplevel_page_my-plugin') {
        return;
    }

    wp_enqueue_script(
        'my-plugin-admin',
        plugins_url('js/admin.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script('my-plugin-admin', 'myPlugin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('my_plugin_nonce'),
    ));
});
```

**Bước 3: JavaScript AJAX call**

```javascript
// js/admin.js

// Sử dụng jQuery
jQuery(document).ready(function($) {
    $('#load-product').on('click', function() {
        var productId = $('#product-id').val();

        $.ajax({
            url: myPlugin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_product_data',  // Tên action (sau wp_ajax_)
                nonce: myPlugin.nonce,
                product_id: productId
            },
            beforeSend: function() {
                $('#result').html('Đang tải...');
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#result').html(
                        'Tên: ' + data.title + '<br>' +
                        'Giá: ' + data.price
                    );
                } else {
                    alert('Lỗi: ' + response.data.message);
                }
            },
            error: function() {
                alert('Lỗi kết nối!');
            }
        });
    });
});

// Hoặc sử dụng Fetch API (modern)
async function loadProduct(productId) {
    const formData = new FormData();
    formData.append('action', 'get_product_data');
    formData.append('nonce', myPlugin.nonce);
    formData.append('product_id', productId);

    const response = await fetch(myPlugin.ajaxUrl, {
        method: 'POST',
        body: formData,
    });

    const result = await response.json();

    if (result.success) {
        console.log(result.data);
    } else {
        console.error(result.data.message);
    }
}
```

#### Response Helpers

```php
// Trả về JSON success
wp_send_json_success($data);
// Output: {"success":true,"data":{...}}

// Trả về JSON error
wp_send_json_error($data, $status_code);
// Output: {"success":false,"data":{...}}

// Trả về JSON tùy ý
wp_send_json($response, $status_code);

// Trả về 0 hoặc 1 (legacy)
wp_die('0');  // Error
wp_die('1');  // Success
```

### 7.2 Admin POST (`admin-post.php`)

**Source**: `wp-admin/admin-post.php` - 85 dòng

Endpoint cho form submissions (non-AJAX). Dùng khi submit form HTML thông thường.

#### Flow xử lý

```
Form submit → POST /wp-admin/admin-post.php
  │
  ├── require wp-load.php
  ├── require includes/admin.php
  ├── do_action('admin_init')
  │
  ├── Nếu user đã đăng nhập:
  │   ├── do_action('admin_post')         (không có action cụ thể)
  │   └── do_action('admin_post_{$action}') (có action cụ thể)
  │
  └── Nếu user chưa đăng nhập:
      ├── do_action('admin_post_nopriv')
      └── do_action('admin_post_nopriv_{$action}')
```

#### Ví dụ hoàn chỉnh: Form submit

```php
// 1. Trang hiển thị form
add_action('admin_menu', function() {
    add_menu_page('Liên Hệ', 'Liên Hệ', 'manage_options', 'my-contact', function() {
        ?>
        <div class="wrap">
            <h1>Quản Lý Form Liên Hệ</h1>
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_contact_settings'); ?>
                <input type="hidden" name="action" value="save_contact_settings">

                <table class="form-table">
                    <tr>
                        <th>Email nhận</th>
                        <td><input type="email" name="contact_email"
                            value="<?php echo esc_attr(get_option('contact_email', '')); ?>"
                            class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Tiêu đề email</th>
                        <td><input type="text" name="contact_subject"
                            value="<?php echo esc_attr(get_option('contact_subject', '')); ?>"
                            class="regular-text"></td>
                    </tr>
                </table>

                <?php submit_button('Lưu Cài Đặt'); ?>
            </form>
        </div>
        <?php
    });
});

// 2. Xử lý form submission
add_action('admin_post_save_contact_settings', function() {
    // Kiểm tra nonce
    check_admin_referer('save_contact_settings');

    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }

    // Lưu dữ liệu
    update_option('contact_email', sanitize_email($_POST['contact_email']));
    update_option('contact_subject', sanitize_text_field($_POST['contact_subject']));

    // Redirect về trang form với thông báo
    wp_redirect(add_query_arg(
        array('page' => 'my-contact', 'saved' => '1'),
        admin_url('admin.php')
    ));
    exit;
});
```

### So sánh với Laravel Route::post()

```php
// Laravel
Route::post('/admin/contact/save', [ContactController::class, 'save'])
    ->middleware(['auth', 'admin'])
    ->name('admin.contact.save');

class ContactController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
        ]);

        Setting::set('contact_email', $request->email);
        Setting::set('contact_subject', $request->subject);

        return redirect()->route('admin.contact')->with('success', 'Đã lưu!');
    }
}

// WordPress
add_action('admin_post_save_contact_settings', function() {
    check_admin_referer('save_contact_settings'); // CSRF protection
    if (!current_user_can('manage_options')) wp_die('No permission');

    update_option('contact_email', sanitize_email($_POST['contact_email']));
    update_option('contact_subject', sanitize_text_field($_POST['contact_subject']));

    wp_redirect(admin_url('admin.php?page=my-contact&saved=1'));
    exit;
});
```

---

## 8. Setup Cấu Hình Đầu Tiên

Khi cài đặt WordPress mới, đây là các bước cấu hình cần thực hiện:

### Bước 1: Cài đặt WordPress

```
Truy cập: https://your-site.com/wp-admin/install.php
```

WordPress sẽ yêu cầu:
- Tên site
- Username admin
- Password
- Email admin
- Search Engine Visibility (cho phép Google index không)

**Source**: `wp-admin/install.php`

**DB**: Tạo tất cả 12 bảng mặc định, insert default options vào `wp_options`, tạo admin user trong `wp_users` và `wp_usermeta`.

### Bước 2: Settings > General

```
URL: /wp-admin/options-general.php
Source: wp-admin/options-general.php
```

Cài đặt:
- **Site Title** (`blogname`) - Tên website
- **Tagline** (`blogdescription`) - Mô tả ngắn
- **WordPress Address (URL)** (`siteurl`) - URL cài đặt WordPress
- **Site Address (URL)** (`home`) - URL truy cập website
- **Administration Email** (`admin_email`) - Email admin
- **Membership** (`users_can_register`) - Cho phép đăng ký
- **Default Role** (`default_role`) - Role mặc định
- **Site Language** (`WPLANG`) - Ngôn ngữ
- **Timezone** (`timezone_string` hoặc `gmt_offset`) - Múi giờ
- **Date Format** (`date_format`) - Định dạng ngày
- **Time Format** (`time_format`) - Định dạng giờ
- **Week Starts On** (`start_of_week`) - Ngày bắt đầu tuần

```php
// Tất cả lưu vào bảng wp_options
// Lấy giá trị:
get_option('blogname');          // "My WordPress Site"
get_option('timezone_string');   // "Asia/Ho_Chi_Minh"
get_option('date_format');       // "F j, Y"
get_option('time_format');       // "g:i a"

// Cập nhật giá trị:
update_option('blogname', 'Tên Site Mới');
```

### Bước 3: Settings > Reading

```
URL: /wp-admin/options-reading.php
Source: wp-admin/options-reading.php
```

Cài đặt:
- **Your homepage displays** (`show_on_front`) - 'posts' (blog) hoặc 'page' (static page)
- **Homepage** (`page_on_front`) - ID trang làm homepage (nếu chọn static)
- **Posts page** (`page_for_posts`) - ID trang blog (nếu chọn static)
- **Blog pages show at most** (`posts_per_page`) - Số bài mỗi trang
- **Syndication feeds** (`posts_per_rss`) - Số bài trong RSS feed
- **Search engine visibility** (`blog_public`) - 0 = ẩn, 1 = hiện

```php
// Ví dụ: Set homepage là static page
update_option('show_on_front', 'page');
update_option('page_on_front', 42);    // ID trang Home
update_option('page_for_posts', 55);   // ID trang Blog
update_option('posts_per_page', 12);
```

### Bước 4: Settings > Permalinks

```
URL: /wp-admin/options-permalink.php
Source: wp-admin/options-permalink.php
```

Cấu trúc URL:
- **Plain** (`/?p=123`) - Mặc định, xấu cho SEO
- **Day and name** (`/2024/01/15/sample-post/`)
- **Month and name** (`/2024/01/sample-post/`)
- **Numeric** (`/archives/123`)
- **Post name** (`/sample-post/`) - **Khuyến nghị dùng**
- **Custom Structure** - Tùy chỉnh

```php
// Lưu trong wp_options:
get_option('permalink_structure');  // "/%postname%/"

// Rewrite rules lưu trong:
get_option('rewrite_rules');       // Array hàng nghìn rules

// Sau khi thay đổi permalink, cần flush:
flush_rewrite_rules();
```

### Bước 5: Settings > Discussion

```
URL: /wp-admin/options-discussion.php
Source: wp-admin/options-discussion.php
```

Cài đặt bình luận:
- **Default post settings** - Cho phép comment, pingback
- **Other comment settings** - Yêu cầu name/email, đóng comment sau N ngày
- **Before a comment appears** - Phải duyệt, đã có comment được duyệt trước
- **Comment Moderation** - Từ khóa moderation, spam
- **Avatars** - Hiện/ẩn avatar, nguồn avatar

```php
// Các option liên quan:
get_option('default_comment_status');  // 'open' hoặc 'closed'
get_option('require_name_email');      // 1
get_option('comment_moderation');      // 0 hoặc 1
get_option('comment_previously_approved'); // 1
get_option('show_avatars');            // 1
```

### Bước 6: Settings > Media

```
URL: /wp-admin/options-media.php
Source: wp-admin/options-media.php
```

Image sizes mặc định:
- **Thumbnail**: 150 x 150px (crop)
- **Medium**: 300 x 300px (max)
- **Large**: 1024 x 1024px (max)

```php
// Các option:
get_option('thumbnail_size_w');  // 150
get_option('thumbnail_size_h');  // 150
get_option('thumbnail_crop');    // 1 (crop chính xác)
get_option('medium_size_w');     // 300
get_option('medium_size_h');     // 300
get_option('large_size_w');      // 1024
get_option('large_size_h');      // 1024
get_option('uploads_use_yearmonth_folders'); // 1 (tổ chức theo năm/tháng)
```

### Bước 7: Tạo User và Phân Quyền

```
URL: /wp-admin/user-new.php
Source: wp-admin/user-new.php
```

WordPress có 5 roles mặc định:
1. **Administrator** - Toàn quyền
2. **Editor** - Quản lý tất cả bài viết
3. **Author** - Đăng bài viết của mình
4. **Contributor** - Viết bài nhưng không đăng được
5. **Subscriber** - Chỉ xem

```php
// Tạo user bằng code:
$user_id = wp_create_user('username', 'password', 'email@example.com');
$user = new WP_User($user_id);
$user->set_role('editor');
```

### Bước 8: Chọn Theme

```
URL: /wp-admin/themes.php
Source: wp-admin/themes.php
```

### Bước 9: Cài Plugins Cần Thiết

```
URL: /wp-admin/plugin-install.php
Source: wp-admin/plugin-install.php
```

Plugins thường cài:
- **Yoast SEO** hoặc **Rank Math** - SEO
- **WP Super Cache** hoặc **W3 Total Cache** - Cache
- **Wordfence** - Security
- **UpdraftPlus** - Backup
- **Contact Form 7** hoặc **WPForms** - Forms

### Bước 10: Site Health

```
URL: /wp-admin/site-health.php
Source: wp-admin/site-health.php
Class: WP_Site_Health (wp-admin/includes/class-wp-site-health.php)
```

Kiểm tra:
- PHP version
- MySQL version
- HTTPS status
- REST API availability
- PHP extensions
- File uploads
- Background updates

---

## 9. So sánh với Laravel

### Bảng so sánh tổng quan

| Khái niệm | Laravel | WordPress Admin |
|-----------|---------|----------------|
| **Admin URL** | `/admin` (tùy cấu hình) | `/wp-admin/` (cố định) |
| **Bootstrap** | `Kernel::handle()` → Pipeline → Controller | `admin.php` → `admin-header.php` → Page → `admin-footer.php` |
| **Authentication** | `auth` middleware | `auth_redirect()` function |
| **Authorization** | Gates & Policies | Capabilities (`current_user_can()`) |
| **CSRF Protection** | `@csrf` + `VerifyCsrfToken` middleware | `wp_nonce_field()` + `check_admin_referer()` |
| **Routing** | `Route::get/post()` in `routes/web.php` | `add_menu_page()` + hook `admin_menu` |
| **Controller** | Class-based controllers | Callback functions |
| **Views** | Blade templates (`.blade.php`) | PHP includes (`.php`) |
| **Asset Management** | Vite / Mix | `wp_enqueue_script()` / `wp_enqueue_style()` |
| **AJAX** | Axios/Fetch → Route → Controller | jQuery → `admin-ajax.php` → Hook handler |
| **Form Handling** | `Route::post()` → Controller method | `admin-post.php` → `admin_post_{action}` hook |
| **Settings Storage** | `.env` + `config/` + DB | `wp_options` table |
| **Flash Messages** | `session()->flash()` | `admin_notices` hook |
| **Pagination** | `->paginate()` | `WP_List_Table::pagination()` |
| **Data Tables** | Livewire DataTable / Laravel DataTables | `WP_List_Table` class |
| **Menu Building** | Manual / Package | `add_menu_page()` API |
| **Middleware** | Class-based, pipeline pattern | Hooks (actions/filters), sequential |
| **Service Container** | Yes, built-in DI | No, uses global functions |
| **Event System** | Events & Listeners | Actions & Filters (hooks) |
| **Database** | Eloquent ORM | `$wpdb` (raw SQL wrapper) |
| **Migration** | `php artisan migrate` | `dbDelta()` function |

### Event System: Laravel Events vs WordPress Hooks

```php
// Laravel Event
// 1. Định nghĩa Event
class PostSaved extends Event {
    public function __construct(public Post $post) {}
}

// 2. Định nghĩa Listener
class SendNotification {
    public function handle(PostSaved $event) {
        // Logic
    }
}

// 3. Đăng ký trong EventServiceProvider
protected $listen = [
    PostSaved::class => [SendNotification::class],
];

// 4. Dispatch
event(new PostSaved($post));

// ---

// WordPress Hook (tương đương)
// 1. Đăng ký handler
add_action('save_post', function($post_id, $post, $update) {
    // Logic xử lý sau khi lưu bài viết
    // Gửi notification, clear cache, etc.
}, 10, 3);

// 2. WordPress tự trigger hook khi lưu post
// Trong wp_insert_post():
do_action('save_post', $post_id, $post, $update);
```

### Middleware vs Hooks

```php
// Laravel Middleware
class CheckAdmin {
    public function handle($request, Closure $next) {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        return $next($request);  // Pipeline pattern
    }
}

// WordPress (không có pipeline, dùng hooks)
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Không có quyền');
    }
});
```

### Database: Eloquent vs $wpdb

```php
// Laravel Eloquent
$posts = Post::where('status', 'published')
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// WordPress $wpdb
global $wpdb;
$posts = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_status = %s ORDER BY post_date DESC LIMIT %d",
        'publish', 20
    )
);

// Hoặc dùng WP_Query (higher level):
$query = new WP_Query(array(
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'posts_per_page' => 20,
));
$posts = $query->posts;
```

### Tư duy chuyển đổi cho Laravel Developer

```
Laravel                          WordPress
───────────────────────          ─────────────────────
Service Provider::register()  →  Plugin activation hook
Service Provider::boot()      →  add_action('init', ...)
Middleware                    →  add_action('admin_init', ...)
Route::get()                  →  add_menu_page() / add_action('template_redirect', ...)
Controller::method()          →  Callback function
Blade @extends                →  get_header() / get_footer()
Blade @section                →  add_action('wp_head', ...) / etc.
Model::create()               →  wp_insert_post() / $wpdb->insert()
Model::find()                 →  get_post() / WP_Query
Config::get()                 →  get_option()
Cache::put()                  →  set_transient()
Event::dispatch()             →  do_action()
Event Listener                →  add_action()
Request::validate()           →  sanitize_text_field() / wp_verify_nonce()
Response::json()              →  wp_send_json_success()
```

---

## Tổng Kết

WordPress Admin là một hệ thống hoàn chỉnh, built-in sẵn, với:

1. **Bootstrap flow** rõ ràng: `admin.php` → `includes/admin.php` → `menu.php` → page content
2. **Menu system** linh hoạt: positions, capabilities, icons
3. **Hook system** mạnh mẽ: 14+ hooks chạy theo thứ tự mỗi admin request
4. **WP_Screen**: API quản lý screen options, help tabs
5. **AJAX & POST**: 2 endpoint chuyên dụng cho xử lý requests
6. **Notices**: Hệ thống thông báo 4 loại
7. **Settings**: Lưu tất cả vào `wp_options`, screen settings vào `wp_usermeta`

Điểm khác biệt lớn nhất so với Laravel: WordPress dùng **hooks** (actions/filters) thay vì **middleware pipeline**, dùng **global functions** thay vì **dependency injection**, và dùng **callback functions** thay vì **class-based controllers**.

---

*Tiếp theo: [02-dashboard.md](./02-dashboard.md) - Dashboard, Widgets, Screen Options*
