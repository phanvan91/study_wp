# Plugin WordPress Co Ban

## Muc luc

1. [Plugin la gi, tai sao can Plugin](#1-plugin-la-gi-tai-sao-can-plugin)
2. [Plugin Headers day du](#2-plugin-headers-day-du)
3. [Cau truc thu muc Plugin](#3-cau-truc-thu-muc-plugin)
4. [Activation, Deactivation, Uninstall Hooks](#4-activation-deactivation-uninstall-hooks)
5. [Plugin Lifecycle](#5-plugin-lifecycle)
6. [Tao Plugin Hello World dau tien](#6-tao-plugin-hello-world-dau-tien)
7. [So sanh Plugin voi Service Provider trong Laravel](#7-so-sanh-plugin-voi-service-provider-trong-laravel)
8. [Best Practices](#8-best-practices)

---

## 1. Plugin la gi, tai sao can Plugin

### Plugin la gi?

Plugin la mot goi ma nguon (package) mo rong chuc nang cua WordPress ma **khong can chinh sua core**. Plugin hoat dong theo co che **hook** (action va filter) de "cam" (plug) vao he thong WordPress.

### Tai sao can Plugin?

| Ly do | Giai thich |
|-------|-----------|
| **Tach biet code** | Code rieng, khong anh huong theme hay core |
| **Tai su dung** | Dung duoc o nhieu site WordPress khac nhau |
| **Cap nhat doc lap** | Cap nhat plugin khong anh huong phan khac |
| **Cong dong** | Co the chia se len WordPress.org |
| **Bao tri de dang** | Bat/tat plugin de kiem tra loi |

### So sanh nhanh voi Laravel

```
Laravel:   Composer Package / Service Provider  =>  Mo rong ung dung
WordPress: Plugin                                =>  Mo rong website
```

Trong Laravel, ban tao **Service Provider** de dang ky services, routes, views. Trong WordPress, ban tao **Plugin** de dang ky hooks, filters, menus, post types.

---

## 2. Plugin Headers day du

Moi plugin WordPress bat buoc phai co **Plugin Header** - la block comment o dau file chinh cua plugin. WordPress doc comment nay de nhan dien plugin.

### Header toi thieu

```php
<?php
/**
 * Plugin Name: My First Plugin
 */
```

Chi can dong `Plugin Name` la WordPress da nhan dien duoc plugin.

### Header day du tat ca cac truong

```php
<?php
/**
 * Plugin Name:       My Awesome Plugin
 * Plugin URI:        https://example.com/my-awesome-plugin
 * Description:       Mo ta ngan gon ve plugin - hien thi trong trang Plugins.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Phan Van A
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-awesome-plugin
 * Domain Path:       /languages
 * Network:           true
 * Update URI:        https://example.com/updates
 */
```

### Giai thich tung truong

```php
<?php
/**
 * Plugin Name:       Ten plugin - BAT BUOC, hien thi trong admin
 * Plugin URI:        URL trang gioi thieu plugin
 * Description:       Mo ta - hien thi duoi ten plugin trong danh sach
 * Version:           Phien ban hien tai (Semantic Versioning: MAJOR.MINOR.PATCH)
 * Requires at least: Phien ban WordPress toi thieu can de chay plugin
 * Requires PHP:      Phien ban PHP toi thieu
 * Author:            Ten tac gia
 * Author URI:        Website tac gia
 * License:           Giay phep - WordPress yeu cau GPL v2+
 * License URI:       URL cua giay phep
 * Text Domain:       Slug dung cho da ngon ngu (internationalization)
 * Domain Path:       Thu muc chua file ngon ngu (.mo, .po)
 * Network:           true neu plugin chi hoat dong tren Multisite
 * Update URI:        URL kiem tra cap nhat (tu WP 5.8)
 */
```

### Ngan chan truy cap truc tiep

Luon them dong nay ngay sau Plugin Header:

```php
<?php
/**
 * Plugin Name: My Plugin
 */

// Ngan chan truy cap truc tiep vao file plugin
// Neu khong co hang ABSPATH (WordPress dinh nghia), thoat ngay
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Hoac dung: die('Direct access not allowed.');
}
```

**Tai sao can dong nay?** Neu ai do truy cap truc tiep URL `yoursite.com/wp-content/plugins/my-plugin/my-plugin.php`, WordPress chua duoc load nen ABSPATH chua duoc dinh nghia. Dong nay ngan chan viec do.

---

## 3. Cau truc thu muc Plugin

### 3.1. Plugin don file (Single File Plugin)

Phu hop voi plugin nho, don gian.

```
wp-content/
  plugins/
    my-simple-plugin.php    <-- Chi 1 file duy nhat
```

```php
<?php
/**
 * Plugin Name: My Simple Plugin
 * Description: Plugin don gian chi co 1 file.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Tat ca code dat trong file nay
add_action( 'wp_footer', function() {
    echo '<p>Hello tu My Simple Plugin!</p>';
});
```

### 3.2. Plugin da file (Multi-File Plugin)

Phu hop voi plugin trung binh va lon.

```
wp-content/
  plugins/
    my-awesome-plugin/                 <-- Thu muc plugin
      my-awesome-plugin.php            <-- File chinh (cung ten voi thu muc)
      uninstall.php                    <-- Chay khi xoa plugin
      readme.txt                       <-- Mo ta cho WordPress.org

      includes/                        <-- Logic chinh cua plugin
        class-main.php
        class-activator.php
        class-deactivator.php
        functions.php

      admin/                           <-- Code danh cho trang Admin
        class-admin.php
        partials/                      <-- Template admin
          settings-page.php
        css/
          admin-style.css
        js/
          admin-script.js

      public/                          <-- Code danh cho Frontend
        class-public.php
        partials/
          display-template.php
        css/
          public-style.css
        js/
          public-script.js

      languages/                       <-- File ngon ngu
        my-awesome-plugin-vi.po
        my-awesome-plugin-vi.mo

      templates/                       <-- Template co the override tu theme
        single-template.php

      assets/                          <-- Tai nguyen chung
        images/
          icon.png
```

### So sanh cau truc voi Laravel

```
Laravel Package:              WordPress Plugin:
src/                    =>    includes/
resources/views/        =>    admin/partials/, public/partials/
config/                 =>    (Settings API)
routes/                 =>    (Hooks trong file chinh)
public/                 =>    assets/, admin/css/, public/css/
tests/                  =>    tests/
lang/                   =>    languages/
composer.json           =>    readme.txt + Plugin Header
ServiceProvider.php     =>    my-plugin.php (file chinh)
```

### 3.3. Dinh nghia Constants huu ich

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Dinh nghia cac hang so de dung xuyen suot plugin
// __FILE__ tra ve duong dan tuyet doi cua file hien tai
define( 'MAP_VERSION', '1.0.0' );

// Duong dan tuyet doi tren server: /var/www/html/wp-content/plugins/my-awesome-plugin/
define( 'MAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// URL cua plugin: https://example.com/wp-content/plugins/my-awesome-plugin/
define( 'MAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Duong dan file chinh: my-awesome-plugin/my-awesome-plugin.php
define( 'MAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Su dung constants
// require_once MAP_PLUGIN_DIR . 'includes/class-main.php';
// wp_enqueue_style( 'map-style', MAP_PLUGIN_URL . 'public/css/style.css', array(), MAP_VERSION );
```

---

## 4. Activation, Deactivation, Uninstall Hooks

WordPress cung cap 3 hooks quan trong cho vong doi cua plugin:

### 4.1. Activation Hook

Chay **mot lan** khi plugin duoc kich hoat (activate).

```php
<?php
/**
 * Plugin Name: Lifecycle Demo
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ham chay khi kich hoat plugin.
 * Dung de:
 * - Tao bang database
 * - Them default options
 * - Tao trang mac dinh
 * - Dang ky Cron Jobs
 * - Flush rewrite rules (neu tao Custom Post Type)
 */
function lifecycle_demo_activate() {
    // 1. Them option mac dinh
    // add_option chi them neu option chua ton tai (khong ghi de)
    add_option( 'lifecycle_demo_version', '1.0.0' );
    add_option( 'lifecycle_demo_settings', array(
        'enable_feature'  => true,
        'items_per_page'  => 10,
        'display_mode'    => 'grid',
    ));

    // 2. Tao bang database (xem chi tiet o bai Database)
    global $wpdb;
    $table_name = $wpdb->prefix . 'lifecycle_demo_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // 3. Them role hoac capability
    $role = get_role( 'administrator' );
    if ( $role ) {
        $role->add_cap( 'manage_lifecycle_demo' );
    }

    // 4. Flush rewrite rules (neu plugin tao Custom Post Type)
    // Luu y: phai dang ky CPT truoc khi flush
    flush_rewrite_rules();
}

// Dang ky activation hook
// Tham so 1: Duong dan file chinh cua plugin (__FILE__)
// Tham so 2: Ham callback
register_activation_hook( __FILE__, 'lifecycle_demo_activate' );
```

### 4.2. Deactivation Hook

Chay **mot lan** khi plugin bi vo hieu hoa (deactivate).

```php
<?php
/**
 * Ham chay khi vo hieu hoa plugin.
 * Dung de:
 * - Xoa Cron Jobs
 * - Flush rewrite rules
 * - Tam dung cac tinh nang
 * KHONG nen xoa data o day (de danh cho uninstall)
 */
function lifecycle_demo_deactivate() {
    // 1. Xoa cron jobs da dang ky
    $timestamp = wp_next_scheduled( 'lifecycle_demo_daily_event' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'lifecycle_demo_daily_event' );
    }

    // 2. Flush rewrite rules
    flush_rewrite_rules();

    // 3. KHONG xoa data, options, tables o day!
    // Nguoi dung co the chi tam vo hieu hoa roi kich hoat lai
}

register_deactivation_hook( __FILE__, 'lifecycle_demo_deactivate' );
```

### 4.3. Uninstall Hook

Chay khi nguoi dung **xoa** plugin (delete). Co 2 cach:

#### Cach 1: Dung register_uninstall_hook

```php
<?php
/**
 * Ham chay khi xoa plugin.
 * Day la noi don dep toan bo data cua plugin.
 */
function lifecycle_demo_uninstall() {
    // 1. Xoa options
    delete_option( 'lifecycle_demo_version' );
    delete_option( 'lifecycle_demo_settings' );

    // 2. Xoa toan bo post meta lien quan
    // Cach nay xoa tat ca post meta co key bat dau bang '_lifecycle_demo_'
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_lifecycle_demo_%'"
    );

    // 3. Xoa user meta
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_lifecycle_demo_%'"
    );

    // 4. Xoa custom tables
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lifecycle_demo_logs" );

    // 5. Xoa transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lifecycle_demo_%'"
    );
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lifecycle_demo_%'"
    );

    // 6. Xoa cron jobs (phong truong hop)
    wp_clear_scheduled_hook( 'lifecycle_demo_daily_event' );

    // 7. Xoa capabilities
    $role = get_role( 'administrator' );
    if ( $role ) {
        $role->remove_cap( 'manage_lifecycle_demo' );
    }
}

register_uninstall_hook( __FILE__, 'lifecycle_demo_uninstall' );
```

#### Cach 2: Dung file uninstall.php (KHUYEN DUNG)

Tao file `uninstall.php` trong thu muc goc cua plugin:

```php
<?php
/**
 * File: uninstall.php
 *
 * File nay tu dong chay khi plugin bi xoa.
 * WordPress se tu dong tim va chay file nay.
 * Cach nay duoc khuyen dung hon register_uninstall_hook
 * vi khong can load toan bo plugin chi de xoa data.
 */

// Kiem tra xem WordPress co dang thuc su goi file nay khong
// WP_UNINSTALL_PLUGIN duoc WordPress dinh nghia khi xoa plugin
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xoa options
delete_option( 'lifecycle_demo_version' );
delete_option( 'lifecycle_demo_settings' );

// Xoa custom table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lifecycle_demo_logs" );

// Xoa post meta
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_lifecycle_demo_%'"
);

// Xoa user meta
$wpdb->query(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'lifecycle_demo_%'"
);

// Xoa transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_lifecycle_demo_%'"
);
```

### So sanh 2 cach Uninstall

| | register_uninstall_hook | uninstall.php |
|---|---|---|
| **Uu diem** | Code nam chung voi file chinh | Khong can load toan bo plugin |
| **Nhuoc diem** | Phai load plugin truoc | File rieng, de quen |
| **Khuyen dung** | Khong | **Co** |
| **Do uu tien** | Thap hon | Cao hon (chay truoc) |

---

## 5. Plugin Lifecycle

```
                    Plugin Lifecycle

 [Cai dat]  =>  [Kich hoat]  =>  [Chay]  =>  [Vo hieu hoa]  =>  [Xoa]
                     |              |              |                |
            activation_hook    moi request   deactivation_hook  uninstall
            - Tao tables      - Actions      - Xoa cron        - Xoa data
            - Add options     - Filters      - Flush rules     - Xoa tables
            - Flush rules     - Shortcodes                     - Xoa options
                              - Menus
                              - Widgets
```

### Chi tiet Lifecycle

```php
<?php
/**
 * Plugin Name: Lifecycle Visualizer
 * Description: Minh hoa vong doi cua plugin WordPress.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === GIAI DOAN 1: KICH HOAT (Activation) ===
// Chay 1 lan duy nhat khi click "Activate"
register_activation_hook( __FILE__, function() {
    // Setup ban dau
    add_option( 'lvp_installed_at', current_time( 'mysql' ) );
    add_option( 'lvp_version', '1.0.0' );
});

// === GIAI DOAN 2: CHAY (Runtime) ===
// Code duoi day chay MOI KHI WordPress load (moi request)

// plugins_loaded - Hook chay sau khi tat ca plugins da duoc load
add_action( 'plugins_loaded', function() {
    // Kiem tra version de upgrade neu can
    $current_version = get_option( 'lvp_version', '0.0.0' );
    if ( version_compare( $current_version, '1.0.0', '<' ) ) {
        // Chay code upgrade
        update_option( 'lvp_version', '1.0.0' );
    }
});

// init - Hook chay sau khi WordPress khoi tao xong
add_action( 'init', function() {
    // Dang ky Post Types, Taxonomies, Shortcodes
});

// admin_init - Chi chay trong trang Admin
add_action( 'admin_init', function() {
    // Dang ky Settings
});

// admin_menu - Them menu trong Admin
add_action( 'admin_menu', function() {
    // Them menu
});

// wp_enqueue_scripts - Them CSS/JS cho frontend
add_action( 'wp_enqueue_scripts', function() {
    // Them styles va scripts
});

// admin_enqueue_scripts - Them CSS/JS cho admin
add_action( 'admin_enqueue_scripts', function() {
    // Them admin styles va scripts
});

// === GIAI DOAN 3: VO HIEU HOA (Deactivation) ===
// Chay 1 lan khi click "Deactivate"
register_deactivation_hook( __FILE__, function() {
    // Don dep tam thoi, KHONG xoa data
});

// === GIAI DOAN 4: XOA (Uninstall) ===
// Xem file uninstall.php hoac register_uninstall_hook
```

### Thu tu Hooks khi WordPress Load

```
muplugins_loaded        => Must-Use plugins da load
registered_taxonomy      => Taxonomies da dang ky
registered_post_type     => Post types da dang ky
plugins_loaded          => Tat ca plugins da load        <-- Plugin bat dau chay
setup_theme             => Truoc khi load theme
after_setup_theme       => Sau khi load theme
init                    => WordPress da khoi tao xong    <-- Dang ky CPT, Taxonomies
admin_init              => Admin da khoi tao (chi admin) <-- Dang ky Settings
admin_menu              => Tao menu admin
wp_loaded               => WordPress da load hoan tat
template_redirect       => Truoc khi chon template
wp_enqueue_scripts      => Them CSS/JS frontend
wp_head                 => Trong <head>
wp_footer               => Truoc </body>
shutdown                => Ket thuc request
```

---

## 6. Tao Plugin Hello World dau tien

### Buoc 1: Tao thu muc va file

```bash
# Di chuyen den thu muc plugins cua WordPress
cd /path/to/wordpress/wp-content/plugins/

# Tao thu muc plugin
mkdir hello-world-plugin

# Tao file chinh
touch hello-world-plugin/hello-world-plugin.php
```

### Buoc 2: Viet code plugin

```php
<?php
/**
 * Plugin Name:       Hello World Plugin
 * Plugin URI:        https://example.com/hello-world
 * Description:       Plugin dau tien - hien thi thong bao "Hello World" tren website.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Developer Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * Text Domain:       hello-world-plugin
 */

// Ngan chan truy cap truc tiep
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === DINH NGHIA CONSTANTS ===
define( 'HWP_VERSION', '1.0.0' );
define( 'HWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// === ACTIVATION HOOK ===
register_activation_hook( __FILE__, 'hwp_activate' );

function hwp_activate() {
    // Luu thoi gian cai dat
    add_option( 'hwp_installed_at', current_time( 'mysql' ) );
    // Luu cai dat mac dinh
    add_option( 'hwp_settings', array(
        'message'    => 'Hello World!',
        'text_color' => '#ffffff',
        'bg_color'   => '#0073aa',
        'position'   => 'bottom',
        'enabled'    => true,
    ));
}

// === DEACTIVATION HOOK ===
register_deactivation_hook( __FILE__, 'hwp_deactivate' );

function hwp_deactivate() {
    // Khong xoa data, chi don dep tam thoi
}

// === THEM MENU ADMIN ===
add_action( 'admin_menu', 'hwp_add_admin_menu' );

function hwp_add_admin_menu() {
    // Them menu trong Admin sidebar
    add_options_page(
        'Hello World Settings',      // Tieu de trang
        'Hello World',               // Ten menu
        'manage_options',            // Quyen can thiet (admin)
        'hello-world-settings',      // Slug (URL)
        'hwp_settings_page'          // Ham hien thi trang
    );
}

// === TRANG SETTINGS ===
function hwp_settings_page() {
    // Kiem tra quyen
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Xu ly form khi submit
    if ( isset( $_POST['hwp_save_settings'] ) ) {
        // Kiem tra nonce (bao mat chong CSRF)
        check_admin_referer( 'hwp_settings_nonce' );

        // Lay va lam sach du lieu
        $settings = array(
            'message'    => sanitize_text_field( $_POST['hwp_message'] ?? '' ),
            'text_color' => sanitize_hex_color( $_POST['hwp_text_color'] ?? '#ffffff' ),
            'bg_color'   => sanitize_hex_color( $_POST['hwp_bg_color'] ?? '#0073aa' ),
            'position'   => sanitize_text_field( $_POST['hwp_position'] ?? 'bottom' ),
            'enabled'    => isset( $_POST['hwp_enabled'] ) ? true : false,
        );

        // Luu vao database
        update_option( 'hwp_settings', $settings );

        // Hien thi thong bao thanh cong
        echo '<div class="notice notice-success"><p>Da luu cai dat!</p></div>';
    }

    // Lay cai dat hien tai
    $settings = get_option( 'hwp_settings', array() );
    $defaults = array(
        'message'    => 'Hello World!',
        'text_color' => '#ffffff',
        'bg_color'   => '#0073aa',
        'position'   => 'bottom',
        'enabled'    => true,
    );
    $settings = wp_parse_args( $settings, $defaults );
    ?>
    <div class="wrap">
        <h1>Hello World Plugin - Cai dat</h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'hwp_settings_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hwp_enabled">Bat/Tat</label>
                    </th>
                    <td>
                        <input type="checkbox"
                               id="hwp_enabled"
                               name="hwp_enabled"
                               value="1"
                               <?php checked( $settings['enabled'], true ); ?>>
                        <label for="hwp_enabled">Hien thi thong bao tren website</label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="hwp_message">Noi dung thong bao</label>
                    </th>
                    <td>
                        <input type="text"
                               id="hwp_message"
                               name="hwp_message"
                               value="<?php echo esc_attr( $settings['message'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="hwp_text_color">Mau chu</label>
                    </th>
                    <td>
                        <input type="color"
                               id="hwp_text_color"
                               name="hwp_text_color"
                               value="<?php echo esc_attr( $settings['text_color'] ); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="hwp_bg_color">Mau nen</label>
                    </th>
                    <td>
                        <input type="color"
                               id="hwp_bg_color"
                               name="hwp_bg_color"
                               value="<?php echo esc_attr( $settings['bg_color'] ); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="hwp_position">Vi tri</label>
                    </th>
                    <td>
                        <select id="hwp_position" name="hwp_position">
                            <option value="top" <?php selected( $settings['position'], 'top' ); ?>>
                                Tren cung
                            </option>
                            <option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>>
                                Duoi cung
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Luu cai dat', 'primary', 'hwp_save_settings' ); ?>
        </form>

        <hr>
        <h3>Shortcode</h3>
        <p>Su dung shortcode <code>[hello_world]</code> de hien thi thong bao trong bai viet.</p>
        <p>Hoac: <code>[hello_world message="Custom message" color="#ff0000"]</code></p>
    </div>
    <?php
}

// === HIEN THI THONG BAO TREN FRONTEND ===
add_action( 'wp_footer', 'hwp_display_message' );

function hwp_display_message() {
    $settings = get_option( 'hwp_settings', array() );

    // Kiem tra xem co bat tinh nang khong
    if ( empty( $settings['enabled'] ) ) {
        return;
    }

    $message    = esc_html( $settings['message'] ?? 'Hello World!' );
    $text_color = esc_attr( $settings['text_color'] ?? '#ffffff' );
    $bg_color   = esc_attr( $settings['bg_color'] ?? '#0073aa' );
    $position   = $settings['position'] ?? 'bottom';

    // Xac dinh vi tri CSS
    $pos_style = ( $position === 'top' ) ? 'top: 0;' : 'bottom: 0;';

    ?>
    <div id="hwp-message" style="
        position: fixed;
        <?php echo $pos_style; ?>
        left: 0;
        right: 0;
        background: <?php echo $bg_color; ?>;
        color: <?php echo $text_color; ?>;
        text-align: center;
        padding: 15px;
        font-size: 16px;
        z-index: 99999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    ">
        <?php echo $message; ?>
        <button onclick="this.parentElement.style.display='none'" style="
            background: none;
            border: 1px solid <?php echo $text_color; ?>;
            color: <?php echo $text_color; ?>;
            padding: 2px 10px;
            margin-left: 15px;
            cursor: pointer;
            border-radius: 3px;
        ">X</button>
    </div>
    <?php
}

// === DANG KY SHORTCODE ===
add_shortcode( 'hello_world', 'hwp_shortcode' );

function hwp_shortcode( $atts ) {
    // Gop thuoc tinh mac dinh voi thuoc tinh nguoi dung truyen vao
    $atts = shortcode_atts( array(
        'message' => 'Hello World!',
        'color'   => '#0073aa',
    ), $atts, 'hello_world' );

    // Tra ve HTML (shortcode PHAI return, KHONG echo)
    return sprintf(
        '<div class="hwp-inline" style="background:%s; color:#fff; padding:10px; border-radius:5px; margin:10px 0;">%s</div>',
        esc_attr( $atts['color'] ),
        esc_html( $atts['message'] )
    );
}

// === THEM LINK SETTINGS TREN TRANG PLUGINS ===
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hwp_action_links' );

function hwp_action_links( $links ) {
    // Them link "Cai dat" ben canh "Deactivate"
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=hello-world-settings' ) . '">Cai dat</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
```

### Buoc 3: Tao file uninstall.php

```php
<?php
/**
 * File: hello-world-plugin/uninstall.php
 * Chay khi plugin bi xoa
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xoa tat ca options cua plugin
delete_option( 'hwp_installed_at' );
delete_option( 'hwp_settings' );
```

### Buoc 4: Kich hoat va test

1. Truy cap **WordPress Admin > Plugins**
2. Tim "Hello World Plugin" trong danh sach
3. Click **Activate**
4. Vao **Settings > Hello World** de cau hinh
5. Xem frontend - thong bao se hien thi o cuoi trang
6. Thu shortcode `[hello_world]` trong bai viet

---

## 7. So sanh Plugin voi Service Provider trong Laravel

### Tuong dong

```php
<?php
// === LARAVEL: Service Provider ===

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MyFeatureServiceProvider extends ServiceProvider
{
    /**
     * Dang ky services - giong nhu activation hook
     */
    public function register()
    {
        // Bind services vao container
        $this->app->singleton('myfeature', function() {
            return new MyFeatureService();
        });

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/myfeature.php', 'myfeature'
        );
    }

    /**
     * Bootstrap - giong nhu cac hooks init, admin_init
     */
    public function boot()
    {
        // Load routes - giong admin_menu
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Load views - giong admin/partials
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'myfeature');

        // Load migrations - giong dbDelta trong activation hook
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish assets - giong wp_enqueue_scripts
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/myfeature'),
        ], 'public');
    }
}

// === WORDPRESS: Plugin tuong duong ===

/**
 * Plugin Name: My Feature Plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// register() tuong duong => activation hook
register_activation_hook( __FILE__, function() {
    // Tao tables (giong migrations)
    // Add options (giong config)
});

// boot() tuong duong => cac hooks
add_action( 'init', function() {
    // Dang ky Post Types (giong Route::resource)
    // Dang ky Taxonomies
});

add_action( 'admin_menu', function() {
    // Them menu (giong routes/web.php)
});

add_action( 'wp_enqueue_scripts', function() {
    // Them CSS/JS (giong publishes assets)
});
```

### Bang so sanh chi tiet

| Khai niem | Laravel | WordPress Plugin |
|-----------|---------|-----------------|
| **Khoi tao** | `register()` | `register_activation_hook()` |
| **Bootstrap** | `boot()` | `plugins_loaded`, `init` hooks |
| **Config** | `config/app.php` | `get_option()`, `update_option()` |
| **Routes** | `routes/web.php` | `add_menu_page()`, `register_rest_route()` |
| **Controllers** | `App\Http\Controllers` | Callback functions / Classes |
| **Views** | Blade templates | PHP templates trong `partials/` |
| **Models** | Eloquent Models | `$wpdb` queries / WP_Query |
| **Migrations** | Migration files | `dbDelta()` trong activation hook |
| **Middleware** | Middleware classes | `current_user_can()`, nonce checks |
| **Events** | Events & Listeners | Actions & Filters (hooks) |
| **Service Container** | `app()->make()` | Khong co (tu quan ly) |
| **Artisan CLI** | `php artisan` | `WP-CLI` |
| **Package Discovery** | `composer.json` extra | Plugin Header comment |
| **Uninstall** | Khong co san | `uninstall.php` |

### Diem khac biet lon

```php
<?php
// Laravel: Dependency Injection tu dong
class UserController extends Controller
{
    // Laravel tu dong inject UserService
    public function __construct(
        private UserService $userService
    ) {}
}

// WordPress: Phai tu quan ly dependencies
class My_Plugin_Controller {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb; // Tu lay dependency
    }
}
```

```php
<?php
// Laravel: Eloquent ORM
$users = User::where('status', 'active')
             ->orderBy('name')
             ->paginate(10);

// WordPress: $wpdb
global $wpdb;
$users = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}users
         WHERE status = %s
         ORDER BY display_name
         LIMIT %d OFFSET %d",
        'active',
        10,
        0
    )
);
```

---

## 8. Best Practices

### Dat ten (Naming)

```php
<?php
// 1. Dung prefix duy nhat cho tat ca functions, classes, constants
// Tranh trung ten voi plugin khac

// SAI - de trung ten
function get_settings() { }
class Admin { }
define( 'VERSION', '1.0.0' );

// DUNG - co prefix
function myplugin_get_settings() { }
class MyPlugin_Admin { }
define( 'MYPLUGIN_VERSION', '1.0.0' );

// TOT NHAT - dung Namespace (PHP 5.6+)
namespace MyPlugin;
class Admin { }
function get_settings() { }
```

### Bao mat

```php
<?php
// 1. Luon kiem tra ABSPATH
if ( ! defined( 'ABSPATH' ) ) exit;

// 2. Luon kiem tra quyen
if ( ! current_user_can( 'manage_options' ) ) return;

// 3. Luon dung nonce
wp_nonce_field( 'my_action', 'my_nonce' );
// Kiem tra: check_admin_referer( 'my_action', 'my_nonce' );

// 4. Luon sanitize input
$clean = sanitize_text_field( $_POST['field'] );

// 5. Luon escape output
echo esc_html( $value );
echo esc_attr( $attribute );
echo esc_url( $url );
```

### Performance

```php
<?php
// 1. Chi load code khi can thiet
if ( is_admin() ) {
    require_once MAP_PLUGIN_DIR . 'admin/class-admin.php';
} else {
    require_once MAP_PLUGIN_DIR . 'public/class-public.php';
}

// 2. Chi load CSS/JS tren trang can
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Chi load tren trang settings cua plugin
    if ( $hook !== 'settings_page_my-plugin' ) {
        return;
    }
    wp_enqueue_style( 'my-plugin-admin', MAP_PLUGIN_URL . 'admin/css/admin.css' );
});

// 3. Dung Transients de cache du lieu
$data = get_transient( 'my_plugin_data' );
if ( false === $data ) {
    $data = expensive_query(); // Query nang
    set_transient( 'my_plugin_data', $data, HOUR_IN_SECONDS );
}
```

### Checklist truoc khi phat hanh

- [ ] Co Plugin Header day du
- [ ] Co file `uninstall.php`
- [ ] Tat ca input duoc sanitize
- [ ] Tat ca output duoc escape
- [ ] Dung Nonces cho moi form va AJAX
- [ ] Kiem tra quyen nguoi dung
- [ ] Dung `$wpdb->prepare()` cho moi query
- [ ] Co Text Domain cho da ngon ngu
- [ ] Khong co `error_log()`, `var_dump()`, `print_r()` trong code production
- [ ] Khong hardcode duong dan (dung `plugin_dir_path()`, `plugin_dir_url()`)
- [ ] Tuong thich voi phien ban PHP va WordPress yeu cau

---

## Tham khao

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Header Requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [Activation/Deactivation Hooks](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/)
- [Plugin Security](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
