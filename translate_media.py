#!/usr/bin/env python3
"""Translate English comments in media.php to Vietnamese."""

import re

with open('/home/phanvan91/projects/wordpress/wp-includes/media.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Dictionary of English -> Vietnamese translations for comments
# Each entry is (old_text, new_text)
replacements = [
    # Line ~788
    ('// Find the best match when \'$size\' is an array.', '// Tìm kết quả khớp tốt nhất khi \'$size\' là mảng.'),
    ('// If there\'s an exact match to an existing image size, short circuit.', '// Nếu có kết quả khớp chính xác với kích thước hình ảnh hiện có, bỏ qua.'),
    ('// If it\'s not an exact match, consider larger sizes with the same aspect ratio.', '// Nếu không khớp chính xác, xem xét các kích thước lớn hơn với cùng tỷ lệ khung hình.'),
    ('// If \'0\' is passed to either size, we test ratios against the original file.', '// Nếu \'0\' được truyền cho bất kỳ kích thước nào, chúng ta kiểm tra tỷ lệ so với tệp gốc.'),
    ('// Sort the array by size if we have more than one candidate.', '// Sắp xếp mảng theo kích thước nếu có nhiều hơn một ứng viên.'),
    ('/*\n\t\t\t* When the size requested is smaller than the thumbnail dimensions, we\n\t\t\t* fall back to the thumbnail size to maintain backward compatibility with\n\t\t\t* pre 4.6 versions of WordPress.\n\t\t\t*/',
     '/*\n\t\t\t* Khi kích thước yêu cầu nhỏ hơn kích thước ảnh thu nhỏ, chúng ta\n\t\t\t* quay lại dùng kích thước ảnh thu nhỏ để duy trì tương thích ngược với\n\t\t\t* các phiên bản WordPress trước 4.6.\n\t\t\t*/'),
    ('// Constrain the width and height attributes to the requested values.', '// Ràng buộc thuộc tính chiều rộng và chiều cao theo giá trị yêu cầu.'),
    ('// If we still don\'t have a match at this point, return false.', '// Nếu tại thời điểm này vẫn không có kết quả khớp, trả về false.'),
    ('// Include the full filesystem path of the intermediate file.', '// Bao gồm đường dẫn hệ thống tệp đầy đủ của tệp trung gian.'),

    # Filter docblock ~856
    ('\t/**\n\t * Filters the output of image_get_intermediate_size()\n\t *\n\t * @since 4.4.0\n\t *\n\t * @see image_get_intermediate_size()\n\t *\n\t * @param array        $data    Array of file relative path, width, and height on success. May also include\n\t *                              file absolute path and URL.\n\t * @param int          $post_id The ID of the image attachment.\n\t * @param string|int[] $size    Requested image size. Can be any registered image size name, or\n\t *                              an array of width and height values in pixels (in that order).\n\t */',
     '\t/**\n\t * Lọc đầu ra của image_get_intermediate_size()\n\t *\n\t * @since 4.4.0\n\t *\n\t * @see image_get_intermediate_size()\n\t *\n\t * @param array        $data    Mảng đường dẫn tương đối, chiều rộng và chiều cao khi thành công. Cũng có thể bao gồm\n\t *                              đường dẫn tuyệt đối và URL.\n\t * @param int          $post_id ID của đính kèm hình ảnh.\n\t * @param string|int[] $size    Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc\n\t *                              mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).\n\t */'),

    # ~872
    ('/**\n * Gets the available intermediate image size names.\n *\n * @since 3.0.0\n *\n * @return string[] An array of image size names.\n */',
     '/**\n * Lấy các tên kích thước hình ảnh trung gian có sẵn.\n *\n * @since 3.0.0\n *\n * @return string[] Mảng các tên kích thước hình ảnh.\n */'),

    # Filter ~887
    ('\t/**\n\t * Filters the list of intermediate image sizes.\n\t *\n\t * @since 2.5.0\n\t *\n\t * @param string[] $default_sizes An array of intermediate image size names. Defaults\n\t *                                are \'thumbnail\', \'medium\', \'medium_large\', \'large\'.\n\t */',
     '\t/**\n\t * Lọc danh sách các kích thước hình ảnh trung gian.\n\t *\n\t * @since 2.5.0\n\t *\n\t * @param string[] $default_sizes Mảng các tên kích thước hình ảnh trung gian. Mặc định\n\t *                                là \'thumbnail\', \'medium\', \'medium_large\', \'large\'.\n\t */'),

    # ~898
    ('/**\n * Returns a normalized list of all currently registered image sub-sizes.\n *\n * @since 5.3.0\n * @uses wp_get_additional_image_sizes()\n * @uses get_intermediate_image_sizes()\n *\n * @return array[] Associative array of arrays of image sub-size information,\n *                 keyed by image size name.\n */',
     '/**\n * Trả về danh sách đã chuẩn hóa của tất cả kích thước phụ hình ảnh đã đăng ký hiện tại.\n *\n * @since 5.3.0\n * @uses wp_get_additional_image_sizes()\n * @uses get_intermediate_image_sizes()\n *\n * @return array[] Mảng kết hợp các mảng thông tin kích thước phụ hình ảnh,\n *                 được đánh chỉ mục theo tên kích thước hình ảnh.\n */'),

    ('// For sizes added by plugins and themes.', '// Cho các kích thước được thêm bởi plugin và theme.'),
    ('// For default sizes set in options.', '// Cho các kích thước mặc định được thiết lập trong tùy chọn.'),
    ('// This size isn\'t set.', '// Kích thước này chưa được thiết lập.'),

    # ~954
    ('/**\n * Retrieves an image to represent an attachment.\n *\n * @since 2.5.0\n *\n * @param int          $attachment_id Image attachment ID.\n * @param string|int[] $size          Optional. Image size. Accepts any registered image size name, or an array of\n *                                    width and height values in pixels (in that order). Default \'thumbnail\'.\n * @param bool         $icon          Optional. Whether the image should fall back to a mime type icon. Default false.\n * @return array|false {\n *     Array of image data, or boolean false if no image is available.\n *\n *     @type string $0 Image source URL.\n *     @type int    $1 Image width in pixels.\n *     @type int    $2 Image height in pixels.\n *     @type bool   $3 Whether the image is a resized image.\n * }\n */',
     '/**\n * Lấy hình ảnh đại diện cho đính kèm.\n *\n * @since 2.5.0\n *\n * @param int          $attachment_id ID đính kèm hình ảnh.\n * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng\n *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định \'thumbnail\'.\n * @param bool         $icon          Tùy chọn. Có nên dùng biểu tượng loại MIME làm phương án dự phòng hay không. Mặc định false.\n * @return array|false {\n *     Mảng dữ liệu hình ảnh, hoặc boolean false nếu không có hình ảnh.\n *\n *     @type string $0 URL nguồn hình ảnh.\n *     @type int    $1 Chiều rộng hình ảnh tính bằng pixel.\n *     @type int    $2 Chiều cao hình ảnh tính bằng pixel.\n *     @type bool   $3 Hình ảnh có phải là ảnh đã thay đổi kích thước hay không.\n * }\n */'),

    ('// Get a thumbnail or intermediate image if there is one.', '// Lấy ảnh thu nhỏ hoặc ảnh trung gian nếu có.'),
    ('/** This filter is documented in wp-includes/post.php */', '/** Bộ lọc này được ghi chú trong wp-includes/post.php */'),
    ('// SVG does not have true dimensions, so this assigns width and height directly.', '// SVG không có kích thước thực, nên gán chiều rộng và chiều cao trực tiếp.'),
]

for old, new in replacements:
    if old in content:
        content = content.replace(old, new, 1)
        print(f"Replaced: {old[:60]}...")
    else:
        print(f"NOT FOUND: {old[:60]}...")

with open('/home/phanvan91/projects/wordpress/wp-includes/media.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("\nDone with first batch!")
