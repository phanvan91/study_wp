<?php
/**
 * Mẫu Footer Quản trị WordPress
 *
 * @package WordPress
 * @subpackage Administration
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * @global string $hook_suffix
 */
global $hook_suffix;
?>

<div class="clear"></div></div><!-- wpbody-content -->
<div class="clear"></div></div><!-- wpbody -->
<div class="clear"></div></div><!-- wpcontent -->

<div id="wpfooter" role="contentinfo">
	<?php
	/**
	 * Kích hoạt sau thẻ mở của footer quản trị.
	 *
	 * @since 2.5.0
	 */
	do_action( 'in_admin_footer' );
	?>
	<p id="footer-left" class="alignleft">
		<?php
		$text = sprintf(
			/* translators: %s: https://wordpress.org/ */
			__( 'Thank you for creating with <a href="%s">WordPress</a>.' ),
			esc_url( __( 'https://wordpress.org/' ) )
		);

		/**
		 * Lọc văn bản "Cảm ơn" hiển thị trong footer quản trị.
		 *
		 * @since 2.8.0
		 *
		 * @param string $text Nội dung sẽ được in ra.
		 */
		echo apply_filters( 'admin_footer_text', '<span id="footer-thankyou">' . $text . '</span>' );
		?>
	</p>
	<p id="footer-upgrade" class="alignright">
		<?php
		/**
		 * Lọc văn bản phiên bản/cập nhật hiển thị trong footer quản trị.
		 *
		 * WordPress in phiên bản hiện tại và thông tin cập nhật,
		 * sử dụng core_update_footer() với độ ưu tiên 10.
		 *
		 * @since 2.3.0
		 *
		 * @see core_update_footer()
		 *
		 * @param string $content Nội dung sẽ được in ra.
		 */
		echo apply_filters( 'update_footer', '' );
		?>
	</p>
	<div class="clear"></div>
</div>
<?php
/**
 * In các script hoặc dữ liệu trước các script footer mặc định.
 *
 * @since 1.2.0
 *
 * @param string $data Dữ liệu cần in.
 */
do_action( 'admin_footer', '' );

/**
 * In các script và dữ liệu được xếp hàng cho footer.
 *
 * Phần động của tên hook, `$hook_suffix`,
 * tham chiếu đến hậu tố hook toàn cục của trang hiện tại.
 *
 * @since 4.6.0
 */
do_action( "admin_print_footer_scripts-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * In bất kỳ script và dữ liệu nào được xếp hàng cho footer.
 *
 * @since 2.8.0
 */
do_action( 'admin_print_footer_scripts' );

/**
 * In các script hoặc dữ liệu sau các script footer mặc định.
 *
 * Phần động của tên hook, `$hook_suffix`,
 * tham chiếu đến hậu tố hook toàn cục của trang hiện tại.
 *
 * @since 2.8.0
 */
do_action( "admin_footer-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

// get_site_option() sẽ không tồn tại khi tự động nâng cấp từ <= 2.7.
if ( function_exists( 'get_site_option' )
	&& false === get_site_option( 'can_compress_scripts' )
) {
	compression_test();
}

?>

<div class="clear"></div></div><!-- wpwrap -->
<script type="text/javascript">if(typeof wpOnload==='function')wpOnload();</script>
</body>
</html>
