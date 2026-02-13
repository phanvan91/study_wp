# Theme WordPress Co Ban - Huong Dan Day Du

## Muc Luc

1. [Theme la gi, tai sao can tu tao theme](#1-theme-la-gi)
2. [Yeu cau toi thieu: style.css + index.php](#2-yeu-cau-toi-thieu)
3. [Style.css Header chi tiet](#3-stylecss-header)
4. [Cau truc thu muc theme day du](#4-cau-truc-thu-muc)
5. [functions.php - Vai tro va cach su dung](#5-functionsphp)
6. [Theme Supports](#6-theme-supports)
7. [Enqueue Styles va Scripts](#7-enqueue-styles-va-scripts)
8. [Tao theme Hello World tu dau](#8-tao-theme-hello-world)
9. [So sanh voi Laravel](#9-so-sanh-voi-laravel)
10. [Best Practices](#10-best-practices)

---

## 1. Theme la gi

### Theme la gi?

Theme trong WordPress la mot tap hop cac file (PHP, CSS, JS, hinh anh) quyet dinh **giao dien** va **cach hien thi noi dung** cua website. Theme khong anh huong den du lieu (data), chi anh huong den cach du lieu duoc trinh bay.

### Tai sao can tu tao theme?

| Ly do | Giai thich |
|-------|-----------|
| **Tuy chinh hoan toan** | Theme co san (Astra, GeneratePress) bi gioi han boi options co san |
| **Hieu nang tot hon** | Theme tu viet chi co nhung gi can thiet, khong bi bloat |
| **Hoc sau WordPress** | Hieu cach WP hoat dong tu ben trong |
| **Kinh doanh** | Ban theme tren ThemeForest, WordPress.org |
| **Du an khach hang** | Tao theme rieng cho tung du an |

### So sanh voi Laravel de hieu

```
Laravel:
  resources/views/     --> Noi chua giao dien
  layouts/app.blade.php --> Layout chinh
  components/          --> Components tai su dung

WordPress:
  wp-content/themes/my-theme/  --> Noi chua giao dien
  header.php + footer.php      --> Layout chinh
  template-parts/              --> Components tai su dung
```

**Diem khac biet lon:** Trong Laravel, ban tu dinh nghia routes va controllers. Trong WordPress, he thong tu dong chon template dua tren URL (Template Hierarchy).

---

## 2. Yeu cau toi thieu

Mot theme WordPress chi can **2 file** de hoat dong:

### style.css (bat buoc)

```css
/*
Theme Name: My First Theme
*/
```

### index.php (bat buoc)

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

Chi can 2 file nay, ban da co mot theme hop le co the kich hoat trong WordPress Admin.

**Vi tri dat file:**
```
wp-content/
  themes/
    my-first-theme/     <-- Tao thu muc nay
      style.css         <-- File 1
      index.php         <-- File 2
```

---

## 3. Style.css Header

File `style.css` co mot phan header dac biet (CSS comment) de WordPress nhan dien theme:

```css
/*
Theme Name:        Developer Theme
Theme URI:         https://example.com/developer-theme
Author:            Nguyen Van A
Author URI:        https://example.com
Description:       Theme WordPress tu tao cho developer, toi uu cho hieu nang va SEO.
                   Ho tro WooCommerce, Gutenberg, va responsive design.
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

/* === CSS code bat dau tu day === */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}
```

### Giai thich tung truong:

| Truong | Bat buoc | Mo ta |
|--------|----------|-------|
| `Theme Name` | Co | Ten theme hien thi trong Admin |
| `Theme URI` | Khong | URL trang gioi thieu theme |
| `Author` | Khong | Ten tac gia |
| `Author URI` | Khong | Website tac gia |
| `Description` | Khong | Mo ta ngan ve theme |
| `Version` | Khong | Phien ban hien tai (theo semver) |
| `Requires at least` | Khong | Phien ban WP toi thieu |
| `Tested up to` | Khong | Phien ban WP da test |
| `Requires PHP` | Khong | Phien ban PHP toi thieu |
| `License` | Khong | Loai license (nen la GPL) |
| `Text Domain` | Khong | ID cho da ngon ngu (phai trung voi ten thu muc) |
| `Tags` | Khong | The de tim kiem tren WordPress.org |
| `Domain Path` | Khong | Thu muc chua file ngon ngu |

### Screenshot cho theme

Tao file `screenshot.png` trong thu muc theme:
- Kich thuoc khuyen nghi: **1200 x 900 pixels**
- Dinh dang: PNG hoac JPG
- File nay se hien thi trong Admin > Appearance > Themes

---

## 4. Cau truc thu muc theme day du

```
my-theme/
|-- style.css                    # Style chinh + Theme Header
|-- index.php                    # Template mac dinh (fallback cuoi cung)
|-- functions.php                # Cau hinh theme (nhu bootstrap/app.php trong Laravel)
|-- screenshot.png               # Anh preview 1200x900
|
|-- # === TEMPLATE FILES CHINH ===
|-- front-page.php               # Trang chu (khi Settings > Reading > Static Page)
|-- home.php                     # Trang blog posts
|-- single.php                   # Bai viet don le
|-- page.php                     # Trang tinh (page)
|-- archive.php                  # Trang archive (danh sach bai viet)
|-- category.php                 # Trang danh muc
|-- tag.php                      # Trang the
|-- author.php                   # Trang tac gia
|-- search.php                   # Trang ket qua tim kiem
|-- 404.php                      # Trang loi 404
|-- comments.php                 # Template binh luan
|-- attachment.php               # Trang file dinh kem
|
|-- # === TEMPLATE PARTS (Components) ===
|-- header.php                   # Phan dau trang
|-- footer.php                   # Phan cuoi trang
|-- sidebar.php                  # Thanh ben
|-- template-parts/              # Cac phan template nho
|   |-- content.php              # Noi dung bai viet trong loop
|   |-- content-single.php       # Noi dung bai viet trang single
|   |-- content-page.php         # Noi dung trang page
|   |-- content-search.php       # Noi dung ket qua tim kiem
|   |-- content-none.php         # Khi khong co bai viet
|   |-- header/
|   |   |-- site-branding.php    # Logo + ten site
|   |   |-- navigation.php       # Menu chinh
|   |-- footer/
|       |-- footer-widgets.php   # Widget o footer
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
|   |   |-- main.css             # CSS chinh (compiled)
|   |   |-- editor-style.css     # Style cho Gutenberg editor
|   |-- js/
|   |   |-- main.js              # JS chinh
|   |   |-- navigation.js        # JS cho mobile menu
|   |   |-- customizer.js        # JS cho theme customizer
|   |-- images/
|   |   |-- default-thumbnail.jpg
|   |-- fonts/
|       |-- custom-font.woff2
|
|-- # === INCLUDES (PHP logic rieng) ===
|-- inc/
|   |-- customizer.php           # Theme Customizer settings
|   |-- template-tags.php        # Custom template tags (helper functions)
|   |-- template-functions.php   # Functions lien quan den template
|   |-- custom-header.php        # Custom header feature
|   |-- widgets.php              # Custom widgets
|   |-- walker-nav-menu.php      # Custom menu walker
|
|-- # === DA NGON NGU ===
|-- languages/
|   |-- developer-theme.pot      # Template file
|   |-- vi.po                    # Tieng Viet
|   |-- vi.mo                    # Tieng Viet (compiled)
|
|-- # === WOOCOMMERCE (neu can) ===
|-- woocommerce/
|   |-- single-product.php
|   |-- archive-product.php
|   |-- cart/
|   |-- checkout/
```

### So sanh voi cau truc Laravel:

```
Laravel                          WordPress Theme
-------                          ---------------
resources/views/                 theme root (*.php)
resources/views/layouts/         header.php + footer.php
resources/views/components/      template-parts/
public/css, public/js            assets/css, assets/js
app/View/Components/             inc/
routes/web.php                   Template Hierarchy (tu dong)
config/                          functions.php + inc/customizer.php
resources/lang/                  languages/
```

---

## 5. functions.php

`functions.php` la file **quan trong nhat** sau index.php. No hoat dong nhu mot **plugin** rieng cho theme - tu dong duoc load khi theme active.

### Vai tro cua functions.php:

1. **Dang ky features** (menus, thumbnails, widget areas)
2. **Load CSS va JS** (enqueue scripts/styles)
3. **Them cac ham tien ich** (helper functions)
4. **Hook vao WordPress** (actions va filters)
5. **Include cac file khac** tu thu muc inc/

### Cau truc functions.php chuan:

```php
<?php
/**
 * Developer Theme - Functions and definitions
 *
 * @package Developer_Theme
 * @since   1.0.0
 */

// === BAOMAT: Khong cho truy cap truc tiep ===
// Neu ai do truy cap file nay truc tiep qua URL, se bi dung lai
if ( ! defined( 'ABSPATH' ) ) {
    exit; // ABSPATH la hang so chi duoc define khi WP load
}

// === HANG SO CUA THEME ===
// Dinh nghia cac hang so de dung xuyen suot theme
define( 'DEV_THEME_VERSION', '1.0.0' );
define( 'DEV_THEME_DIR', get_template_directory() );        // Duong dan thu muc theme
define( 'DEV_THEME_URI', get_template_directory_uri() );    // URL cua theme

/**
 * === THIET LAP THEME ===
 * Hook vao 'after_setup_theme' de dang ky features
 * Hook nay chay sau khi theme duoc load, truoc khi init
 */
function developer_theme_setup() {
    // 1. Ho tro da ngon ngu
    // Load file ngon ngu tu thu muc /languages/
    load_theme_textdomain( 'developer-theme', DEV_THEME_DIR . '/languages' );

    // 2. Tu dong them tag <title> trong <head>
    // Khong can tu viet tag title nua, WP se tu xu ly
    add_theme_support( 'title-tag' );

    // 3. Ho tro anh dai dien cho bai viet (Featured Image)
    add_theme_support( 'post-thumbnails' );

    // 4. Dang ky vi tri menu
    register_nav_menus( array(
        'primary'   => __( 'Menu Chinh', 'developer-theme' ),
        'footer'    => __( 'Menu Footer', 'developer-theme' ),
        'mobile'    => __( 'Menu Mobile', 'developer-theme' ),
    ) );

    // 5. Tao HTML5 markup cho cac thanh phan
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // 6. Ho tro Custom Logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-width'  => true,   // Cho phep chieu rong linh hoat
        'flex-height' => true,   // Cho phep chieu cao linh hoat
    ) );

    // 7. Ho tro Block Editor (Gutenberg)
    add_theme_support( 'align-wide' );           // Cho phep align wide va full
    add_theme_support( 'responsive-embeds' );     // Embeds responsive
    add_theme_support( 'editor-styles' );         // Custom editor styles
    add_editor_style( 'assets/css/editor-style.css' );

    // 8. Dinh nghia kich thuoc anh tuy chinh
    add_image_size( 'developer-featured', 1200, 630, true );  // true = crop
    add_image_size( 'developer-thumbnail', 400, 300, true );
    add_image_size( 'developer-square', 600, 600, true );

    // 9. Ho tro Custom Background
    add_theme_support( 'custom-background', array(
        'default-color' => 'ffffff',
        'default-image' => '',
    ) );
}
add_action( 'after_setup_theme', 'developer_theme_setup' );

/**
 * === DANG KY WIDGET AREAS (Sidebars) ===
 */
function developer_theme_widgets_init() {
    // Sidebar chinh (ben phai)
    register_sidebar( array(
        'name'          => __( 'Sidebar Chinh', 'developer-theme' ),
        'id'            => 'sidebar-main',
        'description'   => __( 'Them widget vao day de hien thi o sidebar.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer Widget Area 1
    register_sidebar( array(
        'name'          => __( 'Footer 1', 'developer-theme' ),
        'id'            => 'footer-1',
        'description'   => __( 'Widget area cho footer cot 1.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    // Footer Widget Area 2
    register_sidebar( array(
        'name'          => __( 'Footer 2', 'developer-theme' ),
        'id'            => 'footer-2',
        'description'   => __( 'Widget area cho footer cot 2.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'developer_theme_widgets_init' );

/**
 * === ENQUEUE STYLES VA SCRIPTS ===
 */
function developer_theme_scripts() {
    // CSS
    wp_enqueue_style(
        'developer-theme-style',              // Handle (ID duy nhat)
        get_stylesheet_uri(),                  // URL toi style.css goc
        array(),                               // Dependencies (khong co)
        DEV_THEME_VERSION                      // Version (de cache busting)
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
        array(),                               // Khong phu thuoc thu vien nao
        DEV_THEME_VERSION,
        true                                   // true = load o footer (truoc </body>)
    );

    wp_enqueue_script(
        'developer-theme-main',
        DEV_THEME_URI . '/assets/js/main.js',
        array( 'jquery' ),                     // Phu thuoc vao jQuery
        DEV_THEME_VERSION,
        true
    );

    // Truyen du lieu tu PHP sang JS
    wp_localize_script( 'developer-theme-main', 'devThemeData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dev_theme_nonce' ),
        'homeUrl'  => home_url( '/' ),
        'themeUrl' => DEV_THEME_URI,
        'i18n'     => array(
            'loading' => __( 'Dang tai...', 'developer-theme' ),
            'error'   => __( 'Co loi xay ra!', 'developer-theme' ),
        ),
    ) );

    // Chi load comment-reply.js khi can (trang single co binh luan)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_theme_scripts' );

/**
 * === INCLUDE CAC FILE KHAC ===
 * Tach logic ra cac file rieng de code sach hon
 */
// Custom template tags (ham helper cho template)
require DEV_THEME_DIR . '/inc/template-tags.php';

// Theme Customizer
require DEV_THEME_DIR . '/inc/customizer.php';

// Custom Widgets
require DEV_THEME_DIR . '/inc/widgets.php';
```

### Giai thich cac ham quan trong:

```php
// === get_template_directory() vs get_stylesheet_directory() ===

// get_template_directory()
// --> Tra ve duong dan TUYET DOI tren server den thu muc theme CHA
// Vi du: /var/www/html/wp-content/themes/developer-theme

// get_template_directory_uri()
// --> Tra ve URL den thu muc theme CHA
// Vi du: https://example.com/wp-content/themes/developer-theme

// get_stylesheet_directory()
// --> Tra ve duong dan den thu muc theme HIEN TAI (child theme neu co)
// Neu dung child theme, no tro den child theme
// Neu khong dung child theme, giong get_template_directory()

// get_stylesheet_directory_uri()
// --> Tra ve URL den thu muc theme HIEN TAI

// get_stylesheet_uri()
// --> Tra ve URL den file style.css cua theme hien tai
// Vi du: https://example.com/wp-content/themes/developer-theme/style.css
```

---

## 6. Theme Supports

`add_theme_support()` cho WordPress biet theme ho tro nhung tinh nang gi:

```php
<?php
/**
 * Tat ca cac theme support co san trong WordPress
 */
function developer_theme_full_supports() {

    // === CO BAN ===

    // 1. Title Tag - WP tu quan ly tag <title>
    add_theme_support( 'title-tag' );

    // 2. Post Thumbnails - Anh dai dien cho bai viet
    add_theme_support( 'post-thumbnails' );
    // Chi ho tro cho post type cu the:
    // add_theme_support( 'post-thumbnails', array( 'post', 'page', 'product' ) );

    // 3. Post Formats - Cac dinh dang bai viet dac biet
    add_theme_support( 'post-formats', array(
        'aside',    // Ghi chu ngan
        'gallery',  // Thu vien anh
        'link',     // Lien ket
        'image',    // Hinh anh
        'quote',    // Trich dan
        'status',   // Trang thai ngan
        'video',    // Video
        'audio',    // Am thanh
        'chat',     // Hoi thoai
    ) );

    // 4. HTML5 Markup - Dung HTML5 cho cac thanh phan WP
    add_theme_support( 'html5', array(
        'comment-list',    // Danh sach binh luan
        'comment-form',    // Form binh luan
        'search-form',     // Form tim kiem
        'gallery',         // Thu vien anh
        'caption',         // Chu thich anh
        'style',           // Tag <style> khong can type attribute
        'script',          // Tag <script> khong can type attribute
        'navigation-widgets', // Widget menu dung HTML5
    ) );

    // 5. Custom Logo
    add_theme_support( 'custom-logo', array(
        'height'               => 100,
        'width'                => 400,
        'flex-height'          => true,
        'flex-width'           => true,
        'header-text'          => array( 'site-title', 'site-description' ),
        'unlink-homepage-logo' => true,  // WP 5.5+ : Logo khong link ve homepage
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
        'video'              => true,   // Cho phep video header
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

    // 8. Automatic Feed Links - Them RSS feed links vao <head>
    add_theme_support( 'automatic-feed-links' );

    // === GUTENBERG / BLOCK EDITOR ===

    // 9. Wide va Full width alignment
    add_theme_support( 'align-wide' );

    // 10. Block Styles - Load style mac dinh cua block
    add_theme_support( 'wp-block-styles' );

    // 11. Responsive Embeds
    add_theme_support( 'responsive-embeds' );

    // 12. Editor Styles - Custom CSS cho editor
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // 13. Custom Color Palette cho editor
    add_theme_support( 'editor-color-palette', array(
        array(
            'name'  => __( 'Xanh Duong Chinh', 'developer-theme' ),
            'slug'  => 'primary',
            'color' => '#0073aa',
        ),
        array(
            'name'  => __( 'Do Nhan Manh', 'developer-theme' ),
            'slug'  => 'accent',
            'color' => '#e74c3c',
        ),
        array(
            'name'  => __( 'Xam Nhat', 'developer-theme' ),
            'slug'  => 'light-gray',
            'color' => '#f5f5f5',
        ),
        array(
            'name'  => __( 'Den', 'developer-theme' ),
            'slug'  => 'dark',
            'color' => '#1a1a1a',
        ),
    ) );

    // 14. Custom Font Sizes cho editor
    add_theme_support( 'editor-font-sizes', array(
        array(
            'name' => __( 'Nho', 'developer-theme' ),
            'slug' => 'small',
            'size' => 14,
        ),
        array(
            'name' => __( 'Binh thuong', 'developer-theme' ),
            'slug' => 'normal',
            'size' => 16,
        ),
        array(
            'name' => __( 'Lon', 'developer-theme' ),
            'slug' => 'large',
            'size' => 24,
        ),
        array(
            'name' => __( 'Rat Lon', 'developer-theme' ),
            'slug' => 'huge',
            'size' => 36,
        ),
    ) );

    // 15. Tat custom colors (chi cho dung colors da dinh nghia)
    // add_theme_support( 'disable-custom-colors' );

    // 16. Tat custom font sizes
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

## 7. Enqueue Styles va Scripts

### Tai sao phai dung wp_enqueue thay vi viet truc tiep?

```php
// === SAI - Khong bao gio lam the nay ===
// Viet truc tiep trong header.php:
<link rel="stylesheet" href="style.css">
<script src="script.js"></script>

// === DUNG - Luon dung wp_enqueue ===
// Viet trong functions.php:
wp_enqueue_style( 'my-style', get_stylesheet_uri() );
wp_enqueue_script( 'my-script', get_template_directory_uri() . '/js/script.js' );
```

**Ly do:**
1. **Tranh trung lap** - Neu 2 plugin cung load jQuery, WP chi load 1 lan
2. **Quan ly thu tu** - Dependencies dam bao thu tu load dung
3. **Toi uu hieu nang** - Plugins cache/minify can biet tat ca scripts
4. **Conditional loading** - Chi load khi can thiet

### wp_enqueue_style() chi tiet:

```php
/**
 * wp_enqueue_style( $handle, $src, $deps, $ver, $media )
 *
 * @param string $handle  - Ten duy nhat (ID) cua stylesheet
 * @param string $src     - URL toi file CSS
 * @param array  $deps    - Mang cac handle ma CSS nay phu thuoc
 * @param string $ver     - Phien ban (de cache busting)
 * @param string $media   - Media query ('all', 'screen', 'print', '(max-width: 768px)')
 */

function developer_enqueue_styles() {
    // 1. Google Fonts
    wp_enqueue_style(
        'developer-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),    // Khong phu thuoc gi
        null        // null = khong them version query string
    );

    // 2. CSS Framework (Bootstrap)
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
        array(),
        '5.3.0'
    );

    // 3. Theme style.css (dang ky qua get_stylesheet_uri)
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

    // 5. Conditional: Chi load tren trang cu the
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
        'print'                       // Chi ap dung khi in
    );
}
add_action( 'wp_enqueue_scripts', 'developer_enqueue_styles' );
```

### wp_enqueue_script() chi tiet:

```php
/**
 * wp_enqueue_script( $handle, $src, $deps, $ver, $args )
 *
 * @param string       $handle - Ten duy nhat
 * @param string       $src    - URL toi file JS
 * @param array        $deps   - Dependencies
 * @param string|false $ver    - Version
 * @param bool|array   $args   - true = load o footer, hoac array chi tiet hon
 */

function developer_enqueue_scripts() {
    // 1. Bootstrap JS
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        array(),              // Bootstrap JS khong can jQuery
        '5.3.0',
        true                  // Load o footer
    );

    // 2. Navigation script
    wp_enqueue_script(
        'developer-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        array(),
        DEV_THEME_VERSION,
        true
    );

    // 3. Main script (phu thuoc jQuery)
    wp_enqueue_script(
        'developer-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array( 'jquery', 'bootstrap' ),  // Can jQuery va Bootstrap
        DEV_THEME_VERSION,
        true
    );

    // 4. Truyen du lieu tu PHP sang JavaScript
    // Tao mot object JavaScript co ten 'devTheme' voi du lieu ben duoi
    wp_localize_script( 'developer-main', 'devTheme', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dev_nonce' ),
        'is_home'  => is_home(),
        'strings'  => array(
            'confirm_delete' => __( 'Ban co chac muon xoa?', 'developer-theme' ),
            'loading'        => __( 'Dang tai...', 'developer-theme' ),
        ),
    ) );
    // Trong JS truy cap: devTheme.ajax_url, devTheme.nonce, devTheme.strings.loading

    // 5. Inline script (them JS truc tiep)
    wp_add_inline_script( 'developer-main', '
        console.log("Theme loaded successfully!");
        document.documentElement.classList.remove("no-js");
        document.documentElement.classList.add("js");
    ', 'before' ); // 'before' = truoc file main.js, 'after' = sau

    // 6. Chi load tren trang co the
    if ( is_singular() && comments_open() ) {
        wp_enqueue_script( 'comment-reply' ); // Script WP co san
    }

    // 7. WP 6.3+: Dung strategy de load async hoac defer
    wp_enqueue_script(
        'developer-analytics',
        get_template_directory_uri() . '/assets/js/analytics.js',
        array(),
        DEV_THEME_VERSION,
        array(
            'in_footer' => true,
            'strategy'  => 'defer',  // 'defer' hoac 'async'
        )
    );
}
add_action( 'wp_enqueue_scripts', 'developer_enqueue_scripts' );
```

### Dequeue va Deregister (go bo scripts/styles):

```php
/**
 * Go bo styles/scripts khong can thiet (tu plugin, theme parent)
 */
function developer_dequeue_unnecessary() {
    // Go bo CSS cua plugin khong can
    wp_dequeue_style( 'contact-form-7' );
    wp_deregister_style( 'contact-form-7' );

    // Chi load CF7 CSS tren trang contact
    if ( is_page( 'contact' ) ) {
        wp_enqueue_style( 'contact-form-7' );
    }

    // Go bo jQuery Migrate (khong can cho theme moi)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', false, array( 'jquery-core' ), null, true );
    }

    // Go bo block library CSS neu khong dung Gutenberg o frontend
    // wp_dequeue_style( 'wp-block-library' );
    // wp_dequeue_style( 'wp-block-library-theme' );

    // Go bo emoji scripts (tiet kiem HTTP requests)
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'wp_enqueue_scripts', 'developer_dequeue_unnecessary', 100 );
// Priority 100 = chay sau cac enqueue khac de dam bao go bo duoc
```

### Enqueue cho Admin:

```php
/**
 * Load CSS/JS trong admin area
 */
function developer_admin_scripts( $hook ) {
    // $hook cho biet dang o trang admin nao
    // Vi du: 'post.php', 'post-new.php', 'edit.php', 'toplevel_page_my-plugin'

    // Chi load tren trang edit post
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

## 8. Tao theme Hello World tu dau

### Buoc 1: Tao thu muc theme

```bash
# Di den thu muc themes cua WordPress
cd wp-content/themes/

# Tao thu muc theme moi
mkdir developer-starter
cd developer-starter

# Tao cau truc thu muc
mkdir -p assets/{css,js,images}
mkdir -p template-parts/{header,footer}
mkdir -p inc
mkdir -p languages
```

### Buoc 2: Tao style.css

```css
/*
Theme Name:        Developer Starter
Theme URI:         https://example.com/developer-starter
Author:            Developer VN
Author URI:        https://example.com
Description:       Theme WordPress don gian cho nguoi moi bat dau.
                   Toi uu, nhe, de tuy chinh.
Version:           1.0.0
Requires at least: 6.0
Tested up to:      6.4
Requires PHP:      8.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       developer-starter
Tags:              blog, custom-menu, featured-images, translation-ready
*/

/* === RESET CO BAN === */
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

/* Khi khong co sidebar */
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

### Buoc 3: Tao functions.php

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
    // Da ngon ngu
    load_theme_textdomain( 'developer-starter', get_template_directory() . '/languages' );

    // Title tag tu dong
    add_theme_support( 'title-tag' );

    // Featured images
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'developer-featured', 1200, 630, true );

    // Dang ky menu
    register_nav_menus( array(
        'primary' => __( 'Menu Chinh', 'developer-starter' ),
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
 * Dang ky Widget Areas
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
 * Enqueue styles va scripts
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
 * Ham helper: Hien thi post meta
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
        esc_html__( 'Dang ngay', 'developer-starter' ),
        $time_string,
        esc_html__( 'boi', 'developer-starter' ),
        '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>'
    );
}

/**
 * Ham helper: Custom excerpt length
 */
function developer_starter_excerpt_length( $length ) {
    return 30; // 30 tu thay vi 55 tu mac dinh
}
add_filter( 'excerpt_length', 'developer_starter_excerpt_length' );

/**
 * Ham helper: Custom excerpt more
 */
function developer_starter_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'developer_starter_excerpt_more' );
```

### Buoc 4: Tao header.php

```php
<?php
/**
 * Header template
 * Hien thi phan dau trang: DOCTYPE -> het navigation
 *
 * @package Developer_Starter
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<!-- language_attributes() tao: lang="vi" hoac lang="en-US" -->

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <!-- bloginfo('charset') tao: UTF-8 -->

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php wp_head(); ?>
    <!--
    wp_head() LA BAT BUOC - No in ra:
    - Cac file CSS da enqueue
    - Cac file JS trong <head>
    - Meta tags (title, description)
    - RSS feed links
    - Cac code tu plugins (analytics, SEO...)
    Tuong tu nhu @vite trong Laravel Blade
    -->
</head>

<body <?php body_class(); ?>>
<!--
body_class() tu dong them cac class huu ich:
- home (trang chu)
- single-post (trang bai viet)
- page-template-xxx (page template dang dung)
- logged-in (dang dang nhap)
- admin-bar (hien admin bar)
Vi du: <body class="home blog logged-in admin-bar">
-->

<?php wp_body_open(); ?>
<!-- WP 5.2+: Hook de them code sau <body> (analytics, skip link...) -->

<div id="page" class="site">

    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e( 'Chuyen den noi dung', 'developer-starter' ); ?>
    </a>
    <!-- Skip link cho accessibility - cho phep ban phim nhay thang den noi dung -->

    <header id="masthead" class="site-header">
        <div class="container">

            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <!-- Neu co custom logo, hien thi logo -->
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <!-- Khong co logo, hien thi ten va mo ta -->
                    <?php if ( is_front_page() && is_home() ) : ?>
                        <!-- Trang chu: dung h1 cho SEO -->
                        <h1 class="site-title">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php bloginfo( 'name' ); ?>
                            </a>
                        </h1>
                    <?php else : ?>
                        <!-- Trang khac: dung p de khong co nhieu h1 -->
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
                // Hien thi menu da dang ky voi ID 'primary'
                wp_nav_menu( array(
                    'theme_location' => 'primary',        // Vi tri menu (da dang ky trong functions.php)
                    'menu_id'        => 'primary-menu',   // ID cua <ul>
                    'container'      => false,             // Khong boc trong <div>
                    'fallback_cb'    => false,             // Khong hien gi neu chua co menu
                    'depth'          => 2,                 // Do sau toi da 2 cap
                ) );
                ?>
            </nav><!-- .main-navigation -->

        </div><!-- .container -->
    </header><!-- .site-header -->
```

### Buoc 5: Tao footer.php

```php
<?php
/**
 * Footer template
 * Hien thi tu footer widgets -> het </html>
 *
 * @package Developer_Starter
 */
?>

    <footer id="colophon" class="site-footer">
        <div class="container">

            <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
            <!-- Chi hien thi footer widgets khi co it nhat 1 widget -->
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
                    | <?php esc_html_e( 'Tao voi WordPress', 'developer-starter' ); ?>
                </p>
            </div><!-- .site-info -->

        </div><!-- .container -->
    </footer><!-- .site-footer -->

</div><!-- #page .site -->

<?php wp_footer(); ?>
<!--
wp_footer() LA BAT BUOC - In ra:
- Cac file JS da enqueue voi in_footer = true
- Admin bar (khi dang nhap)
- Code tu plugins
Tuong tu nhu @vite hoac @stack('scripts') trong Laravel
-->

</body>
</html>
```

### Buoc 6: Tao sidebar.php

```php
<?php
/**
 * Sidebar template
 *
 * @package Developer_Starter
 */

// Neu sidebar khong co widget nao, khong hien thi gi
if ( ! is_active_sidebar( 'sidebar-main' ) ) {
    return;
}
?>

<aside id="secondary" class="widget-area" role="complementary">
    <?php dynamic_sidebar( 'sidebar-main' ); ?>
    <!-- dynamic_sidebar() hien thi tat ca widgets trong sidebar co ID 'sidebar-main' -->
</aside>
```

### Buoc 7: Tao index.php

```php
<?php
/**
 * Main template file - Day la template cuoi cung (fallback)
 * Neu khong co template cu the hon (single.php, page.php...),
 * WordPress se dung file nay.
 *
 * Trong Laravel, tuong tu nhu resources/views/layouts/app.blade.php
 *
 * @package Developer_Starter
 */

get_header(); // Load header.php (tuong tu @include('partials.header'))
?>

<main id="primary" class="site-content">
    <div class="content-area <?php echo is_active_sidebar( 'sidebar-main' ) ? '' : 'no-sidebar'; ?>">

        <div class="main-content">
            <?php if ( have_posts() ) : ?>

                <?php if ( is_home() && ! is_front_page() ) : ?>
                    <!-- Trang blog (khi co static front page) -->
                    <header class="page-header">
                        <h1 class="page-title"><?php single_post_title(); ?></h1>
                    </header>
                <?php endif; ?>

                <?php
                // === THE LOOP ===
                // Vong lap chinh de hien thi bai viet
                // Tuong tu @foreach ($posts as $post) trong Blade
                while ( have_posts() ) :
                    the_post(); // Chuan bi du lieu cho bai viet hien tai
                ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <!--
                        the_ID() : ID cua bai viet
                        post_class() : Them class tu dong (type-post, status-publish, category-xxx...)
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
                                    <?php esc_html_e( 'Doc them', 'developer-starter' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ( is_singular() ) : ?>
                            <footer class="entry-footer">
                                <?php
                                // Hien thi categories va tags
                                $categories = get_the_category_list( ', ' );
                                if ( $categories ) {
                                    printf( '<span class="cat-links">%s: %s</span>', esc_html__( 'Danh muc', 'developer-starter' ), $categories );
                                }

                                $tags = get_the_tag_list( '', ', ' );
                                if ( $tags ) {
                                    printf( ' | <span class="tag-links">%s: %s</span>', esc_html__( 'The', 'developer-starter' ), $tags );
                                }
                                ?>
                            </footer>
                        <?php endif; ?>

                    </article>

                <?php endwhile; // Ket thuc The Loop ?>

                <?php
                // Pagination
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => '&laquo; ' . __( 'Truoc', 'developer-starter' ),
                    'next_text' => __( 'Sau', 'developer-starter' ) . ' &raquo;',
                ) );
                ?>

            <?php else : ?>

                <!-- Khong co bai viet nao -->
                <div class="no-results">
                    <h1><?php esc_html_e( 'Khong tim thay noi dung', 'developer-starter' ); ?></h1>

                    <?php if ( is_search() ) : ?>
                        <p><?php esc_html_e( 'Khong tim thay ket qua cho tu khoa cua ban. Hay thu lai voi tu khoa khac.', 'developer-starter' ); ?></p>
                        <?php get_search_form(); ?>
                    <?php else : ?>
                        <p><?php esc_html_e( 'Co ve nhu khong co noi dung nao o day. Thu tim kiem?', 'developer-starter' ); ?></p>
                        <?php get_search_form(); ?>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div><!-- .main-content -->

        <?php get_sidebar(); // Load sidebar.php ?>

    </div><!-- .content-area -->
</main><!-- .site-content -->

<?php
get_footer(); // Load footer.php
?>
```

### Buoc 8: Tao assets/js/navigation.js

```javascript
/**
 * Navigation - Xu ly mobile menu toggle
 */
(function() {
    'use strict';

    // Tim nut toggle va menu
    const toggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-navigation');

    if (!toggle || !nav) {
        return;
    }

    // Click vao nut hamburger de mo/dong menu
    toggle.addEventListener('click', function() {
        nav.classList.toggle('toggled');

        // Cap nhat aria-expanded cho accessibility
        const expanded = nav.classList.contains('toggled');
        toggle.setAttribute('aria-expanded', expanded);
    });

    // Dong menu khi click ra ngoai
    document.addEventListener('click', function(event) {
        if (!nav.contains(event.target) && !toggle.contains(event.target)) {
            nav.classList.remove('toggled');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });

    // Dong menu khi nhan Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            nav.classList.remove('toggled');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
})();
```

### Buoc 9: Kich hoat theme

1. Vao **WordPress Admin > Appearance > Themes**
2. Tim theme "Developer Starter"
3. Click **Activate**
4. Kiem tra trang chu

---

## 9. So sanh voi Laravel

### Mapping khai niem Laravel -> WordPress Theme

| Laravel | WordPress | Giai thich |
|---------|-----------|-----------|
| `resources/views/` | Thu muc theme | Noi chua template |
| `layouts/app.blade.php` | `header.php` + `footer.php` | Layout chinh |
| `@yield('content')` | The Loop trong index.php | Noi dung chinh |
| `@extends('layouts.app')` | `get_header()` + `get_footer()` | Ke thua layout |
| `@include('partials.nav')` | `get_template_part('template-parts/nav')` | Include component |
| `@section('sidebar')` | `get_sidebar()` | Sidebar |
| `routes/web.php` | Template Hierarchy | Routing |
| `public/css/app.css` | `wp_enqueue_style()` | Load CSS |
| `@vite(['resources/css/app.css'])` | `wp_head()` / `wp_footer()` | In ra assets |
| `config/app.php` | `functions.php` | Cau hinh |
| `AppServiceProvider::boot()` | `after_setup_theme` hook | Bootstrap |
| `{{ $variable }}` | `<?php echo esc_html($var); ?>` | Output escaped |
| `{!! $html !!}` | `<?php echo $html; ?>` | Output raw |
| `Blade::component()` | `get_template_part()` | Reusable component |
| `@if @else @endif` | `<?php if(): ?> <?php else: ?> <?php endif; ?>` | Conditionals |
| `@foreach @endforeach` | `while (have_posts()): the_post();` | Loop |

### Routing: Laravel vs WordPress

```php
// === LARAVEL ===
// Ban TU dinh nghia route
Route::get('/', [HomeController::class, 'index']);
Route::get('/blog/{slug}', [PostController::class, 'show']);
Route::get('/category/{slug}', [CategoryController::class, 'show']);

// === WORDPRESS ===
// WordPress TU DONG chon template dua tren URL:
// /                --> front-page.php hoac home.php hoac index.php
// /hello-world/    --> single.php hoac index.php
// /category/news/  --> category.php hoac archive.php hoac index.php
// Ban KHONG can dinh nghia route!
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
// index.php (tat ca trong 1 file, hoac chia thanh header/footer)
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

### 1. Bao mat

```php
// LUON them dong nay dau moi file PHP trong theme
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// LUON escape output
echo esc_html( $text );         // Text binh thuong
echo esc_attr( $attribute );    // HTML attributes
echo esc_url( $url );           // URLs
echo wp_kses_post( $html );     // HTML noi dung bai viet (cho phep 1 so tag)

// LUON sanitize input
sanitize_text_field( $input );
sanitize_email( $email );
absint( $number );
```

### 2. Prefix moi thu

```php
// DUNG - Them prefix de tranh trung ten voi plugin khac
function developer_starter_setup() { }
function developer_starter_scripts() { }
define( 'DEVELOPER_STARTER_VERSION', '1.0.0' );

// SAI - Ten qua chung, de bi trung
function setup() { }
function load_scripts() { }
```

### 3. Internationalization (da ngon ngu)

```php
// Dung cac ham dich de theme co the da ngon ngu
__( 'Hello', 'developer-starter' );      // Tra ve string da dich
_e( 'Hello', 'developer-starter' );      // Echo string da dich
esc_html__( 'Hello', 'developer-starter' ); // Tra ve + escape
esc_html_e( 'Hello', 'developer-starter' ); // Echo + escape

// Voi bien
sprintf( __( 'Hello %s', 'developer-starter' ), $name );

// So nhieu
_n( '%s comment', '%s comments', $count, 'developer-starter' );
```

### 4. Performance

```php
// Load JS o footer (tham so true cuoi cung)
wp_enqueue_script( 'my-script', $url, array(), $ver, true );

// Chi load khi can
if ( is_page( 'contact' ) ) {
    wp_enqueue_style( 'contact-form-style', ... );
}

// Dung version de cache busting
wp_enqueue_style( 'my-style', $url, array(), '1.0.0' );
```

### 5. Cau truc code sach

```php
// Tach code ra cac file rieng trong thu muc inc/
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/widgets.php';

// Dung template parts cho cac phan lap lai
get_template_part( 'template-parts/content', get_post_type() );
// Se load: template-parts/content-post.php hoac content-page.php
```

### 6. Accessibility (Tiep can)

```php
// Luon co skip link
<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>

// Dung semantic HTML
<header>, <nav>, <main>, <aside>, <footer>, <article>

// ARIA attributes
<nav aria-label="Primary Menu">
<button aria-expanded="false" aria-controls="primary-menu">

// Focus styles - KHONG BAO GIO xoa outline
a:focus, button:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}
```

### 7. Coding Standards

```php
// Theo WordPress Coding Standards:
// - Dung tab (khong space) de indent PHP
// - Space sau dau phay: array( 'a', 'b', 'c' )
// - Space trong ngoac don: if ( true ) { }
// - Dung === thay vi == de so sanh
// - Dung single quotes cho string khong co bien
// - Dung yoda conditions: if ( true === $variable )

// Cai dat PHP CodeSniffer + WordPress standards:
// composer require --dev wp-coding-standards/wpcs
// phpcs --standard=WordPress functions.php
```

---

**Tiep theo:** [02 - Template Hierarchy](./02-template-hierarchy.md) - Hieu cach WordPress tu dong chon template file
