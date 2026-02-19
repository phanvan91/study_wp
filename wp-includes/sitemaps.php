<?php
/**
 * Sitemaps: Các hàm công khai
 *
 * File này chứa nhiều hàm công khai mà các nhà phát triển có thể sử dụng để tương tác với
 * API XML Sitemaps.
 *
 * @package WordPress
 * @subpackage Sitemaps
 * @since 5.5.0
 */

/**
 * Lấy instance hiện tại của máy chủ Sitemaps.
 *
 * @since 5.5.0
 *
 * @global WP_Sitemaps $wp_sitemaps Instance Sitemaps lõi toàn cục.
 *
 * @return WP_Sitemaps Instance Sitemaps.
 */
function wp_sitemaps_get_server() {
	global $wp_sitemaps;

	// Nếu không có instance toàn cục, khởi tạo và thiết lập hệ thống sitemaps.
	if ( empty( $wp_sitemaps ) ) {
		$wp_sitemaps = new WP_Sitemaps();
		$wp_sitemaps->init();

		/**
		 * Kích hoạt khi khởi tạo đối tượng Sitemaps.
		 *
		 * Các sitemaps bổ sung nên được đăng ký trên hook này.
		 *
		 * @since 5.5.0
		 *
		 * @param WP_Sitemaps $wp_sitemaps Đối tượng Sitemaps.
		 */
		do_action( 'wp_sitemaps_init', $wp_sitemaps );
	}

	return $wp_sitemaps;
}

/**
 * Lấy mảng các nhà cung cấp sitemap.
 *
 * @since 5.5.0
 *
 * @return WP_Sitemaps_Provider[] Mảng các nhà cung cấp sitemap.
 */
function wp_get_sitemap_providers() {
	$sitemaps = wp_sitemaps_get_server();

	return $sitemaps->registry->get_providers();
}

/**
 * Đăng ký một nhà cung cấp sitemap mới.
 *
 * @since 5.5.0
 *
 * @param string               $name     Tên duy nhất cho nhà cung cấp sitemap.
 * @param WP_Sitemaps_Provider $provider Instance `Sitemaps_Provider` triển khai sitemap.
 * @return bool Sitemap đã được thêm hay chưa.
 */
function wp_register_sitemap_provider( $name, WP_Sitemaps_Provider $provider ) {
	$sitemaps = wp_sitemaps_get_server();

	return $sitemaps->registry->add_provider( $name, $provider );
}

/**
 * Lấy số lượng URL tối đa cho một sitemap.
 *
 * @since 5.5.0
 *
 * @param string $object_type Loại đối tượng cho sitemap cần lọc (ví dụ: 'post', 'term', 'user').
 * @return int Số lượng URL tối đa.
 */
function wp_sitemaps_get_max_urls( $object_type ) {
	/**
	 * Lọc số lượng URL tối đa hiển thị trên một sitemap.
	 *
	 * @since 5.5.0
	 *
	 * @param int    $max_urls    Số lượng URL tối đa trong một sitemap. Mặc định 2000.
	 * @param string $object_type Loại đối tượng cho sitemap cần lọc (ví dụ: 'post', 'term', 'user').
	 */
	return apply_filters( 'wp_sitemaps_max_urls', 2000, $object_type );
}

/**
 * Lấy URL đầy đủ cho một sitemap.
 *
 * @since 5.5.1
 *
 * @param string $name         Tên sitemap.
 * @param string $subtype_name Tên loại phụ sitemap. Mặc định chuỗi rỗng.
 * @param int    $page         Trang của sitemap. Mặc định 1.
 * @return string|false URL sitemap hoặc false nếu sitemap không tồn tại.
 */
function get_sitemap_url( $name, $subtype_name = '', $page = 1 ) {
	$sitemaps = wp_sitemaps_get_server();

	if ( ! $sitemaps ) {
		return false;
	}

	if ( 'index' === $name ) {
		return $sitemaps->index->get_index_url();
	}

	$provider = $sitemaps->registry->get_provider( $name );
	if ( ! $provider ) {
		return false;
	}

	if ( $subtype_name && ! in_array( $subtype_name, array_keys( $provider->get_object_subtypes() ), true ) ) {
		return false;
	}

	$page = absint( $page );
	if ( 0 >= $page ) {
		$page = 1;
	}

	return $provider->get_sitemap_url( $subtype_name, $page );
}
