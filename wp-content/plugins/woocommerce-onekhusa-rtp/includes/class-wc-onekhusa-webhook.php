<?php
/**
 * REST webhook for OneKhusa callback events.
 *
 * Verify payload signing and field names per https://docs.onekhusa.com - this handler
 * only resolves the order and marks payment complete when appropriate.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Webhook class.
 */
class WC_Onekhusa_Webhook {

	/**
	 * Bootstrap REST route.
	 */
	public static function register() {
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register route.
	 */
	public static function register_routes() {
		register_rest_route(
			'onekhusa/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'handle'),
				'permission_callback' => array(__CLASS__, 'authorize'),
			)
		);
	}

	/**
	 * Returns the webhook token, creating and storing one when missing (e.g. activation hook did not run).
	 */
	private static function getOrCreateWebhookToken() {
		$token = get_option('wc_onekhusa_webhook_token');
		if (!is_string($token) || $token === '') {
			$token = wp_generate_password(48, false, false);
			add_option('wc_onekhusa_webhook_token', $token);
		}
		return $token;
	}

	/**
	 * Full webhook callback URL including secret token (`route.callbackApiUrl`).
	 *
	 * If `rest_url()` returns a `wp-json` URL, switches to `index.php?rest_route=` so callbacks work when
	 * pretty permalinks or `/wp-json/` are unavailable.
	 *
	 * @return string
	 */
	public static function get_callback_url() {
		self::getOrCreateWebhookToken();
		$token = (string) get_option('wc_onekhusa_webhook_token', '');
		$base  = rest_url('onekhusa/v1/webhook');
		if (is_string($base) && strpos($base, 'wp-json') !== false) {
			$base = add_query_arg(
				array(
					'rest_route' => '/onekhusa/v1/webhook',
				),
				home_url('/index.php')
			);
		}
		return add_query_arg(
			'token',
			rawurlencode($token),
			$base
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function authorize($request) {
		self::getOrCreateWebhookToken();
		$expected = (string) get_option('wc_onekhusa_webhook_token', '');
		if ($expected === '') {
			self::log('Webhook callback rejected: store webhook token option is empty.');
			return new WP_Error('forbidden', 'Webhook token missing.', array('status' => 403));
		}
		$token = (string) $request->get_param('token');
		if ($token === '') {
			self::log('Webhook callback rejected: missing token query parameter (callbackApiUrl must include ?token= matching WooCommerce).');
			return new WP_Error('forbidden', 'Invalid webhook token.', array('status' => 403));
		}
		if (!hash_equals($expected, $token)) {
			self::log('Webhook callback rejected: token mismatch (OneKhusa callbackApiUrl token must match this site).');
			return new WP_Error('forbidden', 'Invalid webhook token.', array('status' => 403));
		}
		return true;
	}

	/**
	 * Handle POST body (JSON).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle($request) {
		$raw  = $request->get_body();
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			self::log(
				'Webhook callback failed: invalid_json. body (truncated): ' . substr((string) $raw, 0, 800)
			);
			return new WP_REST_Response(
				array(
					'ok'    => false,
					'error' => 'invalid_json',
				),
				400
			);
		}

		$order = WC_Onekhusa_Order_Resolver::find_order($data);
		if (!$order) {
			self::log(
				'Webhook callback failed: order_not_found. Top-level keys: ' . implode(',', array_keys($data))
				. ' | body (truncated): ' . substr($raw, 0, 800)
			);
			return new WP_REST_Response(
				array(
					'ok'    => false,
					'error' => 'order_not_found',
				),
				404
			);
		}

		if ($order->is_paid()) {
			self::log_success(
				$order,
				array('ok' => true, 'already_paid' => true),
				'already_paid',
				$request
			);
			return new WP_REST_Response(array('ok' => true, 'already_paid' => true), 200);
		}

		$order->payment_complete();
		$order->add_order_note(
			__('OneKhusa: Payment confirmed via webhook callback.', 'woocommerce-onekhusa-rtp')
		);

		self::log_success($order, array('ok' => true), 'payment_complete', $request);

		return new WP_REST_Response(array('ok' => true), 200);
	}

	/**
	 * Proof for support: callback handler returned HTTP 200 with JSON body (no raw payload logged).
	 *
	 * @param WC_Order $order         Order.
	 * @param array    $response_body Response JSON (safe keys only).
	 * @param string   $kind          already_paid|payment_complete.
	 * @param WP_REST_Request $request Request.
	 */
	private static function log_success($order, array $response_body, $kind, $request) {
		$request_url = self::get_request_url_for_log($request);
		$summary = sprintf(
			'Webhook callback acknowledged: HTTP 200. kind=%s order_id=%d order_number=%s request_url=%s body=%s',
			sanitize_key($kind),
			(int) $order->get_id(),
			sanitize_text_field((string) $order->get_order_number()),
			esc_url_raw($request_url),
			wp_json_encode($response_body)
		);
		WC_Onekhusa_Logger::debug($summary);
	}

	/**
	 * Inbound webhook URL for audit logs (redacts token query value).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string
	 */
	private static function get_request_url_for_log($request) {
		$url = '';
		if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI'])) {
			$scheme = is_ssl() ? 'https' : 'http';
			$url    = $scheme . '://' . sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) . wp_unslash($_SERVER['REQUEST_URI']);
		}
		if ($url === '' && is_object($request) && method_exists($request, 'get_route')) {
			$url = rest_url(ltrim((string) $request->get_route(), '/'));
		}
		return (string) preg_replace('/([?&]token=)[^&]*/i', '$1[redacted]', $url);
	}

	/**
	 * @param string $message Message.
	 */
	private static function log($message) {
		WC_Onekhusa_Logger::warning($message);
	}
}
