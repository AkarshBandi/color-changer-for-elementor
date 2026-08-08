<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Plugin {

	private static $loaded = false;

	public static function init() {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_wc_compatibility' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'check_dependencies' ) );
	}

	public static function check_dependencies() {
		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'dependencies_notice' ) );
			return;
		}

		if ( self::$loaded ) {
			return;
		}
		self::$loaded = true;

		self::load();
	}

	public static function dependencies_notice() {
		$message = __( 'WooCommerce Elementor Colors requires both <strong>WooCommerce</strong> and <strong>Elementor</strong> to be installed and active.', 'woocommerce-elementor-colors' );
		echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	private static function load() {
		// Read-only URL flag with capability check; allows safe shutdown.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wooce_disable'] ) && '1' === $_GET['wooce_disable'] && current_user_can( 'manage_options' ) ) {
			define( 'WOOEC_DISABLE', true );
		}

		load_plugin_textdomain(
			'woocommerce-elementor-colors',
			false,
			dirname( plugin_basename( WOOEC_PATH . 'woocommerce-elementor-colors.php' ) ) . '/languages'
		);

		$dequeue = new Dequeue_Manager();
		$dequeue->init();

		$css_gen = new CSS_Generator();
		add_action( 'wp', array( $css_gen, 'maybe_generate_and_inject' ) );

		add_action( 'elementor/kit/after_save', array( __NAMESPACE__ . '\\Cache_Manager', 'clear_css' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_plugin_update' ), 10, 2 );

		$admin = new Admin_Interface();
		$admin->init();

		$ajax = new AJAX_Handlers();
		$ajax->init();

		$preview = new Preview_System();
		$preview->init();

		$cron = new Cron_Handler();
		$cron->init();

		add_action( 'template_redirect', array( __CLASS__, 'handle_share_link' ), 1 );

		$wizard = new Onboarding_Wizard();
		$wizard->init();

		$editor = new Live_Editor();
		$editor->init();
	}

	public static function handle_share_link() {
		// Read-only public share link; token is validated against a transient below.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wooce_share'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = sanitize_key( $_GET['wooce_share'] );

		if ( strlen( $nonce ) < 16 ) {
			return;
		}

		$mappings = get_transient( 'wooce_share_' . $nonce );

		if ( empty( $mappings ) ) {
			return;
		}

		$page_type = Page_Context::get_current_page_type();
		$css       = CSS_Generator::get_css_for_mappings( $mappings, $page_type );

		if ( empty( $css ) ) {
			return;
		}

		add_action(
			'wp_head',
			function () use ( $css ) {
				// CSS is generated internally from sanitized mappings.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<style id="wooce-share-css">' . $css . '</style>';
			},
			999
		);
	}

	public static function declare_wc_compatibility() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

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

	public static function deactivate() {
		wp_clear_scheduled_hook( 'wooce_daily_scan' );
		delete_option( 'wooce_pro_optin_email' );
	}

	public static function on_plugin_update( $upgrader, $options ) {
		if ( 'update' !== ( $options['action'] ?? '' ) || 'plugin' !== ( $options['type'] ?? '' ) ) {
			return;
		}

		$basename = plugin_basename( WOOEC_PATH . 'woocommerce-elementor-colors.php' );

		if ( ! empty( $options['plugins'] ) && in_array( $basename, (array) $options['plugins'], true ) ) {
			Cache_Manager::clear_css();
		}
	}
}
