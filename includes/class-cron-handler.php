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

		// One public trigger. Anything that needs a scan — the daily event, a
		// plugin update, an addon that has just registered new widgets — fires
		// `do_action( 'eccw_rescan' )` rather than reaching for the internals.
		// The two callers that existed had the scan sequence copy-pasted
		// between them, which is exactly why the same two missing methods
		// fatalled in both.
		add_action( 'eccw_rescan', array( $this, 'rescan' ) );
	}

	/**
	 * Rescan the site and merge any newly discovered widgets.
	 *
	 * The one implementation. The daily event, the `eccw_rescan` trigger and
	 * the Rescan button all arrive here, because when this sequence existed
	 * twice the two copies called the same two non-existent methods and both
	 * fatalled — the duplication was the bug, not a symptom of it.
	 *
	 * @return array{report:array,new:array}
	 */
	public function rescan() {
		// Re-answered here as well as on template save: a template edited
		// while the plugin was inactive fires no save hook it can hear.
		CSS_Generator::flush_global_chrome();

		// Re-ask Elementor which widgets it files under WooCommerce. Doing it
		// here rather than per request is the whole reason that set is cached:
		// resolving it means instantiating every registered widget.
		Element_Registry::refresh_woocommerce_widget_types();

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
			Mapping_Service::bump_version( $saved );
			update_option( 'eccw_colors_mappings', $saved );
			Cache_Manager::clear_css();
		}

		return array(
			'report' => $report,
			'new'    => $new_widgets,
		);
	}
}
