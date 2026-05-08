<?php
/**
 * WooCommerce logging helper for OneKhusa RTP.
 *
 * Errors and warnings always log. Debug (verbose) logs require the admin setting.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

defined('ABSPATH') || exit;

/**
 * WC_Onekhusa_Logger class.
 */
final class WC_Onekhusa_Logger {

	/**
	 * WooCommerce log source tag.
	 */
	private const SOURCE = 'onekhusa_rtp';

	/**
	 * Whether detailed logging is enabled in gateway settings.
	 *
	 * @return bool
	 */
	public static function is_debug_enabled() {
		$opts = get_option('woocommerce_onekhusa_rtp_settings', array());
		if (!is_array($opts)) {
			return false;
		}
		return isset($opts['debug_log']) && 'yes' === $opts['debug_log'];
	}

	/**
	 * Always log at error level when WooCommerce logger exists.
	 *
	 * @param string $message Message.
	 */
	public static function error($message) {
		self::emit('error', (string) $message, true);
	}

	/**
	 * Always log at warning level when WooCommerce logger exists.
	 *
	 * @param string $message Message.
	 */
	public static function warning($message) {
		self::emit('warning', (string) $message, true);
	}

	/**
	 * Log at debug level only when detailed logging is enabled.
	 *
	 * @param string $message Message.
	 */
	public static function debug($message) {
		self::emit('debug', (string) $message, self::is_debug_enabled());
	}

	/**
	 * @param string $level   Log level for wc_get_logger()->log().
	 * @param string $message Message.
	 * @param bool   $allow   Whether to write.
	 */
	private static function emit($level, $message, $allow) {
		if (!$allow || !function_exists('wc_get_logger')) {
			return;
		}
		wc_get_logger()->log((string) $level, $message, array('source' => self::SOURCE));
	}
}
