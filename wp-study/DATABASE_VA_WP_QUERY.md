# Database va WP_Query trong WordPress

Huong dan toan dien ve co so du lieu WordPress va cac lop truy van (WP_Query, WP_Meta_Query, WP_Tax_Query, WP_User_Query, WP_Comment_Query), bao gom $wpdb, custom tables va toi uu hieu nang.

---

## Muc luc

1. [Cau truc Database WordPress](#1-cau-truc-database-wordpress)
2. [$wpdb - WordPress Database Abstraction Layer](#2-wpdb---wordpress-database-abstraction-layer)
3. [WP_Query - Giai thich chi tiet](#3-wp_query---giai-thich-chi-tiet)
4. [WP_Meta_Query - Query theo meta fields](#4-wp_meta_query---query-theo-meta-fields)
5. [WP_Tax_Query - Query theo taxonomy](#5-wp_tax_query---query-theo-taxonomy)
6. [WP_User_Query - Query users](#6-wp_user_query---query-users)
7. [WP_Comment_Query - Query comments](#7-wp_comment_query---query-comments)
8. [pre_get_posts - Modify main query](#8-pre_get_posts---modify-main-query)
9. [Custom Tables - Tao bang rieng voi dbDelta()](#9-custom-tables---tao-bang-rieng-voi-dbdelta)
10. [Toi uu Database](#10-toi-uu-database)
11. [Vi du thuc te phuc tap](#11-vi-du-thuc-te-phuc-tap)

---

## 1. Cau truc Database WordPress

WordPress su dung MySQL/MariaDB voi cau truc mac dinh gom 12 bang chinh. Prefix mac dinh la `wp_` nhung co the thay doi trong `wp-config.php`.

### 1.1. wp_posts

Bang quan trong nhat, luu tat ca cac loai noi dung: posts, pages, custom post types, attachments, revisions, menu items.

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

Cac gia tri `post_status` pho bien: `publish`, `draft`, `pending`, `private`, `trash`, `auto-draft`, `inherit` (cho revisions/attachments).

Cac gia tri `post_type` pho bien: `post`, `page`, `attachment`, `revision`, `nav_menu_item`, va cac custom post types.

### 1.2. wp_postmeta

Luu metadata cua posts theo dang key-value. Day la bang duoc truy van nhieu nhat va cung de bi cham nhat khi du lieu lon.

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

Luu y: `meta_value` la `longtext`, khong duoc danh index. Neu can query theo `meta_value` thuong xuyen, can tao custom index hoac custom table.

### 1.3. wp_terms

Luu ten cac term (category, tag, custom taxonomy term).

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

Gan term voi taxonomy cu the. Mot term co the thuoc nhieu taxonomy khac nhau.

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

Bang trung gian (pivot table) lien ket objects (posts) voi term_taxonomy.

```
+------------------+---------------------+------+-----+---------+-------+
| Field            | Type                | Null | Key | Default | Extra |
+------------------+---------------------+------+-----+---------+-------+
| object_id        | bigint(20) unsigned | NO   | PRI | 0       |       |
| term_taxonomy_id | bigint(20) unsigned | NO   | PRI | 0       |       |
| term_order       | int(11)             | NO   |     | 0       |       |
+------------------+---------------------+------+-----+---------+-------+
```

Moi quan he: `wp_posts.ID` -> `wp_term_relationships.object_id` -> `wp_term_relationships.term_taxonomy_id` -> `wp_term_taxonomy.term_taxonomy_id` -> `wp_term_taxonomy.term_id` -> `wp_terms.term_id`.

### 1.6. wp_users

Luu thong tin nguoi dung.

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

Luu metadata cua user, tuong tu wp_postmeta.

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

Cac meta_key quan trong: `wp_capabilities` (roles), `wp_user_level`, `first_name`, `last_name`, `nickname`, `description`.

### 1.8. wp_options

Luu toan bo cai dat cua WordPress va plugins. Bang nay duoc load rat nhieu, dac biet cac row co `autoload = yes`.

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

Luu y: Tat ca cac row co `autoload = yes` se duoc load vao memory moi request. Khi su dung `add_option()` hoac `update_option()`, can can nhac gia tri `autoload`.

### 1.9. wp_comments

Luu binh luan.

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

Luu metadata cua comments.

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

Bang luu bookmarks/links. Hien tai it duoc su dung (deprecated tu WP 3.5) nhung van ton tai trong schema.

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

### So do quan he giua cac bang

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

wp_options (doc lap, khong co foreign key)
wp_links (doc lap, it su dung)
```

---

## 2. $wpdb - WordPress Database Abstraction Layer

`$wpdb` la doi tuong global cung cap interface de tuong tac truc tiep voi database. No la instance cua class `wpdb`.

### 2.1. Truy cap $wpdb

```php
<?php
// Cach 1: Khai bao global
function my_custom_query() {
    global $wpdb;
    // Su dung $wpdb o day
}

// Cach 2: Su dung trong class
class My_Plugin {
    public function get_data() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->posts} LIMIT 10" );
    }
}
```

### 2.2. Cac thuoc tinh quan trong cua $wpdb

```php
<?php
global $wpdb;

// Ten cac bang voi prefix
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
$wpdb->prefix;           // 'wp_' (hoac prefix tuy chinh)
$wpdb->base_prefix;      // prefix goc (multisite)

// Thong tin ket noi
$wpdb->last_query;       // Cau query cuoi cung da chay
$wpdb->last_result;      // Ket qua cuoi cung
$wpdb->last_error;       // Loi cuoi cung
$wpdb->num_rows;         // So dong tra ve tu query cuoi
$wpdb->insert_id;        // ID cua row vua insert
$wpdb->rows_affected;    // So dong bi anh huong boi query cuoi
```

### 2.3. $wpdb->get_results()

Lay nhieu dong ket qua.

```php
<?php
global $wpdb;

// Tra ve mang cac object (mac dinh OBJECT)
$posts = $wpdb->get_results(
    "SELECT ID, post_title, post_date FROM {$wpdb->posts}
     WHERE post_status = 'publish' AND post_type = 'post'
     ORDER BY post_date DESC
     LIMIT 10"
);

foreach ( $posts as $post ) {
    echo $post->ID . ': ' . $post->post_title . "\n";
}

// Tra ve mang cac mang associative (ARRAY_A)
$posts = $wpdb->get_results(
    "SELECT ID, post_title FROM {$wpdb->posts}
     WHERE post_status = 'publish'
     LIMIT 5",
    ARRAY_A
);

foreach ( $posts as $post ) {
    echo $post['ID'] . ': ' . $post['post_title'] . "\n";
}

// Tra ve mang cac mang numeric (ARRAY_N)
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

Cac output type:
- `OBJECT` (mac dinh): Moi row la mot stdClass object
- `OBJECT_K`: Giong OBJECT nhung key la gia tri cot dau tien
- `ARRAY_A`: Moi row la mang associative
- `ARRAY_N`: Moi row la mang so thu tu

### 2.4. $wpdb->get_row()

Lay mot dong duy nhat.

```php
<?php
global $wpdb;

// Lay 1 row dang object
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

// Lay 1 row dang array associative
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

// Lay row thu 3 tu ket qua (0-indexed)
$third_post = $wpdb->get_row(
    "SELECT * FROM {$wpdb->posts} WHERE post_status = 'publish' LIMIT 5",
    OBJECT,
    2  // Row offset, bat dau tu 0
);
```

### 2.5. $wpdb->get_var()

Lay mot gia tri don le.

```php
<?php
global $wpdb;

// Dem so bai viet da publish
$count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_status = 'publish' AND post_type = 'post'"
);
echo "Tong so bai viet: {$count}";

// Lay title cua post co ID = 1
$title = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
        1
    )
);

// Lay gia tri tu cot va row cu the
// get_var( query, column_offset, row_offset )
$second_col_third_row = $wpdb->get_var(
    "SELECT ID, post_title FROM {$wpdb->posts} LIMIT 5",
    1,  // cot thu 2 (0-indexed)
    2   // row thu 3 (0-indexed)
);
```

### 2.6. $wpdb->get_col()

Lay toan bo gia tri cua mot cot.

```php
<?php
global $wpdb;

// Lay tat ca post IDs da publish
$post_ids = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_status = 'publish' AND post_type = 'post'
     ORDER BY post_date DESC"
);

// $post_ids la mang 1 chieu: array( 42, 38, 35, 22, ... )
foreach ( $post_ids as $id ) {
    echo "Post ID: {$id}\n";
}
```

### 2.7. $wpdb->query()

Chay bat ky cau SQL nao, tra ve so dong bi anh huong hoac false neu loi.

```php
<?php
global $wpdb;

// UPDATE truc tiep
$rows_updated = $wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->posts}
         SET post_status = 'draft'
         WHERE post_author = %d AND post_status = 'publish'",
        5
    )
);
echo "Da cap nhat {$rows_updated} bai viet";

// DELETE
$rows_deleted = $wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta}
         WHERE meta_key = %s AND post_id = %d",
        '_old_meta_key',
        42
    )
);

// Tao bang
$wpdb->query(
    "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}my_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) {$wpdb->get_charset_collate()}"
);
```

### 2.8. $wpdb->insert()

Chen du lieu an toan.

```php
<?php
global $wpdb;

// Insert co ban
$result = $wpdb->insert(
    $wpdb->prefix . 'my_table',     // Ten bang
    array(                            // Du lieu (column => value)
        'name'       => 'San pham A',
        'price'      => 299000,
        'created_at' => current_time( 'mysql' ),
    ),
    array(                            // Dinh dang (tuy chon)
        '%s',  // name: string
        '%d',  // price: integer
        '%s',  // created_at: string
    )
);

if ( false !== $result ) {
    $new_id = $wpdb->insert_id;
    echo "Da them voi ID: {$new_id}";
} else {
    echo "Loi: " . $wpdb->last_error;
}

// Insert vao bang posts (thuong dung wp_insert_post() thay vi $wpdb->insert)
$wpdb->insert(
    $wpdb->posts,
    array(
        'post_title'   => 'Bai viet moi',
        'post_content' => 'Noi dung bai viet',
        'post_status'  => 'publish',
        'post_author'  => 1,
        'post_type'    => 'post',
        'post_date'    => current_time( 'mysql' ),
        'post_date_gmt'=> current_time( 'mysql', true ),
    )
);
```

### 2.9. $wpdb->update()

Cap nhat du lieu an toan.

```php
<?php
global $wpdb;

// Update co ban
$rows_updated = $wpdb->update(
    $wpdb->prefix . 'my_table',     // Ten bang
    array(                            // Du lieu can cap nhat
        'name'  => 'San pham B',
        'price' => 350000,
    ),
    array(                            // Dieu kien WHERE
        'id' => 5,
    ),
    array(                            // Dinh dang du lieu
        '%s',  // name
        '%d',  // price
    ),
    array(                            // Dinh dang dieu kien
        '%d',  // id
    )
);

if ( false !== $rows_updated ) {
    echo "Da cap nhat {$rows_updated} dong";
} else {
    echo "Loi: " . $wpdb->last_error;
}

// Update voi nhieu dieu kien WHERE
$wpdb->update(
    $wpdb->postmeta,
    array( 'meta_value' => 'new_value' ),                    // SET
    array( 'post_id' => 42, 'meta_key' => 'my_custom_key' ), // WHERE
    array( '%s' ),                                            // format SET
    array( '%d', '%s' )                                       // format WHERE
);
```

### 2.10. $wpdb->delete()

Xoa du lieu an toan.

```php
<?php
global $wpdb;

// Xoa theo ID
$rows_deleted = $wpdb->delete(
    $wpdb->prefix . 'my_table',  // Ten bang
    array( 'id' => 5 ),          // Dieu kien WHERE
    array( '%d' )                 // Dinh dang
);

// Xoa voi nhieu dieu kien
$wpdb->delete(
    $wpdb->postmeta,
    array(
        'post_id'  => 42,
        'meta_key' => '_temporary_data',
    ),
    array( '%d', '%s' )
);

// Xoa tat ca meta cua mot post
$wpdb->delete(
    $wpdb->postmeta,
    array( 'post_id' => 42 ),
    array( '%d' )
);
```

### 2.11. $wpdb->prepare()

Phuong thuc QUAN TRONG NHAT de ngan chan SQL Injection. Luon su dung khi truyen du lieu tu nguoi dung vao cau query.

```php
<?php
global $wpdb;

// Cac placeholder:
// %d = so nguyen (integer)
// %f = so thuc (float)
// %s = chuoi (string)

// Vi du co ban
$safe_query = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE ID = %d AND post_status = %s",
    42,
    'publish'
);
$result = $wpdb->get_row( $safe_query );

// Voi LIKE - su dung $wpdb->esc_like()
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

// Voi IN clause - su dung nhieu placeholder
$post_ids = array( 1, 5, 10, 15 );
$placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID IN ({$placeholders})",
        ...$post_ids
    )
);

// KHONG BAO GIO lam nhu nay (SQL Injection!):
// $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID = " . $_GET['id'] );
// $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE post_title = '{$_POST['title']}'" );
```

### 2.12. Debug $wpdb

```php
<?php
global $wpdb;

// Bat debug mode trong wp-config.php
// define( 'SAVEQUERIES', true );

// Xem tat ca cac query da chay
if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
    echo '<pre>';
    print_r( $wpdb->queries );
    echo '</pre>';
}

// Xem query cuoi cung
echo $wpdb->last_query;

// Xem loi cuoi cung
echo $wpdb->last_error;

// Hien thi loi truc tiep
$wpdb->show_errors();
$wpdb->hide_errors();

// In loi ra man hinh
$wpdb->print_error();

// Suppress errors
$wpdb->suppress_errors( true );
```

---

## 3. WP_Query - Giai thich chi tiet

`WP_Query` la lop chinh de truy van posts trong WordPress. No la nen tang cua main query va cung duoc su dung de tao custom queries.

### 3.1. Cach su dung co ban

```php
<?php
// Cach 1: Tao instance moi
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
    wp_reset_postdata(); // LUON GOI SAU KHI DUNG the_post()
}

// Cach 2: Su dung get_posts() (wrapper cua WP_Query)
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

### 3.2. Tham so post_type

```php
<?php
// Mot post type
$query = new WP_Query( array(
    'post_type' => 'post',
) );

// Nhieu post types
$query = new WP_Query( array(
    'post_type' => array( 'post', 'page', 'product' ),
) );

// Tat ca post types
$query = new WP_Query( array(
    'post_type' => 'any',
) );
```

### 3.3. Tham so posts_per_page va phan trang

```php
<?php
// So luong bai viet
$query = new WP_Query( array(
    'posts_per_page' => 10,     // 10 bai moi trang
) );

// Tat ca bai viet (khong phan trang)
$query = new WP_Query( array(
    'posts_per_page' => -1,     // Lay het, can than voi du lieu lon!
    'no_found_rows'  => true,   // Bo qua SQL_CALC_FOUND_ROWS de tang toc
) );

// Phan trang
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'paged'          => $paged,
) );

// Hien thi phan trang
if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        // Hien thi bai viet
    }

    // Pagination links
    echo paginate_links( array(
        'total'   => $query->max_num_pages,
        'current' => $paged,
    ) );

    wp_reset_postdata();
}

// Offset - bo qua N bai dau tien
$query = new WP_Query( array(
    'posts_per_page' => 5,
    'offset'         => 3,  // Bo qua 3 bai dau, lay tu bai thu 4
) );
```

### 3.4. Tham so tax_query

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

// Query theo nhieu taxonomy voi AND
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

// Query theo nhieu taxonomy voi OR
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

// NOT IN - Loai tru
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

### 3.5. Tham so meta_query

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

// Query theo nhieu meta fields voi AND
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

// Cac gia tri compare hop le:
// '='          : Bang (mac dinh)
// '!='         : Khac
// '>'          : Lon hon
// '>='         : Lon hon hoac bang
// '<'          : Nho hon
// '<='         : Nho hon hoac bang
// 'LIKE'       : Chua chuoi
// 'NOT LIKE'   : Khong chua chuoi
// 'IN'         : Trong danh sach
// 'NOT IN'     : Khong trong danh sach
// 'BETWEEN'    : Giua 2 gia tri
// 'NOT BETWEEN': Khong giua 2 gia tri
// 'EXISTS'     : Meta key ton tai
// 'NOT EXISTS' : Meta key khong ton tai
// 'REGEXP'     : Khop bieu thuc chinh quy
// 'NOT REGEXP' : Khong khop bieu thuc chinh quy

// Cac gia tri type hop le:
// 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'

// Sap xep theo meta value voi named meta query
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

// Kiem tra meta key ton tai
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

### 3.6. Tham so date_query

```php
<?php
// Bai viet trong nam 2024
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'year' => 2024,
        ),
    ),
) );

// Bai viet tu thang 1 den thang 6 nam 2024
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

// Bai viet trong 30 ngay gan nhat
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'after' => '30 days ago',
        ),
    ),
) );

// Bai viet duoc sua trong tuan nay (dung post_modified)
$query = new WP_Query( array(
    'post_type'  => 'post',
    'date_query' => array(
        array(
            'column' => 'post_modified',
            'after'  => '1 week ago',
        ),
    ),
) );

// Bai viet dang vao buoi sang (8h-12h)
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

### 3.7. Tham so orderby va order

```php
<?php
// Sap xep co ban
$query = new WP_Query( array(
    'orderby' => 'date',    // Theo ngay dang
    'order'   => 'DESC',    // Giam dan (moi nhat truoc)
) );

// Cac gia tri orderby pho bien:
// 'none'           : Khong sap xep
// 'ID'             : Theo post ID
// 'author'         : Theo tac gia
// 'title'          : Theo tieu de
// 'name'           : Theo post slug
// 'date'           : Theo ngay dang (mac dinh)
// 'modified'       : Theo ngay sua
// 'parent'         : Theo parent ID
// 'rand'           : Ngau nhien (CHAM, tranh dung voi du lieu lon)
// 'comment_count'  : Theo so binh luan
// 'menu_order'     : Theo thu tu menu
// 'meta_value'     : Theo gia tri meta (can them meta_key)
// 'meta_value_num' : Theo gia tri meta dang so
// 'post__in'       : Theo thu tu trong mang post__in

// Sap xep theo meta_value
$query = new WP_Query( array(
    'meta_key' => 'price',
    'orderby'  => 'meta_value_num',
    'order'    => 'ASC',
) );

// Sap xep theo nhieu tieu chi
$query = new WP_Query( array(
    'orderby' => array(
        'menu_order' => 'ASC',
        'date'       => 'DESC',
    ),
) );

// Giu thu tu cua mang post__in
$query = new WP_Query( array(
    'post__in' => array( 5, 3, 8, 1, 10 ),
    'orderby'  => 'post__in',
) );
```

### 3.8. Tham so tim kiem (s) va author

```php
<?php
// Tim kiem
$query = new WP_Query( array(
    'post_type' => 'post',
    's'         => 'wordpress tutorial',
) );

// Tim kiem chinh xac (phrase)
$query = new WP_Query( array(
    's'      => '"wordpress tutorial"',  // Dung ngoac kep
    'exact'  => true,                    // Tim chinh xac
) );

// Theo tac gia
$query = new WP_Query( array(
    'author' => 1,                       // Theo user ID
) );

$query = new WP_Query( array(
    'author_name' => 'admin',            // Theo user_nicename
) );

// Nhieu tac gia
$query = new WP_Query( array(
    'author__in' => array( 1, 5, 10 ),
) );

// Loai tru tac gia
$query = new WP_Query( array(
    'author__not_in' => array( 3, 7 ),
) );
```

### 3.9. Cac tham so khac

```php
<?php
// Theo post ID
$query = new WP_Query( array(
    'p' => 42,                            // 1 post theo ID
) );

$query = new WP_Query( array(
    'post__in' => array( 1, 5, 10, 42 ), // Nhieu posts theo ID
) );

$query = new WP_Query( array(
    'post__not_in' => array( 3, 7 ),     // Loai tru posts
) );

// Theo slug
$query = new WP_Query( array(
    'name' => 'bai-viet-mau',            // post slug
) );

// Theo post parent
$query = new WP_Query( array(
    'post_parent'    => 10,              // Cac trang con cua trang ID=10
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
    'has_password' => true,   // Chi lay bai co mat khau
) );

// Sticky posts
$query = new WP_Query( array(
    'post__in'            => get_option( 'sticky_posts' ),
    'ignore_sticky_posts' => true,
) );

// Performance - no_found_rows
$query = new WP_Query( array(
    'posts_per_page' => 5,
    'no_found_rows'  => true,   // Khong dem tong (nhanh hon, khong co pagination)
) );

// Chi lay truong can thiet
$query = new WP_Query( array(
    'fields' => 'ids',          // Chi lay mang IDs
) );

$query = new WP_Query( array(
    'fields' => 'id=>parent',   // Chi lay ID va parent
) );

// Cache
$query = new WP_Query( array(
    'update_post_meta_cache' => false,  // Khong load meta cache
    'update_post_term_cache' => false,  // Khong load term cache
) );
```

### 3.10. Thuoc tinh cua WP_Query

```php
<?php
$query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 10 ) );

$query->posts;          // Mang cac WP_Post objects
$query->post_count;     // So bai trong trang hien tai
$query->found_posts;    // Tong so bai (tat ca cac trang)
$query->max_num_pages;  // Tong so trang
$query->current_post;   // Index bai hien tai trong loop (-1 truoc loop)
$query->post;           // Bai hien tai
$query->is_single();    // True neu la trang single post
$query->is_page();      // True neu la trang page
$query->is_archive();   // True neu la trang archive
$query->is_search();    // True neu la trang tim kiem
$query->request;        // Cau SQL da chay
```

---

## 4. WP_Meta_Query - Query theo meta fields

`WP_Meta_Query` la lop xu ly meta_query ben trong WP_Query. Co the su dung truc tiep hoac thong qua tham so `meta_query` cua WP_Query.

```php
<?php
// Su dung truc tiep WP_Meta_Query
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

// Lay SQL tu WP_Meta_Query (de debug hoac su dung voi $wpdb)
$meta_query_sql = $meta_query->get_sql(
    'post',                // Meta type: 'post', 'user', 'comment', 'term'
    $wpdb->posts,          // Bang chinh
    'ID',                  // Cot primary key cua bang chinh
    null                   // WP_Query object (tuy chon)
);

// $meta_query_sql gom:
// $meta_query_sql['join']  => INNER JOIN wp_postmeta ON ...
// $meta_query_sql['where'] => AND ( (wp_postmeta.meta_key = 'color' AND ...) )

// Vi du phuc tap: san pham gia 100k-500k, mau xanh HOAC do, con hang
$query = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',
        // Dieu kien gia
        'price_clause' => array(
            'key'     => '_price',
            'value'   => array( 100000, 500000 ),
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ),
        // Dieu kien mau sac (OR)
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
        // Dieu kien con hang
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

`WP_Tax_Query` xu ly tax_query ben trong WP_Query.

```php
<?php
// Su dung truc tiep WP_Tax_Query
$tax_query = new WP_Tax_Query( array(
    'relation' => 'AND',
    array(
        'taxonomy'         => 'category',
        'field'            => 'slug',
        'terms'            => array( 'tin-tuc', 'cong-nghe' ),
        'operator'         => 'IN',
        'include_children' => true,  // Bao gom cac category con (mac dinh true)
    ),
    array(
        'taxonomy'         => 'post_tag',
        'field'            => 'slug',
        'terms'            => 'noi-bat',
        'operator'         => 'IN',
    ),
) );

// Lay SQL
$tax_query_sql = $tax_query->get_sql( $wpdb->posts, 'ID' );

// Vi du thuc te: San pham thuoc danh muc "dien-thoai" HOAC "may-tinh"
// VA co tag "khuyen-mai"
// VA KHONG thuoc thuong hieu "nokia"
$query = new WP_Query( array(
    'post_type' => 'product',
    'tax_query' => array(
        'relation' => 'AND',
        // Danh muc (OR)
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
        // Co tag khuyen-mai
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => 'khuyen-mai',
        ),
        // Khong phai Nokia
        array(
            'taxonomy' => 'brand',
            'field'    => 'slug',
            'terms'    => 'nokia',
            'operator' => 'NOT IN',
        ),
    ),
) );

// Cac operator cua tax_query:
// 'IN'         : Thuoc bat ky term nao (mac dinh)
// 'NOT IN'     : Khong thuoc bat ky term nao
// 'AND'        : Thuoc TAT CA cac terms
// 'EXISTS'     : Co gan bat ky term nao cua taxonomy
// 'NOT EXISTS' : Khong co gan term nao cua taxonomy
```

---

## 6. WP_User_Query - Query users

```php
<?php
// Query co ban
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

// Tong so users
$total = $user_query->get_total();
echo "Tong: {$total} nguoi dung";

// Tim kiem user
$user_query = new WP_User_Query( array(
    'search'         => '*nguyen*',         // Tim kiem (wildcard *)
    'search_columns' => array(              // Tim trong cac cot
        'user_login',
        'user_nicename',
        'user_email',
        'display_name',
    ),
) );

// Query theo nhieu roles
$user_query = new WP_User_Query( array(
    'role__in' => array( 'editor', 'author' ),
) );

// Loai tru role
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

// Sap xep theo meta
$user_query = new WP_User_Query( array(
    'meta_key' => 'last_login',
    'orderby'  => 'meta_value',
    'order'    => 'DESC',
) );

// Phan trang
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$per_page = 20;

$user_query = new WP_User_Query( array(
    'number' => $per_page,
    'offset' => ( $paged - 1 ) * $per_page,
) );

$total_users = $user_query->get_total();
$total_pages = ceil( $total_users / $per_page );

// Cac tham so khac
$user_query = new WP_User_Query( array(
    'include'     => array( 1, 5, 10 ),  // Chi lay user IDs nay
    'exclude'     => array( 3 ),          // Loai tru user IDs nay
    'blog_id'     => 1,                   // Blog ID (multisite)
    'count_total' => true,                // Dem tong (mac dinh true)
    'fields'      => 'all',              // 'all', 'all_with_meta', 'ID', 'display_name', 'user_login', hoac mang
    'who'         => 'authors',           // Chi lay users la authors
    'has_published_posts' => true,        // Chi lay users co bai da publish
) );
```

---

## 7. WP_Comment_Query - Query comments

```php
<?php
// Query co ban
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

// Tim kiem comments
$comment_query = new WP_Comment_Query( array(
    'search' => 'wordpress',
) );

// Comments cua 1 user
$comment_query = new WP_Comment_Query( array(
    'user_id' => 5,
) );

// Comments theo post type
$comment_query = new WP_Comment_Query( array(
    'post_type' => 'product',
    'status'    => 'approve',
) );

// Query voi meta
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

// Dem comments
$count = get_comments( array(
    'post_id' => 42,
    'status'  => 'approve',
    'count'   => true,  // Tra ve so luong thay vi danh sach
) );

// Cac gia tri status:
// 'approve' hoac 'approved' hoac '1'  : Da duyet
// 'hold' hoac 'unapproved' hoac '0'   : Cho duyet
// 'spam'                                : Spam
// 'trash'                               : Da xoa
// 'all'                                 : Tat ca

// Comments hierarchical (nested)
$comment_query = new WP_Comment_Query( array(
    'post_id'      => 42,
    'hierarchical' => 'threaded',   // Tra ve comments dang cay
    'status'       => 'approve',
) );

// Phan trang comments
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

`pre_get_posts` la hook cho phep thay doi tham so cua WP_Query TRUOC khi no chay. Day la cach dung de modify main query thay vi tao WP_Query moi.

```php
<?php
// Thay doi so bai tren trang archive
add_action( 'pre_get_posts', 'custom_archive_posts_per_page' );
function custom_archive_posts_per_page( $query ) {
    // QUAN TRONG: Chi modify main query, khong phai custom queries
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( $query->is_category() ) {
            $query->set( 'posts_per_page', 20 );
        }
    }
}

// Them custom post type vao trang chu va archive
add_action( 'pre_get_posts', 'add_custom_post_type_to_query' );
function add_custom_post_type_to_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( $query->is_home() || $query->is_archive() ) {
            $query->set( 'post_type', array( 'post', 'product', 'event' ) );
        }
    }
}

// Loai tru category khoi trang chu
add_action( 'pre_get_posts', 'exclude_category_from_home' );
function exclude_category_from_home( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
        $query->set( 'cat', '-5,-10' );  // Loai tru category ID 5 va 10
    }
}

// Thay doi order cho trang tim kiem
add_action( 'pre_get_posts', 'custom_search_order' );
function custom_search_order( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
        $query->set( 'orderby', 'modified' );
        $query->set( 'order', 'DESC' );
    }
}

// Them meta_query vao main query
add_action( 'pre_get_posts', 'filter_by_custom_field' );
function filter_by_custom_field( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'product' ) ) {
        // Lay tham so tu URL: ?min_price=100&max_price=500
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
            // Neu khong phai admin, chi hien thi bai cua chinh minh
            if ( ! current_user_can( 'manage_options' ) ) {
                $query->set( 'author', get_current_user_id() );
            }
        }
    }
}
```

Luu y quan trong ve `pre_get_posts`:
- Luon kiem tra `$query->is_main_query()` de tranh anh huong den custom queries, widget queries, menu queries.
- Luon kiem tra `! is_admin()` neu chi muon thay doi o frontend.
- KHONG nen su dung `query_posts()` - no thay the main query va gay ra nhieu van de. Luon dung `pre_get_posts` hoac tao `WP_Query` moi.

---

## 9. Custom Tables - Tao bang rieng voi dbDelta()

Khi nao nen tao custom table thay vi su dung postmeta:
- Du lieu co cau truc co dinh, khong phai key-value.
- Can query phuc tap voi JOIN, GROUP BY, aggregate functions.
- Du lieu lon can danh index rieng.
- Du lieu khong lien quan den posts/users/comments.

### 9.1. Tao bang khi activate plugin

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

    // Can include file upgrade.php de su dung dbDelta()
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Luu version cua database schema
    update_option( 'my_plugin_db_version', '1.0' );
}
```

Luu y ve `dbDelta()`:
- Moi truong trong dinh nghia cot phai dung CHINH XAC 1 dau cach giua cac phan.
- PRIMARY KEY phai co HAI dau cach truoc dau ngoac: `PRIMARY KEY  (id)`.
- Phai dung KEY thay vi INDEX.
- Khong duoc dung dau phay sau truong cuoi cung truoc dau ngoac dong.
- Moi truong phai nam tren 1 dong.

### 9.2. Cap nhat schema khi update plugin

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

    // Them cot moi - dbDelta() tu dong detect va chi them cot chua co
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

    // Them bang moi
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

### 9.3. Xoa bang khi uninstall plugin

```php
<?php
// File: uninstall.php (dat o root cua plugin)

// Kiem tra WordPress goi file nay
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Xoa cac bang
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_orders" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_order_items" );

// Xoa options
delete_option( 'my_plugin_db_version' );
delete_option( 'my_plugin_settings' );

// Xoa user meta
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'my_plugin_%'" );
```

### 9.4. CRUD voi custom table

```php
<?php
class My_Order_Model {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'my_orders';
    }

    /**
     * Tao don hang moi
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
     * Lay don hang theo ID
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
     * Lay danh sach don hang voi filter
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

        // Whitelist orderby va order de tranh SQL injection
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
     * Dem tong so don hang theo dieu kien
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
     * Cap nhat don hang
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
            return new WP_Error( 'no_data', 'Khong co du lieu de cap nhat.' );
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
     * Xoa don hang
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

## 10. Toi uu Database

### 10.1. Index

```php
<?php
// Them index vao bang co san
global $wpdb;

// Them index cho meta_value (postmeta) - THAN TRONG voi bang lon
$wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value_index (meta_value(191))" );

// Them composite index
$wpdb->query(
    "ALTER TABLE {$wpdb->prefix}my_orders
     ADD INDEX user_status (user_id, status)"
);

// Kiem tra index hien co
$indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts}" );

// Xem execution plan cua query
$explain = $wpdb->get_results(
    "EXPLAIN SELECT * FROM {$wpdb->posts}
     WHERE post_type = 'product' AND post_status = 'publish'"
);
```

### 10.2. Object Cache va Transients

```php
<?php
// Transients - Cache du lieu vao database (hoac object cache neu co)
function get_popular_posts() {
    $cache_key = 'popular_posts_list';
    $popular = get_transient( $cache_key );

    if ( false === $popular ) {
        // Cache miss - chay query
        $popular = new WP_Query( array(
            'post_type'      => 'post',
            'posts_per_page' => 10,
            'orderby'        => 'comment_count',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ) );

        // Luu cache 1 gio
        set_transient( $cache_key, $popular, HOUR_IN_SECONDS );
    }

    return $popular;
}

// Xoa cache khi co bai viet moi
add_action( 'save_post', function( $post_id ) {
    delete_transient( 'popular_posts_list' );
} );

// Su dung wp_cache (Object Cache) - chi ton tai trong 1 request
// (tru khi co persistent object cache nhu Redis/Memcached)
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

### 10.3. Toi uu WP_Query

```php
<?php
// 1. Su dung no_found_rows khi khong can pagination
$query = new WP_Query( array(
    'posts_per_page' => 5,
    'no_found_rows'  => true,  // Bo qua SQL_CALC_FOUND_ROWS
) );

// 2. Chi lay fields can thiet
$ids = new WP_Query( array(
    'fields'         => 'ids',    // Chi lay IDs
    'posts_per_page' => -1,
    'no_found_rows'  => true,
) );

// 3. Tat meta/term cache khi khong can
$query = new WP_Query( array(
    'posts_per_page'         => 10,
    'update_post_meta_cache' => false,  // Khong preload meta
    'update_post_term_cache' => false,  // Khong preload terms
) );

// 4. Tranh posts_per_page = -1 voi du lieu lon
// Thay vao do, su dung phan trang hoac gioi han hop ly

// 5. Tranh orderby = 'rand' voi du lieu lon
// Thay the bang:
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

Query Monitor la plugin khong the thieu de debug va toi uu database queries.

```php
<?php
// Cai dat: wp plugin install query-monitor --activate

// Query Monitor se tu dong hien thi:
// - Tat ca SQL queries va thoi gian thuc thi
// - Duplicate queries
// - Slow queries
// - Queries by component (theme, plugin, core)
// - PHP errors
// - HTTP API calls
// - Transients
// - Hooks va actions

// Debug rieng voi QM:
do_action( 'qm/debug', 'Thong tin debug' );
do_action( 'qm/info', array( 'key' => 'value' ) );
do_action( 'qm/warning', 'Canh bao!' );
do_action( 'qm/error', 'Loi nghiem trong!' );
```

### 10.5. Cac meo toi uu khac

```php
<?php
// 1. Su dung autoload = 'no' cho options lon hoac it dung
add_option( 'my_large_data', $data, '', 'no' );  // autoload = no

// 2. Giam so luong revisions
// Them vao wp-config.php:
// define( 'WP_POST_REVISIONS', 5 );  // Gioi han 5 revisions
// define( 'WP_POST_REVISIONS', false ); // Tat revisions

// 3. Don dep database dinh ky
function cleanup_old_data() {
    global $wpdb;

    // Xoa revisions cu hon 30 ngay
    $wpdb->query(
        "DELETE FROM {$wpdb->posts}
         WHERE post_type = 'revision'
         AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Xoa orphan postmeta
    $wpdb->query(
        "DELETE pm FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.ID IS NULL"
    );

    // Xoa transients het han
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

## 11. Vi du thuc te phuc tap

### 11.1. He thong tim kiem san pham nang cao

```php
<?php
/**
 * Tim kiem san pham voi nhieu tieu chi:
 * - Tu khoa
 * - Khoang gia
 * - Danh muc
 * - Thuoc tinh (mau sac, kich thuoc)
 * - Sap xep
 * - Phan trang
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

    // Bat dau xay dung query args
    $query_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $args['per_page'] ),
        'paged'          => intval( $args['page'] ),
    );

    // Tu khoa tim kiem
    if ( ! empty( $args['keyword'] ) ) {
        $query_args['s'] = sanitize_text_field( $args['keyword'] );
    }

    // Meta query
    $meta_query = array( 'relation' => 'AND' );

    // Khoang gia
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

    // Chi lay san pham con hang
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

    // Danh muc
    if ( ! empty( $args['category'] ) ) {
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => sanitize_text_field( $args['category'] ),
            'include_children' => true,
        );
    }

    // Mau sac
    if ( ! empty( $args['color'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'pa_color',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $args['color'] ),
        );
    }

    // Kich thuoc
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

    // Sap xep
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
        'sql'         => $query->request,  // De debug
    );
}

// Su dung:
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

echo "Tim thay {$results['total']} san pham\n";
foreach ( $results['products'] as $product ) {
    echo "- {$product->post_title}\n";
}
```

### 11.2. Bao cao thong ke voi $wpdb

```php
<?php
/**
 * Bao cao thong ke don hang
 */
function get_order_statistics( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'date_from' => date( 'Y-m-01' ),  // Dau thang hien tai
        'date_to'   => date( 'Y-m-d' ),   // Hom nay
        'status'    => 'completed',
    );

    $args = wp_parse_args( $args, $defaults );
    $table = $wpdb->prefix . 'my_orders';

    // Tong quan
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

    // Doanh thu theo ngay
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

    // Top 10 khach hang
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

    // Top 10 san pham ban chay
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

// Su dung:
$stats = get_order_statistics( array(
    'date_from' => '2024-01-01',
    'date_to'   => '2024-12-31',
    'status'    => 'completed',
) );

echo "Tong don hang: {$stats['overview']->total_orders}\n";
echo "Tong doanh thu: " . number_format( $stats['overview']->total_revenue ) . " VND\n";
echo "Gia tri trung binh: " . number_format( $stats['overview']->avg_order_value ) . " VND\n";
```

### 11.3. Query phuc tap ket hop nhieu dieu kien

```php
<?php
/**
 * Tim bai viet lien quan dua tren:
 * - Cung category
 * - Cung tags
 * - Cung tac gia
 * - Co nhieu luot xem
 * Sap xep theo do lien quan (diem)
 */
function get_related_posts( $post_id, $limit = 5 ) {
    global $wpdb;

    $post = get_post( $post_id );
    if ( ! $post ) {
        return array();
    }

    // Lay categories va tags cua bai hien tai
    $categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
    $tags       = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

    if ( empty( $categories ) && empty( $tags ) ) {
        return array();
    }

    // Xay dung query tinh diem lien quan
    $score_parts = array();
    $join_parts  = array();
    $values      = array();

    // Diem cho cung category (3 diem moi category trung)
    if ( ! empty( $categories ) ) {
        $cat_placeholders = implode( ', ', array_fill( 0, count( $categories ), '%d' ) );

        $join_parts[] = "LEFT JOIN {$wpdb->term_relationships} tr_cat ON p.ID = tr_cat.object_id";
        $join_parts[] = "LEFT JOIN {$wpdb->term_taxonomy} tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
                         AND tt_cat.taxonomy = 'category'
                         AND tt_cat.term_id IN ({$cat_placeholders})";

        $score_parts[] = 'COALESCE(COUNT(DISTINCT tt_cat.term_id), 0) * 3';
        $values = array_merge( $values, $categories );
    }

    // Diem cho cung tag (2 diem moi tag trung)
    if ( ! empty( $tags ) ) {
        $tag_placeholders = implode( ', ', array_fill( 0, count( $tags ), '%d' ) );

        $join_parts[] = "LEFT JOIN {$wpdb->term_relationships} tr_tag ON p.ID = tr_tag.object_id";
        $join_parts[] = "LEFT JOIN {$wpdb->term_taxonomy} tt_tag ON tr_tag.term_taxonomy_id = tt_tag.term_taxonomy_id
                         AND tt_tag.taxonomy = 'post_tag'
                         AND tt_tag.term_id IN ({$tag_placeholders})";

        $score_parts[] = 'COALESCE(COUNT(DISTINCT tt_tag.term_id), 0) * 2';
        $values = array_merge( $values, $tags );
    }

    // Diem cho cung tac gia (1 diem)
    $score_parts[] = 'CASE WHEN p.post_author = %d THEN 1 ELSE 0 END';
    $values[] = $post->post_author;

    $score_sql = implode( ' + ', $score_parts );
    $join_sql  = implode( "\n", $join_parts );

    // Loai tru bai hien tai
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

// Su dung:
$related = get_related_posts( get_the_ID(), 5 );
foreach ( $related as $post ) {
    echo "{$post->post_title} (diem: {$post->relevance_score})\n";
}
```

### 11.4. Custom Query voi Pagination trong template

```php
<?php
/**
 * Template: archive-product.php
 * Hien thi danh sach san pham voi filter va pagination day du
 */

get_header();

// Lay cac tham so filter tu URL
$current_cat   = get_query_var( 'product_cat', '' );
$price_range   = isset( $_GET['price'] ) ? sanitize_text_field( $_GET['price'] ) : '';
$sort          = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : 'date';
$paged         = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

// Xay dung query
$query_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
);

// Filter theo danh muc
if ( ! empty( $current_cat ) ) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $current_cat,
        ),
    );
}

// Filter theo gia
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

// Sap xep
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
                <option value="">Tat ca gia</option>
                <option value="0-100000" <?php selected( $price_range, '0-100000' ); ?>>Duoi 100k</option>
                <option value="100000-500000" <?php selected( $price_range, '100000-500000' ); ?>>100k - 500k</option>
                <option value="500000-1000000" <?php selected( $price_range, '500000-1000000' ); ?>>500k - 1tr</option>
                <option value="1000000-99999999" <?php selected( $price_range, '1000000-99999999' ); ?>>Tren 1tr</option>
            </select>
            <select name="sort">
                <option value="date" <?php selected( $sort, 'date' ); ?>>Moi nhat</option>
                <option value="price_asc" <?php selected( $sort, 'price_asc' ); ?>>Gia tang dan</option>
                <option value="price_desc" <?php selected( $sort, 'price_desc' ); ?>>Gia giam dan</option>
                <option value="title" <?php selected( $sort, 'title' ); ?>>Ten A-Z</option>
            </select>
            <button type="submit">Loc</button>
        </form>
    </div>

    <p>Tim thay <?php echo $product_query->found_posts; ?> san pham</p>

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
                'prev_text' => 'Trang truoc',
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
        <p>Khong tim thay san pham nao.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

---

Tai lieu tham khao:
- WordPress Developer Resources: https://developer.wordpress.org/reference/classes/wp_query/
- WordPress Database Description: https://codex.wordpress.org/Database_Description
- $wpdb Class Reference: https://developer.wordpress.org/reference/classes/wpdb/
