<?php

use PHPUnit\Framework\TestCase;
use WooElementorColors\Mapping_Service;
use WooElementorColors\Element_Registry;

/**
 * Tests for Mapping_Service.
 */
class MappingServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WP_Shims::reset();
	}

	public function test_normalize_all_migrates_widget_keys() {
		$saved = array(
			'widgets' => array(
				'woocommerce-product-price' => array(
					'label' => 'Price',
					'slots' => array( 'price_regular' => array( 'color' => 'primary' ) ),
				),
				'eael-woo-cart'             => array(
					'label' => 'Cart',
					'slots' => array( 'c' => array( 'color' => 'text' ) ),
				),
				'wc-add-to-cart'            => array(
					'label' => 'Add',
					'slots' => array(),
				),
			),
		);

		$changed = Mapping_Service::normalize_all( $saved );

		$this->assertTrue( $changed );
		$this->assertArrayHasKey( 'wc-product-price', $saved['widgets'] );
		$this->assertArrayNotHasKey( 'woocommerce-product-price', $saved['widgets'] );
		$this->assertArrayHasKey( 'wc-cart-table', $saved['widgets'] );
		$this->assertArrayNotHasKey( 'eael-woo-cart', $saved['widgets'] );
		$this->assertArrayHasKey( 'wc-add-to-cart', $saved['widgets'] );
	}

	public function test_normalize_all_migrates_dismissed_new() {
		$saved = array(
			'widgets'       => array(),
			'dismissed_new' => array( 'woocommerce-product-tabs', 'wc-star-rating' ),
		);

		$changed = Mapping_Service::normalize_all( $saved );

		$this->assertTrue( $changed );
		$this->assertSame( array( 'wc-product-tabs', 'wc-star-rating' ), $saved['dismissed_new'] );
	}

	public function test_normalize_all_returns_false_when_unchanged() {
		$saved = array(
			'widgets' => array(
				'wc-add-to-cart' => array(
					'label' => 'Add',
					'slots' => array(),
				),
			),
		);

		$this->assertFalse( Mapping_Service::normalize_all( $saved ) );
	}

	public function test_merge_new_widgets_adds_with_status_new() {
		$saved = array(
			'widgets' => array(
				'wc-add-to-cart' => array(
					'label'  => 'Add',
					'status' => 'configured',
					'slots'  => array(),
				),
			),
		);

		$new = Mapping_Service::merge_new_widgets( $saved, array( 'wc-add-to-cart', 'wc-product-price', 'wc-notices' ) );

		$this->assertContains( 'wc-product-price', $new );
		$this->assertContains( 'wc-notices', $new );
		$this->assertArrayHasKey( 'wc-product-price', $saved['widgets'] );
		$this->assertSame( 'new', $saved['widgets']['wc-product-price']['status'] );
		$this->assertArrayHasKey( 'last_scan', $saved );
		$this->assertSame( 'configured', $saved['widgets']['wc-add-to-cart']['status'] );
	}

	public function test_merge_new_widgets_returns_empty_when_nothing_new() {
		$saved = array(
			'widgets' => array(
				'wc-add-to-cart' => array(
					'label'  => 'Add',
					'status' => 'configured',
					'slots'  => array(),
				),
			),
		);

		$new = Mapping_Service::merge_new_widgets( $saved, array( 'wc-add-to-cart' ) );

		$this->assertEmpty( $new );
		$this->assertArrayNotHasKey( 'last_scan', $saved );
	}

	public function test_walk_elements_collects_nested_widgets() {
		$data = array(
			array(
				'widgetType' => 'heading',
				'elements'   => array(),
			),
			array(
				'widgetType' => 'eael-woo-cart',
				'elements'   => array(),
			),
			array(
				'widgetType' => 'section',
				'elements'   => array(
					array(
						'widgetType' => 'premium-woo-cta',
						'elements'   => array(),
					),
					array(
						'widgetType' => 'text-editor',
						'elements'   => array(),
					),
				),
			),
		);

		$keys = array();
		Mapping_Service::walk_elements( $data, $keys );

		$this->assertSame( array( 'wc-cart-table', 'wc-add-to-cart' ), $keys );
	}

	public function test_sanitize_mappings_keeps_valid_entries() {
		$raw = array(
			'wc-add-to-cart' => array(
				'label' => '  Add to Cart  ',
				'slots' => array( 'button_normal' => array( 'color' => 'primary' ) ),
			),
		);

		$clean = Mapping_Service::sanitize_mappings( $raw );

		$this->assertArrayHasKey( 'wc-add-to-cart', $clean );
		$this->assertSame( 'Add to Cart', $clean['wc-add-to-cart']['label'] );
		$this->assertSame( 'primary', $clean['wc-add-to-cart']['slots']['button_normal']['color'] );
	}

	public function test_sanitize_mappings_drops_invalid_colors() {
		$raw = array(
			'wc-product-price' => array(
				'label' => 'Price',
				'slots' => array( 'price_regular' => array( 'color' => 'not-a-color' ) ),
			),
		);

		$this->assertEmpty( Mapping_Service::sanitize_mappings( $raw ) );
	}

	public function test_sanitize_mappings_drops_non_arrays() {
		$raw = array(
			'wc-notices' => 'not-array',
		);

		$this->assertEmpty( Mapping_Service::sanitize_mappings( $raw ) );
	}

	public function test_sanitize_mappings_sanitizes_keys() {
		$raw = array(
			'Bad Widget!' => array(
				'label' => 'Bad',
				'slots' => array( 'slot one' => array( 'color' => 'accent' ) ),
			),
		);

		$clean = Mapping_Service::sanitize_mappings( $raw );
		$this->assertArrayHasKey( 'badwidget', $clean );
		$this->assertArrayHasKey( 'slotone', $clean['badwidget']['slots'] );
	}
}
