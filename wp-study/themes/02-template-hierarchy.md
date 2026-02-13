# Template Hierarchy trong WordPress

## Mục Lục

1. [Template Hierarchy là gì](#1-template-hierarchy-la-gi)
2. [Sơ đồ Template Hierarchy đầy đủ](#2-so-do-template-hierarchy)
3. [Các template files chi tiết](#3-cac-template-files)
4. [Template Parts](#4-template-parts)
5. [Header, Footer, Sidebar](#5-header-footer-sidebar)
6. [Conditional Tags](#6-conditional-tags)
7. [Template cho Custom Post Types](#7-template-cho-custom-post-types)
8. [Template cho Custom Taxonomies](#8-template-cho-custom-taxonomies)
9. [Code ví dụ cho từng template](#9-code-vi-du)
10. [So sánh với Laravel Blade](#10-so-sanh-voi-laravel)
11. [Best Practices](#11-best-practices)

---

## 1. Template Hierarchy là gì

Template Hierarchy (Thứ bậc Template) là **hệ thống tự động chọn template file** của WordPress. Khi người dùng truy cập một URL, WordPress sẽ xác định loại nội dung và tìm template file phù hợp nhất theo thứ tự ưu tiên.

### Cách hoạt động:

```
Người dùng truy cập URL
        |
        v
WordPress xác định loại nội dung (post, page, category, tag...)
        |
        v
Tìm template file theo thứ tự ưu tiên (từ cụ thể nhất đến chung nhất)
        |
        v
Dùng template file đầu tiên tìm thấy
        |
        v
Nếu không tìm thấy template nào -> dùng index.php (fallback cuối cùng)
```

### Ví dụ cụ thể:

Khi truy cập `example.com/category/tin-tuc/`, WordPress tìm theo thứ tự:

```
1. category-tin-tuc.php        (theo slug của category)
2. category-5.php              (theo ID của category)
3. category.php                (template chung cho category)
4. archive.php                 (template chung cho archive)
5. index.php                   (fallback cuối cùng)
```

Tìm thấy file nào trước thì dùng file đó.

### So sánh với Laravel:

```php
// LARAVEL - Bạn tự định nghĩa route và controller
Route::get('/category/{slug}', [CategoryController::class, 'show']);
// Bạn phải viết code để mapping URL -> View

// WORDPRESS - Tự động mapping URL -> Template file
// Bạn chỉ cần tạo file category.php là xong!
// Không cần viết route, không cần controller
```

---

## 2. Sơ đồ Template Hierarchy

### Sơ đồ tổng quát (ASCII Art):

```
                                    +------------------+
                                    |    index.php     |
                                    | (Fallback cuối)  |
                                    +--------+---------+
                                             |
                    +------------------------+------------------------+
                    |                        |                        |
            +-------+-------+       +-------+-------+       +-------+-------+
            | Singular      |       | Archive       |       | Đặc biệt      |
            +-------+-------+       +-------+-------+       +-------+-------+
                    |                        |                        |
        +-----------+-----------+    +-------+-------+       +-------+-------+
        |           |           |    |       |       |       |       |       |
   +----+----+ +----+----+ +---+---+ |  +---+---+   |   +---+---+ +---+---+
   |single.php| |page.php | |attach | |  |categ. |   |   |search | | 404   |
   +---------+ +---------+ |ment   | |  +-------+   |   +-------+ +-------+
   |single-   | |page-    | |.php   | |  |tag.php|   |
   |{post-    | |{slug}   | +-------+ |  +-------+   |
   |type}.php | |.php     |           |  |author |   |
   +---------+ |page-    |           |  |.php   |   |
               |{id}.php |           |  +-------+   |
               +---------+           |  |date.php|  |
                                      |  +-------+  |
                                      |  |archive|  |
                                      |  |.php   |  |
                                      +--+-------+--+
```

### Sơ đồ chi tiết theo từng loại trang:

```
=== TRANG CHU (Front Page) ===
front-page.php --> home.php --> index.php

Lưu ý: front-page.php luôn được ưu tiên nhất khi Settings > Reading
       có cấu hình "A static page"

=== TRANG BLOG (Blog Posts Index) ===
home.php --> index.php

=== BÀI VIẾT ĐƠN (Single Post) ===
single-{post-type}-{slug}.php     (WP 4.4+)
  --> single-{post-type}.php
    --> single.php
      --> singular.php
        --> index.php

Ví dụ: Bài viết "Hello World" (post type: post)
single-post-hello-world.php
  --> single-post.php
    --> single.php
      --> singular.php
        --> index.php

Ví dụ: Custom post type "product", bài "iphone"
single-product-iphone.php
  --> single-product.php
    --> single.php
      --> singular.php
        --> index.php

=== TRANG TĨNH (Page) ===
{custom-template}.php             (template được chọn trong editor)
  --> page-{slug}.php
    --> page-{id}.php
      --> page.php
        --> singular.php
          --> index.php

Ví dụ: Trang "About Us" (ID: 10)
page-about-us.php
  --> page-10.php
    --> page.php
      --> singular.php
        --> index.php

=== DANH MỤC (Category) ===
category-{slug}.php
  --> category-{id}.php
    --> category.php
      --> archive.php
        --> index.php

=== THẺ (Tag) ===
tag-{slug}.php
  --> tag-{id}.php
    --> tag.php
      --> archive.php
        --> index.php

=== TÁC GIẢ (Author) ===
author-{nicename}.php
  --> author-{id}.php
    --> author.php
      --> archive.php
        --> index.php

=== NGÀY THÁNG (Date) ===
date.php
  --> archive.php
    --> index.php

=== CUSTOM POST TYPE ARCHIVE ===
archive-{post-type}.php
  --> archive.php
    --> index.php

=== CUSTOM TAXONOMY ===
taxonomy-{taxonomy}-{term}.php
  --> taxonomy-{taxonomy}.php
    --> taxonomy.php
      --> archive.php
        --> index.php

=== TÌM KIẾM (Search) ===
search.php
  --> index.php

=== LỖI 404 ===
404.php
  --> index.php

=== FILE ĐÍNH KÈM (Attachment) ===
{MIME-type}.php (image.php, video.php, application.php)
  --> attachment.php
    --> single-attachment-{slug}.php
      --> single-attachment.php
        --> single.php
          --> singular.php
            --> index.php

=== EMBED (WP 4.5+) ===
embed-{post-type}-{post-format}.php
  --> embed-{post-type}.php
    --> embed.php
```

---

## 3. Các template files chi tiết

### index.php - Template mặc định (bắt buộc)

```php
<?php
/**
 * index.php - Template fallback cuối cùng
 * Mỗi theme PHẢI có file này
 * Khi không tìm thấy template cụ thể hơn, WordPress dùng file này
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main">

    <?php if ( have_posts() ) : ?>

        <!-- Hiển thị tiêu đề trang archive nếu cần -->
        <?php if ( is_archive() ) : ?>
            <header class="page-header">
                <?php
                the_archive_title( '<h1 class="page-title">', '</h1>' );
                the_archive_description( '<div class="archive-description">', '</div>' );
                ?>
            </header>
        <?php endif; ?>

        <?php
        while ( have_posts() ) :
            the_post();

            /*
             * Load template part dựa trên post format
             * Sẽ tìm: template-parts/content-{format}.php
             * Nếu không có, dùng: template-parts/content.php
             */
            get_template_part( 'template-parts/content', get_post_format() );

        endwhile;

        // Pagination
        the_posts_pagination();
        ?>

    <?php else : ?>

        <?php get_template_part( 'template-parts/content', 'none' ); ?>

    <?php endif; ?>

</main>

<?php
get_sidebar();
get_footer();
```

### front-page.php - Trang chủ

```php
<?php
/**
 * front-page.php - Template cho trang chủ
 * Chỉ hoạt động khi: Settings > Reading > "A static page" > Homepage
 *
 * Đây là template có ưu tiên CAO NHẤT cho trang chủ
 * Kể cả khi bạn chọn static page, file này vẫn được ưu tiên hơn page-{slug}.php
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main front-page">

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1><?php echo esc_html( get_theme_mod( 'hero_title', 'Chào mừng đến với Website' ) ); ?></h1>
            <p><?php echo esc_html( get_theme_mod( 'hero_subtitle', 'Mô tả ngắn về website của bạn' ) ); ?></p>
            <a href="<?php echo esc_url( get_theme_mod( 'hero_button_url', '#' ) ); ?>" class="btn-primary">
                <?php echo esc_html( get_theme_mod( 'hero_button_text', 'Tìm hiểu thêm' ) ); ?>
            </a>
        </div>
    </section>

    <!-- Bài viết mới nhất -->
    <section class="latest-posts">
        <div class="container">
            <h2><?php esc_html_e( 'Bài Viết Mới Nhất', 'developer-theme' ); ?></h2>

            <?php
            // Custom query để lấy 6 bài viết mới nhất
            $latest_posts = new WP_Query( array(
                'post_type'      => 'post',
                'posts_per_page' => 6,
                'post_status'    => 'publish',
            ) );

            if ( $latest_posts->have_posts() ) :
            ?>
                <div class="posts-grid">
                    <?php
                    while ( $latest_posts->have_posts() ) :
                        $latest_posts->the_post();
                        get_template_part( 'template-parts/content', 'card' );
                    endwhile;
                    ?>
                </div>
            <?php
            endif;
            wp_reset_postdata(); // QUAN TRỌNG: Reset lại query gốc
            ?>

        </div>
    </section>

    <!-- Nội dung của Page (nếu có) -->
    <?php
    while ( have_posts() ) :
        the_post();
        if ( get_the_content() ) :
    ?>
        <section class="page-content">
            <div class="container">
                <?php the_content(); ?>
            </div>
        </section>
    <?php
        endif;
    endwhile;
    ?>

</main>

<?php get_footer(); ?>
```

### home.php - Trang blog

```php
<?php
/**
 * home.php - Template cho trang danh sách bài viết (blog)
 *
 * Hoạt động khi:
 * - Settings > Reading > "Your latest posts" (trang chủ là blog)
 * - Settings > Reading > "A static page" > Posts page: chọn 1 trang
 *
 * Khác với front-page.php:
 * - front-page.php: trang chủ (homepage)
 * - home.php: trang blog (danh sách bài viết)
 * Nếu "homepage displays latest posts" -> cả 2 đều là 1 trang
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main blog-page">
    <div class="container">
        <div class="content-area">

            <div class="main-content">
                <header class="page-header">
                    <h1 class="page-title">
                        <?php
                        // Nếu có trang blog riêng, hiển thị tên trang đó
                        if ( is_home() && ! is_front_page() ) {
                            single_post_title();
                        } else {
                            esc_html_e( 'Blog', 'developer-theme' );
                        }
                        ?>
                    </h1>
                </header>

                <?php if ( have_posts() ) : ?>

                    <!-- Bài viết đầu tiên (featured) -->
                    <?php
                    the_post(); // Lấy bài đầu tiên
                    ?>
                    <article class="featured-post">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="featured-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'large' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <h2 class="entry-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <div class="entry-excerpt"><?php the_excerpt(); ?></div>
                    </article>

                    <!-- Các bài viết còn lại -->
                    <div class="posts-grid">
                        <?php
                        while ( have_posts() ) :
                            the_post();
                            get_template_part( 'template-parts/content', 'card' );
                        endwhile;
                        ?>
                    </div>

                    <?php the_posts_pagination(); ?>

                <?php else : ?>
                    <?php get_template_part( 'template-parts/content', 'none' ); ?>
                <?php endif; ?>

            </div><!-- .main-content -->

            <?php get_sidebar(); ?>

        </div><!-- .content-area -->
    </div><!-- .container -->
</main>

<?php get_footer(); ?>
```

### single.php - Bài viết đơn

```php
<?php
/**
 * single.php - Template cho bài viết đơn lẻ
 *
 * Tương tự route: Route::get('/post/{slug}', [PostController::class, 'show'])
 * nhưng WordPress tự động handle
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main single-post">
    <div class="container">
        <div class="content-area">

            <div class="main-content">
                <?php
                while ( have_posts() ) :
                    the_post();
                ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                    <!-- Featured Image -->
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail( 'developer-featured' ); ?>
                            <?php
                            // Hiển thị caption của featured image nếu có
                            $caption = get_the_post_thumbnail_caption();
                            if ( $caption ) :
                            ?>
                                <figcaption class="thumbnail-caption"><?php echo esc_html( $caption ); ?></figcaption>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Entry Header -->
                    <header class="entry-header">
                        <!-- Categories -->
                        <div class="entry-categories">
                            <?php
                            $categories = get_the_category();
                            foreach ( $categories as $cat ) :
                            ?>
                                <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="category-badge">
                                    <?php echo esc_html( $cat->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <h1 class="entry-title"><?php the_title(); ?></h1>

                        <div class="entry-meta">
                            <!-- Avatar tác giả -->
                            <span class="author-avatar">
                                <?php echo get_avatar( get_the_author_meta( 'ID' ), 40 ); ?>
                            </span>

                            <!-- Tên tác giả -->
                            <span class="author-name">
                                <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
                                    <?php the_author(); ?>
                                </a>
                            </span>

                            <!-- Ngày đăng -->
                            <span class="posted-date">
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                    <?php echo esc_html( get_the_date() ); ?>
                                </time>
                            </span>

                            <!-- Thời gian đọc -->
                            <span class="reading-time">
                                <?php
                                // Tính thời gian đọc (200 từ/phút)
                                $content = get_the_content();
                                $word_count = str_word_count( strip_tags( $content ) );
                                $reading_time = ceil( $word_count / 200 );
                                printf(
                                    esc_html__( '%d phút đọc', 'developer-theme' ),
                                    $reading_time
                                );
                                ?>
                            </span>
                        </div>
                    </header>

                    <!-- Entry Content -->
                    <div class="entry-content">
                        <?php
                        the_content();

                        // Hiển thị phân trang trong bài viết (<!--nextpage-->)
                        wp_link_pages( array(
                            'before' => '<div class="page-links">' . esc_html__( 'Trang:', 'developer-theme' ),
                            'after'  => '</div>',
                        ) );
                        ?>
                    </div>

                    <!-- Entry Footer -->
                    <footer class="entry-footer">
                        <!-- Tags -->
                        <?php
                        $tags = get_the_tag_list( '', '', '' );
                        if ( $tags ) :
                        ?>
                            <div class="entry-tags">
                                <strong><?php esc_html_e( 'Thẻ:', 'developer-theme' ); ?></strong>
                                <?php echo $tags; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Share buttons -->
                        <div class="share-buttons">
                            <strong><?php esc_html_e( 'Chia sẻ:', 'developer-theme' ); ?></strong>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( get_permalink() ); ?>"
                               target="_blank" rel="noopener noreferrer">
                                Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>"
                               target="_blank" rel="noopener noreferrer">
                                Twitter
                            </a>
                        </div>
                    </footer>

                </article>

                <!-- Bài viết liên quan -->
                <section class="related-posts">
                    <h3><?php esc_html_e( 'Bài Viết Liên Quan', 'developer-theme' ); ?></h3>
                    <?php
                    $categories = get_the_category();
                    if ( $categories ) :
                        $cat_ids = array();
                        foreach ( $categories as $cat ) {
                            $cat_ids[] = $cat->term_id;
                        }

                        $related = new WP_Query( array(
                            'category__in'   => $cat_ids,
                            'post__not_in'   => array( get_the_ID() ), // Loại trừ bài hiện tại
                            'posts_per_page' => 3,
                            'orderby'        => 'rand',
                        ) );

                        if ( $related->have_posts() ) :
                    ?>
                        <div class="related-grid">
                            <?php
                            while ( $related->have_posts() ) :
                                $related->the_post();
                            ?>
                                <div class="related-item">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'developer-thumbnail' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                    <span class="date"><?php echo get_the_date(); ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php
                        endif;
                        wp_reset_postdata();
                    endif;
                    ?>
                </section>

                <!-- Điều hướng bài trước/sau -->
                <nav class="post-navigation">
                    <div class="nav-previous">
                        <?php
                        previous_post_link(
                            '<span class="nav-label">' . esc_html__( 'Bài trước', 'developer-theme' ) . '</span> %link'
                        );
                        ?>
                    </div>
                    <div class="nav-next">
                        <?php
                        next_post_link(
                            '<span class="nav-label">' . esc_html__( 'Bài sau', 'developer-theme' ) . '</span> %link'
                        );
                        ?>
                    </div>
                </nav>

                <!-- Bình luận -->
                <?php
                // Nếu bình luận mở hoặc có bình luận, hiển thị form
                if ( comments_open() || get_comments_number() ) :
                    comments_template(); // Load comments.php
                endif;
                ?>

                <?php endwhile; ?>
            </div><!-- .main-content -->

            <?php get_sidebar(); ?>

        </div><!-- .content-area -->
    </div><!-- .container -->
</main>

<?php get_footer(); ?>
```

### page.php - Trang tĩnh

```php
<?php
/**
 * page.php - Template cho trang tĩnh (static page)
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main page-template">
    <div class="container">
        <?php
        while ( have_posts() ) :
            the_post();
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail( 'full' ); ?>
                </div>
            <?php endif; ?>

            <div class="entry-content">
                <?php
                the_content();

                wp_link_pages( array(
                    'before' => '<div class="page-links">' . esc_html__( 'Trang:', 'developer-theme' ),
                    'after'  => '</div>',
                ) );
                ?>
            </div>

            <?php
            // Hiển thị bình luận trên page (nếu bật)
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
            ?>

        </article>

        <?php endwhile; ?>
    </div><!-- .container -->
</main>

<?php get_footer(); ?>
```

### archive.php - Trang archive

```php
<?php
/**
 * archive.php - Template cho trang archive (danh sách bài viết)
 * Áp dụng cho: category, tag, author, date archives
 * (trừ khi có template cụ thể hơn như category.php, tag.php...)
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main archive-page">
    <div class="container">
        <div class="content-area">

            <div class="main-content">
                <?php if ( have_posts() ) : ?>

                    <header class="page-header">
                        <?php
                        // the_archive_title() tự động tạo tiêu đề phù hợp:
                        // Category: Tin tức | Tag: WordPress | Author: Nguyễn Văn A | Tháng 1, 2024
                        the_archive_title( '<h1 class="page-title">', '</h1>' );

                        // Mô tả của category/tag (nếu có)
                        the_archive_description( '<div class="archive-description">', '</div>' );
                        ?>
                    </header>

                    <div class="posts-list">
                        <?php
                        while ( have_posts() ) :
                            the_post();
                            get_template_part( 'template-parts/content', 'archive' );
                        endwhile;
                        ?>
                    </div>

                    <?php
                    the_posts_pagination( array(
                        'mid_size'  => 2,
                        'prev_text' => '&laquo; ' . __( 'Trước', 'developer-theme' ),
                        'next_text' => __( 'Sau', 'developer-theme' ) . ' &raquo;',
                    ) );
                    ?>

                <?php else : ?>
                    <?php get_template_part( 'template-parts/content', 'none' ); ?>
                <?php endif; ?>
            </div>

            <?php get_sidebar(); ?>

        </div>
    </div>
</main>

<?php get_footer(); ?>
```

### category.php - Trang danh mục

```php
<?php
/**
 * category.php - Template riêng cho trang danh mục
 *
 * File này được ưu tiên hơn archive.php khi xem trang category
 * Bạn cũng có thể tạo: category-{slug}.php cho danh mục cụ thể
 * Ví dụ: category-tin-tuc.php chỉ áp dụng cho danh mục "tin-tuc"
 *
 * @package Developer_Theme
 */

get_header();

// Lấy thông tin category hiện tại
$current_cat = get_queried_object();
?>

<main id="primary" class="site-main category-page">
    <div class="container">

        <!-- Category Header với hình nền và mô tả -->
        <header class="category-header" style="
            <?php
            // Nếu category có custom field hình nền
            $cat_image = get_term_meta( $current_cat->term_id, 'category_image', true );
            if ( $cat_image ) :
                echo 'background-image: url(' . esc_url( $cat_image ) . ');';
            endif;
            ?>
        ">
            <h1 class="page-title">
                <?php
                printf(
                    esc_html__( 'Danh mục: %s', 'developer-theme' ),
                    single_cat_title( '', false )
                    // false = trả về string thay vì echo
                );
                ?>
            </h1>

            <?php if ( category_description() ) : ?>
                <div class="category-description">
                    <?php echo category_description(); ?>
                </div>
            <?php endif; ?>

            <p class="post-count">
                <?php
                printf(
                    esc_html( _n( '%s bài viết', '%s bài viết', $current_cat->count, 'developer-theme' ) ),
                    number_format_i18n( $current_cat->count )
                );
                ?>
            </p>

            <!-- Danh mục con (nếu có) -->
            <?php
            $subcategories = get_categories( array(
                'parent' => $current_cat->term_id,
            ) );

            if ( $subcategories ) :
            ?>
                <div class="subcategories">
                    <strong><?php esc_html_e( 'Danh mục con:', 'developer-theme' ); ?></strong>
                    <?php foreach ( $subcategories as $subcat ) : ?>
                        <a href="<?php echo esc_url( get_category_link( $subcat->term_id ) ); ?>">
                            <?php echo esc_html( $subcat->name ); ?> (<?php echo $subcat->count; ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="content-area">
            <div class="main-content">
                <?php if ( have_posts() ) : ?>
                    <div class="posts-grid">
                        <?php
                        while ( have_posts() ) :
                            the_post();
                            get_template_part( 'template-parts/content', 'card' );
                        endwhile;
                        ?>
                    </div>

                    <?php the_posts_pagination(); ?>
                <?php else : ?>
                    <p><?php esc_html_e( 'Chưa có bài viết nào trong danh mục này.', 'developer-theme' ); ?></p>
                <?php endif; ?>
            </div>

            <?php get_sidebar(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

### tag.php - Trang thẻ

```php
<?php
/**
 * tag.php - Template cho trang thẻ (tag)
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main tag-page">
    <div class="container">

        <header class="page-header">
            <h1 class="page-title">
                <?php
                printf(
                    esc_html__( 'Thẻ: %s', 'developer-theme' ),
                    '<span>' . single_tag_title( '', false ) . '</span>'
                );
                ?>
            </h1>

            <?php if ( tag_description() ) : ?>
                <div class="tag-description"><?php echo tag_description(); ?></div>
            <?php endif; ?>
        </header>

        <div class="content-area">
            <div class="main-content">
                <?php if ( have_posts() ) : ?>
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        get_template_part( 'template-parts/content', 'archive' );
                    endwhile;

                    the_posts_pagination();
                    ?>
                <?php else : ?>
                    <?php get_template_part( 'template-parts/content', 'none' ); ?>
                <?php endif; ?>
            </div>
            <?php get_sidebar(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

### author.php - Trang tác giả

```php
<?php
/**
 * author.php - Template cho trang tác giả
 *
 * @package Developer_Theme
 */

get_header();

// Lấy thông tin tác giả
$author_id   = get_queried_object_id();
$author_name = get_the_author_meta( 'display_name', $author_id );
$author_bio  = get_the_author_meta( 'description', $author_id );
$author_url  = get_the_author_meta( 'user_url', $author_id );
?>

<main id="primary" class="site-main author-page">
    <div class="container">

        <!-- Author Profile Card -->
        <header class="author-header">
            <div class="author-avatar">
                <?php echo get_avatar( $author_id, 120 ); ?>
            </div>
            <div class="author-info">
                <h1 class="author-name"><?php echo esc_html( $author_name ); ?></h1>

                <?php if ( $author_bio ) : ?>
                    <div class="author-bio"><?php echo wp_kses_post( $author_bio ); ?></div>
                <?php endif; ?>

                <?php if ( $author_url ) : ?>
                    <a href="<?php echo esc_url( $author_url ); ?>" class="author-website" target="_blank" rel="noopener">
                        <?php echo esc_html( $author_url ); ?>
                    </a>
                <?php endif; ?>

                <p class="author-post-count">
                    <?php
                    printf(
                        esc_html__( 'Đã viết %s bài', 'developer-theme' ),
                        count_user_posts( $author_id )
                    );
                    ?>
                </p>
            </div>
        </header>

        <!-- Bài viết của tác giả -->
        <div class="content-area">
            <div class="main-content">
                <h2><?php printf( esc_html__( 'Bài viết của %s', 'developer-theme' ), esc_html( $author_name ) ); ?></h2>

                <?php if ( have_posts() ) : ?>
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        get_template_part( 'template-parts/content', 'archive' );
                    endwhile;

                    the_posts_pagination();
                    ?>
                <?php else : ?>
                    <p><?php esc_html_e( 'Tác giả chưa có bài viết nào.', 'developer-theme' ); ?></p>
                <?php endif; ?>
            </div>
            <?php get_sidebar(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

### search.php - Trang tìm kiếm

```php
<?php
/**
 * search.php - Template cho trang kết quả tìm kiếm
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main search-results">
    <div class="container">

        <header class="page-header">
            <h1 class="page-title">
                <?php
                printf(
                    esc_html__( 'Kết quả tìm kiếm cho: "%s"', 'developer-theme' ),
                    '<span>' . get_search_query() . '</span>'
                    // get_search_query() trả về từ khóa đã escape
                );
                ?>
            </h1>

            <p class="results-count">
                <?php
                global $wp_query;
                printf(
                    esc_html( _n(
                        'Tìm thấy %s kết quả',
                        'Tìm thấy %s kết quả',
                        $wp_query->found_posts,
                        'developer-theme'
                    ) ),
                    number_format_i18n( $wp_query->found_posts )
                );
                ?>
            </p>

            <!-- Form tìm kiếm lại -->
            <?php get_search_form(); ?>
        </header>

        <div class="content-area">
            <div class="main-content">
                <?php if ( have_posts() ) : ?>
                    <?php
                    while ( have_posts() ) :
                        the_post();
                    ?>
                        <article <?php post_class( 'search-result-item' ); ?>>
                            <!-- Loại nội dung -->
                            <span class="result-type">
                                <?php
                                $post_type_obj = get_post_type_object( get_post_type() );
                                echo esc_html( $post_type_obj->labels->singular_name );
                                ?>
                            </span>

                            <h2 class="entry-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <div class="entry-summary">
                                <?php the_excerpt(); ?>
                            </div>

                            <div class="entry-meta">
                                <span class="date"><?php echo get_the_date(); ?></span>
                                <a href="<?php the_permalink(); ?>" class="read-more">
                                    <?php esc_html_e( 'Xem chi tiết', 'developer-theme' ); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>

                    <?php the_posts_pagination(); ?>

                <?php else : ?>
                    <div class="no-results">
                        <h2><?php esc_html_e( 'Không tìm thấy kết quả nào', 'developer-theme' ); ?></h2>
                        <p><?php esc_html_e( 'Thử lại với từ khóa khác hoặc duyệt qua các danh mục.', 'developer-theme' ); ?></p>

                        <!-- Gợi ý -->
                        <div class="search-suggestions">
                            <h3><?php esc_html_e( 'Gợi ý:', 'developer-theme' ); ?></h3>
                            <ul>
                                <li><?php esc_html_e( 'Kiểm tra chính tả', 'developer-theme' ); ?></li>
                                <li><?php esc_html_e( 'Dùng từ khóa ngắn hơn', 'developer-theme' ); ?></li>
                                <li><?php esc_html_e( 'Thử dùng từ đồng nghĩa', 'developer-theme' ); ?></li>
                            </ul>
                        </div>

                        <!-- Danh mục -->
                        <div class="categories-list">
                            <h3><?php esc_html_e( 'Danh mục:', 'developer-theme' ); ?></h3>
                            <ul>
                                <?php
                                wp_list_categories( array(
                                    'show_count' => true,
                                    'title_li'   => '',
                                ) );
                                ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php get_sidebar(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

### 404.php - Trang lỗi 404

```php
<?php
/**
 * 404.php - Template cho trang lỗi 404 (không tìm thấy)
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main error-404">
    <div class="container">

        <div class="error-content" style="text-align: center; padding: 4rem 0;">
            <h1 class="error-code" style="font-size: 8rem; color: #ddd; margin-bottom: 0;">404</h1>
            <h2><?php esc_html_e( 'Trang không tồn tại', 'developer-theme' ); ?></h2>
            <p>
                <?php esc_html_e(
                    'Xin lỗi, trang bạn đang tìm không tồn tại hoặc đã bị di chuyển.',
                    'developer-theme'
                ); ?>
            </p>

            <!-- Form tìm kiếm -->
            <div class="error-search" style="max-width: 500px; margin: 2rem auto;">
                <?php get_search_form(); ?>
            </div>

            <!-- Link hữu ích -->
            <div class="error-links">
                <h3><?php esc_html_e( 'Có thể bạn muốn xem:', 'developer-theme' ); ?></h3>

                <p>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <?php esc_html_e( 'Trang Chủ', 'developer-theme' ); ?>
                    </a>
                </p>

                <!-- Bài viết mới nhất -->
                <h4><?php esc_html_e( 'Bài viết mới nhất:', 'developer-theme' ); ?></h4>
                <ul>
                    <?php
                    $recent = new WP_Query( array(
                        'posts_per_page' => 5,
                        'post_status'    => 'publish',
                    ) );

                    while ( $recent->have_posts() ) :
                        $recent->the_post();
                    ?>
                        <li>
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            <span class="date"> - <?php echo get_the_date(); ?></span>
                        </li>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </ul>

                <!-- Danh mục -->
                <h4><?php esc_html_e( 'Danh mục:', 'developer-theme' ); ?></h4>
                <ul>
                    <?php
                    wp_list_categories( array(
                        'show_count' => true,
                        'title_li'   => '',
                        'number'     => 10,
                    ) );
                    ?>
                </ul>
            </div>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

---

## 4. Template Parts

### get_template_part() - Include component

```php
/**
 * get_template_part( $slug, $name, $args )
 *
 * Tương tự @include('components.card') trong Blade
 * Nhưng không có truyền biến tương tự Blade
 *
 * @param string $slug - Đường dẫn cơ sở
 * @param string $name - Phần mở rộng (optional)
 * @param array  $args - Dữ liệu truyền vào (WP 5.5+)
 */

// === Ví dụ 1: Cơ bản ===
get_template_part( 'template-parts/content' );
// Tim file: template-parts/content.php

// === Ví dụ 2: Với name (phụ) ===
get_template_part( 'template-parts/content', 'single' );
// Tim file: template-parts/content-single.php
// Nếu không có, fallback: template-parts/content.php

// === Ví dụ 3: Dựa trên post type ===
get_template_part( 'template-parts/content', get_post_type() );
// Nếu post type là 'post' -> tìm: template-parts/content-post.php
// Nếu post type là 'product' -> tìm: template-parts/content-product.php

// === Ví dụ 4: Dựa trên post format ===
get_template_part( 'template-parts/content', get_post_format() );
// Nếu format là 'video' -> tìm: template-parts/content-video.php
// Nếu format là 'gallery' -> tìm: template-parts/content-gallery.php

// === Ví dụ 5: Truyền dữ liệu (WP 5.5+) ===
get_template_part( 'template-parts/content', 'card', array(
    'show_thumbnail' => true,
    'show_excerpt'   => true,
    'columns'        => 3,
    'custom_class'   => 'featured-card',
) );
// Trong template-parts/content-card.php, truy cap:
// $args['show_thumbnail'], $args['show_excerpt'], $args['columns']
```

### Ví dụ template-parts/content-card.php:

```php
<?php
/**
 * Template part: Content Card
 * Hiển thị bài viết dạng thẻ (card) cho grid layout
 *
 * Tương tự @component('card') trong Laravel Blade
 *
 * @param array $args {
 *     @type bool   $show_thumbnail  Có hiển thị ảnh không
 *     @type bool   $show_excerpt    Có hiển thị excerpt không
 *     @type string $custom_class    Class CSS thêm
 * }
 *
 * @package Developer_Theme
 */

// Lấy args với giá trị mặc định
$show_thumbnail = isset( $args['show_thumbnail'] ) ? $args['show_thumbnail'] : true;
$show_excerpt   = isset( $args['show_excerpt'] ) ? $args['show_excerpt'] : true;
$custom_class   = isset( $args['custom_class'] ) ? $args['custom_class'] : '';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'card-item ' . esc_attr( $custom_class ) ); ?>>

    <?php if ( $show_thumbnail && has_post_thumbnail() ) : ?>
        <div class="card-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'developer-thumbnail' ); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="card-body">
        <!-- Category -->
        <?php
        $category = get_the_category();
        if ( $category ) :
        ?>
            <span class="card-category">
                <a href="<?php echo esc_url( get_category_link( $category[0]->term_id ) ); ?>">
                    <?php echo esc_html( $category[0]->name ); ?>
                </a>
            </span>
        <?php endif; ?>

        <h3 class="card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if ( $show_excerpt ) : ?>
            <div class="card-excerpt">
                <?php echo wp_trim_words( get_the_excerpt(), 15, '...' ); ?>
            </div>
        <?php endif; ?>

        <div class="card-meta">
            <span class="card-date"><?php echo get_the_date(); ?></span>
        </div>
    </div>

</article>
```

### Ví dụ template-parts/content-none.php:

```php
<?php
/**
 * Template part: No Content
 * Hiển thị khi không có bài viết nào
 *
 * @package Developer_Theme
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title">
            <?php esc_html_e( 'Không tìm thấy nội dung', 'developer-theme' ); ?>
        </h1>
    </header>

    <div class="page-content">
        <?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
            <!-- Admin chưa tạo bài viết nào -->
            <p>
                <?php
                printf(
                    wp_kses(
                        __( 'Sẵn sàng đăng bài viết đầu tiên? <a href="%1$s">Bắt đầu ở đây</a>.', 'developer-theme' ),
                        array( 'a' => array( 'href' => array() ) )
                    ),
                    esc_url( admin_url( 'post-new.php' ) )
                );
                ?>
            </p>

        <?php elseif ( is_search() ) : ?>
            <!-- Tìm kiếm không có kết quả -->
            <p><?php esc_html_e( 'Không tìm thấy kết quả. Thử tìm với từ khóa khác.', 'developer-theme' ); ?></p>
            <?php get_search_form(); ?>

        <?php else : ?>
            <!-- Trường hợp chung -->
            <p><?php esc_html_e( 'Không thể tìm thấy nội dung bạn yêu cầu. Thử tìm kiếm.', 'developer-theme' ); ?></p>
            <?php get_search_form(); ?>
        <?php endif; ?>
    </div>
</section>
```

---

## 5. Header, Footer, Sidebar

### get_header() chi tiết:

```php
// === Cơ bản ===
get_header();
// Load file: header.php

// === Với tên cụ thể ===
get_header( 'landing' );
// Load file: header-landing.php
// Nếu không có, fallback: header.php

// === Ví dụ: Landing page dùng header khác ===
// Trong page-templates/template-landing.php:
get_header( 'landing' ); // Load header-landing.php (không có sidebar, menu đơn giản)

// header-landing.php - Header đơn giản cho landing page:
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'landing-page' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site landing">
    <header class="landing-header">
        <div class="container">
            <?php the_custom_logo(); ?>
        </div>
    </header>
<?php
```

### get_footer() chi tiết:

```php
// === Cơ bản ===
get_footer();
// Load file: footer.php

// === Với tên cụ thể ===
get_footer( 'minimal' );
// Load file: footer-minimal.php
// Nếu không có, fallback: footer.php
```

### get_sidebar() chi tiết:

```php
// === Cơ bản ===
get_sidebar();
// Load file: sidebar.php

// === Sidebar riêng cho trang shop ===
get_sidebar( 'shop' );
// Load file: sidebar-shop.php

// === Ví dụ sidebar-shop.php ===
if ( ! is_active_sidebar( 'sidebar-shop' ) ) {
    return;
}
?>
<aside class="widget-area sidebar-shop">
    <?php dynamic_sidebar( 'sidebar-shop' ); ?>
</aside>
<?php
```

---

## 6. Conditional Tags

Conditional Tags là các hàm trả về `true/false` để kiểm tra đang ở trang nào:

```php
<?php
// === KIỂM TRA LOẠI TRANG ===

is_home()          // Trang blog (hiển thị danh sách bài viết mới nhất)
is_front_page()    // Trang chủ (front page trong Settings > Reading)
is_single()        // Bài viết đơn (post, custom post type)
is_page()          // Trang tĩnh (page)
is_singular()      // Cả post và page (single + page + attachment)
is_archive()       // Trang archive (category, tag, date, author)
is_category()      // Trang danh mục
is_tag()           // Trang thẻ
is_author()        // Trang tác giả
is_date()          // Trang ngày tháng
is_year()          // Trang năm
is_month()         // Trang tháng
is_day()           // Trang ngày
is_search()        // Trang kết quả tìm kiếm
is_404()           // Trang lỗi 404
is_attachment()    // Trang file đính kèm
is_tax()           // Trang custom taxonomy

// === KIỂM TRA CỤ THỂ ===

is_single( 'hello-world' )        // Bài viết có slug 'hello-world'
is_single( 42 )                    // Bài viết có ID 42
is_single( array( 42, 'hello' ) ) // Bài viết có ID 42 HOẶC slug 'hello'

is_page( 'about' )                // Trang có slug 'about'
is_page( 10 )                     // Trang có ID 10
is_page( array( 'about', 'contact', 10 ) ) // 1 trong 3 trang này

is_category( 'tin-tuc' )          // Danh mục có slug 'tin-tuc'
is_category( 5 )                  // Danh mục có ID 5

is_tag( 'wordpress' )             // Thẻ có slug 'wordpress'

is_author( 'admin' )              // Trang tác giả có nicename 'admin'

is_post_type_archive( 'product' ) // Archive của post type 'product'

is_tax( 'brand' )                 // Taxonomy 'brand'
is_tax( 'brand', 'apple' )        // Taxonomy 'brand', term 'apple'

// === KIỂM TRA THUỘC TÍNH ===

is_sticky()                       // Bài viết ghim
has_post_thumbnail()              // Có featured image
has_excerpt()                     // Có excerpt tự viết
has_nav_menu( 'primary' )         // Có menu ở vị trí 'primary'
is_active_sidebar( 'sidebar-1' )  // Sidebar có widget

in_category( 'tin-tuc' )          // Bài viết hiện tại thuộc danh mục 'tin-tuc'
has_category()                    // Bài viết có ít nhất 1 category
has_tag()                         // Bài viết có ít nhất 1 tag
has_tag( 'wordpress' )            // Bài viết có tag 'wordpress'

// === KIỂM TRA NGƯỜI DÙNG ===

is_user_logged_in()               // Đang đăng nhập
current_user_can( 'edit_posts' )  // Có quyền chỉnh sửa bài viết
is_admin()                        // Đang ở admin area
is_customize_preview()            // Đang trong Customizer preview

// === KIỂM TRA KHÁC ===

is_paged()                        // Trang 2, 3, ... (có phân trang)
is_main_query()                   // Có phải main query không
is_child_theme()                  // Đang dùng child theme
is_rtl()                          // Ngôn ngữ viết từ phải sang trái
is_multisite()                    // WordPress Multisite

// === SỬ DỤNG TRONG TEMPLATE ===

// Ví dụ 1: Hiển thị khác nhau theo trang
if ( is_home() ) {
    echo '<h1>Blog</h1>';
} elseif ( is_single() ) {
    echo '<h1>' . get_the_title() . '</h1>';
} elseif ( is_page() ) {
    echo '<h1>' . get_the_title() . '</h1>';
} elseif ( is_category() ) {
    echo '<h1>Danh mục: ' . single_cat_title( '', false ) . '</h1>';
} elseif ( is_search() ) {
    echo '<h1>Tìm kiếm: ' . get_search_query() . '</h1>';
} elseif ( is_404() ) {
    echo '<h1>404 - Không tìm thấy</h1>';
}

// Ví dụ 2: Body class khác nhau
$body_class = 'site';
if ( is_front_page() ) {
    $body_class .= ' front-page';
}
if ( ! is_active_sidebar( 'sidebar-main' ) || is_page_template( 'template-full-width.php' ) ) {
    $body_class .= ' no-sidebar';
}

// Ví dụ 3: Sidebar có điều kiện
if ( is_single() || is_page() ) {
    get_sidebar();
    // Chỉ hiện sidebar trên trang single và page
}

// Ví dụ 4: Trong functions.php - Conditional enqueue
function developer_conditional_scripts() {
    // Chỉ load slider JS trên trang chủ
    if ( is_front_page() ) {
        wp_enqueue_script( 'slider', get_template_directory_uri() . '/assets/js/slider.js' );
    }

    // Chỉ load gallery CSS khi bài viết có gallery
    if ( is_singular() && has_shortcode( get_the_content(), 'gallery' ) ) {
        wp_enqueue_style( 'gallery-style', get_template_directory_uri() . '/assets/css/gallery.css' );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_conditional_scripts' );
```

---

## 7. Template cho Custom Post Types

```php
/**
 * Khi bạn tạo Custom Post Type (ví dụ: 'product'),
 * WordPress sẽ tìm template theo thứ tự:
 *
 * Single Product:
 *   single-product-{slug}.php -> single-product.php -> single.php -> singular.php -> index.php
 *
 * Archive Products:
 *   archive-product.php -> archive.php -> index.php
 */

// === Bước 1: Đăng ký Custom Post Type (trong functions.php hoặc plugin) ===
function developer_register_product_cpt() {
    register_post_type( 'product', array(
        'labels' => array(
            'name'               => __( 'Sản Phẩm', 'developer-theme' ),
            'singular_name'      => __( 'Sản Phẩm', 'developer-theme' ),
            'add_new_item'       => __( 'Thêm Sản Phẩm Mới', 'developer-theme' ),
            'edit_item'          => __( 'Sửa Sản Phẩm', 'developer-theme' ),
            'view_item'          => __( 'Xem Sản Phẩm', 'developer-theme' ),
            'all_items'          => __( 'Tất Cả Sản Phẩm', 'developer-theme' ),
            'search_items'       => __( 'Tìm Sản Phẩm', 'developer-theme' ),
            'not_found'          => __( 'Không tìm thấy sản phẩm nào', 'developer-theme' ),
        ),
        'public'       => true,
        'has_archive'  => true,                    // Có trang archive
        'rewrite'      => array( 'slug' => 'san-pham' ), // URL: /san-pham/ten-sp
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'menu_icon'    => 'dashicons-cart',
        'show_in_rest' => true,                    // Hỗ trợ Gutenberg
    ) );
}
add_action( 'init', 'developer_register_product_cpt' );

// === Bước 2: Tạo single-product.php ===
// File: single-product.php
?>
<?php get_header(); ?>

<main id="primary" class="site-main single-product">
    <div class="container">
        <?php
        while ( have_posts() ) :
            the_post();

            // Lay custom fields
            $price     = get_post_meta( get_the_ID(), 'product_price', true );
            $sku       = get_post_meta( get_the_ID(), 'product_sku', true );
            $in_stock  = get_post_meta( get_the_ID(), 'product_in_stock', true );
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="product-layout">

                <!-- Hình ảnh sản phẩm -->
                <div class="product-gallery">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'large' ); ?>
                    <?php endif; ?>
                </div>

                <!-- Thông tin sản phẩm -->
                <div class="product-info">
                    <h1 class="product-title"><?php the_title(); ?></h1>

                    <?php if ( $sku ) : ?>
                        <p class="product-sku">SKU: <?php echo esc_html( $sku ); ?></p>
                    <?php endif; ?>

                    <?php if ( $price ) : ?>
                        <p class="product-price">
                            <?php echo number_format( $price, 0, ',', '.' ); ?> VND
                        </p>
                    <?php endif; ?>

                    <div class="product-status">
                        <?php if ( $in_stock ) : ?>
                            <span class="in-stock"><?php esc_html_e( 'Còn hàng', 'developer-theme' ); ?></span>
                        <?php else : ?>
                            <span class="out-of-stock"><?php esc_html_e( 'Hết hàng', 'developer-theme' ); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-description">
                        <?php the_content(); ?>
                    </div>

                    <!-- Taxonomies (danh mục sản phẩm) -->
                    <?php
                    $product_cats = get_the_terms( get_the_ID(), 'product_category' );
                    if ( $product_cats && ! is_wp_error( $product_cats ) ) :
                    ?>
                        <div class="product-categories">
                            <strong><?php esc_html_e( 'Danh mục:', 'developer-theme' ); ?></strong>
                            <?php
                            foreach ( $product_cats as $cat ) {
                                echo '<a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a> ';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </article>

        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>

<?php
// === Bước 3: Tạo archive-product.php ===
// File: archive-product.php
?>
<?php get_header(); ?>

<main id="primary" class="site-main archive-product">
    <div class="container">

        <header class="page-header">
            <h1 class="page-title"><?php esc_html_e( 'Sản Phẩm', 'developer-theme' ); ?></h1>
            <?php
            // Hiển thị mô tả của post type archive
            $post_type_description = get_the_post_type_description();
            if ( $post_type_description ) :
            ?>
                <p class="archive-description"><?php echo esc_html( $post_type_description ); ?></p>
            <?php endif; ?>
        </header>

        <!-- Filter theo danh mục sản phẩm -->
        <div class="product-filter">
            <?php
            $product_cats = get_terms( array(
                'taxonomy'   => 'product_category',
                'hide_empty' => true,
            ) );

            if ( $product_cats && ! is_wp_error( $product_cats ) ) :
            ?>
                <ul class="filter-list">
                    <li><a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>" class="active">
                        <?php esc_html_e( 'Tất Cả', 'developer-theme' ); ?>
                    </a></li>
                    <?php foreach ( $product_cats as $cat ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
                                <?php echo esc_html( $cat->name ); ?> (<?php echo $cat->count; ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if ( have_posts() ) : ?>
            <div class="products-grid">
                <?php
                while ( have_posts() ) :
                    the_post();
                    $price = get_post_meta( get_the_ID(), 'product_price', true );
                ?>
                    <div class="product-card">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="product-image">
                                <?php the_post_thumbnail( 'developer-square' ); ?>
                            </a>
                        <?php endif; ?>

                        <div class="product-card-body">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <?php if ( $price ) : ?>
                                <p class="price"><?php echo number_format( $price, 0, ',', '.' ); ?> VND</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Chưa có sản phẩm nào.', 'developer-theme' ); ?></p>
        <?php endif; ?>

    </div>
</main>

<?php get_footer();
```

---

## 8. Template cho Custom Taxonomies

```php
/**
 * Custom Taxonomy template hierarchy:
 *
 * taxonomy-{taxonomy}-{term}.php
 *   -> taxonomy-{taxonomy}.php
 *     -> taxonomy.php
 *       -> archive.php
 *         -> index.php
 *
 * Ví dụ: Taxonomy 'brand', term 'apple':
 * taxonomy-brand-apple.php -> taxonomy-brand.php -> taxonomy.php -> archive.php -> index.php
 */

// === Đăng ký Custom Taxonomy (trong functions.php hoặc plugin) ===
function developer_register_brand_taxonomy() {
    register_taxonomy( 'brand', 'product', array(
        'labels' => array(
            'name'          => __( 'Thương Hiệu', 'developer-theme' ),
            'singular_name' => __( 'Thương Hiệu', 'developer-theme' ),
            'search_items'  => __( 'Tìm Thương Hiệu', 'developer-theme' ),
            'all_items'     => __( 'Tất Cả Thương Hiệu', 'developer-theme' ),
            'edit_item'     => __( 'Sửa Thương Hiệu', 'developer-theme' ),
            'add_new_item'  => __( 'Thêm Thương Hiệu Mới', 'developer-theme' ),
        ),
        'public'       => true,
        'hierarchical' => true,       // Như category (có parent/child)
        'rewrite'      => array( 'slug' => 'thuong-hieu' ),
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'developer_register_brand_taxonomy' );

// === File: taxonomy-brand.php ===
get_header();

$current_brand = get_queried_object();
?>

<main id="primary" class="site-main taxonomy-brand">
    <div class="container">

        <header class="brand-header">
            <h1 class="page-title">
                <?php echo esc_html( $current_brand->name ); ?>
            </h1>

            <?php if ( $current_brand->description ) : ?>
                <div class="brand-description">
                    <?php echo wp_kses_post( term_description() ); ?>
                </div>
            <?php endif; ?>

            <!-- Brand logo (custom field cua taxonomy) -->
            <?php
            $brand_logo = get_term_meta( $current_brand->term_id, 'brand_logo', true );
            if ( $brand_logo ) :
            ?>
                <img src="<?php echo esc_url( $brand_logo ); ?>"
                     alt="<?php echo esc_attr( $current_brand->name ); ?>"
                     class="brand-logo">
            <?php endif; ?>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="products-grid">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/content', 'product-card' );
                endwhile;
                ?>
            </div>

            <?php the_posts_pagination(); ?>
        <?php endif; ?>

    </div>
</main>

<?php get_footer();
```

---

## 9. Code ví dụ cho từng template

### Template cho comments.php:

```php
<?php
/**
 * comments.php - Template hiển thị bình luận
 *
 * @package Developer_Theme
 */

// Không hiển thị bình luận nếu trang cần mật khẩu
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>

        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            printf(
                esc_html( _n(
                    '%1$s bình luận cho "%2$s"',
                    '%1$s bình luận cho "%2$s"',
                    $comment_count,
                    'developer-theme'
                ) ),
                number_format_i18n( $comment_count ),
                get_the_title()
            );
            ?>
        </h2>

        <!-- Danh sách bình luận -->
        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 50,
                'max_depth'   => 3,  // Độ sâu reply tối đa
            ) );
            ?>
        </ol>

        <!-- Phân trang bình luận -->
        <?php
        the_comments_navigation( array(
            'prev_text' => __( 'Bình luận cũ hơn', 'developer-theme' ),
            'next_text' => __( 'Bình luận mới hơn', 'developer-theme' ),
        ) );
        ?>

    <?php endif; // have_comments() ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="no-comments">
            <?php esc_html_e( 'Bình luận đã đóng.', 'developer-theme' ); ?>
        </p>
    <?php endif; ?>

    <!-- Form bình luận -->
    <?php
    comment_form( array(
        'title_reply'          => __( 'Để lại bình luận', 'developer-theme' ),
        'title_reply_to'       => __( 'Trả lời %s', 'developer-theme' ),
        'cancel_reply_link'    => __( 'Hủy trả lời', 'developer-theme' ),
        'label_submit'         => __( 'Gửi bình luận', 'developer-theme' ),
        'comment_notes_before' => '<p class="comment-notes">'
            . __( 'Email sẽ không được hiển thị công khai.', 'developer-theme' )
            . '</p>',
    ) );
    ?>

</div><!-- .comments-area -->
```

### Custom Page Template:

```php
<?php
/**
 * Template Name: Full Width
 * Template Post Type: page, post
 *
 * Dòng "Template Name:" là BẮT BUỘC - nó đăng ký template này
 * trong dropdown "Page Attributes > Template" khi edit Page
 *
 * "Template Post Type:" cho phép chọn template này cho nhiều post type
 *
 * Tương tự việc tạo @section khác nhau trong Laravel Blade
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main full-width-template">
    <div class="container-fluid">
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

        <?php
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
        endwhile;
        ?>
    </div>
</main>

<?php get_footer(); ?>
```

---

## 10. So sánh với Laravel Blade

### Routing/Template Selection

```php
// === LARAVEL ===
// routes/web.php
Route::get('/', [HomeController::class, 'index']);           // Trang chủ
Route::get('/blog', [PostController::class, 'index']);       // Danh sách bài
Route::get('/blog/{slug}', [PostController::class, 'show']);  // Bài viết
Route::get('/page/{slug}', [PageController::class, 'show']); // Trang
Route::get('/category/{slug}', [CategoryController::class, 'show']);

// Controller phai return view:
class PostController {
    public function show($slug) {
        $post = Post::where('slug', $slug)->firstOrFail();
        return view('posts.show', compact('post'));
    }
}

// === WORDPRESS ===
// KHÔNG CẦN routes và controller!
// Chỉ cần tạo file template:
// front-page.php   --> Trang chủ
// home.php         --> Trang blog
// single.php       --> Bài viết
// page.php         --> Trang
// category.php     --> Danh mục
// WordPress TỰ ĐỘNG map URL -> Template file
```

### Layout/Template Inheritance

```php
// === LARAVEL BLADE ===
// layouts/app.blade.php
<!DOCTYPE html>
<html>
<head>
    @yield('head')
</head>
<body>
    @include('partials.header')
    <main>
        @yield('content')
        @include('partials.sidebar')
    </main>
    @include('partials.footer')
</body>
</html>

// pages/home.blade.php
@extends('layouts.app')
@section('content')
    <h1>Home</h1>
@endsection

// === WORDPRESS ===
// Không có "extends", dùng get_header() và get_footer() thay thế:
// index.php
<?php get_header(); ?>    <!-- = @include('partials.header') + bắt đầu layout -->
<main>
    <h1>Home</h1>
    <?php get_sidebar(); ?> <!-- = @include('partials.sidebar') -->
</main>
<?php get_footer(); ?>    <!-- = @include('partials.footer') + kết thúc layout -->
```

### Components/Partials

```php
// === LARAVEL BLADE ===
// components/post-card.blade.php
<div class="card">
    <h3>{{ $post->title }}</h3>
    <p>{{ $post->excerpt }}</p>
</div>

// Sử dụng:
@foreach($posts as $post)
    <x-post-card :post="$post" />
@endforeach

// === WORDPRESS ===
// template-parts/content-card.php
<div class="card">
    <h3><?php the_title(); ?></h3>
    <p><?php the_excerpt(); ?></p>
</div>

// Sử dụng:
<?php
while ( have_posts() ) :
    the_post();
    get_template_part( 'template-parts/content', 'card' );
endwhile;
?>

// Truyền data (WP 5.5+):
get_template_part( 'template-parts/content', 'card', array(
    'show_image' => true,
) );
// Trong template: $args['show_image']
```

### Conditionals

```php
// === LARAVEL BLADE ===
@if(Route::is('home'))
    <h1>Home</h1>
@elseif(Route::is('posts.*'))
    <h1>Blog</h1>
@endif

@auth
    <p>Xin chao, {{ auth()->user()->name }}</p>
@endauth

// === WORDPRESS ===
<?php if ( is_home() ) : ?>
    <h1>Home</h1>
<?php elseif ( is_single() ) : ?>
    <h1>Blog</h1>
<?php endif; ?>

<?php if ( is_user_logged_in() ) : ?>
    <p>Xin chao, <?php echo esc_html( wp_get_current_user()->display_name ); ?></p>
<?php endif; ?>
```

---

## 11. Best Practices

### 1. Luôn có index.php

```php
// index.php là BẮT BUỘC và là fallback cuối cùng
// Kể cả khi bạn có single.php, page.php, archive.php... vẫn phải có index.php
```

### 2. Sử dụng get_template_part() thay vì include/require

```php
// SAI
include( 'template-parts/content.php' );
require( TEMPLATEPATH . '/template-parts/content.php' );

// DUNG
get_template_part( 'template-parts/content' );
// get_template_part() an toàn hơn vì:
// - Tự động xử lý đường dẫn
// - Hỗ trợ child theme override
// - Không bị lỗi fatal nếu file không tồn tại
```

### 3. Dùng template phù hợp, không lạm dụng index.php

```php
// SAI: Dùng conditional trong index.php cho mọi thứ
// index.php
if ( is_single() ) {
    // code cho single...
} elseif ( is_page() ) {
    // code cho page...
} elseif ( is_category() ) {
    // code cho category...
}

// ĐÚNG: Tạo file template riêng
// single.php - cho bài viết
// page.php - cho trang
// category.php - cho danh mục
```

### 4. wp_reset_postdata() sau WP_Query

```php
// Khi dùng custom WP_Query, LUÔN reset lại sau khi xong
$custom_query = new WP_Query( array( 'post_type' => 'product' ) );
while ( $custom_query->have_posts() ) :
    $custom_query->the_post();
    // ...
endwhile;
wp_reset_postdata(); // BẮT BUỘC! Nếu không, các hàm như the_title() sẽ bị sai
```

### 5. Kiểm tra template đang dùng

```php
// Trong development, thêm code này để biết WordPress đang dùng template nào:
function developer_show_template() {
    if ( current_user_can( 'manage_options' ) ) {
        global $template;
        echo '<!-- Template: ' . basename( $template ) . ' -->';
    }
}
add_action( 'wp_head', 'developer_show_template' );

// Hoặc cài plugin "Query Monitor" để xem chi tiết
```

### 6. Đặt tên file đúng quy ước

```
DUNG:                          SAI:
single.php                     post.php
page.php                       static-page.php
archive.php                    list.php
template-parts/content.php     parts/content.php (vẫn chạy nhưng không chuẩn)
page-templates/full-width.php  templates/full-width.php
```

### 7. Sử dụng body_class() và post_class()

```php
// Luôn dùng để CSS targeting dễ dàng hơn
<body <?php body_class(); ?>>
// Tạo ra: <body class="home blog logged-in admin-bar">

<article <?php post_class(); ?>>
// Tạo ra: <article class="post type-post status-publish format-standard has-post-thumbnail hentry category-news">

// Thêm class tùy chỉnh:
<body <?php body_class( 'custom-layout dark-mode' ); ?>>
<article <?php post_class( 'card featured' ); ?>>
```

---

**Tiếp theo:** [03 - The Loop và WP_Query](./03-the-loop-va-wp-query.md) - Hiểu cách lấy và hiển thị dữ liệu
