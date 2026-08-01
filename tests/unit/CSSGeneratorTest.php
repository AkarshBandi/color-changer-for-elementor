<?php

use PHPUnit\Framework\TestCase;
use WooElementorColors\CSS_Generator;
use WooElementorColors\Heuristic_Engine;

/**
 * Tests for CSS_Generator (logic core, no WP/Elementor dependency).
 */
class CSSGeneratorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WP_Shims::reset();

		// Reset CSS_Generator's static kit color cache so each test
		// observes the option/fallback state it configures.
		$reflection = new ReflectionClass( CSS_Generator::class );
		$colors     = $reflection->getProperty( 'kit_colors' );
		$colors->setAccessible( true );
		$colors->setValue( null );
	}

	private function mappings_for( $widget_keys ) {
		$engine = new Heuristic_Engine();
		return $engine->apply_defaults( $widget_keys );
	}

	public function test_get_kit_colors_fallback() {
		$colors = CSS_Generator::get_kit_colors();
		$this->assertArrayHasKey( 'primary', $colors );
		$this->assertArrayHasKey( 'secondary', $colors );
		$this->assertArrayHasKey( 'text', $colors );
		$this->assertArrayHasKey( 'accent', $colors );
	}

	public function test_get_kit_colors_from_option() {
		WP_Shims::$options['elementor_active_kit']           = 99;
		WP_Shims::$post_meta[99]['_elementor_page_settings'] = array(
			'system_colors' => array(
				array(
					'_id'   => 'primary',
					'color' => '#111111',
				),
				array(
					'_id'   => 'accent',
					'color' => '#222222',
				),
			),
		);

		$colors = CSS_Generator::get_kit_colors();
		$this->assertSame( '#111111', $colors['primary'] );
		$this->assertSame( '#222222', $colors['accent'] );
	}

	public function test_build_single_css_includes_important() {
		$css = CSS_Generator::build_single_css( 'wc-sale-badge', 'badge_normal', '#FF0000' );
		$this->assertStringContainsString( '#FF0000', $css );
		$this->assertStringContainsString( '!important', $css );
		$this->assertStringContainsString( '.woocommerce span.onsale', $css );
	}

	public function test_build_single_css_disabled_adds_opacity() {
		$css = CSS_Generator::build_single_css( 'wc-add-to-cart', 'button_disabled', '#AAAAAA' );
		$this->assertStringContainsString( 'opacity: 0.5 !important', $css );
	}

	public function test_build_single_css_unknown_widget_returns_empty() {
		$this->assertSame( '', CSS_Generator::build_single_css( 'nope', 'x', '#000000' ) );
	}

	public function test_get_css_for_mappings_respects_page_type() {
		// Add-to-cart is 'all' pages; check it renders on a generic page.
		$mappings = $this->mappings_for( array( 'wc-add-to-cart' ) );
		$css      = CSS_Generator::get_css_for_mappings( $mappings, 'generic' );
		$this->assertNotSame( '', $css );
		$this->assertStringContainsString( '!important', $css );
	}

	public function test_get_css_for_mappings_filters_is_product_only() {
		// price_sale has page_types all; but let's use the cart widget whose
		// slots are cart-only and confirm they do NOT leak onto a product page.
		$mappings = $this->mappings_for( array( 'wc-cart-table' ) );
		$css      = CSS_Generator::get_css_for_mappings( $mappings, 'is_product' );
		$this->assertSame( '', $css );
	}

	public function test_get_css_for_mappings_allows_all_on_product_page() {
		$mappings = $this->mappings_for( array( 'wc-product-price' ) );
		$css      = CSS_Generator::get_css_for_mappings( $mappings, 'is_product' );
		$this->assertNotSame( '', $css );
	}

	public function test_get_registry_selectors() {
		$map = CSS_Generator::get_registry_selectors();
		$this->assertArrayHasKey( 'wc-add-to-cart', $map );
		$this->assertNotEmpty( $map['wc-add-to-cart'] );
	}
}
