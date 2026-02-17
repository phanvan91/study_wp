# Theme WordPress Nâng Cao

## Mục Lục

1. [Child Theme](#1-child-theme)
2. [Theme với WooCommerce Support](#2-woocommerce-support)
3. [Responsive Design trong Theme](#3-responsive-design)
4. [Accessibility (a11y)](#4-accessibility)
5. [Performance Optimization](#5-performance)
6. [Internationalization (i18n)](#6-i18n)
7. [Theme Unit Test](#7-theme-unit-test)
8. [Theme Check Plugin](#8-theme-check)
9. [Packaging và Submit lên WordPress.org](#9-packaging)
10. [Custom Page Templates](#10-custom-page-templates)
11. [Theme Options vs Customizer](#11-theme-options-vs-customizer)
12. [Best Practices tổng hợp](#12-best-practices)

---

## 1. Child Theme

### Tại sao cần Child Theme?

Khi bạn muốn **tùy chỉnh 1 theme có sẵn** (Astra, GeneratePress, TwentyTwentyFour...) mà **không bị mất thay đổi khi theme update**.

```
Khi theme cha update:
- File trong theme cha bị GHI ĐÈ hết
- Code bạn thêm trực tiếp vào theme cha sẽ MẤT

Giải pháp: Child Theme
- Code tùy chỉnh ở child theme KHÔNG bị ảnh hưởng khi theme cha update
- Giống như class kế thừa (extends) trong OOP
```

### So sánh với Laravel:

```php
// LARAVEL
// - Vendor package update không ảnh hưởng published files
// php artisan vendor:publish --tag=views
// Các views đã publish nằm trong resources/views/vendor/ - không bị ghi đè

// WORDPRESS
// - Child theme = "published views" của Laravel
// - Override template mà không sửa theme gốc
```

### Cách tạo Child Theme:

```
Bước 1: Tạo thư mục child theme
wp-content/themes/developer-starter-child/

Bước 2: Tạo style.css
Bước 3: Tạo functions.php
Bước 4: (Tùy chọn) Copy và sửa template files từ theme cha
```

### style.css của Child Theme:

```css
/*
Theme Name:        Developer Starter Child
Theme URI:         https://example.com/developer-starter-child
Description:       Child theme của Developer Starter. Dùng để tùy chỉnh mà không
                   ảnh hưởng đến theme gốc khi update.
Author:            Nguyen Van A
Author URI:        https://example.com
Template:          developer-starter
                   ^^ TÊN THƯ MỤC của theme cha (BẮT BUỘC, phải chính xác)
Version:           1.0.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       developer-starter-child
*/

/* === Custom CSS o day === */

/* Override màu primary */
:root {
    --color-primary: #e74c3c;  /* Đổi từ xanh sang đỏ */
}

/* Thêm style riêng cho child theme */
.site-header {
    border-bottom: 3px solid var(--color-primary);
}

.entry-title a:hover {
    color: var(--color-primary);
}
```

### functions.php của Child Theme:

```php
<?php
/**
 * Child Theme Functions
 *
 * QUAN TRỌNG:
 * - functions.php của child theme được load TRƯỚC theme cha
 * - Không ghi đè functions.php cha, mà BỔ SUNG
 * - Dùng child functions.php để:
 *   1. Enqueue styles
 *   2. Thêm/sửa functions
 *   3. Override hooks
 *
 * @package Developer_Starter_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue parent va child theme styles
 */
function developer_child_enqueue_styles() {
    // 1. Load CSS của theme cha
    wp_enqueue_style(
        'developer-starter-parent-style',
        get_template_directory_uri() . '/style.css',
        // get_template_directory_uri() luôn trỏ đến theme CHA
        array(),
        wp_get_theme( 'developer-starter' )->get( 'Version' )
    );

    // 2. Load CSS của child theme (tự động load SAU theme cha)
    wp_enqueue_style(
        'developer-starter-child-style',
        get_stylesheet_uri(),
        // get_stylesheet_uri() trỏ đến child theme
        array( 'developer-starter-parent-style' ), // Phụ thuộc vào parent
        wp_get_theme()->get( 'Version' )
    );

    // 3. Thêm CSS riêng của child
    wp_enqueue_style(
        'developer-child-custom',
        get_stylesheet_directory_uri() . '/assets/css/custom.css',
        array( 'developer-starter-child-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'developer_child_enqueue_styles' );

/**
 * Override functions của theme cha
 *
 * Nếu theme cha dùng function_exists() để định nghĩa hàm,
 * bạn có thể GHI ĐÈ hàm đó trong child theme
 */

// Theme cha (functions.php):
// if ( ! function_exists( 'developer_starter_posted_on' ) ) {
//     function developer_starter_posted_on() { ... }
// }

// Child theme - ghi đè:
function developer_starter_posted_on() {
    // Custom version - thêm icon trước ngày
    printf(
        '<span class="posted-on">&#128197; %s</span> | <span class="byline">&#9998; %s</span>',
        get_the_date(),
        get_the_author()
    );
}

/**
 * Thêm/sửa features
 */

// Thêm post format không có trong theme cha
function developer_child_setup() {
    add_theme_support( 'post-formats', array( 'aside', 'gallery', 'video', 'quote' ) );

    // Thêm kích thước ảnh mới
    add_image_size( 'child-hero', 1920, 800, true );
}
add_action( 'after_setup_theme', 'developer_child_setup', 11 );
// Priority 11 = chạy SAU theme cha (priority 10)

/**
 * Gỡ bỏ actions/filters của theme cha
 */
function developer_child_remove_parent_actions() {
    // Gỡ bỏ hàm của theme cha
    // remove_action( 'wp_head', 'developer_starter_some_function' );

    // Thay đổi excerpt length
    remove_filter( 'excerpt_length', 'developer_starter_excerpt_length' );
}
add_action( 'after_setup_theme', 'developer_child_remove_parent_actions' );

// Thêm excerpt length mới
function developer_child_excerpt_length( $length ) {
    return 40; // 40 từ thay vì 30 từ của theme cha
}
add_filter( 'excerpt_length', 'developer_child_excerpt_length' );

/**
 * Thêm widget area mới (không có trong theme cha)
 */
function developer_child_widgets() {
    register_sidebar( array(
        'name'          => __( 'Before Content', 'developer-starter-child' ),
        'id'            => 'before-content',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'developer_child_widgets' );
```

### Override template files:

```
Để override 1 template file của theme cha:
1. Copy file từ theme cha sang child theme (CÙNG tên, CÙNG đường dẫn)
2. Sửa file trong child theme

Ví dụ:
Theme cha: developer-starter/single.php
Child:     developer-starter-child/single.php  <-- File này sẽ được ưu tiên

Theme cha: developer-starter/template-parts/content.php
Child:     developer-starter-child/template-parts/content.php

LƯU Ý:
- Chỉ copy những file cần sửa
- Không copy tất cả file (khó maintain khi theme cha update)
- functions.php KHÔNG override, nó BỔ SUNG
```

### Cấu trúc Child Theme:

```
developer-starter-child/
|-- style.css               # BẮT BUỘC: có "Template:" header
|-- functions.php           # BẮT BUỘC: enqueue styles
|-- screenshot.png          # Tùy chọn: ảnh preview
|
|-- # Chỉ copy file cần override:
|-- single.php              # Override trang bài viết
|-- template-parts/
|   |-- content-single.php  # Override nội dung bài viết
|
|-- assets/
|   |-- css/
|   |   |-- custom.css      # CSS riêng
|   |-- js/
|       |-- custom.js        # JS riêng
```

---

## 2. WooCommerce Support

### Khai báo WooCommerce support:

```php
<?php
/**
 * Thêm WooCommerce support vào theme
 * Đặt trong functions.php
 */
function developer_woocommerce_support() {
    // 1. Khai báo hỗ trợ WooCommerce
    add_theme_support( 'woocommerce', array(
        'thumbnail_image_width' => 300,
        'single_image_width'    => 600,
        'product_grid'          => array(
            'default_rows'    => 4,
            'min_rows'        => 2,
            'max_rows'        => 8,
            'default_columns' => 4,
            'min_columns'     => 2,
            'max_columns'     => 5,
        ),
    ) );

    // 2. Hỗ trợ Product Gallery features
    add_theme_support( 'wc-product-gallery-zoom' );      // Zoom hình khi hover
    add_theme_support( 'wc-product-gallery-lightbox' );   // Lightbox khi click
    add_theme_support( 'wc-product-gallery-slider' );     // Slider hình ảnh
}
add_action( 'after_setup_theme', 'developer_woocommerce_support' );

/**
 * Override WooCommerce wrapper
 * Mặc định WooCommerce dùng <main> của nó, có thể không khớp với theme
 */

// Bỏ wrapper mặc định của WooCommerce
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

// Thêm wrapper của theme
function developer_woocommerce_wrapper_before() {
    echo '<main id="primary" class="site-main woocommerce-page"><div class="container"><div class="content-area">';
}
add_action( 'woocommerce_before_main_content', 'developer_woocommerce_wrapper_before' );

function developer_woocommerce_wrapper_after() {
    echo '</div></div></main>';
}
add_action( 'woocommerce_after_main_content', 'developer_woocommerce_wrapper_after' );

/**
 * Sidebar cho WooCommerce
 */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

function developer_woocommerce_sidebar() {
    if ( is_active_sidebar( 'sidebar-shop' ) ) {
        echo '<aside class="widget-area sidebar-shop">';
        dynamic_sidebar( 'sidebar-shop' );
        echo '</aside>';
    }
}
add_action( 'woocommerce_sidebar', 'developer_woocommerce_sidebar' );

/**
 * Tùy chỉnh số sản phẩm mỗi trang
 */
function developer_woocommerce_products_per_page( $cols ) {
    return 12; // 12 sản phẩm mỗi trang
}
add_filter( 'loop_shop_per_page', 'developer_woocommerce_products_per_page' );

/**
 * Tùy chỉnh số cột grid
 */
function developer_woocommerce_columns( $columns ) {
    return 4; // 4 cột
}
add_filter( 'loop_shop_columns', 'developer_woocommerce_columns' );

/**
 * Thêm CSS/JS cho WooCommerce
 */
function developer_woocommerce_scripts() {
    if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
        wp_enqueue_style(
            'developer-woocommerce',
            get_template_directory_uri() . '/assets/css/woocommerce.css',
            array(),
            '1.0.0'
        );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_woocommerce_scripts' );
```

### Override WooCommerce templates:

```
Để override template WooCommerce:
1. Copy file từ: wp-content/plugins/woocommerce/templates/
2. Dán vào:      wp-content/themes/developer-theme/woocommerce/

Ví dụ:
Plugin: woocommerce/templates/single-product.php
Theme:  developer-theme/woocommerce/single-product.php

Plugin: woocommerce/templates/loop/price.php
Theme:  developer-theme/woocommerce/loop/price.php

Plugin: woocommerce/templates/cart/cart.php
Theme:  developer-theme/woocommerce/cart/cart.php
```

---

## 3. Responsive Design

### Mobile-First CSS Approach:

```css
/**
 * Mobile First: Viết CSS cho mobile trước, sau đó thêm cho màn hình lớn
 */

/* === BASE: Mobile (0 - 767px) === */
.container {
    width: 100%;
    padding: 0 1rem;
    margin: 0 auto;
}

.content-area {
    display: flex;
    flex-direction: column; /* Stack trên mobile */
    gap: 1.5rem;
}

.posts-grid {
    display: grid;
    grid-template-columns: 1fr; /* 1 cột trên mobile */
    gap: 1.5rem;
}

.site-header .header-inner {
    flex-direction: column;
    gap: 1rem;
}

/* === TABLET: 768px trở lên === */
@media (min-width: 768px) {
    .container {
        max-width: 720px;
        padding: 0 1.5rem;
    }

    .posts-grid {
        grid-template-columns: repeat(2, 1fr); /* 2 cột */
    }

    .site-header .header-inner {
        flex-direction: row;
        justify-content: space-between;
    }
}

/* === DESKTOP: 1024px trở lên === */
@media (min-width: 1024px) {
    .container {
        max-width: 960px;
    }

    .content-area {
        flex-direction: row; /* Nội dung + Sidebar cạnh nhau */
    }

    .main-content {
        flex: 1;
    }

    .sidebar {
        width: 300px;
        flex-shrink: 0;
    }

    .posts-grid {
        grid-template-columns: repeat(3, 1fr); /* 3 cột */
    }
}

/* === LARGE DESKTOP: 1200px trở lên === */
@media (min-width: 1200px) {
    .container {
        max-width: var(--max-width, 1200px);
    }

    .posts-grid {
        grid-template-columns: repeat(4, 1fr); /* 4 cột */
    }
}
```

### Responsive Images:

```php
<?php
// WordPress tự động tạo srcset và sizes cho ảnh
// Đảm bảo thiết lập image sizes trong functions.php:

add_image_size( 'developer-sm', 400, 300, true );
add_image_size( 'developer-md', 800, 600, true );
add_image_size( 'developer-lg', 1200, 630, true );

// Khi dùng the_post_thumbnail(), WordPress tự động thêm srcset:
the_post_thumbnail( 'developer-lg' );
// Output:
// <img src="image-1200x630.jpg"
//      srcset="image-400x300.jpg 400w,
//              image-800x600.jpg 800w,
//              image-1200x630.jpg 1200w"
//      sizes="(max-width: 1200px) 100vw, 1200px"
//      alt="..." loading="lazy" />

// Custom sizes attribute:
the_post_thumbnail( 'developer-lg', array(
    'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 800px',
) );
```

### Responsive trong PHP:

```php
<?php
/**
 * Helper: Responsive body classes
 */
function developer_responsive_body_classes( $classes ) {
    // Thêm class để CSS targeting dễ hơn
    if ( is_active_sidebar( 'sidebar-main' ) && ! is_page_template( 'page-templates/template-full-width.php' ) ) {
        $classes[] = 'has-sidebar';
    } else {
        $classes[] = 'no-sidebar';
    }

    // Sidebar position
    $sidebar_pos = get_theme_mod( 'developer_sidebar_position', 'right' );
    $classes[] = 'sidebar-' . $sidebar_pos;

    return $classes;
}
add_filter( 'body_class', 'developer_responsive_body_classes' );
```

---

## 4. Accessibility (a11y)

### Các yêu cầu cơ bản:

```php
<?php
// === 1. SKIP LINK ===
// Cho phép người dùng bàn phím nhảy thẳng đến nội dung chính
?>
<body <?php body_class(); ?>>
    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e( 'Chuyển đến nội dung chính', 'developer-theme' ); ?>
    </a>

<?php
// CSS cho skip link:
?>
<style>
.screen-reader-text {
    clip: rect(1px, 1px, 1px, 1px);
    clip-path: inset(50%);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute !important;
    width: 1px;
    word-wrap: normal !important;
}

.screen-reader-text:focus {
    background: #f1f1f1;
    border-radius: 3px;
    box-shadow: 0 0 2px 2px rgba(0, 0, 0, 0.6);
    clip: auto !important;
    clip-path: none;
    color: #21759b;
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    height: auto;
    left: 5px;
    line-height: normal;
    padding: 15px 23px 14px;
    text-decoration: none;
    top: 5px;
    width: auto;
    z-index: 100000;
}
</style>

<?php
// === 2. SEMANTIC HTML ===
?>
<header role="banner">...</header>
<nav role="navigation" aria-label="<?php esc_attr_e( 'Menu Chính', 'developer-theme' ); ?>">...</nav>
<main id="primary" role="main">...</main>
<aside role="complementary">...</aside>
<footer role="contentinfo">...</footer>
<article>...</article>

<?php
// === 3. ARIA ATTRIBUTES ===
?>
<!-- Menu toggle -->
<button class="menu-toggle"
        aria-controls="primary-menu"
        aria-expanded="false"
        aria-label="<?php esc_attr_e( 'Mở menu', 'developer-theme' ); ?>">
    <span class="hamburger-icon"></span>
</button>

<!-- Search form -->
<form role="search" aria-label="<?php esc_attr_e( 'Tìm kiếm trên trang', 'developer-theme' ); ?>">
    <label for="search-input" class="screen-reader-text">
        <?php esc_html_e( 'Tìm kiếm:', 'developer-theme' ); ?>
    </label>
    <input type="search" id="search-input"
           placeholder="<?php esc_attr_e( 'Tìm kiếm...', 'developer-theme' ); ?>"
           value="<?php echo get_search_query(); ?>"
           name="s"
           aria-label="<?php esc_attr_e( 'Từ khóa tìm kiếm', 'developer-theme' ); ?>" />
    <button type="submit" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'developer-theme' ); ?>">
        <span class="screen-reader-text"><?php esc_html_e( 'Tìm kiếm', 'developer-theme' ); ?></span>
        <!-- SVG icon -->
    </button>
</form>

<?php
// === 4. FOCUS STYLES ===
?>
<style>
/* KHÔNG BAO GIỜ xóa outline khi focus */
/* SAI: */ /* *:focus { outline: none; } */

/* ĐÚNG: Tạo focus style đẹp hơn */
a:focus,
button:focus,
input:focus,
select:focus,
textarea:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* Focus visible (chỉ hiện khi dùng bàn phím, không hiện khi click chuột) */
:focus:not(:focus-visible) {
    outline: none;
}
:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}
</style>

<?php
// === 5. ALT TEXT CHO IMAGES ===
// Luôn có alt text cho ảnh
the_post_thumbnail( 'large', array(
    'alt' => get_the_title(), // Hoặc mô tả cụ thể hơn
) );

// === 6. COLOR CONTRAST ===
// Đảm bảo tỷ lệ tương phản tối thiểu:
// - Text bình thường: 4.5:1
// - Text lớn (18px+ bold): 3:1
// Dùng tool: https://webaim.org/resources/contrastchecker/

// === 7. HEADING HIERARCHY ===
// Dùng thứ tự heading đúng (không nhảy bậc)
// h1 -> h2 -> h3 (ĐÚNG)
// h1 -> h3 -> h2 (SAI)
// Chỉ có 1 h1 mỗi trang
```

---

## 5. Performance Optimization

### CSS/JS Optimization:

```php
<?php
/**
 * Performance: Tối ưu CSS và JS
 */

// === 1. Defer va Async cho scripts ===
function developer_script_attributes( $tag, $handle, $src ) {
    // Thêm defer cho scripts cụ thể
    $defer_scripts = array( 'developer-main', 'developer-navigation' );

    if ( in_array( $handle, $defer_scripts ) ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'developer_script_attributes', 10, 3 );

// === 2. Preload fonts va critical CSS ===
function developer_preload_resources() {
    // Preload font
    echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() . '/assets/fonts/inter/Inter-Regular.woff2' ) . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";

    // Preconnect Google Fonts (nếu dùng)
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'developer_preload_resources', 1 );

// === 3. Gỡ bỏ scripts/styles không cần ===
function developer_remove_unnecessary() {
    // Gỡ bỏ emoji scripts
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );

    // Gỡ bỏ wp-embed.js (nếu không cần embed bài viết WP khác)
    wp_deregister_script( 'wp-embed' );

    // Gỡ bỏ jQuery migrate (nếu theme không cần)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', false, array( 'jquery-core' ), null, true );
    }

    // Conditional load: Chỉ load CF7 CSS/JS trên trang có form
    if ( ! is_page( array( 'contact', 'lien-he' ) ) ) {
        wp_dequeue_style( 'contact-form-7' );
        wp_dequeue_script( 'contact-form-7' );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_remove_unnecessary', 100 );

// === 4. Clean up wp_head ===
function developer_clean_head() {
    remove_action( 'wp_head', 'rsd_link' );                    // Remove RSD link
    remove_action( 'wp_head', 'wlwmanifest_link' );            // Remove WLW manifest
    remove_action( 'wp_head', 'wp_generator' );                // Remove WP version
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );        // Remove shortlink
    remove_action( 'wp_head', 'rest_output_link_wp_head' );    // Remove REST API link
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' ); // Remove oEmbed
    remove_action( 'wp_head', 'feed_links_extra', 3 );          // Remove extra feed links
}
add_action( 'init', 'developer_clean_head' );
```

### Image Optimization:

```php
<?php
// === 1. Lazy Loading (WP 5.5+ tự động thêm loading="lazy") ===
// Mặc định đã bật, không cần làm gì thêm

// Nếu muốn tắt lazy loading cho ảnh đầu tiên (LCP):
function developer_disable_lazy_load_first_image( $attr, $attachment, $size ) {
    // Disable lazy load cho ảnh đầu tiên trong loop
    static $counter = 0;
    $counter++;

    if ( $counter === 1 && is_home() || is_front_page() ) {
        $attr['loading'] = 'eager';       // Load ngay, không lazy
        $attr['fetchpriority'] = 'high';  // Ưu tiên cao
    }

    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'developer_disable_lazy_load_first_image', 10, 3 );

// === 2. WebP support ===
// WordPress 5.8+ hỗ trợ WebP tự động
// Chỉ cần upload WebP images, WP sẽ xử lý

// === 3. Responsive images với srcset ===
// WordPress tự động thêm srcset cho ảnh upload qua Media Library
// Đảm bảo có nhiều image sizes:
add_image_size( 'developer-sm', 400, 0, false );
add_image_size( 'developer-md', 800, 0, false );
add_image_size( 'developer-lg', 1200, 0, false );
```

### Caching Strategies:

```php
<?php
// === Transients API cho cache data ===

/**
 * Cache kết quả WP_Query với Transients
 * Giảm số query đến database
 */
function developer_get_featured_posts() {
    // Kiểm tra cache trước
    $cached = get_transient( 'developer_featured_posts' );

    if ( false !== $cached ) {
        return $cached; // Trả về từ cache
    }

    // Không có cache -> chạy query
    $query = new WP_Query( array(
        'post_type'      => 'post',
        'posts_per_page' => 5,
        'meta_key'       => 'is_featured',
        'meta_value'     => '1',
    ) );

    $posts = $query->posts;

    // Lưu vào cache (12 giờ)
    set_transient( 'developer_featured_posts', $posts, 12 * HOUR_IN_SECONDS );

    wp_reset_postdata();

    return $posts;
}

// Xóa cache khi có bài viết mới hoặc cập nhật
function developer_clear_featured_cache( $post_id ) {
    delete_transient( 'developer_featured_posts' );
}
add_action( 'save_post', 'developer_clear_featured_cache' );
```

---

## 6. Internationalization (i18n)

### Các hàm dịch:

```php
<?php
/**
 * Các hàm i18n của WordPress
 *
 * MỌI chuỗi text hiển thị cho người dùng PHẢI dùng hàm dịch
 * Tham số thứ 2 luôn là Text Domain (phải trùng với tên thư mục theme)
 */

// === 1. __() va _e() ===
// __() : Trả về string đã dịch (không echo)
$text = __( 'Xin chao', 'developer-theme' );

// _e() : Echo string đã dịch
_e( 'Xin chao', 'developer-theme' );

// === 2. esc_html__() va esc_html_e() ===
// Giống trên nhưng có escape HTML
echo esc_html__( 'Xin chao', 'developer-theme' );
esc_html_e( 'Xin chao', 'developer-theme' );

// === 3. esc_attr__() va esc_attr_e() ===
// Escape cho HTML attributes
echo '<input placeholder="' . esc_attr__( 'Tim kiem...', 'developer-theme' ) . '">';

// === 4. sprintf() với dịch ===
printf(
    /* translators: %s: tác giả */
    esc_html__( 'Viết bởi %s', 'developer-theme' ),
    get_the_author()
);

// Ví dụ phức tạp hơn:
printf(
    /* translators: 1: số bình luận, 2: tên bài viết */
    esc_html__( '%1$s bình luận cho "%2$s"', 'developer-theme' ),
    number_format_i18n( get_comments_number() ),
    get_the_title()
);

// === 5. _n() - Số nhiều ===
printf(
    /* translators: %s: số bài viết */
    esc_html( _n(
        '%s bài viết',      // Số ít (1)
        '%s bài viết',      // Số nhiều (2+)
        $count,             // Số lượng
        'developer-theme'   // Text domain
    ) ),
    number_format_i18n( $count )
);

// === 6. _x() - Context ===
// Khi cùng 1 từ có nhiều nghĩa
$title = _x( 'Post', 'post type name', 'developer-theme' );  // Bài viết
$verb  = _x( 'Post', 'verb: to post', 'developer-theme' );   // Đăng bài

// === 7. _nx() - Số nhiều với context ===
printf(
    _nx(
        '%s item',
        '%s items',
        $count,
        'cart items count',
        'developer-theme'
    ),
    $count
);

// === 8. Chuỗi có HTML ===
printf(
    wp_kses(
        /* translators: %s: URL trang liên hệ */
        __( 'Liên hệ với chúng tôi <a href="%s">tại đây</a>.', 'developer-theme' ),
        array( 'a' => array( 'href' => array() ) )
    ),
    esc_url( home_url( '/contact' ) )
);

// === 9. number_format_i18n() ===
echo number_format_i18n( 1234567 ); // 1,234,567 (en) hoặc 1.234.567 (de)

// === 10. date_i18n() ===
echo date_i18n( get_option( 'date_format' ) ); // Ngày theo định dạng và ngôn ngữ
```

### Load text domain:

```php
<?php
// Trong functions.php
function developer_load_textdomain() {
    load_theme_textdomain(
        'developer-theme',                                    // Text domain
        get_template_directory() . '/languages'                // Thư mục chứa file .mo
    );
}
add_action( 'after_setup_theme', 'developer_load_textdomain' );
```

### Tạo file dịch:

```bash
# Bước 1: Cài WP-CLI (nếu chưa có)
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Bước 2: Tạo file .pot (template)
cd wp-content/themes/developer-theme/
wp i18n make-pot . languages/developer-theme.pot --domain=developer-theme

# Bước 3: Tạo file .po cho ngôn ngữ cụ thể
# Dùng tool: Poedit (https://poedit.net/)
# Mở file .pot, dịch các chuỗi, lưu thành vi.po và vi.mo

# Hoặc dùng WP-CLI:
wp i18n make-json languages/ --no-purge
```

### File structure:

```
languages/
|-- developer-theme.pot    # Template (source of truth)
|-- vi.po                  # Vietnamese translations (text)
|-- vi.mo                  # Vietnamese translations (compiled binary)
|-- en_US.po               # English (nếu cần)
|-- en_US.mo
```

---

## 7. Theme Unit Test

### Theme Unit Test Data:

```
WordPress cung cấp bộ dữ liệu test để kiểm tra theme:
1. Tải về: https://github.com/WPTT/theme-unit-test
2. Import: Tools > Import > WordPress
3. Kiểm tra tất cả các trang, post types, layouts

Bộ dữ liệu bao gồm:
- Bài viết với nhiều định dạng (titles dài, ngắn, có special chars)
- Pages với nested hierarchy
- Comments (nhiều cấp)
- Categories và Tags
- Featured Images với nhiều kích thước
- Post Formats
- Edge cases (empty content, very long content...)
```

### Checklist kiểm tra:

```
LAYOUT:
[ ] Trang chủ hiển thị đúng
[ ] Single post hiển thị đúng (với/không có featured image)
[ ] Page hiển thị đúng (với/không có parent page)
[ ] Archive, Category, Tag hiển thị đúng
[ ] Search results hiển thị đúng
[ ] 404 page hiển thị đúng
[ ] Sidebar hiển thị/ẩn đúng
[ ] Footer widgets hiển thị đúng

NỘI DUNG:
[ ] Title dài (>100 ký tự) không bị tràn
[ ] Nội dung với tất cả HTML tags (h1-h6, ul, ol, table, blockquote, code...)
[ ] Images với alignleft, alignright, aligncenter, alignnone, alignwide, alignfull
[ ] Galleries với nhiều hình
[ ] Embedded content (YouTube, Twitter, etc.)
[ ] Password protected post
[ ] Sticky post

RESPONSIVE:
[ ] Desktop (1200px+)
[ ] Tablet (768px - 1023px)
[ ] Mobile (< 768px)
[ ] Menu mobile hoạt động

NAVIGATION:
[ ] Menu với nhiều cấp (3+ levels)
[ ] Menu item dài
[ ] Breadcrumbs chính xác

BÌNH LUẬN:
[ ] Comment form hiển thị
[ ] Nested comments (3+ levels)
[ ] Comment pagination
[ ] Trackbacks/Pingbacks

ACCESSIBILITY:
[ ] Tab navigation qua menu
[ ] Skip link hoạt động
[ ] Focus styles rõ ràng
[ ] Alt text cho images
[ ] ARIA labels đúng

PERFORMANCE:
[ ] Không có lỗi console (JS errors)
[ ] Images lazy loaded
[ ] CSS/JS load ở đúng vị trí (head/footer)
```

---

## 8. Theme Check

### Cài đặt Theme Check Plugin:

```
1. Admin > Plugins > Add New
2. Tìm "Theme Check"
3. Install và Activate
4. Vào Admin > Appearance > Theme Check
5. Chọn theme và click "Check it!"
```

### Các quy tắc Theme Check kiểm tra:

```
REQUIRED (bắt buộc):
- Có style.css với Theme Name
- Có index.php
- add_theme_support('automatic-feed-links')
- wp_head() trong header
- wp_footer() trước </body>
- wp_enqueue_style/script (không hard-code)
- body_class() trong <body>
- post_class() trong loop
- comment_form() hoac comments_template()
- wp_link_pages()
- Không có file deprecated như timthumb.php

RECOMMENDED (khuyến dụng):
- add_theme_support('title-tag')
- add_theme_support('custom-logo')
- Tất cả text dùng i18n functions
- Prefix tất cả functions và classes
- Không dùng PHP error suppression (@)
- Không có hard-coded links
- Dùng esc_* functions cho output
```

### Fix các lỗi thường gặp:

```php
<?php
// LỖI: INFO: Could not find wp_link_pages.
// FIX: Thêm vào single.php và page.php:
wp_link_pages( array(
    'before' => '<div class="page-links">' . __( 'Trang:', 'developer-theme' ),
    'after'  => '</div>',
) );

// LỖI: REQUIRED: Could not find add_theme_support( 'automatic-feed-links' )
// FIX: Thêm vào functions.php:
add_theme_support( 'automatic-feed-links' );

// LỖI: REQUIRED: Could not find body_class call
// FIX: header.php phải có:
<body <?php body_class(); ?>>

// LỖI: REQUIRED: Could not find post_class
// FIX: Trong loop:
<article <?php post_class(); ?>>

// LỖI: WARNING: Found hard-coded link
// FIX: Thay link cụ thể bằng hàm:
// SAI: <a href="http://example.com/contact">
// DUNG: <a href="<?php echo esc_url( home_url('/contact') ); ?>">

// LỖI: WARNING: file not sanitized
// FIX: Escape tất cả output
echo esc_html( $variable );
echo esc_url( $url );
echo esc_attr( $attribute );
```

---

## 9. Packaging va Submit len WordPress.org

### Chuẩn bị theme:

```
1. KIỂM TRA:
   - Chạy Theme Check plugin (pass tất cả REQUIRED)
   - Chạy Theme Unit Test data
   - Test trên nhiều trình duyệt (Chrome, Firefox, Safari, Edge)
   - Test responsive (Mobile, Tablet, Desktop)
   - Test accessibility (keyboard navigation, screen reader)

2. FILE CẦN CÓ:
   - style.css (với đầy đủ header)
   - index.php
   - functions.php
   - screenshot.png (1200x900, under 2MB)
   - readme.txt (khuyến dụng)

3. FILE KHÔNG ĐƯỢC CÓ:
   - File .git, .gitignore
   - node_modules/
   - .sass-cache/
   - Source files (SCSS, LESS, TypeScript)
   - IDE files (.vscode, .idea)
   - OS files (.DS_Store, Thumbs.db)

4. LICENSE:
   - Theme PHẢI là GPL v2 hoặc tương thích
   - Tất cả assets (fonts, images, icons) cũng phải GPL compatible
   - Ghi rõ license trong style.css và readme.txt
```

### readme.txt:

```
=== Developer Theme ===
Contributors: developervn
Tags: blog, custom-menu, featured-images, footer-widgets, threaded-comments,
      translation-ready, custom-background, custom-logo, editor-style
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Theme WordPress cho developer, tối ưu cho hiệu năng và SEO.

== Description ==

Developer Theme là theme WordPress đơn giản, nhanh, và dễ tùy chỉnh.
Thiết kế cho blog, portfolio, và website công ty.

Tính năng:
* Responsive design
* Custom logo va colors
* Widget areas (sidebar + 3 footer columns)
* Translation ready
* Block Editor support

== Installation ==

1. Vào Appearance > Themes > Add New
2. Tìm "Developer Theme"
3. Click Install và Activate

== Changelog ==

= 1.0.0 =
* Phiên bản đầu tiên

== Resources ==

* Inter Font: https://fonts.google.com/specimen/Inter
  License: SIL Open Font License, 1.1
  Source: https://github.com/rsms/inter

* Normalize.css: https://necolas.github.io/normalize.css/
  License: MIT
  Source: https://github.com/necolas/normalize.css

* Screenshot image: Unsplash
  License: Unsplash License (free for commercial use)
  Source: https://unsplash.com/photos/xxxxx

== Copyright ==

Developer Theme, Copyright 2024 Developer VN
Developer Theme is distributed under the terms of the GNU GPL v2 or later.
```

### Đóng gói và submit:

```bash
# Bước 1: Dọn dẹp
cd wp-content/themes/developer-theme/

# Xóa file không cần
rm -rf node_modules/
rm -rf .git/
rm -rf .sass-cache/
rm -f .gitignore
rm -f package.json
rm -f package-lock.json
rm -f composer.json
rm -f composer.lock

# Bước 2: Tạo zip
cd ..
zip -r developer-theme.zip developer-theme/ \
    -x "developer-theme/.git/*" \
    -x "developer-theme/node_modules/*" \
    -x "developer-theme/.DS_Store"

# Bước 3: Submit
# 1. Tạo account trên WordPress.org
# 2. Vào https://wordpress.org/themes/upload/
# 3. Upload file zip
# 4. Đợi review (thường 1-3 tháng)

# Hoặc dùng SVN (sau khi được approved):
# svn co https://themes.svn.wordpress.org/developer-theme/
# Copy files vao thu muc trunk/
# svn ci -m "Version 1.0.0"
```

---

## 10. Custom Page Templates

### Tạo Custom Page Templates:

```php
<?php
/**
 * Template Name: Full Width - Không Có Sidebar
 * Template Post Type: page, post
 *
 * Dòng "Template Name:" khai báo tên template
 * Dòng "Template Post Type:" cho phép chọn template cho post types nào
 *
 * File này có thể đặt ở:
 * - Thư mục gốc: full-width.php
 * - Thư mục con: page-templates/full-width.php
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main full-width-page">
    <div class="container-wide">
        <?php
        while ( have_posts() ) :
            the_post();
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
```

```php
<?php
/**
 * Template Name: Landing Page
 * Template Post Type: page
 *
 * Landing page: không có header/footer của theme,
 * chỉ có nội dung page
 */

// KHÔNG gọi get_header() - dùng header riêng
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        /* Styles riêng cho landing page */
        .admin-bar .landing-page { margin-top: 32px; }
        .landing-page { font-family: var(--font-main); }
    </style>
</head>
<body <?php body_class( 'landing-page' ); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site landing">
    <?php
    while ( have_posts() ) :
        the_post();
        the_content();
    endwhile;
    ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
```

```php
<?php
/**
 * Template Name: Sidebar Trái
 * Template Post Type: page
 */

get_header();
?>

<main id="primary" class="site-main sidebar-left-page">
    <div class="container">
        <div class="content-area sidebar-left">
            <!-- Sidebar ở bên trái -->
            <?php get_sidebar(); ?>

            <!-- Nội dung ở bên phải -->
            <div class="main-content">
                <?php
                while ( have_posts() ) :
                    the_post();
                ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                        <div class="entry-content"><?php the_content(); ?></div>
                    </article>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
```

### Kiểm tra template đang dùng:

```php
<?php
// Kiểm tra bài viết/trang đang dùng template nào
if ( is_page_template( 'page-templates/full-width.php' ) ) {
    // Đang dùng template Full Width
    echo 'class="no-sidebar"';
}

// Lấy tên template file đang dùng
$template = get_page_template_slug();
// Trả về: 'page-templates/full-width.php' hoặc '' (mặc định)

// Dùng trong body_class filter
function developer_template_body_class( $classes ) {
    if ( is_page_template( 'page-templates/full-width.php' ) ) {
        $classes[] = 'no-sidebar';
        $classes[] = 'full-width-layout';
    }
    if ( is_page_template( 'page-templates/landing-page.php' ) ) {
        $classes[] = 'landing-page-layout';
    }
    return $classes;
}
add_filter( 'body_class', 'developer_template_body_class' );
```

---

## 11. Theme Options vs Customizer

### So sánh:

```
CUSTOMIZER (khuyến dụng):
+ Live preview
+ API chuẩn WordPress
+ Tích hợp sẵn sanitize
+ Non-destructive (có default values)
+ Responsive preview
- Giới hạn về UI (không làm được dashboard phức tạp)
- Không phù hợp cho settings lớn

THEME OPTIONS PAGE (tự tạo):
+ Linh hoạt về UI
+ Phù hợp cho settings phức tạp
+ Có thể dùng tabs, groups
- Không có live preview
- Tự code sanitize
- Tự code save/load

KHUYẾN NGHỊ:
1. Dùng Customizer cho APPEARANCE settings (colors, fonts, layout)
2. Dùng Options page cho FUNCTIONALITY settings (nếu cần)
3. Hoặc dùng plugin như ACF/CMB2 cho options page
```

### Theme Options Page đơn giản (nếu cần):

```php
<?php
/**
 * Theme Options Page - Cách tạo nhanh
 * Chỉ dùng khi Customizer không đủ
 */

// 1. Thêm menu trong admin
function developer_options_menu() {
    add_theme_page(
        __( 'Theme Options', 'developer-theme' ),    // Page title
        __( 'Theme Options', 'developer-theme' ),    // Menu title
        'manage_options',                             // Capability
        'developer-options',                          // Menu slug
        'developer_options_page'                      // Callback function
    );
}
add_action( 'admin_menu', 'developer_options_menu' );

// 2. Đăng ký settings
function developer_options_init() {
    register_setting(
        'developer_options_group',       // Option group
        'developer_theme_options',       // Option name (luu trong wp_options)
        array(
            'sanitize_callback' => 'developer_sanitize_options', // Sanitize
            'default'           => developer_default_options(),
        )
    );

    // Section
    add_settings_section(
        'developer_general_section',
        __( 'Cài Đặt Chung', 'developer-theme' ),
        function() {
            echo '<p>' . esc_html__( 'Cài đặt chung cho theme.', 'developer-theme' ) . '</p>';
        },
        'developer-options'
    );

    // Field: Google Analytics ID
    add_settings_field(
        'google_analytics_id',
        __( 'Google Analytics ID', 'developer-theme' ),
        function() {
            $options = get_option( 'developer_theme_options' );
            $value = isset( $options['google_analytics_id'] ) ? $options['google_analytics_id'] : '';
            echo '<input type="text" name="developer_theme_options[google_analytics_id]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="G-XXXXXXXXXX" />';
            echo '<p class="description">' . esc_html__( 'Nhập Google Analytics Measurement ID.', 'developer-theme' ) . '</p>';
        },
        'developer-options',
        'developer_general_section'
    );

    // Field: Custom code before </head>
    add_settings_field(
        'head_code',
        __( 'Code Trước &lt;/head&gt;', 'developer-theme' ),
        function() {
            $options = get_option( 'developer_theme_options' );
            $value = isset( $options['head_code'] ) ? $options['head_code'] : '';
            echo '<textarea name="developer_theme_options[head_code]" rows="5" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
            echo '<p class="description">' . esc_html__( 'Code sẽ được thêm trước thẻ đóng </head>.', 'developer-theme' ) . '</p>';
        },
        'developer-options',
        'developer_general_section'
    );
}
add_action( 'admin_init', 'developer_options_init' );

// 3. Default options
function developer_default_options() {
    return array(
        'google_analytics_id' => '',
        'head_code'           => '',
    );
}

// 4. Sanitize
function developer_sanitize_options( $input ) {
    $sanitized = array();
    $sanitized['google_analytics_id'] = sanitize_text_field( $input['google_analytics_id'] ?? '' );
    $sanitized['head_code'] = wp_kses( $input['head_code'] ?? '', array(
        'script' => array( 'src' => array(), 'async' => array(), 'defer' => array() ),
        'noscript' => array(),
        'link' => array( 'rel' => array(), 'href' => array() ),
        'meta' => array( 'name' => array(), 'content' => array() ),
        'style' => array(),
    ) );
    return $sanitized;
}

// 5. Render page
function developer_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Hiển thị thông báo lưu thành công
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error(
            'developer_messages',
            'developer_message',
            __( 'Cài đặt đã được lưu.', 'developer-theme' ),
            'updated'
        );
    }
    settings_errors( 'developer_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'developer_options_group' );
            do_settings_sections( 'developer-options' );
            submit_button( __( 'Lưu Cài Đặt', 'developer-theme' ) );
            ?>
        </form>
    </div>
    <?php
}

// 6. Sử dụng options trong template
function developer_output_head_code() {
    $options = get_option( 'developer_theme_options', developer_default_options() );

    // Google Analytics
    if ( ! empty( $options['google_analytics_id'] ) ) {
        $ga_id = esc_attr( $options['google_analytics_id'] );
        echo "<!-- Google Analytics -->\n";
        echo "<script async src='https://www.googletagmanager.com/gtag/js?id={$ga_id}'></script>\n";
        echo "<script>\n";
        echo "window.dataLayer = window.dataLayer || [];\n";
        echo "function gtag(){dataLayer.push(arguments);}\n";
        echo "gtag('js', new Date());\n";
        echo "gtag('config', '{$ga_id}');\n";
        echo "</script>\n";
    }

    // Custom head code
    if ( ! empty( $options['head_code'] ) ) {
        echo $options['head_code'] . "\n";
    }
}
add_action( 'wp_head', 'developer_output_head_code', 999 );
```

---

## 12. Best Practices tong hop

### 1. Bảo mật

```php
// Escape MỌI output
echo esc_html( $text );
echo esc_attr( $attr );
echo esc_url( $url );
echo wp_kses_post( $html );

// Không cho truy cập trực tiếp file PHP
if ( ! defined( 'ABSPATH' ) ) exit;

// Sanitize tất cả input
sanitize_text_field( $input );
absint( $number );
sanitize_email( $email );

// Nonce cho forms
wp_nonce_field( 'action_name', 'nonce_field' );
wp_verify_nonce( $_POST['nonce_field'], 'action_name' );
```

### 2. Prefix

```php
// LUÔN prefix tất cả: functions, classes, constants, hooks
function developer_theme_setup() {}
class Developer_Theme_Walker {}
define( 'DEVELOPER_THEME_VERSION', '1.0.0' );

// Text domain = tên thư mục theme
__( 'text', 'developer-theme' );
```

### 3. Coding Standards

```php
// Theo WordPress Coding Standards
// - Tab indentation (không spaces)
// - Spaces trong ngoặc: if ( $condition ) { }
// - Yoda conditions: if ( true === $var )
// - === thay vì ==
// - Single quotes cho string không có biến
// - PHPDoc cho mỗi function
```

### 4. Performance

```php
// Load JS ở footer
wp_enqueue_script( 'handle', $url, array(), $ver, true );

// Conditional loading
if ( is_page( 'contact' ) ) {
    wp_enqueue_style( 'contact-css', ... );
}

// no_found_rows cho queries không cần pagination
'no_found_rows' => true

// Transients cho cache
set_transient( 'key', $data, HOUR_IN_SECONDS );

// Lazy loading images
'loading' => 'lazy'
```

### 5. Accessibility

```php
// Skip link
<a class="skip-link screen-reader-text" href="#primary">Skip</a>

// Semantic HTML
<header>, <nav>, <main>, <aside>, <footer>, <article>

// ARIA attributes
aria-label, aria-expanded, aria-controls, role

// Focus styles
:focus-visible { outline: 2px solid #0073aa; }

// Color contrast >= 4.5:1
```

### 6. Template Architecture

```php
// Tách code thành các file nhỏ, có tổ chức
functions.php          -- Bootstrap, includes
inc/customizer.php     -- Customizer settings
inc/template-tags.php  -- Helper functions cho templates
inc/widgets.php        -- Custom widgets
inc/walker.php         -- Custom menu walkers
template-parts/        -- Reusable template components

// Dùng get_template_part() thay vì include
get_template_part( 'template-parts/content', get_post_type() );

// wp_reset_postdata() sau custom queries
wp_reset_postdata();

// Không bao giờ dùng query_posts()
// Dùng pre_get_posts hoặc new WP_Query
```

### 7. Testing

```
// Kiểm tra trước khi release:
1. Theme Check plugin - pass tất cả REQUIRED
2. Theme Unit Test data - không bị lỗi
3. PHP error log - không có warnings/notices
4. Browser DevTools Console - không có JS errors
5. Responsive test - 320px đến 1920px
6. Accessibility test - keyboard navigation + screen reader
7. Performance test - Google PageSpeed Insights > 90
8. Cross-browser - Chrome, Firefox, Safari, Edge
```

### 8. Documentation

```php
// PHPDoc cho mỗi function
/**
 * Hiển thị thông tin meta của bài viết.
 *
 * @since 1.0.0
 *
 * @param int  $post_id  ID của bài viết. Mặc định: bài viết hiện tại.
 * @param bool $show_author Có hiển thị tên tác giả không. Mặc định: true.
 * @return void
 */
function developer_posted_on( $post_id = 0, $show_author = true ) {
    // ...
}

// Inline comments cho logic phức tạp
// Giải thích TẠI SAO, không phải CÁI GÌ
```

---

**Đây là bài cuối trong series Theme WordPress. Sau khi hoàn thành 7 bài này, bạn đã có kiến thức đầy đủ để:**

1. Tạo theme WordPress từ đầu
2. Hiểu Template Hierarchy và The Loop
3. Sử dụng WP_Query để lấy dữ liệu
4. Tạo menus, widgets, sidebars
5. Dùng Customizer API cho tùy chỉnh
6. Hiểu Block Theme và Full Site Editing
7. Áp dụng các kỹ thuật nâng cao (Child Theme, WooCommerce, Performance, i18n, Accessibility)

**Tài liệu tham khảo:**
- [WordPress Theme Developer Handbook](https://developer.wordpress.org/themes/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [Theme Review Requirements](https://make.wordpress.org/themes/handbook/review/)
