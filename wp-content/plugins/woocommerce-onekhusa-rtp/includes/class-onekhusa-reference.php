<?php
/**
 * Request To Pay reference string builder.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Reference class.
 */
class WC_Onekhusa_Reference {

	/**
	 * Unique reference for Request To Pay (5-25 alphanumeric chars per API).
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public static function build_for_order($order) {
		$part = (string) (int) $order->get_id();
		$part = str_pad($part, 3, '0', STR_PAD_LEFT);
		$ref  = 'WC' . $part;
		$ref  = preg_replace('/[^A-Za-z0-9]/', '', $ref);
		if (strlen($ref) < 5) {
			$ref = str_pad($ref, 5, '0');
		}
		if (strlen($ref) > 25) {
			$ref = substr($ref, 0, 25);
		}
		return $ref;
	}
}
