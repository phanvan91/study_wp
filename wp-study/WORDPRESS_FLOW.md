# WordPress Request Flow - Luồng Xử Lý Request trong WordPress

## Tổng Quan

Khi một request được gửi đến WordPress, nó sẽ trải qua một chuỗi các bước xử lý từ entry point đến khi render HTML. Dưới đây là luồng chi tiết:

## 1. Entry Point - Điểm Vào Chính

### `index.php` (Dòng 1-18)

Đây là file đầu tiên được gọi khi có request đến WordPress:

```1:18:index.php
<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
```

**Chức năng:**
- Định nghĩa hằng số `WP_USE_THEMES = true` (báo cho WordPress biết sẽ load theme)
- Load file `wp-blog-header.php` để khởi tạo WordPress environment

---

## 2. Blog Header - Khởi Tạo WordPress Environment

### `wp-blog-header.php` (Dòng 1-22)

File này có nhiệm vụ load WordPress core và thiết lập query:

```1:22:wp-blog-header.php
<?php
/**
 * Loads the WordPress environment and template.
 *
 * @package WordPress
 */

if ( ! isset( $wp_did_header ) ) {

	$wp_did_header = true;

	// Load the WordPress library.
	require_once __DIR__ . '/wp-load.php';

	// Set up the WordPress query.
	wp();

	// Load the theme template.
	require_once ABSPATH . WPINC . '/template-loader.php';

}
```

**Chức năng:**
1. **Kiểm tra `$wp_did_header`**: Đảm bảo WordPress chỉ được load một lần
2. **Load WordPress Core**: Gọi `wp-load.php` để load toàn bộ WordPress
3. **Thiết lập Query**: Gọi hàm `wp()` để parse request và thiết lập query variables
4. **Load Template**: Gọi `template-loader.php` để load theme template phù hợp

---

## 3. WordPress Loader - Load Core WordPress

### `wp-load.php` (Dòng 1-106)

File này là bootstrap file, có nhiệm vụ:

```19:22:wp-load.php
/** Define ABSPATH as this file's directory */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
```

**Chức năng:**
1. **Định nghĩa ABSPATH**: Đường dẫn tuyệt đối đến thư mục WordPress
2. **Load wp-config.php**: Load file cấu hình database, keys, constants
3. **Xử lý lỗi**: Nếu không tìm thấy wp-config.php, redirect đến setup page

```47:50:wp-load.php
if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

	/** The config file resides in ABSPATH */
	require_once ABSPATH . 'wp-config.php';
```

---

## 4. WordPress Settings - Khởi Tạo Toàn Bộ WordPress

### `wp-settings.php` (Dòng 1-750)

Đây là file quan trọng nhất, load toàn bộ WordPress core. Luồng xử lý:

### 4.1. Khởi Tạo Cơ Bản (Dòng 16-84)

```16:84:wp-settings.php
define( 'WPINC', 'wp-includes' );
require ABSPATH . WPINC . '/version.php';
require ABSPATH . WPINC . '/compat.php';
require ABSPATH . WPINC . '/load.php';

// Check for the required PHP version and for the MySQL extension or a database drop-in.
wp_check_php_mysql_versions();

// Standardize $_SERVER variables across setups.
wp_fix_server_vars();

// Check if the site is in maintenance mode.
wp_maintenance();

// Start loading timer.
timer_start();

// Check if WP_DEBUG mode is enabled.
wp_debug_mode();
```

**Chức năng:**
- Load version, compatibility, và load utilities
- Kiểm tra PHP version và MySQL extension
- Chuẩn hóa `$_SERVER` variables
- Kiểm tra maintenance mode
- Bật debug mode nếu cần

### 4.2. Load Database (Dòng 132-147)

```132:147:wp-settings.php
global $wpdb;
// Include the wpdb class and, if present, a db.php database drop-in.
require_wp_db();

/**
 * @since 3.3.0
 *
 * @global string $table_prefix The database table prefix.
 */
$GLOBALS['table_prefix'] = $table_prefix;

// Set the database table prefix and the format specifiers for database table columns.
wp_set_wpdb_vars();

// Start the WordPress object cache, or an external object cache if the drop-in is present.
wp_start_object_cache();
```

**Chức năng:**
- Khởi tạo `$wpdb` object (WordPress database abstraction layer)
- Thiết lập table prefix
- Khởi động object cache

### 4.3. Load Core Classes (Dòng 111-411)

WordPress load hàng trăm class files:
- `class-wp.php` - WordPress main class
- `class-wp-query.php` - Query class
- `class-wp-user.php` - User class
- `class-wp-theme.php` - Theme class
- REST API classes
- Block editor classes
- Và nhiều class khác...

### 4.4. Load Plugins (Dòng 454-557)

```454:557:wp-settings.php
// Load must-use plugins.
foreach ( wp_get_mu_plugins() as $mu_plugin ) {
	$_wp_plugin_file = $mu_plugin;
	include_once $mu_plugin;
	$mu_plugin = $_wp_plugin_file; // Avoid stomping of the $mu_plugin variable in a plugin.

	/**
	 * Fires once a single must-use plugin has loaded.
	 *
	 * @since 5.1.0
	 *
	 * @param string $mu_plugin Full path to the plugin's main file.
	 */
	do_action( 'mu_plugin_loaded', $mu_plugin );
}

// ... network plugins ...

// Load active plugins.
foreach ( wp_get_active_and_valid_plugins() as $plugin ) {
	wp_register_plugin_realpath( $plugin );
	$_wp_plugin_file = $plugin;
	include_once $plugin;
	$plugin = $_wp_plugin_file;
	do_action( 'plugin_loaded', $plugin );
}
```

**Thứ tự load plugins:**
1. **Must-use plugins** (mu-plugins) - Load đầu tiên, không thể tắt
2. **Network plugins** (nếu multisite) - Plugins kích hoạt cho toàn mạng
3. **Active plugins** - Plugins được kích hoạt trong admin

**Hooks được fire:**
- `mu_plugin_loaded` - Sau mỗi mu-plugin
- `muplugins_loaded` - Sau tất cả mu-plugins
- `plugin_loaded` - Sau mỗi active plugin
- `plugins_loaded` - Sau tất cả plugins

### 4.5. Load Theme (Dòng 688-705)

```688:705:wp-settings.php
// Load the functions for the active theme, for both parent and child theme if applicable.
foreach ( wp_get_active_and_valid_themes() as $theme ) {
	$wp_theme = wp_get_theme( basename( $theme ) );

	$wp_theme->load_textdomain();

	if ( file_exists( $theme . '/functions.php' ) ) {
		include $theme . '/functions.php';
	}
}
unset( $theme, $wp_theme );

/**
 * Fires after the theme is loaded.
 *
 * @since 3.0.0
 */
do_action( 'after_setup_theme' );
```

**Chức năng:**
- Load `functions.php` của theme (và child theme nếu có)
- Load textdomain cho theme
- Fire hook `after_setup_theme`

### 4.6. Initialize WordPress Object (Dòng 714-749)

```600:628:wp-settings.php
$GLOBALS['wp_the_query'] = new WP_Query();

$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

$GLOBALS['wp_rewrite'] = new WP_Rewrite();

$GLOBALS['wp'] = new WP();

$GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();

$GLOBALS['wp_roles'] = new WP_Roles();
```

```714:749:wp-settings.php
// Set up current user.
$GLOBALS['wp']->init();

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 * Most of WP is loaded at this stage, and the user is authenticated. WP continues
 * to load on the {@see 'init'} hook that follows (e.g. widgets), and many plugins instantiate
 * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once WP is loaded, use the {@see 'wp_loaded'} hook below.
 *
 * @since 1.5.0
 */
do_action( 'init' );

// Check site status.
if ( is_multisite() ) {
	$file = ms_site_check();
	if ( true !== $file ) {
		require $file;
		die();
	}
	unset( $file );
}

/**
 * This hook is fired once WP, all plugins, and the theme are fully loaded and instantiated.
 *
 * Ajax requests should use wp-admin/admin-ajax.php. admin-ajax.php can handle requests for
 * users not logged in.
 *
 * @link https://developer.wordpress.org/plugins/javascript/ajax
 *
 * @since 3.0.0
 */
do_action( 'wp_loaded' );
```

**Chức năng:**
- Khởi tạo các global objects: `$wp_query`, `$wp_rewrite`, `$wp`, `$wp_roles`
- Gọi `$wp->init()` để thiết lập current user
- Fire hook `init` - Nơi plugins thường đăng ký actions
- Fire hook `wp_loaded` - Sau khi mọi thứ đã load xong

---

## 5. Parse Request - Phân Tích Request

### Hàm `wp()` trong `wp-blog-header.php`

Hàm `wp()` là wrapper function gọi `$wp->main()`:

```php
function wp() {
	global $wp;
	$wp->main();
}
```

### `WP::main()` và `WP::parse_request()`

**Chức năng:**
1. **Parse URL**: Phân tích URL request để xác định loại trang (post, page, archive, etc.)
2. **Set Query Vars**: Thiết lập các biến query như `p`, `page_id`, `category_name`, etc.
3. **Match Rewrite Rules**: Khớp URL với rewrite rules để tạo pretty URLs
4. **Set Query Variables**: Thiết lập `$wp->query_vars` để WP_Query sử dụng

**Ví dụ:**
- URL: `/category/technology/` → `query_vars['category_name'] = 'technology'`
- URL: `/2024/01/15/my-post/` → `query_vars['year'] = '2024'`, `query_vars['monthnum'] = '01'`, `query_vars['name'] = 'my-post'`

---

## 6. Template Loader - Load Template Phù Hợp

### `template-loader.php` (Dòng 1-115)

File này quyết định template nào sẽ được load dựa trên query:

```55:95:template-loader.php
if ( wp_using_themes() ) {

	$tag_templates = array(
		'is_embed'             => 'get_embed_template',
		'is_404'               => 'get_404_template',
		'is_search'            => 'get_search_template',
		'is_front_page'        => 'get_front_page_template',
		'is_home'              => 'get_home_template',
		'is_privacy_policy'    => 'get_privacy_policy_template',
		'is_post_type_archive' => 'get_post_type_archive_template',
		'is_tax'               => 'get_taxonomy_template',
		'is_attachment'        => 'get_attachment_template',
		'is_single'            => 'get_single_template',
		'is_page'              => 'get_page_template',
		'is_singular'          => 'get_singular_template',
		'is_category'          => 'get_category_template',
		'is_tag'               => 'get_tag_template',
		'is_author'            => 'get_author_template',
		'is_date'              => 'get_date_template',
		'is_archive'           => 'get_archive_template',
	);
	$template      = false;

	// Loop through each of the template conditionals, and find the appropriate template file.
	foreach ( $tag_templates as $tag => $template_getter ) {
		if ( call_user_func( $tag ) ) {
			$template = call_user_func( $template_getter );
		}

		if ( $template ) {
			if ( 'is_attachment' === $tag ) {
				remove_filter( 'the_content', 'prepend_attachment' );
			}

			break;
		}
	}

	if ( ! $template ) {
		$template = get_index_template();
	}
```

**Template Hierarchy:**
WordPress kiểm tra các điều kiện theo thứ tự ưu tiên:
1. `is_embed` → `embed.php`
2. `is_404` → `404.php`
3. `is_search` → `search.php`
4. `is_front_page` → `front-page.php`
5. `is_home` → `home.php`
6. `is_single` → `single.php` hoặc `single-{post-type}.php`
7. `is_page` → `page.php` hoặc `page-{slug}.php`
8. `is_category` → `category.php` hoặc `category-{slug}.php`
9. `is_tag` → `tag.php` hoặc `tag-{slug}.php`
10. `is_archive` → `archive.php` hoặc `archive-{post-type}.php`
11. Mặc định → `index.php`

**Template được include:**
```104:107:template-loader.php
$template = apply_filters( 'template_include', $template );
if ( $template ) {
	include $template;
}
```

---

## 7. Template Rendering - Render HTML

Sau khi template được load, WordPress sẽ:
1. **Execute Template**: Chạy code PHP trong template file
2. **Call Template Functions**: Gọi các hàm như `get_header()`, `the_content()`, `get_footer()`
3. **Render HTML**: Tạo HTML output
4. **Send to Browser**: Gửi HTML về browser

---

## Tóm Tắt Luồng Hoạt Động

```
1. Request đến server
   ↓
2. index.php
   - Define WP_USE_THEMES = true
   - Require wp-blog-header.php
   ↓
3. wp-blog-header.php
   - Require wp-load.php
   - Call wp() → Parse request
   - Require template-loader.php
   ↓
4. wp-load.php
   - Define ABSPATH
   - Require wp-config.php
   ↓
5. wp-config.php
   - Define database constants
   - Define security keys
   - Require wp-settings.php
   ↓
6. wp-settings.php
   - Load core classes
   - Connect to database ($wpdb)
   - Load must-use plugins
   - Load active plugins
   - Load theme functions.php
   - Initialize $wp object
   - Fire hooks: muplugins_loaded → plugins_loaded → after_setup_theme → init → wp_loaded
   ↓
7. wp() function
   - Parse URL request
   - Set query variables
   - Match rewrite rules
   ↓
8. template-loader.php
   - Check template conditionals (is_single, is_page, etc.)
   - Find appropriate template file
   - Apply template_include filter
   - Include template file
   ↓
9. Template File (single.php, page.php, etc.)
   - Render HTML
   - Call get_header(), the_content(), get_footer()
   ↓
10. HTML Output → Browser
```

---

## Các Hooks Quan Trọng (Action Hooks)

Thứ tự các hooks được fire trong quá trình load:

1. **`mu_plugin_loaded`** - Sau mỗi must-use plugin
2. **`muplugins_loaded`** - Sau tất cả must-use plugins
3. **`network_plugin_loaded`** - Sau mỗi network plugin (multisite)
4. **`plugin_loaded`** - Sau mỗi active plugin
5. **`plugins_loaded`** - Sau tất cả plugins (quan trọng cho plugin development)
6. **`setup_theme`** - Trước khi load theme
7. **`after_setup_theme`** - Sau khi load theme functions.php
8. **`init`** - Sau khi WordPress đã load xong, user đã authenticated (quan trọng nhất)
9. **`wp_loaded`** - Sau khi mọi thứ đã load xong
10. **`template_redirect`** - Trước khi load template
11. **`template_include`** - Filter để thay đổi template file

---

## Các Global Objects Quan Trọng

- **`$wpdb`**: WordPress database object
- **`$wp_query`**: Main query object
- **`$wp`**: WordPress environment object
- **`$wp_rewrite`**: Rewrite rules object
- **`$wp_roles`**: User roles object
- **`$wp_embed`**: Embed handler object
- **`$wp_locale`**: Locale object

---

## Kết Luận

Luồng xử lý request của WordPress được thiết kế rất có hệ thống, từ entry point đơn giản đến việc load toàn bộ core, plugins, theme và cuối cùng render template phù hợp. Hiểu rõ luồng này giúp bạn:

- Debug hiệu quả hơn
- Viết plugins/themes tốt hơn
- Tối ưu performance
- Customize WordPress theo nhu cầu

