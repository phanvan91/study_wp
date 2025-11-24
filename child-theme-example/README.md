# Child Theme Example

Đây là ví dụ về cách tạo Child Theme cho WordPress.

## Cấu Trúc

```
child-theme-example/
├── style.css          # File CSS chính (bắt buộc)
├── functions.php      # File functions (khuyến nghị)
├── README.md         # File hướng dẫn
└── js/               # Thư mục JavaScript (tùy chọn)
    └── custom.js
```

## Cách Sử Dụng

### Bước 1: Đổi Tên Theme

1. Đổi tên folder từ `child-theme-example` thành tên bạn muốn
2. Mở `style.css` và sửa:
   - `Theme Name`: Tên theme của bạn
   - `Template`: Tên folder của parent theme (ví dụ: `twentytwentyfour`)

### Bước 2: Copy Vào WordPress

1. Copy folder vào: `/wp-content/themes/your-child-theme-name/`
2. Đảm bảo parent theme đã được cài đặt

### Bước 3: Kích Hoạt

1. Vào WordPress Admin → Appearance → Themes
2. Kích hoạt child theme

## Override Parent Theme Files

Để override file của parent theme, copy file đó vào child theme:

**Ví dụ:**
- Parent: `/wp-content/themes/parent-theme/header.php`
- Child: `/wp-content/themes/child-theme/header.php`

WordPress sẽ tự động dùng file từ child theme.

## Customization

### Thêm Custom CSS

Sửa file `style.css` và thêm CSS của bạn vào cuối file.

### Thêm Custom Functions

Sửa file `functions.php` và thêm functions của bạn.

### Thêm Custom JavaScript

1. Tạo file `js/custom.js`
2. Code đã có sẵn trong `functions.php` để enqueue script

## Lưu Ý

- Luôn dùng Child Theme khi customize theme
- Backup trước khi chỉnh sửa
- Test trên staging site trước khi deploy
- Update parent theme thường xuyên

## Tài Liệu

- [WordPress Child Themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [WordPress Theme Handbook](https://developer.wordpress.org/themes/)


