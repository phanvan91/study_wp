# Theme Customizer API trong WordPress

## Muc Luc

1. [Theme Customizer la gi](#1-theme-customizer-la-gi)
2. [Panels, Sections, Settings, Controls](#2-cau-truc)
3. [$wp_customize Object](#3-wp_customize-object)
4. [Cac loai Control co san](#4-cac-loai-control)
5. [Custom Controls](#5-custom-controls)
6. [Selective Refresh (Live Preview)](#6-selective-refresh)
7. [Sanitize Callbacks](#7-sanitize-callbacks)
8. [Default Values](#8-default-values)
9. [Code vi du: Theme Customizer hoan chinh](#9-code-vi-du)
10. [Best Practices](#10-best-practices)

---

## 1. Theme Customizer la gi

Theme Customizer (Appearance > Customize) cho phep nguoi dung **thay doi cai dat giao dien** cua theme voi **live preview** (xem truoc truc tiep).

### Tai sao dung Customizer?

| Dac diem | Customizer | Theme Options Page |
|----------|------------|-------------------|
| Live Preview | Co | Khong |
| API chuan WordPress | Co | Khong (tu code) |
| An toan (sanitize) | Tich hop | Tu lam |
| Non-destructive | Co (gia tri mac dinh) | Tuy |
| Responsive preview | Co (Desktop/Tablet/Mobile) | Khong |

### Truy cap Customizer:
- **Admin > Appearance > Customize**
- Hoac them `?customize=true` vao URL bat ky

### So sanh voi Laravel:

```php
// LARAVEL - Settings page tu tao:
// - Tao route, controller, view
// - Tao migration cho settings table
// - Tu code form va validation

// WORDPRESS - Customizer API:
// - Chi can goi $wp_customize->add_*()
// - WordPress tu dong tao form + live preview
// - Luu vao wp_options, khong can migration
```

---

## 2. Cau truc

### Thu bac Customizer:

```
Panel (Nhom lon - tuy chon)
  |
  +-- Section (Nhom nho - bat buoc)
        |
        +-- Setting (Gia tri luu trong database)
        |     |
        +-----+-- Control (Thanh phan UI de nguoi dung thay doi gia tri)
```

### Mo hinh hoat dong:

```
1. SETTING: Dinh nghia "cai gi duoc luu"
   - key, gia tri mac dinh, sanitize callback
   - Luu vao wp_options hoac theme_mods

2. CONTROL: Dinh nghia "nguoi dung thay doi bang cach nao"
   - Text input, color picker, image upload...
   - Moi control gan voi 1 setting

3. SECTION: Nhom cac controls lai
   - "Header Settings", "Typography", "Footer"...

4. PANEL: Nhom cac sections lai (optional)
   - Dung khi co qua nhieu sections
```

### Cach dang ky:

```php
<?php
/**
 * Dang ky Customizer settings
 * Hook vao 'customize_register'
 */
function developer_customize_register( $wp_customize ) {

    // === 1. TAO PANEL (optional) ===
    $wp_customize->add_panel( 'developer_theme_panel', array(
        'title'       => __( 'Cai Dat Theme', 'developer-theme' ),
        'description' => __( 'Tuy chinh giao dien theme.', 'developer-theme' ),
        'priority'    => 10, // Thu tu hien thi (so nho = hien truoc)
    ) );

    // === 2. TAO SECTION ===
    $wp_customize->add_section( 'developer_header_section', array(
        'title'       => __( 'Header', 'developer-theme' ),
        'description' => __( 'Cai dat phan header cua trang.', 'developer-theme' ),
        'panel'       => 'developer_theme_panel', // Thuoc panel nao
        'priority'    => 10,
    ) );

    // === 3. TAO SETTING ===
    $wp_customize->add_setting( 'developer_header_bg_color', array(
        'default'           => '#23282d',           // Gia tri mac dinh
        'sanitize_callback' => 'sanitize_hex_color', // Ham lam sach du lieu
        'transport'         => 'postMessage',        // Cach cap nhat preview
        // 'refresh'   = reload toan trang (mac dinh)
        // 'postMessage' = cap nhat bang JS (nhanh hon, khong reload)
        'type'              => 'theme_mod',          // Luu o dau
        // 'theme_mod' = luu vao theme_mods (mac dinh, khuyen dung)
        // 'option'    = luu vao wp_options
    ) );

    // === 4. TAO CONTROL ===
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'developer_header_bg_color',  // Phai trung voi setting ID
        array(
            'label'       => __( 'Mau Nen Header', 'developer-theme' ),
            'description' => __( 'Chon mau nen cho header.', 'developer-theme' ),
            'section'     => 'developer_header_section', // Thuoc section nao
            'priority'    => 10,
        )
    ) );
}
add_action( 'customize_register', 'developer_customize_register' );
```

### Lay gia tri da luu:

```php
<?php
// Lay gia tri customizer (dung trong template files)

// Cach 1: get_theme_mod() - khuyen dung
$header_bg = get_theme_mod( 'developer_header_bg_color', '#23282d' );
// Tham so 2 la gia tri mac dinh (truong hop chua luu gi)

// Cach 2: Neu setting type la 'option'
$value = get_option( 'developer_header_bg_color', '#23282d' );

// Su dung trong template:
?>
<header style="background-color: <?php echo esc_attr( get_theme_mod( 'developer_header_bg_color', '#23282d' ) ); ?>">
    ...
</header>

<?php
// Hoac tao CSS tu dong:
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
 * $wp_customize - Object chinh cua Customizer API
 * Cac method quan trong:
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

    // === MODIFY SECTIONS CO SAN ===
    // Di chuyen section vao panel cua minh
    $wp_customize->get_section( 'title_tagline' )->panel = 'developer_theme_panel';
    $wp_customize->get_section( 'colors' )->panel = 'developer_theme_panel';

    // Doi tieu de section
    $wp_customize->get_section( 'title_tagline' )->title = __( 'Logo va Ten Site', 'developer-theme' );

    // Thay doi priority (thu tu)
    $wp_customize->get_section( 'title_tagline' )->priority = 5;

    // === XOA CONTROLS KHONG CAN ===
    // Xoa custom header image
    $wp_customize->remove_section( 'header_image' );

    // Xoa custom background
    $wp_customize->remove_section( 'background_image' );

    // Xoa static front page setting
    // $wp_customize->remove_section( 'static_front_page' );

    // === SELECTIVE REFRESH ===
    $wp_customize->selective_refresh->add_partial( $id, $args );
    $wp_customize->selective_refresh->get_partial( $id );
    $wp_customize->selective_refresh->remove_partial( $id );
}
add_action( 'customize_register', 'developer_customize_full' );
```

---

## 4. Cac loai Control co san

### Controls co ban (built-in):

```php
<?php
function developer_basic_controls( $wp_customize ) {

    // Tao section cho vi du
    $wp_customize->add_section( 'developer_demo_section', array(
        'title' => __( 'Demo Controls', 'developer-theme' ),
    ) );

    // === 1. TEXT INPUT ===
    $wp_customize->add_setting( 'developer_text_field', array(
        'default'           => 'Gia tri mac dinh',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'developer_text_field', array(
        'type'        => 'text',
        'label'       => __( 'Text Input', 'developer-theme' ),
        'description' => __( 'Nhap van ban ngan.', 'developer-theme' ),
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
        'description' => __( 'Nhap van ban dai.', 'developer-theme' ),
        'section'     => 'developer_demo_section',
        'input_attrs' => array(
            'rows'        => 5,
            'placeholder' => __( 'Nhap noi dung...', 'developer-theme' ),
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
        'label'       => __( 'So', 'developer-theme' ),
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
        'label'   => __( 'Bat tinh nang X', 'developer-theme' ),
        'section' => 'developer_demo_section',
    ) );

    // === 8. RADIO BUTTONS ===
    $wp_customize->add_setting( 'developer_radio_field', array(
        'default'           => 'left',
        'sanitize_callback' => 'developer_sanitize_select',
    ) );
    $wp_customize->add_control( 'developer_radio_field', array(
        'type'    => 'radio',
        'label'   => __( 'Vi tri Sidebar', 'developer-theme' ),
        'section' => 'developer_demo_section',
        'choices' => array(
            'left'  => __( 'Ben Trai', 'developer-theme' ),
            'right' => __( 'Ben Phai', 'developer-theme' ),
            'none'  => __( 'Khong Co', 'developer-theme' ),
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
        'label'   => __( 'Chon Trang', 'developer-theme' ),
        'section' => 'developer_demo_section',
    ) );
}
add_action( 'customize_register', 'developer_basic_controls' );
```

### Controls dac biet (WP_Customize_*_Control):

```php
<?php
function developer_special_controls( $wp_customize ) {

    $wp_customize->add_section( 'developer_special_section', array(
        'title' => __( 'Controls Dac Biet', 'developer-theme' ),
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
            'label'   => __( 'Mau Chinh', 'developer-theme' ),
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
            'label'       => __( 'Hinh Nen Header', 'developer-theme' ),
            'description' => __( 'Upload hinh anh 1920x500px.', 'developer-theme' ),
            'section'     => 'developer_special_section',
        )
    ) );

    // === 13. MEDIA UPLOAD (cac loai file) ===
    $wp_customize->add_setting( 'developer_media_field', array(
        'default'           => '',
        'sanitize_callback' => 'absint',  // Luu attachment ID
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control(
        $wp_customize,
        'developer_media_field',
        array(
            'label'     => __( 'Chon Media', 'developer-theme' ),
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
            'label'        => __( 'Ngay Gio', 'developer-theme' ),
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

### Tao control tuy chinh:

```php
<?php
/**
 * Custom Control: Toggle Switch (thay vi checkbox)
 */
class Developer_Toggle_Control extends WP_Customize_Control {

    /**
     * Kieu control
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
     * Render HTML cua control
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
 * De phan chia cac nhom settings trong 1 section
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

// === Dang ky custom controls ===
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
            'label'       => __( 'Hien thi Top Bar', 'developer-theme' ),
            'description' => __( 'Bat/tat thanh top bar o dau trang.', 'developer-theme' ),
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
            'label'       => __( 'Font Chu Noi Dung', 'developer-theme' ),
            'description' => __( 'Chon font chu cho noi dung chinh.', 'developer-theme' ),
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

### Selective Refresh la gi?

Thay vi reload toan trang khi thay doi setting, Selective Refresh chi **cap nhat phan can thiet** cua trang. Nhanh hon va UX tot hon.

```php
<?php
function developer_selective_refresh( $wp_customize ) {

    // === Dang ky Partial ===
    // Partial = phan trang se duoc refresh khi setting thay doi

    // Vi du 1: Cap nhat ten site
    $wp_customize->selective_refresh->add_partial( 'blogname', array(
        'selector'        => '.site-title a',           // CSS selector cua element can refresh
        'render_callback' => function() {                // Ham tra ve HTML moi
            bloginfo( 'name' );
        },
    ) );

    // Vi du 2: Cap nhat mo ta site
    $wp_customize->selective_refresh->add_partial( 'blogdescription', array(
        'selector'        => '.site-description',
        'render_callback' => function() {
            bloginfo( 'description' );
        },
    ) );

    // Vi du 3: Cap nhat footer copyright
    $wp_customize->selective_refresh->add_partial( 'developer_footer_text', array(
        'selector'            => '.site-info',
        'render_callback'     => 'developer_render_footer_text', // Ten ham
        'container_inclusive'  => false,  // false = thay noi dung ben trong selector
                                          // true = thay toan bo element (ca tag cha)
        'fallback_refresh'    => true,   // Reload toan trang neu partial that bai
    ) );

    // Vi du 4: Cap nhat social links
    $wp_customize->selective_refresh->add_partial( 'developer_social_facebook', array(
        'selector'        => '.social-links',
        'render_callback' => 'developer_render_social_links',
        'settings'        => array(                     // Nhieu settings trigger cung 1 partial
            'developer_social_facebook',
            'developer_social_twitter',
            'developer_social_instagram',
            'developer_social_youtube',
        ),
    ) );
}
add_action( 'customize_register', 'developer_selective_refresh' );

// Ham render cho partial
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

### PostMessage voi JavaScript:

```javascript
/**
 * assets/js/customizer-preview.js
 * JS chay trong iframe preview cua Customizer
 *
 * Cap nhat truc tiep DOM khi thay doi setting (khong can reload)
 */
(function($) {
    'use strict';

    // Cap nhat tieu de site
    wp.customize('blogname', function(value) {
        value.bind(function(newval) {
            $('.site-title a').text(newval);
        });
    });

    // Cap nhat mo ta site
    wp.customize('blogdescription', function(value) {
        value.bind(function(newval) {
            $('.site-description').text(newval);
        });
    });

    // Cap nhat mau nen header
    wp.customize('developer_header_bg_color', function(value) {
        value.bind(function(newval) {
            $('.site-header').css('background-color', newval);
        });
    });

    // Cap nhat mau chinh (primary color)
    wp.customize('developer_primary_color', function(value) {
        value.bind(function(newval) {
            // Thay doi CSS variable
            document.documentElement.style.setProperty('--color-primary', newval);
        });
    });

    // Cap nhat font family
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

    // Cap nhat footer text
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
 * Load CSS/JS cho Customizer controls (panel ben trai)
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

### Tai sao can sanitize?

Moi setting PHAI co `sanitize_callback` de lam sach du lieu truoc khi luu. Tranh XSS, SQL injection, va du lieu khong hop le.

```php
<?php
/**
 * Cac ham sanitize co san cua WordPress
 */

// Text ngan (1 dong, loai bo HTML tags)
'sanitize_callback' => 'sanitize_text_field'

// Text dai (nhieu dong)
'sanitize_callback' => 'sanitize_textarea_field'

// Email
'sanitize_callback' => 'sanitize_email'

// URL
'sanitize_callback' => 'esc_url_raw'

// Mau HEX (#ffffff)
'sanitize_callback' => 'sanitize_hex_color'

// So nguyen duong
'sanitize_callback' => 'absint'

// Loai bo tat ca HTML tags
'sanitize_callback' => 'wp_strip_all_tags'

// Cho phep 1 so HTML tags (nhu noi dung bai viet)
'sanitize_callback' => 'wp_kses_post'

// File name
'sanitize_callback' => 'sanitize_file_name'

/**
 * Cac ham sanitize TU TAO
 */

// Checkbox (true/false)
function developer_sanitize_checkbox( $value ) {
    return ( isset( $value ) && true == $value ) ? true : false;
}

// Select/Radio (chi chap nhan cac gia tri da dinh nghia)
function developer_sanitize_select( $input, $setting ) {
    // Lay danh sach choices tu control
    $choices = $setting->manager->get_control( $setting->id )->choices;

    // Kiem tra input co nam trong choices khong
    return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

// So trong khoang
function developer_sanitize_range( $input, $setting ) {
    $control = $setting->manager->get_control( $setting->id );
    $attrs   = $control->input_attrs;

    $min  = isset( $attrs['min'] ) ? $attrs['min'] : 0;
    $max  = isset( $attrs['max'] ) ? $attrs['max'] : 100;
    $step = isset( $attrs['step'] ) ? $attrs['step'] : 1;

    $number = absint( $input );
    return ( $number >= $min && $number <= $max ) ? $number : $setting->default;
}

// CSS an toan
function developer_sanitize_css( $input ) {
    return wp_strip_all_tags( $input );
}

// HTML gioi han
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
 * Quan ly gia tri mac dinh tap trung
 * Dat trong inc/customizer-defaults.php
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
 * Helper: Lay gia tri customizer voi default
 */
function developer_get_option( $key ) {
    $defaults = developer_get_defaults();
    $default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
    return get_theme_mod( $key, $default );
}

// Su dung trong template:
$primary_color = developer_get_option( 'developer_primary_color' );
$body_font     = developer_get_option( 'developer_body_font' );
$show_topbar   = developer_get_option( 'developer_show_topbar' );
```

---

## 9. Code vi du: Theme Customizer hoan chinh

### inc/customizer.php:

```php
<?php
/**
 * Theme Customizer hoan chinh
 *
 * File nay duoc require tu functions.php:
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
 * Dang ky tat ca customizer settings
 */
function developer_full_customize_register( $wp_customize ) {

    // === PANEL CHINH ===
    $wp_customize->add_panel( 'developer_panel', array(
        'title'    => __( 'Cai Dat Theme Developer', 'developer-theme' ),
        'priority' => 25,
    ) );

    // Di chuyen sections co san vao panel
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
            'label'   => __( 'Hien Thi Top Bar', 'developer-theme' ),
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
            'label'   => __( 'Header Dinh (Sticky)', 'developer-theme' ),
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
            'label'   => __( 'Mau Nen Header', 'developer-theme' ),
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
            'label'   => __( 'Mau Chu Header', 'developer-theme' ),
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
        'label'   => __( 'Kieu Header', 'developer-theme' ),
        'section' => 'developer_header',
        'choices' => array(
            'default'  => __( 'Mac dinh (Logo trai, Menu phai)', 'developer-theme' ),
            'centered' => __( 'Logo giua', 'developer-theme' ),
            'stacked'  => __( 'Logo tren, Menu duoi', 'developer-theme' ),
        ),
    ) );

    // ============================================================
    // SECTION: COLORS
    // ============================================================
    $wp_customize->add_section( 'developer_colors', array(
        'title'    => __( 'Mau Sac', 'developer-theme' ),
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
            'label'       => __( 'Mau Chinh (Primary)', 'developer-theme' ),
            'description' => __( 'Dung cho links, buttons, accents.', 'developer-theme' ),
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
            'label'   => __( 'Mau Phu (Secondary)', 'developer-theme' ),
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
            'label'   => __( 'Mau Nhan Manh (Accent)', 'developer-theme' ),
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
            'label'   => __( 'Font Noi Dung', 'developer-theme' ),
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
            'label'   => __( 'Font Tieu De', 'developer-theme' ),
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
        'label'       => __( 'Co Chu (px)', 'developer-theme' ),
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
        'label'       => __( 'Chieu Rong Container (px)', 'developer-theme' ),
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
        'label'   => __( 'Vi Tri Sidebar', 'developer-theme' ),
        'section' => 'developer_layout',
        'choices' => array(
            'right' => __( 'Ben Phai', 'developer-theme' ),
            'left'  => __( 'Ben Trai', 'developer-theme' ),
            'none'  => __( 'Khong Co Sidebar', 'developer-theme' ),
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
            'label'   => __( 'Mau Nen Footer', 'developer-theme' ),
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
        'label'   => __( 'So Cot Footer', 'developer-theme' ),
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
        'title'    => __( 'Mang Xa Hoi', 'developer-theme' ),
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
 * Output Customizer CSS vao <head>
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
 * Load Google Fonts neu can
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
            null // null = khong them version
        );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_load_google_fonts', 5 );
```

---

## 10. Best Practices

### 1. Prefix tat ca settings

```php
// DUNG: Prefix voi ten theme
'developer_header_bg_color'
'developer_primary_color'
'developer_show_topbar'

// SAI: Ten qua chung
'header_color'
'primary_color'
'show_topbar'
```

### 2. Luon co sanitize_callback

```php
// MOI setting PHAI co sanitize_callback
// KHONG BAO GIO bo trong
$wp_customize->add_setting( 'my_setting', array(
    'sanitize_callback' => 'sanitize_text_field', // BAT BUOC
) );
```

### 3. Dung transport postMessage khi co the

```php
// postMessage = nhanh, khong reload trang
// refresh = cham, reload toan trang

// Dung postMessage cho: colors, fonts, text, toggle
'transport' => 'postMessage'

// Dung refresh cho: layout changes, template changes
'transport' => 'refresh'
```

### 4. Default values tap trung

```php
// Tap trung defaults vao 1 ham
function developer_get_defaults() {
    return array( ... );
}

// Dung trong setting:
$defaults = developer_get_defaults();
'default' => $defaults['developer_primary_color']

// Dung trong template:
get_theme_mod( 'developer_primary_color', $defaults['developer_primary_color'] )
```

### 5. Selective Refresh cho UX tot

```php
// Uu tien Selective Refresh hon postMessage
// Vi Selective Refresh dung PHP render, chinh xac hon JS
$wp_customize->selective_refresh->add_partial( ... );
```

### 6. Tach file

```php
// Tach customizer code ra file rieng
// functions.php:
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/customizer-defaults.php';
require get_template_directory() . '/inc/customizer-controls.php';
```

---

**Tiep theo:** [06 - Block Theme va FSE](./06-block-theme-va-fse.md) - Tim hieu Full Site Editing voi Block Theme
