<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Cache_Manager {

	public static function get( $key ) {
		return get_transient( $key );
	}

	public static function set( $key, $css, $ttl = null ) {
		if ( null === $ttl ) {
			$ttl = 30 * DAY_IN_SECONDS;
		}

		set_transient( $key, $css, $ttl );
	}

	public static function clear_all() {
		global $wpdb;

		// Intentional: bulk-delete plugin transients; WP has no bulk API.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_eccw_css_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_eccw_css_' ) . '%'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_eccw_share_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_eccw_share_' ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Clear the CSS cache only.
	 *
	 * Unlike clear_all(), this leaves user drafts (live editor, preview)
	 * untouched. Used by flows that regenerate CSS without wanting to
	 * discard in-progress customization.
	 */
	public static function clear_css() {
		global $wpdb;

		// Intentional: bulk-delete CSS transients on a cache clear.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_eccw_css_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_eccw_css_' ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}
}
