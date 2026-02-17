# Phần 1: Nền Tảng WordPress

> Hiểu WordPress hoạt động như thế nào từ bên trong. Đây là nền tảng quan trọng nhất trước khi bắt đầu phát triển theme và plugin.

---

## Mục Lục

| STT | File | Nội dung chi tiết | Độ khó |
|-----|------|-------------------|--------|
| 1 | [cau-truc-source-code.md](./cau-truc-source-code.md) | Phân tích cấu trúc thư mục WordPress (`wp-admin`, `wp-content`, `wp-includes`), các file chính, design patterns được sử dụng, và cách đọc hiểu source code WordPress hiệu quả. | Cơ bản |
| 2 | [luong-xu-ly-request.md](./luong-xu-ly-request.md) | Luồng xử lý request từ `index.php` đến HTML output. Bao gồm: Entry Point, Blog Header, WordPress Loader, Settings, và Template Rendering. So sánh với Laravel request lifecycle. | Cơ bản |
| 3 | [he-thong-routing.md](./he-thong-routing.md) | Hệ thống URL Rewriting của WordPress (khác hoàn toàn với routing truyền thống). Bao gồm: Query Variables, Pretty URLs, Rewrite API, và cách tích hợp với Template Hierarchy. | Cơ bản |
| 4 | [wp-cli.md](./wp-cli.md) | Công cụ dòng lệnh WP-CLI - tương đương `php artisan` trong Laravel. Bao gồm: cài đặt, các lệnh cơ bản, scaffold theme/plugin, search-replace, database operations, và cách tạo custom commands. | Trung bình |

---

## Thứ Tự Đọc Khuyến Nghị

```
cau-truc-source-code.md    Hiểu cấu trúc thư mục WordPress
        │
        ▼
luong-xu-ly-request.md     Hiểu luồng xử lý từ request → response
        │
        ▼
he-thong-routing.md        Hiểu cách WordPress xử lý URL
        │
        ▼
wp-cli.md                  Sử dụng công cụ dòng lệnh để tăng tốc phát triển
```

---

## So Sánh Nhanh Với Laravel

| Khái niệm | Laravel | WordPress |
|-----------|---------|-----------|
| Entry point | `public/index.php` | `index.php` → `wp-blog-header.php` |
| Routing | `routes/web.php` | Rewrite Rules + Template Hierarchy |
| Config | `.env` + `config/` | `wp-config.php` + Options API |
| CLI | `php artisan` | `wp` (WP-CLI) |
| Cấu trúc | `app/`, `resources/`, `routes/` | `wp-admin/`, `wp-content/`, `wp-includes/` |

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Hiểu rõ cấu trúc thư mục WordPress và vai trò từng thư mục
- [ ] Nắm được luồng xử lý request từ đầu đến cuối
- [ ] Hiểu hệ thống URL Rewriting và Template Hierarchy
- [ ] Sử dụng thành thạo WP-CLI cho các tác vụ phát triển

---

[← Quay lại INDEX.md](../INDEX.md) | [Tiếp theo: Hệ thống Hooks →](../02-hooks/)
