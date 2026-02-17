# 08 - Quản Lý Người Dùng (Users Management)

> **Source chính**: `wp-admin/users.php`, `wp-admin/user-new.php`, `wp-admin/user-edit.php`, `wp-admin/profile.php`
> **Dành cho**: Laravel Developer muốn hiểu hệ thống quản lý user trong WordPress
> **Tương đương Laravel**: User Model + Auth + Spatie Permission + Profile Controller

---

## Mục Lục

1. [Tổng Quan Users Management](#1-tổng-quan-users-management)
2. [Users List Screen (users.php)](#2-users-list-screen)
3. [Add New User (user-new.php)](#3-add-new-user)
4. [Edit User / Profile (user-edit.php)](#4-edit-user--profile)
5. [Roles & Capabilities System](#5-roles--capabilities-system)
6. [Application Passwords](#6-application-passwords)
7. [User Sessions](#7-user-sessions)
8. [Password Hashing](#8-password-hashing)
9. [DB: Users Lưu Gì?](#9-db-users-lưu-gì)
10. [Hooks Users Admin](#10-hooks-users-admin)
11. [Custom User Fields](#11-custom-user-fields)
12. [WP_Users_List_Table Chi Tiết](#12-wp_users_list_table-chi-tiết)
13. [User Query & Search](#13-user-query--search)
14. [So Sánh Laravel](#14-so-sánh-laravel)

---

## 1. Tổng Quan Users Management

### URLs trong Admin

| URL | Chức năng | Capability yêu cầu |
|-----|-----------|---------------------|
| `/wp-admin/users.php` | Danh sách users | `list_users` |
| `/wp-admin/user-new.php` | Thêm user mới | `create_users` |
| `/wp-admin/user-edit.php?user_id=X` | Sửa user khác | `edit_users` |
| `/wp-admin/profile.php` | Sửa profile mình | `read` (tất cả user) |

### Source Files

| File | Kích thước | Vai trò |
|------|-----------|---------|
| `wp-admin/users.php` | ~24KB | Màn hình danh sách users |
| `wp-admin/user-new.php` | ~25KB | Form tạo user mới |
| `wp-admin/user-edit.php` | ~40KB | Form chỉnh sửa user/profile (file lớn nhất!) |
| `wp-admin/profile.php` | ~283B | Chỉ define `IS_PROFILE_PAGE = true` rồi include `user-edit.php` |
| `wp-admin/includes/user.php` | - | Các hàm admin cho user (edit_user, add_user...) |
| `wp-admin/includes/class-wp-users-list-table.php` | - | Class hiển thị bảng danh sách users |
| `wp-includes/class-wp-user.php` | - | Class WP_User chính |
| `wp-includes/class-wp-roles.php` | - | Class WP_Roles - quản lý roles |
| `wp-includes/class-wp-role.php` | - | Class WP_Role - đại diện một role |

### Capabilities Liên Quan

```
list_users     → Xem danh sách users
create_users   → Tạo user mới
edit_users     → Sửa user khác
delete_users   → Xóa user
promote_users  → Thay đổi role user
edit_user      → Meta capability, map thành edit_users
remove_users   → Xóa user khỏi site (multisite)
```

### Profile vs Edit User

```php
// wp-admin/profile.php - Chỉ 3 dòng thôi!
// Source: /wp-admin/profile.php
define( 'IS_PROFILE_PAGE', true );
require_once __DIR__ . '/user-edit.php';
```

Cả 2 trang dùng chung file `user-edit.php`, khác nhau ở biến `IS_PROFILE_PAGE`:
- `IS_PROFILE_PAGE = true` → User đang sửa profile chính mình
- `IS_PROFILE_PAGE = false` → Admin đang sửa user khác

Điều này ảnh hưởng đến:
- Hooks nào được fire (`personal_options_update` vs `edit_user_profile_update`)
- Fields nào hiển thị (Personal Options chỉ hiện khi sửa chính mình)
- Nút "Role" chỉ hiện khi admin sửa user khác

---

## 2. Users List Screen

### Source & Flow Khởi Tạo

```php
// Source: /wp-admin/users.php

// 1. Load admin bootstrap
require_once __DIR__ . '/admin.php';

// 2. Kiểm tra quyền - phải có 'list_users'
if ( ! current_user_can( 'list_users' ) ) {
    wp_die(
        '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
        '<p>' . __( 'Sorry, you are not allowed to list users.' ) . '</p>',
        403
    );
}

// 3. Lấy instance list table
$wp_list_table = _get_list_table( 'WP_Users_List_Table' );
$pagenum       = $wp_list_table->get_pagenum();

// 4. Thiết lập title và screen options
$title       = __( 'Users' );
$parent_file = 'users.php';
add_screen_option( 'per_page' );
```

### Columns Mặc Định

| Column | Key | Nội dung |
|--------|-----|----------|
| Checkbox | `cb` | Checkbox cho bulk actions |
| Username | `username` | Avatar (Gravatar 32px) + user_login + action links |
| Name | `name` | display_name |
| Email | `email` | user_email (link mailto:) |
| Role | `role` | Tên role (Administrator, Editor...) |
| Posts | `posts` | Số bài viết, click để filter theo author trong edit.php |

### Role Filter Tabs

Phía trên bảng có các tab lọc theo role:

```
All (15) | Administrator (2) | Editor (3) | Author (5) | Contributor (3) | Subscriber (2)
```

Chỉ hiện các role có ít nhất 1 user. Role chưa có user sẽ bị ẩn.

### Action Links Mỗi Row

Khi hover vào một row, hiện các action links:

```
Edit | Delete | View | Send password reset
```

- **Edit** → Chuyển đến `user-edit.php?user_id=X`
- **Delete** → Chuyển đến `users.php?action=delete&users=X` (trang xác nhận xóa)
- **View** → Xem author archive page trên frontend
- **Send password reset** → Gửi email link reset password (cần `edit_users` capability)

### Bulk Actions

```
- Delete                         (cần delete_users)
- Send Password Reset            (cần edit_users)
- Change Role to... (dropdown)   (cần promote_users)
    → Administrator
    → Editor
    → Author
    → Contributor
    → Subscriber
    → — No role for this site —
```

### Search Users

```php
// Search box cho phép tìm kiếm user
// Tìm theo: user_login, user_email, user_url, user_nicename, display_name
// Cũng tìm trong usermeta: first_name, last_name, nickname
$wp_list_table->search_box( __( 'Search Users' ), 'user' );
```

### Xử Lý Xóa User

Khi xóa user, WordPress yêu cầu chọn cách xử lý nội dung:

```
Lựa chọn 1: Xóa toàn bộ nội dung (posts, links, comments)
Lựa chọn 2: Gán lại nội dung cho user khác (dropdown chọn user)
```

```php
// Source: /wp-admin/users.php - phần xử lý action 'dodelete'
check_admin_referer( 'delete-users' );

if ( empty( $_REQUEST['users'] ) ) {
    wp_redirect( $redirect );
    exit;
}

foreach ( $userids as $id ) {
    if ( ! current_user_can( 'delete_user', $id ) ) {
        continue;
    }

    if ( $id === $current_user->ID ) {
        // Không cho phép tự xóa chính mình
        continue;
    }

    // Reassign nội dung nếu được chọn
    $reassign = isset( $_REQUEST[ "reassign_user_{$id}" ] )
        ? absint( $_REQUEST[ "reassign_user_{$id}" ] )
        : null;

    wp_delete_user( $id, $reassign );
}
```

---

## 3. Add New User

### Source & Capability Check

```php
// Source: /wp-admin/user-new.php

// Single site: cần create_users
// Multisite: cần create_users HOẶC promote_users
if ( is_multisite() ) {
    if ( ! current_user_can( 'create_users' ) && ! current_user_can( 'promote_users' ) ) {
        wp_die( /* ... */ );
    }
} elseif ( ! current_user_can( 'create_users' ) ) {
    wp_die( /* ... */ );
}
```

### Form Fields

| Field | Name | Bắt buộc | Mô tả |
|-------|------|----------|-------|
| Username | `user_login` | Co | Không thể đổi sau khi tạo |
| Email | `email` | Co | Phải unique |
| First Name | `first_name` | Không | Lưu vào usermeta |
| Last Name | `last_name` | Không | Lưu vào usermeta |
| Website | `url` | Không | user_url trong wp_users |
| Password | `pass1`, `pass2` | Co | Auto-generated hoặc tự nhập |
| Send Notification | `send_user_notification` | Không | Checkbox gửi email thông báo |
| Role | `role` | Co | Dropdown chọn role |

### Flow Tạo User (Single Site)

```php
// Source: /wp-admin/user-new.php - action 'createuser'

// Bước 1: Verify nonce
check_admin_referer( 'create-user', '_wpnonce_create-user' );

// Bước 2: Kiểm tra quyền
if ( ! current_user_can( 'create_users' ) ) {
    wp_die( /* ... */ );
}

// Bước 3: Gọi edit_user() - hàm chung cho cả create và update
// Source: /wp-admin/includes/user.php
$user_id = edit_user();

// Bên trong edit_user(), khi $user_id = 0 (tạo mới):
// → Gọi wp_insert_user()
// → Tạo record trong wp_users
// → Tạo các meta trong wp_usermeta

if ( is_wp_error( $user_id ) ) {
    $add_user_errors = $user_id;
} else {
    // Thành công, redirect về users.php
    if ( current_user_can( 'list_users' ) ) {
        $redirect = 'users.php?update=add&id=' . $user_id;
    } else {
        $redirect = add_query_arg( 'update', 'add', 'user-new.php' );
    }
    wp_redirect( $redirect );
    die();
}
```

### Hàm edit_user() Chi Tiết

```php
// Source: /wp-admin/includes/user.php

function edit_user( $user_id = 0 ) {
    $wp_roles = wp_roles();
    $user     = new stdClass();
    $user_id  = (int) $user_id;

    if ( $user_id ) {
        // UPDATE mode
        $update           = true;
        $user->ID         = $user_id;
        $userdata         = get_userdata( $user_id );
        $user->user_login = wp_slash( $userdata->user_login );
    } else {
        // CREATE mode
        $update = false;
    }

    // Thu thập dữ liệu từ $_POST
    if ( ! $update && isset( $_POST['user_login'] ) ) {
        $user->user_login = sanitize_user( wp_unslash( $_POST['user_login'] ), true );
    }

    // Role
    if ( isset( $_POST['role'] ) && current_user_can( 'promote_users' ) ) {
        $new_role = sanitize_text_field( $_POST['role'] );
        $editable_roles = get_editable_roles();
        if ( ! empty( $new_role ) && empty( $editable_roles[ $new_role ] ) ) {
            wp_die( __( 'Sorry, you are not allowed to give users that role.' ), 403 );
        }
        $user->role = $new_role;
    }

    // Email, URL, Name, Nickname...
    if ( isset( $_POST['email'] ) ) {
        $user->user_email = sanitize_text_field( wp_unslash( $_POST['email'] ) );
    }
    if ( isset( $_POST['first_name'] ) ) {
        $user->first_name = sanitize_text_field( $_POST['first_name'] );
    }
    if ( isset( $_POST['last_name'] ) ) {
        $user->last_name = sanitize_text_field( $_POST['last_name'] );
    }
    // ... nhiều fields khác

    // Validation
    $errors = new WP_Error();
    // Kiểm tra email hợp lệ, unique, etc.

    // Nếu không lỗi → insert hoặc update
    if ( $update ) {
        $user_id = wp_update_user( $user );
    } else {
        $user_id = wp_insert_user( $user );
        // Gửi notification email nếu checkbox được check
        if ( ! is_wp_error( $user_id ) && isset( $_POST['send_user_notification'] ) ) {
            wp_send_new_user_notifications( $user_id, 'both' );
        }
    }

    return $user_id;
}
```

### wp_insert_user() - Core Function

```php
// Source: /wp-includes/user.php

// wp_insert_user() là hàm core thực sự tạo user
// Nó thực hiện:
// 1. Sanitize và validate dữ liệu
// 2. Hash password bằng wp_hash_password()
// 3. INSERT vào bảng wp_users
// 4. Set role bằng WP_User::set_role()
// 5. Tạo các usermeta mặc định (nickname, first_name, last_name...)
// 6. Fire action 'user_register'

$user_id = wp_insert_user([
    'user_login'    => 'john_doe',
    'user_email'    => 'john@example.com',
    'user_pass'     => 'SecurePassword123!',
    'first_name'    => 'John',
    'last_name'     => 'Doe',
    'user_url'      => 'https://johndoe.com',
    'role'          => 'author',
    'display_name'  => 'John Doe',
    'nickname'      => 'johnd',
    'description'   => 'Tác giả blog',
    'locale'        => 'vi',
]);

if ( is_wp_error( $user_id ) ) {
    echo $user_id->get_error_message();
} else {
    echo "User created with ID: " . $user_id;
}
```

### Multisite: Thêm User Hiện Có Vào Site

```php
// Source: /wp-admin/user-new.php - action 'adduser'

// Trong multisite, có thể thêm user đã tồn tại trong network vào site hiện tại
check_admin_referer( 'add-user', '_wpnonce_add-user' );

$user_email   = wp_unslash( $_REQUEST['email'] );
$user_details = get_user_by( 'email', $user_email );

if ( ! $user_details ) {
    // User không tồn tại
    wp_redirect( add_query_arg( array( 'update' => 'does_not_exist' ), 'user-new.php' ) );
    die();
}

// Thêm user vào site với role được chọn
$result = add_existing_user_to_blog([
    'user_id' => $user_details->ID,
    'role'    => $_REQUEST['role'],
]);
```

---

## 4. Edit User / Profile

### Source & Flow

```php
// Source: /wp-admin/user-edit.php

require_once __DIR__ . '/admin.php';
require_once ABSPATH . 'wp-admin/includes/translation-install.php';

$action          = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
$user_id         = ! empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;
$wp_http_referer = ! empty( $_REQUEST['wp_http_referer'] ) ? sanitize_url( $_REQUEST['wp_http_referer'] ) : '';

$current_user = wp_get_current_user();

// Xác định IS_PROFILE_PAGE nếu chưa define (profile.php đã define rồi)
if ( ! defined( 'IS_PROFILE_PAGE' ) ) {
    define( 'IS_PROFILE_PAGE', ( $user_id === $current_user->ID ) );
}
```

### Xử Lý Action Update

```php
// Source: /wp-admin/user-edit.php

switch ( $action ) {
    case 'update':
        // Verify nonce
        check_admin_referer( 'update-user_' . $user_id );

        // Kiểm tra quyền
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            wp_die( __( 'Sorry, you are not allowed to edit this user.' ) );
        }

        // Fire hooks khác nhau tùy profile hay edit user
        if ( IS_PROFILE_PAGE ) {
            // Hook: personal_options_update
            do_action( 'personal_options_update', $user_id );
        } else {
            // Hook: edit_user_profile_update
            do_action( 'edit_user_profile_update', $user_id );
        }

        // Gọi edit_user() để thực hiện update
        $errors = edit_user( $user_id );

        if ( ! is_wp_error( $errors ) ) {
            $redirect = add_query_arg( 'updated', true, get_edit_user_link( $user_id ) );
            wp_redirect( $redirect );
            exit;
        }
        break;
}
```

### Các Sections Trong Form Edit User

#### Section 1: Personal Options (chỉ khi edit chính mình)

```php
// Chỉ hiện khi IS_PROFILE_PAGE = true HOẶC khi user không có quyền edit
// Source: /wp-admin/user-edit.php

// a) Visual Editor
// rich_editing: 'true' hoặc 'false' trong wp_usermeta
// Nếu 'false' → tắt TinyMCE, chỉ dùng text editor

// b) Syntax Highlighting
// syntax_highlighting: 'true' hoặc 'false'
// Tắt CodeMirror trong Theme/Plugin Editor

// c) Admin Color Scheme
// admin_color: 'fresh' (mặc định), 'light', 'blue', 'coffee', 'ectoplasm',
//              'midnight', 'ocean', 'sunrise'
// Ảnh hưởng CSS admin panel

// d) Keyboard Shortcuts
// comment_shortcuts: 'true' hoặc 'false'
// Bật phím tắt cho quản lý comments

// e) Toolbar (Show Admin Bar)
// show_admin_bar_front: 'true' hoặc 'false'
// Hiện thanh admin bar trên frontend

// f) Language
// locale: '' (default site language) hoặc 'vi', 'en_US', etc.
// Ngôn ngữ admin riêng cho user này
```

#### Section 2: Name

```php
// Username (readonly - không thể thay đổi!)
// user_login trong bảng wp_users

// First Name → first_name trong wp_usermeta
// Last Name → last_name trong wp_usermeta
// Nickname → nickname trong wp_usermeta (bắt buộc)

// Display Name → display_name trong wp_users
// Dropdown cho phép chọn từ:
// - user_login
// - first_name
// - last_name
// - nickname
// - first_name + last_name
// - last_name + first_name
```

#### Section 3: Contact Info

```php
// Email (bắt buộc, phải unique)
// user_email trong wp_users
// Khi thay đổi email trên profile page → gửi email xác nhận trước

// Website
// user_url trong wp_users
```

#### Section 4: About

```php
// Biographical Info
// description trong wp_usermeta
// Textarea, cho phép HTML giới hạn

// Profile Picture
// Dùng Gravatar (gravatar.com)
// Hash email → URL avatar: https://www.gravatar.com/avatar/{md5_email}
// WordPress KHÔNG lưu avatar locally
```

#### Section 5: Account Management

```php
// a) New Password
// Nút "Set New Password" → generate password tự động
// Hoặc user tự nhập password mới
// Cả 2 fields pass1, pass2 phải khớp nhau

// b) Sessions
// Hiện danh sách sessions hiện tại
// Nút "Log Out Everywhere Else" → xóa tất cả sessions trừ session hiện tại
// Source: WP_Session_Tokens::destroy_other_sessions()

// c) Application Passwords (từ WP 5.6)
// Tạo password riêng cho REST API / XML-RPC
// KHÔNG dùng được cho login thường qua wp-login.php
// Mỗi application password có tên riêng (ví dụ: "Mobile App", "CI/CD")
```

#### Section 6: Role (chỉ admin thấy khi edit user khác)

```php
// Dropdown chọn role
// Chỉ hiện khi:
// - IS_PROFILE_PAGE = false (admin sửa user khác)
// - current_user_can('promote_users')
// - user đang edit KHÔNG phải super admin (trừ khi mình là super admin)

// Hiển thị qua filter 'editable_roles'
$editable_roles = get_editable_roles();
```

---

## 5. Roles & Capabilities System

### 5 Default Roles

| Role | Tên hiển thị | Level | Mô tả |
|------|-------------|-------|-------|
| `administrator` | Administrator | 10 | Toàn quyền |
| `editor` | Editor | 7 | Quản lý tất cả bài viết |
| `author` | Author | 2 | Viết và publish bài mình |
| `contributor` | Contributor | 1 | Viết bài nhưng không publish |
| `subscriber` | Subscriber | 0 | Chỉ đọc và quản lý profile |

### Classes Core

```
wp-includes/class-wp-roles.php  → WP_Roles  (quản lý tất cả roles)
wp-includes/class-wp-role.php   → WP_Role   (đại diện 1 role)
wp-includes/class-wp-user.php   → WP_User   (đại diện 1 user + roles + caps)
```

### Cách Lưu Trữ Roles Trong DB

```php
// 1. Roles Definition: wp_options → option_name = 'wp_user_roles'
// Lưu serialized array định nghĩa tất cả roles và capabilities

// Khi unserialize:
[
    'administrator' => [
        'name'         => 'Administrator',
        'capabilities' => [
            'switch_themes'          => true,
            'edit_themes'            => true,
            'activate_plugins'       => true,
            'edit_plugins'           => true,
            'edit_users'             => true,
            'edit_files'             => true,
            'manage_options'         => true,
            'moderate_comments'      => true,
            'manage_categories'      => true,
            'manage_links'           => true,
            'upload_files'           => true,
            'import'                 => true,
            'unfiltered_html'        => true,
            'edit_posts'             => true,
            'edit_others_posts'      => true,
            'edit_published_posts'   => true,
            'publish_posts'          => true,
            'edit_pages'             => true,
            'read'                   => true,
            'delete_posts'           => true,
            'delete_published_posts' => true,
            'delete_others_posts'    => true,
            'delete_pages'           => true,
            // ... rất nhiều caps khác
        ],
    ],
    'editor' => [
        'name'         => 'Editor',
        'capabilities' => [
            'moderate_comments'    => true,
            'manage_categories'    => true,
            'manage_links'         => true,
            'upload_files'         => true,
            'edit_posts'           => true,
            'edit_others_posts'    => true,
            'edit_published_posts' => true,
            'publish_posts'        => true,
            'edit_pages'           => true,
            'read'                 => true,
            // ...
        ],
    ],
    // ... author, contributor, subscriber
]

// 2. User's Role: wp_usermeta → meta_key = 'wp_capabilities'
// Serialized: a:1:{s:13:"administrator";b:1;}
// Giải mã: ['administrator' => true]

// 3. User Level (DEPRECATED nhưng vẫn lưu):
// wp_usermeta → meta_key = 'wp_user_level'
// Giá trị: 0-10 (tương ứng subscriber → administrator)
```

### WP_Roles Class

```php
// Source: /wp-includes/class-wp-roles.php

class WP_Roles {
    public $roles;          // Array tất cả roles + capabilities
    public $role_objects;   // Array WP_Role objects
    public $role_names;     // Array role_name => display_name
    public $role_key;       // Option name: 'wp_user_roles'
    public $use_db = true;  // Có dùng database không
    protected $site_id = 0; // Site ID (cho multisite)

    public function __construct( $site_id = null ) {
        global $wp_user_roles;
        $this->use_db = empty( $wp_user_roles );
        $this->for_site( $site_id );
    }
}
```

### Code Quản Lý Roles

```php
// === THÊM ROLE MỚI ===
// Lưu ý: Chỉ chạy 1 lần (trong activation hook)!
// Vì nó ghi vào database, không cần chạy lại mỗi request.

add_role( 'shop_manager', 'Quản Lý Cửa Hàng', [
    'read'              => true,
    'edit_posts'        => true,
    'delete_posts'      => true,
    'manage_products'   => true,  // custom capability
    'manage_orders'     => true,  // custom capability
]);

// === THÊM CAPABILITY VÀO ROLE ĐÃ CÓ ===
$role = get_role( 'editor' );
$role->add_cap( 'manage_products' );    // Thêm cap
$role->remove_cap( 'manage_products' ); // Xóa cap

// === XÓA ROLE ===
// Chỉ chạy trong deactivation hook
remove_role( 'shop_manager' );

// === KIỂM TRA QUYỀN ===
// Cách 1: Kiểm tra quyền chung
if ( current_user_can( 'manage_options' ) ) {
    // Chỉ administrator
}

// Cách 2: Kiểm tra quyền trên object cụ thể (meta capability)
if ( current_user_can( 'edit_post', $post_id ) ) {
    // User có thể sửa post này không?
    // WordPress sẽ map 'edit_post' thành primitive cap phù hợp
    // VD: nếu user là author của post → cần 'edit_posts'
    //     nếu user KHÔNG phải author → cần 'edit_others_posts'
}

// Cách 3: Kiểm tra trên WP_User object
$user = get_user_by( 'id', 5 );
if ( $user->has_cap( 'edit_posts' ) ) {
    // User này có quyền edit_posts
}

// === USER MULTIPLE ROLES ===
// WordPress cho phép user có nhiều roles
$user = new WP_User( 5 );
$user->add_role( 'editor' );       // Thêm role (giữ nguyên roles cũ)
$user->remove_role( 'subscriber' ); // Xóa 1 role
$user->set_role( 'administrator' ); // Xóa TẤT CẢ roles cũ, set role mới
```

### Meta Capabilities vs Primitive Capabilities

```php
// Primitive Capabilities: quyền gốc, lưu trong role definition
// Ví dụ: edit_posts, delete_posts, manage_options, list_users

// Meta Capabilities: quyền trừu tượng, map sang primitive tùy context
// Ví dụ: edit_post (cụ thể 1 post), delete_user (cụ thể 1 user)

// WordPress dùng map_meta_cap() để chuyển đổi
// Source: /wp-includes/capabilities.php

// Ví dụ: current_user_can( 'edit_post', 42 )
// → map_meta_cap( 'edit_post', user_id, 42 )
// → Nếu user là author của post 42 → return ['edit_posts']
// → Nếu user KHÔNG phải author → return ['edit_others_posts']
// → Nếu post đã publish → thêm 'edit_published_posts'
// → WordPress kiểm tra user có tất cả primitive caps này không

// Ví dụ thêm custom meta cap:
add_filter( 'map_meta_cap', function( $caps, $cap, $user_id, $args ) {
    if ( 'edit_product' === $cap ) {
        $post = get_post( $args[0] );
        if ( $post && $post->post_author == $user_id ) {
            return ['edit_products'];  // Chỉ cần cap cơ bản
        }
        return ['edit_others_products']; // Cần cap cao hơn
    }
    return $caps;
}, 10, 4 );
```

### Tạo Role System Cho Plugin (WooCommerce-style)

```php
// Trong plugin activation hook:
register_activation_hook( __FILE__, 'my_plugin_activate' );

function my_plugin_activate() {
    // Tạo role mới
    add_role( 'shop_manager', 'Quản Lý Cửa Hàng', [
        'read'                   => true,
        'edit_posts'             => false,
        'delete_posts'           => false,
        'manage_woocommerce'     => true,
        'view_woocommerce_reports' => true,
        'edit_product'           => true,
        'read_product'           => true,
        'delete_product'         => true,
        'edit_products'          => true,
        'edit_others_products'   => true,
        'publish_products'       => true,
        'read_private_products'  => true,
    ]);

    add_role( 'customer', 'Khách Hàng', [
        'read' => true,
    ]);

    // Thêm caps cho administrator
    $admin = get_role( 'administrator' );
    $admin->add_cap( 'manage_woocommerce' );
    $admin->add_cap( 'view_woocommerce_reports' );
    $admin->add_cap( 'edit_product' );
    // ...
}

// Trong deactivation hook:
register_deactivation_hook( __FILE__, 'my_plugin_deactivate' );

function my_plugin_deactivate() {
    remove_role( 'shop_manager' );
    remove_role( 'customer' );
    // KHÔNG xóa caps khỏi administrator ở đây
    // vì có thể user vẫn muốn giữ dữ liệu
}
```

---

## 6. Application Passwords

### Tổng Quan

Từ WordPress 5.6, hệ thống Application Passwords cho phép tạo password riêng để xác thực qua REST API hoặc XML-RPC, mà không cần dùng password chính.

```php
// Source: /wp-includes/class-wp-application-passwords.php

// Application Passwords:
// - KHÔNG dùng được cho login thường (wp-login.php)
// - Chỉ dùng cho REST API và XML-RPC authentication
// - Mỗi password có tên riêng (ví dụ: "Mobile App")
// - Có thể revoke từng password riêng lẻ
// - Tracking: last_used timestamp, last_ip
```

### Cách Lưu Trong DB

```php
// Lưu trong: wp_usermeta → meta_key = '_application_passwords'
// Serialized array, mỗi phần tử:

[
    [
        'uuid'      => '550e8400-e29b-41d4-a716-446655440000',
        'name'      => 'Mobile App',
        'password'  => '$wp$2y$12$xxxxx...',  // Hashed bằng wp_hash_password()
        'created'   => 1640000000,             // Unix timestamp
        'last_used' => 1640100000,             // Unix timestamp hoặc null
        'last_ip'   => '192.168.1.100',        // IP cuối cùng sử dụng hoặc null
    ],
    [
        'uuid'      => '550e8400-e29b-41d4-a716-446655440001',
        'name'      => 'CI/CD Pipeline',
        'password'  => '$wp$2y$12$yyyyy...',
        'created'   => 1640200000,
        'last_used' => null,
        'last_ip'   => null,
    ],
]
```

### Sử Dụng Application Password

```bash
# Gọi REST API với Application Password
# Format: username:application_password (base64 encoded)
# Application password hiển thị dạng: xxxx xxxx xxxx xxxx xxxx xxxx (có spaces)
# Khi dùng, bỏ spaces: xxxxxxxxxxxxxxxxxxxxxxxx

curl -X GET https://example.com/wp-json/wp/v2/posts \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx"

# Hoặc dùng header Authorization
curl -X GET https://example.com/wp-json/wp/v2/posts \
  -H "Authorization: Basic $(echo -n 'admin:xxxxxxxxxxxxxxxxxxxxxxxx' | base64)"
```

### Kiểm Tra Application Passwords Có Sẵn

```php
// Kiểm tra tính năng Application Passwords có bật cho user không
if ( wp_is_application_passwords_available_for_user( $user_id ) ) {
    // Có thể tạo application password
}

// Tạo application password bằng code
$result = WP_Application_Passwords::create_new_application_password(
    $user_id,
    [
        'name' => 'My Custom App',
    ]
);
// $result[0] = password (plain text, chỉ hiện 1 lần!)
// $result[1] = array thông tin đã lưu (password đã hashed)
```

---

## 7. User Sessions

### Source & Class

```php
// Source: /wp-includes/class-wp-session-tokens.php (abstract class)
// Source: /wp-includes/class-wp-user-meta-session-tokens.php (implementation)

// Default implementation lưu sessions trong usermeta
// Có thể swap bằng filter 'session_token_manager'

$manager = apply_filters( 'session_token_manager', 'WP_User_Meta_Session_Tokens' );
```

### Cách Lưu Trong DB

```php
// Lưu trong: wp_usermeta → meta_key = 'session_tokens'
// Serialized array, key = sha256 hash của token

[
    'abc123hash...' => [
        'expiration' => 1640100000,   // Unix timestamp hết hạn
        'ip'         => '192.168.1.100',
        'ua'         => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)...',
        'login'      => 1640000000,   // Unix timestamp đăng nhập
    ],
    'def456hash...' => [
        'expiration' => 1640200000,
        'ip'         => '10.0.0.1',
        'ua'         => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0...',
        'login'      => 1640050000,
    ],
]
```

### Quản Lý Sessions

```php
// Lấy instance session tokens cho user
$sessions = WP_Session_Tokens::get_instance( $user_id );

// Lấy tất cả sessions
$all_sessions = $sessions->get_all();

// Xóa tất cả sessions trừ token hiện tại (Log Out Everywhere Else)
$sessions->destroy_others( $current_token );

// Xóa TẤT CẢ sessions (Log Out Everywhere)
$sessions->destroy_all();

// Xóa 1 session cụ thể
$sessions->destroy( $token );

// Verify 1 session token
$session_info = $sessions->get( $token );
// Return array session info hoặc null nếu không hợp lệ
```

### Hiển Thị Trên Profile

```php
// Source: /wp-admin/user-edit.php

// Khi sửa profile chính mình, section Sessions hiện:
// - Số sessions đang active
// - Nút "Log Out Everywhere Else"

// Khi admin sửa user khác:
// - Hiện thông tin sessions
// - Nút "Log Out Everywhere" (xóa TẤT CẢ sessions của user đó)
```

---

## 8. Password Hashing

### Cơ Chế Hash

```php
// Từ WordPress 6.8, mặc định dùng bcrypt
// Các version cũ dùng PHPass (Portable PHP Password Hashing Framework)

// Source: /wp-includes/class-wp-user.php (comment)
// "The `user_pass` property is now hashed using bcrypt by default instead of phpass.
//  Existing passwords may still be hashed using phpass."

// Bcrypt hash format:    $wp$2y$12$xxxxx...
// PHPass hash format:    $P$Bxxxxx...

// WordPress tự động upgrade hash cũ sang bcrypt khi user login lại
```

### Các Hàm Password

```php
// 1. Hash password
$hashed = wp_hash_password( 'plain_password' );
// → '$wp$2y$12$...' (bcrypt) hoặc '$P$B...' (PHPass cũ)

// 2. Kiểm tra password
$is_valid = wp_check_password( 'plain_password', $hashed_password, $user_id );
// → true hoặc false
// Nếu password đúng và hash là PHPass cũ → tự động rehash sang bcrypt

// 3. Set password mới cho user
wp_set_password( 'new_password', $user_id );
// → Hash password mới
// → UPDATE wp_users SET user_pass = ...
// → Xóa tất cả session tokens (force logout)
// → Nếu đang đổi password chính mình, session hiện tại sẽ được tạo lại

// 4. Generate password ngẫu nhiên
$random_pass = wp_generate_password( 24, true, true );
// Tham số: length, include_special_chars, extra_special_chars
// Mặc định: 12 ký tự, có special chars, không extra special
```

### Cơ Chế Authentication

```php
// Khi user đăng nhập:
// Source: /wp-includes/user.php → wp_authenticate()

// Flow:
// 1. wp_authenticate( $username, $password )
// 2. → apply_filters( 'authenticate', null, $username, $password )
// 3. → wp_authenticate_username_password() (default handler)
// 4.   → get_user_by( 'login', $username )
// 5.   → wp_check_password( $password, $user->user_pass, $user->ID )
// 6. Nếu đúng → return WP_User object
// 7. Nếu sai → return WP_Error

// Cookie auth:
// Sau login thành công:
// → wp_set_auth_cookie( $user_id, $remember )
// → Tạo session token mới
// → Set cookie: wordpress_logged_in_{hash}
// → Set cookie: wordpress_{hash} (chỉ cho admin area)
```

---

## 9. DB: Users Lưu Gì?

### Bảng wp_users

```sql
CREATE TABLE wp_users (
    ID                 bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_login         varchar(60) NOT NULL DEFAULT '',
    user_pass          varchar(255) NOT NULL DEFAULT '',
    user_nicename      varchar(50) NOT NULL DEFAULT '',
    user_email         varchar(100) NOT NULL DEFAULT '',
    user_url           varchar(100) NOT NULL DEFAULT '',
    user_registered    datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    user_activation_key varchar(255) NOT NULL DEFAULT '',
    user_status        int(11) NOT NULL DEFAULT 0,
    display_name       varchar(250) NOT NULL DEFAULT '',
    PRIMARY KEY (ID),
    KEY user_login_key (user_login),
    KEY user_nicename (user_nicename),
    KEY user_email (user_email)
);
```

| Column | Mô tả |
|--------|--------|
| `ID` | Auto increment, primary key |
| `user_login` | Username đăng nhập (unique, không đổi được) |
| `user_pass` | Password đã hash (bcrypt hoặc PHPass) |
| `user_nicename` | URL-friendly version của username (dùng trong author URL) |
| `user_email` | Email (unique) |
| `user_url` | Website URL |
| `user_registered` | Ngày tạo tài khoản (UTC) |
| `user_activation_key` | Key kích hoạt / reset password |
| `user_status` | Trạng thái (0 = active, ít dùng ở single site) |
| `display_name` | Tên hiển thị công khai |

### Bảng wp_usermeta (Quan Trọng!)

```sql
CREATE TABLE wp_usermeta (
    umeta_id   bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id    bigint(20) unsigned NOT NULL DEFAULT 0,
    meta_key   varchar(255) DEFAULT NULL,
    meta_value longtext,
    PRIMARY KEY (umeta_id),
    KEY user_id (user_id),
    KEY meta_key (meta_key(191))
);
```

### Meta Keys Quan Trọng

| Meta Key | Giá Trị | Mô Tả |
|----------|---------|--------|
| `nickname` | String | Biệt danh |
| `first_name` | String | Tên |
| `last_name` | String | Họ |
| `description` | String | Tiểu sử |
| `rich_editing` | 'true'/'false' | Bật Visual Editor |
| `syntax_highlighting` | 'true'/'false' | Bật Syntax Highlighting |
| `comment_shortcuts` | 'true'/'false' | Bật phím tắt comments |
| `admin_color` | 'fresh', 'light'... | Admin color scheme |
| `use_ssl` | '0'/'1' | Force SSL khi admin |
| `show_admin_bar_front` | 'true'/'false' | Hiện admin bar frontend |
| `locale` | '' hoặc 'vi'... | Ngôn ngữ admin |
| `wp_capabilities` | Serialized | Roles của user |
| `wp_user_level` | 0-10 | Level (deprecated) |
| `dismissed_wp_pointers` | String | Các pointer đã dismiss |
| `show_welcome_panel` | '0'/'1' | Hiện welcome panel |
| `session_tokens` | Serialized | Login sessions |
| `wp_dashboard_quick_press_last_post_id` | Int | ID draft quick press |
| `wp_user-settings` | String | Cài đặt UI admin |
| `wp_user-settings-time` | Timestamp | Thời gian cập nhật settings |
| `_application_passwords` | Serialized | Application passwords |
| `_new_email` | Serialized | Email mới chờ xác nhận |
| `closedpostboxes_*` | Serialized | Các meta box đã đóng |
| `metaboxhidden_*` | Serialized | Các meta box đã ẩn |
| `manageedit-postcolumnshidden` | Serialized | Columns đã ẩn |

### So Sánh Với Laravel

```
WordPress wp_users          ≈  Laravel users table
WordPress wp_usermeta       ≈  Laravel user_profiles hoặc JSON column
WordPress wp_capabilities   ≈  Laravel model_has_roles (Spatie)
WordPress session_tokens    ≈  Laravel sessions table
```

---

## 10. Hooks Users Admin

### Hooks Khi Tạo / Sửa / Xóa User

```php
// === TẠO USER MỚI ===

// Sau khi user mới được tạo thành công
do_action( 'user_register', int $user_id, array $userdata );
// $userdata = array dữ liệu đã insert

// === CẬP NHẬT PROFILE ===

// Trước khi update (trên trang profile chính mình)
do_action( 'personal_options_update', int $user_id );

// Trước khi update (admin sửa user khác)
do_action( 'edit_user_profile_update', int $user_id );

// Sau khi update thành công
do_action( 'profile_update', int $user_id, WP_User $old_user_data, array $userdata );

// === XÓA USER ===

// Trước khi xóa (có thể cancel hoặc cleanup)
do_action( 'delete_user', int $user_id, int|null $reassign_id, WP_User $user );

// Sau khi đã xóa
do_action( 'deleted_user', int $user_id, int|null $reassign_id, WP_User $user );
```

### Hooks Roles

```php
// Khi set role (xóa roles cũ, gán role mới)
do_action( 'set_user_role', int $user_id, string $role, array $old_roles );

// Khi thêm role (giữ roles cũ)
do_action( 'add_user_role', int $user_id, string $role );

// Khi xóa 1 role
do_action( 'remove_user_role', int $user_id, string $role );
```

### Hooks Profile Form

```php
// Thêm fields vào form profile (sửa chính mình)
do_action( 'show_user_profile', WP_User $user );

// Thêm fields vào form edit user (admin sửa user khác)
do_action( 'edit_user_profile', WP_User $user );

// Personal Options section (trước nút đóng table)
do_action( 'personal_options', WP_User $user );
```

### Hooks Users List Table

```php
// Custom columns header
add_filter( 'manage_users_columns', function( $columns ) {
    $columns['phone'] = 'Số ĐT';
    return $columns;
});

// Render custom column
add_filter( 'manage_users_custom_column', function( $output, $column_name, $user_id ) {
    if ( 'phone' === $column_name ) {
        return get_user_meta( $user_id, 'phone', true );
    }
    return $output;
}, 10, 3 );

// Sortable columns
add_filter( 'manage_users_sortable_columns', function( $columns ) {
    $columns['phone'] = 'phone';
    return $columns;
});

// Action links mỗi row
add_filter( 'user_row_actions', function( $actions, $user ) {
    $actions['custom_link'] = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'admin.php?page=my-page&user=' . $user->ID ),
        'Custom Action'
    );
    return $actions;
}, 10, 2 );

// Lọc roles hiển thị trong dropdown
add_filter( 'editable_roles', function( $roles ) {
    // Ẩn role administrator cho non-super-admins
    if ( ! is_super_admin() ) {
        unset( $roles['administrator'] );
    }
    return $roles;
});
```

### Hooks Authentication

```php
// Xác thực đăng nhập
do_action( 'wp_authenticate', string &$user_login, string &$user_password );

// Sau login thành công
do_action( 'wp_login', string $user_login, WP_User $user );

// Sau logout
do_action( 'wp_logout', int $user_id );

// Cookie events
do_action( 'auth_cookie_valid', array $cookie_elements, WP_User $user );
do_action( 'auth_cookie_expired', array $cookie_elements );
do_action( 'auth_cookie_bad_username', array $cookie_elements );
do_action( 'auth_cookie_bad_hash', array $cookie_elements );

// Filter trước authenticate (có thể short-circuit)
apply_filters( 'authenticate', null|WP_User|WP_Error $user, string $username, string $password );
```

---

## 11. Custom User Fields

### Thêm Fields Vào Profile Form

```php
// Thêm field cho CẢ 2 trường hợp: tự sửa profile VÀ admin sửa user khác

// Hook cho profile page (sửa chính mình)
add_action( 'show_user_profile', 'my_custom_user_fields' );

// Hook cho edit user page (admin sửa user khác)
add_action( 'edit_user_profile', 'my_custom_user_fields' );

function my_custom_user_fields( $user ) {
    $phone   = get_user_meta( $user->ID, 'phone', true );
    $company = get_user_meta( $user->ID, 'company', true );
    $address = get_user_meta( $user->ID, 'address', true );
    ?>
    <h3>Thông Tin Bổ Sung</h3>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="phone">Số Điện Thoại</label></th>
            <td>
                <input type="text" name="phone" id="phone"
                       value="<?php echo esc_attr( $phone ); ?>"
                       class="regular-text" />
                <p class="description">Nhập số điện thoại liên hệ.</p>
            </td>
        </tr>
        <tr>
            <th><label for="company">Công Ty</label></th>
            <td>
                <input type="text" name="company" id="company"
                       value="<?php echo esc_attr( $company ); ?>"
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="address">Địa Chỉ</label></th>
            <td>
                <textarea name="address" id="address" rows="3"
                          class="regular-text"><?php echo esc_textarea( $address ); ?></textarea>
            </td>
        </tr>
    </table>
    <?php
}
```

### Lưu Custom Fields

```php
// Hook lưu khi sửa profile chính mình
add_action( 'personal_options_update', 'save_my_custom_user_fields' );

// Hook lưu khi admin sửa user khác
add_action( 'edit_user_profile_update', 'save_my_custom_user_fields' );

function save_my_custom_user_fields( $user_id ) {
    // Kiểm tra quyền
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    // Verify nonce (optional nếu dùng nonce riêng)
    // WordPress đã verify nonce 'update-user_X' rồi

    // Sanitize và lưu
    if ( isset( $_POST['phone'] ) ) {
        update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
    }

    if ( isset( $_POST['company'] ) ) {
        update_user_meta( $user_id, 'company', sanitize_text_field( $_POST['company'] ) );
    }

    if ( isset( $_POST['address'] ) ) {
        update_user_meta( $user_id, 'address', sanitize_textarea_field( $_POST['address'] ) );
    }
}
```

### Hiển Thị Custom Fields Trong Users List

```php
// Thêm column
add_filter( 'manage_users_columns', function( $columns ) {
    $columns['phone']   = 'Số ĐT';
    $columns['company'] = 'Công Ty';
    return $columns;
});

// Render column content
add_filter( 'manage_users_custom_column', function( $output, $column_name, $user_id ) {
    switch ( $column_name ) {
        case 'phone':
            $phone = get_user_meta( $user_id, 'phone', true );
            return $phone ? esc_html( $phone ) : '—';

        case 'company':
            $company = get_user_meta( $user_id, 'company', true );
            return $company ? esc_html( $company ) : '—';
    }
    return $output;
}, 10, 3 );

// Làm cho column sortable
add_filter( 'manage_users_sortable_columns', function( $columns ) {
    $columns['company'] = 'company';
    return $columns;
});

// Xử lý sort query
add_action( 'pre_get_users', function( $query ) {
    if ( 'company' === $query->get( 'orderby' ) ) {
        $query->set( 'meta_key', 'company' );
        $query->set( 'orderby', 'meta_value' );
    }
});
```

### Thêm Custom Contact Methods

```php
// WordPress có sẵn filter để thêm contact methods
add_filter( 'user_contactmethods', function( $methods, $user ) {
    $methods['facebook']  = 'Facebook URL';
    $methods['twitter']   = 'Twitter/X Username';
    $methods['linkedin']  = 'LinkedIn URL';
    $methods['zalo']      = 'Số Zalo';
    $methods['telegram']  = 'Telegram Username';

    // Xóa method mặc định nếu muốn
    // unset( $methods['aim'] );
    // unset( $methods['jabber'] );
    // unset( $methods['yim'] );

    return $methods;
}, 10, 2 );

// Các contact methods này tự động:
// 1. Hiển thị trong section Contact Info trên profile form
// 2. Lưu vào wp_usermeta với meta_key = tên method
// 3. Không cần viết code save riêng!
```

---

## 12. WP_Users_List_Table Chi Tiết

### Source

```php
// Source: /wp-admin/includes/class-wp-users-list-table.php
// Extends: WP_List_Table

class WP_Users_List_Table extends WP_List_Table {
    // Kế thừa toàn bộ cơ chế WP_List_Table:
    // - Pagination
    // - Sorting
    // - Bulk actions
    // - Search
    // - Column management
}
```

### Customize Users List Table

```php
// Thêm extra filter dropdowns (giống category filter trong Posts list)
add_action( 'restrict_manage_users', function( $which ) {
    // $which = 'top' hoặc 'bottom' (vị trí filter)
    if ( 'top' !== $which ) {
        return;
    }

    $departments = ['marketing', 'engineering', 'sales', 'support'];
    $current     = isset( $_GET['department'] ) ? $_GET['department'] : '';
    ?>
    <select name="department">
        <option value="">Tất cả phòng ban</option>
        <?php foreach ( $departments as $dept ) : ?>
            <option value="<?php echo esc_attr( $dept ); ?>"
                    <?php selected( $current, $dept ); ?>>
                <?php echo esc_html( ucfirst( $dept ) ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
});

// Xử lý filter query
add_filter( 'pre_get_users', function( $query ) {
    global $pagenow;

    if ( is_admin() && 'users.php' === $pagenow ) {
        if ( ! empty( $_GET['department'] ) ) {
            $query->set( 'meta_key', 'department' );
            $query->set( 'meta_value', sanitize_text_field( $_GET['department'] ) );
        }
    }
});
```

---

## 13. User Query & Search

### WP_User_Query

```php
// WordPress dùng WP_User_Query để query users
// Tương đương: User::query() trong Laravel

// Ví dụ 1: Lấy tất cả editors
$args = [
    'role'    => 'editor',
    'orderby' => 'display_name',
    'order'   => 'ASC',
];
$user_query = new WP_User_Query( $args );
$editors = $user_query->get_results(); // Array of WP_User objects

// Ví dụ 2: Search users
$args = [
    'search'         => '*john*',       // Tìm kiếm (wildcard)
    'search_columns' => ['user_login', 'user_email', 'display_name'],
];
$user_query = new WP_User_Query( $args );

// Ví dụ 3: Query với meta
$args = [
    'meta_query' => [
        [
            'key'     => 'company',
            'value'   => 'Acme Corp',
            'compare' => '=',
        ],
        [
            'key'     => 'phone',
            'value'   => '',
            'compare' => '!=',
        ],
    ],
    'number'  => 20,      // Limit
    'offset'  => 0,       // Offset
    'paged'   => 1,       // Page number
];
$user_query = new WP_User_Query( $args );

echo 'Total users found: ' . $user_query->get_total();
foreach ( $user_query->get_results() as $user ) {
    echo $user->display_name . ' - ' . $user->user_email . "\n";
}

// Ví dụ 4: Multiple roles
$args = [
    'role__in'     => ['author', 'editor'],  // Có 1 trong các roles này
    'role__not_in' => ['administrator'],       // KHÔNG có role này
];

// Ví dụ 5: Date query
$args = [
    'date_query' => [
        [
            'after'  => '2024-01-01',
            'before' => '2024-12-31',
        ],
    ],
];
```

### Helper Functions

```php
// Lấy user theo ID
$user = get_user_by( 'id', 5 );
$user = get_userdata( 5 ); // Alias

// Lấy user theo login, email, slug
$user = get_user_by( 'login', 'john_doe' );
$user = get_user_by( 'email', 'john@example.com' );
$user = get_user_by( 'slug', 'john-doe' ); // user_nicename

// Lấy user hiện tại
$current = wp_get_current_user(); // WP_User object
$user_id = get_current_user_id(); // Chỉ lấy ID

// Đếm users theo role
$counts = count_users();
// [
//     'total_users' => 150,
//     'avail_roles' => [
//         'administrator' => 2,
//         'editor'        => 10,
//         'author'        => 30,
//         'subscriber'    => 108,
//     ],
// ]

// User meta
$value = get_user_meta( $user_id, 'phone', true );       // Single value
$all   = get_user_meta( $user_id, 'phone', false );      // Array of values
update_user_meta( $user_id, 'phone', '0901234567' );      // Update/Create
delete_user_meta( $user_id, 'phone' );                    // Delete
add_user_meta( $user_id, 'phone', '0901234567', true );   // Add (unique=true)
```

---

## 14. So Sánh Laravel

### Users Table

| WordPress | Laravel |
|-----------|---------|
| `wp_users` table | `users` table |
| `wp_usermeta` table (EAV) | Columns trực tiếp hoặc JSON column |
| `wp_insert_user()` | `User::create()` |
| `wp_update_user()` | `$user->update()` |
| `wp_delete_user()` | `$user->delete()` |
| `get_user_by()` | `User::where()->first()` |
| `WP_User_Query` | `User::query()` / Eloquent Builder |

### Authentication

| WordPress | Laravel |
|-----------|---------|
| `wp_authenticate()` | `Auth::attempt()` |
| `wp_set_auth_cookie()` | `Auth::login()` |
| `wp_logout()` | `Auth::logout()` |
| `is_user_logged_in()` | `Auth::check()` |
| `current_user_can()` | `$user->can()` (Gate) |
| Cookie-based auth | Session-based auth |
| Application Passwords | Laravel Sanctum tokens |

### Roles & Capabilities

| WordPress | Laravel (Spatie Permission) |
|-----------|---------------------------|
| `add_role()` | `Role::create()` |
| `remove_role()` | `$role->delete()` |
| `$role->add_cap()` | `$role->givePermissionTo()` |
| `$role->remove_cap()` | `$role->revokePermissionTo()` |
| `$user->set_role()` | `$user->assignRole()` |
| `current_user_can()` | `$user->hasPermissionTo()` |
| `get_editable_roles()` | `Role::all()` |
| Lưu serialized trong options | Bảng roles, permissions, model_has_roles riêng |

### Profile

| WordPress | Laravel |
|-----------|---------|
| `user-edit.php` | ProfileController |
| `show_user_profile` hook | View blade template |
| `update_user_meta()` | `$user->update()` |
| Gravatar avatar | Custom upload hoặc package |
| Sessions UI built-in | Tự implement hoặc package |

### Key Differences

```
1. WordPress lưu user data theo EAV pattern (wp_usermeta)
   Laravel lưu trực tiếp trong columns

2. WordPress roles/caps lưu serialized trong wp_options + wp_usermeta
   Spatie Permission dùng bảng normalized riêng

3. WordPress password hash có backward compatibility (PHPass → bcrypt)
   Laravel luôn dùng bcrypt (hoặc argon2)

4. WordPress Application Passwords tương đương Sanctum tokens
   Nhưng Application Passwords chỉ dùng cho REST/XML-RPC

5. WordPress sessions lưu trong usermeta (serialized)
   Laravel sessions lưu trong file/database/redis (configurable)

6. WordPress user_login KHÔNG thể thay đổi sau khi tạo
   Laravel username/email có thể thay đổi bất cứ lúc nào
```

### Plugin Example: User Registration Form

```php
/**
 * Plugin hoàn chỉnh: Custom User Registration + Profile Fields
 * Tương đương Laravel: Custom Auth + Profile controller
 */

// === 1. Thêm custom role khi activate ===
register_activation_hook( __FILE__, function() {
    add_role( 'vendor', 'Nhà Cung Cấp', [
        'read'         => true,
        'edit_posts'   => true,
        'delete_posts' => true,
        'upload_files' => true,
    ]);
});

// === 2. Thêm fields vào registration form ===
add_action( 'register_form', function() {
    $phone = isset( $_POST['phone'] ) ? $_POST['phone'] : '';
    ?>
    <p>
        <label for="phone">Số Điện Thoại<br />
        <input type="text" name="phone" id="phone" class="input"
               value="<?php echo esc_attr( $phone ); ?>" size="25" />
        </label>
    </p>
    <?php
});

// === 3. Validate registration ===
add_filter( 'registration_errors', function( $errors, $sanitized_user_login, $user_email ) {
    if ( empty( $_POST['phone'] ) ) {
        $errors->add( 'phone_error', '<strong>Lỗi</strong>: Vui lòng nhập số điện thoại.' );
    } elseif ( ! preg_match( '/^0[0-9]{9}$/', $_POST['phone'] ) ) {
        $errors->add( 'phone_error', '<strong>Lỗi</strong>: Số điện thoại không hợp lệ.' );
    }
    return $errors;
}, 10, 3 );

// === 4. Lưu field sau registration ===
add_action( 'user_register', function( $user_id ) {
    if ( isset( $_POST['phone'] ) ) {
        update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
    }
});

// === 5. Hiển thị + lưu trên profile ===
add_action( 'show_user_profile', 'vendor_profile_fields' );
add_action( 'edit_user_profile', 'vendor_profile_fields' );

function vendor_profile_fields( $user ) {
    if ( ! in_array( 'vendor', $user->roles ) ) return;
    $phone = get_user_meta( $user->ID, 'phone', true );
    $tax_code = get_user_meta( $user->ID, 'tax_code', true );
    ?>
    <h3>Thông Tin Nhà Cung Cấp</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone">Số Điện Thoại</label></th>
            <td><input type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="tax_code">Mã Số Thuế</label></th>
            <td><input type="text" name="tax_code" value="<?php echo esc_attr( $tax_code ); ?>" class="regular-text" /></td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', 'save_vendor_fields' );
add_action( 'edit_user_profile_update', 'save_vendor_fields' );

function save_vendor_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return;
    update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ?? '' ) );
    update_user_meta( $user_id, 'tax_code', sanitize_text_field( $_POST['tax_code'] ?? '' ) );
}
```

---

## Tổng Kết

### Flow Tổng Quan Users Management

```
User truy cập /wp-admin/users.php
  → admin.php bootstrap
  → Kiểm tra list_users capability
  → WP_Users_List_Table render danh sách
  → Columns: Username, Name, Email, Role, Posts
  → Actions: Edit, Delete, View, Send Password Reset
  → Bulk: Delete, Change Role, Send Password Reset

Tạo user mới /wp-admin/user-new.php
  → Form: username, email, name, password, role
  → Submit → check_admin_referer()
  → edit_user() → wp_insert_user()
  → Insert wp_users + wp_usermeta
  → Fire 'user_register' hook
  → wp_send_new_user_notifications()

Sửa user /wp-admin/user-edit.php (hoặc profile.php)
  → Load user data từ DB
  → Render form với tất cả sections
  → Plugins thêm fields qua show_user_profile / edit_user_profile hooks
  → Submit → personal_options_update / edit_user_profile_update hooks
  → edit_user() → wp_update_user()
  → Fire 'profile_update' hook

Xóa user
  → Chọn reassign content hoặc delete all
  → wp_delete_user( $id, $reassign )
  → Fire 'delete_user' trước, 'deleted_user' sau
  → Xóa wp_users + wp_usermeta + cleanup posts/comments
```

### Files Quan Trọng Cần Nhớ

```
/wp-admin/users.php                              → Danh sách users
/wp-admin/user-new.php                           → Tạo user mới
/wp-admin/user-edit.php                          → Sửa user / profile
/wp-admin/profile.php                            → Redirect tới user-edit.php
/wp-admin/includes/user.php                      → Hàm edit_user(), add_user()
/wp-admin/includes/class-wp-users-list-table.php → List table class
/wp-includes/class-wp-user.php                   → WP_User class
/wp-includes/class-wp-roles.php                  → WP_Roles class
/wp-includes/class-wp-role.php                   → WP_Role class
/wp-includes/capabilities.php                    → map_meta_cap() và helpers
/wp-includes/user.php                            → wp_insert_user(), wp_update_user(), etc.
/wp-includes/pluggable.php                       → wp_authenticate(), wp_hash_password()
/wp-includes/class-wp-session-tokens.php         → Session management
/wp-includes/class-wp-application-passwords.php  → Application Passwords
```
