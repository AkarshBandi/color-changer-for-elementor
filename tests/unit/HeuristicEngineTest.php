<?php

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\Heuristic_Engine;

/**
 * Tests for Heuristic_Engine.
 */
class HeuristicEngineTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WP_Shims::reset();
	}

	public function test_determine_color_known_defaults() {
		$engine = new Heuristic_Engine();
		$this->assertSame( 'primary', $engine->determine_color( 'button_normal' ) );
		$this->assertSame( 'secondary', $engine->determine_color( 'button_hover' ) );
		$this->assertSame( 'accent', $engine->determine_color( 'price_sale' ) );
		$this->assertSame( 'text', $engine->determine_color( 'stars_empty' ) );
	}

	public function test_determine_color_heuristics() {
		$engine = new Heuristic_Engine();
		$this->assertSame( 'secondary', $engine->determine_color( 'some_button_hover' ) );
		$this->assertSame( 'primary', $engine->determine_color( 'some_button' ) );
		$this->assertSame( 'primary', $engine->determine_color( 'product_price' ) );
		$this->assertSame( 'accent', $engine->determine_color( 'discount_sale' ) );
		$this->assertSame( 'accent', $engine->determine_color( 'sale_badge' ) );
		$this->assertSame( 'text', $engine->determine_color( 'unknown_slot' ) );
	}

	public function test_apply_defaults_builds_full_widget() {
		$engine   = new Heuristic_Engine();
		$mappings = $engine->apply_defaults( array( 'wc-add-to-cart' ) );

		$this->assertArrayHasKey( 'wc-add-to-cart', $mappings );
		$this->assertSame( 'default', $mappings['wc-add-to-cart']['status'] );
		$this->assertNotEmpty( $mappings['wc-add-to-cart']['slots'] );
		$this->assertArrayHasKey( 'label', $mappings['wc-add-to-cart'] );

		// Every slot color must resolve to a kit color token.
		foreach ( $mappings['wc-add-to-cart']['slots'] as $slot ) {
			$this->assertArrayHasKey( 'color', $slot );
			$this->assertContains(
				$slot['color'],
				array( 'primary', 'secondary', 'text', 'accent' ),
				"Unexpected color token {$slot['color']}"
			);
		}
	}

	public function test_apply_defaults_skips_unknown_widgets() {
		$engine   = new Heuristic_Engine();
		$mappings = $engine->apply_defaults( array( 'heading' ) );
		$this->assertEmpty( $mappings );
	}

	public function test_apply_defaults_normalizes_addon_keys() {
		$engine   = new Heuristic_Engine();
		$mappings = $engine->apply_defaults( array( 'eael-woo-cart' ) );
		$this->assertArrayHasKey( 'wc-cart-table', $mappings );
	}
}
