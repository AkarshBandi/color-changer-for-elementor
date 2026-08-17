<?php
/**
 * Colour maths and colour sanitisation.
 *
 * Two histories are covered here.
 *
 * 1.5.0: text could be applied invisibly, because auto-contrast only ran when a
 * slot painted its own background — so around twenty text-only slots emitted
 * the chosen colour with `!important` and no relation to what sat behind it.
 * `ensure_readable()` was the fix, and its contract includes returning an
 * already-legible colour *verbatim*, uppercase and all.
 *
 * 1.5.1: colours coming from the kit and from the `eccw_kit_colors` filter
 * reached the stylesheet unvalidated. A value carrying `</style><script>`
 * escaped the block, on a stylesheet served on every store page.
 *
 * @package Color_Changer_For_Elementor
 */

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\CSS_Generator;

final class ColourTest extends TestCase {

	protected function setUp(): void {
		ECCW_Test_Store::reset();
		ECCW_Test_Store::$options['eccw_settings'] = array( 'auto_contrast' => true );
		\ElementorColorChanger\Settings::flush();
	}

	/* -------------------- contrast maths -------------------- */

	public function test_contrast_ratio_of_black_on_white_is_21() {
		$this->assertSame( 21.0, round( CSS_Generator::contrast_ratio( '#000000', '#ffffff' ), 1 ) );
	}

	public function test_contrast_ratio_of_a_colour_with_itself_is_1() {
		$this->assertSame( 1.0, round( CSS_Generator::contrast_ratio( '#22c55e', '#22c55e' ), 1 ) );
	}

	public function test_contrast_ratio_is_symmetric() {
		$a = CSS_Generator::contrast_ratio( '#1751aa', '#fafafa' );
		$b = CSS_Generator::contrast_ratio( '#fafafa', '#1751aa' );

		$this->assertSame( round( $a, 4 ), round( $b, 4 ) );
	}

	/**
	 * The label on a button must be readable against it. These are the two
	 * colours from the reference store, and the pair the preview predicts.
	 */
	public function test_contrast_text_picks_the_readable_option() {
		$this->assertSame( '#111111', CSS_Generator::contrast_text( '#22c55e' ), 'dark label on a light green button' );
		$this->assertSame( '#ffffff', CSS_Generator::contrast_text( '#1751aa' ), 'light label on a dark blue button' );
		$this->assertSame( '#ffffff', CSS_Generator::contrast_text( '#000000' ) );
		$this->assertSame( '#111111', CSS_Generator::contrast_text( '#ffffff' ) );
	}

	/**
	 * The real contract, and the one that shipped broken: of the two labels the
	 * plugin is willing to use, it must return whichever is actually more
	 * readable. A luminance threshold got this wrong for a band of mid greys,
	 * because the threshold constant assumes the dark option is pure black and
	 * this one is #111111.
	 *
	 * @dataProvider backgrounds
	 */
	public function test_contrast_text_returns_the_better_of_the_two_options( $background ) {
		$chosen = CSS_Generator::contrast_text( $background );

		$dark  = CSS_Generator::contrast_ratio( '#111111', $background );
		$light = CSS_Generator::contrast_ratio( '#ffffff', $background );

		$this->assertContains( $chosen, array( '#111111', '#ffffff' ) );

		$this->assertSame(
			round( max( $dark, $light ), 4 ),
			round( CSS_Generator::contrast_ratio( $chosen, $background ), 4 ),
			sprintf(
				'on %s it chose %s; #111111 gives %s:1 and #ffffff gives %s:1',
				$background,
				$chosen,
				round( $dark, 2 ),
				round( $light, 2 )
			)
		);
	}

	public function backgrounds() {
		return array(
			array( '#22c55e' ),
			array( '#1751aa' ),
			array( '#ff4a97' ),
			array( '#747880' ),
			array( '#fafafa' ),
			array( '#4e5055' ),
			array( '#f6ba02' ),
			array( '#767676' ),
			array( '#808080' ),
			array( '#000000' ),
			array( '#ffffff' ),
			array( '#7f7f7f' ),
		);
	}

	/**
	 * A documented limitation, asserted so that nobody "fixes" it by widening
	 * the palette of label colours without deciding to.
	 *
	 * A two-option black-or-white label cannot reach WCAG AA on every possible
	 * background: mid greys around #747880 top out near 4.4:1 whichever way you
	 * go. The alternative would be adjusting the *background*, which is the
	 * owner's brand colour and deliberately never touched. So the guarantee is
	 * "the most readable label available", not "AA on any background".
	 */
	public function test_mid_greys_cannot_reach_aa_and_that_is_understood() {
		$background = '#747880';
		$best       = max(
			CSS_Generator::contrast_ratio( '#111111', $background ),
			CSS_Generator::contrast_ratio( '#ffffff', $background )
		);

		$this->assertLessThan(
			4.5,
			$best,
			'if this now passes, a wider label palette was introduced — update the documented guarantee to match'
		);
	}

	/**
	 * Every background in the reference store's own palette must at least clear
	 * the 3:1 floor for large text, which is what buttons are.
	 */
	public function test_reference_palette_all_clears_three_to_one() {
		foreach ( array( '#111111', '#1751aa', '#747880', '#ff4a97', '#fafafa', '#ffffff', '#f6ba02', '#4e5055' ) as $background ) {
			$label = CSS_Generator::contrast_text( $background );
			$ratio = CSS_Generator::contrast_ratio( $label, $background );

			$this->assertGreaterThanOrEqual(
				3.0,
				round( $ratio, 2 ),
				$label . ' on ' . $background . ' is only ' . round( $ratio, 2 ) . ':1'
			);
		}
	}

	/* -------------------- the readability guard -------------------- */

	/**
	 * The part of the contract most easily broken by a later refactor: a colour
	 * that is already legible comes back byte-for-byte, so a store owner's
	 * chosen hex is never quietly rewritten.
	 */
	public function test_already_legible_colours_are_returned_verbatim() {
		foreach ( array( '#111111', '#1751AA', '#4E5055' ) as $colour ) {
			$this->assertSame(
				$colour,
				CSS_Generator::ensure_readable( $colour, '#ffffff' ),
				$colour . ' already clears AA on white and must not be adjusted'
			);
		}
	}

	public function test_illegible_text_is_darkened_until_it_is_readable() {
		$faint  = '#f2f2f2';
		$result = CSS_Generator::ensure_readable( $faint, '#ffffff' );

		$this->assertNotSame( strtolower( $faint ), strtolower( $result ), 'a near-white text colour on white must be adjusted' );
		$this->assertGreaterThanOrEqual(
			3.0,
			round( CSS_Generator::contrast_ratio( $result, '#ffffff' ), 2 ),
			'the adjusted colour is still not readable'
		);
	}

	/**
	 * Adjusting lightness must preserve hue, or the store's brand colour turns
	 * into a different colour rather than a darker one.
	 */
	public function test_adjustment_preserves_hue() {
		$result = CSS_Generator::ensure_readable( '#ffe9f2', '#ffffff' );

		$rgb = sscanf( $result, '#%02x%02x%02x' );

		$this->assertGreaterThan( $rgb[1], $rgb[0], 'a pink should stay red-dominant after darkening' );
		$this->assertGreaterThan( $rgb[1], $rgb[2], 'a pink should stay blue-over-green after darkening' );
	}

	/* -------------------- sanitisation -------------------- */

	/**
	 * @dataProvider accepted_colours
	 */
	public function test_accepts_every_legitimate_colour_form( $colour ) {
		$this->assertNotSame( '', CSS_Generator::sanitize_css_color( $colour ), $colour . ' is a legitimate CSS colour' );
	}

	public function accepted_colours() {
		return array(
			array( '#fff' ),
			array( '#ffffff' ),
			array( '#ffffffff' ),
			array( '#FF4A97' ),
			array( 'rgb(34, 197, 94)' ),
			array( 'rgba(23, 81, 170, 0.5)' ),
			array( 'hsl(210, 100%, 50%)' ),
			array( 'hsla(210, 100%, 50%, 0.2)' ),
		);
	}

	/**
	 * @dataProvider rejected_colours
	 */
	public function test_rejects_anything_that_is_not_a_colour( $value ) {
		$this->assertSame(
			'',
			CSS_Generator::sanitize_css_color( $value ),
			'must reject: ' . $value
		);
	}

	public function rejected_colours() {
		return array(
			'style break-out' => array( '</style><script>alert(1)</script>' ),
			'css injection'   => array( 'red; background: url(//evil.test/x)' ),
			'expression'      => array( 'expression(alert(1))' ),
			'url'             => array( 'url(//evil.test/x.png)' ),
			'var reference'   => array( 'var(--x)' ),
			'named colour'    => array( 'rebeccapurple' ),
			'empty'           => array( '' ),
			'brace'           => array( '#fff}' ),
			'comment'         => array( '#fff/*' ),
		);
	}

	/**
	 * The 1.5.1 finding: values arriving through the filter are as untrusted as
	 * values arriving from the database.
	 */
	public function test_filtered_kit_colours_are_sanitised_too() {
		add_filter(
			'eccw_kit_colors',
			static function () {
				return array( 'primary' => '</style><script>alert(1)</script>' );
			}
		);

		$resolved = CSS_Generator::resolve_color( 'primary' );

		$this->assertStringNotContainsString( '<', $resolved, 'a filtered colour reached the stylesheet unvalidated' );
		$this->assertStringNotContainsString( 'script', strtolower( $resolved ) );
	}
}
