# WordPress Admin - Tài Liệu Học Cho Laravel Developer

> **Dành cho**: PHP Laravel Developer chuyển sang WordPress
> **Mục tiêu**: Hiểu toàn bộ hệ thống Admin Panel của WordPress
> **Source code**: `/wp-admin/` và `/wp-admin/includes/`

---

## Giới Thiệu

WordPress Admin (hay còn gọi là **WP Admin**, **Dashboard**, **Back-end**) là hệ thống quản trị nội dung được tích hợp sẵn trong WordPress. Khác với Laravel, nơi bạn cần cài đặt package bên ngoài như **Laravel Nova**, **Filament**, hoặc **Backpack** để có admin panel, WordPress đã xây dựng sẵn một hệ thống admin hoàn chỉnh ngay từ đầu.

### So sánh nhanh với Laravel

| Tính năng | Laravel | WordPress |
|-----------|---------|-----------|
| Admin Panel | Cần package (Nova, Filament) | Built-in sẵn |
| URL Admin | Tuỳ cấu hình (thường `/admin`) | `/wp-admin/` |
| Authentication | Middleware `auth` | `auth_redirect()` + cookie |
| Menu System | Tự code hoặc package | `add_menu_page()` API |
| CRUD | Resource Controller | `WP_List_Table` + post.php |
| AJAX | Route + Controller | `admin-ajax.php` + hooks |
| Settings | Tự code | Settings API built-in |
| User Roles | Spatie Permission package | Built-in Roles & Capabilities |

### Truy cập WP Admin

```
https://your-site.com/wp-admin/          → Dashboard (trang chính)
https://your-site.com/wp-login.php       → Trang đăng nhập
https://your-site.com/wp-admin/admin.php → Bootstrap file chính
```

---

## Bảng Điều Hướng - 10 Chủ Đề Admin

| STT | File | Nội dung | Mô tả chi tiết |
|-----|------|----------|-----------------|
| 01 | [01-tong-quan-admin.md](./01-tong-quan-admin.md) | Tổng quan WP Admin | Bootstrap flow, menu system, admin hooks, WP_Screen, AJAX, setup cấu hình đầu tiên |
| 02 | [02-dashboard.md](./02-dashboard.md) | Dashboard | Dashboard widgets, Screen Options, tạo custom widget, drag & drop |
| 03 | [03-quan-ly-bai-viet.md](./03-quan-ly-bai-viet.md) | Quản lý Bài viết | Posts, Pages, Editor (Gutenberg/Classic), Meta Boxes, Custom Columns, Bulk Actions |
| 04 | [04-quan-ly-media.md](./04-quan-ly-media.md) | Quản lý Media | Upload files, Image Sizes, Media Library, attachment post type |
| 05 | [05-quan-ly-binh-luan.md](./05-quan-ly-binh-luan.md) | Quản lý Bình luận | Comments list, moderation, spam, comment meta, Akismet |
| 06 | [06-giao-dien.md](./06-giao-dien.md) | Giao diện | Themes, Customizer, Widgets, Menus, Block Themes, Site Editor |
| 07 | [07-quan-ly-plugin.md](./07-quan-ly-plugin.md) | Quản lý Plugins | Install, activate, deactivate, update, Plugin API |
| 08 | [08-quan-ly-nguoi-dung.md](./08-quan-ly-nguoi-dung.md) | Quản lý Người dùng | Users, Roles, Capabilities, Profile, User Meta |
| 09 | [09-cong-cu.md](./09-cong-cu.md) | Công cụ | Import, Export, Site Health, Privacy Tools |
| 10 | [10-cai-dat.md](./10-cai-dat.md) | Cài đặt | 7 trang settings: General, Writing, Reading, Discussion, Media, Permalinks, Privacy |

---

## Sơ Đồ Cấu Trúc Admin

### Cấu trúc thư mục `/wp-admin/`

```
wp-admin/
├── index.php                  ← Dashboard (trang chính khi login)
├── admin.php                  ← Bootstrap chính cho mọi trang admin
├── admin-header.php           ← HTML header template
├── admin-footer.php           ← HTML footer template
├── admin-ajax.php             ← AJAX endpoint
├── admin-post.php             ← POST request handler
├── menu.php                   ← Định nghĩa admin menu
│
├── edit.php                   ← Danh sách bài viết (All Posts)
├── post.php                   ← Xử lý actions: edit, trash, delete
├── post-new.php               ← Tạo bài viết mới
├── edit-form-blocks.php       ← Block Editor (Gutenberg)
├── edit-form-advanced.php     ← Classic Editor form
│
├── upload.php                 ← Media Library
├── media-new.php              ← Upload media mới
│
├── edit-comments.php          ← Danh sách bình luận
├── comment.php                ← Xử lý comment actions
│
├── themes.php                 ← Quản lý Themes
├── customize.php              ← Customizer
├── widgets.php                ← Widgets (classic)
├── nav-menus.php              ← Navigation Menus
├── site-editor.php            ← Site Editor (block themes)
│
├── plugins.php                ← Installed Plugins
├── plugin-install.php         ← Add New Plugin
├── plugin-editor.php          ← Plugin File Editor
│
├── users.php                  ← All Users
├── user-new.php               ← Add New User
├── user-edit.php              ← Edit User
├── profile.php                ← Your Profile
│
├── tools.php                  ← Available Tools
├── import.php                 ← Import
├── export.php                 ← Export
├── erase-personal-data.php    ← Privacy: Erase Personal Data
├── export-personal-data.php   ← Privacy: Export Personal Data
├── site-health.php            ← Site Health
│
├── options-general.php        ← Settings > General
├── options-writing.php        ← Settings > Writing
├── options-reading.php        ← Settings > Reading
├── options-discussion.php     ← Settings > Discussion
├── options-media.php          ← Settings > Media
├── options-permalink.php      ← Settings > Permalinks
├── options-privacy.php        ← Settings > Privacy
├── options.php                ← Xử lý lưu settings
│
├── includes/                  ← Admin API files
│   ├── admin.php              ← Load tất cả admin APIs
│   ├── admin-filters.php      ← Admin hook filters
│   ├── dashboard.php          ← Dashboard widgets API
│   ├── post.php               ← Post admin functions
│   ├── meta-boxes.php         ← Meta boxes API
│   ├── class-wp-screen.php    ← WP_Screen class
│   ├── class-wp-list-table.php        ← Base list table class
│   ├── class-wp-posts-list-table.php  ← Posts list table
│   ├── ajax-actions.php       ← AJAX action handlers
│   ├── plugin.php             ← Plugin admin functions
│   ├── theme.php              ← Theme admin functions
│   ├── user.php               ← User admin functions
│   ├── taxonomy.php           ← Taxonomy admin functions
│   ├── media.php              ← Media admin functions
│   ├── image.php              ← Image processing
│   ├── file.php               ← File operations
│   ├── misc.php               ← Miscellaneous functions
│   ├── options.php            ← Options/Settings functions
│   ├── template.php           ← Admin template tags
│   ├── update.php             ← Update functions
│   ├── bookmark.php           ← Bookmark/Links functions
│   ├── comment.php            ← Comment admin functions
│   ├── import.php             ← Import functions
│   ├── screen.php             ← Screen helper functions
│   ├── list-table.php         ← List table helper
│   ├── privacy-tools.php      ← Privacy tools
│   └── deprecated.php         ← Deprecated functions
│
├── css/                       ← Admin CSS files
├── js/                        ← Admin JavaScript files
└── images/                    ← Admin images
```

### Sơ đồ Admin Menu (sidebar)

```
┌─────────────────────────────────┐
│  Dashboard          (pos: 2)    │  → index.php
│  ─────────────────  (pos: 4)    │  → separator
│  Posts              (pos: 5)    │  → edit.php
│  Media              (pos: 10)   │  → upload.php
│  Links              (pos: 15)   │  → link-manager.php (ẩn mặc định)
│  Pages              (pos: 20)   │  → edit.php?post_type=page
│  Comments           (pos: 25)   │  → edit-comments.php
│  ─────────────────  (pos: 59)   │  → separator
│  Appearance         (pos: 60)   │  → themes.php
│  Plugins            (pos: 65)   │  → plugins.php
│  Users              (pos: 70)   │  → users.php
│  Tools              (pos: 75)   │  → tools.php
│  Settings           (pos: 80)   │  → options-general.php
│  ─────────────────  (pos: 99)   │  → separator
│  [Collapse Menu]                │
└─────────────────────────────────┘
```

### Luồng xử lý một request Admin

```
Browser Request: GET /wp-admin/edit.php
         │
         ▼
    wp-admin/edit.php
         │
         ├── require admin.php (bootstrap)
         │     ├── define WP_ADMIN = true
         │     ├── require wp-load.php (load WordPress core)
         │     ├── nocache_headers()
         │     ├── require includes/admin.php (load ALL admin APIs)
         │     ├── auth_redirect() (kiểm tra đăng nhập)
         │     ├── require menu.php (build admin menu)
         │     ├── do_action('admin_init')
         │     └── set current screen
         │
         ├── Business logic (query posts, handle actions)
         │
         ├── require admin-header.php
         │     ├── <html>, <head>
         │     ├── wp_enqueue_scripts (admin)
         │     ├── do_action('admin_head')
         │     ├── Admin Bar
         │     └── Sidebar Menu
         │
         ├── Page Content (HTML output)
         │
         └── require admin-footer.php
               ├── do_action('admin_footer')
               ├── wp_print_footer_scripts()
               └── </html>
```

---

## Quy Ước Đọc Tài Liệu

- **Source**: đường dẫn đến file source code WordPress, tính từ thư mục gốc WordPress
- **Hook**: tên action/filter hook, kèm file nơi nó được gọi
- **DB**: bảng database và cột liên quan
- **Laravel**: so sánh tương đương trong Laravel framework
- **Code example**: code PHP có thể dùng trong theme `functions.php` hoặc plugin

---

## Bắt Đầu Từ Đâu?

1. **Mới hoàn toàn**: Bắt đầu từ [01-tong-quan-admin.md](./01-tong-quan-admin.md) để hiểu tổng quan
2. **Muốn tìm hiểu Dashboard**: Đọc [02-dashboard.md](./02-dashboard.md)
3. **Muốn quản lý nội dung**: Đọc [03-quan-ly-bai-viet.md](./03-quan-ly-bai-viet.md)
4. **Muốn tìm hiểu settings**: Nhảy đến [10-cai-dat.md](./10-cai-dat.md)

---

*Tài liệu này là một phần của bộ [WordPress Study Guide cho Laravel Developer](../INDEX.md).*
