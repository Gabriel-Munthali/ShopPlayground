<?php
/**
 * Uninstall OneKhusa RTP for WooCommerce.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

/**
 * Remove plugin options and known transients for the current site.
 *
 * @return void
 */
function wc_onekhusa_rtp_uninstall_site() {
	delete_option('woocommerce_onekhusa_rtp_settings');
	delete_option('wc_onekhusa_webhook_token');

	global $wpdb;

	$patterns = array(
		$wpdb->esc_like('_transient_wc_onekhusa_') . '%',
		$wpdb->esc_like('_transient_timeout_wc_onekhusa_') . '%',
	);

	foreach ($patterns as $pattern) {
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern));
	}
}

if (!is_multisite()) {
	wc_onekhusa_rtp_uninstall_site();
	return;
}

$offset = 0;
$limit  = 200;

do {
	$sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => $limit,
			'offset' => $offset,
		)
	);

	foreach ($sites as $site_id) {
		switch_to_blog((int) $site_id);
		wc_onekhusa_rtp_uninstall_site();
		restore_current_blog();
	}

	$offset += $limit;
} while (count($sites) === $limit);
