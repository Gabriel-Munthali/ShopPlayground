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
				'title'       => __('Status & checkout', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('What customers see at checkout. API endpoints follow OneKhusa documentation and are selected by environment below.', 'woocommerce-onekhusa-rtp'),
			),
			'enabled'     => array(
				'title'   => __('Enable/Disable', 'woocommerce-onekhusa-rtp'),
				'type'    => 'checkbox',
				'label'   => __('Enable OneKhusa Request To Pay (RTP)', 'woocommerce-onekhusa-rtp'),
				'default' => 'no',
			),
			'environment' => array(
				'title'       => __('Environment', 'woocommerce-onekhusa-rtp'),
				'type'        => 'select',
				'description' => __('Use sandbox while testing. Switch to live only after your production access is approved.', 'woocommerce-onekhusa-rtp'),
				'default'     => 'sandbox',
				'options'     => array(
					'sandbox' => __('Sandbox (testing)', 'woocommerce-onekhusa-rtp'),
					'live'    => __('Live (production)', 'woocommerce-onekhusa-rtp'),
				),
				'desc_tip'    => true,
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
			'section_logging' => array(
				'title'       => __('Logging (optional)', 'woocommerce-onekhusa-rtp'),
				'type'        => 'title',
				'description' => __('Diagnostic messages for troubleshooting.', 'woocommerce-onekhusa-rtp'),
			),
			'debug_log' => array(
				'title'       => __('Detailed logging', 'woocommerce-onekhusa-rtp'),
				'type'        => 'checkbox',
				'label'       => __('Log diagnostic info to WooCommerce → Status → Logs (source: onekhusa_rtp).', 'woocommerce-onekhusa-rtp'),
				'default'     => 'no',
				'description' => __('Turn on only while troubleshooting.', 'woocommerce-onekhusa-rtp'),
				'desc_tip'    => true,
			),
		);
	}
}
