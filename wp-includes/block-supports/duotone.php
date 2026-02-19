<?php
/**
 * Cờ hỗ trợ duotone cho block.
 *
 * Một phần mã nguồn này được kế thừa và chỉnh sửa từ TinyColor,
 * phát hành theo giấy phép MIT.
 *
 * https://github.com/bgrins/TinyColor
 *
 * Copyright (c), Brian Grinstead, http://briangrinstead.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package WordPress
 * @since 5.8.0
 */

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'duotone',
	array(
		'register_attribute' => array( 'WP_Duotone', 'register_duotone_support' ),
	)
);

// Thêm tên lớp CSS cho các block sử dụng hỗ trợ duotone.
add_filter( 'render_block', array( 'WP_Duotone', 'render_duotone_support' ), 10, 3 );
add_filter( 'render_block_core/image', array( 'WP_Duotone', 'restore_image_outer_container' ), 10, 1 );

// Nạp các kiểu CSS.
// Kiểu block (core-block-supports-inline-css) trước engine kiểu (wp_enqueue_stored_styles).
// Kiểu toàn cục (global-styles-inline-css) sau các kiểu toàn cục khác (wp_enqueue_global_styles).
add_action( 'wp_enqueue_scripts', array( 'WP_Duotone', 'output_block_styles' ), 9 );
add_action( 'wp_enqueue_scripts', array( 'WP_Duotone', 'output_global_styles' ), 11 );

// Thêm bộ lọc SVG vào footer. Ngoài ra, đối với theme cổ điển, xuất kiểu block (core-block-supports-inline-css).
add_action( 'wp_footer', array( 'WP_Duotone', 'output_footer_assets' ), 10 );

// Thêm kiểu CSS và SVG để sử dụng trong trình soạn thảo qua component EditorStyles.
add_filter( 'block_editor_settings_all', array( 'WP_Duotone', 'add_editor_settings' ), 10 );

// Di chuyển cờ hỗ trợ duotone thử nghiệm cũ.
add_filter( 'block_type_metadata_settings', array( 'WP_Duotone', 'migrate_experimental_duotone_support_flag' ), 10, 2 );
