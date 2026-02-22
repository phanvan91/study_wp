<?php
/**
 * Interactivity API: Lớp WP_Interactivity_API.
 *
 * @package WordPress
 * @subpackage Interactivity API
 * @since 6.5.0
 */

/**
 * Lớp được sử dụng để xử lý Interactivity API trên máy chủ.
 *
 * @since 6.5.0
 */
final class WP_Interactivity_API {
	/**
	 * Lưu trữ ánh xạ tên thuộc tính directive tới các phương thức xử lý của chúng.
	 *
	 * @since 6.5.0
	 * @var array
	 */
	private static $directive_processors = array(
		'data-wp-interactive'   => 'data_wp_interactive_processor',
		'data-wp-router-region' => 'data_wp_router_region_processor',
		'data-wp-context'       => 'data_wp_context_processor',
		'data-wp-bind'          => 'data_wp_bind_processor',
		'data-wp-class'         => 'data_wp_class_processor',
		'data-wp-style'         => 'data_wp_style_processor',
		'data-wp-text'          => 'data_wp_text_processor',
		/*
		 * `data-wp-each` cần được xử lý cuối cùng vì nó di chuyển con trỏ
		 * đến cuối các phần tử đã xử lý để ngăn chúng bị xử lý hai lần.
		 */
		'data-wp-each'          => 'data_wp_each_processor',
	);

	/**
	 * Lưu trữ trạng thái ban đầu của các Interactivity API store khác nhau.
	 *
	 * Trạng thái này được sử dụng trong quá trình xử lý directive phía máy chủ. Sau đó,
	 * nó được tuần tự hóa và gửi đến client như một phần của dữ liệu tương tác để được
	 * khôi phục trong quá trình hydration của các interactivity store phía client.
	 *
	 * @since 6.5.0
	 * @var array
	 */
	private $state_data = array();

	/**
	 * Lưu trữ cấu hình cần thiết cho các Interactivity API store khác nhau.
	 *
	 * Cấu hình này được tuần tự hóa và gửi đến client như một phần của dữ liệu
	 * tương tác và có thể được truy cập bởi các interactivity store phía client.
	 *
	 * @since 6.5.0
	 * @var array
	 */
	private $config_data = array();

	/**
	 * Cờ cho biết directive `data-wp-router-region` đã được tìm thấy
	 * trong HTML và đã được xử lý hay chưa.
	 *
	 * Giá trị được lưu trong thuộc tính private của instance WP_Interactivity_API
	 * thay vì sử dụng biến static bên trong hàm xử lý, vì biến static sẽ giữ
	 * cùng một giá trị cho tất cả các instance bất kể chúng đã xử lý
	 * directive `data-wp-router-region` nào hay chưa.
	 *
	 * @since 6.5.0
	 * @var bool
	 */
	private $has_processed_router_region = false;

	/**
	 * Ngăn xếp các namespace được định nghĩa bởi directive `data-wp-interactive`,
	 * theo thứ tự chúng được xử lý.
	 *
	 * Chỉ khả dụng trong quá trình xử lý directive, ngoài ra là `null`.
	 *
	 * @since 6.6.0
	 * @var array<string>|null
	 */
	private $namespace_stack = null;

	/**
	 * Ngăn xếp các context được định nghĩa bởi directive `data-wp-context`,
	 * theo thứ tự chúng được xử lý.
	 *
	 * Chỉ khả dụng trong quá trình xử lý directive, ngoài ra là `null`.
	 *
	 * @since 6.6.0
	 * @var array<array<mixed>>|null
	 */
	private $context_stack = null;

	/**
	 * Biểu diễn dưới dạng mảng của phần tử đang được xử lý hiện tại.
	 *
	 * Chỉ khả dụng trong quá trình xử lý directive, ngoài ra là `null`.
	 *
	 * @since 6.7.0
	 * @var array{attributes: array<string, string|bool>}|null
	 */
	private $current_element = null;

	/**
	 * Lấy và/hoặc thiết lập trạng thái ban đầu của một Interactivity API store
	 * cho một namespace nhất định.
	 *
	 * Nếu trạng thái cho namespace store đó đã tồn tại, nó sẽ gộp trạng thái
	 * mới được cung cấp với trạng thái hiện có.
	 *
	 * Khi không chỉ định namespace, nó trả về trạng thái được định nghĩa cho
	 * giá trị hiện tại trong ngăn xếp namespace nội bộ trong lệnh gọi `process_directives`.
	 *
	 * @since 6.5.0
	 * @since 6.6.0 Tham số `$store_namespace` là tùy chọn.
	 *
	 * @param string $store_namespace Tùy chọn. Định danh namespace store duy nhất.
	 * @param array  $state           Tùy chọn. Mảng sẽ được gộp với trạng thái hiện có cho
	 *                                namespace store được chỉ định.
	 * @return array Trạng thái hiện tại cho namespace store được chỉ định. Đây sẽ là trạng thái
	 *               đã cập nhật nếu tham số $state được cung cấp.
	 */
	public function state( ?string $store_namespace = null, ?array $state = null ): array {
		if ( ! $store_namespace ) {
			if ( $state ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'The namespace is required when state data is passed.' ),
					'6.6.0'
				);
				return array();
			}
			if ( null !== $store_namespace ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'The namespace should be a non-empty string.' ),
					'6.6.0'
				);
				return array();
			}
			if ( null === $this->namespace_stack ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'The namespace can only be omitted during directive processing.' ),
					'6.6.0'
				);
				return array();
			}

			$store_namespace = end( $this->namespace_stack );
		}
		if ( ! isset( $this->state_data[ $store_namespace ] ) ) {
			$this->state_data[ $store_namespace ] = array();
		}
		if ( is_array( $state ) ) {
			$this->state_data[ $store_namespace ] = array_replace_recursive(
				$this->state_data[ $store_namespace ],
				$state
			);
		}
		return $this->state_data[ $store_namespace ];
	}

	/**
	 * Lấy và/hoặc thiết lập cấu hình của Interactivity API cho một namespace
	 * store nhất định.
	 *
	 * Nếu cấu hình cho namespace store đó đã tồn tại, nó sẽ gộp cấu hình
	 * mới được cung cấp với cấu hình hiện có.
	 *
	 * @since 6.5.0
	 *
	 * @param string $store_namespace Định danh namespace store duy nhất.
	 * @param array  $config          Tùy chọn. Mảng sẽ được gộp với cấu hình hiện có cho
	 *                                namespace store được chỉ định.
	 * @return array Cấu hình cho namespace store được chỉ định. Đây sẽ là cấu hình
	 *               đã cập nhật nếu tham số $config được cung cấp.
	 */
	public function config( string $store_namespace, array $config = array() ): array {
		if ( ! isset( $this->config_data[ $store_namespace ] ) ) {
			$this->config_data[ $store_namespace ] = array();
		}
		if ( is_array( $config ) ) {
			$this->config_data[ $store_namespace ] = array_replace_recursive(
				$this->config_data[ $store_namespace ],
				$config
			);
		}
		return $this->config_data[ $store_namespace ];
	}

	/**
	 * In dữ liệu tương tác phía client đã được tuần tự hóa.
	 *
	 * Mã hóa cấu hình và trạng thái ban đầu thành JSON và in chúng bên trong
	 * thẻ script có type "application/json". Khi ở trên trình duyệt, trạng thái sẽ
	 * được phân tích và sử dụng để hydrate các interactivity store phía client và
	 * cấu hình sẽ khả dụng thông qua tiện ích `getConfig`.
	 *
	 * @since 6.5.0
	 *
	 * @deprecated 6.7.0 Việc truyền dữ liệu client được xử lý bởi bộ lọc {@see "script_module_data_{$module_id}"}.
	 */
	public function print_client_interactivity_data() {
		_deprecated_function( __METHOD__, '6.7.0' );
	}

	/**
	 * Thiết lập dữ liệu interactivity-router phía client.
	 *
	 * Khi ở trên trình duyệt, trạng thái sẽ được phân tích và sử dụng để hydrate các
	 * interactivity store phía client và cấu hình sẽ khả dụng thông qua tiện ích `getConfig`.
	 *
	 * @since 6.7.0
	 *
	 * @param array $data Dữ liệu cần lọc.
	 * @return array Dữ liệu cho module script Interactivity Router.
	 */
	public function filter_script_module_interactivity_router_data( array $data ): array {
		if ( ! isset( $data['i18n'] ) ) {
			$data['i18n'] = array();
		}
		$data['i18n']['loading'] = __( 'Loading page, please wait.' );
		$data['i18n']['loaded']  = __( 'Page Loaded.' );
		return $data;
	}

	/**
	 * Thiết lập dữ liệu tương tác phía client.
	 *
	 * Khi ở trên trình duyệt, trạng thái sẽ được phân tích và sử dụng để hydrate các
	 * interactivity store phía client và cấu hình sẽ khả dụng thông qua tiện ích `getConfig`.
	 *
	 * @since 6.7.0
	 *
	 * @param array $data Dữ liệu cần lọc.
	 * @return array Dữ liệu cho module script Interactivity API.
	 */
	public function filter_script_module_interactivity_data( array $data ): array {
		if ( empty( $this->state_data ) && empty( $this->config_data ) ) {
			return $data;
		}

		$config = array();
		foreach ( $this->config_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$config[ $key ] = $value;
			}
		}
		if ( ! empty( $config ) ) {
			$data['config'] = $config;
		}

		$state = array();
		foreach ( $this->state_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$state[ $key ] = $value;
			}
		}
		if ( ! empty( $state ) ) {
			$data['state'] = $state;
		}

		return $data;
	}

	/**
	 * Trả về giá trị mới nhất trên ngăn xếp context với namespace được truyền vào.
	 *
	 * Khi namespace bị bỏ qua, nó sử dụng namespace hiện tại trên ngăn xếp
	 * namespace trong lệnh gọi `process_directives`.
	 *
	 * @since 6.6.0
	 *
	 * @param string $store_namespace Tùy chọn. Định danh namespace store duy nhất.
	 */
	public function get_context( ?string $store_namespace = null ): array {
		if ( null === $this->context_stack ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The context can only be read during directive processing.' ),
				'6.6.0'
			);
			return array();
		}

		if ( ! $store_namespace ) {
			if ( null !== $store_namespace ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'The namespace should be a non-empty string.' ),
					'6.6.0'
				);
				return array();
			}

			$store_namespace = end( $this->namespace_stack );
		}

		$context = end( $this->context_stack );

		return ( $store_namespace && $context && isset( $context[ $store_namespace ] ) )
			? $context[ $store_namespace ]
			: array();
	}

	/**
	 * Trả về biểu diễn dạng mảng của phần tử đang được xử lý hiện tại.
	 *
	 * Mảng trả về chứa một bản sao của các thuộc tính phần tử.
	 *
	 * @since 6.7.0
	 *
	 * @return array{attributes: array<string, string|bool>}|null Phần tử hiện tại.
	 */
	public function get_element(): ?array {
		if ( null === $this->current_element ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The element can only be read during directive processing.' ),
				'6.7.0'
			);
		}

		return $this->current_element;
	}

	/**
	 * Đăng ký các module script `@wordpress/interactivity`.
	 *
	 * @deprecated 6.7.0 Việc đăng ký Script Modules được xử lý bởi {@see wp_default_script_modules()}.
	 *
	 * @since 6.5.0
	 */
	public function register_script_modules() {
		_deprecated_function( __METHOD__, '6.7.0', 'wp_default_script_modules' );
	}

	/**
	 * Thêm các hook cần thiết cho Interactivity API.
	 *
	 * @since 6.5.0
	 */
	public function add_hooks() {
		add_filter( 'script_module_data_@wordpress/interactivity', array( $this, 'filter_script_module_interactivity_data' ) );
		add_filter( 'script_module_data_@wordpress/interactivity-router', array( $this, 'filter_script_module_interactivity_router_data' ) );
	}

	/**
	 * Xử lý các directive tương tác có trong nội dung HTML
	 * và cập nhật markup tương ứng.
	 *
	 * @since 6.5.0
	 *
	 * @param string $html Nội dung HTML cần xử lý.
	 * @return string Nội dung HTML đã xử lý. Trả về nội dung gốc khi HTML chứa các thẻ không cân bằng.
	 */
	public function process_directives( string $html ): string {
		if ( ! str_contains( $html, 'data-wp-' ) ) {
			return $html;
		}

		$this->namespace_stack = array();
		$this->context_stack   = array();

		$result = $this->_process_directives( $html );

		$this->namespace_stack = null;
		$this->context_stack   = null;

		return null === $result ? $html : $result;
	}

	/**
	 * Xử lý các directive tương tác có trong nội dung HTML
	 * và cập nhật markup tương ứng.
	 *
	 * Nó sử dụng ngăn xếp context và namespace của instance WP_Interactivity_API,
	 * được chia sẻ giữa tất cả các lệnh gọi.
	 *
	 * Phương thức này trả về null nếu HTML chứa các thẻ không cân bằng.
	 *
	 * @since 6.6.0
	 *
	 * @param string $html Nội dung HTML cần xử lý.
	 * @return string|null Nội dung HTML đã xử lý. Trả về null khi HTML chứa các thẻ không cân bằng.
	 */
	private function _process_directives( string $html ) {
		$p          = new WP_Interactivity_API_Directives_Processor( $html );
		$tag_stack  = array();
		$unbalanced = false;

		$directive_processor_prefixes          = array_keys( self::$directive_processors );
		$directive_processor_prefixes_reversed = array_reverse( $directive_processor_prefixes );

		/*
		 * Lưu kích thước hiện tại của mỗi ngăn xếp để khôi phục chúng
		 * trong trường hợp quá trình xử lý tìm thấy các thẻ không cân bằng.
		 */
		$namespace_stack_size = count( $this->namespace_stack );
		$context_stack_size   = count( $this->context_stack );

		while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag_name = $p->get_tag();

			/*
			 * Các directive bên trong thẻ SVG và MATH không được xử lý,
			 * vì chúng chưa tương thích với Tag Processor.
			 * Chúng ta vẫn xử lý phần còn lại của HTML.
			 */
			if ( 'SVG' === $tag_name || 'MATH' === $tag_name ) {
				if ( $p->get_attribute_names_with_prefix( 'data-wp-' ) ) {
					/* translators: 1: SVG or MATH HTML tag, 2: Namespace of the interactive block. */
					$message = sprintf( __( 'Interactivity directives were detected on an incompatible %1$s tag when processing "%2$s". These directives will be ignored in the server side render.' ), $tag_name, end( $this->namespace_stack ) );
					_doing_it_wrong( __METHOD__, $message, '6.6.0' );
				}
				$p->skip_to_tag_closer();
				continue;
			}

			if ( $p->is_tag_closer() ) {
				list( $opening_tag_name, $directives_prefixes ) = end( $tag_stack );

				if ( 0 === count( $tag_stack ) || $opening_tag_name !== $tag_name ) {

					/*
					 * Nếu ngăn xếp thẻ trống hoặc thẻ mở tương ứng không giống
					 * với thẻ đóng, nghĩa là HTML không cân bằng và nó sẽ
					 * dừng xử lý.
					 */
					$unbalanced = true;
					break;
				} else {
					// Xóa thẻ cuối cùng khỏi ngăn xếp.
					array_pop( $tag_stack );
				}
			} else {
				if ( 0 !== count( $p->get_attribute_names_with_prefix( 'data-wp-each-child' ) ) ) {
					/*
					 * Nếu thẻ có directive `data-wp-each-child`, nhảy đến thẻ đóng
					 * của nó vì những thẻ đó đã được xử lý rồi.
					 */
					$p->next_balanced_tag_closer_tag();
					continue;
				} else {
					$directives_prefixes = array();

					// Kiểm tra xem có bộ xử lý directive phía máy chủ nào được đăng ký cho mỗi directive hay không.
					foreach ( $p->get_attribute_names_with_prefix( 'data-wp-' ) as $attribute_name ) {
						if ( ! preg_match(
							/*
							 * Regex này phải khớp với regex phía client được sử dụng bởi interactivity API.
							 * @see https://github.com/WordPress/gutenberg/blob/ca616014255efbb61f34c10917d52a2d86c1c660/packages/interactivity/src/vdom.ts#L20-L32
							 */
							'/' .
							'^data-wp-' .
							// Khớp các ký tự chữ và số bao gồm các đoạn phân cách bằng dấu gạch ngang.
							// Loại trừ gạch dưới một cách có chủ đích để tránh nhầm lẫn.
							// Ví dụ: "custom-directive".
							'([a-z0-9]+(?:-[a-z0-9]+)*)' .
							// (Tùy chọn) Khớp '--' theo sau bởi bất kỳ ký tự chữ và số nào. Loại trừ
							// gạch dưới một cách có chủ đích để tránh nhầm lẫn, nhưng có thể chứa
							// nhiều dấu gạch ngang. Ví dụ: "--custom-prefix--with-more-info".
							'(?:--([a-z0-9_-]+))?$' .
							'/i',
							$attribute_name
						) ) {
							continue;
						}
						list( $directive_prefix ) = $this->extract_prefix_and_suffix( $attribute_name );
						if ( array_key_exists( $directive_prefix, self::$directive_processors ) ) {
							$directives_prefixes[] = $directive_prefix;
						}
					}

					/*
					 * Nếu thẻ này sẽ duyệt đến thẻ đóng của nó, nó sẽ thêm vào ngăn xếp thẻ
					 * để có thể xử lý thẻ đóng và kiểm tra các thẻ không cân bằng.
					 */
					if ( $p->has_and_visits_its_closer_tag() ) {
						$tag_stack[] = array( $tag_name, $directives_prefixes );
					}
				}
			}
			/*
			 * Nếu thẻ mở tương ứng không có directive nào, có thể bỏ qua
			 * quá trình xử lý.
			 */
			if ( 0 === count( $directives_prefixes ) ) {
				continue;
			}

			// Việc xử lý directive có thể khác nhau tùy thuộc vào việc đang vào hay ra khỏi thẻ.
			$modes = array(
				'enter' => ! $p->is_tag_closer(),
				'exit'  => $p->is_tag_closer() || ! $p->has_and_visits_its_closer_tag(),
			);

			// Lấy các thuộc tính phần tử để đưa vào biểu diễn phần tử.
			$element_attrs = array();
			$attr_names    = $p->get_attribute_names_with_prefix( '' ) ?? array();

			foreach ( $attr_names as $name ) {
				$element_attrs[ $name ] = $p->get_attribute( $name );
			}

			// Gán phần tử hiện tại ngay trước khi chạy các bộ xử lý directive của nó.
			$this->current_element = array(
				'attributes' => $element_attrs,
			);

			foreach ( $modes as $mode => $should_run ) {
				if ( ! $should_run ) {
					continue;
				}

				/*
				 * Sắp xếp các thuộc tính theo thứ tự của mảng `directives_processor`
				 * và kiểm tra những directive nào có mặt trong phần tử này.
				 */
				$existing_directives_prefixes = array_intersect(
					'enter' === $mode ? $directive_processor_prefixes : $directive_processor_prefixes_reversed,
					$directives_prefixes
				);
				foreach ( $existing_directives_prefixes as $directive_prefix ) {
					$func = is_array( self::$directive_processors[ $directive_prefix ] )
						? self::$directive_processors[ $directive_prefix ]
						: array( $this, self::$directive_processors[ $directive_prefix ] );

					call_user_func_array( $func, array( $p, $mode, &$tag_stack ) );
				}
			}

			// Xóa phần tử hiện tại.
			$this->current_element = null;
		}

		if ( $unbalanced ) {
			// Khôi phục ngăn xếp namespace và context về giá trị trước đó.
			array_splice( $this->namespace_stack, $namespace_stack_size );
			array_splice( $this->context_stack, $context_stack_size );
		}

		/*
		 * Trả về null nếu HTML không cân bằng vì HTML không cân bằng
		 * không an toàn để xử lý. Trong trường hợp đó, runtime của Interactivity API sẽ
		 * cập nhật HTML ở phía client trong quá trình hydration. Nó cũng sẽ
		 * hiển thị thông báo cho nhà phát triển để thông tin về vấn đề này.
		 */
		if ( $unbalanced || 0 < count( $tag_stack ) ) {
			$tag_errored = 0 < count( $tag_stack ) ? end( $tag_stack )[0] : $tag_name;
			/* translators: %1s: Namespace processed, %2s: The tag that caused the error; could be any HTML tag.  */
			$message = sprintf( __( 'Interactivity directives failed to process in "%1$s" due to a missing "%2$s" end tag.' ), end( $this->namespace_stack ), $tag_errored );
			_doing_it_wrong( __METHOD__, $message, '6.6.0' );
			return null;
		}

		return $p->get_updated_html();
	}

	/**
	 * Đánh giá đường dẫn tham chiếu được truyền cho directive dựa trên
	 * namespace store, trạng thái và context hiện tại.
	 *
	 * @since 6.5.0
	 * @since 6.6.0 Hàm giờ thêm cảnh báo khi namespace là null, falsy, hoặc giá trị directive rỗng.
	 * @since 6.6.0 Đã xóa các tham số `default_namespace` và `context`.
	 * @since 6.6.0 Thêm hỗ trợ cho trạng thái phái sinh.
	 *
	 * @param string|true $directive_value Chuỗi giá trị thuộc tính directive hoặc `true` khi là thuộc tính boolean.
	 * @return mixed|null Kết quả đánh giá. Null nếu đường dẫn tham chiếu không tồn tại hoặc namespace là falsy.
	 */
	private function evaluate( $directive_value ) {
		$default_namespace = end( $this->namespace_stack );
		$context           = end( $this->context_stack );

		list( $ns, $path ) = $this->extract_directive_value( $directive_value, $default_namespace );
		if ( ! $ns || ! $path ) {
			/* translators: %s: The directive value referenced. */
			$message = sprintf( __( 'Namespace or reference path cannot be empty. Directive value referenced: %s' ), $directive_value );
			_doing_it_wrong( __METHOD__, $message, '6.6.0' );
			return null;
		}

		$store = array(
			'state'   => $this->state_data[ $ns ] ?? array(),
			'context' => $context[ $ns ] ?? array(),
		);

		// Kiểm tra xem đường dẫn tham chiếu có được đi trước bởi toán tử phủ định (!) hay không.
		$should_negate_value = '!' === $path[0];
		$path                = $should_negate_value ? substr( $path, 1 ) : $path;

		// Trích xuất giá trị từ store bằng đường dẫn tham chiếu.
		$path_segments = explode( '.', $path );
		$current       = $store;
		foreach ( $path_segments as $path_segment ) {
			/*
			 * Trường hợp đặc biệt cho mảng số và chuỗi. Thêm thuộc tính
			 * length mô phỏng hành vi của JavaScript.
			 *
			 * @since 6.8.0
			 */
			if ( 'length' === $path_segment ) {
				if ( is_array( $current ) && array_is_list( $current ) ) {
					$current = count( $current );
					break;
				}

				if ( is_string( $current ) ) {
					/*
					 * Sự khác biệt về mã hóa giữa chuỗi PHP và
					 * JavaScript có nghĩa là việc tính toán độ dài chuỗi
					 * mà JavaScript sẽ thấy từ PHP là phức tạp.
					 * `strlen` là một xấp xỉ hợp lý.
					 *
					 * Người dùng muốn độ dài chính xác hơn có thể có
					 * nhu cầu chính xác hơn "bytelength" và nên
					 * triển khai tính toán độ dài riêng trong trạng thái
					 * phái sinh có tính đến mã hóa và đầu ra mong muốn
					 * (codepoints, graphemes, bytes, v.v.).
					 */
					$current = strlen( $current );
					break;
				}
			}

			if ( ( is_array( $current ) || $current instanceof ArrayAccess ) && isset( $current[ $path_segment ] ) ) {
				$current = $current[ $path_segment ];
			} elseif ( is_object( $current ) && isset( $current->$path_segment ) ) {
				$current = $current->$path_segment;
			} else {
				$current = null;
				break;
			}

			if ( $current instanceof Closure ) {
				/*
				 * Namespace của getter trạng thái này được thêm vào ngăn xếp để
				 * `state()` hoặc `get_config()` đọc namespace đó khi được gọi
				 * mà không chỉ định namespace.
				 */
				array_push( $this->namespace_stack, $ns );
				try {
					$current = $current();
				} catch ( Throwable $e ) {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: 1: Path pointing to an Interactivity API state property, 2: Namespace for an Interactivity API store. */
							__( 'Uncaught error executing a derived state callback with path "%1$s" and namespace "%2$s".' ),
							$path,
							$ns
						),
						'6.6.0'
					);
					return null;
				} finally {
					// Xóa namespace của thuộc tính khỏi ngăn xếp.
					array_pop( $this->namespace_stack );
				}
			}
		}

		// Trả về giá trị đảo ngược nếu chứa toán tử phủ định (!).
		return $should_negate_value ? ! $current : $current;
	}

	/**
	 * Trích xuất tên thuộc tính directive để tách và trả về tiền tố directive
	 * và hậu tố tùy chọn.
	 *
	 * Hậu tố là chuỗi sau dấu gạch ngang kép đầu tiên và tiền tố là
	 * mọi thứ phía trước hậu tố.
	 *
	 * Ví dụ:
	 *
	 *     extract_prefix_and_suffix( 'data-wp-interactive' )   => array( 'data-wp-interactive', null )
	 *     extract_prefix_and_suffix( 'data-wp-bind--src' )     => array( 'data-wp-bind', 'src' )
	 *     extract_prefix_and_suffix( 'data-wp-foo--and--bar' ) => array( 'data-wp-foo', 'and--bar' )
	 *
	 * @since 6.5.0
	 *
	 * @param string $directive_name Tên thuộc tính directive.
	 * @return array Mảng chứa tiền tố directive và hậu tố tùy chọn.
	 */
	private function extract_prefix_and_suffix( string $directive_name ): array {
		return explode( '--', $directive_name, 2 );
	}

	/**
	 * Phân tích và trích xuất namespace và đường dẫn tham chiếu từ giá trị
	 * thuộc tính directive đã cho.
	 *
	 * Nếu giá trị không chứa namespace rõ ràng, nó trả về namespace mặc định.
	 * Nếu giá trị chứa đối tượng JSON thay vì đường dẫn tham chiếu, hàm sẽ
	 * cố phân tích và trả về mảng kết quả. Nếu giá trị chứa chuỗi đại diện
	 * cho boolean ("true" và "false"), số ("1" và "1.2") hoặc "null", hàm
	 * cũng chuyển đổi chúng thành boolean, số và `null` thông thường.
	 *
	 * Ví dụ:
	 *
	 *     extract_directive_value( 'actions.foo', 'myPlugin' )                      => array( 'myPlugin', 'actions.foo' )
	 *     extract_directive_value( 'otherPlugin::actions.foo', 'myPlugin' )         => array( 'otherPlugin', 'actions.foo' )
	 *     extract_directive_value( '{ "isOpen": false }', 'myPlugin' )              => array( 'myPlugin', array( 'isOpen' => false ) )
	 *     extract_directive_value( 'otherPlugin::{ "isOpen": false }', 'myPlugin' ) => array( 'otherPlugin', array( 'isOpen' => false ) )
	 *
	 * @since 6.5.0
	 *
	 * @param string|true $directive_value   Giá trị thuộc tính directive. Có thể là `true` khi là thuộc tính boolean.
	 * @param string|null $default_namespace Tùy chọn. Namespace mặc định nếu không có namespace nào được định nghĩa rõ ràng.
	 * @return array Mảng chứa namespace ở phần tử đầu tiên và JSON, đường dẫn tham chiếu, hoặc null ở phần tử thứ hai.
	 */
	private function extract_directive_value( $directive_value, $default_namespace = null ): array {
		if ( empty( $directive_value ) || is_bool( $directive_value ) ) {
			return array( $default_namespace, null );
		}

		// Thay thế giá trị và namespace nếu có namespace trong giá trị.
		if ( 1 === preg_match( '/^([\w\-_\/]+)::./', $directive_value ) ) {
			list($default_namespace, $directive_value) = explode( '::', $directive_value, 2 );
		}

		/*
		 * Cố giải mã giá trị dưới dạng đối tượng JSON. Nếu thất bại và giá trị
		 * không phải `null`, nó trả về giá trị nguyên bản. Ngược lại, nó trả về
		 * JSON đã giải mã hoặc null cho chuỗi `null`.
		 */
		$decoded_json = json_decode( $directive_value, true );
		if ( null !== $decoded_json || 'null' === $directive_value ) {
			$directive_value = $decoded_json;
		}

		return array( $default_namespace, $directive_value );
	}

	/**
	 * Chuyển đổi chuỗi kebab-case thành camelCase.
	 *
	 * @param string $str Chuỗi kebab-case cần chuyển đổi thành camelCase.
	 * @return string Chuỗi camelCase đã chuyển đổi.
	 */
	private function kebab_to_camel_case( string $str ): string {
		return lcfirst(
			preg_replace_callback(
				'/(-)([a-z])/',
				function ( $matches ) {
					return strtoupper( $matches[2] );
				},
				strtolower( rtrim( $str, '-' ) )
			)
		);
	}

	/**
	 * Xử lý directive `data-wp-interactive`.
	 *
	 * Nó thêm namespace store mặc định được định nghĩa trong giá trị directive vào
	 * ngăn xếp để có sẵn cho các phần tử tương tác lồng nhau.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p    Instance bộ xử lý directive.
	 * @param string                                    $mode Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_interactive_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		// Khi thoát khỏi thẻ, xóa namespace cuối cùng khỏi ngăn xếp.
		if ( 'exit' === $mode ) {
			array_pop( $this->namespace_stack );
			return;
		}

		// Cố giải mã giá trị thuộc tính `data-wp-interactive`.
		$attribute_value = $p->get_attribute( 'data-wp-interactive' );

		/*
		 * Đẩy namespace mới được định nghĩa hoặc namespace hiện tại nếu định nghĩa
		 * `data-wp-interactive` không hợp lệ hoặc không chứa namespace. Nó làm vậy
		 * vì hàm sẽ lấy namespace hiện tại ra khỏi ngăn xếp bất cứ khi nào tìm thấy
		 * thẻ đóng của `data-wp-interactive`, bất kể định nghĩa `data-wp-interactive`
		 * trước đó có chứa namespace hợp lệ hay không.
		 */
		$new_namespace = null;
		if ( is_string( $attribute_value ) && ! empty( $attribute_value ) ) {
			$decoded_json = json_decode( $attribute_value, true );
			if ( is_array( $decoded_json ) ) {
				$new_namespace = $decoded_json['namespace'] ?? null;
			} else {
				$new_namespace = $attribute_value;
			}
		}
		$this->namespace_stack[] = ( $new_namespace && 1 === preg_match( '/^([\w\-_\/]+)/', $new_namespace ) )
			? $new_namespace
			: end( $this->namespace_stack );
	}

	/**
	 * Xử lý directive `data-wp-context`.
	 *
	 * Nó thêm context được định nghĩa trong giá trị directive vào ngăn xếp để
	 * có sẵn cho các phần tử tương tác lồng nhau.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_context_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		// Khi thoát khỏi thẻ, xóa context cuối cùng khỏi ngăn xếp.
		if ( 'exit' === $mode ) {
			array_pop( $this->context_stack );
			return;
		}

		$attribute_value = $p->get_attribute( 'data-wp-context' );
		$namespace_value = end( $this->namespace_stack );

		// Tách namespace khỏi đối tượng JSON context.
		list( $namespace_value, $decoded_json ) = is_string( $attribute_value ) && ! empty( $attribute_value )
			? $this->extract_directive_value( $attribute_value, $namespace_value )
			: array( $namespace_value, null );

		/*
		 * Nếu có namespace, nó thêm context mới vào ngăn xếp bằng cách gộp
		 * context trước đó với context mới.
		 */
		if ( is_string( $namespace_value ) ) {
			$this->context_stack[] = array_replace_recursive(
				end( $this->context_stack ) !== false ? end( $this->context_stack ) : array(),
				array( $namespace_value => is_array( $decoded_json ) ? $decoded_json : array() )
			);
		} else {
			/*
			 * Nếu không có namespace, nó đẩy context hiện tại vào ngăn xếp.
			 * Cần làm vậy vì hàm sẽ lấy context hiện tại ra khỏi ngăn xếp
			 * bất cứ khi nào tìm thấy thẻ đóng của `data-wp-context`.
			 */
			$this->context_stack[] = end( $this->context_stack );
		}
	}

	/**
	 * Xử lý directive `data-wp-bind`.
	 *
	 * Nó cập nhật hoặc xóa các thuộc tính được ràng buộc dựa trên kết quả đánh giá
	 * tham chiếu liên quan.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_bind_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		if ( 'enter' === $mode ) {
			$all_bind_directives = $p->get_attribute_names_with_prefix( 'data-wp-bind--' );

			foreach ( $all_bind_directives as $attribute_name ) {
				list( , $bound_attribute ) = $this->extract_prefix_and_suffix( $attribute_name );
				if ( empty( $bound_attribute ) ) {
					return;
				}

				$attribute_value = $p->get_attribute( $attribute_name );
				$result          = $this->evaluate( $attribute_value );

				if (
					null !== $result &&
					(
						false !== $result ||
						( strlen( $bound_attribute ) > 5 && '-' === $bound_attribute[4] )
					)
				) {
					/*
					 * Nếu kết quả đánh giá là boolean và thuộc tính là `aria-` hoặc `data-`,
					 * chuyển đổi nó thành chuỗi "true" hoặc "false". Nó tuân theo logic
					 * giống hệt Preact vì cần tái tạo những gì Preact sẽ thực hiện sau đó
					 * ở phía client:
					 * https://github.com/preactjs/preact/blob/ea49f7a0f9d1ff2c98c0bdd66aa0cbc583055246/src/diff/props.js#L131C24-L136
					 */
					if (
						is_bool( $result ) &&
						( strlen( $bound_attribute ) > 5 && '-' === $bound_attribute[4] )
					) {
						$result = $result ? 'true' : 'false';
					}
					$p->set_attribute( $bound_attribute, $result );
				} else {
					$p->remove_attribute( $bound_attribute );
				}
			}
		}
	}

	/**
	 * Xử lý directive `data-wp-class`.
	 *
	 * Nó thêm hoặc xóa các lớp CSS trong phần tử HTML hiện tại dựa trên
	 * kết quả đánh giá các tham chiếu liên quan.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_class_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		if ( 'enter' === $mode ) {
			$all_class_directives = $p->get_attribute_names_with_prefix( 'data-wp-class--' );

			foreach ( $all_class_directives as $attribute_name ) {
				list( , $class_name ) = $this->extract_prefix_and_suffix( $attribute_name );
				if ( empty( $class_name ) ) {
					return;
				}

				$attribute_value = $p->get_attribute( $attribute_name );
				$result          = $this->evaluate( $attribute_value );

				if ( $result ) {
					$p->add_class( $class_name );
				} else {
					$p->remove_class( $class_name );
				}
			}
		}
	}

	/**
	 * Xử lý directive `data-wp-style`.
	 *
	 * Nó cập nhật giá trị thuộc tính style của phần tử HTML hiện tại dựa trên
	 * kết quả đánh giá các tham chiếu liên quan.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_style_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		if ( 'enter' === $mode ) {
			$all_style_attributes = $p->get_attribute_names_with_prefix( 'data-wp-style--' );

			foreach ( $all_style_attributes as $attribute_name ) {
				list( , $style_property ) = $this->extract_prefix_and_suffix( $attribute_name );
				if ( empty( $style_property ) ) {
					continue;
				}

				$directive_attribute_value = $p->get_attribute( $attribute_name );
				$style_property_value      = $this->evaluate( $directive_attribute_value );
				$style_attribute_value     = $p->get_attribute( 'style' );
				$style_attribute_value     = ( $style_attribute_value && ! is_bool( $style_attribute_value ) ) ? $style_attribute_value : '';

				/*
				 * Kiểm tra trước xem thuộc tính style có phải falsy không và giá trị
				 * thuộc tính style có rỗng không, vì nếu rỗng thì không cần
				 * cập nhật giá trị thuộc tính.
				 */
				if ( $style_property_value || $style_attribute_value ) {
					$style_attribute_value = $this->merge_style_property( $style_attribute_value, $style_property, $style_property_value );
					/*
					 * Nếu giá trị thuộc tính style không rỗng, đặt giá trị. Ngược lại,
					 * xóa nó.
					 */
					if ( ! empty( $style_attribute_value ) ) {
						$p->set_attribute( 'style', $style_attribute_value );
					} else {
						$p->remove_attribute( 'style' );
					}
				}
			}
		}
	}

	/**
	 * Gộp một thuộc tính style riêng lẻ trong thuộc tính `style` của phần tử HTML,
	 * cập nhật hoặc xóa thuộc tính khi cần thiết.
	 *
	 * Nếu một thuộc tính bị sửa đổi, thuộc tính cũ sẽ bị xóa và thuộc tính mới
	 * được thêm vào cuối danh sách.
	 *
	 * @since 6.5.0
	 *
	 * Ví dụ:
	 *
	 *     merge_style_property( 'color:green;', 'color', 'red' )      => 'color:red;'
	 *     merge_style_property( 'background:green;', 'color', 'red' ) => 'background:green;color:red;'
	 *     merge_style_property( 'color:green;', 'color', null )       => ''
	 *
	 * @param string            $style_attribute_value Giá trị thuộc tính style hiện tại.
	 * @param string            $style_property_name   Tên thuộc tính style cần đặt.
	 * @param string|false|null $style_property_value  Giá trị cần đặt cho thuộc tính style. Với false, null hoặc
	 *                                                 chuỗi rỗng, thuộc tính style sẽ bị xóa.
	 * @return string Giá trị thuộc tính style mới sau khi thuộc tính đã được thêm, cập nhật hoặc xóa.
	 */
	private function merge_style_property( string $style_attribute_value, string $style_property_name, $style_property_value ): string {
		$style_assignments    = explode( ';', $style_attribute_value );
		$result               = array();
		$style_property_value = ! empty( $style_property_value ) ? rtrim( trim( $style_property_value ), ';' ) : null;
		$new_style_property   = $style_property_value ? $style_property_name . ':' . $style_property_value . ';' : '';

		// Tạo mảng với tất cả các thuộc tính ngoại trừ thuộc tính bị sửa đổi.
		foreach ( $style_assignments as $style_assignment ) {
			if ( empty( trim( $style_assignment ) ) ) {
				continue;
			}
			list( $name, $value ) = explode( ':', $style_assignment );
			if ( trim( $name ) !== $style_property_name ) {
				$result[] = trim( $name ) . ':' . trim( $value ) . ';';
			}
		}

		// Thêm thuộc tính mới/đã sửa đổi vào cuối danh sách.
		$result[] = $new_style_property;

		return implode( '', $result );
	}

	/**
	 * Xử lý directive `data-wp-text`.
	 *
	 * Nó cập nhật nội dung bên trong của phần tử HTML hiện tại dựa trên
	 * kết quả đánh giá tham chiếu liên quan.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_text_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		if ( 'enter' === $mode ) {
			$attribute_value = $p->get_attribute( 'data-wp-text' );
			$result          = $this->evaluate( $attribute_value );

			/*
			 * Tuân theo logic giống Preact ở phía client và chỉ thay đổi nội dung
			 * nếu giá trị là chuỗi hoặc số. Ngược lại, nó xóa nội dung.
			 */
			if ( is_string( $result ) || is_numeric( $result ) ) {
				$p->set_content_between_balanced_tags( esc_html( $result ) );
			} else {
				$p->set_content_between_balanced_tags( '' );
			}
		}
	}

	/**
	 * Trả về các kiểu CSS cho hiệu ứng thanh tải phía trên trong router.
	 *
	 * @since 6.5.0
	 *
	 * @return string Các kiểu CSS cho hiệu ứng thanh tải phía trên của router.
	 */
	private function get_router_animation_styles(): string {
		return <<<CSS
			.wp-interactivity-router-loading-bar {
				position: fixed;
				top: 0;
				left: 0;
				margin: 0;
				padding: 0;
				width: 100vw;
				max-width: 100vw !important;
				height: 4px;
				background-color: #000;
				opacity: 0
			}
			.wp-interactivity-router-loading-bar.start-animation {
				animation: wp-interactivity-router-loading-bar-start-animation 30s cubic-bezier(0.03, 0.5, 0, 1) forwards
			}
			.wp-interactivity-router-loading-bar.finish-animation {
				animation: wp-interactivity-router-loading-bar-finish-animation 300ms ease-in
			}
			@keyframes wp-interactivity-router-loading-bar-start-animation {
				0% { transform: scaleX(0); transform-origin: 0 0; opacity: 1 }
				100% { transform: scaleX(1); transform-origin: 0 0; opacity: 1 }
			}
			@keyframes wp-interactivity-router-loading-bar-finish-animation {
				0% { opacity: 1 }
				50% { opacity: 1 }
				100% { opacity: 0 }
			}
CSS;
	}

	/**
	 * Đã ngừng sử dụng.
	 *
	 * @since 6.5.0
	 * @deprecated 6.7.0 Sử dụng {@see WP_Interactivity_API::print_router_markup} thay thế.
	 */
	public function print_router_loading_and_screen_reader_markup() {
		_deprecated_function( __METHOD__, '6.7.0', 'WP_Interactivity_API::print_router_markup' );

		// Gọi phương thức mới.
		$this->print_router_markup();
	}

	/**
	 * Xuất mã đánh dấu cho module script @wordpress/interactivity-router.
	 *
	 * Phương thức này in một phần tử div đại diện cho thanh tải hiển thị
	 * trong quá trình điều hướng.
	 *
	 * @since 6.7.0
	 */
	public function print_router_markup() {
		echo <<<HTML
			<div
				class="wp-interactivity-router-loading-bar"
				data-wp-interactive="core/router"
				data-wp-class--start-animation="state.navigation.hasStarted"
				data-wp-class--finish-animation="state.navigation.hasFinished"
			></div>
HTML;
	}

	/**
	 * Xử lý directive `data-wp-router-region`.
	 *
	 * Nó kết xuất trong footer một tập hợp các phần tử HTML để thông báo cho người dùng
	 * về điều hướng phía client. Cụ thể hơn, các phần tử được thêm là 1) thanh tải
	 * phía trên để thông báo trực quan rằng điều hướng đang diễn ra và 2) vùng
	 * `aria-live` cho thông báo điều hướng trợ năng.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 */
	private function data_wp_router_region_processor( WP_Interactivity_API_Directives_Processor $p, string $mode ) {
		if ( 'enter' === $mode && ! $this->has_processed_router_region ) {
			$this->has_processed_router_region = true;

			// Đưa vào hàng đợi dưới dạng kiểu nội tuyến.
			wp_register_style( 'wp-interactivity-router-animations', false );
			wp_add_inline_style( 'wp-interactivity-router-animations', $this->get_router_animation_styles() );
			wp_enqueue_style( 'wp-interactivity-router-animations' );

			// Thêm mã đánh dấu cần thiết vào footer.
			add_action( 'wp_footer', array( $this, 'print_router_markup' ) );
		}
	}

	/**
	 * Xử lý directive `data-wp-each`.
	 *
	 * Directive này nhận một mảng được truyền dưới dạng tham chiếu và lặp qua nó
	 * để tạo nội dung mới cho mỗi mục dựa trên mã đánh dấu bên trong
	 * thẻ `template`.
	 *
	 * @since 6.5.0
	 *
	 * @param WP_Interactivity_API_Directives_Processor $p               Instance bộ xử lý directive.
	 * @param string                                    $mode            Xử lý đang vào hay ra khỏi thẻ.
	 * @param array                                     $tag_stack       Tham chiếu đến ngăn xếp thẻ.
	 */
	private function data_wp_each_processor( WP_Interactivity_API_Directives_Processor $p, string $mode, array &$tag_stack ) {
		if ( 'enter' === $mode && 'TEMPLATE' === $p->get_tag() ) {
			$attribute_name   = $p->get_attribute_names_with_prefix( 'data-wp-each' )[0];
			$extracted_suffix = $this->extract_prefix_and_suffix( $attribute_name );
			$item_name        = isset( $extracted_suffix[1] ) ? $this->kebab_to_camel_case( $extracted_suffix[1] ) : 'item';
			$attribute_value  = $p->get_attribute( $attribute_name );
			$result           = $this->evaluate( $attribute_value );

			// Lấy nội dung giữa các thẻ template và để con trỏ ở thẻ đóng.
			$inner_content = $p->get_content_between_balanced_template_tags();

			// Kiểm tra xem có xử lý directive phía máy chủ thủ công hay không.
			$template_end = 'data-wp-each: template end';
			$p->set_bookmark( $template_end );
			$p->next_tag();
			$manual_sdp = $p->get_attribute( 'data-wp-each-child' );
			$p->seek( $template_end ); // Quay lại thẻ đóng template.
			$p->release_bookmark( $template_end );

			/*
			 * Không xử lý trong các trường hợp sau:
			 * - Xử lý directive phía máy chủ thủ công.
			 * - Giá trị rỗng hoặc không phải mảng.
			 * - Mảng kết hợp vì chúng được giải tuần tự hóa thành đối tượng trong JS.
			 * - Template chứa văn bản cấp cao nhất vì văn bản đó không thể
			 *   được nhận dạng và xóa ở phía client.
			 */
			if (
				$manual_sdp ||
				empty( $result ) ||
				! is_array( $result ) ||
				! array_is_list( $result ) ||
				! str_starts_with( trim( $inner_content ), '<' ) ||
				! str_ends_with( trim( $inner_content ), '>' )
			) {
				array_pop( $tag_stack );
				return;
			}

			// Trích xuất namespace từ giá trị thuộc tính directive.
			$namespace_value         = end( $this->namespace_stack );
			list( $namespace_value ) = is_string( $attribute_value ) && ! empty( $attribute_value )
				? $this->extract_directive_value( $attribute_value, $namespace_value )
				: array( $namespace_value, null );

			// Xử lý nội dung bên trong cho mỗi phần tử của mảng.
			$processed_content = '';
			foreach ( $result as $item ) {
				// Tạo context mới bao gồm phần tử hiện tại của mảng.
				$this->context_stack[] = array_replace_recursive(
					end( $this->context_stack ) !== false ? end( $this->context_stack ) : array(),
					array( $namespace_value => array( $item_name => $item ) )
				);

				// Xử lý nội dung bên trong với context mới.
				$processed_item = $this->_process_directives( $inner_content );

				if ( null === $processed_item ) {
					// Nếu HTML không cân bằng, dừng xử lý.
					array_pop( $this->context_stack );
					return;
				}

				// Thêm `data-wp-each-child` vào mỗi thẻ cấp cao nhất.
				$i = new WP_Interactivity_API_Directives_Processor( $processed_item );
				while ( $i->next_tag() ) {
					$i->set_attribute( 'data-wp-each-child', true );
					$i->next_balanced_tag_closer_tag();
				}
				$processed_content .= $i->get_updated_html();

				// Xóa context hiện tại khỏi ngăn xếp.
				array_pop( $this->context_stack );
			}

			// Thêm nội dung đã xử lý sau thẻ đóng của template.
			$p->append_content_after_template_tag_closer( $processed_content );

			// Lấy thẻ cuối cùng ra vì nó đã bỏ qua thẻ đóng của thẻ template.
			array_pop( $tag_stack );
		}
	}
}
