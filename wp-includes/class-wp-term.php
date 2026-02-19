<?php
/**
 * API Taxonomy: Lớp WP_Term
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để triển khai đối tượng WP_Term.
 *
 * @since 4.4.0
 *
 * @property-read object $data Dữ liệu term đã được làm sạch.
 */
#[AllowDynamicProperties]
final class WP_Term {

	/**
	 * ID term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $term_id;

	/**
	 * Tên của term.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $name = '';

	/**
	 * Slug của term.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $slug = '';

	/**
	 * Nhóm term (term_group) của term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $term_group = '';

	/**
	 * ID Taxonomy của term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $term_taxonomy_id = 0;

	/**
	 * Tên taxonomy của term.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $taxonomy = '';

	/**
	 * Mô tả của term.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $description = '';

	/**
	 * ID của term cha.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $parent = 0;

	/**
	 * Số đối tượng đã cache cho term này.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $count = 0;

	/**
	 * Lưu trữ mức độ làm sạch dữ liệu của đối tượng term.
	 *
	 * Không tương ứng với trường nào trong cơ sở dữ liệu.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $filter = 'raw';

	/**
	 * Lấy instance WP_Term.
	 *
	 * @since 4.4.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
	 *
	 * @param int    $term_id  ID term.
	 * @param string $taxonomy Tùy chọn. Giới hạn các term khớp với `$taxonomy`. Chỉ dùng để
	 *                         phân biệt các term có thể dùng chung.
	 * @return WP_Term|WP_Error|false Đối tượng term nếu tìm thấy. WP_Error nếu `$term_id` được dùng chung giữa các taxonomy và
	 *                                không đủ dữ liệu để phân biệt term nào được yêu cầu.
	 *                                False cho các trường hợp thất bại khác.
	 */
	public static function get_instance( $term_id, $taxonomy = null ) {
		global $wpdb;

		$term_id = (int) $term_id;
		if ( ! $term_id ) {
			return false;
		}

		$_term = wp_cache_get( $term_id, 'terms' );

		// Nếu không có phiên bản cache, truy vấn cơ sở dữ liệu.
		if ( ! $_term || ( $taxonomy && $taxonomy !== $_term->taxonomy ) ) {
			// Bất kỳ term nào tìm thấy trong cache đều không khớp, nên không dùng nó.
			$_term = false;

			// Lấy tất cả term khớp, phòng trường hợp term được dùng chung giữa các taxonomy.
			$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id = %d", $term_id ) );
			if ( ! $terms ) {
				return false;
			}

			// Nếu taxonomy được chỉ định, tìm kết quả khớp.
			if ( $taxonomy ) {
				foreach ( $terms as $match ) {
					if ( $taxonomy === $match->taxonomy ) {
						$_term = $match;
						break;
					}
				}

				// Nếu chỉ có một kết quả khớp, đó là kết quả cần tìm.
			} elseif ( 1 === count( $terms ) ) {
				$_term = reset( $terms );

				// Ngược lại, term phải được dùng chung giữa các taxonomy.
			} else {
				// Nếu term chỉ được dùng chung với các taxonomy không hợp lệ, trả về term hợp lệ duy nhất.
				foreach ( $terms as $t ) {
					if ( ! taxonomy_exists( $t->taxonomy ) ) {
						continue;
					}

					// Chỉ chạy vào đây nếu đã xác định được term trong taxonomy hợp lệ.
					if ( $_term ) {
						return new WP_Error( 'ambiguous_term_id', __( 'Term ID is shared between multiple taxonomies' ), $term_id );
					}

					$_term = $t;
				}
			}

			if ( ! $_term ) {
				return false;
			}

			// Không trả về term từ các taxonomy không hợp lệ.
			if ( ! taxonomy_exists( $_term->taxonomy ) ) {
				return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
			}

			$_term = sanitize_term( $_term, $_term->taxonomy, 'raw' );

			// Không cache các term được dùng chung giữa các taxonomy.
			if ( 1 === count( $terms ) ) {
				wp_cache_add( $term_id, $_term, 'terms' );
			}
		}

		$term_obj = new WP_Term( $_term );
		$term_obj->filter( $term_obj->filter );

		return $term_obj;
	}

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Term|object $term Đối tượng term.
	 */
	public function __construct( $term ) {
		foreach ( get_object_vars( $term ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Làm sạch các trường term theo loại bộ lọc được cung cấp.
	 *
	 * @since 4.4.0
	 *
	 * @param string $filter Ngữ cảnh bộ lọc. Chấp nhận 'edit', 'db', 'display', 'attribute', 'js', 'rss', hoặc 'raw'.
	 */
	public function filter( $filter ) {
		sanitize_term( $this, $this->taxonomy, $filter );
	}

	/**
	 * Chuyển đổi đối tượng sang mảng.
	 *
	 * @since 4.4.0
	 *
	 * @return array Đối tượng dưới dạng mảng.
	 */
	public function to_array() {
		return get_object_vars( $this );
	}

	/**
	 * Phương thức getter.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Thuộc tính cần lấy.
	 * @return mixed Giá trị thuộc tính.
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'data':
				$data    = new stdClass();
				$columns = array( 'term_id', 'name', 'slug', 'term_group', 'term_taxonomy_id', 'taxonomy', 'description', 'parent', 'count' );
				foreach ( $columns as $column ) {
					$data->{$column} = isset( $this->{$column} ) ? $this->{$column} : null;
				}

				return sanitize_term( $data, $data->taxonomy, 'raw' );
		}
	}
}
