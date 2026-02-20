<?php
/**
 * REST API: Lớp WP_REST_URL_Details_Controller
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.9.0
 */

/**
 * Bộ điều khiển cung cấp endpoint REST API để lấy thông tin
 * từ phản hồi HTML của một trang web từ xa.
 *
 * @since 5.9.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_URL_Details_Controller extends WP_REST_Controller {

	/**
	 * Hàm khởi tạo bộ điều khiển.
	 *
	 * @since 5.9.0
	 */
	public function __construct() {
		$this->namespace = 'wp-block-editor/v1';
		$this->rest_base = 'url-details';
	}

	/**
	 * Đăng ký các route REST API cần thiết.
	 *
	 * @since 5.9.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'parse_url_details' ),
					'args'                => array(
						'url' => array(
							'required'          => true,
							'description'       => __( 'The URL to process.' ),
							'validate_callback' => 'wp_http_validate_url',
							'sanitize_callback' => 'sanitize_url',
							'type'              => 'string',
							'format'            => 'uri',
						),
					),
					'permission_callback' => array( $this, 'permissions_check' ),
					'schema'              => array( $this, 'get_public_item_schema' ),
				),
			)
		);
	}

	/**
	 * Lấy schema của mục, tuân thủ JSON Schema.
	 *
	 * @since 5.9.0
	 *
	 * @return array Dữ liệu schema của mục.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'url-details',
			'type'       => 'object',
			'properties' => array(
				'title'       => array(
					'description' => sprintf(
						/* translators: %s: HTML title tag. */
						__( 'The contents of the %s element from the URL.' ),
						'<title>'
					),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'icon'        => array(
					'description' => sprintf(
						/* translators: %s: HTML link tag. */
						__( 'The favicon image link of the %s element from the URL.' ),
						'<link rel="icon">'
					),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => sprintf(
						/* translators: %s: HTML meta tag. */
						__( 'The content of the %s element from the URL.' ),
						'<meta name="description">'
					),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'image'       => array(
					'description' => sprintf(
						/* translators: 1: HTML meta tag, 2: HTML meta tag. */
						__( 'The Open Graph image link of the %1$s or %2$s element from the URL.' ),
						'<meta property="og:image">',
						'<meta property="og:image:url">'
					),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Lấy nội dung của thẻ title từ phản hồi HTML.
	 *
	 * @since 5.9.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return WP_REST_Response|WP_Error Chi tiết đã phân tích dưới dạng đối tượng phản hồi. WP_Error nếu có lỗi.
	 */
	public function parse_url_details( $request ) {
		$url = untrailingslashit( $request['url'] );

		if ( empty( $url ) ) {
			return new WP_Error( 'rest_invalid_url', __( 'Invalid URL' ), array( 'status' => 404 ) );
		}

		// Transient cho mỗi URL.
		$cache_key = $this->build_cache_key_for_url( $url );

		// Thử lấy phản hồi đã được lưu cache.
		$cached_response = $this->get_cache( $cache_key );

		if ( ! empty( $cached_response ) ) {
			$remote_url_response = $cached_response;
		} else {
			$remote_url_response = $this->get_remote_url( $url );

			// Thoát nếu không có body hợp lệ hoặc body rỗng.
			if ( is_wp_error( $remote_url_response ) || empty( $remote_url_response ) ) {
				return $remote_url_response;
			}

			// Lưu cache phản hồi hợp lệ.
			$this->set_cache( $cache_key, $remote_url_response );
		}

		$html_head     = $this->get_document_head( $remote_url_response );
		$meta_elements = $this->get_meta_with_content_elements( $html_head );

		$data = $this->add_additional_fields_to_object(
			array(
				'title'       => $this->get_title( $html_head ),
				'icon'        => $this->get_icon( $html_head, $url ),
				'description' => $this->get_description( $meta_elements ),
				'image'       => $this->get_image( $meta_elements, $url ),
			),
			$request
		);

		// Bọc dữ liệu trong đối tượng phản hồi.
		$response = rest_ensure_response( $data );

		/**
		 * Lọc dữ liệu URL cho phản hồi.
		 *
		 * @since 5.9.0
		 *
		 * @param WP_REST_Response $response            Đối tượng phản hồi.
		 * @param string           $url                 URL được yêu cầu.
		 * @param WP_REST_Request  $request             Đối tượng yêu cầu.
		 * @param string           $remote_url_response Nội dung phản hồi HTTP từ URL từ xa.
		 */
		return apply_filters( 'rest_prepare_url_details', $response, $url, $request, $remote_url_response );
	}

	/**
	 * Kiểm tra xem yêu cầu có quyền đọc các URL từ xa không.
	 *
	 * @since 5.9.0
	 *
	 * @return true|WP_Error True nếu yêu cầu có quyền, ngược lại là WP_Error.
	 */
	public function permissions_check() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
			if ( current_user_can( $post_type->cap->edit_posts ) ) {
				return true;
			}
		}

		return new WP_Error(
			'rest_cannot_view_url_details',
			__( 'Sorry, you are not allowed to process remote URLs.' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Lấy tiêu đề tài liệu từ một URL từ xa.
	 *
	 * @since 5.9.0
	 *
	 * @param string $url URL của trang web cần truy cập HTML.
	 * @return string|WP_Error Phản hồi HTTP từ URL từ xa khi thành công.
	 *                         WP_Error nếu không có phản hồi hoặc không có nội dung.
	 */
	private function get_remote_url( $url ) {

		/*
		 * Cung cấp chuỗi UA đã sửa đổi để khắc phục các trang web chặn "Pingbacks" của WordPress.
		 * Tại sao? Chuỗi UA dùng cho yêu cầu pingback chứa `WordPress/` rất giống với
		 * chuỗi UA mặc định của WP HTTP API. Do đó các yêu cầu từ endpoint REST này
		 * bị chặn ngoài ý muốn vì bị nhầm lẫn là yêu cầu pingback.
		 * Bằng cách sửa đổi nhẹ chuỗi UA, nhưng vẫn giữ nhận diện "WordPress" (qua "WP")
		 * chúng ta có thể khắc phục vấn đề này.
		 * Ví dụ chuỗi UA: `WP-URLDetails/5.9-alpha-51389 (+http://localhost:8888)`.
		*/
		$modified_user_agent = 'WP-URLDetails/' . get_bloginfo( 'version' ) . ' (+' . get_bloginfo( 'url' ) . ')';

		$args = array(
			'limit_response_size' => 150 * KB_IN_BYTES,
			'user-agent'          => $modified_user_agent,
		);

		/**
		 * Lọc các tham số yêu cầu HTTP để lấy dữ liệu URL.
		 *
		 * Có thể được dùng để điều chỉnh giới hạn kích thước phản hồi và các tham số WP_Http::request() khác.
		 *
		 * @since 5.9.0
		 *
		 * @param array  $args Các tham số dùng cho yêu cầu HTTP.
		 * @param string $url  URL được thử.
		 */
		$args = apply_filters( 'rest_url_details_http_request_args', $args, $url );

		$response = wp_safe_remote_get( $url, $args );

		if ( WP_Http::OK !== wp_remote_retrieve_response_code( $response ) ) {
			// Không lưu phản hồi lỗi vào cache vì lỗi có thể chỉ là tạm thời.
			return new WP_Error(
				'no_response',
				__( 'URL not found. Response returned a non-200 status code for this URL.' ),
				array( 'status' => WP_Http::NOT_FOUND )
			);
		}

		$remote_body = wp_remote_retrieve_body( $response );

		if ( empty( $remote_body ) ) {
			return new WP_Error(
				'no_content',
				__( 'Unable to retrieve body from response at this URL.' ),
				array( 'status' => WP_Http::NOT_FOUND )
			);
		}

		return $remote_body;
	}

	/**
	 * Phân tích nội dung thẻ title từ HTML được cung cấp.
	 *
	 * @since 5.9.0
	 *
	 * @param string $html HTML từ trang web từ xa tại URL.
	 * @return string Nội dung thẻ title khi thành công. Chuỗi rỗng nếu không tìm thấy.
	 */
	private function get_title( $html ) {
		$pattern = '#<title[^>]*>(.*?)<\s*/\s*title>#is';
		preg_match( $pattern, $html, $match_title );

		if ( empty( $match_title[1] ) || ! is_string( $match_title[1] ) ) {
			return '';
		}

		$title = trim( $match_title[1] );

		return $this->prepare_metadata_for_output( $title );
	}

	/**
	 * Phân tích biểu tượng trang web từ HTML được cung cấp.
	 *
	 * @since 5.9.0
	 *
	 * @param string $html HTML từ trang web từ xa tại URL.
	 * @param string $url  URL của trang web đích.
	 * @return string URI của biểu tượng khi thành công. Chuỗi rỗng nếu không tìm thấy.
	 */
	private function get_icon( $html, $url ) {
		// Lấy phần tử link của biểu tượng.
		$pattern = '#<link\s[^>]*rel=(?:[\"\']??)\s*(?:icon|shortcut icon|icon shortcut)\s*(?:[\"\']??)[^>]*\/?>#isU';
		preg_match( $pattern, $html, $element );
		if ( empty( $element[0] ) || ! is_string( $element[0] ) ) {
			return '';
		}
		$element = trim( $element[0] );

		// Lấy giá trị href của biểu tượng.
		$pattern = '#href=([\"\']??)([^\" >]*?)\\1[^>]*#isU';
		preg_match( $pattern, $element, $icon );
		if ( empty( $icon[2] ) || ! is_string( $icon[2] ) ) {
			return '';
		}
		$icon = trim( $icon[2] );

		// Nếu biểu tượng là URL dạng data, trả về nó.
		$parsed_icon = parse_url( $icon );
		if ( isset( $parsed_icon['scheme'] ) && 'data' === $parsed_icon['scheme'] ) {
			return $icon;
		}

		// Thử chuyển đổi URL tương đối thành URL tuyệt đối.
		if ( ! is_string( $url ) || '' === $url ) {
			return $icon;
		}
		$parsed_url = parse_url( $url );
		if ( isset( $parsed_url['scheme'] ) && isset( $parsed_url['host'] ) ) {
			$root_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/';
			$icon     = WP_Http::make_absolute_url( $icon, $root_url );
		}

		return $icon;
	}

	/**
	 * Phân tích mô tả meta từ HTML được cung cấp.
	 *
	 * @since 5.9.0
	 *
	 * @param array $meta_elements {
	 *     Mảng đa chiều được đánh chỉ mục khi thành công, ngược lại là mảng rỗng.
	 *
	 *     @type string[] $0 Các phần tử meta có thuộc tính content.
	 *     @type string[] $1 Dấu ngoặc kép mở của thuộc tính content.
	 *     @type string[] $2 Giá trị thuộc tính content cho mỗi phần tử meta.
	 * }
	 * @return string Nội dung mô tả meta khi thành công. Chuỗi rỗng nếu không tìm thấy.
	 */
	private function get_description( $meta_elements ) {
		// Thoát nếu không có phần tử meta nào.
		if ( empty( $meta_elements[0] ) ) {
			return '';
		}

		$description = $this->get_metadata_from_meta_element(
			$meta_elements,
			'name',
			'(?:description|og:description)'
		);

		// Thoát nếu không tìm thấy mô tả.
		if ( '' === $description ) {
			return '';
		}

		return $this->prepare_metadata_for_output( $description );
	}

	/**
	 * Phân tích hình ảnh Open Graph (OG) từ HTML được cung cấp.
	 *
	 * Xem: https://ogp.me/.
	 *
	 * @since 5.9.0
	 *
	 * @param array  $meta_elements {
	 *     Mảng đa chiều được đánh chỉ mục khi thành công, ngược lại là mảng rỗng.
	 *
	 *     @type string[] $0 Các phần tử meta có thuộc tính content.
	 *     @type string[] $1 Dấu ngoặc kép mở của thuộc tính content.
	 *     @type string[] $2 Giá trị thuộc tính content cho mỗi phần tử meta.
	 * }
	 * @param string $url URL của trang web đích.
	 * @return string Hình ảnh OG khi thành công. Chuỗi rỗng nếu không tìm thấy.
	 */
	private function get_image( $meta_elements, $url ) {
		$image = $this->get_metadata_from_meta_element(
			$meta_elements,
			'property',
			'(?:og:image|og:image:url)'
		);

		// Thoát nếu không tìm thấy hình ảnh.
		if ( '' === $image ) {
			return '';
		}

		// Thử chuyển đổi URL tương đối thành URL tuyệt đối.
		$parsed_url = parse_url( $url );
		if ( isset( $parsed_url['scheme'] ) && isset( $parsed_url['host'] ) ) {
			$root_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/';
			$image    = WP_Http::make_absolute_url( $image, $root_url );
		}

		return $image;
	}

	/**
	 * Prepares the metadata by:
	 *    - stripping all HTML tags and tag entities.
	 *    - converting non-tag entities into characters.
	 *
	 * @since 5.9.0
	 *
	 * @param string $metadata The metadata content to prepare.
	 * @return string The prepared metadata.
	 */
	private function prepare_metadata_for_output( $metadata ) {
		$metadata = html_entity_decode( $metadata, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$metadata = wp_strip_all_tags( $metadata );
		return $metadata;
	}

	/**
	 * Utility function to build cache key for a given URL.
	 *
	 * @since 5.9.0
	 *
	 * @param string $url The URL for which to build a cache key.
	 * @return string The cache key.
	 */
	private function build_cache_key_for_url( $url ) {
		return 'g_url_details_response_' . md5( $url );
	}

	/**
	 * Utility function to retrieve a value from the cache at a given key.
	 *
	 * @since 5.9.0
	 *
	 * @param string $key The cache key.
	 * @return mixed The value from the cache.
	 */
	private function get_cache( $key ) {
		return get_site_transient( $key );
	}

	/**
	 * Utility function to cache a given data set at a given cache key.
	 *
	 * @since 5.9.0
	 *
	 * @param string $key  The cache key under which to store the value.
	 * @param string $data The data to be stored at the given cache key.
	 * @return bool True when transient set. False if not set.
	 */
	private function set_cache( $key, $data = '' ) {
		$ttl = HOUR_IN_SECONDS;

		/**
		 * Filters the cache expiration.
		 *
		 * Can be used to adjust the time until expiration in seconds for the cache
		 * of the data retrieved for the given URL.
		 *
		 * @since 5.9.0
		 *
		 * @param int $ttl The time until cache expiration in seconds.
		 */
		$cache_expiration = apply_filters( 'rest_url_details_cache_expiration', $ttl );

		return set_site_transient( $key, $data, $cache_expiration );
	}

	/**
	 * Retrieves the head element section.
	 *
	 * @since 5.9.0
	 *
	 * @param string $html The string of HTML to parse.
	 * @return string The `<head>..</head>` section on success. Given `$html` if not found.
	 */
	private function get_document_head( $html ) {
		$head_html = $html;

		// Find the opening `<head>` tag.
		$head_start = strpos( $html, '<head' );
		if ( false === $head_start ) {
			// Didn't find it. Return the original HTML.
			return $html;
		}

		// Find the closing `</head>` tag.
		$head_end = strpos( $head_html, '</head>' );
		if ( false === $head_end ) {
			// Didn't find it. Find the opening `<body>` tag.
			$head_end = strpos( $head_html, '<body' );

			// Didn't find it. Return the original HTML.
			if ( false === $head_end ) {
				return $html;
			}
		}

		// Extract the HTML from opening tag to the closing tag. Then add the closing tag.
		$head_html  = substr( $head_html, $head_start, $head_end );
		$head_html .= '</head>';

		return $head_html;
	}

	/**
	 * Gets all the meta tag elements that have a 'content' attribute.
	 *
	 * @since 5.9.0
	 *
	 * @param string $html The string of HTML to be parsed.
	 * @return array {
	 *     A multidimensional indexed array on success, else empty array.
	 *
	 *     @type string[] $0 Meta elements with a content attribute.
	 *     @type string[] $1 Content attribute's opening quotation mark.
	 *     @type string[] $2 Content attribute's value for each meta element.
	 * }
	 */
	private function get_meta_with_content_elements( $html ) {
		/*
		 * Parse all meta elements with a content attribute.
		 *
		 * Why first search for the content attribute rather than directly searching for name=description element?
		 * tl;dr The content attribute's value will be truncated when it contains a > symbol.
		 *
		 * The content attribute's value (i.e. the description to get) can have HTML in it and be well-formed as
		 * it's a string to the browser. Imagine what happens when attempting to match for the name=description
		 * first. Hmm, if a > or /> symbol is in the content attribute's value, then it terminates the match
		 * as the element's closing symbol. But wait, it's in the content attribute and is not the end of the
		 * element. This is a limitation of using regex. It can't determine "wait a minute this is inside of quotation".
		 * If this happens, what gets matched is not the entire element or all of the content.
		 *
		 * Why not search for the name=description and then content="(.*)"?
		 * The attribute order could be opposite. Plus, additional attributes may exist including being between
		 * the name and content attributes.
		 *
		 * Why not lookahead?
		 * Lookahead is not constrained to stay within the element. The first <meta it finds may not include
		 * the name or content, but rather could be from a different element downstream.
		 */
		$pattern = '#<meta\s' .

				/*
				 * Allows for additional attributes before the content attribute.
				 * Searches for anything other than > symbol.
				 */
				'[^>]*' .

				/*
				* Find the content attribute. When found, capture its value (.*).
				*
				* Allows for (a) single or double quotes and (b) whitespace in the value.
				*
				* Why capture the opening quotation mark, i.e. (["\']), and then backreference,
				* i.e \1, for the closing quotation mark?
				* To ensure the closing quotation mark matches the opening one. Why? Attribute values
				* can contain quotation marks, such as an apostrophe in the content.
				*/
				'content=(["\']??)(.*)\1' .

				/*
				* Allows for additional attributes after the content attribute.
				* Searches for anything other than > symbol.
				*/
				'[^>]*' .

				/*
				* \/?> searches for the closing > symbol, which can be in either /> or > format.
				* # ends the pattern.
				*/
				'\/?>#' .

				/*
				* These are the options:
				* - i : case-insensitive
				* - s : allows newline characters for the . match (needed for multiline elements)
				* - U means non-greedy matching
				*/
				'isU';

		preg_match_all( $pattern, $html, $elements );

		return $elements;
	}

	/**
	 * Gets the metadata from a target meta element.
	 *
	 * @since 5.9.0
	 *
	 * @param array  $meta_elements {
	 *     A multi-dimensional indexed array on success, else empty array.
	 *
	 *     @type string[] $0 Meta elements with a content attribute.
	 *     @type string[] $1 Content attribute's opening quotation mark.
	 *     @type string[] $2 Content attribute's value for each meta element.
	 * }
	 * @param string $attr       Attribute that identifies the element with the target metadata.
	 * @param string $attr_value The attribute's value that identifies the element with the target metadata.
	 * @return string The metadata on success. Empty string if not found.
	 */
	private function get_metadata_from_meta_element( $meta_elements, $attr, $attr_value ) {
		// Bail out if there are no meta elements.
		if ( empty( $meta_elements[0] ) ) {
			return '';
		}

		$metadata = '';
		$pattern  = '#' .
				/*
				 * Target this attribute and value to find the metadata element.
				 *
				 * Allows for (a) no, single, double quotes and (b) whitespace in the value.
				 *
				 * Why capture the opening quotation mark, i.e. (["\']), and then backreference,
				 * i.e \1, for the closing quotation mark?
				 * To ensure the closing quotation mark matches the opening one. Why? Attribute values
				 * can contain quotation marks, such as an apostrophe in the content.
				 */
				$attr . '=([\"\']??)\s*' . $attr_value . '\s*\1' .

				/*
				 * These are the options:
				 * - i : case-insensitive
				 * - s : allows newline characters for the . match (needed for multiline elements)
				 * - U means non-greedy matching
				 */
				'#isU';

		// Find the metadata element.
		foreach ( $meta_elements[0] as $index => $element ) {
			preg_match( $pattern, $element, $match );

			// This is not the metadata element. Skip it.
			if ( empty( $match ) ) {
				continue;
			}

			/*
			 * Found the metadata element.
			 * Get the metadata from its matching content array.
			 */
			if ( isset( $meta_elements[2][ $index ] ) && is_string( $meta_elements[2][ $index ] ) ) {
				$metadata = trim( $meta_elements[2][ $index ] );
			}

			break;
		}

		return $metadata;
	}
}
