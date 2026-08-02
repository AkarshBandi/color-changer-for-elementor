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

	public function test_contrast_text_dark_background_gets_white() {
		$this->assertSame( '#ffffff', CSS_Generator::contrast_text( '#161515' ) );
		$this->assertSame( '#ffffff', CSS_Generator::contrast_text( '#54595F' ) );
	}

	public function test_contrast_text_light_background_gets_dark() {
		$this->assertSame( '#111111', CSS_Generator::contrast_text( '#FBF4F4' ) );
		$this->assertSame( '#111111', CSS_Generator::contrast_text( '#61CE70' ) );
	}

	public function test_contrast_text_handles_missing_hash_and_short_hex() {
		$this->assertSame( '#111111', CSS_Generator::contrast_text( '6EC1E4' ) );
		$this->assertSame( '#ffffff', CSS_Generator::contrast_text( '#000' ) );
	}

	public function test_contrast_text_falls_back_on_invalid_input() {
		$this->assertSame( '#111111', CSS_Generator::contrast_text( 'invalid' ) );
	}

	public function test_button_slot_text_gets_contrast_color() {
		$css = CSS_Generator::build_single_css( 'wc-add-to-cart', 'button_normal', '#1B9E77' );
		$this->assertStringContainsString( 'background-color: #1B9E77 !important', $css );
		$this->assertStringContainsString( 'color: #111111 !important', $css );
		$this->assertStringNotContainsString( '{color: #1B9E77 !important', $css );
		$this->assertStringNotContainsString( ';color: #1B9E77 !important', $css );
	}

	public function test_button_slot_fill_follows_text_contrast() {
		$css = CSS_Generator::build_single_css( 'wc-add-to-cart', 'button_normal', '#161515' );
		$this->assertStringContainsString( 'fill: #ffffff !important', $css );
	}

	public function test_text_only_slot_keeps_mapped_color() {
		$css = CSS_Generator::build_single_css( 'wc-product-price', 'price_regular', '#1B9E77' );
		$this->assertStringContainsString( 'color: #1B9E77 !important', $css );
		$this->assertStringNotContainsString( '#111111', $css );
	}

	public function test_contrast_applies_in_full_css_build() {
		WP_Shims::$options['elementor_active_kit']           = 99;
		WP_Shims::$post_meta[99]['_elementor_page_settings'] = array(
			'system_colors' => array(
				array(
					'_id'   => 'primary',
					'color' => '#161515',
				),
			),
		);

		$mappings = $this->mappings_for( array( 'wc-add-to-cart' ) );
		$css      = CSS_Generator::get_css_for_mappings( $mappings, 'generic' );
		$this->assertStringContainsString( 'color: #ffffff !important', $css );
	}
}
