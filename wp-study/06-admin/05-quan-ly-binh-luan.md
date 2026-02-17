# 05 - Quản Lý Bình Luận trong WordPress Admin

> Tài liệu dành cho PHP Laravel developer chuyển sang WordPress.
> Phân tích chi tiết comment management, moderation flow, AJAX reply, hooks và cách lưu DB.

---

## Mục Lục

1. [Tổng Quan Comments Management](#1-tổng-quan-comments-management)
2. [Comments List Screen - edit-comments.php](#2-comments-list-screen---edit-commentsphp)
3. [Comment Status và Tabs](#3-comment-status-và-tabs)
4. [Bulk Actions](#4-bulk-actions)
5. [Single Comment Actions](#5-single-comment-actions)
6. [Comment Edit Form](#6-comment-edit-form)
7. [Inline Reply (AJAX)](#7-inline-reply-ajax)
8. [Comment Moderation Flow](#8-comment-moderation-flow)
9. [Discussion Settings ảnh hưởng](#9-discussion-settings-ảnh-hưởng)
10. [DB: Comments Lưu Gì?](#10-db-comments-lưu-gì)
11. [Hooks Comments Admin - Danh Sách Đầy Đủ](#11-hooks-comments-admin---danh-sách-đầy-đủ)
12. [Anti-Spam và Moderation](#12-anti-spam-và-moderation)
13. [Comment Types](#13-comment-types)
14. [WP_Comment_Query - Truy Vấn Comments](#14-wp_comment_query---truy-vấn-comments)
15. [Ví Dụ Thực Tế: Plugin Comment](#15-ví-dụ-thực-tế-plugin-comment)
16. [So Sánh Với Laravel](#16-so-sánh-với-laravel)
17. [Tổng Kết](#17-tổng-kết)

---

## 1. Tổng Quan Comments Management

### URLs Admin

| Trang | URL | Mô tả |
|-------|-----|-------|
| Comments List | `/wp-admin/edit-comments.php` | Danh sách tất cả bình luận |
| Edit Comment | `/wp-admin/comment.php?action=editcomment&c={id}` | Sửa bình luận |
| Moderate Comment | `/wp-admin/comment.php?action=approve&c={id}` | Duyệt/Spam/Trash |
| Comments on Post | `/wp-admin/edit-comments.php?p={post_id}` | Comments của 1 bài viết |

### Source Files Chính

```
wp-admin/
├── edit-comments.php                                # Comments list (462 dòng)
├── comment.php                                      # Single comment actions (388 dòng)
├── edit-form-comment.php                            # Comment edit form
├── includes/
│   ├── class-wp-comments-list-table.php             # List table class
│   └── ajax-actions.php                             # AJAX handlers (replyto-comment)
wp-includes/
├── comment.php                                      # Comment API functions
├── class-wp-comment.php                             # WP_Comment class
├── class-wp-comment-query.php                       # WP_Comment_Query class
└── comment-template.php                             # Comment template functions
```

### Capabilities (Quyền)

```php
// Xem danh sách comments - Source: wp-admin/edit-comments.php dòng 11
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die(
        '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
        '<p>' . __( 'Sorry, you are not allowed to edit comments.' ) . '</p>',
        403
    );
}

// Sửa comment cụ thể - Source: wp-admin/comment.php dòng 83
if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
    comment_footer_die( __( 'Sorry, you are not allowed to edit this comment.' ) );
}
```

Capabilities liên quan:
- `edit_posts` - Xem danh sách comments
- `edit_comment` - Meta capability, map tới `edit_posts` hoặc `edit_others_posts`
- `moderate_comments` - Duyệt/reject comments (Administrator, Editor)
- `edit_post` - Cần để moderate comments trên post cụ thể

---

## 2. Comments List Screen - edit-comments.php

### Flow khởi tạo

**Source**: `wp-admin/edit-comments.php`

```php
// Dòng 9-17: Bootstrap và kiểm tra quyền
require_once __DIR__ . '/admin.php';
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( /* ... */ );
}

// Dòng 19-20: Khởi tạo list table
$wp_list_table = _get_list_table( 'WP_Comments_List_Table' );
$pagenum       = $wp_list_table->get_pagenum();

// Dòng 22: Lấy action hiện tại
$doaction = $wp_list_table->current_action();
```

### WP_Comments_List_Table

**Source**: `wp-admin/includes/class-wp-comments-list-table.php`

Class này kế thừa `WP_List_Table` và cung cấp:
- Các cột: Author, Comment, In Response To, Submitted On
- Views: All, Mine, Pending, Approved, Spam, Trash
- Bulk actions: Approve, Unapprove, Mark as Spam, Move to Trash
- Row actions: Approve, Reply, Quick Edit, Edit, Spam, Trash

```php
// Các cột mặc định
public function get_columns() {
    return array(
        'cb'       => '<input type="checkbox" />',
        'author'   => __( 'Author' ),
        'comment'  => _x( 'Comment', 'column name' ),
        'response' => __( 'In Response To' ),
        'date'     => __( 'Submitted On' ),
    );
}
```

### Prepare Items (Query Comments)

```php
// Bên trong WP_Comments_List_Table::prepare_items()
$args = array(
    'status'       => $comment_status,
    'search'       => $search,
    'user_id'      => $user_id,
    'offset'       => $start,
    'number'       => $comments_per_page,
    'post_id'      => $post_id,
    'type'         => $comment_type,
    'orderby'      => $orderby,
    'order'        => $order,
);

// Sử dụng WP_Comment_Query
$_comments = get_comments( $args );
```

---

## 3. Comment Status và Tabs

### Các trạng thái comment

| Status | Giá trị DB `comment_approved` | Mô tả |
|--------|-------------------------------|-------|
| Approved | `'1'` | Đã duyệt, hiển thị public |
| Pending (Unapproved) | `'0'` | Chờ duyệt |
| Spam | `'spam'` | Đánh dấu spam |
| Trash | `'trash'` | Đã xóa (có thể khôi phục) |

### Đếm comments theo status

```php
// Source: wp-admin/edit-comments.php dòng 167-201
$comments_count = wp_count_comments();

/*
object(stdClass) (
    'approved'            => 125,
    'awaiting_moderation' => 3,   // Tương đương 'moderated'
    'moderated'           => 3,   // Pending
    'spam'                => 15,
    'trash'               => 2,
    'post-trashed'        => 0,
    'total_comments'      => 145,
    'all'                 => 128,  // approved + moderated
)
*/

// Đếm cho một post cụ thể
$post_comments_count = wp_count_comments( $post_id );
```

### Tab "Mine"

Tab "Mine" hiển thị comments được viết bởi user hiện tại:

```php
// Trong WP_Comments_List_Table, view 'mine' filter theo:
$args['user_id'] = get_current_user_id();
```

### Notification Badge

Badge đỏ trên menu "Comments" hiển thị số pending comments:

```php
// Source: wp-admin/menu.php
$awaiting_mod      = wp_count_comments();
$awaiting_mod      = $awaiting_mod->moderated;
$awaiting_mod_i18n = number_format_i18n( $awaiting_mod );

// Hiển thị bubble notification
$menu[25] = array(
    sprintf( __( 'Comments %s' ), '<span class="awaiting-mod count-' . $awaiting_mod . '"><span class="pending-count">' . $awaiting_mod_i18n . '</span></span>' ),
    'edit_posts',
    'edit-comments.php',
    '',
    'menu-icon-comments',
    'menu-comments',
    'dashicons-admin-comments'
);
```

---

## 4. Bulk Actions

**Source**: `wp-admin/edit-comments.php` dòng 24-151

```php
if ( $doaction ) {
    check_admin_referer( 'bulk-comments' );

    // Lấy danh sách comment IDs
    if ( 'delete_all' === $doaction && ! empty( $_REQUEST['pagegen_timestamp'] ) ) {
        // Xóa tất cả comments theo status và thời gian
        global $wpdb;
        $comment_status = wp_unslash( $_REQUEST['comment_status'] );
        $delete_time    = wp_unslash( $_REQUEST['pagegen_timestamp'] );
        $comment_ids    = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT comment_ID FROM $wpdb->comments
                WHERE comment_approved = %s AND %s > comment_date_gmt",
                $comment_status,
                $delete_time
            )
        );
        $doaction = 'delete';
    } elseif ( isset( $_REQUEST['delete_comments'] ) ) {
        $comment_ids = $_REQUEST['delete_comments'];
        $doaction    = $_REQUEST['action'];
    }

    // Defer comment counting (tối ưu hiệu suất)
    wp_defer_comment_counting( true );

    foreach ( $comment_ids as $comment_id ) {
        // Kiểm tra quyền cho từng comment
        if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
            continue;
        }

        switch ( $doaction ) {
            case 'approve':
                wp_set_comment_status( $comment_id, 'approve' );
                break;
            case 'unapprove':
                wp_set_comment_status( $comment_id, 'hold' );
                break;
            case 'spam':
                wp_spam_comment( $comment_id );
                break;
            case 'unspam':
                wp_unspam_comment( $comment_id );
                break;
            case 'trash':
                wp_trash_comment( $comment_id );
                break;
            case 'untrash':
                wp_untrash_comment( $comment_id );
                break;
            case 'delete':
                wp_delete_comment( $comment_id );
                break;
        }
    }

    // Cập nhật tất cả comment counts sau khi xong
    wp_defer_comment_counting( false );

    // Redirect với thông báo kết quả
    wp_safe_redirect( $redirect_to );
    exit;
}
```

### wp_defer_comment_counting()

Kỹ thuật tối ưu hiệu suất: thay vì cập nhật `comment_count` trong `wp_posts` sau mỗi thao tác, WordPress gom lại và cập nhật một lần cuối cùng.

```php
// Bắt đầu defer - không cập nhật count ngay
wp_defer_comment_counting( true );

// Thực hiện nhiều thao tác
wp_set_comment_status( $id1, 'approve' );
wp_set_comment_status( $id2, 'approve' );
wp_spam_comment( $id3 );

// Kết thúc defer - cập nhật tất cả counts
wp_defer_comment_counting( false );
```

---

## 5. Single Comment Actions

**Source**: `wp-admin/comment.php`

### Các actions được xử lý

```php
// Source: wp-admin/comment.php dòng 20-38
$action = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

// Mapping actions legacy
if ( 'cdc' === $action ) $action = 'delete';
if ( 'mac' === $action ) $action = 'approve';

if ( isset( $_GET['dt'] ) ) {
    if ( 'spam' === $_GET['dt'] )  $action = 'spam';
    if ( 'trash' === $_GET['dt'] ) $action = 'trash';
}
```

### Switch action chính

```php
// Source: wp-admin/comment.php dòng 54-385
switch ( $action ) {
    case 'editcomment':
        // Hiển thị form sửa comment
        // Kiểm tra: current_user_can( 'edit_comment', $comment_id )
        // Kiểm tra: comment không ở trạng thái trash
        $comment = get_comment_to_edit( $comment_id );
        require ABSPATH . 'wp-admin/edit-form-comment.php';
        break;

    case 'delete':
    case 'approve':
    case 'trash':
    case 'spam':
        // Hiển thị trang xác nhận
        // "Bạn có chắc muốn [action] comment này?"
        break;

    case 'deletecomment':
        wp_delete_comment( $comment );
        break;
    case 'trashcomment':
        wp_trash_comment( $comment );
        break;
    case 'untrashcomment':
        wp_untrash_comment( $comment );
        break;
    case 'spamcomment':
        wp_spam_comment( $comment );
        break;
    case 'unspamcomment':
        wp_unspam_comment( $comment );
        break;
    case 'approvecomment':
        wp_set_comment_status( $comment, 'approve' );
        break;
    case 'unapprovecomment':
        wp_set_comment_status( $comment, 'hold' );
        break;

    case 'editedcomment':
        // Xử lý sau khi submit form edit
        check_admin_referer( 'update-comment_' . $comment_id );
        $updated = edit_comment();
        $location = apply_filters( 'comment_edit_redirect', $location, $comment_id );
        wp_redirect( $location );
        break;
}
```

### Hàm xử lý comment status

```php
// Approve/Unapprove
wp_set_comment_status( $comment_id, 'approve' ); // comment_approved = '1'
wp_set_comment_status( $comment_id, 'hold' );    // comment_approved = '0'

// Spam
wp_spam_comment( $comment_id );
// → Lưu old status vào meta '_wp_trash_meta_status'
// → Set comment_approved = 'spam'
// → Fires: 'spam_comment', 'wp_set_comment_status'

// Trash
wp_trash_comment( $comment_id );
// → Lưu old status vào meta '_wp_trash_meta_status'
// → Lưu thời gian vào meta '_wp_trash_meta_time'
// → Set comment_approved = 'trash'
// → Fires: 'trash_comment', 'wp_set_comment_status'

// Delete vĩnh viễn
wp_delete_comment( $comment_id, $force_delete = false );
// Nếu $force_delete = false → trash (nếu EMPTY_TRASH_DAYS > 0)
// Nếu $force_delete = true → xóa hoàn toàn khỏi DB
// → Fires: 'delete_comment', 'deleted_comment'

// Untrash
wp_untrash_comment( $comment_id );
// → Lấy old status từ meta '_wp_trash_meta_status'
// → Restore về status cũ
// → Xóa trash meta
// → Fires: 'untrash_comment', 'untrashed_comment'
```

---

## 6. Comment Edit Form

**Source**: `wp-admin/edit-form-comment.php`

### Cấu trúc form

```php
// Source: wp-admin/edit-form-comment.php
<form name="post" action="comment.php" method="post" id="post">
<?php wp_nonce_field( 'update-comment_' . $comment->comment_ID ); ?>

<input type="hidden" name="action" value="editedcomment" />
<input type="hidden" name="comment_ID" value="<?php echo esc_attr( $comment->comment_ID ); ?>" />
<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $comment->comment_post_ID ); ?>" />
```

### Các fields trong form

```
+---------------------------------------------+
| Edit Comment                                 |
+---------------------------------------------+
| Author Section:                              |
|   - Name:   [newcomment_author]              |
|   - Email:  [newcomment_author_email]        |
|   - URL:    [newcomment_author_url]          |
+---------------------------------------------+
| Comment Content:                             |
|   [TinyMCE Editor / textarea]                |
|   name="content"                             |
+---------------------------------------------+
| Status Metabox (sidebar):                    |
|   - Comment Status: Approved/Pending/Spam    |
|   - Submitted on: [date] [time]              |
|   [Update] button                            |
+---------------------------------------------+
```

### Hàm edit_comment()

**Source**: `wp-includes/comment.php`

```php
// Gọi từ wp-admin/comment.php case 'editedcomment'
function edit_comment() {
    if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
        wp_die( __( 'Sorry, you are not allowed to edit comments on this post.' ) );
    }

    // Sanitize dữ liệu
    $comment = array(
        'comment_ID'           => $comment_id,
        'comment_post_ID'      => $comment_post_id,
        'comment_content'      => $_POST['content'],
        'comment_author'       => $_POST['newcomment_author'],
        'comment_author_email' => $_POST['newcomment_author_email'],
        'comment_author_url'   => $_POST['newcomment_author_url'],
        'comment_approved'     => $_POST['comment_status'],
        'comment_date'         => $comment_date,
    );

    // Update vào database
    wp_update_comment( $comment );
}
```

---

## 7. Inline Reply (AJAX)

### Frontend JS

Khi click "Reply" trên comment list, WordPress mở inline form ngay dưới comment đó.

```javascript
// Source: wp-admin/js/edit-comments.js
// Khi submit reply form, gọi AJAX:
$.ajax({
    type: 'POST',
    url: ajaxurl,
    data: {
        action:     'replyto-comment',
        _ajax_nonce: $('#_ajax_nonce-replyto-comment').val(),
        comment_ID:  commentId,
        content:     $('#replycontent').val(),
        comment_post_ID: postId,
        comment_parent:  parentId
    }
});
```

### Backend AJAX Handler

**Source**: `wp-admin/includes/ajax-actions.php`

```php
// Hàm: wp_ajax_replyto_comment()
function wp_ajax_replyto_comment( $action ) {
    // Kiểm tra nonce
    check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment' );

    // Lấy post ID
    $comment_post_ID = (int) $_POST['comment_post_ID'];
    $post = get_post( $comment_post_ID );

    // Kiểm tra quyền
    if ( ! current_user_can( 'edit_post', $comment_post_ID ) ) {
        wp_die( -1 );
    }

    // Chuẩn bị comment data
    $comment = array(
        'comment_post_ID' => $comment_post_ID,
        'comment_author'  => wp_get_current_user()->display_name,
        'comment_author_email' => wp_get_current_user()->user_email,
        'comment_author_url'   => wp_get_current_user()->user_url,
        'comment_content'      => $_POST['content'],
        'comment_type'         => '',
        'comment_parent'       => (int) $_POST['comment_parent'],
        'user_id'              => get_current_user_id(),
    );

    // Tự động approve vì đây là admin reply
    $comment['comment_approved'] = 1;

    // Insert comment
    $comment_id = wp_new_comment( $comment );

    // Trả về HTML của comment row mới
    $comment = get_comment( $comment_id );
    // ... render table row ...
    $wp_list_table->single_row( $comment );

    wp_die(); // Kết thúc AJAX
}
```

### Quick Edit

Quick Edit cho phép sửa nhanh nội dung comment mà không cần mở trang edit:

```javascript
// Action: wp_ajax_edit-comment
// Data gửi lên tương tự edit_comment() nhưng qua AJAX
```

---

## 8. Comment Moderation Flow

### Flow comment mới từ frontend

```
Visitor gửi comment qua form
    │
    ▼
POST /wp-comments-post.php
    │
    ├──▶ wp_handle_comment_submission()
    │       │
    │       ├── Validate: Name, Email (nếu required)
    │       ├── Check: Comment flood (wp_throttle_comment_flood)
    │       ├── Check: Duplicate comment
    │       ├── Check: Blacklist/Disallowed keys
    │       │
    │       └── wp_new_comment( $commentdata )
    │               │
    │               ├── wp_filter_comment() - sanitize
    │               ├── wp_allow_comment() - kiểm tra moderation
    │               │   │
    │               │   ├── Nếu user đã login + đã có approved comment → approve
    │               │   ├── Nếu chứa moderation keys → hold
    │               │   ├── Nếu chứa disallowed keys → spam/trash
    │               │   ├── Nếu quá nhiều links → hold
    │               │   └── Nếu comment_previously_approved = false → hold
    │               │
    │               ├── wp_insert_comment() - INSERT vào DB
    │               │
    │               └── wp_set_comment_status() nếu cần
    │
    ▼
comment_approved = '0' (pending) hoặc '1' (approved)
    │
    ▼
Notifications:
    ├── Email admin (nếu pending) - wp_notify_moderator()
    └── Email post author (nếu approved) - wp_notify_postauthor()
```

### Kiểm tra moderation

```php
// Source: wp-includes/comment.php - wp_allow_comment()
function wp_allow_comment( $commentdata, $wp_error = false ) {
    // 1. Kiểm tra disallowed keys (blacklist)
    if ( wp_check_comment_disallowed_list(
        $commentdata['comment_author'],
        $commentdata['comment_author_email'],
        $commentdata['comment_author_url'],
        $commentdata['comment_content'],
        $commentdata['comment_author_IP'],
        $commentdata['comment_agent']
    ) ) {
        // Chứa từ cấm → spam hoặc trash
        return 'spam'; // hoặc EMPTY_TRASH_DAYS ? 'trash' : 'spam'
    }

    // 2. Kiểm tra user đã có approved comment chưa
    // Option: comment_previously_approved
    if ( get_option( 'comment_previously_approved' ) ) {
        // Kiểm tra email đã có comment approved trước đó
    }

    // 3. Kiểm tra moderation keys
    if ( wp_check_comment_moderation_list(
        $commentdata['comment_author'],
        $commentdata['comment_author_email'],
        $commentdata['comment_author_url'],
        $commentdata['comment_content'],
        $commentdata['comment_author_IP'],
        $commentdata['comment_agent']
    ) ) {
        return 0; // Hold for moderation
    }

    // 4. Kiểm tra số lượng links
    $max_links = get_option( 'comment_max_links' );
    // Đếm URLs trong content

    return 1; // Approved
}
```

---

## 9. Discussion Settings ảnh hưởng

**URL**: `/wp-admin/options-discussion.php`

### Các options liên quan trong wp_options

| Option Name | Mô tả | Ảnh hưởng |
|-------------|--------|-----------|
| `default_comment_status` | Cho phép comment mặc định | `'open'` hoặc `'closed'` |
| `require_name_email` | Yêu cầu name/email | boolean |
| `comment_registration` | Yêu cầu đăng nhập | boolean |
| `close_comments_for_old_posts` | Đóng comment post cũ | boolean |
| `close_comments_days_old` | Số ngày trước khi đóng | integer |
| `thread_comments` | Cho phép reply lồng nhau | boolean |
| `thread_comments_depth` | Độ sâu tối đa | 1-10 |
| `page_comments` | Phân trang comments | boolean |
| `comments_per_page` | Số comments mỗi trang | integer |
| `default_comments_page` | Trang mặc định (newest/oldest) | string |
| `comment_order` | Thứ tự hiển thị | `'asc'` hoặc `'desc'` |
| `comment_moderation` | Luôn moderate comments | boolean |
| `comment_previously_approved` | Yêu cầu có approved trước | boolean |
| `comment_max_links` | Số links tối đa | integer |
| `moderation_keys` | Từ khóa cần moderate | text (mỗi dòng 1 từ) |
| `disallowed_keys` | Từ khóa cấm (spam) | text (mỗi dòng 1 từ) |
| `show_avatars` | Hiển thị avatar | boolean |
| `avatar_rating` | Rating avatar tối đa | `'G'`, `'PG'`, `'R'`, `'X'` |
| `avatar_default` | Avatar mặc định | string |

### Moderation Keys

```php
// Source: wp-includes/comment.php - wp_check_comment_moderation_list()
// Kiểm tra từng dòng trong 'moderation_keys' option
// So sánh với: author name, email, URL, content, IP, user agent
// Nếu match → comment bị hold

// Ví dụ moderation_keys:
// viagra
// casino
// 192.168.1.100
// spammer@evil.com
```

### Disallowed Keys (Blacklist)

```php
// Source: wp-includes/comment.php - wp_check_comment_disallowed_list()
// Tương tự moderation_keys nhưng comment bị đánh dấu spam/trash
```

---

## 10. DB: Comments Lưu Gì?

### Bảng wp_comments

```sql
CREATE TABLE wp_comments (
    comment_ID           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    comment_post_ID      bigint(20) unsigned NOT NULL DEFAULT 0,  -- Post mà comment thuộc về
    comment_author       tinytext NOT NULL,                        -- Tên người comment
    comment_author_email varchar(100) NOT NULL DEFAULT '',         -- Email
    comment_author_url   varchar(200) NOT NULL DEFAULT '',         -- Website
    comment_author_IP    varchar(100) NOT NULL DEFAULT '',         -- IP address
    comment_date         datetime NOT NULL DEFAULT '0000-00-00 00:00:00',  -- Local time
    comment_date_gmt     datetime NOT NULL DEFAULT '0000-00-00 00:00:00',  -- GMT time
    comment_content      text NOT NULL,                            -- Nội dung comment
    comment_karma        int(11) NOT NULL DEFAULT 0,               -- Karma (ít dùng)
    comment_approved     varchar(20) NOT NULL DEFAULT '1',         -- Status: '1', '0', 'spam', 'trash'
    comment_agent        varchar(255) NOT NULL DEFAULT '',         -- User agent browser
    comment_type         varchar(20) NOT NULL DEFAULT 'comment',   -- Type: comment, pingback, trackback
    comment_parent       bigint(20) unsigned NOT NULL DEFAULT 0,   -- Parent comment (threaded)
    user_id              bigint(20) unsigned NOT NULL DEFAULT 0,   -- WP user ID (0 nếu anonymous)
    PRIMARY KEY (comment_ID),
    KEY comment_post_ID (comment_post_ID),
    KEY comment_approved_date_gmt (comment_approved, comment_date_gmt),
    KEY comment_date_gmt (comment_date_gmt),
    KEY comment_parent (comment_parent),
    KEY comment_author_email (comment_author_email(10))
);
```

### Bảng wp_commentmeta

```sql
CREATE TABLE wp_commentmeta (
    meta_id    bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    comment_id bigint(20) unsigned NOT NULL DEFAULT 0,
    meta_key   varchar(255) DEFAULT NULL,
    meta_value longtext,
    PRIMARY KEY (meta_id),
    KEY comment_id (comment_id),
    KEY meta_key (meta_key(191))
);

-- Meta keys phổ biến:
-- '_wp_trash_meta_status'  → Status trước khi trash
-- '_wp_trash_meta_time'    → Thời gian trash
-- 'akismet_result'         → Kết quả Akismet check ('true'/'false')
-- 'akismet_history'        → Lịch sử Akismet checks
-- 'akismet_user_result'    → User override Akismet
```

### Cache Counter trong wp_posts

```sql
-- Cột comment_count trong wp_posts
-- Đếm số approved comments cho post
UPDATE wp_posts SET comment_count = (
    SELECT COUNT(*) FROM wp_comments
    WHERE comment_post_ID = wp_posts.ID
    AND comment_approved = '1'
) WHERE ID = {post_id};
```

### Options liên quan

```sql
-- wp_options table
-- moderation_keys:  Từ khóa cần moderate (text, mỗi dòng 1 từ)
-- disallowed_keys:  Từ khóa cấm hoàn toàn
-- comment_max_links: Số links tối đa trước khi hold
```

### Ví dụ query thực tế

```php
// Lấy pending comments
$pending = get_comments( array(
    'status' => 'hold',         // pending
    'number' => 20,
    'offset' => 0,
    'orderby' => 'comment_date_gmt',
    'order'  => 'DESC',
) );

// Lấy comments cho 1 post
$comments = get_comments( array(
    'post_id' => 123,
    'status'  => 'approve',
    'hierarchical' => 'threaded', // Lấy theo cây phân cấp
) );

// Tìm comments theo email
$user_comments = get_comments( array(
    'author_email' => 'user@example.com',
    'status'       => 'all',
) );
```

> **So sánh Laravel**: Tương đương bảng `comments` với quan hệ Eloquent:
> ```php
> // Laravel Model
> class Comment extends Model {
>     public function post() { return $this->belongsTo(Post::class); }
>     public function parent() { return $this->belongsTo(Comment::class, 'parent_id'); }
>     public function replies() { return $this->hasMany(Comment::class, 'parent_id'); }
> }
> ```

---

## 11. Hooks Comments Admin - Danh Sách Đầy Đủ

### Action Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `wp_insert_comment` | Sau insert comment mới | `$comment_id`, `$comment` (object) |
| `edit_comment` | Sau edit comment | `$comment_id`, `$data` |
| `delete_comment` | Trước xóa vĩnh viễn | `$comment_id`, `$comment` |
| `deleted_comment` | Sau xóa vĩnh viễn | `$comment_id`, `$comment` |
| `trash_comment` | Trước trash | `$comment_id`, `$comment` |
| `trashed_comment` | Sau trash | `$comment_id`, `$comment` |
| `untrash_comment` | Trước untrash | `$comment_id`, `$comment` |
| `untrashed_comment` | Sau untrash | `$comment_id`, `$comment` |
| `spam_comment` | Trước đánh dấu spam | `$comment_id`, `$comment` |
| `spammed_comment` | Sau đánh dấu spam | `$comment_id`, `$comment` |
| `unspam_comment` | Trước bỏ spam | `$comment_id`, `$comment` |
| `unspammed_comment` | Sau bỏ spam | `$comment_id`, `$comment` |
| `wp_set_comment_status` | Sau đổi status | `$comment_id`, `$status` |
| `transition_comment_status` | Khi status thay đổi | `$new_status`, `$old_status`, `$comment` |
| `comment_approved_{type}` | Comment được approve | `$comment_id`, `$comment` |
| `comment_unapproved_{type}` | Comment bị unapprove | `$comment_id`, `$comment` |

### Filter Hooks

| Hook | Chức năng | Tham số |
|------|-----------|---------|
| `comment_row_actions` | Actions cho mỗi comment row | `$actions`, `$comment` |
| `manage_edit-comments_columns` | Các cột trong list | `$columns` |
| `pre_get_comments` | Modify WP_Comment_Query | `$query` |
| `comments_clauses` | Modify SQL clauses | `$clauses`, `$query` |
| `comment_text` | Filter nội dung comment | `$comment_text`, `$comment`, `$args` |
| `get_comment_author` | Filter tên tác giả | `$author`, `$comment_id`, `$comment` |
| `preprocess_comment` | Filter data trước insert | `$commentdata` |
| `pre_comment_approved` | Override approval status | `$approved`, `$commentdata` |
| `comment_edit_redirect` | Redirect sau edit | `$location`, `$comment_id` |
| `comment_flood_filter` | Kiểm tra flood | `$flood_die`, `$time_lastcomment`, `$time_newcomment` |

### Ví dụ sử dụng hooks

```php
// 1. Thêm action link "Ban User" vào comment row
add_filter( 'comment_row_actions', function( $actions, $comment ) {
    if ( current_user_can( 'moderate_comments' ) ) {
        $ban_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=ban_commenter&email=' . urlencode( $comment->comment_author_email ) ),
            'ban_commenter'
        );
        $actions['ban'] = '<a href="' . esc_url( $ban_url ) . '" style="color:red;">' . __( 'Ban User' ) . '</a>';
    }
    return $actions;
}, 10, 2 );

// 2. Thêm cột "IP Address" vào comments list
add_filter( 'manage_edit-comments_columns', function( $columns ) {
    $columns['comment_ip'] = __( 'IP Address' );
    return $columns;
});

add_action( 'manage_comments_custom_column', function( $column, $comment_id ) {
    if ( 'comment_ip' === $column ) {
        $comment = get_comment( $comment_id );
        echo '<a href="https://ipinfo.io/' . esc_attr( $comment->comment_author_IP ) . '" target="_blank">'
            . esc_html( $comment->comment_author_IP ) . '</a>';
    }
}, 10, 2 );

// 3. Auto-approve comments từ registered users
add_filter( 'pre_comment_approved', function( $approved, $commentdata ) {
    if ( $commentdata['user_id'] > 0 ) {
        return 1; // Tự động approve
    }
    return $approved;
}, 10, 2 );

// 4. Gửi notification Slack khi có comment mới
add_action( 'wp_insert_comment', function( $comment_id, $comment ) {
    if ( '0' === $comment->comment_approved ) {
        // Comment pending - gửi notification
        $post = get_post( $comment->comment_post_ID );
        $message = sprintf(
            "New pending comment on \"%s\" by %s:\n%s\n\nApprove: %s",
            $post->post_title,
            $comment->comment_author,
            wp_trim_words( $comment->comment_content, 30 ),
            admin_url( 'comment.php?action=approve&c=' . $comment_id )
        );
        // wp_remote_post( $slack_webhook_url, ... );
    }
}, 10, 2 );

// 5. Log mọi thay đổi comment status
add_action( 'transition_comment_status', function( $new_status, $old_status, $comment ) {
    if ( $new_status === $old_status ) return;

    error_log( sprintf(
        'Comment #%d status changed: %s → %s (by user %d)',
        $comment->comment_ID,
        $old_status,
        $new_status,
        get_current_user_id()
    ) );
}, 10, 3 );
```

---

## 12. Anti-Spam và Moderation

### Akismet Plugin (Built-in)

Akismet là plugin chống spam mặc định đi kèm WordPress.

```
wp-content/plugins/akismet/
├── akismet.php                 # Main plugin file
├── class.akismet.php           # Core class
├── class.akismet-admin.php     # Admin interface
└── class.akismet-rest-api.php  # REST API
```

Akismet hoạt động bằng cách:
1. Gửi comment data tới API server Akismet
2. Server phân tích và trả về "spam" hoặc "ham"
3. Lưu kết quả vào `wp_commentmeta` (`akismet_result`)

```php
// Meta data Akismet lưu cho mỗi comment:
'akismet_result'     => 'true'   // hoặc 'false' (spam/ham)
'akismet_history'    => array(
    array(
        'time'    => '1705312200',
        'message' => 'Akismet caught this comment as spam',
        'event'   => 'check-spam',
    ),
)
```

### Manual Moderation Queue

```php
// Kiểm tra comment có cần moderate không
function wp_check_comment_moderation_list( $author, $email, $url, $comment, $user_ip, $user_agent ) {
    $mod_keys = trim( get_option( 'moderation_keys' ) );
    if ( empty( $mod_keys ) ) return false;

    $words = explode( "\n", $mod_keys );
    foreach ( $words as $word ) {
        $word = trim( $word );
        if ( empty( $word ) ) continue;

        // Kiểm tra trong tất cả fields
        if (
            preg_match( '#' . preg_quote( $word, '#' ) . '#i', $author ) ||
            preg_match( '#' . preg_quote( $word, '#' ) . '#i', $email ) ||
            preg_match( '#' . preg_quote( $word, '#' ) . '#i', $url ) ||
            preg_match( '#' . preg_quote( $word, '#' ) . '#i', $comment ) ||
            preg_match( '#' . preg_quote( $word, '#' ) . '#i', $user_ip ) ||
            preg_match( '#' . preg_quote( $word, '#' ) . '#i', $user_agent )
        ) {
            return true; // Cần moderate
        }
    }
    return false;
}
```

### Honeypot Technique

Một kỹ thuật phổ biến trong plugin chống spam:

```php
// Thêm hidden field vào comment form
add_action( 'comment_form_after_fields', function() {
    echo '<p style="display:none !important;">';
    echo '<label for="website_url">Website</label>';
    echo '<input type="text" name="website_url" id="website_url" value="" />';
    echo '</p>';
});

// Kiểm tra - bot sẽ fill hidden field
add_filter( 'preprocess_comment', function( $commentdata ) {
    if ( ! empty( $_POST['website_url'] ) ) {
        wp_die( 'Spam detected!' );
    }
    return $commentdata;
});
```

---

## 13. Comment Types

WordPress hỗ trợ nhiều loại comments:

| Type | Mô tả | Giá trị `comment_type` |
|------|--------|------------------------|
| Comment | Bình luận thường | `'comment'` hoặc `''` |
| Pingback | Auto notification khi site khác link tới | `'pingback'` |
| Trackback | Tương tự pingback (cũ hơn) | `'trackback'` |

### Custom Comment Types (từ WP 5.5)

```php
// Đăng ký custom comment type
add_action( 'init', function() {
    // WP 5.5+ không cần đăng ký chính thức
    // Chỉ cần sử dụng comment_type khi insert
});

// Insert comment với custom type
wp_insert_comment( array(
    'comment_post_ID' => $post_id,
    'comment_content' => 'Review nội dung',
    'comment_type'    => 'review', // Custom type
    'comment_meta'    => array(
        'rating' => 5,
    ),
) );

// Query chỉ reviews
$reviews = get_comments( array(
    'post_id' => $post_id,
    'type'    => 'review',
) );
```

---

## 14. WP_Comment_Query - Truy Vấn Comments

### Cách sử dụng

```php
// Cách 1: Dùng get_comments()
$comments = get_comments( array(
    'post_id'    => 123,
    'status'     => 'approve',
    'number'     => 10,
    'offset'     => 0,
    'orderby'    => 'comment_date_gmt',
    'order'      => 'DESC',
    'parent'     => 0,           // Chỉ root comments
    'type'       => 'comment',   // Bỏ pingback/trackback
    'meta_query' => array(
        array(
            'key'     => 'rating',
            'value'   => 4,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ),
    ),
) );

// Cách 2: Dùng WP_Comment_Query trực tiếp
$query = new WP_Comment_Query( array(
    'post_id'        => 123,
    'status'         => 'approve',
    'hierarchical'   => 'threaded', // Tự build cây comment
    'count'          => false,       // true = chỉ đếm
) );
$comments = $query->comments;
```

### Các tham số quan trọng

```php
$args = array(
    // Lọc
    'post_id'          => 0,           // Post cụ thể
    'post__in'         => array(),     // Nhiều posts
    'author_email'     => '',          // Email tác giả
    'author__in'       => array(),     // User IDs
    'status'           => 'all',       // approve, hold, spam, trash, all
    'type'             => '',          // comment, pingback, trackback, custom
    'type__in'         => array(),     // Nhiều types
    'type__not_in'     => array(),     // Loại trừ types
    'search'           => '',          // Tìm kiếm
    'parent'           => '',          // Parent comment ID
    'parent__in'       => array(),
    'parent__not_in'   => array(),

    // Phân trang
    'number'           => '',          // Số comments lấy
    'offset'           => 0,           // Bỏ qua bao nhiêu
    'paged'            => 1,           // Trang

    // Sắp xếp
    'orderby'          => 'comment_date_gmt',
    'order'            => 'DESC',

    // Meta query
    'meta_key'         => '',
    'meta_value'       => '',
    'meta_query'       => array(),

    // Date query
    'date_query'       => array(
        array(
            'after'  => '2024-01-01',
            'before' => '2024-12-31',
        ),
    ),

    // Threading
    'hierarchical'     => false,       // 'flat', 'threaded', false
);
```

---

## 15. Vi Du Thuc Te: Plugin Comment

### Plugin Rating Comments

```php
<?php
/**
 * Plugin Name: Rating Comments
 * Description: Thêm rating vào comments
 * Version: 1.0.0
 */

// Thêm rating field vào comment form
add_action( 'comment_form_logged_in_after', 'rc_rating_field' );
add_action( 'comment_form_after_fields', 'rc_rating_field' );

function rc_rating_field() {
    echo '<p class="comment-form-rating">';
    echo '<label for="rating">' . __( 'Danh gia' ) . '</label>';
    echo '<select name="rating" id="rating">';
    echo '<option value="">-- Chon --</option>';
    for ( $i = 5; $i >= 1; $i-- ) {
        echo '<option value="' . $i . '">' . $i . ' sao</option>';
    }
    echo '</select>';
    echo '</p>';
}

// Lưu rating khi submit comment
add_action( 'comment_post', function( $comment_id, $comment_approved, $commentdata ) {
    if ( isset( $_POST['rating'] ) && '' !== $_POST['rating'] ) {
        $rating = absint( $_POST['rating'] );
        if ( $rating >= 1 && $rating <= 5 ) {
            add_comment_meta( $comment_id, 'rating', $rating );
        }
    }
}, 10, 3 );

// Hiển thị rating trong comment text
add_filter( 'comment_text', function( $text, $comment ) {
    $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
    if ( $rating ) {
        $stars = str_repeat( '&#9733;', $rating ) . str_repeat( '&#9734;', 5 - $rating );
        $text  = '<div class="comment-rating">' . $stars . ' (' . $rating . '/5)</div>' . $text;
    }
    return $text;
}, 10, 2 );

// Thêm cột Rating trong admin
add_filter( 'manage_edit-comments_columns', function( $columns ) {
    $columns['rating'] = __( 'Rating' );
    return $columns;
});

add_action( 'manage_comments_custom_column', function( $column, $comment_id ) {
    if ( 'rating' === $column ) {
        $rating = get_comment_meta( $comment_id, 'rating', true );
        if ( $rating ) {
            echo str_repeat( '&#9733;', $rating );
        } else {
            echo '&mdash;';
        }
    }
}, 10, 2 );

// Tính trung bình rating cho post
function rc_get_average_rating( $post_id ) {
    global $wpdb;

    $result = $wpdb->get_row( $wpdb->prepare(
        "SELECT AVG(cm.meta_value) as avg_rating, COUNT(*) as total
         FROM {$wpdb->commentmeta} cm
         INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
         WHERE c.comment_post_ID = %d
         AND c.comment_approved = '1'
         AND cm.meta_key = 'rating'",
        $post_id
    ) );

    return array(
        'average' => round( (float) $result->avg_rating, 1 ),
        'total'   => (int) $result->total,
    );
}
```

---

## 16. So Sánh Với Laravel

| Tính năng | WordPress | Laravel |
|-----------|-----------|---------|
| Comments table | `wp_comments` (built-in) | Tự tạo migration |
| Comment meta | `wp_commentmeta` | Tự tạo bảng hoặc JSON column |
| Threaded comments | `comment_parent` column | Tự implement nested set/adjacency list |
| Comment status | `comment_approved` column | Tự tạo status column |
| Moderation | Built-in queue + Akismet | Tự build hoặc package |
| Spam detection | Akismet API | Tự integrate Akismet hoặc reCAPTCHA |
| Email notifications | `wp_notify_moderator/postauthor` | Laravel Notification |
| AJAX reply | Built-in | Tự implement API endpoint |
| Comment form | `comment_form()` helper | Blade template |
| Count cache | `wp_posts.comment_count` | Tự implement counter cache |

### Tương đương trong Laravel

```php
// Migration
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained();
    $table->foreignId('user_id')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('comments');
    $table->string('author_name');
    $table->string('author_email');
    $table->string('author_url')->nullable();
    $table->ipAddress('author_ip');
    $table->text('content');
    $table->enum('status', ['approved', 'pending', 'spam', 'trash'])->default('pending');
    $table->string('type')->default('comment');
    $table->string('user_agent')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// Model
class Comment extends Model {
    use SoftDeletes;

    public function post() { return $this->belongsTo(Post::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function parent() { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies() { return $this->hasMany(Comment::class, 'parent_id'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
}

// Controller
class CommentController extends Controller {
    public function store(Request $request, Post $post) {
        $validated = $request->validate([
            'content'   => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $post->comments()->create([
            'user_id'      => auth()->id(),
            'author_name'  => auth()->user()->name,
            'author_email' => auth()->user()->email,
            'author_ip'    => $request->ip(),
            'content'      => $validated['content'],
            'parent_id'    => $validated['parent_id'] ?? null,
            'status'       => $this->determineStatus($request),
            'user_agent'   => $request->userAgent(),
        ]);

        // Notification
        if ($comment->status === 'pending') {
            Notification::send($post->author, new CommentPendingNotification($comment));
        }

        return back()->with('success', 'Comment submitted!');
    }
}
```

---

## 17. Tong Ket

### Các điểm quan trọng cần nhớ

1. **Comments = WordPress core feature**: Hệ thống comment là built-in, không cần plugin.

2. **Flow xử lý**: `wp-comments-post.php` -> `wp_new_comment()` -> `wp_allow_comment()` -> `wp_insert_comment()`.

3. **Status quan trọng**: `comment_approved` column chứa `'1'`, `'0'`, `'spam'`, `'trash'`.

4. **Admin flow**: `edit-comments.php` (list) -> `comment.php` (single actions/edit).

5. **Inline reply qua AJAX**: Action `replyto-comment` trong `ajax-actions.php`.

6. **Discussion Settings**: Nhiều options trong `wp_options` ảnh hưởng trực tiếp đến việc hiển thị và moderate comments.

7. **Hooks quan trọng nhất**:
   - `wp_insert_comment` - Sau insert comment mới
   - `transition_comment_status` - Khi status thay đổi
   - `comment_row_actions` - Custom actions trong admin list
   - `preprocess_comment` - Filter data trước insert
   - `pre_comment_approved` - Override approval logic

8. **Akismet**: Plugin spam filter mặc định, check mỗi comment qua API.

9. **Comment count cache**: `wp_posts.comment_count` được cache, cập nhật qua `wp_update_comment_count()`.

10. **Threading**: `comment_parent` column cho phép reply lồng nhau, depth set trong Discussion Settings.

---

> **Tiep theo**: [06 - Giao Dien (Appearance)](./06-giao-dien.md)
