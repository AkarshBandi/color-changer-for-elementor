<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Cron_Handler {

	public function init() {
		add_action( 'wooce_daily_scan', array( $this, 'rescan' ) );
	}

	public function rescan() {
		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages();

		$saved = get_option( 'wooce_colors_mappings', array() );

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

			$needs_update = true;
		}

		if ( $needs_update ) {
			update_option( 'wooce_colors_mappings', $saved );
		}

		Cache_Manager::clear_all();
	}
}
