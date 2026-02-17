# Filter Hooks Quan Trọng Trong WordPress

## Mục Lục

1. [Giới thiệu](#1-giới-thiệu)
2. [Content Filters](#2-content-filters)
3. [Query Filters](#3-query-filters)
4. [Admin Filters](#4-admin-filters)
5. [Login/Auth Filters](#5-loginauth-filters)
6. [Upload Filters](#6-upload-filters)
7. [Email Filters](#7-email-filters)
8. [Menu Filters](#8-menu-filters)
9. [Body Class Filter](#9-body-class-filter)
10. [Script/Style Filters](#10-scriptstyle-filters)
11. [Miscellaneous Filters](#11-miscellaneous-filters)
12. [Best Practices](#12-best-practices)

---

## 1. Giới thiệu

**Filter Hooks** cho phép bạn chặn và sửa đổi dữ liệu trước khi WordPress sử dụng hoặc hiển thị nó. Khác với Action Hooks (thực thi hành động), Filter Hooks **bắt buộc phải return giá trị**.

### Quy tắc vàng của Filter

```php
<?php
// 1. LUÔN return giá trị
add_filter( 'the_content', function( $content ) {
    // Xử lý...
    return $content; // BẮT BUỘC!
});

// 2. Tham số đầu tiên là giá trị cần filter
add_filter( 'the_title', function( $title, $post_id ) {
    // $title = giá trị cần biến đổi
    // $post_id = tham số bổ sung
    return $title;
}, 10, 2 );

// 3. Nếu không muốn thay đổi gì, return nguyên bản
add_filter( 'the_content', function( $content ) {
    if ( ! is_single() ) {
        return $content; // Không thay đổi gì
    }
    return $content . '<p>Thêm nội dung</p>'; // Chỉ thay đổi khi cần
});
```

### So sánh với Laravel Middleware

```
Laravel Middleware:
    Request → Middleware1 → Middleware2 → Controller → Response
    Mỗi middleware nhận $request, xử lý, rồi return $next($request)

WordPress Filter:
    Giá trị → Filter1 → Filter2 → Filter3 → Giá trị cuối cùng
    Mỗi filter nhận giá trị, xử lý, rồi return giá trị đã sửa
```

---

## 2. Content Filters

### 2.1 the_content

```
Khi nào chạy : Khi hiển thị nội dung bài viết (the_content() hoặc get_the_content())
Tham số      : $content (string) - nội dung HTML của bài viết
Return       : string - nội dung đã sửa
Dùng để      : Thêm nội dung trước/sau bài viết, sửa đổi HTML, thêm ads
```

```php
<?php
// Ví dụ 1: Thêm banner quảng cáo sau đoạn văn thứ 3
add_filter( 'the_content', 'my_insert_ad_after_paragraph', 10, 1 );
function my_insert_ad_after_paragraph( $content ) {
    // Chỉ áp dụng cho single post ở frontend
    if ( ! is_single() || is_admin() ) {
        return $content;
    }

    // HTML quảng cáo
    $ad_code = '<div class="in-content-ad" style="background:#f9f9f9; padding:15px; margin:20px 0; border:1px solid #ddd; text-align:center;">';
    $ad_code .= '<p style="margin:0; color:#999; font-size:12px;">Quảng cáo</p>';
    $ad_code .= '<p><a href="https://example.com">Sản phẩm XYZ - Giảm 50%</a></p>';
    $ad_code .= '</div>';

    // Tách nội dung theo thẻ </p>
    $paragraphs = explode( '</p>', $content );

    // Chèn ad sau đoạn văn thứ 3 (index 2)
    $insert_after = 2; // Sau đoạn thứ 3 (0-indexed)

    if ( count( $paragraphs ) > $insert_after ) {
        // Chèn ad code vào vị trí
        $paragraphs[ $insert_after ] .= $ad_code;
    }

    return implode( '</p>', $paragraphs );
}

// Ví dụ 2: Tự động convert URL YouTube thành embed
add_filter( 'the_content', 'my_auto_embed_youtube', 15 );
function my_auto_embed_youtube( $content ) {
    // Tìm URL YouTube đứng riêng trên 1 dòng (không phải trong thẻ a hoặc iframe)
    $pattern = '/(?<!["\'>])(https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+))(?!["\'])/i';

    $content = preg_replace_callback( $pattern, function( $matches ) {
        $video_id = $matches[2];
        return sprintf(
            '<div class="video-embed" style="position:relative; padding-bottom:56.25%%; height:0; overflow:hidden; margin:20px 0;">' .
            '<iframe src="https://www.youtube.com/embed/%s" style="position:absolute; top:0; left:0; width:100%%; height:100%%;" ' .
            'frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
            esc_attr( $video_id )
        );
    }, $content );

    return $content;
}

// Ví dụ 3: Thêm thời gian đọc ước tính
add_filter( 'the_content', 'my_add_reading_time', 5 );
function my_add_reading_time( $content ) {
    if ( ! is_single() ) {
        return $content;
    }

    // Tính thời gian đọc (trung bình 200 từ/phút cho tiếng Việt)
    $word_count   = str_word_count( wp_strip_all_tags( $content ) );
    $reading_time = max( 1, ceil( $word_count / 200 ) );

    $reading_badge = sprintf(
        '<div class="reading-time" style="background:#e8f4fd; padding:10px 15px; border-radius:5px; margin-bottom:20px; color:#0073aa;">' .
        'Thời gian đọc: khoảng %d phút | %s từ' .
        '</div>',
        $reading_time,
        number_format( $word_count )
    );

    return $reading_badge . $content;
}
```

### 2.2 the_title

```
Khi nào chạy : Khi hiển thị tiêu đề bài viết
Tham số      : $title (string), $post_id (int)
Return       : string
Dùng để      : Sửa đổi tiêu đề hiển thị (không ảnh hưởng database)
```

```php
<?php
// Thêm label cho bài viết đặc biệt
add_filter( 'the_title', 'my_custom_title_labels', 10, 2 );
function my_custom_title_labels( $title, $post_id = 0 ) {
    // Không sửa đổi trong admin
    if ( is_admin() ) {
        return $title;
    }

    // Thêm "[HOT]" cho sticky posts
    if ( is_sticky( $post_id ) ) {
        $title = '<span style="color:red; font-weight:bold;">[HOT]</span> ' . $title;
    }

    // Thêm "[Cập nhật]" cho bài viết đã sửa trong 7 ngày qua
    $post = get_post( $post_id );
    if ( $post ) {
        $modified_time = strtotime( $post->post_modified );
        $seven_days    = time() - ( 7 * DAY_IN_SECONDS );

        if ( $modified_time > $seven_days && $post->post_date !== $post->post_modified ) {
            $title .= ' <span style="color:green; font-size:0.8em;">[Cập nhật]</span>';
        }
    }

    return $title;
}
```

### 2.3 the_excerpt

```
Khi nào chạy : Khi hiển thị đoạn trích của bài viết
Tham số      : $excerpt (string)
Return       : string
Dùng để      : Tùy chỉnh đoạn trích
```

```php
<?php
// Custom excerpt cho từng post type
add_filter( 'the_excerpt', 'my_custom_excerpt' );
function my_custom_excerpt( $excerpt ) {
    if ( is_admin() ) {
        return $excerpt;
    }

    $post_type = get_post_type();

    switch ( $post_type ) {
        case 'product':
            // Sản phẩm: thêm giá vào excerpt
            $price = get_post_meta( get_the_ID(), '_product_price', true );
            if ( $price ) {
                $excerpt .= sprintf(
                    '<p class="product-price"><strong>Giá: %s VNĐ</strong></p>',
                    number_format( floatval( $price ) )
                );
            }
            break;

        case 'event':
            // Sự kiện: thêm ngày tổ chức
            $event_date = get_post_meta( get_the_ID(), '_event_date', true );
            if ( $event_date ) {
                $excerpt .= sprintf(
                    '<p class="event-date">Ngày tổ chức: <strong>%s</strong></p>',
                    esc_html( date_i18n( 'd/m/Y H:i', strtotime( $event_date ) ) )
                );
            }
            break;
    }

    return $excerpt;
}

// Thay đổi độ dài và ký tự kết thúc excerpt
add_filter( 'excerpt_length', function( $length ) {
    return 25; // 25 từ thay vì mặc định 55
});

add_filter( 'excerpt_more', function( $more ) {
    return '... <a href="' . esc_url( get_permalink() ) . '" class="read-more">Xem thêm</a>';
});
```

### 2.4 the_permalink

```
Khi nào chạy : Khi lấy permalink của bài viết
Tham số      : $permalink (string), $post (WP_Post), $leavename (bool)
Return       : string
Dùng để      : Sửa đổi URL bài viết
```

```php
<?php
// Thêm UTM parameters cho external links
add_filter( 'the_permalink', 'my_add_tracking_to_permalink' );
function my_add_tracking_to_permalink( $permalink ) {
    // Chỉ áp dụng khi gửi email (newsletter)
    if ( ! defined( 'MY_SENDING_NEWSLETTER' ) ) {
        return $permalink;
    }

    return add_query_arg( array(
        'utm_source'   => 'newsletter',
        'utm_medium'   => 'email',
        'utm_campaign' => 'weekly-digest',
    ), $permalink );
}
```

---

## 3. Query Filters

### 3.1 pre_get_posts

```
Khi nào chạy : Trước khi WordPress chạy query lấy bài viết
Tham số      : $query (WP_Query object - passed by reference)
Return       : Không cần return (modify trực tiếp $query)
Dùng để      : Thay đổi query: số bài, post type, sắp xếp, lọc
```

```php
<?php
// FILTER MẠNH NHẤT cho queries
add_action( 'pre_get_posts', 'my_customize_queries' );
function my_customize_queries( $query ) {
    // QUAN TRỌNG: Luôn kiểm tra 2 điều kiện này
    // 1. Chỉ modify main query (không phải custom WP_Query)
    // 2. Chỉ ở frontend (không phải admin)
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // === Trang chủ: Hiển thị 8 bài, trộn posts và products ===
    if ( $query->is_home() ) {
        $query->set( 'posts_per_page', 8 );
        $query->set( 'post_type', array( 'post', 'product' ) );
    }

    // === Trang tìm kiếm: Chỉ tìm trong posts và pages ===
    if ( $query->is_search() ) {
        $query->set( 'post_type', array( 'post', 'page' ) );

        // Ưu tiên bài viết mới
        $query->set( 'orderby', 'relevance date' );
    }

    // === Archive theo category: 12 bài mỗi trang ===
    if ( $query->is_category() ) {
        $query->set( 'posts_per_page', 12 );
    }

    // === Archive theo tag: Sắp xếp A-Z ===
    if ( $query->is_tag() ) {
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }

    // === Archive cho custom post type 'product' ===
    if ( $query->is_post_type_archive( 'product' ) ) {
        $query->set( 'posts_per_page', 16 );

        // Lọc theo giá nếu có tham số URL: ?min_price=100000&max_price=500000
        $min_price = isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : 0;
        $max_price = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : 0;

        if ( $min_price > 0 || $max_price > 0 ) {
            $meta_query = array();

            if ( $min_price > 0 ) {
                $meta_query[] = array(
                    'key'     => '_product_price',
                    'value'   => $min_price,
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                );
            }

            if ( $max_price > 0 ) {
                $meta_query[] = array(
                    'key'     => '_product_price',
                    'value'   => $max_price,
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                );
            }

            if ( count( $meta_query ) > 1 ) {
                $meta_query['relation'] = 'AND';
            }

            $query->set( 'meta_query', $meta_query );
        }

        // Sắp xếp theo giá nếu có tham số: ?sort=price_asc
        $sort = sanitize_text_field( $_GET['sort'] ?? '' );
        if ( 'price_asc' === $sort ) {
            $query->set( 'meta_key', '_product_price' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'ASC' );
        } elseif ( 'price_desc' === $sort ) {
            $query->set( 'meta_key', '_product_price' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'DESC' );
        }
    }

    // === Ẩn bài viết có meta '_hidden' = 1 ===
    if ( $query->is_archive() || $query->is_home() ) {
        $existing_meta = $query->get( 'meta_query' ) ?: array();
        $existing_meta[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_hidden',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_hidden',
                'value'   => '1',
                'compare' => '!=',
            ),
        );
        $query->set( 'meta_query', $existing_meta );
    }
}
```

### 3.2 posts_clauses

```
Khi nào chạy : Trước khi SQL query được thực thi
Tham số      : $clauses (array), $query (WP_Query)
Return       : array - các phần của SQL query
Dùng để      : Sửa đổi SQL trực tiếp (nâng cao)
```

```php
<?php
// posts_clauses cho phép sửa từng phần của SQL:
// - where, groupby, join, orderby, distinct, fields, limits

add_filter( 'posts_clauses', 'my_custom_sql_clauses', 10, 2 );
function my_custom_sql_clauses( $clauses, $query ) {
    global $wpdb;

    // Chỉ áp dụng cho query có custom parameter
    if ( ! $query->get( 'my_sort_by_comment_count' ) ) {
        return $clauses;
    }

    // JOIN với bảng comments để đếm số comment
    $clauses['join'] .= " LEFT JOIN (
        SELECT comment_post_ID, COUNT(*) as comment_total
        FROM {$wpdb->comments}
        WHERE comment_approved = '1'
        GROUP BY comment_post_ID
    ) AS comment_counts ON {$wpdb->posts}.ID = comment_counts.comment_post_ID";

    // Sắp xếp theo số comment giảm dần
    $clauses['orderby'] = 'COALESCE(comment_counts.comment_total, 0) DESC, ' . $clauses['orderby'];

    return $clauses;
}

// Sử dụng:
$popular_posts = new WP_Query( array(
    'post_type'                => 'post',
    'posts_per_page'           => 10,
    'my_sort_by_comment_count' => true,  // Custom parameter trigger filter
));
```

### 3.3 posts_where

```
Khi nào chạy : Khi xây dựng WHERE clause của SQL query
Tham số      : $where (string), $query (WP_Query)
Return       : string - WHERE clause đã sửa
Dùng để      : Thêm điều kiện lọc vào query
```

```php
<?php
// Tìm kiếm trong custom fields (mặc định WordPress chỉ tìm trong title + content)
add_filter( 'posts_where', 'my_search_custom_fields', 10, 2 );
function my_search_custom_fields( $where, $query ) {
    global $wpdb;

    if ( ! $query->is_search() || ! $query->is_main_query() || is_admin() ) {
        return $where;
    }

    $search_term = $query->get( 's' );
    if ( empty( $search_term ) ) {
        return $where;
    }

    // Thêm tìm kiếm trong meta_value
    $like = '%' . $wpdb->esc_like( $search_term ) . '%';

    $where = preg_replace(
        "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
        "({$wpdb->posts}.post_title LIKE $1) OR ({$wpdb->posts}.ID IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_value LIKE '{$like}'
        ))",
        $where
    );

    return $where;
}
```

### 3.4 posts_join

```
Khi nào chạy : Khi xây dựng JOIN clause
Tham số      : $join (string), $query (WP_Query)
Return       : string
Dùng để      : JOIN với bảng khác
```

```php
<?php
// Ví dụ: JOIN với bảng custom để sắp xếp theo view count
add_filter( 'posts_join', 'my_join_views_table', 10, 2 );
function my_join_views_table( $join, $query ) {
    global $wpdb;

    if ( $query->get( 'orderby' ) === 'view_count' ) {
        $join .= " LEFT JOIN {$wpdb->postmeta} AS view_meta
                   ON ({$wpdb->posts}.ID = view_meta.post_id AND view_meta.meta_key = '_view_count')";
    }

    return $join;
}

add_filter( 'posts_orderby', 'my_orderby_views', 10, 2 );
function my_orderby_views( $orderby, $query ) {
    if ( $query->get( 'orderby' ) === 'view_count' ) {
        $orderby = 'CAST(COALESCE(view_meta.meta_value, 0) AS UNSIGNED) DESC';
    }
    return $orderby;
}

// Sử dụng:
$popular = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'orderby'        => 'view_count',  // Custom orderby trigger filters
));
```

---

## 4. Admin Filters

### 4.1 manage_{post_type}_posts_columns

```
Khi nào chạy : Khi WordPress render header của bảng danh sách posts trong admin
Tham số      : $columns (array) - danh sách cột
Return       : array
Dùng để      : Thêm/bớt/sắp xếp cột trong admin list table
```

```php
<?php
// Thêm cột vào danh sách sản phẩm
add_filter( 'manage_product_posts_columns', 'my_product_columns' );
function my_product_columns( $columns ) {
    // $columns mặc định:
    // 'cb' => checkbox, 'title' => Tiêu đề, 'author' => Tác giả,
    // 'categories' => Chuyên mục, 'tags' => Thẻ, 'date' => Ngày

    // Tạo mảng mới để kiểm soát thứ tự
    $new_columns = array();
    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;

        // Chèn cột custom SAU cột 'title'
        if ( 'title' === $key ) {
            $new_columns['product_price']    = 'Giá';
            $new_columns['product_sku']      = 'Mã SKU';
            $new_columns['product_stock']    = 'Tồn kho';
            $new_columns['product_featured'] = 'Ảnh';
        }
    }

    // Xóa cột không cần
    unset( $new_columns['tags'] );

    return $new_columns;
}

// Render nội dung cho cột custom
add_action( 'manage_product_posts_custom_column', 'my_product_column_content', 10, 2 );
function my_product_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'product_price':
            $price = get_post_meta( $post_id, '_product_price', true );
            echo $price ? number_format( floatval( $price ) ) . ' VNĐ' : '<span style="color:#999;">—</span>';
            break;

        case 'product_sku':
            $sku = get_post_meta( $post_id, '_product_sku', true );
            echo $sku ? '<code>' . esc_html( $sku ) . '</code>' : '<span style="color:#999;">—</span>';
            break;

        case 'product_stock':
            $stock = get_post_meta( $post_id, '_product_stock', true );
            if ( '' === $stock ) {
                echo '<span style="color:#999;">—</span>';
            } elseif ( intval( $stock ) <= 0 ) {
                echo '<span style="color:red; font-weight:bold;">Hết hàng</span>';
            } elseif ( intval( $stock ) <= 5 ) {
                echo '<span style="color:orange;">Còn ' . intval( $stock ) . '</span>';
            } else {
                echo '<span style="color:green;">Còn ' . intval( $stock ) . '</span>';
            }
            break;

        case 'product_featured':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 50, 50 ), array(
                    'style' => 'border-radius:3px;',
                ));
            } else {
                echo '<span style="color:#999;">No image</span>';
            }
            break;
    }
}

// Cho phép sắp xếp theo cột custom
add_filter( 'manage_edit-product_sortable_columns', 'my_product_sortable_columns' );
function my_product_sortable_columns( $columns ) {
    $columns['product_price'] = 'product_price';
    $columns['product_stock'] = 'product_stock';
    return $columns;
}

// Xử lý sắp xếp
add_action( 'pre_get_posts', 'my_product_column_orderby' );
function my_product_column_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $orderby = $query->get( 'orderby' );

    if ( 'product_price' === $orderby ) {
        $query->set( 'meta_key', '_product_price' );
        $query->set( 'orderby', 'meta_value_num' );
    }

    if ( 'product_stock' === $orderby ) {
        $query->set( 'meta_key', '_product_stock' );
        $query->set( 'orderby', 'meta_value_num' );
    }
}
```

### 4.2 Thêm cột cho post type 'post' (bài viết mặc định)

```php
<?php
// Thêm cột "Lượt xem" và "Featured Image" cho bài viết
add_filter( 'manage_post_posts_columns', 'my_post_custom_columns' );
function my_post_custom_columns( $columns ) {
    // Chèn cột Featured Image trước Title
    $new_columns = array();
    foreach ( $columns as $key => $label ) {
        if ( 'title' === $key ) {
            $new_columns['featured_image'] = 'Ảnh';
        }
        $new_columns[ $key ] = $label;
    }

    // Thêm cột Views trước Date
    $new_columns['view_count'] = 'Lượt xem';

    return $new_columns;
}

add_action( 'manage_post_posts_custom_column', 'my_post_column_content', 10, 2 );
function my_post_column_content( $column, $post_id ) {
    if ( 'featured_image' === $column ) {
        if ( has_post_thumbnail( $post_id ) ) {
            echo get_the_post_thumbnail( $post_id, array( 60, 60 ) );
        }
    }

    if ( 'view_count' === $column ) {
        $views = get_post_meta( $post_id, '_view_count', true );
        echo $views ? number_format( intval( $views ) ) : '0';
    }
}
```

---

## 5. Login/Auth Filters

### 5.1 authenticate

```
Khi nào chạy : Khi user submit form đăng nhập
Tham số      : $user (WP_User|WP_Error|null), $username (string), $password (string)
Return       : WP_User (thành công) hoặc WP_Error (thất bại)
Dùng để      : Custom authentication, 2FA, rate limiting
```

```php
<?php
// Giới hạn số lần đăng nhập sai (Rate Limiting)
add_filter( 'authenticate', 'my_login_rate_limit', 30, 3 );
function my_login_rate_limit( $user, $username, $password ) {
    // Chỉ xử lý nếu có username và password
    if ( empty( $username ) || empty( $password ) ) {
        return $user;
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'login_attempts_' . md5( $ip );

    // Lấy số lần thử
    $attempts = get_transient( $transient_key );
    if ( false === $attempts ) {
        $attempts = 0;
    }

    // Nếu đã vượt quá 5 lần trong 15 phút
    $max_attempts = 5;
    if ( $attempts >= $max_attempts ) {
        return new WP_Error(
            'too_many_attempts',
            sprintf(
                '<strong>Quá nhiều lần thử đăng nhập!</strong> Vui lòng đợi 15 phút trước khi thử lại. ' .
                '(%d/%d lần)',
                $attempts,
                $max_attempts
            )
        );
    }

    // Nếu đăng nhập thất bại, tăng counter
    if ( is_wp_error( $user ) ) {
        $attempts++;
        set_transient( $transient_key, $attempts, 15 * MINUTE_IN_SECONDS );
    } else {
        // Đăng nhập thành công → reset counter
        delete_transient( $transient_key );
    }

    return $user;
}

// Cho phép đăng nhập bằng email (mặc định WordPress chỉ dùng username)
add_filter( 'authenticate', 'my_allow_email_login', 20, 3 );
function my_allow_email_login( $user, $username, $password ) {
    // Nếu đã authenticate thành công, không cần xử lý
    if ( $user instanceof WP_User ) {
        return $user;
    }

    // Kiểm tra xem username có phải email không
    if ( is_email( $username ) ) {
        $user_by_email = get_user_by( 'email', $username );
        if ( $user_by_email ) {
            // Thử authenticate lại với username thật
            return wp_authenticate_username_password( null, $user_by_email->user_login, $password );
        }
    }

    return $user;
}
```

### 5.2 login_redirect

```
Khi nào chạy : Sau khi đăng nhập thành công, trước khi redirect
Tham số      : $redirect_to (string), $requested_redirect_to (string), $user (WP_User|WP_Error)
Return       : string - URL redirect
Dùng để      : Custom redirect theo role
```

```php
<?php
add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );
function my_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    // Kiểm tra user hợp lệ
    if ( ! is_a( $user, 'WP_User' ) ) {
        return $redirect_to;
    }

    // Nếu có redirect_to cụ thể trong URL, ưu tiên nó
    if ( ! empty( $requested_redirect_to ) && $requested_redirect_to !== admin_url() ) {
        return $redirect_to;
    }

    // Redirect theo role
    if ( in_array( 'administrator', $user->roles, true ) ) {
        return admin_url( 'index.php' );      // Admin → Dashboard
    }

    if ( in_array( 'editor', $user->roles, true ) ) {
        return admin_url( 'edit.php' );        // Editor → Danh sách bài viết
    }

    if ( in_array( 'author', $user->roles, true ) ) {
        return admin_url( 'edit.php?post_type=post' ); // Author → Bài viết của mình
    }

    if ( in_array( 'subscriber', $user->roles, true ) ) {
        return home_url( '/tai-khoan/' );      // Subscriber → Trang tài khoản
    }

    if ( in_array( 'customer', $user->roles, true ) ) {
        return home_url( '/tai-khoan/don-hang/' ); // Customer → Đơn hàng
    }

    return $redirect_to; // Mặc định
}
```

### 5.3 logout_redirect

```
Khi nào chạy : WordPress 6.3+ - Sau khi đăng xuất, trước khi redirect
Tham số      : $redirect_to (string), $requested_redirect_to (string), $user (WP_User)
Return       : string
```

```php
<?php
add_filter( 'logout_redirect', 'my_logout_redirect', 10, 3 );
function my_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
    // Redirect về trang chủ thay vì trang login
    return home_url( '/?logged_out=1' );
}
```

---

## 6. Upload Filters

### 6.1 upload_mimes

```
Khi nào chạy : Khi kiểm tra loại file được phép upload
Tham số      : $mimes (array) - danh sách MIME types
Return       : array
Dùng để      : Thêm/bớt loại file được phép upload
```

```php
<?php
// Cho phép upload thêm các loại file
add_filter( 'upload_mimes', 'my_custom_upload_mimes', 10, 1 );
function my_custom_upload_mimes( $mimes ) {
    // Thêm SVG (cẩn thận - SVG có thể chứa mã độc!)
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';

    // Thêm WebP
    $mimes['webp'] = 'image/webp';

    // Thêm file font
    $mimes['woff']  = 'font/woff';
    $mimes['woff2'] = 'font/woff2';

    // Thêm JSON
    $mimes['json'] = 'application/json';

    // Xóa loại file không muốn cho phép
    unset( $mimes['exe'] );  // Không cho upload .exe

    return $mimes;
}

// Cho phép upload SVG chỉ cho admin (an toàn hơn)
add_filter( 'upload_mimes', 'my_svg_for_admin_only' );
function my_svg_for_admin_only( $mimes ) {
    if ( current_user_can( 'manage_options' ) ) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
}
```

### 6.2 wp_handle_upload

```
Khi nào chạy : Sau khi file được upload thành công
Tham số      : $upload (array) - chứa 'file', 'url', 'type'
Return       : array
Dùng để      : Xử lý file sau upload (resize, optimize, validate)
```

```php
<?php
add_filter( 'wp_handle_upload', 'my_after_upload_handler' );
function my_after_upload_handler( $upload ) {
    // $upload = array(
    //     'file' => '/path/to/uploads/2024/01/image.jpg',  // Đường dẫn file
    //     'url'  => 'https://example.com/wp-content/uploads/2024/01/image.jpg',
    //     'type' => 'image/jpeg',
    // )

    // Ghi log upload
    error_log( sprintf(
        '[Upload] File: %s | Type: %s | User: %d',
        basename( $upload['file'] ),
        $upload['type'],
        get_current_user_id()
    ));

    // Tự động optimize ảnh JPEG (giảm quality)
    if ( 'image/jpeg' === $upload['type'] ) {
        $image = imagecreatefromjpeg( $upload['file'] );
        if ( $image ) {
            // Lưu lại với quality 85% (giảm dung lượng ~30-50%)
            imagejpeg( $image, $upload['file'], 85 );
            imagedestroy( $image );
        }
    }

    return $upload;
}

// Giới hạn kích thước upload
add_filter( 'wp_handle_upload_prefilter', 'my_limit_upload_size' );
function my_limit_upload_size( $file ) {
    // $file = $_FILES array item

    // Giới hạn 2MB cho ảnh
    $max_size = 2 * 1024 * 1024; // 2MB in bytes

    if ( strpos( $file['type'], 'image/' ) === 0 && $file['size'] > $max_size ) {
        $file['error'] = sprintf(
            'Ảnh quá lớn! Kích thước tối đa: %s. File của bạn: %s.',
            size_format( $max_size ),
            size_format( $file['size'] )
        );
    }

    return $file;
}
```

---

## 7. Email Filters

### 7.1 wp_mail

```
Khi nào chạy : Trước khi gửi email qua wp_mail()
Tham số      : $args (array) - chứa 'to', 'subject', 'message', 'headers', 'attachments'
Return       : array
Dùng để      : Sửa đổi tất cả email gửi từ WordPress
```

```php
<?php
// Thêm header và footer cho tất cả email
add_filter( 'wp_mail', 'my_customize_all_emails' );
function my_customize_all_emails( $args ) {
    // Đổi sang HTML email
    $args['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );

    // Wrap nội dung trong template HTML
    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url();

    $html_message = '
    <div style="max-width:600px; margin:0 auto; font-family:Arial,sans-serif;">
        <!-- Header -->
        <div style="background:#0073aa; color:#fff; padding:20px; text-align:center;">
            <h2 style="margin:0;">' . esc_html( $site_name ) . '</h2>
        </div>

        <!-- Body -->
        <div style="padding:30px; background:#fff; border:1px solid #ddd;">
            ' . wpautop( $args['message'] ) . '
        </div>

        <!-- Footer -->
        <div style="padding:15px; text-align:center; color:#999; font-size:12px; background:#f5f5f5;">
            <p>Email được gửi từ <a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a></p>
            <p>Bạn nhận được email này vì đã đăng ký tại website của chúng tôi.</p>
        </div>
    </div>';

    $args['message'] = $html_message;

    return $args;
}
```

### 7.2 wp_mail_from và wp_mail_from_name

```
Khi nào chạy : Khi xác định địa chỉ và tên người gửi email
Tham số      : $from_email / $from_name (string)
Return       : string
Dùng để      : Thay đổi "From" trong email
```

```php
<?php
// Mặc định WordPress gửi từ: wordpress@yourdomain.com
// Đổi thành email và tên chuyên nghiệp hơn

add_filter( 'wp_mail_from', 'my_mail_from' );
function my_mail_from( $from_email ) {
    // Thay đổi email gửi
    return 'no-reply@example.com';
}

add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
function my_mail_from_name( $from_name ) {
    // Thay đổi tên hiển thị
    return get_bloginfo( 'name' );
}

// Hoặc gọn hơn với closure
add_filter( 'wp_mail_from', fn() => get_option( 'my_email_from', 'info@example.com' ) );
add_filter( 'wp_mail_from_name', fn() => get_option( 'my_email_from_name', get_bloginfo( 'name' ) ) );
```

### 7.3 wp_mail_content_type

```
Khi nào chạy : Khi xác định Content-Type của email
Tham số      : $content_type (string)
Return       : string
Dùng để      : Đổi email sang HTML
```

```php
<?php
// Đổi tất cả email sang HTML
add_filter( 'wp_mail_content_type', function( $content_type ) {
    return 'text/html';
});

// LƯU Ý: Nếu chỉ muốn đổi cho 1 email cụ thể, dùng filter tạm thời:
function my_send_html_email( $to, $subject, $html_body ) {
    // Thêm filter
    add_filter( 'wp_mail_content_type', $html_filter = function() {
        return 'text/html';
    });

    // Gửi email
    $result = wp_mail( $to, $subject, $html_body );

    // Gỡ filter ngay sau khi gửi (tránh ảnh hưởng email khác)
    remove_filter( 'wp_mail_content_type', $html_filter );

    return $result;
}
```

---

## 8. Menu Filters

### 8.1 wp_nav_menu_items

```
Khi nào chạy : Khi render HTML của navigation menu
Tham số      : $items (string - HTML), $args (object)
Return       : string
Dùng để      : Thêm items vào menu (login/logout link, search form)
```

```php
<?php
add_filter( 'wp_nav_menu_items', 'my_custom_menu_items', 10, 2 );
function my_custom_menu_items( $items, $args ) {
    // Chỉ sửa menu 'primary'
    if ( 'primary' !== $args->theme_location ) {
        return $items;
    }

    // Thêm nút Login/Logout vào cuối menu
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $items .= sprintf(
            '<li class="menu-item menu-item-user">
                <a href="%s">Xin chào, %s</a>
                <ul class="sub-menu">
                    <li><a href="%s">Tài khoản</a></li>
                    %s
                    <li><a href="%s">Đăng xuất</a></li>
                </ul>
            </li>',
            esc_url( admin_url( 'profile.php' ) ),
            esc_html( $current_user->display_name ),
            esc_url( home_url( '/tai-khoan/' ) ),
            current_user_can( 'manage_options' )
                ? '<li><a href="' . esc_url( admin_url() ) . '">Quản trị</a></li>'
                : '',
            esc_url( wp_logout_url( home_url() ) )
        );
    } else {
        $items .= sprintf(
            '<li class="menu-item menu-item-login">
                <a href="%s">Đăng nhập</a>
            </li>',
            esc_url( wp_login_url( get_permalink() ) ) // Redirect về trang hiện tại sau login
        );
    }

    // Thêm search form vào cuối menu
    $items .= '<li class="menu-item menu-item-search">';
    $items .= get_search_form( array( 'echo' => false ) );
    $items .= '</li>';

    return $items;
}
```

### 8.2 nav_menu_css_class

```
Khi nào chạy : Khi tạo CSS classes cho mỗi menu item
Tham số      : $classes (array), $menu_item (WP_Post), $args (stdClass), $depth (int)
Return       : array
Dùng để      : Thêm custom CSS classes cho menu items
```

```php
<?php
add_filter( 'nav_menu_css_class', 'my_menu_item_classes', 10, 4 );
function my_menu_item_classes( $classes, $menu_item, $args, $depth ) {
    // Thêm class theo depth (cấp)
    $classes[] = 'menu-depth-' . $depth;

    // Thêm class cho menu item có children
    if ( in_array( 'menu-item-has-children', $classes, true ) ) {
        $classes[] = 'has-dropdown';
    }

    // Thêm class cho current page
    if ( in_array( 'current-menu-item', $classes, true ) ) {
        $classes[] = 'is-active';
    }

    // Thêm class dựa trên object type
    if ( 'custom' === $menu_item->type ) {
        $classes[] = 'menu-item-custom-link';
    }

    // Thêm class dựa trên URL (highlight external links)
    if ( ! empty( $menu_item->url ) && strpos( $menu_item->url, home_url() ) === false ) {
        $classes[] = 'menu-item-external';
    }

    return $classes;
}
```

---

## 9. Body Class Filter

### body_class

```
Khi nào chạy : Khi tạo CSS classes cho thẻ <body>
Tham số      : $classes (array)
Return       : array
Dùng để      : Thêm custom classes cho styling
```

```php
<?php
add_filter( 'body_class', 'my_custom_body_classes' );
function my_custom_body_classes( $classes ) {
    // Thêm class theo role user
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $classes[] = 'logged-in-as-' . $user->roles[0]; // vd: logged-in-as-administrator
    } else {
        $classes[] = 'not-logged-in';
    }

    // Thêm class theo thời gian (sáng/tối)
    $hour = intval( current_time( 'G' ) );
    if ( $hour >= 6 && $hour < 18 ) {
        $classes[] = 'time-day';
    } else {
        $classes[] = 'time-night';
    }

    // Thêm class theo thiết bị (dựa trên User Agent)
    $ua = strtolower( $_SERVER['HTTP_USER_AGENT'] ?? '' );
    if ( strpos( $ua, 'mobile' ) !== false ) {
        $classes[] = 'is-mobile';
    } elseif ( strpos( $ua, 'tablet' ) !== false ) {
        $classes[] = 'is-tablet';
    } else {
        $classes[] = 'is-desktop';
    }

    // Thêm class cho page template
    if ( is_page_template() ) {
        $template = get_page_template_slug();
        $template = str_replace( array( '.php', '/' ), array( '', '-' ), $template );
        $classes[] = 'template-' . sanitize_html_class( $template );
    }

    // Thêm slug của page làm class
    if ( is_singular() ) {
        global $post;
        $classes[] = 'slug-' . $post->post_name;
    }

    // Thêm browser name
    if ( strpos( $ua, 'firefox' ) !== false ) {
        $classes[] = 'browser-firefox';
    } elseif ( strpos( $ua, 'chrome' ) !== false ) {
        $classes[] = 'browser-chrome';
    } elseif ( strpos( $ua, 'safari' ) !== false ) {
        $classes[] = 'browser-safari';
    }

    return $classes;
}
```

**Sử dụng trong CSS:**
```css
/* Chỉ hiện cho admin */
.logged-in-as-administrator .admin-only-section {
    display: block;
}

/* Dark mode ban đêm */
.time-night body {
    background: #1a1a1a;
    color: #e0e0e0;
}

/* Responsive theo class */
.is-mobile .sidebar {
    display: none;
}
```

---

## 10. Script/Style Filters

### 10.1 script_loader_tag

```
Khi nào chạy : Khi render thẻ <script> cho mỗi enqueued script
Tham số      : $tag (string - HTML), $handle (string), $src (string)
Return       : string
Dùng để      : Thêm attributes (async, defer, type="module", data-*)
```

```php
<?php
add_filter( 'script_loader_tag', 'my_script_attributes', 10, 3 );
function my_script_attributes( $tag, $handle, $src ) {
    // Thêm async cho Google Analytics
    if ( 'google-analytics' === $handle ) {
        $tag = str_replace( ' src', ' async src', $tag );
    }

    // Thêm defer cho scripts không critical
    $defer_scripts = array( 'mytheme-main', 'comment-reply', 'social-share' );
    if ( in_array( $handle, $defer_scripts, true ) ) {
        $tag = str_replace( ' src', ' defer src', $tag );
    }

    // Thêm type="module" cho ES Module scripts
    if ( 'mytheme-app' === $handle ) {
        $tag = str_replace( '<script ', '<script type="module" ', $tag );
    }

    // Thêm crossorigin cho CDN scripts
    if ( strpos( $src, 'cdn.' ) !== false || strpos( $src, 'cdnjs.' ) !== false ) {
        $tag = str_replace( ' src', ' crossorigin="anonymous" src', $tag );
    }

    // Thêm nonce cho CSP (Content Security Policy)
    $csp_nonce = defined( 'MY_CSP_NONCE' ) ? MY_CSP_NONCE : '';
    if ( $csp_nonce ) {
        $tag = str_replace( '<script ', '<script nonce="' . esc_attr( $csp_nonce ) . '" ', $tag );
    }

    return $tag;
}
```

### 10.2 style_loader_tag

```
Khi nào chạy : Khi render thẻ <link> cho mỗi enqueued stylesheet
Tham số      : $tag (string - HTML), $handle (string), $href (string), $media (string)
Return       : string
Dùng để      : Thêm attributes, preload, lazy load CSS
```

```php
<?php
add_filter( 'style_loader_tag', 'my_style_attributes', 10, 4 );
function my_style_attributes( $tag, $handle, $href, $media ) {
    // Preload CSS không critical (load async)
    $preload_styles = array( 'mytheme-print', 'mytheme-animations' );
    if ( in_array( $handle, $preload_styles, true ) ) {
        // Kỹ thuật: load CSS async bằng preload + onload
        $tag = sprintf(
            '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n" .
            '<noscript><link rel="stylesheet" href="%s"></noscript>' . "\n",
            esc_url( $href ),
            esc_url( $href )
        );
    }

    // Thêm integrity hash cho CDN stylesheets
    if ( 'bootstrap-css' === $handle ) {
        $tag = str_replace(
            '/>',
            'integrity="sha384-xxx" crossorigin="anonymous" />',
            $tag
        );
    }

    return $tag;
}
```

---

## 11. Miscellaneous Filters

### 11.1 document_title_parts

```
Khi nào chạy : Khi tạo thẻ <title>
Tham số      : $title_parts (array) - chứa 'title', 'page', 'tagline', 'site'
Return       : array
```

```php
<?php
add_filter( 'document_title_parts', 'my_custom_title_parts' );
function my_custom_title_parts( $title_parts ) {
    // Thêm năm hiện tại vào title cho SEO
    if ( is_front_page() ) {
        $title_parts['title'] = $title_parts['title'] . ' ' . date( 'Y' );
    }

    // Custom title cho 404 page
    if ( is_404() ) {
        $title_parts['title'] = 'Trang không tìm thấy (404)';
    }

    // Đổi separator
    // Mặc định: "Tiêu đề bài - Tên site"
    // Thay đổi phần 'site' nếu cần
    return $title_parts;
}

// Đổi separator (mặc định là '-')
add_filter( 'document_title_separator', function( $sep ) {
    return '|'; // "Tiêu đề | Tên Site"
});
```

### 11.2 wp_kses_allowed_html

```
Khi nào chạy : Khi wp_kses() lọc HTML
Tham số      : $allowed_html (array), $context (string)
Return       : array
Dùng để      : Cho phép thêm HTML tags/attributes
```

```php
<?php
add_filter( 'wp_kses_allowed_html', 'my_allowed_html', 10, 2 );
function my_allowed_html( $allowed_html, $context ) {
    if ( 'post' !== $context ) {
        return $allowed_html;
    }

    // Cho phép iframe (cho YouTube embeds)
    $allowed_html['iframe'] = array(
        'src'             => true,
        'width'           => true,
        'height'          => true,
        'frameborder'     => true,
        'allowfullscreen' => true,
        'loading'         => true,
        'style'           => true,
    );

    // Cho phép data attributes cho div
    if ( isset( $allowed_html['div'] ) ) {
        $allowed_html['div']['data-*'] = true;
    }

    return $allowed_html;
}
```

### 11.3 cron_schedules

```
Khi nào chạy : Khi WordPress lấy danh sách cron intervals
Tham số      : $schedules (array)
Return       : array
```

```php
<?php
add_filter( 'cron_schedules', 'my_custom_cron_schedules' );
function my_custom_cron_schedules( $schedules ) {
    // WordPress mặc định: hourly, twicedaily, daily, weekly

    $schedules['every_5_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display'  => 'Mỗi 5 phút',
    );

    $schedules['every_15_minutes'] = array(
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => 'Mỗi 15 phút',
    );

    $schedules['monthly'] = array(
        'interval' => 30 * DAY_IN_SECONDS,
        'display'  => 'Hàng tháng',
    );

    return $schedules;
}
```

### 11.4 template_include

```
Khi nào chạy : Khi WordPress chọn template file để render
Tham số      : $template (string - đường dẫn file)
Return       : string
Dùng để      : Override template loading logic
```

```php
<?php
add_filter( 'template_include', 'my_custom_templates' );
function my_custom_templates( $template ) {
    // Custom template cho post type 'portfolio'
    if ( is_singular( 'portfolio' ) ) {
        $custom = locate_template( 'single-portfolio.php' );
        if ( $custom ) {
            return $custom;
        }
        // Fallback: dùng template từ plugin
        $plugin_template = plugin_dir_path( __FILE__ ) . 'templates/single-portfolio.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    // Custom template cho taxonomy 'project_type'
    if ( is_tax( 'project_type' ) ) {
        $custom = locate_template( 'taxonomy-project_type.php' );
        if ( $custom ) {
            return $custom;
        }
    }

    // Landing page template cho specific page
    if ( is_page( 'landing' ) ) {
        $landing = locate_template( 'page-landing.php' );
        if ( $landing ) {
            return $landing;
        }
    }

    return $template;
}
```

---

## 12. Best Practices

### 1. Luôn return giá trị trong Filter

```php
<?php
// SAI - Quên return
add_filter( 'the_content', function( $content ) {
    $content .= '<p>Thêm nội dung</p>';
    // BUG: Không return → nội dung biến mất!
});

// ĐÚNG
add_filter( 'the_content', function( $content ) {
    $content .= '<p>Thêm nội dung</p>';
    return $content;
});
```

### 2. Kiểm tra context trước khi filter

```php
<?php
add_filter( 'the_content', function( $content ) {
    // Chỉ filter ở frontend
    if ( is_admin() ) {
        return $content;
    }

    // Chỉ filter single post
    if ( ! is_single() ) {
        return $content;
    }

    // Chỉ filter post type cụ thể
    if ( 'post' !== get_post_type() ) {
        return $content;
    }

    // OK, filter ở đây
    return $content . '<p>Nội dung thêm</p>';
});
```

### 3. Tránh vòng lặp vô hạn

```php
<?php
// SAI - Gọi the_content() trong filter the_content → vòng lặp!
add_filter( 'the_content', function( $content ) {
    // get_the_content() triggers the_content filter → infinite loop!
    $related = get_posts( array( 'numberposts' => 3 ) );
    foreach ( $related as $post ) {
        setup_postdata( $post );
        $content .= get_the_content(); // NGUY HIỂM: Recursive filter!
    }
    wp_reset_postdata();
    return $content;
});

// ĐÚNG - Gỡ filter trước khi gọi
add_filter( 'the_content', 'my_add_related_posts' );
function my_add_related_posts( $content ) {
    // Gỡ filter tạm thời để tránh recursion
    remove_filter( 'the_content', 'my_add_related_posts' );

    $related = get_posts( array( 'numberposts' => 3 ) );
    $related_html = '<h3>Bài viết liên quan</h3><ul>';
    foreach ( $related as $post ) {
        $related_html .= '<li><a href="' . get_permalink( $post ) . '">' . esc_html( $post->post_title ) . '</a></li>';
    }
    $related_html .= '</ul>';

    // Thêm filter lại
    add_filter( 'the_content', 'my_add_related_posts' );

    return $content . $related_html;
}
```

### 4. Không filter trong admin khi không cần

```php
<?php
// Nhiều filter chỉ nên áp dụng ở frontend
// Filter ở admin có thể làm hỏng Gutenberg editor
add_filter( 'the_content', function( $content ) {
    if ( is_admin() ) {
        return $content; // Bỏ qua trong admin
    }
    // Filter logic ở đây
    return $content;
});
```

### 5. Dùng đúng priority

```php
<?php
// Priority thấp (1-5): Filter chạy đầu tiên
// Ví dụ: Thêm nội dung TRƯỚC khi plugin khác xử lý
add_filter( 'the_content', 'my_early_filter', 1 );

// Priority mặc định (10): Phù hợp hầu hết trường hợp
add_filter( 'the_content', 'my_normal_filter' );

// Priority cao (99+): Filter chạy cuối cùng
// Ví dụ: Đảm bảo nội dung cuối cùng đúng format
add_filter( 'the_content', 'my_late_filter', 99 );

// PHP_INT_MAX: Chạy cuối cùng tuyệt đối
add_filter( 'the_content', 'my_final_filter', PHP_INT_MAX );
```

### Bảng tham chiếu nhanh

| Filter | Mục đích | Return Type |
|--------|----------|-------------|
| `the_content` | Nội dung bài viết | string (HTML) |
| `the_title` | Tiêu đề bài viết | string |
| `the_excerpt` | Đoạn trích | string |
| `pre_get_posts` | Thay đổi query | void (modify by ref) |
| `manage_{type}_posts_columns` | Cột admin list | array |
| `authenticate` | Xác thực đăng nhập | WP_User / WP_Error |
| `login_redirect` | Redirect sau login | string (URL) |
| `upload_mimes` | File types cho phép | array |
| `wp_mail` | Sửa email trước khi gửi | array |
| `wp_nav_menu_items` | HTML menu items | string (HTML) |
| `body_class` | CSS classes cho body | array |
| `script_loader_tag` | Thẻ script HTML | string (HTML) |
| `style_loader_tag` | Thẻ link CSS HTML | string (HTML) |
| `template_include` | Template file path | string (path) |

---

> **Tiếp theo:** [04 - Hooks Lifecycle](04-hooks-lifecycle.md) - Thứ tự thực thi hooks trong WordPress.
