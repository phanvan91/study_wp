# Custom Post Types va Taxonomies trong WordPress

## Muc luc

1. [Gioi thieu](#1-gioi-thieu)
2. [register_post_type() - Tham so chi tiet](#2-register_post_type---tham-so-chi-tiet)
3. [Vi du tao Custom Post Type](#3-vi-du-tao-custom-post-type)
4. [register_taxonomy() - Tham so chi tiet](#4-register_taxonomy---tham-so-chi-tiet)
5. [Vi du tao Taxonomy](#5-vi-du-tao-taxonomy)
6. [Lien ket CPT voi Taxonomy](#6-lien-ket-cpt-voi-taxonomy)
7. [Template cho CPT](#7-template-cho-cpt)
8. [Query CPT - WP_Query voi post_type](#8-query-cpt---wp_query-voi-post_type)
9. [Meta Boxes cho CPT](#9-meta-boxes-cho-cpt)
10. [Custom Columns trong Admin List](#10-custom-columns-trong-admin-list)
11. [Best Practices](#11-best-practices)

---

## 1. Gioi thieu

### Custom Post Type (CPT) la gi?

WordPress mac dinh co cac post type: `post`, `page`, `attachment`, `revision`, `nav_menu_item`. Custom Post Type cho phep ban tao loai noi dung rieng, phu hop voi du an cu the.

Vi du thuc te:
- Website ban hang: can post type "Product" (San pham)
- Website portfolio: can post type "Portfolio" (Du an)
- Website cong ty: can post type "Team Member" (Thanh vien)
- Website bat dong san: can post type "Property" (Bat dong san)

### Taxonomy la gi?

Taxonomy la cach phan loai noi dung. WordPress mac dinh co:
- `category` (Chuyen muc) - phan cap (hierarchical)
- `post_tag` (The) - khong phan cap (non-hierarchical)

Custom Taxonomy cho phep tao cach phan loai rieng cho CPT. Vi du:
- Product Category (Danh muc san pham)
- Skill (Ky nang)
- Property Type (Loai bat dong san)

### Moi quan he giua CPT va Taxonomy

```
CPT: Product
  |-- Taxonomy: Product Category
  |     |-- Dien tu
  |     |-- Thoi trang
  |     |-- Gia dung
  |
  |-- Taxonomy: Product Tag
        |-- khuyen-mai
        |-- moi
        |-- ban-chay
```

---

## 2. register_post_type() - Tham so chi tiet

### Cu phap co ban

```php
register_post_type( string $post_type, array|string $args = array() );
```

### Toan bo tham so

```php
/**
 * Dang ky Custom Post Type voi day du tham so
 */
function mytheme_register_post_type() {

    // Mang labels - Dinh nghia cac nhan hien thi trong admin
    $labels = array(
        'name'                  => 'San Pham',           // Ten so nhieu
        'singular_name'         => 'San Pham',           // Ten so it
        'menu_name'             => 'San Pham',           // Ten tren menu admin
        'name_admin_bar'        => 'San Pham',           // Ten tren admin bar
        'add_new'               => 'Them Moi',           // Nut them moi
        'add_new_item'          => 'Them San Pham Moi',  // Tieu de trang them moi
        'new_item'              => 'San Pham Moi',
        'edit_item'             => 'Sua San Pham',
        'view_item'             => 'Xem San Pham',
        'view_items'            => 'Xem Tat Ca San Pham',
        'all_items'             => 'Tat Ca San Pham',
        'search_items'          => 'Tim San Pham',
        'parent_item_colon'     => 'San Pham Cha:',
        'not_found'             => 'Khong tim thay san pham.',
        'not_found_in_trash'    => 'Khong co san pham trong thung rac.',
        'featured_image'        => 'Anh San Pham',
        'set_featured_image'    => 'Dat Anh San Pham',
        'remove_featured_image' => 'Xoa Anh San Pham',
        'use_featured_image'    => 'Dung lam Anh San Pham',
        'archives'              => 'Kho San Pham',
        'insert_into_item'      => 'Chen vao san pham',
        'uploaded_to_this_item' => 'Upload vao san pham nay',
        'filter_items_list'     => 'Loc danh sach san pham',
        'items_list_navigation' => 'Dieu huong danh sach san pham',
        'items_list'            => 'Danh sach san pham',
    );

    // Mang args - Toan bo tham so cau hinh
    $args = array(

        // --- LABELS ---
        'labels'              => $labels,
        'description'         => 'Quan ly san pham cua cua hang',

        // --- HIEN THI (Visibility) ---
        'public'              => true,
        // true = hien thi phia truoc (frontend) va phia sau (admin)
        // false = an hoan toan

        'publicly_queryable'  => true,
        // true = co the truy van tu URL phia truoc
        // false = khong the truy cap truc tiep tu URL

        'show_ui'             => true,
        // true = hien thi giao dien quan ly trong admin
        // false = an khoi admin UI

        'show_in_menu'        => true,
        // true = hien thi nhu menu rieng trong admin sidebar
        // false = an khoi menu
        // 'edit.php' = hien thi duoi menu Posts
        // 'tools.php' = hien thi duoi menu Tools
        // 'options-general.php' = duoi Settings

        'show_in_nav_menus'   => true,
        // true = co the them vao navigation menu

        'show_in_admin_bar'   => true,
        // true = hien thi tren admin bar (thanh tren cung)

        'show_in_rest'        => true,
        // true = ho tro REST API va Gutenberg block editor
        // false = su dung Classic Editor

        // --- URL & ARCHIVE ---
        'has_archive'         => true,
        // true = co trang archive (danh sach), URL: /san-pham/
        // false = khong co trang archive
        // 'custom-slug' = dung slug tuy chinh cho archive

        'rewrite'             => array(
            'slug'       => 'san-pham',    // URL slug: /san-pham/ten-san-pham/
            'with_front' => false,          // false = khong them prefix cua permalink
            'pages'      => true,           // Ho tro phan trang
            'feeds'      => true,           // Ho tro RSS feed
        ),

        'query_var'           => true,
        // true = co the query bang ?product=ten-san-pham
        // 'custom_var' = dung query var tuy chinh

        // --- TINH NANG (Features) ---
        'supports'            => array(
            'title',           // Tieu de
            'editor',          // Trinh soan thao noi dung
            'thumbnail',       // Anh dai dien (featured image)
            'excerpt',         // Tom tat
            'author',          // Tac gia
            'comments',        // Binh luan
            'trackbacks',      // Trackbacks
            'custom-fields',   // Custom fields
            'revisions',       // Lich su chinh sua
            'page-attributes', // Thu tu (menu_order), template
        ),

        // --- PHAN CAP ---
        'hierarchical'        => false,
        // false = giong Post (khong co cha-con)
        // true = giong Page (co the co cha-con)

        // --- QUYEN HAN ---
        'capability_type'     => 'post',
        // 'post' = dung quyen giong post
        // 'page' = dung quyen giong page
        // array('product', 'products') = quyen tuy chinh

        'map_meta_cap'        => true,
        // true = tu dong map cac meta capabilities

        // --- MENU ADMIN ---
        'menu_position'       => 5,
        // 5 = duoi Posts
        // 10 = duoi Media
        // 15 = duoi Links
        // 20 = duoi Pages
        // 25 = duoi Comments
        // 60 = duoi menu dau tien
        // 65 = duoi Plugins
        // 70 = duoi Users
        // 75 = duoi Tools
        // 80 = duoi Settings

        'menu_icon'           => 'dashicons-cart',
        // Dashicons: https://developer.wordpress.org/resource/dashicons/
        // Hoac duong dan den icon: get_template_directory_uri() . '/images/icon.png'
        // Hoac base64 SVG: 'data:image/svg+xml;base64,...'

        // --- TAXONOMY ---
        'taxonomies'          => array( 'product_category', 'product_tag' ),
        // Gan taxonomy truc tiep khi dang ky CPT

        // --- KHAC ---
        'can_export'          => true,
        // true = cho phep export bang WordPress Exporter

        'delete_with_user'    => false,
        // false = giu bai viet khi xoa user
        // true = xoa bai viet khi xoa user

        'exclude_from_search' => false,
        // false = xuat hien trong ket qua tim kiem
        // true = an khoi ket qua tim kiem

        'rest_base'           => 'products',
        // Slug cho REST API: /wp-json/wp/v2/products/

        'rest_controller_class' => 'WP_REST_Posts_Controller',
        // Class xu ly REST API

        'template'            => array(),
        // Block template mac dinh cho Gutenberg

        'template_lock'       => false,
        // false = khong khoa
        // 'all' = khoa hoan toan
        // 'insert' = khong cho them block moi
    );

    register_post_type( 'product', $args );
}
add_action( 'init', 'mytheme_register_post_type' );
```

### Luu y quan trong

```php
// SAU KHI dang ky CPT, can flush rewrite rules
// Chi can lam 1 lan (khi activate plugin/theme)
function mytheme_activate() {
    mytheme_register_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mytheme_activate' );

// Khi deactivate
function mytheme_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mytheme_deactivate' );
```

---

## 3. Vi du tao Custom Post Type

### Vi du 1: Product (San pham)

```php
function mytheme_register_product_cpt() {
    $labels = array(
        'name'               => 'San Pham',
        'singular_name'      => 'San Pham',
        'menu_name'          => 'San Pham',
        'add_new'            => 'Them Moi',
        'add_new_item'       => 'Them San Pham Moi',
        'edit_item'          => 'Sua San Pham',
        'new_item'           => 'San Pham Moi',
        'view_item'          => 'Xem San Pham',
        'search_items'       => 'Tim San Pham',
        'not_found'          => 'Khong tim thay san pham',
        'not_found_in_trash' => 'Khong co san pham trong thung rac',
        'all_items'          => 'Tat Ca San Pham',
    );

    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'rewrite'       => array( 'slug' => 'san-pham' ),
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'menu_icon'     => 'dashicons-cart',
        'menu_position' => 5,
        'show_in_rest'  => true,
    );

    register_post_type( 'product', $args );
}
add_action( 'init', 'mytheme_register_product_cpt' );
```

### Vi du 2: Portfolio (Du an)

```php
function mytheme_register_portfolio_cpt() {
    $labels = array(
        'name'               => 'Portfolio',
        'singular_name'      => 'Portfolio',
        'menu_name'          => 'Portfolio',
        'add_new'            => 'Them Du An',
        'add_new_item'       => 'Them Du An Moi',
        'edit_item'          => 'Sua Du An',
        'new_item'           => 'Du An Moi',
        'view_item'          => 'Xem Du An',
        'search_items'       => 'Tim Du An',
        'not_found'          => 'Khong tim thay du an',
        'not_found_in_trash' => 'Khong co du an trong thung rac',
        'all_items'          => 'Tat Ca Du An',
    );

    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => 'du-an',
        'rewrite'       => array( 'slug' => 'du-an', 'with_front' => false ),
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'     => 'dashicons-portfolio',
        'menu_position' => 6,
        'show_in_rest'  => true,
        'hierarchical'  => false,
    );

    register_post_type( 'portfolio', $args );
}
add_action( 'init', 'mytheme_register_portfolio_cpt' );
```

### Vi du 3: Team Member (Thanh vien)

```php
function mytheme_register_team_cpt() {
    $labels = array(
        'name'               => 'Thanh Vien',
        'singular_name'      => 'Thanh Vien',
        'menu_name'          => 'Doi Ngu',
        'add_new'            => 'Them Thanh Vien',
        'add_new_item'       => 'Them Thanh Vien Moi',
        'edit_item'          => 'Sua Thanh Vien',
        'new_item'           => 'Thanh Vien Moi',
        'view_item'          => 'Xem Thanh Vien',
        'search_items'       => 'Tim Thanh Vien',
        'not_found'          => 'Khong tim thay thanh vien',
        'not_found_in_trash' => 'Khong co thanh vien trong thung rac',
        'all_items'          => 'Tat Ca Thanh Vien',
    );

    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => 'doi-ngu',
        'rewrite'       => array( 'slug' => 'thanh-vien', 'with_front' => false ),
        'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'menu_icon'     => 'dashicons-groups',
        'menu_position' => 7,
        'show_in_rest'  => true,
        'hierarchical'  => false,
        // Khong can comments, khong can excerpt
    );

    register_post_type( 'team_member', $args );
}
add_action( 'init', 'mytheme_register_team_cpt' );
```

---

## 4. register_taxonomy() - Tham so chi tiet

### Cu phap co ban

```php
register_taxonomy( string $taxonomy, array|string $object_type, array|string $args = array() );
```

### Toan bo tham so

```php
function mytheme_register_taxonomy() {

    $labels = array(
        'name'                       => 'Danh Muc San Pham',
        'singular_name'              => 'Danh Muc',
        'menu_name'                  => 'Danh Muc',
        'all_items'                  => 'Tat Ca Danh Muc',
        'parent_item'                => 'Danh Muc Cha',
        'parent_item_colon'          => 'Danh Muc Cha:',
        'new_item_name'              => 'Danh Muc Moi',
        'add_new_item'               => 'Them Danh Muc Moi',
        'edit_item'                  => 'Sua Danh Muc',
        'update_item'                => 'Cap Nhat Danh Muc',
        'view_item'                  => 'Xem Danh Muc',
        'separate_items_with_commas' => 'Phan cach bang dau phay',
        'add_or_remove_items'        => 'Them hoac xoa danh muc',
        'choose_from_most_used'      => 'Chon tu danh muc pho bien',
        'popular_items'              => 'Danh Muc Pho Bien',
        'search_items'               => 'Tim Danh Muc',
        'not_found'                  => 'Khong tim thay',
        'no_terms'                   => 'Khong co danh muc',
        'items_list'                 => 'Danh sach danh muc',
        'items_list_navigation'      => 'Dieu huong danh sach danh muc',
        'back_to_items'              => 'Quay lai danh muc',
    );

    $args = array(

        // --- LABELS ---
        'labels'             => $labels,
        'description'        => 'Phan loai san pham theo danh muc',

        // --- PHAN CAP ---
        'hierarchical'       => true,
        // true = giong Category (co cha-con, hien thi dang checkbox)
        // false = giong Tag (khong phan cap, hien thi dang input text)

        // --- HIEN THI ---
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_nav_menus'  => true,
        'show_tagcloud'      => true,
        'show_in_quick_edit' => true,
        'show_admin_column'  => true,
        // true = hien thi cot taxonomy trong danh sach post

        'show_in_rest'       => true,
        // true = ho tro REST API va Gutenberg

        // --- URL ---
        'rewrite'            => array(
            'slug'         => 'danh-muc-san-pham',
            'with_front'   => false,
            'hierarchical' => true,
            // true = URL phan cap: /danh-muc/cha/con/
        ),

        'query_var'          => true,
        // true = co the query: ?product_category=dien-tu

        // --- QUYEN HAN ---
        'capabilities'       => array(
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),

        // --- REST API ---
        'rest_base'          => 'product-categories',
        'rest_controller_class' => 'WP_REST_Terms_Controller',

        // --- KHAC ---
        'sort'               => true,
        // true = ghi nho thu tu cua terms

        'default_term'       => array(
            'name'        => 'Chua phan loai',
            'slug'        => 'chua-phan-loai',
            'description' => 'Danh muc mac dinh cho san pham chua phan loai',
        ),

        // Meta box callback tuy chinh
        // 'meta_box_cb'     => 'my_custom_meta_box',

        // Callback cap nhat so luong
        // 'update_count_callback' => '_update_post_term_count',
    );

    register_taxonomy( 'product_category', array( 'product' ), $args );
}
add_action( 'init', 'mytheme_register_taxonomy' );
```

---

## 5. Vi du tao Taxonomy

### Vi du 1: Product Category (Phan cap - giong Category)

```php
function mytheme_register_product_category() {
    $labels = array(
        'name'              => 'Danh Muc San Pham',
        'singular_name'     => 'Danh Muc',
        'menu_name'         => 'Danh Muc',
        'all_items'         => 'Tat Ca Danh Muc',
        'parent_item'       => 'Danh Muc Cha',
        'parent_item_colon' => 'Danh Muc Cha:',
        'new_item_name'     => 'Danh Muc Moi',
        'add_new_item'      => 'Them Danh Muc Moi',
        'edit_item'         => 'Sua Danh Muc',
        'update_item'       => 'Cap Nhat Danh Muc',
        'search_items'      => 'Tim Danh Muc',
        'not_found'         => 'Khong tim thay',
    );

    $args = array(
        'labels'             => $labels,
        'hierarchical'       => true,  // Phan cap giong Category
        'public'             => true,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'show_in_rest'       => true,
        'rewrite'            => array(
            'slug'         => 'danh-muc-san-pham',
            'with_front'   => false,
            'hierarchical' => true,
        ),
    );

    register_taxonomy( 'product_category', array( 'product' ), $args );
}
add_action( 'init', 'mytheme_register_product_category' );
```

### Vi du 2: Skill (Khong phan cap - giong Tag)

```php
function mytheme_register_skill_taxonomy() {
    $labels = array(
        'name'                       => 'Ky Nang',
        'singular_name'              => 'Ky Nang',
        'menu_name'                  => 'Ky Nang',
        'all_items'                  => 'Tat Ca Ky Nang',
        'new_item_name'              => 'Ky Nang Moi',
        'add_new_item'               => 'Them Ky Nang Moi',
        'edit_item'                  => 'Sua Ky Nang',
        'update_item'                => 'Cap Nhat Ky Nang',
        'search_items'               => 'Tim Ky Nang',
        'not_found'                  => 'Khong tim thay',
        'separate_items_with_commas' => 'Phan cach cac ky nang bang dau phay',
        'choose_from_most_used'      => 'Chon tu ky nang pho bien',
        'popular_items'              => 'Ky Nang Pho Bien',
    );

    $args = array(
        'labels'             => $labels,
        'hierarchical'       => false,  // Khong phan cap, giong Tag
        'public'             => true,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'show_in_rest'       => true,
        'rewrite'            => array( 'slug' => 'ky-nang', 'with_front' => false ),
    );

    register_taxonomy( 'skill', array( 'team_member', 'portfolio' ), $args );
}
add_action( 'init', 'mytheme_register_skill_taxonomy' );
```

### Vi du 3: Product Tag

```php
function mytheme_register_product_tag() {
    $args = array(
        'labels' => array(
            'name'          => 'The San Pham',
            'singular_name' => 'The',
            'menu_name'     => 'The San Pham',
        ),
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'the-san-pham' ),
    );

    register_taxonomy( 'product_tag', array( 'product' ), $args );
}
add_action( 'init', 'mytheme_register_product_tag' );
```

---

## 6. Lien ket CPT voi Taxonomy

### Cach 1: Khai bao truc tiep trong register_post_type()

```php
$args = array(
    // ...
    'taxonomies' => array( 'product_category', 'product_tag' ),
);
register_post_type( 'product', $args );
```

### Cach 2: Su dung register_taxonomy_for_object_type()

```php
// Gan taxonomy co san cho CPT
function mytheme_connect_taxonomy_to_cpt() {
    // Gan category mac dinh cua WordPress cho CPT portfolio
    register_taxonomy_for_object_type( 'category', 'portfolio' );

    // Gan post_tag cho CPT portfolio
    register_taxonomy_for_object_type( 'post_tag', 'portfolio' );
}
add_action( 'init', 'mytheme_connect_taxonomy_to_cpt' );
```

### Cach 3: Khai bao trong register_taxonomy()

```php
// Tham so thu 2 cua register_taxonomy la post type
register_taxonomy( 'skill', array( 'team_member', 'portfolio' ), $args );
// Taxonomy 'skill' duoc gan cho ca 'team_member' va 'portfolio'
```

### Kiem tra lien ket

```php
// Kiem tra taxonomy co duoc gan cho post type khong
if ( is_object_in_taxonomy( 'product', 'product_category' ) ) {
    echo 'Product co taxonomy product_category';
}

// Lay tat ca taxonomy cua mot post type
$taxonomies = get_object_taxonomies( 'product' );
// Ket qua: array( 'product_category', 'product_tag' )

// Lay taxonomy voi thong tin chi tiet
$taxonomies = get_object_taxonomies( 'product', 'objects' );
```

---

## 7. Template cho CPT

### He thong Template Hierarchy cho CPT

```
Single Post:
  single-{post_type}-{slug}.php
  -> single-{post_type}.php
  -> single.php
  -> singular.php
  -> index.php

Vi du: single-product-ao-thun.php -> single-product.php -> single.php

Archive:
  archive-{post_type}.php
  -> archive.php
  -> index.php

Vi du: archive-product.php -> archive.php

Taxonomy Archive:
  taxonomy-{taxonomy}-{term_slug}.php
  -> taxonomy-{taxonomy}.php
  -> taxonomy.php
  -> archive.php
  -> index.php

Vi du: taxonomy-product_category-dien-tu.php -> taxonomy-product_category.php
```

### single-product.php

```php
<?php get_header(); ?>

<main id="primary" class="site-main">
    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>

                <!-- Hien thi taxonomy terms -->
                <?php
                $categories = get_the_terms( get_the_ID(), 'product_category' );
                if ( $categories && ! is_wp_error( $categories ) ) :
                ?>
                    <div class="product-categories">
                        <strong>Danh muc:</strong>
                        <?php
                        $cat_links = array();
                        foreach ( $categories as $cat ) {
                            $cat_links[] = '<a href="' . esc_url( get_term_link( $cat ) ) . '">'
                                         . esc_html( $cat->name ) . '</a>';
                        }
                        echo implode( ', ', $cat_links );
                        ?>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ( has_post_thumbnail() ) : ?>
                <div class="product-thumbnail">
                    <?php the_post_thumbnail( 'large' ); ?>
                </div>
            <?php endif; ?>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>

            <!-- Hien thi custom fields / meta -->
            <?php
            $price = get_post_meta( get_the_ID(), '_product_price', true );
            if ( $price ) :
            ?>
                <div class="product-price">
                    <strong>Gia:</strong> <?php echo number_format( $price, 0, ',', '.' ); ?> VND
                </div>
            <?php endif; ?>

            <!-- Navigation giua cac san pham -->
            <nav class="product-navigation">
                <?php
                previous_post_link( '<div class="prev">%link</div>', 'San pham truoc: %title' );
                next_post_link( '<div class="next">%link</div>', 'San pham tiep: %title' );
                ?>
            </nav>
        </article>

    <?php endwhile; ?>
</main>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
```

### archive-product.php

```php
<?php get_header(); ?>

<main id="primary" class="site-main">
    <header class="page-header">
        <h1 class="page-title">Tat Ca San Pham</h1>

        <!-- Bo loc theo taxonomy -->
        <div class="product-filters">
            <?php
            $categories = get_terms( array(
                'taxonomy'   => 'product_category',
                'hide_empty' => true,
            ) );

            if ( $categories && ! is_wp_error( $categories ) ) :
            ?>
                <ul class="filter-list">
                    <li><a href="<?php echo get_post_type_archive_link( 'product' ); ?>">Tat ca</a></li>
                    <?php foreach ( $categories as $cat ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
                                <?php echo esc_html( $cat->name ); ?>
                                (<?php echo $cat->count; ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="product-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="product-card">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="product-card__image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'medium' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="product-card__content">
                        <h2 class="product-card__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <?php
                        $price = get_post_meta( get_the_ID(), '_product_price', true );
                        if ( $price ) :
                        ?>
                            <div class="product-card__price">
                                <?php echo number_format( $price, 0, ',', '.' ); ?> VND
                            </div>
                        <?php endif; ?>

                        <div class="product-card__excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => 'Truoc',
            'next_text' => 'Sau',
        ) ); ?>

    <?php else : ?>
        <p>Khong co san pham nao.</p>
    <?php endif; ?>
</main>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
```

### taxonomy-product_category.php

```php
<?php get_header(); ?>

<main id="primary" class="site-main">
    <?php
    $term = get_queried_object();
    ?>
    <header class="page-header">
        <h1 class="page-title">Danh muc: <?php echo esc_html( $term->name ); ?></h1>
        <?php if ( $term->description ) : ?>
            <div class="term-description">
                <?php echo wpautop( esc_html( $term->description ) ); ?>
            </div>
        <?php endif; ?>

        <!-- Hien thi danh muc con -->
        <?php
        $children = get_terms( array(
            'taxonomy' => 'product_category',
            'parent'   => $term->term_id,
            'hide_empty' => false,
        ) );

        if ( $children && ! is_wp_error( $children ) ) :
        ?>
            <div class="subcategories">
                <strong>Danh muc con:</strong>
                <ul>
                    <?php foreach ( $children as $child ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_term_link( $child ) ); ?>">
                                <?php echo esc_html( $child->name ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </header>

    <!-- Danh sach san pham giong archive-product.php -->
    <?php if ( have_posts() ) : ?>
        <div class="product-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="product-card">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail( 'medium' ); ?>
                        <h3><?php the_title(); ?></h3>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p>Khong co san pham trong danh muc nay.</p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
```

---

## 8. Query CPT - WP_Query voi post_type

### Query co ban

```php
// Lay tat ca san pham
$products = new WP_Query( array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

if ( $products->have_posts() ) :
    while ( $products->have_posts() ) : $products->the_post();
        echo '<h3>' . get_the_title() . '</h3>';
    endwhile;
    wp_reset_postdata(); // LUON LUON reset sau WP_Query
endif;
```

### Query theo Taxonomy

```php
// Lay san pham theo danh muc
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',            // 'term_id', 'slug', 'name'
            'terms'    => 'dien-tu',
        ),
    ),
) );

// Query nhieu taxonomy (AND)
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',  // 'AND' hoac 'OR'
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',
            'terms'    => 'dien-tu',
        ),
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => array( 'moi', 'khuyen-mai' ),
            'operator' => 'IN',  // 'IN', 'NOT IN', 'AND', 'EXISTS', 'NOT EXISTS'
        ),
    ),
) );
```

### Query theo Meta (Custom Fields)

```php
// Lay san pham co gia duoi 1 trieu
$products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        array(
            'key'     => '_product_price',
            'value'   => 1000000,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        ),
    ),
    'orderby'    => 'meta_value_num',
    'meta_key'   => '_product_price',
    'order'      => 'ASC',
) );

// Ket hop tax_query va meta_query
$products = new WP_Query( array(
    'post_type'  => 'product',
    'tax_query'  => array(
        array(
            'taxonomy' => 'product_category',
            'field'    => 'slug',
            'terms'    => 'dien-tu',
        ),
    ),
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'     => '_product_price',
            'value'   => array( 100000, 5000000 ),
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => '_product_status',
            'value'   => 'in_stock',
            'compare' => '=',
        ),
    ),
) );
```

### Query nhieu Post Types

```php
// Lay ca product va portfolio
$mixed = new WP_Query( array(
    'post_type' => array( 'product', 'portfolio' ),
    'posts_per_page' => 10,
) );
```

### Su dung pre_get_posts de thay doi Main Query

```php
/**
 * Thay doi so bai viet tren trang archive cua CPT
 */
function mytheme_modify_cpt_query( $query ) {
    // Chi thay doi tren frontend, chi main query
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Trang archive cua product: hien thi 12 san pham
    if ( is_post_type_archive( 'product' ) ) {
        $query->set( 'posts_per_page', 12 );
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }

    // Trang taxonomy product_category
    if ( is_tax( 'product_category' ) ) {
        $query->set( 'posts_per_page', 9 );
    }

    // Them CPT vao trang tim kiem
    if ( is_search() ) {
        $query->set( 'post_type', array( 'post', 'page', 'product', 'portfolio' ) );
    }
}
add_action( 'pre_get_posts', 'mytheme_modify_cpt_query' );
```

---

## 9. Meta Boxes cho CPT

### Tao Meta Box

```php
/**
 * Dang ky meta box cho Product
 */
function mytheme_add_product_meta_boxes() {
    add_meta_box(
        'product_details',                    // ID duy nhat
        'Thong Tin San Pham',                 // Tieu de
        'mytheme_product_meta_box_callback',  // Ham render HTML
        'product',                            // Post type (hoac array cua post types)
        'normal',                             // Vi tri: 'normal', 'side', 'advanced'
        'high'                                // Do uu tien: 'high', 'core', 'default', 'low'
    );
}
add_action( 'add_meta_boxes', 'mytheme_add_product_meta_boxes' );

/**
 * Render noi dung meta box
 */
function mytheme_product_meta_box_callback( $post ) {
    // Tao nonce de bao mat
    wp_nonce_field( 'mytheme_save_product_meta', 'mytheme_product_nonce' );

    // Lay gia tri hien tai
    $price     = get_post_meta( $post->ID, '_product_price', true );
    $sku       = get_post_meta( $post->ID, '_product_sku', true );
    $status    = get_post_meta( $post->ID, '_product_status', true );
    $weight    = get_post_meta( $post->ID, '_product_weight', true );
    $color     = get_post_meta( $post->ID, '_product_color', true );
    ?>

    <table class="form-table">
        <tr>
            <th><label for="product_price">Gia (VND)</label></th>
            <td>
                <input type="number" id="product_price" name="product_price"
                       value="<?php echo esc_attr( $price ); ?>"
                       class="regular-text" min="0" step="1000">
            </td>
        </tr>
        <tr>
            <th><label for="product_sku">Ma San Pham (SKU)</label></th>
            <td>
                <input type="text" id="product_sku" name="product_sku"
                       value="<?php echo esc_attr( $sku ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="product_status">Trang Thai</label></th>
            <td>
                <select id="product_status" name="product_status">
                    <option value="in_stock" <?php selected( $status, 'in_stock' ); ?>>
                        Con hang
                    </option>
                    <option value="out_of_stock" <?php selected( $status, 'out_of_stock' ); ?>>
                        Het hang
                    </option>
                    <option value="on_sale" <?php selected( $status, 'on_sale' ); ?>>
                        Dang giam gia
                    </option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="product_weight">Can nang (gram)</label></th>
            <td>
                <input type="number" id="product_weight" name="product_weight"
                       value="<?php echo esc_attr( $weight ); ?>"
                       class="small-text" min="0">
            </td>
        </tr>
        <tr>
            <th><label for="product_color">Mau sac</label></th>
            <td>
                <input type="text" id="product_color" name="product_color"
                       value="<?php echo esc_attr( $color ); ?>"
                       class="regular-text">
                <p class="description">Nhap mau sac san pham, phan cach bang dau phay</p>
            </td>
        </tr>
    </table>

    <?php
}

/**
 * Luu du lieu meta box
 */
function mytheme_save_product_meta( $post_id ) {
    // Kiem tra nonce
    if ( ! isset( $_POST['mytheme_product_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['mytheme_product_nonce'], 'mytheme_save_product_meta' ) ) {
        return;
    }

    // Kiem tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Kiem tra quyen
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Luu tung truong
    $fields = array(
        'product_price'  => '_product_price',
        'product_sku'    => '_product_sku',
        'product_status' => '_product_status',
        'product_weight' => '_product_weight',
        'product_color'  => '_product_color',
    );

    foreach ( $fields as $field_name => $meta_key ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $value = sanitize_text_field( $_POST[ $field_name ] );

            // Sanitize rieng cho tung loai du lieu
            if ( $meta_key === '_product_price' || $meta_key === '_product_weight' ) {
                $value = absint( $value );
            }

            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post_product', 'mytheme_save_product_meta' );
// save_post_{post_type} chi chay cho post type cu the
```

### Meta Box cho Team Member

```php
function mytheme_add_team_meta_boxes() {
    add_meta_box(
        'team_member_info',
        'Thong Tin Thanh Vien',
        'mytheme_team_meta_box_callback',
        'team_member',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'mytheme_add_team_meta_boxes' );

function mytheme_team_meta_box_callback( $post ) {
    wp_nonce_field( 'mytheme_save_team_meta', 'mytheme_team_nonce' );

    $position = get_post_meta( $post->ID, '_team_position', true );
    $email    = get_post_meta( $post->ID, '_team_email', true );
    $phone    = get_post_meta( $post->ID, '_team_phone', true );
    $facebook = get_post_meta( $post->ID, '_team_facebook', true );
    $linkedin = get_post_meta( $post->ID, '_team_linkedin', true );
    ?>

    <table class="form-table">
        <tr>
            <th><label for="team_position">Chuc vu</label></th>
            <td>
                <input type="text" id="team_position" name="team_position"
                       value="<?php echo esc_attr( $position ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="team_email">Email</label></th>
            <td>
                <input type="email" id="team_email" name="team_email"
                       value="<?php echo esc_attr( $email ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="team_phone">So dien thoai</label></th>
            <td>
                <input type="tel" id="team_phone" name="team_phone"
                       value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="team_facebook">Facebook URL</label></th>
            <td>
                <input type="url" id="team_facebook" name="team_facebook"
                       value="<?php echo esc_url( $facebook ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="team_linkedin">LinkedIn URL</label></th>
            <td>
                <input type="url" id="team_linkedin" name="team_linkedin"
                       value="<?php echo esc_url( $linkedin ); ?>" class="regular-text">
            </td>
        </tr>
    </table>

    <?php
}

function mytheme_save_team_meta( $post_id ) {
    if ( ! isset( $_POST['mytheme_team_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['mytheme_team_nonce'], 'mytheme_save_team_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['team_position'] ) ) {
        update_post_meta( $post_id, '_team_position', sanitize_text_field( $_POST['team_position'] ) );
    }
    if ( isset( $_POST['team_email'] ) ) {
        update_post_meta( $post_id, '_team_email', sanitize_email( $_POST['team_email'] ) );
    }
    if ( isset( $_POST['team_phone'] ) ) {
        update_post_meta( $post_id, '_team_phone', sanitize_text_field( $_POST['team_phone'] ) );
    }
    if ( isset( $_POST['team_facebook'] ) ) {
        update_post_meta( $post_id, '_team_facebook', esc_url_raw( $_POST['team_facebook'] ) );
    }
    if ( isset( $_POST['team_linkedin'] ) ) {
        update_post_meta( $post_id, '_team_linkedin', esc_url_raw( $_POST['team_linkedin'] ) );
    }
}
add_action( 'save_post_team_member', 'mytheme_save_team_meta' );
```

---

## 10. Custom Columns trong Admin List

### Them cot tuy chinh cho Product

```php
/**
 * Dinh nghia cac cot hien thi
 */
function mytheme_product_columns( $columns ) {
    // Tao lai mang columns de sap xep thu tu
    $new_columns = array(
        'cb'               => $columns['cb'],              // Checkbox
        'thumbnail'        => 'Anh',                       // Cot anh (tuy chinh)
        'title'            => 'Ten San Pham',
        'product_category' => 'Danh Muc',                  // Taxonomy column
        'price'            => 'Gia',                       // Meta column
        'sku'              => 'SKU',                       // Meta column
        'status'           => 'Trang Thai',                // Meta column
        'date'             => 'Ngay Tao',
    );
    return $new_columns;
}
add_filter( 'manage_product_posts_columns', 'mytheme_product_columns' );

/**
 * Render noi dung cac cot tuy chinh
 */
function mytheme_product_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'thumbnail':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 50, 50 ) );
            } else {
                echo '<span style="color:#999;">Khong co anh</span>';
            }
            break;

        case 'price':
            $price = get_post_meta( $post_id, '_product_price', true );
            if ( $price ) {
                echo number_format( $price, 0, ',', '.' ) . ' VND';
            } else {
                echo '<span style="color:#999;">Chua co gia</span>';
            }
            break;

        case 'sku':
            $sku = get_post_meta( $post_id, '_product_sku', true );
            echo $sku ? esc_html( $sku ) : '<span style="color:#999;">N/A</span>';
            break;

        case 'status':
            $status = get_post_meta( $post_id, '_product_status', true );
            $status_labels = array(
                'in_stock'     => '<span style="color:green;">Con hang</span>',
                'out_of_stock' => '<span style="color:red;">Het hang</span>',
                'on_sale'      => '<span style="color:orange;">Giam gia</span>',
            );
            echo isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : 'N/A';
            break;

        case 'product_category':
            $terms = get_the_terms( $post_id, 'product_category' );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $links = array();
                foreach ( $terms as $term ) {
                    $links[] = '<a href="' . admin_url( 'edit.php?post_type=product&product_category=' . $term->slug ) . '">'
                             . esc_html( $term->name ) . '</a>';
                }
                echo implode( ', ', $links );
            } else {
                echo '<span style="color:#999;">Chua phan loai</span>';
            }
            break;
    }
}
add_action( 'manage_product_posts_custom_column', 'mytheme_product_column_content', 10, 2 );

/**
 * Cho phep sap xep theo cot tuy chinh
 */
function mytheme_product_sortable_columns( $columns ) {
    $columns['price']  = 'price';
    $columns['sku']    = 'sku';
    $columns['status'] = 'status';
    return $columns;
}
add_filter( 'manage_edit-product_sortable_columns', 'mytheme_product_sortable_columns' );

/**
 * Xu ly logic sap xep
 */
function mytheme_product_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $orderby = $query->get( 'orderby' );

    switch ( $orderby ) {
        case 'price':
            $query->set( 'meta_key', '_product_price' );
            $query->set( 'orderby', 'meta_value_num' );
            break;

        case 'sku':
            $query->set( 'meta_key', '_product_sku' );
            $query->set( 'orderby', 'meta_value' );
            break;

        case 'status':
            $query->set( 'meta_key', '_product_status' );
            $query->set( 'orderby', 'meta_value' );
            break;
    }
}
add_action( 'pre_get_posts', 'mytheme_product_orderby' );
```

### Them bo loc (filter) trong admin

```php
/**
 * Them dropdown loc theo taxonomy tren trang danh sach
 */
function mytheme_product_taxonomy_filter() {
    global $typenow;

    if ( $typenow !== 'product' ) {
        return;
    }

    // Loc theo Product Category
    $taxonomy = 'product_category';
    $selected = isset( $_GET[ $taxonomy ] ) ? $_GET[ $taxonomy ] : '';

    wp_dropdown_categories( array(
        'show_option_all' => 'Tat ca danh muc',
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $selected,
        'show_count'      => true,
        'hide_empty'      => true,
        'value_field'     => 'slug',
    ) );

    // Loc theo trang thai
    $status = isset( $_GET['product_status_filter'] ) ? $_GET['product_status_filter'] : '';
    ?>
    <select name="product_status_filter">
        <option value="">Tat ca trang thai</option>
        <option value="in_stock" <?php selected( $status, 'in_stock' ); ?>>Con hang</option>
        <option value="out_of_stock" <?php selected( $status, 'out_of_stock' ); ?>>Het hang</option>
        <option value="on_sale" <?php selected( $status, 'on_sale' ); ?>>Giam gia</option>
    </select>
    <?php
}
add_action( 'restrict_manage_posts', 'mytheme_product_taxonomy_filter' );

/**
 * Xu ly loc theo meta
 */
function mytheme_product_filter_query( $query ) {
    global $pagenow, $typenow;

    if ( $pagenow !== 'edit.php' || $typenow !== 'product' || ! $query->is_main_query() ) {
        return;
    }

    if ( isset( $_GET['product_status_filter'] ) && $_GET['product_status_filter'] !== '' ) {
        $query->set( 'meta_query', array(
            array(
                'key'   => '_product_status',
                'value' => sanitize_text_field( $_GET['product_status_filter'] ),
            ),
        ) );
    }
}
add_action( 'pre_get_posts', 'mytheme_product_filter_query' );
```

---

## 11. Best Practices

### 1. Quy tac dat ten

```php
// Post type: dung so it, snake_case, toi da 20 ky tu
// TOT:
register_post_type( 'product', $args );
register_post_type( 'team_member', $args );
register_post_type( 'portfolio', $args );

// KHONG TOT:
register_post_type( 'products', $args );      // Khong nen dung so nhieu
register_post_type( 'my-product', $args );    // Tranh dung dau gach ngang
register_post_type( 'myPluginProduct', $args ); // Khong dung camelCase

// Taxonomy: them prefix de tranh trung ten
register_taxonomy( 'product_category', ... );  // TOT
register_taxonomy( 'category', ... );          // KHONG TOT - trung voi taxonomy mac dinh

// Meta keys: dung underscore dau de an khoi Custom Fields UI
update_post_meta( $id, '_product_price', $value );  // Co _ dau = an
update_post_meta( $id, 'product_price', $value );   // Khong _ = hien trong Custom Fields
```

### 2. Bao mat

```php
// LUON kiem tra nonce khi luu meta
wp_nonce_field( 'action_name', 'nonce_name' );
wp_verify_nonce( $_POST['nonce_name'], 'action_name' );

// LUON kiem tra quyen
current_user_can( 'edit_post', $post_id );

// LUON sanitize du lieu dau vao
sanitize_text_field( $_POST['field'] );
sanitize_email( $_POST['email'] );
absint( $_POST['number'] );
esc_url_raw( $_POST['url'] );
wp_kses_post( $_POST['html_content'] );

// LUON escape du lieu dau ra
esc_html( $text );
esc_attr( $attribute );
esc_url( $url );
```

### 3. Hieu suat

```php
// Dung save_post_{post_type} thay vi save_post
// De tranh chay voi moi post type
add_action( 'save_post_product', 'mytheme_save_product_meta' );

// Dung pre_get_posts thay vi tao WP_Query moi cho main query
add_action( 'pre_get_posts', 'mytheme_modify_query' );

// LUON goi wp_reset_postdata() sau WP_Query
$query = new WP_Query( $args );
while ( $query->have_posts() ) : $query->the_post();
    // ...
endwhile;
wp_reset_postdata();

// Cache ket qua query nang
$products = get_transient( 'featured_products' );
if ( false === $products ) {
    $query = new WP_Query( array(
        'post_type'      => 'product',
        'meta_key'       => '_featured',
        'meta_value'     => '1',
        'posts_per_page' => 6,
    ) );
    $products = $query->posts;
    set_transient( 'featured_products', $products, HOUR_IN_SECONDS );
}
```

### 4. Flush Rewrite Rules dung cach

```php
// Chi flush khi activate/deactivate plugin
// KHONG BAO GIO goi flush_rewrite_rules() trong init hook

// Trong plugin:
register_activation_hook( __FILE__, function() {
    mytheme_register_post_type();
    flush_rewrite_rules();
} );

// Trong theme:
function mytheme_after_switch() {
    mytheme_register_post_type();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'mytheme_after_switch' );
```

### 5. Su dung Plugin de dang ky CPT

```
Khi dang ky CPT, nen dat code trong plugin thay vi theme.
Ly do: Khi doi theme, CPT van hoat dong.
Noi dung (data) nen doc lap voi giao dien (theme).

Cau truc plugin don gian:
my-custom-post-types/
  |-- my-custom-post-types.php    (File chinh: dang ky CPT + Taxonomy)
  |-- includes/
  |     |-- post-types.php        (Dang ky CPT)
  |     |-- taxonomies.php        (Dang ky Taxonomy)
  |     |-- meta-boxes.php        (Meta boxes)
  |     |-- admin-columns.php     (Custom columns)
```

### 6. Tong hop code hoan chinh trong mot plugin

```php
<?php
/**
 * Plugin Name: My Custom Post Types
 * Description: Dang ky Custom Post Types va Taxonomies
 * Version: 1.0
 * Author: Dev Team
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Custom_Post_Types {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_product', array( $this, 'save_product_meta' ) );
        add_filter( 'manage_product_posts_columns', array( $this, 'product_columns' ) );
        add_action( 'manage_product_posts_custom_column', array( $this, 'product_column_content' ), 10, 2 );
    }

    public function register_post_types() {
        // Product
        register_post_type( 'product', array(
            'labels' => array(
                'name' => 'San Pham',
                'singular_name' => 'San Pham',
            ),
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'san-pham' ),
            'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'menu_icon'    => 'dashicons-cart',
            'show_in_rest' => true,
        ) );
    }

    public function register_taxonomies() {
        register_taxonomy( 'product_category', 'product', array(
            'labels' => array(
                'name' => 'Danh Muc',
                'singular_name' => 'Danh Muc',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'danh-muc' ),
        ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'product_details', 'Thong Tin San Pham',
            array( $this, 'render_product_meta_box' ), 'product', 'normal', 'high' );
    }

    public function render_product_meta_box( $post ) {
        wp_nonce_field( 'save_product', 'product_nonce' );
        $price = get_post_meta( $post->ID, '_product_price', true );
        echo '<label>Gia: </label>';
        echo '<input type="number" name="product_price" value="' . esc_attr( $price ) . '">';
    }

    public function save_product_meta( $post_id ) {
        if ( ! isset( $_POST['product_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['product_nonce'], 'save_product' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        if ( isset( $_POST['product_price'] ) ) {
            update_post_meta( $post_id, '_product_price', absint( $_POST['product_price'] ) );
        }
    }

    public function product_columns( $columns ) {
        $columns['price'] = 'Gia';
        return $columns;
    }

    public function product_column_content( $column, $post_id ) {
        if ( $column === 'price' ) {
            $price = get_post_meta( $post_id, '_product_price', true );
            echo $price ? number_format( $price, 0, ',', '.' ) . ' VND' : 'N/A';
        }
    }
}

new My_Custom_Post_Types();

// Flush rewrite rules khi activate/deactivate
register_activation_hook( __FILE__, function() {
    ( new My_Custom_Post_Types() )->register_post_types();
    ( new My_Custom_Post_Types() )->register_taxonomies();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );
```
