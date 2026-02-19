<?php
/**
 * Lớp WP_oEmbed_Controller, được sử dụng để cung cấp endpoint oEmbed.
 *
 * @package WordPress
 * @subpackage Embeds
 * @since 4.4.0
 */

/**
 * Bộ điều khiển endpoint API oEmbed.
 *
 * Đăng ký route REST API và cung cấp dữ liệu phản hồi.
 * Định dạng đầu ra (XML hoặc JSON) được xử lý bởi REST API.
 *
 * @since 4.4.0
 */
#[AllowDynamicProperties]
final class WP_oEmbed_Controller {
	/**
	 * Đăng ký route REST API oEmbed.
	 *
	 * @since 4.4.0
	 */
	public function register_routes() {
		/**
		 * Lọc tham số maxwidth oEmbed.
		 *
		 * @since 4.4.0
		 *
		 * @param int $maxwidth Chiều rộng tối đa cho phép. Mặc định 600.
		 */
		$maxwidth = apply_filters( 'oembed_default_width', 600 );

		register_rest_route(
			'oembed/1.0',
			'/embed',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'url'      => array(
							'description' => __( 'The URL of the resource for which to fetch oEmbed data.' ),
							'required'    => true,
							'type'        => 'string',
							'format'      => 'uri',
						),
						'format'   => array(
							'default'           => 'json',
							'sanitize_callback' => 'wp_oembed_ensure_format',
						),
						'maxwidth' => array(
							'default'           => $maxwidth,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			'oembed/1.0',
			'/proxy',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_proxy_item' ),
					'permission_callback' => array( $this, 'get_proxy_item_permissions_check' ),
					'args'                => array(
						'url'       => array(
							'description' => __( 'The URL of the resource for which to fetch oEmbed data.' ),
							'required'    => true,
							'type'        => 'string',
							'format'      => 'uri',
						),
						'format'    => array(
							'description' => __( 'The oEmbed format to use.' ),
							'type'        => 'string',
							'default'     => 'json',
							'enum'        => array(
								'json',
								'xml',
							),
						),
						'maxwidth'  => array(
							'description'       => __( 'The maximum width of the embed frame in pixels.' ),
							'type'              => 'integer',
							'default'           => $maxwidth,
							'sanitize_callback' => 'absint',
						),
						'maxheight' => array(
							'description'       => __( 'The maximum height of the embed frame in pixels.' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'discover'  => array(
							'description' => __( 'Whether to perform an oEmbed discovery request for unsanctioned providers.' ),
							'type'        => 'boolean',
							'default'     => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Callback cho endpoint API nhúng.
	 *
	 * Trả về đối tượng JSON cho bài viết.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request Dữ liệu đầy đủ về yêu cầu.
	 * @return array|WP_Error Dữ liệu phản hồi oEmbed hoặc WP_Error nếu thất bại.
	 */
	public function get_item( $request ) {
		$post_id = url_to_postid( $request['url'] );

		/**
		 * Lọc ID bài viết đã xác định.
		 *
		 * @since 4.4.0
		 *
		 * @param int    $post_id ID bài viết.
		 * @param string $url     URL được yêu cầu.
		 */
		$post_id = apply_filters( 'oembed_request_post_id', $post_id, $request['url'] );

		$data = get_oembed_response_data( $post_id, $request['maxwidth'] );

		if ( ! $data ) {
			return new WP_Error( 'oembed_invalid_url', get_status_header_desc( 404 ), array( 'status' => 404 ) );
		}

		return $data;
	}

	/**
	 * Kiểm tra xem người dùng hiện tại có thể thực hiện yêu cầu proxy oEmbed hay không.
	 *
	 * @since 4.8.0
	 *
	 * @return true|WP_Error True nếu yêu cầu có quyền đọc, đối tượng WP_Error nếu không.
	 */
	public function get_proxy_item_permissions_check() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to make proxied oEmbed requests.' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Callback cho endpoint API proxy.
	 *
	 * Trả về đối tượng JSON cho mục được proxy.
	 *
	 * @since 4.8.0
	 *
	 * @see WP_oEmbed::get_html()
	 * @global WP_Embed   $wp_embed   Đối tượng WordPress Embed.
	 * @global WP_Scripts $wp_scripts
	 *
	 * @param WP_REST_Request $request Dữ liệu đầy đủ về yêu cầu.
	 * @return object|WP_Error Dữ liệu phản hồi oEmbed hoặc WP_Error nếu thất bại.
	 */
	public function get_proxy_item( $request ) {
		global $wp_embed, $wp_scripts;

		$args = $request->get_params();

		// Cung cấp dữ liệu oEmbed từ cache nếu có.
		unset( $args['_wpnonce'] );
		$cache_key = 'oembed_' . md5( serialize( $args ) );
		$data      = get_transient( $cache_key );
		if ( ! empty( $data ) ) {
			return $data;
		}

		$url = $request['url'];
		unset( $args['url'] );

		// Sao chép maxwidth/maxheight thành width/height vì WP_oEmbed::fetch() sử dụng các tên tham số này.
		if ( isset( $args['maxwidth'] ) ) {
			$args['width'] = $args['maxwidth'];
		}
		if ( isset( $args['maxheight'] ) ) {
			$args['height'] = $args['maxheight'];
		}

		// Rút gọn quy trình cho URL thuộc trang web hiện tại.
		$data = get_oembed_response_data_for_url( $url, $args );

		if ( $data ) {
			return $data;
		}

		$data = _wp_oembed_get_object()->get_data( $url, $args );

		if ( false === $data ) {
			// Thử sử dụng nhúng cổ điển thay thế.
			/* @var WP_Embed $wp_embed */
			$html = $wp_embed->get_embed_handler_html( $args, $url );

			if ( $html ) {
				// Kiểm tra xem có script nào được enqueue bởi shortcode không, và bao gồm chúng trong phản hồi.
				$enqueued_scripts = array();

				foreach ( $wp_scripts->queue as $script ) {
					$enqueued_scripts[] = $wp_scripts->registered[ $script ]->src;
				}

				return (object) array(
					'provider_name' => __( 'Embed Handler' ),
					'html'          => $html,
					'scripts'       => $enqueued_scripts,
				);
			}

			return new WP_Error( 'oembed_invalid_url', get_status_header_desc( 404 ), array( 'status' => 404 ) );
		}

		/** Bộ lọc này được ghi chú trong wp-includes/class-wp-oembed.php */
		$data->html = apply_filters( 'oembed_result', _wp_oembed_get_object()->data2html( (object) $data, $url ), $url, $args );

		/**
		 * Lọc giá trị TTL oEmbed (thời gian sống).
		 *
		 * Tương tự bộ lọc {@see 'oembed_ttl'}, nhưng cho endpoint
		 * proxy oEmbed REST API.
		 *
		 * @since 4.8.0
		 *
		 * @param int    $time    Thời gian sống (tính bằng giây).
		 * @param string $url     URL nhúng được thử.
		 * @param array  $args    Mảng tham số yêu cầu nhúng.
		 */
		$ttl = apply_filters( 'rest_oembed_ttl', DAY_IN_SECONDS, $url, $args );

		set_transient( $cache_key, $data, $ttl );

		return $data;
	}
}
