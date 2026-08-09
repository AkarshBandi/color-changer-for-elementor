<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Cron_Handler {

	public function init() {
		add_action( 'eccw_daily_scan', array( $this, 'rescan' ) );
	}

	public function rescan() {
		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages();

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
