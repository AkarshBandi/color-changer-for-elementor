<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrator: dependency gate, service wiring, upgrade path, WooCommerce
 * compatibility, text domain.
 */
class Plugin {

	/**
	 * Stored-data schema version.
	 *
	 * Bumped when existing installs need work that activation alone will not do
	 * — activation does not re-run on update, so anything that has to reach a
	 * site already carrying data belongs in maybe_upgrade().
	 *
	 * 1: pre-1.5.1, implied.
	 * 2: opt-in email purged; admin-only options taken out of autoload.
	 * 3: onboarding flags removed with the wizard in 2.0.
	 * 4: page-type CSS variants dropped; one stylesheet for the whole site.
	 */
	const DB_VERSION = 4;

	const DB_VERSION_OPTION = 'eccw_db_version';

	/**
	 * Whether the plugin has been loaded this request.
	 *
	 * @var bool
	 */
	private static $loaded = false;

	/**
	 * Hook the bootstrap.
	 */
	public static function init() {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_wc_compatibility' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'check_dependencies' ) );
	}

	/*
	 * Translations are not loaded here, and that is deliberate.
	 *
	 * 1.5.1 added a `load_plugin_textdomain()` call on the reasoning that
	 * WordPress.org's automatic loading covers translations it hosts but not
	 * ones shipped in the zip. That was wrong for any WordPress this plugin
	 * supports: just-in-time loading resolves a domain through
	 * `WP_Textdomain_Registry`, which indexes the `Domain Path` declared in the
	 * plugin header — so `/languages` is found without being asked for. The
	 * header carries both `Text Domain` and `Domain Path`, which is the whole
	 * requirement.
	 *
	 * The call was therefore doing nothing except tripping the discouraged-
	 * function check in Plugin Check, and, on 6.7+, risking the "triggered too
	 * early" notice if it ever moved off `init`.
	 *
	 * If bundled `.mo` files are ever added, note that registry-based lookup of
	 * a plugin's own directory needs WordPress 6.1+; language packs from
	 * WordPress.org load from `WP_LANG_DIR` on anything from 4.6. Only the
	 * bundled case would need the floor raised, and there is no bundled case:
	 * `languages/` holds a `.pot` template and nothing else.
	 */

	/**
	 * Gate on WooCommerce and Elementor, then load.
	 */
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

	/**
	 * Missing-dependency notice.
	 */
	public static function dependencies_notice() {
		$message = __( 'Color and Font Sync for Elementor and WooCommerce requires both <strong>WooCommerce</strong> and <strong>Elementor</strong> to be installed and active.', 'color-changer-for-elementor' );
		echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Register services, let add-ons swap them, then boot.
	 */
	private static function load() {
		// Read-only URL flag with capability check; allows safe shutdown.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['eccw_disable'] ) && '1' === $_GET['eccw_disable'] && current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- matches ECCW_* convention used in the main plugin file.
			define( 'ECCW_DISABLE', true );
		}

		self::register_services();

		/**
		 * Fires after the default services are registered but before any are
		 * built. Add-ons swap implementations here via Container::replace().
		 */
		do_action( 'eccw_register_services' );

		self::boot_services();

		/**
		 * Fires once every service has been booted.
		 */
		do_action( 'eccw_loaded' );
	}

	/**
	 * Register the default service factories.
	 *
	 * Factories are lazy, so an add-on hooked to `eccw_register_services` can
	 * replace any of these before construction happens.
	 */
	private static function register_services() {
		Container::set(
			'dequeue',
			function () {
				return new Dequeue_Manager();
			}
		);

		Container::set(
			'css',
			function () {
				return new CSS_Generator();
			}
		);

		Container::set(
			'admin',
			function () {
				return new Admin_Interface();
			}
		);

		Container::set(
			'ajax',
			function () {
				return new AJAX_Handlers();
			}
		);

		Container::set(
			'cron',
			function () {
				return new Cron_Handler();
			}
		);
	}

	/**
	 * Resolve and start every registered service.
	 */
	private static function boot_services() {
		$css = Container::get( 'css' );

		if ( $css ) {
			// wp_enqueue_scripts, not wp. The stylesheet is a file now, and it
			// is enqueued site-wide because a WooCommerce element can appear on
			// any page an Elementor user chooses to put one on.
			//
			// Priority 999: the stylesheet must be the last one in the head.
			// Theme and WooCommerce stylesheets load at priority 10 and can
			// carry equal-specificity font rules that would otherwise override
			// this one after the first paint — the flash of the old font.
			add_action( 'wp_enqueue_scripts', array( $css, 'enqueue_stylesheet' ), 999 );
		}

		add_action( 'elementor/kit/after_save', array( __NAMESPACE__ . '\Cache_Manager', 'clear_css' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_plugin_update' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );

		// WooCommerce generates a product-archive rewrite rule for the shop
		// page's slug (or the hardcoded 'shop' fallback when no shop page is
		// set). When that slug is an Elementor-built page, the rule captures
		// the URL before the page template is considered, so the page's own
		// content (hero banner, Products widget) never renders. Strip the
		// hijack on every request: self-healing, no flush, no user action.
		//
		// Two seams are needed: `rewrite_rules_array` keeps the stored rules
		// clean at generation time, and `option_rewrite_rules` strips the
		// hijack when the stored rules are read for request matching (WP 6.4+
		// reads the option raw in wp_rewrite_rules() without the array filter).
		add_filter( 'rewrite_rules_array', array( __CLASS__, 'guard_shop_rewrite' ) );
		add_filter( 'option_rewrite_rules', array( __CLASS__, 'guard_shop_rewrite' ) );

		// Whether the site's header/footer/templates carry WooCommerce
		// elements is cached, so it has to be forgotten when a template is
		// edited — adding a Menu Cart to a header must start styling it, and
		// removing the last one must stop.
		add_action( 'save_post_elementor_library', array( __NAMESPACE__ . '\CSS_Generator', 'flush_global_chrome' ) );
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted_post' ), 10, 2 );

		foreach ( array( 'dequeue', 'admin', 'ajax', 'cron' ) as $id ) {
			$service = Container::get( $id );

			if ( $service && method_exists( $service, 'init' ) ) {
				$service->init();
			}
		}
	}

	/**
	 * Forget the cached chrome answer when a template is deleted.
	 *
	 * `deleted_post` fires for every post type, so the guard matters: without
	 * it every trash-empty on a large site would drop the cache and make the
	 * next front-end request pay for a rescan.
	 *
	 * @param int          $post_id Deleted post ID.
	 * @param WP_Post|null $post    Deleted post object.
	 */
	public static function on_deleted_post( $post_id, $post = null ) {
		$post_type = $post instanceof \WP_Post ? $post->post_type : get_post_type( $post_id );

		if ( 'elementor_library' === $post_type ) {
			CSS_Generator::flush_global_chrome();
		}
	}

	/**
	 * Disarm the product-archive rewrite rule when it would hijack an
	 * Elementor-built page.
	 *
	 * WooCommerce derives the archive slug from the shop page setting, or
	 * falls back to 'shop' when no shop page is set. Either way the rule
	 * `{slug}/?$ => post_type=product` captures the URL before WordPress
	 * considers the page template, so an Elementor page at that slug (hero
	 * banner, Products widget) is never rendered. Only disarmed when the
	 * slug genuinely resolves to an Elementor page; native archives on
	 * plain pages are untouched.
	 *
	 * @param array $rules Rewrite rules.
	 * @return array
	 */
	public static function guard_shop_rewrite( $rules ) {
		if ( ! is_array( $rules ) ) {
			return $rules;
		}

		$candidates = array( 'shop' );

		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_id = wc_get_page_id( 'shop' );

			if ( $shop_id > 0 ) {
				$candidates[] = get_page_uri( $shop_id );
			}
		}

		foreach ( array_unique( $candidates ) as $slug ) {
			if ( '' === $slug ) {
				continue;
			}

			$page = get_page_by_path( $slug );

			if ( ! $page || ! get_post_meta( $page->ID, '_elementor_data', true ) ) {
				continue;
			}

			unset(
				$rules[ $slug . '/?$' ],
				$rules[ $slug . '/feed/(feed|rdf|rss|rss2|atom)/?$' ],
				$rules[ $slug . '/(feed|rdf|rss|rss2|atom)/?$' ],
				$rules[ $slug . '/page/([0-9]{1,})/?$' ]
			);
		}

		return $rules;
	}

	/**
	 * Declare WooCommerce feature compatibility.
	 */
	public static function declare_wc_compatibility() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

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

	/**
	 * Deactivation: stop the daily scan and clear the CSS cache.
	 *
	 * Deactivation must not destroy user data; a site owner toggling the
	 * plugin off and on again expects their settings to survive. Stored data
	 * is removed in uninstall.php instead.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'eccw_daily_scan' );
		Cache_Manager::clear_css();
	}

	/**
	 * Delete any address collected by the pre-1.3.3 onboarding opt-in.
	 *
	 * Step 4 of the wizard used to trade an email address for a guide that was
	 * never written and never sent. Nothing ever read `eccw_pro_optin_email`,
	 * and the screen disclosed neither the storage nor the purpose. The form
	 * is gone as of 1.3.3, so the address it collected goes with it rather
	 * than sitting in the database until someone uninstalls.
	 */
	public static function purge_legacy_optin_email() {
		if ( false === get_option( 'eccw_pro_optin_email', false ) ) {
			return;
		}

		delete_option( 'eccw_pro_optin_email' );
	}

	/**
	 * Bring an existing install up to the current schema.
	 *
	 * Runs in the admin only, and does nothing at all once the stored version
	 * matches — the guard is a single autoloaded integer, so the common case
	 * costs no query.
	 */
	public static function maybe_upgrade() {
		$stored = (int) get_option( self::DB_VERSION_OPTION, 0 );

		if ( $stored >= self::DB_VERSION ) {
			return;
		}

		if ( $stored < 2 ) {
			self::purge_legacy_optin_email();
			self::demote_admin_only_options();
		}

		if ( $stored < 3 ) {
			self::purge_onboarding_options();
		}

		if ( $stored < 4 ) {
			// Six per-page-type cache entries collapse to one. The old entries
			// are keyed on a page type that no longer forms part of the key, so
			// nothing would ever read them again.
			Cache_Manager::clear_css();
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Remove the flags the onboarding wizard used to track itself.
	 */
	private static function purge_onboarding_options() {
		delete_option( 'eccw_onboarding_completed' );
		delete_option( 'eccw_wizard_dismissed' );
		delete_transient( 'eccw_wizard_redirect' );
	}

	/**
	 * Take options only the admin reads out of autoload.
	 *
	 * wp_set_option_autoload() arrived in WordPress 6.4 and this plugin still
	 * supports 5.8, hence the delete-and-re-add fallback.
	 */
	private static function demote_admin_only_options() {
		$options = array(
			'eccw_colors_mappings',
			'eccw_onboarding_completed',
			'eccw_wizard_dismissed',
		);

		foreach ( $options as $option ) {
			if ( function_exists( 'wp_set_option_autoload' ) ) {
				wp_set_option_autoload( $option, false );
				continue;
			}

			$value = get_option( $option, null );

			if ( null === $value ) {
				continue;
			}

			delete_option( $option );
			add_option( $option, $value, '', 'no' );
		}
	}

	/**
	 * Clear the CSS cache when this plugin is updated.
	 *
	 * @param object $upgrader Upgrader instance.
	 * @param array  $options  Upgrader options.
	 */
	public static function on_plugin_update( $upgrader, $options ) {
		if ( 'update' !== ( $options['action'] ?? '' ) || 'plugin' !== ( $options['type'] ?? '' ) ) {
			return;
		}

		$basename = plugin_basename( ECCW_PATH . 'color-changer-for-elementor.php' );

		if ( ! empty( $options['plugins'] ) && in_array( $basename, (array) $options['plugins'], true ) ) {
			Cache_Manager::clear_css();

			// An update can widen which widget types count as WooCommerce, so
			// the cached answer from the previous version cannot be trusted.
			CSS_Generator::flush_global_chrome();
		}
	}
}
