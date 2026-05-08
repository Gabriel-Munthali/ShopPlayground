<?php
/**
 * Front page with concept shop layout.
 *
 * @package Khusa_Shop
 */

if (! defined('ABSPATH')) {
	exit;
}

get_header();

$product_objects = array();
if (function_exists('wc_get_products')) {
	$product_objects = wc_get_products(
		array(
			'status' => 'publish',
			'limit'  => 12,
			'order'  => 'DESC',
		)
	);
}
?>

<section class="khusa-shop-main">
	<div class="container">
		<section class="khusa-shop-toolbar" aria-label="<?php esc_attr_e('Shop filters', 'khusa-shop'); ?>">
			<div class="khusa-toolbar-left">
				<button type="button"><?php esc_html_e('Filter by', 'khusa-shop'); ?></button>
				<button type="button" class="dropdown"><?php esc_html_e('Categories', 'khusa-shop'); ?></button>
				<button type="button"><?php esc_html_e('Color', 'khusa-shop'); ?></button>
				<button type="button"><?php esc_html_e('Size', 'khusa-shop'); ?></button>
				<button type="button"><?php esc_html_e('Brand', 'khusa-shop'); ?></button>
				<button type="button"><?php esc_html_e('Price', 'khusa-shop'); ?></button>
			</div>
			<div class="khusa-toolbar-right">
			</div>
		</section>

		<?php if (empty($product_objects)) : ?>
			<p class="khusa-empty-state"><?php esc_html_e('No products yet. Add products under Products in admin.', 'khusa-shop'); ?></p>
		<?php else : ?>
			<section class="khusa-product-grid" aria-label="<?php esc_attr_e('Products', 'khusa-shop'); ?>">
				<?php foreach ($product_objects as $wc_product) : ?>
					<?php
					if (! $wc_product instanceof WC_Product) {
						continue;
					}
					$pid = $wc_product->get_id();
					$img_id = $wc_product->get_image_id();
					$img_url = $img_id ? wp_get_attachment_image_url($img_id, 'medium_large') : wc_placeholder_img_src('woocommerce_single');
					$label = $wc_product->is_purchasable() && $wc_product->is_in_stock() ? __('Add to cart', 'khusa-shop') : __('View product', 'khusa-shop');
					$url = $wc_product->is_purchasable() && $wc_product->is_in_stock() ? $wc_product->add_to_cart_url() : $wc_product->get_permalink();
					?>
					<article class="khusa-product-card">
						<a class="khusa-product-image" href="<?php echo esc_url($wc_product->get_permalink()); ?>">
							<img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($wc_product->get_name()); ?>">
						</a>
						<h2><a href="<?php echo esc_url($wc_product->get_permalink()); ?>"><?php echo esc_html($wc_product->get_name()); ?></a></h2>
						<p class="price"><?php echo wp_kses_post($wc_product->get_price_html()); ?></p>
						<div class="khusa-swatches" aria-hidden="true">
							<span class="swatch blue"></span>
							<span class="swatch brown"></span>
							<span class="swatch gray"></span>
						</div>
						<a class="khusa-cart-button" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
					</article>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<nav class="khusa-pagination" aria-label="<?php esc_attr_e('Pagination', 'khusa-shop'); ?>">
			<button type="button" class="is-active">1</button>
			<button type="button">2</button>
			<button type="button">3</button>
			<button type="button">4</button>
			<button type="button" aria-label="<?php esc_attr_e('Next page', 'khusa-shop'); ?>">-></button>
		</nav>
	</div>
</section>

<?php
get_footer();
