<?php
/**
 * Child Theme Functions
 *
 * File này được load sau functions.php của parent theme
 * Thêm các hàm tùy chỉnh của bạn ở đây.
 */

/**
 * Enqueue styles của parent theme và child theme
 */
function child_theme_enqueue_styles() {
    // Enqueue stylesheet của parent theme
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->get('Version')
    );

    // Enqueue stylesheet của child theme
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_styles');

/**
 * Ví dụ: Thêm custom JavaScript
 */
function child_theme_enqueue_scripts() {
    wp_enqueue_script(
        'child-theme-script',
        get_stylesheet_directory_uri() . '/js/custom.js',
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_scripts');

/**
 * Ví dụ: Đăng ký vị trí menu tùy chỉnh
 */
function child_theme_register_menus() {
    register_nav_menus(array(
        'custom-menu' => __('Custom Menu', 'child-theme'),
    ));
}
add_action('after_setup_theme', 'child_theme_register_menus');

/**
 * Ví dụ: Đăng ký vùng widget
 */
function child_theme_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar Tùy Chỉnh', 'child-theme'),
        'id'            => 'custom-sidebar',
        'description'   => __('Thêm widget vào đây.', 'child-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'child_theme_widgets_init');

/**
 * Ví dụ: Tùy chỉnh độ dài excerpt
 */
function child_theme_excerpt_length($length) {
    return 30; // Thay đổi thành số bạn muốn
}
add_filter('excerpt_length', 'child_theme_excerpt_length');

/**
 * Ví dụ: Tùy chỉnh text "đọc thêm" của excerpt
 */
function child_theme_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'child_theme_excerpt_more');

/**
 * Ví dụ: Thêm theme support
 */
function child_theme_setup() {
    // Hỗ trợ ảnh đại diện cho bài viết (Featured Image)
    add_theme_support('post-thumbnails');

    // Hỗ trợ logo tùy chỉnh
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Hỗ trợ header tùy chỉnh
    add_theme_support('custom-header', array(
        'default-image' => get_stylesheet_directory_uri() . '/images/header.jpg',
        'width'         => 1200,
        'height'        => 400,
        'flex-height'   => true,
        'flex-width'    => true,
    ));
}
add_action('after_setup_theme', 'child_theme_setup');

/**
 * Ví dụ: Gỡ bỏ function của parent theme (nếu cần)
 */
// function remove_parent_function() {
//     remove_action('wp_head', 'parent_theme_function');
// }
// add_action('init', 'remove_parent_function');

/**
 * Ví dụ: Thêm custom body classes
 */
function child_theme_body_classes($classes) {
    // Thêm class tùy chỉnh
    $classes[] = 'child-theme-active';
    
    return $classes;
}
add_filter('body_class', 'child_theme_body_classes');


