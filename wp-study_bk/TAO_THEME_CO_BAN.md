# Hướng Dẫn Tạo Theme WordPress Cơ Bản

## Mục Lục

1. [Giới thiệu](#1-gioi-thieu)
2. [Yêu cầu tối thiểu](#2-yeu-cau-toi-thieu)
3. [Cấu trúc thư mục theme](#3-cau-truc-thu-muc-theme)
4. [Style.css Header](#4-stylecss-header)
5. [Template Hierarchy](#5-template-hierarchy)
6. [Tạo theme từ đầu - Step by step](#6-tao-theme-tu-dau---step-by-step)
7. [Template Tags](#7-template-tags)
8. [The Loop](#8-the-loop)
9. [Navigation Menus](#9-navigation-menus)
10. [Sidebars và Widgets](#10-sidebars-va-widgets)
11. [Theme Supports](#11-theme-supports)
12. [Customizer API](#12-customizer-api)
13. [theme.json - Block Theme](#13-themejson---block-theme)
14. [Child Theme](#14-child-theme)
15. [Best Practices](#15-best-practices)

---

## 1. Giới Thiệu

### Theme là gì?

Theme WordPress là tập hợp các file PHP, CSS, JavaScript và hình ảnh quyết định giao diện và cách hiển thị nội dung của website. Theme không thay đổi nội dung (dữ liệu) mà chỉ thay đổi cách trình bày.

### Tại sao tự tạo theme?

- **Tuỳ chỉnh hoàn toàn:** Thiết kế chính xác theo yêu cầu
- **Hiệu năng tốt hơn:** Không có code thừa như theme có sẵn
- **Hiểu sâu WordPress:** Nắm vững cách WordPress hoạt động
- **Kinh doanh:** Bán theme trên marketplace

---

## 2. Yêu Cầu Tối Thiểu

Một theme WordPress hợp lệ chỉ cần **2 file**:

1. **`style.css`** - File CSS chính với header thông tin theme
2. **`index.php`** - Template mặc định (fallback)

WordPress sẽ nhận diện bất kỳ thư mục nào trong `wp-content/themes/` có 2 file này là một theme.

---

## 3. Cấu Trúc Thư Mục Theme

### Cấu trúc đầy đủ

```
my-theme/
├── style.css                # File CSS chính + header (BẮT BUỘC)
├── index.php                # Template fallback (BẮT BUỘC)
├── functions.php            # Đăng ký chức năng theme
├── screenshot.png           # Ảnh preview (1200x900px)
│
├── header.php               # Phần đầu trang (doctype, head, nav)
├── footer.php               # Phần cuối trang (footer, wp_footer)
├── sidebar.php              # Sidebar mặc định
│
├── single.php               # Template bài viết đơn
├── page.php                 # Template trang tĩnh
├── archive.php              # Template trang archive
├── search.php               # Template trang kết quả tìm kiếm
├── 404.php                  # Template trang 404
├── comments.php             # Template phần bình luận
│
├── front-page.php           # Template trang chủ (nếu cấu hình)
├── home.php                 # Template trang blog
│
├── category.php             # Template archive theo category
├── tag.php                  # Template archive theo tag
├── author.php               # Template archive theo tác giả
├── date.php                 # Template archive theo ngày
│
├── single-{post-type}.php   # Template cho custom post type
├── archive-{post-type}.php  # Archive cho custom post type
├── taxonomy-{taxonomy}.php  # Template cho custom taxonomy
├── page-{slug}.php          # Template cho trang cụ thể
│
├── template-parts/          # Các phần template tái sử dụng
│   ├── content.php
│   ├── content-single.php
│   ├── content-page.php
│   ├── content-search.php
│   └── content-none.php
│
├── assets/
│   ├── css/
│   │   └── custom.css
│   ├── js/
│   │   └── main.js
│   ├── images/
│   │   └── logo.png
│   └── fonts/
│
├── inc/                     # File PHP phụ trợ
│   ├── customizer.php
│   ├── template-functions.php
│   └── template-tags.php
│
└── languages/
    └── my-theme-vi.po
```

---

## 4. Style.css Header

File `style.css` **bắt buộc** phải có khối comment header để WordPress nhận diện theme:

```css
/*
Theme Name:        My Theme
Theme URI:         https://example.com/my-theme
Author:            Ten Tac Gia
Author URI:        https://example.com
Description:       Mo ta ngan gon ve theme.
Version:           1.0.0
Requires at least: 6.0
Tested up to:      6.5
Requires PHP:      8.0
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       my-theme
Tags:              blog, custom-menu, featured-images, responsive-layout
*/

/* CSS code bat dau tu day */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}
```

| Trường | Mô tả |
|--------|-------|
| Theme Name | Tên theme hiển thị trong admin (bắt buộc) |
| Template | Tên thư mục parent theme (chỉ dùng cho child theme) |
| Version | Phiên bản hiện tại |
| Text Domain | Identifier cho đa ngôn ngữ |
| Tags | Tags để tìm kiếm trên WordPress.org |

---

## 5. Template Hierarchy

WordPress tự động chọn template dựa trên loại trang đang xem. Thứ tự ưu tiên từ trái sang phải:

### Single Post
```
single-{post-type}-{slug}.php → single-{post-type}.php → single.php → singular.php → index.php
```

### Page
```
page-{slug}.php → page-{id}.php → page.php → singular.php → index.php
```

### Category Archive
```
category-{slug}.php → category-{id}.php → category.php → archive.php → index.php
```

### Tag Archive
```
tag-{slug}.php → tag-{id}.php → tag.php → archive.php → index.php
```

### Custom Post Type Archive
```
archive-{post-type}.php → archive.php → index.php
```

### Custom Taxonomy
```
taxonomy-{taxonomy}-{term}.php → taxonomy-{taxonomy}.php → taxonomy.php → archive.php → index.php
```

### Author Archive
```
author-{nicename}.php → author-{id}.php → author.php → archive.php → index.php
```

### Search Results
```
search.php → index.php
```

### 404 Page
```
404.php → index.php
```

### Front Page
```
front-page.php → (tuỳ cấu hình) home.php hoặc page.php → index.php
```

---

## 6. Tạo Theme Từ Đầu - Step by Step

### Bước 1: Tạo style.css

```css
/*
Theme Name:        Learn Theme
Author:            Developer
Description:       Theme hoc tap WordPress co ban.
Version:           1.0.0
Requires at least: 6.0
Requires PHP:      8.0
License:           GPL v2 or later
Text Domain:       learn-theme
*/

/* === RESET === */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* === BASE === */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 16px;
    line-height: 1.6;
    color: #333;
    background-color: #f5f5f5;
}

a {
    color: #0073aa;
    text-decoration: none;
}

a:hover {
    color: #005177;
    text-decoration: underline;
}

img {
    max-width: 100%;
    height: auto;
}

/* === LAYOUT === */
.site {
    max-width: 1200px;
    margin: 0 auto;
    background: #fff;
}

.site-header {
    background: #23282d;
    color: #fff;
    padding: 20px 30px;
}

.site-header a {
    color: #fff;
}

.site-content {
    display: flex;
    padding: 30px;
    gap: 30px;
}

.content-area {
    flex: 1;
}

.widget-area {
    width: 300px;
    flex-shrink: 0;
}

.site-footer {
    background: #23282d;
    color: #aaa;
    padding: 20px 30px;
    text-align: center;
}

/* === NAVIGATION === */
.main-navigation ul {
    list-style: none;
    display: flex;
    gap: 20px;
}

.main-navigation a {
    color: #fff;
    padding: 5px 0;
}

/* === POST === */
.post, .page {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.entry-title {
    font-size: 1.8em;
    margin-bottom: 10px;
}

.entry-meta {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 15px;
}

.entry-content p {
    margin-bottom: 1em;
}

.post-thumbnail img {
    width: 100%;
    height: auto;
    margin-bottom: 15px;
}

/* === WIDGETS === */
.widget {
    margin-bottom: 25px;
}

.widget-title {
    font-size: 1.1em;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 2px solid #0073aa;
}

.widget ul {
    list-style: none;
}

.widget li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

/* === PAGINATION === */
.pagination {
    margin-top: 30px;
    text-align: center;
}

.pagination a, .pagination span {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 3px;
    border: 1px solid #ddd;
}

.pagination .current {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .site-content {
        flex-direction: column;
    }

    .widget-area {
        width: 100%;
    }

    .main-navigation ul {
        flex-direction: column;
        gap: 5px;
    }
}
```

### Bước 2: Tạo functions.php

```php
<?php
/**
 * Learn Theme functions và definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LEARN_THEME_VERSION', '1.0.0' );

/**
 * Thiết lập theme
 */
function learn_theme_setup() {
    // Hỗ trợ title tag tự động
    add_theme_support( 'title-tag' );

    // Hỗ trợ post thumbnail (ảnh đại diện)
    add_theme_support( 'post-thumbnails' );

    // Hỗ trợ custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Hỗ trợ HTML5 cho các thành phần
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Hỗ trợ custom background
    add_theme_support( 'custom-background', array(
        'default-color' => 'f5f5f5',
    ) );

    // Hỗ trợ block editor styles
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );

    // Đăng ký menu
    register_nav_menus( array(
        'primary' => 'Menu Chính',
        'footer'  => 'Menu Footer',
    ) );

    // Kích thước ảnh tuỳ chỉnh
    add_image_size( 'learn-featured', 800, 400, true );
    add_image_size( 'learn-thumbnail', 300, 200, true );
}
add_action( 'after_setup_theme', 'learn_theme_setup' );

/**
 * Enqueue styles và scripts
 */
function learn_theme_scripts() {
    // Style chính
    wp_enqueue_style(
        'learn-theme-style',
        get_stylesheet_uri(),
        array(),
        LEARN_THEME_VERSION
    );

    // JavaScript
    wp_enqueue_script(
        'learn-theme-script',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        LEARN_THEME_VERSION,
        true
    );

    // Comment reply script (chỉ load khi cần)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'learn_theme_scripts' );

/**
 * Đăng ký sidebar
 */
function learn_theme_widgets_init() {
    register_sidebar( array(
        'name'          => 'Sidebar Chính',
        'id'            => 'sidebar-1',
        'description'   => 'Khu vực widget ở sidebar chính.',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => 'Footer Widget',
        'id'            => 'footer-1',
        'description'   => 'Khu vực widget ở footer.',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'learn_theme_widgets_init' );

/**
 * Custom excerpt length
 */
function learn_theme_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'learn_theme_excerpt_length' );

/**
 * Custom excerpt more
 */
function learn_theme_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'learn_theme_excerpt_more' );
```

### Bước 3: Tạo header.php

```php
<?php
/**
 * Header template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="site-header">
        <div class="site-branding">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <h1 class="site-title">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <?php bloginfo( 'name' ); ?>
                    </a>
                </h1>
                <?php
                $description = get_bloginfo( 'description', 'display' );
                if ( $description ) :
                ?>
                    <p class="site-description"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <nav class="main-navigation">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'fallback_cb'    => false,
            ) );
            ?>
        </nav>
    </header>

    <div class="site-content">
        <main class="content-area">
```

### Bước 4: Tạo footer.php

```php
<?php
/**
 * Footer template
 */
?>
        </main><!-- .content-area -->

        <?php get_sidebar(); ?>

    </div><!-- .site-content -->

    <footer class="site-footer">
        <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
            <div class="footer-widgets">
                <?php dynamic_sidebar( 'footer-1' ); ?>
            </div>
        <?php endif; ?>

        <div class="site-info">
            <p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
        </div>

        <?php
        wp_nav_menu( array(
            'theme_location' => 'footer',
            'menu_id'        => 'footer-menu',
            'container'      => false,
            'fallback_cb'    => false,
            'depth'          => 1,
        ) );
        ?>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
```

### Bước 5: Tạo sidebar.php

```php
<?php
/**
 * Sidebar template
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
    return;
}
?>

<aside class="widget-area">
    <?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
```

### Bước 6: Tạo index.php

```php
<?php
/**
 * Template chính - Fallback cho tất cả các trang
 */

get_header();
?>

<?php if ( have_posts() ) : ?>

    <?php if ( is_home() && ! is_front_page() ) : ?>
        <h1 class="page-title"><?php single_post_title(); ?></h1>
    <?php endif; ?>

    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail( 'learn-featured' ); ?>
                    </a>
                </div>
            <?php endif; ?>

            <header class="entry-header">
                <h2 class="entry-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <div class="entry-meta">
                    <span class="posted-on">
                        Ngày: <?php echo get_the_date(); ?>
                    </span>
                    <span class="posted-by">
                        | Tác giả: <?php the_author(); ?>
                    </span>
                    <span class="post-categories">
                        | Danh mục: <?php the_category( ', ' ); ?>
                    </span>
                </div>
            </header>

            <div class="entry-summary">
                <?php the_excerpt(); ?>
            </div>

            <a href="<?php the_permalink(); ?>" class="read-more">Đọc tiếp &rarr;</a>
        </article>

    <?php endwhile; ?>

    <div class="pagination">
        <?php
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => '&laquo; Trước',
            'next_text' => 'Tiếp &raquo;',
        ) );
        ?>
    </div>

<?php else : ?>

    <article class="no-results">
        <h1>Không tìm thấy nội dung</h1>
        <p>Xin lỗi, không có bài viết nào phù hợp với yêu cầu của bạn.</p>
        <?php get_search_form(); ?>
    </article>

<?php endif; ?>

<?php
get_footer();
```

### Bước 7: Tạo single.php

```php
<?php
/**
 * Template cho bài viết đơn (single post)
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>
            <div class="entry-meta">
                <span class="posted-on">Ngày: <?php echo get_the_date(); ?></span>
                <span class="posted-by"> | Tác giả: <?php the_author_posts_link(); ?></span>
                <span class="post-categories"> | Danh mục: <?php the_category( ', ' ); ?></span>
                <?php if ( has_tag() ) : ?>
                    <span class="post-tags"> | Tags: <?php the_tags( '', ', ' ); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <?php if ( has_post_thumbnail() ) : ?>
            <div class="post-thumbnail">
                <?php the_post_thumbnail( 'large' ); ?>
            </div>
        <?php endif; ?>

        <div class="entry-content">
            <?php
            the_content();

            // Phân trang trong bài viết (khi dùng <!--nextpage-->)
            wp_link_pages( array(
                'before' => '<div class="page-links">Trang:',
                'after'  => '</div>',
            ) );
            ?>
        </div>

        <footer class="entry-footer">
            <?php if ( has_tag() ) : ?>
                <div class="post-tags">
                    <?php the_tags( '<strong>Tags:</strong> ', ', ' ); ?>
                </div>
            <?php endif; ?>
        </footer>

        <!-- Điều hướng bài viết trước/sau -->
        <nav class="post-navigation">
            <div class="nav-previous">
                <?php previous_post_link( '%link', '&laquo; %title' ); ?>
            </div>
            <div class="nav-next">
                <?php next_post_link( '%link', '%title &raquo;' ); ?>
            </div>
        </nav>

        <!-- Bình luận -->
        <?php
        if ( comments_open() || get_comments_number() ) {
            comments_template();
        }
        ?>
    </article>

<?php endwhile; ?>

<?php
get_footer();
```

### Bước 8: Tạo page.php

```php
<?php
/**
 * Template cho trang tĩnh (static page)
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>
        </header>

        <?php if ( has_post_thumbnail() ) : ?>
            <div class="post-thumbnail">
                <?php the_post_thumbnail( 'large' ); ?>
            </div>
        <?php endif; ?>

        <div class="entry-content">
            <?php the_content(); ?>
        </div>

        <?php
        if ( comments_open() || get_comments_number() ) {
            comments_template();
        }
        ?>
    </article>

<?php endwhile; ?>

<?php
get_footer();
```

### Bước 9: Tạo archive.php

```php
<?php
/**
 * Template cho trang archive (category, tag, author, date)
 */

get_header();
?>

<header class="archive-header">
    <?php
    the_archive_title( '<h1 class="archive-title">', '</h1>' );
    the_archive_description( '<div class="archive-description">', '</div>' );
    ?>
</header>

<?php if ( have_posts() ) : ?>

    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail( 'learn-thumbnail' ); ?>
                    </a>
                </div>
            <?php endif; ?>

            <header class="entry-header">
                <h2 class="entry-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <div class="entry-meta">
                    Ngày: <?php echo get_the_date(); ?> | Tác giả: <?php the_author(); ?>
                </div>
            </header>

            <div class="entry-summary">
                <?php the_excerpt(); ?>
            </div>
        </article>

    <?php endwhile; ?>

    <div class="pagination">
        <?php the_posts_pagination(); ?>
    </div>

<?php else : ?>
    <p>Không tìm thấy bài viết nào.</p>
<?php endif; ?>

<?php
get_footer();
```

### Bước 10: Tạo search.php

```php
<?php
/**
 * Template trang kết quả tìm kiếm
 */

get_header();
?>

<header class="search-header">
    <h1>
        <?php
        printf(
            'Kết quả tìm kiếm cho: "%s"',
            '<span>' . esc_html( get_search_query() ) . '</span>'
        );
        ?>
    </h1>
    <?php get_search_form(); ?>
</header>

<?php if ( have_posts() ) : ?>

    <p class="search-count">
        Tìm thấy <?php echo $wp_query->found_posts; ?> kết quả.
    </p>

    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h2 class="entry-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            <div class="entry-summary">
                <?php the_excerpt(); ?>
            </div>
        </article>

    <?php endwhile; ?>

    <div class="pagination">
        <?php the_posts_pagination(); ?>
    </div>

<?php else : ?>
    <article class="no-results">
        <h2>Không tìm thấy kết quả</h2>
        <p>Thử tìm kiếm với từ khoá khác.</p>
        <?php get_search_form(); ?>
    </article>
<?php endif; ?>

<?php
get_footer();
```

### Bước 11: Tạo 404.php

```php
<?php
/**
 * Template trang 404 - Không tìm thấy trang
 */

get_header();
?>

<article class="error-404">
    <h1>404 - Không Tìm Thấy Trang</h1>
    <p>Xin lỗi, trang bạn đang tìm không tồn tại hoặc đã bị xoá.</p>

    <div class="error-404-content">
        <h3>Bạn có thể thử:</h3>
        <ul>
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Quay về trang chủ</a></li>
            <li>Tìm kiếm nội dung bạn cần:</li>
        </ul>
        <?php get_search_form(); ?>

        <h3>Bài viết mới nhất:</h3>
        <ul>
            <?php
            $recent_posts = wp_get_recent_posts( array(
                'numberposts' => 5,
                'post_status' => 'publish',
            ) );
            foreach ( $recent_posts as $post ) :
            ?>
                <li>
                    <a href="<?php echo get_permalink( $post['ID'] ); ?>">
                        <?php echo esc_html( $post['post_title'] ); ?>
                    </a>
                </li>
            <?php endforeach; wp_reset_postdata(); ?>
        </ul>
    </div>
</article>

<?php
get_footer();
```

### Bước 12: Tạo comments.php

```php
<?php
/**
 * Template cho phần bình luận
 */

// Không hiển thị nếu file được truy cập trực tiếp
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            printf(
                '%d bình luận',
                get_comments_number()
            );
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 50,
            ) );
            ?>
        </ol>

        <?php if ( get_comment_pages_count() > 1 ) : ?>
            <nav class="comment-navigation">
                <div class="nav-previous"><?php previous_comments_link( 'Bình luận cũ hơn' ); ?></div>
                <div class="nav-next"><?php next_comments_link( 'Bình luận mới hơn' ); ?></div>
            </nav>
        <?php endif; ?>

    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() ) : ?>
        <p class="no-comments">Bình luận đã đóng.</p>
    <?php endif; ?>

    <?php
    comment_form( array(
        'title_reply'        => 'Để lại bình luận',
        'label_submit'       => 'Gửi bình luận',
        'comment_notes_after' => '',
    ) );
    ?>
</div>
```

---

## 7. Template Tags

Các hàm thường dùng trong template:

### Thông tin bài viết

```php
the_title();                    // Hiển thị tiêu đề
get_the_title();                // Trả về tiêu đề (không echo)
the_content();                  // Hiển thị nội dung đầy đủ
the_excerpt();                  // Hiển thị tóm tắt
get_the_excerpt();              // Trả về tóm tắt
the_permalink();                // Hiển thị URL bài viết
get_the_permalink();            // Trả về URL
the_ID();                       // Hiển thị ID bài viết
get_the_ID();                   // Trả về ID
the_author();                   // Hiển thị tên tác giả
the_author_posts_link();        // Hiển thị tên tác giả với link
the_date();                     // Hiển thị ngày đăng
get_the_date( 'd/m/Y' );       // Trả về ngày theo format
the_time( 'H:i' );             // Hiển thị giờ
the_category( ', ' );           // Hiển thị danh mục
the_tags( 'Tags: ', ', ' );     // Hiển thị tags
```

### Ảnh đại diện (Post Thumbnail)

```php
has_post_thumbnail();                      // Kiểm tra có thumbnail không
the_post_thumbnail();                      // Hiển thị thumbnail
the_post_thumbnail( 'large' );             // Kích thước cụ thể
the_post_thumbnail( 'learn-featured' );    // Kích thước tuỳ chỉnh
get_the_post_thumbnail_url();              // Trả về URL thumbnail
```

### Thông tin site

```php
bloginfo( 'name' );             // Tên website
bloginfo( 'description' );     // Mô tả website
bloginfo( 'charset' );         // Character set
bloginfo( 'url' );             // URL trang chủ
bloginfo( 'template_url' );    // URL thư mục theme
home_url( '/' );               // URL trang chủ
admin_url();                    // URL admin
get_template_directory();       // Đường dẫn thư mục theme (server)
get_template_directory_uri();   // URL thư mục theme
get_stylesheet_uri();           // URL file style.css
```

### Các hàm template

```php
get_header();                   // Load header.php
get_header( 'custom' );        // Load header-custom.php
get_footer();                   // Load footer.php
get_sidebar();                  // Load sidebar.php
get_sidebar( 'left' );         // Load sidebar-left.php
get_search_form();              // Load searchform.php
get_template_part( 'template-parts/content' );           // Load template-parts/content.php
get_template_part( 'template-parts/content', 'single' ); // Load template-parts/content-single.php
```

---

## 8. The Loop

**The Loop** là cơ chế WordPress dùng để lặp qua các bài viết và hiển thị chúng.

### Loop cơ bản

```php
<?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>

        <h2><?php the_title(); ?></h2>
        <div><?php the_content(); ?></div>

    <?php endwhile; ?>
<?php else : ?>
    <p>Không có bài viết nào.</p>
<?php endif; ?>
```

### Giải thích

1. `have_posts()` - Kiểm tra còn bài viết nào để hiển thị không
2. `the_post()` - Chuyển đến bài viết tiếp theo và thiết lập dữ liệu
3. Bên trong loop, dùng các template tags để hiển thị thông tin
4. Phần `else` xử lý trường hợp không có bài viết

### Custom Loop với WP_Query

```php
<?php
// Tạo query tuỳ chỉnh
$custom_query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 5,
    'category_name'  => 'tin-tuc',
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

if ( $custom_query->have_posts() ) :
    while ( $custom_query->have_posts() ) : $custom_query->the_post();
?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php the_excerpt(); ?></p>
<?php
    endwhile;
    wp_reset_postdata();  // QUAN TRỌNG: Reset lại global $post
else :
    echo '<p>Không có bài viết.</p>';
endif;
?>
```

---

## 9. Navigation Menus

### Đăng ký menu

```php
// Trong functions.php
register_nav_menus( array(
    'primary' => 'Menu Chính',
    'footer'  => 'Menu Footer',
    'mobile'  => 'Menu Mobile',
) );
```

### Hiển thị menu

```php
<?php
wp_nav_menu( array(
    'theme_location' => 'primary',      // Vị trí menu đã đăng ký
    'menu_id'        => 'primary-menu',  // ID của thẻ ul
    'menu_class'     => 'nav-menu',      // Class của thẻ ul
    'container'      => 'nav',           // Thẻ bọc ngoài (false để tắt)
    'container_class' => 'main-nav',     // Class của container
    'depth'          => 2,               // Độ sâu menu (0 = không giới hạn)
    'fallback_cb'    => false,           // Không hiển thị gì nếu chưa có menu
) );
?>
```

### Custom Walker (tuỳ chỉnh HTML output)

```php
class Learn_Theme_Walker_Nav extends Walker_Nav_Menu {
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = implode( ' ', $item->classes );
        $output .= '<li class="' . esc_attr( $classes ) . '">';
        $output .= '<a href="' . esc_url( $item->url ) . '"';

        if ( $item->current ) {
            $output .= ' class="active"';
        }

        $output .= '>' . esc_html( $item->title ) . '</a>';
    }
}

// Sử dụng
wp_nav_menu( array(
    'theme_location' => 'primary',
    'walker'         => new Learn_Theme_Walker_Nav(),
) );
```

---

## 10. Sidebars và Widgets

### Đăng ký sidebar

```php
function learn_theme_widgets_init() {
    // Sidebar chính
    register_sidebar( array(
        'name'          => 'Sidebar Chính',
        'id'            => 'sidebar-1',
        'description'   => 'Khu vực widget bên phải.',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'learn_theme_widgets_init' );
```

### Hiển thị sidebar

```php
<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
    <aside class="widget-area">
        <?php dynamic_sidebar( 'sidebar-1' ); ?>
    </aside>
<?php endif; ?>
```

---

## 11. Theme Supports

```php
// Trong functions.php, hook after_setup_theme

// Title tag tự động
add_theme_support( 'title-tag' );

// Post thumbnails (ảnh đại diện)
add_theme_support( 'post-thumbnails' );

// Custom logo
add_theme_support( 'custom-logo' );

// Custom background
add_theme_support( 'custom-background' );

// Custom header
add_theme_support( 'custom-header' );

// HTML5 markup
add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );

// Post formats
add_theme_support( 'post-formats', array( 'aside', 'gallery', 'quote', 'image', 'video' ) );

// Automatic feed links
add_theme_support( 'automatic-feed-links' );

// Block editor
add_theme_support( 'wp-block-styles' );
add_theme_support( 'responsive-embeds' );
add_theme_support( 'align-wide' );
add_theme_support( 'editor-styles' );
add_editor_style( 'assets/css/editor-style.css' );
```

---

## 12. Customizer API

Tạo các tuỳ chọn trong **Appearance > Customize**:

```php
/**
 * Đăng ký Customizer settings
 */
function learn_theme_customize_register( $wp_customize ) {
    // --- SECTION: Cài đặt chung ---
    $wp_customize->add_section( 'learn_theme_general', array(
        'title'    => 'Cài Đặt Theme',
        'priority' => 30,
    ) );

    // Setting: Màu chủ đạo
    $wp_customize->add_setting( 'primary_color', array(
        'default'           => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',  // Cập nhật live preview
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'primary_color',
        array(
            'label'   => 'Màu chủ đạo',
            'section' => 'learn_theme_general',
        )
    ) );

    // Setting: Số điện thoại
    $wp_customize->add_setting( 'phone_number', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'phone_number', array(
        'label'   => 'Số điện thoại',
        'section' => 'learn_theme_general',
        'type'    => 'text',
    ) );

    // Setting: Hiển thị sidebar
    $wp_customize->add_setting( 'show_sidebar', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );

    $wp_customize->add_control( 'show_sidebar', array(
        'label'   => 'Hiển thị sidebar',
        'section' => 'learn_theme_general',
        'type'    => 'checkbox',
    ) );
}
add_action( 'customize_register', 'learn_theme_customize_register' );

/**
 * Output CSS từ Customizer settings
 */
function learn_theme_customizer_css() {
    $color = get_theme_mod( 'primary_color', '#0073aa' );
    ?>
    <style>
        a { color: <?php echo esc_attr( $color ); ?>; }
        .widget-title { border-color: <?php echo esc_attr( $color ); ?>; }
        .pagination .current { background: <?php echo esc_attr( $color ); ?>; }
    </style>
    <?php
}
add_action( 'wp_head', 'learn_theme_customizer_css' );
```

### Sử dụng Customizer values trong template

```php
<?php
$phone = get_theme_mod( 'phone_number', '' );
if ( ! empty( $phone ) ) :
?>
    <p>Liên hệ: <?php echo esc_html( $phone ); ?></p>
<?php endif; ?>

<?php if ( get_theme_mod( 'show_sidebar', true ) ) : ?>
    <?php get_sidebar(); ?>
<?php endif; ?>
```

---

## 13. theme.json - Block Theme

File `theme.json` cấu hình block editor và theme settings:

```json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "settings": {
        "color": {
            "palette": [
                {
                    "slug": "primary",
                    "color": "#0073aa",
                    "name": "Màu chính"
                },
                {
                    "slug": "secondary",
                    "color": "#23282d",
                    "name": "Màu phụ"
                },
                {
                    "slug": "light",
                    "color": "#f5f5f5",
                    "name": "Màu sáng"
                }
            ]
        },
        "typography": {
            "fontFamilies": [
                {
                    "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                    "slug": "system",
                    "name": "System Font"
                }
            ],
            "fontSizes": [
                { "slug": "small", "size": "0.875rem", "name": "Nhỏ" },
                { "slug": "medium", "size": "1rem", "name": "Trung bình" },
                { "slug": "large", "size": "1.5rem", "name": "Lớn" },
                { "slug": "x-large", "size": "2rem", "name": "Rất lớn" }
            ]
        },
        "spacing": {
            "units": ["px", "em", "rem", "%"]
        },
        "layout": {
            "contentSize": "800px",
            "wideSize": "1200px"
        }
    },
    "styles": {
        "color": {
            "background": "#ffffff",
            "text": "#333333"
        },
        "typography": {
            "fontFamily": "var(--wp--preset--font-family--system)",
            "fontSize": "var(--wp--preset--font-size--medium)",
            "lineHeight": "1.6"
        }
    }
}
```

---

## 14. Child Theme

### Tạo Child Theme

**Bước 1:** Tạo thư mục `wp-content/themes/learn-theme-child/`

**Bước 2:** Tạo `style.css`:

```css
/*
Theme Name:  Learn Theme Child
Template:    learn-theme
Description: Child theme của Learn Theme.
Version:     1.0.0
Author:      Developer
Text Domain: learn-theme-child
*/

/* CSS tuỳ chỉnh ở đây */
.site-header {
    background: #1a3a5c;
}
```

> Trường `Template` phải trùng với tên thư mục của parent theme.

**Bước 3:** Tạo `functions.php`:

```php
<?php
/**
 * Child theme functions
 */

function learn_child_enqueue_styles() {
    // Load parent theme style
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    // Load child theme style
    wp_enqueue_style(
        'child-style',
        get_stylesheet_uri(),
        array( 'parent-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'learn_child_enqueue_styles' );
```

### Override template files

Copy file từ parent theme vào child theme để override:

```
Parent: wp-content/themes/learn-theme/single.php
Child:  wp-content/themes/learn-theme-child/single.php  (file này sẽ được dùng)
```

---

## 15. Best Practices

### Accessibility

- Sử dụng HTML semantic (`<nav>`, `<main>`, `<article>`, `<aside>`)
- Thêm `skip-to-content` link
- Đảm bảo contrast ratio đủ
- Sử dụng `aria-label` khi cần

### Responsive

- Dùng relative units (rem, em, %)
- Dùng flexbox hoặc CSS grid
- Test trên nhiều kích thước màn hình

### Performance

- Chỉ enqueue scripts/styles khi cần
- Sử dụng `wp_enqueue_script` với `in_footer = true`
- Tối ưu hình ảnh
- Tránh inline CSS/JS khi có thể

### Security

- Escape tất cả output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize tất cả input
- Sử dụng `wp_nonce` cho forms
- Không trust dữ liệu từ `$_GET`, `$_POST`, `$_REQUEST`

### Coding Standards

- Theo WordPress Coding Standards
- Comment code đầy đủ
- Sử dụng text domain cho tất cả chuỗi hiển thị

---

## Tài Liệu Tham Khảo

- [WordPress Theme Handbook](https://developer.wordpress.org/themes/)
- [Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/)
- [Theme.json Reference](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)
- [Customizer API](https://developer.wordpress.org/themes/customize-api/)
