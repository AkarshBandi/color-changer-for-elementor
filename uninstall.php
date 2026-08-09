<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'eccw_dequeue_settings' );
delete_option( 'eccw_colors_mappings' );
delete_option( 'eccw_onboarding_completed' );
delete_option( 'eccw_wizard_dismissed' );
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

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( 'eccw_history_' ) . '%'
	)
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery

wp_clear_scheduled_hook( 'eccw_daily_scan' );
