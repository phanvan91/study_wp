# Database và WP_Query trong WordPress

Hướng dẫn toàn diện về cơ sở dữ liệu WordPress và các lớp truy vấn (WP_Query, WP_Meta_Query, WP_Tax_Query, WP_User_Query, WP_Comment_Query), bao gồm $wpdb, custom tables và tối ưu hiệu năng.

---

## Mục lục

1. [Cấu trúc Database WordPress](#1-cau-truc-database-wordpress)
2. [$wpdb - WordPress Database Abstraction Layer](#2-wpdb---wordpress-database-abstraction-layer)
3. [WP_Query - Giải thích chi tiết](#3-wp_query---giai-thich-chi-tiet)
4. [WP_Meta_Query - Query theo meta fields](#4-wp_meta_query---query-theo-meta-fields)
5. [WP_Tax_Query - Query theo taxonomy](#5-wp_tax_query---query-theo-taxonomy)
6. [WP_User_Query - Query users](#6-wp_user_query---query-users)
7. [WP_Comment_Query - Query comments](#7-wp_comment_query---query-comments)
8. [pre_get_posts - Modify main query](#8-pre_get_posts---modify-main-query)
9. [Custom Tables - Tạo bảng riêng với dbDelta()](#9-custom-tables---tao-bang-rieng-voi-dbdelta)
10. [Tối ưu Database](#10-toi-uu-database)
11. [Ví dụ thực tế phức tạp](#11-vi-du-thuc-te-phuc-tap)

---

## 1. Cấu trúc Database WordPress

WordPress sử dụng MySQL/MariaDB với cấu trúc mặc định gồm 12 bảng chính. Prefix mặc định là `wp_` nhưng có thể thay đổi trong `wp-config.php`.

### 1.1. wp_posts

Bảng quan trọng nhất, lưu tất cả các loại nội dung: posts, pages, custom post types, attachments, revisions, menu items.

```
+-----------------------+---------------------+------+-----+---------------------+----------------+
| Field                 | Type                | Null | Key | Default             | Extra          |
+-----------------------+---------------------+------+-----+---------------------+----------------+
| ID                    | bigint(20) unsigned | NO   | PRI | NULL                | auto_increment |
| post_author           | bigint(20) unsigned | NO   | MUL | 0                   |                |
| post_date             | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| post_date_gmt         | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| post_content          | longtext            | NO   |     | NULL                |                |
| post_title            | text                | NO   |     | NULL                |                |
| post_excerpt          | text                | NO   |     | NULL                |                |
| post_status           | varchar(20)         | NO   |     | publish             |                |
| comment_status        | varchar(20)         | NO   |     | open                |                |
| ping_status           | varchar(20)         | NO   |     | open                |                |
| post_password         | varchar(255)        | NO   |     |                     |                |
| post_name             | varchar(200)        | NO   | MUL |                     |                |
| to_ping               | text                | NO   |     | NULL                |                |
| pinged                | text                | NO   |     | NULL                |                |
| post_modified         | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| post_modified_gmt     | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| post_content_filtered | longtext            | NO   |     | NULL                |                |
| post_parent           | bigint(20) unsigned | NO   | MUL | 0                   |                |
| guid                  | varchar(255)        | NO   |     |                     |                |
| menu_order            | int(11)             | NO   |     | 0                   |                |
| post_type             | varchar(20)         | NO   | MUL | post                |                |
| post_mime_type        | varchar(100)        | NO   |     |                     |                |
| comment_count         | bigint(20)          | NO   |     | 0                   |                |
+-----------------------+---------------------+------+-----+---------------------+----------------+
```

Các giá trị `post_status` phổ biến: `publish`, `draft`, `pending`, `private`, `trash`, `auto-draft`, `inherit` (cho revisions/attachments).

Các giá trị `post_type` phổ biến: `post`, `page`, `attachment`, `revision`, `nav_menu_item`, và các custom post types.

### 1.2. wp_postmeta

Lưu metadata của posts theo dạng key-value. Đây là bảng được truy vấn nhiều nhất và cũng dễ bị chậm nhất khi dữ liệu lớn.

```
+-----------+---------------------+------+-----+---------+----------------+
| Field     | Type                | Null | Key | Default | Extra          |
+-----------+---------------------+------+-----+---------+----------------+
| meta_id   | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| post_id   | bigint(20) unsigned | NO   | MUL | 0       |                |
| meta_key  | varchar(255)        | YES  | MUL | NULL    |                |
| meta_value| longtext            | YES  |     | NULL    |                |
+-----------+---------------------+------+-----+---------+----------------+
```

Lưu ý: `meta_value` là `longtext`, không được đánh index. Nếu cần query theo `meta_value` thường xuyên, cần tạo custom index hoặc custom table.

### 1.3. wp_terms

Lưu tên các term (category, tag, custom taxonomy term).

```
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| term_id    | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| name       | varchar(200)        | NO   | MUL |         |                |
| slug       | varchar(200)        | NO   | MUL |         |                |
| term_group | bigint(10)          | NO   |     | 0       |                |
+------------+---------------------+------+-----+---------+----------------+
```

### 1.4. wp_term_taxonomy

Gán term với taxonomy cụ thể. Một term có thể thuộc nhiều taxonomy khác nhau.

```
+------------------+---------------------+------+-----+---------+----------------+
| Field            | Type                | Null | Key | Default | Extra          |
+------------------+---------------------+------+-----+---------+----------------+
| term_taxonomy_id | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| term_id          | bigint(20) unsigned | NO   | MUL | 0       |                |
| taxonomy         | varchar(32)         | NO   | MUL |         |                |
| description      | longtext            | NO   |     | NULL    |                |
| parent           | bigint(20) unsigned | NO   |     | 0       |                |
| count            | bigint(20)          | NO   |     | 0       |                |
+------------------+---------------------+------+-----+---------+----------------+
```

### 1.5. wp_term_relationships

Bảng trung gian (pivot table) liên kết objects (posts) với term_taxonomy.

```
+------------------+---------------------+------+-----+---------+-------+
| Field            | Type                | Null | Key | Default | Extra |
+------------------+---------------------+------+-----+---------+-------+
| object_id        | bigint(20) unsigned | NO   | PRI | 0       |       |
| term_taxonomy_id | bigint(20) unsigned | NO   | PRI | 0       |       |
| term_order       | int(11)             | NO   |     | 0       |       |
+------------------+---------------------+------+-----+---------+-------+
```

Mối quan hệ: `wp_posts.ID` -> `wp_term_relationships.object_id` -> `wp_term_relationships.term_taxonomy_id` -> `wp_term_taxonomy.term_taxonomy_id` -> `wp_term_taxonomy.term_id` -> `wp_terms.term_id`.

### 1.6. wp_users

Lưu thông tin người dùng.

```
+---------------------+---------------------+------+-----+---------------------+----------------+
| Field               | Type                | Null | Key | Default             | Extra          |
+---------------------+---------------------+------+-----+---------------------+----------------+
| ID                  | bigint(20) unsigned | NO   | PRI | NULL                | auto_increment |
| user_login          | varchar(60)         | NO   | MUL |                     |                |
| user_pass           | varchar(255)        | NO   |     |                     |                |
| user_nicename       | varchar(50)         | NO   | MUL |                     |                |
| user_email          | varchar(100)        | NO   | MUL |                     |                |
| user_url            | varchar(100)        | NO   |     |                     |                |
| user_registered     | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| user_activation_key | varchar(255)        | NO   |     |                     |                |
| user_status         | int(11)             | NO   |     | 0                   |                |
| display_name        | varchar(250)        | NO   |     |                     |                |
+---------------------+---------------------+------+-----+---------------------+----------------+
```

### 1.7. wp_usermeta

Lưu metadata của user, tương tự wp_postmeta.

```
+-----------+---------------------+------+-----+---------+----------------+
| Field     | Type                | Null | Key | Default | Extra          |
+-----------+---------------------+------+-----+---------+----------------+
| umeta_id  | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| user_id   | bigint(20) unsigned | NO   | MUL | 0       |                |
| meta_key  | varchar(255)        | YES  | MUL | NULL    |                |
| meta_value| longtext            | YES  |     | NULL    |                |
+-----------+---------------------+------+-----+---------+----------------+
```

Các meta_key quan trọng: `wp_capabilities` (roles), `wp_user_level`, `first_name`, `last_name`, `nickname`, `description`.

### 1.8. wp_options

Lưu toàn bộ cài đặt của WordPress và plugins. Bảng này được load rất nhiều, đặc biệt các row có `autoload = yes`.

```
+--------------+---------------------+------+-----+---------+----------------+
| Field        | Type                | Null | Key | Default | Extra          |
+--------------+---------------------+------+-----+---------+----------------+
| option_id    | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| option_name  | varchar(191)        | NO   | UNI |         |                |
| option_value | longtext            | NO   |     | NULL    |                |
| autoload     | varchar(20)         | NO   | MUL | yes     |                |
+--------------+---------------------+------+-----+---------+----------------+
```

Lưu ý: Tất cả các row có `autoload = yes` sẽ được load vào memory mỗi request. Khi sử dụng `add_option()` hoặc `update_option()`, cần cân nhắc giá trị `autoload`.

### 1.9. wp_comments

Lưu bình luận.

```
+----------------------+---------------------+------+-----+---------------------+----------------+
| Field                | Type                | Null | Key | Default             | Extra          |
+----------------------+---------------------+------+-----+---------------------+----------------+
| comment_ID           | bigint(20) unsigned | NO   | PRI | NULL                | auto_increment |
| comment_post_ID      | bigint(20) unsigned | NO   | MUL | 0                   |                |
| comment_author       | tinytext            | NO   |     | NULL                |                |
| comment_author_email | varchar(100)        | NO   | MUL |                     |                |
| comment_author_url   | varchar(200)        | NO   |     |                     |                |
| comment_author_IP    | varchar(100)        | NO   |     |                     |                |
| comment_date         | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| comment_date_gmt     | datetime            | NO   | MUL | 0000-00-00 00:00:00 |                |
| comment_content      | text                | NO   |     | NULL                |                |
| comment_karma        | int(11)             | NO   |     | 0                   |                |
| comment_approved     | varchar(20)         | NO   | MUL | 1                   |                |
| comment_agent        | varchar(255)        | NO   |     |                     |                |
| comment_type         | varchar(20)         | NO   | MUL | comment             |                |
| comment_parent       | bigint(20) unsigned | NO   | MUL | 0                   |                |
| user_id              | bigint(20) unsigned | NO   |     | 0                   |                |
+----------------------+---------------------+------+-----+---------------------+----------------+
```

### 1.10. wp_commentmeta

Lưu metadata của comments.

```
+-----------+---------------------+------+-----+---------+----------------+
| Field     | Type                | Null | Key | Default | Extra          |
+-----------+---------------------+------+-----+---------+----------------+
| meta_id   | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| comment_id| bigint(20) unsigned | NO   | MUL | 0       |                |
| meta_key  | varchar(255)        | YES  | MUL | NULL    |                |
| meta_value| longtext            | YES  |     | NULL    |                |
+-----------+---------------------+------+-----+---------+----------------+
```

### 1.11. wp_links

Bảng lưu bookmarks/links. Hiện tại ít được sử dụng (deprecated từ WP 3.5) nhưng vẫn tồn tại trong schema.

```
+-----------------+---------------------+------+-----+---------------------+----------------+
| Field           | Type                | Null | Key | Default             | Extra          |
+-----------------+---------------------+------+-----+---------------------+----------------+
| link_id         | bigint(20) unsigned | NO   | PRI | NULL                | auto_increment |
| link_url        | varchar(255)        | NO   |     |                     |                |
| link_name       | varchar(255)        | NO   |     |                     |                |
| link_image      | varchar(255)        | NO   |     |                     |                |
| link_target     | varchar(25)         | NO   |     |                     |                |
| link_description| varchar(255)        | NO   |     |                     |                |
| link_visible    | varchar(20)         | NO   | MUL | Y                   |                |
| link_owner      | bigint(20) unsigned | NO   |     | 1                   |                |
| link_rating     | int(11)             | NO   |     | 0                   |                |
| link_updated    | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
| link_rel        | varchar(255)        | NO   |     |                     |                |
| link_notes      | mediumtext          | NO   |     | NULL                |                |
| link_rss        | varchar(255)        | NO   |     |                     |                |
+-----------------+---------------------+------+-----+---------------------+----------------+
```

### Sơ đồ quan hệ giữa các bảng

```
wp_posts (ID)
    |--- wp_postmeta (post_id -> wp_posts.ID)
    |--- wp_term_relationships (object_id -> wp_posts.ID)
    |        |--- wp_term_taxonomy (term_taxonomy_id)
    |                |--- wp_terms (term_id -> wp_term_taxonomy.term_id)
    |--- wp_comments (comment_post_ID -> wp_posts.ID)
             |--- wp_commentmeta (comment_id -> wp_comments.comment_ID)

wp_users (ID)
    |--- wp_usermeta (user_id -> wp_users.ID)
    |--- wp_posts (post_author -> wp_users.ID)
    |--- wp_comments (user_id -> wp_users.ID)

wp_options (độc lập, không có foreign key)
wp_links (độc lập, ít sử dụng)
```

---

## 2. $wpdb - WordPress Database Abstraction Layer

`$wpdb` là đối tượng global cung cấp interface để tương tác trực tiếp với database. Nó là instance của class `wpdb`.

### 2.1. Truy cập $wpdb

```php
<?php
// Cách 1: Khai báo global
function my_custom_query() {
    global $wpdb;
    // Sử dụng $wpdb ở đây
}

// Cách 2: Sử dụng trong class
class My_Plugin {
    public function get_data() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->posts} LIMIT 10" );
    }
}
```

### 2.2. Các thuộc tính quan trọng của $wpdb

```php
<?php
global $wpdb;

// Tên các bảng với prefix
$wpdb->posts;            // wp_posts
$wpdb->postmeta;         // wp_postmeta
$wpdb->users;            // wp_users
$wpdb->usermeta;         // wp_usermeta
$wpdb->comments;         // wp_commentmeta
$wpdb->commentmeta;      // wp_commentmeta
$wpdb->terms;            // wp_terms
$wpdb->term_taxonomy;    // wp_term_taxonomy
$wpdb->term_relationships; // wp_term_relationships
$wpdb->options;          // wp_options
$wpdb->links;            // wp_links

// Prefix
$wpdb->prefix;           // 'wp_' (hoặc prefix tùy chỉnh)
$wpdb->base_prefix;      // prefix gốc (multisite)

// Thông tin kết nối
$wpdb->last_query;       // Câu query cuối cùng đã chạy
$wpdb->last_result;      // Kết quả cuối cùng
$wpdb->last_error;       // Lỗi cuối cùng
$wpdb->num_rows;         // Số dòng trả về từ query cuối
$wpdb->insert_id;        // ID của row vừa insert
$wpdb->rows_affected;    // Số dòng bị ảnh hưởng bởi query cuối
```

### 2.3. $wpdb->get_results()

Lấy nhiều dòng kết quả.

```php
<?php
global $wpdb;

// Trả về mảng các object (mặc định OBJECT)
$posts = $wpdb->get_results(
    "SELECT ID, post_title, post_date FROM {$wpdb->posts}
     WHERE post_status = 'publish' AND post_type = 'post'
     ORDER BY post_date DESC
     LIMIT 10"
);

foreach ( $posts as $post ) {
    echo $post->ID . ': ' . $post->post_title . "\n";
}

// Trả về mảng các mảng associative (ARRAY_A)
$posts = $wpdb->get_results(
    "SELECT ID, post_title FROM {$wpdb->posts}
     WHERE post_status = 'publish'
     LIMIT 5",
    ARRAY_A
);

foreach ( $posts as $post ) {
    echo $post['ID'] . ': ' . $post['post_title'] . "\n";
}

// Trả về mảng các mảng numeric (ARRAY_N)
$posts = $wpdb->get_results(
    "SELECT ID, post_title FROM {$wpdb->posts}
     WHERE post_status = 'publish'
     LIMIT 5",
    ARRAY_N
);

foreach ( $posts as $post ) {
    echo $post[0] . ': ' . $post[1] . "\n";  // 0 = ID, 1 = post_title
}
```

Các output type:
- `OBJECT` (mặc định): Mỗi row là một stdClass object
- `OBJECT_K`: Giống OBJECT nhưng key là giá trị cột đầu tiên
- `ARRAY_A`: Mỗi row là mảng associative
- `ARRAY_N`: Mỗi row là mảng số thứ tự

### 2.4. $wpdb->get_row()

Lấy một dòng duy nhất.

```php
<?php
global $wpdb;

// Lấy 1 row dạng object
$post = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
        42
    )
);

if ( $post ) {
    echo $post->post_title;
    echo $post->post_content;
}

// Lấy 1 row dạng array associative
$post = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT post_title, post_status FROM {$wpdb->posts} WHERE ID = %d",
        42
    ),
    ARRAY_A
);

if ( $post ) {
    echo $post['post_title'];
}

// Lấy row thứ 3 từ kết quả (0-indexed)
$third_post = $wpdb->get_row(
    "SELECT * FROM {$wpdb->posts} WHERE post_status = 'publish' LIMIT 5",
    OBJECT,
    2  // Row offset, bắt đầu từ 0
);
```

### 2.5. $wpdb->get_var()

Lấy một giá trị đơn lẻ.

```php
<?php
global $wpdb;

// Đếm số bài viết đã publish
$count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_status = 'publish' AND post_type = 'post'"
);
echo "Tổng số bài viết: {$count}";

// Lấy title của post có ID = 1
$title = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
        1
    )
);

// Lấy giá trị từ cột và row cụ thể
// get_var( query, column_offset, row_offset )
$second_col_third_row = $wpdb->get_var(
    "SELECT ID, post_title FROM {$wpdb->posts} LIMIT 5",
    1,  // cột thứ 2 (0-indexed)
    2   // row thứ 3 (0-indexed)
);
```

### 2.6. $wpdb->get_col()

Lấy toàn bộ giá trị của một cột.

```php
<?php
global $wpdb;

// Lấy tất cả post IDs đã publish
$post_ids = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_status = 'publish' AND post_type = 'post'
     ORDER BY post_date DESC"
);

// $post_ids là mảng 1 chiều: array( 42, 38, 35, 22, ... )
foreach ( $post_ids as $id ) {
    echo "Post ID: {$id}\n";
}
```

### 2.7. $wpdb->query()

Chạy bất kỳ câu SQL nào, trả về số dòng bị ảnh hưởng hoặc false nếu lỗi.

```php
<?php
global $wpdb;

// UPDATE trực tiếp
$rows_updated = $wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->posts}
         SET post_status = 'draft'
         WHERE post_author = %d AND post_status = 'publish'",
        5
    )
);
echo "Đã cập nhật {$rows_updated} bài viết";

// DELETE
$rows_deleted = $wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta}
         WHERE meta_key = %s AND post_id = %d",
        '_old_meta_key',
        42
    )
);

// Tạo bảng
$wpdb->query(
    "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}my_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) {$wpdb->get_charset_collate()}"
);
```

### 2.8. $wpdb->insert()

Chèn dữ liệu an toàn.

```php
<?php
global $wpdb;

// Insert cơ bản
$result = $wpdb->insert(
    $wpdb->prefix . 'my_table',     // Tên bảng
    array(                            // Dữ liệu (column => value)
        'name'       => 'Sản phẩm A',
        'price'      => 299000,
        'created_at' => current_time( 'mysql' ),
    ),
    array(                            // Định dạng (tùy chọn)
        '%s',  // name: string
        '%d',  // price: integer
        '%s',  // created_at: string
    )
);

if ( false !== $result ) {
    $new_id = $wpdb->insert_id;
    echo "Đã thêm với ID: {$new_id}";
} else {
    echo "Lỗi: " . $wpdb->last_error;
}

// Insert vào bảng posts (thường dùng wp_insert_post() thay vì $wpdb->insert)
$wpdb->insert(
    $wpdb->posts,
    array(
        'post_title'   => 'Bài viết mới',
        'post_content' => 'Nội dung bài viết',
        'post_status'  => 'publish',
        'post_author'  => 1,
        'post_type'    => 'post',
        'post_date'    => current_time( 'mysql' ),
        'post_date_gmt'=> current_time( 'mysql', true ),
    )
);
```

### 2.9. $wpdb->update()

Cập nhật dữ liệu an toàn.

```php
<?php
global $wpdb;

// Update cơ bản
$rows_updated = $wpdb->update(
    $wpdb->prefix . 'my_table',     // Tên bảng
    array(                            // Dữ liệu cần cập nhật
        'name'  => 'Sản phẩm B',
        'price' => 350000,
    ),
    array(                            // Điều kiện WHERE
        'id' => 5,
    ),
    array(                            // Định dạng dữ liệu
        '%s',  // name
        '%d',  // price
    ),
    array(                            // Định dạng điều kiện
        '%d',  // id
    )
);

if ( false !== $rows_updated ) {
    echo "Đã cập nhật {$rows_updated} dòng";
} else {
    echo "Lỗi: " . $wpdb->last_error;
}

// Update với nhiều điều kiện WHERE
$wpdb->update(
    $wpdb->postmeta,
    array( 'meta_value' => 'new_value' ),                    // SET
    array( 'post_id' => 42, 'meta_key' => 'my_custom_key' ), // WHERE
    array( '%s' ),                                            // format SET
    array( '%d', '%s' )                                       // format WHERE
);
```

### 2.10. $wpdb->delete()

Xóa dữ liệu an toàn.

```php
<?php
global $wpdb;

// Xóa theo ID
$rows_deleted = $wpdb->delete(
    $wpdb->prefix . 'my_table',  // Tên bảng
    array( 'id' => 5 ),          // Điều kiện WHERE
    array( '%d' )                 // Định dạng
);

// Xóa với nhiều điều kiện
$wpdb->delete(
    $wpdb->postmeta,
    array(
        'post_id'  => 42,
        'meta_key' => '_temporary_data',
    ),
    array( '%d', '%s' )
);

// Xóa tất cả meta của một post
$wpdb->delete(
    $wpdb->postmeta,
    array( 'post_id' => 42 ),
    array( '%d' )
);
```

### 2.11. $wpdb->prepare()

Phương thức QUAN TRỌNG NHẤT để ngăn chặn SQL Injection. Luôn sử dụng khi truyền dữ liệu từ người dùng vào câu query.

```php
<?php
global $wpdb;

// Các placeholder:
// %d = số nguyên (integer)
// %f = số thực (float)
// %s = chuỗi (string)

// Ví dụ cơ bản
$safe_query = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE ID = %d AND post_status = %s",
    42,
    'publish'
);
$result = $wpdb->get_row( $safe_query );

// Với LIKE - sử dụng $wpdb->esc_like()
$search = 'wordpress';
$like   = '%' . $wpdb->esc_like( $search ) . '%';
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts}
         WHERE post_title LIKE %s AND post_status = %s",
        $like,
        'publish'
    )
);

// Với IN clause - sử dụng nhiều placeholder
$post_ids = array( 1, 5, 10, 15 );
$placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID IN ({$placeholders})",
        ...$post_ids
    )
);

// KHÔNG BAO GIỜ làm như này (SQL Injection!):
// $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID = " . $_GET['id'] );
// $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE post_title = '{$_POST['title']}'" );
```

### 2.12. Debug $wpdb

```php
<?php
global $wpdb;

// Bật debug mode trong wp-config.php
// define( 'SAVEQUERIES', true );

// Xem tất cả các query đã chạy
if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
    echo '<pre>';
    print_r( $wpdb->queries );
    echo '</pre>';
}

// Xem query cuối cùng
echo $wpdb->last_query;

// Xem lỗi cuối cùng
echo $wpdb->last_error;

// Hiển thị lỗi trực tiếp
$wpdb->show_errors();
$wpdb->hide_errors();

// In lỗi ra màn hình
$wpdb->print_error();

// Suppress errors
$wpdb->suppress_errors( true );
```

---

## 3. WP_Query - Giải thích chi tiết

`WP_Query` là lớp chính để truy vấn posts trong WordPress. Nó là nền tảng của main query và cũng được sử dụng để tạo custom queries.

### 3.1. Cách sử dụng cơ bản

```php
<?php
// Cách 1: Tạo instance mới
$query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
) );

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<h2>' . get_the_title() . '</h2>';
        echo '<div>' . get_the_content() . '</div>';
    }
    wp_reset_postdata(); // LUÔN GỌI SAU KHI DÙNG the_post()
}

// Cách 2: Sử dụng get_posts() (wrapper của WP_Query)
$posts = get_posts( array(
    'post_type'      => 'product',
    'posts_per_page' => 5,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

foreach ( $posts as $post ) {
    setup_postdata( $post );
    echo get_the_title();
}
wp_reset_postdata();
```

### 3.2. Tham số post_type

```php
<?php
// Một post type
$query = new WP_Query( array(
    'post_type' => 'post',
) );

// Nhiều post types
$query = new WP_Query( array(
    'post_type' => array( 'post', 'page', 'product' ),
) );

// Tất cả post types
$query = new WP_Query( array(
    'post_type' => 'any',
) );
```

### 3.3. Tham số posts_per_page và phân trang

```php
<?php
// Số lượng bài viết
$query = new WP_Query( array(
    'posts_per_page' => 10,     // 10 bài mỗi trang
) );

// Tất cả bài viết (không phân trang)
$query = new WP_Query( array(
    'posts_per_page' => -1,     // Lấy hết, cẩn thận với dữ liệu lớn!
    'no_found_rows'  => true,   // Bỏ qua SQL_CALC_FOUND_ROWS để tăng tốc
) );

// Phân trang
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'paged'          => $paged,
) );

// Hiển thị phân trang
if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        // Hiển thị bài viết
    }

    // Pagination links
    echo paginate_links( array(
        'total'   => $query->max_num_pages,
        'current' => $paged,
    ) );

    wp_reset_postdata();
}

// Offset - bỏ qua N bài đầu tiên
$query = new WP_Query( array(
    'posts_per_page' => 5,
    'offset'         => 3,  // Bỏ qua 3 bài đầu, lấy từ bài thứ 4
) );
```

### 3.4. Tham số tax_query

```php
<?php
// Query theo 1 taxonomy
$query = new WP_Query( array(
    'post_type' => 'post',
    'tax_query' => array(
        array(
            'taxonomy' => 'category',
            'field'    => 'slug',       // 'term_id', 'slug', 'name'
            'terms'    => 'tin-tuc',
        ),
    ),
) );

// Query theo nhiều taxonomy với AND
$query = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => array( 'ao', 'quan' ),
            'operator' => 'IN',        // IN, NOT IN, AND, EXISTS, NOT EXISTS
        ),
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => 'khuyen-mai',
        ),
    ),
) );

// Query theo nhiều taxonomy với OR
$query = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'OR',
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => 'ao',
        ),
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => 'moi',
        ),
    ),
) );

// NOT IN - Loại trừ
$query = new WP_Query( array(
    'post_type' => 'post',
    'tax_query' => array(
        array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => array( 5, 10 ),
            'operator' => 'NOT IN',
        ),
    ),
) );

// Nested tax_query
$query = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'OR',
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => 'dien-thoai',
        ),
        array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'phu-kien',
            ),
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => 'sale',
            ),
        ),
    ),
) );
```

### 3.5. Tham số meta_query

```php
<?php
// Query theo 1 meta field
$query = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        array(
            'key'     => 'price',
            'value'   => 500000,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ),
    ),
) );

// Query theo nhiều meta fields với AND
$query = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'     => 'price',
            'value'   => array( 100000, 500000 ),
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => 'in_stock',
            'value'   => '1',
            'compare' => '=',
        ),
    ),
) );

// Các giá trị compare hợp lệ:
// '='          : Bằng (mặc định)
// '!='         : Khác
// '>'          : Lớn hơn
// '>='         : Lớn hơn hoặc bằng
// '<'          : Nhỏ hơn
// '<='         : Nhỏ hơn hoặc bằng
// 'LIKE'       : Chứa chuỗi
// 'NOT LIKE'   : Không chứa chuỗi
// 'IN'         : Trong danh sách
// 'NOT IN'     : Không trong danh sách
// 'BETWEEN'    : Giữa 2 giá trị
// 'NOT BETWEEN': Không giữa 2 giá trị
// 'EXISTS'     : Meta key tồn tại
// 'NOT EXISTS' : Meta key không tồn tại
// 'REGEXP'     : Khớp biểu thức chính quy
// 'NOT REGEXP' : Không khớp biểu thức chính quy

// Các giá trị type hợp lệ:
// 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'

// Sắp xếp theo meta value với named meta query
$query = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        'price_clause' => array(
            'key'     => 'price',
            'value'   => 0,
            'compare' => '>',
            'type'    => 'NUMERIC',
        ),
        'rating_clause' => array(
            'key'     => 'rating',
            'compare' => 'EXISTS',
            'type'    => 'NUMERIC',
        ),
    ),
    'orderby' => array(
        'rating_clause' => 'DESC',
        'price_clause'  => 'ASC',
    ),
) );

// Kiểm tra meta key tồn tại
$query = new WP_Query( array(
    'post_type'  => 'post',
    'meta_query' => array(
        array(
            'key'     => '_thumbnail_id',
            'compare' => 'EXISTS',
        ),
    ),
) );
```

### 3.6. Tham số date_query

```php
<?php
// Bài viết trong năm 2024
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'year' => 2024,
        ),
    ),
) );

// Bài viết từ tháng 1 đến tháng 6 năm 2024
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'after'     => 'January 1st, 2024',
            'before'    => 'July 1st, 2024',
            'inclusive' => true,
        ),
    ),
) );

// Bài viết trong 30 ngày gần nhất
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'after' => '30 days ago',
        ),
    ),
) );

// Bài viết được sửa trong tuần này (dùng post_modified)
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'column' => 'post_modified',
            'after'  => '1 week ago',
        ),
    ),
) );

// Bài viết đăng vào buổi sáng (8h-12h)
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'hour'    => 8,
            'compare' => '>=',
        ),
        array(
            'hour'    => 12,
            'compare' => '<=',
        ),
        'relation' => 'AND',
    ),
) );
```

### 3.7. Tham số orderby và order

```php
<?php
// Sắp xếp cơ bản
$query = new WP_Query( array(
    'orderby' => 'date',    // Theo ngày đăng
    'order'   => 'DESC',    // Giảm dần (mới nhất trước)
) );

// Các giá trị orderby phổ biến:
// 'none'           : Không sắp xếp
// 'ID'             : Theo post ID
// 'author'         : Theo tác giả
// 'title'          : Theo tiêu đề
// 'name'           : Theo post slug
// 'date'           : Theo ngày đăng (mặc định)
// 'modified'       : Theo ngày sửa
// 'parent'         : Theo parent ID
// 'rand'           : Ngẫu nhiên (CHẬM, tránh dùng với dữ liệu lớn)
// 'comment_count'  : Theo số bình luận
// 'menu_order'     : Theo thứ tự menu
// 'meta_value'     : Theo giá trị meta (cần thêm meta_key)
// 'meta_value_num' : Theo giá trị meta dạng số
// 'post__in'       : Theo thứ tự trong mảng post__in

// Sắp xếp theo meta_value
$query = new WP_Query( array(
    'meta_key' => 'price',
    'orderby'  => 'meta_value_num',
    'order'    => 'ASC',
) );

// Sắp xếp theo nhiều tiêu chí
$query = new WP_Query( array(
    'orderby' => array(
        'menu_order' => 'ASC',
        'date'       => 'DESC',
    ),
) );

// Giữ thứ tự của mảng post__in
$query = new WP_Query( array(
    'post__in' => array( 5, 3, 8, 1, 10 ),
    'orderby'  => 'post__in',
) );
```

### 3.8. Tham số tìm kiếm (s) và author

```php
<?php
// Tìm kiếm
$query = new WP_Query( array(
    'post_type' => 'post',
    's'         => 'wordpress tutorial',
) );

// Tìm kiếm chính xác (phrase)
$query = new WP_Query( array(
    's'      => '"wordpress tutorial"',  // Dùng ngoặc kép
    'exact'  => true,                    // Tìm chính xác
) );

// Theo tác giả
$query = new WP_Query( array(
    'author' => 1,                       // Theo user ID
) );

$query = new WP_Query( array(
    'author_name' => 'admin',            // Theo user_nicename
) );

// Nhiều tác giả
$query = new WP_Query( array(
    'author__in' => array( 1, 5, 10 ),
) );

// Loại trừ tác giả
$query = new WP_Query( array(
    'author__not_in' => array( 3, 7 ),
) );
```

### 3.9. Các tham số khác

```php
<?php
// Theo post ID
$query = new WP_Query( array(
    'p' => 42,                            // 1 post theo ID
) );

$query = new WP_Query( array(
    'post__in' => array( 1, 5, 10, 42 ), // Nhiều posts theo ID
) );

$query = new WP_Query( array(
    'post__not_in' => array( 3, 7 ),     // Loại trừ posts
) );

// Theo slug
$query = new WP_Query( array(
    'name' => 'bai-viet-mau',            // post slug
) );

// Theo post parent
$query = new WP_Query( array(
    'post_parent'    => 10,              // Các trang con của trang ID=10
    'post_type'      => 'page',
) );

$query = new WP_Query( array(
    'post_parent__in' => array( 10, 20 ),
) );

// Theo post status
$query = new WP_Query( array(
    'post_status' => 'draft',
) );

$query = new WP_Query( array(
    'post_status' => array( 'publish', 'pending', 'draft' ),
) );

// Comment parameters
$query = new WP_Query( array(
    'comment_count' => array(
        'value'   => 5,
        'compare' => '>=',
    ),
) );

// Password protected posts
$query = new WP_Query( array(
    'has_password' => true,   // Chỉ lấy bài có mật khẩu
) );

// Sticky posts
$query = new WP_Query( array(
    'post__in'            => get_option( 'sticky_posts' ),
    'ignore_sticky_posts' => true,
) );

// Performance - no_found_rows
$query = new WP_Query( array(
    'posts_per_page' => 5,
    'no_found_rows'  => true,   // Không đếm tổng (nhanh hơn, không có pagination)
) );

// Chỉ lấy trường cần thiết
$query = new WP_Query( array(
    'fields' => 'ids',          // Chỉ lấy mảng IDs
) );

$query = new WP_Query( array(
    'fields' => 'id=>parent',   // Chỉ lấy ID và parent
) );

// Cache
$query = new WP_Query( array(
    'update_post_meta_cache' => false,  // Không load meta cache
    'update_post_term_cache' => false,  // Không load term cache
) );
```

### 3.10. Thuộc tính của WP_Query

```php
<?php
$query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 10 ) );

$query->posts;          // Mảng các WP_Post objects
$query->post_count;     // Số bài trong trang hiện tại
$query->found_posts;    // Tổng số bài (tất cả các trang)
$query->max_num_pages;  // Tổng số trang
$query->current_post;   // Index bài hiện tại trong loop (-1 trước loop)
$query->post;           // Bài hiện tại
$query->is_single();    // True nếu là trang single post
$query->is_page();      // True nếu là trang page
$query->is_archive();   // True nếu là trang archive
$query->is_search();    // True nếu là trang tìm kiếm
$query->request;        // Câu SQL đã chạy
```

---

## 4. WP_Meta_Query - Query theo meta fields

`WP_Meta_Query` là lớp xử lý meta_query bên trong WP_Query. Có thể sử dụng trực tiếp hoặc thông qua tham số `meta_query` của WP_Query.

```php
<?php
// Sử dụng trực tiếp WP_Meta_Query
$meta_query = new WP_Meta_Query( array(
    'relation' => 'AND',
    array(
        'key'     => 'color',
        'value'   => 'blue',
        'compare' => '=',
    ),
    array(
        'key'     => 'price',
        'value'   => array( 100, 500 ),
        'compare' => 'BETWEEN',
        'type'    => 'NUMERIC',
    ),
) );

// Lấy SQL từ WP_Meta_Query (để debug hoặc sử dụng với $wpdb)
$meta_query_sql = $meta_query->get_sql(
    'post',                // Meta type: 'post', 'user', 'comment', 'term'
    $wpdb->posts,          // Bảng chính
    'ID',                  // Cột primary key của bảng chính
    null                   // WP_Query object (tùy chọn)
);

// $meta_query_sql gồm:
// $meta_query_sql['join']  => INNER JOIN wp_postmeta ON ...
// $meta_query_sql['where'] => AND ( (wp_postmeta.meta_key = 'color' AND ...) )

// Ví dụ phức tạp: sản phẩm giá 100k-500k, màu xanh HOẶC đỏ, còn hàng
$query = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        // Điều kiện giá
        'price_clause' => array(
            'key'     => '_price',
            'value'   => array( 100000, 500000 ),
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ),
        // Điều kiện màu sắc (OR)
        array(
            'relation' => 'OR',
            array(
                'key'     => '_color',
                'value'   => 'xanh',
                'compare' => '=',
            ),
            array(
                'key'     => '_color',
                'value'   => 'do',
                'compare' => '=',
            ),
        ),
        // Điều kiện còn hàng
        array(
            'key'     => '_stock_status',
            'value'   => 'instock',
            'compare' => '=',
        ),
    ),
    'orderby' => 'price_clause',
    'order'   => 'ASC',
) );
```

---

## 5. WP_Tax_Query - Query theo taxonomy

`WP_Tax_Query` xử lý tax_query bên trong WP_Query.

```php
<?php
// Sử dụng trực tiếp WP_Tax_Query
$tax_query = new WP_Tax_Query( array(
    'relation' => 'AND',
    array(
        'taxonomy'         => 'category',
        'field'            => 'slug',
        'terms'            => array( 'tin-tuc', 'cong-nghe' ),
        'operator'         => 'IN',
        'include_children' => true,  // Bao gồm các category con (mặc định true)
    ),
    array(
        'taxonomy'         => 'post_tag',
        'field'            => 'slug',
        'terms'            => 'noi-bat',
        'operator'         => 'IN',
    ),
) );

// Lấy SQL
$tax_query_sql = $tax_query->get_sql( $wpdb->posts, 'ID' );

// Ví dụ thực tế: Sản phẩm thuộc danh mục "dien-thoai" HOẶC "may-tinh"
// VÀ có tag "khuyen-mai"
// VÀ KHÔNG thuộc thương hiệu "nokia"
$query = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',
        // Danh mục (OR)
        array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'dien-thoai',
            ),
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'may-tinh',
            ),
        ),
        // Có tag khuyến mãi
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => 'khuyen-mai',
        ),
        // Không phải Nokia
        array(
            'taxonomy' => 'brand',
            'field'    => 'slug',
            'terms'    => 'nokia',
            'operator' => 'NOT IN',
        ),
    ),
) );

// Các operator của tax_query:
// 'IN'         : Thuộc bất kỳ term nào (mặc định)
// 'NOT IN'     : Không thuộc bất kỳ term nào
// 'AND'        : Thuộc TẤT CẢ các terms
// 'EXISTS'     : Có gán bất kỳ term nào của taxonomy
// 'NOT EXISTS' : Không có gán term nào của taxonomy
```

---

## 6. WP_User_Query - Query users

```php
<?php
// Query cơ bản
$user_query = new WP_User_Query( array(
    'role'    => 'subscriber',
    'orderby' => 'registered',
    'order'   => 'DESC',
    'number'  => 20,
) );

$users = $user_query->get_results();
foreach ( $users as $user ) {
    echo $user->display_name . ' (' . $user->user_email . ")\n";
}

// Tổng số users
$total = $user_query->get_total();
echo "Tổng: {$total} người dùng";

// Tìm kiếm user
$user_query = new WP_User_Query( array(
    'search'         => '*nguyen*',         // Tìm kiếm (wildcard *)
    'search_columns' => array(              // Tìm trong các cột
        'user_login',
        'user_nicename',
        'user_email',
        'display_name',
    ),
) );

// Query theo nhiều roles
$user_query = new WP_User_Query( array(
    'role__in' => array( 'editor', 'author' ),
) );

// Loại trừ role
$user_query = new WP_User_Query( array(
    'role__not_in' => array( 'subscriber' ),
) );

// Query theo meta
$user_query = new WP_User_Query( array(
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'     => 'city',
            'value'   => 'Ha Noi',
            'compare' => '=',
        ),
        array(
            'key'     => 'age',
            'value'   => array( 18, 30 ),
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ),
    ),
) );

// Date query cho users
$user_query = new WP_User_Query( array(
    'date_query' => array(
        array(
            'after'  => '2024-01-01',
            'before' => '2024-12-31',
            'inclusive' => true,
        ),
    ),
) );

// Sắp xếp theo meta
$user_query = new WP_User_Query( array(
    'meta_key' => 'last_login',
    'orderby'  => 'meta_value',
    'order'    => 'DESC',
) );

// Phân trang
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$per_page = 20;

$user_query = new WP_User_Query( array(
    'number' => $per_page,
    'offset' => ( $paged - 1 ) * $per_page,
) );

$total_users = $user_query->get_total();
$total_pages = ceil( $total_users / $per_page );

// Các tham số khác
$user_query = new WP_User_Query( array(
    'include'     => array( 1, 5, 10 ),  // Chỉ lấy user IDs này
    'exclude'     => array( 3 ),          // Loại trừ user IDs này
    'blog_id'     => 1,                   // Blog ID (multisite)
    'count_total' => true,                // Đếm tổng (mặc định true)
    'fields'      => 'all',              // 'all', 'all_with_meta', 'ID', 'display_name', 'user_login', hoặc mảng
    'who'         => 'authors',           // Chỉ lấy users là authors
    'has_published_posts' => true,        // Chỉ lấy users có bài đã publish
) );
```

---

## 7. WP_Comment_Query - Query comments

```php
<?php
// Query cơ bản
$comment_query = new WP_Comment_Query( array(
    'post_id' => 42,
    'status'  => 'approve',
    'orderby' => 'comment_date',
    'order'   => 'DESC',
    'number'  => 20,
) );

$comments = $comment_query->comments;
foreach ( $comments as $comment ) {
    echo $comment->comment_author . ': ' . $comment->comment_content . "\n";
}

// Tìm kiếm comments
$comment_query = new WP_Comment_Query( array(
    'search' => 'wordpress',
) );

// Comments của 1 user
$comment_query = new WP_Comment_Query( array(
    'user_id' => 5,
) );

// Comments theo post type
$comment_query = new WP_Comment_Query( array(
    'post_type' => 'product',
    'status'    => 'approve',
) );

// Query với meta
$comment_query = new WP_Comment_Query( array(
    'meta_query' => array(
        array(
            'key'     => 'rating',
            'value'   => 4,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ),
    ),
) );

// Date query
$comment_query = new WP_Comment_Query( array(
    'date_query' => array(
        array(
            'after' => '1 month ago',
        ),
    ),
) );

// Đếm comments
$count = get_comments( array(
    'post_id' => 42,
    'status'  => 'approve',
    'count'   => true,  // Trả về số lượng thay vì danh sách
) );

// Các giá trị status:
// 'approve' hoặc 'approved' hoặc '1'  : Đã duyệt
// 'hold' hoặc 'unapproved' hoặc '0'   : Chờ duyệt
// 'spam'                                : Spam
// 'trash'                               : Đã xóa
// 'all'                                 : Tất cả

// Comments hierarchical (nested)
$comment_query = new WP_Comment_Query( array(
    'post_id'      => 42,
    'hierarchical' => 'threaded',   // Trả về comments dạng cây
    'status'       => 'approve',
) );

// Phân trang comments
$cpage = get_query_var( 'cpage' ) ? get_query_var( 'cpage' ) : 1;
$comment_query = new WP_Comment_Query( array(
    'post_id' => 42,
    'status'  => 'approve',
    'number'  => 20,
    'offset'  => ( $cpage - 1 ) * 20,
) );
```

---

## 8. pre_get_posts - Modify main query

`pre_get_posts` là hook cho phép thay đổi tham số của WP_Query TRƯỚC khi nó chạy. Đây là cách đúng để modify main query thay vì tạo WP_Query mới.

```php
<?php
// Thay đổi số bài trên trang archive
add_action( 'pre_get_posts', 'custom_archive_posts_per_page' );
function custom_archive_posts_per_page( $query ) {
    // QUAN TRỌNG: Chỉ modify main query, không phải custom queries
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( $query->is_category() ) {
            $query->set( 'posts_per_page', 20 );
        }
    }
}

// Thêm custom post type vào trang chủ và archive
add_action( 'pre_get_posts', 'add_custom_post_type_to_query' );
function add_custom_post_type_to_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( $query->is_home() || $query->is_archive() ) {
            $query->set( 'post_type', array( 'post', 'product', 'event' ) );
        }
    }
}

// Loại trừ category khỏi trang chủ
add_action( 'pre_get_posts', 'exclude_category_from_home' );
function exclude_category_from_home( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
        $query->set( 'cat', '-5,-10' );  // Loại trừ category ID 5 và 10
    }
}

// Thay đổi order cho trang tìm kiếm
add_action( 'pre_get_posts', 'custom_search_order' );
function custom_search_order( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
        $query->set( 'orderby', 'modified' );
        $query->set( 'order', 'DESC' );
    }
}

// Thêm meta_query vào main query
add_action( 'pre_get_posts', 'filter_by_custom_field' );
function filter_by_custom_field( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'product' ) ) {
        // Lấy tham số từ URL: ?min_price=100&max_price=500
        $min_price = isset( $_GET['min_price'] ) ? intval( $_GET['min_price'] ) : 0;
        $max_price = isset( $_GET['max_price'] ) ? intval( $_GET['max_price'] ) : 0;

        if ( $min_price > 0 && $max_price > 0 ) {
            $meta_query = $query->get( 'meta_query' );
            if ( ! is_array( $meta_query ) ) {
                $meta_query = array();
            }
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => array( $min_price, $max_price ),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
            $query->set( 'meta_query', $meta_query );
        }
    }
}

// Modify admin query
add_action( 'pre_get_posts', 'admin_filter_by_author' );
function admin_filter_by_author( $query ) {
    if ( is_admin() && $query->is_main_query() ) {
        $screen = get_current_screen();
        if ( $screen && 'edit-post' === $screen->id ) {
            // Nếu không phải admin, chỉ hiển thị bài của chính mình
            if ( ! current_user_can( 'manage_options' ) ) {
                $query->set( 'author', get_current_user_id() );
            }
        }
    }
}
```

Lưu ý quan trọng về `pre_get_posts`:
- Luôn kiểm tra `$query->is_main_query()` để tránh ảnh hưởng đến custom queries, widget queries, menu queries.
- Luôn kiểm tra `! is_admin()` nếu chỉ muốn thay đổi ở frontend.
- KHÔNG nên sử dụng `query_posts()` - nó thay thế main query và gây ra nhiều vấn đề. Luôn dùng `pre_get_posts` hoặc tạo `WP_Query` mới.

---

## 9. Custom Tables - Tạo bảng riêng với dbDelta()

Khi nào nên tạo custom table thay vì sử dụng postmeta:
- Dữ liệu có cấu trúc cố định, không phải key-value.
- Cần query phức tạp với JOIN, GROUP BY, aggregate functions.
- Dữ liệu lớn cần đánh index riêng.
- Dữ liệu không liên quan đến posts/users/comments.

### 9.1. Tạo bảng khi activate plugin

```php
<?php
/**
 * Plugin Name: My Custom Table Plugin
 */

register_activation_hook( __FILE__, 'my_plugin_create_tables' );

function my_plugin_create_tables() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'my_orders';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        product_id bigint(20) unsigned NOT NULL DEFAULT 0,
        quantity int(11) NOT NULL DEFAULT 1,
        total_price decimal(10,2) NOT NULL DEFAULT 0.00,
        status varchar(20) NOT NULL DEFAULT 'pending',
        order_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        notes text,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY product_id (product_id),
        KEY status (status),
        KEY order_date (order_date)
    ) {$charset_collate};";

    // Cần include file upgrade.php để sử dụng dbDelta()
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Lưu version của database schema
    update_option( 'my_plugin_db_version', '1.0' );
}
```

Lưu ý về `dbDelta()`:
- Mỗi trường trong định nghĩa cột phải dùng CHÍNH XÁC 1 dấu cách giữa các phần.
- PRIMARY KEY phải có HAI dấu cách trước dấu ngoặc: `PRIMARY KEY  (id)`.
- Phải dùng KEY thay vì INDEX.
- Không được dùng dấu phẩy sau trường cuối cùng trước dấu ngoặc đóng.
- Mỗi trường phải nằm trên 1 dòng.

### 9.2. Cập nhật schema khi update plugin

```php
<?php
add_action( 'plugins_loaded', 'my_plugin_check_db_version' );

function my_plugin_check_db_version() {
    $current_version = get_option( 'my_plugin_db_version', '0' );

    if ( version_compare( $current_version, '1.1', '<' ) ) {
        my_plugin_upgrade_db_to_1_1();
    }

    if ( version_compare( $current_version, '1.2', '<' ) ) {
        my_plugin_upgrade_db_to_1_2();
    }
}

function my_plugin_upgrade_db_to_1_1() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'my_orders';
    $charset_collate = $wpdb->get_charset_collate();

    // Thêm cột mới - dbDelta() tự động detect và chỉ thêm cột chưa có
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        product_id bigint(20) unsigned NOT NULL DEFAULT 0,
        quantity int(11) NOT NULL DEFAULT 1,
        total_price decimal(10,2) NOT NULL DEFAULT 0.00,
        discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        status varchar(20) NOT NULL DEFAULT 'pending',
        payment_method varchar(50) NOT NULL DEFAULT '',
        order_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        notes text,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY product_id (product_id),
        KEY status (status),
        KEY order_date (order_date)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'my_plugin_db_version', '1.1' );
}

function my_plugin_upgrade_db_to_1_2() {
    global $wpdb;

    // Thêm bảng mới
    $table_name      = $wpdb->prefix . 'my_order_items';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        order_id bigint(20) unsigned NOT NULL,
        product_id bigint(20) unsigned NOT NULL,
        quantity int(11) NOT NULL DEFAULT 1,
        unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY  (id),
        KEY order_id (order_id),
        KEY product_id (product_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'my_plugin_db_version', '1.2' );
}
```

### 9.3. Xóa bảng khi uninstall plugin

```php
<?php
// File: uninstall.php (đặt ở root của plugin)

// Kiểm tra WordPress gọi file này
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Xóa các bảng
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_orders" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_order_items" );

// Xóa options
delete_option( 'my_plugin_db_version' );
delete_option( 'my_plugin_settings' );

// Xóa user meta
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'my_plugin_%'" );
```

### 9.4. CRUD với custom table

```php
<?php
class My_Order_Model {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'my_orders';
    }

    /**
     * Tạo đơn hàng mới
     */
    public function create( $data ) {
        global $wpdb;

        $defaults = array(
            'user_id'     => 0,
            'product_id'  => 0,
            'quantity'    => 1,
            'total_price' => 0.00,
            'status'      => 'pending',
            'order_date'  => current_time( 'mysql' ),
            'notes'       => '',
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'     => $data['user_id'],
                'product_id'  => $data['product_id'],
                'quantity'    => $data['quantity'],
                'total_price' => $data['total_price'],
                'status'      => $data['status'],
                'order_date'  => $data['order_date'],
                'notes'       => $data['notes'],
            ),
            array( '%d', '%d', '%d', '%f', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_insert_error', $wpdb->last_error );
        }

        return $wpdb->insert_id;
    }

    /**
     * Lấy đơn hàng theo ID
     */
    public function get( $id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Lấy danh sách đơn hàng với filter
     */
    public function get_list( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'user_id'  => 0,
            'status'   => '',
            'orderby'  => 'order_date',
            'order'    => 'DESC',
            'per_page' => 20,
            'page'     => 1,
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );
        $values = array();

        if ( $args['user_id'] > 0 ) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode( ' AND ', $where );

        // Whitelist orderby và order để tránh SQL injection
        $allowed_orderby = array( 'id', 'user_id', 'total_price', 'status', 'order_date' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'order_date';
        $order   = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $sql = "SELECT * FROM {$this->table_name}
                WHERE {$where_clause}
                ORDER BY {$orderby} {$order}
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if ( ! empty( $values ) ) {
            $sql = $wpdb->prepare( $sql, $values );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Đếm tổng số đơn hàng theo điều kiện
     */
    public function count( $args = array() ) {
        global $wpdb;

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['user_id'] ) ) {
            $where[] = 'user_id = %d';
            $values[] = intval( $args['user_id'] );
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode( ' AND ', $where );

        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";

        if ( ! empty( $values ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare( $sql, $values ) );
        }

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Cập nhật đơn hàng
     */
    public function update( $id, $data ) {
        global $wpdb;

        $update_data   = array();
        $update_format = array();

        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $update_format[] = '%s';
        }

        if ( isset( $data['quantity'] ) ) {
            $update_data['quantity'] = $data['quantity'];
            $update_format[] = '%d';
        }

        if ( isset( $data['total_price'] ) ) {
            $update_data['total_price'] = $data['total_price'];
            $update_format[] = '%f';
        }

        if ( isset( $data['notes'] ) ) {
            $update_data['notes'] = $data['notes'];
            $update_format[] = '%s';
        }

        if ( empty( $update_data ) ) {
            return new WP_Error( 'no_data', 'Không có dữ liệu để cập nhật.' );
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array( 'id' => $id ),
            $update_format,
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_update_error', $wpdb->last_error );
        }

        return $result;
    }

    /**
     * Xóa đơn hàng
     */
    public function delete( $id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );
    }
}
```

---

## 10. Tối ưu Database

### 10.1. Index

```php
<?php
// Thêm index vào bảng có sẵn
global $wpdb;

// Thêm index cho meta_value (postmeta) - THẬN TRỌNG với bảng lớn
$wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value_index (meta_value(191))" );

// Thêm composite index
$wpdb->query(
    "ALTER TABLE {$wpdb->prefix}my_orders
     ADD INDEX user_status (user_id, status)"
);

// Kiểm tra index hiện có
$indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts}" );

// Xem execution plan của query
$explain = $wpdb->get_results(
    "EXPLAIN SELECT * FROM {$wpdb->posts}
     WHERE post_type = 'product' AND post_status = 'publish'"
);
```

### 10.2. Object Cache và Transients

```php
<?php
// Transients - Cache dữ liệu vào database (hoặc object cache nếu có)
function get_popular_posts() {
    $cache_key = 'popular_posts_list';
    $popular = get_transient( $cache_key );

    if ( false === $popular ) {
        // Cache miss - chạy query
        $popular = new WP_Query( array(
            'post_type'      => 'post',
            'posts_per_page' => 10,
            'orderby'        => 'comment_count',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ) );

        // Lưu cache 1 giờ
        set_transient( $cache_key, $popular, HOUR_IN_SECONDS );
    }

    return $popular;
}

// Xóa cache khi có bài viết mới
add_action( 'save_post', function( $post_id ) {
    delete_transient( 'popular_posts_list' );
} );

// Sử dụng wp_cache (Object Cache) - chỉ tồn tại trong 1 request
// (trừ khi có persistent object cache như Redis/Memcached)
function get_user_order_count( $user_id ) {
    $cache_key   = 'order_count_' . $user_id;
    $cache_group = 'my_orders';
    $count       = wp_cache_get( $cache_key, $cache_group );

    if ( false === $count ) {
        global $wpdb;
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}my_orders WHERE user_id = %d",
                $user_id
            )
        );
        wp_cache_set( $cache_key, $count, $cache_group, 3600 );
    }

    return (int) $count;
}
```

### 10.3. Tối ưu WP_Query

```php
<?php
// 1. Sử dụng no_found_rows khi không cần pagination
$query = new WP_Query( array(
    'posts_per_page' => 5,
    'no_found_rows'  => true,  // Bỏ qua SQL_CALC_FOUND_ROWS
) );

// 2. Chỉ lấy fields cần thiết
$ids = new WP_Query( array(
    'fields'         => 'ids',    // Chỉ lấy IDs
    'posts_per_page' => -1,
    'no_found_rows'  => true,
) );

// 3. Tắt meta/term cache khi không cần
$query = new WP_Query( array(
    'posts_per_page'         => 10,
    'update_post_meta_cache' => false,  // Không preload meta
    'update_post_term_cache' => false,  // Không preload terms
) );

// 4. Tránh posts_per_page = -1 với dữ liệu lớn
// Thay vào đó, sử dụng phân trang hoặc giới hạn hợp lý

// 5. Tránh orderby = 'rand' với dữ liệu lớn
// Thay thế bằng:
$random_ids = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_type = 'post' AND post_status = 'publish'
     ORDER BY RAND() LIMIT 5"
);
$query = new WP_Query( array(
    'post__in' => $random_ids,
    'orderby'  => 'post__in',
) );
```

### 10.4. Query Monitor Plugin

Query Monitor là plugin không thể thiếu để debug và tối ưu database queries.

```php
<?php
// Cài đặt: wp plugin install query-monitor --activate

// Query Monitor sẽ tự động hiển thị:
// - Tất cả SQL queries và thời gian thực thi
// - Duplicate queries
// - Slow queries
// - Queries by component (theme, plugin, core)
// - PHP errors
// - HTTP API calls
// - Transients
// - Hooks và actions

// Debug riêng với QM:
do_action( 'qm/debug', 'Thông tin debug' );
do_action( 'qm/info', array( 'key' => 'value' ) );
do_action( 'qm/warning', 'Cảnh báo!' );
do_action( 'qm/error', 'Lỗi nghiêm trọng!' );
```

### 10.5. Các mẹo tối ưu khác

```php
<?php
// 1. Sử dụng autoload = 'no' cho options lớn hoặc ít dùng
add_option( 'my_large_data', $data, '', 'no' );  // autoload = no

// 2. Giảm số lượng revisions
// Thêm vào wp-config.php:
// define( 'WP_POST_REVISIONS', 5 );  // Giới hạn 5 revisions
// define( 'WP_POST_REVISIONS', false ); // Tắt revisions

// 3. Dọn dẹp database định kỳ
function cleanup_old_data() {
    global $wpdb;

    // Xóa revisions cũ hơn 30 ngày
    $wpdb->query(
        "DELETE FROM {$wpdb->posts}
         WHERE post_type = 'revision'
         AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Xóa orphan postmeta
    $wpdb->query(
        "DELETE pm FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.ID IS NULL"
    );

    // Xóa transients hết hạn
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_timeout_%'
         AND option_value < UNIX_TIMESTAMP()"
    );
}

// 4. Optimize tables
function optimize_database_tables() {
    global $wpdb;

    $tables = array(
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->options,
        $wpdb->comments,
        $wpdb->commentmeta,
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "OPTIMIZE TABLE {$table}" );
    }
}
```

---

## 11. Ví dụ thực tế phức tạp

### 11.1. Hệ thống tìm kiếm sản phẩm nâng cao

```php
<?php
/**
 * Tìm kiếm sản phẩm với nhiều tiêu chí:
 * - Từ khóa
 * - Khoảng giá
 * - Danh mục
 * - Thuộc tính (màu sắc, kích thước)
 * - Sắp xếp
 * - Phân trang
 */
function advanced_product_search( $args = array() ) {
    $defaults = array(
        'keyword'    => '',
        'min_price'  => 0,
        'max_price'  => 0,
        'category'   => '',
        'color'      => '',
        'size'       => '',
        'sort'       => 'newest',
        'page'       => 1,
        'per_page'   => 12,
    );

    $args = wp_parse_args( $args, $defaults );

    // Bắt đầu xây dựng query args
    $query_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $args['per_page'] ),
        'paged'          => intval( $args['page'] ),
    );

    // Từ khóa tìm kiếm
    if ( ! empty( $args['keyword'] ) ) {
        $query_args['s'] = sanitize_text_field( $args['keyword'] );
    }

    // Meta query
    $meta_query = array( 'relation' => 'AND' );

    // Khoảng giá
    if ( $args['min_price'] > 0 || $args['max_price'] > 0 ) {
        $price_query = array(
            'key'  => '_price',
            'type' => 'NUMERIC',
        );

        if ( $args['min_price'] > 0 && $args['max_price'] > 0 ) {
            $price_query['value']   = array( intval( $args['min_price'] ), intval( $args['max_price'] ) );
            $price_query['compare'] = 'BETWEEN';
        } elseif ( $args['min_price'] > 0 ) {
            $price_query['value']   = intval( $args['min_price'] );
            $price_query['compare'] = '>=';
        } else {
            $price_query['value']   = intval( $args['max_price'] );
            $price_query['compare'] = '<=';
        }

        $meta_query['price_clause'] = $price_query;
    }

    // Chỉ lấy sản phẩm còn hàng
    $meta_query[] = array(
        'key'     => '_stock_status',
        'value'   => 'instock',
        'compare' => '=',
    );

    if ( count( $meta_query ) > 1 ) {
        $query_args['meta_query'] = $meta_query;
    }

    // Tax query
    $tax_query = array( 'relation' => 'AND' );

    // Danh mục
    if ( ! empty( $args['category'] ) ) {
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => sanitize_text_field( $args['category'] ),
            'include_children' => true,
        );
    }

    // Màu sắc
    if ( ! empty( $args['color'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'pa_color',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $args['color'] ),
        );
    }

    // Kích thước
    if ( ! empty( $args['size'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'pa_size',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $args['size'] ),
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $query_args['tax_query'] = $tax_query;
    }

    // Sắp xếp
    switch ( $args['sort'] ) {
        case 'price_asc':
            $query_args['meta_key'] = '_price';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'ASC';
            break;

        case 'price_desc':
            $query_args['meta_key'] = '_price';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'DESC';
            break;

        case 'popular':
            $query_args['meta_key'] = 'total_sales';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'DESC';
            break;

        case 'rating':
            $query_args['meta_key'] = '_wc_average_rating';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'DESC';
            break;

        case 'oldest':
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'ASC';
            break;

        case 'title':
            $query_args['orderby'] = 'title';
            $query_args['order']   = 'ASC';
            break;

        case 'newest':
        default:
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'DESC';
            break;
    }

    $query = new WP_Query( $query_args );

    return array(
        'products'    => $query->posts,
        'total'       => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'current_page'=> intval( $args['page'] ),
        'sql'         => $query->request,  // Để debug
    );
}

// Sử dụng:
$results = advanced_product_search( array(
    'keyword'   => 'ao thun',
    'min_price' => 100000,
    'max_price' => 500000,
    'category'  => 'thoi-trang-nam',
    'color'     => 'den',
    'sort'      => 'price_asc',
    'page'      => 1,
    'per_page'  => 12,
) );

echo "Tìm thấy {$results['total']} sản phẩm\n";
foreach ( $results['products'] as $product ) {
    echo "- {$product->post_title}\n";
}
```

### 11.2. Báo cáo thống kê với $wpdb

```php
<?php
/**
 * Báo cáo thống kê đơn hàng
 */
function get_order_statistics( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'date_from' => date( 'Y-m-01' ),  // Đầu tháng hiện tại
        'date_to'   => date( 'Y-m-d' ),   // Hôm nay
        'status'    => 'completed',
    );

    $args = wp_parse_args( $args, $defaults );
    $table = $wpdb->prefix . 'my_orders';

    // Tổng quan
    $overview = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT
                COUNT(*) as total_orders,
                SUM(total_price) as total_revenue,
                AVG(total_price) as avg_order_value,
                MAX(total_price) as max_order_value,
                MIN(total_price) as min_order_value,
                SUM(quantity) as total_items_sold
             FROM {$table}
             WHERE status = %s
             AND order_date BETWEEN %s AND %s",
            $args['status'],
            $args['date_from'] . ' 00:00:00',
            $args['date_to'] . ' 23:59:59'
        )
    );

    // Doanh thu theo ngày
    $daily_revenue = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                DATE(order_date) as order_day,
                COUNT(*) as num_orders,
                SUM(total_price) as daily_revenue
             FROM {$table}
             WHERE status = %s
             AND order_date BETWEEN %s AND %s
             GROUP BY DATE(order_date)
             ORDER BY order_day ASC",
            $args['status'],
            $args['date_from'] . ' 00:00:00',
            $args['date_to'] . ' 23:59:59'
        )
    );

    // Top 10 khách hàng
    $top_customers = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                o.user_id,
                u.display_name,
                u.user_email,
                COUNT(*) as order_count,
                SUM(o.total_price) as total_spent
             FROM {$table} o
             INNER JOIN {$wpdb->users} u ON o.user_id = u.ID
             WHERE o.status = %s
             AND o.order_date BETWEEN %s AND %s
             GROUP BY o.user_id
             ORDER BY total_spent DESC
             LIMIT 10",
            $args['status'],
            $args['date_from'] . ' 00:00:00',
            $args['date_to'] . ' 23:59:59'
        )
    );

    // Top 10 sản phẩm bán chạy
    $top_products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                o.product_id,
                p.post_title as product_name,
                SUM(o.quantity) as total_sold,
                SUM(o.total_price) as total_revenue
             FROM {$table} o
             INNER JOIN {$wpdb->posts} p ON o.product_id = p.ID
             WHERE o.status = %s
             AND o.order_date BETWEEN %s AND %s
             GROUP BY o.product_id
             ORDER BY total_sold DESC
             LIMIT 10",
            $args['status'],
            $args['date_from'] . ' 00:00:00',
            $args['date_to'] . ' 23:59:59'
        )
    );

    return array(
        'overview'      => $overview,
        'daily_revenue' => $daily_revenue,
        'top_customers' => $top_customers,
        'top_products'  => $top_products,
    );
}

// Sử dụng:
$stats = get_order_statistics( array(
    'date_from' => '2024-01-01',
    'date_to'   => '2024-12-31',
    'status'    => 'completed',
) );

echo "Tổng đơn hàng: {$stats['overview']->total_orders}\n";
echo "Tổng doanh thu: " . number_format( $stats['overview']->total_revenue ) . " VND\n";
echo "Giá trị trung bình: " . number_format( $stats['overview']->avg_order_value ) . " VND\n";
```

### 11.3. Query phức tạp kết hợp nhiều điều kiện

```php
<?php
/**
 * Tìm bài viết liên quan dựa trên:
 * - Cùng category
 * - Cùng tags
 * - Cùng tác giả
 * - Có nhiều lượt xem
 * Sắp xếp theo độ liên quan (điểm)
 */
function get_related_posts( $post_id, $limit = 5 ) {
    global $wpdb;

    $post = get_post( $post_id );
    if ( ! $post ) {
        return array();
    }

    // Lấy categories và tags của bài hiện tại
    $categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
    $tags       = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

    if ( empty( $categories ) && empty( $tags ) ) {
        return array();
    }

    // Xây dựng query tính điểm liên quan
    $score_parts = array();
    $join_parts  = array();
    $values      = array();

    // Điểm cho cùng category (3 điểm mỗi category trùng)
    if ( ! empty( $categories ) ) {
        $cat_placeholders = implode( ', ', array_fill( 0, count( $categories ), '%d' ) );

        $join_parts[] = "LEFT JOIN {$wpdb->term_relationships} tr_cat ON p.ID = tr_cat.object_id";
        $join_parts[] = "LEFT JOIN {$wpdb->term_taxonomy} tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
                         AND tt_cat.taxonomy = 'category'
                         AND tt_cat.term_id IN ({$cat_placeholders})";

        $score_parts[] = 'COALESCE(COUNT(DISTINCT tt_cat.term_id), 0) * 3';
        $values = array_merge( $values, $categories );
    }

    // Điểm cho cùng tag (2 điểm mỗi tag trùng)
    if ( ! empty( $tags ) ) {
        $tag_placeholders = implode( ', ', array_fill( 0, count( $tags ), '%d' ) );

        $join_parts[] = "LEFT JOIN {$wpdb->term_relationships} tr_tag ON p.ID = tr_tag.object_id";
        $join_parts[] = "LEFT JOIN {$wpdb->term_taxonomy} tt_tag ON tr_tag.term_taxonomy_id = tt_tag.term_taxonomy_id
                         AND tt_tag.taxonomy = 'post_tag'
                         AND tt_tag.term_id IN ({$tag_placeholders})";

        $score_parts[] = 'COALESCE(COUNT(DISTINCT tt_tag.term_id), 0) * 2';
        $values = array_merge( $values, $tags );
    }

    // Điểm cho cùng tác giả (1 điểm)
    $score_parts[] = 'CASE WHEN p.post_author = %d THEN 1 ELSE 0 END';
    $values[] = $post->post_author;

    $score_sql = implode( ' + ', $score_parts );
    $join_sql  = implode( "\n", $join_parts );

    // Loại trừ bài hiện tại
    $values[] = $post_id;
    $values[] = $limit;

    $sql = $wpdb->prepare(
        "SELECT p.ID, p.post_title, p.post_date,
                ({$score_sql}) as relevance_score
         FROM {$wpdb->posts} p
         {$join_sql}
         WHERE p.post_type = 'post'
         AND p.post_status = 'publish'
         AND p.ID != %d
         GROUP BY p.ID
         HAVING relevance_score > 0
         ORDER BY relevance_score DESC, p.post_date DESC
         LIMIT %d",
        ...$values
    );

    return $wpdb->get_results( $sql );
}

// Sử dụng:
$related = get_related_posts( get_the_ID(), 5 );
foreach ( $related as $post ) {
    echo "{$post->post_title} (điểm: {$post->relevance_score})\n";
}
```

### 11.4. Custom Query với Pagination trong template

```php
<?php
/**
 * Template: archive-product.php
 * Hiển thị danh sách sản phẩm với filter và pagination đầy đủ
 */

get_header();

// Lấy các tham số filter từ URL
$current_cat   = get_query_var( 'product_cat', '' );
$price_range   = isset( $_GET['price'] ) ? sanitize_text_field( $_GET['price'] ) : '';
$sort          = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : 'date';
$paged         = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

// Xây dựng query
$query_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
);

// Filter theo danh mục
if ( ! empty( $current_cat ) ) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $current_cat,
        ),
    );
}

// Filter theo giá
if ( ! empty( $price_range ) ) {
    $prices = explode( '-', $price_range );
    if ( count( $prices ) === 2 ) {
        $query_args['meta_query'] = array(
            array(
                'key'     => '_price',
                'value'   => array( intval( $prices[0] ), intval( $prices[1] ) ),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ),
        );
    }
}

// Sắp xếp
switch ( $sort ) {
    case 'price_asc':
        $query_args['meta_key'] = '_price';
        $query_args['orderby']  = 'meta_value_num';
        $query_args['order']    = 'ASC';
        break;
    case 'price_desc':
        $query_args['meta_key'] = '_price';
        $query_args['orderby']  = 'meta_value_num';
        $query_args['order']    = 'DESC';
        break;
    case 'title':
        $query_args['orderby'] = 'title';
        $query_args['order']   = 'ASC';
        break;
    default:
        $query_args['orderby'] = 'date';
        $query_args['order']   = 'DESC';
}

$product_query = new WP_Query( $query_args );
?>

<div class="product-archive">
    <div class="filter-bar">
        <form method="get">
            <select name="price">
                <option value="">Tất cả giá</option>
                <option value="0-100000" <?php selected( $price_range, '0-100000' ); ?>>Dưới 100k</option>
                <option value="100000-500000" <?php selected( $price_range, '100000-500000' ); ?>>100k - 500k</option>
                <option value="500000-1000000" <?php selected( $price_range, '500000-1000000' ); ?>>500k - 1tr</option>
                <option value="1000000-99999999" <?php selected( $price_range, '1000000-99999999' ); ?>>Trên 1tr</option>
            </select>
            <select name="sort">
                <option value="date" <?php selected( $sort, 'date' ); ?>>Mới nhất</option>
                <option value="price_asc" <?php selected( $sort, 'price_asc' ); ?>>Giá tăng dần</option>
                <option value="price_desc" <?php selected( $sort, 'price_desc' ); ?>>Giá giảm dần</option>
                <option value="title" <?php selected( $sort, 'title' ); ?>>Tên A-Z</option>
            </select>
            <button type="submit">Lọc</button>
        </form>
    </div>

    <p>Tìm thấy <?php echo $product_query->found_posts; ?> sản phẩm</p>

    <?php if ( $product_query->have_posts() ) : ?>
        <div class="product-grid">
            <?php while ( $product_query->have_posts() ) : $product_query->the_post(); ?>
                <div class="product-card">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="product-image">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </div>
                    <?php endif; ?>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <p class="price"><?php echo number_format( get_post_meta( get_the_ID(), '_price', true ) ); ?> VND</p>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php
            echo paginate_links( array(
                'total'     => $product_query->max_num_pages,
                'current'   => $paged,
                'prev_text' => 'Trang trước',
                'next_text' => 'Trang sau',
                'add_args'  => array(
                    'price' => $price_range,
                    'sort'  => $sort,
                ),
            ) );
            ?>
        </div>

        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p>Không tìm thấy sản phẩm nào.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

---

Tài liệu tham khảo:
- WordPress Developer Resources: https://developer.wordpress.org/reference/classes/wp_query/
- WordPress Database Description: https://codex.wordpress.org/Database_Description
- $wpdb Class Reference: https://developer.wordpress.org/reference/classes/wpdb/
