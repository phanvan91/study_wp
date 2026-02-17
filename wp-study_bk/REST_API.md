# WordPress REST API

Hướng dẫn toàn diện về WordPress REST API: từ các endpoint mặc định, authentication, đến việc tạo custom endpoints, controller, và xây dựng CRUD hoàn chỉnh trong plugin.

---

## Mục lục

1. [Giới thiệu REST API](#1-gioi-thieu-rest-api)
2. [Các endpoint mặc định](#2-cac-endpoint-mac-dinh)
3. [Sử dụng REST API - GET, POST, PUT, DELETE](#3-su-dung-rest-api---get-post-put-delete)
4. [Authentication](#4-authentication)
5. [Tạo Custom Endpoints - register_rest_route()](#5-tao-custom-endpoints---register_rest_route)
6. [Permission Callbacks](#6-permission-callbacks)
7. [Schema và Validation](#7-schema-va-validation)
8. [Custom Controller - Extend WP_REST_Controller](#8-custom-controller---extend-wp_rest_controller)
9. [REST API trong Plugin - Ví dụ CRUD hoàn chỉnh](#9-rest-api-trong-plugin---vi-du-crud-hoan-chinh)
10. [Best Practices](#10-best-practices)

---

## 1. Giới thiệu REST API

### 1.1. REST API là gì?

WordPress REST API cung cấp các HTTP endpoints cho phép truy cập dữ liệu WordPress theo chuẩn RESTful. Dữ liệu được gửi và nhận dưới dạng JSON.

Các khái niệm cơ bản:

- **Route**: Đường dẫn URL của API, ví dụ `/wp/v2/posts`.
- **Endpoint**: Một route kết hợp với HTTP method cụ thể, ví dụ `GET /wp/v2/posts` là một endpoint, `POST /wp/v2/posts` là một endpoint khác.
- **Namespace**: Phần đầu của route dùng để nhóm các endpoint, ví dụ `wp/v2` là namespace của WordPress core.
- **Request**: Đối tượng `WP_REST_Request` chứa thông tin về request (params, headers, body).
- **Response**: Đối tượng `WP_REST_Response` chứa dữ liệu trả về (data, status code, headers).
- **Schema**: Mô tả cấu trúc dữ liệu của endpoint (fields, types, validation rules).

### 1.2. URL cơ bản

```
https://example.com/wp-json/wp/v2/posts
|                  |       |  |  |     |
|   Domain         |wp-json|NS|v |Route|
```

- `wp-json`: REST API prefix (có thể thay đổi bằng filter `rest_url_prefix`)
- `wp/v2`: Namespace (wp = WordPress core, v2 = version 2)
- `posts`: Route name

### 1.3. Kiểm tra REST API có hoạt động

```bash
# Lấy thông tin API root
curl https://example.com/wp-json/

# Lấy danh sách routes
curl https://example.com/wp-json/wp/v2/

# Kiểm tra với OPTIONS method
curl -X OPTIONS https://example.com/wp-json/wp/v2/posts
```

### 1.4. Discovery

```html
<!-- WordPress tự động thêm link trong head -->
<link rel="https://api.w.org/" href="https://example.com/wp-json/" />
```

```php
<?php
// Lấy REST API URL trong PHP
$api_url = rest_url();           // https://example.com/wp-json/
$posts_url = rest_url( 'wp/v2/posts' ); // https://example.com/wp-json/wp/v2/posts
```

---

## 2. Các endpoint mặc định

### 2.1. /wp/v2/posts

Quản lý bài viết (post type = 'post').

```
GET    /wp/v2/posts          - Lấy danh sách bài viết
GET    /wp/v2/posts/<id>     - Lấy 1 bài viết theo ID
POST   /wp/v2/posts          - Tạo bài viết mới (cần xác thực)
PUT    /wp/v2/posts/<id>     - Cập nhật toàn bộ bài viết (cần xác thực)
PATCH  /wp/v2/posts/<id>     - Cập nhật một phần bài viết (cần xác thực)
DELETE /wp/v2/posts/<id>     - Xóa bài viết (cần xác thực)
```

Các tham số query phổ biến cho GET:

| Tham số | Mô tả | Ví dụ |
|---------|-------|-------|
| `page` | Trang hiện tại | `?page=2` |
| `per_page` | Số item mỗi trang (1-100, mặc định 10) | `?per_page=20` |
| `search` | Tìm kiếm | `?search=wordpress` |
| `after` | Bài viết sau ngày | `?after=2024-01-01T00:00:00` |
| `before` | Bài viết trước ngày | `?before=2024-12-31T23:59:59` |
| `author` | Theo ID tác giả | `?author=1` |
| `author_exclude` | Loại trừ tác giả | `?author_exclude=3,5` |
| `exclude` | Loại trừ post IDs | `?exclude=1,2,3` |
| `include` | Chỉ lấy post IDs | `?include=10,20,30` |
| `slug` | Theo slug | `?slug=bai-viet-mau` |
| `status` | Trạng thái | `?status=draft` (cần xác thực) |
| `categories` | Theo category IDs | `?categories=5,10` |
| `categories_exclude` | Loại trừ categories | `?categories_exclude=3` |
| `tags` | Theo tag IDs | `?tags=7,8` |
| `tags_exclude` | Loại trừ tags | `?tags_exclude=2` |
| `sticky` | Bài ghim | `?sticky=true` |
| `orderby` | Sắp xếp | `?orderby=title` |
| `order` | Thứ tự | `?order=asc` |
| `_fields` | Chỉ lấy các trường cụ thể | `?_fields=id,title,link` |
| `_embed` | Nhúng dữ liệu liên quan | `?_embed` |

### 2.2. /wp/v2/pages

Tương tự posts nhưng cho post type = 'page'.

```
GET    /wp/v2/pages          - Lấy danh sách trang
GET    /wp/v2/pages/<id>     - Lấy 1 trang
POST   /wp/v2/pages          - Tạo trang mới
PUT    /wp/v2/pages/<id>     - Cập nhật trang
DELETE /wp/v2/pages/<id>     - Xóa trang
```

Tham số riêng của pages:
- `parent`: ID trang cha (`?parent=10`)
- `menu_order`: Thứ tự menu (`?orderby=menu_order`)

### 2.3. /wp/v2/users

Quản lý người dùng.

```
GET    /wp/v2/users          - Lấy danh sách users
GET    /wp/v2/users/<id>     - Lấy 1 user
GET    /wp/v2/users/me       - Lấy user hiện tại (cần xác thực)
POST   /wp/v2/users          - Tạo user mới
PUT    /wp/v2/users/<id>     - Cập nhật user
DELETE /wp/v2/users/<id>     - Xóa user
```

Tham số:
- `roles`: Lọc theo role (`?roles=author,editor`)
- `slug`: Theo user slug
- `search`: Tìm kiếm

### 2.4. /wp/v2/categories

Quản lý categories.

```
GET    /wp/v2/categories          - Lấy danh sách
GET    /wp/v2/categories/<id>     - Lấy 1 category
POST   /wp/v2/categories          - Tạo mới
PUT    /wp/v2/categories/<id>     - Cập nhật
DELETE /wp/v2/categories/<id>     - Xóa
```

Tham số:
- `parent`: Category cha
- `post`: Lấy categories của 1 post
- `hide_empty`: Ẩn categories không có bài (`?hide_empty=true`)

### 2.5. /wp/v2/tags

Quản lý tags.

```
GET    /wp/v2/tags          - Lấy danh sách
GET    /wp/v2/tags/<id>     - Lấy 1 tag
POST   /wp/v2/tags          - Tạo mới
PUT    /wp/v2/tags/<id>     - Cập nhật
DELETE /wp/v2/tags/<id>     - Xóa
```

### 2.6. /wp/v2/comments

Quản lý bình luận.

```
GET    /wp/v2/comments          - Lấy danh sách
GET    /wp/v2/comments/<id>     - Lấy 1 comment
POST   /wp/v2/comments          - Tạo mới
PUT    /wp/v2/comments/<id>     - Cập nhật
DELETE /wp/v2/comments/<id>     - Xóa
```

Tham số:
- `post`: Comments của 1 post (`?post=42`)
- `parent`: Comment cha (reply)
- `author_email`: Theo email tác giả
- `status`: Trạng thái (`approve`, `hold`, `spam`, `trash`)

### 2.7. /wp/v2/media

Quản lý media (attachments).

```
GET    /wp/v2/media          - Lấy danh sách
GET    /wp/v2/media/<id>     - Lấy 1 media
POST   /wp/v2/media          - Upload file
PUT    /wp/v2/media/<id>     - Cập nhật thông tin
DELETE /wp/v2/media/<id>     - Xóa
```

Tham số:
- `media_type`: Loại media (`image`, `video`, `audio`, `application`)
- `mime_type`: MIME type (`image/jpeg`)

### 2.8. Các endpoint khác

```
/wp/v2/types               - Post types
/wp/v2/statuses            - Post statuses
/wp/v2/taxonomies          - Taxonomies
/wp/v2/search              - Tìm kiếm toàn cục
/wp/v2/settings            - Cài đặt site (cần admin)
/wp/v2/themes              - Themes
/wp/v2/plugins             - Plugins (cần admin)
/wp/v2/block-types         - Block types (Gutenberg)
/wp/v2/blocks              - Reusable blocks
```

---

## 3. Sử dụng REST API - GET, POST, PUT, DELETE

### 3.1. GET - Lấy dữ liệu với cURL

```bash
# Lấy 5 bài viết mới nhất
curl "https://example.com/wp-json/wp/v2/posts?per_page=5&orderby=date&order=desc"

# Lấy bài viết theo ID
curl "https://example.com/wp-json/wp/v2/posts/42"

# Tìm kiếm với nhiều tham số
curl "https://example.com/wp-json/wp/v2/posts?search=wordpress&categories=5&per_page=10&_fields=id,title,link"

# Lấy với embedded data (author, featured media, terms)
curl "https://example.com/wp-json/wp/v2/posts?_embed&per_page=5"

# Lấy chỉ một số trường
curl "https://example.com/wp-json/wp/v2/posts?_fields=id,title.rendered,link,date"

# Lấy headers để biết tổng số và số trang
curl -I "https://example.com/wp-json/wp/v2/posts?per_page=10"
# Response headers:
# X-WP-Total: 156        (tổng số bài)
# X-WP-TotalPages: 16    (tổng số trang)
```

### 3.2. GET - Lấy dữ liệu với JavaScript fetch

```javascript
// Lấy danh sách bài viết
async function getPosts(page = 1, perPage = 10) {
    const url = new URL('https://example.com/wp-json/wp/v2/posts');
    url.searchParams.append('page', page);
    url.searchParams.append('per_page', perPage);
    url.searchParams.append('_fields', 'id,title,excerpt,link,date,_links');
    url.searchParams.append('_embed', '');

    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const posts = await response.json();
        const total = response.headers.get('X-WP-Total');
        const totalPages = response.headers.get('X-WP-TotalPages');

        return {
            posts,
            total: parseInt(total),
            totalPages: parseInt(totalPages),
        };
    } catch (error) {
        console.error('Lỗi khi lấy bài viết:', error);
        throw error;
    }
}

// Sử dụng
getPosts(1, 5).then(result => {
    console.log(`Tổng: ${result.total} bài viết`);
    result.posts.forEach(post => {
        console.log(`- ${post.title.rendered}`);
    });
});

// Lấy 1 bài viết
async function getPost(id) {
    const response = await fetch(
        `https://example.com/wp-json/wp/v2/posts/${id}?_embed`
    );
    if (!response.ok) {
        throw new Error(`Post not found: ${response.status}`);
    }
    return response.json();
}

// Tìm kiếm
async function searchPosts(keyword) {
    const response = await fetch(
        `https://example.com/wp-json/wp/v2/posts?search=${encodeURIComponent(keyword)}&per_page=20`
    );
    return response.json();
}
```

### 3.3. POST - Tạo dữ liệu với cURL

```bash
# Tạo bài viết mới (cần xác thực)
curl -X POST "https://example.com/wp-json/wp/v2/posts" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Bài viết mới từ API",
        "content": "Nội dung bài viết được tạo từ REST API.",
        "status": "publish",
        "categories": [5, 10],
        "tags": [3, 7],
        "meta": {
            "custom_field": "giá trị"
        }
    }'

# Tạo bài viết draft
curl -X POST "https://example.com/wp-json/wp/v2/posts" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Bài nháp",
        "content": "Đang soạn...",
        "status": "draft"
    }'

# Upload media
curl -X POST "https://example.com/wp-json/wp/v2/media" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -H "Content-Disposition: attachment; filename=hinh-anh.jpg" \
    -H "Content-Type: image/jpeg" \
    --data-binary @/path/to/hinh-anh.jpg

# Tạo comment
curl -X POST "https://example.com/wp-json/wp/v2/comments" \
    -H "Content-Type: application/json" \
    -d '{
        "post": 42,
        "author_name": "Nguyễn Văn A",
        "author_email": "a@example.com",
        "content": "Bình luận từ API"
    }'
```

### 3.4. POST - Tạo dữ liệu với JavaScript

```javascript
// Tạo bài viết mới
async function createPost(data) {
    const response = await fetch('https://example.com/wp-json/wp/v2/posts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce,  // Nếu dùng cookie auth
            // HOẶC: 'Authorization': 'Basic ' + btoa('user:app-password')
        },
        body: JSON.stringify({
            title: data.title,
            content: data.content,
            status: data.status || 'draft',
            categories: data.categories || [],
            tags: data.tags || [],
        }),
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message);
    }

    return response.json();
}

// Sử dụng
createPost({
    title: 'Bài viết từ JavaScript',
    content: '<p>Nội dung bài viết.</p>',
    status: 'publish',
    categories: [5],
}).then(post => {
    console.log('Đã tạo bài viết ID:', post.id);
});

// Upload media
async function uploadMedia(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('title', file.name);
    formData.append('alt_text', 'Mô tả hình ảnh');

    const response = await fetch('https://example.com/wp-json/wp/v2/media', {
        method: 'POST',
        headers: {
            'X-WP-Nonce': wpApiSettings.nonce,
        },
        body: formData,  // KHÔNG đặt Content-Type, browser tự thêm với boundary
    });

    return response.json();
}
```

### 3.5. PUT/PATCH - Cập nhật dữ liệu

```bash
# Cập nhật toàn bộ (PUT)
curl -X PUT "https://example.com/wp-json/wp/v2/posts/42" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Tiêu đề đã sửa",
        "content": "Nội dung đã cập nhật.",
        "status": "publish"
    }'

# Cập nhật một phần (PATCH) - chỉ gửi trường cần sửa
curl -X PATCH "https://example.com/wp-json/wp/v2/posts/42" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Chỉ sửa tiêu đề thôi"
    }'
```

```javascript
// Cập nhật bài viết
async function updatePost(id, data) {
    const response = await fetch(`https://example.com/wp-json/wp/v2/posts/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce,
        },
        body: JSON.stringify(data),
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message);
    }

    return response.json();
}

// Cập nhật chỉ tiêu đề
updatePost(42, { title: 'Tiêu đề mới' }).then(post => {
    console.log('Đã cập nhật:', post.title.rendered);
});
```

### 3.6. DELETE - Xóa dữ liệu

```bash
# Xóa bài viết (chuyển vào trash)
curl -X DELETE "https://example.com/wp-json/wp/v2/posts/42" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ="

# Xóa vĩnh viễn (bỏ qua trash)
curl -X DELETE "https://example.com/wp-json/wp/v2/posts/42?force=true" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ="
```

```javascript
// Xóa bài viết
async function deletePost(id, force = false) {
    const url = `https://example.com/wp-json/wp/v2/posts/${id}${force ? '?force=true' : ''}`;

    const response = await fetch(url, {
        method: 'DELETE',
        headers: {
            'X-WP-Nonce': wpApiSettings.nonce,
        },
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message);
    }

    return response.json();
}

// Chuyển vào trash
deletePost(42).then(() => console.log('Đã chuyển vào trash'));

// Xóa vĩnh viễn
deletePost(42, true).then(() => console.log('Đã xóa vĩnh viễn'));
```

### 3.7. Sử dụng REST API trong PHP (nội bộ)

```php
<?php
// Gọi REST API nội bộ (internal request) - không cần HTTP request
$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
$request->set_query_params( array(
    'per_page' => 5,
    'status'   => 'publish',
) );

$response = rest_do_request( $request );
$data     = rest_get_server()->response_to_data( $response, false );

foreach ( $data as $post ) {
    echo $post['title']['rendered'] . "\n";
}

// Tạo bài viết qua internal request
$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
$request->set_body_params( array(
    'title'   => 'Bài viết nội bộ',
    'content' => 'Nội dung...',
    'status'  => 'publish',
) );
// Set user hiện tại để có quyền
$request->set_param( 'author', get_current_user_id() );

$response = rest_do_request( $request );

if ( $response->is_error() ) {
    $error = $response->as_error();
    echo 'Lỗi: ' . $error->get_error_message();
} else {
    $post = $response->get_data();
    echo 'Đã tạo post ID: ' . $post['id'];
}

// Sử dụng wp_remote_get/post cho external request
$response = wp_remote_get( rest_url( 'wp/v2/posts?per_page=5' ), array(
    'headers' => array(
        'Authorization' => 'Basic ' . base64_encode( 'user:app-password' ),
    ),
) );

if ( ! is_wp_error( $response ) ) {
    $body  = wp_remote_retrieve_body( $response );
    $posts = json_decode( $body );
}
```

---

## 4. Authentication

### 4.1. Cookie Authentication

Dành cho các request từ trong WordPress (cùng domain). Sử dụng nonce để xác thực.

```php
<?php
// Đăng ký script với nonce
add_action( 'wp_enqueue_scripts', 'my_enqueue_api_scripts' );
function my_enqueue_api_scripts() {
    wp_enqueue_script(
        'my-api-script',
        get_template_directory_uri() . '/js/api.js',
        array(),
        '1.0',
        true
    );

    // Truyền nonce và URL sang JavaScript
    wp_localize_script( 'my-api-script', 'wpApiSettings', array(
        'root'  => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
    ) );
}
```

```javascript
// Sử dụng nonce trong JavaScript
fetch(wpApiSettings.root + 'wp/v2/posts', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce,  // Gửi nonce trong header
    },
    body: JSON.stringify({
        title: 'Bài viết mới',
        status: 'draft',
    }),
});

// Hoặc gửi nonce như query parameter
fetch(wpApiSettings.root + 'wp/v2/posts?_wpnonce=' + wpApiSettings.nonce);
```

### 4.2. Application Passwords

Có sẵn từ WordPress 5.6. Tạo password riêng cho từng ứng dụng, không dùng password chính.

```php
<?php
// Tạo application password qua code
$user_id = 1;
$app_name = 'My Mobile App';

$result = WP_Application_Passwords::create_new_application_password(
    $user_id,
    array( 'name' => $app_name )
);

if ( ! is_wp_error( $result ) ) {
    $password = $result[0]; // Mật khẩu mới (chỉ hiển thị 1 lần)
    $item     = $result[1]; // Thông tin application password
}
```

```bash
# Sử dụng Application Password với cURL
# Format: username:application-password (có dấu cách, vd: "xxxx xxxx xxxx xxxx")
curl -X GET "https://example.com/wp-json/wp/v2/posts?status=draft" \
    -u "admin:XXXX XXXX XXXX XXXX XXXX XXXX"

# Hoặc sử dụng Basic Auth header
# Base64 encode "admin:XXXX XXXX XXXX XXXX XXXX XXXX"
curl -X GET "https://example.com/wp-json/wp/v2/posts?status=draft" \
    -H "Authorization: Basic YWRtaW46WFhYWCBYWFhYIFhYWFggWFhYWCBYWFhYIFhYWFg="
```

```javascript
// Sử dụng Application Password trong JavaScript
const username = 'admin';
const appPassword = 'XXXX XXXX XXXX XXXX XXXX XXXX';
const credentials = btoa(`${username}:${appPassword}`);

fetch('https://example.com/wp-json/wp/v2/posts', {
    headers: {
        'Authorization': `Basic ${credentials}`,
    },
});
```

### 4.3. JWT (JSON Web Token)

Cần plugin hỗ trợ (ví dụ: JWT Authentication for WP REST API).

```php
<?php
// Cấu hình trong wp-config.php
define( 'JWT_AUTH_SECRET_KEY', 'your-secret-key-here' );
define( 'JWT_AUTH_CORS_ENABLE', true );
```

```bash
# Bước 1: Lấy token
curl -X POST "https://example.com/wp-json/jwt-auth/v1/token" \
    -H "Content-Type: application/json" \
    -d '{
        "username": "admin",
        "password": "your-password"
    }'
# Response: { "token": "eyJ0eXAi...", "user_email": "...", ... }

# Bước 2: Sử dụng token
curl -X GET "https://example.com/wp-json/wp/v2/posts?status=draft" \
    -H "Authorization: Bearer eyJ0eXAi..."

# Validate token
curl -X POST "https://example.com/wp-json/jwt-auth/v1/token/validate" \
    -H "Authorization: Bearer eyJ0eXAi..."
```

```javascript
// JWT flow trong JavaScript
class WPApiClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.token = null;
    }

    async login(username, password) {
        const response = await fetch(`${this.baseUrl}/wp-json/jwt-auth/v1/token`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password }),
        });

        if (!response.ok) {
            throw new Error('Đăng nhập thất bại');
        }

        const data = await response.json();
        this.token = data.token;
        return data;
    }

    async request(endpoint, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers,
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const response = await fetch(`${this.baseUrl}/wp-json/${endpoint}`, {
            ...options,
            headers,
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Request failed');
        }

        return response.json();
    }

    async getPosts(params = {}) {
        const query = new URLSearchParams(params).toString();
        return this.request(`wp/v2/posts?${query}`);
    }

    async createPost(data) {
        return this.request('wp/v2/posts', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }
}

// Sử dụng
const api = new WPApiClient('https://example.com');
await api.login('admin', 'password');
const posts = await api.getPosts({ per_page: 5 });
```

### 4.4. Custom Authentication

```php
<?php
// Thêm authentication method riêng
add_filter( 'rest_authentication_errors', 'my_custom_rest_authentication' );
function my_custom_rest_authentication( $result ) {
    // Nếu đã có kết quả từ authentication khác, không làm gì
    if ( ! is_null( $result ) ) {
        return $result;
    }

    // Kiểm tra API key trong header
    $api_key = isset( $_SERVER['HTTP_X_API_KEY'] ) ? $_SERVER['HTTP_X_API_KEY'] : '';

    if ( empty( $api_key ) ) {
        return $result; // Không có API key, để authentication khác xử lý
    }

    // Xác thực API key
    $user_id = my_validate_api_key( $api_key );
    if ( $user_id ) {
        wp_set_current_user( $user_id );
        return true;
    }

    return new WP_Error(
        'rest_invalid_api_key',
        'API key không hợp lệ.',
        array( 'status' => 401 )
    );
}

function my_validate_api_key( $api_key ) {
    global $wpdb;
    return $wpdb->get_var(
        $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = '_api_key' AND meta_value = %s",
            $api_key
        )
    );
}
```

---

## 5. Tạo Custom Endpoints - register_rest_route()

### 5.1. Endpoint đơn giản

```php
<?php
add_action( 'rest_api_init', 'my_register_custom_routes' );

function my_register_custom_routes() {

    // GET /wp-json/myplugin/v1/hello
    register_rest_route( 'myplugin/v1', '/hello', array(
        'methods'             => WP_REST_Server::READABLE,  // = 'GET'
        'callback'            => 'my_hello_callback',
        'permission_callback' => '__return_true',  // Cho phép tất cả truy cập
    ) );
}

function my_hello_callback( WP_REST_Request $request ) {
    return new WP_REST_Response( array(
        'message' => 'Xin chào từ REST API!',
        'time'    => current_time( 'mysql' ),
    ), 200 );
}
```

### 5.2. Endpoint với tham số

```php
<?php
add_action( 'rest_api_init', 'my_register_routes_with_params' );

function my_register_routes_with_params() {

    // GET /wp-json/myplugin/v1/products?category=dien-thoai&page=1
    register_rest_route( 'myplugin/v1', '/products', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'my_get_products',
        'permission_callback' => '__return_true',
        'args'                => array(
            'category' => array(
                'required'          => false,
                'type'              => 'string',
                'description'       => 'Slug danh mục sản phẩm',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'page' => array(
                'required'          => false,
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'required'          => false,
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );

    // GET /wp-json/myplugin/v1/products/42
    register_rest_route( 'myplugin/v1', '/products/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'my_get_single_product',
        'permission_callback' => '__return_true',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'type'              => 'integer',
                'description'       => 'ID sản phẩm',
                'validate_callback' => function( $value ) {
                    return is_numeric( $value ) && $value > 0;
                },
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );
}

function my_get_products( WP_REST_Request $request ) {
    $category = $request->get_param( 'category' );
    $page     = $request->get_param( 'page' );
    $per_page = $request->get_param( 'per_page' );

    $query_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    );

    if ( ! empty( $category ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $query = new WP_Query( $query_args );
    $products = array();

    foreach ( $query->posts as $post ) {
        $products[] = array(
            'id'    => $post->ID,
            'title' => $post->post_title,
            'slug'  => $post->post_name,
            'price' => get_post_meta( $post->ID, '_price', true ),
            'image' => get_the_post_thumbnail_url( $post->ID, 'medium' ),
            'link'  => get_permalink( $post->ID ),
        );
    }

    $response = new WP_REST_Response( $products, 200 );

    // Thêm headers pagination
    $response->header( 'X-WP-Total', $query->found_posts );
    $response->header( 'X-WP-TotalPages', $query->max_num_pages );

    return $response;
}

function my_get_single_product( WP_REST_Request $request ) {
    $id   = $request->get_param( 'id' );
    $post = get_post( $id );

    if ( ! $post || 'product' !== $post->post_type ) {
        return new WP_Error(
            'product_not_found',
            'Không tìm thấy sản phẩm.',
            array( 'status' => 404 )
        );
    }

    return new WP_REST_Response( array(
        'id'          => $post->ID,
        'title'       => $post->post_title,
        'content'     => apply_filters( 'the_content', $post->post_content ),
        'slug'        => $post->post_name,
        'price'       => get_post_meta( $post->ID, '_price', true ),
        'image'       => get_the_post_thumbnail_url( $post->ID, 'full' ),
        'categories'  => wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) ),
        'link'        => get_permalink( $post->ID ),
        'date'        => $post->post_date,
    ), 200 );
}
```

### 5.3. Endpoint với nhiều methods

```php
<?php
add_action( 'rest_api_init', 'my_register_crud_routes' );

function my_register_crud_routes() {

    // Collection route: GET (list) và POST (create)
    register_rest_route( 'myplugin/v1', '/items', array(
        array(
            'methods'             => WP_REST_Server::READABLE,   // GET
            'callback'            => 'my_get_items',
            'permission_callback' => '__return_true',
        ),
        array(
            'methods'             => WP_REST_Server::CREATABLE,  // POST
            'callback'            => 'my_create_item',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args' => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'value' => array(
                    'required' => true,
                    'type'     => 'number',
                    'sanitize_callback' => 'floatval',
                ),
            ),
        ),
    ) );

    // Single item route: GET, PUT, DELETE
    register_rest_route( 'myplugin/v1', '/items/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE,   // GET
            'callback'            => 'my_get_item',
            'permission_callback' => '__return_true',
        ),
        array(
            'methods'             => WP_REST_Server::EDITABLE,   // PUT, PATCH
            'callback'            => 'my_update_item',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ),
        array(
            'methods'             => WP_REST_Server::DELETABLE,  // DELETE
            'callback'            => 'my_delete_item',
            'permission_callback' => function() {
                return current_user_can( 'delete_posts' );
            },
        ),
    ) );
}
```

### 5.4. HTTP Methods Constants

```php
<?php
// WP_REST_Server constants
WP_REST_Server::READABLE   = 'GET';
WP_REST_Server::CREATABLE  = 'POST';
WP_REST_Server::EDITABLE   = 'POST, PUT, PATCH';
WP_REST_Server::DELETABLE  = 'DELETE';
WP_REST_Server::ALLMETHODS  = 'GET, POST, PUT, PATCH, DELETE';
```

---

## 6. Permission Callbacks

### 6.1. Các loại permission callback

```php
<?php
add_action( 'rest_api_init', 'my_register_permission_routes' );

function my_register_permission_routes() {

    // 1. Cho phép tất cả (public)
    register_rest_route( 'myplugin/v1', '/public-data', array(
        'methods'             => 'GET',
        'callback'            => 'my_public_callback',
        'permission_callback' => '__return_true',
    ) );

    // 2. Yêu cầu đăng nhập
    register_rest_route( 'myplugin/v1', '/private-data', array(
        'methods'             => 'GET',
        'callback'            => 'my_private_callback',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ) );

    // 3. Yêu cầu capability cụ thể
    register_rest_route( 'myplugin/v1', '/admin-data', array(
        'methods'             => 'GET',
        'callback'            => 'my_admin_callback',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );

    // 4. Kiểm tra quyền trên đối tượng cụ thể
    register_rest_route( 'myplugin/v1', '/posts/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'my_update_post_callback',
        'permission_callback' => function( WP_REST_Request $request ) {
            $post_id = $request->get_param( 'id' );
            return current_user_can( 'edit_post', $post_id );
        },
    ) );

    // 5. Kiểm tra nhiều điều kiện
    register_rest_route( 'myplugin/v1', '/restricted', array(
        'methods'             => 'POST',
        'callback'            => 'my_restricted_callback',
        'permission_callback' => 'my_check_multiple_permissions',
    ) );
}

function my_check_multiple_permissions( WP_REST_Request $request ) {
    // Phải đăng nhập
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            'Bạn cần đăng nhập để thực hiện thao tác này.',
            array( 'status' => 401 )
        );
    }

    // Phải có quyền edit_posts
    if ( ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error(
            'rest_forbidden',
            'Bạn không có quyền thực hiện thao tác này.',
            array( 'status' => 403 )
        );
    }

    // Kiểm tra rate limiting (ví dụ)
    $user_id    = get_current_user_id();
    $last_action = get_user_meta( $user_id, '_last_api_action', true );
    if ( $last_action && ( time() - $last_action ) < 60 ) {
        return new WP_Error(
            'rest_rate_limited',
            'Vui lòng đợi 60 giây giữa các request.',
            array( 'status' => 429 )
        );
    }

    return true;
}
```

### 6.2. Permission cho owner

```php
<?php
// Chỉ cho phép user chỉnh sửa dữ liệu của chính mình
register_rest_route( 'myplugin/v1', '/profile', array(
    array(
        'methods'             => 'GET',
        'callback'            => 'my_get_profile',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ),
    array(
        'methods'             => 'PUT',
        'callback'            => 'my_update_profile',
        'permission_callback' => function( WP_REST_Request $request ) {
            if ( ! is_user_logged_in() ) {
                return false;
            }
            // Admin có thể sửa bất kỳ ai
            if ( current_user_can( 'manage_options' ) ) {
                return true;
            }
            // User thường chỉ sửa của mình
            $target_user_id = $request->get_param( 'user_id' );
            return get_current_user_id() === intval( $target_user_id );
        },
    ),
) );
```

---

## 7. Schema và Validation

### 7.1. Định nghĩa Schema

```php
<?php
add_action( 'rest_api_init', 'my_register_schema_route' );

function my_register_schema_route() {
    register_rest_route( 'myplugin/v1', '/contacts', array(
        array(
            'methods'             => 'POST',
            'callback'            => 'my_create_contact',
            'permission_callback' => '__return_true',
            'args'                => my_get_contact_args(),
        ),
        'schema' => 'my_get_contact_schema',
    ) );
}

function my_get_contact_schema() {
    return array(
        '$schema'    => 'http://json-schema.org/draft-04/schema#',
        'title'      => 'contact',
        'type'       => 'object',
        'properties' => array(
            'id' => array(
                'description' => 'ID duy nhất của liên hệ.',
                'type'        => 'integer',
                'context'     => array( 'view', 'edit' ),
                'readonly'    => true,
            ),
            'name' => array(
                'description' => 'Họ tên liên hệ.',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
                'required'    => true,
            ),
            'email' => array(
                'description' => 'Địa chỉ email.',
                'type'        => 'string',
                'format'      => 'email',
                'context'     => array( 'view', 'edit' ),
                'required'    => true,
            ),
            'phone' => array(
                'description' => 'Số điện thoại.',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
            ),
            'message' => array(
                'description' => 'Nội dung liên hệ.',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
                'required'    => true,
            ),
            'status' => array(
                'description' => 'Trạng thái liên hệ.',
                'type'        => 'string',
                'enum'        => array( 'new', 'read', 'replied', 'closed' ),
                'default'     => 'new',
                'context'     => array( 'view', 'edit' ),
            ),
            'created_at' => array(
                'description' => 'Ngày tạo.',
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => array( 'view' ),
                'readonly'    => true,
            ),
        ),
    );
}
```

### 7.2. Validate và Sanitize Callbacks

```php
<?php
function my_get_contact_args() {
    return array(
        'name' => array(
            'required'          => true,
            'type'              => 'string',
            'description'       => 'Họ tên liên hệ',
            'validate_callback' => function( $value, $request, $param ) {
                if ( strlen( $value ) < 2 ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        'Tên phải có ít nhất 2 ký tự.',
                        array( 'status' => 400 )
                    );
                }
                if ( strlen( $value ) > 100 ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        'Tên không được quá 100 ký tự.',
                        array( 'status' => 400 )
                    );
                }
                return true;
            },
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'email' => array(
            'required'          => true,
            'type'              => 'string',
            'format'            => 'email',
            'description'       => 'Địa chỉ email',
            'validate_callback' => function( $value ) {
                if ( ! is_email( $value ) ) {
                    return new WP_Error(
                        'rest_invalid_email',
                        'Địa chỉ email không hợp lệ.',
                        array( 'status' => 400 )
                    );
                }
                return true;
            },
            'sanitize_callback' => 'sanitize_email',
        ),
        'phone' => array(
            'required'          => false,
            'type'              => 'string',
            'description'       => 'Số điện thoại',
            'validate_callback' => function( $value ) {
                if ( ! empty( $value ) && ! preg_match( '/^[0-9\+\-\s\(\)]{8,20}$/', $value ) ) {
                    return new WP_Error(
                        'rest_invalid_phone',
                        'Số điện thoại không hợp lệ.',
                        array( 'status' => 400 )
                    );
                }
                return true;
            },
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'message' => array(
            'required'          => true,
            'type'              => 'string',
            'description'       => 'Nội dung liên hệ',
            'validate_callback' => function( $value ) {
                if ( strlen( $value ) < 10 ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        'Nội dung phải có ít nhất 10 ký tự.',
                        array( 'status' => 400 )
                    );
                }
                return true;
            },
            'sanitize_callback' => 'wp_kses_post',
        ),
        'status' => array(
            'required'          => false,
            'type'              => 'string',
            'default'           => 'new',
            'enum'              => array( 'new', 'read', 'replied', 'closed' ),
            'description'       => 'Trạng thái liên hệ',
            'sanitize_callback' => 'sanitize_text_field',
        ),
    );
}

function my_create_contact( WP_REST_Request $request ) {
    global $wpdb;

    $result = $wpdb->insert(
        $wpdb->prefix . 'contacts',
        array(
            'name'       => $request->get_param( 'name' ),
            'email'      => $request->get_param( 'email' ),
            'phone'      => $request->get_param( 'phone' ),
            'message'    => $request->get_param( 'message' ),
            'status'     => $request->get_param( 'status' ),
            'created_at' => current_time( 'mysql' ),
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    if ( false === $result ) {
        return new WP_Error(
            'rest_db_error',
            'Không thể lưu liên hệ.',
            array( 'status' => 500 )
        );
    }

    $contact = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contacts WHERE id = %d",
            $wpdb->insert_id
        )
    );

    return new WP_REST_Response( $contact, 201 );
}
```

### 7.3. register_rest_field() - Thêm trường vào endpoint có sẵn

```php
<?php
add_action( 'rest_api_init', 'my_register_custom_fields' );

function my_register_custom_fields() {

    // Thêm trường 'view_count' vào endpoint /wp/v2/posts
    register_rest_field( 'post', 'view_count', array(
        'get_callback'    => function( $post_arr ) {
            return (int) get_post_meta( $post_arr['id'], '_view_count', true );
        },
        'update_callback' => function( $value, $post ) {
            return update_post_meta( $post->ID, '_view_count', absint( $value ) );
        },
        'schema'          => array(
            'description' => 'Số lượt xem bài viết.',
            'type'        => 'integer',
            'context'     => array( 'view', 'edit' ),
        ),
    ) );

    // Thêm trường 'featured_image_url' vào endpoint posts
    register_rest_field( 'post', 'featured_image_url', array(
        'get_callback' => function( $post_arr ) {
            $image_id = $post_arr['featured_media'];
            if ( $image_id ) {
                $image = wp_get_attachment_image_src( $image_id, 'full' );
                return $image ? $image[0] : null;
            }
            return null;
        },
        'schema' => array(
            'description' => 'URL ảnh đại diện.',
            'type'        => 'string',
            'format'      => 'uri',
            'context'     => array( 'view' ),
            'readonly'    => true,
        ),
    ) );

    // Thêm trường vào nhiều post types cùng lúc
    $post_types = array( 'post', 'page', 'product' );
    foreach ( $post_types as $post_type ) {
        register_rest_field( $post_type, 'reading_time', array(
            'get_callback' => function( $post_arr ) {
                $content   = strip_tags( $post_arr['content']['rendered'] );
                $word_count = str_word_count( $content );
                $minutes   = max( 1, ceil( $word_count / 200 ) );
                return $minutes;
            },
            'schema' => array(
                'description' => 'Thời gian đọc ước tính (phút).',
                'type'        => 'integer',
                'context'     => array( 'view' ),
                'readonly'    => true,
            ),
        ) );
    }

    // Thêm trường vào endpoint users
    register_rest_field( 'user', 'social_links', array(
        'get_callback' => function( $user_arr ) {
            return array(
                'facebook'  => get_user_meta( $user_arr['id'], 'facebook', true ),
                'twitter'   => get_user_meta( $user_arr['id'], 'twitter', true ),
                'instagram' => get_user_meta( $user_arr['id'], 'instagram', true ),
            );
        },
        'update_callback' => function( $value, $user ) {
            if ( isset( $value['facebook'] ) ) {
                update_user_meta( $user->ID, 'facebook', esc_url_raw( $value['facebook'] ) );
            }
            if ( isset( $value['twitter'] ) ) {
                update_user_meta( $user->ID, 'twitter', esc_url_raw( $value['twitter'] ) );
            }
            if ( isset( $value['instagram'] ) ) {
                update_user_meta( $user->ID, 'instagram', esc_url_raw( $value['instagram'] ) );
            }
        },
        'schema' => array(
            'description' => 'Liên kết mạng xã hội.',
            'type'        => 'object',
            'properties'  => array(
                'facebook'  => array( 'type' => 'string', 'format' => 'uri' ),
                'twitter'   => array( 'type' => 'string', 'format' => 'uri' ),
                'instagram' => array( 'type' => 'string', 'format' => 'uri' ),
            ),
            'context' => array( 'view', 'edit' ),
        ),
    ) );
}
```

---

## 8. Custom Controller - Extend WP_REST_Controller

Khi cần tạo nhiều endpoints liên quan, nên sử dụng WP_REST_Controller để tổ chức code tốt hơn.

```php
<?php
/**
 * REST Controller cho Bookmarks
 */
class My_Bookmarks_REST_Controller extends WP_REST_Controller {

    protected $namespace = 'myplugin/v1';
    protected $rest_base = 'bookmarks';

    /**
     * Đăng ký routes
     */
    public function register_routes() {

        // GET, POST /myplugin/v1/bookmarks
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        // GET, PUT, DELETE /myplugin/v1/bookmarks/<id>
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
                'args'                => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'ID của bookmark.',
                    ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'delete_item_permissions_check' ),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );
    }

    /**
     * Lấy danh sách bookmarks
     */
    public function get_items( $request ) {
        global $wpdb;

        $per_page = $request->get_param( 'per_page' );
        $page     = $request->get_param( 'page' );
        $search   = $request->get_param( 'search' );
        $offset   = ( $page - 1 ) * $per_page;

        $where  = 'WHERE user_id = %d';
        $values = array( get_current_user_id() );

        if ( ! empty( $search ) ) {
            $where   .= ' AND title LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $table = $wpdb->prefix . 'bookmarks';

        // Đếm tổng
        $total = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $values )
        );

        // Lấy dữ liệu
        $all_values    = array_merge( $values, array( $per_page, $offset ) );
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $all_values
            )
        );

        $data = array();
        foreach ( $items as $item ) {
            $data[] = $this->prepare_item_for_response( $item, $request )->get_data();
        }

        $response = new WP_REST_Response( $data, 200 );
        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', ceil( $total / $per_page ) );

        return $response;
    }

    /**
     * Lấy 1 bookmark
     */
    public function get_item( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookmarks';

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request['id'] )
        );

        if ( ! $item ) {
            return new WP_Error(
                'rest_bookmark_not_found',
                'Không tìm thấy bookmark.',
                array( 'status' => 404 )
            );
        }

        return $this->prepare_item_for_response( $item, $request );
    }

    /**
     * Tạo bookmark mới
     */
    public function create_item( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookmarks';

        $result = $wpdb->insert( $table, array(
            'user_id'    => get_current_user_id(),
            'title'      => $request->get_param( 'title' ),
            'url'        => $request->get_param( 'url' ),
            'notes'      => $request->get_param( 'notes' ),
            'created_at' => current_time( 'mysql' ),
        ), array( '%d', '%s', '%s', '%s', '%s' ) );

        if ( false === $result ) {
            return new WP_Error( 'rest_db_error', 'Không thể tạo bookmark.', array( 'status' => 500 ) );
        }

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id )
        );

        $response = $this->prepare_item_for_response( $item, $request );
        $response->set_status( 201 );
        $response->header( 'Location', rest_url( "{$this->namespace}/{$this->rest_base}/{$item->id}" ) );

        return $response;
    }

    /**
     * Cập nhật bookmark
     */
    public function update_item( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookmarks';

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request['id'] )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_bookmark_not_found', 'Không tìm thấy bookmark.', array( 'status' => 404 ) );
        }

        $update_data   = array();
        $update_format = array();

        if ( $request->has_param( 'title' ) ) {
            $update_data['title'] = $request->get_param( 'title' );
            $update_format[]      = '%s';
        }
        if ( $request->has_param( 'url' ) ) {
            $update_data['url'] = $request->get_param( 'url' );
            $update_format[]    = '%s';
        }
        if ( $request->has_param( 'notes' ) ) {
            $update_data['notes'] = $request->get_param( 'notes' );
            $update_format[]      = '%s';
        }

        if ( ! empty( $update_data ) ) {
            $wpdb->update( $table, $update_data, array( 'id' => $request['id'] ), $update_format, array( '%d' ) );
        }

        $updated_item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request['id'] )
        );

        return $this->prepare_item_for_response( $updated_item, $request );
    }

    /**
     * Xóa bookmark
     */
    public function delete_item( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookmarks';

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request['id'] )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_bookmark_not_found', 'Không tìm thấy bookmark.', array( 'status' => 404 ) );
        }

        $response = $this->prepare_item_for_response( $item, $request );

        $wpdb->delete( $table, array( 'id' => $request['id'] ), array( '%d' ) );

        $data = $response->get_data();
        $data['deleted'] = true;

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Kiểm tra quyền
     */
    public function get_items_permissions_check( $request ) {
        return is_user_logged_in();
    }

    public function get_item_permissions_check( $request ) {
        return $this->check_ownership( $request );
    }

    public function create_item_permissions_check( $request ) {
        return is_user_logged_in();
    }

    public function update_item_permissions_check( $request ) {
        return $this->check_ownership( $request );
    }

    public function delete_item_permissions_check( $request ) {
        return $this->check_ownership( $request );
    }

    private function check_ownership( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_not_logged_in', 'Bạn cần đăng nhập.', array( 'status' => 401 ) );
        }

        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        global $wpdb;
        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}bookmarks WHERE id = %d",
                $request['id']
            )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_bookmark_not_found', 'Không tìm thấy bookmark.', array( 'status' => 404 ) );
        }

        if ( (int) $item->user_id !== get_current_user_id() ) {
            return new WP_Error( 'rest_forbidden', 'Bạn không có quyền.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Chuẩn bị response
     */
    public function prepare_item_for_response( $item, $request ) {
        $data = array(
            'id'         => (int) $item->id,
            'user_id'    => (int) $item->user_id,
            'title'      => $item->title,
            'url'        => $item->url,
            'notes'      => $item->notes,
            'created_at' => $item->created_at,
        );

        // Lọc theo _fields parameter
        $fields = $this->get_fields_for_response( $request );
        if ( is_array( $fields ) ) {
            $data = array_intersect_key( $data, array_flip( $fields ) );
        }

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Định nghĩa schema
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            return $this->schema;
        }

        $this->schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'bookmark',
            'type'       => 'object',
            'properties' => array(
                'id' => array(
                    'description' => 'ID duy nhất của bookmark.',
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'title' => array(
                    'description' => 'Tiêu đề bookmark.',
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'required'    => true,
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'url' => array(
                    'description' => 'URL bookmark.',
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => array( 'view', 'edit' ),
                    'required'    => true,
                    'arg_options' => array(
                        'sanitize_callback' => 'esc_url_raw',
                    ),
                ),
                'notes' => array(
                    'description' => 'Ghi chú.',
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
                'created_at' => array(
                    'description' => 'Ngày tạo.',
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view' ),
                    'readonly'    => true,
                ),
            ),
        );

        return $this->schema;
    }

    /**
     * Tham số cho collection (list)
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['per_page']['default'] = 20;
        $params['per_page']['maximum'] = 100;

        return $params;
    }
}

// Đăng ký controller
add_action( 'rest_api_init', function() {
    $controller = new My_Bookmarks_REST_Controller();
    $controller->register_routes();
} );
```

---

## 9. REST API trong Plugin - Ví dụ CRUD hoàn chỉnh

Ví dụ đầy đủ về một plugin quản lý "Tasks" với REST API.

### 9.1. Plugin header và activation

```php
<?php
/**
 * Plugin Name: My Tasks API
 * Description: Quản lý công việc với REST API
 * Version: 1.0.0
 * Author: Developer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MY_TASKS_VERSION', '1.0.0' );
define( 'MY_TASKS_DB_VERSION', '1.0' );
define( 'MY_TASKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Tạo bảng khi activate
register_activation_hook( __FILE__, 'my_tasks_activate' );

function my_tasks_activate() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'tasks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        title varchar(255) NOT NULL,
        description text,
        status varchar(20) NOT NULL DEFAULT 'todo',
        priority varchar(20) NOT NULL DEFAULT 'medium',
        due_date datetime DEFAULT NULL,
        completed_at datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY priority (priority),
        KEY due_date (due_date)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'my_tasks_db_version', MY_TASKS_DB_VERSION );
}

// Xóa bảng khi uninstall
// File: uninstall.php
// if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tasks" );
// delete_option( 'my_tasks_db_version' );
```

### 9.2. REST Controller

```php
<?php
// File: includes/class-tasks-rest-controller.php

class My_Tasks_REST_Controller extends WP_REST_Controller {

    protected $namespace = 'mytasks/v1';
    protected $rest_base = 'tasks';
    protected $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tasks';
    }

    public function register_routes() {
        // Collection: GET (list), POST (create)
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => $this->get_create_params(),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        // Single: GET, PUT/PATCH, DELETE
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
                'args'                => $this->get_update_params(),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'delete_item_permissions_check' ),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        // Batch operations
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'batch_update' ),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args'                => array(
                'ids'    => array( 'required' => true, 'type' => 'array' ),
                'status' => array( 'required' => true, 'type' => 'string', 'enum' => array( 'todo', 'in_progress', 'done', 'cancelled' ) ),
            ),
        ) );

        // Statistics
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_stats' ),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ) );
    }

    /**
     * GET /tasks - Danh sách tasks
     */
    public function get_items( $request ) {
        global $wpdb;

        $per_page = $request->get_param( 'per_page' );
        $page     = $request->get_param( 'page' );
        $status   = $request->get_param( 'status' );
        $priority = $request->get_param( 'priority' );
        $search   = $request->get_param( 'search' );
        $orderby  = $request->get_param( 'orderby' );
        $order    = $request->get_param( 'order' );
        $offset   = ( $page - 1 ) * $per_page;

        $where  = array( 'user_id = %d' );
        $values = array( get_current_user_id() );

        // Admin có thể xem tất cả
        if ( current_user_can( 'manage_options' ) && $request->get_param( 'all_users' ) ) {
            $where  = array( '1=1' );
            $values = array();
        }

        if ( ! empty( $status ) ) {
            $where[]  = 'status = %s';
            $values[] = $status;
        }

        if ( ! empty( $priority ) ) {
            $where[]  = 'priority = %s';
            $values[] = $priority;
        }

        if ( ! empty( $search ) ) {
            $where[]  = '(title LIKE %s OR description LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Whitelist orderby
        $allowed_orderby = array( 'created_at', 'updated_at', 'due_date', 'priority', 'status', 'title' );
        $orderby_sql = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'created_at';
        $order_sql   = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

        // Đếm tổng
        $count_values = $values;
        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}",
                $count_values
            )
        );

        // Lấy dữ liệu
        $values[] = $per_page;
        $values[] = $offset;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                 WHERE {$where_sql}
                 ORDER BY {$orderby_sql} {$order_sql}
                 LIMIT %d OFFSET %d",
                $values
            )
        );

        $data = array();
        foreach ( $items as $item ) {
            $data[] = $this->prepare_item_for_response( $item, $request )->get_data();
        }

        $response = new WP_REST_Response( $data, 200 );
        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', ceil( $total / $per_page ) );

        return $response;
    }

    /**
     * GET /tasks/<id> - Chi tiết task
     */
    public function get_item( $request ) {
        $item = $this->get_task( $request['id'] );

        if ( is_wp_error( $item ) ) {
            return $item;
        }

        return $this->prepare_item_for_response( $item, $request );
    }

    /**
     * POST /tasks - Tạo task mới
     */
    public function create_item( $request ) {
        global $wpdb;

        $data = array(
            'user_id'     => get_current_user_id(),
            'title'       => $request->get_param( 'title' ),
            'description' => $request->get_param( 'description' ) ?: '',
            'status'      => $request->get_param( 'status' ) ?: 'todo',
            'priority'    => $request->get_param( 'priority' ) ?: 'medium',
            'due_date'    => $request->get_param( 'due_date' ) ?: null,
            'created_at'  => current_time( 'mysql' ),
            'updated_at'  => current_time( 'mysql' ),
        );

        $format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

        $result = $wpdb->insert( $this->table_name, $data, $format );

        if ( false === $result ) {
            return new WP_Error( 'rest_db_error', 'Không thể tạo task: ' . $wpdb->last_error, array( 'status' => 500 ) );
        }

        $item = $this->get_task( $wpdb->insert_id );
        $response = $this->prepare_item_for_response( $item, $request );
        $response->set_status( 201 );
        $response->header( 'Location', rest_url( "{$this->namespace}/{$this->rest_base}/{$item->id}" ) );

        /**
         * Fires after a task is created via REST API.
         *
         * @param object          $item    Task object.
         * @param WP_REST_Request $request Request object.
         */
        do_action( 'my_tasks_rest_after_create', $item, $request );

        return $response;
    }

    /**
     * PUT/PATCH /tasks/<id> - Cập nhật task
     */
    public function update_item( $request ) {
        global $wpdb;

        $item = $this->get_task( $request['id'] );
        if ( is_wp_error( $item ) ) {
            return $item;
        }

        $update_data   = array();
        $update_format = array();

        $fields = array(
            'title'       => '%s',
            'description' => '%s',
            'status'      => '%s',
            'priority'    => '%s',
            'due_date'    => '%s',
        );

        foreach ( $fields as $field => $format ) {
            if ( $request->has_param( $field ) ) {
                $update_data[ $field ] = $request->get_param( $field );
                $update_format[]       = $format;
            }
        }

        // Nếu chuyển sang 'done', ghi nhận thời gian hoàn thành
        if ( isset( $update_data['status'] ) && 'done' === $update_data['status'] && 'done' !== $item->status ) {
            $update_data['completed_at'] = current_time( 'mysql' );
            $update_format[]             = '%s';
        }

        // Nếu chuyển TỪ 'done' sang trạng thái khác, xóa completed_at
        if ( isset( $update_data['status'] ) && 'done' !== $update_data['status'] && 'done' === $item->status ) {
            $update_data['completed_at'] = null;
            $update_format[]             = '%s';
        }

        if ( empty( $update_data ) ) {
            return new WP_Error( 'rest_no_data', 'Không có dữ liệu để cập nhật.', array( 'status' => 400 ) );
        }

        $update_data['updated_at'] = current_time( 'mysql' );
        $update_format[]           = '%s';

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array( 'id' => $request['id'] ),
            $update_format,
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'rest_db_error', 'Không thể cập nhật task.', array( 'status' => 500 ) );
        }

        $updated_item = $this->get_task( $request['id'] );

        do_action( 'my_tasks_rest_after_update', $updated_item, $item, $request );

        return $this->prepare_item_for_response( $updated_item, $request );
    }

    /**
     * DELETE /tasks/<id> - Xóa task
     */
    public function delete_item( $request ) {
        global $wpdb;

        $item = $this->get_task( $request['id'] );
        if ( is_wp_error( $item ) ) {
            return $item;
        }

        $response = $this->prepare_item_for_response( $item, $request );
        $data     = $response->get_data();

        $wpdb->delete( $this->table_name, array( 'id' => $request['id'] ), array( '%d' ) );

        $data['deleted'] = true;

        do_action( 'my_tasks_rest_after_delete', $item, $request );

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Batch update - Cập nhật nhiều tasks cùng lúc
     */
    public function batch_update( $request ) {
        global $wpdb;

        $ids    = $request->get_param( 'ids' );
        $status = $request->get_param( 'status' );

        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return new WP_Error( 'rest_invalid_ids', 'Danh sách IDs không hợp lệ.', array( 'status' => 400 ) );
        }

        $user_id      = get_current_user_id();
        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

        $update_data = array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) );
        if ( 'done' === $status ) {
            $update_data['completed_at'] = current_time( 'mysql' );
        }

        $set_parts = array();
        $values    = array();
        foreach ( $update_data as $col => $val ) {
            $set_parts[] = "{$col} = %s";
            $values[]    = $val;
        }
        $set_sql = implode( ', ', $set_parts );

        $values[] = $user_id;
        $values   = array_merge( $values, array_map( 'intval', $ids ) );

        $rows = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET {$set_sql}
                 WHERE user_id = %d AND id IN ({$placeholders})",
                $values
            )
        );

        return new WP_REST_Response( array(
            'updated' => $rows,
            'status'  => $status,
        ), 200 );
    }

    /**
     * Thống kê tasks
     */
    public function get_stats( $request ) {
        global $wpdb;

        $user_id = get_current_user_id();

        $stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) as count
                 FROM {$this->table_name}
                 WHERE user_id = %d
                 GROUP BY status",
                $user_id
            ),
            OBJECT_K
        );

        $overdue = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name}
                 WHERE user_id = %d AND status != 'done' AND status != 'cancelled'
                 AND due_date IS NOT NULL AND due_date < %s",
                $user_id,
                current_time( 'mysql' )
            )
        );

        $completed_this_week = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name}
                 WHERE user_id = %d AND status = 'done'
                 AND completed_at >= DATE_SUB(%s, INTERVAL 7 DAY)",
                $user_id,
                current_time( 'mysql' )
            )
        );

        return new WP_REST_Response( array(
            'by_status'           => $stats,
            'overdue'             => (int) $overdue,
            'completed_this_week' => (int) $completed_this_week,
        ), 200 );
    }

    /**
     * Permission checks
     */
    public function get_items_permissions_check( $request ) {
        return is_user_logged_in();
    }

    public function get_item_permissions_check( $request ) {
        return $this->check_task_permission( $request['id'] );
    }

    public function create_item_permissions_check( $request ) {
        return is_user_logged_in();
    }

    public function update_item_permissions_check( $request ) {
        return $this->check_task_permission( $request['id'] );
    }

    public function delete_item_permissions_check( $request ) {
        return $this->check_task_permission( $request['id'] );
    }

    private function check_task_permission( $task_id ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_not_logged_in', 'Bạn cần đăng nhập.', array( 'status' => 401 ) );
        }

        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $task = $this->get_task( $task_id );
        if ( is_wp_error( $task ) ) {
            return $task;
        }

        if ( (int) $task->user_id !== get_current_user_id() ) {
            return new WP_Error( 'rest_forbidden', 'Bạn không có quyền truy cập task này.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Helper: Lấy task từ database
     */
    private function get_task( $id ) {
        global $wpdb;

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_task_not_found', 'Không tìm thấy task.', array( 'status' => 404 ) );
        }

        return $item;
    }

    /**
     * Chuẩn bị response
     */
    public function prepare_item_for_response( $item, $request ) {
        $data = array(
            'id'           => (int) $item->id,
            'user_id'      => (int) $item->user_id,
            'title'        => $item->title,
            'description'  => $item->description,
            'status'       => $item->status,
            'priority'     => $item->priority,
            'due_date'     => $item->due_date,
            'completed_at' => $item->completed_at,
            'created_at'   => $item->created_at,
            'updated_at'   => $item->updated_at,
            'is_overdue'   => $this->is_overdue( $item ),
        );

        // Thêm thông tin user
        $user = get_userdata( $item->user_id );
        if ( $user ) {
            $data['user'] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 48 ) ),
            );
        }

        // Lọc theo _fields
        $context = $request->get_param( 'context' ) ?: 'view';
        $fields  = $this->get_fields_for_response( $request );

        return new WP_REST_Response( $data, 200 );
    }

    private function is_overdue( $item ) {
        if ( 'done' === $item->status || 'cancelled' === $item->status ) {
            return false;
        }
        if ( empty( $item->due_date ) ) {
            return false;
        }
        return strtotime( $item->due_date ) < current_time( 'timestamp' );
    }

    /**
     * Schema
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            return $this->schema;
        }

        $this->schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'task',
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer', 'readonly' => true ),
                'title'        => array( 'type' => 'string', 'required' => true ),
                'description'  => array( 'type' => 'string' ),
                'status'       => array( 'type' => 'string', 'enum' => array( 'todo', 'in_progress', 'done', 'cancelled' ) ),
                'priority'     => array( 'type' => 'string', 'enum' => array( 'low', 'medium', 'high', 'urgent' ) ),
                'due_date'     => array( 'type' => array( 'string', 'null' ), 'format' => 'date-time' ),
                'completed_at' => array( 'type' => array( 'string', 'null' ), 'format' => 'date-time', 'readonly' => true ),
                'created_at'   => array( 'type' => 'string', 'format' => 'date-time', 'readonly' => true ),
                'updated_at'   => array( 'type' => 'string', 'format' => 'date-time', 'readonly' => true ),
            ),
        );

        return $this->schema;
    }

    /**
     * Tham số cho collection
     */
    public function get_collection_params() {
        return array(
            'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
            'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
            'search'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'status'   => array( 'type' => 'string', 'enum' => array( 'todo', 'in_progress', 'done', 'cancelled' ) ),
            'priority' => array( 'type' => 'string', 'enum' => array( 'low', 'medium', 'high', 'urgent' ) ),
            'orderby'  => array( 'type' => 'string', 'default' => 'created_at', 'enum' => array( 'created_at', 'updated_at', 'due_date', 'priority', 'title' ) ),
            'order'    => array( 'type' => 'string', 'default' => 'DESC', 'enum' => array( 'ASC', 'DESC' ) ),
        );
    }

    private function get_create_params() {
        return array(
            'title'       => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'description' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ),
            'status'      => array( 'type' => 'string', 'default' => 'todo', 'enum' => array( 'todo', 'in_progress', 'done', 'cancelled' ) ),
            'priority'    => array( 'type' => 'string', 'default' => 'medium', 'enum' => array( 'low', 'medium', 'high', 'urgent' ) ),
            'due_date'    => array( 'type' => 'string', 'format' => 'date-time' ),
        );
    }

    private function get_update_params() {
        return array(
            'title'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'description' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ),
            'status'      => array( 'type' => 'string', 'enum' => array( 'todo', 'in_progress', 'done', 'cancelled' ) ),
            'priority'    => array( 'type' => 'string', 'enum' => array( 'low', 'medium', 'high', 'urgent' ) ),
            'due_date'    => array( 'type' => array( 'string', 'null' ), 'format' => 'date-time' ),
        );
    }
}

// Đăng ký controller
add_action( 'rest_api_init', function() {
    $controller = new My_Tasks_REST_Controller();
    $controller->register_routes();
} );
```

### 9.3. Sử dụng API từ frontend (JavaScript)

```javascript
/**
 * JavaScript client cho Tasks API
 */
class TasksAPI {
    constructor() {
        this.baseUrl = wpApiSettings.root + 'mytasks/v1/tasks';
        this.nonce = wpApiSettings.nonce;
    }

    async request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce,
                ...options.headers,
            },
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Request thất bại');
        }

        const data = await response.json();
        return {
            data,
            total: parseInt(response.headers.get('X-WP-Total') || '0'),
            totalPages: parseInt(response.headers.get('X-WP-TotalPages') || '0'),
        };
    }

    // Lấy danh sách tasks
    async list(params = {}) {
        const query = new URLSearchParams(params).toString();
        const url = query ? `${this.baseUrl}?${query}` : this.baseUrl;
        return this.request(url);
    }

    // Lấy 1 task
    async get(id) {
        return this.request(`${this.baseUrl}/${id}`);
    }

    // Tạo task mới
    async create(data) {
        return this.request(this.baseUrl, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    // Cập nhật task
    async update(id, data) {
        return this.request(`${this.baseUrl}/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    // Xóa task
    async delete(id) {
        return this.request(`${this.baseUrl}/${id}`, {
            method: 'DELETE',
        });
    }

    // Batch update
    async batchUpdate(ids, status) {
        return this.request(`${this.baseUrl}/batch`, {
            method: 'PUT',
            body: JSON.stringify({ ids, status }),
        });
    }

    // Thống kê
    async stats() {
        return this.request(`${this.baseUrl}/stats`);
    }
}

// Sử dụng
const tasksAPI = new TasksAPI();

// Lấy danh sách tasks chưa hoàn thành, sắp xếp theo due_date
tasksAPI.list({
    status: 'todo',
    orderby: 'due_date',
    order: 'ASC',
    per_page: 20,
}).then(result => {
    console.log(`Tổng: ${result.total} tasks`);
    result.data.forEach(task => {
        console.log(`[${task.priority}] ${task.title} - Hạn: ${task.due_date}`);
    });
});

// Tạo task mới
tasksAPI.create({
    title: 'Hoàn thành báo cáo',
    description: 'Báo cáo hàng tháng cho phòng kinh doanh',
    priority: 'high',
    due_date: '2024-12-31T17:00:00',
}).then(result => {
    console.log('Đã tạo task ID:', result.data.id);
});

// Cập nhật trạng thái
tasksAPI.update(5, { status: 'done' }).then(result => {
    console.log('Đã hoàn thành:', result.data.title);
});

// Xóa task
tasksAPI.delete(10).then(result => {
    console.log('Đã xóa task');
});
```

---

## 10. Best Practices

### 10.1. Luôn khai báo permission_callback

```php
<?php
// SAI: Thiếu permission_callback (sẽ có warning từ WP 5.5+)
register_rest_route( 'myplugin/v1', '/data', array(
    'methods'  => 'GET',
    'callback' => 'my_callback',
) );

// ĐÚNG: Luôn có permission_callback
register_rest_route( 'myplugin/v1', '/data', array(
    'methods'             => 'GET',
    'callback'            => 'my_callback',
    'permission_callback' => '__return_true',  // Public
) );
```

### 10.2. Sử dụng namespace đúng cách

```php
<?php
// ĐÚNG: Namespace có version
// myplugin/v1
register_rest_route( 'myplugin/v1', '/items', array( /* ... */ ) );

// ĐÚNG: Khi cần version mới (không break version cũ)
register_rest_route( 'myplugin/v2', '/items', array( /* ... */ ) );

// SAI: Không có version
register_rest_route( 'myplugin', '/items', array( /* ... */ ) );

// SAI: Dùng namespace của WordPress core
register_rest_route( 'wp/v2', '/my-items', array( /* ... */ ) );
```

### 10.3. Validate và sanitize DỮ LIỆU

```php
<?php
// ĐÚNG: Validate và sanitize đầy đủ
register_rest_route( 'myplugin/v1', '/items', array(
    'methods'  => 'POST',
    'callback' => 'my_create_item',
    'permission_callback' => function() {
        return current_user_can( 'edit_posts' );
    },
    'args' => array(
        'title' => array(
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value ) {
                if ( empty( trim( $value ) ) ) {
                    return new WP_Error( 'empty_title', 'Tiêu đề không được để trống.' );
                }
                return true;
            },
        ),
        'email' => array(
            'required'          => true,
            'type'              => 'string',
            'format'            => 'email',
            'sanitize_callback' => 'sanitize_email',
        ),
    ),
) );
```

### 10.4. Trả về HTTP status code đúng

```php
<?php
// 200 OK - Thành công (GET, PUT, DELETE)
return new WP_REST_Response( $data, 200 );

// 201 Created - Tạo thành công (POST)
$response = new WP_REST_Response( $data, 201 );
$response->header( 'Location', rest_url( 'myplugin/v1/items/' . $id ) );
return $response;

// 204 No Content - Xóa thành công (không trả dữ liệu)
return new WP_REST_Response( null, 204 );

// 400 Bad Request - Dữ liệu không hợp lệ
return new WP_Error( 'invalid_data', 'Dữ liệu không hợp lệ.', array( 'status' => 400 ) );

// 401 Unauthorized - Chưa xác thực
return new WP_Error( 'rest_not_logged_in', 'Bạn cần đăng nhập.', array( 'status' => 401 ) );

// 403 Forbidden - Không có quyền
return new WP_Error( 'rest_forbidden', 'Bạn không có quyền.', array( 'status' => 403 ) );

// 404 Not Found - Không tìm thấy
return new WP_Error( 'not_found', 'Không tìm thấy.', array( 'status' => 404 ) );

// 500 Internal Server Error - Lỗi server
return new WP_Error( 'server_error', 'Lỗi hệ thống.', array( 'status' => 500 ) );
```

### 10.5. Sử dụng _fields để giảm dữ liệu trả về

```javascript
// Chỉ lấy các trường cần thiết
fetch('/wp-json/wp/v2/posts?_fields=id,title,link,date&per_page=5');

// Thay vì lấy toàn bộ dữ liệu của post (rất nhiều trường)
fetch('/wp-json/wp/v2/posts?per_page=5');  // Trả về nhiều dữ liệu thừa
```

### 10.6. Cache REST API responses

```php
<?php
// Sử dụng transient để cache
function my_cached_endpoint( WP_REST_Request $request ) {
    $cache_key = 'rest_cache_' . md5( serialize( $request->get_params() ) );
    $data      = get_transient( $cache_key );

    if ( false === $data ) {
        // Cache miss - tính toán dữ liệu
        $data = expensive_computation();
        set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );
    }

    $response = new WP_REST_Response( $data, 200 );

    // Thêm cache headers
    $response->header( 'Cache-Control', 'max-age=300' ); // 5 phút
    $response->header( 'X-Cache', false === get_transient( $cache_key ) ? 'MISS' : 'HIT' );

    return $response;
}

// Xóa cache khi dữ liệu thay đổi
add_action( 'save_post', function( $post_id ) {
    // Xóa các transients liên quan
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_rest_cache_%'
         OR option_name LIKE '_transient_timeout_rest_cache_%'"
    );
} );
```

### 10.7. Rate Limiting

```php
<?php
// Rate limiting đơn giản
add_filter( 'rest_pre_dispatch', 'my_rest_rate_limit', 10, 3 );

function my_rest_rate_limit( $result, $server, $request ) {
    // Chỉ áp dụng cho endpoints của mình
    $route = $request->get_route();
    if ( strpos( $route, '/myplugin/' ) === false ) {
        return $result;
    }

    $ip         = $_SERVER['REMOTE_ADDR'];
    $cache_key  = 'rate_limit_' . md5( $ip );
    $requests   = (int) get_transient( $cache_key );
    $max_requests = 60;  // 60 requests mỗi phút

    if ( $requests >= $max_requests ) {
        return new WP_Error(
            'rest_rate_limit',
            'Quá nhiều request. Vui lòng thử lại sau.',
            array(
                'status' => 429,
                'retry_after' => 60,
            )
        );
    }

    set_transient( $cache_key, $requests + 1, MINUTE_IN_SECONDS );

    return $result;
}
```

### 10.8. CORS (Cross-Origin Resource Sharing)

```php
<?php
// Cho phép CORS cho REST API
add_action( 'rest_api_init', function() {
    // Xóa header CORS mặc định của WP
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

    // Thêm header CORS tùy chỉnh
    add_filter( 'rest_pre_serve_request', function( $served, $result, $request, $server ) {
        $origin = get_http_origin();

        // Chỉ cho phép các domain cụ thể
        $allowed_origins = array(
            'https://my-frontend-app.com',
            'https://admin.example.com',
        );

        if ( in_array( $origin, $allowed_origins, true ) ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
            header( 'Access-Control-Allow-Credentials: true' );
            header( 'Access-Control-Max-Age: 600' );
        }

        return $served;
    }, 10, 4 );
} );
```

### 10.9. Disable REST API cho người dùng chưa đăng nhập (nếu cần)

```php
<?php
// Chặn tất cả REST API cho người chưa đăng nhập
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            'Bạn cần đăng nhập để sử dụng API.',
            array( 'status' => 401 )
        );
    }

    return $result;
} );

// HOẶC chỉ chặn một số endpoints
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // Cho phép các endpoint public
    $public_routes = array( '/wp/v2/posts', '/wp/v2/categories', '/wp/v2/tags' );
    $current_route = $_SERVER['REQUEST_URI'];

    foreach ( $public_routes as $route ) {
        if ( strpos( $current_route, $route ) !== false ) {
            return $result;
        }
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'Cần đăng nhập.', array( 'status' => 401 ) );
    }

    return $result;
} );
```

### 10.10. Testing REST API

```bash
# Test với cURL
# Lấy danh sách
curl -s "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Authorization: Basic $(echo -n 'admin:app-password' | base64)" | python3 -m json.tool

# Tạo mới
curl -s -X POST "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic $(echo -n 'admin:app-password' | base64)" \
    -d '{"title": "Test task", "priority": "high"}' | python3 -m json.tool

# Test validation (thiếu title)
curl -s -X POST "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic $(echo -n 'admin:app-password' | base64)" \
    -d '{"priority": "high"}' | python3 -m json.tool
# Kết quả: {"code":"rest_missing_callback_param","message":"Missing parameter(s): title",...}

# Test permission (không xác thực)
curl -s -X POST "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Content-Type: application/json" \
    -d '{"title": "Test"}' | python3 -m json.tool
# Kết quả: {"code":"rest_not_logged_in","message":"Bạn cần đăng nhập.",...}
```

```php
<?php
// Unit test với PHPUnit (WP Test Suite)
class Test_Tasks_REST_Controller extends WP_Test_REST_Controller_Testcase {

    protected $admin_id;
    protected $subscriber_id;

    public function set_up() {
        parent::set_up();

        $this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        $this->subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
    }

    public function test_register_routes() {
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey( '/mytasks/v1/tasks', $routes );
        $this->assertArrayHasKey( '/mytasks/v1/tasks/(?P<id>[\d]+)', $routes );
    }

    public function test_get_items_requires_login() {
        $request  = new WP_REST_Request( 'GET', '/mytasks/v1/tasks' );
        $response = rest_get_server()->dispatch( $request );
        $this->assertEquals( 401, $response->get_status() );
    }

    public function test_create_item() {
        wp_set_current_user( $this->admin_id );

        $request = new WP_REST_Request( 'POST', '/mytasks/v1/tasks' );
        $request->set_body_params( array(
            'title'    => 'Test Task',
            'priority' => 'high',
        ) );

        $response = rest_get_server()->dispatch( $request );
        $this->assertEquals( 201, $response->get_status() );

        $data = $response->get_data();
        $this->assertEquals( 'Test Task', $data['title'] );
        $this->assertEquals( 'high', $data['priority'] );
        $this->assertEquals( 'todo', $data['status'] );
    }

    public function test_create_item_missing_title() {
        wp_set_current_user( $this->admin_id );

        $request = new WP_REST_Request( 'POST', '/mytasks/v1/tasks' );
        $request->set_body_params( array( 'priority' => 'high' ) );

        $response = rest_get_server()->dispatch( $request );
        $this->assertEquals( 400, $response->get_status() );
    }
}
```

---

Tài liệu tham khảo:
- WordPress REST API Handbook: https://developer.wordpress.org/rest-api/
- REST API Reference: https://developer.wordpress.org/rest-api/reference/
- WP_REST_Controller: https://developer.wordpress.org/reference/classes/wp_rest_controller/
