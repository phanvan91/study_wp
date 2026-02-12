# Huong Dan Tao Theme WordPress Co Ban

## Muc Luc

1. [Gioi thieu](#1-gioi-thieu)
2. [Yeu cau toi thieu](#2-yeu-cau-toi-thieu)
3. [Cau truc thu muc theme](#3-cau-truc-thu-muc-theme)
4. [Style.css Header](#4-stylecss-header)
5. [Template Hierarchy](#5-template-hierarchy)
6. [Tao theme tu dau - Step by step](#6-tao-theme-tu-dau---step-by-step)
7. [Template Tags](#7-template-tags)
8. [The Loop](#8-the-loop)
9. [Navigation Menus](#9-navigation-menus)
10. [Sidebars va Widgets](#10-sidebars-va-widgets)
11. [Theme Supports](#11-theme-supports)
12. [Customizer API](#12-customizer-api)
13. [theme.json - Block Theme](#13-themejson---block-theme)
14. [Child Theme](#14-child-theme)
15. [Best Practices](#15-best-practices)

---

## 1. Gioi Thieu

### Theme la gi?

Theme WordPress la tap hop cac file PHP, CSS, JavaScript va hinh anh quyet dinh giao dien va cach hien thi noi dung cua website. Theme khong thay doi noi dung (du lieu) ma chi thay doi cach trinh bay.

### Tai sao tu tao theme?

- **Tuy chinh hoan toan:** Thiet ke chinh xac theo yeu cau
- **Hieu nang tot hon:** Khong co code thua nhu theme co san
- **Hieu sau WordPress:** Nam vung cach WordPress hoat dong
- **Kinh doanh:** Ban theme tren marketplace

---

## 2. Yeu Cau Toi Thieu

Mot theme WordPress hop le chi can **2 file**:

1. **`style.css`** - File CSS chinh voi header thong tin theme
2. **`index.php`** - Template mac dinh (fallback)

WordPress se nhan dien bat ky thu muc nao trong `wp-content/themes/` co 2 file nay la mot theme.

---

## 3. Cau Truc Thu Muc Theme

### Cau truc day du

```
my-theme/
├── style.css                # File CSS chinh + header (BAT BUOC)
├── index.php                # Template fallback (BAT BUOC)
├── functions.php            # Dang ky chuc nang theme
├── screenshot.png           # Anh preview (1200x900px)
│
├── header.php               # Phan dau trang (doctype, head, nav)
├── footer.php               # Phan cuoi trang (footer, wp_footer)
├── sidebar.php              # Sidebar mac dinh
│
├── single.php               # Template bai viet don
├── page.php                 # Template trang tinh
├── archive.php              # Template trang archive
├── search.php               # Template trang ket qua tim kiem
├── 404.php                  # Template trang 404
├── comments.php             # Template phan binh luan
│
├── front-page.php           # Template trang chu (neu cau hinh)
├── home.php                 # Template trang blog
│
├── category.php             # Template archive theo category
├── tag.php                  # Template archive theo tag
├── author.php               # Template archive theo tac gia
├── date.php                 # Template archive theo ngay
│
├── single-{post-type}.php   # Template cho custom post type
├── archive-{post-type}.php  # Archive cho custom post type
├── taxonomy-{taxonomy}.php  # Template cho custom taxonomy
├── page-{slug}.php          # Template cho trang cu the
│
├── template-parts/          # Cac phan template tai su dung
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
├── inc/                     # File PHP phu tro
│   ├── customizer.php
│   ├── template-functions.php
│   └── template-tags.php
│
└── languages/
    └── my-theme-vi.po
```

---

## 4. Style.css Header

File `style.css` **bat buoc** phai co khoi comment header de WordPress nhan dien theme:

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

| Truong | Mo ta |
|--------|-------|
| Theme Name | Ten theme hien thi trong admin (bat buoc) |
| Template | Ten thu muc parent theme (chi dung cho child theme) |
| Version | Phien ban hien tai |
| Text Domain | Identifier cho da ngon ngu |
| Tags | Tags de tim kiem tren WordPress.org |

---

## 5. Template Hierarchy

WordPress tu dong chon template dua tren loai trang dang xem. Thu tu uu tien tu trai sang phai:

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
front-page.php → (tuy cau hinh) home.php hoac page.php → index.php
```

---

## 6. Tao Theme Tu Dau - Step by Step

### Buoc 1: Tao style.css

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

### Buoc 2: Tao functions.php

```php
<?php
/**
 * Learn Theme functions va definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LEARN_THEME_VERSION', '1.0.0' );

/**
 * Thiet lap theme
 */
function learn_theme_setup() {
    // Ho tro title tag tu dong
    add_theme_support( 'title-tag' );

    // Ho tro post thumbnail (anh dai dien)
    add_theme_support( 'post-thumbnails' );

    // Ho tro custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Ho tro HTML5 cho cac thanh phan
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Ho tro custom background
    add_theme_support( 'custom-background', array(
        'default-color' => 'f5f5f5',
    ) );

    // Ho tro block editor styles
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );

    // Dang ky menu
    register_nav_menus( array(
        'primary' => 'Menu Chinh',
        'footer'  => 'Menu Footer',
    ) );

    // Kich thuoc anh tuy chinh
    add_image_size( 'learn-featured', 800, 400, true );
    add_image_size( 'learn-thumbnail', 300, 200, true );
}
add_action( 'after_setup_theme', 'learn_theme_setup' );

/**
 * Enqueue styles va scripts
 */
function learn_theme_scripts() {
    // Style chinh
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

    // Comment reply script (chi load khi can)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'learn_theme_scripts' );

/**
 * Dang ky sidebar
 */
function learn_theme_widgets_init() {
    register_sidebar( array(
        'name'          => 'Sidebar Chinh',
        'id'            => 'sidebar-1',
        'description'   => 'Khu vuc widget o sidebar chinh.',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => 'Footer Widget',
        'id'            => 'footer-1',
        'description'   => 'Khu vuc widget o footer.',
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

### Buoc 3: Tao header.php

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

### Buoc 4: Tao footer.php

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

### Buoc 5: Tao sidebar.php

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

### Buoc 6: Tao index.php

```php
<?php
/**
 * Template chinh - Fallback cho tat ca cac trang
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
                        Ngay: <?php echo get_the_date(); ?>
                    </span>
                    <span class="posted-by">
                        | Tac gia: <?php the_author(); ?>
                    </span>
                    <span class="post-categories">
                        | Danh muc: <?php the_category( ', ' ); ?>
                    </span>
                </div>
            </header>

            <div class="entry-summary">
                <?php the_excerpt(); ?>
            </div>

            <a href="<?php the_permalink(); ?>" class="read-more">Doc tiep &rarr;</a>
        </article>

    <?php endwhile; ?>

    <div class="pagination">
        <?php
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => '&laquo; Truoc',
            'next_text' => 'Tiep &raquo;',
        ) );
        ?>
    </div>

<?php else : ?>

    <article class="no-results">
        <h1>Khong tim thay noi dung</h1>
        <p>Xin loi, khong co bai viet nao phu hop voi yeu cau cua ban.</p>
        <?php get_search_form(); ?>
    </article>

<?php endif; ?>

<?php
get_footer();
```

### Buoc 7: Tao single.php

```php
<?php
/**
 * Template cho bai viet don (single post)
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>
            <div class="entry-meta">
                <span class="posted-on">Ngay: <?php echo get_the_date(); ?></span>
                <span class="posted-by"> | Tac gia: <?php the_author_posts_link(); ?></span>
                <span class="post-categories"> | Danh muc: <?php the_category( ', ' ); ?></span>
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

            // Phan trang trong bai viet (khi dung <!--nextpage-->)
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

        <!-- Dieu huong bai viet truoc/sau -->
        <nav class="post-navigation">
            <div class="nav-previous">
                <?php previous_post_link( '%link', '&laquo; %title' ); ?>
            </div>
            <div class="nav-next">
                <?php next_post_link( '%link', '%title &raquo;' ); ?>
            </div>
        </nav>

        <!-- Binh luan -->
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

### Buoc 8: Tao page.php

```php
<?php
/**
 * Template cho trang tinh (static page)
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

### Buoc 9: Tao archive.php

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
                    Ngay: <?php echo get_the_date(); ?> | Tac gia: <?php the_author(); ?>
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
    <p>Khong tim thay bai viet nao.</p>
<?php endif; ?>

<?php
get_footer();
```

### Buoc 10: Tao search.php

```php
<?php
/**
 * Template trang ket qua tim kiem
 */

get_header();
?>

<header class="search-header">
    <h1>
        <?php
        printf(
            'Ket qua tim kiem cho: "%s"',
            '<span>' . esc_html( get_search_query() ) . '</span>'
        );
        ?>
    </h1>
    <?php get_search_form(); ?>
</header>

<?php if ( have_posts() ) : ?>

    <p class="search-count">
        Tim thay <?php echo $wp_query->found_posts; ?> ket qua.
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
        <h2>Khong tim thay ket qua</h2>
        <p>Thu tim kiem voi tu khoa khac.</p>
        <?php get_search_form(); ?>
    </article>
<?php endif; ?>

<?php
get_footer();
```

### Buoc 11: Tao 404.php

```php
<?php
/**
 * Template trang 404 - Khong tim thay trang
 */

get_header();
?>

<article class="error-404">
    <h1>404 - Khong Tim Thay Trang</h1>
    <p>Xin loi, trang ban dang tim khong ton tai hoac da bi xoa.</p>

    <div class="error-404-content">
        <h3>Ban co the thu:</h3>
        <ul>
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Quay ve trang chu</a></li>
            <li>Tim kiem noi dung ban can:</li>
        </ul>
        <?php get_search_form(); ?>

        <h3>Bai viet moi nhat:</h3>
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

### Buoc 12: Tao comments.php

```php
<?php
/**
 * Template cho phan binh luan
 */

// Khong hien thi neu file duoc truy cap truc tiep
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            printf(
                '%d binh luan',
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
                <div class="nav-previous"><?php previous_comments_link( 'Binh luan cu hon' ); ?></div>
                <div class="nav-next"><?php next_comments_link( 'Binh luan moi hon' ); ?></div>
            </nav>
        <?php endif; ?>

    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() ) : ?>
        <p class="no-comments">Binh luan da dong.</p>
    <?php endif; ?>

    <?php
    comment_form( array(
        'title_reply'        => 'De lai binh luan',
        'label_submit'       => 'Gui binh luan',
        'comment_notes_after' => '',
    ) );
    ?>
</div>
```

---

## 7. Template Tags

Cac ham thuong dung trong template:

### Thong tin bai viet

```php
the_title();                    // Hien thi tieu de
get_the_title();                // Tra ve tieu de (khong echo)
the_content();                  // Hien thi noi dung day du
the_excerpt();                  // Hien thi tom tat
get_the_excerpt();              // Tra ve tom tat
the_permalink();                // Hien thi URL bai viet
get_the_permalink();            // Tra ve URL
the_ID();                       // Hien thi ID bai viet
get_the_ID();                   // Tra ve ID
the_author();                   // Hien thi ten tac gia
the_author_posts_link();        // Hien thi ten tac gia voi link
the_date();                     // Hien thi ngay dang
get_the_date( 'd/m/Y' );       // Tra ve ngay theo format
the_time( 'H:i' );             // Hien thi gio
the_category( ', ' );           // Hien thi danh muc
the_tags( 'Tags: ', ', ' );     // Hien thi tags
```

### Anh dai dien (Post Thumbnail)

```php
has_post_thumbnail();                      // Kiem tra co thumbnail khong
the_post_thumbnail();                      // Hien thi thumbnail
the_post_thumbnail( 'large' );             // Kich thuoc cu the
the_post_thumbnail( 'learn-featured' );    // Kich thuoc tuy chinh
get_the_post_thumbnail_url();              // Tra ve URL thumbnail
```

### Thong tin site

```php
bloginfo( 'name' );             // Ten website
bloginfo( 'description' );     // Mo ta website
bloginfo( 'charset' );         // Character set
bloginfo( 'url' );             // URL trang chu
bloginfo( 'template_url' );    // URL thu muc theme
home_url( '/' );               // URL trang chu
admin_url();                    // URL admin
get_template_directory();       // Duong dan thu muc theme (server)
get_template_directory_uri();   // URL thu muc theme
get_stylesheet_uri();           // URL file style.css
```

### Cac ham template

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

**The Loop** la co che WordPress dung de lap qua cac bai viet va hien thi chung.

### Loop co ban

```php
<?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>

        <h2><?php the_title(); ?></h2>
        <div><?php the_content(); ?></div>

    <?php endwhile; ?>
<?php else : ?>
    <p>Khong co bai viet nao.</p>
<?php endif; ?>
```

### Giai thich

1. `have_posts()` - Kiem tra con bai viet nao de hien thi khong
2. `the_post()` - Chuyen den bai viet tiep theo va thiet lap du lieu
3. Ben trong loop, dung cac template tags de hien thi thong tin
4. Phan `else` xu ly truong hop khong co bai viet

### Custom Loop voi WP_Query

```php
<?php
// Tao query tuy chinh
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
    wp_reset_postdata();  // QUAN TRONG: Reset lai global $post
else :
    echo '<p>Khong co bai viet.</p>';
endif;
?>
```

---

## 9. Navigation Menus

### Dang ky menu

```php
// Trong functions.php
register_nav_menus( array(
    'primary' => 'Menu Chinh',
    'footer'  => 'Menu Footer',
    'mobile'  => 'Menu Mobile',
) );
```

### Hien thi menu

```php
<?php
wp_nav_menu( array(
    'theme_location' => 'primary',      // Vi tri menu da dang ky
    'menu_id'        => 'primary-menu',  // ID cua the ul
    'menu_class'     => 'nav-menu',      // Class cua the ul
    'container'      => 'nav',           // The boc ngoai (false de tat)
    'container_class' => 'main-nav',     // Class cua container
    'depth'          => 2,               // Do sau menu (0 = khong gioi han)
    'fallback_cb'    => false,           // Khong hien thi gi neu chua co menu
) );
?>
```

### Custom Walker (tuy chinh HTML output)

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

// Su dung
wp_nav_menu( array(
    'theme_location' => 'primary',
    'walker'         => new Learn_Theme_Walker_Nav(),
) );
```

---

## 10. Sidebars va Widgets

### Dang ky sidebar

```php
function learn_theme_widgets_init() {
    // Sidebar chinh
    register_sidebar( array(
        'name'          => 'Sidebar Chinh',
        'id'            => 'sidebar-1',
        'description'   => 'Khu vuc widget ben phai.',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'learn_theme_widgets_init' );
```

### Hien thi sidebar

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

// Title tag tu dong
add_theme_support( 'title-tag' );

// Post thumbnails (anh dai dien)
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

Tao cac tuy chon trong **Appearance > Customize**:

```php
/**
 * Dang ky Customizer settings
 */
function learn_theme_customize_register( $wp_customize ) {
    // --- SECTION: Cai dat chung ---
    $wp_customize->add_section( 'learn_theme_general', array(
        'title'    => 'Cai Dat Theme',
        'priority' => 30,
    ) );

    // Setting: Mau chu dao
    $wp_customize->add_setting( 'primary_color', array(
        'default'           => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',  // Cap nhat live preview
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'primary_color',
        array(
            'label'   => 'Mau chu dao',
            'section' => 'learn_theme_general',
        )
    ) );

    // Setting: So dien thoai
    $wp_customize->add_setting( 'phone_number', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'phone_number', array(
        'label'   => 'So dien thoai',
        'section' => 'learn_theme_general',
        'type'    => 'text',
    ) );

    // Setting: Hien thi sidebar
    $wp_customize->add_setting( 'show_sidebar', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );

    $wp_customize->add_control( 'show_sidebar', array(
        'label'   => 'Hien thi sidebar',
        'section' => 'learn_theme_general',
        'type'    => 'checkbox',
    ) );
}
add_action( 'customize_register', 'learn_theme_customize_register' );

/**
 * Output CSS tu Customizer settings
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

### Su dung Customizer values trong template

```php
<?php
$phone = get_theme_mod( 'phone_number', '' );
if ( ! empty( $phone ) ) :
?>
    <p>Lien he: <?php echo esc_html( $phone ); ?></p>
<?php endif; ?>

<?php if ( get_theme_mod( 'show_sidebar', true ) ) : ?>
    <?php get_sidebar(); ?>
<?php endif; ?>
```

---

## 13. theme.json - Block Theme

File `theme.json` cau hinh block editor va theme settings:

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
                    "name": "Mau chinh"
                },
                {
                    "slug": "secondary",
                    "color": "#23282d",
                    "name": "Mau phu"
                },
                {
                    "slug": "light",
                    "color": "#f5f5f5",
                    "name": "Mau sang"
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
                { "slug": "small", "size": "0.875rem", "name": "Nho" },
                { "slug": "medium", "size": "1rem", "name": "Trung binh" },
                { "slug": "large", "size": "1.5rem", "name": "Lon" },
                { "slug": "x-large", "size": "2rem", "name": "Rat lon" }
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

### Tao Child Theme

**Buoc 1:** Tao thu muc `wp-content/themes/learn-theme-child/`

**Buoc 2:** Tao `style.css`:

```css
/*
Theme Name:  Learn Theme Child
Template:    learn-theme
Description: Child theme cua Learn Theme.
Version:     1.0.0
Author:      Developer
Text Domain: learn-theme-child
*/

/* CSS tuy chinh o day */
.site-header {
    background: #1a3a5c;
}
```

> Truong `Template` phai trung voi ten thu muc cua parent theme.

**Buoc 3:** Tao `functions.php`:

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

Copy file tu parent theme vao child theme de override:

```
Parent: wp-content/themes/learn-theme/single.php
Child:  wp-content/themes/learn-theme-child/single.php  (file nay se duoc dung)
```

---

## 15. Best Practices

### Accessibility

- Su dung HTML semantic (`<nav>`, `<main>`, `<article>`, `<aside>`)
- Them `skip-to-content` link
- Dam bao contrast ratio du
- Su dung `aria-label` khi can

### Responsive

- Dung relative units (rem, em, %)
- Dung flexbox hoac CSS grid
- Test tren nhieu kich thuoc man hinh

### Performance

- Chi enqueue scripts/styles khi can
- Su dung `wp_enqueue_script` voi `in_footer = true`
- Toi uu hinh anh
- Tranh inline CSS/JS khi co the

### Security

- Escape tat ca output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize tat ca input
- Su dung `wp_nonce` cho forms
- Khong trust du lieu tu `$_GET`, `$_POST`, `$_REQUEST`

### Coding Standards

- Theo WordPress Coding Standards
- Comment code day du
- Su dung text domain cho tat ca chuoi hien thi

---

## Tai Lieu Tham Khao

- [WordPress Theme Handbook](https://developer.wordpress.org/themes/)
- [Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/)
- [Theme.json Reference](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)
- [Customizer API](https://developer.wordpress.org/themes/customize-api/)
