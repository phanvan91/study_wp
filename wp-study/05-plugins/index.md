# Phần 5: Phát Triển Plugin WordPress

> Plugin là cách mở rộng chức năng WordPress mà không cần sửa đổi core. Phần này hướng dẫn từ tạo plugin Hello World đến kiến trúc OOP phức tạp.
> Tương đương với **Service Provider / Package** trong Laravel.

---

## Mục Lục

| STT | File | Nội dung chi tiết | Độ khó |
|-----|------|-------------------|--------|
| 1 | [01-plugin-co-ban.md](./01-plugin-co-ban.md) | Plugin là gì, Plugin Headers, cấu trúc thư mục, Activation/Deactivation hooks, Uninstall, tạo plugin Hello World. So sánh với Laravel Service Provider. | Cơ bản |
| 2 | [02-menu-va-settings-api.md](./02-menu-va-settings-api.md) | Tạo Admin Menu (`add_menu_page`, `add_submenu_page`), Settings API (`register_setting`, `add_settings_section`, `add_settings_field`), Tabs, các loại field, validate & sanitize. | Trung bình |
| 3 | [03-shortcodes-va-widgets.md](./03-shortcodes-va-widgets.md) | Shortcodes (`add_shortcode`), attributes, enclosed content, nested shortcodes. Widgets API, `WP_Widget` class, đăng ký widget. So sánh Gutenberg blocks vs widgets. | Trung bình |
| 4 | [04-database-va-crud.md](./04-database-va-crud.md) | `$wpdb` global object, tạo Custom Tables với `dbDelta()`, CRUD operations hoàn chỉnh, Prepared Statements. Options API, Post Meta API, User Meta API, Transients API. So sánh Eloquent. | Trung bình |
| 5 | [05-ajax-va-rest-api.md](./05-ajax-va-rest-api.md) | WordPress AJAX: `admin-ajax.php` flow, `wp_localize_script`, nonce verification, jQuery/Fetch API. REST API custom endpoints trong plugin, CRUD API. So sánh Laravel Route/Controller. | Nâng cao |
| 6 | [06-plugin-oop-architecture.md](./06-plugin-oop-architecture.md) | Kiến trúc OOP: Singleton Pattern, Autoloading (Composer), Dependency Injection, Namespaces, MVC pattern, Plugin Boilerplate. So sánh với cấu trúc Laravel package. | Nâng cao |
| 7 | [07-bao-mat-plugin.md](./07-bao-mat-plugin.md) | Bảo mật plugin: Input Sanitization (`sanitize_*`), Output Escaping (`esc_*`), Nonces (CSRF), Capability Checks, SQL Injection Prevention, XSS Prevention, File Upload Security. | Nâng cao |
| 8 | [08-plugin-nang-cao.md](./08-plugin-nang-cao.md) | Tính năng nâng cao: Custom Post Types từ plugin, Meta Boxes, Custom Admin Columns, Cron Jobs, Email (`wp_mail`), Export/Import, Internationalization (i18n), Unit Testing, Packaging. | Nâng cao |
| 9 | [09-vi-du-thuc-te.md](./09-vi-du-thuc-te.md) | **Ví dụ thực tế**: Plugin CRUD hoàn chỉnh (Contact Manager) với admin table, pagination, search, bulk actions, export CSV. Settings Page, REST API (6 endpoints), CPT + Meta Box (Sản phẩm), Shortcode grid, Widget, Cron Job digest, kiến trúc OOP namespace. | Thực hành |

---

## Tổng Quan Nhanh

### Plugin là gì?

Plugin là một **đoạn chương trình PHP** mở rộng chức năng WordPress. Plugin có thể:
- Thêm tính năng mới (contact form, SEO, e-commerce...)
- Thay đổi hành vi mặc định
- Tích hợp với dịch vụ bên ngoài

### Cấu trúc Plugin đơn giản

```
wp-content/plugins/
└── my-plugin/
    ├── my-plugin.php       # File chính (entry point)
    ├── uninstall.php       # Xử lý khi gỡ bỏ plugin
    ├── includes/           # PHP classes
    ├── admin/              # Admin assets (CSS, JS, views)
    ├── public/             # Frontend assets
    ├── languages/          # File ngôn ngữ
    └── templates/          # Template files
```

### Plugin Header tối thiểu

```php
<?php
/**
 * Plugin Name: Tên Plugin
 * Description: Mô tả ngắn gọn
 * Version:     1.0.0
 * Author:      Tên tác giả
 * Text Domain: my-plugin
 */
```

---

## Thứ Tự Đọc Khuyến Nghị

```
01-plugin-co-ban             Tạo plugin Hello World đầu tiên
        │
        ▼
02-menu-va-settings-api      Tạo trang cài đặt trong admin
        │
        ▼
03-shortcodes-va-widgets     Tạo shortcode & widget
        │
        ▼
04-database-va-crud          Làm việc với database
        │
        ▼
05-ajax-va-rest-api          AJAX & REST API
        │
        ▼
06-plugin-oop-architecture   Nâng cấp lên kiến trúc OOP
        │
        ▼
07-bao-mat-plugin            Bảo mật plugin
        │
        ▼
08-plugin-nang-cao           CPT, Cron, Email, Testing, Packaging
        │
        ▼
09-vi-du-thuc-te             ★ Plugin CRUD hoàn chỉnh + OOP
```

---

## So Sánh Với Laravel

| WordPress Plugin | Laravel | Mô tả |
|-----------------|---------|-------|
| Plugin file header | `composer.json` | Thông tin package |
| `register_activation_hook()` | `ServiceProvider::boot()` | Khởi tạo khi kích hoạt |
| `add_action('init', ...)` | `EventServiceProvider` | Đăng ký event listeners |
| Admin Menu page | Route + Controller + View | Trang quản trị |
| Settings API | Config + Form Request | Cài đặt & validation |
| Shortcode | Blade Component | Component tái sử dụng |
| `$wpdb->insert()` | `Model::create()` | Tạo record |
| `admin-ajax.php` | API Route | AJAX endpoint |
| `register_rest_route()` | `Route::apiResource()` | REST API |
| `wp_nonce_field()` | `@csrf` | CSRF protection |
| `current_user_can()` | `Gate::allows()` / `$this->authorize()` | Authorization |

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Tạo được plugin WordPress hoàn chỉnh với admin page
- [ ] Sử dụng Settings API để tạo trang cài đặt
- [ ] Tạo shortcodes và widgets
- [ ] Thực hiện CRUD với database (custom tables và meta API)
- [ ] Xử lý AJAX và tạo REST API endpoints
- [ ] Áp dụng kiến trúc OOP cho plugin phức tạp
- [ ] Bảo mật plugin (sanitize, escape, nonce, capability check)
- [ ] Đóng gói và phân phối plugin

---

[← Quay lại INDEX.md](../INDEX.md) | [Tiếp theo: Quản trị WordPress →](../06-admin/)
