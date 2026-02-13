# Hệ Thống Học Tập WordPress

> Tài liệu học WordPress từ cơ bản đến nâng cao, viết bằng tiếng Việt, có code examples chi tiết.
> Dành cho PHP Laravel Developer chuyển sang WordPress.

---

## Bắt Đầu Từ Đâu

Nếu bạn mới bắt đầu học WordPress, hãy đọc theo thứ tự sau:

1. Đọc [Lộ trình học tập](#lộ-trình-học-tập) để có cái nhìn tổng thể
2. Tìm hiểu [Cấu trúc source code](#hiểu-wordpress-core) để hiểu WordPress hoạt động thế nào
3. Học [Luồng xử lý request](#hiểu-wordpress-core) để hiểu mỗi request được xử lý ra sao
4. Học [Hooks](#hooks-chi-tiết) - hệ thống event-driven của WordPress
5. Tiếp tục với [Plugin](#plugin-development-chi-tiết) hoặc [Theme](#theme-development-chi-tiết) tùy mục tiêu

---

## Mục Lục Tổng Hợp

### Lộ Trình Học Tập

| File | Mô tả |
|------|-------|
| [WORDPRESS_LEARNING_PATH.md](./WORDPRESS_LEARNING_PATH.md) | Lộ trình 8 giai đoạn từ cơ bản đến chuyên gia, có gợi ý study plan 6-9 tháng |

---

### Hiểu WordPress Core

Nhóm này giúp bạn hiểu cách WordPress hoạt động từ bên trong.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phân tích cấu trúc thư mục, các file chính, design patterns | Cơ bản |
| 2 | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luồng xử lý request từ index.php đến HTML output | Cơ bản |
| 3 | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | Hệ thống URL Rewriting, Rewrite API, Template Hierarchy | Trung bình |
| 4 | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Tổng quan Action Hooks, Filter Hooks, Priority, Custom Hooks | Trung bình |

**Thứ tự đọc khuyến nghị:** 1 → 2 → 3 → 4

---

### Hooks Chi Tiết

Hệ thống Hooks là trái tim của WordPress - tương tự Events/Listeners trong Laravel.
Đây là series chi tiết từ cơ bản đến nâng cao.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [01-hooks-co-ban.md](./hooks/01-hooks-co-ban.md) | Hooks là gì, add_action, add_filter, priority, so sánh với Laravel Events | Cơ bản |
| 2 | [02-action-hooks-quan-trong.md](./hooks/02-action-hooks-quan-trong.md) | Danh sách Action Hooks quan trọng: init, admin_menu, wp_enqueue_scripts, save_post... | Trung bình |
| 3 | [03-filter-hooks-quan-trong.md](./hooks/03-filter-hooks-quan-trong.md) | Danh sách Filter Hooks quan trọng: the_content, pre_get_posts, body_class... | Trung bình |
| 4 | [04-hooks-lifecycle.md](./hooks/04-hooks-lifecycle.md) | WordPress Loading Sequence, Frontend/Admin/AJAX/REST/Cron Lifecycle | Trung bình |
| 5 | [05-custom-hooks.md](./hooks/05-custom-hooks.md) | Tạo Custom Hooks, do_action, apply_filters, Observer Pattern | Nâng cao |
| 6 | [06-hooks-trong-plugin.md](./hooks/06-hooks-trong-plugin.md) | Best practices dùng hooks trong plugin, activation/deactivation, conditional hooks | Nâng cao |
| 7 | [07-hooks-nang-cao.md](./hooks/07-hooks-nang-cao.md) | OOP callbacks, WP_Hook class, dynamic hooks, performance, testing | Nâng cao |

**Thứ tự đọc khuyến nghị:** 1 → 2 → 3 → 4 → 5 → 6 → 7

---

### Plugin Development Chi Tiết

Hướng dẫn tạo plugin WordPress từ cơ bản đến nâng cao.
Plugin trong WP tương tự Service Provider / Package trong Laravel.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [01-plugin-co-ban.md](./plugins/01-plugin-co-ban.md) | Plugin là gì, Plugin Headers, Activation/Deactivation Hooks, Hello World | Cơ bản |
| 2 | [02-menu-va-settings-api.md](./plugins/02-menu-va-settings-api.md) | Admin Menu, Settings API, Tabs, các loại field, validate & sanitize | Trung bình |
| 3 | [03-shortcodes-va-widgets.md](./plugins/03-shortcodes-va-widgets.md) | Shortcodes, Widgets API, WP_Widget class, nested shortcodes | Trung bình |
| 4 | [04-database-va-crud.md](./plugins/04-database-va-crud.md) | $wpdb, Custom Tables, dbDelta, CRUD, Options API, Meta API, so sánh Eloquent | Trung bình |
| 5 | [05-ajax-va-rest-api.md](./plugins/05-ajax-va-rest-api.md) | WordPress AJAX, REST API trong plugin, Custom Endpoints, so sánh Laravel Route | Nâng cao |
| 6 | [06-plugin-oop-architecture.md](./plugins/06-plugin-oop-architecture.md) | Singleton, Autoloading, Namespaces, MVC Pattern, Plugin Boilerplate | Nâng cao |
| 7 | [07-bao-mat-plugin.md](./plugins/07-bao-mat-plugin.md) | Sanitize, Escape, Nonces, Capability Checks, SQL Injection, XSS, CSRF | Nâng cao |
| 8 | [08-plugin-nang-cao.md](./plugins/08-plugin-nang-cao.md) | CPT, Meta Boxes, Cron Jobs, Email, i18n, Unit Testing, Packaging | Nâng cao |

**Thứ tự đọc khuyến nghị:** 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8

---

### Theme Development Chi Tiết

Hướng dẫn tạo theme WordPress từ cơ bản đến nâng cao.
Theme trong WP tương tự Views/Blade trong Laravel.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [01-theme-co-ban.md](./themes/01-theme-co-ban.md) | Theme là gì, style.css header, functions.php, enqueue assets, Hello World theme | Cơ bản |
| 2 | [02-template-hierarchy.md](./themes/02-template-hierarchy.md) | Template Hierarchy, template files, conditional tags, template parts | Trung bình |
| 3 | [03-the-loop-va-wp-query.md](./themes/03-the-loop-va-wp-query.md) | The Loop, WP_Query, Multiple Loops, Pagination, pre_get_posts | Trung bình |
| 4 | [04-menus-widgets-sidebars.md](./themes/04-menus-widgets-sidebars.md) | Navigation Menus, Custom Walker, Sidebars, Widget Areas | Trung bình |
| 5 | [05-customizer-api.md](./themes/05-customizer-api.md) | Theme Customizer, Panels, Sections, Controls, Live Preview | Nâng cao |
| 6 | [06-block-theme-va-fse.md](./themes/06-block-theme-va-fse.md) | Block Theme, theme.json, Full Site Editing, Block Patterns | Nâng cao |
| 7 | [07-theme-nang-cao.md](./themes/07-theme-nang-cao.md) | Child Theme, WooCommerce, Responsive, a11y, i18n, Packaging | Nâng cao |

**Thứ tự đọc khuyến nghị:** 1 → 2 → 3 → 4 → 5 → 6 → 7

---

### Tài Liệu Gốc (Legacy)

Các file tài liệu ban đầu, vẫn hữu ích để tham khảo nhanh.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Download, cài đặt, sử dụng theme có sẵn, Child Theme | Cơ bản |
| 2 | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Tạo theme từ đầu (bản tổng hợp) | Trung bình |
| 3 | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Tạo plugin từ đầu (bản tổng hợp) | Trung bình |

---

### Database và Truy Vấn

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Schema database, $wpdb, WP_Query, Meta Query, Tax Query, Custom Tables | Trung bình |
| 2 | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | register_post_type, register_taxonomy, Meta Boxes, Custom Columns | Trung bình |

**Thứ tự đọc khuyến nghị:** 1 → 2

---

### REST API

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [REST_API.md](./REST_API.md) | Endpoints mặc định, Authentication, Custom Endpoints, Controller, CRUD API | Nâng cao |

---

### Bảo Mật

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Sanitize, Escape, Nonces, SQL Injection, XSS, File Upload, Security Constants | Trung bình |

---

### Hiệu Năng

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Object Cache, Page Cache, Transients, Database Optimization, Image, CDN, Profiling | Nâng cao |

---

### Block Editor (Gutenberg)

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Tạo Custom Block, Attributes, InspectorControls, Dynamic Blocks, Block Patterns | Nâng cao |

---

### Công Cụ

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [WP_CLI.md](./WP_CLI.md) | Cài đặt, các lệnh cơ bản, scaffold, search-replace, backup, Custom Command | Trung bình |

---

## Bản Đồ Học Tập

```
                        ┌──────────────────────────┐
                        │  WORDPRESS_LEARNING_PATH  │  Bắt đầu ở đây
                        └─────────────┬────────────┘
                                      │
                        ┌─────────────▼────────────┐
                        │   CAU_TRUC_SOURCE_CODE    │  Hiểu cấu trúc WP
                        │      WORDPRESS_FLOW       │  Hiểu luồng xử lý
                        │     WORDPRESS_ROUTING     │  Hiểu routing
                        └─────────────┬────────────┘
                                      │
                     ┌────────────────▼────────────────┐
                     │         HOOKS (7 files)          │
                     │  hooks/01 → 02 → 03 → ... → 07  │
                     │  (Trái tim của WordPress)         │
                     └────────┬───────────┬────────────┘
                              │           │
               ┌──────────────▼──┐  ┌─────▼──────────────┐
               │  PLUGINS (8 files)│  │  THEMES (7 files)  │
               │  plugins/01 → 08 │  │  themes/01 → 07    │
               │  (Mở rộng WP)    │  │  (Giao diện WP)    │
               └────────┬─────────┘  └─────┬──────────────┘
                        │                   │
         ┌──────────────┼───────────────────┼──────────┐
         │              │                   │          │
  ┌──────▼──────┐ ┌─────▼──────┐  ┌────────▼───┐ ┌────▼────────┐
  │DATABASE_VA  │ │ REST_API   │  │ GUTENBERG  │ │ BAO_MAT_WP  │
  │WP_QUERY     │ │            │  │ BLOCK_EDITOR│ │             │
  └──────┬──────┘ └────────────┘  └────────────┘ └─────────────┘
         │
  ┌──────▼──────────────┐
  │CUSTOM_POST_TYPE     │
  │TAXONOMY             │
  └─────────────────────┘
         │
  ┌──────▼──────────────┐  ┌────────────────┐
  │ HIEU_NANG_TOI_UU    │  │   WP_CLI       │
  │ (Tối ưu hiệu năng)  │  │  (Công cụ)     │
  └─────────────────────┘  └────────────────┘
```

---

## Cấu Trúc Thư Mục

```
wp-study/
├── INDEX.md                          ← File này (điều hướng)
├── WORDPRESS_LEARNING_PATH.md        ← Lộ trình học tập
├── CAU_TRUC_SOURCE_CODE.md           ← Cấu trúc source code WP
├── WORDPRESS_FLOW.md                 ← Luồng xử lý request
├── WORDPRESS_ROUTING.md              ← Hệ thống routing
├── WORDPRESS_HOOKS.md                ← Tổng quan hooks
│
├── hooks/                            ← HOOKS CHI TIẾT (7 files)
│   ├── 01-hooks-co-ban.md
│   ├── 02-action-hooks-quan-trong.md
│   ├── 03-filter-hooks-quan-trong.md
│   ├── 04-hooks-lifecycle.md
│   ├── 05-custom-hooks.md
│   ├── 06-hooks-trong-plugin.md
│   └── 07-hooks-nang-cao.md
│
├── plugins/                          ← PLUGIN CHI TIẾT (8 files)
│   ├── 01-plugin-co-ban.md
│   ├── 02-menu-va-settings-api.md
│   ├── 03-shortcodes-va-widgets.md
│   ├── 04-database-va-crud.md
│   ├── 05-ajax-va-rest-api.md
│   ├── 06-plugin-oop-architecture.md
│   ├── 07-bao-mat-plugin.md
│   └── 08-plugin-nang-cao.md
│
├── themes/                           ← THEME CHI TIẾT (7 files)
│   ├── 01-theme-co-ban.md
│   ├── 02-template-hierarchy.md
│   ├── 03-the-loop-va-wp-query.md
│   ├── 04-menus-widgets-sidebars.md
│   ├── 05-customizer-api.md
│   ├── 06-block-theme-va-fse.md
│   └── 07-theme-nang-cao.md
│
├── DATABASE_VA_WP_QUERY.md           ← Database & WP_Query
├── CUSTOM_POST_TYPE_TAXONOMY.md      ← CPT & Taxonomy
├── REST_API.md                       ← REST API
├── BAO_MAT_WORDPRESS.md              ← Bảo mật
├── HIEU_NANG_TOI_UU.md              ← Hiệu năng
├── GUTENBERG_BLOCK_EDITOR.md         ← Gutenberg
├── WP_CLI.md                         ← WP-CLI
│
├── HUONG_DAN_THEME.md                ← (Legacy) Hướng dẫn theme
├── TAO_THEME_CO_BAN.md               ← (Legacy) Tạo theme cơ bản
└── TAO_PLUGIN_CO_BAN.md              ← (Legacy) Tạo plugin cơ bản
```

---

## So Sánh Nhanh Laravel vs WordPress

| Khái niệm | Laravel | WordPress |
|-----------|---------|-----------|
| **Entry point** | `public/index.php` | `index.php` → `wp-blog-header.php` |
| **Routing** | `routes/web.php` | Rewrite Rules + Template Hierarchy |
| **Controller** | `App\Http\Controllers` | Template files (single.php, page.php...) |
| **Views** | Blade templates | PHP template files |
| **Models** | Eloquent ORM | `$wpdb` + `WP_Query` |
| **Events** | Events/Listeners | Action Hooks / Filter Hooks |
| **Service Provider** | `App\Providers` | Plugins |
| **Middleware** | `App\Http\Middleware` | Hooks trên `init`, `template_redirect`... |
| **Package** | Composer packages | Plugins |
| **Config** | `.env` + `config/` | `wp-config.php` + Options API |
| **CLI** | `php artisan` | `wp` (WP-CLI) |
| **Migration** | Migration files | `dbDelta()` |
| **Cache** | `Cache::remember()` | `get_transient()` / Object Cache |
| **Auth** | Auth scaffolding | Built-in user system |
| **API** | API Resources | REST API + `register_rest_route()` |

---

## Thống Kê

| Thông tin | Giá trị |
|-----------|---------|
| Tổng số file | 29+ |
| Folders | 3 (hooks, plugins, themes) |
| Series Hooks | 7 files |
| Series Plugins | 8 files |
| Series Themes | 7 files |
| Ngôn ngữ | Tiếng Việt |
| Code examples | Có trong mọi file |
| So sánh Laravel | Có trong mọi series |
| Cập nhật | 02/2026 |
