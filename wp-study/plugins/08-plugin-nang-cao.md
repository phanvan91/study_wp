# Plugin Nang cao

## Muc luc

1. [Custom Post Types va Taxonomies](#1-custom-post-types-va-taxonomies)
2. [Meta Boxes](#2-meta-boxes)
3. [Custom Admin Columns](#3-custom-admin-columns)
4. [Cron Jobs](#4-cron-jobs)
5. [Email: wp_mail va Custom Templates](#5-email-wp_mail-va-custom-templates)
6. [Export/Import Functionality](#6-exportimport-functionality)
7. [Plugin Updates (Custom Update Checker)](#7-plugin-updates-custom-update-checker)
8. [Multisite Compatibility](#8-multisite-compatibility)
9. [Internationalization (i18n)](#9-internationalization-i18n)
10. [Unit Testing (PHPUnit)](#10-unit-testing-phpunit)
11. [Packaging va Distribution](#11-packaging-va-distribution)
12. [Best Practices](#12-best-practices)

---

## 1. Custom Post Types va Taxonomies

### Tao Custom Post Type (CPT) trong Plugin

```php
<?php
/**
 * Plugin Name: Advanced Features
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom Post Type = Loai noi dung tuy chinh.
 * WordPress co san: post, page, attachment, revision, nav_menu_item
 * Plugin tao them: product, event, portfolio, testimonial, v.v.
 *
 * So sanh voi Laravel:
 * Laravel: php artisan make:model Product -mcr (Model + Migration + Controller + Resource)
 * WordPress: register_post_type() (tao ca "model" + "admin UI" + "routing")
 */

add_action( 'init', 'af_register_post_types' );

function af_register_post_types() {
    /**
     * register_post_type() - Dang ky Custom Post Type
     *
     * @param string $post_type  Slug cua post type (toi da 20 ky tu, khong co dau cach)
     * @param array  $args       Cac tuy chon
     */
    register_post_type( 'product', array(
        // Labels: Ten hien thi o cac noi trong admin
        'labels' => array(
            'name'                  => 'San pham',
            'singular_name'        => 'San pham',
            'add_new'              => 'Them moi',
            'add_new_item'         => 'Them san pham moi',
            'edit_item'            => 'Sua san pham',
            'new_item'             => 'San pham moi',
            'view_item'            => 'Xem san pham',
            'search_items'         => 'Tim san pham',
            'not_found'            => 'Khong tim thay san pham',
            'not_found_in_trash'   => 'Khong co san pham trong thung rac',
            'all_items'            => 'Tat ca san pham',
            'menu_name'            => 'San pham',
        ),

        // Cai dat chung
        'public'             => true,       // Hien thi cho public
        'publicly_queryable' => true,       // Co the query tu frontend
        'show_ui'            => true,       // Hien thi UI trong admin
        'show_in_menu'       => true,       // Hien thi trong admin menu
        'show_in_rest'       => true,       // Ho tro REST API + Gutenberg
        'show_in_nav_menus'  => true,       // Cho phep them vao menu
        'menu_position'      => 5,          // Vi tri menu (sau Posts)
        'menu_icon'          => 'dashicons-cart', // Icon

        // Tinh nang
        'supports'           => array(
            'title',           // Tieu de
            'editor',          // Noi dung (Gutenberg)
            'thumbnail',       // Featured image
            'excerpt',         // Tom tat
            'custom-fields',   // Custom fields
            'revisions',       // Lich su chinh sua
            'author',          // Tac gia
            'page-attributes', // Thu tu (menu_order)
            'comments',        // Binh luan
        ),

        // URL
        'has_archive'        => true,       // Co trang archive (/products/)
        'rewrite'            => array(
            'slug'       => 'san-pham',     // URL slug (/san-pham/ten-sp/)
            'with_front' => false,          // Khong them prefix blog
        ),

        // Quyen
        'capability_type'    => 'post',     // Dung quyen giong post
        // Hoac tuy chinh:
        // 'capability_type'    => 'product',
        // 'map_meta_cap'       => true,

        // Khac
        'hierarchical'       => false,      // false = giong post, true = giong page (co parent)
        'query_var'          => true,
        'can_export'         => true,
    ));

    // === DANG KY TAXONOMY (Phan loai) ===

    /**
     * register_taxonomy() - Dang ky Taxonomy tuy chinh
     *
     * Taxonomy giong Category/Tag nhung cho Custom Post Type.
     *
     * So sanh voi Laravel:
     * Laravel: Relationship (belongsToMany) + Pivot table
     * WordPress: register_taxonomy() + wp_term_* tables
     */

    // Taxonomy dang Category (hierarchical = true => co parent/child)
    register_taxonomy( 'product_cat', 'product', array(
        'labels' => array(
            'name'          => 'Danh muc san pham',
            'singular_name' => 'Danh muc',
            'add_new_item'  => 'Them danh muc moi',
            'edit_item'     => 'Sua danh muc',
            'all_items'     => 'Tat ca danh muc',
            'search_items'  => 'Tim danh muc',
        ),
        'hierarchical'      => true,    // Co cap bac (giong Category)
        'show_ui'           => true,
        'show_in_rest'      => true,    // Gutenberg support
        'show_admin_column' => true,    // Hien thi cot trong admin list
        'rewrite'           => array( 'slug' => 'danh-muc-sp' ),
    ));

    // Taxonomy dang Tag (hierarchical = false => phang)
    register_taxonomy( 'product_tag', 'product', array(
        'labels' => array(
            'name'          => 'The san pham',
            'singular_name' => 'The',
            'add_new_item'  => 'Them the moi',
        ),
        'hierarchical'      => false,   // Khong co cap bac (giong Tag)
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => array( 'slug' => 'the-sp' ),
    ));
}

// QUAN TRONG: Flush rewrite rules khi activate/deactivate
register_activation_hook( __FILE__, function() {
    af_register_post_types();          // Dang ky CPT truoc
    flush_rewrite_rules();             // Roi flush rules
});

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
```

---

## 2. Meta Boxes

```php
<?php
/**
 * Meta Box = Hop thong tin them trong trang Edit Post/Page.
 * Cho phep them cac truong tuy chinh cho Custom Post Type.
 *
 * So sanh voi Laravel: Form fields trong Create/Edit view
 */

add_action( 'add_meta_boxes', 'af_add_meta_boxes' );

function af_add_meta_boxes() {
    /**
     * add_meta_box() - Them meta box vao trang edit
     *
     * @param string   $id        ID duy nhat
     * @param string   $title     Tieu de hien thi
     * @param callable $callback  Ham render noi dung
     * @param string   $screen    Post type (hoac array cua nhieu post types)
     * @param string   $context   Vi tri: 'normal', 'side', 'advanced'
     * @param string   $priority  Do uu tien: 'high', 'core', 'default', 'low'
     * @param array    $args      Tham so truyen cho callback
     */
    add_meta_box(
        'product_details',         // ID
        'Chi tiet san pham',       // Tieu de
        'af_product_meta_box',     // Callback
        'product',                 // Post type
        'normal',                  // Context
        'high'                     // Priority
    );

    // Meta box o sidebar
    add_meta_box(
        'product_pricing',
        'Gia san pham',
        'af_pricing_meta_box',
        'product',
        'side',                    // Ben sidebar
        'default'
    );
}

/**
 * Render meta box: Chi tiet san pham
 */
function af_product_meta_box( $post ) {
    // Tao nonce de bao mat
    wp_nonce_field( 'af_save_product_meta', 'af_product_nonce' );

    // Lay gia tri hien tai
    $sku       = get_post_meta( $post->ID, '_af_product_sku', true );
    $weight    = get_post_meta( $post->ID, '_af_product_weight', true );
    $dimensions = get_post_meta( $post->ID, '_af_product_dimensions', true ) ?: array();
    $color     = get_post_meta( $post->ID, '_af_product_color', true );
    $featured  = get_post_meta( $post->ID, '_af_product_featured', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="af_sku">Ma san pham (SKU)</label></th>
            <td>
                <input type="text" id="af_sku" name="af_sku"
                       value="<?php echo esc_attr( $sku ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="af_weight">Can nang (kg)</label></th>
            <td>
                <input type="number" id="af_weight" name="af_weight"
                       value="<?php echo esc_attr( $weight ); ?>"
                       step="0.01" min="0" class="small-text"> kg
            </td>
        </tr>
        <tr>
            <th>Kich thuoc (cm)</th>
            <td>
                <input type="number" name="af_dim_length"
                       value="<?php echo esc_attr( $dimensions['length'] ?? '' ); ?>"
                       placeholder="Dai" class="small-text" step="0.1"> x
                <input type="number" name="af_dim_width"
                       value="<?php echo esc_attr( $dimensions['width'] ?? '' ); ?>"
                       placeholder="Rong" class="small-text" step="0.1"> x
                <input type="number" name="af_dim_height"
                       value="<?php echo esc_attr( $dimensions['height'] ?? '' ); ?>"
                       placeholder="Cao" class="small-text" step="0.1">
            </td>
        </tr>
        <tr>
            <th><label for="af_color">Mau sac</label></th>
            <td>
                <select id="af_color" name="af_color">
                    <option value="">-- Chon mau --</option>
                    <?php
                    $colors = array( 'red' => 'Do', 'blue' => 'Xanh duong', 'green' => 'Xanh la',
                                     'black' => 'Den', 'white' => 'Trang' );
                    foreach ( $colors as $val => $label ) :
                    ?>
                        <option value="<?php echo esc_attr( $val ); ?>"
                                <?php selected( $color, $val ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>San pham noi bat</th>
            <td>
                <label>
                    <input type="checkbox" name="af_featured" value="1"
                           <?php checked( $featured, '1' ); ?>>
                    Danh dau la san pham noi bat
                </label>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render meta box: Gia san pham (sidebar)
 */
function af_pricing_meta_box( $post ) {
    $price      = get_post_meta( $post->ID, '_af_price', true );
    $sale_price = get_post_meta( $post->ID, '_af_sale_price', true );
    $stock      = get_post_meta( $post->ID, '_af_stock', true );
    ?>
    <p>
        <label for="af_price"><strong>Gia goc (VND):</strong></label><br>
        <input type="number" id="af_price" name="af_price"
               value="<?php echo esc_attr( $price ); ?>"
               min="0" step="1000" style="width:100%;">
    </p>
    <p>
        <label for="af_sale_price"><strong>Gia khuyen mai:</strong></label><br>
        <input type="number" id="af_sale_price" name="af_sale_price"
               value="<?php echo esc_attr( $sale_price ); ?>"
               min="0" step="1000" style="width:100%;">
    </p>
    <p>
        <label for="af_stock"><strong>Ton kho:</strong></label><br>
        <input type="number" id="af_stock" name="af_stock"
               value="<?php echo esc_attr( $stock ); ?>"
               min="0" style="width:100%;">
    </p>
    <?php
}

/**
 * Luu meta data khi save post
 */
add_action( 'save_post_product', 'af_save_product_meta', 10, 2 );

function af_save_product_meta( $post_id, $post ) {
    // Kiem tra nonce
    if ( ! isset( $_POST['af_product_nonce'] ) ||
         ! wp_verify_nonce( $_POST['af_product_nonce'], 'af_save_product_meta' ) ) {
        return;
    }

    // Kiem tra autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Kiem tra quyen
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Luu tung truong
    update_post_meta( $post_id, '_af_product_sku', sanitize_text_field( $_POST['af_sku'] ?? '' ) );
    update_post_meta( $post_id, '_af_product_weight', floatval( $_POST['af_weight'] ?? 0 ) );
    update_post_meta( $post_id, '_af_product_dimensions', array(
        'length' => floatval( $_POST['af_dim_length'] ?? 0 ),
        'width'  => floatval( $_POST['af_dim_width'] ?? 0 ),
        'height' => floatval( $_POST['af_dim_height'] ?? 0 ),
    ));
    update_post_meta( $post_id, '_af_product_color', sanitize_text_field( $_POST['af_color'] ?? '' ) );
    update_post_meta( $post_id, '_af_product_featured', isset( $_POST['af_featured'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_af_price', absint( $_POST['af_price'] ?? 0 ) );
    update_post_meta( $post_id, '_af_sale_price', absint( $_POST['af_sale_price'] ?? 0 ) );
    update_post_meta( $post_id, '_af_stock', absint( $_POST['af_stock'] ?? 0 ) );
}
```

---

## 3. Custom Admin Columns

```php
<?php
/**
 * Them cac cot tuy chinh vao trang danh sach CPT trong admin.
 */

// Dinh nghia cac cot hien thi
add_filter( 'manage_product_posts_columns', 'af_product_columns' );

function af_product_columns( $columns ) {
    // $columns mac dinh: cb, title, author, date
    $new_columns = array();
    $new_columns['cb']        = $columns['cb'];          // Checkbox
    $new_columns['thumbnail'] = 'Hinh anh';              // Cot moi
    $new_columns['title']     = $columns['title'];        // Tieu de
    $new_columns['sku']       = 'SKU';                    // Cot moi
    $new_columns['price']     = 'Gia';                    // Cot moi
    $new_columns['stock']     = 'Ton kho';                // Cot moi
    $new_columns['featured']  = 'Noi bat';                // Cot moi
    $new_columns['taxonomy-product_cat'] = 'Danh muc';   // Taxonomy column
    $new_columns['date']      = $columns['date'];         // Ngay

    return $new_columns;
}

// Hien thi noi dung cot
add_action( 'manage_product_posts_custom_column', 'af_product_column_content', 10, 2 );

function af_product_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'thumbnail':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 50, 50 ), array(
                    'style' => 'border-radius:4px;'
                ));
            } else {
                echo '<span style="color:#ccc;">--</span>';
            }
            break;

        case 'sku':
            $sku = get_post_meta( $post_id, '_af_product_sku', true );
            echo esc_html( $sku ?: '--' );
            break;

        case 'price':
            $price = get_post_meta( $post_id, '_af_price', true );
            $sale = get_post_meta( $post_id, '_af_sale_price', true );
            if ( $sale && $sale < $price ) {
                echo '<del>' . number_format( $price ) . '</del> ';
                echo '<strong style="color:#46b450;">' . number_format( $sale ) . ' VND</strong>';
            } elseif ( $price ) {
                echo number_format( $price ) . ' VND';
            } else {
                echo '--';
            }
            break;

        case 'stock':
            $stock = get_post_meta( $post_id, '_af_stock', true );
            if ( $stock === '' || $stock === false ) {
                echo '--';
            } elseif ( intval( $stock ) <= 0 ) {
                echo '<span style="color:#dc3232; font-weight:bold;">Het hang</span>';
            } elseif ( intval( $stock ) < 10 ) {
                echo '<span style="color:#f56e28;">' . intval( $stock ) . '</span>';
            } else {
                echo intval( $stock );
            }
            break;

        case 'featured':
            $featured = get_post_meta( $post_id, '_af_product_featured', true );
            echo $featured === '1' ? '<span style="color:#46b450;">&#9733;</span>' : '';
            break;
    }
}

// Cho phep sap xep theo cac cot
add_filter( 'manage_edit-product_sortable_columns', 'af_product_sortable_columns' );

function af_product_sortable_columns( $columns ) {
    $columns['price'] = 'price';
    $columns['stock'] = 'stock';
    $columns['sku']   = 'sku';
    return $columns;
}

// Xu ly sap xep
add_action( 'pre_get_posts', 'af_product_orderby' );

function af_product_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( $query->get( 'post_type' ) !== 'product' ) return;

    $orderby = $query->get( 'orderby' );

    if ( $orderby === 'price' ) {
        $query->set( 'meta_key', '_af_price' );
        $query->set( 'orderby', 'meta_value_num' );
    } elseif ( $orderby === 'stock' ) {
        $query->set( 'meta_key', '_af_stock' );
        $query->set( 'orderby', 'meta_value_num' );
    } elseif ( $orderby === 'sku' ) {
        $query->set( 'meta_key', '_af_product_sku' );
        $query->set( 'orderby', 'meta_value' );
    }
}
```

---

## 4. Cron Jobs

```php
<?php
/**
 * WordPress Cron (WP-Cron)
 * Lich trinh chay cac tac vu dinh ky.
 *
 * LUU Y QUAN TRONG:
 * WP-Cron KHONG la real cron. No chi chay khi co nguoi truy cap website.
 * Neu website it traffic, cron co the bi tre.
 *
 * Giai phap: Dung system cron thay the:
 * * * * * * wget -q -O - https://example.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
 * Va them vao wp-config.php: define('DISABLE_WP_CRON', true);
 *
 * So sanh voi Laravel:
 * Laravel: Task Scheduling trong app/Console/Kernel.php
 * WordPress: wp_schedule_event() + action hooks
 */

// === DANG KY CRON EVENT KHI ACTIVATE ===
register_activation_hook( __FILE__, 'af_activate_cron' );

function af_activate_cron() {
    // Kiem tra xem event da duoc len lich chua
    if ( ! wp_next_scheduled( 'af_daily_cleanup' ) ) {
        /**
         * wp_schedule_event() - Len lich event dinh ky
         *
         * @param int    $timestamp  Thoi gian bat dau (UNIX timestamp)
         * @param string $recurrence Tan suat: 'hourly', 'twicedaily', 'daily', 'weekly'
         * @param string $hook       Action hook se duoc goi
         * @param array  $args       Tham so truyen cho hook
         */
        wp_schedule_event(
            time(),                  // Bat dau ngay
            'daily',                 // Moi ngay
            'af_daily_cleanup'       // Hook name
        );
    }

    // Len lich event chay 1 lan
    if ( ! wp_next_scheduled( 'af_one_time_task' ) ) {
        /**
         * wp_schedule_single_event() - Len lich chay 1 lan
         */
        wp_schedule_single_event(
            time() + 3600,           // 1 gio sau
            'af_one_time_task'
        );
    }
}

// === XOA CRON EVENT KHI DEACTIVATE ===
register_deactivation_hook( __FILE__, 'af_deactivate_cron' );

function af_deactivate_cron() {
    // Xoa tat ca scheduled events cua plugin
    wp_clear_scheduled_hook( 'af_daily_cleanup' );
    wp_clear_scheduled_hook( 'af_hourly_sync' );
    wp_clear_scheduled_hook( 'af_one_time_task' );
}

// === DANG KY HANDLER CHO CRON EVENT ===
add_action( 'af_daily_cleanup', 'af_do_daily_cleanup' );

function af_do_daily_cleanup() {
    global $wpdb;

    // Xoa logs cu hon 30 ngay
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}af_logs WHERE created_at < %s",
            date( 'Y-m-d', strtotime( '-30 days' ) )
        )
    );

    // Xoa transients het han
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_timeout_af_%'
         AND option_value < UNIX_TIMESTAMP()"
    );

    // Ghi log
    update_option( 'af_last_cleanup', current_time( 'mysql' ) );
}

// === THEM TAN SUAT TUY CHINH ===
add_filter( 'cron_schedules', 'af_custom_cron_schedules' );

function af_custom_cron_schedules( $schedules ) {
    // Moi 5 phut
    $schedules['every_5_minutes'] = array(
        'interval' => 300,          // 5 * 60 giay
        'display'  => 'Moi 5 phut',
    );

    // Moi 15 phut
    $schedules['every_15_minutes'] = array(
        'interval' => 900,
        'display'  => 'Moi 15 phut',
    );

    // Moi 30 phut
    $schedules['every_30_minutes'] = array(
        'interval' => 1800,
        'display'  => 'Moi 30 phut',
    );

    // Moi tuan
    $schedules['weekly'] = array(
        'interval' => 604800,       // 7 * 24 * 60 * 60
        'display'  => 'Moi tuan',
    );

    return $schedules;
}

// Su dung tan suat tuy chinh
// wp_schedule_event( time(), 'every_5_minutes', 'af_check_stock' );
// add_action( 'af_check_stock', function() { /* Kiem tra ton kho */ });
```

---

## 5. Email: wp_mail va Custom Templates

```php
<?php
/**
 * wp_mail() - Ham gui email cua WordPress
 * Tuong tu mail() cua PHP nhung co the hook va tuy chinh.
 *
 * So sanh voi Laravel:
 * Laravel: Mail::to($user)->send(new OrderConfirmation($order));
 * WordPress: wp_mail($to, $subject, $message, $headers);
 */

// === GUI EMAIL DON GIAN ===
function af_send_simple_email( $to, $subject, $message ) {
    /**
     * wp_mail() - Gui email
     *
     * @param string|array $to          Dia chi nhan (hoac mang dia chi)
     * @param string       $subject     Tieu de
     * @param string       $message     Noi dung
     * @param string|array $headers     Headers (CC, BCC, From, Content-Type)
     * @param string|array $attachments Duong dan file dinh kem
     *
     * @return bool  true = gui thanh cong, false = that bai
     */
    $sent = wp_mail( $to, $subject, $message );
    return $sent;
}

// === GUI EMAIL HTML VOI TEMPLATE ===
function af_send_html_email( $to, $subject, $template_data ) {
    // Headers cho email HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
        // 'CC: cc@example.com',
        // 'BCC: bcc@example.com',
        // 'Reply-To: reply@example.com',
    );

    // Tao noi dung HTML tu template
    $message = af_get_email_template( 'order-confirmation', $template_data );

    $sent = wp_mail( $to, $subject, $message, $headers );

    if ( ! $sent ) {
        // Log loi
        error_log( 'Email gui that bai den: ' . $to );
    }

    return $sent;
}

// === EMAIL TEMPLATE SYSTEM ===

/**
 * Tao noi dung email tu template file
 */
function af_get_email_template( $template_name, $data = array() ) {
    // Tim file template
    $template_file = plugin_dir_path( __FILE__ ) . "templates/emails/{$template_name}.php";

    if ( ! file_exists( $template_file ) ) {
        return '';
    }

    // Dung output buffering de lay HTML
    ob_start();
    extract( $data ); // Bien doi $data thanh cac bien rieng le
    include $template_file;
    $content = ob_get_clean();

    // Boc trong layout chung
    return af_email_layout( $content, $data );
}

/**
 * Layout chung cho tat ca email
 */
function af_email_layout( $content, $data = array() ) {
    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url();
    $year      = date( 'Y' );

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin:0; padding:0; background:#f4f4f4; font-family:Arial,sans-serif;'>
        <div style='max-width:600px; margin:0 auto; padding:20px;'>
            <!-- Header -->
            <div style='background:#0073aa; padding:20px; text-align:center; border-radius:5px 5px 0 0;'>
                <h1 style='color:#fff; margin:0; font-size:24px;'>{$site_name}</h1>
            </div>

            <!-- Content -->
            <div style='background:#fff; padding:30px; border:1px solid #ddd;'>
                {$content}
            </div>

            <!-- Footer -->
            <div style='text-align:center; padding:20px; color:#999; font-size:12px;'>
                <p>&copy; {$year} {$site_name}. Tat ca quyen duoc bao luu.</p>
                <p><a href='{$site_url}' style='color:#0073aa;'>{$site_url}</a></p>
            </div>
        </div>
    </body>
    </html>";
}

// === FILE TEMPLATE: templates/emails/order-confirmation.php ===
// <?php
// /**
//  * Template: Email xac nhan don hang
//  * Bien co san: $customer_name, $order_id, $items, $total
//  */
// ?>
// <h2 style="color:#333;">Xac nhan don hang #<?php echo esc_html($order_id); ?></h2>
// <p>Xin chao <?php echo esc_html($customer_name); ?>,</p>
// <p>Don hang cua ban da duoc xac nhan. Chi tiet:</p>
// <table style="width:100%; border-collapse:collapse;">
//     <tr style="background:#f5f5f5;">
//         <th style="padding:10px; text-align:left; border:1px solid #ddd;">San pham</th>
//         <th style="padding:10px; text-align:right; border:1px solid #ddd;">Gia</th>
//     </tr>
//     <?php foreach ($items as $item) : ?>
//     <tr>
//         <td style="padding:10px; border:1px solid #ddd;"><?php echo esc_html($item['name']); ?></td>
//         <td style="padding:10px; text-align:right; border:1px solid #ddd;">
//             <?php echo number_format($item['price']); ?> VND
//         </td>
//     </tr>
//     <?php endforeach; ?>
//     <tr style="font-weight:bold;">
//         <td style="padding:10px; border:1px solid #ddd;">Tong cong</td>
//         <td style="padding:10px; text-align:right; border:1px solid #ddd;">
//             <?php echo number_format($total); ?> VND
//         </td>
//     </tr>
// </table>

// === SU DUNG ===
// af_send_html_email(
//     'customer@example.com',
//     'Xac nhan don hang #123',
//     array(
//         'customer_name' => 'Nguyen Van A',
//         'order_id'      => 123,
//         'items'         => array(
//             array( 'name' => 'San pham A', 'price' => 100000 ),
//             array( 'name' => 'San pham B', 'price' => 200000 ),
//         ),
//         'total'         => 300000,
//     )
// );

// === HOOK DE TUY CHINH EMAIL ===

// Doi tat ca email sang HTML
add_filter( 'wp_mail_content_type', function() {
    return 'text/html';
});

// Doi ten nguoi gui
add_filter( 'wp_mail_from_name', function() {
    return get_bloginfo( 'name' );
});

// Doi email nguoi gui
add_filter( 'wp_mail_from', function() {
    return 'noreply@' . parse_url( home_url(), PHP_URL_HOST );
});
```

---

## 6. Export/Import Functionality

```php
<?php
/**
 * Xuat/Nhap du lieu tu plugin.
 */

// === EXPORT ===
add_action( 'admin_init', 'af_handle_export' );

function af_handle_export() {
    if ( ! isset( $_GET['af_export'] ) ) return;

    check_admin_referer( 'af_export_data' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );

    $format = sanitize_text_field( $_GET['format'] ?? 'json' );

    global $wpdb;
    $items = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}af_items ORDER BY id ASC",
        ARRAY_A
    );

    if ( $format === 'csv' ) {
        // Export CSV
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="export-' . date('Y-m-d') . '.csv"' );

        $output = fopen( 'php://output', 'w' );

        // Header row
        if ( ! empty( $items ) ) {
            fputcsv( $output, array_keys( $items[0] ) );
        }

        // Data rows
        foreach ( $items as $item ) {
            fputcsv( $output, $item );
        }

        fclose( $output );
        exit;

    } else {
        // Export JSON
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="export-' . date('Y-m-d') . '.json"' );

        echo wp_json_encode( array(
            'plugin'     => 'Advanced Features',
            'version'    => '1.0.0',
            'exported_at' => current_time( 'mysql' ),
            'data'       => $items,
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        exit;
    }
}

// === IMPORT ===
add_action( 'admin_init', 'af_handle_import' );

function af_handle_import() {
    if ( ! isset( $_POST['af_import'] ) ) return;

    check_admin_referer( 'af_import_data' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );

    if ( empty( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
        set_transient( 'af_notice', array( 'error', 'Vui long chon file.' ), 30 );
        return;
    }

    $file = $_FILES['import_file'];

    // Kiem tra loai file
    $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
    if ( ! in_array( $ext, array( 'json', 'csv' ) ) ) {
        set_transient( 'af_notice', array( 'error', 'Chi chap nhan file JSON hoac CSV.' ), 30 );
        return;
    }

    $content = file_get_contents( $file['tmp_name'] );
    $imported = 0;

    global $wpdb;
    $table = $wpdb->prefix . 'af_items';

    if ( $ext === 'json' ) {
        $data = json_decode( $content, true );
        if ( ! $data || ! isset( $data['data'] ) ) {
            set_transient( 'af_notice', array( 'error', 'File JSON khong hop le.' ), 30 );
            return;
        }

        foreach ( $data['data'] as $item ) {
            $wpdb->insert( $table, array(
                'title'       => sanitize_text_field( $item['title'] ?? '' ),
                'description' => sanitize_textarea_field( $item['description'] ?? '' ),
                'status'      => sanitize_text_field( $item['status'] ?? 'draft' ),
                'created_by'  => get_current_user_id(),
            ), array( '%s', '%s', '%s', '%d' ) );
            if ( $wpdb->insert_id ) $imported++;
        }

    } elseif ( $ext === 'csv' ) {
        $handle = fopen( $file['tmp_name'], 'r' );
        $headers = fgetcsv( $handle ); // Doc dong dau (header)

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $item = array_combine( $headers, $row );
            $wpdb->insert( $table, array(
                'title'       => sanitize_text_field( $item['title'] ?? '' ),
                'description' => sanitize_textarea_field( $item['description'] ?? '' ),
                'status'      => sanitize_text_field( $item['status'] ?? 'draft' ),
                'created_by'  => get_current_user_id(),
            ), array( '%s', '%s', '%s', '%d' ) );
            if ( $wpdb->insert_id ) $imported++;
        }

        fclose( $handle );
    }

    set_transient( 'af_notice', array( 'success', "Da nhap thanh cong {$imported} ban ghi." ), 30 );
}
```

---

## 7. Plugin Updates (Custom Update Checker)

```php
<?php
/**
 * Tu dong kiem tra cap nhat tu server rieng (khong qua WordPress.org).
 * Huu ich cho plugin ban (premium) hoac plugin noi bo.
 */

class AF_Plugin_Updater {

    private $plugin_slug;
    private $plugin_file;
    private $update_url;
    private $current_version;

    public function __construct( $plugin_file, $update_url ) {
        $this->plugin_file     = $plugin_file;
        $this->plugin_slug     = plugin_basename( $plugin_file );
        $this->update_url      = $update_url;
        $this->current_version = $this->get_plugin_version();

        // Hook vao he thong update cua WordPress
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'after_update' ), 10, 2 );
    }

    private function get_plugin_version() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data( $this->plugin_file );
        return $plugin_data['Version'];
    }

    /**
     * Kiem tra co ban cap nhat moi khong
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = $this->get_remote_info();
        if ( ! $remote ) {
            return $transient;
        }

        if ( version_compare( $this->current_version, $remote->version, '<' ) ) {
            $transient->response[ $this->plugin_slug ] = (object) array(
                'slug'        => dirname( $this->plugin_slug ),
                'plugin'      => $this->plugin_slug,
                'new_version' => $remote->version,
                'url'         => $remote->homepage ?? '',
                'package'     => $remote->download_url ?? '',
                'tested'      => $remote->tested ?? '',
                'requires'    => $remote->requires ?? '',
            );
        }

        return $transient;
    }

    /**
     * Lay thong tin tu server update
     */
    private function get_remote_info() {
        $remote = get_transient( 'af_update_check' );

        if ( false === $remote ) {
            $response = wp_remote_get( $this->update_url, array(
                'timeout' => 10,
                'headers' => array( 'Accept' => 'application/json' ),
            ));

            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                return false;
            }

            $remote = json_decode( wp_remote_retrieve_body( $response ) );
            set_transient( 'af_update_check', $remote, 12 * HOUR_IN_SECONDS );
        }

        return $remote;
    }

    /**
     * Hien thi thong tin plugin trong popup "View Details"
     */
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) return $result;
        if ( $args->slug !== dirname( $this->plugin_slug ) ) return $result;

        $remote = $this->get_remote_info();
        if ( ! $remote ) return $result;

        return (object) array(
            'name'          => $remote->name ?? '',
            'slug'          => dirname( $this->plugin_slug ),
            'version'       => $remote->version ?? '',
            'author'        => $remote->author ?? '',
            'homepage'      => $remote->homepage ?? '',
            'requires'      => $remote->requires ?? '',
            'tested'        => $remote->tested ?? '',
            'download_link' => $remote->download_url ?? '',
            'sections'      => array(
                'description' => $remote->description ?? '',
                'changelog'   => $remote->changelog ?? '',
            ),
        );
    }

    /**
     * Don dep sau khi update
     */
    public function after_update( $upgrader, $options ) {
        if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
            delete_transient( 'af_update_check' );
        }
    }
}

// Khoi tao
// new AF_Plugin_Updater( __FILE__, 'https://your-server.com/api/plugin-update.json' );

// Server tra ve JSON dang:
// {
//   "name": "My Plugin",
//   "version": "2.0.0",
//   "download_url": "https://your-server.com/plugins/my-plugin-2.0.0.zip",
//   "homepage": "https://your-plugin.com",
//   "requires": "5.8",
//   "tested": "6.4",
//   "author": "Developer",
//   "description": "Mo ta plugin...",
//   "changelog": "<h4>2.0.0</h4><ul><li>Tinh nang moi</li></ul>"
// }
```

---

## 8. Multisite Compatibility

```php
<?php
/**
 * Ho tro WordPress Multisite (nhieu site tren 1 cai dat).
 */

// Kiem tra Multisite
if ( is_multisite() ) {
    // Code chi chay tren Multisite
}

// Network Activate: Chay tren tat ca sites
// Plugin Header: Network: true

// Activation cho Multisite
register_activation_hook( __FILE__, 'af_multisite_activate' );

function af_multisite_activate( $network_wide ) {
    if ( is_multisite() && $network_wide ) {
        // Network activate: chay cho moi site
        $sites = get_sites( array( 'number' => 0 ) );
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            af_single_site_activate();
            restore_current_blog();
        }
    } else {
        // Single site activate
        af_single_site_activate();
    }
}

function af_single_site_activate() {
    // Tao tables, add options cho site nay
    global $wpdb;
    // ... dbDelta ...
}

// Khi site moi duoc tao (Multisite)
add_action( 'wp_insert_site', function( $site ) {
    switch_to_blog( $site->blog_id );
    af_single_site_activate();
    restore_current_blog();
});

// Network Admin Menu
if ( is_multisite() ) {
    add_action( 'network_admin_menu', function() {
        add_menu_page(
            'My Plugin Network Settings',
            'My Plugin',
            'manage_network_options',   // Quyen network admin
            'my-plugin-network',
            'af_network_settings_page',
            'dashicons-admin-generic'
        );
    });
}

// Network Options
// Luu: update_site_option( 'af_network_setting', $value );
// Lay: get_site_option( 'af_network_setting' );
// Xoa: delete_site_option( 'af_network_setting' );
```

---

## 9. Internationalization (i18n)

```php
<?php
/**
 * Da ngon ngu (Internationalization) cho plugin.
 *
 * i18n = "internationalization" (i + 18 ky tu + n)
 * l10n = "localization"
 *
 * So sanh voi Laravel:
 * Laravel: __('messages.welcome'), @lang('messages.welcome')
 * WordPress: __('Welcome', 'text-domain'), _e('Welcome', 'text-domain')
 */

// === LOAD TEXT DOMAIN ===
add_action( 'plugins_loaded', function() {
    /**
     * load_plugin_textdomain() - Load file ngon ngu cua plugin
     *
     * @param string $domain  Text domain (giong Plugin Header: Text Domain)
     * @param string $deprecated  Bo qua
     * @param string $plugin_rel_path  Duong dan tuong doi den thu muc languages
     */
    load_plugin_textdomain(
        'my-plugin',                              // Text domain
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'  // Duong dan
    );
    // Se load file: languages/my-plugin-vi.mo
});

// === CAC HAM DICH ===

// __() - Dich va TRA VE chuoi (dung khi can gan vao bien)
$message = __( 'Xin chao', 'my-plugin' );

// _e() - Dich va ECHO chuoi (dung khi in truc tiep)
echo '<h1>';
_e( 'Tieu de trang', 'my-plugin' );
echo '</h1>';

// esc_html__() - Dich + escape HTML
echo '<p>' . esc_html__( 'Noi dung an toan', 'my-plugin' ) . '</p>';

// esc_html_e() - Dich + escape + echo
echo '<p>';
esc_html_e( 'Noi dung an toan', 'my-plugin' );
echo '</p>';

// esc_attr__() va esc_attr_e() - Dich + escape cho attributes
echo '<input placeholder="' . esc_attr__( 'Nhap ten', 'my-plugin' ) . '">';

// sprintf voi __() - Chuoi co bien
$count = 5;
echo sprintf(
    /* translators: %d la so luong san pham */
    __( 'Co %d san pham', 'my-plugin' ),
    $count
);

// _n() - Chuoi so nhieu (singular/plural)
$count = 3;
echo sprintf(
    _n(
        '%d san pham',       // So it (1)
        '%d san pham',       // So nhieu (>1)
        $count,              // So luong
        'my-plugin'          // Text domain
    ),
    $count
);

// _x() - Dich voi ngu canh (context)
// Khi 1 tu co nhieu nghia
echo _x( 'Post', 'verb - dang bai', 'my-plugin' );   // Dang (dong tu)
echo _x( 'Post', 'noun - bai viet', 'my-plugin' );   // Bai viet (danh tu)

// === TAO FILE NGON NGU ===

// 1. Dung WP-CLI:
// wp i18n make-pot . languages/my-plugin.pot --domain=my-plugin

// 2. Dung Poedit mo file .pot => dich => save ra .po va .mo

// 3. Cau truc file:
// languages/
//   my-plugin.pot         <- Template (goc)
//   my-plugin-vi.po       <- Tieng Viet (text)
//   my-plugin-vi.mo       <- Tieng Viet (compiled, WordPress doc file nay)
//   my-plugin-ja.po       <- Tieng Nhat
//   my-plugin-ja.mo
```

---

## 10. Unit Testing (PHPUnit)

```php
<?php
/**
 * Unit Testing cho WordPress Plugin.
 *
 * WordPress co WP_UnitTestCase ke thua PHPUnit.
 * Moi test chay trong database tam (khong anh huong data thuc).
 *
 * So sanh voi Laravel:
 * Laravel: php artisan test, TestCase, RefreshDatabase
 * WordPress: phpunit, WP_UnitTestCase, setUp/tearDown
 */

// === CAI DAT ===
// Chay lenh nay trong thu muc plugin:
// composer require --dev phpunit/phpunit
// Hoac dung WP-CLI:
// wp scaffold plugin-tests my-plugin

// === FILE: tests/bootstrap.php ===
// <?php
// // Load WordPress test framework
// $_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';
// require_once $_tests_dir . '/includes/functions.php';
//
// // Load plugin
// tests_add_filter( 'muplugins_loaded', function() {
//     require dirname( __DIR__ ) . '/my-plugin.php';
// });
//
// require $_tests_dir . '/includes/bootstrap.php';

// === FILE: tests/test-contact.php ===

/**
 * Test class cho Contact model
 */
class Test_Contact extends WP_UnitTestCase {

    private $table;

    /**
     * setUp chay TRUOC moi test method
     * Moi test co 1 transaction rieng (tu dong rollback)
     */
    public function setUp(): void {
        parent::setUp();

        global $wpdb;
        $this->table = $wpdb->prefix . 'af_contacts';

        // Tao bang test
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$this->table} (
            id int AUTO_INCREMENT,
            name varchar(100),
            email varchar(100),
            PRIMARY KEY (id)
        )" );
    }

    /**
     * tearDown chay SAU moi test method
     */
    public function tearDown(): void {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$this->table}" );
        parent::tearDown();
    }

    /**
     * Test tao contact
     */
    public function test_create_contact() {
        global $wpdb;

        $wpdb->insert( $this->table, array(
            'name'  => 'Nguyen Van A',
            'email' => 'a@test.com',
        ));

        $this->assertGreaterThan( 0, $wpdb->insert_id );

        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $wpdb->insert_id )
        );

        $this->assertEquals( 'Nguyen Van A', $contact->name );
        $this->assertEquals( 'a@test.com', $contact->email );
    }

    /**
     * Test email validation
     */
    public function test_email_validation() {
        $this->assertTrue( is_email( 'test@example.com' ) );
        $this->assertFalse( is_email( 'not-an-email' ) );
        $this->assertFalse( is_email( '' ) );
    }

    /**
     * Test sanitize
     */
    public function test_sanitize_input() {
        $dirty = '<script>alert("xss")</script>Hello';
        $clean = sanitize_text_field( $dirty );
        $this->assertEquals( 'Hello', $clean );
    }

    /**
     * Test permission check
     */
    public function test_admin_permission() {
        // Tao user admin
        $admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );
        $this->assertTrue( current_user_can( 'manage_options' ) );

        // Tao user subscriber
        $sub_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        wp_set_current_user( $sub_id );
        $this->assertFalse( current_user_can( 'manage_options' ) );
    }

    /**
     * Test Custom Post Type
     */
    public function test_product_post_type_registered() {
        $this->assertTrue( post_type_exists( 'product' ) );
    }

    /**
     * Test tao post
     */
    public function test_create_product() {
        $post_id = $this->factory->post->create( array(
            'post_type'  => 'product',
            'post_title' => 'San pham test',
        ));

        $this->assertGreaterThan( 0, $post_id );

        $post = get_post( $post_id );
        $this->assertEquals( 'San pham test', $post->post_title );
        $this->assertEquals( 'product', $post->post_type );
    }

    /**
     * Test REST API
     */
    public function test_rest_endpoint() {
        // Tao admin va set lam current user
        $admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $admin_id );

        $request = new WP_REST_Request( 'GET', '/contacts-api/v1/contacts' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        $this->assertIsArray( $response->get_data() );
    }
}

// Chay test:
// vendor/bin/phpunit
// hoac: vendor/bin/phpunit --filter test_create_contact
```

---

## 11. Packaging va Distribution

### Chuan bi phat hanh

```bash
# 1. Kiem tra code
# - Xoa debug code (var_dump, error_log, console.log)
# - Kiem tra WPCS (WordPress Coding Standards)
composer require --dev wp-coding-standards/wpcs
vendor/bin/phpcs --standard=WordPress my-plugin.php

# 2. Tao file readme.txt (cho WordPress.org)
```

### readme.txt cho WordPress.org

```
=== My Awesome Plugin ===
Contributors: developername
Tags: contacts, crm, management
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mo ta ngan gon plugin (khong qua 150 ky tu).

== Description ==

Mo ta chi tiet ve plugin.

**Tinh nang chinh:**
* Tinh nang 1
* Tinh nang 2
* Tinh nang 3

== Installation ==

1. Upload thu muc `my-plugin` vao `/wp-content/plugins/`
2. Kich hoat plugin trong trang 'Plugins'
3. Vao Settings > My Plugin de cau hinh

== Frequently Asked Questions ==

= Plugin co mien phi khong? =

Co, plugin hoan toan mien phi.

= Lam sao de lien he ho tro? =

Gui email den support@example.com

== Screenshots ==

1. Trang Dashboard
2. Trang Settings
3. Frontend display

== Changelog ==

= 1.0.0 =
* Phat hanh phien ban dau tien

== Upgrade Notice ==

= 1.0.0 =
Phien ban dau tien.
```

### Build Script

```bash
#!/bin/bash
# build.sh - Tao file ZIP de phat hanh

PLUGIN_SLUG="my-awesome-plugin"
VERSION="1.0.0"
BUILD_DIR="build"

# Xoa build cu
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR/$PLUGIN_SLUG

# Copy files can thiet
rsync -av \
  --exclude='build/' \
  --exclude='node_modules/' \
  --exclude='vendor/' \
  --exclude='tests/' \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='phpunit.xml' \
  --exclude='*.md' \
  --exclude='build.sh' \
  . $BUILD_DIR/$PLUGIN_SLUG/

# Cai production dependencies
cd $BUILD_DIR/$PLUGIN_SLUG
composer install --no-dev --optimize-autoloader 2>/dev/null
cd ../..

# Tao ZIP
cd $BUILD_DIR
zip -r "${PLUGIN_SLUG}-${VERSION}.zip" $PLUGIN_SLUG/
cd ..

echo "Da tao: ${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
echo "Kich thuoc: $(du -sh ${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION}.zip | cut -f1)"
```

---

## 12. Best Practices

### Tong hop

```
KIEN TRUC:
[ ] Su dung OOP cho plugin lon
[ ] Tach code thanh nhieu file/classes
[ ] Dung namespaces de tranh xung dot
[ ] Autoloading (Composer PSR-4 hoac spl_autoload_register)

BAO MAT:
[ ] Sanitize moi input
[ ] Escape moi output
[ ] Nonce cho moi form va action
[ ] Capability check truoc moi hanh dong
[ ] Prepare statements cho moi SQL query

PERFORMANCE:
[ ] Chi load code khi can (is_admin, specific hooks)
[ ] Chi load CSS/JS tren trang can
[ ] Cache du lieu nang voi Transients
[ ] Khong query trong loop (N+1 problem)
[ ] Dung index cho custom tables

TUONG THICH:
[ ] Ho tro Multisite
[ ] Ho tro da ngon ngu (i18n)
[ ] Ho tro Gutenberg (show_in_rest = true)
[ ] Test voi PHP versions toi thieu
[ ] Test voi WordPress versions toi thieu

CODE QUALITY:
[ ] Theo WordPress Coding Standards
[ ] Comment code day du
[ ] Unit tests cho logic quan trong
[ ] Khong hardcode duong dan hoac URLs
[ ] Prefix tat ca functions, classes, options, tables

PHAT HANH:
[ ] Co file readme.txt (cho WordPress.org)
[ ] Co file uninstall.php
[ ] Co changelog
[ ] Xoa debug code
[ ] Test tren fresh WordPress install
```

---

## Tham khao

- [Custom Post Types](https://developer.wordpress.org/plugins/post-types/)
- [Taxonomies](https://developer.wordpress.org/plugins/taxonomies/)
- [Meta Boxes](https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/)
- [Cron API](https://developer.wordpress.org/plugins/cron/)
- [Internationalization](https://developer.wordpress.org/plugins/internationalization/)
- [Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
