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
	 * Sandbox API base URL (`/sandbox/v1`), per OneKhusa documentation.
	 *
	 * @var string
	 */
	private const API_BASE_SANDBOX = 'https://api.onekhusa.com/sandbox/v1';

	/**
	 * Production API base URL (`/live/v1`), per OneKhusa documentation.
	 *
	 * @var string
	 */
	private const API_BASE_LIVE = 'https://api.onekhusa.com/live/v1';

	/**
	 * Hosted checkout redirect base before ?ptid= (Request To Pay Checkout docs).
	 *
	 * @var string
	 */
	private const HOSTED_CHECKOUT_PTID_BASE = 'https://checkout.onekhusa.com/requestToPay/initiate';

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
		$this->maybe_migrate_gateway_settings();
		$this->init_settings();

		$this->title       = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled     = $this->get_option('enabled');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	 * Option keys removed from the admin UI; stripped on save and during settings migration.
	 *
	 * Includes removed URL overrides, legacy single-pair credentials, and superseded slugs.
	 *
	 * @return string[]
	 */
	private static function deprecated_option_keys() {
		return array(
			'api_base',
			'initiate_api_url',
			'checkout_redirect_base',
			'hosted_checkout_base',
			'rtp_checkout_initiate_url',
			'api_key',
			'api_secret',
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function process_admin_options() {
		parent::process_admin_options();
		foreach (self::deprecated_option_keys() as $k) {
			unset($this->settings[$k]);
		}
		update_option($this->get_option_key(), $this->settings);
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
	 * Migrates legacy `api_key` / `api_secret` into sandbox- and production-specific option keys,
	 * then removes the legacy entries from `$opt`.
	 *
	 * When all four per-environment fields are still empty and legacy values exist, copies only
	 * into the slot that matches the stored `environment` so production credentials are never
	 * populated from sandbox-only keys.
	 *
	 * @param array $opt Gateway settings; modified in place.
	 * @return bool True if this method altered `$opt` (including legacy key removal).
	 */
	private static function migrate_legacy_api_credentials(array &$opt) {
		if (!array_key_exists('api_key', $opt) && !array_key_exists('api_secret', $opt)) {
			return false;
		}

		$legacy_key    = isset($opt['api_key']) ? trim((string) $opt['api_key']) : '';
		$legacy_secret = isset($opt['api_secret']) ? trim((string) $opt['api_secret']) : '';

		$sk = isset($opt['api_key_sandbox']) ? trim((string) $opt['api_key_sandbox']) : '';
		$ss = isset($opt['api_secret_sandbox']) ? trim((string) $opt['api_secret_sandbox']) : '';
		$lk = isset($opt['api_key_live']) ? trim((string) $opt['api_key_live']) : '';
		$ls = isset($opt['api_secret_live']) ? trim((string) $opt['api_secret_live']) : '';

		$new_all_empty = ($sk === '' && $ss === '' && $lk === '' && $ls === '');

		if ($new_all_empty && ($legacy_key !== '' || $legacy_secret !== '')) {
			$env = isset($opt['environment']) ? trim((string) $opt['environment']) : '';
			if ('live' !== $env) {
				$env = 'sandbox';
			}
			if ('live' === $env) {
				$opt['api_key_live']    = $legacy_key;
				$opt['api_secret_live'] = $legacy_secret;
			} else {
				$opt['api_key_sandbox']    = $legacy_key;
				$opt['api_secret_sandbox'] = $legacy_secret;
			}
		}

		unset($opt['api_key'], $opt['api_secret']);
		return true;
	}

	/**
	 * Ensures stored settings match the current plugin schema: credential migration, `environment`
	 * backfill from removed URL fields, and removal of deprecated option keys.
	 *
	 * Runs on gateway construction; persists only when something changed.
	 */
	private function maybe_migrate_gateway_settings() {
		$key = $this->get_option_key();
		$opt = get_option($key, array());
		if (!is_array($opt)) {
			return;
		}

		$credential_changed = self::migrate_legacy_api_credentials($opt);

		$deprecated = self::deprecated_option_keys();
		$has_deprecated = false;
		foreach ($deprecated as $d) {
			if (array_key_exists($d, $opt)) {
				$has_deprecated = true;
				break;
			}
		}
		$env = isset($opt['environment']) ? trim((string) $opt['environment']) : '';
		$needs_env = ('sandbox' !== $env && 'live' !== $env);

		if (!$credential_changed && !$has_deprecated && !$needs_env) {
			return;
		}

		if ($needs_env) {
			$opt['environment'] = $this->infer_environment_from_legacy_options($opt);
		}

		foreach ($deprecated as $d) {
			unset($opt[ $d ]);
		}

		update_option($key, $opt);
	}

	/**
	 * Infers the `environment` option (`sandbox` or `live`) from legacy per-URL settings removed from the admin UI.
	 *
	 * @param array $opt Raw gateway options (may still contain deprecated URL keys).
	 * @return string `sandbox` for testing API hosts, `live` for production API hosts.
	 */
	private function infer_environment_from_legacy_options(array $opt) {
		$urls = array();
		foreach (array('api_base', 'initiate_api_url', 'rtp_checkout_initiate_url') as $field) {
			if (!empty($opt[ $field ]) && is_string($opt[ $field ])) {
				$urls[] = trim((string) $opt[ $field ]);
			}
		}
		foreach ($urls as $url) {
			if (preg_match('#/sandbox/v1(?:/|$)#i', $url)) {
				return 'sandbox';
			}
			if (preg_match('#/live/v1(?:/|$)#i', $url)) {
				return 'live';
			}
		}
		foreach ($urls as $url) {
			if ($url !== '') {
				WC_Onekhusa_Logger::warning(
					'OneKhusa RTP: a stored URL could not be mapped to sandbox or live. Defaulting to sandbox; choose Live in WooCommerce payment settings if this site uses production.'
				);
				break;
			}
		}
		return 'sandbox';
	}

	/**
	 * Resolves the OneKhusa API base for the merchant’s selected environment (sandbox vs production).
	 *
	 * Values are fixed constants aligned with docs.onekhusa.com; not overridable in wp-admin.
	 *
	 * @return string Full base URL including version path segment.
	 */
	private function get_api_base() {
		$env = trim((string) $this->get_option('environment', 'sandbox'));
		if ('live' === $env) {
			return self::API_BASE_LIVE;
		}
		return self::API_BASE_SANDBOX;
	}

	/**
	 * Loads the trimmed API key and secret for the active environment (sandbox testing or production).
	 *
	 * @return array{0: string, 1: string} Tuple of API key, API secret.
	 */
	private function get_api_credentials_for_current_environment() {
		$env = trim((string) $this->get_option('environment', 'sandbox'));
		if ('live' === $env) {
			return array(
				trim((string) $this->get_option('api_key_live', '')),
				trim((string) $this->get_option('api_secret_live', '')),
			);
		}
		return array(
			trim((string) $this->get_option('api_key_sandbox', '')),
			trim((string) $this->get_option('api_secret_sandbox', '')),
		);
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
	 * Hosted Checkout RTP Initiate POST URL: {api_base}/checkout/rtp/initiate.
	 *
	 * @return string
	 */
	private function get_rtp_checkout_initiate_url() {
		return esc_url_raw(untrailingslashit($this->get_api_base()) . '/checkout/rtp/initiate');
	}

	/**
	 * Hosted Checkout redirect URL (Request To Pay Checkout: ?ptid={paymentTransactionId} only).
	 *
	 * @param string $ptid Payment transaction id from initiate response.
	 * @return string
	 */
	private function get_hosted_checkout_redirect_url($ptid) {
		$base = untrailingslashit(esc_url_raw(self::HOSTED_CHECKOUT_PTID_BASE));
		return add_query_arg(
			array(
				'ptid' => (string) $ptid,
			),
			$base
		);
	}

	/**
	 * Builds a bounded idempotency key for Hosted Checkout RTP Initiate requests.
	 *
	 * @param int $merchant_account_int Normalized merchant account number.
	 * @param int $order_id             WooCommerce order ID.
	 * @return string Alphanumeric key (max length enforced for API compatibility).
	 */
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
	 * Initiates Hosted Checkout RTP: POST `/checkout/rtp/initiate`, then redirects the shopper.
	 *
	 * Uses API credentials and base URL for the configured environment (sandbox vs production).
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array{result: string, redirect: string} WooCommerce payment result shape.
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

		list($api_key, $secret) = $this->get_api_credentials_for_current_environment();
		$org_id                 = trim((string) $this->get_option('organisation_id', ''));
		if ($api_key === '' || $secret === '' || $org_id === '') {
			$env = trim((string) $this->get_option('environment', 'sandbox'));
			$env_label = ('live' === $env)
				? __('live', 'woocommerce-onekhusa-rtp')
				: __('sandbox', 'woocommerce-onekhusa-rtp');
			WC_Onekhusa_Logger::error(
				sprintf(
					'process_payment failed: gateway not configured (missing %s API key/secret or organisation_id). order_id=%d order_number=%s',
					$env_label,
					$order_id,
					$order_num
				)
			);
			wc_add_notice(
				sprintf(
					/* translators: %s: Localized environment label (sandbox testing or live/production). */
					__('OneKhusa payment is not configured for the %s environment. Please contact the store administrator.', 'woocommerce-onekhusa-rtp'),
					$env_label
				),
				'error'
			);
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
		$failure_redirect_url = $order->get_checkout_payment_url( true );
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
