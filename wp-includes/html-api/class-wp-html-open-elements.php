<?php
/**
 * HTML API: Lớp WP_HTML_Open_Elements
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since 6.4.0
 */

/**
 * Lớp lõi được sử dụng bởi bộ xử lý HTML trong quá trình phân tích cú pháp HTML
 * để quản lý ngăn xếp các phần tử đang mở.
 *
 * Lớp này được thiết kế để sử dụng nội bộ bởi bộ xử lý HTML.
 *
 * > Ban đầu, ngăn xếp các phần tử đang mở là rỗng. Ngăn xếp phát triển
 * > xuống dưới; nút trên cùng của ngăn xếp là nút được thêm đầu tiên
 * > vào ngăn xếp, và nút dưới cùng của ngăn xếp là nút được thêm gần
 * > đây nhất trong ngăn xếp (không tính đến khi ngăn xếp được thao tác
 * > theo kiểu truy cập ngẫu nhiên như một phần của việc xử lý các thẻ
 * > lồng sai).
 *
 * @since 6.4.0
 *
 * @access private
 *
 * @see https://html.spec.whatwg.org/#stack-of-open-elements
 * @see WP_HTML_Processor
 */
class WP_HTML_Open_Elements {
	/**
	 * Lưu trữ ngăn xếp các tham chiếu phần tử đang mở.
	 *
	 * @since 6.4.0
	 *
	 * @var WP_HTML_Token[]
	 */
	public $stack = array();

	/**
	 * Phần tử P có đang nằm trong phạm vi button hiện tại hay không.
	 *
	 * Lớp này tối ưu hóa việc tra cứu phạm vi bằng cách tính trước
	 * giá trị này khi các phần tử được thêm vào và xóa khỏi ngăn xếp
	 * các phần tử đang mở mà có thể thay đổi giá trị của nó.
	 * Điều này tránh việc lặp lại thường xuyên trên ngăn xếp.
	 *
	 * @since 6.4.0
	 *
	 * @var bool
	 */
	private $has_p_in_button_scope = false;

	/**
	 * Hàm sẽ được gọi khi một phần tử bị lấy ra khỏi ngăn xếp các phần tử đang mở.
	 *
	 * Hàm sẽ được gọi với phần tử bị lấy ra làm tham số.
	 *
	 * @since 6.6.0
	 *
	 * @var Closure|null
	 */
	private $pop_handler = null;

	/**
	 * Hàm sẽ được gọi khi một phần tử được đẩy vào ngăn xếp các phần tử đang mở.
	 *
	 * Hàm sẽ được gọi với phần tử được đẩy vào làm tham số.
	 *
	 * @since 6.6.0
	 *
	 * @var Closure|null
	 */
	private $push_handler = null;

	/**
	 * Thiết lập trình xử lý pop sẽ được gọi khi một phần tử bị lấy ra khỏi ngăn xếp
	 * các phần tử đang mở.
	 *
	 * Hàm sẽ được gọi với phần tử được đẩy vào làm tham số.
	 *
	 * @since 6.6.0
	 *
	 * @param Closure $handler Hàm xử lý.
	 */
	public function set_pop_handler( Closure $handler ): void {
		$this->pop_handler = $handler;
	}

	/**
	 * Thiết lập trình xử lý push sẽ được gọi khi một phần tử được đẩy vào ngăn xếp
	 * các phần tử đang mở.
	 *
	 * Hàm sẽ được gọi với phần tử được đẩy vào làm tham số.
	 *
	 * @since 6.6.0
	 *
	 * @param Closure $handler Hàm xử lý.
	 */
	public function set_push_handler( Closure $handler ): void {
		$this->push_handler = $handler;
	}

	/**
	 * Trả về tên của nút tại vị trí thứ n trên ngăn xếp
	 * các phần tử đang mở, hoặc `null` nếu vị trí đó không tồn tại.
	 *
	 * Lưu ý rằng chỉ số này bắt đầu từ 1, đại diện cho
	 * "phần tử thứ n" trên ngăn xếp, đếm từ trên xuống, trong đó
	 * phần tử trên cùng là thứ 1, phần tử thứ hai là thứ 2, v.v...
	 *
	 * @since 6.7.0
	 *
	 * @param int $nth Lấy phần tử thứ n trên ngăn xếp, với 1 là
	 *                 phần tử trên cùng, 2 là phần tử thứ hai, v.v...
	 * @return WP_HTML_Token|null Tên của nút trên ngăn xếp tại vị trí đã cho,
	 *                            hoặc `null` nếu vị trí đó không nằm trên ngăn xếp.
	 */
	public function at( int $nth ): ?WP_HTML_Token {
		foreach ( $this->walk_down() as $item ) {
			if ( 0 === --$nth ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Báo cáo nếu một nút có tên cho trước nằm trong ngăn xếp các phần tử đang mở.
	 *
	 * @since 6.7.0
	 *
	 * @param string $node_name Tên nút cần kiểm tra.
	 * @return bool Nút có tên đã cho có nằm trong ngăn xếp các phần tử đang mở hay không.
	 */
	public function contains( string $node_name ): bool {
		foreach ( $this->walk_up() as $item ) {
			if ( $node_name === $item->node_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Báo cáo nếu một nút cụ thể nằm trong ngăn xếp các phần tử đang mở.
	 *
	 * @since 6.4.0
	 *
	 * @param WP_HTML_Token $token Tìm nút này trong ngăn xếp.
	 * @return bool Nút được tham chiếu có nằm trong ngăn xếp các phần tử đang mở hay không.
	 */
	public function contains_node( WP_HTML_Token $token ): bool {
		foreach ( $this->walk_up() as $item ) {
			if ( $token === $item ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Trả về số lượng nút hiện có trong ngăn xếp các phần tử đang mở.
	 *
	 * @since 6.4.0
	 *
	 * @return int Số lượng nút trong ngăn xếp các phần tử đang mở.
	 */
	public function count(): int {
		return count( $this->stack );
	}

	/**
	 * Trả về nút ở cuối ngăn xếp các phần tử đang mở,
	 * nếu có. Nếu ngăn xếp rỗng, trả về null.
	 *
	 * @since 6.4.0
	 *
	 * @return WP_HTML_Token|null Nút cuối cùng trong ngăn xếp các phần tử đang mở, nếu có, ngược lại là null.
	 */
	public function current_node(): ?WP_HTML_Token {
		$current_node = end( $this->stack );

		return $current_node ? $current_node : null;
	}

	/**
	 * Cho biết nút hiện tại có thuộc loại hoặc tên đã cho hay không.
	 *
	 * Có thể truyền vào loại nút hoặc tên nút cho hàm này.
	 * Trong trường hợp không có phần tử hiện tại, hàm sẽ luôn trả về `false`.
	 *
	 * Ví dụ:
	 *
	 *     // Nút hiện tại có phải là nút văn bản không?
	 *     $stack->current_node_is( '#text' );
	 *
	 *     // Nút hiện tại có phải là phần tử DIV không?
	 *     $stack->current_node_is( 'DIV' );
	 *
	 *     // Nút hiện tại có phải là bất kỳ phần tử/thẻ nào không?
	 *     $stack->current_node_is( '#tag' );
	 *
	 * @see WP_HTML_Tag_Processor::get_token_type
	 * @see WP_HTML_Tag_Processor::get_token_name
	 *
	 * @since 6.7.0
	 *
	 * @access private
	 *
	 * @param string $identity Kiểm tra xem nút hiện tại có tên hoặc loại này không (tùy thuộc vào giá trị được cung cấp).
	 * @return bool Có phần tử hiện tại nào khớp với danh tính đã cho hay không, dù là tên token hay loại token.
	 */
	public function current_node_is( string $identity ): bool {
		$current_node = end( $this->stack );
		if ( false === $current_node ) {
			return false;
		}

		$current_node_name = $current_node->node_name;

		return (
			$current_node_name === $identity ||
			( '#doctype' === $identity && 'html' === $current_node_name ) ||
			( '#tag' === $identity && ctype_upper( $current_node_name ) )
		);
	}

	/**
	 * Trả về phần tử có nằm trong một phạm vi cụ thể hay không.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-the-specific-scope
	 *
	 * @param string   $tag_name         Tên thẻ cần kiểm tra.
	 * @param string[] $termination_list Danh sách các phần tử kết thúc tìm kiếm.
	 * @return bool Phần tử có được tìm thấy trong phạm vi cụ thể hay không.
	 */
	public function has_element_in_specific_scope( string $tag_name, $termination_list ): bool {
		foreach ( $this->walk_up() as $node ) {
			$namespaced_name = 'html' === $node->namespace
				? $node->node_name
				: "{$node->namespace} {$node->node_name}";

			if ( $namespaced_name === $tag_name ) {
				return true;
			}

			if (
				'(internal: H1 through H6 - do not use)' === $tag_name &&
				in_array( $namespaced_name, array( 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ), true )
			) {
				return true;
			}

			if ( in_array( $namespaced_name, $termination_list, true ) ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Trả về một phần tử cụ thể có nằm trong phạm vi hay không.
	 *
	 * > Ngăn xếp các phần tử đang mở được cho là có một phần tử cụ thể trong
	 * > phạm vi khi nó có phần tử đó trong phạm vi cụ thể bao gồm
	 * > các loại phần tử sau:
	 * >
	 * >   - applet
	 * >   - caption
	 * >   - html
	 * >   - table
	 * >   - td
	 * >   - th
	 * >   - marquee
	 * >   - object
	 * >   - template
	 * >   - MathML mi
	 * >   - MathML mo
	 * >   - MathML mn
	 * >   - MathML ms
	 * >   - MathML mtext
	 * >   - MathML annotation-xml
	 * >   - SVG foreignObject
	 * >   - SVG desc
	 * >   - SVG title
	 *
	 * @since 6.4.0
	 * @since 6.7.0 Hỗ trợ đầy đủ.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-scope
	 *
	 * @param string $tag_name Tên thẻ cần kiểm tra.
	 * @return bool Phần tử đã cho có nằm trong phạm vi hay không.
	 */
	public function has_element_in_scope( string $tag_name ): bool {
		return $this->has_element_in_specific_scope(
			$tag_name,
			array(
				'APPLET',
				'CAPTION',
				'HTML',
				'TABLE',
				'TD',
				'TH',
				'MARQUEE',
				'OBJECT',
				'TEMPLATE',

				'math MI',
				'math MO',
				'math MN',
				'math MS',
				'math MTEXT',
				'math ANNOTATION-XML',

				'svg FOREIGNOBJECT',
				'svg DESC',
				'svg TITLE',
			)
		);
	}

	/**
	 * Trả về một phần tử cụ thể có nằm trong phạm vi mục danh sách hay không.
	 *
	 * > Ngăn xếp các phần tử đang mở được cho là có một phần tử cụ thể
	 * > trong phạm vi mục danh sách khi nó có phần tử đó trong phạm vi cụ thể
	 * > bao gồm các loại phần tử sau:
	 * >
	 * >   - Tất cả các loại phần tử được liệt kê ở trên cho thuật toán có phần tử trong phạm vi.
	 * >   - ol trong không gian tên HTML
	 * >   - ul trong không gian tên HTML
	 *
	 * @since 6.4.0
	 * @since 6.5.0 Đã triển khai: không còn ném ngoại lệ mỗi lần gọi.
	 * @since 6.7.0 Hỗ trợ tất cả các phần tử HTML cần thiết.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-list-item-scope
	 *
	 * @param string $tag_name Tên thẻ cần kiểm tra.
	 * @return bool Phần tử đã cho có nằm trong phạm vi hay không.
	 */
	public function has_element_in_list_item_scope( string $tag_name ): bool {
		return $this->has_element_in_specific_scope(
			$tag_name,
			array(
				'APPLET',
				'BUTTON',
				'CAPTION',
				'HTML',
				'TABLE',
				'TD',
				'TH',
				'MARQUEE',
				'OBJECT',
				'OL',
				'TEMPLATE',
				'UL',

				'math MI',
				'math MO',
				'math MN',
				'math MS',
				'math MTEXT',
				'math ANNOTATION-XML',

				'svg FOREIGNOBJECT',
				'svg DESC',
				'svg TITLE',
			)
		);
	}

	/**
	 * Trả về một phần tử cụ thể có nằm trong phạm vi button hay không.
	 *
	 * > Ngăn xếp các phần tử đang mở được cho là có một phần tử cụ thể
	 * > trong phạm vi button khi nó có phần tử đó trong phạm vi cụ thể
	 * > bao gồm các loại phần tử sau:
	 * >
	 * >   - Tất cả các loại phần tử được liệt kê ở trên cho thuật toán có phần tử trong phạm vi.
	 * >   - button trong không gian tên HTML
	 *
	 * @since 6.4.0
	 * @since 6.7.0 Hỗ trợ tất cả các phần tử HTML cần thiết.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-button-scope
	 *
	 * @param string $tag_name Tên thẻ cần kiểm tra.
	 * @return bool Phần tử đã cho có nằm trong phạm vi hay không.
	 */
	public function has_element_in_button_scope( string $tag_name ): bool {
		return $this->has_element_in_specific_scope(
			$tag_name,
			array(
				'APPLET',
				'BUTTON',
				'CAPTION',
				'HTML',
				'TABLE',
				'TD',
				'TH',
				'MARQUEE',
				'OBJECT',
				'TEMPLATE',

				'math MI',
				'math MO',
				'math MN',
				'math MS',
				'math MTEXT',
				'math ANNOTATION-XML',

				'svg FOREIGNOBJECT',
				'svg DESC',
				'svg TITLE',
			)
		);
	}

	/**
	 * Trả về một phần tử cụ thể có nằm trong phạm vi table hay không.
	 *
	 * > Ngăn xếp các phần tử đang mở được cho là có một phần tử cụ thể
	 * > trong phạm vi table khi nó có phần tử đó trong phạm vi cụ thể
	 * > bao gồm các loại phần tử sau:
	 * >
	 * >   - html trong không gian tên HTML
	 * >   - table trong không gian tên HTML
	 * >   - template trong không gian tên HTML
	 *
	 * @since 6.4.0
	 * @since 6.7.0 Triển khai đầy đủ.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-table-scope
	 *
	 * @param string $tag_name Tên thẻ cần kiểm tra.
	 * @return bool Phần tử đã cho có nằm trong phạm vi hay không.
	 */
	public function has_element_in_table_scope( string $tag_name ): bool {
		return $this->has_element_in_specific_scope(
			$tag_name,
			array(
				'HTML',
				'TABLE',
				'TEMPLATE',
			)
		);
	}

	/**
	 * Trả về một phần tử cụ thể có nằm trong phạm vi select hay không.
	 *
	 * Kiểm tra này khác với các kiểm tra tương tự khác, ở chỗ các quy tắc của nó bị đảo ngược.
	 * Thay vì đạt được kết quả khớp khi gặp một thẻ bất kỳ trong nhóm kết thúc,
	 * kiểm tra này kết thúc nếu gặp bất kỳ thẻ nào khác.
	 *
	 * > Ngăn xếp các phần tử đang mở được cho là có một phần tử cụ thể trong phạm vi select khi nó có
	 * > phần tử đó trong phạm vi cụ thể bao gồm tất cả các loại phần tử ngoại trừ:
	 * >   - optgroup trong không gian tên HTML
	 * >   - option trong không gian tên HTML
	 *
	 * @since 6.4.0 Triển khai sơ bộ (ném ngoại lệ).
	 * @since 6.7.0 Triển khai đầy đủ.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-select-scope
	 *
	 * @param string $tag_name Tên thẻ cần kiểm tra.
	 * @return bool Phần tử đã cho có nằm trong phạm vi SELECT hay không.
	 */
	public function has_element_in_select_scope( string $tag_name ): bool {
		foreach ( $this->walk_up() as $node ) {
			if ( $node->node_name === $tag_name ) {
				return true;
			}

			if (
				'OPTION' !== $node->node_name &&
				'OPTGROUP' !== $node->node_name
			) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Trả về P có nằm trong phạm vi BUTTON hay không.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-button-scope
	 *
	 * @return bool P có nằm trong phạm vi BUTTON hay không.
	 */
	public function has_p_in_button_scope(): bool {
		return $this->has_p_in_button_scope;
	}

	/**
	 * Lấy một nút ra khỏi ngăn xếp các phần tử đang mở.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#stack-of-open-elements
	 *
	 * @return bool Có nút nào được lấy ra khỏi ngăn xếp hay không.
	 */
	public function pop(): bool {
		$item = array_pop( $this->stack );
		if ( null === $item ) {
			return false;
		}

		$this->after_element_pop( $item );
		return true;
	}

	/**
	 * Lấy các nút ra khỏi ngăn xếp các phần tử đang mở cho đến khi một thẻ HTML có tên đã cho được lấy ra.
	 *
	 * @since 6.4.0
	 *
	 * @see WP_HTML_Open_Elements::pop
	 *
	 * @param string $html_tag_name Tên thẻ cần được lấy ra khỏi ngăn xếp các phần tử đang mở.
	 * @return bool Thẻ có tên đã cho có được tìm thấy và lấy ra khỏi ngăn xếp các phần tử đang mở hay không.
	 */
	public function pop_until( string $html_tag_name ): bool {
		foreach ( $this->walk_up() as $item ) {
			$this->pop();

			if ( 'html' !== $item->namespace ) {
				continue;
			}

			if (
				'(internal: H1 through H6 - do not use)' === $html_tag_name &&
				in_array( $item->node_name, array( 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ), true )
			) {
				return true;
			}

			if ( $html_tag_name === $item->node_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Đẩy một nút vào ngăn xếp các phần tử đang mở.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#stack-of-open-elements
	 *
	 * @param WP_HTML_Token $stack_item Phần tử cần thêm vào ngăn xếp.
	 */
	public function push( WP_HTML_Token $stack_item ): void {
		$this->stack[] = $stack_item;
		$this->after_element_push( $stack_item );
	}

	/**
	 * Xóa một nút cụ thể khỏi ngăn xếp các phần tử đang mở.
	 *
	 * @since 6.4.0
	 *
	 * @param WP_HTML_Token $token Nút cần xóa khỏi ngăn xếp các phần tử đang mở.
	 * @return bool Nút có được tìm thấy và xóa khỏi ngăn xếp các phần tử đang mở hay không.
	 */
	public function remove_node( WP_HTML_Token $token ): bool {
		foreach ( $this->walk_up() as $position_from_end => $item ) {
			if ( $token->bookmark_name !== $item->bookmark_name ) {
				continue;
			}

			$position_from_start = $this->count() - $position_from_end - 1;
			array_splice( $this->stack, $position_from_start, 1 );
			$this->after_element_pop( $item );
			return true;
		}

		return false;
	}


	/**
	 * Duyệt qua ngăn xếp các phần tử đang mở, bắt đầu từ phần tử trên cùng
	 * (được thêm đầu tiên) và đi xuống phần tử được thêm cuối cùng.
	 *
	 * Hàm generator này được thiết kế để sử dụng trong vòng lặp "foreach".
	 *
	 * Ví dụ:
	 *
	 *     $html = '<em><strong><a>We are here';
	 *     foreach ( $stack->walk_down() as $node ) {
	 *         echo "{$node->node_name} -> ";
	 *     }
	 *     > EM -> STRONG -> A ->
	 *
	 * Để bắt đầu từ phần tử được thêm gần nhất và đi lên trên cùng,
	 * xem WP_HTML_Open_Elements::walk_up().
	 *
	 * @since 6.4.0
	 */
	public function walk_down() {
		$count = count( $this->stack );

		for ( $i = 0; $i < $count; $i++ ) {
			yield $this->stack[ $i ];
		}
	}

	/**
	 * Duyệt qua ngăn xếp các phần tử đang mở, bắt đầu từ phần tử dưới cùng
	 * (được thêm cuối cùng) và đi lên phần tử được thêm đầu tiên.
	 *
	 * Hàm generator này được thiết kế để sử dụng trong vòng lặp "foreach".
	 *
	 * Ví dụ:
	 *
	 *     $html = '<em><strong><a>We are here';
	 *     foreach ( $stack->walk_up() as $node ) {
	 *         echo "{$node->node_name} -> ";
	 *     }
	 *     > A -> STRONG -> EM ->
	 *
	 * Để bắt đầu từ phần tử được thêm đầu tiên và đi xuống dưới cùng,
	 * xem WP_HTML_Open_Elements::walk_down().
	 *
	 * @since 6.4.0
	 * @since 6.5.0 Chấp nhận $above_this_node để bắt đầu duyệt phía trên nút đã cho, nếu nó tồn tại.
	 *
	 * @param WP_HTML_Token|null $above_this_node Tùy chọn. Bắt đầu duyệt phía trên nút này,
	 *                                            nếu được cung cấp và nếu nút tồn tại.
	 */
	public function walk_up( ?WP_HTML_Token $above_this_node = null ) {
		$has_found_node = null === $above_this_node;

		for ( $i = count( $this->stack ) - 1; $i >= 0; $i-- ) {
			$node = $this->stack[ $i ];

			if ( ! $has_found_node ) {
				$has_found_node = $node === $above_this_node;
				continue;
			}

			yield $node;
		}
	}

	/*
	 * Các hàm hỗ trợ nội bộ.
	 */

	/**
	 * Cập nhật các cờ nội bộ sau khi thêm một phần tử.
	 *
	 * Một số điều kiện nhất định (chẳng hạn như "has_p_in_button_scope") được duy trì ở đây dưới
	 * dạng cờ chỉ được thay đổi khi thêm và xóa phần tử. Điều này cho phép
	 * bộ xử lý HTML kiểm tra nhanh các điều kiện này thay vì phải lặp lại
	 * trên ngăn xếp các phần tử đang mở mỗi khi gặp thẻ mới. Tuy nhiên, các cờ
	 * này cần được duy trì khi các mục được thêm vào và xóa khỏi ngăn xếp.
	 *
	 * @since 6.4.0
	 *
	 * @param WP_HTML_Token $item Phần tử đã được thêm vào ngăn xếp các phần tử đang mở.
	 */
	public function after_element_push( WP_HTML_Token $item ): void {
		$namespaced_name = 'html' === $item->namespace
			? $item->node_name
			: "{$item->namespace} {$item->node_name}";

		/*
		 * Khi thêm hỗ trợ cho các phần tử mới, mở rộng switch này để bắt
		 * các trường hợp mà giá trị đã tính trước cần thay đổi.
		 */
		switch ( $namespaced_name ) {
			case 'APPLET':
			case 'BUTTON':
			case 'CAPTION':
			case 'HTML':
			case 'TABLE':
			case 'TD':
			case 'TH':
			case 'MARQUEE':
			case 'OBJECT':
			case 'TEMPLATE':
			case 'math MI':
			case 'math MO':
			case 'math MN':
			case 'math MS':
			case 'math MTEXT':
			case 'math ANNOTATION-XML':
			case 'svg FOREIGNOBJECT':
			case 'svg DESC':
			case 'svg TITLE':
				$this->has_p_in_button_scope = false;
				break;

			case 'P':
				$this->has_p_in_button_scope = true;
				break;
		}

		if ( null !== $this->push_handler ) {
			( $this->push_handler )( $item );
		}
	}

	/**
	 * Cập nhật các cờ nội bộ sau khi xóa một phần tử.
	 *
	 * Một số điều kiện nhất định (chẳng hạn như "has_p_in_button_scope") được duy trì ở đây dưới
	 * dạng cờ chỉ được thay đổi khi thêm và xóa phần tử. Điều này cho phép
	 * bộ xử lý HTML kiểm tra nhanh các điều kiện này thay vì phải lặp lại
	 * trên ngăn xếp các phần tử đang mở mỗi khi gặp thẻ mới. Tuy nhiên, các cờ
	 * này cần được duy trì khi các mục được thêm vào và xóa khỏi ngăn xếp.
	 *
	 * @since 6.4.0
	 *
	 * @param WP_HTML_Token $item Phần tử đã bị xóa khỏi ngăn xếp các phần tử đang mở.
	 */
	public function after_element_pop( WP_HTML_Token $item ): void {
		/*
		 * Khi thêm hỗ trợ cho các phần tử mới, mở rộng switch này để bắt
		 * các trường hợp mà giá trị đã tính trước cần thay đổi.
		 */
		switch ( $item->node_name ) {
			case 'APPLET':
			case 'BUTTON':
			case 'CAPTION':
			case 'HTML':
			case 'P':
			case 'TABLE':
			case 'TD':
			case 'TH':
			case 'MARQUEE':
			case 'OBJECT':
			case 'TEMPLATE':
			case 'math MI':
			case 'math MO':
			case 'math MN':
			case 'math MS':
			case 'math MTEXT':
			case 'math ANNOTATION-XML':
			case 'svg FOREIGNOBJECT':
			case 'svg DESC':
			case 'svg TITLE':
				$this->has_p_in_button_scope = $this->has_element_in_button_scope( 'P' );
				break;
		}

		if ( null !== $this->pop_handler ) {
			( $this->pop_handler )( $item );
		}
	}

	/**
	 * Xóa ngăn xếp trở lại ngữ cảnh table.
	 *
	 * > Khi các bước ở trên yêu cầu UA xóa ngăn xếp trở lại ngữ cảnh table, điều đó có nghĩa
	 * > là UA phải, trong khi nút hiện tại không phải là phần tử table, template, hoặc html, lấy
	 * > các phần tử ra khỏi ngăn xếp các phần tử đang mở.
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#clear-the-stack-back-to-a-table-context
	 *
	 * @since 6.7.0
	 */
	public function clear_to_table_context(): void {
		foreach ( $this->walk_up() as $item ) {
			if (
				'TABLE' === $item->node_name ||
				'TEMPLATE' === $item->node_name ||
				'HTML' === $item->node_name
			) {
				break;
			}
			$this->pop();
		}
	}

	/**
	 * Xóa ngăn xếp trở lại ngữ cảnh thân table.
	 *
	 * > Khi các bước ở trên yêu cầu UA xóa ngăn xếp trở lại ngữ cảnh thân table, điều đó
	 * > có nghĩa là UA phải, trong khi nút hiện tại không phải là phần tử tbody, tfoot, thead, template, hoặc
	 * > html, lấy các phần tử ra khỏi ngăn xếp các phần tử đang mở.
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#clear-the-stack-back-to-a-table-body-context
	 *
	 * @since 6.7.0
	 */
	public function clear_to_table_body_context(): void {
		foreach ( $this->walk_up() as $item ) {
			if (
				'TBODY' === $item->node_name ||
				'TFOOT' === $item->node_name ||
				'THEAD' === $item->node_name ||
				'TEMPLATE' === $item->node_name ||
				'HTML' === $item->node_name
			) {
				break;
			}
			$this->pop();
		}
	}

	/**
	 * Xóa ngăn xếp trở lại ngữ cảnh hàng table.
	 *
	 * > Khi các bước ở trên yêu cầu UA xóa ngăn xếp trở lại ngữ cảnh hàng table, điều đó
	 * > có nghĩa là UA phải, trong khi nút hiện tại không phải là phần tử tr, template, hoặc html, lấy
	 * > các phần tử ra khỏi ngăn xếp các phần tử đang mở.
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#clear-the-stack-back-to-a-table-row-context
	 *
	 * @since 6.7.0
	 */
	public function clear_to_table_row_context(): void {
		foreach ( $this->walk_up() as $item ) {
			if (
				'TR' === $item->node_name ||
				'TEMPLATE' === $item->node_name ||
				'HTML' === $item->node_name
			) {
				break;
			}
			$this->pop();
		}
	}

	/**
	 * Phương thức magic wakeup.
	 *
	 * @since 6.6.0
	 */
	public function __wakeup() {
		throw new \LogicException( __CLASS__ . ' should never be unserialized' );
	}
}
