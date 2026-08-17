<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Daily background rescan.
 *
 * Discovery runs automatically on a daily schedule so newly added WooCommerce
 * widgets are picked up without the owner ever visiting the settings screen.
 */
class Cron_Handler {

	/**
	 * Hook the daily scan.
	 */
	public function init() {
		add_action( 'eccw_daily_scan', array( $this, 'rescan' ) );
	}

	/**
	 * Rescan the site and merge any newly discovered widgets.
	 */
	public function rescan() {
		// Re-answered here as well as on template save: a template edited
		// while the plugin was inactive fires no save hook it can hear.
		CSS_Generator::flush_global_chrome();

		$discovery = new Discovery_Engine();
		$report    = $discovery->scan();

		// Recorded whether or not anything changed, so "we found elements we
		// do not style" survives a scan that alters no mapping.
		Mapping_Service::record_coverage( $report );

		$widget_types = $report['cards'];

		$saved = get_option( 'eccw_colors_mappings', array() );

		$needs_update = Mapping_Service::normalize_all( $saved );

		$new_widgets = Mapping_Service::merge_new_widgets( $saved, $widget_types );

		if ( ! empty( $new_widgets ) ) {
			$needs_update = true;
		}

		if ( Mapping_Service::ensure_defaults( $saved ) ) {
			$needs_update = true;
		}

		if ( $needs_update ) {
			update_option( 'eccw_colors_mappings', $saved );
			Cache_Manager::clear_css();
		}
	}
}
