# Template Hierarchy trong WordPress

## Muc Luc

1. [Template Hierarchy la gi](#1-template-hierarchy-la-gi)
2. [So do Template Hierarchy day du](#2-so-do-template-hierarchy)
3. [Cac template files chi tiet](#3-cac-template-files)
4. [Template Parts](#4-template-parts)
5. [Header, Footer, Sidebar](#5-header-footer-sidebar)
6. [Conditional Tags](#6-conditional-tags)
7. [Template cho Custom Post Types](#7-template-cho-custom-post-types)
8. [Template cho Custom Taxonomies](#8-template-cho-custom-taxonomies)
9. [Code vi du cho tung template](#9-code-vi-du)
10. [So sanh voi Laravel Blade](#10-so-sanh-voi-laravel)
11. [Best Practices](#11-best-practices)

---

## 1. Template Hierarchy la gi

Template Hierarchy (Thu bac Template) la **he thong tu dong chon template file** cua WordPress. Khi nguoi dung truy cap mot URL, WordPress se xac dinh loai noi dung va tim template file phu hop nhat theo thu tu uu tien.

### Cach hoat dong:

```
Nguoi dung truy cap URL
        |
        v
WordPress xac dinh loai noi dung (post, page, category, tag...)
        |
        v
Tim template file theo thu tu uu tien (tu cu the nhat den chung nhat)
        |
        v
Dung template file dau tien tim thay
        |
        v
Neu khong tim thay template nao -> dung index.php (fallback cuoi cung)
```

### Vi du cu the:

Khi truy cap `example.com/category/tin-tuc/`, WordPress tim theo thu tu:

```
1. category-tin-tuc.php        (theo slug cua category)
2. category-5.php              (theo ID cua category)
3. category.php                (template chung cho category)
4. archive.php                 (template chung cho archive)
5. index.php                   (fallback cuoi cung)
```

Tim thay file nao truoc thi dung file do.

### So sanh voi Laravel:

```php
// LARAVEL - Ban tu dinh nghia route va controller
Route::get('/category/{slug}', [CategoryController::class, 'show']);
// Ban phai viet code de mapping URL -> View

// WORDPRESS - Tu dong mapping URL -> Template file
// Ban chi can tao file category.php la xong!
// Khong can viet route, khong can controller
```

---

## 2. So do Template Hierarchy

### So do tong quat (ASCII Art):

```
                                    +------------------+
                                    |    index.php     |
                                    | (Fallback cuoi)  |
                                    +--------+---------+
                                             |
                    +------------------------+------------------------+
                    |                        |                        |
            +-------+-------+       +-------+-------+       +-------+-------+
            | Singular      |       | Archive       |       | Dac biet      |
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

### So do chi tiet theo tung loai trang:

```
=== TRANG CHU (Front Page) ===
front-page.php --> home.php --> index.php

Luu y: front-page.php luon duoc uu tien nhat khi Settings > Reading
       co cau hinh "A static page"

=== TRANG BLOG (Blog Posts Index) ===
home.php --> index.php

=== BAI VIET DON (Single Post) ===
single-{post-type}-{slug}.php     (WP 4.4+)
  --> single-{post-type}.php
    --> single.php
      --> singular.php
        --> index.php

Vi du: Bai viet "Hello World" (post type: post)
single-post-hello-world.php
  --> single-post.php
    --> single.php
      --> singular.php
        --> index.php

Vi du: Custom post type "product", bai "iphone"
single-product-iphone.php
  --> single-product.php
    --> single.php
      --> singular.php
        --> index.php

=== TRANG TINH (Page) ===
{custom-template}.php             (template duoc chon trong editor)
  --> page-{slug}.php
    --> page-{id}.php
      --> page.php
        --> singular.php
          --> index.php

Vi du: Trang "About Us" (ID: 10)
page-about-us.php
  --> page-10.php
    --> page.php
      --> singular.php
        --> index.php

=== DANH MUC (Category) ===
category-{slug}.php
  --> category-{id}.php
    --> category.php
      --> archive.php
        --> index.php

=== THE (Tag) ===
tag-{slug}.php
  --> tag-{id}.php
    --> tag.php
      --> archive.php
        --> index.php

=== TAC GIA (Author) ===
author-{nicename}.php
  --> author-{id}.php
    --> author.php
      --> archive.php
        --> index.php

=== NGAY THANG (Date) ===
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

=== TIM KIEM (Search) ===
search.php
  --> index.php

=== LOI 404 ===
404.php
  --> index.php

=== FILE DINH KEM (Attachment) ===
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

## 3. Cac template files chi tiet

### index.php - Template mac dinh (bat buoc)

```php
<?php
/**
 * index.php - Template fallback cuoi cung
 * Moi theme PHAI co file nay
 * Khi khong tim thay template cu the hon, WordPress dung file nay
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main">

    <?php if ( have_posts() ) : ?>

        <!-- Hien thi tieu de trang archive neu can -->
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
             * Load template part dua tren post format
             * Se tim: template-parts/content-{format}.php
             * Neu khong co, dung: template-parts/content.php
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

### front-page.php - Trang chu

```php
<?php
/**
 * front-page.php - Template cho trang chu
 * Chi hoat dong khi: Settings > Reading > "A static page" > Homepage
 *
 * Day la template co uu tien CAO NHAT cho trang chu
 * Ke ca khi ban chon static page, file nay van duoc uu tien hon page-{slug}.php
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main front-page">

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1><?php echo esc_html( get_theme_mod( 'hero_title', 'Chao mung den voi Website' ) ); ?></h1>
            <p><?php echo esc_html( get_theme_mod( 'hero_subtitle', 'Mo ta ngan ve website cua ban' ) ); ?></p>
            <a href="<?php echo esc_url( get_theme_mod( 'hero_button_url', '#' ) ); ?>" class="btn-primary">
                <?php echo esc_html( get_theme_mod( 'hero_button_text', 'Tim hieu them' ) ); ?>
            </a>
        </div>
    </section>

    <!-- Bai viet moi nhat -->
    <section class="latest-posts">
        <div class="container">
            <h2><?php esc_html_e( 'Bai Viet Moi Nhat', 'developer-theme' ); ?></h2>

            <?php
            // Custom query de lay 6 bai viet moi nhat
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
            wp_reset_postdata(); // QUAN TRONG: Reset lai query goc
            ?>

        </div>
    </section>

    <!-- Noi dung cua Page (neu co) -->
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
 * home.php - Template cho trang danh sach bai viet (blog)
 *
 * Hoat dong khi:
 * - Settings > Reading > "Your latest posts" (trang chu la blog)
 * - Settings > Reading > "A static page" > Posts page: chon 1 trang
 *
 * Khac voi front-page.php:
 * - front-page.php: trang chu (homepage)
 * - home.php: trang blog (danh sach bai viet)
 * Neu "homepage displays latest posts" -> ca 2 deu la 1 trang
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
                        // Neu co trang blog rieng, hien thi ten trang do
                        if ( is_home() && ! is_front_page() ) {
                            single_post_title();
                        } else {
                            esc_html_e( 'Blog', 'developer-theme' );
                        }
                        ?>
                    </h1>
                </header>

                <?php if ( have_posts() ) : ?>

                    <!-- Bai viet dau tien (featured) -->
                    <?php
                    the_post(); // Lay bai dau tien
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

                    <!-- Cac bai viet con lai -->
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

### single.php - Bai viet don

```php
<?php
/**
 * single.php - Template cho bai viet don le
 *
 * Tuong tu route: Route::get('/post/{slug}', [PostController::class, 'show'])
 * nhung WordPress tu dong handle
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
                            // Hien thi caption cua featured image neu co
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
                            <!-- Avatar tac gia -->
                            <span class="author-avatar">
                                <?php echo get_avatar( get_the_author_meta( 'ID' ), 40 ); ?>
                            </span>

                            <!-- Ten tac gia -->
                            <span class="author-name">
                                <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
                                    <?php the_author(); ?>
                                </a>
                            </span>

                            <!-- Ngay dang -->
                            <span class="posted-date">
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                    <?php echo esc_html( get_the_date() ); ?>
                                </time>
                            </span>

                            <!-- Thoi gian doc -->
                            <span class="reading-time">
                                <?php
                                // Tinh thoi gian doc (200 tu/phut)
                                $content = get_the_content();
                                $word_count = str_word_count( strip_tags( $content ) );
                                $reading_time = ceil( $word_count / 200 );
                                printf(
                                    esc_html__( '%d phut doc', 'developer-theme' ),
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

                        // Hien thi phan trang trong bai viet (<!--nextpage-->)
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
                                <strong><?php esc_html_e( 'The:', 'developer-theme' ); ?></strong>
                                <?php echo $tags; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Share buttons -->
                        <div class="share-buttons">
                            <strong><?php esc_html_e( 'Chia se:', 'developer-theme' ); ?></strong>
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

                <!-- Bai viet lien quan -->
                <section class="related-posts">
                    <h3><?php esc_html_e( 'Bai Viet Lien Quan', 'developer-theme' ); ?></h3>
                    <?php
                    $categories = get_the_category();
                    if ( $categories ) :
                        $cat_ids = array();
                        foreach ( $categories as $cat ) {
                            $cat_ids[] = $cat->term_id;
                        }

                        $related = new WP_Query( array(
                            'category__in'   => $cat_ids,
                            'post__not_in'   => array( get_the_ID() ), // Loai tru bai hien tai
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

                <!-- Dieu huong bai truoc/sau -->
                <nav class="post-navigation">
                    <div class="nav-previous">
                        <?php
                        previous_post_link(
                            '<span class="nav-label">' . esc_html__( 'Bai truoc', 'developer-theme' ) . '</span> %link'
                        );
                        ?>
                    </div>
                    <div class="nav-next">
                        <?php
                        next_post_link(
                            '<span class="nav-label">' . esc_html__( 'Bai sau', 'developer-theme' ) . '</span> %link'
                        );
                        ?>
                    </div>
                </nav>

                <!-- Binh luan -->
                <?php
                // Neu binh luan mo hoac co binh luan, hien thi form
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

### page.php - Trang tinh

```php
<?php
/**
 * page.php - Template cho trang tinh (static page)
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
            // Hien thi binh luan tren page (neu bat)
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
 * archive.php - Template cho trang archive (danh sach bai viet)
 * Ap dung cho: category, tag, author, date archives
 * (tru khi co template cu the hon nhu category.php, tag.php...)
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
                        // the_archive_title() tu dong tao tieu de phu hop:
                        // Category: Tin tuc | Tag: WordPress | Author: Nguyen Van A | Thang 1, 2024
                        the_archive_title( '<h1 class="page-title">', '</h1>' );

                        // Mo ta cua category/tag (neu co)
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
                        'prev_text' => '&laquo; ' . __( 'Truoc', 'developer-theme' ),
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

### category.php - Trang danh muc

```php
<?php
/**
 * category.php - Template rieng cho trang danh muc
 *
 * File nay duoc uu tien hon archive.php khi xem trang category
 * Ban cung co the tao: category-{slug}.php cho danh muc cu the
 * Vi du: category-tin-tuc.php chi ap dung cho danh muc "tin-tuc"
 *
 * @package Developer_Theme
 */

get_header();

// Lay thong tin category hien tai
$current_cat = get_queried_object();
?>

<main id="primary" class="site-main category-page">
    <div class="container">

        <!-- Category Header voi hinh nen va mo ta -->
        <header class="category-header" style="
            <?php
            // Neu category co custom field hinh nen
            $cat_image = get_term_meta( $current_cat->term_id, 'category_image', true );
            if ( $cat_image ) :
                echo 'background-image: url(' . esc_url( $cat_image ) . ');';
            endif;
            ?>
        ">
            <h1 class="page-title">
                <?php
                printf(
                    esc_html__( 'Danh muc: %s', 'developer-theme' ),
                    single_cat_title( '', false )
                    // false = tra ve string thay vi echo
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
                    esc_html( _n( '%s bai viet', '%s bai viet', $current_cat->count, 'developer-theme' ) ),
                    number_format_i18n( $current_cat->count )
                );
                ?>
            </p>

            <!-- Danh muc con (neu co) -->
            <?php
            $subcategories = get_categories( array(
                'parent' => $current_cat->term_id,
            ) );

            if ( $subcategories ) :
            ?>
                <div class="subcategories">
                    <strong><?php esc_html_e( 'Danh muc con:', 'developer-theme' ); ?></strong>
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
                    <p><?php esc_html_e( 'Chua co bai viet nao trong danh muc nay.', 'developer-theme' ); ?></p>
                <?php endif; ?>
            </div>

            <?php get_sidebar(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

### tag.php - Trang the

```php
<?php
/**
 * tag.php - Template cho trang the (tag)
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
                    esc_html__( 'The: %s', 'developer-theme' ),
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

### author.php - Trang tac gia

```php
<?php
/**
 * author.php - Template cho trang tac gia
 *
 * @package Developer_Theme
 */

get_header();

// Lay thong tin tac gia
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
                        esc_html__( 'Da viet %s bai', 'developer-theme' ),
                        count_user_posts( $author_id )
                    );
                    ?>
                </p>
            </div>
        </header>

        <!-- Bai viet cua tac gia -->
        <div class="content-area">
            <div class="main-content">
                <h2><?php printf( esc_html__( 'Bai viet cua %s', 'developer-theme' ), esc_html( $author_name ) ); ?></h2>

                <?php if ( have_posts() ) : ?>
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        get_template_part( 'template-parts/content', 'archive' );
                    endwhile;

                    the_posts_pagination();
                    ?>
                <?php else : ?>
                    <p><?php esc_html_e( 'Tac gia chua co bai viet nao.', 'developer-theme' ); ?></p>
                <?php endif; ?>
            </div>
            <?php get_sidebar(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
```

### search.php - Trang tim kiem

```php
<?php
/**
 * search.php - Template cho trang ket qua tim kiem
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
                    esc_html__( 'Ket qua tim kiem cho: "%s"', 'developer-theme' ),
                    '<span>' . get_search_query() . '</span>'
                    // get_search_query() tra ve tu khoa da escape
                );
                ?>
            </h1>

            <p class="results-count">
                <?php
                global $wp_query;
                printf(
                    esc_html( _n(
                        'Tim thay %s ket qua',
                        'Tim thay %s ket qua',
                        $wp_query->found_posts,
                        'developer-theme'
                    ) ),
                    number_format_i18n( $wp_query->found_posts )
                );
                ?>
            </p>

            <!-- Form tim kiem lai -->
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
                            <!-- Loai noi dung -->
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
                                    <?php esc_html_e( 'Xem chi tiet', 'developer-theme' ); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>

                    <?php the_posts_pagination(); ?>

                <?php else : ?>
                    <div class="no-results">
                        <h2><?php esc_html_e( 'Khong tim thay ket qua nao', 'developer-theme' ); ?></h2>
                        <p><?php esc_html_e( 'Thu lai voi tu khoa khac hoac duyet qua cac danh muc.', 'developer-theme' ); ?></p>

                        <!-- Goi y -->
                        <div class="search-suggestions">
                            <h3><?php esc_html_e( 'Goi y:', 'developer-theme' ); ?></h3>
                            <ul>
                                <li><?php esc_html_e( 'Kiem tra chinh ta', 'developer-theme' ); ?></li>
                                <li><?php esc_html_e( 'Dung tu khoa ngan hon', 'developer-theme' ); ?></li>
                                <li><?php esc_html_e( 'Thu dung tu dong nghia', 'developer-theme' ); ?></li>
                            </ul>
                        </div>

                        <!-- Danh muc -->
                        <div class="categories-list">
                            <h3><?php esc_html_e( 'Danh muc:', 'developer-theme' ); ?></h3>
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

### 404.php - Trang loi 404

```php
<?php
/**
 * 404.php - Template cho trang loi 404 (khong tim thay)
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main error-404">
    <div class="container">

        <div class="error-content" style="text-align: center; padding: 4rem 0;">
            <h1 class="error-code" style="font-size: 8rem; color: #ddd; margin-bottom: 0;">404</h1>
            <h2><?php esc_html_e( 'Trang khong ton tai', 'developer-theme' ); ?></h2>
            <p>
                <?php esc_html_e(
                    'Xin loi, trang ban dang tim khong ton tai hoac da bi di chuyen.',
                    'developer-theme'
                ); ?>
            </p>

            <!-- Form tim kiem -->
            <div class="error-search" style="max-width: 500px; margin: 2rem auto;">
                <?php get_search_form(); ?>
            </div>

            <!-- Link huu ich -->
            <div class="error-links">
                <h3><?php esc_html_e( 'Co the ban muon xem:', 'developer-theme' ); ?></h3>

                <p>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <?php esc_html_e( 'Trang Chu', 'developer-theme' ); ?>
                    </a>
                </p>

                <!-- Bai viet moi nhat -->
                <h4><?php esc_html_e( 'Bai viet moi nhat:', 'developer-theme' ); ?></h4>
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

                <!-- Danh muc -->
                <h4><?php esc_html_e( 'Danh muc:', 'developer-theme' ); ?></h4>
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
 * Tuong tu @include('components.card') trong Blade
 * Nhung khong co truyen bien tuong tu Blade
 *
 * @param string $slug - Duong dan co so
 * @param string $name - Phan mo rong (optional)
 * @param array  $args - Du lieu truyen vao (WP 5.5+)
 */

// === Vi du 1: Co ban ===
get_template_part( 'template-parts/content' );
// Tim file: template-parts/content.php

// === Vi du 2: Voi name (phu) ===
get_template_part( 'template-parts/content', 'single' );
// Tim file: template-parts/content-single.php
// Neu khong co, fallback: template-parts/content.php

// === Vi du 3: Dua tren post type ===
get_template_part( 'template-parts/content', get_post_type() );
// Neu post type la 'post' -> tim: template-parts/content-post.php
// Neu post type la 'product' -> tim: template-parts/content-product.php

// === Vi du 4: Dua tren post format ===
get_template_part( 'template-parts/content', get_post_format() );
// Neu format la 'video' -> tim: template-parts/content-video.php
// Neu format la 'gallery' -> tim: template-parts/content-gallery.php

// === Vi du 5: Truyen du lieu (WP 5.5+) ===
get_template_part( 'template-parts/content', 'card', array(
    'show_thumbnail' => true,
    'show_excerpt'   => true,
    'columns'        => 3,
    'custom_class'   => 'featured-card',
) );
// Trong template-parts/content-card.php, truy cap:
// $args['show_thumbnail'], $args['show_excerpt'], $args['columns']
```

### Vi du template-parts/content-card.php:

```php
<?php
/**
 * Template part: Content Card
 * Hien thi bai viet dang the (card) cho grid layout
 *
 * Tuong tu @component('card') trong Laravel Blade
 *
 * @param array $args {
 *     @type bool   $show_thumbnail  Co hien thi anh khong
 *     @type bool   $show_excerpt    Co hien thi excerpt khong
 *     @type string $custom_class    Class CSS them
 * }
 *
 * @package Developer_Theme
 */

// Lay args voi gia tri mac dinh
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

### Vi du template-parts/content-none.php:

```php
<?php
/**
 * Template part: No Content
 * Hien thi khi khong co bai viet nao
 *
 * @package Developer_Theme
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title">
            <?php esc_html_e( 'Khong tim thay noi dung', 'developer-theme' ); ?>
        </h1>
    </header>

    <div class="page-content">
        <?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
            <!-- Admin chua tao bai viet nao -->
            <p>
                <?php
                printf(
                    wp_kses(
                        __( 'San sang dang bai viet dau tien? <a href="%1$s">Bat dau o day</a>.', 'developer-theme' ),
                        array( 'a' => array( 'href' => array() ) )
                    ),
                    esc_url( admin_url( 'post-new.php' ) )
                );
                ?>
            </p>

        <?php elseif ( is_search() ) : ?>
            <!-- Tim kiem khong co ket qua -->
            <p><?php esc_html_e( 'Khong tim thay ket qua. Thu tim voi tu khoa khac.', 'developer-theme' ); ?></p>
            <?php get_search_form(); ?>

        <?php else : ?>
            <!-- Truong hop chung -->
            <p><?php esc_html_e( 'Khong the tim thay noi dung ban yeu cau. Thu tim kiem.', 'developer-theme' ); ?></p>
            <?php get_search_form(); ?>
        <?php endif; ?>
    </div>
</section>
```

---

## 5. Header, Footer, Sidebar

### get_header() chi tiet:

```php
// === Co ban ===
get_header();
// Load file: header.php

// === Voi ten cu the ===
get_header( 'landing' );
// Load file: header-landing.php
// Neu khong co, fallback: header.php

// === Vi du: Landing page dung header khac ===
// Trong page-templates/template-landing.php:
get_header( 'landing' ); // Load header-landing.php (khong co sidebar, menu don gian)

// header-landing.php - Header don gian cho landing page:
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

### get_footer() chi tiet:

```php
// === Co ban ===
get_footer();
// Load file: footer.php

// === Voi ten cu the ===
get_footer( 'minimal' );
// Load file: footer-minimal.php
// Neu khong co, fallback: footer.php
```

### get_sidebar() chi tiet:

```php
// === Co ban ===
get_sidebar();
// Load file: sidebar.php

// === Sidebar rieng cho trang shop ===
get_sidebar( 'shop' );
// Load file: sidebar-shop.php

// === Vi du sidebar-shop.php ===
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

Conditional Tags la cac ham tra ve `true/false` de kiem tra dang o trang nao:

```php
<?php
// === KIEM TRA LOAI TRANG ===

is_home()          // Trang blog (hien thi danh sach bai viet moi nhat)
is_front_page()    // Trang chu (front page trong Settings > Reading)
is_single()        // Bai viet don (post, custom post type)
is_page()          // Trang tinh (page)
is_singular()      // Ca post va page (single + page + attachment)
is_archive()       // Trang archive (category, tag, date, author)
is_category()      // Trang danh muc
is_tag()           // Trang the
is_author()        // Trang tac gia
is_date()          // Trang ngay thang
is_year()          // Trang nam
is_month()         // Trang thang
is_day()           // Trang ngay
is_search()        // Trang ket qua tim kiem
is_404()           // Trang loi 404
is_attachment()    // Trang file dinh kem
is_tax()           // Trang custom taxonomy

// === KIEM TRA CU THE ===

is_single( 'hello-world' )        // Bai viet co slug 'hello-world'
is_single( 42 )                    // Bai viet co ID 42
is_single( array( 42, 'hello' ) ) // Bai viet co ID 42 HOAC slug 'hello'

is_page( 'about' )                // Trang co slug 'about'
is_page( 10 )                     // Trang co ID 10
is_page( array( 'about', 'contact', 10 ) ) // 1 trong 3 trang nay

is_category( 'tin-tuc' )          // Danh muc co slug 'tin-tuc'
is_category( 5 )                  // Danh muc co ID 5

is_tag( 'wordpress' )             // The co slug 'wordpress'

is_author( 'admin' )              // Trang tac gia co nicename 'admin'

is_post_type_archive( 'product' ) // Archive cua post type 'product'

is_tax( 'brand' )                 // Taxonomy 'brand'
is_tax( 'brand', 'apple' )        // Taxonomy 'brand', term 'apple'

// === KIEM TRA THUOC TINH ===

is_sticky()                       // Bai viet ghim
has_post_thumbnail()              // Co featured image
has_excerpt()                     // Co excerpt tu viet
has_nav_menu( 'primary' )         // Co menu o vi tri 'primary'
is_active_sidebar( 'sidebar-1' )  // Sidebar co widget

in_category( 'tin-tuc' )          // Bai viet hien tai thuoc danh muc 'tin-tuc'
has_category()                    // Bai viet co it nhat 1 category
has_tag()                         // Bai viet co it nhat 1 tag
has_tag( 'wordpress' )            // Bai viet co tag 'wordpress'

// === KIEM TRA NGUOI DUNG ===

is_user_logged_in()               // Dang dang nhap
current_user_can( 'edit_posts' )  // Co quyen chinh sua bai viet
is_admin()                        // Dang o admin area
is_customize_preview()            // Dang trong Customizer preview

// === KIEM TRA KHAC ===

is_paged()                        // Trang 2, 3, ... (co phan trang)
is_main_query()                   // Co phai main query khong
is_child_theme()                  // Dang dung child theme
is_rtl()                          // Ngon ngu viet tu phai sang trai
is_multisite()                    // WordPress Multisite

// === SU DUNG TRONG TEMPLATE ===

// Vi du 1: Hien thi khac nhau theo trang
if ( is_home() ) {
    echo '<h1>Blog</h1>';
} elseif ( is_single() ) {
    echo '<h1>' . get_the_title() . '</h1>';
} elseif ( is_page() ) {
    echo '<h1>' . get_the_title() . '</h1>';
} elseif ( is_category() ) {
    echo '<h1>Danh muc: ' . single_cat_title( '', false ) . '</h1>';
} elseif ( is_search() ) {
    echo '<h1>Tim kiem: ' . get_search_query() . '</h1>';
} elseif ( is_404() ) {
    echo '<h1>404 - Khong tim thay</h1>';
}

// Vi du 2: Body class khac nhau
$body_class = 'site';
if ( is_front_page() ) {
    $body_class .= ' front-page';
}
if ( ! is_active_sidebar( 'sidebar-main' ) || is_page_template( 'template-full-width.php' ) ) {
    $body_class .= ' no-sidebar';
}

// Vi du 3: Sidebar co dieu kien
if ( is_single() || is_page() ) {
    get_sidebar();
    // Chi hien sidebar tren trang single va page
}

// Vi du 4: Trong functions.php - Conditional enqueue
function developer_conditional_scripts() {
    // Chi load slider JS tren trang chu
    if ( is_front_page() ) {
        wp_enqueue_script( 'slider', get_template_directory_uri() . '/assets/js/slider.js' );
    }

    // Chi load gallery CSS khi bai viet co gallery
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
 * Khi ban tao Custom Post Type (vi du: 'product'),
 * WordPress se tim template theo thu tu:
 *
 * Single Product:
 *   single-product-{slug}.php -> single-product.php -> single.php -> singular.php -> index.php
 *
 * Archive Products:
 *   archive-product.php -> archive.php -> index.php
 */

// === Buoc 1: Dang ky Custom Post Type (trong functions.php hoac plugin) ===
function developer_register_product_cpt() {
    register_post_type( 'product', array(
        'labels' => array(
            'name'               => __( 'San Pham', 'developer-theme' ),
            'singular_name'      => __( 'San Pham', 'developer-theme' ),
            'add_new_item'       => __( 'Them San Pham Moi', 'developer-theme' ),
            'edit_item'          => __( 'Sua San Pham', 'developer-theme' ),
            'view_item'          => __( 'Xem San Pham', 'developer-theme' ),
            'all_items'          => __( 'Tat Ca San Pham', 'developer-theme' ),
            'search_items'       => __( 'Tim San Pham', 'developer-theme' ),
            'not_found'          => __( 'Khong tim thay san pham nao', 'developer-theme' ),
        ),
        'public'       => true,
        'has_archive'  => true,                    // Co trang archive
        'rewrite'      => array( 'slug' => 'san-pham' ), // URL: /san-pham/ten-sp
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'menu_icon'    => 'dashicons-cart',
        'show_in_rest' => true,                    // Ho tro Gutenberg
    ) );
}
add_action( 'init', 'developer_register_product_cpt' );

// === Buoc 2: Tao single-product.php ===
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

                <!-- Hinh anh san pham -->
                <div class="product-gallery">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'large' ); ?>
                    <?php endif; ?>
                </div>

                <!-- Thong tin san pham -->
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
                            <span class="in-stock"><?php esc_html_e( 'Con hang', 'developer-theme' ); ?></span>
                        <?php else : ?>
                            <span class="out-of-stock"><?php esc_html_e( 'Het hang', 'developer-theme' ); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-description">
                        <?php the_content(); ?>
                    </div>

                    <!-- Taxonomies (danh muc san pham) -->
                    <?php
                    $product_cats = get_the_terms( get_the_ID(), 'product_category' );
                    if ( $product_cats && ! is_wp_error( $product_cats ) ) :
                    ?>
                        <div class="product-categories">
                            <strong><?php esc_html_e( 'Danh muc:', 'developer-theme' ); ?></strong>
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
// === Buoc 3: Tao archive-product.php ===
// File: archive-product.php
?>
<?php get_header(); ?>

<main id="primary" class="site-main archive-product">
    <div class="container">

        <header class="page-header">
            <h1 class="page-title"><?php esc_html_e( 'San Pham', 'developer-theme' ); ?></h1>
            <?php
            // Hien thi mo ta cua post type archive
            $post_type_description = get_the_post_type_description();
            if ( $post_type_description ) :
            ?>
                <p class="archive-description"><?php echo esc_html( $post_type_description ); ?></p>
            <?php endif; ?>
        </header>

        <!-- Filter theo danh muc san pham -->
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
                        <?php esc_html_e( 'Tat Ca', 'developer-theme' ); ?>
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
            <p><?php esc_html_e( 'Chua co san pham nao.', 'developer-theme' ); ?></p>
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
 * Vi du: Taxonomy 'brand', term 'apple':
 * taxonomy-brand-apple.php -> taxonomy-brand.php -> taxonomy.php -> archive.php -> index.php
 */

// === Dang ky Custom Taxonomy (trong functions.php hoac plugin) ===
function developer_register_brand_taxonomy() {
    register_taxonomy( 'brand', 'product', array(
        'labels' => array(
            'name'          => __( 'Thuong Hieu', 'developer-theme' ),
            'singular_name' => __( 'Thuong Hieu', 'developer-theme' ),
            'search_items'  => __( 'Tim Thuong Hieu', 'developer-theme' ),
            'all_items'     => __( 'Tat Ca Thuong Hieu', 'developer-theme' ),
            'edit_item'     => __( 'Sua Thuong Hieu', 'developer-theme' ),
            'add_new_item'  => __( 'Them Thuong Hieu Moi', 'developer-theme' ),
        ),
        'public'       => true,
        'hierarchical' => true,       // Nhu category (co parent/child)
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

## 9. Code vi du cho tung template

### Template cho comments.php:

```php
<?php
/**
 * comments.php - Template hien thi binh luan
 *
 * @package Developer_Theme
 */

// Khong hien thi binh luan neu trang can mat khau
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
                    '%1$s binh luan cho "%2$s"',
                    '%1$s binh luan cho "%2$s"',
                    $comment_count,
                    'developer-theme'
                ) ),
                number_format_i18n( $comment_count ),
                get_the_title()
            );
            ?>
        </h2>

        <!-- Danh sach binh luan -->
        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 50,
                'max_depth'   => 3,  // Do sau reply toi da
            ) );
            ?>
        </ol>

        <!-- Phan trang binh luan -->
        <?php
        the_comments_navigation( array(
            'prev_text' => __( 'Binh luan cu hon', 'developer-theme' ),
            'next_text' => __( 'Binh luan moi hon', 'developer-theme' ),
        ) );
        ?>

    <?php endif; // have_comments() ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="no-comments">
            <?php esc_html_e( 'Binh luan da dong.', 'developer-theme' ); ?>
        </p>
    <?php endif; ?>

    <!-- Form binh luan -->
    <?php
    comment_form( array(
        'title_reply'          => __( 'De lai binh luan', 'developer-theme' ),
        'title_reply_to'       => __( 'Tra loi %s', 'developer-theme' ),
        'cancel_reply_link'    => __( 'Huy tra loi', 'developer-theme' ),
        'label_submit'         => __( 'Gui binh luan', 'developer-theme' ),
        'comment_notes_before' => '<p class="comment-notes">'
            . __( 'Email se khong duoc hien thi cong khai.', 'developer-theme' )
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
 * Dong "Template Name:" la BAT BUOC - no dang ky template nay
 * trong dropdown "Page Attributes > Template" khi edit Page
 *
 * "Template Post Type:" cho phep chon template nay cho nhieu post type
 *
 * Tuong tu viec tao @section khac nhau trong Laravel Blade
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

## 10. So sanh voi Laravel Blade

### Routing/Template Selection

```php
// === LARAVEL ===
// routes/web.php
Route::get('/', [HomeController::class, 'index']);           // Trang chu
Route::get('/blog', [PostController::class, 'index']);       // Danh sach bai
Route::get('/blog/{slug}', [PostController::class, 'show']);  // Bai viet
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
// KHONG CAN routes va controller!
// Chi can tao file template:
// front-page.php   --> Trang chu
// home.php         --> Trang blog
// single.php       --> Bai viet
// page.php         --> Trang
// category.php     --> Danh muc
// WordPress TU DONG map URL -> Template file
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
// Khong co "extends", dung get_header() va get_footer() thay the:
// index.php
<?php get_header(); ?>    <!-- = @include('partials.header') + bat dau layout -->
<main>
    <h1>Home</h1>
    <?php get_sidebar(); ?> <!-- = @include('partials.sidebar') -->
</main>
<?php get_footer(); ?>    <!-- = @include('partials.footer') + ket thuc layout -->
```

### Components/Partials

```php
// === LARAVEL BLADE ===
// components/post-card.blade.php
<div class="card">
    <h3>{{ $post->title }}</h3>
    <p>{{ $post->excerpt }}</p>
</div>

// Su dung:
@foreach($posts as $post)
    <x-post-card :post="$post" />
@endforeach

// === WORDPRESS ===
// template-parts/content-card.php
<div class="card">
    <h3><?php the_title(); ?></h3>
    <p><?php the_excerpt(); ?></p>
</div>

// Su dung:
<?php
while ( have_posts() ) :
    the_post();
    get_template_part( 'template-parts/content', 'card' );
endwhile;
?>

// Truyen data (WP 5.5+):
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

### 1. Luon co index.php

```php
// index.php la BAT BUOC va la fallback cuoi cung
// Ke ca khi ban co single.php, page.php, archive.php... van phai co index.php
```

### 2. Su dung get_template_part() thay vi include/require

```php
// SAI
include( 'template-parts/content.php' );
require( TEMPLATEPATH . '/template-parts/content.php' );

// DUNG
get_template_part( 'template-parts/content' );
// get_template_part() an toan hon vi:
// - Tu dong xu ly duong dan
// - Ho tro child theme override
// - Khong bi loi fatal neu file khong ton tai
```

### 3. Dung template phu hop, khong lam dung index.php

```php
// SAI: Dung conditional trong index.php cho moi thu
// index.php
if ( is_single() ) {
    // code cho single...
} elseif ( is_page() ) {
    // code cho page...
} elseif ( is_category() ) {
    // code cho category...
}

// DUNG: Tao file template rieng
// single.php - cho bai viet
// page.php - cho trang
// category.php - cho danh muc
```

### 4. wp_reset_postdata() sau WP_Query

```php
// Khi dung custom WP_Query, LUON reset lai sau khi xong
$custom_query = new WP_Query( array( 'post_type' => 'product' ) );
while ( $custom_query->have_posts() ) :
    $custom_query->the_post();
    // ...
endwhile;
wp_reset_postdata(); // BAT BUOC! Neu khong, cac ham nhu the_title() se bi sai
```

### 5. Kiem tra template dang dung

```php
// Trong development, them code nay de biet WordPress dang dung template nao:
function developer_show_template() {
    if ( current_user_can( 'manage_options' ) ) {
        global $template;
        echo '<!-- Template: ' . basename( $template ) . ' -->';
    }
}
add_action( 'wp_head', 'developer_show_template' );

// Hoac cai plugin "Query Monitor" de xem chi tiet
```

### 6. Dat ten file dung quy uoc

```
DUNG:                          SAI:
single.php                     post.php
page.php                       static-page.php
archive.php                    list.php
template-parts/content.php     parts/content.php (van chay nhung khong chuan)
page-templates/full-width.php  templates/full-width.php
```

### 7. Su dung body_class() va post_class()

```php
// Luon dung de CSS targeting de dang hon
<body <?php body_class(); ?>>
// Tao ra: <body class="home blog logged-in admin-bar">

<article <?php post_class(); ?>>
// Tao ra: <article class="post type-post status-publish format-standard has-post-thumbnail hentry category-news">

// Them class tuy chinh:
<body <?php body_class( 'custom-layout dark-mode' ); ?>>
<article <?php post_class( 'card featured' ); ?>>
```

---

**Tiep theo:** [03 - The Loop va WP_Query](./03-the-loop-va-wp-query.md) - Hieu cach lay va hien thi du lieu
