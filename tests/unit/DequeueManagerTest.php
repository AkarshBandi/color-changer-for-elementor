<?php

use PHPUnit\Framework\TestCase;
use WooElementorColors\Dequeue_Manager;

/**
 * Tests for Dequeue_Manager gating logic.
 */
class DequeueManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WP_Shims::reset();
	}

	public function test_should_dequeue_false_when_no_mappings() {
		WP_Shims::$options['wooce_colors_mappings'] = array(
			'version' => 1,
			'widgets' => array(),
		);

		$this->assertFalse( Dequeue_Manager::should_dequeue() );
	}

	public function test_should_dequeue_false_when_option_missing() {
		$this->assertFalse( Dequeue_Manager::should_dequeue() );
	}

	public function test_should_dequeue_true_when_mappings_exist() {
		WP_Shims::$options['wooce_colors_mappings'] = array(
			'version' => 1,
			'widgets' => array(
				'wc-add-to-cart' => array(
					'label' => 'Add',
					'slots' => array( 'button_normal' => array( 'color' => 'primary' ) ),
				),
			),
		);

		$this->assertTrue( Dequeue_Manager::should_dequeue() );
	}
}
