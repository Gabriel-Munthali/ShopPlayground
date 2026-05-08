<?php
/**
 * WooCommerce template wrapper — same shell as the rest of the theme.
 *
 * @package Khusa_Shop
 */

if (! defined('ABSPATH')) {
	exit;
}

get_header();
?>

<section class="khusa-shop-main">
	<div class="container py-5">
		<?php woocommerce_content(); ?>
	</div>
</section>

<?php
get_footer();
