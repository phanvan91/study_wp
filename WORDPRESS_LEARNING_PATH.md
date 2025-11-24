## Lộ trình học WordPress từ cơ bản đến nâng cao

### Giai đoạn 0 – Chuẩn bị nền tảng
- Hiểu khái niệm CMS, phân biệt WordPress.org vs WordPress.com.
- Ôn HTML/CSS/JS cơ bản, PHP căn bản (biến, hàm, OOP đơn giản), MySQL cơ bản (CRUD).
- Thiết lập môi trường: PHP >= 8.x, MySQL/MariaDB, Composer, Node, WP-CLI, Docker/Local stack.
- Làm quen Git, VS Code hoặc IDE yêu thích, quy tắc viết README, ghi chú.

### Giai đoạn 1 – Làm chủ WordPress Core
- Khảo sát cấu trúc thư mục (`wp-admin`, `wp-content`, `wp-includes`), vòng đời khởi động WP.
- Hiểu `wp-config.php`, `.htaccess`, cấu hình multisite, debug mode.
- Nắm hệ thống hook (action/filter), loop, template tag, internationalization.
- Làm bài tập: tạo vài post/page, cấu hình permalink, tạo user role, sử dụng custom menu, widget, media.

### Giai đoạn 2 – Theme Development
- Template hierarchy, `functions.php`, enqueue scripts/styles đúng chuẩn.
- Customizer API, theme options, hỗ trợ responsive và accessibility.
- Child theme và best practices khi override template.
- Gutenberg/Block Editor: block template, `theme.json`, style variations.
- Bài tập: nhân bản theme mặc định, thêm custom post type, taxonomy, template riêng cho archive/single.

### Giai đoạn 3 – Plugin Development
- Cấu trúc plugin, headers, activation/deactivation hooks, uninstall.
- Hooks nâng cao, shortcode, widget, REST API endpoint, admin settings page.
- Bảo mật: nonce, capability check, sanitize/escape.
- Composer autoload, tách service, sử dụng dependency injection nhẹ.
- Bài tập: viết plugin CRUD đơn giản, thêm block Gutenberg custom, tạo cron task.

### Giai đoạn 4 – Database & WP_Query
- Hiểu schema core: posts, postmeta, terms, termmeta, users, usermeta, options, comments.
- `WP_Query`, `WP_Meta_Query`, `WP_Tax_Query`, `WP_User_Query`; optimize query với index và caching.
- Custom table (dbDelta), chuẩn hóa dữ liệu, versioning schema.
- Thực hành viết report phức tạp, phân tích log query với Query Monitor.

### Giai đoạn 5 – Công cụ Dev & Workflow
- WP-CLI: scaffold theme/plugin/test, import/export DB, search-replace.
- PHPUnit + WP test suite, integration test với Playwright/Cypress cho frontend.
- Lint/format (PHPCS, ESLint, Prettier), husky pre-commit, CI (GitHub Actions/GitLab).
- Docker/Local WP (DevKinsta, DDEV, Lando) và cấu hình multi-environment (.env, bedrock).

### Giai đoạn 6 – Triển khai & Vận hành
- Hosting: shared, VPS, managed WP (Kinsta, WP Engine), cấu hình Nginx/Apache, SSL, HTTP/2.
- Build pipeline: deploy script, zero-downtime, versioning DB (WP Migrate, wp-cli).
- Backup/restore, monitoring logs, sử dụng cron server hoặc external scheduler.
- CDN, object cache (Redis/Memcached), page cache (Varnish, Nginx FastCGI), sử dụng plugin cache đúng cách.

### Giai đoạn 7 – Bảo mật & Hiệu năng cao cấp
- Harden server, file permission, disable XML-RPC nếu không dùng, sử dụng security headers.
- Audit plugin/theme, static analysis (Psalm, PHPStan).
- Chống spam, rate limiting, Web Application Firewall.
- Profiling hiệu năng (Xdebug, Tideways, New Relic), async queue (Action Scheduler), background processing.

### Giai đoạn 8 – Chuyên gia & Định hướng nghề
- Đóng góp core: tham gia Make WordPress, review Gutenberg PR, internationalization.
- Thiết kế kiến trúc headless WP (WPGraphQL, Next.js/React).
- Multisite, multi-language (WPML, Polylang), enterprise plugin integration (WooCommerce, LMS).
- Tư duy sản phẩm: quy trình discovery, UX, phân tích dữ liệu, A/B testing.
- Mentoring, viết documentation, chia sẻ knowledge, tham gia meetup/WordCamp.

### Gợi ý study plan (6–9 tháng)
1. Tháng 1–2: Giai đoạn 0–2, hoàn thành 2 theme demo.
2. Tháng 3–4: Giai đoạn 3–4, xây 2 plugin (CRUD + block) và 1 dự án custom query phức tạp.
3. Tháng 5: Giai đoạn 5–6, setup CI/CD + môi trường staging/production demo.
4. Tháng 6: Giai đoạn 7, tối ưu bảo mật/hiệu năng cho dự án cũ.
5. Tháng 7+: Giai đoạn 8, đóng góp open-source, học headless, mentoring junior khác.

> Tip: luôn ghi chú lại kiến thức, đóng gói template/plugin thành sản phẩm cá nhân, và định kỳ review skill để xác định bước tiếp theo.

