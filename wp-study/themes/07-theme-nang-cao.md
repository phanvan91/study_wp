# Theme WordPress Nang Cao

## Muc Luc

1. [Child Theme](#1-child-theme)
2. [Theme voi WooCommerce Support](#2-woocommerce-support)
3. [Responsive Design trong Theme](#3-responsive-design)
4. [Accessibility (a11y)](#4-accessibility)
5. [Performance Optimization](#5-performance)
6. [Internationalization (i18n)](#6-i18n)
7. [Theme Unit Test](#7-theme-unit-test)
8. [Theme Check Plugin](#8-theme-check)
9. [Packaging va Submit len WordPress.org](#9-packaging)
10. [Custom Page Templates](#10-custom-page-templates)
11. [Theme Options vs Customizer](#11-theme-options-vs-customizer)
12. [Best Practices tong hop](#12-best-practices)

---

## 1. Child Theme

### Tai sao can Child Theme?

Khi ban muon **tuy chinh 1 theme co san** (Astra, GeneratePress, TwentyTwentyFour...) ma **khong bi mat thay doi khi theme update**.

```
Khi theme cha update:
- File trong theme cha bi GHI DE het
- Code ban them truc tiep vao theme cha se MAT

Giai phap: Child Theme
- Code tuy chinh o child theme KHONG bi anh huong khi theme cha update
- Giong nhu class ke thua (extends) trong OOP
```

### So sanh voi Laravel:

```php
// LARAVEL
// - Vendor package update khong anh huong published files
// php artisan vendor:publish --tag=views
// Cac views da publish nam trong resources/views/vendor/ - khong bi ghi de

// WORDPRESS
// - Child theme = "published views" cua Laravel
// - Override template ma khong sua theme goc
```

### Cach tao Child Theme:

```
Buoc 1: Tao thu muc child theme
wp-content/themes/developer-starter-child/

Buoc 2: Tao style.css
Buoc 3: Tao functions.php
Buoc 4: (Tuy chon) Copy va sua template files tu theme cha
```

### style.css cua Child Theme:

```css
/*
Theme Name:        Developer Starter Child
Theme URI:         https://example.com/developer-starter-child
Description:       Child theme cua Developer Starter. Dung de tuy chinh ma khong
                   anh huong den theme goc khi update.
Author:            Nguyen Van A
Author URI:        https://example.com
Template:          developer-starter
                   ^^ TEN THU MUC cua theme cha (BAT BUOC, phai chinh xac)
Version:           1.0.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       developer-starter-child
*/

/* === Custom CSS o day === */

/* Override mau primary */
:root {
    --color-primary: #e74c3c;  /* Doi tu xanh sang do */
}

/* Them style rieng cho child theme */
.site-header {
    border-bottom: 3px solid var(--color-primary);
}

.entry-title a:hover {
    color: var(--color-primary);
}
```

### functions.php cua Child Theme:

```php
<?php
/**
 * Child Theme Functions
 *
 * QUAN TRONG:
 * - functions.php cua child theme duoc load TRUOC theme cha
 * - Khong ghi de functions.php cha, ma BO SUNG
 * - Dung child functions.php de:
 *   1. Enqueue styles
 *   2. Them/sua functions
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
    // 1. Load CSS cua theme cha
    wp_enqueue_style(
        'developer-starter-parent-style',
        get_template_directory_uri() . '/style.css',
        // get_template_directory_uri() luon tro den theme CHA
        array(),
        wp_get_theme( 'developer-starter' )->get( 'Version' )
    );

    // 2. Load CSS cua child theme (tu dong load SAU theme cha)
    wp_enqueue_style(
        'developer-starter-child-style',
        get_stylesheet_uri(),
        // get_stylesheet_uri() tro den child theme
        array( 'developer-starter-parent-style' ), // Phu thuoc vao parent
        wp_get_theme()->get( 'Version' )
    );

    // 3. Them CSS rieng cua child
    wp_enqueue_style(
        'developer-child-custom',
        get_stylesheet_directory_uri() . '/assets/css/custom.css',
        array( 'developer-starter-child-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'developer_child_enqueue_styles' );

/**
 * Override functions cua theme cha
 *
 * Neu theme cha dung function_exists() de dinh nghia ham,
 * ban co the GHI DE ham do trong child theme
 */

// Theme cha (functions.php):
// if ( ! function_exists( 'developer_starter_posted_on' ) ) {
//     function developer_starter_posted_on() { ... }
// }

// Child theme - ghi de:
function developer_starter_posted_on() {
    // Custom version - them icon truoc ngay
    printf(
        '<span class="posted-on">&#128197; %s</span> | <span class="byline">&#9998; %s</span>',
        get_the_date(),
        get_the_author()
    );
}

/**
 * Them/sua features
 */

// Them post format khong co trong theme cha
function developer_child_setup() {
    add_theme_support( 'post-formats', array( 'aside', 'gallery', 'video', 'quote' ) );

    // Them kich thuoc anh moi
    add_image_size( 'child-hero', 1920, 800, true );
}
add_action( 'after_setup_theme', 'developer_child_setup', 11 );
// Priority 11 = chay SAU theme cha (priority 10)

/**
 * Go bo actions/filters cua theme cha
 */
function developer_child_remove_parent_actions() {
    // Go bo ham cua theme cha
    // remove_action( 'wp_head', 'developer_starter_some_function' );

    // Thay doi excerpt length
    remove_filter( 'excerpt_length', 'developer_starter_excerpt_length' );
}
add_action( 'after_setup_theme', 'developer_child_remove_parent_actions' );

// Them excerpt length moi
function developer_child_excerpt_length( $length ) {
    return 40; // 40 tu thay vi 30 tu cua theme cha
}
add_filter( 'excerpt_length', 'developer_child_excerpt_length' );

/**
 * Them widget area moi (khong co trong theme cha)
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
De override 1 template file cua theme cha:
1. Copy file tu theme cha sang child theme (CUNG ten, CUNG duong dan)
2. Sua file trong child theme

Vi du:
Theme cha: developer-starter/single.php
Child:     developer-starter-child/single.php  <-- File nay se duoc uu tien

Theme cha: developer-starter/template-parts/content.php
Child:     developer-starter-child/template-parts/content.php

LUU Y:
- Chi copy nhung file can sua
- Khong copy tat ca file (kho maintain khi theme cha update)
- functions.php KHONG override, no BO SUNG
```

### Cau truc Child Theme:

```
developer-starter-child/
|-- style.css               # BAT BUOC: co "Template:" header
|-- functions.php           # BAT BUOC: enqueue styles
|-- screenshot.png          # Tuy chon: anh preview
|
|-- # Chi copy file can override:
|-- single.php              # Override trang bai viet
|-- template-parts/
|   |-- content-single.php  # Override noi dung bai viet
|
|-- assets/
|   |-- css/
|   |   |-- custom.css      # CSS rieng
|   |-- js/
|       |-- custom.js        # JS rieng
```

---

## 2. WooCommerce Support

### Khai bao WooCommerce support:

```php
<?php
/**
 * Them WooCommerce support vao theme
 * Dat trong functions.php
 */
function developer_woocommerce_support() {
    // 1. Khai bao ho tro WooCommerce
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

    // 2. Ho tro Product Gallery features
    add_theme_support( 'wc-product-gallery-zoom' );      // Zoom hinh khi hover
    add_theme_support( 'wc-product-gallery-lightbox' );   // Lightbox khi click
    add_theme_support( 'wc-product-gallery-slider' );     // Slider hinh anh
}
add_action( 'after_setup_theme', 'developer_woocommerce_support' );

/**
 * Override WooCommerce wrapper
 * Mac dinh WooCommerce dung <main> cua no, co the khong khop voi theme
 */

// Bo wrapper mac dinh cua WooCommerce
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

// Them wrapper cua theme
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
 * Tuy chinh so san pham moi trang
 */
function developer_woocommerce_products_per_page( $cols ) {
    return 12; // 12 san pham moi trang
}
add_filter( 'loop_shop_per_page', 'developer_woocommerce_products_per_page' );

/**
 * Tuy chinh so cot grid
 */
function developer_woocommerce_columns( $columns ) {
    return 4; // 4 cot
}
add_filter( 'loop_shop_columns', 'developer_woocommerce_columns' );

/**
 * Them CSS/JS cho WooCommerce
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
De override template WooCommerce:
1. Copy file tu: wp-content/plugins/woocommerce/templates/
2. Dan vao:      wp-content/themes/developer-theme/woocommerce/

Vi du:
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
 * Mobile First: Viet CSS cho mobile truoc, sau do them cho man hinh lon
 */

/* === BASE: Mobile (0 - 767px) === */
.container {
    width: 100%;
    padding: 0 1rem;
    margin: 0 auto;
}

.content-area {
    display: flex;
    flex-direction: column; /* Stack tren mobile */
    gap: 1.5rem;
}

.posts-grid {
    display: grid;
    grid-template-columns: 1fr; /* 1 cot tren mobile */
    gap: 1.5rem;
}

.site-header .header-inner {
    flex-direction: column;
    gap: 1rem;
}

/* === TABLET: 768px tro len === */
@media (min-width: 768px) {
    .container {
        max-width: 720px;
        padding: 0 1.5rem;
    }

    .posts-grid {
        grid-template-columns: repeat(2, 1fr); /* 2 cot */
    }

    .site-header .header-inner {
        flex-direction: row;
        justify-content: space-between;
    }
}

/* === DESKTOP: 1024px tro len === */
@media (min-width: 1024px) {
    .container {
        max-width: 960px;
    }

    .content-area {
        flex-direction: row; /* Noi dung + Sidebar canh nhau */
    }

    .main-content {
        flex: 1;
    }

    .sidebar {
        width: 300px;
        flex-shrink: 0;
    }

    .posts-grid {
        grid-template-columns: repeat(3, 1fr); /* 3 cot */
    }
}

/* === LARGE DESKTOP: 1200px tro len === */
@media (min-width: 1200px) {
    .container {
        max-width: var(--max-width, 1200px);
    }

    .posts-grid {
        grid-template-columns: repeat(4, 1fr); /* 4 cot */
    }
}
```

### Responsive Images:

```php
<?php
// WordPress tu dong tao srcset va sizes cho anh
// Dam bao thiet lap image sizes trong functions.php:

add_image_size( 'developer-sm', 400, 300, true );
add_image_size( 'developer-md', 800, 600, true );
add_image_size( 'developer-lg', 1200, 630, true );

// Khi dung the_post_thumbnail(), WordPress tu dong them srcset:
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
    // Them class de CSS targeting de hon
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

### Cac yeu cau co ban:

```php
<?php
// === 1. SKIP LINK ===
// Cho phep nguoi dung ban phim nhay thang den noi dung chinh
?>
<body <?php body_class(); ?>>
    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e( 'Chuyen den noi dung chinh', 'developer-theme' ); ?>
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
<nav role="navigation" aria-label="<?php esc_attr_e( 'Menu Chinh', 'developer-theme' ); ?>">...</nav>
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
        aria-label="<?php esc_attr_e( 'Mo menu', 'developer-theme' ); ?>">
    <span class="hamburger-icon"></span>
</button>

<!-- Search form -->
<form role="search" aria-label="<?php esc_attr_e( 'Tim kiem tren trang', 'developer-theme' ); ?>">
    <label for="search-input" class="screen-reader-text">
        <?php esc_html_e( 'Tim kiem:', 'developer-theme' ); ?>
    </label>
    <input type="search" id="search-input"
           placeholder="<?php esc_attr_e( 'Tim kiem...', 'developer-theme' ); ?>"
           value="<?php echo get_search_query(); ?>"
           name="s"
           aria-label="<?php esc_attr_e( 'Tu khoa tim kiem', 'developer-theme' ); ?>" />
    <button type="submit" aria-label="<?php esc_attr_e( 'Tim kiem', 'developer-theme' ); ?>">
        <span class="screen-reader-text"><?php esc_html_e( 'Tim kiem', 'developer-theme' ); ?></span>
        <!-- SVG icon -->
    </button>
</form>

<?php
// === 4. FOCUS STYLES ===
?>
<style>
/* KHONG BAO GIO xoa outline khi focus */
/* SAI: */ /* *:focus { outline: none; } */

/* DUNG: Tao focus style dep hon */
a:focus,
button:focus,
input:focus,
select:focus,
textarea:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* Focus visible (chi hien khi dung ban phim, khong hien khi click chuot) */
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
// Luon co alt text cho anh
the_post_thumbnail( 'large', array(
    'alt' => get_the_title(), // Hoac mo ta cu the hon
) );

// === 6. COLOR CONTRAST ===
// Dam bao ty le tuong phan toi thieu:
// - Text binh thuong: 4.5:1
// - Text lon (18px+ bold): 3:1
// Dung tool: https://webaim.org/resources/contrastchecker/

// === 7. HEADING HIERARCHY ===
// Dung thu tu heading dung (khong nhay bac)
// h1 -> h2 -> h3 (DUNG)
// h1 -> h3 -> h2 (SAI)
// Chi co 1 h1 moi trang
```

---

## 5. Performance Optimization

### CSS/JS Optimization:

```php
<?php
/**
 * Performance: Toi uu CSS va JS
 */

// === 1. Defer va Async cho scripts ===
function developer_script_attributes( $tag, $handle, $src ) {
    // Them defer cho scripts cu the
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

    // Preconnect Google Fonts (neu dung)
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'developer_preload_resources', 1 );

// === 3. Go bo scripts/styles khong can ===
function developer_remove_unnecessary() {
    // Go bo emoji scripts
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );

    // Go bo wp-embed.js (neu khong can embed bai viet WP khac)
    wp_deregister_script( 'wp-embed' );

    // Go bo jQuery migrate (neu theme khong can)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', false, array( 'jquery-core' ), null, true );
    }

    // Conditional load: Chi load CF7 CSS/JS tren trang co form
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
// === 1. Lazy Loading (WP 5.5+ tu dong them loading="lazy") ===
// Mac dinh da bat, khong can lam gi them

// Neu muon tat lazy loading cho anh dau tien (LCP):
function developer_disable_lazy_load_first_image( $attr, $attachment, $size ) {
    // Disable lazy load cho anh dau tien trong loop
    static $counter = 0;
    $counter++;

    if ( $counter === 1 && is_home() || is_front_page() ) {
        $attr['loading'] = 'eager';       // Load ngay, khong lazy
        $attr['fetchpriority'] = 'high';  // Uu tien cao
    }

    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'developer_disable_lazy_load_first_image', 10, 3 );

// === 2. WebP support ===
// WordPress 5.8+ ho tro WebP tu dong
// Chi can upload WebP images, WP se xu ly

// === 3. Responsive images voi srcset ===
// WordPress tu dong them srcset cho anh upload qua Media Library
// Dam bao co nhieu image sizes:
add_image_size( 'developer-sm', 400, 0, false );
add_image_size( 'developer-md', 800, 0, false );
add_image_size( 'developer-lg', 1200, 0, false );
```

### Caching Strategies:

```php
<?php
// === Transients API cho cache data ===

/**
 * Cache ket qua WP_Query voi Transients
 * Giam so query den database
 */
function developer_get_featured_posts() {
    // Kiem tra cache truoc
    $cached = get_transient( 'developer_featured_posts' );

    if ( false !== $cached ) {
        return $cached; // Tra ve tu cache
    }

    // Khong co cache -> chay query
    $query = new WP_Query( array(
        'post_type'      => 'post',
        'posts_per_page' => 5,
        'meta_key'       => 'is_featured',
        'meta_value'     => '1',
    ) );

    $posts = $query->posts;

    // Luu vao cache (12 gio)
    set_transient( 'developer_featured_posts', $posts, 12 * HOUR_IN_SECONDS );

    wp_reset_postdata();

    return $posts;
}

// Xoa cache khi co bai viet moi hoac cap nhat
function developer_clear_featured_cache( $post_id ) {
    delete_transient( 'developer_featured_posts' );
}
add_action( 'save_post', 'developer_clear_featured_cache' );
```

---

## 6. Internationalization (i18n)

### Cac ham dich:

```php
<?php
/**
 * Cac ham i18n cua WordPress
 *
 * MOI chuoi text hien thi cho nguoi dung PHAI dung ham dich
 * Tham so thu 2 luon la Text Domain (phai trung voi ten thu muc theme)
 */

// === 1. __() va _e() ===
// __() : Tra ve string da dich (khong echo)
$text = __( 'Xin chao', 'developer-theme' );

// _e() : Echo string da dich
_e( 'Xin chao', 'developer-theme' );

// === 2. esc_html__() va esc_html_e() ===
// Giong tren nhung co escape HTML
echo esc_html__( 'Xin chao', 'developer-theme' );
esc_html_e( 'Xin chao', 'developer-theme' );

// === 3. esc_attr__() va esc_attr_e() ===
// Escape cho HTML attributes
echo '<input placeholder="' . esc_attr__( 'Tim kiem...', 'developer-theme' ) . '">';

// === 4. sprintf() voi dich ===
printf(
    /* translators: %s: tac gia */
    esc_html__( 'Viet boi %s', 'developer-theme' ),
    get_the_author()
);

// Vi du phuc tap hon:
printf(
    /* translators: 1: so binh luan, 2: ten bai viet */
    esc_html__( '%1$s binh luan cho "%2$s"', 'developer-theme' ),
    number_format_i18n( get_comments_number() ),
    get_the_title()
);

// === 5. _n() - So nhieu ===
printf(
    /* translators: %s: so bai viet */
    esc_html( _n(
        '%s bai viet',      // So it (1)
        '%s bai viet',      // So nhieu (2+)
        $count,             // So luong
        'developer-theme'   // Text domain
    ) ),
    number_format_i18n( $count )
);

// === 6. _x() - Context ===
// Khi cung 1 tu co nhieu nghia
$title = _x( 'Post', 'post type name', 'developer-theme' );  // Bai viet
$verb  = _x( 'Post', 'verb: to post', 'developer-theme' );   // Dang bai

// === 7. _nx() - So nhieu voi context ===
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

// === 8. Chuoi co HTML ===
printf(
    wp_kses(
        /* translators: %s: URL trang lien he */
        __( 'Lien he voi chung toi <a href="%s">tai day</a>.', 'developer-theme' ),
        array( 'a' => array( 'href' => array() ) )
    ),
    esc_url( home_url( '/contact' ) )
);

// === 9. number_format_i18n() ===
echo number_format_i18n( 1234567 ); // 1,234,567 (en) hoac 1.234.567 (de)

// === 10. date_i18n() ===
echo date_i18n( get_option( 'date_format' ) ); // Ngay theo dinh dang va ngon ngu
```

### Load text domain:

```php
<?php
// Trong functions.php
function developer_load_textdomain() {
    load_theme_textdomain(
        'developer-theme',                                    // Text domain
        get_template_directory() . '/languages'                // Thu muc chua file .mo
    );
}
add_action( 'after_setup_theme', 'developer_load_textdomain' );
```

### Tao file dich:

```bash
# Buoc 1: Cai WP-CLI (neu chua co)
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Buoc 2: Tao file .pot (template)
cd wp-content/themes/developer-theme/
wp i18n make-pot . languages/developer-theme.pot --domain=developer-theme

# Buoc 3: Tao file .po cho ngon ngu cu the
# Dung tool: Poedit (https://poedit.net/)
# Mo file .pot, dich cac chuoi, luu thanh vi.po va vi.mo

# Hoac dung WP-CLI:
wp i18n make-json languages/ --no-purge
```

### File structure:

```
languages/
|-- developer-theme.pot    # Template (source of truth)
|-- vi.po                  # Vietnamese translations (text)
|-- vi.mo                  # Vietnamese translations (compiled binary)
|-- en_US.po               # English (neu can)
|-- en_US.mo
```

---

## 7. Theme Unit Test

### Theme Unit Test Data:

```
WordPress cung cap bo du lieu test de kiem tra theme:
1. Tai ve: https://github.com/WPTT/theme-unit-test
2. Import: Tools > Import > WordPress
3. Kiem tra tat ca cac trang, post types, layouts

Bo du lieu bao gom:
- Bai viet voi nhieu dinh dang (titles dai, ngan, co special chars)
- Pages voi nested hierarchy
- Comments (nhieu cap)
- Categories va Tags
- Featured Images voi nhieu kich thuoc
- Post Formats
- Edge cases (empty content, very long content...)
```

### Checklist kiem tra:

```
LAYOUT:
[ ] Trang chu hien thi dung
[ ] Single post hien thi dung (voi/khong co featured image)
[ ] Page hien thi dung (voi/khong co parent page)
[ ] Archive, Category, Tag hien thi dung
[ ] Search results hien thi dung
[ ] 404 page hien thi dung
[ ] Sidebar hien thi/an dung
[ ] Footer widgets hien thi dung

NHI DUNG:
[ ] Title dai (>100 ky tu) khong bi tran
[ ] Noi dung voi tat ca HTML tags (h1-h6, ul, ol, table, blockquote, code...)
[ ] Images voi alignleft, alignright, aligncenter, alignnone, alignwide, alignfull
[ ] Galleries voi nhieu hinh
[ ] Embedded content (YouTube, Twitter, etc.)
[ ] Password protected post
[ ] Sticky post

RESPONSIVE:
[ ] Desktop (1200px+)
[ ] Tablet (768px - 1023px)
[ ] Mobile (< 768px)
[ ] Menu mobile hoat dong

NAVIGATION:
[ ] Menu voi nhieu cap (3+ levels)
[ ] Menu item dai
[ ] Breadcrumbs chinh xac

BINH LUAN:
[ ] Comment form hien thi
[ ] Nested comments (3+ levels)
[ ] Comment pagination
[ ] Trackbacks/Pingbacks

ACCESSIBILITY:
[ ] Tab navigation qua menu
[ ] Skip link hoat dong
[ ] Focus styles ro rang
[ ] Alt text cho images
[ ] ARIA labels dung

PERFORMANCE:
[ ] Khong co loi console (JS errors)
[ ] Images lazy loaded
[ ] CSS/JS load o dung vi tri (head/footer)
```

---

## 8. Theme Check

### Cai dat Theme Check Plugin:

```
1. Admin > Plugins > Add New
2. Tim "Theme Check"
3. Install va Activate
4. Vao Admin > Appearance > Theme Check
5. Chon theme va click "Check it!"
```

### Cac quy tac Theme Check kiem tra:

```
REQUIRED (bat buoc):
- Co style.css voi Theme Name
- Co index.php
- add_theme_support('automatic-feed-links')
- wp_head() trong header
- wp_footer() truoc </body>
- wp_enqueue_style/script (khong hard-code)
- body_class() trong <body>
- post_class() trong loop
- comment_form() hoac comments_template()
- wp_link_pages()
- Khong co file deprecated nhu timthumb.php

RECOMMENDED (khuyen dung):
- add_theme_support('title-tag')
- add_theme_support('custom-logo')
- Tat ca text dung i18n functions
- Prefix tat ca functions va classes
- Khong dung PHP error suppression (@)
- Khong co hard-coded links
- Dung esc_* functions cho output
```

### Fix cac loi thuong gap:

```php
<?php
// LOI: INFO: Could not find wp_link_pages.
// FIX: Them vao single.php va page.php:
wp_link_pages( array(
    'before' => '<div class="page-links">' . __( 'Trang:', 'developer-theme' ),
    'after'  => '</div>',
) );

// LOI: REQUIRED: Could not find add_theme_support( 'automatic-feed-links' )
// FIX: Them vao functions.php:
add_theme_support( 'automatic-feed-links' );

// LOI: REQUIRED: Could not find body_class call
// FIX: header.php phai co:
<body <?php body_class(); ?>>

// LOI: REQUIRED: Could not find post_class
// FIX: Trong loop:
<article <?php post_class(); ?>>

// LOI: WARNING: Found hard-coded link
// FIX: Thay link cu the bang ham:
// SAI: <a href="http://example.com/contact">
// DUNG: <a href="<?php echo esc_url( home_url('/contact') ); ?>">

// LOI: WARNING: file not sanitized
// FIX: Escape tat ca output
echo esc_html( $variable );
echo esc_url( $url );
echo esc_attr( $attribute );
```

---

## 9. Packaging va Submit len WordPress.org

### Chuan bi theme:

```
1. KIEM TRA:
   - Chay Theme Check plugin (pass tat ca REQUIRED)
   - Chay Theme Unit Test data
   - Test tren nhieu trinh duyet (Chrome, Firefox, Safari, Edge)
   - Test responsive (Mobile, Tablet, Desktop)
   - Test accessibility (keyboard navigation, screen reader)

2. FILE CAN CO:
   - style.css (voi day du header)
   - index.php
   - functions.php
   - screenshot.png (1200x900, under 2MB)
   - readme.txt (khuyen dung)

3. FILE KHONG DUOC CO:
   - File .git, .gitignore
   - node_modules/
   - .sass-cache/
   - Source files (SCSS, LESS, TypeScript)
   - IDE files (.vscode, .idea)
   - OS files (.DS_Store, Thumbs.db)

4. LICENSE:
   - Theme PHAI la GPL v2 hoac tuong thich
   - Tat ca assets (fonts, images, icons) cung phai GPL compatible
   - Ghi ro license trong style.css va readme.txt
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

Theme WordPress cho developer, toi uu cho hieu nang va SEO.

== Description ==

Developer Theme la theme WordPress don gian, nhanh, va de tuy chinh.
Thiet ke cho blog, portfolio, va website cong ty.

Tinh nang:
* Responsive design
* Custom logo va colors
* Widget areas (sidebar + 3 footer columns)
* Translation ready
* Block Editor support

== Installation ==

1. Vao Appearance > Themes > Add New
2. Tim "Developer Theme"
3. Click Install va Activate

== Changelog ==

= 1.0.0 =
* Phien ban dau tien

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

### Dong goi va submit:

```bash
# Buoc 1: Don dep
cd wp-content/themes/developer-theme/

# Xoa file khong can
rm -rf node_modules/
rm -rf .git/
rm -rf .sass-cache/
rm -f .gitignore
rm -f package.json
rm -f package-lock.json
rm -f composer.json
rm -f composer.lock

# Buoc 2: Tao zip
cd ..
zip -r developer-theme.zip developer-theme/ \
    -x "developer-theme/.git/*" \
    -x "developer-theme/node_modules/*" \
    -x "developer-theme/.DS_Store"

# Buoc 3: Submit
# 1. Tao account tren WordPress.org
# 2. Vao https://wordpress.org/themes/upload/
# 3. Upload file zip
# 4. Doi review (thuong 1-3 thang)

# Hoac dung SVN (sau khi duoc approved):
# svn co https://themes.svn.wordpress.org/developer-theme/
# Copy files vao thu muc trunk/
# svn ci -m "Version 1.0.0"
```

---

## 10. Custom Page Templates

### Tao Custom Page Templates:

```php
<?php
/**
 * Template Name: Full Width - Khong Co Sidebar
 * Template Post Type: page, post
 *
 * Dong "Template Name:" khai bao ten template
 * Dong "Template Post Type:" cho phep chon template cho post types nao
 *
 * File nay co the dat o:
 * - Thu muc goc: full-width.php
 * - Thu muc con: page-templates/full-width.php
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
 * Landing page: khong co header/footer cua theme,
 * chi co noi dung page
 */

// KHONG goi get_header() - dung header rieng
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        /* Styles rieng cho landing page */
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
 * Template Name: Sidebar Trai
 * Template Post Type: page
 */

get_header();
?>

<main id="primary" class="site-main sidebar-left-page">
    <div class="container">
        <div class="content-area sidebar-left">
            <!-- Sidebar o ben trai -->
            <?php get_sidebar(); ?>

            <!-- Noi dung o ben phai -->
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

### Kiem tra template dang dung:

```php
<?php
// Kiem tra bai viet/trang dang dung template nao
if ( is_page_template( 'page-templates/full-width.php' ) ) {
    // Dang dung template Full Width
    echo 'class="no-sidebar"';
}

// Lay ten template file dang dung
$template = get_page_template_slug();
// Tra ve: 'page-templates/full-width.php' hoac '' (mac dinh)

// Dung trong body_class filter
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

### So sanh:

```
CUSTOMIZER (khuyen dung):
+ Live preview
+ API chuan WordPress
+ Tich hop san sanitize
+ Non-destructive (co default values)
+ Responsive preview
- Gioi han ve UI (khong lam duoc dashboard phuc tap)
- Khong phu hop cho settings lon

THEME OPTIONS PAGE (tu tao):
+ Linh hoat ve UI
+ Phu hop cho settings phuc tap
+ Co the dung tabs, groups
- Khong co live preview
- Tu code sanitize
- Tu code save/load

KHUYEN NGHI:
1. Dung Customizer cho APPEARANCE settings (colors, fonts, layout)
2. Dung Options page cho FUNCTIONALITY settings (neu can)
3. Hoac dung plugin nhu ACF/CMB2 cho options page
```

### Theme Options Page don gian (neu can):

```php
<?php
/**
 * Theme Options Page - Cach tao nhanh
 * Chi dung khi Customizer khong du
 */

// 1. Them menu trong admin
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

// 2. Dang ky settings
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
        __( 'Cai Dat Chung', 'developer-theme' ),
        function() {
            echo '<p>' . esc_html__( 'Cai dat chung cho theme.', 'developer-theme' ) . '</p>';
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
            echo '<p class="description">' . esc_html__( 'Nhap Google Analytics Measurement ID.', 'developer-theme' ) . '</p>';
        },
        'developer-options',
        'developer_general_section'
    );

    // Field: Custom code before </head>
    add_settings_field(
        'head_code',
        __( 'Code Truoc &lt;/head&gt;', 'developer-theme' ),
        function() {
            $options = get_option( 'developer_theme_options' );
            $value = isset( $options['head_code'] ) ? $options['head_code'] : '';
            echo '<textarea name="developer_theme_options[head_code]" rows="5" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
            echo '<p class="description">' . esc_html__( 'Code se duoc them truoc the dong </head>.', 'developer-theme' ) . '</p>';
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

    // Hien thi thong bao luu thanh cong
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error(
            'developer_messages',
            'developer_message',
            __( 'Cai dat da duoc luu.', 'developer-theme' ),
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
            submit_button( __( 'Luu Cai Dat', 'developer-theme' ) );
            ?>
        </form>
    </div>
    <?php
}

// 6. Su dung options trong template
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

### 1. Bao mat

```php
// Escape MỌOI output
echo esc_html( $text );
echo esc_attr( $attr );
echo esc_url( $url );
echo wp_kses_post( $html );

// Khong cho truy cap truc tiep file PHP
if ( ! defined( 'ABSPATH' ) ) exit;

// Sanitize tat ca input
sanitize_text_field( $input );
absint( $number );
sanitize_email( $email );

// Nonce cho forms
wp_nonce_field( 'action_name', 'nonce_field' );
wp_verify_nonce( $_POST['nonce_field'], 'action_name' );
```

### 2. Prefix

```php
// LUON prefix tat ca: functions, classes, constants, hooks
function developer_theme_setup() {}
class Developer_Theme_Walker {}
define( 'DEVELOPER_THEME_VERSION', '1.0.0' );

// Text domain = ten thu muc theme
__( 'text', 'developer-theme' );
```

### 3. Coding Standards

```php
// Theo WordPress Coding Standards
// - Tab indentation (khong spaces)
// - Spaces trong ngoac: if ( $condition ) { }
// - Yoda conditions: if ( true === $var )
// - === thay vi ==
// - Single quotes cho string khong co bien
// - PHPDoc cho moi function
```

### 4. Performance

```php
// Load JS o footer
wp_enqueue_script( 'handle', $url, array(), $ver, true );

// Conditional loading
if ( is_page( 'contact' ) ) {
    wp_enqueue_style( 'contact-css', ... );
}

// no_found_rows cho queries khong can pagination
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
// Tach code thanh cac file nho, co to chuc
functions.php          -- Bootstrap, includes
inc/customizer.php     -- Customizer settings
inc/template-tags.php  -- Helper functions cho templates
inc/widgets.php        -- Custom widgets
inc/walker.php         -- Custom menu walkers
template-parts/        -- Reusable template components

// Dung get_template_part() thay vi include
get_template_part( 'template-parts/content', get_post_type() );

// wp_reset_postdata() sau custom queries
wp_reset_postdata();

// Khong bao gio dung query_posts()
// Dung pre_get_posts hoac new WP_Query
```

### 7. Testing

```
// Kiem tra truoc khi release:
1. Theme Check plugin - pass tat ca REQUIRED
2. Theme Unit Test data - khong bi loi
3. PHP error log - khong co warnings/notices
4. Browser DevTools Console - khong co JS errors
5. Responsive test - 320px den 1920px
6. Accessibility test - keyboard navigation + screen reader
7. Performance test - Google PageSpeed Insights > 90
8. Cross-browser - Chrome, Firefox, Safari, Edge
```

### 8. Documentation

```php
// PHPDoc cho moi function
/**
 * Hien thi thong tin meta cua bai viet.
 *
 * @since 1.0.0
 *
 * @param int  $post_id  ID cua bai viet. Mac dinh: bai viet hien tai.
 * @param bool $show_author Co hien thi ten tac gia khong. Mac dinh: true.
 * @return void
 */
function developer_posted_on( $post_id = 0, $show_author = true ) {
    // ...
}

// Inline comments cho logic phuc tap
// Gioi thich TAI SAO, khong phai CAI GI
```

---

**Day la bai cuoi trong series Theme WordPress. Sau khi hoan thanh 7 bai nay, ban da co kien thuc day du de:**

1. Tao theme WordPress tu dau
2. Hieu Template Hierarchy va The Loop
3. Su dung WP_Query de lay du lieu
4. Tao menus, widgets, sidebars
5. Dung Customizer API cho tuy chinh
6. Hieu Block Theme va Full Site Editing
7. Ap dung cac ky thuat nang cao (Child Theme, WooCommerce, Performance, i18n, Accessibility)

**Tai lieu tham khao:**
- [WordPress Theme Developer Handbook](https://developer.wordpress.org/themes/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [Theme Review Requirements](https://make.wordpress.org/themes/handbook/review/)
