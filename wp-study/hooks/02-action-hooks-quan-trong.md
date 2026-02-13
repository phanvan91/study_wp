# Action Hooks Quan Trọng Trong WordPress

## Mục Lục

1. [Giới thiệu](#1-giới-thiệu)
2. [Init Hooks - Khởi tạo](#2-init-hooks---khởi-tạo)
3. [Admin Hooks](#3-admin-hooks)
4. [Frontend Hooks](#4-frontend-hooks)
5. [Login Hooks](#5-login-hooks)
6. [Post Hooks](#6-post-hooks)
7. [Comment Hooks](#7-comment-hooks)
8. [User Hooks](#8-user-hooks)
9. [AJAX Hooks](#9-ajax-hooks)
10. [REST API Hooks](#10-rest-api-hooks)
11. [Cron Hooks](#11-cron-hooks)
12. [Best Practices](#12-best-practices)

---

## 1. Giới thiệu

WordPress có hàng trăm action hooks, nhưng bạn thực sự chỉ cần nắm khoảng 30-40 hooks quan trọng nhất. File này liệt kê chi tiết từng hook, khi nào chạy, tham số nhận được, và code ví dụ thực tế.

### Quy ước trong file này

```
Hook Name        : Tên hook
Khi nào chạy     : Thời điểm WordPress kích hoạt hook
Tham số          : Các tham số truyền cho callback
Dùng để          : Mục đích sử dụng phổ biến
```

---

## 2. Init Hooks - Khởi tạo

Các hooks này chạy theo thứ tự khi WordPress khởi tạo. Đây là nền tảng quan trọng nhất.

### 2.1 muplugins_loaded

```
Khi nào chạy : Sau khi mu-plugins (must-use plugins) đã load
Tham số      : Không có
Dùng để      : Code cần chạy sớm nhất có thể
```

```php
<?php
// File: wp-content/mu-plugins/my-mu-plugin.php
// Must-use plugins tự động load, không cần activate

add_action( 'muplugins_loaded', 'my_mu_early_init' );
function my_mu_early_init() {
    // Chạy rất sớm - trước cả plugins thông thường
    // Thường dùng cho: security checks, custom constants

    // Ví dụ: Block IP addresses sớm nhất có thể
    $blocked_ips = array( '192.168.1.100', '10.0.0.50' );
    $visitor_ip  = $_SERVER['REMOTE_ADDR'] ?? '';

    if ( in_array( $visitor_ip, $blocked_ips, true ) ) {
        wp_die( 'Truy cập bị từ chối.', 'Blocked', array( 'response' => 403 ) );
    }
}
```

### 2.2 plugins_loaded

```
Khi nào chạy : Sau khi TẤT CẢ plugins đã load xong
Tham số      : Không có
Dùng để      : Tương tác giữa plugins, load text domain, khởi tạo plugin
```

```php
<?php
/**
 * Plugin Name: My Integration Plugin
 * Description: Tích hợp với WooCommerce
 */

add_action( 'plugins_loaded', 'my_plugin_init' );
function my_plugin_init() {
    // 1. Load ngôn ngữ (i18n)
    load_plugin_textdomain(
        'my-plugin',                                    // Text domain
        false,                                          // Deprecated parameter
        dirname( plugin_basename( __FILE__ ) ) . '/languages'  // Thư mục chứa file .mo
    );

    // 2. Kiểm tra plugin khác có active không trước khi tương tác
    if ( class_exists( 'WooCommerce' ) ) {
        // WooCommerce đang active → có thể dùng WC functions
        add_action( 'woocommerce_thankyou', 'my_custom_thankyou_message' );
    }

    // 3. Kiểm tra version PHP
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo 'Plugin My Integration yêu cầu PHP 7.4 trở lên.';
            echo '</p></div>';
        });
        return; // Dừng init plugin
    }
}

function my_custom_thankyou_message( $order_id ) {
    $order = wc_get_order( $order_id );
    echo '<p>Cảm ơn ' . esc_html( $order->get_billing_first_name() ) . '! ';
    echo 'Đơn hàng #' . $order_id . ' đã được tiếp nhận.</p>';
}
```

### 2.3 after_setup_theme

```
Khi nào chạy : Sau khi theme đã load (functions.php đã chạy)
Tham số      : Không có
Dùng để      : Đăng ký theme features, image sizes, nav menus
```

```php
<?php
// File: wp-content/themes/mytheme/functions.php

add_action( 'after_setup_theme', 'mytheme_setup' );
function mytheme_setup() {
    // 1. Đăng ký Navigation Menus
    register_nav_menus( array(
        'primary'   => 'Menu chính',        // Menu chính trên header
        'footer'    => 'Menu footer',        // Menu ở footer
        'social'    => 'Menu mạng xã hội',  // Menu icon social
    ));

    // 2. Đăng ký Theme Supports
    add_theme_support( 'title-tag' );              // WordPress quản lý <title>
    add_theme_support( 'post-thumbnails' );         // Cho phép featured image
    add_theme_support( 'html5', array(              // HTML5 markup
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support( 'custom-logo', array(        // Custom logo
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support( 'automatic-feed-links' );    // RSS feed links
    add_theme_support( 'responsive-embeds' );        // Responsive embeds

    // 3. Đăng ký Custom Image Sizes
    add_image_size( 'blog-thumbnail', 600, 400, true );   // Crop chính xác 600x400
    add_image_size( 'hero-banner', 1920, 600, true );     // Banner hero
    add_image_size( 'product-card', 300, 300, true );     // Card sản phẩm

    // 4. Content width (cho oEmbed)
    if ( ! isset( $content_width ) ) {
        $content_width = 1200;
    }

    // 5. Load text domain cho theme
    load_theme_textdomain( 'mytheme', get_template_directory() . '/languages' );
}
```

### 2.4 init

```
Khi nào chạy : Sau khi WordPress đã khởi tạo xong (sau plugins_loaded, after_setup_theme)
Tham số      : Không có
Dùng để      : Đăng ký Custom Post Types, Taxonomies, Shortcodes, Rewrite rules
```

```php
<?php
// File: wp-content/plugins/my-portfolio/my-portfolio.php

/**
 * Plugin Name: My Portfolio
 * Description: Quản lý Portfolio/Dự án
 */

add_action( 'init', 'my_portfolio_register_post_types' );
function my_portfolio_register_post_types() {

    // Đăng ký Custom Post Type: Portfolio
    register_post_type( 'portfolio', array(
        'labels' => array(
            'name'               => 'Portfolio',
            'singular_name'      => 'Dự án',
            'add_new'            => 'Thêm dự án',
            'add_new_item'       => 'Thêm dự án mới',
            'edit_item'          => 'Sửa dự án',
            'new_item'           => 'Dự án mới',
            'view_item'          => 'Xem dự án',
            'search_items'       => 'Tìm dự án',
            'not_found'          => 'Không tìm thấy dự án nào',
            'not_found_in_trash' => 'Không có dự án nào trong thùng rác',
        ),
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,               // Bật Gutenberg editor
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'menu_icon'    => 'dashicons-portfolio',
        'rewrite'      => array( 'slug' => 'du-an' ),  // URL: example.com/du-an/ten-du-an
    ));

    // Đăng ký Custom Taxonomy: Loại dự án
    register_taxonomy( 'project_type', 'portfolio', array(
        'labels' => array(
            'name'          => 'Loại dự án',
            'singular_name' => 'Loại dự án',
            'add_new_item'  => 'Thêm loại dự án',
            'search_items'  => 'Tìm loại dự án',
        ),
        'hierarchical'  => true,    // Giống Category (có parent/child)
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'loai-du-an' ),
    ));

    // Đăng ký Shortcode
    add_shortcode( 'portfolio_grid', 'my_portfolio_grid_shortcode' );
}

function my_portfolio_grid_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'count'   => 6,           // Số dự án hiển thị
        'columns' => 3,           // Số cột
        'type'    => '',          // Lọc theo loại dự án
    ), $atts, 'portfolio_grid' );

    $args = array(
        'post_type'      => 'portfolio',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Lọc theo taxonomy nếu có
    if ( ! empty( $atts['type'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'project_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['type'] ),
            ),
        );
    }

    $query = new WP_Query( $args );

    ob_start();
    if ( $query->have_posts() ) {
        echo '<div class="portfolio-grid" style="display:grid; grid-template-columns:repeat(' . intval( $atts['columns'] ) . ', 1fr); gap:20px;">';
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<div class="portfolio-item">';
            if ( has_post_thumbnail() ) {
                the_post_thumbnail( 'product-card' );
            }
            echo '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
            echo '<p>' . esc_html( get_the_excerpt() ) . '</p>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata(); // QUAN TRỌNG: Reset query sau custom WP_Query
    } else {
        echo '<p>Chưa có dự án nào.</p>';
    }

    return ob_get_clean();
}
```

### 2.5 wp_loaded

```
Khi nào chạy : Sau khi WordPress, plugins, và theme đã load HOÀN TOÀN
Tham số      : Không có
Dùng để      : Code cần chạy sau khi mọi thứ đã sẵn sàng, flush rewrite rules
```

```php
<?php
add_action( 'wp_loaded', 'my_after_everything_loaded' );
function my_after_everything_loaded() {
    // Tất cả plugins, themes đã load xong
    // Tất cả post types, taxonomies đã đăng ký
    // Nhưng query chưa chạy, template chưa load

    // Ví dụ: Kiểm tra và tạo trang mặc định nếu chưa có
    $shop_page = get_page_by_path( 'cua-hang' );
    if ( ! $shop_page ) {
        wp_insert_post( array(
            'post_title'   => 'Cửa hàng',
            'post_name'    => 'cua-hang',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '<!-- wp:shortcode -->[products]<!-- /wp:shortcode -->',
        ));
    }
}
```

---

## 3. Admin Hooks

### 3.1 admin_init

```
Khi nào chạy : Đầu tiên khi load bất kỳ trang admin nào
Tham số      : Không có
Dùng để      : Đăng ký settings, redirect, kiểm tra quyền
```

```php
<?php
add_action( 'admin_init', 'my_admin_init' );
function my_admin_init() {
    // 1. Đăng ký Settings (Settings API)
    // Tạo section trong Settings > General
    register_setting(
        'general',                           // Option group
        'my_company_phone',                  // Option name (lưu trong wp_options)
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );

    add_settings_section(
        'my_company_info',                   // Section ID
        'Thông tin công ty',                 // Section title
        function() {
            echo '<p>Nhập thông tin liên hệ công ty.</p>';
        },
        'general'                            // Page slug (general = Settings > General)
    );

    add_settings_field(
        'my_company_phone_field',            // Field ID
        'Số điện thoại',                     // Field label
        function() {
            $value = get_option( 'my_company_phone', '' );
            echo '<input type="text" name="my_company_phone" value="' . esc_attr( $value ) . '" class="regular-text">';
            echo '<p class="description">Nhập số điện thoại công ty.</p>';
        },
        'general',                           // Page slug
        'my_company_info'                    // Section ID
    );

    // 2. Redirect users không có quyền truy cập admin
    if ( ! current_user_can( 'edit_posts' ) && ! wp_doing_ajax() ) {
        wp_redirect( home_url() );
        exit;
    }
}
```

### 3.2 admin_menu

```
Khi nào chạy : Khi WordPress xây dựng admin menu
Tham số      : Không có
Dùng để      : Thêm menu pages, submenu pages trong admin
```

```php
<?php
add_action( 'admin_menu', 'my_plugin_admin_menus' );
function my_plugin_admin_menus() {

    // Menu cấp 1: Quản lý Plugin
    add_menu_page(
        'My Plugin Settings',     // Page title (hiện trên <title>)
        'My Plugin',              // Menu title (hiện trong sidebar)
        'manage_options',         // Capability required
        'my-plugin',              // Menu slug
        'my_plugin_dashboard',    // Callback function
        'dashicons-admin-generic', // Icon (xem: developer.wordpress.org/resource/dashicons/)
        25                        // Position (25 = sau Comments)
    );

    // Submenu 1: Dashboard (thay thế menu cha mặc định)
    add_submenu_page(
        'my-plugin',              // Parent menu slug
        'Dashboard',              // Page title
        'Dashboard',              // Menu title
        'manage_options',         // Capability
        'my-plugin',              // Menu slug (giống parent để thay thế)
        'my_plugin_dashboard'     // Callback
    );

    // Submenu 2: Cài đặt
    add_submenu_page(
        'my-plugin',
        'Cài đặt - My Plugin',
        'Cài đặt',
        'manage_options',
        'my-plugin-settings',
        'my_plugin_settings_page'
    );

    // Submenu 3: Báo cáo
    add_submenu_page(
        'my-plugin',
        'Báo cáo - My Plugin',
        'Báo cáo',
        'manage_options',
        'my-plugin-reports',
        'my_plugin_reports_page'
    );
}

function my_plugin_dashboard() {
    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bạn không có quyền truy cập trang này.' );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <div class="card" style="max-width:600px; padding:20px;">
            <h2>Chào mừng đến Dashboard</h2>
            <p>Plugin đang hoạt động bình thường.</p>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td>Phiên bản</td>
                        <td><strong>1.0.0</strong></td>
                    </tr>
                    <tr>
                        <td>PHP Version</td>
                        <td><strong><?php echo PHP_VERSION; ?></strong></td>
                    </tr>
                    <tr>
                        <td>WordPress Version</td>
                        <td><strong><?php echo get_bloginfo( 'version' ); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function my_plugin_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bạn không có quyền truy cập trang này.' );
    }
    ?>
    <div class="wrap">
        <h1>Cài đặt My Plugin</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'my_plugin_settings' );       // Nonce + hidden fields
            do_settings_sections( 'my-plugin-settings' );   // Render sections & fields
            submit_button( 'Lưu cài đặt' );
            ?>
        </form>
    </div>
    <?php
}

function my_plugin_reports_page() {
    echo '<div class="wrap"><h1>Báo cáo</h1><p>Trang báo cáo đang phát triển.</p></div>';
}
```

### 3.3 admin_enqueue_scripts

```
Khi nào chạy : Khi cần load CSS/JS cho trang admin
Tham số      : $hook_suffix (string) - suffix của trang admin hiện tại
Dùng để      : Load CSS, JavaScript cho admin pages
```

```php
<?php
add_action( 'admin_enqueue_scripts', 'my_admin_assets', 10, 1 );
function my_admin_assets( $hook_suffix ) {
    // $hook_suffix chứa tên trang admin hiện tại
    // Ví dụ: 'post.php', 'edit.php', 'toplevel_page_my-plugin', etc.

    // QUAN TRỌNG: Chỉ load CSS/JS ở trang cần thiết, KHÔNG load ở tất cả trang admin
    // Điều này giúp tăng performance

    // Load cho TẤT CẢ trang admin (hạn chế dùng)
    wp_enqueue_style(
        'my-plugin-admin-global',                          // Handle
        plugin_dir_url( __FILE__ ) . 'css/admin-global.css', // URL
        array(),                                            // Dependencies
        '1.0.0'                                             // Version
    );

    // Chỉ load ở trang plugin của mình
    if ( 'toplevel_page_my-plugin' !== $hook_suffix ) {
        return; // Không phải trang của mình → thoát
    }

    // CSS cho trang plugin
    wp_enqueue_style(
        'my-plugin-admin',
        plugin_dir_url( __FILE__ ) . 'css/admin.css',
        array(),
        '1.0.0'
    );

    // JavaScript cho trang plugin
    wp_enqueue_script(
        'my-plugin-admin-js',
        plugin_dir_url( __FILE__ ) . 'js/admin.js',
        array( 'jquery', 'wp-util' ),  // Dependencies: jQuery và wp.ajax utility
        '1.0.0',
        true                           // Load ở footer
    );

    // Truyền dữ liệu PHP sang JavaScript
    wp_localize_script( 'my-plugin-admin-js', 'MyPluginData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'my_plugin_nonce' ),
        'strings'  => array(
            'confirm_delete' => 'Bạn có chắc muốn xóa?',
            'saving'         => 'Đang lưu...',
            'saved'          => 'Đã lưu thành công!',
            'error'          => 'Có lỗi xảy ra, vui lòng thử lại.',
        ),
    ));
}
```

### 3.4 admin_notices

```
Khi nào chạy : Khi render phần thông báo trên trang admin
Tham số      : Không có
Dùng để      : Hiển thị thông báo (success, error, warning, info)
```

```php
<?php
// Thông báo cố định
add_action( 'admin_notices', 'my_admin_notices' );
function my_admin_notices() {
    // Chỉ hiện cho admin
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Kiểm tra điều kiện
    $api_key = get_option( 'my_plugin_api_key', '' );
    if ( empty( $api_key ) ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>My Plugin:</strong> Bạn chưa cấu hình API Key.
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=my-plugin-settings' ) ); ?>">
                    Cấu hình ngay
                </a>
            </p>
        </div>
        <?php
    }

    // Thông báo sau khi thực hiện action (dựa vào URL parameter)
    if ( isset( $_GET['my_action_done'] ) ) {
        $count = intval( $_GET['my_action_done'] );
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Đã xử lý thành công <strong><?php echo $count; ?></strong> mục.</p>
        </div>
        <?php
    }
}

// Các class CSS cho notice:
// notice-success : Nền xanh lá (thành công)
// notice-error   : Nền đỏ (lỗi)
// notice-warning : Nền vàng (cảnh báo)
// notice-info    : Nền xanh dương (thông tin)
// is-dismissible : Có nút X để đóng
```

---

## 4. Frontend Hooks

### 4.1 wp_enqueue_scripts

```
Khi nào chạy : Khi cần load CSS/JS cho frontend (trang công khai)
Tham số      : Không có
Dùng để      : Load stylesheets, JavaScript cho frontend
```

```php
<?php
// File: functions.php

add_action( 'wp_enqueue_scripts', 'mytheme_enqueue_assets' );
function mytheme_enqueue_assets() {

    // === CSS ===

    // Google Fonts
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null   // null để không thêm version parameter (tối ưu caching)
    );

    // Theme stylesheet chính
    wp_enqueue_style(
        'mytheme-style',
        get_stylesheet_uri(),  // Trỏ đến style.css của theme
        array( 'google-fonts' ),   // Phụ thuộc Google Fonts (load sau)
        wp_get_theme()->get( 'Version' )  // Version từ theme header
    );

    // CSS riêng cho trang single post
    if ( is_single() ) {
        wp_enqueue_style(
            'mytheme-single',
            get_template_directory_uri() . '/css/single.css',
            array( 'mytheme-style' ),
            '1.0.0'
        );
    }

    // === JAVASCRIPT ===

    // Navigation script
    wp_enqueue_script(
        'mytheme-navigation',
        get_template_directory_uri() . '/js/navigation.js',
        array(),   // Không phụ thuộc gì
        '1.0.0',
        array(
            'in_footer' => true,    // Load ở footer (tốt cho performance)
            'strategy'  => 'defer', // WordPress 6.3+: defer loading
        )
    );

    // Main script (phụ thuộc jQuery)
    wp_enqueue_script(
        'mytheme-main',
        get_template_directory_uri() . '/js/main.js',
        array( 'jquery' ),
        '1.0.0',
        true   // true = load ở footer
    );

    // Truyền dữ liệu PHP sang JS
    wp_localize_script( 'mytheme-main', 'MyTheme', array(
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'mytheme_nonce' ),
        'home_url'  => home_url( '/' ),
        'is_single' => is_single(),
        'post_id'   => get_the_ID(),
    ));

    // Gỡ bỏ scripts không cần
    // jQuery Migrate (không cần nếu code đã tương thích jQuery 3.x)
    wp_deregister_script( 'jquery-migrate' );

    // Comment reply script (chỉ load khi cần)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
```

### 4.2 wp_head

```
Khi nào chạy : Trong thẻ <head>, khi gọi wp_head() trong template
Tham số      : Không có
Dùng để      : Thêm meta tags, inline CSS/JS, structured data
```

```php
<?php
// Thêm meta tags Open Graph cho SEO
add_action( 'wp_head', 'mytheme_open_graph_tags', 5 );
function mytheme_open_graph_tags() {
    // Chỉ thêm cho single posts/pages
    if ( ! is_singular() ) {
        return;
    }

    global $post;
    setup_postdata( $post );

    $title       = get_the_title();
    $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 30 );
    $url         = get_permalink();
    $image       = get_the_post_thumbnail_url( $post->ID, 'large' );
    $site_name   = get_bloginfo( 'name' );

    ?>
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
    <meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
    <meta property="og:url" content="<?php echo esc_url( $url ); ?>">
    <meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>">
    <meta property="og:type" content="article">
    <?php if ( $image ) : ?>
    <meta property="og:image" content="<?php echo esc_url( $image ); ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
    <?php if ( $image ) : ?>
    <meta name="twitter:image" content="<?php echo esc_url( $image ); ?>">
    <?php endif; ?>
    <?php

    wp_reset_postdata();
}

// Thêm Schema.org JSON-LD Structured Data
add_action( 'wp_head', 'mytheme_schema_data', 10 );
function mytheme_schema_data() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }

    $schema = array(
        '@context'      => 'https://schema.org',
        '@type'         => 'Article',
        'headline'      => get_the_title(),
        'datePublished' => get_the_date( 'c' ),
        'dateModified'  => get_the_modified_date( 'c' ),
        'author'        => array(
            '@type' => 'Person',
            'name'  => get_the_author_meta( 'display_name' ),
        ),
    );

    $thumbnail = get_the_post_thumbnail_url( get_the_ID(), 'full' );
    if ( $thumbnail ) {
        $schema['image'] = $thumbnail;
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
```

### 4.3 wp_footer

```
Khi nào chạy : Trước thẻ đóng </body>, khi gọi wp_footer() trong template
Tham số      : Không có
Dùng để      : Inline scripts, tracking code, back-to-top button
```

```php
<?php
// Back to top button
add_action( 'wp_footer', 'mytheme_back_to_top' );
function mytheme_back_to_top() {
    ?>
    <button id="back-to-top"
            style="display:none; position:fixed; bottom:30px; right:30px; z-index:999;
                   width:50px; height:50px; border-radius:50%; border:none;
                   background:#0073aa; color:#fff; cursor:pointer; font-size:20px;"
            aria-label="Về đầu trang">
        &#8593;
    </button>
    <script>
    (function() {
        var btn = document.getElementById('back-to-top');
        window.addEventListener('scroll', function() {
            btn.style.display = window.scrollY > 300 ? 'block' : 'none';
        });
        btn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
    </script>
    <?php
}

// Cookie consent banner
add_action( 'wp_footer', 'mytheme_cookie_consent', 99 );
function mytheme_cookie_consent() {
    ?>
    <div id="cookie-consent"
         style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:9999;
                background:#333; color:#fff; padding:15px 30px; text-align:center;">
        <p style="margin:0 0 10px;">
            Website sử dụng cookies để cải thiện trải nghiệm của bạn.
        </p>
        <button onclick="acceptCookies()" style="background:#0073aa; color:#fff; border:none; padding:8px 20px; cursor:pointer; border-radius:3px;">
            Đồng ý
        </button>
    </div>
    <script>
    function acceptCookies() {
        document.cookie = 'cookie_consent=accepted; max-age=31536000; path=/';
        document.getElementById('cookie-consent').style.display = 'none';
    }
    if (!document.cookie.includes('cookie_consent=accepted')) {
        document.getElementById('cookie-consent').style.display = 'block';
    }
    </script>
    <?php
}
```

### 4.4 wp_body_open

```
Khi nào chạy : Ngay sau thẻ mở <body>, khi gọi wp_body_open() trong template
Tham số      : Không có
Dùng để      : Skip navigation links, Google Tag Manager noscript, preloaders
```

```php
<?php
// Skip navigation (accessibility)
add_action( 'wp_body_open', 'mytheme_skip_link' );
function mytheme_skip_link() {
    echo '<a class="skip-link screen-reader-text" href="#main-content">Bỏ qua đến nội dung chính</a>';
}

// Google Tag Manager (noscript fallback)
add_action( 'wp_body_open', 'mytheme_gtm_noscript' );
function mytheme_gtm_noscript() {
    $gtm_id = get_option( 'mytheme_gtm_id', '' );
    if ( empty( $gtm_id ) ) {
        return;
    }
    ?>
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <?php
}
```

---

## 5. Login Hooks

### 5.1 wp_login

```
Khi nào chạy : Ngay sau khi user đăng nhập thành công
Tham số      : $user_login (string), $user (WP_User)
Dùng để      : Ghi log, redirect, cập nhật last login
```

```php
<?php
// Ghi nhận lần đăng nhập cuối
add_action( 'wp_login', 'my_record_last_login', 10, 2 );
function my_record_last_login( $user_login, $user ) {
    // $user_login = tên đăng nhập (string)
    // $user = object WP_User đầy đủ thông tin

    // Lưu thời gian đăng nhập cuối vào user meta
    update_user_meta( $user->ID, '_last_login', current_time( 'mysql' ) );

    // Lưu IP đăng nhập
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    update_user_meta( $user->ID, '_last_login_ip', sanitize_text_field( $ip ) );

    // Ghi log
    error_log( sprintf(
        '[Login] User: %s (ID: %d) | IP: %s | Time: %s',
        $user_login,
        $user->ID,
        $ip,
        current_time( 'mysql' )
    ));
}
```

### 5.2 wp_logout

```
Khi nào chạy : Khi user đăng xuất
Tham số      : $user_id (int) - WordPress 6.0+
Dùng để      : Cleanup sessions, ghi log
```

```php
<?php
add_action( 'wp_logout', 'my_after_logout', 10, 1 );
function my_after_logout( $user_id ) {
    // Ghi log đăng xuất
    $user = get_userdata( $user_id );
    if ( $user ) {
        error_log( sprintf(
            '[Logout] User: %s (ID: %d) | Time: %s',
            $user->user_login,
            $user_id,
            current_time( 'mysql' )
        ));
    }

    // Xóa custom transients của user
    delete_transient( 'user_dashboard_data_' . $user_id );
}
```

### 5.3 login_enqueue_scripts

```
Khi nào chạy : Khi load trang đăng nhập (wp-login.php)
Tham số      : Không có
Dùng để      : Custom CSS/JS cho trang login
```

```php
<?php
add_action( 'login_enqueue_scripts', 'mytheme_custom_login_page' );
function mytheme_custom_login_page() {
    // Custom CSS cho trang login
    ?>
    <style>
        /* Background */
        body.login {
            background: #f0f0f1;
            background-image: url('<?php echo esc_url( get_template_directory_uri() . '/images/login-bg.jpg' ); ?>');
            background-size: cover;
            background-position: center;
        }

        /* Logo */
        .login h1 a {
            background-image: url('<?php echo esc_url( get_template_directory_uri() . '/images/logo.png' ); ?>');
            background-size: contain;
            width: 300px;
            height: 80px;
        }

        /* Form */
        .login form {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Button */
        .login .button-primary {
            background: #0073aa;
            border-color: #0073aa;
            border-radius: 5px;
            font-size: 16px;
            height: 45px;
        }
    </style>
    <?php
}
```

---

## 6. Post Hooks

### 6.1 save_post

```
Khi nào chạy : Khi một post/page/CPT được lưu (tạo mới HOẶC update)
Tham số      : $post_id (int), $post (WP_Post), $update (bool)
Dùng để      : Lưu custom meta fields, validate data, trigger side effects
```

```php
<?php
add_action( 'save_post', 'my_save_post_meta', 10, 3 );
function my_save_post_meta( $post_id, $post, $update ) {
    // === CÁC KIỂM TRA AN TOÀN (PHẢI CÓ!) ===

    // 1. Bỏ qua autosave (WordPress tự save mỗi 60 giây)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 2. Bỏ qua revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // 3. Chỉ xử lý post type cụ thể
    if ( 'product' !== $post->post_type ) {
        return;
    }

    // 4. Kiểm tra nonce (bảo mật - đảm bảo request từ form hợp lệ)
    if ( ! isset( $_POST['my_product_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['my_product_nonce'], 'my_save_product' ) ) {
        return;
    }

    // 5. Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // === LƯU DỮ LIỆU ===

    // Lưu giá sản phẩm
    if ( isset( $_POST['product_price'] ) ) {
        $price = floatval( $_POST['product_price'] );
        update_post_meta( $post_id, '_product_price', $price );
    }

    // Lưu SKU
    if ( isset( $_POST['product_sku'] ) ) {
        $sku = sanitize_text_field( $_POST['product_sku'] );
        update_post_meta( $post_id, '_product_sku', $sku );
    }

    // Lưu mô tả ngắn
    if ( isset( $_POST['product_short_desc'] ) ) {
        $desc = wp_kses_post( $_POST['product_short_desc'] ); // Cho phép HTML an toàn
        update_post_meta( $post_id, '_product_short_desc', $desc );
    }

    // Lưu checkbox (in stock)
    $in_stock = isset( $_POST['product_in_stock'] ) ? 1 : 0;
    update_post_meta( $post_id, '_product_in_stock', $in_stock );
}
```

### 6.2 save_post_{post_type}

```
Khi nào chạy : Giống save_post nhưng chỉ cho post type cụ thể
Tham số      : $post_id (int), $post (WP_Post), $update (bool)
Dùng để      : Xử lý riêng cho từng post type (không cần kiểm tra post type)
```

```php
<?php
// Chỉ trigger khi lưu post type 'portfolio'
// Không cần kiểm tra post type trong callback!
add_action( 'save_post_portfolio', 'my_save_portfolio', 10, 3 );
function my_save_portfolio( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Tự động set featured image từ URL nếu có
    if ( isset( $_POST['portfolio_external_image'] ) && ! has_post_thumbnail( $post_id ) ) {
        $image_url = esc_url_raw( $_POST['portfolio_external_image'] );
        if ( ! empty( $image_url ) ) {
            update_post_meta( $post_id, '_portfolio_image_url', $image_url );
        }
    }
}
```

### 6.3 transition_post_status

```
Khi nào chạy : Khi trạng thái bài viết thay đổi
Tham số      : $new_status (string), $old_status (string), $post (WP_Post)
Dùng để      : Xử lý khi publish, unpublish, trash
```

```php
<?php
add_action( 'transition_post_status', 'my_post_status_changed', 10, 3 );
function my_post_status_changed( $new_status, $old_status, $post ) {
    // Chỉ xử lý post type 'post'
    if ( 'post' !== $post->post_type ) {
        return;
    }

    // Bài viết vừa được publish lần đầu
    if ( 'publish' === $new_status && 'publish' !== $old_status ) {
        // Gửi notification đến subscribers
        my_notify_subscribers( $post );

        // Share lên social media
        my_share_to_social( $post );

        // Xóa cache
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
    }

    // Bài viết bị unpublish (chuyển sang draft/pending)
    if ( 'publish' === $old_status && 'publish' !== $new_status ) {
        error_log( sprintf(
            'Bài viết "%s" (ID: %d) đã bị chuyển từ publish sang %s',
            $post->post_title,
            $post->ID,
            $new_status
        ));
    }

    // Bài viết bị chuyển vào thùng rác
    if ( 'trash' === $new_status ) {
        error_log( sprintf(
            'Bài viết "%s" (ID: %d) đã bị xóa vào thùng rác bởi user ID: %d',
            $post->post_title,
            $post->ID,
            get_current_user_id()
        ));
    }
}

function my_notify_subscribers( $post ) {
    // Lấy danh sách email subscribers từ custom option
    $subscribers = get_option( 'my_subscribers', array() );
    if ( empty( $subscribers ) ) {
        return;
    }

    $subject = sprintf( 'Bài viết mới: %s', $post->post_title );
    $message = sprintf(
        "Xin chào!\n\nBài viết mới đã được đăng:\n\n%s\n\nĐọc ngay: %s",
        $post->post_title,
        get_permalink( $post->ID )
    );

    foreach ( $subscribers as $email ) {
        wp_mail( sanitize_email( $email ), $subject, $message );
    }
}

function my_share_to_social( $post ) {
    // Placeholder - tích hợp API social media
    error_log( 'Share to social: ' . $post->post_title );
}
```

### 6.4 delete_post và before_delete_post

```
Khi nào chạy : Khi bài viết bị XÓA VĨNH VIỄN (không phải trash)
Tham số      : $post_id (int), $post (WP_Post)
Dùng để      : Cleanup dữ liệu liên quan
```

```php
<?php
// before_delete_post: Chạy TRƯỚC khi xóa (dữ liệu post vẫn còn)
add_action( 'before_delete_post', 'my_before_delete_cleanup', 10, 2 );
function my_before_delete_cleanup( $post_id, $post ) {
    if ( 'product' !== $post->post_type ) {
        return;
    }

    // Xóa tất cả ảnh đã upload cho sản phẩm này
    $attachments = get_posts( array(
        'post_type'      => 'attachment',
        'posts_per_page' => -1,
        'post_parent'    => $post_id,
    ));

    foreach ( $attachments as $attachment ) {
        wp_delete_attachment( $attachment->ID, true ); // true = xóa file vật lý
    }

    // Xóa transient cache liên quan
    delete_transient( 'product_data_' . $post_id );
}
```

---

## 7. Comment Hooks

### 7.1 wp_insert_comment

```
Khi nào chạy : Khi comment mới được tạo
Tham số      : $comment_id (int), $comment (WP_Comment)
Dùng để      : Thông báo, moderate, anti-spam custom
```

```php
<?php
add_action( 'wp_insert_comment', 'my_new_comment_notification', 10, 2 );
function my_new_comment_notification( $comment_id, $comment ) {
    // Bỏ qua pingbacks và trackbacks
    if ( 'comment' !== $comment->comment_type && '' !== $comment->comment_type ) {
        return;
    }

    // Lấy thông tin bài viết
    $post = get_post( $comment->comment_post_ID );

    // Gửi thông báo cho tác giả bài viết
    $author_email = get_the_author_meta( 'user_email', $post->post_author );

    // Không gửi nếu tác giả tự comment
    if ( $author_email === $comment->comment_author_email ) {
        return;
    }

    $subject = sprintf( 'Comment mới trên bài "%s"', $post->post_title );
    $message = sprintf(
        "Xin chào,\n\n" .
        "%s đã comment trên bài viết \"%s\":\n\n" .
        "---\n%s\n---\n\n" .
        "Xem comment: %s\n" .
        "Quản lý comments: %s",
        $comment->comment_author,
        $post->post_title,
        $comment->comment_content,
        get_comment_link( $comment_id ),
        admin_url( 'edit-comments.php' )
    );

    wp_mail( $author_email, $subject, $message );
}
```

### 7.2 comment_post

```
Khi nào chạy : Ngay sau khi comment được insert vào database
Tham số      : $comment_id (int), $comment_approved (int|string), $commentdata (array)
Dùng để      : Tương tự wp_insert_comment nhưng có thêm trạng thái approved
```

```php
<?php
add_action( 'comment_post', 'my_auto_approve_registered_users', 10, 3 );
function my_auto_approve_registered_users( $comment_id, $comment_approved, $commentdata ) {
    // $comment_approved: 1 = approved, 0 = pending, 'spam' = spam

    // Tự động approve comment từ user đã đăng ký và có ít nhất 5 comment trước đó
    if ( 0 === $comment_approved && ! empty( $commentdata['user_id'] ) ) {
        $user_id       = intval( $commentdata['user_id'] );
        $comment_count = get_comments( array(
            'user_id' => $user_id,
            'status'  => 'approve',
            'count'   => true,
        ));

        if ( $comment_count >= 5 ) {
            wp_set_comment_status( $comment_id, 'approve' );
        }
    }
}
```

---

## 8. User Hooks

### 8.1 user_register

```
Khi nào chạy : Khi user mới được tạo
Tham số      : $user_id (int), $userdata (array) - WordPress 5.8+
Dùng để      : Welcome email, set default meta, assign role
```

```php
<?php
add_action( 'user_register', 'my_on_user_register', 10, 2 );
function my_on_user_register( $user_id, $userdata = array() ) {
    // Set default user meta
    update_user_meta( $user_id, '_registered_via', 'website' );
    update_user_meta( $user_id, '_registration_ip', $_SERVER['REMOTE_ADDR'] ?? '' );
    update_user_meta( $user_id, '_email_verified', false );

    // Tạo verification token
    $token = wp_generate_password( 32, false );
    update_user_meta( $user_id, '_email_verify_token', $token );

    // Gửi email xác nhận
    $user       = get_userdata( $user_id );
    $verify_url = add_query_arg( array(
        'action' => 'verify_email',
        'token'  => $token,
        'user'   => $user_id,
    ), home_url( '/verify/' ) );

    $subject = 'Xác nhận email đăng ký - ' . get_bloginfo( 'name' );
    $message = sprintf(
        "Xin chào %s,\n\n" .
        "Cảm ơn bạn đã đăng ký tài khoản tại %s.\n\n" .
        "Vui lòng click link sau để xác nhận email:\n%s\n\n" .
        "Link có hiệu lực trong 24 giờ.\n\nTrân trọng!",
        $user->display_name,
        get_bloginfo( 'name' ),
        $verify_url
    );

    wp_mail( $user->user_email, $subject, $message );
}
```

### 8.2 profile_update

```
Khi nào chạy : Khi profile user được cập nhật
Tham số      : $user_id (int), $old_user_data (WP_User), $userdata (array)
Dùng để      : Log thay đổi, sync dữ liệu, notifications
```

```php
<?php
add_action( 'profile_update', 'my_on_profile_update', 10, 3 );
function my_on_profile_update( $user_id, $old_user_data, $userdata = array() ) {
    $new_user = get_userdata( $user_id );

    // Ghi log khi email thay đổi
    if ( $old_user_data->user_email !== $new_user->user_email ) {
        error_log( sprintf(
            '[User Update] User #%d đổi email: %s → %s',
            $user_id,
            $old_user_data->user_email,
            $new_user->user_email
        ));

        // Yêu cầu xác nhận email mới
        update_user_meta( $user_id, '_email_verified', false );
    }

    // Ghi log khi role thay đổi
    $old_roles = $old_user_data->roles;
    $new_roles = $new_user->roles;
    if ( $old_roles !== $new_roles ) {
        error_log( sprintf(
            '[User Update] User #%d đổi role: %s → %s',
            $user_id,
            implode( ', ', $old_roles ),
            implode( ', ', $new_roles )
        ));
    }
}
```

### 8.3 delete_user

```
Khi nào chạy : Trước khi user bị xóa
Tham số      : $user_id (int), $reassign (int|null) - ID user nhận content
Dùng để      : Cleanup dữ liệu, backup, notifications
```

```php
<?php
add_action( 'delete_user', 'my_before_delete_user', 10, 2 );
function my_before_delete_user( $user_id, $reassign ) {
    $user = get_userdata( $user_id );

    // Log việc xóa user
    error_log( sprintf(
        '[User Delete] Xóa user: %s (ID: %d, Email: %s) | Reassign to: %s',
        $user->user_login,
        $user_id,
        $user->user_email,
        $reassign ? "User #{$reassign}" : 'None (delete content)'
    ));

    // Xóa tất cả custom user meta
    $custom_meta_keys = array(
        '_last_login',
        '_last_login_ip',
        '_email_verified',
        '_email_verify_token',
        '_registration_ip',
    );

    foreach ( $custom_meta_keys as $key ) {
        delete_user_meta( $user_id, $key );
    }

    // Xóa custom data liên quan
    global $wpdb;
    $wpdb->delete(
        $wpdb->prefix . 'my_user_settings',
        array( 'user_id' => $user_id ),
        array( '%d' )
    );
}
```

---

## 9. AJAX Hooks

### wp_ajax_{action} và wp_ajax_nopriv_{action}

```
Khi nào chạy : Khi nhận AJAX request qua admin-ajax.php
Tham số      : Không có (lấy data từ $_POST/$_GET)
wp_ajax_{action}        : Chỉ cho users đã đăng nhập
wp_ajax_nopriv_{action} : Chỉ cho users CHƯA đăng nhập
Cần cả hai nếu muốn tất cả users đều truy cập được
```

```php
<?php
/**
 * Plugin Name: My AJAX Demo
 * Description: Ví dụ AJAX trong WordPress
 */

// === PHÍA SERVER (PHP) ===

// Xử lý AJAX cho user đã đăng nhập
add_action( 'wp_ajax_my_load_more_posts', 'my_load_more_posts_handler' );
// Xử lý AJAX cho user chưa đăng nhập (cần cho frontend công khai)
add_action( 'wp_ajax_nopriv_my_load_more_posts', 'my_load_more_posts_handler' );

function my_load_more_posts_handler() {
    // 1. Verify nonce (bảo mật)
    if ( ! check_ajax_referer( 'my_ajax_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Yêu cầu không hợp lệ.' ), 403 );
    }

    // 2. Lấy và validate tham số
    $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
    $per_page = 6;

    // 3. Query
    $query = new WP_Query( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    ));

    // 4. Chuẩn bị response
    $posts_html = '';
    if ( $query->have_posts() ) {
        ob_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <article class="post-card">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail( 'blog-thumbnail' ); ?>
                    </div>
                <?php endif; ?>
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                <span class="post-date"><?php echo get_the_date( 'd/m/Y' ); ?></span>
            </article>
            <?php
        }
        $posts_html = ob_get_clean();
        wp_reset_postdata();
    }

    // 5. Trả về JSON response
    wp_send_json_success( array(
        'html'      => $posts_html,
        'has_more'  => $page < $query->max_num_pages,
        'total'     => $query->found_posts,
        'max_pages' => $query->max_num_pages,
    ));

    // wp_send_json_success() tự gọi wp_die(), không cần gọi thêm
}

// AJAX: Tìm kiếm sản phẩm (ví dụ search autocomplete)
add_action( 'wp_ajax_my_search_products', 'my_search_products_handler' );
add_action( 'wp_ajax_nopriv_my_search_products', 'my_search_products_handler' );

function my_search_products_handler() {
    check_ajax_referer( 'my_ajax_nonce', 'nonce' );

    $search_term = sanitize_text_field( $_GET['q'] ?? '' );

    if ( strlen( $search_term ) < 2 ) {
        wp_send_json_success( array( 'results' => array() ) );
    }

    $query = new WP_Query( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $search_term,
        'posts_per_page' => 10,
    ));

    $results = array();
    while ( $query->have_posts() ) {
        $query->the_post();
        $results[] = array(
            'id'        => get_the_ID(),
            'title'     => get_the_title(),
            'url'       => get_permalink(),
            'thumbnail' => get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ),
            'price'     => get_post_meta( get_the_ID(), '_product_price', true ),
        );
    }
    wp_reset_postdata();

    wp_send_json_success( array( 'results' => $results ) );
}

// === PHÍA CLIENT (JavaScript) ===
// Enqueue script
add_action( 'wp_enqueue_scripts', 'my_ajax_demo_scripts' );
function my_ajax_demo_scripts() {
    wp_enqueue_script(
        'my-ajax-demo',
        plugin_dir_url( __FILE__ ) . 'js/ajax-demo.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );

    wp_localize_script( 'my-ajax-demo', 'MyAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'my_ajax_nonce' ),
    ));
}
```

**File JavaScript (js/ajax-demo.js):**

```javascript
// js/ajax-demo.js
(function($) {
    'use strict';

    // Load More Posts
    var currentPage = 1;

    $('#load-more-btn').on('click', function() {
        var $btn = $(this);
        $btn.text('Đang tải...').prop('disabled', true);

        currentPage++;

        $.ajax({
            url: MyAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'my_load_more_posts',    // Khớp với tên hook: wp_ajax_my_load_more_posts
                nonce: MyAjax.nonce,
                page: currentPage
            },
            success: function(response) {
                if (response.success) {
                    $('#posts-container').append(response.data.html);

                    if (!response.data.has_more) {
                        $btn.hide(); // Ẩn nút nếu hết bài
                    } else {
                        $btn.text('Tải thêm').prop('disabled', false);
                    }
                }
            },
            error: function() {
                alert('Có lỗi xảy ra, vui lòng thử lại.');
                currentPage--;
                $btn.text('Tải thêm').prop('disabled', false);
            }
        });
    });

    // Search Autocomplete
    var searchTimer;
    $('#product-search').on('input', function() {
        var query = $(this).val();

        clearTimeout(searchTimer);

        if (query.length < 2) {
            $('#search-results').empty();
            return;
        }

        // Debounce: đợi 300ms sau khi user ngừng gõ
        searchTimer = setTimeout(function() {
            $.ajax({
                url: MyAjax.ajax_url,
                type: 'GET',
                data: {
                    action: 'my_search_products',
                    nonce: MyAjax.nonce,
                    q: query
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.results.forEach(function(item) {
                            html += '<div class="search-result-item">';
                            html += '<a href="' + item.url + '">' + item.title + '</a>';
                            if (item.price) {
                                html += ' - ' + Number(item.price).toLocaleString('vi-VN') + ' VNĐ';
                            }
                            html += '</div>';
                        });
                        $('#search-results').html(html || '<p>Không tìm thấy sản phẩm.</p>');
                    }
                }
            });
        }, 300);
    });

})(jQuery);
```

---

## 10. REST API Hooks

### rest_api_init

```
Khi nào chạy : Khi REST API được khởi tạo
Tham số      : Không có (WP_REST_Server có thể truy cập qua rest_get_server())
Dùng để      : Đăng ký custom REST routes/endpoints
```

```php
<?php
add_action( 'rest_api_init', 'my_register_rest_routes' );
function my_register_rest_routes() {

    // Endpoint: GET /wp-json/myplugin/v1/products
    register_rest_route( 'myplugin/v1', '/products', array(
        'methods'             => WP_REST_Server::READABLE,  // GET
        'callback'            => 'my_rest_get_products',
        'permission_callback' => '__return_true',            // Công khai
        'args'                => array(
            'per_page' => array(
                'default'           => 10,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0 && $param <= 100;
                },
                'sanitize_callback' => 'absint',
            ),
            'category' => array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Endpoint: POST /wp-json/myplugin/v1/contact
    register_rest_route( 'myplugin/v1', '/contact', array(
        'methods'             => WP_REST_Server::CREATABLE,  // POST
        'callback'            => 'my_rest_submit_contact',
        'permission_callback' => '__return_true',
        'args'                => array(
            'name' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $param ) {
                    return ! empty( $param ) && strlen( $param ) >= 2;
                },
            ),
            'email' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
            'message' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
        ),
    ));

    // Endpoint: GET/PUT/DELETE /wp-json/myplugin/v1/products/<id>
    register_rest_route( 'myplugin/v1', '/products/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'my_rest_get_product',
            'permission_callback' => '__return_true',
        ),
        array(
            'methods'             => WP_REST_Server::EDITABLE,   // PUT/PATCH
            'callback'            => 'my_rest_update_product',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' ); // Cần đăng nhập + quyền edit
            },
        ),
    ));
}

function my_rest_get_products( WP_REST_Request $request ) {
    $per_page = $request->get_param( 'per_page' );
    $category = $request->get_param( 'category' );

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
    );

    if ( ! empty( $category ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_category',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $query = new WP_Query( $args );
    $products = array();

    while ( $query->have_posts() ) {
        $query->the_post();
        $products[] = array(
            'id'        => get_the_ID(),
            'title'     => get_the_title(),
            'excerpt'   => get_the_excerpt(),
            'price'     => floatval( get_post_meta( get_the_ID(), '_product_price', true ) ),
            'thumbnail' => get_the_post_thumbnail_url( get_the_ID(), 'medium' ),
            'url'       => get_permalink(),
        );
    }
    wp_reset_postdata();

    return new WP_REST_Response( array(
        'products' => $products,
        'total'    => $query->found_posts,
    ), 200 );
}

function my_rest_submit_contact( WP_REST_Request $request ) {
    $name    = $request->get_param( 'name' );
    $email   = $request->get_param( 'email' );
    $message = $request->get_param( 'message' );

    // Lưu vào database
    $post_id = wp_insert_post( array(
        'post_type'   => 'contact_form',
        'post_title'  => 'Liên hệ từ ' . $name,
        'post_status' => 'private',
        'post_content' => $message,
    ));

    if ( is_wp_error( $post_id ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Có lỗi xảy ra, vui lòng thử lại.',
        ), 500 );
    }

    update_post_meta( $post_id, '_contact_email', $email );
    update_post_meta( $post_id, '_contact_name', $name );

    // Gửi email thông báo cho admin
    wp_mail(
        get_option( 'admin_email' ),
        'Liên hệ mới từ ' . $name,
        sprintf( "Tên: %s\nEmail: %s\n\nNội dung:\n%s", $name, $email, $message )
    );

    return new WP_REST_Response( array(
        'success' => true,
        'message' => 'Cảm ơn bạn! Chúng tôi đã nhận được tin nhắn.',
    ), 201 );
}

function my_rest_get_product( WP_REST_Request $request ) {
    $product_id = $request->get_param( 'id' );
    $post       = get_post( $product_id );

    if ( ! $post || 'product' !== $post->post_type ) {
        return new WP_REST_Response( array(
            'message' => 'Sản phẩm không tồn tại.',
        ), 404 );
    }

    return new WP_REST_Response( array(
        'id'          => $post->ID,
        'title'       => $post->post_title,
        'content'     => apply_filters( 'the_content', $post->post_content ),
        'price'       => floatval( get_post_meta( $post->ID, '_product_price', true ) ),
        'sku'         => get_post_meta( $post->ID, '_product_sku', true ),
        'in_stock'    => (bool) get_post_meta( $post->ID, '_product_in_stock', true ),
        'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'large' ),
    ), 200 );
}

function my_rest_update_product( WP_REST_Request $request ) {
    $product_id = $request->get_param( 'id' );
    // Update logic...
    return new WP_REST_Response( array( 'message' => 'Cập nhật thành công' ), 200 );
}
```

---

## 11. Cron Hooks

### Scheduled Events (WordPress Cron)

```
Dùng để      : Chạy tác vụ định kỳ (gửi email, cleanup, sync data)
Lưu ý        : WordPress Cron KHÔNG phải real cron - chỉ chạy khi có visit
```

```php
<?php
/**
 * Plugin Name: My Cron Demo
 * Description: Ví dụ WordPress Cron Jobs
 */

// === BƯỚC 1: Đăng ký custom schedule (nếu cần interval khác default) ===
add_filter( 'cron_schedules', 'my_add_cron_intervals' );
function my_add_cron_intervals( $schedules ) {
    // WordPress mặc định có: hourly, twicedaily, daily, weekly
    // Thêm custom interval: mỗi 5 phút
    $schedules['every_five_minutes'] = array(
        'interval' => 300,                    // 5 phút = 300 giây
        'display'  => 'Mỗi 5 phút',
    );

    // Mỗi 30 phút
    $schedules['every_thirty_minutes'] = array(
        'interval' => 1800,
        'display'  => 'Mỗi 30 phút',
    );

    return $schedules;
}

// === BƯỚC 2: Lên lịch event khi plugin activate ===
register_activation_hook( __FILE__, 'my_cron_activate' );
function my_cron_activate() {
    // Kiểm tra xem event đã được lên lịch chưa
    if ( ! wp_next_scheduled( 'my_daily_cleanup_event' ) ) {
        // Lên lịch chạy hàng ngày, bắt đầu từ bây giờ
        wp_schedule_event( time(), 'daily', 'my_daily_cleanup_event' );
    }

    if ( ! wp_next_scheduled( 'my_check_stock_event' ) ) {
        // Lên lịch chạy mỗi 30 phút
        wp_schedule_event( time(), 'every_thirty_minutes', 'my_check_stock_event' );
    }
}

// === BƯỚC 3: Xử lý khi event chạy ===
add_action( 'my_daily_cleanup_event', 'my_do_daily_cleanup' );
function my_do_daily_cleanup() {
    // Xóa transients đã hết hạn
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_timeout_%'
         AND option_value < UNIX_TIMESTAMP()"
    );

    // Xóa bài viết trong trash quá 30 ngày
    $old_trash = get_posts( array(
        'post_type'      => 'any',
        'post_status'    => 'trash',
        'posts_per_page' => -1,
        'date_query'     => array(
            array(
                'before' => '30 days ago',
                'column' => 'post_modified',
            ),
        ),
    ));

    foreach ( $old_trash as $post ) {
        wp_delete_post( $post->ID, true ); // true = force delete (không vào trash lại)
    }

    error_log( '[Cron] Daily cleanup completed. Deleted ' . count( $old_trash ) . ' trashed posts.' );
}

add_action( 'my_check_stock_event', 'my_check_stock' );
function my_check_stock() {
    // Kiểm tra sản phẩm hết hàng và gửi thông báo
    $out_of_stock = get_posts( array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => '_product_stock',
                'value' => 5,
                'type'  => 'NUMERIC',
                'compare' => '<=',
            ),
        ),
    ));

    if ( ! empty( $out_of_stock ) ) {
        $message = "Sản phẩm sắp hết hàng:\n\n";
        foreach ( $out_of_stock as $product ) {
            $stock = get_post_meta( $product->ID, '_product_stock', true );
            $message .= sprintf( "- %s (còn %d)\n", $product->post_title, $stock );
        }

        wp_mail( get_option( 'admin_email' ), 'Cảnh báo: Sản phẩm sắp hết hàng', $message );
    }
}

// === BƯỚC 4: Hủy lịch khi plugin deactivate ===
register_deactivation_hook( __FILE__, 'my_cron_deactivate' );
function my_cron_deactivate() {
    // Xóa tất cả scheduled events của plugin
    wp_clear_scheduled_hook( 'my_daily_cleanup_event' );
    wp_clear_scheduled_hook( 'my_check_stock_event' );
}

// === SINGLE EVENT (chạy 1 lần) ===
// Ví dụ: Gửi email nhắc nhở sau 24 giờ kể từ khi đặt hàng
function my_schedule_order_reminder( $order_id ) {
    // Lên lịch chạy SAU 24 giờ
    wp_schedule_single_event(
        time() + DAY_IN_SECONDS,       // 24 giờ sau
        'my_send_order_reminder',       // Hook name
        array( $order_id )              // Arguments
    );
}

add_action( 'my_send_order_reminder', 'my_do_send_order_reminder', 10, 1 );
function my_do_send_order_reminder( $order_id ) {
    $email = get_post_meta( $order_id, '_customer_email', true );
    if ( $email ) {
        wp_mail( $email, 'Nhắc nhở đơn hàng', 'Đơn hàng của bạn đang được xử lý...' );
    }
}
```

---

## 12. Best Practices

### 1. Chọn đúng hook, đúng thời điểm

```php
<?php
// SAI: Đăng ký post type trong 'plugins_loaded' (quá sớm)
// add_action( 'plugins_loaded', 'register_my_cpt' );

// ĐÚNG: Đăng ký post type trong 'init'
add_action( 'init', 'register_my_cpt' );

// SAI: Enqueue scripts trong 'init' (quá sớm, chưa biết frontend hay admin)
// add_action( 'init', 'my_scripts' );

// ĐÚNG: Enqueue scripts trong hook chuyên dụng
add_action( 'wp_enqueue_scripts', 'my_frontend_scripts' );      // Frontend
add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );       // Admin
add_action( 'login_enqueue_scripts', 'my_login_scripts' );       // Login page
```

### 2. Kiểm tra context (admin/frontend/ajax)

```php
<?php
add_action( 'init', function() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        // Code CHỈ chạy trong admin (không phải AJAX)
    }

    if ( ! is_admin() ) {
        // Code CHỈ chạy ở frontend
    }

    if ( wp_doing_ajax() ) {
        // Code CHỈ chạy khi xử lý AJAX
    }

    if ( wp_doing_cron() ) {
        // Code CHỈ chạy trong cron
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        // Code CHỈ chạy khi xử lý REST API request
    }
});
```

### 3. Luôn verify nonce trong AJAX handlers

```php
<?php
add_action( 'wp_ajax_my_action', function() {
    // LUÔN kiểm tra nonce!
    check_ajax_referer( 'my_nonce_action', 'nonce' );

    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Không có quyền.', 403 );
    }

    // Xử lý...
    wp_send_json_success( 'OK' );
});
```

### 4. Chỉ load assets khi cần

```php
<?php
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
    // ĐÚNG: Chỉ load ở trang cần thiết
    if ( 'toplevel_page_my-plugin' !== $hook_suffix ) {
        return;
    }
    wp_enqueue_script( 'my-plugin-admin', ... );
});
```

### 5. Cleanup khi deactivate plugin

```php
<?php
register_deactivation_hook( __FILE__, function() {
    // Xóa cron events
    wp_clear_scheduled_hook( 'my_cron_event' );

    // Xóa rewrite rules
    flush_rewrite_rules();

    // Xóa transients
    delete_transient( 'my_plugin_cache' );
});
```

### Bảng tham chiếu nhanh: Hook nào dùng cho việc gì?

| Việc cần làm | Hook phù hợp |
|-------------|---------------|
| Đăng ký Custom Post Type | `init` |
| Đăng ký Taxonomy | `init` |
| Đăng ký Shortcode | `init` |
| Load CSS/JS Frontend | `wp_enqueue_scripts` |
| Load CSS/JS Admin | `admin_enqueue_scripts` |
| Thêm Admin Menu | `admin_menu` |
| Đăng ký Settings | `admin_init` |
| Thêm Meta Tags | `wp_head` |
| Tracking Scripts | `wp_footer` |
| Xử lý AJAX | `wp_ajax_{action}` |
| REST API Endpoints | `rest_api_init` |
| Theme Features | `after_setup_theme` |
| Plugin Init | `plugins_loaded` |
| Lưu Post Meta | `save_post` hoặc `save_post_{type}` |
| Thông báo Admin | `admin_notices` |

---

> **Tiếp theo:** [03 - Filter Hooks Quan Trọng](03-filter-hooks-quan-trong.md) - Danh sách chi tiết các Filter Hooks hay dùng nhất.
