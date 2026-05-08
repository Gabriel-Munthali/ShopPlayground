<?php
/**
 * Builder for OneKhusa Hosted Checkout RTP Initiate payloads.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Rtp_Initiate_Payload_Builder class.
 */
class WC_Onekhusa_Rtp_Initiate_Payload_Builder {

	/**
	 * @var array
	 */
	private $authentication = array();

	/**
	 * @var array
	 */
	private $merchant = array();

	/**
	 * @var array
	 */
	private $payment = array();

	/**
	 * @var array
	 */
	private $route = array();

	/**
	 * @param string $api_key API key.
	 * @param string $api_secret API secret.
	 * @return self
	 */
	public function with_authentication($api_key, $api_secret) {
		$this->authentication = array(
			'apiKey'    => (string) $api_key,
			'apiSecret' => (string) $api_secret,
		);
		return $this;
	}

	/**
	 * @param string $organisation_id Organisation ID.
	 * @param int    $merchant_account_number Merchant account number.
	 * @return self
	 */
	public function with_merchant($organisation_id, $merchant_account_number) {
		$this->merchant = array(
			'organisationId'        => (string) $organisation_id,
			'merchantAccountNumber' => (int) $merchant_account_number,
		);
		return $this;
	}

	/**
	 * @param string $source_reference_number Source reference number.
	 * @param string $description Payment description.
	 * @param float  $amount Amount.
	 * @return self
	 */
	public function with_payment($source_reference_number, $description, $amount) {
		$this->payment = array(
			'sourceReferenceNumber' => (string) $source_reference_number,
			'description'           => (string) $description,
			'amount'                => (float) $amount,
		);
		return $this;
	}

	/**
	 * @param string $success_redirection_url Payer redirect after successful checkout (Request To Pay Checkout route.successRedirectionUrl).
	 * @param string $failure_redirection_url Payer redirect after failed checkout (route.failureRedirectionUrl).
	 * @param string $callback_api_url Webhook endpoint (route.callbackApiUrl).
	 * @return self
	 */
	public function with_route($success_redirection_url, $failure_redirection_url, $callback_api_url) {
		$this->route = array(
			'successRedirectionUrl' => (string) $success_redirection_url,
			'failureRedirectionUrl' => (string) $failure_redirection_url,
			'callbackApiUrl'        => (string) $callback_api_url,
		);
		return $this;
	}

	/**
	 * @return array
	 */
	public function build() {
		return array(
			'authentication' => $this->authentication,
			'merchant'       => $this->merchant,
			'payment'        => $this->payment,
			'route'          => $this->route,
		);
	}
}
