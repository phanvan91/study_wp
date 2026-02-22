<?php
/**
 * HTML API: Lớp WP_HTML_Doctype_Info
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since 6.7.0
 */

/**
 * Lớp lõi được sử dụng bởi HTML API để biểu diễn một khai báo DOCTYPE.
 *
 * Lớp này phân tích các token DOCTYPE cho bộ phân tích đầy đủ trong HTML Processor.
 * Hầu hết mã tương tác với HTML không cần phải phân tích khai báo DOCTYPE;
 * HTML Processor là một ngoại lệ. Tham khảo HTML Processor để phân tích
 * đúng cách một tài liệu HTML.
 *
 * Một khai báo DOCTYPE có thể cho biết chế độ tương thích của tài liệu, ảnh hưởng đến
 * cấu trúc của HTML tiếp theo cũng như hành vi của các bộ chọn lớp CSS.
 * Có ba chế độ khả thi:
 *
 *  - Chế độ "no-quirks" và "limited-quirks" (còn gọi là "chế độ tiêu chuẩn").
 *  - Chế độ "quirks".
 *
 * Các chế độ này chủ yếu xác định liệu bộ chọn tên lớp CSS có khớp với giá trị trong
 * thuộc tính `class` HTML theo kiểu không phân biệt chữ hoa chữ thường ASCII (chế độ quirks),
 * hay chúng chỉ khớp khi giống hệt nhau từng byte (chế độ no-quirks).
 *
 * Tất cả tài liệu HTML nên bắt đầu với DOCTYPE HTML5 tiêu chuẩn: `<!DOCTYPE html>`.
 *
 * > DOCTYPE là bắt buộc vì lý do kế thừa. Khi bị bỏ qua, trình duyệt có xu hướng sử dụng
 * > chế độ hiển thị khác không tương thích với một số đặc tả. Bao gồm DOCTYPE trong
 * > tài liệu đảm bảo rằng trình duyệt cố gắng hết sức để tuân theo
 * > các đặc tả liên quan.
 *
 * @see https://html.spec.whatwg.org/#the-doctype
 *
 * Khai báo DOCTYPE bao gồm bốn thuộc tính: tên, định danh công khai, định danh hệ thống,
 * và chỉ báo về chế độ tương thích tài liệu nào chúng sẽ ngụ ý nếu bộ phân tích HTML
 * chưa xác định nó từ thông tin khác.
 *
 * @see https://html.spec.whatwg.org/#the-initial-insertion-mode
 *
 * Trong lịch sử, khai báo DOCTYPE được sử dụng trong tài liệu SGML để hướng dẫn bộ phân tích
 * cách diễn giải các thẻ và thực thể khác nhau trong một tài liệu. Vai trò của nó trong HTML đã
 * phân kỳ với cách nó được sử dụng trong SGML và không nên suy luận ngược ý nghĩa vào HTML dựa
 * trên cách nó được sử dụng trong tài liệu SGML, XML, hoặc XHTML.
 *
 * @see https://www.iso.org/standard/16387.html
 *
 * @since 6.7.0
 *
 * @see WP_HTML_Processor
 */
class WP_HTML_Doctype_Info {
	/**
	 * Tên của DOCTYPE: nên là "html" cho tài liệu HTML.
	 *
	 * Giá trị này nên được coi là "chỉ đọc" và không được sửa đổi.
	 *
	 * Trong lịch sử, tên DOCTYPE cho biết tên của phần tử gốc của tài liệu.
	 *
	 *     <!DOCTYPE html>
	 *               ╰──┴── tên là "html".
	 *
	 * @see https://html.spec.whatwg.org/#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $name = null;

	/**
	 * Định danh công khai của DOCTYPE.
	 *
	 * Giá trị này nên được coi là "chỉ đọc" và không được sửa đổi.
	 *
	 * Định danh công khai là tùy chọn và không nên xuất hiện trong tài liệu HTML.
	 * Giá trị `null` cho biết rằng không có định danh công khai nào có mặt trong DOCTYPE.
	 *
	 * Trong lịch sử, sự hiện diện của định danh công khai cho biết rằng tài liệu
	 * được dùng để chia sẻ giữa các hệ thống máy tính và giá trị cho biết với
	 * bộ phân tích am hiểu cách tìm định nghĩa kiểu tài liệu (DTD) liên quan.
	 *
	 *     <!DOCTYPE html PUBLIC "public id goes here in quotes">
	 *               │  │         ╰─── định danh công khai ────╯
	 *               ╰──┴── tên là "html".
	 *
	 * @see https://html.spec.whatwg.org/#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $public_identifier = null;

	/**
	 * Định danh hệ thống của DOCTYPE.
	 *
	 * Giá trị này nên được coi là "chỉ đọc" và không được sửa đổi.
	 *
	 * Định danh hệ thống là tùy chọn và không nên xuất hiện trong tài liệu HTML.
	 * Giá trị `null` cho biết rằng không có định danh hệ thống nào có mặt trong DOCTYPE.
	 *
	 * Trong lịch sử, định danh hệ thống chỉ định nơi lưu trữ khai báo kiểu tài liệu
	 * liên quan cho tài liệu đã cho và có thể được truy xuất.
	 *
	 *     <!DOCTYPE html SYSTEM "system id goes here in quotes">
	 *               │  │         ╰──── định danh hệ thống ────╯
	 *               ╰──┴── tên là "html".
	 *
	 * Nếu định danh công khai được cung cấp, nó sẽ cho biết với bộ phân tích
	 * am hiểu cách diễn giải định danh hệ thống.
	 *
	 *     <!DOCTYPE html PUBLIC "public id goes here in quotes" "system id goes here in quotes">
	 *               │  │         ╰─── định danh công khai ────╯   ╰──── định danh hệ thống ────╯
	 *               ╰──┴── tên là "html".
	 *
	 * @see https://html.spec.whatwg.org/#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $system_identifier = null;

	/**
	 * Chế độ tương thích tài liệu nào mà khai báo DOCTYPE này cho biết.
	 *
	 * Giá trị này nên được coi là "chỉ đọc" và không được sửa đổi.
	 *
	 * Khi bộ phân tích HTML chưa thiết lập chế độ tương thích tài liệu,
	 * (ví dụ: chế độ "quirks" hoặc "no-quirks"), nó sẽ suy luận từ các thuộc tính
	 * của khai báo DOCTYPE phù hợp, nếu có. DOCTYPE có thể
	 * cho biết một trong ba chế độ tương thích tài liệu khả thi:
	 *
	 *  - Chế độ "no-quirks" và "limited-quirks" (còn gọi là chế độ "tiêu chuẩn").
	 *  - Chế độ "quirks" (còn gọi là chế độ `CSS1Compat`).
	 *
	 * DOCTYPE phù hợp là DOCTYPE được gặp trong chế độ chèn "initial",
	 * trước khi phần tử HTML được mở và trước khi tìm thấy bất kỳ
	 * token khai báo DOCTYPE nào khác.
	 *
	 * @see https://html.spec.whatwg.org/#the-initial-insertion-mode
	 *
	 * @since 6.7.0
	 *
	 * @var string Một trong "no-quirks", "limited-quirks", hoặc "quirks".
	 */
	public $indicated_compatability_mode;

	/**
	 * Hàm khởi tạo.
	 *
	 * Lớp này không nên được khởi tạo trực tiếp.
	 * Sử dụng phương thức tĩnh {@see self::from_doctype_token} thay thế.
	 *
	 * Các tham số của hàm khởi tạo này tương ứng với "token DOCTYPE"
	 * như được định nghĩa trong đặc tả HTML.
	 *
	 * > Các token DOCTYPE có tên, định danh công khai, định danh hệ thống,
	 * > và cờ force-quirks. Khi một token DOCTYPE được tạo, tên, định danh công khai,
	 * > và định danh hệ thống phải được đánh dấu là thiếu (đây là trạng thái khác biệt với
	 * > chuỗi rỗng), và cờ force-quirks phải được đặt thành tắt (trạng thái khác của nó là bật).
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @param string|null $name              Tên của DOCTYPE.
	 * @param string|null $public_identifier Định danh công khai của DOCTYPE.
	 * @param string|null $system_identifier Định danh hệ thống của DOCTYPE.
	 * @param bool        $force_quirks_flag Cờ force-quirks có được đặt cho token hay không.
	 */
	private function __construct(
		?string $name,
		?string $public_identifier,
		?string $system_identifier,
		bool $force_quirks_flag
	) {
		$this->name              = $name;
		$this->public_identifier = $public_identifier;
		$this->system_identifier = $system_identifier;

		/*
		 * > Nếu token DOCTYPE khớp với một trong các điều kiện trong danh sách sau,
		 * > thì đặt Tài liệu sang chế độ quirks:
		 */

		/*
		 * > Cờ force-quirks được đặt thành bật.
		 */
		if ( $force_quirks_flag ) {
			$this->indicated_compatability_mode = 'quirks';
			return;
		}

		/*
		 * Tài liệu chuẩn sẽ chứa chuỗi `<!DOCTYPE html>` không có
		 * định danh công khai hoặc hệ thống; thoát sớm để tránh phân tích thêm.
		 */
		if ( 'html' === $name && null === $public_identifier && null === $system_identifier ) {
			$this->indicated_compatability_mode = 'no-quirks';
			return;
		}

		/*
		 * > Tên không phải là "html".
		 *
		 * Bộ phân tích token phải báo cáo tên bằng chữ thường ngay cả khi được cung cấp trong
		 * tài liệu bằng chữ hoa; do đó không cần chuyển đổi ở đây.
		 */
		if ( 'html' !== $name ) {
			$this->indicated_compatability_mode = 'quirks';
			return;
		}

		/*
		 * Thiết lập một số biến để xử lý các điều kiện còn lại.
		 *
		 * > đặt...định danh công khai...thành...chuỗi rỗng nếu định danh công khai bị thiếu.
		 * > đặt...định danh hệ thống...thành...chuỗi rỗng nếu định danh hệ thống bị thiếu.
		 * >
		 * > Chuỗi định danh hệ thống và định danh công khai phải được so sánh...
		 * > theo cách không phân biệt chữ hoa chữ thường ASCII.
		 * >
		 * > Định danh hệ thống có giá trị là chuỗi rỗng không được coi là thiếu
		 * > cho mục đích của các điều kiện ở trên.
		 */
		$system_identifier_is_missing = null === $system_identifier;
		$public_identifier            = null === $public_identifier ? '' : strtolower( $public_identifier );
		$system_identifier            = null === $system_identifier ? '' : strtolower( $system_identifier );

		/*
		 * > Định danh công khai được đặt thành…
		 */
		if (
			'-//w3o//dtd w3 html strict 3.0//en//' === $public_identifier ||
			'-/w3c/dtd html 4.0 transitional/en' === $public_identifier ||
			'html' === $public_identifier
		) {
			$this->indicated_compatability_mode = 'quirks';
			return;
		}

		/*
		 * > Định danh hệ thống được đặt thành…
		 */
		if ( 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd' === $system_identifier ) {
			$this->indicated_compatability_mode = 'quirks';
			return;
		}

		/*
		 * Tất cả các điều kiện sau phụ thuộc vào việc khớp định danh công khai.
		 * Nếu định danh công khai rỗng, không có điều kiện nào sau đây sẽ khớp.
		 */
		if ( '' === $public_identifier ) {
			$this->indicated_compatability_mode = 'no-quirks';
			return;
		}

		/*
		 * > Định danh công khai bắt đầu bằng…
		 *
		 * @todo Tối ưu hóa việc khớp này. Nó không nên là vấn đề hiệu suất lớn nói chung,
		 *       tuy nhiên, vì chỉ một token khai báo DOCTYPE duy nhất nên được phân tích,
		 *       và tài liệu chuẩn sẽ đã thoát trước khi đến điều kiện này.
		 */
		if (
			str_starts_with( $public_identifier, '+//silmaril//dtd html pro v0r11 19970101//' ) ||
			str_starts_with( $public_identifier, '-//as//dtd html 3.0 aswedit + extensions//' ) ||
			str_starts_with( $public_identifier, '-//advasoft ltd//dtd html 3.0 aswedit + extensions//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 strict level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 strict level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 strict//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.1e//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3.0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3.2 final//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3.2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 3//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 3//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html//' ) ||
			str_starts_with( $public_identifier, '-//metrius//dtd metrius presentational//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 2.0 html strict//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 2.0 html//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 2.0 tables//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 3.0 html strict//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 3.0 html//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 3.0 tables//' ) ||
			str_starts_with( $public_identifier, '-//netscape comm. corp.//dtd html//' ) ||
			str_starts_with( $public_identifier, '-//netscape comm. corp.//dtd strict html//' ) ||
			str_starts_with( $public_identifier, "-//o'reilly and associates//dtd html 2.0//" ) ||
			str_starts_with( $public_identifier, "-//o'reilly and associates//dtd html extended 1.0//" ) ||
			str_starts_with( $public_identifier, "-//o'reilly and associates//dtd html extended relaxed 1.0//" ) ||
			str_starts_with( $public_identifier, '-//sq//dtd html 2.0 hotmetal + extensions//' ) ||
			str_starts_with( $public_identifier, '-//softquad software//dtd hotmetal pro 6.0::19990601::extensions to html 4.0//' ) ||
			str_starts_with( $public_identifier, '-//softquad//dtd hotmetal pro 4.0::19971010::extensions to html 4.0//' ) ||
			str_starts_with( $public_identifier, '-//spyglass//dtd html 2.0 extended//' ) ||
			str_starts_with( $public_identifier, '-//sun microsystems corp.//dtd hotjava html//' ) ||
			str_starts_with( $public_identifier, '-//sun microsystems corp.//dtd hotjava strict html//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3 1995-03-24//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2 draft//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2 final//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2s draft//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 4.0 frameset//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 4.0 transitional//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html experimental 19960712//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html experimental 970421//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd w3 html//' ) ||
			str_starts_with( $public_identifier, '-//w3o//dtd w3 html 3.0//' ) ||
			str_starts_with( $public_identifier, '-//webtechs//dtd mozilla html 2.0//' ) ||
			str_starts_with( $public_identifier, '-//webtechs//dtd mozilla html//' )
		) {
			$this->indicated_compatability_mode = 'quirks';
			return;
		}

		/*
		 * > Định danh hệ thống bị thiếu và định danh công khai bắt đầu bằng…
		 */
		if (
			$system_identifier_is_missing && (
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 frameset//' ) ||
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 transitional//' )
			)
		) {
			$this->indicated_compatability_mode = 'quirks';
			return;
		}

		/*
		 * > Nếu không, nếu token DOCTYPE khớp với một trong các điều kiện trong
		 * > danh sách sau, thì đặt Tài liệu sang chế độ limited-quirks.
		 */

		/*
		 * > Định danh công khai bắt đầu bằng…
		 */
		if (
			str_starts_with( $public_identifier, '-//w3c//dtd xhtml 1.0 frameset//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd xhtml 1.0 transitional//' )
		) {
			$this->indicated_compatability_mode = 'limited-quirks';
			return;
		}

		/*
		 * > Định danh hệ thống không bị thiếu và định danh công khai bắt đầu bằng…
		 */
		if (
			! $system_identifier_is_missing && (
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 frameset//' ) ||
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 transitional//' )
			)
		) {
			$this->indicated_compatability_mode = 'limited-quirks';
			return;
		}

		$this->indicated_compatability_mode = 'no-quirks';
	}

	/**
	 * Tạo một instance WP_HTML_Doctype_Info bằng cách phân tích một token khai báo DOCTYPE thô.
	 *
	 * Sử dụng phương thức này để phân tích một token khai báo DOCTYPE và truy cập các thuộc tính
	 * của nó thông qua instance lớp WP_HTML_Doctype_Info được trả về. Đầu vào được cung cấp phải
	 * phân tích đúng như một khai báo DOCTYPE, mặc dù nó không nhất thiết phải đại diện cho một DOCTYPE hợp lệ.
	 *
	 * Ví dụ:
	 *
	 *     // Khai báo DOCTYPE HTML chuẩn.
	 *     $doctype = WP_HTML_Doctype_Info::from_doctype_token( '<!DOCTYPE html>' );
	 *     'no-quirks' === $doctype->indicated_compatability_mode;
	 *
	 *     // Một DOCTYPE vô nghĩa vẫn hợp lệ và sẽ cho biết chế độ "quirks".
	 *     $doctype = WP_HTML_Doctype_Info::from_doctype_token( '<!doctypeJSON SILLY "nonsense\'>' );
	 *     'quirks' === $doctype->indicated_compatability_mode;
	 *
	 *     // Các đặc điểm văn bản trong HTML thô được xử lý phù hợp.
	 *     $doctype = WP_HTML_Doctype_Info::from_doctype_token( "<!DOCTYPE\nhtml\n>" );
	 *     'no-quirks' === $doctype->indicated_compatability_mode;
	 *
	 *     // Bất kỳ thứ gì khác ngoài token khai báo DOCTYPE đúng sẽ không phân tích được.
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( ' <!DOCTYPE>' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( '<!DOCTYPE ><p>' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( '<!TYPEDOC>' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( 'html' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( '<?xml version="1.0" encoding="UTF-8" ?>' );
	 *
	 * @since 6.7.0
	 *
	 * @param string $doctype_html Chuỗi HTML DOCTYPE thô hoàn chỉnh, ví dụ `<!DOCTYPE html>`.
	 *
	 * @return WP_HTML_Doctype_Info|null Một instance WP_HTML_Doctype_Info sẽ được trả về nếu
	 *                                   HTML DOCTYPE được cung cấp là một DOCTYPE hợp lệ. Ngược lại, null.
	 */
	public static function from_doctype_token( string $doctype_html ): ?self {
		$doctype_name      = null;
		$doctype_public_id = null;
		$doctype_system_id = null;

		$end = strlen( $doctype_html ) - 1;

		/*
		 * Bộ phân tích này kết hợp các quy tắc phân tích token DOCTYPE được tìm thấy trong
		 * đặc tả HTML cho các trạng thái bộ phân tích token liên quan đến DOCTYPE.
		 *
		 * @see https://html.spec.whatwg.org/#doctype-state
		 */

		/*
		 * - Token HTML DOCTYPE hợp lệ phải có ít nhất `<!DOCTYPE>` giả sử một token hoàn chỉnh
		 *   không kết thúc bằng end-of-file.
		 * - Nó phải bắt đầu bằng một khớp không phân biệt chữ hoa chữ thường ASCII cho `<!DOCTYPE`.
		 * - Lần xuất hiện duy nhất của `>` phải là byte cuối cùng trong chuỗi HTML.
		 */
		if (
			$end < 9 ||
			0 !== substr_compare( $doctype_html, '<!DOCTYPE', 0, 9, true )
		) {
			return null;
		}

		$at = 9;
		// Có đúng một và chỉ một `>` không?
		if ( '>' !== $doctype_html[ $end ] || ( strcspn( $doctype_html, '>', $at ) + $at ) < $end ) {
			return null;
		}

		/*
		 * Thực hiện chuẩn hóa ký tự xuống dòng và đảm bảo giá trị $end là đúng sau khi chuẩn hóa.
		 *
		 * @see https://html.spec.whatwg.org/#preprocessing-the-input-stream
		 * @see https://infra.spec.whatwg.org/#normalize-newlines
		 */
		$doctype_html = str_replace( "\r\n", "\n", $doctype_html );
		$doctype_html = str_replace( "\r", "\n", $doctype_html );
		$end          = strlen( $doctype_html ) - 1;

		/*
		 * Trong trạng thái này, token doctype đã được tìm thấy và "nội dung" của nó tùy chọn bao gồm
		 * tên, định danh công khai, và định danh hệ thống nằm giữa vị trí hiện tại và cuối.
		 *
		 *     "<!DOCTYPE...khai báo...>"
		 *               ╰─ $at        ╰─ $end
		 *
		 * Cũng có thể phần khai báo là rỗng.
		 *
		 *               ╭─ $at
		 *     "<!DOCTYPE>"
		 *               ╰─ $end
		 *
		 * Các quy tắc phân tích ">" kết thúc DOCTYPE không cần được xem xét vì chúng
		 * đã được xử lý ở trên trong điều kiện rằng HTML DOCTYPE được cung cấp phải chứa
		 * đúng một ký tự ">" ở vị trí cuối cùng.
		 */

		/*
		 *
		 * Quá trình phân tích thực tế bắt đầu ở "Trạng thái trước tên DOCTYPE". Bỏ qua khoảng trắng và
		 * tiến đến trạng thái tiếp theo.
		 *
		 * @see https://html.spec.whatwg.org/#before-doctype-name-state
		 */
		$at += strspn( $doctype_html, " \t\n\f\r", $at );

		if ( $at >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		$name_length  = strcspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
		$doctype_name = str_replace( "\0", "\u{FFFD}", strtolower( substr( $doctype_html, $at, $name_length ) ) );

		$at += $name_length;
		$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
		if ( $at >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, false );
		}

		/*
		 * "Trạng thái sau tên DOCTYPE"
		 *
		 * Tìm một khớp không phân biệt chữ hoa chữ thường cho "PUBLIC" hoặc "SYSTEM" tại điểm này.
		 * Nếu không, đặt force-quirks và vào trạng thái DOCTYPE giả (bỏ qua phần còn lại của doctype).
		 *
		 * @see https://html.spec.whatwg.org/#after-doctype-name-state
		 */
		if ( $at + 6 >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		/*
		 * > Nếu sáu ký tự bắt đầu từ ký tự đầu vào hiện tại là một khớp
		 * > không phân biệt chữ hoa chữ thường ASCII cho từ "PUBLIC", thì tiêu thụ các ký tự đó
		 * > và chuyển sang trạng thái sau từ khóa PUBLIC của DOCTYPE.
		 */
		if ( 0 === substr_compare( $doctype_html, 'PUBLIC', $at, 6, true ) ) {
			$at += 6;
			$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
			if ( $at >= $end ) {
				return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
			}
			goto parse_doctype_public_identifier;
		}

		/*
		 * > Nếu không, nếu sáu ký tự bắt đầu từ ký tự đầu vào hiện tại là một khớp
		 * > không phân biệt chữ hoa chữ thường ASCII cho từ "SYSTEM", thì tiêu thụ các ký tự đó
		 * > và chuyển sang trạng thái sau từ khóa SYSTEM của DOCTYPE.
		 */
		if ( 0 === substr_compare( $doctype_html, 'SYSTEM', $at, 6, true ) ) {
			$at += 6;
			$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
			if ( $at >= $end ) {
				return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
			}
			goto parse_doctype_system_identifier;
		}

		/*
		 * > Nếu không, đây là lỗi phân tích chuỗi-ký-tự-không-hợp-lệ-sau-tên-doctype.
		 * > Đặt cờ force-quirks của token DOCTYPE hiện tại thành bật. Tiêu thụ lại trong trạng thái
		 * > DOCTYPE giả.
		 */
		return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );

		parse_doctype_public_identifier:
		/*
		 * Bộ phân tích nên vào "trạng thái định danh công khai DOCTYPE (nháy kép)" hoặc
		 * "trạng thái định danh công khai DOCTYPE (nháy đơn)" bằng cách tìm một trong các dấu nháy hợp lệ.
		 * Bất kỳ thứ gì khác sẽ buộc chế độ quirks và bỏ qua phần còn lại của nội dung.
		 *
		 * @see https://html.spec.whatwg.org/#doctype-public-identifier-(double-quoted)-state
		 * @see https://html.spec.whatwg.org/#doctype-public-identifier-(single-quoted)-state
		 */
		$closer_quote = $doctype_html[ $at ];

		/*
		 * > Đây là lỗi phân tích thiếu-dấu-nháy-trước-định-danh-công-khai-doctype. Đặt cờ
		 * > force-quirks của token DOCTYPE hiện tại thành bật. Tiêu thụ lại trong trạng thái DOCTYPE giả.
		 */
		if ( '"' !== $closer_quote && "'" !== $closer_quote ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		++$at;

		$identifier_length = strcspn( $doctype_html, $closer_quote, $at, $end - $at );
		$doctype_public_id = str_replace( "\0", "\u{FFFD}", substr( $doctype_html, $at, $identifier_length ) );

		$at += $identifier_length;
		if ( $at >= $end || $closer_quote !== $doctype_html[ $at ] ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		++$at;

		/*
		 * "Trạng thái giữa định danh công khai và định danh hệ thống của DOCTYPE"
		 *
		 * Di chuyển qua khoảng trắng giữa định danh công khai và định danh hệ thống.
		 *
		 * @see https://html.spec.whatwg.org/#between-doctype-public-and-system-identifiers-state
		 */
		$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
		if ( $at >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, false );
		}

		parse_doctype_system_identifier:
		/*
		 * Bộ phân tích nên vào "trạng thái định danh hệ thống DOCTYPE (nháy kép)" hoặc
		 * "trạng thái định danh hệ thống DOCTYPE (nháy đơn)" bằng cách tìm một trong các dấu nháy hợp lệ.
		 * Bất kỳ thứ gì khác sẽ buộc chế độ quirks và bỏ qua phần còn lại của nội dung.
		 *
		 * @see https://html.spec.whatwg.org/#doctype-system-identifier-(double-quoted)-state
		 * @see https://html.spec.whatwg.org/#doctype-system-identifier-(single-quoted)-state
		 */
		$closer_quote = $doctype_html[ $at ];

		/*
		 * > Đây là lỗi phân tích thiếu-dấu-nháy-trước-định-danh-hệ-thống-doctype. Đặt cờ
		 * > force-quirks của token DOCTYPE hiện tại thành bật. Tiêu thụ lại trong trạng thái DOCTYPE giả.
		 */
		if ( '"' !== $closer_quote && "'" !== $closer_quote ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		++$at;

		$identifier_length = strcspn( $doctype_html, $closer_quote, $at, $end - $at );
		$doctype_system_id = str_replace( "\0", "\u{FFFD}", substr( $doctype_html, $at, $identifier_length ) );

		$at += $identifier_length;
		if ( $at >= $end || $closer_quote !== $doctype_html[ $at ] ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		return new self( $doctype_name, $doctype_public_id, $doctype_system_id, false );
	}
}
