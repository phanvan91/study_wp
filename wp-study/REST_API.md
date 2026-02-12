# WordPress REST API

Huong dan toan dien ve WordPress REST API: tu cac endpoint mac dinh, authentication, den viec tao custom endpoints, controller, va xay dung CRUD hoan chinh trong plugin.

---

## Muc luc

1. [Gioi thieu REST API](#1-gioi-thieu-rest-api)
2. [Cac endpoint mac dinh](#2-cac-endpoint-mac-dinh)
3. [Su dung REST API - GET, POST, PUT, DELETE](#3-su-dung-rest-api---get-post-put-delete)
4. [Authentication](#4-authentication)
5. [Tao Custom Endpoints - register_rest_route()](#5-tao-custom-endpoints---register_rest_route)
6. [Permission Callbacks](#6-permission-callbacks)
7. [Schema va Validation](#7-schema-va-validation)
8. [Custom Controller - Extend WP_REST_Controller](#8-custom-controller---extend-wp_rest_controller)
9. [REST API trong Plugin - Vi du CRUD hoan chinh](#9-rest-api-trong-plugin---vi-du-crud-hoan-chinh)
10. [Best Practices](#10-best-practices)

---

## 1. Gioi thieu REST API

### 1.1. REST API la gi?

WordPress REST API cung cap cac HTTP endpoints cho phep truy cap du lieu WordPress theo chuan RESTful. Du lieu duoc gui va nhan duoi dang JSON.

Cac khai niem co ban:

- **Route**: Duong dan URL cua API, vi du `/wp/v2/posts`.
- **Endpoint**: Mot route ket hop voi HTTP method cu the, vi du `GET /wp/v2/posts` la mot endpoint, `POST /wp/v2/posts` la mot endpoint khac.
- **Namespace**: Phan dau cua route dung de nhom cac endpoint, vi du `wp/v2` la namespace cua WordPress core.
- **Request**: Doi tuong `WP_REST_Request` chua thong tin ve request (params, headers, body).
- **Response**: Doi tuong `WP_REST_Response` chua du lieu tra ve (data, status code, headers).
- **Schema**: Mo ta cau truc du lieu cua endpoint (fields, types, validation rules).

### 1.2. URL co ban

```
https://example.com/wp-json/wp/v2/posts
|                  |       |  |  |     |
|   Domain         |wp-json|NS|v |Route|
```

- `wp-json`: REST API prefix (co the thay doi bang filter `rest_url_prefix`)
- `wp/v2`: Namespace (wp = WordPress core, v2 = version 2)
- `posts`: Route name

### 1.3. Kiem tra REST API co hoat dong

```bash
# Lay thong tin API root
curl https://example.com/wp-json/

# Lay danh sach routes
curl https://example.com/wp-json/wp/v2/

# Kiem tra voi OPTIONS method
curl -X OPTIONS https://example.com/wp-json/wp/v2/posts
```

### 1.4. Discovery

```html
<!-- WordPress tu dong them link trong head -->
<link rel="https://api.w.org/" href="https://example.com/wp-json/" />
```

```php
<?php
// Lay REST API URL trong PHP
$api_url = rest_url();           // https://example.com/wp-json/
$posts_url = rest_url( 'wp/v2/posts' ); // https://example.com/wp-json/wp/v2/posts
```

---

## 2. Cac endpoint mac dinh

### 2.1. /wp/v2/posts

Quan ly bai viet (post type = 'post').

```
GET    /wp/v2/posts          - Lay danh sach bai viet
GET    /wp/v2/posts/<id>     - Lay 1 bai viet theo ID
POST   /wp/v2/posts          - Tao bai viet moi (can xac thuc)
PUT    /wp/v2/posts/<id>     - Cap nhat toan bo bai viet (can xac thuc)
PATCH  /wp/v2/posts/<id>     - Cap nhat mot phan bai viet (can xac thuc)
DELETE /wp/v2/posts/<id>     - Xoa bai viet (can xac thuc)
```

Cac tham so query pho bien cho GET:

| Tham so | Mo ta | Vi du |
|---------|-------|-------|
| `page` | Trang hien tai | `?page=2` |
| `per_page` | So item moi trang (1-100, mac dinh 10) | `?per_page=20` |
| `search` | Tim kiem | `?search=wordpress` |
| `after` | Bai viet sau ngay | `?after=2024-01-01T00:00:00` |
| `before` | Bai viet truoc ngay | `?before=2024-12-31T23:59:59` |
| `author` | Theo ID tac gia | `?author=1` |
| `author_exclude` | Loai tru tac gia | `?author_exclude=3,5` |
| `exclude` | Loai tru post IDs | `?exclude=1,2,3` |
| `include` | Chi lay post IDs | `?include=10,20,30` |
| `slug` | Theo slug | `?slug=bai-viet-mau` |
| `status` | Trang thai | `?status=draft` (can xac thuc) |
| `categories` | Theo category IDs | `?categories=5,10` |
| `categories_exclude` | Loai tru categories | `?categories_exclude=3` |
| `tags` | Theo tag IDs | `?tags=7,8` |
| `tags_exclude` | Loai tru tags | `?tags_exclude=2` |
| `sticky` | Bai ghim | `?sticky=true` |
| `orderby` | Sap xep | `?orderby=title` |
| `order` | Thu tu | `?order=asc` |
| `_fields` | Chi lay cac truong cu the | `?_fields=id,title,link` |
| `_embed` | Nhung du lieu lien quan | `?_embed` |

### 2.2. /wp/v2/pages

Tuong tu posts nhung cho post type = 'page'.

```
GET    /wp/v2/pages          - Lay danh sach trang
GET    /wp/v2/pages/<id>     - Lay 1 trang
POST   /wp/v2/pages          - Tao trang moi
PUT    /wp/v2/pages/<id>     - Cap nhat trang
DELETE /wp/v2/pages/<id>     - Xoa trang
```

Tham so rieng cua pages:
- `parent`: ID trang cha (`?parent=10`)
- `menu_order`: Thu tu menu (`?orderby=menu_order`)

### 2.3. /wp/v2/users

Quan ly nguoi dung.

```
GET    /wp/v2/users          - Lay danh sach users
GET    /wp/v2/users/<id>     - Lay 1 user
GET    /wp/v2/users/me       - Lay user hien tai (can xac thuc)
POST   /wp/v2/users          - Tao user moi
PUT    /wp/v2/users/<id>     - Cap nhat user
DELETE /wp/v2/users/<id>     - Xoa user
```

Tham so:
- `roles`: Loc theo role (`?roles=author,editor`)
- `slug`: Theo user slug
- `search`: Tim kiem

### 2.4. /wp/v2/categories

Quan ly categories.

```
GET    /wp/v2/categories          - Lay danh sach
GET    /wp/v2/categories/<id>     - Lay 1 category
POST   /wp/v2/categories          - Tao moi
PUT    /wp/v2/categories/<id>     - Cap nhat
DELETE /wp/v2/categories/<id>     - Xoa
```

Tham so:
- `parent`: Category cha
- `post`: Lay categories cua 1 post
- `hide_empty`: An categories khong co bai (`?hide_empty=true`)

### 2.5. /wp/v2/tags

Quan ly tags.

```
GET    /wp/v2/tags          - Lay danh sach
GET    /wp/v2/tags/<id>     - Lay 1 tag
POST   /wp/v2/tags          - Tao moi
PUT    /wp/v2/tags/<id>     - Cap nhat
DELETE /wp/v2/tags/<id>     - Xoa
```

### 2.6. /wp/v2/comments

Quan ly binh luan.

```
GET    /wp/v2/comments          - Lay danh sach
GET    /wp/v2/comments/<id>     - Lay 1 comment
POST   /wp/v2/comments          - Tao moi
PUT    /wp/v2/comments/<id>     - Cap nhat
DELETE /wp/v2/comments/<id>     - Xoa
```

Tham so:
- `post`: Comments cua 1 post (`?post=42`)
- `parent`: Comment cha (reply)
- `author_email`: Theo email tac gia
- `status`: Trang thai (`approve`, `hold`, `spam`, `trash`)

### 2.7. /wp/v2/media

Quan ly media (attachments).

```
GET    /wp/v2/media          - Lay danh sach
GET    /wp/v2/media/<id>     - Lay 1 media
POST   /wp/v2/media          - Upload file
PUT    /wp/v2/media/<id>     - Cap nhat thong tin
DELETE /wp/v2/media/<id>     - Xoa
```

Tham so:
- `media_type`: Loai media (`image`, `video`, `audio`, `application`)
- `mime_type`: MIME type (`image/jpeg`)

### 2.8. Cac endpoint khac

```
/wp/v2/types               - Post types
/wp/v2/statuses            - Post statuses
/wp/v2/taxonomies          - Taxonomies
/wp/v2/search              - Tim kiem toan cuc
/wp/v2/settings            - Cai dat site (can admin)
/wp/v2/themes              - Themes
/wp/v2/plugins             - Plugins (can admin)
/wp/v2/block-types         - Block types (Gutenberg)
/wp/v2/blocks              - Reusable blocks
```

---

## 3. Su dung REST API - GET, POST, PUT, DELETE

### 3.1. GET - Lay du lieu voi cURL

```bash
# Lay 5 bai viet moi nhat
curl "https://example.com/wp-json/wp/v2/posts?per_page=5&orderby=date&order=desc"

# Lay bai viet theo ID
curl "https://example.com/wp-json/wp/v2/posts/42"

# Tim kiem voi nhieu tham so
curl "https://example.com/wp-json/wp/v2/posts?search=wordpress&categories=5&per_page=10&_fields=id,title,link"

# Lay voi embedded data (author, featured media, terms)
curl "https://example.com/wp-json/wp/v2/posts?_embed&per_page=5"

# Lay chi mot so truong
curl "https://example.com/wp-json/wp/v2/posts?_fields=id,title.rendered,link,date"

# Lay headers de biet tong so va so trang
curl -I "https://example.com/wp-json/wp/v2/posts?per_page=10"
# Response headers:
# X-WP-Total: 156        (tong so bai)
# X-WP-TotalPages: 16    (tong so trang)
```

### 3.2. GET - Lay du lieu voi JavaScript fetch

```javascript
// Lay danh sach bai viet
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
        console.error('Loi khi lay bai viet:', error);
        throw error;
    }
}

// Su dung
getPosts(1, 5).then(result => {
    console.log(`Tong: ${result.total} bai viet`);
    result.posts.forEach(post => {
        console.log(`- ${post.title.rendered}`);
    });
});

// Lay 1 bai viet
async function getPost(id) {
    const response = await fetch(
        `https://example.com/wp-json/wp/v2/posts/${id}?_embed`
    );
    if (!response.ok) {
        throw new Error(`Post not found: ${response.status}`);
    }
    return response.json();
}

// Tim kiem
async function searchPosts(keyword) {
    const response = await fetch(
        `https://example.com/wp-json/wp/v2/posts?search=${encodeURIComponent(keyword)}&per_page=20`
    );
    return response.json();
}
```

### 3.3. POST - Tao du lieu voi cURL

```bash
# Tao bai viet moi (can xac thuc)
curl -X POST "https://example.com/wp-json/wp/v2/posts" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Bai viet moi tu API",
        "content": "Noi dung bai viet duoc tao tu REST API.",
        "status": "publish",
        "categories": [5, 10],
        "tags": [3, 7],
        "meta": {
            "custom_field": "gia tri"
        }
    }'

# Tao bai viet draft
curl -X POST "https://example.com/wp-json/wp/v2/posts" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Bai nhap",
        "content": "Dang soan...",
        "status": "draft"
    }'

# Upload media
curl -X POST "https://example.com/wp-json/wp/v2/media" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -H "Content-Disposition: attachment; filename=hinh-anh.jpg" \
    -H "Content-Type: image/jpeg" \
    --data-binary @/path/to/hinh-anh.jpg

# Tao comment
curl -X POST "https://example.com/wp-json/wp/v2/comments" \
    -H "Content-Type: application/json" \
    -d '{
        "post": 42,
        "author_name": "Nguyen Van A",
        "author_email": "a@example.com",
        "content": "Binh luan tu API"
    }'
```

### 3.4. POST - Tao du lieu voi JavaScript

```javascript
// Tao bai viet moi
async function createPost(data) {
    const response = await fetch('https://example.com/wp-json/wp/v2/posts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce,  // Neu dung cookie auth
            // HOAC: 'Authorization': 'Basic ' + btoa('user:app-password')
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

// Su dung
createPost({
    title: 'Bai viet tu JavaScript',
    content: '<p>Noi dung bai viet.</p>',
    status: 'publish',
    categories: [5],
}).then(post => {
    console.log('Da tao bai viet ID:', post.id);
});

// Upload media
async function uploadMedia(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('title', file.name);
    formData.append('alt_text', 'Mo ta hinh anh');

    const response = await fetch('https://example.com/wp-json/wp/v2/media', {
        method: 'POST',
        headers: {
            'X-WP-Nonce': wpApiSettings.nonce,
        },
        body: formData,  // KHONG dat Content-Type, browser tu them voi boundary
    });

    return response.json();
}
```

### 3.5. PUT/PATCH - Cap nhat du lieu

```bash
# Cap nhat toan bo (PUT)
curl -X PUT "https://example.com/wp-json/wp/v2/posts/42" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Tieu de da sua",
        "content": "Noi dung da cap nhat.",
        "status": "publish"
    }'

# Cap nhat mot phan (PATCH) - chi gui truong can sua
curl -X PATCH "https://example.com/wp-json/wp/v2/posts/42" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ=" \
    -d '{
        "title": "Chi sua tieu de thoi"
    }'
```

```javascript
// Cap nhat bai viet
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

// Cap nhat chi tieu de
updatePost(42, { title: 'Tieu de moi' }).then(post => {
    console.log('Da cap nhat:', post.title.rendered);
});
```

### 3.6. DELETE - Xoa du lieu

```bash
# Xoa bai viet (chuyen vao trash)
curl -X DELETE "https://example.com/wp-json/wp/v2/posts/42" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ="

# Xoa vinh vien (bo qua trash)
curl -X DELETE "https://example.com/wp-json/wp/v2/posts/42?force=true" \
    -H "Authorization: Basic YWRtaW46YXBwbGljYXRpb24tcGFzc3dvcmQ="
```

```javascript
// Xoa bai viet
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

// Chuyen vao trash
deletePost(42).then(() => console.log('Da chuyen vao trash'));

// Xoa vinh vien
deletePost(42, true).then(() => console.log('Da xoa vinh vien'));
```

### 3.7. Su dung REST API trong PHP (noi bo)

```php
<?php
// Goi REST API noi bo (internal request) - khong can HTTP request
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

// Tao bai viet qua internal request
$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
$request->set_body_params( array(
    'title'   => 'Bai viet noi bo',
    'content' => 'Noi dung...',
    'status'  => 'publish',
) );
// Set user hien tai de co quyen
$request->set_param( 'author', get_current_user_id() );

$response = rest_do_request( $request );

if ( $response->is_error() ) {
    $error = $response->as_error();
    echo 'Loi: ' . $error->get_error_message();
} else {
    $post = $response->get_data();
    echo 'Da tao post ID: ' . $post['id'];
}

// Su dung wp_remote_get/post cho external request
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

Danh cho cac request tu trong WordPress (cung domain). Su dung nonce de xac thuc.

```php
<?php
// Dang ky script voi nonce
add_action( 'wp_enqueue_scripts', 'my_enqueue_api_scripts' );
function my_enqueue_api_scripts() {
    wp_enqueue_script(
        'my-api-script',
        get_template_directory_uri() . '/js/api.js',
        array(),
        '1.0',
        true
    );

    // Truyen nonce va URL sang JavaScript
    wp_localize_script( 'my-api-script', 'wpApiSettings', array(
        'root'  => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
    ) );
}
```

```javascript
// Su dung nonce trong JavaScript
fetch(wpApiSettings.root + 'wp/v2/posts', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce,  // Gui nonce trong header
    },
    body: JSON.stringify({
        title: 'Bai viet moi',
        status: 'draft',
    }),
});

// Hoac gui nonce nhu query parameter
fetch(wpApiSettings.root + 'wp/v2/posts?_wpnonce=' + wpApiSettings.nonce);
```

### 4.2. Application Passwords

Co san tu WordPress 5.6. Tao password rieng cho tung ung dung, khong dung password chinh.

```php
<?php
// Tao application password qua code
$user_id = 1;
$app_name = 'My Mobile App';

$result = WP_Application_Passwords::create_new_application_password(
    $user_id,
    array( 'name' => $app_name )
);

if ( ! is_wp_error( $result ) ) {
    $password = $result[0]; // Mat khau moi (chi hien thi 1 lan)
    $item     = $result[1]; // Thong tin application password
}
```

```bash
# Su dung Application Password voi cURL
# Format: username:application-password (co dau cach, vd: "xxxx xxxx xxxx xxxx")
curl -X GET "https://example.com/wp-json/wp/v2/posts?status=draft" \
    -u "admin:XXXX XXXX XXXX XXXX XXXX XXXX"

# Hoac su dung Basic Auth header
# Base64 encode "admin:XXXX XXXX XXXX XXXX XXXX XXXX"
curl -X GET "https://example.com/wp-json/wp/v2/posts?status=draft" \
    -H "Authorization: Basic YWRtaW46WFhYWCBYWFhYIFhYWFggWFhYWCBYWFhYIFhYWFg="
```

```javascript
// Su dung Application Password trong JavaScript
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

Can plugin ho tro (vi du: JWT Authentication for WP REST API).

```php
<?php
// Cau hinh trong wp-config.php
define( 'JWT_AUTH_SECRET_KEY', 'your-secret-key-here' );
define( 'JWT_AUTH_CORS_ENABLE', true );
```

```bash
# Buoc 1: Lay token
curl -X POST "https://example.com/wp-json/jwt-auth/v1/token" \
    -H "Content-Type: application/json" \
    -d '{
        "username": "admin",
        "password": "your-password"
    }'
# Response: { "token": "eyJ0eXAi...", "user_email": "...", ... }

# Buoc 2: Su dung token
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
            throw new Error('Dang nhap that bai');
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

// Su dung
const api = new WPApiClient('https://example.com');
await api.login('admin', 'password');
const posts = await api.getPosts({ per_page: 5 });
```

### 4.4. Custom Authentication

```php
<?php
// Them authentication method rieng
add_filter( 'rest_authentication_errors', 'my_custom_rest_authentication' );
function my_custom_rest_authentication( $result ) {
    // Neu da co ket qua tu authentication khac, khong lam gi
    if ( ! is_null( $result ) ) {
        return $result;
    }

    // Kiem tra API key trong header
    $api_key = isset( $_SERVER['HTTP_X_API_KEY'] ) ? $_SERVER['HTTP_X_API_KEY'] : '';

    if ( empty( $api_key ) ) {
        return $result; // Khong co API key, de authentication khac xu ly
    }

    // Xac thuc API key
    $user_id = my_validate_api_key( $api_key );
    if ( $user_id ) {
        wp_set_current_user( $user_id );
        return true;
    }

    return new WP_Error(
        'rest_invalid_api_key',
        'API key khong hop le.',
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

## 5. Tao Custom Endpoints - register_rest_route()

### 5.1. Endpoint don gian

```php
<?php
add_action( 'rest_api_init', 'my_register_custom_routes' );

function my_register_custom_routes() {

    // GET /wp-json/myplugin/v1/hello
    register_rest_route( 'myplugin/v1', '/hello', array(
        'methods'             => WP_REST_Server::READABLE,  // = 'GET'
        'callback'            => 'my_hello_callback',
        'permission_callback' => '__return_true',  // Cho phep tat ca truy cap
    ) );
}

function my_hello_callback( WP_REST_Request $request ) {
    return new WP_REST_Response( array(
        'message' => 'Xin chao tu REST API!',
        'time'    => current_time( 'mysql' ),
    ), 200 );
}
```

### 5.2. Endpoint voi tham so

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
                'description'       => 'Slug danh muc san pham',
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
                'description'       => 'ID san pham',
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

    // Them headers pagination
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
            'Khong tim thay san pham.',
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

### 5.3. Endpoint voi nhieu methods

```php
<?php
add_action( 'rest_api_init', 'my_register_crud_routes' );

function my_register_crud_routes() {

    // Collection route: GET (list) va POST (create)
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

### 6.1. Cac loai permission callback

```php
<?php
add_action( 'rest_api_init', 'my_register_permission_routes' );

function my_register_permission_routes() {

    // 1. Cho phep tat ca (public)
    register_rest_route( 'myplugin/v1', '/public-data', array(
        'methods'             => 'GET',
        'callback'            => 'my_public_callback',
        'permission_callback' => '__return_true',
    ) );

    // 2. Yeu cau dang nhap
    register_rest_route( 'myplugin/v1', '/private-data', array(
        'methods'             => 'GET',
        'callback'            => 'my_private_callback',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ) );

    // 3. Yeu cau capability cu the
    register_rest_route( 'myplugin/v1', '/admin-data', array(
        'methods'             => 'GET',
        'callback'            => 'my_admin_callback',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );

    // 4. Kiem tra quyen tren doi tuong cu the
    register_rest_route( 'myplugin/v1', '/posts/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'my_update_post_callback',
        'permission_callback' => function( WP_REST_Request $request ) {
            $post_id = $request->get_param( 'id' );
            return current_user_can( 'edit_post', $post_id );
        },
    ) );

    // 5. Kiem tra nhieu dieu kien
    register_rest_route( 'myplugin/v1', '/restricted', array(
        'methods'             => 'POST',
        'callback'            => 'my_restricted_callback',
        'permission_callback' => 'my_check_multiple_permissions',
    ) );
}

function my_check_multiple_permissions( WP_REST_Request $request ) {
    // Phai dang nhap
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            'Ban can dang nhap de thuc hien thao tac nay.',
            array( 'status' => 401 )
        );
    }

    // Phai co quyen edit_posts
    if ( ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error(
            'rest_forbidden',
            'Ban khong co quyen thuc hien thao tac nay.',
            array( 'status' => 403 )
        );
    }

    // Kiem tra rate limiting (vi du)
    $user_id    = get_current_user_id();
    $last_action = get_user_meta( $user_id, '_last_api_action', true );
    if ( $last_action && ( time() - $last_action ) < 60 ) {
        return new WP_Error(
            'rest_rate_limited',
            'Vui long doi 60 giay giua cac request.',
            array( 'status' => 429 )
        );
    }

    return true;
}
```

### 6.2. Permission cho owner

```php
<?php
// Chi cho phep user chinh sua du lieu cua chinh minh
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
            // Admin co the sua bat ky ai
            if ( current_user_can( 'manage_options' ) ) {
                return true;
            }
            // User thuong chi sua cua minh
            $target_user_id = $request->get_param( 'user_id' );
            return get_current_user_id() === intval( $target_user_id );
        },
    ),
) );
```

---

## 7. Schema va Validation

### 7.1. Dinh nghia Schema

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
                'description' => 'ID duy nhat cua lien he.',
                'type'        => 'integer',
                'context'     => array( 'view', 'edit' ),
                'readonly'    => true,
            ),
            'name' => array(
                'description' => 'Ho ten lien he.',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
                'required'    => true,
            ),
            'email' => array(
                'description' => 'Dia chi email.',
                'type'        => 'string',
                'format'      => 'email',
                'context'     => array( 'view', 'edit' ),
                'required'    => true,
            ),
            'phone' => array(
                'description' => 'So dien thoai.',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
            ),
            'message' => array(
                'description' => 'Noi dung lien he.',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
                'required'    => true,
            ),
            'status' => array(
                'description' => 'Trang thai lien he.',
                'type'        => 'string',
                'enum'        => array( 'new', 'read', 'replied', 'closed' ),
                'default'     => 'new',
                'context'     => array( 'view', 'edit' ),
            ),
            'created_at' => array(
                'description' => 'Ngay tao.',
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => array( 'view' ),
                'readonly'    => true,
            ),
        ),
    );
}
```

### 7.2. Validate va Sanitize Callbacks

```php
<?php
function my_get_contact_args() {
    return array(
        'name' => array(
            'required'          => true,
            'type'              => 'string',
            'description'       => 'Ho ten lien he',
            'validate_callback' => function( $value, $request, $param ) {
                if ( strlen( $value ) < 2 ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        'Ten phai co it nhat 2 ky tu.',
                        array( 'status' => 400 )
                    );
                }
                if ( strlen( $value ) > 100 ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        'Ten khong duoc qua 100 ky tu.',
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
            'description'       => 'Dia chi email',
            'validate_callback' => function( $value ) {
                if ( ! is_email( $value ) ) {
                    return new WP_Error(
                        'rest_invalid_email',
                        'Dia chi email khong hop le.',
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
            'description'       => 'So dien thoai',
            'validate_callback' => function( $value ) {
                if ( ! empty( $value ) && ! preg_match( '/^[0-9\+\-\s\(\)]{8,20}$/', $value ) ) {
                    return new WP_Error(
                        'rest_invalid_phone',
                        'So dien thoai khong hop le.',
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
            'description'       => 'Noi dung lien he',
            'validate_callback' => function( $value ) {
                if ( strlen( $value ) < 10 ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        'Noi dung phai co it nhat 10 ky tu.',
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
            'description'       => 'Trang thai lien he',
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
            'Khong the luu lien he.',
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

### 7.3. register_rest_field() - Them truong vao endpoint co san

```php
<?php
add_action( 'rest_api_init', 'my_register_custom_fields' );

function my_register_custom_fields() {

    // Them truong 'view_count' vao endpoint /wp/v2/posts
    register_rest_field( 'post', 'view_count', array(
        'get_callback'    => function( $post_arr ) {
            return (int) get_post_meta( $post_arr['id'], '_view_count', true );
        },
        'update_callback' => function( $value, $post ) {
            return update_post_meta( $post->ID, '_view_count', absint( $value ) );
        },
        'schema'          => array(
            'description' => 'So luot xem bai viet.',
            'type'        => 'integer',
            'context'     => array( 'view', 'edit' ),
        ),
    ) );

    // Them truong 'featured_image_url' vao endpoint posts
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
            'description' => 'URL anh dai dien.',
            'type'        => 'string',
            'format'      => 'uri',
            'context'     => array( 'view' ),
            'readonly'    => true,
        ),
    ) );

    // Them truong vao nhieu post types cung luc
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
                'description' => 'Thoi gian doc uoc tinh (phut).',
                'type'        => 'integer',
                'context'     => array( 'view' ),
                'readonly'    => true,
            ),
        ) );
    }

    // Them truong vao endpoint users
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
            'description' => 'Lien ket mang xa hoi.',
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

Khi can tao nhieu endpoints lien quan, nen su dung WP_REST_Controller de to chuc code tot hon.

```php
<?php
/**
 * REST Controller cho Bookmarks
 */
class My_Bookmarks_REST_Controller extends WP_REST_Controller {

    protected $namespace = 'myplugin/v1';
    protected $rest_base = 'bookmarks';

    /**
     * Dang ky routes
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
                        'description' => 'ID cua bookmark.',
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
     * Lay danh sach bookmarks
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

        // Dem tong
        $total = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $values )
        );

        // Lay du lieu
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
     * Lay 1 bookmark
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
                'Khong tim thay bookmark.',
                array( 'status' => 404 )
            );
        }

        return $this->prepare_item_for_response( $item, $request );
    }

    /**
     * Tao bookmark moi
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
            return new WP_Error( 'rest_db_error', 'Khong the tao bookmark.', array( 'status' => 500 ) );
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
     * Cap nhat bookmark
     */
    public function update_item( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookmarks';

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request['id'] )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_bookmark_not_found', 'Khong tim thay bookmark.', array( 'status' => 404 ) );
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
     * Xoa bookmark
     */
    public function delete_item( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookmarks';

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request['id'] )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_bookmark_not_found', 'Khong tim thay bookmark.', array( 'status' => 404 ) );
        }

        $response = $this->prepare_item_for_response( $item, $request );

        $wpdb->delete( $table, array( 'id' => $request['id'] ), array( '%d' ) );

        $data = $response->get_data();
        $data['deleted'] = true;

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Kiem tra quyen
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
            return new WP_Error( 'rest_not_logged_in', 'Ban can dang nhap.', array( 'status' => 401 ) );
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
            return new WP_Error( 'rest_bookmark_not_found', 'Khong tim thay bookmark.', array( 'status' => 404 ) );
        }

        if ( (int) $item->user_id !== get_current_user_id() ) {
            return new WP_Error( 'rest_forbidden', 'Ban khong co quyen.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Chuan bi response
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

        // Loc theo _fields parameter
        $fields = $this->get_fields_for_response( $request );
        if ( is_array( $fields ) ) {
            $data = array_intersect_key( $data, array_flip( $fields ) );
        }

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Dinh nghia schema
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
                    'description' => 'ID duy nhat cua bookmark.',
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'title' => array(
                    'description' => 'Tieu de bookmark.',
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
                    'description' => 'Ghi chu.',
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
                'created_at' => array(
                    'description' => 'Ngay tao.',
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
     * Tham so cho collection (list)
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['per_page']['default'] = 20;
        $params['per_page']['maximum'] = 100;

        return $params;
    }
}

// Dang ky controller
add_action( 'rest_api_init', function() {
    $controller = new My_Bookmarks_REST_Controller();
    $controller->register_routes();
} );
```

---

## 9. REST API trong Plugin - Vi du CRUD hoan chinh

Vi du day du ve mot plugin quan ly "Tasks" voi REST API.

### 9.1. Plugin header va activation

```php
<?php
/**
 * Plugin Name: My Tasks API
 * Description: Quan ly cong viec voi REST API
 * Version: 1.0.0
 * Author: Developer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MY_TASKS_VERSION', '1.0.0' );
define( 'MY_TASKS_DB_VERSION', '1.0' );
define( 'MY_TASKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Tao bang khi activate
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

// Xoa bang khi uninstall
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
     * GET /tasks - Danh sach tasks
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

        // Admin co the xem tat ca
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

        // Dem tong
        $count_values = $values;
        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}",
                $count_values
            )
        );

        // Lay du lieu
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
     * GET /tasks/<id> - Chi tiet task
     */
    public function get_item( $request ) {
        $item = $this->get_task( $request['id'] );

        if ( is_wp_error( $item ) ) {
            return $item;
        }

        return $this->prepare_item_for_response( $item, $request );
    }

    /**
     * POST /tasks - Tao task moi
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
            return new WP_Error( 'rest_db_error', 'Khong the tao task: ' . $wpdb->last_error, array( 'status' => 500 ) );
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
     * PUT/PATCH /tasks/<id> - Cap nhat task
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

        // Neu chuyen sang 'done', ghi nhan thoi gian hoan thanh
        if ( isset( $update_data['status'] ) && 'done' === $update_data['status'] && 'done' !== $item->status ) {
            $update_data['completed_at'] = current_time( 'mysql' );
            $update_format[]             = '%s';
        }

        // Neu chuyen TU 'done' sang trang thai khac, xoa completed_at
        if ( isset( $update_data['status'] ) && 'done' !== $update_data['status'] && 'done' === $item->status ) {
            $update_data['completed_at'] = null;
            $update_format[]             = '%s';
        }

        if ( empty( $update_data ) ) {
            return new WP_Error( 'rest_no_data', 'Khong co du lieu de cap nhat.', array( 'status' => 400 ) );
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
            return new WP_Error( 'rest_db_error', 'Khong the cap nhat task.', array( 'status' => 500 ) );
        }

        $updated_item = $this->get_task( $request['id'] );

        do_action( 'my_tasks_rest_after_update', $updated_item, $item, $request );

        return $this->prepare_item_for_response( $updated_item, $request );
    }

    /**
     * DELETE /tasks/<id> - Xoa task
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
     * Batch update - Cap nhat nhieu tasks cung luc
     */
    public function batch_update( $request ) {
        global $wpdb;

        $ids    = $request->get_param( 'ids' );
        $status = $request->get_param( 'status' );

        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return new WP_Error( 'rest_invalid_ids', 'Danh sach IDs khong hop le.', array( 'status' => 400 ) );
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
     * Thong ke tasks
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
            return new WP_Error( 'rest_not_logged_in', 'Ban can dang nhap.', array( 'status' => 401 ) );
        }

        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $task = $this->get_task( $task_id );
        if ( is_wp_error( $task ) ) {
            return $task;
        }

        if ( (int) $task->user_id !== get_current_user_id() ) {
            return new WP_Error( 'rest_forbidden', 'Ban khong co quyen truy cap task nay.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Helper: Lay task tu database
     */
    private function get_task( $id ) {
        global $wpdb;

        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id )
        );

        if ( ! $item ) {
            return new WP_Error( 'rest_task_not_found', 'Khong tim thay task.', array( 'status' => 404 ) );
        }

        return $item;
    }

    /**
     * Chuan bi response
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

        // Them thong tin user
        $user = get_userdata( $item->user_id );
        if ( $user ) {
            $data['user'] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 48 ) ),
            );
        }

        // Loc theo _fields
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
     * Tham so cho collection
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

// Dang ky controller
add_action( 'rest_api_init', function() {
    $controller = new My_Tasks_REST_Controller();
    $controller->register_routes();
} );
```

### 9.3. Su dung API tu frontend (JavaScript)

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
            throw new Error(error.message || 'Request that bai');
        }

        const data = await response.json();
        return {
            data,
            total: parseInt(response.headers.get('X-WP-Total') || '0'),
            totalPages: parseInt(response.headers.get('X-WP-TotalPages') || '0'),
        };
    }

    // Lay danh sach tasks
    async list(params = {}) {
        const query = new URLSearchParams(params).toString();
        const url = query ? `${this.baseUrl}?${query}` : this.baseUrl;
        return this.request(url);
    }

    // Lay 1 task
    async get(id) {
        return this.request(`${this.baseUrl}/${id}`);
    }

    // Tao task moi
    async create(data) {
        return this.request(this.baseUrl, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    // Cap nhat task
    async update(id, data) {
        return this.request(`${this.baseUrl}/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    // Xoa task
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

    // Thong ke
    async stats() {
        return this.request(`${this.baseUrl}/stats`);
    }
}

// Su dung
const tasksAPI = new TasksAPI();

// Lay danh sach tasks chua hoan thanh, sap xep theo due_date
tasksAPI.list({
    status: 'todo',
    orderby: 'due_date',
    order: 'ASC',
    per_page: 20,
}).then(result => {
    console.log(`Tong: ${result.total} tasks`);
    result.data.forEach(task => {
        console.log(`[${task.priority}] ${task.title} - Han: ${task.due_date}`);
    });
});

// Tao task moi
tasksAPI.create({
    title: 'Hoan thanh bao cao',
    description: 'Bao cao hang thang cho phong kinh doanh',
    priority: 'high',
    due_date: '2024-12-31T17:00:00',
}).then(result => {
    console.log('Da tao task ID:', result.data.id);
});

// Cap nhat trang thai
tasksAPI.update(5, { status: 'done' }).then(result => {
    console.log('Da hoan thanh:', result.data.title);
});

// Xoa task
tasksAPI.delete(10).then(result => {
    console.log('Da xoa task');
});
```

---

## 10. Best Practices

### 10.1. Luon khai bao permission_callback

```php
<?php
// SAI: Thieu permission_callback (se co warning tu WP 5.5+)
register_rest_route( 'myplugin/v1', '/data', array(
    'methods'  => 'GET',
    'callback' => 'my_callback',
) );

// DUNG: Luon co permission_callback
register_rest_route( 'myplugin/v1', '/data', array(
    'methods'             => 'GET',
    'callback'            => 'my_callback',
    'permission_callback' => '__return_true',  // Public
) );
```

### 10.2. Su dung namespace dung cach

```php
<?php
// DUNG: Namespace co version
// myplugin/v1
register_rest_route( 'myplugin/v1', '/items', array( /* ... */ ) );

// DUNG: Khi can version moi (khong break version cu)
register_rest_route( 'myplugin/v2', '/items', array( /* ... */ ) );

// SAI: Khong co version
register_rest_route( 'myplugin', '/items', array( /* ... */ ) );

// SAI: Dung namespace cua WordPress core
register_rest_route( 'wp/v2', '/my-items', array( /* ... */ ) );
```

### 10.3. Validate va sanitize DU LIEU

```php
<?php
// DUNG: Validate va sanitize day du
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
                    return new WP_Error( 'empty_title', 'Tieu de khong duoc de trong.' );
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

### 10.4. Tra ve HTTP status code dung

```php
<?php
// 200 OK - Thanh cong (GET, PUT, DELETE)
return new WP_REST_Response( $data, 200 );

// 201 Created - Tao thanh cong (POST)
$response = new WP_REST_Response( $data, 201 );
$response->header( 'Location', rest_url( 'myplugin/v1/items/' . $id ) );
return $response;

// 204 No Content - Xoa thanh cong (khong tra du lieu)
return new WP_REST_Response( null, 204 );

// 400 Bad Request - Du lieu khong hop le
return new WP_Error( 'invalid_data', 'Du lieu khong hop le.', array( 'status' => 400 ) );

// 401 Unauthorized - Chua xac thuc
return new WP_Error( 'rest_not_logged_in', 'Ban can dang nhap.', array( 'status' => 401 ) );

// 403 Forbidden - Khong co quyen
return new WP_Error( 'rest_forbidden', 'Ban khong co quyen.', array( 'status' => 403 ) );

// 404 Not Found - Khong tim thay
return new WP_Error( 'not_found', 'Khong tim thay.', array( 'status' => 404 ) );

// 500 Internal Server Error - Loi server
return new WP_Error( 'server_error', 'Loi he thong.', array( 'status' => 500 ) );
```

### 10.5. Su dung _fields de giam du lieu tra ve

```javascript
// Chi lay cac truong can thiet
fetch('/wp-json/wp/v2/posts?_fields=id,title,link,date&per_page=5');

// Thay vi lay toan bo du lieu cua post (rat nhieu truong)
fetch('/wp-json/wp/v2/posts?per_page=5');  // Tra ve nhieu du lieu thua
```

### 10.6. Cache REST API responses

```php
<?php
// Su dung transient de cache
function my_cached_endpoint( WP_REST_Request $request ) {
    $cache_key = 'rest_cache_' . md5( serialize( $request->get_params() ) );
    $data      = get_transient( $cache_key );

    if ( false === $data ) {
        // Cache miss - tinh toan du lieu
        $data = expensive_computation();
        set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );
    }

    $response = new WP_REST_Response( $data, 200 );

    // Them cache headers
    $response->header( 'Cache-Control', 'max-age=300' ); // 5 phut
    $response->header( 'X-Cache', false === get_transient( $cache_key ) ? 'MISS' : 'HIT' );

    return $response;
}

// Xoa cache khi du lieu thay doi
add_action( 'save_post', function( $post_id ) {
    // Xoa cac transients lien quan
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
// Rate limiting don gian
add_filter( 'rest_pre_dispatch', 'my_rest_rate_limit', 10, 3 );

function my_rest_rate_limit( $result, $server, $request ) {
    // Chi ap dung cho endpoints cua minh
    $route = $request->get_route();
    if ( strpos( $route, '/myplugin/' ) === false ) {
        return $result;
    }

    $ip         = $_SERVER['REMOTE_ADDR'];
    $cache_key  = 'rate_limit_' . md5( $ip );
    $requests   = (int) get_transient( $cache_key );
    $max_requests = 60;  // 60 requests moi phut

    if ( $requests >= $max_requests ) {
        return new WP_Error(
            'rest_rate_limit',
            'Qua nhieu request. Vui long thu lai sau.',
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
// Cho phep CORS cho REST API
add_action( 'rest_api_init', function() {
    // Xoa header CORS mac dinh cua WP
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

    // Them header CORS tuy chinh
    add_filter( 'rest_pre_serve_request', function( $served, $result, $request, $server ) {
        $origin = get_http_origin();

        // Chi cho phep cac domain cu the
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

### 10.9. Disable REST API cho nguoi dung chua dang nhap (neu can)

```php
<?php
// Chan tat ca REST API cho nguoi chua dang nhap
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            'Ban can dang nhap de su dung API.',
            array( 'status' => 401 )
        );
    }

    return $result;
} );

// HOAC chi chan mot so endpoints
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // Cho phep cac endpoint public
    $public_routes = array( '/wp/v2/posts', '/wp/v2/categories', '/wp/v2/tags' );
    $current_route = $_SERVER['REQUEST_URI'];

    foreach ( $public_routes as $route ) {
        if ( strpos( $current_route, $route ) !== false ) {
            return $result;
        }
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'Can dang nhap.', array( 'status' => 401 ) );
    }

    return $result;
} );
```

### 10.10. Testing REST API

```bash
# Test voi cURL
# Lay danh sach
curl -s "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Authorization: Basic $(echo -n 'admin:app-password' | base64)" | python3 -m json.tool

# Tao moi
curl -s -X POST "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic $(echo -n 'admin:app-password' | base64)" \
    -d '{"title": "Test task", "priority": "high"}' | python3 -m json.tool

# Test validation (thieu title)
curl -s -X POST "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Content-Type: application/json" \
    -H "Authorization: Basic $(echo -n 'admin:app-password' | base64)" \
    -d '{"priority": "high"}' | python3 -m json.tool
# Ket qua: {"code":"rest_missing_callback_param","message":"Missing parameter(s): title",...}

# Test permission (khong xac thuc)
curl -s -X POST "https://example.com/wp-json/myplugin/v1/tasks" \
    -H "Content-Type: application/json" \
    -d '{"title": "Test"}' | python3 -m json.tool
# Ket qua: {"code":"rest_not_logged_in","message":"Ban can dang nhap.",...}
```

```php
<?php
// Unit test voi PHPUnit (WP Test Suite)
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

Tai lieu tham khao:
- WordPress REST API Handbook: https://developer.wordpress.org/rest-api/
- REST API Reference: https://developer.wordpress.org/rest-api/reference/
- WP_REST_Controller: https://developer.wordpress.org/reference/classes/wp_rest_controller/
