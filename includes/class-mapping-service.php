<?php

namespace ElementorColorChanger;

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
	 * Advance the mappings version in-place.
	 *
	 * The version is part of the generated CSS cache key, so every path that
	 * changes what the stylesheet should contain has to move it. Owning the
	 * counter in one place means the next path added does not have to know
	 * that history.
	 *
	 * @param array $saved Mappings option, by reference.
	 * @return int The new version.
	 */
	/**
	 * Option holding what the last scan found.
	 */
	const COVERAGE_OPTION = 'eccw_coverage';

	/**
	 * Record what a scan found, whether or not it changed any mapping.
	 *
	 * This method did not exist either, and both rescan paths called it one
	 * line after the missing scan(). Recorded separately from the mappings so
	 * that "we found elements we do not style" survives a scan that alters
	 * nothing -- the count is a fact about the site, not about the saved
	 * colours, and tying it to a mapping change is how it would go stale.
	 *
	 * @param array $report Report from Discovery_Engine::scan().
	 */
	public static function record_coverage( $report ) {
		if ( ! is_array( $report ) ) {
			return;
		}

		update_option(
			self::COVERAGE_OPTION,
			array(
				'styled'   => isset( $report['cards'] ) ? count( (array) $report['cards'] ) : 0,
				'unmapped' => isset( $report['unmapped'] ) ? array_values( (array) $report['unmapped'] ) : array(),
				'scanned'  => isset( $report['scanned'] ) ? (int) $report['scanned'] : 0,
				'at'       => current_time( 'mysql' ),
			),
			false
		);
	}

	/**
	 * What the last scan found.
	 *
	 * @return array
	 */
	public static function coverage() {
		$saved = get_option( self::COVERAGE_OPTION, array() );

		return is_array( $saved ) ? $saved : array();
	}

	public static function bump_version( &$saved ) {
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$current = isset( $saved['version'] ) ? (int) $saved['version'] : 0;

		$saved['version'] = max( 0, $current ) + 1;

		return $saved['version'];
	}

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
					$needs_update                 = true;
				}
			}
		}

		return $needs_update;
	}

	/**
	 * Ensure every registry widget exists in mappings with its default slots.
	 *
	 * Seeds missing widgets with heuristic defaults (status 'default') and
	 * backfills any missing slot within existing widgets. Guarantees the
	 * mappings option is never empty so the frontend always receives the
	 * replacement dynamic CSS.
	 *
	 * @param array $saved Mappings option, by reference.
	 * @return bool Whether the option needs saving.
	 */
	public static function ensure_defaults( &$saved ) {
		if ( ! isset( $saved['widgets'] ) || ! is_array( $saved['widgets'] ) ) {
			$saved['widgets'] = array();
		}

		$registry     = Element_Registry::get_registry();
		$heuristic    = new Heuristic_Engine();
		$needs_update = empty( $saved['widgets'] );

		foreach ( $registry as $widget_key => $definition ) {
			if ( ! isset( $saved['widgets'][ $widget_key ] ) ) {
				$seeded = $heuristic->apply_defaults( array( $widget_key ) );

				if ( isset( $seeded[ $widget_key ] ) ) {
					$seeded[ $widget_key ]['status'] = 'default';
					$saved['widgets'][ $widget_key ] = $seeded[ $widget_key ];
					$needs_update                    = true;
				}

				continue;
			}

			$slots = isset( $saved['widgets'][ $widget_key ]['slots'] ) && is_array( $saved['widgets'][ $widget_key ]['slots'] )
				? $saved['widgets'][ $widget_key ]['slots']
				: array();

			foreach ( $definition['slots'] as $slot ) {
				$slot_id = $slot['slot_id'];

				if ( ! isset( $slots[ $slot_id ] ) || ! is_array( $slots[ $slot_id ] ) || ! isset( $slots[ $slot_id ]['color'] ) ) {
					$slots[ $slot_id ] = array( 'color' => $heuristic->determine_color( $slot_id ) );
					$needs_update      = true;
				}
			}

			$saved['widgets'][ $widget_key ]['slots'] = $slots;
		}

		if ( $needs_update && ! isset( $saved['last_scan'] ) ) {
			$saved['last_scan'] = current_time( 'mysql' );
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
				$config['status']                 = 'new';
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

				$color = strtolower( (string) $slot_config['color'] );

				// Accept either a kit color token or a raw hex value. Custom hex
				// is free: a colour picker that cannot pick a colour reads as
				// broken, not as limited.
				$hex = ltrim( $color, '#' );

				if ( ! in_array( $color, $valid_ids, true ) ) {
					if ( ! preg_match( '/^[0-9a-f]{6}$/', $hex ) ) {
						continue;
					}

					$color = '#' . $hex;
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

		/**
		 * Filters mappings after sanitization, before they are stored.
		 *
		 * Anything added here must already be safe to persist and render.
		 *
		 * @param array $clean Sanitized mappings.
		 * @param array $raw   Unfiltered input.
		 */
		return (array) apply_filters( 'eccw_sanitized_mappings', $clean, $raw );
	}
}
