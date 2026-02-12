# He Thong Hoc Tap WordPress

> Tai lieu hoc WordPress tu co ban den nang cao, viet bang tieng Viet, co example code minh hoa.

---

## Bat Dau Tu Dau

Neu ban moi bat dau hoc WordPress, hay doc theo thu tu sau:

1. Doc [Lo trinh hoc tap](#lo-trinh-hoc-tap) de co cai nhin tong the
2. Tim hieu [Cau truc source code](#hieu-wordpress-core) de hieu WordPress hoat dong the nao
3. Hoc [Luong xu ly request](#hieu-wordpress-core) de hieu moi request duoc xu ly ra sao
4. Tiep tuc voi [Tao theme](#theme-development) hoac [Tao plugin](#plugin-development) tuy muc tieu cua ban

---

## Muc Luc Tong Hop

### Lo Trinh Hoc Tap

| File | Mo ta |
|------|-------|
| [WORDPRESS_LEARNING_PATH.md](./WORDPRESS_LEARNING_PATH.md) | Lo trinh 8 giai doan tu co ban den chuyen gia, co goi y study plan 6-9 thang |

---

### Hieu WordPress Core

Nhom nay giup ban hieu cach WordPress hoat dong tu ben trong.

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phan tich cau truc thu muc, cac file chinh, design patterns | Co ban |
| 2 | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luong xu ly request tu index.php den HTML output | Co ban |
| 3 | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | He thong URL Rewriting, Rewrite API, Template Hierarchy | Trung binh |
| 4 | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Action Hooks, Filter Hooks, Priority, Custom Hooks | Trung binh |

**Thu tu doc khuyen nghi:** 1 → 2 → 3 → 4

---

### Theme Development

Nhom nay huong dan tao va su dung theme WordPress.

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Download, cai dat, su dung theme co san, Child Theme | Co ban |
| 2 | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Tao theme tu dau: template files, Loop, Menus, Widgets, Customizer, theme.json | Trung binh |

**Thu tu doc khuyen nghi:** 1 → 2

---

### Plugin Development

Nhom nay huong dan tao plugin WordPress.

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Tao plugin tu dau: Headers, Menu Admin, Settings API, Shortcodes, Widgets, AJAX, CPT, CRUD hoan chinh | Trung binh |

---

### Database va Truy Van

Nhom nay ve co so du lieu va cach truy van trong WordPress.

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Schema database, $wpdb, WP_Query, Meta Query, Tax Query, Custom Tables | Trung binh |
| 2 | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | register_post_type, register_taxonomy, Meta Boxes, Custom Columns | Trung binh |

**Thu tu doc khuyen nghi:** 1 → 2

---

### REST API

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [REST_API.md](./REST_API.md) | Endpoints mac dinh, Authentication, Custom Endpoints, Controller, CRUD API | Nang cao |

---

### Bao Mat

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Sanitize, Escape, Nonces, SQL Injection, XSS, File Upload, Security Constants | Trung binh |

---

### Hieu Nang

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Object Cache, Page Cache, Transients, Database Optimization, Image, CDN, Profiling | Nang cao |

---

### Block Editor (Gutenberg)

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Tao Custom Block, Attributes, InspectorControls, Dynamic Blocks, Block Patterns, theme.json | Nang cao |

---

### Cong Cu

| # | File | Mo ta | Do kho |
|---|------|-------|--------|
| 1 | [WP_CLI.md](./WP_CLI.md) | Cai dat, cac lenh co ban, scaffold, search-replace, backup, Custom Command, Automation | Trung binh |

---

## Ban Do Hoc Tap

```
                    ┌──────────────────────────┐
                    │  WORDPRESS_LEARNING_PATH  │  Bat dau o day
                    └─────────────┬────────────┘
                                  │
                    ┌─────────────▼────────────┐
                    │   CAU_TRUC_SOURCE_CODE    │  Hieu cau truc WP
                    │      WORDPRESS_FLOW       │  Hieu luong xu ly
                    └─────────────┬────────────┘
                                  │
              ┌───────────────────┼───────────────────┐
              │                   │                    │
    ┌─────────▼─────────┐ ┌──────▼──────────┐ ┌──────▼──────────┐
    │  WORDPRESS_HOOKS   │ │WORDPRESS_ROUTING│ │  BAO_MAT_WP     │
    │ (Hooks he thong)   │ │  (URL Routing)  │ │  (Bao mat)      │
    └─────────┬─────────┘ └──────┬──────────┘ └─────────────────┘
              │                  │
    ┌─────────▼──────────────────▼──────────┐
    │                                        │
    │    TAO_THEME_CO_BAN    TAO_PLUGIN_CO_BAN
    │    (Tao theme)         (Tao plugin)    │
    │                                        │
    └──────────────────┬─────────────────────┘
                       │
         ┌─────────────┼─────────────────┐
         │             │                  │
  ┌──────▼──────┐ ┌───▼────────┐  ┌─────▼──────────┐
  │DATABASE_VA  │ │ REST_API   │  │ GUTENBERG      │
  │WP_QUERY     │ │            │  │ BLOCK_EDITOR   │
  └──────┬──────┘ └────────────┘  └────────────────┘
         │
  ┌──────▼──────────────┐
  │CUSTOM_POST_TYPE     │
  │TAXONOMY             │
  └─────────────────────┘
         │
  ┌──────▼──────────────┐  ┌────────────────┐
  │ HIEU_NANG_TOI_UU    │  │   WP_CLI       │
  │ (Toi uu hieu nang)  │  │  (Cong cu)     │
  └─────────────────────┘  └────────────────┘
```

---

## Thong Ke

| Thong tin | Gia tri |
|-----------|---------|
| Tong so file | 15 |
| Tong so dong | ~19,600+ |
| Ngon ngu | Tieng Viet |
| Code examples | Co trong moi file |
| Cap nhat | 02/2026 |

---

## Danh Sach Tat Ca Cac File

| STT | File | Dong |
|-----|------|------|
| 1 | [INDEX.md](./INDEX.md) | File nay |
| 2 | [WORDPRESS_LEARNING_PATH.md](./WORDPRESS_LEARNING_PATH.md) | ~180 |
| 3 | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | ~760 |
| 4 | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | ~530 |
| 5 | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | ~570 |
| 6 | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | ~840 |
| 7 | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | ~410 |
| 8 | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | ~1,560 |
| 9 | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | ~1,380 |
| 10 | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | ~2,800 |
| 11 | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | ~1,660 |
| 12 | [REST_API.md](./REST_API.md) | ~2,940 |
| 13 | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | ~610 |
| 14 | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | ~580 |
| 15 | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | ~2,810 |
| 16 | [WP_CLI.md](./WP_CLI.md) | ~2,020 |
