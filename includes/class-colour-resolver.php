<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * The one place that decides what colour a slot is.
 *
 * This class exists to close a defect class rather than a defect. Five releases
 * of this plugin have fixed the same shape of bug — something writes to one
 * place and something else reads from another — and the final colour of a
 * WooCommerce element used to be decided in four:
 *
 * - `Heuristic_Engine` chose the token when nobody had chosen one.
 * - `CSS_Generator::resolve_color()` turned that token into a hex.
 * - `CSS_Generator::build_css()` could then replace it with the kit's own state
 *   colour, or with a derived shade, or with the brand primary for a focus
 *   ring.
 * - `CSS_Generator::build_rules()` could then replace the *text* half of it
 *   again for contrast.
 *
 * Four methods across two classes, each holding one quarter of the answer, is
 * why "which colour wins" kept being wrong: there was nowhere to look it up.
 * The active-tab defect lived in the gap between the third and the first — a
 * slot whose stored colour was correct, overwritten by a derivation that was
 * a no-op for its state.
 *
 * Everything that shapes a rendered colour now happens here, in order, and the
 * result carries a `trace` explaining how it got there. That trace is not
 * decoration: it is the thing that makes the next bug of this family visible
 * without a browser, and it is what a support diagnostic should print.
 */
class Colour_Resolver {

	/**
	 * Resolve one slot to the colours that will actually be emitted.
	 *
	 * @param array $slot_def    Registry definition for the slot.
	 * @param array $slot_data   Stored mapping for the slot (`color` key).
	 * @param array $base_colors Resolved colours of the widget's non-derived
	 *                           slots, keyed by slot id.
	 * @param bool  $derive      Whether interaction states are derived.
	 * @return array{base:string,text:string,trace:string[]} Final colours and
	 *               an ordered account of every decision taken.
	 */
	public static function resolve( array $slot_def, array $slot_data, array $base_colors, $derive ) {
		$trace = array();
		$token = isset( $slot_data['color'] ) ? $slot_data['color'] : '';
		$base  = CSS_Generator::resolve_color( $token );

		$trace[] = sprintf( 'token "%s" resolved to %s', (string) $token, $base );

		$derives_from = isset( $slot_def['derives_from'] ) ? $slot_def['derives_from'] : '';
		$state        = CSS_Generator::state_of_slot( $slot_def );

		// Deriving only means anything for a pseudo-class state. Two slots —
		// the active product tab and the current account menu link — carry
		// `derives_from` but paint a selector that already encodes "the current
		// one", so their state is `normal`, and `derive_state_color()` returns
		// the base colour untouched for `normal`. Deriving those made the
		// selected tab exactly the colour of an unselected one on every store
		// with deriving on, which is the default.
		$derivable = $derive
			&& '' !== $derives_from
			&& isset( $base_colors[ $derives_from ] )
			&& 'normal' !== $state;

		if ( $derive && '' !== $derives_from && ! $derivable ) {
			$trace[] = sprintf( 'not derived: state is "%s", which the selector already carries', $state );
		}

		if ( $derivable ) {
			$kit_state = CSS_Generator::kit_state_color( $token );

			if ( '' !== $kit_state ) {
				// Deriving fills a gap; it does not overrule an answer. When
				// the slot holds one of the kit's own state colours the owner
				// has already said what this state looks like everywhere else
				// on the site, and a computed shade would make the shop the
				// one place that disagrees.
				$base    = $kit_state;
				$trace[] = sprintf( 'kept the kit\'s own %s colour %s instead of deriving', $state, $base );
			} else {
				$base    = self::derived( $slot_def, $base_colors[ $derives_from ], $state, $trace );
				$trace[] = sprintf( 'derived "%s" from %s', $state, $derives_from );
			}
		}

		$text = self::text_for( $slot_def, $base, $trace );

		return array(
			'base'  => $base,
			'text'  => $text,
			'trace' => $trace,
		);
	}

	/**
	 * The colour a derived state takes.
	 *
	 * @param array  $slot_def Slot definition.
	 * @param string $from     Colour of the slot this one derives from.
	 * @param string $state    Interaction state.
	 * @param array  $trace    Trace, appended to by reference.
	 * @return string Hex.
	 */
	private static function derived( array $slot_def, $from, $state, array &$trace ) {
		$paints_background = in_array( 'background-color', $slot_def['properties'], true );

		if ( 'focus' === $state && ! $paints_background ) {
			// A focused control must be visibly distinct from its idle state
			// (WCAG 2.4.7). Border-only slots — inputs, quantity boxes — cannot
			// show a shade change, so focus is signalled with the brand primary
			// rather than the base colour, which made the focus border
			// identical to the normal border.
			$kit = CSS_Generator::get_kit_colors();

			if ( isset( $kit['primary'] ) && '' !== $kit['primary'] ) {
				$trace[] = 'border-only focus: used the brand primary so the ring is visible';

				return CSS_Generator::resolve_color( 'primary' );
			}
		}

		return CSS_Generator::derive_state_color( $from, $state );
	}

	/**
	 * The colour used for `color` and `fill`.
	 *
	 * A slot that paints its own background gets a label chosen against that
	 * background. A slot that paints text only gets its chosen colour checked
	 * against the assumed page background, and lifted only if it is unreadable.
	 *
	 * @param array  $slot_def Slot definition.
	 * @param string $base     Resolved base colour.
	 * @param array  $trace    Trace, appended to by reference.
	 * @return string Hex.
	 */
	private static function text_for( array $slot_def, $base, array &$trace ) {
		$paints_background = in_array( 'background-color', $slot_def['properties'], true );

		/**
		 * Filters whether label colour is auto-derived from the background.
		 *
		 * @param bool   $auto       Whether to derive the label colour.
		 * @param string $hex        Background colour being applied.
		 * @param array  $properties Properties the slot paints.
		 */
		$auto_contrast = (bool) apply_filters(
			'eccw_auto_contrast_text',
			Settings::get( 'auto_contrast' ),
			$base,
			$slot_def['properties']
		);

		if ( ! $auto_contrast ) {
			return $base;
		}

		if ( $paints_background ) {
			$text = CSS_Generator::contrast_text( $base );

			if ( $text !== $base ) {
				$trace[] = sprintf( 'label set to %s for contrast against %s', $text, $base );
			}

			return $text;
		}

		$text = CSS_Generator::ensure_readable( $base, CSS_Generator::page_background() );

		if ( $text !== $base ) {
			$trace[] = sprintf( 'text lifted from %s to %s to stay readable on the page', $base, $text );
		}

		return $text;
	}
}
