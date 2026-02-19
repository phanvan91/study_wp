<?php
/**
 * API Tùy biến: Lớp WP_Customize_Date_Time_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.9.0
 */

/**
 * Lớp Điều khiển Ngày Giờ trong Tùy biến.
 *
 * @since 4.9.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Date_Time_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển tùy biến.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $type = 'date_time';

	/**
	 * Năm tối thiểu.
	 *
	 * @since 4.9.0
	 * @var int
	 */
	public $min_year = 1000;

	/**
	 * Năm tối đa.
	 *
	 * @since 4.9.0
	 * @var int
	 */
	public $max_year = 9999;

	/**
	 * Cho phép ngày trong quá khứ, nếu đặt thành false người dùng chỉ có thể chọn ngày trong tương lai.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	public $allow_past_date = true;

	/**
	 * Có hiển thị giờ, phút và buổi sáng/chiều hay không.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	public $include_time = true;

	/**
	 * Nếu đặt thành false, điều khiển sẽ hiển thị ở định dạng 24 giờ,
	 * giá trị vẫn được lưu ở định dạng Y-m-d H:i:s.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	public $twelve_hour_format = true;

	/**
	 * Không hiển thị nội dung điều khiển - được hiển thị bằng mẫu JS.
	 *
	 * @since 4.9.0
	 */
	public function render_content() {}

	/**
	 * Xuất dữ liệu sang JS.
	 *
	 * @since 4.9.0
	 * @return array
	 */
	public function json() {
		$data = parent::json();

		$data['maxYear']          = (int) $this->max_year;
		$data['minYear']          = (int) $this->min_year;
		$data['allowPastDate']    = (bool) $this->allow_past_date;
		$data['twelveHourFormat'] = (bool) $this->twelve_hour_format;
		$data['includeTime']      = (bool) $this->include_time;

		return $data;
	}

	/**
	 * Hiển thị mẫu JS cho nội dung của điều khiển ngày giờ.
	 *
	 * @since 4.9.0
	 */
	public function content_template() {
		$data          = array_merge( $this->json(), $this->get_month_choices() );
		$timezone_info = $this->get_timezone_info();

		$date_format = get_option( 'date_format' );
		$date_format = preg_replace( '/(?<!\\\\)[Yyo]/', '%1$s', $date_format );
		$date_format = preg_replace( '/(?<!\\\\)[FmMn]/', '%2$s', $date_format );
		$date_format = preg_replace( '/(?<!\\\\)[jd]/', '%3$s', $date_format );

		// Trở về định dạng ngày ISO nếu năm, tháng hoặc ngày bị thiếu trong định dạng ngày.
		if ( 1 !== substr_count( $date_format, '%1$s' ) || 1 !== substr_count( $date_format, '%2$s' ) || 1 !== substr_count( $date_format, '%3$s' ) ) {
			$date_format = '%1$s-%2$s-%3$s';
		}
		?>

		<# _.defaults( data, <?php echo wp_json_encode( $data ); ?> ); #>
		<# var idPrefix = _.uniqueId( 'el' ) + '-'; #>

		<# if ( data.label ) { #>
			<span class="customize-control-title">
				{{ data.label }}
			</span>
		<# } #>
		<div class="customize-control-notifications-container"></div>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{ data.description }}</span>
		<# } #>
		<div class="date-time-fields {{ data.includeTime ? 'includes-time' : '' }}">
			<fieldset class="day-row">
				<legend class="title-day {{ ! data.includeTime ? 'screen-reader-text' : '' }}"><?php esc_html_e( 'Date' ); ?></legend>
				<div class="day-fields clear">
					<?php ob_start(); ?>
					<label for="{{ idPrefix }}date-time-month" class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						esc_html_e( 'Month' );
						?>
					</label>
					<select id="{{ idPrefix }}date-time-month" class="date-input month" data-component="month">
						<# _.each( data.month_choices, function( choice ) {
							if ( _.isObject( choice ) && ! _.isUndefined( choice.text ) && ! _.isUndefined( choice.value ) ) {
								text = choice.text;
								value = choice.value;
							}
							#>
							<option value="{{ value }}" >
								{{ text }}
							</option>
						<# } ); #>
					</select>
					<?php $month_field = trim( ob_get_clean() ); ?>

					<?php ob_start(); ?>
					<label for="{{ idPrefix }}date-time-day" class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						esc_html_e( 'Day' );
						?>
					</label>
					<input id="{{ idPrefix }}date-time-day" type="number" size="2" autocomplete="off" class="date-input day tiny-text" data-component="day" min="1" max="31" />
					<?php $day_field = trim( ob_get_clean() ); ?>

					<?php ob_start(); ?>
					<label for="{{ idPrefix }}date-time-year" class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						esc_html_e( 'Year' );
						?>
					</label>
					<input id="{{ idPrefix }}date-time-year" type="number" size="4" autocomplete="off" class="date-input year tiny-text" data-component="year" min="{{ data.minYear }}" max="{{ data.maxYear }}">
					<?php $year_field = trim( ob_get_clean() ); ?>

					<?php printf( $date_format, $year_field, $month_field, $day_field ); ?>
				</div>
			</fieldset>
			<# if ( data.includeTime ) { #>
				<fieldset class="time-row clear">
					<legend class="title-time"><?php esc_html_e( 'Time' ); ?></legend>
					<div class="time-fields clear">
						<label for="{{ idPrefix }}date-time-hour" class="screen-reader-text">
							<?php
							/* translators: Văn bản trợ năng ẩn. */
							esc_html_e( 'Hour' );
							?>
						</label>
						<# var maxHour = data.twelveHourFormat ? 12 : 23; #>
						<# var minHour = data.twelveHourFormat ? 1 : 0; #>
						<input id="{{ idPrefix }}date-time-hour" type="number" size="2" autocomplete="off" class="date-input hour tiny-text" data-component="hour" min="{{ minHour }}" max="{{ maxHour }}">
						:
						<label for="{{ idPrefix }}date-time-minute" class="screen-reader-text">
							<?php
							/* translators: Văn bản trợ năng ẩn. */
							esc_html_e( 'Minute' );
							?>
						</label>
						<input id="{{ idPrefix }}date-time-minute" type="number" size="2" autocomplete="off" class="date-input minute tiny-text" data-component="minute" min="0" max="59">
						<# if ( data.twelveHourFormat ) { #>
							<label for="{{ idPrefix }}date-time-meridian" class="screen-reader-text">
								<?php
								/* translators: Văn bản trợ năng ẩn. */
								esc_html_e( 'Meridian' );
								?>
							</label>
							<select id="{{ idPrefix }}date-time-meridian" class="date-input meridian" data-component="meridian">
								<option value="am"><?php esc_html_e( 'AM' ); ?></option>
								<option value="pm"><?php esc_html_e( 'PM' ); ?></option>
							</select>
						<# } #>
						<p><?php echo $timezone_info['description']; ?></p>
					</div>
				</fieldset>
			<# } #>
		</div>
		<?php
	}

	/**
	 * Tạo các tùy chọn cho Select tháng.
	 *
	 * Dựa trên touch_time().
	 *
	 * @since 4.9.0
	 *
	 * @see touch_time()
	 *
	 * @global WP_Locale $wp_locale Đối tượng ngôn ngữ ngày và giờ của WordPress.
	 *
	 * @return array
	 */
	public function get_month_choices() {
		global $wp_locale;
		$months = array();
		for ( $i = 1; $i < 13; $i++ ) {
			$month_text = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );

			/* translators: 1: Số tháng (01, 02, v.v.), 2: Viết tắt tháng. */
			$months[ $i ]['text']  = sprintf( __( '%1$s-%2$s' ), $i, $month_text );
			$months[ $i ]['value'] = $i;
		}
		return array(
			'month_choices' => $months,
		);
	}

	/**
	 * Lấy thông tin múi giờ.
	 *
	 * @since 4.9.0
	 *
	 * @return array {
	 *     Thông tin múi giờ. Tất cả thuộc tính đều là tùy chọn.
	 *
	 *     @type string $abbr        Viết tắt múi giờ. Ví dụ: PST hoặc CEST.
	 *     @type string $description Mô tả múi giờ dễ đọc dưới dạng HTML.
	 * }
	 */
	public function get_timezone_info() {
		$tz_string     = get_option( 'timezone_string' );
		$timezone_info = array();

		if ( $tz_string ) {
			try {
				$tz = new DateTimeZone( $tz_string );
			} catch ( Exception $e ) {
				$tz = '';
			}

			if ( $tz ) {
				$now                   = new DateTime( 'now', $tz );
				$formatted_gmt_offset  = $this->format_gmt_offset( $tz->getOffset( $now ) / HOUR_IN_SECONDS );
				$tz_name               = str_replace( '_', ' ', $tz->getName() );
				$timezone_info['abbr'] = $now->format( 'T' );

				$timezone_info['description'] = sprintf(
					/* translators: 1: Tên múi giờ, 2: Viết tắt múi giờ, 3: Viết tắt UTC và độ lệch, 4: Độ lệch UTC. */
					__( 'Your timezone is set to %1$s (%2$s), currently %3$s (Coordinated Universal Time %4$s).' ),
					$tz_name,
					'<abbr>' . $timezone_info['abbr'] . '</abbr>',
					'<abbr>UTC</abbr>' . $formatted_gmt_offset,
					$formatted_gmt_offset
				);
			} else {
				$timezone_info['description'] = '';
			}
		} else {
			$formatted_gmt_offset = $this->format_gmt_offset( (int) get_option( 'gmt_offset', 0 ) );

			$timezone_info['description'] = sprintf(
				/* translators: 1: Viết tắt UTC và độ lệch, 2: Độ lệch UTC. */
				__( 'Your timezone is set to %1$s (Coordinated Universal Time %2$s).' ),
				'<abbr>UTC</abbr>' . $formatted_gmt_offset,
				$formatted_gmt_offset
			);
		}

		return $timezone_info;
	}

	/**
	 * Định dạng độ lệch GMT.
	 *
	 * @since 4.9.0
	 *
	 * @see wp_timezone_choice()
	 *
	 * @param float $offset Độ lệch tính bằng giờ.
	 * @return string Độ lệch đã định dạng.
	 */
	public function format_gmt_offset( $offset ) {
		if ( 0 <= $offset ) {
			$formatted_offset = '+' . (string) $offset;
		} else {
			$formatted_offset = (string) $offset;
		}
		$formatted_offset = str_replace(
			array( '.25', '.5', '.75' ),
			array( ':15', ':30', ':45' ),
			$formatted_offset
		);
		return $formatted_offset;
	}
}
