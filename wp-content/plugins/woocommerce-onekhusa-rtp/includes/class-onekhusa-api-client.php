<?php
/**
 * HTTP client for OneKhusa Hosted Checkout RTP.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Api_Client class.
 */
class WC_Onekhusa_Api_Client {

	/**
	 * POST Hosted Checkout RTP Initiate (full URL, credentials in JSON body).
	 *
	 * @param string $initiate_url Full URL e.g. https://api.onekhusa.com/sandbox/v1/checkout/rtp/initiate
	 * @param string $idempotency  Idempotency key (optional header).
	 * @param array  $payload      JSON body (authentication, merchant, payment, route).
	 * @return array{ response: array|WP_Error, code: int, body_raw: string, body: array|null }
	 */
	public static function post_checkout_rtp_initiate($initiate_url, $idempotency, array $payload) {
		$headers = array(
			'Content-Type'     => 'application/json; charset=utf-8',
			'Accept'           => 'application/json',
			'Accept-Language'  => 'en',
		);
		if ($idempotency !== '') {
			$headers['X-Idempotency-Key'] = (string) $idempotency;
		}

		$args = array(
			'timeout' => 45,
			'headers' => $headers,
			'body'    => wp_json_encode($payload),
		);

		$response = wp_remote_post($initiate_url, $args);

		if (is_wp_error($response)) {
			return array(
				'response' => $response,
				'code'     => 0,
				'body_raw' => '',
				'body'     => null,
			);
		}

		$code     = wp_remote_retrieve_response_code($response);
		$body_raw = wp_remote_retrieve_body($response);
		$body     = json_decode($body_raw, true);

		return array(
			'response' => $response,
			'code'     => (int) $code,
			'body_raw' => (string) $body_raw,
			'body'     => is_array($body) ? $body : null,
		);
	}
}
