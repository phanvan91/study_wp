# Lo Trinh Hoc WordPress Tu Co Ban Den Nang Cao

## Muc Luc

1. [Giai doan 0 - Chuan bi nen tang](#giai-doan-0--chuan-bi-nen-tang)
2. [Giai doan 1 - Lam chu WordPress Core](#giai-doan-1--lam-chu-wordpress-core)
3. [Giai doan 2 - Theme Development](#giai-doan-2--theme-development)
4. [Giai doan 3 - Plugin Development](#giai-doan-3--plugin-development)
5. [Giai doan 4 - Database va WP_Query](#giai-doan-4--database-va-wp_query)
6. [Giai doan 5 - Cong cu Dev va Workflow](#giai-doan-5--cong-cu-dev-va-workflow)
7. [Giai doan 6 - Trien khai va Van hanh](#giai-doan-6--trien-khai-va-van-hanh)
8. [Giai doan 7 - Bao mat va Hieu nang cao cap](#giai-doan-7--bao-mat-va-hieu-nang-cao-cap)
9. [Giai doan 8 - Chuyen gia va Dinh huong nghe](#giai-doan-8--chuyen-gia-va-dinh-huong-nghe)
10. [Goi y study plan (6-9 thang)](#goi-y-study-plan-69-thang)

## Tai Lieu Lien Quan

| Chu de | File | Mo ta |
|--------|------|-------|
| Luong request | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luong xu ly request tu dau den cuoi |
| Cau truc source code | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phan tich cau truc thu muc va file chinh |
| Hooks | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Action va Filter Hooks toan dien |
| Routing | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | He thong URL Rewriting va Router |
| Tao Plugin | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Huong dan tao plugin tu dau |
| Tao Theme | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Huong dan tao theme tu dau |
| Download Theme | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Download va su dung theme |
| Database | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Database schema va WP_Query |
| REST API | [REST_API.md](./REST_API.md) | WordPress REST API |
| Bao mat | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Bao mat WordPress |
| CPT va Taxonomy | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | Custom Post Types va Taxonomies |
| WP-CLI | [WP_CLI.md](./WP_CLI.md) | Dong lenh WP-CLI |
| Gutenberg | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Block Editor va Gutenberg |
| Hieu nang | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Toi uu hieu nang WordPress |

---

### Giai doan 0 -- Chuan bi nen tang

**Muc tieu:** Nam vung kien thuc nen tang truoc khi bat dau voi WordPress.

- Hieu khai niem CMS, phan biet WordPress.org vs WordPress.com.
- On HTML/CSS/JS co ban, PHP can ban (bien, ham, OOP don gian), MySQL co ban (CRUD).
- Thiet lap moi truong: PHP >= 8.x, MySQL/MariaDB, Composer, Node, WP-CLI, Docker/Local stack.
- Lam quen Git, VS Code hoac IDE yeu thich, quy tac viet README, ghi chu.

**Tai lieu:** Chua co file rieng, tham khao tai lieu PHP va MySQL chinh thuc.

---

### Giai doan 1 -- Lam chu WordPress Core

**Muc tieu:** Hieu ro cach WordPress hoat dong tu ben trong.

- Khao sat cau truc thu muc (`wp-admin`, `wp-content`, `wp-includes`), vong doi khoi dong WP.
- Hieu `wp-config.php`, `.htaccess`, cau hinh multisite, debug mode.
- Nam he thong hook (action/filter), loop, template tag, internationalization.
- Lam bai tap: tao vai post/page, cau hinh permalink, tao user role, su dung custom menu, widget, media.

**Tai lieu:**
- [Luong xu ly request](./WORDPRESS_FLOW.md)
- [Cau truc source code](./CAU_TRUC_SOURCE_CODE.md)
- [He thong Hooks](./WORDPRESS_HOOKS.md)
- [He thong Routing](./WORDPRESS_ROUTING.md)

---

### Giai doan 2 -- Theme Development

**Muc tieu:** Co kha nang tao va tuy chinh theme WordPress.

- Template hierarchy, `functions.php`, enqueue scripts/styles dung chuan.
- Customizer API, theme options, ho tro responsive va accessibility.
- Child theme va best practices khi override template.
- Gutenberg/Block Editor: block template, `theme.json`, style variations.
- Bai tap: nhan ban theme mac dinh, them custom post type, taxonomy, template rieng cho archive/single.

**Tai lieu:**
- [Tao theme co ban](./TAO_THEME_CO_BAN.md)
- [Download va su dung theme](./HUONG_DAN_THEME.md)
- [Gutenberg Block Editor](./GUTENBERG_BLOCK_EDITOR.md)

---

### Giai doan 3 -- Plugin Development

**Muc tieu:** Viet duoc plugin WordPress hoan chinh.

- Cau truc plugin, headers, activation/deactivation hooks, uninstall.
- Hooks nang cao, shortcode, widget, REST API endpoint, admin settings page.
- Bao mat: nonce, capability check, sanitize/escape.
- Composer autoload, tach service, su dung dependency injection nhe.
- Bai tap: viet plugin CRUD don gian, them block Gutenberg custom, tao cron task.

**Tai lieu:**
- [Tao plugin co ban](./TAO_PLUGIN_CO_BAN.md)
- [He thong Hooks](./WORDPRESS_HOOKS.md)
- [REST API](./REST_API.md)
- [Bao mat WordPress](./BAO_MAT_WORDPRESS.md)

---

### Giai doan 4 -- Database va WP_Query

**Muc tieu:** Thanh thao truy van du lieu trong WordPress.

- Hieu schema core: posts, postmeta, terms, termmeta, users, usermeta, options, comments.
- `WP_Query`, `WP_Meta_Query`, `WP_Tax_Query`, `WP_User_Query`; optimize query voi index va caching.
- Custom table (dbDelta), chuan hoa du lieu, versioning schema.
- Thuc hanh viet report phuc tap, phan tich log query voi Query Monitor.

**Tai lieu:**
- [Database va WP_Query](./DATABASE_VA_WP_QUERY.md)
- [Custom Post Types va Taxonomies](./CUSTOM_POST_TYPE_TAXONOMY.md)

---

### Giai doan 5 -- Cong cu Dev va Workflow

**Muc tieu:** Xay dung workflow phat trien chuyen nghiep.

- WP-CLI: scaffold theme/plugin/test, import/export DB, search-replace.
- PHPUnit + WP test suite, integration test voi Playwright/Cypress cho frontend.
- Lint/format (PHPCS, ESLint, Prettier), husky pre-commit, CI (GitHub Actions/GitLab).
- Docker/Local WP (DevKinsta, DDEV, Lando) va cau hinh multi-environment (.env, bedrock).

**Tai lieu:**
- [WP-CLI](./WP_CLI.md)

---

### Giai doan 6 -- Trien khai va Van hanh

**Muc tieu:** Co kha nang deploy va van hanh website WordPress.

- Hosting: shared, VPS, managed WP (Kinsta, WP Engine), cau hinh Nginx/Apache, SSL, HTTP/2.
- Build pipeline: deploy script, zero-downtime, versioning DB (WP Migrate, wp-cli).
- Backup/restore, monitoring logs, su dung cron server hoac external scheduler.
- CDN, object cache (Redis/Memcached), page cache (Varnish, Nginx FastCGI), su dung plugin cache dung cach.

**Tai lieu:**
- [Toi uu hieu nang](./HIEU_NANG_TOI_UU.md)

---

### Giai doan 7 -- Bao mat va Hieu nang cao cap

**Muc tieu:** Bao ve va toi uu website WordPress o muc cao.

- Harden server, file permission, disable XML-RPC neu khong dung, su dung security headers.
- Audit plugin/theme, static analysis (Psalm, PHPStan).
- Chong spam, rate limiting, Web Application Firewall.
- Profiling hieu nang (Xdebug, Tideways, New Relic), async queue (Action Scheduler), background processing.

**Tai lieu:**
- [Bao mat WordPress](./BAO_MAT_WORDPRESS.md)
- [Toi uu hieu nang](./HIEU_NANG_TOI_UU.md)

---

### Giai doan 8 -- Chuyen gia va Dinh huong nghe

**Muc tieu:** Tro thanh chuyen gia WordPress va dong gop cho cong dong.

- Dong gop core: tham gia Make WordPress, review Gutenberg PR, internationalization.
- Thiet ke kien truc headless WP (WPGraphQL, Next.js/React).
- Multisite, multi-language (WPML, Polylang), enterprise plugin integration (WooCommerce, LMS).
- Tu duy san pham: quy trinh discovery, UX, phan tich du lieu, A/B testing.
- Mentoring, viet documentation, chia se knowledge, tham gia meetup/WordCamp.

---

### Goi y study plan (6-9 thang)

| Thoi gian | Giai doan | Muc tieu |
|-----------|-----------|----------|
| Thang 1-2 | 0-2 | Hoan thanh 2 theme demo |
| Thang 3-4 | 3-4 | Xay 2 plugin (CRUD + block) va 1 du an custom query phuc tap |
| Thang 5 | 5-6 | Setup CI/CD + moi truong staging/production demo |
| Thang 6 | 7 | Toi uu bao mat/hieu nang cho du an cu |
| Thang 7+ | 8 | Dong gop open-source, hoc headless, mentoring junior |

> **Tip:** Luon ghi chu lai kien thuc, dong goi template/plugin thanh san pham ca nhan, va dinh ky review skill de xac dinh buoc tiep theo.
