# WordPress Testing & CI/CD - Hướng Dẫn Chi Tiết

## Mục lục

1. [Tổng quan Testing trong WordPress](#1-tong-quan-testing-trong-wordpress)
2. [PHPUnit Setup cho Plugin](#2-phpunit-setup-cho-plugin)
3. [WP_UnitTestCase - Test Class cơ bản](#3-wp_unittestcase---test-class-co-ban)
4. [Factory Helpers - Tạo Test Data](#4-factory-helpers---tao-test-data)
5. [Testing Hooks và Filters](#5-testing-hooks-va-filters)
6. [Testing REST API Endpoints](#6-testing-rest-api-endpoints)
7. [Testing AJAX Handlers](#7-testing-ajax-handlers)
8. [Mocking với Brain\Monkey](#8-mocking-voi-brainmonkey)
9. [wp-env - Docker Test Environment](#9-wp-env---docker-test-environment)
10. [JavaScript Testing cho Gutenberg Blocks](#10-javascript-testing-cho-gutenberg-blocks)
11. [GitHub Actions - CI Pipeline](#11-github-actions---ci-pipeline)
12. [Deployment - CD Pipeline](#12-deployment---cd-pipeline)
13. [WordPress.org SVN Deployment](#13-wordpressorg-svn-deployment)
14. [So sánh với Laravel Testing & CI/CD](#14-so-sanh-voi-laravel-testing--cicd)

---

## 1. Tổng quan Testing trong WordPress

### Các loại test

```
Unit Test:
  - Test 1 function/method riêng lẻ
  - Mock tất cả dependencies
  - Nhanh, không cần database
  - Dùng: PHPUnit + Brain\Monkey

Integration Test:
  - Test function với WordPress thật
  - Có database (test database riêng)
  - Chậm hơn, nhưng chính xác hơn
  - Dùng: PHPUnit + WP_UnitTestCase

End-to-End Test:
  - Test toàn bộ flow từ browser
  - Dùng: Playwright, Cypress, wp-e2e-tests

JavaScript Test:
  - Test Gutenberg blocks, React components
  - Dùng: Jest + @wordpress/jest-preset-default
```

### Cấu trúc thư mục

```
my-plugin/
├── my-plugin.php
├── src/
│   └── ...
├── tests/
│   ├── bootstrap.php           ← Load WordPress test suite
│   ├── Unit/                   ← Unit tests (no WP)
│   │   └── ExampleUnitTest.php
│   ├── Integration/            ← Integration tests (with WP)
│   │   ├── PluginTest.php
│   │   ├── RestApiTest.php
│   │   └── HooksTest.php
│   └── js/                     ← JavaScript tests
│       └── block.test.js
├── phpunit.xml                 ← PHPUnit config
├── .wp-env.json                ← Docker test environment
└── .github/
    └── workflows/
        └── ci.yml              ← GitHub Actions
```

---

## 2. PHPUnit Setup cho Plugin

### 2.1. Scaffold với WP-CLI

```bash
# Tạo test scaffold tự động
wp scaffold plugin-tests my-plugin

# Tạo ra:
#   my-plugin/tests/bootstrap.php
#   my-plugin/tests/test-sample.php
#   my-plugin/phpunit.xml.dist
#   my-plugin/bin/install-wp-tests.sh
#   my-plugin/.phpcs.xml.dist

# Cài đặt test suite (download WP test library + tạo test database)
cd wp-content/plugins/my-plugin
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
#                              ^DB name      ^user ^pass ^host  ^WP version
```

### 2.2. phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    beStrictAboutTestsThatDoNotTestAnything="true"
    cacheResult="true"
>
    <testsuites>
        <!-- Integration tests: cần WordPress loaded -->
        <testsuite name="integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>

        <!-- Unit tests: không cần WordPress -->
        <testsuite name="unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">./src</directory>
        </include>
        <exclude>
            <directory>./vendor</directory>
            <directory>./tests</directory>
        </exclude>
    </coverage>

    <php>
        <env name="WP_TESTS_DIR" value="/tmp/wordpress-tests-lib" />
    </php>
</phpunit>
```

### 2.3. tests/bootstrap.php

```php
<?php
/**
 * PHPUnit bootstrap file.
 * Load WordPress test suite và plugin.
 */

// Composer autoload
$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

// WordPress test suite path
$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
    echo "Could not find {$_tests_dir}/includes/functions.php\n";
    echo "Run: bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
    exit( 1 );
}

// Load test functions
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Load plugin TRƯỚC khi WordPress test suite bootstrap.
 * Tương đương activate plugin cho test environment.
 */
tests_add_filter( 'muplugins_loaded', function() {
    require dirname( __DIR__ ) . '/my-plugin.php';
} );

// Bootstrap WordPress test suite
require "{$_tests_dir}/includes/bootstrap.php";
```

### 2.4. composer.json cho testing

```json
{
    "name": "my-vendor/my-plugin",
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "brain/monkey": "^2.6",
        "yoast/phpunit-polyfills": "^2.0",
        "wp-coding-standards/wpcs": "^3.0",
        "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
    },
    "autoload": {
        "psr-4": { "MyPlugin\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "MyPlugin\\Tests\\": "tests/" }
    },
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite unit",
        "test:integration": "phpunit --testsuite integration",
        "test:coverage": "phpunit --coverage-html coverage/",
        "lint": "phpcs --standard=WordPress src/",
        "lint:fix": "phpcbf --standard=WordPress src/"
    }
}
```

---

## 3. WP_UnitTestCase - Test Class cơ bản

```php
<?php
/**
 * File: tests/Integration/PluginTest.php
 *
 * WP_UnitTestCase extends PHPUnit\Framework\TestCase
 * Cung cấp:
 *   - WordPress environment đầy đủ
 *   - Database transactions (rollback sau mỗi test)
 *   - Factory helpers cho test data
 *   - Assertion methods bổ sung
 */

namespace MyPlugin\Tests\Integration;

use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {

    /**
     * setUpBeforeClass: chạy 1 lần trước TẤT CẢ tests trong class.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        // Setup tốn kém: tạo taxonomy, post type...
    }

    /**
     * setUp: chạy trước MỖI test method.
     * Database tự động rollback sau mỗi test.
     */
    public function setUp(): void {
        parent::setUp();
        // Reset state, set options...
    }

    /**
     * tearDown: chạy sau MỖI test method.
     */
    public function tearDown(): void {
        // Cleanup...
        parent::tearDown();
    }

    // ── TEST METHODS ────────────────────────────────────────────

    public function test_plugin_is_active(): void {
        $this->assertTrue( is_plugin_active( 'my-plugin/my-plugin.php' ) );
    }

    public function test_custom_post_type_registered(): void {
        $this->assertTrue( post_type_exists( 'my_cpt' ) );
    }

    public function test_custom_taxonomy_registered(): void {
        $this->assertTrue( taxonomy_exists( 'my_taxonomy' ) );
    }

    public function test_admin_menu_registered(): void {
        // Set current user với quyền admin
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );

        // Trigger admin_menu hook
        do_action( 'admin_menu' );

        global $menu;
        $menu_slugs = wp_list_pluck( $menu ?? array(), 2 ); // Column 2 = slug
        $this->assertContains( 'my-plugin-settings', $menu_slugs );
    }

    public function test_option_saved_and_retrieved(): void {
        update_option( 'my_plugin_setting', 'test_value' );
        $this->assertSame( 'test_value', get_option( 'my_plugin_setting' ) );
    }

    public function test_creates_post_with_meta(): void {
        $post_id = wp_insert_post( array(
            'post_title'  => 'Test Post',
            'post_status' => 'publish',
            'post_type'   => 'post',
        ) );

        update_post_meta( $post_id, '_my_plugin_key', 'my_value' );

        $this->assertGreaterThan( 0, $post_id );
        $this->assertSame( 'my_value', get_post_meta( $post_id, '_my_plugin_key', true ) );
    }

    public function test_shortcode_output(): void {
        // Đăng ký shortcode nếu chưa
        add_shortcode( 'my_shortcode', function( $atts ) {
            $atts = shortcode_atts( array( 'name' => 'World' ), $atts );
            return 'Hello, ' . esc_html( $atts['name'] ) . '!';
        } );

        $output = do_shortcode( '[my_shortcode name="WordPress"]' );
        $this->assertSame( 'Hello, WordPress!', $output );
    }

    /**
     * Test với expected WP_Error.
     */
    public function test_invalid_post_returns_error(): void {
        $result = wp_insert_post( array(
            'post_title'  => '',
            'post_status' => 'publish',
        ), true ); // true = return WP_Error

        // WP vẫn tạo post với title trống, nên test khác
        $result = wp_update_post( array( 'ID' => 999999 ), true );
        $this->assertWPError( $result );
    }

    /**
     * Test redirect.
     */
    public function test_redirect_on_condition(): void {
        // WP_UnitTestCase có method go_to() để simulate request
        $this->go_to( home_url( '/non-existent-page/' ) );
        $this->assertQueryTrue( 'is_404' );
    }

    /**
     * Test với different user roles.
     */
    public function test_capability_check(): void {
        // Tạo subscriber (quyền thấp)
        $subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        wp_set_current_user( $subscriber_id );

        $this->assertFalse( current_user_can( 'manage_options' ) );
        $this->assertTrue( current_user_can( 'read' ) );

        // Tạo admin
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );

        $this->assertTrue( current_user_can( 'manage_options' ) );
    }
}
```

---

## 4. Factory Helpers - Tạo Test Data

```php
<?php
/**
 * File: tests/Integration/FactoryExamplesTest.php
 *
 * Factory helpers tạo test data nhanh chóng.
 * Tự động cleanup (rollback) sau mỗi test.
 */

namespace MyPlugin\Tests\Integration;

use WP_UnitTestCase;

class FactoryExamplesTest extends WP_UnitTestCase {

    // ── POST FACTORY ────────────────────────────────────────────

    public function test_post_factory(): void {
        // Tạo 1 post
        $post_id = self::factory()->post->create();
        $this->assertGreaterThan( 0, $post_id );

        // Tạo post với attributes
        $post_id = self::factory()->post->create( array(
            'post_title'   => 'My Test Post',
            'post_content' => 'Content here.',
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => 1,
        ) );

        $post = get_post( $post_id );
        $this->assertSame( 'My Test Post', $post->post_title );

        // Tạo post và trả về object (thay vì ID)
        $post = self::factory()->post->create_and_get( array(
            'post_title' => 'Another Post',
        ) );
        $this->assertInstanceOf( 'WP_Post', $post );

        // Tạo NHIỀU posts cùng lúc
        $post_ids = self::factory()->post->create_many( 10 );
        $this->assertCount( 10, $post_ids );

        // Tạo nhiều posts với attributes
        $post_ids = self::factory()->post->create_many( 5, array(
            'post_type'   => 'page',
            'post_status' => 'publish',
        ) );
    }

    // ── USER FACTORY ────────────────────────────────────────────

    public function test_user_factory(): void {
        // Tạo user
        $user_id = self::factory()->user->create();

        // Tạo user với role
        $admin_id = self::factory()->user->create( array(
            'role'         => 'administrator',
            'user_login'   => 'testadmin',
            'user_email'   => 'admin@test.com',
            'display_name' => 'Test Admin',
        ) );

        $user = get_userdata( $admin_id );
        $this->assertTrue( in_array( 'administrator', $user->roles, true ) );

        // Tạo nhiều users
        $editor_ids = self::factory()->user->create_many( 3, array(
            'role' => 'editor',
        ) );
        $this->assertCount( 3, $editor_ids );
    }

    // ── TERM FACTORY ────────────────────────────────────────────

    public function test_term_factory(): void {
        // Tạo category
        $term_id = self::factory()->category->create( array(
            'name' => 'Test Category',
            'slug' => 'test-category',
        ) );

        // Tạo tag
        $tag_id = self::factory()->tag->create( array(
            'name' => 'Test Tag',
        ) );

        // Tạo custom taxonomy term
        $term_id = self::factory()->term->create( array(
            'taxonomy' => 'my_taxonomy',
            'name'     => 'Custom Term',
        ) );

        // Assign term cho post
        $post_id = self::factory()->post->create();
        wp_set_object_terms( $post_id, array( $term_id ), 'my_taxonomy' );

        $terms = wp_get_object_terms( $post_id, 'my_taxonomy' );
        $this->assertCount( 1, $terms );
    }

    // ── COMMENT FACTORY ─────────────────────────────────────────

    public function test_comment_factory(): void {
        $post_id = self::factory()->post->create();

        // Tạo comment
        $comment_id = self::factory()->comment->create( array(
            'comment_post_ID' => $post_id,
            'comment_content' => 'Test comment.',
            'comment_approved' => 1,
        ) );

        // Tạo nhiều comments
        $comment_ids = self::factory()->comment->create_many( 5, array(
            'comment_post_ID' => $post_id,
        ) );

        $comments = get_comments( array( 'post_id' => $post_id ) );
        $this->assertCount( 6, $comments ); // 1 + 5
    }

    // ── ATTACHMENT FACTORY ──────────────────────────────────────

    public function test_attachment_factory(): void {
        $attachment_id = self::factory()->attachment->create_upload_object(
            DIR_TESTDATA . '/images/canola.jpg' // File test có sẵn trong WP test suite
        );
        $this->assertGreaterThan( 0, $attachment_id );

        // Set as post thumbnail
        $post_id = self::factory()->post->create();
        set_post_thumbnail( $post_id, $attachment_id );
        $this->assertTrue( has_post_thumbnail( $post_id ) );
    }
}
```

---

## 5. Testing Hooks và Filters

```php
<?php
/**
 * File: tests/Integration/HooksTest.php
 */

namespace MyPlugin\Tests\Integration;

use WP_UnitTestCase;

class HooksTest extends WP_UnitTestCase {

    /**
     * Test action hook được registered.
     */
    public function test_init_hook_registered(): void {
        // Kiểm tra callback đã được add vào hook
        $this->assertGreaterThan(
            0,
            has_action( 'init', 'my_plugin_register_post_types' )
        );
    }

    /**
     * Test filter hook thay đổi output.
     */
    public function test_the_content_filter(): void {
        // Plugin thêm CTA vào cuối bài viết
        $post_id = self::factory()->post->create( array(
            'post_content' => 'Original content.',
            'post_status'  => 'publish',
        ) );

        // Simulate the_content filter
        $content = apply_filters( 'the_content', get_post( $post_id )->post_content );

        // Kiểm tra CTA được thêm vào
        $this->assertStringContainsString( 'Original content.', $content );
        // Nếu plugin thêm CTA:
        // $this->assertStringContainsString( 'cta-box', $content );
    }

    /**
     * Test filter với giá trị return.
     */
    public function test_custom_filter_modifies_value(): void {
        // Giả sử plugin có filter 'my_plugin_format_price'
        add_filter( 'my_plugin_format_price', function( $price ) {
            return number_format( (float) $price, 0, ',', '.' ) . ' VNĐ';
        } );

        $formatted = apply_filters( 'my_plugin_format_price', 1500000 );
        $this->assertSame( '1.500.000 VNĐ', $formatted );
    }

    /**
     * Test action hook được fire đúng số lần.
     */
    public function test_action_fires_on_save(): void {
        $fired = 0;
        add_action( 'my_plugin_after_save', function() use ( &$fired ) {
            $fired++;
        } );

        // Simulate save action
        do_action( 'my_plugin_after_save', array( 'data' => 'test' ) );
        do_action( 'my_plugin_after_save', array( 'data' => 'test2' ) );

        $this->assertSame( 2, $fired );
    }

    /**
     * Test hook priority.
     */
    public function test_filter_priority_order(): void {
        $order = array();

        add_filter( 'my_plugin_test_priority', function( $val ) use ( &$order ) {
            $order[] = 'first (10)';
            return $val;
        }, 10 );

        add_filter( 'my_plugin_test_priority', function( $val ) use ( &$order ) {
            $order[] = 'early (5)';
            return $val;
        }, 5 );

        add_filter( 'my_plugin_test_priority', function( $val ) use ( &$order ) {
            $order[] = 'late (20)';
            return $val;
        }, 20 );

        apply_filters( 'my_plugin_test_priority', 'test' );

        $this->assertSame( array( 'early (5)', 'first (10)', 'late (20)' ), $order );
    }

    /**
     * Test remove_action/remove_filter.
     */
    public function test_remove_hook(): void {
        $callback = function() { return 'modified'; };
        add_filter( 'my_test_filter', $callback );

        $this->assertSame( 'modified', apply_filters( 'my_test_filter', 'original' ) );

        remove_filter( 'my_test_filter', $callback );

        $this->assertSame( 'original', apply_filters( 'my_test_filter', 'original' ) );
    }
}
```

---

## 6. Testing REST API Endpoints

```php
<?php
/**
 * File: tests/Integration/RestApiTest.php
 */

namespace MyPlugin\Tests\Integration;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

class RestApiTest extends WP_UnitTestCase {

    private $server;

    public function setUp(): void {
        parent::setUp();

        // Khởi tạo REST server
        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server();
        do_action( 'rest_api_init' );
    }

    public function tearDown(): void {
        global $wp_rest_server;
        $wp_rest_server = null;
        parent::tearDown();
    }

    /**
     * Test endpoint registered.
     */
    public function test_endpoint_registered(): void {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey( '/my-plugin/v1/items', $routes );
    }

    /**
     * Test GET request.
     */
    public function test_get_items(): void {
        // Tạo test data
        self::factory()->post->create_many( 5, array(
            'post_type'   => 'post',
            'post_status' => 'publish',
        ) );

        // Tạo request
        $request = new WP_REST_Request( 'GET', '/my-plugin/v1/items' );
        $request->set_param( 'per_page', 3 );

        // Dispatch
        $response = $this->server->dispatch( $request );

        // Assertions
        $this->assertSame( 200, $response->get_status() );
        $data = $response->get_data();
        $this->assertCount( 3, $data );
    }

    /**
     * Test POST request với authentication.
     */
    public function test_create_item_requires_auth(): void {
        // Không đăng nhập → 401
        $request = new WP_REST_Request( 'POST', '/my-plugin/v1/items' );
        $request->set_body_params( array( 'title' => 'New Item' ) );

        $response = $this->server->dispatch( $request );
        $this->assertSame( 401, $response->get_status() );
    }

    public function test_create_item_as_admin(): void {
        // Đăng nhập admin
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );

        $request = new WP_REST_Request( 'POST', '/my-plugin/v1/items' );
        $request->set_body_params( array(
            'title'   => 'New Item',
            'content' => 'Item content here.',
        ) );

        $response = $this->server->dispatch( $request );

        $this->assertSame( 201, $response->get_status() );
        $data = $response->get_data();
        $this->assertSame( 'New Item', $data['title'] );
    }

    /**
     * Test validation.
     */
    public function test_create_item_validation(): void {
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );

        // Title trống → validation error
        $request = new WP_REST_Request( 'POST', '/my-plugin/v1/items' );
        $request->set_body_params( array( 'title' => '' ) );

        $response = $this->server->dispatch( $request );
        $this->assertSame( 400, $response->get_status() );
    }

    /**
     * Test DELETE request.
     */
    public function test_delete_item(): void {
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );

        $post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );

        $request = new WP_REST_Request( 'DELETE', "/my-plugin/v1/items/{$post_id}" );
        $response = $this->server->dispatch( $request );

        $this->assertSame( 200, $response->get_status() );
        $this->assertNull( get_post( $post_id ) );
    }

    /**
     * Test response schema.
     */
    public function test_response_schema(): void {
        self::factory()->post->create( array( 'post_status' => 'publish' ) );

        $request  = new WP_REST_Request( 'GET', '/my-plugin/v1/items' );
        $response = $this->server->dispatch( $request );
        $data     = $response->get_data();

        // Kiểm tra cấu trúc response
        $item = $data[0];
        $this->assertArrayHasKey( 'id', $item );
        $this->assertArrayHasKey( 'title', $item );
        $this->assertArrayHasKey( 'content', $item );
        $this->assertArrayHasKey( 'date', $item );
        $this->assertIsInt( $item['id'] );
        $this->assertIsString( $item['title'] );
    }
}
```

---

## 7. Testing AJAX Handlers

```php
<?php
/**
 * File: tests/Integration/AjaxTest.php
 */

namespace MyPlugin\Tests\Integration;

use WP_Ajax_UnitTestCase;

class AjaxTest extends WP_Ajax_UnitTestCase {

    /**
     * Test AJAX handler cho logged-in users.
     */
    public function test_ajax_save_settings(): void {
        // Login admin
        $this->_setRole( 'administrator' );

        // Set POST data
        $_POST['my_plugin_nonce'] = wp_create_nonce( 'my_plugin_save_settings' );
        $_POST['setting_key']     = 'test_key';
        $_POST['setting_value']   = 'test_value';

        // Expect success
        try {
            $this->_handleAjax( 'my_plugin_save_settings' );
        } catch ( \WPAjaxDieContinueException $e ) {
            // wp_send_json_success() throws this
        }

        // Parse response
        $response = json_decode( $this->_last_response, true );
        $this->assertTrue( $response['success'] );
        $this->assertSame( 'Settings saved.', $response['data']['message'] );
    }

    /**
     * Test AJAX handler rejects non-admin.
     */
    public function test_ajax_save_settings_forbidden_for_subscriber(): void {
        $this->_setRole( 'subscriber' );

        $_POST['my_plugin_nonce'] = wp_create_nonce( 'my_plugin_save_settings' );
        $_POST['setting_key']     = 'test_key';
        $_POST['setting_value']   = 'test_value';

        try {
            $this->_handleAjax( 'my_plugin_save_settings' );
        } catch ( \WPAjaxDieStopException $e ) {
            // wp_send_json_error() with 403
        }

        $response = json_decode( $this->_last_response, true );
        $this->assertFalse( $response['success'] );
    }

    /**
     * Test nopriv AJAX (không cần login).
     */
    public function test_ajax_public_search(): void {
        // Không login
        $this->logout();

        // Tạo test data
        self::factory()->post->create( array(
            'post_title'  => 'WordPress Testing Guide',
            'post_status' => 'publish',
        ) );

        $_POST['search_term'] = 'Testing';

        try {
            $this->_handleAjax( 'nopriv_my_plugin_search' );
        } catch ( \WPAjaxDieContinueException $e ) {
            // Expected
        }

        $response = json_decode( $this->_last_response, true );
        $this->assertTrue( $response['success'] );
        $this->assertNotEmpty( $response['data']['results'] );
    }
}
```

---

## 8. Mocking với Brain\Monkey

```php
<?php
/**
 * File: tests/Unit/HelperTest.php
 *
 * Unit tests KHÔNG load WordPress.
 * Dùng Brain\Monkey để mock WordPress functions.
 */

namespace MyPlugin\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use PHPUnit\Framework\TestCase;
use MyPlugin\Helpers\PriceFormatter;

class HelperTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Mock WordPress functions.
     */
    public function test_format_price(): void {
        // Mock esc_html() → return input (no-op)
        Functions\stubs( array(
            'esc_html'          => function( $text ) { return $text; },
            'sanitize_text_field' => function( $str ) { return trim( strip_tags( $str ) ); },
        ) );

        // Mock get_option
        Functions\expect( 'get_option' )
            ->once()
            ->with( 'my_plugin_currency', 'VND' )
            ->andReturn( 'VND' );

        $formatter = new PriceFormatter();
        $result = $formatter->format( 1500000 );

        $this->assertSame( '1.500.000 VND', $result );
    }

    /**
     * Test action được fire.
     */
    public function test_action_is_fired(): void {
        // Expect action to be fired
        Actions\expectDone( 'my_plugin_after_process' )
            ->once()
            ->with( \Mockery::type( 'array' ) );

        Functions\stubs( array( 'do_action' ) );

        // Gọi function trigger action
        do_action( 'my_plugin_after_process', array( 'status' => 'ok' ) );
    }

    /**
     * Test filter applied.
     */
    public function test_filter_applied(): void {
        Filters\expectApplied( 'my_plugin_modify_title' )
            ->once()
            ->with( 'Original Title' )
            ->andReturn( 'Modified Title' );

        $result = apply_filters( 'my_plugin_modify_title', 'Original Title' );
        $this->assertSame( 'Modified Title', $result );
    }

    /**
     * Mock wp_remote_get.
     */
    public function test_api_client(): void {
        Functions\expect( 'wp_remote_get' )
            ->once()
            ->with( 'https://api.example.com/data', \Mockery::type( 'array' ) )
            ->andReturn( array(
                'response' => array( 'code' => 200 ),
                'body'     => '{"status":"ok","data":[1,2,3]}',
            ) );

        Functions\expect( 'is_wp_error' )
            ->once()
            ->andReturn( false );

        Functions\expect( 'wp_remote_retrieve_response_code' )
            ->once()
            ->andReturn( 200 );

        Functions\expect( 'wp_remote_retrieve_body' )
            ->once()
            ->andReturn( '{"status":"ok","data":[1,2,3]}' );

        // Test class method
        // $client = new ApiClient();
        // $result = $client->fetch_data();
        // $this->assertSame(array(1, 2, 3), $result);
    }
}
```

---

## 9. wp-env - Docker Test Environment

### 9.1. Cài đặt

```bash
# Cần Node.js + Docker
npm install -g @wordpress/env

# Hoặc local
npm install --save-dev @wordpress/env
```

### 9.2. .wp-env.json

```json
{
    "core": "WordPress/WordPress#6.7",
    "phpVersion": "8.2",
    "plugins": [
        ".",
        "https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip"
    ],
    "themes": [
        "https://downloads.wordpress.org/theme/twentytwentyfour.latest-stable.zip"
    ],
    "config": {
        "WP_DEBUG": true,
        "WP_DEBUG_LOG": true,
        "SCRIPT_DEBUG": true
    },
    "mappings": {
        "wp-content/mu-plugins/test-utils.php": "./tests/mu-plugins/test-utils.php"
    },
    "env": {
        "tests": {
            "phpVersion": "8.2",
            "config": {
                "WP_DEBUG": true
            }
        }
    }
}
```

### 9.3. Commands

```bash
# Start environment
wp-env start
# → WordPress: http://localhost:8888 (admin/password)
# → Tests:     http://localhost:8889

# Run PHPUnit tests
wp-env run tests-cli --env-cwd=wp-content/plugins/my-plugin phpunit

# Run specific test
wp-env run tests-cli --env-cwd=wp-content/plugins/my-plugin phpunit --filter=test_create_item

# Run WP-CLI commands
wp-env run cli wp post list
wp-env run cli wp plugin list

# Stop
wp-env stop

# Destroy (remove data)
wp-env destroy

# Clean start
wp-env clean all
```

---

## 10. JavaScript Testing cho Gutenberg Blocks

### 10.1. Setup

```json
{
    "devDependencies": {
        "@wordpress/scripts": "^30.0.0",
        "@wordpress/jest-preset-default": "^12.0.0",
        "@testing-library/react": "^14.0.0"
    },
    "scripts": {
        "test:js": "wp-scripts test-unit-js",
        "test:js:watch": "wp-scripts test-unit-js --watch",
        "test:js:coverage": "wp-scripts test-unit-js --coverage"
    }
}
```

### 10.2. Test Gutenberg Block

```javascript
/**
 * File: tests/js/block.test.js
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { registerBlockType } from '@wordpress/blocks';
import Edit from '../../src/edit';

// Mock WordPress dependencies
jest.mock( '@wordpress/block-editor', () => ( {
    useBlockProps: () => ( { className: 'test-block' } ),
    RichText: ( { value, onChange, placeholder } ) => (
        <input
            value={ value }
            onChange={ ( e ) => onChange( e.target.value ) }
            placeholder={ placeholder }
            data-testid="rich-text"
        />
    ),
    InspectorControls: ( { children } ) => <div data-testid="inspector">{ children }</div>,
} ) );

jest.mock( '@wordpress/i18n', () => ( {
    __: ( str ) => str,
} ) );

describe( 'Callout Box Block', () => {
    const defaultAttributes = {
        heading: '',
        content: '',
        backgroundColor: '#fff3cd',
        textColor: '#333333',
        alignment: 'left',
    };

    const mockSetAttributes = jest.fn();

    beforeEach( () => {
        mockSetAttributes.mockClear();
    } );

    test( 'renders with default attributes', () => {
        render(
            <Edit
                attributes={ defaultAttributes }
                setAttributes={ mockSetAttributes }
                isSelected={ false }
            />
        );

        expect( screen.getByPlaceholderText( 'Callout Heading…' ) ).toBeInTheDocument();
    } );

    test( 'calls setAttributes when heading changes', () => {
        render(
            <Edit
                attributes={ defaultAttributes }
                setAttributes={ mockSetAttributes }
                isSelected={ true }
            />
        );

        const inputs = screen.getAllByTestId( 'rich-text' );
        fireEvent.change( inputs[0], { target: { value: 'New Heading' } } );

        expect( mockSetAttributes ).toHaveBeenCalledWith( { heading: 'New Heading' } );
    } );

    test( 'applies background color style', () => {
        const { container } = render(
            <Edit
                attributes={ { ...defaultAttributes, backgroundColor: '#ff0000' } }
                setAttributes={ mockSetAttributes }
                isSelected={ false }
            />
        );

        // Check style is applied
        const block = container.querySelector( '.test-block' );
        expect( block ).toBeTruthy();
    } );
} );
```

---

## 11. GitHub Actions - CI Pipeline

### 11.1. CI Pipeline hoàn chỉnh

```yaml
# File: .github/workflows/ci.yml

name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

# Cancel previous runs on same branch
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

jobs:
  # ── JOB 1: PHPCS LINTING ──────────────────────────────────────
  lint:
    name: PHPCS Lint
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer, cs2pr

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run PHPCS
        run: composer lint -- --report=checkstyle | cs2pr

  # ── JOB 2: PHP UNIT TESTS ─────────────────────────────────────
  test-php:
    name: PHPUnit (PHP ${{ matrix.php }} / WP ${{ matrix.wp }})
    runs-on: ubuntu-latest
    needs: lint

    strategy:
      fail-fast: false
      matrix:
        php: ['8.1', '8.2', '8.3']
        wp: ['6.5', '6.6', 'latest']

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mysqli, mbstring, intl
          coverage: xdebug

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install WP test suite
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 ${{ matrix.wp }}

      - name: Run PHPUnit
        run: composer test

      - name: Upload coverage
        if: matrix.php == '8.2' && matrix.wp == 'latest'
        uses: codecov/codecov-action@v4
        with:
          file: ./coverage.xml

  # ── JOB 3: JAVASCRIPT TESTS ───────────────────────────────────
  test-js:
    name: JavaScript Tests
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Install dependencies
        run: npm ci

      - name: Build
        run: npm run build

      - name: Run JS tests
        run: npm run test:js -- --coverage

  # ── JOB 4: BUILD ASSETS ───────────────────────────────────────
  build:
    name: Build Assets
    runs-on: ubuntu-latest
    needs: [test-php, test-js]

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Install & Build
        run: |
          npm ci
          npm run build

      - name: Upload build artifact
        uses: actions/upload-artifact@v4
        with:
          name: build-assets
          path: build/
          retention-days: 7
```

---

## 12. Deployment - CD Pipeline

### 12.1. Deploy via SSH/rsync

```yaml
# File: .github/workflows/deploy.yml

name: Deploy

on:
  push:
    tags:
      - 'v*'  # Chỉ deploy khi push tag: v1.0.0, v1.1.0...

jobs:
  deploy:
    name: Deploy to Production
    runs-on: ubuntu-latest
    environment: production  # GitHub Environment (cần approval)

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Composer (production)
        run: composer install --no-dev --optimize-autoloader

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Build assets
        run: |
          npm ci
          npm run build

      - name: Setup SSH key
        uses: webfactory/ssh-agent@v0.9.0
        with:
          ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}

      - name: Add known hosts
        run: ssh-keyscan -H ${{ secrets.SSH_HOST }} >> ~/.ssh/known_hosts

      - name: Deploy via rsync
        run: |
          rsync -avz --delete \
            --exclude='.git' \
            --exclude='.github' \
            --exclude='node_modules' \
            --exclude='tests' \
            --exclude='.wp-env.json' \
            --exclude='phpunit.xml' \
            --exclude='composer.json' \
            --exclude='composer.lock' \
            --exclude='package.json' \
            --exclude='package-lock.json' \
            --exclude='.phpcs.xml.dist' \
            ./ ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }}:${{ secrets.DEPLOY_PATH }}/

      - name: Clear cache on server
        run: |
          ssh ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} << 'EOF'
            cd ${{ secrets.DEPLOY_PATH }}/../../../
            wp cache flush --path=. 2>/dev/null || true
            wp rewrite flush --path=. 2>/dev/null || true
          EOF

      - name: Notify Slack
        if: always()
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: "Deploy ${{ github.ref_name }} → production: ${{ job.status }}"
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

### 12.2. Deploy Staging (auto trên develop branch)

```yaml
# File: .github/workflows/deploy-staging.yml

name: Deploy Staging

on:
  push:
    branches: [ develop ]

jobs:
  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    environment: staging

    steps:
      - uses: actions/checkout@v4

      - name: Setup & Build
        run: |
          composer install --no-dev --optimize-autoloader
          npm ci && npm run build

      - name: Deploy via rsync
        uses: burnett01/rsync-deployments@7.0.1
        with:
          switches: -avz --delete --exclude='.git' --exclude='node_modules' --exclude='tests'
          path: ./
          remote_path: ${{ secrets.STAGING_PATH }}/
          remote_host: ${{ secrets.STAGING_HOST }}
          remote_user: ${{ secrets.STAGING_USER }}
          remote_key: ${{ secrets.STAGING_SSH_KEY }}
```

---

## 13. WordPress.org SVN Deployment

```yaml
# File: .github/workflows/wp-org-deploy.yml

name: Deploy to WordPress.org

on:
  release:
    types: [published]

jobs:
  deploy-wp-org:
    name: Deploy to WordPress.org SVN
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup
        run: |
          composer install --no-dev --optimize-autoloader
          npm ci && npm run build

      - name: WordPress.org Plugin Deploy
        uses: 10up/action-wordpress-plugin-deploy@stable
        env:
          SVN_USERNAME: ${{ secrets.WP_ORG_SVN_USERNAME }}
          SVN_PASSWORD: ${{ secrets.WP_ORG_SVN_PASSWORD }}
          SLUG: my-plugin
          BUILD_DIR: false
          ASSETS_DIR: .wordpress-org  # screenshots, banners
        with:
          generate-zip: true

      - name: Upload release asset
        uses: softprops/action-gh-release@v2
        with:
          files: ${{ github.event.repository.name }}.zip
```

### WordPress.org file structure

```
my-plugin/
├── .wordpress-org/          ← Assets cho WordPress.org listing
│   ├── banner-772x250.png
│   ├── banner-1544x500.png
│   ├── icon-128x128.png
│   ├── icon-256x256.png
│   └── screenshot-1.png
├── readme.txt               ← WordPress.org readme (REQUIRED)
└── ...
```

### readme.txt

```
=== My Plugin ===
Contributors: yourname
Donate link: https://example.com/donate
Tags: utility, tools, custom
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short description of the plugin (max 150 chars).

== Description ==

Full description of the plugin.

**Features:**

* Feature 1
* Feature 2
* Feature 3

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/my-plugin/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → My Plugin to configure

== Frequently Asked Questions ==

= How do I configure the plugin? =

Go to Settings → My Plugin.

== Screenshots ==

1. Settings page overview
2. Frontend widget display

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
```

---

## 14. So sánh với Laravel Testing & CI/CD

### Bảng so sánh

| Tính năng | WordPress | Laravel |
|-----------|-----------|---------|
| **Test framework** | PHPUnit + WP_UnitTestCase | PHPUnit + TestCase |
| **Test database** | Separate test DB + rollback | SQLite in-memory hoặc transactions |
| **Factory** | `self::factory()->post->create()` | `Post::factory()->create()` |
| **HTTP test** | `WP_REST_Request` + dispatch | `$this->get('/api/...')` |
| **AJAX test** | `WP_Ajax_UnitTestCase` | N/A (dùng HTTP test) |
| **Mocking** | Brain\Monkey + Mockery | Mockery + Facades |
| **Browser test** | wp-e2e-tests (Puppeteer) | Laravel Dusk (Chrome) |
| **JS test** | Jest + @wordpress/scripts | Vitest hoặc Jest |
| **CI tool** | GitHub Actions | GitHub Actions / Laravel Forge |
| **Deploy** | rsync / SVN | Envoyer / Forge / rsync |
| **Environment** | wp-env (Docker) | Laravel Sail (Docker) |
| **Code style** | PHPCS + WPCS | Pint (Laravel Pint) |
| **Coverage** | Xdebug + Codecov | Xdebug + Codecov |

### So sánh code test

```php
<?php
// ── LARAVEL ─────────────────────────────────────────────────────

// tests/Feature/PostTest.php
class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_post(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'New Post',
                'content' => 'Content here.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Post');

        $this->assertDatabaseHas('posts', ['title' => 'New Post']);
    }
}

// ── WORDPRESS ───────────────────────────────────────────────────

// tests/Integration/PostTest.php
class PostTest extends WP_UnitTestCase
{
    public function test_create_post(): void
    {
        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $request = new WP_REST_Request('POST', '/my-plugin/v1/posts');
        $request->set_body_params([
            'title' => 'New Post',
            'content' => 'Content here.',
        ]);

        $response = rest_do_request($request);

        $this->assertSame(201, $response->get_status());
        $this->assertSame('New Post', $response->get_data()['title']);
    }
}
```

---

## Tổng kết

| Chủ đề | Tools/Commands |
|--------|---------------|
| Scaffold tests | `wp scaffold plugin-tests my-plugin` |
| Run tests | `composer test`, `wp-env run tests-cli phpunit` |
| Unit test (no WP) | `PHPUnit\Framework\TestCase` + `Brain\Monkey` |
| Integration test | `WP_UnitTestCase` + factory helpers |
| AJAX test | `WP_Ajax_UnitTestCase` |
| REST test | `WP_REST_Request` + `$server->dispatch()` |
| JS test | `npm run test:js` (Jest + @wordpress/scripts) |
| Docker env | `wp-env start/stop/destroy` |
| CI | GitHub Actions: PHPCS → PHPUnit → Build |
| Deploy | rsync via SSH hoặc 10up/action-wordpress-plugin-deploy |
| WP.org | SVN deploy + readme.txt |

---

[← Quay lại: Multisite](./06-multisite.md) | [Tiếp: i18n & l10n →](./08-i18n-l10n.md)
