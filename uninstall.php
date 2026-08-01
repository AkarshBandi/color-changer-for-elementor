<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wooce_dequeue_settings' );
delete_option( 'wooce_colors_mappings' );
delete_option( 'wooce_onboarding_completed' );
delete_option( 'wooce_pro_optin_email' );

global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wooce_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wooce_' ) . '%'
	)
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( 'wooce_history_' ) . '%'
	)
);

wp_clear_scheduled_hook( 'wooce_daily_scan' );
