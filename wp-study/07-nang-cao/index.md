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
| 5 | [05-cron-va-background-jobs.md](./05-cron-va-background-jobs.md) | WP-Cron & Action Scheduler: `wp_schedule_event()`, custom intervals, system cron, Action Scheduler API, batch processing, retry/error handling, WP-CLI cron management. So sánh với Laravel Queue/Scheduler. | Nâng cao |
| 6 | [06-multisite.md](./06-multisite.md) | WordPress Multisite: Cài đặt, subdomain vs subdirectory, `switch_to_blog()`, network options, MU plugins, cross-site query, domain mapping. So sánh với Laravel multi-tenancy. | Nâng cao |
| 7 | [07-testing-va-cicd.md](./07-testing-va-cicd.md) | Testing & CI/CD: PHPUnit + `WP_UnitTestCase`, factory helpers, Brain\Monkey mocking, `@wordpress/env`, JavaScript testing, GitHub Actions CI pipeline, deployment (SSH/rsync, WP.org SVN). So sánh với Laravel Testing. | Nâng cao |
| 8 | [08-i18n-l10n.md](./08-i18n-l10n.md) | Internationalization: `__()`, `_e()`, `_n()`, `_x()`, text domain, `.pot/.po/.mo` workflow, JavaScript i18n, RTL support, date/number localization. So sánh với Laravel Localization. | Nâng cao |
| 9 | [09-headless-wordpress.md](./09-headless-wordpress.md) | Headless WordPress: REST API optimization, WPGraphQL, Next.js integration (App Router), JWT authentication, preview mode, ISR/revalidation, SEO data, webhooks. So sánh với Laravel API backend. | Nâng cao |
| 10 | [10-rewrite-heartbeat-cache.md](./10-rewrite-heartbeat-cache.md) | Rewrite API (`add_rewrite_rule`, endpoints), Heartbeat API (real-time notifications), Object Cache nâng cao (cache groups, stampede prevention, fragment caching). So sánh với Laravel Routing/Broadcasting/Cache. | Nâng cao |

---

## Thứ Tự Đọc Khuyến Nghị

Các chủ đề **độc lập với nhau**, bạn có thể đọc theo nhu cầu:

```
01-rest-api                 ← Khi cần xây dựng API cho frontend (React, Vue, Mobile App)
02-gutenberg-block-editor   ← Khi cần tạo custom blocks cho editor
03-bao-mat-wordpress        ← Khi cần hardening & bảo vệ website
04-hieu-nang-toi-uu         ← Khi cần tối ưu tốc độ website
05-cron-va-background-jobs  ← Khi cần scheduled tasks, background processing
06-multisite                ← Khi cần quản lý network nhiều sites
07-testing-va-cicd          ← Khi cần viết tests & tự động hóa deployment
08-i18n-l10n                ← Khi cần đa ngôn ngữ cho plugin/theme
09-headless-wordpress       ← Khi cần WP làm backend cho React/Next.js
10-rewrite-heartbeat-cache  ← Khi cần custom URL, real-time, advanced caching
```

**Khuyến nghị đọc tuần tự nếu bạn đang học từ đầu:**
```
REST API → Gutenberg → Bảo mật → Hiệu năng → Cron → Testing → i18n → Multisite → Headless → Rewrite/Cache
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

### Cron & Background Jobs
- WP-Cron: `wp_schedule_event()`, custom intervals, system cron thay thế
- Action Scheduler: async actions, recurring actions, retry/error handling
- Batch processing patterns cho dữ liệu lớn

### Multisite
- Cài đặt và quản lý network nhiều sites
- `switch_to_blog()` / `restore_current_blog()` chuyển đổi giữa các sites
- Network options, MU plugins, cross-site query, domain mapping

### Testing & CI/CD
- PHPUnit + `WP_UnitTestCase` + Factory helpers
- Brain\Monkey mocking, `@wordpress/env` Docker
- GitHub Actions CI pipeline, deployment tự động

### Internationalization (i18n)
- Hàm dịch: `__()`, `_e()`, `_n()`, `_x()`, `esc_html__()`
- `.pot/.po/.mo` workflow, JavaScript i18n
- RTL support, date/number localization

### Headless WordPress
- REST API & WPGraphQL làm backend
- Next.js App Router integration, ISR/revalidation
- JWT authentication, preview mode, webhooks

### Rewrite, Heartbeat & Cache nâng cao
- Rewrite API: custom URL rules, tags, endpoints
- Heartbeat API: real-time data exchange, notifications
- Object Cache: cache groups, stampede prevention, fragment caching

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Tạo được REST API endpoints tùy chỉnh với authentication
- [ ] Tạo được custom Gutenberg blocks
- [ ] Áp dụng đầy đủ các biện pháp bảo mật cho plugin/theme
- [ ] Tối ưu được hiệu năng website WordPress
- [ ] Cấu hình WP-Cron và Action Scheduler cho background jobs
- [ ] Thiết lập và phát triển trên WordPress Multisite
- [ ] Viết unit tests và cấu hình CI/CD pipeline
- [ ] Internationalize plugin/theme cho đa ngôn ngữ
- [ ] Xây dựng headless WordPress với Next.js
- [ ] Sử dụng Rewrite API, Heartbeat API và Object Cache nâng cao

---

[← Quay lại INDEX.md](../INDEX.md)
