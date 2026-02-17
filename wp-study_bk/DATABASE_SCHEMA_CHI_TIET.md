# WordPress Database Schema Chi Tiết

> Phân tích chi tiết từng bảng, từng cột trong database WordPress dựa trên source code `wp-admin/includes/schema.php`.
> File này bổ sung cho [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) với thông tin cấu trúc cột chi tiết.

---

## Mục Lục

1. [Tổng Quan Database WordPress](#1-tổng-quan-database-wordpress)
2. [Bảng wp_posts - Nội Dung](#2-bảng-wp_posts---nội-dung)
3. [Bảng wp_postmeta - Meta Data Bài Viết](#3-bảng-wp_postmeta---meta-data-bài-viết)
4. [Bảng wp_comments - Bình Luận](#4-bảng-wp_comments---bình-luận)
5. [Bảng wp_commentmeta - Meta Data Bình Luận](#5-bảng-wp_commentmeta---meta-data-bình-luận)
6. [Bảng wp_terms - Thuật Ngữ](#6-bảng-wp_terms---thuật-ngữ)
7. [Bảng wp_termmeta - Meta Data Thuật Ngữ](#7-bảng-wp_termmeta---meta-data-thuật-ngữ)
8. [Bảng wp_term_taxonomy - Phân Loại Thuật Ngữ](#8-bảng-wp_term_taxonomy---phân-loại-thuật-ngữ)
9. [Bảng wp_term_relationships - Quan Hệ Nội Dung & Thuật Ngữ](#9-bảng-wp_term_relationships---quan-hệ-nội-dung--thuật-ngữ)
10. [Bảng wp_options - Cấu Hình](#10-bảng-wp_options---cấu-hình)
11. [Bảng wp_users - Người Dùng](#11-bảng-wp_users---người-dùng)
12. [Bảng wp_usermeta - Meta Data Người Dùng](#12-bảng-wp_usermeta---meta-data-người-dùng)
13. [Bảng wp_links - Liên Kết (Legacy)](#13-bảng-wp_links---liên-kết-legacy)
14. [Bảng Multisite](#14-bảng-multisite)
15. [Default Options (populate_options)](#15-default-options-populate_options)
16. [Roles & Capabilities (populate_roles)](#16-roles--capabilities-populate_roles)
17. [ERD - Sơ Đồ Quan Hệ](#17-erd---sơ-đồ-quan-hệ)
18. [So Sánh Với Laravel](#18-so-sánh-với-laravel)
19. [Best Practices & Lưu Ý](#19-best-practices--lưu-ý)

---

## 1. Tổng Quan Database WordPress

### Source Code Tham Khảo

File: `wp-admin/includes/schema.php`

```
wp_get_db_schema( $scope = 'all', $blog_id = null )
```

Hàm này trả về toàn bộ SQL `CREATE TABLE` cho WordPress. Tham số `$scope`:

| Scope | Mô tả |
|-------|--------|
| `'all'` | Tất cả bảng (mặc định) |
| `'blog'` | Chỉ bảng thuộc blog/site cụ thể |
| `'global'` | Bảng dùng chung (users, usermeta + multisite tables nếu có) |
| `'ms_global'` | Chỉ bảng multisite |

### Charset & Collation

```php
$charset_collate = $wpdb->get_charset_collate();
// Thường là: DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
```

### Giới Hạn Index

```php
$max_index_length = 191;
// utf8mb4 dùng 4 bytes/ký tự → 767 bytes / 4 = 191 ký tự tối đa cho index
```

> **So sánh Laravel:** Laravel migration cũng tự động set `$table->string('email', 191)->unique()` khi dùng `utf8mb4`.

### Tổng Quan Các Bảng

**Single Site (12 bảng):**

| Nhóm | Bảng | Chức năng |
|------|------|-----------|
| Nội dung | `wp_posts` | Bài viết, trang, CPT, attachment, revision |
| Nội dung | `wp_postmeta` | Metadata mở rộng cho posts |
| Bình luận | `wp_comments` | Bình luận, pingback, trackback |
| Bình luận | `wp_commentmeta` | Metadata cho comments |
| Phân loại | `wp_terms` | Category, tag, custom taxonomy terms |
| Phân loại | `wp_termmeta` | Metadata cho terms |
| Phân loại | `wp_term_taxonomy` | Gắn term với taxonomy cụ thể |
| Phân loại | `wp_term_relationships` | Liên kết post ↔ term_taxonomy |
| Cấu hình | `wp_options` | Toàn bộ settings, transients, widget config |
| Người dùng | `wp_users` | Tài khoản người dùng |
| Người dùng | `wp_usermeta` | Metadata người dùng (role, preferences...) |
| Legacy | `wp_links` | Blogroll links (không dùng nữa từ WP 3.5) |

**Multisite bổ sung thêm 6 bảng:** `wp_blogs`, `wp_blogmeta`, `wp_site`, `wp_sitemeta`, `wp_registration_log`, `wp_signups`

---

## 2. Bảng wp_posts - Nội Dung

> Bảng quan trọng nhất của WordPress. Lưu trữ TẤT CẢ nội dung: posts, pages, attachments, revisions, menu items, và mọi Custom Post Type.

### SQL Gốc (từ schema.php dòng 159-188)

```sql
CREATE TABLE wp_posts (
    ID bigint(20) unsigned NOT NULL auto_increment,
    post_author bigint(20) unsigned NOT NULL default '0',
    post_date datetime NOT NULL default '0000-00-00 00:00:00',
    post_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
    post_content longtext NOT NULL,
    post_title text NOT NULL,
    post_excerpt text NOT NULL,
    post_status varchar(20) NOT NULL default 'publish',
    comment_status varchar(20) NOT NULL default 'open',
    ping_status varchar(20) NOT NULL default 'open',
    post_password varchar(255) NOT NULL default '',
    post_name varchar(200) NOT NULL default '',
    to_ping text NOT NULL,
    pinged text NOT NULL,
    post_modified datetime NOT NULL default '0000-00-00 00:00:00',
    post_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
    post_content_filtered longtext NOT NULL,
    post_parent bigint(20) unsigned NOT NULL default '0',
    guid varchar(255) NOT NULL default '',
    menu_order int(11) NOT NULL default '0',
    post_type varchar(20) NOT NULL default 'post',
    post_mime_type varchar(100) NOT NULL default '',
    comment_count bigint(20) NOT NULL default '0',
    PRIMARY KEY  (ID),
    KEY post_name (post_name(191)),
    KEY type_status_date (post_type,post_status,post_date,ID),
    KEY post_parent (post_parent),
    KEY post_author (post_author)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `ID` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** ID duy nhất cho mỗi record. Tự tăng, không bao giờ reset về 0 ngay cả khi xóa post. |
| `post_author` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_users.ID.** ID tác giả bài viết. `0` = không có tác giả (ví dụ auto-draft). |
| `post_date` | `datetime` default `'0000-00-00 00:00:00'` | Ngày đăng theo **timezone local** (timezone trong Settings). Dùng cho hiển thị. |
| `post_date_gmt` | `datetime` default `'0000-00-00 00:00:00'` | Ngày đăng theo **GMT/UTC**. WordPress dùng cột này để so sánh thời gian chính xác. |
| `post_content` | `longtext` | **Nội dung chính** của bài viết. Chứa HTML, Gutenberg blocks (`<!-- wp:paragraph -->`), shortcodes. Tối đa ~4GB. |
| `post_title` | `text` | Tiêu đề bài viết. Kiểu `text` (tối đa 65,535 bytes). |
| `post_excerpt` | `text` | Tóm tắt bài viết. Nếu trống, WP tự tạo excerpt từ `post_content` (55 từ mặc định). |
| `post_status` | `varchar(20)` default `'publish'` | **Trạng thái bài viết.** Các giá trị: `publish`, `draft`, `pending`, `private`, `trash`, `auto-draft`, `inherit` (revision/attachment), `future` (scheduled). Có thể đăng ký thêm bằng `register_post_status()`. |
| `comment_status` | `varchar(20)` default `'open'` | Cho phép bình luận? `open` = cho phép, `closed` = không cho phép. |
| `ping_status` | `varchar(20)` default `'open'` | Cho phép pingback/trackback? `open` hoặc `closed`. |
| `post_password` | `varchar(255)` default `''` | Mật khẩu bảo vệ bài viết. Trống = không có mật khẩu. Lưu **plain text** (không hash). |
| `post_name` | `varchar(200)` default `''` | **Slug** của bài viết dùng trong URL. Ví dụ: `hello-world`. Tự động tạo từ title, unique trong cùng post_type. |
| `to_ping` | `text` | Danh sách URL cần gửi pingback (chưa gửi). Mỗi URL trên một dòng. |
| `pinged` | `text` | Danh sách URL đã gửi pingback thành công. |
| `post_modified` | `datetime` default `'0000-00-00 00:00:00'` | Ngày chỉnh sửa lần cuối (timezone local). |
| `post_modified_gmt` | `datetime` default `'0000-00-00 00:00:00'` | Ngày chỉnh sửa lần cuối (GMT/UTC). |
| `post_content_filtered` | `longtext` | Nội dung đã lọc/cache. Hiếm khi dùng, thường trống. Một số plugin dùng để lưu nội dung đã xử lý. |
| `post_parent` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_posts.ID.** ID của bài viết cha. `0` = không có cha. Dùng cho: Page hierarchy, Attachment → Post, Revision → Original post. |
| `guid` | `varchar(255)` default `''` | **Global Unique Identifier.** URL định danh bài viết. **KHÔNG PHẢI permalink!** Không thay đổi khi đổi domain. Dùng trong RSS feed. Ví dụ: `http://example.com/?p=123`. |
| `menu_order` | `int(11)` default `'0'` | Thứ tự sắp xếp. Dùng cho Pages, Nav Menu Items, Attachments. `0` = mặc định. |
| `post_type` | `varchar(20)` default `'post'` | **Loại nội dung.** Built-in: `post`, `page`, `attachment`, `revision`, `nav_menu_item`, `wp_block`, `wp_template`, `wp_template_part`, `wp_navigation`, `wp_global_styles`. Custom: đăng ký bằng `register_post_type()`. |
| `post_mime_type` | `varchar(100)` default `''` | MIME type, **chỉ dùng cho attachment.** Ví dụ: `image/jpeg`, `image/png`, `application/pdf`, `video/mp4`. |
| `comment_count` | `bigint(20)` default `'0'` | **Cache counter** - tổng số bình luận được duyệt. Tự cập nhật khi thêm/xóa comment. Dùng để query nhanh mà không cần JOIN. |

### Indexes

| Index | Cột | Mục đích |
|-------|-----|----------|
| `PRIMARY KEY` | `ID` | Tra cứu post theo ID |
| `post_name` | `post_name(191)` | Tìm post theo slug (URL) |
| `type_status_date` | `post_type, post_status, post_date, ID` | **Index quan trọng nhất!** Query phổ biến: lấy posts theo type + status + sắp xếp theo date. |
| `post_parent` | `post_parent` | Tìm pages con, revisions, attachments |
| `post_author` | `post_author` | Lấy bài viết theo tác giả |

### Ví Dụ Dữ Liệu

```
+----+-------------+---------------------+--------------+-------------------+
| ID | post_author | post_date           | post_status  | post_type         |
+----+-------------+---------------------+--------------+-------------------+
|  1 |           1 | 2024-01-15 10:30:00 | publish      | post              |
|  2 |           1 | 2024-01-15 10:30:00 | publish      | page              |
|  3 |           1 | 2024-01-16 08:00:00 | inherit      | attachment         |
|  4 |           1 | 2024-01-15 10:30:00 | inherit      | revision          |
|  5 |           0 | 2024-01-17 00:00:00 | auto-draft   | post              |
|  6 |           1 | 2024-02-01 09:00:00 | future       | post              |
|  7 |           1 | 2024-01-18 11:00:00 | publish      | nav_menu_item     |
|  8 |           1 | 2024-01-18 12:00:00 | publish      | product           |
+----+-------------+---------------------+--------------+-------------------+
```

### So Sánh Laravel

```php
// Laravel Migration tương đương
Schema::create('posts', function (Blueprint $table) {
    $table->id();                                    // ID
    $table->foreignId('user_id');                    // post_author
    $table->timestamp('published_at')->nullable();   // post_date
    $table->longText('content');                      // post_content
    $table->string('title');                          // post_title
    $table->text('excerpt')->nullable();              // post_excerpt
    $table->string('status', 20)->default('draft');  // post_status
    $table->string('slug', 200)->unique();           // post_name
    $table->string('type', 20)->default('post');     // post_type
    $table->foreignId('parent_id')->nullable();      // post_parent
    $table->integer('sort_order')->default(0);       // menu_order
    $table->timestamps();                             // post_modified
});
```

> **Điểm khác biệt lớn:** WordPress lưu TẤT CẢ loại nội dung vào 1 bảng duy nhất (posts, pages, attachments, menus...). Laravel thường tạo bảng riêng cho từng entity.

---

## 3. Bảng wp_postmeta - Meta Data Bài Viết

> Mở rộng dữ liệu cho `wp_posts` theo mô hình **EAV (Entity-Attribute-Value)**. Mỗi post có thể có vô số cặp key-value.

### SQL Gốc (từ schema.php dòng 150-158)

```sql
CREATE TABLE wp_postmeta (
    meta_id bigint(20) unsigned NOT NULL auto_increment,
    post_id bigint(20) unsigned NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (meta_id),
    KEY post_id (post_id),
    KEY meta_key (meta_key(191))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `meta_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** ID duy nhất cho mỗi meta entry. |
| `post_id` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_posts.ID.** Post mà meta này thuộc về. |
| `meta_key` | `varchar(255)` default `NULL` | **Tên key.** Ví dụ: `_thumbnail_id`, `_edit_last`, `price`, `_wp_page_template`. Key bắt đầu bằng `_` được ẩn khỏi Custom Fields UI mặc định. |
| `meta_value` | `longtext` | **Giá trị.** Có thể chứa string, number, hoặc serialized array/object. Tối đa ~4GB. |

### Meta Keys Quan Trọng Của WordPress

| Meta Key | Mô tả | Ví dụ giá trị |
|----------|--------|----------------|
| `_thumbnail_id` | ID ảnh đại diện (Featured Image) | `42` (ID của attachment post) |
| `_wp_page_template` | Template file cho Page | `templates/full-width.php` |
| `_edit_last` | User ID người sửa cuối | `1` |
| `_edit_lock` | Lock chỉnh sửa (timestamp:user_id) | `1705305000:1` |
| `_wp_old_slug` | Slug cũ (dùng cho redirect 301) | `old-post-name` |
| `_wp_attached_file` | Đường dẫn file attachment | `2024/01/image.jpg` |
| `_wp_attachment_metadata` | Metadata ảnh (serialized) | Serialized array chứa width, height, sizes... |
| `_encloseme` | Flag đánh dấu cần gửi enclosure | `1` |

### Ví Dụ Dữ Liệu

```
+---------+---------+-----------------------------+----------------------------------+
| meta_id | post_id | meta_key                    | meta_value                       |
+---------+---------+-----------------------------+----------------------------------+
|       1 |       1 | _edit_last                  | 1                                |
|       2 |       1 | _thumbnail_id               | 42                               |
|       3 |       1 | price                       | 299000                           |
|       4 |       1 | _wp_old_slug                | bai-viet-cu                      |
|       5 |       3 | _wp_attached_file           | 2024/01/photo.jpg                |
|       6 |       3 | _wp_attachment_metadata     | a:6:{s:5:"width";i:1920;...}    |
+---------+---------+-----------------------------+----------------------------------+
```

### API Sử Dụng

```php
// Thêm meta
add_post_meta( $post_id, 'price', 299000 );

// Lấy meta
$price = get_post_meta( $post_id, 'price', true );   // true = single value
$all   = get_post_meta( $post_id, 'price', false );   // false = array tất cả

// Cập nhật meta
update_post_meta( $post_id, 'price', 399000 );

// Xóa meta
delete_post_meta( $post_id, 'price' );

// Query theo meta (WP_Query)
$query = new WP_Query([
    'meta_query' => [
        [
            'key'     => 'price',
            'value'   => 200000,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ],
    ],
]);
```

> **So sánh Laravel:** Tương đương JSON column hoặc bảng EAV riêng. Laravel thường dùng `$casts` để serialize JSON vào một cột thay vì tạo bảng meta riêng.

---

## 4. Bảng wp_comments - Bình Luận

> Lưu trữ bình luận, pingback, và trackback.

### SQL Gốc (từ schema.php dòng 101-123)

```sql
CREATE TABLE wp_comments (
    comment_ID bigint(20) unsigned NOT NULL auto_increment,
    comment_post_ID bigint(20) unsigned NOT NULL default '0',
    comment_author tinytext NOT NULL,
    comment_author_email varchar(100) NOT NULL default '',
    comment_author_url varchar(200) NOT NULL default '',
    comment_author_IP varchar(100) NOT NULL default '',
    comment_date datetime NOT NULL default '0000-00-00 00:00:00',
    comment_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
    comment_content text NOT NULL,
    comment_karma int(11) NOT NULL default '0',
    comment_approved varchar(20) NOT NULL default '1',
    comment_agent varchar(255) NOT NULL default '',
    comment_type varchar(20) NOT NULL default 'comment',
    comment_parent bigint(20) unsigned NOT NULL default '0',
    user_id bigint(20) unsigned NOT NULL default '0',
    PRIMARY KEY  (comment_ID),
    KEY comment_post_ID (comment_post_ID),
    KEY comment_approved_date_gmt (comment_approved,comment_date_gmt),
    KEY comment_date_gmt (comment_date_gmt),
    KEY comment_parent (comment_parent),
    KEY comment_author_email (comment_author_email(10))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `comment_ID` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** ID duy nhất cho mỗi comment. |
| `comment_post_ID` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_posts.ID.** Bài viết được bình luận. |
| `comment_author` | `tinytext` | **Tên người bình luận.** Tối đa 255 bytes. Không bắt buộc là user đã đăng ký. |
| `comment_author_email` | `varchar(100)` default `''` | Email người bình luận. Dùng để hiển thị Gravatar. |
| `comment_author_url` | `varchar(200)` default `''` | Website của người bình luận. |
| `comment_author_IP` | `varchar(100)` default `''` | Địa chỉ IP. Dùng cho anti-spam (Akismet) và blacklist. Lưu ý: GDPR yêu cầu có thể xóa/ẩn. |
| `comment_date` | `datetime` default `'0000-00-00 00:00:00'` | Thời gian bình luận (timezone local). |
| `comment_date_gmt` | `datetime` default `'0000-00-00 00:00:00'` | Thời gian bình luận (GMT/UTC). |
| `comment_content` | `text` | Nội dung bình luận. Cho phép HTML hạn chế (filtered). Tối đa ~65KB. |
| `comment_karma` | `int(11)` default `'0'` | Điểm karma (up/down vote). WordPress core không dùng, nhưng plugins có thể tận dụng. |
| `comment_approved` | `varchar(20)` default `'1'` | **Trạng thái duyệt.** `'1'` = đã duyệt, `'0'` = chờ duyệt, `'spam'` = spam, `'trash'` = thùng rác, `'post-trashed'` = post cha đã xóa. |
| `comment_agent` | `varchar(255)` default `''` | User-Agent trình duyệt. Ví dụ: `Mozilla/5.0 (Windows NT 10.0...)`. |
| `comment_type` | `varchar(20)` default `'comment'` | **Loại bình luận.** `'comment'` = bình luận thường, `'pingback'` = pingback, `'trackback'` = trackback. Plugin có thể thêm type mới. |
| `comment_parent` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_comments.comment_ID.** ID comment cha (threaded/nested comments). `0` = comment gốc. |
| `user_id` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_users.ID.** `0` = khách (guest). Nếu > 0 = user đã đăng nhập. |

### Indexes

| Index | Cột | Mục đích |
|-------|-----|----------|
| `PRIMARY KEY` | `comment_ID` | Tra cứu comment theo ID |
| `comment_post_ID` | `comment_post_ID` | Lấy comments của một bài viết |
| `comment_approved_date_gmt` | `comment_approved, comment_date_gmt` | Query comments đã duyệt theo thời gian |
| `comment_date_gmt` | `comment_date_gmt` | Sắp xếp theo thời gian |
| `comment_parent` | `comment_parent` | Tìm replies (threaded comments) |
| `comment_author_email` | `comment_author_email(10)` | Tìm theo email (chỉ index 10 ký tự đầu) |

### API Sử Dụng

```php
// Lấy comments của 1 post
$comments = get_comments([
    'post_id' => 123,
    'status'  => 'approve',
    'orderby' => 'comment_date_gmt',
    'order'   => 'ASC',
]);

// Thêm comment
wp_insert_comment([
    'comment_post_ID' => 123,
    'comment_author'  => 'Nguyễn Văn A',
    'comment_content' => 'Bài viết rất hay!',
    'comment_approved' => 1,
    'user_id'         => 5,
]);

// WP_Comment_Query
$query = new WP_Comment_Query([
    'post_id' => 123,
    'type'    => 'comment',
    'status'  => 'approve',
]);
```

---

## 5. Bảng wp_commentmeta - Meta Data Bình Luận

> Mở rộng dữ liệu cho `wp_comments`. Cấu trúc EAV giống wp_postmeta.

### SQL Gốc (từ schema.php dòng 92-100)

```sql
CREATE TABLE wp_commentmeta (
    meta_id bigint(20) unsigned NOT NULL auto_increment,
    comment_id bigint(20) unsigned NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (meta_id),
    KEY comment_id (comment_id),
    KEY meta_key (meta_key(191))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `meta_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** |
| `comment_id` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_comments.comment_ID.** |
| `meta_key` | `varchar(255)` default `NULL` | Tên metadata. Ví dụ: Akismet lưu `akismet_result`, `akismet_history`. |
| `meta_value` | `longtext` | Giá trị metadata. |

### API Sử Dụng

```php
add_comment_meta( $comment_id, 'rating', 5 );
$rating = get_comment_meta( $comment_id, 'rating', true );
update_comment_meta( $comment_id, 'rating', 4 );
delete_comment_meta( $comment_id, 'rating' );
```

---

## 6. Bảng wp_terms - Thuật Ngữ

> Lưu trữ tất cả terms (hạng mục): categories, tags, và custom taxonomy terms. Mỗi term là một "nhãn" có thể gắn với nhiều taxonomy.

### SQL Gốc (từ schema.php dòng 65-73)

```sql
CREATE TABLE wp_terms (
    term_id bigint(20) unsigned NOT NULL auto_increment,
    name varchar(200) NOT NULL default '',
    slug varchar(200) NOT NULL default '',
    term_group bigint(10) NOT NULL default 0,
    PRIMARY KEY  (term_id),
    KEY slug (slug(191)),
    KEY name (name(191))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `term_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** ID duy nhất cho mỗi term. |
| `name` | `varchar(200)` default `''` | **Tên hiển thị.** Ví dụ: `"Công nghệ"`, `"WordPress"`, `"Tin tức"`. Cho phép ký tự Unicode, khoảng trắng. |
| `slug` | `varchar(200)` default `''` | **Slug dùng trong URL.** Ví dụ: `cong-nghe`, `wordpress`. Tự động tạo từ `name`. |
| `term_group` | `bigint(10)` default `0` | Nhóm term. **Hiếm khi dùng.** Được thiết kế để nhóm các term "giống nhau" lại (term aliases), nhưng tính năng chưa bao giờ hoàn thiện. |

### Ví Dụ Dữ Liệu

```
+---------+---------------+--------------+------------+
| term_id | name          | slug         | term_group |
+---------+---------------+--------------+------------+
|       1 | Chưa phân loại| chua-phan-loai|          0 |
|       2 | Công nghệ     | cong-nghe    |          0 |
|       3 | WordPress     | wordpress    |          0 |
|       4 | Laravel       | laravel      |          0 |
|       5 | PHP           | php          |          0 |
+---------+---------------+--------------+------------+
```

> **Lưu ý:** Một term có thể thuộc nhiều taxonomy khác nhau (ví dụ: "WordPress" có thể vừa là category vừa là tag). Mối quan hệ term ↔ taxonomy nằm ở bảng `wp_term_taxonomy`.

---

## 7. Bảng wp_termmeta - Meta Data Thuật Ngữ

> Mở rộng dữ liệu cho `wp_terms`. Thêm từ WordPress 4.4.

### SQL Gốc (từ schema.php dòng 56-64)

```sql
CREATE TABLE wp_termmeta (
    meta_id bigint(20) unsigned NOT NULL auto_increment,
    term_id bigint(20) unsigned NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (meta_id),
    KEY term_id (term_id),
    KEY meta_key (meta_key(191))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `meta_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** |
| `term_id` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_terms.term_id.** |
| `meta_key` | `varchar(255)` default `NULL` | Tên metadata. Ví dụ: `'category_image'`, `'color'`, `'icon'`. |
| `meta_value` | `longtext` | Giá trị metadata. |

### API Sử Dụng

```php
add_term_meta( $term_id, 'category_image', $image_url );
$image = get_term_meta( $term_id, 'category_image', true );
update_term_meta( $term_id, 'category_image', $new_url );
delete_term_meta( $term_id, 'category_image' );
```

---

## 8. Bảng wp_term_taxonomy - Phân Loại Thuật Ngữ

> **Bảng nối** gắn mỗi term vào một taxonomy cụ thể. Cho phép cùng một term có thể thuộc nhiều taxonomy khác nhau.

### SQL Gốc (từ schema.php dòng 74-84)

```sql
CREATE TABLE wp_term_taxonomy (
    term_taxonomy_id bigint(20) unsigned NOT NULL auto_increment,
    term_id bigint(20) unsigned NOT NULL default 0,
    taxonomy varchar(32) NOT NULL default '',
    description longtext NOT NULL,
    parent bigint(20) unsigned NOT NULL default 0,
    count bigint(20) NOT NULL default 0,
    PRIMARY KEY  (term_taxonomy_id),
    UNIQUE KEY term_id_taxonomy (term_id,taxonomy),
    KEY taxonomy (taxonomy)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `term_taxonomy_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** ID duy nhất cho mỗi cặp term+taxonomy. |
| `term_id` | `bigint(20) unsigned` default `0` | **Foreign Key → wp_terms.term_id.** |
| `taxonomy` | `varchar(32)` default `''` | **Tên taxonomy.** Built-in: `'category'`, `'post_tag'`, `'nav_menu'`, `'link_category'`, `'post_format'`, `'wp_theme'`, `'wp_template_part_area'`. Custom: đăng ký bằng `register_taxonomy()`. |
| `description` | `longtext` | Mô tả của term trong taxonomy này. Hiển thị trên trang archive. |
| `parent` | `bigint(20) unsigned` default `0` | **Foreign Key → wp_term_taxonomy.term_taxonomy_id.** ID cha (hierarchical taxonomy như category). `0` = không có cha. Chỉ áp dụng cho taxonomy có `'hierarchical' => true`. |
| `count` | `bigint(20)` default `0` | **Cache counter.** Số lượng objects (posts) thuộc term này. Tự cập nhật. |

### Ví Dụ Dữ Liệu

```
+------------------+---------+----------+------------------+--------+-------+
| term_taxonomy_id | term_id | taxonomy | description      | parent | count |
+------------------+---------+----------+------------------+--------+-------+
|                1 |       1 | category |                  |      0 |     5 |
|                2 |       2 | category | Tin tức công nghệ|      0 |    12 |
|                3 |       3 | post_tag |                  |      0 |     8 |
|                4 |       3 | category | Chuyên mục WP    |      2 |     3 |
|                5 |       4 | post_tag |                  |      0 |     6 |
+------------------+---------+----------+------------------+--------+-------+
```

> Chú ý dòng 3 và 4: term_id=3 ("WordPress") vừa là `post_tag` vừa là `category` - cùng 1 term nhưng thuộc 2 taxonomy khác nhau.

---

## 9. Bảng wp_term_relationships - Quan Hệ Nội Dung & Thuật Ngữ

> **Bảng pivot** nối `wp_posts` với `wp_term_taxonomy`. Tương đương bảng pivot trong Laravel.

### SQL Gốc (từ schema.php dòng 85-91)

```sql
CREATE TABLE wp_term_relationships (
    object_id bigint(20) unsigned NOT NULL default 0,
    term_taxonomy_id bigint(20) unsigned NOT NULL default 0,
    term_order int(11) NOT NULL default 0,
    PRIMARY KEY  (object_id,term_taxonomy_id),
    KEY term_taxonomy_id (term_taxonomy_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `object_id` | `bigint(20) unsigned` default `0` | **Foreign Key → wp_posts.ID** (hoặc wp_links.link_id). ID của object được gắn term. Thường là post ID. |
| `term_taxonomy_id` | `bigint(20) unsigned` default `0` | **Foreign Key → wp_term_taxonomy.term_taxonomy_id.** |
| `term_order` | `int(11)` default `0` | Thứ tự sắp xếp term cho object này. `0` = mặc định. |

### Composite Primary Key

```
PRIMARY KEY (object_id, term_taxonomy_id)
```

Mỗi cặp (object, term_taxonomy) là duy nhất = một post chỉ được gắn 1 lần vào 1 term+taxonomy.

### Ví Dụ: Mối Quan Hệ Hoàn Chỉnh

```
Post #1 "Học WordPress" thuộc:
  - Category "Công nghệ" (term_id=2, term_taxonomy_id=2)
  - Tag "WordPress" (term_id=3, term_taxonomy_id=3)
  - Tag "PHP" (term_id=5, term_taxonomy_id=5)

wp_term_relationships:
+-----------+------------------+------------+
| object_id | term_taxonomy_id | term_order |
+-----------+------------------+------------+
|         1 |                2 |          0 |  ← Post 1 → Category "Công nghệ"
|         1 |                3 |          0 |  ← Post 1 → Tag "WordPress"
|         1 |                5 |          0 |  ← Post 1 → Tag "PHP"
+-----------+------------------+------------+
```

### So Sánh Laravel

```php
// Laravel tương đương - Bảng pivot
Schema::create('post_term', function (Blueprint $table) {
    $table->foreignId('post_id');           // object_id
    $table->foreignId('term_taxonomy_id');  // term_taxonomy_id
    $table->integer('order')->default(0);   // term_order
    $table->primary(['post_id', 'term_taxonomy_id']);
});

// Eloquent relationship
class Post extends Model {
    public function categories() {
        return $this->belongsToMany(Term::class, 'post_term');
    }
}
```

### Sơ Đồ Quan Hệ Taxonomy (4 bảng)

```
wp_posts                 wp_term_relationships       wp_term_taxonomy        wp_terms
+--------+              +-----------+--------+       +--------+--------+     +--------+------+
| ID     |──────1:N────▶| object_id | tt_id  |◀──N:1─| tt_id  | term_id|──N:1▶| term_id| name |
| title  |              | term_order|        |       | taxonomy|        |     | slug   |      |
| type   |              +-----------+--------+       | parent  |        |     +--------+------+
+--------+                                           | count   |        |
                                                     | desc    |        |
                                                     +--------+--------+

Ví dụ flow:
Post "Học WP" (ID=1) → object_id=1, tt_id=2 → term_id=2, taxonomy='category' → name="Công nghệ"
```

---

## 10. Bảng wp_options - Cấu Hình

> **Bảng cấu hình trung tâm** của WordPress. Lưu settings, widget config, plugin options, transients, và mọi thứ cần lưu trữ key-value.

### SQL Gốc (từ schema.php dòng 141-149)

```sql
CREATE TABLE wp_options (
    option_id bigint(20) unsigned NOT NULL auto_increment,
    option_name varchar(191) NOT NULL default '',
    option_value longtext NOT NULL,
    autoload varchar(20) NOT NULL default 'yes',
    PRIMARY KEY  (option_id),
    UNIQUE KEY option_name (option_name),
    KEY autoload (autoload)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `option_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** |
| `option_name` | `varchar(191)` default `''` | **Tên option, UNIQUE.** Giới hạn 191 ký tự do `utf8mb4` index. Ví dụ: `'siteurl'`, `'blogname'`, `'active_plugins'`. |
| `option_value` | `longtext` | **Giá trị.** Có thể là string, number, hoặc serialized PHP array/object. Tối đa ~4GB. |
| `autoload` | `varchar(20)` default `'yes'` | **Tự động load?** `'yes'`/`'on'` = load vào bộ nhớ mỗi request. `'no'`/`'off'` = chỉ load khi gọi `get_option()`. **Quan trọng cho performance!** |

> **Lưu ý quan trọng:** `option_name` là `varchar(191)` chứ không phải `varchar(255)` như các meta_key khác. Lý do: cột này có UNIQUE INDEX, mà `utf8mb4` giới hạn 191 ký tự cho index.

### Cột autoload - Hiệu Năng

```php
// Mỗi request, WordPress chạy query:
SELECT option_name, option_value FROM wp_options WHERE autoload IN ('yes', 'on', 'auto', 'auto-on')
// → Tất cả options autoload được load 1 lần vào bộ nhớ

// Options lớn KHÔNG nên autoload:
add_option( 'my_large_data', $big_array, '', 'no' );

// Hoặc dùng update_option với autoload:
update_option( 'my_option', $value, false ); // false = không autoload
```

### Options Mặc Định Quan Trọng (từ populate_options)

| Option Name | Default Value | Mô tả |
|-------------|---------------|--------|
| `siteurl` | URL tự detect | URL cài đặt WordPress (có /wp nếu cài trong subfolder) |
| `home` | URL tự detect | URL trang chủ hiển thị |
| `blogname` | `'My Site'` | Tên website |
| `blogdescription` | `''` | Mô tả website (tagline) |
| `admin_email` | `'you@example.com'` | Email admin |
| `users_can_register` | `0` | Cho phép đăng ký? 0=không, 1=có |
| `default_role` | `'subscriber'` | Role mặc định khi đăng ký |
| `posts_per_page` | `10` | Số bài viết mỗi trang |
| `date_format` | `'F j, Y'` | Format ngày |
| `time_format` | `'g:i a'` | Format giờ |
| `permalink_structure` | `''` | Cấu trúc URL. Rỗng = `?p=123` |
| `template` | Theme template | Theme đang dùng (parent theme) |
| `stylesheet` | Theme stylesheet | Theme đang dùng (child theme nếu có) |
| `active_plugins` | `array()` | Danh sách plugins đang active (serialized) |
| `show_on_front` | `'posts'` | Trang chủ hiển thị: `'posts'` hoặc `'page'` |
| `page_on_front` | `0` | ID page làm trang chủ |
| `page_for_posts` | `0` | ID page làm trang blog |
| `blog_charset` | `'UTF-8'` | Character encoding |
| `blog_public` | `'1'` | Cho search engine index? |
| `default_comment_status` | `'open'` | Mặc định cho phép comment? |
| `thumbnail_size_w` | `150` | Chiều rộng thumbnail |
| `thumbnail_size_h` | `150` | Chiều cao thumbnail |
| `medium_size_w` | `300` | Chiều rộng medium |
| `medium_size_h` | `300` | Chiều cao medium |
| `large_size_w` | `1024` | Chiều rộng large |
| `large_size_h` | `1024` | Chiều cao large |
| `medium_large_size_w` | `768` | Chiều rộng medium_large (từ WP 4.4) |
| `uploads_use_yearmonth_folders` | `1` | Upload theo thư mục năm/tháng? |
| `db_version` | Tự detect | Phiên bản database schema |
| `initial_db_version` | Tự detect | Phiên bản DB khi cài lần đầu |

### Options Autoload = 'off' (Fat Options)

Các options này lớn và hiếm khi cần, nên KHÔNG autoload:

```php
$fat_options = [
    'moderation_keys',                  // Từ khóa moderate comment
    'recently_edited',                  // Files plugin/theme vừa sửa
    'disallowed_keys',                  // Từ khóa cấm trong comment
    'uninstall_plugins',                // Callback khi uninstall plugins
    'auto_plugin_theme_update_emails',  // Config email auto-update
];
```

### Transients Trong wp_options

WordPress lưu transients (cache tạm thời) ngay trong `wp_options`:

```
option_name: _transient_timeout_feed_abc123    → Thời gian hết hạn
option_name: _transient_feed_abc123            → Giá trị cache

option_name: _site_transient_timeout_xxx       → Site-wide transient timeout
option_name: _site_transient_xxx               → Site-wide transient value
```

> **Vấn đề:** Nếu site không dùng Object Cache (Redis/Memcached), bảng `wp_options` sẽ phình to với hàng ngàn transient rows. Dùng Object Cache để giảm tải.

### API Sử Dụng

```php
// CRUD options
add_option( 'my_option', 'value', '', 'yes' );  // 'yes' = autoload
get_option( 'my_option', 'default_value' );
update_option( 'my_option', 'new_value' );
delete_option( 'my_option' );

// Transients (cache tạm thời)
set_transient( 'api_data', $data, HOUR_IN_SECONDS );
$data = get_transient( 'api_data' ); // false nếu hết hạn
delete_transient( 'api_data' );
```

---

## 11. Bảng wp_users - Người Dùng

> Lưu thông tin cơ bản của user. Có 2 phiên bản: Single site và Multisite.

### SQL Gốc - Single Site (từ schema.php dòng 191-206)

```sql
CREATE TABLE wp_users (
    ID bigint(20) unsigned NOT NULL auto_increment,
    user_login varchar(60) NOT NULL default '',
    user_pass varchar(255) NOT NULL default '',
    user_nicename varchar(50) NOT NULL default '',
    user_email varchar(100) NOT NULL default '',
    user_url varchar(100) NOT NULL default '',
    user_registered datetime NOT NULL default '0000-00-00 00:00:00',
    user_activation_key varchar(255) NOT NULL default '',
    user_status int(11) NOT NULL default '0',
    display_name varchar(250) NOT NULL default '',
    PRIMARY KEY  (ID),
    KEY user_login_key (user_login),
    KEY user_nicename (user_nicename),
    KEY user_email (user_email)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### SQL Gốc - Multisite (từ schema.php dòng 209-226)

Multisite thêm 2 cột:

```sql
-- Thêm vào cuối, trước PRIMARY KEY:
    spam tinyint(2) NOT NULL default '0',
    deleted tinyint(2) NOT NULL default '0',
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `ID` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** |
| `user_login` | `varchar(60)` default `''` | **Username đăng nhập.** Không thay đổi được sau khi tạo (by design). Case-insensitive khi login. |
| `user_pass` | `varchar(255)` default `''` | **Mật khẩu đã hash.** WordPress dùng PHPass (bcrypt-like). Format: `$P$B...` hoặc `$wp$...` (từ WP 6.x dùng bcrypt). **KHÔNG BAO GIỜ lưu plain text!** |
| `user_nicename` | `varchar(50)` default `''` | **Slug** dùng trong URL author page. Ví dụ: `nguyen-van-a`. Tự tạo từ `user_login`, sanitized. |
| `user_email` | `varchar(100)` default `''` | **Email.** Unique (được enforce bởi code, không phải DB constraint). Dùng cho login, reset password, notifications. |
| `user_url` | `varchar(100)` default `''` | Website cá nhân. Hiển thị trên profile. |
| `user_registered` | `datetime` default `'0000-00-00 00:00:00'` | Ngày đăng ký (GMT). |
| `user_activation_key` | `varchar(255)` default `''` | Key kích hoạt / reset password. Hash của token. Tạm thời, xóa sau khi dùng. |
| `user_status` | `int(11)` default `'0'` | **Deprecated.** Không dùng nữa. Luôn = `0`. Chức năng spam/delete chuyển sang multisite columns. |
| `display_name` | `varchar(250)` default `''` | **Tên hiển thị.** Có thể chọn: username, first name, last name, nickname, hoặc tùy chỉnh. Hiển thị trong bài viết, comments. |
| `spam` | `tinyint(2)` default `'0'` | **(Chỉ Multisite)** `1` = user bị đánh dấu spam. |
| `deleted` | `tinyint(2)` default `'0'` | **(Chỉ Multisite)** `1` = user bị xóa (soft delete). |

### Ví Dụ Dữ Liệu

```
+----+------------+--------------------+--------------+--------------------+---+
| ID | user_login | user_pass          | user_nicename| user_email         |...|
+----+------------+--------------------+--------------+--------------------+---+
|  1 | admin      | $P$Bx1Abc2Def3... | admin        | admin@example.com  |   |
|  2 | editor1    | $P$By4Ghi5Jkl6... | editor1      | editor@example.com |   |
|  3 | nguyenvana | $P$Bz7Mno8Pqr9... | nguyen-van-a | vana@example.com   |   |
+----+------------+--------------------+--------------+--------------------+---+
```

### API Sử Dụng

```php
// Tạo user
$user_id = wp_create_user( 'username', 'password', 'email@example.com' );

// Lấy user
$user = get_user_by( 'id', 1 );        // Theo ID
$user = get_user_by( 'login', 'admin' ); // Theo username
$user = get_user_by( 'email', 'a@b.com' ); // Theo email

// WP_User_Query
$query = new WP_User_Query([
    'role'    => 'editor',
    'orderby' => 'registered',
    'order'   => 'DESC',
]);

// Verify password
wp_check_password( 'plain_password', $user->user_pass, $user->ID );
```

---

## 12. Bảng wp_usermeta - Meta Data Người Dùng

> Mở rộng dữ liệu cho `wp_users`. Lưu role, capabilities, preferences, và custom fields.

### SQL Gốc (từ schema.php dòng 229-237)

```sql
CREATE TABLE wp_usermeta (
    umeta_id bigint(20) unsigned NOT NULL auto_increment,
    user_id bigint(20) unsigned NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (umeta_id),
    KEY user_id (user_id),
    KEY meta_key (meta_key(191))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả chi tiết |
|-----|------|-----------------|
| `umeta_id` | `bigint(20) unsigned` AUTO_INCREMENT | **Primary Key.** Lưu ý: tên là `umeta_id` (không phải `meta_id` như các bảng meta khác). |
| `user_id` | `bigint(20) unsigned` default `'0'` | **Foreign Key → wp_users.ID.** |
| `meta_key` | `varchar(255)` default `NULL` | Tên metadata. Prefix `wp_` cho single site, `wp_2_` cho site 2 trong multisite. |
| `meta_value` | `longtext` | Giá trị metadata. |

### Meta Keys Quan Trọng

| Meta Key | Mô tả | Ví dụ giá trị |
|----------|--------|----------------|
| `first_name` | Tên | `'Văn A'` |
| `last_name` | Họ | `'Nguyễn'` |
| `nickname` | Nickname | `'vana'` |
| `description` | Tiểu sử | `'Lập trình viên PHP...'` |
| `wp_capabilities` | Roles (serialized) | `a:1:{s:13:"administrator";b:1;}` |
| `wp_user_level` | User level (deprecated) | `10` (admin=10, editor=7, author=2...) |
| `rich_editing` | Dùng Visual Editor? | `'true'` |
| `syntax_highlighting` | Bật syntax highlight? | `'true'` |
| `admin_color` | Bảng màu admin | `'fresh'` |
| `show_admin_bar_front` | Hiện admin bar? | `'true'` |
| `locale` | Ngôn ngữ riêng | `'vi'` |
| `wp_dashboard_quick_press_last_post_id` | Draft ID của Quick Draft | `42` |
| `session_tokens` | Session tokens (serialized) | Mảng các session đang active |
| `wp_user-settings` | Cài đặt admin riêng | `'editor=tinymce&libraryContent=browse'` |
| `wp_user-settings-time` | Thời gian lưu settings | `1705305000` |
| `dismissed_wp_pointers` | Các popup đã dismiss | `'wp496_privacy,wp500_gutenberg...'` |

### Multisite Meta Keys

Trong Multisite, mỗi site có prefix riêng:

```
wp_capabilities     ← Role trên site 1 (main)
wp_2_capabilities   ← Role trên site 2
wp_3_capabilities   ← Role trên site 3

wp_user_level       ← Level trên site 1
wp_2_user_level     ← Level trên site 2
```

### API Sử Dụng

```php
add_user_meta( $user_id, 'phone', '0901234567' );
$phone = get_user_meta( $user_id, 'phone', true );
update_user_meta( $user_id, 'phone', '0909876543' );
delete_user_meta( $user_id, 'phone' );

// Lấy tất cả meta của user
$all_meta = get_user_meta( $user_id ); // Trả về array of arrays
```

---

## 13. Bảng wp_links - Liên Kết (Legacy)

> **Bảng cũ (legacy).** Từ WordPress 3.5 (2012), tính năng Blogroll/Link Manager bị ẩn. Bảng vẫn được tạo nhưng gần như không dùng.

### SQL Gốc (từ schema.php dòng 124-140)

```sql
CREATE TABLE wp_links (
    link_id bigint(20) unsigned NOT NULL auto_increment,
    link_url varchar(255) NOT NULL default '',
    link_name varchar(255) NOT NULL default '',
    link_image varchar(255) NOT NULL default '',
    link_target varchar(25) NOT NULL default '',
    link_description varchar(255) NOT NULL default '',
    link_visible varchar(20) NOT NULL default 'Y',
    link_owner bigint(20) unsigned NOT NULL default '1',
    link_rating int(11) NOT NULL default '0',
    link_updated datetime NOT NULL default '0000-00-00 00:00:00',
    link_rel varchar(255) NOT NULL default '',
    link_notes mediumtext NOT NULL,
    link_rss varchar(255) NOT NULL default '',
    PRIMARY KEY  (link_id),
    KEY link_visible (link_visible)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Chi Tiết Từng Cột

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `link_id` | `bigint(20) unsigned` AUTO_INCREMENT | Primary Key |
| `link_url` | `varchar(255)` | URL đích |
| `link_name` | `varchar(255)` | Tên hiển thị |
| `link_image` | `varchar(255)` | URL ảnh đại diện |
| `link_target` | `varchar(25)` | Target: `_blank`, `_top`, `_none` |
| `link_description` | `varchar(255)` | Mô tả ngắn |
| `link_visible` | `varchar(20)` default `'Y'` | Hiển thị? `'Y'` / `'N'` |
| `link_owner` | `bigint(20) unsigned` default `'1'` | FK → wp_users.ID, người tạo |
| `link_rating` | `int(11)` default `'0'` | Đánh giá (0-10) |
| `link_updated` | `datetime` | Ngày cập nhật |
| `link_rel` | `varchar(255)` | Thuộc tính `rel`. Ví dụ: `nofollow` |
| `link_notes` | `mediumtext` | Ghi chú |
| `link_rss` | `varchar(255)` | URL RSS feed của link |

> **Để kích hoạt lại:** Cài plugin "Link Manager" hoặc thêm `define('LINK_MANAGER', true);` vào `wp-config.php`.

---

## 14. Bảng Multisite

> 6 bảng bổ sung khi bật WordPress Multisite. Các bảng này là **global** (dùng chung cho mọi site).

### 14.1. wp_blogs (schema.php dòng 247-263)

> Danh sách tất cả site/blog trong network.

```sql
CREATE TABLE wp_blogs (
    blog_id bigint(20) NOT NULL auto_increment,
    site_id bigint(20) NOT NULL default '0',
    domain varchar(200) NOT NULL default '',
    path varchar(100) NOT NULL default '',
    registered datetime NOT NULL default '0000-00-00 00:00:00',
    last_updated datetime NOT NULL default '0000-00-00 00:00:00',
    public tinyint(2) NOT NULL default '1',
    archived tinyint(2) NOT NULL default '0',
    mature tinyint(2) NOT NULL default '0',
    spam tinyint(2) NOT NULL default '0',
    deleted tinyint(2) NOT NULL default '0',
    lang_id int(11) NOT NULL default '0',
    PRIMARY KEY  (blog_id),
    KEY domain (domain(50),path(5)),
    KEY lang_id (lang_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `blog_id` | `bigint(20)` AUTO_INCREMENT | **Primary Key.** ID site. Site chính = 1. |
| `site_id` | `bigint(20)` default `'0'` | **FK → wp_site.id.** ID network mà site thuộc về. |
| `domain` | `varchar(200)` | Domain. Ví dụ: `example.com` hoặc `sub.example.com`. |
| `path` | `varchar(100)` | Path. Ví dụ: `/` hoặc `/blog2/`. |
| `registered` | `datetime` | Ngày tạo site. |
| `last_updated` | `datetime` | Lần cập nhật cuối. |
| `public` | `tinyint(2)` default `'1'` | Công khai? `1`=có, `0`=không. |
| `archived` | `tinyint(2)` default `'0'` | Đã archive? `1`=có. |
| `mature` | `tinyint(2)` default `'0'` | Nội dung mature? `1`=có. |
| `spam` | `tinyint(2)` default `'0'` | Bị đánh dấu spam? `1`=có. |
| `deleted` | `tinyint(2)` default `'0'` | Đã xóa (soft delete)? `1`=có. |
| `lang_id` | `int(11)` default `'0'` | ID ngôn ngữ. Hiện tại không dùng bởi core. |

### 14.2. wp_blogmeta (schema.php dòng 264-272)

> Metadata cho mỗi site. Thêm từ WordPress 5.1.

```sql
CREATE TABLE wp_blogmeta (
    meta_id bigint(20) unsigned NOT NULL auto_increment,
    blog_id bigint(20) NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (meta_id),
    KEY meta_key (meta_key(191)),
    KEY blog_id (blog_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 14.3. wp_site (schema.php dòng 282-288)

> Danh sách networks (trước gọi là "sites"). Thường chỉ có 1 record.

```sql
CREATE TABLE wp_site (
    id bigint(20) NOT NULL auto_increment,
    domain varchar(200) NOT NULL default '',
    path varchar(100) NOT NULL default '',
    PRIMARY KEY  (id),
    KEY domain (domain(140),path(51))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | `bigint(20)` AUTO_INCREMENT | ID network |
| `domain` | `varchar(200)` | Domain chính của network |
| `path` | `varchar(100)` | Path gốc |

### 14.4. wp_sitemeta (schema.php dòng 289-297)

> Network-wide settings. Tương đương `wp_options` nhưng cho toàn network.

```sql
CREATE TABLE wp_sitemeta (
    meta_id bigint(20) NOT NULL auto_increment,
    site_id bigint(20) NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (meta_id),
    KEY meta_key (meta_key(191)),
    KEY site_id (site_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 14.5. wp_registration_log (schema.php dòng 273-281)

> Log đăng ký site mới trong multisite.

```sql
CREATE TABLE wp_registration_log (
    ID bigint(20) NOT NULL auto_increment,
    email varchar(255) NOT NULL default '',
    IP varchar(30) NOT NULL default '',
    blog_id bigint(20) NOT NULL default '0',
    date_registered datetime NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY  (ID),
    KEY IP (IP)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 14.6. wp_signups (schema.php dòng 298-315)

> Quản lý đăng ký chờ xác nhận (user và site).

```sql
CREATE TABLE wp_signups (
    signup_id bigint(20) NOT NULL auto_increment,
    domain varchar(200) NOT NULL default '',
    path varchar(100) NOT NULL default '',
    title longtext NOT NULL,
    user_login varchar(60) NOT NULL default '',
    user_email varchar(100) NOT NULL default '',
    registered datetime NOT NULL default '0000-00-00 00:00:00',
    activated datetime NOT NULL default '0000-00-00 00:00:00',
    active tinyint(1) NOT NULL default '0',
    activation_key varchar(50) NOT NULL default '',
    meta longtext,
    PRIMARY KEY  (signup_id),
    KEY activation_key (activation_key),
    KEY user_email (user_email),
    KEY user_login_email (user_login,user_email),
    KEY domain_path (domain(140),path(51))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `signup_id` | `bigint(20)` AUTO_INCREMENT | Primary Key |
| `domain` | `varchar(200)` | Domain đăng ký |
| `path` | `varchar(100)` | Path đăng ký |
| `title` | `longtext` | Tên site đăng ký |
| `user_login` | `varchar(60)` | Username đăng ký |
| `user_email` | `varchar(100)` | Email đăng ký |
| `registered` | `datetime` | Ngày đăng ký |
| `activated` | `datetime` | Ngày kích hoạt |
| `active` | `tinyint(1)` default `'0'` | Đã kích hoạt? `0`=chưa, `1`=rồi |
| `activation_key` | `varchar(50)` | Key kích hoạt |
| `meta` | `longtext` | Metadata bổ sung (serialized) |

### Multisite Table Prefix

Trong Multisite, mỗi site có bộ bảng blog riêng với prefix:

```
Site 1 (main): wp_posts, wp_postmeta, wp_comments, ...
Site 2:        wp_2_posts, wp_2_postmeta, wp_2_comments, ...
Site 3:        wp_3_posts, wp_3_postmeta, wp_3_comments, ...

Bảng global (dùng chung): wp_users, wp_usermeta, wp_blogs, wp_site, ...
```

---

## 15. Default Options (populate_options)

> Hàm `populate_options()` trong `schema.php` (dòng 361-708) tạo tất cả options mặc định khi cài WordPress.

### Phân Loại Options Theo Chức Năng

#### General Settings

| Option | Default | Mô tả |
|--------|---------|--------|
| `siteurl` | Auto-detect | URL WordPress installation |
| `home` | Auto-detect | URL trang chủ |
| `blogname` | `'My Site'` | Tên site |
| `blogdescription` | `''` | Tagline |
| `admin_email` | `'you@example.com'` | Email admin |
| `users_can_register` | `0` | Cho phép đăng ký |
| `default_role` | `'subscriber'` | Role mặc định |
| `gmt_offset` | `0` | Offset timezone |
| `timezone_string` | `''` | Timezone string |
| `blog_charset` | `'UTF-8'` | Encoding |
| `blog_public` | `'1'` | Search engine indexing |

#### Reading Settings

| Option | Default | Mô tả |
|--------|---------|--------|
| `posts_per_page` | `10` | Số bài mỗi trang |
| `posts_per_rss` | `10` | Số bài trong RSS |
| `rss_use_excerpt` | `0` | RSS dùng excerpt? |
| `show_on_front` | `'posts'` | Trang chủ hiển thị |
| `page_on_front` | `0` | ID page trang chủ |
| `page_for_posts` | `0` | ID page blog |

#### Writing Settings

| Option | Default | Mô tả |
|--------|---------|--------|
| `default_category` | `1` | Category mặc định |
| `default_post_format` | `0` | Post format mặc định |
| `default_email_category` | `1` | Category khi post qua email |
| `use_balanceTags` | `0` | Auto-correct HTML? |
| `use_smilies` | `1` | Convert emoticons? |

#### Discussion Settings

| Option | Default | Mô tả |
|--------|---------|--------|
| `default_comment_status` | `'open'` | Comment mặc định |
| `default_ping_status` | `'open'` | Ping mặc định |
| `default_pingback_flag` | `1` | Auto pingback? |
| `require_name_email` | `1` | Yêu cầu name/email? |
| `comment_registration` | `0` | Phải đăng nhập mới comment? |
| `comments_notify` | `1` | Email khi có comment? |
| `moderation_notify` | `1` | Email khi chờ duyệt? |
| `comment_moderation` | `0` | Duyệt trước khi hiện? |
| `comment_max_links` | `2` | Số link tối đa trong comment |
| `close_comments_for_old_posts` | `0` | Tắt comment bài cũ? |
| `close_comments_days_old` | `14` | Số ngày để coi là "cũ" |
| `thread_comments` | `1` | Cho phép trả lời lồng nhau? |
| `thread_comments_depth` | `5` | Độ sâu tối đa |
| `page_comments` | `0` | Phân trang comments? |
| `comments_per_page` | `50` | Số comment mỗi trang |
| `default_comments_page` | `'newest'` | Trang mặc định |
| `comment_order` | `'asc'` | Thứ tự comments |
| `show_avatars` | `'1'` | Hiện avatar? |
| `avatar_rating` | `'G'` | Giới hạn avatar rating |
| `avatar_default` | `'mystery'` | Avatar mặc định |
| `comment_previously_approved` | `1` | Yêu cầu duyệt lần đầu? |
| `show_comments_cookies_opt_in` | `1` | Checkbox cookies GDPR? |

#### Media Settings

| Option | Default | Mô tả |
|--------|---------|--------|
| `thumbnail_size_w` | `150` | Thumbnail width |
| `thumbnail_size_h` | `150` | Thumbnail height |
| `thumbnail_crop` | `1` | Crop thumbnail? |
| `medium_size_w` | `300` | Medium width |
| `medium_size_h` | `300` | Medium height |
| `medium_large_size_w` | `768` | Medium-large width |
| `medium_large_size_h` | `0` | Medium-large height (0=proportional) |
| `large_size_w` | `1024` | Large width |
| `large_size_h` | `1024` | Large height |
| `uploads_use_yearmonth_folders` | `1` | Tổ chức theo năm/tháng? |

#### Permalink Settings

| Option | Default | Mô tả |
|--------|---------|--------|
| `permalink_structure` | `''` | Cấu trúc URL (rỗng = `?p=123`) |
| `category_base` | `''` | URL base cho category |
| `tag_base` | `''` | URL base cho tag |

#### Auto-Update Settings (từ WP 5.6)

| Option | Default | Mô tả |
|--------|---------|--------|
| `auto_update_core_dev` | `'enabled'` | Auto-update bản dev? |
| `auto_update_core_minor` | `'enabled'` | Auto-update bản minor? |
| `auto_update_core_major` | `'enabled'` | Auto-update bản major? |

---

## 16. Roles & Capabilities (populate_roles)

> Hàm `populate_roles()` (schema.php dòng 715-950) tạo 5 roles mặc định với capabilities tương ứng.

### 5 Default Roles

| Role | Level | Mô tả |
|------|-------|--------|
| `administrator` | 10 | Quản trị toàn quyền |
| `editor` | 7 | Quản lý nội dung (mọi bài viết) |
| `author` | 2 | Viết & quản lý bài viết riêng |
| `contributor` | 1 | Viết bài nhưng không publish |
| `subscriber` | 0 | Chỉ đọc |

### Ma Trận Capabilities

| Capability | Admin | Editor | Author | Contributor | Subscriber |
|------------|:-----:|:------:|:------:|:-----------:|:----------:|
| `read` | x | x | x | x | x |
| `edit_posts` | x | x | x | x | |
| `delete_posts` | x | x | x | x | |
| `publish_posts` | x | x | x | | |
| `upload_files` | x | x | x | | |
| `edit_published_posts` | x | x | x | | |
| `delete_published_posts` | x | x | x | | |
| `edit_others_posts` | x | x | | | |
| `delete_others_posts` | x | x | | | |
| `edit_pages` | x | x | | | |
| `edit_others_pages` | x | x | | | |
| `publish_pages` | x | x | | | |
| `delete_pages` | x | x | | | |
| `delete_others_pages` | x | x | | | |
| `edit_published_pages` | x | x | | | |
| `delete_published_pages` | x | x | | | |
| `edit_private_posts` | x | x | | | |
| `read_private_posts` | x | x | | | |
| `edit_private_pages` | x | x | | | |
| `read_private_pages` | x | x | | | |
| `delete_private_posts` | x | x | | | |
| `delete_private_pages` | x | x | | | |
| `manage_categories` | x | x | | | |
| `manage_links` | x | x | | | |
| `moderate_comments` | x | x | | | |
| `unfiltered_html` | x | x | | | |
| `switch_themes` | x | | | | |
| `edit_themes` | x | | | | |
| `activate_plugins` | x | | | | |
| `edit_plugins` | x | | | | |
| `edit_users` | x | | | | |
| `edit_files` | x | | | | |
| `manage_options` | x | | | | |
| `import` | x | | | | |
| `delete_users` | x | | | | |
| `create_users` | x | | | | |
| `unfiltered_upload` | x | | | | |
| `edit_dashboard` | x | | | | |
| `update_plugins` | x | | | | |
| `delete_plugins` | x | | | | |
| `install_plugins` | x | | | | |
| `update_themes` | x | | | | |
| `install_themes` | x | | | | |
| `update_core` | x | | | | |
| `list_users` | x | | | | |
| `remove_users` | x | | | | |
| `promote_users` | x | | | | |
| `edit_theme_options` | x | | | | |
| `delete_themes` | x | | | | |
| `export` | x | | | | |

### Roles Lưu Ở Đâu?

Roles và capabilities lưu trong `wp_options`:

```php
// option_name = 'wp_user_roles'
// option_value = serialized array:
[
    'administrator' => [
        'name' => 'Administrator',
        'capabilities' => [
            'switch_themes'    => true,
            'edit_themes'      => true,
            'activate_plugins' => true,
            // ... 50+ capabilities
        ],
    ],
    'editor' => [
        'name' => 'Editor',
        'capabilities' => [ /* ... */ ],
    ],
    // ...
]
```

User's role lưu trong `wp_usermeta`:

```php
// meta_key = 'wp_capabilities'
// meta_value = 'a:1:{s:13:"administrator";b:1;}'
```

### So Sánh Laravel

```php
// Laravel thường dùng package Spatie Permission:
// - Bảng roles: id, name, guard_name
// - Bảng permissions: id, name, guard_name
// - Bảng model_has_roles: role_id, model_type, model_id
// - Bảng model_has_permissions: permission_id, model_type, model_id

// WordPress lưu trực tiếp trong options + usermeta (không cần bảng riêng)
```

---

## 17. ERD - Sơ Đồ Quan Hệ

### Single Site ERD

```
┌──────────────────┐       ┌──────────────────┐
│    wp_users       │       │   wp_usermeta     │
├──────────────────┤       ├──────────────────┤
│ ID (PK)          │──1:N─▶│ umeta_id (PK)    │
│ user_login       │       │ user_id (FK)     │
│ user_pass        │       │ meta_key         │
│ user_email       │       │ meta_value       │
│ display_name     │       └──────────────────┘
│ ...              │
└──────┬───────────┘
       │
       │ 1:N (post_author)
       ▼
┌──────────────────┐       ┌──────────────────┐
│    wp_posts       │       │   wp_postmeta     │
├──────────────────┤       ├──────────────────┤
│ ID (PK)          │──1:N─▶│ meta_id (PK)     │
│ post_author (FK) │       │ post_id (FK)     │
│ post_content     │       │ meta_key         │
│ post_title       │       │ meta_value       │
│ post_status      │       └──────────────────┘
│ post_type        │
│ post_parent (FK→self)│
│ comment_count    │
│ ...              │
└──────┬───────────┘
       │
       ├──── 1:N ────────────────────────────┐
       │                                      ▼
       │                              ┌──────────────────┐
       │                              │  wp_comments      │
       │                              ├──────────────────┤
       │                              │ comment_ID (PK)  │
       │                              │ comment_post_ID  │
       │                              │ comment_author   │
       │                              │ comment_content  │
       │                              │ comment_approved │
       │                              │ comment_parent   │
       │                              │ user_id (FK)     │
       │                              └──────┬───────────┘
       │                                     │ 1:N
       │                                     ▼
       │                              ┌──────────────────┐
       │                              │ wp_commentmeta   │
       │                              ├──────────────────┤
       │                              │ meta_id (PK)     │
       │                              │ comment_id (FK)  │
       │                              │ meta_key         │
       │                              │ meta_value       │
       │                              └──────────────────┘
       │
       │ 1:N
       ▼
┌──────────────────────┐    ┌────────────────────┐    ┌──────────────┐
│wp_term_relationships │    │ wp_term_taxonomy    │    │  wp_terms     │
├──────────────────────┤    ├────────────────────┤    ├──────────────┤
│ object_id (PK,FK)    │─N:1▶ term_taxonomy_id   │─N:1▶ term_id (PK) │
│ term_taxonomy_id(PK) │    │ (PK)               │    │ name         │
│ term_order           │    │ term_id (FK)       │    │ slug         │
└──────────────────────┘    │ taxonomy           │    │ term_group   │
                            │ description        │    └──────┬───────┘
                            │ parent             │           │ 1:N
                            │ count              │           ▼
                            └────────────────────┘    ┌──────────────┐
                                                      │ wp_termmeta  │
                                                      ├──────────────┤
                                                      │ meta_id (PK) │
                                                      │ term_id (FK) │
                                                      │ meta_key     │
                                                      │ meta_value   │
                                                      └──────────────┘

┌──────────────────┐         ┌──────────────────┐
│   wp_options      │         │   wp_links       │
├──────────────────┤         ├──────────────────┤
│ option_id (PK)   │         │ link_id (PK)     │
│ option_name (UQ) │         │ link_url         │
│ option_value     │         │ link_name        │
│ autoload         │         │ link_owner (FK)  │
└──────────────────┘         │ ...              │
                              └──────────────────┘
```

### Multisite ERD (bổ sung)

```
┌──────────────┐       ┌──────────────┐
│   wp_site     │       │  wp_sitemeta  │
├──────────────┤       ├──────────────┤
│ id (PK)      │──1:N─▶│ meta_id (PK) │
│ domain       │       │ site_id (FK) │
│ path         │       │ meta_key     │
└──────┬───────┘       │ meta_value   │
       │               └──────────────┘
       │ 1:N (site_id)
       ▼
┌──────────────────┐       ┌──────────────────┐
│    wp_blogs       │       │   wp_blogmeta     │
├──────────────────┤       ├──────────────────┤
│ blog_id (PK)     │──1:N─▶│ meta_id (PK)     │
│ site_id (FK)     │       │ blog_id (FK)     │
│ domain           │       │ meta_key         │
│ path             │       │ meta_value       │
│ public/spam/del  │       └──────────────────┘
└──────────────────┘

┌────────────────────┐       ┌──────────────────┐
│ wp_registration_log │       │   wp_signups      │
├────────────────────┤       ├──────────────────┤
│ ID (PK)            │       │ signup_id (PK)   │
│ email              │       │ domain           │
│ IP                 │       │ user_login       │
│ blog_id            │       │ user_email       │
│ date_registered    │       │ active           │
└────────────────────┘       │ activation_key   │
                              └──────────────────┘
```

---

## 18. So Sánh Với Laravel

### Kiến Trúc Database

| Đặc điểm | WordPress | Laravel |
|-----------|-----------|---------|
| **ORM** | Không có ORM. Dùng `$wpdb` (raw query wrapper) | Eloquent ORM (Active Record) |
| **Migration** | `dbDelta()` so sánh schema hiện tại vs mong muốn | Migration files chạy tuần tự (up/down) |
| **Schema** | 1 file `schema.php` chứa tất cả | Nhiều migration files theo thời gian |
| **Mô hình dữ liệu** | EAV (Entity-Attribute-Value) với bảng meta | Cột cụ thể cho từng attribute |
| **Extensibility** | Thêm data → thêm row vào bảng meta | Thêm data → migration thêm cột/bảng mới |
| **Query Builder** | `$wpdb->prepare()` | Eloquent / Query Builder |
| **Relationship** | Không có (tự JOIN) | `hasMany`, `belongsTo`, `belongsToMany`... |
| **Soft Delete** | `post_status = 'trash'` | `SoftDeletes` trait |
| **Timestamps** | `post_date` + `post_modified` (manual) | `created_at` + `updated_at` (auto) |

### Ưu/Nhược Điểm EAV của WordPress

**Ưu điểm:**
- Linh hoạt cực cao: thêm field mới KHÔNG cần migrate database
- Plugin/theme có thể lưu bất kỳ data nào vào meta mà không sửa schema
- Backward compatible: schema gần như không thay đổi từ WP 4.4

**Nhược điểm:**
- Query chậm hơn khi JOIN nhiều meta (so với cột cụ thể)
- Không có type checking ở DB level (mọi thứ đều là `longtext`)
- Data không normalize → khó làm foreign key constraints
- Meta table phình to theo thời gian

### Ví Dụ So Sánh Query

```php
// WordPress: Tìm sản phẩm giá > 200000, còn hàng
$query = new WP_Query([
    'post_type'  => 'product',
    'meta_query' => [
        'relation' => 'AND',
        [
            'key'     => 'price',
            'value'   => 200000,
            'compare' => '>',
            'type'    => 'NUMERIC',
        ],
        [
            'key'   => 'in_stock',
            'value' => '1',
        ],
    ],
]);
// Nội bộ: SELECT * FROM wp_posts
//   INNER JOIN wp_postmeta AS pm1 ON (wp_posts.ID = pm1.post_id AND pm1.meta_key = 'price')
//   INNER JOIN wp_postmeta AS pm2 ON (wp_posts.ID = pm2.post_id AND pm2.meta_key = 'in_stock')
//   WHERE post_type = 'product' AND CAST(pm1.meta_value AS SIGNED) > 200000 AND pm2.meta_value = '1'

// Laravel Eloquent tương đương:
Product::where('price', '>', 200000)
       ->where('in_stock', true)
       ->get();
// SQL: SELECT * FROM products WHERE price > 200000 AND in_stock = 1
```

---

## 19. Best Practices & Lưu Ý

### 1. Prefix Bảng

```php
// wp-config.php
$table_prefix = 'wp_';  // Nên đổi thành prefix ngẫu nhiên cho bảo mật
$table_prefix = 'x7k_'; // Ví dụ

// Trong code, LUÔN dùng $wpdb->prefix hoặc $wpdb->posts (KHÔNG hardcode 'wp_')
global $wpdb;
$table = $wpdb->prefix . 'my_custom_table';  // Đúng: x7k_my_custom_table
$table = 'wp_my_custom_table';                // SAI: hardcode prefix
```

### 2. Tạo Custom Table Đúng Cách

```php
function my_plugin_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'my_orders';

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL auto_increment,
        user_id bigint(20) unsigned NOT NULL default '0',
        product_id bigint(20) unsigned NOT NULL default '0',
        quantity int(11) NOT NULL default '1',
        total decimal(10,2) NOT NULL default '0.00',
        status varchar(20) NOT NULL default 'pending',
        created_at datetime NOT NULL default CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY product_id (product_id),
        KEY status (status)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'my_plugin_create_table' );
```

> **Lưu ý `dbDelta()`:**
> - Mỗi cột phải trên 1 dòng riêng
> - `PRIMARY KEY` phải có **2 dấu cách** trước `(` → `PRIMARY KEY  (id)`
> - KEY definition phải trên 1 dòng
> - Dùng `KEY` thay vì `INDEX`
> - Luôn include `$charset_collate`

### 3. Khi Nào Dùng Meta vs Custom Table

| Tiêu chí | Dùng Meta Table | Dùng Custom Table |
|-----------|----------------|-------------------|
| Số lượng fields | Ít (< 10) | Nhiều (> 10) |
| Cần query phức tạp | Không | Có |
| Cần JOIN nhiều | Không | Có |
| Data volume | Nhỏ-vừa | Lớn |
| Cần WP_Query integration | Có | Không nhất thiết |
| Cần type constraints | Không | Có |
| Ví dụ | Post custom fields, user profile | E-commerce orders, analytics logs |

### 4. Tối Ưu Performance

```php
// 1. Index cho meta_query phổ biến:
// Thêm index cho meta_value nếu query thường xuyên
$wpdb->query("ALTER TABLE {$wpdb->postmeta}
    ADD INDEX meta_value_index (meta_value(191))");

// 2. Object Cache cho queries lặp:
$result = wp_cache_get( 'my_query_result', 'my_plugin' );
if ( false === $result ) {
    $result = $wpdb->get_results( $query );
    wp_cache_set( 'my_query_result', $result, 'my_plugin', HOUR_IN_SECONDS );
}

// 3. Giảm autoload options:
update_option( 'my_large_option', $data, false ); // false = không autoload

// 4. Dọn dẹp transients định kỳ:
delete_expired_transients( true );

// 5. Dùng $wpdb->prepare() LUÔN cho input từ user:
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        $post_type,
        'publish'
    )
);
```

### 5. Tool Kiểm Tra Database

```bash
# WP-CLI: Kiểm tra kích thước bảng
wp db size --tables

# Kiểm tra và tối ưu bảng
wp db optimize

# Repair bảng
wp db repair

# Export/Import
wp db export backup.sql
wp db import backup.sql

# Search & Replace (đổi domain)
wp search-replace 'old-domain.com' 'new-domain.com' --dry-run
```

---

## Tham Khảo

- **Source code:** `wp-admin/includes/schema.php` - File gốc chứa toàn bộ schema
- **Hàm chính:** `wp_get_db_schema()` (dòng 36-344) - Trả về SQL CREATE TABLE
- **Default options:** `populate_options()` (dòng 361-708) - Options mặc định
- **Default roles:** `populate_roles()` (dòng 715-950) - Roles & capabilities
- **dbDelta:** `wp-admin/includes/upgrade.php` - Hàm tạo/update bảng
- **$wpdb class:** `wp-includes/class-wpdb.php` - Database abstraction layer

---

*Tài liệu phân tích từ WordPress source code. Cập nhật 02/2026.*
*Xem thêm: [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) cho hướng dẫn sử dụng $wpdb và WP_Query.*
