# Theme WordPress Cơ Bản - Hướng Dẫn Đầy Đủ

## Mục Lục

1. [Theme là gì, tại sao cần tự tạo theme](#1-theme-là-gì)
2. [Yêu cầu tối thiểu: style.css + index.php](#2-yêu-cầu-tối-thiểu)
3. [Style.css Header chi tiết](#3-stylecss-header-chi-tiết)
4. [Cấu trúc thư mục theme đầy đủ](#4-cấu-trúc-thư-mục-theme-đầy-đủ)
5. [functions.php - Vai trò và cách sử dụng](#5-functionsphp---vai-trò-và-cách-sử-dụng)
6. [Theme Supports](#6-theme-supports)
7. [Enqueue Styles và Scripts](#7-enqueue-styles-và-scripts)
8. [Tạo theme Hello World từ đầu](#8-tạo-theme-hello-world-từ-đầu)
9. [So sánh với Laravel](#9-so-sánh-với-laravel)
10. [Best Practices](#10-best-practices)

---

## 1. Theme là gì

### Theme là gì?

Theme trong WordPress là một tập hợp các file (PHP, CSS, JS, hình ảnh) quyết định **giao diện** và **cách hiển thị nội dung** của website. Theme không ảnh hưởng đến dữ liệu (data), chỉ ảnh hưởng đến cách dữ liệu được trình bày.

### Tại sao cần tự tạo theme?

| Lý do | Giải thích |
|-------|-----------|
| **Tùy chỉnh hoàn toàn** | Theme có sẵn (Astra, GeneratePress) bị giới hạn bởi options có sẵn |
| **Hiệu năng tốt hơn** | Theme tự viết chỉ có những gì cần thiết, không bị bloat |
| **Học sâu WordPress** | Hiểu cách WP hoạt động từ bên trong |
| **Kinh doanh** | Bán theme trên ThemeForest, WordPress.org |
| **Dự án khách hàng** | Tạo theme riêng cho từng dự án |

### So sánh với Laravel để hiểu

```
Laravel:
  resources/views/     --> Nơi chứa giao diện
  layouts/app.blade.php --> Layout chính
  components/          --> Components tái sử dụng

WordPress:
  wp-content/themes/my-theme/  --> Nơi chứa giao diện
  header.php + footer.php      --> Layout chính
  template-parts/              --> Components tái sử dụng
```

**Điểm khác biệt lớn:** Trong Laravel, bạn tự định nghĩa routes và controllers. Trong WordPress, hệ thống tự động chọn template dựa trên URL (Template Hierarchy).

---

## 2. Yêu cầu tối thiểu

Một theme WordPress chỉ cần **2 file** để hoạt động:

### style.css (bắt buộc)

```css
/*
Theme Name: My First Theme
*/
```

### index.php (bắt buộc)

```php
<!DOCTYPE html>
<html>
<head>
    <title>My Theme</title>
</head>
<body>
    <h1>Hello WordPress!</h1>
</body>
</html>
```

Chỉ cần 2 file này, bạn đã có một theme hợp lệ có thể kích hoạt trong WordPress Admin.

**Vị trí đặt file:**
```
wp-content/
  themes/
    my-first-theme/     <-- Tạo thư mục này
      style.css         <-- File 1
      index.php         <-- File 2
```

---

## 3. Style.css Header chi tiết

File `style.css` có một phần header đặc biệt (CSS comment) để WordPress nhận diện theme:

```css
/*
Theme Name:        Developer Theme
Theme URI:         https://example.com/developer-theme
Author:            Nguyen Van A
Author URI:        https://example.com
Description:       Theme WordPress tự tạo cho developer, tối ưu cho hiệu năng và SEO.
                   Hỗ trợ WooCommerce, Gutenberg, và responsive design.
Version:           1.0.0
Requires at least: 6.0
Tested up to:      6.4
Requires PHP:      8.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       developer-theme
Tags:              blog, portfolio, custom-menu, featured-images, threaded-comments,
                   translation-ready, custom-background, custom-logo
Domain Path:       /languages
*/

/* === CSS code bắt đầu từ đây === */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}
```

### Giải thích từng trường:

| Trường | Bắt buộc | Mô tả |
|--------|----------|-------|
| `Theme Name` | Có | Tên theme hiển thị trong Admin |
| `Theme URI` | Không | URL trang giới thiệu theme |
| `Author` | Không | Tên tác giả |
| `Author URI` | Không | Website tác giả |
| `Description` | Không | Mô tả ngắn về theme |
| `Version` | Không | Phiên bản hiện tại (theo semver) |
| `Requires at least` | Không | Phiên bản WP tối thiểu |
| `Tested up to` | Không | Phiên bản WP đã test |
| `Requires PHP` | Không | Phiên bản PHP tối thiểu |
| `License` | Không | Loại license (nên là GPL) |
| `Text Domain` | Không | ID cho đa ngôn ngữ (phải trùng với tên thư mục) |
| `Tags` | Không | Thẻ để tìm kiếm trên WordPress.org |
| `Domain Path` | Không | Thư mục chứa file ngôn ngữ |

### Screenshot cho theme

Tạo file `screenshot.png` trong thư mục theme:
- Kích thước khuyến nghị: **1200 x 900 pixels**
- Định dạng: PNG hoặc JPG
- File này sẽ hiển thị trong Admin > Appearance > Themes

---

## 4. Cấu trúc thư mục theme đầy đủ

```
my-theme/
|-- style.css                    # Style chính + Theme Header
|-- index.php                    # Template mặc định (fallback cuối cùng)
|-- functions.php                # Cấu hình theme (như bootstrap/app.php trong Laravel)
|-- screenshot.png               # Ảnh preview 1200x900
|
|-- # === TEMPLATE FILES CHÍNH ===
|-- front-page.php               # Trang chủ (khi Settings > Reading > Static Page)
|-- home.php                     # Trang blog posts
|-- single.php                   # Bài viết đơn lẻ
|-- page.php                     # Trang tĩnh (page)
|-- archive.php                  # Trang archive (danh sách bài viết)
|-- category.php                 # Trang danh mục
|-- tag.php                      # Trang thẻ
|-- author.php                   # Trang tác giả
|-- search.php                   # Trang kết quả tìm kiếm
|-- 404.php                      # Trang lỗi 404
|-- comments.php                 # Template bình luận
|-- attachment.php               # Trang file đính kèm
|
|-- # === TEMPLATE PARTS (Components) ===
|-- header.php                   # Phần đầu trang
|-- footer.php                   # Phần cuối trang
|-- sidebar.php                  # Thanh bên
|-- template-parts/              # Các phần template nhỏ
|   |-- content.php              # Nội dung bài viết trong loop
|   |-- content-single.php       # Nội dung bài viết trang single
|   |-- content-page.php         # Nội dung trang page
|   |-- content-search.php       # Nội dung kết quả tìm kiếm
|   |-- content-none.php         # Khi không có bài viết
|   |-- header/
|   |   |-- site-branding.php    # Logo + tên site
|   |   |-- navigation.php       # Menu chính
|   |-- footer/
|       |-- footer-widgets.php   # Widget ở footer
|       |-- site-info.php        # Copyright
|
|-- # === CUSTOM PAGE TEMPLATES ===
|-- page-templates/
|   |-- template-full-width.php  # Template trang full width
|   |-- template-sidebar-left.php
|   |-- template-landing.php     # Template landing page
|
|-- # === ASSETS ===
|-- assets/
|   |-- css/
|   |   |-- main.css             # CSS chính (compiled)
|   |   |-- editor-style.css     # Style cho Gutenberg editor
|   |-- js/
|   |   |-- main.js              # JS chính
|   |   |-- navigation.js        # JS cho mobile menu
|   |   |-- customizer.js        # JS cho theme customizer
|   |-- images/
|   |   |-- default-thumbnail.jpg
|   |-- fonts/
|       |-- custom-font.woff2
|
|-- # === INCLUDES (PHP logic riêng) ===
|-- inc/
|   |-- customizer.php           # Theme Customizer settings
|   |-- template-tags.php        # Custom template tags (helper functions)
|   |-- template-functions.php   # Functions liên quan đến template
|   |-- custom-header.php        # Custom header feature
|   |-- widgets.php              # Custom widgets
|   |-- walker-nav-menu.php      # Custom menu walker
|
|-- # === ĐA NGÔN NGỮ ===
|-- languages/
|   |-- developer-theme.pot      # Template file
|   |-- vi.po                    # Tiếng Việt
|   |-- vi.mo                    # Tiếng Việt (compiled)
|
|-- # === WOOCOMMERCE (nếu cần) ===
|-- woocommerce/
|   |-- single-product.php
|   |-- archive-product.php
|   |-- cart/
|   |-- checkout/
```

### So sánh với cấu trúc Laravel:

```
Laravel                          WordPress Theme
-------                          ---------------
resources/views/                 theme root (*.php)
resources/views/layouts/         header.php + footer.php
resources/views/components/      template-parts/
public/css, public/js            assets/css, assets/js
app/View/Components/             inc/
routes/web.php                   Template Hierarchy (tự động)
config/                          functions.php + inc/customizer.php
resources/lang/                  languages/
```

---

## 5. functions.php

`functions.php` là file **quan trọng nhất** sau index.php. Nó hoạt động như một **plugin** riêng cho theme - tự động được load khi theme active.

### Vai trò của functions.php:

1. **Đăng ký features** (menus, thumbnails, widget areas)
2. **Load CSS và JS** (enqueue scripts/styles)
3. **Thêm các hàm tiện ích** (helper functions)
4. **Hook vào WordPress** (actions và filters)
5. **Include các file khác** từ thư mục inc/

### Cấu trúc functions.php chuẩn:

```php
<?php
/**
 * Developer Theme - Functions and definitions
 *
 * @package Developer_Theme
 * @since   1.0.0
 */

// === BẢO MẬT: Không cho truy cập trực tiếp ===
// Nếu ai đó truy cập file này trực tiếp qua URL, sẽ bị dừng lại
if ( ! defined( 'ABSPATH' ) ) {
    exit; // ABSPATH là hằng số chỉ được define khi WP load
}

// === HẰNG SỐ CỦA THEME ===
// Định nghĩa các hằng số để dùng xuyên suốt theme
define( 'DEV_THEME_VERSION', '1.0.0' );
define( 'DEV_THEME_DIR', get_template_directory() );        // Đường dẫn thư mục theme
define( 'DEV_THEME_URI', get_template_directory_uri() );    // URL của theme

/**
 * === THIẾT LẬP THEME ===
 * Hook vào 'after_setup_theme' để đăng ký features
 * Hook này chạy sau khi theme được load, trước khi init
 */
function developer_theme_setup() {
    // 1. Hỗ trợ đa ngôn ngữ
    // Load file ngôn ngữ từ thư mục /languages/
    load_theme_textdomain( 'developer-theme', DEV_THEME_DIR . '/languages' );

    // 2. Tự động thêm tag <title> trong <head>
    // Không cần tự viết tag title nữa, WP sẽ tự xử lý
    add_theme_support( 'title-tag' );

    // 3. Hỗ trợ ảnh đại diện cho bài viết (Featured Image)
    add_theme_support( 'post-thumbnails' );

    // 4. Đăng ký vị trí menu
    register_nav_menus( array(
        'primary'   => __( 'Menu Chính', 'developer-theme' ),
        'footer'    => __( 'Menu Footer', 'developer-theme' ),
        'mobile'    => __( 'Menu Mobile', 'developer-theme' ),
    ) );

    // 5. Tạo HTML5 markup cho các thành phần
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // 6. Hỗ trợ Custom Logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-width'  => true,   // Cho phép chiều rộng linh hoạt
        'flex-height' => true,   // Cho phép chiều cao linh hoạt
    ) );

    // 7. Hỗ trợ Block Editor (Gutenberg)
    add_theme_support( 'align-wide' );           // Cho phép align wide và full
    add_theme_support( 'responsive-embeds' );     // Embeds responsive
    add_theme_support( 'editor-styles' );         // Custom editor styles
    add_editor_style( 'assets/css/editor-style.css' );

    // 8. Định nghĩa kích thước ảnh tùy chỉnh
    add_image_size( 'developer-featured', 1200, 630, true );  // true = crop
    add_image_size( 'developer-thumbnail', 400, 300, true );
    add_image_size( 'developer-square', 600, 600, true );

    // 9. Hỗ trợ Custom Background
    add_theme_support( 'custom-background', array(
        'default-color' => 'ffffff',
        'default-image' => '',
    ) );
}
add_action( 'after_setup_theme', 'developer_theme_setup' );

/**
 * === ĐĂNG KÝ WIDGET AREAS (Sidebars) ===
 */
function developer_theme_widgets_init() {
    // Sidebar chính (bên phải)
    register_sidebar( array(
        'name'          => __( 'Sidebar Chính', 'developer-theme' ),
        'id'            => 'sidebar-main',
        'description'   => __( 'Thêm widget vào đây để hiển thị ở sidebar.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer Widget Area 1
    register_sidebar( array(
        'name'          => __( 'Footer 1', 'developer-theme' ),
        'id'            => 'footer-1',
        'description'   => __( 'Widget area cho footer cột 1.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    // Footer Widget Area 2
    register_sidebar( array(
        'name'          => __( 'Footer 2', 'developer-theme' ),
        'id'            => 'footer-2',
        'description'   => __( 'Widget area cho footer cột 2.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'developer_theme_widgets_init' );

/**
 * === ENQUEUE STYLES VÀ SCRIPTS ===
 */
function developer_theme_scripts() {
    // CSS
    wp_enqueue_style(
        'developer-theme-style',              // Handle (ID duy nhất)
        get_stylesheet_uri(),                  // URL tới style.css gốc
        array(),                               // Dependencies (không có)
        DEV_THEME_VERSION                      // Version (để cache busting)
    );

    wp_enqueue_style(
        'developer-theme-main',
        DEV_THEME_URI . '/assets/css/main.css',
        array( 'developer-theme-style' ),      // Load sau style.css
        DEV_THEME_VERSION
    );

    // JavaScript
    wp_enqueue_script(
        'developer-theme-navigation',
        DEV_THEME_URI . '/assets/js/navigation.js',
        array(),                               // Không phụ thuộc thư viện nào
        DEV_THEME_VERSION,
        true                                   // true = load ở footer (trước </body>)
    );

    wp_enqueue_script(
        'developer-theme-main',
        DEV_THEME_URI . '/assets/js/main.js',
        array( 'jquery' ),                     // Phụ thuộc vào jQuery
        DEV_THEME_VERSION,
        true
    );

    // Truyền dữ liệu từ PHP sang JS
    wp_localize_script( 'developer-theme-main', 'devThemeData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dev_theme_nonce' ),
        'homeUrl'  => home_url( '/' ),
        'themeUrl' => DEV_THEME_URI,
        'i18n'     => array(
            'loading' => __( 'Đang tải...', 'developer-theme' ),
            'error'   => __( 'Có lỗi xảy ra!', 'developer-theme' ),
        ),
    ) );

    // Chỉ load comment-reply.js khi cần (trang single có bình luận)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_theme_scripts' );

/**
 * === INCLUDE CÁC FILE KHÁC ===
 * Tách logic ra các file riêng để code sạch hơn
 */
// Custom template tags (hàm helper cho template)
require DEV_THEME_DIR . '/inc/template-tags.php';

// Theme Customizer
require DEV_THEME_DIR . '/inc/customizer.php';

// Custom Widgets
require DEV_THEME_DIR . '/inc/widgets.php';
```

### Giải thích các hàm quan trọng:

```php
// === get_template_directory() vs get_stylesheet_directory() ===

// get_template_directory()
// --> Trả về đường dẫn TUYỆT ĐỐI trên server đến thư mục theme CHA
// Ví dụ: /var/www/html/wp-content/themes/developer-theme

// get_template_directory_uri()
// --> Trả về URL đến thư mục theme CHA
// Ví dụ: https://example.com/wp-content/themes/developer-theme

// get_stylesheet_directory()
// --> Trả về đường dẫn đến thư mục theme HIỆN TẠI (child theme nếu có)
// Nếu dùng child theme, nó trỏ đến child theme
// Nếu không dùng child theme, giống get_template_directory()

// get_stylesheet_directory_uri()
// --> Trả về URL đến thư mục theme HIỆN TẠI

// get_stylesheet_uri()
// --> Trả về URL đến file style.css của theme hiện tại
// Ví dụ: https://example.com/wp-content/themes/developer-theme/style.css
```

---

## 6. Theme Supports

`add_theme_support()` cho WordPress biết theme hỗ trợ những tính năng gì:

```php
<?php
/**
 * Tất cả các theme support có sẵn trong WordPress
 */
function developer_theme_full_supports() {

    // === CƠ BẢN ===

    // 1. Title Tag - WP tự quản lý tag <title>
    add_theme_support( 'title-tag' );

    // 2. Post Thumbnails - Ảnh đại diện cho bài viết
    add_theme_support( 'post-thumbnails' );
    // Chỉ hỗ trợ cho post type cụ thể:
    // add_theme_support( 'post-thumbnails', array( 'post', 'page', 'product' ) );

    // 3. Post Formats - Các định dạng bài viết đặc biệt
    add_theme_support( 'post-formats', array(
        'aside',    // Ghi chú ngắn
        'gallery',  // Thư viện ảnh
        'link',     // Liên kết
        'image',    // Hình ảnh
        'quote',    // Trích dẫn
        'status',   // Trạng thái ngắn
        'video',    // Video
        'audio',    // Âm thanh
        'chat',     // Hội thoại
    ) );

    // 4. HTML5 Markup - Dùng HTML5 cho các thành phần WP
    add_theme_support( 'html5', array(
        'comment-list',    // Danh sách bình luận
        'comment-form',    // Form bình luận
        'search-form',     // Form tìm kiếm
        'gallery',         // Thư viện ảnh
        'caption',         // Chú thích ảnh
        'style',           // Tag <style> không cần type attribute
        'script',          // Tag <script> không cần type attribute
        'navigation-widgets', // Widget menu dùng HTML5
    ) );

    // 5. Custom Logo
    add_theme_support( 'custom-logo', array(
        'height'               => 100,
        'width'                => 400,
        'flex-height'          => true,
        'flex-width'           => true,
        'header-text'          => array( 'site-title', 'site-description' ),
        'unlink-homepage-logo' => true,  // WP 5.5+ : Logo không link về homepage
    ) );

    // 6. Custom Header Image
    add_theme_support( 'custom-header', array(
        'default-image'      => '',
        'default-text-color' => '000000',
        'width'              => 1920,
        'height'             => 500,
        'flex-width'         => true,
        'flex-height'        => true,
        'uploads'            => true,
        'video'              => true,   // Cho phép video header
    ) );

    // 7. Custom Background
    add_theme_support( 'custom-background', array(
        'default-color'      => 'ffffff',
        'default-image'      => '',
        'default-repeat'     => 'repeat',
        'default-position-x' => 'left',
        'default-position-y' => 'top',
        'default-size'       => 'auto',
        'default-attachment' => 'scroll',
    ) );

    // 8. Automatic Feed Links - Thêm RSS feed links vào <head>
    add_theme_support( 'automatic-feed-links' );

    // === GUTENBERG / BLOCK EDITOR ===

    // 9. Wide và Full width alignment
    add_theme_support( 'align-wide' );

    // 10. Block Styles - Load style mặc định của block
    add_theme_support( 'wp-block-styles' );

    // 11. Responsive Embeds
    add_theme_support( 'responsive-embeds' );

    // 12. Editor Styles - Custom CSS cho editor
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // 13. Custom Color Palette cho editor
    add_theme_support( 'editor-color-palette', array(
        array(
            'name'  => __( 'Xanh Dương Chính', 'developer-theme' ),
            'slug'  => 'primary',
            'color' => '#0073aa',
        ),
        array(
            'name'  => __( 'Đỏ Nhấn Mạnh', 'developer-theme' ),
            'slug'  => 'accent',
            'color' => '#e74c3c',
        ),
        array(
            'name'  => __( 'Xám Nhạt', 'developer-theme' ),
            'slug'  => 'light-gray',
            'color' => '#f5f5f5',
        ),
        array(
            'name'  => __( 'Đen', 'developer-theme' ),
            'slug'  => 'dark',
            'color' => '#1a1a1a',
        ),
    ) );

    // 14. Custom Font Sizes cho editor
    add_theme_support( 'editor-font-sizes', array(
        array(
            'name' => __( 'Nhỏ', 'developer-theme' ),
            'slug' => 'small',
            'size' => 14,
        ),
        array(
            'name' => __( 'Bình thường', 'developer-theme' ),
            'slug' => 'normal',
            'size' => 16,
        ),
        array(
            'name' => __( 'Lớn', 'developer-theme' ),
            'slug' => 'large',
            'size' => 24,
        ),
        array(
            'name' => __( 'Rất Lớn', 'developer-theme' ),
            'slug' => 'huge',
            'size' => 36,
        ),
    ) );

    // 15. Tắt custom colors (chỉ cho dùng colors đã định nghĩa)
    // add_theme_support( 'disable-custom-colors' );

    // 16. Tắt custom font sizes
    // add_theme_support( 'disable-custom-font-sizes' );

    // === WOOCOMMERCE ===

    // 17. WooCommerce support
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'developer_theme_full_supports' );
```

---

## 7. Enqueue Styles và Scripts

### Tại sao phải dùng wp_enqueue thay vì viết trực tiếp?

```php
// === SAI - Không bao giờ làm thế này ===
// Viết trực tiếp trong header.php:
<link rel="stylesheet" href="style.css">
<script src="script.js"></script>

// === ĐÚNG - Luôn dùng wp_enqueue ===
// Viết trong functions.php:
wp_enqueue_style( 'my-style', get_stylesheet_uri() );
wp_enqueue_script( 'my-script', get_template_directory_uri() . '/js/script.js' );
```

**Lý do:**
1. **Tránh trùng lặp** - Nếu 2 plugin cùng load jQuery, WP chỉ load 1 lần
2. **Quản lý thứ tự** - Dependencies đảm bảo thứ tự load đúng
3. **Tối ưu hiệu năng** - Plugins cache/minify cần biết tất cả scripts
4. **Conditional loading** - Chỉ load khi cần thiết

### wp_enqueue_style() chi tiết:

```php
/**
 * wp_enqueue_style( $handle, $src, $deps, $ver, $media )
 *
 * @param string $handle  - Tên duy nhất (ID) của stylesheet
 * @param string $src     - URL tới file CSS
 * @param array  $deps    - Mảng các handle mà CSS này phụ thuộc
 * @param string $ver     - Phiên bản (để cache busting)
 * @param string $media   - Media query ('all', 'screen', 'print', '(max-width: 768px)')
 */

function developer_enqueue_styles() {
    // 1. Google Fonts
    wp_enqueue_style(
        'developer-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),    // Không phụ thuộc gì
        null        // null = không thêm version query string
    );

    // 2. CSS Framework (Bootstrap)
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
        array(),
        '5.3.0'
    );

    // 3. Theme style.css (đăng ký qua get_stylesheet_uri)
    wp_enqueue_style(
        'developer-style',
        get_stylesheet_uri(),
        array( 'bootstrap' ),         // Load SAU bootstrap
        DEV_THEME_VERSION
    );

    // 4. Main CSS (custom styles)
    wp_enqueue_style(
        'developer-main',
        get_template_directory_uri() . '/assets/css/main.css',
        array( 'developer-style' ),   // Load SAU theme style
        DEV_THEME_VERSION
    );

    // 5. Conditional: Chỉ load trên trang cụ thể
    if ( is_page( 'contact' ) ) {
        wp_enqueue_style(
            'developer-contact',
            get_template_directory_uri() . '/assets/css/contact.css',
            array( 'developer-main' ),
            DEV_THEME_VERSION
        );
    }

    // 6. Print stylesheet
    wp_enqueue_style(
        'developer-print',
        get_template_directory_uri() . '/assets/css/print.css',
        array(),
        DEV_THEME_VERSION,
        'print'                       // Chỉ áp dụng khi in
    );
}
add_action( 'wp_enqueue_scripts', 'developer_enqueue_styles' );
```

### wp_enqueue_script() chi tiết:

```php
/**
 * wp_enqueue_script( $handle, $src, $deps, $ver, $args )
 *
 * @param string       $handle - Tên duy nhất
 * @param string       $src    - URL tới file JS
 * @param array        $deps   - Dependencies
 * @param string|false $ver    - Version
 * @param bool|array   $args   - true = load ở footer, hoặc array chi tiết hơn
 */

function developer_enqueue_scripts() {
    // 1. Bootstrap JS
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        array(),              // Bootstrap JS không cần jQuery
        '5.3.0',
        true                  // Load ở footer
    );

    // 2. Navigation script
    wp_enqueue_script(
        'developer-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        array(),
        DEV_THEME_VERSION,
        true
    );

    // 3. Main script (phụ thuộc jQuery)
    wp_enqueue_script(
        'developer-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array( 'jquery', 'bootstrap' ),  // Cần jQuery và Bootstrap
        DEV_THEME_VERSION,
        true
    );

    // 4. Truyền dữ liệu từ PHP sang JavaScript
    // Tạo một object JavaScript có tên 'devTheme' với dữ liệu bên dưới
    wp_localize_script( 'developer-main', 'devTheme', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dev_nonce' ),
        'is_home'  => is_home(),
        'strings'  => array(
            'confirm_delete' => __( 'Bạn có chắc muốn xóa?', 'developer-theme' ),
            'loading'        => __( 'Đang tải...', 'developer-theme' ),
        ),
    ) );
    // Trong JS truy cập: devTheme.ajax_url, devTheme.nonce, devTheme.strings.loading

    // 5. Inline script (thêm JS trực tiếp)
    wp_add_inline_script( 'developer-main', '
        console.log("Theme loaded successfully!");
        document.documentElement.classList.remove("no-js");
        document.documentElement.classList.add("js");
    ', 'before' ); // 'before' = trước file main.js, 'after' = sau

    // 6. Chỉ load trên trang có thể
    if ( is_singular() && comments_open() ) {
        wp_enqueue_script( 'comment-reply' ); // Script WP có sẵn
    }

    // 7. WP 6.3+: Dùng strategy để load async hoặc defer
    wp_enqueue_script(
        'developer-analytics',
        get_template_directory_uri() . '/assets/js/analytics.js',
        array(),
        DEV_THEME_VERSION,
        array(
            'in_footer' => true,
            'strategy'  => 'defer',  // 'defer' hoặc 'async'
        )
    );
}
add_action( 'wp_enqueue_scripts', 'developer_enqueue_scripts' );
```

### Dequeue và Deregister (gỡ bỏ scripts/styles):

```php
/**
 * Gỡ bỏ styles/scripts không cần thiết (từ plugin, theme parent)
 */
function developer_dequeue_unnecessary() {
    // Gỡ bỏ CSS của plugin không cần
    wp_dequeue_style( 'contact-form-7' );
    wp_deregister_style( 'contact-form-7' );

    // Chỉ load CF7 CSS trên trang contact
    if ( is_page( 'contact' ) ) {
        wp_enqueue_style( 'contact-form-7' );
    }

    // Gỡ bỏ jQuery Migrate (không cần cho theme mới)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', false, array( 'jquery-core' ), null, true );
    }

    // Gỡ bỏ block library CSS nếu không dùng Gutenberg ở frontend
    // wp_dequeue_style( 'wp-block-library' );
    // wp_dequeue_style( 'wp-block-library-theme' );

    // Gỡ bỏ emoji scripts (tiết kiệm HTTP requests)
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'wp_enqueue_scripts', 'developer_dequeue_unnecessary', 100 );
// Priority 100 = chạy sau các enqueue khác để đảm bảo gỡ bỏ được
```

### Enqueue cho Admin:

```php
/**
 * Load CSS/JS trong admin area
 */
function developer_admin_scripts( $hook ) {
    // $hook cho biết đang ở trang admin nào
    // Ví dụ: 'post.php', 'post-new.php', 'edit.php', 'toplevel_page_my-plugin'

    // Chỉ load trên trang edit post
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'developer-admin-style',
        get_template_directory_uri() . '/assets/css/admin.css',
        array(),
        DEV_THEME_VERSION
    );

    wp_enqueue_script(
        'developer-admin-script',
        get_template_directory_uri() . '/assets/js/admin.js',
        array( 'jquery' ),
        DEV_THEME_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'developer_admin_scripts' );
```

---

## 8. Tạo theme Hello World từ đầu

### Bước 1: Tạo thư mục theme

```bash
# Di đến thư mục themes của WordPress
cd wp-content/themes/

# Tạo thư mục theme mới
mkdir developer-starter
cd developer-starter

# Tạo cấu trúc thư mục
mkdir -p assets/{css,js,images}
mkdir -p template-parts/{header,footer}
mkdir -p inc
mkdir -p languages
```

### Bước 2: Tạo style.css

```css
/*
Theme Name:        Developer Starter
Theme URI:         https://example.com/developer-starter
Author:            Developer VN
Author URI:        https://example.com
Description:       Theme WordPress đơn giản cho người mới bắt đầu.
                   Tối ưu, nhẹ, dễ tùy chỉnh.
Version:           1.0.0
Requires at least: 6.0
Tested up to:      6.4
Requires PHP:      8.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       developer-starter
Tags:              blog, custom-menu, featured-images, translation-ready
*/

/* === RESET CƠ BẢN === */
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* === VARIABLES CSS === */
:root {
    --color-primary: #0073aa;
    --color-secondary: #23282d;
    --color-accent: #e74c3c;
    --color-text: #333333;
    --color-text-light: #666666;
    --color-bg: #ffffff;
    --color-bg-light: #f5f5f5;
    --color-border: #dddddd;
    --font-main: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    --font-heading: 'Inter', var(--font-main);
    --font-code: 'Fira Code', 'Courier New', monospace;
    --max-width: 1200px;
    --sidebar-width: 300px;
    --gap: 2rem;
}

/* === TYPOGRAPHY === */
html {
    font-size: 16px;
    scroll-behavior: smooth;
}

body {
    font-family: var(--font-main);
    font-size: 1rem;
    line-height: 1.7;
    color: var(--color-text);
    background-color: var(--color-bg);
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    font-weight: 700;
    line-height: 1.3;
    margin-bottom: 0.5em;
    color: var(--color-secondary);
}

h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.5rem; }
h4 { font-size: 1.25rem; }

a {
    color: var(--color-primary);
    text-decoration: none;
    transition: color 0.3s ease;
}

a:hover {
    color: var(--color-accent);
}

img {
    max-width: 100%;
    height: auto;
    display: block;
}

/* === LAYOUT === */
.site {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.site-content {
    flex: 1;
    max-width: var(--max-width);
    margin: 0 auto;
    padding: var(--gap);
    width: 100%;
}

.content-area {
    display: grid;
    grid-template-columns: 1fr var(--sidebar-width);
    gap: var(--gap);
}

/* Khi không có sidebar */
.content-area.no-sidebar {
    grid-template-columns: 1fr;
}

/* === HEADER === */
.site-header {
    background-color: var(--color-secondary);
    color: #fff;
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.site-header .container {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 var(--gap);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.site-title {
    margin: 0;
    font-size: 1.5rem;
}

.site-title a {
    color: #fff;
    text-decoration: none;
}

.site-description {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

/* === NAVIGATION === */
.main-navigation ul {
    list-style: none;
    display: flex;
    gap: 1.5rem;
}

.main-navigation a {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    padding: 0.5rem 0;
    position: relative;
}

.main-navigation a:hover,
.main-navigation .current-menu-item > a {
    color: #fff;
}

.main-navigation a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--color-primary);
    transition: width 0.3s ease;
}

.main-navigation a:hover::after,
.main-navigation .current-menu-item > a::after {
    width: 100%;
}

/* Sub-menu */
.main-navigation .sub-menu {
    display: none;
    position: absolute;
    background: var(--color-secondary);
    padding: 0.5rem 0;
    min-width: 200px;
    flex-direction: column;
    gap: 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.main-navigation li:hover > .sub-menu {
    display: flex;
}

.main-navigation .sub-menu a {
    padding: 0.5rem 1rem;
}

/* Menu Toggle (Mobile) */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
}

/* === POSTS === */
.post {
    margin-bottom: var(--gap);
    padding-bottom: var(--gap);
    border-bottom: 1px solid var(--color-border);
}

.post:last-child {
    border-bottom: none;
}

.entry-title {
    margin-bottom: 0.5rem;
}

.entry-title a {
    color: var(--color-secondary);
}

.entry-title a:hover {
    color: var(--color-primary);
}

.entry-meta {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin-bottom: 1rem;
}

.entry-meta a {
    color: var(--color-text-light);
}

.entry-content {
    margin-bottom: 1rem;
}

.entry-content p {
    margin-bottom: 1em;
}

.post-thumbnail {
    margin-bottom: 1rem;
    border-radius: 8px;
    overflow: hidden;
}

.read-more {
    display: inline-block;
    padding: 0.5rem 1.5rem;
    background: var(--color-primary);
    color: #fff;
    border-radius: 4px;
    font-weight: 500;
    transition: background 0.3s ease;
}

.read-more:hover {
    background: var(--color-accent);
    color: #fff;
}

/* === SIDEBAR === */
.widget-area {
    font-size: 0.9375rem;
}

.widget {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--color-bg-light);
    border-radius: 8px;
}

.widget-title {
    font-size: 1.125rem;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid var(--color-primary);
}

.widget ul {
    list-style: none;
}

.widget li {
    padding: 0.375rem 0;
    border-bottom: 1px solid var(--color-border);
}

.widget li:last-child {
    border-bottom: none;
}

/* === PAGINATION === */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: var(--gap);
}

.pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    font-weight: 500;
}

.pagination .page-numbers.current {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
}

/* === FOOTER === */
.site-footer {
    background: var(--color-secondary);
    color: rgba(255, 255, 255, 0.8);
    padding: 2rem 0;
    margin-top: auto;
}

.site-footer .container {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 var(--gap);
}

.footer-widgets {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--gap);
    margin-bottom: 2rem;
}

.site-info {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.875rem;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    :root {
        --gap: 1rem;
    }

    .content-area {
        grid-template-columns: 1fr;
    }

    .main-navigation ul {
        display: none;
        flex-direction: column;
    }

    .main-navigation.toggled ul {
        display: flex;
    }

    .menu-toggle {
        display: block;
    }

    .footer-widgets {
        grid-template-columns: 1fr;
    }

    h1 { font-size: 1.75rem; }
    h2 { font-size: 1.5rem; }
}
```

### Bước 3: Tạo functions.php

```php
<?php
/**
 * Developer Starter Theme - Functions
 *
 * @package Developer_Starter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DEVELOPER_STARTER_VERSION', '1.0.0' );

/**
 * Thiet lap theme
 */
function developer_starter_setup() {
    // Đa ngôn ngữ
    load_theme_textdomain( 'developer-starter', get_template_directory() . '/languages' );

    // Title tag tự động
    add_theme_support( 'title-tag' );

    // Featured images
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'developer-featured', 1200, 630, true );

    // Đăng ký menu
    register_nav_menus( array(
        'primary' => __( 'Menu Chính', 'developer-starter' ),
        'footer'  => __( 'Menu Footer', 'developer-starter' ),
    ) );

    // HTML5
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ) );

    // Custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-width'  => true,
        'flex-height' => true,
    ) );

    // Gutenberg
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
}
add_action( 'after_setup_theme', 'developer_starter_setup' );

/**
 * Đăng ký Widget Areas
 */
function developer_starter_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'developer-starter' ),
        'id'            => 'sidebar-main',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer 1', 'developer-starter' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer 2', 'developer-starter' ),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer 3', 'developer-starter' ),
        'id'            => 'footer-3',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'developer_starter_widgets_init' );

/**
 * Enqueue styles và scripts
 */
function developer_starter_scripts() {
    // Main stylesheet
    wp_enqueue_style(
        'developer-starter-style',
        get_stylesheet_uri(),
        array(),
        DEVELOPER_STARTER_VERSION
    );

    // Navigation JS
    wp_enqueue_script(
        'developer-starter-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        array(),
        DEVELOPER_STARTER_VERSION,
        true
    );

    // Comment reply
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_starter_scripts' );

/**
 * Hàm helper: Hiển thị post meta
 */
function developer_starter_posted_on() {
    $time_string = '<time class="entry-date" datetime="%1$s">%2$s</time>';
    $time_string = sprintf(
        $time_string,
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date() )
    );

    printf(
        '<span class="posted-on">%1$s %2$s</span> | <span class="byline">%3$s %4$s</span>',
        esc_html__( 'Đăng ngày', 'developer-starter' ),
        $time_string,
        esc_html__( 'bởi', 'developer-starter' ),
        '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>'
    );
}

/**
 * Hàm helper: Custom excerpt length
 */
function developer_starter_excerpt_length( $length ) {
    return 30; // 30 từ thay vì 55 từ mặc định
}
add_filter( 'excerpt_length', 'developer_starter_excerpt_length' );

/**
 * Hàm helper: Custom excerpt more
 */
function developer_starter_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'developer_starter_excerpt_more' );
```

### Bước 4: Tạo header.php

```php
<?php
/**
 * Header template
 * Hiển thị phần đầu trang: DOCTYPE -> hết navigation
 *
 * @package Developer_Starter
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<!-- language_attributes() tạo: lang="vi" hoặc lang="en-US" -->

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <!-- bloginfo('charset') tạo: UTF-8 -->

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php wp_head(); ?>
    <!--
    wp_head() LÀ BẮT BUỘC - Nó in ra:
    - Các file CSS đã enqueue
    - Các file JS trong <head>
    - Meta tags (title, description)
    - RSS feed links
    - Các code từ plugins (analytics, SEO...)
    Tương tự như @vite trong Laravel Blade
    -->
</head>

<body <?php body_class(); ?>>
<!--
body_class() tự động thêm các class hữu ích:
- home (trang chủ)
- single-post (trang bài viết)
- page-template-xxx (page template đang dùng)
- logged-in (đang đăng nhập)
- admin-bar (hiển admin bar)
Ví dụ: <body class="home blog logged-in admin-bar">
-->

<?php wp_body_open(); ?>
<!-- WP 5.2+: Hook để thêm code sau <body> (analytics, skip link...) -->

<div id="page" class="site">

    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e( 'Chuyển đến nội dung', 'developer-starter' ); ?>
    </a>
    <!-- Skip link cho accessibility - cho phép bàn phím nhảy thẳng đến nội dung -->

    <header id="masthead" class="site-header">
        <div class="container">

            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <!-- Nếu có custom logo, hiển thị logo -->
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <!-- Không có logo, hiển thị tên và mô tả -->
                    <?php if ( is_front_page() && is_home() ) : ?>
                        <!-- Trang chủ: dùng h1 cho SEO -->
                        <h1 class="site-title">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php bloginfo( 'name' ); ?>
                            </a>
                        </h1>
                    <?php else : ?>
                        <!-- Trang khác: dùng p để không có nhiều h1 -->
                        <p class="site-title">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php bloginfo( 'name' ); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description || is_customize_preview() ) :
                    ?>
                        <p class="site-description"><?php echo $description; ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div><!-- .site-branding -->

            <nav id="site-navigation" class="main-navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    &#9776; <?php esc_html_e( 'Menu', 'developer-starter' ); ?>
                </button>

                <?php
                // Hiển thị menu đã đăng ký với ID 'primary'
                wp_nav_menu( array(
                    'theme_location' => 'primary',        // Vị trí menu (đã đăng ký trong functions.php)
                    'menu_id'        => 'primary-menu',   // ID của <ul>
                    'container'      => false,             // Không bọc trong <div>
                    'fallback_cb'    => false,             // Không hiển gì nếu chưa có menu
                    'depth'          => 2,                 // Độ sâu tối đa 2 cấp
                ) );
                ?>
            </nav><!-- .main-navigation -->

        </div><!-- .container -->
    </header><!-- .site-header -->
```

### Bước 5: Tạo footer.php

```php
<?php
/**
 * Footer template
 * Hiển thị từ footer widgets -> hết </html>
 *
 * @package Developer_Starter
 */
?>

    <footer id="colophon" class="site-footer">
        <div class="container">

            <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
            <!-- Chỉ hiển thị footer widgets khi có ít nhất 1 widget -->
            <div class="footer-widgets">
                <div class="footer-widget-area">
                    <?php dynamic_sidebar( 'footer-1' ); ?>
                </div>
                <div class="footer-widget-area">
                    <?php dynamic_sidebar( 'footer-2' ); ?>
                </div>
                <div class="footer-widget-area">
                    <?php dynamic_sidebar( 'footer-3' ); ?>
                </div>
            </div><!-- .footer-widgets -->
            <?php endif; ?>

            <div class="site-info">
                <p>
                    &copy; <?php echo date( 'Y' ); ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <?php bloginfo( 'name' ); ?>
                    </a>
                    | <?php esc_html_e( 'Tạo với WordPress', 'developer-starter' ); ?>
                </p>
            </div><!-- .site-info -->

        </div><!-- .container -->
    </footer><!-- .site-footer -->

</div><!-- #page .site -->

<?php wp_footer(); ?>
<!--
wp_footer() LÀ BẮT BUỘC - In ra:
- Các file JS đã enqueue với in_footer = true
- Admin bar (khi đăng nhập)
- Code từ plugins
Tương tự như @vite hoặc @stack('scripts') trong Laravel
-->

</body>
</html>
```

### Bước 6: Tạo sidebar.php

```php
<?php
/**
 * Sidebar template
 *
 * @package Developer_Starter
 */

// Nếu sidebar không có widget nào, không hiển thị gì
if ( ! is_active_sidebar( 'sidebar-main' ) ) {
    return;
}
?>

<aside id="secondary" class="widget-area" role="complementary">
    <?php dynamic_sidebar( 'sidebar-main' ); ?>
    <!-- dynamic_sidebar() hiển thị tất cả widgets trong sidebar có ID 'sidebar-main' -->
</aside>
```

### Bước 7: Tạo index.php

```php
<?php
/**
 * Main template file - Đây là template cuối cùng (fallback)
 * Nếu không có template cụ thể hơn (single.php, page.php...),
 * WordPress sẽ dùng file này.
 *
 * Trong Laravel, tương tự như resources/views/layouts/app.blade.php
 *
 * @package Developer_Starter
 */

get_header(); // Load header.php (tương tự @include('partials.header'))
?>

<main id="primary" class="site-content">
    <div class="content-area <?php echo is_active_sidebar( 'sidebar-main' ) ? '' : 'no-sidebar'; ?>">

        <div class="main-content">
            <?php if ( have_posts() ) : ?>

                <?php if ( is_home() && ! is_front_page() ) : ?>
                    <!-- Trang blog (khi có static front page) -->
                    <header class="page-header">
                        <h1 class="page-title"><?php single_post_title(); ?></h1>
                    </header>
                <?php endif; ?>

                <?php
                // === THE LOOP ===
                // Vòng lặp chính để hiển thị bài viết
                // Tương tự @foreach ($posts as $post) trong Blade
                while ( have_posts() ) :
                    the_post(); // Chuẩn bị dữ liệu cho bài viết hiện tại
                ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <!--
                        the_ID() : ID của bài viết
                        post_class() : Thêm class tự động (type-post, status-publish, category-xxx...)
                        -->

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'developer-featured' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <header class="entry-header">
                            <?php if ( is_singular() ) : ?>
                                <h1 class="entry-title"><?php the_title(); ?></h1>
                            <?php else : ?>
                                <h2 class="entry-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                            <?php endif; ?>

                            <?php if ( 'post' === get_post_type() ) : ?>
                                <div class="entry-meta">
                                    <?php developer_starter_posted_on(); ?>
                                </div>
                            <?php endif; ?>
                        </header>

                        <div class="entry-content">
                            <?php if ( is_singular() ) : ?>
                                <?php the_content(); ?>
                            <?php else : ?>
                                <?php the_excerpt(); ?>
                                <a href="<?php the_permalink(); ?>" class="read-more">
                                    <?php esc_html_e( 'Đọc thêm', 'developer-starter' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ( is_singular() ) : ?>
                            <footer class="entry-footer">
                                <?php
                                // Hiển thị categories và tags
                                $categories = get_the_category_list( ', ' );
                                if ( $categories ) {
                                    printf( '<span class="cat-links">%s: %s</span>', esc_html__( 'Danh mục', 'developer-starter' ), $categories );
                                }

                                $tags = get_the_tag_list( '', ', ' );
                                if ( $tags ) {
                                    printf( ' | <span class="tag-links">%s: %s</span>', esc_html__( 'Thẻ', 'developer-starter' ), $tags );
                                }
                                ?>
                            </footer>
                        <?php endif; ?>

                    </article>

                <?php endwhile; // Kết thúc The Loop ?>

                <?php
                // Pagination
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => '&laquo; ' . __( 'Trước', 'developer-starter' ),
                    'next_text' => __( 'Sau', 'developer-starter' ) . ' &raquo;',
                ) );
                ?>

            <?php else : ?>

                <!-- Không có bài viết nào -->
                <div class="no-results">
                    <h1><?php esc_html_e( 'Không tìm thấy nội dung', 'developer-starter' ); ?></h1>

                    <?php if ( is_search() ) : ?>
                        <p><?php esc_html_e( 'Không tìm thấy kết quả cho từ khóa của bạn. Hãy thử lại với từ khóa khác.', 'developer-starter' ); ?></p>
                        <?php get_search_form(); ?>
                    <?php else : ?>
                        <p><?php esc_html_e( 'Có vẻ như không có nội dung nào ở đây. Thử tìm kiếm?', 'developer-starter' ); ?></p>
                        <?php get_search_form(); ?>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div><!-- .main-content -->

        <?php get_sidebar(); // Load sidebar.php ?>

    </div><!-- .content-area -->
</main><!-- #primary .site-content -->

<?php
get_footer(); // Load footer.php
?>
```

### Bước 8: Tạo assets/js/navigation.js

```javascript
/**
 * Navigation - Xử lý mobile menu toggle
 */
(function() {
    'use strict';

    // Tìm nút toggle và menu
    const toggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-navigation');

    if (!toggle || !nav) {
        return;
    }

    // Click vào nút hamburger để mở/đóng menu
    toggle.addEventListener('click', function() {
        nav.classList.toggle('toggled');

        // Cập nhật aria-expanded cho accessibility
        const expanded = nav.classList.contains('toggled');
        toggle.setAttribute('aria-expanded', expanded);
    });

    // Đóng menu khi click ra ngoài
    document.addEventListener('click', function(event) {
        if (!nav.contains(event.target) && !toggle.contains(event.target)) {
            nav.classList.remove('toggled');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });

    // Đóng menu khi nhấn Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            nav.classList.remove('toggled');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
})();
```

### Bước 9: Kích hoạt theme

1. Vào **WordPress Admin > Appearance > Themes**
2. Tìm theme "Developer Starter"
3. Click **Activate**
4. Kiểm tra trang chủ

---

## 9. So sánh với Laravel

### Mapping khái niệm Laravel -> WordPress Theme

| Laravel | WordPress | Giải thích |
|---------|-----------|-----------|
| `resources/views/` | Thư mục theme | Nơi chứa template |
| `layouts/app.blade.php` | `header.php` + `footer.php` | Layout chính |
| `@yield('content')` | The Loop trong index.php | Nội dung chính |
| `@extends('layouts.app')` | `get_header()` + `get_footer()` | Kế thừa layout |
| `@include('partials.nav')` | `get_template_part('template-parts/nav')` | Include component |
| `@section('sidebar')` | `get_sidebar()` | Sidebar |
| `routes/web.php` | Template Hierarchy | Routing |
| `public/css/app.css` | `wp_enqueue_style()` | Load CSS |
| `@vite(['resources/css/app.css'])` | `wp_head()` / `wp_footer()` | In ra assets |
| `config/app.php` | `functions.php` | Cấu hình |
| `AppServiceProvider::boot()` | `after_setup_theme` hook | Bootstrap |
| `{{ $variable }}` | `<?php echo esc_html($var); ?>` | Output escaped |
| `{!! $html !!}` | `<?php echo $html; ?>` | Output raw |
| `Blade::component()` | `get_template_part()` | Reusable component |
| `@if @else @endif` | `<?php if(): ?> <?php else: ?> <?php endif; ?>` | Conditionals |
| `@foreach @endforeach` | `while (have_posts()): the_post();` | Loop |

### Routing: Laravel vs WordPress

```php
// === LARAVEL ===
// Bạn TỰ định nghĩa route
Route::get('/', [HomeController::class, 'index']);
Route::get('/blog/{slug}', [PostController::class, 'show']);
Route::get('/category/{slug}', [CategoryController::class, 'show']);

// === WORDPRESS ===
// WordPress TỰ ĐỘNG chọn template dựa trên URL:
// /                --> front-page.php hoặc home.php hoặc index.php
// /hello-world/    --> single.php hoặc index.php
// /category/news/  --> category.php hoặc archive.php hoặc index.php
// Bạn KHÔNG cần định nghĩa route!
```

### Layout: Laravel vs WordPress

```php
// === LARAVEL (Blade) ===
// layouts/app.blade.php
<!DOCTYPE html>
<html>
<head>
    @vite(['resources/css/app.css'])
</head>
<body>
    @include('partials.header')
    <main>
        @yield('content')
    </main>
    @include('partials.footer')
    @vite(['resources/js/app.js'])
</body>
</html>

// pages/home.blade.php
@extends('layouts.app')
@section('content')
    @foreach($posts as $post)
        <h2>{{ $post->title }}</h2>
    @endforeach
@endsection

// === WORDPRESS ===
// index.php (tất cả trong 1 file, hoặc chia thành header/footer)
<?php get_header(); ?>
<main>
    <?php
    while (have_posts()) :
        the_post();
    ?>
        <h2><?php the_title(); ?></h2>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
```

---

## 10. Best Practices

### 1. Bảo mật

```php
// LUÔN thêm dòng này đầu mỗi file PHP trong theme
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// LUÔN escape output
echo esc_html( $text );         // Text bình thường
echo esc_attr( $attribute );    // HTML attributes
echo esc_url( $url );           // URLs
echo wp_kses_post( $html );     // HTML nội dung bài viết (cho phép 1 số tag)

// LUÔN sanitize input
sanitize_text_field( $input );
sanitize_email( $email );
absint( $number );
```

### 2. Prefix mọi thứ

```php
// ĐÚNG - Thêm prefix để tránh trùng tên với plugin khác
function developer_starter_setup() { }
function developer_starter_scripts() { }
define( 'DEVELOPER_STARTER_VERSION', '1.0.0' );

// SAI - Tên quá chung, dễ bị trùng
function setup() { }
function load_scripts() { }
```

### 3. Internationalization (đa ngôn ngữ)

```php
// Dùng các hàm dịch để theme có thể đa ngôn ngữ
__( 'Hello', 'developer-starter' );      // Trả về string đã dịch
_e( 'Hello', 'developer-starter' );      // Echo string đã dịch
esc_html__( 'Hello', 'developer-starter' ); // Trả về + escape
esc_html_e( 'Hello', 'developer-starter' ); // Echo + escape

// Với biến
sprintf( __( 'Hello %s', 'developer-starter' ), $name );

// Số nhiều
_n( '%s comment', '%s comments', $count, 'developer-starter' );
```

### 4. Performance

```php
// Load JS ở footer (tham số true cuối cùng)
wp_enqueue_script( 'my-script', $url, array(), $ver, true );

// Chỉ load khi cần
if ( is_page( 'contact' ) ) {
    wp_enqueue_style( 'contact-form-style', ... );
}

// Dùng version để cache busting
wp_enqueue_style( 'my-style', $url, array(), '1.0.0' );
```

### 5. Cấu trúc code sạch

```php
// Tách code ra các file riêng trong thư mục inc/
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/widgets.php';

// Dùng template parts cho các phần lặp lại
get_template_part( 'template-parts/content', get_post_type() );
// Sẽ load: template-parts/content-post.php hoặc content-page.php
```

### 6. Accessibility (Tiếp cận)

```php
// Luôn có skip link
<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>

// Dùng semantic HTML
<header>, <nav>, <main>, <aside>, <footer>, <article>

// ARIA attributes
<nav aria-label="Primary Menu">
<button aria-expanded="false" aria-controls="primary-menu">

// Focus styles - KHÔNG BAO GIỜ xóa outline
a:focus, button:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}
```

### 7. Coding Standards

```php
// Theo WordPress Coding Standards:
// - Dùng tab (không space) để indent PHP
// - Space sau dấu phẩy: array( 'a', 'b', 'c' )
// - Space trong ngoặc đơn: if ( true ) { }
// - Dùng === thay vì == để so sánh
// - Dùng single quotes cho string không có biến
// - Dùng yoda conditions: if ( true === $variable )

// Cài đặt PHP CodeSniffer + WordPress standards:
// composer require --dev wp-coding-standards/wpcs
// phpcs --standard=WordPress functions.php
```

---

**Tiếp theo:** [02 - Template Hierarchy](./02-template-hierarchy.md) - Hiểu cách WordPress tự động chọn template file
