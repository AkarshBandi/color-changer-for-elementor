<?php
/**
 * Colour-slot labels.
 *
 * Regression cover for the 2.1.1 defect: state slots were labelled by their
 * state alone — "When reached by keyboard" — which is unambiguous only on a card
 * holding one sub-element. Checkout holds two, a place-order button and the
 * input fields, so that label sat under a card titled Checkout, read as the
 * button, and actually coloured the input border. Tabbing to the button showed
 * nothing, and the control looked broken.
 *
 * @package Color_Changer_For_Elementor
 */

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\Admin_Interface;

final class StateLabelTest extends TestCase {

	protected function setUp(): void {
		ECCW_Test_Store::reset();
	}

	public function test_names_the_element_a_state_belongs_to() {
		$label = Admin_Interface::state_label( 'place_order_hover', 'Place Order Hover', 'Place Order Button' );

		$this->assertStringContainsString( 'Place Order Button', $label );
		$this->assertStringContainsString( 'mouse', $label );
	}

	/**
	 * The two labels that used to be indistinguishable on the Checkout card.
	 */
	public function test_two_states_on_one_card_are_distinguishable() {
		$button = Admin_Interface::state_label( 'place_order_hover', 'Place Order Hover', 'Place Order Button' );
		$input  = Admin_Interface::state_label( 'input_focus', 'Input Focus', 'Input Fields' );

		$this->assertNotSame( $button, $input );
		$this->assertStringContainsString( 'Place Order Button', $button );
		$this->assertStringContainsString( 'Input Fields', $input );
	}

	/**
	 * @dataProvider states
	 */
	public function test_recognises_every_state_suffix( $slot_id, $needle ) {
		// Case-insensitive: with a base label the phrase is lower-cased into the
		// middle of a sentence ("Widget — normally"), and standalone it is
		// sentence-cased. Both are correct; the suffix recognition is what is
		// under test here.
		$this->assertStringContainsString(
			strtolower( $needle ),
			strtolower( Admin_Interface::state_label( $slot_id, 'Registry Label', 'Widget' ) ),
			$slot_id . ' was not recognised as a state'
		);
	}

	public function states() {
		return array(
			array( 'button_hover', 'mouse' ),
			array( 'button_focus', 'keyboard' ),
			array( 'button_disabled', 'unavailable' ),
			array( 'tab_active', 'current' ),
			array( 'button_normal', 'normally' ),
		);
	}

	/**
	 * With no base label known, the standalone phrasing must still be a
	 * sentence-cased label rather than a fragment.
	 */
	public function test_falls_back_to_standalone_phrasing() {
		$label = Admin_Interface::state_label( 'button_hover', 'Button Hover', '' );

		$this->assertSame( 'When the mouse is over it', $label );
	}

	/**
	 * A slot that is not a state keeps the registry's own label. "Filled Stars"
	 * and "Column Headings" read better than anything derivable from the id.
	 */
	public function test_non_state_slots_keep_the_registry_label() {
		$this->assertSame( 'Filled Stars', Admin_Interface::state_label( 'stars_filled', 'Filled Stars', '' ) );
		$this->assertSame( 'Column Headings', Admin_Interface::state_label( 'table_header', 'Column Headings', '' ) );
	}

	public function test_falls_back_to_the_slot_id_when_nothing_else_is_known() {
		$this->assertSame( 'mystery_slot', Admin_Interface::state_label( 'mystery_slot', '', '' ) );
	}

	/**
	 * Every state slot in the registry must produce a label that names its
	 * element, since every state slot has a `derives_from`. This is the assertion
	 * that would have caught the original defect across all twelve groups rather
	 * than just the one someone happened to look at.
	 */
	public function test_every_state_slot_in_the_registry_gets_a_qualified_label() {
		foreach ( \ElementorColorChanger\Element_Registry::get_registry() as $key => $definition ) {
			$labels = array();

			foreach ( $definition['slots'] as $slot ) {
				$labels[ $slot['slot_id'] ] = $slot['label'];
			}

			foreach ( $definition['slots'] as $slot ) {
				if ( empty( $slot['derives_from'] ) ) {
					continue;
				}

				$base  = $labels[ $slot['derives_from'] ] ?? '';
				$label = Admin_Interface::state_label( $slot['slot_id'], $slot['label'], $base );

				$this->assertStringContainsString(
					$base,
					$label,
					$key . '/' . $slot['slot_id'] . ' does not say which element it belongs to'
				);
			}
		}
	}
}
