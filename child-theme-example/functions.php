<?php
/**
 * Child Theme Functions
 * 
 * This file is loaded after the parent theme's functions.php
 * Add your custom functions here.
 */

/**
 * Enqueue parent and child theme styles
 */
function child_theme_enqueue_styles() {
    // Enqueue parent theme stylesheet
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->get('Version')
    );
    
    // Enqueue child theme stylesheet
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_styles');

/**
 * Example: Add custom JavaScript
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
 * Example: Register custom menu location
 */
function child_theme_register_menus() {
    register_nav_menus(array(
        'custom-menu' => __('Custom Menu', 'child-theme'),
    ));
}
add_action('after_setup_theme', 'child_theme_register_menus');

/**
 * Example: Register widget area
 */
function child_theme_widgets_init() {
    register_sidebar(array(
        'name'          => __('Custom Sidebar', 'child-theme'),
        'id'            => 'custom-sidebar',
        'description'   => __('Add widgets here.', 'child-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'child_theme_widgets_init');

/**
 * Example: Custom excerpt length
 */
function child_theme_excerpt_length($length) {
    return 30; // Change to your desired length
}
add_filter('excerpt_length', 'child_theme_excerpt_length');

/**
 * Example: Custom excerpt more text
 */
function child_theme_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'child_theme_excerpt_more');

/**
 * Example: Add theme support
 */
function child_theme_setup() {
    // Add theme support for post thumbnails
    add_theme_support('post-thumbnails');
    
    // Add theme support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Add theme support for custom header
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
 * Example: Remove parent theme function (if needed)
 */
// function remove_parent_function() {
//     remove_action('wp_head', 'parent_theme_function');
// }
// add_action('init', 'remove_parent_function');

/**
 * Example: Add custom body classes
 */
function child_theme_body_classes($classes) {
    // Add custom class
    $classes[] = 'child-theme-active';
    
    return $classes;
}
add_filter('body_class', 'child_theme_body_classes');


