<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Activation: seed settings and mappings, schedule the daily scan, declare
 * WooCommerce compatibility, and tell the owner the store is now styled.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
			deactivate_plugins( plugin_basename( ECCW_PATH . 'color-changer-for-elementor.php' ) );
			wp_die(
				esc_html__( 'Color and Font Sync for Elementor and WooCommerce requires both WooCommerce and Elementor to be installed and active.', 'color-changer-for-elementor' )
			);
		}

		// Autoloaded on purpose: Settings::all() runs on every front-end
		// request that generates CSS, and this is four booleans.
		//
		// Seeds Settings::defaults(), which leaves the blocks dequeue off. An
		// upgrade from an earlier version keeps whatever was chosen there;
		// see Settings::migrate_legacy().
		add_option( Settings::OPTION, Settings::defaults() );

		// No onboarding flags and no redirect transient as of 2.0. The wizard
		// was four steps to reach a result the plugin already produces on
		// activation — the colours are applied before anyone opens it.

		// Keep activation fast and predictable. A small scan is enough to seed
		// sensible mappings; the full sweep runs later on cron. An unbounded
		// scan here could time out activation on a large store.
		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages( 50 );

		$heuristic = new Heuristic_Engine();
		$mappings  = $heuristic->apply_defaults( $widget_types );

		$saved = array(
			'version'       => 1,
			'widgets'       => $mappings,
			'dismissed_new' => array(),
			'last_scan'     => current_time( 'mysql' ),
		);

		// Seed the full registry so mappings are never empty, even when the
		// initial scan finds no Elementor widgets yet.
		Mapping_Service::ensure_defaults( $saved );

		// Not autoloaded. This is the plugin's largest option — twelve widgets,
		// each with up to four slots, plus labels and status — and it is only
		// read on WooCommerce pages and the settings screen.
		add_option( 'eccw_colors_mappings', $saved, '', 'no' );

		if ( ! wp_next_scheduled( 'eccw_daily_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'eccw_daily_scan' );
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			$plugin_file = ECCW_PATH . 'color-changer-for-elementor.php';

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				$plugin_file,
				true
			);

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				$plugin_file,
				true
			);
		}

		set_transient( 'eccw_activation_notice', true, 60 );
		add_action( 'admin_notices', array( __CLASS__, 'activation_notice' ) );
	}

	/**
	 * One-shot success notice after activation.
	 */
	public static function activation_notice() {
		if ( ! get_transient( 'eccw_activation_notice' ) ) {
			return;
		}

		delete_transient( 'eccw_activation_notice' );

		$settings_url = admin_url( 'admin.php?page=eccw-settings' );
		$message      = sprintf(
			/* translators: %s: settings page URL */
			__( 'Color and Font Sync for Elementor and WooCommerce is active. Your store elements are now styled with your Elementor brand colors. <a href="%s">Go to settings</a>.', 'color-changer-for-elementor' ),
			esc_url( $settings_url )
		);

		echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
