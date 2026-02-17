# AJAX và REST API trong WordPress Plugin

## Mục lục

1. [WordPress AJAX cơ bản](#1-wordpress-ajax-co-ban)
2. [admin-ajax.php Flow](#2-admin-ajaxphp-flow)
3. [wp_localize_script - Truyền data sang JS](#3-wp_localize_script---truyen-data-sang-js)
4. [Nonce Verification trong AJAX](#4-nonce-verification-trong-ajax)
5. [jQuery AJAX và Fetch API](#5-jquery-ajax-va-fetch-api)
6. [REST API trong Plugin](#6-rest-api-trong-plugin)
7. [Custom Endpoints](#7-custom-endpoints)
8. [Permission Callback và Schema Validation](#8-permission-callback-va-schema-validation)
9. [Code ví dụ: CRUD API hoàn chỉnh](#9-code-vi-du-crud-api-hoan-chinh)
10. [So sánh REST API với Route trong Laravel](#10-so-sanh-rest-api-voi-route-trong-laravel)
11. [Best Practices](#11-best-practices)

---

## 1. WordPress AJAX cơ bản

### AJAX trong WordPress là gì?

WordPress AJAX sử dụng file `admin-ajax.php` làm endpoint trung tâm cho tất cả các AJAX request. Plugin đăng ký handler thông qua hooks đặc biệt.

### 2 loai AJAX hooks

```php
<?php
/**
 * Plugin Name: AJAX Demo
 * Description: Demo WordPress AJAX.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook AJAX cho người dùng DA DANG NHAP (logged in):
 * wp_ajax_{action_name}
 *
 * Hook AJAX cho người dùng CHUA DANG NHAP (logged out):
 * wp_ajax_nopriv_{action_name}
 *
 * {action_name} = giá trị của 'action' gửi từ JavaScript
 */

// Hook cho cả 2 loại người dùng
add_action( 'wp_ajax_my_ajax_action', 'handle_my_ajax_action' );
add_action( 'wp_ajax_nopriv_my_ajax_action', 'handle_my_ajax_action' );

/**
 * Hàm xử lý AJAX request
 */
function handle_my_ajax_action() {
    // 1. Kiểm tra nonce (bảo mật)
    check_ajax_referer( 'my_ajax_nonce', 'nonce' );

    // 2. Lấy dữ liệu từ request
    $name = sanitize_text_field( $_POST['name'] ?? '' );

    // 3. Xử lý logic
    if ( empty( $name ) ) {
        // Trả về lỗi - wp_send_json_error tự động set status 200
        // và gửi JSON: { "success": false, "data": {...} }
        wp_send_json_error( array(
            'message' => 'Tên không được để trống.',
        ));
        // wp_send_json_error tự động gọi wp_die() - code phía dưới không chạy
    }

    // 4. Trả về thành công
    // wp_send_json_success gửi JSON: { "success": true, "data": {...} }
    wp_send_json_success( array(
        'message' => 'Xin chao, ' . $name . '!',
        'time'    => current_time( 'mysql' ),
    ));
}

// Hook chỉ cho người dùng đã đăng nhập (ví dụ: chức năng admin)
add_action( 'wp_ajax_admin_only_action', 'handle_admin_only_action' );
// KHÔNG có wp_ajax_nopriv_ => người chưa đăng nhập không thể gọi

function handle_admin_only_action() {
    // Kiểm tra quyền
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
    }

    check_ajax_referer( 'admin_ajax_nonce', 'nonce' );

    // Xử lý...
    wp_send_json_success( array( 'message' => 'OK' ) );
}
```

---

## 2. admin-ajax.php Flow

```
FRONTEND (JavaScript)                    BACKEND (WordPress)
======================                   ====================

1. User click button
   |
2. JS gửi AJAX request    ------>    3. admin-ajax.php nhận request
   URL: /wp-admin/admin-ajax.php          |
   Data: { action: 'my_action',      4. Tìm hook wp_ajax_{action}
           nonce: '...',                  hoặc wp_ajax_nopriv_{action}
           name: 'John' }                |
                                     5. Gọi callback function
                                          |
                                     6. Xử lý logic, query DB...
                                          |
7. JS nhận response       <------    7. wp_send_json_success/error
   |                                     (tự động set Content-Type: JSON
8. Cập nhật DOM                          và gọi wp_die())
```

### Cấu hình AJAX URL và Nonce

```php
<?php
/**
 * Đăng ký script và truyền dữ liệu sang JavaScript
 */
add_action( 'wp_enqueue_scripts', 'ajax_demo_enqueue_scripts' );

function ajax_demo_enqueue_scripts() {
    // Đăng ký và load file JS
    wp_enqueue_script(
        'ajax-demo-script',                              // Handle
        plugin_dir_url( __FILE__ ) . 'js/ajax-demo.js', // URL
        array( 'jquery' ),                               // Dependencies
        '1.0.0',                                         // Version
        true                                             // Load ở footer
    );

    /**
     * wp_localize_script() - Truyền dữ liệu PHP sang JavaScript
     *
     * Tạo 1 object JavaScript global chứa dữ liệu cần thiết.
     * PHẢI gọi SAU wp_enqueue_script().
     *
     * @param string $handle  Handle của script đã enqueue
     * @param string $name    Tên object JavaScript sẽ tạo
     * @param array  $data    Dữ liệu truyền sang
     */
    wp_localize_script( 'ajax-demo-script', 'ajaxDemo', array(
        // URL của admin-ajax.php
        // admin_url('admin-ajax.php') = https://example.com/wp-admin/admin-ajax.php
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),

        // Tạo nonce (số dùng 1 lần) cho bảo mật
        'nonce'   => wp_create_nonce( 'my_ajax_nonce' ),

        // Có thể truyền bất kỳ dữ liệu nào
        'siteUrl' => home_url(),
        'isAdmin' => current_user_can( 'manage_options' ),
        'i18n'    => array(
            'loading' => 'Đang xử lý...',
            'error'   => 'Có lỗi xảy ra!',
            'success' => 'Thành công!',
        ),
    ));
    // Kết quả: Trong JS có thể truy cập ajaxDemo.ajaxUrl, ajaxDemo.nonce, v.v.
}
```

---

## 3. wp_localize_script - Truyền data sang JS

```php
<?php
/**
 * wp_localize_script tạo 1 object JavaScript.
 * Đây là cách CHUẨN để truyền data từ PHP sang JS trong WordPress.
 *
 * Tương đương với @json() trong Blade của Laravel.
 */

// PHP (trong plugin):
wp_localize_script( 'my-script', 'myPluginData', array(
    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
    'restUrl'   => rest_url( 'my-plugin/v1/' ),
    'restNonce' => wp_create_nonce( 'wp_rest' ),
    'nonce'     => wp_create_nonce( 'my_nonce' ),
    'userId'    => get_current_user_id(),
    'settings'  => get_option( 'my_settings', array() ),
    'strings'   => array(
        'confirm_delete' => 'Bạn có chắc muốn xóa?',
        'saved'          => 'Đã lưu thành công!',
    ),
));

// JavaScript: Tự động có object myPluginData
// console.log(myPluginData.ajaxUrl);    // "https://example.com/wp-admin/admin-ajax.php"
// console.log(myPluginData.restUrl);    // "https://example.com/wp-json/my-plugin/v1/"
// console.log(myPluginData.userId);     // 1

// === CÁCH MỚI (WP 6.3+): wp_add_inline_script ===
// Linh hoạt hơn wp_localize_script

wp_enqueue_script( 'my-script', '...', array(), '1.0', true );

wp_add_inline_script( 'my-script', sprintf(
    'const myPluginConfig = %s;',
    wp_json_encode( array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'my_nonce' ),
    ))
), 'before' ); // 'before' = trước file script, 'after' = sau
```

---

## 4. Nonce Verification trong AJAX

```php
<?php
/**
 * NONCE (Number used ONCE) = Mã bảo mật dùng 1 lần
 * Chống CSRF (Cross-Site Request Forgery)
 *
 * Luồng hoạt động:
 * 1. PHP tạo nonce => gửi sang JS
 * 2. JS gửi nonce kèm theo AJAX request
 * 3. PHP kiểm tra nonce có hợp lệ không
 */

// === TẠO NONCE (PHP) ===
$nonce = wp_create_nonce( 'my_action_name' );
// $nonce = chuỗi hash ngắn (ví dụ: "a1b2c3d4e5")

// === GỬI NONCE SANG JS ===
wp_localize_script( 'my-script', 'myData', array(
    'nonce' => wp_create_nonce( 'my_action_name' ),
));

// === GỬI NONCE TỪ JS ===
// jQuery:
// $.post(myData.ajaxUrl, {
//     action: 'my_action',
//     nonce: myData.nonce,     // <-- Gửi kèm nonce
//     data: '...'
// });

// === KIỂM TRA NONCE (PHP) ===

// Cách 1: check_ajax_referer() - Dùng cho AJAX
add_action( 'wp_ajax_my_action', function() {
    /**
     * check_ajax_referer() - Kiểm tra nonce từ AJAX request
     *
     * @param string      $action    Tên action (khớp với wp_create_nonce)
     * @param string|false $query_arg Tên trường chứa nonce (mặc định: '_ajax_nonce')
     * @param bool        $die       true = tự động die nếu sai (mặc định: true)
     *
     * @return int|false  1 = nonce dưới 12h, 2 = nonce 12-24h, false = sai
     */
    check_ajax_referer( 'my_action_name', 'nonce' );
    // Nếu nonce sai, tự động die() với HTTP 403

    // Code xử lý tiếp...
    wp_send_json_success();
});

// Cách 2: wp_verify_nonce() - Kiểm tra thủ công
add_action( 'wp_ajax_my_action2', function() {
    $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );

    if ( ! wp_verify_nonce( $nonce, 'my_action_name' ) ) {
        wp_send_json_error( array( 'message' => 'Nonce không hợp lệ.' ), 403 );
    }

    // Code xử lý tiếp...
    wp_send_json_success();
});

// LƯU Ý: Nonce có thời hạn
// - Mặc định: 24 giờ (có thể thay đổi bằng filter nonce_life)
// - wp_verify_nonce trả về:
//   1 = nonce dưới 12 giờ (mới)
//   2 = nonce từ 12-24 giờ (cũ nhưng vẫn hợp lệ)
//   false = hết hạn hoặc sai
```

---

## 5. jQuery AJAX và Fetch API

### jQuery AJAX

```javascript
/**
 * File: js/ajax-demo.js
 *
 * Sử dụng jQuery AJAX (WordPress đã bao gồm jQuery)
 */
jQuery(document).ready(function($) {

    // === AJAX POST cơ bản ===
    $('#my-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $message = $('#ajax-message');

        // Disable button khi đang xử lý
        $submitBtn.prop('disabled', true).text(ajaxDemo.i18n.loading);

        $.ajax({
            url: ajaxDemo.ajaxUrl,          // URL admin-ajax.php
            type: 'POST',
            data: {
                action: 'my_ajax_action',   // Tên action (khớp với wp_ajax_{action})
                nonce: ajaxDemo.nonce,       // Nonce bảo mật
                name: $('#input-name').val(),
                email: $('#input-email').val(),
            },
            success: function(response) {
                // response = { success: true/false, data: {...} }
                if (response.success) {
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .html(response.data.message)
                        .show();
                } else {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .html(response.data.message)
                        .show();
                }
            },
            error: function(xhr, status, error) {
                // Lỗi mạng, server, v.v.
                $message
                    .addClass('error')
                    .html(ajaxDemo.i18n.error + ': ' + error)
                    .show();
            },
            complete: function() {
                // Luôn chạy, kể cả thành công hay thất bại
                $submitBtn.prop('disabled', false).text('Gửi');
            }
        });
    });

    // === AJAX với jQuery.post (ngắn gọn hơn) ===
    $('#load-more').on('click', function() {
        var page = $(this).data('page') || 1;

        $.post(ajaxDemo.ajaxUrl, {
            action: 'load_more_posts',
            nonce: ajaxDemo.nonce,
            page: page,
        }, function(response) {
            if (response.success) {
                $('#posts-container').append(response.data.html);
                $('#load-more').data('page', page + 1);

                if (!response.data.has_more) {
                    $('#load-more').hide();
                }
            }
        });
    });

    // === Upload file qua AJAX ===
    $('#upload-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'upload_file');
        formData.append('nonce', ajaxDemo.nonce);

        $.ajax({
            url: ajaxDemo.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,     // QUAN TRỌNG: Không xử lý data
            contentType: false,     // QUAN TRỌNG: Không set content-type
            success: function(response) {
                if (response.success) {
                    alert('Upload thành công: ' + response.data.filename);
                }
            }
        });
    });
});
```

### Fetch API (Modern JavaScript, không cần jQuery)

```javascript
/**
 * File: js/ajax-fetch.js
 *
 * Sử dụng Fetch API (modern, không cần jQuery)
 * Cần truyền dữ liệu qua wp_add_inline_script hoặc wp_localize_script
 */

// === Fetch POST cơ bản ===
async function submitForm() {
    const formData = new FormData();
    formData.append('action', 'my_ajax_action');
    formData.append('nonce', myPluginData.nonce);
    formData.append('name', document.getElementById('input-name').value);

    try {
        const response = await fetch(myPluginData.ajaxUrl, {
            method: 'POST',
            body: formData,
            // Không cần set Content-Type - FormData tự động set
            // credentials: 'same-origin' là mặc định cho same-origin requests
        });

        const data = await response.json();

        if (data.success) {
            showMessage('success', data.data.message);
        } else {
            showMessage('error', data.data.message);
        }
    } catch (error) {
        showMessage('error', 'Lỗi mạng: ' + error.message);
    }
}

// === Fetch với REST API (khuyến dùng cho REST endpoints) ===
async function fetchContacts() {
    try {
        const response = await fetch(myPluginData.restUrl + 'contacts', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': myPluginData.restNonce,  // Nonce cho REST API
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('HTTP Error: ' + response.status);
        }

        const data = await response.json();
        renderContacts(data);
    } catch (error) {
        console.error('Lỗi:', error);
    }
}

// === Fetch POST voi JSON body ===
async function createContact(contactData) {
    try {
        const response = await fetch(myPluginData.restUrl + 'contacts', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': myPluginData.restNonce,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(contactData),
        });

        const data = await response.json();

        if (response.ok) {
            showMessage('success', 'Đã tạo thành công!');
            return data;
        } else {
            showMessage('error', data.message || 'Lỗi không xác định');
            return null;
        }
    } catch (error) {
        showMessage('error', error.message);
        return null;
    }
}

// === Helper function ===
function showMessage(type, message) {
    const el = document.getElementById('ajax-message');
    el.className = 'notice notice-' + type;
    el.textContent = message;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 5000);
}
```

---

## 6. REST API trong Plugin

### REST API là gì?

WordPress REST API cung cấp **HTTP endpoints** để tương tác với dữ liệu. Mỗi endpoint trả về JSON. Đây là cách **hiện đại** và **chuyên nghiệp** hơn admin-ajax.php.

### So sánh AJAX vs REST API

```
+--------------------+------------------------+------------------------+
| Đặc điểm           | admin-ajax.php         | REST API               |
+--------------------+------------------------+------------------------+
| URL                | /wp-admin/admin-ajax   | /wp-json/namespace/v1/ |
| Method             | Chỉ POST               | GET, POST, PUT, DELETE |
| Response           | Tự định dạng           | JSON chuẩn             |
| Authentication     | Cookie + Nonce         | Nonce, OAuth, JWT      |
| Cacheable          | Khó                    | Dễ (GET request)       |
| Dùng cho           | Internal AJAX          | API cho bên ngoài      |
| RESTful            | Không                  | Có                     |
| Discoverable       | Không                  | Có (schema)            |
+--------------------+------------------------+------------------------+
```

### register_rest_route - Đăng ký endpoint

```php
<?php
/**
 * Plugin Name: REST API Demo
 * Description: Demo WordPress REST API.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Đăng ký REST API routes.
 * Hook 'rest_api_init' là nơi đăng ký tất cả custom endpoints.
 */
add_action( 'rest_api_init', 'rad_register_routes' );

function rad_register_routes() {
    /**
     * register_rest_route() - Đăng ký 1 REST endpoint
     *
     * @param string $namespace  Namespace (tên-plugin/version)
     * @param string $route      Đường dẫn endpoint
     * @param array  $args       Cấu hình: methods, callback, permission_callback, args
     */

    $namespace = 'rad/v1'; // Namespace: tên-plugin/version

    // GET /wp-json/rad/v1/hello
    register_rest_route( $namespace, '/hello', array(
        'methods'             => WP_REST_Server::READABLE,  // = 'GET'
        'callback'            => 'rad_hello_endpoint',
        'permission_callback' => '__return_true',  // Public (ai cũng gọi được)
    ));

    // GET /wp-json/rad/v1/posts
    register_rest_route( $namespace, '/posts', array(
        'methods'             => 'GET',
        'callback'            => 'rad_get_posts',
        'permission_callback' => '__return_true',
        'args'                => array(    // Validate query params
            'per_page' => array(
                'default'           => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $value ) {
                    return is_numeric( $value ) && $value > 0 && $value <= 100;
                },
            ),
            'page' => array(
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // POST /wp-json/rad/v1/contact (cần đăng nhập)
    register_rest_route( $namespace, '/contact', array(
        'methods'             => 'POST',
        'callback'            => 'rad_create_contact',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
        'args' => array(
            'name' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $value ) {
                    return ! empty( $value );
                },
            ),
            'email' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
        ),
    ));
}

// === CALLBACK FUNCTIONS ===

/**
 * Handler cho GET /wp-json/rad/v1/hello
 *
 * @param WP_REST_Request $request  Object chứa thông tin request
 * @return WP_REST_Response|WP_Error
 */
function rad_hello_endpoint( WP_REST_Request $request ) {
    return new WP_REST_Response( array(
        'message' => 'Hello từ REST API!',
        'time'    => current_time( 'mysql' ),
    ), 200 );
}

function rad_get_posts( WP_REST_Request $request ) {
    // Lấy params (đã được validate và sanitize từ 'args')
    $per_page = $request->get_param( 'per_page' );
    $page     = $request->get_param( 'page' );

    $query = new WP_Query( array(
        'post_type'      => 'post',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
    ));

    $posts = array();
    foreach ( $query->posts as $post ) {
        $posts[] = array(
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'excerpt'   => wp_trim_words( $post->post_content, 30 ),
            'date'      => $post->post_date,
            'permalink' => get_permalink( $post->ID ),
            'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'medium' ),
        );
    }

    $response = new WP_REST_Response( $posts, 200 );

    // Thêm headers cho pagination
    $response->header( 'X-WP-Total', $query->found_posts );
    $response->header( 'X-WP-TotalPages', $query->max_num_pages );

    return $response;
}

function rad_create_contact( WP_REST_Request $request ) {
    $name  = $request->get_param( 'name' );
    $email = $request->get_param( 'email' );

    // Xử lý logic lưu contact...
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'contacts',
        array( 'name' => $name, 'email' => $email ),
        array( '%s', '%s' )
    );

    if ( false === $result ) {
        return new WP_Error(
            'db_error',
            'Không thể tạo contact.',
            array( 'status' => 500 )
        );
    }

    return new WP_REST_Response( array(
        'id'      => $wpdb->insert_id,
        'name'    => $name,
        'email'   => $email,
        'message' => 'Đã tạo contact thành công!',
    ), 201 ); // 201 Created
}
```

---

## 7. Custom Endpoints

### REST Methods

```php
<?php
// WordPress REST Server Constants:
// WP_REST_Server::READABLE   = 'GET'
// WP_REST_Server::CREATABLE  = 'POST'
// WP_REST_Server::EDITABLE   = 'POST, PUT, PATCH'
// WP_REST_Server::DELETABLE  = 'DELETE'
// WP_REST_Server::ALLMETHODS  = 'GET, POST, PUT, PATCH, DELETE'
```

### CRUD Endpoints đầy đủ

```php
<?php
add_action( 'rest_api_init', 'my_register_crud_routes' );

function my_register_crud_routes() {
    $namespace = 'myplugin/v1';

    // GET /wp-json/myplugin/v1/items - Lấy danh sách
    register_rest_route( $namespace, '/items', array(
        'methods'             => 'GET',
        'callback'            => 'my_get_items',
        'permission_callback' => '__return_true',
        'args'                => my_get_collection_params(),
    ));

    // POST /wp-json/myplugin/v1/items - Tạo mới
    register_rest_route( $namespace, '/items', array(
        'methods'             => 'POST',
        'callback'            => 'my_create_item',
        'permission_callback' => 'my_check_admin_permission',
        'args'                => my_get_item_schema(),
    ));

    // GET /wp-json/myplugin/v1/items/123 - Lấy 1 item
    // (?P<id>\d+) là regex: bắt số nguyên làm param 'id'
    register_rest_route( $namespace, '/items/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'my_get_item',
        'permission_callback' => '__return_true',
        'args'                => array(
            'id' => array(
                'validate_callback' => function( $value ) {
                    return is_numeric( $value ) && $value > 0;
                },
            ),
        ),
    ));

    // PUT /wp-json/myplugin/v1/items/123 - Cập nhật
    register_rest_route( $namespace, '/items/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'my_update_item',
        'permission_callback' => 'my_check_admin_permission',
        'args'                => my_get_item_schema(),
    ));

    // DELETE /wp-json/myplugin/v1/items/123 - Xóa
    register_rest_route( $namespace, '/items/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'my_delete_item',
        'permission_callback' => 'my_check_admin_permission',
    ));

    // Nhiều methods cho 1 route (gom lại)
    register_rest_route( $namespace, '/items/(?P<id>\d+)', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'my_get_item',
            'permission_callback' => '__return_true',
        ),
        array(
            'methods'             => 'PUT',
            'callback'            => 'my_update_item',
            'permission_callback' => 'my_check_admin_permission',
        ),
        array(
            'methods'             => 'DELETE',
            'callback'            => 'my_delete_item',
            'permission_callback' => 'my_check_admin_permission',
        ),
    ));
}

// === PERMISSION CALLBACK ===
function my_check_admin_permission() {
    return current_user_can( 'manage_options' );
}

// === COLLECTION PARAMS (cho GET danh sách) ===
function my_get_collection_params() {
    return array(
        'page' => array(
            'default'           => 1,
            'sanitize_callback' => 'absint',
        ),
        'per_page' => array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
            'validate_callback' => function( $v ) {
                return $v >= 1 && $v <= 100;
            },
        ),
        'search' => array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'orderby' => array(
            'default'           => 'id',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $v ) {
                return in_array( $v, array( 'id', 'name', 'created_at' ) );
            },
        ),
        'order' => array(
            'default'           => 'desc',
            'validate_callback' => function( $v ) {
                return in_array( strtolower( $v ), array( 'asc', 'desc' ) );
            },
        ),
    );
}

// === ITEM SCHEMA (cho POST/PUT) ===
function my_get_item_schema() {
    return array(
        'name' => array(
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $v ) {
                return ! empty( $v ) && strlen( $v ) <= 200;
            },
            'description'       => 'Tên item',
        ),
        'email' => array(
            'required'          => true,
            'type'              => 'string',
            'format'            => 'email',
            'sanitize_callback' => 'sanitize_email',
            'validate_callback' => 'is_email',
        ),
        'status' => array(
            'default'           => 'active',
            'type'              => 'string',
            'enum'              => array( 'active', 'inactive' ),
            'sanitize_callback' => 'sanitize_text_field',
        ),
    );
}
```

---

## 8. Permission Callback và Schema Validation

### Permission Callback chi tiết

```php
<?php
/**
 * permission_callback CHẠY TRƯỚC callback.
 * Nếu trả về false hoặc WP_Error, request bị từ chối (401/403).
 *
 * QUAN TRỌNG: Mỗi route PHẢI có permission_callback.
 * Dùng __return_true cho public endpoints.
 */

// Public - ai cũng gọi được
'permission_callback' => '__return_true'

// Đăng nhập - bất kỳ user nào đã login
'permission_callback' => 'is_user_logged_in'

// Admin only
'permission_callback' => function() {
    return current_user_can( 'manage_options' );
}

// Editor trở lên
'permission_callback' => function() {
    return current_user_can( 'edit_posts' );
}

// Tác giả của item
'permission_callback' => function( WP_REST_Request $request ) {
    $item_id = $request->get_param( 'id' );
    $item = get_item( $item_id );
    return $item && $item->created_by === get_current_user_id();
}

// Admin HOẶC tác giả
'permission_callback' => function( WP_REST_Request $request ) {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }
    $item_id = $request->get_param( 'id' );
    $item = get_item( $item_id );
    return $item && $item->created_by === get_current_user_id();
}

// Kiểm tra nonce từ cookie-based auth
'permission_callback' => function() {
    return wp_verify_nonce(
        $_SERVER['HTTP_X_WP_NONCE'] ?? '',
        'wp_rest'
    );
}
```

### Schema Validation chi tiết

```php
<?php
/**
 * WordPress REST API hỗ trợ JSON Schema để validate dữ liệu.
 * Khai báo trong 'args' của register_rest_route.
 */

register_rest_route( 'myplugin/v1', '/items', array(
    'methods'  => 'POST',
    'callback' => 'create_item_handler',
    'permission_callback' => 'my_check_admin_permission',
    'args' => array(
        'title' => array(
            'required'          => true,
            'type'              => 'string',
            'description'       => 'Tiêu đề của item.',
            'minLength'         => 3,
            'maxLength'         => 200,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value, $request, $key ) {
                if ( strlen( $value ) < 3 ) {
                    return new WP_Error(
                        'too_short',
                        'Tiêu đề phải có ít nhất 3 ký tự.',
                        array( 'status' => 400 )
                    );
                }
                return true;
            },
        ),
        'price' => array(
            'required'          => true,
            'type'              => 'number',
            'minimum'           => 0,
            'maximum'           => 999999999,
            'sanitize_callback' => 'floatval',
        ),
        'status' => array(
            'type'    => 'string',
            'default' => 'draft',
            'enum'    => array( 'draft', 'published', 'archived' ),
            // enum: Chỉ chấp nhận các giá trị trong danh sách
        ),
        'tags' => array(
            'type'  => 'array',
            'items' => array(
                'type' => 'string',
            ),
            'default'           => array(),
            'sanitize_callback' => function( $value ) {
                return array_map( 'sanitize_text_field', (array) $value );
            },
        ),
        'metadata' => array(
            'type'       => 'object',
            'properties' => array(
                'color' => array( 'type' => 'string' ),
                'size'  => array(
                    'type' => 'string',
                    'enum' => array( 'S', 'M', 'L', 'XL' ),
                ),
            ),
        ),
    ),
));
```

---

## 9. Code ví dụ: CRUD API hoàn chỉnh

```php
<?php
/**
 * Plugin Name:       Contacts REST API
 * Description:       REST API CRUD hoàn chỉnh cho quản lý contacts.
 * Version:           1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Contacts_REST_Controller {

    private $namespace = 'contacts-api/v1';
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'api_contacts';

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Đăng ký tất cả REST routes
     */
    public function register_routes() {
        // GET    /wp-json/contacts-api/v1/contacts         - Danh sách
        // POST   /wp-json/contacts-api/v1/contacts         - Tạo mới
        // GET    /wp-json/contacts-api/v1/contacts/{id}     - Chi tiết
        // PUT    /wp-json/contacts-api/v1/contacts/{id}     - Cập nhật
        // DELETE /wp-json/contacts-api/v1/contacts/{id}     - Xóa
        // GET    /wp-json/contacts-api/v1/contacts/stats    - Thống kê

        // Danh sách + Tạo mới
        register_rest_route( $this->namespace, '/contacts', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => $this->get_item_params( true ),
            ),
        ));

        // Chi tiết + Cập nhật + Xóa
        register_rest_route( $this->namespace, '/contacts/(?P<id>\d+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => $this->get_item_params( false ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'check_delete_permission' ),
            ),
        ));

        // Thống kê
        register_rest_route( $this->namespace, '/contacts/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_stats' ),
            'permission_callback' => array( $this, 'check_read_permission' ),
        ));
    }

    // === PERMISSIONS ===

    public function check_read_permission() {
        return current_user_can( 'edit_posts' );
    }

    public function check_write_permission() {
        return current_user_can( 'manage_options' );
    }

    public function check_delete_permission() {
        return current_user_can( 'manage_options' );
    }

    // === GET ITEMS (Danh sách) ===

    public function get_items( WP_REST_Request $request ) {
        global $wpdb;

        $page     = $request->get_param( 'page' );
        $per_page = $request->get_param( 'per_page' );
        $search   = $request->get_param( 'search' );
        $status   = $request->get_param( 'status' );
        $orderby  = $request->get_param( 'orderby' );
        $order    = strtoupper( $request->get_param( 'order' ) );

        // Xây dựng query
        $where = "WHERE 1=1";
        $params = array();

        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty( $status ) ) {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        // Đếm tổng
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        $total = empty( $params )
            ? $wpdb->get_var( $count_sql )
            : $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        // Lấy dữ liệu
        $offset = ( $page - 1 ) * $per_page;
        $safe_orderby = in_array( $orderby, array( 'id', 'first_name', 'email', 'status', 'created_at' ) )
            ? $orderby : 'id';
        $safe_order = $order === 'ASC' ? 'ASC' : 'DESC';

        $data_sql = "SELECT * FROM {$this->table_name} {$where}
                     ORDER BY {$safe_orderby} {$safe_order}
                     LIMIT %d OFFSET %d";
        $all_params = array_merge( $params, array( $per_page, $offset ) );
        $items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$all_params ) );

        // Format response
        $data = array();
        foreach ( $items as $item ) {
            $data[] = $this->prepare_item( $item );
        }

        $response = new WP_REST_Response( $data, 200 );

        // Headers cho pagination
        $total_pages = ceil( $total / $per_page );
        $response->header( 'X-WP-Total', intval( $total ) );
        $response->header( 'X-WP-TotalPages', intval( $total_pages ) );

        return $response;
    }

    // === GET ITEM (Chi tiết) ===

    public function get_item( WP_REST_Request $request ) {
        global $wpdb;

        $id = absint( $request->get_param( 'id' ) );
        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $item ) {
            return new WP_Error(
                'not_found',
                'Không tìm thấy contact.',
                array( 'status' => 404 )
            );
        }

        return new WP_REST_Response( $this->prepare_item( $item ), 200 );
    }

    // === CREATE ITEM ===

    public function create_item( WP_REST_Request $request ) {
        global $wpdb;

        $data = array(
            'first_name' => $request->get_param( 'first_name' ),
            'last_name'  => $request->get_param( 'last_name' ),
            'email'      => $request->get_param( 'email' ),
            'phone'      => $request->get_param( 'phone' ),
            'company'    => $request->get_param( 'company' ),
            'status'     => $request->get_param( 'status' ) ?: 'lead',
            'created_by' => get_current_user_id(),
        );

        // Kiểm tra email trùng
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE email = %s",
                $data['email']
            )
        );

        if ( $exists > 0 ) {
            return new WP_Error(
                'duplicate_email',
                'Email đã tồn tại trong hệ thống.',
                array( 'status' => 409 )  // 409 Conflict
            );
        }

        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'db_error',
                'Lỗi database: ' . $wpdb->last_error,
                array( 'status' => 500 )
            );
        }

        // Lấy item vừa tạo
        $new_item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $wpdb->insert_id )
        );

        // Fire action để các plugin khác có thể hook vào
        do_action( 'contacts_api_contact_created', $new_item );

        return new WP_REST_Response( $this->prepare_item( $new_item ), 201 );
    }

    // === UPDATE ITEM ===

    public function update_item( WP_REST_Request $request ) {
        global $wpdb;

        $id = absint( $request->get_param( 'id' ) );

        // Kiểm tra tồn tại
        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $existing ) {
            return new WP_Error( 'not_found', 'Không tìm thấy contact.', array( 'status' => 404 ) );
        }

        // Chỉ update các trường được gửi (PATCH-like behavior)
        $update_data = array();
        $format = array();

        $fields = array( 'first_name', 'last_name', 'email', 'phone', 'company', 'status' );
        foreach ( $fields as $field ) {
            $value = $request->get_param( $field );
            if ( $value !== null ) {
                $update_data[ $field ] = $value;
                $format[] = '%s';
            }
        }

        if ( empty( $update_data ) ) {
            return new WP_Error( 'no_data', 'Không có dữ liệu để cập nhật.', array( 'status' => 400 ) );
        }

        // Kiểm tra email trùng (nếu đổi email)
        if ( isset( $update_data['email'] ) && $update_data['email'] !== $existing->email ) {
            $email_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE email = %s AND id != %d",
                    $update_data['email'],
                    $id
                )
            );
            if ( $email_exists > 0 ) {
                return new WP_Error( 'duplicate_email', 'Email đã tồn tại.', array( 'status' => 409 ) );
            }
        }

        $wpdb->update( $this->table_name, $update_data, array( 'id' => $id ), $format, array( '%d' ) );

        $updated_item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        do_action( 'contacts_api_contact_updated', $updated_item, $existing );

        return new WP_REST_Response( $this->prepare_item( $updated_item ), 200 );
    }

    // === DELETE ITEM ===

    public function delete_item( WP_REST_Request $request ) {
        global $wpdb;

        $id = absint( $request->get_param( 'id' ) );

        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $existing ) {
            return new WP_Error( 'not_found', 'Không tìm thấy contact.', array( 'status' => 404 ) );
        }

        $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );

        do_action( 'contacts_api_contact_deleted', $existing );

        return new WP_REST_Response( array(
            'deleted' => true,
            'id'      => $id,
            'message' => 'Đã xóa contact thành công.',
        ), 200 );
    }

    // === GET STATS ===

    public function get_stats() {
        global $wpdb;

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status='inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status='lead' THEN 1 ELSE 0 END) as leads,
                MAX(created_at) as last_created
             FROM {$this->table_name}"
        );

        return new WP_REST_Response( array(
            'total'        => intval( $stats->total ),
            'active'       => intval( $stats->active ),
            'inactive'     => intval( $stats->inactive ),
            'leads'        => intval( $stats->leads ),
            'last_created' => $stats->last_created,
        ), 200 );
    }

    // === HELPER: Format item cho response ===

    private function prepare_item( $item ) {
        $creator = get_userdata( $item->created_by );

        return array(
            'id'         => intval( $item->id ),
            'first_name' => $item->first_name,
            'last_name'  => $item->last_name,
            'full_name'  => trim( $item->first_name . ' ' . $item->last_name ),
            'email'      => $item->email,
            'phone'      => $item->phone,
            'company'    => $item->company,
            'status'     => $item->status,
            'created_by' => array(
                'id'   => intval( $item->created_by ),
                'name' => $creator ? $creator->display_name : 'Unknown',
            ),
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at ?? $item->created_at,
            '_links'     => array(
                'self' => rest_url( $this->namespace . '/contacts/' . $item->id ),
            ),
        );
    }

    // === PARAMS ===

    private function get_collection_params() {
        return array(
            'page'     => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
            'per_page' => array( 'default' => 10, 'sanitize_callback' => 'absint',
                'validate_callback' => function($v) { return $v >= 1 && $v <= 100; } ),
            'search'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'status'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($v) { return empty($v) || in_array($v, array('active','inactive','lead')); } ),
            'orderby'  => array( 'default' => 'id', 'sanitize_callback' => 'sanitize_text_field' ),
            'order'    => array( 'default' => 'desc', 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    private function get_item_params( $create = true ) {
        return array(
            'first_name' => array(
                'required' => $create, 'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($v) use ($create) {
                    if ( $create && empty($v) ) return new WP_Error('required', 'first_name là bắt buộc.');
                    return true;
                },
            ),
            'last_name' => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'email' => array(
                'required' => $create, 'sanitize_callback' => 'sanitize_email',
                'validate_callback' => function($v) use ($create) {
                    if ( $create && ! is_email($v) ) return new WP_Error('invalid', 'Email không hợp lệ.');
                    if ( ! $create && $v !== null && ! is_email($v) ) return new WP_Error('invalid', 'Email không hợp lệ.');
                    return true;
                },
            ),
            'phone'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'company' => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'status'  => array( 'default' => 'lead', 'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($v) { return in_array($v, array('active','inactive','lead')); } ),
        );
    }
}

// Khởi tạo
new Contacts_REST_Controller();

// === TEST VỚI cURL ===
// # Lấy danh sách
// curl -X GET "https://example.com/wp-json/contacts-api/v1/contacts?per_page=5" \
//      -H "X-WP-Nonce: YOUR_NONCE"
//
// # Tạo mới
// curl -X POST "https://example.com/wp-json/contacts-api/v1/contacts" \
//      -H "X-WP-Nonce: YOUR_NONCE" \
//      -H "Content-Type: application/json" \
//      -d '{"first_name":"Nguyen","last_name":"Van A","email":"a@b.com"}'
//
// # Cập nhật
// curl -X PUT "https://example.com/wp-json/contacts-api/v1/contacts/1" \
//      -H "X-WP-Nonce: YOUR_NONCE" \
//      -H "Content-Type: application/json" \
//      -d '{"first_name":"Tran"}'
//
// # Xóa
// curl -X DELETE "https://example.com/wp-json/contacts-api/v1/contacts/1" \
//      -H "X-WP-Nonce: YOUR_NONCE"
```

---

## 10. So sánh REST API với Route trong Laravel

```php
<?php
// === LARAVEL ===
// routes/api.php
// Route::apiResource('contacts', ContactController::class);
// Tự động tạo: GET, POST, PUT, DELETE

// Controller:
// class ContactController extends Controller {
//     public function index(Request $request) {
//         return Contact::paginate($request->per_page ?? 10);
//     }
//     public function store(StoreContactRequest $request) {
//         return Contact::create($request->validated());
//     }
//     public function show(Contact $contact) {
//         return $contact;  // Route Model Binding
//     }
//     public function update(UpdateContactRequest $request, Contact $contact) {
//         $contact->update($request->validated());
//         return $contact;
//     }
//     public function destroy(Contact $contact) {
//         $contact->delete();
//         return response()->noContent();
//     }
// }

// === WORDPRESS ===
// Không có Route file, đăng ký trong rest_api_init hook
// Không có Route Model Binding, tự query
// Không có Form Request, validate trong 'args'
```

### Bảng so sánh

| Tính năng | Laravel API | WordPress REST API |
|-----------|-----------|-------------------|
| **Định nghĩa route** | `routes/api.php` | `register_rest_route()` |
| **Resource route** | `Route::apiResource()` | Tự đăng ký từng route |
| **Controller** | Class riêng | Callback function/method |
| **Middleware** | Middleware classes | `permission_callback` |
| **Validation** | Form Request | `validate_callback` trong args |
| **Model Binding** | Tự động | Tự query |
| **Response** | `response()->json()` | `WP_REST_Response` |
| **Error** | `abort(404)` | `WP_Error` |
| **Authentication** | Sanctum/Passport | Cookie+Nonce, Application Passwords |
| **Rate Limiting** | ThrottleRequests | Không có sẵn (tự code) |
| **API Versioning** | URL prefix `/v1/` | Namespace: `plugin/v1` |
| **Pagination** | `->paginate()` | Tự code LIMIT/OFFSET |
| **CORS** | Middleware | `rest_pre_serve_request` filter |

---

## 11. Best Practices

### AJAX

```php
<?php
// 1. Luôn dùng nonce
check_ajax_referer( 'my_nonce', 'nonce' );

// 2. Luôn kiểm tra quyền
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
}

// 3. Luôn sanitize input
$name = sanitize_text_field( $_POST['name'] ?? '' );

// 4. Dùng wp_send_json_success/error (tự động die)
wp_send_json_success( $data );
// KHONG dung: echo json_encode($data); die();

// 5. Chỉ load JS khi cần
add_action( 'wp_enqueue_scripts', function() {
    if ( is_singular( 'product' ) ) {
        wp_enqueue_script( 'my-ajax-script' );
    }
});
```

### REST API

```php
<?php
// 1. Luôn có permission_callback (bảo mật)
// SAI:
register_rest_route( 'ns/v1', '/data', array(
    'methods'  => 'GET',
    'callback' => 'handler',
    // Thiếu permission_callback => WARNING
));

// DUNG:
register_rest_route( 'ns/v1', '/data', array(
    'methods'             => 'GET',
    'callback'            => 'handler',
    'permission_callback' => '__return_true', // Hoặc check cụ thể
));

// 2. Dùng namespace đúng chuẩn: tên-plugin/vN
// 'my-plugin/v1' (DUNG)
// 'v1/my-plugin' (SAI)

// 3. Trả về WP_REST_Response với status code phù hợp
// 200 = OK (GET, PUT, DELETE)
// 201 = Created (POST)
// 204 = No Content
// 400 = Bad Request
// 401 = Unauthorized
// 403 = Forbidden
// 404 = Not Found
// 409 = Conflict
// 500 = Server Error

// 4. Trả về WP_Error cho lỗi
return new WP_Error( 'error_code', 'Message', array( 'status' => 404 ) );

// 5. Validate và sanitize trong args (không trong callback)
'args' => array(
    'email' => array(
        'required'          => true,
        'sanitize_callback' => 'sanitize_email',
        'validate_callback' => 'is_email',
    ),
);
```

---

## Tham khảo

- [WordPress AJAX](https://developer.wordpress.org/plugins/javascript/ajax/)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [register_rest_route()](https://developer.wordpress.org/reference/functions/register_rest_route/)
- [WP_REST_Request](https://developer.wordpress.org/reference/classes/wp_rest_request/)
- [WP_REST_Response](https://developer.wordpress.org/reference/classes/wp_rest_response/)
- [REST API Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
