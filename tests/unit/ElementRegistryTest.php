<?php
/**
 * Widget-type recognition.
 *
 * Regression cover for the 2.1.0 defect: recognition was a literal
 * `woocommerce-*` prefix match, and Elementor free ships no WooCommerce
 * widgets at all — so on the Essential Addons / Premium Addons stack, which is
 * how most such stores are built, nothing was recognised and nothing was
 * styled.
 *
 * @package Color_Changer_For_Elementor
 */

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\Element_Registry;

final class ElementRegistryTest extends TestCase {

	protected function setUp(): void {
		ECCW_Test_Store::reset();
	}

	/**
	 * @dataProvider woocommerce_widget_types
	 */
	public function test_recognises_woocommerce_widget_types( $widget_type ) {
		$this->assertTrue(
			Element_Registry::is_woocommerce_widget_type( $widget_type ),
			$widget_type . ' should be recognised as a WooCommerce widget'
		);
	}

	public function woocommerce_widget_types() {
		return array(
			'Elementor Pro native'      => array( 'woocommerce-product-price' ),
			'Elementor Pro menu cart'   => array( 'woocommerce-menu-cart' ),
			'Essential Addons'          => array( 'eael-woo-add-to-cart' ),
			'Premium Addons'            => array( 'premium-woo-products' ),
			'Happy Addons product grid' => array( 'product-grid-new' ),
			'Happy Addons mini cart'    => array( 'mini-cart' ),
			'wc- prefixed'              => array( 'wc-cart' ),
		);
	}

	/**
	 * @dataProvider unrelated_widget_types
	 */
	public function test_does_not_claim_unrelated_widgets( $widget_type ) {
		$this->assertFalse(
			Element_Registry::is_woocommerce_widget_type( $widget_type ),
			$widget_type . ' is not a WooCommerce widget and must not be claimed'
		);
	}

	public function unrelated_widget_types() {
		return array(
			array( 'heading' ),
			array( 'nav-menu' ),
			array( 'theme-site-logo' ),
			array( 'stepbooking' ),
			array( 'text-editor' ),
			array( '' ),
		);
	}

	/**
	 * Every widget type the registry recognises must normalise onto a real
	 * registry entry, or downstream code looks it up and finds nothing. This is
	 * what `woocommerce-menu-cart` failed: it passed recognition, normalised to
	 * itself, and resolved to no element group.
	 */
	public function test_addon_map_targets_all_exist_in_the_registry() {
		$registry = Element_Registry::get_registry();

		foreach ( Element_Registry::get_addon_map() as $widget_type => $target ) {
			$this->assertArrayHasKey(
				$target,
				$registry,
				$widget_type . ' maps to "' . $target . '", which is not a registry entry'
			);
		}
	}

	public function test_menu_cart_normalises_onto_the_cart_table() {
		$this->assertSame( 'wc-cart-table', Element_Registry::normalize_key( 'woocommerce-menu-cart' ) );
	}

	public function test_addon_map_is_filterable() {
		add_filter(
			'eccw_addon_widget_map',
			static function ( $map ) {
				$map['acme-woo-thing'] = 'wc-add-to-cart';

				return $map;
			}
		);

		$this->assertTrue( Element_Registry::is_woocommerce_widget_type( 'acme-woo-thing' ) );
		$this->assertSame( 'wc-add-to-cart', Element_Registry::normalize_key( 'acme-woo-thing' ) );
	}

	/**
	 * The registry is the selector map. A slot without selectors emits nothing,
	 * which is a silent hole rather than an error.
	 */
	public function test_every_slot_has_selectors_and_properties() {
		foreach ( Element_Registry::get_registry() as $key => $definition ) {
			$this->assertArrayHasKey( 'slots', $definition, $key . ' has no slots' );

			foreach ( $definition['slots'] as $slot ) {
				$this->assertNotEmpty( $slot['slot_id'], $key . ' has a slot with no id' );
				$this->assertNotEmpty(
					$slot['selectors'] ?? array(),
					$key . '/' . $slot['slot_id'] . ' has no selectors, so it can never paint anything'
				);
				$this->assertNotEmpty(
					$slot['properties'] ?? array(),
					$key . '/' . $slot['slot_id'] . ' has no properties'
				);
			}
		}
	}

	/**
	 * A state slot names the slot it is a state of. The admin screen relies on
	 * that link to say *what* is being hovered, and a dangling reference would
	 * silently produce an unlabelled control.
	 */
	public function test_derives_from_always_points_at_a_sibling_slot() {
		foreach ( Element_Registry::get_registry() as $key => $definition ) {
			$ids = wp_list_pluck( $definition['slots'], 'slot_id' );

			foreach ( $definition['slots'] as $slot ) {
				if ( empty( $slot['derives_from'] ) ) {
					continue;
				}

				$this->assertContains(
					$slot['derives_from'],
					$ids,
					$key . '/' . $slot['slot_id'] . ' derives from "' . $slot['derives_from'] . '", which is not a sibling slot'
				);
			}
		}
	}
}
