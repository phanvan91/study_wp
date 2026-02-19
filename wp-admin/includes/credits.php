<?php
/**
 * API Quản trị Ghi nhận Đóng góp WordPress.
 *
 * @package WordPress
 * @subpackage Administration
 * @since 4.4.0
 */

/**
 * Lấy danh sách ghi nhận đóng góp.
 *
 * @since 3.2.0
 * @since 5.6.0 Thêm các tham số `$version` và `$locale`.
 *
 * @param string $version Phiên bản WordPress. Mặc định là phiên bản hiện tại.
 * @param string $locale  Ngôn ngữ WordPress. Mặc định là ngôn ngữ của người dùng hiện tại.
 * @return array|false Danh sách tất cả người đóng góp, hoặc false khi có lỗi.
 */
function wp_credits( $version = '', $locale = '' ) {
	if ( ! $version ) {
		$version = wp_get_wp_version();
	}

	if ( ! $locale ) {
		$locale = get_user_locale();
	}

	$results = get_site_transient( 'wordpress_credits_' . $locale );

	if ( ! is_array( $results )
		|| str_contains( $version, '-' )
		|| ( isset( $results['data']['version'] ) && ! str_starts_with( $version, $results['data']['version'] ) )
	) {
		$url     = "http://api.wordpress.org/core/credits/1.1/?version={$version}&locale={$locale}";
		$options = array( 'user-agent' => 'WordPress/' . $version . '; ' . home_url( '/' ) );

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$response = wp_remote_get( $url, $options );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$results = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $results ) ) {
			return false;
		}

		set_site_transient( 'wordpress_credits_' . $locale, $results, DAY_IN_SECONDS );
	}

	return $results;
}

/**
 * Lấy liên kết đến trang hồ sơ WordPress.org của người đóng góp.
 *
 * @access private
 * @since 3.2.0
 *
 * @param string $display_name  Tên hiển thị của người đóng góp (truyền bằng tham chiếu).
 * @param string $username      Tên đăng nhập của người đóng góp.
 * @param string $profiles      URL đến trang hồ sơ WordPress.org của người đóng góp.
 */
function _wp_credits_add_profile_link( &$display_name, $username, $profiles ) {
	$display_name = '<a href="' . esc_url( sprintf( $profiles, $username ) ) . '">' . esc_html( $display_name ) . '</a>';
}

/**
 * Lấy liên kết đến thư viện bên ngoài được sử dụng trong WordPress.
 *
 * @access private
 * @since 3.2.0
 *
 * @param string $data Dữ liệu thư viện bên ngoài (truyền bằng tham chiếu).
 */
function _wp_credits_build_object_link( &$data ) {
	$data = '<a href="' . esc_url( $data[1] ) . '">' . esc_html( $data[0] ) . '</a>';
}

/**
 * Hiển thị tiêu đề cho một nhóm người đóng góp nhất định.
 *
 * @since 5.3.0
 *
 * @param array $group_data Nhóm người đóng góp hiện tại.
 */
function wp_credits_section_title( $group_data = array() ) {
	if ( ! count( $group_data ) ) {
		return;
	}

	if ( $group_data['name'] ) {
		if ( 'Translators' === $group_data['name'] ) {
			// Được coi là slug đặc biệt trong phản hồi API. (Ngoài ra, sẽ không bao giờ được trả về cho en_US.)
			$title = _x( 'Translators', 'Translate this to be the equivalent of English Translators in your language for the credits page Translators section' );
		} elseif ( isset( $group_data['placeholders'] ) ) {
			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
			$title = vsprintf( translate( $group_data['name'] ), $group_data['placeholders'] );
		} else {
			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
			$title = translate( $group_data['name'] );
		}

		echo '<h2 class="wp-people-group-title">' . esc_html( $title ) . "</h2>\n";
	}
}

/**
 * Hiển thị danh sách người đóng góp cho một nhóm nhất định.
 *
 * @since 5.3.0
 *
 * @param array  $credits Các nhóm ghi nhận đóng góp được trả về từ API.
 * @param string $slug    Nhóm hiện tại để hiển thị.
 */
function wp_credits_section_list( $credits = array(), $slug = '' ) {
	$group_data   = isset( $credits['groups'][ $slug ] ) ? $credits['groups'][ $slug ] : array();
	$credits_data = $credits['data'];
	if ( ! count( $group_data ) ) {
		return;
	}

	if ( ! empty( $group_data['shuffle'] ) ) {
		shuffle( $group_data['data'] ); // Chúng tôi định sắp xếp theo khả năng phát âm "hierarchical", nhưng điều đó không công bằng với Matt.
	}

	switch ( $group_data['type'] ) {
		case 'list':
			array_walk( $group_data['data'], '_wp_credits_add_profile_link', $credits_data['profiles'] );
			echo '<p class="wp-credits-list">' . wp_sprintf( '%l.', $group_data['data'] ) . "</p>\n\n";
			break;
		case 'libraries':
			array_walk( $group_data['data'], '_wp_credits_build_object_link' );
			echo '<p class="wp-credits-list">' . wp_sprintf( '%l.', $group_data['data'] ) . "</p>\n\n";
			break;
		default:
			$compact = 'compact' === $group_data['type'];
			$classes = 'wp-people-group ' . ( $compact ? 'compact' : '' );
			echo '<ul class="' . $classes . '" id="wp-people-group-' . $slug . '">' . "\n";
			foreach ( $group_data['data'] as $person_data ) {
				echo '<li class="wp-person" id="wp-person-' . esc_attr( $person_data[2] ) . '">' . "\n\t";
				echo '<a href="' . esc_url( sprintf( $credits_data['profiles'], $person_data[2] ) ) . '" class="web">';
				$size   = $compact ? 80 : 160;
				$data   = get_avatar_data( $person_data[1] . '@sha256.gravatar.com', array( 'size' => $size ) );
				$data2x = get_avatar_data( $person_data[1] . '@sha256.gravatar.com', array( 'size' => $size * 2 ) );
				echo '<span class="wp-person-avatar"><img src="' . esc_url( $data['url'] ) . '" srcset="' . esc_url( $data2x['url'] ) . ' 2x" class="gravatar" alt="" /></span>' . "\n";
				echo esc_html( $person_data[0] ) . "</a>\n\t";
				if ( ! $compact && ! empty( $person_data[3] ) ) {
					// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
					echo '<span class="title">' . translate( $person_data[3] ) . "</span>\n";
				}
				echo "</li>\n";
			}
			echo "</ul>\n";
			break;
	}
}
