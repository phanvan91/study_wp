# The Loop va WP_Query trong WordPress

## Muc Luc

1. [The Loop la gi](#1-the-loop-la-gi)
2. [Cau truc Loop co ban](#2-cau-truc-loop-co-ban)
3. [Loop Functions chi tiet](#3-loop-functions)
4. [Custom Loop voi WP_Query](#4-custom-loop-voi-wp_query)
5. [Tham so WP_Query chi tiet](#5-tham-so-wp_query)
6. [Multiple Loops tren mot page](#6-multiple-loops)
7. [Pagination](#7-pagination)
8. [pre_get_posts hook](#8-pre_get_posts)
9. [Code vi du: Trang blog voi nhieu loops](#9-code-vi-du)
10. [So sanh voi Eloquent trong Laravel](#10-so-sanh-voi-eloquent)
11. [Best Practices](#11-best-practices)

---

## 1. The Loop la gi

The Loop (Vong Lap) la co che **co ban nhat** cua WordPress de hien thi noi dung. No la mot vong lap PHP duyet qua danh sach bai viet va hien thi tung bai.

### Nguyen ly hoat dong:

```
1. Nguoi dung truy cap URL (vd: /category/tin-tuc/)
2. WordPress tu dong tao 1 query (Main Query) de lay bai viet phu hop
3. The Loop duyet qua ket qua cua query do
4. Moi vong lap, WordPress "setup" bai viet hien tai (global $post)
5. Ban dung cac ham nhu the_title(), the_content() de hien thi
```

### So sanh nhanh voi Laravel:

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

**Diem khac biet:** Trong Laravel, ban truyen `$posts` tu controller. Trong WordPress, The Loop tu dong lay du lieu tu Main Query (dua tren URL).

---

## 2. Cau truc Loop co ban

### Loop toi gian nhat:

```php
<?php
/**
 * Cau truc Loop co ban nhat
 */
if ( have_posts() ) :
    // Co bai viet -> bat dau loop
    while ( have_posts() ) :
        the_post();
        // Hien thi noi dung bai viet o day
        the_title();
        the_content();
    endwhile;
else :
    // Khong co bai viet nao
    echo 'Khong tim thay bai viet.';
endif;
?>
```

### Giai thich chi tiet:

```php
<?php
/**
 * === have_posts() ===
 * - Kiem tra con bai viet nao trong query khong
 * - Tra ve true/false
 * - Tuong tu: $collection->isNotEmpty() trong Laravel
 *
 * === the_post() ===
 * - Chuyen den bai viet tiep theo trong loop
 * - Setup global $post object
 * - Sau khi goi the_post(), tat ca cac ham nhu the_title(), the_content()
 *   se tra ve du lieu cua bai viet HIEN TAI
 * - Tuong tu: $loop->current() trong Laravel
 *
 * === the_title() ===
 * - In ra tieu de bai viet hien tai
 * - the_title() echo ra, get_the_title() tra ve string
 *
 * === the_content() ===
 * - In ra noi dung bai viet hien tai (da qua filter)
 * - Tu dong apply shortcodes, embed, wpautop...
 */
?>
```

### Loop day du voi HTML:

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
                        <?php comments_number( 'Chua co binh luan', '1 binh luan', '% binh luan' ); ?>
                    </span>
                </div>
            </header>

            <!-- Content / Excerpt -->
            <div class="entry-content">
                <?php
                if ( is_singular() ) {
                    // Trang single: hien thi toan bo noi dung
                    the_content( __( 'Doc tiep &rarr;', 'developer-theme' ) );
                } else {
                    // Trang archive: hien thi excerpt (tom tat)
                    the_excerpt();
                }
                ?>
            </div>

            <!-- Footer -->
            <footer class="entry-footer">
                <?php
                // Tags
                the_tags( '<span class="tags">Tags: ', ', ', '</span>' );

                // Edit link (chi hien cho nguoi co quyen)
                edit_post_link( __( 'Chinh sua', 'developer-theme' ), '<span class="edit-link">', '</span>' );
                ?>
            </footer>

        </article>

    <?php endwhile; ?>

    <!-- Pagination -->
    <?php the_posts_pagination(); ?>

<?php else : ?>

    <p><?php esc_html_e( 'Khong co bai viet nao.', 'developer-theme' ); ?></p>

<?php endif; ?>
```

---

## 3. Loop Functions

### Cac ham hien thi thong tin bai viet:

```php
<?php
// === TIEU DE ===
the_title();                          // Echo tieu de
the_title( '<h1>', '</h1>' );         // Boc trong tag
$title = get_the_title();             // Tra ve string (khong echo)
$title = get_the_title( $post_id );   // Lay tieu de theo ID

// === NOI DUNG ===
the_content();                        // Echo toan bo noi dung (da filter)
the_content( 'Doc tiep...' );         // Voi "more" link text
$content = get_the_content();         // Tra ve string (CHUA filter)
$content = apply_filters( 'the_content', get_the_content() ); // Tra ve string (DA filter)

// === TOM TAT (EXCERPT) ===
the_excerpt();                        // Echo excerpt (tu dong tao tu content neu khong co)
$excerpt = get_the_excerpt();         // Tra ve string

// Mac dinh: 55 tu, ket thuc bang "[...]"
// Tuy chinh excerpt length:
function custom_excerpt_length( $length ) {
    return 20; // 20 tu
}
add_filter( 'excerpt_length', 'custom_excerpt_length' );

// Tuy chinh excerpt more text:
function custom_excerpt_more( $more ) {
    return '... <a href="' . get_permalink() . '">Doc them</a>';
}
add_filter( 'excerpt_more', 'custom_excerpt_more' );

// Tuy chinh so tu excerpt ngay trong template:
echo wp_trim_words( get_the_content(), 30, '...' );

// === LINK/URL ===
the_permalink();                      // Echo URL bai viet
$url = get_the_permalink();           // Tra ve URL
$url = get_permalink();               // Giong get_the_permalink()
$url = get_permalink( $post_id );     // URL theo ID

// === FEATURED IMAGE (Anh dai dien) ===
the_post_thumbnail();                 // Echo anh mac dinh
the_post_thumbnail( 'thumbnail' );    // Kich thuoc 150x150
the_post_thumbnail( 'medium' );       // Kich thuoc 300x300
the_post_thumbnail( 'medium_large' ); // Kich thuoc 768px wide
the_post_thumbnail( 'large' );        // Kich thuoc 1024x1024
the_post_thumbnail( 'full' );         // Kich thuoc goc
the_post_thumbnail( 'developer-featured' ); // Kich thuoc tuy chinh
the_post_thumbnail( array( 400, 300 ) );    // Kich thuoc cu the

// Lay URL cua featured image:
$thumbnail_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );

// Kiem tra co featured image khong:
if ( has_post_thumbnail() ) {
    the_post_thumbnail( 'large', array(
        'class' => 'featured-img',
        'alt'   => get_the_title(),
        'loading' => 'lazy',        // Lazy loading
    ) );
}

// === ID ===
the_ID();                             // Echo ID bai viet
$id = get_the_ID();                   // Tra ve ID

// === NGAY THANG ===
the_date();                           // Echo ngay (chi hien 1 lan/ngay)
the_time();                           // Echo gio
echo get_the_date();                  // Luon hien thi (khac the_date!)
echo get_the_date( 'd/m/Y' );        // Dinh dang tuy chinh
echo get_the_date( 'F j, Y' );       // January 15, 2024
echo get_the_modified_date();         // Ngay chinh sua cuoi
echo human_time_diff( get_the_time('U'), current_time('timestamp') ) . ' truoc';
// Output: "2 ngay truoc", "3 gio truoc"

// === TAC GIA ===
the_author();                         // Echo ten tac gia
$author = get_the_author();           // Tra ve ten tac gia
the_author_posts_link();              // Link den trang tac gia
echo get_the_author_meta( 'display_name' );
echo get_the_author_meta( 'description' );  // Bio
echo get_the_author_meta( 'user_email' );
echo get_avatar( get_the_author_meta( 'ID' ), 80 ); // Avatar 80px

// === DANH MUC VA THE ===
the_category( ', ' );                 // Echo danh muc, phan cach bang phay
$categories = get_the_category();     // Mang cac category objects
the_tags( 'Tags: ', ', ', '' );       // Echo tags
$tags = get_the_tags();               // Mang cac tag objects

// Chi lay ten danh muc dau tien:
$cat = get_the_category();
if ( $cat ) {
    echo $cat[0]->name;
}

// Lay tat ca categories voi link:
echo get_the_category_list( ', ' );

// === BINH LUAN ===
comments_number();                    // "No Comments", "1 Comment", "5 Comments"
comments_number( 'Chua co', '1 binh luan', '% binh luan' ); // Tuy chinh
$count = get_comments_number();       // Tra ve so

// === CUSTOM FIELDS (Post Meta) ===
$value = get_post_meta( get_the_ID(), 'meta_key', true );  // Lay 1 gia tri
$values = get_post_meta( get_the_ID(), 'meta_key', false ); // Lay mang gia tri
$all_meta = get_post_meta( get_the_ID() );                  // Lay tat ca meta

// === POST TYPE ===
$type = get_post_type();                    // 'post', 'page', 'product'...
$type_obj = get_post_type_object( $type );  // Object chi tiet

// === POST FORMAT ===
$format = get_post_format();          // 'video', 'gallery', 'quote', false (standard)

// === POST STATUS ===
$status = get_post_status();          // 'publish', 'draft', 'private', 'pending'...

// === CLASSES ===
post_class();                         // Them cac class cho <article>
post_class( 'custom-class' );        // Them class tuy chinh
$classes = get_post_class();          // Tra ve mang classes
?>
```

### Vi du su dung tat ca cac ham:

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
                printf( __( '%d phut doc', 'developer-theme' ), $minutes );
                ?>
            </span>

            <!-- Comments -->
            <span class="comments-count">
                <a href="<?php comments_link(); ?>">
                    <?php comments_number( '0 binh luan', '1 binh luan', '% binh luan' ); ?>
                </a>
            </span>
        </div>

        <!-- Excerpt -->
        <div class="post-excerpt">
            <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
        </div>

        <!-- Read More -->
        <a href="<?php the_permalink(); ?>" class="read-more-link">
            <?php esc_html_e( 'Doc them', 'developer-theme' ); ?> &rarr;
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

## 4. Custom Loop voi WP_Query

### Khi nao can WP_Query?

Main Query (The Loop mac dinh) chi lay bai viet dua tren URL hien tai. Khi ban can:
- Hien thi bai viet o vi tri khac (sidebar, footer)
- Lay bai viet theo dieu kien rieng
- Hien thi nhieu danh sach bai viet tren 1 trang
- Lay bai viet tu Custom Post Type

Ban can tao **Custom Query** voi `WP_Query`.

### Co ban:

```php
<?php
/**
 * WP_Query co ban - Lay 5 bai viet moi nhat
 */
$query = new WP_Query( array(
    'post_type'      => 'post',        // Loai bai viet
    'posts_per_page' => 5,             // So bai viet
    'post_status'    => 'publish',     // Chi lay bai da xuat ban
) );

// Loop qua ket qua
if ( $query->have_posts() ) :
    while ( $query->have_posts() ) :
        $query->the_post();
        // Sau the_post(), cac ham nhu the_title() hoat dong binh thuong
?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php the_excerpt(); ?></p>
<?php
    endwhile;
endif;

// === BAT BUOC: Reset postdata ===
wp_reset_postdata();
// Neu khong reset, cac ham nhu the_title() ben ngoai loop nay
// se tra ve du lieu cua bai viet cuoi cung trong custom query
// thay vi bai viet cua Main Query
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

## 5. Tham so WP_Query chi tiet

### Tong hop tat ca tham so:

```php
<?php
/**
 * WP_Query - Tat ca cac tham so quan trong
 */
$args = array(

    // === LOAI BAI VIET ===
    'post_type'      => 'post',                // string hoac array
    // 'post_type'   => array( 'post', 'page', 'product' ),
    // 'post_type'   => 'any', // Tat ca post types (tru revision va nav_menu_item)

    'post_status'    => 'publish',             // publish, draft, pending, private, trash, any
    // 'post_status' => array( 'publish', 'draft' ),

    // === SO LUONG VA PHAN TRANG ===
    'posts_per_page' => 10,                    // So bai moi trang (-1 = tat ca)
    'offset'         => 5,                     // Bo qua N bai dau tien
    'paged'          => get_query_var('paged') ?: 1, // Trang hien tai (cho pagination)
    'nopaging'       => false,                 // true = lay tat ca, bo qua phan trang

    // === SAP XEP ===
    'orderby'        => 'date',                // Sap xep theo
    // Cac gia tri orderby:
    // 'date'          - Ngay dang
    // 'modified'      - Ngay chinh sua
    // 'title'         - Tieu de (alphabet)
    // 'name'          - Slug
    // 'ID'            - ID bai viet
    // 'author'        - Tac gia
    // 'rand'          - Ngau nhien
    // 'comment_count' - So binh luan
    // 'menu_order'    - Thu tu menu (dung cho Pages)
    // 'meta_value'    - Gia tri meta (can them meta_key)
    // 'meta_value_num' - Gia tri meta dang so
    // 'post__in'      - Theo thu tu cua mang post__in

    'order'          => 'DESC',                // DESC (giam) hoac ASC (tang)

    // Sap xep theo nhieu truong:
    // 'orderby' => array(
    //     'meta_value_num' => 'DESC',
    //     'title'          => 'ASC',
    // ),

    // === LOC THEO ID ===
    'p'              => 42,                    // Lay bai viet co ID = 42
    'post__in'       => array( 1, 2, 3 ),      // Chi lay cac ID nay
    'post__not_in'   => array( 4, 5, 6 ),      // Loai tru cac ID nay

    // === LOC THEO SLUG ===
    'name'           => 'hello-world',         // Lay theo slug
    'pagename'       => 'about',               // Lay page theo slug

    // === LOC THEO PARENT ===
    'post_parent'     => 10,                   // Lay cac trang con cua trang ID=10
    'post_parent__in' => array( 10, 20 ),      // Con cua nhieu trang

    // === LOC THEO TAC GIA ===
    'author'          => 1,                    // ID tac gia
    'author_name'     => 'admin',              // Nicename tac gia
    'author__in'      => array( 1, 2, 3 ),     // Nhieu tac gia
    'author__not_in'  => array( 4, 5 ),        // Loai tru tac gia

    // === LOC THEO DANH MUC ===
    'cat'              => 5,                   // ID danh muc
    'category_name'    => 'tin-tuc',           // Slug danh muc
    'category__in'     => array( 5, 6, 7 ),    // Thuoc IT NHAT 1 danh muc
    'category__not_in' => array( 8, 9 ),       // Khong thuoc cac danh muc nay
    'category__and'    => array( 5, 6 ),       // Thuoc TAT CA cac danh muc nay

    // === LOC THEO THE (TAG) ===
    'tag'          => 'wordpress',             // Slug tag
    'tag_id'       => 10,                      // ID tag
    'tag__in'      => array( 10, 11 ),         // Co IT NHAT 1 tag
    'tag__not_in'  => array( 12, 13 ),         // Khong co cac tag nay
    'tag__and'     => array( 10, 11 ),         // Co TAT CA cac tag nay
    'tag_slug__in' => array( 'wp', 'php' ),    // Theo slug

    // === TIM KIEM ===
    's'              => 'tu khoa tim kiem',    // Tim kiem trong title va content

    // === POST FORMAT ===
    // Dung tax_query (xem ben duoi)

    // === STICKY POSTS ===
    'ignore_sticky_posts' => true,             // Bo qua sticky posts
    // Mac dinh: sticky posts luon hien dau tien

    // === PERFORMANCE ===
    'no_found_rows'          => true,          // Khong dem tong so bai (nhanh hon, nhung mat pagination)
    'update_post_meta_cache' => false,         // Khong cache meta (nhanh hon neu khong can meta)
    'update_post_term_cache' => false,         // Khong cache terms
    'fields'                 => 'ids',         // Chi lay IDs thay vi full objects
    // 'fields' => 'id=>parent',              // Chi lay ID va parent

    // === CACHE ===
    'cache_results'  => true,                  // Cache ket qua (mac dinh true)
);

$query = new WP_Query( $args );
```

### Meta Query (Custom Fields):

```php
<?php
/**
 * meta_query - Loc theo Custom Fields
 * Tuong tu WHERE ... AND meta_key = 'value' trong SQL
 * Tuong tu ->where('meta_key', 'value') trong Eloquent
 */

// === Vi du 1: Lay san pham co gia > 100000 ===
$products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_key'   => 'price',           // Dung ket hop voi orderby meta_value_num
    'orderby'    => 'meta_value_num',  // Sap xep theo gia (dang so)
    'order'      => 'ASC',             // Tu thap den cao
    'meta_query' => array(
        array(
            'key'     => 'price',        // Ten meta key
            'value'   => 100000,         // Gia tri so sanh
            'compare' => '>',            // Phep so sanh
            'type'    => 'NUMERIC',      // Kieu du lieu
        ),
    ),
) );

// === Vi du 2: Nhieu dieu kien (AND) ===
$products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',            // Tat ca dieu kien deu phai dung
        array(
            'key'     => 'price',
            'value'   => array( 100000, 500000 ),
            'compare' => 'BETWEEN',      // Gia tu 100k-500k
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => 'in_stock',
            'value'   => '1',
            'compare' => '=',            // Con hang
        ),
        array(
            'key'     => 'featured',
            'compare' => 'EXISTS',       // Co truong 'featured' (bat ky gia tri nao)
        ),
    ),
) );

// === Vi du 3: Nhieu dieu kien (OR) ===
$posts = new WP_Query( array(
    'post_type'  => 'post',
    'meta_query' => array(
        'relation' => 'OR',             // Chi can 1 dieu kien dung
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

// === Vi du 4: Ket hop AND va OR (nested) ===
$posts = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        // Dieu kien 1: Con hang
        array(
            'key'     => 'in_stock',
            'value'   => '1',
            'compare' => '=',
        ),
        // Dieu kien 2: Mau do HOAC xanh
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

// === Cac phep so sanh (compare) ===
// '='           : Bang
// '!='          : Khong bang
// '>'           : Lon hon
// '>='          : Lon hon hoac bang
// '<'           : Nho hon
// '<='          : Nho hon hoac bang
// 'LIKE'        : Chua ky tu (tuong tu SQL LIKE)
// 'NOT LIKE'    : Khong chua
// 'IN'          : Trong mang (value la array)
// 'NOT IN'      : Khong trong mang
// 'BETWEEN'     : Giua 2 gia tri (value la array 2 phan tu)
// 'NOT BETWEEN' : Khong giua 2 gia tri
// 'EXISTS'      : Ton tai meta key (khong can value)
// 'NOT EXISTS'  : Khong ton tai meta key

// === Cac kieu du lieu (type) ===
// 'CHAR'      : Chuoi ky tu (mac dinh)
// 'NUMERIC'   : So nguyen
// 'DECIMAL'   : So thap phan
// 'DATE'      : Ngay (Y-m-d)
// 'DATETIME'  : Ngay gio (Y-m-d H:i:s)
// 'TIME'      : Gio (H:i:s)
// 'SIGNED'    : So nguyen co dau
// 'UNSIGNED'  : So nguyen khong dau
// 'BINARY'    : Nhi phan
```

### Tax Query (Taxonomy):

```php
<?php
/**
 * tax_query - Loc theo Taxonomy (Category, Tag, Custom Taxonomy)
 * Tuong tu whereHas('category', ...) trong Eloquent
 */

// === Vi du 1: San pham thuoc thuong hieu Apple ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'brand',           // Ten taxonomy
            'field'    => 'slug',            // Tim theo: 'slug', 'term_id', 'name'
            'terms'    => 'apple',           // Gia tri tim
        ),
    ),
) );

// === Vi du 2: San pham thuoc nhieu danh muc ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',
            'terms'    => array( 'dien-thoai', 'may-tinh' ),
            'operator' => 'IN',              // Thuoc 1 trong cac danh muc nay
        ),
    ),
) );

// === Vi du 3: Ket hop nhieu taxonomy ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',                // Phai thoa man TAT CA
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

// === Vi du 4: Loai tru taxonomy ===
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'brand',
            'field'    => 'slug',
            'terms'    => 'no-name',
            'operator' => 'NOT IN',          // Khong thuoc thuong hieu nay
        ),
    ),
) );

// === Cac operator ===
// 'IN'         : Thuoc it nhat 1 term (mac dinh)
// 'NOT IN'     : Khong thuoc bat ky term nao
// 'AND'        : Thuoc tat ca cac terms
// 'EXISTS'     : Co bat ky term nao trong taxonomy nay
// 'NOT EXISTS' : Khong co term nao trong taxonomy nay
```

### Date Query:

```php
<?php
/**
 * date_query - Loc theo ngay thang
 * Tuong tu ->whereDate(), ->whereBetween() trong Eloquent
 */

// === Vi du 1: Bai viet trong 30 ngay gan nhat ===
$recent = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'after'     => '30 days ago',    // Co the dung chuoi tuong doi
            'inclusive' => true,
        ),
    ),
) );

// === Vi du 2: Bai viet trong thang 1/2024 ===
$january = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'year'  => 2024,
            'month' => 1,
        ),
    ),
) );

// === Vi du 3: Bai viet tu ngay X den ngay Y ===
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

// === Vi du 4: Bai viet vao gio lam viec (9h-17h) ===
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

// === Vi du 5: Loc theo ngay chinh sua ===
$modified = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'column' => 'post_modified',     // Mac dinh la 'post_date'
            'after'  => '7 days ago',
        ),
    ),
) );
```

---

## 6. Multiple Loops

### Nhieu loops tren cung mot trang:

```php
<?php
/**
 * Multiple Loops - Hien thi nhieu danh sach bai viet tren 1 trang
 *
 * QUAN TRONG:
 * - Moi custom WP_Query phai co wp_reset_postdata() sau khi xong
 * - Main Query (The Loop mac dinh) khong can reset
 */

get_header();
?>

<main class="site-main">

    <!-- === SECTION 1: Bai viet noi bat (sticky) === -->
    <section class="featured-posts">
        <h2>Bai Viet Noi Bat</h2>
        <?php
        $sticky = get_option( 'sticky_posts' ); // Lay mang ID bai viet ghim
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

    <!-- === SECTION 2: Bai viet moi nhat === -->
    <section class="latest-posts">
        <h2>Bai Viet Moi</h2>
        <?php
        $latest = new WP_Query( array(
            'post_type'           => 'post',
            'posts_per_page'      => 6,
            'ignore_sticky_posts' => true,     // Khong hien sticky o dau
            'post__not_in'        => $sticky,  // Loai tru bai da hien o section 1
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

    <!-- === SECTION 3: Bai viet theo tung danh muc === -->
    <?php
    // Lay 4 danh muc co nhieu bai nhat
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
        wp_reset_postdata(); // RESET sau moi loop!
        ?>

        <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="view-all">
            <?php printf( esc_html__( 'Xem tat ca %s &rarr;', 'developer-theme' ), esc_html( $cat->name ) ); ?>
        </a>
    </section>
    <?php endforeach; ?>

    <!-- === SECTION 4: San pham (Custom Post Type) === -->
    <section class="products-section">
        <h2>San Pham Moi</h2>
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

    <!-- === SECTION 5: Main Query (bai viet mac dinh theo URL) === -->
    <section class="main-posts">
        <h2>Tat Ca Bai Viet</h2>
        <?php
        // Day la Main Query - khong can new WP_Query
        // WordPress tu dong setup dua tren URL
        if ( have_posts() ) :
            while ( have_posts() ) :
                the_post();
        ?>
                <article>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                </article>
        <?php
            endwhile;
            the_posts_pagination(); // Pagination chi cho Main Query
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
// === Cach 1: the_posts_pagination() (khuyen dung, WP 4.1+) ===
the_posts_pagination( array(
    'mid_size'           => 2,        // So trang hien thi 2 ben trang hien tai
    'prev_text'          => '&laquo; Truoc',
    'next_text'          => 'Sau &raquo;',
    'before_page_number' => '<span class="page-num">',
    'after_page_number'  => '</span>',
    'screen_reader_text' => __( 'Dieu huong bai viet', 'developer-theme' ),
) );
// Tao ra HTML co class: nav-links, page-numbers, current, prev, next

// === Cach 2: paginate_links() (linh hoat hon) ===
echo paginate_links( array(
    'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
    'format'    => '?paged=%#%',
    'current'   => max( 1, get_query_var( 'paged' ) ),
    'total'     => $wp_query->max_num_pages,
    'type'      => 'list',            // 'plain', 'array', 'list'
    'prev_text' => '&laquo;',
    'next_text' => '&raquo;',
) );

// === Cach 3: Truoc/Sau don gian ===
the_posts_navigation( array(
    'prev_text' => __( '&larr; Bai cu hon', 'developer-theme' ),
    'next_text' => __( 'Bai moi hon &rarr;', 'developer-theme' ),
) );

// === Cach 4: previous_posts_link / next_posts_link ===
previous_posts_link( '&laquo; Trang truoc' );
next_posts_link( 'Trang sau &raquo;' );
?>
```

### Pagination cho Custom WP_Query:

```php
<?php
/**
 * Pagination cho custom WP_Query
 * Diem QUAN TRONG: Phai lay dung so trang hien tai
 */

// Lay so trang hien tai
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

// Custom query
$custom_query = new WP_Query( array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'paged'          => $paged,        // Truyen so trang vao query
    'meta_key'       => 'price',
    'orderby'        => 'meta_value_num',
    'order'          => 'ASC',
) );

// Hien thi bai viet
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
    // QUAN TRONG: Phai truyen $custom_query->max_num_pages
    echo paginate_links( array(
        'base'    => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'format'  => '?paged=%#%',
        'current' => $paged,
        'total'   => $custom_query->max_num_pages,  // Tong so trang tu custom query
    ) );

    wp_reset_postdata();
endif;
?>
```

### Pagination tuy chinh voi HTML/CSS:

```php
<?php
/**
 * Custom pagination function
 * Co the dat trong functions.php hoac inc/template-tags.php
 */
function developer_custom_pagination( $query = null ) {
    // Neu khong truyen query, dung global
    if ( ! $query ) {
        global $wp_query;
        $query = $wp_query;
    }

    $total_pages = $query->max_num_pages;

    if ( $total_pages <= 1 ) {
        return; // Khong can pagination neu chi co 1 trang
    }

    $current_page = max( 1, get_query_var( 'paged' ) );

    echo '<nav class="custom-pagination" aria-label="Phan trang">';
    echo '<ul class="pagination-list">';

    // Nut Previous
    if ( $current_page > 1 ) {
        printf(
            '<li><a href="%s" class="page-link prev">&laquo; Truoc</a></li>',
            get_pagenum_link( $current_page - 1 )
        );
    }

    // Trang dau
    if ( $current_page > 3 ) {
        printf( '<li><a href="%s" class="page-link">1</a></li>', get_pagenum_link( 1 ) );
        if ( $current_page > 4 ) {
            echo '<li><span class="page-dots">...</span></li>';
        }
    }

    // Cac trang xung quanh trang hien tai
    for ( $i = max( 1, $current_page - 2 ); $i <= min( $total_pages, $current_page + 2 ); $i++ ) {
        if ( $i === $current_page ) {
            printf( '<li><span class="page-link current">%d</span></li>', $i );
        } else {
            printf( '<li><a href="%s" class="page-link">%d</a></li>', get_pagenum_link( $i ), $i );
        }
    }

    // Trang cuoi
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

    // Thong tin trang
    printf(
        '<p class="page-info">Trang %d / %d</p>',
        $current_page,
        $total_pages
    );

    echo '</nav>';
}

// Su dung:
// developer_custom_pagination();            // Cho main query
// developer_custom_pagination( $my_query ); // Cho custom query
```

---

## 8. pre_get_posts Hook

### pre_get_posts la gi?

`pre_get_posts` la hook cho phep ban **thay doi Main Query TRUOC KHI no chay**. Day la cach **tot nhat** de thay doi so bai viet, thu tu sap xep, loc dieu kien cho Main Query.

```php
<?php
/**
 * QUAN TRONG:
 * - Dung pre_get_posts de modify Main Query
 * - KHONG dung query_posts() (da loi thoi va cham)
 * - KHONG tao new WP_Query de thay the Main Query
 */

// === Vi du 1: Thay doi so bai viet tren trang archive ===
function developer_modify_posts_per_page( $query ) {
    // Kiem tra:
    // 1. Khong phai trong admin
    // 2. La main query (khong phai custom query)
    if ( ! is_admin() && $query->is_main_query() ) {

        // Trang blog: 12 bai/trang
        if ( $query->is_home() ) {
            $query->set( 'posts_per_page', 12 );
        }

        // Trang tim kiem: 20 ket qua/trang
        if ( $query->is_search() ) {
            $query->set( 'posts_per_page', 20 );
        }

        // Archive custom post type: 24 san pham/trang
        if ( $query->is_post_type_archive( 'product' ) ) {
            $query->set( 'posts_per_page', 24 );
        }
    }
}
add_action( 'pre_get_posts', 'developer_modify_posts_per_page' );

// === Vi du 2: Thay doi thu tu sap xep ===
function developer_modify_ordering( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {

        // San pham: sap xep theo gia tang dan
        if ( $query->is_post_type_archive( 'product' ) ) {
            $query->set( 'meta_key', 'price' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'ASC' );
        }

        // Trang tac gia: sap xep theo tieu de
        if ( $query->is_author() ) {
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'ASC' );
        }
    }
}
add_action( 'pre_get_posts', 'developer_modify_ordering' );

// === Vi du 3: Them custom post type vao trang tim kiem va archive ===
function developer_include_custom_post_types( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        // Tim kiem: bao gom ca pages va products
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

// === Vi du 4: Loai tru category khoi trang blog ===
function developer_exclude_category( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
        // Loai tru category co ID = 5 (vi du: "Khong phan loai")
        $query->set( 'category__not_in', array( 5 ) );
    }
}
add_action( 'pre_get_posts', 'developer_exclude_category' );

// === Vi du 5: Loc theo meta field tu URL parameter ===
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
