# Hooks Trong Plugin - Best Practices

## Mục Lục

1. [Giới thiệu](#1-giới-thiệu)
2. [Plugin Activation/Deactivation Hooks](#2-plugin-activationdeactivation-hooks)
3. [Hooks cho Admin Pages](#3-hooks-cho-admin-pages)
4. [Hooks cho Custom Post Types](#4-hooks-cho-custom-post-types)
5. [Hooks cho Shortcodes](#5-hooks-cho-shortcodes)
6. [Hooks cho REST API Endpoints](#6-hooks-cho-rest-api-endpoints)
7. [Hooks cho Cron Jobs](#7-hooks-cho-cron-jobs)
8. [Hooks cho Email](#8-hooks-cho-email)
9. [Remove Hooks từ Plugin/Theme Khác](#9-remove-hooks-từ-plugintheme-khác)
10. [Conditional Hooks](#10-conditional-hooks)
11. [Plugin hoàn chỉnh: Task Manager](#11-plugin-hoàn-chỉnh-task-manager)
12. [Best Practices](#12-best-practices)

---

## 1. Giới thiệu

Plugin là nơi hooks được sử dụng nhiều nhất trong WordPress. File này hướng dẫn cách dùng hooks hiệu quả khi phát triển plugin, từ activation đến các tình huống phức tạp.

### So sánh với Laravel

```
Laravel Plugin (Package):
    - ServiceProvider::register()    → plugins_loaded / init
    - ServiceProvider::boot()        → after_setup_theme / wp_loaded
    - Routes                         → rest_api_init
    - Migrations                     → register_activation_hook
    - Artisan Commands              → WP-CLI commands
    - Queue Jobs                     → wp_schedule_event (cron)

WordPress Plugin:
    - Main plugin file               → Entry point
    - register_activation_hook()     → Chạy khi activate
    - register_deactivation_hook()   → Chạy khi deactivate
    - add_action('init')             → Đăng ký CPT, taxonomy, etc.
    - add_action('admin_menu')       → Tạo admin pages
```

---

## 2. Plugin Activation/Deactivation Hooks

### 2.1 register_activation_hook

```
Khi nào chạy : KHI và CHỈ KHI plugin được activate (1 lần duy nhất)
Tham số      : $file (string) - đường dẫn file chính plugin
               $callback (callable) - hàm xử lý
Dùng để      : Tạo database tables, set default options, flush rewrite rules
```

```php
<?php
/**
 * Plugin Name: My Task Manager
 * Description: Plugin quản lý công việc
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Hằng số plugin
define( 'MY_TASK_VERSION', '1.0.0' );
define( 'MY_TASK_FILE', __FILE__ );
define( 'MY_TASK_PATH', plugin_dir_path( __FILE__ ) );
define( 'MY_TASK_URL', plugin_dir_url( __FILE__ ) );

// === ACTIVATION ===
register_activation_hook( __FILE__, 'my_task_activate' );
function my_task_activate() {
    // 1. Kiểm tra yêu cầu hệ thống
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            'Plugin My Task Manager yêu cầu PHP 7.4 trở lên. PHP hiện tại: ' . PHP_VERSION,
            'Lỗi kích hoạt plugin',
            array( 'back_link' => true )
        );
    }

    if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            'Plugin My Task Manager yêu cầu WordPress 5.8 trở lên.',
            'Lỗi kích hoạt plugin',
            array( 'back_link' => true )
        );
    }

    // 2. Tạo database tables
    my_task_create_tables();

    // 3. Set default options
    $default_options = array(
        'tasks_per_page'      => 20,
        'enable_notifications' => true,
        'default_priority'    => 'medium',
        'date_format'         => 'd/m/Y',
        'allowed_roles'       => array( 'administrator', 'editor' ),
    );

    // Chỉ set nếu chưa có (tránh ghi đè khi re-activate)
    if ( false === get_option( 'my_task_settings' ) ) {
        add_option( 'my_task_settings', $default_options );
    }

    // 4. Lưu version để kiểm tra upgrade sau này
    update_option( 'my_task_version', MY_TASK_VERSION );

    // 5. Tạo capabilities
    my_task_add_capabilities();

    // 6. Đăng ký Custom Post Type (cần trước flush rewrite rules)
    my_task_register_post_type();

    // 7. Flush rewrite rules (để URLs mới hoạt động)
    flush_rewrite_rules();

    // 8. Log activation
    error_log( '[My Task Manager] Plugin activated. Version: ' . MY_TASK_VERSION );
}

/**
 * Tạo custom database tables
 */
function my_task_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'task_logs';

    // Sử dụng dbDelta() - hàm WordPress để tạo/update tables an toàn
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        task_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        action varchar(50) NOT NULL,
        details text,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY task_id (task_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    // dbDelta() tự tạo table nếu chưa có, hoặc update nếu schema thay đổi
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Thêm capabilities cho roles
 */
function my_task_add_capabilities() {
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->add_cap( 'manage_tasks' );
        $admin->add_cap( 'edit_tasks' );
        $admin->add_cap( 'delete_tasks' );
        $admin->add_cap( 'assign_tasks' );
    }

    $editor = get_role( 'editor' );
    if ( $editor ) {
        $editor->add_cap( 'manage_tasks' );
        $editor->add_cap( 'edit_tasks' );
    }
}
```

### 2.2 register_deactivation_hook

```
Khi nào chạy : Khi plugin bị deactivate
Dùng để      : Cleanup tạm thời (cron, transients, rewrite rules)
LƯU Ý       : KHÔNG xóa data! User có thể activate lại
```

```php
<?php
register_deactivation_hook( __FILE__, 'my_task_deactivate' );
function my_task_deactivate() {
    // 1. Xóa scheduled cron events
    wp_clear_scheduled_hook( 'my_task_daily_digest' );
    wp_clear_scheduled_hook( 'my_task_overdue_check' );

    // 2. Flush rewrite rules (loại bỏ custom URLs)
    flush_rewrite_rules();

    // 3. Xóa transient caches
    delete_transient( 'my_task_dashboard_data' );
    delete_transient( 'my_task_stats' );

    // KHÔNG XÓA:
    // - Database tables (user có thể activate lại)
    // - Options (cài đặt plugin)
    // - User meta
    // - Post meta
    // - Custom Post Type data

    error_log( '[My Task Manager] Plugin deactivated.' );
}
```

### 2.3 register_uninstall_hook (Xóa sạch khi GỠ CÀI ĐẶT)

```php
<?php
// Cách 1: Hook function
register_uninstall_hook( __FILE__, 'my_task_uninstall' );

function my_task_uninstall() {
    // CHỈ CHẠY KHI USER XÓA PLUGIN (Delete)
    // Đây là lúc xóa TOÀN BỘ data

    global $wpdb;

    // 1. Xóa custom table
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}task_logs" );

    // 2. Xóa tất cả posts của custom post type
    $tasks = get_posts( array(
        'post_type'      => 'task',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ));
    foreach ( $tasks as $task_id ) {
        wp_delete_post( $task_id, true ); // true = force delete
    }

    // 3. Xóa options
    delete_option( 'my_task_settings' );
    delete_option( 'my_task_version' );

    // 4. Xóa tất cả post meta liên quan
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_task_%'" );

    // 5. Xóa user meta liên quan
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_my_task_%'" );

    // 6. Xóa capabilities
    $roles = array( 'administrator', 'editor' );
    foreach ( $roles as $role_name ) {
        $role = get_role( $role_name );
        if ( $role ) {
            $role->remove_cap( 'manage_tasks' );
            $role->remove_cap( 'edit_tasks' );
            $role->remove_cap( 'delete_tasks' );
            $role->remove_cap( 'assign_tasks' );
        }
    }

    // 7. Xóa transients
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_my_task_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_my_task_%'" );

    // 8. Flush cache
    wp_cache_flush();
}

// Cách 2: File uninstall.php (khuyến khích)
// Tạo file: wp-content/plugins/my-plugin/uninstall.php
// WordPress tự gọi file này khi user xóa plugin
```

---

## 3. Hooks cho Admin Pages

### Settings Page hoàn chỉnh với Settings API

```php
<?php
// === ĐĂNG KÝ MENU ===
add_action( 'admin_menu', 'my_task_admin_menu' );
function my_task_admin_menu() {
    add_menu_page(
        'Task Manager',
        'Tasks',
        'manage_tasks',           // Custom capability
        'my-task-manager',
        'my_task_dashboard_page',
        'dashicons-list-view',
        30
    );

    add_submenu_page(
        'my-task-manager',
        'Tất cả Tasks',
        'Tất cả Tasks',
        'manage_tasks',
        'my-task-manager',        // Giống parent slug = thay thế
        'my_task_dashboard_page'
    );

    // Submenu: Cài đặt
    $settings_hook = add_submenu_page(
        'my-task-manager',
        'Cài đặt Task Manager',
        'Cài đặt',
        'manage_options',
        'my-task-settings',
        'my_task_settings_page'
    );

    // Hook chỉ load khi mở trang Settings
    add_action( "load-{$settings_hook}", 'my_task_settings_page_load' );
}

function my_task_settings_page_load() {
    // Code ở đây CHỈ chạy khi user mở trang Settings
    // Dùng cho: add_screen_option(), add_help_tab()

    // Thêm Help tab
    $screen = get_current_screen();
    $screen->add_help_tab( array(
        'id'      => 'my-task-help',
        'title'   => 'Hướng dẫn',
        'content' => '<p>Hướng dẫn sử dụng Task Manager...</p>',
    ));
}

// === ĐĂNG KÝ SETTINGS ===
add_action( 'admin_init', 'my_task_register_settings' );
function my_task_register_settings() {
    // Đăng ký setting group
    register_setting(
        'my_task_settings_group',   // Option group
        'my_task_settings',         // Option name
        array(
            'type'              => 'array',
            'sanitize_callback' => 'my_task_sanitize_settings',
            'default'           => array(),
        )
    );

    // Section: Cài đặt chung
    add_settings_section(
        'my_task_general_section',
        'Cài đặt chung',
        function() {
            echo '<p>Cấu hình các thiết lập chung cho Task Manager.</p>';
        },
        'my-task-settings'
    );

    // Field: Số tasks mỗi trang
    add_settings_field(
        'tasks_per_page',
        'Số tasks mỗi trang',
        function() {
            $options = get_option( 'my_task_settings', array() );
            $value   = $options['tasks_per_page'] ?? 20;
            echo '<input type="number" name="my_task_settings[tasks_per_page]" value="' . esc_attr( $value ) . '" min="5" max="100" class="small-text">';
            echo '<p class="description">Số lượng tasks hiển thị mỗi trang (5-100).</p>';
        },
        'my-task-settings',
        'my_task_general_section'
    );

    // Field: Bật thông báo
    add_settings_field(
        'enable_notifications',
        'Thông báo email',
        function() {
            $options = get_option( 'my_task_settings', array() );
            $checked = ! empty( $options['enable_notifications'] );
            echo '<label>';
            echo '<input type="checkbox" name="my_task_settings[enable_notifications]" value="1" ' . checked( $checked, true, false ) . '>';
            echo ' Gửi email thông báo khi task được giao hoặc cập nhật';
            echo '</label>';
        },
        'my-task-settings',
        'my_task_general_section'
    );

    // Field: Mức ưu tiên mặc định
    add_settings_field(
        'default_priority',
        'Mức ưu tiên mặc định',
        function() {
            $options  = get_option( 'my_task_settings', array() );
            $current  = $options['default_priority'] ?? 'medium';
            $priorities = array(
                'low'      => 'Thấp',
                'medium'   => 'Trung bình',
                'high'     => 'Cao',
                'critical' => 'Khẩn cấp',
            );
            echo '<select name="my_task_settings[default_priority]">';
            foreach ( $priorities as $value => $label ) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $value ),
                    selected( $current, $value, false ),
                    esc_html( $label )
                );
            }
            echo '</select>';
        },
        'my-task-settings',
        'my_task_general_section'
    );

    // Section: Phân quyền
    add_settings_section(
        'my_task_permissions_section',
        'Phân quyền',
        function() {
            echo '<p>Cấu hình roles nào được phép quản lý tasks.</p>';
        },
        'my-task-settings'
    );

    add_settings_field(
        'allowed_roles',
        'Roles được phép',
        function() {
            $options       = get_option( 'my_task_settings', array() );
            $allowed_roles = $options['allowed_roles'] ?? array( 'administrator' );
            $all_roles     = wp_roles()->get_names();

            foreach ( $all_roles as $role_key => $role_name ) {
                $checked = in_array( $role_key, $allowed_roles, true );
                printf(
                    '<label style="display:block; margin-bottom:5px;">' .
                    '<input type="checkbox" name="my_task_settings[allowed_roles][]" value="%s" %s> %s' .
                    '</label>',
                    esc_attr( $role_key ),
                    checked( $checked, true, false ),
                    esc_html( translate_user_role( $role_name ) )
                );
            }
        },
        'my-task-settings',
        'my_task_permissions_section'
    );
}

// Sanitize callback
function my_task_sanitize_settings( $input ) {
    $sanitized = array();

    $sanitized['tasks_per_page'] = absint( $input['tasks_per_page'] ?? 20 );
    $sanitized['tasks_per_page'] = max( 5, min( 100, $sanitized['tasks_per_page'] ) );

    $sanitized['enable_notifications'] = ! empty( $input['enable_notifications'] );

    $valid_priorities = array( 'low', 'medium', 'high', 'critical' );
    $sanitized['default_priority'] = in_array( $input['default_priority'] ?? '', $valid_priorities, true )
        ? $input['default_priority']
        : 'medium';

    $sanitized['allowed_roles'] = array_map( 'sanitize_text_field', $input['allowed_roles'] ?? array() );

    return $sanitized;
}

// Render trang Settings
function my_task_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Không có quyền truy cập.' );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <?php settings_errors( 'my_task_settings' ); ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'my_task_settings_group' );
            do_settings_sections( 'my-task-settings' );
            submit_button( 'Lưu cài đặt' );
            ?>
        </form>
    </div>
    <?php
}

// Dashboard page
function my_task_dashboard_page() {
    if ( ! current_user_can( 'manage_tasks' ) ) {
        wp_die( 'Không có quyền truy cập.' );
    }
    ?>
    <div class="wrap">
        <h1>Task Manager Dashboard</h1>
        <p>Quản lý công việc của bạn.</p>
    </div>
    <?php
}
```

---

## 4. Hooks cho Custom Post Types

### Meta Boxes và Save Post

```php
<?php
// Đăng ký CPT
add_action( 'init', 'my_task_register_post_type' );
function my_task_register_post_type() {
    register_post_type( 'task', array(
        'labels' => array(
            'name'          => 'Công việc',
            'singular_name' => 'Công việc',
            'add_new'       => 'Thêm công việc',
            'add_new_item'  => 'Thêm công việc mới',
            'edit_item'     => 'Sửa công việc',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'my-task-manager', // Hiển thị dưới Task Manager menu
        'supports'     => array( 'title', 'editor' ),
        'show_in_rest' => true,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
    ));
}

// Thêm Meta Boxes
add_action( 'add_meta_boxes', 'my_task_add_meta_boxes' );
function my_task_add_meta_boxes() {
    add_meta_box(
        'my_task_details',           // ID
        'Chi tiết công việc',        // Tiêu đề
        'my_task_details_callback',  // Callback render
        'task',                      // Post type
        'normal',                    // Context: normal, side, advanced
        'high'                       // Priority: high, core, default, low
    );

    add_meta_box(
        'my_task_assignee',
        'Người thực hiện',
        'my_task_assignee_callback',
        'task',
        'side',
        'default'
    );
}

function my_task_details_callback( $post ) {
    // Nonce field cho bảo mật
    wp_nonce_field( 'my_task_save_details', 'my_task_details_nonce' );

    // Lấy giá trị đã lưu
    $priority  = get_post_meta( $post->ID, '_task_priority', true ) ?: 'medium';
    $due_date  = get_post_meta( $post->ID, '_task_due_date', true );
    $status    = get_post_meta( $post->ID, '_task_status', true ) ?: 'open';
    $estimated = get_post_meta( $post->ID, '_task_estimated_hours', true );

    ?>
    <table class="form-table">
        <tr>
            <th><label for="task_priority">Mức ưu tiên</label></th>
            <td>
                <select name="task_priority" id="task_priority">
                    <option value="low" <?php selected( $priority, 'low' ); ?>>Thấp</option>
                    <option value="medium" <?php selected( $priority, 'medium' ); ?>>Trung bình</option>
                    <option value="high" <?php selected( $priority, 'high' ); ?>>Cao</option>
                    <option value="critical" <?php selected( $priority, 'critical' ); ?>>Khẩn cấp</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="task_status">Trạng thái</label></th>
            <td>
                <select name="task_status" id="task_status">
                    <option value="open" <?php selected( $status, 'open' ); ?>>Mở</option>
                    <option value="in_progress" <?php selected( $status, 'in_progress' ); ?>>Đang thực hiện</option>
                    <option value="review" <?php selected( $status, 'review' ); ?>>Đang review</option>
                    <option value="completed" <?php selected( $status, 'completed' ); ?>>Hoàn thành</option>
                    <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>>Đã hủy</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="task_due_date">Hạn hoàn thành</label></th>
            <td>
                <input type="date" name="task_due_date" id="task_due_date"
                       value="<?php echo esc_attr( $due_date ); ?>">
            </td>
        </tr>
        <tr>
            <th><label for="task_estimated_hours">Thời gian ước tính (giờ)</label></th>
            <td>
                <input type="number" name="task_estimated_hours" id="task_estimated_hours"
                       value="<?php echo esc_attr( $estimated ); ?>" min="0" step="0.5" class="small-text">
            </td>
        </tr>
    </table>
    <?php
}

function my_task_assignee_callback( $post ) {
    wp_nonce_field( 'my_task_save_assignee', 'my_task_assignee_nonce' );

    $assignee_id = get_post_meta( $post->ID, '_task_assignee', true );

    // Lấy danh sách users có quyền
    $users = get_users( array(
        'role__in' => array( 'administrator', 'editor', 'author' ),
        'orderby'  => 'display_name',
    ));

    ?>
    <select name="task_assignee" id="task_assignee" style="width:100%;">
        <option value="">-- Chưa giao --</option>
        <?php foreach ( $users as $user ) : ?>
            <option value="<?php echo esc_attr( $user->ID ); ?>"
                <?php selected( $assignee_id, $user->ID ); ?>>
                <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

// Lưu meta data khi save post
add_action( 'save_post_task', 'my_task_save_meta', 10, 3 );
function my_task_save_meta( $post_id, $post, $update ) {
    // Kiểm tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Kiểm tra nonce - details
    if ( ! isset( $_POST['my_task_details_nonce'] ) ||
         ! wp_verify_nonce( $_POST['my_task_details_nonce'], 'my_task_save_details' ) ) {
        return;
    }

    // Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Lấy giá trị cũ (để so sánh)
    $old_status   = get_post_meta( $post_id, '_task_status', true );
    $old_assignee = get_post_meta( $post_id, '_task_assignee', true );

    // Lưu meta
    $fields = array(
        'task_priority'        => '_task_priority',
        'task_status'          => '_task_status',
        'task_due_date'        => '_task_due_date',
        'task_estimated_hours' => '_task_estimated_hours',
    );

    foreach ( $fields as $post_key => $meta_key ) {
        if ( isset( $_POST[ $post_key ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
        }
    }

    // Lưu assignee
    if ( isset( $_POST['task_assignee'] ) ) {
        update_post_meta( $post_id, '_task_assignee', absint( $_POST['task_assignee'] ) );
    }

    // Trigger events khi có thay đổi
    $new_status   = sanitize_text_field( $_POST['task_status'] ?? '' );
    $new_assignee = absint( $_POST['task_assignee'] ?? 0 );

    // Status thay đổi
    if ( $old_status !== $new_status && ! empty( $new_status ) ) {
        do_action( 'my_task_status_changed', $post_id, $old_status, $new_status );

        if ( 'completed' === $new_status ) {
            update_post_meta( $post_id, '_task_completed_date', current_time( 'mysql' ) );
            update_post_meta( $post_id, '_task_completed_by', get_current_user_id() );
            do_action( 'my_task_completed', $post_id );
        }
    }

    // Assignee thay đổi
    if ( (int) $old_assignee !== $new_assignee && $new_assignee > 0 ) {
        do_action( 'my_task_assigned', $post_id, $new_assignee, (int) $old_assignee );
    }
}
```

---

## 5. Hooks cho Shortcodes

```php
<?php
// Đăng ký shortcodes trong hook 'init'
add_action( 'init', 'my_task_register_shortcodes' );
function my_task_register_shortcodes() {

    // [task_list] - Hiển thị danh sách tasks
    add_shortcode( 'task_list', 'my_task_list_shortcode' );

    // [task_form] - Form tạo task mới
    add_shortcode( 'task_form', 'my_task_form_shortcode' );

    // [task_stats] - Thống kê tasks
    add_shortcode( 'task_stats', 'my_task_stats_shortcode' );
}

function my_task_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'status'   => 'open',       // Lọc theo status
        'count'    => 10,            // Số lượng
        'assignee' => '',            // Lọc theo người thực hiện
        'priority' => '',            // Lọc theo priority
        'orderby'  => 'date',        // Sắp xếp
        'order'    => 'DESC',
    ), $atts, 'task_list' );

    // Filter: Cho phép sửa đổi atts trước khi query
    $atts = apply_filters( 'my_task_list_shortcode_atts', $atts );

    $args = array(
        'post_type'      => 'task',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'meta_query'     => array(),
    );

    if ( ! empty( $atts['status'] ) ) {
        $args['meta_query'][] = array(
            'key'   => '_task_status',
            'value' => $atts['status'],
        );
    }

    if ( ! empty( $atts['assignee'] ) ) {
        $args['meta_query'][] = array(
            'key'   => '_task_assignee',
            'value' => intval( $atts['assignee'] ),
        );
    }

    if ( ! empty( $atts['priority'] ) ) {
        $args['meta_query'][] = array(
            'key'   => '_task_priority',
            'value' => $atts['priority'],
        );
    }

    if ( count( $args['meta_query'] ) > 1 ) {
        $args['meta_query']['relation'] = 'AND';
    }

    $query = new WP_Query( $args );

    ob_start();

    // Action: Trước danh sách
    do_action( 'my_task_before_list', $atts );

    if ( $query->have_posts() ) {
        echo '<div class="my-task-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $task_id  = get_the_ID();
            $priority = get_post_meta( $task_id, '_task_priority', true );
            $status   = get_post_meta( $task_id, '_task_status', true );
            $due_date = get_post_meta( $task_id, '_task_due_date', true );

            // Filter: Cho phép sửa đổi HTML mỗi task item
            $item_html = sprintf(
                '<div class="task-item task-priority-%s task-status-%s">
                    <h3>%s</h3>
                    <span class="task-priority">%s</span>
                    <span class="task-status">%s</span>
                    %s
                </div>',
                esc_attr( $priority ),
                esc_attr( $status ),
                esc_html( get_the_title() ),
                esc_html( ucfirst( $priority ) ),
                esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ),
                $due_date ? '<span class="task-due">Hạn: ' . esc_html( date_i18n( 'd/m/Y', strtotime( $due_date ) ) ) . '</span>' : ''
            );

            echo apply_filters( 'my_task_list_item_html', $item_html, $task_id );
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo apply_filters( 'my_task_list_empty_message', '<p>Không có công việc nào.</p>', $atts );
    }

    do_action( 'my_task_after_list', $atts );

    return ob_get_clean();
}

function my_task_stats_shortcode( $atts ) {
    // Cache stats bằng transient (tránh query nặng mỗi page load)
    $stats = get_transient( 'my_task_stats' );

    if ( false === $stats ) {
        global $wpdb;

        $stats = array(
            'total'        => 0,
            'open'         => 0,
            'in_progress'  => 0,
            'completed'    => 0,
            'overdue'      => 0,
        );

        $results = $wpdb->get_results(
            "SELECT pm.meta_value as status, COUNT(*) as count
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'task' AND p.post_status = 'publish'
             AND pm.meta_key = '_task_status'
             GROUP BY pm.meta_value"
        );

        foreach ( $results as $row ) {
            $stats[ $row->status ] = intval( $row->count );
            $stats['total'] += intval( $row->count );
        }

        // Cache 5 phút
        set_transient( 'my_task_stats', $stats, 5 * MINUTE_IN_SECONDS );
    }

    $stats = apply_filters( 'my_task_stats_data', $stats );

    ob_start();
    ?>
    <div class="my-task-stats" style="display:flex; gap:15px; flex-wrap:wrap;">
        <div class="stat-card" style="flex:1; min-width:120px; padding:15px; background:#f0f0f1; border-radius:5px; text-align:center;">
            <div style="font-size:24px; font-weight:bold;"><?php echo number_format( $stats['total'] ); ?></div>
            <div>Tổng cộng</div>
        </div>
        <div class="stat-card" style="flex:1; min-width:120px; padding:15px; background:#e8f5e9; border-radius:5px; text-align:center;">
            <div style="font-size:24px; font-weight:bold; color:#2e7d32;"><?php echo number_format( $stats['open'] ); ?></div>
            <div>Đang mở</div>
        </div>
        <div class="stat-card" style="flex:1; min-width:120px; padding:15px; background:#e3f2fd; border-radius:5px; text-align:center;">
            <div style="font-size:24px; font-weight:bold; color:#1565c0;"><?php echo number_format( $stats['in_progress'] ); ?></div>
            <div>Đang làm</div>
        </div>
        <div class="stat-card" style="flex:1; min-width:120px; padding:15px; background:#f3e5f5; border-radius:5px; text-align:center;">
            <div style="font-size:24px; font-weight:bold; color:#7b1fa2;"><?php echo number_format( $stats['completed'] ); ?></div>
            <div>Hoàn thành</div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
```

---

## 6. Hooks cho REST API Endpoints

```php
<?php
add_action( 'rest_api_init', 'my_task_register_rest_routes' );
function my_task_register_rest_routes() {

    $namespace = 'my-task/v1';

    // GET /wp-json/my-task/v1/tasks
    register_rest_route( $namespace, '/tasks', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'my_task_rest_get_tasks',
        'permission_callback' => function() {
            return current_user_can( 'manage_tasks' );
        },
        'args' => array(
            'status' => array(
                'default'           => 'all',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'per_page' => array(
                'default'           => 20,
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // POST /wp-json/my-task/v1/tasks
    register_rest_route( $namespace, '/tasks', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'my_task_rest_create_task',
        'permission_callback' => function() {
            return current_user_can( 'edit_tasks' );
        },
        'args' => array(
            'title' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'default'           => '',
                'sanitize_callback' => 'wp_kses_post',
            ),
            'priority' => array(
                'default'           => 'medium',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $value ) {
                    return in_array( $value, array( 'low', 'medium', 'high', 'critical' ), true );
                },
            ),
            'assignee' => array(
                'default'           => 0,
                'sanitize_callback' => 'absint',
            ),
            'due_date' => array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // PUT /wp-json/my-task/v1/tasks/<id>/status
    register_rest_route( $namespace, '/tasks/(?P<id>\d+)/status', array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => 'my_task_rest_update_status',
        'permission_callback' => function( $request ) {
            $task_id = $request->get_param( 'id' );
            return current_user_can( 'edit_post', $task_id );
        },
        'args' => array(
            'status' => array(
                'required' => true,
                'validate_callback' => function( $value ) {
                    return in_array( $value, array( 'open', 'in_progress', 'review', 'completed', 'cancelled' ), true );
                },
            ),
        ),
    ));
}

function my_task_rest_get_tasks( WP_REST_Request $request ) {
    $status   = $request->get_param( 'status' );
    $per_page = $request->get_param( 'per_page' );

    $args = array(
        'post_type'      => 'task',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
    );

    if ( 'all' !== $status ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_task_status',
                'value' => $status,
            ),
        );
    }

    $query = new WP_Query( $args );
    $tasks = array();

    while ( $query->have_posts() ) {
        $query->the_post();
        $task_id = get_the_ID();

        $assignee_id = get_post_meta( $task_id, '_task_assignee', true );
        $assignee    = $assignee_id ? get_userdata( $assignee_id ) : null;

        $tasks[] = array(
            'id'          => $task_id,
            'title'       => get_the_title(),
            'description' => get_the_content(),
            'priority'    => get_post_meta( $task_id, '_task_priority', true ),
            'status'      => get_post_meta( $task_id, '_task_status', true ),
            'due_date'    => get_post_meta( $task_id, '_task_due_date', true ),
            'assignee'    => $assignee ? array(
                'id'   => $assignee->ID,
                'name' => $assignee->display_name,
            ) : null,
            'created'     => get_the_date( 'c' ),
            'modified'    => get_the_modified_date( 'c' ),
        );
    }
    wp_reset_postdata();

    // Filter: Cho phép modify response
    $tasks = apply_filters( 'my_task_rest_tasks_response', $tasks, $request );

    return new WP_REST_Response( array(
        'tasks' => $tasks,
        'total' => $query->found_posts,
    ), 200 );
}

function my_task_rest_create_task( WP_REST_Request $request ) {
    $data = array(
        'title'       => $request->get_param( 'title' ),
        'description' => $request->get_param( 'description' ),
        'priority'    => $request->get_param( 'priority' ),
        'assignee'    => $request->get_param( 'assignee' ),
        'due_date'    => $request->get_param( 'due_date' ),
    );

    $task_id = wp_insert_post( array(
        'post_type'    => 'task',
        'post_title'   => $data['title'],
        'post_content' => $data['description'],
        'post_status'  => 'publish',
    ));

    if ( is_wp_error( $task_id ) ) {
        return new WP_REST_Response( array( 'message' => 'Không thể tạo task.' ), 500 );
    }

    update_post_meta( $task_id, '_task_priority', $data['priority'] );
    update_post_meta( $task_id, '_task_status', 'open' );
    update_post_meta( $task_id, '_task_assignee', $data['assignee'] );
    update_post_meta( $task_id, '_task_due_date', $data['due_date'] );

    // Fire custom hook
    do_action( 'my_task_created_via_api', $task_id, $data );

    return new WP_REST_Response( array(
        'id'      => $task_id,
        'message' => 'Task đã được tạo thành công.',
    ), 201 );
}

function my_task_rest_update_status( WP_REST_Request $request ) {
    $task_id    = $request->get_param( 'id' );
    $new_status = $request->get_param( 'status' );
    $old_status = get_post_meta( $task_id, '_task_status', true );

    update_post_meta( $task_id, '_task_status', $new_status );

    do_action( 'my_task_status_changed', $task_id, $old_status, $new_status );

    // Xóa stats cache
    delete_transient( 'my_task_stats' );

    return new WP_REST_Response( array(
        'message' => sprintf( 'Task #%d đã chuyển sang trạng thái: %s', $task_id, $new_status ),
    ), 200 );
}
```

---

## 7. Hooks cho Cron Jobs

```php
<?php
// Đăng ký cron khi activate
register_activation_hook( MY_TASK_FILE, function() {
    if ( ! wp_next_scheduled( 'my_task_daily_digest' ) ) {
        // Chạy hàng ngày lúc 8h sáng
        $tomorrow_8am = strtotime( 'tomorrow 08:00:00' );
        wp_schedule_event( $tomorrow_8am, 'daily', 'my_task_daily_digest' );
    }

    if ( ! wp_next_scheduled( 'my_task_overdue_check' ) ) {
        wp_schedule_event( time(), 'hourly', 'my_task_overdue_check' );
    }
});

// Hủy cron khi deactivate
register_deactivation_hook( MY_TASK_FILE, function() {
    wp_clear_scheduled_hook( 'my_task_daily_digest' );
    wp_clear_scheduled_hook( 'my_task_overdue_check' );
});

// Handler: Gửi daily digest email
add_action( 'my_task_daily_digest', 'my_task_send_daily_digest' );
function my_task_send_daily_digest() {
    // Lấy tasks open và in_progress
    $tasks = get_posts( array(
        'post_type'      => 'task',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_task_status',
                'value'   => array( 'open', 'in_progress' ),
                'compare' => 'IN',
            ),
        ),
    ));

    if ( empty( $tasks ) ) {
        return;
    }

    // Nhóm tasks theo assignee
    $grouped = array();
    foreach ( $tasks as $task ) {
        $assignee_id = get_post_meta( $task->ID, '_task_assignee', true ) ?: 0;
        $grouped[ $assignee_id ][] = $task;
    }

    // Gửi email cho mỗi assignee
    foreach ( $grouped as $user_id => $user_tasks ) {
        if ( 0 === $user_id ) {
            continue; // Bỏ qua tasks chưa giao
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            continue;
        }

        $message = "Xin chào {$user->display_name},\n\n";
        $message .= "Bạn có " . count( $user_tasks ) . " công việc cần hoàn thành:\n\n";

        foreach ( $user_tasks as $task ) {
            $priority = get_post_meta( $task->ID, '_task_priority', true );
            $due_date = get_post_meta( $task->ID, '_task_due_date', true );
            $status   = get_post_meta( $task->ID, '_task_status', true );

            $message .= sprintf(
                "- [%s] %s (Hạn: %s, Ưu tiên: %s)\n",
                strtoupper( $status ),
                $task->post_title,
                $due_date ? date_i18n( 'd/m/Y', strtotime( $due_date ) ) : 'Chưa đặt',
                ucfirst( $priority )
            );
        }

        $message .= "\nQuản lý tasks: " . admin_url( 'edit.php?post_type=task' );

        $subject = apply_filters( 'my_task_digest_subject', sprintf(
            '[%s] Tóm tắt công việc hàng ngày',
            get_bloginfo( 'name' )
        ));

        wp_mail( $user->user_email, $subject, $message );
    }

    do_action( 'my_task_daily_digest_sent', $grouped );
}

// Handler: Kiểm tra tasks quá hạn
add_action( 'my_task_overdue_check', 'my_task_check_overdue' );
function my_task_check_overdue() {
    $today = date( 'Y-m-d' );

    $overdue_tasks = get_posts( array(
        'post_type'      => 'task',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_task_status',
                'value'   => array( 'open', 'in_progress' ),
                'compare' => 'IN',
            ),
            array(
                'key'     => '_task_due_date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_task_overdue_notified',
                'compare' => 'NOT EXISTS',  // Chỉ notify 1 lần
            ),
        ),
    ));

    foreach ( $overdue_tasks as $task ) {
        // Đánh dấu đã notify
        update_post_meta( $task->ID, '_task_overdue_notified', current_time( 'mysql' ) );

        // Fire hook
        do_action( 'my_task_overdue', $task->ID );
    }
}

// Xử lý khi task quá hạn
add_action( 'my_task_overdue', function( $task_id ) {
    $assignee_id = get_post_meta( $task_id, '_task_assignee', true );
    if ( ! $assignee_id ) {
        return;
    }

    $user     = get_userdata( $assignee_id );
    $task     = get_post( $task_id );
    $due_date = get_post_meta( $task_id, '_task_due_date', true );

    wp_mail(
        $user->user_email,
        sprintf( 'QUAHẠN: %s', $task->post_title ),
        sprintf(
            "Công việc \"%s\" đã quá hạn (hạn: %s).\n\nVui lòng cập nhật trạng thái.",
            $task->post_title,
            date_i18n( 'd/m/Y', strtotime( $due_date ) )
        )
    );
});
```

---

## 8. Hooks cho Email

```php
<?php
// Gửi email khi task được giao
add_action( 'my_task_assigned', 'my_task_notify_assignee', 10, 3 );
function my_task_notify_assignee( $task_id, $new_assignee_id, $old_assignee_id ) {
    $settings = get_option( 'my_task_settings', array() );
    if ( empty( $settings['enable_notifications'] ) ) {
        return; // Notifications bị tắt
    }

    $task = get_post( $task_id );
    $user = get_userdata( $new_assignee_id );

    if ( ! $task || ! $user ) {
        return;
    }

    $priority = get_post_meta( $task_id, '_task_priority', true );
    $due_date = get_post_meta( $task_id, '_task_due_date', true );

    // Filter: Cho phép customize email
    $subject = apply_filters( 'my_task_assigned_email_subject',
        sprintf( 'Công việc mới: %s', $task->post_title ),
        $task_id
    );

    $message = apply_filters( 'my_task_assigned_email_body',
        sprintf(
            "Xin chào %s,\n\n" .
            "Bạn được giao công việc mới:\n\n" .
            "Tiêu đề: %s\n" .
            "Ưu tiên: %s\n" .
            "Hạn: %s\n\n" .
            "Chi tiết: %s\n\n" .
            "Trân trọng!",
            $user->display_name,
            $task->post_title,
            ucfirst( $priority ),
            $due_date ? date_i18n( 'd/m/Y', strtotime( $due_date ) ) : 'Chưa đặt',
            admin_url( 'post.php?post=' . $task_id . '&action=edit' )
        ),
        $task_id,
        $new_assignee_id
    );

    wp_mail( $user->user_email, $subject, $message );
}
```

---

## 9. Remove Hooks từ Plugin/Theme Khác

```php
<?php
// === KỸ THUẬT 1: Remove named functions ===
// Biết chính xác tên function và priority
add_action( 'init', function() {
    // Gỡ bỏ redirect trang login từ plugin security
    remove_action( 'login_init', 'security_plugin_redirect_login', 10 );

    // Gỡ bỏ filter từ SEO plugin
    remove_filter( 'the_title', 'seo_plugin_modify_title', 10 );
}, 20 ); // Priority cao hơn để chạy sau khi plugin kia đã add

// === KỸ THUẬT 2: Remove class methods ===
// Khi callback là method của class (phổ biến ở plugins OOP)
add_action( 'init', function() {
    global $wp_filter;

    // Tìm và remove callback từ class instance
    if ( isset( $wp_filter['wp_head'] ) ) {
        foreach ( $wp_filter['wp_head']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $key => $callback ) {
                // Kiểm tra xem callback có phải method của class mong muốn
                if ( is_array( $callback['function'] ) ) {
                    $object = $callback['function'][0];
                    $method = $callback['function'][1];

                    if ( is_object( $object ) && get_class( $object ) === 'WP_Super_Plugin' && $method === 'add_scripts' ) {
                        remove_action( 'wp_head', array( $object, 'add_scripts' ), $priority );
                    }
                }
            }
        }
    }
}, 999 );

// === KỸ THUẬT 3: Remove tất cả callbacks ở priority cụ thể ===
add_action( 'wp_loaded', function() {
    // Xóa tất cả callbacks ở priority 10 của wp_head
    // CẨN THẬN: Có thể gây hỏng chức năng!
    remove_all_actions( 'wp_head', 10 );
});

// === KỸ THUẬT 4: Unhook và re-hook với modification ===
add_action( 'init', function() {
    // Gỡ filter cũ
    remove_filter( 'the_content', 'original_plugin_content_filter', 10 );

    // Thêm filter mới (wrap filter cũ)
    add_filter( 'the_content', function( $content ) {
        // Gọi filter gốc nhưng thêm điều kiện
        if ( is_single() ) {
            // Chỉ áp dụng cho single post
            $content = original_plugin_content_filter( $content );
        }
        return $content;
    }, 10 );
}, 20 );
```

---

## 10. Conditional Hooks

```php
<?php
// === CHỈ LOAD Ở ADMIN ===
if ( is_admin() ) {
    require_once MY_TASK_PATH . 'includes/admin/class-admin.php';
    // Hoặc:
    add_action( 'admin_init', function() {
        // Code admin only
    });
}

// === CHỈ LOAD Ở FRONTEND ===
add_action( 'template_redirect', function() {
    // Ở thời điểm này, đã xác định rõ đang ở frontend
    // và biết đang xem trang gì

    if ( is_singular( 'task' ) ) {
        // Code chỉ chạy khi xem chi tiết task
        add_filter( 'the_content', 'my_task_enhance_content' );
    }
});

// === CHỈ LOAD TRÊN SPECIFIC PAGE ===
add_action( 'wp_enqueue_scripts', function() {
    // Chỉ load CSS/JS khi page có shortcode
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'task_list' ) ) {
        wp_enqueue_style( 'my-task-frontend', MY_TASK_URL . 'css/frontend.css' );
        wp_enqueue_script( 'my-task-frontend', MY_TASK_URL . 'js/frontend.js', array( 'jquery' ), MY_TASK_VERSION, true );
    }
});

// === CHỈ CHO USER CÓ QUYỀN ===
add_action( 'init', function() {
    if ( ! current_user_can( 'manage_tasks' ) ) {
        return; // Không load các hooks quản lý task
    }

    add_action( 'admin_menu', 'my_task_admin_menu' );
    add_action( 'admin_init', 'my_task_register_settings' );
});

// === CHỈ KHI PLUGIN KHÁC ACTIVE ===
add_action( 'plugins_loaded', function() {
    // WooCommerce integration
    if ( class_exists( 'WooCommerce' ) ) {
        add_action( 'woocommerce_order_status_completed', function( $order_id ) {
            // Tự động tạo task khi có đơn hàng mới
            wp_insert_post( array(
                'post_type'  => 'task',
                'post_title' => 'Xử lý đơn hàng #' . $order_id,
                'post_status' => 'publish',
            ));
        });
    }

    // BuddyPress integration
    if ( class_exists( 'BuddyPress' ) ) {
        add_action( 'my_task_completed', function( $task_id ) {
            // Post activity khi task hoàn thành
            if ( function_exists( 'bp_activity_add' ) ) {
                bp_activity_add( array(
                    'action'  => 'completed a task',
                    'content' => get_the_title( $task_id ),
                    'type'    => 'task_completed',
                ));
            }
        });
    }
});
```

---

## 11. Plugin hoàn chỉnh: Task Manager

Dưới đây là cấu trúc file của plugin hoàn chỉnh sử dụng tất cả hooks đã học:

```
my-task-manager/
├── my-task-manager.php          # Main plugin file (entry point)
├── uninstall.php                # Uninstall cleanup
├── includes/
│   ├── class-plugin.php         # Main plugin class
│   ├── class-post-type.php      # CPT registration
│   ├── class-meta-boxes.php     # Meta boxes
│   ├── class-rest-api.php       # REST API endpoints
│   ├── class-cron.php           # Cron jobs
│   ├── class-shortcodes.php     # Shortcodes
│   └── class-notifications.php  # Email notifications
├── admin/
│   ├── class-admin.php          # Admin functionality
│   ├── class-settings.php       # Settings page
│   ├── css/admin.css
│   └── js/admin.js
├── public/
│   ├── css/frontend.css
│   └── js/frontend.js
└── languages/
    └── my-task-manager-vi.po
```

```php
<?php
// File: my-task-manager.php (Entry point)

/**
 * Plugin Name: My Task Manager
 * Description: Quản lý công việc trong WordPress
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Developer
 * Text Domain: my-task-manager
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

// Constants
define( 'MY_TASK_VERSION', '1.0.0' );
define( 'MY_TASK_FILE', __FILE__ );
define( 'MY_TASK_PATH', plugin_dir_path( __FILE__ ) );
define( 'MY_TASK_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes
spl_autoload_register( function( $class ) {
    $prefix = 'My_Task_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative_class = strtolower( str_replace( '_', '-', substr( $class, strlen( $prefix ) ) ) );
    $file = MY_TASK_PATH . 'includes/class-' . $relative_class . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

// Activation/Deactivation
register_activation_hook( __FILE__, array( 'My_Task_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'My_Task_Plugin', 'deactivate' ) );

// Init plugin
add_action( 'plugins_loaded', function() {
    My_Task_Plugin::get_instance();
});
```

---

## 12. Best Practices

### 1. Prefix mọi thứ

```php
<?php
// Functions, hooks, options, meta keys, CSS classes, JS variables
// Tất cả đều cần prefix

// Functions
function my_task_get_stats() { }       // Prefix: my_task_
function my_task_send_email() { }

// Hook names
do_action( 'my_task_created' );        // Prefix: my_task_

// Options
get_option( 'my_task_settings' );      // Prefix: my_task_

// Meta keys
update_post_meta( $id, '_my_task_priority', 'high' ); // Prefix: _my_task_

// CSS classes
echo '<div class="my-task-list">';     // Prefix: my-task-

// JS global variables
wp_localize_script( 'my-task-js', 'MyTask', $data ); // Prefix: MyTask
```

### 2. Chỉ load khi cần

```php
<?php
// SAI: Load tất cả ở mọi trang
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'my-task-css', MY_TASK_URL . 'public/css/frontend.css' );
    wp_enqueue_script( 'my-task-js', MY_TASK_URL . 'public/js/frontend.js' );
});

// ĐÚNG: Chỉ load khi cần
add_action( 'wp_enqueue_scripts', function() {
    global $post;
    // Chỉ load khi trang có shortcode
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'task_list' ) ) {
        wp_enqueue_style( 'my-task-css', MY_TASK_URL . 'public/css/frontend.css', array(), MY_TASK_VERSION );
        wp_enqueue_script( 'my-task-js', MY_TASK_URL . 'public/js/frontend.js', array(), MY_TASK_VERSION, true );
    }
});
```

### 3. Cleanup khi deactivate/uninstall

```php
<?php
// Deactivate: Cleanup tạm thời
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'my_task_cron' );  // Cron
    flush_rewrite_rules();                        // Rewrite rules
});

// Uninstall: Cleanup vĩnh viễn (file uninstall.php)
// Xóa tables, options, meta, capabilities
```

### 4. Verify nonce và permissions trong mọi handler

```php
<?php
// AJAX handler
add_action( 'wp_ajax_my_task_save', function() {
    // 1. Nonce
    check_ajax_referer( 'my_task_nonce', 'security' );

    // 2. Capability
    if ( ! current_user_can( 'edit_tasks' ) ) {
        wp_send_json_error( 'Forbidden', 403 );
    }

    // 3. Sanitize input
    $title = sanitize_text_field( $_POST['title'] ?? '' );

    // 4. Process...
    wp_send_json_success();
});
```

### 5. Dùng OOP cho plugins phức tạp

```php
<?php
// Tổ chức code trong classes thay vì functions rời rạc
// Giúp: tránh conflict, dễ test, dễ maintain

class My_Task_Plugin {
    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    // ...
}
```

---

> **Tiếp theo:** [07 - Hooks Nâng Cao](07-hooks-nang-cao.md) - Kỹ thuật hooks nâng cao, OOP, performance, testing.
