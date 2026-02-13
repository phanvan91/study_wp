# Database va CRUD trong WordPress Plugin

## Muc luc

1. [Global $wpdb Object](#1-global-wpdb-object)
2. [Tao Custom Table khi Activate Plugin](#2-tao-custom-table-khi-activate-plugin)
3. [dbDelta() Function](#3-dbdelta-function)
4. [CRUD voi $wpdb](#4-crud-voi-wpdb)
5. [Prepared Statements](#5-prepared-statements)
6. [Options API](#6-options-api)
7. [Post Meta API](#7-post-meta-api)
8. [User Meta API](#8-user-meta-api)
9. [Transients API](#9-transients-api)
10. [Code vi du: Plugin quan ly Contacts](#10-code-vi-du-plugin-quan-ly-contacts)
11. [So sanh voi Eloquent ORM trong Laravel](#11-so-sanh-voi-eloquent-orm-trong-laravel)
12. [Best Practices](#12-best-practices)

---

## 1. Global $wpdb Object

### $wpdb la gi?

`$wpdb` la object global cua class `wpdb`, cung cap cac phuong thuc de tuong tac voi database WordPress. No la **lop trung gian** giua code PHP va MySQL, tuong tu nhu **DB Facade** trong Laravel.

```php
<?php
// Lay $wpdb - co 2 cach

// Cach 1: Khai bao global (thuong dung trong functions)
function my_function() {
    global $wpdb;
    $results = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} LIMIT 5" );
}

// Cach 2: Dung $GLOBALS (it dung hon)
$results = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->posts} LIMIT 5" );
```

### Cac thuoc tinh quan trong cua $wpdb

```php
<?php
global $wpdb;

// === TABLE NAMES (co prefix) ===
// WordPress tu dong them prefix (mac dinh: wp_)

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
$wpdb->prefix;          // 'wp_' (hoac prefix tuy chinh)
// Dung khi tao custom table
$my_table = $wpdb->prefix . 'my_contacts';  // wp_my_contacts

// === CHARSET ===
$wpdb->get_charset_collate();  // 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'

// === THONG TIN KET QUA ===
$wpdb->num_rows;         // So dong tra ve tu query SELECT cuoi
$wpdb->rows_affected;    // So dong bi anh huong tu query INSERT/UPDATE/DELETE cuoi
$wpdb->insert_id;        // Auto-increment ID cua dong vua INSERT
$wpdb->last_query;       // Cau query cuoi cung da chay
$wpdb->last_error;       // Thong bao loi cuoi cung (rong neu khong co loi)
$wpdb->last_result;      // Ket qua tho cua query cuoi
```

---

## 2. Tao Custom Table khi Activate Plugin

```php
<?php
/**
 * Plugin Name: Database Demo
 * Description: Demo tao va quan ly custom table.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DBD_VERSION', '1.0.0' );
define( 'DBD_DB_VERSION', '1.0.0' ); // Version rieng cho database schema

/**
 * Tao custom table khi activate plugin.
 *
 * NGUYEN TAC QUAN TRONG:
 * - Dung $wpdb->prefix de ho tro Multisite
 * - Dung dbDelta() de tao/cap nhat table an toan
 * - Luu db_version de biet khi nao can upgrade
 * - Dung charset_collate de ho tro Unicode
 */
register_activation_hook( __FILE__, 'dbd_create_tables' );

function dbd_create_tables() {
    global $wpdb;

    // Ten table voi prefix
    $table_contacts = $wpdb->prefix . 'dbd_contacts';
    $table_notes    = $wpdb->prefix . 'dbd_contact_notes';

    // Charset va Collation (ho tro Unicode/tieng Viet)
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

    // Load file chua ham dbDelta
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Chay dbDelta de tao/cap nhat tables
    dbDelta( $sql_contacts );
    dbDelta( $sql_notes );

    // Luu version database de biet khi nao can upgrade
    update_option( 'dbd_db_version', DBD_DB_VERSION );
}

/**
 * Kiem tra va upgrade database khi can.
 * Chay o plugins_loaded de bat upgrade khi update plugin.
 */
add_action( 'plugins_loaded', 'dbd_check_db_upgrade' );

function dbd_check_db_upgrade() {
    $installed_version = get_option( 'dbd_db_version', '0' );

    // Neu version trong database khac version hien tai => upgrade
    if ( version_compare( $installed_version, DBD_DB_VERSION, '<' ) ) {
        dbd_create_tables(); // dbDelta se chi cap nhat, khong tao lai
    }
}
```

---

## 3. dbDelta() Function

### dbDelta la gi?

`dbDelta()` la ham dac biet cua WordPress de tao hoac cap nhat schema database. No **thong minh hon** `CREATE TABLE` vi:

- Neu table chua ton tai: **Tao moi**
- Neu table da ton tai nhung thieu column: **Them column**
- Neu table da ton tai nhung thieu index: **Them index**
- **KHONG** xoa column hoac table cu

### Quy tac SQL cho dbDelta (RAT QUAN TRONG)

```php
<?php
// dbDelta rat KHUNG KHO ve dinh dang SQL. Phai tuan thu chinh xac:

// 1. Moi truong tren 1 dong rieng
// 2. Co CHINH XAC 2 dau cach truoc PRIMARY KEY
// 3. Phai co PRIMARY KEY
// 4. Dung KEY thay vi INDEX
// 5. Khong co dau phay sau truong cuoi cung (truoc PRIMARY KEY)
// 6. Ten truong khong dung dau backtick `

// SAI - se bi loi:
$sql_wrong = "CREATE TABLE {$table} (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(100),
PRIMARY KEY (id)
)";
// Loi: dung backtick, PRIMARY KEY chi co 1 dau cach, thieu charset

// DUNG:
$sql_correct = "CREATE TABLE {$table} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL DEFAULT '',
    PRIMARY KEY  (id)
) $charset_collate;";
// Chu y: "PRIMARY KEY  (id)" co 2 dau cach giua KEY va (id)

// THEM COLUMN MOI (upgrade):
// Chi can them dong moi vao SQL roi goi dbDelta lai
// dbDelta se tu dong phat hien va ALTER TABLE ADD COLUMN
$sql_v2 = "CREATE TABLE {$table} (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL DEFAULT '',
    new_column varchar(50) NOT NULL DEFAULT '',
    PRIMARY KEY  (id)
) $charset_collate;";
// dbDelta se chi ALTER TABLE ADD new_column, khong tao lai table
```

---

## 4. CRUD voi $wpdb

### 4.1. CREATE - Them du lieu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->insert() - Them 1 dong moi
 *
 * @param string $table  Ten table
 * @param array  $data   Mang key => value
 * @param array  $format Dinh dang tung truong (%s = string, %d = integer, %f = float)
 *
 * @return int|false  So dong da them (1) hoac false neu loi
 */
$result = $wpdb->insert(
    $table,                              // Ten table
    array(                               // Du lieu
        'first_name' => 'Nguyen',
        'last_name'  => 'Van A',
        'email'      => 'nguyenvana@example.com',
        'phone'      => '0901234567',
        'company'    => 'Cong ty ABC',
        'status'     => 'active',
        'notes'      => 'Khach hang VIP',
        'created_by' => get_current_user_id(),
    ),
    array(                               // Format tuong ung
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
    // Lay ID cua dong vua them
    $new_id = $wpdb->insert_id;
    echo "Da them contact ID: {$new_id}";
} else {
    echo "Loi: " . $wpdb->last_error;
}
```

### 4.2. READ - Doc du lieu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->get_results() - Lay nhieu dong
 *
 * @param string $query   Cau SQL
 * @param string $output  Kieu ket qua:
 *                        OBJECT  (mac dinh) - Mang cac object
 *                        OBJECT_K - Object voi key la cot dau tien
 *                        ARRAY_A  - Mang cac associative array
 *                        ARRAY_N  - Mang cac numeric array
 *
 * @return array  Mang ket qua
 */

// Lay tat ca contacts
$contacts = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
// $contacts = array cua objects
foreach ( $contacts as $contact ) {
    echo $contact->first_name . ' ' . $contact->last_name;
    echo ' - ' . $contact->email;
}

// Lay ket qua dang array
$contacts_array = $wpdb->get_results(
    "SELECT * FROM {$table} ORDER BY created_at DESC",
    ARRAY_A    // Tra ve associative array
);
// $contacts_array[0]['first_name'], $contacts_array[0]['email']

/**
 * $wpdb->get_row() - Lay 1 dong duy nhat
 *
 * @param string $query   Cau SQL
 * @param string $output  OBJECT, ARRAY_A, ARRAY_N
 * @param int    $offset  Dong thu may (0-based)
 */
$contact = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", 5 )
);
// $contact->first_name, $contact->email

/**
 * $wpdb->get_var() - Lay 1 gia tri duy nhat (1 cell)
 *
 * @param string $query  Cau SQL
 * @param int    $col    Cot thu may (0-based)
 * @param int    $row    Dong thu may (0-based)
 */

// Dem so contacts
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
echo "Tong: {$count} contacts";

// Lay email cua contact ID = 5
$email = $wpdb->get_var(
    $wpdb->prepare( "SELECT email FROM {$table} WHERE id = %d", 5 )
);

/**
 * $wpdb->get_col() - Lay 1 cot (mang gia tri)
 *
 * @param string $query  Cau SQL
 * @param int    $col    Cot thu may (0-based)
 */
$all_emails = $wpdb->get_col( "SELECT email FROM {$table} WHERE status = 'active'" );
// $all_emails = array( 'email1@...', 'email2@...', ... )
```

### 4.3. UPDATE - Cap nhat du lieu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->update() - Cap nhat du lieu
 *
 * @param string $table        Ten table
 * @param array  $data         Du lieu can cap nhat (key => value)
 * @param array  $where        Dieu kien WHERE (key => value)
 * @param array  $format       Format cua $data
 * @param array  $where_format Format cua $where
 *
 * @return int|false  So dong da cap nhat hoac false neu loi
 */
$result = $wpdb->update(
    $table,
    // SET (du lieu cap nhat)
    array(
        'first_name' => 'Tran',
        'last_name'  => 'Van B',
        'status'     => 'active',
    ),
    // WHERE (dieu kien)
    array(
        'id' => 5,
    ),
    // Format cua SET
    array( '%s', '%s', '%s' ),
    // Format cua WHERE
    array( '%d' )
);

if ( $result !== false ) {
    echo "Da cap nhat {$result} dong.";
    // $result = 0 neu khong co gi thay doi (du lieu giong cu)
    // $result = false neu co loi SQL
}

// Update nhieu dong cung luc (khong dung $wpdb->update)
$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$table} SET status = %s WHERE status = %s AND created_at < %s",
        'inactive',
        'lead',
        '2024-01-01 00:00:00'
    )
);
echo "Da cap nhat {$wpdb->rows_affected} dong.";
```

### 4.4. DELETE - Xoa du lieu

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

/**
 * $wpdb->delete() - Xoa du lieu
 *
 * @param string $table        Ten table
 * @param array  $where        Dieu kien WHERE
 * @param array  $where_format Format cua WHERE
 *
 * @return int|false  So dong da xoa hoac false neu loi
 */
$result = $wpdb->delete(
    $table,
    array( 'id' => 5 ),           // WHERE id = 5
    array( '%d' )                  // id la integer
);

if ( $result !== false ) {
    echo "Da xoa {$result} dong.";
}

// Xoa nhieu dong
$wpdb->delete(
    $table,
    array( 'status' => 'inactive' ),
    array( '%s' )
);

// Xoa voi dieu kien phuc tap (dung query)
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$table} WHERE status = %s AND created_at < %s",
        'inactive',
        date( 'Y-m-d', strtotime( '-1 year' ) )
    )
);
```

### 4.5. Query phuc tap

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

// === TIM KIEM ===
$search = 'Nguyen';
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table}
         WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s
         ORDER BY first_name ASC",
        '%' . $wpdb->esc_like( $search ) . '%',  // esc_like: escape ky tu dac biet LIKE
        '%' . $wpdb->esc_like( $search ) . '%',
        '%' . $wpdb->esc_like( $search ) . '%'
    )
);

// === PHAN TRANG ===
$per_page = 10;
$current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
$offset = ( $current_page - 1 ) * $per_page;

// Dem tong
$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

// Lay trang hien tai
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

### Tai sao phai dung Prepared Statements?

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'dbd_contacts';

// === NGUY HIEM: SQL Injection ===
// KHONG BAO GIO lam the nay!
$id = $_GET['id']; // Nguoi dung co the truyen: "1 OR 1=1"
$result = $wpdb->get_row( "SELECT * FROM {$table} WHERE id = {$id}" );
// Query thuc te: SELECT * FROM wp_dbd_contacts WHERE id = 1 OR 1=1
// => Lay TOAN BO du lieu!

// === AN TOAN: Dung $wpdb->prepare() ===
$id = intval( $_GET['id'] ); // Ep kieu truoc
$result = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
);
// Query thuc te: SELECT * FROM wp_dbd_contacts WHERE id = 1
```

### Cu phap $wpdb->prepare()

```php
<?php
global $wpdb;

/**
 * $wpdb->prepare() - Tao prepared statement an toan
 *
 * Placeholders:
 *   %d  = integer (so nguyen)
 *   %f  = float (so thuc)
 *   %s  = string (chuoi - tu dong escape quotes)
 *
 * Tra ve string SQL da duoc escape an toan
 */

// 1 placeholder
$sql = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
    42
);
// Ket qua: "SELECT * FROM wp_posts WHERE ID = 42"

// Nhieu placeholders
$sql = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s LIMIT %d",
    'post',        // %s dau tien
    'publish',     // %s thu hai
    10             // %d
);

// LIKE query (phai dung esc_like)
$search = "O'Brien"; // Co dau ' (nguy hiem)
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE last_name LIKE %s",
    '%' . $wpdb->esc_like( $search ) . '%'
);
// WordPress se tu dong escape: "... WHERE last_name LIKE '%O\'Brien%'"

// IN clause (mang gia tri)
$statuses = array( 'active', 'lead' );
$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE status IN ({$placeholders})",
    ...$statuses    // Spread operator (PHP 5.6+)
);

// Query phuc tap
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

Options API luu tru cac cap **key-value** trong bang `wp_options`. Phu hop cho cai dat (settings), config.

```php
<?php
/**
 * Options API - Luu tru cai dat cua plugin
 * Tuong tu: config() hoac .env trong Laravel
 */

// === GET OPTION ===
/**
 * get_option() - Lay gia tri option
 *
 * @param string $option   Ten option
 * @param mixed  $default  Gia tri mac dinh neu option chua ton tai
 * @return mixed           Gia tri cua option
 */
$value = get_option( 'my_plugin_setting', 'default_value' );

// Lay option la array
$settings = get_option( 'my_plugin_settings', array() );
$per_page = $settings['per_page'] ?? 10;

// === ADD OPTION ===
/**
 * add_option() - Them option MOI (chi them neu CHUA ton tai)
 *
 * @param string $option     Ten option
 * @param mixed  $value      Gia tri
 * @param string $deprecated Bo qua (compat)
 * @param bool   $autoload   Tu dong load moi request (yes/no)
 */
add_option( 'my_plugin_version', '1.0.0' );

// Khong autoload (cho du lieu lon, it dung)
add_option( 'my_plugin_large_data', $large_array, '', 'no' );

// === UPDATE OPTION ===
/**
 * update_option() - Cap nhat option (tao moi neu chua ton tai)
 *
 * @param string $option   Ten option
 * @param mixed  $value    Gia tri moi
 * @param bool   $autoload Autoload (tu WP 4.2)
 */
update_option( 'my_plugin_setting', 'new_value' );

// Update array
$settings = get_option( 'my_plugin_settings', array() );
$settings['per_page'] = 20;
update_option( 'my_plugin_settings', $settings );

// === DELETE OPTION ===
/**
 * delete_option() - Xoa option
 */
delete_option( 'my_plugin_setting' );

// === OPTION CO THE LUU NHIEU KIEU DU LIEU ===
// String
update_option( 'my_string', 'Hello World' );

// Integer
update_option( 'my_number', 42 );

// Boolean
update_option( 'my_bool', true );

// Array (tu dong serialize/unserialize)
update_option( 'my_array', array(
    'key1' => 'value1',
    'key2' => array( 'nested' => true ),
));

// Object (tu dong serialize)
$obj = new stdClass();
$obj->name = 'Test';
update_option( 'my_object', $obj );

// LUU Y: WordPress tu dong serialize mang va object khi luu,
// va tu dong unserialize khi doc. Ban khong can tu lam.
```

### Khi nao dung Options API vs Custom Table?

```
Options API phu hop khi:
- Du lieu la cai dat, config (it thay doi)
- Du lieu nho, it ban ghi (< 100)
- Khong can tim kiem, sap xep phuc tap
- Chia se giua cac phan cua plugin

Custom Table phu hop khi:
- Du lieu nhieu ban ghi (> 100)
- Can tim kiem, loc, sap xep
- Can JOIN voi cac bang khac
- Du lieu co cau truc co dinh (schema)
- Can index de toi uu performance
```

---

## 7. Post Meta API

Post Meta luu tru **du lieu bo sung** cho moi post/page. Luu trong bang `wp_postmeta`.

```php
<?php
/**
 * Post Meta API - Luu du lieu cho tung bai viet
 * Tuong tu: JSON column hoac pivot table trong Laravel
 */

$post_id = 123;

// === ADD POST META ===
/**
 * add_post_meta() - Them meta cho post
 *
 * @param int    $post_id   ID bai viet
 * @param string $meta_key  Ten meta
 * @param mixed  $value     Gia tri
 * @param bool   $unique    true = chi 1 gia tri, false = nhieu gia tri cung key
 */
add_post_meta( $post_id, '_my_plugin_price', 150000, true );
// _ (underscore) o dau key = hidden (khong hien trong Custom Fields UI)

// Cho phep nhieu gia tri cung key
add_post_meta( $post_id, '_my_plugin_gallery', 'image1.jpg', false );
add_post_meta( $post_id, '_my_plugin_gallery', 'image2.jpg', false );

// === GET POST META ===
/**
 * get_post_meta() - Lay meta cua post
 *
 * @param int    $post_id   ID bai viet
 * @param string $meta_key  Ten meta ('' = lay tat ca)
 * @param bool   $single    true = tra ve gia tri, false = tra ve array
 */

// Lay 1 gia tri (single = true)
$price = get_post_meta( $post_id, '_my_plugin_price', true );
// $price = 150000

// Lay nhieu gia tri (single = false)
$gallery = get_post_meta( $post_id, '_my_plugin_gallery', false );
// $gallery = array( 'image1.jpg', 'image2.jpg' )

// Lay TAT CA meta cua post
$all_meta = get_post_meta( $post_id );
// $all_meta = array( '_my_plugin_price' => array('150000'), ... )

// === UPDATE POST META ===
/**
 * update_post_meta() - Cap nhat meta (tao moi neu chua co)
 *
 * @param int    $post_id    ID bai viet
 * @param string $meta_key   Ten meta
 * @param mixed  $value      Gia tri moi
 * @param mixed  $prev_value Gia tri cu (de cap nhat chinh xac khi co nhieu gia tri)
 */
update_post_meta( $post_id, '_my_plugin_price', 200000 );

// Luu array (tu dong serialize)
update_post_meta( $post_id, '_my_plugin_settings', array(
    'color'    => 'red',
    'size'     => 'large',
    'featured' => true,
));

// === DELETE POST META ===
/**
 * delete_post_meta() - Xoa meta
 *
 * @param int    $post_id    ID bai viet
 * @param string $meta_key   Ten meta
 * @param mixed  $meta_value Gia tri cu the (neu chi muon xoa 1 gia tri trong nhieu gia tri)
 */
delete_post_meta( $post_id, '_my_plugin_price' );

// Xoa 1 gia tri cu the trong nhieu gia tri
delete_post_meta( $post_id, '_my_plugin_gallery', 'image1.jpg' );

// === QUERY THEO META ===
$expensive_products = new WP_Query( array(
    'post_type'  => 'product',
    'meta_query' => array(
        'relation' => 'AND',   // AND hoac OR
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
 * User Meta API - Luu du lieu cho tung nguoi dung
 * Cu phap giong Post Meta nhung cho users
 */

$user_id = get_current_user_id();

// Them
add_user_meta( $user_id, 'my_plugin_preferences', array(
    'theme'        => 'dark',
    'notification' => true,
    'language'     => 'vi',
), true );

// Lay
$prefs = get_user_meta( $user_id, 'my_plugin_preferences', true );
$theme = $prefs['theme'] ?? 'light';

// Cap nhat
$prefs['theme'] = 'light';
update_user_meta( $user_id, 'my_plugin_preferences', $prefs );

// Xoa
delete_user_meta( $user_id, 'my_plugin_preferences' );

// Query users theo meta
$dark_theme_users = get_users( array(
    'meta_key'   => 'my_plugin_preferences',
    'meta_value' => 'dark',
    'meta_compare' => 'LIKE',    // Tim trong serialized data
));

// Them truong vao trang Profile
add_action( 'show_user_profile', 'my_plugin_user_fields' );
add_action( 'edit_user_profile', 'my_plugin_user_fields' );

function my_plugin_user_fields( $user ) {
    $phone = get_user_meta( $user->ID, 'my_plugin_phone', true );
    ?>
    <h3>Thong tin bo sung</h3>
    <table class="form-table">
        <tr>
            <th><label for="my_plugin_phone">So dien thoai</label></th>
            <td>
                <input type="tel" name="my_plugin_phone" id="my_plugin_phone"
                       value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    <?php
}

// Luu truong khi update profile
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

Transients la **cache tam thoi** luu trong database (hoac object cache neu co). Tu dong het han.

```php
<?php
/**
 * Transients API - Cache tam thoi
 * Tuong tu: Cache::remember() trong Laravel
 */

// === SET TRANSIENT ===
/**
 * set_transient() - Luu du lieu tam thoi
 *
 * @param string $transient  Ten transient
 * @param mixed  $value      Gia tri
 * @param int    $expiration Thoi gian het han (giay), 0 = khong het han
 */

// Cache ket qua API trong 1 gio
$api_data = wp_remote_get( 'https://api.example.com/data' );
if ( ! is_wp_error( $api_data ) ) {
    $data = json_decode( wp_remote_retrieve_body( $api_data ), true );
    set_transient( 'my_plugin_api_data', $data, HOUR_IN_SECONDS );
}

// Cac hang thoi gian co san:
// MINUTE_IN_SECONDS  = 60
// HOUR_IN_SECONDS    = 3600
// DAY_IN_SECONDS     = 86400
// WEEK_IN_SECONDS    = 604800
// MONTH_IN_SECONDS   = 2592000 (30 ngay)
// YEAR_IN_SECONDS    = 31536000

// === GET TRANSIENT ===
/**
 * get_transient() - Lay du lieu tu cache
 *
 * @return mixed  Du lieu hoac false neu het han/khong ton tai
 */

// Pattern thuong dung: Check cache truoc, query neu khong co
$data = get_transient( 'my_plugin_api_data' );

if ( false === $data ) {
    // Cache het han hoac chua co => goi API
    $response = wp_remote_get( 'https://api.example.com/data' );
    if ( ! is_wp_error( $response ) ) {
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        // Luu cache 1 gio
        set_transient( 'my_plugin_api_data', $data, HOUR_IN_SECONDS );
    }
}

// Su dung $data...

// === DELETE TRANSIENT ===
/**
 * delete_transient() - Xoa cache
 * Dung khi du lieu thay doi va can cap nhat cache
 */
delete_transient( 'my_plugin_api_data' );

// === VI DU THUC TE: Cache danh sach bai viet pho bien ===
function my_plugin_get_popular_posts( $count = 5 ) {
    $cache_key = 'my_plugin_popular_posts_' . $count;
    $posts = get_transient( $cache_key );

    if ( false === $posts ) {
        // Query nang - chi chay khi cache het han
        $posts = get_posts( array(
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'meta_key'       => 'post_views_count',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ));

        // Cache 30 phut
        set_transient( $cache_key, $posts, 30 * MINUTE_IN_SECONDS );
    }

    return $posts;
}

// Xoa cache khi co bai viet moi
add_action( 'save_post', function( $post_id ) {
    // Xoa tat ca transients lien quan
    delete_transient( 'my_plugin_popular_posts_5' );
    delete_transient( 'my_plugin_popular_posts_10' );
});
```

### So sanh cac cach luu du lieu

```
+-------------------+----------------+----------------+------------------+
| Phuong phap       | Pham vi        | Het han?       | Use case         |
+-------------------+----------------+----------------+------------------+
| Options API       | Toan site      | Khong          | Settings, config |
| Post Meta         | 1 post         | Khong          | Data cua post    |
| User Meta         | 1 user         | Khong          | Data cua user    |
| Transients        | Toan site      | Co             | Cache, temp data |
| Custom Table      | Tuy chinh      | Khong          | Du lieu phuc tap  |
+-------------------+----------------+----------------+------------------+
```

---

## 10. Code vi du: Plugin quan ly Contacts

```php
<?php
/**
 * Plugin Name:       Contacts Manager
 * Description:       Plugin quan ly danh sach lien he voi CRUD hoan chinh.
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

    // === ACTIVATION: Tao table ===
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

        // Them du lieu mau
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        if ( intval( $count ) === 0 ) {
            $samples = array(
                array( 'Nguyen', 'Van A', 'nguyenvana@email.com', '0901234567', 'Cong ty ABC', 'Ha Noi', 'active' ),
                array( 'Tran', 'Thi B', 'tranthib@email.com', '0912345678', 'Cong ty XYZ', 'TP HCM', 'active' ),
                array( 'Le', 'Van C', 'levanc@email.com', '0923456789', 'Cong ty DEF', 'Da Nang', 'lead' ),
                array( 'Pham', 'Thi D', 'phamthid@email.com', '0934567890', 'Cong ty GHI', 'Hai Phong', 'inactive' ),
                array( 'Hoang', 'Van E', 'hoangvane@email.com', '0945678901', 'Cong ty JKL', 'Can Tho', 'lead' ),
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
        // Khong xoa data
    }

    // === ADD MENU ===
    public function add_menu() {
        add_menu_page(
            'Quan ly Lien he',
            'Contacts',
            'manage_options',
            'cm-contacts',
            array( $this, 'render_page' ),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'cm-contacts',
            'Tat ca Lien he',
            'Tat ca',
            'manage_options',
            'cm-contacts',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'cm-contacts',
            'Them Lien he moi',
            'Them moi',
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

    // === XU LY FORM ACTIONS ===
    public function handle_form_actions() {
        global $wpdb;

        // --- CREATE ---
        if ( isset( $_POST['cm_action'] ) && $_POST['cm_action'] === 'create' ) {
            check_admin_referer( 'cm_create_contact' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Khong co quyen.' );
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
                set_transient( 'cm_notice', array( 'type' => 'success', 'message' => 'Da them lien he thanh cong!' ), 30 );
                wp_redirect( admin_url( 'admin.php?page=cm-contacts' ) );
            } else {
                set_transient( 'cm_notice', array( 'type' => 'error', 'message' => 'Loi: ' . $wpdb->last_error ), 30 );
                wp_redirect( admin_url( 'admin.php?page=cm-contacts-add' ) );
            }
            exit;
        }

        // --- UPDATE ---
        if ( isset( $_POST['cm_action'] ) && $_POST['cm_action'] === 'update' ) {
            check_admin_referer( 'cm_update_contact' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Khong co quyen.' );
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

            set_transient( 'cm_notice', array( 'type' => 'success', 'message' => 'Da cap nhat thanh cong!' ), 30 );
            wp_redirect( admin_url( 'admin.php?page=cm-contacts' ) );
            exit;
        }

        // --- DELETE ---
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            check_admin_referer( 'cm_delete_contact' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Khong co quyen.' );
            }

            $id = absint( $_GET['id'] );
            $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );

            set_transient( 'cm_notice', array( 'type' => 'success', 'message' => 'Da xoa lien he.' ), 30 );
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
        if ( empty( $data['first_name'] ) ) $errors[] = 'Ho ten khong duoc de trong.';
        if ( empty( $data['email'] ) ) $errors[] = 'Email khong duoc de trong.';
        if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) $errors[] = 'Email khong hop le.';
        return $errors;
    }

    // === TRANG DANH SACH ===
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Kiem tra action
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

        // Hien thi thong bao
        $notice = get_transient( 'cm_notice' );
        if ( $notice ) delete_transient( 'cm_notice' );

        // Tim kiem
        $search = sanitize_text_field( $_GET['s'] ?? '' );
        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );

        // Dem tong
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

        // Tong so ban ghi
        $total_query = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        if ( ! empty( $params ) ) {
            $total = $wpdb->get_var( $wpdb->prepare( $total_query, ...$params ) );
        } else {
            $total = $wpdb->get_var( $total_query );
        }

        // Phan trang
        $per_page = 10;
        $current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $offset = ( $current_page - 1 ) * $per_page;
        $total_pages = ceil( $total / $per_page );

        // Lay du lieu
        $order = sanitize_sql_orderby( $_GET['orderby'] ?? 'created_at' ) ?: 'created_at';
        $order_dir = strtoupper( $_GET['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

        $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY {$order} {$order_dir} LIMIT %d OFFSET %d";
        $all_params = array_merge( $params, array( $per_page, $offset ) );
        $contacts = $wpdb->get_results( $wpdb->prepare( $query, ...$all_params ) );

        // Thong ke
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
            <h1 class="wp-heading-inline">Quan ly Lien he</h1>
            <a href="<?php echo admin_url( 'admin.php?page=cm-contacts-add' ); ?>" class="page-title-action">Them moi</a>
            <hr class="wp-header-end">

            <?php if ( $notice ) : ?>
                <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
                    <p><?php echo esc_html( $notice['message'] ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Thong ke -->
            <div class="cm-stats">
                <div class="cm-stat-card">
                    <div class="cm-stat-number"><?php echo intval( $stats->total ); ?></div>
                    <div class="cm-stat-label">Tong cong</div>
                </div>
                <div class="cm-stat-card">
                    <div class="cm-stat-number" style="color:#46b450;"><?php echo intval( $stats->active_count ); ?></div>
                    <div class="cm-stat-label">Dang hoat dong</div>
                </div>
                <div class="cm-stat-card">
                    <div class="cm-stat-number" style="color:#856404;"><?php echo intval( $stats->lead_count ); ?></div>
                    <div class="cm-stat-label">Tiem nang</div>
                </div>
                <div class="cm-stat-card">
                    <div class="cm-stat-number" style="color:#dc3232;"><?php echo intval( $stats->inactive_count ); ?></div>
                    <div class="cm-stat-label">Ngung hoat dong</div>
                </div>
            </div>

            <!-- Loc va Tim kiem -->
            <div class="cm-search-box">
                <form method="get" style="display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="page" value="cm-contacts">

                    <select name="status">
                        <option value="">-- Tat ca trang thai --</option>
                        <option value="active" <?php selected( $status_filter, 'active' ); ?>>Active</option>
                        <option value="inactive" <?php selected( $status_filter, 'inactive' ); ?>>Inactive</option>
                        <option value="lead" <?php selected( $status_filter, 'lead' ); ?>>Lead</option>
                    </select>

                    <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>"
                           placeholder="Tim kiem ten, email, cong ty..."
                           style="width:300px;">

                    <button type="submit" class="button">Tim kiem</button>

                    <?php if ( ! empty( $search ) || ! empty( $status_filter ) ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=cm-contacts' ); ?>" class="button">Xoa bo loc</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bang du lieu -->
            <table class="cm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ho ten</th>
                        <th>Email</th>
                        <th>Dien thoai</th>
                        <th>Cong ty</th>
                        <th>Trang thai</th>
                        <th>Ngay tao</th>
                        <th>Hanh dong</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $contacts ) ) : ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Khong co du lieu.</td></tr>
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
                                       style="color:#0073aa;">Sua</a>
                                    <a href="<?php echo esc_url( wp_nonce_url(
                                        admin_url( 'admin.php?page=cm-contacts&action=delete&id=' . $contact->id ),
                                        'cm_delete_contact'
                                    ) ); ?>"
                                       style="color:#dc3232;"
                                       onclick="return confirm('Ban co chac muon xoa lien he nay?');">Xoa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Phan trang -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="cm-pagination">
                    <?php
                    $page_links = paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'prev_text' => '&laquo; Truoc',
                        'next_text' => 'Sau &raquo;',
                    ));
                    echo $page_links;
                    ?>
                    <span style="margin-left:15px; color:#666;">
                        Trang <?php echo $current_page; ?>/<?php echo $total_pages; ?>
                        (<?php echo $total; ?> ket qua)
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // === TRANG THEM MOI ===
    public function render_add_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $errors = get_transient( 'cm_form_errors' );
        $data = get_transient( 'cm_form_data' );
        if ( $errors ) delete_transient( 'cm_form_errors' );
        if ( $data ) delete_transient( 'cm_form_data' );

        $data = $data ?: array();
        $this->render_form( 'create', $data, $errors );
    }

    // === TRANG CHINH SUA ===
    private function render_edit_page() {
        global $wpdb;

        $id = absint( $_GET['id'] ?? 0 );
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( ! $contact ) {
            echo '<div class="wrap"><h1>Khong tim thay lien he.</h1></div>';
            return;
        }

        $errors = get_transient( 'cm_form_errors' );
        if ( $errors ) delete_transient( 'cm_form_errors' );

        $this->render_form( 'update', $contact, $errors );
    }

    // === TRANG XEM CHI TIET ===
    private function render_view_page() {
        global $wpdb;

        $id = absint( $_GET['id'] ?? 0 );
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $contact ) {
            echo '<div class="wrap"><h1>Khong tim thay lien he.</h1></div>';
            return;
        }

        $creator = get_userdata( $contact->created_by );
        ?>
        <div class="wrap">
            <h1>
                Chi tiet lien he
                <a href="<?php echo admin_url( 'admin.php?page=cm-contacts&action=edit&id=' . $id ); ?>" class="page-title-action">Sua</a>
                <a href="<?php echo admin_url( 'admin.php?page=cm-contacts' ); ?>" class="page-title-action">Quay lai</a>
            </h1>
            <div style="background:#fff; padding:25px; border:1px solid #ddd; border-radius:5px; max-width:600px; margin-top:15px;">
                <table class="form-table">
                    <tr><th>Ho:</th><td><?php echo esc_html( $contact->first_name ); ?></td></tr>
                    <tr><th>Ten:</th><td><?php echo esc_html( $contact->last_name ); ?></td></tr>
                    <tr><th>Email:</th><td><a href="mailto:<?php echo esc_attr( $contact->email ); ?>"><?php echo esc_html( $contact->email ); ?></a></td></tr>
                    <tr><th>Dien thoai:</th><td><?php echo esc_html( $contact->phone ); ?></td></tr>
                    <tr><th>Cong ty:</th><td><?php echo esc_html( $contact->company ); ?></td></tr>
                    <tr><th>Dia chi:</th><td><?php echo nl2br( esc_html( $contact->address ) ); ?></td></tr>
                    <tr><th>Trang thai:</th><td><span class="cm-status cm-status-<?php echo esc_attr( $contact->status ); ?>"><?php echo esc_html( $contact->status ); ?></span></td></tr>
                    <tr><th>Nguoi tao:</th><td><?php echo esc_html( $creator ? $creator->display_name : 'N/A' ); ?></td></tr>
                    <tr><th>Ngay tao:</th><td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $contact->created_at ) ) ); ?></td></tr>
                    <tr><th>Cap nhat:</th><td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $contact->updated_at ) ) ); ?></td></tr>
                </table>
            </div>
        </div>
        <?php
    }

    // === RENDER FORM (dung chung cho Add va Edit) ===
    private function render_form( $action, $data, $errors = null ) {
        $is_edit = ( $action === 'update' );
        $title = $is_edit ? 'Chinh sua Lien he' : 'Them Lien he moi';
        $nonce_action = $is_edit ? 'cm_update_contact' : 'cm_create_contact';
        ?>
        <div class="wrap cm-form">
            <h1>
                <?php echo esc_html( $title ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=cm-contacts' ); ?>" class="page-title-action">Quay lai</a>
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

                <!-- Form gui truc tiep ve admin.php (xu ly trong admin_init) -->
                <input type="hidden" name="action" value="cm_form">

                <table class="form-table">
                    <tr>
                        <th><label for="first_name">Ho <span style="color:red;">*</span></label></th>
                        <td><input type="text" name="first_name" id="first_name" class="regular-text"
                                   value="<?php echo esc_attr( $data['first_name'] ?? '' ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Ten</label></th>
                        <td><input type="text" name="last_name" id="last_name" class="regular-text"
                                   value="<?php echo esc_attr( $data['last_name'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email <span style="color:red;">*</span></label></th>
                        <td><input type="email" name="email" id="email" class="regular-text"
                                   value="<?php echo esc_attr( $data['email'] ?? '' ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Dien thoai</label></th>
                        <td><input type="tel" name="phone" id="phone" class="regular-text"
                                   value="<?php echo esc_attr( $data['phone'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="company">Cong ty</label></th>
                        <td><input type="text" name="company" id="company" class="regular-text"
                                   value="<?php echo esc_attr( $data['company'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="address">Dia chi</label></th>
                        <td><textarea name="address" id="address" rows="3" class="large-text"><?php
                            echo esc_textarea( $data['address'] ?? '' );
                        ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="status">Trang thai</label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="lead" <?php selected( $data['status'] ?? '', 'lead' ); ?>>Lead (Tiem nang)</option>
                                <option value="active" <?php selected( $data['status'] ?? '', 'active' ); ?>>Active (Hoat dong)</option>
                                <option value="inactive" <?php selected( $data['status'] ?? '', 'inactive' ); ?>>Inactive (Ngung)</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button( $is_edit ? 'Cap nhat' : 'Them moi' ); ?>
            </form>
        </div>
        <?php
    }
}

// === HOOKS ===
register_activation_hook( __FILE__, array( 'Contacts_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Contacts_Manager', 'deactivate' ) );

// Khoi tao plugin
add_action( 'plugins_loaded', function() {
    Contacts_Manager::get_instance();
});
```

---

## 11. So sanh voi Eloquent ORM trong Laravel

```php
<?php
/**
 * LARAVEL: Eloquent ORM - Tuong tac database bang Model objects
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
 * WORDPRESS: $wpdb - Tuong tac database bang SQL queries
 */

// Tao table (trong activation hook)
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

### Bang so sanh

| Tinh nang | Laravel Eloquent | WordPress $wpdb |
|-----------|-----------------|-----------------|
| **Cach tiep can** | ORM (Object-Relational Mapping) | Query Builder / Raw SQL |
| **Tao table** | Migration files | dbDelta() |
| **Model** | Class extend Model | Khong co (tu viet) |
| **Query Builder** | `User::where()->get()` | `$wpdb->prepare()` + SQL |
| **Relationships** | `hasMany`, `belongsTo` | Tu viet JOIN |
| **Pagination** | `->paginate(10)` | Tu tinh LIMIT/OFFSET |
| **Validation** | Form Request | Tu viet |
| **Mass Assignment** | `$fillable`, `$guarded` | Khong co |
| **Soft Delete** | `SoftDeletes` trait | Tu them column |
| **Events** | Model Events | Hooks (actions/filters) |
| **Cache** | `Cache::remember()` | `get_transient()` |
| **Tinker** | `php artisan tinker` | WP-CLI |

---

## 12. Best Practices

### 1. Luon dung $wpdb->prepare()

```php
<?php
// SAI
$wpdb->query( "DELETE FROM {$table} WHERE id = {$_GET['id']}" );

// DUNG
$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id = %d", absint( $_GET['id'] ) ) );
```

### 2. Luon dung prefix

```php
<?php
// SAI - hardcode ten table
$wpdb->get_results( "SELECT * FROM wp_my_table" );

// DUNG - dung prefix
$wpdb->get_results( "SELECT * FROM {$wpdb->prefix}my_table" );
```

### 3. Kiem tra loi sau moi query

```php
<?php
$result = $wpdb->insert( $table, $data, $format );
if ( false === $result ) {
    error_log( 'DB Error: ' . $wpdb->last_error );
}
```

### 4. Dung dbDelta dung cach

```php
<?php
// Nho: 2 dau cach truoc PRIMARY KEY
// Khong dung backtick cho ten truong
// Moi truong tren 1 dong
```

### 5. Don dep khi uninstall

```php
<?php
// Trong uninstall.php
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_table" );
delete_option( 'my_plugin_db_version' );
```

### 6. Dung Transients cho du lieu nang

```php
<?php
$data = get_transient( 'expensive_query' );
if ( false === $data ) {
    $data = $wpdb->get_results( "SELECT ... phuc tap ..." );
    set_transient( 'expensive_query', $data, HOUR_IN_SECONDS );
}
```

---

## Tham khao

- [WordPress Database API ($wpdb)](https://developer.wordpress.org/reference/classes/wpdb/)
- [Creating Tables with Plugins](https://developer.wordpress.org/plugins/creating-tables-with-plugins/)
- [Options API](https://developer.wordpress.org/plugins/settings/options-api/)
- [Metadata API](https://developer.wordpress.org/plugins/metadata/)
- [Transients API](https://developer.wordpress.org/apis/transients/)
