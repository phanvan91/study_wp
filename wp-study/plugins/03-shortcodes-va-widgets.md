# Shortcodes va Widgets trong WordPress Plugin

## Muc luc

1. [Shortcodes co ban](#1-shortcodes-co-ban)
2. [Shortcode voi Attributes](#2-shortcode-voi-attributes)
3. [Shortcode voi Enclosed Content](#3-shortcode-voi-enclosed-content)
4. [Shortcode voi Form](#4-shortcode-voi-form)
5. [Shortcode long nhau (Nested)](#5-shortcode-long-nhau-nested)
6. [Widgets API](#6-widgets-api)
7. [Tao Widget tuy chinh](#7-tao-widget-tuy-chinh)
8. [Gutenberg Block vs Widget](#8-gutenberg-block-vs-widget)
9. [Code vi du day du](#9-code-vi-du-day-du)
10. [Best Practices](#10-best-practices)

---

## 1. Shortcodes co ban

### Shortcode la gi?

Shortcode la **ma tat** dat trong ngoac vuong `[]` cho phep nguoi dung chen noi dung dong vao bai viet, trang, hoac widget. Shortcode duoc xu ly phia server va tra ve HTML.

```
Nguoi dung viet:     [my_shortcode]
WordPress xu ly:     goi ham callback
Ket qua:             <div class="my-output">Noi dung</div>
```

### So sanh voi Laravel

```
Laravel:   Blade Component   @component('alert') hoac <x-alert />
WordPress: Shortcode         [alert]
```

### Tao Shortcode don gian

```php
<?php
/**
 * Plugin Name: Shortcode Demo
 * Description: Demo cac loai shortcode.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * add_shortcode() - Dang ky 1 shortcode
 *
 * @param string   $tag      Ten shortcode (dat trong [])
 * @param callable $callback Ham xu ly, PHAI return (khong echo)
 */
add_shortcode( 'hello', 'scd_hello_shortcode' );

/**
 * Shortcode don gian nhat: [hello]
 * Tra ve chuoi "Xin chao!"
 *
 * LUU Y QUAN TRONG: Shortcode callback PHAI return, KHONG duoc echo.
 * Neu echo, noi dung se xuat hien sai vi tri (tren cung trang).
 */
function scd_hello_shortcode() {
    return '<p style="color: green; font-weight: bold;">Xin chao tu Shortcode!</p>';
}

// Su dung trong bai viet:
// [hello]

// Shortcode hien thi nam hien tai: [current_year]
add_shortcode( 'current_year', function() {
    return date( 'Y' );
});

// Su dung: Ban quyen &copy; [current_year] Cong ty ABC
// Ket qua: Ban quyen (c) 2024 Cong ty ABC
```

---

## 2. Shortcode voi Attributes

### shortcode_atts() - Xu ly thuoc tinh

```php
<?php
/**
 * Shortcode voi thuoc tinh (attributes):
 * [button text="Click me" url="https://example.com" color="blue" size="large"]
 */
add_shortcode( 'button', 'scd_button_shortcode' );

/**
 * @param array  $atts    Cac thuoc tinh nguoi dung truyen vao
 * @param string $content Noi dung giua the mo va dong (null neu self-closing)
 * @param string $tag     Ten shortcode ('button')
 */
function scd_button_shortcode( $atts, $content = null, $tag = '' ) {
    /**
     * shortcode_atts() - Gop thuoc tinh mac dinh voi thuoc tinh nguoi dung
     *
     * @param array  $defaults  Gia tri mac dinh
     * @param array  $atts      Gia tri nguoi dung truyen
     * @param string $shortcode Ten shortcode (cho filter)
     *
     * Hoat dong: Neu nguoi dung KHONG truyen attribute,
     * dung gia tri mac dinh. Neu co truyen, dung gia tri cua nguoi dung.
     */
    $atts = shortcode_atts( array(
        'text'   => 'Click here',           // Mac dinh neu khong truyen
        'url'    => '#',
        'color'  => 'blue',                 // blue, green, red, orange
        'size'   => 'medium',               // small, medium, large
        'target' => '_self',                // _self, _blank
        'class'  => '',                     // CSS class tuy chinh
    ), $atts, 'button' );                   // 'button' = ten shortcode

    // Xac dinh styles dua tren attributes
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

    // Tra ve HTML
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

// Su dung:
// [button]                                          => Nut mac dinh
// [button text="Mua ngay" color="green" size="large"]
// [button text="Xem chi tiet" url="/san-pham" target="_blank"]
```

### Shortcode hien thi danh sach posts

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

    // Xay dung query args
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
        'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
        'post_status'    => 'publish',
    );

    // Them category neu co
    if ( ! empty( $atts['category'] ) ) {
        $args['category_name'] = sanitize_text_field( $atts['category'] );
    }

    $query = new WP_Query( $args );

    // Bat dau output buffering
    // Vi shortcode phai return, ta dung ob_start/ob_get_clean
    // de viet HTML tu nhien hon (thay vi noi chuoi)
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
        echo '<p>Khong co bai viet nao.</p>';
    endif;

    // QUAN TRONG: Reset post data sau khi dung WP_Query tuy chinh
    wp_reset_postdata();

    // Lay noi dung tu buffer va return
    return ob_get_clean();
}

// Su dung:
// [recent_posts]
// [recent_posts count="3" columns="3"]
// [recent_posts count="6" category="tin-tuc" columns="2"]
```

---

## 3. Shortcode voi Enclosed Content

### Shortcode bao quanh noi dung

```php
<?php
/**
 * Shortcode co the bao quanh noi dung:
 * [highlight color="yellow"]Noi dung can highlight[/highlight]
 *
 * $content chua noi dung giua [shortcode] va [/shortcode]
 */
add_shortcode( 'highlight', 'scd_highlight_shortcode' );

function scd_highlight_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'color' => 'yellow',
        'style' => 'inline',   // inline hoac block
    ), $atts, 'highlight' );

    // Xu ly noi dung ben trong (cho phep shortcode long nhau)
    // do_shortcode() xu ly cac shortcode nam trong $content
    $content = do_shortcode( $content );

    $display = $atts['style'] === 'block' ? 'display:block; padding:15px;' : 'padding:2px 5px;';

    return sprintf(
        '<span class="scd-highlight" style="background-color:%s; %s border-radius:3px;">%s</span>',
        esc_attr( $atts['color'] ),
        $display,
        wp_kses_post( $content )  // Cho phep HTML an toan
    );
}

// [highlight]Noi dung quan trong[/highlight]
// [highlight color="#ff0" style="block"]
//     <strong>Chu y:</strong> Day la noi dung quan trong.
// [/highlight]
```

### Shortcode Alert Box

```php
<?php
/**
 * [alert type="warning"]Noi dung canh bao[/alert]
 * [alert type="success"]Thao tac thanh cong![/alert]
 * [alert type="error"]Co loi xay ra![/alert]
 * [alert type="info"]Thong tin tham khao.[/alert]
 */
add_shortcode( 'alert', 'scd_alert_shortcode' );

function scd_alert_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'type'        => 'info',      // info, success, warning, error
        'dismissible' => 'false',     // true/false
        'icon'        => 'true',      // true/false
    ), $atts, 'alert' );

    // Mau sac va icon theo type
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

// Su dung:
// [alert type="info"]Day la thong tin tham khao.[/alert]
// [alert type="warning" dismissible="true"]Canh bao! Co the dong lai duoc.[/alert]
// [alert type="error"]Loi: Khong the ket noi database![/alert]
// [alert type="success" icon="false"]Thanh cong![/alert]
```

---

## 4. Shortcode voi Form

```php
<?php
/**
 * Shortcode tao form lien he:
 * [contact_form email="admin@example.com" subject="Lien he tu website"]
 */
add_shortcode( 'contact_form', 'scd_contact_form_shortcode' );

function scd_contact_form_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'email'   => get_option( 'admin_email' ),
        'subject' => 'Lien he tu ' . get_bloginfo( 'name' ),
        'success' => 'Cam on ban! Tin nhan da duoc gui thanh cong.',
    ), $atts, 'contact_form' );

    $message = '';
    $form_data = array( 'name' => '', 'email' => '', 'phone' => '', 'message' => '' );

    // Xu ly form khi submit
    if ( isset( $_POST['scd_contact_submit'] ) ) {
        // Kiem tra nonce
        if ( ! wp_verify_nonce( $_POST['scd_contact_nonce'] ?? '', 'scd_contact_action' ) ) {
            $message = '<div class="scd-alert" style="background:#ffdddd; padding:10px; margin:10px 0;">
                Loi bao mat! Vui long thu lai.</div>';
        } else {
            // Lay va sanitize du lieu
            $form_data['name']    = sanitize_text_field( $_POST['scd_name'] ?? '' );
            $form_data['email']   = sanitize_email( $_POST['scd_email'] ?? '' );
            $form_data['phone']   = sanitize_text_field( $_POST['scd_phone'] ?? '' );
            $form_data['message'] = sanitize_textarea_field( $_POST['scd_message'] ?? '' );

            // Validate
            $errors = array();
            if ( empty( $form_data['name'] ) ) {
                $errors[] = 'Vui long nhap ho ten.';
            }
            if ( ! is_email( $form_data['email'] ) ) {
                $errors[] = 'Email khong hop le.';
            }
            if ( empty( $form_data['message'] ) ) {
                $errors[] = 'Vui long nhap noi dung tin nhan.';
            }

            if ( ! empty( $errors ) ) {
                $message = '<div style="background:#ffdddd; padding:10px; margin:10px 0; border-radius:4px;">';
                foreach ( $errors as $error ) {
                    $message .= '<p style="margin:5px 0; color:#d63638;">' . esc_html( $error ) . '</p>';
                }
                $message .= '</div>';
            } else {
                // Gui email
                $email_body = sprintf(
                    "Ho ten: %s\nEmail: %s\nSo dien thoai: %s\n\nNoi dung:\n%s",
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
                    // Reset form data sau khi gui thanh cong
                    $form_data = array( 'name' => '', 'email' => '', 'phone' => '', 'message' => '' );
                } else {
                    $message = '<div style="background:#ffdddd; padding:15px; margin:10px 0; border-radius:4px;">
                        <p style="margin:0; color:#d63638;">Co loi khi gui email. Vui long thu lai.</p>
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
                    Ho ten <span style="color:red;">*</span>
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
                    So dien thoai
                </label>
                <input type="tel" id="scd_phone" name="scd_phone"
                       value="<?php echo esc_attr( $form_data['phone'] ); ?>"
                       style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px;">
            </div>

            <div>
                <label for="scd_message" style="display:block; margin-bottom:5px; font-weight:600;">
                    Noi dung <span style="color:red;">*</span>
                </label>
                <textarea id="scd_message" name="scd_message" rows="5" required
                          style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px;"
                ><?php echo esc_textarea( $form_data['message'] ); ?></textarea>
            </div>

            <div>
                <button type="submit" name="scd_contact_submit" value="1"
                        style="background:#0073aa; color:#fff; border:none; padding:12px 30px;
                               border-radius:4px; cursor:pointer; font-size:16px;">
                    Gui tin nhan
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
```

---

## 5. Shortcode long nhau (Nested)

```php
<?php
/**
 * Shortcodes long nhau de tao layout phuc tap.
 *
 * [row]
 *   [column width="6"]Noi dung cot 1[/column]
 *   [column width="6"]Noi dung cot 2[/column]
 * [/row]
 *
 * Dung he thong 12 columns (giong Bootstrap grid).
 */

// === ROW Shortcode ===
add_shortcode( 'row', 'scd_row_shortcode' );

function scd_row_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'gap'   => '20px',
        'class' => '',
    ), $atts, 'row' );

    // do_shortcode() de xu ly [column] ben trong
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
        'width' => '6',        // 1-12 (he thong 12 cot)
        'class' => '',
    ), $atts, 'column' );

    $width = max( 1, min( 12, intval( $atts['width'] ) ) );
    // Tinh % dua tren 12 columns, tru gap
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

// Su dung:
// [row]
//   [column width="4"]
//     <h3>Cot 1</h3>
//     <p>Chiem 1/3 chieu rong</p>
//   [/column]
//   [column width="4"]
//     <h3>Cot 2</h3>
//     <p>Chiem 1/3 chieu rong</p>
//   [/column]
//   [column width="4"]
//     <h3>Cot 3</h3>
//     <p>Chiem 1/3 chieu rong</p>
//   [/column]
// [/row]
```

### Tabs Shortcode

```php
<?php
/**
 * [tabs]
 *   [tab title="Tab 1"]Noi dung tab 1[/tab]
 *   [tab title="Tab 2"]Noi dung tab 2[/tab]
 *   [tab title="Tab 3"]Noi dung tab 3[/tab]
 * [/tabs]
 */

// Bien global tam de luu data cac tab
$scd_tabs_data = array();

add_shortcode( 'tabs', 'scd_tabs_shortcode' );

function scd_tabs_shortcode( $atts, $content = null ) {
    global $scd_tabs_data;
    $scd_tabs_data = array(); // Reset

    // do_shortcode de xu ly cac [tab] ben trong
    // Moi [tab] se push data vao $scd_tabs_data
    do_shortcode( $content );

    if ( empty( $scd_tabs_data ) ) {
        return '';
    }

    // Tao ID duy nhat cho moi tabs instance
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

        // An tat ca tab content
        var contents = container.querySelectorAll('.scd-tab-content');
        contents.forEach(function(el) { el.style.display = 'none'; });

        // Reset tat ca buttons
        var buttons = container.querySelectorAll('.scd-tab-button');
        buttons.forEach(function(el) {
            el.style.background = 'transparent';
            el.style.fontWeight = '400';
            el.style.borderBottom = 'none';
        });

        // Hien thi tab duoc chon
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

    // Luu data cua tab vao mang global
    $scd_tabs_data[] = array(
        'title'   => $atts['title'],
        'content' => do_shortcode( $content ),
    );

    // Khong return gi ca - tabs shortcode se render tat ca
    return '';
}
```

---

## 6. Widgets API

### Widget la gi?

Widget la cac khoi noi dung nho co the keo tha vao **sidebar**, **footer**, hoac bat ky **widget area** nao trong theme. Moi widget co form cai dat rieng trong admin.

### So sanh voi Laravel

```
Laravel:   View Component / Blade Component
WordPress: Widget (WP_Widget class)

Laravel:
  <x-sidebar-widget title="Recent Posts" :count="5" />

WordPress:
  Keo tha widget vao sidebar area trong Admin > Appearance > Widgets
```

### WP_Widget Class - Cau truc

```php
<?php
/**
 * Moi Widget la 1 class ke thua WP_Widget.
 * Can override 3 methods chinh:
 */
class My_Widget extends WP_Widget {

    /**
     * Constructor: Dang ky widget
     */
    public function __construct() {
        parent::__construct(
            'my_widget',                  // Base ID (duy nhat)
            'Ten Widget',                 // Ten hien thi
            array(                        // Tuy chon
                'description' => 'Mo ta widget.',
                'classname'   => 'my-widget-class',    // CSS class cho wrapper
            )
        );
    }

    /**
     * Frontend: Hien thi widget tren trang web
     *
     * @param array $args     Tham so tu widget area (before_widget, after_widget, etc.)
     * @param array $instance Gia tri settings cua widget instance nay
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

        // Noi dung widget
        echo '<p>Noi dung widget o day.</p>';

        echo $args['after_widget'];
    }

    /**
     * Admin Form: Form cai dat trong admin
     *
     * @param array $instance Gia tri hien tai
     */
    public function form( $instance ) {
        $title = $instance['title'] ?? 'Tieu de mac dinh';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                Tieu de:
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
     * Update: Xu ly khi luu settings
     *
     * @param array $new_instance Gia tri moi tu form
     * @param array $old_instance Gia tri cu
     * @return array Gia tri da sanitize de luu
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        return $instance;
    }
}

// Dang ky widget
add_action( 'widgets_init', function() {
    register_widget( 'My_Widget' );
});
```

---

## 7. Tao Widget tuy chinh

### Widget Thong tin lien he

```php
<?php
/**
 * Plugin Name: Custom Widgets Plugin
 * Description: Tao cac widget tuy chinh.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === WIDGET 1: Thong tin lien he ===

class CWP_Contact_Info_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'cwp_contact_info',
            'Thong tin lien he',
            array(
                'description' => 'Hien thi thong tin lien he cua cong ty.',
                'classname'   => 'cwp-contact-info-widget',
            )
        );
    }

    /**
     * Frontend: Hien thi widget
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        // Tieu de
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'];
            echo esc_html( $instance['title'] );
            echo $args['after_title'];
        }
        ?>
        <div class="cwp-contact-info">
            <?php if ( ! empty( $instance['address'] ) ) : ?>
                <p>
                    <strong>Dia chi:</strong><br>
                    <?php echo esc_html( $instance['address'] ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $instance['phone'] ) ) : ?>
                <p>
                    <strong>Dien thoai:</strong><br>
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
                    <strong>Gio lam viec:</strong><br>
                    <?php echo nl2br( esc_html( $instance['hours'] ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $instance['show_map'] ) && ! empty( $instance['map_embed'] ) ) : ?>
                <div class="cwp-map" style="margin-top:10px;">
                    <?php
                    // Chi cho phep iframe tu Google Maps
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
     * Admin Form: Cac truong nhap lieu
     */
    public function form( $instance ) {
        $defaults = array(
            'title'     => 'Lien he',
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
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Tieu de:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <!-- Address -->
        <p>
            <label for="<?php echo $this->get_field_id( 'address' ); ?>">Dia chi:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'address' ); ?>"
                   name="<?php echo $this->get_field_name( 'address' ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $instance['address'] ); ?>">
        </p>

        <!-- Phone -->
        <p>
            <label for="<?php echo $this->get_field_id( 'phone' ); ?>">So dien thoai:</label>
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
            <label for="<?php echo $this->get_field_id( 'hours' ); ?>">Gio lam viec:</label>
            <textarea class="widefat" rows="3"
                      id="<?php echo $this->get_field_id( 'hours' ); ?>"
                      name="<?php echo $this->get_field_name( 'hours' ); ?>"
            ><?php echo esc_textarea( $instance['hours'] ); ?></textarea>
            <small>Vi du: Thu 2 - Thu 6: 8:00 - 17:00</small>
        </p>

        <!-- Show Map -->
        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_map' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_map' ); ?>"
                   value="1"
                   <?php checked( $instance['show_map'], true ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_map' ); ?>">Hien thi ban do</label>
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
     * Update: Sanitize va luu
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
            'Lien ket mang xa hoi',
            array(
                'description' => 'Hien thi cac icon mang xa hoi.',
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
        $title = $instance['title'] ?? 'Ket noi voi chung toi';
        $style = $instance['style'] ?? 'icon';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Tieu de:</label>
            <input class="widefat" type="text"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'style' ); ?>">Kieu hien thi:</label>
            <select class="widefat"
                    id="<?php echo $this->get_field_id( 'style' ); ?>"
                    name="<?php echo $this->get_field_name( 'style' ); ?>">
                <option value="icon" <?php selected( $style, 'icon' ); ?>>Icon tron</option>
                <option value="text" <?php selected( $style, 'text' ); ?>>Hien thi ten</option>
            </select>
        </p>

        <hr>
        <p><strong>Nhap URL mang xa hoi:</strong></p>

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

// === WIDGET 3: Bai viet noi bat ===

class CWP_Featured_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'cwp_featured_posts',
            'Bai viet noi bat',
            array(
                'description' => 'Hien thi danh sach bai viet noi bat voi thumbnail.',
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
            $query_args['meta_key'] = '_thumbnail_id';  // Chi lay bai co thumbnail
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
            echo '<p>Khong co bai viet.</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $defaults = array(
            'title'          => 'Bai viet noi bat',
            'count'          => 5,
            'category'       => '',
            'orderby'        => 'date',
            'show_thumbnail' => true,
            'show_date'      => true,
        );
        $instance = wp_parse_args( $instance, $defaults );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Tieu de:</label>
            <input class="widefat" type="text"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'count' ); ?>">So luong bai viet:</label>
            <input class="tiny-text" type="number" min="1" max="20"
                   id="<?php echo $this->get_field_id( 'count' ); ?>"
                   name="<?php echo $this->get_field_name( 'count' ); ?>"
                   value="<?php echo esc_attr( $instance['count'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'category' ); ?>">Chuyen muc:</label>
            <?php
            // wp_dropdown_categories tao dropdown tu dong tu danh sach categories
            wp_dropdown_categories( array(
                'show_option_all' => '-- Tat ca --',
                'selected'        => $instance['category'],
                'name'            => $this->get_field_name( 'category' ),
                'id'              => $this->get_field_id( 'category' ),
                'class'           => 'widefat',
                'hide_empty'      => false,
            ));
            ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'orderby' ); ?>">Sap xep theo:</label>
            <select class="widefat"
                    id="<?php echo $this->get_field_id( 'orderby' ); ?>"
                    name="<?php echo $this->get_field_name( 'orderby' ); ?>">
                <option value="date" <?php selected( $instance['orderby'], 'date' ); ?>>Ngay moi nhat</option>
                <option value="comment_count" <?php selected( $instance['orderby'], 'comment_count' ); ?>>Nhieu binh luan</option>
                <option value="rand" <?php selected( $instance['orderby'], 'rand' ); ?>>Ngau nhien</option>
            </select>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>"
                   value="1" <?php checked( $instance['show_thumbnail'] ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>">Hien thi hinh nho</label>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_date' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_date' ); ?>"
                   value="1" <?php checked( $instance['show_date'] ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_date' ); ?>">Hien thi ngay dang</label>
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

// === DANG KY TAT CA WIDGETS ===
add_action( 'widgets_init', 'cwp_register_widgets' );

function cwp_register_widgets() {
    register_widget( 'CWP_Contact_Info_Widget' );
    register_widget( 'CWP_Social_Links_Widget' );
    register_widget( 'CWP_Featured_Posts_Widget' );
}
```

---

## 8. Gutenberg Block vs Widget

### So sanh

| Dac diem | Classic Widget | Gutenberg Block |
|----------|---------------|-----------------|
| **Editor** | Widget panel (Appearance > Widgets) | Block Editor |
| **Cong nghe** | PHP (WP_Widget class) | JavaScript (React) + PHP |
| **Vi tri** | Chi sidebar/widget areas | Bat ky dau trong noi dung |
| **Tu WP version** | 2.8+ | 5.0+ (2018) |
| **Tuong lai** | Van duoc ho tro | **Duoc khuyen dung** |
| **Do phuc tap** | Thap | Trung binh - Cao |

### Tao Gutenberg Block don gian (khong can JSX)

```php
<?php
/**
 * Dang ky 1 Block don gian chi bang PHP (khong can build JS)
 * Tu WordPress 5.8+, dung register_block_type voi render_callback
 */
add_action( 'init', 'cwp_register_blocks' );

function cwp_register_blocks() {
    /**
     * Block don gian render phia server (Server-Side Rendering)
     * Cach nay phu hop khi ban khong muon viet React
     */
    register_block_type( 'cwp/contact-info', array(
        // Attributes ma user co the cau hinh
        'attributes' => array(
            'title' => array(
                'type'    => 'string',
                'default' => 'Lien he',
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
        // Ham render phia server
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
            <p>Dien thoai: <a href="tel:<?php echo esc_attr( $attributes['phone'] ); ?>">
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

### Khuyen nghi

```
Khi nao dung Widget:
- Can tuong thich voi WordPress cu (truoc 5.0)
- Plugin don gian, chi hien thi o sidebar
- Khong muon viet JavaScript phuc tap

Khi nao dung Block:
- Phien ban WordPress 5.0+
- Noi dung can dat o bat ky dau trong bai viet
- Muon trai nghiem editor tot hon
- Plugin moi, huong toi tuong lai
```

---

## 9. Code vi du day du

### Plugin hoan chinh: Shortcodes + Widgets

```php
<?php
/**
 * Plugin Name:       Content Elements Plugin
 * Description:       Bo suu tap Shortcodes va Widgets cho website.
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
        do_shortcode( $content ); // Xu ly cac [cep_item] ben trong

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
            'features' => '',             // Phan cach bang |
            'url'      => '#',
            'label'    => 'Dang ky ngay',
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
                ">Pho bien nhat</span>
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
            'btn_text' => 'Tim hieu them',
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

// Khoi tao Shortcodes
new CEP_Shortcodes();

// === DANG KY WIDGETS (xem cac class Widget phia tren) ===
add_action( 'widgets_init', function() {
    register_widget( 'CWP_Contact_Info_Widget' );
    register_widget( 'CWP_Social_Links_Widget' );
    register_widget( 'CWP_Featured_Posts_Widget' );
});

// Su dung shortcodes:
//
// [cep_accordion]
//   [cep_item title="Cau hoi 1"]Tra loi cho cau hoi 1[/cep_item]
//   [cep_item title="Cau hoi 2"]Tra loi cho cau hoi 2[/cep_item]
// [/cep_accordion]
//
// [cep_pricing name="Pro" price="29" features="10 Users|50GB Storage|Email Support" featured="true"]
//
// [cep_cta title="Bat dau ngay hom nay" btn_text="Dang ky mien phi" btn_url="/register"]
//   Tham gia cung hang nghin nguoi dung khac!
// [/cep_cta]
//
// [cep_counter number="1500" label="Khach hang"]
//
// [cep_testimonial name="Nguyen Van A" title="CEO Cong ty X" rating="5"]
//   San pham tuyet voi, toi rat hai long voi dich vu!
// [/cep_testimonial]
```

---

## 10. Best Practices

### Shortcodes

```php
<?php
// 1. Luon RETURN, khong ECHO
// SAI:
add_shortcode( 'bad', function() {
    echo '<p>Noi dung</p>'; // Se xuat hien sai vi tri!
});

// DUNG:
add_shortcode( 'good', function() {
    return '<p>Noi dung</p>';
});

// 2. Dung output buffering khi can HTML phuc tap
add_shortcode( 'complex', function() {
    ob_start();
    ?>
    <div class="complex-layout">
        <!-- HTML phuc tap o day -->
    </div>
    <?php
    return ob_get_clean();
});

// 3. Luon dung shortcode_atts cho attributes
add_shortcode( 'safe', function( $atts ) {
    $atts = shortcode_atts( array(
        'default1' => 'value1',
        'default2' => 'value2',
    ), $atts, 'safe' ); // Tham so 3 cho phep filter
    // ...
});

// 4. Dung do_shortcode() cho nested content
function my_wrapper( $atts, $content = null ) {
    return '<div class="wrapper">' . do_shortcode( $content ) . '</div>';
}

// 5. Khong dat shortcode trong the <title> hay attribute
// Shortcode chi hoat dong trong the_content, the_excerpt, va text widgets

// 6. Xu ly khi khong co attributes
add_shortcode( 'flexible', function( $atts ) {
    // $atts co the la string rong '' khi khong co attributes
    $atts = shortcode_atts( array(
        'param' => 'default',
    ), $atts ?: array(), 'flexible' );
});
```

### Widgets

```php
<?php
// 1. Luon ke thua WP_Widget
// 2. Override du 3 methods: widget(), form(), update()
// 3. Luon sanitize trong update()
// 4. Luon escape trong widget() va form()
// 5. Dung $this->get_field_id() va $this->get_field_name() cho form fields
// 6. Dung wp_parse_args() cho default values
// 7. Reset postdata sau WP_Query: wp_reset_postdata()

// 8. Dang ky Widget area (trong theme)
add_action( 'widgets_init', function() {
    register_sidebar( array(
        'name'          => 'Footer Widget Area',
        'id'            => 'footer-widgets',
        'description'   => 'Widgets hien thi o footer.',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
});
```

---

## Tham khao

- [WordPress Shortcode API](https://developer.wordpress.org/plugins/shortcodes/)
- [WordPress Widgets API](https://developer.wordpress.org/plugins/widgets/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [shortcode_atts()](https://developer.wordpress.org/reference/functions/shortcode_atts/)
- [WP_Widget Class](https://developer.wordpress.org/reference/classes/wp_widget/)
