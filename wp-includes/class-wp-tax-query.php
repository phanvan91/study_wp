<?php
/**
 * API Taxonomy: Lớp WP_Tax_Query
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để triển khai truy vấn taxonomy cho API Taxonomy.
 *
 * Dùng để tạo các mệnh đề SQL lọc truy vấn chính theo các term taxonomy của đối tượng.
 *
 * WP_Tax_Query là trình trợ giúp cho phép các lớp truy vấn chính, như WP_Query, lọc
 * kết quả theo metadata đối tượng, bằng cách tạo các mệnh đề con `JOIN` và `WHERE` để
 * gắn vào chuỗi truy vấn SQL chính.
 *
 * @since 3.1.0
 */
#[AllowDynamicProperties]
class WP_Tax_Query {

	/**
	 * Mảng các truy vấn taxonomy.
	 *
	 * Xem WP_Tax_Query::__construct() để biết thông tin về tham số truy vấn tax.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	public $queries = array();

	/**
	 * Mối quan hệ giữa các truy vấn. Có thể là 'AND' hoặc 'OR'.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $relation;

	/**
	 * Phản hồi chuẩn khi truy vấn không nên trả về bất kỳ hàng nào.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	private static $no_results = array(
		'join'  => array( '' ),
		'where' => array( '0 = 1' ),
	);

	/**
	 * Danh sách phẳng các bí danh bảng được sử dụng trong các mệnh đề JOIN.
	 *
	 * @since 4.1.0
	 * @var array
	 */
	protected $table_aliases = array();

	/**
	 * Các term và taxonomy được lấy bởi truy vấn này.
	 *
	 * Chúng ta lưu dữ liệu này trong mảng phẳng vì chúng được tham chiếu ở
	 * nhiều nơi bởi WP_Query.
	 *
	 * @since 4.1.0
	 * @var array
	 */
	public $queried_terms = array();

	/**
	 * Bảng cơ sở dữ liệu nơi lưu trữ các đối tượng của metadata (vd: $wpdb->users).
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $primary_table;

	/**
	 * Cột trong 'primary_table' đại diện cho ID của đối tượng.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $primary_id_column;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 3.1.0
	 * @since 4.1.0 Thêm hỗ trợ cho giá trị 'NOT EXISTS' và 'EXISTS' của `$operator`.
	 *
	 * @param array $tax_query {
	 *     Mảng các mệnh đề truy vấn taxonomy.
	 *
	 *     @type string $relation Tùy chọn. Từ khóa MySQL dùng để nối
	 *                            các mệnh đề của truy vấn. Chấp nhận 'AND' hoặc 'OR'. Mặc định 'AND'.
	 *     @type array  ...$0 {
	 *         Mảng tham số mệnh đề bậc một, hoặc một truy vấn tax đầy đủ khác.
	 *
	 *         @type string           $taxonomy         Taxonomy đang được truy vấn. Tùy chọn khi field=term_taxonomy_id.
	 *         @type string|int|array $terms            Term hoặc các term để lọc.
	 *         @type string           $field            Trường để so khớp $terms. Chấp nhận 'term_id', 'slug',
	 *                                                 'name', hoặc 'term_taxonomy_id'. Mặc định: 'term_id'.
	 *         @type string           $operator         Toán tử MySQL dùng với $terms trong mệnh đề WHERE.
	 *                                                  Chấp nhận 'AND', 'IN', 'NOT IN', 'EXISTS', 'NOT EXISTS'.
	 *                                                  Mặc định: 'IN'.
	 *         @type bool             $include_children Tùy chọn. Có bao gồm các term con hay không.
	 *                                                  Yêu cầu một $taxonomy. Mặc định: true.
	 *     }
	 * }
	 */
	public function __construct( $tax_query ) {
		if ( isset( $tax_query['relation'] ) ) {
			$this->relation = $this->sanitize_relation( $tax_query['relation'] );
		} else {
			$this->relation = 'AND';
		}

		$this->queries = $this->sanitize_query( $tax_query );
	}

	/**
	 * Đảm bảo tham số 'tax_query' được truyền vào hàm khởi tạo có định dạng đúng.
	 *
	 * Đảm bảo rằng mỗi mệnh đề cấp truy vấn có khóa 'relation', và
	 * mỗi mệnh đề bậc một chứa tất cả các khóa cần thiết từ `$defaults`.
	 *
	 * @since 4.1.0
	 *
	 * @param array $queries Mảng các mệnh đề truy vấn.
	 * @return array Mảng mệnh đề truy vấn đã được làm sạch.
	 */
	public function sanitize_query( $queries ) {
		$cleaned_query = array();

		$defaults = array(
			'taxonomy'         => '',
			'terms'            => array(),
			'field'            => 'term_id',
			'operator'         => 'IN',
			'include_children' => true,
		);

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$cleaned_query['relation'] = $this->sanitize_relation( $query );

				// Mệnh đề bậc một.
			} elseif ( self::is_first_order_clause( $query ) ) {

				$cleaned_clause          = array_merge( $defaults, $query );
				$cleaned_clause['terms'] = (array) $cleaned_clause['terms'];
				$cleaned_query[]         = $cleaned_clause;

				/*
				 * Giữ bản sao của mệnh đề trong mảng phẳng
				 * $queried_terms, để sử dụng trong WP_Query.
				 */
				if ( ! empty( $cleaned_clause['taxonomy'] ) && 'NOT IN' !== $cleaned_clause['operator'] ) {
					$taxonomy = $cleaned_clause['taxonomy'];
					if ( ! isset( $this->queried_terms[ $taxonomy ] ) ) {
						$this->queried_terms[ $taxonomy ] = array();
					}

					/*
					 * Tương thích ngược: Chỉ lưu 'terms' và 'field' đầu tiên
					 * tìm thấy cho một taxonomy nhất định.
					 */
					if ( ! empty( $cleaned_clause['terms'] ) && ! isset( $this->queried_terms[ $taxonomy ]['terms'] ) ) {
						$this->queried_terms[ $taxonomy ]['terms'] = $cleaned_clause['terms'];
					}

					if ( ! empty( $cleaned_clause['field'] ) && ! isset( $this->queried_terms[ $taxonomy ]['field'] ) ) {
						$this->queried_terms[ $taxonomy ]['field'] = $cleaned_clause['field'];
					}
				}

				// Nếu không, đây là truy vấn lồng nhau, nên chúng ta đệ quy.
			} elseif ( is_array( $query ) ) {
				$cleaned_subquery = $this->sanitize_query( $query );

				if ( ! empty( $cleaned_subquery ) ) {
					// Tất cả truy vấn có con phải có quan hệ.
					if ( ! isset( $cleaned_subquery['relation'] ) ) {
						$cleaned_subquery['relation'] = 'AND';
					}

					$cleaned_query[] = $cleaned_subquery;
				}
			}
		}

		return $cleaned_query;
	}

	/**
	 * Làm sạch toán tử 'relation'.
	 *
	 * @since 4.1.0
	 *
	 * @param string $relation Khóa relation thô từ tham số truy vấn.
	 * @return string Relation đã được làm sạch. Có thể là 'AND' hoặc 'OR'.
	 */
	public function sanitize_relation( $relation ) {
		if ( 'OR' === strtoupper( $relation ) ) {
			return 'OR';
		} else {
			return 'AND';
		}
	}

	/**
	 * Xác định xem một mệnh đề có phải là bậc một hay không.
	 *
	 * Mệnh đề "bậc một" là mệnh đề chứa bất kỳ khóa nào của mệnh đề bậc một
	 * ('terms', 'taxonomy', 'include_children', 'field', 'operator').
	 * Mệnh đề rỗng cũng được tính là mệnh đề bậc một, để tương thích ngược.
	 * Bất kỳ mệnh đề nào không đáp ứng điều này được xác định, bằng cách loại trừ,
	 * là truy vấn bậc cao hơn.
	 *
	 * @since 4.1.0
	 *
	 * @param array $query Tham số truy vấn tax.
	 * @return bool Mệnh đề truy vấn có phải là mệnh đề bậc một hay không.
	 */
	protected static function is_first_order_clause( $query ) {
		return is_array( $query ) && ( empty( $query ) || array_key_exists( 'terms', $query ) || array_key_exists( 'taxonomy', $query ) || array_key_exists( 'include_children', $query ) || array_key_exists( 'field', $query ) || array_key_exists( 'operator', $query ) );
	}

	/**
	 * Tạo các mệnh đề SQL để nối vào truy vấn chính.
	 *
	 * @since 3.1.0
	 *
	 * @param string $primary_table     Bảng cơ sở dữ liệu nơi đối tượng được lọc được lưu trữ (vd: wp_users).
	 * @param string $primary_id_column Cột ID cho đối tượng được lọc trong $primary_table.
	 * @return string[] {
	 *     Mảng chứa các mệnh đề SQL JOIN và WHERE để nối vào truy vấn chính.
	 *
	 *     @type string $join  Đoạn SQL để nối vào mệnh đề JOIN chính.
	 *     @type string $where Đoạn SQL để nối vào mệnh đề WHERE chính.
	 * }
	 */
	public function get_sql( $primary_table, $primary_id_column ) {
		$this->primary_table     = $primary_table;
		$this->primary_id_column = $primary_id_column;

		return $this->get_sql_clauses();
	}

	/**
	 * Tạo các mệnh đề SQL để nối vào truy vấn chính.
	 *
	 * Được gọi bởi phương thức công khai WP_Tax_Query::get_sql(), phương thức này
	 * được tách ra để duy trì sự tương đồng với các lớp Query khác.
	 *
	 * @since 4.1.0
	 *
	 * @return string[] {
	 *     Mảng chứa các mệnh đề SQL JOIN và WHERE để nối vào truy vấn chính.
	 *
	 *     @type string $join  Đoạn SQL để nối vào mệnh đề JOIN chính.
	 *     @type string $where Đoạn SQL để nối vào mệnh đề WHERE chính.
	 * }
	 */
	protected function get_sql_clauses() {
		/*
		 * $queries được truyền bằng tham chiếu cho get_sql_for_query() để đệ quy.
		 * Để giữ $this->queries không thay đổi, truyền một bản sao.
		 */
		$queries = $this->queries;
		$sql     = $this->get_sql_for_query( $queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Tạo các mệnh đề SQL cho một mảng truy vấn đơn.
	 *
	 * Nếu tìm thấy các truy vấn con lồng nhau, phương thức này đệ quy cây
	 * để tạo SQL lồng nhau đúng cách.
	 *
	 * @since 4.1.0
	 *
	 * @param array $query Truy vấn cần phân tích (truyền bằng tham chiếu).
	 * @param int   $depth Tùy chọn. Số cấp độ cây sâu hiện tại.
	 *                     Dùng để tính thụt lề. Mặc định 0.
	 * @return string[] {
	 *     Mảng chứa các mệnh đề SQL JOIN và WHERE để nối vào một mảng truy vấn đơn.
	 *
	 *     @type string $join  Đoạn SQL để nối vào mệnh đề JOIN chính.
	 *     @type string $where Đoạn SQL để nối vào mệnh đề WHERE chính.
	 * }
	 */
	protected function get_sql_for_query( &$query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= '  ';
		}

		foreach ( $query as $key => &$clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {

				// Đây là mệnh đề bậc một.
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
					// Đây là truy vấn con, nên chúng ta đệ quy.
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Lọc để loại bỏ các phần tử rỗng.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Lọc các mệnh đề JOIN trùng lặp và kết hợp thành một chuỗi đơn.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Tạo một mệnh đề WHERE đơn với ngoặc và thụt lề đúng cách.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		return $sql;
	}

	/**
	 * Tạo các mệnh đề SQL JOIN và WHERE cho mệnh đề truy vấn "bậc một".
	 *
	 * @since 4.1.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param array $clause       Mệnh đề truy vấn (truyền bằng tham chiếu).
	 * @param array $parent_query Mảng truy vấn cha.
	 * @return array {
	 *     Mảng chứa các mệnh đề SQL JOIN và WHERE để nối vào truy vấn bậc một.
	 *
	 *     @type string[] $join  Mảng các đoạn SQL để nối vào mệnh đề JOIN chính.
	 *     @type string[] $where Mảng các đoạn SQL để nối vào mệnh đề WHERE chính.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query ) {
		global $wpdb;

		$sql = array(
			'where' => array(),
			'join'  => array(),
		);

		$join  = '';
		$where = '';

		$this->clean_query( $clause );

		if ( is_wp_error( $clause ) ) {
			return self::$no_results;
		}

		$terms    = $clause['terms'];
		$operator = strtoupper( $clause['operator'] );

		if ( 'IN' === $operator ) {

			if ( empty( $terms ) ) {
				return self::$no_results;
			}

			$terms = implode( ',', $terms );

			/*
			 * Trước khi tạo một join bảng khác, kiểm tra xem mệnh đề này có
			 * mệnh đề anh em với join hiện có có thể chia sẻ không.
			 */
			$alias = $this->find_compatible_table_alias( $clause, $parent_query );
			if ( false === $alias ) {
				$i     = count( $this->table_aliases );
				$alias = $i ? 'tt' . $i : $wpdb->term_relationships;

				// Lưu bí danh như phần của mảng phẳng để xây dựng các bộ lặp trong tương lai.
				$this->table_aliases[] = $alias;

				// Lưu bí danh với mệnh đề này, để các mệnh đề anh em sau có thể sử dụng.
				$clause['alias'] = $alias;

				$join .= " LEFT JOIN $wpdb->term_relationships";
				$join .= $i ? " AS $alias" : '';
				$join .= " ON ($this->primary_table.$this->primary_id_column = $alias.object_id)";
			}

			$where = "$alias.term_taxonomy_id $operator ($terms)";

		} elseif ( 'NOT IN' === $operator ) {

			if ( empty( $terms ) ) {
				return $sql;
			}

			$terms = implode( ',', $terms );

			$where = "$this->primary_table.$this->primary_id_column NOT IN (
				SELECT object_id
				FROM $wpdb->term_relationships
				WHERE term_taxonomy_id IN ($terms)
			)";

		} elseif ( 'AND' === $operator ) {

			if ( empty( $terms ) ) {
				return $sql;
			}

			$num_terms = count( $terms );

			$terms = implode( ',', $terms );

			$where = "(
				SELECT COUNT(1)
				FROM $wpdb->term_relationships
				WHERE term_taxonomy_id IN ($terms)
				AND object_id = $this->primary_table.$this->primary_id_column
			) = $num_terms";

		} elseif ( 'NOT EXISTS' === $operator || 'EXISTS' === $operator ) {

			$where = $wpdb->prepare(
				"$operator (
					SELECT 1
					FROM $wpdb->term_relationships
					INNER JOIN $wpdb->term_taxonomy
					ON $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id
					WHERE $wpdb->term_taxonomy.taxonomy = %s
					AND $wpdb->term_relationships.object_id = $this->primary_table.$this->primary_id_column
				)",
				$clause['taxonomy']
			);

		}

		$sql['join'][]  = $join;
		$sql['where'][] = $where;
		return $sql;
	}

	/**
	 * Xác định bí danh bảng hiện có tương thích với mệnh đề truy vấn hiện tại.
	 *
	 * Chúng ta tránh các join bảng không cần thiết bằng cách cho phép mỗi mệnh đề
	 * tìm kiếm bí danh bảng hiện có tương thích với truy vấn mà nó cần thực hiện.
	 *
	 * Bí danh hiện có tương thích nếu (a) nó là anh em của `$clause`
	 * (tức là nằm trong phạm vi của cùng một relation), và (b) sự kết hợp
	 * của toán tử và relation giữa các mệnh đề cho phép chia sẻ join bảng.
	 * Trong trường hợp WP_Tax_Query, điều này chỉ áp dụng cho các mệnh đề
	 * 'IN' được kết nối bởi relation 'OR'.
	 *
	 * @since 4.1.0
	 *
	 * @param array $clause       Mệnh đề truy vấn.
	 * @param array $parent_query Truy vấn cha của $clause.
	 * @return string|false Bí danh bảng nếu tìm thấy, ngược lại false.
	 */
	protected function find_compatible_table_alias( $clause, $parent_query ) {
		$alias = false;

		// Kiểm tra xác nhận. Chỉ các truy vấn IN sử dụng cú pháp JOIN.
		if ( ! isset( $clause['operator'] ) || 'IN' !== $clause['operator'] ) {
			return $alias;
		}

		// Vì chúng ta chỉ kiểm tra truy vấn IN, nên chỉ quan tâm đến relation OR.
		if ( ! isset( $parent_query['relation'] ) || 'OR' !== $parent_query['relation'] ) {
			return $alias;
		}

		$compatible_operators = array( 'IN' );

		foreach ( $parent_query as $sibling ) {
			if ( ! is_array( $sibling ) || ! $this->is_first_order_clause( $sibling ) ) {
				continue;
			}

			if ( empty( $sibling['alias'] ) || empty( $sibling['operator'] ) ) {
				continue;
			}

			// Mệnh đề anh em phải có toán tử tương thích để chia sẻ bí danh.
			if ( in_array( strtoupper( $sibling['operator'] ), $compatible_operators, true ) ) {
				$alias = preg_replace( '/\W/', '_', $sibling['alias'] );
				break;
			}
		}

		return $alias;
	}

	/**
	 * Xác thực một truy vấn đơn.
	 *
	 * @since 3.2.0
	 *
	 * @param array $query Truy vấn đơn. Truyền bằng tham chiếu.
	 */
	private function clean_query( &$query ) {
		if ( empty( $query['taxonomy'] ) ) {
			if ( 'term_taxonomy_id' !== $query['field'] ) {
				$query = new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
				return;
			}

			// Miễn là có các term được chia sẻ, 'include_children' yêu cầu phải đặt taxonomy.
			$query['include_children'] = false;
		} elseif ( ! taxonomy_exists( $query['taxonomy'] ) ) {
			$query = new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
			return;
		}

		if ( 'slug' === $query['field'] || 'name' === $query['field'] ) {
			$query['terms'] = array_unique( (array) $query['terms'] );
		} else {
			$query['terms'] = wp_parse_id_list( $query['terms'] );
		}

		if ( is_taxonomy_hierarchical( $query['taxonomy'] ) && $query['include_children'] ) {
			$this->transform_query( $query, 'term_id' );

			if ( is_wp_error( $query ) ) {
				return;
			}

			$children = array();
			foreach ( $query['terms'] as $term ) {
				$children   = array_merge( $children, get_term_children( $term, $query['taxonomy'] ) );
				$children[] = $term;
			}
			$query['terms'] = $children;
		}

		$this->transform_query( $query, 'term_taxonomy_id' );
	}

	/**
	 * Chuyển đổi một truy vấn đơn, từ trường này sang trường khác.
	 *
	 * Thao tác trên đối tượng `$query` bằng tham chiếu. Trong trường hợp lỗi,
	 * `$query` được chuyển đổi thành đối tượng WP_Error.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $query           Truy vấn đơn. Truyền bằng tham chiếu.
	 * @param string $resulting_field Trường kết quả. Chấp nhận 'slug', 'name', 'term_taxonomy_id',
	 *                                hoặc 'term_id'. Mặc định 'term_id'.
	 */
	public function transform_query( &$query, $resulting_field ) {
		if ( empty( $query['terms'] ) ) {
			return;
		}

		if ( $query['field'] === $resulting_field ) {
			return;
		}

		$resulting_field = sanitize_key( $resulting_field );

		// 'terms' rỗng luôn dẫn đến chuyển đổi null.
		$terms = array_filter( $query['terms'] );
		if ( empty( $terms ) ) {
			$query['terms'] = array();
			$query['field'] = $resulting_field;
			return;
		}

		$args = array(
			'get'                    => 'all',
			'number'                 => 0,
			'taxonomy'               => $query['taxonomy'],
			'update_term_meta_cache' => false,
			'orderby'                => 'none',
		);

		// Tên tham số truy vấn term phụ thuộc vào 'field' đang được tìm kiếm.
		switch ( $query['field'] ) {
			case 'slug':
				$args['slug'] = $terms;
				break;
			case 'name':
				$args['name'] = $terms;
				break;
			case 'term_taxonomy_id':
				$args['term_taxonomy_id'] = $terms;
				break;
			default:
				$args['include'] = wp_parse_id_list( $terms );
				break;
		}

		if ( ! is_taxonomy_hierarchical( $query['taxonomy'] ) ) {
			$args['number'] = count( $terms );
		}

		$term_query = new WP_Term_Query();
		$term_list  = $term_query->query( $args );

		if ( is_wp_error( $term_list ) ) {
			$query = $term_list;
			return;
		}

		if ( 'AND' === $query['operator'] && count( $term_list ) < count( $query['terms'] ) ) {
			$query = new WP_Error( 'inexistent_terms', __( 'Inexistent terms.' ) );
			return;
		}

		$query['terms'] = wp_list_pluck( $term_list, $resulting_field );
		$query['field'] = $resulting_field;
	}
}
