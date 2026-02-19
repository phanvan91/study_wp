<?php
/**
 * API Block Bindings
 *
 * Chứa các hàm để quản lý block bindings trong WordPress.
 *
 * @package WordPress
 * @subpackage Block Bindings
 * @since 6.5.0
 */

/**
 * Đăng ký một nguồn block bindings mới.
 *
 * Việc đăng ký một nguồn bao gồm việc định nghĩa một **tên** cho nguồn đó và một hàm callback
 * chỉ định cách lấy giá trị từ nguồn đó và truyền nó cho thuộc tính block.
 *
 * Khi nguồn đã được đăng ký, bất kỳ block nào hỗ trợ API Block Bindings đều có thể sử dụng giá trị
 * từ nguồn đó bằng cách thiết lập thuộc tính `metadata.bindings` của nó tham chiếu đến nguồn.
 *
 * Lưu ý rằng `register_block_bindings_source()` nên được gọi từ handler gắn vào hook `init`.
 *
 *
 * ## Ví dụ
 *
 * ### Đăng ký một nguồn
 *
 * Đầu tiên, bạn cần định nghĩa một hàm sẽ được sử dụng để lấy giá trị từ nguồn.
 *
 *     function my_plugin_get_custom_source_value( array $source_args, $block_instance, string $attribute_name ) {
 *       // Logic tùy chỉnh của bạn để lấy giá trị từ nguồn.
 *       // Ví dụ, bạn có thể sử dụng `$source_args` để tra cứu giá trị trong bảng tùy chỉnh hoặc lấy từ API bên ngoài.
 *       $value = $source_args['key'];
 *
 *       return "Giá trị được truyền cho block là: $value"
 *     }
 *
 * `$source_args` sẽ chứa các đối số được truyền cho nguồn trong thuộc tính
 * `metadata.bindings` của block. Xem ví dụ trong phần "Sử dụng trong block" bên dưới.
 *
 *     function my_plugin_register_block_bindings_sources() {
 *       register_block_bindings_source( 'my-plugin/my-custom-source', array(
 *         'label'              => __( 'My Custom Source', 'my-plugin' ),
 *         'get_value_callback' => 'my_plugin_get_custom_source_value',
 *       ) );
 *     }
 *     add_action( 'init', 'my_plugin_register_block_bindings_sources' );
 *
 * ### Sử dụng trong block
 *
 * Trong thuộc tính `metadata.bindings` của block, bạn có thể chỉ định nguồn và
 * các đối số của nó. Block như vậy sẽ sử dụng nguồn để ghi đè giá trị thuộc tính
 * của block. Ví dụ:
 *
 *     <!-- wp:paragraph {
 *       "metadata": {
 *         "bindings": {
 *           "content": {
 *             "source": "my-plugin/my-custom-source",
 *             "args": {
 *               "key": "you can pass any custom arguments here"
 *             }
 *           }
 *         }
 *       }
 *     } -->
 *     <p>Fallback text that gets replaced.</p>
 *     <!-- /wp:paragraph -->
 *
 * @since 6.5.0
 *
 * @param string $source_name       Tên của nguồn. Phải là chuỗi chứa tiền tố namespace, ví dụ
 *                                  `my-plugin/my-custom-source`. Chỉ chứa ký tự chữ thường, số,
 *                                  dấu gạch chéo `/` và dấu gạch ngang.
 * @param array  $source_properties {
 *     Mảng các đối số được sử dụng để đăng ký nguồn.
 *
 *     @type string   $label              Nhãn của nguồn.
 *     @type callable $get_value_callback Callback được thực thi khi nguồn được xử lý trong quá trình render block.
 *                                        Callback nên có chữ ký sau:
 *
 *                                        `function( $source_args, $block_instance, $attribute_name ): mixed`
 *                                            - @param array    $source_args    Mảng chứa đối số nguồn
 *                                                                              dùng để tra cứu giá trị ghi đè,
 *                                                                              ví dụ {"key": "foo"}.
 *                                            - @param WP_Block $block_instance Instance của block.
 *                                            - @param string   $attribute_name Tên của thuộc tính.
 *                                        Callback có kiểu trả về hỗn hợp; có thể trả về chuỗi để ghi đè
 *                                        giá trị gốc của block, null, false để xóa thuộc tính, v.v.
 *     @type string[] $uses_context       Tùy chọn. Mảng các giá trị để thêm vào `uses_context` của block cần thiết cho nguồn.
 * }
 * @return WP_Block_Bindings_Source|false Nguồn khi đăng ký thành công, hoặc `false` khi thất bại.
 */
function register_block_bindings_source( string $source_name, array $source_properties ) {
	return WP_Block_Bindings_Registry::get_instance()->register( $source_name, $source_properties );
}

/**
 * Hủy đăng ký một nguồn block bindings.
 *
 * @since 6.5.0
 *
 * @param string $source_name Tên nguồn block bindings bao gồm namespace.
 * @return WP_Block_Bindings_Source|false Nguồn block bindings đã hủy đăng ký khi thành công và `false` trong trường hợp khác.
 */
function unregister_block_bindings_source( string $source_name ) {
	return WP_Block_Bindings_Registry::get_instance()->unregister( $source_name );
}

/**
 * Lấy danh sách tất cả các nguồn block bindings đã đăng ký.
 *
 * @since 6.5.0
 *
 * @return WP_Block_Bindings_Source[] Mảng các nguồn block bindings đã đăng ký.
 */
function get_all_registered_block_bindings_sources() {
	return WP_Block_Bindings_Registry::get_instance()->get_all_registered();
}

/**
 * Lấy một nguồn block bindings đã đăng ký.
 *
 * @since 6.5.0
 *
 * @param string $source_name Tên của nguồn.
 * @return WP_Block_Bindings_Source|null Nguồn block bindings đã đăng ký, hoặc `null` nếu chưa được đăng ký.
 */
function get_block_bindings_source( string $source_name ) {
	return WP_Block_Bindings_Registry::get_instance()->get_registered( $source_name );
}
