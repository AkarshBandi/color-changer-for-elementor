<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Activator {

	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
			deactivate_plugins( plugin_basename( ECCw_PATH . 'color-changer-for-elementor.php' ) );
			wp_die(
				esc_html__( 'Color Changer for Elementor and WooCommerce requires both WooCommerce and Elementor to be installed and active.', 'color-changer-for-elementor' )
			);
		}

		add_option( 'eccw_onboarding_completed', false );
		add_option( 'eccw_wizard_dismissed', false );

		add_option(
			'eccw_dequeue_settings',
			array(
				'dequeue_blocks' => 'yes',
			)
		);

		set_transient( 'eccw_wizard_redirect', true, 60 );

		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages();

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

		add_option( 'eccw_colors_mappings', $saved );

		if ( ! wp_next_scheduled( 'eccw_daily_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'eccw_daily_scan' );
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			$plugin_file = ECCw_PATH . 'color-changer-for-elementor.php';

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

	public static function activation_notice() {
		if ( ! get_transient( 'eccw_activation_notice' ) ) {
			return;
		}

		delete_transient( 'eccw_activation_notice' );

		$settings_url = admin_url( 'admin.php?page=eccw-settings' );
		$message      = sprintf(
			/* translators: %s: settings page URL */
			__( 'Color Changer for Elementor and WooCommerce is active. Your store elements are now styled with your Elementor brand colors. <a href="%s">Go to settings</a>.', 'color-changer-for-elementor' ),
			esc_url( $settings_url )
		);

		echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
