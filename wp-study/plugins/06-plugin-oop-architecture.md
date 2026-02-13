# Plugin OOP Architecture

## Muc luc

1. [Tai sao dung OOP cho Plugin](#1-tai-sao-dung-oop-cho-plugin)
2. [Singleton Pattern cho Main Plugin Class](#2-singleton-pattern-cho-main-plugin-class)
3. [Autoloading](#3-autoloading)
4. [Dependency Injection co ban](#4-dependency-injection-co-ban)
5. [Namespaces trong Plugin](#5-namespaces-trong-plugin)
6. [Cau truc thu muc OOP](#6-cau-truc-thu-muc-oop)
7. [MVC Pattern trong Plugin](#7-mvc-pattern-trong-plugin)
8. [Plugin Boilerplate](#8-plugin-boilerplate)
9. [Code vi du: Plugin hoan chinh theo kien truc OOP](#9-code-vi-du-plugin-hoan-chinh-theo-kien-truc-oop)
10. [So sanh voi cau truc Laravel](#10-so-sanh-voi-cau-truc-laravel)
11. [Best Practices](#11-best-practices)

---

## 1. Tai sao dung OOP cho Plugin

### Van de voi Procedural Code

```php
<?php
// Procedural: Tat ca la functions rieng le
// Kho quan ly khi plugin lon

function myp_activate() { }
function myp_deactivate() { }
function myp_add_menu() { }
function myp_settings_page() { }
function myp_save_settings() { }
function myp_enqueue_scripts() { }
function myp_shortcode_handler() { }
function myp_ajax_handler() { }
function myp_widget_init() { }
// ... 50+ functions => Kho bao tri!

// Van de:
// 1. Tat ca functions nam trong global scope => de xung dot ten
// 2. Khong co cau truc ro rang
// 3. Kho test (unit testing)
// 4. Kho tai su dung
// 5. Kho hieu khi plugin lon
```

### Uu diem OOP

```php
<?php
// OOP: Code duoc to chuc thanh classes

// 1. Encapsulation - Gom nhom code lien quan
class Admin_Settings {
    public function register() { }
    public function render_page() { }
    private function sanitize() { }
}

// 2. Namespace - Tranh xung dot ten
namespace MyPlugin\Admin;
class Settings { }  // MyPlugin\Admin\Settings - khong trung voi plugin khac

// 3. Inheritance - Ke thua va tai su dung
class Base_Widget extends \WP_Widget { }
class Contact_Widget extends Base_Widget { }
class Social_Widget extends Base_Widget { }

// 4. Testable - De viet unit test
class Calculator {
    public function add($a, $b) { return $a + $b; }
}
// Test: assertEquals(3, $calc->add(1, 2));

// 5. Maintainable - De bao tri
// Moi class 1 trach nhiem (Single Responsibility)
// Thay doi 1 class khong anh huong class khac
```

---

## 2. Singleton Pattern cho Main Plugin Class

### Singleton Pattern la gi?

Singleton dam bao chi co **duy nhat 1 instance** cua class trong toan bo ung dung. Day la pattern pho bien nhat cho Main Plugin Class.

```php
<?php
/**
 * Plugin Name: OOP Plugin
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Class - Singleton Pattern
 *
 * Chi co 1 instance duy nhat trong suot vong doi cua request.
 * Cac phan khac truy cap qua: OOP_Plugin::get_instance()
 */
final class OOP_Plugin {

    /**
     * Phien ban plugin
     */
    const VERSION = '1.0.0';

    /**
     * Instance duy nhat
     * @var OOP_Plugin|null
     */
    private static $instance = null;

    /**
     * Duong dan thu muc plugin
     * @var string
     */
    private $plugin_path;

    /**
     * URL cua plugin
     * @var string
     */
    private $plugin_url;

    /**
     * Lay instance duy nhat (tao moi neu chua co)
     *
     * @return OOP_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - PRIVATE de ngan tao object tu ben ngoai
     * Chi duoc goi 1 lan tu get_instance()
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url  = plugin_dir_url( __FILE__ );

        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Ngan clone object
     */
    private function __clone() { }

    /**
     * Ngan unserialize
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton.' );
    }

    /**
     * Dinh nghia constants
     */
    private function define_constants() {
        define( 'OOP_PLUGIN_VERSION', self::VERSION );
        define( 'OOP_PLUGIN_PATH', $this->plugin_path );
        define( 'OOP_PLUGIN_URL', $this->plugin_url );
        define( 'OOP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
    }

    /**
     * Include cac file can thiet
     */
    private function includes() {
        // Core
        require_once $this->plugin_path . 'includes/class-activator.php';
        require_once $this->plugin_path . 'includes/class-deactivator.php';

        // Admin - chi load trong admin
        if ( is_admin() ) {
            require_once $this->plugin_path . 'admin/class-admin.php';
            require_once $this->plugin_path . 'admin/class-settings.php';
        }

        // Public - chi load o frontend
        if ( ! is_admin() ) {
            require_once $this->plugin_path . 'public/class-frontend.php';
        }
    }

    /**
     * Dang ky hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook( __FILE__, array( 'OOP_Plugin_Activator', 'activate' ) );
        register_deactivation_hook( __FILE__, array( 'OOP_Plugin_Deactivator', 'deactivate' ) );

        // Init
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        add_action( 'init', array( $this, 'on_init' ) );
    }

    /**
     * Chay sau khi tat ca plugins da load
     */
    public function on_plugins_loaded() {
        // Load textdomain cho da ngon ngu
        load_plugin_textdomain(
            'oop-plugin',
            false,
            dirname( OOP_PLUGIN_BASENAME ) . '/languages'
        );

        // Kiem tra va upgrade database
        $this->maybe_upgrade();
    }

    /**
     * Chay khi WordPress init
     */
    public function on_init() {
        // Khoi tao cac components
        if ( is_admin() ) {
            new OOP_Plugin_Admin();
            new OOP_Plugin_Settings();
        } else {
            new OOP_Plugin_Frontend();
        }
    }

    /**
     * Kiem tra va upgrade database
     */
    private function maybe_upgrade() {
        $installed = get_option( 'oop_plugin_version', '0.0.0' );
        if ( version_compare( $installed, self::VERSION, '<' ) ) {
            OOP_Plugin_Activator::activate();
            update_option( 'oop_plugin_version', self::VERSION );
        }
    }

    // === GETTERS ===

    public function get_plugin_path() {
        return $this->plugin_path;
    }

    public function get_plugin_url() {
        return $this->plugin_url;
    }

    public function get_version() {
        return self::VERSION;
    }
}

// Khoi dong plugin
OOP_Plugin::get_instance();
```

---

## 3. Autoloading

### 3.1. spl_autoload_register (khong can Composer)

```php
<?php
/**
 * Autoloading - Tu dong load file class khi can.
 * Khong can require_once tung file thu cong.
 *
 * Quy tac: Ten class map voi ten file.
 * Class: MyPlugin_Admin_Settings
 * File:  includes/class-admin-settings.php
 */

spl_autoload_register( function( $class_name ) {
    // Chi xu ly classes cua plugin nay
    // Kiem tra prefix
    $prefix = 'OOP_Plugin_';
    if ( strpos( $class_name, $prefix ) !== 0 ) {
        return; // Khong phai class cua plugin, bo qua
    }

    // Chuyen ten class thanh ten file
    // OOP_Plugin_Admin_Settings => admin-settings
    $class_file = str_replace( $prefix, '', $class_name );  // Admin_Settings
    $class_file = strtolower( $class_file );                  // admin_settings
    $class_file = str_replace( '_', '-', $class_file );       // admin-settings
    $class_file = 'class-' . $class_file . '.php';            // class-admin-settings.php

    // Tim file trong nhieu thu muc
    $directories = array(
        OOP_PLUGIN_PATH . 'includes/',
        OOP_PLUGIN_PATH . 'admin/',
        OOP_PLUGIN_PATH . 'public/',
    );

    foreach ( $directories as $dir ) {
        $file = $dir . $class_file;
        if ( file_exists( $file ) ) {
            require_once $file;
            return;
        }
    }
});

// Gio chi can dung class, file se tu dong duoc load:
// $admin = new OOP_Plugin_Admin_Settings();
// => Tu dong load: includes/class-admin-settings.php
```

### 3.2. PSR-4 Autoloading voi Namespace

```php
<?php
/**
 * PSR-4 Autoloader - Cach chuan cua PHP.
 * Map namespace voi thu muc.
 *
 * Namespace: MyPlugin\Admin\Settings
 * File:      src/Admin/Settings.php
 */

spl_autoload_register( function( $class ) {
    // Namespace goc cua plugin
    $base_namespace = 'MyPlugin\\';
    $base_dir = __DIR__ . '/src/';

    // Kiem tra namespace co thuoc plugin khong
    $len = strlen( $base_namespace );
    if ( strncmp( $base_namespace, $class, $len ) !== 0 ) {
        return; // Khong phai namespace cua plugin
    }

    // Lay phan namespace con
    // MyPlugin\Admin\Settings => Admin\Settings
    $relative_class = substr( $class, $len );

    // Chuyen namespace thanh duong dan file
    // Admin\Settings => Admin/Settings.php
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

// Vi du:
// use MyPlugin\Admin\Settings;
// $s = new Settings();
// => Load: src/Admin/Settings.php
```

### 3.3. Composer Autoloading (KHUYEN DUNG)

```json
// composer.json
{
    "name": "developer/my-plugin",
    "description": "My WordPress Plugin",
    "autoload": {
        "psr-4": {
            "MyPlugin\\": "src/"
        }
    },
    "require": {
        "php": ">=7.4"
    }
}
```

```bash
# Cai dat va tao autoloader
composer install

# Hoac chi tao autoloader (khong cai dependencies)
composer dump-autoload
```

```php
<?php
/**
 * Plugin Name: My Plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Gio co the dung bat ky class nao trong src/
use MyPlugin\Core\Plugin;
use MyPlugin\Admin\Settings;
use MyPlugin\Frontend\Display;

Plugin::get_instance();
```

```
Cau truc thu muc voi Composer:
my-plugin/
  my-plugin.php            # File chinh, load autoloader
  composer.json             # Cau hinh Composer
  vendor/                   # Thu muc Composer (KHONG commit vao git)
    autoload.php
  src/                      # Source code (PSR-4)
    Core/
      Plugin.php            # Main class
      Activator.php
    Admin/
      Settings.php
      Menu.php
    Frontend/
      Display.php
      Shortcodes.php
    Models/
      Contact.php
```

---

## 4. Dependency Injection co ban

```php
<?php
namespace MyPlugin\Core;

/**
 * Dependency Injection (DI) - Tiem phu thuoc tu ben ngoai.
 *
 * Thay vi class tu tao dependencies cua minh,
 * chung ta truyen (inject) chung tu ben ngoai.
 *
 * Laravel dung Service Container de tu dong DI.
 * WordPress khong co san, ta tu lam.
 */

// === SAI: Tight Coupling (phu thuoc chat) ===
class AdminPage_Bad {
    private $db;

    public function __construct() {
        // Tu tao dependency => kho test, kho thay doi
        $this->db = new DatabaseHandler();
    }
}

// === DUNG: Dependency Injection ===
class AdminPage_Good {
    private $db;

    // Nhan dependency tu ben ngoai
    public function __construct( DatabaseInterface $db ) {
        $this->db = $db;
    }
}

// Su dung:
$db = new MySQLHandler();
$page = new AdminPage_Good( $db );

// Test:
$mock_db = new MockDatabaseHandler();
$page_test = new AdminPage_Good( $mock_db );
```

### Container don gian cho WordPress Plugin

```php
<?php
namespace MyPlugin\Core;

/**
 * Service Container don gian
 * Tuong tu Laravel's Service Container nhung don gian hon nhieu
 */
class Container {

    /**
     * Luu tru cac services da dang ky
     */
    private $bindings = array();

    /**
     * Luu tru cac singleton instances
     */
    private $instances = array();

    /**
     * Dang ky 1 service
     */
    public function bind( string $abstract, callable $factory ) {
        $this->bindings[ $abstract ] = $factory;
    }

    /**
     * Dang ky 1 singleton (chi tao 1 lan)
     */
    public function singleton( string $abstract, callable $factory ) {
        $this->bindings[ $abstract ] = function() use ( $abstract, $factory ) {
            if ( ! isset( $this->instances[ $abstract ] ) ) {
                $this->instances[ $abstract ] = $factory( $this );
            }
            return $this->instances[ $abstract ];
        };
    }

    /**
     * Lay 1 service
     */
    public function make( string $abstract ) {
        if ( isset( $this->bindings[ $abstract ] ) ) {
            return call_user_func( $this->bindings[ $abstract ], $this );
        }
        throw new \Exception( "Service [{$abstract}] not found." );
    }
}

// === Su dung ===

$container = new Container();

// Dang ky services
$container->singleton( 'database', function( $c ) {
    global $wpdb;
    return $wpdb;
});

$container->singleton( 'settings', function( $c ) {
    return new \MyPlugin\Admin\Settings( $c->make( 'database' ) );
});

$container->bind( 'contact_repository', function( $c ) {
    return new \MyPlugin\Models\ContactRepository( $c->make( 'database' ) );
});

// Lay service
$settings = $container->make( 'settings' );
$repo = $container->make( 'contact_repository' );
```

---

## 5. Namespaces trong Plugin

```php
<?php
/**
 * Namespaces giup tranh xung dot ten giua cac plugins.
 *
 * Khong co namespace:
 *   class Settings {}     // Trung voi plugin khac!
 *
 * Co namespace:
 *   MyPlugin\Admin\Settings    // Duy nhat
 *   OtherPlugin\Admin\Settings // Khong trung
 */

// === File: src/Core/Plugin.php ===
namespace MyPlugin\Core;

// Khai bao namespace o dong dau tien (sau <?php)
// Tat ca classes, functions, constants trong file nay
// thuoc namespace MyPlugin\Core

class Plugin {
    const VERSION = '1.0.0';

    public static function get_instance() {
        // ...
    }
}

// === File: src/Admin/Settings.php ===
namespace MyPlugin\Admin;

// Import classes tu namespace khac bang 'use'
use MyPlugin\Core\Plugin;
use MyPlugin\Models\Contact;

// Import class cua WordPress (global namespace)
// Them \ truoc hoac dung 'use'
use WP_Widget;

class Settings {
    public function init() {
        // Dung class cua WordPress trong namespace
        // Phai them \ (backslash) truoc class global
        add_action( 'admin_init', array( $this, 'register' ) );

        // Hoac import bang use o tren
        $plugin = Plugin::get_instance();
    }

    public function register() {
        // Khi goi WordPress functions trong namespace
        // Co the dung truc tiep (functions global tu dong resolve)
        register_setting( 'my_group', 'my_option' );

        // Nhung voi classes phai them \
        $query = new \WP_Query( array() );
    }
}

// === File: src/Models/Contact.php ===
namespace MyPlugin\Models;

class Contact {
    private $wpdb;

    public function __construct() {
        // Global variable trong namespace phai dung 'global'
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function find( int $id ): ?object {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}contacts WHERE id = %d",
                $id
            )
        );
    }
}

// === File chinh: my-plugin.php ===
namespace MyPlugin;

// Import
use MyPlugin\Core\Plugin;

// Hoac dung ten day du:
// \MyPlugin\Core\Plugin::get_instance();
```

---

## 6. Cau truc thu muc OOP

```
my-awesome-plugin/
|
|-- my-awesome-plugin.php          # File chinh (entry point)
|-- uninstall.php                  # Xu ly khi xoa plugin
|-- composer.json                  # Composer config
|-- README.md                      # Mo ta plugin
|
|-- src/                           # Source code (PSR-4 autoload)
|   |-- Core/                      # Loi cua plugin
|   |   |-- Plugin.php             # Main class (Singleton)
|   |   |-- Activator.php          # Activation handler
|   |   |-- Deactivator.php        # Deactivation handler
|   |   |-- Loader.php             # Hook loader (luu tru hooks)
|   |   |-- Container.php          # Simple DI Container
|   |   |-- I18n.php               # Internationalization
|   |
|   |-- Admin/                     # Admin area
|   |   |-- AdminController.php    # Main admin controller
|   |   |-- Settings.php           # Settings page
|   |   |-- Menu.php               # Menu registration
|   |   |-- AdminAssets.php        # CSS/JS cho admin
|   |   |-- ListTable.php          # WP_List_Table extensions
|   |
|   |-- Frontend/                  # Public facing
|   |   |-- FrontendController.php
|   |   |-- Shortcodes.php
|   |   |-- FrontendAssets.php
|   |
|   |-- Models/                    # Data models
|   |   |-- Contact.php
|   |   |-- ContactRepository.php
|   |
|   |-- Api/                       # REST API
|   |   |-- RestController.php
|   |   |-- ContactEndpoints.php
|   |
|   |-- Widgets/                   # Widgets
|   |   |-- ContactWidget.php
|   |
|   |-- Services/                  # Business logic
|   |   |-- EmailService.php
|   |   |-- CacheService.php
|   |
|   |-- Traits/                    # Shared traits
|   |   |-- HasMeta.php
|   |   |-- Sanitizable.php
|
|-- templates/                     # View templates
|   |-- admin/
|   |   |-- settings-page.php
|   |   |-- contact-list.php
|   |   |-- contact-form.php
|   |-- frontend/
|   |   |-- contact-display.php
|   |   |-- shortcode-output.php
|
|-- assets/                        # Static files
|   |-- css/
|   |   |-- admin.css
|   |   |-- frontend.css
|   |-- js/
|   |   |-- admin.js
|   |   |-- frontend.js
|   |-- images/
|       |-- icon.svg
|
|-- languages/                     # Translation files
|   |-- my-awesome-plugin.pot
|   |-- my-awesome-plugin-vi.po
|   |-- my-awesome-plugin-vi.mo
|
|-- tests/                         # Unit tests
|   |-- bootstrap.php
|   |-- Unit/
|   |   |-- ContactTest.php
|   |-- Integration/
|       |-- SettingsTest.php
|
|-- vendor/                        # Composer dependencies (KHONG commit)
    |-- autoload.php
```

### So sanh voi Laravel

```
Laravel                          WordPress Plugin OOP
app/                      =>    src/
app/Http/Controllers/     =>    src/Admin/, src/Frontend/
app/Models/               =>    src/Models/
app/Services/             =>    src/Services/
app/Http/Middleware/       =>    permission callbacks
resources/views/          =>    templates/
public/                   =>    assets/
routes/                   =>    Hooks trong src/Core/Plugin.php
config/                   =>    Settings API
database/migrations/      =>    src/Core/Activator.php (dbDelta)
tests/                    =>    tests/
vendor/                   =>    vendor/
```

---

## 7. MVC Pattern trong Plugin

```php
<?php
/**
 * MVC (Model-View-Controller) trong WordPress Plugin.
 *
 * Model      = Xu ly du lieu (database queries)
 * View       = Hien thi (templates)
 * Controller = Dieu phoi (nhan request, goi model, tra view)
 */

// === MODEL: src/Models/Contact.php ===
namespace MyPlugin\Models;

class Contact {

    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'mp_contacts';
    }

    /**
     * Lay tat ca contacts voi pagination
     */
    public function get_all( array $args = array() ): array {
        global $wpdb;

        $defaults = array(
            'per_page' => 10,
            'page'     => 1,
            'search'   => '',
            'status'   => '',
            'orderby'  => 'id',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where = "WHERE 1=1";
        $params = array();

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where .= " AND (first_name LIKE %s OR email LIKE %s)";
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty( $args['status'] ) ) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }

        $safe_orderby = in_array( $args['orderby'], array('id','first_name','email','created_at') )
            ? $args['orderby'] : 'id';
        $safe_order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $total = empty($params)
            ? $wpdb->get_var( $count_sql )
            : $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        // Data
        $data_sql = "SELECT * FROM {$this->table} {$where} ORDER BY {$safe_orderby} {$safe_order} LIMIT %d OFFSET %d";
        $all_params = array_merge( $params, array( $args['per_page'], $offset ) );
        $items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$all_params ) );

        return array(
            'items'       => $items,
            'total'       => intval( $total ),
            'total_pages' => ceil( $total / $args['per_page'] ),
            'page'        => $args['page'],
        );
    }

    /**
     * Tim 1 contact theo ID
     */
    public function find( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
        );
    }

    /**
     * Tao contact moi
     */
    public function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( $this->table, $data, array_fill( 0, count($data), '%s' ) );
        return $wpdb->insert_id;
    }

    /**
     * Cap nhat contact
     */
    public function update( int $id, array $data ): bool {
        global $wpdb;
        return false !== $wpdb->update(
            $this->table, $data, array( 'id' => $id ),
            array_fill( 0, count($data), '%s' ), array( '%d' )
        );
    }

    /**
     * Xoa contact
     */
    public function delete( int $id ): bool {
        global $wpdb;
        return false !== $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
    }
}

// === CONTROLLER: src/Admin/ContactController.php ===
namespace MyPlugin\Admin;

use MyPlugin\Models\Contact;

class ContactController {

    private $model;

    public function __construct() {
        $this->model = new Contact();
    }

    /**
     * Xu ly trang danh sach
     */
    public function index() {
        $args = array(
            'per_page' => 10,
            'page'     => max( 1, intval( $_GET['paged'] ?? 1 ) ),
            'search'   => sanitize_text_field( $_GET['s'] ?? '' ),
            'status'   => sanitize_text_field( $_GET['status'] ?? '' ),
        );

        $result = $this->model->get_all( $args );

        // Truyen data cho view
        $this->render( 'admin/contact-list', array(
            'contacts'    => $result['items'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
            'current_page' => $result['page'],
            'search'      => $args['search'],
        ));
    }

    /**
     * Xu ly trang tao moi
     */
    public function create() {
        $errors = array();
        $data = array();

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            check_admin_referer( 'mp_create_contact' );

            $data = $this->sanitize_input( $_POST );
            $errors = $this->validate( $data );

            if ( empty( $errors ) ) {
                $id = $this->model->create( $data );
                if ( $id ) {
                    wp_redirect( admin_url( 'admin.php?page=mp-contacts&created=1' ) );
                    exit;
                }
                $errors[] = 'Loi khi luu vao database.';
            }
        }

        $this->render( 'admin/contact-form', array(
            'action' => 'create',
            'data'   => $data,
            'errors' => $errors,
        ));
    }

    /**
     * Xu ly trang chinh sua
     */
    public function edit() {
        $id = absint( $_GET['id'] ?? 0 );
        $contact = $this->model->find( $id );

        if ( ! $contact ) {
            wp_die( 'Contact khong ton tai.' );
        }

        $errors = array();

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            check_admin_referer( 'mp_update_contact' );

            $data = $this->sanitize_input( $_POST );
            $errors = $this->validate( $data );

            if ( empty( $errors ) ) {
                $this->model->update( $id, $data );
                wp_redirect( admin_url( 'admin.php?page=mp-contacts&updated=1' ) );
                exit;
            }

            $contact = (object) array_merge( (array) $contact, $data );
        }

        $this->render( 'admin/contact-form', array(
            'action'  => 'edit',
            'data'    => (array) $contact,
            'errors'  => $errors,
        ));
    }

    /**
     * Xu ly xoa
     */
    public function destroy() {
        check_admin_referer( 'mp_delete_contact' );
        $id = absint( $_GET['id'] ?? 0 );
        $this->model->delete( $id );
        wp_redirect( admin_url( 'admin.php?page=mp-contacts&deleted=1' ) );
        exit;
    }

    /**
     * Render view template
     */
    private function render( string $template, array $data = array() ) {
        // Extract data thanh bien: $data['contacts'] => $contacts
        extract( $data );

        $file = OOP_PLUGIN_PATH . "templates/{$template}.php";
        if ( file_exists( $file ) ) {
            include $file;
        }
    }

    /**
     * Sanitize input
     */
    private function sanitize_input( array $input ): array {
        return array(
            'first_name' => sanitize_text_field( $input['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $input['last_name'] ?? '' ),
            'email'      => sanitize_email( $input['email'] ?? '' ),
            'phone'      => sanitize_text_field( $input['phone'] ?? '' ),
            'status'     => in_array( $input['status'] ?? '', array('active','inactive','lead') )
                           ? $input['status'] : 'lead',
        );
    }

    /**
     * Validate
     */
    private function validate( array $data ): array {
        $errors = array();
        if ( empty( $data['first_name'] ) ) $errors[] = 'Ho la bat buoc.';
        if ( ! is_email( $data['email'] ) ) $errors[] = 'Email khong hop le.';
        return $errors;
    }
}

// === VIEW: templates/admin/contact-list.php ===
// <?php
// // Bien co san: $contacts, $total, $total_pages, $current_page, $search
// ?>
// <div class="wrap">
//     <h1>Danh sach Contacts</h1>
//     <!-- Table HTML o day, dung $contacts, $total, v.v. -->
// </div>
```

---

## 8. Plugin Boilerplate

WordPress Plugin Boilerplate (WPPB) la template/scaffold giup khoi tao plugin OOP nhanh chong.

### Cai dat va su dung

```bash
# Tai WPPB tu: https://wppb.me/
# Nhap thong tin plugin => tai ve scaffold

# Hoac clone tu GitHub:
git clone https://github.com/DevinVinson/WordPress-Plugin-Boilerplate.git my-plugin
cd my-plugin

# Doi ten cac file va class theo plugin cua ban
# (Dung search-replace tren editor)
```

### Cau truc WPPB

```
my-plugin/
|-- my-plugin.php                 # Entry point
|-- includes/
|   |-- class-my-plugin.php       # Main orchestrator class
|   |-- class-my-plugin-loader.php     # Hook registry
|   |-- class-my-plugin-activator.php  # Activation
|   |-- class-my-plugin-deactivator.php # Deactivation
|   |-- class-my-plugin-i18n.php       # i18n
|
|-- admin/
|   |-- class-my-plugin-admin.php      # Admin functionality
|   |-- partials/
|   |   |-- my-plugin-admin-display.php # Admin views
|   |-- css/
|   |   |-- my-plugin-admin.css
|   |-- js/
|       |-- my-plugin-admin.js
|
|-- public/
|   |-- class-my-plugin-public.php     # Public functionality
|   |-- partials/
|   |   |-- my-plugin-public-display.php
|   |-- css/
|   |   |-- my-plugin-public.css
|   |-- js/
|       |-- my-plugin-public.js
|
|-- languages/
    |-- my-plugin.pot
```

### Loader Class - Quan ly Hooks

```php
<?php
/**
 * Loader class luu tru tat ca hooks va chay chung.
 * Giup quan ly hooks tap trung thay vi rai rac.
 */
class Plugin_Loader {

    /**
     * Mang luu actions
     */
    protected $actions = array();

    /**
     * Mang luu filters
     */
    protected $filters = array();

    /**
     * Dang ky action
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Dang ky filter
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Chay tat ca hooks da dang ky
     * Goi method nay o cuoi qua trinh khoi tao plugin
     */
    public function run() {
        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}

// === Su dung trong Main Class ===
class My_Plugin {

    protected $loader;

    public function __construct() {
        $this->loader = new Plugin_Loader();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function define_admin_hooks() {
        $admin = new My_Plugin_Admin();
        $this->loader->add_action( 'admin_menu', $admin, 'add_menu' );
        $this->loader->add_action( 'admin_init', $admin, 'register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
    }

    private function define_public_hooks() {
        $public = new My_Plugin_Public();
        $this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
        $this->loader->add_filter( 'the_content', $public, 'modify_content' );
    }

    public function run() {
        $this->loader->run();
    }
}
```

---

## 9. Code vi du: Plugin hoan chinh theo kien truc OOP

### File chinh: my-oop-plugin.php

```php
<?php
/**
 * Plugin Name:       My OOP Plugin
 * Description:       Plugin mau voi kien truc OOP hoan chinh.
 * Version:           1.0.0
 * Author:            Developer
 * Text Domain:       my-oop-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'MOP_VERSION', '1.0.0' );
define( 'MOP_FILE', __FILE__ );
define( 'MOP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MOP_URL', plugin_dir_url( __FILE__ ) );
define( 'MOP_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader (PSR-4 style, khong can Composer)
spl_autoload_register( function( $class ) {
    $prefix = 'MOP\\';
    $base_dir = MOP_PATH . 'src/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) return;

    $relative = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

// Activation / Deactivation
register_activation_hook( __FILE__, array( 'MOP\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MOP\\Core\\Deactivator', 'deactivate' ) );

// Boot plugin
add_action( 'plugins_loaded', function() {
    MOP\Core\Plugin::get_instance();
});
```

### src/Core/Plugin.php

```php
<?php
namespace MOP\Core;

use MOP\Admin\AdminController;
use MOP\Admin\Settings;
use MOP\Frontend\FrontendController;
use MOP\Api\RestController;

final class Plugin {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        // Core
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Admin
        if ( is_admin() ) {
            $admin = new AdminController();
            add_action( 'admin_menu', array( $admin, 'register_menus' ) );
            add_action( 'admin_init', array( $admin, 'handle_actions' ) );
            add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );

            $settings = new Settings();
            add_action( 'admin_init', array( $settings, 'register' ) );
        }

        // Frontend
        if ( ! is_admin() ) {
            $frontend = new FrontendController();
            add_action( 'wp_enqueue_scripts', array( $frontend, 'enqueue_assets' ) );
            add_shortcode( 'mop_contacts', array( $frontend, 'shortcode_contacts' ) );
        }

        // REST API
        add_action( 'rest_api_init', function() {
            $api = new RestController();
            $api->register_routes();
        });

        // Plugin action links
        add_filter( 'plugin_action_links_' . MOP_BASENAME, array( $this, 'action_links' ) );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'my-oop-plugin', false, dirname( MOP_BASENAME ) . '/languages' );
    }

    public function action_links( array $links ): array {
        $custom = array(
            '<a href="' . admin_url( 'admin.php?page=mop-settings' ) . '">Cai dat</a>',
        );
        return array_merge( $custom, $links );
    }
}
```

### src/Core/Activator.php

```php
<?php
namespace MOP\Core;

class Activator {

    public static function activate(): void {
        self::create_tables();
        self::add_options();
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mop_items';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(200) NOT NULL DEFAULT '',
            description text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'mop_db_version', MOP_VERSION );
    }

    private static function add_options(): void {
        add_option( 'mop_settings', array(
            'per_page'    => 10,
            'enable_api'  => true,
            'date_format' => 'Y-m-d',
        ));
    }
}
```

### src/Core/Deactivator.php

```php
<?php
namespace MOP\Core;

class Deactivator {

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
```

### src/Models/Item.php

```php
<?php
namespace MOP\Models;

class Item {

    private $table;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'mop_items';
    }

    public function all( array $args = array() ): array {
        $defaults = array( 'per_page' => 10, 'page' => 1, 'search' => '', 'status' => '' );
        $args = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where = "WHERE 1=1";
        $params = array();

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
            $where .= " AND (title LIKE %s OR description LIKE %s)";
            $params[] = $like;
            $params[] = $like;
        }
        if ( ! empty( $args['status'] ) ) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }

        $count_sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $total = empty( $params )
            ? $this->wpdb->get_var( $count_sql )
            : $this->wpdb->get_var( $this->wpdb->prepare( $count_sql, ...$params ) );

        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $all_params = array_merge( $params, array( $args['per_page'], $offset ) );
        $items = $this->wpdb->get_results( $this->wpdb->prepare( $sql, ...$all_params ) );

        return compact( 'items', 'total' ) + array(
            'pages' => ceil( $total / $args['per_page'] ),
            'page'  => $args['page'],
        );
    }

    public function find( int $id ): ?object {
        return $this->wpdb->get_row(
            $this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
        );
    }

    public function create( array $data ): int {
        $this->wpdb->insert( $this->table, $data, $this->get_format( $data ) );
        return $this->wpdb->insert_id;
    }

    public function update( int $id, array $data ): bool {
        return false !== $this->wpdb->update(
            $this->table, $data, array( 'id' => $id ),
            $this->get_format( $data ), array( '%d' )
        );
    }

    public function delete( int $id ): bool {
        return false !== $this->wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
    }

    private function get_format( array $data ): array {
        return array_map( function( $value ) {
            return is_int( $value ) ? '%d' : '%s';
        }, $data );
    }
}
```

### src/Admin/AdminController.php

```php
<?php
namespace MOP\Admin;

use MOP\Models\Item;

class AdminController {

    private $model;

    public function __construct() {
        $this->model = new Item();
    }

    public function register_menus(): void {
        add_menu_page(
            'My OOP Plugin', 'OOP Plugin', 'manage_options',
            'mop-items', array( $this, 'page_list' ),
            'dashicons-admin-generic', 30
        );

        add_submenu_page(
            'mop-items', 'Tat ca', 'Tat ca', 'manage_options',
            'mop-items', array( $this, 'page_list' )
        );

        add_submenu_page(
            'mop-items', 'Them moi', 'Them moi', 'manage_options',
            'mop-items-add', array( $this, 'page_form' )
        );

        add_submenu_page(
            'mop-items', 'Cai dat', 'Cai dat', 'manage_options',
            'mop-settings', array( $this, 'page_settings' )
        );
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'mop-' ) === false ) return;
        wp_enqueue_style( 'mop-admin', MOP_URL . 'assets/css/admin.css', array(), MOP_VERSION );
        wp_enqueue_script( 'mop-admin', MOP_URL . 'assets/js/admin.js', array('jquery'), MOP_VERSION, true );
        wp_localize_script( 'mop-admin', 'mopAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'mop_admin_nonce' ),
        ));
    }

    public function handle_actions(): void {
        if ( isset( $_POST['mop_action'] ) && $_POST['mop_action'] === 'save_item' ) {
            check_admin_referer( 'mop_save_item' );
            $data = array(
                'title'       => sanitize_text_field( $_POST['title'] ?? '' ),
                'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
                'status'      => sanitize_text_field( $_POST['status'] ?? 'draft' ),
                'created_by'  => get_current_user_id(),
            );
            $id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
            if ( $id > 0 ) {
                $this->model->update( $id, $data );
            } else {
                $this->model->create( $data );
            }
            wp_redirect( admin_url( 'admin.php?page=mop-items&saved=1' ) );
            exit;
        }

        if ( isset( $_GET['mop_delete'] ) ) {
            check_admin_referer( 'mop_delete_item' );
            $this->model->delete( absint( $_GET['mop_delete'] ) );
            wp_redirect( admin_url( 'admin.php?page=mop-items&deleted=1' ) );
            exit;
        }
    }

    public function page_list(): void {
        $result = $this->model->all( array(
            'page'    => max( 1, intval( $_GET['paged'] ?? 1 ) ),
            'search'  => sanitize_text_field( $_GET['s'] ?? '' ),
            'status'  => sanitize_text_field( $_GET['status'] ?? '' ),
        ));
        extract( $result ); // $items, $total, $pages, $page
        include MOP_PATH . 'templates/admin/item-list.php';
    }

    public function page_form(): void {
        $item = null;
        if ( isset( $_GET['id'] ) ) {
            $item = $this->model->find( absint( $_GET['id'] ) );
        }
        include MOP_PATH . 'templates/admin/item-form.php';
    }

    public function page_settings(): void {
        include MOP_PATH . 'templates/admin/settings.php';
    }
}
```

---

## 10. So sanh voi cau truc Laravel

| Khai niem | Laravel | WordPress Plugin OOP |
|-----------|---------|---------------------|
| **Entry point** | `public/index.php` | `my-plugin.php` |
| **App bootstrap** | `App\Providers\AppServiceProvider` | `Plugin::get_instance()` |
| **Autoloading** | Composer PSR-4 | Composer hoac `spl_autoload_register` |
| **Routing** | `routes/web.php`, `routes/api.php` | `add_action('admin_menu')`, `register_rest_route()` |
| **Controllers** | `app/Http/Controllers/` | `src/Admin/`, `src/Api/` |
| **Models** | `app/Models/` | `src/Models/` |
| **Views** | `resources/views/` (Blade) | `templates/` (PHP) |
| **Middleware** | `app/Http/Middleware/` | `permission_callback`, `current_user_can()` |
| **Events** | `app/Events/`, `app/Listeners/` | Actions & Filters |
| **Config** | `config/` | `get_option()` |
| **DI Container** | Built-in (automatic) | Tu viet hoac don gian |
| **Migrations** | `database/migrations/` | `dbDelta()` trong Activator |
| **Testing** | PHPUnit + Laravel helpers | PHPUnit + `WP_UnitTestCase` |

---

## 11. Best Practices

### 1. Mot class, mot trach nhiem (SRP)

```php
<?php
// SAI: 1 class lam qua nhieu viec
class God_Class {
    public function add_menu() { }
    public function render_page() { }
    public function save_data() { }
    public function send_email() { }
    public function create_table() { }
    public function handle_ajax() { }
}

// DUNG: Tach thanh nhieu classes
class Menu { public function register() { } }
class Settings { public function register() { } }
class EmailService { public function send() { } }
class AjaxHandler { public function handle() { } }
```

### 2. Dung Interfaces

```php
<?php
namespace MOP\Contracts;

interface RepositoryInterface {
    public function all( array $args = array() ): array;
    public function find( int $id ): ?object;
    public function create( array $data ): int;
    public function update( int $id, array $data ): bool;
    public function delete( int $id ): bool;
}

// Implement
class ContactRepository implements RepositoryInterface {
    // ...
}
```

### 3. Prefix hoac Namespace tat ca

```php
<?php
// Dung namespace cho classes
namespace MyPlugin\Core;

// Dung prefix cho hooks/filters
do_action( 'myplugin_after_save', $id );
apply_filters( 'myplugin_item_data', $data );

// Dung prefix cho database
$wpdb->prefix . 'myplugin_items';

// Dung prefix cho options
get_option( 'myplugin_settings' );
```

### 4. Tach logic khoi views

```php
<?php
// SAI: Logic trong template
// templates/admin/list.php
// <?php
// global $wpdb;
// $items = $wpdb->get_results("SELECT * FROM ...");
// foreach ($items as $item) { echo $item->name; }

// DUNG: Chi truyen data san cho template
// Controller:
$items = $this->model->all();
include 'templates/admin/list.php';

// Template chi hien thi:
// <?php foreach ($items as $item) : ?>
//   <tr><td><?php echo esc_html($item->name); ?></td></tr>
// <?php endforeach; ?>
```

---

## Tham khao

- [WordPress Plugin Boilerplate](https://wppb.me/)
- [PSR-4 Autoloading Standard](https://www.php-fig.org/psr/psr-4/)
- [WordPress Coding Standards - PHP](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [PHP Design Patterns](https://designpatternsphp.readthedocs.io/)
