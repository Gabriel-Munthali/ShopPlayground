<?php
/**
 * PHPUnit bootstrap — no full WordPress load for unit tests.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

if (! defined('ABSPATH')) {
	define('ABSPATH', '/tmp/');
}

require_once dirname(__DIR__) . '/includes/class-onekhusa-reference.php';
require_once dirname(__DIR__) . '/includes/class-onekhusa-order-resolver.php';
