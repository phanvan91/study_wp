# WP-Cron & Background Jobs - Hướng Dẫn Chi Tiết

## Mục lục

1. [Tổng quan WP-Cron](#1-tong-quan-wp-cron)
2. [WP-Cron cơ bản](#2-wp-cron-co-ban)
3. [Custom Cron Intervals](#3-custom-cron-intervals)
4. [Cron trong Plugin - Lifecycle đầy đủ](#4-cron-trong-plugin---lifecycle-day-du)
5. [System Cron thay thế WP-Cron](#5-system-cron-thay-the-wp-cron)
6. [Action Scheduler - Background Jobs chuyên nghiệp](#6-action-scheduler---background-jobs-chuyen-nghiep)
7. [Batch Processing - Xử lý dữ liệu lớn](#7-batch-processing---xu-ly-du-lieu-lon)
8. [Ví dụ thực tế: Email Digest Plugin](#8-vi-du-thuc-te-email-digest-plugin)
9. [Ví dụ thực tế: Data Sync với API bên ngoài](#9-vi-du-thuc-te-data-sync-voi-api-ben-ngoai)
10. [Ví dụ thực tế: Cleanup & Maintenance](#10-vi-du-thuc-te-cleanup--maintenance)
11. [WP-CLI quản lý Cron](#11-wp-cli-quan-ly-cron)
12. [Debugging & Monitoring](#12-debugging--monitoring)
13. [So sánh WP-Cron vs Action Scheduler vs Laravel](#13-so-sanh-wp-cron-vs-action-scheduler-vs-laravel)

---

## 1. Tổng quan WP-Cron

### WP-Cron là gì?

```
WP-Cron KHÔNG phải là real cron job!

System Cron (Linux):
  - Chạy đúng giờ, đúng phút bởi OS
  - Độc lập với web traffic
  - Đáng tin cậy 100%

WP-Cron (WordPress):
  - Chỉ chạy KHI có visitor truy cập website
  - Kiểm tra "có event nào quá hạn?" → nếu có thì chạy
  - Website ít traffic → cron có thể bị trễ hàng giờ
  - Website nhiều traffic → cron chạy đúng giờ hơn
```

### Cơ chế hoạt động

```
Visitor truy cập website
       ↓
WordPress load (wp-settings.php)
       ↓
wp-cron.php kiểm tra: "Có event nào cần chạy không?"
       ↓
Nếu CÓ → Spawn một request riêng (non-blocking) đến wp-cron.php
       ↓
wp-cron.php thực thi các callbacks đã đăng ký
       ↓
Cập nhật thời gian chạy tiếp theo
```

### So sánh nhanh với Laravel

```
Laravel Scheduler:
  * * * * * cd /path-to-project && php artisan schedule:run
  → Chạy mỗi phút bởi OS cron
  → $schedule->command('report:generate')->daily();

WordPress WP-Cron:
  → Chạy khi có visitor (pseudo-cron)
  → wp_schedule_event(time(), 'daily', 'my_daily_hook');
```

---

## 2. WP-Cron cơ bản

### 2.1. Đăng ký Recurring Event

```php
<?php
/**
 * Đăng ký một event chạy định kỳ.
 *
 * wp_schedule_event( $timestamp, $recurrence, $hook, $args, $wp_error )
 *   - $timestamp: Unix timestamp lần chạy đầu tiên
 *   - $recurrence: 'hourly' | 'twicedaily' | 'daily' | 'weekly' (WP 5.4+)
 *   - $hook: Tên action hook sẽ được fire
 *   - $args: Array arguments truyền vào callback
 *   - $wp_error: true để return WP_Error thay vì false (WP 5.1+)
 */

// Đăng ký event - chỉ nên gọi 1 lần (activation hook hoặc kiểm tra wp_next_scheduled)
function my_plugin_schedule_events() {
    // QUAN TRỌNG: Kiểm tra đã có chưa để tránh đăng ký trùng
    if ( ! wp_next_scheduled( 'my_plugin_hourly_cleanup' ) ) {
        wp_schedule_event( time(), 'hourly', 'my_plugin_hourly_cleanup' );
    }

    if ( ! wp_next_scheduled( 'my_plugin_daily_report' ) ) {
        // Lần đầu chạy lúc 2:00 AM ngày mai
        $tomorrow_2am = strtotime( 'tomorrow 2:00am' );
        wp_schedule_event( $tomorrow_2am, 'daily', 'my_plugin_daily_report' );
    }
}
add_action( 'init', 'my_plugin_schedule_events' );

// Callback xử lý khi event fire
function my_plugin_do_hourly_cleanup() {
    // Xóa transients hết hạn
    global $wpdb;
    $wpdb->query(
        "DELETE a, b FROM {$wpdb->options} a
         INNER JOIN {$wpdb->options} b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
         WHERE a.option_name LIKE '_transient_%'
         AND b.option_value < UNIX_TIMESTAMP()"
    );

    error_log( 'my_plugin: Hourly cleanup completed at ' . current_time( 'mysql' ) );
}
add_action( 'my_plugin_hourly_cleanup', 'my_plugin_do_hourly_cleanup' );

function my_plugin_do_daily_report() {
    $stats = array(
        'total_posts'    => wp_count_posts()->publish,
        'total_comments' => wp_count_comments()->approved,
        'total_users'    => count_users()['total_users'],
    );

    $admin_email = get_option( 'admin_email' );
    $subject     = sprintf( '[%s] Báo cáo hàng ngày', get_bloginfo( 'name' ) );
    $message     = sprintf(
        "Thống kê ngày %s:\n- Bài viết: %d\n- Bình luận: %d\n- Thành viên: %d",
        current_time( 'd/m/Y' ),
        $stats['total_posts'],
        $stats['total_comments'],
        $stats['total_users']
    );

    wp_mail( $admin_email, $subject, $message );
}
add_action( 'my_plugin_daily_report', 'my_plugin_do_daily_report' );
```

### 2.2. Đăng ký Single Event (chạy 1 lần)

```php
<?php
/**
 * Single event: chạy 1 lần rồi tự xóa.
 * Hữu ích cho: gửi email delay, xử lý sau khi save, scheduled publish...
 */

// Gửi email follow-up sau 24 giờ khi user đăng ký
function my_send_welcome_followup( $user_id ) {
    // Schedule gửi email sau 24 giờ
    wp_schedule_single_event(
        time() + DAY_IN_SECONDS,       // 86400 giây = 24 giờ
        'my_plugin_send_followup',
        array( $user_id )              // Args truyền vào callback
    );
}
add_action( 'user_register', 'my_send_welcome_followup' );

// Callback nhận $user_id từ args
function my_plugin_do_send_followup( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    wp_mail(
        $user->user_email,
        'Bạn ơi, quay lại nhé!',
        sprintf( 'Chào %s, bạn đã khám phá hết các tính năng chưa?', $user->display_name )
    );
}
add_action( 'my_plugin_send_followup', 'my_plugin_do_send_followup' );

// Với WP 5.1+: trả về WP_Error nếu thất bại
$result = wp_schedule_single_event(
    time() + HOUR_IN_SECONDS,
    'my_plugin_delayed_task',
    array( 'data' => 'value' ),
    true  // $wp_error = true
);

if ( is_wp_error( $result ) ) {
    error_log( 'Cron schedule failed: ' . $result->get_error_message() );
}
```

### 2.3. Hủy Event

```php
<?php
/**
 * Các hàm hủy cron event.
 */

// Hủy lần chạy tiếp theo của recurring event
$timestamp = wp_next_scheduled( 'my_plugin_hourly_cleanup' );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'my_plugin_hourly_cleanup' );
}

// Hủy TẤT CẢ instances của một hook (dùng khi deactivation)
wp_clear_scheduled_hook( 'my_plugin_hourly_cleanup' );

// Hủy event có args cụ thể
wp_clear_scheduled_hook( 'my_plugin_send_followup', array( $user_id ) );

// WordPress 5.1+: Hủy tất cả events của tất cả hooks (dùng khi uninstall)
wp_unschedule_hook( 'my_plugin_hourly_cleanup' );
wp_unschedule_hook( 'my_plugin_daily_report' );
wp_unschedule_hook( 'my_plugin_send_followup' );
```

### 2.4. Kiểm tra Event

```php
<?php
// Kiểm tra event tiếp theo
$next = wp_next_scheduled( 'my_plugin_daily_report' );
if ( $next ) {
    echo 'Lần chạy tiếp: ' . date( 'Y-m-d H:i:s', $next );
} else {
    echo 'Chưa có event nào được schedule';
}

// Lấy tất cả cron events (debug)
$crons = _get_cron_array();
foreach ( $crons as $timestamp => $hooks ) {
    foreach ( $hooks as $hook => $events ) {
        echo sprintf(
            "Hook: %s | Thời gian: %s\n",
            $hook,
            date( 'Y-m-d H:i:s', $timestamp )
        );
    }
}

// WordPress 5.1+: Lấy danh sách schedules
$schedules = wp_get_schedules();
/*
Array(
    'hourly'     => array( 'interval' => 3600,  'display' => 'Once Hourly' ),
    'twicedaily' => array( 'interval' => 43200, 'display' => 'Twice Daily' ),
    'daily'      => array( 'interval' => 86400, 'display' => 'Once Daily' ),
    'weekly'     => array( 'interval' => 604800,'display' => 'Once Weekly' ),
)
*/
```

---

## 3. Custom Cron Intervals

```php
<?php
/**
 * WordPress chỉ có 4 intervals mặc định: hourly, twicedaily, daily, weekly.
 * Dùng filter 'cron_schedules' để thêm interval tùy chỉnh.
 */

function my_plugin_add_cron_intervals( $schedules ) {
    // Mỗi 5 phút
    $schedules['every_five_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,  // 300 giây
        'display'  => __( 'Mỗi 5 phút', 'my-plugin' ),
    );

    // Mỗi 15 phút
    $schedules['every_fifteen_minutes'] = array(
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => __( 'Mỗi 15 phút', 'my-plugin' ),
    );

    // Mỗi 30 phút
    $schedules['every_thirty_minutes'] = array(
        'interval' => 30 * MINUTE_IN_SECONDS,
        'display'  => __( 'Mỗi 30 phút', 'my-plugin' ),
    );

    // Hai lần mỗi tuần (thứ 2 và thứ 5)
    $schedules['twice_weekly'] = array(
        'interval' => 3.5 * DAY_IN_SECONDS,
        'display'  => __( 'Hai lần mỗi tuần', 'my-plugin' ),
    );

    // Hàng tháng (xấp xỉ 30 ngày)
    $schedules['monthly'] = array(
        'interval' => 30 * DAY_IN_SECONDS,
        'display'  => __( 'Hàng tháng', 'my-plugin' ),
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'my_plugin_add_cron_intervals' );

// Sử dụng custom interval
if ( ! wp_next_scheduled( 'my_plugin_sync_data' ) ) {
    wp_schedule_event( time(), 'every_fifteen_minutes', 'my_plugin_sync_data' );
}
```

### WordPress Time Constants

```php
<?php
/**
 * Constants có sẵn trong WordPress (wp-includes/default-constants.php):
 */
MINUTE_IN_SECONDS; // 60
HOUR_IN_SECONDS;   // 3600
DAY_IN_SECONDS;    // 86400
WEEK_IN_SECONDS;   // 604800
MONTH_IN_SECONDS;  // 2592000 (30 ngày)
YEAR_IN_SECONDS;   // 31536000 (365 ngày)
```

---

## 4. Cron trong Plugin - Lifecycle đầy đủ

```php
<?php
/**
 * Plugin Name:  My Cron Plugin
 * Description:  Ví dụ lifecycle đầy đủ cho WP-Cron trong plugin.
 * Version:      1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Cron_Plugin {

    private const HOURLY_HOOK  = 'my_cron_plugin_hourly';
    private const DAILY_HOOK   = 'my_cron_plugin_daily';
    private const WEEKLY_HOOK  = 'my_cron_plugin_weekly';

    /**
     * Boot plugin - gọi từ file chính.
     */
    public static function init(): void {
        // Đăng ký custom intervals
        add_filter( 'cron_schedules', array( self::class, 'add_intervals' ) );

        // Đăng ký callbacks
        add_action( self::HOURLY_HOOK,  array( self::class, 'run_hourly' ) );
        add_action( self::DAILY_HOOK,   array( self::class, 'run_daily' ) );
        add_action( self::WEEKLY_HOOK,  array( self::class, 'run_weekly' ) );
    }

    /**
     * ACTIVATION: Đăng ký tất cả cron events.
     * Gọi từ register_activation_hook().
     */
    public static function activate(): void {
        // Phải add filter TRƯỚC khi schedule (vì custom interval chưa tồn tại)
        add_filter( 'cron_schedules', array( self::class, 'add_intervals' ) );

        if ( ! wp_next_scheduled( self::HOURLY_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', self::HOURLY_HOOK );
        }

        if ( ! wp_next_scheduled( self::DAILY_HOOK ) ) {
            wp_schedule_event(
                strtotime( 'tomorrow 3:00am' ),
                'daily',
                self::DAILY_HOOK
            );
        }

        if ( ! wp_next_scheduled( self::WEEKLY_HOOK ) ) {
            wp_schedule_event(
                strtotime( 'next monday 4:00am' ),
                'weekly',
                self::WEEKLY_HOOK
            );
        }
    }

    /**
     * DEACTIVATION: Hủy tất cả cron events.
     * QUAN TRỌNG: Luôn cleanup khi deactivate!
     */
    public static function deactivate(): void {
        wp_clear_scheduled_hook( self::HOURLY_HOOK );
        wp_clear_scheduled_hook( self::DAILY_HOOK );
        wp_clear_scheduled_hook( self::WEEKLY_HOOK );
    }

    /**
     * UNINSTALL: Cleanup toàn bộ (gọi từ uninstall.php).
     */
    public static function uninstall(): void {
        // Hủy cron
        wp_unschedule_hook( self::HOURLY_HOOK );
        wp_unschedule_hook( self::DAILY_HOOK );
        wp_unschedule_hook( self::WEEKLY_HOOK );

        // Xóa options
        delete_option( 'my_cron_plugin_settings' );
        delete_option( 'my_cron_plugin_last_run' );
    }

    public static function add_intervals( array $schedules ): array {
        $schedules['every_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => 'Mỗi 5 phút',
        );
        return $schedules;
    }

    public static function run_hourly(): void {
        update_option( 'my_cron_plugin_last_run', array(
            'hook' => self::HOURLY_HOOK,
            'time' => current_time( 'mysql' ),
        ) );
        // Xử lý logic...
    }

    public static function run_daily(): void {
        // Gửi báo cáo, cleanup database...
    }

    public static function run_weekly(): void {
        // Tạo backup, thống kê tuần...
    }
}

// Boot
My_Cron_Plugin::init();

// Lifecycle hooks - PHẢI dùng file chính của plugin
register_activation_hook( __FILE__, array( 'My_Cron_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'My_Cron_Plugin', 'deactivate' ) );
```

---

## 5. System Cron thay thế WP-Cron

### 5.1. Tắt WP-Cron mặc định

```php
<?php
/**
 * File: wp-config.php
 *
 * Tắt WP-Cron chạy theo visitor.
 * Thay bằng system cron chạy đúng giờ.
 */
define( 'DISABLE_WP_CRON', true );

/**
 * Tùy chọn: Thay đổi cron lock timeout (mặc định 60 giây).
 * Ngăn nhiều cron processes chạy đồng thời.
 */
define( 'WP_CRON_LOCK_TIMEOUT', 120 );
```

### 5.2. Cài đặt System Cron

```bash
# Mở crontab editor
crontab -e

# Chạy WP-Cron mỗi phút (khuyến nghị cho production)
* * * * * cd /var/www/html && php wp-cron.php > /dev/null 2>&1

# Hoặc dùng wget (không cần cd vào thư mục)
* * * * * wget -q -O - https://example.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1

# Hoặc dùng curl
* * * * * curl -s https://example.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1

# Dùng WP-CLI (tốt nhất - chạy đúng PHP version, không cần HTTP)
* * * * * cd /var/www/html && wp cron event run --due-now > /dev/null 2>&1

# Mỗi 5 phút (tiết kiệm resources hơn)
*/5 * * * * cd /var/www/html && wp cron event run --due-now > /dev/null 2>&1

# Với logging
*/5 * * * * cd /var/www/html && wp cron event run --due-now >> /var/log/wp-cron.log 2>&1
```

### 5.3. System Cron cho Multisite

```bash
# Chạy cron cho tất cả sites trong network
* * * * * cd /var/www/html && wp site list --field=url | xargs -I {} wp cron event run --due-now --url={}

# Hoặc chạy từng site cụ thể
* * * * * cd /var/www/html && wp cron event run --due-now --url=https://site1.example.com
* * * * * cd /var/www/html && wp cron event run --due-now --url=https://site2.example.com
```

---

## 6. Action Scheduler - Background Jobs chuyên nghiệp

### 6.1. Giới thiệu Action Scheduler

```
Action Scheduler là gì?
  - Thư viện PHP do Automattic phát triển
  - Dùng bởi WooCommerce, WooCommerce Subscriptions, Action Scheduler
  - Job queue đáng tin cậy cho WordPress
  - Hỗ trợ retry, logging, monitoring
  - Chạy được qua WP-Cron, WP-CLI, hoặc system cron

Tại sao dùng Action Scheduler thay WP-Cron?
  - WP-Cron: Chỉ lưu trong wp_options (1 row), không scale
  - Action Scheduler: Có bảng riêng, hỗ trợ hàng triệu jobs
  - Action Scheduler: Retry khi fail, logging, admin UI
  - Action Scheduler: Xử lý concurrent jobs an toàn
```

### 6.2. Cài đặt

```bash
# Cách 1: Composer (khuyến nghị cho plugin)
composer require woocommerce/action-scheduler

# Cách 2: Include trực tiếp (copy thư mục action-scheduler vào plugin)
# Download từ: https://github.com/woocommerce/action-scheduler
```

```php
<?php
/**
 * Load Action Scheduler trong plugin.
 * File: my-plugin.php
 */

// Cách 1: Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Cách 2: Include trực tiếp
require_once __DIR__ . '/libraries/action-scheduler/action-scheduler.php';
```

### 6.3. API cơ bản

```php
<?php
/**
 * Action Scheduler API Functions.
 * Tất cả functions có prefix as_ (action scheduler).
 */

// ── SCHEDULE ACTIONS ──────────────────────────────────────────────

// Schedule 1 lần (tương đương wp_schedule_single_event)
as_schedule_single_action(
    time() + HOUR_IN_SECONDS,           // Khi nào chạy
    'my_plugin_process_order',           // Hook name
    array( 'order_id' => 123 ),          // Args
    'my-plugin'                          // Group (để quản lý)
);

// Schedule recurring (tương đương wp_schedule_event)
as_schedule_recurring_action(
    time(),                              // Lần đầu chạy
    HOUR_IN_SECONDS,                     // Interval (giây)
    'my_plugin_sync_inventory',          // Hook name
    array(),                             // Args
    'my-plugin'                          // Group
);

// Schedule theo Cron expression (UNIX cron syntax)
as_schedule_cron_action(
    time(),                              // Lần đầu chạy
    '0 2 * * *',                         // Mỗi ngày lúc 2:00 AM
    'my_plugin_nightly_cleanup',
    array(),
    'my-plugin'
);

// ── KIỂM TRA ────────────────────────────────────────────────────

// Kiểm tra đã có action chưa (tránh đăng ký trùng)
if ( ! as_has_scheduled_action( 'my_plugin_sync_inventory', array(), 'my-plugin' ) ) {
    as_schedule_recurring_action( time(), HOUR_IN_SECONDS, 'my_plugin_sync_inventory', array(), 'my-plugin' );
}

// Lấy timestamp lần chạy tiếp
$next = as_next_scheduled_action( 'my_plugin_sync_inventory' );

// ── HỦY ACTIONS ────────────────────────────────────────────────

// Hủy lần chạy tiếp theo
as_unschedule_action( 'my_plugin_sync_inventory', array(), 'my-plugin' );

// Hủy TẤT CẢ actions trong group
as_unschedule_all_actions( 'my_plugin_sync_inventory', array(), 'my-plugin' );

// ── CALLBACKS ──────────────────────────────────────────────────

// Callback giống hệt WP hooks
add_action( 'my_plugin_process_order', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    // Xử lý order...
    error_log( "Processed order #{$order_id}" );
} );

add_action( 'my_plugin_sync_inventory', function() {
    // Sync inventory từ API bên ngoài...
} );
```

### 6.4. Async Actions (chạy ngay lập tức, non-blocking)

```php
<?php
/**
 * Async action: Đưa vào queue và xử lý ngay khi có thể.
 * Không block request hiện tại.
 *
 * Ví dụ: User upload file → xử lý ảnh background
 */

// Khi user upload
function my_handle_upload( $attachment_id ) {
    // Trả response cho user ngay lập tức
    // Xử lý nặng chạy background
    as_enqueue_async_action(
        'my_plugin_process_image',
        array( 'attachment_id' => $attachment_id ),
        'my-plugin'
    );
}
add_action( 'add_attachment', 'my_handle_upload' );

// Callback chạy background
add_action( 'my_plugin_process_image', function( $attachment_id ) {
    $file = get_attached_file( $attachment_id );

    // Tạo thêm image sizes, optimize, watermark...
    // Xử lý nặng không ảnh hưởng UX

    // Cập nhật meta khi xong
    update_post_meta( $attachment_id, '_processed', true );
} );
```

### 6.5. Retry và Error Handling

```php
<?php
/**
 * Action Scheduler tự động retry khi callback throw Exception.
 *
 * Retry schedule mặc định:
 *   - Lần 1: sau 5 phút
 *   - Lần 2: sau 30 phút
 *   - Lần 3: sau 2 giờ
 *   - Lần 4: sau 12 giờ
 *   - Lần 5: sau 24 giờ
 *   → Sau 5 lần fail → status = "failed"
 */

add_action( 'my_plugin_call_external_api', function( $endpoint, $data ) {
    $response = wp_remote_post( $endpoint, array(
        'body'    => wp_json_encode( $data ),
        'headers' => array( 'Content-Type' => 'application/json' ),
        'timeout' => 30,
    ) );

    // Throw exception → Action Scheduler sẽ retry
    if ( is_wp_error( $response ) ) {
        throw new Exception( 'API call failed: ' . $response->get_error_message() );
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code >= 500 ) {
        throw new Exception( "API returned {$code} - server error, will retry" );
    }

    if ( $code >= 400 ) {
        // 4xx errors: KHÔNG retry (lỗi client, retry cũng không fix được)
        error_log( "API returned {$code} - client error, skipping retry" );
        return;
    }

    // Success - xử lý response
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    // ...
}, 10, 2 );

// Hook vào failed action để notification
add_action( 'action_scheduler_failed_action', function( $action_id ) {
    $store  = ActionScheduler_Store::instance();
    $action = $store->fetch_action( $action_id );

    $hook = $action->get_hook();
    $args = $action->get_args();

    // Gửi email thông báo admin
    wp_mail(
        get_option( 'admin_email' ),
        "[Alert] Background job failed: {$hook}",
        sprintf(
            "Action ID: %d\nHook: %s\nArgs: %s\nTime: %s",
            $action_id,
            $hook,
            wp_json_encode( $args ),
            current_time( 'mysql' )
        )
    );
} );
```

### 6.6. Admin UI

```php
<?php
/**
 * Action Scheduler tự tạo admin page tại:
 * Tools → Scheduled Actions (tools.php?page=action-scheduler)
 *
 * UI hiển thị:
 *   - Pending: Actions đang chờ chạy
 *   - In-progress: Đang chạy
 *   - Complete: Đã hoàn thành
 *   - Failed: Lỗi (có thể retry manual)
 *   - Canceled: Đã hủy
 *
 * Mỗi action hiển thị: Hook, Status, Group, Args, Schedule, Log
 */

// Thay đổi số ngày giữ lại log (mặc định 30 ngày)
add_filter( 'action_scheduler_retention_period', function() {
    return 7 * DAY_IN_SECONDS; // Giữ 7 ngày
} );

// Thay đổi số actions xử lý mỗi batch (mặc định 25)
add_filter( 'action_scheduler_queue_runner_batch_size', function() {
    return 50; // Tăng lên 50 nếu server mạnh
} );

// Thay đổi thời gian tối đa mỗi batch (mặc định 30 giây)
add_filter( 'action_scheduler_queue_runner_time_limit', function() {
    return 60; // 60 giây
} );
```

---

## 7. Batch Processing - Xử lý dữ liệu lớn

### 7.1. Pattern: Self-Scheduling Batch với WP-Cron

```php
<?php
/**
 * Xử lý 10,000 users → không thể làm 1 lần (timeout).
 * Pattern: Mỗi batch xử lý N items, rồi schedule batch tiếp theo.
 */

class My_Batch_Processor {

    private const BATCH_SIZE = 50;     // Xử lý 50 items/batch
    private const HOOK       = 'my_plugin_batch_process';
    private const OPTION_KEY = 'my_plugin_batch_offset';

    public static function init(): void {
        add_action( self::HOOK, array( self::class, 'process_batch' ) );
    }

    /**
     * Bắt đầu batch processing.
     */
    public static function start(): void {
        // Reset offset
        update_option( self::OPTION_KEY, 0 );

        // Schedule batch đầu tiên chạy ngay
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_single_event( time(), self::HOOK );
        }
    }

    /**
     * Xử lý 1 batch.
     */
    public static function process_batch(): void {
        $offset = (int) get_option( self::OPTION_KEY, 0 );

        // Lấy batch items
        $users = get_users( array(
            'number' => self::BATCH_SIZE,
            'offset' => $offset,
            'fields' => 'ID',
        ) );

        if ( empty( $users ) ) {
            // Hết items → hoàn thành
            delete_option( self::OPTION_KEY );
            do_action( 'my_plugin_batch_complete' );
            error_log( 'Batch processing completed!' );
            return;
        }

        // Xử lý từng user trong batch
        foreach ( $users as $user_id ) {
            self::process_single_user( $user_id );
        }

        // Cập nhật offset
        $new_offset = $offset + self::BATCH_SIZE;
        update_option( self::OPTION_KEY, $new_offset );

        // Schedule batch tiếp theo (delay 5 giây để server nghỉ)
        wp_schedule_single_event( time() + 5, self::HOOK );

        error_log( sprintf(
            'Batch processed: offset %d → %d (%d users)',
            $offset,
            $new_offset,
            count( $users )
        ) );
    }

    private static function process_single_user( int $user_id ): void {
        // Logic xử lý...
        update_user_meta( $user_id, '_migration_v2', true );
    }
}

My_Batch_Processor::init();
```

### 7.2. Pattern: Batch với Action Scheduler (tốt hơn)

```php
<?php
/**
 * Action Scheduler: Mỗi item là 1 action riêng biệt.
 * Ưu điểm: Retry từng item, không mất progress khi fail.
 */

class My_AS_Batch_Processor {

    private const GROUP     = 'my-plugin-migration';
    private const INIT_HOOK = 'my_plugin_init_batch';
    private const ITEM_HOOK = 'my_plugin_process_item';

    public static function init(): void {
        add_action( self::INIT_HOOK, array( self::class, 'schedule_items' ) );
        add_action( self::ITEM_HOOK, array( self::class, 'process_item' ) );
    }

    /**
     * Bắt đầu: Schedule tất cả items vào queue.
     */
    public static function start(): void {
        // Schedule action khởi tạo
        as_enqueue_async_action( self::INIT_HOOK, array(), self::GROUP );
    }

    /**
     * Tạo 1 action cho mỗi item cần xử lý.
     */
    public static function schedule_items(): void {
        global $wpdb;

        // Lấy tất cả IDs cần xử lý
        $ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'product'
             AND post_status = 'publish'"
        );

        $total = count( $ids );
        error_log( "Scheduling {$total} items for batch processing" );

        foreach ( $ids as $id ) {
            as_schedule_single_action(
                time(),
                self::ITEM_HOOK,
                array( 'product_id' => (int) $id ),
                self::GROUP
            );
        }
    }

    /**
     * Xử lý 1 item.
     * Action Scheduler tự quản lý concurrency và retry.
     */
    public static function process_item( int $product_id ): void {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return; // Skip, không throw (không cần retry)
        }

        // Xử lý nặng...
        $product->update_meta_data( '_migrated_v2', true );
        $product->save();
    }

    /**
     * Kiểm tra progress.
     */
    public static function get_progress(): array {
        $store = ActionScheduler_Store::instance();

        return array(
            'pending'   => $store->query_actions( array(
                'hook'   => self::ITEM_HOOK,
                'status' => ActionScheduler_Store::STATUS_PENDING,
                'group'  => self::GROUP,
            ), 'count' ),
            'complete'  => $store->query_actions( array(
                'hook'   => self::ITEM_HOOK,
                'status' => ActionScheduler_Store::STATUS_COMPLETE,
                'group'  => self::GROUP,
            ), 'count' ),
            'failed'    => $store->query_actions( array(
                'hook'   => self::ITEM_HOOK,
                'status' => ActionScheduler_Store::STATUS_FAILED,
                'group'  => self::GROUP,
            ), 'count' ),
        );
    }
}

My_AS_Batch_Processor::init();
```

---

## 8. Ví dụ thực tế: Email Digest Plugin

```php
<?php
/**
 * Plugin gửi email tổng hợp bài viết mới hàng tuần.
 */

class Weekly_Email_Digest {

    private const HOOK       = 'weekly_digest_send';
    private const OPTION_KEY = 'weekly_digest_last_sent';

    public static function register(): void {
        add_action( self::HOOK, array( self::class, 'send_digest' ) );
    }

    public static function activate(): void {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            // Chạy mỗi thứ 2 lúc 8:00 AM
            wp_schedule_event(
                strtotime( 'next monday 8:00am' ),
                'weekly',
                self::HOOK
            );
        }
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( self::HOOK );
    }

    public static function send_digest(): void {
        $last_sent = get_option( self::OPTION_KEY, strtotime( '-7 days' ) );

        // Lấy bài viết mới trong tuần
        $posts = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'date_query'     => array(
                array(
                    'after'     => date( 'Y-m-d H:i:s', $last_sent ),
                    'inclusive' => false,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( empty( $posts ) ) {
            error_log( 'Weekly digest: Không có bài mới, bỏ qua.' );
            return;
        }

        // Build HTML email
        $html = self::build_email_html( $posts );

        // Lấy subscribers
        $subscribers = get_users( array(
            'meta_key'   => '_digest_subscribed',
            'meta_value' => '1',
            'fields'     => array( 'user_email', 'display_name' ),
        ) );

        if ( empty( $subscribers ) ) {
            return;
        }

        // Gửi email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
        );

        foreach ( $subscribers as $user ) {
            wp_mail(
                $user->user_email,
                sprintf( '[%s] Tổng hợp tuần %s', get_bloginfo( 'name' ), date_i18n( 'd/m/Y' ) ),
                str_replace( '{{name}}', $user->display_name, $html ),
                $headers
            );
        }

        // Cập nhật thời gian gửi
        update_option( self::OPTION_KEY, time() );
        error_log( sprintf(
            'Weekly digest sent to %d subscribers (%d posts)',
            count( $subscribers ),
            count( $posts )
        ) );
    }

    private static function build_email_html( array $posts ): string {
        $items = '';
        foreach ( $posts as $post ) {
            $items .= sprintf(
                '<tr>
                    <td style="padding:12px 0;border-bottom:1px solid #eee;">
                        <a href="%s" style="font-size:16px;color:#0073aa;text-decoration:none;font-weight:bold;">%s</a>
                        <br><span style="color:#666;font-size:13px;">%s — %s</span>
                        <br><span style="color:#444;font-size:14px;">%s</span>
                    </td>
                </tr>',
                esc_url( get_permalink( $post ) ),
                esc_html( $post->post_title ),
                esc_html( get_the_author_meta( 'display_name', $post->post_author ) ),
                esc_html( get_the_date( 'd/m/Y', $post ) ),
                esc_html( wp_trim_words( $post->post_content, 30 ) )
            );
        }

        return sprintf(
            '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;">
                <h2 style="color:#333;">Xin chào {{name}},</h2>
                <p>Đây là tổng hợp %d bài viết mới trong tuần:</p>
                <table width="100%%">%s</table>
                <p style="margin-top:20px;color:#666;font-size:12px;">
                    Bạn nhận email này vì đã đăng ký nhận thông báo.
                </p>
            </div>',
            count( $posts ),
            $items
        );
    }
}

Weekly_Email_Digest::register();
register_activation_hook( __FILE__, array( 'Weekly_Email_Digest', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Weekly_Email_Digest', 'deactivate' ) );
```

---

## 9. Ví dụ thực tế: Data Sync với API bên ngoài

```php
<?php
/**
 * Sync sản phẩm từ API bên ngoài (ERP, POS...) vào WooCommerce.
 * Dùng Action Scheduler cho reliability.
 */

class Product_Sync {

    private const GROUP     = 'product-sync';
    private const SYNC_HOOK = 'product_sync_run';
    private const ITEM_HOOK = 'product_sync_item';
    private const API_URL   = 'https://erp.example.com/api/v1/products';

    public static function register(): void {
        add_action( self::SYNC_HOOK, array( self::class, 'fetch_and_queue' ) );
        add_action( self::ITEM_HOOK, array( self::class, 'sync_single_product' ) );
    }

    /**
     * Schedule sync mỗi 6 giờ.
     */
    public static function activate(): void {
        if ( ! as_has_scheduled_action( self::SYNC_HOOK, array(), self::GROUP ) ) {
            as_schedule_recurring_action(
                time(),
                6 * HOUR_IN_SECONDS,
                self::SYNC_HOOK,
                array(),
                self::GROUP
            );
        }
    }

    public static function deactivate(): void {
        as_unschedule_all_actions( self::SYNC_HOOK, array(), self::GROUP );
        as_unschedule_all_actions( self::ITEM_HOOK, array(), self::GROUP );
    }

    /**
     * Bước 1: Gọi API lấy danh sách → tạo queue cho từng sản phẩm.
     */
    public static function fetch_and_queue(): void {
        $page     = 1;
        $per_page = 100;
        $total    = 0;

        do {
            $response = wp_remote_get( add_query_arg( array(
                'page'     => $page,
                'per_page' => $per_page,
            ), self::API_URL ), array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . get_option( 'product_sync_api_key' ),
                    'Accept'        => 'application/json',
                ),
                'timeout' => 30,
            ) );

            if ( is_wp_error( $response ) ) {
                throw new Exception( 'API fetch failed: ' . $response->get_error_message() );
            }

            $products = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $products ) ) {
                break;
            }

            // Queue từng sản phẩm
            foreach ( $products as $product_data ) {
                as_schedule_single_action(
                    time(),
                    self::ITEM_HOOK,
                    array( 'data' => $product_data ),
                    self::GROUP
                );
                $total++;
            }

            $page++;
        } while ( count( $products ) === $per_page );

        error_log( "Product sync: Queued {$total} products for processing" );
    }

    /**
     * Bước 2: Sync 1 sản phẩm vào WooCommerce.
     * Action Scheduler tự retry nếu fail.
     */
    public static function sync_single_product( array $data ): void {
        $sku = sanitize_text_field( $data['sku'] ?? '' );
        if ( empty( $sku ) ) {
            return;
        }

        // Tìm sản phẩm WooCommerce theo SKU
        $product_id = wc_get_product_id_by_sku( $sku );

        if ( $product_id ) {
            // Cập nhật sản phẩm có sẵn
            $product = wc_get_product( $product_id );
        } else {
            // Tạo mới
            $product = new WC_Product_Simple();
            $product->set_sku( $sku );
        }

        // Cập nhật thông tin
        $product->set_name( sanitize_text_field( $data['name'] ?? '' ) );
        $product->set_regular_price( (string) floatval( $data['price'] ?? 0 ) );
        $product->set_stock_quantity( (int) ( $data['stock'] ?? 0 ) );
        $product->set_manage_stock( true );
        $product->set_description( wp_kses_post( $data['description'] ?? '' ) );

        $product->update_meta_data( '_erp_last_synced', current_time( 'mysql' ) );
        $product->update_meta_data( '_erp_id', sanitize_text_field( $data['id'] ?? '' ) );

        $product->save();

        error_log( "Synced product: SKU={$sku}, ID={$product->get_id()}" );
    }
}

Product_Sync::register();
```

---

## 10. Ví dụ thực tế: Cleanup & Maintenance

```php
<?php
/**
 * Plugin tự động dọn dẹp database.
 */

class Database_Cleanup {

    private const HOOK  = 'my_plugin_database_cleanup';
    private const GROUP = 'my-plugin-cleanup';

    public static function register(): void {
        add_action( self::HOOK, array( self::class, 'run_cleanup' ) );
    }

    public static function activate(): void {
        // Chạy mỗi ngày lúc 3:00 AM
        if ( ! as_has_scheduled_action( self::HOOK ) ) {
            as_schedule_cron_action(
                time(),
                '0 3 * * *',
                self::HOOK,
                array(),
                self::GROUP
            );
        }
    }

    public static function run_cleanup(): void {
        global $wpdb;
        $results = array();

        // 1. Xóa post revisions cũ hơn 30 ngày
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->posts}
                 WHERE post_type = 'revision'
                 AND post_modified < %s",
                date( 'Y-m-d', strtotime( '-30 days' ) )
            )
        );
        $results['revisions'] = $deleted;

        // 2. Xóa trashed posts cũ hơn 7 ngày
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->posts}
                 WHERE post_status = 'trash'
                 AND post_modified < %s",
                date( 'Y-m-d', strtotime( '-7 days' ) )
            )
        );
        $results['trashed'] = $deleted;

        // 3. Xóa orphaned postmeta
        $deleted = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.ID IS NULL"
        );
        $results['orphaned_meta'] = $deleted;

        // 4. Xóa spam comments
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->comments}
             WHERE comment_approved = 'spam'"
        );
        $results['spam_comments'] = $deleted;

        // 5. Xóa trashed comments
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->comments}
             WHERE comment_approved = 'trash'"
        );
        $results['trashed_comments'] = $deleted;

        // 6. Xóa expired transients
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a
                 INNER JOIN {$wpdb->options} b
                    ON b.option_name = REPLACE(a.option_name, '_transient_', '_transient_timeout_')
                 WHERE a.option_name LIKE '\_transient\_%'
                 AND a.option_name NOT LIKE '\_transient\_timeout\_%'
                 AND b.option_value < %d",
                time()
            )
        );
        $results['expired_transients'] = $deleted;

        // 7. Optimize tables
        $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE `{$table}`" );
        }
        $results['tables_optimized'] = count( $tables );

        // Log kết quả
        error_log( sprintf(
            'Database cleanup completed: %s',
            wp_json_encode( $results )
        ) );

        // Lưu kết quả cho admin dashboard
        update_option( 'my_plugin_last_cleanup', array(
            'time'    => current_time( 'mysql' ),
            'results' => $results,
        ) );
    }
}

Database_Cleanup::register();
```

---

## 11. WP-CLI quản lý Cron

```bash
# ── LIỆT KÊ EVENTS ─────────────────────────────────────────────

# Liệt kê tất cả cron events
wp cron event list

# Output:
# +-----------------------------------+---------------------+-----------+------------------+
# | hook                              | next_run_gmt        | next_run  | recurrence       |
# +-----------------------------------+---------------------+-----------+------------------+
# | wp_version_check                  | 2024-01-15 03:00:00 | 2 hours   | twicedaily       |
# | my_plugin_daily_report            | 2024-01-15 02:00:00 | 1 hour    | daily            |
# | my_plugin_hourly_cleanup          | 2024-01-15 01:30:00 | 30 minutes| hourly           |
# +-----------------------------------+---------------------+-----------+------------------+

# Lọc theo hook name
wp cron event list --fields=hook,next_run,recurrence | grep my_plugin

# ── CHẠY EVENTS ──────────────────────────────────────────────────

# Chạy tất cả events đến hạn
wp cron event run --due-now

# Chạy 1 event cụ thể (bất kể có đến hạn hay không)
wp cron event run my_plugin_daily_report

# ── XÓA EVENTS ──────────────────────────────────────────────────

# Xóa 1 event
wp cron event delete my_plugin_hourly_cleanup

# ── SCHEDULES ────────────────────────────────────────────────────

# Liệt kê tất cả intervals đã đăng ký
wp cron schedule list

# Output:
# +-------------------------+----------+------------------+
# | name                    | interval | display          |
# +-------------------------+----------+------------------+
# | hourly                  | 3600     | Once Hourly      |
# | twicedaily              | 43200    | Twice Daily      |
# | daily                   | 86400    | Once Daily       |
# | weekly                  | 604800   | Once Weekly      |
# | every_five_minutes      | 300      | Mỗi 5 phút      |
# +-------------------------+----------+------------------+

# ── TEST CRON ────────────────────────────────────────────────────

# Kiểm tra WP-Cron có hoạt động không
wp cron test
# Output: "Success: WP-Cron spawning is working as expected."

# ── ACTION SCHEDULER (nếu có) ───────────────────────────────────

# Liệt kê actions
wp action-scheduler list --status=pending --per-page=20

# Chạy actions đến hạn
wp action-scheduler run

# Chạy với giới hạn
wp action-scheduler run --batch-size=100 --time-limit=60
```

---

## 12. Debugging & Monitoring

### 12.1. Debug Cron Events

```php
<?php
/**
 * Thêm debug info vào admin dashboard.
 */

add_action( 'admin_menu', function() {
    add_management_page(
        'Cron Monitor',
        'Cron Monitor',
        'manage_options',
        'cron-monitor',
        'render_cron_monitor_page'
    );
} );

function render_cron_monitor_page() {
    $crons = _get_cron_array();

    echo '<div class="wrap">';
    echo '<h1>Cron Event Monitor</h1>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>Hook</th><th>Next Run</th><th>Recurrence</th><th>Args</th></tr></thead>';
    echo '<tbody>';

    foreach ( $crons as $timestamp => $hooks ) {
        foreach ( $hooks as $hook => $events ) {
            foreach ( $events as $key => $event ) {
                $time_diff = $timestamp - time();
                $status    = $time_diff < 0 ? '<span style="color:red;">OVERDUE</span>' : human_time_diff( time(), $timestamp );

                printf(
                    '<tr><td><code>%s</code></td><td>%s (%s)</td><td>%s</td><td><pre>%s</pre></td></tr>',
                    esc_html( $hook ),
                    esc_html( date( 'Y-m-d H:i:s', $timestamp ) ),
                    $status,
                    esc_html( $event['schedule'] ?: 'single' ),
                    esc_html( wp_json_encode( $event['args'], JSON_PRETTY_PRINT ) )
                );
            }
        }
    }

    echo '</tbody></table>';
    echo '</div>';
}
```

### 12.2. Logging Cron Execution

```php
<?php
/**
 * Log mỗi khi cron event chạy.
 */

// Hook vào tất cả cron events của plugin
$my_hooks = array(
    'my_plugin_hourly_cleanup',
    'my_plugin_daily_report',
    'my_plugin_sync_data',
);

foreach ( $my_hooks as $hook ) {
    // Log trước khi chạy
    add_action( $hook, function() use ( $hook ) {
        error_log( sprintf(
            '[CRON START] %s at %s | Memory: %s',
            $hook,
            current_time( 'mysql' ),
            size_format( memory_get_usage( true ) )
        ) );
    }, 1 ); // Priority 1 = chạy đầu tiên

    // Log sau khi chạy
    add_action( $hook, function() use ( $hook ) {
        error_log( sprintf(
            '[CRON END] %s at %s | Peak memory: %s',
            $hook,
            current_time( 'mysql' ),
            size_format( memory_get_peak_usage( true ) )
        ) );
    }, PHP_INT_MAX ); // Priority cao nhất = chạy cuối cùng
}
```

### 12.3. Health Check

```php
<?php
/**
 * Kiểm tra cron có hoạt động đúng không.
 * Thêm vào Site Health (Tools → Site Health).
 */

add_filter( 'site_status_tests', function( $tests ) {
    $tests['direct']['cron_health'] = array(
        'label' => __( 'Cron Events Health', 'my-plugin' ),
        'test'  => 'my_plugin_cron_health_check',
    );
    return $tests;
} );

function my_plugin_cron_health_check() {
    $result = array(
        'label'       => 'Cron events đang hoạt động bình thường',
        'status'      => 'good',
        'badge'       => array( 'label' => 'Performance', 'color' => 'blue' ),
        'description' => '',
        'actions'     => '',
        'test'        => 'cron_health',
    );

    // Kiểm tra WP-Cron có bị disable không
    if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
        $result['label']  = 'WP-Cron bị tắt - cần system cron';
        $result['status'] = 'recommended';
        $result['description'] = '<p>DISABLE_WP_CRON đang bật. Đảm bảo system cron đã được cấu hình.</p>';
    }

    // Kiểm tra có event overdue không
    $crons   = _get_cron_array();
    $overdue = 0;
    foreach ( $crons as $timestamp => $hooks ) {
        if ( $timestamp < ( time() - HOUR_IN_SECONDS ) ) {
            $overdue += count( $hooks );
        }
    }

    if ( $overdue > 5 ) {
        $result['label']  = sprintf( '%d cron events bị trễ', $overdue );
        $result['status'] = 'critical';
        $result['description'] = '<p>Nhiều cron events bị trễ quá 1 giờ. Kiểm tra lại cấu hình cron.</p>';
    }

    return $result;
}
```

---

## 13. So sánh WP-Cron vs Action Scheduler vs Laravel

### Bảng so sánh tổng quan

| Tính năng | WP-Cron | Action Scheduler | Laravel Queue |
|-----------|---------|-----------------|---------------|
| **Cơ chế** | Pseudo-cron (visitor-triggered) | DB-backed job queue | Redis/DB queue + worker |
| **Storage** | `wp_options` (1 row) | Custom DB tables | `jobs` table hoặc Redis |
| **Retry** | Không tự retry | 5 lần, exponential backoff | Configurable retries |
| **Concurrency** | Cron lock (1 process) | Concurrent runners | Multiple workers |
| **Monitoring** | Không có UI | Admin UI tại Tools | Horizon dashboard |
| **Scale** | Hàng chục events | Hàng triệu actions | Hàng triệu jobs |
| **Logging** | Không | Có (action logs) | Có (failed_jobs table) |
| **Priority** | Không | Không (FIFO) | Có (queue priority) |
| **Delay** | Có (timestamp) | Có (timestamp + cron) | Có (delay/release) |
| **Batch** | Tự implement | Tự implement | Bus::batch() |
| **Unique jobs** | wp_next_scheduled() | as_has_scheduled_action() | ShouldBeUnique |
| **Cài đặt** | Built-in | Composer require | Built-in |
| **Khi nào dùng** | Tasks đơn giản, ít events | Plugin phức tạp, WooCommerce | Laravel apps |

### So sánh code

```php
<?php
// ── LARAVEL ─────────────────────────────────────────────────────
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('report:daily')->dailyAt('02:00');
    $schedule->job(new SyncProducts)->everyFifteenMinutes();
    $schedule->call(fn() => DB::table('logs')->where('created_at', '<', now()->subMonth())->delete())
             ->monthly();
}

// app/Jobs/SyncProducts.php
class SyncProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [60, 300, 3600];

    public function handle(): void
    {
        // Sync logic...
    }

    public function failed(Throwable $exception): void
    {
        // Notify admin...
    }
}

// Dispatch
SyncProducts::dispatch()->delay(now()->addMinutes(5));

// ── WORDPRESS WP-CRON ──────────────────────────────────────────
// Schedule
wp_schedule_event(strtotime('tomorrow 2:00am'), 'daily', 'my_daily_report');
add_action('my_daily_report', 'do_daily_report');

// Single job
wp_schedule_single_event(time() + 300, 'my_sync_products');
add_action('my_sync_products', 'do_sync_products');

// ── WORDPRESS ACTION SCHEDULER ─────────────────────────────────
// Schedule recurring
as_schedule_recurring_action(time(), 15 * MINUTE_IN_SECONDS, 'my_sync_products', [], 'my-plugin');
add_action('my_sync_products', 'do_sync_products');

// Single job với delay
as_schedule_single_action(time() + 300, 'my_process_order', ['order_id' => 123], 'my-plugin');

// Async (chạy ASAP, non-blocking)
as_enqueue_async_action('my_send_notification', ['user_id' => 456], 'my-plugin');
```

### Sơ đồ quyết định chọn công cụ

```
Cần background job trong WordPress?
       │
       ├── Chỉ cần schedule đơn giản (daily backup, hourly check)?
       │   └── ✅ WP-Cron (built-in, không cần thêm dependency)
       │
       ├── Cần xử lý hàng ngàn items, retry khi fail, monitoring?
       │   └── ✅ Action Scheduler (thêm via Composer)
       │
       ├── Đã dùng WooCommerce?
       │   └── ✅ Action Scheduler (đã có sẵn qua WooCommerce)
       │
       └── Cần real-time queue với workers riêng?
           └── ✅ Xem xét WP Background Processing hoặc external queue (Redis + custom worker)
```

---

## Tổng kết

| Chủ đề | Hàm/API quan trọng |
|--------|-------------------|
| Schedule recurring | `wp_schedule_event()`, `as_schedule_recurring_action()` |
| Schedule single | `wp_schedule_single_event()`, `as_schedule_single_action()` |
| Async (ASAP) | `as_enqueue_async_action()` |
| Check exists | `wp_next_scheduled()`, `as_has_scheduled_action()` |
| Cancel | `wp_clear_scheduled_hook()`, `as_unschedule_all_actions()` |
| Custom interval | Filter `cron_schedules` |
| System cron | `DISABLE_WP_CRON` + crontab |
| WP-CLI | `wp cron event list/run`, `wp action-scheduler run` |
| Batch processing | Self-scheduling pattern hoặc AS per-item actions |
| Deactivation | LUÔN `wp_clear_scheduled_hook()` trong deactivation hook |

---

[← Quay lại mục lục](./index.md) | [Tiếp: Multisite →](./06-multisite.md)
