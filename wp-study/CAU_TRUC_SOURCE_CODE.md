# Phan Tich Cau Truc Source Code WordPress

## Muc Luc

1. [Tong quan cau truc thu muc goc](#1-tong-quan-cau-truc-thu-muc-goc)
2. [Cac file goc quan trong](#2-cac-file-goc-quan-trong)
3. [Thu muc wp-admin](#3-thu-muc-wp-admin)
4. [Thu muc wp-includes](#4-thu-muc-wp-includes)
5. [Thu muc wp-content](#5-thu-muc-wp-content)
6. [Cac file cau hinh quan trong](#6-cac-file-cau-hinh-quan-trong)
7. [Cac Global Objects](#7-cac-global-objects)
8. [Design Patterns trong WordPress](#8-design-patterns-trong-wordpress)
9. [Cach doc source code hieu qua](#9-cach-doc-source-code-hieu-qua)

---

## 1. Tong Quan Cau Truc Thu Muc Goc

```
wordpress/
├── index.php                 # Entry point chinh
├── wp-activate.php           # Kich hoat user (multisite)
├── wp-blog-header.php        # Load WP environment + template
├── wp-comments-post.php      # Xu ly comment submission
├── wp-config.php             # Cau hinh (tao khi cai dat)
├── wp-config-sample.php      # Mau cau hinh
├── wp-cron.php               # WordPress cron system
├── wp-links-opml.php         # OPML export (it dung)
├── wp-load.php               # Bootstrap - load WP core
├── wp-login.php              # Trang dang nhap/dang ky
├── wp-mail.php               # Post qua email (it dung)
├── wp-settings.php           # Load TOAN BO WordPress core
├── wp-signup.php             # Dang ky (multisite)
├── wp-trackback.php          # Trackback handler (it dung)
├── xmlrpc.php                # XML-RPC API (legacy)
├── .htaccess                 # Apache rewrite rules
│
├── wp-admin/                 # Admin dashboard
├── wp-content/               # Noi dung: themes, plugins, uploads
└── wp-includes/              # Core library
```

---

## 2. Cac File Goc Quan Trong

### index.php - Entry Point

```php
// File nay rat don gian, chi lam 2 viec:
define( 'WP_USE_THEMES', true );
require __DIR__ . '/wp-blog-header.php';
```

Moi request den WordPress deu bat dau tu day (nho .htaccess redirect).

### wp-blog-header.php - Dieu phoi chinh

```php
if ( ! isset( $wp_did_header ) ) {
    $wp_did_header = true;
    require_once __DIR__ . '/wp-load.php';      // 1. Load WordPress
    wp();                                         // 2. Parse request
    require_once ABSPATH . WPINC . '/template-loader.php';  // 3. Load template
}
```

Day la file quan trong nhat - no dieu phoi 3 buoc chinh cua moi request.

### wp-load.php - Bootstrap

```php
// Dinh nghia ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

// Tim va load wp-config.php
if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
    require_once ABSPATH . 'wp-config.php';
}
```

Nhiem vu: Dinh nghia duong dan goc va tim file cau hinh.

### wp-config.php - Cau hinh

```php
// Thong tin database
define( 'DB_NAME',     'database_name' );
define( 'DB_USER',     'username' );
define( 'DB_PASSWORD', 'password' );
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8mb4' );

// Authentication Keys va Salts
define( 'AUTH_KEY',         'random-string' );
define( 'SECURE_AUTH_KEY',  'random-string' );
define( 'LOGGED_IN_KEY',    'random-string' );
define( 'NONCE_KEY',        'random-string' );
// ... va 4 SALT tuong ung

// Table prefix
$table_prefix = 'wp_';

// Debug mode
define( 'WP_DEBUG', false );

// Load wp-settings.php
require_once ABSPATH . 'wp-settings.php';
```

### wp-settings.php - Trung tam khoi dong

Day la file dai nhat va quan trong nhat. No load TOAN BO WordPress theo thu tu:

```
1. Dinh nghia hang so (WPINC = 'wp-includes')
2. Load utility functions (load.php, compat.php)
3. Kiem tra PHP/MySQL version
4. Bat debug mode
5. Load error handling
6. Ket noi database ($wpdb)
7. Khoi dong object cache
8. Load core libraries (hang tram file)
9. Load must-use plugins      → do_action('muplugins_loaded')
10. Load active plugins        → do_action('plugins_loaded')
11. Load theme functions.php   → do_action('after_setup_theme')
12. Khoi tao global objects ($wp_query, $wp_rewrite, $wp)
13. do_action('init')
14. do_action('wp_loaded')
```

### wp-cron.php - He thong Cron

```php
// WordPress cron KHONG phai la cron that su
// No chay khi co request den website
// Van hanh bang cach kiem tra cac scheduled events trong database
```

WordPress cron duoc kich hoat boi moi page load. Voi website it traffic, co the dung server cron goi `wp-cron.php` theo lich.

### wp-login.php - He thong dang nhap

Xu ly:
- Form dang nhap
- Dang ky (neu bat)
- Quen mat khau
- Reset mat khau
- Logout

### xmlrpc.php - XML-RPC API (Legacy)

API cu cho phep truy cap tu xa. Hien nay duoc thay the boi REST API. Nen disable neu khong dung de tang bao mat.

---

## 3. Thu Muc wp-admin

```
wp-admin/
├── admin.php                # Entry point cho moi trang admin
├── admin-ajax.php           # Xu ly AJAX requests
├── admin-header.php         # Header cua admin
├── admin-footer.php         # Footer cua admin
│
├── edit.php                 # Trang danh sach bai viet
├── post.php                 # Trang chinh sua bai viet
├── post-new.php             # Trang tao bai viet moi
├── revision.php             # Quan ly phien ban
│
├── edit-tags.php            # Quan ly categories/tags
├── term.php                 # Chinh sua term
│
├── upload.php               # Thu vien media
├── media-new.php            # Upload media moi
│
├── users.php                # Danh sach users
├── user-new.php             # Them user moi
├── user-edit.php            # Chinh sua user
├── profile.php              # Ho so ca nhan
│
├── plugins.php              # Quan ly plugins
├── plugin-install.php       # Cai dat plugin
├── plugin-editor.php        # Chinh sua plugin (khong khuyen dung)
│
├── themes.php               # Quan ly themes
├── theme-install.php        # Cai dat theme
├── theme-editor.php         # Chinh sua theme (khong khuyen dung)
├── customize.php            # Customizer
│
├── options.php              # Xu ly luu options
├── options-general.php      # Settings > General
├── options-writing.php      # Settings > Writing
├── options-reading.php      # Settings > Reading
├── options-discussion.php   # Settings > Discussion
├── options-media.php        # Settings > Media
├── options-permalink.php    # Settings > Permalinks
│
├── tools.php                # Cong cu
├── import.php               # Import noi dung
├── export.php               # Export noi dung
│
├── update-core.php          # Cap nhat WordPress
├── update.php               # Xu ly cap nhat
│
├── nav-menus.php            # Quan ly menu
├── widgets.php              # Quan ly widgets
├── comment.php              # Quan ly comment
│
├── includes/                # Admin utility classes va functions
│   ├── class-wp-list-table.php      # Base class cho bang danh sach
│   ├── class-wp-posts-list-table.php # Bang danh sach bai viet
│   ├── dashboard.php                 # Dashboard widgets
│   ├── upgrade.php                   # Database upgrade (dbDelta)
│   ├── file.php                      # File operations
│   ├── media.php                     # Media handling
│   ├── plugin.php                    # Plugin management
│   ├── template.php                  # Admin template functions
│   └── ...
│
├── css/                     # Admin stylesheets
├── js/                      # Admin JavaScript
└── images/                  # Admin images
```

### File quan trong nhat

**admin.php** - Entry point cho moi trang admin:
```php
// Moi URL admin deu di qua day
// VD: /wp-admin/edit.php → load admin.php truoc
// Kiem tra quyen, load admin environment, fire admin hooks
```

**admin-ajax.php** - Xu ly AJAX:
```php
// Nhan request voi action parameter
// Tim va goi callback tuong ung
// wp_ajax_{action} cho user da dang nhap
// wp_ajax_nopriv_{action} cho user chua dang nhap
```

---

## 4. Thu Muc wp-includes

Day la **core library** cua WordPress, chua hang tram file. Duoi day la cac nhom chinh:

```
wp-includes/
├── ===== CORE CLASSES =====
├── class-wp.php                    # WP main class - xu ly request
├── class-wp-query.php              # WP_Query - truy van bai viet
├── class-wp-post.php               # WP_Post - doi tuong bai viet
├── class-wp-user.php               # WP_User - doi tuong user
├── class-wp-term.php               # WP_Term - doi tuong term
├── class-wp-comment.php            # WP_Comment - doi tuong comment
├── class-wp-rewrite.php            # WP_Rewrite - URL rewriting
├── class-wp-hook.php               # WP_Hook - he thong hooks
├── class-wp-roles.php              # WP_Roles - he thong quyen
├── class-wp-error.php              # WP_Error - xu ly loi
├── class-wpdb.php                  # wpdb - database abstraction
├── class-wp-object-cache.php       # WP_Object_Cache - caching
│
├── ===== PLUGIN/HOOK SYSTEM =====
├── plugin.php                      # add_action, add_filter, do_action, apply_filters
├── default-filters.php             # Dang ky tat ca filters mac dinh cua WP
│
├── ===== DATABASE =====
├── wp-db.php                       # Load wpdb class
│
├── ===== TEMPLATE SYSTEM =====
├── template.php                    # get_header, get_footer, get_template_part
├── template-loader.php             # Chon template dua tren query
├── general-template.php            # wp_head, wp_footer, get_search_form
├── link-template.php               # the_permalink, home_url, admin_url
├── post-template.php               # the_title, the_content, the_excerpt
├── comment-template.php            # comment functions
├── author-template.php             # the_author, get_author_posts_url
├── category-template.php           # the_category, get_categories
│
├── ===== FORMATTING =====
├── formatting.php                  # Xu ly chuoi: wpautop, esc_html, sanitize_*
├── kses.php                        # HTML filtering (wp_kses)
├── shortcodes.php                  # Shortcode system
│
├── ===== USER/AUTH =====
├── user.php                        # User functions
├── capabilities.php                # current_user_can, user roles
├── pluggable.php                   # Cac ham co the override boi plugin
│
├── ===== TAXONOMY =====
├── taxonomy.php                    # register_taxonomy, get_terms
├── class-wp-tax-query.php          # WP_Tax_Query
│
├── ===== POST TYPES =====
├── post.php                        # register_post_type, wp_insert_post
├── class-wp-meta-query.php         # WP_Meta_Query
│
├── ===== REST API =====
├── rest-api.php                    # register_rest_route
├── class-wp-rest-server.php        # REST server
├── class-wp-rest-request.php       # REST request
├── class-wp-rest-response.php      # REST response
├── rest-api/                       # REST API endpoint classes
│
├── ===== HTTP =====
├── class-wp-http.php               # HTTP client (wp_remote_get/post)
├── http.php                        # HTTP utility functions
│
├── ===== CACHE =====
├── cache.php                       # wp_cache_* functions
├── class-wp-object-cache.php       # Object cache
│
├── ===== CRON =====
├── cron.php                        # wp_schedule_event, wp_cron
│
├── ===== MEDIA =====
├── media.php                       # Media handling
├── class-wp-image-editor.php       # Image manipulation
│
├── ===== BLOCKS (Gutenberg) =====
├── blocks.php                      # register_block_type
├── block-patterns.php              # Block patterns
├── class-wp-block.php              # WP_Block class
├── class-wp-block-type.php         # Block type registration
├── blocks/                         # Built-in block types
│
├── ===== MISC =====
├── option.php                      # get_option, update_option
├── meta.php                        # get_post_meta, update_post_meta
├── widgets.php                     # Widget system
├── nav-menu.php                    # Navigation menu
├── l10n.php                        # __, _e, load_textdomain (i18n)
├── script-loader.php               # wp_enqueue_script, wp_enqueue_style
├── theme.php                       # Theme functions
├── class-wp-theme.php              # WP_Theme class
│
├── css/                            # Core CSS
├── js/                             # Core JavaScript
├── fonts/                          # Dashicons, etc.
├── images/                         # Core images
└── certificates/                   # SSL certificates
```

### Cac class quan trong nhat

#### class-wp.php (WP class)
```php
// Class chinh dieu phoi request
class WP {
    public $query_vars;          // Bien query tu URL
    public $query_string;        // Chuoi query
    public $matched_rule;        // Rewrite rule da match

    public function main() {
        $this->init();
        $this->parse_request();   // Phan tich URL → query vars
        $this->send_headers();    // Gui HTTP headers
        $this->query_posts();     // Thuc hien query
        $this->handle_404();      // Xu ly 404
        $this->register_globals();
    }

    public function parse_request() {
        // Match URL voi rewrite rules
        // Extract query variables
    }
}
```

#### class-wp-query.php (WP_Query)
```php
// Class truy van bai viet - QUAN TRONG NHAT
class WP_Query {
    public $posts;          // Mang bai viet ket qua
    public $post;           // Bai viet hien tai
    public $post_count;     // So bai viet trong trang
    public $found_posts;    // Tong so bai viet
    public $max_num_pages;  // So trang

    // Conditional tags
    public function is_single() { }
    public function is_page() { }
    public function is_archive() { }
    public function is_search() { }
    public function is_404() { }

    // Loop methods
    public function have_posts() { }
    public function the_post() { }
}
```

#### class-wpdb.php (wpdb)
```php
// Database abstraction layer
class wpdb {
    public $prefix;          // Table prefix
    public $posts;           // Ten bang posts (voi prefix)
    public $postmeta;        // Ten bang postmeta

    public function query( $query ) { }
    public function prepare( $query, ...$args ) { }
    public function get_results( $query ) { }
    public function get_row( $query ) { }
    public function get_var( $query ) { }
    public function insert( $table, $data ) { }
    public function update( $table, $data, $where ) { }
    public function delete( $table, $where ) { }
}
```

#### class-wp-hook.php (WP_Hook)
```php
// He thong hook noi bo
class WP_Hook {
    public $callbacks;    // Mang callbacks theo priority

    public function add_filter( $hook_name, $callback, $priority, $accepted_args ) { }
    public function remove_filter( $hook_name, $callback, $priority ) { }
    public function has_filter( $hook_name, $callback ) { }
    public function apply_filters( $value, $args ) { }
    public function do_action( $args ) { }
}
```

### plugin.php - API cong khai cho hooks

```php
// Day la file ma developer su dung truc tiep
function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
    global $wp_filter;
    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        $wp_filter[ $hook_name ] = new WP_Hook();
    }
    $wp_filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, $accepted_args );
    return true;
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
    return add_filter( $hook_name, $callback, $priority, $accepted_args );
    // add_action thuc chat chi la add_filter!
}

function do_action( $hook_name, ...$arg ) {
    // Fire tat ca callbacks da dang ky cho hook nay
}

function apply_filters( $hook_name, $value, ...$args ) {
    // Chay tat ca filters va tra ve gia tri da modify
}
```

---

## 5. Thu Muc wp-content

```
wp-content/
├── plugins/                 # Cac plugin
│   ├── akismet/             # Plugin chong spam (mac dinh)
│   ├── hello.php            # Plugin Hello Dolly (mac dinh)
│   └── my-plugin/           # Plugin cua ban
│
├── themes/                  # Cac theme
│   ├── twentytwentyfour/    # Theme mac dinh
│   └── my-theme/            # Theme cua ban
│
├── uploads/                 # File upload
│   ├── 2024/
│   │   ├── 01/              # Theo thang
│   │   │   ├── image.jpg
│   │   │   └── image-150x150.jpg  # Thumbnail
│   │   └── 02/
│   └── 2025/
│
├── mu-plugins/              # Must-Use plugins (tu dong kich hoat)
│   └── custom-functions.php
│
├── languages/               # File ngon ngu
│   ├── vi_VN.mo
│   └── vi_VN.po
│
├── upgrade/                 # Thu muc tam khi cap nhat
├── cache/                   # Object cache (neu su dung)
│
├── debug.log                # Log loi (khi WP_DEBUG_LOG = true)
├── index.php                # Ngan directory listing
└── advanced-cache.php       # Page cache drop-in
```

### Dac diem quan trong

- **plugins/**: WordPress scan thu muc nay de tim plugin headers
- **themes/**: WordPress scan de tim style.css headers
- **uploads/**: Thu muc duy nhat can quyen ghi (writable)
- **mu-plugins/**: Plugins o day tu dong kich hoat, khong the tat tu admin
- **languages/**: File .mo (compiled) va .po (source) cho da ngon ngu

---

## 6. Cac File Cau Hinh Quan Trong

### wp-config.php - Hang so quan trong

```php
// === DATABASE ===
define( 'DB_NAME',     'wp_database' );
define( 'DB_USER',     'wp_user' );
define( 'DB_PASSWORD', 'password' );
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

// === DEBUG ===
define( 'WP_DEBUG',         true );    // Bat che do debug
define( 'WP_DEBUG_LOG',     true );    // Ghi log vao wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false );   // Tat hien thi loi tren trang
define( 'SAVEQUERIES',      true );    // Luu tat ca SQL queries
define( 'SCRIPT_DEBUG',     true );    // Dung file JS/CSS chua minify

// === SECURITY ===
define( 'DISALLOW_FILE_EDIT',  true );  // Tat editor trong admin
define( 'DISALLOW_FILE_MODS',  true );  // Tat cap nhat/cai dat tu admin
define( 'FORCE_SSL_ADMIN',     true );  // Bat buoc SSL cho admin
define( 'FORCE_SSL_LOGIN',     true );  // Bat buoc SSL cho dang nhap

// === PERFORMANCE ===
define( 'WP_MEMORY_LIMIT',     '256M' );  // Gioi han memory
define( 'WP_MAX_MEMORY_LIMIT', '512M' );  // Gioi han memory cho admin
define( 'WP_CACHE',            true );     // Bat page cache

// === CONTENT ===
define( 'AUTOSAVE_INTERVAL', 120 );    // Auto-save moi 120 giay
define( 'WP_POST_REVISIONS', 5 );     // Gioi han so revisions
define( 'EMPTY_TRASH_DAYS',  7 );     // Xoa thung rac sau 7 ngay
define( 'MEDIA_TRASH',       true );   // Cho phep trash media

// === URLS ===
define( 'WP_HOME',    'https://example.com' );
define( 'WP_SITEURL', 'https://example.com' );

// === CRON ===
define( 'DISABLE_WP_CRON',    true );   // Tat WP cron (dung server cron)
define( 'WP_CRON_LOCK_TIMEOUT', 120 );  // Thoi gian khoa cron

// === MULTISITE ===
define( 'WP_ALLOW_MULTISITE', true );
```

### .htaccess

```apache
# WordPress mac dinh
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

Logic: Neu file/thu muc khong ton tai tren server → chuyen ve `index.php` de WordPress xu ly.

---

## 7. Cac Global Objects

WordPress su dung nhieu global objects. Truy cap bang `global $ten_bien;`

```php
// === DATABASE ===
global $wpdb;
// $wpdb->posts         = 'wp_posts'
// $wpdb->prefix        = 'wp_'
// $wpdb->get_results( "SELECT * FROM {$wpdb->posts}" )

// === QUERY ===
global $wp_query;        // Main query object
global $wp_the_query;    // Ban goc cua main query (khong thay doi)
// $wp_query->posts      = mang bai viet
// $wp_query->is_single()

// === WORDPRESS OBJECT ===
global $wp;
// $wp->query_vars       = query variables tu URL
// $wp->matched_rule     = rewrite rule da match

// === POST HIEN TAI ===
global $post;
// $post->ID, $post->post_title, $post->post_content

// === REWRITE ===
global $wp_rewrite;
// $wp_rewrite->rules            = tat ca rewrite rules
// $wp_rewrite->permalink_structure

// === HOOKS ===
global $wp_filter;       // Tat ca hooks da dang ky
global $wp_actions;      // Dem so lan moi action duoc fire

// === ROLES ===
global $wp_roles;
// $wp_roles->roles      = tat ca roles va capabilities

// === SCRIPTS/STYLES ===
global $wp_scripts;      // WP_Scripts - quan ly JS
global $wp_styles;       // WP_Styles - quan ly CSS

// === ADMIN ===
global $pagenow;         // File admin hien tai (vd: 'edit.php')
global $typenow;         // Post type hien tai
global $taxnow;          // Taxonomy hien tai
```

---

## 8. Design Patterns Trong WordPress

### Observer Pattern (Hooks System)

```php
// WordPress hooks la mot dang Observer Pattern
// Subjects (noi fire event) va Observers (noi lang nghe)

// Subject fire event
do_action( 'save_post', $post_id, $post );

// Observer dang ky lang nghe
add_action( 'save_post', function( $post_id, $post ) {
    // Phan ung khi event xay ra
}, 10, 2 );
```

### Singleton Pattern

```php
// Nhieu class trong WordPress dung Singleton
class WP_Screen {
    private static $instance;

    public static function get() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Registry Pattern

```php
// Post Types va Taxonomies dung Registry
global $wp_post_types;    // Luu tat ca post types da dang ky
global $wp_taxonomies;    // Luu tat ca taxonomies da dang ky

// Dang ky
register_post_type( 'product', $args );   // Them vao registry
register_taxonomy( 'brand', 'product', $args );

// Truy xuat
$post_type = get_post_type_object( 'product' );
$taxonomy = get_taxonomy( 'brand' );
```

### Factory Pattern

```php
// WP_Widget_Factory tao va quan ly widgets
global $wp_widget_factory;
$wp_widget_factory->register( 'WP_Widget_Search' );

// wp_remote_get su dung factory de chon HTTP transport
$response = wp_remote_get( 'https://api.example.com/data' );
// Ben trong, WP_Http chon transport phu hop (cURL, streams, ...)
```

### Template Method Pattern

```php
// WP_List_Table dung template method
class WP_Posts_List_Table extends WP_List_Table {
    // Override cac method cu the
    public function get_columns() { }
    public function column_default( $item, $column_name ) { }
    public function prepare_items() { }
}
```

---

## 9. Cach Doc Source Code Hieu Qua

### Phuong phap tong quat

1. **Bat dau tu entry point:** Doc `index.php` → `wp-blog-header.php` → `wp-load.php` → `wp-settings.php`
2. **Theo luong request:** Hieu tung buoc tu request den response
3. **Tim theo chuc nang:** Dung grep/search de tim ham cu the
4. **Doc comment/PHPDoc:** WordPress comment rat day du

### Cong cu huu ich

```php
// 1. Tim ham trong core
// Dung IDE voi "Go to Definition" hoac grep
grep -rn "function wp_insert_post" wp-includes/

// 2. Xem hook nao duoc fire
add_action( 'all', function( $hook ) {
    error_log( $hook );
} );

// 3. Xem call stack
function my_debug() {
    $trace = debug_backtrace();
    error_log( print_r( $trace, true ) );
}

// 4. Plugin Query Monitor
// Hien thi queries, hooks, conditionals, HTTP requests
```

### Thu tu doc khuyen nghi

1. `wp-settings.php` - Hieu thu tu boot
2. `plugin.php` - Hieu he thong hooks
3. `class-wp-query.php` - Hieu cach query du lieu
4. `template-loader.php` - Hieu template hierarchy
5. `formatting.php` - Hieu cac ham xu ly du lieu
6. `post.php` - Hieu cach quan ly bai viet
7. `user.php` + `capabilities.php` - Hieu he thong quyen
8. `class-wp-rewrite.php` - Hieu URL routing
9. `option.php` - Hieu cach luu cau hinh
10. `rest-api.php` - Hieu REST API

### Tips

- **Doc tu tren xuong duoi** theo luong thuc thi
- **Dat breakpoint** voi Xdebug de theo doi luong
- **Doc test files** trong `tests/` de hieu cach ham hoat dong
- **Tham khao** developer.wordpress.org cho tai lieu chinh thuc
- **So sanh phien ban** tren trac.wordpress.org de thay thay doi qua cac version

---

## Tai Lieu Tham Khao

- [WordPress Developer Resources](https://developer.wordpress.org/)
- [WordPress Code Reference](https://developer.wordpress.org/reference/)
- [WordPress Trac (Source Browser)](https://core.trac.wordpress.org/browser)
- [Make WordPress Core](https://make.wordpress.org/core/)
