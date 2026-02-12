# WordPress Hooks - Hướng Dẫn Toàn Diện

## Mục Lục
1. [Giới Thiệu](#giới-thiệu)
2. [Action Hooks](#action-hooks)
3. [Filter Hooks](#filter-hooks)
4. [Sự Khác Biệt Giữa Actions và Filters](#sự-khác-biệt-giữa-actions-và-filters)
5. [Cách Sử Dụng Hooks](#cách-sử-dụng-hooks)
6. [Priority (Độ Ưu Tiên)](#priority-độ-ưu-tiên)
7. [Các Hooks Quan Trọng Trong WordPress](#các-hooks-quan-trọng-trong-wordpress)
8. [Tạo Custom Hooks](#tạo-custom-hooks)
9. [Best Practices](#best-practices)
10. [Ví Dụ Thực Tế](#ví-dụ-thực-tế)

---

## Giới Thiệu

**WordPress Hooks** là cơ chế cho phép plugins và themes "hook vào" (móc vào) các điểm cụ thể trong quá trình xử lý của WordPress để thực thi code tùy chỉnh mà không cần sửa đổi core WordPress.

### Tại Sao Cần Hooks?

- ✅ **Không sửa core**: Giữ WordPress core nguyên vẹn
- ✅ **Dễ maintain**: Code tách biệt, dễ quản lý
- ✅ **Tương thích**: Plugins/themes không conflict với nhau
- ✅ **Linh hoạt**: Có thể thêm/xóa functionality dễ dàng

### Hai Loại Hooks Chính:

1. **Action Hooks**: Thực thi code tại một điểm cụ thể
2. **Filter Hooks**: Thay đổi/modify dữ liệu trước khi sử dụng

---

## Action Hooks

### Khái Niệm

**Action Hooks** cho phép bạn thực thi code tại một điểm cụ thể trong quá trình xử lý của WordPress. Actions không trả về giá trị, chỉ thực thi code.

### Cú Pháp

```php
// Đăng ký action
add_action( $hook_name, $callback, $priority, $accepted_args );

// Fire action
do_action( $hook_name, ...$arg );
```

### Ví Dụ Cơ Bản

```php
// Đăng ký function để chạy khi WordPress init
function my_custom_function() {
    echo 'WordPress đã được khởi tạo!';
}
add_action( 'init', 'my_custom_function' );
```

### Các Hàm Quan Trọng

#### 1. `add_action()`

```121:131:wp-includes/plugin.php
function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		$wp_filter[ $hook_name ] = new WP_Hook();
	}

	$wp_filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, $accepted_args );

	return true;
}
```

**Tham số:**
- `$hook_name` (string): Tên của hook
- `$callback` (callable): Function hoặc method cần gọi
- `$priority` (int): Độ ưu tiên (mặc định: 10)
- `$accepted_args` (int): Số tham số function nhận (mặc định: 1)

#### 2. `do_action()`

```482:520:wp-includes/plugin.php
function do_action( $hook_name, ...$arg ) {
	global $wp_filter, $wp_actions, $wp_current_filter;

	if ( ! isset( $wp_actions[ $hook_name ] ) ) {
		$wp_actions[ $hook_name ] = 1;
	} else {
		++$wp_actions[ $hook_name ];
	}

	// Do 'all' actions first.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
		$all_args            = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		_wp_call_all_hook( $all_args );
	}

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		if ( isset( $wp_filter['all'] ) ) {
			array_pop( $wp_current_filter );
		}

		return;
	}

	if ( ! isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
	}

	if ( empty( $arg ) ) {
		$arg[] = '';
	} elseif ( is_array( $arg[0] ) && 1 === count( $arg[0] ) && isset( $arg[0][0] ) && is_object( $arg[0][0] ) ) {
		// Backward compatibility for PHP4-style passing of `array( &$this )` as action `$arg`.
		$arg[0] = $arg[0][0];
	}

	$wp_filter[ $hook_name ]->do_action( $arg );

	array_pop( $wp_current_filter );
}
```

#### 3. `remove_action()`

```php
remove_action( $hook_name, $callback, $priority );
```

#### 4. `has_action()`

```php
// Kiểm tra xem hook có được đăng ký chưa
if ( has_action( 'init' ) ) {
    // Hook đã được đăng ký
}
```

### Ví Dụ Với Tham Số

```php
// Function nhận tham số
function my_custom_action( $post_id, $post ) {
    echo "Post ID: $post_id";
    echo "Post Title: " . $post->post_title;
}

// Đăng ký với 2 tham số
add_action( 'save_post', 'my_custom_action', 10, 2 );
```

---

## Filter Hooks

### Khái Niệm

**Filter Hooks** cho phép bạn thay đổi/modify dữ liệu trước khi WordPress sử dụng. Filters **PHẢI** trả về giá trị.

### Cú Pháp

```php
// Đăng ký filter
add_filter( $hook_name, $callback, $priority, $accepted_args );

// Apply filter
$value = apply_filters( $hook_name, $value, ...$args );
```

### Ví Dụ Cơ Bản

```php
// Thay đổi excerpt length
function custom_excerpt_length( $length ) {
    return 30; // Thay đổi từ 55 (mặc định) thành 30
}
add_filter( 'excerpt_length', 'custom_excerpt_length' );
```

### Các Hàm Quan Trọng

#### 1. `add_filter()`

```121:131:wp-includes/plugin.php
function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		$wp_filter[ $hook_name ] = new WP_Hook();
	}

	$wp_filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, $accepted_args );

	return true;
}
```

#### 2. `apply_filters()`

```173:210:wp-includes/plugin.php
function apply_filters( $hook_name, $value, ...$args ) {
	global $wp_filter, $wp_filters, $wp_current_filter;

	if ( ! isset( $wp_filters[ $hook_name ] ) ) {
		$wp_filters[ $hook_name ] = 1;
	} else {
		++$wp_filters[ $hook_name ];
	}

	// Do 'all' actions first.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;

		$all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		_wp_call_all_hook( $all_args );
	}

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		if ( isset( $wp_filter['all'] ) ) {
			array_pop( $wp_current_filter );
		}

		return $value;
	}

	if ( ! isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
	}

	// Pass the value to WP_Hook.
	array_unshift( $args, $value );

	$filtered = $wp_filter[ $hook_name ]->apply_filters( $value, $args );

	array_pop( $wp_current_filter );

	return $filtered;
}
```

#### 3. `remove_filter()`

```php
remove_filter( $hook_name, $callback, $priority );
```

#### 4. `has_filter()`

```php
// Kiểm tra xem filter có được đăng ký chưa
if ( has_filter( 'the_content' ) ) {
    // Filter đã được đăng ký
}
```

### Ví Dụ Với Nhiều Tham Số

```php
// Filter nhận nhiều tham số
function custom_post_link( $permalink, $post, $leavename ) {
    // Modify permalink
    return $permalink;
}
add_filter( 'post_link', 'custom_post_link', 10, 3 );
```

### Ví Dụ Từ WordPress Core

WordPress sử dụng filters rất nhiều để sanitize dữ liệu:

```32:36:wp-includes/default-filters.php
foreach ( array( 'pre_term_name', 'pre_comment_author_name', 'pre_link_name', 'pre_link_target', 'pre_link_rel', 'pre_user_display_name', 'pre_user_first_name', 'pre_user_last_name', 'pre_user_nickname' ) as $filter ) {
	add_filter( $filter, 'sanitize_text_field' );
	add_filter( $filter, 'wp_filter_kses' );
	add_filter( $filter, '_wp_specialchars', 30 );
}
```

---

## Sự Khác Biệt Giữa Actions và Filters

| Đặc Điểm | Action Hooks | Filter Hooks |
|----------|--------------|--------------|
| **Mục đích** | Thực thi code | Thay đổi dữ liệu |
| **Giá trị trả về** | Không cần | **PHẢI** trả về giá trị |
| **Cú pháp đăng ký** | `add_action()` | `add_filter()` |
| **Cú pháp gọi** | `do_action()` | `apply_filters()` |
| **Ví dụ** | Gửi email, log, redirect | Thay đổi title, content, URL |

### Quy Tắc Vàng

> **Action**: Làm gì đó (do something)  
> **Filter**: Thay đổi cái gì đó (modify something)

### Ví Dụ So Sánh

```php
// ACTION: Thực thi code (không trả về)
function log_post_save( $post_id ) {
    error_log( "Post $post_id đã được lưu" );
    // Không cần return
}
add_action( 'save_post', 'log_post_save' );

// FILTER: Thay đổi dữ liệu (phải trả về)
function modify_post_title( $title ) {
    return $title . ' - Custom Suffix';
    // PHẢI return giá trị
}
add_filter( 'the_title', 'modify_post_title' );
```

---

## Cách Sử Dụng Hooks

### 1. Sử Dụng Function

```php
function my_custom_function() {
    // Code của bạn
}
add_action( 'init', 'my_custom_function' );
```

### 2. Sử Dụng Anonymous Function (Closure)

```php
add_action( 'init', function() {
    // Code của bạn
} );
```

### 3. Sử Dụng Class Method

```php
class My_Plugin {
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }
    
    public function init() {
        // Code của bạn
    }
}
new My_Plugin();
```

### 4. Sử Dụng Static Method

```php
class My_Plugin {
    public static function init() {
        // Code của bạn
    }
}
add_action( 'init', array( 'My_Plugin', 'init' ) );
```

### 5. Sử Dụng Object Method

```php
$my_object = new My_Class();
add_action( 'init', array( $my_object, 'method_name' ) );
```

---

## Priority (Độ Ưu Tiên)

### Khái Niệm

**Priority** xác định thứ tự thực thi của các callbacks trên cùng một hook. Số càng nhỏ, thực thi càng sớm.

### Thứ Tự Thực Thi

```
Priority 1  → Thực thi đầu tiên
Priority 5  → Thực thi thứ hai
Priority 10 → Thực thi thứ ba (mặc định)
Priority 15 → Thực thi thứ tư
Priority 20 → Thực thi cuối cùng
```

### Ví Dụ

```php
// Thực thi đầu tiên (priority 1)
add_action( 'init', 'function_one', 1 );

// Thực thi thứ hai (priority 5)
add_action( 'init', 'function_two', 5 );

// Thực thi thứ ba (priority 10 - mặc định)
add_action( 'init', 'function_three' );

// Thực thi cuối cùng (priority 20)
add_action( 'init', 'function_four', 20 );
```

### Priority Mặc Định

- **Actions**: 10
- **Filters**: 10

### Khi Nào Cần Thay Đổi Priority?

1. **Cần chạy trước các hooks khác**: Dùng priority thấp (1-9)
2. **Cần chạy sau các hooks khác**: Dùng priority cao (11-99)
3. **Override plugin khác**: Dùng priority cao hơn plugin đó

---

## Các Hooks Quan Trọng Trong WordPress

### Action Hooks Quan Trọng

#### 1. `muplugins_loaded`
- **Khi nào**: Sau khi must-use plugins được load
- **Priority**: Rất sớm
- **Ví dụ**:
```php
add_action( 'muplugins_loaded', 'my_function' );
```

#### 2. `plugins_loaded`
- **Khi nào**: Sau khi tất cả plugins được load
- **Priority**: Sớm, trước `init`
- **Ví dụ**:
```php
add_action( 'plugins_loaded', 'my_function' );
```

#### 3. `after_setup_theme`
- **Khi nào**: Sau khi theme được load
- **Priority**: Sớm
- **Ví dụ**:
```php
add_action( 'after_setup_theme', 'my_theme_setup' );
```

#### 4. `init`
- **Khi nào**: Sau khi WordPress đã load xong, user đã authenticated
- **Priority**: Quan trọng nhất cho plugin development
- **Ví dụ**:
```496:496:wp-settings.php
do_action( 'muplugins_loaded' );
```

```578:578:wp-settings.php
do_action( 'plugins_loaded' );
```

```705:705:wp-settings.php
do_action( 'after_setup_theme' );
```

```727:727:wp-settings.php
do_action( 'init' );
```

#### 5. `wp_loaded`
- **Khi nào**: Sau khi WordPress và tất cả plugins đã load xong
- **Ví dụ**:
```749:749:wp-settings.php
do_action( 'wp_loaded' );
```

#### 6. `template_redirect`
- **Khi nào**: Trước khi load template
- **Dùng để**: Redirect, kiểm tra permissions
- **Ví dụ**:
```php
add_action( 'template_redirect', function() {
    if ( ! is_user_logged_in() ) {
        wp_redirect( wp_login_url() );
        exit;
    }
} );
```

#### 7. `wp_head`
- **Khi nào**: Trong `<head>` tag
- **Dùng để**: Thêm CSS, JS, meta tags
- **Ví dụ**:
```php
add_action( 'wp_head', function() {
    echo '<meta name="custom" content="value">';
} );
```

#### 8. `wp_footer`
- **Khi nào**: Trước thẻ `</body>`
- **Dùng để**: Thêm tracking code, analytics
- **Ví dụ**:
```php
add_action( 'wp_footer', function() {
    echo '<script>console.log("Footer loaded");</script>';
} );
```

#### 9. `save_post`
- **Khi nào**: Khi post/page được lưu
- **Ví dụ**:
```php
add_action( 'save_post', function( $post_id ) {
    // Xử lý sau khi save post
}, 10, 1 );
```

### Filter Hooks Quan Trọng

#### 1. `the_content`
- **Mục đích**: Filter nội dung post/page
- **Ví dụ**:
```php
add_filter( 'the_content', function( $content ) {
    return $content . '<p>Custom content</p>';
} );
```

#### 2. `the_title`
- **Mục đích**: Filter tiêu đề
- **Ví dụ**:
```php
add_filter( 'the_title', function( $title ) {
    return 'Prefix: ' . $title;
} );
```

#### 3. `excerpt_length`
- **Mục đích**: Thay đổi độ dài excerpt
- **Ví dụ**:
```php
add_filter( 'excerpt_length', function( $length ) {
    return 30;
} );
```

#### 4. `excerpt_more`
- **Mục đích**: Thay đổi text "read more"
- **Ví dụ**:
```php
add_filter( 'excerpt_more', function( $more ) {
    return '...';
} );
```

#### 5. `wp_mail`
- **Mục đích**: Filter email trước khi gửi
- **Ví dụ**:
```php
add_filter( 'wp_mail', function( $args ) {
    $args['subject'] = '[Custom] ' . $args['subject'];
    return $args;
} );
```

#### 6. `template_include`
- **Mục đích**: Thay đổi template file được load
- **Ví dụ**:
```php
add_filter( 'template_include', function( $template ) {
    if ( is_single() && get_post_type() == 'product' ) {
        return get_template_directory() . '/single-product.php';
    }
    return $template;
} );
```

---

## Tạo Custom Hooks

### Tạo Custom Action Hook

```php
// Trong code của bạn
function my_custom_process() {
    // Xử lý gì đó
    
    // Fire custom action
    do_action( 'my_custom_action', $data1, $data2 );
}

// Nơi khác, đăng ký hook
add_action( 'my_custom_action', function( $data1, $data2 ) {
    // Xử lý khi action được fire
} );
```

### Tạo Custom Filter Hook

```php
// Trong code của bạn
function get_custom_data() {
    $data = 'Original Data';
    
    // Apply filter
    $data = apply_filters( 'my_custom_filter', $data, $arg1, $arg2 );
    
    return $data;
}

// Nơi khác, đăng ký filter
add_filter( 'my_custom_filter', function( $data, $arg1, $arg2 ) {
    return 'Modified: ' . $data;
}, 10, 3 );
```

### Ví Dụ Thực Tế: Plugin Hook

```php
class My_Plugin {
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }
    
    public function init() {
        // Fire custom hook
        do_action( 'my_plugin_init', $this );
    }
    
    public function process_data( $data ) {
        // Filter data
        $data = apply_filters( 'my_plugin_data', $data );
        return $data;
    }
}
```

---

## Best Practices

### 1. Đặt Tên Hooks Rõ Ràng

```php
// ❌ TỒI
do_action( 'hook1' );

// ✅ TỐT
do_action( 'my_plugin_before_save' );
```

### 2. Sử Dụng Prefix

```php
// ✅ Luôn dùng prefix để tránh conflict
do_action( 'my_plugin_init' );
apply_filters( 'my_plugin_data', $data );
```

### 3. Document Hooks

```php
/**
 * Fires before saving post data.
 *
 * @param int    $post_id Post ID.
 * @param object $post    Post object.
 */
do_action( 'my_plugin_before_save', $post_id, $post );
```

### 4. Kiểm Tra Trước Khi Remove

```php
if ( has_action( 'init', 'some_function' ) ) {
    remove_action( 'init', 'some_function' );
}
```

### 5. Sử Dụng Đúng Priority

```php
// Cần chạy sớm
add_action( 'init', 'my_function', 1 );

// Cần chạy muộn
add_action( 'init', 'my_function', 99 );
```

### 6. Luôn Return Value Trong Filters

```php
// ❌ SAI - Filter phải return
add_filter( 'the_content', function( $content ) {
    echo 'Something'; // SAI!
} );

// ✅ ĐÚNG
add_filter( 'the_content', function( $content ) {
    return $content . 'Something'; // ĐÚNG!
} );
```

### 7. Sử Dụng Conditional Tags

```php
add_action( 'init', function() {
    if ( is_admin() ) {
        // Chỉ chạy trong admin
    }
    
    if ( ! is_user_logged_in() ) {
        // Chỉ chạy khi chưa login
    }
} );
```

---

## Ví Dụ Thực Tế

### Ví Dụ 1: Thêm Custom CSS vào Head

```php
add_action( 'wp_head', function() {
    ?>
    <style>
        .custom-class {
            color: red;
        }
    </style>
    <?php
} );
```

### Ví Dụ 2: Thay Đổi Excerpt Length

```php
add_filter( 'excerpt_length', function( $length ) {
    return 30;
} );

add_filter( 'excerpt_more', function( $more ) {
    return '...';
} );
```

### Ví Dụ 3: Redirect Sau Khi Login

```php
add_action( 'wp_login', function( $user_login, $user ) {
    wp_redirect( home_url( '/custom-page' ) );
    exit;
}, 10, 2 );
```

### Ví Dụ 4: Thêm Custom Field vào Post

```php
// Lưu custom field
add_action( 'save_post', function( $post_id ) {
    if ( isset( $_POST['custom_field'] ) ) {
        update_post_meta( $post_id, 'custom_field', sanitize_text_field( $_POST['custom_field'] ) );
    }
} );

// Hiển thị custom field
add_filter( 'the_content', function( $content ) {
    $custom_field = get_post_meta( get_the_ID(), 'custom_field', true );
    if ( $custom_field ) {
        $content .= '<p>Custom Field: ' . esc_html( $custom_field ) . '</p>';
    }
    return $content;
} );
```

### Ví Dụ 5: Disable Comments trên Specific Post Types

```php
add_filter( 'comments_open', function( $open, $post_id ) {
    $post = get_post( $post_id );
    if ( $post->post_type == 'product' ) {
        return false;
    }
    return $open;
}, 10, 2 );
```

### Ví Dụ 6: Custom Email Template

```php
add_filter( 'wp_mail', function( $args ) {
    $args['message'] = '<html><body>' . $args['message'] . '</body></html>';
    $args['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
    return $args;
} );
```

### Ví Dụ 7: Log Mọi Hook Được Fire

```php
add_action( 'all', function( $hook ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Hook fired: ' . $hook );
    }
} );
```

---

## Tài Liệu Tham Khảo

- **WordPress Plugin API**: https://developer.wordpress.org/plugins/hooks/
- **Action Reference**: https://codex.wordpress.org/Plugin_API/Action_Reference
- **Filter Reference**: https://codex.wordpress.org/Plugin_API/Filter_Reference
- **Hook Database**: https://adambrown.info/p/wp_hooks

---

## Kết Luận

WordPress Hooks là nền tảng của plugin và theme development:

- ✅ **Actions**: Thực thi code tại điểm cụ thể
- ✅ **Filters**: Thay đổi dữ liệu trước khi sử dụng
- ✅ **Priority**: Kiểm soát thứ tự thực thi
- ✅ **Best Practices**: Đảm bảo code chất lượng

Hiểu rõ hooks giúp bạn:
- Viết plugins/themes tốt hơn
- Customize WordPress hiệu quả
- Tích hợp với plugins khác
- Maintain code dễ dàng

Chuc ban thanh cong voi WordPress Hooks!

