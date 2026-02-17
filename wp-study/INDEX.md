# Hệ Thống Học Tập WordPress

> Tài liệu học WordPress từ cơ bản đến nâng cao, viết bằng **tiếng Việt có dấu**, code examples chi tiết.
> Dành cho **PHP Laravel Developer** chuyển sang WordPress.

---

## Mục Lục Nhanh

| Phần | Thư mục | Chủ đề | Số bài |
|------|---------|--------|--------|
| 0 | [00-gioi-thieu](./00-gioi-thieu/) | Giới thiệu & Lộ trình học tập | 1 bài |
| 1 | [01-nen-tang](./01-nen-tang/) | Nền tảng WordPress (cấu trúc, luồng xử lý, routing, CLI) | 4 bài |
| 2 | [02-hooks](./02-hooks/) | Hệ thống Hooks - Trái tim WordPress | 8 bài |
| 3 | [03-database](./03-database/) | Cơ sở dữ liệu ($wpdb, WP_Query, CPT) | 3 bài |
| 4 | [04-themes](./04-themes/) | Phát triển Theme | 10 bài |
| 5 | [05-plugins](./05-plugins/) | Phát triển Plugin | 9 bài |
| 6 | [06-admin](./06-admin/) | Quản trị WordPress Admin | 10 bài |
| 7 | [07-nang-cao](./07-nang-cao/) | Nâng cao (REST API, Gutenberg, Bảo mật, Hiệu năng, Cron, Multisite, Testing, i18n, Headless, Rewrite/Cache) | 10 bài |

**Tổng cộng: 55 bài học + 8 file index điều hướng**

---

## Lộ Trình Học Tập (Đọc Theo Thứ Tự)

### Giai đoạn 1: Nền Tảng WordPress (Tuần 1-2)

> **Mục tiêu**: Hiểu WordPress hoạt động như thế nào từ bên trong.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 1.0 | [Lộ trình tổng quan](./00-gioi-thieu/lo-trinh-hoc-tap.md) | Lộ trình 8 giai đoạn, study plan 6-9 tháng | Tổng quan |
| 1.1 | [Cấu trúc source code](./01-nen-tang/cau-truc-source-code.md) | Phân tích cấu trúc thư mục WP, design patterns | Cơ bản |
| 1.2 | [Luồng xử lý request](./01-nen-tang/luong-xu-ly-request.md) | Từ `index.php` → HTML output | Cơ bản |
| 1.3 | [Hệ thống routing](./01-nen-tang/he-thong-routing.md) | URL Rewriting, Rewrite API, Template Hierarchy | Cơ bản |
| 1.4 | [WP-CLI](./01-nen-tang/wp-cli.md) | Công cụ dòng lệnh (tương đương `php artisan`) | Trung bình |

---

### Giai đoạn 2: WordPress Hooks (Tuần 3-4)

> **Mục tiêu**: Nắm vững hệ thống Hooks - trái tim của WordPress.
> **So sánh**: Hooks = Events/Listeners trong Laravel.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 2.1 | [Hooks cơ bản](./02-hooks/01-hooks-co-ban.md) | `add_action`, `add_filter`, priority, closures | Cơ bản |
| 2.2 | [Action Hooks quan trọng](./02-hooks/02-action-hooks-quan-trong.md) | `init`, `admin_menu`, `wp_enqueue_scripts`, `save_post`... | Trung bình |
| 2.3 | [Filter Hooks quan trọng](./02-hooks/03-filter-hooks-quan-trong.md) | `the_content`, `pre_get_posts`, `body_class`... | Trung bình |
| 2.4 | [Hooks Lifecycle](./02-hooks/04-hooks-lifecycle.md) | Vòng đời: Frontend, Admin, AJAX, REST, Cron | Trung bình |
| 2.5 | [Custom Hooks](./02-hooks/05-custom-hooks.md) | Tạo `do_action`, `apply_filters` riêng | Nâng cao |
| 2.6 | [Hooks trong Plugin](./02-hooks/06-hooks-trong-plugin.md) | Activation/deactivation, conditional, remove hooks | Nâng cao |
| 2.7 | [Hooks nâng cao](./02-hooks/07-hooks-nang-cao.md) | OOP callbacks, WP_Hook class, testing | Nâng cao |
| 2.8 | [★ Ví dụ thực tế](./02-hooks/08-vi-du-thuc-te.md) | 20+ ví dụ hooks copy-paste chạy được | Thực hành |

---

### Giai đoạn 3: Cơ Sở Dữ Liệu (Tuần 5-6)

> **Mục tiêu**: Thành thạo truy vấn dữ liệu trong WordPress.
> **So sánh**: `$wpdb` = DB Facade, `WP_Query` = Eloquent trong Laravel.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 3.1 | [Database & $wpdb](./03-database/01-database-va-wpdb.md) | Schema, `$wpdb`, `WP_Query`, Meta Query, Tax Query | Trung bình |
| 3.2 | [Database Schema chi tiết](./03-database/02-database-schema.md) | Phân tích từng bảng, từng cột, ERD, Roles | Nâng cao |
| 3.3 | [Custom Post Type & Taxonomy](./03-database/03-custom-post-type-taxonomy.md) | `register_post_type`, Meta Boxes, Admin Columns | Trung bình |

---

### Giai đoạn 4: Phát Triển Theme (Tuần 7-10)

> **Mục tiêu**: Tạo được theme WordPress từ đầu.
> **So sánh**: Theme = Views / Blade Templates trong Laravel.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 4.1 | [Theme cơ bản](./04-themes/01-theme-co-ban.md) | `style.css`, `functions.php`, Hello World theme | Cơ bản |
| 4.2 | [Template Hierarchy](./04-themes/02-template-hierarchy.md) | Cách WP chọn template, conditional tags | Trung bình |
| 4.3 | [The Loop & WP_Query](./04-themes/03-the-loop-va-wp-query.md) | The Loop, Custom Loops, Pagination | Trung bình |
| 4.4 | [Menus, Widgets, Sidebars](./04-themes/04-menus-widgets-sidebars.md) | Navigation Menus, Walker, Sidebars | Trung bình |
| 4.5 | [Customizer API](./04-themes/05-customizer-api.md) | Panels, Sections, Controls, Live Preview | Nâng cao |
| 4.6 | [Block Theme & FSE](./04-themes/06-block-theme-va-fse.md) | `theme.json`, Block Patterns, Full Site Editing | Nâng cao |
| 4.7 | [Theme nâng cao](./04-themes/07-theme-nang-cao.md) | Child Theme, WooCommerce, a11y, i18n, Packaging | Nâng cao |
| 4.8 | [★ Ví dụ thực tế](./04-themes/08-vi-du-thuc-te.md) | Xây theme hoàn chỉnh step-by-step | Thực hành |
| 4.9 | [★ Sơ đồ & Minh họa](./04-themes/09-so-do-va-minh-hoa.md) | Sơ đồ trực quan cấu trúc theme, Template Hierarchy, hooks | Minh họa |
| 4.10 | [★ Theme hoàn chỉnh](./04-themes/10-tao-theme-hoan-chinh.md) | Theme "Developer Blog" copy-paste ready, 16 files đầy đủ | Thực hành |

---

### Giai đoạn 5: Phát Triển Plugin (Tuần 11-14)

> **Mục tiêu**: Tạo được plugin WordPress hoàn chỉnh.
> **So sánh**: Plugin = Service Provider / Package trong Laravel.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 5.1 | [Plugin cơ bản](./05-plugins/01-plugin-co-ban.md) | Headers, cấu trúc, Activation/Deactivation, Hello World | Cơ bản |
| 5.2 | [Menu & Settings API](./05-plugins/02-menu-va-settings-api.md) | Admin Menu, Settings API, Tabs, Fields | Trung bình |
| 5.3 | [Shortcodes & Widgets](./05-plugins/03-shortcodes-va-widgets.md) | Shortcodes, nested, Widgets API, WP_Widget | Trung bình |
| 5.4 | [Database & CRUD](./05-plugins/04-database-va-crud.md) | `$wpdb`, Custom Tables, CRUD, Meta API | Trung bình |
| 5.5 | [AJAX & REST API](./05-plugins/05-ajax-va-rest-api.md) | WordPress AJAX, REST Custom Endpoints | Nâng cao |
| 5.6 | [Kiến trúc OOP](./05-plugins/06-plugin-oop-architecture.md) | Singleton, Autoloading, Namespaces, MVC | Nâng cao |
| 5.7 | [Bảo mật Plugin](./05-plugins/07-bao-mat-plugin.md) | Sanitize, Escape, Nonces, Capability Checks | Nâng cao |
| 5.8 | [Plugin nâng cao](./05-plugins/08-plugin-nang-cao.md) | CPT, Cron, Email, i18n, Unit Testing | Nâng cao |
| 5.9 | [★ Ví dụ thực tế](./05-plugins/09-vi-du-thuc-te.md) | Plugin CRUD hoàn chỉnh + REST API + OOP | Thực hành |

---

### Giai đoạn 6: Quản Trị WordPress (Tuần 15-18)

> **Mục tiêu**: Hiểu tường tận phần Admin Dashboard và cách quản lý WordPress.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 6.1 | [Tổng quan Admin](./06-admin/01-tong-quan-admin.md) | Bootstrap flow, Menu, Hooks, Setup cấu hình | Cơ bản |
| 6.2 | [Dashboard](./06-admin/02-dashboard.md) | Dashboard widgets, Screen Options | Trung bình |
| 6.3 | [Quản lý Bài viết](./06-admin/03-quan-ly-bai-viet.md) | Posts, Editor, Meta Boxes, Custom Columns | Trung bình |
| 6.4 | [Quản lý Media](./06-admin/04-quan-ly-media.md) | Upload, Image Sizes, Media Library | Trung bình |
| 6.5 | [Quản lý Bình luận](./06-admin/05-quan-ly-binh-luan.md) | Comments, Moderation, Anti-spam | Trung bình |
| 6.6 | [Giao diện](./06-admin/06-giao-dien.md) | Themes, Customizer, Widgets, Menus, FSE | Nâng cao |
| 6.7 | [Quản lý Plugins](./06-admin/07-quan-ly-plugin.md) | Install, Activate, Recovery Mode | Nâng cao |
| 6.8 | [Quản lý Người dùng](./06-admin/08-quan-ly-nguoi-dung.md) | Users, Roles, Capabilities, Sessions | Nâng cao |
| 6.9 | [Công cụ](./06-admin/09-cong-cu.md) | Import, Export, Site Health, GDPR Tools | Trung bình |
| 6.10 | [Cài đặt](./06-admin/10-cai-dat.md) | 7 trang Settings, Settings API | Nâng cao |

---

### Giai đoạn 7: Chuyên Sâu (Tuần 19+)

> **Mục tiêu**: REST API, Gutenberg, Bảo mật, Hiệu năng - nâng cao kỹ năng chuyên sâu.

| Bước | Bài học | Nội dung | Độ khó |
|------|---------|----------|--------|
| 7.1 | [REST API](./07-nang-cao/01-rest-api.md) | Endpoints, Authentication, Custom Endpoints, CRUD | Nâng cao |
| 7.2 | [Gutenberg Block Editor](./07-nang-cao/02-gutenberg-block-editor.md) | Custom Blocks, Attributes, Dynamic Blocks, Patterns | Nâng cao |
| 7.3 | [Bảo mật WordPress](./07-nang-cao/03-bao-mat-wordpress.md) | Sanitize, Escape, Nonces, SQL Injection, XSS | Nâng cao |
| 7.4 | [Tối ưu Hiệu năng](./07-nang-cao/04-hieu-nang-toi-uu.md) | Object Cache, Page Cache, CDN, Profiling | Nâng cao |
| 7.5 | [Cron & Background Jobs](./07-nang-cao/05-cron-va-background-jobs.md) | WP-Cron, Action Scheduler, Batch Processing | Nâng cao |
| 7.6 | [Multisite](./07-nang-cao/06-multisite.md) | Network Setup, switch_to_blog, MU Plugins, Domain Mapping | Nâng cao |
| 7.7 | [Testing & CI/CD](./07-nang-cao/07-testing-va-cicd.md) | PHPUnit, WP_UnitTestCase, GitHub Actions, Deployment | Nâng cao |
| 7.8 | [Internationalization](./07-nang-cao/08-i18n-l10n.md) | i18n Functions, .pot/.po/.mo, JS i18n, RTL | Nâng cao |
| 7.9 | [Headless WordPress](./07-nang-cao/09-headless-wordpress.md) | WPGraphQL, Next.js, JWT, ISR, Preview Mode | Nâng cao |
| 7.10 | [Rewrite, Heartbeat & Cache](./07-nang-cao/10-rewrite-heartbeat-cache.md) | Rewrite API, Heartbeat API, Object Cache nâng cao | Nâng cao |

---

## Bản Đồ Học Tập

```
 PHẦN 0: GIỚI THIỆU
 ┌─────────────────────────────────────────────────────────┐
 │  00-gioi-thieu/lo-trinh-hoc-tap.md                      │
 │  → Lộ trình tổng quan 8 giai đoạn                       │
 └────────────────────────────┬────────────────────────────┘
                              ▼
 PHẦN 1: NỀN TẢNG WORDPRESS
 ┌─────────────────────────────────────────────────────────┐
 │  01-nen-tang/                                            │
 │  ├── cau-truc-source-code   → Cấu trúc thư mục WP      │
 │  ├── luong-xu-ly-request    → Luồng request → response  │
 │  ├── he-thong-routing       → URL Rewriting & Routing   │
 │  └── wp-cli                 → Công cụ dòng lệnh         │
 └────────────────────────────┬────────────────────────────┘
                              ▼
 PHẦN 2: HỆ THỐNG HOOKS (trái tim WordPress)
 ┌─────────────────────────────────────────────────────────┐
 │  02-hooks/                                               │
 │  ├── 01 Hooks cơ bản        → add_action, add_filter    │
 │  ├── 02 Action Hooks        → init, save_post, wp_head  │
 │  ├── 03 Filter Hooks        → the_content, pre_get_posts│
 │  ├── 04 Hooks Lifecycle     → Vòng đời thực thi         │
 │  ├── 05 Custom Hooks        → Tạo hooks riêng           │
 │  ├── 06 Hooks trong Plugin  → Best practices             │
 │  ├── 07 Hooks nâng cao      → OOP, WP_Hook, testing     │
 │  └── 08 ★ Ví dụ thực tế    → 20+ ví dụ copy-paste      │
 └────────────────────────────┬────────────────────────────┘
                              ▼
 PHẦN 3: CƠ SỞ DỮ LIỆU
 ┌─────────────────────────────────────────────────────────┐
 │  03-database/                                            │
 │  ├── 01 Database & $wpdb    → Schema, WP_Query, Meta    │
 │  ├── 02 Schema chi tiết     → Từng bảng, từng cột, ERD │
 │  └── 03 CPT & Taxonomy      → Custom Post Types         │
 └───────────┬─────────────────────────────┬───────────────┘
             ▼                             ▼
 PHẦN 4: THEME                    PHẦN 5: PLUGIN
 ┌───────────────────────┐        ┌───────────────────────┐
 │  04-themes/            │        │  05-plugins/           │
 │  ├── 01 Cơ bản        │        │  ├── 01 Cơ bản        │
 │  ├── 02 Hierarchy     │        │  ├── 02 Menu/Settings │
 │  ├── 03 The Loop      │        │  ├── 03 Shortcodes    │
 │  ├── 04 Menus/Widgets │        │  ├── 04 Database/CRUD │
 │  ├── 05 Customizer    │        │  ├── 05 AJAX/REST     │
 │  ├── 06 Block/FSE     │        │  ├── 06 OOP           │
 │  ├── 07 Nâng cao      │        │  ├── 07 Bảo mật       │
 │  ├── 08 ★ Ví dụ      │        │  ├── 08 Nâng cao      │
 │  ├── 09 ★ Sơ đồ     │        │  └── 09 ★ Ví dụ      │
 │  └── 10 ★ Theme đầy đủ│
 └───────────┬────────────┘        └───────────┬───────────┘
             └──────────────┬──────────────────┘
                            ▼
 PHẦN 6: QUẢN TRỊ ADMIN
 ┌─────────────────────────────────────────────────────────┐
 │  06-admin/                                               │
 │  ├── 01 Tổng quan          → Bootstrap, Menu, Setup     │
 │  ├── 02 Dashboard          → Dashboard Widgets           │
 │  ├── 03 Bài viết           → Posts, Editor, Meta Boxes  │
 │  ├── 04 Media              → Upload, Image Sizes        │
 │  ├── 05 Bình luận          → Comments, Moderation       │
 │  ├── 06 Giao diện          → Themes, Customizer, Menus  │
 │  ├── 07 Plugins            → Install, Activate, Recovery│
 │  ├── 08 Người dùng         → Users, Roles, Capabilities │
 │  ├── 09 Công cụ            → Import, Export, Site Health │
 │  └── 10 Cài đặt            → Settings API, 7 trang      │
 └────────────────────────────┬────────────────────────────┘
                              ▼
 PHẦN 7: CHUYÊN SÂU
 ┌─────────────────────────────────────────────────────────┐
 │  07-nang-cao/                                            │
 │  ├── 01 REST API            → Endpoints, Authentication │
 │  ├── 02 Gutenberg           → Custom Blocks, theme.json │
 │  ├── 03 Bảo mật             → Sanitize, Escape, Nonces │
 │  ├── 04 Hiệu năng           → Cache, CDN, Profiling    │
 │  ├── 05 Cron & Jobs         → WP-Cron, Action Scheduler│
 │  ├── 06 Multisite           → Network, MU Plugins      │
 │  ├── 07 Testing & CI/CD    → PHPUnit, GitHub Actions   │
 │  ├── 08 i18n/l10n           → Đa ngôn ngữ, RTL        │
 │  ├── 09 Headless WP         → Next.js, WPGraphQL       │
 │  └── 10 Rewrite/HB/Cache   → URL Rules, Heartbeat     │
 └─────────────────────────────────────────────────────────┘
```

---

## So Sánh Nhanh: Laravel vs WordPress

| Khái niệm | Laravel | WordPress |
|-----------|---------|-----------|
| **Entry point** | `public/index.php` | `index.php` → `wp-blog-header.php` |
| **Routing** | `routes/web.php` | Rewrite Rules + Template Hierarchy |
| **Controller** | `App\Http\Controllers` | Template files (`single.php`, `page.php`...) |
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
| **Validation** | Form Request | Tự viết (`sanitize_*` + validate) |
| **Queue** | Jobs / Queue | WP Cron + Action Scheduler |

---

## Cấu Trúc Thư Mục

```
wp-study/
│
├── INDEX.md                          ← BẠN ĐANG Ở ĐÂY
│
├── 00-gioi-thieu/                    ← Giới thiệu & Lộ trình
│   ├── index.md                      ← Mục lục phần giới thiệu
│   └── lo-trinh-hoc-tap.md          ← Lộ trình học 8 giai đoạn
│
├── 01-nen-tang/                      ← Nền tảng WordPress
│   ├── index.md                      ← Mục lục phần nền tảng
│   ├── cau-truc-source-code.md      ← Cấu trúc source code WP
│   ├── luong-xu-ly-request.md       ← Luồng xử lý request
│   ├── he-thong-routing.md          ← Hệ thống routing
│   └── wp-cli.md                    ← WP-CLI
│
├── 02-hooks/                         ← Hệ thống Hooks
│   ├── index.md                      ← Tổng quan & mục lục hooks
│   ├── 01-hooks-co-ban.md           ← Hooks cơ bản
│   ├── 02-action-hooks-quan-trong.md← Action Hooks
│   ├── 03-filter-hooks-quan-trong.md← Filter Hooks
│   ├── 04-hooks-lifecycle.md        ← Vòng đời hooks
│   ├── 05-custom-hooks.md           ← Tạo hooks riêng
│   ├── 06-hooks-trong-plugin.md     ← Hooks trong plugin
│   ├── 07-hooks-nang-cao.md         ← Hooks nâng cao
│   └── 08-vi-du-thuc-te.md         ← ★ Ví dụ thực tế (20+ examples)
│
├── 03-database/                      ← Cơ sở dữ liệu
│   ├── index.md                      ← Mục lục phần database
│   ├── 01-database-va-wpdb.md       ← Database & $wpdb & WP_Query
│   ├── 02-database-schema.md        ← Schema chi tiết từng bảng
│   └── 03-custom-post-type-taxonomy.md ← CPT & Taxonomy
│
├── 04-themes/                        ← Phát triển Theme
│   ├── index.md                      ← Tổng quan & mục lục themes
│   ├── 01-theme-co-ban.md           ← Theme cơ bản
│   ├── 02-template-hierarchy.md     ← Template Hierarchy
│   ├── 03-the-loop-va-wp-query.md   ← The Loop & WP_Query
│   ├── 04-menus-widgets-sidebars.md ← Menus, Widgets, Sidebars
│   ├── 05-customizer-api.md         ← Customizer API
│   ├── 06-block-theme-va-fse.md     ← Block Theme & FSE
│   ├── 07-theme-nang-cao.md         ← Theme nâng cao
│   ├── 08-vi-du-thuc-te.md         ← ★ Xây theme hoàn chỉnh step-by-step
│   ├── 09-so-do-va-minh-hoa.md    ← ★ Sơ đồ trực quan & minh họa
│   └── 10-tao-theme-hoan-chinh.md ← ★ Theme "Developer Blog" copy-paste ready
│
├── 05-plugins/                       ← Phát triển Plugin
│   ├── index.md                      ← Tổng quan & mục lục plugins
│   ├── 01-plugin-co-ban.md          ← Plugin cơ bản
│   ├── 02-menu-va-settings-api.md   ← Menu & Settings API
│   ├── 03-shortcodes-va-widgets.md  ← Shortcodes & Widgets
│   ├── 04-database-va-crud.md       ← Database & CRUD
│   ├── 05-ajax-va-rest-api.md       ← AJAX & REST API
│   ├── 06-plugin-oop-architecture.md← Kiến trúc OOP
│   ├── 07-bao-mat-plugin.md         ← Bảo mật plugin
│   ├── 08-plugin-nang-cao.md        ← Plugin nâng cao
│   └── 09-vi-du-thuc-te.md        ← ★ Plugin CRUD + REST API + OOP
│
├── 06-admin/                         ← Quản trị WordPress
│   ├── index.md                      ← Mục lục phần admin
│   ├── 01-tong-quan-admin.md        ← Tổng quan Admin
│   ├── 02-dashboard.md              ← Dashboard
│   ├── 03-quan-ly-bai-viet.md      ← Quản lý bài viết
│   ├── 04-quan-ly-media.md         ← Quản lý media
│   ├── 05-quan-ly-binh-luan.md     ← Quản lý bình luận
│   ├── 06-giao-dien.md             ← Giao diện
│   ├── 07-quan-ly-plugin.md        ← Quản lý plugins
│   ├── 08-quan-ly-nguoi-dung.md    ← Quản lý người dùng
│   ├── 09-cong-cu.md               ← Công cụ
│   └── 10-cai-dat.md               ← Cài đặt
│
└── 07-nang-cao/                      ← Chủ đề nâng cao
    ├── index.md                      ← Mục lục phần nâng cao
    ├── 01-rest-api.md               ← REST API
    ├── 02-gutenberg-block-editor.md ← Gutenberg Block Editor
    ├── 03-bao-mat-wordpress.md      ← Bảo mật WordPress
    ├── 04-hieu-nang-toi-uu.md      ← Tối ưu hiệu năng
    ├── 05-cron-va-background-jobs.md ← WP-Cron & Action Scheduler
    ├── 06-multisite.md              ← WordPress Multisite
    ├── 07-testing-va-cicd.md        ← Testing & CI/CD
    ├── 08-i18n-l10n.md              ← Internationalization
    ├── 09-headless-wordpress.md     ← Headless WordPress + Next.js
    └── 10-rewrite-heartbeat-cache.md ← Rewrite, Heartbeat & Cache
```

---

## Tìm Nhanh Theo Chủ Đề

### Tôi muốn học về...

| Chủ đề | Đọc file |
|--------|----------|
| WordPress hoạt động thế nào? | [01-nen-tang/luong-xu-ly-request.md](./01-nen-tang/luong-xu-ly-request.md) |
| Hooks là gì? | [02-hooks/01-hooks-co-ban.md](./02-hooks/01-hooks-co-ban.md) |
| Cách truy vấn database? | [03-database/01-database-va-wpdb.md](./03-database/01-database-va-wpdb.md) |
| Tạo Custom Post Type? | [03-database/03-custom-post-type-taxonomy.md](./03-database/03-custom-post-type-taxonomy.md) |
| Tạo theme từ đầu? | [04-themes/01-theme-co-ban.md](./04-themes/01-theme-co-ban.md) |
| Template Hierarchy? | [04-themes/02-template-hierarchy.md](./04-themes/02-template-hierarchy.md) |
| Tạo plugin đầu tiên? | [05-plugins/01-plugin-co-ban.md](./05-plugins/01-plugin-co-ban.md) |
| Admin Menu & Settings? | [05-plugins/02-menu-va-settings-api.md](./05-plugins/02-menu-va-settings-api.md) |
| AJAX trong WordPress? | [05-plugins/05-ajax-va-rest-api.md](./05-plugins/05-ajax-va-rest-api.md) |
| REST API? | [07-nang-cao/01-rest-api.md](./07-nang-cao/01-rest-api.md) |
| Gutenberg Blocks? | [07-nang-cao/02-gutenberg-block-editor.md](./07-nang-cao/02-gutenberg-block-editor.md) |
| Bảo mật WordPress? | [07-nang-cao/03-bao-mat-wordpress.md](./07-nang-cao/03-bao-mat-wordpress.md) |
| Tối ưu hiệu năng? | [07-nang-cao/04-hieu-nang-toi-uu.md](./07-nang-cao/04-hieu-nang-toi-uu.md) |
| Roles & Capabilities? | [06-admin/08-quan-ly-nguoi-dung.md](./06-admin/08-quan-ly-nguoi-dung.md) |
| WP-CLI commands? | [01-nen-tang/wp-cli.md](./01-nen-tang/wp-cli.md) |
| Ví dụ hooks thực tế? | [02-hooks/08-vi-du-thuc-te.md](./02-hooks/08-vi-du-thuc-te.md) |
| Xây theme từ đầu? | [04-themes/08-vi-du-thuc-te.md](./04-themes/08-vi-du-thuc-te.md) |
| Sơ đồ cấu trúc theme? | [04-themes/09-so-do-va-minh-hoa.md](./04-themes/09-so-do-va-minh-hoa.md) |
| Theme copy-paste ready? | [04-themes/10-tao-theme-hoan-chinh.md](./04-themes/10-tao-theme-hoan-chinh.md) |
| Plugin CRUD + REST API? | [05-plugins/09-vi-du-thuc-te.md](./05-plugins/09-vi-du-thuc-te.md) |
| WP-Cron & Background Jobs? | [07-nang-cao/05-cron-va-background-jobs.md](./07-nang-cao/05-cron-va-background-jobs.md) |
| WordPress Multisite? | [07-nang-cao/06-multisite.md](./07-nang-cao/06-multisite.md) |
| Testing & CI/CD? | [07-nang-cao/07-testing-va-cicd.md](./07-nang-cao/07-testing-va-cicd.md) |
| Đa ngôn ngữ (i18n)? | [07-nang-cao/08-i18n-l10n.md](./07-nang-cao/08-i18n-l10n.md) |
| Headless WordPress? | [07-nang-cao/09-headless-wordpress.md](./07-nang-cao/09-headless-wordpress.md) |
| Rewrite, Heartbeat, Cache? | [07-nang-cao/10-rewrite-heartbeat-cache.md](./07-nang-cao/10-rewrite-heartbeat-cache.md) |

---

## Thống Kê

| Thông tin | Giá trị |
|-----------|---------|
| Tổng số bài học | 55 bài |
| Tổng số file index | 8 file (mỗi thư mục 1 file) |
| Thư mục | 8 (00 → 07) |
| Ngôn ngữ | Tiếng Việt có dấu |
| Code examples | Có trong mọi file, copy-paste chạy được |
| So sánh Laravel | Có trong hầu hết các file |
| Đối tượng | PHP Laravel Developer chuyển sang WordPress |
