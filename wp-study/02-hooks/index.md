# Phần 2: Hệ Thống Hooks - Trái Tim Của WordPress

> **Hooks** là cơ chế cho phép plugins và themes "móc vào" các điểm cụ thể trong quá trình xử lý của WordPress để thực thi code tùy chỉnh mà **không cần sửa đổi core**.
> Tương đương với **Events/Listeners** trong Laravel.

---

## Mục Lục

| STT | File | Nội dung chi tiết | Độ khó |
|-----|------|-------------------|--------|
| 1 | [01-hooks-co-ban.md](./01-hooks-co-ban.md) | Khái niệm hooks, Event-Driven Architecture. Các hàm cơ bản: `add_action()`, `add_filter()`, `remove_action()`, `remove_filter()`. Priority, accepted_args, closures. So sánh chi tiết với Laravel Events/Listeners. | Cơ bản |
| 2 | [02-action-hooks-quan-trong.md](./02-action-hooks-quan-trong.md) | Danh sách các Action Hooks quan trọng nhất: `init`, `admin_menu`, `wp_enqueue_scripts`, `save_post`, `wp_ajax_{action}`, `template_redirect`, `wp_head`, `wp_footer`... Kèm ví dụ thực tế và thời điểm thực thi. | Trung bình |
| 3 | [03-filter-hooks-quan-trong.md](./03-filter-hooks-quan-trong.md) | Danh sách các Filter Hooks quan trọng nhất: `the_content`, `the_title`, `pre_get_posts`, `body_class`, `upload_mimes`, `wp_mail`, `template_include`... Kèm patterns thay đổi dữ liệu. | Trung bình |
| 4 | [04-hooks-lifecycle.md](./04-hooks-lifecycle.md) | Vòng đời thực thi hooks trong WordPress. Sơ đồ chi tiết cho từng loại request: Frontend, Admin, AJAX, REST API, Cron, Login page. Thứ tự hooks được fire. | Trung bình |
| 5 | [05-custom-hooks.md](./05-custom-hooks.md) | Cách tạo custom hooks riêng với `do_action()` và `apply_filters()`. Quy tắc đặt tên, documentation, pluggable functions, Observer Pattern. | Nâng cao |
| 6 | [06-hooks-trong-plugin.md](./06-hooks-trong-plugin.md) | Best practices sử dụng hooks trong plugin: activation/deactivation hooks, conditional hooks, cách remove hooks từ plugin khác, debugging hooks. | Nâng cao |
| 7 | [07-hooks-nang-cao.md](./07-hooks-nang-cao.md) | Kỹ thuật nâng cao: OOP callbacks, WP_Hook class internals, dynamic hooks, tối ưu hiệu năng hooks, PHPUnit testing cho hooks. | Nâng cao |

---

## Hai Loại Hooks Chính

### Action Hooks - "Làm gì đó"
- Thực thi code tại một điểm cụ thể
- **Không cần** trả về giá trị
- Cú pháp: `add_action()` / `do_action()`

```php
// Ví dụ: Thực thi code khi WordPress khởi tạo
add_action( 'init', function() {
    // Đăng ký custom post type, taxonomy...
} );
```

### Filter Hooks - "Thay đổi cái gì đó"
- Thay đổi/modify dữ liệu trước khi sử dụng
- **PHẢI** trả về giá trị
- Cú pháp: `add_filter()` / `apply_filters()`

```php
// Ví dụ: Thay đổi nội dung bài viết
add_filter( 'the_content', function( $content ) {
    return $content . '<p>Nội dung thêm vào cuối bài.</p>';
} );
```

---

## So Sánh Với Laravel

| WordPress | Laravel | Mô tả |
|-----------|---------|-------|
| `add_action()` | `Event::listen()` | Đăng ký listener cho event |
| `do_action()` | `event()` / `Event::dispatch()` | Fire event |
| `add_filter()` | Pipeline / Middleware | Thay đổi dữ liệu qua chuỗi xử lý |
| `apply_filters()` | `$next($request)` | Áp dụng chuỗi filter |
| Priority (1-99) | `$listen` order | Thứ tự thực thi |

---

## Thứ Tự Đọc Khuyến Nghị

```
01-hooks-co-ban             Nắm vững khái niệm & cú pháp cơ bản
        │
        ├──▶ 02-action-hooks     Học các Action Hooks quan trọng
        │
        ├──▶ 03-filter-hooks     Học các Filter Hooks quan trọng
        │
        ▼
04-hooks-lifecycle          Hiểu vòng đời hooks trong request
        │
        ▼
05-custom-hooks             Tạo hooks riêng cho plugin/theme
        │
        ▼
06-hooks-trong-plugin       Best practices trong thực tế
        │
        ▼
07-hooks-nang-cao           Kỹ thuật OOP, debugging, testing
```

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Phân biệt rõ Action Hooks và Filter Hooks
- [ ] Sử dụng thành thạo `add_action()`, `add_filter()`, `remove_action()`, `remove_filter()`
- [ ] Biết các hooks quan trọng nhất và khi nào chúng được fire
- [ ] Tạo được custom hooks cho plugin/theme riêng
- [ ] Hiểu WP_Hook class và cách WordPress quản lý hooks bên trong

---

[← Quay lại INDEX.md](../INDEX.md) | [Tiếp theo: Cơ sở dữ liệu →](../03-database/)
