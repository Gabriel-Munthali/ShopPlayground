<?php
/**
 * Unit tests for webhook payload structure handling (no database).
 *
 * @package WooCommerce_Onekhusa_RTP
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers WC_Onekhusa_Order_Resolver
 */
final class OrderResolverMetaTest extends TestCase {

	/**
	 * @param string $name Private method name.
	 * @return ReflectionMethod
	 */
	private function private_method($name) {
		$m = new ReflectionMethod(WC_Onekhusa_Order_Resolver::class, $name);
		$m->setAccessible(true);
		return $m;
	}

	public function test_extract_meta_block_prefers_metaData(): void {
		$m    = $this->private_method('extract_meta_block');
		$data = array(
			'metaData' => array(
				'referenceNumber' => 'WC00007',
			),
		);
		$out = $m->invoke(null, $data);
		$this->assertArrayHasKey('referenceNumber', $out);
		$this->assertSame('WC00007', $out['referenceNumber']);
	}

	public function test_extract_meta_block_nested_transaction(): void {
		$m    = $this->private_method('extract_meta_block');
		$data = array(
			'transaction' => array(
				'metaData' => array(
					'timedAccountNumber' => '12345678',
				),
			),
		);
		$out = $m->invoke(null, $data);
		$this->assertIsArray($out);
		$this->assertArrayHasKey('timedAccountNumber', $out);
		$this->assertSame('12345678', $out['timedAccountNumber']);
	}

	/**
	 * Indirect: metaData nested in transaction with reference on inner.
	 */
	public function test_meta_get_prefers_first_matching_key(): void {
		$mg  = $this->private_method('meta_get');
		$arr = array('ReferenceNumber' => 'R1');
		$this->assertSame('R1', $mg->invoke(null, $arr, array('referenceNumber', 'ReferenceNumber')));
	}
}
