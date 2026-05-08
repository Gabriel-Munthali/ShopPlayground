<?php
/**
 * Theme header.
 *
 * @package Khusa_Shop
 */

if (! defined('ABSPATH')) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html(wp_get_document_title()); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
$cart_count = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
?>
<header class="khusa-site-header">
	<div class="container khusa-top-nav">
		<a class="khusa-brand" href="<?php echo esc_url(home_url('/')); ?>">
			<?php esc_html_e('Shop Playground', 'khusa-shop'); ?>
		</a>
		<nav class="khusa-main-menu" aria-label="<?php esc_attr_e('Primary', 'khusa-shop'); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'khusa-shop'); ?></a>
			<a href="<?php echo esc_url($shop_url); ?>" class="is-active"><?php esc_html_e('Shop', 'khusa-shop'); ?></a>
			<a href="<?php echo esc_url($cart_url); ?>">
				<?php esc_html_e('Cart', 'khusa-shop'); ?>
				<span class="khusa-cart-count"><?php echo esc_html((string) $cart_count); ?></span>
			</a>
		</nav>
	</div>
</header>

<main id="primary" class="site-main">
