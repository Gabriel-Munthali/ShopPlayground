<?php
/**
 * End-to-end check: create a pending order with OneKhusa meta, resolve via webhook JSON.
 *
 * Run inside the project (WordPress + WooCommerce required):
 *   php wp-content/plugins/woocommerce-onekhusa-rtp/tests/integration/webhook-lookup.php
 *
 * @package WooCommerce_Onekhusa_RTP
 */

$wp_load = realpath(__DIR__ . '/../../../../wp-load.php');
if (! $wp_load || ! is_readable($wp_load)) {
	fwrite(STDERR, "wp-load.php not found. Run this from a full WordPress tree.\n");
	exit(1);
}

require_once $wp_load;

if (! function_exists('wc_create_order')) {
	fwrite(STDERR, "WooCommerce is not active.\n");
	exit(1);
}

if (! class_exists('WC_Onekhusa_Order_Resolver') || ! class_exists('WC_Onekhusa_Reference')) {
	fwrite(STDERR, "WooCommerce OneKhusa RTP must be active (classes not loaded).\n");
	exit(1);
}

$order = wc_create_order();
if (! $order instanceof WC_Order) {
	fwrite(STDERR, "Could not create test order.\n");
	exit(1);
}

$ref = WC_Onekhusa_Reference::build_for_order($order);

$order->set_payment_method('onekhusa_rtp');
$order->set_status('pending');
$order->update_meta_data('_onekhusa_r2p_reference', $ref);
$order->save();

$payload = array(
	'event'    => 'payrequest.success',
	'metaData' => array(
		'referenceNumber'     => $ref,
		'timedAccountNumber'  => '99999999',
	),
);

$found = WC_Onekhusa_Order_Resolver::find_order($payload);
$ok    = ( $found && (int) $found->get_id() === (int) $order->get_id() );

$order->delete( true );

if (! $ok) {
	fwrite(STDERR, "Order resolver did not find the order for reference {$ref}.\n");
	exit(1);
}

fwrite(STDOUT, "OK: WC_Onekhusa_Order_Resolver::find_order matched order meta for reference {$ref}.\n");
exit(0);
