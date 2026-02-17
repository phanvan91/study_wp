# 03 - Quản Lý Bài Viết, Pages và Editor

> **Source chính**: `wp-admin/edit.php` (518 dòng) + `wp-admin/post.php` (369 dòng)
> **URL danh sách**: `/wp-admin/edit.php`
> **URL tạo mới**: `/wp-admin/post-new.php`
> **URL sửa**: `/wp-admin/post.php?post={id}&action=edit`
> **Laravel tương đương**: Resource Controller (index, create, edit, update, destroy)

---

## Mục Lục

1. [Trang Danh Sách Bài Viết](#1-trang-danh-sách-bài-viết)
2. [WP_List_Table Base Class](#2-wp_list_table-base-class)
3. [Trang Tạo/Sửa Bài Viết](#3-trang-tạosửa-bài-viết)
4. [Post Actions (wp-admin/post.php)](#4-post-actions-wp-adminpostphp)
5. [Meta Boxes](#5-meta-boxes)
6. [Bulk Actions](#6-bulk-actions)
7. [Quick Edit & Inline Edit](#7-quick-edit--inline-edit)
8. [Screen Options cho Posts List](#8-screen-options-cho-posts-list)
9. [Custom Columns](#9-custom-columns)
10. [Admin Filters (Dropdown)](#10-admin-filters-dropdown)
11. [DB: Posts Lưu Gì?](#11-db-posts-lưu-gì)
12. [Hooks Quan Trọng](#12-hooks-quan-trọng)
13. [So Sánh Laravel](#13-so-sánh-laravel)

---

## 1. Trang Danh Sách Bài Viết

### Source files

```
wp-admin/edit.php                                    ← Trang danh sách (518 dòng)
wp-admin/includes/class-wp-posts-list-table.php      ← Class render bảng (2132 dòng)
wp-admin/includes/class-wp-list-table.php            ← Base class (1877 dòng)
```

### URL patterns

```
/wp-admin/edit.php                           → All Posts (post type = 'post')
/wp-admin/edit.php?post_type=page            → All Pages
/wp-admin/edit.php?post_type=product         → Custom post type 'product'
/wp-admin/edit.php?post_status=draft         → Filter: chỉ bài nháp
/wp-admin/edit.php?post_status=trash         → Thùng rác
/wp-admin/edit.php?s=keyword                 → Tìm kiếm
/wp-admin/edit.php?category_name=news        → Filter theo category
/wp-admin/edit.php?author=1                  → Filter theo tác giả
/wp-admin/edit.php?m=202401                  → Filter theo tháng (Jan 2024)
```

### Capability yêu cầu

```php
// Cho post type cụ thể, cần capability edit_posts của post type đó
// Mặc định:
// - 'post'    → edit_posts
// - 'page'    → edit_pages
// - Custom    → tùy theo register_post_type() capabilities

// Kiểm tra trong edit.php:
if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
    wp_die( 'Sorry, you are not allowed to edit posts in this post type.', 403 );
}
```

### Luồng xử lý `edit.php`

```php
// wp-admin/edit.php (trích đoạn quan trọng)

// Bước 1: Bootstrap
require_once __DIR__ . '/admin.php';

// Bước 2: Xác định post type
global $typenow;
// $typenow được set từ $_REQUEST['post_type'] trong admin.php
// Mặc định: 'post'

$post_type        = $typenow;
$post_type_object = get_post_type_object( $post_type );

// Bước 3: Kiểm tra quyền
if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
    wp_die( '...', 403 );
}

// Bước 4: Khởi tạo List Table
$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
$pagenum       = $wp_list_table->get_pagenum();

// Bước 5: Xử lý Bulk Actions (nếu có)
$doaction = $wp_list_table->current_action();
if ( $doaction ) {
    check_admin_referer( 'bulk-posts' );
    // Xử lý: trash, untrash, delete, edit (bulk edit)
    // ...
    // Redirect sau khi xử lý
}

// Bước 6: Prepare items (query posts)
$wp_list_table->prepare_items();

// Bước 7: Render page
require_once ABSPATH . 'wp-admin/admin-header.php';

// Post status tabs (All | Published | Draft | Pending | Trash)
$wp_list_table->views();

// Search box
$wp_list_table->search_box( 'Search Posts', 'post' );

// Table
$wp_list_table->display();

require_once ABSPATH . 'wp-admin/admin-footer.php';
```

### Giao diện danh sách

```
┌──────────────────────────────────────────────────────────────────────┐
│  Posts                                                [Add New Post] │
│                                                                      │
│  All (25) | Published (20) | Draft (3) | Pending (1) | Trash (1)    │
│                                                                      │
│  [Bulk Actions ▼] [Apply]  All dates ▼  All Categories ▼  [Filter]  │
│  ┌─────┬────────────────────┬──────────┬────────────┬──────┬───────┐│
│  │ [ ] │ Title              │ Author   │ Categories │ Tags │ Date  ││
│  ├─────┼────────────────────┼──────────┼────────────┼──────┼───────┤│
│  │ [x] │ Hello World        │ admin    │ Uncateg.   │ —    │ 2024/ ││
│  │     │ Edit|Quick Edit|   │          │            │      │ 01/15 ││
│  │     │ Trash|View         │          │            │      │       ││
│  │ [ ] │ Sample Page        │ admin    │ —          │ —    │ 2024/ ││
│  │     │ Edit|Quick Edit|   │          │            │      │ 01/10 ││
│  │     │ Trash|View         │          │            │      │       ││
│  └─────┴────────────────────┴──────────┴────────────┴──────┴───────┘│
│                                                                      │
│  [Bulk Actions ▼] [Apply]               1 of 2  [<] [1] [2] [>]     │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 2. WP_List_Table Base Class

### Source

**Source**: `wp-admin/includes/class-wp-list-table.php` - 1877 dòng

`WP_List_Table` là base class cho tất cả bảng danh sách trong WordPress admin. Nó cung cấp:
- Pagination
- Sorting
- Search
- Bulk actions
- Column management
- Row actions

### Cấu trúc class

```php
// wp-admin/includes/class-wp-list-table.php
class WP_List_Table {
    // Properties
    public $items;           // Array dữ liệu hiện tại
    protected $screen;       // WP_Screen object

    // Pagination
    protected $_pagination_args = array();
    protected $_pagination;

    // Methods quan trọng
    public function prepare_items() {}       // Query dữ liệu - PHẢI override
    public function get_columns() {}         // Định nghĩa cột - PHẢI override
    public function get_sortable_columns() {} // Cột có thể sort
    public function column_default($item, $column_name) {} // Render cột mặc định
    public function column_cb($item) {}      // Checkbox column
    public function get_bulk_actions() {}    // Bulk action options
    public function display() {}             // Render toàn bộ table
    public function display_rows() {}        // Render rows
    public function single_row($item) {}     // Render 1 row
    public function search_box($text, $input_id) {} // Search form
    public function views() {}               // Status tabs
    public function pagination($which) {}    // Pagination controls
    public function get_pagenum() {}         // Trang hiện tại
    public function current_action() {}      // Action đang thực hiện
}
```

### WP_Posts_List_Table (extends WP_List_Table)

**Source**: `wp-admin/includes/class-wp-posts-list-table.php` - 2132 dòng

```php
class WP_Posts_List_Table extends WP_List_Table {

    // Override prepare_items() - query posts
    public function prepare_items() {
        global $mode, $avail_post_stati, $wp_query, $per_page;

        // Số items per page
        $per_page = $this->get_items_per_page(
            'edit_' . $this->screen->post_type . '_per_page'
        );

        // Build query args
        $args = array(
            'post_type'      => $this->screen->post_type,
            'posts_per_page' => $per_page,
            'paged'          => $this->get_pagenum(),
            'post_status'    => $post_status,
            'orderby'        => $orderby,
            'order'          => $order,
            // + search, category, month filters
        );

        // Chạy WP_Query
        $wp_query = new WP_Query( $args );

        $this->items = $wp_query->posts;
        $total_items = $wp_query->found_posts;

        // Set pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ));
    }

    // Cột mặc định
    public function get_columns() {
        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'title'      => _x( 'Title', 'column name' ),
            'author'     => __( 'Author' ),
            'categories' => __( 'Categories' ),  // Chỉ cho post type 'post'
            'tags'       => __( 'Tags' ),         // Chỉ cho post type 'post'
            'comments'   => '...',                 // Icon comment
            'date'       => __( 'Date' ),
        );

        // Filter cho plugins/themes thêm/xóa cột
        return apply_filters(
            "manage_{$this->screen->post_type}_posts_columns",
            $columns
        );
    }

    // Row actions (Edit, Quick Edit, Trash, View)
    protected function handle_row_actions( $post, $column_name, $primary ) {
        $actions = array();
        $actions['edit']       = '<a href="' . get_edit_post_link($post->ID) . '">Edit</a>';
        $actions['inline hide-if-no-js'] = '<button type="button" class="button-link editinline">Quick Edit</button>';
        $actions['trash']      = '<a href="' . get_delete_post_link($post->ID) . '">Trash</a>';
        $actions['view']       = '<a href="' . get_permalink($post->ID) . '">View</a>';

        return $this->row_actions( $actions );
    }
}
```

### Tạo Custom List Table

```php
// Tạo list table riêng cho plugin
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class My_Products_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'product',
            'plural'   => 'products',
            'ajax'     => false,
        ));
    }

    // Query dữ liệu
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => $_GET['orderby'] ?? 'date',
            'order'          => $_GET['order'] ?? 'DESC',
        );

        // Tìm kiếm
        if (!empty($_GET['s'])) {
            $args['s'] = sanitize_text_field($_GET['s']);
        }

        $query = new WP_Query($args);

        $this->items = $query->posts;

        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
        ));
    }

    // Định nghĩa cột
    public function get_columns() {
        return array(
            'cb'      => '<input type="checkbox" />',
            'title'   => 'Tên Sản Phẩm',
            'price'   => 'Giá',
            'stock'   => 'Tồn Kho',
            'date'    => 'Ngày Tạo',
        );
    }

    // Cột sortable
    public function get_sortable_columns() {
        return array(
            'title' => array('title', false),
            'date'  => array('date', true), // true = default sort desc
            'price' => array('price', false),
        );
    }

    // Render cột mặc định
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'price':
                $price = get_post_meta($item->ID, '_price', true);
                return number_format((float)$price, 0, ',', '.') . ' VNĐ';
            case 'stock':
                return get_post_meta($item->ID, '_stock', true) ?: '0';
            default:
                return '';
        }
    }

    // Render cột title
    public function column_title($item) {
        $edit_link = get_edit_post_link($item->ID);
        $actions = array(
            'edit'  => sprintf('<a href="%s">Sửa</a>', $edit_link),
            'trash' => sprintf('<a href="%s">Xóa</a>', get_delete_post_link($item->ID)),
            'view'  => sprintf('<a href="%s" target="_blank">Xem</a>', get_permalink($item->ID)),
        );

        return sprintf('<strong><a href="%s">%s</a></strong>%s',
            $edit_link,
            esc_html($item->post_title),
            $this->row_actions($actions)
        );
    }

    // Checkbox column
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="post[]" value="%s" />', $item->ID);
    }

    // Bulk actions
    public function get_bulk_actions() {
        return array(
            'trash'  => 'Chuyển vào thùng rác',
            'delete' => 'Xóa vĩnh viễn',
        );
    }
}

// Sử dụng trong admin page
add_action('admin_menu', function() {
    add_menu_page('Sản Phẩm', 'Sản Phẩm', 'manage_options', 'my-products', function() {
        $list_table = new My_Products_List_Table();
        $list_table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Sản Phẩm</h1>';
        echo '<a href="' . admin_url('post-new.php?post_type=product') . '" class="page-title-action">Thêm Mới</a>';
        echo '<hr class="wp-header-end">';

        // Search box
        $list_table->search_box('Tìm sản phẩm', 'product');

        // Table
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="my-products">';
        $list_table->display();
        echo '</form>';

        echo '</div>';
    });
});
```

---

## 3. Trang Tạo/Sửa Bài Viết

### URLs

```
/wp-admin/post-new.php                    → Tạo post mới (type: post)
/wp-admin/post-new.php?post_type=page     → Tạo page mới
/wp-admin/post-new.php?post_type=product  → Tạo custom post type
/wp-admin/post.php?post=123&action=edit   → Sửa bài viết ID 123
```

### 2 loại Editor

WordPress có 2 editor:

#### Block Editor (Gutenberg) - Mặc định từ WP 5.0

**Source**: `wp-admin/edit-form-blocks.php` - 430 dòng

```php
// wp-admin/post.php, trong case 'edit':
if ( use_block_editor_for_post( $post ) ) {
    require ABSPATH . 'wp-admin/edit-form-blocks.php';
    break;
}
```

Đặc điểm:
- React-based editor
- Block-based content (mỗi đoạn nội dung là 1 block)
- Fullscreen mode mặc định
- Giao tiếp qua REST API
- Content lưu dạng HTML comments: `<!-- wp:paragraph -->...<br><!-- /wp:paragraph -->`

```php
// wp-admin/edit-form-blocks.php (trích đoạn)

$block_editor_context = new WP_Block_Editor_Context( array( 'post' => $post ) );

// Flag block editor
$current_screen = get_current_screen();
$current_screen->is_block_editor( true );

// Fullscreen mode
add_filter( 'admin_body_class', static function ( $classes ) {
    return "$classes is-fullscreen-mode";
});

// Enqueue editor scripts
wp_enqueue_script( 'heartbeat' );
wp_enqueue_script( 'wp-edit-post' );

// Preload REST API paths
$preload_paths = array(
    '/wp/v2/types?context=view',
    '/wp/v2/taxonomies?context=view',
    add_query_arg( 'context', 'edit', $rest_path ),
    sprintf( '/wp/v2/types/%s?context=edit', $post_type ),
    '/wp/v2/users/me',
    sprintf( '%s/autosaves?context=edit', $rest_path ),
    '/wp/v2/settings',
);
```

#### Classic Editor

**Source**: `wp-admin/edit-form-advanced.php` - 775 dòng

Khi Gutenberg bị disable (qua plugin Classic Editor hoặc filter):

```php
// Disable Gutenberg cho tất cả post types
add_filter('use_block_editor_for_post', '__return_false');

// Disable chỉ cho post type cụ thể
add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
    if ($post_type === 'my_custom_type') {
        return false;
    }
    return $use_block_editor;
}, 10, 2);
```

Classic Editor form có nhiều meta boxes:

```php
// wp-admin/edit-form-advanced.php (trích đoạn)

// Kiểm tra post supports
if ( post_type_supports( $post_type, 'editor' ) ) {
    // TinyMCE editor (WYSIWYG)
    wp_editor( $post->post_content, 'content', array(
        'drag_drop_upload' => true,
        'tabfocus_elements' => 'content-html,save-post',
        'editor_height'    => 300,
        'tinymce'          => array(
            'resize'               => false,
            'wp_autoresize_on'     => true,
            'add_unload_trigger'   => false,
        ),
    ));
}

// Đăng ký meta boxes
do_action( 'add_meta_boxes', $post_type, $post );
do_action( "add_meta_boxes_{$post_type}", $post );

// Render meta boxes
do_meta_boxes( $post_type, 'normal', $post );  // Normal context
do_meta_boxes( $post_type, 'side', $post );    // Side context
do_meta_boxes( $post_type, 'advanced', $post ); // Advanced context
```

### Luồng tạo bài mới

```
GET /wp-admin/post-new.php
  │
  ├── require admin.php (bootstrap)
  │
  ├── Tạo auto-draft post
  │     $post = get_default_post_to_edit( $post_type, true );
  │     // → INSERT INTO wp_posts (post_status = 'auto-draft')
  │     // → Trả về WP_Post object
  │
  ├── Redirect đến edit form
  │     wp_redirect( admin_url('post.php?post=' . $post->ID . '&action=edit') );
  │
  └── Hoặc trực tiếp render form (tuỳ flow)
        ├── Block Editor: require edit-form-blocks.php
        └── Classic Editor: require edit-form-advanced.php
```

### Luồng sửa bài viết

```
GET /wp-admin/post.php?post=123&action=edit
  │
  ├── require admin.php
  │
  ├── $post = get_post( 123 )
  │
  ├── Kiểm tra quyền: current_user_can('edit_post', 123)
  │
  ├── Kiểm tra post lock (ai đang sửa)
  │     $user_id = wp_check_post_lock( 123 );
  │     if ( ! $user_id ) {
  │         $active_post_lock = wp_set_post_lock( 123 );
  │     }
  │
  ├── Filter: replace_editor
  │     if ( apply_filters( 'replace_editor', false, $post ) ) break;
  │
  ├── Chọn editor
  │     if ( use_block_editor_for_post( $post ) ) {
  │         require 'edit-form-blocks.php';    // Gutenberg
  │     } else {
  │         require 'edit-form-advanced.php';  // Classic
  │     }
  │
  └── Render form với data từ $post
```

---

## 4. Post Actions (wp-admin/post.php)

**Source**: `wp-admin/post.php` - 369 dòng

File `post.php` xử lý tất cả actions liên quan đến bài viết thông qua switch/case:

### Action: `post-quickdraft-save`

Lưu Quick Draft từ Dashboard widget.

```php
case 'post-quickdraft-save':
    $nonce = $_REQUEST['_wpnonce'];
    // Verify nonce
    if ( ! wp_verify_nonce( $nonce, 'add-post' ) ) {
        $error_msg = __( 'Unable to submit this form...' );
    }
    // Check capability
    if ( ! current_user_can( get_post_type_object( 'post' )->cap->create_posts ) ) {
        exit;
    }
    // Wrap content trong Paragraph block
    if ( ! str_contains( $_POST['content'], '<!-- wp:paragraph -->' ) ) {
        $_POST['content'] = sprintf(
            '<!-- wp:paragraph -->%s<!-- /wp:paragraph -->',
            str_replace( array( "\r\n", "\r", "\n" ), '<br />', $_POST['content'] )
        );
    }
    // Lưu
    edit_post();
    break;
```

### Action: `edit`

Hiển thị form edit bài viết.

```php
case 'edit':
    // Kiểm tra post tồn tại
    if ( ! $post ) wp_die( 'Not found' );

    // Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $post_id ) ) wp_die( 'No permission' );

    // Kiểm tra trash
    if ( 'trash' === $post->post_status ) wp_die( 'Cannot edit trashed item' );

    // Post lock (tránh 2 người sửa cùng lúc)
    if ( ! empty( $_GET['get-post-lock'] ) ) {
        check_admin_referer( 'lock-post_' . $post_id );
        wp_set_post_lock( $post_id );
    }

    // Filter cho phép thay thế editor
    if ( true === apply_filters( 'replace_editor', false, $post ) ) break;

    // Chọn Block Editor hoặc Classic Editor
    if ( use_block_editor_for_post( $post ) ) {
        require ABSPATH . 'wp-admin/edit-form-blocks.php';
    } else {
        require ABSPATH . 'wp-admin/edit-form-advanced.php';
    }
    break;
```

### Action: `editpost`

Lưu bài viết khi user submit form.

```php
case 'editpost':
    // Kiểm tra nonce
    check_admin_referer( 'update-post_' . $post_id );

    // Gọi edit_post() - hàm xử lý chính
    $post_id = edit_post();
    // → edit_post() nằm trong wp-admin/includes/post.php
    // → Bên trong gọi wp_update_post()
    // → wp_update_post() trigger hooks: save_post, wp_insert_post

    // Redirect về trang edit với thông báo
    redirect_post( $post_id );
    exit;
```

### Action: `trash`

Di chuyển bài viết vào thùng rác.

```php
case 'trash':
    check_admin_referer( 'trash-post_' . $post_id );

    // Kiểm tra post tồn tại và quyền
    if ( ! $post ) wp_die( 'Not found' );
    if ( ! current_user_can( 'delete_post', $post_id ) ) wp_die( 'No permission' );

    // Kiểm tra post lock
    $user_id = wp_check_post_lock( $post_id );
    if ( $user_id ) {
        $user = get_userdata( $user_id );
        wp_die( sprintf( '%s is currently editing.', $user->display_name ) );
    }

    // Thực hiện trash
    wp_trash_post( $post_id );
    // → Đổi post_status thành 'trash'
    // → Lưu meta '_wp_trash_meta_time' = time()
    // → Lưu meta '_wp_trash_meta_status' = status cũ
    // → Trigger hook: wp_trash_post

    // Redirect
    wp_redirect( add_query_arg( array( 'trashed' => 1, 'ids' => $post_id ), $sendback ) );
    exit;
```

### Action: `untrash`

Khôi phục bài viết từ thùng rác.

```php
case 'untrash':
    check_admin_referer( 'untrash-post_' . $post_id );

    if ( ! current_user_can( 'delete_post', $post_id ) ) wp_die( 'No permission' );

    wp_untrash_post( $post_id );
    // → Đọc meta '_wp_trash_meta_status' để khôi phục status cũ
    // → Xóa meta '_wp_trash_meta_time' và '_wp_trash_meta_status'
    // → Trigger hook: untrash_post

    wp_redirect( add_query_arg( array( 'untrashed' => 1 ), $sendback ) );
    exit;
```

### Action: `delete`

Xóa vĩnh viễn bài viết.

```php
case 'delete':
    check_admin_referer( 'delete-post_' . $post_id );

    if ( ! current_user_can( 'delete_post', $post_id ) ) wp_die( 'No permission' );

    if ( 'attachment' === $post->post_type ) {
        $force = ( ! MEDIA_TRASH );
        wp_delete_attachment( $post_id, $force );
    } else {
        wp_delete_post( $post_id, true );  // true = force delete, skip trash
    }
    // → Xóa khỏi wp_posts
    // → Xóa tất cả post meta khỏi wp_postmeta
    // → Xóa term relationships khỏi wp_term_relationships
    // → Xóa comments khỏi wp_comments
    // → Trigger hooks: before_delete_post, delete_post, deleted_post

    wp_redirect( add_query_arg( 'deleted', 1, $sendback ) );
    exit;
```

### Action: `preview`

Xem trước bài viết.

```php
case 'preview':
    check_admin_referer( 'update-post_' . $post_id );
    $url = post_preview();  // Tạo preview URL
    wp_redirect( $url );
    exit;
```

### Custom Actions

```php
// Mặc định, nếu action không match bất kỳ case nào:
default:
    do_action( "post_action_{$action}", $post_id );
    wp_redirect( admin_url( 'edit.php' ) );
    exit;
```

Bạn có thể hook vào đây:

```php
// Xử lý custom action
add_action('post_action_my_custom_action', function($post_id) {
    check_admin_referer('my_action_' . $post_id);

    // Xử lý logic
    update_post_meta($post_id, '_processed', true);

    wp_redirect(admin_url('edit.php?processed=1'));
    exit;
});
```

---

## 5. Meta Boxes

### Source

**Source**: `wp-admin/includes/meta-boxes.php` - 1753 dòng

### Meta Box là gì?

Meta boxes là các "hộp" (boxes) hiển thị trên trang edit bài viết. Mỗi box chứa một nhóm fields hoặc thông tin liên quan.

### Default Meta Boxes

WordPress đăng ký các meta boxes mặc định sau:

```php
// Trong wp-admin/edit-form-advanced.php và wp-admin/includes/meta-boxes.php:

// === SIDE CONTEXT ===

// 1. Publish Box (Submit)
add_meta_box( 'submitdiv', __( 'Publish' ), 'post_submit_meta_box', null, 'side', 'core' );
// → Status: Draft, Pending Review, Published
// → Visibility: Public, Password protected, Private
// → Schedule: Immediately hoặc chọn ngày
// → Nút: Save Draft, Preview, Publish/Update

// 2. Format (nếu post type support 'post-formats')
add_meta_box( 'formatdiv', _x( 'Format', 'post format' ), 'post_format_meta_box', null, 'side', 'core' );

// 3. Categories
add_meta_box( 'categorydiv', __( 'Categories' ), 'post_categories_meta_box', null, 'side', 'core' );

// 4. Tags (và các taxonomy khác)
add_meta_box( 'tagsdiv-post_tag', __( 'Tags' ), 'post_tags_meta_box', null, 'side', 'core' );

// 5. Featured Image (nếu post type support 'thumbnail')
add_meta_box( 'postimagediv', esc_html( $post_type_object->labels->featured_image ), 'post_thumbnail_meta_box', null, 'side', 'low' );

// === NORMAL CONTEXT ===

// 6. Excerpt (nếu post type support 'excerpt')
add_meta_box( 'postexcerpt', __( 'Excerpt' ), 'post_excerpt_meta_box', null, 'normal', 'core' );

// 7. Trackbacks (nếu post type support 'trackbacks')
add_meta_box( 'trackbacksdiv', __( 'Send Trackbacks' ), 'post_trackback_meta_box', null, 'normal', 'core' );

// 8. Custom Fields (nếu user bật)
add_meta_box( 'postcustom', __( 'Custom Fields' ), 'post_custom_meta_box', null, 'normal', 'core' );

// 9. Discussion (nếu post type support 'comments')
add_meta_box( 'commentstatusdiv', __( 'Discussion' ), 'post_comment_status_meta_box', null, 'normal', 'core' );

// 10. Comments (nếu có comments)
add_meta_box( 'commentsdiv', __( 'Comments' ), 'post_comment_meta_box', null, 'normal', 'core' );

// 11. Slug (nếu post type public)
add_meta_box( 'slugdiv', __( 'Slug' ), 'post_slug_meta_box', null, 'normal', 'core' );

// 12. Author (nếu post type support 'author' và user có quyền)
add_meta_box( 'authordiv', __( 'Author' ), 'post_author_meta_box', null, 'normal', 'core' );
```

### Layout Meta Boxes

```
┌────────────────────────────────────────────────────────┐
│  Edit Post: "Hello World"                               │
│                                                         │
│  ┌── NORMAL ────────────────┐  ┌── SIDE ────────────┐  │
│  │                          │  │                    │  │
│  │  [Editor - Title/Content]│  │  [Publish Box]     │  │
│  │                          │  │  Status: Draft     │  │
│  │                          │  │  Visibility: Public│  │
│  │                          │  │  Schedule: Now     │  │
│  │                          │  │  [Save] [Publish]  │  │
│  │                          │  │                    │  │
│  ├──────────────────────────┤  ├────────────────────┤  │
│  │  [Excerpt]               │  │  [Categories]      │  │
│  ├──────────────────────────┤  │  [x] Uncategorized │  │
│  │  [Custom Fields]         │  │  [ ] News          │  │
│  ├──────────────────────────┤  ├────────────────────┤  │
│  │  [Discussion]            │  │  [Tags]            │  │
│  │  [x] Allow comments      │  │  tag1, tag2        │  │
│  │  [x] Allow pingbacks     │  ├────────────────────┤  │
│  ├──────────────────────────┤  │  [Featured Image]  │  │
│  │  [Slug]                  │  │  [Set image]       │  │
│  │  hello-world             │  │                    │  │
│  ├──────────────────────────┤  │                    │  │
│  │  [Author]                │  │                    │  │
│  │  [admin ▼]               │  │                    │  │
│  └──────────────────────────┘  └────────────────────┘  │
└────────────────────────────────────────────────────────┘
```

### Tạo Custom Meta Box

Đây là pattern phổ biến nhất trong WordPress development:

```php
/**
 * Đăng ký meta box
 */
add_action('add_meta_boxes', function() {
    add_meta_box(
        'product_details',           // ID (unique)
        'Chi Tiết Sản Phẩm',        // Tiêu đề
        'render_product_meta_box',   // Callback render
        'product',                   // Post type (hoặc array post types)
        'normal',                    // Context: 'normal', 'side', 'advanced'
        'high',                      // Priority: 'high', 'core', 'default', 'low'
        array(                       // Callback args (optional)
            'currency' => 'VNĐ',
        )
    );
});

// Hoặc chỉ cho post type cụ thể:
add_action('add_meta_boxes_product', function($post) {
    add_meta_box('product_details', 'Chi Tiết Sản Phẩm', 'render_product_meta_box', null, 'normal', 'high');
});

/**
 * Render meta box content
 */
function render_product_meta_box($post, $meta_box) {
    // Nonce field cho bảo mật
    wp_nonce_field('product_details_save', 'product_details_nonce');

    // Lấy giá trị đã lưu
    $price     = get_post_meta($post->ID, '_product_price', true);
    $sku       = get_post_meta($post->ID, '_product_sku', true);
    $stock     = get_post_meta($post->ID, '_product_stock', true);
    $weight    = get_post_meta($post->ID, '_product_weight', true);
    $is_sale   = get_post_meta($post->ID, '_product_is_sale', true);
    $sale_price = get_post_meta($post->ID, '_product_sale_price', true);

    $currency = $meta_box['args']['currency'] ?? 'VNĐ';
    ?>
    <table class="form-table">
        <tr>
            <th><label for="product_sku">Mã SKU</label></th>
            <td>
                <input type="text" id="product_sku" name="product_sku"
                       value="<?php echo esc_attr($sku); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="product_price">Giá (<?php echo $currency; ?>)</label></th>
            <td>
                <input type="number" id="product_price" name="product_price"
                       value="<?php echo esc_attr($price); ?>" class="regular-text" min="0" step="1000">
            </td>
        </tr>
        <tr>
            <th><label for="product_is_sale">Đang giảm giá?</label></th>
            <td>
                <label>
                    <input type="checkbox" id="product_is_sale" name="product_is_sale"
                           value="1" <?php checked($is_sale, '1'); ?>>
                    Có
                </label>
            </td>
        </tr>
        <tr id="sale-price-row" style="<?php echo $is_sale ? '' : 'display:none'; ?>">
            <th><label for="product_sale_price">Giá giảm (<?php echo $currency; ?>)</label></th>
            <td>
                <input type="number" id="product_sale_price" name="product_sale_price"
                       value="<?php echo esc_attr($sale_price); ?>" class="regular-text" min="0" step="1000">
            </td>
        </tr>
        <tr>
            <th><label for="product_stock">Tồn kho</label></th>
            <td>
                <input type="number" id="product_stock" name="product_stock"
                       value="<?php echo esc_attr($stock); ?>" class="small-text" min="0">
            </td>
        </tr>
        <tr>
            <th><label for="product_weight">Cân nặng (kg)</label></th>
            <td>
                <input type="text" id="product_weight" name="product_weight"
                       value="<?php echo esc_attr($weight); ?>" class="small-text">
            </td>
        </tr>
    </table>

    <script>
    jQuery('#product_is_sale').on('change', function() {
        jQuery('#sale-price-row').toggle(this.checked);
    });
    </script>
    <?php
}

/**
 * Lưu meta box data
 */
add_action('save_post', function($post_id, $post, $update) {
    // 1. Kiểm tra nonce
    if (!isset($_POST['product_details_nonce'])) return;
    if (!wp_verify_nonce($_POST['product_details_nonce'], 'product_details_save')) return;

    // 2. Kiểm tra autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // 3. Kiểm tra quyền
    if (!current_user_can('edit_post', $post_id)) return;

    // 4. Kiểm tra post type
    if ($post->post_type !== 'product') return;

    // 5. Sanitize và lưu
    if (isset($_POST['product_price'])) {
        update_post_meta($post_id, '_product_price', sanitize_text_field($_POST['product_price']));
    }
    if (isset($_POST['product_sku'])) {
        update_post_meta($post_id, '_product_sku', sanitize_text_field($_POST['product_sku']));
    }
    if (isset($_POST['product_stock'])) {
        update_post_meta($post_id, '_product_stock', intval($_POST['product_stock']));
    }
    if (isset($_POST['product_weight'])) {
        update_post_meta($post_id, '_product_weight', sanitize_text_field($_POST['product_weight']));
    }

    // Checkbox: nếu không checked thì $_POST không có key
    update_post_meta($post_id, '_product_is_sale', isset($_POST['product_is_sale']) ? '1' : '0');

    if (isset($_POST['product_sale_price'])) {
        update_post_meta($post_id, '_product_sale_price', sanitize_text_field($_POST['product_sale_price']));
    }
}, 10, 3);
```

### Xóa Default Meta Boxes

```php
add_action('add_meta_boxes', function() {
    // Xóa Excerpt
    remove_meta_box('postexcerpt', 'post', 'normal');

    // Xóa Custom Fields
    remove_meta_box('postcustom', 'post', 'normal');

    // Xóa Trackbacks
    remove_meta_box('trackbacksdiv', 'post', 'normal');

    // Xóa Author
    remove_meta_box('authordiv', 'post', 'normal');

    // Xóa Slug
    remove_meta_box('slugdiv', 'post', 'normal');

    // Xóa Discussion
    remove_meta_box('commentstatusdiv', 'post', 'normal');

    // Xóa Tags cho page
    remove_meta_box('tagsdiv-post_tag', 'page', 'side');
}, 99); // Priority cao để chạy sau khi đã đăng ký
```

---

## 6. Bulk Actions

### Default Bulk Actions

Trên trang danh sách bài viết, có 2 bulk actions mặc định:
- **Edit** - Bulk edit (thay đổi category, status, etc. cho nhiều bài cùng lúc)
- **Move to Trash** - Chuyển nhiều bài vào thùng rác

Trên trang Trash:
- **Restore** - Khôi phục
- **Delete Permanently** - Xóa vĩnh viễn

### Custom Bulk Actions

```php
// Thêm bulk action mới
add_filter('bulk_actions-edit-post', function($bulk_actions) {
    $bulk_actions['mark_featured'] = 'Đánh dấu nổi bật';
    $bulk_actions['unmark_featured'] = 'Bỏ đánh dấu nổi bật';
    $bulk_actions['export_csv'] = 'Xuất CSV';
    return $bulk_actions;
});

// Cho custom post type: bulk_actions-edit-{post_type}
add_filter('bulk_actions-edit-product', function($bulk_actions) {
    $bulk_actions['update_stock'] = 'Cập nhật tồn kho';
    return $bulk_actions;
});

// Xử lý bulk action
add_filter('handle_bulk_actions-edit-post', function($redirect_to, $doaction, $post_ids) {
    if ($doaction === 'mark_featured') {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_is_featured', '1');
        }
        $redirect_to = add_query_arg('bulk_featured', count($post_ids), $redirect_to);
    }

    if ($doaction === 'unmark_featured') {
        foreach ($post_ids as $post_id) {
            delete_post_meta($post_id, '_is_featured');
        }
        $redirect_to = add_query_arg('bulk_unfeatured', count($post_ids), $redirect_to);
    }

    if ($doaction === 'export_csv') {
        // Export logic...
        $redirect_to = add_query_arg('exported', count($post_ids), $redirect_to);
    }

    return $redirect_to;
}, 10, 3);

// Hiện thông báo sau khi bulk action
add_action('admin_notices', function() {
    if (!empty($_GET['bulk_featured'])) {
        $count = intval($_GET['bulk_featured']);
        printf(
            '<div class="notice notice-success is-dismissible"><p>%d bài viết đã được đánh dấu nổi bật.</p></div>',
            $count
        );
    }
    if (!empty($_GET['bulk_unfeatured'])) {
        $count = intval($_GET['bulk_unfeatured']);
        printf(
            '<div class="notice notice-success is-dismissible"><p>%d bài viết đã bỏ đánh dấu nổi bật.</p></div>',
            $count
        );
    }
});
```

---

## 7. Quick Edit & Inline Edit

### Giới thiệu

Quick Edit cho phép sửa nhanh một số fields của bài viết ngay trên trang danh sách, không cần mở trang edit.

### Hoạt động

```
User click "Quick Edit" trên row
  │
  ├── JavaScript mở inline form (ẩn row, hiện form)
  │
  ├── User sửa: Title, Slug, Date, Category, Tags, Status, etc.
  │
  ├── User click "Update"
  │
  └── AJAX call: wp_ajax_inline-save
        │
        ├── Source: wp-admin/includes/ajax-actions.php
        ├── check_ajax_referer( 'inlineeditnonce', '_inline_edit' )
        ├── edit_post() → wp_update_post()
        └── Return updated row HTML
```

### Thêm Custom Fields vào Quick Edit

```php
// 1. Thêm cột hiển thị data (cần cho JS đọc)
add_filter('manage_posts_columns', function($columns) {
    $columns['price'] = 'Giá';
    return $columns;
});

add_action('manage_posts_custom_column', function($column, $post_id) {
    if ($column === 'price') {
        $price = get_post_meta($post_id, '_product_price', true);
        echo '<span class="price-value">' . esc_html($price) . '</span>';
    }
}, 10, 2);

// 2. Thêm field vào Quick Edit form
add_action('quick_edit_custom_box', function($column_name, $post_type) {
    if ($column_name !== 'price') return;
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title">Giá</span>
                <span class="input-text-wrap">
                    <input type="number" name="product_price" class="inline-edit-price" value="">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}, 10, 2);

// 3. JavaScript để populate value khi mở Quick Edit
add_action('admin_footer-edit.php', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Lưu reference hàm gốc
        var wpInlineEdit = inlineEditPost.edit;

        // Override
        inlineEditPost.edit = function(id) {
            // Gọi hàm gốc
            wpInlineEdit.apply(this, arguments);

            // Lấy post ID
            var postId = 0;
            if (typeof(id) === 'object') {
                postId = parseInt(this.getId(id));
            }

            if (postId > 0) {
                // Lấy row hiện tại
                var editRow = $('#edit-' + postId);
                var postRow = $('#post-' + postId);

                // Lấy giá trị từ cột
                var price = postRow.find('.price-value').text();

                // Set vào Quick Edit form
                editRow.find('input[name="product_price"]').val(price);
            }
        };
    });
    </script>
    <?php
});

// 4. Lưu khi Quick Edit submit
add_action('save_post', function($post_id) {
    // Quick Edit gửi qua AJAX nên kiểm tra inline edit nonce
    if (!isset($_POST['_inline_edit'])) return;
    if (!wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce')) return;

    if (isset($_POST['product_price'])) {
        update_post_meta($post_id, '_product_price', sanitize_text_field($_POST['product_price']));
    }
});
```

---

## 8. Screen Options cho Posts List

### Số items per page

```php
// Thay đổi mặc định số bài mỗi trang
add_filter('edit_post_per_page', function($per_page) {
    return 50; // Mặc định WordPress là 20
});

// Cho custom post type:
add_filter('edit_product_per_page', function($per_page) {
    return 30;
});
```

### Show/Hide Columns

User có thể ẩn/hiện cột qua Screen Options. Giá trị lưu vào `wp_usermeta`:

```
meta_key: manageedit-postcolumnshidden
meta_value: a:2:{i:0;s:6:"author";i:1;s:10:"categories";}
```

### View Mode

2 chế độ xem:
- **List view** (mặc định) - Compact
- **Excerpt view** - Hiện thêm excerpt

```php
// Lấy view mode hiện tại:
$mode = get_user_setting('posts_list_mode', 'list');
// 'list' hoặc 'excerpt'
```

---

## 9. Custom Columns

### Thêm cột mới

```php
// Bước 1: Đăng ký cột
add_filter('manage_posts_columns', function($columns) {
    // Thêm sau title
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['thumbnail'] = 'Ảnh đại diện';
            $new_columns['price'] = 'Giá';
            $new_columns['views'] = 'Lượt xem';
        }
    }
    return $new_columns;
});

// Cho custom post type: manage_{post_type}_posts_columns
add_filter('manage_product_posts_columns', function($columns) {
    $columns['sku'] = 'Mã SKU';
    $columns['price'] = 'Giá';
    $columns['stock'] = 'Tồn kho';
    return $columns;
});

// Bước 2: Render nội dung cột
add_action('manage_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case 'thumbnail':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(50, 50));
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'price':
            $price = get_post_meta($post_id, '_product_price', true);
            if ($price) {
                echo number_format((float)$price, 0, ',', '.') . ' VNĐ';
            } else {
                echo '—';
            }
            break;

        case 'views':
            $views = get_post_meta($post_id, '_post_views', true);
            echo $views ? number_format_i18n($views) : '0';
            break;
    }
}, 10, 2);

// Cho custom post type: manage_{post_type}_posts_custom_column
add_action('manage_product_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case 'sku':
            echo esc_html(get_post_meta($post_id, '_product_sku', true));
            break;
        case 'stock':
            $stock = get_post_meta($post_id, '_product_stock', true);
            $color = ($stock > 0) ? 'green' : 'red';
            echo '<span style="color:' . $color . '">' . intval($stock) . '</span>';
            break;
    }
}, 10, 2);

// Bước 3: Sortable columns
add_filter('manage_edit-post_sortable_columns', function($columns) {
    $columns['price'] = 'price';
    $columns['views'] = 'views';
    return $columns;
});

// Bước 4: Xử lý sort query
add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    $orderby = $query->get('orderby');

    if ($orderby === 'price') {
        $query->set('meta_key', '_product_price');
        $query->set('orderby', 'meta_value_num');
    }

    if ($orderby === 'views') {
        $query->set('meta_key', '_post_views');
        $query->set('orderby', 'meta_value_num');
    }
});
```

### Xóa cột mặc định

```php
add_filter('manage_posts_columns', function($columns) {
    unset($columns['author']);     // Xóa cột Author
    unset($columns['categories']); // Xóa cột Categories
    unset($columns['tags']);       // Xóa cột Tags
    unset($columns['comments']);   // Xóa cột Comments
    return $columns;
});
```

### Thay đổi độ rộng cột

```php
add_action('admin_head', function() {
    ?>
    <style>
        .column-thumbnail { width: 60px; }
        .column-price { width: 100px; }
        .column-views { width: 80px; }
        .column-sku { width: 120px; }
        .column-stock { width: 80px; text-align: center; }
    </style>
    <?php
});
```

---

## 10. Admin Filters (Dropdown)

### Default Filters

Trang danh sách bài viết có 2 dropdown filter mặc định:
1. **All dates** - Filter theo tháng
2. **All Categories** - Filter theo category (chỉ cho post type 'post')

Và các **status tabs** phía trên:
- All | Published | Draft | Pending | Trash

### Thêm Custom Filter

```php
// Thêm dropdown filter
add_action('restrict_manage_posts', function($post_type) {
    if ($post_type !== 'product') return;

    // Filter theo trạng thái tồn kho
    $selected = $_GET['stock_status'] ?? '';
    echo '<select name="stock_status">';
    echo '<option value="">Tất cả trạng thái kho</option>';
    echo '<option value="in_stock" ' . selected($selected, 'in_stock', false) . '>Còn hàng</option>';
    echo '<option value="out_of_stock" ' . selected($selected, 'out_of_stock', false) . '>Hết hàng</option>';
    echo '</select>';

    // Filter theo khoảng giá
    $price_range = $_GET['price_range'] ?? '';
    echo '<select name="price_range">';
    echo '<option value="">Tất cả mức giá</option>';
    echo '<option value="0-100000" ' . selected($price_range, '0-100000', false) . '>Dưới 100K</option>';
    echo '<option value="100000-500000" ' . selected($price_range, '100000-500000', false) . '>100K - 500K</option>';
    echo '<option value="500000-0" ' . selected($price_range, '500000-0', false) . '>Trên 500K</option>';
    echo '</select>';
});

// Xử lý filter trong query
add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    // Filter tồn kho
    if (!empty($_GET['stock_status'])) {
        $meta_query = $query->get('meta_query') ?: array();

        if ($_GET['stock_status'] === 'in_stock') {
            $meta_query[] = array(
                'key'     => '_product_stock',
                'value'   => 0,
                'compare' => '>',
                'type'    => 'NUMERIC',
            );
        } elseif ($_GET['stock_status'] === 'out_of_stock') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_product_stock',
                    'value'   => 0,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_product_stock',
                    'compare' => 'NOT EXISTS',
                ),
            );
        }

        $query->set('meta_query', $meta_query);
    }

    // Filter khoảng giá
    if (!empty($_GET['price_range'])) {
        list($min, $max) = explode('-', $_GET['price_range']);
        $meta_query = $query->get('meta_query') ?: array();

        if ($max > 0) {
            $meta_query[] = array(
                'key'     => '_product_price',
                'value'   => array(intval($min), intval($max)),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        } else {
            $meta_query[] = array(
                'key'     => '_product_price',
                'value'   => intval($min),
                'compare' => '>',
                'type'    => 'NUMERIC',
            );
        }

        $query->set('meta_query', $meta_query);
    }
});
```

---

## 11. DB: Posts Lưu Gì?

### Bảng `wp_posts`

Đây là bảng chính lưu tất cả nội dung WordPress (posts, pages, attachments, revisions, menu items, v.v.):

```sql
CREATE TABLE wp_posts (
    ID                  bigint(20)   NOT NULL AUTO_INCREMENT,
    post_author         bigint(20)   NOT NULL DEFAULT 0,       -- user_id tác giả
    post_date           datetime     NOT NULL DEFAULT '0000-00-00 00:00:00', -- ngày tạo (local)
    post_date_gmt       datetime     NOT NULL DEFAULT '0000-00-00 00:00:00', -- ngày tạo (GMT)
    post_content        longtext     NOT NULL,                  -- nội dung HTML
    post_title          text         NOT NULL,                  -- tiêu đề
    post_excerpt        text         NOT NULL,                  -- tóm tắt
    post_status         varchar(20)  NOT NULL DEFAULT 'publish',-- trạng thái
    comment_status      varchar(20)  NOT NULL DEFAULT 'open',   -- cho phép comment
    ping_status         varchar(20)  NOT NULL DEFAULT 'open',   -- cho phép ping
    post_password       varchar(255) NOT NULL DEFAULT '',        -- mật khẩu bảo vệ
    post_name           varchar(200) NOT NULL DEFAULT '',        -- slug URL
    to_ping             text         NOT NULL,
    pinged              text         NOT NULL,
    post_modified       datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    post_modified_gmt   datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    post_content_filtered longtext   NOT NULL,
    post_parent         bigint(20)   NOT NULL DEFAULT 0,       -- parent post ID (cho pages)
    guid                varchar(255) NOT NULL DEFAULT '',
    menu_order          int(11)      NOT NULL DEFAULT 0,        -- thứ tự (cho pages, menu items)
    post_type           varchar(20)  NOT NULL DEFAULT 'post',   -- loại: post, page, attachment, revision, etc.
    post_mime_type      varchar(100) NOT NULL DEFAULT '',        -- MIME type (cho attachments)
    comment_count       bigint(20)   NOT NULL DEFAULT 0,
    PRIMARY KEY (ID)
);
```

### Post Status

```php
// Built-in statuses:
'publish'    // Đã xuất bản, hiện trên frontend
'draft'      // Bản nháp, chưa xuất bản
'pending'    // Chờ duyệt
'private'    // Riêng tư, chỉ admin/author thấy
'auto-draft' // WordPress tự tạo khi mở post-new.php
'trash'      // Trong thùng rác
'inherit'    // Kế thừa từ parent (dùng cho attachments, revisions)
'future'     // Đã lên lịch xuất bản

// Đăng ký custom status:
register_post_status('archived', array(
    'label'                     => 'Đã lưu trữ',
    'public'                    => false,
    'exclude_from_search'       => true,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'label_count'               => _n_noop('Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>'),
));
```

### Bảng `wp_postmeta`

Lưu metadata cho mỗi post:

```sql
CREATE TABLE wp_postmeta (
    meta_id    bigint(20) NOT NULL AUTO_INCREMENT,
    post_id    bigint(20) NOT NULL DEFAULT 0,
    meta_key   varchar(255) DEFAULT NULL,
    meta_value longtext,
    PRIMARY KEY (meta_id)
);
```

```php
// WordPress core meta keys (bắt đầu bằng _):
'_edit_lock'              // Post lock: "timestamp:user_id"
'_edit_last'              // User ID người sửa cuối
'_wp_page_template'       // Page template file
'_thumbnail_id'           // Featured image attachment ID
'_wp_trash_meta_status'   // Status trước khi trash
'_wp_trash_meta_time'     // Thời điểm trash
'_wp_old_slug'            // Slug cũ (cho redirect 301)

// Custom meta:
get_post_meta($post_id, '_product_price', true);
update_post_meta($post_id, '_product_price', '500000');
delete_post_meta($post_id, '_product_price');
add_post_meta($post_id, '_product_price', '500000', true); // true = unique
```

### Bảng `wp_term_relationships`

Liên kết posts với categories, tags:

```sql
CREATE TABLE wp_term_relationships (
    object_id        bigint(20) NOT NULL DEFAULT 0,  -- post_id
    term_taxonomy_id bigint(20) NOT NULL DEFAULT 0,  -- term_taxonomy_id
    term_order       int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (object_id, term_taxonomy_id)
);
```

### Revisions (Autosave)

```sql
-- Revisions là posts đặc biệt với post_type = 'revision'
SELECT * FROM wp_posts
WHERE post_type = 'revision'
  AND post_parent = 123  -- parent post ID
ORDER BY post_date DESC;

-- Mỗi lần lưu bài (nếu nội dung thay đổi), WordPress tạo 1 revision
-- Autosave cũng tạo revision (1 per user per post)
```

### Trash

```sql
-- Bài trong thùng rác
SELECT p.*, pm1.meta_value AS trash_time, pm2.meta_value AS original_status
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_wp_trash_meta_time'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_wp_trash_meta_status'
WHERE p.post_status = 'trash';

-- WordPress tự động xóa vĩnh viễn bài trong trash sau 30 ngày
-- Cron job: wp_scheduled_delete (chạy daily)
-- Constant: define('EMPTY_TRASH_DAYS', 30); // trong wp-config.php
```

---

## 12. Hooks Quan Trọng

### Hooks khi lưu bài viết

```php
// === KHI TẠO MỚI (wp_insert_post) ===
// Thứ tự hooks:
// 1. wp_insert_post_data (filter) - Filter data trước khi insert
// 2. wp_insert_post (action) - Sau khi insert thành công
// 3. save_post_{post_type} (action) - Sau khi lưu, cho post type cụ thể
// 4. save_post (action) - Sau khi lưu, cho tất cả post types
// 5. wp_after_insert_post (action) - Cuối cùng, sau tất cả meta đã lưu

// Filter data trước khi lưu
add_filter('wp_insert_post_data', function($data, $postarr) {
    // $data = array data sẽ insert/update vào DB
    // Có thể modify trước khi lưu

    // Ví dụ: auto-generate excerpt nếu trống
    if (empty($data['post_excerpt']) && !empty($data['post_content'])) {
        $data['post_excerpt'] = wp_trim_words(strip_tags($data['post_content']), 30);
    }

    return $data;
}, 10, 2);

// Sau khi lưu (phổ biến nhất)
add_action('save_post', function($post_id, $post, $update) {
    // $update = true nếu đang update, false nếu insert mới

    // Bỏ qua autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Bỏ qua revisions
    if (wp_is_post_revision($post_id)) return;

    // Bỏ qua auto-draft
    if ($post->post_status === 'auto-draft') return;

    // Custom logic
    if ($post->post_type === 'product') {
        // Tự động tính giá sale
        $price = get_post_meta($post_id, '_product_price', true);
        $is_sale = get_post_meta($post_id, '_product_is_sale', true);
        if ($is_sale && $price) {
            $sale_price = $price * 0.9; // Giảm 10%
            update_post_meta($post_id, '_product_sale_price', $sale_price);
        }
    }
}, 10, 3);

// Cho post type cụ thể
add_action('save_post_product', function($post_id, $post, $update) {
    // Chỉ chạy cho post type 'product'
}, 10, 3);

// Cuối cùng, sau khi tất cả meta đã lưu (WP 5.6+)
add_action('wp_after_insert_post', function($post_id, $post, $update, $post_before) {
    // $post_before = WP_Post object trước khi update (null nếu insert)
    // Thích hợp cho notifications, indexing, etc.

    if ($update && $post->post_status === 'publish' && $post_before->post_status !== 'publish') {
        // Bài viết vừa được publish lần đầu
        // Gửi notification, share social media, etc.
    }
}, 10, 4);
```

### Hooks khi xóa bài viết

```php
// Trash
add_action('wp_trash_post', function($post_id) {
    // Trước khi chuyển vào trash
});

add_action('trashed_post', function($post_id) {
    // Sau khi đã chuyển vào trash
});

// Untrash (khôi phục)
add_action('untrash_post', function($post_id) {
    // Trước khi khôi phục
});

add_action('untrashed_post', function($post_id) {
    // Sau khi khôi phục
});

// Xóa vĩnh viễn
add_action('before_delete_post', function($post_id, $post) {
    // Trước khi xóa khỏi DB
    // Dọn dẹp data liên quan

    // Ví dụ: Xóa files liên quan
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_parent' => $post_id,
        'numberposts' => -1,
    ));
    foreach ($attachments as $attachment) {
        wp_delete_attachment($attachment->ID, true);
    }
}, 10, 2);

add_action('deleted_post', function($post_id, $post) {
    // Sau khi đã xóa khỏi DB
    // Lưu log, clear cache, etc.
}, 10, 2);
```

### Hooks Meta Boxes

```php
// Đăng ký meta boxes
add_action('add_meta_boxes', function($post_type, $post) {
    add_meta_box('my_box', 'Title', 'callback', $post_type, 'normal', 'high');
}, 10, 2);

// Cho post type cụ thể
add_action('add_meta_boxes_product', function($post) {
    add_meta_box('product_box', 'Product Details', 'callback');
});
```

### Hooks Custom Columns

```php
// Đăng ký cột (filter)
add_filter('manage_posts_columns', 'my_columns');
add_filter('manage_{post_type}_posts_columns', 'my_columns');

// Render cột (action)
add_action('manage_posts_custom_column', 'my_column_content', 10, 2);
add_action('manage_{post_type}_posts_custom_column', 'my_column_content', 10, 2);

// Sortable columns (filter)
add_filter('manage_edit-post_sortable_columns', 'my_sortable_columns');
add_filter('manage_edit-{post_type}_sortable_columns', 'my_sortable_columns');
```

### Hooks Admin Filters

```php
// Dropdown filters (action)
add_action('restrict_manage_posts', function($post_type, $which) {
    // $which = 'top' hoặc 'bottom' (trên hoặc dưới bảng)
    if ($which === 'top') {
        // Render dropdown
    }
}, 10, 2);
```

### Hooks Modify Query

```php
// Thay đổi query trước khi chạy
add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-product') return;

    // Mặc định sort theo giá
    if (empty($query->get('orderby'))) {
        $query->set('meta_key', '_product_price');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
    }

    // Chỉ hiện sản phẩm active
    $query->set('meta_query', array(
        array(
            'key'   => '_product_active',
            'value' => '1',
        ),
    ));
});
```

### Hooks Editor

```php
// Thêm content sau title
add_action('edit_form_after_title', function($post) {
    if ($post->post_type !== 'product') return;
    echo '<div class="notice notice-info" style="margin:15px 0">';
    echo '<p>Mã sản phẩm: ' . esc_html(get_post_meta($post->ID, '_product_sku', true)) . '</p>';
    echo '</div>';
});

// Thêm content sau editor
add_action('edit_form_after_editor', function($post) {
    if ($post->post_type !== 'product') return;
    echo '<h3>Thông Tin Bổ Sung</h3>';
    echo '<p>Nội dung thêm dưới editor.</p>';
});
```

### Bảng tổng hợp hooks

| Hook | Loại | File | Khi nào |
|------|------|------|---------|
| `save_post` | action | `wp-includes/post.php` | Sau khi lưu bất kỳ post nào |
| `save_post_{type}` | action | `wp-includes/post.php` | Sau khi lưu post type cụ thể |
| `wp_insert_post` | action | `wp-includes/post.php` | Sau khi insert post |
| `wp_insert_post_data` | filter | `wp-includes/post.php` | Filter data trước insert |
| `wp_after_insert_post` | action | `wp-includes/post.php` | Cuối cùng sau khi lưu |
| `before_delete_post` | action | `wp-includes/post.php` | Trước khi xóa |
| `deleted_post` | action | `wp-includes/post.php` | Sau khi xóa |
| `wp_trash_post` | action | `wp-includes/post.php` | Trước khi trash |
| `untrash_post` | action | `wp-includes/post.php` | Trước khi untrash |
| `add_meta_boxes` | action | `wp-admin/edit-form-advanced.php` | Đăng ký meta boxes |
| `manage_{type}_posts_columns` | filter | `wp-admin/includes/class-wp-posts-list-table.php` | Cột danh sách |
| `manage_{type}_posts_custom_column` | action | `wp-admin/includes/class-wp-posts-list-table.php` | Render cột |
| `restrict_manage_posts` | action | `wp-admin/includes/class-wp-posts-list-table.php` | Dropdown filters |
| `pre_get_posts` | action | `wp-includes/class-wp-query.php` | Modify query |
| `bulk_actions-edit-{type}` | filter | `wp-admin/includes/class-wp-list-table.php` | Bulk actions |
| `edit_form_after_title` | action | `wp-admin/edit-form-advanced.php` | Sau title trong editor |
| `edit_form_after_editor` | action | `wp-admin/edit-form-advanced.php` | Sau editor |
| `use_block_editor_for_post` | filter | `wp-includes/post.php` | Chọn Gutenberg/Classic |
| `post_action_{$action}` | action | `wp-admin/post.php` | Custom post action |

---

## 13. So Sánh Laravel

### Resource Controller

```php
// Laravel Resource Controller
class PostController extends Controller
{
    // GET /posts → index()
    public function index() {
        $posts = Post::paginate(20);
        return view('posts.index', compact('posts'));
    }

    // GET /posts/create → create()
    public function create() {
        return view('posts.create');
    }

    // POST /posts → store()
    public function store(Request $request) {
        $validated = $request->validate([...]);
        $post = Post::create($validated);
        return redirect()->route('posts.index');
    }

    // GET /posts/{id}/edit → edit()
    public function edit(Post $post) {
        return view('posts.edit', compact('post'));
    }

    // PUT /posts/{id} → update()
    public function update(Request $request, Post $post) {
        $validated = $request->validate([...]);
        $post->update($validated);
        return redirect()->route('posts.index');
    }

    // DELETE /posts/{id} → destroy()
    public function destroy(Post $post) {
        $post->delete();
        return redirect()->route('posts.index');
    }
}
```

```php
// WordPress tương đương (không dùng class controller):
// - index()  → edit.php + WP_Posts_List_Table
// - create() → post-new.php → post.php?action=edit
// - store()  → post.php?action=editpost → edit_post()
// - edit()   → post.php?post=ID&action=edit
// - update() → post.php?action=editpost → edit_post()
// - destroy()→ post.php?action=delete → wp_delete_post()
```

### DataTable

```php
// Laravel DataTables (package)
class PostDataTable extends DataTable
{
    public function dataTable($query) {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function($post) {
                return view('posts.action', compact('post'));
            });
    }

    public function query(Post $model) {
        return $model->newQuery()->with('author', 'categories');
    }

    public function columns() {
        return [
            Column::make('id'),
            Column::make('title'),
            Column::make('author.name'),
            Column::make('created_at'),
            Column::computed('action'),
        ];
    }
}

// WordPress: WP_Posts_List_Table
// - Columns: manage_posts_columns filter
// - Content: manage_posts_custom_column action
// - Sorting: manage_edit-post_sortable_columns filter
// - Filtering: restrict_manage_posts action
// - Bulk actions: bulk_actions-edit-post filter
// - Pagination: built-in
// - Search: built-in
```

### Meta Box vs Form

```php
// Laravel: Form trong Blade template
<form action="{{ route('posts.update', $post) }}" method="POST">
    @csrf
    @method('PUT')
    <input type="text" name="title" value="{{ $post->title }}">
    <textarea name="content">{{ $post->content }}</textarea>
    <input type="number" name="price" value="{{ $post->price }}">
    <button type="submit">Save</button>
</form>

// WordPress: Meta box
add_action('add_meta_boxes', function() {
    add_meta_box('my_box', 'Extra Fields', function($post) {
        wp_nonce_field('my_box_save', 'my_box_nonce');
        $price = get_post_meta($post->ID, '_price', true);
        echo '<input type="number" name="price" value="' . esc_attr($price) . '">';
    });
});

add_action('save_post', function($post_id) {
    if (!wp_verify_nonce($_POST['my_box_nonce'] ?? '', 'my_box_save')) return;
    update_post_meta($post_id, '_price', sanitize_text_field($_POST['price']));
});
```

### Soft Delete (Trash)

```php
// Laravel Soft Delete
class Post extends Model {
    use SoftDeletes;
}

// Delete (soft)
$post->delete();        // Set deleted_at = now()
// Restore
$post->restore();       // Set deleted_at = null
// Force delete
$post->forceDelete();   // Xóa khỏi DB
// Query trashed
Post::withTrashed()->get();
Post::onlyTrashed()->get();

// WordPress Trash
wp_trash_post($post_id);     // Set post_status = 'trash', lưu meta
wp_untrash_post($post_id);   // Khôi phục status cũ, xóa meta
wp_delete_post($post_id, true); // Xóa vĩnh viễn
// Query trashed
get_posts(['post_status' => 'trash']);
```

---

## Tổng Kết

Hệ thống quản lý bài viết WordPress bao gồm:

1. **Trang danh sách** (`edit.php`): Dùng `WP_Posts_List_Table` class, hỗ trợ pagination, search, sort, filter, bulk actions
2. **Trang tạo/sửa** (`post-new.php`, `post.php`): 2 editor (Block/Classic), meta boxes, autosave, post lock
3. **Post actions** (`post.php`): Xử lý tất cả CRUD operations qua switch/case
4. **Meta boxes**: Hệ thống mở rộng form edit, nonce verification, save_post hook
5. **Custom columns**: Filter/action hooks để thêm/xóa cột trên danh sách
6. **DB**: `wp_posts` (nội dung) + `wp_postmeta` (metadata) + `wp_term_relationships` (categories/tags)

Tất cả đều có thể mở rộng thông qua hooks (actions & filters), khác biệt lớn so với Laravel nơi bạn extend thông qua class inheritance và service providers.

---

*Trước đó: [02-dashboard.md](./02-dashboard.md) - Dashboard, Widgets, Screen Options*
*Tiếp theo: [04-quan-ly-media.md](./04-quan-ly-media.md) - Quản lý Media, Upload, Image Sizes*
