# Menu và Settings API trong WordPress Plugin

## Mục lục

1. [Tạo Admin Menu](#1-tao-admin-menu)
2. [Tạo Submenu](#2-tao-submenu)
3. [Settings API chi tiết](#3-settings-api-chi-tiet)
4. [Tạo trang Options hoàn chỉnh](#4-tao-trang-options-hoan-chinh)
5. [Tabs trong Settings Page](#5-tabs-trong-settings-page)
6. [Các loại field](#6-cac-loai-field)
7. [Validate và Sanitize Settings](#7-validate-va-sanitize-settings)
8. [Code ví dụ: Plugin Settings hoàn chỉnh](#8-code-vi-du-plugin-settings-hoan-chinh)
9. [Best Practices](#9-best-practices)

---

## 1. Tạo Admin Menu

WordPress cho phép thêm menu vào sidebar Admin thông qua hook `admin_menu`.

### add_menu_page - Thêm Menu chính

```php
<?php
/**
 * Plugin Name: Menu Demo
 * Description: Demo tạo menu admin.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Thêm menu chính vào sidebar Admin.
 * Hook 'admin_menu' chạy khi WordPress đang xây dựng menu admin.
 */
add_action( 'admin_menu', 'md_add_admin_menu' );

function md_add_admin_menu() {
    /**
     * add_menu_page() - Tạo 1 menu cấp cao nhất (top-level menu)
     *
     * @param string   $page_title  Tiêu đề trang (thẻ <title>)
     * @param string   $menu_title  Tên hiển thị trên menu sidebar
     * @param string   $capability  Quyền cần thiết để thấy menu
     * @param string   $menu_slug   Slug duy nhất (dùng làm URL: admin.php?page=slug)
     * @param callable $callback    Hàm render nội dung trang
     * @param string   $icon_url    Icon của menu (Dashicons, SVG, hoặc URL)
     * @param int      $position    Vị trí trong sidebar (số càng nhỏ càng lên trên)
     *
     * @return string  Hook suffix của trang (dùng để chỉ load CSS/JS trên trang này)
     */
    $hook_suffix = add_menu_page(
        'My Plugin Dashboard',              // page_title
        'My Plugin',                         // menu_title
        'manage_options',                    // capability (chỉ admin thấy)
        'my-plugin-dashboard',               // menu_slug
        'md_dashboard_page',                 // callback function
        'dashicons-admin-generic',           // icon (Dashicons)
        30                                   // position (sau Comments = 25)
    );

    // Dùng hook_suffix để chỉ load CSS/JS trên trang này
    add_action( "load-{$hook_suffix}", 'md_load_dashboard_assets' );
}

function md_dashboard_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Đây là trang Dashboard của plugin.</p>
    </div>
    <?php
}

function md_load_dashboard_assets() {
    // Code này chỉ chạy khi trang Dashboard của plugin được load
    // Phù hợp để enqueue CSS/JS chỉ cho trang này
}
```

### Vị trí menu (Position)

```
2    - Dashboard
4    - Separator
5    - Posts
10   - Media
15   - Links
20   - Pages
25   - Comments
59   - Separator
60   - Appearance
65   - Plugins
70   - Users
75   - Tools
80   - Settings
99   - Separator
```

### Dashicons - Icon cho Menu

```php
<?php
// Một số Dashicons thường dùng:
// 'dashicons-admin-generic'     - Bánh răng
// 'dashicons-admin-tools'       - Công cụ
// 'dashicons-chart-bar'         - Biểu đồ
// 'dashicons-email'             - Email
// 'dashicons-calendar'          - Lịch
// 'dashicons-cart'              - Giỏ hàng
// 'dashicons-store'             - Cửa hàng
// 'dashicons-groups'            - Nhóm người
// 'dashicons-shield'            - Bảo mật
// 'dashicons-megaphone'         - Thông báo

// Dùng SVG base64
$icon = 'data:image/svg+xml;base64,' . base64_encode('<svg>...</svg>');

// Dùng URL hình ảnh
$icon = plugins_url( 'assets/icon.png', __FILE__ );

// Danh sách đầy đủ: https://developer.wordpress.org/resource/dashicons/
```

---

## 2. Tạo Submenu

### add_submenu_page - Thêm menu con

```php
<?php
add_action( 'admin_menu', 'md_add_menus' );

function md_add_menus() {
    // Menu chính
    add_menu_page(
        'My Plugin',
        'My Plugin',
        'manage_options',
        'my-plugin',               // parent_slug
        'md_main_page',
        'dashicons-admin-generic',
        30
    );

    /**
     * add_submenu_page() - Thêm submenu dưới menu chính
     *
     * @param string   $parent_slug  Slug của menu cha
     * @param string   $page_title   Tiêu đề trang
     * @param string   $menu_title   Tên hiển thị
     * @param string   $capability   Quyền cần thiết
     * @param string   $menu_slug    Slug duy nhất
     * @param callable $callback     Hàm render
     * @param int      $position     Vị trí trong submenu
     */

    // Submenu 1: Dashboard (thay thế tên mặc định của menu cha)
    // Khi tạo menu cha, WP tự động tạo 1 submenu cùng tên
    // Thêm submenu với parent_slug giống menu cha để đổi tên submenu đầu tiên
    add_submenu_page(
        'my-plugin',                // parent_slug = slug của menu cha
        'Dashboard',
        'Dashboard',                // Tên mới cho submenu đầu tiên
        'manage_options',
        'my-plugin',                // Slug GIỐNG menu cha => thay thế submenu đầu
        'md_main_page'
    );

    // Submenu 2: Settings
    add_submenu_page(
        'my-plugin',
        'Cài đặt Plugin',
        'Cài đặt',
        'manage_options',
        'my-plugin-settings',
        'md_settings_page'
    );

    // Submenu 3: Reports - chỉ Editor trở lên thấy
    add_submenu_page(
        'my-plugin',
        'Báo cáo',
        'Báo cáo',
        'edit_posts',               // Editor trở lên
        'my-plugin-reports',
        'md_reports_page'
    );

    // Submenu 4: Logs - chỉ Admin thấy
    add_submenu_page(
        'my-plugin',
        'Nhật ký',
        'Nhật ký',
        'manage_options',
        'my-plugin-logs',
        'md_logs_page'
    );
}

function md_main_page() {
    echo '<div class="wrap"><h1>Dashboard</h1></div>';
}

function md_settings_page() {
    echo '<div class="wrap"><h1>Cài đặt</h1></div>';
}

function md_reports_page() {
    echo '<div class="wrap"><h1>Báo cáo</h1></div>';
}

function md_logs_page() {
    echo '<div class="wrap"><h1>Nhật ký</h1></div>';
}
```

### Thêm submenu vào menu có sẵn của WordPress

```php
<?php
add_action( 'admin_menu', 'md_add_to_existing_menus' );

function md_add_to_existing_menus() {
    // Thêm vào menu Settings (Options)
    // Tương đương: add_options_page(...)
    add_submenu_page(
        'options-general.php',      // Parent slug của Settings
        'My Plugin Settings',
        'My Plugin',
        'manage_options',
        'my-plugin-settings',
        'md_settings_callback'
    );

    // Thêm vào menu Tools
    // Tương đương: add_management_page(...)
    add_submenu_page(
        'tools.php',                // Parent slug của Tools
        'My Plugin Tools',
        'My Plugin Tool',
        'manage_options',
        'my-plugin-tool',
        'md_tool_callback'
    );

    // Thêm vào menu Posts
    add_submenu_page(
        'edit.php',                 // Parent slug của Posts
        'Extra Post Settings',
        'Extra Settings',
        'edit_posts',
        'extra-post-settings',
        'md_extra_post_callback'
    );

    // Danh sách parent slugs có sẵn:
    // 'index.php'           - Dashboard
    // 'edit.php'             - Posts
    // 'upload.php'           - Media
    // 'edit.php?post_type=X' - Custom Post Type X
    // 'edit-comments.php'    - Comments
    // 'themes.php'           - Appearance
    // 'plugins.php'          - Plugins
    // 'users.php'            - Users
    // 'tools.php'            - Tools
    // 'options-general.php'  - Settings
}

// Các hàm shortcut tương đương:
// add_dashboard_page()    => add_submenu_page('index.php', ...)
// add_posts_page()        => add_submenu_page('edit.php', ...)
// add_media_page()        => add_submenu_page('upload.php', ...)
// add_pages_page()        => add_submenu_page('edit.php?post_type=page', ...)
// add_comments_page()     => add_submenu_page('edit-comments.php', ...)
// add_theme_page()        => add_submenu_page('themes.php', ...)
// add_plugins_page()      => add_submenu_page('plugins.php', ...)
// add_users_page()        => add_submenu_page('users.php', ...)
// add_management_page()   => add_submenu_page('tools.php', ...)
// add_options_page()      => add_submenu_page('options-general.php', ...)
```

---

## 3. Settings API chi tiết

Settings API là cách **chuẩn** của WordPress để tạo trang cài đặt. Nó giúp tự động hóa việc lưu, validate và hiển thị settings.

### Khái niệm chính

```
Settings API có 3 thành phần:

1. Setting (register_setting)
   - Đăng ký 1 option trong database (wp_options)
   - Định nghĩa hàm sanitize/validate

2. Section (add_settings_section)
   - Nhóm các fields lại với nhau
   - Có tiêu đề và mô tả

3. Field (add_settings_field)
   - Từng trường nhập liệu cụ thể
   - Text, checkbox, select, v.v.

Quan hệ:
Setting 1:N Section 1:N Field

Mỗi Setting chứa nhiều Sections
Mỗi Section chứa nhiều Fields
```

### Luồng hoạt động của Settings API

```
1. admin_init hook
   => register_setting()         : Đăng ký setting
   => add_settings_section()     : Thêm section
   => add_settings_field()       : Thêm field vào section

2. Trang Settings (callback)
   => <form action="options.php"> : Form gửi đến options.php
   => settings_fields()           : Output nonce + hidden fields
   => do_settings_sections()      : Render tất cả sections và fields
   => submit_button()             : Nút Submit

3. Khi Submit
   => WordPress tự động:
      - Kiểm tra nonce
      - Gọi hàm sanitize/validate
      - Lưu vào wp_options
      - Redirect về trang settings với thông báo
```

### Ví dụ cơ bản Settings API

```php
<?php
/**
 * Plugin Name: Settings API Demo
 * Description: Demo Settings API cơ bản.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === BƯỚC 1: Thêm menu ===
add_action( 'admin_menu', 'sad_add_menu' );

function sad_add_menu() {
    add_options_page(
        'Settings API Demo',
        'Settings Demo',
        'manage_options',
        'settings-api-demo',
        'sad_options_page'
    );
}

// === BƯỚC 2: Đăng ký Settings ===
add_action( 'admin_init', 'sad_register_settings' );

function sad_register_settings() {
    /**
     * register_setting() - Đăng ký 1 setting (1 option trong database)
     *
     * @param string $option_group  Nhóm setting (dùng trong settings_fields())
     * @param string $option_name   Tên option trong wp_options
     * @param array  $args          Tùy chọn: type, description, sanitize_callback, default
     */
    register_setting(
        'sad_options_group',         // option_group
        'sad_options',               // option_name (lưu trong wp_options)
        array(
            'type'              => 'array',
            'sanitize_callback' => 'sad_sanitize_options',
            'default'           => array(
                'site_name'   => '',
                'site_email'  => '',
                'per_page'    => 10,
                'show_header' => true,
            ),
        )
    );

    /**
     * add_settings_section() - Thêm 1 section (nhóm fields)
     *
     * @param string   $id        ID duy nhất của section
     * @param string   $title     Tiêu đề hiển thị
     * @param callable $callback  Hàm render mô tả (có thể null)
     * @param string   $page      Slug trang (khớp với menu_slug)
     */
    add_settings_section(
        'sad_general_section',           // section ID
        'Cài đặt chung',                // tiêu đề
        'sad_general_section_callback',  // hàm mô tả
        'settings-api-demo'              // page slug
    );

    /**
     * add_settings_field() - Thêm 1 trường nhập liệu
     *
     * @param string   $id        ID duy nhất
     * @param string   $title     Label của field
     * @param callable $callback  Hàm render input
     * @param string   $page      Slug trang
     * @param string   $section   ID của section
     * @param array    $args      Tham số truyền cho callback
     */
    add_settings_field(
        'sad_site_name',                 // field ID
        'Tên website',                   // label
        'sad_text_field_callback',       // hàm render
        'settings-api-demo',             // page slug
        'sad_general_section',           // section ID
        array(                           // args truyền cho callback
            'label_for' => 'sad_site_name',  // thêm for="" cho label
            'field_key' => 'site_name',      // key trong option array
        )
    );

    add_settings_field(
        'sad_site_email',
        'Email liên hệ',
        'sad_email_field_callback',
        'settings-api-demo',
        'sad_general_section',
        array(
            'label_for' => 'sad_site_email',
            'field_key' => 'site_email',
        )
    );

    add_settings_field(
        'sad_per_page',
        'Số lượng mỗi trang',
        'sad_number_field_callback',
        'settings-api-demo',
        'sad_general_section',
        array(
            'label_for' => 'sad_per_page',
            'field_key' => 'per_page',
        )
    );

    add_settings_field(
        'sad_show_header',
        'Hiển thị Header',
        'sad_checkbox_field_callback',
        'settings-api-demo',
        'sad_general_section',
        array(
            'label_for' => 'sad_show_header',
            'field_key' => 'show_header',
        )
    );
}

// === BƯỚC 3: Callback cho Section ===
function sad_general_section_callback() {
    echo '<p>Cấu hình các thông tin chung của website.</p>';
}

// === BƯỚC 4: Callbacks cho Fields ===

function sad_text_field_callback( $args ) {
    $options = get_option( 'sad_options' );
    $value = $options[ $args['field_key'] ] ?? '';
    ?>
    <input type="text"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="sad_options[<?php echo esc_attr( $args['field_key'] ); ?>]"
           value="<?php echo esc_attr( $value ); ?>"
           class="regular-text">
    <?php
}

function sad_email_field_callback( $args ) {
    $options = get_option( 'sad_options' );
    $value = $options[ $args['field_key'] ] ?? '';
    ?>
    <input type="email"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="sad_options[<?php echo esc_attr( $args['field_key'] ); ?>]"
           value="<?php echo esc_attr( $value ); ?>"
           class="regular-text">
    <p class="description">Nhập email hợp lệ.</p>
    <?php
}

function sad_number_field_callback( $args ) {
    $options = get_option( 'sad_options' );
    $value = $options[ $args['field_key'] ] ?? 10;
    ?>
    <input type="number"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="sad_options[<?php echo esc_attr( $args['field_key'] ); ?>]"
           value="<?php echo esc_attr( $value ); ?>"
           min="1"
           max="100"
           step="1"
           class="small-text">
    <p class="description">Từ 1 đến 100.</p>
    <?php
}

function sad_checkbox_field_callback( $args ) {
    $options = get_option( 'sad_options' );
    $checked = ! empty( $options[ $args['field_key'] ] );
    ?>
    <input type="checkbox"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="sad_options[<?php echo esc_attr( $args['field_key'] ); ?>]"
           value="1"
           <?php checked( $checked, true ); ?>>
    <label for="<?php echo esc_attr( $args['label_for'] ); ?>">
        Bật tính năng này
    </label>
    <?php
}

// === BƯỚC 5: Hàm Sanitize ===
function sad_sanitize_options( $input ) {
    $sanitized = array();

    // Sanitize từng trường
    $sanitized['site_name'] = sanitize_text_field( $input['site_name'] ?? '' );

    $sanitized['site_email'] = sanitize_email( $input['site_email'] ?? '' );
    if ( ! is_email( $sanitized['site_email'] ) && ! empty( $input['site_email'] ) ) {
        add_settings_error(
            'sad_options',           // setting slug
            'invalid_email',         // error code
            'Email không hợp lệ!',  // thông báo lỗi
            'error'                  // loại: error, warning, success, info
        );
        // Giữ lại giá trị cũ
        $old = get_option( 'sad_options' );
        $sanitized['site_email'] = $old['site_email'] ?? '';
    }

    $sanitized['per_page'] = absint( $input['per_page'] ?? 10 );
    if ( $sanitized['per_page'] < 1 || $sanitized['per_page'] > 100 ) {
        $sanitized['per_page'] = 10;
    }

    $sanitized['show_header'] = ! empty( $input['show_header'] );

    return $sanitized;
}

// === BƯỚC 6: Render trang Settings ===
function sad_options_page() {
    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <?php
        // Hiển thị thông báo lỗi/thành công (tự động từ Settings API)
        settings_errors( 'sad_options' );
        ?>

        <!--
        QUAN TRỌNG: action="options.php"
        WordPress sẽ tự động xử lý:
        1. Kiểm tra nonce
        2. Gọi sanitize callback
        3. Lưu option
        4. Redirect về trang này với thông báo
        -->
        <form method="post" action="options.php">
            <?php
            // Output hidden fields: nonce, option_page, action
            settings_fields( 'sad_options_group' );

            // Render tất cả sections và fields của trang này
            do_settings_sections( 'settings-api-demo' );

            // Nút Submit
            submit_button( 'Lưu cài đặt' );
            ?>
        </form>
    </div>
    <?php
}
```

---

## 4. Tạo trang Options hoàn chỉnh

Một trang Options chuyên nghiệp với cấu trúc rõ ràng:

```php
<?php
/**
 * Plugin Name: Professional Settings
 * Description: Trang Settings chuyên nghiệp.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Professional_Settings {

    /**
     * Option name trong database
     */
    private $option_name = 'pro_settings';

    /**
     * Option group cho Settings API
     */
    private $option_group = 'pro_settings_group';

    /**
     * Default values
     */
    private $defaults = array(
        'company_name'   => '',
        'company_email'  => '',
        'company_phone'  => '',
        'items_per_page' => 10,
        'date_format'    => 'Y-m-d',
        'enable_cache'   => true,
        'cache_duration' => 3600,
        'theme_color'    => '#0073aa',
        'custom_css'     => '',
    );

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Lấy giá trị option, tự động merge với defaults
     */
    public function get_options() {
        return wp_parse_args(
            get_option( $this->option_name, array() ),
            $this->defaults
        );
    }

    /**
     * Thêm menu
     */
    public function add_menu() {
        add_menu_page(
            'Professional Settings',
            'Pro Settings',
            'manage_options',
            'pro-settings',
            array( $this, 'render_page' ),
            'dashicons-admin-settings',
            30
        );
    }

    /**
     * Đăng ký settings
     */
    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            array(
                'sanitize_callback' => array( $this, 'sanitize' ),
                'default'           => $this->defaults,
            )
        );

        // --- Section: Thông tin công ty ---
        add_settings_section(
            'company_section',
            'Thông tin công ty',
            function() {
                echo '<p>Nhập thông tin công ty của bạn.</p>';
            },
            'pro-settings'
        );

        $this->add_field( 'company_name', 'Tên công ty', 'company_section', 'text' );
        $this->add_field( 'company_email', 'Email', 'company_section', 'email' );
        $this->add_field( 'company_phone', 'Số điện thoại', 'company_section', 'tel' );

        // --- Section: Hiển thị ---
        add_settings_section(
            'display_section',
            'Cài đặt hiển thị',
            function() {
                echo '<p>Tùy chỉnh cách hiển thị nội dung.</p>';
            },
            'pro-settings'
        );

        $this->add_field( 'items_per_page', 'Số lượng mỗi trang', 'display_section', 'number' );
        $this->add_field( 'date_format', 'Định dạng ngày', 'display_section', 'select', array(
            'options' => array(
                'Y-m-d'  => '2024-01-15',
                'd/m/Y'  => '15/01/2024',
                'm/d/Y'  => '01/15/2024',
                'F j, Y' => 'January 15, 2024',
            ),
        ));
        $this->add_field( 'theme_color', 'Màu chủ đạo', 'display_section', 'color' );
        $this->add_field( 'custom_css', 'CSS tùy chỉnh', 'display_section', 'textarea' );

        // --- Section: Hiệu suất ---
        add_settings_section(
            'performance_section',
            'Hiệu suất',
            function() {
                echo '<p>Cài đặt liên quan đến hiệu suất website.</p>';
            },
            'pro-settings'
        );

        $this->add_field( 'enable_cache', 'Bật Cache', 'performance_section', 'checkbox' );
        $this->add_field( 'cache_duration', 'Thời gian cache (giây)', 'performance_section', 'number' );
    }

    /**
     * Helper: Thêm field nhanh
     */
    private function add_field( $key, $label, $section, $type, $extra = array() ) {
        add_settings_field(
            "pro_{$key}",
            $label,
            array( $this, 'render_field' ),
            'pro-settings',
            $section,
            array_merge( array(
                'label_for' => "pro_{$key}",
                'field_key' => $key,
                'type'      => $type,
            ), $extra )
        );
    }

    /**
     * Render field dựa trên type
     */
    public function render_field( $args ) {
        $options = $this->get_options();
        $value   = $options[ $args['field_key'] ] ?? '';
        $id      = esc_attr( $args['label_for'] );
        $name    = esc_attr( $this->option_name . '[' . $args['field_key'] . ']' );

        switch ( $args['type'] ) {
            case 'text':
            case 'email':
            case 'tel':
            case 'url':
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">',
                    esc_attr( $args['type'] ),
                    $id,
                    $name,
                    esc_attr( $value )
                );
                break;

            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="0">',
                    $id,
                    $name,
                    esc_attr( $value )
                );
                break;

            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="5" cols="50" class="large-text">%s</textarea>',
                    $id,
                    $name,
                    esc_textarea( $value )
                );
                break;

            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s>',
                    $id,
                    $name,
                    checked( $value, true, false )
                );
                break;

            case 'select':
                printf( '<select id="%s" name="%s">', $id, $name );
                foreach ( $args['options'] as $opt_value => $opt_label ) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr( $opt_value ),
                        selected( $value, $opt_value, false ),
                        esc_html( $opt_label )
                    );
                }
                echo '</select>';
                break;

            case 'color':
                printf(
                    '<input type="color" id="%s" name="%s" value="%s">',
                    $id,
                    $name,
                    esc_attr( $value )
                );
                break;
        }

        // Hiển thị mô tả nếu có
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Sanitize tất cả options
     */
    public function sanitize( $input ) {
        $sanitized = array();

        $sanitized['company_name']   = sanitize_text_field( $input['company_name'] ?? '' );
        $sanitized['company_email']  = sanitize_email( $input['company_email'] ?? '' );
        $sanitized['company_phone']  = sanitize_text_field( $input['company_phone'] ?? '' );
        $sanitized['items_per_page'] = absint( $input['items_per_page'] ?? 10 );
        $sanitized['date_format']    = sanitize_text_field( $input['date_format'] ?? 'Y-m-d' );
        $sanitized['enable_cache']   = ! empty( $input['enable_cache'] );
        $sanitized['cache_duration'] = absint( $input['cache_duration'] ?? 3600 );
        $sanitized['theme_color']    = sanitize_hex_color( $input['theme_color'] ?? '#0073aa' );
        $sanitized['custom_css']     = wp_strip_all_tags( $input['custom_css'] ?? '' );

        return $sanitized;
    }

    /**
     * Render trang settings
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( 'pro-settings' );
                submit_button( 'Lưu cài đặt' );
                ?>
            </form>
        </div>
        <?php
    }
}

// Khởi tạo
new Professional_Settings();
```

---

## 5. Tabs trong Settings Page

```php
<?php
/**
 * Plugin Name: Tabbed Settings
 * Description: Settings page với nhiều tabs.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tabbed_Settings {

    private $tabs = array();

    public function __construct() {
        // Định nghĩa các tabs
        $this->tabs = array(
            'general'     => 'Tổng quan',
            'display'     => 'Hiển thị',
            'social'      => 'Mạng xã hội',
            'advanced'    => 'Nâng cao',
        );

        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu() {
        add_menu_page(
            'Tabbed Settings',
            'Tabbed Settings',
            'manage_options',
            'tabbed-settings',
            array( $this, 'render_page' ),
            'dashicons-admin-settings',
            31
        );
    }

    public function register_settings() {
        // === TAB: General ===
        register_setting( 'ts_general_group', 'ts_general_options', array(
            'sanitize_callback' => array( $this, 'sanitize_general' ),
        ));

        add_settings_section(
            'ts_general_section', 'Cài đặt tổng quan', null, 'ts-general'
        );

        add_settings_field(
            'ts_site_title', 'Tiêu đề site', array( $this, 'render_text_field' ),
            'ts-general', 'ts_general_section',
            array( 'option' => 'ts_general_options', 'field' => 'site_title' )
        );

        add_settings_field(
            'ts_tagline', 'Khẩu hiệu', array( $this, 'render_text_field' ),
            'ts-general', 'ts_general_section',
            array( 'option' => 'ts_general_options', 'field' => 'tagline' )
        );

        // === TAB: Display ===
        register_setting( 'ts_display_group', 'ts_display_options', array(
            'sanitize_callback' => array( $this, 'sanitize_display' ),
        ));

        add_settings_section(
            'ts_display_section', 'Cài đặt hiển thị', null, 'ts-display'
        );

        add_settings_field(
            'ts_layout', 'Bố cục', array( $this, 'render_radio_field' ),
            'ts-display', 'ts_display_section',
            array(
                'option'  => 'ts_display_options',
                'field'   => 'layout',
                'choices' => array(
                    'boxed'     => 'Bố cục hộp (Boxed)',
                    'wide'      => 'Bố cục rộng (Wide)',
                    'fullwidth' => 'Toàn màn hình (Full Width)',
                ),
            )
        );

        add_settings_field(
            'ts_sidebar', 'Vị trí Sidebar', array( $this, 'render_select_field' ),
            'ts-display', 'ts_display_section',
            array(
                'option'  => 'ts_display_options',
                'field'   => 'sidebar',
                'choices' => array(
                    'left'  => 'Bên trái',
                    'right' => 'Bên phải',
                    'none'  => 'Không có',
                ),
            )
        );

        // === TAB: Social ===
        register_setting( 'ts_social_group', 'ts_social_options', array(
            'sanitize_callback' => array( $this, 'sanitize_social' ),
        ));

        add_settings_section(
            'ts_social_section', 'Liên kết mạng xã hội', null, 'ts-social'
        );

        $social_networks = array( 'facebook', 'twitter', 'instagram', 'youtube', 'linkedin' );
        foreach ( $social_networks as $network ) {
            add_settings_field(
                "ts_{$network}", ucfirst( $network ), array( $this, 'render_url_field' ),
                'ts-social', 'ts_social_section',
                array( 'option' => 'ts_social_options', 'field' => $network )
            );
        }

        // === TAB: Advanced ===
        register_setting( 'ts_advanced_group', 'ts_advanced_options', array(
            'sanitize_callback' => array( $this, 'sanitize_advanced' ),
        ));

        add_settings_section(
            'ts_advanced_section', 'Cài đặt nâng cao', null, 'ts-advanced'
        );

        add_settings_field(
            'ts_custom_header', 'Custom Header Code', array( $this, 'render_textarea_field' ),
            'ts-advanced', 'ts_advanced_section',
            array(
                'option'      => 'ts_advanced_options',
                'field'       => 'custom_header',
                'description' => 'Code sẽ được thêm vào trước thẻ </head>',
            )
        );

        add_settings_field(
            'ts_custom_footer', 'Custom Footer Code', array( $this, 'render_textarea_field' ),
            'ts-advanced', 'ts_advanced_section',
            array(
                'option'      => 'ts_advanced_options',
                'field'       => 'custom_footer',
                'description' => 'Code sẽ được thêm vào trước thẻ </body>',
            )
        );

        add_settings_field(
            'ts_debug_mode', 'Chế độ Debug', array( $this, 'render_checkbox_field' ),
            'ts-advanced', 'ts_advanced_section',
            array( 'option' => 'ts_advanced_options', 'field' => 'debug_mode' )
        );
    }

    // === RENDER FIELDS ===

    public function render_text_field( $args ) {
        $options = get_option( $args['option'], array() );
        $value = $options[ $args['field'] ] ?? '';
        printf(
            '<input type="text" name="%s[%s]" value="%s" class="regular-text">',
            esc_attr( $args['option'] ),
            esc_attr( $args['field'] ),
            esc_attr( $value )
        );
    }

    public function render_url_field( $args ) {
        $options = get_option( $args['option'], array() );
        $value = $options[ $args['field'] ] ?? '';
        printf(
            '<input type="url" name="%s[%s]" value="%s" class="regular-text" placeholder="https://">',
            esc_attr( $args['option'] ),
            esc_attr( $args['field'] ),
            esc_attr( $value )
        );
    }

    public function render_textarea_field( $args ) {
        $options = get_option( $args['option'], array() );
        $value = $options[ $args['field'] ] ?? '';
        printf(
            '<textarea name="%s[%s]" rows="5" cols="50" class="large-text">%s</textarea>',
            esc_attr( $args['option'] ),
            esc_attr( $args['field'] ),
            esc_textarea( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function render_checkbox_field( $args ) {
        $options = get_option( $args['option'], array() );
        $checked = ! empty( $options[ $args['field'] ] );
        printf(
            '<input type="checkbox" name="%s[%s]" value="1" %s>',
            esc_attr( $args['option'] ),
            esc_attr( $args['field'] ),
            checked( $checked, true, false )
        );
    }

    public function render_select_field( $args ) {
        $options = get_option( $args['option'], array() );
        $value = $options[ $args['field'] ] ?? '';
        printf( '<select name="%s[%s]">', esc_attr( $args['option'] ), esc_attr( $args['field'] ) );
        foreach ( $args['choices'] as $key => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $key ),
                selected( $value, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
    }

    public function render_radio_field( $args ) {
        $options = get_option( $args['option'], array() );
        $value = $options[ $args['field'] ] ?? '';
        foreach ( $args['choices'] as $key => $label ) {
            printf(
                '<label style="display:block; margin-bottom:5px;">
                    <input type="radio" name="%s[%s]" value="%s" %s> %s
                </label>',
                esc_attr( $args['option'] ),
                esc_attr( $args['field'] ),
                esc_attr( $key ),
                checked( $value, $key, false ),
                esc_html( $label )
            );
        }
    }

    // === SANITIZE FUNCTIONS ===

    public function sanitize_general( $input ) {
        return array(
            'site_title' => sanitize_text_field( $input['site_title'] ?? '' ),
            'tagline'    => sanitize_text_field( $input['tagline'] ?? '' ),
        );
    }

    public function sanitize_display( $input ) {
        $valid_layouts = array( 'boxed', 'wide', 'fullwidth' );
        $valid_sidebars = array( 'left', 'right', 'none' );

        return array(
            'layout'  => in_array( $input['layout'] ?? '', $valid_layouts ) ? $input['layout'] : 'wide',
            'sidebar' => in_array( $input['sidebar'] ?? '', $valid_sidebars ) ? $input['sidebar'] : 'right',
        );
    }

    public function sanitize_social( $input ) {
        $sanitized = array();
        $networks = array( 'facebook', 'twitter', 'instagram', 'youtube', 'linkedin' );
        foreach ( $networks as $network ) {
            $sanitized[ $network ] = esc_url_raw( $input[ $network ] ?? '' );
        }
        return $sanitized;
    }

    public function sanitize_advanced( $input ) {
        return array(
            'custom_header' => wp_kses( $input['custom_header'] ?? '', array(
                'script' => array( 'src' => array(), 'type' => array() ),
                'style'  => array( 'type' => array() ),
                'link'   => array( 'rel' => array(), 'href' => array(), 'type' => array() ),
                'meta'   => array( 'name' => array(), 'content' => array() ),
            )),
            'custom_footer' => wp_kses( $input['custom_footer'] ?? '', array(
                'script' => array( 'src' => array(), 'type' => array() ),
                'style'  => array( 'type' => array() ),
            )),
            'debug_mode'    => ! empty( $input['debug_mode'] ),
        );
    }

    // === RENDER TRANG CHÍNH ===

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Xác định tab hiện tại từ URL parameter
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

        // Đảm bảo tab hợp lệ
        if ( ! array_key_exists( $current_tab, $this->tabs ) ) {
            $current_tab = 'general';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php settings_errors(); ?>

            <!-- TABS NAVIGATION -->
            <nav class="nav-tab-wrapper">
                <?php foreach ( $this->tabs as $tab_key => $tab_label ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array(
                        'page' => 'tabbed-settings',
                        'tab'  => $tab_key,
                    ), admin_url( 'admin.php' ) ) ); ?>"
                       class="nav-tab <?php echo ( $current_tab === $tab_key ) ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- TAB CONTENT -->
            <form method="post" action="options.php">
                <?php
                switch ( $current_tab ) {
                    case 'general':
                        settings_fields( 'ts_general_group' );
                        do_settings_sections( 'ts-general' );
                        break;

                    case 'display':
                        settings_fields( 'ts_display_group' );
                        do_settings_sections( 'ts-display' );
                        break;

                    case 'social':
                        settings_fields( 'ts_social_group' );
                        do_settings_sections( 'ts-social' );
                        break;

                    case 'advanced':
                        settings_fields( 'ts_advanced_group' );
                        do_settings_sections( 'ts-advanced' );
                        break;
                }

                submit_button( 'Lưu cài đặt' );
                ?>
            </form>
        </div>
        <?php
    }
}

new Tabbed_Settings();
```

---

## 6. Các loại field

### Tổng hợp tất cả các loại field thường dùng

```php
<?php
/**
 * Render các loại field khác nhau trong Settings page.
 * File này là tập hợp các hàm helper.
 */

// === 1. TEXT FIELD ===
function render_text_field( $option_name, $field_key, $placeholder = '' ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';
    printf(
        '<input type="text" name="%s[%s]" value="%s" placeholder="%s" class="regular-text">',
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        esc_attr( $value ),
        esc_attr( $placeholder )
    );
}

// === 2. TEXTAREA ===
function render_textarea_field( $option_name, $field_key, $rows = 5 ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';
    printf(
        '<textarea name="%s[%s]" rows="%d" cols="50" class="large-text">%s</textarea>',
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        intval( $rows ),
        esc_textarea( $value )
    );
}

// === 3. CHECKBOX ĐƠN ===
function render_checkbox_field( $option_name, $field_key, $label = '' ) {
    $options = get_option( $option_name, array() );
    $checked = ! empty( $options[ $field_key ] );
    printf(
        '<label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label>',
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        checked( $checked, true, false ),
        esc_html( $label )
    );
}

// === 4. NHIỀU CHECKBOXES ===
function render_multi_checkbox_field( $option_name, $field_key, $choices ) {
    $options = get_option( $option_name, array() );
    $values = (array) ( $options[ $field_key ] ?? array() );

    foreach ( $choices as $key => $label ) {
        printf(
            '<label style="display:block; margin-bottom:5px;">
                <input type="checkbox" name="%s[%s][]" value="%s" %s> %s
            </label>',
            esc_attr( $option_name ),
            esc_attr( $field_key ),
            esc_attr( $key ),
            checked( in_array( $key, $values ), true, false ),
            esc_html( $label )
        );
    }
}

// Sử dụng:
// render_multi_checkbox_field( 'my_options', 'features', array(
//     'seo'       => 'SEO Optimization',
//     'cache'     => 'Page Caching',
//     'minify'    => 'CSS/JS Minification',
//     'lazy_load' => 'Lazy Load Images',
// ));

// === 5. SELECT DROPDOWN ===
function render_select_field( $option_name, $field_key, $choices ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';

    printf( '<select name="%s[%s]">', esc_attr( $option_name ), esc_attr( $field_key ) );
    foreach ( $choices as $key => $label ) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr( $key ),
            selected( $value, $key, false ),
            esc_html( $label )
        );
    }
    echo '</select>';
}

// === 6. RADIO BUTTONS ===
function render_radio_field( $option_name, $field_key, $choices ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';

    foreach ( $choices as $key => $label ) {
        printf(
            '<label style="display:block; margin-bottom:8px;">
                <input type="radio" name="%s[%s]" value="%s" %s> %s
            </label>',
            esc_attr( $option_name ),
            esc_attr( $field_key ),
            esc_attr( $key ),
            checked( $value, $key, false ),
            esc_html( $label )
        );
    }
}

// === 7. COLOR PICKER (với jQuery) ===
function render_color_picker_field( $option_name, $field_key ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '#000000';

    // Cần wp_enqueue_script('wp-color-picker') và wp_enqueue_style('wp-color-picker')
    printf(
        '<input type="text" name="%s[%s]" value="%s" class="color-picker" data-default-color="#000000">',
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        esc_attr( $value )
    );
}

// Enqueue Color Picker
// add_action( 'admin_enqueue_scripts', function( $hook ) {
//     if ( $hook !== 'toplevel_page_my-plugin' ) return;
//     wp_enqueue_style( 'wp-color-picker' );
//     wp_enqueue_script( 'wp-color-picker' );
//     wp_add_inline_script( 'wp-color-picker', "
//         jQuery(document).ready(function($){
//             $('.color-picker').wpColorPicker();
//         });
//     ");
// });

// === 8. MEDIA UPLOAD (chọn hình ảnh từ Media Library) ===
function render_media_upload_field( $option_name, $field_key ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';
    $image_url = $value ? wp_get_attachment_url( $value ) : '';
    ?>
    <div class="media-upload-field">
        <input type="hidden"
               name="<?php echo esc_attr( $option_name . '[' . $field_key . ']' ); ?>"
               id="media_<?php echo esc_attr( $field_key ); ?>"
               value="<?php echo esc_attr( $value ); ?>">

        <div id="preview_<?php echo esc_attr( $field_key ); ?>" style="margin-bottom:10px;">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>"
                     style="max-width:200px; max-height:200px;">
            <?php endif; ?>
        </div>

        <button type="button"
                class="button media-upload-button"
                data-field="<?php echo esc_attr( $field_key ); ?>">
            Chọn hình ảnh
        </button>

        <?php if ( $value ) : ?>
            <button type="button"
                    class="button media-remove-button"
                    data-field="<?php echo esc_attr( $field_key ); ?>">
                Xóa hình
            </button>
        <?php endif; ?>
    </div>
    <?php
}

// JavaScript cho Media Upload
// add_action( 'admin_enqueue_scripts', function( $hook ) {
//     if ( $hook !== 'toplevel_page_my-plugin' ) return;
//
//     wp_enqueue_media(); // Load Media Library
//
//     wp_add_inline_script( 'jquery-core', "
//         jQuery(document).ready(function($){
//             // Upload button
//             $('.media-upload-button').on('click', function(e){
//                 e.preventDefault();
//                 var field = $(this).data('field');
//                 var frame = wp.media({
//                     title: 'Chọn hình ảnh',
//                     button: { text: 'Sử dụng hình này' },
//                     multiple: false,
//                     library: { type: 'image' }
//                 });
//
//                 frame.on('select', function(){
//                     var attachment = frame.state().get('selection').first().toJSON();
//                     $('#media_' + field).val(attachment.id);
//                     $('#preview_' + field).html(
//                         '<img src=\"' + attachment.url + '\" style=\"max-width:200px;\">'
//                     );
//                 });
//
//                 frame.open();
//             });
//
//             // Remove button
//             $('.media-remove-button').on('click', function(e){
//                 e.preventDefault();
//                 var field = $(this).data('field');
//                 $('#media_' + field).val('');
//                 $('#preview_' + field).html('');
//             });
//         });
//     ");
// });

// === 9. WYSIWYG EDITOR ===
function render_wysiwyg_field( $option_name, $field_key ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';

    // wp_editor tự động tạo editor (TinyMCE hoặc Quicktags)
    wp_editor( $value, $field_key, array(
        'textarea_name' => $option_name . '[' . $field_key . ']',
        'textarea_rows' => 10,
        'media_buttons' => true,    // Nút thêm media
        'teeny'         => false,   // true = editor đơn giản
        'quicktags'     => true,    // Tab Text
    ));
}

// === 10. PASSWORD FIELD ===
function render_password_field( $option_name, $field_key ) {
    $options = get_option( $option_name, array() );
    $value = $options[ $field_key ] ?? '';
    printf(
        '<input type="password" name="%s[%s]" value="%s" class="regular-text" autocomplete="off">',
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        esc_attr( $value )
    );
}

// === 11. REPEATER FIELD (thêm nhiều dòng) ===
function render_repeater_field( $option_name, $field_key ) {
    $options = get_option( $option_name, array() );
    $items = (array) ( $options[ $field_key ] ?? array() );
    $name = esc_attr( $option_name . '[' . $field_key . ']' );
    ?>
    <div class="repeater-container" id="repeater_<?php echo esc_attr( $field_key ); ?>">
        <?php foreach ( $items as $index => $item ) : ?>
            <div class="repeater-row" style="margin-bottom:5px;">
                <input type="text"
                       name="<?php echo $name; ?>[]"
                       value="<?php echo esc_attr( $item ); ?>"
                       class="regular-text">
                <button type="button" class="button remove-row">Xóa</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button add-row" data-field="<?php echo esc_attr( $field_key ); ?>">
        + Thêm dòng mới
    </button>
    <?php
}
```

---

## 7. Validate và Sanitize Settings

### Phân biệt Sanitize và Validate

```
SANITIZE = Làm sạch dữ liệu (loại bỏ ký tự xấu)
  - Luôn thực hiện
  - Biến đổi dữ liệu thành dạng an toàn
  - Ví dụ: "<script>alert(1)</script>" => "alert(1)"

VALIDATE = Kiểm tra dữ liệu có hợp lệ không
  - Kiểm tra logic nghiệp vụ
  - Trả về true/false hoặc error
  - Ví dụ: Email có đúng định dạng? Số có trong phạm vi?
```

### Ví dụ Sanitize và Validate đầy đủ

```php
<?php
/**
 * Hàm sanitize toàn bộ settings.
 * Hàm này được gọi tự động bởi Settings API trước khi lưu data.
 *
 * @param array $input Dữ liệu thô từ form
 * @return array Dữ liệu đã được làm sạch
 */
function my_sanitize_settings( $input ) {
    $sanitized = array();
    $errors = array();

    // --- Sanitize text ---
    // sanitize_text_field: Loại bỏ tags HTML, xóa ký tự đặc biệt
    $sanitized['name'] = sanitize_text_field( $input['name'] ?? '' );

    // Validate: Kiểm tra không để trống
    if ( empty( $sanitized['name'] ) ) {
        $errors[] = 'Tên không được để trống.';
    }

    // --- Sanitize email ---
    $sanitized['email'] = sanitize_email( $input['email'] ?? '' );

    // Validate: Kiểm tra định dạng email
    if ( ! empty( $input['email'] ) && ! is_email( $sanitized['email'] ) ) {
        $errors[] = 'Địa chỉ email không hợp lệ.';
        // Giữ lại giá trị cũ
        $old = get_option( 'my_settings' );
        $sanitized['email'] = $old['email'] ?? '';
    }

    // --- Sanitize URL ---
    // esc_url_raw: Giống esc_url nhưng cho database (không encode entities)
    $sanitized['website'] = esc_url_raw( $input['website'] ?? '' );

    // --- Sanitize number ---
    // absint: Trả về số nguyên dương tuyệt đối
    $sanitized['age'] = absint( $input['age'] ?? 0 );

    // Validate: Kiểm tra phạm vi
    if ( $sanitized['age'] < 1 || $sanitized['age'] > 150 ) {
        $errors[] = 'Tuổi phải từ 1 đến 150.';
        $sanitized['age'] = 25; // Giá trị mặc định
    }

    // intval: Trả về số nguyên (có thể âm)
    $sanitized['offset'] = intval( $input['offset'] ?? 0 );

    // floatval: Trả về số thực
    $sanitized['price'] = floatval( $input['price'] ?? 0 );

    // --- Sanitize textarea ---
    // sanitize_textarea_field: Giống sanitize_text_field nhưng giữ lại xuống dòng
    $sanitized['description'] = sanitize_textarea_field( $input['description'] ?? '' );

    // --- Sanitize HTML content ---
    // wp_kses_post: Cho phép HTML an toàn (giống nội dung bài viết)
    $sanitized['rich_content'] = wp_kses_post( $input['rich_content'] ?? '' );

    // wp_kses: Tùy chỉnh chính xác HTML nào được phép
    $allowed_html = array(
        'a'      => array( 'href' => array(), 'title' => array(), 'target' => array() ),
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
        'p'      => array( 'class' => array() ),
    );
    $sanitized['limited_html'] = wp_kses( $input['limited_html'] ?? '', $allowed_html );

    // --- Sanitize checkbox ---
    $sanitized['enabled'] = ! empty( $input['enabled'] );

    // --- Sanitize select/radio (giá trị từ danh sách cố định) ---
    $valid_colors = array( 'red', 'green', 'blue' );
    $sanitized['color'] = in_array( $input['color'] ?? '', $valid_colors, true )
        ? $input['color']
        : 'blue'; // Default nếu giá trị không hợp lệ

    // --- Sanitize array ---
    $sanitized['tags'] = array();
    if ( ! empty( $input['tags'] ) && is_array( $input['tags'] ) ) {
        $sanitized['tags'] = array_map( 'sanitize_text_field', $input['tags'] );
        $sanitized['tags'] = array_filter( $sanitized['tags'] ); // Loại bỏ empty
    }

    // --- Sanitize hex color ---
    $sanitized['bg_color'] = sanitize_hex_color( $input['bg_color'] ?? '' );
    if ( empty( $sanitized['bg_color'] ) ) {
        $sanitized['bg_color'] = '#ffffff'; // Default
    }

    // --- Sanitize file name ---
    $sanitized['filename'] = sanitize_file_name( $input['filename'] ?? '' );

    // --- Sanitize CSS class ---
    $sanitized['css_class'] = sanitize_html_class( $input['css_class'] ?? '' );

    // --- Hiển thị lỗi ---
    foreach ( $errors as $error ) {
        add_settings_error(
            'my_settings',       // Setting slug
            'validation_error',  // Error code (duy nhất)
            $error,              // Nội dung lỗi
            'error'              // Loại: 'error', 'warning', 'success', 'info'
        );
    }

    return $sanitized;
}
```

### Danh sách Sanitize Functions của WordPress

```
+---------------------------+-----------------------------------+
| Hàm                       | Công dụng                         |
+---------------------------+-----------------------------------+
| sanitize_text_field()     | Xóa tags, trim, xóa xuống dòng   |
| sanitize_textarea_field() | Giống trên nhưng giữ xuống dòng   |
| sanitize_email()          | Chỉ giữ ký tự email hợp lệ       |
| sanitize_url()            | Làm sạch URL                      |
| esc_url_raw()             | URL cho database (không encode)   |
| sanitize_file_name()      | Làm sạch tên file                 |
| sanitize_html_class()     | Làm sạch CSS class                |
| sanitize_hex_color()      | Kiểm tra mã màu hex (#ffffff)     |
| sanitize_title()          | Tạo slug từ text                  |
| sanitize_key()            | Chữ thường, số, gạch ngang        |
| sanitize_mime_type()      | Làm sạch MIME type                |
| wp_kses()                 | Lọc HTML theo whitelist           |
| wp_kses_post()            | Cho phép HTML an toàn bài viết    |
| wp_strip_all_tags()       | Xóa tất cả tags HTML              |
| absint()                  | Số nguyên dương tuyệt đối         |
| intval()                  | Chuyển sang số nguyên             |
| floatval()                | Chuyển sang số thực               |
+---------------------------+-----------------------------------+
```

---

## 8. Code ví dụ: Plugin Settings hoàn chỉnh

```php
<?php
/**
 * Plugin Name:       Advanced Settings Plugin
 * Description:       Plugin mẫu với trang Settings hoàn chỉnh, nhiều tabs, nhiều loại field.
 * Version:           1.0.0
 * Author:            Developer
 * Text Domain:       adv-settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ASP_VERSION', '1.0.0' );
define( 'ASP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class Advanced_Settings_Plugin {

    /**
     * Instance duy nhất (Singleton)
     */
    private static $instance = null;

    /**
     * Các tab
     */
    private $tabs;

    /**
     * Lấy instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->tabs = array(
            'general'  => array( 'title' => 'Tổng quan',   'icon' => 'dashicons-admin-generic' ),
            'email'    => array( 'title' => 'Email',       'icon' => 'dashicons-email' ),
            'display'  => array( 'title' => 'Hiển thị',    'icon' => 'dashicons-visibility' ),
            'advanced' => array( 'title' => 'Nâng cao',    'icon' => 'dashicons-admin-tools' ),
        );

        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue CSS/JS cho admin
     */
    public function enqueue_assets( $hook ) {
        // Chỉ load trên trang settings của plugin
        if ( 'toplevel_page_adv-settings' !== $hook ) {
            return;
        }

        // WordPress Color Picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // WordPress Media Uploader
        wp_enqueue_media();

        // Inline CSS cho trang settings
        wp_add_inline_style( 'wp-color-picker', '
            .asp-tab-icon { margin-right: 5px; }
            .asp-settings-wrap { max-width: 800px; }
            .asp-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-top: 20px;
            }
            .asp-card h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
        ');

        // Inline JS cho Color Picker và Media Upload
        wp_add_inline_script( 'wp-color-picker', "
            jQuery(document).ready(function($){
                // Khởi tạo Color Picker
                $('.asp-color-picker').wpColorPicker();

                // Media Upload
                $(document).on('click', '.asp-media-upload', function(e){
                    e.preventDefault();
                    var button = $(this);
                    var field = button.data('field');
                    var frame = wp.media({
                        title: 'Chọn hình ảnh',
                        button: { text: 'Sử dụng hình này' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#' + field).val(attachment.id);
                        $('#' + field + '_preview').html(
                            '<img src=\"' + attachment.url + '\" style=\"max-width:150px; margin-top:10px;\">'
                        );
                        button.siblings('.asp-media-remove').show();
                    });
                    frame.open();
                });

                $(document).on('click', '.asp-media-remove', function(e){
                    e.preventDefault();
                    var field = $(this).data('field');
                    $('#' + field).val('');
                    $('#' + field + '_preview').html('');
                    $(this).hide();
                });
            });
        ");
    }

    /**
     * Thêm menu
     */
    public function add_menu() {
        add_menu_page(
            'Advanced Settings',
            'Adv Settings',
            'manage_options',
            'adv-settings',
            array( $this, 'render_page' ),
            'dashicons-admin-settings',
            31
        );
    }

    /**
     * Đăng ký tất cả settings cho mỗi tab
     */
    public function register_settings() {
        // === TAB GENERAL ===
        register_setting( 'asp_general', 'asp_general_options', array(
            'sanitize_callback' => array( $this, 'sanitize_general' ),
            'default' => array(
                'site_name' => get_bloginfo( 'name' ),
                'site_description' => '',
                'logo_id' => '',
                'per_page' => 10,
            ),
        ));

        add_settings_section( 'asp_general_main', 'Thông tin website', null, 'asp-general' );

        add_settings_field( 'asp_site_name', 'Tên website', function() {
            $opts = get_option( 'asp_general_options', array() );
            printf(
                '<input type="text" name="asp_general_options[site_name]" value="%s" class="regular-text">',
                esc_attr( $opts['site_name'] ?? '' )
            );
        }, 'asp-general', 'asp_general_main' );

        add_settings_field( 'asp_site_desc', 'Mô tả', function() {
            $opts = get_option( 'asp_general_options', array() );
            printf(
                '<textarea name="asp_general_options[site_description]" rows="3" class="large-text">%s</textarea>',
                esc_textarea( $opts['site_description'] ?? '' )
            );
        }, 'asp-general', 'asp_general_main' );

        add_settings_field( 'asp_logo', 'Logo', function() {
            $opts = get_option( 'asp_general_options', array() );
            $logo_id = $opts['logo_id'] ?? '';
            $logo_url = $logo_id ? wp_get_attachment_url( $logo_id ) : '';
            ?>
            <input type="hidden" id="asp_logo_id" name="asp_general_options[logo_id]"
                   value="<?php echo esc_attr( $logo_id ); ?>">
            <div id="asp_logo_id_preview">
                <?php if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" style="max-width:150px; margin-top:10px;">
                <?php endif; ?>
            </div>
            <button type="button" class="button asp-media-upload" data-field="asp_logo_id">
                Chọn Logo
            </button>
            <button type="button" class="button asp-media-remove" data-field="asp_logo_id"
                    style="<?php echo $logo_id ? '' : 'display:none;'; ?>">
                Xóa Logo
            </button>
            <?php
        }, 'asp-general', 'asp_general_main' );

        add_settings_field( 'asp_per_page', 'Số lượng mỗi trang', function() {
            $opts = get_option( 'asp_general_options', array() );
            printf(
                '<input type="number" name="asp_general_options[per_page]" value="%s"
                        min="1" max="100" class="small-text">',
                esc_attr( $opts['per_page'] ?? 10 )
            );
        }, 'asp-general', 'asp_general_main' );

        // === TAB EMAIL ===
        register_setting( 'asp_email', 'asp_email_options', array(
            'sanitize_callback' => array( $this, 'sanitize_email' ),
        ));

        add_settings_section( 'asp_email_main', 'Cài đặt Email', function() {
            echo '<p>Cấu hình email gửi từ plugin.</p>';
        }, 'asp-email' );

        add_settings_field( 'asp_from_email', 'Email gửi', function() {
            $opts = get_option( 'asp_email_options', array() );
            printf(
                '<input type="email" name="asp_email_options[from_email]" value="%s" class="regular-text">',
                esc_attr( $opts['from_email'] ?? get_option( 'admin_email' ) )
            );
        }, 'asp-email', 'asp_email_main' );

        add_settings_field( 'asp_from_name', 'Tên người gửi', function() {
            $opts = get_option( 'asp_email_options', array() );
            printf(
                '<input type="text" name="asp_email_options[from_name]" value="%s" class="regular-text">',
                esc_attr( $opts['from_name'] ?? get_bloginfo( 'name' ) )
            );
        }, 'asp-email', 'asp_email_main' );

        add_settings_field( 'asp_email_footer', 'Footer email', function() {
            $opts = get_option( 'asp_email_options', array() );
            wp_editor(
                $opts['email_footer'] ?? '',
                'asp_email_footer_editor',
                array(
                    'textarea_name' => 'asp_email_options[email_footer]',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny'         => true,
                )
            );
        }, 'asp-email', 'asp_email_main' );

        // === TAB DISPLAY ===
        register_setting( 'asp_display', 'asp_display_options', array(
            'sanitize_callback' => array( $this, 'sanitize_display' ),
        ));

        add_settings_section( 'asp_display_main', 'Tùy chỉnh giao diện', null, 'asp-display' );

        add_settings_field( 'asp_primary_color', 'Màu chính', function() {
            $opts = get_option( 'asp_display_options', array() );
            printf(
                '<input type="text" name="asp_display_options[primary_color]" value="%s"
                        class="asp-color-picker" data-default-color="#0073aa">',
                esc_attr( $opts['primary_color'] ?? '#0073aa' )
            );
        }, 'asp-display', 'asp_display_main' );

        add_settings_field( 'asp_layout', 'Bố cục', function() {
            $opts = get_option( 'asp_display_options', array() );
            $current = $opts['layout'] ?? 'wide';
            $layouts = array( 'boxed' => 'Boxed', 'wide' => 'Wide', 'full' => 'Full Width' );
            foreach ( $layouts as $val => $label ) {
                printf(
                    '<label style="display:inline-block; margin-right:20px;">
                        <input type="radio" name="asp_display_options[layout]" value="%s" %s> %s
                    </label>',
                    esc_attr( $val ),
                    checked( $current, $val, false ),
                    esc_html( $label )
                );
            }
        }, 'asp-display', 'asp_display_main' );

        add_settings_field( 'asp_features', 'Tính năng', function() {
            $opts = get_option( 'asp_display_options', array() );
            $features = (array) ( $opts['features'] ?? array() );
            $all_features = array(
                'breadcrumbs' => 'Hiển thị Breadcrumbs',
                'scroll_top'  => 'Nút Scroll to Top',
                'preloader'   => 'Preloader Animation',
                'dark_mode'   => 'Hỗ trợ Dark Mode',
            );
            foreach ( $all_features as $key => $label ) {
                printf(
                    '<label style="display:block; margin-bottom:5px;">
                        <input type="checkbox" name="asp_display_options[features][]" value="%s" %s> %s
                    </label>',
                    esc_attr( $key ),
                    checked( in_array( $key, $features ), true, false ),
                    esc_html( $label )
                );
            }
        }, 'asp-display', 'asp_display_main' );

        add_settings_field( 'asp_custom_css', 'Custom CSS', function() {
            $opts = get_option( 'asp_display_options', array() );
            printf(
                '<textarea name="asp_display_options[custom_css]" rows="8"
                           class="large-text" style="font-family:monospace;">%s</textarea>',
                esc_textarea( $opts['custom_css'] ?? '' )
            );
        }, 'asp-display', 'asp_display_main' );

        // === TAB ADVANCED ===
        register_setting( 'asp_advanced', 'asp_advanced_options', array(
            'sanitize_callback' => array( $this, 'sanitize_advanced' ),
        ));

        add_settings_section( 'asp_advanced_main', 'Cài đặt nâng cao', function() {
            echo '<p style="color:#d63638;"><strong>Chú ý:</strong> Chỉ thay đổi khi bạn hiểu rõ mình đang làm gì.</p>';
        }, 'asp-advanced' );

        add_settings_field( 'asp_debug', 'Chế độ Debug', function() {
            $opts = get_option( 'asp_advanced_options', array() );
            printf(
                '<label><input type="checkbox" name="asp_advanced_options[debug]" value="1" %s>
                 Bật chế độ debug (ghi log)</label>',
                checked( ! empty( $opts['debug'] ), true, false )
            );
        }, 'asp-advanced', 'asp_advanced_main' );

        add_settings_field( 'asp_cache_ttl', 'Cache TTL (giây)', function() {
            $opts = get_option( 'asp_advanced_options', array() );
            printf(
                '<input type="number" name="asp_advanced_options[cache_ttl]" value="%s"
                        min="0" max="86400" class="small-text">
                 <p class="description">0 = tắt cache. Tối đa 86400 (24 giờ).</p>',
                esc_attr( $opts['cache_ttl'] ?? 3600 )
            );
        }, 'asp-advanced', 'asp_advanced_main' );

        add_settings_field( 'asp_api_key', 'API Key', function() {
            $opts = get_option( 'asp_advanced_options', array() );
            printf(
                '<input type="password" name="asp_advanced_options[api_key]" value="%s"
                        class="regular-text" autocomplete="new-password">
                 <p class="description">Nhập API key để kết nối dịch vụ ngoài.</p>',
                esc_attr( $opts['api_key'] ?? '' )
            );
        }, 'asp-advanced', 'asp_advanced_main' );

        add_settings_field( 'asp_export', 'Xuất/Nhập cài đặt', function() {
            $all_options = array(
                'asp_general_options'  => get_option( 'asp_general_options', array() ),
                'asp_email_options'    => get_option( 'asp_email_options', array() ),
                'asp_display_options'  => get_option( 'asp_display_options', array() ),
                'asp_advanced_options' => get_option( 'asp_advanced_options', array() ),
            );
            ?>
            <h4>Xuất cài đặt</h4>
            <textarea readonly class="large-text" rows="3" onclick="this.select();"><?php
                echo esc_textarea( wp_json_encode( $all_options ) );
            ?></textarea>
            <p class="description">Copy nội dung trên để sao lưu cài đặt.</p>

            <h4 style="margin-top:15px;">Nhập cài đặt</h4>
            <textarea name="asp_advanced_options[import_data]" class="large-text" rows="3"
                      placeholder="Dán nội dung JSON đã xuất ở đây..."></textarea>
            <p class="description">Dán JSON đã xuất vào đây và nhấn Lưu để khôi phục cài đặt.</p>
            <?php
        }, 'asp-advanced', 'asp_advanced_main' );
    }

    // === SANITIZE FUNCTIONS ===

    public function sanitize_general( $input ) {
        return array(
            'site_name'        => sanitize_text_field( $input['site_name'] ?? '' ),
            'site_description' => sanitize_textarea_field( $input['site_description'] ?? '' ),
            'logo_id'          => absint( $input['logo_id'] ?? 0 ),
            'per_page'         => max( 1, min( 100, absint( $input['per_page'] ?? 10 ) ) ),
        );
    }

    public function sanitize_email( $input ) {
        $sanitized = array();
        $sanitized['from_email'] = sanitize_email( $input['from_email'] ?? '' );
        $sanitized['from_name']  = sanitize_text_field( $input['from_name'] ?? '' );
        $sanitized['email_footer'] = wp_kses_post( $input['email_footer'] ?? '' );

        if ( ! empty( $input['from_email'] ) && ! is_email( $sanitized['from_email'] ) ) {
            add_settings_error( 'asp_email_options', 'invalid_email', 'Email không hợp lệ!' );
        }

        return $sanitized;
    }

    public function sanitize_display( $input ) {
        $valid_layouts = array( 'boxed', 'wide', 'full' );
        $valid_features = array( 'breadcrumbs', 'scroll_top', 'preloader', 'dark_mode' );

        $features = array();
        if ( ! empty( $input['features'] ) && is_array( $input['features'] ) ) {
            $features = array_intersect( $input['features'], $valid_features );
        }

        return array(
            'primary_color' => sanitize_hex_color( $input['primary_color'] ?? '#0073aa' ),
            'layout'        => in_array( $input['layout'] ?? '', $valid_layouts ) ? $input['layout'] : 'wide',
            'features'      => array_values( $features ),
            'custom_css'    => wp_strip_all_tags( $input['custom_css'] ?? '' ),
        );
    }

    public function sanitize_advanced( $input ) {
        // Xử lý Import
        if ( ! empty( $input['import_data'] ) ) {
            $import = json_decode( wp_unslash( $input['import_data'] ), true );
            if ( is_array( $import ) ) {
                foreach ( $import as $option_name => $option_value ) {
                    if ( in_array( $option_name, array(
                        'asp_general_options', 'asp_email_options',
                        'asp_display_options', 'asp_advanced_options'
                    ))) {
                        update_option( $option_name, $option_value );
                    }
                }
                add_settings_error( 'asp_advanced_options', 'import_success',
                    'Đã nhập cài đặt thành công!', 'success' );
            } else {
                add_settings_error( 'asp_advanced_options', 'import_error',
                    'Dữ liệu JSON không hợp lệ!', 'error' );
            }
        }

        return array(
            'debug'     => ! empty( $input['debug'] ),
            'cache_ttl' => max( 0, min( 86400, absint( $input['cache_ttl'] ?? 3600 ) ) ),
            'api_key'   => sanitize_text_field( $input['api_key'] ?? '' ),
        );
    }

    /**
     * Render trang settings với tabs
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_tab = sanitize_key( $_GET['tab'] ?? 'general' );
        if ( ! array_key_exists( $current_tab, $this->tabs ) ) {
            $current_tab = 'general';
        }
        ?>
        <div class="wrap asp-settings-wrap">
            <h1>
                <span class="dashicons dashicons-admin-settings" style="font-size:30px; margin-right:10px;"></span>
                <?php echo esc_html( get_admin_page_title() ); ?>
            </h1>

            <?php settings_errors(); ?>

            <!-- Tabs Navigation -->
            <nav class="nav-tab-wrapper" style="margin-bottom:0;">
                <?php foreach ( $this->tabs as $tab_key => $tab_info ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array(
                            'page' => 'adv-settings',
                            'tab'  => $tab_key,
                        ), admin_url( 'admin.php' ) ) ); ?>"
                       class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $tab_info['icon'] ); ?> asp-tab-icon"></span>
                        <?php echo esc_html( $tab_info['title'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Tab Content -->
            <div class="asp-card">
                <form method="post" action="options.php">
                    <?php
                    switch ( $current_tab ) {
                        case 'general':
                            settings_fields( 'asp_general' );
                            do_settings_sections( 'asp-general' );
                            break;
                        case 'email':
                            settings_fields( 'asp_email' );
                            do_settings_sections( 'asp-email' );
                            break;
                        case 'display':
                            settings_fields( 'asp_display' );
                            do_settings_sections( 'asp-display' );
                            break;
                        case 'advanced':
                            settings_fields( 'asp_advanced' );
                            do_settings_sections( 'asp-advanced' );
                            break;
                    }

                    submit_button( 'Lưu cài đặt' );
                    ?>
                </form>
            </div>

            <!-- Footer Info -->
            <p style="margin-top:20px; color:#666;">
                <span class="dashicons dashicons-info"></span>
                Plugin version <?php echo esc_html( ASP_VERSION ); ?> |
                Tab hiện tại: <strong><?php echo esc_html( $this->tabs[ $current_tab ]['title'] ); ?></strong>
            </p>
        </div>
        <?php
    }
}

// Khởi tạo plugin
Advanced_Settings_Plugin::get_instance();
```

---

## 9. Best Practices

### 1. Luôn dùng Settings API

```php
<?php
// SAI: Tự xử lý form
if ( $_POST['action'] === 'save' ) {
    update_option( 'my_opt', $_POST['value'] ); // Không an toàn!
}

// ĐÚNG: Dùng Settings API
register_setting( 'my_group', 'my_opt', array(
    'sanitize_callback' => 'sanitize_text_field',
));
// WordPress tự động kiểm tra nonce, sanitize, và lưu
```

### 2. Prefix tất cả

```php
<?php
// SAI: Dễ bị trùng tên
add_menu_page( 'Settings', 'Settings', ... );

// ĐÚNG: Prefix duy nhất
add_menu_page( 'My Plugin Settings', 'My Plugin', ... );
```

### 3. Chỉ load assets khi cần

```php
<?php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Chỉ load trên trang settings của plugin
    if ( strpos( $hook, 'my-plugin' ) === false ) {
        return;
    }
    wp_enqueue_style( 'my-plugin-admin' );
});
```

### 4. Dùng wp_parse_args cho defaults

```php
<?php
// Đảm bảo tất cả keys luôn có giá trị
$defaults = array( 'name' => '', 'email' => '', 'age' => 25 );
$options = wp_parse_args( get_option( 'my_options', array() ), $defaults );
// $options luôn có đủ 3 keys kể cả khi database chưa có
```

### 5. Tách code thành nhiều file

```php
<?php
// File chính chỉ load các file khác
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-settings.php';
}
```

---

## Tham khảo

- [WordPress Settings API](https://developer.wordpress.org/plugins/settings/settings-api/)
- [add_menu_page()](https://developer.wordpress.org/reference/functions/add_menu_page/)
- [add_submenu_page()](https://developer.wordpress.org/reference/functions/add_submenu_page/)
- [WordPress Dashicons](https://developer.wordpress.org/resource/dashicons/)
- [Data Validation](https://developer.wordpress.org/plugins/security/data-validation/)
