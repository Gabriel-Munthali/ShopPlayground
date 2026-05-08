<?php
/**
 * Unit tests for WC_Onekhusa_Reference.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers WC_Onekhusa_Reference
 */
final class ReferenceTest extends TestCase {

	/**
	 * @return object Order-like object with get_id and get_order_key.
	 */
	private function make_order_mock($id, $order_key = 'key') {
		return new class($id, $order_key) {
			private $id;
			private $order_key;

			public function __construct($id, $order_key) {
				$this->id        = $id;
				$this->order_key = $order_key;
			}

			public function get_id() {
				return $this->id;
			}

			public function get_order_key() {
				return $this->order_key;
			}
		};
	}

	public function test_build_for_order_produces_alphanumeric_length_bounds(): void {
		$ref = WC_Onekhusa_Reference::build_for_order($this->make_order_mock(7));
		$this->assertMatchesRegularExpression('/^[A-Za-z0-9]{5,25}$/', $ref);
		$this->assertStringStartsWith('WC', $ref);
	}

	public function test_build_for_order_is_deterministic_for_same_order(): void {
		$order = $this->make_order_mock(42, 'stable-key');
		$a     = WC_Onekhusa_Reference::build_for_order($order);
		$b     = WC_Onekhusa_Reference::build_for_order($order);
		$this->assertSame($a, $b);
	}

	public function test_build_for_order_handles_very_long_numeric_id(): void {
		$long_id = 10 ** 21; // forces hash path in implementation
		$ref     = WC_Onekhusa_Reference::build_for_order($this->make_order_mock($long_id));
		$this->assertMatchesRegularExpression('/^[A-Za-z0-9]{5,25}$/', $ref);
		$this->assertLessThanOrEqual(25, strlen($ref));
		$this->assertGreaterThanOrEqual(5, strlen($ref));
	}
}
