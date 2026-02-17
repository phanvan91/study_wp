# Plugin WordPress Cơ Bản

## Mục lục

1. [Plugin là gì, tại sao cần Plugin](#1-plugin-la-gi-tai-sao-can-plugin)
2. [Plugin Headers đầy đủ](#2-plugin-headers-day-du)
3. [Cấu trúc thư mục Plugin](#3-cau-truc-thu-muc-plugin)
4. [Activation, Deactivation, Uninstall Hooks](#4-activation-deactivation-uninstall-hooks)
5. [Plugin Lifecycle](#5-plugin-lifecycle)
6. [Tạo Plugin Hello World đầu tiên](#6-tao-plugin-hello-world-dau-tien)
7. [So sánh Plugin với Service Provider trong Laravel](#7-so-sanh-plugin-voi-service-provider-trong-laravel)
8. [Best Practices](#8-best-practices)

---

## 1. Plugin là gì, tại sao cần Plugin

### Plugin là gì?

Plugin là một gói mã nguồn (package) mở rộng chức năng của WordPress mà **không cần chỉnh sửa core**. Plugin hoạt động theo cơ chế **hook** (action và filter) để "cắm" (plug) vào hệ thống WordPress.

### Tại sao cần Plugin?

| Lý do | Giải thích |
|-------|-----------|
| **Tách biệt code** | Code riêng, không ảnh hưởng theme hay core |
| **Tái sử dụng** | Dùng được ở nhiều site WordPress khác nhau |
| **Cập nhật độc lập** | Cập nhật plugin không ảnh hưởng phần khác |
| **Cộng đồng** | Có thể chia sẻ lên WordPress.org |
| **Bảo trì dễ dàng** | Bật/tắt plugin để kiểm tra lỗi |

### So sánh nhanh với Laravel

```
Laravel:   Composer Package / Service Provider  =>  Mở rộng ứng dụng
WordPress: Plugin                                =>  Mở rộng website
```

Trong Laravel, bạn tạo **Service Provider** để đăng ký services, routes, views. Trong WordPress, bạn tạo **Plugin** để đăng ký hooks, filters, menus, post types.

---

## 2. Plugin Headers đầy đủ

Mỗi plugin WordPress bắt buộc phải có **Plugin Header** - là block comment ở đầu file chính của plugin. WordPress đọc comment này để nhận diện plugin.

### Header tối thiểu

```php
<?php
/**
 * Plugin Name: My First Plugin
 */
```

Chỉ cần dòng `Plugin Name` là WordPress đã nhận diện được plugin.

### Header đầy đủ tất cả các trường

```php
<?php
/**
 * Plugin Name:       My Awesome Plugin
 * Plugin URI:        https://example.com/my-awesome-plugin
 * Description:       Mô tả ngắn gọn về plugin - hiển thị trong trang Plugins.
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

### Giải thích từng trường

```php
<?php
/**
 * Plugin Name:       Tên plugin - BẮT BUỘC, hiển thị trong admin
 * Plugin URI:        URL trang giới thiệu plugin
 * Description:       Mô tả - hiển thị dưới tên plugin trong danh sách
 * Version:           Phiên bản hiện tại (Semantic Versioning: MAJOR.MINOR.PATCH)
 * Requires at least: Phiên bản WordPress tối thiểu cần để chạy plugin
 * Requires PHP:      Phiên bản PHP tối thiểu
 * Author:            Tên tác giả
 * Author URI:        Website tác giả
 * License:           Giấy phép - WordPress yêu cầu GPL v2+
 * License URI:       URL của giấy phép
 * Text Domain:       Slug dùng cho đa ngôn ngữ (internationalization)
 * Domain Path:       Thư mục chứa file ngôn ngữ (.mo, .po)
 * Network:           true nếu plugin chỉ hoạt động trên Multisite
 * Update URI:        URL kiểm tra cập nhật (từ WP 5.8)
 */
```

### Ngăn chặn truy cập trực tiếp

Luôn thêm dòng này ngay sau Plugin Header:

```php
<?php
/**
 * Plugin Name: My Plugin
 */

// Ngăn chặn truy cập trực tiếp vào file plugin
// Nếu không có hằng ABSPATH (WordPress định nghĩa), thoát ngay
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Hoặc dùng: die('Direct access not allowed.');
}
```

**Tại sao cần dòng này?** Nếu ai đó truy cập trực tiếp URL `yoursite.com/wp-content/plugins/my-plugin/my-plugin.php`, WordPress chưa được load nên ABSPATH chưa được định nghĩa. Dòng này ngăn chặn việc đó.

---

## 3. Cấu trúc thư mục Plugin

### 3.1. Plugin đơn file (Single File Plugin)

Phù hợp với plugin nhỏ, đơn giản.

```
wp-content/
  plugins/
    my-simple-plugin.php    <-- Chỉ 1 file duy nhất
```

```php
<?php
/**
 * Plugin Name: My Simple Plugin
 * Description: Plugin đơn giản chỉ có 1 file.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Tất cả code đặt trong file này
add_action( 'wp_footer', function() {
    echo '<p>Hello từ My Simple Plugin!</p>';
});
```

### 3.2. Plugin đa file (Multi-File Plugin)

Phù hợp với plugin trung bình và lớn.

```
wp-content/
  plugins/
    my-awesome-plugin/                 <-- Thư mục plugin
      my-awesome-plugin.php            <-- File chính (cùng tên với thư mục)
      uninstall.php                    <-- Chạy khi xóa plugin
      readme.txt                       <-- Mô tả cho WordPress.org

      includes/                        <-- Logic chính của plugin
        class-main.php
        class-activator.php
        class-deactivator.php
        functions.php

      admin/                           <-- Code dành cho trang Admin
        class-admin.php
        partials/                      <-- Template admin
          settings-page.php
        css/
          admin-style.css
        js/
          admin-script.js

      public/                          <-- Code dành cho Frontend
        class-public.php
        partials/
          display-template.php
        css/
          public-style.css
        js/
          public-script.js

      languages/                       <-- File ngôn ngữ
        my-awesome-plugin-vi.po
        my-awesome-plugin-vi.mo

      templates/                       <-- Template có thể override từ theme
        single-template.php

      assets/                          <-- Tài nguyên chung
        images/
          icon.png
```

### So sánh cấu trúc với Laravel

```
Laravel Package:              WordPress Plugin:
src/                    =>    includes/
resources/views/        =>    admin/partials/, public/partials/
config/                 =>    (Settings API)
routes/                 =>    (Hooks trong file chính)
public/                 =>    assets/, admin/css/, public/css/
tests/                  =>    tests/
lang/                   =>    languages/
composer.json           =>    readme.txt + Plugin Header
ServiceProvider.php     =>    my-plugin.php (file chính)
```

### 3.3. Định nghĩa Constants hữu ích

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Định nghĩa các hằng số để dùng xuyên suốt plugin
// __FILE__ trả về đường dẫn tuyệt đối của file hiện tại
define( 'MAP_VERSION', '1.0.0' );

// Đường dẫn tuyệt đối trên server: /var/www/html/wp-content/plugins/my-awesome-plugin/
define( 'MAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// URL của plugin: https://example.com/wp-content/plugins/my-awesome-plugin/
define( 'MAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Đường dẫn file chính: my-awesome-plugin/my-awesome-plugin.php
define( 'MAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Sử dụng constants
// require_once MAP_PLUGIN_DIR . 'includes/class-main.php';
// wp_enqueue_style( 'map-style', MAP_PLUGIN_URL . 'public/css/style.css', array(), MAP_VERSION );
```

---

## 4. Activation, Deactivation, Uninstall Hooks

WordPress cung cấp 3 hooks quan trọng cho vòng đời của plugin:

### 4.1. Activation Hook

Chạy **một lần** khi plugin được kích hoạt (activate).

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
 * Hàm chạy khi kích hoạt plugin.
 * Dùng để:
 * - Tạo bảng database
 * - Thêm default options
 * - Tạo trang mặc định
 * - Đăng ký Cron Jobs
 * - Flush rewrite rules (nếu tạo Custom Post Type)
 */
function lifecycle_demo_activate() {
    // 1. Thêm option mặc định
    // add_option chỉ thêm nếu option chưa tồn tại (không ghi đè)
    add_option( 'lifecycle_demo_version', '1.0.0' );
    add_option( 'lifecycle_demo_settings', array(
        'enable_feature'  => true,
        'items_per_page'  => 10,
        'display_mode'    => 'grid',
    ));

    // 2. Tạo bảng database (xem chi tiết ở bài Database)
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

    // 3. Thêm role hoặc capability
    $role = get_role( 'administrator' );
    if ( $role ) {
        $role->add_cap( 'manage_lifecycle_demo' );
    }

    // 4. Flush rewrite rules (nếu plugin tạo Custom Post Type)
    // Lưu ý: phải đăng ký CPT trước khi flush
    flush_rewrite_rules();
}

// Đăng ký activation hook
// Tham số 1: Đường dẫn file chính của plugin (__FILE__)
// Tham số 2: Hàm callback
register_activation_hook( __FILE__, 'lifecycle_demo_activate' );
```

### 4.2. Deactivation Hook

Chạy **một lần** khi plugin bị vô hiệu hóa (deactivate).

```php
<?php
/**
 * Hàm chạy khi vô hiệu hóa plugin.
 * Dùng để:
 * - Xóa Cron Jobs
 * - Flush rewrite rules
 * - Tạm dừng các tính năng
 * KHÔNG nên xóa data ở đây (để dành cho uninstall)
 */
function lifecycle_demo_deactivate() {
    // 1. Xóa cron jobs đã đăng ký
    $timestamp = wp_next_scheduled( 'lifecycle_demo_daily_event' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'lifecycle_demo_daily_event' );
    }

    // 2. Flush rewrite rules
    flush_rewrite_rules();

    // 3. KHÔNG xóa data, options, tables ở đây!
    // Người dùng có thể chỉ tạm vô hiệu hóa rồi kích hoạt lại
}

register_deactivation_hook( __FILE__, 'lifecycle_demo_deactivate' );
```

### 4.3. Uninstall Hook

Chạy khi người dùng **xóa** plugin (delete). Có 2 cách:

#### Cách 1: Dùng register_uninstall_hook

```php
<?php
/**
 * Hàm chạy khi xóa plugin.
 * Đây là nơi dọn dẹp toàn bộ data của plugin.
 */
function lifecycle_demo_uninstall() {
    // 1. Xóa options
    delete_option( 'lifecycle_demo_version' );
    delete_option( 'lifecycle_demo_settings' );

    // 2. Xóa toàn bộ post meta liên quan
    // Cách này xóa tất cả post meta có key bắt đầu bằng '_lifecycle_demo_'
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_lifecycle_demo_%'"
    );

    // 3. Xóa user meta
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_lifecycle_demo_%'"
    );

    // 4. Xóa custom tables
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lifecycle_demo_logs" );

    // 5. Xóa transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lifecycle_demo_%'"
    );
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lifecycle_demo_%'"
    );

    // 6. Xóa cron jobs (phòng trường hợp)
    wp_clear_scheduled_hook( 'lifecycle_demo_daily_event' );

    // 7. Xóa capabilities
    $role = get_role( 'administrator' );
    if ( $role ) {
        $role->remove_cap( 'manage_lifecycle_demo' );
    }
}

register_uninstall_hook( __FILE__, 'lifecycle_demo_uninstall' );
```

#### Cách 2: Dùng file uninstall.php (KHUYÊN DÙNG)

Tạo file `uninstall.php` trong thư mục gốc của plugin:

```php
<?php
/**
 * File: uninstall.php
 *
 * File này tự động chạy khi plugin bị xóa.
 * WordPress sẽ tự động tìm và chạy file này.
 * Cách này được khuyên dùng hơn register_uninstall_hook
 * vì không cần load toàn bộ plugin chỉ để xóa data.
 */

// Kiểm tra xem WordPress có đang thực sự gọi file này không
// WP_UNINSTALL_PLUGIN được WordPress định nghĩa khi xóa plugin
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xóa options
delete_option( 'lifecycle_demo_version' );
delete_option( 'lifecycle_demo_settings' );

// Xóa custom table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lifecycle_demo_logs" );

// Xóa post meta
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_lifecycle_demo_%'"
);

// Xóa user meta
$wpdb->query(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'lifecycle_demo_%'"
);

// Xóa transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_lifecycle_demo_%'"
);
```

### So sánh 2 cách Uninstall

| | register_uninstall_hook | uninstall.php |
|---|---|---|
| **Ưu điểm** | Code nằm chung với file chính | Không cần load toàn bộ plugin |
| **Nhược điểm** | Phải load plugin trước | File riêng, dễ quên |
| **Khuyên dùng** | Không | **Có** |
| **Độ ưu tiên** | Thấp hơn | Cao hơn (chạy trước) |

---

## 5. Plugin Lifecycle

```
                    Plugin Lifecycle

 [Cài đặt]  =>  [Kích hoạt]  =>  [Chạy]  =>  [Vô hiệu hóa]  =>  [Xóa]
                     |              |              |                |
            activation_hook    mỗi request   deactivation_hook  uninstall
            - Tạo tables      - Actions      - Xóa cron        - Xóa data
            - Add options     - Filters      - Flush rules     - Xóa tables
            - Flush rules     - Shortcodes                     - Xóa options
                              - Menus
                              - Widgets
```

### Chi tiết Lifecycle

```php
<?php
/**
 * Plugin Name: Lifecycle Visualizer
 * Description: Minh họa vòng đời của plugin WordPress.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === GIAI ĐOẠN 1: KÍCH HOẠT (Activation) ===
// Chạy 1 lần duy nhất khi click "Activate"
register_activation_hook( __FILE__, function() {
    // Setup ban đầu
    add_option( 'lvp_installed_at', current_time( 'mysql' ) );
    add_option( 'lvp_version', '1.0.0' );
});

// === GIAI ĐOẠN 2: CHẠY (Runtime) ===
// Code dưới đây chạy MỖI KHI WordPress load (mỗi request)

// plugins_loaded - Hook chạy sau khi tất cả plugins đã được load
add_action( 'plugins_loaded', function() {
    // Kiểm tra version để upgrade nếu cần
    $current_version = get_option( 'lvp_version', '0.0.0' );
    if ( version_compare( $current_version, '1.0.0', '<' ) ) {
        // Chạy code upgrade
        update_option( 'lvp_version', '1.0.0' );
    }
});

// init - Hook chạy sau khi WordPress khởi tạo xong
add_action( 'init', function() {
    // Đăng ký Post Types, Taxonomies, Shortcodes
});

// admin_init - Chỉ chạy trong trang Admin
add_action( 'admin_init', function() {
    // Đăng ký Settings
});

// admin_menu - Thêm menu trong Admin
add_action( 'admin_menu', function() {
    // Thêm menu
});

// wp_enqueue_scripts - Thêm CSS/JS cho frontend
add_action( 'wp_enqueue_scripts', function() {
    // Thêm styles và scripts
});

// admin_enqueue_scripts - Thêm CSS/JS cho admin
add_action( 'admin_enqueue_scripts', function() {
    // Thêm admin styles và scripts
});

// === GIAI ĐOẠN 3: VÔ HIỆU HÓA (Deactivation) ===
// Chạy 1 lần khi click "Deactivate"
register_deactivation_hook( __FILE__, function() {
    // Dọn dẹp tạm thời, KHÔNG xóa data
});

// === GIAI ĐOẠN 4: XÓA (Uninstall) ===
// Xem file uninstall.php hoặc register_uninstall_hook
```

### Thứ tự Hooks khi WordPress Load

```
muplugins_loaded        => Must-Use plugins đã load
registered_taxonomy      => Taxonomies đã đăng ký
registered_post_type     => Post types đã đăng ký
plugins_loaded          => Tất cả plugins đã load        <-- Plugin bắt đầu chạy
setup_theme             => Trước khi load theme
after_setup_theme       => Sau khi load theme
init                    => WordPress đã khởi tạo xong    <-- Đăng ký CPT, Taxonomies
admin_init              => Admin đã khởi tạo (chỉ admin) <-- Đăng ký Settings
admin_menu              => Tạo menu admin
wp_loaded               => WordPress đã load hoàn tất
template_redirect       => Trước khi chọn template
wp_enqueue_scripts      => Thêm CSS/JS frontend
wp_head                 => Trong <head>
wp_footer               => Trước </body>
shutdown                => Kết thúc request
```

---

## 6. Tạo Plugin Hello World đầu tiên

### Bước 1: Tạo thư mục và file

```bash
# Di chuyển đến thư mục plugins của WordPress
cd /path/to/wordpress/wp-content/plugins/

# Tạo thư mục plugin
mkdir hello-world-plugin

# Tạo file chính
touch hello-world-plugin/hello-world-plugin.php
```

### Bước 2: Viết code plugin

```php
<?php
/**
 * Plugin Name:       Hello World Plugin
 * Plugin URI:        https://example.com/hello-world
 * Description:       Plugin đầu tiên - hiển thị thông báo "Hello World" trên website.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Developer Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * Text Domain:       hello-world-plugin
 */

// Ngăn chặn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === ĐỊNH NGHĨA CONSTANTS ===
define( 'HWP_VERSION', '1.0.0' );
define( 'HWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// === ACTIVATION HOOK ===
register_activation_hook( __FILE__, 'hwp_activate' );

function hwp_activate() {
    // Lưu thời gian cài đặt
    add_option( 'hwp_installed_at', current_time( 'mysql' ) );
    // Lưu cài đặt mặc định
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
    // Không xóa data, chỉ dọn dẹp tạm thời
}

// === THÊM MENU ADMIN ===
add_action( 'admin_menu', 'hwp_add_admin_menu' );

function hwp_add_admin_menu() {
    // Thêm menu trong Admin sidebar
    add_options_page(
        'Hello World Settings',      // Tiêu đề trang
        'Hello World',               // Tên menu
        'manage_options',            // Quyền cần thiết (admin)
        'hello-world-settings',      // Slug (URL)
        'hwp_settings_page'          // Hàm hiển thị trang
    );
}

// === TRANG SETTINGS ===
function hwp_settings_page() {
    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Xử lý form khi submit
    if ( isset( $_POST['hwp_save_settings'] ) ) {
        // Kiểm tra nonce (bảo mật chống CSRF)
        check_admin_referer( 'hwp_settings_nonce' );

        // Lấy và làm sạch dữ liệu
        $settings = array(
            'message'    => sanitize_text_field( $_POST['hwp_message'] ?? '' ),
            'text_color' => sanitize_hex_color( $_POST['hwp_text_color'] ?? '#ffffff' ),
            'bg_color'   => sanitize_hex_color( $_POST['hwp_bg_color'] ?? '#0073aa' ),
            'position'   => sanitize_text_field( $_POST['hwp_position'] ?? 'bottom' ),
            'enabled'    => isset( $_POST['hwp_enabled'] ) ? true : false,
        );

        // Lưu vào database
        update_option( 'hwp_settings', $settings );

        // Hiển thị thông báo thành công
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt!</p></div>';
    }

    // Lấy cài đặt hiện tại
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
        <h1>Hello World Plugin - Cài đặt</h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'hwp_settings_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hwp_enabled">Bật/Tắt</label>
                    </th>
                    <td>
                        <input type="checkbox"
                               id="hwp_enabled"
                               name="hwp_enabled"
                               value="1"
                               <?php checked( $settings['enabled'], true ); ?>>
                        <label for="hwp_enabled">Hiển thị thông báo trên website</label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="hwp_message">Nội dung thông báo</label>
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
                        <label for="hwp_text_color">Màu chữ</label>
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
                        <label for="hwp_bg_color">Màu nền</label>
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
                        <label for="hwp_position">Vị trí</label>
                    </th>
                    <td>
                        <select id="hwp_position" name="hwp_position">
                            <option value="top" <?php selected( $settings['position'], 'top' ); ?>>
                                Trên cùng
                            </option>
                            <option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>>
                                Dưới cùng
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Lưu cài đặt', 'primary', 'hwp_save_settings' ); ?>
        </form>

        <hr>
        <h3>Shortcode</h3>
        <p>Sử dụng shortcode <code>[hello_world]</code> để hiển thị thông báo trong bài viết.</p>
        <p>Hoặc: <code>[hello_world message="Custom message" color="#ff0000"]</code></p>
    </div>
    <?php
}

// === HIỂN THỊ THÔNG BÁO TRÊN FRONTEND ===
add_action( 'wp_footer', 'hwp_display_message' );

function hwp_display_message() {
    $settings = get_option( 'hwp_settings', array() );

    // Kiểm tra xem có bật tính năng không
    if ( empty( $settings['enabled'] ) ) {
        return;
    }

    $message    = esc_html( $settings['message'] ?? 'Hello World!' );
    $text_color = esc_attr( $settings['text_color'] ?? '#ffffff' );
    $bg_color   = esc_attr( $settings['bg_color'] ?? '#0073aa' );
    $position   = $settings['position'] ?? 'bottom';

    // Xác định vị trí CSS
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

// === ĐĂNG KÝ SHORTCODE ===
add_shortcode( 'hello_world', 'hwp_shortcode' );

function hwp_shortcode( $atts ) {
    // Gộp thuộc tính mặc định với thuộc tính người dùng truyền vào
    $atts = shortcode_atts( array(
        'message' => 'Hello World!',
        'color'   => '#0073aa',
    ), $atts, 'hello_world' );

    // Trả về HTML (shortcode PHẢI return, KHÔNG echo)
    return sprintf(
        '<div class="hwp-inline" style="background:%s; color:#fff; padding:10px; border-radius:5px; margin:10px 0;">%s</div>',
        esc_attr( $atts['color'] ),
        esc_html( $atts['message'] )
    );
}

// === THÊM LINK SETTINGS TRÊN TRANG PLUGINS ===
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hwp_action_links' );

function hwp_action_links( $links ) {
    // Thêm link "Cài đặt" bên cạnh "Deactivate"
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=hello-world-settings' ) . '">Cài đặt</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
```

### Bước 3: Tạo file uninstall.php

```php
<?php
/**
 * File: hello-world-plugin/uninstall.php
 * Chạy khi plugin bị xóa
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xóa tất cả options của plugin
delete_option( 'hwp_installed_at' );
delete_option( 'hwp_settings' );
```

### Bước 4: Kích hoạt và test

1. Truy cập **WordPress Admin > Plugins**
2. Tìm "Hello World Plugin" trong danh sách
3. Click **Activate**
4. Vào **Settings > Hello World** để cấu hình
5. Xem frontend - thông báo sẽ hiển thị ở cuối trang
6. Thử shortcode `[hello_world]` trong bài viết

---

## 7. So sánh Plugin với Service Provider trong Laravel

### Tương đồng

```php
<?php
// === LARAVEL: Service Provider ===

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MyFeatureServiceProvider extends ServiceProvider
{
    /**
     * Đăng ký services - giống như activation hook
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
     * Bootstrap - giống như các hooks init, admin_init
     */
    public function boot()
    {
        // Load routes - giống admin_menu
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Load views - giống admin/partials
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'myfeature');

        // Load migrations - giống dbDelta trong activation hook
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish assets - giống wp_enqueue_scripts
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/myfeature'),
        ], 'public');
    }
}

// === WORDPRESS: Plugin tương đương ===

/**
 * Plugin Name: My Feature Plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// register() tương đương => activation hook
register_activation_hook( __FILE__, function() {
    // Tạo tables (giống migrations)
    // Add options (giống config)
});

// boot() tương đương => các hooks
add_action( 'init', function() {
    // Đăng ký Post Types (giống Route::resource)
    // Đăng ký Taxonomies
});

add_action( 'admin_menu', function() {
    // Thêm menu (giống routes/web.php)
});

add_action( 'wp_enqueue_scripts', function() {
    // Thêm CSS/JS (giống publishes assets)
});
```

### Bảng so sánh chi tiết

| Khái niệm | Laravel | WordPress Plugin |
|-----------|---------|-----------------|
| **Khởi tạo** | `register()` | `register_activation_hook()` |
| **Bootstrap** | `boot()` | `plugins_loaded`, `init` hooks |
| **Config** | `config/app.php` | `get_option()`, `update_option()` |
| **Routes** | `routes/web.php` | `add_menu_page()`, `register_rest_route()` |
| **Controllers** | `App\Http\Controllers` | Callback functions / Classes |
| **Views** | Blade templates | PHP templates trong `partials/` |
| **Models** | Eloquent Models | `$wpdb` queries / WP_Query |
| **Migrations** | Migration files | `dbDelta()` trong activation hook |
| **Middleware** | Middleware classes | `current_user_can()`, nonce checks |
| **Events** | Events & Listeners | Actions & Filters (hooks) |
| **Service Container** | `app()->make()` | Không có (tự quản lý) |
| **Artisan CLI** | `php artisan` | `WP-CLI` |
| **Package Discovery** | `composer.json` extra | Plugin Header comment |
| **Uninstall** | Không có sẵn | `uninstall.php` |

### Điểm khác biệt lớn

```php
<?php
// Laravel: Dependency Injection tự động
class UserController extends Controller
{
    // Laravel tự động inject UserService
    public function __construct(
        private UserService $userService
    ) {}
}

// WordPress: Phải tự quản lý dependencies
class My_Plugin_Controller {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb; // Tự lấy dependency
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

### Đặt tên (Naming)

```php
<?php
// 1. Dùng prefix duy nhất cho tất cả functions, classes, constants
// Tránh trùng tên với plugin khác

// SAI - dễ trùng tên
function get_settings() { }
class Admin { }
define( 'VERSION', '1.0.0' );

// ĐÚNG - có prefix
function myplugin_get_settings() { }
class MyPlugin_Admin { }
define( 'MYPLUGIN_VERSION', '1.0.0' );

// TỐT NHẤT - dùng Namespace (PHP 5.6+)
namespace MyPlugin;
class Admin { }
function get_settings() { }
```

### Bảo mật

```php
<?php
// 1. Luôn kiểm tra ABSPATH
if ( ! defined( 'ABSPATH' ) ) exit;

// 2. Luôn kiểm tra quyền
if ( ! current_user_can( 'manage_options' ) ) return;

// 3. Luôn dùng nonce
wp_nonce_field( 'my_action', 'my_nonce' );
// Kiểm tra: check_admin_referer( 'my_action', 'my_nonce' );

// 4. Luôn sanitize input
$clean = sanitize_text_field( $_POST['field'] );

// 5. Luôn escape output
echo esc_html( $value );
echo esc_attr( $attribute );
echo esc_url( $url );
```

### Performance

```php
<?php
// 1. Chỉ load code khi cần thiết
if ( is_admin() ) {
    require_once MAP_PLUGIN_DIR . 'admin/class-admin.php';
} else {
    require_once MAP_PLUGIN_DIR . 'public/class-public.php';
}

// 2. Chỉ load CSS/JS trên trang cần
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Chỉ load trên trang settings của plugin
    if ( $hook !== 'settings_page_my-plugin' ) {
        return;
    }
    wp_enqueue_style( 'my-plugin-admin', MAP_PLUGIN_URL . 'admin/css/admin.css' );
});

// 3. Dùng Transients để cache dữ liệu
$data = get_transient( 'my_plugin_data' );
if ( false === $data ) {
    $data = expensive_query(); // Query nặng
    set_transient( 'my_plugin_data', $data, HOUR_IN_SECONDS );
}
```

### Checklist trước khi phát hành

- [ ] Có Plugin Header đầy đủ
- [ ] Có file `uninstall.php`
- [ ] Tất cả input được sanitize
- [ ] Tất cả output được escape
- [ ] Dùng Nonces cho mỗi form và AJAX
- [ ] Kiểm tra quyền người dùng
- [ ] Dùng `$wpdb->prepare()` cho mỗi query
- [ ] Có Text Domain cho đa ngôn ngữ
- [ ] Không có `error_log()`, `var_dump()`, `print_r()` trong code production
- [ ] Không hardcode đường dẫn (dùng `plugin_dir_path()`, `plugin_dir_url()`)
- [ ] Tương thích với phiên bản PHP và WordPress yêu cầu

---

## Tham khảo

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Header Requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [Activation/Deactivation Hooks](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/)
- [Plugin Security](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
