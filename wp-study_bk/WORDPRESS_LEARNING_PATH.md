# Lộ Trình Học WordPress Từ Cơ Bản Đến Nâng Cao

## Mục Lục

1. [Giai đoạn 0 - Chuẩn bị nền tảng](#giai-đoạn-0--chuẩn-bị-nền-tảng)
2. [Giai đoạn 1 - Làm chủ WordPress Core](#giai-đoạn-1--làm-chủ-wordpress-core)
3. [Giai đoạn 2 - Theme Development](#giai-đoạn-2--theme-development)
4. [Giai đoạn 3 - Plugin Development](#giai-đoạn-3--plugin-development)
5. [Giai đoạn 4 - Database và WP_Query](#giai-đoạn-4--database-và-wp_query)
6. [Giai đoạn 5 - Công cụ Dev và Workflow](#giai-đoạn-5--công-cụ-dev-và-workflow)
7. [Giai đoạn 6 - Triển khai và Vận hành](#giai-đoạn-6--triển-khai-và-vận-hành)
8. [Giai đoạn 7 - Bảo mật và Hiệu năng cao cấp](#giai-đoạn-7--bảo-mật-và-hiệu-năng-cao-cấp)
9. [Giai đoạn 8 - Chuyên gia và Định hướng nghề](#giai-đoạn-8--chuyên-gia-và-định-hướng-nghề)
10. [Gợi ý study plan (6-9 tháng)](#gợi-ý-study-plan-69-tháng)

## Tài Liệu Liên Quan

| Chủ đề | File | Mô tả |
|--------|------|-------|
| Luồng request | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luồng xử lý request từ đầu đến cuối |
| Cấu trúc source code | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phân tích cấu trúc thư mục và file chính |
| Hooks | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Action và Filter Hooks toàn diện |
| Routing | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | Hệ thống URL Rewriting và Router |
| Tạo Plugin | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Hướng dẫn tạo plugin từ đầu |
| Tạo Theme | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Hướng dẫn tạo theme từ đầu |
| Download Theme | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Download và sử dụng theme |
| Database | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Database schema và WP_Query |
| REST API | [REST_API.md](./REST_API.md) | WordPress REST API |
| Bảo mật | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Bảo mật WordPress |
| CPT và Taxonomy | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | Custom Post Types và Taxonomies |
| WP-CLI | [WP_CLI.md](./WP_CLI.md) | Dòng lệnh WP-CLI |
| Gutenberg | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Block Editor và Gutenberg |
| Hiệu năng | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Tối ưu hiệu năng WordPress |

---

### Giai đoạn 0 -- Chuẩn bị nền tảng

**Mục tiêu:** Nắm vững kiến thức nền tảng trước khi bắt đầu với WordPress.

- Hiểu khái niệm CMS, phân biệt WordPress.org vs WordPress.com.
- Ôn HTML/CSS/JS cơ bản, PHP cơ bản (biến, hàm, OOP đơn giản), MySQL cơ bản (CRUD).
- Thiết lập môi trường: PHP >= 8.x, MySQL/MariaDB, Composer, Node, WP-CLI, Docker/Local stack.
- Làm quen Git, VS Code hoặc IDE yêu thích, quy tắc viết README, ghi chú.

**Tài liệu:** Chưa có file riêng, tham khảo tài liệu PHP và MySQL chính thức.

---

### Giai đoạn 1 -- Làm chủ WordPress Core

**Mục tiêu:** Hiểu rõ cách WordPress hoạt động từ bên trong.

- Khảo sát cấu trúc thư mục (`wp-admin`, `wp-content`, `wp-includes`), vòng đời khởi động WP.
- Hiểu `wp-config.php`, `.htaccess`, cấu hình multisite, debug mode.
- Nắm hệ thống hook (action/filter), loop, template tag, internationalization.
- Làm bài tập: tạo vài post/page, cấu hình permalink, tạo user role, sử dụng custom menu, widget, media.

**Tài liệu:**
- [Luồng xử lý request](./WORDPRESS_FLOW.md)
- [Cấu trúc source code](./CAU_TRUC_SOURCE_CODE.md)
- [Hệ thống Hooks](./WORDPRESS_HOOKS.md)
- [Hệ thống Routing](./WORDPRESS_ROUTING.md)

---

### Giai đoạn 2 -- Theme Development

**Mục tiêu:** Có khả năng tạo và tùy chỉnh theme WordPress.

- Template hierarchy, `functions.php`, enqueue scripts/styles đúng chuẩn.
- Customizer API, theme options, hỗ trợ responsive và accessibility.
- Child theme và best practices khi override template.
- Gutenberg/Block Editor: block template, `theme.json`, style variations.
- Bài tập: nhân bản theme mặc định, thêm custom post type, taxonomy, template riêng cho archive/single.

**Tài liệu:**
- [Tạo theme cơ bản](./TAO_THEME_CO_BAN.md)
- [Download và sử dụng theme](./HUONG_DAN_THEME.md)
- [Gutenberg Block Editor](./GUTENBERG_BLOCK_EDITOR.md)

---

### Giai đoạn 3 -- Plugin Development

**Mục tiêu:** Viết được plugin WordPress hoàn chỉnh.

- Cấu trúc plugin, headers, activation/deactivation hooks, uninstall.
- Hooks nâng cao, shortcode, widget, REST API endpoint, admin settings page.
- Bảo mật: nonce, capability check, sanitize/escape.
- Composer autoload, tách service, sử dụng dependency injection nhẹ.
- Bài tập: viết plugin CRUD đơn giản, thêm block Gutenberg custom, tạo cron task.

**Tài liệu:**
- [Tạo plugin cơ bản](./TAO_PLUGIN_CO_BAN.md)
- [Hệ thống Hooks](./WORDPRESS_HOOKS.md)
- [REST API](./REST_API.md)
- [Bảo mật WordPress](./BAO_MAT_WORDPRESS.md)

---

### Giai đoạn 4 -- Database và WP_Query

**Mục tiêu:** Thành thạo truy vấn dữ liệu trong WordPress.

- Hiểu schema core: posts, postmeta, terms, termmeta, users, usermeta, options, comments.
- `WP_Query`, `WP_Meta_Query`, `WP_Tax_Query`, `WP_User_Query`; optimize query với index và caching.
- Custom table (dbDelta), chuẩn hóa dữ liệu, versioning schema.
- Thực hành viết report phức tạp, phân tích log query với Query Monitor.

**Tài liệu:**
- [Database và WP_Query](./DATABASE_VA_WP_QUERY.md)
- [Custom Post Types và Taxonomies](./CUSTOM_POST_TYPE_TAXONOMY.md)

---

### Giai đoạn 5 -- Công cụ Dev và Workflow

**Mục tiêu:** Xây dựng workflow phát triển chuyên nghiệp.

- WP-CLI: scaffold theme/plugin/test, import/export DB, search-replace.
- PHPUnit + WP test suite, integration test với Playwright/Cypress cho frontend.
- Lint/format (PHPCS, ESLint, Prettier), husky pre-commit, CI (GitHub Actions/GitLab).
- Docker/Local WP (DevKinsta, DDEV, Lando) và cấu hình multi-environment (.env, bedrock).

**Tài liệu:**
- [WP-CLI](./WP_CLI.md)

---

### Giai đoạn 6 -- Triển khai và Vận hành

**Mục tiêu:** Có khả năng deploy và vận hành website WordPress.

- Hosting: shared, VPS, managed WP (Kinsta, WP Engine), cấu hình Nginx/Apache, SSL, HTTP/2.
- Build pipeline: deploy script, zero-downtime, versioning DB (WP Migrate, wp-cli).
- Backup/restore, monitoring logs, sử dụng cron server hoặc external scheduler.
- CDN, object cache (Redis/Memcached), page cache (Varnish, Nginx FastCGI), sử dụng plugin cache đúng cách.

**Tài liệu:**
- [Tối ưu hiệu năng](./HIEU_NANG_TOI_UU.md)

---

### Giai đoạn 7 -- Bảo mật và Hiệu năng cao cấp

**Mục tiêu:** Bảo vệ và tối ưu website WordPress ở mức cao.

- Harden server, file permission, disable XML-RPC nếu không dùng, sử dụng security headers.
- Audit plugin/theme, static analysis (Psalm, PHPStan).
- Chống spam, rate limiting, Web Application Firewall.
- Profiling hiệu năng (Xdebug, Tideways, New Relic), async queue (Action Scheduler), background processing.

**Tài liệu:**
- [Bảo mật WordPress](./BAO_MAT_WORDPRESS.md)
- [Tối ưu hiệu năng](./HIEU_NANG_TOI_UU.md)

---

### Giai đoạn 8 -- Chuyên gia và Định hướng nghề

**Mục tiêu:** Trở thành chuyên gia WordPress và đóng góp cho cộng đồng.

- Đóng góp core: tham gia Make WordPress, review Gutenberg PR, internationalization.
- Thiết kế kiến trúc headless WP (WPGraphQL, Next.js/React).
- Multisite, multi-language (WPML, Polylang), enterprise plugin integration (WooCommerce, LMS).
- Tư duy sản phẩm: quy trình discovery, UX, phân tích dữ liệu, A/B testing.
- Mentoring, viết documentation, chia sẻ knowledge, tham gia meetup/WordCamp.

---

### Gợi ý study plan (6-9 tháng)

| Thời gian | Giai đoạn | Mục tiêu |
|-----------|-----------|----------|
| Tháng 1-2 | 0-2 | Hoàn thành 2 theme demo |
| Tháng 3-4 | 3-4 | Xây 2 plugin (CRUD + block) và 1 dự án custom query phức tạp |
| Tháng 5 | 5-6 | Setup CI/CD + môi trường staging/production demo |
| Tháng 6 | 7 | Tối ưu bảo mật/hiệu năng cho dự án cũ |
| Tháng 7+ | 8 | Đóng góp open-source, học headless, mentoring junior |

> **Tip:** Luôn ghi chú lại kiến thức, đóng gói template/plugin thành sản phẩm cá nhân, và định kỳ review skill để xác định bước tiếp theo.
