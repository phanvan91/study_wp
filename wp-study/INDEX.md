# Hệ Thống Học Tập WordPress

> Tài liệu học WordPress từ cơ bản đến nâng cao, viết bằng tiếng Việt, có example code minh họa.

---

## Bắt Đầu Từ Đâu

Nếu bạn mới bắt đầu học WordPress, hãy đọc theo thứ tự sau:

1. Đọc [Lộ trình học tập](#lộ-trình-học-tập) để có cái nhìn tổng thể
2. Tìm hiểu [Cấu trúc source code](#hiểu-wordpress-core) để hiểu WordPress hoạt động thế nào
3. Học [Luồng xử lý request](#hiểu-wordpress-core) để hiểu mỗi request được xử lý ra sao
4. Tiếp tục với [Tạo theme](#theme-development) hoặc [Tạo plugin](#plugin-development) tùy mục tiêu của bạn

---

## Mục Lục Tổng Hợp

### Lộ Trình Học Tập

| File | Mô tả |
|------|-------|
| [WORDPRESS_LEARNING_PATH.md](./WORDPRESS_LEARNING_PATH.md) | Lộ trình 8 giai đoạn từ cơ bản đến chuyên gia, có gợi ý study plan 6-9 tháng |

---

### Hiểu WordPress Core

Nhóm này giúp bạn hiểu cách WordPress hoạt động từ bên trong.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [CAU_TRUC_SOURCE_CODE.md](./CAU_TRUC_SOURCE_CODE.md) | Phân tích cấu trúc thư mục, các file chính, design patterns | Cơ bản |
| 2 | [WORDPRESS_FLOW.md](./WORDPRESS_FLOW.md) | Luồng xử lý request từ index.php đến HTML output | Cơ bản |
| 3 | [WORDPRESS_ROUTING.md](./WORDPRESS_ROUTING.md) | Hệ thống URL Rewriting, Rewrite API, Template Hierarchy | Trung bình |
| 4 | [WORDPRESS_HOOKS.md](./WORDPRESS_HOOKS.md) | Action Hooks, Filter Hooks, Priority, Custom Hooks | Trung bình |

**Thứ tự đọc khuyến nghị:** 1 → 2 → 3 → 4

---

### Theme Development

Nhóm này hướng dẫn tạo và sử dụng theme WordPress.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [HUONG_DAN_THEME.md](./HUONG_DAN_THEME.md) | Download, cài đặt, sử dụng theme có sẵn, Child Theme | Cơ bản |
| 2 | [TAO_THEME_CO_BAN.md](./TAO_THEME_CO_BAN.md) | Tạo theme từ đầu: template files, Loop, Menus, Widgets, Customizer, theme.json | Trung bình |

**Thứ tự đọc khuyến nghị:** 1 → 2

---

### Plugin Development

Nhóm này hướng dẫn tạo plugin WordPress.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [TAO_PLUGIN_CO_BAN.md](./TAO_PLUGIN_CO_BAN.md) | Tạo plugin từ đầu: Headers, Menu Admin, Settings API, Shortcodes, Widgets, AJAX, CPT, CRUD hoàn chỉnh | Trung bình |

---

### Database và Truy Vấn

Nhóm này về cơ sở dữ liệu và cách truy vấn trong WordPress.

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [DATABASE_VA_WP_QUERY.md](./DATABASE_VA_WP_QUERY.md) | Schema database, $wpdb, WP_Query, Meta Query, Tax Query, Custom Tables | Trung bình |
| 2 | [CUSTOM_POST_TYPE_TAXONOMY.md](./CUSTOM_POST_TYPE_TAXONOMY.md) | register_post_type, register_taxonomy, Meta Boxes, Custom Columns | Trung bình |

**Thứ tự đọc khuyến nghị:** 1 → 2

---

### REST API

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [REST_API.md](./REST_API.md) | Endpoints mặc định, Authentication, Custom Endpoints, Controller, CRUD API | Nâng cao |

---

### Bảo Mật

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [BAO_MAT_WORDPRESS.md](./BAO_MAT_WORDPRESS.md) | Sanitize, Escape, Nonces, SQL Injection, XSS, File Upload, Security Constants | Trung bình |

---

### Hiệu Năng

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [HIEU_NANG_TOI_UU.md](./HIEU_NANG_TOI_UU.md) | Object Cache, Page Cache, Transients, Database Optimization, Image, CDN, Profiling | Nâng cao |

---

### Block Editor (Gutenberg)

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [GUTENBERG_BLOCK_EDITOR.md](./GUTENBERG_BLOCK_EDITOR.md) | Tạo Custom Block, Attributes, InspectorControls, Dynamic Blocks, Block Patterns, theme.json | Nâng cao |

---

### Công Cụ

| # | File | Mô tả | Độ khó |
|---|------|-------|--------|
| 1 | [WP_CLI.md](./WP_CLI.md) | Cài đặt, các lệnh cơ bản, scaffold, search-replace, backup, Custom Command, Automation | Trung bình |

---

## Bản Đồ Học Tập

```
                    ┌──────────────────────────┐
                    │  WORDPRESS_LEARNING_PATH  │  Bắt đầu ở đây
                    └─────────────┬────────────┘
                                  │
                    ┌─────────────▼────────────┐
                    │   CAU_TRUC_SOURCE_CODE    │  Hiểu cấu trúc WP
                    │      WORDPRESS_FLOW       │  Hiểu luồng xử lý
                    └─────────────┬────────────┘
                                  │
              ┌───────────────────┼───────────────────┐
              │                   │                    │
    ┌─────────▼─────────┐ ┌──────▼──────────┐ ┌──────▼──────────┐
    │  WORDPRESS_HOOKS   │ │WORDPRESS_ROUTING│ │  BAO_MAT_WP     │
    │ (Hooks hệ thống)   │ │  (URL Routing)  │ │  (Bảo mật)      │
    └─────────┬─────────┘ └──────┬──────────┘ └─────────────────┘
              │                  │
    ┌─────────▼──────────────────▼──────────┐
    │                                        │
    │    TAO_THEME_CO_BAN    TAO_PLUGIN_CO_BAN
    │    (Tạo theme)         (Tạo plugin)    │
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
  │ (Tối ưu hiệu năng)  │  │  (Công cụ)     │
  └─────────────────────┘  └────────────────┘
```

---

## Thống Kê

| Thông tin | Giá trị |
|-----------|---------|
| Tổng số file | 15 |
| Tổng số dòng | ~19,600+ |
| Ngôn ngữ | Tiếng Việt |
| Code examples | Có trong mọi file |
| Cập nhật | 02/2026 |

---

## Danh Sách Tất Cả Các File

| STT | File | Dòng |
|-----|------|------|
| 1 | [INDEX.md](./INDEX.md) | File này |
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
