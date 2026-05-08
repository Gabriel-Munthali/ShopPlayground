<?php
/**
 * WooCommerce Checkout / Cart Blocks payment method integration for OneKhusa RTP.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Bridges the classic WC_Payment_Gateway to Checkout Blocks frontend registration.
 *
 * Redirect flow is unchanged: Store API invokes the gateway `process_payment` on place order,
 * returning `redirect`.
 */
final class WC_Onekhusa_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Gateway identifier (matches {@see WC_Gateway_Onekhusa::ID}).
	 *
	 * @var string
	 */
	protected $name = WC_Gateway_Onekhusa::ID;

	/**
	 * Script handle for the Checkout Blocks frontend registration bundle.
	 */
	private const SCRIPT_HANDLE = 'wc-onekhusa-rtp-blocks-payment-method';

	/**
	 * Built JS bundle from `npm run build`.
	 *
	 * @var string
	 */
	private static function blocks_bundle_path() {
		return WC_ONEKHUSA_RTP_PLUGIN_DIR . 'build/checkout-blocks.js';
	}

	/**
	 * {@inheritdoc}
	 */
	public function initialize() {
		$this->settings = get_option(
			sprintf('woocommerce_%s_settings', WC_Gateway_Onekhusa::ID),
			array()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active() {
		$gateway_on = filter_var($this->get_setting('enabled', false), FILTER_VALIDATE_BOOLEAN);
		if (!$gateway_on) {
			return false;
		}
		return is_readable(self::blocks_bundle_path());
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_payment_method_script_handles() {
		if (!is_readable(self::blocks_bundle_path())) {
			return array();
		}

		if (!wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
			$this->register_frontend_script();
		}

		return array(self::SCRIPT_HANDLE);
	}

	/**
	 * Registers the `@wordpress/scripts` output with dependency metadata.
	 */
	private function register_frontend_script() {
		$asset_file_path = WC_ONEKHUSA_RTP_PLUGIN_DIR . 'build/checkout-blocks.asset.php';

		if (!file_exists($asset_file_path)) {
			return;
		}

		/** @var array{dependencies?: string[], version?: string} $asset_file */
		$asset_file   = require $asset_file_path;
		$dependencies = isset($asset_file['dependencies']) && is_array($asset_file['dependencies']) ? $asset_file['dependencies'] : array();
		$version      = isset($asset_file['version']) ? (string) $asset_file['version'] : WC_ONEKHUSA_RTP_VERSION;

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugins_url('/build/checkout-blocks.js', WC_ONEKHUSA_RTP_PLUGIN_FILE),
			$dependencies,
			$version,
			true
		);

		wp_set_script_translations(
			self::SCRIPT_HANDLE,
			'woocommerce-onekhusa-rtp',
			dirname(WC_ONEKHUSA_RTP_PLUGIN_FILE) . '/languages'
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_payment_method_data() {
		return array(
			'title'         => $this->get_setting('title', ''),
			'description'   => $this->get_setting('description', ''),
			'supports'      => array_values(array_unique($this->get_supported_features())),
			'icons'         => WC_Onekhusa_Brand::get_blocks_payment_icons(),
			'logo_wordmark' => WC_Onekhusa_Brand::get_logo_wordmark_url(),
		);
	}
}
