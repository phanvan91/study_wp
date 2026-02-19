<?php
/**
 * API Blocks: Lớp WP_Block
 *
 * @package WordPress
 * @since 5.5.0
 */

/**
 * Lớp đại diện cho một thể hiện block đã được phân tích cú pháp.
 *
 * @since 5.5.0
 * @property array $attributes
 */
#[AllowDynamicProperties]
class WP_Block {

	/**
	 * Mảng biểu diễn gốc của block đã được phân tích cú pháp.
	 *
	 * @since 5.5.0
	 * @var array
	 */
	public $parsed_block;

	/**
	 * Tên của block.
	 *
	 * @example "core/paragraph"
	 *
	 * @since 5.5.0
	 * @var string
	 */
	public $name;

	/**
	 * Loại block liên kết với thể hiện này.
	 *
	 * @since 5.5.0
	 * @var WP_Block_Type
	 */
	public $block_type;

	/**
	 * Các giá trị ngữ cảnh của block.
	 *
	 * @since 5.5.0
	 * @var array
	 */
	public $context = array();

	/**
	 * Tất cả ngữ cảnh khả dụng của cấp bậc hiện tại.
	 *
	 * @since 5.5.0
	 * @var array
	 * @access protected
	 */
	protected $available_context = array();

	/**
	 * Registry loại block.
	 *
	 * @since 5.9.0
	 * @var WP_Block_Type_Registry
	 * @access protected
	 */
	protected $registry;

	/**
	 * Danh sách các block con (cùng lớp này)
	 *
	 * @since 5.5.0
	 * @var WP_Block_List
	 */
	public $inner_blocks = array();

	/**
	 * HTML kết quả từ bên trong các dấu phân cách comment block
	 * sau khi loại bỏ các block con.
	 *
	 * @example "...Just <!-- wp:test /--> testing..." -> "Just testing..."
	 *
	 * @since 5.5.0
	 * @var string
	 */
	public $inner_html = '';

	/**
	 * Danh sách các đoạn chuỗi và các dấu null nơi các block con được tìm thấy
	 *
	 * @example array(
	 *   'inner_html'    => 'BeforeInnerAfter',
	 *   'inner_blocks'  => array( block, block ),
	 *   'inner_content' => array( 'Before', null, 'Inner', null, 'After' ),
	 * )
	 *
	 * @since 5.5.0
	 * @var array
	 */
	public $inner_content = array();

	/**
	 * Hàm khởi tạo.
	 *
	 * Điền các thuộc tính đối tượng từ tham số thể hiện block được cung cấp.
	 *
	 * Mảng giá trị ngữ cảnh được cung cấp không nhất thiết sẽ có sẵn trên chính
	 * thể hiện này, mà được coi là tập hợp đầy đủ các giá trị được cung cấp bởi
	 * tổ tiên của block. Giá trị này được gán cho thuộc tính riêng tư `available_context`.
	 * Chỉ các giá trị được cấu hình để block sử dụng thông qua loại block đã đăng ký
	 * mới được gán cho thuộc tính `context` của block.
	 *
	 * @since 5.5.0
	 *
	 * @param array                  $block             {
	 *     Mảng liên kết của một đối tượng block đã phân tích cú pháp đơn lẻ. Xem WP_Block_Parser_Block.
	 *
	 *     @type string   $blockName    Tên của block.
	 *     @type array    $attrs        Thuộc tính từ các dấu phân cách comment block.
	 *     @type array    $innerBlocks  Danh sách các block con. Mảng các mảng có cùng
	 *                                  cấu trúc với mảng này.
	 *     @type string   $innerHTML    HTML từ bên trong các dấu phân cách comment block.
	 *     @type array    $innerContent Danh sách các đoạn chuỗi và dấu null nơi các block con được tìm thấy.
	 * }
	 * @param array                  $available_context Mảng tùy chọn các giá trị ngữ cảnh tổ tiên.
	 * @param WP_Block_Type_Registry $registry          Registry loại block tùy chọn.
	 */
	public function __construct( $block, $available_context = array(), $registry = null ) {
		$this->parsed_block = $block;
		$this->name         = $block['blockName'];

		if ( is_null( $registry ) ) {
			$registry = WP_Block_Type_Registry::get_instance();
		}

		$this->registry = $registry;

		$this->block_type = $registry->get_registered( $this->name );

		$this->available_context = $available_context;

		$this->refresh_context_dependents();
	}

	/**
	 * Cập nhật ngữ cảnh cho block hiện tại và các block con của nó.
	 *
	 * Phương thức cập nhật ngữ cảnh của các block con, nếu có, bằng cách truyền xuống
	 * bất kỳ giá trị ngữ cảnh nào mà block cung cấp (`provides_context`).
	 *
	 * Nếu block có các block con, phương thức sẽ xử lý đệ quy chúng bằng cách tạo các
	 * thể hiện mới của `WP_Block` cho mỗi block con và cập nhật ngữ cảnh dựa trên
	 * thuộc tính `provides_context` của block.
	 *
	 * @since 6.8.0
	 */
	public function refresh_context_dependents() {
		/*
		 * Việc hợp nhất thuộc tính `$context` ở đây không lý tưởng, nhưng hiện tại cần thiết vì lý do
		 * tương thích ngược. Lý tưởng nhất, thuộc tính `$context` sẽ không thể lọc được trực tiếp
		 * và chỉ `$available_context` mới có thể lọc được.
		 * Tuy nhiên, cần phải tìm hiểu riêng xem liệu có thể thực hiện điều này mà không gây lỗi hay không.
		 */
		$this->available_context = array_merge( $this->available_context, $this->context );

		if ( ! empty( $this->block_type->uses_context ) ) {
			foreach ( $this->block_type->uses_context as $context_name ) {
				if ( array_key_exists( $context_name, $this->available_context ) ) {
					$this->context[ $context_name ] = $this->available_context[ $context_name ];
				}
			}
		}

		$this->refresh_parsed_block_dependents();
	}

	/**
	 * Cập nhật nội dung block đã phân tích cú pháp cho block hiện tại và các block con.
	 *
	 * Phương thức này thiết lập các thuộc tính `inner_html` và `inner_content` của block dựa trên
	 * nội dung block đã phân tích cú pháp được cung cấp trong quá trình khởi tạo. Nó đảm bảo rằng
	 * thể hiện block phản ánh nội dung cập nhật nhất cho cả HTML bên trong và bất kỳ đoạn chuỗi nào
	 * xung quanh các block con.
	 *
	 * Nếu block có các block con, phương thức này khởi tạo một `WP_Block_List` mới cho chúng,
	 * đảm bảo nội dung và ngữ cảnh chính xác được cập nhật cho mỗi block lồng nhau.
	 *
	 * @since 6.8.0
	 */
	public function refresh_parsed_block_dependents() {
		if ( ! empty( $this->parsed_block['innerBlocks'] ) ) {
			$child_context = $this->available_context;

			if ( ! empty( $this->block_type->provides_context ) ) {
				foreach ( $this->block_type->provides_context as $context_name => $attribute_name ) {
					if ( array_key_exists( $attribute_name, $this->attributes ) ) {
						$child_context[ $context_name ] = $this->attributes[ $attribute_name ];
					}
				}
			}

			$this->inner_blocks = new WP_Block_List( $this->parsed_block['innerBlocks'], $child_context, $this->registry );
		}

		if ( ! empty( $this->parsed_block['innerHTML'] ) ) {
			$this->inner_html = $this->parsed_block['innerHTML'];
		}

		if ( ! empty( $this->parsed_block['innerContent'] ) ) {
			$this->inner_content = $this->parsed_block['innerContent'];
		}
	}

	/**
	 * Trả về giá trị từ một thuộc tính không thể truy cập.
	 *
	 * Được sử dụng để khởi tạo lười thuộc tính `attributes` của block,
	 * sao cho nó chỉ được chuẩn bị với các thuộc tính mặc định tại thời điểm
	 * thuộc tính được truy cập. Với tất cả các thuộc tính không thể truy cập khác,
	 * giá trị `null` được trả về.
	 *
	 * @since 5.5.0
	 *
	 * @param string $name Tên thuộc tính.
	 * @return array|null Các thuộc tính đã chuẩn bị, hoặc null.
	 */
	public function __get( $name ) {
		if ( 'attributes' === $name ) {
			$this->attributes = isset( $this->parsed_block['attrs'] ) ?
				$this->parsed_block['attrs'] :
				array();

			if ( ! is_null( $this->block_type ) ) {
				$this->attributes = $this->block_type->prepare_attributes_for_render( $this->attributes );
			}

			return $this->attributes;
		}

		return null;
	}

	/**
	 * Xử lý các liên kết dữ liệu (bindings) của block và cập nhật thuộc tính block với giá trị từ các nguồn.
	 *
	 * Một block có thể chứa các liên kết dữ liệu trong thuộc tính của nó. Liên kết dữ liệu là ánh xạ
	 * giữa một thuộc tính của block và một nguồn. "Nguồn" là một hàm được đăng ký với
	 * `register_block_bindings_source()` xác định cách lấy giá trị từ bên ngoài block,
	 * ví dụ từ post meta.
	 *
	 * Hàm này sẽ xử lý các liên kết dữ liệu đó và cập nhật thuộc tính của block
	 * với các giá trị đến từ các liên kết dữ liệu.
	 *
	 * ### Ví dụ
	 *
	 * Thuộc tính "bindings" cho block Hình ảnh có thể trông như thế này:
	 *
	 * ```json
	 * {
	 *   "metadata": {
	 *     "bindings": {
	 *       "title": {
	 *         "source": "core/post-meta",
	 *         "args": { "key": "text_custom_field" }
	 *       },
	 *       "url": {
	 *         "source": "core/post-meta",
	 *         "args": { "key": "url_custom_field" }
	 *       }
	 *     }
	 *   }
	 * }
	 * ```
	 *
	 * Ví dụ trên sẽ thay thế thuộc tính `title` và `url` của block Hình ảnh
	 * bằng giá trị của post meta `text_custom_field` và `url_custom_field`.
	 *
	 * @since 6.5.0
	 * @since 6.6.0 Xử lý thuộc tính `__default` cho ghi đè pattern.
	 * @since 6.7.0 Trả về bất kỳ metadata liên kết dữ liệu cập nhật nào trong các thuộc tính đã tính toán.
	 *
	 * @return array Các thuộc tính block đã tính toán cho các liên kết dữ liệu block được cung cấp.
	 */
	private function process_block_bindings() {
		$parsed_block               = $this->parsed_block;
		$computed_attributes        = array();
		$supported_block_attributes = array(
			'core/paragraph' => array( 'content' ),
			'core/heading'   => array( 'content' ),
			'core/image'     => array( 'id', 'url', 'title', 'alt' ),
			'core/button'    => array( 'url', 'text', 'linkTarget', 'rel' ),
		);

		// Nếu block không có thuộc tính bindings, không thuộc loại block được hỗ trợ,
		// hoặc thuộc tính bindings không phải là mảng, trả về nội dung block.
		if (
			! isset( $supported_block_attributes[ $this->name ] ) ||
			empty( $parsed_block['attrs']['metadata']['bindings'] ) ||
			! is_array( $parsed_block['attrs']['metadata']['bindings'] )
		) {
			return $computed_attributes;
		}

		$bindings = $parsed_block['attrs']['metadata']['bindings'];

		/*
		 * Nếu liên kết mặc định được thiết lập cho ghi đè pattern, thay thế nó
		 * bằng liên kết ghi đè pattern cho tất cả thuộc tính được hỗ trợ.
		 */
		if (
			isset( $bindings['__default']['source'] ) &&
			'core/pattern-overrides' === $bindings['__default']['source']
		) {
			$updated_bindings = array();

			/*
			 * Xây dựng mảng liên kết của tất cả thuộc tính được hỗ trợ.
			 * Lưu ý rằng điều này cũng loại bỏ thuộc tính `__default` khỏi
			 * mảng kết quả.
			 */
			foreach ( $supported_block_attributes[ $parsed_block['blockName'] ] as $attribute_name ) {
				// Giữ lại bất kỳ liên kết không phải ghi đè pattern nào có thể có mặt.
				$updated_bindings[ $attribute_name ] = isset( $bindings[ $attribute_name ] )
					? $bindings[ $attribute_name ]
					: array( 'source' => 'core/pattern-overrides' );
			}
			$bindings = $updated_bindings;
			/*
			 * Cập nhật metadata liên kết dữ liệu của các thuộc tính đã tính toán.
			 * Điều này đảm bảo block nhận được metadata liên kết __default đã mở rộng khi nó render.
			 */
			$computed_attributes['metadata'] = array_merge(
				$parsed_block['attrs']['metadata'],
				array( 'bindings' => $bindings )
			);
		}

		foreach ( $bindings as $attribute_name => $block_binding ) {
			// Nếu thuộc tính không nằm trong danh sách hỗ trợ, xử lý thuộc tính tiếp theo.
			if ( ! in_array( $attribute_name, $supported_block_attributes[ $this->name ], true ) ) {
				continue;
			}
			// Nếu không có nguồn được cung cấp, hoặc nguồn đó không được đăng ký, xử lý thuộc tính tiếp theo.
			if ( ! isset( $block_binding['source'] ) || ! is_string( $block_binding['source'] ) ) {
				continue;
			}

			$block_binding_source = get_block_bindings_source( $block_binding['source'] );
			if ( null === $block_binding_source ) {
				continue;
			}

			// Thêm ngữ cảnh cần thiết được định nghĩa bởi nguồn.
			if ( ! empty( $block_binding_source->uses_context ) ) {
				foreach ( $block_binding_source->uses_context as $context_name ) {
					if ( array_key_exists( $context_name, $this->available_context ) ) {
						$this->context[ $context_name ] = $this->available_context[ $context_name ];
					}
				}
			}

			$source_args  = ! empty( $block_binding['args'] ) && is_array( $block_binding['args'] ) ? $block_binding['args'] : array();
			$source_value = $block_binding_source->get_value( $source_args, $this, $attribute_name );

			// Nếu giá trị không null, xử lý HTML dựa trên block và thuộc tính.
			if ( ! is_null( $source_value ) ) {
				$computed_attributes[ $attribute_name ] = $source_value;
			}
		}

		return $computed_attributes;
	}

	/**
	 * Tùy thuộc vào tên thuộc tính block, thay thế giá trị của nó trong HTML dựa trên giá trị được cung cấp.
	 *
	 * @since 6.5.0
	 *
	 * @param string $block_content  Nội dung block.
	 * @param string $attribute_name Tên thuộc tính cần thay thế.
	 * @param mixed  $source_value   Giá trị được sử dụng để thay thế trong HTML.
	 * @return string Nội dung block đã được sửa đổi.
	 */
	private function replace_html( string $block_content, string $attribute_name, $source_value ) {
		$block_type = $this->block_type;
		if ( ! isset( $block_type->attributes[ $attribute_name ]['source'] ) ) {
			return $block_content;
		}

		// Tùy thuộc vào nguồn thuộc tính, quá trình xử lý sẽ khác nhau.
		switch ( $block_type->attributes[ $attribute_name ]['source'] ) {
			case 'html':
			case 'rich-text':
				$block_reader = new WP_HTML_Tag_Processor( $block_content );

				// TODO: Hỗ trợ bộ chọn CSS khi chúng sẵn sàng trong HTML API.
				// Trong khi chờ đợi, hỗ trợ bộ chọn phân cách bằng dấu phẩy bằng cách tách chúng thành mảng.
				$selectors = explode( ',', $block_type->attributes[ $attribute_name ]['selector'] );
				// Thêm bookmark vào thẻ đầu tiên để có thể lặp qua các bộ chọn.
				$block_reader->next_tag();
				$block_reader->set_bookmark( 'iterate-selectors' );

				// TODO: Điều này sẽ không cần thiết khi hàm `set_inner_html` sẵn sàng.
				// Lưu thẻ cha và thuộc tính của nó để có thể khôi phục sau trong button.
				// Block button có wrapper trong khi block paragraph và heading thì không.
				if ( 'core/button' === $this->name ) {
					$button_wrapper                 = $block_reader->get_tag();
					$button_wrapper_attribute_names = $block_reader->get_attribute_names_with_prefix( '' );
					$button_wrapper_attrs           = array();
					foreach ( $button_wrapper_attribute_names as $name ) {
						$button_wrapper_attrs[ $name ] = $block_reader->get_attribute( $name );
					}
				}

				foreach ( $selectors as $selector ) {
					// Nếu thẻ cha, hoặc bất kỳ phần tử con nào, khớp với bộ chọn, thay thế HTML.
					if ( strcasecmp( $block_reader->get_tag(), $selector ) === 0 || $block_reader->next_tag(
						array(
							'tag_name' => $selector,
						)
					) ) {
						$block_reader->release_bookmark( 'iterate-selectors' );

						// TODO: Sử dụng phương thức `set_inner_html` khi nó sẵn sàng trong HTML API.
						// Cho đến khi đó, nó được mã hóa cứng cho các block paragraph, heading và button.
						// Lưu thẻ và thuộc tính của nó để có thể khôi phục sau.
						$selector_attribute_names = $block_reader->get_attribute_names_with_prefix( '' );
						$selector_attrs           = array();
						foreach ( $selector_attribute_names as $name ) {
							$selector_attrs[ $name ] = $block_reader->get_attribute( $name );
						}
						$selector_markup = "<$selector>" . wp_kses_post( $source_value ) . "</$selector>";
						$amended_content = new WP_HTML_Tag_Processor( $selector_markup );
						$amended_content->next_tag();
						foreach ( $selector_attrs as $attribute_key => $attribute_value ) {
							$amended_content->set_attribute( $attribute_key, $attribute_value );
						}
						if ( 'core/paragraph' === $this->name || 'core/heading' === $this->name ) {
							return $amended_content->get_updated_html();
						}
						if ( 'core/button' === $this->name ) {
							$button_markup  = "<$button_wrapper>{$amended_content->get_updated_html()}</$button_wrapper>";
							$amended_button = new WP_HTML_Tag_Processor( $button_markup );
							$amended_button->next_tag();
							foreach ( $button_wrapper_attrs as $attribute_key => $attribute_value ) {
								$amended_button->set_attribute( $attribute_key, $attribute_value );
							}
							return $amended_button->get_updated_html();
						}
					} else {
						$block_reader->seek( 'iterate-selectors' );
					}
				}
				$block_reader->release_bookmark( 'iterate-selectors' );
				return $block_content;

			case 'attribute':
				$amended_content = new WP_HTML_Tag_Processor( $block_content );
				if ( ! $amended_content->next_tag(
					array(
						// TODO: xây dựng truy vấn từ bộ chọn CSS.
						'tag_name' => $block_type->attributes[ $attribute_name ]['selector'],
					)
				) ) {
					return $block_content;
				}
				$amended_content->set_attribute( $block_type->attributes[ $attribute_name ]['attribute'], $source_value );
				return $amended_content->get_updated_html();

			default:
				return $block_content;
		}
	}


	/**
	 * Tạo đầu ra render cho block.
	 *
	 * @since 5.5.0
	 * @since 6.5.0 Thêm xử lý liên kết dữ liệu block.
	 *
	 * @global WP_Post $post Đối tượng bài viết toàn cục.
	 *
	 * @param array $options {
	 *     Đối tượng tùy chọn tùy chọn.
	 *
	 *     @type bool $dynamic Mặc định 'true'. Tùy chọn đặt thành false để tránh sử dụng render_callback của block.
	 * }
	 * @return string Đầu ra render của block.
	 */
	public function render( $options = array() ) {
		global $post;

		/*
		 * Tại một thời điểm chỉ có thể có một block tương tác gốc vì HTML render của block đó
		 * chứa HTML render của tất cả các block con, bao gồm mọi block tương tác.
		 */
		static $root_interactive_block = null;
		/**
		 * Lọc xem Interactivity API có nên xử lý các chỉ thị hay không.
		 *
		 * @since 6.6.0
		 *
		 * @param bool $enabled Xử lý chỉ thị có được bật hay không.
		 */
		$interactivity_process_directives_enabled = apply_filters( 'interactivity_process_directives', true );
		if (
			$interactivity_process_directives_enabled && null === $root_interactive_block && (
				( isset( $this->block_type->supports['interactivity'] ) && true === $this->block_type->supports['interactivity'] ) ||
				! empty( $this->block_type->supports['interactivity']['interactive'] )
			)
		) {
			$root_interactive_block = $this;
		}

		$options = wp_parse_args(
			$options,
			array(
				'dynamic' => true,
			)
		);

		// Xử lý các liên kết dữ liệu block và lấy thuộc tính đã cập nhật với giá trị từ các nguồn.
		$computed_attributes = $this->process_block_bindings();
		if ( ! empty( $computed_attributes ) ) {
			// Hợp nhất các thuộc tính đã tính toán với các thuộc tính gốc.
			$this->attributes = array_merge( $this->attributes, $computed_attributes );
		}

		$is_dynamic    = $options['dynamic'] && $this->name && null !== $this->block_type && $this->block_type->is_dynamic();
		$block_content = '';

		if ( ! $options['dynamic'] || empty( $this->block_type->skip_inner_blocks ) ) {
			$index = 0;

			foreach ( $this->inner_content as $chunk ) {
				if ( is_string( $chunk ) ) {
					$block_content .= $chunk;
				} else {
					$inner_block  = $this->inner_blocks[ $index ];
					$parent_block = $this;

					/** Bộ lọc này được ghi nhận trong wp-includes/blocks.php */
					$pre_render = apply_filters( 'pre_render_block', null, $inner_block->parsed_block, $parent_block );

					if ( ! is_null( $pre_render ) ) {
						$block_content .= $pre_render;
					} else {
						$source_block        = $inner_block->parsed_block;
						$inner_block_context = $inner_block->context;

						/** Bộ lọc này được ghi nhận trong wp-includes/blocks.php */
						$inner_block->parsed_block = apply_filters( 'render_block_data', $inner_block->parsed_block, $source_block, $parent_block );

						/** Bộ lọc này được ghi nhận trong wp-includes/blocks.php */
						$inner_block->context = apply_filters( 'render_block_context', $inner_block->context, $inner_block->parsed_block, $parent_block );

						/*
						 * Phương thức `refresh_context_dependents()` đã gọi `refresh_parsed_block_dependents()`.
						 * Do đó điều kiện thứ hai không liên quan nếu điều kiện đầu tiên được thỏa mãn.
						 */
						if ( $inner_block->context !== $inner_block_context ) {
							$inner_block->refresh_context_dependents();
						} elseif ( $inner_block->parsed_block !== $source_block ) {
							$inner_block->refresh_parsed_block_dependents();
						}

						$block_content .= $inner_block->render();
					}

					++$index;
				}
			}
		}

		if ( ! empty( $computed_attributes ) && ! empty( $block_content ) ) {
			foreach ( $computed_attributes as $attribute_name => $source_value ) {
				$block_content = $this->replace_html( $block_content, $attribute_name, $source_value );
			}
		}

		if ( $is_dynamic ) {
			$global_post = $post;
			$parent      = WP_Block_Supports::$block_to_render;

			WP_Block_Supports::$block_to_render = $this->parsed_block;

			$block_content = (string) call_user_func( $this->block_type->render_callback, $this->attributes, $block_content, $this );

			WP_Block_Supports::$block_to_render = $parent;

			$post = $global_post;
		}

		if ( ( ! empty( $this->block_type->script_handles ) ) ) {
			foreach ( $this->block_type->script_handles as $script_handle ) {
				wp_enqueue_script( $script_handle );
			}
		}

		if ( ! empty( $this->block_type->view_script_handles ) ) {
			foreach ( $this->block_type->view_script_handles as $view_script_handle ) {
				wp_enqueue_script( $view_script_handle );
			}
		}

		if ( ! empty( $this->block_type->view_script_module_ids ) ) {
			foreach ( $this->block_type->view_script_module_ids as $view_script_module_id ) {
				wp_enqueue_script_module( $view_script_module_id );
			}
		}

		/*
		 * Đối với các block Core, các style này chỉ được enqueue nếu `wp_should_load_separate_core_block_assets()`
		 * trả về true. Nếu không, các lệnh gọi `wp_enqueue_style()` này sẽ không có hiệu lực, vì các block Core
		 * dựa vào stylesheet kết hợp 'wp-block-library', được enqueue vô điều kiện.
		 */
		if ( ( ! empty( $this->block_type->style_handles ) ) ) {
			foreach ( $this->block_type->style_handles as $style_handle ) {
				wp_enqueue_style( $style_handle );
			}
		}

		if ( ( ! empty( $this->block_type->view_style_handles ) ) ) {
			foreach ( $this->block_type->view_style_handles as $view_style_handle ) {
				wp_enqueue_style( $view_style_handle );
			}
		}

		/**
		 * Lọc nội dung của một block đơn lẻ.
		 *
		 * @since 5.0.0
		 * @since 5.9.0 Thêm tham số `$instance`.
		 *
		 * @param string   $block_content Nội dung block.
		 * @param array    $block         Block đầy đủ, bao gồm tên và thuộc tính.
		 * @param WP_Block $instance      Thể hiện block.
		 */
		$block_content = apply_filters( 'render_block', $block_content, $this->parsed_block, $this );

		/**
		 * Lọc nội dung của một block đơn lẻ.
		 *
		 * Phần động của tên hook, `$name`, tham chiếu đến
		 * tên block, ví dụ "core/paragraph".
		 *
		 * @since 5.7.0
		 * @since 5.9.0 Thêm tham số `$instance`.
		 *
		 * @param string   $block_content Nội dung block.
		 * @param array    $block         Block đầy đủ, bao gồm tên và thuộc tính.
		 * @param WP_Block $instance      Thể hiện block.
		 */
		$block_content = apply_filters( "render_block_{$this->name}", $block_content, $this->parsed_block, $this );

		if ( $root_interactive_block === $this ) {
			// Block tương tác gốc đã render xong. Đến lúc xử lý các chỉ thị.
			$block_content          = wp_interactivity_process_directives( $block_content );
			$root_interactive_block = null;
		}

		return $block_content;
	}
}
