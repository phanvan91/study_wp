# 07 - Quản Lý Plugin trong WordPress Admin

> Tài liệu dành cho PHP Laravel developer chuyển sang WordPress.
> Phân tích chi tiết plugin management, activation flow, mu-plugins, drop-ins, recovery mode, hooks và cách lưu DB.

---

## Mục Lục

1. [Tổng Quan Plugins Management](#1-tổng-quan-plugins-management)
2. [Plugins List Screen (plugins.php)](#2-plugins-list-screen-pluginsphp)
3. [Plugin Groups và Tabs](#3-plugin-groups-và-tabs)
4. [Activation Flow Chi Tiết](#4-activation-flow-chi-tiết)
5. [Deactivation Flow](#5-deactivation-flow)
6. [Delete Plugin Flow](#6-delete-plugin-flow)
7. [Install Plugins (plugin-install.php)](#7-install-plugins-plugin-installphp)
8. [Plugin Headers](#8-plugin-headers)
9. [get_plugin_data() - Đọc Plugin Metadata](#9-get_plugin_data---đọc-plugin-metadata)
10. [Must-Use Plugins (mu-plugins)](#10-must-use-plugins-mu-plugins)
11. [Drop-ins](#11-drop-ins)
12. [Plugin File Editor](#12-plugin-file-editor)
13. [Auto-Updates](#13-auto-updates)
14. [Plugin Dependencies (WP 6.5+)](#14-plugin-dependencies-wp-65)
15. [DB: Plugins Lưu Gì?](#15-db-plugins-lưu-gì)
16. [Hooks Plugins - Danh Sách Đầy Đủ](#16-hooks-plugins---danh-sách-đầy-đủ)
17. [Recovery Mode (WP 5.2+)](#17-recovery-mode-wp-52)
18. [Uninstall Hooks](#18-uninstall-hooks)
19. [Ví Dụ Thực Tế: Plugin Skeleton](#19-ví-dụ-thực-tế-plugin-skeleton)
20. [So Sánh Với Laravel](#20-so-sánh-với-laravel)
21. [Tổng Kết](#21-tổng-kết)

---

## 1. Tổng Quan Plugins Management

### URLs Admin

| Trang | URL | Mô tả |
|-------|-----|-------|
| Plugins List | `/wp-admin/plugins.php` | Danh sách tất cả plugins |
| Install Plugins | `/wp-admin/plugin-install.php` | Cài đặt plugin mới |
| Plugin Editor | `/wp-admin/plugin-editor.php` | Sửa file plugin |
| Update Plugins | `/wp-admin/update.php?action=install-plugin` | Cập nhật plugin |

### Source Files Chính

```
wp-admin/
├── plugins.php                                    # Plugin list & actions
├── plugin-install.php                             # Install from repo
├── plugin-editor.php                              # Plugin file editor
├── update.php                                     # Update handler
├── includes/
│   ├── plugin.php                                 # Plugin management API (~2671 dòng)
│   ├── class-wp-plugins-list-table.php            # List table class (~1661 dòng)
│   ├── plugin-install.php                         # Install API
│   └── class-plugin-upgrader.php                  # Upgrader class
wp-includes/
├── plugin.php                                     # Plugin API (hooks, filters)
├── class-wp-hook.php                              # Hook system implementation
└── option.php                                     # Options API (lưu active plugins)
```

### Capability (Quyền)

```php
// Source: wp-admin/plugins.php dòng 12-14
if ( ! current_user_can( 'activate_plugins' ) ) {
    wp_die( __( 'Sorry, you are not allowed to manage plugins for this site.' ) );
}
```

Capabilities liên quan:
- `activate_plugins` - Activate/deactivate plugins
- `install_plugins` - Cài đặt plugins mới
- `update_plugins` - Cập nhật plugins
- `delete_plugins` - Xóa plugins
- `edit_plugins` - Sửa file plugin
- `manage_network_plugins` - Quản lý plugins trên multisite

---

## 2. Plugins List Screen (plugins.php)

**Source**: `wp-admin/plugins.php`

### Flow khởi tạo

```php
// Source: wp-admin/plugins.php dòng 10-43
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'activate_plugins' ) ) {
    wp_die( __( 'Sorry, you are not allowed to manage plugins for this site.' ) );
}

$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
$pagenum       = $wp_list_table->get_pagenum();
$action        = $wp_list_table->current_action();
$plugin        = isset( $_REQUEST['plugin'] ) ? wp_unslash( $_REQUEST['plugin'] ) : '';

// Enqueue update script
wp_enqueue_script( 'updates' );

// Initialize Plugin Dependencies (WP 6.5+)
WP_Plugin_Dependencies::initialize();
```

### WP_Plugins_List_Table

**Source**: `wp-admin/includes/class-wp-plugins-list-table.php`

```php
// Columns
public function get_columns() {
    return array(
        'cb'          => '<input type="checkbox" />',
        'name'        => __( 'Plugin' ),
        'description' => __( 'Description' ),
    );
}

// Mỗi row hiển thị:
// - Plugin name (linked)
// - Version | By Author | View Details
// - Description
// - Action links: Activate | Edit | Delete
// - Auto-update toggle
```

---

## 3. Plugin Groups và Tabs

### Các nhóm plugin

| Tab | Mô tả | Filter condition |
|-----|--------|-----------------|
| All | Tất cả plugins | Không filter |
| Active | Đang active | `is_plugin_active($plugin)` |
| Inactive | Chưa active | `!is_plugin_active($plugin)` |
| Recently Active | Vừa deactivate gần đây | Có trong option `recently_activated` |
| Must-Use | Must-use plugins | Thư mục `wp-content/mu-plugins/` |
| Drop-ins | Drop-in plugins | Files đặc biệt trong `wp-content/` |
| Paused | Plugins bị tạm dừng do lỗi | Recovery mode |
| Auto-updates Enabled | Đã bật auto-update | Có trong option `auto_update_plugins` |
| Auto-updates Disabled | Chưa bật auto-update | Không có trong auto_update_plugins |

### Filter plugin status

```php
// Source: wp-admin/includes/class-wp-plugins-list-table.php
// Tab views được tạo dựa trên đếm:
$plugins = array(
    'all'                => $all_plugins,
    'search'             => $search_plugins,
    'active'             => $active_plugins,
    'inactive'           => $inactive_plugins,
    'recently_activated' => $recently_activated,
    'upgrade'            => $upgrade_plugins,
    'mustuse'            => $mustuse_plugins,
    'dropins'            => $dropin_plugins,
    'paused'             => $paused_plugins,
    'auto-update-enabled'  => $auto_update_enabled,
    'auto-update-disabled' => $auto_update_disabled,
);
```

---

## 4. Activation Flow Chi Tiết

**Source**: `wp-admin/includes/plugin.php` dòng 641

### Trong plugins.php - Trigger activate

```php
// Source: wp-admin/plugins.php dòng 47-90
case 'activate':
    if ( ! current_user_can( 'activate_plugin', $plugin ) ) {
        wp_die( __( 'Sorry, you are not allowed to activate this plugin.' ) );
    }

    // Multisite: kiểm tra network-only plugin
    if ( is_multisite() && ! is_network_admin() && is_network_only_plugin( $plugin ) ) {
        wp_redirect( self_admin_url( "plugins.php?plugin_status=$status&paged=$page&s=$s" ) );
        exit;
    }

    check_admin_referer( 'activate-plugin_' . $plugin );

    // Gọi hàm activate_plugin()
    $result = activate_plugin(
        $plugin,
        self_admin_url( 'plugins.php?error=true&plugin=' . urlencode( $plugin ) ),
        is_network_admin()
    );

    if ( is_wp_error( $result ) ) {
        // Xử lý lỗi: unexpected_output, v.v.
        if ( 'unexpected_output' === $result->get_error_code() ) {
            $redirect = self_admin_url( 'plugins.php?error=true&charsout=' . strlen( $result->get_error_data() ) . '&plugin=' . urlencode( $plugin ) );
            wp_redirect( add_query_arg( '_error_nonce', wp_create_nonce( 'plugin-activation-error_' . $plugin ), $redirect ) );
            exit;
        } else {
            wp_die( $result );
        }
    }

    // Xóa khỏi recently_activated
    $recent = (array) get_option( 'recently_activated' );
    unset( $recent[ $plugin ] );
    update_option( 'recently_activated', $recent, false );

    wp_redirect( self_admin_url( "plugins.php?activate=true&plugin_status=$status" ) );
    exit;
```

### Hàm activate_plugin() - Core

```php
// Source: wp-admin/includes/plugin.php dòng 641-740+
function activate_plugin( $plugin, $redirect = '', $network_wide = false, $silent = false ) {
    // Chuẩn hóa plugin path
    $plugin = plugin_basename( trim( $plugin ) );

    // Xác định single-site hay network-wide
    if ( is_multisite() && ( $network_wide || is_network_only_plugin( $plugin ) ) ) {
        $network_wide = true;
        $current      = get_site_option( 'active_sitewide_plugins', array() );
    } else {
        $current = get_option( 'active_plugins', array() );
    }

    // BƯỚC 1: Validate plugin file tồn tại
    $valid = validate_plugin( $plugin );
    if ( is_wp_error( $valid ) ) {
        return $valid;
    }

    // BƯỚC 2: Validate requirements (WP version, PHP version)
    $requirements = validate_plugin_requirements( $plugin );
    if ( is_wp_error( $requirements ) ) {
        return $requirements;
    }

    // BƯỚC 3: Kiểm tra đã active chưa
    if ( $network_wide && ! isset( $current[ $plugin ] )
        || ! $network_wide && ! in_array( $plugin, $current, true )
    ) {
        // Nếu có redirect URL, set nó (sẽ override nếu plugin ok)
        if ( ! empty( $redirect ) ) {
            wp_redirect( add_query_arg( '_error_nonce',
                wp_create_nonce( 'plugin-activation-error_' . $plugin ), $redirect ) );
        }

        // BƯỚC 4: Sandbox - Load plugin lần đầu để test
        ob_start();
        plugin_sandbox_scrape( $plugin );
        // → Nếu plugin có fatal error, execution dừng ở đây
        // → redirect URL ở trên sẽ được sử dụng

        // BƯỚC 5: Fire activation hooks
        if ( ! $silent ) {
            /**
             * Fires trước khi plugin được activate
             * @param string $plugin       Plugin path relative to plugins dir
             * @param bool   $network_wide Network activate hay không
             */
            do_action( 'activate_plugin', $plugin, $network_wide );

            /**
             * Fires cho plugin cụ thể (dùng bởi register_activation_hook)
             * Dynamic hook name: activate_{$plugin}
             * Ví dụ: activate_my-plugin/my-plugin.php
             */
            do_action( "activate_{$plugin}", $network_wide );
        }

        // BƯỚC 6: Cập nhật danh sách active plugins trong DB
        if ( $network_wide ) {
            $current            = get_site_option( 'active_sitewide_plugins', array() );
            $current[ $plugin ] = time();
            update_site_option( 'active_sitewide_plugins', $current );
        } else {
            $current   = get_option( 'active_plugins', array() );
            $current[] = $plugin;
            sort( $current );
            update_option( 'active_plugins', $current );
        }

        // BƯỚC 7: Fire post-activation hook
        if ( ! $silent ) {
            /**
             * Fires sau khi plugin được activate thành công
             * @param string $plugin       Plugin path
             * @param bool   $network_wide Network activate
             */
            do_action( 'activated_plugin', $plugin, $network_wide );
        }

        // Kiểm tra output bất thường
        if ( ob_get_length() > 0 ) {
            $output = ob_get_clean();
            return new WP_Error( 'unexpected_output', __( 'The plugin generated unexpected output.' ), $output );
        }
        ob_end_clean();
    }

    return null; // Thành công
}
```

### Sơ đồ activation

```
activate_plugin( 'my-plugin/my-plugin.php' )
    │
    ├── validate_plugin() → Kiểm tra file tồn tại
    │   └── Plugin file: wp-content/plugins/my-plugin/my-plugin.php
    │
    ├── validate_plugin_requirements() → Check WP/PHP version
    │   └── Đọc "Requires at least" và "Requires PHP" headers
    │
    ├── plugin_sandbox_scrape() → Include plugin file lần đầu
    │   └── Nếu fatal error → redirect tới error page
    │
    ├── do_action( 'activate_plugin', $plugin, $network_wide )
    │   └── Hook chung cho mọi plugin activation
    │
    ├── do_action( 'activate_my-plugin/my-plugin.php', $network_wide )
    │   └── Hook riêng cho plugin cụ thể (register_activation_hook)
    │
    ├── update_option( 'active_plugins', [..., 'my-plugin/my-plugin.php'] )
    │   └── Lưu vào wp_options
    │
    └── do_action( 'activated_plugin', $plugin, $network_wide )
        └── Hook sau khi activate xong
```

### register_activation_hook()

```php
// Trong file chính của plugin: my-plugin/my-plugin.php

// Cách 1: Dùng register_activation_hook
register_activation_hook( __FILE__, 'my_plugin_activate' );

function my_plugin_activate() {
    // Tạo bảng database
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}my_plugin_data (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        value longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Set default options
    add_option( 'my_plugin_version', '1.0.0' );
    add_option( 'my_plugin_settings', array(
        'enabled' => true,
        'api_key' => '',
    ) );

    // Flush rewrite rules (nếu plugin đăng ký custom post type)
    flush_rewrite_rules();
}

// Cách 2: Dùng hook trực tiếp
add_action( 'activate_my-plugin/my-plugin.php', function( $network_wide ) {
    // Code khi activate
});
```

---

## 5. Deactivation Flow

**Source**: `wp-admin/includes/plugin.php` dòng 758

```php
function deactivate_plugins( $plugins, $silent = false, $network_wide = null ) {
    if ( is_multisite() ) {
        $network_current = get_site_option( 'active_sitewide_plugins', array() );
    }
    $current = get_option( 'active_plugins', array() );

    foreach ( (array) $plugins as $plugin ) {
        $plugin = plugin_basename( trim( $plugin ) );

        if ( ! is_plugin_active( $plugin ) ) {
            continue;
        }

        $network_deactivating = ( false !== $network_wide ) && is_plugin_active_for_network( $plugin );

        if ( ! $silent ) {
            /**
             * Fires trước khi deactivate
             */
            do_action( 'deactivate_plugin', $plugin, $network_deactivating );
        }

        // Xóa khỏi danh sách active
        if ( true !== $network_wide ) {
            $key = array_search( $plugin, $current, true );
            if ( false !== $key ) {
                unset( $current[ $key ] );
            }
        }

        // Nếu đang trong recovery mode, xóa paused state
        if ( wp_is_recovery_mode() ) {
            list( $extension ) = explode( '/', $plugin );
            wp_paused_plugins()->delete( $extension );
        }

        if ( ! $silent ) {
            /**
             * Hook riêng cho plugin (register_deactivation_hook)
             */
            do_action( "deactivate_{$plugin}", $network_deactivating );
        }
    }

    // Cập nhật DB
    update_option( 'active_plugins', array_values( $current ) );

    if ( ! $silent ) {
        /**
         * Fires sau khi deactivate thành công
         */
        do_action( 'deactivated_plugin', $plugin, $network_deactivating );
    }
}
```

### register_deactivation_hook()

```php
register_deactivation_hook( __FILE__, 'my_plugin_deactivate' );

function my_plugin_deactivate() {
    // Xóa scheduled cron events
    $timestamp = wp_next_scheduled( 'my_plugin_cron_event' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'my_plugin_cron_event' );
    }

    // Flush rewrite rules
    flush_rewrite_rules();

    // KHÔNG xóa data ở đây! Chỉ xóa khi uninstall
    // User có thể muốn activate lại sau
}
```

---

## 6. Delete Plugin Flow

### Trong plugins.php

```php
// Source: wp-admin/plugins.php
case 'delete-selected':
    if ( ! current_user_can( 'delete_plugins' ) ) {
        wp_die( __( 'Sorry, you are not allowed to delete plugins for this site.' ) );
    }

    check_admin_referer( 'bulk-plugins' );

    $plugins = isset( $_REQUEST['checked'] ) ? (array) wp_unslash( $_REQUEST['checked'] ) : array();

    // Không cho xóa plugin đang active
    $active = get_option( 'active_plugins', array() );
    foreach ( $plugins as $i => $plugin ) {
        if ( in_array( $plugin, $active, true ) ) {
            unset( $plugins[ $i ] );
        }
    }

    // Hiển thị trang xác nhận
    // User phải confirm trước khi xóa
```

### Hàm delete_plugins()

```php
function delete_plugins( $plugins, $deprecated = '' ) {
    // Kiểm tra filesystem
    $checked = array();
    foreach ( $plugins as $plugin ) {
        // Gọi uninstall hook nếu có
        // Source: wp-admin/includes/plugin.php
        $uninstallable_plugins = (array) get_option( 'uninstall_plugins' );

        if ( isset( $uninstallable_plugins[ $plugin ] ) ) {
            // Gọi uninstall callback
            $callable = $uninstallable_plugins[ $plugin ];
            if ( is_callable( $callable ) ) {
                call_user_func( $callable );
            }
        } elseif ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $plugin ) . '/uninstall.php' ) ) {
            // Hoặc include uninstall.php
            include WP_PLUGIN_DIR . '/' . dirname( $plugin ) . '/uninstall.php';
        }

        // Xóa files
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin );
        $deleted    = $wp_filesystem->delete( $plugin_dir, true ); // recursive

        if ( $deleted ) {
            // Xóa khỏi uninstall list
            unset( $uninstallable_plugins[ $plugin ] );
            update_option( 'uninstall_plugins', $uninstallable_plugins );

            /**
             * Fires sau khi xóa plugin
             */
            do_action( 'deleted_plugin', $plugin, $deleted );
        }
    }
}
```

---

## 7. Install Plugins (plugin-install.php)

**Source**: `wp-admin/plugin-install.php`

### Tabs cài đặt

```php
// Tabs
$tabs = array(
    'featured'    => _x( 'Featured', 'Plugin Installer' ),
    'popular'     => _x( 'Popular', 'Plugin Installer' ),
    'recommended' => _x( 'Recommended', 'Plugin Installer' ),
    'favorites'   => _x( 'Favorites', 'Plugin Installer' ),
    'beta'        => false, // Beta testing (ẩn)
);

// Filter tabs
$tabs = apply_filters( 'install_plugins_tabs', $tabs );
```

### Search API

```php
// Query WordPress.org Plugin API
$api = plugins_api( 'query_plugins', array(
    'page'     => 1,
    'per_page' => 30,
    'search'   => 'seo',
    'tag'      => 'seo',
    'author'   => 'developer-name',
) );

// URL API: https://api.wordpress.org/plugins/info/1.2/
// Trả về: name, slug, version, author, rating, active_installs, v.v.
```

### Upload Plugin .zip

```php
// URL: /wp-admin/update.php?action=upload-plugin
// Form upload .zip file

// Sử dụng Plugin_Upgrader
$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin() );
$result   = $upgrader->install( $package );

// Flow:
// 1. Download .zip
// 2. Extract tới wp-content/plugins/
// 3. Verify plugin headers
// 4. Hiển thị kết quả
```

### Install flow

```
Plugin_Upgrader::install( $package_url )
    │
    ├── download_package() → Tải .zip về temp
    │
    ├── unpack_package() → Giải nén
    │
    ├── install_package()
    │   ├── Kiểm tra destination: wp-content/plugins/
    │   ├── Copy files
    │   └── Clear plugin cache
    │
    └── Hiển thị "Plugin installed successfully. Activate Plugin"
```

---

## 8. Plugin Headers

Mỗi plugin cần file chính với comment header:

```php
<?php
/**
 * Plugin Name:       My Plugin
 * Plugin URI:        https://example.com/my-plugin
 * Description:       Mô tả ngắn gọn về plugin.
 * Version:           1.0.0
 * Author:            Tên Tác Giả
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-plugin
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Network:           true
 * Update URI:        https://example.com/my-plugin/
 * Requires Plugins:  woocommerce, advanced-custom-fields
 */
```

### Chi tiết headers

| Header | Bắt buộc | Mô tả |
|--------|----------|-------|
| `Plugin Name` | Có | Tên plugin, hiển thị trong admin |
| `Plugin URI` | Không | URL trang web plugin |
| `Description` | Không | Mô tả ngắn (1 dòng) |
| `Version` | Khuyến nghị | Phiên bản hiện tại |
| `Author` | Không | Tên tác giả |
| `Author URI` | Không | URL tác giả |
| `License` | Khuyến nghị | Giấy phép (GPL v2+) |
| `Text Domain` | Khuyến nghị | Cho internationalization |
| `Domain Path` | Không | Đường dẫn tới .mo files |
| `Requires at least` | Khuyến nghị | WordPress version tối thiểu |
| `Requires PHP` | Khuyến nghị | PHP version tối thiểu |
| `Network` | Không | `true` = chỉ activate network-wide |
| `Update URI` | Không | URI cho custom update server |
| `Requires Plugins` | Không | Comma-separated plugin slugs (WP 6.5+) |

---

## 9. get_plugin_data() - Đọc Plugin Metadata

**Source**: `wp-admin/includes/plugin.php` dòng 74

```php
function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {
    $default_headers = array(
        'Name'            => 'Plugin Name',
        'PluginURI'       => 'Plugin URI',
        'Version'         => 'Version',
        'Description'     => 'Description',
        'Author'          => 'Author',
        'AuthorURI'       => 'Author URI',
        'TextDomain'      => 'Text Domain',
        'DomainPath'      => 'Domain Path',
        'Network'         => 'Network',
        'RequiresWP'      => 'Requires at least',
        'RequiresPHP'     => 'Requires PHP',
        'UpdateURI'       => 'Update URI',
        'RequiresPlugins' => 'Requires Plugins',
    );

    // Đọc 8KB đầu tiên của file
    $plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

    // Nếu không có TextDomain, dùng plugin slug
    if ( ! $plugin_data['TextDomain'] ) {
        $plugin_slug = dirname( plugin_basename( $plugin_file ) );
        if ( '.' !== $plugin_slug && ! str_contains( $plugin_slug, '/' ) ) {
            $plugin_data['TextDomain'] = $plugin_slug;
        }
    }

    return $plugin_data;
}
```

### Sử dụng

```php
// Đọc data plugin
$data = get_plugin_data( WP_PLUGIN_DIR . '/my-plugin/my-plugin.php' );
/*
Array (
    'Name'            => 'My Plugin',
    'PluginURI'       => 'https://example.com/my-plugin',
    'Version'         => '1.0.0',
    'Description'     => 'Mô tả plugin',
    'Author'          => 'Tác Giả',
    'AuthorURI'       => 'https://example.com',
    'TextDomain'      => 'my-plugin',
    'DomainPath'      => '/languages',
    'Network'         => false,
    'RequiresWP'      => '6.0',
    'RequiresPHP'     => '7.4',
    'Title'           => 'My Plugin',
    'AuthorName'      => 'Tác Giả',
)
*/

// Lấy danh sách tất cả plugins
$all_plugins = get_plugins(); // Đọc headers của tất cả plugin files
/*
Array (
    'my-plugin/my-plugin.php' => array( 'Name' => 'My Plugin', ... ),
    'akismet/akismet.php'     => array( 'Name' => 'Akismet', ... ),
)
*/
```

---

## 10. Must-Use Plugins (mu-plugins)

### Thư mục

```
wp-content/mu-plugins/
├── my-mu-plugin.php        # Auto-loaded
├── another-mu-plugin.php   # Auto-loaded
└── my-complex-plugin/      # KHÔNG tự load
    ├── main.php            # Phải có loader file ở thư mục gốc
    └── includes/
        └── ...
```

### Đặc điểm

1. **Luôn active**: Không thể deactivate từ admin
2. **Load trước**: Load trước plugins thường và theme
3. **Không subdirectory**: Chỉ load file `.php` trực tiếp trong thư mục, KHÔNG tự load file trong subdirectory
4. **Không activation hooks**: `register_activation_hook()` không hoạt động
5. **Không auto-update**: Phải update thủ công

### Loader cho mu-plugin phức tạp

```php
// wp-content/mu-plugins/load-my-complex-plugin.php
<?php
/**
 * Plugin Name: My Complex MU Plugin
 * Description: Loader cho mu-plugin nhiều file
 */

require_once __DIR__ . '/my-complex-plugin/main.php';
```

### Ví dụ mu-plugin

```php
// wp-content/mu-plugins/security-headers.php
<?php
/**
 * Plugin Name: Security Headers
 * Description: Thêm security headers cho tất cả responses
 */

add_action( 'send_headers', function() {
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-XSS-Protection: 1; mode=block' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
});

// wp-content/mu-plugins/disable-xmlrpc.php
<?php
/**
 * Plugin Name: Disable XML-RPC
 * Description: Tắt XML-RPC hoàn toàn
 */

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
});
```

---

## 11. Drop-ins

Drop-ins là các file PHP đặc biệt đặt trực tiếp trong `wp-content/`.

### Danh sách Drop-ins

| File | Mô tả | Khi nào load |
|------|--------|-------------|
| `advanced-cache.php` | Page caching | Khi `WP_CACHE = true` |
| `db.php` | Custom database class | Khi kết nối DB |
| `object-cache.php` | Object cache (Redis/Memcached) | Thay wp_cache functions |
| `maintenance.php` | Custom maintenance page | Khi `.maintenance` file tồn tại |
| `sunrise.php` | Multisite domain mapping | Multisite, khi `SUNRISE = true` |
| `blog-deleted.php` | Blog deleted message | Multisite |
| `blog-inactive.php` | Blog inactive message | Multisite |
| `blog-suspended.php` | Blog suspended message | Multisite |

### Ví dụ object-cache.php (Redis)

```php
// wp-content/object-cache.php
// Thường được install bởi plugin như Redis Object Cache

// File này thay thế hoàn toàn WordPress Object Cache
// Cung cấp các functions:
// wp_cache_init(), wp_cache_get(), wp_cache_set(),
// wp_cache_delete(), wp_cache_flush(), wp_cache_add()

// Kiểm tra trong admin:
// Plugins → Drop-ins sẽ hiển thị object-cache.php
```

### Ví dụ db.php

```php
// wp-content/db.php
// Custom database class - phải extends wpdb

// Ví dụ: Query logging
class Custom_WPDB extends wpdb {
    public function query( $query ) {
        $start  = microtime( true );
        $result = parent::query( $query );
        $time   = microtime( true ) - $start;

        if ( $time > 1.0 ) {
            error_log( "Slow query ({$time}s): {$query}" );
        }

        return $result;
    }
}

$wpdb = new Custom_WPDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
```

---

## 12. Plugin File Editor

**Source**: `wp-admin/plugin-editor.php`

```php
// Capability required
if ( ! current_user_can( 'edit_plugins' ) ) {
    wp_die( __( 'Sorry, you are not allowed to edit plugins for this site.' ) );
}
```

### Disable Plugin Editor (Khuyến nghị)

```php
// Trong wp-config.php
define( 'DISALLOW_FILE_EDIT', true );  // Tắt Plugin + Theme Editor
define( 'DISALLOW_FILE_MODS', true );  // Tắt luôn install/update
```

> **Quan trọng**: Luôn tắt file editor trên production. Lý do:
> - Sửa trực tiếp code trên server rất nguy hiểm
> - Nếu bị hack, attacker có thể inject code
> - Không có version control
> - Một lỗi syntax có thể làm crash toàn bộ site

---

## 13. Auto-Updates

### Cấu hình từ WordPress 5.5

```php
// Option trong wp_options
get_option( 'auto_update_plugins' );
// Array ( 'my-plugin/my-plugin.php', 'akismet/akismet.php' )

// Bật/tắt cho plugin cụ thể qua admin:
// Plugins list → Toggle "Enable auto-updates" / "Disable auto-updates"
```

### Kiểm soát bằng code

```php
// Bật auto-update cho tất cả plugins
add_filter( 'auto_update_plugin', '__return_true' );

// Tắt cho tất cả
add_filter( 'auto_update_plugin', '__return_false' );

// Điều kiện: chỉ auto-update plugins cụ thể
add_filter( 'auto_update_plugin', function( $update, $item ) {
    $auto_update_plugins = array(
        'akismet/akismet.php',
        'wordpress-seo/wp-seo.php',
    );
    return in_array( $item->plugin, $auto_update_plugins, true );
}, 10, 2 );

// Tắt email notification khi auto-update
add_filter( 'auto_plugin_update_send_email', '__return_false' );
```

### Cron job

```php
// WordPress dùng WP-Cron để kiểm tra updates
// Event: wp_maybe_auto_update
// Schedule: twice_daily (2 lần/ngày)

// Hook: wp_maybe_auto_update
// Source: wp-admin/includes/update.php
```

---

## 14. Plugin Dependencies (WP 6.5+)

Từ WordPress 6.5, plugins có thể khai báo dependencies.

```php
/**
 * Plugin Name: My WooCommerce Extension
 * Requires Plugins: woocommerce, advanced-custom-fields
 */
```

### Hoạt động

```php
// WP_Plugin_Dependencies::initialize()
// Được gọi từ wp-admin/plugins.php dòng 43

// Kiểm tra:
// 1. Plugin dependency đã cài chưa?
// 2. Plugin dependency đã active chưa?
// 3. Nếu chưa → Hiển thị warning, không cho activate

// Source: wp-admin/includes/class-wp-plugin-dependencies.php
```

### UI trong admin

Khi plugin khai báo `Requires Plugins`:
- Nếu dependency chưa cài → hiển thị link "Install" dependency
- Nếu dependency chưa active → hiển thị warning
- Nếu activate plugin mà dependency chưa active → hiển thị error

---

## 15. DB: Plugins Lưu Gì?

### wp_options - Plugin-related Options

| Option Name | Mô tả | Giá trị |
|-------------|--------|---------|
| `active_plugins` | Danh sách plugins đang active | Serialized array |
| `uninstall_plugins` | Callbacks khi uninstall | Serialized array |
| `recently_activated` | Plugins vừa deactivate | Serialized array (plugin => timestamp) |
| `auto_update_plugins` | Plugins bật auto-update | Serialized array |
| `_site_transient_update_plugins` | Cache thông tin update | Serialized object |

### active_plugins chi tiết

```php
get_option( 'active_plugins' );
/*
Array (
    [0] => 'akismet/akismet.php',
    [1] => 'my-plugin/my-plugin.php',
    [2] => 'woocommerce/woocommerce.php',
    [3] => 'wordpress-seo/wp-seo.php',
)
*/

// Sorted alphabetically!
// WordPress sort() sau mỗi lần activate
```

### uninstall_plugins

```php
get_option( 'uninstall_plugins' );
/*
Array (
    'my-plugin/my-plugin.php' => array( 'My_Plugin', 'uninstall' ),
    'another-plugin/main.php' => 'another_plugin_uninstall_callback',
)
*/
```

### recently_activated

```php
get_option( 'recently_activated' );
/*
Array (
    'old-plugin/old-plugin.php' => 1705312200,  // Unix timestamp khi deactivate
)
*/

// Tự xóa entries cũ hơn 7 ngày
// Source: wp-admin/includes/plugin.php
```

### Plugin-specific Options

Mỗi plugin tự quản lý options riêng:

```php
// Plugin lưu settings
update_option( 'my_plugin_settings', array(
    'api_key'     => 'abc123',
    'enabled'     => true,
    'cache_ttl'   => 3600,
) );

// Plugin lưu version
update_option( 'my_plugin_db_version', '1.0.0' );

// Plugin lưu transient (cache tạm)
set_transient( 'my_plugin_api_cache', $data, HOUR_IN_SECONDS );
```

### Multisite: Network-activated Plugins

```php
// Lưu trong wp_sitemeta (không phải wp_options)
get_site_option( 'active_sitewide_plugins' );
/*
Array (
    'akismet/akismet.php' => 1705312200,  // plugin => activation timestamp
    'woocommerce/woocommerce.php' => 1705398600,
)
*/
```

---

## 16. Hooks Plugins - Danh Sách Đầy Đủ

### Action Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `activate_plugin` | Trước activate (chung) | `$plugin`, `$network_wide` |
| `activate_{$plugin}` | Trước activate (cụ thể) | `$network_wide` |
| `activated_plugin` | Sau activate | `$plugin`, `$network_wide` |
| `deactivate_plugin` | Trước deactivate (chung) | `$plugin`, `$network_deactivating` |
| `deactivate_{$plugin}` | Trước deactivate (cụ thể) | `$network_deactivating` |
| `deactivated_plugin` | Sau deactivate | `$plugin`, `$network_deactivating` |
| `deleted_plugin` | Sau xóa | `$plugin_file`, `$deleted` |
| `upgrader_process_complete` | Sau update | `$upgrader`, `$hook_extra` |
| `pre_current_active_plugins` | Trước hiển thị list | (không có) |

### Filter Hooks

| Hook | Chức năng | Tham số |
|------|-----------|---------|
| `plugin_action_links_{$plugin_file}` | Action links dưới tên plugin | `$actions`, `$plugin_file`, `$plugin_data`, `$context` |
| `plugin_row_meta` | Meta links dưới description | `$plugin_meta`, `$plugin_file`, `$plugin_data`, `$status` |
| `all_plugins` | Filter danh sách tất cả plugins | `$all_plugins` |
| `network_admin_plugin_action_links_{$plugin_file}` | Network admin action links | `$actions`, `$plugin_file`, `$plugin_data`, `$context` |
| `install_plugins_tabs` | Tabs trên install screen | `$tabs` |
| `plugins_api` | Custom plugin API | `$result`, `$action`, `$args` |
| `plugins_api_result` | Filter API result | `$result`, `$action`, `$args` |
| `auto_update_plugin` | Control auto-update per plugin | `$update`, `$item` |
| `site_transient_update_plugins` | Filter update info | `$transient` |

### Ví dụ sử dụng hooks

```php
// 1. Thêm "Settings" link dưới tên plugin
add_filter( 'plugin_action_links_my-plugin/my-plugin.php', function( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=my-plugin' ) . '">'
        . __( 'Cài Đặt', 'my-plugin' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
});

// 2. Thêm links dưới description
add_filter( 'plugin_row_meta', function( $links, $file ) {
    if ( 'my-plugin/my-plugin.php' === $file ) {
        $links[] = '<a href="https://docs.example.com" target="_blank">'
            . __( 'Tài Liệu', 'my-plugin' ) . '</a>';
        $links[] = '<a href="https://example.com/support" target="_blank">'
            . __( 'Hỗ Trợ', 'my-plugin' ) . '</a>';
    }
    return $links;
}, 10, 2 );

// 3. Ẩn plugin khỏi danh sách (mu-plugin-like behavior)
add_filter( 'all_plugins', function( $plugins ) {
    unset( $plugins['hidden-plugin/hidden-plugin.php'] );
    return $plugins;
});

// 4. Thêm notice sau khi activate
add_action( 'activated_plugin', function( $plugin ) {
    if ( 'my-plugin/my-plugin.php' === $plugin ) {
        set_transient( 'my_plugin_activation_notice', true, 30 );
    }
});

add_action( 'admin_notices', function() {
    if ( get_transient( 'my_plugin_activation_notice' ) ) {
        delete_transient( 'my_plugin_activation_notice' );
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . __( 'My Plugin đã được kích hoạt! ', 'my-plugin' );
        echo '<a href="' . admin_url( 'options-general.php?page=my-plugin' ) . '">';
        echo __( 'Cấu hình ngay', 'my-plugin' ) . '</a></p>';
        echo '</div>';
    }
});

// 5. Redirect tới settings page sau activate
add_action( 'activated_plugin', function( $plugin ) {
    if ( 'my-plugin/my-plugin.php' === $plugin ) {
        wp_redirect( admin_url( 'options-general.php?page=my-plugin&activated=1' ) );
        exit;
    }
});

// 6. Hiển thị custom thông tin trong plugin row
add_action( 'after_plugin_row_my-plugin/my-plugin.php', function( $plugin_file, $plugin_data, $status ) {
    $license = get_option( 'my_plugin_license_status' );
    if ( 'valid' !== $license ) {
        echo '<tr class="plugin-update-tr active">';
        echo '<td colspan="4" class="plugin-update colspanchange">';
        echo '<div class="update-message notice notice-warning inline">';
        echo '<p>' . __( 'Giấy phép chưa được kích hoạt. ', 'my-plugin' );
        echo '<a href="' . admin_url( 'options-general.php?page=my-plugin-license' ) . '">';
        echo __( 'Nhập license key', 'my-plugin' ) . '</a></p>';
        echo '</div></td></tr>';
    }
}, 10, 3 );
```

---

## 17. Recovery Mode (WP 5.2+)

### Cách hoạt động

Khi plugin gây fatal error (PHP fatal, exception), WordPress:

1. Bắt error qua shutdown handler
2. Gửi email cho admin với recovery link
3. Admin click link → login trong Recovery Mode
4. Trong Recovery Mode, plugin lỗi bị tạm dừng (paused)
5. Admin có thể deactivate plugin lỗi

**Source**: `wp-includes/class-wp-recovery-mode.php`

```php
// Kiểm tra có đang trong recovery mode
wp_is_recovery_mode(); // true/false

// Recovery mode cookie
// Set khi admin click recovery link trong email
// Cookie name: wordpress_rec_{hash}
// Duration: Mặc định 1 giờ (RECOVERY_MODE_COOKIE_EXPIRATION)
```

### Paused Plugins

```php
// Khi plugin gây error, nó bị "paused"
// Lưu trong option: 'paused_extensions'
// Sub-key: 'plugins'

// Source: wp-includes/class-wp-paused-extensions-storage.php
wp_paused_plugins()->get_all();
/*
Array (
    'my-broken-plugin' => array(
        'type'    => E_ERROR,
        'message' => 'Call to undefined function...',
        'file'    => '/path/to/plugins/my-broken-plugin/main.php',
        'line'    => 42,
    ),
)
*/
```

### Email thông báo

```php
// WordPress gửi email admin khi detect fatal error
// Template email: wp-includes/class-wp-recovery-mode-email-service.php
// Nội dung: Mô tả lỗi + Recovery Mode link

// Filter email
add_filter( 'recovery_mode_email', function( $email, $url ) {
    // $email = array( 'to', 'subject', 'message', 'headers' )
    // $url = Recovery Mode URL
    // Thêm người nhận, thay đổi nội dung, v.v.
    return $email;
}, 10, 2 );
```

### Resume Plugin trong Recovery Mode

```php
// Khi vào admin trong Recovery Mode:
// - Plugin lỗi hiển thị với nút "Resume"
// - Click "Resume" → Cố gắng load plugin lại
// - Nếu vẫn lỗi → plugin vẫn bị paused
// - Nếu ok → plugin hoạt động bình thường

// Admin có thể:
// 1. Deactivate plugin lỗi
// 2. Resume plugin (nếu đã fix lỗi)
// 3. Update plugin (nếu có bản update fix lỗi)
```

---

## 18. Uninstall Hooks

### Cách 1: register_uninstall_hook()

```php
// Trong file chính của plugin
register_uninstall_hook( __FILE__, 'my_plugin_uninstall' );

function my_plugin_uninstall() {
    // Xóa options
    delete_option( 'my_plugin_settings' );
    delete_option( 'my_plugin_db_version' );

    // Xóa transients
    delete_transient( 'my_plugin_cache' );

    // Xóa custom tables
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_plugin_data" );

    // Xóa user meta
    delete_metadata( 'user', 0, 'my_plugin_user_pref', '', true );

    // Xóa post meta
    delete_metadata( 'post', 0, '_my_plugin_meta', '', true );

    // Xóa cron events
    wp_clear_scheduled_hook( 'my_plugin_cron_event' );

    // Xóa custom posts
    $posts = get_posts( array(
        'post_type'      => 'my_cpt',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ) );
    foreach ( $posts as $post_id ) {
        wp_delete_post( $post_id, true );
    }

    // Xóa custom taxonomy terms
    // ... tương tự ...

    // Xóa uploaded files nếu cần
    // ... cẩn thận, kiểm tra kỹ trước khi xóa ...
}
```

### Cách 2: uninstall.php

```php
// File: wp-content/plugins/my-plugin/uninstall.php

// Kiểm tra WP đang thực sự uninstall (bảo mật)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Code cleanup tương tự cách 1
delete_option( 'my_plugin_settings' );
// ...
```

### Sự khác biệt

| | `register_uninstall_hook()` | `uninstall.php` |
|---|---|---|
| Cách dùng | Callback function | File riêng |
| Khi nào gọi | Khi delete plugin | Khi delete plugin |
| Ưu tiên | Gọi trước | Gọi nếu không có register_uninstall_hook |
| Plugin loaded | Có (plugin file được include) | Không (chỉ load uninstall.php) |
| Lưu trong DB | `uninstall_plugins` option | Không cần |

---

## 19. Ví Dụ Thực Tế: Plugin Skeleton

### Cấu trúc thư mục

```
my-plugin/
├── my-plugin.php           # Main file (headers + bootstrap)
├── uninstall.php           # Cleanup khi xóa plugin
├── readme.txt              # WordPress.org readme
├── composer.json           # Dependencies (nếu dùng Composer)
├── includes/
│   ├── class-my-plugin.php         # Core class
│   ├── class-my-plugin-admin.php   # Admin functionality
│   ├── class-my-plugin-public.php  # Public functionality
│   └── class-my-plugin-api.php     # REST API
├── admin/
│   ├── css/
│   ├── js/
│   └── views/
│       └── settings-page.php
├── public/
│   ├── css/
│   └── js/
├── languages/
│   └── my-plugin-vi.po
└── templates/
    └── single-my-cpt.php
```

### Main plugin file

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: Plugin mẫu cho WordPress developer
 * Version: 1.0.0
 * Author: Tác Giả
 * Text Domain: my-plugin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Không cho truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'MY_PLUGIN_VERSION', '1.0.0' );
define( 'MY_PLUGIN_FILE', __FILE__ );
define( 'MY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoload (nếu dùng Composer)
if ( file_exists( MY_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once MY_PLUGIN_DIR . 'vendor/autoload.php';
}

// Activation/Deactivation hooks
register_activation_hook( __FILE__, array( 'My_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'My_Plugin', 'deactivate' ) );

// Load plugin
require_once MY_PLUGIN_DIR . 'includes/class-my-plugin.php';

// Initialize
add_action( 'plugins_loaded', function() {
    My_Plugin::instance();
});
```

### Core class (Singleton pattern)

```php
<?php
// includes/class-my-plugin.php

class My_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once MY_PLUGIN_DIR . 'includes/class-my-plugin-admin.php';
        require_once MY_PLUGIN_DIR . 'includes/class-my-plugin-public.php';
    }

    private function set_locale() {
        add_action( 'init', function() {
            load_plugin_textdomain( 'my-plugin', false, dirname( MY_PLUGIN_BASENAME ) . '/languages' );
        });
    }

    private function define_admin_hooks() {
        if ( ! is_admin() ) return;

        $admin = new My_Plugin_Admin();

        // Settings page
        add_action( 'admin_menu', array( $admin, 'add_settings_page' ) );
        add_action( 'admin_init', array( $admin, 'register_settings' ) );

        // Admin scripts/styles
        add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );

        // Plugin action links
        add_filter( 'plugin_action_links_' . MY_PLUGIN_BASENAME, array( $admin, 'action_links' ) );
    }

    private function define_public_hooks() {
        $public = new My_Plugin_Public();
        add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_assets' ) );
    }

    // Activation
    public static function activate() {
        global $wpdb;

        // Tạo bảng
        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = $wpdb->prefix . 'my_plugin_data';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Default options
        add_option( 'my_plugin_version', MY_PLUGIN_VERSION );
        add_option( 'my_plugin_settings', array(
            'enabled'   => true,
            'per_page'  => 20,
        ) );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    // Deactivation
    public static function deactivate() {
        // Xóa cron
        wp_clear_scheduled_hook( 'my_plugin_daily_event' );

        // Flush rewrite rules
        flush_rewrite_rules();

        // KHÔNG xóa data - chỉ xóa khi uninstall
    }
}
```

---

## 20. So Sánh Với Laravel

| Tính năng | WordPress Plugin | Laravel Package |
|-----------|-----------------|-----------------|
| Cấu hình | Plugin headers (comment) | `composer.json` |
| Cài đặt | Upload .zip / WordPress.org | `composer require` |
| Activate | Admin UI / `activate_plugin()` | Service Provider auto-discovery |
| Deactivate | Admin UI | Xóa khỏi `providers` array |
| Xóa | Admin UI + uninstall hook | `composer remove` |
| Settings | Options API / Settings API | Config files + `.env` |
| Database | `dbDelta()` trong activation hook | Migrations |
| Auto-update | Built-in (WP 5.5+) | `composer update` |
| Dependencies | `Requires Plugins` header (WP 6.5+) | `composer.json` require |
| Recovery | Recovery Mode (WP 5.2+) | Không có built-in |
| Marketplace | WordPress.org | Packagist |
| Hooks system | Actions + Filters | Events + Listeners |

### Service Provider tương đương

```php
// WordPress: Plugin activation hook
register_activation_hook( __FILE__, function() {
    // Tạo tables, set options
});

// Laravel: Service Provider
class MyPackageServiceProvider extends ServiceProvider {
    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../config/my-package.php', 'my-package');
    }

    public function boot() {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/my-package.php' => config_path('my-package.php'),
        ]);

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'my-package');
    }
}
```

### Hooks = Events

```php
// WordPress hooks
add_action( 'save_post', function( $post_id ) {
    // Sau khi lưu post
});

add_filter( 'the_title', function( $title ) {
    return strtoupper( $title );
});

// Laravel events
Event::listen( PostSaved::class, function( $event ) {
    // Sau khi lưu post
});

// Laravel middleware (tương đương filter)
class UppercaseTitle {
    public function handle( $request, $next ) {
        $response = $next( $request );
        // Modify response
        return $response;
    }
}
```

---

## 21. Tổng Kết

### Các điểm quan trọng

1. **Plugin = Extend WordPress**: Plugin thêm tính năng mà không sửa core. Tương đương Composer packages + Service Providers trong Laravel.

2. **Activation flow**: `activate_plugin()` → validate → sandbox scrape → fire hooks → update `active_plugins` option.

3. **active_plugins option**: Serialized array trong `wp_options`, chứa danh sách plugin file paths đang active.

4. **Plugin headers**: 8KB đầu tiên của file plugin chứa metadata trong comment block. Tương đương `composer.json`.

5. **Must-Use Plugins**: Luôn active, load trước, không thể deactivate. Dùng cho code infrastructure (security, performance).

6. **Drop-ins**: Files đặc biệt thay thế core functionality (object cache, DB class, maintenance page).

7. **Recovery Mode**: WordPress 5.2+ tự phát hiện fatal errors, tạm dừng plugin lỗi, gửi email admin với recovery link.

8. **Auto-Updates**: WordPress 5.5+ hỗ trợ auto-update plugins qua WP-Cron. Control bằng filter `auto_update_plugin`.

9. **Uninstall**: Dùng `register_uninstall_hook()` hoặc file `uninstall.php` để cleanup khi xóa plugin. KHÔNG cleanup trong deactivation hook.

10. **Hooks quan trọng nhất**:
    - `activate_{$plugin}` / `deactivate_{$plugin}` - Activation/deactivation
    - `plugin_action_links_{$plugin_file}` - Thêm links trong admin
    - `plugin_row_meta` - Thêm meta info
    - `auto_update_plugin` - Control auto-update
    - `upgrader_process_complete` - Sau update

11. **DISALLOW_FILE_EDIT**: Luôn bật trong production.

12. **Plugin Dependencies (WP 6.5+)**: Header `Requires Plugins` cho phép khai báo plugin dependencies.

---

> **Tiep theo**: Xem thêm các tài liệu khác trong thư mục `wp-study/admin/`
