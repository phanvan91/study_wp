<?php
/**
 * API Mạng
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 5.1.0
 */

/**
 * Lấy dữ liệu mạng theo ID mạng hoặc đối tượng mạng.
 *
 * Dữ liệu mạng sẽ được lưu vào bộ nhớ đệm và trả về sau khi được truyền qua bộ lọc.
 * Nếu mạng được cung cấp rỗng, biến toàn cục mạng hiện tại sẽ được sử dụng.
 *
 * @since 4.6.0
 *
 * @global WP_Network $current_site
 *
 * @param WP_Network|int|null $network Tùy chọn. Mạng cần lấy. Mặc định là mạng hiện tại.
 * @return WP_Network|null Đối tượng mạng hoặc null nếu không tìm thấy.
 */
function get_network( $network = null ) {
	global $current_site;
	if ( empty( $network ) && isset( $current_site ) ) {
		$network = $current_site;
	}

	if ( $network instanceof WP_Network ) {
		$_network = $network;
	} elseif ( is_object( $network ) ) {
		$_network = new WP_Network( $network );
	} else {
		$_network = WP_Network::get_instance( $network );
	}

	if ( ! $_network ) {
		return null;
	}

	/**
	 * Kích hoạt sau khi một mạng được lấy.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Network $_network Dữ liệu mạng.
	 */
	$_network = apply_filters( 'get_network', $_network );

	return $_network;
}

/**
 * Lấy danh sách các mạng.
 *
 * @since 4.6.0
 *
 * @param string|array $args Tùy chọn. Mảng hoặc chuỗi tham số. Xem WP_Network_Query::parse_query()
 *                           để biết thông tin về các tham số được chấp nhận. Mặc định mảng rỗng.
 * @return array|int Danh sách các đối tượng WP_Network, danh sách ID mạng khi 'fields' được đặt thành 'ids',
 *                   hoặc số lượng mạng khi 'count' được truyền dưới dạng biến truy vấn.
 */
function get_networks( $args = array() ) {
	$query = new WP_Network_Query();

	return $query->query( $args );
}

/**
 * Xóa một mạng khỏi bộ nhớ đệm đối tượng.
 *
 * @since 4.6.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|array $ids ID mạng hoặc mảng các ID mạng cần xóa khỏi bộ nhớ đệm.
 */
function clean_network_cache( $ids ) {
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	$network_ids = (array) $ids;
	wp_cache_delete_multiple( $network_ids, 'networks' );

	foreach ( $network_ids as $id ) {
		/**
		 * Kích hoạt ngay sau khi một mạng đã được xóa khỏi bộ nhớ đệm đối tượng.
		 *
		 * @since 4.6.0
		 *
		 * @param int $id ID mạng.
		 */
		do_action( 'clean_network_cache', $id );
	}

	wp_cache_set_last_changed( 'networks' );
}

/**
 * Cập nhật bộ nhớ đệm mạng của các mạng đã cho.
 *
 * Sẽ thêm các mạng trong $networks vào bộ nhớ đệm. Nếu ID mạng đã tồn tại
 * trong bộ nhớ đệm thì sẽ không được cập nhật. Mạng được thêm vào
 * bộ nhớ đệm sử dụng nhóm mạng với khóa sử dụng ID của các mạng.
 *
 * @since 4.6.0
 *
 * @param array $networks Mảng các đối tượng hàng mạng.
 */
function update_network_cache( $networks ) {
	$data = array();
	foreach ( (array) $networks as $network ) {
		$data[ $network->id ] = $network;
	}
	wp_cache_add_multiple( $data, 'networks' );
}

/**
 * Thêm bất kỳ mạng nào từ các ID đã cho vào bộ nhớ đệm mà chưa tồn tại trong bộ nhớ đệm.
 *
 * @since 4.6.0
 * @since 6.1.0 Hàm này không còn được đánh dấu là "private".
 *
 * @see update_network_cache()
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $network_ids Mảng các ID mạng.
 */
function _prime_network_caches( $network_ids ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $network_ids, 'networks' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_networks = $wpdb->get_results( sprintf( "SELECT $wpdb->site.* FROM $wpdb->site WHERE id IN (%s)", implode( ',', array_map( 'intval', $non_cached_ids ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		update_network_cache( $fresh_networks );
	}
}
