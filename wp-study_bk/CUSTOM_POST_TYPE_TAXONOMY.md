# Custom Post Types và Taxonomies trong WordPress

## Mục lục

1. [Giới thiệu](#1-gioi-thieu)
2. [register_post_type() - Tham số chi tiết](#2-register_post_type---tham-so-chi-tiet)
3. [Ví dụ tạo Custom Post Type](#3-vi-du-tao-custom-post-type)
4. [register_taxonomy() - Tham số chi tiết](#4-register_taxonomy---tham-so-chi-tiet)
5. [Ví dụ tạo Taxonomy](#5-vi-du-tao-taxonomy)
6. [Liên kết CPT với Taxonomy](#6-lien-ket-cpt-voi-taxonomy)
7. [Template cho CPT](#7-template-cho-cpt)
8. [Query CPT - WP_Query với post_type](#8-query-cpt---wp_query-voi-post_type)
9. [Meta Boxes cho CPT](#9-meta-boxes-cho-cpt)
10. [Custom Columns trong Admin List](#10-custom-columns-trong-admin-list)
11. [Best Practices](#11-best-practices)

---

## 1. Giới thiệu

### Custom Post Type (CPT) là gì?

WordPress mặc định có các post type: `post`, `page`, `attachment`, `revision`, `nav_menu_item`. Custom Post Type cho phép bạn tạo loại nội dung riêng, phù hợp với dự án cụ thể.

Ví dụ thực tế:
- Website bán hàng: cần post type "Product" (Sản phẩm)
- Website portfolio: cần post type "Portfolio" (Dự án)
- Website công ty: cần post type "Team Member" (Thành viên)
- Website bất động sản: cần post type "Property" (Bất động sản)

### Taxonomy là gì?

Taxonomy là cách phân loại nội dung. WordPress mặc định có:
- `category` (Chuyên mục) - phân cấp (hierarchical)
- `post_tag` (Thẻ) - không phân cấp (non-hierarchical)

Custom Taxonomy cho phép tạo cách phân loại riêng cho CPT. Ví dụ:
- Product Category (Danh mục sản phẩm)
- Skill (Kỹ năng)
- Property Type (Loại bất động sản)

### Mối quan hệ giữa CPT và Taxonomy

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

## 2. register_post_type() - Tham số chi tiết

### Cú pháp cơ bản

```php
register_post_type( string $post_type, array|string $args = array() );
```

### Toàn bộ tham số

```php
/**
 * Đăng ký Custom Post Type với đầy đủ tham số
 */
function mytheme_register_post_type() {

    // Mảng labels - Định nghĩa các nhãn hiển thị trong admin
    $labels = array(
        'name'                  => 'Sản Phẩm',           // Tên số nhiều
        'singular_name'         => 'Sản Phẩm',           // Tên số ít
        'menu_name'             => 'Sản Phẩm',           // Tên trên menu admin
        'name_admin_bar'        => 'Sản Phẩm',           // Tên trên admin bar
        'add_new'               => 'Thêm Mới',           // Nút thêm mới
        'add_new_item'          => 'Thêm Sản Phẩm Mới',  // Tiêu đề trang thêm mới
        'new_item'              => 'Sản Phẩm Mới',
        'edit_item'             => 'Sửa Sản Phẩm',
        'view_item'             => 'Xem Sản Phẩm',
        'view_items'            => 'Xem Tất Cả Sản Phẩm',
        'all_items'             => 'Tất Cả Sản Phẩm',
        'search_items'          => 'Tìm Sản Phẩm',
        'parent_item_colon'     => 'Sản Phẩm Cha:',
        'not_found'             => 'Không tìm thấy sản phẩm.',
        'not_found_in_trash'    => 'Không có sản phẩm trong thùng rác.',
        'featured_image'        => 'Ảnh Sản Phẩm',
        'set_featured_image'    => 'Đặt Ảnh Sản Phẩm',
        'remove_featured_image' => 'Xóa Ảnh Sản Phẩm',
        'use_featured_image'    => 'Dùng làm Ảnh Sản Phẩm',
        'archives'              => 'Kho Sản Phẩm',
        'insert_into_item'      => 'Chèn vào sản phẩm',
        'uploaded_to_this_item' => 'Upload vào sản phẩm này',
        'filter_items_list'     => 'Lọc danh sách sản phẩm',
        'items_list_navigation' => 'Điều hướng danh sách sản phẩm',
        'items_list'            => 'Danh sách sản phẩm',
    );

    // Mảng args - Toàn bộ tham số cấu hình
    $args = array(

        // --- LABELS ---
        'labels'              => $labels,
        'description'         => 'Quản lý sản phẩm của cửa hàng',

        // --- HIỂN THỊ (Visibility) ---
        'public'              => true,
        // true = hiển thị phía trước (frontend) và phía sau (admin)
        // false = ẩn hoàn toàn

        'publicly_queryable'  => true,
        // true = có thể truy vấn từ URL phía trước
        // false = không thể truy cập trực tiếp từ URL

        'show_ui'             => true,
        // true = hiển thị giao diện quản lý trong admin
        // false = ẩn khỏi admin UI

        'show_in_menu'        => true,
        // true = hiển thị như menu riêng trong admin sidebar
        // false = ẩn khỏi menu
        // 'edit.php' = hiển thị dưới menu Posts
        // 'tools.php' = hiển thị dưới menu Tools
        // 'options-general.php' = dưới Settings

        'show_in_nav_menus'   => true,
        // true = có thể thêm vào navigation menu

        'show_in_admin_bar'   => true,
        // true = hiển thị trên admin bar (thanh trên cùng)

        'show_in_rest'        => true,
        // true = hỗ trợ REST API và Gutenberg block editor
        // false = sử dụng Classic Editor

        // --- URL & ARCHIVE ---
        'has_archive'         => true,
        // true = có trang archive (danh sách), URL: /san-pham/
        // false = không có trang archive
        // 'custom-slug' = dùng slug tùy chỉnh cho archive

        'rewrite'             => array(
            'slug'       => 'san-pham',    // URL slug: /san-pham/ten-san-pham/
            'with_front' => false,          // false = không thêm prefix của permalink
            'pages'      => true,           // Hỗ trợ phân trang
            'feeds'      => true,           // Hỗ trợ RSS feed
        ),

        'query_var'           => true,
        // true = có thể query bằng ?product=ten-san-pham
        // 'custom_var' = dùng query var tùy chỉnh

        // --- TÍNH NĂNG (Features) ---
        'supports'            => array(
            'title',           // Tiêu đề
            'editor',          // Trình soạn thảo nội dung
            'thumbnail',       // Ảnh đại diện (featured image)
            'excerpt',         // Tóm tắt
            'author',          // Tác giả
            'comments',        // Bình luận
            'trackbacks',      // Trackbacks
            'custom-fields',   // Custom fields
            'revisions',       // Lịch sử chỉnh sửa
            'page-attributes', // Thứ tự (menu_order), template
        ),

        // --- PHÂN CẤP ---
        'hierarchical'        => false,
        // false = giống Post (không có cha-con)
        // true = giống Page (có thể có cha-con)

        // --- QUYỀN HẠN ---
        'capability_type'     => 'post',
        // 'post' = dùng quyền giống post
        // 'page' = dùng quyền giống page
        // array('product', 'products') = quyền tùy chỉnh

        'map_meta_cap'        => true,
        // true = tự động map các meta capabilities

        // --- MENU ADMIN ---
        'menu_position'       => 5,
        // 5 = dưới Posts
        // 10 = dưới Media
        // 15 = dưới Links
        // 20 = dưới Pages
        // 25 = dưới Comments
        // 60 = dưới menu đầu tiên
        // 65 = dưới Plugins
        // 70 = dưới Users
        // 75 = dưới Tools
        // 80 = dưới Settings

        'menu_icon'           => 'dashicons-cart',
        // Dashicons: https://developer.wordpress.org/resource/dashicons/
        // Hoặc đường dẫn đến icon: get_template_directory_uri() . '/images/icon.png'
        // Hoặc base64 SVG: 'data:image/svg+xml;base64,...'

        // --- TAXONOMY ---
        'taxonomies'          => array( 'product_category', 'product_tag' ),
        // Gán taxonomy trực tiếp khi đăng ký CPT

        // --- KHÁC ---
        'can_export'          => true,
        // true = cho phép export bằng WordPress Exporter

        'delete_with_user'    => false,
        // false = giữ bài viết khi xóa user
        // true = xóa bài viết khi xóa user

        'exclude_from_search' => false,
        // false = xuất hiện trong kết quả tìm kiếm
        // true = ẩn khỏi kết quả tìm kiếm

        'rest_base'           => 'products',
        // Slug cho REST API: /wp-json/wp/v2/products/

        'rest_controller_class' => 'WP_REST_Posts_Controller',
        // Class xử lý REST API

        'template'            => array(),
        // Block template mặc định cho Gutenberg

        'template_lock'       => false,
        // false = không khóa
        // 'all' = khóa hoàn toàn
        // 'insert' = không cho thêm block mới
    );

    register_post_type( 'product', $args );
}
add_action( 'init', 'mytheme_register_post_type' );
```

### Lưu ý quan trọng

```php
// SAU KHI đăng ký CPT, cần flush rewrite rules
// Chỉ cần làm 1 lần (khi activate plugin/theme)
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

## 3. Ví dụ tạo Custom Post Type

### Ví dụ 1: Product (Sản phẩm)

```php
function mytheme_register_product_cpt() {
    $labels = array(
        'name'               => 'Sản Phẩm',
        'singular_name'      => 'Sản Phẩm',
        'menu_name'          => 'Sản Phẩm',
        'add_new'            => 'Thêm Mới',
        'add_new_item'       => 'Thêm Sản Phẩm Mới',
        'edit_item'          => 'Sửa Sản Phẩm',
        'new_item'           => 'Sản Phẩm Mới',
        'view_item'          => 'Xem Sản Phẩm',
        'search_items'       => 'Tìm Sản Phẩm',
        'not_found'          => 'Không tìm thấy sản phẩm',
        'not_found_in_trash' => 'Không có sản phẩm trong thùng rác',
        'all_items'          => 'Tất Cả Sản Phẩm',
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

### Ví dụ 2: Portfolio (Dự án)

```php
function mytheme_register_portfolio_cpt() {
    $labels = array(
        'name'               => 'Portfolio',
        'singular_name'      => 'Portfolio',
        'menu_name'          => 'Portfolio',
        'add_new'            => 'Thêm Dự Án',
        'add_new_item'       => 'Thêm Dự Án Mới',
        'edit_item'          => 'Sửa Dự Án',
        'new_item'           => 'Dự Án Mới',
        'view_item'          => 'Xem Dự Án',
        'search_items'       => 'Tìm Dự Án',
        'not_found'          => 'Không tìm thấy dự án',
        'not_found_in_trash' => 'Không có dự án trong thùng rác',
        'all_items'          => 'Tất Cả Dự Án',
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

### Ví dụ 3: Team Member (Thành viên)

```php
function mytheme_register_team_cpt() {
    $labels = array(
        'name'               => 'Thành Viên',
        'singular_name'      => 'Thành Viên',
        'menu_name'          => 'Đội Ngũ',
        'add_new'            => 'Thêm Thành Viên',
        'add_new_item'       => 'Thêm Thành Viên Mới',
        'edit_item'          => 'Sửa Thành Viên',
        'new_item'           => 'Thành Viên Mới',
        'view_item'          => 'Xem Thành Viên',
        'search_items'       => 'Tìm Thành Viên',
        'not_found'          => 'Không tìm thấy thành viên',
        'not_found_in_trash' => 'Không có thành viên trong thùng rác',
        'all_items'          => 'Tất Cả Thành Viên',
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
        // Không cần comments, không cần excerpt
    );

    register_post_type( 'team_member', $args );
}
add_action( 'init', 'mytheme_register_team_cpt' );
```

---

## 4. register_taxonomy() - Tham số chi tiết

### Cú pháp cơ bản

```php
register_taxonomy( string $taxonomy, array|string $object_type, array|string $args = array() );
```

### Toàn bộ tham số

```php
function mytheme_register_taxonomy() {

    $labels = array(
        'name'                       => 'Danh Mục Sản Phẩm',
        'singular_name'              => 'Danh Mục',
        'menu_name'                  => 'Danh Mục',
        'all_items'                  => 'Tất Cả Danh Mục',
        'parent_item'                => 'Danh Mục Cha',
        'parent_item_colon'          => 'Danh Mục Cha:',
        'new_item_name'              => 'Danh Mục Mới',
        'add_new_item'               => 'Thêm Danh Mục Mới',
        'edit_item'                  => 'Sửa Danh Mục',
        'update_item'                => 'Cập Nhật Danh Mục',
        'view_item'                  => 'Xem Danh Mục',
        'separate_items_with_commas' => 'Phân cách bằng dấu phẩy',
        'add_or_remove_items'        => 'Thêm hoặc xóa danh mục',
        'choose_from_most_used'      => 'Chọn từ danh mục phổ biến',
        'popular_items'              => 'Danh Mục Phổ Biến',
        'search_items'               => 'Tìm Danh Mục',
        'not_found'                  => 'Không tìm thấy',
        'no_terms'                   => 'Không có danh mục',
        'items_list'                 => 'Danh sách danh mục',
        'items_list_navigation'      => 'Điều hướng danh sách danh mục',
        'back_to_items'              => 'Quay lại danh mục',
    );

    $args = array(

        // --- LABELS ---
        'labels'             => $labels,
        'description'        => 'Phân loại sản phẩm theo danh mục',

        // --- PHÂN CẤP ---
        'hierarchical'       => true,
        // true = giống Category (có cha-con, hiển thị dạng checkbox)
        // false = giống Tag (không phân cấp, hiển thị dạng input text)

        // --- HIỂN THỊ ---
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_nav_menus'  => true,
        'show_tagcloud'      => true,
        'show_in_quick_edit' => true,
        'show_admin_column'  => true,
        // true = hiển thị cột taxonomy trong danh sách post

        'show_in_rest'       => true,
        // true = hỗ trợ REST API và Gutenberg

        // --- URL ---
        'rewrite'            => array(
            'slug'         => 'danh-muc-san-pham',
            'with_front'   => false,
            'hierarchical' => true,
            // true = URL phân cấp: /danh-muc/cha/con/
        ),

        'query_var'          => true,
        // true = có thể query: ?product_category=dien-tu

        // --- QUYỀN HẠN ---
        'capabilities'       => array(
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),

        // --- REST API ---
        'rest_base'          => 'product-categories',
        'rest_controller_class' => 'WP_REST_Terms_Controller',

        // --- KHÁC ---
        'sort'               => true,
        // true = ghi nhớ thứ tự của terms

        'default_term'       => array(
            'name'        => 'Chưa phân loại',
            'slug'        => 'chua-phan-loai',
            'description' => 'Danh mục mặc định cho sản phẩm chưa phân loại',
        ),

        // Meta box callback tùy chỉnh
        // 'meta_box_cb'     => 'my_custom_meta_box',

        // Callback cập nhật số lượng
        // 'update_count_callback' => '_update_post_term_count',
    );

    register_taxonomy( 'product_category', array( 'product' ), $args );
}
add_action( 'init', 'mytheme_register_taxonomy' );
```

---

## 5. Ví dụ tạo Taxonomy

### Ví dụ 1: Product Category (Phân cấp - giống Category)

```php
function mytheme_register_product_category() {
    $labels = array(
        'name'              => 'Danh Mục Sản Phẩm',
        'singular_name'     => 'Danh Mục',
        'menu_name'         => 'Danh Mục',
        'all_items'         => 'Tất Cả Danh Mục',
        'parent_item'       => 'Danh Mục Cha',
        'parent_item_colon' => 'Danh Mục Cha:',
        'new_item_name'     => 'Danh Mục Mới',
        'add_new_item'      => 'Thêm Danh Mục Mới',
        'edit_item'         => 'Sửa Danh Mục',
        'update_item'       => 'Cập Nhật Danh Mục',
        'search_items'      => 'Tìm Danh Mục',
        'not_found'         => 'Không tìm thấy',
    );

    $args = array(
        'labels'             => $labels,
        'hierarchical'       => true,  // Phân cấp giống Category
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

### Ví dụ 2: Skill (Không phân cấp - giống Tag)

```php
function mytheme_register_skill_taxonomy() {
    $labels = array(
        'name'                       => 'Kỹ Năng',
        'singular_name'              => 'Kỹ Năng',
        'menu_name'                  => 'Kỹ Năng',
        'all_items'                  => 'Tất Cả Kỹ Năng',
        'new_item_name'              => 'Kỹ Năng Mới',
        'add_new_item'               => 'Thêm Kỹ Năng Mới',
        'edit_item'                  => 'Sửa Kỹ Năng',
        'update_item'                => 'Cập Nhật Kỹ Năng',
        'search_items'               => 'Tìm Kỹ Năng',
        'not_found'                  => 'Không tìm thấy',
        'separate_items_with_commas' => 'Phân cách các kỹ năng bằng dấu phẩy',
        'choose_from_most_used'      => 'Chọn từ kỹ năng phổ biến',
        'popular_items'              => 'Kỹ Năng Phổ Biến',
    );

    $args = array(
        'labels'             => $labels,
        'hierarchical'       => false,  // Không phân cấp, giống Tag
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

### Ví dụ 3: Product Tag

```php
function mytheme_register_product_tag() {
    $args = array(
        'labels' => array(
            'name'          => 'Thẻ Sản Phẩm',
            'singular_name' => 'Thẻ',
            'menu_name'     => 'Thẻ Sản Phẩm',
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

## 6. Liên kết CPT với Taxonomy

### Cách 1: Khai báo trực tiếp trong register_post_type()

```php
$args = array(
    // ...
    'taxonomies' => array( 'product_category', 'product_tag' ),
);
register_post_type( 'product', $args );
```

### Cách 2: Sử dụng register_taxonomy_for_object_type()

```php
// Gán taxonomy có sẵn cho CPT
function mytheme_connect_taxonomy_to_cpt() {
    // Gán category mặc định của WordPress cho CPT portfolio
    register_taxonomy_for_object_type( 'category', 'portfolio' );

    // Gán post_tag cho CPT portfolio
    register_taxonomy_for_object_type( 'post_tag', 'portfolio' );
}
add_action( 'init', 'mytheme_connect_taxonomy_to_cpt' );
```

### Cách 3: Khai báo trong register_taxonomy()

```php
// Tham số thứ 2 của register_taxonomy là post type
register_taxonomy( 'skill', array( 'team_member', 'portfolio' ), $args );
// Taxonomy 'skill' được gán cho cả 'team_member' và 'portfolio'
```

### Kiểm tra liên kết

```php
// Kiểm tra taxonomy có được gán cho post type không
if ( is_object_in_taxonomy( 'product', 'product_category' ) ) {
    echo 'Product có taxonomy product_category';
}

// Lấy tất cả taxonomy của một post type
$taxonomies = get_object_taxonomies( 'product' );
// Kết quả: array( 'product_category', 'product_tag' )

// Lấy taxonomy với thông tin chi tiết
$taxonomies = get_object_taxonomies( 'product', 'objects' );
```

---

## 7. Template cho CPT

### Hệ thống Template Hierarchy cho CPT

```
Single Post:
  single-{post_type}-{slug}.php
  -> single-{post_type}.php
  -> single.php
  -> singular.php
  -> index.php

Ví dụ: single-product-ao-thun.php -> single-product.php -> single.php

Archive:
  archive-{post_type}.php
  -> archive.php
  -> index.php

Ví dụ: archive-product.php -> archive.php

Taxonomy Archive:
  taxonomy-{taxonomy}-{term_slug}.php
  -> taxonomy-{taxonomy}.php
  -> taxonomy.php
  -> archive.php
  -> index.php

Ví dụ: taxonomy-product_category-dien-tu.php -> taxonomy-product_category.php
```

### single-product.php

```php
<?php get_header(); ?>

<main id="primary" class="site-main">
    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>

                <!-- Hiển thị taxonomy terms -->
                <?php
                $categories = get_the_terms( get_the_ID(), 'product_category' );
                if ( $categories && ! is_wp_error( $categories ) ) :
                ?>
                    <div class="product-categories">
                        <strong>Danh mục:</strong>
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

            <!-- Hiển thị custom fields / meta -->
            <?php
            $price = get_post_meta( get_the_ID(), '_product_price', true );
            if ( $price ) :
            ?>
                <div class="product-price">
                    <strong>Giá:</strong> <?php echo number_format( $price, 0, ',', '.' ); ?> VND
                </div>
            <?php endif; ?>

            <!-- Navigation giữa các sản phẩm -->
            <nav class="product-navigation">
                <?php
                previous_post_link( '<div class="prev">%link</div>', 'Sản phẩm trước: %title' );
                next_post_link( '<div class="next">%link</div>', 'Sản phẩm tiếp: %title' );
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
        <h1 class="page-title">Tất Cả Sản Phẩm</h1>

        <!-- Bộ lọc theo taxonomy -->
        <div class="product-filters">
            <?php
            $categories = get_terms( array(
                'taxonomy'   => 'product_category',
                'hide_empty' => true,
            ) );

            if ( $categories && ! is_wp_error( $categories ) ) :
            ?>
                <ul class="filter-list">
                    <li><a href="<?php echo get_post_type_archive_link( 'product' ); ?>">Tất cả</a></li>
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
            'prev_text' => 'Trước',
            'next_text' => 'Sau',
        ) ); ?>

    <?php else : ?>
        <p>Không có sản phẩm nào.</p>
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
        <h1 class="page-title">Danh mục: <?php echo esc_html( $term->name ); ?></h1>
        <?php if ( $term->description ) : ?>
            <div class="term-description">
                <?php echo wpautop( esc_html( $term->description ) ); ?>
            </div>
        <?php endif; ?>

        <!-- Hiển thị danh mục con -->
        <?php
        $children = get_terms( array(
            'taxonomy' => 'product_category',
            'parent'   => $term->term_id,
            'hide_empty' => false,
        ) );

        if ( $children && ! is_wp_error( $children ) ) :
        ?>
            <div class="subcategories">
                <strong>Danh mục con:</strong>
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

    <!-- Danh sách sản phẩm giống archive-product.php -->
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
        <p>Không có sản phẩm trong danh mục này.</p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
```

---

## 8. Query CPT - WP_Query với post_type

### Query cơ bản

```php
// Lấy tất cả sản phẩm
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
    wp_reset_postdata(); // LUÔN LUÔN reset sau WP_Query
endif;
```

### Query theo Taxonomy

```php
// Lấy sản phẩm theo danh mục
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

// Query nhiều taxonomy (AND)
$products = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',  // 'AND' hoặc 'OR'
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
// Lấy sản phẩm có giá dưới 1 triệu
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

// Kết hợp tax_query và meta_query
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

### Query nhiều Post Types

```php
// Lấy cả product và portfolio
$mixed = new WP_Query( array(
    'post_type' => array( 'product', 'portfolio' ),
    'posts_per_page' => 10,
) );
```

### Sử dụng pre_get_posts để thay đổi Main Query

```php
/**
 * Thay đổi số bài viết trên trang archive của CPT
 */
function mytheme_modify_cpt_query( $query ) {
    // Chỉ thay đổi trên frontend, chỉ main query
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Trang archive của product: hiển thị 12 sản phẩm
    if ( is_post_type_archive( 'product' ) ) {
        $query->set( 'posts_per_page', 12 );
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }

    // Trang taxonomy product_category
    if ( is_tax( 'product_category' ) ) {
        $query->set( 'posts_per_page', 9 );
    }

    // Thêm CPT vào trang tìm kiếm
    if ( is_search() ) {
        $query->set( 'post_type', array( 'post', 'page', 'product', 'portfolio' ) );
    }
}
add_action( 'pre_get_posts', 'mytheme_modify_cpt_query' );
```

---

## 9. Meta Boxes cho CPT

### Tạo Meta Box

```php
/**
 * Đăng ký meta box cho Product
 */
function mytheme_add_product_meta_boxes() {
    add_meta_box(
        'product_details',                    // ID duy nhất
        'Thông Tin Sản Phẩm',                 // Tiêu đề
        'mytheme_product_meta_box_callback',  // Hàm render HTML
        'product',                            // Post type (hoặc array của post types)
        'normal',                             // Vị trí: 'normal', 'side', 'advanced'
        'high'                                // Độ ưu tiên: 'high', 'core', 'default', 'low'
    );
}
add_action( 'add_meta_boxes', 'mytheme_add_product_meta_boxes' );

/**
 * Render nội dung meta box
 */
function mytheme_product_meta_box_callback( $post ) {
    // Tạo nonce để bảo mật
    wp_nonce_field( 'mytheme_save_product_meta', 'mytheme_product_nonce' );

    // Lấy giá trị hiện tại
    $price     = get_post_meta( $post->ID, '_product_price', true );
    $sku       = get_post_meta( $post->ID, '_product_sku', true );
    $status    = get_post_meta( $post->ID, '_product_status', true );
    $weight    = get_post_meta( $post->ID, '_product_weight', true );
    $color     = get_post_meta( $post->ID, '_product_color', true );
    ?>

    <table class="form-table">
        <tr>
            <th><label for="product_price">Giá (VND)</label></th>
            <td>
                <input type="number" id="product_price" name="product_price"
                       value="<?php echo esc_attr( $price ); ?>"
                       class="regular-text" min="0" step="1000">
            </td>
        </tr>
        <tr>
            <th><label for="product_sku">Mã Sản Phẩm (SKU)</label></th>
            <td>
                <input type="text" id="product_sku" name="product_sku"
                       value="<?php echo esc_attr( $sku ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="product_status">Trạng Thái</label></th>
            <td>
                <select id="product_status" name="product_status">
                    <option value="in_stock" <?php selected( $status, 'in_stock' ); ?>>
                        Còn hàng
                    </option>
                    <option value="out_of_stock" <?php selected( $status, 'out_of_stock' ); ?>>
                        Hết hàng
                    </option>
                    <option value="on_sale" <?php selected( $status, 'on_sale' ); ?>>
                        Đang giảm giá
                    </option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="product_weight">Cân nặng (gram)</label></th>
            <td>
                <input type="number" id="product_weight" name="product_weight"
                       value="<?php echo esc_attr( $weight ); ?>"
                       class="small-text" min="0">
            </td>
        </tr>
        <tr>
            <th><label for="product_color">Màu sắc</label></th>
            <td>
                <input type="text" id="product_color" name="product_color"
                       value="<?php echo esc_attr( $color ); ?>"
                       class="regular-text">
                <p class="description">Nhập màu sắc sản phẩm, phân cách bằng dấu phẩy</p>
            </td>
        </tr>
    </table>

    <?php
}

/**
 * Lưu dữ liệu meta box
 */
function mytheme_save_product_meta( $post_id ) {
    // Kiểm tra nonce
    if ( ! isset( $_POST['mytheme_product_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['mytheme_product_nonce'], 'mytheme_save_product_meta' ) ) {
        return;
    }

    // Kiểm tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Lưu từng trường
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

            // Sanitize riêng cho từng loại dữ liệu
            if ( $meta_key === '_product_price' || $meta_key === '_product_weight' ) {
                $value = absint( $value );
            }

            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post_product', 'mytheme_save_product_meta' );
// save_post_{post_type} chỉ chạy cho post type cụ thể
```

### Meta Box cho Team Member

```php
function mytheme_add_team_meta_boxes() {
    add_meta_box(
        'team_member_info',
        'Thông Tin Thành Viên',
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
            <th><label for="team_position">Chức vụ</label></th>
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
            <th><label for="team_phone">Số điện thoại</label></th>
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

### Thêm cột tùy chỉnh cho Product

```php
/**
 * Định nghĩa các cột hiển thị
 */
function mytheme_product_columns( $columns ) {
    // Tạo lại mảng columns để sắp xếp thứ tự
    $new_columns = array(
        'cb'               => $columns['cb'],              // Checkbox
        'thumbnail'        => 'Ảnh',                       // Cột ảnh (tùy chỉnh)
        'title'            => 'Tên Sản Phẩm',
        'product_category' => 'Danh Mục',                  // Taxonomy column
        'price'            => 'Giá',                       // Meta column
        'sku'              => 'SKU',                       // Meta column
        'status'           => 'Trạng Thái',                // Meta column
        'date'             => 'Ngày Tạo',
    );
    return $new_columns;
}
add_filter( 'manage_product_posts_columns', 'mytheme_product_columns' );

/**
 * Render nội dung các cột tùy chỉnh
 */
function mytheme_product_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'thumbnail':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 50, 50 ) );
            } else {
                echo '<span style="color:#999;">Không có ảnh</span>';
            }
            break;

        case 'price':
            $price = get_post_meta( $post_id, '_product_price', true );
            if ( $price ) {
                echo number_format( $price, 0, ',', '.' ) . ' VND';
            } else {
                echo '<span style="color:#999;">Chưa có giá</span>';
            }
            break;

        case 'sku':
            $sku = get_post_meta( $post_id, '_product_sku', true );
            echo $sku ? esc_html( $sku ) : '<span style="color:#999;">N/A</span>';
            break;

        case 'status':
            $status = get_post_meta( $post_id, '_product_status', true );
            $status_labels = array(
                'in_stock'     => '<span style="color:green;">Còn hàng</span>',
                'out_of_stock' => '<span style="color:red;">Hết hàng</span>',
                'on_sale'      => '<span style="color:orange;">Giảm giá</span>',
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
                echo '<span style="color:#999;">Chưa phân loại</span>';
            }
            break;
    }
}
add_action( 'manage_product_posts_custom_column', 'mytheme_product_column_content', 10, 2 );

/**
 * Cho phép sắp xếp theo cột tùy chỉnh
 */
function mytheme_product_sortable_columns( $columns ) {
    $columns['price']  = 'price';
    $columns['sku']    = 'sku';
    $columns['status'] = 'status';
    return $columns;
}
add_filter( 'manage_edit-product_sortable_columns', 'mytheme_product_sortable_columns' );

/**
 * Xử lý logic sắp xếp
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

### Thêm bộ lọc (filter) trong admin

```php
/**
 * Thêm dropdown lọc theo taxonomy trên trang danh sách
 */
function mytheme_product_taxonomy_filter() {
    global $typenow;

    if ( $typenow !== 'product' ) {
        return;
    }

    // Lọc theo Product Category
    $taxonomy = 'product_category';
    $selected = isset( $_GET[ $taxonomy ] ) ? $_GET[ $taxonomy ] : '';

    wp_dropdown_categories( array(
        'show_option_all' => 'Tất cả danh mục',
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $selected,
        'show_count'      => true,
        'hide_empty'      => true,
        'value_field'     => 'slug',
    ) );

    // Lọc theo trạng thái
    $status = isset( $_GET['product_status_filter'] ) ? $_GET['product_status_filter'] : '';
    ?>
    <select name="product_status_filter">
        <option value="">Tất cả trạng thái</option>
        <option value="in_stock" <?php selected( $status, 'in_stock' ); ?>>Còn hàng</option>
        <option value="out_of_stock" <?php selected( $status, 'out_of_stock' ); ?>>Hết hàng</option>
        <option value="on_sale" <?php selected( $status, 'on_sale' ); ?>>Giảm giá</option>
    </select>
    <?php
}
add_action( 'restrict_manage_posts', 'mytheme_product_taxonomy_filter' );

/**
 * Xử lý lọc theo meta
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

### 1. Quy tắc đặt tên

```php
// Post type: dùng số ít, snake_case, tối đa 20 ký tự
// TỐT:
register_post_type( 'product', $args );
register_post_type( 'team_member', $args );
register_post_type( 'portfolio', $args );

// KHÔNG TỐT:
register_post_type( 'products', $args );      // Không nên dùng số nhiều
register_post_type( 'my-product', $args );    // Tránh dùng dấu gạch ngang
register_post_type( 'myPluginProduct', $args ); // Không dùng camelCase

// Taxonomy: thêm prefix để tránh trùng tên
register_taxonomy( 'product_category', ... );  // TỐT
register_taxonomy( 'category', ... );          // KHÔNG TỐT - trùng với taxonomy mặc định

// Meta keys: dùng underscore đầu để ẩn khỏi Custom Fields UI
update_post_meta( $id, '_product_price', $value );  // Có _ đầu = ẩn
update_post_meta( $id, 'product_price', $value );   // Không _ = hiện trong Custom Fields
```

### 2. Bảo mật

```php
// LUÔN kiểm tra nonce khi lưu meta
wp_nonce_field( 'action_name', 'nonce_name' );
wp_verify_nonce( $_POST['nonce_name'], 'action_name' );

// LUÔN kiểm tra quyền
current_user_can( 'edit_post', $post_id );

// LUÔN sanitize dữ liệu đầu vào
sanitize_text_field( $_POST['field'] );
sanitize_email( $_POST['email'] );
absint( $_POST['number'] );
esc_url_raw( $_POST['url'] );
wp_kses_post( $_POST['html_content'] );

// LUÔN escape dữ liệu đầu ra
esc_html( $text );
esc_attr( $attribute );
esc_url( $url );
```

### 3. Hiệu suất

```php
// Dùng save_post_{post_type} thay vì save_post
// Để tránh chạy với mọi post type
add_action( 'save_post_product', 'mytheme_save_product_meta' );

// Dùng pre_get_posts thay vì tạo WP_Query mới cho main query
add_action( 'pre_get_posts', 'mytheme_modify_query' );

// LUÔN gọi wp_reset_postdata() sau WP_Query
$query = new WP_Query( $args );
while ( $query->have_posts() ) : $query->the_post();
    // ...
endwhile;
wp_reset_postdata();

// Cache kết quả query nặng
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

### 4. Flush Rewrite Rules đúng cách

```php
// Chỉ flush khi activate/deactivate plugin
// KHÔNG BAO GIỜ gọi flush_rewrite_rules() trong init hook

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

### 5. Sử dụng Plugin để đăng ký CPT

```
Khi đăng ký CPT, nên đặt code trong plugin thay vì theme.
Lý do: Khi đổi theme, CPT vẫn hoạt động.
Nội dung (data) nên độc lập với giao diện (theme).

Cấu trúc plugin đơn giản:
my-custom-post-types/
  |-- my-custom-post-types.php    (File chính: đăng ký CPT + Taxonomy)
  |-- includes/
  |     |-- post-types.php        (Đăng ký CPT)
  |     |-- taxonomies.php        (Đăng ký Taxonomy)
  |     |-- meta-boxes.php        (Meta boxes)
  |     |-- admin-columns.php     (Custom columns)
```

### 6. Tổng hợp code hoàn chỉnh trong một plugin

```php
<?php
/**
 * Plugin Name: My Custom Post Types
 * Description: Đăng ký Custom Post Types và Taxonomies
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
                'name' => 'Sản Phẩm',
                'singular_name' => 'Sản Phẩm',
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
                'name' => 'Danh Mục',
                'singular_name' => 'Danh Mục',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'danh-muc' ),
        ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'product_details', 'Thông Tin Sản Phẩm',
            array( $this, 'render_product_meta_box' ), 'product', 'normal', 'high' );
    }

    public function render_product_meta_box( $post ) {
        wp_nonce_field( 'save_product', 'product_nonce' );
        $price = get_post_meta( $post->ID, '_product_price', true );
        echo '<label>Giá: </label>';
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
        $columns['price'] = 'Giá';
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
