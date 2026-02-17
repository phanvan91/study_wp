# Bài 8: Ví Dụ Thực Tế - Xây Dựng Theme WordPress

> **Hướng dẫn step-by-step** xây dựng theme WordPress hoàn chỉnh từ đầu.
> Mỗi file đều có **code đầy đủ**, copy-paste chạy được ngay.

---

## Mục Lục

1. [Cấu trúc thư mục theme](#1-cấu-trúc-thư-mục-theme)
2. [style.css - Khai báo theme](#2-stylecss---khai-báo-theme)
3. [functions.php - Bộ não của theme](#3-functionsphp---bộ-não-của-theme)
4. [header.php - Phần đầu trang](#4-headerphp---phần-đầu-trang)
5. [footer.php - Phần chân trang](#5-footerphp---phần-chân-trang)
6. [index.php - Template mặc định](#6-indexphp---template-mặc-định)
7. [single.php - Trang bài viết đơn](#7-singlephp---trang-bài-viết-đơn)
8. [page.php - Trang tĩnh](#8-pagephp---trang-tĩnh)
9. [archive.php - Trang danh sách](#9-archivephp---trang-danh-sách)
10. [search.php - Trang tìm kiếm](#10-searchphp---trang-tìm-kiếm)
11. [404.php - Trang không tìm thấy](#11-404php---trang-không-tìm-thấy)
12. [sidebar.php - Sidebar](#12-sidebarphp---sidebar)
13. [Template Parts](#13-template-parts)
14. [Custom Page Templates](#14-custom-page-templates)
15. [Ví dụ functions.php nâng cao](#15-ví-dụ-functionsphp-nâng-cao)
16. [Block Theme (FSE) hoàn chỉnh](#16-block-theme-fse-hoàn-chỉnh)
17. [Custom Walker Nav Menu](#17-custom-walker-nav-menu)

---

## 1. Cấu Trúc Thư Mục Theme

```
wp-content/themes/mytheme/
│
├── style.css                    ← Khai báo theme (BẮT BUỘC)
├── index.php                    ← Template mặc định (BẮT BUỘC)
├── functions.php                ← Đăng ký chức năng
├── screenshot.png               ← Ảnh preview (1200x900)
│
├── header.php                   ← <head> + phần đầu <body>
├── footer.php                   ← Phần cuối <body>
├── sidebar.php                  ← Sidebar widgets
│
├── single.php                   ← Single post
├── page.php                     ← Static page
├── archive.php                  ← Archive (category, tag, date...)
├── search.php                   ← Search results
├── 404.php                      ← Page not found
├── front-page.php               ← Trang chủ (nếu set "static page")
├── home.php                     ← Blog page
│
├── comments.php                 ← Bình luận
├── searchform.php               ← Form tìm kiếm
│
├── template-parts/              ← Các phần template tái sử dụng
│   ├── content.php              ← Nội dung bài viết (loop)
│   ├── content-none.php         ← Không có kết quả
│   ├── content-search.php       ← Kết quả tìm kiếm
│   └── content-page.php         ← Nội dung trang
│
├── page-templates/              ← Custom page templates
│   ├── full-width.php           ← Template full width
│   └── contact.php              ← Template trang liên hệ
│
├── inc/                         ← PHP includes
│   ├── customizer.php           ← Customizer settings
│   ├── template-tags.php        ← Template helper functions
│   └── walker-nav-menu.php      ← Custom menu walker
│
├── assets/                      ← Static files
│   ├── css/
│   │   ├── main.css             ← CSS chính
│   │   └── responsive.css       ← Responsive rules
│   ├── js/
│   │   ├── main.js              ← JS chính
│   │   └── navigation.js        ← Mobile menu
│   ├── images/                  ← Hình ảnh theme
│   └── fonts/                   ← Font tùy chỉnh
│
└── languages/                   ← File ngôn ngữ (.po, .mo)
    └── mytheme.pot
```

---

## 2. style.css - Khai Báo Theme

```css
/*
Theme Name:        My Theme
Theme URI:         https://example.com/mytheme
Author:            Tên bạn
Author URI:        https://example.com
Description:       Theme WordPress tùy chỉnh, responsive, hỗ trợ Gutenberg.
                   Được xây dựng cho mục đích học tập.
Version:           1.0.0
Requires at least: 6.0
Tested up to:      6.5
Requires PHP:      8.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       mytheme
Tags:              custom-menu, custom-logo, featured-images, footer-widgets,
                   theme-options, translation-ready, blog, portfolio
*/

/* === RESET & BASE === */
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --color-primary: #0073aa;
    --color-secondary: #23282d;
    --color-accent: #00a0d2;
    --color-text: #333;
    --color-text-light: #666;
    --color-bg: #fff;
    --color-bg-alt: #f7f7f7;
    --color-border: #ddd;
    --font-main: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-heading: 'Inter', sans-serif;
    --max-width: 1200px;
    --sidebar-width: 300px;
}

body {
    font-family: var(--font-main);
    font-size: 16px;
    line-height: 1.7;
    color: var(--color-text);
    background-color: var(--color-bg);
}

a {
    color: var(--color-primary);
    text-decoration: none;
    transition: color 0.2s;
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
.site-container {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 20px;
}

.content-area {
    display: flex;
    gap: 40px;
    padding: 40px 0;
}

.main-content {
    flex: 1;
    min-width: 0; /* Fix flexbox overflow */
}

.sidebar {
    width: var(--sidebar-width);
    flex-shrink: 0;
}

/* === HEADER === */
.site-header {
    background: var(--color-secondary);
    color: #fff;
    padding: 0;
}

.header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 20px;
    min-height: 70px;
}

.site-branding a {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
}

.site-description {
    color: #aaa;
    font-size: 0.85rem;
    margin-top: 2px;
}

/* === NAVIGATION === */
.main-navigation ul {
    display: flex;
    list-style: none;
    gap: 5px;
}

.main-navigation a {
    color: #ccc;
    padding: 8px 16px;
    display: block;
    border-radius: 4px;
    font-size: 0.95rem;
}

.main-navigation a:hover,
.main-navigation .current-menu-item a {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

/* Dropdown */
.main-navigation li {
    position: relative;
}

.main-navigation ul ul {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 4px;
    flex-direction: column;
    z-index: 100;
}

.main-navigation li:hover > ul {
    display: flex;
}

.main-navigation ul ul a {
    color: var(--color-text);
    padding: 10px 16px;
}

.main-navigation ul ul a:hover {
    background: var(--color-bg-alt);
    color: var(--color-primary);
}

/* Mobile menu toggle */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 10px;
}

/* === POST CARDS === */
.post-card {
    margin-bottom: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid var(--color-border);
}

.post-card:last-child {
    border-bottom: none;
}

.post-card .post-thumbnail {
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
}

.post-card .post-thumbnail img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.3s;
}

.post-card .post-thumbnail:hover img {
    transform: scale(1.03);
}

.post-card .entry-title {
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.post-card .entry-title a {
    color: var(--color-secondary);
}

.post-card .entry-title a:hover {
    color: var(--color-primary);
}

.entry-meta {
    color: var(--color-text-light);
    font-size: 0.85rem;
    margin-bottom: 15px;
}

.entry-meta a {
    color: var(--color-text-light);
}

.entry-meta .sep {
    margin: 0 8px;
}

.read-more {
    display: inline-block;
    margin-top: 10px;
    font-weight: 600;
    color: var(--color-primary);
}

/* === SINGLE POST === */
.single-post .entry-header {
    margin-bottom: 30px;
}

.single-post .entry-title {
    font-size: 2rem;
    line-height: 1.3;
    margin-bottom: 15px;
}

.entry-content {
    line-height: 1.8;
}

.entry-content h2 {
    margin: 2em 0 0.8em;
    font-size: 1.5rem;
}

.entry-content h3 {
    margin: 1.5em 0 0.6em;
    font-size: 1.25rem;
}

.entry-content p {
    margin-bottom: 1.2em;
}

.entry-content ul, .entry-content ol {
    margin: 0 0 1.2em 1.5em;
}

.entry-content blockquote {
    border-left: 4px solid var(--color-primary);
    padding: 15px 20px;
    margin: 1.5em 0;
    background: var(--color-bg-alt);
    font-style: italic;
}

.entry-content pre {
    background: #1e1e1e;
    color: #f8f8f2;
    padding: 20px;
    border-radius: 6px;
    overflow-x: auto;
    margin: 1.5em 0;
    font-size: 0.9rem;
}

.entry-content code {
    background: var(--color-bg-alt);
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}

.entry-content pre code {
    background: none;
    padding: 0;
}

/* === PAGINATION === */
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin: 40px 0;
}

.pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    color: var(--color-text);
}

.pagination .page-numbers.current,
.pagination .page-numbers:hover {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #fff;
}

/* === SIDEBAR === */
.sidebar .widget {
    margin-bottom: 30px;
    padding: 20px;
    background: var(--color-bg-alt);
    border-radius: 8px;
}

.sidebar .widget-title {
    font-size: 1.1rem;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--color-primary);
}

.sidebar .widget ul {
    list-style: none;
}

.sidebar .widget ul li {
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.sidebar .widget ul li:last-child {
    border-bottom: none;
}

/* === FOOTER === */
.site-footer {
    background: var(--color-secondary);
    color: #aaa;
    padding: 40px 0 20px;
    margin-top: 40px;
}

.footer-widgets {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    margin-bottom: 30px;
}

.footer-widgets .widget-title {
    color: #fff;
    margin-bottom: 15px;
}

.footer-widgets a {
    color: #aaa;
}

.footer-widgets a:hover {
    color: #fff;
}

.site-credits {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 0.85rem;
}

/* === COMMENTS === */
.comments-area {
    margin-top: 40px;
    padding-top: 40px;
    border-top: 1px solid var(--color-border);
}

.comment-list {
    list-style: none;
}

.comment-body {
    padding: 20px;
    margin-bottom: 20px;
    background: var(--color-bg-alt);
    border-radius: 8px;
}

.comment-author .avatar {
    float: left;
    margin-right: 15px;
    border-radius: 50%;
}

.comment-metadata {
    font-size: 0.85rem;
    color: var(--color-text-light);
}

/* === 404 === */
.error-404 {
    text-align: center;
    padding: 80px 20px;
}

.error-404 h1 {
    font-size: 6rem;
    color: var(--color-primary);
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .content-area {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
    }

    .menu-toggle {
        display: block;
    }

    .main-navigation ul {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--color-secondary);
        padding: 10px;
    }

    .main-navigation.toggled ul {
        display: flex;
    }

    .main-navigation ul ul {
        position: static;
        box-shadow: none;
        background: rgba(255,255,255,0.05);
    }

    .footer-widgets {
        grid-template-columns: 1fr;
    }

    .header-inner {
        flex-wrap: wrap;
    }

    .single-post .entry-title {
        font-size: 1.5rem;
    }
}
```

---

## 3. functions.php - Bộ Não Của Theme

```php
<?php
/**
 * My Theme functions and definitions
 *
 * @package MyTheme
 * @since   1.0.0
 */

// Ngăn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hằng số theme
define( 'MYTHEME_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'MYTHEME_DIR', get_template_directory() );
define( 'MYTHEME_URI', get_template_directory_uri() );

/**
 * 1. SETUP THEME
 * Hook: after_setup_theme
 * Đăng ký các tính năng theme hỗ trợ
 */
add_action( 'after_setup_theme', 'mytheme_setup' );

function mytheme_setup() {
    // Hỗ trợ đa ngôn ngữ
    load_theme_textdomain( 'mytheme', MYTHEME_DIR . '/languages' );

    // Tự động thêm <title> tag
    add_theme_support( 'title-tag' );

    // Hỗ trợ ảnh đại diện (featured image)
    add_theme_support( 'post-thumbnails' );

    // Kích thước ảnh tùy chỉnh
    add_image_size( 'mytheme-featured', 800, 450, true );   // Crop chính xác
    add_image_size( 'mytheme-thumbnail', 400, 300, true );
    add_image_size( 'mytheme-full', 1200, 0, false );        // Tự động chiều cao

    // Đăng ký Navigation Menus
    register_nav_menus( array(
        'primary'   => __( 'Menu Chính', 'mytheme' ),
        'footer'    => __( 'Menu Footer', 'mytheme' ),
        'mobile'    => __( 'Menu Mobile', 'mytheme' ),
    ) );

    // Hỗ trợ HTML5 cho các phần tử WP
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Custom Logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Custom Background
    add_theme_support( 'custom-background', array(
        'default-color' => 'ffffff',
    ) );

    // Hỗ trợ Gutenberg
    add_theme_support( 'align-wide' );                // Hỗ trợ alignwide, alignfull
    add_theme_support( 'responsive-embeds' );         // Responsive video embeds
    add_theme_support( 'editor-styles' );             // Custom editor styles
    add_editor_style( 'assets/css/editor-style.css' );

    // Gutenberg color palette
    add_theme_support( 'editor-color-palette', array(
        array(
            'name'  => __( 'Primary', 'mytheme' ),
            'slug'  => 'primary',
            'color' => '#0073aa',
        ),
        array(
            'name'  => __( 'Secondary', 'mytheme' ),
            'slug'  => 'secondary',
            'color' => '#23282d',
        ),
        array(
            'name'  => __( 'Accent', 'mytheme' ),
            'slug'  => 'accent',
            'color' => '#00a0d2',
        ),
    ) );

    // Content width (cho embeds, images)
    $GLOBALS['content_width'] = 800;

    // Automatic feed links
    add_theme_support( 'automatic-feed-links' );

    // Selective refresh cho widgets
    add_theme_support( 'customize-selective-refresh-widgets' );
}

/**
 * 2. ENQUEUE SCRIPTS & STYLES
 * Hook: wp_enqueue_scripts
 */
add_action( 'wp_enqueue_scripts', 'mytheme_scripts' );

function mytheme_scripts() {
    // CSS
    wp_enqueue_style( 'mytheme-style', get_stylesheet_uri(), array(), MYTHEME_VERSION );
    wp_enqueue_style( 'mytheme-main', MYTHEME_URI . '/assets/css/main.css', array(), MYTHEME_VERSION );

    // Google Fonts
    wp_enqueue_style(
        'mytheme-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // JavaScript
    wp_enqueue_script(
        'mytheme-navigation',
        MYTHEME_URI . '/assets/js/navigation.js',
        array(),
        MYTHEME_VERSION,
        true
    );

    wp_enqueue_script(
        'mytheme-main',
        MYTHEME_URI . '/assets/js/main.js',
        array(),
        MYTHEME_VERSION,
        true
    );

    // Truyền data PHP → JS
    wp_localize_script( 'mytheme-main', 'mythemeVars', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mytheme_nonce' ),
        'themeUrl' => MYTHEME_URI,
    ) );

    // Comments reply script (chỉ khi cần)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}

/**
 * 3. ĐĂNG KÝ SIDEBARS & WIDGET AREAS
 * Hook: widgets_init
 */
add_action( 'widgets_init', 'mytheme_widgets_init' );

function mytheme_widgets_init() {
    // Sidebar chính (bên phải)
    register_sidebar( array(
        'name'          => __( 'Sidebar Chính', 'mytheme' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Sidebar bên phải, hiển thị trên blog và single post.', 'mytheme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer widgets (3 cột)
    register_sidebar( array(
        'name'          => __( 'Footer Cột 1', 'mytheme' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer Cột 2', 'mytheme' ),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer Cột 3', 'mytheme' ),
        'id'            => 'footer-3',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
}

/**
 * 4. TEMPLATE HELPER FUNCTIONS
 * Các hàm hỗ trợ dùng trong template files
 */

/**
 * Hiển thị posted date + author
 */
function mytheme_posted_on() {
    $time_string = sprintf(
        '<time class="entry-date" datetime="%1$s">%2$s</time>',
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date() )
    );

    printf(
        '<span class="posted-on">%s</span><span class="sep"> · </span><span class="byline">%s %s</span>',
        $time_string,
        __( 'bởi', 'mytheme' ),
        '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' .
        esc_html( get_the_author() ) . '</a>'
    );
}

/**
 * Hiển thị categories và tags
 */
function mytheme_entry_footer() {
    if ( 'post' === get_post_type() ) {
        $categories = get_the_category_list( ', ' );
        if ( $categories ) {
            printf( '<span class="cat-links">%s %s</span>', __( 'Danh mục:', 'mytheme' ), $categories );
        }

        $tags = get_the_tag_list( '', ', ' );
        if ( $tags ) {
            printf( '<span class="tag-links"> | %s %s</span>', __( 'Thẻ:', 'mytheme' ), $tags );
        }
    }

    edit_post_link(
        __( 'Sửa bài', 'mytheme' ),
        '<span class="edit-link"> | ',
        '</span>'
    );
}

/**
 * Hiển thị post thumbnail với fallback
 */
function mytheme_post_thumbnail( $size = 'mytheme-featured' ) {
    if ( post_password_required() || is_attachment() ) {
        return;
    }

    if ( has_post_thumbnail() ) {
        printf(
            '<div class="post-thumbnail"><a href="%s">%s</a></div>',
            esc_url( get_permalink() ),
            get_the_post_thumbnail( null, $size, array(
                'alt'     => the_title_attribute( 'echo=0' ),
                'loading' => 'lazy',
            ) )
        );
    }
}

/**
 * Pagination
 */
function mytheme_pagination() {
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => '&laquo; ' . __( 'Trước', 'mytheme' ),
        'next_text' => __( 'Sau', 'mytheme' ) . ' &raquo;',
    ) );
}

/**
 * Breadcrumb đơn giản
 */
function mytheme_breadcrumb() {
    if ( is_front_page() ) {
        return;
    }

    echo '<nav class="breadcrumb" aria-label="Breadcrumb">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . __( 'Trang chủ', 'mytheme' ) . '</a>';

    if ( is_category() ) {
        echo ' &raquo; ' . single_cat_title( '', false );
    } elseif ( is_tag() ) {
        echo ' &raquo; Tag: ' . single_tag_title( '', false );
    } elseif ( is_singular( 'post' ) ) {
        $categories = get_the_category();
        if ( $categories ) {
            echo ' &raquo; <a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">';
            echo esc_html( $categories[0]->name ) . '</a>';
        }
        echo ' &raquo; ' . get_the_title();
    } elseif ( is_page() ) {
        echo ' &raquo; ' . get_the_title();
    } elseif ( is_search() ) {
        echo ' &raquo; ' . __( 'Kết quả tìm kiếm', 'mytheme' );
    } elseif ( is_404() ) {
        echo ' &raquo; 404';
    }

    echo '</nav>';
}

/**
 * 5. CUSTOMIZER
 * Hook: customize_register
 */
add_action( 'customize_register', 'mytheme_customize_register' );

function mytheme_customize_register( $wp_customize ) {
    // Section: Social Links
    $wp_customize->add_section( 'mytheme_social', array(
        'title'    => __( 'Mạng xã hội', 'mytheme' ),
        'priority' => 130,
    ) );

    $socials = array(
        'facebook'  => 'Facebook URL',
        'twitter'   => 'Twitter URL',
        'instagram' => 'Instagram URL',
        'youtube'   => 'YouTube URL',
    );

    foreach ( $socials as $key => $label ) {
        $wp_customize->add_setting( "mytheme_social_{$key}", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );

        $wp_customize->add_control( "mytheme_social_{$key}", array(
            'label'   => $label,
            'section' => 'mytheme_social',
            'type'    => 'url',
        ) );
    }

    // Section: Footer
    $wp_customize->add_section( 'mytheme_footer', array(
        'title'    => __( 'Footer', 'mytheme' ),
        'priority' => 140,
    ) );

    $wp_customize->add_setting( 'mytheme_footer_text', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ) );

    $wp_customize->add_control( 'mytheme_footer_text', array(
        'label'   => __( 'Footer text', 'mytheme' ),
        'section' => 'mytheme_footer',
        'type'    => 'textarea',
    ) );
}

/**
 * 6. CUSTOM EXCERPT
 */
add_filter( 'excerpt_length', function() { return 25; }, 999 );

add_filter( 'excerpt_more', function() {
    return '... <a href="' . esc_url( get_permalink() ) . '" class="read-more">' .
           __( 'Đọc tiếp →', 'mytheme' ) . '</a>';
} );

/**
 * 7. LOAD INCLUDES
 * Tách code ra file riêng để gọn gàng
 */
// require MYTHEME_DIR . '/inc/customizer.php';
// require MYTHEME_DIR . '/inc/template-tags.php';
// require MYTHEME_DIR . '/inc/walker-nav-menu.php';
```

**Giải thích so sánh Laravel:**

| functions.php | Laravel tương đương |
|---------------|---------------------|
| `add_theme_support()` | `config/app.php` providers |
| `register_nav_menus()` | Blade component cho menu |
| `wp_enqueue_style()` | `mix('css/app.css')` trong Blade |
| `register_sidebar()` | Blade slots/components |
| `add_action('after_setup_theme')` | `AppServiceProvider::boot()` |

---

## 4. header.php - Phần Đầu Trang

```php
<?php
/**
 * Header template
 *
 * Hiển thị phần <head> và đầu <body>:
 * - Meta tags, CSS, JS
 * - Site branding (logo, tên site)
 * - Navigation menu chính
 *
 * @package MyTheme
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); // BẮT BUỘC - Output CSS, JS, meta tags từ WP và plugins ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); // Hook cho plugins thêm code ngay sau <body> ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#content">
        <?php esc_html_e( 'Bỏ qua, đến nội dung', 'mytheme' ); ?>
    </a>

    <header id="masthead" class="site-header">
        <div class="header-inner site-container">

            <!-- Logo / Site Title -->
            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                        <?php bloginfo( 'name' ); ?>
                    </a>
                    <?php
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description ) :
                    ?>
                        <p class="site-description"><?php echo esc_html( $description ); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Navigation -->
            <nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Menu chính', 'mytheme' ); ?>">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    &#9776; <?php esc_html_e( 'Menu', 'mytheme' ); ?>
                </button>

                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'depth'          => 2,         // Tối đa 2 cấp dropdown
                    'fallback_cb'    => function() {
                        echo '<ul><li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">';
                        echo esc_html__( 'Thêm menu', 'mytheme' );
                        echo '</a></li></ul>';
                    },
                ) );
                ?>
            </nav>

        </div>
    </header>

    <div id="content" class="site-content site-container">
```

**Các hàm quan trọng:**
- `wp_head()` - **BẮT BUỘC** - Output tất cả CSS, JS, meta đã enqueue
- `body_class()` - Thêm CSS classes tự động vào `<body>` (post type, page template...)
- `wp_body_open()` - Hook cho plugins (analytics, chat widget...)
- `wp_nav_menu()` - Render navigation menu đã đăng ký
- `language_attributes()` - Output `lang="vi"` (hoặc ngôn ngữ site)

---

## 5. footer.php - Phần Chân Trang

```php
<?php
/**
 * Footer template
 *
 * @package MyTheme
 */
?>
    </div><!-- #content .site-content -->

    <footer id="colophon" class="site-footer">
        <div class="site-container">

            <!-- Footer Widgets -->
            <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
                <div class="footer-widgets">
                    <div class="footer-col">
                        <?php dynamic_sidebar( 'footer-1' ); ?>
                    </div>
                    <div class="footer-col">
                        <?php dynamic_sidebar( 'footer-2' ); ?>
                    </div>
                    <div class="footer-col">
                        <?php dynamic_sidebar( 'footer-3' ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer Menu -->
            <?php if ( has_nav_menu( 'footer' ) ) : ?>
                <nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Menu Footer', 'mytheme' ); ?>">
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer',
                        'depth'          => 1,
                        'container'      => false,
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

            <!-- Social Links -->
            <?php
            $socials = array(
                'facebook'  => 'Facebook',
                'twitter'   => 'Twitter',
                'instagram' => 'Instagram',
                'youtube'   => 'YouTube',
            );
            $has_social = false;
            foreach ( $socials as $key => $label ) {
                if ( get_theme_mod( "mytheme_social_{$key}" ) ) {
                    $has_social = true;
                    break;
                }
            }
            if ( $has_social ) :
            ?>
                <div class="social-links">
                    <?php foreach ( $socials as $key => $label ) :
                        $url = get_theme_mod( "mytheme_social_{$key}" );
                        if ( $url ) :
                    ?>
                        <a href="<?php echo esc_url( $url ); ?>"
                           target="_blank" rel="noopener noreferrer"
                           aria-label="<?php echo esc_attr( $label ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </a>
                    <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Credits -->
            <div class="site-credits">
                <?php
                $footer_text = get_theme_mod( 'mytheme_footer_text' );
                if ( $footer_text ) {
                    echo wp_kses_post( $footer_text );
                } else {
                    printf(
                        '&copy; %s <a href="%s">%s</a>. %s',
                        date( 'Y' ),
                        esc_url( home_url( '/' ) ),
                        esc_html( get_bloginfo( 'name' ) ),
                        esc_html__( 'Powered by WordPress.', 'mytheme' )
                    );
                }
                ?>
            </div>

        </div>
    </footer>

</div><!-- #page .site -->

<?php wp_footer(); // BẮT BUỘC - Output JS từ WP và plugins ?>
</body>
</html>
```

---

## 6. index.php - Template Mặc Định

```php
<?php
/**
 * Main template file (fallback cho tất cả)
 *
 * Template hierarchy: Nếu không tìm thấy template cụ thể hơn
 * (single.php, page.php, archive.php...), WordPress sẽ dùng file này.
 *
 * @package MyTheme
 */

get_header(); // Include header.php
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <?php mytheme_breadcrumb(); ?>

        <?php if ( have_posts() ) : ?>

            <?php if ( is_home() && ! is_front_page() ) : ?>
                <header class="page-header">
                    <h1 class="page-title"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>

            <?php
            // === THE LOOP ===
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content', get_post_type() );
            endwhile;
            // === END LOOP ===
            ?>

            <?php mytheme_pagination(); ?>

        <?php else : ?>

            <?php get_template_part( 'template-parts/content', 'none' ); ?>

        <?php endif; ?>

    </main>

    <?php get_sidebar(); // Include sidebar.php ?>
</div>

<?php get_footer(); // Include footer.php ?>
```

**Giải thích The Loop:**
1. `have_posts()` - Kiểm tra còn bài viết không
2. `the_post()` - Set up global `$post` cho bài viết hiện tại
3. `get_template_part('template-parts/content', 'post')` - Include `template-parts/content-post.php`
4. Nếu không có bài → include `template-parts/content-none.php`

---

## 7. single.php - Trang Bài Viết Đơn

```php
<?php
/**
 * Single post template
 *
 * Hiển thị chi tiết một bài viết (post_type = 'post')
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content single-post" role="main">

        <?php mytheme_breadcrumb(); ?>

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

                    <div class="entry-meta">
                        <?php mytheme_posted_on(); ?>

                        <?php
                        $categories = get_the_category_list( ', ' );
                        if ( $categories ) {
                            echo '<span class="sep"> · </span>';
                            echo '<span class="cat-links">' . $categories . '</span>';
                        }
                        ?>

                        <?php if ( ! post_password_required() && comments_open() ) : ?>
                            <span class="sep"> · </span>
                            <?php
                            comments_popup_link(
                                __( '0 bình luận', 'mytheme' ),
                                __( '1 bình luận', 'mytheme' ),
                                __( '% bình luận', 'mytheme' )
                            );
                            ?>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail( 'mytheme-full', array(
                            'alt' => the_title_attribute( 'echo=0' ),
                        ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    the_content();

                    // Phân trang nội bộ bài viết (<!--nextpage-->)
                    wp_link_pages( array(
                        'before' => '<div class="page-links">' . __( 'Trang:', 'mytheme' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div>

                <footer class="entry-footer">
                    <?php mytheme_entry_footer(); ?>
                </footer>

            </article>

            <!-- Bài viết trước/sau -->
            <nav class="post-navigation">
                <div class="nav-links">
                    <?php
                    $prev = get_previous_post();
                    $next = get_next_post();
                    ?>
                    <?php if ( $prev ) : ?>
                        <div class="nav-previous">
                            <span class="nav-label"><?php esc_html_e( '← Bài trước', 'mytheme' ); ?></span>
                            <a href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
                                <?php echo esc_html( $prev->post_title ); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ( $next ) : ?>
                        <div class="nav-next">
                            <span class="nav-label"><?php esc_html_e( 'Bài sau →', 'mytheme' ); ?></span>
                            <a href="<?php echo esc_url( get_permalink( $next ) ); ?>">
                                <?php echo esc_html( $next->post_title ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>

            <?php
            // Bình luận
            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }
            ?>

        <?php endwhile; ?>

    </main>

    <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
```

---

## 8. page.php - Trang Tĩnh

```php
<?php
/**
 * Page template
 *
 * Hiển thị một trang tĩnh (post_type = 'page')
 * Không có sidebar (full width)
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <?php mytheme_breadcrumb(); ?>

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail( 'mytheme-full' ); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    the_content();

                    wp_link_pages( array(
                        'before' => '<div class="page-links">' . __( 'Trang:', 'mytheme' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div>

                <?php edit_post_link( __( 'Sửa trang', 'mytheme' ), '<footer class="entry-footer"><span class="edit-link">', '</span></footer>' ); ?>

            </article>

            <?php
            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }
            ?>

        <?php endwhile; ?>

    </main>
</div>

<?php get_footer(); ?>
```

---

## 9. archive.php - Trang Danh Sách

```php
<?php
/**
 * Archive template
 *
 * Hiển thị danh sách bài viết theo: Category, Tag, Author, Date, CPT archive
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <?php mytheme_breadcrumb(); ?>

        <?php if ( have_posts() ) : ?>

            <header class="archive-header">
                <?php
                the_archive_title( '<h1 class="archive-title">', '</h1>' );
                the_archive_description( '<div class="archive-description">', '</div>' );
                ?>
            </header>

            <div class="posts-list">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/content', get_post_type() );
                endwhile;
                ?>
            </div>

            <?php mytheme_pagination(); ?>

        <?php else : ?>
            <?php get_template_part( 'template-parts/content', 'none' ); ?>
        <?php endif; ?>

    </main>

    <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
```

**Cách tinh chỉnh tiêu đề archive:**

```php
// Trong functions.php - Loại bỏ prefix "Danh mục:", "Thẻ:" khỏi tiêu đề
add_filter( 'get_the_archive_title', function( $title ) {
    if ( is_category() ) {
        return single_cat_title( '', false );
    }
    if ( is_tag() ) {
        return single_tag_title( '', false );
    }
    if ( is_author() ) {
        return get_the_author();
    }
    if ( is_post_type_archive() ) {
        return post_type_archive_title( '', false );
    }
    return $title;
} );
```

---

## 10. search.php - Trang Tìm Kiếm

```php
<?php
/**
 * Search results template
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <?php if ( have_posts() ) : ?>

            <header class="search-header">
                <h1 class="search-title">
                    <?php
                    printf(
                        /* translators: %s: search query */
                        esc_html__( 'Kết quả tìm kiếm cho: "%s"', 'mytheme' ),
                        '<span>' . get_search_query() . '</span>'
                    );
                    ?>
                </h1>
                <p class="search-count">
                    <?php
                    global $wp_query;
                    printf(
                        esc_html__( 'Tìm thấy %d kết quả', 'mytheme' ),
                        $wp_query->found_posts
                    );
                    ?>
                </p>
            </header>

            <?php
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content', 'search' );
            endwhile;
            ?>

            <?php mytheme_pagination(); ?>

        <?php else : ?>

            <div class="no-results">
                <h1><?php esc_html_e( 'Không tìm thấy kết quả', 'mytheme' ); ?></h1>
                <p><?php esc_html_e( 'Không có kết quả phù hợp. Hãy thử từ khóa khác.', 'mytheme' ); ?></p>
                <?php get_search_form(); ?>
            </div>

        <?php endif; ?>

    </main>

    <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
```

---

## 11. 404.php - Trang Không Tìm Thấy

```php
<?php
/**
 * 404 template
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <div class="error-404">
            <h1>404</h1>
            <h2><?php esc_html_e( 'Trang không tồn tại', 'mytheme' ); ?></h2>
            <p><?php esc_html_e( 'Trang bạn tìm kiếm không tồn tại hoặc đã bị xóa.', 'mytheme' ); ?></p>

            <!-- Form tìm kiếm -->
            <?php get_search_form(); ?>

            <!-- Bài viết gần đây -->
            <div class="error-recent-posts">
                <h3><?php esc_html_e( 'Bài viết gần đây', 'mytheme' ); ?></h3>
                <ul>
                    <?php
                    $recent = new WP_Query( array(
                        'posts_per_page' => 5,
                        'no_found_rows'  => true,
                    ) );
                    while ( $recent->have_posts() ) :
                        $recent->the_post();
                    ?>
                        <li>
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            <span class="post-date">(<?php echo get_the_date(); ?>)</span>
                        </li>
                    <?php endwhile; wp_reset_postdata(); ?>
                </ul>
            </div>

            <p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn">
                    <?php esc_html_e( '← Về trang chủ', 'mytheme' ); ?>
                </a>
            </p>
        </div>

    </main>
</div>

<?php get_footer(); ?>
```

---

## 12. sidebar.php - Sidebar

```php
<?php
/**
 * Sidebar template
 *
 * @package MyTheme
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
    return; // Không hiển thị sidebar nếu không có widget
}
?>

<aside id="secondary" class="sidebar widget-area" role="complementary"
       aria-label="<?php esc_attr_e( 'Sidebar', 'mytheme' ); ?>">
    <?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
```

---

## 13. Template Parts

### template-parts/content.php - Nội dung trong loop

```php
<?php
/**
 * Template part: hiển thị bài viết trong loop (blog, archive)
 *
 * @package MyTheme
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>

    <?php mytheme_post_thumbnail(); ?>

    <header class="entry-header">
        <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

        <?php if ( 'post' === get_post_type() ) : ?>
            <div class="entry-meta">
                <?php mytheme_posted_on(); ?>
            </div>
        <?php endif; ?>
    </header>

    <div class="entry-summary">
        <?php the_excerpt(); ?>
    </div>

</article>
```

### template-parts/content-search.php - Kết quả tìm kiếm

```php
<?php
/**
 * Template part: kết quả tìm kiếm
 *
 * @package MyTheme
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card search-result' ); ?>>

    <header class="entry-header">
        <span class="post-type-badge">
            <?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?>
        </span>

        <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

        <div class="entry-meta">
            <?php mytheme_posted_on(); ?>
        </div>
    </header>

    <div class="entry-summary">
        <?php the_excerpt(); ?>
    </div>

</article>
```

### template-parts/content-none.php - Không có bài viết

```php
<?php
/**
 * Template part: không có bài viết nào
 *
 * @package MyTheme
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e( 'Chưa có nội dung', 'mytheme' ); ?></h1>
    </header>

    <div class="page-content">
        <?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
            <p>
                <?php
                printf(
                    /* translators: %s: link to new post */
                    esc_html__( 'Sẵn sàng viết bài đầu tiên? %s', 'mytheme' ),
                    '<a href="' . esc_url( admin_url( 'post-new.php' ) ) . '">' .
                    esc_html__( 'Bắt đầu tại đây', 'mytheme' ) . '</a>'
                );
                ?>
            </p>
        <?php elseif ( is_search() ) : ?>
            <p><?php esc_html_e( 'Không tìm thấy kết quả phù hợp. Hãy thử từ khóa khác.', 'mytheme' ); ?></p>
            <?php get_search_form(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Không có nội dung nào ở đây. Hãy thử tìm kiếm.', 'mytheme' ); ?></p>
            <?php get_search_form(); ?>
        <?php endif; ?>
    </div>
</section>
```

---

## 14. Custom Page Templates

### page-templates/full-width.php

```php
<?php
/**
 * Template Name: Full Width (Không sidebar)
 * Template Post Type: page, post
 *
 * "Template Name:" - WordPress tự nhận diện dòng này
 * Sẽ xuất hiện trong dropdown "Page Attributes → Template" khi chỉnh sửa page
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content full-width-content" role="main">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>

        <?php endwhile; ?>

    </main>
    <!-- Không có get_sidebar() → full width -->
</div>

<?php get_footer(); ?>
```

### page-templates/contact.php

```php
<?php
/**
 * Template Name: Trang Liên Hệ
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <?php while ( have_posts() ) : the_post(); ?>

            <article <?php post_class( 'contact-page' ); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); // Nội dung từ editor ?>
                </div>

                <!-- Thông tin liên hệ -->
                <div class="contact-info">
                    <div class="contact-grid">
                        <div class="contact-item">
                            <h3><?php esc_html_e( 'Địa chỉ', 'mytheme' ); ?></h3>
                            <p><?php echo esc_html( get_theme_mod( 'mytheme_address', '123 Đường ABC, TP.HCM' ) ); ?></p>
                        </div>
                        <div class="contact-item">
                            <h3><?php esc_html_e( 'Email', 'mytheme' ); ?></h3>
                            <p><a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                                <?php echo esc_html( get_option( 'admin_email' ) ); ?>
                            </a></p>
                        </div>
                        <div class="contact-item">
                            <h3><?php esc_html_e( 'Điện thoại', 'mytheme' ); ?></h3>
                            <p><?php echo esc_html( get_theme_mod( 'mytheme_phone', '0123 456 789' ) ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Contact Form (dùng shortcode từ plugin) -->
                <div class="contact-form-section">
                    <h2><?php esc_html_e( 'Gửi tin nhắn cho chúng tôi', 'mytheme' ); ?></h2>
                    <?php echo do_shortcode( '[contact_form]' ); ?>
                </div>

            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php get_footer(); ?>
```

---

## 15. Ví Dụ functions.php Nâng Cao

### 15.1. Custom Walker cho Bootstrap Nav Menu

```php
/**
 * Walker class cho Bootstrap 5 navigation
 * Dùng khi theme sử dụng Bootstrap framework
 */
class Mytheme_Bootstrap_Walker extends Walker_Nav_Menu {
    /**
     * Bắt đầu 1 level (ul)
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $classes = ( $depth === 0 ) ? 'dropdown-menu' : 'dropdown-menu submenu';
        $output .= "\n{$indent}<ul class=\"{$classes}\">\n";
    }

    /**
     * Bắt đầu 1 item (li)
     */
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item    = $data_object;
        $indent  = str_repeat( "\t", $depth );
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        // Thêm class Bootstrap
        $li_classes = array( 'nav-item' );
        if ( in_array( 'menu-item-has-children', $classes, true ) ) {
            $li_classes[] = 'dropdown';
        }
        if ( in_array( 'current-menu-item', $classes, true ) ) {
            $li_classes[] = 'active';
        }

        $output .= $indent . '<li class="' . implode( ' ', $li_classes ) . '">';

        // Link attributes
        $atts = array(
            'href'  => $item->url,
            'class' => ( $depth === 0 ) ? 'nav-link' : 'dropdown-item',
        );

        // Dropdown toggle
        if ( in_array( 'menu-item-has-children', $classes, true ) && $depth === 0 ) {
            $atts['class']         .= ' dropdown-toggle';
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['role']           = 'button';
            $atts['aria-expanded']  = 'false';
        }

        // Build attributes string
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            $attributes .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
        }

        $output .= sprintf( '<a%s>%s</a>', $attributes, esc_html( $item->title ) );
    }
}

// Sử dụng trong header.php:
// wp_nav_menu( array(
//     'theme_location' => 'primary',
//     'container'      => false,
//     'menu_class'     => 'navbar-nav',
//     'walker'         => new Mytheme_Bootstrap_Walker(),
// ) );
```

---

### 15.2. Đăng ký Custom Post Type + Taxonomy từ Theme

```php
/**
 * Đăng ký CPT "Portfolio" + Taxonomy "Portfolio Category"
 * Thường đặt trong plugin, nhưng có thể dùng trong theme cho dự án cụ thể
 */
add_action( 'init', 'mytheme_register_portfolio' );

function mytheme_register_portfolio() {
    // Custom Post Type
    register_post_type( 'portfolio', array(
        'labels' => array(
            'name'               => __( 'Portfolio', 'mytheme' ),
            'singular_name'      => __( 'Project', 'mytheme' ),
            'add_new_item'       => __( 'Thêm Project mới', 'mytheme' ),
            'edit_item'          => __( 'Sửa Project', 'mytheme' ),
            'all_items'          => __( 'Tất cả Projects', 'mytheme' ),
            'search_items'       => __( 'Tìm Project', 'mytheme' ),
            'not_found'          => __( 'Không tìm thấy project nào', 'mytheme' ),
        ),
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-portfolio',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'rewrite'            => array( 'slug' => 'portfolio' ),
        'show_in_rest'       => true,
        'menu_position'      => 5,
    ) );

    // Taxonomy
    register_taxonomy( 'portfolio_category', 'portfolio', array(
        'labels' => array(
            'name'              => __( 'Danh mục Portfolio', 'mytheme' ),
            'singular_name'     => __( 'Danh mục', 'mytheme' ),
            'add_new_item'      => __( 'Thêm danh mục mới', 'mytheme' ),
            'search_items'      => __( 'Tìm danh mục', 'mytheme' ),
        ),
        'hierarchical'      => true,    // true = category style, false = tag style
        'show_admin_column'  => true,
        'show_in_rest'       => true,
        'rewrite'            => array( 'slug' => 'portfolio-cat' ),
    ) );
}

// Flush rewrite rules khi theme activate
add_action( 'after_switch_theme', function() {
    mytheme_register_portfolio();
    flush_rewrite_rules();
} );
```

---

### 15.3. Template cho CPT (archive-portfolio.php)

```php
<?php
/**
 * Archive template cho Portfolio CPT
 * File name phải là: archive-{post_type}.php
 *
 * @package MyTheme
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="main-content" role="main">

        <header class="archive-header">
            <h1 class="archive-title"><?php esc_html_e( 'Portfolio', 'mytheme' ); ?></h1>

            <!-- Filter theo taxonomy -->
            <?php
            $categories = get_terms( array(
                'taxonomy'   => 'portfolio_category',
                'hide_empty' => true,
            ) );
            if ( $categories && ! is_wp_error( $categories ) ) :
            ?>
                <div class="portfolio-filter">
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>"
                       class="filter-btn <?php echo ! is_tax() ? 'active' : ''; ?>">
                        <?php esc_html_e( 'Tất cả', 'mytheme' ); ?>
                    </a>
                    <?php foreach ( $categories as $cat ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                           class="filter-btn <?php echo is_tax( 'portfolio_category', $cat->slug ) ? 'active' : ''; ?>">
                            <?php echo esc_html( $cat->name ); ?>
                            <span class="count">(<?php echo esc_html( $cat->count ); ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="portfolio-grid">
                <?php while ( have_posts() ) : the_post(); ?>

                    <article <?php post_class( 'portfolio-item' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="portfolio-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'mytheme-thumbnail' ); ?>
                                    <div class="portfolio-overlay">
                                        <span><?php esc_html_e( 'Xem chi tiết', 'mytheme' ); ?></span>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="portfolio-info">
                            <h2 class="portfolio-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <?php
                            $client = get_post_meta( get_the_ID(), '_portfolio_client', true );
                            if ( $client ) :
                            ?>
                                <span class="portfolio-client"><?php echo esc_html( $client ); ?></span>
                            <?php endif; ?>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <?php mytheme_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Chưa có project nào.', 'mytheme' ); ?></p>
        <?php endif; ?>

    </main>
</div>

<?php get_footer(); ?>
```

---

## Tổng Kết - Checklist Theme Hoàn Chỉnh

| File | Mục đích | Bắt buộc? |
|------|----------|-----------|
| `style.css` | Khai báo theme + CSS | **Bắt buộc** |
| `index.php` | Template mặc định | **Bắt buộc** |
| `functions.php` | Đăng ký chức năng | Nên có |
| `header.php` | `<head>` + đầu `<body>` | Nên có |
| `footer.php` | Cuối `<body>` | Nên có |
| `single.php` | Bài viết đơn | Nên có |
| `page.php` | Trang tĩnh | Nên có |
| `archive.php` | Danh sách bài | Nên có |
| `search.php` | Kết quả tìm kiếm | Nên có |
| `404.php` | Trang lỗi | Nên có |
| `sidebar.php` | Sidebar widgets | Tùy chọn |
| `comments.php` | Bình luận | Tùy chọn |
| `template-parts/*` | Phần tái sử dụng | Best practice |

**Quy tắc vàng:**
1. **Luôn gọi `wp_head()` và `wp_footer()`** - plugins phụ thuộc vào chúng
2. **Escape mọi output**: `esc_html()`, `esc_attr()`, `esc_url()`
3. **Luôn gọi `wp_reset_postdata()`** sau custom query
4. **Dùng `get_template_part()`** thay vì `include` trực tiếp
5. **Dùng `wp_enqueue_*`** thay vì echo `<script>` / `<link>`

---

## 16. Block Theme (Full Site Editing) - Ví Dụ Hoàn Chỉnh

> Block Theme là kiểu theme mới (WP 5.9+) sử dụng `theme.json` và HTML templates
> thay vì PHP templates. Toàn bộ giao diện được chỉnh sửa bằng Block Editor.

### 16.1. Cấu trúc Block Theme

```
wp-content/themes/my-block-theme/
│
├── style.css                    ← Khai báo theme (BẮT BUỘC)
├── theme.json                   ← Cấu hình theme (thay thế functions.php phần lớn)
├── functions.php                ← Chỉ cần cho PHP logic (tùy chọn)
│
├── templates/                   ← Block templates (HTML)
│   ├── index.html              ← Template mặc định (BẮT BUỘC)
│   ├── single.html             ← Single post
│   ├── page.html               ← Page
│   ├── archive.html            ← Archive
│   ├── search.html             ← Search
│   ├── 404.html                ← 404
│   ├── home.html               ← Blog page
│   └── front-page.html         ← Front page
│
├── parts/                       ← Template parts (HTML)
│   ├── header.html             ← Header
│   ├── footer.html             ← Footer
│   └── sidebar.html            ← Sidebar
│
├── patterns/                    ← Block patterns (PHP)
│   ├── hero-section.php
│   ├── cta-banner.php
│   └── feature-grid.php
│
└── assets/
    ├── css/
    └── images/
```

### 16.2. theme.json - Cấu hình hoàn chỉnh

```json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 2,
    "settings": {
        "appearanceTools": true,
        "useRootPaddingAwareAlignments": true,
        "color": {
            "defaultPalette": false,
            "defaultGradients": false,
            "palette": [
                {
                    "slug": "primary",
                    "color": "#0073aa",
                    "name": "Primary"
                },
                {
                    "slug": "secondary",
                    "color": "#23282d",
                    "name": "Secondary"
                },
                {
                    "slug": "accent",
                    "color": "#00a0d2",
                    "name": "Accent"
                },
                {
                    "slug": "light",
                    "color": "#f7f7f7",
                    "name": "Light"
                },
                {
                    "slug": "dark",
                    "color": "#1d2327",
                    "name": "Dark"
                },
                {
                    "slug": "white",
                    "color": "#ffffff",
                    "name": "White"
                }
            ]
        },
        "typography": {
            "fluid": true,
            "fontFamilies": [
                {
                    "fontFamily": "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                    "name": "Inter",
                    "slug": "inter",
                    "fontFace": [
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "400",
                            "fontStyle": "normal",
                            "src": [ "file:./assets/fonts/Inter-Regular.woff2" ]
                        },
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "600",
                            "fontStyle": "normal",
                            "src": [ "file:./assets/fonts/Inter-SemiBold.woff2" ]
                        },
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "700",
                            "fontStyle": "normal",
                            "src": [ "file:./assets/fonts/Inter-Bold.woff2" ]
                        }
                    ]
                },
                {
                    "fontFamily": "'JetBrains Mono', monospace",
                    "name": "JetBrains Mono",
                    "slug": "jetbrains-mono"
                }
            ],
            "fontSizes": [
                { "slug": "small",   "size": "0.875rem", "name": "Nhỏ" },
                { "slug": "medium",  "size": "1rem",     "name": "Trung bình" },
                { "slug": "large",   "size": "1.25rem",  "name": "Lớn" },
                { "slug": "x-large", "size": "1.75rem",  "name": "Rất lớn" },
                { "slug": "xx-large","size": "2.5rem",   "name": "Khổng lồ" }
            ]
        },
        "spacing": {
            "units": [ "px", "rem", "em", "vh", "vw", "%" ],
            "spacingSizes": [
                { "slug": "10", "size": "0.5rem", "name": "1" },
                { "slug": "20", "size": "1rem",   "name": "2" },
                { "slug": "30", "size": "1.5rem", "name": "3" },
                { "slug": "40", "size": "2rem",   "name": "4" },
                { "slug": "50", "size": "3rem",   "name": "5" },
                { "slug": "60", "size": "4rem",   "name": "6" }
            ]
        },
        "layout": {
            "contentSize": "800px",
            "wideSize": "1200px"
        },
        "custom": {
            "borderRadius": "8px",
            "lineHeight": {
                "body": 1.7,
                "heading": 1.3
            }
        }
    },
    "styles": {
        "color": {
            "background": "var(--wp--preset--color--white)",
            "text": "var(--wp--preset--color--dark)"
        },
        "typography": {
            "fontFamily": "var(--wp--preset--font-family--inter)",
            "fontSize": "var(--wp--preset--font-size--medium)",
            "lineHeight": "var(--wp--custom--line-height--body)"
        },
        "spacing": {
            "padding": {
                "top": "0",
                "bottom": "0",
                "left": "var(--wp--preset--spacing--30)",
                "right": "var(--wp--preset--spacing--30)"
            }
        },
        "elements": {
            "link": {
                "color": {
                    "text": "var(--wp--preset--color--primary)"
                },
                ":hover": {
                    "color": {
                        "text": "var(--wp--preset--color--accent)"
                    }
                }
            },
            "heading": {
                "typography": {
                    "fontWeight": "700",
                    "lineHeight": "var(--wp--custom--line-height--heading)"
                },
                "color": {
                    "text": "var(--wp--preset--color--secondary)"
                }
            },
            "h1": { "typography": { "fontSize": "var(--wp--preset--font-size--xx-large)" } },
            "h2": { "typography": { "fontSize": "var(--wp--preset--font-size--x-large)" } },
            "h3": { "typography": { "fontSize": "var(--wp--preset--font-size--large)" } },
            "button": {
                "border": {
                    "radius": "var(--wp--custom--border-radius)"
                },
                "color": {
                    "background": "var(--wp--preset--color--primary)",
                    "text": "var(--wp--preset--color--white)"
                },
                ":hover": {
                    "color": {
                        "background": "var(--wp--preset--color--accent)"
                    }
                },
                "typography": {
                    "fontWeight": "600"
                }
            }
        },
        "blocks": {
            "core/code": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--jetbrains-mono)"
                },
                "color": {
                    "background": "var(--wp--preset--color--dark)",
                    "text": "#f8f8f2"
                },
                "border": {
                    "radius": "var(--wp--custom--border-radius)"
                }
            },
            "core/quote": {
                "border": {
                    "left": {
                        "color": "var(--wp--preset--color--primary)",
                        "width": "4px",
                        "style": "solid"
                    }
                },
                "spacing": {
                    "padding": {
                        "left": "var(--wp--preset--spacing--30)"
                    }
                }
            },
            "core/navigation": {
                "typography": {
                    "fontSize": "var(--wp--preset--font-size--small)"
                }
            }
        }
    },
    "templateParts": [
        {
            "name": "header",
            "title": "Header",
            "area": "header"
        },
        {
            "name": "footer",
            "title": "Footer",
            "area": "footer"
        },
        {
            "name": "sidebar",
            "title": "Sidebar",
            "area": "uncategorized"
        }
    ],
    "customTemplates": [
        {
            "name": "page-full-width",
            "title": "Full Width (Không sidebar)",
            "postTypes": [ "page", "post" ]
        },
        {
            "name": "page-blank",
            "title": "Blank (Không header/footer)",
            "postTypes": [ "page" ]
        }
    ]
}
```

### 16.3. templates/index.html - Template mặc định

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

    <!-- wp:query-title {"type":"archive"} /-->

    <!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
    <div class="wp-block-query">

        <!-- wp:post-template {"layout":{"type":"default"}} -->

            <!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"},"top":"var:preset|spacing|40"}},"border":{"bottom":{"color":"var:preset|color|light","width":"1px"}}} -->
            <div class="wp-block-group">

                <!-- wp:post-featured-image {"isLink":true,"height":"250px","style":{"border":{"radius":"8px"}}} /-->

                <!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->

                <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} -->
                <div class="wp-block-group">
                    <!-- wp:post-date /-->
                    <!-- wp:paragraph -->
                    <p>·</p>
                    <!-- /wp:paragraph -->
                    <!-- wp:post-author {"showAvatar":false} /-->
                    <!-- wp:paragraph -->
                    <p>·</p>
                    <!-- /wp:paragraph -->
                    <!-- wp:post-terms {"term":"category"} /-->
                </div>
                <!-- /wp:group -->

                <!-- wp:post-excerpt {"moreText":"Đọc tiếp →","excerptLength":30} /-->

            </div>
            <!-- /wp:group -->

        <!-- /wp:post-template -->

        <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
            <!-- wp:query-pagination-previous {"label":"← Trước"} /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next {"label":"Sau →"} /-->
        <!-- /wp:query-pagination -->

        <!-- wp:query-no-results -->
            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center">Không tìm thấy bài viết nào.</p>
            <!-- /wp:paragraph -->
            <!-- wp:search {"label":"Tìm kiếm","buttonText":"Tìm"} /-->
        <!-- /wp:query-no-results -->

    </div>
    <!-- /wp:query -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

### 16.4. parts/header.html - Header template part

```html
<!-- wp:group {"style":{"color":{"background":"var:preset|color|secondary"},"spacing":{"padding":{"top":"0","bottom":"0"}}},"textColor":"white"} -->
<div class="wp-block-group has-white-color has-text-color" style="background-color:var(--wp--preset--color--secondary)">

    <!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <div class="wp-block-group">

        <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
        <div class="wp-block-group">

            <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
            <div class="wp-block-group">
                <!-- wp:site-logo {"width":40} /-->
                <!-- wp:site-title {"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"fontSize":"large"} /-->
            </div>
            <!-- /wp:group -->

            <!-- wp:navigation {"textColor":"white","overlayBackgroundColor":"secondary","overlayTextColor":"white","layout":{"type":"flex","justifyContent":"right"},"fontSize":"small"} -->
                <!-- wp:navigation-link {"label":"Trang chủ","url":"/","kind":"custom"} /-->
                <!-- wp:navigation-link {"label":"Blog","url":"/blog","kind":"custom"} /-->
                <!-- wp:navigation-link {"label":"Giới thiệu","url":"/gioi-thieu","kind":"custom"} /-->
                <!-- wp:navigation-link {"label":"Liên hệ","url":"/lien-he","kind":"custom"} /-->
            <!-- /wp:navigation -->

        </div>
        <!-- /wp:group -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
```

### 16.5. parts/footer.html - Footer template part

```html
<!-- wp:group {"style":{"color":{"background":"var:preset|color|secondary"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|30"}}},"textColor":"white"} -->
<div class="wp-block-group has-white-color has-text-color" style="background-color:var(--wp--preset--color--secondary)">

    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">

        <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
        <div class="wp-block-columns">

            <!-- wp:column -->
            <div class="wp-block-column">
                <!-- wp:heading {"level":4,"textColor":"white","fontSize":"medium"} -->
                <h4 class="wp-block-heading has-white-color has-text-color">Về chúng tôi</h4>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"fontSize":"small"} -->
                <p class="has-small-font-size">Website chia sẻ kiến thức và kinh nghiệm về lập trình WordPress.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->

            <!-- wp:column -->
            <div class="wp-block-column">
                <!-- wp:heading {"level":4,"textColor":"white","fontSize":"medium"} -->
                <h4 class="wp-block-heading has-white-color has-text-color">Liên kết</h4>
                <!-- /wp:heading -->
                <!-- wp:navigation {"textColor":"white","overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"},"fontSize":"small"} -->
                    <!-- wp:navigation-link {"label":"Trang chủ","url":"/"} /-->
                    <!-- wp:navigation-link {"label":"Blog","url":"/blog"} /-->
                    <!-- wp:navigation-link {"label":"Liên hệ","url":"/lien-he"} /-->
                <!-- /wp:navigation -->
            </div>
            <!-- /wp:column -->

            <!-- wp:column -->
            <div class="wp-block-column">
                <!-- wp:heading {"level":4,"textColor":"white","fontSize":"medium"} -->
                <h4 class="wp-block-heading has-white-color has-text-color">Liên hệ</h4>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"fontSize":"small"} -->
                <p class="has-small-font-size">Email: info@example.com<br>Điện thoại: 0123 456 789</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->

        </div>
        <!-- /wp:columns -->

        <!-- wp:separator {"style":{"color":{"background":"rgba(255,255,255,0.1)"}},"className":"is-style-wide"} -->
        <hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background is-style-wide"/>
        <!-- /wp:separator -->

        <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
        <p class="has-text-align-center has-small-font-size">© 2024 Tên website. Powered by WordPress.</p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
```

### 16.6. Block Pattern - Đăng ký pattern tùy chỉnh

```php
<?php
/**
 * File: patterns/hero-section.php
 *
 * Title: Hero Section
 * Slug: mytheme/hero-section
 * Categories: featured, banner
 * Keywords: hero, banner, header, CTA
 * Description: Section hero với tiêu đề lớn, mô tả và nút CTA
 */
?>

<!-- wp:cover {"url":"","dimRatio":70,"overlayColor":"secondary","minHeight":500,"isDark":true,"align":"full"} -->
<div class="wp-block-cover alignfull is-dark" style="min-height:500px">
    <span aria-hidden="true" class="wp-block-cover__background has-secondary-background-color has-background-dim-70 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">

        <!-- wp:group {"layout":{"type":"constrained","contentSize":"700px"}} -->
        <div class="wp-block-group">

            <!-- wp:heading {"textAlign":"center","level":1,"textColor":"white","fontSize":"xx-large"} -->
            <h1 class="wp-block-heading has-text-align-center has-white-color has-text-color has-xx-large-font-size">
                Chào mừng đến với website của chúng tôi
            </h1>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","textColor":"white","fontSize":"large"} -->
            <p class="has-text-align-center has-white-color has-text-color has-large-font-size">
                Khám phá kiến thức WordPress từ cơ bản đến nâng cao
            </p>
            <!-- /wp:paragraph -->

            <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
            <div class="wp-block-buttons">
                <!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"30px","right":"30px"}}}} -->
                <div class="wp-block-button">
                    <a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background" style="border-radius:8px;padding-top:12px;padding-right:30px;padding-bottom:12px;padding-left:30px">
                        Bắt đầu ngay →
                    </a>
                </div>
                <!-- /wp:button -->

                <!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"30px","right":"30px"}}}} -->
                <div class="wp-block-button is-style-outline">
                    <a class="wp-block-button__link" style="border-radius:8px;padding-top:12px;padding-right:30px;padding-bottom:12px;padding-left:30px">
                        Tìm hiểu thêm
                    </a>
                </div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->

        </div>
        <!-- /wp:group -->

    </div>
</div>
<!-- /wp:cover -->
```

### 16.7. functions.php cho Block Theme (tối giản)

```php
<?php
/**
 * Block Theme functions.php
 *
 * Với Block Theme, functions.php rất gọn vì theme.json đã xử lý phần lớn.
 * Chỉ cần đăng ký: styles, scripts, block styles, pattern categories.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue styles
 */
add_action( 'wp_enqueue_scripts', 'mytheme_enqueue_styles' );

function mytheme_enqueue_styles() {
    wp_enqueue_style(
        'mytheme-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );
}

/**
 * Đăng ký Block Pattern Categories
 */
add_action( 'init', 'mytheme_register_pattern_categories' );

function mytheme_register_pattern_categories() {
    register_block_pattern_category( 'mytheme-hero', array(
        'label' => __( 'Hero Sections', 'mytheme' ),
    ) );

    register_block_pattern_category( 'mytheme-cta', array(
        'label' => __( 'Call to Action', 'mytheme' ),
    ) );

    register_block_pattern_category( 'mytheme-features', array(
        'label' => __( 'Features', 'mytheme' ),
    ) );
}

/**
 * Đăng ký Block Styles (biến thể style cho core blocks)
 */
add_action( 'init', 'mytheme_register_block_styles' );

function mytheme_register_block_styles() {
    // Button style: Rounded
    register_block_style( 'core/button', array(
        'name'  => 'rounded',
        'label' => __( 'Bo tròn', 'mytheme' ),
    ) );

    // Image style: Shadow
    register_block_style( 'core/image', array(
        'name'  => 'shadow',
        'label' => __( 'Có bóng đổ', 'mytheme' ),
    ) );

    // Group style: Card
    register_block_style( 'core/group', array(
        'name'  => 'card',
        'label' => __( 'Card', 'mytheme' ),
    ) );
}
```

**So sánh Classic Theme vs Block Theme:**

| Tính năng | Classic Theme | Block Theme |
|-----------|---------------|-------------|
| Template files | `.php` (PHP logic + HTML) | `.html` (Block markup) |
| Cấu hình styles | `functions.php` + CSS | `theme.json` |
| Header/Footer | `get_header()` / `get_footer()` | `<!-- wp:template-part -->` |
| Menus | `register_nav_menus()` + `wp_nav_menu()` | `<!-- wp:navigation -->` |
| Sidebars | `register_sidebar()` + `dynamic_sidebar()` | Group block hoặc Column block |
| Customizer | `customize_register` hook | Site Editor (trực tiếp) |
| Template parts | `get_template_part()` | `<!-- wp:template-part -->` |
| Content loop | PHP `while(have_posts())` | `<!-- wp:query -->` + `<!-- wp:post-template -->` |
| Pagination | `the_posts_pagination()` | `<!-- wp:query-pagination -->` |
| Enqueue assets | Luôn cần `wp_enqueue_*` | `theme.json` cho fonts, chỉ cần enqueue CSS custom |

---

## 17. Custom Walker Nav Menu

> **Walker** là class cho phép bạn tùy chỉnh hoàn toàn HTML output của menu WordPress.
> Mặc định `wp_nav_menu()` tạo HTML cơ bản, nhưng với Walker bạn có thể tạo menu Bootstrap, Tailwind, hoặc bất kỳ framework nào.

### 17.1. Walker Nav Menu cơ bản

```php
<?php
/**
 * Custom Navigation Walker
 * File: inc/class-mytheme-walker-nav-menu.php
 *
 * Tạo menu tương thích Bootstrap 5
 * So sánh Laravel: Không có tương đương trực tiếp
 * (Laravel dùng Blade component hoặc package như spatie/laravel-menu)
 */
class MyTheme_Walker_Nav_Menu extends Walker_Nav_Menu {

    /**
     * Mở tag <ul> cho submenu
     * Gọi khi bắt đầu dropdown
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent  = str_repeat( "\t", $depth );
        $classes = array( 'sub-menu', 'dropdown-menu' );

        if ( $depth > 0 ) {
            $classes[] = 'sub-sub-menu';
        }

        $class_names = implode( ' ', $classes );
        $output .= "\n{$indent}<ul class=\"{$class_names}\">\n";
    }

    /**
     * Đóng tag </ul> cho submenu
     */
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent  = str_repeat( "\t", $depth );
        $output .= "{$indent}</ul>\n";
    }

    /**
     * Render từng menu item (<li> + <a>)
     * Đây là method quan trọng nhất
     */
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $menu_item = $data_object;
        $indent    = ( $depth ) ? str_repeat( "\t", $depth ) : '';

        // === Xử lý CSS classes ===
        $classes   = empty( $menu_item->classes ) ? array() : (array) $menu_item->classes;
        $classes[] = 'menu-item-' . $menu_item->ID;
        $classes[] = 'nav-item';

        // Item có submenu → thêm class dropdown
        if ( $args->walker->has_children ) {
            $classes[] = 'has-dropdown';
            $classes[] = 'dropdown';
        }

        // Item đang active
        if ( in_array( 'current-menu-item', $classes, true ) ) {
            $classes[] = 'active';
        }

        $class_names = implode( ' ', apply_filters(
            'nav_menu_css_class',
            array_filter( $classes ),
            $menu_item, $args, $depth
        ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $id = apply_filters(
            'nav_menu_item_id',
            'menu-item-' . $menu_item->ID,
            $menu_item, $args, $depth
        );
        $id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

        $output .= $indent . '<li' . $id . $class_names . '>';

        // === Xử lý thuộc tính <a> ===
        $atts = array();
        $atts['title']  = ! empty( $menu_item->attr_title ) ? $menu_item->attr_title : '';
        $atts['target'] = ! empty( $menu_item->target ) ? $menu_item->target : '';
        $atts['rel']    = ! empty( $menu_item->xfn ) ? $menu_item->xfn : '';
        $atts['href']   = ! empty( $menu_item->url ) ? $menu_item->url : '';
        $atts['class']  = 'nav-link';

        // Parent dropdown: thêm thuộc tính Bootstrap
        if ( $args->walker->has_children && $depth === 0 ) {
            $atts['class']         .= ' dropdown-toggle';
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['aria-haspopup']  = 'true';
            $atts['aria-expanded']  = 'false';
            $atts['role']           = 'button';
        }

        // Submenu items
        if ( $depth > 0 ) {
            $atts['class'] = 'dropdown-item';
        }

        // Accessibility: đánh dấu trang hiện tại
        if ( in_array( 'current-menu-item', $classes, true ) ) {
            $atts['aria-current'] = 'page';
        }

        $atts       = apply_filters( 'nav_menu_link_attributes', $atts, $menu_item, $args, $depth );
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        // === Build link HTML ===
        $title = apply_filters( 'the_title', $menu_item->title, $menu_item->ID );
        $title = apply_filters( 'nav_menu_item_title', $title, $menu_item, $args, $depth );

        $item_output  = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . $title . $args->link_after;

        // Icon dropdown cho parent items
        if ( $args->walker->has_children && $depth === 0 ) {
            $item_output .= ' <span class="caret">▾</span>';
        }

        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $menu_item, $depth, $args );
    }

    /**
     * Đóng tag </li>
     */
    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $output .= "</li>\n";
    }

    /**
     * Fallback khi chưa gán menu
     */
    public static function fallback( $args ) {
        echo '<ul class="' . esc_attr( $args['menu_class'] ) . '">';
        echo '<li class="nav-item">';
        echo '<a class="nav-link" href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">';
        echo esc_html__( 'Cài đặt Menu', 'mytheme' );
        echo '</a></li></ul>';
    }
}
```

### 17.2. Sử dụng Walker trong header.php

```php
<!-- Trong header.php - Bootstrap 5 Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php
            if ( has_custom_logo() ) {
                the_custom_logo();
            } else {
                echo esc_html( get_bloginfo( 'name' ) );
            }
            ?>
        </a>

        <!-- Nút hamburger (mobile) -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMain"
                aria-controls="navbarMain"
                aria-expanded="false"
                aria-label="<?php esc_attr_e( 'Toggle navigation', 'mytheme' ); ?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <?php
            wp_nav_menu( array(
                'theme_location'  => 'primary',     // Vị trí đã register
                'container'       => false,           // Không bọc thêm div
                'menu_class'      => 'navbar-nav ms-auto', // Class cho <ul>
                'depth'           => 2,               // Tối đa 2 cấp
                'walker'          => new MyTheme_Walker_Nav_Menu(),
                'fallback_cb'     => 'MyTheme_Walker_Nav_Menu::fallback',
            ) );
            ?>
        </div>
    </div>
</nav>
```

### 17.3. Đăng ký menu và load Walker

```php
// Trong functions.php:

// 1. Đăng ký menu locations
add_action( 'after_setup_theme', 'mytheme_register_menus' );

function mytheme_register_menus() {
    register_nav_menus( array(
        'primary'   => __( 'Menu Chính', 'mytheme' ),
        'footer'    => __( 'Menu Footer', 'mytheme' ),
        'mobile'    => __( 'Menu Mobile', 'mytheme' ),
    ) );
}

// 2. Load Walker class
require_once get_template_directory() . '/inc/class-mytheme-walker-nav-menu.php';
```

**Sơ đồ Walker methods được gọi:**

```
wp_nav_menu() gọi Walker:

start_lvl()   ← Mở <ul class="sub-menu">
├── start_el() ← Mở <li> + <a>
├── end_el()   ← Đóng </li>
├── start_el() ← <li> tiếp theo
│   ├── start_lvl() ← Submenu level 2
│   │   ├── start_el()
│   │   └── end_el()
│   └── end_lvl()
└── end_el()
end_lvl()     ← Đóng </ul>
```

**So sánh các phương pháp tạo menu:**

| Phương pháp | Khi nào dùng | Độ phức tạp |
|-------------|-------------|-------------|
| `wp_nav_menu()` mặc định | Menu đơn giản, HTML cơ bản | Thấp |
| Walker custom | Menu Bootstrap/Tailwind, cần HTML tùy chỉnh | Trung bình |
| `wp_get_nav_menu_items()` + loop | Kiểm soát hoàn toàn, mega menu | Cao |
| Block `<!-- wp:navigation -->` | Block Theme (FSE), không cần PHP | Thấp |

---

[← Quay lại: Theme nâng cao](./07-theme-nang-cao.md) | [↑ Mục lục Theme](./index.md) | [→ Tiếp: Sơ đồ & Minh họa](./09-so-do-va-minh-hoa.md)
