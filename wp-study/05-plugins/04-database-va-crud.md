# Database và CRUD trong WordPress Plugin

## Mục lục

1. [Global $wpdb Object](#1-global-wpdb-object)
2. [Tạo Custom Table khi Activate Plugin](#2-tạo-custom-table-khi-activate-plugin)
3. [dbDelta() Function](#3-dbdelta-function)
4. [CRUD với $wpdb](#4-crud-với-wpdb)
5. [Prepared Statements](#5-prepared-statements)
6. [Options API](#6-options-api)
7. [Post Meta API](#7-post-meta-api)
8. [User Meta API](#8-user-meta-api)
9. [Transients API](#9-transients-api)
10. [Code ví dụ: Plugin quản lý Contacts](#10-code-ví-dụ-plugin-quản-lý-contacts)
11. [So sánh với Eloquent ORM trong Laravel](#11-so-sánh-với-eloquent-orm-trong-laravel)
12. [Best Practices](#12-best-practices)

---

## 1. Global $wpdb Object

### $wpdb là gì?

`$wpdb` là object global của class `wpdb`, cung cấp các phương thức để tương tác với database WordPress. Nó là **lớp trung gian** giữa code PHP và MySQL, tương tự như **DB Facade** trong Laravel.

```php
<?php
// Lấy $wpdb - có 2 cách

// Cách 1: Khai báo global (thường dùng trong functions)
function my_function() {
    global $wpdb;
    $results = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} LIMIT 5" );
}

// Cách 2: Dùng $GLOBALS (ít dùng hơn)
$results = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->posts} LIMIT 5" );
```

### Các thuộc tính quan trọng của $wpdb

```php
<?php
global $wpdb;

// === TABLE NAMES (có prefix) ===
// WordPress tự động thêm prefix (mặc định: wp_)

$wpdb->posts;           // wp_posts
$wpdb->postmeta;        // wp_postmeta
$wpdb->users;           // wp_users
$wpdb->usermeta;        // wp_usermeta
$wpdb->comments;        // wp_comments
$wpdb->commentmeta;     // wp_commentmeta
$wpdb->terms;           // wp_terms
$wpdb->term_taxonomy;   // wp_term_taxonomy
$wpdb->term_relationships; // wp_term_relationships
$wpdb->options;         // wp_options
$wpdb->links;           // wp_links

// === PREFIX ===
$wpdb->prefix;          // 'wp_' (hoặc prefix tùy chỉnh)
// Dùng khi tạo custom table
$my_table = $wpdb->prefix . 'my_contacts';  // wp_my_contacts

// === CHARSET ===
$wpdb->get_charset_collate();  // 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'

// === THÔNG TIN KẾT QUẢ ===
$wpdb->num_rows;         // Số dòng trả về từ query SELECT cuối
$wpdb->rows_affected;    // Số dòng bị ảnh hưởng từ query INSERT/UPDATE/DELETE cuối
$wpdb->insert_id;        // Auto-increment ID của dòng vừa INSERT
$wpdb->last_query;       // Câu query cuối cùng đã chạy
$wpdb->last_error;       // Thông báo lỗi cuối cùng (rỗng nếu không có lỗi)
$wpdb->last_result;      // Kết quả thô của query cuối
```

---

## 2. Tạo Custom Table khi Activate Plugin

```php
<?php
/**
 * Plugin Name: Database Demo
 * Description: Demo tạo và quản lý custom table.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DBD_VERSION', '1.0.0' );
define( 'DBD_DB_VERSION', '1.0.0' ); // Version riêng cho database schema

/**
 * Tạo custom table khi activate plugin.
 *
 * NGUYÊN TẮC QUAN TRỌNG:
 * - Dùng $wpdb->prefix để hỗ trợ Multisite
 * - Dùng dbDelta() để tạo/cập nhật table an toàn
 * - Lưu db_version để biết khi nào cần upgrade
 * - Dùng charset_collate để hỗ trợ Unicode
 */
register_activation_hook( __FILE__, 'dbd_create_tables' );

function dbd_create_tables() {
    global $wpdb;

    // Tên table với prefix
    $table_contacts = $wpdb->prefix . 'dbd_contacts';
    $table_notes    = $wpdb->prefix . 'dbd_contact_notes';

    // Charset và Collation (hỗ trợ Unicode/tiếng Việt)
    $charset_collate = $wpdb->get_charset_collate();

    // === TABLE 1: Contacts ===
    $sql_contacts = "CREATE TABLE {$table_contacts} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL DEFAULT '',
        last_name varchar(100) NOT NULL DEFAULT '',
        email varchar(100) NOT NULL DEFAULT '',
        phone varchar(20) NOT NULL DEFAULT '',
        company varchar(200) NOT NULL DEFAULT '',
        status enum('active','inactive','lead') NOT NULL DEFAULT 'lead',
        notes text NOT NULL,
        created_by bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY email (email),
        KEY status (status),
        KEY created_by (created_by)
    ) $charset_collate;";

    // === TABLE 2: Contact Notes (1-N relationship) ===
    $sql_notes = "CREATE TABLE {$table_notes} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        contact_id bigint(20) unsigned NOT NULL,
        note_content text NOT NULL,
        created_by bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY contact_id (contact_id)
    ) $charset_collate;";

    // Load file chứa hàm dbDelta
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Chạy dbDelta để tạo/cập nhật tables
    dbDelta( $sql_contacts );
    dbDelta( $sql_notes );

    // Lưu version database để biết khi nào cần upgrade
    update_option( 'dbd_db_version', DBD_DB_VERSION );
}

/**
 * Kiểm tra và upgrade database khi cần.
 * Chạy ở plugins_loaded để bắt upgrade khi update plugin.
 */
add_action( 'plugins_loaded', 'dbd_check_db_upgrade' );

function dbd_check_db_upgrade() {
    $installed_version = get_option( 'dbd_db_version', '0' );

    // Nếu version trong database khác version hiện tại => upgrade
    if ( version_compare( $installed_version, DBD_DB_VERSION, '<' ) ) {
        dbd_create_tables(); // dbDelta sẽ chỉ cập nhật, không tạo lại
    }
}
```

---

## 3. dbDelta() Function

### dbDelta là gì?

`dbDelta()` là hàm đặc biệt của WordPress để tạo hoặc cập nhật schema database. Nó **thông minh hơn** `CREATE TABLE` vì:

- Nếu table chưa tồn tại: **Tạo mới**
- Nếu table đã tồn tại nhưng thiếu column: **Thêm column**
- Nếu table đã tồn tại nhưng thiếu index: **Thêm index**
- **KHÔNG** xóa column hoặc table cũ

### Quy tắc SQL cho dbDelta (RẤT QUAN TRỌNG)

```php
<?php
// dbDelta rất KHẮT KHE về định dạng SQL. Phải tuân thủ chính xác:

// 1. Mỗi trường trên 1 dòng riêng
// 2. Có CHÍNH XÁC 2 dấu cách trước PRIMARY KEY
// 3. Phải có PRIMARY KEY
// 4. Dùng KEY thay vì INDEX
// 5. Không có dấu phẩy sau trường cuối cùng (trước PRIMARY KEY)
// 6. Tên trường không dùng dấu backtick `

// SAI - sẽ bị lỗi:
$sql_wrong = "CREATE TABLE {$table} (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(100),
PRIMARY KEY (id)
)";
// Lỗi: dùng backtick, PRIMARY KEY chỉ có 1 dấu cách, thiếu charset

// ĐÚNG:
$sql_correct = "CREATE TABLE {$table} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL DEFAULT '',
    PRIMARY KEY  (id)
) $charset_collate;";
// Chú ý: "PRIMARY KEY  (id)" có 2 dấu cách giữa KEY và (id)

// THÊM COLUMN MỚI (upgrade):
// Chỉ cần thêm dòng mới vào SQL rồi gọi dbDelta lại
// dbDelta sẽ tự động phát hiện và ALTER TABLE ADD COLUMN
$sql_v2 = "CREATE TABLE {$table} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL DEFAULT '',
    new_column varchar(50) NOT NULL DEFAULT '',
    PRIMARY KEY  (id)
) $charset_collate;";
// dbDelta sẽ chỉ ALTER TABLE ADD new_column, không tạo lại table
```

---

## 4. CRUD với $wpdb

### 4.1. CREATE - Thêm dữ liệu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->insert() - Thêm 1 dòng mới
 *
 * @param string $table  Tên table
 * @param array  $data   Mảng key => value
 * @param array  $format Định dạng từng trường (%s = string, %d = integer, %f = float)
 *
 * @return int|false  Số dòng đã thêm (1) hoặc false nếu lỗi
 */
$result = $wpdb->insert(
    $table,                              // Tên table
    array(                               // Dữ liệu
        'first_name' => 'Nguyen',
        'last_name'  => 'Van A',
        'email'      => 'nguyenvana@example.com',
        'phone'      => '0901234567',
        'company'    => 'Công ty ABC',
        'status'     => 'active',
        'notes'      => 'Khách hàng VIP',
        'created_by' => get_current_user_id(),
    ),
    array(                               // Format tương ứng
        '%s',  // first_name = string
        '%s',  // last_name = string
        '%s',  // email = string
        '%s',  // phone = string
        '%s',  // company = string
        '%s',  // status = string
        '%s',  // notes = string
        '%d',  // created_by = integer
    )
);

if ( $result !== false ) {
    // Lấy ID của dòng vừa thêm
    $new_id = $wpdb->insert_id;
    echo "Đã thêm contact ID: {$new_id}";
} else {
    echo "Lỗi: " . $wpdb->last_error;
}
```

### 4.2. READ - Đọc dữ liệu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->get_results() - Lấy nhiều dòng
 *
 * @param string $query   Câu SQL
 * @param string $output  Kiểu kết quả:
 *                        OBJECT  (mặc định) - Mảng các object
 *                        OBJECT_K - Object với key là cột đầu tiên
 *                        ARRAY_A  - Mảng các associative array
 *                        ARRAY_N  - Mảng các numeric array
 *
 * @return array  Mảng kết quả
 */

// Lấy tất cả contacts
$contacts = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
// $contacts = array của objects
foreach ( $contacts as $contact ) {
    echo $contact->first_name . ' ' . $contact->last_name;
    echo ' - ' . $contact->email;
}

// Lấy kết quả dạng array
$contacts_array = $wpdb->get_results(
    "SELECT * FROM {$table} ORDER BY created_at DESC",
    ARRAY_A    // Trả về associative array
);
// $contacts_array[0]['first_name'], $contacts_array[0]['email']

/**
 * $wpdb->get_row() - Lấy 1 dòng duy nhất
 *
 * @param string $query   Câu SQL
 * @param string $output  OBJECT, ARRAY_A, ARRAY_N
 * @param int    $offset  Dòng thứ mấy (0-based)
 */
$contact = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", 5 )
);
// $contact->first_name, $contact->email

/**
 * $wpdb->get_var() - Lấy 1 giá trị duy nhất (1 cell)
 *
 * @param string $query  Câu SQL
 * @param int    $col    Cột thứ mấy (0-based)
 * @param int    $row    Dòng thứ mấy (0-based)
 */

// Đếm số contacts
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
echo "Tổng: {$count} contacts";

// Lấy email của contact ID = 5
$email = $wpdb->get_var(
    $wpdb->prepare( "SELECT email FROM {$table} WHERE id = %d", 5 )
);

/**
 * $wpdb->get_col() - Lấy 1 cột (mảng giá trị)
 *
 * @param string $query  Câu SQL
 * @param int    $col    Cột thứ mấy (0-based)
 */
$all_emails = $wpdb->get_col( "SELECT email FROM {$table} WHERE status = 'active'" );
// $all_emails = array( 'email1@...', 'email2@...', ... )
```

### 4.3. UPDATE - Cập nhật dữ liệu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->update() - Cập nhật dữ liệu
 *
 * @param string $table        Tên table
 * @param array  $data         Dữ liệu cần cập nhật (key => value)
 * @param array  $where        Điều kiện WHERE (key => value)
 * @param array  $format       Format của $data
 * @param array  $where_format Format của $where
 *
 * @return int|false  Số dòng đã cập nhật hoặc false nếu lỗi
 */
$result = $wpdb->update(
    $table,
    // SET (dữ liệu cập nhật)
    array(
        'first_name' => 'Tran',
        'last_name'  => 'Van B',
        'status'     => 'active',
    ),
    // WHERE (điều kiện)
    array(
        'id' => 5,
    ),
    // Format của SET
    array( '%s', '%s', '%s' ),
    // Format của WHERE
    array( '%d' )
);

if ( $result !== false ) {
    echo "Đã cập nhật {$result} dòng.";
    // $result = 0 nếu không có gì thay đổi (dữ liệu giống cũ)
    // $result = false nếu có lỗi SQL
}

// Update nhiều dòng cùng lúc (không dùng $wpdb->update)
$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$table} SET status = %s WHERE status = %s AND created_at < %s",
        'inactive',
        'lead',
        '2024-01-01 00:00:00'
    )
);
echo "Đã cập nhật {$wpdb->rows_affected} dòng.";
```

### 4.4. DELETE - Xóa dữ liệu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->delete() - Xóa dữ liệu
 *
 * @param string $table        Tên table
 * @param array  $where        Điều kiện WHERE
 * @param array  $where_format Format của WHERE
 *
 * @return int|false  Số dòng đã xóa hoặc false nếu lỗi
 */
$result = $wpdb->delete(
    $table,
    array( 'id' => 5 ),           // WHERE id = 5
    array( '%d' )                  // id là integer
);

if ( $result !== false ) {
    echo "Đã xóa {$result} dòng.";
}

// Xóa nhiều dòng
$wpdb->delete(
    $table,
    array( 'status' => 'inactive' ),
    array( '%s' )
);

// Xóa với điều kiện phức tạp (dùng query)
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$table} WHERE status = %s AND created_at < %s",
        'inactive',
        date( 'Y-m-d', strtotime( '-1 year' ) )
    )
);
```

### 4.5. Query phức tạp

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

// === TÌM KIẾM ===
$search = 'Nguyen';
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table}
         WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s
         ORDER BY first_name ASC",
        '%' . $wpdb->esc_like( $search ) . '%',  // esc_like: escape ký tự đặc biệt LIKE
        '%' . $wpdb->esc_like( $search ) . '%',
        '%' . $wpdb->esc_like( $search ) . '%'
    )
);

// === PHÂN TRANG ===
$per_page = 10;
$current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
$offset = ( $current_page - 1 ) * $per_page;

// Đếm tổng
$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

// Lấy trang hiện tại
$contacts = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);

$total_pages = ceil( $total / $per_page );

// === JOIN TABLES ===
$table_notes = $wpdb->prefix . 'dbd_contact_notes';
$contact_with_notes = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT c.*, COUNT(n.id) as note_count
         FROM {$table} c
         LEFT JOIN {$table_notes} n ON c.id = n.contact_id
         WHERE c.status = %s
         GROUP BY c.id
         ORDER BY note_count DESC
         LIMIT %d",
        'active',
        10
    )
);

// === AGGREGATE ===
$stats = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
        SUM(CASE WHEN status = 'lead' THEN 1 ELSE 0 END) as lead_count
     FROM {$table}"
);
// $stats->total, $stats->active_count, ...
```

---

## 5. Prepared Statements

### Tại sao phải dùng Prepared Statements?

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

// === NGUY HIỂM: SQL Injection ===
// KHÔNG BAO GIỜ làm thế này!
$id = $_GET['id']; // Người dùng có thể truyền: "1 OR 1=1"
$result = $wpdb->get_row( "SELECT * FROM {$table} WHERE id = {$id}" );
// Query thực tế: SELECT * FROM wp_dbd_contacts WHERE id = 1 OR 1=1
// => Lấy TOÀN BỘ dữ liệu!

// === AN TOÀN: Dùng $wpdb->prepare() ===
$id = intval( $_GET['id'] ); // Ép kiểu trước
$result = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
);
// Query thực tế: SELECT * FROM wp_dbd_contacts WHERE id = 1
```

### Cú pháp $wpdb->prepare()

```php
<?php
global $wpdb;

/**
 * $wpdb->prepare() - Tạo prepared statement an toàn
 *
 * Placeholders:
 *   %d  = integer (số nguyên)
 *   %f  = float (số thực)
 *   %s  = string (chuỗi - tự động escape quotes)
 *
 * Trả về string SQL đã được escape an toàn
 */

// 1 placeholder
$sql = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
    42
);
// Kết quả: "SELECT * FROM wp_posts WHERE ID = 42"

// Nhiều placeholders
$sql = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s LIMIT %d",
    'post',        // %s đầu tiên
    'publish',     // %s thứ hai
    10             // %d
);

// LIKE query (phải dùng esc_like)
$search = "O'Brien"; // Có dấu ' (nguy hiểm)
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE last_name LIKE %s",
    '%' . $wpdb->esc_like( $search ) . '%'
);
// WordPress sẽ tự động escape: "... WHERE last_name LIKE '%O\'Brien%'"

// IN clause (mảng giá trị)
$statuses = array( 'active', 'lead' );
$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE status IN ({$placeholders})",
    ...$statuses    // Spread operator (PHP 5.6+)
);

// Query phức tạp
$sql = $wpdb->prepare(
    "SELECT c.*, u.display_name as created_by_name
     FROM {$table} c
     LEFT JOIN {$wpdb->users} u ON c.created_by = u.ID
     WHERE c.status = %s
       AND c.created_at BETWEEN %s AND %s
     ORDER BY c.created_at DESC
     LIMIT %d OFFSET %d",
    'active',
    '2024-01-01',
    '2024-12-31',
    10,
    0
);
```

---

## 6. Options API

Options API lưu trữ các cặp **key-value** trong bảng `wp_options`. Phù hợp cho cài đặt (settings), config.

```php
<?php
/**
 * Options API - Lưu trữ cài đặt của plugin
 * Tương tự: config() hoặc .env trong Laravel
 */

// === GET OPTION ===
/**
 * get_option() - Lấy giá trị option
 *
 * @param string $option   Tên option
 * @param mixed  $default  Giá trị mặc định nếu option chưa tồn tại
 * @return mixed           Giá trị của option
 */
$value = get_option( 'my_plugin_setting', 'default_value' );

// Lấy option là array
$settings = get_option( 'my_plugin_settings', array() );
$per_page = $settings['per_page'] ?? 10;

// === ADD OPTION ===
/**
 * add_option() - Thêm option MỚI (chỉ thêm nếu CHƯA tồn tại)
 *
 * @param string $option     Tên option
 * @param mixed  $value      Giá trị
 * @param string $deprecated Bỏ qua (compat)
 * @param bool   $autoload   Tự động load mỗi request (yes/no)
 */
add_option( 'my_plugin_version', '1.0.0' );

// Không autoload (cho dữ liệu lớn, ít dùng)
add_option( 'my_plugin_large_data', $large_array, '', 'no' );

// === UPDATE OPTION ===
/**
 * update_option() - Cập nhật option (tạo mới nếu chưa tồn tại)
 *
 * @param string $option   Tên option
 * @param mixed  $value    Giá trị mới
 * @param bool   $autoload Autoload (từ WP 4.2)
 */
update_option( 'my_plugin_setting', 'new_value' );

// Update array
$settings = get_option( 'my_plugin_settings', array() );
$settings['per_page'] = 20;
update_option( 'my_plugin_settings', $settings );

// === DELETE OPTION ===
/**
 * delete_option() - Xóa option
 */
delete_option( 'my_plugin_setting' );

// === OPTION CÓ THỂ LƯU NHIỀU KIỂU DỮ LIỆU ===
// String
update_option( 'my_string', 'Hello World' );

// Integer
update_option( 'my_number', 42 );

// Boolean
update_option( 'my_bool', true );

// Array (tự động serialize/unserialize)
update_option( 'my_array', array(
    'key1' => 'value1',
    'key2' => array( 'nested' => true ),
));

// Object (tự động serialize)
$obj = new stdClass();
$obj->name = 'Test';
update_option( 'my_object', $obj );

// LƯU Ý: WordPress tự động serialize mảng và object khi lưu,
// và tự động unserialize khi đọc. Bạn không cần tự làm.
```

### Khi nào dùng Options API vs Custom Table?

```
Options API phù hợp khi:
- Dữ liệu là cài đặt, config (ít thay đổi)
- Dữ liệu nhỏ, ít bản ghi (< 100)
- Không cần tìm kiếm, sắp xếp phức tạp
- Chia sẻ giữa các phần của plugin

Custom Table phù hợp khi:
- Dữ liệu nhiều bản ghi (> 100)
- Cần tìm kiếm, lọc, sắp xếp
- Cần JOIN với các bảng khác
- Dữ liệu có cấu trúc cố định (schema)
- Cần index để tối ưu performance
```

---

## 7. Post Meta API

Post Meta lưu trữ **dữ liệu bổ sung** cho mỗi post/page. Lưu trong bảng `wp_postmeta`.

```php
<?php
/**
 * Post Meta API - Lưu dữ liệu cho từng bài viết
 * Tương tự: JSON column hoặc pivot table trong Laravel
 */

$post_id = 123;

// === ADD POST META ===
/**
 * add_post_meta() - Thêm meta cho post
 *
 * @param int    $post_id   ID bài viết
 * @param string $meta_key  Tên meta
 * @param mixed  $value     Giá trị
 * @param bool   $unique    true = chỉ 1 giá trị, false = nhiều giá trị cùng key
 */
add_post_meta( $post_id, '_my_plugin_price', 150000, true );
// _ (underscore) ở đầu key = hidden (không hiện trong Custom Fields UI)

// Cho phép nhiều giá trị cùng key
add_post_meta( $post_id, '_my_plugin_gallery', 'image1.jpg', false );
add_post_meta( $post_id, '_my_plugin_gallery', 'image2.jpg', false );

// === GET POST META ===
/**
 * get_post_meta() - Lấy meta của post
 *
 * @param int    $post_id   ID bài viết
 * @param string $meta_key  Tên meta ('' = lấy tất cả)
 * @param bool   $single    true = trả về giá trị, false = trả về array
 */

// Lấy 1 giá trị (single = true)
$price = get_post_meta( $post_id, '_my_plugin_price', true );
// $price = 150000

// Lấy nhiều giá trị (single = false)
$gallery = get_post_meta( $post_id, '_my_plugin_gallery', false );
// $gallery = array( 'image1.jpg', 'image2.jpg' )

// Lấy TẤT CẢ meta của post
$all_meta = get_post_meta( $post_id );
// $all_meta = array( '_my_plugin_price' => array('150000'), ... )

// === UPDATE POST META ===
/**
 * update_post_meta() - Cập nhật meta (tạo mới nếu chưa có)
 *
 * @param int    $post_id    ID bài viết
 * @param string $meta_key   Tên meta
 * @param mixed  $value      Giá trị mới
 * @param mixed  $prev_value Giá trị cũ (để cập nhật chính xác khi có nhiều giá trị)
 */
update_post_meta( $post_id, '_my_plugin_price', 200000 );

// Lưu array (tự động serialize)
update_post_meta( $post_id, '_my_plugin_settings', array(
    'color'    => 'red',
    'size'     => 'large',
    'featured' => true,
));

// === DELETE POST META ===
/**
 * delete_post_meta() - Xóa meta
 *
 * @param int    $post_id    ID bài viết
 * @param string $meta_key   Tên meta
 * @param mixed  $meta_value Giá trị cụ thể (nếu chỉ muốn xóa 1 giá trị trong nhiều giá trị)
 */
delete_post_meta( $post_id, '_my_plugin_price' );

// Xóa 1 giá trị cụ thể trong nhiều giá trị
delete_post_meta( $post_id, '_my_plugin_gallery', 'image1.jpg' );

// === QUERY THEO META ===
$expensive_products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',   // AND hoặc OR
        array(
            'key'     => '_my_plugin_price',
            'value'   => 100000,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => '_my_plugin_featured',
            'value'   => '1',
            'compare' => '=',
        ),
    ),
    'orderby'  => 'meta_value_num',
    'meta_key' => '_my_plugin_price',
    'order'    => 'DESC',
));
```

---

## 8. User Meta API

```php
<?php
/**
 * User Meta API - Lưu dữ liệu cho từng người dùng
 * Cú pháp giống Post Meta nhưng cho users
 */

$user_id = get_current_user_id();

// Thêm
add_user_meta( $user_id, 'my_plugin_preferences', array(
    'theme'        => 'dark',
    'notification' => true,
    'language'     => 'vi',
), true );

// Lấy
$prefs = get_user_meta( $user_id, 'my_plugin_preferences', true );
$theme = $prefs['theme'] ?? 'light';

// Cập nhật
$prefs['theme'] = 'light';
update_user_meta( $user_id, 'my_plugin_preferences', $prefs );

// Xóa
delete_user_meta( $user_id, 'my_plugin_preferences' );

// Query users theo meta
$dark_theme_users = get_users( array(
    'meta_key'   => 'my_plugin_preferences',
    'meta_value' => 'dark',
    'meta_compare' => 'LIKE',    // Tìm trong serialized data
));

// Thêm trường vào trang Profile
add_action( 'show_user_profile', 'my_plugin_user_fields' );
add_action( 'edit_user_profile', 'my_plugin_user_fields' );

function my_plugin_user_fields( $user ) {
    $phone = get_user_meta( $user->ID, 'my_plugin_phone', true );
    ?>
    <h3>Thông tin bổ sung</h3>
    <table class="form-table">
        <tr>
            <th><label for="my_plugin_phone">Số điện thoại</label></th>
            <td>
                <input type="tel" name="my_plugin_phone" id="my_plugin_phone"
                       value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    <?php
}

// Lưu trường khi update profile
add_action( 'personal_options_update', 'my_plugin_save_user_fields' );
add_action( 'edit_user_profile_update', 'my_plugin_save_user_fields' );

function my_plugin_save_user_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return;
    update_user_meta( $user_id, 'my_plugin_phone',
        sanitize_text_field( $_POST['my_plugin_phone'] ?? '' )
    );
}
```

---

## 9. Transients API

Transients là **cache tạm thời** lưu trong database (hoặc object cache nếu có). Tự động hết hạn.

```php
<?php
/**
 * Transients API - Cache tạm thời
 * Tương tự: Cache::remember() trong Laravel
 */

// === SET TRANSIENT ===
/**
 * set_transient() - Lưu dữ liệu tạm thời
 *
 * @param string $transient  Tên transient
 * @param mixed  $value      Giá trị
 * @param int    $expiration Thời gian hết hạn (giây), 0 = không hết hạn
 */

// Cache kết quả API trong 1 giờ
$api_data = wp_remote_get( 'https://api.example.com/data' );
if ( ! is_wp_error( $api_data ) ) {
    $data = json_decode( wp_remote_retrieve_body( $api_data ), true );
    set_transient( 'my_plugin_api_data', $data, HOUR_IN_SECONDS );
}

// Các hằng thời gian có sẵn:
// MINUTE_IN_SECONDS  = 60
// HOUR_IN_SECONDS    = 3600
// DAY_IN_SECONDS     = 86400
// WEEK_IN_SECONDS    = 604800
// MONTH_IN_SECONDS   = 2592000 (30 ngày)
// YEAR_IN_SECONDS    = 31536000

// === GET TRANSIENT ===
/**
 * get_transient() - Lấy dữ liệu từ cache
 *
 * @return mixed  Dữ liệu hoặc false nếu hết hạn/không tồn tại
 */

// Pattern thường dùng: Check cache trước, query nếu không có
$data = get_transient( 'my_plugin_api_data' );

if ( false === $data ) {
    // Cache hết hạn hoặc chưa có => gọi API
    $response = wp_remote_get( 'https://api.example.com/data' );
    if ( ! is_wp_error( $response ) ) {
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        // Lưu cache 1 giờ
        set_transient( 'my_plugin_api_data', $data, HOUR_IN_SECONDS );
    }
}

// Sử dụng $data...

// === DELETE TRANSIENT ===
/**
 * delete_transient() - Xóa cache
 * Dùng khi dữ liệu thay đổi và cần cập nhật cache
 */
delete_transient( 'my_plugin_api_data' );

// === VÍ DỤ THỰC TẾ: Cache danh sách bài viết phổ biến ===
function my_plugin_get_popular_posts( $count = 5 ) {
    $cache_key = 'my_plugin_popular_posts_' . $count;
    $posts = get_transient( $cache_key );

    if ( false === $posts ) {
        // Query nặng - chỉ chạy khi cache hết hạn
        $posts = get_posts( array(
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'meta_key'       => 'post_views_count',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ));

        // Cache 30 phút
        set_transient( $cache_key, $posts, 30 * MINUTE_IN_SECONDS );
    }

    return $posts;
}

// Xóa cache khi có bài viết mới
add_action( 'save_post', function( $post_id ) {
    // Xóa tất cả transients liên quan
    delete_transient( 'my_plugin_popular_posts_5' );
    delete_transient( 'my_plugin_popular_posts_10' );
});
```

### So sánh các cách lưu dữ liệu

```
+-------------------+----------------+----------------+------------------+
| Phương pháp       | Phạm vi        | Hết hạn?       | Use case         |
+-------------------+----------------+----------------+------------------+
| Options API       | Toàn site      | Không          | Settings, config |
| Post Meta         | 1 post         | Không          | Data của post    |
| User Meta         | 1 user         | Không          | Data của user    |
| Transients        | Toàn site      | Có             | Cache, temp data |
| Custom Table      | Tùy chỉnh      | Không          | Dữ liệu phức tạp |
+-------------------+----------------+----------------+------------------+
```

---

## 10. Code ví dụ: Plugin quản lý Contacts

```php
<?php
/**
 * Plugin Name:       Contacts Manager
 * Description:       Plugin quản lý danh sách liên hệ với CRUD hoàn chỉnh.
 * Version:           1.0.0
 * Author:            Developer
 * Text Domain:       contacts-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CM_VERSION', '1.0.0' );
define( 'CM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class Contacts_Manager {

    private static $instance = null;
    private $table_name;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cm_contacts';

        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'handle_form_actions' ) );
    }

    // === ACTIVATION: Tạo table ===
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cm_contacts';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL DEFAULT '',
            last_name varchar(100) NOT NULL DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            phone varchar(20) NOT NULL DEFAULT '',
            company varchar(200) NOT NULL DEFAULT '',
            address text NOT NULL,
            status enum('active','inactive','lead') NOT NULL DEFAULT 'lead',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Thêm dữ liệu mẫu
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        if ( intval( $count ) === 0 ) {
            $samples = array(
                array( 'Nguyen', 'Van A', 'nguyenvana@email.com', '0901234567', 'Công ty ABC', 'Hà Nội', 'active' ),
                array( 'Tran', 'Thi B', 'tranthib@email.com', '0912345678', 'Công ty XYZ', 'TP HCM', 'active' ),
                array( 'Le', 'Van C', 'levanc@email.com', '0923456789', 'Công ty DEF', 'Đà Nẵng', 'lead' ),
                array( 'Pham', 'Thi D', 'phamthid@email.com', '0934567890', 'Công ty GHI', 'Hải Phòng', 'inactive' ),
                array( 'Hoang', 'Van E', 'hoangvane@email.com', '0945678901', 'Công ty JKL', 'Cần Thơ', 'lead' ),
            );
            foreach ( $samples as $s ) {
                $wpdb->insert( $table_name, array(
                    'first_name' => $s[0], 'last_name' => $s[1], 'email' => $s[2],
                    'phone' => $s[3], 'company' => $s[4], 'address' => $s[5],
                    'status' => $s[6], 'created_by' => 1,
                ), array( '%s','%s','%s','%s','%s','%s','%s','%d' ));
            }
        }

        update_option( 'cm_db_version', '1.0.0' );
    }

    // === DEACTIVATION ===
    public static function deactivate() {
        // Không xóa data
    }

    // === ADD MENU ===
    public function add_menu() {
        add_menu_page(
            'Quản lý Liên hệ',
            'Contacts',
            'manage_options',
            'cm-contacts',
            array( $this, 'render_page' ),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'cm-contacts',
            'Tất cả Liên hệ',
            'Tất cả',
            'manage_options',
            'cm-contacts',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'cm-contacts',
            'Thêm Liên hệ mới',
            'Thêm mới',
            'manage_options',
            'cm-contacts-add',
            array( $this, 'render_add_page' )
        );
    }

    // === ENQUEUE ASSETS ===
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'cm-contacts' ) === false ) return;

        wp_add_inline_style( 'common', '
            .cm-wrap { max-width: 1200px; }
            .cm-stats { display: flex; gap: 15px; margin: 20px 0; }
            .cm-stat-card {
                background: #fff; border: 1px solid #ddd; border-radius: 5px;
                padding: 15px 25px; text-align: center; flex: 1;
            }
            .cm-stat-number { font-size: 28px; font-weight: bold; color: #0073aa; }
            .cm-stat-label { color: #666; font-size: 13px; }
            .cm-table { width: 100%; border-collapse: collapse; background: #fff; }
            .cm-table th, .cm-table td { padding: 10px 15px; text-align: left; border-bottom: 1px solid #eee; }
            .cm-table th { background: #f5f5f5; font-weight: 600; }
            .cm-table tr:hover { background: #f9f9f9; }
            .cm-status { padding: 3px 10px; border-radius: 12px; font-size: 12px; }
            .cm-status-active { background: #ddffdd; color: #46b450; }
            .cm-status-inactive { background: #ffdddd; color: #dc3232; }
            .cm-status-lead { background: #fff3cd; color: #856404; }
            .cm-actions a { margin-right: 10px; text-decoration: none; }
            .cm-form { max-width: 700px; }
            .cm-form .form-table th { width: 150px; }
            .cm-search-box { margin: 15px 0; }
            .cm-pagination { margin: 20px 0; }
        ');
    }

    // === XỬ LÝ FORM ACTIONS ===
    public function handle_form_actions() {
        global $wpdb;

        // --- CREATE ---
        if ( isset( $_POST['cm_action'] ) && $_POST['cm_action'] === 'create' ) {
            check_admin_referer( 'cm_create_contact' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Không có quyền.' );
            }

            $data = $this->sanitize_contact_data( $_POST );
            $errors = $this->validate_contact_data( $data );

            if ( ! empty( $errors ) ) {
                set_transient( 'cm_form_errors', $errors, 30 );
                set_transient( 'cm_form_data', $data, 30 );
                wp_redirect( admin_url( 'admin.php?page=cm-contacts-add' ) );
                exit;
            }

            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'email'      => $data['email'],
                    'phone'      => $data['phone'],
                    'company'    => $data['company'],
                    'address'    => $data['address'],
                    'status'     => $data['status'],
                    'created_by' => get_current_user_id(),
                ),
                array( '%s','%s','%s','%s','%s','%s','%s','%d' )
            );

            if ( $result ) {
                set_transient( 'cm_notice', array( 'type' => 'success', 'message' => 'Đã thêm liên hệ thành công!' ), 30 );
                wp_redirect( admin_url( 'admin.php?page=cm-contacts' ) );
            } else {
                set_transient( 'cm_notice', array( 'type' => 'error', 'message' => 'Lỗi: ' . $wpdb->last_error ), 30 );
                wp_redirect( admin_url( 'admin.php?page=cm-contacts-add' ) );
            }
            exit;
        }

        // --- UPDATE ---
        if ( isset( $_POST['cm_action'] ) && $_POST['cm_action'] === 'update' ) {
            check_admin_referer( 'cm_update_contact' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Không có quyền.' );
            }

            $id = absint( $_POST['contact_id'] ?? 0 );
            $data = $this->sanitize_contact_data( $_POST );
            $errors = $this->validate_contact_data( $data );

            if ( ! empty( $errors ) ) {
                set_transient( 'cm_form_errors', $errors, 30 );
                wp_redirect( admin_url( 'admin.php?page=cm-contacts&action=edit&id=' . $id ) );
                exit;
            }

            $wpdb->update(
                $this->table_name,
                array(
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'email'      => $data['email'],
                    'phone'      => $data['phone'],
                    'company'    => $data['company'],
                    'address'    => $data['address'],
                    'status'     => $data['status'],
                ),
                array( 'id' => $id ),
                array( '%s','%s','%s','%s','%s','%s','%s' ),
                array( '%d' )
            );

            set_transient( 'cm_notice', array( 'type' => 'success', 'message' => 'Đã cập nhật thành công!' ), 30 );
            wp_redirect( admin_url( 'admin.php?page=cm-contacts' ) );
            exit;
        }

        // --- DELETE ---
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            check_admin_referer( 'cm_delete_contact' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Không có quyền.' );
            }

            $id = absint( $_GET['id'] );
            $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );

            set_transient( 'cm_notice', array( 'type' => 'success', 'message' => 'Đã xóa liên hệ.' ), 30 );
            wp_redirect( admin_url( 'admin.php?page=cm-contacts' ) );
            exit;
        }
    }

    // === SANITIZE ===
    private function sanitize_contact_data( $input ) {
        return array(
            'first_name' => sanitize_text_field( $input['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $input['last_name'] ?? '' ),
            'email'      => sanitize_email( $input['email'] ?? '' ),
            'phone'      => sanitize_text_field( $input['phone'] ?? '' ),
            'company'    => sanitize_text_field( $input['company'] ?? '' ),
            'address'    => sanitize_textarea_field( $input['address'] ?? '' ),
            'status'     => in_array( $input['status'] ?? '', array( 'active', 'inactive', 'lead' ) )
                           ? $input['status'] : 'lead',
        );
    }

    // === VALIDATE ===
    private function validate_contact_data( $data ) {
        $errors = array();
        if ( empty( $data['first_name'] ) ) $errors[] = 'Họ tên không được để trống.';
        if ( empty( $data['email'] ) ) $errors[] = 'Email không được để trống.';
        if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) $errors[] = 'Email không hợp lệ.';
        return $errors;
    }

    // === TRANG DANH SÁCH ===
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Kiểm tra action
        $action = sanitize_text_field( $_GET['action'] ?? 'list' );

        if ( $action === 'edit' && isset( $_GET['id'] ) ) {
            $this->render_edit_page();
            return;
        }

        if ( $action === 'view' && isset( $_GET['id'] ) ) {
            $this->render_view_page();
            return;
        }

        global $wpdb;

        // Hiển thị thông báo
        $notice = get_transient( 'cm_notice' );
        if ( $notice ) delete_transient( 'cm_notice' );

        // Tìm kiếm
        $search = sanitize_text_field( $_GET['s'] ?? '' );
        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );

        // Đếm tổng
        $where = "WHERE 1=1";
        $params = array();

        if ( ! empty( $search ) ) {
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR company LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params = array_merge( $params, array( $like, $like, $like, $like ) );
        }

        if ( ! empty( $status_filter ) ) {
            $where .= " AND status = %s";
            $params[] = $status_filter;
        }

        // Tổng số bản ghi
        $total_query = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        if ( ! empty( $params ) ) {
            $total = $wpdb->get_var( $wpdb->prepare( $total_query, ...$params ) );
        } else {
            $total = $wpdb->get_var( $total_query );
        }

        // Phân trang
        $per_page = 10;
        $current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $offset = ( $current_page - 1 ) * $per_page;
        $total_pages = ceil( $total / $per_page );

        // Lấy dữ liệu
        $order = sanitize_sql_orderby( $_GET['orderby'] ?? 'created_at' ) ?: 'created_at';
        $order_dir = strtoupper( $_GET['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

        $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY {$order} {$order_dir} LIMIT %d OFFSET %d";
        $all_params = array_merge( $params, array( $per_page, $offset ) );
        $contacts = $wpdb->get_results( $wpdb->prepare( $query, ...$all_params ) );

        // Thống kê
        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status='inactive' THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN status='lead' THEN 1 ELSE 0 END) as lead_count
             FROM {$this->table_name}"
        );

        // Render
        ?>
        <div class="wrap cm-wrap">
            <h1 class="wp-heading-inline">Quản lý Liên hệ</h1>
            <a href="<?php echo admin_url( 'admin.php?page=cm-contacts-add' ); ?>" class="page-title-action">Thêm mới</a>
            <hr class="wp-header-end">

            <?php if ( $notice ) : ?>
                <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
                    <p><?php echo esc_html( $notice['message'] ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Thống kê -->
            <div class="cm-stats">
                <div class="cm-stat-card">
                    <div class="cm-stat-number"><?php echo intval( $stats->total ); ?></div>
                    <div class="cm-stat-label">Tổng cộng</div>
                </div>
                <div class="cm-stat-card">
                    <div class="cm-stat-number" style="color:#46b450;"><?php echo intval( $stats->active_count ); ?></div>
                    <div class="cm-stat-label">Đang hoạt động</div>
                </div>
                <div class="cm-stat-card">
                    <div class="cm-stat-number" style="color:#856404;"><?php echo intval( $stats->lead_count ); ?></div>
                    <div class="cm-stat-label">Tiềm năng</div>
                </div>
                <div class="cm-stat-card">
                    <div class="cm-stat-number" style="color:#dc3232;"><?php echo intval( $stats->inactive_count ); ?></div>
                    <div class="cm-stat-label">Ngừng hoạt động</div>
                </div>
            </div>

            <!-- Lọc và Tìm kiếm -->
            <div class="cm-search-box">
                <form method="get" style="display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="page" value="cm-contacts">

                    <select name="status">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="active" <?php selected( $status_filter, 'active' ); ?>>Active</option>
                        <option value="inactive" <?php selected( $status_filter, 'inactive' ); ?>>Inactive</option>
                        <option value="lead" <?php selected( $status_filter, 'lead' ); ?>>Lead</option>
                    </select>

                    <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>"
                           placeholder="Tìm kiếm tên, email, công ty..."
                           style="width:300px;">

                    <button type="submit" class="button">Tìm kiếm</button>

                    <?php if ( ! empty( $search ) || ! empty( $status_filter ) ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=cm-contacts' ); ?>" class="button">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bảng dữ liệu -->
            <table class="cm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Công ty</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $contacts ) ) : ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Không có dữ liệu.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $contacts as $i => $contact ) : ?>
                            <tr>
                                <td><?php echo $offset + $i + 1; ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cm-contacts&action=view&id=' . $contact->id ) ); ?>">
                                            <?php echo esc_html( $contact->first_name . ' ' . $contact->last_name ); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html( $contact->email ); ?></td>
                                <td><?php echo esc_html( $contact->phone ); ?></td>
                                <td><?php echo esc_html( $contact->company ); ?></td>
                                <td>
                                    <span class="cm-status cm-status-<?php echo esc_attr( $contact->status ); ?>">
                                        <?php
                                        $status_labels = array( 'active' => 'Active', 'inactive' => 'Inactive', 'lead' => 'Lead' );
                                        echo esc_html( $status_labels[ $contact->status ] ?? $contact->status );
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $contact->created_at ) ) ); ?></td>
                                <td class="cm-actions">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cm-contacts&action=edit&id=' . $contact->id ) ); ?>"
                                       style="color:#0073aa;">Sửa</a>
                                    <a href="<?php echo esc_url( wp_nonce_url(
                                        admin_url( 'admin.php?page=cm-contacts&action=delete&id=' . $contact->id ),
                                        'cm_delete_contact'
                                    ) ); ?>"
                                       style="color:#dc3232;"
                                       onclick="return confirm('Bạn có chắc muốn xóa liên hệ này?');">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Phân trang -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="cm-pagination">
                    <?php
                    $page_links = paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'prev_text' => '&laquo; Trước',
                        'next_text' => 'Sau &raquo;',
                    ));
                    echo $page_links;
                    ?>
                    <span style="margin-left:15px; color:#666;">
                        Trang <?php echo $current_page; ?>/<?php echo $total_pages; ?>
                        (<?php echo $total; ?> kết quả)
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // === TRANG THÊM MỚI ===
    public function render_add_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $errors = get_transient( 'cm_form_errors' );
        $data = get_transient( 'cm_form_data' );
        if ( $errors ) delete_transient( 'cm_form_errors' );
        if ( $data ) delete_transient( 'cm_form_data' );

        $data = $data ?: array();
        $this->render_form( 'create', $data, $errors );
    }

    // === TRANG CHỈNH SỬA ===
    private function render_edit_page() {
        global $wpdb;

        $id = absint( $_GET['id'] ?? 0 );
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( ! $contact ) {
            echo '<div class="wrap"><h1>Không tìm thấy liên hệ.</h1></div>';
            return;
        }

        $errors = get_transient( 'cm_form_errors' );
        if ( $errors ) delete_transient( 'cm_form_errors' );

        $this->render_form( 'update', $contact, $errors );
    }

    // === TRANG XEM CHI TIẾT ===
    private function render_view_page() {
        global $wpdb;

        $id = absint( $_GET['id'] ?? 0 );
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $contact ) {
            echo '<div class="wrap"><h1>Không tìm thấy liên hệ.</h1></div>';
            return;
        }

        $creator = get_userdata( $contact->created_by );
        ?>
        <div class="wrap">
            <h1>
                Chi tiết liên hệ
                <a href="<?php echo admin_url( 'admin.php?page=cm-contacts&action=edit&id=' . $id ); ?>" class="page-title-action">Sửa</a>
                <a href="<?php echo admin_url( 'admin.php?page=cm-contacts' ); ?>" class="page-title-action">Quay lại</a>
            </h1>
            <div style="background:#fff; padding:25px; border:1px solid #ddd; border-radius:5px; max-width:600px; margin-top:15px;">
                <table class="form-table">
                    <tr><th>Họ:</th><td><?php echo esc_html( $contact->first_name ); ?></td></tr>
                    <tr><th>Tên:</th><td><?php echo esc_html( $contact->last_name ); ?></td></tr>
                    <tr><th>Email:</th><td><a href="mailto:<?php echo esc_attr( $contact->email ); ?>"><?php echo esc_html( $contact->email ); ?></a></td></tr>
                    <tr><th>Điện thoại:</th><td><?php echo esc_html( $contact->phone ); ?></td></tr>
                    <tr><th>Công ty:</th><td><?php echo esc_html( $contact->company ); ?></td></tr>
                    <tr><th>Địa chỉ:</th><td><?php echo nl2br( esc_html( $contact->address ) ); ?></td></tr>
                    <tr><th>Trạng thái:</th><td><span class="cm-status cm-status-<?php echo esc_attr( $contact->status ); ?>"><?php echo esc_html( $contact->status ); ?></span></td></tr>
                    <tr><th>Người tạo:</th><td><?php echo esc_html( $creator ? $creator->display_name : 'N/A' ); ?></td></tr>
                    <tr><th>Ngày tạo:</th><td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $contact->created_at ) ) ); ?></td></tr>
                    <tr><th>Cập nhật:</th><td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $contact->updated_at ) ) ); ?></td></tr>
                </table>
            </div>
        </div>
        <?php
    }

    // === RENDER FORM (dùng chung cho Add và Edit) ===
    private function render_form( $action, $data, $errors = null ) {
        $is_edit = ( $action === 'update' );
        $title = $is_edit ? 'Chỉnh sửa Liên hệ' : 'Thêm Liên hệ mới';
        $nonce_action = $is_edit ? 'cm_update_contact' : 'cm_create_contact';
        ?>
        <div class="wrap cm-form">
            <h1>
                <?php echo esc_html( $title ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=cm-contacts' ); ?>" class="page-title-action">Quay lại</a>
            </h1>

            <?php if ( ! empty( $errors ) ) : ?>
                <div class="notice notice-error">
                    <?php foreach ( $errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"
                  style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:5px; margin-top:15px;">

                <?php wp_nonce_field( $nonce_action ); ?>
                <input type="hidden" name="cm_action" value="<?php echo esc_attr( $action ); ?>">
                <?php if ( $is_edit ) : ?>
                    <input type="hidden" name="contact_id" value="<?php echo esc_attr( $data['id'] ?? '' ); ?>">
                <?php endif; ?>

                <!-- Form gửi trực tiếp về admin.php (xử lý trong admin_init) -->
                <input type="hidden" name="action" value="cm_form">

                <table class="form-table">
                    <tr>
                        <th><label for="first_name">Họ <span style="color:red;">*</span></label></th>
                        <td><input type="text" name="first_name" id="first_name" class="regular-text"
                                   value="<?php echo esc_attr( $data['first_name'] ?? '' ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Tên</label></th>
                        <td><input type="text" name="last_name" id="last_name" class="regular-text"
                                   value="<?php echo esc_attr( $data['last_name'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email <span style="color:red;">*</span></label></th>
                        <td><input type="email" name="email" id="email" class="regular-text"
                                   value="<?php echo esc_attr( $data['email'] ?? '' ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Điện thoại</label></th>
                        <td><input type="tel" name="phone" id="phone" class="regular-text"
                                   value="<?php echo esc_attr( $data['phone'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="company">Công ty</label></th>
                        <td><input type="text" name="company" id="company" class="regular-text"
                                   value="<?php echo esc_attr( $data['company'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="address">Địa chỉ</label></th>
                        <td><textarea name="address" id="address" rows="3" class="large-text"><?php
                            echo esc_textarea( $data['address'] ?? '' );
                        ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="status">Trạng thái</label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="lead" <?php selected( $data['status'] ?? '', 'lead' ); ?>>Lead (Tiềm năng)</option>
                                <option value="active" <?php selected( $data['status'] ?? '', 'active' ); ?>>Active (Hoạt động)</option>
                                <option value="inactive" <?php selected( $data['status'] ?? '', 'inactive' ); ?>>Inactive (Ngừng)</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button( $is_edit ? 'Cập nhật' : 'Thêm mới' ); ?>
            </form>
        </div>
        <?php
    }
}

// === HOOKS ===
register_activation_hook( __FILE__, array( 'Contacts_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Contacts_Manager', 'deactivate' ) );

// Khởi tạo plugin
add_action( 'plugins_loaded', function() {
    Contacts_Manager::get_instance();
});
```

---

## 11. So sánh với Eloquent ORM trong Laravel

```php
<?php
/**
 * LARAVEL: Eloquent ORM - Tương tác database bằng Model objects
 */

// Migration
// Schema::create('contacts', function (Blueprint $table) {
//     $table->id();
//     $table->string('first_name');
//     $table->string('email')->unique();
//     $table->enum('status', ['active', 'inactive', 'lead'])->default('lead');
//     $table->timestamps();
// });

// Model
// class Contact extends Model {
//     protected $fillable = ['first_name', 'last_name', 'email', 'status'];
// }

// CRUD
// $contact = Contact::create(['first_name' => 'A', 'email' => 'a@b.com']);  // CREATE
// $contacts = Contact::where('status', 'active')->paginate(10);              // READ
// $contact->update(['first_name' => 'B']);                                    // UPDATE
// $contact->delete();                                                        // DELETE

/**
 * WORDPRESS: $wpdb - Tương tác database bằng SQL queries
 */

// Tạo table (trong activation hook)
// dbDelta("CREATE TABLE {$wpdb->prefix}contacts (...)");

// CREATE
// $wpdb->insert('wp_contacts', ['first_name' => 'A', 'email' => 'a@b.com'], ['%s', '%s']);

// READ
// $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_contacts WHERE status = %s LIMIT %d OFFSET %d", 'active', 10, 0));

// UPDATE
// $wpdb->update('wp_contacts', ['first_name' => 'B'], ['id' => 1], ['%s'], ['%d']);

// DELETE
// $wpdb->delete('wp_contacts', ['id' => 1], ['%d']);
```

### Bảng so sánh

| Tính năng | Laravel Eloquent | WordPress $wpdb |
|-----------|-----------------|-----------------|
| **Cách tiếp cận** | ORM (Object-Relational Mapping) | Query Builder / Raw SQL |
| **Tạo table** | Migration files | dbDelta() |
| **Model** | Class extend Model | Không có (tự viết) |
| **Query Builder** | `User::where()->get()` | `$wpdb->prepare()` + SQL |
| **Relationships** | `hasMany`, `belongsTo` | Tự viết JOIN |
| **Pagination** | `->paginate(10)` | Tự tính LIMIT/OFFSET |
| **Validation** | Form Request | Tự viết |
| **Mass Assignment** | `$fillable`, `$guarded` | Không có |
| **Soft Delete** | `SoftDeletes` trait | Tự thêm column |
| **Events** | Model Events | Hooks (actions/filters) |
| **Cache** | `Cache::remember()` | `get_transient()` |
| **Tinker** | `php artisan tinker` | WP-CLI |

---

## 12. Best Practices

### 1. Luôn dùng $wpdb->prepare()

```php
<?php
// SAI
$wpdb->query( "DELETE FROM {$table} WHERE id = {$_GET['id']}" );

// ĐÚNG
$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id = %d", absint( $_GET['id'] ) ) );
```

### 2. Luôn dùng prefix

```php
<?php
// SAI - hardcode tên table
$wpdb->get_results( "SELECT * FROM wp_my_table" );

// ĐÚNG - dùng prefix
$wpdb->get_results( "SELECT * FROM {$wpdb->prefix}my_table" );
```

### 3. Kiểm tra lỗi sau mỗi query

```php
<?php
$result = $wpdb->insert( $table, $data, $format );
if ( false === $result ) {
    error_log( 'DB Error: ' . $wpdb->last_error );
}
```

### 4. Dùng dbDelta đúng cách

```php
<?php
// Nhớ: 2 dấu cách trước PRIMARY KEY
// Không dùng backtick cho tên trường
// Mỗi trường trên 1 dòng
```

### 5. Dọn dẹp khi uninstall

```php
<?php
// Trong uninstall.php
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_table" );
delete_option( 'my_plugin_db_version' );
```

### 6. Dùng Transients cho dữ liệu nặng

```php
<?php
$data = get_transient( 'expensive_query' );
if ( false === $data ) {
    $data = $wpdb->get_results( "SELECT ... phức tạp ..." );
    set_transient( 'expensive_query', $data, HOUR_IN_SECONDS );
}
```

---

## Tham khảo

- [WordPress Database API ($wpdb)](https://developer.wordpress.org/reference/classes/wpdb/)
- [Creating Tables with Plugins](https://developer.wordpress.org/plugins/creating-tables-with-plugins/)
- [Options API](https://developer.wordpress.org/plugins/settings/options-api/)
- [Metadata API](https://developer.wordpress.org/plugins/metadata/)
- [Transients API](https://developer.wordpress.org/apis/transients/)
