# Bài 9: Hooks Chuyên Đề - WooCommerce, User, REST API, Performance, Dashboard

> **Tổng hợp hooks nâng cao** theo từng chủ đề chuyên biệt: WooCommerce, đăng ký người dùng,
> REST API, tối ưu hiệu năng, và tùy chỉnh Dashboard. Tất cả ví dụ đều copy-paste được ngay.

---

## Mục Lục

1. [WooCommerce Hooks](#1-woocommerce-hooks)
2. [User Registration & Authentication Hooks](#2-user-registration--authentication-hooks)
3. [REST API Hooks](#3-rest-api-hooks)
4. [Performance Optimization Hooks](#4-performance-optimization-hooks)
5. [Dashboard Customization Hooks](#5-dashboard-customization-hooks)
6. [Advanced Hook Patterns](#6-advanced-hook-patterns)

---

## 1. WooCommerce Hooks

### 1.1. woocommerce_before_cart / woocommerce_after_cart

```php
<?php
/**
 * Hook: woocommerce_before_cart
 * Thời điểm: Ngay trước khi nội dung giỏ hàng được render
 * Dùng khi: Hiển thị thông báo, banner khuyến mãi, warning
 */
add_action( 'woocommerce_before_cart', 'my_cart_notice' );

function my_cart_notice() {
    // Tính tổng giỏ hàng hiện tại
    $cart_total = WC()->cart->get_cart_contents_total();

    // Ngưỡng miễn phí vận chuyển
    $free_shipping_threshold = 500000; // 500,000 VNĐ
    $remaining = $free_shipping_threshold - $cart_total;

    if ( $cart_total > 0 && $remaining > 0 ) {
        wc_print_notice(
            sprintf(
                'Mua thêm <strong>%s</strong> để được <strong>miễn phí vận chuyển</strong>!',
                wc_price( $remaining )
            ),
            'notice' // 'notice', 'error', 'success'
        );
    } elseif ( $cart_total >= $free_shipping_threshold ) {
        wc_print_notice(
            'Bạn đã đủ điều kiện <strong>miễn phí vận chuyển</strong>!',
            'success'
        );
    }
}

/**
 * Hook: woocommerce_after_cart
 * Thời điểm: Sau khi toàn bộ giỏ hàng đã render xong
 * Dùng khi: Thêm upsell, cross-sell, trust badges
 */
add_action( 'woocommerce_after_cart', 'my_cart_trust_badges' );

function my_cart_trust_badges() {
    echo '<div class="cart-trust-badges">';
    echo '<span class="badge"><i class="icon-lock"></i> Thanh toán bảo mật SSL</span>';
    echo '<span class="badge"><i class="icon-truck"></i> Giao hàng toàn quốc</span>';
    echo '<span class="badge"><i class="icon-return"></i> Đổi trả trong 30 ngày</span>';
    echo '</div>';
}
```

### 1.2. woocommerce_before_cart_contents / woocommerce_after_cart_contents

```php
<?php
/**
 * Hook: woocommerce_before_cart_contents
 * Vị trí: Ngay trước danh sách sản phẩm trong giỏ hàng
 */
add_action( 'woocommerce_before_cart_contents', 'my_cart_reservation_notice' );

function my_cart_reservation_notice() {
    // Đếm sản phẩm có số lượng giới hạn trong giỏ
    $limited_items = array();
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = $cart_item['data'];
        $stock   = $product->get_stock_quantity();

        // Cảnh báo nếu tồn kho thấp (dưới 5 cái)
        if ( $product->managing_stock() && $stock !== null && $stock <= 5 ) {
            $limited_items[] = $product->get_name();
        }
    }

    if ( ! empty( $limited_items ) ) {
        echo '<tr class="cart-stock-warning"><td colspan="6">';
        echo '<div class="woocommerce-info">';
        echo '<strong>Lưu ý:</strong> Các sản phẩm sau có số lượng tồn kho giới hạn: ';
        echo esc_html( implode( ', ', $limited_items ) );
        echo '</div></td></tr>';
    }
}
```

### 1.3. woocommerce_checkout_process - Validate checkout

```php
<?php
/**
 * Hook: woocommerce_checkout_process
 * Thời điểm: Khi form checkout được submit, trước khi tạo order
 * Dùng khi: Validate custom fields, kiểm tra điều kiện đặc biệt
 *
 * LƯU Ý: Dùng wc_add_notice() để thêm lỗi, KHÔNG dùng return false
 */
add_action( 'woocommerce_checkout_process', 'my_checkout_validate_fields' );

function my_checkout_validate_fields() {
    // Validate số điện thoại Việt Nam (bắt đầu bằng 0, 10 số)
    $phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( $_POST['billing_phone'] ) : '';

    if ( ! empty( $phone ) && ! preg_match( '/^(0[3|5|7|8|9])[0-9]{8}$/', $phone ) ) {
        wc_add_notice(
            'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (VD: 0901234567).',
            'error'
        );
    }

    // Validate custom field "Mã giảm giá công ty" (chỉ chữ và số, tối đa 10 ký tự)
    $company_code = isset( $_POST['billing_company_code'] ) ? sanitize_text_field( $_POST['billing_company_code'] ) : '';
    if ( ! empty( $company_code ) && ! preg_match( '/^[A-Za-z0-9]{4,10}$/', $company_code ) ) {
        wc_add_notice(
            'Mã công ty phải gồm 4-10 ký tự chữ và số.',
            'error'
        );
    }

    // Kiểm tra điều kiện tuổi tối thiểu (ví dụ: bán rượu)
    $contains_age_restricted = false;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        // Kiểm tra custom attribute "age_restricted"
        if ( 'yes' === $product->get_attribute( 'age_restricted' ) ) {
            $contains_age_restricted = true;
            break;
        }
    }

    if ( $contains_age_restricted ) {
        $confirm_age = isset( $_POST['confirm_age'] ) ? (bool) $_POST['confirm_age'] : false;
        if ( ! $confirm_age ) {
            wc_add_notice(
                'Bạn phải xác nhận đủ 18 tuổi để mua sản phẩm này.',
                'error'
            );
        }
    }
}

/**
 * Thêm custom checkout field: "Xác nhận tuổi 18+"
 * Hook: woocommerce_review_order_before_submit
 */
add_action( 'woocommerce_review_order_before_submit', 'my_checkout_add_age_confirm' );

function my_checkout_add_age_confirm() {
    // Kiểm tra giỏ hàng có sản phẩm giới hạn tuổi không
    $has_restricted = false;
    foreach ( WC()->cart->get_cart() as $item ) {
        if ( 'yes' === $item['data']->get_attribute( 'age_restricted' ) ) {
            $has_restricted = true;
            break;
        }
    }

    if ( $has_restricted ) {
        echo '<p class="form-row">';
        echo '<label class="woocommerce-form__label checkbox">';
        echo '<input type="checkbox" name="confirm_age" value="1">';
        echo ' Tôi xác nhận tôi đủ 18 tuổi và đồng ý mua sản phẩm có giới hạn tuổi.';
        echo '</label>';
        echo '</p>';
    }
}
```

### 1.4. woocommerce_checkout_order_created - Sau khi tạo order

```php
<?php
/**
 * Hook: woocommerce_checkout_order_created (WC 4.3+)
 * Thay thế cho: woocommerce_checkout_order_processed
 * Thời điểm: Sau khi order được tạo thành công trong database
 * Tham số: WC_Order $order
 */
add_action( 'woocommerce_checkout_order_created', 'my_after_order_created', 10, 1 );

function my_after_order_created( $order ) {
    $order_id = $order->get_id();

    // Lưu thêm thông tin vào order meta
    $company_code = isset( $_POST['billing_company_code'] )
        ? sanitize_text_field( $_POST['billing_company_code'] )
        : '';

    if ( $company_code ) {
        $order->update_meta_data( '_billing_company_code', $company_code );
        $order->save();
    }

    // Ghi log đơn hàng mới
    error_log( sprintf(
        '[My Plugin] Order #%d mới được tạo. Khách: %s (%s). Tổng: %s',
        $order_id,
        $order->get_billing_full_name(),
        $order->get_billing_email(),
        $order->get_formatted_order_total()
    ) );

    // Fire custom action để các module khác xử lý
    do_action( 'my_plugin_order_created', $order_id, $order );
}
```

### 1.5. woocommerce_order_status_changed - Khi trạng thái đơn hàng thay đổi

```php
<?php
/**
 * Hook: woocommerce_order_status_changed
 * Tham số: $order_id (int), $old_status (string), $new_status (string), $order (WC_Order)
 * Thời điểm: Ngay sau khi status order thay đổi
 * VD: 'pending' → 'processing', 'processing' → 'completed'
 */
add_action( 'woocommerce_order_status_changed', 'my_order_status_handler', 10, 4 );

function my_order_status_handler( $order_id, $old_status, $new_status, $order ) {
    // Ghi log mọi thay đổi trạng thái
    error_log( sprintf(
        '[Orders] #%d: %s → %s (Khách: %s)',
        $order_id,
        $old_status,
        $new_status,
        $order->get_billing_email()
    ) );

    // Xử lý theo trạng thái mới
    switch ( $new_status ) {
        case 'processing':
            // Đơn hàng đã thanh toán - gửi SMS xác nhận
            my_send_sms_confirmation( $order );
            break;

        case 'completed':
            // Đơn hàng hoàn thành - tặng điểm thưởng
            my_award_loyalty_points( $order );
            // Gửi email yêu cầu đánh giá sản phẩm sau 3 ngày
            my_schedule_review_request( $order_id );
            break;

        case 'cancelled':
            // Đơn hàng bị hủy - hoàn lại điểm đã dùng
            my_refund_loyalty_points( $order );
            break;

        case 'refunded':
            // Đơn hàng được hoàn tiền - cập nhật kế toán
            my_sync_refund_to_accounting( $order );
            break;
    }

    // Hook cụ thể cho transition: pending → processing
    if ( 'pending' === $old_status && 'processing' === $new_status ) {
        my_on_first_payment_received( $order );
    }
}

function my_send_sms_confirmation( WC_Order $order ) {
    $phone = $order->get_billing_phone();
    if ( ! $phone ) {
        return;
    }
    // Gọi SMS API (ví dụ ESMS, Twilio...)
    // sms_api_send( $phone, "Đơn hàng #{$order->get_id()} đã được xác nhận!" );
}

function my_award_loyalty_points( WC_Order $order ) {
    $user_id = $order->get_user_id();
    if ( ! $user_id ) {
        return; // Khách không đăng nhập không tích điểm
    }

    // Tính điểm: 1 điểm mỗi 10,000 VNĐ
    $points_earned = (int) ( $order->get_total() / 10000 );

    if ( $points_earned > 0 ) {
        $current_points = (int) get_user_meta( $user_id, 'loyalty_points', true );
        update_user_meta( $user_id, 'loyalty_points', $current_points + $points_earned );

        // Thêm order note
        $order->add_order_note( sprintf(
            'Khách hàng được tặng %d điểm thưởng. Tổng điểm hiện tại: %d.',
            $points_earned,
            $current_points + $points_earned
        ) );
    }
}

function my_schedule_review_request( $order_id ) {
    // Đặt lịch gửi email sau 3 ngày
    if ( ! wp_next_scheduled( 'my_send_review_request', array( $order_id ) ) ) {
        wp_schedule_single_event(
            time() + ( 3 * DAY_IN_SECONDS ),
            'my_send_review_request',
            array( $order_id )
        );
    }
}

add_action( 'my_send_review_request', 'my_do_send_review_email', 10, 1 );
function my_do_send_review_email( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order || 'completed' !== $order->get_status() ) {
        return;
    }
    wp_mail(
        $order->get_billing_email(),
        'Bạn cảm thấy thế nào về đơn hàng của mình?',
        'Xin hãy dành 1 phút đánh giá sản phẩm: ' . wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) )
    );
}

function my_on_first_payment_received( WC_Order $order ) {
    // Gửi thông báo Slack cho team
    // slack_notify( "#orders", "New paid order #{$order->get_id()} - {$order->get_formatted_order_total()}" );
}
```

### 1.6. woocommerce_add_to_cart - Khi thêm sản phẩm vào giỏ

```php
<?php
/**
 * Hook: woocommerce_add_to_cart
 * Tham số: $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data
 * Thời điểm: Sau khi sản phẩm được thêm vào giỏ thành công
 */
add_action( 'woocommerce_add_to_cart', 'my_on_add_to_cart', 10, 6 );

function my_on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    // Track sự kiện thêm vào giỏ (analytics)
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return;
    }

    // Lưu dữ liệu tracking vào session (gửi lên frontend sau)
    $tracking_events   = WC()->session->get( 'my_tracking_events', array() );
    $tracking_events[] = array(
        'event'       => 'add_to_cart',
        'product_id'  => $product_id,
        'product_name'=> $product->get_name(),
        'price'       => $product->get_price(),
        'quantity'    => $quantity,
        'timestamp'   => time(),
    );
    WC()->session->set( 'my_tracking_events', $tracking_events );
}

/**
 * Filter: woocommerce_add_to_cart_validation
 * Validate TRƯỚC KHI thêm vào giỏ - trả về false để ngăn chặn
 * Tham số: $passed (bool), $product_id, $quantity, $variation_id, $variations
 */
add_filter( 'woocommerce_add_to_cart_validation', 'my_validate_add_to_cart', 10, 5 );

function my_validate_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
    if ( ! $passed ) {
        return false; // Đã có lỗi từ trước, không cần kiểm tra thêm
    }

    $product = wc_get_product( $product_id );

    // Kiểm tra giới hạn số lượng tối đa mỗi lần mua
    $max_per_order = (int) $product->get_meta( '_max_per_order' );
    if ( $max_per_order > 0 ) {
        // Kiểm tra số lượng hiện có trong giỏ
        $cart_quantity = 0;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( $cart_item['product_id'] === $product_id ) {
                $cart_quantity += $cart_item['quantity'];
            }
        }

        if ( ( $cart_quantity + $quantity ) > $max_per_order ) {
            wc_add_notice(
                sprintf(
                    'Sản phẩm "%s" chỉ có thể mua tối đa %d cái mỗi đơn hàng.',
                    $product->get_name(),
                    $max_per_order
                ),
                'error'
            );
            return false;
        }
    }

    return $passed;
}
```

### 1.7. Filter hooks WooCommerce quan trọng

```php
<?php
/**
 * Filter: woocommerce_product_tabs
 * Thêm/xóa/sắp xếp lại các tab trên trang sản phẩm
 */
add_filter( 'woocommerce_product_tabs', 'my_custom_product_tabs' );

function my_custom_product_tabs( $tabs ) {
    // Xóa tab Reviews nếu không cần
    // unset( $tabs['reviews'] );

    // Thay đổi title và priority của tab mô tả
    $tabs['description']['title']    = 'Chi tiết sản phẩm';
    $tabs['description']['priority'] = 10;

    // Thêm tab mới: Hướng dẫn sử dụng
    $tabs['how_to_use'] = array(
        'title'    => 'Hướng dẫn sử dụng',
        'priority' => 20,
        'callback' => 'my_how_to_use_tab_content',
    );

    // Thêm tab: Chính sách bảo hành
    $tabs['warranty'] = array(
        'title'    => 'Bảo hành',
        'priority' => 30,
        'callback' => 'my_warranty_tab_content',
    );

    return $tabs;
}

function my_how_to_use_tab_content() {
    global $product;
    $how_to_use = $product->get_meta( '_how_to_use' );
    if ( $how_to_use ) {
        echo wp_kses_post( wpautop( $how_to_use ) );
    } else {
        echo '<p>Vui lòng liên hệ để được hướng dẫn chi tiết.</p>';
    }
}

function my_warranty_tab_content() {
    echo '<p><strong>Chính sách bảo hành:</strong></p>';
    echo '<ul>';
    echo '<li>Bảo hành chính hãng 12 tháng</li>';
    echo '<li>Đổi trả trong 30 ngày nếu lỗi sản xuất</li>';
    echo '<li>Hỗ trợ kỹ thuật miễn phí</li>';
    echo '</ul>';
}

/**
 * Filter: woocommerce_email_subject_new_order
 * Tùy chỉnh tiêu đề email thông báo đơn hàng mới gửi cho admin
 */
add_filter( 'woocommerce_email_subject_new_order', 'my_custom_new_order_subject', 10, 2 );

function my_custom_new_order_subject( $subject, $order ) {
    return sprintf(
        '[Đơn hàng mới] #%d - %s - %s',
        $order->get_id(),
        $order->get_billing_full_name(),
        $order->get_formatted_order_total()
    );
}

/**
 * Filter: woocommerce_cart_item_price
 * Tùy chỉnh hiển thị giá trong giỏ hàng
 */
add_filter( 'woocommerce_cart_item_price', 'my_cart_item_price_display', 10, 3 );

function my_cart_item_price_display( $price_html, $cart_item, $cart_item_key ) {
    $product       = $cart_item['data'];
    $regular_price = $product->get_regular_price();
    $sale_price    = $product->get_sale_price();

    // Nếu đang sale, hiển thị giá gốc bị gạch ngang
    if ( $product->is_on_sale() && $regular_price && $sale_price ) {
        return sprintf(
            '<del>%s</del> <ins>%s</ins>',
            wc_price( $regular_price ),
            wc_price( $sale_price )
        );
    }

    return $price_html;
}
```

---

## 2. User Registration & Authentication Hooks

### 2.1. user_register - Sau khi đăng ký thành công

```php
<?php
/**
 * Hook: user_register
 * Tham số: $user_id (int)
 * Thời điểm: Ngay SAU KHI user mới được tạo thành công
 * LƯU Ý: KHÔNG validate ở đây - dùng registration_errors để validate
 */
add_action( 'user_register', 'my_on_user_registered', 10, 1 );

function my_on_user_registered( $user_id ) {
    // Lưu thông tin bổ sung từ form đăng ký
    if ( isset( $_POST['phone'] ) ) {
        update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
    }

    if ( isset( $_POST['referral_code'] ) ) {
        $referral_code = sanitize_text_field( $_POST['referral_code'] );
        update_user_meta( $user_id, 'referral_code_used', $referral_code );

        // Tìm người giới thiệu và tặng hoa hồng
        $referrer = get_users( array(
            'meta_key'   => 'my_referral_code',
            'meta_value' => $referral_code,
            'number'     => 1,
        ) );

        if ( ! empty( $referrer ) ) {
            $referrer_id      = $referrer[0]->ID;
            $current_ref_count = (int) get_user_meta( $referrer_id, 'referral_count', true );
            update_user_meta( $referrer_id, 'referral_count', $current_ref_count + 1 );
        }
    }

    // Tạo mã referral duy nhất cho user mới
    $referral_code = strtoupper( substr( md5( $user_id . time() ), 0, 8 ) );
    update_user_meta( $user_id, 'my_referral_code', $referral_code );

    // Gán điểm chào mừng
    update_user_meta( $user_id, 'loyalty_points', 100 );

    // Ghi log đăng ký
    $user = get_userdata( $user_id );
    error_log( sprintf(
        '[Registration] User mới: ID=%d, Email=%s, IP=%s',
        $user_id,
        $user->user_email,
        sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' )
    ) );

    // Gửi email chào mừng
    my_send_welcome_email( $user_id );

    // Fire custom hook
    do_action( 'my_user_registered', $user_id );
}

function my_send_welcome_email( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $subject = sprintf( 'Chào mừng %s đến với %s!', $user->display_name, get_bloginfo( 'name' ) );
    $message = sprintf(
        "Xin chào %s,\n\n" .
        "Tài khoản của bạn đã được tạo thành công!\n\n" .
        "Email: %s\n" .
        "Tên đăng nhập: %s\n\n" .
        "Đăng nhập ngay: %s\n\n" .
        "Trân trọng,\nĐội ngũ %s",
        $user->display_name,
        $user->user_email,
        $user->user_login,
        wp_login_url(),
        get_bloginfo( 'name' )
    );

    wp_mail( $user->user_email, $subject, $message );
}
```

### 2.2. registration_errors - Validate khi đăng ký

```php
<?php
/**
 * Filter: registration_errors
 * Tham số: $errors (WP_Error), $sanitized_user_login, $user_email
 * Dùng khi: Thêm validation rules khi đăng ký
 * PHẢI return $errors (dù không có lỗi)
 */
add_filter( 'registration_errors', 'my_registration_validation', 10, 3 );

function my_registration_validation( $errors, $sanitized_user_login, $user_email ) {
    // Validate số điện thoại (nếu có field phone trên form)
    if ( isset( $_POST['phone'] ) ) {
        $phone = sanitize_text_field( $_POST['phone'] );
        if ( ! empty( $phone ) && ! preg_match( '/^(0[3|5|7|8|9])[0-9]{8}$/', $phone ) ) {
            $errors->add(
                'phone_invalid',
                '<strong>Lỗi:</strong> Số điện thoại không hợp lệ.'
            );
        }

        // Kiểm tra số điện thoại chưa được dùng
        if ( ! empty( $phone ) ) {
            $existing = get_users( array(
                'meta_key'   => 'phone',
                'meta_value' => $phone,
                'number'     => 1,
            ) );
            if ( ! empty( $existing ) ) {
                $errors->add(
                    'phone_taken',
                    '<strong>Lỗi:</strong> Số điện thoại này đã được đăng ký.'
                );
            }
        }
    }

    // Kiểm tra email domain (chặn các email tạm thời)
    $blocked_domains = array( 'mailinator.com', 'tempmail.com', 'guerrillamail.com' );
    $email_domain    = substr( strrchr( $user_email, '@' ), 1 );
    if ( in_array( $email_domain, $blocked_domains, true ) ) {
        $errors->add(
            'email_blocked',
            '<strong>Lỗi:</strong> Không chấp nhận email tạm thời. Vui lòng dùng email thực.'
        );
    }

    // Kiểm tra mật khẩu đủ mạnh (nếu có field password)
    if ( isset( $_POST['pass1'] ) && ! empty( $_POST['pass1'] ) ) {
        $password = $_POST['pass1'];
        if ( strlen( $password ) < 8 ) {
            $errors->add( 'pass_weak', '<strong>Lỗi:</strong> Mật khẩu phải có ít nhất 8 ký tự.' );
        }
        if ( ! preg_match( '/[A-Z]/', $password ) ) {
            $errors->add( 'pass_no_upper', '<strong>Lỗi:</strong> Mật khẩu phải có ít nhất 1 chữ hoa.' );
        }
        if ( ! preg_match( '/[0-9]/', $password ) ) {
            $errors->add( 'pass_no_number', '<strong>Lỗi:</strong> Mật khẩu phải có ít nhất 1 chữ số.' );
        }
    }

    return $errors; // LUÔN return $errors
}
```

### 2.3. profile_update - Khi user cập nhật profile

```php
<?php
/**
 * Hook: profile_update
 * Tham số: $user_id (int), $old_user_data (WP_User object)
 * Thời điểm: Ngay SAU KHI profile user được cập nhật
 */
add_action( 'profile_update', 'my_on_profile_update', 10, 2 );

function my_on_profile_update( $user_id, $old_user_data ) {
    $new_user_data = get_userdata( $user_id );

    // Kiểm tra nếu email thay đổi
    if ( $old_user_data->user_email !== $new_user_data->user_email ) {
        // Ghi log thay đổi email
        error_log( sprintf(
            '[Profile] User #%d đổi email: %s → %s',
            $user_id,
            $old_user_data->user_email,
            $new_user_data->user_email
        ) );

        // Gửi thông báo đến email cũ
        wp_mail(
            $old_user_data->user_email,
            'Thông báo thay đổi email',
            sprintf(
                "Xin chào %s,\n\nEmail tài khoản của bạn vừa được thay đổi thành: %s\n\nNếu không phải bạn thực hiện, hãy liên hệ ngay với chúng tôi.",
                $old_user_data->display_name,
                $new_user_data->user_email
            )
        );

        // Xóa xác thực email cũ
        delete_user_meta( $user_id, 'email_verified' );
    }

    // Kiểm tra nếu display_name thay đổi
    if ( $old_user_data->display_name !== $new_user_data->display_name ) {
        // Xóa cache avatar nếu có
        wp_cache_delete( 'user_avatar_' . $user_id, 'my_plugin' );
    }

    // Lưu lịch sử thay đổi profile
    $change_history = get_user_meta( $user_id, '_profile_change_history', true );
    if ( ! is_array( $change_history ) ) {
        $change_history = array();
    }

    $change_history[] = array(
        'timestamp'  => current_time( 'mysql' ),
        'old_email'  => $old_user_data->user_email,
        'new_email'  => $new_user_data->user_email,
        'old_name'   => $old_user_data->display_name,
        'new_name'   => $new_user_data->display_name,
        'changed_by' => get_current_user_id(),
    );

    // Giữ tối đa 10 bản ghi gần nhất
    if ( count( $change_history ) > 10 ) {
        $change_history = array_slice( $change_history, -10 );
    }

    update_user_meta( $user_id, '_profile_change_history', $change_history );
}
```

### 2.4. wp_login / wp_logout - Đăng nhập và đăng xuất

```php
<?php
/**
 * Hook: wp_login
 * Tham số: $user_login (string), $user (WP_User)
 * Thời điểm: Sau khi user đăng nhập thành công
 */
add_action( 'wp_login', 'my_on_user_login', 10, 2 );

function my_on_user_login( $user_login, $user ) {
    // Cập nhật thời gian đăng nhập cuối
    update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
    update_user_meta( $user->ID, 'last_login_ip', sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );

    // Đếm số lần đăng nhập
    $login_count = (int) get_user_meta( $user->ID, 'login_count', true );
    update_user_meta( $user->ID, 'login_count', $login_count + 1 );

    // Lưu lịch sử đăng nhập (tối đa 20 bản ghi)
    $login_history = get_user_meta( $user->ID, '_login_history', true );
    if ( ! is_array( $login_history ) ) {
        $login_history = array();
    }

    $login_history[] = array(
        'timestamp' => current_time( 'mysql' ),
        'ip'        => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
        'user_agent'=> sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
    );

    if ( count( $login_history ) > 20 ) {
        $login_history = array_slice( $login_history, -20 );
    }

    update_user_meta( $user->ID, '_login_history', $login_history );

    // Ghi log nếu admin đăng nhập
    if ( in_array( 'administrator', $user->roles, true ) ) {
        error_log( sprintf(
            '[Security] Admin login: %s từ IP %s lúc %s',
            $user_login,
            sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
            current_time( 'Y-m-d H:i:s' )
        ) );
    }

    // Xóa bộ đếm failed login khi đăng nhập thành công
    delete_transient( 'failed_login_' . sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );
}

/**
 * Hook: wp_login_failed
 * Tham số: $username (string), $error (WP_Error)
 * Dùng khi: Đăng nhập thất bại - rate limiting, security logging
 */
add_action( 'wp_login_failed', 'my_on_login_failed', 10, 2 );

function my_on_login_failed( $username, $error ) {
    $ip         = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $cache_key  = 'failed_login_' . $ip;
    $fail_count = (int) get_transient( $cache_key );

    $fail_count++;
    // Khóa IP sau 5 lần thất bại trong 15 phút
    set_transient( $cache_key, $fail_count, 15 * MINUTE_IN_SECONDS );

    error_log( sprintf(
        '[Security] Đăng nhập thất bại lần %d: username=%s, IP=%s',
        $fail_count,
        sanitize_text_field( $username ),
        $ip
    ) );

    // Gửi cảnh báo admin nếu quá 10 lần thất bại
    if ( $fail_count === 10 ) {
        wp_mail(
            get_option( 'admin_email' ),
            '[Cảnh báo bảo mật] Nhiều lần đăng nhập thất bại',
            sprintf(
                "IP %s đã thử đăng nhập thất bại %d lần.\n" .
                "Username: %s\n" .
                "Thời gian: %s",
                $ip,
                $fail_count,
                sanitize_text_field( $username ),
                current_time( 'Y-m-d H:i:s' )
            )
        );
    }
}

/**
 * Filter: authenticate
 * Dùng để chặn đăng nhập theo điều kiện (rate limiting)
 */
add_filter( 'authenticate', 'my_rate_limit_login', 30, 3 );

function my_rate_limit_login( $user, $username, $password ) {
    if ( empty( $username ) || empty( $password ) ) {
        return $user;
    }

    $ip        = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $fail_count = (int) get_transient( 'failed_login_' . $ip );

    if ( $fail_count >= 5 ) {
        return new WP_Error(
            'too_many_attempts',
            sprintf(
                '<strong>Tài khoản tạm thời bị khóa.</strong> Bạn đã thử đăng nhập thất bại %d lần. Vui lòng thử lại sau 15 phút.',
                $fail_count
            )
        );
    }

    return $user;
}

/**
 * Hook: wp_logout
 * Tham số: $user_id (int) - WordPress 5.5+
 * Thời điểm: Trước khi session user bị xóa
 */
add_action( 'wp_logout', 'my_on_user_logout', 10, 1 );

function my_on_user_logout( $user_id ) {
    if ( ! $user_id ) {
        return;
    }

    // Cập nhật thời gian đăng xuất cuối
    update_user_meta( $user_id, 'last_logout', current_time( 'mysql' ) );

    // Xóa session data tùy chỉnh
    delete_user_meta( $user_id, '_active_session_data' );

    // Tính thời gian online (nếu có lưu last_login)
    $last_login = get_user_meta( $user_id, 'last_login', true );
    if ( $last_login ) {
        $duration = time() - strtotime( $last_login );
        $total_online = (int) get_user_meta( $user_id, 'total_online_seconds', true );
        update_user_meta( $user_id, 'total_online_seconds', $total_online + $duration );
    }
}
```

---

## 3. REST API Hooks

### 3.1. rest_api_init - Đăng ký custom endpoints

```php
<?php
/**
 * Hook: rest_api_init
 * Thời điểm: Khi REST API được khởi tạo (trước khi xử lý request)
 * Dùng khi: Đăng ký custom routes, thêm fields vào responses có sẵn
 */
add_action( 'rest_api_init', 'my_register_rest_routes' );

function my_register_rest_routes() {
    // === ENDPOINT 1: Lấy danh sách sản phẩm nổi bật ===
    // GET /wp-json/my-api/v1/featured-products
    register_rest_route(
        'my-api/v1',
        '/featured-products',
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'my_get_featured_products',
            'permission_callback' => '__return_true',          // Public
            'args'                => array(
                'limit' => array(
                    'default'           => 6,
                    'type'              => 'integer',
                    'minimum'           => 1,
                    'maximum'           => 50,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0;
                    },
                ),
                'category' => array(
                    'default'           => '',
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        )
    );

    // === ENDPOINT 2: Submit form liên hệ (yêu cầu nonce) ===
    // POST /wp-json/my-api/v1/contact
    register_rest_route(
        'my-api/v1',
        '/contact',
        array(
            'methods'             => WP_REST_Server::CREATABLE, // POST
            'callback'            => 'my_handle_contact_form',
            'permission_callback' => '__return_true',
            'args'                => array(
                'name'    => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
                'email'   => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ),
                'message' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ),
                'nonce'   => array( 'required' => true, 'type' => 'string' ),
            ),
        )
    );

    // === ENDPOINT 3: Yêu cầu xác thực - Lấy đơn hàng của user ===
    // GET /wp-json/my-api/v1/my-orders
    register_rest_route(
        'my-api/v1',
        '/my-orders',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'my_get_user_orders',
            'permission_callback' => function() {
                // Chỉ cho phép user đã đăng nhập
                return is_user_logged_in();
            },
        )
    );

    // === ENDPOINT 4: Admin only - Xem thống kê ===
    // GET /wp-json/my-api/v1/stats
    register_rest_route(
        'my-api/v1',
        '/stats',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'my_get_stats',
            'permission_callback' => function() {
                // Chỉ admin
                return current_user_can( 'manage_options' );
            },
        )
    );
}

function my_get_featured_products( WP_REST_Request $request ) {
    $limit    = $request->get_param( 'limit' );
    $category = $request->get_param( 'category' );

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'   => '_featured',
                'value' => 'yes',
            ),
        ),
    );

    if ( $category ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $products = get_posts( $args );
    $data     = array();

    foreach ( $products as $product_post ) {
        $product = wc_get_product( $product_post->ID );
        if ( ! $product ) {
            continue;
        }

        $data[] = array(
            'id'           => $product->get_id(),
            'name'         => $product->get_name(),
            'slug'         => $product->get_slug(),
            'price'        => $product->get_price(),
            'regular_price'=> $product->get_regular_price(),
            'sale_price'   => $product->get_sale_price(),
            'is_on_sale'   => $product->is_on_sale(),
            'permalink'    => get_permalink( $product->get_id() ),
            'image'        => get_the_post_thumbnail_url( $product->get_id(), 'medium' ),
            'rating'       => $product->get_average_rating(),
        );
    }

    // Thêm custom headers
    $response = new WP_REST_Response( $data, 200 );
    $response->header( 'X-Total-Count', count( $data ) );
    $response->header( 'Cache-Control', 'public, max-age=300' ); // Cache 5 phút

    return $response;
}

function my_handle_contact_form( WP_REST_Request $request ) {
    // Verify nonce
    $nonce = $request->get_param( 'nonce' );
    if ( ! wp_verify_nonce( $nonce, 'my_api_contact_form' ) ) {
        return new WP_REST_Response(
            array( 'success' => false, 'message' => 'Phiên làm việc không hợp lệ.' ),
            403
        );
    }

    $name    = $request->get_param( 'name' );
    $email   = $request->get_param( 'email' );
    $message = $request->get_param( 'message' );

    if ( ! is_email( $email ) ) {
        return new WP_REST_Response(
            array( 'success' => false, 'message' => 'Email không hợp lệ.' ),
            400
        );
    }

    wp_mail(
        get_option( 'admin_email' ),
        '[Liên hệ] ' . $name,
        "Từ: {$name} ({$email})\n\n{$message}"
    );

    return new WP_REST_Response(
        array( 'success' => true, 'message' => 'Gửi thành công!' ),
        201
    );
}

function my_get_user_orders( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    $orders  = wc_get_orders( array(
        'customer' => $user_id,
        'limit'    => 10,
        'orderby'  => 'date',
        'order'    => 'DESC',
    ) );

    $data = array();
    foreach ( $orders as $order ) {
        $data[] = array(
            'id'       => $order->get_id(),
            'status'   => $order->get_status(),
            'total'    => $order->get_total(),
            'date'     => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
            'items'    => count( $order->get_items() ),
        );
    }

    return new WP_REST_Response( $data, 200 );
}

function my_get_stats( WP_REST_Request $request ) {
    return new WP_REST_Response( array(
        'total_users'   => count_users()['total_users'],
        'total_posts'   => wp_count_posts()->publish,
        'total_orders'  => wc_orders_count( 'processing' ),
        'generated_at'  => current_time( 'mysql' ),
    ), 200 );
}

/**
 * Thêm custom field vào REST API response của Posts
 * Hook: rest_api_init (dùng register_rest_field)
 */
add_action( 'rest_api_init', 'my_register_rest_fields' );

function my_register_rest_fields() {
    // Thêm field 'reading_time' vào /wp-json/wp/v2/posts
    register_rest_field(
        'post',
        'reading_time',
        array(
            'get_callback'    => function( $post_arr ) {
                $content   = get_post_field( 'post_content', $post_arr['id'] );
                $word_count = str_word_count( strip_tags( $content ) );
                // Trung bình đọc 200 từ/phút
                return max( 1, (int) ceil( $word_count / 200 ) );
            },
            'update_callback' => null, // Read-only
            'schema'          => array(
                'description' => 'Estimated reading time in minutes.',
                'type'        => 'integer',
            ),
        )
    );

    // Thêm field 'featured_image_url' vào /wp-json/wp/v2/posts
    register_rest_field(
        array( 'post', 'page' ),
        'featured_image_url',
        array(
            'get_callback' => function( $post_arr ) {
                return get_the_post_thumbnail_url( $post_arr['id'], 'full' ) ?: '';
            },
            'schema' => array(
                'description' => 'Featured image URL.',
                'type'        => 'string',
            ),
        )
    );
}
```

### 3.2. rest_pre_dispatch - Chặn/chỉnh sửa request trước khi xử lý

```php
<?php
/**
 * Filter: rest_pre_dispatch
 * Tham số: $result (null|mixed), $server (WP_REST_Server), $request (WP_REST_Request)
 * Thời điểm: TRƯỚC KHI route handler được gọi
 * Trả về null = tiếp tục xử lý bình thường
 * Trả về WP_Error hoặc WP_REST_Response = ngừng và trả về kết quả đó ngay
 * Dùng khi: Rate limiting, maintenance mode cho API, global authentication
 */
add_filter( 'rest_pre_dispatch', 'my_rest_rate_limiting', 10, 3 );

function my_rest_rate_limiting( $result, $server, $request ) {
    // Chỉ rate limit các route của plugin này
    $route = $request->get_route();
    if ( strpos( $route, '/my-api/' ) === false ) {
        return $result; // Không phải route của mình, bỏ qua
    }

    $ip        = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $cache_key = 'rest_ratelimit_' . md5( $ip );
    $requests  = (int) get_transient( $cache_key );

    if ( $requests >= 60 ) {
        // Quá 60 request/phút
        return new WP_REST_Response(
            array(
                'code'    => 'rate_limit_exceeded',
                'message' => 'Quá nhiều request. Vui lòng thử lại sau 1 phút.',
            ),
            429 // Too Many Requests
        );
    }

    // Tăng bộ đếm
    if ( 0 === $requests ) {
        set_transient( $cache_key, 1, MINUTE_IN_SECONDS );
    } else {
        set_transient( $cache_key, $requests + 1, MINUTE_IN_SECONDS );
    }

    return $result; // null = tiếp tục xử lý
}

/**
 * Filter: rest_pre_dispatch - Maintenance mode cho API
 */
add_filter( 'rest_pre_dispatch', 'my_rest_maintenance_mode', 5, 3 );

function my_rest_maintenance_mode( $result, $server, $request ) {
    // Kiểm tra maintenance mode
    if ( ! get_option( 'my_api_maintenance_mode', false ) ) {
        return $result;
    }

    // Cho phép admin vẫn dùng API khi maintenance
    if ( current_user_can( 'manage_options' ) ) {
        return $result;
    }

    // Trả về lỗi 503 Service Unavailable
    $response = new WP_REST_Response(
        array(
            'code'    => 'service_unavailable',
            'message' => 'API đang bảo trì. Vui lòng thử lại sau.',
        ),
        503
    );
    $response->header( 'Retry-After', '3600' );

    return $response;
}
```

### 3.3. rest_post_dispatch - Xử lý response sau khi dispatch

```php
<?php
/**
 * Filter: rest_post_dispatch
 * Tham số: $result (WP_REST_Response), $server (WP_REST_Server), $request (WP_REST_Request)
 * Thời điểm: SAU KHI route handler đã xử lý xong, trước khi gửi về client
 * Dùng khi: Thêm headers, log response, modify response, CORS handling
 * PHẢI return $result
 */
add_filter( 'rest_post_dispatch', 'my_rest_add_headers', 10, 3 );

function my_rest_add_headers( $result, $server, $request ) {
    // Thêm CORS headers cho các endpoint công khai
    $route = $request->get_route();

    if ( strpos( $route, '/my-api/' ) !== false ) {
        $result->header( 'Access-Control-Allow-Origin', '*' );
        $result->header( 'Access-Control-Allow-Methods', 'GET, POST, OPTIONS' );
        $result->header( 'X-API-Version', '1.0' );
        $result->header( 'X-Request-ID', uniqid( 'req_', true ) );
    }

    return $result;
}

/**
 * Filter: rest_post_dispatch - Ghi log API requests
 */
add_filter( 'rest_post_dispatch', 'my_rest_log_requests', 99, 3 );

function my_rest_log_requests( $result, $server, $request ) {
    // Chỉ log các route của mình
    $route = $request->get_route();
    if ( strpos( $route, '/my-api/' ) === false ) {
        return $result;
    }

    $status  = $result->get_status();
    $method  = $request->get_method();
    $user_id = get_current_user_id();
    $ip      = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );

    error_log( sprintf(
        '[REST API] %s %s → %d | User: %d | IP: %s',
        $method,
        $route,
        $status,
        $user_id,
        $ip
    ) );

    // Log lỗi 4xx và 5xx
    if ( $status >= 400 ) {
        $body = $result->get_data();
        error_log( sprintf(
            '[REST API Error] %d: %s',
            $status,
            is_array( $body ) ? json_encode( $body ) : (string) $body
        ) );
    }

    return $result;
}

/**
 * Filter: rest_post_dispatch - Wrap response theo format chuẩn
 */
add_filter( 'rest_post_dispatch', 'my_rest_wrap_response', 20, 3 );

function my_rest_wrap_response( $result, $server, $request ) {
    $route = $request->get_route();

    // Chỉ wrap các route của plugin
    if ( strpos( $route, '/my-api/v1/' ) === false ) {
        return $result;
    }

    $status = $result->get_status();
    $data   = $result->get_data();

    // Không wrap nếu đã là error format
    if ( isset( $data['code'] ) && isset( $data['message'] ) ) {
        return $result;
    }

    // Wrap theo format chuẩn
    $wrapped = array(
        'success'   => $status < 400,
        'data'      => $data,
        'timestamp' => current_time( 'c' ),
        'version'   => '1.0',
    );

    $result->set_data( $wrapped );
    return $result;
}
```

---

## 4. Performance Optimization Hooks

### 4.1. script_loader_tag - Thêm defer/async cho scripts

```php
<?php
/**
 * Filter: script_loader_tag
 * Tham số: $tag (HTML string), $handle (string), $src (string)
 * Dùng khi: Thêm defer, async, type="module" vào script tags
 * LƯU Ý: Đừng defer các script quan trọng (jQuery, checkout scripts...)
 */
add_filter( 'script_loader_tag', 'my_defer_scripts', 10, 3 );

function my_defer_scripts( $tag, $handle, $src ) {
    // Danh sách scripts KHÔNG được defer (quan trọng, chặn render)
    $no_defer = array(
        'jquery',
        'jquery-core',
        'jquery-migrate',
        'wc-cart',
        'wc-checkout',
        'woocommerce',
        'wc-add-to-cart',
    );

    // Scripts cần async (độc lập, không phụ thuộc DOM)
    $async_scripts = array(
        'google-analytics',
        'facebook-pixel',
        'hotjar',
    );

    // Scripts cần defer (phụ thuộc DOM nhưng không blocking)
    $defer_scripts = array(
        'mytheme-main',
        'mytheme-slider',
        'mytheme-portfolio',
        'my-plugin-frontend',
    );

    // Không xử lý nếu đang trong admin
    if ( is_admin() ) {
        return $tag;
    }

    // Không defer nếu nằm trong danh sách ngoại lệ
    if ( in_array( $handle, $no_defer, true ) ) {
        return $tag;
    }

    // Thêm async
    if ( in_array( $handle, $async_scripts, true ) ) {
        return str_replace( ' src=', ' async src=', $tag );
    }

    // Thêm defer
    if ( in_array( $handle, $defer_scripts, true ) ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }

    return $tag;
}

/**
 * Thêm type="module" cho ES modules (WordPress 6.4+ có native support)
 */
add_filter( 'script_loader_tag', 'my_add_module_type', 10, 3 );

function my_add_module_type( $tag, $handle, $src ) {
    // Danh sách scripts cần type="module"
    $module_scripts = array( 'mytheme-es-module', 'my-plugin-module' );

    if ( in_array( $handle, $module_scripts, true ) ) {
        return str_replace( '<script ', '<script type="module" ', $tag );
    }

    return $tag;
}
```

### 4.2. style_loader_tag - Tối ưu loading CSS

```php
<?php
/**
 * Filter: style_loader_tag
 * Tham số: $html (string), $handle (string), $href (string), $media (string)
 * Dùng khi: Thêm preload, defer non-critical CSS, thêm attributes
 */
add_filter( 'style_loader_tag', 'my_defer_non_critical_css', 10, 4 );

function my_defer_non_critical_css( $html, $handle, $href, $media ) {
    // CSS quan trọng - KHÔNG defer
    $critical_css = array(
        'mytheme-main',    // CSS chính của theme
        'dashicons',       // WordPress dashicons
        'woocommerce-layout',
        'woocommerce-smallscreen',
        'woocommerce-general',
    );

    // CSS không quan trọng - Defer bằng kỹ thuật print/onload
    $non_critical_css = array(
        'mytheme-animations',
        'mytheme-print',
        'font-awesome',
        'my-plugin-admin-bar',
    );

    if ( is_admin() ) {
        return $html;
    }

    if ( in_array( $handle, $critical_css, true ) ) {
        return $html; // Giữ nguyên - load bình thường
    }

    if ( in_array( $handle, $non_critical_css, true ) ) {
        // Kỹ thuật defer CSS: Load as print, chuyển thành all sau khi trang load xong
        // Tương thích với tất cả browsers, không blocking
        return sprintf(
            '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' .
            '<noscript><link rel="stylesheet" href="%s"></noscript>' . "\n",
            esc_url( $href ),
            esc_url( $href )
        );
    }

    return $html;
}

/**
 * Thêm preload hints cho critical CSS
 */
add_filter( 'style_loader_tag', 'my_preload_critical_css', 5, 4 );

function my_preload_critical_css( $html, $handle, $href, $media ) {
    $preload_handles = array( 'mytheme-main', 'mytheme-fonts' );

    if ( in_array( $handle, $preload_handles, true ) && 'all' === $media ) {
        // Thêm preload link trước link tag thực sự
        $preload = sprintf(
            '<link rel="preload" href="%s" as="style">' . "\n",
            esc_url( $href )
        );
        return $preload . $html;
    }

    return $html;
}
```

### 4.3. wp_resource_hints - DNS prefetch và preconnect

```php
<?php
/**
 * Filter: wp_resource_hints
 * Tham số: $hints (array), $relation_type (string)
 * $relation_type có thể là: 'dns-prefetch', 'preconnect', 'prefetch', 'prerender'
 * Thời điểm: Trong <head>, khi WordPress render resource hints
 * Dùng khi: Tối ưu kết nối đến CDN, font services, API domains
 */
add_filter( 'wp_resource_hints', 'my_add_resource_hints', 10, 2 );

function my_add_resource_hints( $hints, $relation_type ) {
    // === DNS PREFETCH: Phân giải DNS trước, chi phí thấp ===
    if ( 'dns-prefetch' === $relation_type ) {
        $hints[] = '//fonts.googleapis.com';
        $hints[] = '//fonts.gstatic.com';
        $hints[] = '//cdn.yoursite.com';
        $hints[] = '//www.google-analytics.com';
        $hints[] = '//connect.facebook.net';
        $hints[] = '//s.gravatar.com';
    }

    // === PRECONNECT: Thiết lập kết nối trước (DNS + TCP + TLS) ===
    // Chỉ dùng cho origins thực sự cần thiết (tốn tài nguyên hơn dns-prefetch)
    if ( 'preconnect' === $relation_type ) {
        // Google Fonts cần 2 domains
        $hints[] = array(
            'href'        => 'https://fonts.googleapis.com',
            'crossorigin' => false,
        );
        $hints[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous', // Cần crossorigin cho fonts
        );

        // CDN của bạn
        $hints[] = array(
            'href'        => 'https://cdn.yoursite.com',
            'crossorigin' => false,
        );

        // WooCommerce payment gateways
        if ( class_exists( 'WooCommerce' ) ) {
            $hints[] = array(
                'href'        => 'https://js.stripe.com',
                'crossorigin' => false,
            );
        }
    }

    // === PREFETCH: Prefetch pages user có thể vào tiếp ===
    if ( 'prefetch' === $relation_type && is_singular( 'post' ) ) {
        // Prefetch trang tiếp theo
        $next_post = get_adjacent_post( false, '', false ); // Bài mới hơn
        if ( $next_post ) {
            $hints[] = get_permalink( $next_post );
        }
    }

    return $hints;
}

/**
 * Xóa resource hints mặc định của WordPress không cần thiết
 */
add_filter( 'wp_resource_hints', 'my_remove_unnecessary_hints', 10, 2 );

function my_remove_unnecessary_hints( $hints, $relation_type ) {
    if ( 'dns-prefetch' === $relation_type ) {
        // Xóa s.w.org nếu không dùng emojis
        $hints = array_filter( $hints, function( $hint ) {
            if ( is_array( $hint ) ) {
                return strpos( $hint['href'] ?? '', 's.w.org' ) === false;
            }
            return strpos( $hint, 's.w.org' ) === false;
        });
    }
    return $hints;
}

/**
 * Tắt hoàn toàn emoji để giảm request và scripts không cần thiết
 */
add_action( 'init', 'my_disable_emojis' );

function my_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    // Xóa emoji domain khỏi dns-prefetch
    add_filter( 'wp_resource_hints', function( $hints, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/14.0.0/svg/' );
            $hints         = array_diff( $hints, array( $emoji_svg_url ) );
        }
        return $hints;
    }, 10, 2 );
}

/**
 * Tối ưu thêm: Xóa các output thừa trong <head>
 */
add_action( 'init', 'my_clean_head' );

function my_clean_head() {
    // Xóa WordPress generator tag (bảo mật)
    remove_action( 'wp_head', 'wp_generator' );

    // Xóa RSD link (chỉ cần nếu dùng XML-RPC)
    remove_action( 'wp_head', 'rsd_link' );

    // Xóa Windows Live Writer
    remove_action( 'wp_head', 'wlwmanifest_link' );

    // Xóa shortlink
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );

    // Xóa adjacent posts links
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

    // Xóa REST API link từ <head> (nếu không cần public discovery)
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
}
```

---

## 5. Dashboard Customization Hooks

### 5.1. dashboard_glance_items - Thêm vào widget "At a Glance"

```php
<?php
/**
 * Filter: dashboard_glance_items
 * Tham số: $items (array) - danh sách các item đang hiển thị
 * Thời điểm: Khi WordPress render widget "Tổng quan" (At a Glance) trên Dashboard
 * Dùng khi: Thêm Custom Post Types vào "At a Glance"
 * PHẢI return $items
 */
add_filter( 'dashboard_glance_items', 'my_custom_glance_items' );

function my_custom_glance_items( $items ) {
    // Danh sách Custom Post Types muốn hiển thị
    $custom_post_types = array(
        'portfolio'   => array( 'icon' => 'dashicons-portfolio', 'label' => 'Portfolio' ),
        'product'     => array( 'icon' => 'dashicons-cart', 'label' => 'Sản phẩm' ),
        'event'       => array( 'icon' => 'dashicons-calendar', 'label' => 'Sự kiện' ),
        'testimonial' => array( 'icon' => 'dashicons-format-quote', 'label' => 'Nhận xét' ),
    );

    foreach ( $custom_post_types as $post_type => $config ) {
        // Kiểm tra CPT có tồn tại không
        if ( ! post_type_exists( $post_type ) ) {
            continue;
        }

        $num_posts    = wp_count_posts( $post_type );
        $post_count   = $num_posts->publish ?? 0;
        $post_type_obj = get_post_type_object( $post_type );

        if ( ! $post_type_obj ) {
            continue;
        }

        // Build link đến trang edit của CPT
        if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
            $items[] = sprintf(
                '<a class="dashicons-before %s" href="%s">%d %s</a>',
                esc_attr( $config['icon'] ),
                admin_url( 'edit.php?post_type=' . $post_type ),
                $post_count,
                esc_html( _n(
                    $post_type_obj->labels->singular_name,
                    $post_type_obj->labels->name,
                    $post_count
                ) )
            );
        } else {
            // User không có quyền edit - hiển thị nhưng không link
            $items[] = sprintf(
                '<span class="dashicons-before %s">%d %s</span>',
                esc_attr( $config['icon'] ),
                $post_count,
                esc_html( $post_type_obj->labels->name )
            );
        }
    }

    // Thêm thống kê khác (ví dụ: đơn hàng chờ xử lý)
    if ( class_exists( 'WooCommerce' ) && current_user_can( 'manage_woocommerce' ) ) {
        $pending_orders = wc_orders_count( 'pending' );
        if ( $pending_orders > 0 ) {
            $items[] = sprintf(
                '<a class="dashicons-before dashicons-clock" href="%s">%d đơn hàng chờ xử lý</a>',
                admin_url( 'edit.php?post_type=shop_order&post_status=wc-pending' ),
                $pending_orders
            );
        }
    }

    return $items;
}

/**
 * Styling cho các glance items tùy chỉnh
 */
add_action( 'admin_head', 'my_glance_items_style' );

function my_glance_items_style() {
    $screen = get_current_screen();
    if ( ! $screen || 'dashboard' !== $screen->id ) {
        return;
    }
    ?>
    <style>
        #dashboard_right_now li a.dashicons-before::before,
        #dashboard_right_now li span.dashicons-before::before {
            font-family: dashicons;
            font-size: 14px;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
    <?php
}
```

### 5.2. admin_footer_text - Tùy chỉnh footer admin

```php
<?php
/**
 * Filter: admin_footer_text
 * Tham số: $text (string) - HTML text bên trái footer admin
 * Dùng khi: Thay đổi "Thank you for creating with WordPress" thành text tùy chỉnh
 * PHẢI return $text
 */
add_filter( 'admin_footer_text', 'my_custom_admin_footer_text' );

function my_custom_admin_footer_text( $text ) {
    // Chỉ thay đổi cho non-admin users
    if ( ! current_user_can( 'manage_options' ) ) {
        return sprintf(
            'Cần hỗ trợ? Liên hệ <a href="mailto:%s">%s</a>',
            esc_attr( get_option( 'admin_email' ) ),
            esc_html( get_option( 'admin_email' ) )
        );
    }

    // Cho admin: hiển thị thông tin version và link hữu ích
    return sprintf(
        '<span id="footer-thankyou">%s v%s | <a href="%s" target="_blank">Documentation</a> | <a href="%s" target="_blank">Support</a></span>',
        esc_html( get_bloginfo( 'name' ) ),
        esc_html( get_bloginfo( 'version' ) ),
        'https://docs.yoursite.com',
        'https://support.yoursite.com'
    );
}

/**
 * Filter: update_footer
 * Tham số: $content (string) - Text bên phải footer (thường là version WP)
 * Dùng khi: Thay đổi hoặc ẩn version WordPress
 */
add_filter( 'update_footer', 'my_custom_admin_footer_version', 11 );

function my_custom_admin_footer_version( $content ) {
    // Ẩn version WordPress (bảo mật - không để lộ version)
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }

    // Với admin: hiển thị version cụ thể
    global $wp_version;
    return sprintf(
        'WordPress %s | PHP %s | MySQL %s',
        $wp_version,
        phpversion(),
        $GLOBALS['wpdb']->db_version()
    );
}
```

### 5.3. wp_dashboard_setup - Thêm/xóa Dashboard widgets

```php
<?php
/**
 * Hook: wp_dashboard_setup
 * Thời điểm: Khi WordPress thiết lập các Dashboard widgets
 * Dùng khi: Thêm custom widgets, xóa widgets mặc định không cần thiết
 */
add_action( 'wp_dashboard_setup', 'my_setup_dashboard' );

function my_setup_dashboard() {
    // === XÓA WIDGETS MẶC ĐỊNH KHÔNG CẦN THIẾT ===
    // Chỉ xóa cho non-admins để admin vẫn thấy đủ thông tin

    if ( ! current_user_can( 'manage_options' ) ) {
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );       // Quick Draft
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );           // WordPress News
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );       // At a Glance
    }

    // Xóa WooCommerce marketing widget (nếu không cần)
    remove_meta_box( 'wc_admin_dashboard_setup', 'dashboard', 'normal' );

    // === THÊM CUSTOM WIDGETS ===

    // Widget 1: Thống kê tổng quan
    wp_add_dashboard_widget(
        'my_stats_widget',          // Widget ID (unique)
        'Thống kê hôm nay',         // Tiêu đề widget
        'my_render_stats_widget',   // Callback render
        'my_configure_stats_widget' // Callback cấu hình (optional)
    );

    // Widget 2: Đơn hàng cần xử lý (chỉ hiện với người có quyền)
    if ( class_exists( 'WooCommerce' ) && current_user_can( 'manage_woocommerce' ) ) {
        wp_add_dashboard_widget(
            'my_orders_widget',
            'Đơn hàng cần xử lý',
            'my_render_orders_widget'
        );
    }

    // Widget 3: Thông báo nội bộ (chỉ admin thấy)
    if ( current_user_can( 'manage_options' ) ) {
        wp_add_dashboard_widget(
            'my_admin_notice_widget',
            'Thông báo Quản trị',
            'my_render_admin_notice_widget'
        );

        // Di chuyển widget lên vị trí đầu tiên
        global $wp_meta_boxes;
        $widget = $wp_meta_boxes['dashboard']['normal']['core']['my_admin_notice_widget'];
        unset( $wp_meta_boxes['dashboard']['normal']['core']['my_admin_notice_widget'] );
        $wp_meta_boxes['dashboard']['normal']['high']['my_admin_notice_widget'] = $widget;
    }
}

function my_render_stats_widget() {
    // Lấy số liệu thống kê trong ngày hôm nay
    $today_start = current_time( 'Y-m-d' ) . ' 00:00:00';
    $today_end   = current_time( 'Y-m-d' ) . ' 23:59:59';

    // Đếm bài viết publish hôm nay
    global $wpdb;
    $today_posts = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(ID) FROM {$wpdb->posts}
         WHERE post_status = 'publish'
         AND post_date >= %s AND post_date <= %s",
        $today_start,
        $today_end
    ) );

    // Đếm comments hôm nay
    $today_comments = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(comment_ID) FROM {$wpdb->comments}
         WHERE comment_approved = '1'
         AND comment_date >= %s AND comment_date <= %s",
        $today_start,
        $today_end
    ) );

    // Đếm user đăng ký hôm nay
    $today_users = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(ID) FROM {$wpdb->users}
         WHERE user_registered >= %s AND user_registered <= %s",
        $today_start,
        $today_end
    ) );

    echo '<div class="my-stats-widget">';
    echo '<ul>';
    printf( '<li><span class="dashicons dashicons-admin-post"></span> <strong>%d</strong> bài viết mới hôm nay</li>', (int) $today_posts );
    printf( '<li><span class="dashicons dashicons-admin-comments"></span> <strong>%d</strong> bình luận hôm nay</li>', (int) $today_comments );
    printf( '<li><span class="dashicons dashicons-admin-users"></span> <strong>%d</strong> thành viên mới hôm nay</li>', (int) $today_users );
    echo '</ul>';

    // Link đến các trang quản lý
    echo '<p class="my-stats-links">';
    echo '<a href="' . admin_url( 'edit.php' ) . '">Quản lý bài viết</a> | ';
    echo '<a href="' . admin_url( 'edit-comments.php' ) . '">Quản lý bình luận</a> | ';
    echo '<a href="' . admin_url( 'users.php' ) . '">Quản lý thành viên</a>';
    echo '</p>';
    echo '</div>';
}

function my_render_orders_widget() {
    $orders = wc_get_orders( array(
        'status'  => array( 'wc-pending', 'wc-processing' ),
        'limit'   => 5,
        'orderby' => 'date',
        'order'   => 'DESC',
    ) );

    if ( empty( $orders ) ) {
        echo '<p>Không có đơn hàng nào cần xử lý.</p>';
        return;
    }

    echo '<table class="my-orders-widget" style="width:100%">';
    echo '<thead><tr><th>Đơn #</th><th>Khách hàng</th><th>Tổng</th><th>Trạng thái</th></tr></thead>';
    echo '<tbody>';

    foreach ( $orders as $order ) {
        printf(
            '<tr>
                <td><a href="%s">#%d</a></td>
                <td>%s</td>
                <td>%s</td>
                <td><mark class="order-status status-%s">%s</mark></td>
            </tr>',
            esc_url( $order->get_edit_order_url() ),
            $order->get_id(),
            esc_html( $order->get_billing_full_name() ),
            wp_kses_post( $order->get_formatted_order_total() ),
            esc_attr( $order->get_status() ),
            esc_html( wc_get_order_status_name( $order->get_status() ) )
        );
    }

    echo '</tbody></table>';

    $pending_count    = wc_orders_count( 'pending' );
    $processing_count = wc_orders_count( 'processing' );

    printf(
        '<p><a href="%s">Xem tất cả đơn hàng</a> | Đang chờ: <strong>%d</strong> | Đang xử lý: <strong>%d</strong></p>',
        admin_url( 'edit.php?post_type=shop_order' ),
        $pending_count,
        $processing_count
    );
}

function my_render_admin_notice_widget() {
    $notices = get_option( 'my_admin_internal_notices', array() );

    if ( empty( $notices ) ) {
        echo '<p style="color:#999">Không có thông báo nào.</p>';
    } else {
        echo '<ul>';
        foreach ( array_slice( array_reverse( $notices ), 0, 5 ) as $notice ) {
            printf(
                '<li><strong>[%s]</strong> %s</li>',
                esc_html( $notice['date'] ),
                esc_html( $notice['message'] )
            );
        }
        echo '</ul>';
    }

    // Form thêm thông báo mới
    echo '<hr>';
    echo '<form method="post">';
    wp_nonce_field( 'my_add_admin_notice', 'my_notice_nonce' );
    echo '<input type="text" name="my_notice_text" placeholder="Thêm thông báo nội bộ..." style="width:100%">';
    echo '<input type="hidden" name="action" value="my_add_notice">';
    submit_button( 'Thêm', 'small', 'submit', false );
    echo '</form>';
}

// Xử lý form thêm thông báo
add_action( 'admin_post_my_add_notice', 'my_handle_add_notice' );

function my_handle_add_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Không có quyền.' );
    }

    check_admin_referer( 'my_add_admin_notice', 'my_notice_nonce' );

    $message = sanitize_text_field( $_POST['my_notice_text'] ?? '' );
    if ( ! empty( $message ) ) {
        $notices   = get_option( 'my_admin_internal_notices', array() );
        $notices[] = array(
            'date'    => current_time( 'Y-m-d H:i' ),
            'message' => $message,
            'user'    => get_current_user_id(),
        );

        // Giữ tối đa 20 thông báo
        if ( count( $notices ) > 20 ) {
            $notices = array_slice( $notices, -20 );
        }

        update_option( 'my_admin_internal_notices', $notices );
    }

    wp_redirect( admin_url( 'index.php' ) );
    exit;
}
```

---

## 6. Advanced Hook Patterns

### 6.1. Conditional Hook Registration - Đăng ký hook có điều kiện

```php
<?php
/**
 * Pattern: Đăng ký hook chỉ khi cần thiết
 * Tránh đăng ký hooks không cần thiết để tối ưu performance
 */

// KHÔNG ĐÚNG: Luôn đăng ký dù hook chỉ cần trên một số trang
add_filter( 'the_content', 'my_expensive_filter' ); // Chạy trên MỌI request

// ĐÚNG: Đăng ký trong context phù hợp
add_action( 'wp', 'my_register_conditional_hooks' );

function my_register_conditional_hooks() {
    // Chỉ thêm filter tốn kém khi thực sự cần
    if ( is_singular( 'product' ) ) {
        add_filter( 'the_content', 'my_product_content_filter' );
        add_action( 'woocommerce_before_single_product', 'my_product_announcement' );
    }

    if ( is_user_logged_in() && is_account_page() ) {
        add_action( 'woocommerce_account_content', 'my_custom_account_content' );
    }

    if ( is_checkout() ) {
        add_filter( 'woocommerce_checkout_fields', 'my_checkout_fields_modifier' );
        add_action( 'woocommerce_checkout_process', 'my_checkout_validator' );
    }
}
```

### 6.2. Hook Chaining - Chuỗi hook phụ thuộc

```php
<?php
/**
 * Pattern: Hook kích hoạt hook khác - tạo pipeline xử lý
 */

// Bước 1: Khi order hoàn thành → kích hoạt post-processing pipeline
add_action( 'woocommerce_order_status_completed', 'my_order_completed_pipeline', 10, 1 );

function my_order_completed_pipeline( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Fire một chuỗi hooks theo thứ tự
    // Mỗi module đăng ký vào hook tương ứng
    do_action( 'my_pipeline_start',     $order_id, $order );
    do_action( 'my_pipeline_award',     $order_id, $order ); // Tích điểm
    do_action( 'my_pipeline_notify',    $order_id, $order ); // Gửi thông báo
    do_action( 'my_pipeline_analytics', $order_id, $order ); // Tracking
    do_action( 'my_pipeline_sync',      $order_id, $order ); // Sync ERP/CRM
    do_action( 'my_pipeline_end',       $order_id, $order );
}

// Module tích điểm
add_action( 'my_pipeline_award', function( $order_id, $order ) {
    $user_id = $order->get_user_id();
    if ( $user_id ) {
        $points = (int) ( $order->get_total() / 10000 );
        update_user_meta( $user_id, 'loyalty_points',
            (int) get_user_meta( $user_id, 'loyalty_points', true ) + $points
        );
    }
}, 10, 2 );

// Module thông báo
add_action( 'my_pipeline_notify', function( $order_id, $order ) {
    // Gửi SMS + Email + Push notification
    wp_mail(
        $order->get_billing_email(),
        'Đơn hàng #' . $order_id . ' hoàn thành!',
        'Cảm ơn bạn đã mua hàng!'
    );
}, 10, 2 );

// Module sync CRM
add_action( 'my_pipeline_sync', function( $order_id, $order ) {
    // Gọi CRM API
    // crm_api_update_customer( $order->get_billing_email(), $order->get_total() );
}, 10, 2 );
```

### 6.3. Hookable Class - Class cho phép override method qua filter

```php
<?php
/**
 * Pattern: Hookable class - Cho phép override hành vi qua filters
 * Hữu ích khi muốn tạo extensible libraries
 */

class My_Email_Sender {

    private $from_email;
    private $from_name;

    public function __construct() {
        // Cho phép filter giá trị mặc định
        $this->from_email = apply_filters(
            'my_email_sender_from_email',
            get_option( 'admin_email' )
        );
        $this->from_name = apply_filters(
            'my_email_sender_from_name',
            get_bloginfo( 'name' )
        );
    }

    public function send( $to, $subject, $message, $args = array() ) {
        // Cho phép filter toàn bộ params trước khi gửi
        $params = apply_filters( 'my_email_sender_params', array(
            'to'      => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => array(
                'Content-Type: text/html; charset=UTF-8',
                "From: {$this->from_name} <{$this->from_email}>",
            ),
        ), $args );

        // Cho phép cancel gửi email
        if ( ! apply_filters( 'my_email_sender_should_send', true, $params ) ) {
            return false;
        }

        $result = wp_mail(
            $params['to'],
            $params['subject'],
            $params['message'],
            $params['headers']
        );

        // Fire action sau khi gửi
        do_action( 'my_email_sender_sent', $result, $params );

        return $result;
    }
}

// Sử dụng:
$sender = new My_Email_Sender();

// Plugin khác có thể override từ email:
add_filter( 'my_email_sender_from_email', function( $email ) {
    return 'noreply@mysite.com';
} );

// Cancel gửi email trong môi trường dev:
add_filter( 'my_email_sender_should_send', function( $should_send, $params ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[Dev] Email sẽ gửi đến: ' . $params['to'] );
        return false; // Không gửi thật trong dev
    }
    return $should_send;
}, 10, 2 );
```

### 6.4. One-time Hook - Hook chỉ chạy một lần

```php
<?php
/**
 * Pattern: Hook chỉ chạy MỘT LẦN rồi tự remove
 * Hữu ích cho: setup tasks, migration, one-off actions
 */

function my_run_once_on_save( $post_id ) {
    // Remove chính nó ngay lập tức
    remove_action( 'save_post', 'my_run_once_on_save' );

    // Thực hiện logic chỉ chạy một lần
    error_log( '[Migration] Chạy migration lần đầu tiên cho post #' . $post_id );
    update_option( 'my_migration_done', true );
}

// Chỉ đăng ký nếu migration chưa chạy
if ( ! get_option( 'my_migration_done' ) ) {
    add_action( 'save_post', 'my_run_once_on_save' );
}

/**
 * Pattern: Sử dụng closure để tạo one-time hook linh hoạt hơn
 */
function my_add_once_action( $hook, $callback, $priority = 10, $args = 1 ) {
    // Tạo wrapper function tự remove sau khi chạy
    $wrapper = null;
    $wrapper = function() use ( $hook, &$wrapper, $callback, $priority, $args ) {
        remove_action( $hook, $wrapper, $priority );
        return call_user_func_array( $callback, func_get_args() );
    };

    add_action( $hook, $wrapper, $priority, $args );
    return $wrapper;
}

// Sử dụng:
my_add_once_action( 'init', function() {
    error_log( 'Chạy chỉ một lần!' );
} );
```

### 6.5. Filter with Context - Filter nhận context parameter

```php
<?php
/**
 * Pattern: Filter truyền context để callback biết đang ở đâu
 * Best practice khi cùng một filter dùng ở nhiều nơi
 */

function my_get_product_price( $product_id, $context = 'display' ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return 0;
    }

    $price = $product->get_price();

    /**
     * Filter: my_product_price
     * Tham số:
     *   $price      (float)  - Giá hiện tại
     *   $product_id (int)    - ID sản phẩm
     *   $context    (string) - 'display', 'cart', 'checkout', 'api', 'export'
     */
    $price = apply_filters( 'my_product_price', $price, $product_id, $context );

    return $price;
}

// Callback xử lý giá khác nhau theo context
add_filter( 'my_product_price', function( $price, $product_id, $context ) {
    switch ( $context ) {
        case 'display':
            // Hiển thị - không thay đổi
            return $price;

        case 'cart':
        case 'checkout':
            // Áp dụng giá VIP nếu user có role đặc biệt
            if ( current_user_can( 'vip_customer' ) ) {
                return $price * 0.9; // Giảm 10%
            }
            return $price;

        case 'api':
            // Luôn trả về giá gốc cho API
            $product = wc_get_product( $product_id );
            return $product ? $product->get_regular_price() : $price;

        case 'export':
            // Làm tròn cho export
            return round( $price );
    }

    return $price;
}, 10, 3 );

// Cách dùng:
$display_price  = my_get_product_price( 123, 'display' );
$cart_price     = my_get_product_price( 123, 'cart' );
$api_price      = my_get_product_price( 123, 'api' );
```

---

## Tổng Kết

| Nhóm Hook | Hooks Chính | Mục đích |
|-----------|-------------|----------|
| **WooCommerce** | `woocommerce_before_cart`, `woocommerce_checkout_process`, `woocommerce_order_status_changed`, `woocommerce_add_to_cart_validation` | Tùy chỉnh giỏ hàng, checkout, đơn hàng |
| **User** | `user_register`, `registration_errors`, `profile_update`, `wp_login`, `wp_login_failed`, `wp_logout` | Quản lý vòng đời user |
| **REST API** | `rest_api_init`, `rest_pre_dispatch`, `rest_post_dispatch` | Tạo và kiểm soát REST endpoints |
| **Performance** | `script_loader_tag`, `style_loader_tag`, `wp_resource_hints` | Tối ưu tải trang |
| **Dashboard** | `dashboard_glance_items`, `admin_footer_text`, `wp_dashboard_setup` | Tùy chỉnh Admin UI |

**Nguyên tắc quan trọng khi làm việc với hooks chuyên đề:**

1. WooCommerce hooks: Luôn kiểm tra `class_exists('WooCommerce')` trước khi dùng
2. REST API hooks: Luôn có `permission_callback` - không bao giờ dùng `'permission_callback' => '__return_true'` cho endpoints nhạy cảm
3. Performance hooks: Không defer các scripts quan trọng (jQuery, checkout, payment)
4. User hooks: Validate trong `registration_errors`, KHÔNG trong `user_register`
5. Dashboard hooks: Kiểm tra `current_user_can()` trước khi hiển thị thông tin nhạy cảm

---

[← Quay lại: Ví dụ thực tế](./08-vi-du-thuc-te.md) | [↑ Mục lục Hooks](./index.md) | [→ Tiếp: Cơ sở dữ liệu](../03-database/)
