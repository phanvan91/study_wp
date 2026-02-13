# Hooks Nâng Cao - Advanced Techniques

## Mục Lục

1. [Hook Priority Nâng Cao](#1-hook-priority-nâng-cao)
2. [Hooks với OOP](#2-hooks-với-oop)
3. [Static Methods vs Instance Methods](#3-static-methods-vs-instance-methods)
4. [Removing Hooks từ Class Methods](#4-removing-hooks-từ-class-methods)
5. [Hooks và Performance](#5-hooks-và-performance)
6. [WP_Hook Class - Internal Implementation](#6-wp_hook-class---internal-implementation)
7. [Global $wp_filter Array](#7-global-wp_filter-array)
8. [Hooks trong Multisite](#8-hooks-trong-multisite)
9. [Hooks và Caching](#9-hooks-và-caching)
10. [Dynamic Hooks](#10-dynamic-hooks)
11. [Hooks Testing với PHPUnit](#11-hooks-testing-với-phpunit)
12. [Common Pitfalls và Solutions](#12-common-pitfalls-và-solutions)
13. [Best Practices Nâng Cao](#13-best-practices-nâng-cao)

---

## 1. Hook Priority Nâng Cao

### Late Binding và Priority Strategies

```php
<?php
// === STRATEGY 1: Đảm bảo callback chạy ĐẦU TIÊN ===
// Dùng priority rất thấp
add_filter( 'the_content', 'my_first_content_filter', 1 );
function my_first_content_filter( $content ) {
    // Chạy trước tất cả filter khác
    // Hữu ích cho: security filters, input sanitization
    return wp_kses_post( $content );
}

// === STRATEGY 2: Đảm bảo callback chạy CUỐI CÙNG ===
// Dùng PHP_INT_MAX (giá trị int lớn nhất)
add_filter( 'the_content', 'my_final_content_filter', PHP_INT_MAX );
function my_final_content_filter( $content ) {
    // Chạy SAU TẤT CẢ filter khác
    // Hữu ích cho: output caching, final modifications, analytics tracking
    return $content;
}

// === STRATEGY 3: Chạy NGAY SAU một callback cụ thể ===
// Ví dụ: WordPress shortcode filter ở priority 11
// Bạn muốn chạy ngay sau shortcodes đã được parse
add_filter( 'the_content', 'my_after_shortcodes', 12 );
function my_after_shortcodes( $content ) {
    // Shortcodes đã được convert thành HTML
    // Có thể wrap output của shortcodes
    return $content;
}

// === STRATEGY 4: Late binding - Đăng ký hook trong hook khác ===
add_action( 'wp', 'my_late_binding' );
function my_late_binding() {
    // Tại thời điểm 'wp', đã biết đang xem trang gì
    // Chỉ đăng ký filter khi thực sự cần

    if ( is_singular( 'product' ) ) {
        // Chỉ filter the_content cho trang sản phẩm
        add_filter( 'the_content', 'my_product_content_filter' );
    }

    if ( is_author() ) {
        // Chỉ modify query cho trang author
        add_action( 'pre_get_posts', 'my_author_page_query' );
    }
}

function my_product_content_filter( $content ) {
    $product_id = get_the_ID();
    $price      = get_post_meta( $product_id, '_price', true );

    $price_html = '<div class="product-price">';
    $price_html .= '<strong>Giá: ' . number_format( floatval( $price ) ) . ' VNĐ</strong>';
    $price_html .= '</div>';

    return $price_html . $content;
}
```

### Priority với multiple callbacks

```php
<?php
/**
 * Ví dụ: Pipeline xử lý nội dung với priority rõ ràng
 *
 * Priority 1-9    : Security & Sanitization
 * Priority 10     : Default WordPress processing
 * Priority 11-20  : Content enhancement (TOC, related posts)
 * Priority 21-50  : Layout modifications (ads, widgets)
 * Priority 51-99  : Final adjustments
 * Priority 100+   : Caching, analytics
 */

// Priority 5: Xóa malicious code
add_filter( 'the_content', function( $content ) {
    // Xóa inline event handlers (onclick, onload, etc.)
    $content = preg_replace( '/\s+on\w+="[^"]*"/i', '', $content );
    return $content;
}, 5 );

// Priority 15: Thêm Table of Contents
add_filter( 'the_content', function( $content ) {
    // Thêm TOC sau khi content đã được processed bởi WordPress
    return my_generate_toc( $content );
}, 15 );

// Priority 25: Chèn quảng cáo
add_filter( 'the_content', function( $content ) {
    return my_insert_ads( $content );
}, 25 );

// Priority 100: Cache nội dung cuối cùng
add_filter( 'the_content', function( $content ) {
    // Cache nội dung đã xử lý hoàn chỉnh
    $cache_key = 'content_' . get_the_ID() . '_' . md5( $content );
    set_transient( $cache_key, $content, HOUR_IN_SECONDS );
    return $content;
}, 100 );
```

---

## 2. Hooks với OOP

### Class-based Hook Registration

```php
<?php
/**
 * Plugin sử dụng OOP pattern cho hooks
 */
class My_Advanced_Plugin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Plugin settings
     */
    private $settings = array();

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - đăng ký tất cả hooks
     */
    private function __construct() {
        $this->settings = get_option( 'my_plugin_settings', array() );

        // === ACTION HOOKS ===

        // Instance method: dùng $this
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 3 );

        // === FILTER HOOKS ===
        add_filter( 'the_content', array( $this, 'filter_content' ) );
        add_filter( 'the_title', array( $this, 'filter_title' ), 10, 2 );

        // === AJAX HOOKS ===
        add_action( 'wp_ajax_my_plugin_action', array( $this, 'handle_ajax' ) );
        add_action( 'wp_ajax_nopriv_my_plugin_action', array( $this, 'handle_ajax' ) );
    }

    /**
     * Init hook - đăng ký CPT, taxonomy
     */
    public function init() {
        register_post_type( 'my_item', array(
            'labels' => array(
                'name' => 'Items',
            ),
            'public'       => true,
            'show_in_rest' => true,
        ));

        // Load text domain
        load_plugin_textdomain(
            'my-plugin',
            false,
            dirname( plugin_basename( MY_PLUGIN_FILE ) ) . '/languages'
        );
    }

    /**
     * Filter nội dung
     * $this->settings có thể truy cập vì đây là instance method
     */
    public function filter_content( $content ) {
        if ( ! is_single() || is_admin() ) {
            return $content;
        }

        // Truy cập settings dễ dàng trong OOP
        if ( ! empty( $this->settings['show_author_box'] ) ) {
            $content .= $this->render_author_box();
        }

        if ( ! empty( $this->settings['show_share_buttons'] ) ) {
            $content .= $this->render_share_buttons();
        }

        return $content;
    }

    public function filter_title( $title, $post_id ) {
        return $title;
    }

    /**
     * Render author box (private method - chỉ dùng nội bộ)
     */
    private function render_author_box() {
        $author_id   = get_the_author_meta( 'ID' );
        $author_name = get_the_author_meta( 'display_name' );

        return sprintf(
            '<div class="my-plugin-author-box"><strong>%s</strong> %s</div>',
            esc_html( $author_name ),
            esc_html( get_the_author_meta( 'description' ) )
        );
    }

    private function render_share_buttons() {
        $url   = urlencode( get_permalink() );
        $title = urlencode( get_the_title() );

        return '<div class="my-plugin-share">'
            . '<a href="https://facebook.com/sharer.php?u=' . $url . '">Facebook</a> '
            . '<a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '">Twitter</a>'
            . '</div>';
    }

    public function register_admin_menu() {
        add_menu_page(
            'My Plugin',
            'My Plugin',
            'manage_options',
            'my-plugin',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-generic'
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>My Plugin Settings</h1></div>';
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_my-plugin' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'my-plugin-admin', MY_PLUGIN_URL . 'css/admin.css', array(), MY_PLUGIN_VERSION );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style( 'my-plugin', MY_PLUGIN_URL . 'css/frontend.css', array(), MY_PLUGIN_VERSION );
    }

    public function handle_save_post( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        // Save logic...
    }

    public function handle_ajax() {
        check_ajax_referer( 'my_plugin_nonce', 'nonce' );
        wp_send_json_success( array( 'message' => 'OK' ) );
    }
}

// Init
add_action( 'plugins_loaded', function() {
    My_Advanced_Plugin::get_instance();
});
```

---

## 3. Static Methods vs Instance Methods

### So sánh

```php
<?php
class My_Example {

    private $config = array();

    public function __construct() {
        $this->config = get_option( 'my_config', array() );
    }

    // === INSTANCE METHOD ===
    // Ưu điểm: Truy cập được $this, properties, private methods
    // Nhược điểm: Cần instance để add/remove hook
    public function instance_callback( $content ) {
        // Có thể dùng $this->config
        if ( ! empty( $this->config['enable_feature'] ) ) {
            $content .= '<p>Feature enabled!</p>';
        }
        return $content;
    }

    // === STATIC METHOD ===
    // Ưu điểm: Dễ add/remove (không cần instance)
    // Nhược điểm: Không có $this, phải dùng static properties
    public static function static_callback( $content ) {
        // KHÔNG có $this ở đây
        // Phải lấy data từ static properties hoặc gọi lại get_option()
        $config = get_option( 'my_config', array() );
        if ( ! empty( $config['enable_feature'] ) ) {
            $content .= '<p>Feature enabled!</p>';
        }
        return $content;
    }
}

// === ĐĂNG KÝ HOOKS ===

// Instance method: cần instance
$instance = new My_Example();
add_filter( 'the_content', array( $instance, 'instance_callback' ) );

// Static method: không cần instance
add_filter( 'the_content', array( 'My_Example', 'static_callback' ) );
// Hoặc:
add_filter( 'the_content', 'My_Example::static_callback' );

// === REMOVE HOOKS ===

// Instance method: CẦN CÙNG INSTANCE để remove
remove_filter( 'the_content', array( $instance, 'instance_callback' ) );

// Static method: DỄ remove (không cần instance)
remove_filter( 'the_content', array( 'My_Example', 'static_callback' ) );
```

### Best Practice: Dùng Singleton pattern

```php
<?php
class My_Plugin_Controller {

    private static $instance = null;
    private $data = array();

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->data = get_option( 'my_plugin_data', array() );

        // Đăng ký hooks với instance methods
        add_filter( 'the_content', array( $this, 'filter_content' ) );
        add_action( 'wp_footer', array( $this, 'render_footer' ) );
    }

    public function filter_content( $content ) {
        // Có $this->data
        return $content;
    }

    public function render_footer() {
        echo '<!-- Plugin footer -->';
    }
}

// Init
My_Plugin_Controller::get_instance();

// Bên ngoài có thể remove hook qua singleton:
$controller = My_Plugin_Controller::get_instance();
remove_filter( 'the_content', array( $controller, 'filter_content' ) );
```

---

## 4. Removing Hooks từ Class Methods

### Thách thức

```php
<?php
// Vấn đề: Plugin A add hook bằng instance method
// File: plugin-a.php
class Plugin_A {
    public function __construct() {
        add_action( 'wp_head', array( $this, 'add_tracking' ) );
    }

    public function add_tracking() {
        echo '<script>/* tracking code */</script>';
    }
}
$plugin_a = new Plugin_A(); // Instance được tạo nhưng không lưu globally

// Plugin B muốn remove hook đó:
// remove_action( 'wp_head', array( ???, 'add_tracking' ) );
// KHÔNG CÓ reference đến instance $plugin_a!
```

### Giải pháp 1: Duyệt qua $wp_filter

```php
<?php
/**
 * Helper function: Remove hook từ class method khi không có instance
 *
 * @param string $hook_name   Tên hook
 * @param string $class_name  Tên class
 * @param string $method_name Tên method
 * @param int    $priority    Priority (phải chính xác)
 * @return bool  True nếu remove thành công
 */
function my_remove_class_hook( $hook_name, $class_name, $method_name, $priority = 10 ) {
    global $wp_filter;

    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        return false;
    }

    // Duyệt qua tất cả callbacks ở priority
    if ( isset( $wp_filter[ $hook_name ]->callbacks[ $priority ] ) ) {
        foreach ( $wp_filter[ $hook_name ]->callbacks[ $priority ] as $key => $callback_data ) {
            $callback = $callback_data['function'];

            // Kiểm tra xem callback có phải class method không
            if ( is_array( $callback ) && count( $callback ) === 2 ) {
                $object = $callback[0];
                $method = $callback[1];

                // So sánh class name và method name
                if ( is_object( $object ) && get_class( $object ) === $class_name && $method === $method_name ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $key ] );
                    return true;
                }
            }
        }
    }

    return false;
}

// Sử dụng:
add_action( 'init', function() {
    my_remove_class_hook( 'wp_head', 'Plugin_A', 'add_tracking', 10 );
}, 99 );
```

### Giải pháp 2: Plugin cung cấp static accessor

```php
<?php
// Plugin viết tốt sẽ cung cấp cách remove hooks
class Plugin_B_Good {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_head', array( $this, 'add_tracking' ) );
    }

    public function add_tracking() {
        echo '<script>/* tracking */</script>';
    }
}

Plugin_B_Good::get_instance();

// Bây giờ có thể remove:
$instance = Plugin_B_Good::get_instance();
remove_action( 'wp_head', array( $instance, 'add_tracking' ) );
```

---

## 5. Hooks và Performance

### Đo lường impact của hooks

```php
<?php
/**
 * Plugin Name: Hook Performance Monitor
 * Description: Đo thời gian thực thi hooks
 * CHỈ DÙNG TRONG DEVELOPMENT
 */

class Hook_Performance_Monitor {

    private $hook_times = array();
    private $threshold_ms = 5; // Cảnh báo nếu hook chạy > 5ms

    public function __construct() {
        // Chỉ chạy khi có parameter debug
        if ( ! isset( $_GET['debug_performance'] ) ) {
            return;
        }

        add_action( 'all', array( $this, 'start_timer' ) );
        add_action( 'shutdown', array( $this, 'output_report' ), PHP_INT_MAX );
    }

    public function start_timer( $hook_name ) {
        // Bắt đầu đo thời gian
        if ( ! isset( $this->hook_times[ $hook_name ] ) ) {
            $this->hook_times[ $hook_name ] = array(
                'start'      => microtime( true ),
                'total_time' => 0,
                'calls'      => 0,
            );
        } else {
            $this->hook_times[ $hook_name ]['start'] = microtime( true );
        }

        $this->hook_times[ $hook_name ]['calls']++;

        // Đăng ký callback để đo thời gian kết thúc
        add_action( $hook_name, function() use ( $hook_name ) {
            if ( isset( $this->hook_times[ $hook_name ]['start'] ) ) {
                $elapsed = microtime( true ) - $this->hook_times[ $hook_name ]['start'];
                $this->hook_times[ $hook_name ]['total_time'] += $elapsed;
            }
        }, PHP_INT_MAX );
    }

    public function output_report() {
        // Sắp xếp theo tổng thời gian giảm dần
        uasort( $this->hook_times, function( $a, $b ) {
            return $b['total_time'] <=> $a['total_time'];
        });

        error_log( '=== HOOK PERFORMANCE REPORT ===' );
        error_log( sprintf( '%-40s %10s %8s %10s', 'Hook', 'Total (ms)', 'Calls', 'Avg (ms)' ) );
        error_log( str_repeat( '-', 72 ) );

        $count = 0;
        foreach ( $this->hook_times as $hook => $data ) {
            $total_ms = round( $data['total_time'] * 1000, 2 );
            $avg_ms   = $data['calls'] > 0 ? round( ( $data['total_time'] / $data['calls'] ) * 1000, 2 ) : 0;

            if ( $total_ms < 0.01 ) {
                continue; // Bỏ qua hooks quá nhanh
            }

            $warning = $total_ms > $this->threshold_ms ? ' [SLOW]' : '';

            error_log( sprintf(
                '%-40s %10s %8d %10s%s',
                substr( $hook, 0, 40 ),
                $total_ms,
                $data['calls'],
                $avg_ms,
                $warning
            ));

            $count++;
            if ( $count > 50 ) {
                break; // Chỉ hiện top 50
            }
        }
    }
}

// Chỉ chạy khi development
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    new Hook_Performance_Monitor();
}
```

### Tối ưu hook performance

```php
<?php
// === TỐI ƯU 1: Tránh query trong hooks chạy nhiều lần ===

// SAI: Query mỗi lần the_post chạy (N lần trong 1 page)
add_action( 'the_post', function( $post ) {
    $views = get_post_meta( $post->ID, '_views', true ); // Query mỗi lần!
});

// ĐÚNG: Cache kết quả hoặc dùng hook chạy 1 lần
add_action( 'wp', function() {
    if ( is_single() ) {
        // Chỉ chạy 1 lần, increment view count
        $post_id = get_queried_object_id();
        $views = (int) get_post_meta( $post_id, '_views', true );
        update_post_meta( $post_id, '_views', $views + 1 );
    }
});

// === TỐI ƯU 2: Dùng transients cho queries nặng ===
add_filter( 'the_content', function( $content ) {
    if ( ! is_single() ) {
        return $content;
    }

    // Cache related posts
    $cache_key     = 'related_posts_' . get_the_ID();
    $related_html  = get_transient( $cache_key );

    if ( false === $related_html ) {
        // Query nặng - chỉ chạy khi cache miss
        $related = get_posts( array(
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'post__not_in'   => array( get_the_ID() ),
            'category__in'   => wp_get_post_categories( get_the_ID() ),
        ));

        $related_html = '<div class="related-posts"><h3>Bài viết liên quan</h3><ul>';
        foreach ( $related as $post ) {
            $related_html .= '<li><a href="' . get_permalink( $post ) . '">' . esc_html( $post->post_title ) . '</a></li>';
        }
        $related_html .= '</ul></div>';

        set_transient( $cache_key, $related_html, HOUR_IN_SECONDS );
    }

    return $content . $related_html;
});

// Xóa cache khi có bài viết mới
add_action( 'save_post', function( $post_id ) {
    // Xóa tất cả related posts cache
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_related_posts_%'
         OR option_name LIKE '_transient_timeout_related_posts_%'"
    );
});

// === TỐI ƯU 3: Early return ===
add_filter( 'the_content', function( $content ) {
    // Early return nếu không phải context cần xử lý
    if ( is_admin() ) return $content;
    if ( ! is_single() ) return $content;
    if ( 'post' !== get_post_type() ) return $content;

    // Chỉ xử lý phức tạp khi thực sự cần
    return $content . my_expensive_computation();
});

// === TỐI ƯU 4: Lazy loading hooks ===
// Chỉ đăng ký hooks khi cần
add_action( 'template_redirect', function() {
    // Chỉ đăng ký filter nặng khi thực sự cần
    if ( is_singular( 'product' ) ) {
        add_filter( 'the_content', 'my_heavy_product_filter' );
    }
    // Các trang khác không bị ảnh hưởng performance
});
```

---

## 6. WP_Hook Class - Internal Implementation

### Cách WordPress quản lý hooks bên trong

```php
<?php
/**
 * WP_Hook là class quản lý một hook cụ thể (từ WordPress 4.7+)
 * Trước 4.7, WordPress dùng plain array
 *
 * Mỗi hook trong $wp_filter là một instance của WP_Hook
 */

// Cấu trúc bên trong WP_Hook:
// $wp_filter['the_content'] = WP_Hook Object (
//     [callbacks] => Array (
//         [5] => Array (        // Priority 5
//             ['hash1'] => Array (
//                 [function] => 'my_early_filter'
//                 [accepted_args] => 1
//             )
//         )
//         [10] => Array (       // Priority 10 (default)
//             ['hash2'] => Array (
//                 [function] => 'wpautop'        // WordPress core
//                 [accepted_args] => 1
//             )
//             ['hash3'] => Array (
//                 [function] => 'my_custom_filter'
//                 [accepted_args] => 1
//             )
//         )
//         [20] => Array (       // Priority 20
//             ['hash4'] => Array (
//                 [function] => Array( $object, 'method_name' )
//                 [accepted_args] => 2
//             )
//         )
//     )
//     [iterations] => Array()    // Tracking cho nested hooks
//     [current_priority] => Array() // Priority đang thực thi
//     [nesting_level] => 0       // Mức lồng nhau
//     [doing_action] => false    // Có đang thực thi không
// )

// === TRUY CẬP WP_Hook TRỰC TIẾP ===

// Liệt kê tất cả callbacks của một hook
function my_list_hook_callbacks( $hook_name ) {
    global $wp_filter;

    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        return 'Hook không tồn tại hoặc chưa có callbacks.';
    }

    $hook = $wp_filter[ $hook_name ];
    $info = array();

    foreach ( $hook->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $callback_info ) {
            $func = $callback_info['function'];

            if ( is_string( $func ) ) {
                $name = $func;
            } elseif ( is_array( $func ) && is_object( $func[0] ) ) {
                $name = get_class( $func[0] ) . '->' . $func[1] . '()';
            } elseif ( is_array( $func ) && is_string( $func[0] ) ) {
                $name = $func[0] . '::' . $func[1] . '()';
            } elseif ( $func instanceof Closure ) {
                $ref = new ReflectionFunction( $func );
                $name = 'Closure@' . basename( $ref->getFileName() ) . ':' . $ref->getStartLine();
            } else {
                $name = 'Unknown callback type';
            }

            $info[] = array(
                'priority'      => $priority,
                'callback'      => $name,
                'accepted_args' => $callback_info['accepted_args'],
            );
        }
    }

    return $info;
}

// Sử dụng:
add_action( 'wp_loaded', function() {
    if ( ! isset( $_GET['show_hooks'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $hook_name = sanitize_text_field( $_GET['show_hooks'] );
    $callbacks = my_list_hook_callbacks( $hook_name );

    error_log( "=== Callbacks cho hook: {$hook_name} ===" );
    foreach ( $callbacks as $cb ) {
        error_log( sprintf(
            '  [Priority %d] %s (args: %d)',
            $cb['priority'],
            $cb['callback'],
            $cb['accepted_args']
        ));
    }
});
// Truy cập: ?show_hooks=the_content
```

---

## 7. Global $wp_filter Array

### Khám phá $wp_filter

```php
<?php
/**
 * $wp_filter là mảng global chứa TẤT CẢ hooks đã đăng ký
 *
 * Key: hook name
 * Value: WP_Hook instance
 */

// Đếm số hooks đã đăng ký
add_action( 'wp_loaded', function() {
    global $wp_filter;

    $total_hooks     = count( $wp_filter );
    $total_callbacks = 0;

    foreach ( $wp_filter as $hook_name => $hook ) {
        foreach ( $hook->callbacks as $callbacks ) {
            $total_callbacks += count( $callbacks );
        }
    }

    error_log( sprintf(
        '[Hook Stats] Tổng hooks: %d | Tổng callbacks: %d',
        $total_hooks,
        $total_callbacks
    ));
});

// Tìm tất cả hooks của một plugin
function my_find_plugin_hooks( $plugin_prefix ) {
    global $wp_filter;

    $plugin_hooks = array();

    foreach ( $wp_filter as $hook_name => $hook ) {
        foreach ( $hook->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback_info ) {
                $func = $callback_info['function'];

                // Kiểm tra function name có chứa prefix
                if ( is_string( $func ) && strpos( $func, $plugin_prefix ) !== false ) {
                    $plugin_hooks[] = array(
                        'hook'     => $hook_name,
                        'callback' => $func,
                        'priority' => $priority,
                    );
                }

                // Kiểm tra class name có chứa prefix
                if ( is_array( $func ) && is_object( $func[0] ) ) {
                    $class_name = get_class( $func[0] );
                    if ( strpos( $class_name, $plugin_prefix ) !== false ) {
                        $plugin_hooks[] = array(
                            'hook'     => $hook_name,
                            'callback' => $class_name . '->' . $func[1],
                            'priority' => $priority,
                        );
                    }
                }
            }
        }
    }

    return $plugin_hooks;
}
```

---

## 8. Hooks trong Multisite

### Multisite-specific Hooks

```php
<?php
// === HOOKS CHỈ CÓ TRONG MULTISITE ===

// Khi tạo site mới trong network
add_action( 'wp_initialize_site', 'my_setup_new_site', 10, 2 );
function my_setup_new_site( $new_site, $args ) {
    // $new_site là WP_Site object
    $blog_id = $new_site->blog_id;

    // Chuyển sang site mới để thao tác
    switch_to_blog( $blog_id );

    // Tạo default pages
    wp_insert_post( array(
        'post_type'   => 'page',
        'post_title'  => 'Giới thiệu',
        'post_status' => 'publish',
        'post_content' => 'Chào mừng đến với site mới!',
    ));

    // Set default options cho site mới
    update_option( 'my_plugin_settings', array(
        'feature_a' => true,
        'feature_b' => false,
    ));

    // Tạo custom tables cho site mới
    my_create_site_tables();

    // QUAN TRỌNG: Quay lại site gốc
    restore_current_blog();
}

// Khi xóa site trong network
add_action( 'wp_uninitialize_site', 'my_cleanup_site', 10, 1 );
function my_cleanup_site( $old_site ) {
    $blog_id = $old_site->blog_id;

    switch_to_blog( $blog_id );

    // Cleanup custom data
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_custom_table" );

    restore_current_blog();
}

// Hook chạy trên TẤT CẢ sites trong network
// Dùng khi plugin cần update schema cho mọi site
add_action( 'init', function() {
    // Kiểm tra version cho network-wide update
    if ( ! is_multisite() ) {
        return;
    }

    $network_version = get_site_option( 'my_plugin_network_version', '0' );

    if ( version_compare( $network_version, MY_PLUGIN_VERSION, '<' ) ) {
        // Cần update
        if ( is_main_site() ) {
            // Chỉ update từ main site
            $sites = get_sites( array( 'number' => 0 ) );
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                my_run_updates();
                restore_current_blog();
            }
            update_site_option( 'my_plugin_network_version', MY_PLUGIN_VERSION );
        }
    }
});

// Network Admin hooks
add_action( 'network_admin_menu', 'my_network_admin_menu' );
function my_network_admin_menu() {
    add_menu_page(
        'Network Plugin Settings',
        'My Plugin',
        'manage_network_options',     // Network admin capability
        'my-network-plugin',
        'my_network_settings_page',
        'dashicons-admin-generic'
    );
}

function my_network_settings_page() {
    if ( ! current_user_can( 'manage_network_options' ) ) {
        wp_die( 'Không có quyền.' );
    }
    echo '<div class="wrap"><h1>Network Settings</h1></div>';
}
```

---

## 9. Hooks và Caching

### Object Cache integration

```php
<?php
// === SỬ DỤNG OBJECT CACHE VỚI HOOKS ===

// Cache kết quả filter
add_filter( 'the_content', 'my_cached_content_filter' );
function my_cached_content_filter( $content ) {
    if ( is_admin() || ! is_single() ) {
        return $content;
    }

    $post_id   = get_the_ID();
    $cache_key = 'enhanced_content_' . $post_id;
    $cache_group = 'my_plugin';

    // Thử lấy từ object cache (Redis/Memcached nếu có)
    $cached = wp_cache_get( $cache_key, $cache_group );

    if ( false !== $cached ) {
        return $cached; // Cache hit - trả về ngay, không xử lý
    }

    // Cache miss - xử lý bình thường
    $content = my_enhance_content( $content );

    // Lưu vào cache (expire sau 1 giờ)
    wp_cache_set( $cache_key, $content, $cache_group, HOUR_IN_SECONDS );

    return $content;
}

// Xóa cache khi bài viết thay đổi
add_action( 'save_post', function( $post_id ) {
    wp_cache_delete( 'enhanced_content_' . $post_id, 'my_plugin' );
});

// === INVALIDATE CACHE THÔNG MINH ===
// Xóa cache liên quan khi có thay đổi

class My_Cache_Manager {

    private $cache_group = 'my_plugin';

    public function __construct() {
        // Xóa cache khi content thay đổi
        add_action( 'save_post', array( $this, 'invalidate_post_cache' ) );
        add_action( 'delete_post', array( $this, 'invalidate_post_cache' ) );
        add_action( 'transition_post_status', array( $this, 'invalidate_on_status_change' ), 10, 3 );

        // Xóa cache khi settings thay đổi
        add_action( 'update_option_my_plugin_settings', array( $this, 'invalidate_all_cache' ) );

        // Xóa cache khi theme switch
        add_action( 'switch_theme', array( $this, 'invalidate_all_cache' ) );
    }

    public function invalidate_post_cache( $post_id ) {
        // Xóa cache cho bài viết cụ thể
        wp_cache_delete( 'enhanced_content_' . $post_id, $this->cache_group );
        wp_cache_delete( 'post_sidebar_' . $post_id, $this->cache_group );

        // Xóa cache danh sách (vì danh sách có thể chứa bài này)
        wp_cache_delete( 'recent_posts_widget', $this->cache_group );
        wp_cache_delete( 'popular_posts_widget', $this->cache_group );

        // Xóa transient
        delete_transient( 'related_posts_' . $post_id );
    }

    public function invalidate_on_status_change( $new_status, $old_status, $post ) {
        if ( $new_status !== $old_status ) {
            $this->invalidate_post_cache( $post->ID );
        }
    }

    public function invalidate_all_cache() {
        // Xóa toàn bộ cache group (nếu object cache hỗ trợ)
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( $this->cache_group );
        } else {
            wp_cache_flush(); // Fallback: flush tất cả
        }
    }
}

new My_Cache_Manager();
```

---

## 10. Dynamic Hooks

### Hooks với tên động

```php
<?php
// WordPress có nhiều hooks với tên động (chứa biến)

// === PATTERN 1: Post type trong tên hook ===
// save_post_{post_type}
add_action( 'save_post_product', 'my_save_product', 10, 3 );
add_action( 'save_post_event', 'my_save_event', 10, 3 );

// manage_{post_type}_posts_columns
add_filter( 'manage_product_posts_columns', 'my_product_columns' );

// === PATTERN 2: Taxonomy trong tên hook ===
// {taxonomy}_add_form_fields
add_action( 'product_category_add_form_fields', 'my_add_taxonomy_fields' );
// {taxonomy}_edit_form_fields
add_action( 'product_category_edit_form_fields', 'my_edit_taxonomy_fields' );

// === PATTERN 3: Screen/Page trong tên hook ===
// load-{page}
add_action( 'load-edit.php', 'my_on_edit_page' );
add_action( 'load-post.php', 'my_on_post_page' );

// === PATTERN 4: AJAX action trong tên hook ===
// wp_ajax_{action}
add_action( 'wp_ajax_my_save', 'my_ajax_save_handler' );

// === TẠO DYNAMIC HOOKS TRONG PLUGIN ===

/**
 * Plugin xử lý nhiều loại sự kiện với dynamic hooks
 */
class My_Event_Processor {

    private $event_types = array( 'order', 'refund', 'subscription', 'review' );

    public function __construct() {
        // Đăng ký handler cho mỗi loại sự kiện
        foreach ( $this->event_types as $type ) {
            add_action( "my_plugin_process_{$type}", array( $this, "process_{$type}" ) );
        }
    }

    /**
     * Dispatch event theo type
     */
    public function dispatch( $type, $data ) {
        if ( ! in_array( $type, $this->event_types, true ) ) {
            return new WP_Error( 'invalid_type', "Event type '{$type}' không hợp lệ." );
        }

        // Hook chung: chạy cho MỌI event type
        do_action( 'my_plugin_before_process', $type, $data );

        // Hook cụ thể: chạy cho event type cụ thể
        do_action( "my_plugin_process_{$type}", $data );

        // Hook chung sau khi xử lý
        do_action( 'my_plugin_after_process', $type, $data );
    }

    public function process_order( $data ) {
        error_log( '[Event] Processing order: ' . print_r( $data, true ) );
    }

    public function process_refund( $data ) {
        error_log( '[Event] Processing refund: ' . print_r( $data, true ) );
    }

    public function process_subscription( $data ) {
        error_log( '[Event] Processing subscription: ' . print_r( $data, true ) );
    }

    public function process_review( $data ) {
        error_log( '[Event] Processing review: ' . print_r( $data, true ) );
    }
}

// Plugin khác hook vào event cụ thể:
add_action( 'my_plugin_process_order', function( $data ) {
    // Xử lý riêng cho orders
    wp_mail( $data['email'], 'Order received', '...' );
});

add_action( 'my_plugin_process_refund', function( $data ) {
    // Xử lý riêng cho refunds
    wp_mail( $data['email'], 'Refund processed', '...' );
});

// Hook vào TẤT CẢ events:
add_action( 'my_plugin_before_process', function( $type, $data ) {
    error_log( "[Event] Starting: {$type}" );
}, 10, 2 );

// === DYNAMIC HOOKS CHO STATUS TRANSITIONS ===

/**
 * Tạo hooks cho mọi status transition
 */
function my_order_status_transition( $order_id, $old_status, $new_status ) {
    // Hook chung
    do_action( 'my_order_status_changed', $order_id, $old_status, $new_status );

    // Hook cụ thể theo new status
    // Ví dụ: my_order_status_completed, my_order_status_cancelled
    do_action( "my_order_status_{$new_status}", $order_id, $old_status );

    // Hook cụ thể theo transition
    // Ví dụ: my_order_pending_to_completed, my_order_processing_to_shipped
    do_action( "my_order_{$old_status}_to_{$new_status}", $order_id );
}

// Người dùng hook vào transitions cụ thể:
add_action( 'my_order_status_completed', function( $order_id, $old_status ) {
    // Đơn hàng hoàn thành - bất kể trạng thái trước là gì
}, 10, 2 );

add_action( 'my_order_pending_to_completed', function( $order_id ) {
    // Chỉ khi chuyển từ pending → completed
});

add_action( 'my_order_processing_to_shipped', function( $order_id ) {
    // Chỉ khi chuyển từ processing → shipped
    // Gửi tracking email
});
```

---

## 11. Hooks Testing với PHPUnit

### Setup Testing Environment

```php
<?php
// File: tests/bootstrap.php

// Load WordPress test suite
$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

// Load plugin
tests_add_filter( 'muplugins_loaded', function() {
    require dirname( __DIR__ ) . '/my-plugin.php';
});

require $_tests_dir . '/includes/bootstrap.php';
```

### Test Hook Registration

```php
<?php
// File: tests/test-hooks.php

class Test_My_Plugin_Hooks extends WP_UnitTestCase {

    /**
     * Test: Plugin đăng ký hooks đúng
     */
    public function test_hooks_are_registered() {
        // Kiểm tra action hooks
        $this->assertNotFalse(
            has_action( 'init', 'my_plugin_init' ),
            'Hook init phải được đăng ký'
        );

        $this->assertNotFalse(
            has_action( 'admin_menu', 'my_plugin_admin_menu' ),
            'Hook admin_menu phải được đăng ký'
        );

        // Kiểm tra filter hooks
        $this->assertNotFalse(
            has_filter( 'the_content', 'my_plugin_filter_content' ),
            'Filter the_content phải được đăng ký'
        );
    }

    /**
     * Test: Hook priority đúng
     */
    public function test_hook_priorities() {
        $priority = has_filter( 'the_content', 'my_plugin_filter_content' );
        $this->assertEquals( 10, $priority, 'Priority phải là 10' );
    }

    /**
     * Test: Filter trả về giá trị đúng
     */
    public function test_content_filter() {
        // Tạo post giả
        $post_id = $this->factory->post->create( array(
            'post_content' => 'Original content',
            'post_type'    => 'post',
        ));

        // Giả lập single post context
        $this->go_to( get_permalink( $post_id ) );

        // Apply filter
        $content  = 'Test content here';
        $filtered = apply_filters( 'the_content', $content );

        // Kiểm tra filter hoạt động
        $this->assertStringContainsString( 'Test content here', $filtered );
        // Kiểm tra nội dung đã thêm
        $this->assertStringContainsString( 'author-box', $filtered );
    }

    /**
     * Test: Action hook thực thi đúng
     */
    public function test_save_post_action() {
        // Tạo post
        $post_id = $this->factory->post->create( array(
            'post_type' => 'task',
        ));

        // Giả lập POST data
        $_POST['task_priority']         = 'high';
        $_POST['task_status']           = 'in_progress';
        $_POST['my_task_details_nonce'] = wp_create_nonce( 'my_task_save_details' );

        // Set current user là admin
        $admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin );

        // Trigger save_post
        do_action( 'save_post_task', $post_id, get_post( $post_id ), true );

        // Kiểm tra meta đã được lưu
        $this->assertEquals( 'high', get_post_meta( $post_id, '_task_priority', true ) );
        $this->assertEquals( 'in_progress', get_post_meta( $post_id, '_task_status', true ) );
    }

    /**
     * Test: Custom hook được fire
     */
    public function test_custom_hook_fires() {
        $fired = false;
        $received_data = null;

        // Đăng ký listener
        add_action( 'my_task_completed', function( $task_id ) use ( &$fired, &$received_data ) {
            $fired = true;
            $received_data = $task_id;
        });

        // Fire hook
        do_action( 'my_task_completed', 42 );

        // Assertions
        $this->assertTrue( $fired, 'Hook my_task_completed phải được fire' );
        $this->assertEquals( 42, $received_data, 'Task ID phải là 42' );
    }

    /**
     * Test: Filter chain hoạt động đúng
     */
    public function test_filter_chain() {
        // Thêm 2 filters
        add_filter( 'my_custom_value', function( $value ) {
            return $value * 2;
        }, 10 );

        add_filter( 'my_custom_value', function( $value ) {
            return $value + 10;
        }, 20 );

        // Apply filters
        $result = apply_filters( 'my_custom_value', 5 );

        // 5 * 2 = 10, 10 + 10 = 20
        $this->assertEquals( 20, $result );
    }

    /**
     * Test: did_action() đếm đúng
     */
    public function test_did_action_count() {
        $this->assertEquals( 0, did_action( 'my_test_event' ) );

        do_action( 'my_test_event' );
        $this->assertEquals( 1, did_action( 'my_test_event' ) );

        do_action( 'my_test_event' );
        $this->assertEquals( 2, did_action( 'my_test_event' ) );
    }

    /**
     * Test: Remove hook hoạt động
     */
    public function test_remove_hook() {
        $callback = function( $content ) {
            return $content . ' - modified';
        };

        add_filter( 'my_test_filter', $callback );

        // Trước khi remove
        $this->assertEquals( 'hello - modified', apply_filters( 'my_test_filter', 'hello' ) );

        // Remove
        remove_filter( 'my_test_filter', $callback );

        // Sau khi remove
        $this->assertEquals( 'hello', apply_filters( 'my_test_filter', 'hello' ) );
    }

    /**
     * Test: AJAX handler
     */
    public function test_ajax_handler() {
        // Set admin user
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );

        // Giả lập AJAX request
        $_POST['action']   = 'my_plugin_action';
        $_POST['nonce']    = wp_create_nonce( 'my_plugin_nonce' );
        $_POST['task_id']  = 1;

        // Bắt output
        try {
            $this->_handleAjax( 'my_plugin_action' );
        } catch ( WPAjaxDieContinueException $e ) {
            // Expected - wp_send_json calls wp_die()
        }

        $response = json_decode( $this->_last_response, true );
        $this->assertTrue( $response['success'] );
    }
}
```

---

## 12. Common Pitfalls và Solutions

### Pitfall 1: Quên return trong Filter

```php
<?php
// SAI
add_filter( 'the_title', function( $title ) {
    if ( is_admin() ) {
        return $title; // OK ở đây
    }
    // QUÊN RETURN! → title biến mất
});

// ĐÚNG
add_filter( 'the_title', function( $title ) {
    if ( is_admin() ) {
        return $title;
    }
    $title = 'Prefix: ' . $title;
    return $title; // Luôn return!
});
```

### Pitfall 2: Infinite loop khi gọi function trigger cùng hook

```php
<?php
// SAI: wp_update_post() trigger save_post → gọi lại callback → loop!
add_action( 'save_post', function( $post_id ) {
    wp_update_post( array(
        'ID'         => $post_id,
        'post_title' => 'Updated: ' . get_the_title( $post_id ),
    ));
    // save_post trigger lại → infinite loop → crash!
});

// ĐÚNG: Remove hook trước, add lại sau
add_action( 'save_post', 'my_safe_update' );
function my_safe_update( $post_id ) {
    // Gỡ hook
    remove_action( 'save_post', 'my_safe_update' );

    // Giờ an toàn để update
    wp_update_post( array(
        'ID'         => $post_id,
        'post_title' => 'Updated: ' . get_the_title( $post_id ),
    ));

    // Gắn hook lại
    add_action( 'save_post', 'my_safe_update' );
}
```

### Pitfall 3: Hook chạy quá sớm - functions chưa available

```php
<?php
// SAI: get_current_user_id() chưa available ở plugins_loaded
add_action( 'plugins_loaded', function() {
    $user_id = get_current_user_id(); // Có thể return 0!
});

// ĐÚNG: Dùng hook phù hợp
add_action( 'init', function() {
    $user_id = get_current_user_id(); // OK - user đã được set
});
```

### Pitfall 4: Conditional tags chưa available

```php
<?php
// SAI: is_single() chưa available ở init
add_action( 'init', function() {
    if ( is_single() ) { // LUÔN trả về false tại init!
        add_filter( 'the_content', 'my_filter' );
    }
});

// ĐÚNG: Dùng hook chạy sau khi query đã parse
add_action( 'wp', function() {
    if ( is_single() ) { // OK - query đã parse
        add_filter( 'the_content', 'my_filter' );
    }
});

// Hoặc kiểm tra trong callback
add_filter( 'the_content', function( $content ) {
    if ( ! is_single() ) {
        return $content;
    }
    // Process...
    return $content;
});
```

### Pitfall 5: Anonymous function không thể remove

```php
<?php
// SAI: Không thể remove
add_action( 'wp_head', function() {
    echo '<meta name="custom" content="value">';
});
// remove_action( 'wp_head', ??? ); // Impossible!

// ĐÚNG: Dùng named function nếu cần allow remove
function my_custom_meta() {
    echo '<meta name="custom" content="value">';
}
add_action( 'wp_head', 'my_custom_meta' );
// remove_action( 'wp_head', 'my_custom_meta' ); // OK!

// Hoặc: Lưu closure vào biến
$my_closure = function() {
    echo '<meta name="custom" content="value">';
};
add_action( 'wp_head', $my_closure );
// remove_action( 'wp_head', $my_closure ); // OK - same reference!
```

### Pitfall 6: Priority không khớp khi remove

```php
<?php
// add ở priority 15
add_action( 'init', 'my_function', 15 );

// SAI: remove ở priority 10 (mặc định)
remove_action( 'init', 'my_function' ); // KHÔNG HOẠT ĐỘNG!

// ĐÚNG: remove ở priority 15
remove_action( 'init', 'my_function', 15 ); // OK!
```

### Pitfall 7: Race condition khi remove hook từ plugin khác

```php
<?php
// Plugin A load trước, add hook trong constructor
// Plugin B muốn remove hook từ Plugin A

// SAI: Remove ngay - Plugin A có thể chưa load
remove_action( 'wp_head', 'plugin_a_tracking' ); // Plugin A chưa add!

// ĐÚNG: Remove trong hook chạy sau
add_action( 'wp_loaded', function() {
    remove_action( 'wp_head', 'plugin_a_tracking' ); // Plugin A đã add rồi
});

// Hoặc dùng after_setup_theme / init với priority cao
add_action( 'init', function() {
    remove_action( 'wp_head', 'plugin_a_tracking' );
}, 999 );
```

---

## 13. Best Practices Nang Cao

### 1. Dùng type hints trong callbacks (PHP 7.4+)

```php
<?php
add_filter( 'the_content', function( string $content ): string {
    return $content . '<p>Extra content</p>';
});

add_action( 'save_post', function( int $post_id, WP_Post $post, bool $update ): void {
    // Type-safe code
}, 10, 3 );
```

### 2. Tổ chức hooks trong class riêng

```php
<?php
class My_Hook_Registry {

    public function register_all(): void {
        // Actions
        add_action( 'init', array( $this, 'on_init' ) );
        add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );

        // Filters
        add_filter( 'the_content', array( $this, 'on_the_content' ) );

        // AJAX
        add_action( 'wp_ajax_my_action', array( $this, 'on_ajax' ) );

        // Conditional
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'on_admin_init' ) );
        }
    }

    // ... implementations
}
```

### 3. Defensive programming: Kiểm tra function tồn tại

```php
<?php
add_action( 'init', function() {
    // Kiểm tra WooCommerce function trước khi dùng
    if ( function_exists( 'wc_get_products' ) ) {
        $products = wc_get_products( array( 'limit' => 10 ) );
    }

    // Kiểm tra class trước khi dùng
    if ( class_exists( 'ACF' ) ) {
        $field = get_field( 'my_field' );
    }
});
```

### 4. Error handling trong hooks

```php
<?php
add_action( 'my_plugin_process', function( $data ) {
    try {
        // Code có thể throw exception
        $result = process_data( $data );
        do_action( 'my_plugin_process_success', $result );
    } catch ( Exception $e ) {
        // Log error
        error_log( '[My Plugin Error] ' . $e->getMessage() );

        // Fire error hook
        do_action( 'my_plugin_process_error', $e, $data );

        // Không re-throw - tránh crash WordPress
    }
});

// Error listener
add_action( 'my_plugin_process_error', function( $exception, $data ) {
    // Gửi email thông báo admin
    if ( $exception->getCode() >= 500 ) {
        wp_mail(
            get_option( 'admin_email' ),
            '[Critical] Plugin Error',
            $exception->getMessage()
        );
    }
}, 10, 2 );
```

### 5. Hooks documentation chuẩn

```php
<?php
/**
 * Fires after processing is complete.
 *
 * @since 1.0.0
 * @since 1.2.0 Added $context parameter.
 *
 * @param int    $item_id  The item ID that was processed.
 * @param array  $result   Processing results.
 * @param string $context  The context: 'admin', 'frontend', 'api', 'cron'.
 */
do_action( 'my_plugin_processed', $item_id, $result, $context );

/**
 * Filters the output before rendering.
 *
 * @since 1.0.0
 *
 * @param string $output   The HTML output.
 * @param int    $item_id  The item ID.
 * @param array  $args     Display arguments.
 * @return string Modified HTML output.
 */
$output = apply_filters( 'my_plugin_output', $output, $item_id, $args );
```

### Tổng kết: Checklist khi dùng Hooks

```
[ ] Prefix tất cả function names và hook names
[ ] Return giá trị trong filter callbacks
[ ] Kiểm tra nonce trong save_post và AJAX handlers
[ ] Kiểm tra capability (current_user_can)
[ ] Kiểm tra DOING_AUTOSAVE trong save_post
[ ] Dùng đúng hook cho đúng mục đích
[ ] Chỉ load assets khi cần
[ ] Cleanup khi deactivate (cron, rewrite rules)
[ ] Xóa data khi uninstall (tables, options, meta)
[ ] Document custom hooks đầy đủ
[ ] Test hooks với PHPUnit
[ ] Xử lý errors, không để crash WordPress
[ ] Cache kết quả queries nặng
[ ] Dùng early return để tối ưu performance
```

---

> **Kết thúc series:** Bạn đã nắm vững WordPress Hooks từ cơ bản đến nâng cao. Quay lại [01 - Hooks Cơ Bản](01-hooks-co-ban.md) nếu cần ôn lại.
