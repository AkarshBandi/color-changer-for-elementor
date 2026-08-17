<?php
/**
 * Typography selector groups.
 *
 * Regression cover for the 2.1.1 defect: the `accent` group listed seven button
 * selectors and the `button` group listed six of the same ones. Both emitted a
 * rule for the same selector, the later one won, and on a kit whose Accent is
 * Futura 700 and whose Button tab is Inter 400 every WooCommerce button
 * silently got Inter. The owner's typography was set, applied, and then
 * overwritten by the plugin itself.
 *
 * @package Color_Changer_For_Elementor
 */

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\Typography;

final class TypographyTest extends TestCase {

	protected function setUp(): void {
		ECCW_Test_Store::reset();

		ECCW_Test_Store::$options['eccw_settings']            = array( 'apply_typography' => true );
		ECCW_Test_Store::$options['elementor_active_kit']     = 6;
		ECCW_Test_Store::$meta[6]['_elementor_page_settings'] = array(
			'button_typography_font_family' => 'Inter',
			'button_typography_font_weight' => '400',
			'h1_typography_font_family'     => 'Playfair Display',
			'h1_typography_font_weight'     => '700',
			'body_typography_font_family'   => 'Inter',
			'body_typography_font_weight'   => '400',
		);

		\ElementorColorChanger\Settings::flush();
	}

	/**
	 * The bug, stated as a test: no selector may appear in two groups.
	 */
	public function test_no_selector_is_claimed_by_two_groups() {
		$seen = array();

		foreach ( Typography::groups() as $token => $selectors ) {
			foreach ( (array) $selectors as $selector ) {
				$this->assertArrayNotHasKey(
					$selector,
					$seen,
					sprintf(
						'"%s" is claimed by both "%s" and "%s"; which font wins would be decided by array order',
						$selector,
						$seen[ $selector ] ?? '?',
						$token
					)
				);

				$seen[ $selector ] = $token;
			}
		}

		$this->assertNotEmpty( $seen, 'groups() returned nothing at all' );
	}

	/**
	 * The structural guard, so a third-party filter cannot reintroduce the bug:
	 * even with a deliberate overlap, exactly one rule is emitted per selector.
	 */
	public function test_one_rule_per_selector_even_when_a_filter_overlaps() {
		add_filter(
			'eccw_typography_groups',
			static function ( $groups ) {
				$groups['h1']     = array( '.duplicated-selector' );
				$groups['button'] = array( '.duplicated-selector' );

				return $groups;
			}
		);

		$css = Typography::css();

		$this->assertSame(
			1,
			substr_count( $css, '.duplicated-selector {' ),
			'a selector claimed twice must still produce exactly one rule'
		);
	}

	/**
	 * Last group wins, explicitly, rather than by accident of source order.
	 */
	public function test_the_last_group_to_claim_a_selector_wins() {
		add_filter(
			'eccw_typography_groups',
			static function () {
				return array(
					'h1'     => array( '.contested' ),
					'button' => array( '.contested' ),
				);
			}
		);

		$css = Typography::css();

		$this->assertStringContainsString( 'Inter', $css, 'the button group should have won' );
		$this->assertStringNotContainsString( 'Playfair', $css, 'the h1 group should have lost' );
	}

	public function test_emits_nothing_when_typography_is_off() {
		ECCW_Test_Store::$options['eccw_settings'] = array( 'apply_typography' => false );
		\ElementorColorChanger\Settings::flush();

		$this->assertSame( '', Typography::css() );
	}

	/**
	 * Size and line-height are layout. Emitting either breaks the plugin's
	 * central promise, so it is asserted rather than trusted.
	 */
	public function test_never_emits_anything_but_family_and_weight() {
		$css = Typography::css();

		$this->assertNotSame( '', $css );

		// Only look inside declaration blocks. Scanning the whole stylesheet for
		// `word:` also matches pseudo-classes in the selectors themselves —
		// `.wc-block-components-button:not(.is-link)` looks like a property
		// called "wc-block-components-button" to a naive regex.
		preg_match_all( '/\{([^}]*)\}/', $css, $blocks );

		$this->assertNotEmpty( $blocks[1], 'no declaration blocks found' );

		$properties = array();

		foreach ( $blocks[1] as $block ) {
			foreach ( explode( ';', $block ) as $declaration ) {
				if ( false === strpos( $declaration, ':' ) ) {
					continue;
				}

				$properties[] = strtolower( trim( strstr( $declaration, ':', true ) ) );
			}
		}

		foreach ( array_unique( $properties ) as $property ) {
			$this->assertContains(
				$property,
				array( 'font-family', 'font-weight' ),
				'typography emitted "' . $property . '", which is not a font property'
			);
		}
	}

	public function test_font_family_is_quoted() {
		$css = Typography::css();

		$this->assertStringContainsString( '"Inter"', $css );
	}
}
