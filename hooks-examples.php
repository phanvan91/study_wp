<?php
/**
 * WordPress Hooks Examples
 * 
 * File này chứa các ví dụ về cách sử dụng WordPress Hooks
 * Copy code vào functions.php của theme hoặc plugin để test
 */

// ============================================
// ACTION HOOKS EXAMPLES
// ============================================

/**
 * Example 1: Thêm custom message khi WordPress init
 */
function example_init_action() {
    if ( current_user_can( 'manage_options' ) ) {
        // Chỉ hiển thị cho admin
        // echo 'WordPress đã được khởi tạo!';
    }
}
add_action( 'init', 'example_init_action' );

/**
 * Example 2: Thêm custom CSS vào head
 */
function example_wp_head_action() {
    ?>
    <style>
        .custom-style {
            color: #0073aa;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'example_wp_head_action' );

/**
 * Example 3: Thêm script vào footer
 */
function example_wp_footer_action() {
    ?>
    <script>
        console.log('WordPress Footer Loaded');
    </script>
    <?php
}
add_action( 'wp_footer', 'example_wp_footer_action' );

/**
 * Example 4: Log khi post được lưu
 */
function example_save_post_action( $post_id, $post ) {
    // Tránh auto-save
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Log post đã được lưu
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( "Post saved: {$post->post_title} (ID: $post_id)" );
    }
}
add_action( 'save_post', 'example_save_post_action', 10, 2 );

/**
 * Example 5: Redirect sau khi login
 */
function example_wp_login_action( $user_login, $user ) {
    // Redirect đến custom page
    // wp_redirect( home_url( '/dashboard' ) );
    // exit;
}
// Uncomment để enable
// add_action( 'wp_login', 'example_wp_login_action', 10, 2 );

// ============================================
// FILTER HOOKS EXAMPLES
// ============================================

/**
 * Example 1: Thay đổi độ dài excerpt
 */
function example_excerpt_length_filter( $length ) {
    return 30; // Thay đổi từ 55 (mặc định) thành 30
}
add_filter( 'excerpt_length', 'example_excerpt_length_filter' );

/**
 * Example 2: Thay đổi text "read more"
 */
function example_excerpt_more_filter( $more ) {
    return '... [Đọc thêm]';
}
add_filter( 'excerpt_more', 'example_excerpt_more_filter' );

/**
 * Example 3: Thêm custom content vào the_content
 */
function example_the_content_filter( $content ) {
    // Chỉ thêm vào single post
    if ( is_single() ) {
        $custom_content = '<div class="custom-content">Custom content here</div>';
        $content = $content . $custom_content;
    }
    return $content;
}
add_filter( 'the_content', 'example_the_content_filter' );

/**
 * Example 4: Thay đổi post title
 */
function example_the_title_filter( $title, $post_id = null ) {
    // Thêm prefix vào title
    if ( ! is_admin() && in_the_loop() ) {
        $title = '📝 ' . $title;
    }
    return $title;
}
add_filter( 'the_title', 'example_the_title_filter', 10, 2 );

/**
 * Example 5: Filter email trước khi gửi
 */
function example_wp_mail_filter( $args ) {
    // Thêm prefix vào subject
    $args['subject'] = '[My Site] ' . $args['subject'];
    
    // Thêm signature vào message
    $args['message'] .= "\n\n---\nSent from My WordPress Site";
    
    return $args;
}
// Uncomment để enable
// add_filter( 'wp_mail', 'example_wp_mail_filter' );

/**
 * Example 6: Thay đổi permalink structure
 */
function example_post_link_filter( $permalink, $post, $leavename ) {
    // Custom permalink logic
    // return $custom_permalink;
    return $permalink;
}
// Uncomment để enable
// add_filter( 'post_link', 'example_post_link_filter', 10, 3 );

// ============================================
// PRIORITY EXAMPLES
// ============================================

/**
 * Example: Sử dụng priority để kiểm soát thứ tự
 */
function example_priority_early() {
    // Chạy đầu tiên (priority 1)
    // echo 'Early function';
}
add_action( 'init', 'example_priority_early', 1 );

function example_priority_default() {
    // Chạy thứ hai (priority 10 - mặc định)
    // echo 'Default function';
}
add_action( 'init', 'example_priority_default' );

function example_priority_late() {
    // Chạy cuối cùng (priority 20)
    // echo 'Late function';
}
add_action( 'init', 'example_priority_late', 20 );

// ============================================
// CLASS METHOD EXAMPLES
// ============================================

/**
 * Example: Sử dụng class method với hooks
 */
class Example_Hooks_Class {
    
    public function __construct() {
        // Đăng ký hooks trong constructor
        add_action( 'init', array( $this, 'init' ) );
        add_filter( 'the_content', array( $this, 'filter_content' ) );
    }
    
    public function init() {
        // Code khi init
    }
    
    public function filter_content( $content ) {
        // Filter content
        return $content;
    }
}

// Khởi tạo class
new Example_Hooks_Class();

// ============================================
// ANONYMOUS FUNCTION EXAMPLES
// ============================================

/**
 * Example: Sử dụng anonymous function (closure)
 */
add_action( 'init', function() {
    // Code của bạn
} );

add_filter( 'the_title', function( $title ) {
    return $title;
} );

// ============================================
// CONDITIONAL HOOKS
// ============================================

/**
 * Example: Chỉ chạy hook trong điều kiện cụ thể
 */
function example_conditional_action() {
    // Chỉ chạy trong admin
    if ( is_admin() ) {
        // Admin code
    }
    
    // Chỉ chạy khi user đã login
    if ( is_user_logged_in() ) {
        // Logged in code
    }
    
    // Chỉ chạy trên frontend
    if ( ! is_admin() ) {
        // Frontend code
    }
    
    // Chỉ chạy trên single post
    if ( is_single() ) {
        // Single post code
    }
}
add_action( 'init', 'example_conditional_action' );

// ============================================
// REMOVE HOOKS
// ============================================

/**
 * Example: Remove hook từ plugin/theme khác
 */
function example_remove_hook() {
    // Kiểm tra xem hook có tồn tại không
    if ( has_action( 'wp_head', 'some_function' ) ) {
        remove_action( 'wp_head', 'some_function' );
    }
}
add_action( 'init', 'example_remove_hook' );

// ============================================
// CUSTOM HOOKS
// ============================================

/**
 * Example: Tạo custom action hook
 */
function example_custom_process() {
    $data = 'Some data';
    
    // Fire custom action
    do_action( 'my_custom_action', $data );
}

// Đăng ký custom action
add_action( 'my_custom_action', function( $data ) {
    // Xử lý khi action được fire
    // echo $data;
} );

/**
 * Example: Tạo custom filter hook
 */
function example_get_custom_data() {
    $data = 'Original Data';
    
    // Apply custom filter
    $data = apply_filters( 'my_custom_filter', $data, 'arg1', 'arg2' );
    
    return $data;
}

// Đăng ký custom filter
add_filter( 'my_custom_filter', function( $data, $arg1, $arg2 ) {
    return 'Modified: ' . $data;
}, 10, 3 );

// ============================================
// DEBUG HOOKS
// ============================================

/**
 * Example: Log tất cả hooks được fire (chỉ khi WP_DEBUG = true)
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'all', function( $hook ) {
        // Log hook name
        // error_log( 'Hook fired: ' . $hook );
    } );
}

/**
 * Example: Kiểm tra hook có được đăng ký không
 */
function example_check_hooks() {
    if ( has_action( 'init' ) ) {
        // Hook 'init' đã được đăng ký
    }
    
    if ( has_filter( 'the_content' ) ) {
        // Filter 'the_content' đã được đăng ký
    }
}
add_action( 'init', 'example_check_hooks' );

