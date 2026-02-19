<?php
/**
 * Plugin có thể nạp file này để truy cập các hàm trợ giúp đặc biệt
 * cho việc cài đặt plugin. File này không được WordPress tự động include và
 * khuyến nghị sử dụng require_once để tránh lỗi nghiêm trọng.
 *
 * Các hàm này không được tối ưu hóa về tốc độ, nhưng chúng chỉ nên được sử dụng
 * thỉnh thoảng, nên tốc độ không phải là vấn đề. Nếu bạn cần sử dụng
 * các hàm này nhiều, bạn có thể gặp timeout.
 * Khi đó, khuyến nghị tự viết mã SQL.
 *
 *     check_column( 'wp_links', 'link_description', 'mediumtext' );
 *
 *     if ( check_column( $wpdb->comments, 'comment_author', 'tinytext' ) ) {
 *         echo "ok\n";
 *     }
 *
 *     // Kiểm tra cột.
 *     if ( ! check_column( $wpdb->links, 'link_description', 'varchar( 255 )' ) ) {
 *         $ddl = "ALTER TABLE $wpdb->links MODIFY COLUMN link_description varchar(255) NOT NULL DEFAULT '' ";
 *         $q = $wpdb->query( $ddl );
 *     }
 *
 *     $error_count = 0;
 *     $tablename   = $wpdb->links;
 *
 *     if ( check_column( $wpdb->links, 'link_description', 'varchar( 255 )' ) ) {
 *         $res .= $tablename . ' - ok <br />';
 *     } else {
 *         $res .= 'There was a problem with ' . $tablename . '<br />';
 *         ++$error_count;
 *     }
 *
 * @package WordPress
 * @subpackage Plugin
 */

/** Nạp Bootstrap WordPress */
require_once dirname( __DIR__ ) . '/wp-load.php';

if ( ! function_exists( 'maybe_create_table' ) ) :
	/**
	 * Tạo bảng trong cơ sở dữ liệu nếu chưa tồn tại.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param string $table_name Tên bảng cơ sở dữ liệu.
	 * @param string $create_ddl Câu lệnh SQL để tạo bảng.
	 * @return bool True nếu thành công hoặc bảng đã tồn tại. False nếu thất bại.
	 */
	function maybe_create_table( $table_name, $create_ddl ) {
		global $wpdb;

		foreach ( $wpdb->get_col( 'SHOW TABLES', 0 ) as $table ) {
			if ( $table === $table_name ) {
				return true;
			}
		}

		// Không tìm thấy, nên thử tạo mới.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Không có biến áp dụng cho truy vấn này.
		$wpdb->query( $create_ddl );

		// Không thể biết trực tiếp liệu thao tác có thành công không!
		foreach ( $wpdb->get_col( 'SHOW TABLES', 0 ) as $table ) {
			if ( $table === $table_name ) {
				return true;
			}
		}

		return false;
	}
endif;

if ( ! function_exists( 'maybe_add_column' ) ) :
	/**
	 * Thêm cột vào bảng cơ sở dữ liệu, nếu chưa tồn tại.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param string $table_name  Tên bảng cơ sở dữ liệu.
	 * @param string $column_name Tên cột của bảng.
	 * @param string $create_ddl  Câu lệnh SQL để thêm cột.
	 * @return bool True nếu thành công hoặc cột đã tồn tại. False nếu thất bại.
	 */
	function maybe_add_column( $table_name, $column_name, $create_ddl ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Không thể chuẩn bị. Lấy danh sách cột cho tên bảng.
		foreach ( $wpdb->get_col( "DESC $table_name", 0 ) as $column ) {
			if ( $column === $column_name ) {
				return true;
			}
		}

		// Không tìm thấy, nên thử tạo mới.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Không có biến áp dụng cho truy vấn này.
		$wpdb->query( $create_ddl );

		// Không thể biết trực tiếp liệu thao tác có thành công không!
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Không thể chuẩn bị. Lấy danh sách cột cho tên bảng.
		foreach ( $wpdb->get_col( "DESC $table_name", 0 ) as $column ) {
			if ( $column === $column_name ) {
				return true;
			}
		}

		return false;
	}
endif;

/**
 * Xóa cột khỏi bảng cơ sở dữ liệu, nếu nó tồn tại.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $table_name  Tên bảng cơ sở dữ liệu.
 * @param string $column_name Tên cột của bảng.
 * @param string $drop_ddl    Câu lệnh SQL để xóa cột.
 * @return bool True nếu thành công hoặc cột không tồn tại. False nếu thất bại.
 */
function maybe_drop_column( $table_name, $column_name, $drop_ddl ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Không thể chuẩn bị. Lấy danh sách cột cho tên bảng.
	foreach ( $wpdb->get_col( "DESC $table_name", 0 ) as $column ) {
		if ( $column === $column_name ) {

			// Tìm thấy, nên thử xóa nó.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Không có biến áp dụng cho truy vấn này.
			$wpdb->query( $drop_ddl );

			// Không thể biết trực tiếp liệu thao tác có thành công không!
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Không thể chuẩn bị. Lấy danh sách cột cho tên bảng.
			foreach ( $wpdb->get_col( "DESC $table_name", 0 ) as $column ) {
				if ( $column === $column_name ) {
					return false;
				}
			}
		}
	}

	// Không tìm thấy cột.
	return true;
}

/**
 * Kiểm tra xem cột trong bảng cơ sở dữ liệu có khớp với tiêu chí hay không.
 *
 * Sử dụng lệnh SQL DESC để lấy thông tin bảng cho cột. Việc tìm hiểu thêm
 * về thông tin cột được trả về từ câu lệnh SQL sẽ giúp bạn hiểu rõ hơn
 * các tham số. Truyền null để bỏ qua việc kiểm tra tiêu chí đó.
 *
 * Tên cột trả về từ DESC table phân biệt chữ hoa chữ thường và được liệt kê như sau:
 *
 *  - Field
 *  - Type
 *  - Null
 *  - Key
 *  - Default
 *  - Extra
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $table_name    Tên bảng cơ sở dữ liệu.
 * @param string $col_name      Tên cột của bảng.
 * @param string $col_type      Kiểu dữ liệu của cột.
 * @param bool   $is_null       Tùy chọn. Kiểm tra có null không.
 * @param mixed  $key           Tùy chọn. Thông tin khóa.
 * @param mixed  $default_value Tùy chọn. Giá trị mặc định.
 * @param mixed  $extra         Tùy chọn. Giá trị bổ sung.
 * @return bool True nếu khớp. False nếu không khớp.
 */
function check_column( $table_name, $col_name, $col_type, $is_null = null, $key = null, $default_value = null, $extra = null ) {
	global $wpdb;

	$diffs = 0;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Không thể chuẩn bị. Lấy danh sách cột cho tên bảng.
	$results = $wpdb->get_results( "DESC $table_name" );

	foreach ( $results as $row ) {

		if ( $row->Field === $col_name ) {

			// Đã tìm thấy cột, kiểm tra các tham số.
			if ( ( null !== $col_type ) && ( $row->Type !== $col_type ) ) {
				++$diffs;
			}
			if ( ( null !== $is_null ) && ( $row->Null !== $is_null ) ) {
				++$diffs;
			}
			if ( ( null !== $key ) && ( $row->Key !== $key ) ) {
				++$diffs;
			}
			if ( ( null !== $default_value ) && ( $row->Default !== $default_value ) ) {
				++$diffs;
			}
			if ( ( null !== $extra ) && ( $row->Extra !== $extra ) ) {
				++$diffs;
			}

			if ( $diffs > 0 ) {
				return false;
			}

			return true;
		} // Kết thúc kiểm tra cột tìm thấy.
	}

	return false;
}
