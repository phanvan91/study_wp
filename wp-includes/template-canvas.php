<?php
/**
 * File canvas template để render 'wp_template' hiện tại.
 *
 * @package WordPress
 */

/*
 * Lấy HTML của template.
 * Cần chạy trước <head> để các block có thể thêm script và style trong wp_head().
 */
$template_html = get_the_block_template_html();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php echo $template_html; ?>

<?php wp_footer(); ?>
</body>
</html>
