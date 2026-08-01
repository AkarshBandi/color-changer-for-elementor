<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Activator {

	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
			deactivate_plugins( plugin_basename( WOOEC_PATH . 'woocommerce-elementor-colors.php' ) );
			wp_die(
				esc_html__( 'WooCommerce Elementor Colors requires both WooCommerce and Elementor to be installed and active.', 'woocommerce-elementor-colors' )
			);
		}

		add_option( 'wooce_onboarding_completed', false );
		add_option( 'wooce_wizard_dismissed', false );

		add_option( 'wooce_dequeue_settings', array(
			'dequeue_core'   => 'yes',
			'dequeue_blocks' => 'yes',
		) );

		set_transient( 'wooce_wizard_redirect', true, 60 );

		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages();

		$heuristic = new Heuristic_Engine();
		$mappings  = $heuristic->apply_defaults( $widget_types );

		add_option( 'wooce_colors_mappings', array(
			'version'       => 1,
			'widgets'       => $mappings,
			'dismissed_new' => array(),
			'last_scan'     => current_time( 'mysql' ),
		) );

		if ( ! wp_next_scheduled( 'wooce_daily_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'wooce_daily_scan' );
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			$plugin_file = WOOEC_PATH . 'woocommerce-elementor-colors.php';

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

		set_transient( 'wooce_activation_notice', true, 60 );
		add_action( 'admin_notices', array( __CLASS__, 'activation_notice' ) );
	}

	public static function activation_notice() {
		if ( ! get_transient( 'wooce_activation_notice' ) ) {
			return;
		}

		delete_transient( 'wooce_activation_notice' );

		$settings_url = admin_url( 'admin.php?page=wooce-settings' );
		$message      = sprintf(
			/* translators: %s: settings page URL */
			__( 'WooCommerce Elementor Colors is active. Your store elements are now styled with your Elementor brand colors. <a href="%s">Go to settings</a>.', 'woocommerce-elementor-colors' ),
			esc_url( $settings_url )
		);

		echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
