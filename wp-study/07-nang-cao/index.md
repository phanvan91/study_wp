# Phần 7: Chủ Đề Nâng Cao

> Các chủ đề chuyên sâu giúp bạn xây dựng ứng dụng WordPress chuyên nghiệp: REST API, Gutenberg Block Editor, Bảo mật, và Tối ưu hiệu năng.

---

## Mục Lục

| STT | File | Nội dung chi tiết | Độ khó |
|-----|------|-------------------|--------|
| 1 | [01-rest-api.md](./01-rest-api.md) | WordPress REST API đầy đủ: Endpoints mặc định, Authentication (Cookie, Application Passwords, OAuth), Custom Endpoints với `register_rest_route()`, REST Controller class, CRUD API hoàn chỉnh. So sánh với Laravel API Resources. | Nâng cao |
| 2 | [02-gutenberg-block-editor.md](./02-gutenberg-block-editor.md) | Gutenberg Block Editor: Tạo Custom Blocks, Block Attributes, InspectorControls, Dynamic Blocks (server-side render), Block Patterns, `theme.json` configuration, Block API. | Nâng cao |
| 3 | [03-bao-mat-wordpress.md](./03-bao-mat-wordpress.md) | Bảo mật WordPress toàn diện: Sanitization (`sanitize_*`), Escaping (`esc_*`), Validation, Nonces (CSRF), Capability Checks, SQL Injection Prevention, XSS Prevention, File Upload Security, Security Constants, Hardening. | Nâng cao |
| 4 | [04-hieu-nang-toi-uu.md](./04-hieu-nang-toi-uu.md) | Tối ưu hiệu năng: Object Cache (Redis/Memcached), Page Cache (Varnish, Nginx FastCGI), Transients API, Database Optimization, Image Optimization, Minification, CDN, Profiling (Query Monitor, Xdebug). | Nâng cao |

---

## Thứ Tự Đọc Khuyến Nghị

Bốn chủ đề này **độc lập với nhau**, bạn có thể đọc theo nhu cầu:

```
01-rest-api                 ← Khi cần xây dựng API cho frontend (React, Vue, Mobile App)
02-gutenberg-block-editor   ← Khi cần tạo custom blocks cho editor
03-bao-mat-wordpress        ← Khi cần hardening & bảo vệ website
04-hieu-nang-toi-uu         ← Khi cần tối ưu tốc độ website
```

**Khuyến nghị đọc tuần tự nếu bạn đang học từ đầu:**
```
REST API  →  Gutenberg  →  Bảo mật  →  Hiệu năng
```

---

## Tóm Tắt Từng Chủ Đề

### REST API
- WordPress cung cấp sẵn REST API tại `/wp-json/wp/v2/`
- Tạo custom endpoints với `register_rest_route()`
- Hỗ trợ CRUD đầy đủ, authentication, permission callbacks
- Nền tảng cho headless WordPress (WP + React/Vue/Next.js)

### Gutenberg Block Editor
- Editor mặc định từ WordPress 5.0+
- Tạo custom blocks bằng JavaScript (React) + PHP
- `theme.json` cấu hình toàn bộ editor settings
- Block Patterns: tổ hợp blocks có sẵn để tái sử dụng

### Bảo mật
- **Nguyên tắc vàng**: Sanitize input, Escape output, Validate everything
- Nonces chống CSRF, Capability checks chống unauthorized access
- Prepared statements chống SQL Injection
- Security headers, file permissions, disable XML-RPC

### Hiệu năng
- Object Cache (Redis/Memcached) giảm database queries
- Page Cache (Varnish/Nginx FastCGI) giảm PHP processing
- Transients API cho cache tạm thời
- CDN, image optimization, minification

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Tạo được REST API endpoints tùy chỉnh với authentication
- [ ] Tạo được custom Gutenberg blocks
- [ ] Áp dụng đầy đủ các biện pháp bảo mật cho plugin/theme
- [ ] Tối ưu được hiệu năng website WordPress

---

[← Quay lại INDEX.md](../INDEX.md)
