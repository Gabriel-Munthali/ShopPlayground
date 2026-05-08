<?php
/**
 * Maps OneKhusa webhook JSON to a WooCommerce order.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Order_Resolver class.
 */
class WC_Onekhusa_Order_Resolver {

	/**
	 * Resolve WC_Order from webhook payload.
	 *
	 * @param array $data Decoded JSON.
	 * @return WC_Order|null
	 */
	public static function find_order(array $data) {
		$meta = self::extract_meta_block($data);

		$reference_candidates = array_merge(
			self::find_property_values(
				$data,
				array(
					'sourceReferenceNumber',
					'rn',
					'merchantReferenceNumber',
					'merchantRequestReferenceNumber',
					'sourceReference',
					'referenceNumber',
					'reference',
					'clientReference',
				)
			),
			array(
				self::meta_get(
					$meta,
					array(
						'referenceNumber',
						'ReferenceNumber',
						'reference_number',
						'sourceReferenceNumber',
						'SourceReferenceNumber',
					)
				),
			)
		);
		foreach (array_unique(array_filter($reference_candidates)) as $ref) {
			$ref = sanitize_text_field($ref);
			foreach (array('_onekhusa_r2p_reference', '_onekhusa_source_reference') as $meta_key) {
				$q   = new WC_Order_Query(
					array(
						'limit'      => 1,
						'meta_key'   => $meta_key,
						'meta_value' => $ref,
						'return'     => 'ids',
					)
				);
				$ids = $q->get_orders();
				if (!empty($ids)) {
					return wc_get_order($ids[0]);
				}
			}
		}

		$transaction_candidates = self::find_property_values(
			$data,
			array(
				'paymentTransactionId',
				'ptid',
				'transactionId',
				'paymentTransactionCode',
				'providerTransactionId',
				'providerReference',
				'transactionReference',
				'transactionReferenceNumber',
			)
		);
		foreach (array_unique(array_filter($transaction_candidates)) as $ptid) {
			$ptid = sanitize_text_field($ptid);
			$q    = new WC_Order_Query(
				array(
					'limit'      => 1,
					'meta_key'   => '_onekhusa_ptid',
					'meta_value' => $ptid,
					'return'     => 'ids',
				)
			);
			$ids = $q->get_orders();
			if (!empty($ids)) {
				return wc_get_order($ids[0]);
			}
		}

		return null;
	}

	/**
	 * metaData / MetaData / nested transaction object (best-effort).
	 *
	 * @param array $data Payload.
	 * @return array
	 */
	private static function extract_meta_block(array $data) {
		foreach (array('metaData', 'MetaData', 'meta_data') as $k) {
			if (!empty($data[$k]) && is_array($data[$k])) {
				return $data[$k];
			}
		}
		foreach (array('transaction', 'Transaction', 'payload', 'Payload') as $wrap) {
			if (!empty($data[$wrap]) && is_array($data[$wrap])) {
				$inner         = $data[$wrap];
				$from_nested = self::extract_meta_block($inner);
				if (!empty($from_nested)) {
					return $from_nested;
				}
				if (self::meta_get($inner, array('referenceNumber', 'ReferenceNumber', 'timedAccountNumber', 'TimedAccountNumber')) !== '') {
					return $inner;
				}
			}
		}
		return array();
	}

	/**
	 * First non-empty string among keys.
	 *
	 * @param array $arr  Array.
	 * @param array $keys Keys.
	 * @return string
	 */
	private static function meta_get(array $arr, array $keys) {
		foreach ($keys as $k) {
			if (isset($arr[$k]) && $arr[$k] !== '' && $arr[$k] !== null) {
				return is_scalar($arr[$k]) ? (string) $arr[$k] : '';
			}
		}
		return '';
	}

	/**
	 * Recursively collect values for possible OneKhusa identifier keys.
	 *
	 * @param mixed $value Payload fragment.
	 * @param array $names Candidate key names.
	 * @return array
	 */
	private static function find_property_values($value, array $names) {
		$values = array();
		if (!is_array($value)) {
			return $values;
		}
		foreach ($value as $key => $child) {
			foreach ($names as $name) {
				if (strcasecmp((string) $key, (string) $name) === 0 && is_scalar($child) && (string) $child !== '') {
					$values[] = (string) $child;
				}
			}
			if (is_array($child)) {
				$values = array_merge($values, self::find_property_values($child, $names));
			}
		}
		return array_values(array_unique($values));
	}
}
