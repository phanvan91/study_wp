# 10 - Cài Đặt (Settings)

> **Source chính**: `wp-admin/options-general.php`, `wp-admin/options.php`, và 6 trang settings khác
> **Dành cho**: Laravel Developer muốn hiểu hệ thống Settings trong WordPress
> **Tương đương Laravel**: `.env` + `config/` + Database settings + FormRequest validation

---

## Mục Lục

1. [Tổng Quan Settings](#1-tổng-quan-settings)
2. [Settings API](#2-settings-api)
3. [General Settings](#3-general-settings)
4. [Writing Settings](#4-writing-settings)
5. [Reading Settings](#5-reading-settings)
6. [Discussion Settings](#6-discussion-settings)
7. [Media Settings](#7-media-settings)
8. [Permalink Settings](#8-permalink-settings)
9. [Privacy Settings](#9-privacy-settings)
10. [options.php - Settings Processor](#10-optionsphp---settings-processor)
11. [Tạo Settings Page Custom](#11-tạo-settings-page-custom)
12. [DB: Settings Lưu Gì?](#12-db-settings-lưu-gì)
13. [Hooks Settings](#13-hooks-settings)
14. [So Sánh Laravel](#14-so-sánh-laravel)

---

## 1. Tổng Quan Settings

### 7 Trang Settings Mặc Định

| STT | Trang | URL | Source File |
|-----|-------|-----|-------------|
| 1 | General | `/wp-admin/options-general.php` | `wp-admin/options-general.php` (~22KB) |
| 2 | Writing | `/wp-admin/options-writing.php` | `wp-admin/options-writing.php` (~9.1KB) |
| 3 | Reading | `/wp-admin/options-reading.php` | `wp-admin/options-reading.php` (~11KB) |
| 4 | Discussion | `/wp-admin/options-discussion.php` | `wp-admin/options-discussion.php` (~16KB) |
| 5 | Media | `/wp-admin/options-media.php` | `wp-admin/options-media.php` (~6.4KB) |
| 6 | Permalinks | `/wp-admin/options-permalink.php` | `wp-admin/options-permalink.php` (~22KB) |
| 7 | Privacy | `/wp-admin/options-privacy.php` | `wp-admin/options-privacy.php` (~10KB) |

### Đặc Điểm Chung

- Tất cả đều yêu cầu capability `manage_options`
- Tất cả form submit tới `options.php` (trừ Permalinks và Privacy có xử lý riêng)
- Tất cả dùng Settings API (`settings_fields()`, `do_settings_sections()`)
- Tất cả lưu vào bảng `wp_options`

### Menu Structure

```
Settings (options-general.php)
  ├── General (options-general.php)
  ├── Writing (options-writing.php)
  ├── Reading (options-reading.php)
  ├── Discussion (options-discussion.php)
  ├── Media (options-media.php)
  ├── Permalinks (options-permalink.php)
  └── Privacy (options-privacy.php)
```

### Source Processor

```php
// Source: /wp-admin/options.php (~14KB)
// File này xử lý TẤT CẢ form submissions từ các trang settings
// Nó:
// 1. Verify nonce
// 2. Kiểm tra capability
// 3. Validate allowed options (whitelist)
// 4. Sanitize mỗi option qua sanitize_callback
// 5. Gọi update_option() cho mỗi option
// 6. Redirect về trang settings với ?updated=true
```

---

## 2. Settings API

### Tổng Quan

WordPress Settings API là bộ hàm giúp đăng ký, validate và render các settings một cách chuẩn hóa. API này giúp:
- Tự động tạo form HTML
- Tự động verify nonce
- Tự động sanitize dữ liệu
- Tự động lưu vào wp_options
- Tích hợp với options.php processor

### Flow Hoạt Động

```
register_setting()         → Đăng ký option với group
add_settings_section()     → Tạo section trong trang
add_settings_field()       → Tạo field trong section
          ↓
settings_fields()          → Output hidden fields (nonce, action, option_page)
do_settings_sections()     → Render tất cả sections + fields
do_settings_fields()       → Render fields của 1 section
          ↓
Form submit → options.php
  → check_admin_referer()  → Verify nonce
  → Kiểm tra allowed_options whitelist
  → sanitize_callback()   → Sanitize giá trị
  → update_option()       → Lưu vào DB
  → wp_redirect() + ?updated=true
```

### register_setting()

```php
// Source: /wp-includes/option.php

register_setting( $option_group, $option_name, $args );

// $option_group: Tên group (dùng trong settings_fields())
// $option_name:  Tên option trong wp_options
// $args:         Array cấu hình

// Ví dụ:
register_setting( 'my_group', 'my_option', [
    'type'              => 'string',        // string, boolean, integer, number, array, object
    'description'       => 'Mô tả option',
    'sanitize_callback' => 'sanitize_text_field',  // Hàm sanitize
    'show_in_rest'      => false,           // Có hiện trong REST API không
    'default'           => '',              // Giá trị mặc định
]);

// Khi register_setting() được gọi:
// 1. Thêm option vào allowed_options whitelist cho group này
// 2. Đăng ký sanitize_callback vào filter 'sanitize_option_{$option_name}'
// 3. Nếu show_in_rest = true, register option cho REST API

// QUAN TRỌNG: register_setting() PHẢI gọi trong hook 'admin_init'
add_action( 'admin_init', function() {
    register_setting( 'my_group', 'my_option' );
});
```

### add_settings_section()

```php
add_settings_section(
    $id,        // ID unique cho section
    $title,     // Title hiển thị (h2)
    $callback,  // Callback render description dưới title
    $page       // Slug trang settings (dùng trong do_settings_sections())
);

// Ví dụ:
add_settings_section(
    'my_general_section',
    'Cài Đặt Chung',
    function() {
        echo '<p>Cấu hình các thiết lập chính cho plugin.</p>';
    },
    'my-settings-page'
);
```

### add_settings_field()

```php
add_settings_field(
    $id,        // ID unique cho field
    $title,     // Label hiển thị
    $callback,  // Callback render input HTML
    $page,      // Slug trang settings
    $section,   // ID section chứa field này
    $args       // Extra args truyền vào callback
);

// Ví dụ:
add_settings_field(
    'my_api_key',
    'API Key',
    function( $args ) {
        $value = get_option( 'my_option' );
        printf(
            '<input type="text" id="%s" name="my_option" value="%s" class="regular-text" />',
            esc_attr( $args['label_for'] ),
            esc_attr( $value )
        );
        echo '<p class="description">Nhập API key từ dashboard.</p>';
    },
    'my-settings-page',
    'my_general_section',
    [
        'label_for' => 'my_api_key', // Tự động thêm for="" vào label
    ]
);
```

### Render Functions

```php
// 1. settings_fields( $option_group )
// Output:
// - wp_nonce_field cho group
// - <input type="hidden" name="option_page" value="$option_group" />
// - <input type="hidden" name="action" value="update" />

// 2. do_settings_sections( $page )
// Render TẤT CẢ sections + fields đã đăng ký cho $page
// Output HTML: <table class="form-table">...</table> cho mỗi section

// 3. do_settings_fields( $page, $section )
// Render fields của 1 section cụ thể (ít dùng trực tiếp)

// Cấu trúc HTML output:
// <h2>Section Title</h2>
// <p>Section description callback</p>
// <table class="form-table" role="presentation">
//   <tr>
//     <th scope="row"><label for="field_id">Field Label</label></th>
//     <td><!-- Field callback output --></td>
//   </tr>
//   ...
// </table>
```

---

## 3. General Settings

### Source

```php
// Source: /wp-admin/options-general.php (~22KB)

require_once __DIR__ . '/admin.php';
require_once ABSPATH . 'wp-admin/includes/translation-install.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );
}

$title       = __( 'General Settings' );
$parent_file = 'options-general.php';
```

### Fields Chi Tiết

#### Site Title (blogname)

```php
// Option: blogname
// Type: string
// Mặc định: Tên set khi cài đặt WordPress
// Hiển thị: Title bar browser, header theme, RSS feeds
// Autoload: yes

<input name="blogname" type="text" id="blogname"
       value="<?php form_option( 'blogname' ); ?>"
       class="regular-text" />
```

#### Tagline (blogdescription)

```php
// Option: blogdescription
// Type: string
// Mặc định: "Just another WordPress site"
// Hiển thị: Dưới site title, meta description (tùy theme)
// Autoload: yes
```

#### WordPress Address URL (siteurl)

```php
// Option: siteurl
// Type: string
// CHÚ Ý: Thay đổi sai → website bị hỏng!
// Đây là URL nơi WordPress files được cài
// Ví dụ: https://example.com hoặc https://example.com/wordpress
// KHÔNG hiện trong multisite
// Có thể define trong wp-config.php: define('WP_SITEURL', '...');

// Nếu WP_SITEURL đã define → field bị disable (readonly)
if ( ! defined( 'WP_SITEURL' ) ) {
    $allowed_options['general'][] = 'siteurl';
}
```

#### Site Address URL (home)

```php
// Option: home
// Type: string
// URL trang chủ mà visitors truy cập
// Thường giống siteurl, nhưng có thể khác nếu WordPress cài trong subfolder
// Ví dụ: siteurl = https://example.com/wordpress
//         home    = https://example.com
// Có thể define: define('WP_HOME', '...');
```

#### Administration Email (admin_email)

```php
// Option: admin_email (thực tế lưu là new_admin_email khi thay đổi)
// Type: email
// ĐẶC BIỆT: Khi thay đổi email, WordPress gửi email xác nhận
// Email mới chỉ được cập nhật sau khi click link xác nhận

// Flow thay đổi admin email:
// 1. Nhập email mới → Submit form
// 2. WordPress lưu vào option 'new_admin_email' (tạm)
// 3. WordPress lưu hash vào option 'adminhash'
// 4. Gửi email xác nhận tới email mới
// 5. User click link → options.php?adminhash=xxx
// 6. WordPress verify hash → update option 'admin_email'
// 7. Xóa 'new_admin_email' và 'adminhash'

// Source: /wp-admin/options.php
if ( ! empty( $_GET['adminhash'] ) ) {
    $new_admin_details = get_option( 'adminhash' );
    if ( is_array( $new_admin_details )
        && hash_equals( $new_admin_details['hash'], $_GET['adminhash'] )
        && ! empty( $new_admin_details['newemail'] )
    ) {
        update_option( 'admin_email', $new_admin_details['newemail'] );
        delete_option( 'adminhash' );
        delete_option( 'new_admin_email' );
    }
}
```

#### Membership (users_can_register)

```php
// Option: users_can_register
// Type: boolean (0/1)
// Mặc định: 0 (tắt)
// Khi bật: hiện link "Register" trên wp-login.php
// KHÔNG hiện trong multisite (multisite quản lý riêng)
```

#### New User Default Role (default_role)

```php
// Option: default_role
// Type: string
// Mặc định: 'subscriber'
// Role gán cho user mới khi đăng ký
// Dropdown hiển thị tất cả editable roles
```

#### Site Language (WPLANG)

```php
// Option: WPLANG
// Type: string
// Mặc định: '' (English)
// Ví dụ: 'vi' cho tiếng Việt, 'ja' cho tiếng Nhật
// Khi thay đổi → WordPress download translation files (.mo, .po)
// Lưu trong wp-content/languages/
```

#### Timezone

```php
// Options: timezone_string HOẶC gmt_offset
// timezone_string: 'Asia/Ho_Chi_Minh', 'America/New_York', etc.
// gmt_offset: +7, -5, etc. (dùng khi không chọn city)

// Nếu chọn UTC+7 → gmt_offset = 7, timezone_string = ''
// Nếu chọn Asia/Ho_Chi_Minh → timezone_string = 'Asia/Ho_Chi_Minh', gmt_offset = 7

// WordPress ưu tiên timezone_string nếu có
```

#### Date Format (date_format)

```php
// Option: date_format
// Type: string (PHP date format)
// Mặc định: 'F j, Y' (January 1, 2024)
// Các lựa chọn:
// 'F j, Y'     → January 1, 2024
// 'Y-m-d'      → 2024-01-01
// 'm/d/Y'      → 01/01/2024
// 'd/m/Y'      → 01/01/2024
// Custom        → Nhập tự do
```

#### Time Format (time_format)

```php
// Option: time_format
// Type: string (PHP date format)
// Mặc định: 'g:i a' (1:30 pm)
// 'g:i a'  → 1:30 pm
// 'g:i A'  → 1:30 PM
// 'H:i'    → 13:30
```

#### Week Starts On (start_of_week)

```php
// Option: start_of_week
// Type: integer (0-6)
// 0 = Sunday, 1 = Monday, ..., 6 = Saturday
// Mặc định: 1 (Monday)
// Ảnh hưởng: Calendar widget, date pickers
```

---

## 4. Writing Settings

### Source

```php
// Source: /wp-admin/options-writing.php (~9.1KB)

require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( /* ... */ );
}

$title       = __( 'Writing Settings' );
$parent_file = 'options-general.php';
```

### Fields

#### Default Post Category (default_category)

```php
// Option: default_category
// Type: integer (term_id)
// Mặc định: 1 (Uncategorized)
// Category tự động gán cho bài viết mới nếu không chọn category nào
```

#### Default Post Format (default_post_format)

```php
// Option: default_post_format
// Type: string
// Mặc định: '' (Standard)
// Các format: aside, gallery, link, image, quote, status, video, audio, chat
// Chỉ hiện nếu theme hỗ trợ post formats (add_theme_support('post-formats'))
```

#### Post via Email (Legacy - đã ẩn từ WP 5.x)

```php
// Các options này vẫn tồn tại nhưng bị ẩn mặc định
// Có thể bật lại bằng filter:

// Source: /wp-admin/options.php
if ( apply_filters( 'enable_post_by_email_configuration', true ) ) {
    $allowed_options['writing'][] = 'mailserver_url';
    $allowed_options['writing'][] = 'mailserver_port';
    $allowed_options['writing'][] = 'mailserver_login';
    $allowed_options['writing'][] = 'mailserver_pass';
}

// Options:
// mailserver_url   → Mail server hostname (POP3)
// mailserver_port  → Port (mặc định: 110)
// mailserver_login → Username
// mailserver_pass  → Password
// default_email_category → Category cho posts via email
```

#### Update Services (ping_sites)

```php
// Option: ping_sites
// Type: string (URLs, mỗi URL trên 1 dòng)
// Mặc định: 'http://rpc.pingomatic.com/'
// WordPress ping các services này khi publish bài mới
// Chỉ hiện khi blog_public = 1 (không chặn search engines)

// Có thể tắt bằng filter:
add_filter( 'enable_update_services_configuration', '__return_false' );
```

---

## 5. Reading Settings

### Source

```php
// Source: /wp-admin/options-reading.php (~11KB)

require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( /* ... */ );
}

$title       = __( 'Reading Settings' );
$parent_file = 'options-general.php';
```

### Fields

#### Your Homepage Displays (show_on_front)

```php
// Option: show_on_front
// Type: string
// Giá trị: 'posts' (blog mặc định) hoặc 'page' (static page)

// Khi chọn 'page', cần thêm 2 options:
// page_on_front  → ID page làm trang chủ
// page_for_posts → ID page hiển thị blog posts

// Ví dụ:
// show_on_front  = 'page'
// page_on_front  = 10  (Page "Trang Chủ")
// page_for_posts = 15  (Page "Blog")

// Nếu chưa có pages nào → chỉ hiện option 'posts'
if ( ! get_pages() ) :
    // Ẩn phần chọn static page
    echo '<input name="show_on_front" type="hidden" value="posts" />';
endif;
```

#### Blog Pages Show At Most (posts_per_page)

```php
// Option: posts_per_page
// Type: integer
// Mặc định: 10
// Số bài viết hiển thị trên mỗi trang blog
// Ảnh hưởng: main query, archive pages, search results
// Tương đương: ->paginate(10) trong Laravel
```

#### Syndication Feeds (posts_per_rss)

```php
// Option: posts_per_rss
// Type: integer
// Mặc định: 10
// Số bài viết trong RSS feeds (/feed/)
```

#### Feed Content (rss_use_excerpt)

```php
// Option: rss_use_excerpt
// Type: boolean (0/1)
// 0 = Full text trong feed
// 1 = Chỉ excerpt trong feed
```

#### Search Engine Visibility (blog_public)

```php
// Option: blog_public
// Type: boolean (0/1)
// 1 = Cho phép search engines index (mặc định)
// 0 = Discourage search engines
// Khi 0: thêm <meta name="robots" content="noindex, nofollow" /> vào HTML
// VÀ thêm Disallow: / vào robots.txt
// CHÚ Ý: Đây chỉ là "discourage", không phải block hoàn toàn!

// Khi blog_public = 0, Dashboard hiện cảnh báo:
// "Search engines discouraged"
```

---

## 6. Discussion Settings

### Source

```php
// Source: /wp-admin/options-discussion.php (~16KB)
// Trang settings nhiều options nhất!

require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( /* ... */ );
}

$title       = __( 'Discussion Settings' );
$parent_file = 'options-general.php';
```

### Fields Chi Tiết

#### Default Post Settings

```php
// default_pingback_flag (checkbox)
// Mặc định: 1
// Tự động gửi pingback khi bài viết link tới blog khác

// default_ping_status (checkbox, value='open')
// Mặc định: 'open'
// Cho phép nhận pingbacks/trackbacks từ blog khác

// default_comment_status (checkbox, value='open')
// Mặc định: 'open'
// Cho phép bình luận trên bài viết mới
// LƯU Ý: Chỉ ảnh hưởng bài viết MỚI, không thay đổi bài cũ
```

#### Other Comment Settings

```php
// require_name_email (checkbox)
// Mặc định: 1
// Bắt buộc name và email khi comment (anonymous user)

// comment_registration (checkbox)
// Mặc định: 0
// Phải đăng ký/đăng nhập để comment

// close_comments_for_old_posts (checkbox) + close_comments_days_old (number)
// Mặc định: 0 (tắt), 14 ngày
// Tự động đóng comments cho bài viết cũ hơn X ngày

// show_comments_cookies_opt_in (checkbox)
// Mặc định: 1
// Hiện checkbox "Save my name, email, and website" trong comment form
// Liên quan GDPR compliance

// thread_comments (checkbox) + thread_comments_depth (dropdown)
// Mặc định: 1 (bật), depth 5
// Cho phép reply comments (nested comments)
// Depth: 2-10 levels

// page_comments (checkbox) + comments_per_page + default_comments_page + comment_order
// Mặc định: 0 (tắt), 50, 'newest', 'asc'
// Phân trang comments
// comments_per_page: số comments mỗi trang
// default_comments_page: 'newest' hoặc 'oldest' (trang nào hiện đầu tiên)
// comment_order: 'asc' hoặc 'desc'
```

#### Email Me Whenever

```php
// comments_notify (checkbox)
// Mặc định: 1
// Gửi email khi có comment mới

// moderation_notify (checkbox)
// Mặc định: 1
// Gửi email khi comment bị held for moderation
```

#### Before A Comment Appears

```php
// comment_moderation (checkbox)
// Mặc định: 0
// TẤT CẢ comments phải được approve thủ công
// Nếu bật: comment mới → pending, admin phải approve

// comment_previously_approved (checkbox)
// Mặc định: 1
// Author phải có ít nhất 1 comment đã approved trước đó
// Comment đầu tiên của mỗi người → pending
```

#### Comment Moderation

```php
// comment_max_links (number)
// Mặc định: 2
// Comment chứa >= X links → tự động held for moderation
// Spam thường chứa nhiều links

// moderation_keys (textarea)
// Mặc định: '' (trống)
// Danh sách từ/cụm từ, mỗi dòng 1 từ
// Nếu comment chứa từ này → held for moderation
// Kiểm tra trong: content, author name, URL, email, IP, user agent
```

#### Disallowed Comment Keys

```php
// disallowed_keys (textarea)
// Mặc định: '' (trống)
// Giống moderation_keys nhưng MẠNH hơn:
// Nếu comment chứa từ này → tự động đánh dấu SPAM (trash)
// Trước WP 5.5 option này tên là 'blacklist_keys'
```

#### Avatars

```php
// show_avatars (checkbox)
// Mặc định: 1
// Hiện avatar (Gravatar) bên cạnh comments

// avatar_rating (radio)
// Mặc định: 'G'
// Maximum rating cho Gravatar: G, PG, R, X
// G = Phù hợp mọi lứa tuổi (mặc định)

// avatar_default (radio)
// Mặc định: 'mystery'
// Avatar cho user không có Gravatar:
// - mystery    → Mystery Person (hình người ẩn danh)
// - blank      → Blank (trống)
// - gravatar_default → Gravatar Logo
// - identicon  → Identicon (generated geometric pattern)
// - wavatar    → Wavatar (generated face)
// - monsterid  → MonsterID (generated monster)
// - retro      → Retro (8-bit style)
// - robohash   → RoboHash (generated robot)
```

---

## 7. Media Settings

### Source

```php
// Source: /wp-admin/options-media.php (~6.4KB)

require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( /* ... */ );
}

$title       = __( 'Media Settings' );
$parent_file = 'options-general.php';
```

### Fields

#### Image Sizes

```php
// Thumbnail size
// thumbnail_size_w → width (mặc định: 150)
// thumbnail_size_h → height (mặc định: 150)
// thumbnail_crop   → crop to exact dimensions (mặc định: 1 = có crop)

// Medium size
// medium_size_w → width (mặc định: 300)
// medium_size_h → height (mặc định: 300)
// KHÔNG crop, chỉ scale proportional

// Large size
// large_size_w → width (mặc định: 1024)
// large_size_h → height (mặc định: 1024)
// KHÔNG crop, chỉ scale proportional

// LƯU Ý: Thay đổi sizes KHÔNG ảnh hưởng ảnh đã upload
// Cần dùng plugin Regenerate Thumbnails để tạo lại
```

#### Uploading Files

```php
// uploads_use_yearmonth_folders (checkbox)
// Mặc định: 1 (có)
// Organize uploads vào thư mục theo năm/tháng
// VD: wp-content/uploads/2024/01/image.jpg
// Nếu tắt: wp-content/uploads/image.jpg

// upload_path (hidden field, thường trống)
// Mặc định: '' (= wp-content/uploads)
// Custom upload path (relative to ABSPATH)
// Chỉ hiện khi đã có giá trị khác mặc định

// upload_url_path (hidden field, thường trống)
// Mặc định: '' (= site_url/wp-content/uploads)
// Custom URL cho uploads (dùng khi có CDN)
// Chỉ hiện khi đã có giá trị khác mặc định
```

### Image Sizes Trong Code

```php
// WordPress tạo nhiều kích thước khi upload ảnh:

// Sizes mặc định:
// - thumbnail:    150x150 (cropped)
// - medium:       300x300 (proportional)
// - medium_large: 768x0 (proportional, ẩn trong settings)
// - large:        1024x1024 (proportional)
// - full:         Original size
// - 1536x1536:    (từ WP 5.3)
// - 2048x2048:    (từ WP 5.3)

// Thêm custom size:
add_image_size( 'product-thumb', 400, 400, true );  // Cropped
add_image_size( 'hero-banner', 1920, 600, true );   // Cropped
add_image_size( 'sidebar-image', 300, 0 );           // Proportional (height auto)

// Lấy URL ảnh theo size:
$url = wp_get_attachment_image_url( $attachment_id, 'product-thumb' );

// Responsive images (srcset):
// WordPress tự động tạo srcset attribute với tất cả sizes có cùng aspect ratio
echo wp_get_attachment_image( $attachment_id, 'large' );
// Output: <img src="image-1024x768.jpg"
//              srcset="image-300x225.jpg 300w, image-768x576.jpg 768w, image-1024x768.jpg 1024w"
//              sizes="(max-width: 1024px) 100vw, 1024px"
//              alt="..." />
```

---

## 8. Permalink Settings

### Source

```php
// Source: /wp-admin/options-permalink.php (~22KB)
// Đây là 1 trong 2 trang settings lớn nhất (cùng General)

require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( /* ... */ );
}

$title       = __( 'Permalink Settings' );
$parent_file = 'options-general.php';

$home_path           = get_home_path();
$iis7_permalinks     = iis7_supports_permalinks();
$permalink_structure = get_option( 'permalink_structure' );
$category_base       = get_option( 'category_base' );
$tag_base            = get_option( 'tag_base' );
```

### Permalink Structures

| Tên | Structure | URL ví dụ |
|-----|-----------|-----------|
| Plain | (trống) | `?p=123` |
| Day and name | `/%year%/%monthnum%/%day%/%postname%/` | `/2024/01/15/hello-world/` |
| Month and name | `/%year%/%monthnum%/%postname%/` | `/2024/01/hello-world/` |
| Numeric | `/archives/%post_id%` | `/archives/123` |
| Post name | `/%postname%/` | `/hello-world/` (RECOMMENDED) |
| Custom Structure | User defined | Tùy ý |

### Structure Tags

```php
// Tags có thể dùng trong permalink structure:
%year%       → Năm (4 chữ số): 2024
%monthnum%   → Tháng (2 chữ số): 01
%day%        → Ngày (2 chữ số): 15
%hour%       → Giờ (2 chữ số): 14
%minute%     → Phút (2 chữ số): 30
%second%     → Giây (2 chữ số): 00
%post_id%    → Post ID: 123
%postname%   → Post slug: hello-world
%category%   → Category slug: tin-tuc
%author%     → Author slug: admin
```

### Optional Settings

```php
// Category base (category_base)
// Mặc định: 'category'
// URL: example.com/category/tin-tuc/
// Đổi thành 'danh-muc' → example.com/danh-muc/tin-tuc/
// Để trống → dùng mặc định 'category'

// Tag base (tag_base)
// Mặc định: 'tag'
// URL: example.com/tag/wordpress/
// Đổi thành 'the' → example.com/the/wordpress/
```

### Khi Save Permalinks

```php
// Source: /wp-admin/options-permalink.php

// Khi nhấn Save Changes:
// 1. Cập nhật permalink_structure trong wp_options
// 2. Cập nhật category_base, tag_base
// 3. Gọi flush_rewrite_rules()

// flush_rewrite_rules() thực hiện:
// a. Xóa option 'rewrite_rules' trong wp_options
// b. Tạo lại tất cả rewrite rules
// c. Lưu lại vào 'rewrite_rules' (serialized, có thể rất lớn!)
// d. Kiểm tra và update .htaccess (Apache)

// Trên Apache: tạo/update file .htaccess
// Source: /wp-admin/options-permalink.php
if ( $iis7_permalinks ) {
    // IIS 7+: update web.config
} elseif ( ! $is_nginx ) {
    // Apache: update .htaccess
    $htaccess_content = "# BEGIN WordPress\n" .
        "<IfModule mod_rewrite.c>\n" .
        "RewriteEngine On\n" .
        "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n" .
        "RewriteBase /\n" .
        "RewriteRule ^index\.php$ - [L]\n" .
        "RewriteCond %{REQUEST_FILENAME} !-f\n" .
        "RewriteCond %{REQUEST_FILENAME} !-d\n" .
        "RewriteRule . /index.php [L]\n" .
        "</IfModule>\n" .
        "# END WordPress";
}

// Trên Nginx: KHÔNG tự update
// Admin phải tự cấu hình nginx config:
// location / {
//     try_files $uri $uri/ /index.php?$args;
// }
```

### Rewrite Rules Trong DB

```php
// Option: rewrite_rules
// Lưu serialized array, có thể rất lớn (hàng trăm rules)

// Ví dụ một phần rewrite_rules:
[
    'category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?category_name=$matches[1]&feed=$matches[2]',
    'category/(.+?)/?$' => 'index.php?category_name=$matches[1]',
    '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)(?:/([0-9]+))?/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5]',
    '(.?.+?)(?:/([0-9]+))?/?$' => 'index.php?pagename=$matches[1]&page=$matches[2]',
    // ... hàng trăm rules khác
]

// LƯU Ý CHO DEVELOPER:
// KHÔNG gọi flush_rewrite_rules() trong mỗi request!
// Chỉ gọi trong activation hook hoặc khi save settings
// Vì nó rất tốn tài nguyên (query DB, generate rules, write .htaccess)

// ĐÚNG:
register_activation_hook( __FILE__, function() {
    // Đăng ký CPT/taxonomy trước
    my_register_post_types();
    // Sau đó flush
    flush_rewrite_rules();
});

// SAI (gây chậm mọi request):
add_action( 'init', function() {
    flush_rewrite_rules(); // ĐỪNG LÀM THẾ NÀY!
});
```

### Custom Permalink Cho Post Types

```php
// Khi đăng ký Custom Post Type, có thể set permalink structure:

register_post_type( 'product', [
    'public'    => true,
    'label'     => 'Sản Phẩm',
    'rewrite'   => [
        'slug'       => 'san-pham',      // URL prefix: /san-pham/product-name/
        'with_front' => false,           // Không thêm blog prefix
        'feeds'      => true,            // Có RSS feed
        'pages'      => true,            // Có pagination
    ],
    'has_archive' => 'san-pham',         // Archive page: /san-pham/
]);

// Custom taxonomy permalink:
register_taxonomy( 'product_cat', 'product', [
    'rewrite' => [
        'slug'         => 'danh-muc-san-pham',
        'with_front'   => false,
        'hierarchical' => true,
    ],
]);
```

---

## 9. Privacy Settings

### Source

```php
// Source: /wp-admin/options-privacy.php (~10KB)

require_once __DIR__ . '/admin.php';

// Capability khác với các settings page khác!
if ( ! current_user_can( 'manage_privacy_options' ) ) {
    wp_die( __( 'Sorry, you are not allowed to manage privacy options on this site.' ) );
}

// 2 tabs
if ( isset( $_GET['tab'] ) && 'policyguide' === $_GET['tab'] ) {
    require_once __DIR__ . '/privacy-policy-guide.php';
    return;
}

$title = __( 'Privacy' );
```

### Tab 1: Settings

```php
// Option: wp_page_for_privacy_policy
// Type: integer (page ID)
// Chọn trang Privacy Policy từ dropdown pages
// Hoặc nút "Create Privacy Policy Page" để tạo mới

// Khi tạo mới:
$privacy_policy_page_id = wp_insert_post([
    'post_title'   => __( 'Privacy Policy' ),
    'post_status'  => 'draft',
    'post_type'    => 'page',
    'post_content' => WP_Privacy_Policy_Content::get_default_content(),
], true );

if ( ! is_wp_error( $privacy_policy_page_id ) ) {
    update_option( 'wp_page_for_privacy_policy', $privacy_policy_page_id );
}

// Khi chọn page đã có:
if ( 'set-privacy-page' === $action ) {
    $privacy_policy_page_id = isset( $_POST['page_for_privacy_policy'] )
        ? (int) $_POST['page_for_privacy_policy']
        : 0;
    update_option( 'wp_page_for_privacy_policy', $privacy_policy_page_id );
}
```

### Tab 2: Policy Guide

```php
// Hiển thị template nội dung privacy policy
// Gom gợi ý từ WordPress core + plugins đã đăng ký

// WordPress core cung cấp template mặc định về:
// - Ai chúng tôi là
// - Dữ liệu cá nhân chúng tôi thu thập
// - Cookies
// - Ai chúng tôi chia sẻ dữ liệu
// - Chúng tôi lưu dữ liệu bao lâu
// - Quyền của bạn với dữ liệu
```

### Đăng Ký Privacy Policy Content Từ Plugin

```php
add_action( 'admin_init', function() {
    // Thêm nội dung vào Privacy Policy Guide
    wp_add_privacy_policy_content(
        'My Plugin',                        // Tên plugin
        '<h2>Dữ liệu My Plugin thu thập</h2>' .
        '<p>Plugin thu thập các dữ liệu sau:</p>' .
        '<ul>' .
        '<li><strong>Thông tin đơn hàng:</strong> Tên, email, địa chỉ, số điện thoại.</li>' .
        '<li><strong>Thông tin thanh toán:</strong> 4 số cuối thẻ (KHÔNG lưu full card number).</li>' .
        '<li><strong>Cookies:</strong> Plugin sử dụng cookie "my_cart" để lưu giỏ hàng.</li>' .
        '</ul>' .
        '<h2>Thời gian lưu trữ</h2>' .
        '<p>Dữ liệu đơn hàng được lưu trong 5 năm theo quy định thuế.</p>' .
        '<h2>Chia sẻ dữ liệu</h2>' .
        '<p>Thông tin đơn hàng được chia sẻ với đơn vị vận chuyển để giao hàng.</p>'
    );
});
```

---

## 10. options.php - Settings Processor

### Source & Flow Chi Tiết

```php
// Source: /wp-admin/options.php (~14KB)
// File này là "heart" của hệ thống Settings

require_once __DIR__ . '/admin.php';

$action      = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
$option_page = ! empty( $_REQUEST['option_page'] ) ? sanitize_text_field( $_REQUEST['option_page'] ) : '';

// === Bước 1: Xác định capability ===
$capability = 'manage_options'; // Mặc định

// Plugin có thể thay đổi capability cho settings page riêng
$capability = apply_filters( "option_page_capability_{$option_page}", $capability );

if ( ! current_user_can( $capability ) ) {
    wp_die( /* ... */ );
}
```

### Allowed Options (Whitelist)

```php
// === Bước 2: Định nghĩa allowed options ===
// Source: /wp-admin/options.php

$allowed_options = array(
    'general'    => array(
        'blogname',
        'blogdescription',
        'site_icon',
        'gmt_offset',
        'date_format',
        'time_format',
        'start_of_week',
        'timezone_string',
        'WPLANG',
        'new_admin_email',
    ),
    'discussion' => array(
        'default_pingback_flag',
        'default_ping_status',
        'default_comment_status',
        'comments_notify',
        'moderation_notify',
        'comment_moderation',
        'require_name_email',
        'comment_previously_approved',
        'comment_max_links',
        'moderation_keys',
        'disallowed_keys',
        'show_avatars',
        'avatar_rating',
        'avatar_default',
        'close_comments_for_old_posts',
        'close_comments_days_old',
        'thread_comments',
        'thread_comments_depth',
        'page_comments',
        'comments_per_page',
        'default_comments_page',
        'comment_order',
        'comment_registration',
        'show_comments_cookies_opt_in',
    ),
    'media'      => array(
        'thumbnail_size_w',
        'thumbnail_size_h',
        'thumbnail_crop',
        'medium_size_w',
        'medium_size_h',
        'large_size_w',
        'large_size_h',
        'image_default_size',
        'image_default_align',
        'image_default_link_type',
    ),
    'reading'    => array(
        'posts_per_page',
        'posts_per_rss',
        'rss_use_excerpt',
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'blog_public',
    ),
    'writing'    => array(
        'default_category',
        'default_email_category',
        'default_link_category',
        'default_post_format',
    ),
);

// Thêm conditional options:
if ( ! is_multisite() ) {
    if ( ! defined( 'WP_SITEURL' ) ) {
        $allowed_options['general'][] = 'siteurl';
    }
    if ( ! defined( 'WP_HOME' ) ) {
        $allowed_options['general'][] = 'home';
    }
    $allowed_options['general'][] = 'users_can_register';
    $allowed_options['general'][] = 'default_role';

    if ( '1' === get_option( 'blog_public' ) ) {
        $allowed_options['writing'][] = 'ping_sites';
    }

    $allowed_options['media'][] = 'uploads_use_yearmonth_folders';
}

// Post-by-email options
if ( apply_filters( 'enable_post_by_email_configuration', true ) ) {
    $allowed_options['writing'][] = 'mailserver_url';
    $allowed_options['writing'][] = 'mailserver_port';
    $allowed_options['writing'][] = 'mailserver_login';
    $allowed_options['writing'][] = 'mailserver_pass';
}

// Filter cho plugins thêm options
$allowed_options = apply_filters( 'allowed_options', $allowed_options );
// Deprecated filter (vẫn hoạt động):
// $allowed_options = apply_filters( 'whitelist_options', $allowed_options );
```

### Xử Lý Update

```php
// === Bước 3: Xử lý save ===
if ( 'update' === $action ) {
    // Verify nonce
    check_admin_referer( $option_page . '-options' );

    // Kiểm tra option_page có trong whitelist
    if ( ! isset( $allowed_options[ $option_page ] ) ) {
        wp_die( __( '<strong>Error:</strong> Options page not found.' ) );
    }

    // Lấy danh sách options được phép save
    $options = $allowed_options[ $option_page ];

    // Với mỗi option:
    foreach ( $options as $option ) {
        // Lấy giá trị từ POST
        if ( isset( $_POST[ $option ] ) ) {
            $value = $_POST[ $option ];
            if ( ! is_array( $value ) ) {
                $value = trim( $value );
            }
            $value = wp_unslash( $value );
        } else {
            $value = null;
        }

        // Sanitize qua filter
        // Mỗi option có thể có sanitize_callback đăng ký qua register_setting()
        // WordPress tự động gọi: apply_filters( 'sanitize_option_{$option}', $value )

        // Update option trong database
        update_option( $option, $value );
    }

    // Redirect về trang settings
    $goback = add_query_arg( 'updated', 'true', wp_get_referer() );
    wp_redirect( $goback );
    exit;
}
```

---

## 11. Tạo Settings Page Custom

### Full Working Example - Plugin Settings

```php
<?php
/**
 * Plugin Name: My Plugin Settings Example
 * Description: Ví dụ tạo settings page hoàn chỉnh
 */

class My_Plugin_Settings {

    /**
     * Option name trong wp_options
     * Lưu dạng serialized array (nhiều settings trong 1 option)
     */
    private $option_name = 'my_plugin_options';

    /**
     * Constructor - đăng ký hooks
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Thêm menu item dưới Settings
     */
    public function add_menu() {
        add_options_page(
            'My Plugin Settings',       // Page title (HTML <title>)
            'My Plugin',                // Menu title
            'manage_options',           // Capability
            'my-plugin-settings',       // Menu slug
            [ $this, 'render_page' ]    // Callback render
        );
    }

    /**
     * Đăng ký settings, sections, fields
     */
    public function register_settings() {
        // Đăng ký 1 option chứa tất cả settings
        register_setting(
            'my_plugin_group',          // Option group
            $this->option_name,         // Option name
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize' ],
                'default'           => $this->get_defaults(),
            ]
        );

        // === Section: General ===
        add_settings_section(
            'general',
            'Cài Đặt Chung',
            function() {
                echo '<p>Cấu hình cơ bản cho plugin.</p>';
            },
            'my-plugin-settings'
        );

        // Field: API Key
        add_settings_field(
            'api_key',
            'API Key',
            [ $this, 'render_text_field' ],
            'my-plugin-settings',
            'general',
            [
                'label_for'   => 'api_key',
                'field'       => 'api_key',
                'description' => 'Nhập API key từ dashboard. <a href="https://example.com/api" target="_blank">Lấy key tại đây</a>.',
                'class'       => 'regular-text',
            ]
        );

        // Field: Enabled
        add_settings_field(
            'enabled',
            'Kích Hoạt Plugin',
            [ $this, 'render_checkbox_field' ],
            'my-plugin-settings',
            'general',
            [
                'field'       => 'enabled',
                'description' => 'Bật/tắt tính năng chính của plugin.',
            ]
        );

        // Field: Mode
        add_settings_field(
            'mode',
            'Chế Độ Hoạt Động',
            [ $this, 'render_select_field' ],
            'my-plugin-settings',
            'general',
            [
                'label_for' => 'mode',
                'field'     => 'mode',
                'options'   => [
                    'sandbox'    => 'Sandbox (Test)',
                    'production' => 'Production (Live)',
                ],
            ]
        );

        // === Section: Display ===
        add_settings_section(
            'display',
            'Cài Đặt Hiển Thị',
            function() {
                echo '<p>Tùy chỉnh cách hiển thị trên frontend.</p>';
            },
            'my-plugin-settings'
        );

        // Field: Items per page
        add_settings_field(
            'items_per_page',
            'Số items mỗi trang',
            [ $this, 'render_number_field' ],
            'my-plugin-settings',
            'display',
            [
                'label_for' => 'items_per_page',
                'field'     => 'items_per_page',
                'min'       => 1,
                'max'       => 100,
            ]
        );

        // Field: Custom CSS
        add_settings_field(
            'custom_css',
            'CSS Tùy Chỉnh',
            [ $this, 'render_textarea_field' ],
            'my-plugin-settings',
            'display',
            [
                'field'       => 'custom_css',
                'rows'        => 8,
                'description' => 'Thêm CSS custom. Ví dụ: .my-plugin-wrapper { color: red; }',
            ]
        );

        // === Section: Advanced ===
        add_settings_section(
            'advanced',
            'Cài Đặt Nâng Cao',
            function() {
                echo '<p class="description" style="color: #d63638;">Cẩn thận khi thay đổi các settings này.</p>';
            },
            'my-plugin-settings'
        );

        // Field: Debug Mode
        add_settings_field(
            'debug',
            'Debug Mode',
            [ $this, 'render_checkbox_field' ],
            'my-plugin-settings',
            'advanced',
            [
                'field'       => 'debug',
                'description' => 'Bật logging chi tiết. KHÔNG nên bật trên production.',
            ]
        );

        // Field: Cache TTL
        add_settings_field(
            'cache_ttl',
            'Cache TTL (giây)',
            [ $this, 'render_number_field' ],
            'my-plugin-settings',
            'advanced',
            [
                'label_for'   => 'cache_ttl',
                'field'       => 'cache_ttl',
                'min'         => 0,
                'max'         => 86400,
                'description' => '0 = tắt cache. Mặc định: 3600 giây (1 giờ).',
            ]
        );
    }

    /**
     * Giá trị mặc định
     */
    private function get_defaults() {
        return [
            'api_key'        => '',
            'enabled'        => 0,
            'mode'           => 'sandbox',
            'items_per_page' => 12,
            'custom_css'     => '',
            'debug'          => 0,
            'cache_ttl'      => 3600,
        ];
    }

    /**
     * Lấy options (merge với defaults)
     */
    private function get_options() {
        return wp_parse_args(
            get_option( $this->option_name, [] ),
            $this->get_defaults()
        );
    }

    /**
     * Sanitize callback
     */
    public function sanitize( $input ) {
        $output = [];

        $output['api_key']        = sanitize_text_field( $input['api_key'] ?? '' );
        $output['enabled']        = isset( $input['enabled'] ) ? 1 : 0;
        $output['mode']           = in_array( $input['mode'] ?? '', [ 'sandbox', 'production' ] )
                                    ? $input['mode'] : 'sandbox';
        $output['items_per_page'] = absint( $input['items_per_page'] ?? 12 );
        $output['items_per_page'] = max( 1, min( 100, $output['items_per_page'] ) );
        $output['custom_css']     = wp_strip_all_tags( $input['custom_css'] ?? '' );
        $output['debug']          = isset( $input['debug'] ) ? 1 : 0;
        $output['cache_ttl']      = absint( $input['cache_ttl'] ?? 3600 );
        $output['cache_ttl']      = min( 86400, $output['cache_ttl'] );

        // Validation: nếu mode = production và enabled = 1, phải có API key
        if ( 'production' === $output['mode'] && $output['enabled'] && empty( $output['api_key'] ) ) {
            add_settings_error(
                $this->option_name,
                'api_key_required',
                'API Key bắt buộc khi chạy ở chế độ Production.',
                'error'
            );
            // Giữ nguyên mode cũ
            $old = get_option( $this->option_name );
            $output['mode'] = $old['mode'] ?? 'sandbox';
        }

        return $output;
    }

    /**
     * Render text input
     */
    public function render_text_field( $args ) {
        $options = $this->get_options();
        $field   = $args['field'];
        $value   = $options[ $field ] ?? '';
        $class   = $args['class'] ?? 'regular-text';

        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" class="%s" />',
            esc_attr( $args['label_for'] ?? $field ),
            esc_attr( $this->option_name ),
            esc_attr( $field ),
            esc_attr( $value ),
            esc_attr( $class )
        );

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', $args['description'] );
        }
    }

    /**
     * Render checkbox
     */
    public function render_checkbox_field( $args ) {
        $options = $this->get_options();
        $field   = $args['field'];
        $value   = $options[ $field ] ?? 0;

        printf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s />',
            esc_attr( $field ),
            esc_attr( $this->option_name ),
            esc_attr( $field ),
            checked( 1, $value, false )
        );

        if ( ! empty( $args['description'] ) ) {
            printf(
                ' <label for="%s">%s</label>',
                esc_attr( $field ),
                esc_html( $args['description'] )
            );
        }
    }

    /**
     * Render select dropdown
     */
    public function render_select_field( $args ) {
        $options      = $this->get_options();
        $field        = $args['field'];
        $current      = $options[ $field ] ?? '';
        $field_options = $args['options'] ?? [];

        printf(
            '<select id="%s" name="%s[%s]">',
            esc_attr( $args['label_for'] ?? $field ),
            esc_attr( $this->option_name ),
            esc_attr( $field )
        );

        foreach ( $field_options as $value => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $value ),
                selected( $current, $value, false ),
                esc_html( $label )
            );
        }

        echo '</select>';
    }

    /**
     * Render number input
     */
    public function render_number_field( $args ) {
        $options = $this->get_options();
        $field   = $args['field'];
        $value   = $options[ $field ] ?? '';

        printf(
            '<input type="number" id="%s" name="%s[%s]" value="%s" min="%s" max="%s" class="small-text" />',
            esc_attr( $args['label_for'] ?? $field ),
            esc_attr( $this->option_name ),
            esc_attr( $field ),
            esc_attr( $value ),
            esc_attr( $args['min'] ?? 0 ),
            esc_attr( $args['max'] ?? '' )
        );

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render textarea
     */
    public function render_textarea_field( $args ) {
        $options = $this->get_options();
        $field   = $args['field'];
        $value   = $options[ $field ] ?? '';
        $rows    = $args['rows'] ?? 5;

        printf(
            '<textarea id="%s" name="%s[%s]" rows="%d" class="large-text code">%s</textarea>',
            esc_attr( $field ),
            esc_attr( $this->option_name ),
            esc_attr( $field ),
            absint( $rows ),
            esc_textarea( $value )
        );

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render settings page
     */
    public function render_page() {
        // Kiểm tra quyền
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Hiển thị errors/notices
        settings_errors( $this->option_name );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options.php">
                <?php
                // Output hidden fields (nonce, action, option_page)
                settings_fields( 'my_plugin_group' );

                // Render tất cả sections + fields
                do_settings_sections( 'my-plugin-settings' );

                // Nút Save
                submit_button( 'Lưu Cài Đặt' );
                ?>
            </form>
        </div>
        <?php
    }
}

// Khởi tạo
new My_Plugin_Settings();
```

### Settings Page Với Tabs

```php
// Nhiều plugins cần settings page phức tạp với tabs

class My_Plugin_Tabbed_Settings {

    private $tabs = [
        'general'  => 'Chung',
        'display'  => 'Hiển Thị',
        'advanced' => 'Nâng Cao',
    ];

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu() {
        // Thêm top-level menu thay vì sub-menu của Settings
        add_menu_page(
            'My Plugin',
            'My Plugin',
            'manage_options',
            'my-plugin',
            [ $this, 'render_page' ],
            'dashicons-admin-generic',
            80
        );
    }

    public function render_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

        if ( ! array_key_exists( $active_tab, $this->tabs ) ) {
            $active_tab = 'general';
        }
        ?>
        <div class="wrap">
            <h1>My Plugin Settings</h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $this->tabs as $tab_key => $tab_label ) : ?>
                    <a href="<?php echo admin_url( 'admin.php?page=my-plugin&tab=' . $tab_key ); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'my_plugin_' . $active_tab );
                do_settings_sections( 'my-plugin-' . $active_tab );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        // Tab: General
        register_setting( 'my_plugin_general', 'my_plugin_general_options' );
        add_settings_section( 'default', '', null, 'my-plugin-general' );
        // ... add_settings_field() cho tab general

        // Tab: Display
        register_setting( 'my_plugin_display', 'my_plugin_display_options' );
        add_settings_section( 'default', '', null, 'my-plugin-display' );
        // ... add_settings_field() cho tab display

        // Tab: Advanced
        register_setting( 'my_plugin_advanced', 'my_plugin_advanced_options' );
        add_settings_section( 'default', '', null, 'my-plugin-advanced' );
        // ... add_settings_field() cho tab advanced
    }
}
```

---

## 12. DB: Settings Lưu Gì?

### Bảng wp_options

```sql
CREATE TABLE wp_options (
    option_id    bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    option_name  varchar(191) NOT NULL DEFAULT '',
    option_value longtext NOT NULL,
    autoload     varchar(20) NOT NULL DEFAULT 'yes',
    PRIMARY KEY (option_id),
    UNIQUE KEY option_name (option_name),
    KEY autoload (autoload)
);
```

### Autoload

```php
// autoload = 'yes' → Option được load vào memory mỗi request
// autoload = 'no'  → Chỉ load khi get_option() được gọi

// Tất cả settings core đều autoload = 'yes'
// Vì chúng cần thiết cho hầu hết mọi request

// Plugin nên set autoload = 'no' cho options ít dùng:
update_option( 'my_large_data', $data, false ); // autoload = false (='no')

// Hoặc khi đăng ký:
add_option( 'my_option', $default, '', 'no' ); // autoload = 'no'

// QUAN TRỌNG: Nếu có quá nhiều options autoload = yes
// → Memory usage tăng, performance giảm
// → Dùng query: SELECT * FROM wp_options WHERE autoload = 'yes'
// → Kiểm tra total size
```

### Options Core Quan Trọng

```php
// === General ===
'blogname'            → 'My WordPress Site'
'blogdescription'     → 'Just another WordPress site'
'siteurl'             → 'https://example.com'
'home'                → 'https://example.com'
'admin_email'         → 'admin@example.com'
'users_can_register'  → '0'
'default_role'        → 'subscriber'
'WPLANG'              → 'vi'
'timezone_string'     → 'Asia/Ho_Chi_Minh'
'gmt_offset'          → '7'
'date_format'         → 'd/m/Y'
'time_format'         → 'H:i'
'start_of_week'       → '1'

// === Writing ===
'default_category'      → '1'
'default_post_format'   → ''
'ping_sites'            → 'http://rpc.pingomatic.com/'

// === Reading ===
'posts_per_page'  → '10'
'posts_per_rss'   → '10'
'rss_use_excerpt'  → '0'
'show_on_front'   → 'posts'
'page_on_front'   → '0'
'page_for_posts'  → '0'
'blog_public'     → '1'

// === Discussion ===
'default_comment_status' → 'open'
'default_ping_status'    → 'open'
'require_name_email'     → '1'
'comment_moderation'     → '0'
'comment_max_links'      → '2'
'show_avatars'           → '1'
'avatar_default'         → 'mystery'
'thread_comments'        → '1'
'thread_comments_depth'  → '5'

// === Media ===
'thumbnail_size_w'  → '150'
'thumbnail_size_h'  → '150'
'thumbnail_crop'    → '1'
'medium_size_w'     → '300'
'medium_size_h'     → '300'
'large_size_w'      → '1024'
'large_size_h'      → '1024'

// === Permalinks ===
'permalink_structure' → '/%postname%/'
'category_base'       → ''
'tag_base'            → ''
'rewrite_rules'       → serialized array (rất lớn!)

// === Privacy ===
'wp_page_for_privacy_policy' → '3'

// === System (internal) ===
'active_plugins'    → serialized array
'template'          → 'theme-slug'
'stylesheet'        → 'theme-slug'
'db_version'        → '57155'
'initial_db_version'→ '57155'
'site_icon'         → '0'
```

### Plugin Options Best Practices

```php
// ĐÚNG: Gom nhiều settings vào 1 option (serialized array)
update_option( 'my_plugin_options', [
    'api_key'    => 'xxx',
    'enabled'    => true,
    'mode'       => 'production',
    'cache_ttl'  => 3600,
] );

// SAI: Mỗi setting 1 option riêng (gây bloat wp_options)
update_option( 'my_plugin_api_key', 'xxx' );
update_option( 'my_plugin_enabled', true );
update_option( 'my_plugin_mode', 'production' );
update_option( 'my_plugin_cache_ttl', 3600 );

// Tuy nhiên, nếu cần truy cập 1 option riêng lẻ thường xuyên
// hoặc option rất lớn → tách ra riêng cũng OK
```

---

## 13. Hooks Settings

### Hooks Chung Cho Tất Cả Options

```php
// === TRƯỚC KHI LẤY OPTION ===
// Filter giá trị trước khi trả về
apply_filters( "pre_option_{$option}", $pre_option, $option, $default );
// Return non-false → short-circuit, không query DB

// Filter giá trị sau khi lấy từ DB
apply_filters( "option_{$option}", $value, $option );

// === TRƯỚC KHI UPDATE ===
// Filter giá trị trước khi lưu
apply_filters( "pre_update_option_{$option}", $value, $old_value, $option );

// Sanitize option value
apply_filters( "sanitize_option_{$option}", $value, $option, $original_value );

// === SAU KHI UPDATE ===
// Action sau khi option đã update thành công
do_action( "update_option_{$option}", $old_value, $value, $option );

// Action chung cho tất cả options
do_action( 'updated_option', $option, $old_value, $value );

// === KHI THÊM OPTION MỚI ===
do_action( "add_option_{$option}", $option, $value );
do_action( 'added_option', $option, $value );

// === KHI XÓA OPTION ===
do_action( "delete_option_{$option}", $option );
do_action( 'deleted_option', $option );
```

### Hooks Quan Trọng Khi Thay Đổi Settings

```php
// Khi đổi Site URL - CẨN THẬN!
do_action( 'update_option_siteurl', $old_value, $value, 'siteurl' );
// Đổi sai → website bị hỏng, không truy cập được admin

// Khi đổi Home URL
do_action( 'update_option_home', $old_value, $value, 'home' );

// Khi đổi tên site
do_action( 'update_option_blogname', $old_value, $value, 'blogname' );

// Khi plugins thay đổi (activate/deactivate)
do_action( 'update_option_active_plugins', $old_value, $value, 'active_plugins' );

// Khi đổi permalink structure
do_action( 'update_option_permalink_structure', $old_value, $value, 'permalink_structure' );

// Khi đổi theme
do_action( 'update_option_template', $old_value, $value, 'template' );
do_action( 'update_option_stylesheet', $old_value, $value, 'stylesheet' );

// Khi đổi timezone
do_action( 'update_option_timezone_string', $old_value, $value, 'timezone_string' );
do_action( 'update_option_gmt_offset', $old_value, $value, 'gmt_offset' );
```

### Hooks Settings API

```php
// Đăng ký settings trong admin_init
add_action( 'admin_init', 'my_register_settings' );

// Filter allowed options (whitelist)
apply_filters( 'allowed_options', $allowed_options );

// Custom capability cho settings page
apply_filters( "option_page_capability_{$option_page}", $capability );

// Ví dụ: Cho phép editors truy cập settings page
add_filter( 'option_page_capability_my_plugin_group', function( $cap ) {
    return 'edit_others_posts'; // editors có cap này
});
```

### Settings Errors & Notices

```php
// Thêm error/notice
add_settings_error(
    'my_plugin_options',    // Setting slug
    'my_error_code',        // Error code
    'Thông báo lỗi!',      // Message
    'error'                 // Type: error, success, warning, info
);

// Hiển thị errors/notices
settings_errors( 'my_plugin_options' );
// Tự động hiển thị nếu dùng Settings API chuẩn

// Ví dụ trong sanitize callback:
public function sanitize( $input ) {
    if ( empty( $input['api_key'] ) ) {
        add_settings_error(
            'my_plugin_options',
            'empty_api_key',
            'API Key không được để trống.',
            'error'
        );
    }

    if ( strlen( $input['api_key'] ) < 10 ) {
        add_settings_error(
            'my_plugin_options',
            'short_api_key',
            'API Key phải có ít nhất 10 ký tự.',
            'warning'
        );
    }

    return $input;
}
```

---

## 14. So Sánh Laravel

### Settings Storage

| WordPress | Laravel |
|-----------|---------|
| `wp_options` table (key-value) | `.env` file + `config/` directory |
| `get_option('key')` | `config('app.key')` hoặc `env('APP_KEY')` |
| `update_option('key', $value)` | Không thể update .env/config runtime |
| Serialized arrays | Config arrays |
| Database-backed (runtime editable) | File-backed (deploy-time) |

### Nếu Laravel Cần Database Settings

```php
// Laravel tương đương cho WordPress options:

// 1. Tạo migration
Schema::create('settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value')->nullable();
    $table->boolean('autoload')->default(true);
});

// 2. Model
class Setting extends Model {
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}

// 3. Helper functions (tương đương get_option/update_option)
function setting_get($key, $default = null) {
    return Setting::find($key)?->value ?? $default;
}

function setting_set($key, $value) {
    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
}
```

### Settings API

| WordPress | Laravel |
|-----------|---------|
| Settings API | Không có built-in tương đương |
| `register_setting()` | Tự define validation rules |
| `sanitize_callback` | `FormRequest::rules()` |
| `add_settings_section()` | View/Blade sections |
| `add_settings_field()` | Form components |
| `options.php` processor | Controller method |
| `settings_errors()` | `$errors` bag trong Blade |

### Validation

```php
// WordPress:
register_setting( 'group', 'option', [
    'sanitize_callback' => function( $value ) {
        return sanitize_text_field( $value );
    },
]);

// Laravel:
$request->validate([
    'option' => 'required|string|max:255',
]);
```

### Permalinks

| WordPress | Laravel |
|-----------|---------|
| Permalink Settings UI | `routes/web.php` |
| `permalink_structure` option | Route definitions |
| `flush_rewrite_rules()` | `php artisan route:cache` |
| `.htaccess` / nginx config | Apache/Nginx config |
| Rewrite rules in DB | Compiled route cache file |
| `%postname%` tags | `{slug}` parameters |

### Key Differences

```
1. WordPress settings lưu trong DB → thay đổi runtime qua UI
   Laravel config lưu trong files → thay đổi khi deploy

2. WordPress Settings API tạo form tự động
   Laravel cần tự viết form (hoặc dùng Filament/Nova)

3. WordPress options.php xử lý TẤT CẢ settings forms
   Laravel mỗi form có controller riêng

4. WordPress allowed_options whitelist bảo vệ against injection
   Laravel FormRequest validation bảo vệ

5. WordPress Permalinks quản lý rewrite rules + .htaccess
   Laravel routes compile thành PHP file

6. WordPress settings autoload tất cả vào mỗi request
   Laravel config cache compile thành 1 file

7. WordPress dùng serialized arrays trong options
   Laravel dùng native PHP arrays trong config files
```

---

## Tổng Kết

### Files Quan Trọng

```
/wp-admin/options-general.php     → General Settings
/wp-admin/options-writing.php     → Writing Settings
/wp-admin/options-reading.php     → Reading Settings
/wp-admin/options-discussion.php  → Discussion Settings
/wp-admin/options-media.php       → Media Settings
/wp-admin/options-permalink.php   → Permalink Settings
/wp-admin/options-privacy.php     → Privacy Settings
/wp-admin/options.php             → Settings Processor (xử lý save TẤT CẢ forms)
/wp-includes/option.php           → get_option(), update_option(), register_setting()
/wp-admin/includes/template.php   → settings_fields(), do_settings_sections()
```

### Flow Tổng Quan

```
Admin truy cập Settings page (options-*.php)
  → Kiểm tra manage_options capability
  → Render form với settings_fields() + do_settings_sections()
  → User thay đổi và nhấn Save Changes

Form submit tới options.php
  → Verify nonce (check_admin_referer)
  → Kiểm tra capability
  → Kiểm tra allowed_options whitelist
  → Với mỗi option:
    → sanitize_option_{$option} filter
    → update_option() → INSERT/UPDATE wp_options
    → update_option_{$option} action fires
  → Redirect về settings page ?updated=true
  → Hiện notice "Settings saved."

Plugin tạo settings page riêng:
  → admin_menu hook → add_options_page()
  → admin_init hook → register_setting() + add_settings_section() + add_settings_field()
  → Render: settings_fields() + do_settings_sections() + submit_button()
  → Form submit → options.php tự động xử lý
```

### Khi Nào Dùng Settings API vs Custom

```
Dùng Settings API khi:
  - Settings đơn giản (text, checkbox, select, number)
  - Lưu vào wp_options
  - Muốn form chuẩn WordPress style
  - Không cần JavaScript phức tạp

Dùng Custom form khi:
  - Settings phức tạp (repeater fields, image upload, drag & drop)
  - Cần AJAX save (không reload page)
  - Lưu vào bảng custom (không phải wp_options)
  - Cần UI khác biệt (React/Vue)
  - Settings page rất lớn với tabs phức tạp
```
