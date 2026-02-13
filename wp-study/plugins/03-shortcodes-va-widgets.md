# Shortcodes và Widgets trong WordPress Plugin

## Mục lục

1. [Shortcodes cơ bản](#1-shortcodes-co-ban)
2. [Shortcode với Attributes](#2-shortcode-voi-attributes)
3. [Shortcode với Enclosed Content](#3-shortcode-voi-enclosed-content)
4. [Shortcode với Form](#4-shortcode-voi-form)
5. [Shortcode lồng nhau (Nested)](#5-shortcode-long-nhau-nested)
6. [Widgets API](#6-widgets-api)
7. [Tạo Widget tùy chỉnh](#7-tao-widget-tuy-chinh)
8. [Gutenberg Block vs Widget](#8-gutenberg-block-vs-widget)
9. [Code ví dụ đầy đủ](#9-code-vi-du-day-du)
10. [Best Practices](#10-best-practices)

---

## 1. Shortcodes cơ bản

### Shortcode là gì?

Shortcode là **mã tắt** đặt trong ngoặc vuông `[]` cho phép người dùng chèn nội dung động vào bài viết, trang, hoặc widget. Shortcode được xử lý phía server và trả về HTML.

```
Người dùng viết:     [my_shortcode]
WordPress xử lý:     gọi hàm callback
Kết quả:             <div class="my-output">Nội dung</div>
```

### So sánh với Laravel

```
Laravel:   Blade Component   @component('alert') hoặc <x-alert />
WordPress: Shortcode         [alert]
```

### Tạo Shortcode đơn giản

```php
<?php
/**
 * Plugin Name: Shortcode Demo
 * Description: Demo các loại shortcode.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * add_shortcode() - Đăng ký 1 shortcode
 *
 * @param string   $tag      Tên shortcode (đặt trong [])
 * @param callable $callback Hàm xử lý, PHẢI return (không echo)
 */
add_shortcode( 'hello', 'scd_hello_shortcode' );

/**
 * Shortcode đơn giản nhất: [hello]
 * Trả về chuỗi "Xin chào!"
 *
 * LƯU Ý QUAN TRỌNG: Shortcode callback PHẢI return, KHÔNG được echo.
 * Nếu echo, nội dung sẽ xuất hiện sai vị trí (trên cùng trang).
 */
function scd_hello_shortcode() {
    return '<p style="color: green; font-weight: bold;">Xin chào từ Shortcode!</p>';
}

// Sử dụng trong bài viết:
// [hello]

// Shortcode hiển thị năm hiện tại: [current_year]
add_shortcode( 'current_year', function() {
    return date( 'Y' );
});

// Sử dụng: Bản quyền &copy; [current_year] Công ty ABC
// Kết quả: Bản quyền (c) 2024 Công ty ABC
```

---

## 2. Shortcode với Attributes

### shortcode_atts() - Xử lý thuộc tính

```php
<?php
/**
 * Shortcode với thuộc tính (attributes):
 * [button text="Click me" url="https://example.com" color="blue" size="large"]
 */
add_shortcode( 'button', 'scd_button_shortcode' );

/**
 * @param array  $atts    Các thuộc tính người dùng truyền vào
 * @param string $content Nội dung giữa thẻ mở và đóng (null nếu self-closing)
 * @param string $tag     Tên shortcode ('button')
 */
function scd_button_shortcode( $atts, $content = null, $tag = '' ) {
    /**
     * shortcode_atts() - Gộp thuộc tính mặc định với thuộc tính người dùng
     *
     * @param array  $defaults  Giá trị mặc định
     * @param array  $atts      Giá trị người dùng truyền
     * @param string $shortcode Tên shortcode (cho filter)
     *
     * Hoạt động: Nếu người dùng KHÔNG truyền attribute,
     * dùng giá trị mặc định. Nếu có truyền, dùng giá trị của người dùng.
     */
    $atts = shortcode_atts( array(
        'text'   => 'Click here',           // Mặc định nếu không truyền
        'url'    => '#',
        'color'  => 'blue',                 // blue, green, red, orange
        'size'   => 'medium',               // small, medium, large
        'target' => '_self',                // _self, _blank
        'class'  => '',                     // CSS class tùy chỉnh
    ), $atts, 'button' );                   // 'button' = tên shortcode

    // Xác định styles dựa trên attributes
    $colors = array(
        'blue'   => '#0073aa',
        'green'  => '#46b450',
        'red'    => '#dc3232',
        'orange' => '#f56e28',
    );

    $sizes = array(
        'small'  => 'padding: 5px 15px; font-size: 12px;',
        'medium' => 'padding: 10px 25px; font-size: 14px;',
        'large'  => 'padding: 15px 35px; font-size: 18px;',
    );

    $bg_color = $colors[ $atts['color'] ] ?? $colors['blue'];
    $size_style = $sizes[ $atts['size'] ] ?? $sizes['medium'];
    $extra_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

    // Trả về HTML
    return sprintf(
        '<a href="%s" target="%s" class="scd-button%s" style="
            display: inline-block;
            background: %s;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            %s
        ">%s</a>',
        esc_url( $atts['url'] ),
        esc_attr( $atts['target'] ),
        $extra_class,
        esc_attr( $bg_color ),
        $size_style,
        esc_html( $atts['text'] )
    );
}

// Sử dụng:
// [button]                                          => Nút mặc định
// [button text="Mua ngay" color="green" size="large"]
// [button text="Xem chi tiết" url="/san-pham" target="_blank"]
```

### Shortcode hiển thị danh sách posts

```php
<?php
/**
 * [recent_posts count="5" category="tin-tuc" orderby="date"]
 */
add_shortcode( 'recent_posts', 'scd_recent_posts_shortcode' );

function scd_recent_posts_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'count'    => 5,
        'category' => '',
        'orderby'  => 'date',
        'order'    => 'DESC',
        'columns'  => 1,
    ), $atts, 'recent_posts' );

    // Xây dựng query args
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
        'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
        'post_status'    => 'publish',
    );

    // Thêm category nếu có
    if ( ! empty( $atts['category'] ) ) {
        $args['category_name'] = sanitize_text_field( $atts['category'] );
    }

    $query = new WP_Query( $args );

    // Bắt đầu output buffering
    // Vì shortcode phải return, ta dùng ob_start/ob_get_clean
    // để viết HTML tự nhiên hơn (thay vì nối chuỗi)
    ob_start();

    if ( $query->have_posts() ) :
        $cols = max( 1, intval( $atts['columns'] ) );
        ?>
        <div class="scd-posts-grid" style="
            display: grid;
            grid-template-columns: repeat(<?php echo $cols; ?>, 1fr);
            gap: 20px;
            margin: 20px 0;
        ">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <article class="scd-post-item" style="
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    overflow: hidden;
                ">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'medium', array(
                                'style' => 'width:100%; height:200px; object-fit:cover;'
                            ) ); ?>
                        </a>
                    <?php endif; ?>

                    <div style="padding: 15px;">
                        <h3 style="margin:0 0 10px;">
                            <a href="<?php the_permalink(); ?>" style="text-decoration:none; color:#333;">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        <p style="color:#666; font-size:13px; margin:0 0 10px;">
                            <?php echo esc_html( get_the_date() ); ?> |
                            <?php echo esc_html( get_the_author() ); ?>
                        </p>
                        <p style="margin:0;">
                            <?php echo wp_trim_words( get_the_excerpt(), 20, '...' ); ?>
                        </p>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <?php
    else :
        echo '<p>Không có bài viết nào.</p>';
    endif;

    // QUAN TRỌNG: Reset post data sau khi dùng WP_Query tùy chỉnh
    wp_reset_postdata();

    // Lấy nội dung từ buffer và return
    return ob_get_clean();
}

// Sử dụng:
// [recent_posts]
// [recent_posts count="3" columns="3"]
// [recent_posts count="6" category="tin-tuc" columns="2"]
```

---

## 3. Shortcode với Enclosed Content

### Shortcode bao quanh nội dung

```php
<?php
/**
 * Shortcode có thể bao quanh nội dung:
 * [highlight color="yellow"]Nội dung cần highlight[/highlight]
 *
 * $content chứa nội dung giữa [shortcode] và [/shortcode]
 */
add_shortcode( 'highlight', 'scd_highlight_shortcode' );

function scd_highlight_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'color' => 'yellow',
        'style' => 'inline',   // inline hoặc block
    ), $atts, 'highlight' );

    // Xử lý nội dung bên trong (cho phép shortcode lồng nhau)
    // do_shortcode() xử lý các shortcode nằm trong $content
    $content = do_shortcode( $content );

    $display = $atts['style'] === 'block' ? 'display:block; padding:15px;' : 'padding:2px 5px;';

    return sprintf(
        '<span class="scd-highlight" style="background-color:%s; %s border-radius:3px;">%s</span>',
        esc_attr( $atts['color'] ),
        $display,
        wp_kses_post( $content )  // Cho phép HTML an toàn
    );
}

// [highlight]Nội dung quan trọng[/highlight]
// [highlight color="#ff0" style="block"]
//     <strong>Chú ý:</strong> Đây là nội dung quan trọng.
// [/highlight]
```

### Shortcode Alert Box

```php
<?php
/**
 * [alert type="warning"]Nội dung cảnh báo[/alert]
 * [alert type="success"]Thao tác thành công![/alert]
 * [alert type="error"]Có lỗi xảy ra![/alert]
 * [alert type="info"]Thông tin tham khảo.[/alert]
 */
add_shortcode( 'alert', 'scd_alert_shortcode' );

function scd_alert_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'type'        => 'info',      // info, success, warning, error
        'dismissible' => 'false',     // true/false
        'icon'        => 'true',      // true/false
    ), $atts, 'alert' );

    // Màu sắc và icon theo type
    $styles = array(
        'info'    => array( 'bg' => '#e7f3fe', 'border' => '#2196F3', 'icon' => 'ℹ' ),
        'success' => array( 'bg' => '#ddffdd', 'border' => '#4CAF50', 'icon' => '✓' ),
        'warning' => array( 'bg' => '#ffffcc', 'border' => '#ff9800', 'icon' => '⚠' ),
        'error'   => array( 'bg' => '#ffdddd', 'border' => '#f44336', 'icon' => '✗' ),
    );

    $type = array_key_exists( $atts['type'], $styles ) ? $atts['type'] : 'info';
    $style = $styles[ $type ];

    $content = do_shortcode( $content );

    $icon_html = '';
    if ( $atts['icon'] === 'true' ) {
        $icon_html = '<span style="font-size:20px; margin-right:10px;">' . $style['icon'] . '</span>';
    }

    $close_html = '';
    if ( $atts['dismissible'] === 'true' ) {
        $close_html = '<button onclick="this.parentElement.style.display=\'none\'"
                        style="float:right; background:none; border:none; font-size:18px; cursor:pointer;">
                        &times;</button>';
    }

    return sprintf(
        '<div class="scd-alert scd-alert-%s" role="alert" style="
            background: %s;
            border-left: 4px solid %s;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
        ">%s%s<div>%s</div>%s</div>',
        esc_attr( $type ),
        esc_attr( $style['bg'] ),
        esc_attr( $style['border'] ),
        $close_html,
        $icon_html,
        wp_kses_post( $content ),
        ''
    );
}

// Sử dụng:
// [alert type="info"]Đây là thông tin tham khảo.[/alert]
// [alert type="warning" dismissible="true"]Cảnh báo! Có thể đóng lại được.[/alert]
// [alert type="error"]Lỗi: Không thể kết nối database![/alert]
// [alert type="success" icon="false"]Thành công![/alert]
```

---

## 4. Shortcode với Form

```php
<?php
/**
 * Shortcode tạo form liên hệ:
 * [contact_form email="admin@example.com" subject="Liên hệ từ website"]
 */
add_shortcode( 'contact_form', 'scd_contact_form_shortcode' );

function scd_contact_form_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'email'   => get_option( 'admin_email' ),
        'subject' => 'Liên hệ từ ' . get_bloginfo( 'name' ),
        'success' => 'Cảm ơn bạn! Tin nhắn đã được gửi thành công.',
    ), $atts, 'contact_form' );

    $message = '';
    $form_data = array( 'name' => '', 'email' => '', 'phone' => '', 'message' => '' );

    // Xử lý form khi submit
    if ( isset( $_POST['scd_contact_submit'] ) ) {
        // Kiểm tra nonce
        if ( ! wp_verify_nonce( $_POST['scd_contact_nonce'] ?? '', 'scd_contact_action' ) ) {
            $message = '<div class="scd-alert" style="background:#ffdddd; padding:10px; margin:10px 0;">
                Lỗi bảo mật! Vui lòng thử lại.</div>';
        } else {
            // Lấy và sanitize dữ liệu
            $form_data['name']    = sanitize_text_field( $_POST['scd_name'] ?? '' );
            $form_data['email']   = sanitize_email( $_POST['scd_email'] ?? '' );
            $form_data['phone']   = sanitize_text_field( $_POST['scd_phone'] ?? '' );
            $form_data['message'] = sanitize_textarea_field( $_POST['scd_message'] ?? '' );

            // Validate
            $errors = array();
            if ( empty( $form_data['name'] ) ) {
                $errors[] = 'Vui lòng nhập họ tên.';
            }
            if ( ! is_email( $form_data['email'] ) ) {
                $errors[] = 'Email không hợp lệ.';
            }
            if ( empty( $form_data['message'] ) ) {
                $errors[] = 'Vui lòng nhập nội dung tin nhắn.';
            }

            if ( ! empty( $errors ) ) {
                $message = '<div style="background:#ffdddd; padding:10px; margin:10px 0; border-radius:4px;">';
                foreach ( $errors as $error ) {
                    $message .= '<p style="margin:5px 0; color:#d63638;">' . esc_html( $error ) . '</p>';
                }
                $message .= '</div>';
            } else {
                // Gửi email
                $email_body = sprintf(
                    "Họ tên: %s\nEmail: %s\nSố điện thoại: %s\n\nNội dung:\n%s",
                    $form_data['name'],
                    $form_data['email'],
                    $form_data['phone'],
                    $form_data['message']
                );

                $headers = array(
                    'Content-Type: text/plain; charset=UTF-8',
                    'Reply-To: ' . $form_data['name'] . ' <' . $form_data['email'] . '>',
                );

                $sent = wp_mail(
                    sanitize_email( $atts['email'] ),
                    sanitize_text_field( $atts['subject'] ),
                    $email_body,
                    $headers
                );

                if ( $sent ) {
                    $message = '<div style="background:#ddffdd; padding:15px; margin:10px 0; border-radius:4px;">
                        <p style="margin:0; color:#46b450;">' . esc_html( $atts['success'] ) . '</p>
                    </div>';
                    // Reset form data sau khi gửi thành công
                    $form_data = array( 'name' => '', 'email' => '', 'phone' => '', 'message' => '' );
                } else {
                    $message = '<div style="background:#ffdddd; padding:15px; margin:10px 0; border-radius:4px;">
                        <p style="margin:0; color:#d63638;">Có lỗi khi gửi email. Vui lòng thử lại.</p>
                    </div>';
                }
            }
        }
    }

    // Render form
    ob_start();
    ?>
    <div class="scd-contact-form" style="max-width:600px; margin:20px 0;">
        <?php echo $message; ?>

        <form method="post" action="" style="display:flex; flex-direction:column; gap:15px;">
            <?php wp_nonce_field( 'scd_contact_action', 'scd_contact_nonce' ); ?>

            <div>
                <label for="scd_name" style="display:block; margin-bottom:5px; font-weight:600;">
                    Họ tên <span style="color:red;">*</span>
                </label>
                <input type="text" id="scd_name" name="scd_name"
                       value="<?php echo esc_attr( $form_data['name'] ); ?>"
                       required
                       style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px;">
            </div>

            <div>
                <label for="scd_email" style="display:block; margin-bottom:5px; font-weight:600;">
                    Email <span style="color:red;">*</span>
                </label>
                <input type="email" id="scd_email" name="scd_email"
                       value="<?php echo esc_attr( $form_data['email'] ); ?>"
                       required
                       style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px;">
            </div>

            <div>
                <label for="scd_phone" style="display:block; margin-bottom:5px; font-weight:600;">
                    Số điện thoại
                </label>
                <input type="tel" id="scd_phone" name="scd_phone"
                       value="<?php echo esc_attr( $form_data['phone'] ); ?>"
                       style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px;">
            </div>

            <div>
                <label for="scd_message" style="display:block; margin-bottom:5px; font-weight:600;">
                    Nội dung <span style="color:red;">*</span>
                </label>
                <textarea id="scd_message" name="scd_message" rows="5" required
                          style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px;"
                ><?php echo esc_textarea( $form_data['message'] ); ?></textarea>
            </div>

            <div>
                <button type="submit" name="scd_contact_submit" value="1"
                        style="background:#0073aa; color:#fff; border:none; padding:12px 30px;
                               border-radius:4px; cursor:pointer; font-size:16px;">
                    Gửi tin nhắn
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
```

---

## 5. Shortcode lồng nhau (Nested)

```php
<?php
/**
 * Shortcodes lồng nhau để tạo layout phức tạp.
 *
 * [row]
 *   [column width="6"]Nội dung cột 1[/column]
 *   [column width="6"]Nội dung cột 2[/column]
 * [/row]
 *
 * Dùng hệ thống 12 columns (giống Bootstrap grid).
 */

// === ROW Shortcode ===
add_shortcode( 'row', 'scd_row_shortcode' );

function scd_row_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'gap'   => '20px',
        'class' => '',
    ), $atts, 'row' );

    // do_shortcode() để xử lý [column] bên trong
    $content = do_shortcode( $content );

    return sprintf(
        '<div class="scd-row %s" style="
            display: flex;
            flex-wrap: wrap;
            gap: %s;
            margin: 15px 0;
        ">%s</div>',
        esc_attr( $atts['class'] ),
        esc_attr( $atts['gap'] ),
        $content
    );
}

// === COLUMN Shortcode ===
add_shortcode( 'column', 'scd_column_shortcode' );

function scd_column_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'width' => '6',        // 1-12 (hệ thống 12 cột)
        'class' => '',
    ), $atts, 'column' );

    $width = max( 1, min( 12, intval( $atts['width'] ) ) );
    // Tính % dựa trên 12 columns, trừ gap
    $percentage = ( $width / 12 ) * 100;

    $content = do_shortcode( $content );

    return sprintf(
        '<div class="scd-column %s" style="
            flex: 0 0 calc(%s%% - 20px);
            max-width: calc(%s%% - 20px);
        ">%s</div>',
        esc_attr( $atts['class'] ),
        $percentage,
        $percentage,
        wp_kses_post( $content )
    );
}

// Sử dụng:
// [row]
//   [column width="4"]
//     <h3>Cột 1</h3>
//     <p>Chiếm 1/3 chiều rộng</p>
//   [/column]
//   [column width="4"]
//     <h3>Cột 2</h3>
//     <p>Chiếm 1/3 chiều rộng</p>
//   [/column]
//   [column width="4"]
//     <h3>Cột 3</h3>
//     <p>Chiếm 1/3 chiều rộng</p>
//   [/column]
// [/row]
```

### Tabs Shortcode

```php
<?php
/**
 * [tabs]
 *   [tab title="Tab 1"]Nội dung tab 1[/tab]
 *   [tab title="Tab 2"]Nội dung tab 2[/tab]
 *   [tab title="Tab 3"]Nội dung tab 3[/tab]
 * [/tabs]
 */

// Biến global tạm để lưu data các tab
$scd_tabs_data = array();

add_shortcode( 'tabs', 'scd_tabs_shortcode' );

function scd_tabs_shortcode( $atts, $content = null ) {
    global $scd_tabs_data;
    $scd_tabs_data = array(); // Reset

    // do_shortcode để xử lý các [tab] bên trong
    // Mỗi [tab] sẽ push data vào $scd_tabs_data
    do_shortcode( $content );

    if ( empty( $scd_tabs_data ) ) {
        return '';
    }

    // Tạo ID duy nhất cho mỗi tabs instance
    $tabs_id = 'scd-tabs-' . wp_rand( 1000, 9999 );

    ob_start();
    ?>
    <div class="scd-tabs" id="<?php echo esc_attr( $tabs_id ); ?>"
         style="margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">

        <!-- Tab Headers -->
        <div class="scd-tabs-nav" style="display:flex; background:#f5f5f5; border-bottom:1px solid #ddd;">
            <?php foreach ( $scd_tabs_data as $index => $tab ) : ?>
                <button class="scd-tab-button"
                        data-tab="<?php echo $index; ?>"
                        onclick="scdSwitchTab('<?php echo esc_js( $tabs_id ); ?>', <?php echo $index; ?>)"
                        style="
                            padding: 12px 20px;
                            border: none;
                            background: <?php echo $index === 0 ? '#fff' : 'transparent'; ?>;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: <?php echo $index === 0 ? '600' : '400'; ?>;
                            border-bottom: <?php echo $index === 0 ? '2px solid #0073aa' : 'none'; ?>;
                        ">
                    <?php echo esc_html( $tab['title'] ); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab Contents -->
        <?php foreach ( $scd_tabs_data as $index => $tab ) : ?>
            <div class="scd-tab-content"
                 data-tab="<?php echo $index; ?>"
                 style="
                    padding: 20px;
                    display: <?php echo $index === 0 ? 'block' : 'none'; ?>;
                 ">
                <?php echo wp_kses_post( $tab['content'] ); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function scdSwitchTab(tabsId, tabIndex) {
        var container = document.getElementById(tabsId);

        // Ẩn tất cả tab content
        var contents = container.querySelectorAll('.scd-tab-content');
        contents.forEach(function(el) { el.style.display = 'none'; });

        // Reset tất cả buttons
        var buttons = container.querySelectorAll('.scd-tab-button');
        buttons.forEach(function(el) {
            el.style.background = 'transparent';
            el.style.fontWeight = '400';
            el.style.borderBottom = 'none';
        });

        // Hiển thị tab được chọn
        container.querySelector('.scd-tab-content[data-tab="' + tabIndex + '"]').style.display = 'block';
        var activeBtn = container.querySelector('.scd-tab-button[data-tab="' + tabIndex + '"]');
        activeBtn.style.background = '#fff';
        activeBtn.style.fontWeight = '600';
        activeBtn.style.borderBottom = '2px solid #0073aa';
    }
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode( 'tab', 'scd_tab_shortcode' );

function scd_tab_shortcode( $atts, $content = null ) {
    global $scd_tabs_data;

    $atts = shortcode_atts( array(
        'title' => 'Tab',
    ), $atts, 'tab' );

    // Lưu data của tab vào mảng global
    $scd_tabs_data[] = array(
        'title'   => $atts['title'],
        'content' => do_shortcode( $content ),
    );

    // Không return gì cả - tabs shortcode sẽ render tất cả
    return '';
}
```

---

## 6. Widgets API

### Widget là gì?

Widget là các khối nội dung nhỏ có thể kéo thả vào **sidebar**, **footer**, hoặc bất kỳ **widget area** nào trong theme. Mỗi widget có form cài đặt riêng trong admin.

### So sánh với Laravel

```
Laravel:   View Component / Blade Component
WordPress: Widget (WP_Widget class)

Laravel:
  <x-sidebar-widget title="Recent Posts" :count="5" />

WordPress:
  Kéo thả widget vào sidebar area trong Admin > Appearance > Widgets
```

### WP_Widget Class - Cấu trúc

```php
<?php
/**
 * Mỗi Widget là 1 class kế thừa WP_Widget.
 * Cần override 3 methods chính:
 */
class My_Widget extends WP_Widget {

    /**
     * Constructor: Đăng ký widget
     */
    public function __construct() {
        parent::__construct(
            'my_widget',                  // Base ID (duy nhất)
            'Tên Widget',                 // Tên hiển thị
            array(                        // Tùy chọn
                'description' => 'Mô tả widget.',
                'classname'   => 'my-widget-class',    // CSS class cho wrapper
            )
        );
    }

    /**
     * Frontend: Hiển thị widget trên trang web
     *
     * @param array $args     Tham số từ widget area (before_widget, after_widget, etc.)
     * @param array $instance Giá trị settings của widget instance này
     */
    public function widget( $args, $instance ) {
        // $args['before_widget'] = '<div class="widget my-widget-class">'
        // $args['after_widget']  = '</div>'
        // $args['before_title']  = '<h2 class="widget-title">'
        // $args['after_title']   = '</h2>'

        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        // Nội dung widget
        echo '<p>Nội dung widget ở đây.</p>';

        echo $args['after_widget'];
    }

    /**
     * Admin Form: Form cài đặt trong admin
     *
     * @param array $instance Giá trị hiện tại
     */
    public function form( $instance ) {
        $title = $instance['title'] ?? 'Tiêu đề mặc định';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                Tiêu đề:
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    /**
     * Update: Xử lý khi lưu settings
     *
     * @param array $new_instance Giá trị mới từ form
     * @param array $old_instance Giá trị cũ
     * @return array Giá trị đã sanitize để lưu
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        return $instance;
    }
}

// Đăng ký widget
add_action( 'widgets_init', function() {
    register_widget( 'My_Widget' );
});
```

---

## 7. Tạo Widget tùy chỉnh

### Widget Thông tin liên hệ

```php
<?php
/**
 * Plugin Name: Custom Widgets Plugin
 * Description: Tạo các widget tùy chỉnh.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === WIDGET 1: Thông tin liên hệ ===

class CWP_Contact_Info_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'cwp_contact_info',
            'Thông tin liên hệ',
            array(
                'description' => 'Hiển thị thông tin liên hệ của công ty.',
                'classname'   => 'cwp-contact-info-widget',
            )
        );
    }

    /**
     * Frontend: Hiển thị widget
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        // Tiêu đề
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'];
            echo esc_html( $instance['title'] );
            echo $args['after_title'];
        }
        ?>
        <div class="cwp-contact-info">
            <?php if ( ! empty( $instance['address'] ) ) : ?>
                <p>
                    <strong>Địa chỉ:</strong><br>
                    <?php echo esc_html( $instance['address'] ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $instance['phone'] ) ) : ?>
                <p>
                    <strong>Điện thoại:</strong><br>
                    <a href="tel:<?php echo esc_attr( $instance['phone'] ); ?>">
                        <?php echo esc_html( $instance['phone'] ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $instance['email'] ) ) : ?>
                <p>
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo esc_attr( $instance['email'] ); ?>">
                        <?php echo esc_html( $instance['email'] ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $instance['hours'] ) ) : ?>
                <p>
                    <strong>Giờ làm việc:</strong><br>
                    <?php echo nl2br( esc_html( $instance['hours'] ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $instance['show_map'] ) && ! empty( $instance['map_embed'] ) ) : ?>
                <div class="cwp-map" style="margin-top:10px;">
                    <?php
                    // Chỉ cho phép iframe từ Google Maps
                    echo wp_kses( $instance['map_embed'], array(
                        'iframe' => array(
                            'src'             => array(),
                            'width'           => array(),
                            'height'          => array(),
                            'style'           => array(),
                            'frameborder'     => array(),
                            'allowfullscreen' => array(),
                            'loading'         => array(),
                        ),
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        echo $args['after_widget'];
    }

    /**
     * Admin Form: Các trường nhập liệu
     */
    public function form( $instance ) {
        $defaults = array(
            'title'     => 'Liên hệ',
            'address'   => '',
            'phone'     => '',
            'email'     => '',
            'hours'     => '',
            'show_map'  => false,
            'map_embed' => '',
        );
        $instance = wp_parse_args( $instance, $defaults );
        ?>
        <!-- Title -->
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Tiêu đề:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <!-- Address -->
        <p>
            <label for="<?php echo $this->get_field_id( 'address' ); ?>">Địa chỉ:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'address' ); ?>"
                   name="<?php echo $this->get_field_name( 'address' ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $instance['address'] ); ?>">
        </p>

        <!-- Phone -->
        <p>
            <label for="<?php echo $this->get_field_id( 'phone' ); ?>">Số điện thoại:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'phone' ); ?>"
                   name="<?php echo $this->get_field_name( 'phone' ); ?>"
                   type="tel"
                   value="<?php echo esc_attr( $instance['phone'] ); ?>">
        </p>

        <!-- Email -->
        <p>
            <label for="<?php echo $this->get_field_id( 'email' ); ?>">Email:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'email' ); ?>"
                   name="<?php echo $this->get_field_name( 'email' ); ?>"
                   type="email"
                   value="<?php echo esc_attr( $instance['email'] ); ?>">
        </p>

        <!-- Working Hours -->
        <p>
            <label for="<?php echo $this->get_field_id( 'hours' ); ?>">Giờ làm việc:</label>
            <textarea class="widefat" rows="3"
                      id="<?php echo $this->get_field_id( 'hours' ); ?>"
                      name="<?php echo $this->get_field_name( 'hours' ); ?>"
            ><?php echo esc_textarea( $instance['hours'] ); ?></textarea>
            <small>Ví dụ: Thứ 2 - Thứ 6: 8:00 - 17:00</small>
        </p>

        <!-- Show Map -->
        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_map' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_map' ); ?>"
                   value="1"
                   <?php checked( $instance['show_map'], true ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_map' ); ?>">Hiển thị bản đồ</label>
        </p>

        <!-- Map Embed Code -->
        <p>
            <label for="<?php echo $this->get_field_id( 'map_embed' ); ?>">Google Maps Embed:</label>
            <textarea class="widefat" rows="3"
                      id="<?php echo $this->get_field_id( 'map_embed' ); ?>"
                      name="<?php echo $this->get_field_name( 'map_embed' ); ?>"
                      placeholder='<iframe src="https://www.google.com/maps/embed?..."></iframe>'
            ><?php echo esc_textarea( $instance['map_embed'] ); ?></textarea>
        </p>
        <?php
    }

    /**
     * Update: Sanitize và lưu
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']     = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['address']   = sanitize_text_field( $new_instance['address'] ?? '' );
        $instance['phone']     = sanitize_text_field( $new_instance['phone'] ?? '' );
        $instance['email']     = sanitize_email( $new_instance['email'] ?? '' );
        $instance['hours']     = sanitize_textarea_field( $new_instance['hours'] ?? '' );
        $instance['show_map']  = ! empty( $new_instance['show_map'] );
        $instance['map_embed'] = wp_kses( $new_instance['map_embed'] ?? '', array(
            'iframe' => array(
                'src'             => array(),
                'width'           => array(),
                'height'          => array(),
                'style'           => array(),
                'frameborder'     => array(),
                'allowfullscreen' => array(),
                'loading'         => array(),
            ),
        ));
        return $instance;
    }
}

// === WIDGET 2: Social Links ===

class CWP_Social_Links_Widget extends WP_Widget {

    private $networks = array(
        'facebook'  => 'Facebook',
        'twitter'   => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
        'linkedin'  => 'LinkedIn',
        'tiktok'    => 'TikTok',
        'github'    => 'GitHub',
    );

    public function __construct() {
        parent::__construct(
            'cwp_social_links',
            'Liên kết mạng xã hội',
            array(
                'description' => 'Hiển thị các icon mạng xã hội.',
                'classname'   => 'cwp-social-links-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        $style = $instance['style'] ?? 'icon';
        ?>
        <div class="cwp-social-links" style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach ( $this->networks as $key => $label ) :
                if ( empty( $instance[ $key ] ) ) continue;
                $url = esc_url( $instance[ $key ] );
                ?>
                <a href="<?php echo $url; ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="<?php echo esc_attr( $label ); ?>"
                   style="
                       display: inline-flex;
                       align-items: center;
                       justify-content: center;
                       width: <?php echo $style === 'icon' ? '40px' : 'auto'; ?>;
                       height: 40px;
                       background: #333;
                       color: #fff;
                       border-radius: <?php echo $style === 'icon' ? '50%' : '4px'; ?>;
                       text-decoration: none;
                       font-size: 14px;
                       padding: <?php echo $style === 'icon' ? '0' : '0 15px'; ?>;
                   ">
                    <?php if ( $style === 'text' ) : ?>
                        <?php echo esc_html( $label ); ?>
                    <?php else : ?>
                        <?php echo esc_html( strtoupper( substr( $label, 0, 2 ) ) ); ?>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? 'Kết nối với chúng tôi';
        $style = $instance['style'] ?? 'icon';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Tiêu đề:</label>
            <input class="widefat" type="text"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'style' ); ?>">Kiểu hiển thị:</label>
            <select class="widefat"
                    id="<?php echo $this->get_field_id( 'style' ); ?>"
                    name="<?php echo $this->get_field_name( 'style' ); ?>">
                <option value="icon" <?php selected( $style, 'icon' ); ?>>Icon tròn</option>
                <option value="text" <?php selected( $style, 'text' ); ?>>Hiển thị tên</option>
            </select>
        </p>

        <hr>
        <p><strong>Nhập URL mạng xã hội:</strong></p>

        <?php foreach ( $this->networks as $key => $label ) :
            $value = $instance[ $key ] ?? '';
            ?>
            <p>
                <label for="<?php echo $this->get_field_id( $key ); ?>">
                    <?php echo esc_html( $label ); ?>:
                </label>
                <input class="widefat" type="url"
                       id="<?php echo $this->get_field_id( $key ); ?>"
                       name="<?php echo $this->get_field_name( $key ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                       placeholder="https://">
            </p>
        <?php endforeach; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['style'] = in_array( $new_instance['style'] ?? '', array( 'icon', 'text' ) )
            ? $new_instance['style'] : 'icon';

        foreach ( $this->networks as $key => $label ) {
            $instance[ $key ] = esc_url_raw( $new_instance[ $key ] ?? '' );
        }

        return $instance;
    }
}

// === WIDGET 3: Bài viết nổi bật ===

class CWP_Featured_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'cwp_featured_posts',
            'Bài viết nổi bật',
            array(
                'description' => 'Hiển thị danh sách bài viết nổi bật với thumbnail.',
                'classname'   => 'cwp-featured-posts-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        $count = max( 1, intval( $instance['count'] ?? 5 ) );
        $category = $instance['category'] ?? '';

        $query_args = array(
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'orderby'        => $instance['orderby'] ?? 'date',
            'order'          => 'DESC',
        );

        if ( ! empty( $category ) ) {
            $query_args['cat'] = intval( $category );
        }

        if ( ! empty( $instance['show_thumbnail'] ) ) {
            $query_args['meta_key'] = '_thumbnail_id';  // Chỉ lấy bài có thumbnail
        }

        $query = new WP_Query( $query_args );

        if ( $query->have_posts() ) :
            echo '<ul style="list-style:none; padding:0; margin:0;">';
            while ( $query->have_posts() ) : $query->the_post();
                ?>
                <li style="display:flex; gap:10px; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #eee;">
                    <?php if ( ! empty( $instance['show_thumbnail'] ) && has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" style="flex-shrink:0;">
                            <?php the_post_thumbnail( array( 60, 60 ), array(
                                'style' => 'width:60px; height:60px; object-fit:cover; border-radius:4px;'
                            )); ?>
                        </a>
                    <?php endif; ?>
                    <div>
                        <a href="<?php the_permalink(); ?>" style="
                            text-decoration:none;
                            color:#333;
                            font-weight:600;
                            font-size:14px;
                            line-height:1.3;
                        ">
                            <?php the_title(); ?>
                        </a>
                        <?php if ( ! empty( $instance['show_date'] ) ) : ?>
                            <span style="display:block; color:#999; font-size:12px; margin-top:3px;">
                                <?php echo get_the_date(); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php
            endwhile;
            echo '</ul>';
            wp_reset_postdata();
        else :
            echo '<p>Không có bài viết.</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $defaults = array(
            'title'          => 'Bài viết nổi bật',
            'count'          => 5,
            'category'       => '',
            'orderby'        => 'date',
            'show_thumbnail' => true,
            'show_date'      => true,
        );
        $instance = wp_parse_args( $instance, $defaults );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Tiêu đề:</label>
            <input class="widefat" type="text"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'count' ); ?>">Số lượng bài viết:</label>
            <input class="tiny-text" type="number" min="1" max="20"
                   id="<?php echo $this->get_field_id( 'count' ); ?>"
                   name="<?php echo $this->get_field_name( 'count' ); ?>"
                   value="<?php echo esc_attr( $instance['count'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'category' ); ?>">Chuyên mục:</label>
            <?php
            // wp_dropdown_categories tao dropdown tu dong tu danh sach categories
            wp_dropdown_categories( array(
                'show_option_all' => '-- Tất cả --',
                'selected'        => $instance['category'],
                'name'            => $this->get_field_name( 'category' ),
                'id'              => $this->get_field_id( 'category' ),
                'class'           => 'widefat',
                'hide_empty'      => false,
            ));
            ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'orderby' ); ?>">Sắp xếp theo:</label>
            <select class="widefat"
                    id="<?php echo $this->get_field_id( 'orderby' ); ?>"
                    name="<?php echo $this->get_field_name( 'orderby' ); ?>">
                <option value="date" <?php selected( $instance['orderby'], 'date' ); ?>>Ngày mới nhất</option>
                <option value="comment_count" <?php selected( $instance['orderby'], 'comment_count' ); ?>>Nhiều bình luận</option>
                <option value="rand" <?php selected( $instance['orderby'], 'rand' ); ?>>Ngẫu nhiên</option>
            </select>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>"
                   value="1" <?php checked( $instance['show_thumbnail'] ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>">Hiển thị hình nhỏ</label>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_date' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_date' ); ?>"
                   value="1" <?php checked( $instance['show_date'] ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_date' ); ?>">Hiển thị ngày đăng</label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        return array(
            'title'          => sanitize_text_field( $new_instance['title'] ?? '' ),
            'count'          => max( 1, min( 20, intval( $new_instance['count'] ?? 5 ) ) ),
            'category'       => absint( $new_instance['category'] ?? 0 ),
            'orderby'        => in_array( $new_instance['orderby'] ?? '', array( 'date', 'comment_count', 'rand' ) )
                                ? $new_instance['orderby'] : 'date',
            'show_thumbnail' => ! empty( $new_instance['show_thumbnail'] ),
            'show_date'      => ! empty( $new_instance['show_date'] ),
        );
    }
}

// === ĐĂNG KÝ TẤT CẢ WIDGETS ===
add_action( 'widgets_init', 'cwp_register_widgets' );

function cwp_register_widgets() {
    register_widget( 'CWP_Contact_Info_Widget' );
    register_widget( 'CWP_Social_Links_Widget' );
    register_widget( 'CWP_Featured_Posts_Widget' );
}
```

---

## 8. Gutenberg Block vs Widget

### So sánh

| Đặc điểm | Classic Widget | Gutenberg Block |
|----------|---------------|-----------------|
| **Editor** | Widget panel (Appearance > Widgets) | Block Editor |
| **Công nghệ** | PHP (WP_Widget class) | JavaScript (React) + PHP |
| **Vị trí** | Chỉ sidebar/widget areas | Bất kỳ đâu trong nội dung |
| **Từ WP version** | 2.8+ | 5.0+ (2018) |
| **Tương lai** | Vẫn được hỗ trợ | **Được khuyên dùng** |
| **Độ phức tạp** | Thấp | Trung bình - Cao |

### Tạo Gutenberg Block đơn giản (không cần JSX)

```php
<?php
/**
 * Đăng ký 1 Block đơn giản chỉ bằng PHP (không cần build JS)
 * Từ WordPress 5.8+, dùng register_block_type với render_callback
 */
add_action( 'init', 'cwp_register_blocks' );

function cwp_register_blocks() {
    /**
     * Block đơn giản render phía server (Server-Side Rendering)
     * Cách này phù hợp khi bạn không muốn viết React
     */
    register_block_type( 'cwp/contact-info', array(
        // Attributes mà user có thể cấu hình
        'attributes' => array(
            'title' => array(
                'type'    => 'string',
                'default' => 'Liên hệ',
            ),
            'phone' => array(
                'type'    => 'string',
                'default' => '',
            ),
            'email' => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
        // Hàm render phía server
        'render_callback' => 'cwp_render_contact_block',
    ));
}

function cwp_render_contact_block( $attributes ) {
    ob_start();
    ?>
    <div class="cwp-contact-block" style="
        background: #f9f9f9;
        padding: 20px;
        border-radius: 5px;
        border-left: 4px solid #0073aa;
    ">
        <?php if ( ! empty( $attributes['title'] ) ) : ?>
            <h3 style="margin-top:0;"><?php echo esc_html( $attributes['title'] ); ?></h3>
        <?php endif; ?>

        <?php if ( ! empty( $attributes['phone'] ) ) : ?>
            <p>Điện thoại: <a href="tel:<?php echo esc_attr( $attributes['phone'] ); ?>">
                <?php echo esc_html( $attributes['phone'] ); ?></a></p>
        <?php endif; ?>

        <?php if ( ! empty( $attributes['email'] ) ) : ?>
            <p>Email: <a href="mailto:<?php echo esc_attr( $attributes['email'] ); ?>">
                <?php echo esc_html( $attributes['email'] ); ?></a></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

### Khuyến nghị

```
Khi nào dùng Widget:
- Cần tương thích với WordPress cũ (trước 5.0)
- Plugin đơn giản, chỉ hiển thị ở sidebar
- Không muốn viết JavaScript phức tạp

Khi nào dùng Block:
- Phiên bản WordPress 5.0+
- Nội dung cần đặt ở bất kỳ đâu trong bài viết
- Muốn trải nghiệm editor tốt hơn
- Plugin mới, hướng tới tương lai
```

---

## 9. Code ví dụ đầy đủ

### Plugin hoàn chỉnh: Shortcodes + Widgets

```php
<?php
/**
 * Plugin Name:       Content Elements Plugin
 * Description:       Bộ sưu tập Shortcodes và Widgets cho website.
 * Version:           1.0.0
 * Author:            Developer
 * Text Domain:       content-elements
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CEP_VERSION', '1.0.0' );
define( 'CEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// === SHORTCODES ===

class CEP_Shortcodes {

    public function __construct() {
        add_shortcode( 'cep_accordion', array( $this, 'accordion' ) );
        add_shortcode( 'cep_item', array( $this, 'accordion_item' ) );
        add_shortcode( 'cep_pricing', array( $this, 'pricing_table' ) );
        add_shortcode( 'cep_cta', array( $this, 'call_to_action' ) );
        add_shortcode( 'cep_counter', array( $this, 'counter' ) );
        add_shortcode( 'cep_testimonial', array( $this, 'testimonial' ) );
    }

    // --- Accordion ---
    private $accordion_items = array();

    public function accordion( $atts, $content = null ) {
        $this->accordion_items = array();
        do_shortcode( $content ); // Xử lý các [cep_item] bên trong

        if ( empty( $this->accordion_items ) ) return '';

        $acc_id = 'cep-acc-' . wp_rand( 1000, 9999 );
        ob_start();
        ?>
        <div class="cep-accordion" id="<?php echo esc_attr( $acc_id ); ?>"
             style="margin:20px 0; border:1px solid #ddd; border-radius:5px; overflow:hidden;">
            <?php foreach ( $this->accordion_items as $i => $item ) : ?>
                <div class="cep-acc-item">
                    <div class="cep-acc-header"
                         onclick="cepToggleAcc(this)"
                         style="
                            padding: 15px 20px;
                            background: #f5f5f5;
                            cursor: pointer;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 1px solid #ddd;
                            font-weight: 600;
                         ">
                        <?php echo esc_html( $item['title'] ); ?>
                        <span class="cep-acc-arrow" style="transition:transform 0.3s;">&#9660;</span>
                    </div>
                    <div class="cep-acc-body"
                         style="padding:15px 20px; display:<?php echo $i === 0 ? 'block' : 'none'; ?>;">
                        <?php echo wp_kses_post( $item['content'] ); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        function cepToggleAcc(header) {
            var body = header.nextElementSibling;
            var arrow = header.querySelector('.cep-acc-arrow');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                arrow.style.transform = 'rotate(180deg)';
            } else {
                body.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public function accordion_item( $atts, $content = null ) {
        $atts = shortcode_atts( array( 'title' => 'Item' ), $atts );
        $this->accordion_items[] = array(
            'title'   => $atts['title'],
            'content' => do_shortcode( $content ),
        );
        return '';
    }

    // --- Pricing Table ---
    public function pricing_table( $atts ) {
        $atts = shortcode_atts( array(
            'name'     => 'Basic',
            'price'    => '0',
            'currency' => '$',
            'period'   => '/thang',
            'features' => '',             // Phân cách bằng |
            'url'      => '#',
            'label'    => 'Đăng ký ngay',
            'featured' => 'false',
        ), $atts, 'cep_pricing' );

        $features = array_filter( explode( '|', $atts['features'] ) );
        $is_featured = $atts['featured'] === 'true';

        ob_start();
        ?>
        <div class="cep-pricing" style="
            border: <?php echo $is_featured ? '2px solid #0073aa' : '1px solid #ddd'; ?>;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            max-width: 300px;
            margin: 20px auto;
            background: <?php echo $is_featured ? '#f0f7ff' : '#fff'; ?>;
            position: relative;
        ">
            <?php if ( $is_featured ) : ?>
                <span style="
                    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
                    background: #0073aa; color: #fff; padding: 3px 15px; border-radius: 10px;
                    font-size: 12px;
                ">Phổ biến nhất</span>
            <?php endif; ?>

            <h3 style="margin-top:10px;"><?php echo esc_html( $atts['name'] ); ?></h3>
            <div style="font-size:40px; font-weight:bold; margin:15px 0; color:#0073aa;">
                <?php echo esc_html( $atts['currency'] . $atts['price'] ); ?>
                <span style="font-size:16px; color:#666;"><?php echo esc_html( $atts['period'] ); ?></span>
            </div>

            <?php if ( ! empty( $features ) ) : ?>
                <ul style="list-style:none; padding:0; margin:20px 0; text-align:left;">
                    <?php foreach ( $features as $feature ) : ?>
                        <li style="padding:8px 0; border-bottom:1px solid #eee;">
                            &#10003; <?php echo esc_html( trim( $feature ) ); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="<?php echo esc_url( $atts['url'] ); ?>"
               style="
                   display: inline-block; width: 100%; box-sizing: border-box;
                   background: #0073aa; color: #fff; padding: 12px; border-radius: 5px;
                   text-decoration: none; font-size: 16px; font-weight: 600;
               ">
                <?php echo esc_html( $atts['label'] ); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // --- Call to Action ---
    public function call_to_action( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'title'    => '',
            'btn_text' => 'Tìm hiểu thêm',
            'btn_url'  => '#',
            'bg_color' => '#0073aa',
        ), $atts, 'cep_cta' );

        $content = do_shortcode( $content );

        ob_start();
        ?>
        <div class="cep-cta" style="
            background: <?php echo esc_attr( $atts['bg_color'] ); ?>;
            color: #fff; padding: 40px; text-align: center;
            border-radius: 8px; margin: 20px 0;
        ">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h2 style="margin:0 0 10px; color:#fff;"><?php echo esc_html( $atts['title'] ); ?></h2>
            <?php endif; ?>
            <?php if ( $content ) : ?>
                <p style="font-size:18px; margin:0 0 20px; opacity:0.9;"><?php echo wp_kses_post( $content ); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url( $atts['btn_url'] ); ?>"
               style="
                   display:inline-block; background:#fff;
                   color:<?php echo esc_attr( $atts['bg_color'] ); ?>;
                   padding:12px 35px; border-radius:5px;
                   text-decoration:none; font-weight:600; font-size:16px;
               ">
                <?php echo esc_html( $atts['btn_text'] ); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // --- Counter ---
    public function counter( $atts ) {
        $atts = shortcode_atts( array(
            'number' => '100',
            'suffix' => '+',
            'label'  => 'Items',
            'color'  => '#0073aa',
        ), $atts, 'cep_counter' );

        return sprintf(
            '<div class="cep-counter" style="text-align:center; padding:20px;">
                <div style="font-size:48px; font-weight:bold; color:%s;">%s%s</div>
                <div style="font-size:16px; color:#666; margin-top:5px;">%s</div>
            </div>',
            esc_attr( $atts['color'] ),
            esc_html( $atts['number'] ),
            esc_html( $atts['suffix'] ),
            esc_html( $atts['label'] )
        );
    }

    // --- Testimonial ---
    public function testimonial( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'name'     => '',
            'title'    => '',
            'image'    => '',
            'rating'   => '5',
        ), $atts, 'cep_testimonial' );

        $stars = str_repeat( '&#9733;', min( 5, max( 0, intval( $atts['rating'] ) ) ) );
        $content = do_shortcode( $content );

        ob_start();
        ?>
        <div class="cep-testimonial" style="
            background: #f9f9f9; padding: 25px; border-radius: 8px;
            margin: 20px 0; max-width: 500px;
        ">
            <div style="color:#f4a623; font-size:20px; margin-bottom:10px;">
                <?php echo $stars; ?>
            </div>
            <p style="font-style:italic; font-size:16px; line-height:1.6; margin:0 0 15px;">
                &ldquo;<?php echo wp_kses_post( $content ); ?>&rdquo;
            </p>
            <div style="display:flex; align-items:center; gap:12px;">
                <?php if ( ! empty( $atts['image'] ) ) : ?>
                    <img src="<?php echo esc_url( $atts['image'] ); ?>"
                         alt="<?php echo esc_attr( $atts['name'] ); ?>"
                         style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
                <?php endif; ?>
                <div>
                    <strong><?php echo esc_html( $atts['name'] ); ?></strong>
                    <?php if ( ! empty( $atts['title'] ) ) : ?>
                        <br><small style="color:#666;"><?php echo esc_html( $atts['title'] ); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Khởi tạo Shortcodes
new CEP_Shortcodes();

// === ĐĂNG KÝ WIDGETS (xem các class Widget phía trên) ===
add_action( 'widgets_init', function() {
    register_widget( 'CWP_Contact_Info_Widget' );
    register_widget( 'CWP_Social_Links_Widget' );
    register_widget( 'CWP_Featured_Posts_Widget' );
});

// Sử dụng shortcodes:
//
// [cep_accordion]
//   [cep_item title="Câu hỏi 1"]Trả lời cho câu hỏi 1[/cep_item]
//   [cep_item title="Câu hỏi 2"]Trả lời cho câu hỏi 2[/cep_item]
// [/cep_accordion]
//
// [cep_pricing name="Pro" price="29" features="10 Users|50GB Storage|Email Support" featured="true"]
//
// [cep_cta title="Bắt đầu ngay hôm nay" btn_text="Đăng ký miễn phí" btn_url="/register"]
//   Tham gia cùng hàng nghìn người dùng khác!
// [/cep_cta]
//
// [cep_counter number="1500" label="Khách hàng"]
//
// [cep_testimonial name="Nguyễn Văn A" title="CEO Công ty X" rating="5"]
//   Sản phẩm tuyệt vời, tôi rất hài lòng với dịch vụ!
// [/cep_testimonial]
```

---

## 10. Best Practices

### Shortcodes

```php
<?php
// 1. Luôn RETURN, không ECHO
// SAI:
add_shortcode( 'bad', function() {
    echo '<p>Nội dung</p>'; // Sẽ xuất hiện sai vị trí!
});

// DUNG:
add_shortcode( 'good', function() {
    return '<p>Nội dung</p>';
});

// 2. Dùng output buffering khi cần HTML phức tạp
add_shortcode( 'complex', function() {
    ob_start();
    ?>
    <div class="complex-layout">
        <!-- HTML phức tạp ở đây -->
    </div>
    <?php
    return ob_get_clean();
});

// 3. Luôn dùng shortcode_atts cho attributes
add_shortcode( 'safe', function( $atts ) {
    $atts = shortcode_atts( array(
        'default1' => 'value1',
        'default2' => 'value2',
    ), $atts, 'safe' ); // Tham số 3 cho phép filter
    // ...
});

// 4. Dùng do_shortcode() cho nested content
function my_wrapper( $atts, $content = null ) {
    return '<div class="wrapper">' . do_shortcode( $content ) . '</div>';
}

// 5. Không đặt shortcode trong thẻ <title> hay attribute
// Shortcode chỉ hoạt động trong the_content, the_excerpt, và text widgets

// 6. Xử lý khi không có attributes
add_shortcode( 'flexible', function( $atts ) {
    // $atts có thể là string rỗng '' khi không có attributes
    $atts = shortcode_atts( array(
        'param' => 'default',
    ), $atts ?: array(), 'flexible' );
});
```

### Widgets

```php
<?php
// 1. Luôn kế thừa WP_Widget
// 2. Override đủ 3 methods: widget(), form(), update()
// 3. Luôn sanitize trong update()
// 4. Luôn escape trong widget() và form()
// 5. Dùng $this->get_field_id() và $this->get_field_name() cho form fields
// 6. Dùng wp_parse_args() cho default values
// 7. Reset postdata sau WP_Query: wp_reset_postdata()

// 8. Đăng ký Widget area (trong theme)
add_action( 'widgets_init', function() {
    register_sidebar( array(
        'name'          => 'Footer Widget Area',
        'id'            => 'footer-widgets',
        'description'   => 'Widgets hiển thị ở footer.',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
});
```

---

## Tham khảo

- [WordPress Shortcode API](https://developer.wordpress.org/plugins/shortcodes/)
- [WordPress Widgets API](https://developer.wordpress.org/plugins/widgets/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [shortcode_atts()](https://developer.wordpress.org/reference/functions/shortcode_atts/)
- [WP_Widget Class](https://developer.wordpress.org/reference/classes/wp_widget/)
