# Bài 8: Ví Dụ Thực Tế - WordPress Hooks

> **Tổng hợp các ví dụ hooks thực tế**, copy-paste chạy được ngay, áp dụng trong plugin hoặc `functions.php`.
> Mỗi ví dụ đều có **giải thích chi tiết** từng dòng code.

---

## Mục Lục

1. [Action Hooks - Ví dụ thực tế](#1-action-hooks---ví-dụ-thực-tế)
2. [Filter Hooks - Ví dụ thực tế](#2-filter-hooks---ví-dụ-thực-tế)
3. [Kết hợp Action + Filter](#3-kết-hợp-action--filter)
4. [Custom Hooks - Tạo hệ thống mở rộng](#4-custom-hooks---tạo-hệ-thống-mở-rộng)
5. [Hooks phổ biến trong dự án thực tế](#5-hooks-phổ-biến-trong-dự-án-thực-tế)
6. [Anti-patterns cần tránh](#6-anti-patterns-cần-tránh)
7. [WooCommerce Hooks - Ví dụ thực tế](#7-woocommerce-hooks---ví-dụ-thực-tế)
8. [User & Authentication Hooks](#8-user--authentication-hooks)
9. [Dashboard & Admin Hooks](#9-dashboard--admin-hooks)
10. [Performance & Optimization Hooks](#10-performance--optimization-hooks)
11. [REST API Hooks Nâng Cao](#11-rest-api-hooks-nâng-cao)
12. [Email Hooks - wp_mail & SMTP](#12-email-hooks---wp_mail--smtp)

---

## 1. Action Hooks - Ví Dụ Thực Tế

### 1.1. Đăng ký Custom Post Type khi WordPress khởi tạo

```php
/**
 * Hook: init
 * Thời điểm: WordPress đã load xong core, trước khi xử lý request
 * Dùng khi: Đăng ký CPT, taxonomy, rewrite rules
 */
add_action( 'init', 'mytheme_register_portfolio_cpt' );

function mytheme_register_portfolio_cpt() {
    $labels = array(
        'name'               => 'Portfolio',
        'singular_name'      => 'Portfolio Item',
        'menu_name'          => 'Portfolio',
        'add_new'            => 'Thêm mới',
        'add_new_item'       => 'Thêm Portfolio mới',
        'edit_item'          => 'Sửa Portfolio',
        'view_item'          => 'Xem Portfolio',
        'all_items'          => 'Tất cả Portfolio',
        'search_items'       => 'Tìm Portfolio',
        'not_found'          => 'Không tìm thấy',
        'not_found_in_trash' => 'Không có trong thùng rác',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,          // Hiển thị public
        'has_archive'        => true,          // Có trang archive
        'menu_icon'          => 'dashicons-portfolio', // Icon trong admin menu
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'            => array( 'slug' => 'portfolio' ),
        'show_in_rest'       => true,          // Hỗ trợ Gutenberg & REST API
    );

    register_post_type( 'portfolio', $args );
}
```

**Giải thích:**
- `init` là hook phổ biến nhất để đăng ký CPT vì WordPress đã load xong nhưng chưa xử lý query
- `show_in_rest => true` bắt buộc nếu muốn dùng Gutenberg editor
- `rewrite => slug` tạo URL đẹp: `yoursite.com/portfolio/ten-bai-viet`

---

### 1.2. Thêm Admin Menu tùy chỉnh

```php
/**
 * Hook: admin_menu
 * Thời điểm: Khi WordPress đang build admin sidebar menu
 * Dùng khi: Thêm trang settings, dashboard tùy chỉnh
 */
add_action( 'admin_menu', 'myplugin_add_admin_pages' );

function myplugin_add_admin_pages() {
    // Menu chính
    add_menu_page(
        'Cài đặt Plugin',           // Tiêu đề trang (title tag)
        'My Plugin',                 // Tên hiển thị trên menu
        'manage_options',            // Capability cần có (chỉ admin)
        'myplugin-settings',         // Menu slug (unique)
        'myplugin_settings_page',    // Callback render HTML
        'dashicons-admin-generic',   // Icon
        30                           // Vị trí (sau Comments = 25)
    );

    // Submenu
    add_submenu_page(
        'myplugin-settings',         // Parent slug
        'Thống kê',                  // Tiêu đề trang
        'Thống kê',                  // Tên menu
        'manage_options',            // Capability
        'myplugin-stats',            // Menu slug
        'myplugin_stats_page'        // Callback
    );
}

function myplugin_settings_page() {
    // Kiểm tra quyền trước khi render
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bạn không có quyền truy cập trang này.' );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'myplugin_options_group' );
            do_settings_sections( 'myplugin-settings' );
            submit_button( 'Lưu cài đặt' );
            ?>
        </form>
    </div>
    <?php
}

function myplugin_stats_page() {
    echo '<div class="wrap"><h1>Thống kê</h1><p>Nội dung thống kê...</p></div>';
}
```

**So sánh Laravel:**
| WordPress | Laravel |
|-----------|---------|
| `add_menu_page()` | Route + Controller + Sidebar component |
| `manage_options` capability | `Gate::allows('admin')` |
| `options.php` form action | `Route::post('/settings')` |

---

### 1.3. Enqueue Scripts & Styles đúng chuẩn

```php
/**
 * Hook: wp_enqueue_scripts (frontend)
 * Hook: admin_enqueue_scripts (admin)
 * Dùng khi: Load CSS/JS đúng cách, tránh conflict
 */

// === FRONTEND ===
add_action( 'wp_enqueue_scripts', 'mytheme_enqueue_assets' );

function mytheme_enqueue_assets() {
    // CSS - style.css của theme
    wp_enqueue_style(
        'mytheme-main',                              // Handle (unique ID)
        get_stylesheet_uri(),                         // URL file
        array(),                                      // Dependencies
        wp_get_theme()->get( 'Version' )             // Version (cache busting)
    );

    // CSS bên ngoài - Google Fonts
    wp_enqueue_style(
        'mytheme-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap',
        array(),
        null  // null = không thêm version query string
    );

    // JavaScript - với dependency jQuery
    wp_enqueue_script(
        'mytheme-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array( 'jquery' ),      // Phụ thuộc jQuery (WP tự load jQuery trước)
        '1.0.0',
        true                    // true = load ở footer (trước </body>)
    );

    // Truyền dữ liệu PHP → JavaScript
    wp_localize_script( 'mytheme-main', 'mythemeData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mytheme_nonce' ),
        'homeUrl'  => home_url(),
        'isLogged' => is_user_logged_in(),
    ) );

    // Load script chỉ trên trang cụ thể
    if ( is_singular( 'portfolio' ) ) {
        wp_enqueue_script(
            'mytheme-portfolio',
            get_template_directory_uri() . '/assets/js/portfolio.js',
            array( 'mytheme-main' ),
            '1.0.0',
            true
        );
    }
}

// === ADMIN ===
add_action( 'admin_enqueue_scripts', 'myplugin_admin_assets' );

function myplugin_admin_assets( $hook_suffix ) {
    // Chỉ load trên trang settings của plugin (tránh ảnh hưởng admin khác)
    if ( $hook_suffix !== 'toplevel_page_myplugin-settings' ) {
        return;
    }

    wp_enqueue_style(
        'myplugin-admin',
        plugin_dir_url( __FILE__ ) . 'admin/css/admin.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'myplugin-admin',
        plugin_dir_url( __FILE__ ) . 'admin/js/admin.js',
        array( 'jquery', 'wp-color-picker' ),  // Dùng WP Color Picker
        '1.0.0',
        true
    );
}
```

**Lưu ý quan trọng:**
- **Luôn dùng `wp_enqueue_*`**, không bao giờ echo `<script>` hoặc `<link>` trực tiếp
- Dùng `wp_localize_script()` để truyền dữ liệu PHP sang JS (thay vì inline script)
- Kiểm tra `$hook_suffix` trong admin để chỉ load assets trên trang cần thiết

---

### 1.4. Xử lý khi lưu bài viết (Save Post)

```php
/**
 * Hook: save_post
 * Thời điểm: Sau khi bài viết được lưu vào database
 * Dùng khi: Lưu custom meta data, gửi notification, sync data
 */
add_action( 'save_post', 'myplugin_save_portfolio_meta', 10, 3 );

function myplugin_save_portfolio_meta( $post_id, $post, $update ) {
    // 1. Kiểm tra autosave (WordPress tự lưu mỗi 60s)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 2. Kiểm tra đúng post type
    if ( $post->post_type !== 'portfolio' ) {
        return;
    }

    // 3. Kiểm tra nonce (CSRF protection)
    if ( ! isset( $_POST['portfolio_meta_nonce'] ) ||
         ! wp_verify_nonce( $_POST['portfolio_meta_nonce'], 'save_portfolio_meta' ) ) {
        return;
    }

    // 4. Kiểm tra quyền (capability check)
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 5. Sanitize và lưu dữ liệu
    if ( isset( $_POST['portfolio_url'] ) ) {
        update_post_meta(
            $post_id,
            '_portfolio_url',
            esc_url_raw( $_POST['portfolio_url'] )  // Sanitize URL
        );
    }

    if ( isset( $_POST['portfolio_client'] ) ) {
        update_post_meta(
            $post_id,
            '_portfolio_client',
            sanitize_text_field( $_POST['portfolio_client'] )  // Sanitize text
        );
    }

    if ( isset( $_POST['portfolio_year'] ) ) {
        update_post_meta(
            $post_id,
            '_portfolio_year',
            absint( $_POST['portfolio_year'] )  // Sanitize integer
        );
    }
}
```

**5 bước bảo mật bắt buộc khi save_post:**
1. Kiểm tra autosave
2. Kiểm tra post type
3. Verify nonce
4. Kiểm tra capability
5. Sanitize input trước khi lưu

---

### 1.5. Gửi email thông báo khi có bình luận mới

```php
/**
 * Hook: comment_post
 * Thời điểm: Ngay sau khi comment được lưu vào database
 * Dùng khi: Gửi notification, log, moderate
 */
add_action( 'comment_post', 'mytheme_notify_author_new_comment', 10, 3 );

function mytheme_notify_author_new_comment( $comment_id, $comment_approved, $commentdata ) {
    // Chỉ gửi khi comment được approved (1) hoặc pending (0)
    if ( $comment_approved === 'spam' ) {
        return;
    }

    $comment = get_comment( $comment_id );
    $post    = get_post( $comment->comment_post_ID );
    $author  = get_userdata( $post->post_author );

    // Không gửi nếu tác giả tự comment bài của mình
    if ( $comment->user_id == $post->post_author ) {
        return;
    }

    $subject = sprintf(
        '[%s] Bình luận mới trên "%s"',
        get_bloginfo( 'name' ),
        $post->post_title
    );

    $message = sprintf(
        "Xin chào %s,\n\n" .
        "%s vừa bình luận trên bài \"%s\":\n\n" .
        "---\n%s\n---\n\n" .
        "Xem bình luận: %s\n\n" .
        "Quản lý bình luận: %s",
        $author->display_name,
        $comment->comment_author,
        $post->post_title,
        $comment->comment_content,
        get_comment_link( $comment_id ),
        admin_url( 'edit-comments.php' )
    );

    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

    wp_mail( $author->user_email, $subject, $message, $headers );
}
```

---

### 1.6. Tạo bảng database khi kích hoạt plugin

```php
/**
 * Hook: register_activation_hook
 * Thời điểm: Khi plugin được activate lần đầu
 * Dùng khi: Tạo bảng, set default options, flush rewrite rules
 */
register_activation_hook( __FILE__, 'myplugin_activate' );

function myplugin_activate() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'contact_messages';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL tạo bảng - dbDelta yêu cầu format chính xác
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL DEFAULT '',
        email varchar(100) NOT NULL DEFAULT '',
        phone varchar(20) DEFAULT '',
        subject varchar(255) NOT NULL DEFAULT '',
        message text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'new',
        ip_address varchar(45) DEFAULT '',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY status (status),
        KEY created_at (created_at)
    ) {$charset_collate};";

    // dbDelta() so sánh schema hiện tại và chỉ thay đổi khi cần
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Lưu version để kiểm tra upgrade sau này
    update_option( 'myplugin_db_version', '1.0' );

    // Set default options
    add_option( 'myplugin_notification_email', get_option( 'admin_email' ) );
    add_option( 'myplugin_messages_per_page', 20 );
}

/**
 * Hook: register_deactivation_hook
 * Thời điểm: Khi plugin bị deactivate
 * Lưu ý: KHÔNG xóa data ở đây, chỉ dọn dẹp tạm thời
 */
register_deactivation_hook( __FILE__, 'myplugin_deactivate' );

function myplugin_deactivate() {
    // Xóa scheduled cron events
    wp_clear_scheduled_hook( 'myplugin_daily_cleanup' );

    // Flush rewrite rules
    flush_rewrite_rules();
}
```

**Lưu ý về `dbDelta()`:**
- Mỗi field trên 1 dòng riêng
- `PRIMARY KEY` phải có **2 dấu cách** trước dấu ngoặc: `PRIMARY KEY  (id)`
- Không có dấu phẩy sau field cuối cùng trước `PRIMARY KEY`
- Dùng `$wpdb->prefix` để hỗ trợ multisite

---

### 1.7. Lên lịch Cron Job tự động

```php
/**
 * Hook: wp_schedule_event + custom hook
 * Dùng khi: Dọn dẹp database, gửi email digest, sync data bên ngoài
 */

// Đăng ký cron khi plugin activate
register_activation_hook( __FILE__, 'myplugin_schedule_cron' );
function myplugin_schedule_cron() {
    if ( ! wp_next_scheduled( 'myplugin_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'myplugin_daily_cleanup' );
    }
}

// Xử lý khi cron chạy
add_action( 'myplugin_daily_cleanup', 'myplugin_do_cleanup' );
function myplugin_do_cleanup() {
    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';

    // Xóa messages cũ hơn 90 ngày đã được xử lý
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table} WHERE status = %s AND created_at < %s",
            'resolved',
            date( 'Y-m-d H:i:s', strtotime( '-90 days' ) )
        )
    );

    // Log kết quả
    error_log( sprintf(
        '[MyPlugin] Cleanup: Đã xóa %d messages cũ',
        $wpdb->rows_affected
    ) );
}

// Thêm interval tùy chỉnh (mặc định WP chỉ có hourly, twicedaily, daily)
add_filter( 'cron_schedules', 'myplugin_custom_cron_interval' );
function myplugin_custom_cron_interval( $schedules ) {
    $schedules['every_5_minutes'] = array(
        'interval' => 300,        // 5 phút = 300 giây
        'display'  => 'Mỗi 5 phút',
    );
    $schedules['weekly'] = array(
        'interval' => 604800,     // 7 ngày
        'display'  => 'Hàng tuần',
    );
    return $schedules;
}
```

---

### 1.8. Redirect sau khi đăng nhập theo Role

```php
/**
 * Hook: login_redirect
 * Thời điểm: Sau khi user đăng nhập thành công, trước khi redirect
 * Dùng khi: Điều hướng user đến trang phù hợp với vai trò
 */
add_filter( 'login_redirect', 'mytheme_login_redirect', 10, 3 );

function mytheme_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    // Kiểm tra user hợp lệ (tránh lỗi khi login thất bại)
    if ( ! is_a( $user, 'WP_User' ) ) {
        return $redirect_to;
    }

    // Redirect theo role
    if ( in_array( 'administrator', $user->roles, true ) ) {
        return admin_url();                    // Admin → Dashboard
    }

    if ( in_array( 'editor', $user->roles, true ) ) {
        return admin_url( 'edit.php' );        // Editor → All Posts
    }

    if ( in_array( 'subscriber', $user->roles, true ) ) {
        return home_url( '/tai-khoan/' );      // Subscriber → Trang tài khoản
    }

    return home_url();  // Mặc định → Trang chủ
}
```

---

## 2. Filter Hooks - Ví Dụ Thực Tế

### 2.1. Tùy chỉnh nội dung bài viết (the_content)

```php
/**
 * Filter: the_content
 * Dùng khi: Thêm nội dung tự động vào bài viết
 * Lưu ý: Filter này chạy mỗi khi get_the_content() hoặc the_content() được gọi
 */

// Thêm box "Bài viết liên quan" cuối bài
add_filter( 'the_content', 'mytheme_add_related_posts' );

function mytheme_add_related_posts( $content ) {
    // Chỉ thêm trên single post, không thêm trong feed hay admin
    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    // Lấy categories của bài hiện tại
    $categories = wp_get_post_categories( get_the_ID(), array( 'fields' => 'ids' ) );

    if ( empty( $categories ) ) {
        return $content;
    }

    // Query bài viết cùng category
    $related = new WP_Query( array(
        'category__in'        => $categories,
        'post__not_in'        => array( get_the_ID() ),  // Loại bài hiện tại
        'posts_per_page'      => 3,
        'no_found_rows'       => true,       // Tối ưu: không cần đếm tổng
        'ignore_sticky_posts' => true,
    ) );

    if ( ! $related->have_posts() ) {
        return $content;
    }

    // Build HTML
    $html = '<div class="related-posts">';
    $html .= '<h3>Bài viết liên quan</h3>';
    $html .= '<ul>';

    while ( $related->have_posts() ) {
        $related->the_post();
        $html .= sprintf(
            '<li><a href="%s">%s</a></li>',
            esc_url( get_permalink() ),
            esc_html( get_the_title() )
        );
    }
    wp_reset_postdata();  // QUAN TRỌNG: reset global $post

    $html .= '</ul></div>';

    return $content . $html;
}
```

**Giải thích 3 điều kiện quan trọng:**
- `is_singular('post')` → Chỉ trên trang single post
- `in_the_loop()` → Đang trong The Loop chính (không phải sidebar)
- `is_main_query()` → Query chính (không phải custom query)

---

### 2.2. Thay đổi truy vấn chính (pre_get_posts)

```php
/**
 * Filter: pre_get_posts
 * Thời điểm: Trước khi WordPress chạy query chính
 * Dùng khi: Thay đổi số bài/trang, sắp xếp, lọc post type
 * ĐÂY LÀ HOOK MẠNH NHẤT để tùy chỉnh query
 */
add_action( 'pre_get_posts', 'mytheme_customize_queries' );

function mytheme_customize_queries( $query ) {
    // LUÔN kiểm tra: không phải admin + là main query
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Trang archive hiển thị 12 bài/trang (thay vì default)
    if ( $query->is_archive() ) {
        $query->set( 'posts_per_page', 12 );
    }

    // Trang search: chỉ tìm trong post (loại page, attachment...)
    if ( $query->is_search() ) {
        $query->set( 'post_type', 'post' );
    }

    // Trang archive portfolio: sắp xếp theo menu_order
    if ( $query->is_post_type_archive( 'portfolio' ) ) {
        $query->set( 'orderby', 'menu_order' );
        $query->set( 'order', 'ASC' );
        $query->set( 'posts_per_page', 9 );
    }

    // Trang category: bao gồm cả CPT
    if ( $query->is_category() ) {
        $query->set( 'post_type', array( 'post', 'portfolio' ) );
    }

    // Trang author: loại bỏ bài private
    if ( $query->is_author() ) {
        $query->set( 'post_status', 'publish' );
    }
}
```

**Tại sao dùng `pre_get_posts` thay vì `new WP_Query`:**
- `pre_get_posts` **sửa query gốc** → WordPress chỉ chạy 1 query
- `new WP_Query` **tạo query thêm** → chạy 2 query (tốn tài nguyên hơn)
- Luôn ưu tiên `pre_get_posts` khi có thể

---

### 2.3. Tùy chỉnh excerpt (đoạn trích)

```php
/**
 * Filter: excerpt_length - Thay đổi độ dài excerpt
 * Filter: excerpt_more  - Thay đổi text "..." cuối excerpt
 * Filter: get_the_excerpt - Tùy chỉnh toàn bộ excerpt
 */

// Thay đổi độ dài: 55 từ → 25 từ
add_filter( 'excerpt_length', function( $length ) {
    return 25;
}, 999 );  // Priority 999 để ghi đè các plugin khác

// Thay đổi text cuối: "[...]" thay vì "..."
add_filter( 'excerpt_more', function( $more ) {
    return sprintf(
        '... <a href="%s" class="read-more">Đọc tiếp →</a>',
        esc_url( get_permalink() )
    );
} );

// Custom excerpt hoàn toàn
add_filter( 'get_the_excerpt', 'mytheme_custom_excerpt', 10, 2 );
function mytheme_custom_excerpt( $excerpt, $post ) {
    // Nếu bài viết có excerpt tùy chỉnh → giữ nguyên
    if ( has_excerpt( $post ) ) {
        return $excerpt;
    }

    // Nếu không, tạo excerpt từ content
    $content = get_the_content( '', false, $post );
    $content = strip_shortcodes( $content );        // Xóa shortcodes
    $content = wp_strip_all_tags( $content );        // Xóa HTML tags
    $content = wp_trim_words( $content, 30, '...' ); // Cắt 30 từ

    return $content;
}
```

---

### 2.4. Cho phép upload thêm loại file

```php
/**
 * Filter: upload_mimes
 * Dùng khi: Cho phép upload SVG, WebP, hoặc file đặc biệt
 */
add_filter( 'upload_mimes', 'mytheme_custom_upload_mimes' );

function mytheme_custom_upload_mimes( $mimes ) {
    // Thêm SVG (cẩn thận: SVG có thể chứa XSS)
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';

    // Thêm WebP
    $mimes['webp'] = 'image/webp';

    // Thêm file font
    $mimes['woff']  = 'font/woff';
    $mimes['woff2'] = 'font/woff2';

    // Xóa loại file không muốn cho upload
    unset( $mimes['exe'] );

    return $mimes;
}

// Xử lý thêm cho SVG: fix WordPress không nhận kích thước
add_filter( 'wp_check_filetype_and_ext', 'mytheme_fix_svg_upload', 10, 5 );
function mytheme_fix_svg_upload( $data, $file, $filename, $mimes, $real_mime ) {
    $ext = pathinfo( $filename, PATHINFO_EXTENSION );
    if ( $ext === 'svg' ) {
        $data['type'] = 'image/svg+xml';
        $data['ext']  = 'svg';
    }
    return $data;
}
```

---

### 2.5. Tùy chỉnh body class

```php
/**
 * Filter: body_class
 * Dùng khi: Thêm CSS class vào <body> để style theo điều kiện
 */
add_filter( 'body_class', 'mytheme_custom_body_classes' );

function mytheme_custom_body_classes( $classes ) {
    // Thêm class theo user role
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $classes[] = 'role-' . $user->roles[0];  // role-administrator, role-editor...
    } else {
        $classes[] = 'not-logged-in';
    }

    // Thêm class theo thời gian (sáng/tối - dùng cho dark mode tự động)
    $hour = current_time( 'G' );  // 0-23
    $classes[] = ( $hour >= 6 && $hour < 18 ) ? 'daytime' : 'nighttime';

    // Thêm class nếu có sidebar
    if ( is_active_sidebar( 'sidebar-1' ) && ! is_page_template( 'full-width.php' ) ) {
        $classes[] = 'has-sidebar';
    } else {
        $classes[] = 'no-sidebar full-width';
    }

    // Thêm class cho trang có thumbnail
    if ( is_singular() && has_post_thumbnail() ) {
        $classes[] = 'has-featured-image';
    }

    // Xóa class không cần (WordPress tự thêm nhiều class thừa)
    $remove = array( 'blog', 'wp-embed-responsive' );
    $classes = array_diff( $classes, $remove );

    return $classes;
}
```

---

### 2.6. Tùy chỉnh email WordPress

```php
/**
 * Filter: wp_mail_from      - Thay đổi email gửi
 * Filter: wp_mail_from_name - Thay đổi tên người gửi
 * Filter: wp_mail_content_type - Thay đổi định dạng email
 * Filter: wp_mail           - Tùy chỉnh toàn bộ email
 */

// Thay đổi "From" mặc định (WordPress dùng wordpress@yoursite.com)
add_filter( 'wp_mail_from', function( $email ) {
    return 'noreply@yoursite.com';
} );

add_filter( 'wp_mail_from_name', function( $name ) {
    return get_bloginfo( 'name' );
} );

// Gửi email HTML thay vì plain text
add_filter( 'wp_mail_content_type', function( $content_type ) {
    return 'text/html';
} );

// Tùy chỉnh toàn bộ email trước khi gửi
add_filter( 'wp_mail', 'mytheme_customize_emails' );
function mytheme_customize_emails( $args ) {
    // Wrap message trong HTML template đẹp
    $args['message'] = sprintf(
        '<!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #0073aa; color: #fff; padding: 20px; text-align: center;">
                <h1>%s</h1>
            </div>
            <div style="padding: 20px;">%s</div>
            <div style="background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px;">
                &copy; %s %s
            </div>
        </body>
        </html>',
        esc_html( get_bloginfo( 'name' ) ),
        $args['message'],
        date( 'Y' ),
        esc_html( get_bloginfo( 'name' ) )
    );

    return $args;
}
```

---

### 2.7. Thêm custom admin columns

```php
/**
 * Filter: manage_{post_type}_posts_columns  - Định nghĩa columns
 * Action: manage_{post_type}_posts_custom_column - Render nội dung
 * Filter: manage_edit-{post_type}_sortable_columns - Cho phép sort
 */

// Thêm columns cho Portfolio CPT
add_filter( 'manage_portfolio_posts_columns', 'mytheme_portfolio_columns' );
function mytheme_portfolio_columns( $columns ) {
    // Thêm columns tùy chỉnh
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        // Chèn sau cột title
        if ( $key === 'title' ) {
            $new_columns['thumbnail'] = 'Ảnh đại diện';
            $new_columns['client']    = 'Khách hàng';
            $new_columns['year']      = 'Năm';
        }
    }

    // Xóa column không cần
    unset( $new_columns['date'] );

    return $new_columns;
}

// Render nội dung columns
add_action( 'manage_portfolio_posts_custom_column', 'mytheme_portfolio_column_content', 10, 2 );
function mytheme_portfolio_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'thumbnail':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 60, 60 ) );
            } else {
                echo '<span style="color:#999">—</span>';
            }
            break;

        case 'client':
            $client = get_post_meta( $post_id, '_portfolio_client', true );
            echo $client ? esc_html( $client ) : '<span style="color:#999">—</span>';
            break;

        case 'year':
            $year = get_post_meta( $post_id, '_portfolio_year', true );
            echo $year ? esc_html( $year ) : '<span style="color:#999">—</span>';
            break;
    }
}

// Cho phép sort theo year
add_filter( 'manage_edit-portfolio_sortable_columns', 'mytheme_portfolio_sortable' );
function mytheme_portfolio_sortable( $columns ) {
    $columns['year']   = 'year';
    $columns['client'] = 'client';
    return $columns;
}

// Xử lý sort query
add_action( 'pre_get_posts', 'mytheme_portfolio_orderby' );
function mytheme_portfolio_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $orderby = $query->get( 'orderby' );
    if ( $orderby === 'year' ) {
        $query->set( 'meta_key', '_portfolio_year' );
        $query->set( 'orderby', 'meta_value_num' );
    }
    if ( $orderby === 'client' ) {
        $query->set( 'meta_key', '_portfolio_client' );
        $query->set( 'orderby', 'meta_value' );
    }
}
```

---

## 3. Kết Hợp Action + Filter

### 3.1. Hệ thống Contact Form hoàn chỉnh

```php
/**
 * Ví dụ kết hợp nhiều hooks để tạo contact form
 * - Shortcode hiển thị form
 * - AJAX xử lý submit
 * - Email notification
 * - Lưu vào database
 */

// === 1. Shortcode hiển thị form ===
add_shortcode( 'contact_form', 'myplugin_contact_form_shortcode' );

function myplugin_contact_form_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'title' => 'Liên hệ với chúng tôi',
    ), $atts );

    ob_start();
    ?>
    <div id="contact-form-wrapper">
        <h3><?php echo esc_html( $atts['title'] ); ?></h3>
        <form id="contact-form" method="post">
            <?php wp_nonce_field( 'contact_form_submit', 'contact_nonce' ); ?>

            <p>
                <label for="cf-name">Họ tên <span class="required">*</span></label>
                <input type="text" id="cf-name" name="name" required
                       minlength="2" maxlength="100">
            </p>
            <p>
                <label for="cf-email">Email <span class="required">*</span></label>
                <input type="email" id="cf-email" name="email" required>
            </p>
            <p>
                <label for="cf-phone">Số điện thoại</label>
                <input type="tel" id="cf-phone" name="phone">
            </p>
            <p>
                <label for="cf-subject">Chủ đề <span class="required">*</span></label>
                <input type="text" id="cf-subject" name="subject" required>
            </p>
            <p>
                <label for="cf-message">Nội dung <span class="required">*</span></label>
                <textarea id="cf-message" name="message" rows="5" required
                          minlength="10"></textarea>
            </p>
            <p>
                <button type="submit" id="cf-submit">Gửi tin nhắn</button>
            </p>
            <div id="cf-response"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// === 2. Enqueue JS cho form ===
add_action( 'wp_enqueue_scripts', 'myplugin_contact_form_scripts' );

function myplugin_contact_form_scripts() {
    // Chỉ load khi trang có shortcode
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'contact_form' ) ) {
        return;
    }

    wp_enqueue_script(
        'myplugin-contact',
        plugin_dir_url( __FILE__ ) . 'js/contact-form.js',
        array(),
        '1.0.0',
        true
    );

    wp_localize_script( 'myplugin-contact', 'contactFormData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'contact_form_submit' ),
    ) );
}

// === 3. AJAX handler (cả logged-in và không logged-in) ===
add_action( 'wp_ajax_submit_contact_form', 'myplugin_handle_contact_form' );
add_action( 'wp_ajax_nopriv_submit_contact_form', 'myplugin_handle_contact_form' );

function myplugin_handle_contact_form() {
    // Verify nonce
    if ( ! check_ajax_referer( 'contact_form_submit', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang.' ) );
    }

    // Sanitize input
    $name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

    // Validate
    $errors = array();
    if ( empty( $name ) || strlen( $name ) < 2 ) {
        $errors[] = 'Họ tên phải có ít nhất 2 ký tự.';
    }
    if ( ! is_email( $email ) ) {
        $errors[] = 'Email không hợp lệ.';
    }
    if ( empty( $subject ) ) {
        $errors[] = 'Vui lòng nhập chủ đề.';
    }
    if ( empty( $message ) || strlen( $message ) < 10 ) {
        $errors[] = 'Nội dung phải có ít nhất 10 ký tự.';
    }

    if ( ! empty( $errors ) ) {
        wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
    }

    // Lưu vào database
    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';
    $inserted = $wpdb->insert( $table, array(
        'name'       => $name,
        'email'      => $email,
        'phone'      => $phone,
        'subject'    => $subject,
        'message'    => $message,
        'ip_address' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
        'status'     => 'new',
        'created_at' => current_time( 'mysql' ),
    ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );

    if ( $inserted === false ) {
        wp_send_json_error( array( 'message' => 'Có lỗi xảy ra, vui lòng thử lại.' ) );
    }

    // Gửi email thông báo cho admin
    $admin_email = get_option( 'myplugin_notification_email', get_option( 'admin_email' ) );
    $email_subject = sprintf( '[%s] Tin nhắn mới: %s', get_bloginfo( 'name' ), $subject );
    $email_body = sprintf(
        "Bạn nhận được tin nhắn mới:\n\n" .
        "Họ tên: %s\nEmail: %s\nSĐT: %s\nChủ đề: %s\n\nNội dung:\n%s",
        $name, $email, $phone ?: '(không có)', $subject, $message
    );

    wp_mail( $admin_email, $email_subject, $email_body );

    // Custom hook cho developer khác mở rộng
    do_action( 'myplugin_contact_form_submitted', $wpdb->insert_id, compact(
        'name', 'email', 'phone', 'subject', 'message'
    ) );

    wp_send_json_success( array(
        'message' => 'Cảm ơn bạn! Tin nhắn đã được gửi thành công.'
    ) );
}
```

**JavaScript (js/contact-form.js):**

```javascript
// js/contact-form.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn  = document.getElementById('cf-submit');
        const responseEl = document.getElementById('cf-response');
        const formData   = new FormData(form);

        formData.append('action', 'submit_contact_form');
        formData.append('nonce', contactFormData.nonce);

        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang gửi...';
        responseEl.innerHTML = '';

        fetch(contactFormData.ajaxUrl, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                responseEl.innerHTML = '<div class="cf-success">' + data.data.message + '</div>';
                form.reset();
            } else {
                responseEl.innerHTML = '<div class="cf-error">' + data.data.message + '</div>';
            }
        })
        .catch(() => {
            responseEl.innerHTML = '<div class="cf-error">Lỗi kết nối, vui lòng thử lại.</div>';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Gửi tin nhắn';
        });
    });
});
```

---

## 4. Custom Hooks - Tạo Hệ Thống Mở Rộng

### 4.1. Plugin có thể mở rộng bởi plugin khác

```php
/**
 * Ví dụ: Plugin "Booking System" cho phép plugin khác mở rộng
 * Pattern: Tạo custom hooks tại các điểm quan trọng
 */

class MyBookingPlugin {
    /**
     * Xử lý đặt lịch
     */
    public function process_booking( $booking_data ) {
        // Cho phép plugin khác modify dữ liệu booking trước khi lưu
        $booking_data = apply_filters(
            'mybooking_before_save',
            $booking_data
        );

        // Validate
        $errors = $this->validate_booking( $booking_data );
        $errors = apply_filters( 'mybooking_validation_errors', $errors, $booking_data );

        if ( ! empty( $errors ) ) {
            // Hook action khi validation fail
            do_action( 'mybooking_validation_failed', $errors, $booking_data );
            return new WP_Error( 'validation_failed', implode( ', ', $errors ) );
        }

        // Lưu booking
        $booking_id = $this->save_booking( $booking_data );

        // Hook action sau khi booking thành công
        do_action( 'mybooking_created', $booking_id, $booking_data );

        // Gửi email (có thể bị plugin khác chặn)
        $send_email = apply_filters( 'mybooking_send_confirmation_email', true, $booking_id );
        if ( $send_email ) {
            $this->send_confirmation( $booking_id );
            do_action( 'mybooking_confirmation_sent', $booking_id );
        }

        // Cho phép plugin khác modify response
        $response = apply_filters( 'mybooking_response', array(
            'success'    => true,
            'booking_id' => $booking_id,
            'message'    => 'Đặt lịch thành công!',
        ), $booking_id );

        return $response;
    }
}

// === Plugin khác có thể hook vào ===

// Thêm field discount khi validate
add_filter( 'mybooking_before_save', function( $data ) {
    // Tính discount cho member VIP
    if ( current_user_can( 'vip_member' ) ) {
        $data['discount'] = $data['price'] * 0.1;  // Giảm 10%
    }
    return $data;
} );

// Gửi SMS khi booking thành công
add_action( 'mybooking_created', function( $booking_id, $data ) {
    // Gọi SMS API
    $phone = $data['phone'] ?? '';
    if ( $phone ) {
        sms_api_send( $phone, "Đặt lịch #{$booking_id} thành công!" );
    }
}, 10, 2 );

// Sync booking lên Google Calendar
add_action( 'mybooking_created', 'sync_to_google_calendar', 20, 2 );
function sync_to_google_calendar( $booking_id, $data ) {
    // ... Google Calendar API
}

// Tắt email xác nhận (dùng SMS thay thế)
add_filter( 'mybooking_send_confirmation_email', '__return_false' );
```

---

### 4.2. Theme cho phép child theme tùy chỉnh

```php
/**
 * Trong parent theme functions.php
 * Tạo custom hooks để child theme có thể override
 */

// === Header area ===
function mytheme_header() {
    // Hook trước header
    do_action( 'mytheme_before_header' );

    // Cho phép child theme override toàn bộ header
    $custom_header = apply_filters( 'mytheme_header_output', '' );
    if ( $custom_header ) {
        echo $custom_header;
    } else {
        // Header mặc định
        get_template_part( 'template-parts/header', 'default' );
    }

    // Hook sau header
    do_action( 'mytheme_after_header' );
}

// === Logo ===
function mytheme_logo() {
    $logo_html = apply_filters( 'mytheme_logo', '' );

    if ( ! $logo_html ) {
        if ( has_custom_logo() ) {
            $logo_html = get_custom_logo();
        } else {
            $logo_html = sprintf(
                '<a href="%s" class="site-title">%s</a>',
                esc_url( home_url( '/' ) ),
                esc_html( get_bloginfo( 'name' ) )
            );
        }
    }

    echo $logo_html;
}

// === Footer credits ===
function mytheme_footer_credits() {
    $credits = apply_filters(
        'mytheme_footer_credits',
        sprintf(
            '&copy; %s %s. Powered by WordPress.',
            date( 'Y' ),
            esc_html( get_bloginfo( 'name' ) )
        )
    );

    echo '<div class="site-credits">' . wp_kses_post( $credits ) . '</div>';
}

// === Trong Child Theme: override ===

// Thêm banner quảng cáo trước header
add_action( 'mytheme_before_header', function() {
    echo '<div class="promo-banner">Giảm giá 50% - Chỉ hôm nay!</div>';
} );

// Thay đổi footer credits
add_filter( 'mytheme_footer_credits', function( $credits ) {
    return '&copy; ' . date( 'Y' ) . ' Công ty ABC. All rights reserved.';
} );
```

---

## 5. Hooks Phổ Biến Trong Dự Án Thực Tế

### 5.1. Bật chế độ bảo trì (Maintenance Mode)

```php
add_action( 'template_redirect', 'mytheme_maintenance_mode' );

function mytheme_maintenance_mode() {
    // Cho phép admin vẫn truy cập
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    // Kiểm tra option bật/tắt
    if ( ! get_option( 'mytheme_maintenance_mode', false ) ) {
        return;
    }

    // Trả về HTTP 503 (Service Unavailable)
    wp_die(
        '<h1>Website đang bảo trì</h1>' .
        '<p>Chúng tôi đang cập nhật hệ thống. Vui lòng quay lại sau.</p>',
        'Đang bảo trì',
        array( 'response' => 503 )
    );
}
```

---

### 5.2. Tùy chỉnh trang đăng nhập

```php
// Thay logo WordPress bằng logo site
add_action( 'login_enqueue_scripts', 'mytheme_custom_login_style' );
function mytheme_custom_login_style() {
    $logo = get_theme_mod( 'custom_logo' );
    $logo_url = $logo ? wp_get_attachment_image_url( $logo, 'medium' ) : '';
    ?>
    <style>
        body.login {
            background-color: #f0f2f5;
        }
        #login h1 a {
            background-image: url('<?php echo esc_url( $logo_url ); ?>');
            background-size: contain;
            width: 200px;
            height: 80px;
        }
        .login form {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .login #backtoblog, .login #nav {
            text-align: center;
        }
    </style>
    <?php
}

// Thay đổi URL logo (click vào logo → về trang chủ thay vì wordpress.org)
add_filter( 'login_headerurl', function() {
    return home_url( '/' );
} );

// Thay đổi title tooltip
add_filter( 'login_headertext', function() {
    return get_bloginfo( 'name' );
} );

// Thay đổi thông báo lỗi (ẩn thông tin nhạy cảm)
add_filter( 'login_errors', function( $error ) {
    return 'Thông tin đăng nhập không chính xác.';
} );
```

---

### 5.3. Ẩn menu admin cho user không phải admin

```php
add_action( 'admin_menu', 'mytheme_remove_menus_for_non_admin', 999 );

function mytheme_remove_menus_for_non_admin() {
    if ( current_user_can( 'manage_options' ) ) {
        return;  // Admin thấy tất cả
    }

    // Ẩn các menu không cần thiết
    remove_menu_page( 'tools.php' );           // Tools
    remove_menu_page( 'options-general.php' );  // Settings
    remove_menu_page( 'themes.php' );           // Appearance
    remove_menu_page( 'plugins.php' );          // Plugins
    remove_menu_page( 'users.php' );            // Users

    // Ẩn submenu cụ thể
    remove_submenu_page( 'index.php', 'update-core.php' );  // Updates
}

// Ẩn Admin Bar trên frontend cho subscriber
add_action( 'after_setup_theme', function() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        show_admin_bar( false );
    }
} );
```

---

### 5.4. Thêm Meta Box tùy chỉnh

```php
/**
 * Ví dụ: Meta Box "Thông tin SEO" cho bài viết
 */

// Đăng ký Meta Box
add_action( 'add_meta_boxes', 'mytheme_add_seo_meta_box' );

function mytheme_add_seo_meta_box() {
    add_meta_box(
        'mytheme_seo',           // ID (unique)
        'Thông tin SEO',         // Title
        'mytheme_seo_meta_box',  // Callback render HTML
        array( 'post', 'page' ), // Post types
        'normal',                // Context: normal, side, advanced
        'high'                   // Priority: high, core, default, low
    );
}

// Render Meta Box HTML
function mytheme_seo_meta_box( $post ) {
    // Nonce field cho bảo mật
    wp_nonce_field( 'mytheme_save_seo', 'mytheme_seo_nonce' );

    // Lấy giá trị đã lưu
    $seo_title       = get_post_meta( $post->ID, '_seo_title', true );
    $seo_description = get_post_meta( $post->ID, '_seo_description', true );
    $seo_keywords    = get_post_meta( $post->ID, '_seo_keywords', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="seo_title">SEO Title</label></th>
            <td>
                <input type="text" id="seo_title" name="seo_title"
                       value="<?php echo esc_attr( $seo_title ); ?>"
                       class="regular-text" maxlength="70">
                <p class="description">Tối đa 70 ký tự. Hiện tại:
                    <span id="seo-title-count"><?php echo strlen( $seo_title ); ?></span>/70
                </p>
            </td>
        </tr>
        <tr>
            <th><label for="seo_description">Meta Description</label></th>
            <td>
                <textarea id="seo_description" name="seo_description"
                          rows="3" class="large-text"
                          maxlength="160"><?php echo esc_textarea( $seo_description ); ?></textarea>
                <p class="description">Tối đa 160 ký tự. Hiện tại:
                    <span id="seo-desc-count"><?php echo strlen( $seo_description ); ?></span>/160
                </p>
            </td>
        </tr>
        <tr>
            <th><label for="seo_keywords">Keywords</label></th>
            <td>
                <input type="text" id="seo_keywords" name="seo_keywords"
                       value="<?php echo esc_attr( $seo_keywords ); ?>"
                       class="regular-text">
                <p class="description">Phân cách bằng dấu phẩy. VD: wordpress, theme, plugin</p>
            </td>
        </tr>
    </table>
    <?php
}

// Lưu Meta Box data
add_action( 'save_post', 'mytheme_save_seo_meta', 10, 2 );

function mytheme_save_seo_meta( $post_id, $post ) {
    // Kiểm tra nonce
    if ( ! isset( $_POST['mytheme_seo_nonce'] ) ||
         ! wp_verify_nonce( $_POST['mytheme_seo_nonce'], 'mytheme_save_seo' ) ) {
        return;
    }

    // Kiểm tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Sanitize & save
    $fields = array(
        'seo_title'       => 'sanitize_text_field',
        'seo_description' => 'sanitize_textarea_field',
        'seo_keywords'    => 'sanitize_text_field',
    );

    foreach ( $fields as $field => $sanitize_fn ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = call_user_func( $sanitize_fn, wp_unslash( $_POST[ $field ] ) );
            update_post_meta( $post_id, '_' . $field, $value );
        }
    }
}

// Output SEO meta tags trong <head>
add_action( 'wp_head', 'mytheme_output_seo_meta' );

function mytheme_output_seo_meta() {
    if ( ! is_singular() ) {
        return;
    }

    $post_id = get_the_ID();

    $seo_title = get_post_meta( $post_id, '_seo_title', true );
    if ( $seo_title ) {
        printf( '<meta name="title" content="%s">' . "\n", esc_attr( $seo_title ) );
    }

    $seo_description = get_post_meta( $post_id, '_seo_description', true );
    if ( $seo_description ) {
        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $seo_description ) );
    }

    $seo_keywords = get_post_meta( $post_id, '_seo_keywords', true );
    if ( $seo_keywords ) {
        printf( '<meta name="keywords" content="%s">' . "\n", esc_attr( $seo_keywords ) );
    }
}
```

---

### 5.5. REST API endpoint tùy chỉnh

```php
/**
 * Hook: rest_api_init
 * Tạo API endpoint cho frontend (React, Vue, Mobile App)
 */
add_action( 'rest_api_init', 'myplugin_register_api_routes' );

function myplugin_register_api_routes() {
    // GET /wp-json/myplugin/v1/portfolio
    register_rest_route( 'myplugin/v1', '/portfolio', array(
        'methods'             => WP_REST_Server::READABLE,  // GET
        'callback'            => 'myplugin_get_portfolio',
        'permission_callback' => '__return_true',  // Public API
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
    ) );

    // POST /wp-json/myplugin/v1/contact
    register_rest_route( 'myplugin/v1', '/contact', array(
        'methods'             => WP_REST_Server::CREATABLE,  // POST
        'callback'            => 'myplugin_submit_contact',
        'permission_callback' => '__return_true',
        'args'                => array(
            'name'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'email'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_email' ),
            'message' => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
        ),
    ) );
}

function myplugin_get_portfolio( WP_REST_Request $request ) {
    $args = array(
        'post_type'      => 'portfolio',
        'posts_per_page' => $request->get_param( 'per_page' ),
        'post_status'    => 'publish',
    );

    // Lọc theo category nếu có
    $category = $request->get_param( 'category' );
    if ( $category ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'portfolio_category',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $query = new WP_Query( $args );
    $items = array();

    foreach ( $query->posts as $post ) {
        $items[] = array(
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'excerpt'   => get_the_excerpt( $post ),
            'url'       => get_permalink( $post ),
            'thumbnail' => get_the_post_thumbnail_url( $post, 'medium' ),
            'client'    => get_post_meta( $post->ID, '_portfolio_client', true ),
            'year'      => get_post_meta( $post->ID, '_portfolio_year', true ),
        );
    }

    return new WP_REST_Response( array(
        'items' => $items,
        'total' => $query->found_posts,
    ), 200 );
}

function myplugin_submit_contact( WP_REST_Request $request ) {
    $name    = $request->get_param( 'name' );
    $email   = $request->get_param( 'email' );
    $message = $request->get_param( 'message' );

    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Email không hợp lệ.',
        ), 400 );
    }

    // Lưu vào database...
    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'contact_messages', array(
        'name'       => $name,
        'email'      => $email,
        'message'    => $message,
        'status'     => 'new',
        'created_at' => current_time( 'mysql' ),
    ) );

    return new WP_REST_Response( array(
        'success' => true,
        'message' => 'Gửi thành công!',
        'id'      => $wpdb->insert_id,
    ), 201 );
}
```

---

## 6. Anti-Patterns Cần Tránh

### 6.1. SAI: Echo script/style trực tiếp

```php
// ❌ SAI - Không bao giờ làm thế này
add_action( 'wp_head', function() {
    echo '<link rel="stylesheet" href="/my-style.css">';
    echo '<script src="/my-script.js"></script>';
} );

// ✅ ĐÚNG - Luôn dùng enqueue
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'my-style', get_template_directory_uri() . '/my-style.css' );
    wp_enqueue_script( 'my-script', get_template_directory_uri() . '/my-script.js', array(), '1.0', true );
} );
```

### 6.2. SAI: Không kiểm tra is_main_query trong pre_get_posts

```php
// ❌ SAI - Ảnh hưởng TẤT CẢ queries (sidebar, widgets, custom queries...)
add_action( 'pre_get_posts', function( $query ) {
    $query->set( 'posts_per_page', 5 );
} );

// ✅ ĐÚNG - Chỉ ảnh hưởng main query trên frontend
add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        $query->set( 'posts_per_page', 5 );
    }
} );
```

### 6.3. SAI: Quên wp_reset_postdata sau custom query

```php
// ❌ SAI - Global $post bị thay đổi, ảnh hưởng code phía sau
$custom = new WP_Query( array( 'post_type' => 'portfolio' ) );
while ( $custom->have_posts() ) {
    $custom->the_post();
    the_title();
}
// Từ đây trở đi, the_title() sẽ trả về title của portfolio cuối cùng!

// ✅ ĐÚNG - Reset global $post
$custom = new WP_Query( array( 'post_type' => 'portfolio' ) );
while ( $custom->have_posts() ) {
    $custom->the_post();
    the_title();
}
wp_reset_postdata();  // Khôi phục global $post
```

### 6.4. SAI: Không sanitize/escape data

```php
// ❌ SAI - XSS vulnerability
$name = $_POST['name'];
echo "Xin chào " . $name;
update_post_meta( $post_id, '_name', $name );

// ✅ ĐÚNG
$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );  // Sanitize input
echo "Xin chào " . esc_html( $name );                          // Escape output
update_post_meta( $post_id, '_name', $name );
```

### 6.5. SAI: Quên return giá trị trong filter

```php
// ❌ SAI - Filter PHẢI return giá trị, nếu không nội dung bài viết sẽ biến mất!
add_filter( 'the_content', function( $content ) {
    // Quên return → $content = null → bài viết trống!
    error_log( 'Content length: ' . strlen( $content ) );
} );

// ✅ ĐÚNG
add_filter( 'the_content', function( $content ) {
    error_log( 'Content length: ' . strlen( $content ) );
    return $content;  // Luôn return giá trị!
} );
```

---

## Tổng Kết

| Loại Hook | Khi nào dùng | Có return không? |
|-----------|-------------|------------------|
| **Action** | Thực thi code tại một điểm (gửi email, lưu data, enqueue scripts) | Không |
| **Filter** | Thay đổi/modify dữ liệu (nội dung, query, class, email) | **Bắt buộc** |
| **Custom Action** | Tạo điểm mở rộng cho plugin/theme khác | Không |
| **Custom Filter** | Cho phép plugin/theme khác modify dữ liệu của bạn | **Bắt buộc** |

**Quy tắc vàng:**
1. **Action** = "Làm gì đó" → `do_action()` / `add_action()`
2. **Filter** = "Thay đổi cái gì đó" → `apply_filters()` / `add_filter()` → **Luôn return!**
3. Luôn kiểm tra `is_admin()` + `is_main_query()` trong `pre_get_posts`
4. Luôn sanitize input, escape output
5. Luôn verify nonce khi xử lý form/AJAX

---

## 7. WooCommerce Hooks - Ví Dụ Thực Tế

> WooCommerce sử dụng hệ thống hooks rất rộng. Dưới đây là các hooks phổ biến nhất.

### 7.1. Thêm nội dung vào trang sản phẩm

```php
/**
 * WooCommerce Action Hooks trên trang sản phẩm đơn
 *
 * Thứ tự hooks trên trang single product:
 * woocommerce_before_single_product
 * woocommerce_before_single_product_summary
 * woocommerce_single_product_summary (title, rating, price, excerpt, add to cart, meta)
 * woocommerce_after_single_product_summary (tabs, related products)
 * woocommerce_after_single_product
 */

// Thêm badge "Mới" cho sản phẩm mới (< 30 ngày)
add_action( 'woocommerce_before_single_product_summary', 'mytheme_new_product_badge', 5 );

function mytheme_new_product_badge() {
    global $product;
    $created = strtotime( $product->get_date_created() );
    $now     = time();

    if ( ( $now - $created ) < ( 30 * DAY_IN_SECONDS ) ) {
        echo '<span class="new-badge" style="
            position:absolute; top:10px; left:10px; z-index:10;
            background:#e74c3c; color:#fff; padding:4px 12px;
            font-size:12px; font-weight:bold; border-radius:3px;
        ">MỚI</span>';
    }
}

// Thêm thông tin giao hàng sau giá
add_action( 'woocommerce_single_product_summary', 'mytheme_shipping_info', 15 );

function mytheme_shipping_info() {
    echo '<div class="shipping-info" style="
        background:#e8f5e9; padding:10px 15px; border-radius:4px;
        margin:10px 0; font-size:14px;
    ">';
    echo '🚚 Miễn phí giao hàng cho đơn từ 500.000đ';
    echo '</div>';
}

// Thêm tab tùy chỉnh trong product tabs
add_filter( 'woocommerce_product_tabs', 'mytheme_custom_product_tabs' );

function mytheme_custom_product_tabs( $tabs ) {
    // Thêm tab "Hướng dẫn sử dụng"
    $tabs['usage_guide'] = array(
        'title'    => __( 'Hướng dẫn sử dụng', 'mytheme' ),
        'priority' => 50,
        'callback' => function() {
            global $product;
            $guide = get_post_meta( $product->get_id(), '_usage_guide', true );
            if ( $guide ) {
                echo '<h2>' . esc_html__( 'Hướng dẫn sử dụng', 'mytheme' ) . '</h2>';
                echo wp_kses_post( $guide );
            } else {
                echo '<p>' . esc_html__( 'Chưa có hướng dẫn cho sản phẩm này.', 'mytheme' ) . '</p>';
            }
        },
    );

    // Thêm tab "Chính sách đổi trả"
    $tabs['return_policy'] = array(
        'title'    => __( 'Đổi trả', 'mytheme' ),
        'priority' => 60,
        'callback' => function() {
            echo '<h2>Chính sách đổi trả</h2>';
            echo '<ul>';
            echo '<li>Đổi trả trong vòng 7 ngày kể từ ngày nhận hàng</li>';
            echo '<li>Sản phẩm còn nguyên tem, nhãn, chưa qua sử dụng</li>';
            echo '<li>Hoàn tiền trong 3-5 ngày làm việc</li>';
            echo '</ul>';
        },
    );

    // Xóa tab review nếu muốn
    // unset( $tabs['reviews'] );

    // Đổi tên tab description
    if ( isset( $tabs['description'] ) ) {
        $tabs['description']['title'] = __( 'Chi tiết sản phẩm', 'mytheme' );
    }

    return $tabs;
}
```

---

### 7.2. Tùy chỉnh giỏ hàng và thanh toán

```php
/**
 * Hooks giỏ hàng (Cart) và thanh toán (Checkout)
 */

// Thêm thông báo miễn phí ship trên trang giỏ hàng
add_action( 'woocommerce_before_cart', 'mytheme_free_shipping_notice' );

function mytheme_free_shipping_notice() {
    $min_amount = 500000; // 500.000đ
    $current    = WC()->cart->get_subtotal();
    $remaining  = $min_amount - $current;

    if ( $remaining > 0 ) {
        wc_print_notice(
            sprintf(
                __( 'Mua thêm %s để được MIỄN PHÍ giao hàng!', 'mytheme' ),
                wc_price( $remaining )
            ),
            'notice'
        );
    } else {
        wc_print_notice(
            __( '🎉 Bạn đã đủ điều kiện MIỄN PHÍ giao hàng!', 'mytheme' ),
            'success'
        );
    }
}

// Validate checkout trước khi đặt hàng
add_action( 'woocommerce_checkout_process', 'mytheme_validate_checkout' );

function mytheme_validate_checkout() {
    // Yêu cầu SĐT phải 10 số
    $phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( $_POST['billing_phone'] ) : '';
    if ( ! preg_match( '/^0[0-9]{9}$/', $phone ) ) {
        wc_add_notice(
            __( 'Số điện thoại không hợp lệ. Vui lòng nhập 10 chữ số bắt đầu bằng 0.', 'mytheme' ),
            'error'
        );
    }

    // Giá trị đơn hàng tối thiểu
    $min_order = 100000; // 100.000đ
    if ( WC()->cart->get_subtotal() < $min_order ) {
        wc_add_notice(
            sprintf(
                __( 'Giá trị đơn hàng tối thiểu là %s', 'mytheme' ),
                wc_price( $min_order )
            ),
            'error'
        );
    }
}

// Thêm field tùy chỉnh vào checkout
add_action( 'woocommerce_after_order_notes', 'mytheme_checkout_custom_fields' );

function mytheme_checkout_custom_fields( $checkout ) {
    woocommerce_form_field( 'delivery_date', array(
        'type'        => 'date',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Ngày giao hàng mong muốn', 'mytheme' ),
        'placeholder' => '',
        'required'    => false,
    ), $checkout->get_value( 'delivery_date' ) );

    woocommerce_form_field( 'gift_message', array(
        'type'        => 'textarea',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Lời nhắn quà tặng', 'mytheme' ),
        'placeholder' => __( 'Nếu đây là quà tặng, nhập lời nhắn tại đây...', 'mytheme' ),
    ), $checkout->get_value( 'gift_message' ) );
}

// Lưu custom fields khi đặt hàng
add_action( 'woocommerce_checkout_update_order_meta', 'mytheme_save_checkout_custom_fields' );

function mytheme_save_checkout_custom_fields( $order_id ) {
    if ( ! empty( $_POST['delivery_date'] ) ) {
        update_post_meta( $order_id, '_delivery_date', sanitize_text_field( $_POST['delivery_date'] ) );
    }
    if ( ! empty( $_POST['gift_message'] ) ) {
        update_post_meta( $order_id, '_gift_message', sanitize_textarea_field( $_POST['gift_message'] ) );
    }
}

// Hiển thị custom fields trong admin order detail
add_action( 'woocommerce_admin_order_data_after_billing_address', 'mytheme_display_order_custom_fields' );

function mytheme_display_order_custom_fields( $order ) {
    $delivery_date = get_post_meta( $order->get_id(), '_delivery_date', true );
    $gift_message  = get_post_meta( $order->get_id(), '_gift_message', true );

    if ( $delivery_date ) {
        echo '<p><strong>' . esc_html__( 'Ngày giao:', 'mytheme' ) . '</strong> ';
        echo esc_html( date_i18n( 'd/m/Y', strtotime( $delivery_date ) ) ) . '</p>';
    }
    if ( $gift_message ) {
        echo '<p><strong>' . esc_html__( 'Lời nhắn quà:', 'mytheme' ) . '</strong> ';
        echo esc_html( $gift_message ) . '</p>';
    }
}
```

---

### 7.3. Xử lý khi đơn hàng thay đổi trạng thái

```php
/**
 * Hook: woocommerce_order_status_changed
 * Thời điểm: Khi trạng thái đơn hàng thay đổi
 * Dùng khi: Gửi notification, cập nhật tồn kho, tích điểm
 */
add_action( 'woocommerce_order_status_changed', 'mytheme_order_status_changed', 10, 4 );

function mytheme_order_status_changed( $order_id, $old_status, $new_status, $order ) {
    // Gửi SMS khi đơn hàng được giao
    if ( $new_status === 'completed' ) {
        $phone = $order->get_billing_phone();
        $name  = $order->get_billing_first_name();
        $total = $order->get_total();

        // Gọi SMS API (ví dụ)
        // sms_send( $phone, "Xin chào {$name}, đơn hàng #{$order_id} ({$total}đ) đã giao thành công!" );

        // Tích điểm cho khách hàng (1% giá trị đơn)
        $user_id = $order->get_user_id();
        if ( $user_id ) {
            $current_points = (int) get_user_meta( $user_id, '_loyalty_points', true );
            $earned_points  = (int) floor( $total / 100 ); // 1 điểm / 100đ
            update_user_meta( $user_id, '_loyalty_points', $current_points + $earned_points );

            // Log
            error_log( sprintf(
                '[Loyalty] User #%d earned %d points from order #%d',
                $user_id, $earned_points, $order_id
            ) );
        }
    }

    // Gửi email nội bộ khi đơn bị hủy
    if ( $new_status === 'cancelled' ) {
        $admin_email = get_option( 'admin_email' );
        wp_mail(
            $admin_email,
            sprintf( '[%s] Đơn hàng #%d bị hủy', get_bloginfo( 'name' ), $order_id ),
            sprintf(
                "Đơn hàng #%d từ %s (%s) đã bị hủy.\nGiá trị: %s\nTrạng thái trước: %s",
                $order_id,
                $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                $order->get_billing_email(),
                wc_price( $order->get_total() ),
                $old_status
            )
        );
    }
}

// Tự động hoàn thành đơn hàng cho sản phẩm virtual/downloadable
add_action( 'woocommerce_thankyou', 'mytheme_auto_complete_virtual_orders' );

function mytheme_auto_complete_virtual_orders( $order_id ) {
    if ( ! $order_id ) return;

    $order = wc_get_order( $order_id );
    if ( $order->get_status() === 'processing' ) {
        $all_virtual = true;
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product->is_virtual() && ! $product->is_downloadable() ) {
                $all_virtual = false;
                break;
            }
        }

        if ( $all_virtual ) {
            $order->update_status( 'completed', __( 'Tự động hoàn thành (sản phẩm virtual).', 'mytheme' ) );
        }
    }
}
```

---

### 7.4. Tùy chỉnh giá sản phẩm

```php
/**
 * Filter: woocommerce_get_price_html - Thay đổi HTML hiển thị giá
 * Filter: woocommerce_product_get_price - Thay đổi giá thực tế
 */

// Hiển thị "Liên hệ" thay vì giá 0đ
add_filter( 'woocommerce_get_price_html', 'mytheme_custom_price_html', 10, 2 );

function mytheme_custom_price_html( $price_html, $product ) {
    if ( $product->get_price() == 0 ) {
        return '<span class="price-contact">' .
               '<a href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">' .
               esc_html__( 'Liên hệ báo giá', 'mytheme' ) .
               '</a></span>';
    }

    // Thêm "/ tháng" cho sản phẩm subscription
    if ( $product->get_meta( '_is_subscription' ) === 'yes' ) {
        $price_html .= '<span class="subscription-period"> / tháng</span>';
    }

    return $price_html;
}

// Giảm giá 10% cho user role "wholesale"
add_filter( 'woocommerce_product_get_price', 'mytheme_wholesale_price', 10, 2 );
add_filter( 'woocommerce_product_get_regular_price', 'mytheme_wholesale_price', 10, 2 );

function mytheme_wholesale_price( $price, $product ) {
    if ( ! is_user_logged_in() ) return $price;

    $user = wp_get_current_user();
    if ( in_array( 'wholesale', $user->roles, true ) ) {
        $discount = 0.10; // 10%
        $price    = $price * ( 1 - $discount );
    }

    return $price;
}
```

---

## 8. User & Authentication Hooks

### 8.1. Xử lý đăng ký và đăng nhập

```php
/**
 * Hook: user_register - Ngay sau khi user mới được tạo
 * Hook: profile_update - Khi user cập nhật profile
 * Hook: wp_login - Khi user đăng nhập thành công
 * Hook: wp_logout - Khi user đăng xuất
 */

// Gửi email chào mừng khi user mới đăng ký
add_action( 'user_register', 'mytheme_welcome_new_user', 10, 2 );

function mytheme_welcome_new_user( $user_id, $userdata ) {
    $user = get_userdata( $user_id );

    $subject = sprintf(
        __( 'Chào mừng bạn đến với %s!', 'mytheme' ),
        get_bloginfo( 'name' )
    );

    $message = sprintf(
        "Xin chào %s,\n\n" .
        "Cảm ơn bạn đã đăng ký tài khoản tại %s.\n\n" .
        "Tên đăng nhập: %s\n" .
        "Email: %s\n\n" .
        "Đăng nhập tại: %s\n\n" .
        "Trân trọng,\n%s",
        $user->display_name,
        get_bloginfo( 'name' ),
        $user->user_login,
        $user->user_email,
        wp_login_url(),
        get_bloginfo( 'name' )
    );

    wp_mail( $user->user_email, $subject, $message );

    // Set default meta cho user mới
    update_user_meta( $user_id, '_welcome_dismissed', false );
    update_user_meta( $user_id, '_loyalty_points', 100 ); // Tặng 100 điểm chào mừng
    update_user_meta( $user_id, '_registered_source', sanitize_text_field( $_SERVER['HTTP_REFERER'] ?? '' ) );
}

// Ghi log đăng nhập
add_action( 'wp_login', 'mytheme_log_user_login', 10, 2 );

function mytheme_log_user_login( $user_login, $user ) {
    update_user_meta( $user->ID, '_last_login', current_time( 'mysql' ) );
    update_user_meta( $user->ID, '_login_count',
        (int) get_user_meta( $user->ID, '_login_count', true ) + 1
    );
    update_user_meta( $user->ID, '_last_login_ip',
        sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' )
    );
}

// Redirect sau khi đăng xuất
add_action( 'wp_logout', 'mytheme_redirect_after_logout' );

function mytheme_redirect_after_logout() {
    wp_safe_redirect( home_url( '/da-dang-xuat/' ) );
    exit;
}

// Thêm field tùy chỉnh vào trang profile
add_action( 'show_user_profile', 'mytheme_extra_profile_fields' );
add_action( 'edit_user_profile', 'mytheme_extra_profile_fields' );

function mytheme_extra_profile_fields( $user ) {
    ?>
    <h3><?php esc_html_e( 'Thông tin bổ sung', 'mytheme' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="phone"><?php esc_html_e( 'Số điện thoại', 'mytheme' ); ?></label></th>
            <td>
                <input type="tel" name="phone" id="phone"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, '_phone', true ) ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="company"><?php esc_html_e( 'Công ty', 'mytheme' ); ?></label></th>
            <td>
                <input type="text" name="company" id="company"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, '_company', true ) ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Thống kê', 'mytheme' ); ?></th>
            <td>
                <p><?php printf(
                    esc_html__( 'Đăng nhập lần cuối: %s', 'mytheme' ),
                    esc_html( get_user_meta( $user->ID, '_last_login', true ) ?: 'Chưa có' )
                ); ?></p>
                <p><?php printf(
                    esc_html__( 'Số lần đăng nhập: %d', 'mytheme' ),
                    (int) get_user_meta( $user->ID, '_login_count', true )
                ); ?></p>
                <p><?php printf(
                    esc_html__( 'Điểm thưởng: %d', 'mytheme' ),
                    (int) get_user_meta( $user->ID, '_loyalty_points', true )
                ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// Lưu extra profile fields
add_action( 'personal_options_update', 'mytheme_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'mytheme_save_extra_profile_fields' );

function mytheme_save_extra_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }

    update_user_meta( $user_id, '_phone', sanitize_text_field( $_POST['phone'] ?? '' ) );
    update_user_meta( $user_id, '_company', sanitize_text_field( $_POST['company'] ?? '' ) );
}
```

---

## 9. Dashboard & Admin Hooks

### 9.1. Tùy chỉnh Dashboard

```php
/**
 * Tùy chỉnh trang Dashboard admin
 */

// Thêm Dashboard Widget thống kê
add_action( 'wp_dashboard_setup', 'mytheme_dashboard_widgets' );

function mytheme_dashboard_widgets() {
    // Thêm widget
    wp_add_dashboard_widget(
        'mytheme_stats_widget',
        __( 'Thống kê nhanh', 'mytheme' ),
        'mytheme_stats_widget_callback'
    );

    // Xóa các widget mặc định không cần
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );    // Quick Draft
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );         // WordPress News
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );      // Activity
}

function mytheme_stats_widget_callback() {
    $posts    = wp_count_posts();
    $pages    = wp_count_posts( 'page' );
    $comments = wp_count_comments();
    $users    = count_users();

    echo '<div style="display:grid; grid-template-columns:repeat(2,1fr); gap:15px;">';

    $stats = array(
        array( 'label' => 'Bài viết',    'count' => $posts->publish,    'icon' => '📝', 'url' => 'edit.php' ),
        array( 'label' => 'Trang',       'count' => $pages->publish,    'icon' => '📄', 'url' => 'edit.php?post_type=page' ),
        array( 'label' => 'Bình luận',   'count' => $comments->approved,'icon' => '💬', 'url' => 'edit-comments.php' ),
        array( 'label' => 'Thành viên',  'count' => $users['total_users'], 'icon' => '👥', 'url' => 'users.php' ),
    );

    foreach ( $stats as $stat ) {
        printf(
            '<div style="background:#f0f0f1; padding:15px; border-radius:6px; text-align:center;">
                <div style="font-size:24px;">%s</div>
                <div style="font-size:28px; font-weight:bold; color:#1d2327;">%s</div>
                <div><a href="%s">%s</a></div>
            </div>',
            $stat['icon'],
            number_format_i18n( $stat['count'] ),
            esc_url( admin_url( $stat['url'] ) ),
            esc_html( $stat['label'] )
        );
    }

    echo '</div>';

    // Dung lượng uploads
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['basedir'];
    if ( is_dir( $upload_path ) ) {
        $size = 0;
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $upload_path ) ) as $file ) {
            if ( $file->isFile() ) {
                $size += $file->getSize();
            }
        }
        printf(
            '<p style="margin-top:15px; color:#666;">Dung lượng uploads: <strong>%s</strong></p>',
            size_format( $size )
        );
    }
}

// Thay đổi footer text admin
add_filter( 'admin_footer_text', 'mytheme_admin_footer_text' );

function mytheme_admin_footer_text( $text ) {
    return sprintf(
        '%s | Phiên bản WordPress %s | PHP %s',
        esc_html( get_bloginfo( 'name' ) ),
        get_bloginfo( 'version' ),
        phpversion()
    );
}

// Thêm thông tin vào "At a Glance" widget
add_filter( 'dashboard_glance_items', 'mytheme_dashboard_glance_items' );

function mytheme_dashboard_glance_items( $items ) {
    // Đếm CPT "Portfolio"
    $portfolio_count = wp_count_posts( 'portfolio' );
    if ( $portfolio_count && isset( $portfolio_count->publish ) && $portfolio_count->publish > 0 ) {
        $items[] = sprintf(
            '<a href="%s" class="portfolio-count">%s Portfolio</a>',
            esc_url( admin_url( 'edit.php?post_type=portfolio' ) ),
            number_format_i18n( $portfolio_count->publish )
        );
    }

    // Đếm contact messages (custom table)
    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
        $msg_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'new'" );
        if ( $msg_count > 0 ) {
            $items[] = sprintf(
                '<a href="%s" style="color:#d63638">%d tin nhắn mới</a>',
                esc_url( admin_url( 'admin.php?page=contact-manager' ) ),
                $msg_count
            );
        }
    }

    return $items;
}
```

---

### 9.2. Thêm Admin Bar menu và Admin notices

```php
/**
 * Tùy chỉnh Admin Bar (thanh công cụ trên cùng)
 */
add_action( 'admin_bar_menu', 'mytheme_admin_bar_links', 999 );

function mytheme_admin_bar_links( $admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Thêm nhóm menu
    $admin_bar->add_node( array(
        'id'    => 'mytheme-shortcuts',
        'title' => '⚡ Truy cập nhanh',
    ) );

    $shortcuts = array(
        array( 'id' => 'new-post',    'title' => 'Viết bài mới',     'url' => admin_url( 'post-new.php' ) ),
        array( 'id' => 'all-pages',   'title' => 'Tất cả trang',     'url' => admin_url( 'edit.php?post_type=page' ) ),
        array( 'id' => 'menus',       'title' => 'Quản lý menu',     'url' => admin_url( 'nav-menus.php' ) ),
        array( 'id' => 'widgets',     'title' => 'Quản lý widgets',  'url' => admin_url( 'widgets.php' ) ),
        array( 'id' => 'customizer',  'title' => 'Tùy biến giao diện', 'url' => admin_url( 'customize.php' ) ),
    );

    foreach ( $shortcuts as $shortcut ) {
        $admin_bar->add_node( array(
            'parent' => 'mytheme-shortcuts',
            'id'     => 'mytheme-' . $shortcut['id'],
            'title'  => $shortcut['title'],
            'href'   => $shortcut['url'],
        ) );
    }
}

/**
 * Admin Notices thông minh
 */
add_action( 'admin_notices', 'mytheme_smart_admin_notices' );

function mytheme_smart_admin_notices() {
    // Cảnh báo nếu chưa set timezone
    if ( get_option( 'timezone_string' ) === '' && get_option( 'gmt_offset' ) == 0 ) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>⚠️ Chưa thiết lập múi giờ!</strong> ';
        printf(
            'Hãy vào <a href="%s">Cài đặt → Tổng quan</a> để thiết lập múi giờ chính xác.',
            esc_url( admin_url( 'options-general.php' ) )
        );
        echo '</p></div>';
    }

    // Nhắc nhở nếu chưa set permalink
    if ( get_option( 'permalink_structure' ) === '' ) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>🔗 Permalink đang ở chế độ mặc định!</strong> ';
        printf(
            'Hãy vào <a href="%s">Cài đặt → Đường dẫn tĩnh</a> để thiết lập URL đẹp.',
            esc_url( admin_url( 'options-permalink.php' ) )
        );
        echo '</p></div>';
    }

    // Cảnh báo nếu debug mode bật trên production
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! defined( 'WP_DEBUG_LOG' ) ) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>🐛 WP_DEBUG đang bật!</strong> ';
        echo 'Hãy tắt debug mode trên môi trường production. Thêm <code>define("WP_DEBUG", false);</code> vào wp-config.php';
        echo '</p></div>';
    }
}
```

---

## 10. Performance & Optimization Hooks

### 10.1. Tối ưu hiệu năng với hooks

```php
/**
 * Các hooks giúp tăng hiệu năng WordPress
 */

// Xóa các thẻ meta không cần thiết khỏi <head>
add_action( 'init', 'mytheme_clean_head' );

function mytheme_clean_head() {
    // Xóa generator meta (bảo mật: ẩn phiên bản WP)
    remove_action( 'wp_head', 'wp_generator' );

    // Xóa RSD link (dùng cho XML-RPC editor)
    remove_action( 'wp_head', 'rsd_link' );

    // Xóa Windows Live Writer link
    remove_action( 'wp_head', 'wlwmanifest_link' );

    // Xóa shortlink
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );

    // Xóa REST API link (nếu không dùng frontend)
    // remove_action( 'wp_head', 'rest_output_link_wp_head' );

    // Xóa oEmbed links
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

    // Xóa emoji scripts (tiết kiệm ~15KB)
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
}

// Thêm async/defer cho scripts
add_filter( 'script_loader_tag', 'mytheme_async_defer_scripts', 10, 3 );

function mytheme_async_defer_scripts( $tag, $handle, $src ) {
    // Danh sách scripts cần defer (load sau khi parse HTML)
    $defer_scripts = array( 'mytheme-main', 'mytheme-navigation', 'comment-reply' );

    if ( in_array( $handle, $defer_scripts, true ) ) {
        return str_replace( ' src', ' defer src', $tag );
    }

    // Danh sách scripts cần async (load song song, chạy ngay khi xong)
    $async_scripts = array( 'google-analytics', 'facebook-pixel' );

    if ( in_array( $handle, $async_scripts, true ) ) {
        return str_replace( ' src', ' async src', $tag );
    }

    return $tag;
}

// Preload fonts và critical resources
add_action( 'wp_head', 'mytheme_preload_resources', 1 );

function mytheme_preload_resources() {
    // Preload font chính
    printf(
        '<link rel="preload" href="%s/assets/fonts/Inter-Regular.woff2" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url( get_template_directory_uri() )
    );

    // Preconnect đến Google Fonts
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

    // DNS prefetch cho CDN / analytics
    echo '<link rel="dns-prefetch" href="//www.google-analytics.com">' . "\n";
}

// Lazy load images (WP 5.5+ tự động có, nhưng có thể tùy chỉnh)
add_filter( 'wp_lazy_loading_enabled', '__return_true' );

// Tắt query string trên static resources (giúp cache tốt hơn)
add_filter( 'script_loader_src', 'mytheme_remove_script_version', 999 );
add_filter( 'style_loader_src', 'mytheme_remove_script_version', 999 );

function mytheme_remove_script_version( $src ) {
    // Chỉ xóa trên frontend, giữ nguyên trong admin
    if ( is_admin() ) {
        return $src;
    }
    return remove_query_arg( 'ver', $src );
}

// Giới hạn số revision lưu trữ
add_filter( 'wp_revisions_to_keep', function( $num, $post ) {
    return 5; // Chỉ giữ 5 revisions thay vì unlimited
}, 10, 2 );

// Cache kết quả menu (tránh query mỗi page load)
add_filter( 'pre_wp_nav_menu', 'mytheme_cache_nav_menu', 10, 2 );

function mytheme_cache_nav_menu( $output, $args ) {
    $cache_key = 'nav_menu_' . md5( serialize( $args ) );
    $cached    = get_transient( $cache_key );

    if ( $cached !== false && ! is_customize_preview() ) {
        return $cached;
    }

    return $output; // Trả null để WP render bình thường
}

// Lưu cache sau khi menu render
add_filter( 'wp_nav_menu', 'mytheme_save_nav_menu_cache', 10, 2 );

function mytheme_save_nav_menu_cache( $nav_menu, $args ) {
    if ( is_customize_preview() ) {
        return $nav_menu;
    }

    $cache_key = 'nav_menu_' . md5( serialize( $args ) );
    set_transient( $cache_key, $nav_menu, HOUR_IN_SECONDS );

    return $nav_menu;
}

// Xóa cache menu khi menu được cập nhật
add_action( 'wp_update_nav_menu', function() {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nav_menu_%' OR option_name LIKE '_transient_timeout_nav_menu_%'"
    );
} );
```

---

## 11. REST API Hooks Nâng Cao

### 11.1. Tùy chỉnh REST API responses

```php
/**
 * Hooks mở rộng WordPress REST API
 */

// Thêm custom fields vào REST API response cho posts
add_action( 'rest_api_init', 'mytheme_register_rest_fields' );

function mytheme_register_rest_fields() {
    // Thêm field "reading_time" vào posts
    register_rest_field( 'post', 'reading_time', array(
        'get_callback' => function( $post ) {
            $content    = strip_tags( $post['content']['rendered'] );
            $word_count = str_word_count( $content );
            $minutes    = max( 1, ceil( $word_count / 200 ) ); // 200 từ/phút
            return sprintf( '%d phút đọc', $minutes );
        },
        'schema' => array(
            'description' => 'Thời gian đọc ước tính',
            'type'        => 'string',
        ),
    ) );

    // Thêm featured image URL (thay vì chỉ có ID)
    register_rest_field( 'post', 'featured_image_url', array(
        'get_callback' => function( $post ) {
            $image_id = $post['featured_media'];
            if ( ! $image_id ) return null;

            return array(
                'thumbnail' => wp_get_attachment_image_url( $image_id, 'thumbnail' ),
                'medium'    => wp_get_attachment_image_url( $image_id, 'medium' ),
                'large'     => wp_get_attachment_image_url( $image_id, 'large' ),
                'full'      => wp_get_attachment_image_url( $image_id, 'full' ),
            );
        },
        'schema' => array(
            'description' => 'URLs ảnh đại diện các kích thước',
            'type'        => 'object',
        ),
    ) );

    // Thêm author info đầy đủ
    register_rest_field( 'post', 'author_info', array(
        'get_callback' => function( $post ) {
            $author = get_userdata( $post['author'] );
            if ( ! $author ) return null;

            return array(
                'name'   => $author->display_name,
                'avatar' => get_avatar_url( $author->ID, array( 'size' => 96 ) ),
                'bio'    => $author->description,
                'url'    => get_author_posts_url( $author->ID ),
            );
        },
    ) );
}

// Rate limiting cho REST API
add_filter( 'rest_pre_dispatch', 'mytheme_rest_rate_limit', 10, 3 );

function mytheme_rest_rate_limit( $result, $server, $request ) {
    // Chỉ limit cho requests không có auth
    if ( is_user_logged_in() ) {
        return $result;
    }

    $ip        = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $cache_key = 'rest_rate_' . md5( $ip );
    $requests  = (int) get_transient( $cache_key );

    if ( $requests > 60 ) { // Tối đa 60 requests / phút
        return new WP_Error(
            'rate_limit_exceeded',
            __( 'Quá nhiều request. Vui lòng thử lại sau 1 phút.', 'mytheme' ),
            array( 'status' => 429 )
        );
    }

    set_transient( $cache_key, $requests + 1, MINUTE_IN_SECONDS );

    return $result;
}

// Thêm CORS headers cho REST API (cho phép frontend từ domain khác)
add_action( 'rest_api_init', function() {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function( $value ) {
        $allowed_origins = array(
            'https://app.yoursite.com',
            'https://admin.yoursite.com',
        );

        $origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
        if ( in_array( $origin, $allowed_origins, true ) ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
            header( 'Access-Control-Allow-Credentials: true' );
        }

        return $value;
    } );
} );
```

---

## 12. Email Hooks - wp_mail & SMTP

### 12.1. Thay đổi "From" Email và Tên

```php
/**
 * Hook: wp_mail_from (Filter) - Thay đổi email gửi đi
 * Hook: wp_mail_from_name (Filter) - Thay đổi tên người gửi
 *
 * Mặc định WordPress gửi từ: wordpress@yourdomain.com
 * với tên "WordPress" → cần thay đổi cho chuyên nghiệp.
 */

// Thay đổi email gửi đi
add_filter( 'wp_mail_from', function( $email ) {
    return 'noreply@yoursite.com';
} );

// Thay đổi tên người gửi
add_filter( 'wp_mail_from_name', function( $name ) {
    return get_bloginfo( 'name' ); // Dùng tên website
} );
```

### 12.2. Cấu hình SMTP với phpmailer_init

```php
/**
 * Hook: phpmailer_init (Action)
 * Fires: Sau khi PHPMailer được khởi tạo, trước khi gửi email
 * Dùng: Cấu hình SMTP thay vì dùng PHP mail() mặc định
 *
 * So sánh Laravel: Giống config/mail.php
 * MAIL_MAILER=smtp
 * MAIL_HOST=smtp.gmail.com
 * MAIL_PORT=587
 */
add_action( 'phpmailer_init', 'mytheme_configure_smtp' );

function mytheme_configure_smtp( $phpmailer ) {
    // Lấy cấu hình từ options hoặc constants
    $phpmailer->isSMTP();
    $phpmailer->Host       = defined( 'SMTP_HOST' ) ? SMTP_HOST : 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = defined( 'SMTP_PORT' ) ? SMTP_PORT : 587;
    $phpmailer->Username   = defined( 'SMTP_USER' ) ? SMTP_USER : '';
    $phpmailer->Password   = defined( 'SMTP_PASS' ) ? SMTP_PASS : '';
    $phpmailer->SMTPSecure = defined( 'SMTP_SECURE' ) ? SMTP_SECURE : 'tls'; // 'tls' hoặc 'ssl'
    $phpmailer->CharSet    = 'UTF-8';
}

// Trong wp-config.php:
// define( 'SMTP_HOST', 'smtp.gmail.com' );
// define( 'SMTP_PORT', 587 );
// define( 'SMTP_USER', 'your@gmail.com' );
// define( 'SMTP_PASS', 'app-password-here' );
// define( 'SMTP_SECURE', 'tls' );
```

### 12.3. Gửi Email HTML với Template

```php
/**
 * Hàm tiện ích gửi email HTML có template đẹp
 *
 * So sánh Laravel: Giống Mail::send() với Blade template
 * Mail::to($user)->send(new OrderConfirmed($order));
 */
function mytheme_send_html_email( $to, $subject, $heading, $body, $cta_url = '', $cta_text = '' ) {
    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url();

    $html = '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0; padding:0; background:#f4f4f4; font-family:Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto; background:#fff;">
            <!-- Header -->
            <tr>
                <td style="background:#2271b1; padding:20px; text-align:center;">
                    <h1 style="color:#fff; margin:0; font-size:24px;">'
                        . esc_html( $site_name ) .
                    '</h1>
                </td>
            </tr>
            <!-- Nội dung -->
            <tr>
                <td style="padding:30px;">
                    <h2 style="color:#1d2327; margin-top:0;">' . esc_html( $heading ) . '</h2>
                    <div style="color:#50575e; line-height:1.6;">' . wp_kses_post( $body ) . '</div>';

    // Nút CTA (Call To Action)
    if ( $cta_url && $cta_text ) {
        $html .= '<p style="text-align:center; margin-top:30px;">
                    <a href="' . esc_url( $cta_url ) . '"
                       style="display:inline-block; background:#2271b1; color:#fff;
                              padding:12px 30px; text-decoration:none; border-radius:4px;
                              font-weight:bold;">'
                        . esc_html( $cta_text ) .
                    '</a>
                  </p>';
    }

    $html .= '  </td>
            </tr>
            <!-- Footer -->
            <tr>
                <td style="background:#f0f0f1; padding:15px; text-align:center; color:#646970; font-size:12px;">
                    <p>&copy; ' . date( 'Y' ) . ' ' . esc_html( $site_name ) . ' |
                       <a href="' . esc_url( $site_url ) . '">' . esc_html( $site_url ) . '</a></p>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    // Headers: Set Content-Type HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url( $site_url, PHP_URL_HOST ) . '>',
    );

    return wp_mail( $to, $subject, $html, $headers );
}

// === Sử dụng ===
// mytheme_send_html_email(
//     'user@example.com',
//     'Đơn hàng đã được xác nhận',
//     'Xác Nhận Đơn Hàng!',
//     '<p>Cảm ơn bạn đã đặt hàng #1234.</p><p>Chúng tôi sẽ xử lý sớm nhất.</p>',
//     home_url( '/my-account/orders/' ),
//     'Xem Đơn Hàng'
// );
```

### 12.4. Hook wp_mail - Filter toàn bộ email gửi đi

```php
/**
 * Hook: wp_mail (Filter)
 * Fires: Trước khi email được gửi
 * Dùng: BCC admin, thêm footer, log emails
 *
 * $args chứa: 'to', 'subject', 'message', 'headers', 'attachments'
 */
add_filter( 'wp_mail', 'mytheme_filter_all_emails' );

function mytheme_filter_all_emails( $args ) {
    // BCC admin mọi email gửi đi
    if ( ! is_array( $args['headers'] ) ) {
        $args['headers'] = array_filter( explode( "\n", $args['headers'] ) );
    }
    $args['headers'][] = 'Bcc: admin@yoursite.com';

    // Thêm footer vào tất cả email
    $args['message'] .= "\n\n---\n" . sprintf(
        'Email này được gửi từ %s (%s)',
        get_bloginfo( 'name' ),
        home_url()
    );

    return $args; // Filter PHẢI return!
}
```

### 12.5. Xử lý khi gửi email thất bại

```php
/**
 * Hook: wp_mail_failed (Action)
 * Fires: Khi wp_mail() gửi thất bại
 * Dùng: Logging lỗi, retry logic, thông báo admin
 */
add_action( 'wp_mail_failed', 'mytheme_handle_mail_failure' );

function mytheme_handle_mail_failure( $wp_error ) {
    // Log lỗi
    error_log( sprintf(
        '[Email FAILED] Lỗi: %s | Data: %s',
        $wp_error->get_error_message(),
        wp_json_encode( $wp_error->get_error_data() )
    ) );

    // Lưu email thất bại để retry sau
    $failed = get_option( 'mytheme_failed_emails', array() );
    $failed[] = array(
        'error'     => $wp_error->get_error_message(),
        'data'      => $wp_error->get_error_data(),
        'timestamp' => current_time( 'mysql' ),
    );
    // Giữ tối đa 100 bản ghi
    $failed = array_slice( $failed, -100 );
    update_option( 'mytheme_failed_emails', $failed );
}
```

### 12.6. Log tất cả email gửi đi

```php
/**
 * Theo dõi mọi email gửi từ WordPress
 * Hữu ích cho debugging và audit
 */
add_filter( 'wp_mail', 'mytheme_log_outgoing_emails', PHP_INT_MAX );

function mytheme_log_outgoing_emails( $args ) {
    error_log( sprintf(
        '[Email Sent] To: %s | Subject: %s | Time: %s',
        is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : $args['to'],
        $args['subject'],
        current_time( 'mysql' )
    ) );

    return $args; // Luôn return - đây là filter!
}
```

**Bảng tổng hợp Email Hooks:**

| Hook | Loại | Mô tả | So sánh Laravel |
|------|------|-------|-----------------|
| `wp_mail_from` | Filter | Thay đổi email "From" | `config('mail.from.address')` |
| `wp_mail_from_name` | Filter | Thay đổi tên "From" | `config('mail.from.name')` |
| `wp_mail_content_type` | Filter | Set HTML/plain text | Mailable `$format` |
| `wp_mail` | Filter | Filter toàn bộ params | `Mail::beforeSending()` |
| `phpmailer_init` | Action | Cấu hình SMTP | `config/mail.php` |
| `wp_mail_failed` | Action | Xử lý lỗi gửi email | `Mail::failures()` |

---

## Tổng Kết Mở Rộng

| Nhóm Hooks | Ví dụ tiêu biểu | Dùng khi |
|------------|-----------------|----------|
| **WooCommerce** | `woocommerce_before_cart`, `woocommerce_checkout_process`, `woocommerce_order_status_changed` | Tùy chỉnh shop, giỏ hàng, thanh toán, đơn hàng |
| **User/Auth** | `user_register`, `wp_login`, `show_user_profile` | Quản lý user, đăng ký, đăng nhập, profile |
| **Dashboard** | `wp_dashboard_setup`, `admin_bar_menu`, `admin_notices`, `dashboard_glance_items` | Tùy chỉnh admin dashboard, thanh công cụ |
| **Performance** | `script_loader_tag`, `wp_head` cleanup, `wp_revisions_to_keep` | Tối ưu tốc độ, giảm requests, cache |
| **REST API** | `rest_api_init`, `rest_pre_dispatch`, `register_rest_field` | Mở rộng API, thêm fields, rate limiting, CORS |
| **Email** | `wp_mail`, `wp_mail_from`, `phpmailer_init`, `wp_mail_failed` | Cấu hình SMTP, HTML email, logging, xử lý lỗi |

---

[← Quay lại: Hooks nâng cao](./07-hooks-nang-cao.md) | [↑ Mục lục Hooks](./index.md) | [→ Tiếp: Hooks chuyên đề](./09-hooks-chuyen-de.md)
