<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class AJAX_Handlers {

	/**
	 * Three endpoints, down from eleven.
	 *
	 * The eight that went existed to serve the Live Editor and preview mode:
	 * live_update, commit_live, undo_action, discard_draft, save_preview,
	 * generate_share, scan_progress and the wizard's batched scan. With those
	 * screens gone, every remaining action belongs to the settings page.
	 */
	public function init() {
		add_action( 'wp_ajax_eccw_dismiss_new', array( $this, 'dismiss_new' ) );
		add_action( 'wp_ajax_eccw_rescan', array( $this, 'rescan' ) );
		add_action( 'wp_ajax_eccw_reset_defaults', array( $this, 'reset_defaults' ) );
	}

	private function verify_request() {
		if ( ! check_ajax_referer( 'eccw_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'commerce-colors-for-elementor' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'commerce-colors-for-elementor' ) ) );
		}
	}

	public function dismiss_new() {
		$this->verify_request();

		// Nonce and capability are verified in verify_request() above.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['widgets'] ) || ! is_array( $_POST['widgets'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No widgets specified.', 'commerce-colors-for-elementor' ) ) );
		}

		$saved = get_option( 'eccw_colors_mappings', array() );

		$this->migrate_widget_keys( $saved );

		$dismissed = isset( $saved['dismissed_new'] ) ? $saved['dismissed_new'] : array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each key sanitized via sanitize_key() below.
		$widgets = wp_unslash( $_POST['widgets'] );
		$widgets = is_array( $widgets ) ? $widgets : array();

		foreach ( $widgets as $widget_key ) {
			$widget_key = Element_Registry::normalize_key( sanitize_key( $widget_key ) );

			if ( ! in_array( $widget_key, $dismissed, true ) ) {
				$dismissed[] = $widget_key;
			}

			if ( isset( $saved['widgets'][ $widget_key ] ) && 'new' === $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'default';
			}
		}

		$saved['dismissed_new'] = $dismissed;

		update_option( 'eccw_colors_mappings', $saved );

		wp_send_json_success( array( 'message' => __( 'Dismissed.', 'commerce-colors-for-elementor' ) ) );
	}

	public function rescan() {
		$this->verify_request();

		// Straight through the shared implementation, so the button and the
		// nightly job cannot drift apart. Bumping the version is part of that
		// sequence now, which is what makes a newly merged slot actually reach
		// the stylesheet rather than sitting in the mappings unused.
		$cron   = new Cron_Handler();
		$result = $cron->rescan();

		$report      = $result['report'];
		$new_widgets = $result['new'];

		Cache_Manager::clear_css();

		// The old message counted widgets added to the mappings, which is
		// always zero: every card is seeded up front by ensure_defaults(), and
		// anything outside the registry was discarded. So the one button people
		// press to ask "did you find my new element?" answered "Found 0 new
		// widget(s)" on a healthy store and on a broken one alike.
		//
		// It now reports what the scan actually saw.
		$styled   = count( $report['cards'] );
		$unmapped = count( $report['unmapped'] );

		$message = sprintf(
			/* translators: 1: number of store elements found, 2: number of pages and templates read. */
			_n(
				'Found %1$d kind of store element across %2$d pages and templates.',
				'Found %1$d kinds of store element across %2$d pages and templates.',
				$styled,
				'commerce-colors-for-elementor'
			),
			$styled,
			(int) $report['scanned']
		);

		if ( $unmapped > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: number of elements the plugin does not style. */
				_n(
					'%d is not styled yet — see "Not styled yet" below.',
					'%d are not styled yet — see "Not styled yet" below.',
					$unmapped,
					'commerce-colors-for-elementor'
				),
				$unmapped
			);
		}

		wp_send_json_success(
			array(
				'new_count' => count( $new_widgets ),
				'unmapped'  => $unmapped,
				'message'   => $message,
			)
		);
	}

	public function reset_defaults() {
		$this->verify_request();

		$saved = get_option( 'eccw_colors_mappings', array() );

		$widget_types = array_keys( isset( $saved['widgets'] ) ? $saved['widgets'] : array() );

		$heuristic = new Heuristic_Engine();
		$mappings  = $heuristic->apply_defaults( $widget_types );

		$saved['widgets'] = $mappings;

		Mapping_Service::bump_version( $saved );

		update_option( 'eccw_colors_mappings', $saved );

		Cache_Manager::clear_css();

		wp_send_json_success( array( 'message' => __( 'All elements reset to defaults.', 'commerce-colors-for-elementor' ) ) );
	}

	private function migrate_widget_keys( &$saved ) {
		if ( Mapping_Service::normalize_all( $saved ) ) {
			update_option( 'eccw_colors_mappings', $saved );
		}
	}
}
