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

	private function seed_mappings() {
		WP_Shims::$options['wooce_colors_mappings'] = array(
			'version' => 1,
			'widgets' => array(
				'wc-add-to-cart' => array(
					'label' => 'Add',
					'slots' => array( 'button_normal' => array( 'color' => 'primary' ) ),
				),
			),
		);

		WP_Shims::$options['wooce_dequeue_settings'] = array(
			'dequeue_blocks' => 'yes',
		);

		WP_Shims::$page_flags['is_admin'] = false;
		return new Dequeue_Manager();
	}

	/**
	 * The plugin only replaces colors, never structural layout rules. The
	 * product grid lives in woocommerce-layout, responsive breakpoints in
	 * woocommerce-smallscreen, and button box-model sizing in
	 * woocommerce-general. Dequeuing these collapses the shop grid into a
	 * single column and shrinks buttons, so they must never be removed.
	 */
	public function test_structural_core_styles_are_never_dequeued() {
		$manager = $this->seed_mappings();

		$manager->dequeue_styles();

		$this->assertNotContains( 'woocommerce-general', WP_Shims::$dequeued, 'woocommerce-general carries button structure and must be kept.' );
		$this->assertNotContains( 'woocommerce-layout', WP_Shims::$dequeued, 'woocommerce-layout carries the product grid and must be kept.' );
		$this->assertNotContains( 'woocommerce-smallscreen', WP_Shims::$dequeued, 'woocommerce-smallscreen carries responsive layout and must be kept.' );
	}

	public function test_block_styles_still_dequeued_when_enabled() {
		$manager = $this->seed_mappings();

		$manager->dequeue_styles();

		$this->assertContains( 'wc-blocks-style', WP_Shims::$dequeued );
		$this->assertContains( 'wc-blocks-vendors-style', WP_Shims::$dequeued );
	}

	public function test_blocks_key_dequeued_when_disabled() {
		$manager                                     = $this->seed_mappings();
		WP_Shims::$options['wooce_dequeue_settings'] = array(
			'dequeue_blocks' => 'no',
		);

		$manager->dequeue_styles();

		$this->assertNotContains( 'wc-blocks-style', WP_Shims::$dequeued );
		$this->assertNotContains( 'wc-blocks-vendors-style', WP_Shims::$dequeued );
	}

	public function test_frontend_only_dequeue_skipped_in_admin() {
		$manager                          = $this->seed_mappings();
		WP_Shims::$page_flags['is_admin'] = true;

		$manager->dequeue_styles();

		$this->assertEmpty( WP_Shims::$dequeued );
	}
}
