# Bài 10: Tạo Theme WordPress Hoàn Chỉnh - Copy-Paste Ready

> **Theme "Developer Blog"** - Một theme blog hiện đại, responsive, viết bằng CSS thuần.
> Copy từng code block bên dưới vào đúng file → Kích hoạt theme → Chạy ngay!
> Dành cho **PHP Laravel Developer** muốn hiểu theme WordPress từ trong ra ngoài.

---

## Mục Lục

1. [Preview giao diện & Cấu trúc thư mục](#1-preview-giao-diện--cấu-trúc-thư-mục)
2. [style.css - Khai báo theme + Toàn bộ CSS](#2-stylecss---khai-báo-theme--toàn-bộ-css)
3. [functions.php - Bộ não của theme](#3-functionsphp---bộ-não-của-theme)
4. [header.php - Phần đầu trang](#4-headerphp---phần-đầu-trang)
5. [footer.php - Phần chân trang](#5-footerphp---phần-chân-trang)
6. [index.php - Trang blog chính](#6-indexphp---trang-blog-chính)
7. [single.php - Trang bài viết đơn](#7-singlephp---trang-bài-viết-đơn)
8. [page.php - Trang tĩnh](#8-pagephp---trang-tĩnh)
9. [archive.php - Trang danh sách](#9-archivephp---trang-danh-sách)
10. [search.php - Trang tìm kiếm](#10-searchphp---trang-tìm-kiếm)
11. [searchform.php - Form tìm kiếm](#11-searchformphp---form-tìm-kiếm)
12. [404.php - Trang không tìm thấy](#12-404php---trang-không-tìm-thấy)
13. [sidebar.php - Thanh bên](#13-sidebarphp---thanh-bên)
14. [comments.php - Bình luận](#14-commentsphp---bình-luận)
15. [Template Parts - Các phần tái sử dụng](#15-template-parts---các-phần-tái-sử-dụng)
16. [assets/js/navigation.js - Menu mobile](#16-assetsjsnavigationjs---menu-mobile)
17. [Hướng dẫn cài đặt & Kích hoạt](#17-hướng-dẫn-cài-đặt--kích-hoạt)
18. [Tổng kết](#18-tổng-kết)

---

## 1. Preview Giao Diện & Cấu Trúc Thư Mục

### 1.1. Preview trang chủ (Blog Listing)

```
┌─────────────────────────────────────────────────────────────────┐
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  [LOGO] Developer Blog     [Trang chủ] [Giới thiệu] [🔍] │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────┐  ┌──────────────────────┐ │
│  │                                  │  │  🔍 Tìm kiếm        │ │
│  │  ┌────────────────────────────┐  │  │  [_______________]   │ │
│  │  │  ┌──────────────────────┐  │  │  │                      │ │
│  │  │  │    [Ảnh đại diện]    │  │  │  │  📋 Bài viết mới    │ │
│  │  │  └──────────────────────┘  │  │  │  ├── Bài viết 1      │ │
│  │  │  📁 Danh mục · 📅 01/2026 │  │  │  ├── Bài viết 2      │ │
│  │  │  Tiêu đề bài viết 1       │  │  │  └── Bài viết 3      │ │
│  │  │  Đoạn trích nội dung...   │  │  │                      │ │
│  │  │            [Đọc tiếp →]   │  │  │  📂 Danh mục         │ │
│  │  └────────────────────────────┘  │  │  ├── Công nghệ (5)   │ │
│  │                                  │  │  ├── Laravel (3)      │ │
│  │  ┌────────────────────────────┐  │  │  └── WordPress (8)   │ │
│  │  │  ┌──────────────────────┐  │  │  │                      │ │
│  │  │  │    [Ảnh đại diện]    │  │  │  │  🏷️ Tags            │ │
│  │  │  └──────────────────────┘  │  │  │  [PHP] [CSS] [JS]    │ │
│  │  │  📁 Danh mục · 📅 01/2026 │  │  │                      │ │
│  │  │  Tiêu đề bài viết 2       │  │  └──────────────────────┘ │
│  │  │  Đoạn trích nội dung...   │  │                            │
│  │  │            [Đọc tiếp →]   │  │                            │
│  │  └────────────────────────────┘  │                            │
│  │                                  │                            │
│  │  « Trước   [1] [2] [3]   Sau » │                            │
│  └──────────────────────────────────┘                            │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  [Về chúng tôi]    [Liên kết]      [Liên hệ]            │  │
│  │  Blog chia sẻ      ├── GitHub      Email: hi@blog.com    │  │
│  │  kiến thức lập     ├── Twitter     Điện thoại: 090...    │  │
│  │  trình web.        └── YouTube                            │  │
│  │  ─────────────────────────────────────────────────────── │  │
│  │           © 2026 Developer Blog. Powered by WordPress.    │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2. Preview trang bài viết đơn (Single Post)

```
┌─────────────────────────────────────────────────────────────────┐
│  [LOGO] Developer Blog     [Trang chủ] [Giới thiệu] [🔍]      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  📁 Công nghệ · 📅 15/01/2026 · ✍️ Admin                 │  │
│  │                                                            │  │
│  │  Tiêu Đề Bài Viết Đầy Đủ                                 │  │
│  │  ═══════════════════════════                               │  │
│  │                                                            │  │
│  │  ┌──────────────────────────────────────────────────────┐ │  │
│  │  │              [Ảnh đại diện lớn]                      │ │  │
│  │  └──────────────────────────────────────────────────────┘ │  │
│  │                                                            │  │
│  │  Nội dung bài viết đầy đủ ở đây...                       │  │
│  │  Paragraph 1...                                            │  │
│  │  Paragraph 2...                                            │  │
│  │                                                            │  │
│  │  🏷️ Tags: [PHP] [WordPress] [Theme]                      │  │
│  │                                                            │  │
│  │  ┌────────────────────────────────────────────────────┐   │  │
│  │  │  ← Bài trước: Tiêu đề...                          │   │  │
│  │  │                     Bài sau: Tiêu đề... →          │   │  │
│  │  └────────────────────────────────────────────────────┘   │  │
│  │                                                            │  │
│  │  💬 Bình luận (3)                                         │  │
│  │  ┌── Người dùng A ──────────────────────────────────┐    │  │
│  │  │   Bình luận hay quá!                              │    │  │
│  │  └───────────────────────────────────────────────────┘    │  │
│  │  ┌── Form bình luận ────────────────────────────────┐    │  │
│  │  │   [Tên] [Email] [Nội dung...]     [Gửi]         │    │  │
│  │  └───────────────────────────────────────────────────┘    │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  [Footer...]                                                     │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3. Cấu trúc thư mục theme

```
wp-content/themes/developer-blog/
│
├── style.css                        ★ BẮT BUỘC - Khai báo theme + toàn bộ CSS
├── functions.php                    ★ Đăng ký chức năng theme
├── screenshot.png                     Ảnh preview 1200x900px (tự chụp sau)
│
├── header.php                       ← Phần đầu mọi trang
├── footer.php                       ← Phần cuối mọi trang
├── sidebar.php                      ← Thanh bên (widgets)
│
├── index.php                        ★ BẮT BUỘC - Template mặc định (blog listing)
├── single.php                       ← Trang bài viết đơn
├── page.php                         ← Trang tĩnh (About, Contact...)
├── archive.php                      ← Trang danh sách (category, tag, author...)
├── search.php                       ← Kết quả tìm kiếm
├── searchform.php                   ← Form tìm kiếm tùy chỉnh
├── 404.php                          ← Trang lỗi 404
├── comments.php                     ← Template bình luận
│
├── template-parts/                  ← Các phần tái sử dụng
│   ├── content.php                  ← Card bài viết trong loop
│   └── content-none.php             ← Không có kết quả
│
└── assets/
    └── js/
        └── navigation.js            ← JavaScript menu mobile
```

### 1.4. Bảng tổng hợp các file

| # | File | Chức năng | Bắt buộc? |
|---|------|-----------|-----------|
| 1 | `style.css` | Khai báo theme + CSS styling | ★ Bắt buộc |
| 2 | `functions.php` | Đăng ký menus, widgets, enqueue assets | Rất quan trọng |
| 3 | `header.php` | `<head>`, logo, navigation, mở `<body>` | Rất quan trọng |
| 4 | `footer.php` | Footer widgets, copyright, đóng `</body>` | Rất quan trọng |
| 5 | `index.php` | Blog listing với The Loop | ★ Bắt buộc |
| 6 | `single.php` | Trang bài viết chi tiết | Quan trọng |
| 7 | `page.php` | Trang tĩnh (About, Contact...) | Quan trọng |
| 8 | `archive.php` | Danh sách theo category/tag/author | Quan trọng |
| 9 | `search.php` | Kết quả tìm kiếm | Nên có |
| 10 | `searchform.php` | Form tìm kiếm tùy chỉnh | Nên có |
| 11 | `404.php` | Trang lỗi 404 | Nên có |
| 12 | `sidebar.php` | Widget area bên phải | Nên có |
| 13 | `comments.php` | Danh sách + form bình luận | Nên có |
| 14 | `template-parts/content.php` | Card bài viết (tái sử dụng) | Tùy chọn |
| 15 | `template-parts/content-none.php` | Thông báo không có kết quả | Tùy chọn |
| 16 | `assets/js/navigation.js` | Toggle menu mobile | Tùy chọn |

> **So sánh Laravel:** Theme WordPress tương đương thư mục `resources/views/` trong Laravel.
> `header.php` + `footer.php` = `layouts/app.blade.php`,
> `template-parts/` = `components/` hoặc `partials/`.
> Khác biệt lớn: WordPress **tự động chọn template** dựa trên URL (Template Hierarchy),
> còn Laravel bạn phải **tự định nghĩa route → controller → view**.

---

## 2. style.css - Khai báo Theme + Toàn Bộ CSS

> **File này làm 2 việc:**
> 1. Khai báo metadata cho WordPress nhận diện theme (phần comment đầu file)
> 2. Chứa toàn bộ CSS styling cho theme

### Preview visual: CSS tạo ra giao diện gì?

```
Màu sắc theme:
┌──────────────────────────────────────────────────────┐
│  Primary (nút, link):  ████ #2563eb (xanh dương)    │
│  Text chính:           ████ #1e293b (đen đậm)       │
│  Text phụ:             ████ #64748b (xám)            │
│  Background:           ████ #f8fafc (trắng nhạt)     │
│  Card background:      ████ #ffffff (trắng)          │
│  Border:               ████ #e2e8f0 (xám nhạt)      │
│  Footer background:    ████ #1e293b (đen đậm)       │
│  Accent (hover):       ████ #1d4ed8 (xanh đậm)      │
└──────────────────────────────────────────────────────┘
```

### Code hoàn chỉnh: `style.css`

```css
/*
Theme Name: Developer Blog
Theme URI: https://developer-blog.dev
Author: Developer Blog Team
Author URI: https://developer-blog.dev
Description: Theme blog hiện đại, tối giản, responsive. Thiết kế cho developer chia sẻ kiến thức lập trình. Hỗ trợ Custom Logo, Navigation Menu, Sidebar Widgets, Footer Widgets, Featured Images, và Comments.
Version: 1.0.0
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: developer-blog
Tags: blog, two-columns, right-sidebar, custom-logo, custom-menu, featured-images, threaded-comments, translation-ready
*/

/* ==========================================================================
   1. CSS CUSTOM PROPERTIES (Biến CSS)
   - Định nghĩa màu sắc, font, spacing ở 1 chỗ
   - Thay đổi ở đây → tự động thay đổi toàn bộ theme
   - Tương đương biến $color trong SCSS hoặc config trong Tailwind
   ========================================================================== */

:root {
    /* Màu sắc chính */
    --color-primary: #2563eb;
    --color-primary-dark: #1d4ed8;
    --color-text: #1e293b;
    --color-text-light: #64748b;
    --color-bg: #f8fafc;
    --color-white: #ffffff;
    --color-border: #e2e8f0;
    --color-footer-bg: #1e293b;
    --color-footer-text: #cbd5e1;

    /* Font chữ - System font stack (nhanh, không cần tải thêm) */
    --font-main: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
                 Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue",
                 sans-serif;
    --font-code: "Fira Code", "Cascadia Code", Consolas, Monaco, "Courier New",
                 monospace;

    /* Kích thước */
    --container-width: 1200px;
    --sidebar-width: 320px;
    --gap: 2rem;
    --radius: 8px;
}

/* ==========================================================================
   2. RESET & BASE
   - Đặt lại CSS mặc định của trình duyệt
   - Thiết lập kiểu chữ cơ bản
   ========================================================================== */

*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    font-size: 16px;
    scroll-behavior: smooth;
    -webkit-text-size-adjust: 100%;
}

body {
    font-family: var(--font-main);
    font-size: 1rem;
    line-height: 1.7;
    color: var(--color-text);
    background-color: var(--color-bg);
}

img {
    max-width: 100%;
    height: auto;
    display: block;
}

a {
    color: var(--color-primary);
    text-decoration: none;
    transition: color 0.2s ease;
}

a:hover {
    color: var(--color-primary-dark);
}

ul, ol {
    list-style-position: inside;
}

/* ==========================================================================
   3. LAYOUT CONTAINER
   - .site-container: bọc toàn bộ trang
   - .container: giới hạn chiều rộng content (1200px)
   - .content-area: flexbox chia content + sidebar
   ========================================================================== */

.site-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.container {
    max-width: var(--container-width);
    margin: 0 auto;
    padding: 0 1.5rem;
    width: 100%;
}

.content-area {
    display: flex;
    gap: var(--gap);
    padding: 2rem 0;
}

.main-content {
    flex: 1;
    min-width: 0; /* Ngăn flex item bị tràn */
}

.sidebar {
    width: var(--sidebar-width);
    flex-shrink: 0;
}

/* ==========================================================================
   4. HEADER & NAVIGATION
   - Header trắng, sticky ở trên cùng
   - Logo bên trái, menu bên phải
   - Hamburger menu cho mobile
   ========================================================================== */

.site-header {
    background: var(--color-white);
    border-bottom: 1px solid var(--color-border);
    position: sticky;
    top: 0;
    z-index: 100;
}

/* Khi đăng nhập WP Admin, admin bar chiếm 32px trên cùng */
.admin-bar .site-header {
    top: 32px;
}

.header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
}

/* --- Logo --- */
.site-branding {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.site-branding .custom-logo {
    height: 40px;
    width: auto;
}

.site-title {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.site-title a {
    color: var(--color-text);
}

.site-title a:hover {
    color: var(--color-primary);
}

.site-description {
    font-size: 0.8rem;
    color: var(--color-text-light);
    margin: 0;
}

/* --- Navigation Menu --- */
.main-navigation ul {
    display: flex;
    gap: 0.25rem;
    list-style: none;
}

.main-navigation a {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--color-text);
    font-weight: 500;
    border-radius: var(--radius);
    transition: background 0.2s, color 0.2s;
}

.main-navigation a:hover,
.main-navigation .current-menu-item > a {
    background: var(--color-primary);
    color: var(--color-white);
}

/* Submenu (dropdown) */
.main-navigation li {
    position: relative;
}

.main-navigation .sub-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    min-width: 200px;
    padding: 0.5rem 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    flex-direction: column;
    z-index: 10;
}

.main-navigation li:hover > .sub-menu {
    display: flex;
}

.main-navigation .sub-menu a {
    padding: 0.4rem 1rem;
    border-radius: 0;
}

/* --- Hamburger Button (mobile) --- */
.menu-toggle {
    display: none; /* Ẩn trên desktop */
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: var(--color-text);
}

.menu-toggle svg {
    width: 24px;
    height: 24px;
}

/* --- Header Search Toggle --- */
.header-search-toggle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: var(--color-text-light);
    font-size: 1.2rem;
    transition: color 0.2s;
}

.header-search-toggle:hover {
    color: var(--color-primary);
}

/* ==========================================================================
   5. BLOG POST CARD (trong listing)
   - Card trắng với ảnh thumbnail, meta, excerpt
   - Hover có shadow nhẹ
   ========================================================================== */

.post-card {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: box-shadow 0.2s ease;
}

.post-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.post-card .post-thumbnail {
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.post-card .post-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.post-card:hover .post-thumbnail img {
    transform: scale(1.03);
}

.post-card .post-card-body {
    padding: 1.5rem;
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.85rem;
    color: var(--color-text-light);
    margin-bottom: 0.5rem;
}

.post-meta a {
    color: var(--color-text-light);
}

.post-meta a:hover {
    color: var(--color-primary);
}

.post-card .entry-title {
    font-size: 1.35rem;
    line-height: 1.4;
    margin-bottom: 0.75rem;
}

.post-card .entry-title a {
    color: var(--color-text);
}

.post-card .entry-title a:hover {
    color: var(--color-primary);
}

.post-card .entry-excerpt {
    color: var(--color-text-light);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.read-more {
    display: inline-block;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--color-primary);
}

.read-more:hover {
    color: var(--color-primary-dark);
}

/* ==========================================================================
   6. SINGLE POST (trang bài viết đơn)
   ========================================================================== */

.single-post-header {
    margin-bottom: 1.5rem;
}

.single-post-header .entry-title {
    font-size: 2rem;
    line-height: 1.3;
    margin-bottom: 0.75rem;
}

.single-post-header .post-meta {
    margin-bottom: 1rem;
}

.single-post-thumbnail {
    margin-bottom: 1.5rem;
    border-radius: var(--radius);
    overflow: hidden;
}

.single-post-thumbnail img {
    width: 100%;
    height: auto;
}

/* Entry Content - style cho nội dung bài viết */
.entry-content {
    line-height: 1.8;
    font-size: 1.05rem;
}

.entry-content h2 {
    font-size: 1.5rem;
    margin: 2rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--color-border);
}

.entry-content h3 {
    font-size: 1.25rem;
    margin: 1.5rem 0 0.75rem;
}

.entry-content p {
    margin-bottom: 1.25rem;
}

.entry-content ul,
.entry-content ol {
    margin-bottom: 1.25rem;
    padding-left: 1.5rem;
    list-style-position: outside;
}

.entry-content li {
    margin-bottom: 0.35rem;
}

.entry-content blockquote {
    border-left: 4px solid var(--color-primary);
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
    background: rgba(37, 99, 235, 0.05);
    border-radius: 0 var(--radius) var(--radius) 0;
    font-style: italic;
}

.entry-content pre {
    background: #1e293b;
    color: #e2e8f0;
    padding: 1.25rem;
    border-radius: var(--radius);
    overflow-x: auto;
    margin: 1.5rem 0;
    font-family: var(--font-code);
    font-size: 0.9rem;
    line-height: 1.5;
}

.entry-content code {
    font-family: var(--font-code);
    background: #f1f5f9;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-size: 0.88rem;
}

.entry-content pre code {
    background: none;
    padding: 0;
    border-radius: 0;
    font-size: inherit;
}

.entry-content img {
    border-radius: var(--radius);
    margin: 1rem 0;
}

.entry-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
}

.entry-content th,
.entry-content td {
    border: 1px solid var(--color-border);
    padding: 0.6rem 1rem;
    text-align: left;
}

.entry-content th {
    background: #f1f5f9;
    font-weight: 600;
}

/* Tags ở cuối bài */
.post-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 2rem 0;
    padding-top: 1rem;
    border-top: 1px solid var(--color-border);
}

.post-tags a {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    border-radius: 20px;
    font-size: 0.85rem;
    color: var(--color-text-light);
}

.post-tags a:hover {
    background: var(--color-primary);
    color: var(--color-white);
}

/* Post Navigation (Bài trước / Bài sau) */
.post-navigation {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin: 2rem 0;
    padding: 1.5rem;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
}

.post-navigation .nav-previous,
.post-navigation .nav-next {
    flex: 1;
}

.post-navigation .nav-next {
    text-align: right;
}

.post-navigation .nav-label {
    display: block;
    font-size: 0.8rem;
    color: var(--color-text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.post-navigation .nav-title {
    font-weight: 600;
    color: var(--color-text);
}

.post-navigation a:hover .nav-title {
    color: var(--color-primary);
}

/* ==========================================================================
   7. PAGE (trang tĩnh)
   ========================================================================== */

.page-header-section {
    margin-bottom: 1.5rem;
}

.page-header-section .page-title {
    font-size: 2rem;
    line-height: 1.3;
}

/* ==========================================================================
   8. ARCHIVE HEADER (trang danh sách)
   ========================================================================== */

.archive-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--color-border);
}

.archive-header .archive-title {
    font-size: 1.75rem;
    margin-bottom: 0.25rem;
}

.archive-header .archive-description {
    color: var(--color-text-light);
    font-size: 1rem;
}

/* ==========================================================================
   9. PAGINATION
   ========================================================================== */

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.35rem;
    margin: 2rem 0;
}

.pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    font-weight: 500;
    color: var(--color-text);
    transition: all 0.2s;
}

.pagination .page-numbers:hover,
.pagination .page-numbers.current {
    background: var(--color-primary);
    color: var(--color-white);
    border-color: var(--color-primary);
}

/* ==========================================================================
   10. SIDEBAR
   ========================================================================== */

.widget {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.widget-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--color-primary);
}

.widget ul {
    list-style: none;
}

.widget ul li {
    padding: 0.4rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.widget ul li:last-child {
    border-bottom: none;
}

.widget ul li a {
    color: var(--color-text);
    font-size: 0.95rem;
}

.widget ul li a:hover {
    color: var(--color-primary);
}

/* Widget Search */
.widget .search-form {
    display: flex;
}

.widget .search-field {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--color-border);
    border-right: none;
    border-radius: var(--radius) 0 0 var(--radius);
    font-size: 0.9rem;
    outline: none;
}

.widget .search-field:focus {
    border-color: var(--color-primary);
}

.widget .search-submit {
    padding: 0.5rem 1rem;
    background: var(--color-primary);
    color: var(--color-white);
    border: none;
    border-radius: 0 var(--radius) var(--radius) 0;
    cursor: pointer;
    font-weight: 600;
}

.widget .search-submit:hover {
    background: var(--color-primary-dark);
}

/* ==========================================================================
   11. FOOTER
   ========================================================================== */

.site-footer {
    background: var(--color-footer-bg);
    color: var(--color-footer-text);
    margin-top: auto;
}

.footer-widgets {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--gap);
    padding: 3rem 0;
}

.footer-widgets .widget {
    background: transparent;
    border: none;
    padding: 0;
    color: var(--color-footer-text);
}

.footer-widgets .widget-title {
    color: var(--color-white);
    border-bottom-color: var(--color-primary);
}

.footer-widgets .widget ul li {
    border-bottom-color: rgba(255,255,255,0.1);
}

.footer-widgets .widget ul li a {
    color: var(--color-footer-text);
}

.footer-widgets .widget ul li a:hover {
    color: var(--color-white);
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding: 1.25rem 0;
    text-align: center;
    font-size: 0.9rem;
}

/* ==========================================================================
   12. COMMENTS
   ========================================================================== */

.comments-area {
    margin-top: 2.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--color-border);
}

.comments-title {
    font-size: 1.35rem;
    margin-bottom: 1.5rem;
}

.comment-list {
    list-style: none;
}

.comment-list .comment {
    padding: 1.25rem;
    margin-bottom: 1rem;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
}

.comment-list .children {
    list-style: none;
    margin-left: 2rem;
    margin-top: 1rem;
}

.comment-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.comment-meta .avatar {
    border-radius: 50%;
    width: 40px;
    height: 40px;
}

.comment-author {
    font-weight: 600;
    font-size: 0.95rem;
}

.comment-date {
    font-size: 0.8rem;
    color: var(--color-text-light);
}

.comment-content p {
    margin-bottom: 0.5rem;
}

.reply a {
    font-size: 0.85rem;
    font-weight: 600;
}

/* Comment Form */
.comment-respond {
    margin-top: 2rem;
}

.comment-respond .comment-reply-title {
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.comment-form label {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.comment-form input[type="text"],
.comment-form input[type="email"],
.comment-form input[type="url"],
.comment-form textarea {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    font-size: 0.95rem;
    font-family: var(--font-main);
    margin-bottom: 1rem;
    transition: border-color 0.2s;
}

.comment-form input:focus,
.comment-form textarea:focus {
    border-color: var(--color-primary);
    outline: none;
}

.comment-form textarea {
    min-height: 150px;
    resize: vertical;
}

.comment-form .form-submit input[type="submit"] {
    background: var(--color-primary);
    color: var(--color-white);
    border: none;
    padding: 0.7rem 2rem;
    border-radius: var(--radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.comment-form .form-submit input[type="submit"]:hover {
    background: var(--color-primary-dark);
}

/* ==========================================================================
   13. PAGE 404
   ========================================================================== */

.error-404-content {
    text-align: center;
    padding: 4rem 1rem;
}

.error-404-content .error-code {
    font-size: 6rem;
    font-weight: 800;
    color: var(--color-primary);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.error-404-content h1 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.error-404-content p {
    color: var(--color-text-light);
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.error-404-content .search-form {
    display: flex;
    max-width: 400px;
    margin: 0 auto;
}

/* ==========================================================================
   14. SEARCH RESULTS
   ========================================================================== */

.search-results-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--color-border);
}

.search-results-header h1 {
    font-size: 1.5rem;
}

.search-results-header .search-query {
    color: var(--color-primary);
}

/* ==========================================================================
   15. HELPER CLASSES
   ========================================================================== */

.screen-reader-text {
    border: 0;
    clip: rect(1px, 1px, 1px, 1px);
    clip-path: inset(50%);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
    word-wrap: normal !important;
}

.screen-reader-text:focus {
    background-color: #f1f1f1;
    border-radius: 3px;
    box-shadow: 0 0 2px 2px rgba(0, 0, 0, 0.6);
    clip: auto !important;
    clip-path: none;
    color: #21759b;
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    height: auto;
    left: 5px;
    line-height: normal;
    padding: 15px 23px 14px;
    text-decoration: none;
    top: 5px;
    width: auto;
    z-index: 100000;
}

/* ==========================================================================
   16. RESPONSIVE DESIGN
   - Mobile first: CSS ở trên dành cho mobile
   - Dùng min-width để thêm style cho màn hình lớn hơn
   ========================================================================== */

/* --- Tablet (768px trở lên) --- */
@media (min-width: 768px) {
    .content-area {
        padding: 2.5rem 0;
    }

    .single-post-header .entry-title {
        font-size: 2.25rem;
    }
}

/* --- Desktop (1024px trở lên) --- */
@media (min-width: 1024px) {
    .header-inner {
        height: 70px;
    }

    .content-area {
        padding: 3rem 0;
    }
}

/* --- Mobile (dưới 768px) --- */
@media (max-width: 767px) {
    /* Layout: single column trên mobile */
    .content-area {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
    }

    /* Header mobile */
    .menu-toggle {
        display: block; /* Hiện hamburger button */
    }

    .main-navigation {
        display: none; /* Ẩn menu */
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--color-white);
        border-bottom: 1px solid var(--color-border);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        padding: 1rem;
    }

    .main-navigation.toggled {
        display: block; /* Hiện khi toggle */
    }

    .main-navigation ul {
        flex-direction: column;
        gap: 0;
    }

    .main-navigation .sub-menu {
        position: static;
        box-shadow: none;
        border: none;
        padding-left: 1rem;
    }

    .main-navigation li:hover > .sub-menu {
        display: flex;
    }

    /* Footer: 1 cột trên mobile */
    .footer-widgets {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    /* Post title nhỏ hơn */
    .single-post-header .entry-title {
        font-size: 1.5rem;
    }

    /* Pagination nhỏ hơn */
    .post-navigation {
        flex-direction: column;
    }

    .post-navigation .nav-next {
        text-align: left;
    }
}

/* --- Tablet ngang (768px - 1023px) --- */
@media (min-width: 768px) and (max-width: 1023px) {
    .sidebar {
        width: 280px;
    }

    .footer-widgets {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* ==========================================================================
   17. WORDPRESS CORE STYLES
   - Style cho các class WordPress tự thêm
   ========================================================================== */

/* Captions (ảnh có caption) */
.wp-caption {
    max-width: 100%;
    margin-bottom: 1rem;
}

.wp-caption-text {
    font-size: 0.85rem;
    color: var(--color-text-light);
    text-align: center;
    padding: 0.5rem;
}

/* Alignment */
.alignleft {
    float: left;
    margin: 0 1.5rem 1rem 0;
}

.alignright {
    float: right;
    margin: 0 0 1rem 1.5rem;
}

.aligncenter {
    display: block;
    margin: 1rem auto;
}

/* Gallery */
.gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
    margin: 1rem 0;
}

/* Sticky post */
.sticky .post-card {
    border-left: 4px solid var(--color-primary);
}
```

### Giải thích CSS chi tiết

| Section | Mục đích | Điểm quan trọng |
|---------|----------|-----------------|
| CSS Variables | Định nghĩa biến màu, font, spacing | Thay đổi 1 chỗ → update toàn bộ theme |
| Reset & Base | Reset CSS mặc định trình duyệt | `box-sizing: border-box` cho mọi element |
| Layout Container | Cấu trúc trang: header-content-footer | Flexbox cho content+sidebar |
| Header & Nav | Header sticky, menu dropdown | `.admin-bar .site-header` fix cho WP admin bar |
| Post Card | Card bài viết trong listing | Hover effect với `box-shadow` và `transform` |
| Single Post | Style nội dung bài viết | `pre`, `code`, `blockquote` cho developer blog |
| Pagination | Nút phân trang | `min-width: 40px` cho nút vuông đều |
| Sidebar | Widget area | Widget có border, title có border-bottom primary |
| Footer | Footer 3 cột, nền tối | CSS Grid `repeat(3, 1fr)` |
| Comments | Bình luận lồng nhau | `.children` margin-left tạo indent |
| 404 Page | Trang lỗi | Text center, font-size lớn cho error code |
| Responsive | Mobile-first responsive | 3 breakpoints: mobile, tablet, desktop |
| WP Core | WordPress tự thêm classes | `.alignleft`, `.wp-caption`, `.gallery` |

> **So sánh Laravel:** CSS trong WP theme tương đương file `resources/css/app.css` trong Laravel.
> Khác biệt: WP theme đặt CSS trong `style.css` (root), Laravel thường dùng Vite/Mix compile.

---

## 3. functions.php - Bộ Não Của Theme

> **`functions.php` là gì?** Tương đương `app/Providers/AppServiceProvider.php` trong Laravel.
> Nơi đăng ký tất cả chức năng: menus, widgets, enqueue CSS/JS, theme supports.
> File này tự động được WordPress load khi theme active.

### Code hoàn chỉnh: `functions.php`

```php
<?php
/**
 * Developer Blog - functions.php
 *
 * File này là "bộ não" của theme, tương đương AppServiceProvider trong Laravel.
 * WordPress tự động load file này khi theme được kích hoạt.
 *
 * @package Developer_Blog
 * @version 1.0.0
 */

// ═══════════════════════════════════════════════════════════════
// 1. THEME SETUP
// Hook: after_setup_theme (chạy sau khi WordPress load theme)
// Tương đương: register() trong ServiceProvider Laravel
// ═══════════════════════════════════════════════════════════════

function developer_blog_setup() {

    /**
     * Cho phép WordPress tự quản lý thẻ <title> trong <head>.
     * Không cần viết <title> thủ công trong header.php nữa.
     * WordPress sẽ tự tạo: "Tên bài viết – Tên site"
     */
    add_theme_support( 'title-tag' );

    /**
     * Bật tính năng "Ảnh đại diện" (Featured Image / Post Thumbnail)
     * cho bài viết và trang.
     * Sau khi bật, editor sẽ có box "Ảnh đại diện" bên phải.
     */
    add_theme_support( 'post-thumbnails' );

    // Đặt kích thước ảnh thumbnail mặc định (width x height, crop)
    set_post_thumbnail_size( 800, 450, true );

    /**
     * Bật tính năng Custom Logo.
     * User có thể upload logo tại: Giao diện → Tùy biến → Logo.
     * Trong template dùng: the_custom_logo() để hiển thị.
     */
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 250,
        'flex-height' => true,   // Cho phép thay đổi chiều cao
        'flex-width'  => true,   // Cho phép thay đổi chiều rộng
    ) );

    /**
     * Sử dụng HTML5 cho các form WordPress tự tạo.
     * Thay vì output HTML cũ, WP sẽ dùng HTML5 semantic tags.
     */
    add_theme_support( 'html5', array(
        'search-form',    // Form tìm kiếm dùng <input type="search">
        'comment-form',   // Form bình luận dùng HTML5
        'comment-list',   // Danh sách bình luận
        'gallery',        // Gallery dùng <figure>
        'caption',        // Caption dùng <figcaption>
        'style',          // Style tags
        'script',         // Script tags
    ) );

    /**
     * Đăng ký Navigation Menus.
     * Tương đương: định nghĩa routes navigation trong Laravel.
     * Sau khi đăng ký, user quản lý menu tại: Giao diện → Menu.
     *
     * 'primary' là slug (key), dùng trong wp_nav_menu().
     * Giá trị là label hiển thị trong admin.
     */
    register_nav_menus( array(
        'primary' => 'Menu Chính (Header)',
        'footer'  => 'Menu Footer',
    ) );

    /**
     * Cho phép WordPress thêm editor styles.
     * Gutenberg editor sẽ load CSS giống frontend.
     */
    add_theme_support( 'editor-styles' );

    /**
     * Bật align wide/full cho Gutenberg blocks.
     * Cho phép user chọn "Wide width" và "Full width" cho blocks.
     */
    add_theme_support( 'align-wide' );

    /**
     * Bật responsive embeds (YouTube, Twitter...).
     * Video embed sẽ tự co giãn theo kích thước màn hình.
     */
    add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'developer_blog_setup' );


// ═══════════════════════════════════════════════════════════════
// 2. ENQUEUE STYLES & SCRIPTS
// Hook: wp_enqueue_scripts (chạy khi render trang frontend)
// Tương đương: @vite(['resources/css/app.css']) trong Laravel Blade
// ═══════════════════════════════════════════════════════════════

function developer_blog_scripts() {

    /**
     * Đăng ký CSS chính (style.css ở root theme).
     *
     * Tham số:
     * 1. 'developer-blog-style' : Handle (tên định danh duy nhất)
     * 2. get_stylesheet_uri()   : URL đến style.css (tự detect)
     * 3. array()                : Dependencies (CSS phải load trước)
     * 4. wp_get_theme()->get('Version') : Version (cache busting)
     */
    wp_enqueue_style(
        'developer-blog-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );

    /**
     * Đăng ký JavaScript cho mobile navigation.
     *
     * Tham số:
     * 1. Handle name
     * 2. URL đến file JS
     * 3. array() : Dependencies
     * 4. Version
     * 5. true = load ở footer (trước </body>), tốt cho performance
     */
    wp_enqueue_script(
        'developer-blog-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        array(),
        wp_get_theme()->get( 'Version' ),
        true  // Load ở footer
    );

    /**
     * Nếu đang ở trang single post và có bình luận mở
     * → Load script reply để nút "Trả lời" hoạt động.
     */
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'developer_blog_scripts' );


// ═══════════════════════════════════════════════════════════════
// 3. ĐĂNG KÝ WIDGET AREAS (Sidebars)
// Hook: widgets_init
// Tương đương: đăng ký View Components trong Laravel
// ═══════════════════════════════════════════════════════════════

function developer_blog_widgets_init() {

    /**
     * Sidebar chính (bên phải content).
     * User thêm widgets tại: Giao diện → Widgets → Sidebar.
     *
     * before_widget / after_widget: HTML bọc mỗi widget.
     * WordPress tự thêm class 'widget' và id duy nhất.
     */
    register_sidebar( array(
        'name'          => 'Sidebar',
        'id'            => 'sidebar-1',
        'description'   => 'Khu vực widget bên phải trang.',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // 3 widget areas cho Footer (3 cột)
    for ( $i = 1; $i <= 3; $i++ ) {
        register_sidebar( array(
            'name'          => "Footer Cột $i",
            'id'            => "footer-$i",
            'description'   => "Widget area cho footer cột $i.",
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ) );
    }
}
add_action( 'widgets_init', 'developer_blog_widgets_init' );


// ═══════════════════════════════════════════════════════════════
// 4. HELPER FUNCTIONS (Hàm tiện ích cho template)
// Tương đương: Helper functions hoặc Blade directives trong Laravel
// ═══════════════════════════════════════════════════════════════

/**
 * Hiển thị thông tin meta bài viết: ngày đăng + tác giả.
 * Dùng trong template: developer_blog_posted_on()
 */
function developer_blog_posted_on() {
    // Ngày đăng (link đến archive ngày)
    $time_string = sprintf(
        '<time class="entry-date" datetime="%1$s">%2$s</time>',
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date() )
    );

    // Tác giả (link đến archive tác giả)
    $author_string = sprintf(
        '<span class="author"><a href="%1$s">%2$s</a></span>',
        esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
        esc_html( get_the_author() )
    );

    printf(
        '<span class="posted-on">%1$s</span><span class="byline"> · %2$s</span>',
        $time_string,
        $author_string
    );
}

/**
 * Hiển thị danh mục (categories) của bài viết.
 * Dùng trong template: developer_blog_entry_category()
 */
function developer_blog_entry_category() {
    if ( 'post' === get_post_type() ) {
        $categories_list = get_the_category_list( ', ' );
        if ( $categories_list ) {
            printf( '<span class="cat-links">%s</span>', $categories_list );
        }
    }
}

/**
 * Hiển thị tags của bài viết.
 * Dùng trong template: developer_blog_entry_tags()
 */
function developer_blog_entry_tags() {
    if ( 'post' === get_post_type() ) {
        $tags_list = get_the_tag_list( '', '' );
        if ( $tags_list ) {
            printf( '<div class="post-tags">%s</div>', $tags_list );
        }
    }
}


// ═══════════════════════════════════════════════════════════════
// 5. CUSTOMIZER (Tùy biến giao diện)
// Hook: customize_register
// Cho phép user thay đổi settings tại: Giao diện → Tùy biến
// ═══════════════════════════════════════════════════════════════

function developer_blog_customize_register( $wp_customize ) {

    // --- Section: Footer ---
    $wp_customize->add_section( 'developer_blog_footer', array(
        'title'    => 'Cài đặt Footer',
        'priority' => 120,
    ) );

    // Setting: Copyright text
    $wp_customize->add_setting( 'footer_copyright', array(
        'default'           => '© ' . date( 'Y' ) . ' Developer Blog.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'footer_copyright', array(
        'label'   => 'Nội dung Copyright',
        'section' => 'developer_blog_footer',
        'type'    => 'text',
    ) );
}
add_action( 'customize_register', 'developer_blog_customize_register' );


// ═══════════════════════════════════════════════════════════════
// 6. FILTERS (Tùy chỉnh output mặc định của WordPress)
// ═══════════════════════════════════════════════════════════════

/**
 * Giới hạn excerpt (đoạn trích) còn 25 từ.
 * Mặc định WordPress là 55 từ.
 */
function developer_blog_excerpt_length( $length ) {
    return 25;
}
add_filter( 'excerpt_length', 'developer_blog_excerpt_length' );

/**
 * Thay thế [...] ở cuối excerpt bằng "Đọc tiếp →".
 */
function developer_blog_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'developer_blog_excerpt_more' );

/**
 * Loại bỏ prefix "Chuyên mục:", "Thẻ:" khỏi archive title.
 * Ví dụ: "Chuyên mục: Laravel" → "Laravel"
 */
function developer_blog_archive_title( $title ) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_author() ) {
        $title = get_the_author();
    }
    return $title;
}
add_filter( 'get_the_archive_title', 'developer_blog_archive_title' );
```

### Giải thích functions.php theo sơ đồ

```
functions.php
│
├── 1. developer_blog_setup()          ← Hook: after_setup_theme
│   ├── add_theme_support('title-tag')       → WP tự quản lý <title>
│   ├── add_theme_support('post-thumbnails') → Bật ảnh đại diện
│   ├── add_theme_support('custom-logo')     → Bật upload logo
│   ├── add_theme_support('html5')           → HTML5 cho forms
│   ├── register_nav_menus()                 → Đăng ký 2 menus
│   ├── add_theme_support('editor-styles')   → CSS cho Gutenberg
│   ├── add_theme_support('align-wide')      → Wide/Full blocks
│   └── add_theme_support('responsive-embeds') → Video responsive
│
├── 2. developer_blog_scripts()        ← Hook: wp_enqueue_scripts
│   ├── wp_enqueue_style('style.css')        → Load CSS chính
│   ├── wp_enqueue_script('navigation.js')   → Load JS mobile menu
│   └── wp_enqueue_script('comment-reply')   → Load JS reply comment
│
├── 3. developer_blog_widgets_init()   ← Hook: widgets_init
│   ├── register_sidebar('sidebar-1')        → Sidebar chính
│   ├── register_sidebar('footer-1')         → Footer cột 1
│   ├── register_sidebar('footer-2')         → Footer cột 2
│   └── register_sidebar('footer-3')         → Footer cột 3
│
├── 4. Helper Functions
│   ├── developer_blog_posted_on()           → Hiển thị ngày + tác giả
│   ├── developer_blog_entry_category()      → Hiển thị danh mục
│   └── developer_blog_entry_tags()          → Hiển thị tags
│
├── 5. developer_blog_customize_register() ← Hook: customize_register
│   └── Setting: footer_copyright            → Tùy chỉnh text copyright
│
└── 6. Filters
    ├── excerpt_length → 25                  → Rút ngắn excerpt
    ├── excerpt_more → '...'                 → Thay [...] bằng ...
    └── get_the_archive_title → clean title  → Bỏ prefix danh mục
```

> **So sánh Laravel:**
> | WordPress | Laravel |
> |-----------|---------|
> | `add_theme_support()` | `config/app.php` providers |
> | `wp_enqueue_style()` | `@vite(['resources/css/app.css'])` |
> | `register_nav_menus()` | Route groups cho navigation |
> | `register_sidebar()` | View composers / components |
> | `add_filter()` | Middleware / Mutators |
> | Customizer API | `.env` + config files |

---

## 4. header.php - Phần Đầu Trang

> **`header.php` là gì?** Tương đương phần trên của `layouts/app.blade.php` trong Laravel.
> Chứa `<head>`, mở `<body>`, logo, navigation menu.
> Được gọi bằng `get_header()` từ mọi template khác.

### Preview header

```
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│  [🖼 Logo]  Developer Blog    [Trang chủ] [Giới thiệu] [🔍] │
│                                                              │
└─────────────────────────────────────────────────────────────┘
     ↑              ↑                    ↑              ↑
 custom-logo   site-title         wp_nav_menu()   search toggle
```

### Code hoàn chỉnh: `header.php`

```php
<?php
/**
 * header.php - Phần đầu của mọi trang
 *
 * Tương đương phần trên của layouts/app.blade.php trong Laravel:
 * <!DOCTYPE html> đến hết <nav>
 *
 * @package Developer_Blog
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<!--
    language_attributes() → output: lang="vi" (hoặc lang="en-US")
    Dựa trên Settings → General → Site Language
    Tương đương: <html lang="{{ app()->getLocale() }}"> trong Laravel
-->

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <!--
        bloginfo('charset') → output: UTF-8
        Lấy từ Settings → Reading → Encoding
    -->

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--
        Responsive meta tag - bắt buộc cho mobile
    -->

    <?php wp_head(); ?>
    <!--
        ★ QUAN TRỌNG: wp_head() output tất cả CSS, JS, meta tags
        - CSS đã enqueue bằng wp_enqueue_style()
        - <title> tag (từ add_theme_support('title-tag'))
        - Meta tags từ SEO plugins (Yoast, RankMath)
        - Canonical URLs
        - RSS feed links
        - wp_enqueue_script() có $in_footer = false

        Tương đương: @vite() + @stack('styles') trong Laravel Blade
    -->
</head>

<body <?php body_class(); ?>>
<!--
    body_class() → thêm CSS classes tự động vào <body>:
    - Trang chủ: class="home blog"
    - Bài viết: class="single single-post postid-123"
    - Trang: class="page page-id-42 page-template-default"
    - Đăng nhập: class="logged-in admin-bar"

    Rất hữu ích cho CSS targeting!
    Tương đương: không có trong Laravel (bạn phải tự làm)
-->

<?php wp_body_open(); ?>
<!--
    wp_body_open() → hook cho plugins thêm code ngay sau <body>
    - Google Tag Manager
    - Facebook Pixel
    - Skip navigation link (accessibility)
-->

<div class="site-container">

    <header class="site-header" role="banner">
        <div class="container header-inner">

            <!-- === LOGO & TÊN SITE === -->
            <div class="site-branding">
                <?php
                // Nếu user đã upload logo (tại Giao diện → Tùy biến → Logo)
                if ( has_custom_logo() ) :
                    the_custom_logo();
                    // Output: <a href="URL"><img class="custom-logo" src="logo.png"></a>
                endif;
                ?>

                <div>
                    <?php if ( is_front_page() && is_home() ) : ?>
                        <!--
                            Trang chủ: dùng <h1> cho SEO
                            (chỉ trang chủ mới dùng h1 cho site title)
                        -->
                        <h1 class="site-title">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php bloginfo( 'name' ); ?>
                            </a>
                        </h1>
                    <?php else : ?>
                        <!--
                            Các trang khác: dùng <p> (h1 dành cho tiêu đề bài viết)
                        -->
                        <p class="site-title">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php bloginfo( 'name' ); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php
                    // Mô tả ngắn của site (Settings → General → Tagline)
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description ) :
                    ?>
                        <p class="site-description"><?php echo $description; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- /site-branding -->

            <!-- === NAVIGATION MENU === -->
            <nav class="main-navigation" id="site-navigation" role="navigation"
                 aria-label="<?php esc_attr_e( 'Menu chính', 'developer-blog' ); ?>">
                <?php
                /**
                 * wp_nav_menu() - Hiển thị menu đã đăng ký.
                 *
                 * 'theme_location' => 'primary' : Lấy menu gán cho vị trí "primary"
                 *                                  (đăng ký ở register_nav_menus)
                 * 'menu_class'     => 'menu'    : Class CSS cho thẻ <ul>
                 * 'container'      => false      : Không bọc <div> bên ngoài
                 * 'fallback_cb'    => false      : Không hiện gì nếu chưa tạo menu
                 *
                 * Output: <ul class="menu"><li><a href="/">Trang chủ</a></li>...</ul>
                 *
                 * Tương đương: @include('partials.navigation') trong Laravel
                 * Khác biệt: User quản lý menu bằng drag & drop trong WP Admin
                 */
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_class'     => 'menu',
                    'container'      => false,
                    'fallback_cb'    => false,
                ) );
                ?>
            </nav>

            <!-- === NÚT HAMBURGER (mobile) + SEARCH === -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <button class="header-search-toggle" aria-label="Tìm kiếm">
                    &#128269;
                </button>

                <button class="menu-toggle" aria-controls="site-navigation"
                        aria-expanded="false" aria-label="Menu">
                    <!--
                        Hamburger icon (3 gạch ngang) bằng SVG
                        Chỉ hiện trên mobile (CSS: display:none trên desktop)
                    -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>

        </div><!-- /header-inner -->
    </header><!-- /site-header -->

    <div class="container">
```

### Giải thích từng WordPress function trong header.php

| Function | Output | Mục đích |
|----------|--------|----------|
| `language_attributes()` | `lang="vi"` | Ngôn ngữ cho `<html>` tag |
| `bloginfo('charset')` | `UTF-8` | Encoding cho `<meta charset>` |
| `wp_head()` | CSS, JS, meta tags | **Bắt buộc** - Load tất cả assets |
| `body_class()` | `class="home blog logged-in..."` | CSS classes tự động cho `<body>` |
| `wp_body_open()` | Hook content | Cho plugins thêm code sau `<body>` |
| `has_custom_logo()` | `true/false` | Kiểm tra đã upload logo chưa |
| `the_custom_logo()` | `<a><img src="logo.png"></a>` | Hiển thị logo |
| `home_url('/')` | `https://yoursite.com/` | URL trang chủ |
| `bloginfo('name')` | `Developer Blog` | Tên site |
| `bloginfo('description')` | `Blog chia sẻ kiến thức` | Tagline |
| `wp_nav_menu()` | `<ul class="menu">...</ul>` | Menu navigation |

---

## 5. footer.php - Phần Chân Trang

> **`footer.php` là gì?** Tương đương phần cuối `layouts/app.blade.php` trong Laravel.
> Chứa footer widgets, copyright, đóng `</body>` và `</html>`.
> Được gọi bằng `get_footer()` từ mọi template khác.

### Preview footer

```
┌─────────────────────────────────────────────────────────────┐
│  ██████████████████ FOOTER (nền tối) ██████████████████████ │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │ Footer Cột 1 │  │ Footer Cột 2 │  │  Footer Cột 3    │  │
│  │ (Widget)     │  │ (Widget)     │  │  (Widget)        │  │
│  │ About text   │  │ Links list   │  │  Contact info    │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
│  ─────────────────────────────────────────────────────────  │
│        © 2026 Developer Blog. Powered by WordPress.         │
└─────────────────────────────────────────────────────────────┘
```

### Code hoàn chỉnh: `footer.php`

```php
<?php
/**
 * footer.php - Phần cuối của mọi trang
 *
 * Tương đương phần dưới layouts/app.blade.php:
 * Footer → </body> → </html>
 *
 * @package Developer_Blog
 */
?>
    </div><!-- /.container (mở ở header.php) -->

    <footer class="site-footer" role="contentinfo">

        <?php
        // Kiểm tra có ít nhất 1 footer widget area có content không
        if ( is_active_sidebar( 'footer-1' ) ||
             is_active_sidebar( 'footer-2' ) ||
             is_active_sidebar( 'footer-3' ) ) :
        ?>
        <div class="container">
            <div class="footer-widgets">

                <!-- Footer Cột 1 -->
                <div class="footer-column">
                    <?php
                    if ( is_active_sidebar( 'footer-1' ) ) :
                        dynamic_sidebar( 'footer-1' );
                        // dynamic_sidebar() hiển thị tất cả widgets
                        // đã được user kéo vào area "Footer Cột 1"
                        // tại Giao diện → Widgets
                    endif;
                    ?>
                </div>

                <!-- Footer Cột 2 -->
                <div class="footer-column">
                    <?php
                    if ( is_active_sidebar( 'footer-2' ) ) :
                        dynamic_sidebar( 'footer-2' );
                    endif;
                    ?>
                </div>

                <!-- Footer Cột 3 -->
                <div class="footer-column">
                    <?php
                    if ( is_active_sidebar( 'footer-3' ) ) :
                        dynamic_sidebar( 'footer-3' );
                    endif;
                    ?>
                </div>

            </div><!-- /footer-widgets -->
        </div>
        <?php endif; ?>

        <!-- Copyright -->
        <div class="footer-bottom">
            <div class="container">
                <?php
                // Lấy text copyright từ Customizer (hoặc dùng default)
                $copyright = get_theme_mod(
                    'footer_copyright',
                    '&copy; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) . '.'
                );
                echo esc_html( $copyright );
                ?>
                <span>Powered by <a href="https://wordpress.org" style="color:var(--color-primary);">WordPress</a>.</span>
            </div>
        </div>

    </footer><!-- /site-footer -->

</div><!-- /.site-container (mở ở header.php) -->

<?php wp_footer(); ?>
<!--
    ★ QUAN TRỌNG: wp_footer() output tất cả JS ở cuối trang
    - Scripts enqueue với $in_footer = true
    - Admin bar (nếu đăng nhập)
    - Analytics code
    - Plugin scripts

    Tương đương: @stack('scripts') + </body></html> trong Laravel
-->

</body>
</html>
```

### Cấu trúc HTML mở/đóng giữa header.php và footer.php

```
header.php MỞ:                    footer.php ĐÓNG:
─────────────                      ──────────────
<html>
  <head>...</head>
  <body>
    <div class="site-container">
      <header>...</header>
      <div class="container">      </div>        ← container
                                     <footer>...</footer>
                                   </div>         ← site-container
                                   </body>
                                   </html>
```

> **Lưu ý:** `<div class="container">` mở ở cuối `header.php` và đóng ở đầu `footer.php`.
> Tất cả content ở giữa (index.php, single.php...) đều nằm trong container này.

---

## 6. index.php - Trang Blog Chính

> **`index.php` là gì?** Template bắt buộc, là fallback cuối cùng của Template Hierarchy.
> Thường dùng hiển thị danh sách bài viết (blog listing).
> Tương đương `resources/views/posts/index.blade.php` trong Laravel.

### Preview blog listing

```
┌──────────────────────────────────┐  ┌──────────────────────┐
│  ┌────────────────────────────┐  │  │  ┌────────────────┐  │
│  │     [Ảnh đại diện]        │  │  │  │   Widget 1     │  │
│  ├────────────────────────────┤  │  │  └────────────────┘  │
│  │  📁 Laravel · 📅 15/01    │  │  │                      │
│  │  Tiêu đề bài viết 1      │  │  │  ┌────────────────┐  │
│  │  Đoạn trích nội dung...   │  │  │  │   Widget 2     │  │
│  │              Đọc tiếp →   │  │  │  └────────────────┘  │
│  └────────────────────────────┘  │  │                      │
│                                  │  │  ┌────────────────┐  │
│  ┌────────────────────────────┐  │  │  │   Widget 3     │  │
│  │     [Ảnh đại diện]        │  │  │  └────────────────┘  │
│  ├────────────────────────────┤  │  │                      │
│  │  📁 WordPress · 📅 12/01  │  │  └──────────────────────┘
│  │  Tiêu đề bài viết 2      │  │
│  │  Đoạn trích nội dung...   │  │     MAIN         SIDEBAR
│  │              Đọc tiếp →   │  │     (flex:1)     (320px)
│  └────────────────────────────┘  │
│                                  │
│  « Trước   [1] [2] [3]   Sau » │
└──────────────────────────────────┘
```

### Code hoàn chỉnh: `index.php`

```php
<?php
/**
 * index.php - Template mặc định (Blog Listing)
 *
 * Đây là template BẮT BUỘC duy nhất (cùng với style.css).
 * Hiển thị danh sách bài viết với The Loop.
 *
 * Tương đương: resources/views/posts/index.blade.php trong Laravel
 * Route tương đương: Route::get('/posts', [PostController::class, 'index'])
 *
 * @package Developer_Blog
 */

get_header(); // Include header.php (tương đương @extends('layouts.app'))
?>

<div class="content-area">

    <main class="main-content" role="main">

        <?php
        /**
         * THE LOOP - Trái tim của WordPress
         *
         * WordPress đã tự chạy WP_Query dựa trên URL trước khi đến template.
         * have_posts() kiểm tra còn bài viết không.
         * the_post() setup global $post cho bài viết hiện tại.
         *
         * Tương đương Laravel:
         * @foreach($posts as $post)
         *     @include('partials.post-card', ['post' => $post])
         * @endforeach
         */
        if ( have_posts() ) :

            while ( have_posts() ) :
                the_post();

                /**
                 * get_template_part() include một file template part.
                 * 'template-parts/content' → load file template-parts/content.php
                 *
                 * Tương đương: @include('partials.post-card') trong Laravel
                 */
                get_template_part( 'template-parts/content' );

            endwhile;

            // Pagination: hiển thị nút phân trang
            the_posts_pagination( array(
                'mid_size'  => 2,             // Số trang hiển thị 2 bên trang hiện tại
                'prev_text' => '&laquo; Trước', // Text nút "Trang trước"
                'next_text' => 'Sau &raquo;',   // Text nút "Trang sau"
            ) );

        else :

            // Không có bài viết nào → hiển thị thông báo
            get_template_part( 'template-parts/content', 'none' );
            // Load file: template-parts/content-none.php

        endif;
        ?>

    </main>

    <?php get_sidebar(); // Include sidebar.php ?>

</div><!-- /content-area -->

<?php get_footer(); // Include footer.php ?>
```

### Luồng hoạt động của index.php

```
URL: yoursite.com/ (trang chủ blog)
         │
         ▼
WordPress tự chạy WP_Query:
  SELECT * FROM wp_posts WHERE post_type='post' AND post_status='publish'
  ORDER BY post_date DESC LIMIT 10
         │
         ▼
Template Hierarchy chọn: index.php
         │
         ▼
┌─ index.php ──────────────────────────────────┐
│                                               │
│  get_header()  → load header.php              │
│                                               │
│  if (have_posts()):     ← còn bài?           │
│    while (have_posts()): ← lặp từng bài      │
│      the_post();        ← setup $post         │
│      get_template_part('template-parts/content')│
│    endwhile;                                   │
│    the_posts_pagination() ← phân trang         │
│  else:                                         │
│    get_template_part('template-parts/content-none')│
│  endif;                                        │
│                                               │
│  get_sidebar() → load sidebar.php             │
│  get_footer()  → load footer.php              │
│                                               │
└───────────────────────────────────────────────┘
```

---

## 7. single.php - Trang Bài Viết Đơn

> **`single.php` là gì?** Template hiển thị 1 bài viết chi tiết.
> Tương đương `resources/views/posts/show.blade.php` trong Laravel.
> WordPress tự chọn file này khi URL là bài viết đơn (ví dụ: `/hello-world/`).

### Code hoàn chỉnh: `single.php`

```php
<?php
/**
 * single.php - Template bài viết đơn
 *
 * WordPress tự chọn file này khi: is_single() === true
 * URL: yoursite.com/ten-bai-viet/
 *
 * Tương đương Laravel:
 *   Route::get('/posts/{slug}', [PostController::class, 'show'])
 *   resources/views/posts/show.blade.php
 *
 * @package Developer_Blog
 */

get_header();
?>

<div class="content-area">

    <main class="main-content" role="main">

        <?php
        while ( have_posts() ) :
            the_post();
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <!--
                the_ID() → ID bài viết (ví dụ: 123)
                post_class() → classes tự động: "post-123 type-post status-publish
                                format-standard has-post-thumbnail category-laravel"
            -->

            <!-- === HEADER BÀI VIẾT === -->
            <header class="single-post-header">

                <!-- Meta: Danh mục + Ngày + Tác giả -->
                <div class="post-meta">
                    <?php developer_blog_entry_category(); ?>
                    <?php developer_blog_posted_on(); ?>
                </div>

                <!-- Tiêu đề bài viết (h1 cho SEO) -->
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <!-- === ẢNH ĐẠI DIỆN === -->
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="single-post-thumbnail">
                    <?php
                    the_post_thumbnail( 'large' );
                    // Output: <img src="anh-dai-dien-1024x576.jpg" alt="...">
                    // 'large' = kích thước ảnh (thumbnail, medium, large, full)
                    ?>
                </div>
            <?php endif; ?>

            <!-- === NỘI DUNG BÀI VIẾT === -->
            <div class="entry-content">
                <?php
                the_content();
                // Output toàn bộ nội dung bài viết
                // Tự động xử lý: shortcodes, oEmbed, wpautop (thêm <p>)
                // Tương đương: {!! $post->content !!} trong Laravel Blade

                // Hỗ trợ bài viết chia nhiều trang (<!--nextpage-->)
                wp_link_pages( array(
                    'before' => '<div class="page-links">Trang:',
                    'after'  => '</div>',
                ) );
                ?>
            </div>

            <!-- === TAGS === -->
            <?php developer_blog_entry_tags(); ?>

        </article>

        <?php endwhile; ?>

        <!-- === ĐIỀU HƯỚNG BÀI TRƯỚC / SAU === -->
        <?php
        $prev_post = get_previous_post();
        $next_post = get_next_post();

        if ( $prev_post || $next_post ) :
        ?>
        <nav class="post-navigation">
            <?php if ( $prev_post ) : ?>
                <div class="nav-previous">
                    <a href="<?php echo get_permalink( $prev_post ); ?>">
                        <span class="nav-label">&larr; Bài trước</span>
                        <span class="nav-title"><?php echo esc_html( $prev_post->post_title ); ?></span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ( $next_post ) : ?>
                <div class="nav-next">
                    <a href="<?php echo get_permalink( $next_post ); ?>">
                        <span class="nav-label">Bài sau &rarr;</span>
                        <span class="nav-title"><?php echo esc_html( $next_post->post_title ); ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <!-- === BÌNH LUẬN === -->
        <?php
        // Nếu bài viết cho phép bình luận HOẶC đã có bình luận
        if ( comments_open() || get_comments_number() ) :
            comments_template();
            // Load file: comments.php
            // Tương đương: @include('partials.comments', ['post' => $post])
        endif;
        ?>

    </main>

    <?php get_sidebar(); ?>

</div><!-- /content-area -->

<?php get_footer(); ?>
```

---

## 8. page.php - Trang Tĩnh

> **`page.php` là gì?** Template cho trang tĩnh (About, Contact, Privacy Policy...).
> Tương đương `resources/views/pages/show.blade.php` trong Laravel.
> WordPress chọn file này khi URL là page (ví dụ: `/gioi-thieu/`).

### Code hoàn chỉnh: `page.php`

```php
<?php
/**
 * page.php - Template trang tĩnh
 *
 * WordPress tự chọn file này khi: is_page() === true
 * URL: yoursite.com/gioi-thieu/ hoặc yoursite.com/lien-he/
 *
 * Tương đương Laravel:
 *   Route::get('/about', [PageController::class, 'show'])
 *
 * @package Developer_Blog
 */

get_header();
?>

<div class="content-area">

    <main class="main-content" role="main">

        <?php
        while ( have_posts() ) :
            the_post();
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <!-- Tiêu đề trang -->
            <header class="page-header-section">
                <h1 class="page-title"><?php the_title(); ?></h1>
            </header>

            <!-- Ảnh đại diện (nếu có) -->
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="single-post-thumbnail">
                    <?php the_post_thumbnail( 'large' ); ?>
                </div>
            <?php endif; ?>

            <!-- Nội dung trang -->
            <div class="entry-content">
                <?php
                the_content();

                wp_link_pages( array(
                    'before' => '<div class="page-links">Trang:',
                    'after'  => '</div>',
                ) );
                ?>
            </div>

        </article>

        <?php endwhile; ?>

        <!-- Bình luận (nếu trang bật comment) -->
        <?php
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif;
        ?>

    </main>

    <?php get_sidebar(); ?>

</div>

<?php get_footer(); ?>
```

---

## 9. archive.php - Trang Danh Sách

> **`archive.php` là gì?** Template cho trang danh sách: category, tag, author, date.
> Tương đương `resources/views/posts/index.blade.php` nhưng có filter theo category.
> WordPress chọn file này khi URL là archive (ví dụ: `/category/laravel/`).

### Code hoàn chỉnh: `archive.php`

```php
<?php
/**
 * archive.php - Template trang danh sách
 *
 * Dùng cho: category, tag, author, date archives
 * URL: /category/laravel/, /tag/php/, /author/admin/, /2026/01/
 *
 * Tương đương Laravel:
 *   Route::get('/posts?category=laravel', [PostController::class, 'index'])
 *
 * @package Developer_Blog
 */

get_header();
?>

<div class="content-area">

    <main class="main-content" role="main">

        <!-- === HEADER ARCHIVE === -->
        <header class="archive-header">
            <?php
            /**
             * the_archive_title() output tiêu đề archive.
             * Đã được filter ở functions.php để bỏ prefix "Chuyên mục:"
             * Output: "Laravel" thay vì "Chuyên mục: Laravel"
             */
            the_archive_title( '<h1 class="archive-title">', '</h1>' );

            /**
             * the_archive_description() output mô tả của category/tag.
             * User nhập tại: Bài viết → Chuyên mục → Mô tả
             */
            the_archive_description( '<div class="archive-description">', '</div>' );
            ?>
        </header>

        <?php
        // The Loop - giống index.php
        if ( have_posts() ) :

            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content' );
            endwhile;

            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '&laquo; Trước',
                'next_text' => 'Sau &raquo;',
            ) );

        else :
            get_template_part( 'template-parts/content', 'none' );
        endif;
        ?>

    </main>

    <?php get_sidebar(); ?>

</div>

<?php get_footer(); ?>
```

---

## 10. search.php - Trang Tìm Kiếm

> **`search.php` là gì?** Template hiển thị kết quả tìm kiếm.
> WordPress chọn file này khi URL là `/?s=từ+khóa`.

### Code hoàn chỉnh: `search.php`

```php
<?php
/**
 * search.php - Template kết quả tìm kiếm
 *
 * WordPress tự chọn file này khi: is_search() === true
 * URL: yoursite.com/?s=laravel
 *
 * @package Developer_Blog
 */

get_header();
?>

<div class="content-area">

    <main class="main-content" role="main">

        <!-- Header kết quả tìm kiếm -->
        <header class="search-results-header">
            <h1>
                <?php
                printf(
                    'Kết quả tìm kiếm: <span class="search-query">"%s"</span>',
                    esc_html( get_search_query() )
                    // get_search_query() lấy từ khóa user nhập
                );
                ?>
            </h1>
        </header>

        <?php
        if ( have_posts() ) :

            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content' );
            endwhile;

            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '&laquo; Trước',
                'next_text' => 'Sau &raquo;',
            ) );

        else :
            get_template_part( 'template-parts/content', 'none' );
        endif;
        ?>

    </main>

    <?php get_sidebar(); ?>

</div>

<?php get_footer(); ?>
```

---

## 11. searchform.php - Form Tìm Kiếm

> **`searchform.php` là gì?** Tùy chỉnh giao diện form tìm kiếm.
> Mặc định WordPress tạo form search, nhưng file này cho phép override.
> Được gọi bởi `get_search_form()` hoặc widget Search.

### Code hoàn chỉnh: `searchform.php`

```php
<?php
/**
 * searchform.php - Form tìm kiếm tùy chỉnh
 *
 * Override form mặc định của WordPress.
 * Được load khi gọi get_search_form() hoặc dùng Search widget.
 *
 * @package Developer_Blog
 */
?>

<form role="search" method="get" class="search-form"
      action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <!--
        action = URL trang chủ
        WordPress sẽ nhận param ?s=... và chuyển đến search.php
    -->

    <label class="screen-reader-text" for="search-field">
        Tìm kiếm:
    </label>

    <input type="search"
           id="search-field"
           class="search-field"
           placeholder="Tìm kiếm bài viết..."
           value="<?php echo get_search_query(); ?>"
           name="s"
           required>
    <!--
        name="s" là param WordPress dùng cho search
        get_search_query() giữ lại từ khóa đã nhập
    -->

    <button type="submit" class="search-submit">
        Tìm
    </button>
</form>
```

---

## 12. 404.php - Trang Không Tìm Thấy

> **`404.php` là gì?** Trang hiển thị khi URL không tồn tại.
> WordPress chọn file này khi: `is_404() === true`.

### Preview 404

```
┌─────────────────────────────────────────────┐
│                                              │
│                   404                        │
│             (font-size: 6rem)                │
│                                              │
│     Trang không tìm thấy                    │
│                                              │
│     Xin lỗi, nội dung bạn tìm              │
│     không tồn tại hoặc đã bị xóa.          │
│                                              │
│     [________Tìm kiếm________] [Tìm]       │
│                                              │
└─────────────────────────────────────────────┘
```

### Code hoàn chỉnh: `404.php`

```php
<?php
/**
 * 404.php - Trang lỗi 404
 *
 * WordPress tự chọn file này khi không tìm thấy nội dung.
 * URL: yoursite.com/trang-khong-ton-tai/
 *
 * @package Developer_Blog
 */

get_header();
?>

<main class="main-content" role="main">

    <div class="error-404-content">

        <div class="error-code">404</div>

        <h1>Trang không tìm thấy</h1>

        <p>
            Xin lỗi, nội dung bạn tìm không tồn tại hoặc đã bị xóa.
            Hãy thử tìm kiếm bên dưới:
        </p>

        <?php get_search_form(); ?>
        <!-- Load searchform.php để hiển thị form tìm kiếm -->

    </div>

</main>

<?php get_footer(); ?>
```

---

## 13. sidebar.php - Thanh Bên

> **`sidebar.php` là gì?** Hiển thị widget area bên phải.
> User thêm widgets tại: Giao diện → Widgets → Sidebar.
> Được gọi bằng `get_sidebar()` từ index, single, page, archive...

### Code hoàn chỉnh: `sidebar.php`

```php
<?php
/**
 * sidebar.php - Widget area bên phải
 *
 * Hiển thị các widgets user đã thêm tại Giao diện → Widgets.
 * Nếu chưa thêm widget nào → không hiển thị gì.
 *
 * Tương đương: @include('partials.sidebar') trong Laravel
 *
 * @package Developer_Blog
 */

// Kiểm tra có widget nào trong sidebar không
if ( ! is_active_sidebar( 'sidebar-1' ) ) {
    return; // Không có widget → thoát, không render gì
}
?>

<aside class="sidebar" role="complementary">
    <?php
    dynamic_sidebar( 'sidebar-1' );
    // Hiển thị tất cả widgets trong area 'sidebar-1'
    // Mỗi widget được bọc bởi before_widget / after_widget
    // đã đăng ký trong register_sidebar() ở functions.php
    ?>
</aside>
```

---

## 14. comments.php - Bình Luận

> **`comments.php` là gì?** Template hiển thị danh sách bình luận + form gửi bình luận.
> Được gọi bằng `comments_template()` trong single.php và page.php.
> WordPress tự quản lý bình luận (lưu DB, kiểm duyệt, spam).

### Preview comments

```
┌─────────────────────────────────────────────────┐
│  💬 Có 3 bình luận                              │
│                                                  │
│  ┌── [Avatar] Nguyễn Văn A ── 15/01/2026 ────┐ │
│  │   Bài viết rất hay, cảm ơn bạn!           │ │
│  │                              [Trả lời]     │ │
│  │                                             │ │
│  │   ┌── [Avatar] Admin ── 16/01/2026 ─────┐ │ │
│  │   │   Cảm ơn bạn đã đọc!               │ │ │
│  │   │                          [Trả lời]  │ │ │
│  │   └─────────────────────────────────────┘ │ │
│  └─────────────────────────────────────────────┘ │
│                                                  │
│  ┌── [Avatar] Trần Thị B ── 17/01/2026 ──────┐ │
│  │   Mình đã áp dụng thành công!              │ │
│  │                              [Trả lời]     │ │
│  └─────────────────────────────────────────────┘ │
│                                                  │
│  ── Viết bình luận ──────────────────────────── │
│  Tên:  [________________________]                │
│  Email:[________________________]                │
│  Nội dung:                                       │
│  [______________________________________________]│
│  [______________________________________________]│
│                                [  Gửi bình luận ]│
└─────────────────────────────────────────────────┘
```

### Code hoàn chỉnh: `comments.php`

```php
<?php
/**
 * comments.php - Template bình luận
 *
 * Hiển thị danh sách comments + form gửi comment.
 * Được load bởi comments_template() trong single.php / page.php.
 *
 * Tương đương:
 *   @include('partials.comments', ['comments' => $post->comments])
 *   + Form submit comment
 *
 * @package Developer_Blog
 */

// Bảo vệ: không cho truy cập trực tiếp file này
if ( post_password_required() ) {
    return; // Bài viết có mật khẩu → không hiện comment
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>

        <!-- Tiêu đề: "Có X bình luận" -->
        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            printf(
                'Có %s bình luận',
                number_format_i18n( $comment_count )
            );
            ?>
        </h2>

        <!-- Danh sách bình luận -->
        <ol class="comment-list">
            <?php
            /**
             * wp_list_comments() hiển thị tất cả comments.
             * WordPress tự xử lý:
             * - Phân cấp (reply lồng nhau)
             * - Avatar (Gravatar)
             * - Nút "Trả lời"
             * - Pending comments
             *
             * 'style' => 'ol'    : Dùng <ol> thay vì <div>
             * 'short_ping' => true: Pingback/trackback hiển thị ngắn gọn
             */
            wp_list_comments( array(
                'style'      => 'ol',
                'short_ping' => true,
                'avatar_size' => 40,
            ) );
            ?>
        </ol>

        <!-- Phân trang bình luận (nếu nhiều comments) -->
        <?php
        the_comments_navigation( array(
            'prev_text' => '&larr; Bình luận cũ hơn',
            'next_text' => 'Bình luận mới hơn &rarr;',
        ) );
        ?>

    <?php endif; // have_comments() ?>

    <!-- Form gửi bình luận -->
    <?php
    /**
     * comment_form() output form comment hoàn chỉnh.
     * WordPress tự xử lý:
     * - Hiển thị form HTML
     * - Validate input
     * - Lưu vào database
     * - Gửi email thông báo
     *
     * Bạn chỉ cần gọi hàm này, WP làm hết phần còn lại.
     * Tương đương: form AJAX submit trong Laravel nhưng không cần viết controller!
     */
    comment_form( array(
        'title_reply'        => 'Viết bình luận',
        'title_reply_to'     => 'Trả lời %s',
        'cancel_reply_link'  => 'Hủy trả lời',
        'label_submit'       => 'Gửi bình luận',
        'comment_notes_after' => '',
    ) );
    ?>

</div><!-- /comments-area -->
```

---

## 15. Template Parts - Các Phần Tái Sử Dụng

> **Template Parts là gì?** Tương đương Blade Components / Partials trong Laravel.
> Đặt trong thư mục `template-parts/`, gọi bằng `get_template_part()`.
> Giúp tái sử dụng code: index.php, archive.php, search.php đều dùng chung `content.php`.

### 15.1. `template-parts/content.php` - Card Bài Viết

> File này là 1 "card" bài viết trong danh sách blog.
> Được gọi bởi `get_template_part('template-parts/content')` trong index, archive, search.

```php
<?php
/**
 * template-parts/content.php - Card bài viết trong listing
 *
 * Hiển thị: Thumbnail + Meta + Title + Excerpt + Read more
 * Được dùng trong: index.php, archive.php, search.php
 *
 * Tương đương: resources/views/components/post-card.blade.php
 *
 * @package Developer_Blog
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
    <!--
        post_class('post-card') thêm class 'post-card' cùng với
        các class WP tự động: "post-123 type-post status-publish
        format-standard has-post-thumbnail category-laravel tag-php"
    -->

    <!-- Ảnh đại diện (nếu có) -->
    <?php if ( has_post_thumbnail() ) : ?>
        <a href="<?php the_permalink(); ?>" class="post-thumbnail">
            <?php
            the_post_thumbnail( 'medium_large' );
            // 'medium_large' = 768px width (tốt cho card)
            ?>
        </a>
    <?php endif; ?>

    <div class="post-card-body">

        <!-- Meta: Danh mục + Ngày -->
        <div class="post-meta">
            <?php developer_blog_entry_category(); ?>
            <?php developer_blog_posted_on(); ?>
        </div>

        <!-- Tiêu đề (link đến bài viết) -->
        <h2 class="entry-title">
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
        </h2>

        <!-- Đoạn trích -->
        <div class="entry-excerpt">
            <?php the_excerpt(); ?>
            <!--
                the_excerpt() output 25 từ đầu tiên (đã filter ở functions.php)
                + "..." ở cuối
            -->
        </div>

        <!-- Link đọc tiếp -->
        <a href="<?php the_permalink(); ?>" class="read-more">
            Đọc tiếp &rarr;
        </a>

    </div>

</article>
```

### 15.2. `template-parts/content-none.php` - Không Có Kết Quả

```php
<?php
/**
 * template-parts/content-none.php
 *
 * Hiển thị khi không có bài viết nào (loop rỗng).
 * Ví dụ: tìm kiếm không có kết quả, category rỗng.
 *
 * @package Developer_Blog
 */
?>

<section class="no-results">

    <header>
        <h1 class="page-title">Không có nội dung</h1>
    </header>

    <div class="page-content">
        <?php if ( is_search() ) : ?>
            <p>
                Không tìm thấy kết quả nào cho từ khóa
                "<strong><?php echo esc_html( get_search_query() ); ?></strong>".
                Hãy thử tìm kiếm với từ khóa khác:
            </p>
            <?php get_search_form(); ?>

        <?php else : ?>
            <p>Chưa có bài viết nào. Hãy quay lại sau nhé!</p>
            <?php get_search_form(); ?>

        <?php endif; ?>
    </div>

</section>
```

---

## 16. assets/js/navigation.js - Menu Mobile

> **File này làm gì?** Toggle menu mobile khi click hamburger button.
> Enqueue ở `functions.php` bằng `wp_enqueue_script()`.

### Code hoàn chỉnh: `assets/js/navigation.js`

```javascript
/**
 * navigation.js - Mobile Menu Toggle
 *
 * Khi click hamburger button trên mobile:
 * - Thêm/bỏ class 'toggled' vào <nav>
 * - Menu hiện/ẩn (CSS xử lý display)
 * - Cập nhật aria-expanded cho accessibility
 *
 * @package Developer_Blog
 */

( function() {
    'use strict';

    // Tìm nút hamburger và navigation
    var menuToggle = document.querySelector( '.menu-toggle' );
    var navigation = document.getElementById( 'site-navigation' );

    // Nếu không tìm thấy → thoát (tránh lỗi JS)
    if ( ! menuToggle || ! navigation ) {
        return;
    }

    // Khi click hamburger button
    menuToggle.addEventListener( 'click', function() {

        // Toggle class 'toggled' trên <nav>
        navigation.classList.toggle( 'toggled' );

        // Cập nhật aria-expanded (accessibility)
        var isExpanded = navigation.classList.contains( 'toggled' );
        menuToggle.setAttribute( 'aria-expanded', isExpanded ? 'true' : 'false' );
    } );

    // Đóng menu khi click bên ngoài
    document.addEventListener( 'click', function( event ) {
        if ( ! navigation.contains( event.target ) &&
             ! menuToggle.contains( event.target ) ) {
            navigation.classList.remove( 'toggled' );
            menuToggle.setAttribute( 'aria-expanded', 'false' );
        }
    } );

    // Đóng menu khi nhấn Escape
    document.addEventListener( 'keydown', function( event ) {
        if ( event.key === 'Escape' ) {
            navigation.classList.remove( 'toggled' );
            menuToggle.setAttribute( 'aria-expanded', 'false' );
        }
    } );

} )();
```

---

## 17. Hướng Dẫn Cài Đặt & Kích Hoạt

### Bước 1: Tạo thư mục theme

```bash
# Vào thư mục themes của WordPress
cd /đường-dẫn/wp-content/themes/

# Tạo thư mục theme và các thư mục con
mkdir -p developer-blog/template-parts
mkdir -p developer-blog/assets/js
```

### Bước 2: Tạo các file

Tạo từng file theo thứ tự và copy code từ các phần trên:

```bash
# Danh sách file cần tạo:
developer-blog/
├── style.css                         ← Phần 2
├── functions.php                     ← Phần 3
├── header.php                        ← Phần 4
├── footer.php                        ← Phần 5
├── index.php                         ← Phần 6
├── single.php                        ← Phần 7
├── page.php                          ← Phần 8
├── archive.php                       ← Phần 9
├── search.php                        ← Phần 10
├── searchform.php                    ← Phần 11
├── 404.php                           ← Phần 12
├── sidebar.php                       ← Phần 13
├── comments.php                      ← Phần 14
├── template-parts/
│   ├── content.php                   ← Phần 15.1
│   └── content-none.php              ← Phần 15.2
└── assets/js/
    └── navigation.js                 ← Phần 16
```

### Bước 3: Kích hoạt theme

1. Vào **WordPress Admin** → **Giao diện** → **Theme**
2. Tìm theme **"Developer Blog"** → Click **Kích hoạt**
3. Nếu không thấy theme, kiểm tra:
   - File `style.css` có đúng vị trí: `wp-content/themes/developer-blog/style.css`
   - Comment header trong `style.css` đúng format (có `Theme Name:`)

### Bước 4: Cấu hình cơ bản

```
1. Tạo Menu:
   → Giao diện → Menu → Tạo menu mới → Thêm trang/link → Gán vào "Menu Chính"

2. Thêm Widgets:
   → Giao diện → Widgets
   → Kéo "Tìm kiếm" vào Sidebar
   → Kéo "Bài viết gần đây" vào Sidebar
   → Kéo "Chuyên mục" vào Sidebar
   → Kéo "Văn bản" vào Footer Cột 1, 2, 3

3. Upload Logo:
   → Giao diện → Tùy biến → Nhận diện Site → Chọn logo

4. Cài đặt trang chủ:
   → Cài đặt → Đọc → "Trang bài viết mới nhất" (mặc định)
```

### Bước 5: Kiểm tra

| Trang | URL | Template sử dụng |
|-------|-----|-------------------|
| Trang chủ (Blog) | `yoursite.com/` | `index.php` |
| Bài viết đơn | `yoursite.com/hello-world/` | `single.php` |
| Trang tĩnh | `yoursite.com/gioi-thieu/` | `page.php` |
| Danh mục | `yoursite.com/category/laravel/` | `archive.php` |
| Tìm kiếm | `yoursite.com/?s=wordpress` | `search.php` |
| 404 | `yoursite.com/trang-khong-co/` | `404.php` |

---

## 18. Tổng Kết

### Bảng tất cả file đã tạo

| # | File | Dòng code | Chức năng chính |
|---|------|-----------|-----------------|
| 1 | `style.css` | ~500 | CSS Variables + Layout + Components + Responsive |
| 2 | `functions.php` | ~200 | Theme setup, enqueue, widgets, helpers, customizer |
| 3 | `header.php` | ~80 | `<head>`, logo, navigation, mobile toggle |
| 4 | `footer.php` | ~60 | Footer widgets 3 cột, copyright |
| 5 | `index.php` | ~40 | Blog listing với The Loop + pagination |
| 6 | `single.php` | ~90 | Post chi tiết + navigation + comments |
| 7 | `page.php` | ~45 | Trang tĩnh + featured image |
| 8 | `archive.php` | ~40 | Danh sách theo category/tag/author |
| 9 | `search.php` | ~40 | Kết quả tìm kiếm |
| 10 | `searchform.php` | ~20 | Form tìm kiếm tùy chỉnh |
| 11 | `404.php` | ~25 | Trang lỗi 404 |
| 12 | `sidebar.php` | ~15 | Widget area |
| 13 | `comments.php` | ~60 | Danh sách comments + form |
| 14 | `template-parts/content.php` | ~40 | Card bài viết (tái sử dụng) |
| 15 | `template-parts/content-none.php` | ~20 | Thông báo không có kết quả |
| 16 | `assets/js/navigation.js` | ~35 | Mobile menu toggle |

### Sơ đồ: File nào gọi file nào?

```
index.php / single.php / page.php / archive.php / search.php / 404.php
    │
    ├── get_header()           → header.php
    │                              ├── wp_head()      → load style.css
    │                              ├── the_custom_logo()
    │                              └── wp_nav_menu()
    │
    ├── get_template_part()    → template-parts/content.php
    │   (hoặc content-none.php)    ├── the_post_thumbnail()
    │                              ├── the_title()
    │                              └── the_excerpt()
    │
    ├── get_sidebar()          → sidebar.php
    │                              └── dynamic_sidebar('sidebar-1')
    │
    ├── comments_template()    → comments.php
    │                              ├── wp_list_comments()
    │                              └── comment_form()
    │
    └── get_footer()           → footer.php
                                   ├── dynamic_sidebar('footer-1,2,3')
                                   └── wp_footer()    → load navigation.js
```

### Tiếp theo nên học gì?

| Chủ đề | File trong wp-study | Mô tả |
|--------|---------------------|-------|
| Child Theme | [07-theme-nang-cao.md](./07-theme-nang-cao.md) | Kế thừa và tùy chỉnh theme mà không sửa theme gốc |
| Block Theme / FSE | [06-block-theme-va-fse.md](./06-block-theme-va-fse.md) | Theme hiện đại dùng HTML blocks + theme.json |
| Customizer API | [05-customizer-api.md](./05-customizer-api.md) | Thêm options cho user tùy chỉnh theme |
| Template Hierarchy | [02-template-hierarchy.md](./02-template-hierarchy.md) | Hiểu cách WP chọn template file |
| Sơ đồ minh họa | [09-so-do-va-minh-hoa.md](./09-so-do-va-minh-hoa.md) | ASCII art minh họa cấu trúc theme |

### So sánh tổng hợp: WordPress Theme vs Laravel Views

| Khái niệm | WordPress | Laravel |
|-----------|-----------|---------|
| Layout chính | `header.php` + `footer.php` | `layouts/app.blade.php` |
| Trang danh sách | `index.php` | `posts/index.blade.php` |
| Trang chi tiết | `single.php` | `posts/show.blade.php` |
| Trang tĩnh | `page.php` | `pages/show.blade.php` |
| Components | `template-parts/*.php` | `components/*.blade.php` |
| Sidebar | `sidebar.php` + widgets | `@include('partials.sidebar')` |
| CSS/JS | `wp_enqueue_style/script()` | `@vite(['resources/css/app.css'])` |
| Routing | Template Hierarchy (tự động) | `routes/web.php` (thủ công) |
| Data | Global `$post`, `WP_Query` | Controller → View `$data` |
| Form | `comment_form()` (tự động) | Blade form + Controller |

---

[← Quay lại: Sơ đồ & Minh họa](./09-so-do-va-minh-hoa.md) | [↑ Mục lục Theme](./index.md) | [→ Tiếp: Phát triển Plugin](../05-plugins/)
