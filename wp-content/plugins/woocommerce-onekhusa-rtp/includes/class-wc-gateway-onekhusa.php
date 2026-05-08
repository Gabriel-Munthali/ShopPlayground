<?php
/**
 * OneKhusa Request To Pay (RTP) payment gateway (Hosted Checkout RTP Initiate API).
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Gateway_Onekhusa class.
 */
class WC_Gateway_Onekhusa extends WC_Payment_Gateway {

	/**
	 * Gateway option key slug (stored as woocommerce_${id}_settings).
	 *
	 * @var string
	 */
	public const ID = 'onekhusa_rtp';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = WC_Onekhusa_Brand::get_logo_wordmark_url();
		$this->has_fields         = false;
		$this->method_title       = __('OneKhusa (Request To Pay)', 'woocommerce-onekhusa-rtp');
		$this->method_description = __('Accept payment through OneKhusa Request To Pay Hosted Checkout.', 'woocommerce-onekhusa-rtp');
		$this->supports           = array('products');

		$this->init_form_fields();
		$this->maybe_migrate_legacy_api_url();
		$this->init_settings();

		$this->title       = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled     = $this->get_option('enabled');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	 * Gateway icon markup: compact letter mark on admin Payments list thumbnails;
	 * full wordmark on storefront checkout (classic).
	 *
	 * @return string
	 */
	public function get_icon() {
		$letter    = WC_Onekhusa_Brand::get_logo_icon_url();
		$wordmark  = WC_Onekhusa_Brand::get_logo_wordmark_url();
		$use_thumb = function_exists('is_admin') && is_admin() && ! wp_doing_ajax();
		if ($use_thumb && $letter !== '') {
			$url = $letter;
		} elseif (!$use_thumb && $wordmark !== '') {
			$url = $wordmark;
		} elseif ($letter !== '') {
			$url = $letter;
		} elseif ($wordmark !== '') {
			$url = $wordmark;
		} else {
			$url = '';
		}
		if ($url === '') {
			return apply_filters('woocommerce_gateway_icon', '', $this->id);
		}
		$url  = WC_HTTPS::force_https_url($url);
		$icon = '<img src="' . esc_url($url) . '" alt="' . esc_attr($this->get_title()) . '" />';
		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}

	/**
	 * Admin fields.
	 */
	public function init_form_fields() {
		$this->form_fields = WC_Onekhusa_Gateway_Settings::get_form_fields();
	}

	/**
	 * One-time migration from old initiate_api_url / checkout_redirect_base to api_base.
	 */
	private function maybe_migrate_legacy_api_url() {
		$key = $this->get_option_key();
		$opt = get_option($key, array());
		if (!is_array($opt) || (!empty($opt['api_base']) && is_string($opt['api_base']))) {
			return;
		}
		$legacy = isset($opt['initiate_api_url']) ? trim((string) $opt['initiate_api_url']) : '';
		if ($legacy === '') {
			return;
		}
		$base = 'https://api.onekhusa.com/sandbox/v1';
		if (preg_match('#^(https://[^/]+/(?:sandbox|live)/v1)(?:/|$)#i', $legacy, $m)) {
			$base = untrailingslashit($m[1]);
		}
		$opt['api_base'] = $base;
		unset($opt['initiate_api_url'], $opt['checkout_redirect_base']);
		update_option($key, $opt);
	}

	/**
	 * Read saved settings: prefer api_base; fall back to legacy initiate_api_url (derive base if possible).
	 *
	 * @return string
	 */
	private function get_api_base() {
		$base = trim((string) $this->get_option('api_base', ''));
		if ($base !== '') {
			return untrailingslashit(esc_url_raw($base));
		}
		$legacy = trim((string) $this->get_option('initiate_api_url', ''));
		if ($legacy === '') {
			return 'https://api.onekhusa.com/sandbox/v1';
		}
		if (preg_match('#^(https://[^/]+/(?:sandbox|live)/v1)(?:/|$)#i', $legacy, $m)) {
			return untrailingslashit($m[1]);
		}
		return untrailingslashit(esc_url_raw($legacy));
	}

	/**
	 * Merchant account as integer for API bodies.
	 *
	 * @return int
	 */
	private function get_merchant_account_int() {
		return (int) preg_replace('/\D/', '', (string) $this->get_option('merchant_account_number', ''));
	}

	/**
	 * Hosted Checkout RTP Initiate POST URL.
	 * Override via gateway setting; otherwise {api_base}/checkout/rtp/initiate.
	 *
	 * @return string
	 */
	private function get_rtp_checkout_initiate_url() {
		$override = trim((string) $this->get_option('rtp_checkout_initiate_url', ''));
		if ($override !== '') {
			$override = str_replace('/api/checkout/rtp/initiate', '/checkout/rtp/initiate', $override);
			return esc_url_raw($override);
		}
		return esc_url_raw(untrailingslashit($this->get_api_base()) . '/checkout/rtp/initiate');
	}

	/**
	 * Hosted Checkout redirect URL (Request To Pay Checkout: ?ptid={paymentTransactionId} only).
	 *
	 * @param string $ptid Payment transaction id from initiate response.
	 * @return string
	 */
	private function get_hosted_checkout_redirect_url($ptid) {
		$base = trim((string) $this->get_option('hosted_checkout_base', ''));
		if ($base === '') {
			$base = 'https://checkout.onekhusa.com/requestToPay/initiate';
		}
		$base = untrailingslashit(esc_url_raw($base));
		return add_query_arg(
			array(
				'ptid' => (string) $ptid,
			),
			$base
		);
	}

	private function build_idempotency_key($merchant_account_int, $order_id) {
		$key = sprintf(
			'%d-wcrtp-%d-%s',
			(int) $merchant_account_int,
			(int) $order_id,
			substr(hash('sha256', uniqid('', true) . wp_rand()), 0, 24)
		);
		return substr(preg_replace('/[^a-zA-Z0-9-]/', '', $key), 0, 80);
	}

	/**
	 * Payment transaction id from initiate JSON when present.
	 *
	 * @param array|null $body Decoded JSON body.
	 * @return string
	 */
	private function extract_payment_transaction_id_from_body($body) {
		if (!is_array($body)) {
			return '';
		}
		$keys = array(
			'paymentTransactionId',
			'PaymentTransactionId',
			'payment_transaction_id',
			'transactionId',
			'TransactionId',
		);
		foreach ($keys as $k) {
			if (!empty($body[$k]) && is_scalar($body[$k])) {
				$id = sanitize_text_field((string) $body[$k]);
				if ($id !== '') {
					return $id;
				}
			}
		}
		if (!empty($body['data']) && is_array($body['data'])) {
			return $this->extract_payment_transaction_id_from_body($body['data']);
		}
		return '';
	}

	/**
	 * Source reference from initiate response when present (stored for reconciliation / webhooks).
	 *
	 * @param array|null $body Response body.
	 * @param string     $fallback Default (e.g. order reference sent in request).
	 * @return string
	 */
	private function extract_source_reference_from_body($body, $fallback) {
		if (!is_array($body)) {
			return $fallback;
		}
		foreach (array('sourceReferenceNumber', 'SourceReferenceNumber', 'referenceNumber', 'ReferenceNumber') as $k) {
			if (!empty($body[$k]) && is_scalar($body[$k])) {
				$rn = sanitize_text_field((string) $body[$k]);
				if ($rn !== '') {
					return $rn;
				}
			}
		}
		if (!empty($body['data']) && is_array($body['data'])) {
			return $this->extract_source_reference_from_body($body['data'], $fallback);
		}
		return $fallback;
	}

	/**
	 * Private order note when payment initiation fails (WooCommerce already created the order).
	 *
	 * @param WC_Order $order   Order.
	 * @param string   $summary Short reason for admins.
	 */
	private function add_order_note_initiate_failed($order, $summary) {
		$order->add_order_note(
			wp_kses_post($summary),
			false,
			true
		);
	}

	/**
	 * Process payment: POST Hosted Checkout RTP Initiate, then redirect to OneKhusa Hosted Checkout.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment($order_id) {
		$order_id = (int) $order_id;
		$order    = wc_get_order($order_id);
		if (!$order) {
			WC_Onekhusa_Logger::error(sprintf('process_payment failed: order not found. order_id=%d', $order_id));
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$order_num = sanitize_text_field((string) $order->get_order_number());

		$api_key = trim((string) $this->get_option('api_key', ''));
		$secret  = trim((string) $this->get_option('api_secret', ''));
		$org_id  = trim((string) $this->get_option('organisation_id', ''));
		if ($api_key === '' || $secret === '' || $org_id === '') {
			WC_Onekhusa_Logger::error(
				sprintf(
					'process_payment failed: gateway not configured (missing api_key, api_secret, or organisation_id). order_id=%d order_number=%s',
					$order_id,
					$order_num
				)
			);
			wc_add_notice(__('OneKhusa payment is not configured. Please contact the store administrator.', 'woocommerce-onekhusa-rtp'), 'error');
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$merchant = $this->get_merchant_account_int();
		if ($merchant < 10000000 || $merchant > 99999999) {
			WC_Onekhusa_Logger::error(
				sprintf(
					'process_payment failed: invalid merchant account number. order_id=%d order_number=%s',
					$order_id,
					$order_num
				)
			);
			wc_add_notice(__('Merchant account number must be a valid 8-digit value.', 'woocommerce-onekhusa-rtp'), 'error');
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$ref = WC_Onekhusa_Reference::build_for_order($order);

		$amount_dec = wc_format_decimal($order->get_total('edit'), wc_get_price_decimals(), false);
		$amount     = (float) $amount_dec;

		$success_redirect_url = $order->get_checkout_order_received_url();
		$failure_redirect_url = $success_redirect_url;
		$webhook_url          = WC_Onekhusa_Webhook::get_callback_url();

		$description = mb_substr(
			sprintf(
				/* translators: %s: order number */
				__('Order %s', 'woocommerce-onekhusa-rtp'),
				$order->get_order_number()
			),
			0,
			200
		);

		$payload = (new WC_Onekhusa_Rtp_Initiate_Payload_Builder())
			->with_authentication($api_key, $secret)
			->with_merchant($org_id, $merchant)
			->with_payment($ref, $description, $amount)
			->with_route($success_redirect_url, $failure_redirect_url, $webhook_url)
			->build();

		$initiate_url = $this->get_rtp_checkout_initiate_url();

		$idempotency = $this->build_idempotency_key($merchant, $order_id);
		$attempt     = WC_Onekhusa_Api_Client::post_checkout_rtp_initiate($initiate_url, $idempotency, $payload);
		if (is_wp_error($attempt['response'])) {
			WC_Onekhusa_Logger::error(
				sprintf(
					'Hosted Checkout RTP Initiate request failed (order_id=%d order_number=%s): %s',
					$order_id,
					$order_num,
					$attempt['response']->get_error_message()
				)
			);
			$this->add_order_note_initiate_failed(
				$order,
				sprintf(
					/* translators: %s: transport error message */
					__('OneKhusa Hosted Checkout RTP Initiate could not be reached: %s', 'woocommerce-onekhusa-rtp'),
					sanitize_text_field($attempt['response']->get_error_message())
				)
			);
			$order->save();
			wc_add_notice(sprintf(__('Unable to reach the payment service. Your order (%s) was saved as awaiting payment—you can try again later.', 'woocommerce-onekhusa-rtp'), $order->get_order_number()), 'error');
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$code     = $attempt['code'];
		$body_raw = $attempt['body_raw'];
		$body     = $attempt['body'];

		$checkout_ptid = is_array($body) ? $this->extract_payment_transaction_id_from_body($body) : '';
		if ($code < 200 || $code >= 300 || !is_array($body) || $checkout_ptid === '') {
			WC_Onekhusa_Logger::error(
				sprintf(
					'Hosted Checkout RTP Initiate rejected or invalid response (order_id=%1$d order_number=%2$s HTTP %3$d). Body: %4$s',
					$order_id,
					$order_num,
					(int) $code,
					substr($body_raw, 0, 500)
				)
			);

			$note_detail = sprintf(
				/* translators: 1: HTTP status code, 2: short response snippet */
				__('Hosted Checkout RTP Initiate failed (HTTP %1$d). Snippet: %2$s', 'woocommerce-onekhusa-rtp'),
				(int) $code,
				sanitize_text_field(substr(wp_strip_all_tags($body_raw), 0, 350))
			);
			$this->add_order_note_initiate_failed($order, $note_detail);
			$order->save();

			wc_add_notice(sprintf(__('Payment could not be started. Your order (%s) was saved as awaiting payment—you can try again from your account orders or contact us.', 'woocommerce-onekhusa-rtp'), $order->get_order_number()), 'error');
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$ptid = $checkout_ptid;
		$rn   = $this->extract_source_reference_from_body($body, $ref);

		$order->update_meta_data('_onekhusa_r2p_reference', $ref);
		$order->update_meta_data('_onekhusa_source_reference', $rn);
		$order->update_meta_data('_onekhusa_ptid', $ptid);
		$order->set_transaction_id($ptid);
		$order->update_status('pending', __('OneKhusa: Request To Pay initiated.', 'woocommerce-onekhusa-rtp'));
		$order->save();

		$redirect = $this->get_hosted_checkout_redirect_url($ptid);

		WC_Onekhusa_Logger::debug(
			sprintf(
				'RTP initiate success: order_id=%d order_number=%s ptid=%s reference=%s status=pending success_redirect=%s failure_redirect=%s callback_url=%s',
				$order_id,
				$order_num,
				sanitize_text_field($ptid),
				sanitize_text_field($rn),
				esc_url_raw($success_redirect_url),
				esc_url_raw($failure_redirect_url),
				esc_url_raw($webhook_url)
			)
		);

		return array(
			'result'   => 'success',
			'redirect' => $redirect,
		);
	}
}
