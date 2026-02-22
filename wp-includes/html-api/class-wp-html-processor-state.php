<?php
/**
 * HTML API: Lớp WP_HTML_Processor_State
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since 6.4.0
 */

/**
 * Lớp lõi được sử dụng bởi bộ xử lý HTML trong quá trình phân tích cú pháp HTML
 * để quản lý trạng thái phân tích nội bộ.
 *
 * Lớp này được thiết kế để sử dụng nội bộ bởi bộ xử lý HTML.
 *
 * @since 6.4.0
 *
 * @access private
 *
 * @see WP_HTML_Processor
 */
class WP_HTML_Processor_State {
	/*
	 * Các hằng số chế độ chèn.
	 *
	 * Các hằng số này tồn tại và được đặt tên để giúp dễ dàng
	 * khám phá và nhận ra các chế độ chèn được hỗ trợ trong
	 * bộ phân tích.
	 *
	 * Trong tất cả các chế độ chèn có thể, chỉ những chế độ
	 * được bộ phân tích hỗ trợ mới được liệt kê ở đây. Khi thêm
	 * hỗ trợ cho nhiều chế độ hơn vào bộ phân tích, thêm chúng ở đây
	 * theo cùng mẫu đặt tên và giá trị.
	 *
	 * @see https://html.spec.whatwg.org/#the-insertion-mode
	 */

	/**
	 * Chế độ chèn ban đầu cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#the-initial-insertion-mode
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_INITIAL = 'insertion-mode-initial';

	/**
	 * Chế độ chèn trước HTML cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#the-before-html-insertion-mode
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_BEFORE_HTML = 'insertion-mode-before-html';

	/**
	 * Chế độ chèn trước head cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-beforehead
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_BEFORE_HEAD = 'insertion-mode-before-head';

	/**
	 * Chế độ chèn trong head cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inhead
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_HEAD = 'insertion-mode-in-head';

	/**
	 * Chế độ chèn trong head noscript cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inheadnoscript
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_HEAD_NOSCRIPT = 'insertion-mode-in-head-noscript';

	/**
	 * Chế độ chèn sau head cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-afterhead
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_AFTER_HEAD = 'insertion-mode-after-head';

	/**
	 * Chế độ chèn trong body cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inbody
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_BODY = 'insertion-mode-in-body';

	/**
	 * Chế độ chèn trong table cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-intable
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_TABLE = 'insertion-mode-in-table';

	/**
	 * Chế độ chèn văn bản trong table cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-intabletext
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_TABLE_TEXT = 'insertion-mode-in-table-text';

	/**
	 * Chế độ chèn trong caption cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-incaption
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_CAPTION = 'insertion-mode-in-caption';

	/**
	 * Chế độ chèn trong nhóm cột cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-incolumngroup
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_COLUMN_GROUP = 'insertion-mode-in-column-group';

	/**
	 * Chế độ chèn trong thân table cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-intablebody
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_TABLE_BODY = 'insertion-mode-in-table-body';

	/**
	 * Chế độ chèn trong hàng cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inrow
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_ROW = 'insertion-mode-in-row';

	/**
	 * Chế độ chèn trong ô cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-incell
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_CELL = 'insertion-mode-in-cell';

	/**
	 * Chế độ chèn trong select cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inselect
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_SELECT = 'insertion-mode-in-select';

	/**
	 * Chế độ chèn trong select trong table cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inselectintable
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_SELECT_IN_TABLE = 'insertion-mode-in-select-in-table';

	/**
	 * Chế độ chèn trong template cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-intemplate
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_TEMPLATE = 'insertion-mode-in-template';

	/**
	 * Chế độ chèn sau body cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-afterbody
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_AFTER_BODY = 'insertion-mode-after-body';

	/**
	 * Chế độ chèn trong frameset cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inframeset
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_IN_FRAMESET = 'insertion-mode-in-frameset';

	/**
	 * Chế độ chèn sau frameset cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-afterframeset
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_AFTER_FRAMESET = 'insertion-mode-after-frameset';

	/**
	 * Chế độ chèn sau sau body cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#the-after-after-body-insertion-mode
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_AFTER_AFTER_BODY = 'insertion-mode-after-after-body';

	/**
	 * Chế độ chèn sau sau frameset cho bộ phân tích HTML đầy đủ.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#the-after-after-frameset-insertion-mode
	 * @see WP_HTML_Processor_State::$insertion_mode
	 *
	 * @var string
	 */
	const INSERTION_MODE_AFTER_AFTER_FRAMESET = 'insertion-mode-after-after-frameset';

	/**
	 * Ngăn xếp các chế độ chèn template.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/#the-insertion-mode:stack-of-template-insertion-modes
	 *
	 * @var array<string>
	 */
	public $stack_of_template_insertion_modes = array();

	/**
	 * Theo dõi các phần tử đang mở trong quá trình quét HTML.
	 *
	 * Thuộc tính này được khởi tạo trong hàm khởi tạo và không bao giờ null.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#stack-of-open-elements
	 *
	 * @var WP_HTML_Open_Elements
	 */
	public $stack_of_open_elements;

	/**
	 * Theo dõi các phần tử định dạng đang mở, được sử dụng để xử lý các thẻ định dạng lồng sai.
	 *
	 * Thuộc tính này được khởi tạo trong hàm khởi tạo và không bao giờ null.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#list-of-active-formatting-elements
	 *
	 * @var WP_HTML_Active_Formatting_Elements
	 */
	public $active_formatting_elements;

	/**
	 * Tham chiếu đến thẻ hiện đang khớp, nếu có.
	 *
	 * @since 6.4.0
	 *
	 * @var WP_HTML_Token|null
	 */
	public $current_token = null;

	/**
	 * Chế độ chèn cho việc xây dựng cây.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#insertion-mode
	 *
	 * @var string
	 */
	public $insertion_mode = self::INSERTION_MODE_INITIAL;

	/**
	 * Nút ngữ cảnh khởi tạo bộ phân tích đoạn, nếu được tạo như bộ phân tích đoạn.
	 *
	 * @since 6.4.0
	 * @deprecated 6.8.0 WP_HTML_Processor theo dõi context_node nội bộ.
	 *
	 * @var null
	 */
	public $context_node = null;

	/**
	 * Bảng mã được nhận dạng của luồng byte đầu vào.
	 *
	 * > Luồng các điểm mã tạo nên đầu vào cho giai đoạn phân tách token
	 * > ban đầu sẽ được tác nhân người dùng nhìn nhận như một luồng byte
	 * > (thường đến từ mạng hoặc từ hệ thống tệp cục bộ).
	 * > Các byte mã hóa các ký tự thực tế theo một bảng mã ký tự
	 * > cụ thể, mà tác nhân người dùng sử dụng để giải mã các byte thành ký tự.
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $encoding = null;

	/**
	 * Mức độ tin cậy của bộ phân tích vào bảng mã đầu vào.
	 *
	 * > Khi bộ phân tích HTML đang giải mã một luồng byte đầu vào, nó sử dụng một bảng mã
	 * > ký tự và một mức độ tin cậy. Mức độ tin cậy có thể là tạm thời, chắc chắn, hoặc
	 * > không liên quan. Bảng mã được sử dụng, và liệu mức độ tin cậy vào bảng mã đó
	 * > là tạm thời hay chắc chắn, được sử dụng trong quá trình phân tích để xác định liệu
	 * > có nên thay đổi bảng mã hay không. Nếu không cần bảng mã, ví dụ vì bộ phân tích
	 * > đang hoạt động trên một luồng Unicode và không cần sử dụng bảng mã ký tự
	 * > nào cả, thì mức độ tin cậy là không liên quan.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	public $encoding_confidence = 'tentative';

	/**
	 * Con trỏ phần tử HEAD.
	 *
	 * @since 6.7.0
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#head-element-pointer
	 *
	 * @var WP_HTML_Token|null
	 */
	public $head_element = null;

	/**
	 * Con trỏ phần tử FORM.
	 *
	 * > trỏ đến phần tử form cuối cùng được mở và chưa thấy thẻ đóng của nó.
	 * > Nó được sử dụng để liên kết các điều khiển biểu mẫu với biểu mẫu khi gặp
	 * > markup rất tệ, vì lý do lịch sử.
	 * > Nó bị bỏ qua bên trong các phần tử template.
	 *
	 * @todo Giá trị này có thể bị vô hiệu bởi thao tác seek.
	 *
	 * @see https://html.spec.whatwg.org/#form-element-pointer
	 *
	 * @since 6.7.0
	 *
	 * @var WP_HTML_Token|null
	 */
	public $form_element = null;

	/**
	 * Cờ frameset-ok cho biết liệu phần tử `FRAMESET` có được phép trong trạng thái hiện tại hay không.
	 *
	 * > Cờ frameset-ok được đặt thành "ok" khi bộ phân tích được tạo. Nó được đặt thành "not ok" sau khi gặp một số token nhất định.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#frameset-ok-flag
	 *
	 * @var bool
	 */
	public $frameset_ok = true;

	/**
	 * Hàm khởi tạo - tạo một giá trị trạng thái mới và rỗng.
	 *
	 * @since 6.4.0
	 *
	 * @see WP_HTML_Processor
	 */
	public function __construct() {
		$this->stack_of_open_elements     = new WP_HTML_Open_Elements();
		$this->active_formatting_elements = new WP_HTML_Active_Formatting_Elements();
	}
}
