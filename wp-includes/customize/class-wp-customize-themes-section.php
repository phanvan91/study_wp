<?php
/**
 * API Tùy biến: Lớp WP_Customize_Themes_Section
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Phần Giao diện trong Tùy biến.
 *
 * Vùng chứa giao diện cho các điều khiển giao diện, được hiển thị trong các phần.
 *
 * @since 4.2.0
 *
 * @see WP_Customize_Section
 */
class WP_Customize_Themes_Section extends WP_Customize_Section {

	/**
	 * Loại phần.
	 *
	 * @since 4.2.0
	 * @var string
	 */
	public $type = 'themes';

	/**
	 * Hành động phần giao diện.
	 *
	 * Xác định loại giao diện cần tải (đã cài đặt, wporg, v.v.).
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $action = '';

	/**
	 * Loại bộ lọc phần giao diện.
	 *
	 * Xác định bộ lọc được áp dụng cho giao diện đã tải (cục bộ) hay bằng cách khởi tạo truy vấn từ xa mới (từ xa).
	 * Khi lọc cục bộ, truy vấn giao diện ban đầu không được phân trang theo mặc định.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $filter_type = 'local';

	/**
	 * Lấy các tham số phần cho JS.
	 *
	 * @since 4.9.0
	 * @return array Các tham số đã được xuất.
	 */
	public function json() {
		$exported                = parent::json();
		$exported['action']      = $this->action;
		$exported['filter_type'] = $this->filter_type;

		return $exported;
	}

	/**
	 * Hiển thị phần giao diện dưới dạng mẫu JS.
	 *
	 * Mẫu chỉ được hiển thị bởi PHP một lần, vì vậy tất cả các hành động được chuẩn bị cùng lúc ở phía máy chủ.
	 *
	 * @since 4.9.0
	 */
	protected function render_template() {
		?>
		<li id="accordion-section-{{ data.id }}" class="theme-section">
			<button type="button" class="customize-themes-section-title themes-section-{{ data.id }}">{{ data.title }}</button>
			<?php if ( current_user_can( 'install_themes' ) || is_multisite() ) : // @todo Hỗ trợ tải lên. ?>
			<?php endif; ?>
			<div class="customize-themes-section themes-section-{{ data.id }} control-section-content themes-php">
				<div class="theme-overlay" tabindex="0" role="dialog" aria-label="<?php esc_attr_e( 'Theme Details' ); ?>"></div>
				<div class="theme-browser rendered">
					<div class="customize-preview-header themes-filter-bar">
						<?php $this->filter_bar_content_template(); ?>
					</div>
					<?php $this->filter_drawer_content_template(); ?>
					<div class="error unexpected-error" style="display: none; ">
						<p>
							<?php
							printf(
								/* translators: %s: URL diễn đàn hỗ trợ. */
								__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
								__( 'https://wordpress.org/support/forums/' )
							);
							?>
						</p>
					</div>
					<ul class="themes">
					</ul>
					<p class="no-themes"><?php _e( 'No themes found. Try a different search.' ); ?></p>
					<p class="no-themes-local">
						<?php
						printf(
							/* translators: %s: Văn bản nút "Tìm kiếm giao diện WordPress.org". */
							__( 'No themes found. Try a different search, or %s.' ),
							sprintf( '<button type="button" class="button-link search-dotorg-themes">%s</button>', __( 'Search WordPress.org themes' ) )
						);
						?>
					</p>
					<p class="spinner"></p>
				</div>
			</div>
		</li>
		<?php
	}

	/**
	 * Hiển thị phần thanh bộ lọc của phần giao diện dưới dạng mẫu JS.
	 *
	 * Mẫu chỉ được hiển thị bởi PHP một lần, vì vậy tất cả các hành động được chuẩn bị cùng lúc ở phía máy chủ.
	 * Vùng chứa thanh bộ lọc được hiển thị bởi {@see render_template()}.
	 *
	 * @since 4.9.0
	 */
	protected function filter_bar_content_template() {
		?>
		<button type="button" class="button button-primary customize-section-back customize-themes-mobile-back"><?php _e( 'Go to theme sources' ); ?></button>
		<# if ( 'wporg' === data.action ) { #>
			<div class="themes-filter-container">
				<label for="wp-filter-search-input-{{ data.id }}"><?php _e( 'Search themes' ); ?></label>
				<div class="search-form-input">
					<input type="search" id="wp-filter-search-input-{{ data.id }}" aria-describedby="{{ data.id }}-live-search-desc" class="wp-filter-search">
					<div class="search-icon" aria-hidden="true"></div>
					<span id="{{ data.id }}-live-search-desc" class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						_e( 'The search results will be updated as you type.' );
						?>
					</span>
				</div>
			</div>
		<# } else { #>
			<div class="themes-filter-container">
				<label for="{{ data.id }}-themes-filter"><?php _e( 'Search themes' ); ?></label>
				<div class="search-form-input">
					<input type="search" id="{{ data.id }}-themes-filter" aria-describedby="{{ data.id }}-live-search-desc" class="wp-filter-search wp-filter-search-themes" />
					<div class="search-icon" aria-hidden="true"></div>
					<span id="{{ data.id }}-live-search-desc" class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						_e( 'The search results will be updated as you type.' );
						?>
					</span>
				</div>
			</div>
		<# } #>
		<div class="filter-themes-wrapper">
			<# if ( 'wporg' === data.action ) { #>
			<button type="button" class="button feature-filter-toggle">
				<span class="filter-count-0"><?php _e( 'Filter themes' ); ?></span><span class="filter-count-filters">
					<?php
					/* translators: %s: Số bộ lọc đã chọn. */
					printf( __( 'Filter themes (%s)' ), '<span class="theme-filter-count">0</span>' );
					?>
				</span>
			</button>
			<# } #>
			<div class="filter-themes-count">
				<span class="themes-displayed">
					<?php
					/* translators: %s: Số giao diện được hiển thị. */
					printf( __( '%s themes' ), '<span class="theme-count">0</span>' );
					?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Hiển thị phần ngăn kéo bộ lọc của phần giao diện dưới dạng mẫu JS.
	 *
	 * Vùng chứa thanh bộ lọc được hiển thị bởi {@see render_template()}.
	 *
	 * @since 4.9.0
	 */
	protected function filter_drawer_content_template() {
		/*
		 * @todo Sử dụng API .org thay vì danh sách tính năng cốt lõi cục bộ.
		 * API .org hiện đã lỗi thời và sẽ được điều chỉnh khi thư mục giao diện .org được thiết kế lại tiếp theo.
		 */
		$feature_list = get_theme_feature_list( false );
		?>
		<# if ( 'wporg' === data.action ) { #>
			<div class="filter-drawer filter-details">
				<?php foreach ( $feature_list as $feature_name => $features ) : ?>
					<fieldset class="filter-group">
						<legend><?php echo esc_html( $feature_name ); ?></legend>
						<div class="filter-group-feature">
							<?php foreach ( $features as $feature => $feature_name ) : ?>
								<input type="checkbox" id="filter-id-<?php echo esc_attr( $feature ); ?>" value="<?php echo esc_attr( $feature ); ?>" />
								<label for="filter-id-<?php echo esc_attr( $feature ); ?>"><?php echo esc_html( $feature_name ); ?></label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php endforeach; ?>
			</div>
		<# } #>
		<?php
	}
}
