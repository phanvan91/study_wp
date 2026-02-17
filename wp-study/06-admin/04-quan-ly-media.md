# 04 - Quản Lý Media trong WordPress Admin

> Tài liệu dành cho PHP Laravel developer chuyển sang WordPress.
> Đọc source code WordPress thực tế, phân tích chi tiết upload flow, image processing, hooks và cách lưu DB.

---

## Mục Lục

1. [Tổng Quan Media Management](#1-tổng-quan-media-management)
2. [Upload Flow Chi Tiết](#2-upload-flow-chi-tiết)
3. [Hàm media_handle_upload() Phân Tích](#3-hàm-media_handle_upload-phân-tích)
4. [Hàm wp_handle_upload() Phân Tích](#4-hàm-wp_handle_upload-phân-tích)
5. [Media Library Views](#5-media-library-views)
6. [Image Sizes và Thumbnails](#6-image-sizes-và-thumbnails)
7. [Image Processing - wp_generate_attachment_metadata()](#7-image-processing---wp_generate_attachment_metadata)
8. [Upload Directory Structure](#8-upload-directory-structure)
9. [EXIF và Image Metadata](#9-exif-và-image-metadata)
10. [DB: Media Lưu Gì?](#10-db-media-lưu-gì)
11. [Hooks Media - Danh Sách Đầy Đủ](#11-hooks-media---danh-sách-đầy-đủ)
12. [Image Editor trong Admin](#12-image-editor-trong-admin)
13. [Big Image Threshold (WP 5.3+)](#13-big-image-threshold-wp-53)
14. [Upload MIME Types](#14-upload-mime-types)
15. [Custom Media Columns](#15-custom-media-columns)
16. [Ví Dụ Thực Tế: Plugin Quản Lý Media](#16-ví-dụ-thực-tế-plugin-quản-lý-media)
17. [So Sánh Với Laravel](#17-so-sánh-với-laravel)
18. [Tổng Kết](#18-tổng-kết)

---

## 1. Tổng Quan Media Management

### URLs Admin

| Trang | URL | Mô tả |
|-------|-----|-------|
| Media Library | `/wp-admin/upload.php` | Danh sách tất cả media |
| Upload New | `/wp-admin/media-new.php` | Form upload file mới |
| Edit Media | `/wp-admin/post.php?post={id}&action=edit` | Sửa thông tin media |
| Async Upload | `/wp-admin/async-upload.php` | Handler upload bất đồng bộ |

### Source Files Chính

```
wp-admin/
├── upload.php                              # Media library list (465 dòng)
├── media-new.php                           # Upload form (91 dòng)
├── async-upload.php                        # Async upload handler (166 dòng)
├── includes/
│   ├── media.php                           # Media API (~3890 dòng)
│   ├── image.php                           # Image processing (1280 dòng)
│   ├── file.php                            # File system API (~2829 dòng)
│   └── class-wp-media-list-table.php       # List table cho Media Library
```

### Capability (Quyền)

```php
// Kiểm tra quyền upload - source: wp-admin/upload.php dòng 12
if ( ! current_user_can( 'upload_files' ) ) {
    wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
}
```

Capability `upload_files` mặc định được gán cho các role:
- **Administrator**: co
- **Editor**: co
- **Author**: co
- **Contributor**: KHONG co
- **Subscriber**: KHONG co

> **So sánh Laravel**: Trong Laravel, bạn sẽ dùng Gate/Policy:
> `$this->authorize('upload', Media::class);`
> Hoặc middleware: `->middleware('can:upload_files')`

---

## 2. Upload Flow Chi Tiết

### Sơ đồ tổng quan

```
User chọn file trên trình duyệt
    │
    ▼
Plupload JS Library (wp-includes/js/plupload/)
    │ Gửi file qua XMLHttpRequest
    ▼
POST /wp-admin/async-upload.php
    │ action = 'upload-attachment' (Grid view)
    │ hoặc không có action (Classic view)
    ▼
wp_ajax_upload_attachment()  hoặc  media_handle_upload()
    │
    ▼
media_handle_upload( $file_id, $post_id )
    │
    ├──▶ wp_handle_upload( $_FILES[$file_id], $overrides, $time )
    │       │
    │       ├──▶ wp_check_filetype_and_ext() - Validate MIME type
    │       ├──▶ wp_unique_filename() - Tạo tên file duy nhất
    │       └──▶ move_uploaded_file() - Di chuyển tới wp-content/uploads/YYYY/MM/
    │
    ├──▶ wp_insert_attachment( $attachment, $file, $post_id )
    │       │
    │       └──▶ INSERT vào wp_posts (post_type = 'attachment')
    │
    └──▶ wp_generate_attachment_metadata( $attachment_id, $file )
            │
            ├──▶ wp_create_image_subsizes() - Tạo thumbnails
            │       ├── thumbnail (150x150, crop)
            │       ├── medium (300x300)
            │       ├── medium_large (768x0)
            │       └── large (1024x1024)
            │
            ├──▶ wp_read_image_metadata() - Đọc EXIF/IPTC data
            │
            └──▶ wp_update_attachment_metadata() - Lưu vào wp_postmeta
```

### Source code async-upload.php

Khi sử dụng Grid view (mặc định từ WP 4.0+), file được upload qua AJAX:

```php
// Source: wp-admin/async-upload.php dòng 9-31
if ( isset( $_REQUEST['action'] ) && 'upload-attachment' === $_REQUEST['action'] ) {
    define( 'DOING_AJAX', true );
}

// ... bootstrap WordPress ...

if ( isset( $_REQUEST['action'] ) && 'upload-attachment' === $_REQUEST['action'] ) {
    require ABSPATH . 'wp-admin/includes/ajax-actions.php';
    send_nosniff_header();
    nocache_headers();
    wp_ajax_upload_attachment();
    die( '0' );
}

// Classic upload (non-AJAX):
// Source: wp-admin/async-upload.php dòng 113
$id = media_handle_upload( 'async-upload', $post_id );
```

### Source code media-new.php

```php
// Source: wp-admin/media-new.php dòng 19
wp_enqueue_script( 'plupload-handlers' );

// Xử lý HTML upload (non-JS fallback)
// Source: wp-admin/media-new.php dòng 29-39
if ( $_POST ) {
    if ( isset( $_POST['html-upload'] ) && ! empty( $_FILES ) ) {
        check_admin_referer( 'media-form' );
        $upload_id = media_handle_upload( 'async-upload', $post_id );
        if ( is_wp_error( $upload_id ) ) {
            wp_die( $upload_id );
        }
    }
    wp_redirect( admin_url( 'upload.php' ) );
    exit;
}
```

---

## 3. Hàm media_handle_upload() Phân Tích

Đây là hàm chính xử lý toàn bộ quá trình upload media.

**Source**: `wp-admin/includes/media.php` dòng 295

```php
function media_handle_upload( $file_id, $post_id, $post_data = array(), $overrides = array( 'test_form' => false ) ) {
    $time = current_time( 'mysql' );
    $post = get_post( $post_id );

    if ( $post ) {
        // Dùng ngày của post để tổ chức thư mục upload
        if ( 'page' !== $post->post_type && substr( $post->post_date, 0, 4 ) > 0 ) {
            $time = $post->post_date;
        }
    }

    // Bước 1: Upload file vật lý
    $file = wp_handle_upload( $_FILES[ $file_id ], $overrides, $time );

    if ( isset( $file['error'] ) ) {
        return new WP_Error( 'upload_error', $file['error'] );
    }

    // Bước 2: Chuẩn bị dữ liệu attachment
    $name    = $_FILES[ $file_id ]['name'];
    $ext     = pathinfo( $name, PATHINFO_EXTENSION );
    $name    = wp_basename( $name, ".$ext" );
    $url     = $file['url'];
    $type    = $file['type'];
    $file    = $file['file'];
    $title   = sanitize_text_field( $name );
    $content = '';
    $excerpt = '';

    // Đọc metadata từ audio/image nếu có
    if ( preg_match( '#^audio#', $type ) ) {
        $meta = wp_read_audio_metadata( $file );
        // ... xử lý title, album, artist ...
    } elseif ( str_starts_with( $type, 'image/' ) ) {
        $image_meta = wp_read_image_metadata( $file );
        // ... xử lý title, caption từ EXIF ...
    }

    // Bước 3: Tạo attachment data
    $attachment = array_merge( array(
        'post_mime_type' => $type,
        'guid'           => $url,
        'post_parent'    => $post_id,
        'post_title'     => $title,
        'post_content'   => $content,
        'post_excerpt'   => $excerpt,
    ), $post_data );

    // Bước 4: Insert vào database
    $attachment_id = wp_insert_attachment( $attachment, $file, $post_id, true );

    if ( is_wp_error( $attachment_id ) ) {
        // ... xóa file nếu insert lỗi
        return $attachment_id;
    }

    // Bước 5: Generate metadata (thumbnails, EXIF, v.v.)
    $metadata = wp_generate_attachment_metadata( $attachment_id, $file );
    wp_update_attachment_metadata( $attachment_id, $metadata );

    return $attachment_id;
}
```

> **So sánh Laravel**: Tương đương với luồng:
> ```php
> $path = $request->file('photo')->store('photos', 'public');
> Photo::create(['path' => $path, 'user_id' => auth()->id()]);
> // + Intervention Image để tạo thumbnails
> ```

---

## 4. Hàm wp_handle_upload() Phân Tích

**Source**: `wp-admin/includes/file.php` dòng 1095

```php
function wp_handle_upload( &$file, $overrides = false, $time = null ) {
    $action = 'wp_handle_upload';
    if ( isset( $overrides['action'] ) ) {
        $action = $overrides['action'];
    }
    return _wp_handle_upload( $file, $overrides, $time, $action );
}
```

Hàm `_wp_handle_upload()` (private) thực hiện:

1. **Validate file**: Kiểm tra `$_FILES` array hợp lệ
2. **Check file size**: So sánh với `upload_max_filesize` và `post_max_size`
3. **Check MIME type**: Gọi `wp_check_filetype_and_ext()`
4. **Apply filter**: `wp_handle_upload_prefilter` cho phép plugin can thiệp
5. **Tạo thư mục**: `wp_upload_dir()` tạo `wp-content/uploads/YYYY/MM/`
6. **Tạo unique filename**: `wp_unique_filename()` tránh trùng tên
7. **Di chuyển file**: `move_uploaded_file()` hoặc `copy()`
8. **Set permissions**: `chmod` file theo `FS_CHMOD_FILE`
9. **Apply filter**: `wp_handle_upload` cho phép plugin xử lý sau upload

```php
// Filter trước upload - validate custom
add_filter( 'wp_handle_upload_prefilter', function( $file ) {
    // $file = $_FILES['async-upload'] array
    $size = $file['size'];

    // Giới hạn 5MB cho non-admin
    if ( ! current_user_can( 'manage_options' ) && $size > 5 * 1024 * 1024 ) {
        $file['error'] = 'File quá lớn! Tối đa 5MB.';
    }

    return $file;
});

// Filter sau upload thành công
add_filter( 'wp_handle_upload', function( $upload ) {
    // $upload = array( 'file' => '/path/to/file', 'url' => 'http://...', 'type' => 'image/jpeg' )
    error_log( 'File uploaded: ' . $upload['file'] );
    return $upload;
});
```

---

## 5. Media Library Views

### Grid View (Mặc định)

**Source**: `wp-admin/upload.php` dòng 131-249

```php
// Source: wp-admin/upload.php dòng 131-138
$modes = array( 'grid', 'list' );

if ( isset( $_GET['mode'] ) && in_array( $_GET['mode'], $modes, true ) ) {
    $mode = $_GET['mode'];
    update_user_option( get_current_user_id(), 'media_library_mode', $mode );
} else {
    $mode = get_user_option( 'media_library_mode', get_current_user_id() )
        ? get_user_option( 'media_library_mode', get_current_user_id() )
        : 'grid';
}
```

Grid view sử dụng JavaScript Backbone.js framework:

```php
// Source: wp-admin/upload.php dòng 140-173
if ( 'grid' === $mode ) {
    wp_enqueue_media();
    wp_enqueue_script( 'media-grid' );
    wp_enqueue_script( 'media' );

    // Truyền settings cho JS
    wp_localize_script( 'media-grid', '_wpMediaGridSettings', array(
        'adminUrl'  => parse_url( self_admin_url(), PHP_URL_PATH ),
        'queryVars' => (object) $vars,
    ) );

    // ... help tabs ...

    // Render container - JS sẽ fill nội dung
    ?>
    <div class="wrap" id="wp-media-grid" data-search="<?php _admin_search_query(); ?>">
        <h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
        <!-- JS renders media grid here -->
    </div>
    <?php
    require_once ABSPATH . 'wp-admin/admin-footer.php';
    exit;
}
```

### List View

```php
// Source: wp-admin/upload.php dòng 252-460
$wp_list_table = _get_list_table( 'WP_Media_List_Table' );
$pagenum       = $wp_list_table->get_pagenum();

// Handle bulk actions
$doaction = $wp_list_table->current_action();
```

**Source class**: `wp-admin/includes/class-wp-media-list-table.php`

### Bulk Actions trong List View

```php
// Source: wp-admin/upload.php dòng 258-346
switch ( $doaction ) {
    case 'detach':
        wp_media_attach_action( $_REQUEST['parent_post_id'], 'detach' );
        break;
    case 'attach':
        wp_media_attach_action( $_REQUEST['found_post_id'] );
        break;
    case 'trash':
        foreach ( $post_ids as $post_id ) {
            if ( ! current_user_can( 'delete_post', $post_id ) ) {
                wp_die( __( 'Sorry, you are not allowed to move this item to the Trash.' ) );
            }
            if ( ! wp_trash_post( $post_id ) ) {
                wp_die( __( 'Error in moving the item to Trash.' ) );
            }
        }
        break;
    case 'untrash':
        // ... restore from trash ...
        break;
    case 'delete':
        foreach ( $post_ids as $post_id_del ) {
            if ( ! current_user_can( 'delete_post', $post_id_del ) ) {
                wp_die( __( 'Sorry, you are not allowed to delete this item.' ) );
            }
            if ( ! wp_delete_attachment( $post_id_del ) ) {
                wp_die( __( 'Error in deleting the attachment.' ) );
            }
        }
        break;
}
```

---

## 6. Image Sizes và Thumbnails

### Default Sizes (từ wp_options)

| Size Name | Chiều rộng | Chiều cao | Crop | Option Name |
|-----------|------------|-----------|------|-------------|
| `thumbnail` | 150 | 150 | Yes (crop) | `thumbnail_size_w`, `thumbnail_size_h`, `thumbnail_crop` |
| `medium` | 300 | 300 | No | `medium_size_w`, `medium_size_h` |
| `medium_large` | 768 | 0 | No | `medium_large_size_w`, `medium_large_size_h` |
| `large` | 1024 | 1024 | No | `large_size_w`, `large_size_h` |

> `0` cho chiều cao nghĩa là tự tính theo tỷ lệ (proportional).

### Đăng ký Custom Image Size

```php
// Trong functions.php của theme hoặc plugin
add_action( 'after_setup_theme', function() {
    // add_image_size( $name, $width, $height, $crop )
    add_image_size( 'blog-thumbnail', 600, 400, true );   // Hard crop
    add_image_size( 'blog-featured', 1200, 600, true );
    add_image_size( 'sidebar-thumb', 120, 120, true );

    // Crop từ vị trí cụ thể (WP 3.9+)
    add_image_size( 'custom-crop', 300, 300, array( 'left', 'top' ) );
    // Crop positions: 'left', 'center', 'right' x 'top', 'center', 'bottom'
});
```

### Hiển thị Custom Size trong Media Dialog

```php
// Mặc định custom sizes KHÔNG hiện trong dropdown "Attachment Display Settings"
// Phải thêm bằng filter:
add_filter( 'image_size_names_choose', function( $sizes ) {
    return array_merge( $sizes, array(
        'blog-thumbnail' => __( 'Blog Thumbnail' ),
        'blog-featured'  => __( 'Blog Featured' ),
    ) );
});
```

### Lấy thông tin tất cả registered sizes

```php
// Từ WordPress 5.3+
$all_sizes = wp_get_registered_image_subsizes();
/*
Array (
    'thumbnail'    => array( 'width' => 150,  'height' => 150,  'crop' => true ),
    'medium'       => array( 'width' => 300,  'height' => 300,  'crop' => false ),
    'medium_large' => array( 'width' => 768,  'height' => 0,    'crop' => false ),
    'large'        => array( 'width' => 1024, 'height' => 1024, 'crop' => false ),
    'blog-thumb'   => array( 'width' => 600,  'height' => 400,  'crop' => true ),
)
*/
```

> **So sánh Laravel + Intervention Image**:
> ```php
> $image = Image::make($request->file('photo'));
> $image->fit(150, 150)->save(storage_path('app/public/thumbs/' . $name));
> $image->resize(300, null, function ($c) { $c->aspectRatio(); })
>       ->save(storage_path('app/public/medium/' . $name));
> ```

---

## 7. Image Processing - wp_generate_attachment_metadata()

**Source**: `wp-admin/includes/image.php` dòng 579

```php
function wp_generate_attachment_metadata( $attachment_id, $file ) {
    $attachment = get_post( $attachment_id );
    $metadata   = array();
    $support    = false;
    $mime_type  = get_post_mime_type( $attachment );

    // Xử lý theo loại file
    if ( 'image/heic' === $mime_type
        || ( preg_match( '!^image/!', $mime_type ) && file_is_displayable_image( $file ) )
    ) {
        // IMAGE: Tạo thumbnails
        $metadata = wp_create_image_subsizes( $file, $attachment_id );
    } elseif ( wp_attachment_is( 'video', $attachment ) ) {
        // VIDEO: Đọc metadata (duration, width, height, codec...)
        $metadata = wp_read_video_metadata( $file );
        $support  = current_theme_supports( 'post-thumbnails', 'attachment:video' );
    } elseif ( wp_attachment_is( 'audio', $attachment ) ) {
        // AUDIO: Đọc metadata (title, artist, album, duration...)
        $metadata = wp_read_audio_metadata( $file );
        $support  = current_theme_supports( 'post-thumbnails', 'attachment:audio' );
    }

    // PDF: Tạo preview thumbnails
    if ( 'application/pdf' === $mime_type ) {
        // Tạo ảnh preview cho trang đầu tiên của PDF
        // Sử dụng Imagick nếu có
        $editor = wp_get_image_editor( $file );
        // ... tạo thumbnails cho PDF ...
    }

    // Thêm filesize
    if ( ! isset( $metadata['filesize'] ) && file_exists( $file ) ) {
        $metadata['filesize'] = wp_filesize( $file );
    }

    /**
     * Filter metadata sau khi generate
     * @param array  $metadata      Metadata array
     * @param int    $attachment_id Attachment ID
     * @param string $context       'create' hoặc 'update'
     */
    return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'create' );
}
```

### wp_create_image_subsizes() - Tạo thumbnails

**Source**: `wp-admin/includes/image.php` dòng 240

```php
function wp_create_image_subsizes( $file, $attachment_id ) {
    $imagesize = wp_getimagesize( $file );

    if ( empty( $imagesize ) ) {
        return array(); // Không phải ảnh
    }

    // Metadata mặc định
    $image_meta = array(
        'width'    => $imagesize[0],
        'height'   => $imagesize[1],
        'file'     => _wp_relative_upload_path( $file ),
        'filesize' => wp_filesize( $file ),
        'sizes'    => array(),
    );

    // Đọc EXIF/IPTC
    $exif_meta = wp_read_image_metadata( $file );
    if ( $exif_meta ) {
        $image_meta['image_meta'] = $exif_meta;
    }

    // Big Image Threshold (mặc định 2560px)
    $threshold = (int) apply_filters( 'big_image_size_threshold', 2560, $imagesize, $file, $attachment_id );

    // Nếu ảnh quá lớn, scale down và lưu bản "-scaled"
    if ( $threshold && ( $image_meta['width'] > $threshold || $image_meta['height'] > $threshold ) ) {
        $editor = wp_get_image_editor( $file );
        $resized = $editor->resize( $threshold, $threshold );
        // Lưu file ví dụ: my-image-scaled.jpg
        $saved = $editor->save( $editor->generate_filename( 'scaled' ) );
        $image_meta = _wp_image_meta_replace_original( $saved, $file, $image_meta, $attachment_id );
    }

    // Lưu metadata ban đầu (chưa có thumbnails)
    wp_update_attachment_metadata( $attachment_id, $image_meta );

    // Lấy tất cả registered sizes
    $new_sizes = wp_get_registered_image_subsizes();

    /**
     * Filter sizes sẽ được tạo
     */
    $new_sizes = apply_filters( 'intermediate_image_sizes_advanced', $new_sizes, $image_meta, $attachment_id );

    // Tạo từng sub-size
    return _wp_make_subsizes( $new_sizes, $file, $image_meta, $attachment_id );
}
```

### _wp_make_subsizes() - Tạo từng thumbnail

**Source**: `wp-admin/includes/image.php` dòng 430

```php
function _wp_make_subsizes( $new_sizes, $file, $image_meta, $attachment_id ) {
    // Thứ tự ưu tiên tạo thumbnail
    $priority = array(
        'medium'       => null,
        'large'        => null,
        'thumbnail'    => null,
        'medium_large' => null,
    );
    $new_sizes = array_filter( array_merge( $priority, $new_sizes ) );

    $editor = wp_get_image_editor( $file );

    // Xoay ảnh theo EXIF nếu cần
    if ( ! empty( $image_meta['image_meta'] ) ) {
        $editor->maybe_exif_rotate();
    }

    // Tạo từng sub-size
    foreach ( $new_sizes as $new_size_name => $new_size_data ) {
        $new_size_meta = $editor->make_subsize( $new_size_data );
        if ( ! is_wp_error( $new_size_meta ) ) {
            $image_meta['sizes'][ $new_size_name ] = $new_size_meta;
            // Lưu metadata sau MỖI thumbnail (tránh mất dữ liệu nếu bị timeout)
            wp_update_attachment_metadata( $attachment_id, $image_meta );
        }
    }

    return $image_meta;
}
```

---

## 8. Upload Directory Structure

### Cấu trúc mặc định

```
wp-content/uploads/
├── 2024/
│   ├── 01/
│   │   ├── my-photo.jpg                    # Ảnh gốc (hoặc scaled)
│   │   ├── my-photo-scaled.jpg             # Bản scaled (nếu > 2560px)
│   │   ├── my-photo-150x150.jpg            # thumbnail
│   │   ├── my-photo-300x200.jpg            # medium
│   │   ├── my-photo-768x512.jpg            # medium_large
│   │   ├── my-photo-1024x683.jpg           # large
│   │   └── my-photo-600x400.jpg            # custom size
│   ├── 02/
│   │   └── ...
│   └── 12/
│       └── ...
├── 2025/
│   └── ...
├── woocommerce_uploads/                    # WooCommerce uploads (nếu có)
│   └── .htaccess                           # Bảo vệ direct access
└── sites/                                  # Multisite
    ├── 2/
    │   └── 2024/01/...
    └── 3/
        └── 2024/01/...
```

### Hàm wp_upload_dir()

```php
$upload_dir = wp_upload_dir();
/*
Array (
    'path'    => '/var/www/html/wp-content/uploads/2024/01',  // Đường dẫn thực
    'url'     => 'https://example.com/wp-content/uploads/2024/01', // URL
    'subdir'  => '/2024/01',                                   // Thư mục con
    'basedir' => '/var/www/html/wp-content/uploads',           // Thư mục gốc
    'baseurl' => 'https://example.com/wp-content/uploads',     // URL gốc
    'error'   => false,
)
*/
```

### Thay đổi thư mục upload

```php
// Trong wp-config.php - thay đổi hoàn toàn
define( 'UPLOADS', 'wp-content/media' ); // Tương đối từ ABSPATH

// Hoặc dùng filter
add_filter( 'upload_dir', function( $dirs ) {
    // Tổ chức theo user
    $user_id = get_current_user_id();
    $dirs['subdir'] = '/user-' . $user_id . $dirs['subdir'];
    $dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
    $dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
    return $dirs;
});
```

> **So sánh Laravel**: Tương đương `config/filesystems.php` disks configuration.
> ```php
> 'disks' => [
>     'uploads' => [
>         'driver' => 'local',
>         'root' => storage_path('app/public/uploads'),
>     ],
> ];
> ```

---

## 9. EXIF và Image Metadata

### Hàm wp_read_image_metadata()

**Source**: `wp-admin/includes/image.php` dòng 825

```php
function wp_read_image_metadata( $file ) {
    if ( ! file_exists( $file ) ) {
        return false;
    }

    // Cấu trúc metadata trả về
    $meta = array(
        'aperture'          => 0,       // Khẩu độ (f/2.8)
        'credit'            => '',      // Tác giả
        'camera'            => '',      // Model máy ảnh
        'caption'           => '',      // Mô tả
        'created_timestamp' => 0,       // Thời gian chụp (unix timestamp)
        'copyright'         => '',      // Bản quyền
        'focal_length'      => 0,       // Tiêu cự
        'iso'               => 0,       // ISO
        'shutter_speed'     => 0,       // Tốc độ màn trập
        'title'             => '',      // Tiêu đề
        'orientation'       => 0,       // Hướng ảnh (1-8)
        'keywords'          => array(), // Từ khóa
    );

    // Đọc IPTC data trước (ưu tiên hơn EXIF cho text fields)
    if ( is_callable( 'iptcparse' ) ) {
        wp_getimagesize( $file, $info );
        if ( ! empty( $info['APP13'] ) ) {
            $iptc = iptcparse( $info['APP13'] );
            // 2#105 = Headline, 2#005 = Title
            // 2#120 = Caption/Description
            // 2#110 = Credit, 2#080 = Byline
            // 2#116 = Copyright
            // 2#025 = Keywords
        }
    }

    // Đọc EXIF data
    if ( is_callable( 'exif_read_data' ) ) {
        $exif = exif_read_data( $file );
        // FNumber, Model, DateTimeDigitized, FocalLength
        // ISOSpeedRatings, ExposureTime, Orientation
    }

    /**
     * Filter metadata đã đọc
     */
    return apply_filters( 'wp_read_image_metadata', $meta, $file, $image_type, $iptc, $exif );
}
```

---

## 10. DB: Media Lưu Gì?

### Bảng wp_posts (post_type = 'attachment')

```sql
INSERT INTO wp_posts SET
    post_type      = 'attachment',
    post_mime_type = 'image/jpeg',          -- MIME type
    post_title     = 'my-photo',            -- Tên file (sanitized)
    post_content   = '',                     -- Mô tả dài
    post_excerpt   = '',                     -- Caption
    post_status    = 'inherit',              -- Luôn là 'inherit'
    post_parent    = 123,                    -- ID của post đính kèm (0 nếu unattached)
    post_name      = 'my-photo',            -- Slug
    guid           = 'https://example.com/wp-content/uploads/2024/01/my-photo.jpg',
    post_date      = '2024-01-15 10:30:00';
```

### Bảng wp_postmeta

```sql
-- File path tương đối
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(456, '_wp_attached_file', '2024/01/my-photo.jpg');

-- Alt text
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(456, '_wp_attachment_image_alt', 'Mô tả ảnh cho SEO');

-- Metadata phức tạp (serialized array)
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(456, '_wp_attachment_metadata', 'a:6:{s:5:"width";i:2400;s:6:"height";i:1600;...}');
```

### Cấu trúc _wp_attachment_metadata (sau khi unserialize)

```php
array(
    'width'      => 2400,
    'height'     => 1600,
    'file'       => '2024/01/my-photo-scaled.jpg',
    'filesize'   => 524288,
    'sizes'      => array(
        'thumbnail' => array(
            'file'      => 'my-photo-150x150.jpg',
            'width'     => 150,
            'height'    => 150,
            'mime-type' => 'image/jpeg',
            'filesize'  => 8192,
        ),
        'medium' => array(
            'file'      => 'my-photo-300x200.jpg',
            'width'     => 300,
            'height'    => 200,
            'mime-type' => 'image/jpeg',
            'filesize'  => 24576,
        ),
        'medium_large' => array(
            'file'      => 'my-photo-768x512.jpg',
            'width'     => 768,
            'height'    => 512,
            'mime-type' => 'image/jpeg',
            'filesize'  => 65536,
        ),
        'large' => array(
            'file'      => 'my-photo-1024x683.jpg',
            'width'     => 1024,
            'height'    => 683,
            'mime-type' => 'image/jpeg',
            'filesize'  => 131072,
        ),
    ),
    'image_meta' => array(
        'aperture'          => '2.8',
        'credit'            => 'Photographer Name',
        'camera'            => 'Canon EOS R5',
        'caption'           => '',
        'created_timestamp' => 1705312200,
        'copyright'         => '2024 Photographer',
        'focal_length'      => '50',
        'iso'               => '100',
        'shutter_speed'     => '0.005',
        'title'             => '',
        'orientation'       => 1,
        'keywords'          => array(),
    ),
    'original_image' => 'my-photo.jpg', // File gốc (nếu đã scaled)
)
```

### Query Media từ Database

```php
// Lấy tất cả images
$images = get_posts( array(
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => 20,
) );

// Lấy attachments của một post cụ thể
$attachments = get_children( array(
    'post_parent'    => $post_id,
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
) );

// Lấy URL của một size cụ thể
$url = wp_get_attachment_image_url( $attachment_id, 'medium' );
// => 'https://example.com/wp-content/uploads/2024/01/my-photo-300x200.jpg'

// Lấy full img tag với srcset
$img_tag = wp_get_attachment_image( $attachment_id, 'large', false, array(
    'class'   => 'featured-image',
    'loading' => 'lazy',
) );
// => <img width="1024" height="683" src="...large.jpg"
//     class="featured-image" loading="lazy"
//     srcset="...300x200.jpg 300w, ...768x512.jpg 768w, ...1024x683.jpg 1024w"
//     sizes="(max-width: 1024px) 100vw, 1024px" />
```

> **So sánh Laravel**: Laravel không có ORM cho media. Spatie Media Library tạo bảng `media` riêng:
> ```php
> // Spatie Media Library
> $post->addMediaFromRequest('image')
>      ->withResponsiveImages()
>      ->toMediaCollection('featured');
> ```

---

## 11. Hooks Media - Danh Sách Đầy Đủ

### Action Hooks

| Hook | Khi nào | Tham số |
|------|---------|---------|
| `add_attachment` | Sau khi insert attachment vào DB | `$attachment_id` |
| `edit_attachment` | Sau khi update attachment | `$attachment_id` |
| `delete_attachment` | Trước khi xóa attachment | `$attachment_id`, `$post` |
| `wp_create_file_in_uploads` | Khi tạo file trong uploads | `$file`, `$attachment_id` |

### Filter Hooks

| Hook | Chức năng | Tham số |
|------|-----------|---------|
| `wp_handle_upload_prefilter` | Validate trước upload | `$file` ($_FILES array) |
| `wp_handle_upload` | Sau upload thành công | `$upload` (path, url, type) |
| `wp_generate_attachment_metadata` | Filter metadata | `$metadata`, `$attachment_id`, `$context` |
| `wp_get_attachment_url` | Filter URL attachment | `$url`, `$attachment_id` |
| `wp_get_attachment_image_src` | Filter image source | `$image`, `$attachment_id`, `$size`, `$icon` |
| `upload_mimes` | Cho phép MIME types | `$mimes`, `$user` |
| `upload_size_limit` | Giới hạn kích thước | `$size` |
| `intermediate_image_sizes` | Filter tên sizes | `$sizes` |
| `intermediate_image_sizes_advanced` | Filter sizes data | `$sizes`, `$image_meta`, `$attachment_id` |
| `big_image_size_threshold` | Ngưỡng ảnh lớn | `$threshold`, `$imagesize`, `$file`, `$id` |
| `wp_read_image_metadata` | Filter EXIF data | `$meta`, `$file`, `$image_type`, `$iptc`, `$exif` |
| `media_upload_tabs` | Tabs trong media dialog | `$tabs` |
| `image_size_names_choose` | Sizes hiện trong dropdown | `$sizes` |
| `wp_editor_set_quality` | Chất lượng JPEG | `$quality`, `$mime_type` |
| `wp_image_editors` | Image editor classes | `$editors` |
| `image_downsize` | Custom image downsize | `$out`, `$id`, `$size` |
| `wp_get_attachment_image_attributes` | Attributes thẻ img | `$attr`, `$attachment`, `$size` |
| `wp_calculate_image_srcset` | Srcset cho responsive | `$sources`, `$size_array`, `$image_src`, `$image_meta`, `$id` |

### Ví dụ sử dụng hooks

```php
// 1. Thêm watermark sau upload
add_filter( 'wp_generate_attachment_metadata', function( $metadata, $attachment_id, $context ) {
    if ( 'create' !== $context ) return $metadata;

    $file = get_attached_file( $attachment_id );
    if ( ! preg_match( '/\.(jpe?g|png)$/i', $file ) ) return $metadata;

    // Thêm watermark vào ảnh gốc
    $editor = wp_get_image_editor( $file );
    if ( ! is_wp_error( $editor ) ) {
        // ... thêm watermark logic ...
        $editor->save( $file );
    }

    return $metadata;
}, 10, 3 );

// 2. Giới hạn upload size theo user role
add_filter( 'upload_size_limit', function( $size ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return 2 * 1024 * 1024; // 2MB cho non-admin
    }
    return $size; // Không giới hạn cho admin
});

// 3. Thêm MIME types
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['webp'] = 'image/webp';
    $mimes['avif'] = 'image/avif';
    return $mimes;
});

// 4. Chuyển URL media sang CDN
add_filter( 'wp_get_attachment_url', function( $url, $attachment_id ) {
    $upload_dir = wp_get_upload_dir();
    $cdn_url    = 'https://cdn.example.com/uploads';
    return str_replace( $upload_dir['baseurl'], $cdn_url, $url );
}, 10, 2 );

// 5. Skip tạo medium_large size
add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
    unset( $sizes['medium_large'] );
    return $sizes;
});
```

---

## 12. Image Editor trong Admin

WordPress cho phep chỉnh sửa ảnh ngay trong admin (crop, rotate, flip, scale).

**Source**: `wp-admin/includes/image-edit.php`

### Image Editor Classes

```
WP_Image_Editor (abstract base)
├── WP_Image_Editor_Imagick  (ưu tiên, dùng ImageMagick)
└── WP_Image_Editor_GD       (fallback, dùng GD library)
```

```php
// Kiểm tra editor nào đang được dùng
$editor = wp_get_image_editor( $file );
echo get_class( $editor );
// => WP_Image_Editor_Imagick hoặc WP_Image_Editor_GD

// Thay đổi thứ tự ưu tiên
add_filter( 'wp_image_editors', function( $editors ) {
    // Ưu tiên GD thay vì Imagick
    return array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
});

// Thay đổi chất lượng JPEG
add_filter( 'wp_editor_set_quality', function( $quality, $mime_type ) {
    if ( 'image/jpeg' === $mime_type ) {
        return 85; // Mặc định là 82
    }
    return $quality;
}, 10, 2 );
```

---

## 13. Big Image Threshold (WP 5.3+)

Từ WordPress 5.3, ảnh có kích thước lớn hơn 2560px sẽ tự động được scale down.

```php
// Source: wp-admin/includes/image.php dòng 285
$threshold = (int) apply_filters( 'big_image_size_threshold', 2560, $imagesize, $file, $attachment_id );

// Tắt hoàn toàn big image scaling
add_filter( 'big_image_size_threshold', '__return_false' );

// Thay đổi threshold
add_filter( 'big_image_size_threshold', function() {
    return 1920; // Scale xuống max 1920px
});
```

Khi ảnh bị scale:
- File gốc vẫn giữ nguyên: `my-photo.jpg`
- Bản scaled: `my-photo-scaled.jpg`
- `_wp_attached_file` meta trỏ tới bản scaled
- `original_image` trong metadata chứa tên file gốc

```php
// Lấy đường dẫn file gốc (chưa scaled)
$original = wp_get_original_image_path( $attachment_id );

// Lấy URL file gốc
$original_url = wp_get_original_image_url( $attachment_id );
```

---

## 14. Upload MIME Types

### Danh sách MIME types mặc định

```php
// Source: wp-includes/functions.php - wp_get_mime_types()
// Images
'jpg|jpeg|jpe' => 'image/jpeg',
'gif'          => 'image/gif',
'png'          => 'image/png',
'bmp'          => 'image/bmp',
'tiff|tif'     => 'image/tiff',
'webp'         => 'image/webp',
'avif'         => 'image/avif',
'ico'          => 'image/x-icon',
'heic'         => 'image/heic',

// Documents
'pdf'          => 'application/pdf',
'doc'          => 'application/msword',
'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
'xls'          => 'application/vnd.ms-excel',
'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
'ppt'          => 'application/vnd.ms-powerpoint',
'pptx'         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

// Audio
'mp3|m4a|m4b'  => 'audio/mpeg',
'wav'          => 'audio/wav',
'ogg|oga'      => 'audio/ogg',

// Video
'mp4|m4v'      => 'video/mp4',
'mov|qt'       => 'video/quicktime',
'wmv'          => 'video/x-ms-wmv',
'avi'          => 'video/avi',
'webm'         => 'video/webm',
```

### Thêm/Bỏ MIME types

```php
// Cho phép SVG upload
add_filter( 'upload_mimes', function( $mimes ) {
    // Chỉ cho admin
    if ( current_user_can( 'manage_options' ) ) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
});

// Cần thêm filter này để WordPress không block SVG
add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes ) {
    $ext = pathinfo( $filename, PATHINFO_EXTENSION );
    if ( 'svg' === $ext ) {
        $data['type'] = 'image/svg+xml';
        $data['ext']  = 'svg';
    }
    return $data;
}, 10, 4 );
```

---

## 15. Custom Media Columns

### Thêm cột trong Media List View

```php
// Thêm cột "Dimensions"
add_filter( 'manage_media_columns', function( $columns ) {
    $columns['dimensions'] = __( 'Kich thuoc' );
    $columns['filesize']   = __( 'Dung luong' );
    return $columns;
});

// Render nội dung cột
add_action( 'manage_media_custom_column', function( $column_name, $post_id ) {
    if ( 'dimensions' === $column_name ) {
        $metadata = wp_get_attachment_metadata( $post_id );
        if ( $metadata && isset( $metadata['width'] ) ) {
            echo $metadata['width'] . ' x ' . $metadata['height'] . ' px';
        } else {
            echo '&mdash;';
        }
    }

    if ( 'filesize' === $column_name ) {
        $metadata = wp_get_attachment_metadata( $post_id );
        if ( $metadata && isset( $metadata['filesize'] ) ) {
            echo size_format( $metadata['filesize'] );
        } else {
            $file = get_attached_file( $post_id );
            if ( $file && file_exists( $file ) ) {
                echo size_format( filesize( $file ) );
            } else {
                echo '&mdash;';
            }
        }
    }
}, 10, 2 );

// Cho phep sắp xếp theo cột
add_filter( 'manage_upload_sortable_columns', function( $columns ) {
    $columns['filesize'] = 'filesize';
    return $columns;
});
```

---

## 16. Vi Du Thuc Te: Plugin Quan Ly Media

### Plugin giới hạn upload theo user role

```php
<?php
/**
 * Plugin Name: Media Upload Restrictions
 * Description: Giới hạn upload media theo user role
 * Version: 1.0.0
 */

// Giới hạn kích thước upload
add_filter( 'upload_size_limit', function( $size ) {
    $user = wp_get_current_user();

    if ( in_array( 'author', $user->roles ) ) {
        return 2 * MB_IN_BYTES; // 2MB cho Author
    }
    if ( in_array( 'editor', $user->roles ) ) {
        return 10 * MB_IN_BYTES; // 10MB cho Editor
    }

    return $size; // Mặc định cho Admin
});

// Giới hạn MIME types
add_filter( 'upload_mimes', function( $mimes, $user ) {
    if ( ! $user ) return $mimes;

    $user_obj = get_userdata( $user );
    if ( $user_obj && in_array( 'author', $user_obj->roles ) ) {
        // Author chỉ upload ảnh
        return array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif'          => 'image/gif',
            'png'          => 'image/png',
            'webp'         => 'image/webp',
        );
    }

    return $mimes;
}, 10, 2 );

// Tự động đặt alt text từ filename
add_action( 'add_attachment', function( $attachment_id ) {
    $post = get_post( $attachment_id );
    if ( ! wp_attachment_is_image( $attachment_id ) ) return;

    $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
    if ( empty( $alt ) ) {
        // Chuyển filename thành alt text đẹp
        $title = $post->post_title;
        $title = str_replace( array( '-', '_' ), ' ', $title );
        $title = ucfirst( $title );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );
    }
});

// Tự động xóa file vật lý khi xóa attachment
// (WordPress đã làm mặc định, nhưng đây là ví dụ hook)
add_action( 'delete_attachment', function( $attachment_id ) {
    // Log việc xóa media
    $file = get_attached_file( $attachment_id );
    error_log( sprintf(
        'Media deleted: ID=%d, File=%s, User=%d',
        $attachment_id,
        $file,
        get_current_user_id()
    ) );
});
```

### Upload media bằng code (programmatic)

```php
// Upload từ URL
function upload_image_from_url( $url, $post_id = 0 ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Download file về temp
    $tmp = download_url( $url );
    if ( is_wp_error( $tmp ) ) {
        return $tmp;
    }

    $file_array = array(
        'name'     => wp_basename( $url ),
        'tmp_name' => $tmp,
    );

    // Upload và tạo attachment
    $attachment_id = media_handle_sideload( $file_array, $post_id );

    if ( is_wp_error( $attachment_id ) ) {
        @unlink( $tmp );
        return $attachment_id;
    }

    return $attachment_id;
}

// Sử dụng
$id = upload_image_from_url( 'https://example.com/photo.jpg', $post_id );
if ( ! is_wp_error( $id ) ) {
    set_post_thumbnail( $post_id, $id ); // Set featured image
}
```

---

## 17. So Sanh Voi Laravel

| Tính năng | WordPress | Laravel |
|-----------|-----------|---------|
| Upload file | `media_handle_upload()` | `$request->file('photo')->store('photos')` |
| Validate MIME | filter `upload_mimes` | `$request->validate(['photo' => 'mimes:jpg,png'])` |
| Validate size | filter `upload_size_limit` | `$request->validate(['photo' => 'max:2048'])` |
| Image resize | `wp_create_image_subsizes()` tự động | Intervention Image `$img->fit(150,150)` thủ công |
| Storage path | `wp-content/uploads/YYYY/MM/` | `storage/app/public/` |
| CDN | filter `wp_get_attachment_url` | `Storage::disk('s3')` |
| Metadata DB | `wp_postmeta` (serialized) | Migration tự tạo bảng |
| Media Library UI | Built-in Grid/List view | Tự build hoặc dùng Nova/Filament |
| Responsive images | `wp_get_attachment_image()` tự tạo srcset | Tự implement |
| File manager | Built-in | Spatie Media Library package |

### Workflow tương đương trong Laravel

```php
// Laravel: Upload + resize + lưu DB
class MediaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,png,webp|max:10240'
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/' . date('Y/m'), 'public');

        // Tạo thumbnails (Intervention Image)
        $image = Image::make($file);

        $sizes = [
            'thumbnail'    => [150, 150, true],
            'medium'       => [300, 300, false],
            'large'        => [1024, 1024, false],
        ];

        foreach ($sizes as $name => [$w, $h, $crop]) {
            $resized = clone $image;
            if ($crop) {
                $resized->fit($w, $h);
            } else {
                $resized->resize($w, $h, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                });
            }
            $resized->save(storage_path('app/public/uploads/' . date('Y/m') . '/' . $name . '-' . $file->hashName()));
        }

        // Lưu DB
        $media = Media::create([
            'path'      => $path,
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'metadata'  => json_encode($this->extractExif($file)),
        ]);

        return response()->json($media);
    }
}
```

---

## 18. Tong Ket

### Các điểm quan trọng cần nhớ

1. **Media = Attachment post type**: Mọi file upload đều là post trong `wp_posts` với `post_type = 'attachment'`.

2. **Upload flow**: `media_handle_upload()` -> `wp_handle_upload()` -> `wp_insert_attachment()` -> `wp_generate_attachment_metadata()`.

3. **Thumbnails tự động**: WordPress tự tạo tất cả registered image sizes khi upload. Không cần xử lý thủ công.

4. **Big Image Threshold**: Ảnh > 2560px tự động scale down (từ WP 5.3).

5. **Metadata trong postmeta**: `_wp_attached_file`, `_wp_attachment_metadata`, `_wp_attachment_image_alt`.

6. **Hooks quan trọng nhất**:
   - `wp_handle_upload_prefilter` - validate trước upload
   - `wp_generate_attachment_metadata` - xử lý sau upload
   - `upload_mimes` - cho phép MIME types
   - `wp_get_attachment_url` - filter URL (CDN, v.v.)
   - `intermediate_image_sizes_advanced` - control sizes được tạo

7. **Srcset tự động**: `wp_get_attachment_image()` tự tạo responsive srcset attributes.

8. **Programmatic upload**: Dùng `media_handle_sideload()` để upload từ URL hoặc file ngoài.

---

> **Tiếp theo**: [05 - Quản Lý Bình Luận](./05-quan-ly-binh-luan.md)
