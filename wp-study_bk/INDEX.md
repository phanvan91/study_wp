# Hệ Thống Học Tập WordPress

> Tài liệu học WordPress từ cơ bản đến nâng cao, viết bằng tiếng Việt có dấu, code examples chi tiết.
> Dành cho PHP Laravel Developer chuyển sang WordPress.

---

## Lộ Trình Học Tập (Đọc Theo Thứ Tự)

### Giai đoạn 1: Nền Tảng WordPress (Tuần 1-2)

> Mục tiêu: Hiểu WordPress hoạt động như thế nào từ bên trong.

| Bước | File | Nội dung | Độ khó |
|------|------|----------|--------|
| 1.1 | [WORDPRESS_LEARNING_PATH.md](./WORDPRESS_LEARNING_PATH.md) | Lộ trình tổng quan 8 giai đoạn, study plan 6-9 tháng | Cơ bản |
| 1.2 | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phân tích cấu trúc thư mục WP, các file chính, design patterns | Cơ bản |
| 1.3 | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luồng xử lý request từ index.php → HTML output | Cơ bản |
| 1.4 | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | Hệ thống URL Rewriting, Rewrite API, Template Hierarchy | Cơ bản |

---

### Giai đoạn 2: WordPress Hooks (Tuần 3-4)

> Mục tiêu: Nắm vững hệ thống Hooks - trái tim của WordPress.
> So sánh: Hooks = Events/Listeners trong Laravel.

| Bước | File | Nội dung | Độ khó |
|------|------|----------|--------|
| 2.1 | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Tổng quan nhanh về Action & Filter Hooks | Cơ bản |
| 2.2 | [hooks/01-hooks-co-ban.md](./hooks/01-hooks-co-ban.md) | add_action, add_filter, remove, priority, closures, so sánh Laravel | Cơ bản |
| 2.3 | [hooks/02-action-hooks-quan-trong.md](./hooks/02-action-hooks-quan-trong.md) | init, admin_menu, wp_enqueue_scripts, save_post, wp_ajax... | Trung bình |
| 2.4 | [hooks/03-filter-hooks-quan-trong.md](./hooks/03-filter-hooks-quan-trong.md) | the_content, pre_get_posts, body_class, upload_mimes, wp_mail... | Trung bình |
| 2.5 | [hooks/04-hooks-lifecycle.md](./hooks/04-hooks-lifecycle.md) | Vòng đời request: Frontend, Admin, AJAX, REST, Cron lifecycle | Trung bình |
| 2.6 | [hooks/05-custom-hooks.md](./hooks/05-custom-hooks.md) | Tạo do_action, apply_filters riêng, Observer Pattern | Nâng cao |
| 2.7 | [hooks/06-hooks-trong-plugin.md](./hooks/06-hooks-trong-plugin.md) | Activation/deactivation hooks, conditional hooks, remove hooks | Nâng cao |
| 2.8 | [hooks/07-hooks-nang-cao.md](./hooks/07-hooks-nang-cao.md) | OOP callbacks, WP_Hook class, dynamic hooks, performance, testing | Nâng cao |

---

### Giai đoạn 3: Phát Triển Plugin (Tuần 5-8)

> Mục tiêu: Tạo được plugin WordPress hoàn chỉnh.
> So sánh: Plugin = Service Provider / Package trong Laravel.

| Bước | File | Nội dung | Độ khó |
|------|------|----------|--------|
| 3.1 | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Bản tổng hợp nhanh về tạo plugin (đọc lướt) | Trung bình |
| 3.2 | [plugins/01-plugin-co-ban.md](./plugins/01-plugin-co-ban.md) | Plugin là gì, Headers, cấu trúc, Activation/Deactivation, Hello World | Cơ bản |
| 3.3 | [plugins/02-menu-va-settings-api.md](./plugins/02-menu-va-settings-api.md) | Admin Menu, Settings API, Tabs, các loại field, validate & sanitize | Trung bình |
| 3.4 | [plugins/03-shortcodes-va-widgets.md](./plugins/03-shortcodes-va-widgets.md) | Shortcodes, nested shortcodes, Widgets API, WP_Widget class | Trung bình |
| 3.5 | [plugins/04-database-va-crud.md](./plugins/04-database-va-crud.md) | $wpdb, Custom Tables, dbDelta, CRUD hoàn chỉnh, so sánh Eloquent | Trung bình |
| 3.6 | [plugins/05-ajax-va-rest-api.md](./plugins/05-ajax-va-rest-api.md) | WordPress AJAX, REST API, Custom Endpoints, so sánh Laravel Route | Nâng cao |
| 3.7 | [plugins/06-plugin-oop-architecture.md](./plugins/06-plugin-oop-architecture.md) | Singleton, Autoloading, Namespaces, MVC, Plugin Boilerplate | Nâng cao |
| 3.8 | [plugins/07-bao-mat-plugin.md](./plugins/07-bao-mat-plugin.md) | Sanitize, Escape, Nonces, SQL Injection, XSS, CSRF, File Upload | Nâng cao |
| 3.9 | [plugins/08-plugin-nang-cao.md](./plugins/08-plugin-nang-cao.md) | CPT, Meta Boxes, Cron Jobs, Email, i18n, Unit Testing, Packaging | Nâng cao |

---

### Giai đoạn 4: Phát Triển Theme (Tuần 9-12)

> Mục tiêu: Tạo được theme WordPress từ đầu.
> So sánh: Theme = Views / Blade Templates trong Laravel.

| Bước | File | Nội dung | Độ khó |
|------|------|----------|--------|
| 4.1 | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Download, cài đặt, sử dụng theme có sẵn (đọc nhanh) | Cơ bản |
| 4.2 | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Bản tổng hợp nhanh về tạo theme (đọc lướt) | Trung bình |
| 4.3 | [themes/01-theme-co-ban.md](./themes/01-theme-co-ban.md) | style.css header, functions.php, enqueue assets, Hello World theme | Cơ bản |
| 4.4 | [themes/02-template-hierarchy.md](./themes/02-template-hierarchy.md) | Template Hierarchy, template files, conditional tags, template parts | Trung bình |
| 4.5 | [themes/03-the-loop-va-wp-query.md](./themes/03-the-loop-va-wp-query.md) | The Loop, WP_Query, Multiple Loops, Pagination, pre_get_posts | Trung bình |
| 4.6 | [themes/04-menus-widgets-sidebars.md](./themes/04-menus-widgets-sidebars.md) | Navigation Menus, Custom Walker, Mega Menu, Sidebars, Widget Areas | Trung bình |
| 4.7 | [themes/05-customizer-api.md](./themes/05-customizer-api.md) | Theme Customizer, Panels, Sections, Controls, Live Preview | Nâng cao |
| 4.8 | [themes/06-block-theme-va-fse.md](./themes/06-block-theme-va-fse.md) | Block Theme, theme.json, Full Site Editing, Block Patterns | Nâng cao |
| 4.9 | [themes/07-theme-nang-cao.md](./themes/07-theme-nang-cao.md) | Child Theme, WooCommerce, Responsive, a11y, i18n, Packaging | Nâng cao |

---

### Giai đoạn 5: Quản Lý Admin WordPress (Tuần 13-16)

> Mục tiêu: Hiểu tường tận phần Admin Dashboard và cách quản lý WordPress.

| Bước | File | Nội dung | Độ khó |
|------|------|----------|--------|
| 5.1 | [admin/INDEX.md](./admin/INDEX.md) | Mục lục điều hướng toàn bộ phần Admin | - |
| 5.2 | [admin/01-tong-quan-admin.md](./admin/01-tong-quan-admin.md) | Bootstrap flow, Admin Menu, Hooks, Setup cấu hình đầu tiên | Cơ bản |
| 5.3 | [admin/02-dashboard.md](./admin/02-dashboard.md) | Dashboard widgets, Screen Options, Custom widgets | Trung bình |
| 5.4 | [admin/03-quan-ly-bai-viet.md](./admin/03-quan-ly-bai-viet.md) | Posts list, Editor, Meta Boxes, Custom Columns, Bulk Actions | Trung bình |
| 5.5 | [admin/04-quan-ly-media.md](./admin/04-quan-ly-media.md) | Media Library, Upload flow, Image Sizes, EXIF | Trung bình |
| 5.6 | [admin/05-quan-ly-binh-luan.md](./admin/05-quan-ly-binh-luan.md) | Comments list, Moderation, Reply, Anti-spam | Trung bình |
| 5.7 | [admin/06-giao-dien.md](./admin/06-giao-dien.md) | Themes, Customizer, Widgets, Menus, FSE | Nâng cao |
| 5.8 | [admin/07-quan-ly-plugin.md](./admin/07-quan-ly-plugin.md) | Plugins list, Activate/Deactivate, Install, Recovery Mode | Nâng cao |
| 5.9 | [admin/08-quan-ly-nguoi-dung.md](./admin/08-quan-ly-nguoi-dung.md) | Users, Roles & Capabilities, Profile, Sessions | Nâng cao |
| 5.10 | [admin/09-cong-cu.md](./admin/09-cong-cu.md) | Import, Export, Site Health, GDPR Tools | Trung bình |
| 5.11 | [admin/10-cai-dat.md](./admin/10-cai-dat.md) | 7 trang Settings, Settings API, Custom Settings Page | Nâng cao |

---

### Giai đoạn 6: Chuyên Sâu (Tuần 17-20+)

> Mục tiêu: Database, REST API, Gutenberg, Bảo mật, Hiệu năng, CLI.

| Bước | File | Nội dung | Độ khó |
|------|------|----------|--------|
| 6.1 | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Schema database, $wpdb, WP_Query, Meta Query, Tax Query | Trung bình |
| 6.2 | [DATABASE_SCHEMA_CHI_TIET.md](./DATABASE_SCHEMA_CHI_TIET.md) | Phân tích chi tiết từng bảng, từng cột từ schema.php, ERD, Roles | Nâng cao |
| 6.3 | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | register_post_type, register_taxonomy, Meta Boxes, Admin Columns | Trung bình |
| 6.4 | [REST_API.md](./REST_API.md) | Endpoints, Authentication, Custom Endpoints, Controller, CRUD API | Nâng cao |
| 6.5 | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Custom Block, Attributes, InspectorControls, Dynamic Blocks | Nâng cao |
| 6.6 | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Sanitize, Escape, Nonces, SQL Injection, XSS, Security Constants | Nâng cao |
| 6.7 | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Object Cache, Page Cache, Transients, DB Optimization, CDN | Nâng cao |
| 6.8 | [WP_CLI.md](./WP_CLI.md) | Cài đặt, lệnh cơ bản, scaffold, search-replace, Custom Command | Trung bình |

---

## Bản Đồ Học Tập

```
 GIAI ĐOẠN 1: NỀN TẢNG
 ┌─────────────────────────────────────────────────────────┐
 │  WORDPRESS_LEARNING_PATH  →  Lộ trình tổng quan         │
 │  CAU_TRUC_SOURCE_CODE     →  Cấu trúc thư mục WP       │
 │  WORDPRESS_FLOW           →  Luồng xử lý request        │
 │  WORDPRESS_ROUTING        →  Hệ thống routing            │
 └────────────────────────────┬────────────────────────────┘
                              │
                              ▼
 GIAI ĐOẠN 2: HOOKS (trái tim WordPress)
 ┌─────────────────────────────────────────────────────────┐
 │  WORDPRESS_HOOKS          →  Tổng quan nhanh             │
 │  hooks/01-hooks-co-ban    →  Cơ bản: add_action/filter   │
 │  hooks/02-action-hooks    →  Action hooks quan trọng     │
 │  hooks/03-filter-hooks    →  Filter hooks quan trọng     │
 │  hooks/04-lifecycle       →  Vòng đời request            │
 │  hooks/05-custom-hooks    →  Tạo hooks riêng             │
 │  hooks/06-trong-plugin    →  Hooks trong plugin          │
 │  hooks/07-nang-cao        →  Kỹ thuật nâng cao           │
 └───────────┬─────────────────────────────┬───────────────┘
             │                             │
             ▼                             ▼
 GIAI ĐOẠN 3: PLUGIN                GIAI ĐOẠN 4: THEME
 ┌───────────────────────┐          ┌───────────────────────┐
 │  TAO_PLUGIN_CO_BAN    │          │  HUONG_DAN_THEME      │
 │  plugins/01 cơ bản    │          │  TAO_THEME_CO_BAN     │
 │  plugins/02 menu      │          │  themes/01 cơ bản     │
 │  plugins/03 shortcode │          │  themes/02 hierarchy  │
 │  plugins/04 database  │          │  themes/03 loop       │
 │  plugins/05 ajax/rest │          │  themes/04 menus      │
 │  plugins/06 oop       │          │  themes/05 customizer │
 │  plugins/07 bảo mật   │          │  themes/06 block/FSE  │
 │  plugins/08 nâng cao  │          │  themes/07 nâng cao   │
 └───────────┬───────────┘          └───────────┬───────────┘
             │                                  │
             └──────────────┬───────────────────┘
                            ▼
 GIAI ĐOẠN 5: QUẢN LÝ ADMIN
 ┌─────────────────────────────────────────────────────────┐
 │  admin/01 tổng quan       →  Bootstrap, Menu, Setup      │
 │  admin/02 dashboard       →  Dashboard Widgets            │
 │  admin/03 bài viết        →  Posts, Editor, Meta Boxes    │
 │  admin/04 media           →  Upload, Image Sizes          │
 │  admin/05 bình luận       →  Comments, Moderation         │
 │  admin/06 giao diện       →  Themes, Customizer, Menus    │
 │  admin/07 plugin          →  Activate, Install, Recovery  │
 │  admin/08 người dùng      →  Users, Roles, Capabilities   │
 │  admin/09 công cụ         →  Import, Export, Site Health   │
 │  admin/10 cài đặt         →  Settings API, 7 trang cài đặt│
 └────────────────────────────┬────────────────────────────┘
                              ▼
 GIAI ĐOẠN 6: CHUYÊN SÂU
 ┌─────────────────────────────────────────────────────────┐
 │  DATABASE_VA_WP_QUERY        →  Database & truy vấn      │
 │  DATABASE_SCHEMA_CHI_TIET    →  Chi tiết schema từng cột  │
 │  CUSTOM_POST_TYPE_TAXONOMY   →  CPT & Taxonomy           │
 │  REST_API                    →  REST API đầy đủ          │
 │  GUTENBERG_BLOCK_EDITOR      →  Gutenberg Block          │
 │  BAO_MAT_WORDPRESS           →  Bảo mật tổng hợp        │
 │  HIEU_NANG_TOI_UU            →  Tối ưu hiệu năng        │
 │  WP_CLI                      →  Công cụ dòng lệnh        │
 └─────────────────────────────────────────────────────────┘
```

---

## Danh Sách Toàn Bộ Files (50 files)

### Thư mục gốc `/wp-study/` (17 files)

| STT | File | Nội dung | Dòng |
|-----|------|----------|------|
| 1 | [INDEX.md](./INDEX.md) | File điều hướng này | - |
| 2 | [WORDPRESS_LEARNING_PATH.md](./WORDPRESS_LEARNING_PATH.md) | Lộ trình học 8 giai đoạn | ~182 |
| 3 | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phân tích cấu trúc source code WordPress | ~2,054 |
| 4 | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luồng xử lý request trong WordPress | ~528 |
| 5 | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | Hệ thống routing trong WordPress | ~573 |
| 6 | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Tổng quan Hooks (Action & Filter) | ~836 |
| 7 | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Hướng dẫn download, cài theme có sẵn | ~409 |
| 8 | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Tổng hợp tạo theme cơ bản | ~1,562 |
| 9 | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Tổng hợp tạo plugin cơ bản | ~1,381 |
| 10 | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Database, $wpdb, WP_Query, Meta Query | ~2,802 |
| 11 | [DATABASE_SCHEMA_CHI_TIET.md](./DATABASE_SCHEMA_CHI_TIET.md) | Chi tiết từng bảng, từng cột, ERD, Roles | ~1,200 |
| 12 | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | Custom Post Types & Taxonomies | ~1,660 |
| 13 | [REST_API.md](./REST_API.md) | WordPress REST API đầy đủ | ~2,941 |
| 14 | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Gutenberg Block Editor | ~2,808 |
| 15 | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Bảo mật WordPress | ~2,350 |
| 16 | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Tối ưu hiệu năng | ~2,109 |
| 17 | [WP_CLI.md](./WP_CLI.md) | WP-CLI công cụ dòng lệnh | ~2,018 |

### Thư mục `hooks/` (7 files)

| STT | File | Nội dung | Dòng |
|-----|------|----------|------|
| 18 | [01-hooks-co-ban.md](./hooks/01-hooks-co-ban.md) | Hooks cơ bản, add_action, add_filter, priority | ~1,277 |
| 19 | [02-action-hooks-quan-trong.md](./hooks/02-action-hooks-quan-trong.md) | Danh sách Action Hooks quan trọng nhất | ~2,057 |
| 20 | [03-filter-hooks-quan-trong.md](./hooks/03-filter-hooks-quan-trong.md) | Danh sách Filter Hooks quan trọng nhất | ~1,593 |
| 21 | [04-hooks-lifecycle.md](./hooks/04-hooks-lifecycle.md) | Vòng đời hooks trong WordPress | ~988 |
| 22 | [05-custom-hooks.md](./hooks/05-custom-hooks.md) | Tạo Custom Hooks riêng | ~1,658 |
| 23 | [06-hooks-trong-plugin.md](./hooks/06-hooks-trong-plugin.md) | Best practices hooks trong plugin | ~1,593 |
| 24 | [07-hooks-nang-cao.md](./hooks/07-hooks-nang-cao.md) | Hooks nâng cao: OOP, WP_Hook, testing | ~1,709 |

### Thư mục `plugins/` (8 files)

| STT | File | Nội dung | Dòng |
|-----|------|----------|------|
| 25 | [01-plugin-co-ban.md](./plugins/01-plugin-co-ban.md) | Plugin cơ bản, Headers, Hello World | ~1,120 |
| 26 | [02-menu-va-settings-api.md](./plugins/02-menu-va-settings-api.md) | Admin Menu, Settings API, Tabs, Fields | ~2,252 |
| 27 | [03-shortcodes-va-widgets.md](./plugins/03-shortcodes-va-widgets.md) | Shortcodes, Widgets API, WP_Widget | ~1,838 |
| 28 | [04-database-va-crud.md](./plugins/04-database-va-crud.md) | $wpdb, Custom Tables, CRUD hoàn chỉnh | ~1,802 |
| 29 | [05-ajax-va-rest-api.md](./plugins/05-ajax-va-rest-api.md) | WordPress AJAX, REST API trong plugin | ~1,545 |
| 30 | [06-plugin-oop-architecture.md](./plugins/06-plugin-oop-architecture.md) | Singleton, Autoloading, MVC, Boilerplate | ~1,666 |
| 31 | [07-bao-mat-plugin.md](./plugins/07-bao-mat-plugin.md) | Bảo mật plugin: Sanitize, Escape, Nonces | ~1,441 |
| 32 | [08-plugin-nang-cao.md](./plugins/08-plugin-nang-cao.md) | CPT, Cron, Email, i18n, Testing, Packaging | ~1,565 |

### Thư mục `themes/` (7 files)

| STT | File | Nội dung | Dòng |
|-----|------|----------|------|
| 33 | [01-theme-co-ban.md](./themes/01-theme-co-ban.md) | Theme cơ bản, style.css, functions.php | ~2,045 |
| 34 | [02-template-hierarchy.md](./themes/02-template-hierarchy.md) | Template Hierarchy, conditional tags | ~2,239 |
| 35 | [03-the-loop-va-wp-query.md](./themes/03-the-loop-va-wp-query.md) | The Loop, WP_Query, Pagination | ~1,876 |
| 36 | [04-menus-widgets-sidebars.md](./themes/04-menus-widgets-sidebars.md) | Menus, Walker, Sidebars, Widget Areas | ~1,905 |
| 37 | [05-customizer-api.md](./themes/05-customizer-api.md) | Theme Customizer API, Controls, Preview | ~1,700 |
| 38 | [06-block-theme-va-fse.md](./themes/06-block-theme-va-fse.md) | Block Theme, theme.json, FSE | ~1,841 |
| 39 | [07-theme-nang-cao.md](./themes/07-theme-nang-cao.md) | Child Theme, WooCommerce, a11y, Packaging | ~1,709 |

### Thư mục `admin/` (11 files)

| STT | File | Nội dung | Dòng |
|-----|------|----------|------|
| 40 | [INDEX.md](./admin/INDEX.md) | Mục lục điều hướng phần Admin | ~222 |
| 41 | [01-tong-quan-admin.md](./admin/01-tong-quan-admin.md) | Tổng quan WP Admin, Bootstrap, Menu, Setup | ~2,051 |
| 42 | [02-dashboard.md](./admin/02-dashboard.md) | Dashboard, Widgets, Screen Options | ~1,280 |
| 43 | [03-quan-ly-bai-viet.md](./admin/03-quan-ly-bai-viet.md) | Posts, Editor, Meta Boxes, Custom Columns | ~1,996 |
| 44 | [04-quan-ly-media.md](./admin/04-quan-ly-media.md) | Media Library, Upload flow, Image Sizes | ~1,352 |
| 45 | [05-quan-ly-binh-luan.md](./admin/05-quan-ly-binh-luan.md) | Comments, Moderation, Reply, Anti-spam | ~1,323 |
| 46 | [06-giao-dien.md](./admin/06-giao-dien.md) | Themes, Customizer, Widgets, Menus, FSE | ~1,660 |
| 47 | [07-quan-ly-plugin.md](./admin/07-quan-ly-plugin.md) | Plugins, Activate/Deactivate, Install, Recovery | ~1,595 |
| 48 | [08-quan-ly-nguoi-dung.md](./admin/08-quan-ly-nguoi-dung.md) | Users, Roles & Capabilities, Profile, Sessions | ~1,767 |
| 49 | [09-cong-cu.md](./admin/09-cong-cu.md) | Import, Export, Site Health, GDPR Tools | ~1,340 |
| 50 | [10-cai-dat.md](./admin/10-cai-dat.md) | 7 trang Settings, Settings API, Custom Page | ~2,158 |

---

## Cấu Trúc Thư Mục

```
wp-study/
│
├── INDEX.md                          ← BẠN ĐANG Ở ĐÂY
│
├── ─── GIAI ĐOẠN 1: NỀN TẢNG ────────────────────────
├── WORDPRESS_LEARNING_PATH.md        ← Lộ trình học tập
├── CAU_TRUC_SOURCE_CODE.md           ← Cấu trúc source code WP
├── WORDPRESS_FLOW.md                 ← Luồng xử lý request
├── WORDPRESS_ROUTING.md              ← Hệ thống routing
│
├── ─── GIAI ĐOẠN 2: HOOKS ────────────────────────────
├── WORDPRESS_HOOKS.md                ← Tổng quan hooks
├── hooks/
│   ├── 01-hooks-co-ban.md            ← Hooks cơ bản
│   ├── 02-action-hooks-quan-trong.md ← Action Hooks quan trọng
│   ├── 03-filter-hooks-quan-trong.md ← Filter Hooks quan trọng
│   ├── 04-hooks-lifecycle.md         ← Vòng đời hooks
│   ├── 05-custom-hooks.md            ← Tạo hooks riêng
│   ├── 06-hooks-trong-plugin.md      ← Hooks trong plugin
│   └── 07-hooks-nang-cao.md          ← Hooks nâng cao
│
├── ─── GIAI ĐOẠN 3: PLUGIN ───────────────────────────
├── TAO_PLUGIN_CO_BAN.md              ← Tổng hợp tạo plugin
├── plugins/
│   ├── 01-plugin-co-ban.md           ← Plugin cơ bản
│   ├── 02-menu-va-settings-api.md    ← Admin Menu & Settings
│   ├── 03-shortcodes-va-widgets.md   ← Shortcodes & Widgets
│   ├── 04-database-va-crud.md        ← Database & CRUD
│   ├── 05-ajax-va-rest-api.md        ← AJAX & REST API
│   ├── 06-plugin-oop-architecture.md ← Kiến trúc OOP
│   ├── 07-bao-mat-plugin.md          ← Bảo mật plugin
│   └── 08-plugin-nang-cao.md         ← Plugin nâng cao
│
├── ─── GIAI ĐOẠN 4: THEME ────────────────────────────
├── HUONG_DAN_THEME.md                ← Hướng dẫn dùng theme
├── TAO_THEME_CO_BAN.md               ← Tổng hợp tạo theme
├── themes/
│   ├── 01-theme-co-ban.md            ← Theme cơ bản
│   ├── 02-template-hierarchy.md      ← Template Hierarchy
│   ├── 03-the-loop-va-wp-query.md    ← The Loop & WP_Query
│   ├── 04-menus-widgets-sidebars.md  ← Menus, Widgets, Sidebars
│   ├── 05-customizer-api.md          ← Customizer API
│   ├── 06-block-theme-va-fse.md      ← Block Theme & FSE
│   └── 07-theme-nang-cao.md          ← Theme nâng cao
│
├── ─── GIAI ĐOẠN 5: QUẢN LÝ ADMIN ──────────────────────
├── admin/
│   ├── INDEX.md                      ← Mục lục phần Admin
│   ├── 01-tong-quan-admin.md         ← Tổng quan, Bootstrap, Setup
│   ├── 02-dashboard.md               ← Dashboard & Widgets
│   ├── 03-quan-ly-bai-viet.md        ← Quản lý bài viết & Editor
│   ├── 04-quan-ly-media.md           ← Quản lý Media & Upload
│   ├── 05-quan-ly-binh-luan.md       ← Quản lý bình luận
│   ├── 06-giao-dien.md               ← Themes, Customizer, Menus
│   ├── 07-quan-ly-plugin.md          ← Quản lý Plugins
│   ├── 08-quan-ly-nguoi-dung.md      ← Users, Roles, Capabilities
│   ├── 09-cong-cu.md                 ← Tools, Site Health, GDPR
│   └── 10-cai-dat.md                 ← 7 trang Settings
│
├── ─── GIAI ĐOẠN 6: CHUYÊN SÂU ───────────────────────
├── DATABASE_VA_WP_QUERY.md           ← Database & WP_Query
├── DATABASE_SCHEMA_CHI_TIET.md       ← Chi tiết schema từng cột
├── CUSTOM_POST_TYPE_TAXONOMY.md      ← CPT & Taxonomy
├── REST_API.md                       ← REST API
├── GUTENBERG_BLOCK_EDITOR.md         ← Gutenberg Block Editor
├── BAO_MAT_WORDPRESS.md              ← Bảo mật WordPress
├── HIEU_NANG_TOI_UU.md               ← Tối ưu hiệu năng
└── WP_CLI.md                         ← WP-CLI
```

---

## So Sánh Nhanh: Laravel vs WordPress

| Khái niệm | Laravel | WordPress |
|-----------|---------|-----------|
| **Entry point** | `public/index.php` | `index.php` → `wp-blog-header.php` |
| **Routing** | `routes/web.php` | Rewrite Rules + Template Hierarchy |
| **Controller** | `App\Http\Controllers` | Template files (single.php, page.php...) |
| **Views** | Blade templates | PHP template files |
| **Models** | Eloquent ORM | `$wpdb` + `WP_Query` |
| **Events** | Events / Listeners | Action Hooks / Filter Hooks |
| **Service Provider** | `App\Providers` | Plugins |
| **Middleware** | `App\Http\Middleware` | Hooks: `init`, `template_redirect`... |
| **Package** | Composer packages | Plugins |
| **Config** | `.env` + `config/` | `wp-config.php` + Options API |
| **CLI** | `php artisan` | `wp` (WP-CLI) |
| **Migration** | Migration files | `dbDelta()` |
| **Cache** | `Cache::remember()` | `get_transient()` / Object Cache |
| **Auth** | Auth scaffolding | Built-in user system |
| **API** | API Resources | REST API + `register_rest_route()` |
| **Validation** | Form Request | Tự viết (sanitize + validate) |
| **Queue** | Jobs / Queue | WP Cron + Action Scheduler |

---

## Thống Kê

| Thông tin | Giá trị |
|-----------|---------|
| Tổng số file MD | 50 (17 gốc + 7 hooks + 8 plugins + 7 themes + 11 admin) |
| Tổng số dòng | ~80,000+ |
| Folders | 4 (hooks/, plugins/, themes/, admin/) |
| Ngôn ngữ | Tiếng Việt có dấu |
| Code examples | Có trong mọi file, copy-paste chạy được |
| So sánh Laravel | Có trong hầu hết các file |
| Cập nhật lần cuối | 02/2026 |
