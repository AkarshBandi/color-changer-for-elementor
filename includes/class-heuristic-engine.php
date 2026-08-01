<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Heuristic_Engine {

	private static $defaults = array(
		'button_normal'      => 'primary',
		'button_hover'       => 'secondary',
		'button_focus'       => 'primary',
		'button_disabled'    => 'text',
		'price_regular'      => 'primary',
		'price_sale'         => 'accent',
		'badge_normal'       => 'accent',
		'stars_filled'       => 'accent',
		'stars_empty'        => 'text',
		'tab_normal'         => 'text',
		'tab_active'         => 'primary',
		'table_header'       => 'primary',
		'table_cell'         => 'text',
		'proceed_button'     => 'primary',
		'proceed_hover'      => 'secondary',
		'coupon_input'       => 'text',
		'update_cart'        => 'primary',
		'update_cart_hover'  => 'secondary',
		'place_order'        => 'primary',
		'place_order_hover'  => 'secondary',
		'input_text'         => 'text',
		'input_focus'        => 'primary',
		'success_border'     => 'accent',
		'success_icon'       => 'accent',
		'info_border'        => 'primary',
		'info_icon'          => 'primary',
		'error_border'       => 'text',
		'error_icon'         => 'text',
		'qty_input'          => 'text',
		'qty_focus'          => 'primary',
		'nav_link'           => 'text',
		'nav_active'         => 'primary',
		'link_normal'        => 'text',
		'link_hover'         => 'primary',
		'loop_button'        => 'primary',
		'loop_hover'         => 'secondary',
	);

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
				$color = $this->determine_color( $slot['slot_id'] );
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

	public function determine_color( $slot_id ) {
		if ( isset( self::$defaults[ $slot_id ] ) ) {
			return self::$defaults[ $slot_id ];
		}

		if ( false !== strpos( $slot_id, 'button' ) || false !== strpos( $slot_id, 'proceed' ) || false !== strpos( $slot_id, 'place_order' ) || false !== strpos( $slot_id, 'update_cart' ) || false !== strpos( $slot_id, 'loop' ) ) {
			if ( false !== strpos( $slot_id, 'hover' ) || false !== strpos( $slot_id, 'focus' ) ) {
				return 'secondary';
			}
			return 'primary';
		}

		if ( false !== strpos( $slot_id, 'price' ) ) {
			return 'primary';
		}

		if ( false !== strpos( $slot_id, 'sale' ) ) {
			return 'accent';
		}

		if ( false !== strpos( $slot_id, 'badge' ) || false !== strpos( $slot_id, 'star' ) ) {
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
			if ( false !== strpos( $slot_id, 'focus' ) ) {
				return 'primary';
			}
			return 'text';
		}

		if ( false !== strpos( $slot_id, 'nav' ) || false !== strpos( $slot_id, 'link' ) ) {
			if ( false !== strpos( $slot_id, 'active' ) || false !== strpos( $slot_id, 'hover' ) ) {
				return 'primary';
			}
			return 'text';
		}

		if ( false !== strpos( $slot_id, 'tab' ) ) {
			if ( false !== strpos( $slot_id, 'active' ) ) {
				return 'primary';
			}
			return 'text';
		}

		if ( false !== strpos( $slot_id, 'header' ) || false !== strpos( $slot_id, 'cell' ) || false !== strpos( $slot_id, 'table' ) ) {
			return 'primary';
		}

		return 'text';
	}
}
