<?php

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\Element_Registry;

/**
 * Tests for Element_Registry.
 */
class Element_RegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WP_Shims::reset();
	}

	public function test_registry_has_expected_keys() {
		$registry = Element_Registry::get_registry();
		$expected = array(
			'wc-add-to-cart',
			'wc-product-price',
			'wc-sale-badge',
			'wc-star-rating',
			'wc-product-tabs',
			'wc-cart-table',
			'wc-checkout',
			'wc-notices',
			'wc-quantity-input',
			'wc-account',
			'wc-general-links',
			'wc-loop-buttons',
		);
		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $registry, "Missing registry key $key" );
			$this->assertArrayHasKey( 'label', $registry[ $key ] );
			$this->assertArrayHasKey( 'slots', $registry[ $key ] );
			$this->assertNotEmpty( $registry[ $key ]['slots'] );
		}
	}

	public function test_registry_is_filterable() {
		WP_Shims::$filters['eccw_element_registry'] = array(
			'wc-add-to-cart' => array(
				'label' => 'Custom',
				'slots' => array(
					array(
						'slot_id'    => 'btn',
						'selectors'  => array( '.x' ),
						'properties' => array( 'color' ),
						'states'     => array( 'normal' ),
						'page_types' => array( 'all' ),
					),
				),
			),
		);

		$registry = Element_Registry::get_registry();
		$this->assertCount( 1, $registry );
		$this->assertSame( 'Custom', $registry['wc-add-to-cart']['label'] );
	}

	public function test_normalize_key_passthrough_for_known_keys() {
		$this->assertSame( 'wc-add-to-cart', Element_Registry::normalize_key( 'wc-add-to-cart' ) );
	}

	public function test_normalize_key_woocommerce_prefix() {
		$this->assertSame( 'wc-product-price', Element_Registry::normalize_key( 'woocommerce-product-price' ) );
		$this->assertSame( 'wc-add-to-cart', Element_Registry::normalize_key( 'woocommerce-add-to-cart' ) );
	}

	public function test_normalize_key_wc_round_trip() {
		// Keys that only exist under the woocommerce-* alias fall back to wc-*.
		$this->assertSame( 'wc-product-price', Element_Registry::normalize_key( 'wc-product-price' ) );
	}

	public function test_normalize_key_addon_map() {
		$this->assertSame( 'wc-add-to-cart', Element_Registry::normalize_key( 'eael-woo-add-to-cart' ) );
		$this->assertSame( 'wc-product-price', Element_Registry::normalize_key( 'eael-woo-product-price' ) );
		$this->assertSame( 'wc-star-rating', Element_Registry::normalize_key( 'eael-woo-product-rating' ) );
		$this->assertSame( 'wc-product-tabs', Element_Registry::normalize_key( 'eael-woo-product-tabs' ) );
		$this->assertSame( 'wc-cart-table', Element_Registry::normalize_key( 'eael-woo-cart' ) );
		$this->assertSame( 'wc-checkout', Element_Registry::normalize_key( 'eael-woo-checkout' ) );
		$this->assertSame( 'wc-loop-buttons', Element_Registry::normalize_key( 'eicon-woocommerce' ) );
		$this->assertSame( 'wc-loop-buttons', Element_Registry::normalize_key( 'premium-woo-products' ) );
		$this->assertSame( 'wc-add-to-cart', Element_Registry::normalize_key( 'premium-woo-cta' ) );
		$this->assertSame( 'wc-cart-table', Element_Registry::normalize_key( 'premium-mini-cart' ) );
		$this->assertSame( 'wc-general-links', Element_Registry::normalize_key( 'premium-woo-categories' ) );
	}

	public function test_normalize_key_unknown_returns_input() {
		$this->assertSame( 'heading', Element_Registry::normalize_key( 'heading' ) );
	}

	public function test_lookup_returns_definition() {
		$def = Element_Registry::lookup( 'wc-add-to-cart' );
		$this->assertNotNull( $def );
		$this->assertArrayHasKey( 'slots', $def );
	}

	public function test_lookup_addon_key() {
		$def = Element_Registry::lookup( 'eael-woo-cart' );
		$this->assertNotNull( $def );
		$this->assertArrayHasKey( 'label', $def );
	}

	public function test_lookup_unknown_returns_null() {
		$this->assertNull( Element_Registry::lookup( 'not-a-widget' ) );
	}

	public function test_is_woocommerce_widget_type() {
		$this->assertTrue( Element_Registry::is_woocommerce_widget_type( 'woocommerce-product-price' ) );
		$this->assertTrue( Element_Registry::is_woocommerce_widget_type( 'wc-add-to-cart' ) );
		$this->assertTrue( Element_Registry::is_woocommerce_widget_type( 'eael-woo-cart' ) );
		$this->assertTrue( Element_Registry::is_woocommerce_widget_type( 'eicon-woocommerce' ) );
		$this->assertTrue( Element_Registry::is_woocommerce_widget_type( 'premium-woo-cta' ) );
		$this->assertFalse( Element_Registry::is_woocommerce_widget_type( 'heading' ) );
		$this->assertFalse( Element_Registry::is_woocommerce_widget_type( 'text-editor' ) );
		$this->assertFalse( Element_Registry::is_woocommerce_widget_type( '' ) );
		$this->assertFalse( Element_Registry::is_woocommerce_widget_type( 123 ) );
	}
}
