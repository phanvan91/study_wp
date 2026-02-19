<?php
/**
 * REST API: Lớp WP_REST_Meta_Fields
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Lớp cốt lõi để quản lý giá trị meta cho đối tượng thông qua REST API.
 *
 * @since 4.7.0
 */
#[AllowDynamicProperties]
abstract class WP_REST_Meta_Fields {

	/**
	 * Lấy loại meta của đối tượng.
	 *
	 * @since 4.7.0
	 *
	 * @return string Một trong 'post', 'comment', 'term', 'user', hoặc bất kỳ
	 *                loại nào khác được hỗ trợ bởi `_get_meta_table()`.
	 */
	abstract protected function get_meta_type();

	/**
	 * Lấy loại phụ meta của đối tượng.
	 *
	 * @since 4.9.8
	 *
	 * @return string Loại phụ cho loại meta, hoặc chuỗi rỗng nếu không có loại phụ cụ thể.
	 */
	protected function get_meta_subtype() {
		return '';
	}

	/**
	 * Lấy loại đối tượng cho register_rest_field().
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại trường REST, ví dụ như tên loại bài viết, tên taxonomy, 'comment', hoặc 'user'.
	 */
	abstract protected function get_rest_field_type();

	/**
	 * Đăng ký trường meta.
	 *
	 * @since 4.7.0
	 * @deprecated 5.6.0
	 *
	 * @see register_rest_field()
	 */
	public function register_field() {
		_deprecated_function( __METHOD__, '5.6.0' );

		register_rest_field(
			$this->get_rest_field_type(),
			'meta',
			array(
				'get_callback'    => array( $this, 'get_value' ),
				'update_callback' => array( $this, 'update_value' ),
				'schema'          => $this->get_field_schema(),
			)
		);
	}

	/**
	 * Lấy giá trị trường meta.
	 *
	 * @since 4.7.0
	 *
	 * @param int             $object_id ID đối tượng để lấy meta.
	 * @param WP_REST_Request $request   Chi tiết đầy đủ về request.
	 * @return array Mảng chứa các giá trị meta được đánh khóa theo tên.
	 */
	public function get_value( $object_id, $request ) {
		$fields   = $this->get_registered_fields();
		$response = array();

		foreach ( $fields as $meta_key => $args ) {
			$name       = $args['name'];
			$all_values = get_metadata( $this->get_meta_type(), $object_id, $meta_key, false );

			if ( $args['single'] ) {
				if ( empty( $all_values ) ) {
					$value = $args['schema']['default'];
				} else {
					$value = $all_values[0];
				}

				$value = $this->prepare_value_for_response( $value, $request, $args );
			} else {
				$value = array();

				if ( is_array( $all_values ) ) {
					foreach ( $all_values as $row ) {
						$value[] = $this->prepare_value_for_response( $row, $request, $args );
					}
				}
			}

			$response[ $name ] = $value;
		}

		return $response;
	}

	/**
	 * Chuẩn bị giá trị meta cho phản hồi.
	 *
	 * Điều này là cần thiết vì một số kiểu dữ liệu gốc không thể lưu trữ
	 * chính xác trong cơ sở dữ liệu, chẳng hạn như boolean. Chúng ta cần ép kiểu
	 * về loại phù hợp trước khi truyền lại cho JSON.
	 *
	 * @since 4.7.0
	 *
	 * @param mixed           $value   Giá trị meta cần chuẩn bị.
	 * @param WP_REST_Request $request Đối tượng request hiện tại.
	 * @param array           $args    Các tùy chọn cho trường.
	 * @return mixed Giá trị đã được chuẩn bị.
	 */
	protected function prepare_value_for_response( $value, $request, $args ) {
		if ( ! empty( $args['prepare_callback'] ) ) {
			$value = call_user_func( $args['prepare_callback'], $value, $request, $args );
		}

		return $value;
	}

	/**
	 * Cập nhật các giá trị meta.
	 *
	 * @since 4.7.0
	 *
	 * @param array $meta      Mảng meta được phân tích từ request.
	 * @param int   $object_id ID đối tượng để lấy meta.
	 * @return null|WP_Error Null nếu thành công, đối tượng WP_Error nếu thất bại.
	 */
	public function update_value( $meta, $object_id ) {
		$fields = $this->get_registered_fields();
		$error  = new WP_Error();

		foreach ( $fields as $meta_key => $args ) {
			$name = $args['name'];
			if ( ! array_key_exists( $name, $meta ) ) {
				continue;
			}

			$value = $meta[ $name ];

			/*
			 * Giá trị null có nghĩa là đặt lại trường, về cơ bản là xóa nó
			 * khỏi cơ sở dữ liệu và sau đó dựa vào giá trị mặc định.
			 *
			 * Meta không đơn cũng có thể được xóa bằng cách truyền mảng rỗng.
			 */
			if ( is_null( $value ) || ( array() === $value && ! $args['single'] ) ) {
				$args = $this->get_registered_fields()[ $meta_key ];

				if ( $args['single'] ) {
					$current = get_metadata( $this->get_meta_type(), $object_id, $meta_key, true );

					if ( is_wp_error( rest_validate_value_from_schema( $current, $args['schema'] ) ) ) {
						$error->add(
							'rest_invalid_stored_value',
							/* translators: %s: Custom field key. */
							sprintf( __( 'The %s property has an invalid stored value, and cannot be updated to null.' ), $name ),
							array( 'status' => 500 )
						);
						continue;
					}
				}

				$result = $this->delete_meta_value( $object_id, $meta_key, $name );
				if ( is_wp_error( $result ) ) {
					$error->merge_from( $result );
				}
				continue;
			}

			if ( ! $args['single'] && is_array( $value ) && count( array_filter( $value, 'is_null' ) ) ) {
				$error->add(
					'rest_invalid_stored_value',
					/* translators: %s: Custom field key. */
					sprintf( __( 'The %s property has an invalid stored value, and cannot be updated to null.' ), $name ),
					array( 'status' => 500 )
				);
				continue;
			}

			$is_valid = rest_validate_value_from_schema( $value, $args['schema'], 'meta.' . $name );
			if ( is_wp_error( $is_valid ) ) {
				$is_valid->add_data( array( 'status' => 400 ) );
				$error->merge_from( $is_valid );
				continue;
			}

			$value = rest_sanitize_value_from_schema( $value, $args['schema'] );

			if ( $args['single'] ) {
				$result = $this->update_meta_value( $object_id, $meta_key, $name, $value );
			} else {
				$result = $this->update_multi_meta_value( $object_id, $meta_key, $name, $value );
			}

			if ( is_wp_error( $result ) ) {
				$error->merge_from( $result );
				continue;
			}
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		return null;
	}

	/**
	 * Xóa giá trị meta cho một đối tượng.
	 *
	 * @since 4.7.0
	 *
	 * @param int    $object_id ID đối tượng mà trường thuộc về.
	 * @param string $meta_key  Khóa cho trường.
	 * @param string $name      Tên cho trường được hiển thị trong REST API.
	 * @return true|WP_Error True nếu trường meta bị xóa, WP_Error nếu ngược lại.
	 */
	protected function delete_meta_value( $object_id, $meta_key, $name ) {
		$meta_type = $this->get_meta_type();

		if ( ! current_user_can( "delete_{$meta_type}_meta", $object_id, $meta_key ) ) {
			return new WP_Error(
				'rest_cannot_delete',
				/* translators: %s: Custom field key. */
				sprintf( __( 'Sorry, you are not allowed to edit the %s custom field.' ), $name ),
				array(
					'key'    => $name,
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( null === get_metadata_raw( $meta_type, $object_id, wp_slash( $meta_key ) ) ) {
			return true;
		}

		if ( ! delete_metadata( $meta_type, $object_id, wp_slash( $meta_key ) ) ) {
			return new WP_Error(
				'rest_meta_database_error',
				__( 'Could not delete meta value from database.' ),
				array(
					'key'    => $name,
					'status' => WP_Http::INTERNAL_SERVER_ERROR,
				)
			);
		}

		return true;
	}

	/**
	 * Cập nhật nhiều giá trị meta cho một đối tượng.
	 *
	 * Thay đổi danh sách giá trị trong cơ sở dữ liệu để khớp với danh sách giá trị được cung cấp.
	 *
	 * @since 4.7.0
	 * @since 6.7.0 Lưu giá trị vào DB ngay cả khi cung cấp giá trị mặc định đã đăng ký.
	 *
	 * @param int    $object_id ID đối tượng cần cập nhật.
	 * @param string $meta_key  Khóa cho trường tùy chỉnh.
	 * @param string $name      Tên cho trường được hiển thị trong REST API.
	 * @param array  $values    Danh sách giá trị cần cập nhật.
	 * @return true|WP_Error True nếu các trường meta được cập nhật, WP_Error nếu ngược lại.
	 */
	protected function update_multi_meta_value( $object_id, $meta_key, $name, $values ) {
		$meta_type = $this->get_meta_type();

		if ( ! current_user_can( "edit_{$meta_type}_meta", $object_id, $meta_key ) ) {
			return new WP_Error(
				'rest_cannot_update',
				/* translators: %s: Custom field key. */
				sprintf( __( 'Sorry, you are not allowed to edit the %s custom field.' ), $name ),
				array(
					'key'    => $name,
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$current_values = get_metadata_raw( $meta_type, $object_id, $meta_key, false );
		$subtype        = get_object_subtype( $meta_type, $object_id );

		if ( ! is_array( $current_values ) ) {
			$current_values = array();
		}

		$to_remove = $current_values;
		$to_add    = $values;

		foreach ( $to_add as $add_key => $value ) {
			$remove_keys = array_keys(
				array_filter(
					$current_values,
					function ( $stored_value ) use ( $meta_key, $subtype, $value ) {
						return $this->is_meta_value_same_as_stored_value( $meta_key, $subtype, $stored_value, $value );
					}
				)
			);

			if ( empty( $remove_keys ) ) {
				continue;
			}

			if ( count( $remove_keys ) > 1 ) {
				// Để xóa, chúng ta cần xóa trước rồi thêm sau, nên không chạm vào.
				continue;
			}

			$remove_key = $remove_keys[0];

			unset( $to_remove[ $remove_key ] );
			unset( $to_add[ $add_key ] );
		}

		/*
		 * `delete_metadata` xóa _tất cả_ các instance của giá trị, nên chỉ gọi một lần. Nếu không,
		 * `delete_metadata` sẽ trả về false cho các lần gọi tiếp theo với cùng giá trị.
		 * Sử dụng serialize để tạo chuỗi dự đoán được mà array_unique có thể sử dụng.
		 */
		$to_remove = array_map( 'maybe_unserialize', array_unique( array_map( 'maybe_serialize', $to_remove ) ) );

		foreach ( $to_remove as $value ) {
			if ( ! delete_metadata( $meta_type, $object_id, wp_slash( $meta_key ), wp_slash( $value ) ) ) {
				return new WP_Error(
					'rest_meta_database_error',
					/* translators: %s: Custom field key. */
					sprintf( __( 'Could not update the meta value of %s in database.' ), $meta_key ),
					array(
						'key'    => $name,
						'status' => WP_Http::INTERNAL_SERVER_ERROR,
					)
				);
			}
		}

		foreach ( $to_add as $value ) {
			if ( ! add_metadata( $meta_type, $object_id, wp_slash( $meta_key ), wp_slash( $value ) ) ) {
				return new WP_Error(
					'rest_meta_database_error',
					/* translators: %s: Custom field key. */
					sprintf( __( 'Could not update the meta value of %s in database.' ), $meta_key ),
					array(
						'key'    => $name,
						'status' => WP_Http::INTERNAL_SERVER_ERROR,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Cập nhật giá trị meta cho một đối tượng.
	 *
	 * @since 4.7.0
	 * @since 6.7.0 Lưu giá trị vào DB ngay cả khi cung cấp giá trị mặc định đã đăng ký.
	 *
	 * @param int    $object_id ID đối tượng cần cập nhật.
	 * @param string $meta_key  Khóa cho trường tùy chỉnh.
	 * @param string $name      Tên cho trường được hiển thị trong REST API.
	 * @param mixed  $value     Giá trị đã cập nhật.
	 * @return true|WP_Error True nếu trường meta được cập nhật, WP_Error nếu ngược lại.
	 */
	protected function update_meta_value( $object_id, $meta_key, $name, $value ) {
		$meta_type = $this->get_meta_type();

		// Thực hiện kiểm tra giá trị trùng lặp giống hệt như trong update_metadata() để tránh update_metadata() trả về false.
		$old_value = get_metadata_raw( $meta_type, $object_id, $meta_key );
		$subtype   = get_object_subtype( $meta_type, $object_id );

		if ( is_array( $old_value ) && 1 === count( $old_value )
			&& $this->is_meta_value_same_as_stored_value( $meta_key, $subtype, $old_value[0], $value )
		) {
			return true;
		}

		if ( ! current_user_can( "edit_{$meta_type}_meta", $object_id, $meta_key ) ) {
			return new WP_Error(
				'rest_cannot_update',
				/* translators: %s: Custom field key. */
				sprintf( __( 'Sorry, you are not allowed to edit the %s custom field.' ), $name ),
				array(
					'key'    => $name,
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! update_metadata( $meta_type, $object_id, wp_slash( $meta_key ), wp_slash( $value ) ) ) {
			return new WP_Error(
				'rest_meta_database_error',
				/* translators: %s: Custom field key. */
				sprintf( __( 'Could not update the meta value of %s in database.' ), $meta_key ),
				array(
					'key'    => $name,
					'status' => WP_Http::INTERNAL_SERVER_ERROR,
				)
			);
		}

		return true;
	}

	/**
	 * Kiểm tra xem giá trị người dùng cung cấp có tương đương với giá trị đã lưu cho khóa meta đã cho không.
	 *
	 * @since 5.5.0
	 *
	 * @param string $meta_key     Khóa meta đang được kiểm tra.
	 * @param string $subtype      Loại phụ đối tượng.
	 * @param mixed  $stored_value Giá trị hiện đang lưu trữ được lấy từ get_metadata().
	 * @param mixed  $user_value   Giá trị do người dùng cung cấp.
	 * @return bool
	 */
	protected function is_meta_value_same_as_stored_value( $meta_key, $subtype, $stored_value, $user_value ) {
		$args      = $this->get_registered_fields()[ $meta_key ];
		$sanitized = sanitize_meta( $meta_key, $user_value, $this->get_meta_type(), $subtype );

		if ( in_array( $args['type'], array( 'string', 'number', 'integer', 'boolean' ), true ) ) {
			// Giá trị trả về của get_metadata luôn là chuỗi cho các kiểu vô hướng.
			$sanitized = (string) $sanitized;
		}

		return $sanitized === $stored_value;
	}

	/**
	 * Lấy tất cả các trường meta đã đăng ký.
	 *
	 * @since 4.7.0
	 *
	 * @return array Các trường đã đăng ký.
	 */
	protected function get_registered_fields() {
		$registered = array();

		$meta_type    = $this->get_meta_type();
		$meta_subtype = $this->get_meta_subtype();

		$meta_keys = get_registered_meta_keys( $meta_type );
		if ( ! empty( $meta_subtype ) ) {
			$meta_keys = array_merge( $meta_keys, get_registered_meta_keys( $meta_type, $meta_subtype ) );
		}

		foreach ( $meta_keys as $name => $args ) {
			if ( empty( $args['show_in_rest'] ) ) {
				continue;
			}

			$rest_args = array();

			if ( is_array( $args['show_in_rest'] ) ) {
				$rest_args = $args['show_in_rest'];
			}

			$default_args = array(
				'name'             => $name,
				'single'           => $args['single'],
				'type'             => ! empty( $args['type'] ) ? $args['type'] : null,
				'schema'           => array(),
				'prepare_callback' => array( $this, 'prepare_value' ),
			);

			$default_schema = array(
				'type'        => $default_args['type'],
				'title'       => empty( $args['label'] ) ? '' : $args['label'],
				'description' => empty( $args['description'] ) ? '' : $args['description'],
				'default'     => isset( $args['default'] ) ? $args['default'] : null,
			);

			$rest_args           = array_merge( $default_args, $rest_args );
			$rest_args['schema'] = array_merge( $default_schema, $rest_args['schema'] );

			$type = ! empty( $rest_args['type'] ) ? $rest_args['type'] : null;
			$type = ! empty( $rest_args['schema']['type'] ) ? $rest_args['schema']['type'] : $type;

			if ( null === $rest_args['schema']['default'] ) {
				$rest_args['schema']['default'] = static::get_empty_value_for_type( $type );
			}

			$rest_args['schema'] = rest_default_additional_properties_to_false( $rest_args['schema'] );

			if ( ! in_array( $type, array( 'string', 'boolean', 'integer', 'number', 'array', 'object' ), true ) ) {
				continue;
			}

			if ( empty( $rest_args['single'] ) ) {
				$rest_args['schema'] = array(
					'type'  => 'array',
					'items' => $rest_args['schema'],
				);
			}

			$registered[ $name ] = $rest_args;
		}

		return $registered;
	}

	/**
	 * Lấy schema meta của đối tượng, tuân theo JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Dữ liệu schema trường.
	 */
	public function get_field_schema() {
		$fields = $this->get_registered_fields();

		$schema = array(
			'description' => __( 'Meta fields.' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(),
			'arg_options' => array(
				'sanitize_callback' => null,
				'validate_callback' => array( $this, 'check_meta_is_array' ),
			),
		);

		foreach ( $fields as $args ) {
			$schema['properties'][ $args['name'] ] = $args['schema'];
		}

		return $schema;
	}

	/**
	 * Chuẩn bị giá trị meta cho đầu ra.
	 *
	 * Chuẩn bị mặc định cho các trường meta. Ghi đè bằng cách truyền
	 * `prepare_callback` trong tùy chọn `show_in_rest` của bạn.
	 *
	 * @since 4.7.0
	 *
	 * @param mixed           $value   Giá trị meta từ cơ sở dữ liệu.
	 * @param WP_REST_Request $request Đối tượng request.
	 * @param array           $args    Các tùy chọn dành riêng cho REST của khóa meta.
	 * @return mixed Giá trị đã chuẩn bị cho đầu ra. Nếu là đối tượng không JsonSerializable, trả về null.
	 */
	public static function prepare_value( $value, $request, $args ) {
		if ( $args['single'] ) {
			$schema = $args['schema'];
		} else {
			$schema = $args['schema']['items'];
		}

		if ( '' === $value && in_array( $schema['type'], array( 'boolean', 'integer', 'number' ), true ) ) {
			$value = static::get_empty_value_for_type( $schema['type'] );
		}

		if ( is_wp_error( rest_validate_value_from_schema( $value, $schema ) ) ) {
			return null;
		}

		return rest_sanitize_value_from_schema( $value, $schema );
	}

	/**
	 * Kiểm tra giá trị 'meta' của request có phải là mảng kết hợp không.
	 *
	 * @since 4.7.0
	 *
	 * @param mixed           $value   Giá trị meta được gửi trong request.
	 * @param WP_REST_Request $request Chi tiết đầy đủ về request.
	 * @param string          $param   Tên tham số.
	 * @return array|false Mảng meta nếu hợp lệ, false nếu không.
	 */
	public function check_meta_is_array( $value, $request, $param ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Đệ quy thêm additionalProperties = false cho tất cả các object trong schema nếu không có
	 * cài đặt additionalProperties nào được chỉ định.
	 *
	 * Điều này cần thiết để giới hạn thuộc tính của object trong giá trị meta chỉ
	 * cho các mục đã đăng ký, vì REST API sẽ cho phép thuộc tính bổ sung
	 * theo mặc định.
	 *
	 * @since 5.3.0
	 * @deprecated 5.6.0 Sử dụng rest_default_additional_properties_to_false() thay thế.
	 *
	 * @param array $schema Mảng schema.
	 * @return array
	 */
	protected function default_additional_properties_to_false( $schema ) {
		_deprecated_function( __METHOD__, '5.6.0', 'rest_default_additional_properties_to_false()' );

		return rest_default_additional_properties_to_false( $schema );
	}

	/**
	 * Lấy giá trị rỗng cho một kiểu schema.
	 *
	 * @since 5.3.0
	 *
	 * @param string $type Kiểu schema.
	 * @return mixed
	 */
	protected static function get_empty_value_for_type( $type ) {
		switch ( $type ) {
			case 'string':
				return '';
			case 'boolean':
				return false;
			case 'integer':
				return 0;
			case 'number':
				return 0.0;
			case 'array':
			case 'object':
				return array();
			default:
				return null;
		}
	}
}
