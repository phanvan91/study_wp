<?php
/**
 * API Quản trị Lõi
 *
 * @package WordPress
 * @subpackage Administration
 * @since 2.3.0
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	/*
	 * File này đang được include từ một file khác ngoài wp-admin/admin.php, nên
	 * một số thiết lập đã bị bỏ qua. Đảm bảo catalog thông báo admin được tải vì
	 * load_default_textdomain() sẽ không thực hiện điều đó trong ngữ cảnh này.
	 */
	$admin_locale = get_locale();
	load_textdomain( 'default', WP_LANG_DIR . '/admin-' . $admin_locale . '.mo', $admin_locale );
	unset( $admin_locale );
}

/** Hook Quản trị WordPress */
require_once ABSPATH . 'wp-admin/includes/admin-filters.php';

/** API Quản trị Bookmark WordPress */
require_once ABSPATH . 'wp-admin/includes/bookmark.php';

/** API Quản trị Bình luận WordPress */
require_once ABSPATH . 'wp-admin/includes/comment.php';

/** API File Quản trị WordPress */
require_once ABSPATH . 'wp-admin/includes/file.php';

/** API Quản trị Hình ảnh WordPress */
require_once ABSPATH . 'wp-admin/includes/image.php';

/** API Quản trị Media WordPress */
require_once ABSPATH . 'wp-admin/includes/media.php';

/** API Quản trị Nhập khẩu WordPress */
require_once ABSPATH . 'wp-admin/includes/import.php';

/** API Quản trị Tiện ích WordPress */
require_once ABSPATH . 'wp-admin/includes/misc.php';

/** API Quản trị Tiện ích WordPress */
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';

/** API Quản trị Tùy chọn WordPress */
require_once ABSPATH . 'wp-admin/includes/options.php';

/** API Quản trị Plugin WordPress */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/** API Quản trị Bài viết WordPress */
require_once ABSPATH . 'wp-admin/includes/post.php';

/** API Màn hình Quản trị WordPress */
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';

/** API Quản trị Phân loại WordPress */
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

/** API Quản trị Template WordPress */
require_once ABSPATH . 'wp-admin/includes/template.php';

/** API và lớp cơ sở Quản trị Bảng Danh sách WordPress */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
require_once ABSPATH . 'wp-admin/includes/list-table.php';

/** API Quản trị Giao diện WordPress */
require_once ABSPATH . 'wp-admin/includes/theme.php';

/** Các hàm Quyền riêng tư WordPress */
require_once ABSPATH . 'wp-admin/includes/privacy-tools.php';

/** Các lớp Bảng Danh sách Quyền riêng tư WordPress. */
// Trước đây nằm trong wp-admin/includes/user.php. Cần được tải để tương thích ngược.
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-requests-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-export-requests-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-removal-requests-list-table.php';

/** API Quản trị Người dùng WordPress */
require_once ABSPATH . 'wp-admin/includes/user.php';

/** API Biểu tượng Trang WordPress */
require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';

/** API Quản trị Cập nhật WordPress */
require_once ABSPATH . 'wp-admin/includes/update.php';

/** API Quản trị Không dùng nữa WordPress */
require_once ABSPATH . 'wp-admin/includes/deprecated.php';

/** API Hỗ trợ Multisite WordPress */
if ( is_multisite() ) {
	require_once ABSPATH . 'wp-admin/includes/ms-admin-filters.php';
	require_once ABSPATH . 'wp-admin/includes/ms.php';
	require_once ABSPATH . 'wp-admin/includes/ms-deprecated.php';
}
