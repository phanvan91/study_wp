# Bài 9: Sơ Đồ & Minh Họa - Hiểu Theme WordPress Qua Hình Ảnh

> **Tổng hợp sơ đồ trực quan** giúp bạn hiểu cấu trúc theme, template hierarchy,
> layout trang web, và cách WordPress xử lý giao diện.
> Tất cả sơ đồ vẽ bằng ASCII art, dễ xem trên mọi thiết bị.

---

## Mục Lục

1. [Giải phẫu một trang WordPress](#1-giải-phẫu-một-trang-wordpress)
2. [Template Hierarchy - Sơ đồ đầy đủ](#2-template-hierarchy---sơ-đồ-đầy-đủ)
3. [Cấu trúc Classic Theme vs Block Theme](#3-cấu-trúc-classic-theme-vs-block-theme)
4. [Luồng xử lý: Từ URL đến HTML](#4-luồng-xử-lý-từ-url-đến-html)
5. [Layout phổ biến và cách xây dựng](#5-layout-phổ-biến-và-cách-xây-dựng)
6. [functions.php - Sơ đồ chức năng](#6-functionsphp---sơ-đồ-chức-năng)
7. [The Loop - Cách WordPress hiển thị bài viết](#7-the-loop---cách-wordpress-hiển-thị-bài-viết)
8. [Hooks trong Theme - Vị trí và thời điểm](#8-hooks-trong-theme---vị-trí-và-thời-điểm)
9. [Block Theme và theme.json](#9-block-theme-và-themejson)
10. [So sánh: Theme phổ biến trên thị trường](#10-so-sánh-theme-phổ-biến-trên-thị-trường)

---

## 1. Giải Phẫu Một Trang WordPress

### 1.1. Sơ đồ tổng quan trang web

```
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER WINDOW                           │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    header.php                              │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │  Logo          Navigation Menu          Search      │  │  │
│  │  │  (the_custom   (wp_nav_menu)            (get_search │  │  │
│  │  │   _logo())                               _form())   │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Breadcrumb (tùy chọn)                                    │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────┐ ┌──────────────────────┐  │
│  │         MAIN CONTENT             │ │     sidebar.php      │  │
│  │    (index/single/page/           │ │                      │  │
│  │     archive/search.php)          │ │  ┌────────────────┐  │  │
│  │                                   │ │  │   Widget 1     │  │  │
│  │  ┌─────────────────────────────┐ │ │  │   (Tìm kiếm)   │  │  │
│  │  │     Post/Page Content       │ │ │  └────────────────┘  │  │
│  │  │                             │ │ │                      │  │
│  │  │  Title (the_title)          │ │ │  ┌────────────────┐  │  │
│  │  │  Meta (date, author, cat)   │ │ │  │   Widget 2     │  │  │
│  │  │  Featured Image             │ │ │  │   (Bài mới)    │  │  │
│  │  │  (the_post_thumbnail)       │ │ │  └────────────────┘  │  │
│  │  │  Content (the_content)      │ │ │                      │  │
│  │  │  Tags                       │ │ │  ┌────────────────┐  │  │
│  │  │                             │ │ │  │   Widget 3     │  │  │
│  │  └─────────────────────────────┘ │ │  │   (Danh mục)   │  │  │
│  │                                   │ │  └────────────────┘  │  │
│  │  ┌─────────────────────────────┐ │ │                      │  │
│  │  │     Pagination / Nav        │ │ │  ┌────────────────┐  │  │
│  │  │  « Trước  1  2  3  Sau »   │ │ │  │   Widget 4     │  │  │
│  │  └─────────────────────────────┘ │ │  │   (Quảng cáo)  │  │  │
│  │                                   │ │  └────────────────┘  │  │
│  │  ┌─────────────────────────────┐ │ │                      │  │
│  │  │     Comments                │ │ │                      │  │
│  │  │  (comments_template())      │ │ │                      │  │
│  │  └─────────────────────────────┘ │ │                      │  │
│  └──────────────────────────────────┘ └──────────────────────┘  │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                     footer.php                             │  │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐ │  │
│  │  │ Footer Col 1│ │ Footer Col 2│ │    Footer Col 3     │ │  │
│  │  │ (Widget)    │ │ (Widget)    │ │    (Widget)         │ │  │
│  │  └─────────────┘ └─────────────┘ └─────────────────────┘ │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │  © 2024 Site Name. Powered by WordPress.            │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2. Tương ứng file PHP ↔ Vùng hiển thị

```
┌─────────────────────────────────────────────────────────┐
│ get_header()  →  header.php                             │
│   ├── wp_head()        → <head> tags, CSS, meta        │
│   ├── body_class()     → CSS classes cho <body>         │
│   ├── the_custom_logo()→ Logo site                      │
│   ├── wp_nav_menu()    → Navigation menu                │
│   └── get_search_form()→ Form tìm kiếm                 │
├─────────────────────────────────────────────────────────┤
│ THE LOOP  →  template file (index/single/page/...)      │
│   ├── have_posts()     → Còn bài viết không?            │
│   ├── the_post()       → Setup bài viết hiện tại        │
│   ├── the_title()      → Tiêu đề                        │
│   ├── the_content()    → Nội dung                        │
│   ├── the_excerpt()    → Đoạn trích                      │
│   ├── the_post_thumbnail() → Ảnh đại diện               │
│   ├── the_category()   → Danh mục                        │
│   ├── the_tags()       → Thẻ tags                        │
│   └── the_author()     → Tên tác giả                     │
├─────────────────────────────────────────────────────────┤
│ get_sidebar()  →  sidebar.php                           │
│   └── dynamic_sidebar('sidebar-1') → Hiển thị widgets   │
├─────────────────────────────────────────────────────────┤
│ comments_template()  →  comments.php                    │
│   └── wp_list_comments() → Danh sách bình luận          │
├─────────────────────────────────────────────────────────┤
│ get_footer()  →  footer.php                             │
│   ├── dynamic_sidebar('footer-1') → Footer widgets      │
│   └── wp_footer()      → JS scripts, tracking code      │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Template Hierarchy - Sơ Đồ Đầy Đủ

### 2.1. Sơ đồ quyết định: WordPress chọn template nào?

```
URL Request đến WordPress
         │
         ▼
    ┌─────────┐
    │ Loại    │
    │ trang?  │
    └────┬────┘
         │
    ┌────┴─────────────────────────────────────────────────────────┐
    │    │          │         │        │        │       │          │
    ▼    ▼          ▼         ▼        ▼        ▼       ▼          ▼
 Trang  Bài     Archive   Search    404    Trang    Home      Attachment
 chủ    đơn                                 tĩnh    (Blog)
    │    │          │         │        │        │       │          │
    ▼    ▼          ▼         ▼        ▼        ▼       ▼          ▼
```

### 2.2. Chi tiết từng loại trang

**Trang chủ (Front Page):**
```
front-page.php          ← Ưu tiên cao nhất
    │ (không có?)
    ▼
home.php                ← Nếu Settings > Reading = "Your latest posts"
    │ (không có?)       ← Hoặc page được chọn làm "Posts page"
    ▼
index.php               ← Fallback cuối cùng
```

**Bài viết đơn (Single Post):**
```
single-{post-type}-{slug}.php    ← VD: single-post-hello-world.php
    │ (không có?)
    ▼
single-{post-type}.php           ← VD: single-post.php, single-portfolio.php
    │ (không có?)
    ▼
single.php                       ← Cho tất cả post types
    │ (không có?)
    ▼
singular.php                     ← Cho cả post và page
    │ (không có?)
    ▼
index.php                        ← Fallback
```

**Trang tĩnh (Page):**
```
{custom-template}.php            ← VD: page-templates/full-width.php
    │ (không có?)                   (Template Name: Full Width)
    ▼
page-{slug}.php                  ← VD: page-about.php, page-contact.php
    │ (không có?)
    ▼
page-{id}.php                   ← VD: page-42.php
    │ (không có?)
    ▼
page.php                        ← Cho tất cả pages
    │ (không có?)
    ▼
singular.php
    │ (không có?)
    ▼
index.php
```

**Archive (Danh sách):**
```
Category:  category-{slug}.php → category-{id}.php → category.php ─┐
Tag:       tag-{slug}.php → tag-{id}.php → tag.php ────────────────┤
Author:    author-{nicename}.php → author-{id}.php → author.php ──┤
Date:      date.php ───────────────────────────────────────────────┤
CPT:       archive-{post-type}.php ────────────────────────────────┤
Taxonomy:  taxonomy-{tax}-{term}.php → taxonomy-{tax}.php ────────┤
                                                                    │
                                                                    ▼
                                                              archive.php
                                                                    │
                                                                    ▼
                                                              index.php
```

**Tìm kiếm và 404:**
```
Search:  search.php → index.php
404:     404.php → index.php
```

### 2.3. Sơ đồ tổng hợp (cheat sheet)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                    WORDPRESS TEMPLATE HIERARCHY                          │
│                                                                          │
│  REQUEST TYPE          TEMPLATE FILES (ưu tiên từ trái → phải)          │
│  ─────────────         ──────────────────────────────────────            │
│                                                                          │
│  Front Page     front-page.php ──────────────────────────── → index.php │
│                                                                          │
│  Blog Home      home.php ────────────────────────────────── → index.php │
│                                                                          │
│  Single Post    single-{type}-{slug} → single-{type} → single          │
│                 → singular ──────────────────────────────── → index.php │
│                                                                          │
│  Page           {template} → page-{slug} → page-{id} → page            │
│                 → singular ──────────────────────────────── → index.php │
│                                                                          │
│  Category       category-{slug} → category-{id} → category             │
│                 → archive ───────────────────────────────── → index.php │
│                                                                          │
│  Tag            tag-{slug} → tag-{id} → tag → archive ──── → index.php │
│                                                                          │
│  Author         author-{name} → author-{id} → author                   │
│                 → archive ───────────────────────────────── → index.php │
│                                                                          │
│  Date           date → archive ──────────────────────────── → index.php │
│                                                                          │
│  CPT Archive    archive-{type} → archive ────────────────── → index.php │
│                                                                          │
│  Taxonomy       taxonomy-{tax}-{term} → taxonomy-{tax}                  │
│                 → taxonomy → archive ────────────────────── → index.php │
│                                                                          │
│  Search         search ──────────────────────────────────── → index.php │
│                                                                          │
│  404            404 ─────────────────────────────────────── → index.php │
│                                                                          │
│  Attachment     {mime}-{sub}.php → {sub}.php → {mime}.php               │
│                 → attachment.php → single-attachment-{slug}              │
│                 → single-attachment → single → singular ─── → index.php │
│                                                                          │
│  ★ index.php là FALLBACK CUỐI CÙNG cho mọi loại trang                  │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Cấu Trúc Classic Theme vs Block Theme

### 3.1. Classic Theme (truyền thống)

```
mytheme/                          ← Thư mục theme
│
├── style.css                     ★ BẮT BUỘC - Khai báo theme
├── index.php                     ★ BẮT BUỘC - Template fallback
├── functions.php                 ★ Quan trọng - Đăng ký chức năng
├── screenshot.png                  Ảnh preview (1200x900px)
│
│  ── TEMPLATE FILES ──
├── header.php                    <head> + đầu <body> + nav
├── footer.php                    Footer + wp_footer()
├── sidebar.php                   Widget area
├── single.php                    Bài viết đơn
├── page.php                      Trang tĩnh
├── archive.php                   Trang danh sách
├── search.php                    Kết quả tìm kiếm
├── 404.php                       Trang lỗi
├── front-page.php                Trang chủ
├── home.php                      Trang blog
├── comments.php                  Bình luận
├── searchform.php                Form tìm kiếm
│
│  ── TEMPLATE PARTS ──
├── template-parts/
│   ├── content.php               Loop content
│   ├── content-search.php        Search result item
│   ├── content-none.php          No results
│   └── content-page.php          Page content
│
│  ── PAGE TEMPLATES ──
├── page-templates/
│   ├── full-width.php            Template: Full Width
│   └── contact.php               Template: Liên hệ
│
│  ── INCLUDES ──
├── inc/
│   ├── customizer.php            Customizer API
│   ├── template-tags.php         Helper functions
│   └── walker-nav-menu.php       Custom Walker
│
│  ── ASSETS ──
├── assets/
│   ├── css/
│   │   ├── main.css
│   │   └── editor-style.css      Gutenberg editor styles
│   ├── js/
│   │   ├── navigation.js
│   │   └── main.js
│   ├── images/
│   └── fonts/
│
└── languages/
    └── mytheme.pot               Translation template
```

### 3.2. Block Theme (Full Site Editing - WP 6.0+)

```
my-block-theme/                   ← Thư mục theme
│
├── style.css                     ★ BẮT BUỘC - Khai báo theme
├── theme.json                    ★ QUAN TRỌNG - Cấu hình toàn bộ theme
├── functions.php                   Tùy chọn (thường ít code)
├── screenshot.png
│
│  ── TEMPLATES (HTML) ──
├── templates/                    ★ BẮT BUỘC có ít nhất index.html
│   ├── index.html                  Template mặc định
│   ├── single.html                 Bài viết
│   ├── page.html                   Trang tĩnh
│   ├── archive.html                Danh sách
│   ├── search.html                 Tìm kiếm
│   ├── 404.html                    Lỗi 404
│   ├── front-page.html             Trang chủ
│   └── home.html                   Blog page
│
│  ── PARTS (HTML) ──
├── parts/                          Template parts (tái sử dụng)
│   ├── header.html                 Header
│   ├── footer.html                 Footer
│   ├── sidebar.html                Sidebar
│   └── comments.html               Bình luận
│
│  ── PATTERNS (PHP) ──
├── patterns/                       Block Patterns
│   ├── hero.php                    Hero section
│   ├── cta.php                     Call to action
│   ├── testimonials.php            Đánh giá khách hàng
│   └── pricing.php                 Bảng giá
│
│  ── STYLES ──
├── styles/                         Style variations
│   ├── dark.json                   Dark mode
│   └── ocean.json                  Ocean color scheme
│
│  ── ASSETS ──
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
│       ├── inter-regular.woff2
│       └── inter-bold.woff2
│
└── languages/
```

### 3.3. So sánh trực quan

```
┌──────────────────────────────┬──────────────────────────────┐
│      CLASSIC THEME           │       BLOCK THEME            │
├──────────────────────────────┼──────────────────────────────┤
│                              │                              │
│  Template = PHP files        │  Template = HTML files       │
│  (header.php, single.php)    │  (templates/single.html)     │
│                              │                              │
│  Giao diện = PHP + HTML      │  Giao diện = Block markup    │
│  <?php the_title(); ?>       │  <!-- wp:post-title /-->     │
│                              │                              │
│  Tùy chỉnh = Customizer     │  Tùy chỉnh = Site Editor     │
│  (live preview panel)        │  (kéo thả trực tiếp)         │
│                              │                              │
│  Style = CSS files           │  Style = theme.json + CSS    │
│  (style.css, main.css)       │  (JSON cấu hình + blocks)   │
│                              │                              │
│  functions.php = nhiều code  │  functions.php = ít code     │
│  (enqueue, register, setup)  │  (theme.json xử lý phần lớn)│
│                              │                              │
│  Widget Areas = register     │  Widget = Block widget       │
│  (register_sidebar)          │  (wp:widget-area)            │
│                              │                              │
│  Menus = wp_nav_menu()       │  Menus = <!-- wp:navigation  │
│                              │  {"ref":123} /-->            │
│                              │                              │
│  Child Theme = override PHP  │  Child Theme = override HTML │
│                              │  + theme.json                │
│                              │                              │
│  ★ Phù hợp: Dev muốn kiểm  │  ★ Phù hợp: User muốn kéo  │
│    soát hoàn toàn bằng code  │    thả, ít code              │
│                              │                              │
│  Ví dụ: Astra, GeneratePress│  Ví dụ: Twenty Twenty-Four,  │
│  OceanWP, Flavor            │  Flavor Block, Ollie         │
└──────────────────────────────┴──────────────────────────────┘
```

---

## 4. Luồng Xử Lý: Từ URL Đến HTML

### 4.1. Luồng hoàn chỉnh

```
┌─────────────────┐
│  User truy cập  │
│  yoursite.com/  │
│  hello-world    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Web Server     │   Apache/Nginx nhận request
│  (Apache/Nginx) │   → Chuyển đến index.php
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  index.php      │   WordPress entry point
│  (root)         │   → require wp-blog-header.php
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  wp-config.php  │   Load cấu hình database, debug, constants
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  wp-settings.php│   Load WordPress core:
│                 │   - Constants
│                 │   - Load $wpdb
│                 │   - Load plugins (active)
│                 │   ─── do_action('plugins_loaded') ───
│                 │   - Load theme (functions.php)
│                 │   ─── do_action('after_setup_theme') ───
│                 │   - Init
│                 │   ─── do_action('init') ───
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  WP::main()     │   Parse URL → Xác định loại request
│  (class-wp.php) │   → Chạy WP_Query
│                 │   ─── do_action('wp') ───
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Template       │   Dựa trên loại request:
│  Loader         │   - is_single() → single.php
│                 │   - is_page()   → page.php
│  (template-     │   - is_archive()→ archive.php
│   loader.php)   │   - is_404()   → 404.php
│                 │   ─── do_action('template_redirect') ───
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────┐
│              TEMPLATE FILE                   │
│                                              │
│  get_header()     ← header.php               │
│  ┌────────────────────────────────────────┐  │
│  │ wp_head()  → CSS, JS, meta            │  │
│  │ wp_nav_menu() → Navigation             │  │
│  └────────────────────────────────────────┘  │
│                                              │
│  THE LOOP                                    │
│  ┌────────────────────────────────────────┐  │
│  │ while (have_posts()) : the_post();     │  │
│  │   the_title();                         │  │
│  │   the_content();                       │  │
│  │ endwhile;                              │  │
│  └────────────────────────────────────────┘  │
│                                              │
│  get_sidebar()    ← sidebar.php              │
│  get_footer()     ← footer.php               │
│  ┌────────────────────────────────────────┐  │
│  │ wp_footer() → JS scripts              │  │
│  └────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────┘
                       │
                       ▼
              ┌─────────────────┐
              │  HTML Response  │  → Gửi về browser
              │  (hoàn chỉnh)  │
              └─────────────────┘
```

---

## 5. Layout Phổ Biến Và Cách Xây Dựng

### 5.1. Layout 1: Blog chuẩn (Content + Sidebar)

```
┌────────────────────────────────────────────────────┐
│                    HEADER                           │
│  [Logo]                    [Menu1] [Menu2] [Menu3] │
├──────────────────────────────────┬─────────────────┤
│                                  │                 │
│          MAIN CONTENT            │    SIDEBAR      │
│          (70% width)             │    (30% width)  │
│                                  │                 │
│  ┌────────────────────────────┐  │  ┌───────────┐  │
│  │ [Ảnh]                     │  │  │  Search   │  │
│  │ Tiêu đề bài 1             │  │  └───────────┘  │
│  │ 20/01/2024 · Tác giả      │  │                 │
│  │ Đoạn trích nội dung...    │  │  ┌───────────┐  │
│  │              [Đọc tiếp →] │  │  │ Bài mới   │  │
│  └────────────────────────────┘  │  │  nhất     │  │
│                                  │  └───────────┘  │
│  ┌────────────────────────────┐  │                 │
│  │ [Ảnh]                     │  │  ┌───────────┐  │
│  │ Tiêu đề bài 2             │  │  │ Danh mục  │  │
│  │ 19/01/2024 · Tác giả      │  │  └───────────┘  │
│  │ Đoạn trích nội dung...    │  │                 │
│  │              [Đọc tiếp →] │  │  ┌───────────┐  │
│  └────────────────────────────┘  │  │  Tags     │  │
│                                  │  └───────────┘  │
│  « Trước  [1] [2] [3]  Sau »    │                 │
│                                  │                 │
├──────────────────────────────────┴─────────────────┤
│                     FOOTER                          │
│  [Col 1: About]  [Col 2: Links]  [Col 3: Contact] │
│              © 2024 Site Name                       │
└────────────────────────────────────────────────────┘

CSS cho layout này:
.content-area { display: flex; gap: 40px; }
.main-content { flex: 1; }
.sidebar { width: 300px; flex-shrink: 0; }
```

### 5.2. Layout 2: Magazine / News (Grid)

```
┌──────────────────────────────────────────────────────┐
│  [Logo]      [Menu]      [Search] [Social Icons]     │
├──────────────────────────────────────────────────────┤
│  BREAKING: Tin nóng chạy ngang đây...               │
├──────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────────────────────┐ ┌────────────────────┐ │
│  │                          │ │  Bài phụ 1         │ │
│  │     BÀI NỔI BẬT         │ │  [Ảnh nhỏ] Title   │ │
│  │     (Featured Post)      │ ├────────────────────┤ │
│  │     [Ảnh lớn]            │ │  Bài phụ 2         │ │
│  │     Tiêu đề bài          │ │  [Ảnh nhỏ] Title   │ │
│  │     Tóm tắt...           │ ├────────────────────┤ │
│  │                          │ │  Bài phụ 3         │ │
│  └──────────────────────────┘ │  [Ảnh nhỏ] Title   │ │
│                                └────────────────────┘ │
│                                                      │
│  ── Tin mới nhất ──────────────────────────────────  │
│                                                      │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐       │
│  │ [Ảnh]  │ │ [Ảnh]  │ │ [Ảnh]  │ │ [Ảnh]  │       │
│  │ Title  │ │ Title  │ │ Title  │ │ Title  │       │
│  │ Date   │ │ Date   │ │ Date   │ │ Date   │       │
│  └────────┘ └────────┘ └────────┘ └────────┘       │
│                                                      │
│  ── Theo chuyên mục ─────────────────────────────── │
│  ┌──────────────────────┐ ┌──────────────────────┐  │
│  │ CÔNG NGHỆ            │ │ KINH DOANH           │  │
│  │ ├── Bài 1            │ │ ├── Bài 1            │  │
│  │ ├── Bài 2            │ │ ├── Bài 2            │  │
│  │ └── Bài 3            │ │ └── Bài 3            │  │
│  └──────────────────────┘ └──────────────────────┘  │
└──────────────────────────────────────────────────────┘

CSS cho layout này:
.featured-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
.posts-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.category-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
```

### 5.3. Layout 3: Portfolio / Agency (Full Width)

```
┌──────────────────────────────────────────────────────┐
│                  TRANSPARENT HEADER                   │
│  [Logo]                     [Work] [About] [Contact] │
├──────────────────────────────────────────────────────┤
│                                                      │
│  ████████████████████████████████████████████████████ │
│  █                                                 █ │
│  █            HERO SECTION (Full Width)             █ │
│  █                                                 █ │
│  █     "Chúng tôi tạo ra những trải nghiệm        █ │
│  █      số tuyệt vời"                              █ │
│  █                                                 █ │
│  █     [  Xem Portfolio  ]  [  Liên hệ  ]         █ │
│  █                                                 █ │
│  ████████████████████████████████████████████████████ │
│                                                      │
│  ── Dự án nổi bật ────────────────────────────────  │
│                                                      │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│  │              │ │              │ │              │ │
│  │   [Ảnh dự   │ │   [Ảnh dự   │ │   [Ảnh dự   │ │
│  │    án 1]    │ │    án 2]    │ │    án 3]    │ │
│  │              │ │              │ │              │ │
│  │  Hover:      │ │              │ │              │ │
│  │  Overlay +   │ │              │ │              │ │
│  │  View Detail │ │              │ │              │ │
│  └──────────────┘ └──────────────┘ └──────────────┘ │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│  │  [Dự án 4]  │ │  [Dự án 5]  │ │  [Dự án 6]  │ │
│  └──────────────┘ └──────────────┘ └──────────────┘ │
│                                                      │
│  ── Về chúng tôi ───────────────────────────────── │
│  ┌────────────────────┐  ┌────────────────────────┐ │
│  │    [Ảnh team]      │  │  Công ty XYZ           │ │
│  │                    │  │  10+ năm kinh nghiệm   │ │
│  │                    │  │  200+ dự án hoàn thành  │ │
│  │                    │  │  [Tìm hiểu thêm →]     │ │
│  └────────────────────┘  └────────────────────────┘ │
│                                                      │
│  ── Khách hàng nói gì ─────────────────────────── │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐      │
│  │  "Tuyệt    │ │  "Rất      │ │  "Team rất │      │
│  │   vời!"   │ │  chuyên    │ │  giỏi"     │      │
│  │  — Anh A   │ │  nghiệp"  │ │  — Chị C   │      │
│  │            │ │  — Anh B   │ │            │      │
│  └────────────┘ └────────────┘ └────────────┘      │
│                                                      │
│  ████████████████████████████████████████████████████ │
│  █          CTA: Bắt đầu dự án của bạn            █ │
│  █          [  Liên hệ ngay  ]                     █ │
│  ████████████████████████████████████████████████████ │
│                                                      │
├──────────────────────────────────────────────────────┤
│                     FOOTER                            │
└──────────────────────────────────────────────────────┘

Đặc điểm: Không có sidebar, full-width sections, nhiều hình ảnh
Template: front-page.php hoặc page template "Homepage"
```

---

## 6. functions.php - Sơ Đồ Chức Năng

```
functions.php
│
├── 1. THEME SETUP (after_setup_theme)
│   ├── add_theme_support('title-tag')           → Tự động <title>
│   ├── add_theme_support('post-thumbnails')     → Featured images
│   ├── add_theme_support('custom-logo')         → Logo upload
│   ├── add_theme_support('html5', [...])        → HTML5 markup
│   ├── add_theme_support('editor-styles')       → Gutenberg styles
│   ├── add_theme_support('align-wide')          → Wide/Full alignment
│   ├── register_nav_menus([...])                → Đăng ký menus
│   ├── load_theme_textdomain()                  → Đa ngôn ngữ
│   └── set_post_thumbnail_size(...)             → Kích thước ảnh
│
├── 2. ENQUEUE ASSETS (wp_enqueue_scripts)
│   ├── wp_enqueue_style('theme-style')          → CSS chính
│   ├── wp_enqueue_style('google-fonts')         → Google Fonts
│   ├── wp_enqueue_script('theme-main')          → JS chính
│   ├── wp_enqueue_script('navigation')          → Mobile menu
│   └── wp_localize_script('theme-main', data)   → PHP → JS data
│
├── 3. WIDGET AREAS (widgets_init)
│   ├── register_sidebar('sidebar-1')            → Sidebar chính
│   ├── register_sidebar('footer-1')             → Footer cột 1
│   ├── register_sidebar('footer-2')             → Footer cột 2
│   └── register_sidebar('footer-3')             → Footer cột 3
│
├── 4. CUSTOMIZER (customize_register)
│   ├── add_section('social_links')              → Mạng xã hội
│   ├── add_setting('facebook_url')              → Facebook URL
│   ├── add_control('facebook_url')              → Input field
│   └── add_section('footer_options')            → Tùy chọn footer
│
├── 5. CUSTOM POST TYPES (init)
│   ├── register_post_type('portfolio')          → Portfolio CPT
│   └── register_taxonomy('portfolio_cat')       → Portfolio taxonomy
│
├── 6. TEMPLATE HELPERS
│   ├── mytheme_posted_on()                      → Date + author
│   ├── mytheme_entry_footer()                   → Categories + tags
│   ├── mytheme_post_thumbnail()                 → Featured image
│   ├── mytheme_pagination()                     → Page numbers
│   └── mytheme_breadcrumb()                     → Breadcrumb nav
│
├── 7. FILTERS
│   ├── excerpt_length → 25                      → Rút ngắn excerpt
│   ├── excerpt_more → "Đọc tiếp →"             → Link đọc tiếp
│   ├── body_class → thêm custom classes         → CSS classes
│   └── get_the_archive_title → bỏ prefix        → Clean title
│
└── 8. INCLUDES
    ├── inc/customizer.php                       → Customizer chi tiết
    ├── inc/template-tags.php                    → Helper functions
    └── inc/walker-nav-menu.php                  → Custom Walker
```

---

## 7. The Loop - Cách WordPress Hiển Thị Bài Viết

### 7.1. Sơ đồ The Loop cơ bản

```
┌─────────────────────────────────────────┐
│         have_posts() ?                  │
│         (Còn bài viết không?)           │
└──────────┬────────────────┬─────────────┘
           │ CÓ             │ KHÔNG
           ▼                ▼
    ┌──────────────┐  ┌──────────────────┐
    │  the_post()  │  │ "Không có bài    │
    │  (Setup      │  │  viết nào."      │
    │   global     │  │                  │
    │   $post)     │  │  get_search_form │
    └──────┬───────┘  └──────────────────┘
           │
           ▼
    ┌──────────────────────────────┐
    │  HIỂN THỊ BÀI VIẾT          │
    │                              │
    │  the_title()     → Tiêu đề  │
    │  the_content()   → Nội dung  │
    │  the_excerpt()   → Trích dẫn │
    │  the_permalink() → URL       │
    │  the_date()      → Ngày      │
    │  the_author()    → Tác giả   │
    │  the_category()  → Danh mục  │
    │  the_tags()      → Tags      │
    │  the_post_thumbnail()        │
    │                  → Ảnh đại   │
    │                    diện      │
    └──────────┬───────────────────┘
               │
               │ Quay lại kiểm tra
               │ have_posts()
               ▼
        ┌──────────────┐
        │ Hết bài →    │
        │ Thoát loop   │
        │              │
        │ Hiển thị     │
        │ pagination   │
        └──────────────┘
```

### 7.2. Code tương ứng

```php
<?php
// === THE LOOP CƠ BẢN ===

if ( have_posts() ) :              // Kiểm tra: có bài viết không?

    while ( have_posts() ) :       // Lặp qua từng bài
        the_post();                // Setup global $post

        // Hiển thị bài viết
        the_title( '<h2>', '</h2>' );
        the_post_thumbnail( 'medium' );
        the_excerpt();

    endwhile;                      // Kết thúc lặp

    // Pagination
    the_posts_pagination();

else :                             // Không có bài viết

    echo '<p>Không có bài viết nào.</p>';
    get_search_form();

endif;
```

### 7.3. Multiple Loops (nhiều loop trên 1 trang)

```
┌─────────────────────────────────────────────────────┐
│                    FRONT PAGE                        │
│                                                      │
│  ── Loop 1: Bài nổi bật (Sticky Posts) ────────    │
│  $sticky_query = new WP_Query([                     │
│      'post__in' => get_option('sticky_posts'),       │
│      'posts_per_page' => 3                           │
│  ]);                                                 │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│  │ Sticky 1 │ │ Sticky 2 │ │ Sticky 3 │            │
│  └──────────┘ └──────────┘ └──────────┘            │
│  wp_reset_postdata();  ← QUAN TRỌNG!               │
│                                                      │
│  ── Loop 2: Bài mới nhất ──────────────────────── │
│  $recent = new WP_Query([                           │
│      'posts_per_page' => 6,                          │
│      'post__not_in' => get_option('sticky_posts')    │
│  ]);                                                 │
│  ┌────────┐ ┌────────┐ ┌────────┐                  │
│  │ Post 1 │ │ Post 2 │ │ Post 3 │                  │
│  └────────┘ └────────┘ └────────┘                  │
│  ┌────────┐ ┌────────┐ ┌────────┐                  │
│  │ Post 4 │ │ Post 5 │ │ Post 6 │                  │
│  └────────┘ └────────┘ └────────┘                  │
│  wp_reset_postdata();  ← QUAN TRỌNG!               │
│                                                      │
│  ── Loop 3: Portfolio (CPT) ────────────────────── │
│  $portfolio = new WP_Query([                        │
│      'post_type' => 'portfolio',                     │
│      'posts_per_page' => 4                           │
│  ]);                                                 │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐│
│  │ Project1 │ │ Project2 │ │ Project3 │ │Project4││
│  └──────────┘ └──────────┘ └──────────┘ └────────┘│
│  wp_reset_postdata();  ← QUAN TRỌNG!               │
└─────────────────────────────────────────────────────┘

★ LUÔN gọi wp_reset_postdata() sau mỗi custom WP_Query
  để khôi phục global $post về trạng thái ban đầu!
```

---

## 8. Hooks Trong Theme - Vị Trí Và Thời Điểm

### 8.1. Hooks fire theo thứ tự trong 1 page load

```
┌─────────────────────────────────────────────────────────┐
│  THỜI ĐIỂM                  HOOK                        │
│  ─────────                  ────                        │
│                                                          │
│  ① PHP bắt đầu             muplugins_loaded             │
│                             plugins_loaded               │
│                                                          │
│  ② Theme load              after_setup_theme ★           │
│                             (register menus, supports)   │
│                                                          │
│  ③ WordPress init          init ★                        │
│                             (register CPT, taxonomy)     │
│                                                          │
│  ④ Widgets init            widgets_init ★                │
│                             (register sidebars)          │
│                                                          │
│  ⑤ Parse request           parse_request                 │
│                             pre_get_posts ★               │
│                             (modify main query)          │
│                                                          │
│  ⑥ Template chọn           template_redirect ★           │
│                             template_include             │
│                                                          │
│  ⑦ <head> render           wp_enqueue_scripts ★          │
│                             (enqueue CSS/JS)             │
│                             wp_head ★                     │
│                             (output meta, CSS)           │
│                                                          │
│  ⑧ <body> bắt đầu         wp_body_open                  │
│                                                          │
│  ⑨ Content render          the_content (filter) ★        │
│                             the_title (filter)           │
│                             the_excerpt (filter)         │
│                                                          │
│  ⑩ Comments                comment_form_before           │
│                             comment_form_after           │
│                                                          │
│  ⑪ Footer                  wp_footer ★                   │
│                             (output JS)                  │
│                                                          │
│  ⑫ Shutdown               shutdown                      │
│                                                          │
│  ★ = Hooks quan trọng nhất cho theme developer          │
└─────────────────────────────────────────────────────────┘
```

### 8.2. Vị trí hooks trên giao diện

```
┌─────────────────────────────────────────────┐
│  wp_head()         ← Trong <head>           │
│  ┌─ meta tags, CSS, JS header ─────────┐    │
│  └─────────────────────────────────────┘    │
├─────────────────────────────────────────────┤
│  wp_body_open()    ← Ngay sau <body>        │
│  ┌─ tracking code, skip link ──────────┐    │
│  └─────────────────────────────────────┘    │
├─────────────────────────────────────────────┤
│           ┌─────────────────┐               │
│           │ wp_nav_menu()   │               │
│           │ hook: wp_nav_   │               │
│           │ menu_items      │               │
│           └─────────────────┘               │
├─────────────────────────────────────────────┤
│                                             │
│  the_title()   ← filter: the_title         │
│  the_content() ← filter: the_content ★     │
│  the_excerpt() ← filter: the_excerpt       │
│                                             │
│  ┌─────────────────────────────────────┐    │
│  │ Trong the_content():                │    │
│  │  - Shortcodes được xử lý           │    │
│  │  - oEmbed được render              │    │
│  │  - wpautop() thêm <p> tags         │    │
│  │  - Plugin thêm nội dung qua filter │    │
│  └─────────────────────────────────────┘    │
│                                             │
├─────────────────────────────────────────────┤
│  dynamic_sidebar()                          │
│  ┌─ widget_title (filter) ─────────────┐    │
│  │  widget_text (filter)               │    │
│  └─────────────────────────────────────┘    │
├─────────────────────────────────────────────┤
│  wp_footer()       ← Trước </body>         │
│  ┌─ JS footer, analytics ─────────────┐    │
│  └─────────────────────────────────────┘    │
└─────────────────────────────────────────────┘
```

---

## 9. Block Theme Và theme.json

### 9.1. Cấu trúc theme.json

```
theme.json
│
├── $schema          → URL schema validation
├── version          → 2 (WP 6.0+)
│
├── settings         → Cấu hình cho editor
│   ├── color
│   │   ├── palette          → Bảng màu tùy chỉnh
│   │   ├── gradients        → Gradient presets
│   │   ├── duotone          → Duotone filter
│   │   └── custom: false    → Tắt color picker tự do
│   │
│   ├── typography
│   │   ├── fontFamilies     → Font chữ
│   │   ├── fontSizes        → Kích thước chữ presets
│   │   ├── lineHeight: true → Bật line-height control
│   │   └── letterSpacing    → Letter spacing
│   │
│   ├── spacing
│   │   ├── padding: true    → Bật padding control
│   │   ├── margin: true     → Bật margin control
│   │   ├── blockGap: true   → Khoảng cách giữa blocks
│   │   └── units: ["px","rem","%","vw"]
│   │
│   ├── layout
│   │   ├── contentSize: "800px"    → Chiều rộng content
│   │   └── wideSize: "1200px"      → Chiều rộng wide
│   │
│   └── blocks       → Override settings cho từng block
│       └── core/paragraph
│           └── typography → Cấu hình riêng cho paragraph
│
├── styles           → CSS global styles
│   ├── color
│   │   ├── background       → Màu nền
│   │   └── text             → Màu chữ
│   ├── typography
│   │   ├── fontFamily
│   │   ├── fontSize
│   │   └── lineHeight
│   ├── spacing
│   │   └── padding
│   ├── elements     → Style cho HTML elements
│   │   ├── link → { color, :hover }
│   │   ├── h1 → { fontSize, lineHeight }
│   │   ├── h2, h3, h4...
│   │   └── button → { color, background, border }
│   └── blocks       → Style cho từng block
│       ├── core/site-title
│       ├── core/navigation
│       └── core/post-title
│
├── templateParts    → Khai báo template parts
│   ├── { name: "header", title: "Header", area: "header" }
│   ├── { name: "footer", title: "Footer", area: "footer" }
│   └── { name: "sidebar", title: "Sidebar", area: "uncategorized" }
│
├── customTemplates  → Khai báo custom templates
│   ├── { name: "full-width", title: "Full Width" }
│   └── { name: "blank", title: "Blank Canvas" }
│
└── patterns         → Đăng ký block patterns
    └── [ "pattern-slug-1", "pattern-slug-2" ]
```

### 9.2. Ví dụ theme.json

```json
{
    "$schema": "https://schemas.wp.org/wp/6.5/theme.json",
    "version": 2,
    "settings": {
        "color": {
            "palette": [
                { "slug": "primary", "color": "#0073aa", "name": "Primary" },
                { "slug": "secondary", "color": "#23282d", "name": "Secondary" },
                { "slug": "accent", "color": "#00a0d2", "name": "Accent" },
                { "slug": "light", "color": "#f7f7f7", "name": "Light" },
                { "slug": "dark", "color": "#1e1e1e", "name": "Dark" }
            ],
            "custom": false
        },
        "typography": {
            "fontFamilies": [
                {
                    "fontFamily": "'Inter', sans-serif",
                    "slug": "inter",
                    "name": "Inter",
                    "fontFace": [
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "400",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/inter-regular.woff2"]
                        },
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "700",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/inter-bold.woff2"]
                        }
                    ]
                }
            ],
            "fontSizes": [
                { "slug": "small", "size": "0.875rem", "name": "Small" },
                { "slug": "medium", "size": "1rem", "name": "Medium" },
                { "slug": "large", "size": "1.25rem", "name": "Large" },
                { "slug": "x-large", "size": "2rem", "name": "Extra Large" }
            ]
        },
        "spacing": {
            "padding": true,
            "margin": true,
            "units": ["px", "rem", "%", "vw"]
        },
        "layout": {
            "contentSize": "800px",
            "wideSize": "1200px"
        }
    },
    "styles": {
        "color": {
            "background": "var(--wp--preset--color--light)",
            "text": "var(--wp--preset--color--dark)"
        },
        "typography": {
            "fontFamily": "var(--wp--preset--font-family--inter)",
            "fontSize": "var(--wp--preset--font-size--medium)",
            "lineHeight": "1.7"
        },
        "elements": {
            "link": {
                "color": { "text": "var(--wp--preset--color--primary)" },
                ":hover": {
                    "color": { "text": "var(--wp--preset--color--accent)" }
                }
            },
            "h1": {
                "typography": { "fontSize": "2.5rem", "lineHeight": "1.2" }
            },
            "h2": {
                "typography": { "fontSize": "2rem", "lineHeight": "1.3" }
            }
        }
    },
    "templateParts": [
        { "name": "header", "title": "Header", "area": "header" },
        { "name": "footer", "title": "Footer", "area": "footer" }
    ]
}
```

### 9.3. Ví dụ templates/index.html (Block Theme)

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

    <!-- wp:query {"queryId":1,"query":{"perPage":10,"offset":0,"postType":"post"}} -->
    <div class="wp-block-query">

        <!-- wp:post-template -->
            <!-- wp:post-featured-image {"isLink":true} /-->
            <!-- wp:post-title {"isLink":true,"level":2} /-->
            <!-- wp:post-date /-->
            <!-- wp:post-excerpt {"moreText":"Đọc tiếp →"} /-->
        <!-- /wp:post-template -->

        <!-- wp:query-pagination -->
            <!-- wp:query-pagination-previous /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next /-->
        <!-- /wp:query-pagination -->

        <!-- wp:query-no-results -->
            <!-- wp:paragraph -->
            <p>Không có bài viết nào.</p>
            <!-- /wp:paragraph -->
        <!-- /wp:query-no-results -->

    </div>
    <!-- /wp:query -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

---

## 10. So Sánh: Theme Phổ Biến Trên Thị Trường

### 10.1. Bảng so sánh

```
┌────────────────┬──────────┬─────────────┬──────────────┬─────────────┐
│                │  Astra   │ GeneratePress│ Twenty       │ Flavor /    │
│ Tiêu chí       │          │             │ Twenty-Four  │ Flavor Block│
├────────────────┼──────────┼─────────────┼──────────────┼─────────────┤
│ Loại           │ Classic  │ Classic     │ Block Theme  │ Block Theme │
│ File size      │ ~50KB    │ ~30KB       │ ~200KB       │ ~100KB      │
│ PHP files      │ 50+      │ 40+         │ 5-10         │ 5-10        │
│ HTML templates │ 0        │ 0           │ 15+          │ 10+         │
│ theme.json     │ Không    │ Không       │ Có           │ Có          │
│ Customizer     │ Mạnh     │ Mạnh        │ Ít           │ Ít          │
│ Site Editor    │ Không    │ Không       │ Đầy đủ       │ Đầy đủ      │
│ Hook system    │ Nhiều    │ Rất nhiều   │ Ít           │ Ít          │
│ WooCommerce    │ Tích hợp │ Tích hợp    │ Cơ bản       │ Cơ bản      │
│ Page Builder   │ Hỗ trợ   │ Hỗ trợ      │ Gutenberg    │ Gutenberg   │
│ Tốc độ        │ Nhanh    │ Rất nhanh   │ Nhanh        │ Nhanh       │
│ Học dễ?       │ TB       │ TB          │ Dễ           │ Dễ          │
│ Dev friendly? │ Rất tốt  │ Tuyệt vời  │ Tốt          │ Tốt         │
├────────────────┼──────────┼─────────────┼──────────────┼─────────────┤
│ Phù hợp cho   │ Mọi loại │ Dev muốn    │ Blog, site   │ Blog, site  │
│                │ website  │ kiểm soát   │ đơn giản     │ hiện đại    │
│                │          │ hoàn toàn   │ kéo thả      │ kéo thả     │
└────────────────┴──────────┴─────────────┴──────────────┴─────────────┘
```

### 10.2. Cấu trúc Astra Theme (phổ biến nhất)

```
astra/
├── style.css
├── functions.php                 → Nhỏ gọn, load files từ inc/
├── header.php
├── footer.php
├── sidebar.php
├── index.php
├── single.php
├── page.php
├── archive.php
├── search.php
├── 404.php
│
├── inc/                          → Core logic
│   ├── class-astra-theme-options.php
│   ├── class-astra-dynamic-css.php
│   ├── customizer/               → Customizer panels, sections
│   │   ├── class-astra-customizer.php
│   │   ├── configurations/       → Từng section riêng
│   │   └── controls/             → Custom controls
│   │
│   ├── addons/                   → Các tính năng mở rộng
│   │   ├── transparent-header/
│   │   ├── breadcrumbs/
│   │   └── scroll-to-top/
│   │
│   ├── core/                     → Core functions
│   │   ├── class-astra-enqueue-scripts.php
│   │   ├── class-astra-admin-settings.php
│   │   └── class-theme-strings.php
│   │
│   └── compatibility/            → Tương thích plugins
│       ├── class-astra-woocommerce.php
│       ├── class-astra-elementor.php
│       └── class-astra-beaver-builder.php
│
├── template-parts/
│   ├── content-blog.php
│   ├── content-single.php
│   ├── content-page.php
│   ├── header/
│   │   ├── header-main-layout.php
│   │   └── header-mobile-layout.php
│   └── footer/
│       └── footer-main-layout.php
│
└── assets/
    ├── css/
    │   ├── minified/
    │   └── unminified/
    └── js/
        ├── minified/
        └── unminified/
```

### 10.3. Cấu trúc GeneratePress (developer favorite)

```
generatepress/
├── style.css
├── functions.php
├── header.php, footer.php, sidebar.php
├── index.php, single.php, page.php, archive.php
│
├── inc/
│   ├── structure/                ← Đặc biệt: tách structure
│   │   ├── header.php            → Render header
│   │   ├── navigation.php        → Render nav
│   │   ├── post-meta.php         → Date, author, categories
│   │   ├── footer.php            → Render footer
│   │   ├── sidebars.php          → Sidebar logic
│   │   ├── featured-images.php   → Thumbnail logic
│   │   └── archives.php          → Archive layout
│   │
│   ├── defaults.php              → Default options
│   ├── css-output.php            → Dynamic CSS
│   ├── general.php               → General functions
│   ├── markup.php                → HTML wrappers
│   ├── typography.php            → Font handling
│   └── class-css.php             → CSS builder
│
└── assets/
```

**GeneratePress hooks visual guide:**
```
┌─────────────────────────────────────────────┐
│  generate_before_header                     │
│  ┌─────────────────────────────────────┐    │
│  │  generate_header                    │    │
│  │  ┌─ generate_inside_header ────┐    │    │
│  │  │  Logo + Navigation          │    │    │
│  │  └────────────────────────────┘    │    │
│  └─────────────────────────────────────┘    │
│  generate_after_header                      │
│                                             │
│  generate_before_content                    │
│  ┌─────────────────────────────────────┐    │
│  │  generate_before_main_content       │    │
│  │  ┌─ Content area ──────────────┐    │    │
│  │  │  generate_before_entry      │    │    │
│  │  │  ┌── Entry Content ──────┐  │    │    │
│  │  │  │  generate_after_      │  │    │    │
│  │  │  │  entry_title          │  │    │    │
│  │  │  │  generate_after_      │  │    │    │
│  │  │  │  entry_content        │  │    │    │
│  │  │  └───────────────────────┘  │    │    │
│  │  │  generate_after_entry       │    │    │
│  │  └────────────────────────────┘    │    │
│  │  generate_after_main_content       │    │
│  └─────────────────────────────────────┘    │
│  generate_after_content                     │
│                                             │
│  generate_before_footer                     │
│  ┌─────────────────────────────────────┐    │
│  │  generate_footer                    │    │
│  └─────────────────────────────────────┘    │
│  generate_after_footer                      │
└─────────────────────────────────────────────┘
```

---

## Tổng Kết

| Sơ đồ | Giúp bạn hiểu |
|-------|---------------|
| Giải phẫu trang | File PHP nào render phần nào trên trang |
| Template Hierarchy | WordPress chọn template nào cho từng URL |
| Classic vs Block | Khác biệt giữa 2 loại theme |
| Luồng xử lý | Request đi từ URL → HTML như thế nào |
| Layout patterns | Các kiểu bố cục phổ biến |
| functions.php | Chức năng nào đăng ký ở đâu |
| The Loop | Cách WordPress lặp qua bài viết |
| Hooks vị trí | Hook nào fire ở đâu trên trang |
| theme.json | Cấu trúc cấu hình Block Theme |
| Theme phổ biến | Kiến trúc của theme thật trên thị trường |

---

[← Quay lại: Ví dụ thực tế](./08-vi-du-thuc-te.md) | [↑ Mục lục Theme](./index.md) | [→ Tiếp: Phát triển Plugin](../05-plugins/)
