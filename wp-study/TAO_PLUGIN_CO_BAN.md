# Hướng Dẫn Tạo Plugin WordPress Cơ Bản

## Mục Lục

1. [Giới Thiệu](#1-giới-thiệu)
2. [Cấu Trúc Plugin Cơ Bản](#2-cấu-trúc-plugin-cơ-bản)
3. [Tạo Plugin Đầu Tiên - Hello World](#3-tạo-plugin-đầu-tiên---hello-world)
4. [Plugin Headers](#4-plugin-headers)
5. [Activation và Deactivation Hooks](#5-activation-và-deactivation-hooks)
6. [Uninstall Plugin](#6-uninstall-plugin)
7. [Tạo Menu Admin](#7-tạo-menu-admin)
8. [Settings API](#8-settings-api)
9. [Shortcodes](#9-shortcodes)
10. [Widgets](#10-widgets)
11. [Enqueue Scripts và Styles](#11-enqueue-scripts-và-styles)
12. [AJAX trong Plugin](#12-ajax-trong-plugin)
13. [Custom Post Types](#13-custom-post-types)
14. [Nonce và Security](#14-nonce-và-security)
15. [Internationalization (i18n)](#15-internationalization-i18n)
16. [Ví Dụ Plugin Hoàn Chỉnh - CRUD](#16-ví-dụ-plugin-hoàn-chỉnh---crud)
17. [Best Practices](#17-best-practices)
18. [Debug Plugin](#18-debug-plugin)

---

## 1. Giới Thiệu

### Plugin là gì?

Plugin là một đoạn chương trình PHP mở rộng chức năng của WordPress mà không cần sửa đổi core. Plugin có thể thêm tính năng mới, thay đổi hành vi mặc định, hoặc tích hợp với dịch vụ bên ngoài.

### Tại sao cần viết plugin?

- **Tách biệt logic:** Giữ code riêng biệt với theme và core
- **Tái sử dụng:** Dùng lại trên nhiều website
- **Cập nhật an toàn:** Không mất code khi cập nhật WordPress hoặc theme
- **Chia sẻ:** Phân phối cho cộng đồng hoặc bán thương mại

### Plugin được lưu ở đâu?

Tất cả plugin nằm trong thư mục `wp-content/plugins/`.

---

## 2. Cấu Trúc Plugin Cơ Bản

### Plugin đơn file

```
wp-content/plugins/
└── my-plugin.php          # Plugin chỉ có 1 file
```

### Plugin nhiều file (khuyến dùng)

```
wp-content/plugins/
└── my-plugin/
    ├── my-plugin.php       # File chính (entry point)
    ├── uninstall.php       # Xử lý khi gỡ bỏ plugin
    ├── readme.txt          # Mô tả plugin (cho WordPress.org)
    ├── includes/
    │   ├── class-my-plugin.php
    │   ├── class-my-plugin-admin.php
    │   └── class-my-plugin-public.php
    ├── admin/
    │   ├── css/
    │   │   └── admin-style.css
    │   ├── js/
    │   │   └── admin-script.js
    │   └── views/
    │       └── settings-page.php
    ├── public/
    │   ├── css/
    │   │   └── public-style.css
    │   └── js/
    │       └── public-script.js
    ├── languages/
    │   └── my-plugin-vi.po
    └── templates/
        └── shortcode-template.php
```

---

## 3. Tạo Plugin Đầu Tiên - Hello World

### Bước 1: Tạo thư mục plugin

Tạo thư mục `wp-content/plugins/hello-world/`

### Bước 2: Tạo file chính

```php
<?php
/**
 * Plugin Name: Hello World
 * Plugin URI:  https://example.com/hello-world
 * Description: Plugin đầu tiên - Hiển thị "Hello World" trong admin.
 * Version:     1.0.0
 * Author:      Ten Cua Ban
 * Author URI:  https://example.com
 * License:     GPL v2 or later
 * Text Domain: hello-world
 * Domain Path: /languages
 */

// Ngan truy cap truc tiep vao file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Dinh nghia hang so
define( 'HELLO_WORLD_VERSION', '1.0.0' );
define( 'HELLO_WORLD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HELLO_WORLD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Hiển thị thông báo "Hello World" trong admin dashboard
 */
function hello_world_admin_notice() {
    echo '<div class="notice notice-info"><p>Hello World! Plugin đang hoạt động.</p></div>';
}
add_action( 'admin_notices', 'hello_world_admin_notice' );
```

### Bước 3: Kích hoạt plugin

Vào **Plugins > Installed Plugins** trong admin, tìm "Hello World" và click **Activate**.

---

## 4. Plugin Headers

Plugin headers là khối comment ở đầu file chính, WordPress đọc các trường này để hiển thị thông tin plugin.

```php
<?php
/**
 * Plugin Name:       Tên Plugin (bắt buộc)
 * Plugin URI:        https://example.com/plugin
 * Description:       Mô tả ngắn gọn về plugin (bắt buộc)
 * Version:           1.0.0
 * Requires at least: 6.0          // Phiên bản WP tối thiểu
 * Requires PHP:      8.0          // Phiên bản PHP tối thiểu
 * Author:            Tên tác giả
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-plugin    // Dùng cho đa ngôn ngữ
 * Domain Path:       /languages   // Thư mục chứa file ngôn ngữ
 * Network:           true         // Hỗ trợ multisite (tùy chọn)
 * Update URI:        https://example.com/update
 */
```

| Trường | Bắt buộc | Mô tả |
|--------|----------|-------|
| Plugin Name | Có | Tên hiển thị trong admin |
| Description | Có | Mô tả chức năng plugin |
| Version | Nên có | Phiên bản hiện tại |
| Author | Nên có | Tác giả plugin |
| Text Domain | Nên có | Identifier cho đa ngôn ngữ |
| License | Nên có | Giấy phép sử dụng |

---

## 5. Activation và Deactivation Hooks

### Activation Hook

Chạy một lần khi plugin được kích hoạt. Dùng để:
- Tạo bảng database
- Thêm option mặc định
- Flush rewrite rules
- Kiểm tra yêu cầu (PHP version, extension, ...)

```php
/**
 * Xử lý khi kích hoạt plugin
 */
function hello_world_activate() {
    // Tạo bảng database
    global $wpdb;
    $table_name = $wpdb->prefix . 'hello_world';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Lưu phiên bản database
    add_option( 'hello_world_db_version', '1.0' );

    // Thêm option mặc định
    add_option( 'hello_world_settings', array(
        'display_message' => true,
        'message_text'    => 'Hello World!',
    ) );

    // Flush rewrite rules (nếu có custom post type)
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hello_world_activate' );
```

### Deactivation Hook

Chạy khi plugin bị vô hiệu hóa. Dùng để:
- Xóa cron jobs
- Flush rewrite rules
- Xóa cache tạm thời

```php
/**
 * Xử lý khi vô hiệu hóa plugin
 */
function hello_world_deactivate() {
    // Xóa scheduled cron events
    $timestamp = wp_next_scheduled( 'hello_world_cron_event' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'hello_world_cron_event' );
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hello_world_deactivate' );
```

---

## 6. Uninstall Plugin

Khi người dùng xóa plugin, cần dọn dẹp dữ liệu.

### Cách 1: File uninstall.php (khuyến dùng)

Tạo file `uninstall.php` ở thư mục gốc của plugin:

```php
<?php
// Kiểm tra WordPress có gọi file này không
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xóa options
delete_option( 'hello_world_settings' );
delete_option( 'hello_world_db_version' );

// Xóa bảng database
global $wpdb;
$table_name = $wpdb->prefix . 'hello_world';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Xóa user meta (nếu có)
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'hello_world_%'" );

// Xóa transients
delete_transient( 'hello_world_cache' );
```

### Cách 2: register_uninstall_hook

```php
register_uninstall_hook( __FILE__, 'hello_world_uninstall' );

function hello_world_uninstall() {
    delete_option( 'hello_world_settings' );
}
```

---

## 7. Tạo Menu Admin

### Menu chính (Top-level menu)

```php
/**
 * Thêm menu vào admin sidebar
 */
function hello_world_admin_menu() {
    // Menu chính
    add_menu_page(
        'Hello World Settings',     // Tiêu đề trang (title tag)
        'Hello World',              // Tên menu (hiển thị trong sidebar)
        'manage_options',           // Capability cần thiết
        'hello-world',              // Menu slug (URL identifier)
        'hello_world_settings_page', // Callback function render trang
        'dashicons-admin-generic',  // Icon (dashicons hoặc URL)
        30                          // Vị trí trong menu
    );

    // Sub-menu
    add_submenu_page(
        'hello-world',              // Parent slug
        'Danh Sách',                // Tiêu đề trang
        'Danh Sách',                // Tên menu
        'manage_options',           // Capability
        'hello-world',              // Menu slug (trùng với parent để làm default)
        'hello_world_settings_page' // Callback
    );

    add_submenu_page(
        'hello-world',
        'Thêm Mới',
        'Thêm Mới',
        'manage_options',
        'hello-world-add',
        'hello_world_add_page'
    );
}
add_action( 'admin_menu', 'hello_world_admin_menu' );

/**
 * Render trang settings
 */
function hello_world_settings_page() {
    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Đây là trang settings của plugin Hello World.</p>
    </div>
    <?php
}

/**
 * Render trang thêm mới
 */
function hello_world_add_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Thêm Mới</h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'hello_world_add', 'hello_world_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="name">Tên:</label></th>
                    <td><input type="text" name="name" id="name" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="message">Nội dung:</label></th>
                    <td><textarea name="message" id="message" rows="5" class="large-text"></textarea></td>
                </tr>
            </table>
            <?php submit_button( 'Lưu' ); ?>
        </form>
    </div>
    <?php
}
```

### Dashicons phổ biến

```
dashicons-admin-home        - Trang chủ
dashicons-admin-post        - Bài viết
dashicons-admin-page        - Trang
dashicons-admin-settings    - Cài đặt
dashicons-admin-tools       - Công cụ
dashicons-admin-users       - Người dùng
dashicons-admin-plugins     - Plugin
dashicons-admin-generic     - Chung
dashicons-chart-bar         - Biểu đồ
dashicons-cart              - Giỏ hàng
dashicons-email             - Email
```

---

## 8. Settings API

WordPress cung cấp Settings API để tạo trang cài đặt một cách an toàn và chuẩn hóa.

```php
/**
 * Đăng ký settings
 */
function hello_world_register_settings() {
    // Đăng ký setting group
    register_setting(
        'hello_world_options',       // Option group
        'hello_world_settings',      // Option name (lưu trong wp_options)
        array(
            'type'              => 'array',
            'sanitize_callback' => 'hello_world_sanitize_settings',
            'default'           => array(
                'display_message' => true,
                'message_text'    => 'Hello World!',
                'message_color'   => '#000000',
            ),
        )
    );

    // Thêm section
    add_settings_section(
        'hello_world_general',           // Section ID
        'Cài Đặt Chung',                 // Tiêu đề section
        'hello_world_section_callback',  // Callback mô tả
        'hello-world-settings'           // Page slug
    );

    // Thêm field: Hiển thị thông báo
    add_settings_field(
        'display_message',                   // Field ID
        'Hiển thị thông báo',                // Label
        'hello_world_checkbox_callback',     // Callback render field
        'hello-world-settings',              // Page slug
        'hello_world_general',               // Section ID
        array( 'field' => 'display_message' ) // Args truyền vào callback
    );

    // Thêm field: Nội dung thông báo
    add_settings_field(
        'message_text',
        'Nội dung thông báo',
        'hello_world_text_callback',
        'hello-world-settings',
        'hello_world_general',
        array( 'field' => 'message_text' )
    );

    // Thêm field: Màu sắc
    add_settings_field(
        'message_color',
        'Màu sắc',
        'hello_world_color_callback',
        'hello-world-settings',
        'hello_world_general',
        array( 'field' => 'message_color' )
    );
}
add_action( 'admin_init', 'hello_world_register_settings' );

/**
 * Callback cho section
 */
function hello_world_section_callback() {
    echo '<p>Cấu hình các tùy chọn hiển thị của plugin.</p>';
}

/**
 * Render checkbox field
 */
function hello_world_checkbox_callback( $args ) {
    $options = get_option( 'hello_world_settings' );
    $checked = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : false;
    ?>
    <input type="checkbox"
           name="hello_world_settings[<?php echo esc_attr( $args['field'] ); ?>]"
           value="1"
           <?php checked( $checked, true ); ?> />
    <?php
}

/**
 * Render text field
 */
function hello_world_text_callback( $args ) {
    $options = get_option( 'hello_world_settings' );
    $value = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : '';
    ?>
    <input type="text"
           name="hello_world_settings[<?php echo esc_attr( $args['field'] ); ?>]"
           value="<?php echo esc_attr( $value ); ?>"
           class="regular-text" />
    <?php
}

/**
 * Render color picker field
 */
function hello_world_color_callback( $args ) {
    $options = get_option( 'hello_world_settings' );
    $value = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : '#000000';
    ?>
    <input type="color"
           name="hello_world_settings[<?php echo esc_attr( $args['field'] ); ?>]"
           value="<?php echo esc_attr( $value ); ?>" />
    <?php
}

/**
 * Sanitize settings truoc khi luu
 */
function hello_world_sanitize_settings( $input ) {
    $sanitized = array();
    $sanitized['display_message'] = isset( $input['display_message'] ) ? true : false;
    $sanitized['message_text']    = sanitize_text_field( $input['message_text'] ?? '' );
    $sanitized['message_color']   = sanitize_hex_color( $input['message_color'] ?? '#000000' );
    return $sanitized;
}

/**
 * Render trang settings voi Settings API
 */
function hello_world_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'hello_world_options' );      // Output nonce + hidden fields
            do_settings_sections( 'hello-world-settings' ); // Render sections + fields
            submit_button( 'Luu Cai Dat' );
            ?>
        </form>
    </div>
    <?php
}
```

---

## 9. Shortcodes

Shortcode cho phep nguoi dung chen noi dung dong vao bai viet/trang bang cu phap `[shortcode]`.

### Shortcode don gian

```php
/**
 * Shortcode hien thi loi chao
 * Su dung: [hello_world]
 */
function hello_world_shortcode( $atts ) {
    // Merge attributes voi gia tri mac dinh
    $atts = shortcode_atts( array(
        'name'  => 'World',
        'color' => '#333333',
    ), $atts, 'hello_world' );

    // Luon return, KHONG echo
    return sprintf(
        '<p style="color: %s;">Hello, %s!</p>',
        esc_attr( $atts['color'] ),
        esc_html( $atts['name'] )
    );
}
add_shortcode( 'hello_world', 'hello_world_shortcode' );

// Su dung trong bai viet:
// [hello_world]
// [hello_world name="Viet Nam" color="#ff0000"]
```

### Shortcode voi noi dung ben trong (enclosing)

```php
/**
 * Shortcode boc noi dung trong hop
 * Su dung: [hello_box title="Tieu de"]Noi dung[/hello_box]
 */
function hello_box_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'title'  => '',
        'class'  => 'default',
    ), $atts, 'hello_box' );

    $output = '<div class="hello-box hello-box--' . esc_attr( $atts['class'] ) . '">';
    if ( ! empty( $atts['title'] ) ) {
        $output .= '<h3>' . esc_html( $atts['title'] ) . '</h3>';
    }
    // do_shortcode() de xu ly shortcode long nhau
    $output .= '<div class="hello-box__content">' . do_shortcode( $content ) . '</div>';
    $output .= '</div>';

    return $output;
}
add_shortcode( 'hello_box', 'hello_box_shortcode' );
```

---

## 10. Widgets

### Tao Widget (Classic Widget)

```php
/**
 * Widget hien thi thong bao Hello World
 */
class Hello_World_Widget extends WP_Widget {

    /**
     * Khoi tao widget
     */
    public function __construct() {
        parent::__construct(
            'hello_world_widget',           // Widget ID
            'Hello World Widget',           // Ten widget
            array(
                'description' => 'Hien thi loi chao Hello World.',
                'classname'   => 'hello-world-widget',
            )
        );
    }

    /**
     * Hien thi widget o frontend
     */
    public function widget( $args, $instance ) {
        $title   = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $message = $instance['message'] ?? 'Hello World!';

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo '<p>' . esc_html( $message ) . '</p>';
        echo $args['after_widget'];
    }

    /**
     * Form settings trong admin
     */
    public function form( $instance ) {
        $title   = $instance['title'] ?? 'Hello World';
        $message = $instance['message'] ?? 'Hello World!';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Tieu de:</label>
            <input type="text"
                   class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'message' ) ); ?>">Thong bao:</label>
            <textarea class="widefat"
                      id="<?php echo esc_attr( $this->get_field_id( 'message' ) ); ?>"
                      name="<?php echo esc_attr( $this->get_field_name( 'message' ) ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
        </p>
        <?php
    }

    /**
     * Luu settings
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']   = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['message'] = sanitize_textarea_field( $new_instance['message'] ?? '' );
        return $instance;
    }
}

/**
 * Dang ky widget
 */
function hello_world_register_widget() {
    register_widget( 'Hello_World_Widget' );
}
add_action( 'widgets_init', 'hello_world_register_widget' );
```

---

## 11. Enqueue Scripts va Styles

### Quy tac quan trong

- **KHONG** dung `<link>` hay `<script>` truc tiep trong code
- **LUON** dung `wp_enqueue_style()` va `wp_enqueue_script()`
- Dieu nay dam bao khong conflict va ho tro caching tot

```php
/**
 * Enqueue styles va scripts cho frontend
 */
function hello_world_enqueue_public() {
    // CSS
    wp_enqueue_style(
        'hello-world-public',                              // Handle (ten dinh danh)
        HELLO_WORLD_PLUGIN_URL . 'public/css/style.css',   // URL file
        array(),                                            // Dependencies
        HELLO_WORLD_VERSION                                 // Version (cache busting)
    );

    // JavaScript
    wp_enqueue_script(
        'hello-world-public',
        HELLO_WORLD_PLUGIN_URL . 'public/js/script.js',
        array( 'jquery' ),         // Phu thuoc vao jQuery
        HELLO_WORLD_VERSION,
        true                       // Load o footer (truoc </body>)
    );

    // Truyen du lieu tu PHP sang JavaScript
    wp_localize_script( 'hello-world-public', 'helloWorldData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'hello_world_nonce' ),
        'siteUrl' => home_url(),
    ) );
}
add_action( 'wp_enqueue_scripts', 'hello_world_enqueue_public' );

/**
 * Enqueue styles va scripts cho admin
 */
function hello_world_enqueue_admin( $hook ) {
    // Chi load tren trang cua plugin de tranh anh huong cac trang khac
    if ( 'toplevel_page_hello-world' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'hello-world-admin',
        HELLO_WORLD_PLUGIN_URL . 'admin/css/admin-style.css',
        array(),
        HELLO_WORLD_VERSION
    );

    wp_enqueue_script(
        'hello-world-admin',
        HELLO_WORLD_PLUGIN_URL . 'admin/js/admin-script.js',
        array( 'jquery' ),
        HELLO_WORLD_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'hello_world_enqueue_admin' );
```

---

## 12. AJAX Trong Plugin

### Buoc 1: Dang ky AJAX handler trong PHP

```php
/**
 * Xu ly AJAX request - Lay danh sach items
 * wp_ajax_{action} - cho user da dang nhap
 * wp_ajax_nopriv_{action} - cho user chua dang nhap
 */
function hello_world_get_items() {
    // Kiem tra nonce
    check_ajax_referer( 'hello_world_nonce', 'nonce' );

    // Kiem tra quyen
    if ( ! current_user_can( 'read' ) ) {
        wp_send_json_error( array( 'message' => 'Khong co quyen truy cap.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'hello_world';
    $items = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );

    wp_send_json_success( array(
        'items' => $items,
        'total' => count( $items ),
    ) );
}
add_action( 'wp_ajax_hello_world_get_items', 'hello_world_get_items' );
add_action( 'wp_ajax_nopriv_hello_world_get_items', 'hello_world_get_items' );

/**
 * AJAX - Them item moi
 */
function hello_world_add_item() {
    check_ajax_referer( 'hello_world_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Khong co quyen.' ) );
    }

    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( empty( $name ) ) {
        wp_send_json_error( array( 'message' => 'Ten khong duoc de trong.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'hello_world';

    $result = $wpdb->insert(
        $table_name,
        array(
            'name'    => $name,
            'message' => $message,
        ),
        array( '%s', '%s' )
    );

    if ( $result ) {
        wp_send_json_success( array(
            'message' => 'Them thanh cong!',
            'id'      => $wpdb->insert_id,
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Loi khi them du lieu.' ) );
    }
}
add_action( 'wp_ajax_hello_world_add_item', 'hello_world_add_item' );
```

### Buoc 2: Goi AJAX tu JavaScript

```javascript
// public/js/script.js
(function($) {
    'use strict';

    // Lay danh sach items
    function getItems() {
        $.ajax({
            url: helloWorldData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hello_world_get_items',  // Phai trung voi ten hook
                nonce: helloWorldData.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Items:', response.data.items);
                } else {
                    console.error('Loi:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    // Them item moi
    $('#hello-world-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: helloWorldData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hello_world_add_item',
                nonce: helloWorldData.nonce,
                name: $('#name').val(),
                message: $('#message').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    getItems(); // Reload danh sach
                } else {
                    alert('Loi: ' + response.data.message);
                }
            }
        });
    });

    // Load items khi trang san sang
    $(document).ready(function() {
        getItems();
    });

})(jQuery);
```

---

## 13. Custom Post Types

```php
/**
 * Dang ky Custom Post Type "Product"
 */
function hello_world_register_post_types() {
    $labels = array(
        'name'               => 'San Pham',
        'singular_name'      => 'San Pham',
        'menu_name'          => 'San Pham',
        'add_new'            => 'Them Moi',
        'add_new_item'       => 'Them San Pham Moi',
        'edit_item'          => 'Sua San Pham',
        'new_item'           => 'San Pham Moi',
        'view_item'          => 'Xem San Pham',
        'search_items'       => 'Tim San Pham',
        'not_found'          => 'Khong tim thay san pham',
        'not_found_in_trash' => 'Khong co san pham trong thung rac',
    );

    $args = array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array( 'slug' => 'san-pham' ),
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'menu_icon'    => 'dashicons-cart',
        'show_in_rest' => true,   // Ho tro Gutenberg va REST API
    );

    register_post_type( 'product', $args );
}
add_action( 'init', 'hello_world_register_post_types' );

/**
 * Dang ky Taxonomy cho Product
 */
function hello_world_register_taxonomies() {
    register_taxonomy( 'product_category', 'product', array(
        'labels'       => array(
            'name'          => 'Danh Muc San Pham',
            'singular_name' => 'Danh Muc',
            'add_new_item'  => 'Them Danh Muc Moi',
        ),
        'public'       => true,
        'hierarchical' => true,   // true = giong Category, false = giong Tag
        'rewrite'      => array( 'slug' => 'danh-muc-san-pham' ),
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'hello_world_register_taxonomies' );
```

---

## 14. Nonce va Security

### Nonce la gi?

**Nonce** (Number used once) la mot token bao mat de bao ve chong CSRF (Cross-Site Request Forgery).

### Su dung trong Form

```php
// Tao form voi nonce
function hello_world_form() {
    ?>
    <form method="post" action="">
        <?php
        // Tao nonce field
        wp_nonce_field( 'hello_world_save', 'hello_world_nonce' );
        ?>
        <input type="text" name="data" />
        <input type="submit" value="Luu" />
    </form>
    <?php
}

// Xu ly form submission
function hello_world_handle_form() {
    // Kiem tra nonce
    if ( ! isset( $_POST['hello_world_nonce'] ) ||
         ! wp_verify_nonce( $_POST['hello_world_nonce'], 'hello_world_save' ) ) {
        wp_die( 'Loi bao mat: Nonce khong hop le.' );
    }

    // Kiem tra quyen
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Ban khong co quyen thuc hien hanh dong nay.' );
    }

    // Sanitize du lieu
    $data = sanitize_text_field( $_POST['data'] ?? '' );

    // Xu ly du lieu...
}
```

### Su dung trong URL

```php
// Tao URL voi nonce
$delete_url = wp_nonce_url(
    admin_url( 'admin.php?page=hello-world&action=delete&id=' . $item_id ),
    'hello_world_delete_' . $item_id
);

// Kiem tra nonce tu URL
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    check_admin_referer( 'hello_world_delete_' . $_GET['id'] );
    // Xu ly xoa...
}
```

### Capability Check

```php
// Cac capability pho bien
current_user_can( 'manage_options' );    // Administrator
current_user_can( 'edit_posts' );        // Editor, Author, Contributor
current_user_can( 'publish_posts' );     // Editor, Author
current_user_can( 'read' );             // Tat ca user da dang nhap
current_user_can( 'edit_post', $post_id ); // Kiem tra quyen tren bai viet cu the

// Su dung trong code
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Ban khong co quyen truy cap trang nay.' );
}
```

---

## 15. Internationalization (i18n)

### Cac ham chinh

```php
// __() - Tra ve chuoi da dich
$text = __( 'Hello World', 'hello-world' );

// _e() - Echo chuoi da dich
_e( 'Hello World', 'hello-world' );

// _n() - So it / so nhieu
$text = _n(
    '%d san pham',      // So it
    '%d san pham',      // So nhieu
    $count,             // So luong
    'hello-world'       // Text domain
);

// _x() - Chuoi co ngu canh
$text = _x( 'Post', 'verb', 'hello-world' );  // Dang (post = gui)
$text = _x( 'Post', 'noun', 'hello-world' );  // Bai viet (post = bai)

// esc_html__() - Escape + dich
echo esc_html__( 'Hello World', 'hello-world' );

// esc_html_e() - Escape + dich + echo
esc_html_e( 'Hello World', 'hello-world' );

// sprintf voi dich
$text = sprintf(
    __( 'Xin chao, %s!', 'hello-world' ),
    $user_name
);
```

### Load Text Domain

```php
/**
 * Load file ngon ngu cho plugin
 */
function hello_world_load_textdomain() {
    load_plugin_textdomain(
        'hello-world',                          // Text domain
        false,                                   // Deprecated
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'  // Duong dan
    );
}
add_action( 'plugins_loaded', 'hello_world_load_textdomain' );
```

---

## 16. Vi Du Plugin Hoan Chinh - CRUD

Duoi day la vi du plugin CRUD (Create, Read, Update, Delete) don gian:

### File chinh: my-crud-plugin.php

```php
<?php
/**
 * Plugin Name: My CRUD Plugin
 * Description: Plugin CRUD don gian voi custom table
 * Version:     1.0.0
 * Author:      Developer
 * Text Domain: my-crud
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MY_CRUD_VERSION', '1.0.0' );
define( 'MY_CRUD_DIR', plugin_dir_path( __FILE__ ) );
define( 'MY_CRUD_URL', plugin_dir_url( __FILE__ ) );

// --- ACTIVATION ---
register_activation_hook( __FILE__, 'my_crud_activate' );
function my_crud_activate() {
    global $wpdb;
    $table = $wpdb->prefix . 'my_crud_contacts';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) DEFAULT '',
        note text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    add_option( 'my_crud_db_version', '1.0' );
}

// --- ADMIN MENU ---
add_action( 'admin_menu', 'my_crud_admin_menu' );
function my_crud_admin_menu() {
    add_menu_page(
        'Quan Ly Lien He',
        'Lien He',
        'manage_options',
        'my-crud',
        'my_crud_list_page',
        'dashicons-groups',
        30
    );
    add_submenu_page( 'my-crud', 'Them Moi', 'Them Moi', 'manage_options', 'my-crud-add', 'my_crud_add_page' );
}

// --- TRANG DANH SACH ---
function my_crud_list_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . 'my_crud_contacts';

    // Xu ly xoa
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'my_crud_delete_' . $_GET['id'] );
        $wpdb->delete( $table, array( 'id' => intval( $_GET['id'] ) ), array( '%d' ) );
        echo '<div class="notice notice-success"><p>Da xoa thanh cong.</p></div>';
    }

    $items = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" );
    ?>
    <div class="wrap">
        <h1>Quan Ly Lien He <a href="<?php echo admin_url( 'admin.php?page=my-crud-add' ); ?>" class="page-title-action">Them Moi</a></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ten</th>
                    <th>Email</th>
                    <th>Dien Thoai</th>
                    <th>Ngay Tao</th>
                    <th>Hanh Dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $items ) : ?>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html( $item->id ); ?></td>
                            <td><?php echo esc_html( $item->name ); ?></td>
                            <td><?php echo esc_html( $item->email ); ?></td>
                            <td><?php echo esc_html( $item->phone ); ?></td>
                            <td><?php echo esc_html( $item->created_at ); ?></td>
                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=my-crud-add&id=' . $item->id ); ?>">Sua</a> |
                                <a href="<?php echo wp_nonce_url(
                                    admin_url( 'admin.php?page=my-crud&action=delete&id=' . $item->id ),
                                    'my_crud_delete_' . $item->id
                                ); ?>" onclick="return confirm('Ban co chac muon xoa?');" style="color:red;">Xoa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6">Chua co du lieu.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// --- TRANG THEM/SUA ---
function my_crud_add_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . 'my_crud_contacts';
    $editing = false;
    $item = null;

    // Kiem tra co phai dang sua khong
    if ( isset( $_GET['id'] ) ) {
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['id'] ) ) );
        if ( $item ) $editing = true;
    }

    // Xu ly luu du lieu
    if ( isset( $_POST['submit'] ) ) {
        check_admin_referer( 'my_crud_save', 'my_crud_nonce' );

        $data = array(
            'name'  => sanitize_text_field( $_POST['name'] ?? '' ),
            'email' => sanitize_email( $_POST['email'] ?? '' ),
            'phone' => sanitize_text_field( $_POST['phone'] ?? '' ),
            'note'  => sanitize_textarea_field( $_POST['note'] ?? '' ),
        );
        $format = array( '%s', '%s', '%s', '%s' );

        if ( $editing ) {
            $wpdb->update( $table, $data, array( 'id' => $item->id ), $format, array( '%d' ) );
            echo '<div class="notice notice-success"><p>Da cap nhat.</p></div>';
            $item = (object) array_merge( (array) $item, $data );
        } else {
            $wpdb->insert( $table, $data, $format );
            echo '<div class="notice notice-success"><p>Da them moi.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $editing ? 'Sua Lien He' : 'Them Lien He Moi'; ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'my_crud_save', 'my_crud_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="name">Ten:</label></th>
                    <td><input type="text" name="name" id="name" class="regular-text"
                               value="<?php echo esc_attr( $item->name ?? '' ); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="email">Email:</label></th>
                    <td><input type="email" name="email" id="email" class="regular-text"
                               value="<?php echo esc_attr( $item->email ?? '' ); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="phone">Dien thoai:</label></th>
                    <td><input type="text" name="phone" id="phone" class="regular-text"
                               value="<?php echo esc_attr( $item->phone ?? '' ); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="note">Ghi chu:</label></th>
                    <td><textarea name="note" id="note" rows="5" class="large-text"><?php
                        echo esc_textarea( $item->note ?? '' );
                    ?></textarea></td>
                </tr>
            </table>
            <?php submit_button( $editing ? 'Cap Nhat' : 'Them Moi' ); ?>
        </form>
    </div>
    <?php
}

// --- UNINSTALL ---
// Xem file uninstall.php
```

---

## 17. Best Practices

### Coding Standards

```php
// 1. LUON dung prefix de tranh conflict
function myplugin_do_something() { }  // Tot
function do_something() { }           // Xau - de conflict

// 2. LUON kiem tra ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 3. LUON sanitize input
$name = sanitize_text_field( $_POST['name'] );
$email = sanitize_email( $_POST['email'] );
$url = esc_url_raw( $_POST['url'] );
$html = wp_kses_post( $_POST['content'] );

// 4. LUON escape output
echo esc_html( $name );          // Trong text
echo esc_attr( $value );         // Trong attribute
echo esc_url( $url );            // Trong URL
echo wp_kses_post( $content );   // HTML an toan

// 5. LUON su dung prepare cho SQL
$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );
// KHONG BAO GIO:
$wpdb->query( "SELECT * FROM $table WHERE id = $id" );  // SQL Injection!

// 6. Su dung OOP khi plugin phuc tap
class My_Plugin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        // Khoi tao plugin
    }
}
My_Plugin::get_instance();
```

---

## 18. Debug Plugin

### Bat Debug Mode

Trong `wp-config.php`:

```php
// Bat debug mode
define( 'WP_DEBUG', true );

// Ghi log vao file wp-content/debug.log
define( 'WP_DEBUG_LOG', true );

// Tat hien thi loi tren trang (production)
define( 'WP_DEBUG_DISPLAY', false );

// Ghi log tat ca SQL queries
define( 'SAVEQUERIES', true );
```

### Su dung error_log

```php
// Ghi log don gian
error_log( 'My plugin: Bat dau xu ly' );

// Ghi log array/object
error_log( print_r( $data, true ) );

// Ghi log co dieu kien
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'Debug: ' . $variable );
}
```

### Plugin ho tro debug

- **Query Monitor** - Xem queries, hooks, conditionals, HTTP requests
- **Debug Bar** - Thanh debug trong admin bar
- **Log Deprecated Notices** - Canh bao ham/tham so da cu

### Kiem tra error voi WP_Error

```php
$result = wp_insert_post( $post_data );

if ( is_wp_error( $result ) ) {
    $error_message = $result->get_error_message();
    error_log( 'Loi tao post: ' . $error_message );
} else {
    $post_id = $result;
}
```

---

## Tai Lieu Tham Khao

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Security](https://developer.wordpress.org/plugins/security/)
- [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/)
- [Shortcodes API](https://developer.wordpress.org/plugins/shortcodes/)
