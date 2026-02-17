# Phần 4: Phát Triển Theme WordPress

> Theme WordPress quyết định giao diện và cách hiển thị nội dung website. Phần này hướng dẫn từ cơ bản đến nâng cao, bao gồm cả Block Theme (Full Site Editing) mới nhất.
> Tương đương với **Views / Blade Templates** trong Laravel.

---

## Mục Lục

| STT | File | Nội dung chi tiết | Độ khó |
|-----|------|-------------------|--------|
| 1 | [01-theme-co-ban.md](./01-theme-co-ban.md) | Theme là gì, tại sao tạo custom theme. Yêu cầu tối thiểu (`style.css` + `index.php`), cấu trúc thư mục đầy đủ, `functions.php`, Theme Supports, Enqueue assets. Tạo theme Hello World từ đầu. | Cơ bản |
| 2 | [02-template-hierarchy.md](./02-template-hierarchy.md) | Hệ thống Template Hierarchy - cách WordPress tự động chọn template file. Thứ tự ưu tiên cho từng loại trang (single, page, archive, search, 404...). Template Parts, Conditional Tags, CPT templates. | Trung bình |
| 3 | [03-the-loop-va-wp-query.md](./03-the-loop-va-wp-query.md) | The Loop - cơ chế hiển thị bài viết. WP_Query trong theme, Custom Loops, Multiple Loops, Pagination, `pre_get_posts` hook. So sánh với Eloquent/Blade. | Trung bình |
| 4 | [04-menus-widgets-sidebars.md](./04-menus-widgets-sidebars.md) | Đăng ký và hiển thị Navigation Menus. Custom Walker class, Mega Menu, Breadcrumbs. Đăng ký Sidebars, Widget Areas, Footer Widgets. | Trung bình |
| 5 | [05-customizer-api.md](./05-customizer-api.md) | Theme Customizer API: Panels, Sections, Settings, Controls. Tạo Custom Controls, Selective Refresh, Live Preview, Sanitization, Default Values. | Nâng cao |
| 6 | [06-block-theme-va-fse.md](./06-block-theme-va-fse.md) | Block Themes (WP 5.9+): `theme.json` configuration, Template Parts, Block Templates, Block Patterns, Template Editor, Full Site Editing. So sánh với Classic Themes. | Nâng cao |
| 7 | [07-theme-nang-cao.md](./07-theme-nang-cao.md) | Kỹ thuật nâng cao: Child Themes, WooCommerce integration, Responsive Design, Accessibility (a11y), Performance optimization, Internationalization (i18n), Packaging & phân phối. | Nâng cao |
| 8 | [08-vi-du-thuc-te.md](./08-vi-du-thuc-te.md) | **Ví dụ thực tế**: Xây theme hoàn chỉnh step-by-step. Code đầy đủ cho: style.css, functions.php, header.php, footer.php, index.php, single.php, page.php, archive.php, search.php, 404.php, sidebar.php, template parts, custom page templates, Bootstrap Walker, CPT archive template. | Thực hành |
| 9 | [09-so-do-va-minh-hoa.md](./09-so-do-va-minh-hoa.md) | **Sơ đồ & Minh họa**: Sơ đồ cấu trúc trang, Template Hierarchy dạng sơ đồ, so sánh Classic vs Block Theme, luồng request, layout patterns (Blog, Magazine, Portfolio), functions.php map, The Loop flow, vị trí hooks trên trang, theme.json, phân tích theme phổ biến (Astra, GeneratePress, Twenty Twenty-Four). | Minh họa |
| 10 | [10-tao-theme-hoan-chinh.md](./10-tao-theme-hoan-chinh.md) | **★ Theme hoàn chỉnh Copy-Paste Ready**: Theme "Developer Blog" với 16 file đầy đủ. Toàn bộ CSS (variables, layout, responsive), functions.php, header/footer, index, single, page, archive, search, 404, sidebar, comments, template parts, JS navigation. Mỗi file có ASCII art minh họa + giải thích chi tiết + so sánh Laravel. | Thực hành |

---

## Tổng Quan Nhanh

### Theme là gì?

Theme WordPress là tập hợp các file **PHP, CSS, JavaScript và hình ảnh** quyết định giao diện website. Theme chỉ thay đổi **cách trình bày**, không thay đổi **nội dung** (dữ liệu).

### Yêu cầu tối thiểu

Một theme hợp lệ chỉ cần **2 file**:
1. `style.css` - File CSS chính với header thông tin theme
2. `index.php` - Template mặc định (fallback)

### Cách cài đặt theme

1. **Qua Admin**: Appearance → Themes → Add New → Upload Theme
2. **Thủ công**: Upload folder theme vào `/wp-content/themes/`
3. **Từ WordPress.org**: Tìm kiếm trực tiếp trong admin

---

## Thứ Tự Đọc Khuyến Nghị

```
01-theme-co-ban              Tạo theme đầu tiên (Hello World)
        │
        ▼
02-template-hierarchy        Hiểu cách WP chọn template file
        │
        ▼
03-the-loop-va-wp-query      Hiển thị bài viết với The Loop
        │
        ├──▶ 04-menus-widgets     Menus, Sidebars, Widget Areas
        │
        ▼
05-customizer-api            Tạo tùy chọn cho theme
        │
        ▼
06-block-theme-va-fse        Theme kiểu mới (Block/FSE)
        │
        ▼
07-theme-nang-cao            Child Theme, WooCommerce, a11y, i18n
        │
        ▼
08-vi-du-thuc-te             ★ Xây theme hoàn chỉnh step-by-step
        │
        ▼
09-so-do-va-minh-hoa         ★ Sơ đồ trực quan & minh họa
        │
        ▼
10-tao-theme-hoan-chinh      ★ Theme hoàn chỉnh copy-paste ready
```

---

## So Sánh Với Laravel

| WordPress Theme | Laravel | Mô tả |
|----------------|---------|-------|
| `style.css` header | `composer.json` | Thông tin package/theme |
| `functions.php` | `AppServiceProvider` | Đăng ký chức năng |
| Template files | Blade templates | View files |
| Template Hierarchy | Route → Controller → View | Cơ chế chọn view |
| `get_header()` | `@include('header')` | Include partial |
| `get_template_part()` | `@include('partials.content')` | Include component |
| The Loop | `@foreach($posts as $post)` | Lặp qua dữ liệu |
| `wp_enqueue_style()` | `mix('css/app.css')` | Load assets |
| Customizer API | Config / Settings UI | Tùy chọn cấu hình |
| Child Theme | Override views | Ghi đè template |

---

## Mục Tiêu Sau Khi Hoàn Thành

- [ ] Tạo được theme WordPress hoàn chỉnh từ đầu
- [ ] Hiểu rõ Template Hierarchy và cách WordPress chọn template
- [ ] Sử dụng thành thạo The Loop và WP_Query trong theme
- [ ] Đăng ký và hiển thị Menus, Sidebars, Widget Areas
- [ ] Tạo tùy chọn theme với Customizer API
- [ ] Hiểu Block Themes và Full Site Editing
- [ ] Tạo Child Theme để customize an toàn

---

[← Quay lại INDEX.md](../INDEX.md) | [Tiếp theo: Phát triển Plugin →](../05-plugins/)
