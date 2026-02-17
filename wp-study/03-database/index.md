# Phần 3: Cơ Sở Dữ Liệu WordPress

> Hiểu cách WordPress lưu trữ và truy vấn dữ liệu. Bao gồm schema database, lớp trừu tượng `$wpdb`, class `WP_Query`, và cách tạo Custom Post Types & Taxonomies.
> Tương đương với **Eloquent ORM** và **Migrations** trong Laravel.

---

## Mục Lục

| STT | File | Nội dung chi tiết | Độ khó |
|-----|------|-------------------|--------|
| 1 | [01-database-va-wpdb.md](./01-database-va-wpdb.md) | Tổng quan database WordPress, lớp trừu tượng `$wpdb`, class `WP_Query` đầy đủ. Bao gồm: Meta Query, Tax Query, Date Query, Pagination, Custom Queries. So sánh chi tiết với Eloquent ORM. | Trung bình |
| 2 | [02-database-schema.md](./02-database-schema.md) | Phân tích chi tiết **từng bảng, từng cột** trong database WordPress dựa trên `schema.php`. Bao gồm: ERD diagram, mối quan hệ giữa các bảng, vai trò từng cột, và Roles/Capabilities. | Nâng cao |
| 3 | [03-custom-post-type-taxonomy.md](./03-custom-post-type-taxonomy.md) | Hướng dẫn tạo Custom Post Types và Taxonomies. Bao gồm: `register_post_type()`, `register_taxonomy()`, tham số đăng ký, Meta Boxes, Custom Admin Columns, và template files cho CPT. | Trung bình |

---

## Thứ Tự Đọc Khuyến Nghị

```
01-database-va-wpdb.md          Học cách truy vấn dữ liệu ($wpdb, WP_Query)
        │
        ▼
02-database-schema.md           Hiểu chi tiết schema từng bảng
        │
        ▼
03-custom-post-type-taxonomy.md Tạo loại nội dung & phân loại riêng
```

---

## 12 Bảng Mặc Định Của WordPress

| Bảng | Vai trò | So sánh Laravel |
|------|---------|-----------------|
| `wp_posts` | Lưu tất cả nội dung (posts, pages, CPT, revisions, attachments) | `posts` table |
| `wp_postmeta` | Metadata của posts (key-value) | `post_meta` / JSON columns |
| `wp_terms` | Tên các thuật ngữ phân loại | `tags` / `categories` |
| `wp_term_taxonomy` | Loại phân loại (category, tag, custom) | `taggables` pivot |
| `wp_term_relationships` | Liên kết posts ↔ terms | Pivot table |
| `wp_termmeta` | Metadata của terms | - |
| `wp_users` | Thông tin người dùng | `users` table |
| `wp_usermeta` | Metadata của users (key-value) | User profile columns |
| `wp_options` | Cài đặt toàn cục (key-value) | `settings` / `.env` |
| `wp_comments` | Bình luận | `comments` table |
| `wp_commentmeta` | Metadata của comments | - |
| `wp_links` | Blogroll links (ít dùng) | - |

---

## So Sánh Với Laravel

| Thao tác | Laravel | WordPress |
|----------|---------|-----------|
| Truy vấn | `Model::where()->get()` | `new WP_Query([...])` |
| Tạo record | `Model::create([...])` | `wp_insert_post([...])` |
| Cập nhật | `$model->update([...])` | `wp_update_post([...])` |
| Xóa | `$model->delete()` | `wp_delete_post($id)` |
| Raw query | `DB::select()` | `$wpdb->get_results()` |
| Migration | `Schema::create()` | `dbDelta($sql)` |
| Prepared statement | `DB::select('...?', [...])` | `$wpdb->prepare('...%d', $id)` |

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Sử dụng thành thạo `$wpdb` cho raw queries
- [ ] Viết được WP_Query phức tạp (Meta Query, Tax Query, Pagination)
- [ ] Hiểu schema database WordPress và mối quan hệ giữa các bảng
- [ ] Tạo được Custom Post Types và Taxonomies
- [ ] Tạo được custom database tables với `dbDelta()`

---

[← Quay lại INDEX.md](../INDEX.md) | [Tiếp theo: Phát triển Theme →](../04-themes/)
