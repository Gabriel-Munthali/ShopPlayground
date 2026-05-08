<?php
/**
 * Khusa Shop theme — asset loading and theme setup.
 *
 * @package Khusa_Shop
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Theme version for cache busting local assets.
 */
define('KHUSA_SHOP_VERSION', '1.0.0');

/**
 * Register and enqueue styles/scripts (mirrors KhusaRedifined.Design components.html stack).
 */
function khusa_shop_enqueue_assets() {
	$theme_uri = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'khusa-shop-bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
		array(),
		'5.3.2'
	);

	wp_enqueue_style(
		'khusa-shop-air-datepicker',
		'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.min.css',
		array(),
		'3.5.3'
	);

	wp_enqueue_style(
		'khusa-shop-components',
		$theme_uri . '/assets/css/style.css',
		array('khusa-shop-bootstrap'),
		KHUSA_SHOP_VERSION
	);

	wp_enqueue_style(
		'khusa-shop-responsive',
		$theme_uri . '/assets/css/responsive.css',
		array('khusa-shop-components'),
		KHUSA_SHOP_VERSION
	);

	wp_enqueue_style(
		'khusa-shop-style',
		get_stylesheet_uri(),
		array('khusa-shop-responsive'),
		KHUSA_SHOP_VERSION
	);

	wp_enqueue_script('jquery');

	wp_enqueue_script(
		'khusa-shop-popper',
		'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js',
		array(),
		'2.11.8',
		true
	);

	wp_enqueue_script(
		'khusa-shop-bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js',
		array('khusa-shop-popper'),
		'5.3.2',
		true
	);

	wp_enqueue_script(
		'khusa-shop-lucide',
		'https://cdn.jsdelivr.net/npm/lucide@0.561.0/dist/umd/lucide.min.js',
		array(),
		'0.561.0',
		true
	);

	wp_enqueue_script(
		'khusa-shop-air-datepicker',
		'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.min.js',
		array(),
		'3.5.3',
		true
	);

	wp_enqueue_script(
		'khusa-shop-main',
		$theme_uri . '/assets/js/main.js',
		array('jquery', 'khusa-shop-bootstrap', 'khusa-shop-lucide', 'khusa-shop-air-datepicker'),
		KHUSA_SHOP_VERSION,
		true
	);
}
add_action('wp_enqueue_scripts', 'khusa_shop_enqueue_assets');

/**
 * Core theme supports.
 */
function khusa_shop_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));

	add_theme_support('woocommerce');
	add_theme_support('wc-product-gallery-zoom');
	add_theme_support('wc-product-gallery-lightbox');
	add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'khusa_shop_setup');

/**
 * Match concept toolbar by removing default WooCommerce sorting UI.
 */
function khusa_shop_customize_catalog_controls() {
	if (! function_exists('is_woocommerce')) {
		return;
	}

	remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
}
add_action('wp', 'khusa_shop_customize_catalog_controls');
