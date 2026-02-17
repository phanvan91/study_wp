# WordPress Routing System - Hệ Thống Router trong WordPress

## Tổng Quan

WordPress **KHÔNG có router theo kiểu MVC truyền thống** như Laravel hay Express.js. Thay vào đó, WordPress sử dụng một hệ thống **URL Rewriting** kết hợp với **Query Variables** để xử lý routing.

---

## 1. Kiến Trúc Router WordPress

### 1.1. URL Rewriting System

WordPress sử dụng **Apache mod_rewrite** (hoặc Nginx rewrite rules) để chuyển đổi các URL "pretty" thành query variables:

```
Pretty URL: /category/technology/
        ↓ (Apache mod_rewrite)
Internal URL: index.php?category_name=technology
```

### 1.2. Hai Phương Pháp Routing

WordPress hỗ trợ **2 chế độ URL**:

#### **Chế độ 1: Plain Permalinks (Không có pretty URLs)**
```
❌ http://example.com/?p=123
❌ http://example.com/index.php?page_id=456
```
- Không cần mod_rewrite
- URL không thân thiện với SEO
- Ít dùng trong thực tế

#### **Chế độ 2: Pretty Permalinks (URL thân thiện)**
```
✅ http://example.com/2024/01/15/my-post/
✅ http://example.com/category/technology/
✅ http://example.com/product/laptop/
```
- Cần mod_rewrite
- URL thân thiện với SEO
- Phổ biến nhất

---

## 2. Các Thành Phần Chính

### 2.1. `WP_Rewrite` Class

Class chính quản lý toàn bộ hệ thống routing:

```php
global $wp_rewrite;
```

**File:** `wp-includes/class-wp-rewrite.php`

**Chức năng:**
- Tạo rewrite rules từ permalink structure
- Lưu rewrite rules vào database (option `rewrite_rules`)
- Generate .htaccess rules cho Apache
- Parse URL và khớp với rewrite rules

### 2.2. Rewrite Rules

Là các quy tắc chuyển đổi URL pattern thành query variables, được lưu trong:
- **Database:** `wp_options` table, option `rewrite_rules`
- **File:** `.htaccess` (nếu dùng Apache)

**Ví dụ rewrite rule:**
```php
'category/(.+?)/?$' => 'index.php?category_name=$matches[1]'
```

URL: `/category/technology/` → `category_name=technology`

### 2.3. Query Variables

Sau khi parse URL, WordPress tạo ra các **query variables** trong `$wp->query_vars`:

```php
// Ví dụ URL: /2024/01/15/my-post/
$wp->query_vars = [
    'year'      => '2024',
    'monthnum'  => '01',
    'day'       => '15',
    'name'      => 'my-post'
];
```

---

## 3. Luồng Xử Lý Routing

### 3.1. Flow Diagram

```
1. Request đến: /category/technology/
   ↓
2. Apache mod_rewrite chuyển thành: index.php?...
   ↓
3. WordPress load wp-blog-header.php
   ↓
4. Parse REQUEST_URI
   ↓
5. WP_Rewrite::wp_rewrite_rules() - Lấy danh sách rewrite rules
   ↓
6. WP::parse_request() - Match URL với rewrite rules
   ↓
7. Extract query variables từ matched rule
   ↓
8. Set $wp->query_vars
   ↓
9. WP_Query sử dụng query_vars để query database
   ↓
10. Template loader chọn template phù hợp
   ↓
11. Render template
```

### 3.2. Chi Tiết Các Bước

#### **Bước 1: Apache mod_rewrite**

File `.htaccess` có các rules:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

**Chức năng:**
- Nếu file không tồn tại → redirect về `index.php`
- WordPress sẽ xử lý tiếp từ `index.php`

#### **Bước 2: Parse Request**

Trong `WP::parse_request()` (file: `wp-includes/class-wp.php`):

```136:238:wp-includes/class-wp.php
	public function parse_request( $extra_query_vars = '' ) {
		global $wp_rewrite;

		// ... code ...

		// Fetch the rewrite rules.
		$rewrite = $wp_rewrite->wp_rewrite_rules();

		if ( ! empty( $rewrite ) ) {
			// ... process PATH_INFO, REQUEST_URI ...

			// Look for matches.
			$request_match = $requested_path;
			if ( empty( $request_match ) ) {
				// ... empty request ...
			} else {
				foreach ( (array) $rewrite as $match => $query ) {
					if ( preg_match( "#^$match#", $request_match, $matches )
						|| preg_match( "#^$match#", urldecode( $request_match ), $matches )
					) {
						// Got a match.
						$this->matched_rule = $match;
						break;
					}
				}
			}

			// ... parse query from matched rule ...
		}
	}
```

**Quá trình:**
1. Lấy danh sách rewrite rules từ `$wp_rewrite`
2. Loop qua từng rule và dùng regex để match URL
3. Khi match được → extract query variables
4. Set vào `$wp->query_vars`

---

## 4. Rewrite API - Tạo Custom Routes

WordPress cung cấp các hàm để bạn tạo custom rewrite rules:

### 4.1. `add_rewrite_rule()`

Thêm một rewrite rule mới:

```php
// URL: /products/laptop/
// → index.php?product_type=laptop

add_rewrite_rule(
    '^products/([^/]+)/?$',
    'index.php?product_type=$matches[1]',
    'top' // 'top' hoặc 'bottom'
);
```

**Tham số:**
- `$regex`: Pattern regex để match URL
- `$query`: Query string tương ứng
- `$after`: 'top' (ưu tiên cao) hoặc 'bottom' (ưu tiên thấp)

### 4.2. `add_rewrite_tag()`

Đăng ký một rewrite tag mới (như `%postname%`, `%category%`):

```php
// Đăng ký tag %product%
add_rewrite_tag( '%product%', '([^/]+)', 'product=' );

// Sử dụng trong permalink structure
add_permastruct( 'product', '/product/%product%/', array(
    'with_front' => false,
) );
```

### 4.3. `add_permastruct()`

Tạo permalink structure mới:

```php
// Tạo permalink structure cho custom post type
add_permastruct( 'product', '/product/%product%/', array(
    'with_front' => false,
) );
```

### 4.4. `add_rewrite_endpoint()`

Thêm endpoint vào URL (như `/feed/`, `/page/2/`):

```php
// Thêm endpoint /json/ vào single posts
add_rewrite_endpoint( 'json', EP_PERMALINK | EP_PAGES );

// URL: /my-post/json/
// → index.php?name=my-post&json=...
```

### 4.5. Đăng ký Query Var

Để WordPress nhận diện query variable mới:

```php
// Trong functions.php hoặc plugin
function my_register_query_vars( $vars ) {
    $vars[] = 'product_type';
    return $vars;
}
add_filter( 'query_vars', 'my_register_query_vars' );
```

### 4.6. Flush Rewrite Rules

**QUAN TRỌNG:** Sau khi thêm rewrite rules, phải flush để lưu:

```php
// Option 1: Trong code (khi activate plugin/theme)
flush_rewrite_rules( true ); // hard flush

// Option 2: Trong admin
// Settings → Permalinks → Click "Save Changes"
```

---

## 5. Ví Dụ Thực Tế

### 5.1. Tạo Custom Post Type với Custom URL

```php
// Register custom post type
function register_product_post_type() {
    register_post_type( 'product', array(
        'public' => true,
        'rewrite' => array(
            'slug' => 'san-pham',
            'with_front' => false,
        ),
    ) );
}
add_action( 'init', 'register_product_post_type' );
```

**Kết quả:**
- URL: `/san-pham/laptop/` thay vì `/product/laptop/`

### 5.2. Tạo Custom Archive với Query Var

```php
// 1. Đăng ký query var
function register_custom_query_vars( $vars ) {
    $vars[] = 'product_category';
    return $vars;
}
add_filter( 'query_vars', 'register_custom_query_vars' );

// 2. Thêm rewrite rule
function add_product_category_rewrite_rule() {
    add_rewrite_rule(
        '^loai-san-pham/([^/]+)/?$',
        'index.php?product_category=$matches[1]',
        'top'
    );
}
add_action( 'init', 'add_product_category_rewrite_rule' );

// 3. Xử lý query trong template
function handle_product_category_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        $product_category = get_query_var( 'product_category' );
        if ( $product_category ) {
            // Custom query logic
            $query->set( 'post_type', 'product' );
            $query->set( 'tax_query', array(
                array(
                    'taxonomy' => 'product_category',
                    'field'    => 'slug',
                    'terms'    => $product_category,
                ),
            ) );
        }
    }
}
add_action( 'pre_get_posts', 'handle_product_category_query' );

// 4. Flush rules (chỉ cần chạy 1 lần khi setup)
// flush_rewrite_rules( true );
```

**Kết quả:**
- URL: `/loai-san-pham/laptop/` → Query products với category "laptop"

### 5.3. REST API Routes

WordPress REST API cũng dùng rewrite rules:

```226:229:wp-includes/rest-api.php
	add_rewrite_rule( '^' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
	add_rewrite_rule( '^' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
```

**Ví dụ:**
- URL: `/wp-json/wp/v2/posts` → `index.php?rest_route=/wp/v2/posts`

---

## 6. Template Routing

Sau khi parse URL và set query vars, WordPress dùng **Template Hierarchy** để chọn template:

### 6.1. Template Hierarchy

WordPress kiểm tra các điều kiện theo thứ tự:

```php
// Template loader checks:
is_embed()              → embed.php
is_404()                → 404.php
is_search()             → search.php
is_front_page()         → front-page.php
is_home()               → home.php
is_post_type_archive()  → archive-{post_type}.php
is_tax()                → taxonomy-{taxonomy}-{term}.php
is_attachment()         → attachment-{mime-type}.php
is_single()             → single-{post_type}.php
is_page()               → page-{slug}.php
is_category()           → category-{slug}.php
is_tag()                → tag-{slug}.php
is_author()             → author-{nicename}.php
is_date()               → date.php
is_archive()            → archive.php
                        → index.php (fallback)
```

### 6.2. Template Conditional Tags

Sử dụng các hàm conditional để kiểm tra loại trang:

```php
if ( is_single() ) {
    // Single post page
}

if ( is_page() ) {
    // Static page
}

if ( is_category() ) {
    // Category archive
}

// Custom query var check
if ( get_query_var( 'product_category' ) ) {
    // Custom product category page
}
```

---

## 7. Debugging Rewrite Rules

### 7.1. Xem Tất Cả Rewrite Rules

```php
// Trong functions.php hoặc plugin
global $wp_rewrite;
print_r( $wp_rewrite->wp_rewrite_rules() );
```

### 7.2. Xem Matched Rule

```php
global $wp;
echo 'Matched Rule: ' . $wp->matched_rule;
echo 'Query Vars: ';
print_r( $wp->query_vars );
```

### 7.3. Debug Query Vars

```php
add_action( 'wp', function() {
    global $wp;
    if ( current_user_can( 'administrator' ) ) {
        echo '<pre>';
        print_r( $wp->query_vars );
        echo '</pre>';
    }
} );
```

### 7.4. Xem Rewrite Rules trong Database

```sql
SELECT * FROM wp_options WHERE option_name = 'rewrite_rules';
```

---

## 8. Best Practices

### 8.1. Flush Rules Đúng Cách

❌ **SAI:** Flush mỗi request
```php
add_action( 'init', 'my_flush_rules' ); // SAI!
```

✅ **ĐÚNG:** Chỉ flush khi cần
```php
register_activation_hook( __FILE__, 'my_plugin_activate' );
function my_plugin_activate() {
    // Add rewrite rules
    add_rewrite_rule( ... );
    flush_rewrite_rules( true );
}

register_deactivation_hook( __FILE__, 'my_plugin_deactivate' );
function my_plugin_deactivate() {
    flush_rewrite_rules( true );
}
```

### 8.2. Đặt Rewrite Rules ở Hook `init`

```php
add_action( 'init', 'my_add_rewrite_rules' );
function my_add_rewrite_rules() {
    add_rewrite_rule( ... );
}
```

### 8.3. Sử dụng Rewrite Tags thay vì Hard-code

❌ **SAI:**
```php
add_rewrite_rule( '^product/(.+?)/?$', 'index.php?product=$matches[1]', 'top' );
```

✅ **ĐÚNG:**
```php
add_rewrite_tag( '%product%', '([^/]+)', 'product=' );
add_permastruct( 'product', '/product/%product%/' );
```

### 8.4. Document Custom Routes

Luôn document các custom routes bạn tạo:

```php
/**
 * Custom rewrite rules:
 * 
 * /products/{category}/     → product_category={category}
 * /product/{slug}/json/     → product JSON endpoint
 */
```

---

## 9. So Sánh với Framework Khác

### 9.1. Laravel
```php
// Laravel
Route::get('/products/{id}', [ProductController::class, 'show']);

// WordPress
add_rewrite_rule('^products/([^/]+)/?$', 'index.php?product_id=$matches[1]', 'top');
```

### 9.2. Express.js
```javascript
// Express.js
app.get('/products/:id', (req, res) => { ... });

// WordPress
add_rewrite_rule('^products/([^/]+)/?$', 'index.php?product_id=$matches[1]', 'top');
```

**Điểm khác biệt:**
- WordPress: Rewrite rules → Query vars → Template hierarchy
- Laravel/Express: Route → Controller → View/Response

---

## 10. Tổng Kết

### Đặc Điểm Router WordPress:

✅ **Ưu điểm:**
- Linh hoạt với rewrite API
- Tích hợp sâu với database và query
- Hỗ trợ pretty URLs tốt
- Template hierarchy tự động

❌ **Hạn chế:**
- Không có controller layer
- Phức tạp hơn framework hiện đại
- Cần flush rules khi thay đổi
- Phụ thuộc vào server rewrite rules

### Khi Nào Dùng:

✅ **Phù hợp:**
- Custom post types
- Custom taxonomies
- Custom archive pages
- REST API endpoints

❌ **Không phù hợp:**
- SPA (Single Page Application)
- API-first architecture
- Complex routing logic

---

## Tài Liệu Tham Khảo

- [WordPress Rewrite API](https://developer.wordpress.org/reference/functions/add_rewrite_rule/)
- [WP_Rewrite Class](https://developer.wordpress.org/reference/classes/wp_rewrite/)
- [Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/)
- [Custom Post Types](https://developer.wordpress.org/reference/functions/register_post_type/)

