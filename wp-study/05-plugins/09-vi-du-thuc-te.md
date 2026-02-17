# Bài 9: Ví Dụ Thực Tế - Xây Dựng Plugin WordPress

> **Hướng dẫn step-by-step** xây dựng plugin WordPress hoàn chỉnh.
> Bao gồm: **CRUD**, **Settings Page**, **REST API**, **Custom Post Type**, **Admin Table**.
> Code đầy đủ, copy-paste chạy được ngay.

---

## Mục Lục

1. [Plugin CRUD hoàn chỉnh - Quản lý Liên hệ](#1-plugin-crud-hoàn-chỉnh---quản-lý-liên-hệ)
2. [Plugin Settings Page](#2-plugin-settings-page)
3. [Plugin REST API](#3-plugin-rest-api)
4. [Plugin Custom Post Type + Meta Box](#4-plugin-custom-post-type--meta-box)
5. [Plugin Shortcode nâng cao](#5-plugin-shortcode-nâng-cao)
6. [Plugin Widget](#6-plugin-widget)
7. [Plugin với Cron Job](#7-plugin-với-cron-job)
8. [Kiến trúc Plugin OOP](#8-kiến-trúc-plugin-oop)
9. [Best Practices tổng hợp](#9-best-practices-tổng-hợp)
10. [WP_List_Table - Bảng Admin Chuyên Nghiệp](#10-plugin-wp_list_table---bảng-admin-chuyên-nghiệp)
11. [Gutenberg Custom Block Hoàn Chỉnh](#11-gutenberg-custom-block-hoàn-chỉnh)
12. [Database Migration với Version Tracking](#12-database-migration-với-version-tracking)
13. [WooCommerce Integration - Plugin tích hợp](#13-woocommerce-integration---plugin-tích-hợp)

---

## 1. Plugin CRUD Hoàn Chỉnh - Quản Lý Liên Hệ

### 1.1. File chính: contact-manager.php

```php
<?php
/**
 * Plugin Name: Contact Manager
 * Plugin URI:  https://example.com/contact-manager
 * Description: Plugin quản lý tin nhắn liên hệ với đầy đủ CRUD, admin table, export CSV.
 * Version:     1.0.0
 * Author:      Tên bạn
 * Author URI:  https://example.com
 * License:     GPL v2 or later
 * Text Domain: contact-manager
 * Domain Path: /languages
 */

// Ngăn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hằng số plugin
define( 'CM_VERSION', '1.0.0' );
define( 'CM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * === ACTIVATION: Tạo bảng database ===
 */
register_activation_hook( __FILE__, 'cm_activate' );

function cm_activate() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'contact_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL DEFAULT '',
        email varchar(100) NOT NULL DEFAULT '',
        phone varchar(20) DEFAULT '',
        subject varchar(255) NOT NULL DEFAULT '',
        message text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'new',
        ip_address varchar(45) DEFAULT '',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY status (status),
        KEY email (email),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'cm_db_version', CM_VERSION );
    update_option( 'cm_items_per_page', 20 );
}

/**
 * === DEACTIVATION: Dọn dẹp tạm ===
 */
register_deactivation_hook( __FILE__, 'cm_deactivate' );

function cm_deactivate() {
    wp_clear_scheduled_hook( 'cm_daily_cleanup' );
}

/**
 * === UNINSTALL: Xóa hoàn toàn (file uninstall.php) ===
 * Tạo file uninstall.php riêng - KHÔNG đặt trong deactivation hook
 */

/**
 * === ADMIN MENU ===
 */
add_action( 'admin_menu', 'cm_admin_menu' );

function cm_admin_menu() {
    // Menu chính
    add_menu_page(
        __( 'Quản lý Liên hệ', 'contact-manager' ),
        __( 'Liên hệ', 'contact-manager' ),
        'manage_options',
        'contact-manager',
        'cm_admin_page',
        'dashicons-email-alt',
        26
    );

    // Submenu: Cài đặt
    add_submenu_page(
        'contact-manager',
        __( 'Cài đặt', 'contact-manager' ),
        __( 'Cài đặt', 'contact-manager' ),
        'manage_options',
        'contact-manager-settings',
        'cm_settings_page'
    );
}

/**
 * === ADMIN STYLES & SCRIPTS ===
 */
add_action( 'admin_enqueue_scripts', 'cm_admin_scripts' );

function cm_admin_scripts( $hook ) {
    // Chỉ load trên trang plugin
    if ( strpos( $hook, 'contact-manager' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'cm-admin',
        CM_PLUGIN_URL . 'admin/css/admin.css',
        array(),
        CM_VERSION
    );
}

/**
 * === TRANG ADMIN CHÍNH (Danh sách + CRUD) ===
 */
function cm_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Bạn không có quyền truy cập.', 'contact-manager' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';

    // Xử lý actions
    $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';

    switch ( $action ) {
        case 'view':
            cm_view_message();
            break;

        case 'delete':
            cm_delete_message();
            break;

        case 'bulk':
            cm_bulk_action();
            break;

        case 'export':
            cm_export_csv();
            break;

        default:
            cm_list_messages();
            break;
    }
}

/**
 * DANH SÁCH TIN NHẮN
 */
function cm_list_messages() {
    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';

    // Pagination
    $per_page     = (int) get_option( 'cm_items_per_page', 20 );
    $current_page = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
    $offset       = ( $current_page - 1 ) * $per_page;

    // Filter theo status
    $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
    $where         = '';
    if ( $status_filter ) {
        $where = $wpdb->prepare( 'WHERE status = %s', $status_filter );
    }

    // Search
    $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    if ( $search ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        $search_where = $wpdb->prepare(
            '(name LIKE %s OR email LIKE %s OR subject LIKE %s)',
            $like, $like, $like
        );
        $where = $where ? "{$where} AND {$search_where}" : "WHERE {$search_where}";
    }

    // Query
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    // Đếm theo status
    $counts = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
        OBJECT_K
    );

    $total_pages = ceil( $total / $per_page );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'Quản lý Liên hệ', 'contact-manager' ); ?>
        </h1>

        <!-- Status filter tabs -->
        <ul class="subsubsub">
            <li>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=contact-manager' ) ); ?>"
                   class="<?php echo ! $status_filter ? 'current' : ''; ?>">
                    <?php printf( __( 'Tất cả (%d)', 'contact-manager' ), $total ); ?>
                </a> |
            </li>
            <?php
            $statuses = array(
                'new'      => __( 'Mới', 'contact-manager' ),
                'read'     => __( 'Đã đọc', 'contact-manager' ),
                'replied'  => __( 'Đã trả lời', 'contact-manager' ),
                'resolved' => __( 'Đã xử lý', 'contact-manager' ),
            );
            foreach ( $statuses as $key => $label ) :
                $count = isset( $counts[ $key ] ) ? $counts[ $key ]->count : 0;
            ?>
                <li>
                    <a href="<?php echo esc_url( add_query_arg( 'status', $key ) ); ?>"
                       class="<?php echo $status_filter === $key ? 'current' : ''; ?>">
                        <?php printf( '%s (%d)', $label, $count ); ?>
                    </a>
                    <?php echo $key !== 'resolved' ? '|' : ''; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Search form -->
        <form method="get" action="">
            <input type="hidden" name="page" value="contact-manager">
            <p class="search-box">
                <input type="search" name="s"
                       value="<?php echo esc_attr( $search ); ?>"
                       placeholder="<?php esc_attr_e( 'Tìm theo tên, email, chủ đề...', 'contact-manager' ); ?>">
                <?php submit_button( __( 'Tìm kiếm', 'contact-manager' ), '', '', false ); ?>
            </p>
        </form>

        <!-- Table -->
        <form method="post" id="cm-bulk-form">
            <?php wp_nonce_field( 'cm_bulk_action', 'cm_bulk_nonce' ); ?>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action">
                        <option value=""><?php esc_html_e( 'Hành động hàng loạt', 'contact-manager' ); ?></option>
                        <option value="mark_read"><?php esc_html_e( 'Đánh dấu đã đọc', 'contact-manager' ); ?></option>
                        <option value="mark_resolved"><?php esc_html_e( 'Đánh dấu đã xử lý', 'contact-manager' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Xóa', 'contact-manager' ); ?></option>
                    </select>
                    <?php submit_button( __( 'Áp dụng', 'contact-manager' ), 'action', 'do_bulk', false ); ?>
                </div>

                <!-- Export button -->
                <div class="alignright">
                    <a href="<?php echo esc_url( wp_nonce_url(
                        add_query_arg( 'action', 'export' ),
                        'cm_export'
                    ) ); ?>" class="button">
                        <?php esc_html_e( 'Export CSV', 'contact-manager' ); ?>
                    </a>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th><?php esc_html_e( 'Họ tên', 'contact-manager' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'contact-manager' ); ?></th>
                        <th><?php esc_html_e( 'Chủ đề', 'contact-manager' ); ?></th>
                        <th><?php esc_html_e( 'Trạng thái', 'contact-manager' ); ?></th>
                        <th><?php esc_html_e( 'Ngày gửi', 'contact-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $messages ) : ?>
                        <?php foreach ( $messages as $msg ) : ?>
                            <tr class="<?php echo $msg->status === 'new' ? 'cm-unread' : ''; ?>">
                                <th class="check-column">
                                    <input type="checkbox" name="message_ids[]"
                                           value="<?php echo absint( $msg->id ); ?>">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url( add_query_arg( array(
                                            'action' => 'view',
                                            'id'     => $msg->id,
                                        ) ) ); ?>">
                                            <?php echo esc_html( $msg->name ); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="<?php echo esc_url( add_query_arg( array(
                                                'action' => 'view',
                                                'id'     => $msg->id,
                                            ) ) ); ?>">
                                                <?php esc_html_e( 'Xem', 'contact-manager' ); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="<?php echo esc_url( wp_nonce_url(
                                                add_query_arg( array(
                                                    'action' => 'delete',
                                                    'id'     => $msg->id,
                                                ) ),
                                                'cm_delete_' . $msg->id
                                            ) ); ?>" class="submitdelete"
                                               onclick="return confirm('<?php esc_attr_e( 'Bạn chắc chắn muốn xóa?', 'contact-manager' ); ?>');">
                                                <?php esc_html_e( 'Xóa', 'contact-manager' ); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $msg->email ); ?></td>
                                <td><?php echo esc_html( $msg->subject ); ?></td>
                                <td>
                                    <span class="cm-status cm-status-<?php echo esc_attr( $msg->status ); ?>">
                                        <?php echo esc_html( $statuses[ $msg->status ] ?? $msg->status ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(
                                        date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                                        strtotime( $msg->created_at ) )
                                    ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e( 'Chưa có tin nhắn nào.', 'contact-manager' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $current_page,
                    ) );
                    ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php
}

/**
 * XEM CHI TIẾT TIN NHẮN
 */
function cm_view_message() {
    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';
    $id    = absint( $_GET['id'] ?? 0 );

    $message = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d", $id
    ) );

    if ( ! $message ) {
        wp_die( __( 'Tin nhắn không tồn tại.', 'contact-manager' ) );
    }

    // Cập nhật status thành "read" nếu đang là "new"
    if ( $message->status === 'new' ) {
        $wpdb->update( $table,
            array( 'status' => 'read', 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        $message->status = 'read';
    }

    // Xử lý cập nhật status
    if ( isset( $_POST['cm_update_status'] ) && check_admin_referer( 'cm_update_status_' . $id ) ) {
        $new_status = sanitize_text_field( $_POST['new_status'] );
        $allowed    = array( 'new', 'read', 'replied', 'resolved' );

        if ( in_array( $new_status, $allowed, true ) ) {
            $wpdb->update( $table,
                array( 'status' => $new_status, 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => $id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            $message->status = $new_status;

            echo '<div class="notice notice-success"><p>' .
                 esc_html__( 'Đã cập nhật trạng thái.', 'contact-manager' ) .
                 '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=contact-manager' ) ); ?>"
               class="page-title-action">
                <?php esc_html_e( '← Quay lại danh sách', 'contact-manager' ); ?>
            </a>
        </h1>

        <div class="cm-message-detail" style="max-width:800px; margin-top:20px;">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Họ tên', 'contact-manager' ); ?></th>
                    <td><strong><?php echo esc_html( $message->name ); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Email', 'contact-manager' ); ?></th>
                    <td>
                        <a href="mailto:<?php echo esc_attr( $message->email ); ?>">
                            <?php echo esc_html( $message->email ); ?>
                        </a>
                    </td>
                </tr>
                <?php if ( $message->phone ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Điện thoại', 'contact-manager' ); ?></th>
                    <td><?php echo esc_html( $message->phone ); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e( 'Chủ đề', 'contact-manager' ); ?></th>
                    <td><?php echo esc_html( $message->subject ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Nội dung', 'contact-manager' ); ?></th>
                    <td>
                        <div style="background:#f9f9f9; padding:15px; border-radius:4px;">
                            <?php echo nl2br( esc_html( $message->message ) ); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Ngày gửi', 'contact-manager' ); ?></th>
                    <td><?php echo esc_html(
                        date_i18n( 'l, d/m/Y H:i', strtotime( $message->created_at ) )
                    ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'IP', 'contact-manager' ); ?></th>
                    <td><?php echo esc_html( $message->ip_address ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Trạng thái', 'contact-manager' ); ?></th>
                    <td>
                        <form method="post" style="display:inline-flex; gap:10px; align-items:center;">
                            <?php wp_nonce_field( 'cm_update_status_' . $id ); ?>
                            <select name="new_status">
                                <option value="new" <?php selected( $message->status, 'new' ); ?>>
                                    <?php esc_html_e( 'Mới', 'contact-manager' ); ?>
                                </option>
                                <option value="read" <?php selected( $message->status, 'read' ); ?>>
                                    <?php esc_html_e( 'Đã đọc', 'contact-manager' ); ?>
                                </option>
                                <option value="replied" <?php selected( $message->status, 'replied' ); ?>>
                                    <?php esc_html_e( 'Đã trả lời', 'contact-manager' ); ?>
                                </option>
                                <option value="resolved" <?php selected( $message->status, 'resolved' ); ?>>
                                    <?php esc_html_e( 'Đã xử lý', 'contact-manager' ); ?>
                                </option>
                            </select>
                            <?php submit_button(
                                __( 'Cập nhật', 'contact-manager' ),
                                'secondary', 'cm_update_status', false
                            ); ?>
                        </form>
                    </td>
                </tr>
            </table>

            <!-- Nút Reply bằng email -->
            <p>
                <a href="mailto:<?php echo esc_attr( $message->email ); ?>?subject=Re: <?php echo esc_attr( $message->subject ); ?>"
                   class="button button-primary">
                    <?php esc_html_e( 'Trả lời qua Email', 'contact-manager' ); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

/**
 * XÓA TIN NHẮN
 */
function cm_delete_message() {
    $id = absint( $_GET['id'] ?? 0 );

    // Verify nonce
    check_admin_referer( 'cm_delete_' . $id );

    global $wpdb;
    $wpdb->delete(
        $wpdb->prefix . 'contact_messages',
        array( 'id' => $id ),
        array( '%d' )
    );

    wp_redirect( add_query_arg( array(
        'page'    => 'contact-manager',
        'deleted' => 1,
    ), admin_url( 'admin.php' ) ) );
    exit;
}

/**
 * XỬ LÝ HÀNH ĐỘNG HÀNG LOẠT
 */
function cm_bulk_action() {
    if ( ! isset( $_POST['cm_bulk_nonce'] ) || ! wp_verify_nonce( $_POST['cm_bulk_nonce'], 'cm_bulk_action' ) ) {
        wp_die( __( 'Nonce verification failed.', 'contact-manager' ) );
    }

    $action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
    $ids    = array_map( 'absint', $_POST['message_ids'] ?? array() );

    if ( empty( $action ) || empty( $ids ) ) {
        wp_redirect( admin_url( 'admin.php?page=contact-manager' ) );
        exit;
    }

    global $wpdb;
    $table       = $wpdb->prefix . 'contact_messages';
    $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

    switch ( $action ) {
        case 'mark_read':
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$table} SET status = 'read', updated_at = %s WHERE id IN ({$placeholders})",
                array_merge( array( current_time( 'mysql' ) ), $ids )
            ) );
            break;

        case 'mark_resolved':
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$table} SET status = 'resolved', updated_at = %s WHERE id IN ({$placeholders})",
                array_merge( array( current_time( 'mysql' ) ), $ids )
            ) );
            break;

        case 'delete':
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table} WHERE id IN ({$placeholders})",
                $ids
            ) );
            break;
    }

    wp_redirect( admin_url( 'admin.php?page=contact-manager&updated=1' ) );
    exit;
}

/**
 * EXPORT CSV
 */
function cm_export_csv() {
    check_admin_referer( 'cm_export' );

    global $wpdb;
    $table    = $wpdb->prefix . 'contact_messages';
    $messages = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );

    // Headers cho download
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=contacts-' . date( 'Y-m-d' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );

    // BOM cho Excel đọc đúng UTF-8
    fwrite( $output, "\xEF\xBB\xBF" );

    // Header row
    fputcsv( $output, array( 'ID', 'Họ tên', 'Email', 'SĐT', 'Chủ đề', 'Nội dung', 'Trạng thái', 'IP', 'Ngày gửi' ) );

    // Data rows
    foreach ( $messages as $msg ) {
        fputcsv( $output, array(
            $msg->id,
            $msg->name,
            $msg->email,
            $msg->phone,
            $msg->subject,
            $msg->message,
            $msg->status,
            $msg->ip_address,
            $msg->created_at,
        ) );
    }

    fclose( $output );
    exit;
}

/**
 * HIỂN THỊ THÔNG BÁO ADMIN
 */
add_action( 'admin_notices', 'cm_admin_notices' );

function cm_admin_notices() {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'contact-manager' ) === false ) {
        return;
    }

    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
             esc_html__( 'Đã xóa tin nhắn thành công.', 'contact-manager' ) .
             '</p></div>';
    }

    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
             esc_html__( 'Đã cập nhật thành công.', 'contact-manager' ) .
             '</p></div>';
    }

    // Hiển thị badge tin nhắn mới
    global $wpdb;
    $new_count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}contact_messages WHERE status = 'new'"
    );

    if ( $new_count > 0 ) {
        // Thêm bubble vào menu
        global $menu;
        foreach ( $menu as &$item ) {
            if ( isset( $item[2] ) && $item[2] === 'contact-manager' ) {
                $item[0] .= sprintf(
                    ' <span class="awaiting-mod count-%d"><span class="pending-count">%d</span></span>',
                    $new_count,
                    $new_count
                );
                break;
            }
        }
    }
}
```

**So sánh với Laravel:**

| Plugin Contact Manager | Laravel tương đương |
|------------------------|---------------------|
| `dbDelta()` tạo bảng | `php artisan migrate` |
| `$wpdb->get_results()` | `DB::table('contacts')->get()` |
| `$wpdb->insert()` | `DB::table('contacts')->insert()` |
| `wp_nonce_field()` | `@csrf` trong Blade |
| `current_user_can()` | `Gate::allows()` / middleware |
| `add_menu_page()` | Route + Controller |
| `wp_list_table` style | Filament/Nova resource table |
| `paginate_links()` | `$items->links()` |

---

## 2. Plugin Settings Page

```php
/**
 * SETTINGS PAGE sử dụng WordPress Settings API
 */
add_action( 'admin_init', 'cm_register_settings' );

function cm_register_settings() {
    // Đăng ký settings group
    register_setting(
        'cm_settings_group',           // Option group
        'cm_settings',                  // Option name (lưu dạng array)
        array( 'sanitize_callback' => 'cm_sanitize_settings' )
    );

    // Section: General
    add_settings_section(
        'cm_section_general',
        __( 'Cài đặt chung', 'contact-manager' ),
        function() {
            echo '<p>' . esc_html__( 'Cấu hình cơ bản cho plugin Contact Manager.', 'contact-manager' ) . '</p>';
        },
        'cm-settings'
    );

    // Field: Notification email
    add_settings_field(
        'cm_notification_email',
        __( 'Email nhận thông báo', 'contact-manager' ),
        'cm_field_email',
        'cm-settings',
        'cm_section_general'
    );

    // Field: Items per page
    add_settings_field(
        'cm_items_per_page',
        __( 'Số tin nhắn / trang', 'contact-manager' ),
        'cm_field_per_page',
        'cm-settings',
        'cm_section_general'
    );

    // Section: Form
    add_settings_section(
        'cm_section_form',
        __( 'Cài đặt Form', 'contact-manager' ),
        null,
        'cm-settings'
    );

    // Field: Enable phone field
    add_settings_field(
        'cm_enable_phone',
        __( 'Hiển thị trường SĐT', 'contact-manager' ),
        'cm_field_checkbox',
        'cm-settings',
        'cm_section_form',
        array( 'field' => 'enable_phone', 'label' => __( 'Hiển thị trường số điện thoại trên form', 'contact-manager' ) )
    );

    // Field: Success message
    add_settings_field(
        'cm_success_message',
        __( 'Tin nhắn thành công', 'contact-manager' ),
        'cm_field_textarea',
        'cm-settings',
        'cm_section_form',
        array( 'field' => 'success_message' )
    );

    // Field: Enable reCAPTCHA
    add_settings_field(
        'cm_recaptcha_key',
        __( 'reCAPTCHA Site Key', 'contact-manager' ),
        'cm_field_text',
        'cm-settings',
        'cm_section_form',
        array( 'field' => 'recaptcha_key', 'placeholder' => '6LeIxAcTAAAAAJcZ...' )
    );
}

// === RENDER FIELDS ===

function cm_get_settings() {
    return wp_parse_args( get_option( 'cm_settings', array() ), array(
        'notification_email' => get_option( 'admin_email' ),
        'items_per_page'     => 20,
        'enable_phone'       => 1,
        'success_message'    => __( 'Cảm ơn bạn! Tin nhắn đã được gửi thành công.', 'contact-manager' ),
        'recaptcha_key'      => '',
    ) );
}

function cm_field_email() {
    $settings = cm_get_settings();
    printf(
        '<input type="email" name="cm_settings[notification_email]" value="%s" class="regular-text">
         <p class="description">%s</p>',
        esc_attr( $settings['notification_email'] ),
        esc_html__( 'Email nhận thông báo khi có tin nhắn mới.', 'contact-manager' )
    );
}

function cm_field_per_page() {
    $settings = cm_get_settings();
    printf(
        '<input type="number" name="cm_settings[items_per_page]" value="%d" min="5" max="100" class="small-text">',
        absint( $settings['items_per_page'] )
    );
}

function cm_field_checkbox( $args ) {
    $settings = cm_get_settings();
    $field    = $args['field'];
    printf(
        '<label><input type="checkbox" name="cm_settings[%s]" value="1" %s> %s</label>',
        esc_attr( $field ),
        checked( $settings[ $field ] ?? 0, 1, false ),
        esc_html( $args['label'] )
    );
}

function cm_field_textarea( $args ) {
    $settings = cm_get_settings();
    $field    = $args['field'];
    printf(
        '<textarea name="cm_settings[%s]" rows="3" class="large-text">%s</textarea>',
        esc_attr( $field ),
        esc_textarea( $settings[ $field ] ?? '' )
    );
}

function cm_field_text( $args ) {
    $settings = cm_get_settings();
    $field    = $args['field'];
    printf(
        '<input type="text" name="cm_settings[%s]" value="%s" class="regular-text" placeholder="%s">',
        esc_attr( $field ),
        esc_attr( $settings[ $field ] ?? '' ),
        esc_attr( $args['placeholder'] ?? '' )
    );
}

// === SANITIZE ===
function cm_sanitize_settings( $input ) {
    $sanitized = array();

    $sanitized['notification_email'] = sanitize_email( $input['notification_email'] ?? '' );
    $sanitized['items_per_page']     = absint( $input['items_per_page'] ?? 20 );
    $sanitized['enable_phone']       = isset( $input['enable_phone'] ) ? 1 : 0;
    $sanitized['success_message']    = sanitize_textarea_field( $input['success_message'] ?? '' );
    $sanitized['recaptcha_key']      = sanitize_text_field( $input['recaptcha_key'] ?? '' );

    // Validate
    if ( $sanitized['items_per_page'] < 5 ) {
        $sanitized['items_per_page'] = 5;
    }
    if ( $sanitized['items_per_page'] > 100 ) {
        $sanitized['items_per_page'] = 100;
    }

    return $sanitized;
}

// === RENDER SETTINGS PAGE ===
function cm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Bạn không có quyền truy cập.', 'contact-manager' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'cm_settings_group' );    // Nonce + hidden fields
            do_settings_sections( 'cm-settings' );       // Render sections + fields
            submit_button( __( 'Lưu cài đặt', 'contact-manager' ) );
            ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Hướng dẫn sử dụng', 'contact-manager' ); ?></h2>
        <p><?php esc_html_e( 'Thêm shortcode sau vào bất kỳ trang nào:', 'contact-manager' ); ?></p>
        <code>[contact_form]</code>
        <p><?php esc_html_e( 'Hoặc với tiêu đề tùy chỉnh:', 'contact-manager' ); ?></p>
        <code>[contact_form title="Liên hệ ngay"]</code>
    </div>
    <?php
}
```

---

## 3. Plugin REST API

```php
/**
 * REST API Endpoints cho Contact Manager
 *
 * GET    /wp-json/contact-manager/v1/messages     - Danh sách
 * GET    /wp-json/contact-manager/v1/messages/{id} - Chi tiết
 * POST   /wp-json/contact-manager/v1/messages     - Tạo mới
 * PUT    /wp-json/contact-manager/v1/messages/{id} - Cập nhật status
 * DELETE /wp-json/contact-manager/v1/messages/{id} - Xóa
 * GET    /wp-json/contact-manager/v1/stats         - Thống kê
 */
add_action( 'rest_api_init', 'cm_register_rest_routes' );

function cm_register_rest_routes() {
    $namespace = 'contact-manager/v1';

    // GET /messages - Danh sách (yêu cầu đăng nhập admin)
    register_rest_route( $namespace, '/messages', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'cm_api_list_messages',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
        'args' => array(
            'page' => array(
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'default'           => 20,
                'sanitize_callback' => 'absint',
            ),
            'status' => array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // GET /messages/{id} - Chi tiết
    register_rest_route( $namespace, '/messages/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'cm_api_get_message',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );

    // POST /messages - Tạo mới (public, không cần đăng nhập)
    register_rest_route( $namespace, '/messages', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'cm_api_create_message',
        'permission_callback' => '__return_true',
        'args' => array(
            'name'    => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $value ) {
                    return strlen( $value ) >= 2;
                },
            ),
            'email'   => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
            'phone'   => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'subject' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'message' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'validate_callback' => function( $value ) {
                    return strlen( $value ) >= 10;
                },
            ),
        ),
    ) );

    // PUT /messages/{id} - Cập nhật status
    register_rest_route( $namespace, '/messages/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => 'cm_api_update_message',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );

    // DELETE /messages/{id} - Xóa
    register_rest_route( $namespace, '/messages/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => 'cm_api_delete_message',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );

    // GET /stats - Thống kê
    register_rest_route( $namespace, '/stats', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'cm_api_stats',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );
}

// === API CALLBACKS ===

function cm_api_list_messages( WP_REST_Request $request ) {
    global $wpdb;
    $table    = $wpdb->prefix . 'contact_messages';
    $page     = $request->get_param( 'page' );
    $per_page = min( $request->get_param( 'per_page' ), 100 );
    $status   = $request->get_param( 'status' );
    $offset   = ( $page - 1 ) * $per_page;

    $where = '';
    $params = array();
    if ( $status ) {
        $where = 'WHERE status = %s';
        $params[] = $status;
    }

    $total = (int) $wpdb->get_var(
        $status
            ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $params )
            : "SELECT COUNT(*) FROM {$table}"
    );

    $params[] = $per_page;
    $params[] = $offset;

    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params
        )
    );

    return new WP_REST_Response( array(
        'data'       => $messages,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $per_page,
        'total_pages' => ceil( $total / $per_page ),
    ), 200 );
}

function cm_api_get_message( WP_REST_Request $request ) {
    global $wpdb;
    $id      = absint( $request->get_param( 'id' ) );
    $message = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}contact_messages WHERE id = %d", $id
    ) );

    if ( ! $message ) {
        return new WP_REST_Response( array(
            'message' => 'Không tìm thấy tin nhắn.',
        ), 404 );
    }

    return new WP_REST_Response( $message, 200 );
}

function cm_api_create_message( WP_REST_Request $request ) {
    global $wpdb;

    $data = array(
        'name'       => $request->get_param( 'name' ),
        'email'      => $request->get_param( 'email' ),
        'phone'      => $request->get_param( 'phone' ) ?? '',
        'subject'    => $request->get_param( 'subject' ),
        'message'    => $request->get_param( 'message' ),
        'status'     => 'new',
        'ip_address' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
        'created_at' => current_time( 'mysql' ),
    );

    $result = $wpdb->insert(
        $wpdb->prefix . 'contact_messages',
        $data,
        array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    if ( $result === false ) {
        return new WP_REST_Response( array(
            'message' => 'Có lỗi xảy ra khi lưu tin nhắn.',
        ), 500 );
    }

    $insert_id = $wpdb->insert_id;

    // Gửi email thông báo
    $settings = cm_get_settings();
    wp_mail(
        $settings['notification_email'],
        sprintf( '[%s] Tin nhắn mới: %s', get_bloginfo( 'name' ), $data['subject'] ),
        sprintf( "Từ: %s (%s)\n\n%s", $data['name'], $data['email'], $data['message'] )
    );

    // Trigger custom hook
    do_action( 'cm_message_created', $insert_id, $data );

    return new WP_REST_Response( array(
        'message' => $settings['success_message'],
        'id'      => $insert_id,
    ), 201 );
}

function cm_api_update_message( WP_REST_Request $request ) {
    global $wpdb;
    $id         = absint( $request->get_param( 'id' ) );
    $new_status = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
    $allowed    = array( 'new', 'read', 'replied', 'resolved' );

    if ( ! in_array( $new_status, $allowed, true ) ) {
        return new WP_REST_Response( array(
            'message' => 'Trạng thái không hợp lệ. Cho phép: ' . implode( ', ', $allowed ),
        ), 400 );
    }

    $updated = $wpdb->update(
        $wpdb->prefix . 'contact_messages',
        array( 'status' => $new_status, 'updated_at' => current_time( 'mysql' ) ),
        array( 'id' => $id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    if ( $updated === false ) {
        return new WP_REST_Response( array( 'message' => 'Cập nhật thất bại.' ), 500 );
    }

    return new WP_REST_Response( array(
        'message' => 'Đã cập nhật trạng thái.',
        'status'  => $new_status,
    ), 200 );
}

function cm_api_delete_message( WP_REST_Request $request ) {
    global $wpdb;
    $id = absint( $request->get_param( 'id' ) );

    $deleted = $wpdb->delete(
        $wpdb->prefix . 'contact_messages',
        array( 'id' => $id ),
        array( '%d' )
    );

    if ( $deleted === false ) {
        return new WP_REST_Response( array( 'message' => 'Xóa thất bại.' ), 500 );
    }

    return new WP_REST_Response( array( 'message' => 'Đã xóa tin nhắn.' ), 200 );
}

function cm_api_stats( WP_REST_Request $request ) {
    global $wpdb;
    $table = $wpdb->prefix . 'contact_messages';

    $stats = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
        OBJECT_K
    );

    $total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    $today    = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s",
        current_time( 'Y-m-d' )
    ) );
    $this_week = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
        date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) )
    ) );

    return new WP_REST_Response( array(
        'total'      => $total,
        'today'      => $today,
        'this_week'  => $this_week,
        'by_status'  => $stats,
    ), 200 );
}
```

**Test REST API bằng cURL:**

```bash
# Tạo tin nhắn mới (public, không cần auth)
curl -X POST https://yoursite.com/wp-json/contact-manager/v1/messages \
  -H "Content-Type: application/json" \
  -d '{"name":"Nguyen Van A","email":"a@example.com","subject":"Test","message":"Nội dung test tin nhắn"}'

# Lấy danh sách (cần auth - Application Password)
curl https://yoursite.com/wp-json/contact-manager/v1/messages \
  -u "admin:xxxx xxxx xxxx xxxx"

# Cập nhật status
curl -X PUT https://yoursite.com/wp-json/contact-manager/v1/messages/1 \
  -H "Content-Type: application/json" \
  -u "admin:xxxx xxxx xxxx xxxx" \
  -d '{"status":"replied"}'

# Xem thống kê
curl https://yoursite.com/wp-json/contact-manager/v1/stats \
  -u "admin:xxxx xxxx xxxx xxxx"
```

---

## 4. Plugin Custom Post Type + Meta Box

```php
/**
 * Plugin đăng ký CPT "Sản phẩm" + Meta Box giá + gallery
 * Dùng khi cần quản lý nội dung tùy chỉnh mà không cần WooCommerce
 */

// === ĐĂNG KÝ CPT ===
add_action( 'init', 'myplugin_register_product_cpt' );

function myplugin_register_product_cpt() {
    register_post_type( 'product', array(
        'labels' => array(
            'name'               => __( 'Sản phẩm', 'myplugin' ),
            'singular_name'      => __( 'Sản phẩm', 'myplugin' ),
            'add_new_item'       => __( 'Thêm sản phẩm mới', 'myplugin' ),
            'edit_item'          => __( 'Sửa sản phẩm', 'myplugin' ),
            'all_items'          => __( 'Tất cả sản phẩm', 'myplugin' ),
            'search_items'       => __( 'Tìm sản phẩm', 'myplugin' ),
        ),
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-cart',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'            => array( 'slug' => 'san-pham' ),
        'show_in_rest'       => true,
    ) );

    // Taxonomy: Danh mục sản phẩm
    register_taxonomy( 'product_cat', 'product', array(
        'labels' => array(
            'name'          => __( 'Danh mục SP', 'myplugin' ),
            'singular_name' => __( 'Danh mục', 'myplugin' ),
        ),
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'danh-muc-sp' ),
    ) );
}

// === META BOX: Thông tin sản phẩm ===
add_action( 'add_meta_boxes', 'myplugin_product_meta_boxes' );

function myplugin_product_meta_boxes() {
    add_meta_box(
        'product_details',
        __( 'Chi tiết sản phẩm', 'myplugin' ),
        'myplugin_product_meta_box_html',
        'product',
        'normal',
        'high'
    );
}

function myplugin_product_meta_box_html( $post ) {
    wp_nonce_field( 'myplugin_product_meta', 'myplugin_product_nonce' );

    $price      = get_post_meta( $post->ID, '_product_price', true );
    $sale_price = get_post_meta( $post->ID, '_product_sale_price', true );
    $sku        = get_post_meta( $post->ID, '_product_sku', true );
    $stock      = get_post_meta( $post->ID, '_product_stock', true );
    $featured   = get_post_meta( $post->ID, '_product_featured', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="product_price"><?php esc_html_e( 'Giá (VNĐ)', 'myplugin' ); ?></label></th>
            <td>
                <input type="number" id="product_price" name="product_price"
                       value="<?php echo esc_attr( $price ); ?>"
                       min="0" step="1000" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="product_sale_price"><?php esc_html_e( 'Giá khuyến mãi', 'myplugin' ); ?></label></th>
            <td>
                <input type="number" id="product_sale_price" name="product_sale_price"
                       value="<?php echo esc_attr( $sale_price ); ?>"
                       min="0" step="1000" class="regular-text">
                <p class="description"><?php esc_html_e( 'Để trống nếu không khuyến mãi.', 'myplugin' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="product_sku"><?php esc_html_e( 'Mã SP (SKU)', 'myplugin' ); ?></label></th>
            <td>
                <input type="text" id="product_sku" name="product_sku"
                       value="<?php echo esc_attr( $sku ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="product_stock"><?php esc_html_e( 'Tồn kho', 'myplugin' ); ?></label></th>
            <td>
                <input type="number" id="product_stock" name="product_stock"
                       value="<?php echo esc_attr( $stock ); ?>"
                       min="0" class="small-text">
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Sản phẩm nổi bật', 'myplugin' ); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="product_featured" value="1"
                           <?php checked( $featured, '1' ); ?>>
                    <?php esc_html_e( 'Đánh dấu sản phẩm nổi bật', 'myplugin' ); ?>
                </label>
            </td>
        </tr>
    </table>
    <?php
}

// === SAVE META BOX ===
add_action( 'save_post_product', 'myplugin_save_product_meta', 10, 2 );

function myplugin_save_product_meta( $post_id, $post ) {
    if ( ! isset( $_POST['myplugin_product_nonce'] ) ||
         ! wp_verify_nonce( $_POST['myplugin_product_nonce'], 'myplugin_product_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Sanitize & save
    $fields = array(
        'product_price'      => 'absint',
        'product_sale_price' => 'absint',
        'product_sku'        => 'sanitize_text_field',
        'product_stock'      => 'absint',
    );

    foreach ( $fields as $field => $sanitize ) {
        if ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '' ) {
            update_post_meta( $post_id, '_' . $field, call_user_func( $sanitize, $_POST[ $field ] ) );
        } else {
            delete_post_meta( $post_id, '_' . $field );
        }
    }

    // Checkbox
    update_post_meta(
        $post_id,
        '_product_featured',
        isset( $_POST['product_featured'] ) ? '1' : '0'
    );
}

// === CUSTOM ADMIN COLUMNS ===
add_filter( 'manage_product_posts_columns', function( $columns ) {
    $new = array();
    foreach ( $columns as $key => $value ) {
        $new[ $key ] = $value;
        if ( $key === 'title' ) {
            $new['price'] = __( 'Giá', 'myplugin' );
            $new['stock'] = __( 'Tồn kho', 'myplugin' );
            $new['sku']   = __( 'SKU', 'myplugin' );
        }
    }
    return $new;
} );

add_action( 'manage_product_posts_custom_column', function( $column, $post_id ) {
    switch ( $column ) {
        case 'price':
            $price      = get_post_meta( $post_id, '_product_price', true );
            $sale_price = get_post_meta( $post_id, '_product_sale_price', true );
            if ( $sale_price ) {
                printf(
                    '<del>%s</del> <ins style="color:red">%s</ins>',
                    number_format( $price ) . 'đ',
                    number_format( $sale_price ) . 'đ'
                );
            } elseif ( $price ) {
                echo number_format( $price ) . 'đ';
            } else {
                echo '—';
            }
            break;
        case 'stock':
            $stock = get_post_meta( $post_id, '_product_stock', true );
            if ( $stock !== '' ) {
                $color = ( $stock > 0 ) ? 'green' : 'red';
                printf( '<span style="color:%s">%s</span>', $color, esc_html( $stock ) );
            } else {
                echo '—';
            }
            break;
        case 'sku':
            echo esc_html( get_post_meta( $post_id, '_product_sku', true ) ?: '—' );
            break;
    }
}, 10, 2 );
```

---

## 5. Plugin Shortcode Nâng Cao

```php
/**
 * Shortcode hiển thị sản phẩm dạng grid
 * Dùng: [products category="dien-thoai" limit="6" columns="3"]
 */
add_shortcode( 'products', 'myplugin_products_shortcode' );

function myplugin_products_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'category' => '',
        'limit'    => 6,
        'columns'  => 3,
        'orderby'  => 'date',
        'order'    => 'DESC',
        'featured' => '',       // 'yes' để chỉ lấy sản phẩm nổi bật
    ), $atts, 'products' );

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => absint( $atts['limit'] ),
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
        'order'          => sanitize_text_field( $atts['order'] ),
        'post_status'    => 'publish',
    );

    // Lọc theo category
    if ( $atts['category'] ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_map( 'sanitize_text_field', explode( ',', $atts['category'] ) ),
            ),
        );
    }

    // Lọc sản phẩm nổi bật
    if ( $atts['featured'] === 'yes' ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_product_featured',
                'value' => '1',
            ),
        );
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '<p class="no-products">' . esc_html__( 'Không có sản phẩm nào.', 'myplugin' ) . '</p>';
    }

    $columns = absint( $atts['columns'] );

    ob_start();
    ?>
    <div class="products-grid" style="display:grid; grid-template-columns:repeat(<?php echo $columns; ?>, 1fr); gap:20px;">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <div class="product-card" style="border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                <?php if ( has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail( 'medium', array( 'style' => 'width:100%; height:200px; object-fit:cover;' ) ); ?>
                    </a>
                <?php endif; ?>

                <div style="padding:15px;">
                    <h3 style="margin:0 0 10px; font-size:1rem;">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <?php
                    $price      = get_post_meta( get_the_ID(), '_product_price', true );
                    $sale_price = get_post_meta( get_the_ID(), '_product_sale_price', true );
                    ?>
                    <div class="product-price">
                        <?php if ( $sale_price ) : ?>
                            <del><?php echo number_format( $price ); ?>đ</del>
                            <strong style="color:red"><?php echo number_format( $sale_price ); ?>đ</strong>
                        <?php elseif ( $price ) : ?>
                            <strong><?php echo number_format( $price ); ?>đ</strong>
                        <?php else : ?>
                            <em><?php esc_html_e( 'Liên hệ', 'myplugin' ); ?></em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
```

---

## 6. Plugin Widget

```php
/**
 * Widget hiển thị sản phẩm nổi bật trên sidebar
 */
class MyPlugin_Featured_Products_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'myplugin_featured_products',
            __( 'Sản phẩm nổi bật', 'myplugin' ),
            array( 'description' => __( 'Hiển thị danh sách sản phẩm nổi bật.', 'myplugin' ) )
        );
    }

    // Render widget frontend
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $count = absint( $instance['count'] ?? 5 );

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $query = new WP_Query( array(
            'post_type'      => 'product',
            'posts_per_page' => $count,
            'meta_key'       => '_product_featured',
            'meta_value'     => '1',
            'no_found_rows'  => true,
        ) );

        if ( $query->have_posts() ) {
            echo '<ul class="featured-products-list">';
            while ( $query->have_posts() ) {
                $query->the_post();
                $price = get_post_meta( get_the_ID(), '_product_price', true );
                printf(
                    '<li><a href="%s">%s</a>%s</li>',
                    esc_url( get_permalink() ),
                    esc_html( get_the_title() ),
                    $price ? ' <span class="price">' . number_format( $price ) . 'đ</span>' : ''
                );
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'Chưa có sản phẩm nổi bật.', 'myplugin' ) . '</p>';
        }

        echo $args['after_widget'];
    }

    // Form cài đặt trong admin
    public function form( $instance ) {
        $title = $instance['title'] ?? __( 'Sản phẩm nổi bật', 'myplugin' );
        $count = $instance['count'] ?? 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Tiêu đề:', 'myplugin' ); ?>
            </label>
            <input type="text" class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
                <?php esc_html_e( 'Số sản phẩm:', 'myplugin' ); ?>
            </label>
            <input type="number" class="tiny-text"
                   id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                   value="<?php echo absint( $count ); ?>" min="1" max="20">
        </p>
        <?php
    }

    // Sanitize khi save
    public function update( $new_instance, $old_instance ) {
        return array(
            'title' => sanitize_text_field( $new_instance['title'] ),
            'count' => absint( $new_instance['count'] ),
        );
    }
}

// Đăng ký widget
add_action( 'widgets_init', function() {
    register_widget( 'MyPlugin_Featured_Products_Widget' );
} );
```

---

## 7. Plugin Với Cron Job

```php
/**
 * Plugin gửi email digest hàng ngày về tin nhắn mới
 */

// Lên lịch khi activate
register_activation_hook( __FILE__, function() {
    if ( ! wp_next_scheduled( 'cm_daily_digest' ) ) {
        // Chạy lúc 8:00 sáng mỗi ngày
        $timestamp = strtotime( 'tomorrow 08:00:00' );
        wp_schedule_event( $timestamp, 'daily', 'cm_daily_digest' );
    }
} );

// Xử lý cron
add_action( 'cm_daily_digest', 'cm_send_daily_digest' );

function cm_send_daily_digest() {
    global $wpdb;
    $table    = $wpdb->prefix . 'contact_messages';
    $yesterday = date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );

    // Đếm tin nhắn mới trong 24h qua
    $new_messages = $wpdb->get_results( $wpdb->prepare(
        "SELECT name, email, subject, created_at FROM {$table}
         WHERE created_at >= %s ORDER BY created_at DESC",
        $yesterday
    ) );

    if ( empty( $new_messages ) ) {
        return; // Không có tin nhắn mới → không gửi email
    }

    $settings = cm_get_settings();
    $count    = count( $new_messages );

    $subject = sprintf(
        '[%s] Báo cáo: %d tin nhắn mới trong 24h qua',
        get_bloginfo( 'name' ),
        $count
    );

    $body = "Xin chào,\n\n";
    $body .= sprintf( "Trong 24 giờ qua, bạn nhận được %d tin nhắn mới:\n\n", $count );

    foreach ( $new_messages as $i => $msg ) {
        $body .= sprintf(
            "%d. %s (%s) - %s [%s]\n",
            $i + 1,
            $msg->name,
            $msg->email,
            $msg->subject,
            date_i18n( 'H:i', strtotime( $msg->created_at ) )
        );
    }

    $body .= sprintf(
        "\nXem tất cả: %s\n",
        admin_url( 'admin.php?page=contact-manager' )
    );

    wp_mail( $settings['notification_email'], $subject, $body );
}

// Xóa cron khi deactivate
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'cm_daily_digest' );
} );
```

---

## 8. Kiến Trúc Plugin OOP

```php
/**
 * Cấu trúc plugin OOP với namespace và autoloading
 *
 * Cấu trúc thư mục:
 * my-plugin/
 * ├── my-plugin.php          ← Entry point
 * ├── composer.json           ← Autoload config
 * ├── src/
 * │   ├── Plugin.php          ← Main plugin class
 * │   ├── Admin/
 * │   │   ├── AdminMenu.php   ← Admin menu registration
 * │   │   └── Settings.php    ← Settings page
 * │   ├── Frontend/
 * │   │   ├── Shortcodes.php  ← Shortcode handlers
 * │   │   └── Assets.php      ← Enqueue scripts/styles
 * │   ├── Api/
 * │   │   └── RestController.php ← REST API endpoints
 * │   └── Models/
 * │       └── Contact.php     ← Data model
 * └── templates/
 *     └── contact-form.php    ← Form template
 */

// === my-plugin.php (entry point) ===
namespace MyPlugin;

if ( ! defined( 'ABSPATH' ) ) exit;

// Autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

// Boot plugin
Plugin::instance()->init();

// === src/Plugin.php ===
namespace MyPlugin;

class Plugin {
    private static ?Plugin $instance = null;
    private string $version = '1.0.0';

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // Hooks
        register_activation_hook( CM_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( CM_PLUGIN_FILE, array( $this, 'deactivate' ) );

        // Load components
        add_action( 'init', array( $this, 'load_textdomain' ) );

        if ( is_admin() ) {
            new Admin\AdminMenu();
            new Admin\Settings();
        }

        new Frontend\Shortcodes();
        new Frontend\Assets();
        new Api\RestController();
    }

    public function activate(): void {
        Models\Contact::create_table();
        flush_rewrite_rules();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( CM_PLUGIN_FILE ) ) . '/languages' );
    }

    public function version(): string {
        return $this->version;
    }
}

// === src/Models/Contact.php ===
namespace MyPlugin\Models;

class Contact {
    public int $id;
    public string $name;
    public string $email;
    public string $message;
    public string $status;
    public string $created_at;

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'contact_messages';
    }

    public static function create_table(): void {
        global $wpdb;
        $table           = self::table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            message text NOT NULL,
            status varchar(20) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function find( int $id ): ?self {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id
        ) );

        return $row ? self::from_row( $row ) : null;
    }

    public static function all( array $args = array() ): array {
        global $wpdb;
        $defaults = array(
            'per_page' => 20,
            'page'     => 1,
            'status'   => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );
        $args   = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];
        $where  = $args['status'] ? $wpdb->prepare( 'WHERE status = %s', $args['status'] ) : '';

        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT * FROM ' . self::table() . " {$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $args['per_page'],
            $offset
        ) );

        return array_map( array( self::class, 'from_row' ), $rows );
    }

    public static function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( self::table(), array(
            'name'       => sanitize_text_field( $data['name'] ),
            'email'      => sanitize_email( $data['email'] ),
            'message'    => sanitize_textarea_field( $data['message'] ),
            'status'     => 'new',
            'created_at' => current_time( 'mysql' ),
        ) );

        return (int) $wpdb->insert_id;
    }

    public function update_status( string $status ): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            self::table(),
            array( 'status' => $status ),
            array( 'id' => $this->id )
        );
    }

    public function delete(): bool {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), array( 'id' => $this->id ) );
    }

    private static function from_row( object $row ): self {
        $contact             = new self();
        $contact->id         = (int) $row->id;
        $contact->name       = $row->name;
        $contact->email      = $row->email;
        $contact->message    = $row->message;
        $contact->status     = $row->status;
        $contact->created_at = $row->created_at;
        return $contact;
    }
}
```

**So sánh OOP:**

| WordPress Plugin OOP | Laravel tương đương |
|----------------------|---------------------|
| `Plugin::instance()` (Singleton) | Service Container binding |
| `Models\Contact` class | Eloquent Model |
| `Contact::all()` | `Contact::paginate()` |
| `Contact::find($id)` | `Contact::find($id)` |
| `Contact::create($data)` | `Contact::create($data)` |
| `$contact->delete()` | `$contact->delete()` |
| `Admin\AdminMenu` | `Route::middleware('admin')` |
| `Frontend\Assets` | `mix()` trong webpack |

---

## 9. Best Practices Tổng Hợp

### Checklist bảo mật plugin

```php
// 1. Ngăn truy cập trực tiếp file PHP
if ( ! defined( 'ABSPATH' ) ) exit;

// 2. Verify nonce cho mọi form/AJAX
check_admin_referer( 'my_action', 'my_nonce' );          // Admin form
check_ajax_referer( 'my_action', 'nonce' );               // AJAX

// 3. Kiểm tra capability
if ( ! current_user_can( 'manage_options' ) ) wp_die();

// 4. Sanitize mọi input
$name  = sanitize_text_field( wp_unslash( $_POST['name'] ) );
$email = sanitize_email( $_POST['email'] );
$url   = esc_url_raw( $_POST['url'] );
$html  = wp_kses_post( $_POST['content'] );
$int   = absint( $_POST['id'] );
$text  = sanitize_textarea_field( wp_unslash( $_POST['message'] ) );

// 5. Escape mọi output
echo esc_html( $name );           // Trong text
echo esc_attr( $value );          // Trong attribute
echo esc_url( $url );             // Trong href/src
echo wp_kses_post( $html );       // HTML cho phép tags an toàn

// 6. Prepared statements cho SQL
$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND status = %s", $id, $status );

// 7. Prefix mọi thứ để tránh conflict
// Functions: myplugin_function_name()
// Classes:   MyPlugin_Class_Name hoặc namespace MyPlugin
// Options:   myplugin_option_name
// Meta:      _myplugin_meta_key (prefix _ = hidden)
// DB tables: $wpdb->prefix . 'myplugin_table'
// Hooks:     myplugin_custom_hook
// Nonces:    myplugin_nonce_action
```

### File uninstall.php

```php
<?php
/**
 * Uninstall handler - chạy khi plugin bị XÓA (không phải deactivate)
 *
 * Đây là nơi duy nhất nên xóa data:
 * - Xóa bảng database tùy chỉnh
 * - Xóa options
 * - Xóa post meta, user meta
 * - Xóa files uploaded bởi plugin
 */

// Bảo mật: chỉ chạy khi WordPress gọi
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Xóa bảng tùy chỉnh
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}contact_messages" );

// Xóa options
delete_option( 'cm_settings' );
delete_option( 'cm_db_version' );
delete_option( 'cm_items_per_page' );

// Xóa transients
delete_transient( 'cm_stats_cache' );

// Xóa cron events
wp_clear_scheduled_hook( 'cm_daily_cleanup' );
wp_clear_scheduled_hook( 'cm_daily_digest' );
```

---

## Tổng Kết

| Kỹ thuật | Ví dụ trong bài | Laravel tương đương |
|----------|-----------------|---------------------|
| CRUD | Contact Manager | Resource Controller |
| Settings API | Settings page | Config + Form |
| REST API | 6 endpoints | API Resource Routes |
| Custom Post Type | Sản phẩm CPT | Eloquent Model |
| Meta Box | Chi tiết sản phẩm | Form fields |
| Shortcode | Products grid | Blade Component |
| Widget | Featured Products | Livewire Component |
| Cron Job | Daily digest | Laravel Scheduler |
| OOP Architecture | Namespace + Autoload | Service Provider |
| Security | Nonce, sanitize, escape | CSRF, Validation, XSS |

---

## 10. Plugin WP_List_Table - Bảng Admin Chuyên Nghiệp

> `WP_List_Table` là class WordPress dùng để tạo bảng danh sách trong admin (giống bảng Posts, Users...).
> Hỗ trợ sẵn: pagination, search, sortable columns, bulk actions, column toggle.

```php
/**
 * Tạo bảng admin chuyên nghiệp bằng WP_List_Table
 *
 * Ưu điểm so với viết table HTML thủ công:
 * - Giao diện nhất quán với WordPress core
 * - Pagination, sort, search tự động
 * - Bulk actions có sẵn
 * - Screen Options (ẩn/hiện cột)
 */

// Load WP_List_Table class (chỉ có trong admin)
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CM_Messages_List_Table extends WP_List_Table {

    /**
     * Constructor - Khai báo thông tin bảng
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Tin nhắn', 'contact-manager' ),   // Tên số ít
            'plural'   => __( 'Tin nhắn', 'contact-manager' ),   // Tên số nhiều
            'ajax'     => false,                                    // Không dùng AJAX
        ) );
    }

    /**
     * Định nghĩa các cột
     * Key = column slug, Value = column header text
     */
    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',  // Checkbox cho bulk actions
            'name'       => __( 'Họ tên', 'contact-manager' ),
            'email'      => __( 'Email', 'contact-manager' ),
            'subject'    => __( 'Chủ đề', 'contact-manager' ),
            'status'     => __( 'Trạng thái', 'contact-manager' ),
            'created_at' => __( 'Ngày gửi', 'contact-manager' ),
        );
    }

    /**
     * Các cột cho phép sort
     */
    public function get_sortable_columns() {
        return array(
            'name'       => array( 'name', false ),        // false = chưa sort sẵn
            'email'      => array( 'email', false ),
            'status'     => array( 'status', false ),
            'created_at' => array( 'created_at', true ),   // true = sort mặc định DESC
        );
    }

    /**
     * Bulk actions dropdown
     */
    public function get_bulk_actions() {
        return array(
            'mark_read'     => __( 'Đánh dấu đã đọc', 'contact-manager' ),
            'mark_resolved' => __( 'Đánh dấu đã xử lý', 'contact-manager' ),
            'delete'        => __( 'Xóa', 'contact-manager' ),
        );
    }

    /**
     * Xử lý bulk actions
     */
    public function process_bulk_action() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
            return;
        }

        $action = $this->current_action();
        $ids    = array_map( 'absint', $_POST['message_ids'] ?? array() );

        if ( empty( $ids ) ) return;

        global $wpdb;
        $table        = $wpdb->prefix . 'contact_messages';
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        switch ( $action ) {
            case 'mark_read':
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$table} SET status = 'read' WHERE id IN ({$placeholders})", $ids
                ) );
                break;

            case 'mark_resolved':
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$table} SET status = 'resolved' WHERE id IN ({$placeholders})", $ids
                ) );
                break;

            case 'delete':
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids
                ) );
                break;
        }
    }

    /**
     * Lấy dữ liệu cho bảng
     */
    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'contact_messages';

        // Process bulk actions trước
        $this->process_bulk_action();

        // Columns
        $this->_column_headers = array(
            $this->get_columns(),
            array(),                       // Hidden columns
            $this->get_sortable_columns(),
        );

        // Pagination
        $per_page     = $this->get_items_per_page( 'messages_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        // Search
        $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        $where  = '';
        $params = array();

        if ( $search ) {
            $like   = '%' . $wpdb->esc_like( $search ) . '%';
            $where  = 'WHERE (name LIKE %s OR email LIKE %s OR subject LIKE %s)';
            $params = array( $like, $like, $like );
        }

        // Status filter
        $status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
        if ( $status ) {
            $where   .= $where ? ' AND status = %s' : 'WHERE status = %s';
            $params[] = $status;
        }

        // Sorting
        $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'created_at';
        $order   = isset( $_REQUEST['order'] ) && strtoupper( $_REQUEST['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        // Whitelist orderby columns
        $allowed_orderby = array( 'name', 'email', 'status', 'created_at' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }

        // Total count
        $total = (int) $wpdb->get_var(
            $params
                ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $params )
                : "SELECT COUNT(*) FROM {$table} {$where}"
        );

        // Query
        $params[] = $per_page;
        $params[] = $offset;

        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $params
            ),
            ARRAY_A
        );

        // Set pagination
        $this->set_pagination_args( array(
            'total_items' => $total,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ) );
    }

    /**
     * Render checkbox column
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="message_ids[]" value="%d" />',
            absint( $item['id'] )
        );
    }

    /**
     * Render cột name (với row actions)
     */
    public function column_name( $item ) {
        $view_url = add_query_arg( array(
            'page'   => 'contact-manager',
            'action' => 'view',
            'id'     => $item['id'],
        ), admin_url( 'admin.php' ) );

        $delete_url = wp_nonce_url(
            add_query_arg( array(
                'page'   => 'contact-manager',
                'action' => 'delete',
                'id'     => $item['id'],
            ), admin_url( 'admin.php' ) ),
            'cm_delete_' . $item['id']
        );

        // Row actions (hiện khi hover)
        $actions = array(
            'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), __( 'Xem', 'contact-manager' ) ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                esc_url( $delete_url ),
                esc_js( __( 'Bạn chắc chắn muốn xóa?', 'contact-manager' ) ),
                __( 'Xóa', 'contact-manager' )
            ),
        );

        $name_style = $item['status'] === 'new' ? ' style="font-weight:bold"' : '';

        return sprintf(
            '<strong><a href="%s"%s>%s</a></strong>%s',
            esc_url( $view_url ),
            $name_style,
            esc_html( $item['name'] ),
            $this->row_actions( $actions )
        );
    }

    /**
     * Render cột status
     */
    public function column_status( $item ) {
        $labels = array(
            'new'      => '🔵 ' . __( 'Mới', 'contact-manager' ),
            'read'     => '⚪ ' . __( 'Đã đọc', 'contact-manager' ),
            'replied'  => '🟢 ' . __( 'Đã trả lời', 'contact-manager' ),
            'resolved' => '✅ ' . __( 'Đã xử lý', 'contact-manager' ),
        );
        return $labels[ $item['status'] ] ?? esc_html( $item['status'] );
    }

    /**
     * Render cột created_at
     */
    public function column_created_at( $item ) {
        return esc_html( date_i18n(
            get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
            strtotime( $item['created_at'] )
        ) );
    }

    /**
     * Render cột mặc định (cho các cột không có hàm riêng)
     */
    public function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] ?? '' );
    }

    /**
     * Hiển thị khi không có data
     */
    public function no_items() {
        esc_html_e( 'Chưa có tin nhắn nào.', 'contact-manager' );
    }

    /**
     * Extra navigation (filter tabs)
     */
    protected function extra_tablenav( $which ) {
        if ( $which !== 'top' ) return;

        $current_status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
        ?>
        <div class="alignleft actions">
            <select name="status">
                <option value=""><?php esc_html_e( 'Tất cả trạng thái', 'contact-manager' ); ?></option>
                <option value="new" <?php selected( $current_status, 'new' ); ?>>
                    <?php esc_html_e( 'Mới', 'contact-manager' ); ?>
                </option>
                <option value="read" <?php selected( $current_status, 'read' ); ?>>
                    <?php esc_html_e( 'Đã đọc', 'contact-manager' ); ?>
                </option>
                <option value="replied" <?php selected( $current_status, 'replied' ); ?>>
                    <?php esc_html_e( 'Đã trả lời', 'contact-manager' ); ?>
                </option>
                <option value="resolved" <?php selected( $current_status, 'resolved' ); ?>>
                    <?php esc_html_e( 'Đã xử lý', 'contact-manager' ); ?>
                </option>
            </select>
            <?php submit_button( __( 'Lọc', 'contact-manager' ), '', 'filter_action', false ); ?>
        </div>
        <?php
    }
}

// === SỬ DỤNG WP_LIST_TABLE ===

function cm_admin_page_with_list_table() {
    $list_table = new CM_Messages_List_Table();
    $list_table->prepare_items();

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__( 'Quản lý Liên hệ', 'contact-manager' ) . '</h1>';

    // Search box
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="contact-manager">';
    $list_table->search_box( __( 'Tìm kiếm', 'contact-manager' ), 'cm-search' );
    echo '</form>';

    // Table (cần wrap trong form riêng cho bulk actions)
    echo '<form method="post">';
    $list_table->display();
    echo '</form>';

    echo '</div>';
}
```

**So sánh WP_List_Table với Laravel:**

| WP_List_Table | Laravel tương đương |
|---------------|---------------------|
| `get_columns()` | Filament `$table->columns()` |
| `get_sortable_columns()` | Filament `Tables\Columns\TextColumn::make()->sortable()` |
| `get_bulk_actions()` | Filament `$table->bulkActions()` |
| `prepare_items()` | Controller `index()` + Query Builder |
| `column_name()` | Filament `TextColumn::make('name')` |
| `search_box()` | Filament `$table->searchable()` |
| `set_pagination_args()` | `$items->paginate(20)` |

---

## 11. Gutenberg Custom Block Hoàn Chỉnh

> Custom Gutenberg Block cho phép tạo block editor riêng trong plugin.
> Từ WordPress 5.8+, block development sử dụng `block.json` và `@wordpress/scripts`.

### 11.1. Cấu trúc thư mục block

```
my-plugin/
├── my-plugin.php
├── blocks/
│   └── testimonial/
│       ├── block.json         ← Metadata block
│       ├── edit.js            ← Giao diện trong editor
│       ├── save.js            ← Output HTML frontend
│       ├── index.js           ← Entry point
│       ├── editor.css         ← CSS trong editor
│       └── style.css          ← CSS cả editor + frontend
├── build/                     ← Compiled output (tự tạo khi build)
└── package.json
```

### 11.2. block.json - Khai báo block

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "myplugin/testimonial",
    "version": "1.0.0",
    "title": "Testimonial",
    "category": "widgets",
    "icon": "format-quote",
    "description": "Block hiển thị đánh giá/nhận xét từ khách hàng.",
    "keywords": ["testimonial", "đánh giá", "nhận xét", "review"],
    "supports": {
        "html": false,
        "align": ["wide", "full"],
        "color": {
            "background": true,
            "text": true
        },
        "spacing": {
            "margin": true,
            "padding": true
        }
    },
    "attributes": {
        "content": {
            "type": "string",
            "source": "html",
            "selector": ".testimonial-content"
        },
        "authorName": {
            "type": "string",
            "default": ""
        },
        "authorRole": {
            "type": "string",
            "default": ""
        },
        "authorImage": {
            "type": "string",
            "default": ""
        },
        "authorImageId": {
            "type": "number",
            "default": 0
        },
        "rating": {
            "type": "number",
            "default": 5
        }
    },
    "textdomain": "myplugin",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./editor.css",
    "style": "file:./style.css"
}
```

### 11.3. index.js - Entry point

```javascript
/**
 * Block entry point
 * File: blocks/testimonial/index.js
 */
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

// Đăng ký block
registerBlockType( metadata.name, {
    edit: Edit,
    save: Save,
} );
```

### 11.4. edit.js - Giao diện trong Editor

```javascript
/**
 * Block Edit component - Giao diện khi chỉnh sửa trong editor
 * File: blocks/testimonial/edit.js
 */
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    MediaUpload,
    MediaUploadCheck,
    InspectorControls,
} from '@wordpress/block-editor';
import {
    PanelBody,
    RangeControl,
    TextControl,
    Button,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const {
        content,
        authorName,
        authorRole,
        authorImage,
        authorImageId,
        rating,
    } = attributes;

    const blockProps = useBlockProps( {
        className: 'testimonial-block',
    } );

    // Render sao đánh giá
    const renderStars = () => {
        return '★'.repeat( rating ) + '☆'.repeat( 5 - rating );
    };

    return (
        <>
            {/* Inspector Controls (sidebar phải) */}
            <InspectorControls>
                <PanelBody title={ __( 'Cài đặt Testimonial', 'myplugin' ) }>
                    <RangeControl
                        label={ __( 'Đánh giá (sao)', 'myplugin' ) }
                        value={ rating }
                        onChange={ ( value ) => setAttributes( { rating: value } ) }
                        min={ 1 }
                        max={ 5 }
                    />
                    <TextControl
                        label={ __( 'Tên tác giả', 'myplugin' ) }
                        value={ authorName }
                        onChange={ ( value ) => setAttributes( { authorName: value } ) }
                    />
                    <TextControl
                        label={ __( 'Chức danh', 'myplugin' ) }
                        value={ authorRole }
                        onChange={ ( value ) => setAttributes( { authorRole: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            {/* Block content */}
            <div { ...blockProps }>
                <div className="testimonial-rating">
                    { renderStars() }
                </div>

                <RichText
                    tagName="blockquote"
                    className="testimonial-content"
                    value={ content }
                    onChange={ ( value ) => setAttributes( { content: value } ) }
                    placeholder={ __( 'Nhập nội dung đánh giá...', 'myplugin' ) }
                />

                <div className="testimonial-author">
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={ ( media ) => setAttributes( {
                                authorImage: media.url,
                                authorImageId: media.id,
                            } ) }
                            allowedTypes={ [ 'image' ] }
                            value={ authorImageId }
                            render={ ( { open } ) => (
                                <Button
                                    className="testimonial-avatar-btn"
                                    onClick={ open }
                                >
                                    { authorImage ? (
                                        <img
                                            src={ authorImage }
                                            alt={ authorName }
                                            className="testimonial-avatar"
                                        />
                                    ) : (
                                        __( 'Chọn ảnh', 'myplugin' )
                                    ) }
                                </Button>
                            ) }
                        />
                    </MediaUploadCheck>

                    <div className="testimonial-author-info">
                        <RichText
                            tagName="strong"
                            className="testimonial-author-name"
                            value={ authorName }
                            onChange={ ( value ) => setAttributes( { authorName: value } ) }
                            placeholder={ __( 'Tên khách hàng', 'myplugin' ) }
                        />
                        <RichText
                            tagName="span"
                            className="testimonial-author-role"
                            value={ authorRole }
                            onChange={ ( value ) => setAttributes( { authorRole: value } ) }
                            placeholder={ __( 'Chức danh / Công ty', 'myplugin' ) }
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
```

### 11.5. save.js - Output HTML

```javascript
/**
 * Block Save component - HTML output cho frontend
 * File: blocks/testimonial/save.js
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
    const {
        content,
        authorName,
        authorRole,
        authorImage,
        rating,
    } = attributes;

    const blockProps = useBlockProps.save( {
        className: 'testimonial-block',
    } );

    const stars = '★'.repeat( rating ) + '☆'.repeat( 5 - rating );

    return (
        <div { ...blockProps }>
            <div className="testimonial-rating">{ stars }</div>

            <RichText.Content
                tagName="blockquote"
                className="testimonial-content"
                value={ content }
            />

            <div className="testimonial-author">
                { authorImage && (
                    <img
                        src={ authorImage }
                        alt={ authorName }
                        className="testimonial-avatar"
                    />
                ) }
                <div className="testimonial-author-info">
                    { authorName && (
                        <RichText.Content
                            tagName="strong"
                            className="testimonial-author-name"
                            value={ authorName }
                        />
                    ) }
                    { authorRole && (
                        <RichText.Content
                            tagName="span"
                            className="testimonial-author-role"
                            value={ authorRole }
                        />
                    ) }
                </div>
            </div>
        </div>
    );
}
```

### 11.6. style.css - CSS cho block

```css
/* blocks/testimonial/style.css */
.wp-block-myplugin-testimonial {
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
    border-radius: 8px;
    padding: 30px;
    margin: 20px 0;
}

.testimonial-rating {
    color: #f5a623;
    font-size: 1.2rem;
    margin-bottom: 15px;
    letter-spacing: 2px;
}

.testimonial-content {
    font-size: 1.1rem;
    line-height: 1.7;
    font-style: italic;
    color: #333;
    margin: 0 0 20px;
    border: none;
    padding: 0;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.testimonial-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.testimonial-author-name {
    display: block;
    font-size: 1rem;
    color: #1d2327;
}

.testimonial-author-role {
    display: block;
    font-size: 0.85rem;
    color: #666;
}
```

### 11.7. Đăng ký block trong PHP

```php
/**
 * Đăng ký Gutenberg block từ plugin
 * Đặt trong file plugin chính
 */
add_action( 'init', 'myplugin_register_blocks' );

function myplugin_register_blocks() {
    // Cách 1: Đăng ký từ block.json (khuyến nghị từ WP 5.8+)
    register_block_type( __DIR__ . '/build/blocks/testimonial' );

    // Cách 2: Dynamic block (render bằng PHP)
    register_block_type( 'myplugin/recent-posts', array(
        'api_version'     => 3,
        'editor_script'   => 'myplugin-recent-posts-editor',
        'render_callback' => 'myplugin_render_recent_posts_block',
        'attributes'      => array(
            'postsToShow' => array(
                'type'    => 'number',
                'default' => 5,
            ),
            'showExcerpt' => array(
                'type'    => 'boolean',
                'default' => true,
            ),
        ),
    ) );
}

/**
 * Dynamic block: Render bằng PHP (luôn cập nhật dữ liệu mới nhất)
 */
function myplugin_render_recent_posts_block( $attributes, $content ) {
    $posts_to_show = absint( $attributes['postsToShow'] ?? 5 );
    $show_excerpt  = (bool) ( $attributes['showExcerpt'] ?? true );

    $recent_posts = get_posts( array(
        'numberposts' => $posts_to_show,
        'post_status' => 'publish',
    ) );

    if ( empty( $recent_posts ) ) {
        return '<p>' . esc_html__( 'Chưa có bài viết nào.', 'myplugin' ) . '</p>';
    }

    $html = '<div class="wp-block-myplugin-recent-posts">';
    $html .= '<ul>';

    foreach ( $recent_posts as $post ) {
        $html .= '<li>';
        $html .= sprintf(
            '<a href="%s">%s</a>',
            esc_url( get_permalink( $post ) ),
            esc_html( $post->post_title )
        );
        $html .= '<span class="post-date">' . esc_html( get_the_date( '', $post ) ) . '</span>';

        if ( $show_excerpt && $post->post_excerpt ) {
            $html .= '<p class="post-excerpt">' . esc_html( $post->post_excerpt ) . '</p>';
        }

        $html .= '</li>';
    }

    $html .= '</ul></div>';

    return $html;
}
```

### 11.8. package.json và build commands

```json
{
    "name": "myplugin-blocks",
    "version": "1.0.0",
    "description": "Gutenberg blocks cho My Plugin",
    "scripts": {
        "build": "wp-scripts build",
        "start": "wp-scripts start",
        "format": "wp-scripts format",
        "lint:js": "wp-scripts lint-js"
    },
    "devDependencies": {
        "@wordpress/scripts": "^27.0.0"
    }
}
```

```bash
# Cài đặt dependencies
npm install

# Development (watch mode - tự build khi thay đổi)
npm start

# Production build
npm run build
```

---

## 12. Database Migration với Version Tracking

> Pattern quản lý database schema khi plugin cần upgrade bảng qua các phiên bản.

```php
/**
 * Database Migration System cho plugin
 *
 * Tương đương với Laravel Migrations nhưng đơn giản hơn:
 * - Lưu version hiện tại trong wp_options
 * - So sánh version khi plugin load
 * - Chạy migration nếu cần upgrade
 */

class CM_Database_Migrator {
    private string $current_version = '1.3.0';  // Version mới nhất
    private string $option_key = 'cm_db_version';

    /**
     * Kiểm tra và chạy migration nếu cần
     * Gọi trong plugin activation hook HOẶC admin_init
     */
    public function maybe_migrate(): void {
        $installed_version = get_option( $this->option_key, '0.0.0' );

        if ( version_compare( $installed_version, $this->current_version, '>=' ) ) {
            return; // Đã cập nhật, không cần migrate
        }

        // Chạy từng migration theo thứ tự
        $migrations = array(
            '1.0.0' => 'migrate_100',
            '1.1.0' => 'migrate_110',
            '1.2.0' => 'migrate_120',
            '1.3.0' => 'migrate_130',
        );

        foreach ( $migrations as $version => $method ) {
            if ( version_compare( $installed_version, $version, '<' ) ) {
                error_log( "[CM] Running migration: {$version}" );
                $this->$method();
                update_option( $this->option_key, $version );
            }
        }

        // Flush rewrite rules sau migration
        flush_rewrite_rules();
    }

    /**
     * v1.0.0 - Tạo bảng ban đầu
     */
    private function migrate_100(): void {
        global $wpdb;
        $table           = $wpdb->prefix . 'contact_messages';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            phone varchar(20) DEFAULT '',
            subject varchar(255) NOT NULL DEFAULT '',
            message text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'new',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * v1.1.0 - Thêm cột ip_address và index email
     */
    private function migrate_110(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'contact_messages';

        // dbDelta có thể thêm cột mới
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            phone varchar(20) DEFAULT '',
            subject varchar(255) NOT NULL DEFAULT '',
            message text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'new',
            ip_address varchar(45) DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY email (email)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Set default options mới
        add_option( 'cm_notification_email', get_option( 'admin_email' ) );
    }

    /**
     * v1.2.0 - Thêm cột updated_at, thêm index created_at
     */
    private function migrate_120(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'contact_messages';

        // Kiểm tra cột tồn tại trước khi thêm
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$table} LIKE %s",
                'updated_at'
            )
        );

        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN updated_at datetime DEFAULT NULL AFTER created_at" );
        }

        // Thêm index
        $index_exists = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'created_at'" );
        if ( empty( $index_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD INDEX created_at (created_at)" );
        }
    }

    /**
     * v1.3.0 - Tạo bảng mới cho attachments
     */
    private function migrate_130(): void {
        global $wpdb;
        $table           = $wpdb->prefix . 'cm_attachments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            message_id bigint(20) unsigned NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_type varchar(100) DEFAULT '',
            file_size bigint(20) DEFAULT 0,
            uploaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY message_id (message_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Data migration: cập nhật records cũ
        $main_table = $wpdb->prefix . 'contact_messages';
        $wpdb->query(
            "UPDATE {$main_table} SET updated_at = created_at WHERE updated_at IS NULL"
        );
    }

    /**
     * Rollback - Xóa tất cả (dùng trong uninstall.php)
     */
    public static function uninstall(): void {
        global $wpdb;

        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cm_attachments" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}contact_messages" );

        delete_option( 'cm_db_version' );
        delete_option( 'cm_settings' );
        delete_option( 'cm_notification_email' );
    }
}

// === Sử dụng ===

// Khi plugin activate
register_activation_hook( __FILE__, function() {
    $migrator = new CM_Database_Migrator();
    $migrator->maybe_migrate();
} );

// Kiểm tra migration khi admin load (cho trường hợp update plugin qua FTP)
add_action( 'admin_init', function() {
    $migrator = new CM_Database_Migrator();
    $migrator->maybe_migrate();
} );
```

**So sánh với Laravel:**

| WordPress Migration | Laravel Migration |
|--------------------|-------------------|
| `dbDelta($sql)` | `Schema::create()` |
| `version_compare()` | Migration timestamp ordering |
| `update_option('db_version')` | `migrations` table |
| `$wpdb->query('ALTER TABLE')` | `Schema::table()->addColumn()` |
| `uninstall.php` | `php artisan migrate:rollback` |
| `admin_init` check | Auto-migration on deploy |

---

## 13. WooCommerce Integration - Plugin Tích Hợp

> Ví dụ đầy đủ về cách plugin tích hợp với WooCommerce:
> **Product Data Tab**, **Order Hooks**, **WooCommerce Settings Tab**, **Cart Validation**.
> So sánh Laravel: Giống Service Provider tích hợp với package bên thứ 3.

### 13.1. Class WooCommerce Integration

```php
<?php
/**
 * File: includes/class-cm-woocommerce.php
 *
 * Plugin tích hợp WooCommerce - Pattern chuẩn:
 * 1. Kiểm tra WooCommerce có active không trước khi hook
 * 2. Hook trên plugins_loaded (sau khi WooCommerce loaded)
 * 3. Dùng guard clause để tránh fatal error
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class CM_WooCommerce_Integration {

    public function __construct() {
        // QUAN TRỌNG: Chỉ hook nếu WooCommerce active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $this->init_hooks();
    }

    private function init_hooks() {
        // --- Product Data Tab (Admin) ---
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'product_tab_content' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );

        // --- Order Hooks ---
        add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'custom_thankyou' ) );

        // --- Cart Validation ---
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_cart' ), 10, 3 );

        // --- WooCommerce Settings Tab ---
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_cm_plugin', array( $this, 'settings_tab_content' ) );
        add_action( 'woocommerce_update_options_cm_plugin', array( $this, 'save_settings' ) );

        // --- Hiển thị trên Single Product ---
        add_action( 'woocommerce_single_product_summary', array( $this, 'display_custom_field' ), 25 );

        // --- Admin Product List Column ---
        add_filter( 'manage_edit-product_columns', array( $this, 'add_product_column' ) );
        add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), 10, 2 );
    }

    // =========================================================================
    // PRODUCT DATA TAB - Thêm tab vào trang edit sản phẩm
    // =========================================================================

    /**
     * Thêm tab mới vào Product Data metabox
     * Xuất hiện cạnh: General, Inventory, Shipping, Linked Products...
     */
    public function add_product_tab( $tabs ) {
        $tabs['cm_plugin_tab'] = array(
            'label'    => __( 'Contact Manager', 'contact-manager' ),
            'target'   => 'cm_plugin_product_data',      // ID của panel div
            'class'    => array( 'show_if_simple', 'show_if_variable' ),
            'priority' => 70,
        );
        return $tabs;
    }

    /**
     * Nội dung tab - Các fields tùy chỉnh
     * WooCommerce cung cấp helper functions: woocommerce_wp_text_input, woocommerce_wp_select...
     */
    public function product_tab_content() {
        ?>
        <div id="cm_plugin_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // Text field
                woocommerce_wp_text_input( array(
                    'id'          => '_cm_product_code',
                    'label'       => __( 'Mã sản phẩm nội bộ', 'contact-manager' ),
                    'placeholder' => 'VD: SP-001',
                    'desc_tip'    => true,
                    'description' => __( 'Mã sản phẩm dùng nội bộ trong hệ thống.', 'contact-manager' ),
                ) );

                // Select field
                woocommerce_wp_select( array(
                    'id'      => '_cm_product_priority',
                    'label'   => __( 'Mức ưu tiên', 'contact-manager' ),
                    'options' => array(
                        ''       => __( '-- Chọn --', 'contact-manager' ),
                        'high'   => __( 'Cao', 'contact-manager' ),
                        'medium' => __( 'Trung bình', 'contact-manager' ),
                        'low'    => __( 'Thấp', 'contact-manager' ),
                    ),
                ) );

                // Checkbox
                woocommerce_wp_checkbox( array(
                    'id'          => '_cm_requires_approval',
                    'label'       => __( 'Cần phê duyệt', 'contact-manager' ),
                    'description' => __( 'Đơn hàng chứa sản phẩm này cần admin phê duyệt.', 'contact-manager' ),
                ) );

                // Textarea
                woocommerce_wp_textarea_input( array(
                    'id'          => '_cm_internal_notes',
                    'label'       => __( 'Ghi chú nội bộ', 'contact-manager' ),
                    'desc_tip'    => true,
                    'description' => __( 'Ghi chú chỉ admin nhìn thấy.', 'contact-manager' ),
                ) );
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Lưu dữ liệu tab khi save product
     */
    public function save_product_data( $post_id ) {
        $product = wc_get_product( $post_id );

        // Text
        $code = sanitize_text_field( wp_unslash( $_POST['_cm_product_code'] ?? '' ) );
        $product->update_meta_data( '_cm_product_code', $code );

        // Select
        $priority = sanitize_text_field( wp_unslash( $_POST['_cm_product_priority'] ?? '' ) );
        $product->update_meta_data( '_cm_product_priority', $priority );

        // Checkbox
        $approval = isset( $_POST['_cm_requires_approval'] ) ? 'yes' : 'no';
        $product->update_meta_data( '_cm_requires_approval', $approval );

        // Textarea
        $notes = sanitize_textarea_field( wp_unslash( $_POST['_cm_internal_notes'] ?? '' ) );
        $product->update_meta_data( '_cm_internal_notes', $notes );

        $product->save();
    }

    // =========================================================================
    // ORDER HOOKS - Xử lý đơn hàng
    // =========================================================================

    /**
     * Khi đơn hàng hoàn thành
     * So sánh Laravel: Event OrderCompleted → Listener ProcessOrder
     */
    public function on_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;

            // Kiểm tra product có cần phê duyệt không
            if ( 'yes' === $product->get_meta( '_cm_requires_approval' ) ) {
                $order->add_order_note( sprintf(
                    __( 'Sản phẩm "%s" cần phê duyệt (Mã: %s)', 'contact-manager' ),
                    $item->get_name(),
                    $product->get_meta( '_cm_product_code' )
                ) );

                // Gửi email thông báo admin
                wp_mail(
                    get_option( 'admin_email' ),
                    sprintf( 'Đơn hàng #%d cần phê duyệt', $order_id ),
                    sprintf( 'Sản phẩm "%s" trong đơn #%d cần bạn phê duyệt.', $item->get_name(), $order_id )
                );
            }
        }
    }

    /**
     * Hiển thị thông tin custom trên trang Thank You
     */
    public function custom_thankyou( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        // Kiểm tra nếu có sản phẩm cần phê duyệt
        $needs_approval = false;
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product && 'yes' === $product->get_meta( '_cm_requires_approval' ) ) {
                $needs_approval = true;
                break;
            }
        }

        if ( $needs_approval ) {
            printf(
                '<div class="woocommerce-message" style="border-left-color: #f0ad4e;">
                    <strong>%s</strong><br>%s
                </div>',
                esc_html__( 'Đơn hàng đang chờ phê duyệt', 'contact-manager' ),
                esc_html__( 'Đơn hàng của bạn chứa sản phẩm cần admin phê duyệt. Chúng tôi sẽ liên hệ sớm nhất.', 'contact-manager' )
            );
        }
    }

    // =========================================================================
    // CART VALIDATION - Kiểm tra trước khi thêm vào giỏ
    // =========================================================================

    /**
     * Validate trước khi add to cart
     * Return false + wc_add_notice() để chặn
     */
    public function validate_cart( $passed, $product_id, $quantity ) {
        $product = wc_get_product( $product_id );

        // VD: Sản phẩm cần phê duyệt chỉ được mua 1
        if ( $product && 'yes' === $product->get_meta( '_cm_requires_approval' ) && $quantity > 1 ) {
            wc_add_notice(
                __( 'Sản phẩm này cần phê duyệt, chỉ được đặt tối đa 1 đơn vị.', 'contact-manager' ),
                'error'
            );
            return false;
        }

        return $passed;
    }

    // =========================================================================
    // WOOCOMMERCE SETTINGS TAB - Tab cài đặt riêng
    // =========================================================================

    /**
     * Thêm tab vào WooCommerce → Settings
     * Xuất hiện cạnh: General, Products, Shipping, Payments...
     */
    public function add_settings_tab( $tabs ) {
        $tabs['cm_plugin'] = __( 'Contact Manager', 'contact-manager' );
        return $tabs;
    }

    /**
     * Nội dung tab Settings
     * Dùng WooCommerce Settings API (khác WordPress Settings API)
     */
    public function settings_tab_content() {
        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Lưu settings
     */
    public function save_settings() {
        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Định nghĩa settings fields
     * WooCommerce Settings API format (khác register_setting của WordPress)
     */
    private function get_settings() {
        return array(
            // Section title
            'section_title' => array(
                'name' => __( 'Cài đặt Contact Manager', 'contact-manager' ),
                'type' => 'title',
                'desc' => __( 'Cấu hình tích hợp Contact Manager với WooCommerce.', 'contact-manager' ),
                'id'   => 'cm_woo_section_title',
            ),
            // Checkbox
            'enable_approval' => array(
                'name'    => __( 'Bật phê duyệt', 'contact-manager' ),
                'type'    => 'checkbox',
                'desc'    => __( 'Tự động yêu cầu phê duyệt cho đơn hàng có sản phẩm đặc biệt.', 'contact-manager' ),
                'id'      => 'cm_woo_enable_approval',
                'default' => 'yes',
            ),
            // Text
            'notification_email' => array(
                'name'     => __( 'Email thông báo', 'contact-manager' ),
                'type'     => 'email',
                'desc'     => __( 'Email nhận thông báo phê duyệt (để trống = dùng email admin).', 'contact-manager' ),
                'id'       => 'cm_woo_notification_email',
                'desc_tip' => true,
            ),
            // Select
            'default_priority' => array(
                'name'    => __( 'Mức ưu tiên mặc định', 'contact-manager' ),
                'type'    => 'select',
                'desc'    => __( 'Mức ưu tiên mặc định cho sản phẩm mới.', 'contact-manager' ),
                'id'      => 'cm_woo_default_priority',
                'options' => array(
                    'high'   => __( 'Cao', 'contact-manager' ),
                    'medium' => __( 'Trung bình', 'contact-manager' ),
                    'low'    => __( 'Thấp', 'contact-manager' ),
                ),
                'default' => 'medium',
            ),
            // Section end
            'section_end' => array(
                'type' => 'sectionend',
                'id'   => 'cm_woo_section_end',
            ),
        );
    }

    // =========================================================================
    // FRONTEND DISPLAY - Hiển thị trên trang sản phẩm
    // =========================================================================

    /**
     * Hiển thị custom field trên single product
     * Hook tại priority 25 (sau giá ở 10, trước add-to-cart ở 30)
     */
    public function display_custom_field() {
        global $product;

        $code = $product->get_meta( '_cm_product_code' );
        if ( ! empty( $code ) ) {
            printf(
                '<div class="cm-product-code" style="margin:10px 0; padding:8px 12px; background:#f7f7f7; border-radius:4px;">
                    <strong>%s:</strong> %s
                </div>',
                esc_html__( 'Mã sản phẩm', 'contact-manager' ),
                esc_html( $code )
            );
        }
    }

    // =========================================================================
    // ADMIN LIST COLUMN - Cột trong danh sách Products
    // =========================================================================

    public function add_product_column( $columns ) {
        // Chèn cột mới sau cột 'name'
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'name' === $key ) {
                $new_columns['cm_code'] = __( 'Mã nội bộ', 'contact-manager' );
            }
        }
        return $new_columns;
    }

    public function render_product_column( $column, $post_id ) {
        if ( 'cm_code' !== $column ) return;

        $product = wc_get_product( $post_id );
        $code    = $product ? $product->get_meta( '_cm_product_code' ) : '';
        echo esc_html( $code ?: '—' );
    }
}

// === Khởi tạo trên plugins_loaded (sau khi WooCommerce đã load) ===
add_action( 'plugins_loaded', function() {
    new CM_WooCommerce_Integration();
}, 20 ); // Priority 20 = sau WooCommerce (priority 10)
```

**So sánh WooCommerce API vs WordPress API:**

| Tính năng | WordPress API | WooCommerce API |
|-----------|---------------|-----------------|
| Settings | `register_setting()` + `add_settings_field()` | `woocommerce_admin_fields()` + `woocommerce_update_options()` |
| Meta fields | `add_meta_box()` + manual HTML | `woocommerce_wp_text_input()`, `woocommerce_wp_select()` |
| Save meta | `update_post_meta()` | `$product->update_meta_data()` + `$product->save()` |
| Validation | `admin_notices` | `wc_add_notice( $msg, 'error' )` |
| Get object | `get_post()` | `wc_get_product()`, `wc_get_order()` |

**Pattern tích hợp an toàn với WooCommerce:**

```php
// ✅ ĐÚNG - Kiểm tra trước khi dùng
if ( class_exists( 'WooCommerce' ) ) {
    // Code dùng WooCommerce functions
}

// ✅ ĐÚNG - Hook trên plugins_loaded
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        new My_WooCommerce_Integration();
    }
}, 20 );

// ❌ SAI - Gọi trực tiếp (Fatal error nếu WooCommerce không active)
$product = wc_get_product( 123 );

// ❌ SAI - Hook quá sớm (WooCommerce chưa loaded)
add_action( 'init', function() {
    // wc_get_product() chưa tồn tại!
} );
```

---

[← Quay lại: Plugin nâng cao](./08-plugin-nang-cao.md) | [↑ Mục lục Plugin](./index.md) | [→ Tiếp: Quản trị WordPress](../06-admin/)
