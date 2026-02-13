# The Loop và WP_Query trong WordPress

## Mục Lục

1. [The Loop là gì](#1-the-loop-la-gi)
2. [Cấu trúc Loop cơ bản](#2-cau-truc-loop-co-ban)
3. [Loop Functions chi tiết](#3-loop-functions)
4. [Custom Loop với WP_Query](#4-custom-loop-voi-wp_query)
5. [Tham số WP_Query chi tiết](#5-tham-so-wp_query)
6. [Multiple Loops trên một page](#6-multiple-loops)
7. [Pagination](#7-pagination)
8. [pre_get_posts hook](#8-pre_get_posts)
9. [Code ví dụ: Trang blog với nhiều loops](#9-code-vi-du)
10. [So sánh với Eloquent trong Laravel](#10-so-sanh-voi-eloquent)
11. [Best Practices](#11-best-practices)

---

## 1. The Loop là gì

The Loop (Vòng Lặp) là cơ chế **cơ bản nhất** của WordPress để hiển thị nội dung. Nó là một vòng lặp PHP duyệt qua danh sách bài viết và hiển thị từng bài.

### Nguyên lý hoạt động:

```
1. Người dùng truy cập URL (vd: /category/tin-tuc/)
2. WordPress tự động tạo 1 query (Main Query) để lấy bài viết phù hợp
3. The Loop duyệt qua kết quả của query đó
4. Mỗi vòng lặp, WordPress "setup" bài viết hiện tại (global $post)
5. Bạn dùng các hàm như the_title(), the_content() để hiển thị
```

### So sánh nhanh với Laravel:

```php
// LARAVEL
@foreach ($posts as $post)
    <h2>{{ $post->title }}</h2>
    <p>{{ $post->content }}</p>
@endforeach

// WORDPRESS
<?php while ( have_posts() ) : the_post(); ?>
    <h2><?php the_title(); ?></h2>
    <div><?php the_content(); ?></div>
<?php endwhile; ?>
```

**Điểm khác biệt:** Trong Laravel, bạn truyền `$posts` từ controller. Trong WordPress, The Loop tự động lấy dữ liệu từ Main Query (dựa trên URL).

---

## 2. Cấu trúc Loop cơ bản

### Loop tối giản nhất:

```php
<?php
/**
 * Cấu trúc Loop cơ bản nhất
 */
if ( have_posts() ) :
    // Có bài viết -> bắt đầu loop
    while ( have_posts() ) :
        the_post();
        // Hiển thị nội dung bài viết ở đây
        the_title();
        the_content();
    endwhile;
else :
    // Không có bài viết nào
    echo 'Không tìm thấy bài viết.';
endif;
?>
```

### Giải thích chi tiết:

```php
<?php
/**
 * === have_posts() ===
 * - Kiểm tra còn bài viết nào trong query không
 * - Trả về true/false
 * - Tương tự: $collection->isNotEmpty() trong Laravel
 *
 * === the_post() ===
 * - Chuyển đến bài viết tiếp theo trong loop
 * - Setup global $post object
 * - Sau khi gọi the_post(), tất cả các hàm như the_title(), the_content()
 *   sẽ trả về dữ liệu của bài viết HIỆN TẠI
 * - Tương tự: $loop->current() trong Laravel
 *
 * === the_title() ===
 * - In ra tiêu đề bài viết hiện tại
 * - the_title() echo ra, get_the_title() trả về string
 *
 * === the_content() ===
 * - In ra nội dung bài viết hiện tại (đã qua filter)
 * - Tự động apply shortcodes, embed, wpautop...
 */
?>
```

### Loop đầy đủ với HTML:

```php
<?php if ( have_posts() ) : ?>

    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <!-- Featured Image -->
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail( 'medium' ); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <header class="entry-header">
                <h2 class="entry-title">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h2>

                <div class="entry-meta">
                    <span class="posted-date">
                        <time datetime="<?php echo get_the_date( 'c' ); ?>">
                            <?php echo get_the_date(); ?>
                        </time>
                    </span>
                    <span class="author">
                        <?php the_author_posts_link(); ?>
                    </span>
                    <span class="categories">
                        <?php the_category( ', ' ); ?>
                    </span>
                    <span class="comments">
                        <?php comments_number( 'Chưa có bình luận', '1 bình luận', '% bình luận' ); ?>
                    </span>
                </div>
            </header>

            <!-- Content / Excerpt -->
            <div class="entry-content">
                <?php
                if ( is_singular() ) {
                    // Trang single: hiển thị toàn bộ nội dung
                    the_content( __( 'Đọc tiếp &rarr;', 'developer-theme' ) );
                } else {
                    // Trang archive: hiển thị excerpt (tóm tắt)
                    the_excerpt();
                }
                ?>
            </div>

            <!-- Footer -->
            <footer class="entry-footer">
                <?php
                // Tags
                the_tags( '<span class="tags">Tags: ', ', ', '</span>' );

                // Edit link (chỉ hiện cho người có quyền)
                edit_post_link( __( 'Chỉnh sửa', 'developer-theme' ), '<span class="edit-link">', '</span>' );
                ?>
            </footer>

        </article>

    <?php endwhile; ?>

    <!-- Pagination -->
    <?php the_posts_pagination(); ?>

<?php else : ?>

    <p><?php esc_html_e( 'Không có bài viết nào.', 'developer-theme' ); ?></p>

<?php endif; ?>
```

---

## 3. Loop Functions

### Các hàm hiển thị thông tin bài viết:

```php
<?php
// === TIÊU ĐỀ ===
the_title();                          // Echo tiêu đề
the_title( '<h1>', '</h1>' );         // Bọc trong tag
$title = get_the_title();             // Trả về string (không echo)
$title = get_the_title( $post_id );   // Lấy tiêu đề theo ID

// === NỘI DUNG ===
the_content();                        // Echo toàn bộ nội dung (đã filter)
the_content( 'Đọc tiếp...' );         // Với "more" link text
$content = get_the_content();         // Trả về string (CHƯA filter)
$content = apply_filters( 'the_content', get_the_content() ); // Trả về string (ĐÃ filter)

// === TÓM TẮT (EXCERPT) ===
the_excerpt();                        // Echo excerpt (tự động tạo từ content nếu không có)
$excerpt = get_the_excerpt();         // Trả về string

// Mặc định: 55 từ, kết thúc bằng "[...]"
// Tùy chỉnh excerpt length:
function custom_excerpt_length( $length ) {
    return 20; // 20 từ
}
add_filter( 'excerpt_length', 'custom_excerpt_length' );

// Tùy chỉnh excerpt more text:
function custom_excerpt_more( $more ) {
    return '... <a href="' . get_permalink() . '">Đọc thêm</a>';
}
add_filter( 'excerpt_more', 'custom_excerpt_more' );

// Tùy chỉnh số từ excerpt ngay trong template:
echo wp_trim_words( get_the_content(), 30, '...' );

// === LINK/URL ===
the_permalink();                      // Echo URL bài viết
$url = get_the_permalink();           // Trả về URL
$url = get_permalink();               // Giống get_the_permalink()
$url = get_permalink( $post_id );     // URL theo ID

// === FEATURED IMAGE (Ảnh đại diện) ===
the_post_thumbnail();                 // Echo ảnh mặc định
the_post_thumbnail( 'thumbnail' );    // Kích thước 150x150
the_post_thumbnail( 'medium' );       // Kích thước 300x300
the_post_thumbnail( 'medium_large' ); // Kích thước 768px wide
the_post_thumbnail( 'large' );        // Kích thước 1024x1024
the_post_thumbnail( 'full' );         // Kích thước gốc
the_post_thumbnail( 'developer-featured' ); // Kích thước tùy chỉnh
the_post_thumbnail( array( 400, 300 ) );    // Kích thước cụ thể

// Lay URL cua featured image:
$thumbnail_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );

// Kiểm tra có featured image không:
if ( has_post_thumbnail() ) {
    the_post_thumbnail( 'large', array(
        'class' => 'featured-img',
        'alt'   => get_the_title(),
        'loading' => 'lazy',        // Lazy loading
    ) );
}

// === ID ===
the_ID();                             // Echo ID bài viết
$id = get_the_ID();                   // Trả về ID

// === NGÀY THÁNG ===
the_date();                           // Echo ngày (chỉ hiện 1 lần/ngày)
the_time();                           // Echo giờ
echo get_the_date();                  // Luôn hiển thị (khác the_date!)
echo get_the_date( 'd/m/Y' );        // Định dạng tùy chỉnh
echo get_the_date( 'F j, Y' );       // January 15, 2024
echo get_the_modified_date();         // Ngày chỉnh sửa cuối
echo human_time_diff( get_the_time('U'), current_time('timestamp') ) . ' trước';
// Output: "2 ngày trước", "3 giờ trước"

// === TÁC GIẢ ===
the_author();                         // Echo tên tác giả
$author = get_the_author();           // Trả về tên tác giả
the_author_posts_link();              // Link đến trang tác giả
echo get_the_author_meta( 'display_name' );
echo get_the_author_meta( 'description' );  // Bio
echo get_the_author_meta( 'user_email' );
echo get_avatar( get_the_author_meta( 'ID' ), 80 ); // Avatar 80px

// === DANH MỤC VÀ THẺ ===
the_category( ', ' );                 // Echo danh mục, phân cách bằng phẩy
$categories = get_the_category();     // Mảng các category objects
the_tags( 'Tags: ', ', ', '' );       // Echo tags
$tags = get_the_tags();               // Mảng các tag objects

// Chỉ lấy tên danh mục đầu tiên:
$cat = get_the_category();
if ( $cat ) {
    echo $cat[0]->name;
}

// Lấy tất cả categories với link:
echo get_the_category_list( ', ' );

// === BÌNH LUẬN ===
comments_number();                    // "No Comments", "1 Comment", "5 Comments"
comments_number( 'Chưa có', '1 bình luận', '% bình luận' ); // Tùy chỉnh
$count = get_comments_number();       // Trả về số

// === CUSTOM FIELDS (Post Meta) ===
$value = get_post_meta( get_the_ID(), 'meta_key', true );  // Lấy 1 giá trị
$values = get_post_meta( get_the_ID(), 'meta_key', false ); // Lấy mảng giá trị
$all_meta = get_post_meta( get_the_ID() );                  // Lấy tất cả meta

// === POST TYPE ===
$type = get_post_type();                    // 'post', 'page', 'product'...
$type_obj = get_post_type_object( $type );  // Object chi tiết

// === POST FORMAT ===
$format = get_post_format();          // 'video', 'gallery', 'quote', false (standard)

// === POST STATUS ===
$status = get_post_status();          // 'publish', 'draft', 'private', 'pending'...

// === CLASSES ===
post_class();                         // Thêm các class cho <article>
post_class( 'custom-class' );        // Thêm class tùy chỉnh
$classes = get_post_class();          // Trả về mảng classes
?>
```

### Ví dụ sử dụng tất cả các hàm:

```php
<?php while ( have_posts() ) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <?php if ( has_post_thumbnail() ) : ?>
        <figure class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'medium_large', array(
                    'alt'     => get_the_title(),
                    'loading' => 'lazy',
                ) ); ?>
            </a>
        </figure>
    <?php endif; ?>

    <div class="post-body">
        <!-- Categories -->
        <div class="post-categories">
            <?php
            $categories = get_the_category();
            if ( $categories ) :
                foreach ( $categories as $cat ) :
            ?>
                <a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"
                   class="cat-badge"
                   style="background-color: <?php echo esc_attr( get_term_meta( $cat->term_id, 'color', true ) ?: '#0073aa' ); ?>">
                    <?php echo esc_html( $cat->name ); ?>
                </a>
            <?php
                endforeach;
            endif;
            ?>
        </div>

        <!-- Title -->
        <h2 class="post-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

        <!-- Meta -->
        <div class="post-meta">
            <!-- Avatar + Author -->
            <span class="author">
                <?php echo get_avatar( get_the_author_meta( 'ID' ), 24 ); ?>
                <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
                    <?php the_author(); ?>
                </a>
            </span>

            <!-- Date -->
            <span class="date">
                <time datetime="<?php echo get_the_date( 'c' ); ?>">
                    <?php echo get_the_date( 'd/m/Y' ); ?>
                </time>
            </span>

            <!-- Reading time -->
            <span class="reading-time">
                <?php
                $word_count = str_word_count( wp_strip_all_tags( get_the_content() ) );
                $minutes = max( 1, ceil( $word_count / 200 ) );
                printf( __( '%d phút đọc', 'developer-theme' ), $minutes );
                ?>
            </span>

            <!-- Comments -->
            <span class="comments-count">
                <a href="<?php comments_link(); ?>">
                    <?php comments_number( '0 bình luận', '1 bình luận', '% bình luận' ); ?>
                </a>
            </span>
        </div>

        <!-- Excerpt -->
        <div class="post-excerpt">
            <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
        </div>

        <!-- Read More -->
        <a href="<?php the_permalink(); ?>" class="read-more-link">
            <?php esc_html_e( 'Đọc thêm', 'developer-theme' ); ?> &rarr;
        </a>

        <!-- Tags -->
        <?php if ( has_tag() ) : ?>
            <div class="post-tags">
                <?php the_tags( '', ' ', '' ); ?>
            </div>
        <?php endif; ?>
    </div>

</article>

<?php endwhile; ?>
```

---

## 4. Custom Loop với WP_Query

### Khi nào cần WP_Query?

Main Query (The Loop mặc định) chỉ lấy bài viết dựa trên URL hiện tại. Khi bạn cần:
- Hiển thị bài viết ở vị trí khác (sidebar, footer)
- Lấy bài viết theo điều kiện riêng
- Hiển thị nhiều danh sách bài viết trên 1 trang
- Lấy bài viết từ Custom Post Type

Bạn cần tạo **Custom Query** với `WP_Query`.

### Cơ bản:

```php
<?php
/**
 * WP_Query cơ bản - Lấy 5 bài viết mới nhất
 */
$query = new WP_Query( array(
    'post_type'      => 'post',        // Loại bài viết
    'posts_per_page' => 5,             // Số bài viết
    'post_status'    => 'publish',     // Chỉ lấy bài đã xuất bản
) );

// Loop qua kết quả
if ( $query->have_posts() ) :
    while ( $query->have_posts() ) :
        $query->the_post();
        // Sau the_post(), các hàm như the_title() hoạt động bình thường
?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php the_excerpt(); ?></p>
<?php
    endwhile;
endif;

// === BẮT BUỘC: Reset postdata ===
wp_reset_postdata();
// Nếu không reset, các hàm như the_title() bên ngoài loop này
// sẽ trả về dữ liệu của bài viết cuối cùng trong custom query
// thay vì bài viết của Main Query
?>
```

### So sanh voi Laravel Eloquent:

```php
// LARAVEL
$posts = Post::where('status', 'published')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($posts as $post) {
    echo $post->title;
}

// WORDPRESS
$query = new WP_Query( array(
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'posts_per_page' => 5,
) );

while ( $query->have_posts() ) :
    $query->the_post();
    the_title();
endwhile;
wp_reset_postdata();
```

---

## 5. Tham số WP_Query chi tiết

### Tổng hợp tất cả tham số:

```php
<?php
/**
 * WP_Query - Tất cả các tham số quan trọng
 */
$args = array(

    // === LOẠI BÀI VIẾT ===
    'post_type'      => 'post',                // string hoac array
    // 'post_type'   => array( 'post', 'page', 'product' ),
    // 'post_type'   => 'any', // Tất cả post types (trừ revision và nav_menu_item)

    'post_status'    => 'publish',             // publish, draft, pending, private, trash, any
    // 'post_status' => array( 'publish', 'draft' ),

    // === SỐ LƯỢNG VÀ PHÂN TRANG ===
    'posts_per_page' => 10,                    // Số bài mỗi trang (-1 = tất cả)
    'offset'         => 5,                     // Bỏ qua N bài đầu tiên
    'paged'          => get_query_var('paged') ?: 1, // Trang hiện tại (cho pagination)
    'nopaging'       => false,                 // true = lấy tất cả, bỏ qua phân trang

    // === SẮP XẾP ===
    'orderby'        => 'date',                // Sắp xếp theo
    // Các giá trị orderby:
    // 'date'          - Ngày đăng
    // 'modified'      - Ngày chỉnh sửa
    // 'title'         - Tiêu đề (alphabet)
    // 'name'          - Slug
    // 'ID'            - ID bài viết
    // 'author'        - Tác giả
    // 'rand'          - Ngẫu nhiên
    // 'comment_count' - Số bình luận
    // 'menu_order'    - Thứ tự menu (dùng cho Pages)
    // 'meta_value'    - Giá trị meta (cần thêm meta_key)
    // 'meta_value_num' - Giá trị meta dạng số
    // 'post__in'      - Theo thứ tự của mảng post__in

    'order'          => 'DESC',                // DESC (giảm) hoặc ASC (tăng)

    // Sắp xếp theo nhiều trường:
    // 'orderby' => array(
    //     'meta_value_num' => 'DESC',
    //     'title'          => 'ASC',
    // ),

    // === LỌC THEO ID ===
    'p'              => 42,                    // Lấy bài viết có ID = 42
    'post__in'       => array( 1, 2, 3 ),      // Chỉ lấy các ID này
    'post__not_in'   => array( 4, 5, 6 ),      // Loại trừ các ID này

    // === LỌC THEO SLUG ===
    'name'           => 'hello-world',         // Lấy theo slug
    'pagename'       => 'about',               // Lấy page theo slug

    // === LỌC THEO PARENT ===
    'post_parent'     => 10,                   // Lấy các trang con của trang ID=10
    'post_parent__in' => array( 10, 20 ),      // Con của nhiều trang

    // === LỌC THEO TÁC GIẢ ===
    'author'          => 1,                    // ID tác giả
    'author_name'     => 'admin',              // Nicename tác giả
    'author__in'      => array( 1, 2, 3 ),     // Nhiều tác giả
    'author__not_in'  => array( 4, 5 ),        // Loại trừ tác giả

    // === LỌC THEO DANH MỤC ===
    'cat'              => 5,                   // ID danh mục
    'category_name'    => 'tin-tuc',           // Slug danh mục
    'category__in'     => array( 5, 6, 7 ),    // Thuộc ÍT NHẤT 1 danh mục
    'category__not_in' => array( 8, 9 ),       // Không thuộc các danh mục này
    'category__and'    => array( 5, 6 ),       // Thuộc TẤT CẢ các danh mục này

    // === LỌC THEO THẺ (TAG) ===
    'tag'          => 'wordpress',             // Slug tag
    'tag_id'       => 10,                      // ID tag
    'tag__in'      => array( 10, 11 ),         // Có ÍT NHẤT 1 tag
    'tag__not_in'  => array( 12, 13 ),         // Không có các tag này
    'tag__and'     => array( 10, 11 ),         // Có TẤT CẢ các tag này
    'tag_slug__in' => array( 'wp', 'php' ),    // Theo slug

    // === TÌM KIẾM ===
    's'              => 'từ khóa tìm kiếm',    // Tìm kiếm trong title và content

    // === POST FORMAT ===
    // Dùng tax_query (xem bên dưới)

    // === STICKY POSTS ===
    'ignore_sticky_posts' => true,             // Bỏ qua sticky posts
    // Mặc định: sticky posts luôn hiện đầu tiên

    // === PERFORMANCE ===
    'no_found_rows'          => true,          // Không đếm tổng số bài (nhanh hơn, nhưng mất pagination)
    'update_post_meta_cache' => false,         // Không cache meta (nhanh hơn nếu không cần meta)
    'update_post_term_cache' => false,         // Không cache terms
    'fields'                 => 'ids',         // Chỉ lấy IDs thay vì full objects
    // 'fields' => 'id=>parent',              // Chỉ lấy ID và parent

    // === CACHE ===
    'cache_results'  => true,                  // Cache kết quả (mặc định true)
);

$query = new WP_Query( $args );
```

### Meta Query (Custom Fields):

```php
<?php
/**
 * meta_query - Lọc theo Custom Fields
 * Tương tự WHERE ... AND meta_key = 'value' trong SQL
 * Tương tự ->where('meta_key', 'value') trong Eloquent
 */

// === Ví dụ 1: Lấy sản phẩm có giá > 100000 ===
$products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_key'   => 'price',           // Dùng kết hợp với orderby meta_value_num
    'orderby'    => 'meta_value_num',  // Sắp xếp theo giá (dạng số)
    'order'      => 'ASC',             // Từ thấp đến cao
    'meta_query' => array(
        array(
            'key'     => 'price',        // Tên meta key
            'value'   => 100000,         // Giá trị so sánh
            'compare' => '>',            // Phép so sánh
            'type'    => 'NUMERIC',      // Kiểu dữ liệu
        ),
    ),
) );

// === Ví dụ 2: Nhiều điều kiện (AND) ===
$products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',            // Tất cả điều kiện đều phải đúng
        array(
            'key'     => 'price',
            'value'   => array( 100000, 500000 ),
            'compare' => 'BETWEEN',      // Giá từ 100k-500k
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => 'in_stock',
            'value'   => '1',
            'compare' => '=',            // Còn hàng
        ),
        array(
            'key'     => 'featured',
            'compare' => 'EXISTS',       // Có trường 'featured' (bất kỳ giá trị nào)
        ),
    ),
) );

// === Ví dụ 3: Nhiều điều kiện (OR) ===
$posts = new WP_Query( array(
    'post_type'  => 'post',
    'meta_query' => array(
        'relation' => 'OR',             // Chỉ cần 1 điều kiện đúng
        array(
            'key'     => 'color',
            'value'   => 'red',
            'compare' => '=',
        ),
        array(
            'key'     => 'color',
            'value'   => 'blue',
            'compare' => '=',
        ),
    ),
) );

// === Ví dụ 4: Kết hợp AND và OR (nested) ===
$posts = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        // Điều kiện 1: Còn hàng
        array(
            'key'     => 'in_stock',
            'value'   => '1',
            'compare' => '=',
        ),
        // Điều kiện 2: Màu đỏ HOẶC xanh
        array(
            'relation' => 'OR',
            array(
                'key'     => 'color',
                'value'   => 'red',
                'compare' => '=',
            ),
            array(
                'key'     => 'color',
                'value'   => 'blue',
                'compare' => '=',
            ),
        ),
    ),
) );

// === Các phép so sánh (compare) ===
// '='           : Bằng
// '!='          : Không bằng
// '>'           : Lớn hơn
// '>='          : Lớn hơn hoặc bằng
// '<'           : Nhỏ hơn
// '<='          : Nhỏ hơn hoặc bằng
// 'LIKE'        : Chứa ký tự (tương tự SQL LIKE)
// 'NOT LIKE'    : Không chứa
// 'IN'          : Trong mảng (value là array)
// 'NOT IN'      : Không trong mảng
// 'BETWEEN'     : Giữa 2 giá trị (value là array 2 phần tử)
// 'NOT BETWEEN' : Không giữa 2 giá trị
// 'EXISTS'      : Tồn tại meta key (không cần value)
// 'NOT EXISTS'  : Không tồn tại meta key

// === Các kiểu dữ liệu (type) ===
// 'CHAR'      : Chuỗi ký tự (mặc định)
// 'NUMERIC'   : Số nguyên
// 'DECIMAL'   : Số thập phân
// 'DATE'      : Ngày (Y-m-d)
// 'DATETIME'  : Ngày giờ (Y-m-d H:i:s)
// 'TIME'      : Giờ (H:i:s)
// 'SIGNED'    : Số nguyên có dấu
// 'UNSIGNED'  : Số nguyên không dấu
// 'BINARY'    : Nhị phân
```

### Tax Query (Taxonomy):

```php
<?php
/**
 * tax_query - Lọc theo Taxonomy (Category, Tag, Custom Taxonomy)
 * Tương tự whereHas('category', ...) trong Eloquent
 */

// === Ví dụ 1: Sản phẩm thuộc thương hiệu Apple ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'brand',           // Tên taxonomy
            'field'    => 'slug',            // Tìm theo: 'slug', 'term_id', 'name'
            'terms'    => 'apple',           // Giá trị tìm
        ),
    ),
) );

// === Ví dụ 2: Sản phẩm thuộc nhiều danh mục ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',
            'terms'    => array( 'dien-thoai', 'may-tinh' ),
            'operator' => 'IN',              // Thuộc 1 trong các danh mục này
        ),
    ),
) );

// === Ví dụ 3: Kết hợp nhiều taxonomy ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',                // Phải thỏa mãn TẤT CẢ
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',
            'terms'    => 'dien-thoai',
        ),
        array(
            'taxonomy' => 'brand',
            'field'    => 'slug',
            'terms'    => array( 'apple', 'samsung' ),
            'operator' => 'IN',
        ),
    ),
) );

// === Ví dụ 4: Loại trừ taxonomy ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'brand',
            'field'    => 'slug',
            'terms'    => 'no-name',
            'operator' => 'NOT IN',          // Không thuộc thương hiệu này
        ),
    ),
) );

// === Các operator ===
// 'IN'         : Thuộc ít nhất 1 term (mặc định)
// 'NOT IN'     : Không thuộc bất kỳ term nào
// 'AND'        : Thuộc tất cả các terms
// 'EXISTS'     : Có bất kỳ term nào trong taxonomy này
// 'NOT EXISTS' : Không có term nào trong taxonomy này
```

### Date Query:

```php
<?php
/**
 * date_query - Lọc theo ngày tháng
 * Tương tự ->whereDate(), ->whereBetween() trong Eloquent
 */

// === Ví dụ 1: Bài viết trong 30 ngày gần nhất ===
$recent = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'after'     => '30 days ago',    // Có thể dùng chuỗi tương đối
            'inclusive' => true,
        ),
    ),
) );

// === Ví dụ 2: Bài viết trong tháng 1/2024 ===
$january = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'year'  => 2024,
            'month' => 1,
        ),
    ),
) );

// === Ví dụ 3: Bài viết từ ngày X đến ngày Y ===
$range = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'after'     => 'January 1, 2024',
            'before'    => 'December 31, 2024',
            'inclusive' => true,
        ),
    ),
) );

// === Ví dụ 4: Bài viết vào giờ làm việc (9h-17h) ===
$work_hours = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'hour'    => 9,
            'compare' => '>=',
        ),
        array(
            'hour'    => 17,
            'compare' => '<=',
        ),
        'relation' => 'AND',
    ),
) );

// === Ví dụ 5: Lọc theo ngày chỉnh sửa ===
$modified = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'column' => 'post_modified',     // Mặc định là 'post_date'
            'after'  => '7 days ago',
        ),
    ),
) );
```

---

## 6. Multiple Loops

### Nhiều loops trên cùng một trang:

```php
<?php
/**
 * Multiple Loops - Hiển thị nhiều danh sách bài viết trên 1 trang
 *
 * QUAN TRỌNG:
 * - Mỗi custom WP_Query phải có wp_reset_postdata() sau khi xong
 * - Main Query (The Loop mặc định) không cần reset
 */

get_header();
?>

<main class="site-main">

    <!-- === SECTION 1: Bài viết nổi bật (sticky) === -->
    <section class="featured-posts">
        <h2>Bài Viết Nổi Bật</h2>
        <?php
        $sticky = get_option( 'sticky_posts' ); // Lấy mảng ID bài viết ghim
        if ( $sticky ) :
            $featured = new WP_Query( array(
                'post__in'       => $sticky,
                'posts_per_page' => 3,
                'post_status'    => 'publish',
            ) );

            while ( $featured->have_posts() ) :
                $featured->the_post();
        ?>
                <article class="featured-item">
                    <?php the_post_thumbnail( 'medium_large' ); ?>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                </article>
        <?php
            endwhile;
            wp_reset_postdata(); // RESET!
        endif;
        ?>
    </section>

    <!-- === SECTION 2: Bài viết mới nhất === -->
    <section class="latest-posts">
        <h2>Bài Viết Mới</h2>
        <?php
        $latest = new WP_Query( array(
            'post_type'           => 'post',
            'posts_per_page'      => 6,
            'ignore_sticky_posts' => true,     // Không hiện sticky ở đầu
            'post__not_in'        => $sticky,  // Loại trừ bài đã hiện ở section 1
        ) );

        while ( $latest->have_posts() ) :
            $latest->the_post();
        ?>
            <article>
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
            </article>
        <?php
        endwhile;
        wp_reset_postdata(); // RESET!
        ?>
    </section>

    <!-- === SECTION 3: Bài viết theo từng danh mục === -->
    <?php
    // Lấy 4 danh mục có nhiều bài nhất
    $top_categories = get_categories( array(
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 4,
        'hide_empty' => true,
    ) );

    foreach ( $top_categories as $cat ) :
    ?>
    <section class="category-section">
        <h2>
            <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
                <?php echo esc_html( $cat->name ); ?>
            </a>
        </h2>

        <?php
        $cat_query = new WP_Query( array(
            'post_type'      => 'post',
            'cat'            => $cat->term_id,
            'posts_per_page' => 4,
        ) );

        while ( $cat_query->have_posts() ) :
            $cat_query->the_post();
        ?>
            <article>
                <?php the_post_thumbnail( 'thumbnail' ); ?>
                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                <span><?php echo get_the_date(); ?></span>
            </article>
        <?php
        endwhile;
        wp_reset_postdata(); // RESET sau mỗi loop!
        ?>

        <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="view-all">
            <?php printf( esc_html__( 'Xem tất cả %s &rarr;', 'developer-theme' ), esc_html( $cat->name ) ); ?>
        </a>
    </section>
    <?php endforeach; ?>

    <!-- === SECTION 4: Sản phẩm (Custom Post Type) === -->
    <section class="products-section">
        <h2>Sản Phẩm Mới</h2>
        <?php
        $products = new WP_Query( array(
            'post_type'      => 'product',
            'posts_per_page' => 4,
            'meta_key'       => 'price',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ) );

        while ( $products->have_posts() ) :
            $products->the_post();
            $price = get_post_meta( get_the_ID(), 'price', true );
        ?>
            <div class="product-card">
                <?php the_post_thumbnail( 'developer-square' ); ?>
                <h4><?php the_title(); ?></h4>
                <p class="price"><?php echo number_format( $price, 0, ',', '.' ); ?> VND</p>
            </div>
        <?php
        endwhile;
        wp_reset_postdata(); // RESET!
        ?>
    </section>

    <!-- === SECTION 5: Main Query (bài viết mặc định theo URL) === -->
    <section class="main-posts">
        <h2>Tất Cả Bài Viết</h2>
        <?php
        // Đây là Main Query - không cần new WP_Query
        // WordPress tự động setup dựa trên URL
        if ( have_posts() ) :
            while ( have_posts() ) :
                the_post();
        ?>
                <article>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                </article>
        <?php
            endwhile;
            the_posts_pagination(); // Pagination chỉ cho Main Query
        else :
            get_template_part( 'template-parts/content', 'none' );
        endif;
        // Khong can wp_reset_postdata() cho Main Query
        ?>
    </section>

</main>

<?php get_footer(); ?>
```

---

## 7. Pagination

### Pagination cho Main Query:

```php
<?php
// === Cách 1: the_posts_pagination() (khuyên dùng, WP 4.1+) ===
the_posts_pagination( array(
    'mid_size'           => 2,        // Số trang hiển thị 2 bên trang hiện tại
    'prev_text'          => '&laquo; Trước',
    'next_text'          => 'Sau &raquo;',
    'before_page_number' => '<span class="page-num">',
    'after_page_number'  => '</span>',
    'screen_reader_text' => __( 'Điều hướng bài viết', 'developer-theme' ),
) );
// Tao ra HTML co class: nav-links, page-numbers, current, prev, next

// === Cách 2: paginate_links() (linh hoạt hơn) ===
echo paginate_links( array(
    'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
    'format'    => '?paged=%#%',
    'current'   => max( 1, get_query_var( 'paged' ) ),
    'total'     => $wp_query->max_num_pages,
    'type'      => 'list',            // 'plain', 'array', 'list'
    'prev_text' => '&laquo;',
    'next_text' => '&raquo;',
) );

// === Cách 3: Trước/Sau đơn giản ===
the_posts_navigation( array(
    'prev_text' => __( '&larr; Bài cũ hơn', 'developer-theme' ),
    'next_text' => __( 'Bài mới hơn &rarr;', 'developer-theme' ),
) );

// === Cach 4: previous_posts_link / next_posts_link ===
previous_posts_link( '&laquo; Trang trước' );
next_posts_link( 'Trang sau &raquo;' );
?>
```

### Pagination cho Custom WP_Query:

```php
<?php
/**
 * Pagination cho custom WP_Query
 * Điểm QUAN TRỌNG: Phải lấy đúng số trang hiện tại
 */

// Lấy số trang hiện tại
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

// Custom query
$custom_query = new WP_Query( array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'paged'          => $paged,        // Truyền số trang vào query
    'meta_key'       => 'price',
    'orderby'        => 'meta_value_num',
    'order'          => 'ASC',
) );

// Hiển thị bài viết
if ( $custom_query->have_posts() ) :
    while ( $custom_query->have_posts() ) :
        $custom_query->the_post();
?>
    <article>
        <h3><?php the_title(); ?></h3>
    </article>
<?php
    endwhile;

    // Pagination cho custom query
    // QUAN TRỌNG: Phải truyền $custom_query->max_num_pages
    echo paginate_links( array(
        'base'    => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'format'  => '?paged=%#%',
        'current' => $paged,
        'total'   => $custom_query->max_num_pages,  // Tổng số trang từ custom query
    ) );

    wp_reset_postdata();
endif;
?>
```

### Pagination tùy chỉnh với HTML/CSS:

```php
<?php
/**
 * Custom pagination function
 * Có thể đặt trong functions.php hoặc inc/template-tags.php
 */
function developer_custom_pagination( $query = null ) {
    // Nếu không truyền query, dùng global
    if ( ! $query ) {
        global $wp_query;
        $query = $wp_query;
    }

    $total_pages = $query->max_num_pages;

    if ( $total_pages <= 1 ) {
        return; // Không cần pagination nếu chỉ có 1 trang
    }

    $current_page = max( 1, get_query_var( 'paged' ) );

    echo '<nav class="custom-pagination" aria-label="Phân trang">';
    echo '<ul class="pagination-list">';

    // Nut Previous
    if ( $current_page > 1 ) {
        printf(
            '<li><a href="%s" class="page-link prev">&laquo; Trước</a></li>',
            get_pagenum_link( $current_page - 1 )
        );
    }

    // Trang đầu
    if ( $current_page > 3 ) {
        printf( '<li><a href="%s" class="page-link">1</a></li>', get_pagenum_link( 1 ) );
        if ( $current_page > 4 ) {
            echo '<li><span class="page-dots">...</span></li>';
        }
    }

    // Các trang xung quanh trang hiện tại
    for ( $i = max( 1, $current_page - 2 ); $i <= min( $total_pages, $current_page + 2 ); $i++ ) {
        if ( $i === $current_page ) {
            printf( '<li><span class="page-link current">%d</span></li>', $i );
        } else {
            printf( '<li><a href="%s" class="page-link">%d</a></li>', get_pagenum_link( $i ), $i );
        }
    }

    // Trang cuối
    if ( $current_page < $total_pages - 2 ) {
        if ( $current_page < $total_pages - 3 ) {
            echo '<li><span class="page-dots">...</span></li>';
        }
        printf( '<li><a href="%s" class="page-link">%d</a></li>', get_pagenum_link( $total_pages ), $total_pages );
    }

    // Nut Next
    if ( $current_page < $total_pages ) {
        printf(
            '<li><a href="%s" class="page-link next">Sau &raquo;</a></li>',
            get_pagenum_link( $current_page + 1 )
        );
    }

    echo '</ul>';

    // Thông tin trang
    printf(
        '<p class="page-info">Trang %d / %d</p>',
        $current_page,
        $total_pages
    );

    echo '</nav>';
}

// Sử dụng:
// developer_custom_pagination();            // Cho main query
// developer_custom_pagination( $my_query ); // Cho custom query
```

---

## 8. pre_get_posts Hook

### pre_get_posts là gì?

`pre_get_posts` là hook cho phép bạn **thay đổi Main Query TRƯỚC KHI nó chạy**. Đây là cách **tốt nhất** để thay đổi số bài viết, thứ tự sắp xếp, lọc điều kiện cho Main Query.

```php
<?php
/**
 * QUAN TRỌNG:
 * - Dùng pre_get_posts để modify Main Query
 * - KHÔNG dùng query_posts() (đã lỗi thời và chậm)
 * - KHÔNG tạo new WP_Query để thay thế Main Query
 */

// === Ví dụ 1: Thay đổi số bài viết trên trang archive ===
function developer_modify_posts_per_page( $query ) {
    // Kiểm tra:
    // 1. Không phải trong admin
    // 2. Là main query (không phải custom query)
    if ( ! is_admin() && $query->is_main_query() ) {

        // Trang blog: 12 bài/trang
        if ( $query->is_home() ) {
            $query->set( 'posts_per_page', 12 );
        }

        // Trang tìm kiếm: 20 kết quả/trang
        if ( $query->is_search() ) {
            $query->set( 'posts_per_page', 20 );
        }

        // Archive custom post type: 24 sản phẩm/trang
        if ( $query->is_post_type_archive( 'product' ) ) {
            $query->set( 'posts_per_page', 24 );
        }
    }
}
add_action( 'pre_get_posts', 'developer_modify_posts_per_page' );

// === Ví dụ 2: Thay đổi thứ tự sắp xếp ===
function developer_modify_ordering( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {

        // Sản phẩm: sắp xếp theo giá tăng dần
        if ( $query->is_post_type_archive( 'product' ) ) {
            $query->set( 'meta_key', 'price' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'ASC' );
        }

        // Trang tác giả: sắp xếp theo tiêu đề
        if ( $query->is_author() ) {
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'ASC' );
        }
    }
}
add_action( 'pre_get_posts', 'developer_modify_ordering' );

// === Ví dụ 3: Thêm custom post type vào trang tìm kiếm và archive ===
function developer_include_custom_post_types( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        // Tìm kiếm: bao gồm cả pages và products
        if ( $query->is_search() ) {
            $query->set( 'post_type', array( 'post', 'page', 'product' ) );
        }

        // Tag archive: bao gom ca products
        if ( $query->is_tag() ) {
            $query->set( 'post_type', array( 'post', 'product' ) );
        }
    }
}
add_action( 'pre_get_posts', 'developer_include_custom_post_types' );

// === Ví dụ 4: Loại trừ category khỏi trang blog ===
function developer_exclude_category( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
        // Loại trừ category có ID = 5 (ví dụ: "Không phân loại")
        $query->set( 'category__not_in', array( 5 ) );
    }
}
add_action( 'pre_get_posts', 'developer_exclude_category' );

// === Ví dụ 5: Lọc theo meta field từ URL parameter ===
function developer_filter_by_meta( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'product' ) ) {

        // URL: /san-pham/?min_price=100000&max_price=500000
        $min_price = isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : 0;
        $max_price = isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : 0;

        $meta_query = array();

        if ( $min_price > 0 ) {
            $meta_query[] = array(
                'key'     => 'price',
                'value'   => $min_price,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        if ( $max_price > 0 ) {
            $meta_query[] = array(
                'key'     => 'price',
                'value'   => $max_price,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }

        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $query->set( 'meta_query', $meta_query );
        }
    }
}
add_action( 'pre_get_posts', 'developer_filter_by_meta' );

// === TAI SAO KHONG DUNG query_posts()? ===
// query_posts() GHI DE Main Query, gay ra:
// 1. Chay query 2 lan (cham)
// 2. Pha hong pagination
// 3. Anh huong den conditional tags
// 4. Da bi deprecated (loi thoi)

// SAI:
query_posts( 'posts_per_page=5' );
while ( have_posts() ) : the_post();
    // ...
endwhile;
wp_reset_query();

// DUNG: Dung pre_get_posts hook
// Hoac dung new WP_Query cho secondary queries
```

---

## 9. Code vi du: Trang blog voi nhieu loops

### File: page-templates/template-blog-magazine.php

```php
<?php
/**
 * Template Name: Blog Magazine
 *
 * Trang blog kieu tap chi voi nhieu section khac nhau
 *
 * @package Developer_Theme
 */

get_header();
?>

<main id="primary" class="site-main blog-magazine">

    <!-- ===== HERO: 1 Bai viet noi bat lon ===== -->
    <section class="magazine-hero">
        <div class="container">
            <?php
            $hero = new WP_Query( array(
                'post_type'      => 'post',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'   => 'is_hero',
                        'value' => '1',
                    ),
                ),
            ) );

            // Fallback: Neu khong co bai hero, lay bai moi nhat
            if ( ! $hero->have_posts() ) {
                $hero = new WP_Query( array(
                    'post_type'      => 'post',
                    'posts_per_page' => 1,
                ) );
            }

            if ( $hero->have_posts() ) :
                $hero->the_post();
                $hero_id = get_the_ID(); // Luu ID de loai tru sau
            ?>
                <div class="hero-post" style="background-image: url(<?php echo esc_url( get_the_post_thumbnail_url( null, 'full' ) ); ?>)">
                    <div class="hero-overlay">
                        <div class="hero-content">
                            <?php
                            $categories = get_the_category();
                            if ( $categories ) :
                            ?>
                                <a href="<?php echo esc_url( get_category_link( $categories[0] ) ); ?>" class="hero-category">
                                    <?php echo esc_html( $categories[0]->name ); ?>
                                </a>
                            <?php endif; ?>

                            <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
                            <p class="hero-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 30 ); ?></p>

                            <div class="hero-meta">
                                <?php echo get_avatar( get_the_author_meta( 'ID' ), 32 ); ?>
                                <span><?php the_author(); ?></span>
                                <span>&middot;</span>
                                <time datetime="<?php echo get_the_date( 'c' ); ?>"><?php echo get_the_date(); ?></time>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </section>

    <!-- ===== TRENDING: 4 bai viet nhieu luot xem ===== -->
    <section class="magazine-trending">
        <div class="container">
            <h2 class="section-title">
                <span class="title-icon">&#128293;</span>
                <?php esc_html_e( 'Dang Xu Huong', 'developer-theme' ); ?>
            </h2>

            <?php
            $trending = new WP_Query( array(
                'post_type'      => 'post',
                'posts_per_page' => 4,
                'post__not_in'   => array( $hero_id ),
                'meta_key'       => 'post_views_count',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'date_query'     => array(
                    array( 'after' => '7 days ago' ), // Chi trong 7 ngay qua
                ),
            ) );

            // Fallback neu khong co meta views
            if ( ! $trending->have_posts() ) {
                $trending = new WP_Query( array(
                    'post_type'      => 'post',
                    'posts_per_page' => 4,
                    'post__not_in'   => array( $hero_id ),
                    'orderby'        => 'comment_count',
                    'order'          => 'DESC',
                ) );
            }

            $trending_ids = array( $hero_id ); // Mang ID da hien thi

            if ( $trending->have_posts() ) :
            ?>
                <div class="trending-grid">
                    <?php
                    $counter = 1;
                    while ( $trending->have_posts() ) :
                        $trending->the_post();
                        $trending_ids[] = get_the_ID();
                    ?>
                        <article class="trending-item">
                            <span class="trending-number"><?php echo $counter; ?></span>
                            <div class="trending-content">
                                <span class="trending-category">
                                    <?php
                                    $cat = get_the_category();
                                    echo $cat ? esc_html( $cat[0]->name ) : '';
                                    ?>
                                </span>
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <span class="trending-date"><?php echo get_the_date(); ?></span>
                            </div>
                        </article>
                    <?php
                        $counter++;
                    endwhile;
                    ?>
                </div>
            <?php
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </section>

    <!-- ===== BAI VIET MOI + SIDEBAR ===== -->
    <section class="magazine-latest">
        <div class="container">
            <div class="content-area">

                <div class="main-content">
                    <h2 class="section-title"><?php esc_html_e( 'Bai Viet Moi', 'developer-theme' ); ?></h2>

                    <?php
                    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

                    $latest = new WP_Query( array(
                        'post_type'      => 'post',
                        'posts_per_page' => 10,
                        'paged'          => $paged,
                        'post__not_in'   => $trending_ids,
                    ) );

                    if ( $latest->have_posts() ) :
                        while ( $latest->have_posts() ) :
                            $latest->the_post();
                    ?>
                        <article <?php post_class( 'latest-item' ); ?>>
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="latest-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'developer-thumbnail' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="latest-body">
                                <div class="latest-meta">
                                    <?php
                                    $cat = get_the_category();
                                    if ( $cat ) :
                                    ?>
                                        <a href="<?php echo esc_url( get_category_link( $cat[0] ) ); ?>" class="cat-badge">
                                            <?php echo esc_html( $cat[0]->name ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <time datetime="<?php echo get_the_date( 'c' ); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </div>

                                <h3 class="latest-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <p class="latest-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                                </p>

                                <div class="latest-footer">
                                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 24 ); ?>
                                    <span class="author-name"><?php the_author(); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php
                        endwhile;

                        // Pagination
                        echo '<div class="pagination">';
                        echo paginate_links( array(
                            'base'    => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                            'format'  => '?paged=%#%',
                            'current' => $paged,
                            'total'   => $latest->max_num_pages,
                        ) );
                        echo '</div>';

                        wp_reset_postdata();
                    endif;
                    ?>
                </div>

                <?php get_sidebar(); ?>

            </div>
        </div>
    </section>

    <!-- ===== BAI VIET THEO DANH MUC ===== -->
    <section class="magazine-categories">
        <div class="container">
            <?php
            $featured_cats = array( 'cong-nghe', 'kinh-doanh', 'doi-song' ); // Slug cua cac danh muc

            foreach ( $featured_cats as $cat_slug ) :
                $cat = get_category_by_slug( $cat_slug );
                if ( ! $cat ) continue;

                $cat_posts = new WP_Query( array(
                    'post_type'      => 'post',
                    'category_name'  => $cat_slug,
                    'posts_per_page' => 5,
                ) );

                if ( ! $cat_posts->have_posts() ) continue;
            ?>
                <div class="category-block">
                    <div class="category-header">
                        <h2 class="section-title" style="border-color: <?php echo esc_attr( get_term_meta( $cat->term_id, 'color', true ) ?: '#0073aa' ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </h2>
                        <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="view-all">
                            <?php esc_html_e( 'Xem tat ca', 'developer-theme' ); ?> &rarr;
                        </a>
                    </div>

                    <div class="category-posts">
                        <?php
                        $first = true;
                        while ( $cat_posts->have_posts() ) :
                            $cat_posts->the_post();

                            if ( $first ) :
                                // Bai dau tien: hien thi lon
                        ?>
                                <article class="cat-post-featured">
                                    <?php the_post_thumbnail( 'medium_large' ); ?>
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <p><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                                </article>
                                <div class="cat-post-list">
                        <?php
                                $first = false;
                            else :
                                // Cac bai con lai: hien thi danh sach
                        ?>
                                <article class="cat-post-item">
                                    <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                    <time><?php echo get_the_date(); ?></time>
                                </article>
                        <?php
                            endif;
                        endwhile;
                        ?>
                                </div><!-- .cat-post-list -->
                    </div>
                </div>
            <?php
                wp_reset_postdata();
            endforeach;
            ?>
        </div>
    </section>

</main>

<?php get_footer(); ?>
```

---

## 10. So sanh voi Eloquent trong Laravel

### CRUD Operations

```php
// === LARAVEL ELOQUENT ===
// Create
$post = Post::create(['title' => 'Hello', 'content' => 'World']);

// Read
$post = Post::find(42);
$post = Post::where('slug', 'hello')->first();
$posts = Post::all();

// Update
$post->update(['title' => 'Updated']);

// Delete
$post->delete();

// === WORDPRESS ===
// Create
$post_id = wp_insert_post( array(
    'post_title'   => 'Hello',
    'post_content' => 'World',
    'post_status'  => 'publish',
    'post_type'    => 'post',
) );

// Read
$post = get_post( 42 );
$posts = get_posts( array( 'name' => 'hello' ) );

// Update
wp_update_post( array(
    'ID'         => 42,
    'post_title' => 'Updated',
) );

// Delete
wp_delete_post( 42 );         // Chuyen vao thung rac
wp_delete_post( 42, true );   // Xoa vinh vien
```

### Query phuc tap

```php
// === LARAVEL ELOQUENT ===
$products = Product::query()
    ->where('status', 'active')
    ->where('price', '>', 100000)
    ->where('price', '<', 500000)
    ->whereHas('category', function($q) {
        $q->where('slug', 'dien-thoai');
    })
    ->whereHas('brand', function($q) {
        $q->whereIn('slug', ['apple', 'samsung']);
    })
    ->where('stock', '>', 0)
    ->orderBy('price', 'asc')
    ->paginate(12);

// === WORDPRESS WP_Query ===
$products = new WP_Query( array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => get_query_var( 'paged' ) ?: 1,
    'meta_key'       => 'price',
    'orderby'        => 'meta_value_num',
    'order'          => 'ASC',
    'meta_query'     => array(
        'relation' => 'AND',
        array(
            'key'     => 'price',
            'value'   => array( 100000, 500000 ),
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => 'stock',
            'value'   => 0,
            'compare' => '>',
            'type'    => 'NUMERIC',
        ),
    ),
    'tax_query'      => array(
        'relation' => 'AND',
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',
            'terms'    => 'dien-thoai',
        ),
        array(
            'taxonomy' => 'brand',
            'field'    => 'slug',
            'terms'    => array( 'apple', 'samsung' ),
            'operator' => 'IN',
        ),
    ),
) );
```

### Mapping ham

```
Laravel Eloquent              WordPress
---                           ---
Model::all()                  get_posts() / new WP_Query()
Model::find($id)              get_post($id)
Model::where(...)             WP_Query meta_query / tax_query
Model::orderBy(...)           WP_Query orderby + order
Model::paginate(n)            WP_Query posts_per_page + paged
$model->title                 get_the_title() / the_title()
$model->content               get_the_content() / the_content()
$model->category              get_the_category()
$model->tags                  get_the_tags()
$model->meta_field            get_post_meta($id, 'key', true)
DB::raw(...)                  $wpdb->query(...)
Model::scope(...)             pre_get_posts hook
```

---

## 11. Best Practices

### 1. Luon reset postdata

```php
// Sau MOI custom WP_Query, GOI wp_reset_postdata()
$query = new WP_Query( $args );
while ( $query->have_posts() ) : $query->the_post();
    // ...
endwhile;
wp_reset_postdata(); // BAT BUOC!

// Khong can reset cho Main Query
// Khong can reset cho get_posts() (vi get_posts khong thay doi global $post)
```

### 2. Dung pre_get_posts thay vi query_posts

```php
// SAI: query_posts() - KHONG BAO GIO dung
query_posts( 'cat=5&posts_per_page=10' );

// DUNG: pre_get_posts
add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
        $query->set( 'cat', 5 );
        $query->set( 'posts_per_page', 10 );
    }
} );
```

### 3. Toi uu performance

```php
// Khi chi can IDs (vi du: dem so, hoac truyen cho post__in)
$ids = new WP_Query( array(
    'fields'         => 'ids',           // Chi lay IDs, khong lay toan bo object
    'no_found_rows'  => true,            // Khong chay SQL_CALC_FOUND_ROWS (nhanh hon)
    'posts_per_page' => 100,
) );

// Khi khong can meta hoac terms
$query = new WP_Query( array(
    'update_post_meta_cache' => false,   // Khong preload meta
    'update_post_term_cache' => false,   // Khong preload terms
) );

// Tranh orderby rand tren dataset lon (cham)
// Thay vi:
'orderby' => 'rand', 'posts_per_page' => 5
// Dung:
$all_ids = get_posts( array( 'fields' => 'ids', 'posts_per_page' => -1 ) );
$random_ids = array_rand( array_flip( $all_ids ), 5 );
$random_posts = new WP_Query( array( 'post__in' => $random_ids, 'orderby' => 'post__in' ) );
```

### 4. Escape output

```php
// LUON escape khi echo ra HTML
echo esc_html( get_the_title() );           // Text binh thuong
echo esc_url( get_the_permalink() );         // URL
echo esc_attr( get_the_title() );            // HTML attribute
echo wp_kses_post( get_the_content() );      // HTML noi dung (cho phep 1 so tag)

// Cac ham the_*() da tu escape
// Cac ham get_the_*() can escape khi echo
```

### 5. Dung get_posts() cho query don gian

```php
// get_posts() phu hop cho:
// - Lay nhanh danh sach bai viet
// - Khong can pagination
// - Khong can thay doi global $post

$recent_posts = get_posts( array(
    'numberposts' => 5,              // Luu y: 'numberposts', KHONG phai 'posts_per_page'
    'post_type'   => 'post',
    'post_status' => 'publish',
) );

foreach ( $recent_posts as $post ) :
    // Dung $post->title thay vi the_title()
    echo '<li><a href="' . get_permalink( $post ) . '">' . esc_html( $post->post_title ) . '</a></li>';
endforeach;
// Khong can wp_reset_postdata() vi get_posts() KHONG thay doi global $post
```

---

**Tiep theo:** [04 - Menus, Widgets, Sidebars](./04-menus-widgets-sidebars.md) - Tao menu, widget, va sidebar cho theme
