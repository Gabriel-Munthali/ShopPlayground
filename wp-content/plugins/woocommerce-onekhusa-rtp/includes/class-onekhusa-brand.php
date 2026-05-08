<?php
/**
 * OneKhusa brand assets (checkout / blocks).
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * Resolved URLs under assets/images/.
 */
final class WC_Onekhusa_Brand {

	const FILE_FULL   = 'onekhusa-logo.svg';
	const FILE_ICON   = 'onekhusa-logo-letter.svg';

	/**
	 * @param string $filename Basename within assets/images/.
	 * @return string
	 */
	public static function asset_url($filename) {
		$filename = ltrim(str_replace('\\', '/', (string) $filename), '/');
		if ($filename === '' || preg_match('#[\\/]\.\.#', $filename)) {
			return '';
		}
		return esc_url_raw(plugins_url('/assets/images/' . $filename, WC_ONEKHUSA_RTP_PLUGIN_FILE));
	}

	/**
	 * Wordmark SVG (classic / blocks checkout payment label).
	 *
	 * @return string
	 */
	public static function get_logo_wordmark_url() {
		return self::asset_url(self::FILE_FULL);
	}

	/**
	 * Compact logomark (WooCommerce admin Payments gateway list thumbnails).
	 *
	 * @return string
	 */
	public static function get_logo_icon_url() {
		return self::asset_url(self::FILE_ICON);
	}

	/**
	 * Icons payload for WooCommerce Checkout Blocks (checkout label icon).
	 *
	 * @return array<int, array{id:string, src:string, alt:string}>
	 */
	public static function get_blocks_payment_icons() {
		$url = self::get_logo_wordmark_url();
		if ($url === '') {
			return array();
		}
		return array(
			array(
				'id'  => 'onekhusa',
				'src' => $url,
				/* translators: payment method brand label */
				'alt' => __('OneKhusa', 'woocommerce-onekhusa-rtp'),
			),
		);
	}
}
