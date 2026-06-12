<?php
/**
 * Plugin Name: WooCommerce OneKhusa RTP
 * Description: Request To Pay hosted checkout for OneKhusa. See https://docs.onekhusa.com for API details.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.7
 * Author: OneKhusa
 * Author URI: https://onekhusa.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-onekhusa-rtp
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

define('WC_ONEKHUSA_RTP_VERSION', '0.1.0');
define('WC_ONEKHUSA_RTP_PLUGIN_FILE', __FILE__);
define('WC_ONEKHUSA_RTP_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Activation: webhook verification token.
 */
function wc_onekhusa_rtp_activate() {
	if (!get_option('wc_onekhusa_webhook_token')) {
		add_option('wc_onekhusa_webhook_token', wp_generate_password(48, false, false));
	}
}

register_activation_hook(__FILE__, 'wc_onekhusa_rtp_activate');

add_action('before_woocommerce_init', 'wc_onekhusa_rtp_declare_compat');

add_action('template_redirect', 'wcOnekhusaRtpRedirectPaidOrders');

/**
 * Declare compatibility with WooCommerce optional features (HPOS, Checkout Blocks).
 */
function wc_onekhusa_rtp_declare_compat() {
	if (!class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		return;
	}
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WC_ONEKHUSA_RTP_PLUGIN_FILE, true);
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', WC_ONEKHUSA_RTP_PLUGIN_FILE, true);
}

add_action('plugins_loaded', 'wc_onekhusa_rtp_bootstrap', 11);

/**
 * Load gateway after WooCommerce.
 */
function wc_onekhusa_rtp_bootstrap() {
	if (!class_exists('WooCommerce')) {
		return;
	}
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-wc-onekhusa-logger.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-onekhusa-brand.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-onekhusa-reference.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-onekhusa-api-client.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-onekhusa-rtp-initiate-payload-builder.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-onekhusa-gateway-settings.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-onekhusa-order-resolver.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-wc-onekhusa-webhook.php';
	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-wc-gateway-onekhusa.php';

	add_filter('woocommerce_payment_gateways', 'wc_onekhusa_rtp_add_gateway');

	wc_onekhusa_rtp_register_blocks_integration();

	WC_Onekhusa_Webhook::register();
}

/**
 * Register Checkout Blocks / Cart Blocks integration when WooCommerce Blocks is available.
 *
 * Registers on woocommerce_blocks_payment_method_type_registration (fires before integrations initialize).
 *
 * Note: WooCommerce loads before most extensions; registering from plugins_loaded avoids missing Blocks entirely.
 *
 * @return void
 */
function wc_onekhusa_rtp_register_blocks_integration() {
	if (!class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		return;
	}

	require_once WC_ONEKHUSA_RTP_PLUGIN_DIR . 'includes/class-wc-onekhusa-blocks-support.php';

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		static function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
			$payment_method_registry->register(new WC_Onekhusa_Blocks_Support());
		}
	);
}

/**
 * @param array $gateways Gateway class names.
 * @return array
 */
function wc_onekhusa_rtp_add_gateway($gateways) {
	$gateways[] = 'WC_Gateway_Onekhusa';
	return $gateways;
}

/**
 * Redirect paid OneKhusa orders away from order-pay to order-received.
 *
 * @return void
 */
function wcOnekhusaRtpRedirectPaidOrders() {
	$redirect_url = '';

	if (function_exists('is_checkout_pay_page') && is_checkout_pay_page()) {
		$order_id  = absint(get_query_var('order-pay'));
		$order_key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';

		if ($order_id > 0 && $order_key !== '') {
			$order = wc_get_order($order_id);

			if ($order
				&& class_exists('WC_Gateway_Onekhusa')
				&& $order->get_payment_method() === WC_Gateway_Onekhusa::ID
				&& hash_equals($order->get_order_key(), $order_key)
				&& ! $order->needs_payment()
			) {
				$redirect_url = $order->get_checkout_order_received_url();
			}
		}
	}

	if ($redirect_url === '') {
		return;
	}

	wp_safe_redirect($redirect_url);
	exit;
}
