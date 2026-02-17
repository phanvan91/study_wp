# 06 - Giao Diện (Appearance) trong WordPress Admin

> Tài liệu dành cho PHP Laravel developer chuyển sang WordPress.
> Phân tích chi tiết Themes, Customizer, Widgets, Navigation Menus, Theme Editor, hooks và cách lưu DB.

---

## Mục Lục

1. [Tổng Quan Appearance Menu](#1-tổng-quan-appearance-menu)
2. [Themes Management (themes.php)](#2-themes-management-themesphp)
3. [Theme Install (theme-install.php)](#3-theme-install-theme-installphp)
4. [Theme Customizer (customize.php)](#4-theme-customizer-customizephp)
5. [Customizer API Chi Tiết](#5-customizer-api-chi-tiết)
6. [Changeset System](#6-changeset-system)
7. [Widgets Management (widgets.php)](#7-widgets-management-widgetsphp)
8. [Block Widget Editor (WP 5.8+)](#8-block-widget-editor-wp-58)
9. [Navigation Menus (nav-menus.php)](#9-navigation-menus-nav-menusphp)
10. [Menu Items Chi Tiết](#10-menu-items-chi-tiết)
11. [Menu Locations](#11-menu-locations)
12. [Theme File Editor (theme-editor.php)](#12-theme-file-editor-theme-editorphp)
13. [Custom Header và Custom Background](#13-custom-header-và-custom-background)
14. [DB: Giao Diện Lưu Gì?](#14-db-giao-diện-lưu-gì)
15. [Hooks Giao Diện - Danh Sách Đầy Đủ](#15-hooks-giao-diện---danh-sách-đầy-đủ)
16. [Full Site Editing (FSE) - Block Themes](#16-full-site-editing-fse---block-themes)
17. [Ví Dụ Thực Tế: Theme Development](#17-ví-dụ-thực-tế-theme-development)
18. [So Sánh Với Laravel](#18-so-sánh-với-laravel)
19. [Tổng Kết](#19-tổng-kết)

---

## 1. Tổng Quan Appearance Menu

### Submenu Items

| Trang | URL | Capability | Source File |
|-------|-----|------------|-------------|
| Themes | `/wp-admin/themes.php` | `switch_themes` / `edit_theme_options` | `wp-admin/themes.php` |
| Customize | `/wp-admin/customize.php` | `customize` | `wp-admin/customize.php` |
| Widgets | `/wp-admin/widgets.php` | `edit_theme_options` | `wp-admin/widgets.php` |
| Menus | `/wp-admin/nav-menus.php` | `edit_theme_options` | `wp-admin/nav-menus.php` |
| Theme File Editor | `/wp-admin/theme-editor.php` | `edit_themes` | `wp-admin/theme-editor.php` |
| Header* | Custom header page | `edit_theme_options` | (nếu theme supports) |
| Background* | Custom background page | `edit_theme_options` | (nếu theme supports) |

> (*) Chỉ hiện khi theme khai báo `add_theme_support()`.

### Source Files Chính

```
wp-admin/
├── themes.php                              # Theme list & actions
├── theme-install.php                       # Install from repo
├── customize.php                           # Customizer entry point
├── widgets.php                             # Widget router (36 dòng)
├── widgets-form.php                        # Classic widget editor
├── widgets-form-blocks.php                 # Block widget editor (WP 5.8+)
├── nav-menus.php                           # Menu management
├── theme-editor.php                        # Theme file editor
├── includes/
│   ├── theme.php                           # Theme API functions
│   ├── class-wp-themes-list-table.php      # Theme list table
│   ├── widgets.php                         # Widget admin functions
│   └── nav-menu.php                        # Nav menu admin functions
wp-includes/
├── class-wp-customize-manager.php          # Customizer manager
├── class-wp-customize-control.php          # Base control class
├── class-wp-customize-setting.php          # Base setting class
├── class-wp-customize-section.php          # Base section class
├── class-wp-customize-panel.php            # Base panel class
├── class-wp-widget.php                     # Base widget class
├── widgets/                                # Built-in widgets
├── nav-menu.php                            # Nav menu API
└── theme.php                               # Theme API
```

---

## 2. Themes Management (themes.php)

**Source**: `wp-admin/themes.php`

### Kiểm tra quyền

```php
// Source: wp-admin/themes.php dòng 12-18
if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) ) {
    wp_die(
        '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
        '<p>' . __( 'Sorry, you are not allowed to edit theme options on this site.' ) . '</p>',
        403
    );
}
```

### Theme Actions

#### Activate Theme

```php
// Source: wp-admin/themes.php dòng 20-35
if ( current_user_can( 'switch_themes' ) && isset( $_GET['action'] ) ) {
    if ( 'activate' === $_GET['action'] ) {
        check_admin_referer( 'switch-theme_' . $_GET['stylesheet'] );
        $theme = wp_get_theme( $_GET['stylesheet'] );

        if ( ! $theme->exists() || ! $theme->is_allowed() ) {
            wp_die( /* theme không tồn tại */ );
        }

        switch_theme( $theme->get_stylesheet() );
        wp_redirect( admin_url( 'themes.php?activated=true' ) );
        exit;
    }
}
```

Hàm `switch_theme()` thực hiện:

```php
// Source: wp-includes/theme.php
function switch_theme( $stylesheet ) {
    // Lấy theme cũ
    $old_theme = wp_get_theme();

    // Cập nhật options
    update_option( 'template',   $new_theme->get_template() );
    update_option( 'stylesheet', $new_theme->get_stylesheet() );

    // Xóa widget cache
    // Xóa customizer cache

    /**
     * Fires khi switch theme
     * @param string   $new_name  Tên theme mới
     * @param WP_Theme $new_theme Theme mới
     * @param WP_Theme $old_theme Theme cũ
     */
    do_action( 'switch_theme', $new_name, $new_theme, $old_theme );
}
```

#### Delete Theme

```php
// Source: wp-admin/themes.php dòng 56-83
if ( 'delete' === $_GET['action'] ) {
    check_admin_referer( 'delete-theme_' . $_GET['stylesheet'] );

    if ( ! current_user_can( 'delete_themes' ) ) {
        wp_die( /* không có quyền */ );
    }

    // Không cho xóa theme đang active
    $active = wp_get_theme();
    if ( $active->get( 'Template' ) === $_GET['stylesheet'] ) {
        wp_redirect( admin_url( 'themes.php?delete-active-child=true' ) );
    } else {
        delete_theme( $_GET['stylesheet'] );
        wp_redirect( admin_url( 'themes.php?deleted=true' ) );
    }
    exit;
}
```

#### Resume Theme (sau lỗi)

```php
// Source: wp-admin/themes.php dòng 36-55
if ( 'resume' === $_GET['action'] ) {
    check_admin_referer( 'resume-theme_' . $_GET['stylesheet'] );

    if ( ! current_user_can( 'resume_theme', $_GET['stylesheet'] ) ) {
        wp_die( /* không có quyền */ );
    }

    $result = resume_theme( $theme->get_stylesheet(), self_admin_url( 'themes.php?error=resuming' ) );
    wp_redirect( admin_url( 'themes.php?resumed=true' ) );
    exit;
}
```

#### Auto-Update Theme

```php
// Source: wp-admin/themes.php dòng 84-99
if ( 'enable-auto-update' === $_GET['action'] ) {
    $auto_updates   = (array) get_site_option( 'auto_update_themes', array() );
    $auto_updates[] = $_GET['stylesheet'];
    $auto_updates   = array_unique( $auto_updates );
    update_site_option( 'auto_update_themes', $auto_updates );
}

if ( 'disable-auto-update' === $_GET['action'] ) {
    $auto_updates = (array) get_site_option( 'auto_update_themes', array() );
    $auto_updates = array_diff( $auto_updates, array( $_GET['stylesheet'] ) );
    update_site_option( 'auto_update_themes', $auto_updates );
}
```

### WP_Theme Class

```php
$theme = wp_get_theme(); // Theme hiện tại
// hoặc
$theme = wp_get_theme( 'theme-slug' ); // Theme cụ thể

// Lấy thông tin
$theme->get( 'Name' );          // Tên theme
$theme->get( 'Version' );       // Version
$theme->get( 'Author' );        // Tác giả
$theme->get( 'Description' );   // Mô tả
$theme->get( 'Template' );      // Parent theme (child theme)
$theme->get( 'TextDomain' );    // Text domain
$theme->get( 'Tags' );          // Tags

$theme->get_stylesheet();       // Stylesheet directory name
$theme->get_template();         // Template directory name
$theme->get_stylesheet_directory(); // Full path to stylesheet dir
$theme->get_template_directory();   // Full path to template dir

$theme->exists();               // Theme có tồn tại?
$theme->is_allowed();           // Được phép sử dụng?
$theme->parent();               // WP_Theme parent (child theme)

// Lấy tất cả themes
$themes = wp_get_themes();
```

---

## 3. Theme Install (theme-install.php)

**Source**: `wp-admin/theme-install.php`

### Tabs cài đặt

```php
// Tabs trên trang install
$tabs = array(
    'featured'  => _x( 'Featured', 'themes' ),
    'popular'   => _x( 'Popular', 'themes' ),
    'new'       => _x( 'Latest', 'themes' ),
    'favorites' => _x( 'Favorites', 'themes' ),
    'upload'    => __( 'Upload Theme' ),
);

// Filter tabs
$tabs = apply_filters( 'install_themes_tabs', $tabs );
```

### API WordPress.org

```php
// Query themes từ WordPress.org
$api = themes_api( 'query_themes', array(
    'page'     => 1,
    'per_page' => 36,
    'browse'   => 'popular',   // featured, popular, new
    'search'   => 'keyword',
    'tag'      => array( 'full-width-template', 'blog' ),
    'author'   => 'developer-name',
) );

// URL API: https://api.wordpress.org/themes/info/1.2/
```

### Upload Theme .zip

```php
// Sử dụng WP_Theme_Install_List_Table và Theme_Upgrader
$upgrader = new Theme_Upgrader( new Theme_Installer_Skin() );
$result   = $upgrader->install( $package_url ); // URL tới .zip file
```

---

## 4. Theme Customizer (customize.php)

**Source**: `wp-admin/customize.php`

### Kiểm tra quyền

```php
// Source: wp-admin/customize.php dòng 15-21
if ( ! current_user_can( 'customize' ) ) {
    wp_die(
        '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
        '<p>' . __( 'Sorry, you are not allowed to customize this site.' ) . '</p>',
        403
    );
}
```

### WP_Customize_Manager

**Source**: `wp-includes/class-wp-customize-manager.php`

```php
global $wp_customize;
// $wp_customize là instance của WP_Customize_Manager

// Cấu trúc phân cấp:
// Panels → Sections → Controls → Settings

// Panel:   Nhóm lớn (ít dùng, chỉ khi nhiều sections)
// Section: Tab trong sidebar customizer
// Control: UI element (color picker, text input, v.v.)
// Setting: Giá trị thực tế lưu vào DB
```

### Built-in Sections

| Section ID | Tên hiển thị | Mô tả |
|------------|-------------|-------|
| `title_tagline` | Site Identity | Site title, tagline, logo, icon |
| `colors` | Colors | Header text color, v.v. |
| `header_image` | Header Image | Ảnh header |
| `background_image` | Background Image | Ảnh nền |
| `nav_menus` | Menus | Quản lý menus (panel) |
| `widgets` | Widgets | Quản lý widgets (panel) |
| `static_front_page` | Homepage Settings | Front page, Posts page |
| `custom_css` | Additional CSS | CSS tùy chỉnh |

---

## 5. Customizer API Chi Tiết

### Đăng ký Setting, Section, Control

```php
add_action( 'customize_register', function( $wp_customize ) {
    // ============================================
    // 1. PANEL (Nhóm nhiều sections)
    // ============================================
    $wp_customize->add_panel( 'theme_options', array(
        'title'       => __( 'Tùy Chỉnh Theme' ),
        'description' => __( 'Các thiết lập giao diện' ),
        'priority'    => 10,
    ) );

    // ============================================
    // 2. SECTION (Tab trong sidebar)
    // ============================================
    $wp_customize->add_section( 'theme_colors', array(
        'title'    => __( 'Màu Sắc' ),
        'panel'    => 'theme_options', // Thuộc panel nào
        'priority' => 10,
    ) );

    $wp_customize->add_section( 'theme_layout', array(
        'title'    => __( 'Bố Cục' ),
        'panel'    => 'theme_options',
        'priority' => 20,
    ) );

    // ============================================
    // 3. SETTING (Giá trị lưu vào DB)
    // ============================================

    // Setting kiểu 'theme_mod' (mặc định, lưu vào theme_mods_{theme})
    $wp_customize->add_setting( 'primary_color', array(
        'default'           => '#0073aa',
        'transport'         => 'postMessage', // Realtime preview (no refresh)
        'sanitize_callback' => 'sanitize_hex_color',
        'type'              => 'theme_mod',   // Mặc định
    ) );

    // Setting kiểu 'option' (lưu vào wp_options)
    $wp_customize->add_setting( 'my_plugin_option', array(
        'default'           => '',
        'transport'         => 'refresh',     // Refresh preview khi thay đổi
        'sanitize_callback' => 'sanitize_text_field',
        'type'              => 'option',
        'capability'        => 'manage_options',
    ) );

    // ============================================
    // 4. CONTROL (UI Element)
    // ============================================

    // Color Picker
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'primary_color',
        array(
            'label'   => __( 'Màu Chính' ),
            'section' => 'theme_colors',
        )
    ) );

    // Text Input
    $wp_customize->add_control( 'footer_text', array(
        'label'   => __( 'Footer Text' ),
        'section' => 'theme_layout',
        'type'    => 'text',
        'setting' => 'footer_text',
    ) );

    // Textarea
    $wp_customize->add_control( 'custom_code', array(
        'label'   => __( 'Custom Code' ),
        'section' => 'theme_layout',
        'type'    => 'textarea',
    ) );

    // Select
    $wp_customize->add_control( 'sidebar_position', array(
        'label'   => __( 'Vị Trí Sidebar' ),
        'section' => 'theme_layout',
        'type'    => 'select',
        'choices' => array(
            'left'  => __( 'Bên Trái' ),
            'right' => __( 'Bên Phải' ),
            'none'  => __( 'Không Sidebar' ),
        ),
    ) );

    // Radio
    $wp_customize->add_control( 'blog_layout', array(
        'label'   => __( 'Blog Layout' ),
        'section' => 'theme_layout',
        'type'    => 'radio',
        'choices' => array(
            'grid' => __( 'Grid' ),
            'list' => __( 'List' ),
        ),
    ) );

    // Checkbox
    $wp_customize->add_control( 'show_breadcrumb', array(
        'label'   => __( 'Hiển thị Breadcrumb' ),
        'section' => 'theme_layout',
        'type'    => 'checkbox',
    ) );

    // Image Upload
    $wp_customize->add_control( new WP_Customize_Image_Control(
        $wp_customize,
        'hero_image',
        array(
            'label'   => __( 'Ảnh Hero' ),
            'section' => 'theme_layout',
        )
    ) );

    // Media (file bất kỳ)
    $wp_customize->add_control( new WP_Customize_Media_Control(
        $wp_customize,
        'hero_video',
        array(
            'label'     => __( 'Video Hero' ),
            'section'   => 'theme_layout',
            'mime_type' => 'video',
        )
    ) );
});
```

### Sử dụng trong template

```php
// Lấy giá trị theme_mod
$primary_color = get_theme_mod( 'primary_color', '#0073aa' );
$sidebar_pos   = get_theme_mod( 'sidebar_position', 'right' );
$show_breadcrumb = get_theme_mod( 'show_breadcrumb', false );

// Lấy giá trị option
$plugin_option = get_option( 'my_plugin_option', '' );

// Sử dụng trong CSS
?>
<style>
:root {
    --primary-color: <?php echo esc_attr( $primary_color ); ?>;
}
</style>
```

### Transport: postMessage vs refresh

```php
// 'refresh': Trang preview reload khi thay đổi value (chậm)
// 'postMessage': JavaScript cập nhật realtime (nhanh, nhưng cần JS)

// Với postMessage, cần thêm JS preview:
add_action( 'customize_preview_init', function() {
    wp_enqueue_script(
        'theme-customizer-preview',
        get_template_directory_uri() . '/js/customizer-preview.js',
        array( 'customize-preview' ),
        '1.0.0',
        true
    );
});
```

```javascript
// customizer-preview.js
( function( $ ) {
    // Realtime preview cho primary_color
    wp.customize( 'primary_color', function( value ) {
        value.bind( function( newval ) {
            document.documentElement.style.setProperty( '--primary-color', newval );
        } );
    } );

    // Realtime preview cho footer_text
    wp.customize( 'footer_text', function( value ) {
        value.bind( function( newval ) {
            $( '.site-footer .footer-text' ).text( newval );
        } );
    } );
} )( jQuery );
```

### Selective Refresh (Partial Refresh)

Tối ưu hơn postMessage - chỉ refresh một phần trang:

```php
add_action( 'customize_register', function( $wp_customize ) {
    // Đăng ký partial
    $wp_customize->selective_refresh->add_partial( 'footer_text', array(
        'selector'        => '.site-footer .footer-text',
        'render_callback' => function() {
            return get_theme_mod( 'footer_text', 'Default footer' );
        },
    ) );
});
```

---

## 6. Changeset System

Từ WordPress 4.7, Customizer sử dụng Changeset để quản lý thay đổi.

### Cách hoạt động

```
User mở Customizer
    │
    ▼
Tạo changeset (post_type = 'customize_changeset')
    │ UUID unique cho mỗi session
    ▼
User thay đổi settings
    │ Mỗi thay đổi lưu vào changeset (post_content = JSON)
    ▼
User click "Publish"
    │
    ├── Draft → Publish: Áp dụng tất cả thay đổi
    ├── Draft → Schedule: Lên lịch publish
    └── Share Preview URL: Chia sẻ link preview
```

### DB lưu changeset

```php
// wp_posts: post_type = 'customize_changeset'
$changeset = array(
    'post_type'    => 'customize_changeset',
    'post_status'  => 'draft',        // draft, publish, future (scheduled)
    'post_name'    => $uuid,           // UUID duy nhất
    'post_content' => json_encode( array(
        'primary_color' => array(
            'value'   => '#ff0000',
            'type'    => 'theme_mod',
            'user_id' => 1,
        ),
        'blogname' => array(
            'value'   => 'My New Site Name',
            'type'    => 'option',
            'user_id' => 1,
        ),
    ) ),
);
```

### Schedule Changeset

```php
// Lên lịch publish changeset
// Status: 'future'
// post_date: thời gian publish trong tương lai
// WP Cron sẽ tự publish khi đến giờ
```

---

## 7. Widgets Management (widgets.php)

**Source**: `wp-admin/widgets.php` (36 dong - router file)

```php
// Source: wp-admin/widgets.php dòng 9-35
require_once __DIR__ . '/admin.php';
require_once ABSPATH . 'wp-admin/includes/widgets.php';

if ( ! current_user_can( 'edit_theme_options' ) ) {
    wp_die( /* ... */ );
}

if ( ! current_theme_supports( 'widgets' ) ) {
    wp_die( __( 'The theme you are currently using is not widget-aware...' ) );
}

// Router: Block editor hoặc Classic editor
if ( wp_use_widgets_block_editor() ) {
    require ABSPATH . 'wp-admin/widgets-form-blocks.php';
} else {
    require ABSPATH . 'wp-admin/widgets-form.php';
}
```

### Đăng ký Sidebar

```php
// Trong functions.php của theme
add_action( 'widgets_init', function() {
    // Sidebar chính
    register_sidebar( array(
        'name'          => __( 'Sidebar Chính' ),
        'id'            => 'main-sidebar',
        'description'   => __( 'Widget area bên phải' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer widgets
    register_sidebar( array(
        'name'          => __( 'Footer Col 1' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer Col 2' ),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
});
```

### Hiển thị Sidebar trong template

```php
<!-- sidebar.php -->
<?php if ( is_active_sidebar( 'main-sidebar' ) ) : ?>
    <aside id="sidebar" class="widget-area">
        <?php dynamic_sidebar( 'main-sidebar' ); ?>
    </aside>
<?php endif; ?>

<!-- footer.php -->
<footer class="site-footer">
    <div class="footer-widgets">
        <div class="col">
            <?php dynamic_sidebar( 'footer-1' ); ?>
        </div>
        <div class="col">
            <?php dynamic_sidebar( 'footer-2' ); ?>
        </div>
    </div>
</footer>
```

### Tạo Custom Widget

```php
class My_Recent_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'my_recent_posts',          // Base ID
            __( 'Bài Viết Gần Đây' ),  // Name
            array(
                'description' => __( 'Hiển thị bài viết gần đây với thumbnail' ),
                'classname'   => 'widget-my-recent-posts',
            )
        );
    }

    // Frontend output
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $count = $instance['count'] ?? 5;

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $posts = get_posts( array(
            'posts_per_page' => $count,
            'post_status'    => 'publish',
        ) );

        echo '<ul class="recent-posts-list">';
        foreach ( $posts as $post ) {
            $thumb = get_the_post_thumbnail( $post->ID, 'thumbnail' );
            echo '<li>';
            if ( $thumb ) echo '<div class="thumb">' . $thumb . '</div>';
            echo '<a href="' . get_permalink( $post ) . '">' . esc_html( $post->post_title ) . '</a>';
            echo '<span class="date">' . get_the_date( '', $post ) . '</span>';
            echo '</li>';
        }
        echo '</ul>';

        echo $args['after_widget'];
    }

    // Admin form
    public function form( $instance ) {
        $title = $instance['title'] ?? __( 'Bài Viết Gần Đây' );
        $count = $instance['count'] ?? 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Tiêu đề:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Số bài:' ); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( 'count' ); ?>"
                   name="<?php echo $this->get_field_name( 'count' ); ?>"
                   type="number" min="1" max="20" value="<?php echo esc_attr( $count ); ?>">
        </p>
        <?php
    }

    // Save settings
    public function update( $new_instance, $old_instance ) {
        return array(
            'title' => sanitize_text_field( $new_instance['title'] ),
            'count' => absint( $new_instance['count'] ),
        );
    }
}

// Đăng ký widget
add_action( 'widgets_init', function() {
    register_widget( 'My_Recent_Posts_Widget' );
});
```

### DB lưu Widgets

```php
// wp_options: sidebars_widgets
// Mapping sidebar → widget instances
get_option( 'sidebars_widgets' );
/*
Array (
    'main-sidebar' => array(
        'my_recent_posts-2',
        'search-2',
        'categories-3',
    ),
    'footer-1' => array(
        'text-4',
        'nav_menu-2',
    ),
    'footer-2' => array(
        'tag_cloud-2',
    ),
    'wp_inactive_widgets' => array(
        'archives-2',
    ),
)
*/

// wp_options: widget_{widget_type}
// Settings cho từng widget instance
get_option( 'widget_my_recent_posts' );
/*
Array (
    2 => array(
        'title' => 'Bài Viết Mới',
        'count' => 5,
    ),
    '_multiwidget' => 1,
)
*/
```

---

## 8. Block Widget Editor (WP 5.8+)

Từ WordPress 5.8, Widget Editor sử dụng Block Editor (Gutenberg).

```php
// Kiểm tra có dùng block widget editor không
wp_use_widgets_block_editor(); // true/false

// Tắt block widget editor, quay về classic
add_filter( 'use_widgets_block_editor', '__return_false' );
```

### Source

```php
// Source: wp-admin/widgets-form-blocks.php
// Enqueue block editor cho widget area
wp_enqueue_script( 'wp-customize-widgets' );
wp_enqueue_script( 'wp-widgets' );
wp_enqueue_style( 'wp-edit-widgets' );
```

---

## 9. Navigation Menus (nav-menus.php)

**Source**: `wp-admin/nav-menus.php`

### Kiểm tra quyền và support

```php
// Source: wp-admin/nav-menus.php dòng 18-29
if ( ! current_theme_supports( 'menus' ) && ! current_theme_supports( 'widgets' ) ) {
    wp_die( __( 'Your theme does not support navigation menus or widgets.' ) );
}

if ( ! current_user_can( 'edit_theme_options' ) ) {
    wp_die( /* ... */ );
}
```

### Đăng ký Menu Locations trong Theme

```php
// functions.php
add_action( 'after_setup_theme', function() {
    register_nav_menus( array(
        'primary'   => __( 'Menu Chính' ),
        'footer'    => __( 'Menu Footer' ),
        'mobile'    => __( 'Menu Mobile' ),
        'social'    => __( 'Menu Mạng Xã Hội' ),
    ) );
});

// Hoặc đăng ký từng cái
register_nav_menu( 'primary', __( 'Menu Chính' ) );
```

### Tạo Menu

```php
// Tạo menu mới
$menu_id = wp_create_nav_menu( 'Menu Chính' );
// → Tạo term trong taxonomy 'nav_menu'
// → Trả về term_id

// Xóa menu
wp_delete_nav_menu( $menu_id );
```

### Menu Actions trong Admin

```php
// Source: wp-admin/nav-menus.php dòng 63-...
switch ( $action ) {
    case 'add-menu-item':
        check_admin_referer( 'add-menu_item', 'menu-settings-column-nonce' );
        wp_save_nav_menu_items( $nav_menu_selected_id, $_REQUEST['menu-item'] );
        break;

    case 'move-down-menu-item':
    case 'move-up-menu-item':
        // Sắp xếp thứ tự menu items
        break;

    case 'delete-menu-item':
        wp_delete_post( $menu_item_id, true ); // Force delete
        break;

    case 'delete':
        // Xóa menu
        wp_delete_nav_menu( $nav_menu_selected_id );
        break;

    case 'update':
        // Cập nhật menu name, locations, items
        wp_update_nav_menu_object( $nav_menu_selected_id, array(
            'menu-name' => $_POST['menu-name'],
        ) );

        // Cập nhật menu locations
        if ( isset( $_POST['menu-locations'] ) ) {
            set_theme_mod( 'nav_menu_locations', array_map( 'absint', $_POST['menu-locations'] ) );
        }

        // Cập nhật từng menu item
        wp_save_nav_menu_items( $nav_menu_selected_id, $_POST['menu-item-db-id'] );
        break;
}
```

---

## 10. Menu Items Chi Tiết

### Menu Item Types

| Type | Mô tả | `_menu_item_type` | `_menu_item_object` |
|------|--------|-------------------|---------------------|
| Post/Page | Link tới post hoặc page | `'post_type'` | `'post'` / `'page'` |
| Category | Link tới category archive | `'taxonomy'` | `'category'` |
| Tag | Link tới tag archive | `'taxonomy'` | `'post_tag'` |
| Custom Link | URL tùy ý | `'custom'` | `'custom'` |
| Custom Post Type | Link tới CPT | `'post_type'` | `'{cpt_name}'` |
| Custom Taxonomy | Link tới taxonomy archive | `'taxonomy'` | `'{taxonomy_name}'` |

### Thêm Menu Item bằng code

```php
// Thêm page vào menu
wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title'     => 'Trang Chủ',
    'menu-item-object-id' => get_option( 'page_on_front' ),
    'menu-item-object'    => 'page',
    'menu-item-type'      => 'post_type',
    'menu-item-status'    => 'publish',
) );

// Thêm custom link
wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title'  => 'Google',
    'menu-item-url'    => 'https://google.com',
    'menu-item-type'   => 'custom',
    'menu-item-status' => 'publish',
) );

// Thêm category
wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title'     => 'Tin Tức',
    'menu-item-object-id' => $category_id,
    'menu-item-object'    => 'category',
    'menu-item-type'      => 'taxonomy',
    'menu-item-status'    => 'publish',
) );

// Thêm sub-menu item (con)
$parent_item_id = wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title'  => 'Parent',
    'menu-item-url'    => '#',
    'menu-item-type'   => 'custom',
    'menu-item-status' => 'publish',
) );

wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title'     => 'Child Item',
    'menu-item-url'       => '/child-page',
    'menu-item-type'      => 'custom',
    'menu-item-parent-id' => $parent_item_id,  // Parent!
    'menu-item-status'    => 'publish',
) );
```

### Hiển thị Menu trong Template

```php
// Cách 1: Theo location
wp_nav_menu( array(
    'theme_location' => 'primary',
    'container'      => 'nav',
    'container_class'=> 'main-navigation',
    'container_id'   => 'primary-menu',
    'menu_class'     => 'menu-list',
    'menu_id'        => 'primary-menu-list',
    'fallback_cb'    => 'wp_page_menu', // Fallback nếu chưa set menu
    'depth'          => 3,               // Độ sâu submenu
    'walker'         => new Custom_Walker_Nav_Menu(), // Custom output
) );

// Cách 2: Theo menu name/id
wp_nav_menu( array(
    'menu' => 'Menu Chính', // hoặc menu ID
) );
```

### Custom Walker

```php
class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {

    // Mở thẻ <ul> cho submenu
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="sub-menu depth-' . $depth . '">';
    }

    // Mở thẻ <li> cho mỗi item
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item = $data_object;

        $classes = implode( ' ', $item->classes );
        $output .= '<li class="' . esc_attr( $classes ) . '">';
        $output .= '<a href="' . esc_url( $item->url ) . '"';

        if ( $item->target ) {
            $output .= ' target="' . esc_attr( $item->target ) . '"';
        }

        $output .= '>';
        $output .= esc_html( $item->title );

        // Hiển thị description nếu có
        if ( $item->description && $depth === 0 ) {
            $output .= '<span class="menu-description">' . esc_html( $item->description ) . '</span>';
        }

        $output .= '</a>';
    }
}
```

---

## 11. Menu Locations

### Assign Menu tới Location

```php
// Cách 1: Trong admin (nav-menus.php) - checkbox "Display location"
// Cách 2: Bằng code
$locations = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary'] = $menu_id;
$locations['footer']  = $footer_menu_id;
set_theme_mod( 'nav_menu_locations', $locations );
```

### Lấy Menu Location hiện tại

```php
// Lấy tất cả locations đã đăng ký
$registered = get_registered_nav_menus();
/*
Array (
    'primary' => 'Menu Chính',
    'footer'  => 'Menu Footer',
)
*/

// Lấy menu ID cho từng location
$locations = get_nav_menu_locations();
/*
Array (
    'primary' => 5,  // menu term_id
    'footer'  => 8,
)
*/

// Kiểm tra location có menu chưa
has_nav_menu( 'primary' ); // true/false
```

---

## 12. Theme File Editor (theme-editor.php)

**Source**: `wp-admin/theme-editor.php`

```php
// Source: wp-admin/theme-editor.php dòng 17-19
if ( ! current_user_can( 'edit_themes' ) ) {
    wp_die( '<p>' . __( 'Sorry, you are not allowed to edit templates for this site.' ) . '</p>' );
}
```

### Disable Theme Editor (Khuyến nghị cho production)

```php
// Trong wp-config.php
define( 'DISALLOW_FILE_EDIT', true );  // Tắt Theme + Plugin Editor
define( 'DISALLOW_FILE_MODS', true );  // Tắt luôn cả install/update themes/plugins
```

### Theme Editor cho phép sửa

Tất cả file trong thư mục theme: `.php`, `.css`, `.js`, `.json`, `.html`, v.v.

Danh sách file descriptions được định nghĩa trong `wp-admin/includes/file.php`:

```php
// Source: wp-admin/includes/file.php dòng 15-66
$wp_file_descriptions = array(
    'functions.php'     => __( 'Theme Functions' ),
    'header.php'        => __( 'Theme Header' ),
    'footer.php'        => __( 'Theme Footer' ),
    'sidebar.php'       => __( 'Sidebar' ),
    'comments.php'      => __( 'Comments' ),
    'index.php'         => __( 'Main Index Template' ),
    'single.php'        => __( 'Single Post' ),
    'page.php'          => __( 'Single Page' ),
    'archive.php'       => __( 'Archives' ),
    'search.php'        => __( 'Search Results' ),
    '404.php'           => __( '404 Template' ),
    'style.css'         => __( 'Stylesheet' ),
    'theme.json'        => __( 'Theme Styles & Block Settings' ),
    'front-page.php'    => __( 'Homepage' ),
    // ... nhiều templates khác ...
);
```

---

## 13. Custom Header và Custom Background

### Custom Header

```php
// Trong functions.php - đăng ký support
add_theme_support( 'custom-header', array(
    'default-image'      => get_template_directory_uri() . '/images/header.jpg',
    'width'              => 1920,
    'height'             => 400,
    'flex-width'         => true,
    'flex-height'        => true,
    'uploads'            => true,
    'random-default'     => false,
    'header-text'        => true,
    'default-text-color' => '000000',
    'wp-head-callback'   => 'my_header_style',
) );

// Sử dụng trong template
$header_image = get_header_image();
if ( $header_image ) {
    echo '<img src="' . esc_url( $header_image ) . '" alt="Header">';
}
```

### Custom Background

```php
// Trong functions.php
add_theme_support( 'custom-background', array(
    'default-color' => 'ffffff',
    'default-image' => '',
) );

// WordPress tự thêm CSS vào <head> qua _custom_background_cb()
// Hoặc custom callback:
add_theme_support( 'custom-background', array(
    'wp-head-callback' => 'my_custom_background_cb',
) );
```

---

## 14. DB: Giao Diện Lưu Gì?

### Theme Settings trong wp_options

| Option Name | Mô tả | Giá trị |
|-------------|--------|---------|
| `template` | Theme template (parent) | `'theme-slug'` |
| `stylesheet` | Theme stylesheet (active) | `'child-theme-slug'` |
| `current_theme` | Tên theme hiện tại | `'Theme Name'` |
| `theme_mods_{theme}` | Customizer settings | serialized array |
| `sidebars_widgets` | Widget → sidebar mapping | serialized array |
| `widget_{type}` | Widget settings | serialized array |
| `auto_update_themes` | Auto-update list | serialized array |
| `custom_css_post_id` | Post ID của Additional CSS | integer |

### theme_mods_{theme} chi tiết

```php
get_option( 'theme_mods_my-theme' );
/*
Array (
    // Menu locations
    'nav_menu_locations' => array(
        'primary' => 5,
        'footer'  => 8,
    ),

    // Custom logo
    'custom_logo' => 123,    // attachment ID

    // Header image
    'header_image'      => 'https://example.com/wp-content/uploads/header.jpg',
    'header_image_data' => (object) array(
        'attachment_id' => 456,
        'url'           => '...',
        'width'         => 1920,
        'height'        => 400,
    ),

    // Custom background
    'background_color'      => 'ffffff',
    'background_image'      => '',
    'background_position_x' => 'center',
    'background_position_y' => 'center',
    'background_size'       => 'auto',
    'background_repeat'     => 'repeat',
    'background_attachment' => 'scroll',

    // Header text color
    'header_textcolor' => '000000',

    // Custom settings từ theme
    'primary_color'     => '#0073aa',
    'sidebar_position'  => 'right',
    'show_breadcrumb'   => true,
    'footer_text'       => 'Copyright 2024',

    // Custom CSS
    'custom_css_post_id' => 789,
)
*/
```

### Navigation Menu trong DB

```php
// 1. wp_terms: Tên menu
// term_id=5, name='Menu Chính', slug='menu-chinh'

// 2. wp_term_taxonomy: taxonomy = 'nav_menu'
// term_taxonomy_id=5, term_id=5, taxonomy='nav_menu'

// 3. wp_posts: Menu items (post_type = 'nav_menu_item')
// ID=100, post_type='nav_menu_item', post_status='publish', menu_order=1

// 4. wp_postmeta: Chi tiết menu item
// post_id=100:
//   _menu_item_type          => 'post_type'
//   _menu_item_menu_item_parent => '0'        (root item)
//   _menu_item_object_id     => '2'            (page ID)
//   _menu_item_object        => 'page'
//   _menu_item_target        => ''             (blank = same window)
//   _menu_item_classes        => 'a:1:{i:0;s:0:"";}'
//   _menu_item_xfn           => ''
//   _menu_item_url           => ''             (auto cho post_type/taxonomy)

// 5. wp_term_relationships: Liên kết menu item với menu
// object_id=100, term_taxonomy_id=5
// → Menu item 100 thuộc menu 5

// 6. wp_options: theme_mods_{theme} chứa nav_menu_locations
// nav_menu_locations => array( 'primary' => 5, 'footer' => 8 )
```

### Widget trong DB

```php
// wp_options: sidebars_widgets
// Key: sidebar ID → array widget instance IDs
array(
    'main-sidebar' => array( 'search-2', 'recent-posts-2', 'categories-2' ),
    'footer-1'     => array( 'text-2' ),
    'wp_inactive_widgets' => array( 'archives-2' ),
)

// wp_options: widget_{type}
// Ví dụ: widget_text
array(
    2 => array(
        'title'  => 'About Us',
        'text'   => '<p>Welcome to our site!</p>',
        'filter' => true,
    ),
    '_multiwidget' => 1,
)
```

---

## 15. Hooks Giao Diện - Danh Sách Đầy Đủ

### Theme Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `switch_theme` | Sau khi đổi theme | `$new_name`, `$new_theme`, `$old_theme` |
| `after_switch_theme` | Sau switch (có old theme) | `$old_name`, `$old_theme` |
| `after_setup_theme` | Sau load theme functions.php | (không có) |
| `load-themes.php` | Khi load trang themes.php | (không có) |

### Customizer Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `customize_register` | Đăng ký settings/controls | `$wp_customize` |
| `customize_save` | Trước lưu customizer | `$wp_customize` |
| `customize_save_after` | Sau lưu customizer | `$wp_customize` |
| `customize_preview_init` | Init customizer preview | `$wp_customize` |
| `customize_controls_enqueue_scripts` | Enqueue scripts cho controls | (không có) |
| `customize_preview_js` | JS trong preview frame | (không có) |

### Widget Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `widgets_init` | Đăng ký widgets và sidebars | (không có) |
| `sidebar_admin_setup` | Setup widget admin page | (không có) |
| `sidebar_admin_page` | Render widget admin page | (không có) |
| `dynamic_sidebar_before` | Trước render sidebar | `$sidebar_id`, `$has_widgets` |
| `dynamic_sidebar_after` | Sau render sidebar | `$sidebar_id`, `$has_widgets` |
| `dynamic_sidebar` | Khi render mỗi widget | `$widget` |

### Menu Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `wp_create_nav_menu` | Sau tạo menu | `$menu_id`, `$menu_data` |
| `wp_update_nav_menu` | Sau update menu | `$menu_id`, `$menu_data` |
| `wp_delete_nav_menu` | Sau xóa menu | `$menu_id` |
| `wp_update_nav_menu_item` | Sau update menu item | `$menu_id`, `$menu_item_db_id`, `$args` |
| `wp_nav_menu_objects` | Filter menu items trước render | `$sorted_menu_items`, `$args` |
| `wp_nav_menu` | Filter final menu HTML | `$nav_menu`, `$args` |
| `wp_nav_menu_items` | Filter menu items HTML | `$items`, `$args` |

### Filter Hooks quan trọng

```php
// 1. Filter menu items - thêm class active cho ancestors
add_filter( 'wp_nav_menu_objects', function( $items, $args ) {
    foreach ( $items as $item ) {
        // Thêm class cho menu items
        if ( $item->current ) {
            $item->classes[] = 'active';
        }
    }
    return $items;
}, 10, 2 );

// 2. Thêm item vào cuối menu
add_filter( 'wp_nav_menu_items', function( $items, $args ) {
    if ( 'primary' === $args->theme_location ) {
        if ( is_user_logged_in() ) {
            $items .= '<li class="menu-item"><a href="' . wp_logout_url() . '">Đăng Xuất</a></li>';
        } else {
            $items .= '<li class="menu-item"><a href="' . wp_login_url() . '">Đăng Nhập</a></li>';
        }
    }
    return $items;
}, 10, 2 );

// 3. Thêm custom fields cho menu item
add_action( 'wp_nav_menu_item_custom_fields', function( $item_id, $item, $depth, $args ) {
    $icon = get_post_meta( $item_id, '_menu_item_icon', true );
    ?>
    <p class="description description-wide">
        <label for="menu-item-icon-<?php echo $item_id; ?>">
            <?php _e( 'Icon Class (FontAwesome):' ); ?>
            <input type="text" id="menu-item-icon-<?php echo $item_id; ?>"
                   name="menu-item-icon[<?php echo $item_id; ?>]"
                   value="<?php echo esc_attr( $icon ); ?>" class="widefat" />
        </label>
    </p>
    <?php
}, 10, 4 );

// Lưu custom field
add_action( 'wp_update_nav_menu_item', function( $menu_id, $menu_item_db_id, $args ) {
    if ( isset( $_POST['menu-item-icon'][ $menu_item_db_id ] ) ) {
        update_post_meta( $menu_item_db_id, '_menu_item_icon',
            sanitize_text_field( $_POST['menu-item-icon'][ $menu_item_db_id ] )
        );
    }
}, 10, 3 );
```

---

## 16. Full Site Editing (FSE) - Block Themes

Từ WordPress 5.9+, WordPress hỗ trợ Block Themes (Full Site Editing).

### Cấu trúc Block Theme

```
my-block-theme/
├── style.css                   # Theme header
├── theme.json                  # Global styles & settings
├── functions.php               # (tùy chọn)
├── templates/                  # Block templates
│   ├── index.html
│   ├── single.html
│   ├── page.html
│   ├── archive.html
│   ├── 404.html
│   └── search.html
├── parts/                      # Template parts
│   ├── header.html
│   ├── footer.html
│   └── sidebar.html
└── patterns/                   # Block patterns
    ├── hero.php
    └── featured-posts.php
```

### theme.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "settings": {
        "color": {
            "palette": [
                { "slug": "primary", "color": "#0073aa", "name": "Primary" },
                { "slug": "secondary", "color": "#23282d", "name": "Secondary" }
            ],
            "custom": true,
            "link": true
        },
        "typography": {
            "fontFamilies": [
                {
                    "fontFamily": "Inter, sans-serif",
                    "slug": "inter",
                    "name": "Inter"
                }
            ],
            "fontSizes": [
                { "slug": "small", "size": "14px", "name": "Small" },
                { "slug": "medium", "size": "18px", "name": "Medium" },
                { "slug": "large", "size": "24px", "name": "Large" }
            ]
        },
        "layout": {
            "contentSize": "800px",
            "wideSize": "1200px"
        }
    },
    "styles": {
        "color": {
            "background": "#ffffff",
            "text": "#333333"
        },
        "typography": {
            "fontFamily": "var(--wp--preset--font-family--inter)",
            "fontSize": "var(--wp--preset--font-size--medium)"
        },
        "elements": {
            "link": {
                "color": { "text": "var(--wp--preset--color--primary)" }
            }
        }
    }
}
```

### Site Editor

Khi dùng Block Theme, menu "Appearance" thay đổi:
- Themes → vẫn giữ
- **Editor** (thay Customize) → `/wp-admin/site-editor.php`
- Widgets → ẩn (dùng template parts thay thế)
- Menus → ẩn (dùng Navigation block)

---

## 17. Ví Dụ Thực Tế: Theme Development

### Theme functions.php hoàn chỉnh

```php
<?php
/**
 * Theme Functions
 */

// ============================================
// Theme Setup
// ============================================
add_action( 'after_setup_theme', function() {
    // Translation
    load_theme_textdomain( 'my-theme', get_template_directory() . '/languages' );

    // Theme supports
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ) );
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-width'  => true,
        'flex-height' => true,
    ) );
    add_theme_support( 'custom-background', array(
        'default-color' => 'ffffff',
    ) );

    // Image sizes
    add_image_size( 'blog-card', 600, 400, true );
    add_image_size( 'hero', 1920, 600, true );

    // Menu locations
    register_nav_menus( array(
        'primary' => __( 'Menu Chinh', 'my-theme' ),
        'footer'  => __( 'Menu Footer', 'my-theme' ),
    ) );
});

// ============================================
// Widgets
// ============================================
add_action( 'widgets_init', function() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'my-theme' ),
        'id'            => 'sidebar-1',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer', 'my-theme' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
});

// ============================================
// Customizer
// ============================================
add_action( 'customize_register', function( $wp_customize ) {
    // Section
    $wp_customize->add_section( 'theme_options', array(
        'title'    => __( 'Tuy Chinh Theme', 'my-theme' ),
        'priority' => 30,
    ) );

    // Primary Color
    $wp_customize->add_setting( 'primary_color', array(
        'default'           => '#0073aa',
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize, 'primary_color', array(
            'label'   => __( 'Mau Chinh', 'my-theme' ),
            'section' => 'theme_options',
        )
    ) );

    // Footer Text
    $wp_customize->add_setting( 'footer_text', array(
        'default'           => 'Copyright ' . date( 'Y' ),
        'sanitize_callback' => 'wp_kses_post',
    ) );
    $wp_customize->add_control( 'footer_text', array(
        'label'   => __( 'Footer Text', 'my-theme' ),
        'section' => 'theme_options',
        'type'    => 'textarea',
    ) );
});

// ============================================
// Enqueue Assets
// ============================================
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'my-theme-style', get_stylesheet_uri(), array(), '1.0.0' );
    wp_enqueue_script( 'my-theme-script', get_template_directory_uri() . '/js/main.js',
        array(), '1.0.0', true );

    // Inline CSS từ customizer
    $primary_color = get_theme_mod( 'primary_color', '#0073aa' );
    $custom_css = ":root { --primary-color: {$primary_color}; }";
    wp_add_inline_style( 'my-theme-style', $custom_css );
});
```

---

## 18. So Sánh Với Laravel

| Tính năng | WordPress | Laravel |
|-----------|-----------|---------|
| Theme system | Built-in, switch dễ dàng | Blade templates, không switch runtime |
| Theme settings | Customizer API | Config files, `.env` |
| Widget system | Built-in, drag & drop | Không có built-in, dùng Livewire/Components |
| Navigation menus | Built-in, admin UI | Tự build hoặc package |
| Theme marketplace | WordPress.org | Không có |
| Live preview | Customizer preview | Không có built-in |
| CSS customization | Additional CSS, theme_mods | CSS files, Tailwind, v.v. |
| Template hierarchy | Tự động theo WordPress rules | Manual route → view mapping |
| Layout system | Widgets + Sidebars | Blade layouts + components |
| Full Site Editing | Block Editor (Gutenberg) | Không có tương đương |

### Tương đương trong Laravel

```php
// WordPress: register_nav_menus() + wp_nav_menu()
// Laravel: Tự tạo Menu system

// Model
class Menu extends Model {
    public function items() {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }
}

class MenuItem extends Model {
    public function children() {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }
    public function menu() {
        return $this->belongsTo(Menu::class);
    }
}

// Blade Component
// <x-navigation location="primary" />
```

---

## 19. Tổng Kết

### Các điểm quan trọng

1. **Theme = Presentation layer**: Theme chỉ lo giao diện, plugin lo logic. Không đặt business logic trong theme.

2. **Customizer**: Hệ thống Settings → Controls mạnh mẽ, live preview. Dùng `customize_register` hook.

3. **Widgets**: Drag & drop UI, mỗi widget là class kế thừa `WP_Widget`. Từ WP 5.8 hỗ trợ Block Editor.

4. **Navigation Menus**: Menu là taxonomy `nav_menu`, items là post type `nav_menu_item`. Metadata lưu trong `wp_postmeta`.

5. **theme_mods**: Tất cả customizer settings (type `theme_mod`) lưu trong `wp_options` key `theme_mods_{theme}`.

6. **Changeset**: Customizer changes lưu dạng draft trước khi publish. Hỗ trợ scheduled publish.

7. **Block Themes (FSE)**: Xu hướng mới, dùng `theme.json` + HTML templates + block patterns.

8. **DISALLOW_FILE_EDIT**: Luôn bật trong production để tắt Theme/Plugin Editor.

9. **Hooks quan trọng nhất**:
   - `after_setup_theme` - Setup theme
   - `customize_register` - Đăng ký customizer
   - `widgets_init` - Đăng ký widgets/sidebars
   - `switch_theme` - Khi đổi theme
   - `wp_nav_menu_objects` - Filter menu items

---

> **Tiep theo**: [07 - Quan Ly Plugin](./07-quan-ly-plugin.md)
