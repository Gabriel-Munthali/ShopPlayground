<?php
/**
 * Admin form fields for the OneKhusa gateway.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Gateway_Settings class.
 */
class WC_Onekhusa_Gateway_Settings {

	/**
	 * Gateway option definitions for WC_Payment_Gateway::form_fields.
	 *
	 * @return array
	 */
	public static function get_form_fields() {
		return array(
			'section_status_checkout' => array(
				'title'       => __('Status & checkout text', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('What customers see at checkout.', 'woocommerce-onekhusa-rtp'),
			),
			'enabled'     => array(
				'title'   => __('Enable/Disable', 'woocommerce-onekhusa-rtp'),
				'type'    => 'checkbox',
				'label'   => __('Enable OneKhusa Request To Pay (RTP)', 'woocommerce-onekhusa-rtp'),
				'default' => 'no',
			),
			'title'       => array(
				'title'       => __('Title', 'woocommerce-onekhusa-rtp'),
				'type'        => 'text',
				'description' => __('Name shown at checkout.', 'woocommerce-onekhusa-rtp'),
				'default'     => __('Pay with OneKhusa', 'woocommerce-onekhusa-rtp'),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __('Description', 'woocommerce-onekhusa-rtp'),
				'type'        => 'textarea',
				'description' => __('Short note shown under the title at checkout.', 'woocommerce-onekhusa-rtp'),
				'default'     => __('After placing your order you will be redirected to OneKhusa to complete payment. You can return here when finished.', 'woocommerce-onekhusa-rtp'),
			),
			'section_account' => array(
				'title'       => __('Your OneKhusa account', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('Find these in the OneKhusa portal.', 'woocommerce-onekhusa-rtp'),
			),
			'merchant_account_number' => array(
				'title'       => __('Merchant Account Number', 'woocommerce-onekhusa-rtp'),
				'type'        => 'text',
				'description' => __('Your OneKhusa merchant account number.', 'woocommerce-onekhusa-rtp'),
				'desc_tip'    => true,
			),
			'organisation_id' => array(
				'title'       => __('Organisation ID', 'woocommerce-onekhusa-rtp'),
				'type'        => 'text',
				'description' => __('From the OneKhusa portal: Settings → Profile.', 'woocommerce-onekhusa-rtp'),
				'desc_tip'    => true,
			),
			'section_credentials' => array(
				'title'       => __('API credentials', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('Generated in the OneKhusa portal. Keep these private.', 'woocommerce-onekhusa-rtp'),
			),
			'api_key'     => array(
				'title' => __('API key', 'woocommerce-onekhusa-rtp'),
				'type'  => 'password',
			),
			'api_secret'  => array(
				'title' => __('API secret', 'woocommerce-onekhusa-rtp'),
				'type'  => 'password',
			),
			'section_endpoints' => array(
				'title'       => __('API endpoints', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('Choose sandbox while testing, live once approved.', 'woocommerce-onekhusa-rtp'),
			),
			'api_base' => array(
				'title'       => __('API base URL', 'woocommerce-onekhusa-rtp'),
				'type'        => 'text',
				'description' => __('Use the sandbox URL while testing, the live URL once approved.', 'woocommerce-onekhusa-rtp'),
				'default'     => 'https://api.onekhusa.com/sandbox/v1',
				'desc_tip'    => true,
			),
			'hosted_checkout_base' => array(
				'title'       => __('Hosted Checkout base URL', 'woocommerce-onekhusa-rtp'),
				'type'        => 'text',
				'description' => __('Where shoppers go to authorise payment. Default works for most stores.', 'woocommerce-onekhusa-rtp'),
				'default'     => 'https://checkout.onekhusa.com/requestToPay/initiate',
				'desc_tip'    => true,
			),
			'section_advanced' => array(
				'title'       => __('Advanced (optional)', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('Most stores can leave this section alone.', 'woocommerce-onekhusa-rtp'),
			),
			'rtp_checkout_initiate_url' => array(
				'title'       => __('Hosted Checkout RTP Initiate URL (Optional override)', 'woocommerce-onekhusa-rtp'),
				'type'        => 'text',
				'description' => __('Leave empty unless OneKhusa gave you a different URL.', 'woocommerce-onekhusa-rtp'),
				'default'     => '',
				'placeholder' => __('Default: {API base}/checkout/rtp/initiate', 'woocommerce-onekhusa-rtp'),
				'desc_tip'    => true,
			),
			'debug_log' => array(
				'title'       => __('Detailed logging (Optional)', 'woocommerce-onekhusa-rtp'),
				'type'        => 'checkbox',
				'label'       => __('Log diagnostic info to WooCommerce > Status > Logs (source: onekhusa_rtp).', 'woocommerce-onekhusa-rtp'),
				'default'     => 'no',
				'description' => __('Turn on only while troubleshooting.', 'woocommerce-onekhusa-rtp'),
				'desc_tip'    => true,
			),
		);
	}
}
