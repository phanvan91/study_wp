# Custom Hooks - Tạo Hooks Riêng

## Mục Lục

1. [Tại sao cần Custom Hooks?](#1-tại-sao-cần-custom-hooks)
2. [Tạo Custom Action Hooks](#2-tạo-custom-action-hooks)
3. [Tạo Custom Filter Hooks](#3-tạo-custom-filter-hooks)
4. [Naming Convention](#4-naming-convention)
5. [Documenting Custom Hooks](#5-documenting-custom-hooks)
6. [Pluggable Functions](#6-pluggable-functions)
7. [Hook trong Plugin](#7-hook-trong-plugin)
8. [Hook trong Theme](#8-hook-trong-theme)
9. [Design Pattern: Observer Pattern](#9-design-pattern-observer-pattern)
10. [Code ví dụ: Plugin hoàn chỉnh với Custom Hooks](#10-code-ví-dụ-plugin-hoàn-chỉnh-với-custom-hooks)
11. [Best Practices](#11-best-practices)

---

## 1. Tại sao cần Custom Hooks?

### Vấn đề: Code cứng, không thể mở rộng

```php
<?php
// Plugin A: Xử lý đơn hàng
function process_order( $order_data ) {
    // 1. Validate
    // 2. Lưu vào DB
    $order_id = save_order( $order_data );

    // 3. Gửi email (hardcoded - không thể thay đổi)
    wp_mail( $order_data['email'], 'Xác nhận đơn hàng', '...' );

    // 4. Ghi log (hardcoded)
    error_log( 'New order: ' . $order_id );

    // Nếu muốn thêm: gửi SMS, cập nhật CRM, tích điểm...
    // Phải SỬA TRỰC TIẾP code plugin → XẤU!
}
```

### Giải pháp: Custom Hooks

```php
<?php
// Plugin A: Xử lý đơn hàng VỚI CUSTOM HOOKS
function process_order( $order_data ) {
    // 1. Cho phép validate tùy chỉnh
    $order_data = apply_filters( 'my_shop_order_data', $order_data );

    // 2. Cho phép hủy đơn hàng trước khi lưu
    $should_process = apply_filters( 'my_shop_should_process_order', true, $order_data );
    if ( ! $should_process ) {
        return false;
    }

    // 3. Lưu vào DB
    $order_id = save_order( $order_data );

    // 4. Hook SAU khi tạo đơn hàng → plugin/theme khác có thể mở rộng
    do_action( 'my_shop_order_created', $order_id, $order_data );

    return $order_id;
}

// Bây giờ, BẤT KỲ AI cũng có thể thêm chức năng:

// Plugin B: Gửi SMS
add_action( 'my_shop_order_created', function( $order_id, $data ) {
    send_sms( $data['phone'], 'Đơn hàng #' . $order_id . ' đã được tạo.' );
}, 10, 2 );

// Plugin C: Tích điểm loyalty
add_action( 'my_shop_order_created', function( $order_id, $data ) {
    add_loyalty_points( $data['user_id'], $data['total'] );
}, 10, 2 );

// Plugin D: Sync với CRM
add_action( 'my_shop_order_created', function( $order_id, $data ) {
    update_crm_order( $order_id, $data );
}, 20, 2 );
```

### So sánh với Laravel

```
Laravel:
    Event::dispatch(new OrderCreated($order));   // Phát event
    // Listeners tự động chạy

WordPress:
    do_action('my_shop_order_created', $order_id, $order_data);  // Phát hook
    // Callbacks đã add_action() sẽ chạy
```

---

## 2. Tạo Custom Action Hooks

### Cú pháp cơ bản

```php
<?php
/**
 * do_action() - Tạo một điểm mở rộng trong code
 *
 * @param string $hook_name  Tên hook (unique)
 * @param mixed  ...$args    Các tham số truyền cho callbacks
 */
do_action( 'hook_name', $arg1, $arg2, ... );
```

### Ví dụ 1: Plugin membership với lifecycle hooks

```php
<?php
/**
 * Plugin Name: My Membership
 * Description: Plugin membership với custom hooks
 */

class My_Membership {

    /**
     * Đăng ký thành viên mới
     */
    public function register_member( $user_data ) {
        // Hook TRƯỚC khi xử lý - cho phép validate/modify
        do_action( 'my_membership_before_register', $user_data );

        // Tạo user WordPress
        $user_id = wp_create_user(
            sanitize_user( $user_data['username'] ),
            $user_data['password'],
            sanitize_email( $user_data['email'] )
        );

        if ( is_wp_error( $user_id ) ) {
            // Hook khi đăng ký thất bại
            do_action( 'my_membership_register_failed', $user_data, $user_id );
            return $user_id;
        }

        // Set role và meta
        $user = new WP_User( $user_id );
        $user->set_role( 'subscriber' );
        update_user_meta( $user_id, '_membership_level', $user_data['level'] ?? 'free' );
        update_user_meta( $user_id, '_membership_start', current_time( 'mysql' ) );

        // Hook SAU khi đăng ký thành công - HOOK CHÍNH
        // Plugin khác có thể: gửi welcome email, add vào newsletter, tạo profile, etc.
        do_action( 'my_membership_after_register', $user_id, $user_data );

        return $user_id;
    }

    /**
     * Nâng cấp membership
     */
    public function upgrade_membership( $user_id, $new_level ) {
        $old_level = get_user_meta( $user_id, '_membership_level', true );

        // Hook trước khi upgrade
        do_action( 'my_membership_before_upgrade', $user_id, $old_level, $new_level );

        update_user_meta( $user_id, '_membership_level', $new_level );
        update_user_meta( $user_id, '_membership_upgraded', current_time( 'mysql' ) );

        // Hook sau khi upgrade - rất hữu ích!
        // Dùng cho: gửi email, unlock content, cập nhật quyền, log
        do_action( 'my_membership_after_upgrade', $user_id, $old_level, $new_level );
    }

    /**
     * Hủy membership
     */
    public function cancel_membership( $user_id, $reason = '' ) {
        $level = get_user_meta( $user_id, '_membership_level', true );

        do_action( 'my_membership_before_cancel', $user_id, $level, $reason );

        update_user_meta( $user_id, '_membership_level', 'cancelled' );
        update_user_meta( $user_id, '_membership_cancelled', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_cancellation_reason', $reason );

        do_action( 'my_membership_after_cancel', $user_id, $level, $reason );
    }
}

// === PLUGIN KHÁC HOOK VÀO ===

// Gửi welcome email khi member mới đăng ký
add_action( 'my_membership_after_register', 'my_send_welcome_email', 10, 2 );
function my_send_welcome_email( $user_id, $user_data ) {
    $user = get_userdata( $user_id );
    wp_mail(
        $user->user_email,
        'Chào mừng bạn đến với membership!',
        sprintf(
            "Xin chào %s,\n\nTài khoản %s level %s đã được tạo.\n\nĐăng nhập tại: %s",
            $user->display_name,
            $user_data['level'],
            $user_data['level'],
            wp_login_url()
        )
    );
}

// Log tất cả membership events
add_action( 'my_membership_after_register', function( $user_id, $data ) {
    error_log( "[Membership] New member: #{$user_id} - Level: {$data['level']}" );
}, 10, 2 );

add_action( 'my_membership_after_upgrade', function( $user_id, $old, $new ) {
    error_log( "[Membership] Upgrade: #{$user_id} - {$old} → {$new}" );
}, 10, 3 );

add_action( 'my_membership_after_cancel', function( $user_id, $level, $reason ) {
    error_log( "[Membership] Cancel: #{$user_id} - Level: {$level} - Reason: {$reason}" );
}, 10, 3 );

// Gửi email khi upgrade
add_action( 'my_membership_after_upgrade', function( $user_id, $old_level, $new_level ) {
    $user = get_userdata( $user_id );
    wp_mail(
        $user->user_email,
        'Nâng cấp tài khoản thành công!',
        sprintf(
            "Xin chào %s,\n\nTài khoản đã được nâng cấp từ %s lên %s.\n\nCảm ơn bạn!",
            $user->display_name,
            $old_level,
            $new_level
        )
    );
}, 10, 3 );
```

---

## 3. Tạo Custom Filter Hooks

### Cú pháp cơ bản

```php
<?php
/**
 * apply_filters() - Tạo điểm cho phép filter dữ liệu
 *
 * @param string $hook_name  Tên filter hook
 * @param mixed  $value      Giá trị mặc định (sẽ được filter)
 * @param mixed  ...$args    Tham số bổ sung
 * @return mixed             Giá trị sau khi filter
 */
$result = apply_filters( 'hook_name', $default_value, $arg1, $arg2, ... );
```

### Ví dụ 1: Plugin pricing với filterable values

```php
<?php
/**
 * Tính giá sản phẩm với nhiều điểm filter
 */
function my_calculate_product_price( $product_id, $quantity = 1, $user_id = 0 ) {
    // Lấy giá gốc
    $base_price = floatval( get_post_meta( $product_id, '_product_price', true ) );

    // Filter: Cho phép thay đổi giá gốc
    // Ví dụ: dynamic pricing, currency conversion
    $price = apply_filters( 'my_shop_base_price', $base_price, $product_id );

    // Tính giá theo số lượng
    $subtotal = $price * $quantity;

    // Filter: Áp dụng giảm giá
    // Ví dụ: coupon, membership discount, seasonal sale
    $discount = apply_filters( 'my_shop_discount', 0, $product_id, $quantity, $user_id );

    $subtotal -= $discount;

    // Filter: Tính thuế
    // Ví dụ: VAT, tax theo khu vực
    $tax_rate = apply_filters( 'my_shop_tax_rate', 0.1, $product_id ); // Mặc định 10%
    $tax_amount = $subtotal * $tax_rate;

    // Filter: Tính phí vận chuyển
    $shipping = apply_filters( 'my_shop_shipping_cost', 30000, $product_id, $quantity );

    // Tổng cộng
    $total = $subtotal + $tax_amount + $shipping;

    // Filter cuối cùng: Cho phép override tổng tiền
    $total = apply_filters( 'my_shop_order_total', $total, array(
        'product_id' => $product_id,
        'quantity'   => $quantity,
        'user_id'    => $user_id,
        'subtotal'   => $subtotal,
        'discount'   => $discount,
        'tax'        => $tax_amount,
        'shipping'   => $shipping,
    ));

    return $total;
}

// === PLUGIN KHÁC FILTER GIÁ ===

// Membership discount: 20% cho premium members
add_filter( 'my_shop_discount', function( $discount, $product_id, $quantity, $user_id ) {
    if ( ! $user_id ) {
        return $discount;
    }

    $level = get_user_meta( $user_id, '_membership_level', true );
    if ( 'premium' === $level ) {
        $base_price = floatval( get_post_meta( $product_id, '_product_price', true ) );
        $discount += ( $base_price * $quantity ) * 0.20; // Giảm 20%
    }

    return $discount;
}, 10, 4 );

// Miễn phí ship cho đơn trên 500k
add_filter( 'my_shop_shipping_cost', function( $shipping, $product_id, $quantity ) {
    $price    = floatval( get_post_meta( $product_id, '_product_price', true ) );
    $subtotal = $price * $quantity;

    if ( $subtotal >= 500000 ) {
        return 0; // Miễn phí ship
    }

    return $shipping;
}, 10, 3 );

// Tax 0% cho sản phẩm giáo dục
add_filter( 'my_shop_tax_rate', function( $rate, $product_id ) {
    $terms = wp_get_post_terms( $product_id, 'product_category', array( 'fields' => 'slugs' ) );
    if ( in_array( 'giao-duc', $terms, true ) ) {
        return 0; // Không tính thuế cho sản phẩm giáo dục
    }
    return $rate;
}, 10, 2 );
```

### Ví dụ 2: Template builder với filterable output

```php
<?php
/**
 * Tạo email template có thể customize
 */
function my_get_email_template( $type, $data ) {
    // Header (filterable)
    $header = apply_filters( 'my_email_header', sprintf(
        '<div style="background:#0073aa; color:#fff; padding:20px; text-align:center;">' .
        '<h1>%s</h1></div>',
        get_bloginfo( 'name' )
    ), $type );

    // Body content dựa trên type
    switch ( $type ) {
        case 'welcome':
            $body = sprintf(
                '<h2>Chào mừng %s!</h2>' .
                '<p>Cảm ơn bạn đã tham gia.</p>',
                esc_html( $data['name'] )
            );
            break;

        case 'order_confirmation':
            $body = sprintf(
                '<h2>Xác nhận đơn hàng #%d</h2>' .
                '<p>Tổng tiền: %s VNĐ</p>',
                $data['order_id'],
                number_format( $data['total'] )
            );
            break;

        default:
            $body = $data['content'] ?? '';
    }

    // Filter body (cho phép customize nội dung)
    $body = apply_filters( 'my_email_body', $body, $type, $data );

    // Footer (filterable)
    $footer = apply_filters( 'my_email_footer', sprintf(
        '<div style="padding:15px; text-align:center; color:#999; font-size:12px;">' .
        '<p>&copy; %d %s. Mọi quyền được bảo lưu.</p></div>',
        date( 'Y' ),
        get_bloginfo( 'name' )
    ), $type );

    // Toàn bộ template (filterable)
    $template = apply_filters( 'my_email_template', sprintf(
        '<div style="max-width:600px; margin:0 auto; font-family:Arial,sans-serif;">%s<div style="padding:30px;">%s</div>%s</div>',
        $header,
        $body,
        $footer
    ), $type, $data );

    return $template;
}

// Plugin khác có thể customize:
add_filter( 'my_email_footer', function( $footer, $type ) {
    // Thêm link unsubscribe vào footer
    $footer .= '<p><a href="' . home_url( '/unsubscribe/' ) . '">Hủy đăng ký email</a></p>';
    return $footer;
}, 10, 2 );

add_filter( 'my_email_body', function( $body, $type, $data ) {
    // Thêm banner promo vào email welcome
    if ( 'welcome' === $type ) {
        $body .= '<div style="background:#fff3cd; padding:15px; margin:20px 0; border-radius:5px;">';
        $body .= '<strong>Ưu đãi đặc biệt!</strong> Giảm 10% đơn hàng đầu tiên với mã: WELCOME10';
        $body .= '</div>';
    }
    return $body;
}, 10, 3 );
```

---

## 4. Naming Convention

### Quy tắc đặt tên

```php
<?php
// === PREFIX ===
// Luôn thêm prefix unique để tránh conflict

// SAI: Tên quá chung
do_action( 'order_created' );          // Có thể conflict với plugin khác!
apply_filters( 'product_price', $price ); // Quá chung!

// ĐÚNG: Thêm prefix plugin/theme name
do_action( 'my_shop_order_created' );
apply_filters( 'my_shop_product_price', $price );

// === FORMAT ===
// {prefix}_{context}_{action/subject}

// Action hooks: diễn tả HÀNH ĐỘNG
do_action( 'my_shop_before_checkout' );        // Trước checkout
do_action( 'my_shop_after_checkout' );         // Sau checkout
do_action( 'my_shop_order_created' );          // Đơn hàng được tạo
do_action( 'my_shop_payment_completed' );      // Thanh toán xong
do_action( 'my_shop_order_status_changed' );   // Trạng thái đổi

// Filter hooks: diễn tả DỮ LIỆU được filter
apply_filters( 'my_shop_product_price', $price, $product_id );
apply_filters( 'my_shop_cart_items', $items );
apply_filters( 'my_shop_checkout_fields', $fields );
apply_filters( 'my_shop_order_email_subject', $subject, $order_id );
apply_filters( 'my_shop_allowed_payment_methods', $methods );
```

### Naming patterns phổ biến

```php
<?php
// Pattern 1: before/after (cho lifecycle events)
do_action( 'my_plugin_before_save', $data );
// ... thực hiện save ...
do_action( 'my_plugin_after_save', $id, $data );

// Pattern 2: pre (cho validation/modification trước khi xử lý)
$data = apply_filters( 'my_plugin_pre_save_data', $data );

// Pattern 3: {subject}_{verb} (cho sự kiện cụ thể)
do_action( 'my_plugin_user_registered', $user_id );
do_action( 'my_plugin_order_cancelled', $order_id );

// Pattern 4: {noun} (cho dữ liệu)
$columns = apply_filters( 'my_plugin_table_columns', $default_columns );
$args    = apply_filters( 'my_plugin_query_args', $default_args );

// Pattern 5: Dynamic hooks (hook có tên thay đổi)
do_action( "my_plugin_{$post_type}_saved", $post_id );
do_action( "my_plugin_status_{$new_status}", $order_id, $old_status );
$template = apply_filters( "my_plugin_template_{$type}", $default_template );
```

---

## 5. Documenting Custom Hooks

### Chuẩn WordPress Documentation

```php
<?php
/**
 * Fires after a new membership is successfully registered.
 *
 * Cho phép plugin/theme khác thực hiện hành động sau khi member mới đăng ký.
 * Ví dụ: gửi welcome email, thêm vào newsletter, tạo profile mặc định.
 *
 * @since 1.0.0
 * @since 1.2.0 Thêm tham số $user_data
 *
 * @param int   $user_id   ID của user vừa đăng ký.
 * @param array $user_data {
 *     Dữ liệu đăng ký.
 *
 *     @type string $username Username đã đăng ký.
 *     @type string $email    Email address.
 *     @type string $level    Membership level ('free', 'basic', 'premium').
 * }
 */
do_action( 'my_membership_after_register', $user_id, $user_data );

/**
 * Filters the product price before display.
 *
 * Cho phép thay đổi giá sản phẩm hiển thị. Có thể dùng cho:
 * - Chuyển đổi đơn vị tiền tệ
 * - Áp dụng giảm giá theo role
 * - Dynamic pricing
 *
 * @since 1.0.0
 *
 * @param float $price      Giá sản phẩm (VNĐ).
 * @param int   $product_id Post ID của sản phẩm.
 * @param int   $user_id    ID user đang xem (0 nếu chưa đăng nhập).
 * @return float Giá đã được filter.
 */
$price = apply_filters( 'my_shop_product_price', $price, $product_id, $user_id );
```

---

## 6. Pluggable Functions

### Khái niệm

Pluggable functions là functions có thể bị THAY THẾ HOÀN TOÀN bởi plugin/theme. WordPress core có một số pluggable functions (trong `wp-includes/pluggable.php`).

```php
<?php
// Cách WordPress core định nghĩa pluggable function:
if ( ! function_exists( 'wp_mail' ) ) {
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        // Default implementation
    }
}

// Plugin có thể THAY THẾ HOÀN TOÀN hàm wp_mail() bằng cách define trước:
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
    // Custom implementation (ví dụ: gửi qua Amazon SES)
}
```

### Tạo Pluggable Functions cho plugin của bạn

```php
<?php
// File: my-plugin/includes/pluggable.php

// Hàm gửi notification - có thể được override bởi theme
if ( ! function_exists( 'my_plugin_send_notification' ) ) {
    /**
     * Gửi notification cho user.
     * Hàm này có thể được override bởi theme hoặc plugin khác
     * bằng cách define function cùng tên TRƯỚC khi plugin load.
     *
     * @param int    $user_id ID user.
     * @param string $title   Tiêu đề notification.
     * @param string $message Nội dung.
     * @param string $type    Loại: 'info', 'success', 'warning', 'error'.
     */
    function my_plugin_send_notification( $user_id, $title, $message, $type = 'info' ) {
        // Implementation mặc định: lưu vào user meta
        $notifications = get_user_meta( $user_id, '_notifications', true ) ?: array();
        $notifications[] = array(
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'time'    => current_time( 'mysql' ),
            'read'    => false,
        );
        update_user_meta( $user_id, '_notifications', $notifications );
    }
}

// Hàm format giá - có thể override
if ( ! function_exists( 'my_plugin_format_price' ) ) {
    function my_plugin_format_price( $price ) {
        return number_format( $price, 0, ',', '.' ) . ' VNĐ';
    }
}

// Theme có thể override:
// Trong functions.php (load trước plugin):
function my_plugin_format_price( $price ) {
    return '$' . number_format( $price / 23000, 2 ); // Chuyển sang USD
}
```

### So sánh: Pluggable vs Hooks

```php
<?php
// Pluggable: THAY THẾ HOÀN TOÀN logic
// Chỉ cho phép 1 override
if ( ! function_exists( 'my_format_date' ) ) {
    function my_format_date( $date ) {
        return date( 'd/m/Y', strtotime( $date ) );
    }
}

// Hooks: MỞ RỘNG hoặc SỬA ĐỔI logic
// Cho phép NHIỀU callbacks
function my_format_date( $date ) {
    $formatted = date( 'd/m/Y', strtotime( $date ) );
    return apply_filters( 'my_plugin_date_format', $formatted, $date );
}

// KHUYẾN NGHỊ: Dùng Hooks thay vì Pluggable Functions
// Vì hooks linh hoạt hơn (nhiều callbacks, priority, có thể remove)
```

---

## 7. Hook trong Plugin

### Plugin extensible: Cho phép người khác mở rộng

```php
<?php
/**
 * Plugin Name: My Contact Form
 * Description: Plugin form liên hệ có thể mở rộng
 * Version: 1.0.0
 */

class My_Contact_Form {

    public function __construct() {
        add_shortcode( 'my_contact_form', array( $this, 'render_form' ) );
        add_action( 'wp_ajax_my_submit_contact', array( $this, 'handle_submission' ) );
        add_action( 'wp_ajax_nopriv_my_submit_contact', array( $this, 'handle_submission' ) );
    }

    /**
     * Render form liên hệ
     */
    public function render_form( $atts ) {
        // Filter: Cho phép tùy chỉnh các trường
        $fields = apply_filters( 'my_contact_form_fields', array(
            'name' => array(
                'type'        => 'text',
                'label'       => 'Họ và tên',
                'required'    => true,
                'placeholder' => 'Nhập họ tên của bạn',
            ),
            'email' => array(
                'type'        => 'email',
                'label'       => 'Email',
                'required'    => true,
                'placeholder' => 'Nhập email',
            ),
            'phone' => array(
                'type'        => 'tel',
                'label'       => 'Số điện thoại',
                'required'    => false,
                'placeholder' => 'Nhập số điện thoại',
            ),
            'message' => array(
                'type'        => 'textarea',
                'label'       => 'Nội dung',
                'required'    => true,
                'placeholder' => 'Nhập nội dung tin nhắn',
            ),
        ));

        ob_start();

        // Action: Trước form
        do_action( 'my_contact_form_before_form' );

        echo '<form id="my-contact-form" class="my-contact-form">';

        // Action: Đầu form (sau thẻ <form>)
        do_action( 'my_contact_form_start' );

        // Render fields
        foreach ( $fields as $name => $field ) {
            // Filter: Cho phép sửa HTML của từng field
            $field_html = $this->render_field( $name, $field );
            echo apply_filters( 'my_contact_form_field_html', $field_html, $name, $field );
        }

        // Action: Trước nút submit
        do_action( 'my_contact_form_before_submit' );

        // Filter: Cho phép sửa text nút submit
        $submit_text = apply_filters( 'my_contact_form_submit_text', 'Gửi tin nhắn' );
        echo '<button type="submit" class="my-cf-submit">' . esc_html( $submit_text ) . '</button>';

        // Action: Cuối form (trước thẻ </form>)
        do_action( 'my_contact_form_end' );

        echo '</form>';

        // Action: Sau form
        do_action( 'my_contact_form_after_form' );

        return ob_get_clean();
    }

    /**
     * Render một field
     */
    private function render_field( $name, $field ) {
        $required = $field['required'] ? 'required' : '';
        $html = '<div class="my-cf-field my-cf-field-' . esc_attr( $name ) . '">';
        $html .= '<label for="my-cf-' . esc_attr( $name ) . '">' . esc_html( $field['label'] );
        if ( $field['required'] ) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        if ( 'textarea' === $field['type'] ) {
            $html .= sprintf(
                '<textarea id="my-cf-%s" name="%s" placeholder="%s" %s rows="5"></textarea>',
                esc_attr( $name ),
                esc_attr( $name ),
                esc_attr( $field['placeholder'] ),
                $required
            );
        } else {
            $html .= sprintf(
                '<input type="%s" id="my-cf-%s" name="%s" placeholder="%s" %s>',
                esc_attr( $field['type'] ),
                esc_attr( $name ),
                esc_attr( $name ),
                esc_attr( $field['placeholder'] ),
                $required
            );
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Xử lý khi form submit
     */
    public function handle_submission() {
        check_ajax_referer( 'my_contact_form_nonce', 'nonce' );

        $data = array(
            'name'    => sanitize_text_field( $_POST['name'] ?? '' ),
            'email'   => sanitize_email( $_POST['email'] ?? '' ),
            'phone'   => sanitize_text_field( $_POST['phone'] ?? '' ),
            'message' => sanitize_textarea_field( $_POST['message'] ?? '' ),
        );

        // Filter: Cho phép thêm/sửa data trước khi validate
        $data = apply_filters( 'my_contact_form_submission_data', $data );

        // Filter: Custom validation
        $errors = apply_filters( 'my_contact_form_validate', array(), $data );

        // Validation mặc định
        if ( empty( $data['name'] ) ) {
            $errors[] = 'Vui lòng nhập họ tên.';
        }
        if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
            $errors[] = 'Email không hợp lệ.';
        }
        if ( empty( $data['message'] ) ) {
            $errors[] = 'Vui lòng nhập nội dung.';
        }

        if ( ! empty( $errors ) ) {
            // Action: Khi validation thất bại
            do_action( 'my_contact_form_validation_failed', $errors, $data );
            wp_send_json_error( array( 'errors' => $errors ) );
        }

        // Action: Trước khi lưu
        do_action( 'my_contact_form_before_save', $data );

        // Lưu vào database
        $post_id = wp_insert_post( array(
            'post_type'    => 'contact_submission',
            'post_title'   => 'Liên hệ từ ' . $data['name'],
            'post_content' => $data['message'],
            'post_status'  => 'private',
        ));

        foreach ( $data as $key => $value ) {
            update_post_meta( $post_id, '_cf_' . $key, $value );
        }

        // Gửi email
        $admin_email = apply_filters( 'my_contact_form_admin_email', get_option( 'admin_email' ) );
        $subject     = apply_filters( 'my_contact_form_email_subject', 'Liên hệ mới từ ' . $data['name'], $data );
        $message     = apply_filters( 'my_contact_form_email_body', sprintf(
            "Họ tên: %s\nEmail: %s\nSĐT: %s\n\nNội dung:\n%s",
            $data['name'], $data['email'], $data['phone'], $data['message']
        ), $data );

        wp_mail( $admin_email, $subject, $message );

        // Action: Sau khi submit thành công
        do_action( 'my_contact_form_after_submit', $post_id, $data );

        // Filter: Customize success message
        $success_message = apply_filters(
            'my_contact_form_success_message',
            'Cảm ơn bạn! Tin nhắn đã được gửi thành công.',
            $data
        );

        wp_send_json_success( array( 'message' => $success_message ) );
    }
}

new My_Contact_Form();
```

### Plugin khác mở rộng Contact Form

```php
<?php
/**
 * Plugin Name: My Contact Form - Extra Fields
 * Description: Thêm trường cho Contact Form plugin
 */

// Thêm trường "Subject"
add_filter( 'my_contact_form_fields', function( $fields ) {
    // Chèn trường 'subject' sau 'email'
    $new_fields = array();
    foreach ( $fields as $key => $field ) {
        $new_fields[ $key ] = $field;
        if ( 'email' === $key ) {
            $new_fields['subject'] = array(
                'type'        => 'select',
                'label'       => 'Chủ đề',
                'required'    => true,
                'options'     => array(
                    ''          => '-- Chọn chủ đề --',
                    'support'   => 'Hỗ trợ kỹ thuật',
                    'sales'     => 'Tư vấn mua hàng',
                    'feedback'  => 'Góp ý',
                    'other'     => 'Khác',
                ),
            );
        }
    }
    return $new_fields;
});

// Thêm CAPTCHA trước nút submit
add_action( 'my_contact_form_before_submit', function() {
    echo '<div class="my-cf-captcha">';
    echo '<label>Captcha: Bao nhiêu là 3 + 4? <span class="required">*</span></label>';
    echo '<input type="text" name="captcha" required>';
    echo '</div>';
});

// Validate CAPTCHA
add_filter( 'my_contact_form_validate', function( $errors, $data ) {
    $captcha = sanitize_text_field( $_POST['captcha'] ?? '' );
    if ( '7' !== $captcha ) {
        $errors[] = 'Captcha không đúng.';
    }
    return $errors;
}, 10, 2 );

// Ghi log sau khi submit
add_action( 'my_contact_form_after_submit', function( $post_id, $data ) {
    error_log( sprintf(
        '[Contact Form] Submission #%d from %s (%s)',
        $post_id,
        $data['name'],
        $data['email']
    ));
}, 10, 2 );
```

---

## 8. Hook trong Theme

### Parent Theme tạo hooks cho Child Theme

```php
<?php
// File: themes/parent-theme/functions.php

// === HEADER HOOKS ===
function parent_theme_header() {
    do_action( 'parent_theme_before_header' );
    ?>
    <header id="site-header" class="site-header">
        <?php do_action( 'parent_theme_header_start' ); ?>

        <div class="site-branding">
            <?php
            // Filter: Cho phép child theme thay đổi logo
            $logo_html = apply_filters( 'parent_theme_logo', '' );
            if ( $logo_html ) {
                echo $logo_html;
            } else {
                the_custom_logo();
            }
            ?>
        </div>

        <nav class="main-navigation">
            <?php
            do_action( 'parent_theme_before_nav' );

            wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
            ));

            do_action( 'parent_theme_after_nav' );
            ?>
        </nav>

        <?php do_action( 'parent_theme_header_end' ); ?>
    </header>
    <?php
    do_action( 'parent_theme_after_header' );
}

// === CONTENT HOOKS ===
function parent_theme_content_area() {
    do_action( 'parent_theme_before_content' );

    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();

            do_action( 'parent_theme_before_post' );

            echo '<article id="post-' . get_the_ID() . '" class="' . esc_attr( implode( ' ', get_post_class() ) ) . '">';

            // Filter: Cho phép thay đổi template hiển thị
            $template_part = apply_filters( 'parent_theme_post_template', 'template-parts/content', get_post_type() );
            get_template_part( $template_part, get_post_type() );

            echo '</article>';

            do_action( 'parent_theme_after_post' );
        }
    }

    do_action( 'parent_theme_after_content' );
}

// === FOOTER HOOKS ===
function parent_theme_footer() {
    do_action( 'parent_theme_before_footer' );
    ?>
    <footer id="site-footer" class="site-footer">
        <?php do_action( 'parent_theme_footer_start' ); ?>

        <div class="footer-widgets">
            <?php
            // Filter: Số cột widget trong footer
            $footer_columns = apply_filters( 'parent_theme_footer_columns', 3 );
            for ( $i = 1; $i <= $footer_columns; $i++ ) {
                echo '<div class="footer-column">';
                dynamic_sidebar( 'footer-' . $i );
                echo '</div>';
            }
            ?>
        </div>

        <div class="site-info">
            <?php
            $credits = apply_filters( 'parent_theme_credits',
                sprintf( '&copy; %d %s', date( 'Y' ), get_bloginfo( 'name' ) )
            );
            echo wp_kses_post( $credits );
            ?>
        </div>

        <?php do_action( 'parent_theme_footer_end' ); ?>
    </footer>
    <?php
    do_action( 'parent_theme_after_footer' );
}
```

### Child Theme sử dụng hooks

```php
<?php
// File: themes/child-theme/functions.php

// Thêm banner thông báo trước header
add_action( 'parent_theme_before_header', function() {
    echo '<div class="announcement-bar" style="background:#ffeb3b; text-align:center; padding:10px;">';
    echo 'Miễn phí vận chuyển cho đơn hàng trên 500.000 VNĐ!';
    echo '</div>';
});

// Thêm nút search vào navigation
add_action( 'parent_theme_after_nav', function() {
    echo '<div class="nav-search">';
    get_search_form();
    echo '</div>';
});

// Thay đổi số cột footer
add_filter( 'parent_theme_footer_columns', function() {
    return 4; // 4 cột thay vì 3
});

// Thay đổi credit text
add_filter( 'parent_theme_credits', function() {
    return sprintf(
        '&copy; %d %s. Thiết kế bởi <a href="https://example.com">My Agency</a>',
        date( 'Y' ),
        get_bloginfo( 'name' )
    );
});

// Thêm breadcrumb trước nội dung
add_action( 'parent_theme_before_content', function() {
    if ( ! is_front_page() ) {
        echo '<nav class="breadcrumb">';
        echo '<a href="' . esc_url( home_url() ) . '">Trang chủ</a>';
        if ( is_single() ) {
            $categories = get_the_category();
            if ( $categories ) {
                echo ' &raquo; <a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">';
                echo esc_html( $categories[0]->name ) . '</a>';
            }
            echo ' &raquo; ' . esc_html( get_the_title() );
        } elseif ( is_page() ) {
            echo ' &raquo; ' . esc_html( get_the_title() );
        } elseif ( is_category() ) {
            echo ' &raquo; ' . esc_html( single_cat_title( '', false ) );
        }
        echo '</nav>';
    }
});

// Thêm social share buttons sau mỗi bài viết
add_action( 'parent_theme_after_post', function() {
    if ( ! is_single() ) {
        return;
    }

    $url   = urlencode( get_permalink() );
    $title = urlencode( get_the_title() );

    echo '<div class="social-share">';
    echo '<span>Chia sẻ: </span>';
    echo '<a href="https://facebook.com/sharer/sharer.php?u=' . $url . '" target="_blank">Facebook</a> | ';
    echo '<a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '" target="_blank">Twitter</a> | ';
    echo '<a href="https://www.linkedin.com/shareArticle?mini=true&url=' . $url . '" target="_blank">LinkedIn</a>';
    echo '</div>';
});
```

---

## 9. Design Pattern: Observer Pattern

### Observer Pattern trong WordPress

```
┌─────────────────┐
│   Subject        │     do_action('event')
│   (WordPress)    │────────────────────────────────┐
│                  │                                 │
└─────────────────┘                                 │
                                                     ▼
                                          ┌──────────────────┐
                                          │ Observer Manager  │
                                          │ ($wp_filter)      │
                                          └──────────────────┘
                                                     │
                              ┌───────────────────────┼───────────────────────┐
                              ▼                       ▼                       ▼
                    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
                    │  Observer 1      │    │  Observer 2      │    │  Observer 3      │
                    │  (Plugin A)      │    │  (Plugin B)      │    │  (Theme)         │
                    │  add_action()    │    │  add_action()    │    │  add_action()    │
                    └─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Implement Observer Pattern rõ ràng

```php
<?php
/**
 * Plugin Name: My Event System
 * Description: Observer Pattern rõ ràng trong WordPress
 */

/**
 * Event Manager - Quản lý tất cả events trong plugin
 * Sử dụng WordPress hooks nhưng organize code theo pattern rõ ràng
 */
class My_Event_Manager {

    /**
     * Danh sách tất cả events plugin support
     */
    const EVENTS = array(
        // User events
        'my_app_user_registered'   => 'Khi user đăng ký mới',
        'my_app_user_logged_in'    => 'Khi user đăng nhập',
        'my_app_user_profile_updated' => 'Khi user cập nhật profile',

        // Order events
        'my_app_order_created'     => 'Khi đơn hàng được tạo',
        'my_app_order_paid'        => 'Khi đơn hàng được thanh toán',
        'my_app_order_shipped'     => 'Khi đơn hàng được gửi đi',
        'my_app_order_completed'   => 'Khi đơn hàng hoàn thành',
        'my_app_order_cancelled'   => 'Khi đơn hàng bị hủy',

        // Product events
        'my_app_product_created'   => 'Khi sản phẩm mới được tạo',
        'my_app_product_out_of_stock' => 'Khi sản phẩm hết hàng',
    );

    /**
     * Dispatch một event
     */
    public static function dispatch( $event_name, ...$args ) {
        if ( ! array_key_exists( $event_name, self::EVENTS ) ) {
            _doing_it_wrong( __METHOD__, "Event '{$event_name}' không được đăng ký.", '1.0.0' );
            return;
        }

        do_action( $event_name, ...$args );
    }

    /**
     * Đăng ký listener cho event
     */
    public static function listen( $event_name, $callback, $priority = 10, $accepted_args = 1 ) {
        add_action( $event_name, $callback, $priority, $accepted_args );
    }
}

// === SỬ DỤNG ===

// Dispatch events (trong business logic)
class My_Order_Service {

    public function create_order( $data ) {
        // Xử lý tạo đơn hàng...
        $order_id = 123;

        // Phát event
        My_Event_Manager::dispatch( 'my_app_order_created', $order_id, $data );

        return $order_id;
    }

    public function complete_order( $order_id ) {
        // Cập nhật trạng thái...
        update_post_meta( $order_id, '_status', 'completed' );

        // Phát event
        My_Event_Manager::dispatch( 'my_app_order_completed', $order_id );
    }
}

// Đăng ký listeners (trong plugin init)
class My_Email_Listener {

    public function __construct() {
        My_Event_Manager::listen( 'my_app_order_created', array( $this, 'send_order_confirmation' ), 10, 2 );
        My_Event_Manager::listen( 'my_app_order_shipped', array( $this, 'send_shipping_notification' ) );
        My_Event_Manager::listen( 'my_app_user_registered', array( $this, 'send_welcome_email' ) );
    }

    public function send_order_confirmation( $order_id, $data ) {
        wp_mail( $data['email'], 'Xác nhận đơn hàng #' . $order_id, '...' );
    }

    public function send_shipping_notification( $order_id ) {
        // Gửi email thông báo vận chuyển
    }

    public function send_welcome_email( $user_id ) {
        $user = get_userdata( $user_id );
        wp_mail( $user->user_email, 'Chào mừng!', '...' );
    }
}

class My_Analytics_Listener {

    public function __construct() {
        My_Event_Manager::listen( 'my_app_order_created', array( $this, 'track_order' ), 20, 2 );
        My_Event_Manager::listen( 'my_app_user_registered', array( $this, 'track_registration' ) );
    }

    public function track_order( $order_id, $data ) {
        error_log( "[Analytics] New order #{$order_id}" );
    }

    public function track_registration( $user_id ) {
        error_log( "[Analytics] New user #{$user_id}" );
    }
}

// Init listeners
add_action( 'plugins_loaded', function() {
    new My_Email_Listener();
    new My_Analytics_Listener();
});
```

---

## 10. Code ví dụ: Plugin hoàn chỉnh với Custom Hooks

```php
<?php
/**
 * Plugin Name: My Booking System
 * Description: Hệ thống đặt lịch với custom hooks cho extensibility
 * Version: 1.0.0
 * Author: Developer
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin Class
 */
class My_Booking_System {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'wp_ajax_my_booking_submit', array( $this, 'handle_booking' ) );
        add_action( 'wp_ajax_nopriv_my_booking_submit', array( $this, 'handle_booking' ) );
        add_shortcode( 'booking_form', array( $this, 'booking_form_shortcode' ) );
    }

    /**
     * Đăng ký CPT cho bookings
     */
    public function register_post_type() {
        $labels = apply_filters( 'my_booking_post_type_labels', array(
            'name'          => 'Đặt lịch',
            'singular_name' => 'Lịch hẹn',
            'add_new'       => 'Thêm lịch hẹn',
        ));

        $args = apply_filters( 'my_booking_post_type_args', array(
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-calendar-alt',
            'supports'     => array( 'title' ),
        ));

        register_post_type( 'booking', $args );
    }

    /**
     * Shortcode render form đặt lịch
     */
    public function booking_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'service' => '',
        ), $atts, 'booking_form' );

        // Filter: Danh sách dịch vụ
        $services = apply_filters( 'my_booking_services', array(
            'consultation' => 'Tư vấn (30 phút) - Miễn phí',
            'basic'        => 'Gói cơ bản (1 giờ) - 500.000 VNĐ',
            'premium'      => 'Gói premium (2 giờ) - 1.000.000 VNĐ',
        ));

        // Filter: Khung giờ
        $time_slots = apply_filters( 'my_booking_time_slots', array(
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '14:00', '14:30', '15:00', '15:30', '16:00', '16:30',
        ));

        ob_start();

        /**
         * Fires before the booking form.
         *
         * @since 1.0.0
         * @param array $atts Shortcode attributes.
         */
        do_action( 'my_booking_before_form', $atts );

        ?>
        <form id="my-booking-form" class="my-booking-form">
            <?php wp_nonce_field( 'my_booking_nonce', 'booking_nonce' ); ?>

            <?php do_action( 'my_booking_form_start' ); ?>

            <div class="booking-field">
                <label for="booking-name">Họ và tên *</label>
                <input type="text" id="booking-name" name="customer_name" required>
            </div>

            <div class="booking-field">
                <label for="booking-email">Email *</label>
                <input type="email" id="booking-email" name="customer_email" required>
            </div>

            <div class="booking-field">
                <label for="booking-phone">Số điện thoại *</label>
                <input type="tel" id="booking-phone" name="customer_phone" required>
            </div>

            <?php
            /**
             * Fires after the customer info fields.
             * Dùng để thêm trường tùy chỉnh.
             *
             * @since 1.0.0
             */
            do_action( 'my_booking_after_customer_fields' );
            ?>

            <div class="booking-field">
                <label for="booking-service">Dịch vụ *</label>
                <select id="booking-service" name="service" required>
                    <option value="">-- Chọn dịch vụ --</option>
                    <?php foreach ( $services as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"
                            <?php selected( $atts['service'], $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="booking-field">
                <label for="booking-date">Ngày *</label>
                <input type="date" id="booking-date" name="booking_date" required
                       min="<?php echo date( 'Y-m-d', strtotime( '+1 day' ) ); ?>">
            </div>

            <div class="booking-field">
                <label for="booking-time">Giờ *</label>
                <select id="booking-time" name="booking_time" required>
                    <option value="">-- Chọn giờ --</option>
                    <?php foreach ( $time_slots as $slot ) : ?>
                        <option value="<?php echo esc_attr( $slot ); ?>">
                            <?php echo esc_html( $slot ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="booking-field">
                <label for="booking-notes">Ghi chú</label>
                <textarea id="booking-notes" name="notes" rows="3"></textarea>
            </div>

            <?php do_action( 'my_booking_before_submit' ); ?>

            <button type="submit" class="booking-submit">
                <?php echo esc_html( apply_filters( 'my_booking_submit_text', 'Đặt lịch' ) ); ?>
            </button>

            <?php do_action( 'my_booking_form_end' ); ?>
        </form>
        <div id="booking-result"></div>
        <?php

        do_action( 'my_booking_after_form', $atts );

        return ob_get_clean();
    }

    /**
     * Xử lý booking submission
     */
    public function handle_booking() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['booking_nonce'] ?? '', 'my_booking_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Yêu cầu không hợp lệ.' ) );
        }

        // Thu thập dữ liệu
        $data = array(
            'customer_name'  => sanitize_text_field( $_POST['customer_name'] ?? '' ),
            'customer_email' => sanitize_email( $_POST['customer_email'] ?? '' ),
            'customer_phone' => sanitize_text_field( $_POST['customer_phone'] ?? '' ),
            'service'        => sanitize_text_field( $_POST['service'] ?? '' ),
            'booking_date'   => sanitize_text_field( $_POST['booking_date'] ?? '' ),
            'booking_time'   => sanitize_text_field( $_POST['booking_time'] ?? '' ),
            'notes'          => sanitize_textarea_field( $_POST['notes'] ?? '' ),
        );

        /**
         * Filters the booking data before validation.
         *
         * @since 1.0.0
         * @param array $data Booking data.
         * @return array Modified booking data.
         */
        $data = apply_filters( 'my_booking_submission_data', $data );

        // Validation
        $errors = array();

        if ( empty( $data['customer_name'] ) ) {
            $errors[] = 'Vui lòng nhập họ tên.';
        }
        if ( ! is_email( $data['customer_email'] ) ) {
            $errors[] = 'Email không hợp lệ.';
        }
        if ( empty( $data['customer_phone'] ) ) {
            $errors[] = 'Vui lòng nhập số điện thoại.';
        }

        /**
         * Filters the validation errors.
         *
         * @since 1.0.0
         * @param array $errors Danh sách lỗi.
         * @param array $data   Booking data.
         * @return array Modified errors.
         */
        $errors = apply_filters( 'my_booking_validation_errors', $errors, $data );

        if ( ! empty( $errors ) ) {
            do_action( 'my_booking_validation_failed', $errors, $data );
            wp_send_json_error( array( 'errors' => $errors ) );
        }

        // Kiểm tra slot còn trống
        $is_available = $this->check_availability( $data['booking_date'], $data['booking_time'] );

        /**
         * Filters whether the time slot is available.
         *
         * @since 1.0.0
         */
        $is_available = apply_filters( 'my_booking_is_available', $is_available, $data );

        if ( ! $is_available ) {
            do_action( 'my_booking_slot_unavailable', $data );
            wp_send_json_error( array( 'message' => 'Khung giờ này đã được đặt. Vui lòng chọn giờ khác.' ) );
        }

        /**
         * Fires before saving the booking.
         *
         * @since 1.0.0
         * @param array $data Booking data.
         */
        do_action( 'my_booking_before_save', $data );

        // Lưu booking
        $booking_id = wp_insert_post( array(
            'post_type'   => 'booking',
            'post_title'  => sprintf( '%s - %s %s', $data['customer_name'], $data['booking_date'], $data['booking_time'] ),
            'post_status' => 'publish',
        ));

        if ( is_wp_error( $booking_id ) ) {
            wp_send_json_error( array( 'message' => 'Có lỗi xảy ra.' ) );
        }

        // Lưu meta
        foreach ( $data as $key => $value ) {
            update_post_meta( $booking_id, '_booking_' . $key, $value );
        }
        update_post_meta( $booking_id, '_booking_status', 'pending' );

        /**
         * Fires after a booking is successfully created.
         *
         * @since 1.0.0
         * @param int   $booking_id Booking post ID.
         * @param array $data       Booking data.
         */
        do_action( 'my_booking_created', $booking_id, $data );

        // Gửi email xác nhận
        $this->send_confirmation_email( $booking_id, $data );

        $success_message = apply_filters(
            'my_booking_success_message',
            sprintf(
                'Đặt lịch thành công! Mã đặt lịch: #%d. Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.',
                $booking_id
            ),
            $booking_id,
            $data
        );

        wp_send_json_success( array(
            'message'    => $success_message,
            'booking_id' => $booking_id,
        ));
    }

    private function check_availability( $date, $time ) {
        $existing = get_posts( array(
            'post_type'  => 'booking',
            'meta_query' => array(
                'relation' => 'AND',
                array( 'key' => '_booking_booking_date', 'value' => $date ),
                array( 'key' => '_booking_booking_time', 'value' => $time ),
                array( 'key' => '_booking_status', 'value' => array( 'pending', 'confirmed' ), 'compare' => 'IN' ),
            ),
        ));
        return empty( $existing );
    }

    private function send_confirmation_email( $booking_id, $data ) {
        $to      = $data['customer_email'];
        $subject = apply_filters( 'my_booking_email_subject', 'Xác nhận đặt lịch #' . $booking_id, $booking_id, $data );
        $message = apply_filters( 'my_booking_email_body', sprintf(
            "Xin chào %s,\n\nĐặt lịch của bạn đã được tiếp nhận.\n\n" .
            "Mã đặt lịch: #%d\nDịch vụ: %s\nNgày: %s\nGiờ: %s\n\n" .
            "Chúng tôi sẽ liên hệ xác nhận sớm.\n\nTrân trọng!",
            $data['customer_name'], $booking_id, $data['service'],
            $data['booking_date'], $data['booking_time']
        ), $booking_id, $data );

        wp_mail( $to, $subject, $message );

        // Gửi email cho admin
        $admin_email = apply_filters( 'my_booking_admin_email', get_option( 'admin_email' ) );
        wp_mail( $admin_email, 'Đặt lịch mới #' . $booking_id, $message );
    }
}

// Init plugin
My_Booking_System::get_instance();
```

---

## 11. Best Practices

### 1. Tạo hooks ở mọi "điểm quan trọng"

```php
<?php
// Mỗi function quan trọng nên có 3 loại hooks:
function my_important_process( $data ) {
    // 1. Filter DATA trước khi xử lý
    $data = apply_filters( 'my_plugin_pre_process_data', $data );

    // 2. Action TRƯỚC khi xử lý
    do_action( 'my_plugin_before_process', $data );

    // ... Xử lý chính ...
    $result = do_something( $data );

    // 3. Action SAU khi xử lý
    do_action( 'my_plugin_after_process', $result, $data );

    // 4. Filter KẾT QUẢ trước khi return
    return apply_filters( 'my_plugin_process_result', $result, $data );
}
```

### 2. Document tất cả custom hooks

```php
<?php
// Tạo file riêng: hooks-reference.md hoặc PHPDoc blocks
// Giúp developer khác biết plugin có những hooks nào

/**
 * HOOKS REFERENCE:
 *
 * Actions:
 * - my_plugin_before_save      : Trước khi lưu dữ liệu
 * - my_plugin_after_save       : Sau khi lưu dữ liệu
 * - my_plugin_on_delete        : Khi xóa dữ liệu
 *
 * Filters:
 * - my_plugin_data             : Filter dữ liệu trước khi lưu
 * - my_plugin_query_args       : Filter tham số query
 * - my_plugin_output           : Filter output HTML
 */
```

### 3. Truyền đủ context trong tham số

```php
<?php
// SAI: Không đủ thông tin cho callbacks
do_action( 'my_plugin_saved', $id );

// ĐÚNG: Truyền đủ context
do_action( 'my_plugin_saved', $id, $data, $old_data, $user_id );

// ĐÚNG: Hoặc truyền object chứa tất cả thông tin
do_action( 'my_plugin_saved', array(
    'id'       => $id,
    'data'     => $data,
    'old_data' => $old_data,
    'user_id'  => get_current_user_id(),
    'context'  => 'admin_save',
));
```

### 4. Không thay đổi hook signature giữa các version

```php
<?php
// Version 1.0: 2 tham số
do_action( 'my_plugin_saved', $id, $data );

// Version 2.0: THÊM tham số (backward compatible)
do_action( 'my_plugin_saved', $id, $data, $extra_info );
// Callbacks cũ (chỉ nhận 2 tham số) vẫn hoạt động!

// ĐỪNG LÀM: Thay đổi thứ tự hoặc ý nghĩa tham số
// do_action( 'my_plugin_saved', $data, $id ); // BREAKING CHANGE!
```

### 5. Sử dụng apply_filters() cho mọi giá trị configurable

```php
<?php
// Thay vì hardcode
$per_page = 10;

// Dùng filter để cho phép customize
$per_page = apply_filters( 'my_plugin_items_per_page', 10 );

// Thay vì hardcode template
$template = plugin_dir_path( __FILE__ ) . 'templates/list.php';

// Dùng filter
$template = apply_filters( 'my_plugin_list_template', plugin_dir_path( __FILE__ ) . 'templates/list.php' );
```

---

> **Tiếp theo:** [06 - Hooks Trong Plugin](06-hooks-trong-plugin.md) - Best practices dùng hooks khi phát triển plugin.
