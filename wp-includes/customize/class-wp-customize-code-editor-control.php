<?php
/**
 * API Tùy biến: Lớp WP_Customize_Code_Editor_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.9.0
 */

/**
 * Lớp Điều khiển Trình soạn thảo mã trong Tùy biến.
 *
 * @since 4.9.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Code_Editor_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển tùy biến.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $type = 'code_editor';

	/**
	 * Loại mã đang được chỉnh sửa.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $code_type = '';

	/**
	 * Cài đặt trình soạn thảo mã.
	 *
	 * @see wp_enqueue_code_editor()
	 * @since 4.9.0
	 * @var array|false
	 */
	public $editor_settings = array();

	/**
	 * Nạp các script/style liên quan đến điều khiển.
	 *
	 * @since 4.9.0
	 */
	public function enqueue() {
		$this->editor_settings = wp_enqueue_code_editor(
			array_merge(
				array(
					'type'       => $this->code_type,
					'codemirror' => array(
						'indentUnit' => 2,
						'tabSize'    => 2,
					),
				),
				$this->editor_settings
			)
		);
	}

	/**
	 * Làm mới các tham số được truyền tới JavaScript qua JSON.
	 *
	 * @since 4.9.0
	 *
	 * @see WP_Customize_Control::json()
	 *
	 * @return array Mảng các tham số được truyền tới JavaScript.
	 */
	public function json() {
		$json                    = parent::json();
		$json['editor_settings'] = $this->editor_settings;
		$json['input_attrs']     = $this->input_attrs;
		return $json;
	}

	/**
	 * Không hiển thị nội dung điều khiển từ PHP, vì nó được hiển thị qua JS khi tải trang.
	 *
	 * @since 4.9.0
	 */
	public function render_content() {}

	/**
	 * Hiển thị mẫu JS cho giao diện điều khiển.
	 *
	 * @since 4.9.0
	 */
	public function content_template() {
		?>
		<# var elementIdPrefix = 'el' + String( Math.random() ); #>
		<# if ( data.label ) { #>
			<label for="{{ elementIdPrefix }}_editor" class="customize-control-title">
				{{ data.label }}
			</label>
		<# } #>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>
		<div class="customize-control-notifications-container"></div>
		<textarea id="{{ elementIdPrefix }}_editor"
			<# _.each( _.extend( { 'class': 'code' }, data.input_attrs ), function( value, key ) { #>
				{{{ key }}}="{{ value }}"
			<# }); #>
			></textarea>
		<?php
	}
}
