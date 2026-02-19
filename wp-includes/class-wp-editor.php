<?php
/**
 * Hỗ trợ thêm trình soạn thảo WordPress được sử dụng trên các màn hình Viết và Chỉnh sửa.
 *
 * @package WordPress
 * @since 3.3.0
 *
 * Riêng tư, không được bao gồm mặc định. Xem wp_editor() trong wp-includes/general-template.php.
 */

#[AllowDynamicProperties]
final class _WP_Editors {
	public static $mce_locale;

	private static $mce_settings = array();
	private static $qt_settings  = array();
	private static $plugins      = array();
	private static $qt_buttons   = array();
	private static $ext_plugins;
	private static $baseurl;
	private static $first_init;
	private static $this_tinymce       = false;
	private static $this_quicktags     = false;
	private static $has_tinymce        = false;
	private static $has_quicktags      = false;
	private static $has_medialib       = false;
	private static $editor_buttons_css = true;
	private static $drag_drop_upload   = false;
	private static $translation;
	private static $tinymce_scripts_printed = false;
	private static $link_dialog_printed     = false;

	private function __construct() {}

	/**
	 * Phân tích các tham số mặc định cho phiên bản trình soạn thảo.
	 *
	 * @since 3.3.0
	 *
	 * @param string $editor_id ID HTML cho textarea và các phiên bản TinyMCE và Quicktags.
	 *                          Không nên chứa dấu ngoặc vuông.
	 * @param array  $settings {
	 *     Mảng các tham số trình soạn thảo.
	 *
	 *     @type bool       $wpautop           Có sử dụng wpautop() hay không. Mặc định true.
	 *     @type bool       $media_buttons     Có hiển thị nút Thêm Media/các nút media khác hay không.
	 *     @type string     $default_editor    Khi cả TinyMCE và Quicktags đều được sử dụng, đặt trình soạn thảo
	 *                                         nào hiển thị khi tải trang. Mặc định rỗng.
	 *     @type bool       $drag_drop_upload  Có bật kéo thả tải lên trên trình soạn thảo hay không. Mặc định false.
	 *                                         Yêu cầu modal media.
	 *     @type string     $textarea_name     Đặt tên duy nhất cho textarea ở đây. Có thể sử dụng dấu ngoặc vuông.
	 *                                         Mặc định $editor_id.
	 *     @type int        $textarea_rows     Số hàng trong textarea của trình soạn thảo. Mặc định 20.
	 *     @type string|int $tabindex          Giá trị tabindex để sử dụng. Mặc định rỗng.
	 *     @type string     $tabfocus_elements ID phần tử trước và sau để chuyển focus tới
	 *                                         khi nhấn phím Tab trong TinyMCE. Mặc định ':prev,:next'.
	 *     @type string     $editor_css        Dành cho các style bổ sung cho cả trình soạn thảo Trực quan và Code.
	 *                                         Nên bao gồm thẻ `<style>`, và có thể dùng "scoped". Mặc định rỗng.
	 *     @type string     $editor_class      Các class bổ sung thêm vào phần tử textarea của trình soạn thảo. Mặc định rỗng.
	 *     @type bool       $teeny             Có xuất cấu hình trình soạn thảo tối thiểu hay không. Ví dụ bao gồm
	 *                                         Press This và trình soạn thảo Bình luận. Mặc định false.
	 *     @type bool       $dfw               Không dùng nữa từ 4.1. Không sử dụng.
	 *     @type bool|array $tinymce           Có tải TinyMCE hay không. Có thể dùng để truyền cài đặt trực tiếp tới
	 *                                         TinyMCE bằng mảng. Mặc định true.
	 *     @type bool|array $quicktags         Có tải Quicktags hay không. Có thể dùng để truyền cài đặt trực tiếp tới
	 *                                         Quicktags bằng mảng. Mặc định true.
	 * }
	 * @return array Mảng tham số đã được phân tích.
	 */
	public static function parse_settings( $editor_id, $settings ) {

		/**
		 * Lọc các cài đặt wp_editor().
		 *
		 * @since 4.0.0
		 *
		 * @see _WP_Editors::parse_settings()
		 *
		 * @param array  $settings  Mảng các tham số trình soạn thảo.
		 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
		 *                          khi được gọi từ khối Classic của trình soạn thảo khối.
		 */
		$settings = apply_filters( 'wp_editor_settings', $settings, $editor_id );

		$set = wp_parse_args(
			$settings,
			array(
				// Tắt autop nếu bài viết hiện tại có chứa các khối.
				'wpautop'             => ! has_blocks(),
				'media_buttons'       => true,
				'default_editor'      => '',
				'drag_drop_upload'    => false,
				'textarea_name'       => $editor_id,
				'textarea_rows'       => 20,
				'tabindex'            => '',
				'tabfocus_elements'   => ':prev,:next',
				'editor_css'          => '',
				'editor_class'        => '',
				'teeny'               => false,
				'_content_editor_dfw' => false,
				'tinymce'             => true,
				'quicktags'           => true,
			)
		);

		self::$this_tinymce = ( $set['tinymce'] && user_can_richedit() );

		if ( self::$this_tinymce ) {
			if ( str_contains( $editor_id, '[' ) ) {
				self::$this_tinymce = false;
				_deprecated_argument( 'wp_editor()', '3.9.0', 'TinyMCE editor IDs cannot have brackets.' );
			}
		}

		self::$this_quicktags = (bool) $set['quicktags'];

		if ( self::$this_tinymce ) {
			self::$has_tinymce = true;
		}

		if ( self::$this_quicktags ) {
			self::$has_quicktags = true;
		}

		if ( empty( $set['editor_height'] ) ) {
			return $set;
		}

		if ( 'content' === $editor_id && empty( $set['tinymce']['wp_autoresize_on'] ) ) {
			// Một cookie (được đặt khi người dùng thay đổi kích thước trình soạn thảo) ghi đè chiều cao.
			$cookie = (int) get_user_setting( 'ed_size' );

			if ( $cookie ) {
				$set['editor_height'] = $cookie;
			}
		}

		if ( $set['editor_height'] < 50 ) {
			$set['editor_height'] = 50;
		} elseif ( $set['editor_height'] > 5000 ) {
			$set['editor_height'] = 5000;
		}

		return $set;
	}

	/**
	 * Xuất HTML cho một phiên bản duy nhất của trình soạn thảo.
	 *
	 * @since 3.3.0
	 *
	 * @global WP_Screen $current_screen Đối tượng màn hình hiện tại của WordPress.
	 *
	 * @param string $content   Nội dung ban đầu cho trình soạn thảo.
	 * @param string $editor_id ID HTML cho textarea và các phiên bản TinyMCE và Quicktags.
	 *                          Không nên chứa dấu ngoặc vuông.
	 * @param array  $settings  Xem _WP_Editors::parse_settings() để biết mô tả.
	 */
	public static function editor( $content, $editor_id, $settings = array() ) {
		$set            = self::parse_settings( $editor_id, $settings );
		$editor_class   = ' class="' . trim( esc_attr( $set['editor_class'] ) . ' wp-editor-area' ) . '"';
		$tabindex       = $set['tabindex'] ? ' tabindex="' . (int) $set['tabindex'] . '"' : '';
		$default_editor = 'html';
		$buttons        = '';
		$autocomplete   = '';
		$editor_id_attr = esc_attr( $editor_id );

		if ( $set['drag_drop_upload'] ) {
			self::$drag_drop_upload = true;
		}

		if ( ! empty( $set['editor_height'] ) ) {
			$height = ' style="height: ' . (int) $set['editor_height'] . 'px"';
		} else {
			$height = ' rows="' . (int) $set['textarea_rows'] . '"';
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			$set['media_buttons'] = false;
		}

		if ( self::$this_tinymce ) {
			$autocomplete = ' autocomplete="off"';

			if ( self::$this_quicktags ) {
				$default_editor = $set['default_editor'] ? $set['default_editor'] : wp_default_editor();
				// 'html' được sử dụng cho tab trình soạn thảo "Code".
				if ( 'html' !== $default_editor ) {
					$default_editor = 'tinymce';
				}
				$tmce_active = ( 'html' === $default_editor ) ? ' aria-pressed="true"' : '';
				$html_active = ( 'html' === $default_editor ) ? '' : ' aria-pressed="true"';

				$buttons .= '<button type="button" id="' . $editor_id_attr . '-tmce"' . $html_active . ' class="wp-switch-editor switch-tmce"' .
					' data-wp-editor-id="' . $editor_id_attr . '">' . _x( 'Visual', 'Name for the Visual editor tab' ) . "</button>\n";
				$buttons .= '<button type="button" id="' . $editor_id_attr . '-html"' . $tmce_active . ' class="wp-switch-editor switch-html"' .
					' data-wp-editor-id="' . $editor_id_attr . '">' . _x( 'Code', 'Name for the Code editor tab (formerly Text)' ) . "</button>\n";
			} else {
				$default_editor = 'tinymce';
			}
		}

		$switch_class = 'html' === $default_editor ? 'html-active' : 'tmce-active';
		$wrap_class   = 'wp-core-ui wp-editor-wrap ' . $switch_class;

		if ( $set['_content_editor_dfw'] ) {
			$wrap_class .= ' has-dfw';
		}

		echo '<div id="wp-' . $editor_id_attr . '-wrap" class="' . $wrap_class . '">';

		if ( self::$editor_buttons_css ) {
			wp_print_styles( 'editor-buttons' );
			self::$editor_buttons_css = false;
		}

		if ( ! empty( $set['editor_css'] ) ) {
			echo $set['editor_css'] . "\n";
		}

		if ( ! empty( $buttons ) || $set['media_buttons'] ) {
			echo '<div id="wp-' . $editor_id_attr . '-editor-tools" class="wp-editor-tools hide-if-no-js">';

			if ( $set['media_buttons'] ) {
				self::$has_medialib = true;

				if ( ! function_exists( 'media_buttons' ) ) {
					require ABSPATH . 'wp-admin/includes/media.php';
				}

				echo '<div id="wp-' . $editor_id_attr . '-media-buttons" class="wp-media-buttons">';

				/**
				 * Kích hoạt sau khi (các) nút media mặc định được hiển thị.
				 *
				 * @since 2.5.0
				 *
				 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
				 */
				do_action( 'media_buttons', $editor_id );
				echo "</div>\n";
			}

			echo '<div class="wp-editor-tabs">' . $buttons . "</div>\n";
			echo "</div>\n";
		}

		$quicktags_toolbar = '';

		if ( self::$this_quicktags ) {
			if ( 'content' === $editor_id && ! empty( $GLOBALS['current_screen'] ) && 'post' === $GLOBALS['current_screen']->base ) {
				$toolbar_id = 'ed_toolbar';
			} else {
				$toolbar_id = 'qt_' . $editor_id_attr . '_toolbar';
			}

			$quicktags_toolbar = '<div id="' . $toolbar_id . '" class="quicktags-toolbar hide-if-no-js"></div>';
		}

		/**
		 * Lọc đầu ra HTML hiển thị trình soạn thảo.
		 *
		 * @since 2.1.0
		 *
		 * @param string $output Mã HTML của trình soạn thảo.
		 */
		$the_editor = apply_filters(
			'the_editor',
			'<div id="wp-' . $editor_id_attr . '-editor-container" class="wp-editor-container">' .
			$quicktags_toolbar .
			'<textarea' . $editor_class . $height . $tabindex . $autocomplete . ' cols="40" name="' . esc_attr( $set['textarea_name'] ) . '" ' .
			'id="' . $editor_id_attr . '">%s</textarea></div>'
		);

		// Chuẩn bị nội dung cho trình soạn thảo Trực quan hoặc Code, chỉ khi TinyMCE được sử dụng (tương thích ngược).
		if ( self::$this_tinymce ) {
			add_filter( 'the_editor_content', 'format_for_editor', 10, 2 );
		}

		/**
		 * Lọc nội dung mặc định của trình soạn thảo.
		 *
		 * @since 2.1.0
		 *
		 * @param string $content        Nội dung mặc định của trình soạn thảo.
		 * @param string $default_editor Trình soạn thảo mặc định cho người dùng hiện tại.
		 *                               Là 'html' hoặc 'tinymce'.
		 */
		$content = apply_filters( 'the_editor_content', $content, $default_editor );

		// Xóa bộ lọc vì trình soạn thảo tiếp theo trên cùng trang có thể không cần nó.
		if ( self::$this_tinymce ) {
			remove_filter( 'the_editor_content', 'format_for_editor' );
		}

		// Tương thích ngược cho các bộ lọc `htmledit_pre` và `richedit_pre`.
		if ( 'html' === $default_editor && has_filter( 'htmledit_pre' ) ) {
			/** Bộ lọc này được ghi tài liệu trong wp-includes/deprecated.php */
			$content = apply_filters_deprecated( 'htmledit_pre', array( $content ), '4.3.0', 'format_for_editor' );
		} elseif ( 'tinymce' === $default_editor && has_filter( 'richedit_pre' ) ) {
			/** Bộ lọc này được ghi tài liệu trong wp-includes/deprecated.php */
			$content = apply_filters_deprecated( 'richedit_pre', array( $content ), '4.3.0', 'format_for_editor' );
		}

		if ( false !== stripos( $content, 'textarea' ) ) {
			$content = preg_replace( '%</textarea%i', '&lt;/textarea', $content );
		}

		printf( $the_editor, $content );
		echo "\n</div>\n\n";

		self::editor_settings( $editor_id, $set );
	}

	/**
	 * @since 3.3.0
	 *
	 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
	 * @param array  $set       Mảng các tham số trình soạn thảo.
	 */
	public static function editor_settings( $editor_id, $set ) {
		if ( empty( self::$first_init ) ) {
			if ( is_admin() ) {
				add_action( 'admin_print_footer_scripts', array( __CLASS__, 'editor_js' ), 50 );
				add_action( 'admin_print_footer_scripts', array( __CLASS__, 'force_uncompressed_tinymce' ), 1 );
				add_action( 'admin_print_footer_scripts', array( __CLASS__, 'enqueue_scripts' ), 1 );
			} else {
				add_action( 'wp_print_footer_scripts', array( __CLASS__, 'editor_js' ), 50 );
				add_action( 'wp_print_footer_scripts', array( __CLASS__, 'force_uncompressed_tinymce' ), 1 );
				add_action( 'wp_print_footer_scripts', array( __CLASS__, 'enqueue_scripts' ), 1 );
			}
		}

		if ( self::$this_quicktags ) {

			$qt_init = array(
				'id'      => $editor_id,
				'buttons' => '',
			);

			if ( is_array( $set['quicktags'] ) ) {
				$qt_init = array_merge( $qt_init, $set['quicktags'] );
			}

			if ( empty( $qt_init['buttons'] ) ) {
				$qt_init['buttons'] = 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close';
			}

			if ( $set['_content_editor_dfw'] ) {
				$qt_init['buttons'] .= ',dfw';
			}

			/**
			 * Lọc các cài đặt Quicktags.
			 *
			 * @since 3.3.0
			 *
			 * @param array  $qt_init   Cài đặt Quicktags.
			 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
			 */
			$qt_init = apply_filters( 'quicktags_settings', $qt_init, $editor_id );

			self::$qt_settings[ $editor_id ] = $qt_init;

			self::$qt_buttons = array_merge( self::$qt_buttons, explode( ',', $qt_init['buttons'] ) );
		}

		if ( self::$this_tinymce ) {

			if ( empty( self::$first_init ) ) {
				$baseurl     = self::get_baseurl();
				$mce_locale  = self::get_mce_locale();
				$ext_plugins = '';

				if ( $set['teeny'] ) {

					/**
					 * Lọc danh sách các plugin teenyMCE.
					 *
					 * @since 2.7.0
					 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
					 *
					 * @param array  $plugins   Mảng các plugin teenyMCE.
					 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
					 */
					$plugins = apply_filters(
						'teeny_mce_plugins',
						array(
							'colorpicker',
							'lists',
							'fullscreen',
							'image',
							'wordpress',
							'wpeditimage',
							'wplink',
						),
						$editor_id
					);
				} else {

					/**
					 * Lọc danh sách các plugin TinyMCE bên ngoài.
					 *
					 * Bộ lọc nhận một mảng kết hợp các plugin bên ngoài cho
					 * TinyMCE dưới dạng 'tên_plugin' => 'url'.
					 *
					 * URL phải là đường dẫn tuyệt đối, và phải bao gồm tên file js
					 * cần tải. Ví dụ:
					 * 'myplugin' => 'http://mysite.com/wp-content/plugins/myfolder/mce_plugin.js'.
					 *
					 * Nếu plugin bên ngoài thêm một nút, nó nên được thêm bằng
					 * một trong các bộ lọc 'mce_buttons'.
					 *
					 * @since 2.5.0
					 * @since 5.3.0 Tham số `$editor_id` đã được thêm.
					 *
					 * @param array  $external_plugins Mảng các plugin TinyMCE bên ngoài.
					 * @param string $editor_id        Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
					 *                                 khi được gọi từ khối Classic của trình soạn thảo khối.
					 */
					$mce_external_plugins = apply_filters( 'mce_external_plugins', array(), $editor_id );

					$plugins = array(
						'charmap',
						'colorpicker',
						'hr',
						'lists',
						'media',
						'paste',
						'tabfocus',
						'textcolor',
						'fullscreen',
						'wordpress',
						'wpautoresize',
						'wpeditimage',
						'wpemoji',
						'wpgallery',
						'wplink',
						'wpdialogs',
						'wptextpattern',
						'wpview',
					);

					if ( ! self::$has_medialib ) {
						$plugins[] = 'image';
					}

					/**
					 * Lọc danh sách các plugin TinyMCE mặc định.
					 *
					 * Bộ lọc xác định plugin mặc định nào đã được bao gồm
					 * trong WordPress sẽ được thêm vào phiên bản TinyMCE.
					 *
					 * @since 3.3.0
					 * @since 5.3.0 Tham số `$editor_id` đã được thêm.
					 *
					 * @param array  $plugins   Mảng các plugin TinyMCE mặc định.
					 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
					 *                          khi được gọi từ khối Classic của trình soạn thảo khối.
					 */
					$plugins = array_unique( apply_filters( 'tiny_mce_plugins', $plugins, $editor_id ) );

					$key = array_search( 'spellchecker', $plugins, true );
					if ( false !== $key ) {
						/*
						 * Xóa 'spellchecker' khỏi các plugin nội bộ nếu được thêm bằng bộ lọc 'tiny_mce_plugins' để ngăn lỗi.
						 * Nó có thể được thêm bằng 'mce_external_plugins'.
						 */
						unset( $plugins[ $key ] );
					}

					if ( ! empty( $mce_external_plugins ) ) {

						/**
						 * Lọc các bản dịch được tải cho các plugin TinyMCE 3.x bên ngoài.
						 *
						 * Bộ lọc nhận một mảng kết hợp ('tên_plugin' => 'đường_dẫn')
						 * trong đó 'đường_dẫn' là đường dẫn include tới file.
						 *
						 * File ngôn ngữ nên theo cùng định dạng như wp_mce_translation(),
						 * và nên định nghĩa một biến ($strings) chứa tất cả các chuỗi đã dịch.
						 *
						 * @since 2.5.0
						 * @since 5.3.0 Tham số `$editor_id` đã được thêm.
						 *
						 * @param array  $translations Các bản dịch cho các plugin TinyMCE bên ngoài.
						 * @param string $editor_id    Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
						 */
						$mce_external_languages = apply_filters( 'mce_external_languages', array(), $editor_id );

						$loaded_langs = array();
						$strings      = '';

						if ( ! empty( $mce_external_languages ) ) {
							foreach ( $mce_external_languages as $name => $path ) {
								if ( @is_file( $path ) && @is_readable( $path ) ) {
									include_once $path;
									$ext_plugins   .= $strings . "\n";
									$loaded_langs[] = $name;
								}
							}
						}

						foreach ( $mce_external_plugins as $name => $url ) {
							if ( in_array( $name, $plugins, true ) ) {
								unset( $mce_external_plugins[ $name ] );
								continue;
							}

							$url                           = set_url_scheme( $url );
							$mce_external_plugins[ $name ] = $url;
							$plugurl                       = dirname( $url );
							$strings                       = '';

							// Thử tải langs/[locale].js và langs/[locale]_dlg.js.
							if ( ! in_array( $name, $loaded_langs, true ) ) {
								$path = str_replace( content_url(), '', $plugurl );
								$path = realpath( WP_CONTENT_DIR . $path . '/langs/' );

								if ( ! $path ) {
									continue;
								}

								$path = trailingslashit( $path );

								if ( @is_file( $path . $mce_locale . '.js' ) ) {
									$strings .= @file_get_contents( $path . $mce_locale . '.js' ) . "\n";
								}

								if ( @is_file( $path . $mce_locale . '_dlg.js' ) ) {
									$strings .= @file_get_contents( $path . $mce_locale . '_dlg.js' ) . "\n";
								}

								if ( 'en' !== $mce_locale && empty( $strings ) ) {
									if ( @is_file( $path . 'en.js' ) ) {
										$str1     = @file_get_contents( $path . 'en.js' );
										$strings .= preg_replace( '/([\'"])en\./', '$1' . $mce_locale . '.', $str1, 1 ) . "\n";
									}

									if ( @is_file( $path . 'en_dlg.js' ) ) {
										$str2     = @file_get_contents( $path . 'en_dlg.js' );
										$strings .= preg_replace( '/([\'"])en\./', '$1' . $mce_locale . '.', $str2, 1 ) . "\n";
									}
								}

								if ( ! empty( $strings ) ) {
									$ext_plugins .= "\n" . $strings . "\n";
								}
							}

							$ext_plugins .= 'tinyMCEPreInit.load_ext("' . $plugurl . '", "' . $mce_locale . '");' . "\n";
						}
					}
				}

				self::$plugins     = $plugins;
				self::$ext_plugins = $ext_plugins;

				$settings            = self::default_settings();
				$settings['plugins'] = implode( ',', $plugins );

				if ( ! empty( $mce_external_plugins ) ) {
					$settings['external_plugins'] = wp_json_encode( $mce_external_plugins );
				}

				/** Bộ lọc này được ghi tài liệu trong wp-admin/includes/media.php */
				if ( apply_filters( 'disable_captions', '' ) ) {
					$settings['wpeditimage_disable_captions'] = true;
				}

				$mce_css = $settings['content_css'];

				/*
				 * `editor-style.css` được thêm bởi theme thường dành cho phiên bản trình soạn thảo trên màn hình Chỉnh sửa Bài viết.
				 * Các plugin sử dụng wp_editor() ở front-end có thể quyết định có thêm stylesheet của theme hay không
				 * bằng cách sử dụng `get_editor_stylesheets()` và các bộ lọc `mce_css` hoặc `tiny_mce_before_init`, xem bên dưới.
				 */
				if ( is_admin() ) {
					$editor_styles = get_editor_stylesheets();

					if ( ! empty( $editor_styles ) ) {
						// Bắt buộc mã hóa URL cho dấu phẩy.
						foreach ( $editor_styles as $key => $url ) {
							if ( str_contains( $url, ',' ) ) {
								$editor_styles[ $key ] = str_replace( ',', '%2C', $url );
							}
						}

						$mce_css .= ',' . implode( ',', $editor_styles );
					}
				}

				/**
				 * Lọc danh sách các stylesheet được phân cách bằng dấu phẩy để tải trong TinyMCE.
				 *
				 * @since 2.1.0
				 *
				 * @param string $stylesheets Danh sách các stylesheet phân cách bằng dấu phẩy.
				 */
				$mce_css = trim( apply_filters( 'mce_css', $mce_css ), ' ,' );

				if ( ! empty( $mce_css ) ) {
					$settings['content_css'] = $mce_css;
				} else {
					unset( $settings['content_css'] );
				}

				self::$first_init = $settings;
			}

			if ( $set['teeny'] ) {
				$mce_buttons = array(
					'bold',
					'italic',
					'underline',
					'blockquote',
					'strikethrough',
					'bullist',
					'numlist',
					'alignleft',
					'aligncenter',
					'alignright',
					'undo',
					'redo',
					'link',
					'fullscreen',
				);

				/**
				 * Lọc danh sách các nút teenyMCE (tab Code).
				 *
				 * @since 2.7.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_buttons Mảng các nút teenyMCE.
				 * @param string $editor_id   Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
				 */
				$mce_buttons   = apply_filters( 'teeny_mce_buttons', $mce_buttons, $editor_id );
				$mce_buttons_2 = array();
				$mce_buttons_3 = array();
				$mce_buttons_4 = array();
			} else {
				$mce_buttons = array(
					'formatselect',
					'bold',
					'italic',
					'bullist',
					'numlist',
					'blockquote',
					'alignleft',
					'aligncenter',
					'alignright',
					'link',
					'wp_more',
					'spellchecker',
				);

				if ( ! wp_is_mobile() ) {
					if ( $set['_content_editor_dfw'] ) {
						$mce_buttons[] = 'wp_adv';
						$mce_buttons[] = 'dfw';
					} else {
						$mce_buttons[] = 'fullscreen';
						$mce_buttons[] = 'wp_adv';
					}
				} else {
					$mce_buttons[] = 'wp_adv';
				}

				/**
				 * Lọc danh sách nút TinyMCE hàng đầu tiên (tab Trực quan).
				 *
				 * @since 2.0.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_buttons Danh sách nút hàng đầu tiên.
				 * @param string $editor_id   Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
				 *                            khi được gọi từ khối Classic của trình soạn thảo khối.
				 */
				$mce_buttons = apply_filters( 'mce_buttons', $mce_buttons, $editor_id );

				$mce_buttons_2 = array(
					'strikethrough',
					'hr',
					'forecolor',
					'pastetext',
					'removeformat',
					'charmap',
					'outdent',
					'indent',
					'undo',
					'redo',
				);

				if ( ! wp_is_mobile() ) {
					$mce_buttons_2[] = 'wp_help';
				}

				/**
				 * Lọc danh sách nút TinyMCE hàng thứ hai (tab Trực quan).
				 *
				 * @since 2.0.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_buttons_2 Danh sách nút hàng thứ hai.
				 * @param string $editor_id     Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
				 *                              khi được gọi từ khối Classic của trình soạn thảo khối.
				 */
				$mce_buttons_2 = apply_filters( 'mce_buttons_2', $mce_buttons_2, $editor_id );

				/**
				 * Lọc danh sách nút TinyMCE hàng thứ ba (tab Trực quan).
				 *
				 * @since 2.0.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_buttons_3 Danh sách nút hàng thứ ba.
				 * @param string $editor_id     Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
				 *                              khi được gọi từ khối Classic của trình soạn thảo khối.
				 */
				$mce_buttons_3 = apply_filters( 'mce_buttons_3', array(), $editor_id );

				/**
				 * Lọc danh sách nút TinyMCE hàng thứ tư (tab Trực quan).
				 *
				 * @since 2.5.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_buttons_4 Danh sách nút hàng thứ tư.
				 * @param string $editor_id     Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
				 *                              khi được gọi từ khối Classic của trình soạn thảo khối.
				 */
				$mce_buttons_4 = apply_filters( 'mce_buttons_4', array(), $editor_id );
			}

			$body_class = $editor_id;

			$post = get_post();
			if ( $post ) {
				$body_class .= ' post-type-' . sanitize_html_class( $post->post_type ) . ' post-status-' . sanitize_html_class( $post->post_status );

				if ( post_type_supports( $post->post_type, 'post-formats' ) ) {
					$post_format = get_post_format( $post );
					if ( $post_format && ! is_wp_error( $post_format ) ) {
						$body_class .= ' post-format-' . sanitize_html_class( $post_format );
					} else {
						$body_class .= ' post-format-standard';
					}
				}

				$page_template = get_page_template_slug( $post );

				if ( false !== $page_template ) {
					$page_template = empty( $page_template ) ? 'default' : str_replace( '.', '-', basename( $page_template, '.php' ) );
					$body_class   .= ' page-template-' . sanitize_html_class( $page_template );
				}
			}

			$body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

			if ( ! empty( $set['tinymce']['body_class'] ) ) {
				$body_class .= ' ' . $set['tinymce']['body_class'];
				unset( $set['tinymce']['body_class'] );
			}

			$mce_init = array(
				'selector'          => "#$editor_id",
				'wpautop'           => (bool) $set['wpautop'],
				'indent'            => ! $set['wpautop'],
				'toolbar1'          => implode( ',', $mce_buttons ),
				'toolbar2'          => implode( ',', $mce_buttons_2 ),
				'toolbar3'          => implode( ',', $mce_buttons_3 ),
				'toolbar4'          => implode( ',', $mce_buttons_4 ),
				'tabfocus_elements' => $set['tabfocus_elements'],
				'body_class'        => $body_class,
			);

			// Gộp với phần đầu tiên của mảng khởi tạo.
			$mce_init = array_merge( self::$first_init, $mce_init );

			if ( is_array( $set['tinymce'] ) ) {
				$mce_init = array_merge( $mce_init, $set['tinymce'] );
			}

			/*
			 * Dành cho những người THỰC SỰ biết họ đang làm gì với TinyMCE.
			 * Bạn có thể chỉnh sửa $mceInit để thêm, xóa, thay đổi các phần tử của cấu hình
			 * trước khi tinyMCE.init. Thiết lập "valid_elements", "invalid_elements"
			 * và "extended_valid_elements" có thể được thực hiện thông qua bộ lọc này. Tốt nhất
			 * là sử dụng dọn dẹp mặc định bằng cách không chỉ định valid_elements,
			 * vì TinyMCE kiểm tra với toàn bộ tập hợp các phần tử và thuộc tính HTML 5.0.
			 */
			if ( $set['teeny'] ) {

				/**
				 * Lọc cấu hình teenyMCE trước khi khởi tạo.
				 *
				 * @since 2.7.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_init  Mảng cấu hình teenyMCE.
				 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'.
				 */
				$mce_init = apply_filters( 'teeny_mce_before_init', $mce_init, $editor_id );
			} else {

				/**
				 * Lọc cấu hình TinyMCE trước khi khởi tạo.
				 *
				 * @since 2.5.0
				 * @since 3.3.0 Tham số `$editor_id` đã được thêm.
				 *
				 * @param array  $mce_init  Mảng cấu hình TinyMCE.
				 * @param string $editor_id Mã định danh trình soạn thảo duy nhất, ví dụ 'content'. Chấp nhận 'classic-block'
				 *                          khi được gọi từ khối Classic của trình soạn thảo khối.
				 */
				$mce_init = apply_filters( 'tiny_mce_before_init', $mce_init, $editor_id );
			}

			if ( empty( $mce_init['toolbar3'] ) && ! empty( $mce_init['toolbar4'] ) ) {
				$mce_init['toolbar3'] = $mce_init['toolbar4'];
				$mce_init['toolbar4'] = '';
			}

			self::$mce_settings[ $editor_id ] = $mce_init;
		} // Kết thúc if self::$this_tinymce.
	}

	/**
	 * @since 3.3.0
	 *
	 * @param array $init Mảng cấu hình cần phân tích.
	 * @return string Chuỗi cấu hình đã phân tích.
	 */
	private static function _parse_init( $init ) {
		$options = '';

		foreach ( $init as $key => $value ) {
			if ( is_bool( $value ) ) {
				$val      = $value ? 'true' : 'false';
				$options .= $key . ':' . $val . ',';
				continue;
			} elseif ( ! empty( $value ) && is_string( $value ) && (
				( '{' === $value[0] && '}' === $value[ strlen( $value ) - 1 ] ) ||
				( '[' === $value[0] && ']' === $value[ strlen( $value ) - 1 ] ) ||
				preg_match( '/^\(?function ?\(/', $value ) ) ) {

				$options .= $key . ':' . $value . ',';
				continue;
			}
			$options .= $key . ':"' . $value . '",';
		}

		return '{' . trim( $options, ' ,' ) . '}';
	}

	/**
	 * @since 3.3.0
	 *
	 * @param bool $default_scripts Tùy chọn. Có nên nạp các script mặc định hay không. Mặc định false.
	 */
	public static function enqueue_scripts( $default_scripts = false ) {
		if ( $default_scripts || self::$has_tinymce ) {
			wp_enqueue_script( 'editor' );
		}

		if ( $default_scripts || self::$has_quicktags ) {
			wp_enqueue_script( 'quicktags' );
			wp_enqueue_style( 'buttons' );
		}

		if ( $default_scripts || in_array( 'wplink', self::$plugins, true ) || in_array( 'link', self::$qt_buttons, true ) ) {
			wp_enqueue_script( 'wplink' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
		}

		if ( self::$has_medialib ) {
			add_thickbox();
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'wp-embed' );
		} elseif ( $default_scripts ) {
			wp_enqueue_script( 'media-upload' );
		}

		/**
		 * Kích hoạt khi các script và style được nạp cho trình soạn thảo.
		 *
		 * @since 3.9.0
		 *
		 * @param array $to_load Mảng chứa các giá trị boolean cho biết TinyMCE
		 *                       và Quicktags có đang được tải hay không.
		 */
		do_action(
			'wp_enqueue_editor',
			array(
				'tinymce'   => ( $default_scripts || self::$has_tinymce ),
				'quicktags' => ( $default_scripts || self::$has_quicktags ),
			)
		);
	}

	/**
	 * Nạp tất cả các script trình soạn thảo.
	 * Dùng khi trình soạn thảo sẽ được khởi tạo sau khi trang đã tải.
	 *
	 * @since 4.8.0
	 */
	public static function enqueue_default_editor() {
		// Chúng ta đã qua thời điểm mà các script có thể được nạp đúng cách.
		if ( did_action( 'wp_enqueue_editor' ) ) {
			return;
		}

		self::enqueue_scripts( true );

		// Cũng thêm wp-includes/css/editor.css.
		wp_enqueue_style( 'editor-buttons' );

		if ( is_admin() ) {
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'force_uncompressed_tinymce' ), 1 );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_default_editor_scripts' ), 45 );
		} else {
			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'force_uncompressed_tinymce' ), 1 );
			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'print_default_editor_scripts' ), 45 );
		}
	}

	/**
	 * In (xuất) tất cả các script trình soạn thảo và cài đặt mặc định.
	 * Dùng khi trình soạn thảo sẽ được khởi tạo sau khi trang đã tải.
	 *
	 * @since 4.8.0
	 */
	public static function print_default_editor_scripts() {
		$user_can_richedit = user_can_richedit();

		if ( $user_can_richedit ) {
			$settings = self::default_settings();

			$settings['toolbar1']    = 'bold,italic,bullist,numlist,link';
			$settings['wpautop']     = false;
			$settings['indent']      = true;
			$settings['elementpath'] = false;

			if ( is_rtl() ) {
				$settings['directionality'] = 'rtl';
			}

			/*
			 * Trong môi trường sản xuất, tất cả các plugin được tải (chúng nằm trong wp-editor.js.gz).
			 * Các plugin TinyMCE 'wpview', 'wpdialogs', và 'media' không được khởi tạo mặc định.
			 * Có thể được thêm từ js bằng cách sử dụng sự kiện 'wp-before-tinymce-init'.
			 */
			$settings['plugins'] = implode(
				',',
				array(
					'charmap',
					'colorpicker',
					'hr',
					'lists',
					'paste',
					'tabfocus',
					'textcolor',
					'fullscreen',
					'wordpress',
					'wpautoresize',
					'wpeditimage',
					'wpemoji',
					'wpgallery',
					'wplink',
					'wptextpattern',
				)
			);

			$settings = self::_parse_init( $settings );
		} else {
			$settings = '{}';
		}

		?>
		<script type="text/javascript">
		window.wp = window.wp || {};
		window.wp.editor = window.wp.editor || {};
		window.wp.editor.getDefaultSettings = function() {
			return {
				tinymce: <?php echo $settings; ?>,
				quicktags: {
					buttons: 'strong,em,link,ul,ol,li,code'
				}
			};
		};

		<?php

		if ( $user_can_richedit ) {
			$suffix  = SCRIPT_DEBUG ? '' : '.min';
			$baseurl = self::get_baseurl();

			?>
			var tinyMCEPreInit = {
				baseURL: "<?php echo $baseurl; ?>",
				suffix: "<?php echo $suffix; ?>",
				mceInit: {},
				qtInit: {},
				load_ext: function(url,lang){var sl=tinymce.ScriptLoader;sl.markDone(url+'/langs/'+lang+'.js');sl.markDone(url+'/langs/'+lang+'_dlg.js');}
			};
			<?php
		}
		?>
		</script>
		<?php

		if ( $user_can_richedit ) {
			self::print_tinymce_scripts();
		}

		/**
		 * Kích hoạt khi các script trình soạn thảo được tải để khởi tạo sau,
		 * sau khi tất cả các script và cài đặt đã được in.
		 *
		 * @since 4.8.0
		 */
		do_action( 'print_default_editor_scripts' );

		self::wp_link_dialog();
	}

	/**
	 * Trả về locale của TinyMCE.
	 *
	 * @since 4.8.0
	 *
	 * @return string Mã locale.
	 */
	public static function get_mce_locale() {
		if ( empty( self::$mce_locale ) ) {
			$mce_locale       = get_user_locale();
			self::$mce_locale = empty( $mce_locale ) ? 'en' : strtolower( substr( $mce_locale, 0, 2 ) ); // ISO 639-1.
		}

		return self::$mce_locale;
	}

	/**
	 * Trả về URL gốc của TinyMCE.
	 *
	 * @since 4.8.0
	 *
	 * @return string URL gốc.
	 */
	public static function get_baseurl() {
		if ( empty( self::$baseurl ) ) {
			self::$baseurl = includes_url( 'js/tinymce' );
		}

		return self::$baseurl;
	}

	/**
	 * Trả về các cài đặt TinyMCE mặc định.
	 * Không bao gồm plugin, nút, bộ chọn trình soạn thảo.
	 *
	 * @since 4.8.0
	 *
	 * @global string $tinymce_version
	 *
	 * @return array Mảng cài đặt mặc định.
	 */
	private static function default_settings() {
		global $tinymce_version;

		$shortcut_labels = array();

		foreach ( self::get_translation() as $name => $value ) {
			if ( is_array( $value ) ) {
				$shortcut_labels[ $name ] = $value[1];
			}
		}

		$settings = array(
			'theme'                        => 'modern',
			'skin'                         => 'lightgray',
			'language'                     => self::get_mce_locale(),
			'formats'                      => '{' .
				'alignleft: [' .
					'{selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign:"left"}},' .
					'{selector: "img,table,dl.wp-caption", classes: "alignleft"}' .
				'],' .
				'aligncenter: [' .
					'{selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign:"center"}},' .
					'{selector: "img,table,dl.wp-caption", classes: "aligncenter"}' .
				'],' .
				'alignright: [' .
					'{selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign:"right"}},' .
					'{selector: "img,table,dl.wp-caption", classes: "alignright"}' .
				'],' .
				'strikethrough: {inline: "del"}' .
			'}',
			'relative_urls'                => false,
			'remove_script_host'           => false,
			'convert_urls'                 => false,
			'browser_spellcheck'           => true,
			'fix_list_elements'            => true,
			'entities'                     => '38,amp,60,lt,62,gt',
			'entity_encoding'              => 'raw',
			'keep_styles'                  => false,
			'cache_suffix'                 => 'wp-mce-' . $tinymce_version,
			'resize'                       => 'vertical',
			'menubar'                      => false,
			'branding'                     => false,

			// Giới hạn các style xem trước trong menu/thanh công cụ.
			'preview_styles'               => 'font-family font-size font-weight font-style text-decoration text-transform',

			'end_container_on_empty_block' => true,
			'wpeditimage_html5_captions'   => true,
			'wp_lang_attr'                 => get_bloginfo( 'language' ),
			'wp_shortcut_labels'           => wp_json_encode( $shortcut_labels ),
		);

		$suffix  = SCRIPT_DEBUG ? '' : '.min';
		$version = 'ver=' . get_bloginfo( 'version' );

		// Các stylesheet mặc định.
		$settings['content_css'] = includes_url( "css/dashicons$suffix.css?$version" ) . ',' .
			includes_url( "js/tinymce/skins/wordpress/wp-content.css?$version" );

		return $settings;
	}

	/**
	 * @since 4.7.0
	 *
	 * @return array Mảng bản dịch.
	 */
	private static function get_translation() {
		if ( empty( self::$translation ) ) {
			self::$translation = array(
				// Các chuỗi TinyMCE mặc định.
				'New document'                         => __( 'New document' ),
				'Formats'                              => _x( 'Formats', 'TinyMCE' ),

				'Headings'                             => _x( 'Headings', 'TinyMCE' ),
				'Heading 1'                            => array( __( 'Heading 1' ), 'access1' ),
				'Heading 2'                            => array( __( 'Heading 2' ), 'access2' ),
				'Heading 3'                            => array( __( 'Heading 3' ), 'access3' ),
				'Heading 4'                            => array( __( 'Heading 4' ), 'access4' ),
				'Heading 5'                            => array( __( 'Heading 5' ), 'access5' ),
				'Heading 6'                            => array( __( 'Heading 6' ), 'access6' ),

				/* translators: Block tags. */
				'Blocks'                               => _x( 'Blocks', 'TinyMCE' ),
				'Paragraph'                            => array( __( 'Paragraph' ), 'access7' ),
				'Blockquote'                           => array( __( 'Blockquote' ), 'accessQ' ),
				'Div'                                  => _x( 'Div', 'HTML tag' ),
				'Pre'                                  => _x( 'Pre', 'HTML tag' ),
				'Preformatted'                         => _x( 'Preformatted', 'HTML tag' ),
				'Address'                              => _x( 'Address', 'HTML tag' ),

				'Inline'                               => _x( 'Inline', 'HTML elements' ),
				'Underline'                            => array( __( 'Underline' ), 'metaU' ),
				'Strikethrough'                        => array( __( 'Strikethrough' ), 'accessD' ),
				'Subscript'                            => __( 'Subscript' ),
				'Superscript'                          => __( 'Superscript' ),
				'Clear formatting'                     => __( 'Clear formatting' ),
				'Bold'                                 => array( __( 'Bold' ), 'metaB' ),
				'Italic'                               => array( __( 'Italic' ), 'metaI' ),
				'Code'                                 => array( __( 'Code' ), 'accessX' ),
				'Source code'                          => __( 'Source code' ),
				'Font Family'                          => __( 'Font Family' ),
				'Font Sizes'                           => __( 'Font Sizes' ),

				'Align center'                         => array( __( 'Align center' ), 'accessC' ),
				'Align right'                          => array( __( 'Align right' ), 'accessR' ),
				'Align left'                           => array( __( 'Align left' ), 'accessL' ),
				'Justify'                              => array( __( 'Justify' ), 'accessJ' ),
				'Increase indent'                      => __( 'Increase indent' ),
				'Decrease indent'                      => __( 'Decrease indent' ),

				'Cut'                                  => array( __( 'Cut' ), 'metaX' ),
				'Copy'                                 => array( __( 'Copy' ), 'metaC' ),
				'Paste'                                => array( __( 'Paste' ), 'metaV' ),
				'Select all'                           => array( __( 'Select all' ), 'metaA' ),
				'Undo'                                 => array( __( 'Undo' ), 'metaZ' ),
				'Redo'                                 => array( __( 'Redo' ), 'metaY' ),

				'Ok'                                   => __( 'OK' ),
				'Cancel'                               => __( 'Cancel' ),
				'Close'                                => __( 'Close' ),
				'Visual aids'                          => __( 'Visual aids' ),

				'Bullet list'                          => array( __( 'Bulleted list' ), 'accessU' ),
				'Numbered list'                        => array( __( 'Numbered list' ), 'accessO' ),
				'Square'                               => _x( 'Square', 'list style' ),
				'Default'                              => _x( 'Default', 'list style' ),
				'Circle'                               => _x( 'Circle', 'list style' ),
				'Disc'                                 => _x( 'Disc', 'list style' ),
				'Lower Greek'                          => _x( 'Lower Greek', 'list style' ),
				'Lower Alpha'                          => _x( 'Lower Alpha', 'list style' ),
				'Upper Alpha'                          => _x( 'Upper Alpha', 'list style' ),
				'Upper Roman'                          => _x( 'Upper Roman', 'list style' ),
				'Lower Roman'                          => _x( 'Lower Roman', 'list style' ),

				// Plugin Anchor (neo).
				'Name'                                 => _x( 'Name', 'Name of link anchor (TinyMCE)' ),
				'Anchor'                               => _x( 'Anchor', 'Link anchor (TinyMCE)' ),
				'Anchors'                              => _x( 'Anchors', 'Link anchors (TinyMCE)' ),
				'Id should start with a letter, followed only by letters, numbers, dashes, dots, colons or underscores.' =>
					__( 'Id should start with a letter, followed only by letters, numbers, dashes, dots, colons or underscores.' ),
				'Id'                                   => _x( 'Id', 'Id for link anchor (TinyMCE)' ),

				// Plugin Fullpage (toàn trang).
				'Document properties'                  => __( 'Document properties' ),
				'Robots'                               => __( 'Robots' ),
				'Title'                                => __( 'Title' ),
				'Keywords'                             => __( 'Keywords' ),
				'Encoding'                             => __( 'Encoding' ),
				'Description'                          => __( 'Description' ),
				'Author'                               => __( 'Author' ),

				// Các plugin Media, hình ảnh.
				'Image'                                => __( 'Image' ),
				'Insert/edit image'                    => array( __( 'Insert/edit image' ), 'accessM' ),
				'General'                              => __( 'General' ),
				'Advanced'                             => __( 'Advanced' ),
				'Source'                               => __( 'Source' ),
				'Border'                               => __( 'Border' ),
				'Constrain proportions'                => __( 'Constrain proportions' ),
				'Vertical space'                       => __( 'Vertical space' ),
				'Image description'                    => __( 'Image description' ),
				'Style'                                => __( 'Style' ),
				'Dimensions'                           => __( 'Dimensions' ),
				'Insert image'                         => __( 'Insert image' ),
				'Date/time'                            => __( 'Date/time' ),
				'Insert date/time'                     => __( 'Insert date/time' ),
				'Table of Contents'                    => __( 'Table of Contents' ),
				'Insert/Edit code sample'              => __( 'Insert/edit code sample' ),
				'Language'                             => __( 'Language' ),
				'Media'                                => __( 'Media' ),
				'Insert/edit media'                    => __( 'Insert/edit media' ),
				'Poster'                               => __( 'Poster' ),
				'Alternative source'                   => __( 'Alternative source' ),
				'Paste your embed code below:'         => __( 'Paste your embed code below:' ),
				'Insert video'                         => __( 'Insert video' ),
				'Embed'                                => __( 'Embed' ),

				// Mỗi cái dưới đây có một plugin tương ứng.
				'Special character'                    => __( 'Special character' ),
				'Right to left'                        => _x( 'Right to left', 'editor button' ),
				'Left to right'                        => _x( 'Left to right', 'editor button' ),
				'Emoticons'                            => __( 'Emoticons' ),
				'Nonbreaking space'                    => __( 'Nonbreaking space' ),
				'Page break'                           => __( 'Page break' ),
				'Paste as text'                        => __( 'Paste as text' ),
				'Preview'                              => __( 'Preview' ),
				'Print'                                => __( 'Print' ),
				'Save'                                 => __( 'Save' ),
				'Fullscreen'                           => __( 'Fullscreen' ),
				'Horizontal line'                      => __( 'Horizontal line' ),
				'Horizontal space'                     => __( 'Horizontal space' ),
				'Restore last draft'                   => __( 'Restore last draft' ),
				'Insert/edit link'                     => array( __( 'Insert/edit link' ), 'metaK' ),
				'Remove link'                          => array( __( 'Remove link' ), 'accessS' ),

				// Plugin Link (liên kết).
				'Link'                                 => __( 'Link' ),
				'Insert link'                          => __( 'Insert link' ),
				'Target'                               => __( 'Target' ),
				'New window'                           => __( 'New window' ),
				'Text to display'                      => __( 'Text to display' ),
				'Url'                                  => __( 'URL' ),
				'The URL you entered seems to be an email address. Do you want to add the required mailto: prefix?' =>
					__( 'The URL you entered seems to be an email address. Do you want to add the required mailto: prefix?' ),
				'The URL you entered seems to be an external link. Do you want to add the required http:// prefix?' =>
					__( 'The URL you entered seems to be an external link. Do you want to add the required http:// prefix?' ),

				'Color'                                => __( 'Color' ),
				'Custom color'                         => __( 'Custom color' ),
				'Custom...'                            => _x( 'Custom...', 'label for custom color' ), // No ellipsis.
				'No color'                             => __( 'No color' ),
				'R'                                    => _x( 'R', 'Short for red in RGB' ),
				'G'                                    => _x( 'G', 'Short for green in RGB' ),
				'B'                                    => _x( 'B', 'Short for blue in RGB' ),

				// Các plugin kiểm tra chính tả, tìm kiếm/thay thế.
				'Could not find the specified string.' => __( 'Could not find the specified string.' ),
				'Replace'                              => _x( 'Replace', 'find/replace' ),
				'Next'                                 => _x( 'Next', 'find/replace' ),
				/* translators: Previous. */
				'Prev'                                 => _x( 'Prev', 'find/replace' ),
				'Whole words'                          => _x( 'Whole words', 'find/replace' ),
				'Find and replace'                     => __( 'Find and replace' ),
				'Replace with'                         => _x( 'Replace with', 'find/replace' ),
				'Find'                                 => _x( 'Find', 'find/replace' ),
				'Replace all'                          => _x( 'Replace all', 'find/replace' ),
				'Match case'                           => __( 'Match case' ),
				'Spellcheck'                           => __( 'Check Spelling' ),
				'Finish'                               => _x( 'Finish', 'spellcheck' ),
				'Ignore all'                           => _x( 'Ignore all', 'spellcheck' ),
				'Ignore'                               => _x( 'Ignore', 'spellcheck' ),
				'Add to Dictionary'                    => __( 'Add to Dictionary' ),

				// Bảng TinyMCE.
				'Insert table'                         => __( 'Insert table' ),
				'Delete table'                         => __( 'Delete table' ),
				'Table properties'                     => __( 'Table properties' ),
				'Row properties'                       => __( 'Table row properties' ),
				'Cell properties'                      => __( 'Table cell properties' ),
				'Border color'                         => __( 'Border color' ),

				'Row'                                  => __( 'Row' ),
				'Rows'                                 => __( 'Rows' ),
				'Column'                               => __( 'Column' ),
				'Cols'                                 => __( 'Columns' ),
				'Cell'                                 => _x( 'Cell', 'table cell' ),
				'Header cell'                          => __( 'Header cell' ),
				'Header'                               => _x( 'Header', 'table header' ),
				'Body'                                 => _x( 'Body', 'table body' ),
				'Footer'                               => _x( 'Footer', 'table footer' ),

				'Insert row before'                    => __( 'Insert row before' ),
				'Insert row after'                     => __( 'Insert row after' ),
				'Insert column before'                 => __( 'Insert column before' ),
				'Insert column after'                  => __( 'Insert column after' ),
				'Paste row before'                     => __( 'Paste table row before' ),
				'Paste row after'                      => __( 'Paste table row after' ),
				'Delete row'                           => __( 'Delete row' ),
				'Delete column'                        => __( 'Delete column' ),
				'Cut row'                              => __( 'Cut table row' ),
				'Copy row'                             => __( 'Copy table row' ),
				'Merge cells'                          => __( 'Merge table cells' ),
				'Split cell'                           => __( 'Split table cell' ),

				'Height'                               => __( 'Height' ),
				'Width'                                => __( 'Width' ),
				'Caption'                              => __( 'Caption' ),
				'Alignment'                            => __( 'Alignment' ),
				'H Align'                              => _x( 'H Align', 'horizontal table cell alignment' ),
				'Left'                                 => __( 'Left' ),
				'Center'                               => __( 'Center' ),
				'Right'                                => __( 'Right' ),
				'None'                                 => _x( 'None', 'table cell alignment attribute' ),
				'V Align'                              => _x( 'V Align', 'vertical table cell alignment' ),
				'Top'                                  => __( 'Top' ),
				'Middle'                               => __( 'Middle' ),
				'Bottom'                               => __( 'Bottom' ),

				'Row group'                            => __( 'Row group' ),
				'Column group'                         => __( 'Column group' ),
				'Row type'                             => __( 'Row type' ),
				'Cell type'                            => __( 'Cell type' ),
				'Cell padding'                         => __( 'Cell padding' ),
				'Cell spacing'                         => __( 'Cell spacing' ),
				'Scope'                                => _x( 'Scope', 'table cell scope attribute' ),

				'Insert template'                      => _x( 'Insert template', 'TinyMCE' ),
				'Templates'                            => _x( 'Templates', 'TinyMCE' ),

				'Background color'                     => __( 'Background color' ),
				'Text color'                           => __( 'Text color' ),
				'Show blocks'                          => _x( 'Show blocks', 'editor button' ),
				'Show invisible characters'            => __( 'Show invisible characters' ),

				/* translators: Word count. */
				'Words: {0}'                           => sprintf( __( 'Words: %s' ), '{0}' ),
				'Paste is now in plain text mode. Contents will now be pasted as plain text until you toggle this option off.' =>
					__( 'Paste is now in plain text mode. Contents will now be pasted as plain text until you toggle this option off.' ) . "\n\n" .
					__( 'If you are looking to paste rich content from Microsoft Word, try turning this option off. The editor will clean up text pasted from Word automatically.' ),
				'Rich Text Area. Press ALT-F9 for menu. Press ALT-F10 for toolbar. Press ALT-0 for help' =>
					__( 'Rich Text Area. Press Alt-Shift-H for help.' ),
				'Rich Text Area. Press Control-Option-H for help.' => __( 'Rich Text Area. Press Control-Option-H for help.' ),
				'You have unsaved changes are you sure you want to navigate away?' =>
					__( 'The changes you made will be lost if you navigate away from this page.' ),
				'Your browser doesn\'t support direct access to the clipboard. Please use the Ctrl+X/C/V keyboard shortcuts instead.' =>
					__( 'Your browser does not support direct access to the clipboard. Please use keyboard shortcuts or your browser&#8217;s edit menu instead.' ),

				// Menu TinyMCE.
				'Insert'                               => _x( 'Insert', 'TinyMCE menu' ),
				'File'                                 => _x( 'File', 'TinyMCE menu' ),
				'Edit'                                 => _x( 'Edit', 'TinyMCE menu' ),
				'Tools'                                => _x( 'Tools', 'TinyMCE menu' ),
				'View'                                 => _x( 'View', 'TinyMCE menu' ),
				'Table'                                => _x( 'Table', 'TinyMCE menu' ),
				'Format'                               => _x( 'Format', 'TinyMCE menu' ),

				// Các chuỗi WordPress.
				'Toolbar Toggle'                       => array( __( 'Toolbar Toggle' ), 'accessZ' ),
				'Insert Read More tag'                 => array( __( 'Insert Read More tag' ), 'accessT' ),
				'Insert Page Break tag'                => array( __( 'Insert Page Break tag' ), 'accessP' ),
				'Read more...'                         => __( 'Read more...' ), // Tiêu đề trên placeholder bên trong trình soạn thảo (không có dấu ba chấm).
				'Distraction-free writing mode'        => array( __( 'Distraction-free writing mode' ), 'accessW' ),
				'No alignment'                         => __( 'No alignment' ), // Tooltip cho nút 'alignnone' trong thanh công cụ hình ảnh.
				'Remove'                               => __( 'Remove' ),       // Tooltip cho nút 'remove' trong thanh công cụ hình ảnh.
				'Edit|button'                          => __( 'Edit' ),         // Tooltip cho nút 'edit' trong thanh công cụ hình ảnh.
				'Paste URL or type to search'          => __( 'Paste URL or type to search' ), // Placeholder cho hộp thoại liên kết nội tuyến.
				'Apply'                                => __( 'Apply' ),        // Tooltip cho nút 'apply' trong hộp thoại liên kết nội tuyến.
				'Link options'                         => __( 'Link options' ), // Tooltip for the 'link options' button in the inline link dialog.
				'Visual'                               => _x( 'Visual', 'Name for the Visual editor tab' ),             // Editor switch tab label.
				'Code|tab'                             => _x( 'Code', 'Name for the Code editor tab (formerly Text)' ), // Editor switch tab label.
				'Add Media'                            => array( __( 'Add Media' ), 'accessM' ), // Tooltip for the 'Add Media' button in the block editor Classic block.

				// Shortcuts help modal.
				'Keyboard Shortcuts'                   => array( __( 'Keyboard Shortcuts' ), 'accessH' ),
				'Classic Block Keyboard Shortcuts'     => __( 'Classic Block Keyboard Shortcuts' ),
				'Default shortcuts,'                   => __( 'Default shortcuts,' ),
				'Additional shortcuts,'                => __( 'Additional shortcuts,' ),
				'Focus shortcuts:'                     => __( 'Focus shortcuts:' ),
				'Inline toolbar (when an image, link or preview is selected)' => __( 'Inline toolbar (when an image, link or preview is selected)' ),
				'Editor menu (when enabled)'           => __( 'Editor menu (when enabled)' ),
				'Editor toolbar'                       => __( 'Editor toolbar' ),
				'Elements path'                        => __( 'Elements path' ),
				'Ctrl + Alt + letter:'                 => __( 'Ctrl + Alt + letter:' ),
				'Shift + Alt + letter:'                => __( 'Shift + Alt + letter:' ),
				'Cmd + letter:'                        => __( 'Cmd + letter:' ),
				'Ctrl + letter:'                       => __( 'Ctrl + letter:' ),
				'Letter'                               => __( 'Letter' ),
				'Action'                               => __( 'Action' ),
				'Warning: the link has been inserted but may have errors. Please test it.' => __( 'Warning: the link has been inserted but may have errors. Please test it.' ),
				'To move focus to other buttons use Tab or the arrow keys. To return focus to the editor press Escape or use one of the buttons.' =>
					__( 'To move focus to other buttons use Tab or the arrow keys. To return focus to the editor press Escape or use one of the buttons.' ),
				'When starting a new paragraph with one of these formatting shortcuts followed by a space, the formatting will be applied automatically. Press Backspace or Escape to undo.' =>
					__( 'When starting a new paragraph with one of these formatting shortcuts followed by a space, the formatting will be applied automatically. Press Backspace or Escape to undo.' ),
				'The following formatting shortcuts are replaced when pressing Enter. Press Escape or the Undo button to undo.' =>
					__( 'The following formatting shortcuts are replaced when pressing Enter. Press Escape or the Undo button to undo.' ),
				'The next group of formatting shortcuts are applied as you type or when you insert them around plain text in the same paragraph. Press Escape or the Undo button to undo.' =>
					__( 'The next group of formatting shortcuts are applied as you type or when you insert them around plain text in the same paragraph. Press Escape or the Undo button to undo.' ),
			);
		}

		/*
		Imagetools plugin (not included):
			'Edit image' => __( 'Edit image' ),
			'Image options' => __( 'Image options' ),
			'Back' => __( 'Back' ),
			'Invert' => __( 'Invert' ),
			'Flip horizontally' => __( 'Flip horizontal' ),
			'Flip vertically' => __( 'Flip vertical' ),
			'Crop' => __( 'Crop' ),
			'Orientation' => __( 'Orientation' ),
			'Resize' => __( 'Resize' ),
			'Rotate clockwise' => __( 'Rotate right' ),
			'Rotate counterclockwise' => __( 'Rotate left' ),
			'Sharpen' => __( 'Sharpen' ),
			'Brightness' => __( 'Brightness' ),
			'Color levels' => __( 'Color levels' ),
			'Contrast' => __( 'Contrast' ),
			'Gamma' => __( 'Gamma' ),
			'Zoom in' => __( 'Zoom in' ),
			'Zoom out' => __( 'Zoom out' ),
		*/

		return self::$translation;
	}

	/**
	 * Translates the default TinyMCE strings and returns them as JSON encoded object ready to be loaded with tinymce.addI18n(),
	 * or as JS snippet that should run after tinymce.js is loaded.
	 *
	 * @since 3.9.0
	 *
	 * @param string $mce_locale The locale used for the editor.
	 * @param bool   $json_only  Optional. Whether to include the JavaScript calls to tinymce.addI18n() and
	 *                           tinymce.ScriptLoader.markDone(). Default false.
	 * @return string Translation object, JSON encoded.
	 */
	public static function wp_mce_translation( $mce_locale = '', $json_only = false ) {
		if ( ! $mce_locale ) {
			$mce_locale = self::get_mce_locale();
		}

		$mce_translation = self::get_translation();

		foreach ( $mce_translation as $name => $value ) {
			if ( is_array( $value ) ) {
				$mce_translation[ $name ] = $value[0];
			}
		}

		/**
		 * Filters translated strings prepared for TinyMCE.
		 *
		 * @since 3.9.0
		 *
		 * @param array  $mce_translation Key/value pairs of strings.
		 * @param string $mce_locale      Locale.
		 */
		$mce_translation = apply_filters( 'wp_mce_translation', $mce_translation, $mce_locale );

		foreach ( $mce_translation as $key => $value ) {
			// Remove strings that are not translated.
			if ( $key === $value ) {
				unset( $mce_translation[ $key ] );
				continue;
			}

			if ( str_contains( $value, '&' ) ) {
				$mce_translation[ $key ] = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
			}
		}

		// Set direction.
		if ( is_rtl() ) {
			$mce_translation['_dir'] = 'rtl';
		}

		if ( $json_only ) {
			return wp_json_encode( $mce_translation );
		}

		$baseurl = self::get_baseurl();

		return "tinymce.addI18n( '$mce_locale', " . wp_json_encode( $mce_translation ) . ");\n" .
			"tinymce.ScriptLoader.markDone( '$baseurl/langs/$mce_locale.js' );\n";
	}

	/**
	 * Force uncompressed TinyMCE when a custom theme has been defined.
	 *
	 * The compressed TinyMCE file cannot deal with custom themes, so this makes
	 * sure that WordPress uses the uncompressed TinyMCE file if a theme is defined.
	 * Even if the website is running on a production environment.
	 *
	 * @since 5.0.0
	 */
	public static function force_uncompressed_tinymce() {
		$has_custom_theme = false;
		foreach ( self::$mce_settings as $init ) {
			if ( ! empty( $init['theme_url'] ) ) {
				$has_custom_theme = true;
				break;
			}
		}

		if ( ! $has_custom_theme ) {
			return;
		}

		$wp_scripts = wp_scripts();

		$wp_scripts->remove( 'wp-tinymce' );
		wp_register_tinymce_scripts( $wp_scripts, true );
	}

	/**
	 * Print (output) the main TinyMCE scripts.
	 *
	 * @since 4.8.0
	 *
	 * @global bool $concatenate_scripts
	 */
	public static function print_tinymce_scripts() {
		global $concatenate_scripts;

		if ( self::$tinymce_scripts_printed ) {
			return;
		}

		self::$tinymce_scripts_printed = true;

		if ( ! isset( $concatenate_scripts ) ) {
			script_concat_settings();
		}

		wp_print_scripts( array( 'wp-tinymce' ) );

		echo "<script type='text/javascript'>\n" . self::wp_mce_translation() . "</script>\n";
	}

	/**
	 * Print (output) the TinyMCE configuration and initialization scripts.
	 *
	 * @since 3.3.0
	 *
	 * @global string $tinymce_version
	 */
	public static function editor_js() {
		global $tinymce_version;

		$tmce_on  = ! empty( self::$mce_settings );
		$mce_init = '';
		$qt_init  = '';

		if ( $tmce_on ) {
			foreach ( self::$mce_settings as $editor_id => $init ) {
				$options   = self::_parse_init( $init );
				$mce_init .= "'$editor_id':{$options},";
			}
			$mce_init = '{' . trim( $mce_init, ',' ) . '}';
		} else {
			$mce_init = '{}';
		}

		if ( ! empty( self::$qt_settings ) ) {
			foreach ( self::$qt_settings as $editor_id => $init ) {
				$options  = self::_parse_init( $init );
				$qt_init .= "'$editor_id':{$options},";
			}
			$qt_init = '{' . trim( $qt_init, ',' ) . '}';
		} else {
			$qt_init = '{}';
		}

		$ref = array(
			'plugins'  => implode( ',', self::$plugins ),
			'theme'    => 'modern',
			'language' => self::$mce_locale,
		);

		$suffix  = SCRIPT_DEBUG ? '' : '.min';
		$baseurl = self::get_baseurl();
		$version = 'ver=' . $tinymce_version;

		/**
		 * Fires immediately before the TinyMCE settings are printed.
		 *
		 * @since 3.2.0
		 *
		 * @param array $mce_settings TinyMCE settings array.
		 */
		do_action( 'before_wp_tiny_mce', self::$mce_settings );
		?>

		<script type="text/javascript">
		tinyMCEPreInit = {
			baseURL: "<?php echo $baseurl; ?>",
			suffix: "<?php echo $suffix; ?>",
			<?php

			if ( self::$drag_drop_upload ) {
				echo 'dragDropUpload: true,';
			}

			?>
			mceInit: <?php echo $mce_init; ?>,
			qtInit: <?php echo $qt_init; ?>,
			ref: <?php echo self::_parse_init( $ref ); ?>,
			load_ext: function(url,lang){var sl=tinymce.ScriptLoader;sl.markDone(url+'/langs/'+lang+'.js');sl.markDone(url+'/langs/'+lang+'_dlg.js');}
		};
		</script>
		<?php

		if ( $tmce_on ) {
			self::print_tinymce_scripts();

			if ( self::$ext_plugins ) {
				// Load the old-format English strings to prevent unsightly labels in old style popups.
				echo "<script type='text/javascript' src='{$baseurl}/langs/wp-langs-en.js?$version'></script>\n";
			}
		}

		/**
		 * Fires after tinymce.js is loaded, but before any TinyMCE editor
		 * instances are created.
		 *
		 * @since 3.9.0
		 *
		 * @param array $mce_settings TinyMCE settings array.
		 */
		do_action( 'wp_tiny_mce_init', self::$mce_settings );

		?>
		<script type="text/javascript">
		<?php

		if ( self::$ext_plugins ) {
			echo self::$ext_plugins . "\n";
		}

		if ( ! is_admin() ) {
			echo 'var ajaxurl = "' . admin_url( 'admin-ajax.php', 'relative' ) . '";';
		}

		?>

		( function() {
			var initialized = [];
			var initialize  = function() {
				var init, id, inPostbox, $wrap;
				var readyState = document.readyState;

				if ( readyState !== 'complete' && readyState !== 'interactive' ) {
					return;
				}

				for ( id in tinyMCEPreInit.mceInit ) {
					if ( initialized.indexOf( id ) > -1 ) {
						continue;
					}

					init      = tinyMCEPreInit.mceInit[id];
					$wrap     = tinymce.$( '#wp-' + id + '-wrap' );
					inPostbox = $wrap.parents( '.postbox' ).length > 0;

					if (
						! init.wp_skip_init &&
						( $wrap.hasClass( 'tmce-active' ) || ! tinyMCEPreInit.qtInit.hasOwnProperty( id ) ) &&
						( readyState === 'complete' || ( ! inPostbox && readyState === 'interactive' ) )
					) {
						tinymce.init( init );
						initialized.push( id );

						if ( ! window.wpActiveEditor ) {
							window.wpActiveEditor = id;
						}
					}
				}
			}

			if ( typeof tinymce !== 'undefined' ) {
				if ( tinymce.Env.ie && tinymce.Env.ie < 11 ) {
					tinymce.$( '.wp-editor-wrap ' ).removeClass( 'tmce-active' ).addClass( 'html-active' );
				} else {
					if ( document.readyState === 'complete' ) {
						initialize();
					} else {
						document.addEventListener( 'readystatechange', initialize );
					}
				}
			}

			if ( typeof quicktags !== 'undefined' ) {
				for ( id in tinyMCEPreInit.qtInit ) {
					quicktags( tinyMCEPreInit.qtInit[id] );

					if ( ! window.wpActiveEditor ) {
						window.wpActiveEditor = id;
					}
				}
			}
		}());
		</script>
		<?php

		if ( in_array( 'wplink', self::$plugins, true ) || in_array( 'link', self::$qt_buttons, true ) ) {
			self::wp_link_dialog();
		}

		/**
		 * Fires after any core TinyMCE editor instances are created.
		 *
		 * @since 3.2.0
		 *
		 * @param array $mce_settings TinyMCE settings array.
		 */
		do_action( 'after_wp_tiny_mce', self::$mce_settings );
	}

	/**
	 * Outputs the HTML for distraction-free writing mode.
	 *
	 * @since 3.2.0
	 * @deprecated 4.3.0
	 */
	public static function wp_fullscreen_html() {
		_deprecated_function( __FUNCTION__, '4.3.0' );
	}

	/**
	 * Performs post queries for internal linking.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args {
	 *     Optional. Array of link query arguments.
	 *
	 *     @type int    $pagenum Page number. Default 1.
	 *     @type string $s       Search keywords.
	 * }
	 * @return array|false $results {
	 *     An array of associative arrays of query results, false if there are none.
	 *
	 *     @type array ...$0 {
	 *         @type int    $ID        Post ID.
	 *         @type string $title     The trimmed, escaped post title.
	 *         @type string $permalink Post permalink.
	 *         @type string $info      A 'Y/m/d'-formatted date for 'post' post type,
	 *                                 the 'singular_name' post type label otherwise.
	 *     }
	 * }
	 */
	public static function wp_link_query( $args = array() ) {
		$pts      = get_post_types( array( 'public' => true ), 'objects' );
		$pt_names = array_keys( $pts );

		$query = array(
			'post_type'              => $pt_names,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'post_status'            => 'publish',
			'posts_per_page'         => 20,
		);

		$args['pagenum'] = isset( $args['pagenum'] ) ? absint( $args['pagenum'] ) : 1;

		if ( isset( $args['s'] ) ) {
			$query['s'] = $args['s'];
		}

		$query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

		/**
		 * Filters the link query arguments.
		 *
		 * Allows modification of the link query arguments before querying.
		 *
		 * @see WP_Query for a full list of arguments
		 *
		 * @since 3.7.0
		 *
		 * @param array $query An array of WP_Query arguments.
		 */
		$query = apply_filters( 'wp_link_query_args', $query );

		// Do main query.
		$get_posts = new WP_Query();
		$posts     = $get_posts->query( $query );

		// Build results.
		$results = array();
		foreach ( $posts as $post ) {
			if ( 'post' === $post->post_type ) {
				$info = mysql2date( __( 'Y/m/d' ), $post->post_date );
			} else {
				$info = $pts[ $post->post_type ]->labels->singular_name;
			}

			$results[] = array(
				'ID'        => $post->ID,
				'title'     => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
				'permalink' => get_permalink( $post->ID ),
				'info'      => $info,
			);
		}

		/**
		 * Filters the link query results.
		 *
		 * Allows modification of the returned link query results.
		 *
		 * @since 3.7.0
		 *
		 * @see 'wp_link_query_args' filter
		 *
		 * @param array $results {
		 *     An array of associative arrays of query results.
		 *
		 *     @type array ...$0 {
		 *         @type int    $ID        Post ID.
		 *         @type string $title     The trimmed, escaped post title.
		 *         @type string $permalink Post permalink.
		 *         @type string $info      A 'Y/m/d'-formatted date for 'post' post type,
		 *                                 the 'singular_name' post type label otherwise.
		 *     }
		 * }
		 * @param array $query  An array of WP_Query arguments.
		 */
		$results = apply_filters( 'wp_link_query', $results, $query );

		return ! empty( $results ) ? $results : false;
	}

	/**
	 * Dialog for internal linking.
	 *
	 * @since 3.1.0
	 */
	public static function wp_link_dialog() {
		// Run once.
		if ( self::$link_dialog_printed ) {
			return;
		}

		self::$link_dialog_printed = true;

		// `display: none` is required here, see #WP27605.
		?>
		<div id="wp-link-backdrop" style="display: none"></div>
		<div id="wp-link-wrap" class="wp-core-ui" style="display: none" role="dialog" aria-modal="true" aria-labelledby="link-modal-title">
		<form id="wp-link" tabindex="-1">
		<?php wp_nonce_field( 'internal-linking', '_ajax_linking_nonce', false ); ?>
		<h1 id="link-modal-title"><?php _e( 'Insert/edit link' ); ?></h1>
		<button type="button" id="wp-link-close"><span class="screen-reader-text">
			<?php
			/* translators: Hidden accessibility text. */
			_e( 'Close' );
			?>
		</span></button>
		<div id="link-selector">
			<div id="link-options">
				<p class="howto" id="wplink-enter-url"><?php _e( 'Enter the destination URL' ); ?></p>
				<div>
					<label><span><?php _e( 'URL' ); ?></span>
					<input id="wp-link-url" type="text" aria-describedby="wplink-enter-url" /></label>
				</div>
				<div class="wp-link-text-field">
					<label><span><?php _e( 'Link Text' ); ?></span>
					<input id="wp-link-text" type="text" /></label>
				</div>
				<div class="link-target">
					<label><span></span>
					<input type="checkbox" id="wp-link-target" /> <?php _e( 'Open link in a new tab' ); ?></label>
				</div>
			</div>
			<p class="howto" id="wplink-link-existing-content"><?php _e( 'Or link to existing content' ); ?></p>
			<div id="search-panel">
				<div class="link-search-wrapper">
					<label>
						<span class="search-label"><?php _e( 'Search' ); ?></span>
						<input type="search" id="wp-link-search" class="link-search-field" autocomplete="off" aria-describedby="wplink-link-existing-content" />
						<span class="spinner"></span>
					</label>
				</div>
				<div id="search-results" class="query-results" tabindex="0">
					<ul></ul>
					<div class="river-waiting">
						<span class="spinner"></span>
					</div>
				</div>
				<div id="most-recent-results" class="query-results" tabindex="0">
					<div class="query-notice" id="query-notice-message">
						<em class="query-notice-default"><?php _e( 'No search term specified. Showing recent items.' ); ?></em>
						<em class="query-notice-hint screen-reader-text">
							<?php
							/* translators: Hidden accessibility text. */
							_e( 'Search or use up and down arrow keys to select an item.' );
							?>
						</em>
					</div>
					<ul></ul>
					<div class="river-waiting">
						<span class="spinner"></span>
					</div>
				</div>
			</div>
		</div>
		<div class="submitbox">
			<div id="wp-link-cancel">
				<button type="button" class="button"><?php _e( 'Cancel' ); ?></button>
			</div>
			<div id="wp-link-update">
				<input type="submit" value="<?php esc_attr_e( 'Add Link' ); ?>" class="button button-primary" id="wp-link-submit" name="wp-link-submit">
			</div>
		</div>
		</form>
		</div>
		<?php
	}
}
