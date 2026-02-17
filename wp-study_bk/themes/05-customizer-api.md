# Theme Customizer API trong WordPress

## Mục Lục

1. [Theme Customizer là gì](#1-theme-customizer-la-gi)
2. [Panels, Sections, Settings, Controls](#2-cau-truc)
3. [$wp_customize Object](#3-wp_customize-object)
4. [Các loại Control có sẵn](#4-cac-loai-control)
5. [Custom Controls](#5-custom-controls)
6. [Selective Refresh (Live Preview)](#6-selective-refresh)
7. [Sanitize Callbacks](#7-sanitize-callbacks)
8. [Default Values](#8-default-values)
9. [Code ví dụ: Theme Customizer hoàn chỉnh](#9-code-vi-du)
10. [Best Practices](#10-best-practices)

---

## 1. Theme Customizer là gì

Theme Customizer (Appearance > Customize) cho phép người dùng **thay đổi cài đặt giao diện** của theme với **live preview** (xem trước trực tiếp).

### Tại sao dùng Customizer?

| Đặc điểm | Customizer | Theme Options Page |
|----------|------------|-------------------|
| Live Preview | Có | Không |
| API chuẩn WordPress | Có | Không (tự code) |
| An toàn (sanitize) | Tích hợp | Tự làm |
| Non-destructive | Có (giá trị mặc định) | Tùy |
| Responsive preview | Có (Desktop/Tablet/Mobile) | Không |

### Truy cập Customizer:
- **Admin > Appearance > Customize**
- Hoặc thêm `?customize=true` vào URL bất kỳ

### So sánh với Laravel:

```php
// LARAVEL - Settings page tự tạo:
// - Tạo route, controller, view
// - Tạo migration cho settings table
// - Tự code form và validation

// WORDPRESS - Customizer API:
// - Chỉ cần gọi $wp_customize->add_*()
// - WordPress tự động tạo form + live preview
// - Lưu vào wp_options, không cần migration
```

---

## 2. Cấu trúc

### Thứ bậc Customizer:

```
Panel (Nhóm lớn - tùy chọn)
  |
  +-- Section (Nhóm nhỏ - bắt buộc)
        |
        +-- Setting (Giá trị lưu trong database)
        |     |
        +-----+-- Control (Thành phần UI để người dùng thay đổi giá trị)
```

### Mô hình hoạt động:

```
1. SETTING: Định nghĩa "cái gì được lưu"
   - key, giá trị mặc định, sanitize callback
   - Lưu vào wp_options hoặc theme_mods

2. CONTROL: Định nghĩa "người dùng thay đổi bằng cách nào"
   - Text input, color picker, image upload...
   - Mỗi control gắn với 1 setting

3. SECTION: Nhóm các controls lại
   - "Header Settings", "Typography", "Footer"...

4. PANEL: Nhóm các sections lại (optional)
   - Dùng khi có quá nhiều sections
```

### Cách đăng ký:

```php
<?php
/**
 * Đăng ký Customizer settings
 * Hook vào 'customize_register'
 */
function developer_customize_register( $wp_customize ) {

    // === 1. TAO PANEL (optional) ===
    $wp_customize->add_panel( 'developer_theme_panel', array(
        'title'       => __( 'Cài Đặt Theme', 'developer-theme' ),
        'description' => __( 'Tùy chỉnh giao diện theme.', 'developer-theme' ),
        'priority'    => 10, // Thứ tự hiển thị (số nhỏ = hiện trước)
    ) );

    // === 2. TAO SECTION ===
    $wp_customize->add_section( 'developer_header_section', array(
        'title'       => __( 'Header', 'developer-theme' ),
        'description' => __( 'Cài đặt phần header của trang.', 'developer-theme' ),
        'panel'       => 'developer_theme_panel', // Thuộc panel nào
        'priority'    => 10,
    ) );

    // === 3. TAO SETTING ===
    $wp_customize->add_setting( 'developer_header_bg_color', array(
        'default'           => '#23282d',           // Giá trị mặc định
        'sanitize_callback' => 'sanitize_hex_color', // Hàm làm sạch dữ liệu
        'transport'         => 'postMessage',        // Cách cập nhật preview
        // 'refresh'   = reload toàn trang (mặc định)
        // 'postMessage' = cập nhật bằng JS (nhanh hơn, không reload)
        'type'              => 'theme_mod',          // Lưu ở đâu
        // 'theme_mod' = lưu vào theme_mods (mặc định, khuyên dùng)
        // 'option'    = lưu vào wp_options
    ) );

    // === 4. TAO CONTROL ===
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'developer_header_bg_color',  // Phải trùng với setting ID
        array(
            'label'       => __( 'Màu Nền Header', 'developer-theme' ),
            'description' => __( 'Chọn màu nền cho header.', 'developer-theme' ),
            'section'     => 'developer_header_section', // Thuộc section nào
            'priority'    => 10,
        )
    ) );
}
add_action( 'customize_register', 'developer_customize_register' );
```

### Lấy giá trị đã lưu:

```php
<?php
// Lấy giá trị customizer (dùng trong template files)

// Cách 1: get_theme_mod() - khuyên dùng
$header_bg = get_theme_mod( 'developer_header_bg_color', '#23282d' );
// Tham số 2 là giá trị mặc định (trường hợp chưa lưu gì)

// Cách 2: Nếu setting type là 'option'
$value = get_option( 'developer_header_bg_color', '#23282d' );

// Sử dụng trong template:
?>
<header style="background-color: <?php echo esc_attr( get_theme_mod( 'developer_header_bg_color', '#23282d' ) ); ?>">
    ...
</header>

<?php
// Hoặc tạo CSS tự động:
function developer_customizer_css() {
    $header_bg   = get_theme_mod( 'developer_header_bg_color', '#23282d' );
    $header_text = get_theme_mod( 'developer_header_text_color', '#ffffff' );
    $primary     = get_theme_mod( 'developer_primary_color', '#0073aa' );
    ?>
    <style id="developer-customizer-css">
        .site-header {
            background-color: <?php echo esc_attr( $header_bg ); ?>;
            color: <?php echo esc_attr( $header_text ); ?>;
        }
        a {
            color: <?php echo esc_attr( $primary ); ?>;
        }
        .btn-primary {
            background-color: <?php echo esc_attr( $primary ); ?>;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'developer_customizer_css' );
```

---

## 3. $wp_customize Object

```php
<?php
/**
 * $wp_customize - Object chính của Customizer API
 * Các method quan trọng:
 */
function developer_customize_full( $wp_customize ) {

    // === PANEL ===
    $wp_customize->add_panel( $id, $args );
    $wp_customize->get_panel( $id );
    $wp_customize->remove_panel( $id );

    // === SECTION ===
    $wp_customize->add_section( $id, $args );
    $wp_customize->get_section( $id );
    $wp_customize->remove_section( $id );

    // === SETTING ===
    $wp_customize->add_setting( $id, $args );
    $wp_customize->get_setting( $id );
    $wp_customize->remove_setting( $id );

    // === CONTROL ===
    $wp_customize->add_control( $id_or_object, $args );
    $wp_customize->get_control( $id );
    $wp_customize->remove_control( $id );

    // === MODIFY SECTIONS CÓ SẴN ===
    // Di chuyển section vào panel của mình
    $wp_customize->get_section( 'title_tagline' )->panel = 'developer_theme_panel';
    $wp_customize->get_section( 'colors' )->panel = 'developer_theme_panel';

    // Đổi tiêu đề section
    $wp_customize->get_section( 'title_tagline' )->title = __( 'Logo và Tên Site', 'developer-theme' );

    // Thay đổi priority (thứ tự)
    $wp_customize->get_section( 'title_tagline' )->priority = 5;

    // === XÓA CONTROLS KHÔNG CẦN ===
    // Xóa custom header image
    $wp_customize->remove_section( 'header_image' );

    // Xóa custom background
    $wp_customize->remove_section( 'background_image' );

    // Xóa static front page setting
    // $wp_customize->remove_section( 'static_front_page' );

    // === SELECTIVE REFRESH ===
    $wp_customize->selective_refresh->add_partial( $id, $args );
    $wp_customize->selective_refresh->get_partial( $id );
    $wp_customize->selective_refresh->remove_partial( $id );
}
add_action( 'customize_register', 'developer_customize_full' );
```

---

## 4. Các loại Control có sẵn

### Controls cơ bản (built-in):

```php
<?php
function developer_basic_controls( $wp_customize ) {

    // Tạo section cho ví dụ
    $wp_customize->add_section( 'developer_demo_section', array(
        'title' => __( 'Demo Controls', 'developer-theme' ),
    ) );

    // === 1. TEXT INPUT ===
    $wp_customize->add_setting( 'developer_text_field', array(
        'default'           => 'Giá trị mặc định',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_text_field', array(
        'type'        => 'text',
        'label'       => __( 'Text Input', 'developer-theme' ),
        'description' => __( 'Nhập văn bản ngắn.', 'developer-theme' ),
        'section'     => 'developer_demo_section',
    ) );

    // === 2. TEXTAREA ===
    $wp_customize->add_setting( 'developer_textarea_field', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_textarea_field', array(
        'type'        => 'textarea',
        'label'       => __( 'Textarea', 'developer-theme' ),
        'description' => __( 'Nhập văn bản dài.', 'developer-theme' ),
        'section'     => 'developer_demo_section',
        'input_attrs' => array(
            'rows'        => 5,
            'placeholder' => __( 'Nhập nội dung...', 'developer-theme' ),
        ),
    ) );

    // === 3. EMAIL ===
    $wp_customize->add_setting( 'developer_email_field', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_email',
    ) );
    $wp_customize->add_control( 'developer_email_field', array(
        'type'    => 'email',
        'label'   => __( 'Email', 'developer-theme' ),
        'section' => 'developer_demo_section',
    ) );

    // === 4. URL ===
    $wp_customize->add_setting( 'developer_url_field', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'developer_url_field', array(
        'type'    => 'url',
        'label'   => __( 'URL', 'developer-theme' ),
        'section' => 'developer_demo_section',
    ) );

    // === 5. NUMBER ===
    $wp_customize->add_setting( 'developer_number_field', array(
        'default'           => 10,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'developer_number_field', array(
        'type'        => 'number',
        'label'       => __( 'Số', 'developer-theme' ),
        'section'     => 'developer_demo_section',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 100,
            'step' => 1,
        ),
    ) );

    // === 6. RANGE (Slider) ===
    $wp_customize->add_setting( 'developer_range_field', array(
        'default'           => 50,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_range_field', array(
        'type'        => 'range',
        'label'       => __( 'Range Slider', 'developer-theme' ),
        'section'     => 'developer_demo_section',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 100,
            'step' => 5,
        ),
    ) );

    // === 7. CHECKBOX ===
    $wp_customize->add_setting( 'developer_checkbox_field', array(
        'default'           => true,
        'sanitize_callback' => 'developer_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'developer_checkbox_field', array(
        'type'    => 'checkbox',
        'label'   => __( 'Bật tính năng X', 'developer-theme' ),
        'section' => 'developer_demo_section',
    ) );

    // === 8. RADIO BUTTONS ===
    $wp_customize->add_setting( 'developer_radio_field', array(
        'default'           => 'left',
        'sanitize_callback' => 'developer_sanitize_select',
    ) );
    $wp_customize->add_control( 'developer_radio_field', array(
        'type'    => 'radio',
        'label'   => __( 'Vị trí Sidebar', 'developer-theme' ),
        'section' => 'developer_demo_section',
        'choices' => array(
            'left'  => __( 'Bên Trái', 'developer-theme' ),
            'right' => __( 'Bên Phải', 'developer-theme' ),
            'none'  => __( 'Không Có', 'developer-theme' ),
        ),
    ) );

    // === 9. SELECT (Dropdown) ===
    $wp_customize->add_setting( 'developer_select_field', array(
        'default'           => 'boxed',
        'sanitize_callback' => 'developer_sanitize_select',
    ) );
    $wp_customize->add_control( 'developer_select_field', array(
        'type'    => 'select',
        'label'   => __( 'Layout', 'developer-theme' ),
        'section' => 'developer_demo_section',
        'choices' => array(
            'boxed'      => __( 'Boxed', 'developer-theme' ),
            'full-width' => __( 'Full Width', 'developer-theme' ),
            'fluid'      => __( 'Fluid', 'developer-theme' ),
        ),
    ) );

    // === 10. DROPDOWN PAGES ===
    $wp_customize->add_setting( 'developer_page_field', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'developer_page_field', array(
        'type'    => 'dropdown-pages',
        'label'   => __( 'Chọn Trang', 'developer-theme' ),
        'section' => 'developer_demo_section',
    ) );
}
add_action( 'customize_register', 'developer_basic_controls' );
```

### Controls đặc biệt (WP_Customize_*_Control):

```php
<?php
function developer_special_controls( $wp_customize ) {

    $wp_customize->add_section( 'developer_special_section', array(
        'title' => __( 'Controls Đặc Biệt', 'developer-theme' ),
    ) );

    // === 11. COLOR PICKER ===
    $wp_customize->add_setting( 'developer_color_field', array(
        'default'           => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'developer_color_field',
        array(
            'label'   => __( 'Màu Chính', 'developer-theme' ),
            'section' => 'developer_special_section',
        )
    ) );

    // === 12. IMAGE UPLOAD ===
    $wp_customize->add_setting( 'developer_image_field', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control(
        $wp_customize,
        'developer_image_field',
        array(
            'label'       => __( 'Hình Nền Header', 'developer-theme' ),
            'description' => __( 'Upload hình ảnh 1920x500px.', 'developer-theme' ),
            'section'     => 'developer_special_section',
        )
    ) );

    // === 13. MEDIA UPLOAD (các loại file) ===
    $wp_customize->add_setting( 'developer_media_field', array(
        'default'           => '',
        'sanitize_callback' => 'absint',  // Lưu attachment ID
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control(
        $wp_customize,
        'developer_media_field',
        array(
            'label'     => __( 'Chọn Media', 'developer-theme' ),
            'section'   => 'developer_special_section',
            'mime_type' => 'image', // 'image', 'video', 'audio', 'application'
        )
    ) );

    // === 14. CROPPED IMAGE ===
    $wp_customize->add_setting( 'developer_cropped_image', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( new WP_Customize_Cropped_Image_Control(
        $wp_customize,
        'developer_cropped_image',
        array(
            'label'       => __( 'Banner (Crop)', 'developer-theme' ),
            'section'     => 'developer_special_section',
            'width'       => 1200,
            'height'      => 400,
            'flex_width'  => true,
            'flex_height' => false,
        )
    ) );

    // === 15. DATE/TIME (WP 4.9+) ===
    $wp_customize->add_setting( 'developer_date_field', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( new WP_Customize_Date_Time_Control(
        $wp_customize,
        'developer_date_field',
        array(
            'label'        => __( 'Ngày Giờ', 'developer-theme' ),
            'section'      => 'developer_special_section',
            'include_time' => true,
            'allow_past_date' => true,
        )
    ) );

    // === 16. CODE EDITOR (WP 4.9+) ===
    $wp_customize->add_setting( 'developer_custom_css_field', array(
        'default'           => '',
        'sanitize_callback' => 'wp_strip_all_tags',
    ) );
    $wp_customize->add_control( new WP_Customize_Code_Editor_Control(
        $wp_customize,
        'developer_custom_css_field',
        array(
            'label'       => __( 'Custom CSS', 'developer-theme' ),
            'section'     => 'developer_special_section',
            'code_type'   => 'text/css', // 'text/css', 'text/html', 'application/javascript'
            'input_attrs' => array(
                'aria-describedby' => 'custom-css-description',
            ),
        )
    ) );
}
add_action( 'customize_register', 'developer_special_controls' );
```

---

## 5. Custom Controls

### Tạo control tùy chỉnh:

```php
<?php
/**
 * Custom Control: Toggle Switch (thay vi checkbox)
 */
class Developer_Toggle_Control extends WP_Customize_Control {

    /**
     * Kiểu control
     */
    public $type = 'developer-toggle';

    /**
     * Enqueue CSS/JS cho control
     */
    public function enqueue() {
        wp_enqueue_style(
            'developer-toggle-control',
            get_template_directory_uri() . '/assets/css/customizer-controls.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Render HTML của control
     */
    public function render_content() {
        ?>
        <div class="developer-toggle-control">
            <div class="toggle-wrapper">
                <label class="toggle-switch">
                    <input type="checkbox"
                           value="<?php echo esc_attr( $this->value() ); ?>"
                           <?php $this->link(); ?>
                           <?php checked( $this->value() ); ?> />
                    <span class="toggle-slider"></span>
                </label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            </div>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description">
                    <?php echo esc_html( $this->description ); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Custom Control: Separator/Heading
 * Để phân chia các nhóm settings trong 1 section
 */
class Developer_Separator_Control extends WP_Customize_Control {

    public $type = 'developer-separator';

    public function render_content() {
        ?>
        <div class="developer-separator">
            <?php if ( $this->label ) : ?>
                <h4 class="separator-title"><?php echo esc_html( $this->label ); ?></h4>
            <?php endif; ?>
            <?php if ( $this->description ) : ?>
                <p class="separator-description"><?php echo esc_html( $this->description ); ?></p>
            <?php endif; ?>
            <hr />
        </div>
        <?php
    }
}

/**
 * Custom Control: Google Fonts Selector
 */
class Developer_Font_Control extends WP_Customize_Control {

    public $type = 'developer-font';

    private $fonts = array(
        'system'        => 'System Default (-apple-system, BlinkMacSystemFont, ...)',
        'Inter'         => 'Inter',
        'Roboto'        => 'Roboto',
        'Open Sans'     => 'Open Sans',
        'Lato'          => 'Lato',
        'Montserrat'    => 'Montserrat',
        'Source Sans 3'  => 'Source Sans 3',
        'Poppins'       => 'Poppins',
        'Nunito'        => 'Nunito',
        'Playfair Display' => 'Playfair Display',
        'Merriweather'  => 'Merriweather',
    );

    public function render_content() {
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>

            <select <?php $this->link(); ?> style="width: 100%;">
                <?php foreach ( $this->fonts as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $this->value(), $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Preview font -->
        <p class="font-preview" style="font-family: '<?php echo esc_attr( $this->value() ); ?>', sans-serif; margin-top: 10px; font-size: 16px;">
            The quick brown fox jumps over the lazy dog. Xin chao Viet Nam.
        </p>
        <?php
    }
}

// === Đăng ký custom controls ===
function developer_custom_controls( $wp_customize ) {

    $wp_customize->add_section( 'developer_custom_section', array(
        'title' => __( 'Custom Controls', 'developer-theme' ),
    ) );

    // Toggle control
    $wp_customize->add_setting( 'developer_show_topbar', array(
        'default'           => true,
        'sanitize_callback' => 'developer_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new Developer_Toggle_Control(
        $wp_customize,
        'developer_show_topbar',
        array(
            'label'       => __( 'Hiển thị Top Bar', 'developer-theme' ),
            'description' => __( 'Bật/tắt thanh top bar ở đầu trang.', 'developer-theme' ),
            'section'     => 'developer_custom_section',
        )
    ) );

    // Font control
    $wp_customize->add_setting( 'developer_body_font', array(
        'default'           => 'Inter',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new Developer_Font_Control(
        $wp_customize,
        'developer_body_font',
        array(
            'label'       => __( 'Font Chữ Nội Dung', 'developer-theme' ),
            'description' => __( 'Chọn font chữ cho nội dung chính.', 'developer-theme' ),
            'section'     => 'developer_custom_section',
        )
    ) );
}
add_action( 'customize_register', 'developer_custom_controls' );
```

### CSS cho Custom Controls:

```css
/* assets/css/customizer-controls.css */

/* Toggle Switch */
.toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider::before {
    content: "";
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #0073aa;
}

.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(20px);
}

/* Separator */
.developer-separator {
    margin: 15px 0 10px;
}

.separator-title {
    margin: 0 0 5px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #23282d;
}

.developer-separator hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 10px 0 0;
}
```

---

## 6. Selective Refresh

### Selective Refresh là gì?

Thay vì reload toàn trang khi thay đổi setting, Selective Refresh chỉ **cập nhật phần cần thiết** của trang. Nhanh hơn và UX tốt hơn.

```php
<?php
function developer_selective_refresh( $wp_customize ) {

    // === Đăng ký Partial ===
    // Partial = phần trang sẽ được refresh khi setting thay đổi

    // Ví dụ 1: Cập nhật tên site
    $wp_customize->selective_refresh->add_partial( 'blogname', array(
        'selector'        => '.site-title a',           // CSS selector của element cần refresh
        'render_callback' => function() {                // Hàm trả về HTML mới
            bloginfo( 'name' );
        },
    ) );

    // Ví dụ 2: Cập nhật mô tả site
    $wp_customize->selective_refresh->add_partial( 'blogdescription', array(
        'selector'        => '.site-description',
        'render_callback' => function() {
            bloginfo( 'description' );
        },
    ) );

    // Ví dụ 3: Cập nhật footer copyright
    $wp_customize->selective_refresh->add_partial( 'developer_footer_text', array(
        'selector'            => '.site-info',
        'render_callback'     => 'developer_render_footer_text', // Tên hàm
        'container_inclusive'  => false,  // false = thay nội dung bên trong selector
                                          // true = thay toàn bộ element (cả tag cha)
        'fallback_refresh'    => true,   // Reload toàn trang nếu partial thất bại
    ) );

    // Ví dụ 4: Cập nhật social links
    $wp_customize->selective_refresh->add_partial( 'developer_social_facebook', array(
        'selector'        => '.social-links',
        'render_callback' => 'developer_render_social_links',
        'settings'        => array(                     // Nhiều settings trigger cùng 1 partial
            'developer_social_facebook',
            'developer_social_twitter',
            'developer_social_instagram',
            'developer_social_youtube',
        ),
    ) );
}
add_action( 'customize_register', 'developer_selective_refresh' );

// Hàm render cho partial
function developer_render_footer_text() {
    $text = get_theme_mod( 'developer_footer_text', '&copy; 2024 Developer Theme' );
    echo wp_kses_post( $text );
}

function developer_render_social_links() {
    $socials = array(
        'facebook'  => get_theme_mod( 'developer_social_facebook', '' ),
        'twitter'   => get_theme_mod( 'developer_social_twitter', '' ),
        'instagram' => get_theme_mod( 'developer_social_instagram', '' ),
        'youtube'   => get_theme_mod( 'developer_social_youtube', '' ),
    );

    foreach ( $socials as $network => $url ) {
        if ( $url ) {
            printf(
                '<a href="%s" target="_blank" rel="noopener" class="social-link social-%s">%s</a>',
                esc_url( $url ),
                esc_attr( $network ),
                esc_html( ucfirst( $network ) )
            );
        }
    }
}
```

### PostMessage với JavaScript:

```javascript
/**
 * assets/js/customizer-preview.js
 * JS chạy trong iframe preview của Customizer
 *
 * Cập nhật trực tiếp DOM khi thay đổi setting (không cần reload)
 */
(function($) {
    'use strict';

    // Cập nhật tiêu đề site
    wp.customize('blogname', function(value) {
        value.bind(function(newval) {
            $('.site-title a').text(newval);
        });
    });

    // Cập nhật mô tả site
    wp.customize('blogdescription', function(value) {
        value.bind(function(newval) {
            $('.site-description').text(newval);
        });
    });

    // Cập nhật màu nền header
    wp.customize('developer_header_bg_color', function(value) {
        value.bind(function(newval) {
            $('.site-header').css('background-color', newval);
        });
    });

    // Cập nhật màu chính (primary color)
    wp.customize('developer_primary_color', function(value) {
        value.bind(function(newval) {
            // Thay đổi CSS variable
            document.documentElement.style.setProperty('--color-primary', newval);
        });
    });

    // Cập nhật font family
    wp.customize('developer_body_font', function(value) {
        value.bind(function(newval) {
            if (newval !== 'system') {
                // Load Google Font
                var link = document.getElementById('developer-google-font-preview');
                if (!link) {
                    link = document.createElement('link');
                    link.id = 'developer-google-font-preview';
                    link.rel = 'stylesheet';
                    document.head.appendChild(link);
                }
                link.href = 'https://fonts.googleapis.com/css2?family=' +
                            newval.replace(/ /g, '+') +
                            ':wght@400;500;600;700&display=swap';
            }
            $('body').css('font-family', "'" + newval + "', sans-serif");
        });
    });

    // Cập nhật footer text
    wp.customize('developer_footer_text', function(value) {
        value.bind(function(newval) {
            $('.footer-text').html(newval);
        });
    });

    // Toggle elements
    wp.customize('developer_show_topbar', function(value) {
        value.bind(function(newval) {
            if (newval) {
                $('.top-bar').show();
            } else {
                $('.top-bar').hide();
            }
        });
    });

})(jQuery);
```

### Enqueue JS cho Customizer:

```php
<?php
/**
 * Load JS cho Customizer preview
 */
function developer_customize_preview_js() {
    wp_enqueue_script(
        'developer-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        array( 'customize-preview', 'jquery' ),
        '1.0.0',
        true
    );
}
add_action( 'customize_preview_init', 'developer_customize_preview_js' );

/**
 * Load CSS/JS cho Customizer controls (panel bên trái)
 */
function developer_customize_controls_js() {
    wp_enqueue_style(
        'developer-customizer-controls',
        get_template_directory_uri() . '/assets/css/customizer-controls.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'developer-customizer-controls',
        get_template_directory_uri() . '/assets/js/customizer-controls.js',
        array( 'customize-controls', 'jquery' ),
        '1.0.0',
        true
    );
}
add_action( 'customize_controls_enqueue_scripts', 'developer_customize_controls_js' );
```

---

## 7. Sanitize Callbacks

### Tại sao cần sanitize?

Mỗi setting PHẢI có `sanitize_callback` để làm sạch dữ liệu trước khi lưu. Tránh XSS, SQL injection, và dữ liệu không hợp lệ.

```php
<?php
/**
 * Các hàm sanitize có sẵn của WordPress
 */

// Text ngắn (1 dòng, loại bỏ HTML tags)
'sanitize_callback' => 'sanitize_text_field'

// Text dài (nhiều dòng)
'sanitize_callback' => 'sanitize_textarea_field'

// Email
'sanitize_callback' => 'sanitize_email'

// URL
'sanitize_callback' => 'esc_url_raw'

// Màu HEX (#ffffff)
'sanitize_callback' => 'sanitize_hex_color'

// Số nguyên dương
'sanitize_callback' => 'absint'

// Loại bỏ tất cả HTML tags
'sanitize_callback' => 'wp_strip_all_tags'

// Cho phép 1 số HTML tags (như nội dung bài viết)
'sanitize_callback' => 'wp_kses_post'

// File name
'sanitize_callback' => 'sanitize_file_name'

/**
 * Các hàm sanitize TỰ TẠO
 */

// Checkbox (true/false)
function developer_sanitize_checkbox( $value ) {
    return ( isset( $value ) && true == $value ) ? true : false;
}

// Select/Radio (chỉ chấp nhận các giá trị đã định nghĩa)
function developer_sanitize_select( $input, $setting ) {
    // Lấy danh sách choices từ control
    $choices = $setting->manager->get_control( $setting->id )->choices;

    // Kiểm tra input có nằm trong choices không
    return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

// Số trong khoảng
function developer_sanitize_range( $input, $setting ) {
    $control = $setting->manager->get_control( $setting->id );
    $attrs   = $control->input_attrs;

    $min  = isset( $attrs['min'] ) ? $attrs['min'] : 0;
    $max  = isset( $attrs['max'] ) ? $attrs['max'] : 100;
    $step = isset( $attrs['step'] ) ? $attrs['step'] : 1;

    $number = absint( $input );
    return ( $number >= $min && $number <= $max ) ? $number : $setting->default;
}

// CSS an toàn
function developer_sanitize_css( $input ) {
    return wp_strip_all_tags( $input );
}

// HTML giới hạn
function developer_sanitize_html( $input ) {
    $allowed_html = array(
        'a'      => array( 'href' => array(), 'title' => array(), 'target' => array() ),
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
        'span'   => array( 'class' => array() ),
        'p'      => array(),
    );
    return wp_kses( $input, $allowed_html );
}

// Google Font name
function developer_sanitize_font( $input ) {
    $valid_fonts = array(
        'system', 'Inter', 'Roboto', 'Open Sans', 'Lato',
        'Montserrat', 'Source Sans 3', 'Poppins', 'Nunito',
    );
    return in_array( $input, $valid_fonts, true ) ? $input : 'system';
}
```

---

## 8. Default Values

```php
<?php
/**
 * Quản lý giá trị mặc định tập trung
 * Đặt trong inc/customizer-defaults.php
 */
function developer_get_defaults() {
    return array(
        // Header
        'developer_header_bg_color'    => '#23282d',
        'developer_header_text_color'  => '#ffffff',
        'developer_show_topbar'        => true,
        'developer_header_layout'      => 'default',
        'developer_sticky_header'      => true,

        // Colors
        'developer_primary_color'      => '#0073aa',
        'developer_secondary_color'    => '#23282d',
        'developer_accent_color'       => '#e74c3c',
        'developer_text_color'         => '#333333',
        'developer_bg_color'           => '#ffffff',

        // Typography
        'developer_body_font'          => 'Inter',
        'developer_heading_font'       => 'Inter',
        'developer_body_font_size'     => 16,
        'developer_heading_font_weight' => '700',

        // Layout
        'developer_container_width'    => 1200,
        'developer_sidebar_position'   => 'right',

        // Footer
        'developer_footer_bg_color'    => '#23282d',
        'developer_footer_text_color'  => '#ffffff',
        'developer_footer_text'        => '&copy; 2024 Developer Theme. All rights reserved.',
        'developer_footer_columns'     => 3,

        // Social
        'developer_social_facebook'    => '',
        'developer_social_twitter'     => '',
        'developer_social_instagram'   => '',
        'developer_social_youtube'     => '',
        'developer_social_linkedin'    => '',
        'developer_social_github'      => '',
    );
}

/**
 * Helper: Lấy giá trị customizer với default
 */
function developer_get_option( $key ) {
    $defaults = developer_get_defaults();
    $default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
    return get_theme_mod( $key, $default );
}

// Sử dụng trong template:
$primary_color = developer_get_option( 'developer_primary_color' );
$body_font     = developer_get_option( 'developer_body_font' );
$show_topbar   = developer_get_option( 'developer_show_topbar' );
```

---

## 9. Code ví dụ: Theme Customizer hoàn chỉnh

### inc/customizer.php:

```php
<?php
/**
 * Theme Customizer hoàn chỉnh
 *
 * File này được require từ functions.php:
 * require get_template_directory() . '/inc/customizer.php';
 *
 * @package Developer_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load defaults
require_once get_template_directory() . '/inc/customizer-defaults.php';

/**
 * Đăng ký tất cả customizer settings
 */
function developer_full_customize_register( $wp_customize ) {

    // === PANEL CHÍNH ===
    $wp_customize->add_panel( 'developer_panel', array(
        'title'    => __( 'Cài Đặt Theme Developer', 'developer-theme' ),
        'priority' => 25,
    ) );

    // Di chuyển sections có sẵn vào panel
    $wp_customize->get_section( 'title_tagline' )->panel = 'developer_panel';
    $wp_customize->get_section( 'title_tagline' )->priority = 5;

    // Enable selective refresh cho title va description
    $wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
    $wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

    $defaults = developer_get_defaults();

    // ============================================================
    // SECTION: HEADER SETTINGS
    // ============================================================
    $wp_customize->add_section( 'developer_header', array(
        'title'    => __( 'Header', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 10,
    ) );

    // --- Show Top Bar ---
    $wp_customize->add_setting( 'developer_show_topbar', array(
        'default'           => $defaults['developer_show_topbar'],
        'sanitize_callback' => 'developer_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new Developer_Toggle_Control(
        $wp_customize, 'developer_show_topbar', array(
            'label'   => __( 'Hiển Thị Top Bar', 'developer-theme' ),
            'section' => 'developer_header',
        )
    ) );

    // --- Sticky Header ---
    $wp_customize->add_setting( 'developer_sticky_header', array(
        'default'           => $defaults['developer_sticky_header'],
        'sanitize_callback' => 'developer_sanitize_checkbox',
    ) );
    $wp_customize->add_control( new Developer_Toggle_Control(
        $wp_customize, 'developer_sticky_header', array(
            'label'   => __( 'Header Dính (Sticky)', 'developer-theme' ),
            'section' => 'developer_header',
        )
    ) );

    // --- Header Background Color ---
    $wp_customize->add_setting( 'developer_header_bg_color', array(
        'default'           => $defaults['developer_header_bg_color'],
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'developer_header_bg_color', array(
            'label'   => __( 'Màu Nền Header', 'developer-theme' ),
            'section' => 'developer_header',
        )
    ) );

    // --- Header Text Color ---
    $wp_customize->add_setting( 'developer_header_text_color', array(
        'default'           => $defaults['developer_header_text_color'],
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'developer_header_text_color', array(
            'label'   => __( 'Màu Chữ Header', 'developer-theme' ),
            'section' => 'developer_header',
        )
    ) );

    // --- Header Layout ---
    $wp_customize->add_setting( 'developer_header_layout', array(
        'default'           => $defaults['developer_header_layout'],
        'sanitize_callback' => 'developer_sanitize_select',
    ) );
    $wp_customize->add_control( 'developer_header_layout', array(
        'type'    => 'select',
        'label'   => __( 'Kiểu Header', 'developer-theme' ),
        'section' => 'developer_header',
        'choices' => array(
            'default'  => __( 'Mặc định (Logo trái, Menu phải)', 'developer-theme' ),
            'centered' => __( 'Logo giữa', 'developer-theme' ),
            'stacked'  => __( 'Logo trên, Menu dưới', 'developer-theme' ),
        ),
    ) );

    // ============================================================
    // SECTION: COLORS
    // ============================================================
    $wp_customize->add_section( 'developer_colors', array(
        'title'    => __( 'Màu Sắc', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 20,
    ) );

    // --- Primary Color ---
    $wp_customize->add_setting( 'developer_primary_color', array(
        'default'           => $defaults['developer_primary_color'],
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'developer_primary_color', array(
            'label'       => __( 'Màu Chính (Primary)', 'developer-theme' ),
            'description' => __( 'Dùng cho links, buttons, accents.', 'developer-theme' ),
            'section'     => 'developer_colors',
        )
    ) );

    // --- Secondary Color ---
    $wp_customize->add_setting( 'developer_secondary_color', array(
        'default'           => $defaults['developer_secondary_color'],
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'developer_secondary_color', array(
            'label'   => __( 'Màu Phụ (Secondary)', 'developer-theme' ),
            'section' => 'developer_colors',
        )
    ) );

    // --- Accent Color ---
    $wp_customize->add_setting( 'developer_accent_color', array(
        'default'           => $defaults['developer_accent_color'],
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'developer_accent_color', array(
            'label'   => __( 'Màu Nhấn Mạnh (Accent)', 'developer-theme' ),
            'section' => 'developer_colors',
        )
    ) );

    // ============================================================
    // SECTION: TYPOGRAPHY
    // ============================================================
    $wp_customize->add_section( 'developer_typography', array(
        'title'    => __( 'Typography', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 30,
    ) );

    // --- Body Font ---
    $wp_customize->add_setting( 'developer_body_font', array(
        'default'           => $defaults['developer_body_font'],
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new Developer_Font_Control(
        $wp_customize, 'developer_body_font', array(
            'label'   => __( 'Font Nội Dung', 'developer-theme' ),
            'section' => 'developer_typography',
        )
    ) );

    // --- Heading Font ---
    $wp_customize->add_setting( 'developer_heading_font', array(
        'default'           => $defaults['developer_heading_font'],
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new Developer_Font_Control(
        $wp_customize, 'developer_heading_font', array(
            'label'   => __( 'Font Tiêu Đề', 'developer-theme' ),
            'section' => 'developer_typography',
        )
    ) );

    // --- Body Font Size ---
    $wp_customize->add_setting( 'developer_body_font_size', array(
        'default'           => $defaults['developer_body_font_size'],
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_body_font_size', array(
        'type'        => 'range',
        'label'       => __( 'Cỡ Chữ (px)', 'developer-theme' ),
        'section'     => 'developer_typography',
        'input_attrs' => array( 'min' => 12, 'max' => 24, 'step' => 1 ),
    ) );

    // ============================================================
    // SECTION: LAYOUT
    // ============================================================
    $wp_customize->add_section( 'developer_layout', array(
        'title'    => __( 'Layout', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 40,
    ) );

    // --- Container Width ---
    $wp_customize->add_setting( 'developer_container_width', array(
        'default'           => $defaults['developer_container_width'],
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_container_width', array(
        'type'        => 'range',
        'label'       => __( 'Chiều Rộng Container (px)', 'developer-theme' ),
        'section'     => 'developer_layout',
        'input_attrs' => array( 'min' => 960, 'max' => 1600, 'step' => 20 ),
    ) );

    // --- Sidebar Position ---
    $wp_customize->add_setting( 'developer_sidebar_position', array(
        'default'           => $defaults['developer_sidebar_position'],
        'sanitize_callback' => 'developer_sanitize_select',
    ) );
    $wp_customize->add_control( 'developer_sidebar_position', array(
        'type'    => 'radio',
        'label'   => __( 'Vị Trí Sidebar', 'developer-theme' ),
        'section' => 'developer_layout',
        'choices' => array(
            'right' => __( 'Bên Phải', 'developer-theme' ),
            'left'  => __( 'Bên Trái', 'developer-theme' ),
            'none'  => __( 'Không Có Sidebar', 'developer-theme' ),
        ),
    ) );

    // ============================================================
    // SECTION: FOOTER
    // ============================================================
    $wp_customize->add_section( 'developer_footer', array(
        'title'    => __( 'Footer', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 50,
    ) );

    // --- Footer Text ---
    $wp_customize->add_setting( 'developer_footer_text', array(
        'default'           => $defaults['developer_footer_text'],
        'sanitize_callback' => 'developer_sanitize_html',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_footer_text', array(
        'type'    => 'textarea',
        'label'   => __( 'Footer Text', 'developer-theme' ),
        'section' => 'developer_footer',
    ) );

    // --- Footer Background ---
    $wp_customize->add_setting( 'developer_footer_bg_color', array(
        'default'           => $defaults['developer_footer_bg_color'],
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'developer_footer_bg_color', array(
            'label'   => __( 'Màu Nền Footer', 'developer-theme' ),
            'section' => 'developer_footer',
        )
    ) );

    // --- Footer Columns ---
    $wp_customize->add_setting( 'developer_footer_columns', array(
        'default'           => $defaults['developer_footer_columns'],
        'sanitize_callback' => 'developer_sanitize_select',
    ) );
    $wp_customize->add_control( 'developer_footer_columns', array(
        'type'    => 'select',
        'label'   => __( 'Số Cột Footer', 'developer-theme' ),
        'section' => 'developer_footer',
        'choices' => array(
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
        ),
    ) );

    // ============================================================
    // SECTION: SOCIAL LINKS
    // ============================================================
    $wp_customize->add_section( 'developer_social', array(
        'title'    => __( 'Mạng Xã Hội', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 60,
    ) );

    $social_networks = array(
        'facebook'  => 'Facebook',
        'twitter'   => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
        'linkedin'  => 'LinkedIn',
        'github'    => 'GitHub',
    );

    foreach ( $social_networks as $network => $label ) {
        $setting_id = 'developer_social_' . $network;

        $wp_customize->add_setting( $setting_id, array(
            'default'           => $defaults[ $setting_id ] ?? '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'postMessage',
        ) );

        $wp_customize->add_control( $setting_id, array(
            'type'        => 'url',
            'label'       => $label,
            'section'     => 'developer_social',
            'input_attrs' => array(
                'placeholder' => 'https://' . $network . '.com/yourprofile',
            ),
        ) );
    }

    // ============================================================
    // SECTION: CUSTOM CSS
    // ============================================================
    $wp_customize->add_section( 'developer_custom_code', array(
        'title'    => __( 'Custom CSS/JS', 'developer-theme' ),
        'panel'    => 'developer_panel',
        'priority' => 100,
    ) );

    $wp_customize->add_setting( 'developer_custom_css', array(
        'default'           => '',
        'sanitize_callback' => 'wp_strip_all_tags',
    ) );
    $wp_customize->add_control( new WP_Customize_Code_Editor_Control(
        $wp_customize, 'developer_custom_css', array(
            'label'     => __( 'Custom CSS', 'developer-theme' ),
            'section'   => 'developer_custom_code',
            'code_type' => 'text/css',
        )
    ) );

    // ============================================================
    // SELECTIVE REFRESH PARTIALS
    // ============================================================
    $wp_customize->selective_refresh->add_partial( 'blogname', array(
        'selector'        => '.site-title a',
        'render_callback' => function() { bloginfo( 'name' ); },
    ) );

    $wp_customize->selective_refresh->add_partial( 'blogdescription', array(
        'selector'        => '.site-description',
        'render_callback' => function() { bloginfo( 'description' ); },
    ) );

    $wp_customize->selective_refresh->add_partial( 'developer_footer_text', array(
        'selector'        => '.footer-text',
        'render_callback' => function() {
            echo wp_kses_post( get_theme_mod( 'developer_footer_text', '' ) );
        },
    ) );
}
add_action( 'customize_register', 'developer_full_customize_register' );

/**
 * Output Customizer CSS vào <head>
 */
function developer_customizer_output_css() {
    $defaults = developer_get_defaults();

    $header_bg    = get_theme_mod( 'developer_header_bg_color', $defaults['developer_header_bg_color'] );
    $header_text  = get_theme_mod( 'developer_header_text_color', $defaults['developer_header_text_color'] );
    $primary      = get_theme_mod( 'developer_primary_color', $defaults['developer_primary_color'] );
    $secondary    = get_theme_mod( 'developer_secondary_color', $defaults['developer_secondary_color'] );
    $accent       = get_theme_mod( 'developer_accent_color', $defaults['developer_accent_color'] );
    $body_font    = get_theme_mod( 'developer_body_font', $defaults['developer_body_font'] );
    $heading_font = get_theme_mod( 'developer_heading_font', $defaults['developer_heading_font'] );
    $font_size    = get_theme_mod( 'developer_body_font_size', $defaults['developer_body_font_size'] );
    $container    = get_theme_mod( 'developer_container_width', $defaults['developer_container_width'] );
    $footer_bg    = get_theme_mod( 'developer_footer_bg_color', $defaults['developer_footer_bg_color'] );
    $custom_css   = get_theme_mod( 'developer_custom_css', '' );

    $font_family_body = ( $body_font === 'system' )
        ? "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
        : "'{$body_font}', sans-serif";

    $font_family_heading = ( $heading_font === 'system' )
        ? "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
        : "'{$heading_font}', sans-serif";
    ?>
    <style id="developer-customizer-css">
        :root {
            --color-primary: <?php echo esc_attr( $primary ); ?>;
            --color-secondary: <?php echo esc_attr( $secondary ); ?>;
            --color-accent: <?php echo esc_attr( $accent ); ?>;
            --font-body: <?php echo $font_family_body; ?>;
            --font-heading: <?php echo $font_family_heading; ?>;
            --font-size-base: <?php echo absint( $font_size ); ?>px;
            --max-width: <?php echo absint( $container ); ?>px;
        }

        body {
            font-family: var(--font-body);
            font-size: var(--font-size-base);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
        }

        .site-header {
            background-color: <?php echo esc_attr( $header_bg ); ?>;
            color: <?php echo esc_attr( $header_text ); ?>;
        }

        .site-header a {
            color: <?php echo esc_attr( $header_text ); ?>;
        }

        a { color: var(--color-primary); }
        a:hover { color: var(--color-accent); }

        .btn-primary,
        .read-more,
        .pagination .page-numbers.current {
            background-color: var(--color-primary);
        }

        .site-footer {
            background-color: <?php echo esc_attr( $footer_bg ); ?>;
        }

        .container {
            max-width: var(--max-width);
        }

        <?php if ( $custom_css ) : ?>
            <?php echo $custom_css; ?>
        <?php endif; ?>
    </style>
    <?php
}
add_action( 'wp_head', 'developer_customizer_output_css', 25 );

/**
 * Load Google Fonts nếu cần
 */
function developer_load_google_fonts() {
    $defaults     = developer_get_defaults();
    $body_font    = get_theme_mod( 'developer_body_font', $defaults['developer_body_font'] );
    $heading_font = get_theme_mod( 'developer_heading_font', $defaults['developer_heading_font'] );

    $fonts = array();

    if ( $body_font !== 'system' ) {
        $fonts[] = $body_font . ':wght@400;500;600;700';
    }
    if ( $heading_font !== 'system' && $heading_font !== $body_font ) {
        $fonts[] = $heading_font . ':wght@400;500;600;700';
    }

    if ( ! empty( $fonts ) ) {
        $font_string = implode( '&family=', array_map( function( $f ) {
            return str_replace( ' ', '+', $f );
        }, $fonts ) );

        wp_enqueue_style(
            'developer-google-fonts',
            'https://fonts.googleapis.com/css2?family=' . $font_string . '&display=swap',
            array(),
            null // null = không thêm version
        );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_load_google_fonts', 5 );
```

---

## 10. Best Practices

### 1. Prefix tất cả settings

```php
// ĐÚNG: Prefix với tên theme
'developer_header_bg_color'
'developer_primary_color'
'developer_show_topbar'

// SAI: Tên quá chung
'header_color'
'primary_color'
'show_topbar'
```

### 2. Luôn có sanitize_callback

```php
// MỖI setting PHẢI có sanitize_callback
// KHÔNG BAO GIỜ bỏ trống
$wp_customize->add_setting( 'my_setting', array(
    'sanitize_callback' => 'sanitize_text_field', // BẮT BUỘC
) );
```

### 3. Dùng transport postMessage khi có thể

```php
// postMessage = nhanh, không reload trang
// refresh = chậm, reload toàn trang

// Dùng postMessage cho: colors, fonts, text, toggle
'transport' => 'postMessage'

// Dùng refresh cho: layout changes, template changes
'transport' => 'refresh'
```

### 4. Default values tập trung

```php
// Tập trung defaults vào 1 hàm
function developer_get_defaults() {
    return array( ... );
}

// Dùng trong setting:
$defaults = developer_get_defaults();
'default' => $defaults['developer_primary_color']

// Dùng trong template:
get_theme_mod( 'developer_primary_color', $defaults['developer_primary_color'] )
```

### 5. Selective Refresh cho UX tốt

```php
// Ưu tiên Selective Refresh hơn postMessage
// Vì Selective Refresh dùng PHP render, chính xác hơn JS
$wp_customize->selective_refresh->add_partial( ... );
```

### 6. Tách file

```php
// Tách customizer code ra file riêng
// functions.php:
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/customizer-defaults.php';
require get_template_directory() . '/inc/customizer-controls.php';
```

---

**Tiếp theo:** [06 - Block Theme và FSE](./06-block-theme-va-fse.md) - Tìm hiểu Full Site Editing với Block Theme
