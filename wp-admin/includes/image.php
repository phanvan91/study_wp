<?php
/**
 * File chứa tất cả các hàm xử lý hình ảnh trong quản trị.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Cắt hình ảnh theo kích thước cho trước.
 *
 * @since 2.1.0
 *
 * @param string|int   $src      File nguồn hoặc ID đính kèm.
 * @param int          $src_x    Vị trí x bắt đầu cắt.
 * @param int          $src_y    Vị trí y bắt đầu cắt.
 * @param int          $src_w    Chiều rộng cần cắt.
 * @param int          $src_h    Chiều cao cần cắt.
 * @param int          $dst_w    Chiều rộng đích.
 * @param int          $dst_h    Chiều cao đích.
 * @param bool|false   $src_abs  Tùy chọn. Các điểm cắt nguồn có phải tuyệt đối hay không.
 * @param string|false $dst_file Tùy chọn. File đích để ghi vào.
 * @return string|WP_Error Đường dẫn file mới khi thành công, WP_Error khi thất bại.
 */
function wp_crop_image( $src, $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs = false, $dst_file = false ) {
	$src_file = $src;
	if ( is_numeric( $src ) ) { // Xử lý số nguyên làm ID đính kèm.
		$src_file = get_attached_file( $src );

		if ( ! file_exists( $src_file ) ) {
			/*
			 * Nếu file không tồn tại, thử fopen URL trên liên kết nguồn.
			 * Điều này có thể xảy ra với một số plugin sao chép file.
			 */
			$src = _load_image_to_edit_path( $src, 'full' );
		} else {
			$src = $src_file;
		}
	}

	$editor = wp_get_image_editor( $src );
	if ( is_wp_error( $editor ) ) {
		return $editor;
	}

	$src = $editor->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs );
	if ( is_wp_error( $src ) ) {
		return $src;
	}

	if ( ! $dst_file ) {
		$dst_file = str_replace( wp_basename( $src_file ), 'cropped-' . wp_basename( $src_file ), $src_file );
	}

	/*
	 * Thư mục chứa file gốc có thể không còn tồn tại khi
	 * sử dụng plugin sao chép.
	 */
	wp_mkdir_p( dirname( $dst_file ) );

	$dst_file = dirname( $dst_file ) . '/' . wp_unique_filename( dirname( $dst_file ), wp_basename( $dst_file ) );

	$result = $editor->save( $dst_file );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( ! empty( $result['path'] ) ) {
		return $result['path'];
	}

	return $dst_file;
}

/**
 * So sánh các kích thước phụ hình ảnh hiện có (đã lưu trong meta đính kèm)
 * với các kích thước phụ hình ảnh đã đăng ký hiện tại, và trả về sự khác biệt.
 *
 * Các kích thước đã đăng ký lớn hơn hình ảnh sẽ bị bỏ qua.
 *
 * @since 5.3.0
 *
 * @param int $attachment_id ID bài đính kèm hình ảnh.
 * @return array[] Mảng liên kết chứa các mảng thông tin kích thước phụ hình ảnh
 *                 cho các kích thước bị thiếu, được đánh khóa theo tên kích thước.
 */
function wp_get_missing_image_subsizes( $attachment_id ) {
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return array();
	}

	$registered_sizes = wp_get_registered_image_subsizes();
	$image_meta       = wp_get_attachment_metadata( $attachment_id );

	// Lỗi meta?
	if ( empty( $image_meta ) ) {
		return $registered_sizes;
	}

	// Sử dụng kích thước hình ảnh được tải lên ban đầu làm full_width và full_height.
	if ( ! empty( $image_meta['original_image'] ) ) {
		$image_file = wp_get_original_image_path( $attachment_id );
		$imagesize  = wp_getimagesize( $image_file );
	}

	if ( ! empty( $imagesize ) ) {
		$full_width  = $imagesize[0];
		$full_height = $imagesize[1];
	} else {
		$full_width  = (int) $image_meta['width'];
		$full_height = (int) $image_meta['height'];
	}

	$possible_sizes = array();

	// Bỏ qua các kích thước đã đăng ký quá lớn cho hình ảnh đã tải lên.
	foreach ( $registered_sizes as $size_name => $size_data ) {
		if ( image_resize_dimensions( $full_width, $full_height, $size_data['width'], $size_data['height'], $size_data['crop'] ) ) {
			$possible_sizes[ $size_name ] = $size_data;
		}
	}

	if ( empty( $image_meta['sizes'] ) ) {
		$image_meta['sizes'] = array();
	}

	/*
	 * Xóa các kích thước đã tồn tại. Chỉ kiểm tra "tên kích thước" khớp nhau.
	 * Có thể kích thước cho một tên cụ thể đã thay đổi.
	 * Ví dụ người dùng đã thay đổi giá trị trên màn hình Cài đặt -> Phương tiện.
	 * Tuy nhiên chúng ta giữ lại các kích thước phụ cũ với kích thước trước đó
	 * vì hình ảnh có thể đã được sử dụng trong bài viết cũ hơn.
	 */
	$missing_sizes = array_diff_key( $possible_sizes, $image_meta['sizes'] );

	/**
	 * Lọc mảng các kích thước phụ hình ảnh bị thiếu cho hình ảnh đã tải lên.
	 *
	 * @since 5.3.0
	 *
	 * @param array[] $missing_sizes Mảng liên kết chứa các mảng thông tin kích thước phụ hình ảnh
	 *                               cho các kích thước bị thiếu, được đánh khóa theo tên kích thước.
	 * @param array   $image_meta    Dữ liệu meta hình ảnh.
	 * @param int     $attachment_id ID bài đính kèm hình ảnh.
	 */
	return apply_filters( 'wp_get_missing_image_subsizes', $missing_sizes, $image_meta, $attachment_id );
}

/**
 * Nếu bất kỳ kích thước phụ hình ảnh đã đăng ký nào bị thiếu,
 * tạo chúng và cập nhật dữ liệu meta hình ảnh.
 *
 * @since 5.3.0
 *
 * @param int $attachment_id ID bài đính kèm hình ảnh.
 * @return array|WP_Error Mảng dữ liệu meta hình ảnh đã cập nhật hoặc đối tượng WP_Error
 *                        nếu cả meta hình ảnh và file đính kèm đều bị thiếu.
 */
function wp_update_image_subsizes( $attachment_id ) {
	$image_meta = wp_get_attachment_metadata( $attachment_id );
	$image_file = wp_get_original_image_path( $attachment_id );

	if ( empty( $image_meta ) || ! is_array( $image_meta ) ) {
		/*
		 * Tải lên thất bại trước đó?
		 * Nếu có file đã tải lên, tạo tất cả kích thước phụ và sinh toàn bộ meta đính kèm.
		 */
		if ( ! empty( $image_file ) ) {
			$image_meta = wp_create_image_subsizes( $image_file, $attachment_id );
		} else {
			return new WP_Error( 'invalid_attachment', __( 'The attached file cannot be found.' ) );
		}
	} else {
		$missing_sizes = wp_get_missing_image_subsizes( $attachment_id );

		if ( empty( $missing_sizes ) ) {
			return $image_meta;
		}

		// Điều này cũng cập nhật meta hình ảnh.
		$image_meta = _wp_make_subsizes( $missing_sizes, $image_file, $image_meta, $attachment_id );
	}

	/** Bộ lọc này được ghi tài liệu trong wp-admin/includes/image.php */
	$image_meta = apply_filters( 'wp_generate_attachment_metadata', $image_meta, $attachment_id, 'update' );

	// Lưu metadata đã cập nhật.
	wp_update_attachment_metadata( $attachment_id, $image_meta );

	return $image_meta;
}

/**
 * Cập nhật file đính kèm và dữ liệu meta hình ảnh khi hình ảnh gốc được chỉnh sửa.
 *
 * @since 5.3.0
 * @since 6.0.0 Giá trị `$filesize` được thêm vào mảng trả về.
 * @access private
 *
 * @param array  $saved_data    Dữ liệu trả về từ WP_Image_Editor sau khi lưu hình ảnh thành công.
 * @param string $original_file Đường dẫn đến file gốc.
 * @param array  $image_meta    Mảng dữ liệu meta hình ảnh.
 * @param int    $attachment_id ID bài đính kèm.
 * @return array Dữ liệu meta hình ảnh đã cập nhật.
 */
function _wp_image_meta_replace_original( $saved_data, $original_file, $image_meta, $attachment_id ) {
	$new_file = $saved_data['path'];

	// Cập nhật meta file đính kèm.
	update_attached_file( $attachment_id, $new_file );

	// Chiều rộng và chiều cao của hình ảnh mới.
	$image_meta['width']  = $saved_data['width'];
	$image_meta['height'] = $saved_data['height'];

	// Đặt đường dẫn file tương đối so với thư mục upload.
	$image_meta['file'] = _wp_relative_upload_path( $new_file );

	// Thêm kích thước file hình ảnh.
	$image_meta['filesize'] = wp_filesize( $new_file );

	// Lưu tên file hình ảnh gốc trong image_meta.
	$image_meta['original_image'] = wp_basename( $original_file );

	return $image_meta;
}

/**
 * Tạo các kích thước phụ hình ảnh, thêm dữ liệu mới vào mảng `sizes` trong meta hình ảnh, và cập nhật metadata hình ảnh.
 *
 * Được dùng sau khi hình ảnh được tải lên. Lưu/cập nhật metadata hình ảnh sau mỗi
 * kích thước phụ được tạo. Nếu có lỗi, nó được thêm vào mảng metadata hình ảnh trả về.
 *
 * @since 5.3.0
 *
 * @param string $file          Đường dẫn đầy đủ đến file hình ảnh.
 * @param int    $attachment_id ID đính kèm cần xử lý.
 * @return array Dữ liệu meta đính kèm hình ảnh.
 */
function wp_create_image_subsizes( $file, $attachment_id ) {
	$imagesize = wp_getimagesize( $file );

	if ( empty( $imagesize ) ) {
		// File không phải là hình ảnh.
		return array();
	}

	// Meta hình ảnh mặc định.
	$image_meta = array(
		'width'    => $imagesize[0],
		'height'   => $imagesize[1],
		'file'     => _wp_relative_upload_path( $file ),
		'filesize' => wp_filesize( $file ),
		'sizes'    => array(),
	);

	// Lấy metadata bổ sung từ EXIF/IPTC.
	$exif_meta = wp_read_image_metadata( $file );

	if ( $exif_meta ) {
		$image_meta['image_meta'] = $exif_meta;
	}

	/**
	 * Lọc giá trị ngưỡng "hình ảnh LỚN".
	 *
	 * Nếu chiều rộng hoặc chiều cao hình ảnh gốc vượt quá ngưỡng, nó sẽ được thu nhỏ. Ngưỡng
	 * được sử dụng làm chiều rộng tối đa và chiều cao tối đa. Hình ảnh đã thu nhỏ sẽ được dùng
	 * làm kích thước lớn nhất có sẵn, bao gồm giá trị meta bài `_wp_attached_file`.
	 *
	 * Trả về `false` từ callback bộ lọc sẽ vô hiệu hóa việc thu nhỏ.
	 *
	 * @since 5.3.0
	 *
	 * @param int    $threshold     Giá trị ngưỡng tính bằng pixel. Mặc định 2560.
	 * @param array  $imagesize     {
	 *     Mảng chỉ mục chứa chiều rộng và chiều cao hình ảnh tính bằng pixel.
	 *
	 *     @type int $0 Chiều rộng hình ảnh.
	 *     @type int $1 Chiều cao hình ảnh.
	 * }
	 * @param string $file          Đường dẫn đầy đủ đến file hình ảnh đã tải lên.
	 * @param int    $attachment_id ID bài đính kèm.
	 */
	$threshold = (int) apply_filters( 'big_image_size_threshold', 2560, $imagesize, $file, $attachment_id );

	/*
	 * Nếu kích thước hình ảnh gốc vượt quá ngưỡng,
	 * thu nhỏ hình ảnh và sử dụng nó làm kích thước "full".
	 */
	$scale_down = false;
	$convert    = false;

	if ( $threshold && ( $image_meta['width'] > $threshold || $image_meta['height'] > $threshold ) ) {
		// Hình ảnh sẽ được chuyển đổi nếu cần khi lưu.
		$scale_down = true;
	} else {
		// Hình ảnh có thể cần được chuyển đổi bất kể kích thước.
		$output_format = wp_get_image_editor_output_format( $file, $imagesize['mime'] );

		if (
			is_array( $output_format ) &&
			array_key_exists( $imagesize['mime'], $output_format ) &&
			$output_format[ $imagesize['mime'] ] !== $imagesize['mime']
		) {
			$convert = true;
		}
	}

	if ( $scale_down || $convert ) {
		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) ) {
			// Không thể chỉnh sửa hình ảnh này.
			return $image_meta;
		}

		if ( $scale_down ) {
			// Thay đổi kích thước hình ảnh. Điều này cũng sẽ chuyển đổi nếu cần.
			$resized = $editor->resize( $threshold, $threshold );
		} elseif ( $convert ) {
			// Hình ảnh sẽ được chuyển đổi (nếu có thể) khi lưu.
			$resized = true;
		}

		$rotated = null;

		// Nếu có dữ liệu EXIF, xoay theo hướng EXIF.
		if ( ! is_wp_error( $resized ) && is_array( $exif_meta ) ) {
			$resized = $editor->maybe_exif_rotate();
			$rotated = $resized; // bool true hoặc WP_Error
		}

		if ( ! is_wp_error( $resized ) ) {
			/*
			 * Thêm "-scaled" vào tên file hình ảnh. Nó sẽ trông như "my_image-scaled.jpg".
			 * Điều này không ảnh hưởng đến tên các kích thước phụ vì chúng được sinh từ hình ảnh gốc (để có chất lượng tốt nhất).
			 */
			if ( $scale_down ) {
				$saved = $editor->save( $editor->generate_filename( 'scaled' ) );
			} elseif ( $convert ) {
				// Truyền chuỗi rỗng để tránh thêm hậu tố vào tên file đã chuyển đổi.
				$saved = $editor->save( $editor->generate_filename( '' ) );
			} else {
				$saved = $editor->save();
			}

			if ( ! is_wp_error( $saved ) ) {
				$image_meta = _wp_image_meta_replace_original( $saved, $file, $image_meta, $attachment_id );

				// Nếu hình ảnh đã được xoay, cập nhật dữ liệu EXIF đã lưu.
				if ( true === $rotated && ! empty( $image_meta['image_meta']['orientation'] ) ) {
					$image_meta['image_meta']['orientation'] = 1;
				}
			} else {
				// TODO: Ghi log lỗi.
			}
		} else {
			// TODO: Ghi log lỗi.
		}
	} elseif ( ! empty( $exif_meta['orientation'] ) && 1 !== (int) $exif_meta['orientation'] ) {
		// Xoay toàn bộ hình ảnh gốc nếu có dữ liệu EXIF và "orientation" không phải 1.
		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) ) {
			// Không thể chỉnh sửa hình ảnh này.
			return $image_meta;
		}

		// Xoay hình ảnh.
		$rotated = $editor->maybe_exif_rotate();

		if ( true === $rotated ) {
			// Thêm `-rotated` vào tên file hình ảnh.
			$saved = $editor->save( $editor->generate_filename( 'rotated' ) );

			if ( ! is_wp_error( $saved ) ) {
				$image_meta = _wp_image_meta_replace_original( $saved, $file, $image_meta, $attachment_id );

				// Cập nhật dữ liệu EXIF đã lưu.
				if ( ! empty( $image_meta['image_meta']['orientation'] ) ) {
					$image_meta['image_meta']['orientation'] = 1;
				}
			} else {
				// TODO: Ghi log lỗi.
			}
		}
	}

	/*
	 * Lưu ban đầu metadata mới.
	 * Tại thời điểm này file đã được tải lên và chuyển đến thư mục uploads
	 * nhưng các kích thước phụ hình ảnh chưa được tạo và mảng `sizes` còn trống.
	 */
	wp_update_attachment_metadata( $attachment_id, $image_meta );

	$new_sizes = wp_get_registered_image_subsizes();

	/**
	 * Lọc các kích thước hình ảnh được sinh tự động khi tải lên hình ảnh.
	 *
	 * @since 2.9.0
	 * @since 4.4.0 Thêm đối số `$image_meta`.
	 * @since 5.3.0 Thêm đối số `$attachment_id`.
	 *
	 * @param array $new_sizes     Mảng liên kết các kích thước hình ảnh cần tạo.
	 * @param array $image_meta    Dữ liệu meta hình ảnh: chiều rộng, chiều cao, file, sizes, v.v.
	 * @param int   $attachment_id ID bài đính kèm cho hình ảnh.
	 */
	$new_sizes = apply_filters( 'intermediate_image_sizes_advanced', $new_sizes, $image_meta, $attachment_id );

	return _wp_make_subsizes( $new_sizes, $file, $image_meta, $attachment_id );
}

/**
 * Hàm cấp thấp để tạo các kích thước phụ hình ảnh.
 *
 * Cập nhật meta hình ảnh sau mỗi kích thước phụ được tạo.
 * Lỗi được lưu trong mảng metadata hình ảnh trả về.
 *
 * @since 5.3.0
 * @access private
 *
 * @param array  $new_sizes     Mảng định nghĩa các kích thước cần tạo.
 * @param string $file          Đường dẫn đầy đủ đến file hình ảnh.
 * @param array  $image_meta    Mảng dữ liệu meta đính kèm.
 * @param int    $attachment_id ID đính kèm cần xử lý.
 * @return array Dữ liệu meta đính kèm với mảng `sizes` đã cập nhật. Bao gồm mảng lỗi gặp phải khi thay đổi kích thước.
 */
function _wp_make_subsizes( $new_sizes, $file, $image_meta, $attachment_id ) {
	if ( empty( $image_meta ) || ! is_array( $image_meta ) ) {
		// Không phải đính kèm hình ảnh.
		return array();
	}

	// Kiểm tra xem có kích thước mới nào đã tồn tại không.
	if ( isset( $image_meta['sizes'] ) && is_array( $image_meta['sizes'] ) ) {
		foreach ( $image_meta['sizes'] as $size_name => $size_meta ) {
			/*
			 * Chỉ kiểm tra "tên kích thước" nên không ghi đè hình ảnh hiện có ngay cả khi kích thước
			 * không khớp với kích thước đã định nghĩa hiện tại có cùng tên.
			 * Để thay đổi hành vi, hủy đặt các kích thước đã thay đổi/không khớp trong mảng `sizes` trong meta hình ảnh.
			 */
			if ( array_key_exists( $size_name, $new_sizes ) ) {
				unset( $new_sizes[ $size_name ] );
			}
		}
	} else {
		$image_meta['sizes'] = array();
	}

	if ( empty( $new_sizes ) ) {
		// Không có gì để làm...
		return $image_meta;
	}

	/*
	 * Sắp xếp các kích thước phụ hình ảnh theo thứ tự ưu tiên khi tạo.
	 * Điều này đảm bảo có một kích thước phụ phù hợp mà người dùng có thể truy cập ngay
	 * ngay cả khi có lỗi và không phải tất cả kích thước phụ đều được tạo.
	 */
	$priority = array(
		'medium'       => null,
		'large'        => null,
		'thumbnail'    => null,
		'medium_large' => null,
	);

	$new_sizes = array_filter( array_merge( $priority, $new_sizes ) );

	$editor = wp_get_image_editor( $file );

	if ( is_wp_error( $editor ) ) {
		// Không thể chỉnh sửa hình ảnh.
		return $image_meta;
	}

	// Nếu có dữ liệu EXIF đã lưu, xoay hình ảnh nguồn trước khi tạo kích thước phụ.
	if ( ! empty( $image_meta['image_meta'] ) ) {
		$rotated = $editor->maybe_exif_rotate();

		if ( is_wp_error( $rotated ) ) {
			// TODO: Ghi log lỗi.
		}
	}

	if ( method_exists( $editor, 'make_subsize' ) ) {
		foreach ( $new_sizes as $new_size_name => $new_size_data ) {
			$new_size_meta = $editor->make_subsize( $new_size_data );

			if ( is_wp_error( $new_size_meta ) ) {
				// TODO: Ghi log lỗi.
			} else {
				// Lưu giá trị meta kích thước.
				$image_meta['sizes'][ $new_size_name ] = $new_size_meta;
				wp_update_attachment_metadata( $attachment_id, $image_meta );
			}
		}
	} else {
		// Quay lại dùng `$editor->multi_resize()`.
		$created_sizes = $editor->multi_resize( $new_sizes );

		if ( ! empty( $created_sizes ) ) {
			$image_meta['sizes'] = array_merge( $image_meta['sizes'], $created_sizes );
			wp_update_attachment_metadata( $attachment_id, $image_meta );
		}
	}

	return $image_meta;
}

/**
 * Sao chép thuộc tính đính kèm cha sang hình ảnh mới được cắt.
 *
 * @since 6.5.0
 *
 * @param string $cropped              Đường dẫn đến file hình ảnh đã cắt.
 * @param int    $parent_attachment_id ID đính kèm file cha.
 * @param string $context              Ngữ cảnh gọi hàm.
 * @return array Thuộc tính của đính kèm.
 */
function wp_copy_parent_attachment_properties( $cropped, $parent_attachment_id, $context = '' ) {
	$parent          = get_post( $parent_attachment_id );
	$parent_url      = wp_get_attachment_url( $parent->ID );
	$parent_basename = wp_basename( $parent_url );
	$url             = str_replace( wp_basename( $parent_url ), wp_basename( $cropped ), $parent_url );

	$size       = wp_getimagesize( $cropped );
	$image_type = $size ? $size['mime'] : 'image/jpeg';

	$sanitized_post_title = sanitize_file_name( $parent->post_title );
	$use_original_title   = (
		( '' !== trim( $parent->post_title ) ) &&
		/*
		 * Kiểm tra xem hình ảnh gốc có tiêu đề khác với mặc định "tên file" hay không,
		 * nghĩa là hình ảnh đã có tiêu đề khi tải lên ban đầu hoặc tiêu đề đã được chỉnh sửa.
		 */
		( $parent_basename !== $sanitized_post_title ) &&
		( pathinfo( $parent_basename, PATHINFO_FILENAME ) !== $sanitized_post_title )
	);
	$use_original_description = ( '' !== trim( $parent->post_content ) );

	$attachment = array(
		'post_title'     => $use_original_title ? $parent->post_title : wp_basename( $cropped ),
		'post_content'   => $use_original_description ? $parent->post_content : $url,
		'post_mime_type' => $image_type,
		'guid'           => $url,
		'context'        => $context,
	);

	// Sao chép thuộc tính chú thích hình ảnh (trường post_excerpt) từ hình ảnh gốc.
	if ( '' !== trim( $parent->post_excerpt ) ) {
		$attachment['post_excerpt'] = $parent->post_excerpt;
	}

	// Sao chép thuộc tính văn bản thay thế hình ảnh từ hình ảnh gốc.
	if ( '' !== trim( $parent->_wp_attachment_image_alt ) ) {
		$attachment['meta_input'] = array(
			'_wp_attachment_image_alt' => wp_slash( $parent->_wp_attachment_image_alt ),
		);
	}

	$attachment['post_parent'] = $parent_attachment_id;

	return $attachment;
}

/**
 * Sinh dữ liệu meta đính kèm và tạo các kích thước phụ cho hình ảnh.
 *
 * @since 2.1.0
 * @since 6.0.0 Giá trị `$filesize` được thêm vào mảng trả về.
 * @since 6.7.0 Hỗ trợ loại mime 'image/heic'.
 *
 * @param int    $attachment_id ID đính kèm cần xử lý.
 * @param string $file          Đường dẫn file của hình ảnh đính kèm.
 * @return array Metadata cho đính kèm.
 */
function wp_generate_attachment_metadata( $attachment_id, $file ) {
	$attachment = get_post( $attachment_id );

	$metadata  = array();
	$support   = false;
	$mime_type = get_post_mime_type( $attachment );

	if ( 'image/heic' === $mime_type || ( preg_match( '!^image/!', $mime_type ) && file_is_displayable_image( $file ) ) ) {
		// Tạo ảnh thu nhỏ và các kích thước trung gian khác.
		$metadata = wp_create_image_subsizes( $file, $attachment_id );
	} elseif ( wp_attachment_is( 'video', $attachment ) ) {
		$metadata = wp_read_video_metadata( $file );
		$support  = current_theme_supports( 'post-thumbnails', 'attachment:video' ) || post_type_supports( 'attachment:video', 'thumbnail' );
	} elseif ( wp_attachment_is( 'audio', $attachment ) ) {
		$metadata = wp_read_audio_metadata( $file );
		$support  = current_theme_supports( 'post-thumbnails', 'attachment:audio' ) || post_type_supports( 'attachment:audio', 'thumbnail' );
	}

	/*
	 * wp_read_video_metadata() và wp_read_audio_metadata() trả về `false`
	 * nếu đính kèm không tồn tại trên hệ thống file cục bộ,
	 * nên đảm bảo chuyển đổi giá trị thành mảng.
	 */
	if ( ! is_array( $metadata ) ) {
		$metadata = array();
	}

	if ( $support && ! empty( $metadata['image']['data'] ) ) {
		// Kiểm tra ảnh bìa đã tồn tại.
		$hash   = md5( $metadata['image']['data'] );
		$posts  = get_posts(
			array(
				'fields'         => 'ids',
				'post_type'      => 'attachment',
				'post_mime_type' => $metadata['image']['mime'],
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'meta_key'       => '_cover_hash',
				'meta_value'     => $hash,
			)
		);
		$exists = reset( $posts );

		if ( ! empty( $exists ) ) {
			update_post_meta( $attachment_id, '_thumbnail_id', $exists );
		} else {
			$ext = '.jpg';
			switch ( $metadata['image']['mime'] ) {
				case 'image/gif':
					$ext = '.gif';
					break;
				case 'image/png':
					$ext = '.png';
					break;
				case 'image/webp':
					$ext = '.webp';
					break;
			}
			$basename = str_replace( '.', '-', wp_basename( $file ) ) . '-image' . $ext;
			$uploaded = wp_upload_bits( $basename, '', $metadata['image']['data'] );
			if ( false === $uploaded['error'] ) {
				$image_attachment = array(
					'post_mime_type' => $metadata['image']['mime'],
					'post_type'      => 'attachment',
					'post_content'   => '',
				);
				/**
				 * Lọc các tham số cho việc tạo ảnh thu nhỏ đính kèm.
				 *
				 * @since 3.9.0
				 *
				 * @param array $image_attachment Mảng các tham số để tạo ảnh thu nhỏ.
				 * @param array $metadata         Metadata đính kèm hiện tại.
				 * @param array $uploaded         {
				 *     Thông tin về file mới tải lên.
				 *
				 *     @type string $file  Tên file của file mới tải lên.
				 *     @type string $url   URL của file đã tải lên.
				 *     @type string $type  Loại file.
				 * }
				 */
				$image_attachment = apply_filters( 'attachment_thumbnail_args', $image_attachment, $metadata, $uploaded );

				$sub_attachment_id = wp_insert_attachment( $image_attachment, $uploaded['file'] );
				add_post_meta( $sub_attachment_id, '_cover_hash', $hash );
				$attach_data = wp_generate_attachment_metadata( $sub_attachment_id, $uploaded['file'] );
				wp_update_attachment_metadata( $sub_attachment_id, $attach_data );
				update_post_meta( $attachment_id, '_thumbnail_id', $sub_attachment_id );
			}
		}
	} elseif ( 'application/pdf' === $mime_type ) {
		// Thử tạo ảnh thu nhỏ cho PDF.

		$fallback_sizes = array(
			'thumbnail',
			'medium',
			'large',
		);

		/**
		 * Lọc các kích thước hình ảnh được sinh cho các loại mime không phải hình ảnh.
		 *
		 * @since 4.7.0
		 *
		 * @param string[] $fallback_sizes Mảng tên kích thước hình ảnh.
		 * @param array    $metadata       Metadata đính kèm hiện tại.
		 */
		$fallback_sizes = apply_filters( 'fallback_intermediate_image_sizes', $fallback_sizes, $metadata );

		$registered_sizes = wp_get_registered_image_subsizes();
		$merged_sizes     = array_intersect_key( $registered_sizes, array_flip( $fallback_sizes ) );

		// Ép ảnh thu nhỏ cắt mềm.
		if ( isset( $merged_sizes['thumbnail'] ) && is_array( $merged_sizes['thumbnail'] ) ) {
			$merged_sizes['thumbnail']['crop'] = false;
		}

		// Chỉ tải PDF trong trình chỉnh sửa hình ảnh nếu đang xử lý kích thước.
		if ( ! empty( $merged_sizes ) ) {
			$editor = wp_get_image_editor( $file );

			if ( ! is_wp_error( $editor ) ) { // Không hỗ trợ loại file này.
				/*
				 * PDF có thể có cùng tên file với JPEG.
				 * Đảm bảo hình ảnh xem trước PDF không ghi đè bất kỳ hình ảnh JPEG nào đã tồn tại.
				 */
				$dirname      = dirname( $file ) . '/';
				$ext          = '.' . pathinfo( $file, PATHINFO_EXTENSION );
				$preview_file = $dirname . wp_unique_filename( $dirname, wp_basename( $file, $ext ) . '-pdf.jpg' );

				$uploaded = $editor->save( $preview_file, 'image/jpeg' );
				unset( $editor );

				// Thay đổi kích thước dựa trên hình ảnh kích thước đầy đủ, thay vì nguồn.
				if ( ! is_wp_error( $uploaded ) ) {
					$image_file = $uploaded['path'];
					unset( $uploaded['path'] );

					$metadata['sizes'] = array(
						'full' => $uploaded,
					);

					// Lưu dữ liệu meta trước khi bất kỳ lỗi hậu xử lý hình ảnh nào có thể xảy ra.
					wp_update_attachment_metadata( $attachment_id, $metadata );

					// Tạo kích thước phụ và lưu meta hình ảnh sau mỗi kích thước.
					$metadata = _wp_make_subsizes( $merged_sizes, $image_file, $metadata, $attachment_id );
				}
			}
		}
	}

	// Xóa dữ liệu nhị phân khỏi mảng.
	unset( $metadata['image']['data'] );

	// Ghi nhận kích thước file cho trường hợp chưa được ghi, ví dụ PDF.
	if ( ! isset( $metadata['filesize'] ) && file_exists( $file ) ) {
		$metadata['filesize'] = wp_filesize( $file );
	}

	/**
	 * Lọc dữ liệu meta đính kèm đã sinh.
	 *
	 * @since 2.1.0
	 * @since 5.3.0 Thêm tham số `$context`.
	 *
	 * @param array  $metadata      Mảng dữ liệu meta đính kèm.
	 * @param int    $attachment_id ID đính kèm hiện tại.
	 * @param string $context       Ngữ cảnh bổ sung. Có thể là 'create' khi metadata được tạo ban đầu cho đính kèm mới
	 *                              hoặc 'update' khi metadata được cập nhật.
	 */
	return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'create' );
}

/**
 * Chuyển đổi chuỗi phân số thành số thập phân.
 *
 * @since 2.5.0
 *
 * @param string $str Chuỗi phân số.
 * @return int|float Trả về phân số đã tính hoặc số nguyên 0 khi đầu vào không hợp lệ.
 */
function wp_exif_frac2dec( $str ) {
	if ( ! is_scalar( $str ) || is_bool( $str ) ) {
		return 0;
	}

	if ( ! is_string( $str ) ) {
		return $str; // Chỉ có thể là số nguyên hoặc số thực, nên điều này ổn.
	}

	// Phân số dạng chuỗi phải chứa đúng một `/`.
	if ( substr_count( $str, '/' ) !== 1 ) {
		if ( is_numeric( $str ) ) {
			return (float) $str;
		}

		return 0;
	}

	list( $numerator, $denominator ) = explode( '/', $str );

	// Cả tử số và mẫu số đều phải là số.
	if ( ! is_numeric( $numerator ) || ! is_numeric( $denominator ) ) {
		return 0;
	}

	// Mẫu số không được bằng không.
	if ( 0 == $denominator ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Deliberate loose comparison.
		return 0;
	}

	return $numerator / $denominator;
}

/**
 * Chuyển đổi định dạng ngày exif sang dấu thời gian unix.
 *
 * @since 2.5.0
 *
 * @param string $str Chuỗi ngày theo định dạng Exif (Y:m:d H:i:s).
 * @return int|false Dấu thời gian unix, hoặc false khi thất bại.
 */
function wp_exif_date2ts( $str ) {
	list( $date, $time ) = explode( ' ', trim( $str ) );
	list( $y, $m, $d )   = explode( ':', $date );

	return strtotime( "{$y}-{$m}-{$d} {$time}" );
}

/**
 * Lấy metadata hình ảnh mở rộng, exif hoặc iptc nếu có.
 *
 * Trích xuất metadata EXIF gồm aperture, credit, camera, caption, copyright, iso,
 * created_timestamp, focal_length, shutter_speed, và title.
 *
 * Metadata IPTC được trích xuất là APP13, credit, byline, ngày và giờ tạo,
 * caption, copyright, và title. Cũng bao gồm FNumber, Model,
 * DateTimeDigitized, FocalLength, ISOSpeedRatings, và ExposureTime.
 *
 * @todo Thử các thư viện exif khác nếu có.
 * @since 2.5.0
 *
 * @param string $file
 * @return array|false Mảng metadata hình ảnh khi thành công, false khi thất bại.
 */
function wp_read_image_metadata( $file ) {
	if ( ! file_exists( $file ) ) {
		return false;
	}

	list( , , $image_type ) = wp_getimagesize( $file );

	/*
	 * EXIF chứa nhiều dữ liệu mà chúng ta có lẽ không bao giờ cần, được định dạng
	 * theo cách khó sử dụng. Chúng ta sẽ chuẩn hóa và chỉ trích xuất các trường
	 * có thể hữu ích. Phân số và số được chuyển thành
	 * số thực, ngày thành dấu thời gian unix, và mọi thứ khác thành chuỗi.
	 */
	$meta = array(
		'aperture'          => 0,
		'credit'            => '',
		'camera'            => '',
		'caption'           => '',
		'created_timestamp' => 0,
		'copyright'         => '',
		'focal_length'      => 0,
		'iso'               => 0,
		'shutter_speed'     => 0,
		'title'             => '',
		'orientation'       => 0,
		'keywords'          => array(),
	);

	$iptc = array();
	$info = array();
	/*
	 * Đọc IPTC trước, vì nó có thể chứa dữ liệu không có trong exif như
	 * caption, description, v.v.
	 */
	if ( is_callable( 'iptcparse' ) ) {
		wp_getimagesize( $file, $info );

		if ( ! empty( $info['APP13'] ) ) {
			// Không tắt lỗi khi ở chế độ debug, trừ khi đang chạy unit test.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG
				&& ! defined( 'WP_RUN_CORE_TESTS' )
			) {
				$iptc = iptcparse( $info['APP13'] );
			} else {
				// Việc tắt notice và warning là có chủ đích. Xem https://core.trac.wordpress.org/ticket/42480
				$iptc = @iptcparse( $info['APP13'] );
			}

			if ( ! is_array( $iptc ) ) {
				$iptc = array();
			}

			// Tiêu đề, "Tóm tắt ngắn gọn của chú thích".
			if ( ! empty( $iptc['2#105'][0] ) ) {
				$meta['title'] = trim( $iptc['2#105'][0] );
				/*
				* Title, "Nhiều người sử dụng trường Title để lưu tên file hình ảnh,
				* mặc dù trường này có thể được sử dụng theo nhiều cách".
				*/
			} elseif ( ! empty( $iptc['2#005'][0] ) ) {
				$meta['title'] = trim( $iptc['2#005'][0] );
			}

			if ( ! empty( $iptc['2#120'][0] ) ) { // Mô tả / chú thích cũ.
				$caption = trim( $iptc['2#120'][0] );

				mbstring_binary_safe_encoding();
				$caption_length = strlen( $caption );
				reset_mbstring_encoding();

				if ( empty( $meta['title'] ) && $caption_length < 80 ) {
					// Giả định tiêu đề được lưu trong 2:120 nếu nó ngắn.
					$meta['title'] = $caption;
				}

				$meta['caption'] = $caption;
			}

			if ( ! empty( $iptc['2#110'][0] ) ) { // Ghi công.
				$meta['credit'] = trim( $iptc['2#110'][0] );
			} elseif ( ! empty( $iptc['2#080'][0] ) ) { // Tác giả / byline cũ.
				$meta['credit'] = trim( $iptc['2#080'][0] );
			}

			if ( ! empty( $iptc['2#055'][0] ) && ! empty( $iptc['2#060'][0] ) ) { // Ngày và giờ tạo.
				$meta['created_timestamp'] = strtotime( $iptc['2#055'][0] . ' ' . $iptc['2#060'][0] );
			}

			if ( ! empty( $iptc['2#116'][0] ) ) { // Bản quyền.
				$meta['copyright'] = trim( $iptc['2#116'][0] );
			}

			if ( ! empty( $iptc['2#025'][0] ) ) { // Mảng từ khóa.
				$meta['keywords'] = array_values( $iptc['2#025'] );
			}
		}
	}

	$exif = array();

	/**
	 * Lọc các loại hình ảnh cần kiểm tra dữ liệu exif.
	 *
	 * @since 2.5.0
	 *
	 * @param int[] $image_types Mảng các loại hình ảnh cần kiểm tra dữ liệu exif. Mỗi giá trị
	 *                           thường là một trong các hằng số `IMAGETYPE_*`.
	 */
	$exif_image_types = apply_filters( 'wp_read_image_metadata_types', array( IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM ) );

	if ( is_callable( 'exif_read_data' ) && in_array( $image_type, $exif_image_types, true ) ) {
		// Không tắt lỗi khi ở chế độ debug, trừ khi đang chạy unit test.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG
			&& ! defined( 'WP_RUN_CORE_TESTS' )
		) {
			$exif = exif_read_data( $file );
		} else {
			// Việc tắt notice và warning là có chủ đích. Xem https://core.trac.wordpress.org/ticket/42480
			$exif = @exif_read_data( $file );
		}

		if ( ! is_array( $exif ) ) {
			$exif = array();
		}

		$exif_description = '';
		$exif_usercomment = '';
		if ( ! empty( $exif['ImageDescription'] ) ) {
			$exif_description = trim( $exif['ImageDescription'] );
		}

		if ( ! empty( $exif['COMPUTED']['UserComment'] ) ) {
			$exif_usercomment = trim( $exif['COMPUTED']['UserComment'] );
		}

		if ( $exif_description ) {
			mbstring_binary_safe_encoding();
			$description_length = strlen( $exif_description );
			reset_mbstring_encoding();
			if ( empty( $meta['title'] ) && $description_length < 80 ) {
				// Giả định tiêu đề được lưu trong ImageDescription.
				$meta['title'] = $exif_description;
			}

			// Nếu cả nhận xét người dùng và mô tả đều có.
			if ( empty( $meta['caption'] ) && $exif_description && $exif_usercomment ) {
				if ( ! empty( $meta['title'] ) && $exif_description === $meta['title'] ) {
					$caption = $exif_usercomment;
				} else {
					if ( $exif_description === $exif_usercomment ) {
						$caption = $exif_description;
					} else {
						$caption = trim( $exif_description . ' ' . $exif_usercomment );
					}
				}
				$meta['caption'] = $caption;
			}

			if ( empty( $meta['caption'] ) && $exif_usercomment ) {
				$meta['caption'] = $exif_usercomment;
			}

			if ( empty( $meta['caption'] ) ) {
				$meta['caption'] = $exif_description;
			}
		} elseif ( empty( $meta['caption'] ) && $exif_usercomment ) {
			$meta['caption']    = $exif_usercomment;
			$description_length = strlen( $exif_usercomment );
			if ( empty( $meta['title'] ) && $description_length < 80 ) {
				$meta['title'] = trim( $exif_usercomment );
			}
		} elseif ( empty( $meta['caption'] ) && ! empty( $exif['Comments'] ) ) {
			$meta['caption'] = trim( $exif['Comments'] );
		}

		if ( empty( $meta['credit'] ) ) {
			if ( ! empty( $exif['Artist'] ) ) {
				$meta['credit'] = trim( $exif['Artist'] );
			} elseif ( ! empty( $exif['Author'] ) ) {
				$meta['credit'] = trim( $exif['Author'] );
			}
		}

		if ( empty( $meta['copyright'] ) && ! empty( $exif['Copyright'] ) ) {
			$meta['copyright'] = trim( $exif['Copyright'] );
		}
		if ( ! empty( $exif['FNumber'] ) && is_scalar( $exif['FNumber'] ) ) {
			$meta['aperture'] = round( wp_exif_frac2dec( $exif['FNumber'] ), 2 );
		}
		if ( ! empty( $exif['Model'] ) ) {
			$meta['camera'] = trim( $exif['Model'] );
		}
		if ( empty( $meta['created_timestamp'] ) && ! empty( $exif['DateTimeDigitized'] ) ) {
			$meta['created_timestamp'] = wp_exif_date2ts( $exif['DateTimeDigitized'] );
		}
		if ( ! empty( $exif['FocalLength'] ) ) {
			$meta['focal_length'] = (string) $exif['FocalLength'];
			if ( is_scalar( $exif['FocalLength'] ) ) {
				$meta['focal_length'] = (string) wp_exif_frac2dec( $exif['FocalLength'] );
			}
		}
		if ( ! empty( $exif['ISOSpeedRatings'] ) ) {
			$meta['iso'] = is_array( $exif['ISOSpeedRatings'] ) ? reset( $exif['ISOSpeedRatings'] ) : $exif['ISOSpeedRatings'];
			$meta['iso'] = trim( $meta['iso'] );
		}
		if ( ! empty( $exif['ExposureTime'] ) ) {
			$meta['shutter_speed'] = (string) $exif['ExposureTime'];
			if ( is_scalar( $exif['ExposureTime'] ) ) {
				$meta['shutter_speed'] = (string) wp_exif_frac2dec( $exif['ExposureTime'] );
			}
		}
		if ( ! empty( $exif['Orientation'] ) ) {
			$meta['orientation'] = $exif['Orientation'];
		}
	}

	foreach ( array( 'title', 'caption', 'credit', 'copyright', 'camera', 'iso' ) as $key ) {
		if ( $meta[ $key ] && ! seems_utf8( $meta[ $key ] ) ) {
			$meta[ $key ] = utf8_encode( $meta[ $key ] );
		}
	}

	foreach ( $meta['keywords'] as $key => $keyword ) {
		if ( ! seems_utf8( $keyword ) ) {
			$meta['keywords'][ $key ] = utf8_encode( $keyword );
		}
	}

	$meta = wp_kses_post_deep( $meta );

	/**
	 * Lọc mảng dữ liệu meta đọc từ dữ liệu exif của hình ảnh.
	 *
	 * @since 2.5.0
	 * @since 4.4.0 Thêm tham số `$iptc`.
	 * @since 5.0.0 Thêm tham số `$exif`.
	 *
	 * @param array  $meta       Dữ liệu meta hình ảnh.
	 * @param string $file       Đường dẫn đến file hình ảnh.
	 * @param int    $image_type Loại hình ảnh, một trong các hằng số `IMAGETYPE_XXX`.
	 * @param array  $iptc       Dữ liệu IPTC.
	 * @param array  $exif       Dữ liệu EXIF.
	 */
	return apply_filters( 'wp_read_image_metadata', $meta, $file, $image_type, $iptc, $exif );
}

/**
 * Xác thực rằng file là hình ảnh.
 *
 * @since 2.5.0
 *
 * @param string $path Đường dẫn file cần kiểm tra có phải hình ảnh hợp lệ hay không.
 * @return bool True nếu là hình ảnh hợp lệ, false nếu không phải.
 */
function file_is_valid_image( $path ) {
	$size = wp_getimagesize( $path );
	return ! empty( $size );
}

/**
 * Xác thực rằng file phù hợp để hiển thị trên trang web.
 *
 * @since 2.5.0
 *
 * @param string $path Đường dẫn file cần kiểm tra.
 * @return bool True nếu phù hợp, false nếu không phù hợp.
 */
function file_is_displayable_image( $path ) {
	$displayable_image_types = array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP, IMAGETYPE_ICO, IMAGETYPE_WEBP, IMAGETYPE_AVIF );

	$info = wp_getimagesize( $path );
	if ( empty( $info ) ) {
		$result = false;
	} elseif ( ! in_array( $info[2], $displayable_image_types, true ) ) {
		$result = false;
	} else {
		$result = true;
	}

	/**
	 * Lọc xem hình ảnh hiện tại có thể hiển thị trong trình duyệt hay không.
	 *
	 * @since 2.5.0
	 *
	 * @param bool   $result Hình ảnh có thể hiển thị hay không. Mặc định true.
	 * @param string $path   Đường dẫn đến hình ảnh.
	 */
	return apply_filters( 'file_is_displayable_image', $result, $path );
}

/**
 * Tải tài nguyên hình ảnh để chỉnh sửa.
 *
 * @since 2.9.0
 *
 * @param int          $attachment_id ID đính kèm.
 * @param string       $mime_type     Loại mime hình ảnh.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận bất kỳ tên kích thước hình ảnh đã đăng ký,
 *                                    hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'full'.
 * @return resource|GdImage|false Tài nguyên hình ảnh hoặc instance GdImage khi thành công,
 *                                false khi thất bại.
 */
function load_image_to_edit( $attachment_id, $mime_type, $size = 'full' ) {
	$filepath = _load_image_to_edit_path( $attachment_id, $size );
	if ( empty( $filepath ) ) {
		return false;
	}

	switch ( $mime_type ) {
		case 'image/jpeg':
			$image = imagecreatefromjpeg( $filepath );
			break;
		case 'image/png':
			$image = imagecreatefrompng( $filepath );
			break;
		case 'image/gif':
			$image = imagecreatefromgif( $filepath );
			break;
		case 'image/webp':
			$image = false;
			if ( function_exists( 'imagecreatefromwebp' ) ) {
				$image = imagecreatefromwebp( $filepath );
			}
			break;
		default:
			$image = false;
			break;
	}

	if ( is_gd_image( $image ) ) {
		/**
		 * Lọc hình ảnh hiện tại đang được tải để chỉnh sửa.
		 *
		 * @since 2.9.0
		 *
		 * @param resource|GdImage $image         Hình ảnh hiện tại.
		 * @param int              $attachment_id ID đính kèm.
		 * @param string|int[]     $size          Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
		 *                                        hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
		 */
		$image = apply_filters( 'load_image_to_edit', $image, $attachment_id, $size );

		if ( function_exists( 'imagealphablending' ) && function_exists( 'imagesavealpha' ) ) {
			imagealphablending( $image, false );
			imagesavealpha( $image, true );
		}
	}

	return $image;
}

/**
 * Lấy đường dẫn hoặc URL của file đính kèm.
 *
 * Nếu file đính kèm không có trên hệ thống file cục bộ (thường do plugin sao chép),
 * thì URL của file sẽ được trả về nếu `allow_url_fopen` được hỗ trợ.
 *
 * @since 3.4.0
 * @access private
 *
 * @param int          $attachment_id ID đính kèm.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận bất kỳ tên kích thước hình ảnh đã đăng ký,
 *                                    hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'full'.
 * @return string|false Đường dẫn file hoặc URL khi thành công, false khi thất bại.
 */
function _load_image_to_edit_path( $attachment_id, $size = 'full' ) {
	$filepath = get_attached_file( $attachment_id );

	if ( $filepath && file_exists( $filepath ) ) {
		if ( 'full' !== $size ) {
			$data = image_get_intermediate_size( $attachment_id, $size );

			if ( $data ) {
				$filepath = path_join( dirname( $filepath ), $data['file'] );

				/**
				 * Lọc đường dẫn đến file đính kèm khi chỉnh sửa hình ảnh.
				 *
				 * Bộ lọc được đánh giá cho tất cả kích thước hình ảnh ngoại trừ 'full'.
				 *
				 * @since 3.1.0
				 *
				 * @param string       $path          Đường dẫn đến hình ảnh hiện tại.
				 * @param int          $attachment_id ID đính kèm.
				 * @param string|int[] $size          Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
				 *                                    hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
				 */
				$filepath = apply_filters( 'load_image_to_edit_filesystempath', $filepath, $attachment_id, $size );
			}
		}
	} elseif ( function_exists( 'fopen' ) && ini_get( 'allow_url_fopen' ) ) {
		/**
		 * Lọc đường dẫn đến URL đính kèm khi chỉnh sửa hình ảnh.
		 *
		 * Bộ lọc chỉ được đánh giá nếu file không được lưu trữ cục bộ và `allow_url_fopen` được bật trên server.
		 *
		 * @since 3.1.0
		 *
		 * @param string|false $image_url     URL hình ảnh hiện tại.
		 * @param int          $attachment_id ID đính kèm.
		 * @param string|int[] $size          Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
		 *                                    hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
		 */
		$filepath = apply_filters( 'load_image_to_edit_attachmenturl', wp_get_attachment_url( $attachment_id ), $attachment_id, $size );
	}

	/**
	 * Lọc đường dẫn hoặc URL trả về của hình ảnh hiện tại.
	 *
	 * @since 2.9.0
	 *
	 * @param string|false $filepath      Đường dẫn file hoặc URL đến hình ảnh hiện tại, hoặc false.
	 * @param int          $attachment_id ID đính kèm.
	 * @param string|int[] $size          Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
	 *                                    hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 */
	return apply_filters( 'load_image_to_edit_path', $filepath, $attachment_id, $size );
}

/**
 * Sao chép file hình ảnh đã tồn tại.
 *
 * @since 3.4.0
 * @access private
 *
 * @param int $attachment_id ID đính kèm.
 * @return string|false Đường dẫn file mới khi thành công, false khi thất bại.
 */
function _copy_image_file( $attachment_id ) {
	$dst_file = get_attached_file( $attachment_id );
	$src_file = $dst_file;

	if ( ! file_exists( $src_file ) ) {
		$src_file = _load_image_to_edit_path( $attachment_id );
	}

	if ( $src_file ) {
		$dst_file = str_replace( wp_basename( $dst_file ), 'copy-' . wp_basename( $dst_file ), $dst_file );
		$dst_file = dirname( $dst_file ) . '/' . wp_unique_filename( dirname( $dst_file ), wp_basename( $dst_file ) );

		/*
		 * Thư mục chứa file gốc có thể không còn
		 * tồn tại khi sử dụng plugin sao chép.
		 */
		wp_mkdir_p( dirname( $dst_file ) );

		if ( ! copy( $src_file, $dst_file ) ) {
			$dst_file = false;
		}
	} else {
		$dst_file = false;
	}

	return $dst_file;
}
