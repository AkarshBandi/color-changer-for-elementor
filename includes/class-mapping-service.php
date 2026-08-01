<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

/**
 * Shared mapping helpers.
 *
 * Single source of truth for the widget-key migration loop, the scan/merge
 * pattern, Elementor element traversal, and mapping sanitization. Previously
 * these were duplicated across AJAX handlers, preview, admin and cron.
 */
class Mapping_Service {

	/**
	 * Normalize legacy widget keys (and dismissed keys) in-place.
	 *
	 * @param array $saved Mappings option, by reference.
	 * @return bool Whether any key changed and the option needs saving.
	 */
	public static function normalize_all( &$saved ) {
		$needs_update = false;

		if ( isset( $saved['widgets'] ) && is_array( $saved['widgets'] ) ) {
			foreach ( $saved['widgets'] as $key => $data ) {
				$normalized = Element_Registry::normalize_key( $key );

				if ( $normalized !== $key ) {
					if ( ! isset( $saved['widgets'][ $normalized ] ) ) {
						$saved['widgets'][ $normalized ] = $data;
					}
					unset( $saved['widgets'][ $key ] );
					$needs_update = true;
				}
			}
		}

		if ( isset( $saved['dismissed_new'] ) && is_array( $saved['dismissed_new'] ) ) {
			foreach ( $saved['dismissed_new'] as $i => $key ) {
				$normalized = Element_Registry::normalize_key( $key );
				if ( $normalized !== $key ) {
					$saved['dismissed_new'][ $i ] = $normalized;
					$needs_update = true;
				}
			}
		}

		return $needs_update;
	}

	/**
	 * Merge newly discovered widget types into saved mappings.
	 *
	 * @param array $saved        Mappings option, by reference.
	 * @param array $widget_types Discovered widget types.
	 * @return array Widget types that were added.
	 */
	public static function merge_new_widgets( &$saved, $widget_types ) {
		$existing_widgets = isset( $saved['widgets'] ) ? array_keys( $saved['widgets'] ) : array();

		$new_widgets = array_diff( $widget_types, $existing_widgets );

		if ( ! empty( $new_widgets ) ) {
			$heuristic = new Heuristic_Engine();
			$defaults  = $heuristic->apply_defaults( $new_widgets );

			foreach ( $defaults as $widget_type => $config ) {
				$config['status'] = 'new';
				$saved['widgets'][ $widget_type ] = $config;
			}

			$saved['last_scan'] = current_time( 'mysql' );
		}

		return $new_widgets;
	}

	/**
	 * Recursively collect WooCommerce widget keys from Elementor data.
	 *
	 * @param array $elements    Elementor element data.
	 * @param array $widget_keys Collected widget keys, by reference.
	 */
	public static function walk_elements( $elements, &$widget_keys ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['widgetType'] ) && is_string( $element['widgetType'] ) ) {
				if ( Element_Registry::is_woocommerce_widget_type( $element['widgetType'] ) ) {
					$widget_keys[] = Element_Registry::normalize_key( $element['widgetType'] );
				}
			}

			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				self::walk_elements( $element['elements'], $widget_keys );
			}
		}
	}

	/**
	 * Sanitize raw mapping input.
	 *
	 * @param array $raw Unfiltered mappings.
	 * @return array Sanitized mappings.
	 */
	public static function sanitize_mappings( $raw ) {
		$kit_colors = CSS_Generator::get_kit_colors();
		$valid_ids  = array_keys( $kit_colors );

		$clean = array();

		foreach ( $raw as $widget_key => $widget_data ) {
			$widget_key = sanitize_key( $widget_key );

			if ( ! is_array( $widget_data ) || ! isset( $widget_data['slots'] ) ) {
				continue;
			}

			$clean_slots = array();

			foreach ( $widget_data['slots'] as $slot_id => $slot_config ) {
				$slot_id = sanitize_key( $slot_id );

				if ( ! is_array( $slot_config ) || ! isset( $slot_config['color'] ) ) {
					continue;
				}

				$color = sanitize_key( $slot_config['color'] );

				if ( ! in_array( $color, $valid_ids, true ) ) {
					continue;
				}

				$clean_slots[ $slot_id ] = array( 'color' => $color );
			}

			if ( ! empty( $clean_slots ) ) {
				$clean[ $widget_key ] = array(
					'label' => isset( $widget_data['label'] ) ? sanitize_text_field( $widget_data['label'] ) : $widget_key,
					'slots' => $clean_slots,
				);
			}
		}

		return $clean;
	}
}
