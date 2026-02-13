# 02 - Dashboard: Trang Chính WordPress Admin

> **Source chính**: `wp-admin/index.php` + `wp-admin/includes/dashboard.php` (2129 dòng)
> **URL**: `/wp-admin/` hoặc `/wp-admin/index.php`
> **Capability**: `read` (tất cả user đăng nhập đều thấy)
> **Laravel tương đương**: Admin Dashboard page (trang đầu tiên khi vào admin)

---

## Mục Lục

1. [Dashboard là gì?](#1-dashboard-là-gì)
2. [Dashboard Bootstrap](#2-dashboard-bootstrap)
3. [Dashboard Widgets System](#3-dashboard-widgets-system)
4. [Default Widgets Chi Tiết](#4-default-widgets-chi-tiết)
5. [Tạo Custom Dashboard Widget](#5-tạo-custom-dashboard-widget)
6. [Xóa/Ẩn Dashboard Widgets](#6-xóaẩn-dashboard-widgets)
7. [Screen Options](#7-screen-options)
8. [Dashboard Hooks](#8-dashboard-hooks)
9. [DB: Dashboard Lưu Gì?](#9-db-dashboard-lưu-gì)
10. [So Sánh Với Laravel](#10-so-sánh-với-laravel)

---

## 1. Dashboard là gì?

Dashboard (Bảng Điều Khiển) là trang đầu tiên bạn thấy khi đăng nhập vào WordPress Admin. Nó hiển thị tổng quan nhanh về site: thống kê, bài viết gần đây, bình luận, tin tức WordPress, v.v.

### URL truy cập

```
/wp-admin/              → Redirect tới index.php
/wp-admin/index.php     → Dashboard trực tiếp
```

### Source files liên quan

```
wp-admin/index.php                    ← Trang Dashboard chính
wp-admin/includes/dashboard.php       ← Dashboard Widgets API (2129 dòng)
wp-admin/admin.php                    ← Bootstrap (được require bởi index.php)
wp-admin/admin-header.php             ← HTML header
wp-admin/admin-footer.php             ← HTML footer
```

### Giao diện Dashboard

```
┌────────────────────────────────────────────────────────────┐
│  [Admin Bar - Toolbar]                                      │
├──────────┬─────────────────────────────────────────────────┤
│          │  Dashboard                        [Screen Opts] │
│  Menu    │  ─────────────────────────────────────────────  │
│  Sidebar │                                                  │
│          │  ┌── Welcome Panel ──────────────────────────┐  │
│  Dashboard│  │ Welcome to WordPress! Here are some...    │  │
│  Posts   │  └───────────────────────────────────────────┘  │
│  Media   │                                                  │
│  Pages   │  ┌─ At a Glance ──┐  ┌── Quick Draft ───────┐  │
│  Comments│  │ 5 Posts         │  │ Title: [          ]  │  │
│  ...     │  │ 2 Pages         │  │ Content: [        ]  │  │
│          │  │ 1 Comment       │  │ [Save Draft]         │  │
│          │  │ Theme: Twenty24 │  │                      │  │
│          │  └─────────────────┘  └──────────────────────┘  │
│          │                                                  │
│          │  ┌── Activity ─────┐  ┌── WP Events & News ─┐  │
│          │  │ Recently Pub.   │  │ WordPress 6.x news  │  │
│          │  │ Recent Comments │  │ Upcoming events     │  │
│          │  └─────────────────┘  └──────────────────────┘  │
│          │                                                  │
│          │  ┌── Site Health ──────────────────────────────┐ │
│          │  │ Site Health: Good (80%)                      │ │
│          │  └─────────────────────────────────────────────┘ │
├──────────┴─────────────────────────────────────────────────┤
│  Thank you for creating with WordPress.  │  Version 6.x   │
└────────────────────────────────────────────────────────────┘
```

---

## 2. Dashboard Bootstrap

### File `wp-admin/index.php`

**Source**: `wp-admin/index.php`

Đây là file entry point cho trang Dashboard. Nó rất ngắn gọn:

```php
// wp-admin/index.php (trích đoạn quan trọng)

// Bước 1: Load WordPress admin bootstrap
require_once __DIR__ . '/admin.php';
// → Tại đây: auth_redirect() đã chạy → user phải đăng nhập
// → admin_init hook đã fire
// → Menu đã được build

// Bước 2: Load Dashboard API
require_once ABSPATH . 'wp-admin/includes/dashboard.php';

// Bước 3: Setup Dashboard Widgets
wp_dashboard_setup();
// → Đăng ký tất cả default widgets
// → Fire hook 'wp_dashboard_setup' cho plugins

// Bước 4: Enqueue scripts
wp_enqueue_script( 'dashboard' );

if ( current_user_can( 'install_plugins' ) ) {
    wp_enqueue_script( 'plugin-install' );
    wp_enqueue_script( 'updates' );
}
if ( current_user_can( 'upload_files' ) ) {
    wp_enqueue_script( 'media-upload' );
}
add_thickbox();  // Popup modal

if ( wp_is_mobile() ) {
    wp_enqueue_script( 'jquery-touch-punch' );  // Cho drag & drop trên mobile
}

// Bước 5: Set page title
$title       = __( 'Dashboard' );
$parent_file = 'index.php';

// Bước 6: Setup Help tabs
$screen = get_current_screen();
$screen->add_help_tab( array(
    'id'      => 'overview',
    'title'   => __( 'Overview' ),
    'content' => $help,
));

// Bước 7: Render page
require_once ABSPATH . 'wp-admin/admin-header.php';
// → HTML <html>, <head>, admin bar, sidebar menu

// Welcome Panel
if ( has_action( 'welcome_panel' ) && current_user_can( 'edit_theme_options' ) ) {
    $option = (int) get_user_meta( get_current_user_id(), 'show_welcome_panel', true );
    // 0 = hide, 1 = show, 2 = multisite owner
    ?>
    <div id="welcome-panel" class="welcome-panel">
        <?php do_action( 'welcome_panel' ); ?>
    </div>
    <?php
}

// Dashboard Widgets (dùng meta box system)
wp_dashboard();

require_once ABSPATH . 'wp-admin/admin-footer.php';
```

### Luồng chi tiết

```
GET /wp-admin/index.php
  │
  ├── require admin.php (bootstrap)
  │     ├── WP_ADMIN = true
  │     ├── require wp-load.php (WordPress core)
  │     ├── require includes/admin.php (admin APIs)
  │     ├── auth_redirect() → chưa login? redirect wp-login.php
  │     ├── require menu.php → build admin menu
  │     └── do_action('admin_init')
  │
  ├── require includes/dashboard.php
  │
  ├── wp_dashboard_setup()
  │     ├── Đăng ký default widgets
  │     │     ├── dashboard_site_health    (Site Health Status)
  │     │     ├── dashboard_right_now      (At a Glance)
  │     │     ├── dashboard_activity       (Activity)
  │     │     ├── dashboard_quick_press    (Quick Draft)
  │     │     └── dashboard_primary        (WordPress Events and News)
  │     │
  │     └── do_action('wp_dashboard_setup') ← Plugin thêm widget ở đây
  │
  ├── wp_enqueue_script('dashboard')
  │
  ├── require admin-header.php → HTML header + sidebar
  │
  ├── Welcome Panel (nếu chưa ẩn)
  │     └── do_action('welcome_panel')
  │
  ├── wp_dashboard() → Render tất cả widgets
  │     ├── do_meta_boxes('dashboard', 'normal', '')
  │     └── do_meta_boxes('dashboard', 'side', '')
  │
  └── require admin-footer.php → HTML footer
```

---

## 3. Dashboard Widgets System

### Source

**Source**: `wp-admin/includes/dashboard.php` - 2129 dòng

### Hàm `wp_dashboard_setup()`

Đây là hàm chính khởi tạo tất cả dashboard widgets:

```php
// wp-admin/includes/dashboard.php, dòng 20
function wp_dashboard_setup() {
    global $wp_registered_widgets, $wp_registered_widget_controls, $wp_dashboard_control_callbacks;

    $screen = get_current_screen();
    $wp_dashboard_control_callbacks = array();

    // 1. Browser version check
    $check_browser = wp_check_browser_version();
    if ( $check_browser && $check_browser['upgrade'] ) {
        wp_add_dashboard_widget( 'dashboard_browser_nag', __( 'Your browser is out of date!' ), 'wp_dashboard_browser_nag' );
    }

    // 2. PHP version check
    $check_php = wp_check_php_version();
    if ( $check_php && current_user_can( 'update_php' ) ) {
        if ( isset( $check_php['is_acceptable'] ) && ! $check_php['is_acceptable'] ) {
            wp_add_dashboard_widget( 'dashboard_php_nag', __( 'PHP Update Required' ), 'wp_dashboard_php_nag' );
        }
    }

    // 3. Site Health Status
    if ( current_user_can( 'view_site_health_checks' ) && ! is_network_admin() ) {
        wp_add_dashboard_widget( 'dashboard_site_health', __( 'Site Health Status' ), 'wp_dashboard_site_health' );
    }

    // 4. At a Glance (Right Now)
    if ( is_blog_admin() && current_user_can( 'edit_posts' ) ) {
        wp_add_dashboard_widget( 'dashboard_right_now', __( 'At a Glance' ), 'wp_dashboard_right_now' );
    }

    // 5. Activity
    if ( is_blog_admin() ) {
        wp_add_dashboard_widget( 'dashboard_activity', __( 'Activity' ), 'wp_dashboard_site_activity' );
    }

    // 6. Quick Draft
    if ( is_blog_admin() && current_user_can( get_post_type_object( 'post' )->cap->create_posts ) ) {
        wp_add_dashboard_widget( 'dashboard_quick_press', $quick_draft_title, 'wp_dashboard_quick_press' );
    }

    // 7. WordPress Events and News
    wp_add_dashboard_widget( 'dashboard_primary', __( 'WordPress Events and News' ), 'wp_dashboard_events_news' );

    // 8. Fire hook cho plugins
    do_action( 'wp_dashboard_setup' );  // ← PLUGIN HOOK TẠI ĐÂY
}
```

### Hàm `wp_add_dashboard_widget()`

**Source**: `wp-admin/includes/dashboard.php`, dòng 188

```php
function wp_add_dashboard_widget(
    $widget_id,         // ID duy nhất (string)
    $widget_name,       // Tiêu đề hiển thị (string)
    $callback,          // Hàm render nội dung (callable)
    $control_callback = null,  // Hàm render controls (callable, optional)
    $callback_args = null,     // Arguments truyền vào callback (array, optional)
    $context = 'normal',       // Vị trí: 'normal', 'side', 'column3', 'column4'
    $priority = 'core'         // Thứ tự: 'high', 'core', 'default', 'low'
) {
    // Bên trong sử dụng add_meta_box() để đăng ký
    add_meta_box( $widget_id, $widget_name, $callback, $screen, $context, $priority, $callback_args );
}
```

Điểm quan trọng: **Dashboard widgets thực chất là meta boxes**. WordPress dùng cùng hệ thống meta box cho cả Dashboard widgets và Post edit meta boxes.

### Vị trí widgets (context)

Dashboard mặc định có 2 cột:

```
┌─────────── normal ──────────┐  ┌──────── side ─────────────┐
│                              │  │                            │
│  Site Health Status          │  │  Quick Draft               │
│  At a Glance                 │  │  WordPress Events & News   │
│  Activity                    │  │                            │
│                              │  │                            │
└──────────────────────────────┘  └────────────────────────────┘
```

Khi chọn nhiều cột hơn trong Screen Options:
- `column3` - Cột thứ 3
- `column4` - Cột thứ 4

---

## 4. Default Widgets Chi Tiết

### 4.1 Welcome Panel

**Hàm**: `wp_welcome_panel()`
**Hook**: `welcome_panel`

Welcome Panel là panel lớn ở đầu Dashboard, hiển thị cho user lần đầu. User có thể ẩn bằng nút "Dismiss".

```php
// Kiểm tra hiện/ẩn Welcome Panel
$option = (int) get_user_meta( get_current_user_id(), 'show_welcome_panel', true );
// 0 = ẩn
// 1 = hiện (toggled bởi user hoặc single site creator)
// 2 = multisite site owner

// Nội dung Welcome Panel được output bởi action 'welcome_panel'
// WordPress core đăng ký: add_action('welcome_panel', 'wp_welcome_panel');
```

**Tùy chỉnh Welcome Panel**:

```php
// Thay thế nội dung Welcome Panel
remove_action('welcome_panel', 'wp_welcome_panel');
add_action('welcome_panel', function() {
    ?>
    <div class="welcome-panel-content">
        <div class="welcome-panel-header">
            <h2>Chào mừng đến với Website!</h2>
            <p>Đây là bảng điều khiển quản trị website của bạn.</p>
        </div>
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column">
                <h3>Bắt đầu</h3>
                <ul>
                    <li><a href="<?php echo admin_url('post-new.php'); ?>">Viết bài mới</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=page'); ?>">Quản lý trang</a></li>
                </ul>
            </div>
            <div class="welcome-panel-column">
                <h3>Liên kết nhanh</h3>
                <ul>
                    <li><a href="<?php echo admin_url('themes.php'); ?>">Đổi giao diện</a></li>
                    <li><a href="<?php echo admin_url('options-general.php'); ?>">Cài đặt</a></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
});
```

### 4.2 At a Glance (Thống Kê Nhanh)

**Hàm**: `wp_dashboard_right_now()`
**Widget ID**: `dashboard_right_now`
**Capability**: `edit_posts`

Hiển thị:
- Tổng số bài viết (Published)
- Tổng số trang (Published)
- Tổng số bình luận
- Theme hiện tại + version WordPress

```php
// Bên trong hàm wp_dashboard_right_now():

// Đếm posts theo post type
foreach ( array( 'post', 'page' ) as $post_type ) {
    $num_posts = wp_count_posts( $post_type );
    // $num_posts->publish = số bài published
    // $num_posts->draft = số bài draft
    // $num_posts->pending = số bài pending review
}

// Đếm comments
$num_comm = wp_count_comments();
// $num_comm->approved = số comment đã duyệt
// $num_comm->moderated = số comment chờ duyệt
// $num_comm->spam = số comment spam
// $num_comm->trash = số comment trong thùng rác
// $num_comm->total_comments = tổng tất cả

// Theme hiện tại
$theme = wp_get_theme();
// $theme->get('Name') = tên theme
// $theme->get('Version') = version
```

**Thêm items vào At a Glance**:

```php
// Hook: 'dashboard_glance_items'
add_filter('dashboard_glance_items', function($items) {
    // Đếm sản phẩm (custom post type)
    $count = wp_count_posts('product');
    $text = sprintf('%s Sản phẩm', number_format_i18n($count->publish));
    $items[] = sprintf('<a class="product-count" href="%s">%s</a>',
        admin_url('edit.php?post_type=product'),
        $text
    );

    // Đếm đơn hàng
    $order_count = wp_count_posts('shop_order');
    $items[] = sprintf('<a href="%s">%s Đơn hàng</a>',
        admin_url('edit.php?post_type=shop_order'),
        number_format_i18n($order_count->publish)
    );

    return $items;
});

// Hook: 'rightnow_end' - thêm nội dung vào cuối widget
add_action('rightnow_end', function() {
    echo '<p>Custom info ở cuối At a Glance widget.</p>';
});
```

### 4.3 Activity

**Hàm**: `wp_dashboard_site_activity()`
**Widget ID**: `dashboard_activity`

Hiển thị:
- **Recently Published**: 5 bài viết gần đây nhất
- **Recent Comments**: 5 bình luận gần đây nhất (với nút Approve, Reply, Edit, Spam, Trash)

```php
// Bên trong wp_dashboard_site_activity():
// Gọi 2 hàm con:

// 1. Bài viết gần đây
wp_dashboard_recent_posts(array(
    'post_type'   => 'post',
    'post_status' => 'publish',
    'orderby'     => 'date',
    'order'       => 'DESC',
    'max'         => 5,              // Hiện 5 bài
    'title'       => __('Recently Published'),
));

// 2. Bình luận gần đây
wp_dashboard_recent_comments();
// → Hiện 5 comments mới nhất
// → Mỗi comment có action links: Approve/Unapprove, Reply, Edit, Spam, Trash
```

**Hook cuối Activity widget**:

```php
add_action('activity_box_end', function() {
    echo '<p><a href="' . admin_url('edit.php') . '">Xem tất cả bài viết &rarr;</a></p>';
});
```

### 4.4 Quick Draft

**Hàm**: `wp_dashboard_quick_press()`
**Widget ID**: `dashboard_quick_press`
**Capability**: `create_posts` (của post type 'post')
**Vị trí**: `side`

Hiển thị:
- Form nhanh để tạo bài draft mới (Title + Content + Save Draft)
- Danh sách 3 bài draft gần đây nhất

```php
// Bên trong wp_dashboard_quick_press():

// 1. Tạo auto-draft mới để dùng làm form
$post = get_default_post_to_edit('post', true);
// → post_status = 'auto-draft'

// 2. Render form
?>
<form name="post" action="<?php echo esc_url(admin_url('post.php')); ?>" method="post">
    <?php wp_nonce_field('add-post'); ?>
    <input type="hidden" name="action" value="post-quickdraft-save">
    <input type="hidden" name="post_ID" value="<?php echo $post->ID; ?>">
    <input type="hidden" name="post_type" value="post">

    <div class="input-text-wrap">
        <label for="title">Title</label>
        <input type="text" name="post_title" id="title">
    </div>
    <div class="textarea-wrap">
        <label for="content">Content</label>
        <textarea name="content" id="content" rows="3"></textarea>
    </div>
    <p class="submit">
        <input type="submit" name="save" value="Save Draft" class="button button-primary">
        <br class="clear">
    </p>
</form>
<?php

// 3. Hiện 3 drafts gần đây
$drafts_query = new WP_Query(array(
    'post_type'      => 'post',
    'post_status'    => 'draft',
    'author'         => get_current_user_id(),
    'posts_per_page' => 3,
    'orderby'        => 'modified',
    'order'          => 'DESC',
));
```

**Khi submit Quick Draft**:

Luồng xử lý:
```
Form submit → POST /wp-admin/post.php?action=post-quickdraft-save
  │
  ├── wp-admin/post.php, case 'post-quickdraft-save':
  │     ├── check nonce
  │     ├── check capability
  │     ├── Wrap content trong Paragraph block
  │     ├── edit_post() → wp_update_post()
  │     └── Redirect về dashboard
  │
  └── Bài viết được lưu với post_status = 'draft'
```

### 4.5 WordPress Events and News

**Hàm**: `wp_dashboard_events_news()`
**Widget ID**: `dashboard_primary`
**Vị trí**: `side`

Hiển thị:
- WordPress community events gần vị trí user
- Tin tức từ WordPress.org blog
- Tin tức từ WordPress Planet

```php
// Lấy events từ WordPress.org API
// Endpoint: https://api.wordpress.org/events/1.0/
// Cache: stored as transient 'community-events-{hash}'

// Lấy RSS feeds
// WordPress Blog: https://wordpress.org/news/feed/
// WordPress Planet: https://planet.wordpress.org/feed/
```

### 4.6 Site Health Status

**Hàm**: `wp_dashboard_site_health()`
**Widget ID**: `dashboard_site_health`
**Capability**: `view_site_health_checks`
**Class**: `WP_Site_Health` (`wp-admin/includes/class-wp-site-health.php`)

Hiển thị:
- Trạng thái Site Health (Good, Should be improved, Critical)
- Phần trăm sức khỏe
- Link đến trang Site Health chi tiết

```php
// Dữ liệu Site Health được lưu dưới dạng transient
$health_check_site_status = get_transient('health-check-site-status-result');
// Cấu trúc:
// {
//     "good": 10,     // Số test passed
//     "recommended": 2, // Số test recommended
//     "critical": 0    // Số test critical
// }
```

---

## 5. Tạo Custom Dashboard Widget

### Ví dụ cơ bản

```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'my_custom_widget',       // Widget ID (unique)
        'Thống Kê Cửa Hàng',     // Tiêu đề
        function() {              // Callback render nội dung
            // Đếm sản phẩm
            $products = wp_count_posts('product');
            $orders = wp_count_posts('shop_order');

            echo '<div class="my-dashboard-stats">';
            echo '<ul>';
            echo '<li><strong>Tổng sản phẩm:</strong> ' . intval($products->publish) . '</li>';
            echo '<li><strong>Sản phẩm nháp:</strong> ' . intval($products->draft) . '</li>';
            echo '<li><strong>Đơn hàng mới:</strong> ' . intval($orders->{'wc-pending'} ?? 0) . '</li>';
            echo '<li><strong>Đơn hoàn thành:</strong> ' . intval($orders->{'wc-completed'} ?? 0) . '</li>';
            echo '</ul>';
            echo '</div>';
        }
    );
});
```

### Widget với Control (Configure)

Widget có thể có phần "Configure" cho phép admin tùy chỉnh nội dung:

```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'my_configurable_widget',
        'Thông Báo Tùy Chỉnh',
        // Callback hiển thị nội dung
        function() {
            $options = get_option('my_widget_options', array(
                'message' => 'Chào mừng!',
                'show_date' => true,
            ));

            echo '<p>' . esc_html($options['message']) . '</p>';
            if ($options['show_date']) {
                echo '<p>Hôm nay: ' . wp_date('d/m/Y H:i') . '</p>';
            }
        },
        // Control callback (form cấu hình)
        function() {
            $options = get_option('my_widget_options', array(
                'message' => 'Chào mừng!',
                'show_date' => true,
            ));

            // Lưu khi submit
            if (isset($_POST['my_widget_message'])) {
                $options['message'] = sanitize_text_field($_POST['my_widget_message']);
                $options['show_date'] = isset($_POST['my_widget_show_date']);
                update_option('my_widget_options', $options);
            }

            echo '<p>';
            echo '<label>Thông báo: ';
            echo '<input type="text" name="my_widget_message" value="' . esc_attr($options['message']) . '" class="widefat">';
            echo '</label></p>';
            echo '<p>';
            echo '<label>';
            echo '<input type="checkbox" name="my_widget_show_date" ' . checked($options['show_date'], true, false) . '>';
            echo ' Hiện ngày giờ';
            echo '</label></p>';
        }
    );
});
```

### Widget nâng cao: Chart thống kê

```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'my_stats_chart',
        'Thống Kê Bài Viết 7 Ngày Qua',
        function() {
            global $wpdb;

            // Query số bài viết mỗi ngày trong 7 ngày qua
            $results = $wpdb->get_results(
                "SELECT DATE(post_date) as date, COUNT(*) as count
                 FROM {$wpdb->posts}
                 WHERE post_status = 'publish'
                   AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY DATE(post_date)
                 ORDER BY date ASC"
            );

            echo '<table class="widefat striped">';
            echo '<thead><tr><th>Ngày</th><th>Số bài</th></tr></thead>';
            echo '<tbody>';

            if ($results) {
                foreach ($results as $row) {
                    echo '<tr>';
                    echo '<td>' . esc_html(wp_date('d/m/Y', strtotime($row->date))) . '</td>';
                    echo '<td>' . intval($row->count) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="2">Không có bài viết nào trong 7 ngày qua.</td></tr>';
            }

            echo '</tbody></table>';

            // Tổng số
            $total_posts = wp_count_posts();
            echo '<p class="sub">';
            echo 'Tổng: <strong>' . number_format_i18n($total_posts->publish) . '</strong> bài viết | ';
            echo '<strong>' . number_format_i18n($total_posts->draft) . '</strong> nháp | ';
            echo '<strong>' . number_format_i18n($total_posts->pending) . '</strong> chờ duyệt';
            echo '</p>';
        }
    );
});
```

### Widget đặt ở cột bên (side)

```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'my_side_widget',
        'Ghi Chú Nhanh',
        function() {
            $notes = get_option('my_admin_notes', '');
            echo '<div id="my-notes-display">';
            echo wpautop(esc_html($notes));
            echo '</div>';
            echo '<p><a href="#" id="edit-my-notes" class="button">Sửa ghi chú</a></p>';
        },
        null,    // no control callback
        null,    // no callback args
        'side',  // Đặt ở cột bên phải
        'high'   // Priority cao (hiện lên trên)
    );
});
```

### Widget với AJAX

```php
// PHP: Đăng ký widget và AJAX handler
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'my_ajax_widget',
        'Dữ Liệu Realtime',
        function() {
            echo '<div id="my-ajax-widget-content">';
            echo '<p>Đang tải...</p>';
            echo '</div>';
            echo '<p><button id="refresh-my-widget" class="button">Làm mới</button></p>';
        }
    );
});

// Enqueue script
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'index.php') return; // Chỉ trên Dashboard

    wp_enqueue_script('my-dashboard-widget', plugins_url('js/dashboard-widget.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('my-dashboard-widget', 'myWidgetData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('my_widget_nonce'),
    ));
});

// AJAX handler
add_action('wp_ajax_my_widget_refresh', function() {
    check_ajax_referer('my_widget_nonce', 'nonce');

    $data = array(
        'total_posts' => wp_count_posts()->publish,
        'total_comments' => wp_count_comments()->total_comments,
        'timestamp' => wp_date('H:i:s d/m/Y'),
    );

    wp_send_json_success($data);
});
```

```javascript
// js/dashboard-widget.js
jQuery(document).ready(function($) {
    function loadWidgetData() {
        $.post(myWidgetData.ajaxUrl, {
            action: 'my_widget_refresh',
            nonce: myWidgetData.nonce
        }, function(response) {
            if (response.success) {
                var html = '<ul>';
                html += '<li>Bài viết: ' + response.data.total_posts + '</li>';
                html += '<li>Bình luận: ' + response.data.total_comments + '</li>';
                html += '<li>Cập nhật: ' + response.data.timestamp + '</li>';
                html += '</ul>';
                $('#my-ajax-widget-content').html(html);
            }
        });
    }

    // Load ngay khi trang mở
    loadWidgetData();

    // Refresh khi nhấn nút
    $('#refresh-my-widget').on('click', function() {
        loadWidgetData();
    });
});
```

---

## 6. Xóa/Ẩn Dashboard Widgets

### Xóa default widgets

```php
add_action('wp_dashboard_setup', function() {
    // Xóa Welcome Panel
    remove_action('welcome_panel', 'wp_welcome_panel');

    // Xóa At a Glance
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

    // Xóa Activity
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');

    // Xóa Quick Draft
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');

    // Xóa WordPress Events and News
    remove_meta_box('dashboard_primary', 'dashboard', 'side');

    // Xóa Site Health
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');

    // Xóa Browser Nag
    remove_meta_box('dashboard_browser_nag', 'dashboard', 'normal');

    // Xóa PHP Nag
    remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');
}, 20); // Priority 20 để chạy sau default setup (priority 10)
```

### Xóa widget cho role cụ thể

```php
add_action('wp_dashboard_setup', function() {
    // Chỉ admin mới thấy Site Health
    if (!current_user_can('manage_options')) {
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
    }

    // Editor không cần thấy Quick Draft (họ thường dùng full editor)
    if (current_user_can('edit_others_posts') && !current_user_can('manage_options')) {
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    }
}, 20);
```

### Dashboard hoàn toàn tùy chỉnh

```php
// Xóa hết widgets mặc định và thay bằng custom
add_action('wp_dashboard_setup', function() {
    // Xóa tất cả
    global $wp_meta_boxes;
    $wp_meta_boxes['dashboard'] = array();

    // Xóa Welcome Panel
    remove_action('welcome_panel', 'wp_welcome_panel');

    // Thêm widget riêng
    wp_add_dashboard_widget('my_main_widget', 'Tổng Quan Website', 'my_main_widget_callback');
    wp_add_dashboard_widget('my_quick_links', 'Liên Kết Nhanh', 'my_quick_links_callback', null, null, 'side');
}, 999);

function my_main_widget_callback() {
    $posts = wp_count_posts();
    $pages = wp_count_posts('page');
    $comments = wp_count_comments();
    $users = count_users();

    echo '<div class="activity-block">';
    echo '<h3>Nội dung</h3>';
    echo '<ul>';
    echo '<li><a href="' . admin_url('edit.php') . '">' . $posts->publish . ' Bài viết</a></li>';
    echo '<li><a href="' . admin_url('edit.php?post_type=page') . '">' . $pages->publish . ' Trang</a></li>';
    echo '<li><a href="' . admin_url('edit-comments.php') . '">' . $comments->total_comments . ' Bình luận</a></li>';
    echo '<li><a href="' . admin_url('users.php') . '">' . $users['total_users'] . ' Người dùng</a></li>';
    echo '</ul>';
    echo '</div>';
}

function my_quick_links_callback() {
    echo '<ul>';
    echo '<li><a href="' . admin_url('post-new.php') . '" class="button button-primary" style="width:100%;text-align:center;margin-bottom:8px;">Viết Bài Mới</a></li>';
    echo '<li><a href="' . admin_url('post-new.php?post_type=page') . '" class="button" style="width:100%;text-align:center;margin-bottom:8px;">Tạo Trang Mới</a></li>';
    echo '<li><a href="' . admin_url('media-new.php') . '" class="button" style="width:100%;text-align:center;margin-bottom:8px;">Upload Media</a></li>';
    echo '<li><a href="' . admin_url('options-general.php') . '" class="button" style="width:100%;text-align:center;">Cài Đặt</a></li>';
    echo '</ul>';
}
```

---

## 7. Screen Options

### Giới thiệu

Screen Options nằm ở góc trên bên phải Dashboard, cho phép user tùy chỉnh:
- **Ẩn/hiện widgets** (checkbox cho từng widget)
- **Số cột hiển thị** (1 đến 4 cột)
- **Drag & Drop** sắp xếp vị trí widgets

### Ẩn/Hiện Widgets

Mỗi widget có checkbox trong Screen Options. Khi user bỏ tick, widget bị ẩn (nhưng không bị xóa).

```
Screen Options [▼]
┌──────────────────────────────────────────────┐
│  Show on screen:                              │
│  [x] At a Glance                              │
│  [x] Activity                                 │
│  [ ] Quick Draft          ← Đã ẩn             │
│  [x] WordPress Events and News                │
│  [x] Site Health Status                       │
│  [x] My Custom Widget                         │
│                                               │
│  Screen Layout:                               │
│  Number of columns: [2 ▼]                     │
└──────────────────────────────────────────────┘
```

### Số cột

```php
// Mặc định Dashboard hỗ trợ đến 4 cột
// User có thể chọn 1, 2, 3, hoặc 4 cột

// Giá trị lưu trong wp_usermeta:
// meta_key: screen_layout_dashboard
// meta_value: 1, 2, 3, hoặc 4

// Lấy số cột hiện tại:
$columns = get_user_option('screen_layout_dashboard');
// Mặc định: 2
```

### Drag & Drop

Dashboard widgets hỗ trợ drag & drop để sắp xếp. Thứ tự được lưu vào user meta.

```php
// Thứ tự được lưu qua AJAX call khi user drag & drop
// Action: wp_ajax_meta-box-order
// Lưu vào wp_usermeta:
//   meta_key: meta-box-order_dashboard
//   meta_value: serialized array

// Ví dụ giá trị:
// a:4:{
//   s:6:"normal";s:82:"dashboard_site_health,dashboard_right_now,dashboard_activity,my_custom_widget";
//   s:4:"side";s:40:"dashboard_quick_press,dashboard_primary";
//   s:7:"column3";s:0:"";
//   s:7:"column4";s:0:"";
// }
```

### Widgets đóng/mở (collapse)

User có thể click vào tiêu đề widget để collapse/expand. Trạng thái này cũng được lưu:

```php
// AJAX action: wp_ajax_closed-postboxes
// Lưu vào wp_usermeta:
//   meta_key: closedpostboxes_dashboard
//   meta_value: serialized array các widget ID đang đóng

// Ví dụ:
// a:2:{i:0;s:18:"dashboard_activity";i:1;s:17:"dashboard_primary";}
```

### Hidden widgets (Screen Options)

```php
// Lưu vào wp_usermeta:
//   meta_key: metaboxhidden_dashboard
//   meta_value: serialized array các widget ID đang ẩn

// Ví dụ:
// a:1:{i:0;s:20:"dashboard_quick_press";}
```

---

## 8. Dashboard Hooks

### Hook chính: `wp_dashboard_setup`

**Khi nào**: Sau khi core widgets đã đăng ký, trước khi render.
**Dùng để**: Thêm/xóa dashboard widgets.

```php
add_action('wp_dashboard_setup', function() {
    // Thêm widget
    wp_add_dashboard_widget('my_widget', 'Widget Của Tôi', 'my_callback');

    // Xóa widget
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
});
```

### Hook: `welcome_panel`

**Khi nào**: Render nội dung Welcome Panel.

```php
// Xóa Welcome Panel mặc định
remove_action('welcome_panel', 'wp_welcome_panel');

// Thay bằng custom
add_action('welcome_panel', function() {
    echo '<h2>Welcome Panel Tùy Chỉnh</h2>';
});
```

### Hook: `activity_box_end`

**Khi nào**: Cuối Activity widget.

```php
add_action('activity_box_end', function() {
    echo '<div class="activity-block">';
    echo '<h3>Hoạt Động Plugin</h3>';
    echo '<p>10 đơn hàng mới hôm nay</p>';
    echo '</div>';
});
```

### Hook: `rightnow_end`

**Khi nào**: Cuối At a Glance widget (legacy hook).

```php
add_action('rightnow_end', function() {
    echo '<p>Server: PHP ' . phpversion() . '</p>';
});
```

### Hook: `dashboard_glance_items`

**Khi nào**: Filter items hiển thị trong At a Glance.

```php
add_filter('dashboard_glance_items', function($items) {
    $count = wp_count_posts('product');
    $items[] = '<a href="edit.php?post_type=product">' . $count->publish . ' Sản phẩm</a>';
    return $items;
});
```

### Hook: `wp_dashboard_widgets`

**Khi nào**: Filter danh sách widget IDs sẽ được load.

```php
add_filter('wp_dashboard_widgets', function($widgets) {
    // Xóa widget khỏi danh sách
    $key = array_search('dashboard_primary', $widgets);
    if ($key !== false) {
        unset($widgets[$key]);
    }
    return $widgets;
});
```

### Hook: `admin_footer-index.php`

**Khi nào**: Footer chỉ trên trang Dashboard (index.php).

```php
add_action('admin_footer-index.php', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // JS chỉ chạy trên Dashboard
        console.log('Dashboard loaded');
    });
    </script>
    <?php
});
```

### Bảng tổng hợp Dashboard Hooks

```
wp_dashboard_setup
│  Thêm/xóa widgets
│
welcome_panel
│  Render Welcome Panel
│
dashboard_glance_items (filter)
│  Thêm items vào At a Glance
│
rightnow_end (action)
│  Cuối At a Glance
│
activity_box_end (action)
│  Cuối Activity widget
│
wp_dashboard_widgets (filter)
│  Filter danh sách widgets
│
admin_footer-index.php (action)
│  JS/HTML cuối trang Dashboard
```

---

## 9. DB: Dashboard Lưu Gì?

### Bảng `wp_usermeta` - User preferences

| meta_key | Ý nghĩa | Giá trị mẫu |
|----------|----------|-------------|
| `show_welcome_panel` | Hiện/ẩn Welcome Panel | `0` (ẩn), `1` (hiện) |
| `meta-box-order_dashboard` | Thứ tự widgets (drag & drop) | Serialized array |
| `closedpostboxes_dashboard` | Widgets đang collapse | Serialized array widget IDs |
| `metaboxhidden_dashboard` | Widgets đang ẩn (Screen Options) | Serialized array widget IDs |
| `screen_layout_dashboard` | Số cột hiển thị | `1`, `2`, `3`, hoặc `4` |

```php
// Ví dụ đọc giá trị từ DB:
$user_id = get_current_user_id();

// Thứ tự widgets
$order = get_user_meta($user_id, 'meta-box-order_dashboard', true);
// Result: array(
//   'normal' => 'dashboard_right_now,dashboard_activity',
//   'side'   => 'dashboard_quick_press,dashboard_primary',
//   'column3' => '',
//   'column4' => '',
// )

// Widgets đang ẩn
$hidden = get_user_meta($user_id, 'metaboxhidden_dashboard', true);
// Result: array('dashboard_quick_press', 'dashboard_primary')

// Welcome panel
$show_welcome = get_user_meta($user_id, 'show_welcome_panel', true);
// Result: '1' hoặc '0'

// Số cột
$columns = get_user_meta($user_id, 'screen_layout_dashboard', true);
// Result: '2'
```

### Bảng `wp_posts` - Quick Draft

Quick Draft tạo bài viết với status `auto-draft` hoặc `draft`:

```sql
-- Auto-draft (trước khi user nhập nội dung)
SELECT * FROM wp_posts
WHERE post_status = 'auto-draft'
  AND post_type = 'post';

-- Draft (sau khi user bấm Save Draft)
SELECT * FROM wp_posts
WHERE post_status = 'draft'
  AND post_type = 'post'
  AND post_author = {user_id}
ORDER BY post_modified DESC
LIMIT 3;
```

### Bảng `wp_options` - Widget settings

```sql
-- Custom widget options
SELECT * FROM wp_options WHERE option_name = 'my_widget_options';
-- Result: serialized array of widget settings
```

### Bảng `wp_options` - Transients (cached data)

```sql
-- Site Health cache
SELECT * FROM wp_options
WHERE option_name = '_transient_health-check-site-status-result';

-- Community events cache
SELECT * FROM wp_options
WHERE option_name LIKE '_transient_community-events%';

-- Dashboard feed cache (RSS)
SELECT * FROM wp_options
WHERE option_name LIKE '_transient_dash_%';
```

---

## 10. So Sánh Với Laravel

### Dashboard Page

```php
// Laravel Filament - Dashboard
class Dashboard extends BaseDashboard
{
    protected function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RecentOrders::class,
            LatestPosts::class,
        ];
    }
}

// WordPress - Dashboard tự động có sẵn
// Chỉ cần hook vào wp_dashboard_setup để tùy chỉnh
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('my_stats', 'Stats', 'my_stats_callback');
});
```

### Dashboard Widget vs Filament Widget

```php
// Laravel Filament Widget
class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Posts', Post::count()),
            Card::make('Total Users', User::count()),
            Card::make('Total Orders', Order::count()),
        ];
    }
}

// WordPress Dashboard Widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('stats_overview', 'Thống Kê', function() {
        $posts = wp_count_posts()->publish;
        $users = count_users()['total_users'];

        echo "<p>Bài viết: {$posts}</p>";
        echo "<p>Người dùng: {$users}</p>";
    });
});
```

### User Preferences

```php
// Laravel - User preferences
// Cần tự tạo bảng hoặc dùng JSON column
$user->preferences()->set('dashboard_layout', '2-columns');
$user->preferences()->set('hidden_widgets', ['recent-orders']);

// WordPress - Built-in user meta
update_user_meta($user_id, 'screen_layout_dashboard', 2);
update_user_meta($user_id, 'metaboxhidden_dashboard', ['dashboard_primary']);
// WordPress tự động handle drag & drop, collapse, show/hide
```

### Tóm tắt điểm khác biệt

| Tính năng | Laravel | WordPress |
|-----------|---------|-----------|
| Dashboard | Phải code hoặc dùng package | Built-in sẵn |
| Widgets | Filament/Nova Widget classes | `wp_add_dashboard_widget()` |
| Drag & Drop | Cần thêm JS library | Built-in (jQuery UI Sortable) |
| User Preferences | Tự implement | Built-in (user meta) |
| Screen Options | Không có | Built-in |
| Help Tabs | Không có | Built-in |
| Widget Configure | Form builder | Control callback |
| Data caching | Cache facade | Transients API |

---

## Tổng Kết

Dashboard WordPress là hệ thống hoàn chỉnh với:

1. **6 default widgets** được đăng ký tự động
2. **`wp_add_dashboard_widget()`** để thêm widget tùy chỉnh
3. **Meta box system** (dashboard widgets = meta boxes)
4. **Screen Options** cho user tùy chỉnh layout
5. **Drag & Drop** sắp xếp widgets
6. **User meta** lưu preferences cho từng user
7. **Hooks** linh hoạt: `wp_dashboard_setup`, `welcome_panel`, `dashboard_glance_items`, v.v.

Tất cả preferences (thứ tự, ẩn/hiện, collapse, số cột) được lưu vào bảng `wp_usermeta`, riêng cho từng user.

---

*Trước đó: [01-tong-quan-admin.md](./01-tong-quan-admin.md) - Tổng quan WP Admin*
*Tiếp theo: [03-quan-ly-bai-viet.md](./03-quan-ly-bai-viet.md) - Quản lý Bài viết, Pages, Editor*
