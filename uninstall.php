<?php
/**
 * Remove everything the plugin stored.
 *
 * Uninstall runs once, in the context of one site. On a network install that is
 * not enough: options, transients and the daily scan are per-site, so deleting
 * the plugin from the network used to leave a full set of rows behind on every
 * subsite except the one the request happened to be on — permanently, with no
 * remaining code to clean them up.
 *
 * User meta is the exception. `wp_usermeta` is a single shared table across a
 * network, so the drafts and undo history are removed once rather than once per
 * site.
 *
 * @package Color_Changer_For_Elementor
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! function_exists( 'eccw_uninstall_site_data' ) ) {
	/**
	 * Delete the options, transients and scheduled job for the current site.
	 */
	function eccw_uninstall_site_data() {
		delete_option( 'eccw_settings' );
		delete_option( 'eccw_dequeue_settings' );
		delete_option( 'eccw_colors_mappings' );
		delete_option( 'eccw_onboarding_completed' );
		delete_option( 'eccw_wizard_dismissed' );
		delete_option( 'eccw_css_generation' );
		delete_option( 'eccw_global_chrome' );

		// Removed in 1.3.3 along with the wizard opt-in that wrote it.
		// Plugin::purge_legacy_optin_email() clears it on upgrade, but only when
		// WooCommerce and Elementor are both active, so it is deleted here as
		// well for sites that upgraded without them.
		delete_option( 'eccw_pro_optin_email' );

		global $wpdb;

		// Intentional: bulk-clean plugin transients on uninstall.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_eccw_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_eccw_' ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		// Generated stylesheets, written since 2.0.
		$uploads = wp_upload_dir();

		if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
			$dir = trailingslashit( $uploads['basedir'] ) . 'eccw';

			foreach ( (array) glob( $dir . '/colors-*.css' ) as $sheet ) {
				wp_delete_file( $sheet );
			}

			// Best effort. WP_Filesystem is not loaded during uninstall, and a
			// directory that still holds something is left alone rather than
			// forced.
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.directory_rmdir, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			@rmdir( $dir );
		}

		wp_clear_scheduled_hook( 'eccw_daily_scan' );
	}
}

if ( ! function_exists( 'eccw_uninstall_user_data' ) ) {
	/**
	 * Delete editor drafts and undo history for every user.
	 *
	 * Network-wide by nature: `wp_usermeta` is shared across a multisite
	 * install, so this runs once regardless of how many sites there are.
	 */
	function eccw_uninstall_user_data() {
		global $wpdb;

		// Intentional: bulk-clean plugin user meta on uninstall.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key = %s OR meta_key = %s",
				$wpdb->esc_like( 'eccw_history_' ) . '%',
				'eccw_draft',
				'eccw_draft_time'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}
}

if ( ! function_exists( 'eccw_uninstall' ) ) {
	/**
	 * Entry point.
	 */
	function eccw_uninstall() {
		eccw_uninstall_user_data();

		if ( ! is_multisite() ) {
			eccw_uninstall_site_data();
			return;
		}

		// Deleting per-site data across a very large network cannot finish
		// inside one request. A partial delete is worse than a clean skip: it
		// looks done, and the remainder has no code left to remove it. Skip the
		// sweep, leave the data intact and consistent, and let an operator
		// finish it deliberately.
		if ( function_exists( 'wp_is_large_network' ) && wp_is_large_network( 'sites' ) ) {
			return;
		}

		$paged = 1;

		do {
			$sites = get_sites(
				array(
					'fields'                 => 'ids',
					'number'                 => 100,
					'paged'                  => $paged,
					'update_site_cache'      => false,
					'update_site_meta_cache' => false,
				)
			);

			$found = count( $sites );

			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				eccw_uninstall_site_data();
				restore_current_blog();
			}

			++$paged;

			// Loops again only on a full page, so an exactly-full last page
			// costs one extra empty query rather than running forever.
		} while ( 100 === $found );
	}
}

eccw_uninstall();
