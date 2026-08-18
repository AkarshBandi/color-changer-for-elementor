<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * The defensible default colour for any slot, so activation needs no input.
 *
 * Two kinds of token can answer that question, and the difference is the whole
 * point of this class:
 *
 * - A **brand token** — primary, secondary, text, accent — is one of the four
 *   colours the owner named in the kit's palette. It says what the colour *is*
 *   in the brand, not what it is *for*.
 * - A **semantic token** — button_normal, link_hover, body — is read from the
 *   kit's own Button, Link and Typography tabs. It says what Elementor already
 *   paints that kind of element, everywhere else on the site.
 *
 * Buttons, links and body text exist on both sides of the store boundary: the
 * owner has already chosen how they look in Elementor, and a shopper moving
 * from the home page to the shop sees both. For those, following the semantic
 * token is the only answer that makes the shop match the site without anyone
 * touching this screen — which is the question the product exists to answer.
 *
 * Prices, sale badges, ratings and notices have no counterpart in Elementor's
 * settings. Nothing there says "this is the colour of a sale badge", so they
 * keep a brand token.
 *
 * Semantic tokens only exist when the kit actually sets them; a kit with no
 * Button tab yields no `button_normal`. Every semantic default therefore
 * carries a brand fallback, and `determine_color()` checks the live palette
 * before returning one. Without that check an absent token would reach
 * `CSS_Generator::resolve_color()`, which answers `#000000` for anything it
 * does not recognise — a store painted black by a kit that simply left a field
 * empty.
 */
class Heuristic_Engine {

	/**
	 * The four palette tokens every kit defines, in the order a chooser shows
	 * them.
	 */
	const BRAND_TOKENS = array( 'primary', 'secondary', 'text', 'accent' );

	/**
	 * slot_id => array( semantic token, brand fallback ).
	 *
	 * The fallback is what the slot resolved to before semantic defaults
	 * existed, so a kit that sets no Button or Link colours produces exactly
	 * the mapping it always did.
	 *
	 * @var array<string, array{0:string,1:string}>
	 */
	private static $semantic_defaults = array(
		// Buttons follow the kit's Button tab.
		'button_normal'     => array( 'button_normal', 'primary' ),
		'button_focus'      => array( 'button_normal', 'primary' ),
		'button_hover'      => array( 'button_hover', 'secondary' ),
		'proceed_button'    => array( 'button_normal', 'primary' ),
		'menu_cart_toggle'  => array( 'text', 'primary' ),
		'menu_cart_count'   => array( 'accent', 'primary' ),
		'proceed_hover'     => array( 'button_hover', 'secondary' ),
		'update_cart'       => array( 'button_normal', 'primary' ),
		'update_cart_hover' => array( 'button_hover', 'secondary' ),
		'place_order'       => array( 'button_normal', 'primary' ),
		'place_order_hover' => array( 'button_hover', 'secondary' ),
		'loop_button'       => array( 'button_normal', 'primary' ),
		'loop_hover'        => array( 'button_hover', 'secondary' ),

		// Links follow the kit's Link tab.
		'link_normal'       => array( 'link_normal', 'text' ),
		'link_hover'        => array( 'link_hover', 'primary' ),
		'nav_link'          => array( 'link_normal', 'text' ),
		'tab_normal'        => array( 'body', 'text' ),

		// Plain reading text follows the kit's Body typography colour.
		'table_cell'        => array( 'body', 'text' ),
		'input_text'        => array( 'body', 'text' ),
		'qty_input'         => array( 'body', 'text' ),
		'coupon_input'      => array( 'body', 'text' ),
		'body_text'         => array( 'body', 'text' ),
	);

	/**
	 * slot_id => brand token, for everything with no counterpart in the kit's
	 * own settings.
	 *
	 * @var array<string, string>
	 */
	private static $defaults = array(
		// "The current one" has to be *distinguishable*, which is a stronger
		// requirement than matching the site. Elementor's link-hover colour is
		// free to equal its body colour — on the reference kit both are
		// #747880 — and pointing an active tab there made the selected tab
		// identical to the unselected ones. A brand token cannot collide with
		// the reading colour by accident.
		'tab_active'      => 'primary',
		'nav_active'      => 'primary',

		'button_disabled' => 'text',
		'price_regular'   => 'primary',
		'price_sale'      => 'accent',
		'badge_normal'    => 'accent',
		'stars_filled'    => 'accent',
		'stars_empty'     => 'text',
		'table_header'    => 'primary',
		'input_focus'     => 'primary',
		'success_border'  => 'accent',
		'success_icon'    => 'accent',
		'info_border'     => 'primary',
		'info_icon'       => 'primary',
		'error_border'    => 'text',
		'error_icon'      => 'text',
		'qty_focus'       => 'primary',
	);

	/**
	 * Seed mappings for a set of widget types.
	 *
	 * @param array $widget_types Widget type strings.
	 * @return array Registry key => mapping.
	 */
	public function apply_defaults( $widget_types ) {
		$mappings = array();

		foreach ( $widget_types as $widget_type ) {
			$registry_key = Element_Registry::normalize_key( $widget_type );
			$definition   = Element_Registry::lookup( $registry_key );

			if ( null === $definition ) {
				continue;
			}

			$widget_slots = array();

			foreach ( $definition['slots'] as $slot ) {
				$color                            = $this->determine_color( $slot['slot_id'] );
				$widget_slots[ $slot['slot_id'] ] = array( 'color' => $color );
			}

			$mappings[ $registry_key ] = array(
				'label'  => $definition['label'],
				'status' => 'default',
				'slots'  => $widget_slots,
			);
		}

		return $mappings;
	}

	/**
	 * The token a slot should use when nobody has chosen one.
	 *
	 * @param string $slot_id Slot identifier.
	 * @return string Colour token id.
	 */
	public function determine_color( $slot_id ) {
		if ( isset( self::$semantic_defaults[ $slot_id ] ) ) {
			list( $semantic, $fallback ) = self::$semantic_defaults[ $slot_id ];

			return $this->palette_has( $semantic ) ? $semantic : $fallback;
		}

		if ( isset( self::$defaults[ $slot_id ] ) ) {
			return self::$defaults[ $slot_id ];
		}

		return $this->guess_from_name( $slot_id );
	}

	/**
	 * The Elementor-derived token a slot naturally follows, whether or not the
	 * kit defines it.
	 *
	 * Used by the chooser to decide which non-brand option to offer, so the
	 * list stays short: a button offers the kit's button colour, never its
	 * heading colours.
	 *
	 * @param string $slot_id Slot identifier.
	 * @return string Token id, or '' when the slot has no counterpart.
	 */
	public function semantic_token( $slot_id ) {
		if ( isset( self::$semantic_defaults[ $slot_id ] ) ) {
			return self::$semantic_defaults[ $slot_id ][0];
		}

		return '';
	}

	/**
	 * The colours a chooser offers for one slot.
	 *
	 * The specification asks for the four brand tokens plus Custom, so that a
	 * store owner picks between colours they named rather than between
	 * twenty-three internal ids. The slot's own semantic token leads the list
	 * where the kit defines one — without it the colour Elementor already
	 * paints that element could not be chosen at all, which is the one choice
	 * most owners want.
	 *
	 * Tokens absent from the palette are dropped rather than offered and then
	 * resolved to black.
	 *
	 * @param string $slot_id Slot identifier.
	 * @return string[] Ordered token ids.
	 */
	public function choices_for( $slot_id ) {
		$choices  = array();
		$semantic = $this->semantic_token( $slot_id );

		if ( '' !== $semantic && $this->palette_has( $semantic ) ) {
			$choices[] = $semantic;
		}

		foreach ( self::BRAND_TOKENS as $brand ) {
			if ( $this->palette_has( $brand ) ) {
				$choices[] = $brand;
			}
		}

		return array_values( array_unique( $choices ) );
	}

	/**
	 * Whether the active kit actually defines a token.
	 *
	 * @param string $token Token id.
	 * @return bool
	 */
	private function palette_has( $token ) {
		$palette = CSS_Generator::get_kit_colors();

		return isset( $palette[ $token ] ) && '' !== $palette[ $token ];
	}

	/**
	 * Last resort for slot ids this class has never seen — an add-on may
	 * register its own. Reads the name for the same families the tables above
	 * enumerate, and prefers the semantic token when the kit has one.
	 *
	 * @param string $slot_id Slot identifier.
	 * @return string Colour token id.
	 */
	private function guess_from_name( $slot_id ) {
		$is_state = false !== strpos( $slot_id, 'hover' ) || false !== strpos( $slot_id, 'focus' );

		$button = false !== strpos( $slot_id, 'button' )
			|| false !== strpos( $slot_id, 'proceed' )
			|| false !== strpos( $slot_id, 'place_order' )
			|| false !== strpos( $slot_id, 'update_cart' )
			|| false !== strpos( $slot_id, 'loop' );

		if ( $button ) {
			if ( $is_state ) {
				return $this->palette_has( 'button_hover' ) ? 'button_hover' : 'secondary';
			}

			return $this->palette_has( 'button_normal' ) ? 'button_normal' : 'primary';
		}

		if ( false !== strpos( $slot_id, 'price' ) ) {
			return 'primary';
		}

		if ( false !== strpos( $slot_id, 'sale' ) || false !== strpos( $slot_id, 'badge' ) || false !== strpos( $slot_id, 'star' ) ) {
			return 'accent';
		}

		if ( false !== strpos( $slot_id, 'success' ) ) {
			return 'accent';
		}

		if ( false !== strpos( $slot_id, 'info' ) ) {
			return 'primary';
		}

		if ( false !== strpos( $slot_id, 'error' ) ) {
			return 'text';
		}

		if ( false !== strpos( $slot_id, 'input' ) || false !== strpos( $slot_id, 'qty' ) || false !== strpos( $slot_id, 'coupon' ) ) {
			if ( $is_state ) {
				return 'primary';
			}

			return $this->palette_has( 'body' ) ? 'body' : 'text';
		}

		// "Active" means the current one, and it has to stay distinguishable
		// from its resting state. A kit is free to set its link-hover colour
		// equal to its body colour, so an active token taken from the kit can
		// silently collide; a brand token cannot.
		$is_current = false !== strpos( $slot_id, 'active' );

		if ( false !== strpos( $slot_id, 'nav' ) || false !== strpos( $slot_id, 'link' ) ) {
			if ( $is_current ) {
				return 'primary';
			}

			if ( $is_state ) {
				return $this->palette_has( 'link_hover' ) ? 'link_hover' : 'primary';
			}

			return $this->palette_has( 'link_normal' ) ? 'link_normal' : 'text';
		}

		if ( false !== strpos( $slot_id, 'tab' ) ) {
			if ( $is_current ) {
				return 'primary';
			}

			return $this->palette_has( 'body' ) ? 'body' : 'text';
		}

		if ( false !== strpos( $slot_id, 'header' ) || false !== strpos( $slot_id, 'cell' ) || false !== strpos( $slot_id, 'table' ) ) {
			return 'primary';
		}

		return 'text';
	}
}
