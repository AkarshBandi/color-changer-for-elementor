<?php
/**
 * Elementor layout inspection.
 *
 * Regression cover for the part of the 2.1.0 scoping defect that *is* unit
 * testable. The gate itself depends on a real query and was only ever going to
 * be caught by fetching real URLs — but the layout inspection underneath it is
 * pure string work, and it was wrong in two ways:
 *
 * - it matched `"widgetType":"woocommerce-*"` literally, so add-on widgets were
 *   invisible;
 * - it read shortcode widgets with `"shortcode":"([^"]+)"`, which stops at the
 *   first escaped quote, so `[products ids=\"4,5\"]` was truncated.
 *
 * @package Color_Changer_For_Elementor
 */

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\CSS_Generator;

final class ScopeDetectionTest extends TestCase {

	/** Invoke the private inspector. */
	private function inspect( $data ) {
		$method = new ReflectionMethod( CSS_Generator::class, 'layout_has_wc_widget' );
		$method->setAccessible( true );

		return $method->invoke( null, $data );
	}

	private function layout( array $widget_types ) {
		return wp_json_encode(
			array(
				array(
					'elType'   => 'container',
					'elements' => array_map(
						static function ( $type ) {
							return array(
								'elType'     => 'widget',
								'widgetType' => $type,
							);
						},
						$widget_types
					),
				),
			)
		);
	}

	protected function setUp(): void {
		ECCW_Test_Store::reset();
	}

	public function test_finds_native_woocommerce_widgets() {
		$this->assertTrue( $this->inspect( $this->layout( array( 'heading', 'woocommerce-product-price' ) ) ) );
	}

	/**
	 * The headline case. Elementor free ships no WooCommerce widgets, so this is
	 * the common stack rather than an edge case.
	 */
	public function test_finds_addon_woocommerce_widgets() {
		$this->assertTrue( $this->inspect( $this->layout( array( 'heading', 'eael-woo-add-to-cart' ) ) ) );
		$this->assertTrue( $this->inspect( $this->layout( array( 'premium-woo-products' ) ) ) );
		$this->assertTrue( $this->inspect( $this->layout( array( 'product-grid-new' ) ) ) );
	}

	public function test_finds_a_menu_cart_in_a_header_template() {
		$this->assertTrue( $this->inspect( $this->layout( array( 'theme-site-logo', 'nav-menu', 'woocommerce-menu-cart' ) ) ) );
	}

	public function test_ignores_a_layout_with_no_store_content() {
		$this->assertFalse( $this->inspect( $this->layout( array( 'heading', 'nav-menu', 'stepbooking', 'text-editor' ) ) ) );
	}

	public function test_walks_nested_containers() {
		$nested = wp_json_encode(
			array(
				array(
					'elType'   => 'container',
					'elements' => array(
						array(
							'elType'   => 'container',
							'elements' => array(
								array(
									'elType'     => 'widget',
									'widgetType' => 'woocommerce-product-price',
								),
							),
						),
					),
				),
			)
		);

		$this->assertTrue( $this->inspect( $nested ) );
	}

	/**
	 * The escaped-quote bug, in the form where it actually changes the answer.
	 *
	 * The stored value is JSON, so quotes arrive escaped. The old pattern
	 * `"shortcode":"([^"]+)"` stopped at the first escaped quote, so it captured
	 * only `[wrapper title=` and never saw the WooCommerce shortcode that
	 * followed it. A single shortcode whose tag comes first survived truncation
	 * by luck; this one does not.
	 */
	public function test_finds_a_woocommerce_shortcode_after_an_earlier_quoted_attribute() {
		$data = wp_json_encode(
			array(
				array(
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => array( 'shortcode' => '[wrapper title="Our picks"][products limit="4"]' ),
				),
			)
		);

		$this->assertTrue( $this->inspect( $data ) );
	}

	/**
	 * The escaped-quote bug: an attribute-carrying shortcode must still be read.
	 */
	public function test_finds_shortcodes_carrying_attributes() {
		$data = wp_json_encode(
			array(
				array(
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => array( 'shortcode' => '[products ids="4,5" columns="2"]' ),
				),
			)
		);

		$this->assertTrue( $this->inspect( $data ) );
	}

	public function test_finds_bare_shortcodes() {
		$data = wp_json_encode(
			array(
				array(
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => array( 'shortcode' => '[woocommerce_cart]' ),
				),
			)
		);

		$this->assertTrue( $this->inspect( $data ) );
	}

	public function test_ignores_unrelated_shortcodes() {
		$data = wp_json_encode(
			array(
				array(
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => array( 'shortcode' => '[contact-form-7 id="9"]' ),
				),
			)
		);

		$this->assertFalse( $this->inspect( $data ) );
	}

	/**
	 * A shortcode whose tag merely begins with a WooCommerce tag is a different
	 * shortcode. `[products_carousel]` is not `[products]`.
	 */
	public function test_does_not_match_a_longer_tag_that_starts_the_same() {
		$data = wp_json_encode(
			array(
				array(
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => array( 'shortcode' => '[products_carousel id="2"]' ),
				),
			)
		);

		$this->assertFalse( $this->inspect( $data ) );
	}

	/**
	 * @dataProvider empty_layouts
	 */
	public function test_handles_absent_or_malformed_data( $data ) {
		$this->assertFalse( $this->inspect( $data ) );
	}

	public function empty_layouts() {
		return array(
			'empty string' => array( '' ),
			'null'         => array( null ),
			'false'        => array( false ),
			'empty array'  => array( array() ),
			'not json'     => array( 'this is not json at all' ),
		);
	}

	/**
	 * Elementor sometimes hands back an already-decoded array.
	 */
	public function test_accepts_an_array_as_well_as_a_json_string() {
		$this->assertTrue(
			$this->inspect(
				array(
					array(
						'elType'     => 'widget',
						'widgetType' => 'woocommerce-product-price',
					),
				)
			)
		);
	}

	/**
	 * Which template types count as site chrome is a filterable contract, and
	 * `page` and `section` must stay out of it — those are inserted into one
	 * specific layout, which the per-post check already reads.
	 */
	public function test_chrome_template_types_exclude_page_and_section() {
		$method = new ReflectionMethod( CSS_Generator::class, 'chrome_template_types' );
		$method->setAccessible( true );

		$types = $method->invoke( null );

		$this->assertContains( 'header', $types );
		$this->assertContains( 'footer', $types );
		$this->assertNotContains( 'page', $types );
		$this->assertNotContains( 'section', $types );
	}
}
