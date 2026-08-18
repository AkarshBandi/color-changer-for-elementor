<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Admin_Interface {

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_eccw_save_settings', array( $this, 'handle_form_save' ) );

		$plugin_file = plugin_basename( ECCW_PATH . 'commerce-colors-for-elementor.php' );
		add_filter( 'plugin_action_links_' . $plugin_file, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Where the screen lives.
	 *
	 * Two doors to one room. WooCommerce is the home: this styles WooCommerce,
	 * and a shop owner adjusting their store is already in that menu. But the
	 * question people actually arrive with is "how do I change how my store
	 * looks", and the place everyone has been trained to look for that is
	 * Appearance — so it is listed there too, pointing at the same page.
	 *
	 * "Elementor Colors" was the old label. It named the source rather than
	 * the job, and it stopped being true when fonts were added.
	 */
	public function add_submenu() {
		add_submenu_page(
			'woocommerce',
			__( 'Store Design', 'commerce-colors-for-elementor' ),
			__( 'Store Design', 'commerce-colors-for-elementor' ),
			'manage_options',
			'eccw-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'themes.php',
			__( 'Store Design', 'commerce-colors-for-elementor' ),
			__( 'Store Design', 'commerce-colors-for-elementor' ),
			'manage_options',
			'eccw-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Admin screen ids this plugin owns.
	 *
	 * The same page is registered under two menus, so it has two hook
	 * suffixes. Asset loading keys off this list rather than one hard-coded
	 * string, which is how the Appearance copy would otherwise render
	 * unstyled.
	 *
	 * @return string[]
	 */
	private function screen_hooks() {
		return array(
			'woocommerce_page_eccw-settings',
			'appearance_page_eccw-settings',
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=eccw-settings' ) . '">' .
			esc_html__( 'Settings', 'commerce-colors-for-elementor' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, $this->screen_hooks(), true ) ) {
			return;
		}

		// One stylesheet and one script. The screen used to load six of each,
		// split per card, which meant every layout change touched three files
		// and the cascade order depended on an enqueue loop.
		wp_enqueue_style(
			'eccw-admin',
			ECCW_URL . 'admin/css/admin.css',
			array(),
			ECCW_VERSION
		);

		wp_enqueue_script(
			'eccw-admin',
			ECCW_URL . 'admin/js/admin.js',
			array(),
			ECCW_VERSION,
			true
		);

		$kit_colors = CSS_Generator::get_kit_colors();
		$kit_labels = self::kit_color_labels();
		$colors_js  = array();

		foreach ( $kit_colors as $id => $hex ) {
			$colors_js[] = array(
				'id'    => $id,
				'label' => isset( $kit_labels[ $id ] ) ? $kit_labels[ $id ] : ucfirst( str_replace( '_', ' ', $id ) ),
				'hex'   => $hex,
			);
		}

		$defaults  = $this->get_slot_defaults();
		$saved     = get_option( 'eccw_colors_mappings', array() );
		$mappings  = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$new_count = $this->count_new_widgets( $mappings );

		$data = array(
			'kitColors' => $colors_js,
			'defaults'  => $defaults,
			'newCount'  => $new_count,
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'eccw_admin_nonce' ),
			'siteUrl'   => home_url(),
			'features'  => Features::all(),
			'i18n'      => array(
				'scanning'      => __( 'Checking…', 'commerce-colors-for-elementor' ),
				'checkAgain'    => __( 'Check for new parts of my store', 'commerce-colors-for-elementor' ),
				'resetting'     => __( 'Resetting…', 'commerce-colors-for-elementor' ),
				'requestFailed' => __( 'That did not work. Please try again.', 'commerce-colors-for-elementor' ),
				'unsaved'       => __( 'You have unsaved changes.', 'commerce-colors-for-elementor' ),
				'nothingNew'    => __( 'Nothing new to review.', 'commerce-colors-for-elementor' ),
				'reloadPrompt'  => __( 'New parts of your store were found. Reload to see them?', 'commerce-colors-for-elementor' ),
				'customColour'  => __( 'Custom colour', 'commerce-colors-for-elementor' ),
			),
		);

		/**
		 * Filters the data handed to the settings screen script.
		 *
		 * @param array $data Settings bootstrap data.
		 */
		$data = (array) apply_filters( 'eccw_admin_data', $data );

		wp_localize_script( 'eccw-admin', 'eccwData', $data );

		/**
		 * Fires after the settings screen assets are enqueued.
		 *
		 * Add-ons enqueue their own admin assets here.
		 */
		do_action( 'eccw_admin_assets_enqueued' );
	}

	/**
	 * Panels rendered on the settings screen.
	 *
	 * Each entry is keyed by panel id and holds a label plus a render
	 * callback. The free plugin renders its markup from templates directly,
	 * so this starts empty and exists for add-ons to extend.
	 *
	 * @return array
	 */
	public function get_panels() {
		/**
		 * Filters the settings-screen panels.
		 *
		 * @param array $panels Panel id => array( 'label' => string, 'render' => callable ).
		 */
		return (array) apply_filters( 'eccw_settings_panels', array() );
	}

	/**
	 * Human names for the kit's colour tokens.
	 *
	 * The screen used to print the raw token id, so a store owner picking a
	 * colour chose between "Primary" and "2666acb8" — Elementor's internal
	 * id for a custom colour they had named "Light Grey" in the kit. The kit
	 * stores that title; it just was not being read.
	 *
	 * @return array Token id => human label.
	 */
	public static function kit_color_labels() {
		$labels = array(
			'button_normal'     => __( 'Button colour', 'commerce-colors-for-elementor' ),
			'button_hover'      => __( 'Button colour (mouse over)', 'commerce-colors-for-elementor' ),
			'button_text'       => __( 'Button text', 'commerce-colors-for-elementor' ),
			'button_hover_text' => __( 'Button text (mouse over)', 'commerce-colors-for-elementor' ),
			'link_normal'       => __( 'Link colour', 'commerce-colors-for-elementor' ),
			'link_hover'        => __( 'Link colour (mouse over)', 'commerce-colors-for-elementor' ),
			'body'              => __( 'Body text', 'commerce-colors-for-elementor' ),
		);

		for ( $level = 1; $level <= 6; $level++ ) {
			/* translators: %d: heading level, 1 to 6. */
			$labels[ 'h' . $level ] = sprintf( __( 'Heading %d', 'commerce-colors-for-elementor' ), $level );
		}

		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return $labels;
		}

		$kit = get_post_meta( (int) $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $kit ) ) {
			return $labels;
		}

		foreach ( array( 'system_colors', 'custom_colors' ) as $group ) {
			if ( empty( $kit[ $group ] ) || ! is_array( $kit[ $group ] ) ) {
				continue;
			}

			foreach ( $kit[ $group ] as $entry ) {
				if ( empty( $entry['_id'] ) || empty( $entry['title'] ) ) {
					continue;
				}

				$labels[ $entry['_id'] ] = $entry['title'];
			}
		}

		return $labels;
	}

	/**
	 * The active kit's own name.
	 *
	 * The product spec asks the screen to answer five questions, and this is the
	 * one it could not: "which Elementor kit is being used?" The id was already
	 * known — it builds the edit link — but a number is not an answer. Kits are
	 * usually called "Default Kit" until someone renames one, and a site with
	 * several is exactly where the question gets asked.
	 *
	 * @return string Kit title, or '' when there is no kit.
	 */
	public static function kit_name() {
		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return '';
		}

		$title = get_the_title( (int) $kit_id );

		return is_string( $title ) ? trim( $title ) : '';
	}

	/**
	 * Whether the active kit defines typography of its own.
	 *
	 * The mirror of CSS_Generator::has_kit_colors(), so the status area can say
	 * "typography detected" truthfully rather than assuming it.
	 *
	 * @return bool
	 */
	public static function has_kit_typography() {
		foreach ( Typography::kit_typography() as $token ) {
			if ( ! empty( $token['family'] ) || ! empty( $token['weight'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * One plain sentence per store element, saying where it is seen.
	 *
	 * The registry label ("Cart Table") names the code's idea of the element.
	 * This names the thing a shop owner would point at on their own site.
	 *
	 * @return array Widget key => description.
	 */
	public static function element_descriptions() {
		return array(
			'wc-add-to-cart'    => __( 'The main button on a product page.', 'commerce-colors-for-elementor' ),
			'wc-product-price'  => __( 'Prices, wherever they appear.', 'commerce-colors-for-elementor' ),
			'wc-sale-badge'     => __( 'The “Sale!” flash on a discounted product.', 'commerce-colors-for-elementor' ),
			'wc-star-rating'    => __( 'Review stars on products.', 'commerce-colors-for-elementor' ),
			'wc-product-tabs'   => __( 'The Description and Reviews tabs on a product.', 'commerce-colors-for-elementor' ),
			'wc-cart-table'     => __( 'The cart contents, and the mini cart in your header.', 'commerce-colors-for-elementor' ),
			'wc-checkout'       => __( 'Checkout fields, totals and the place-order button.', 'commerce-colors-for-elementor' ),
			'wc-notices'        => __( '“Added to cart” confirmations, warnings and errors.', 'commerce-colors-for-elementor' ),
			'wc-quantity-input' => __( 'The quantity box beside Add to cart.', 'commerce-colors-for-elementor' ),
			'wc-account'        => __( 'The My Account pages and their menu.', 'commerce-colors-for-elementor' ),
			'wc-general-links'  => __( 'Store links, such as category and product names.', 'commerce-colors-for-elementor' ),
			'wc-loop-buttons'   => __( 'Add-to-cart buttons in product listings.', 'commerce-colors-for-elementor' ),
		);
	}

	/**
	 * Human name for one of an element's interaction states.
	 *
	 * The registry calls these `button_normal`, `button_hover`, `button_focus`,
	 * `button_disabled` and labels them "Button normal" and so on. Nobody
	 * outside a codebase says "normal state" — and "focus" means nothing to
	 * someone who has never used a keyboard to shop.
	 *
	 * @param string $slot_id       Slot identifier.
	 * @param string $registry_label Fallback label from the registry.
	 * @return string
	 */
	public static function state_label( $slot_id, $registry_label = '', $base_label = '' ) {
		$states = array(
			'_hover'    => __( 'when the mouse is over it', 'commerce-colors-for-elementor' ),
			'_focus'    => __( 'when reached by keyboard', 'commerce-colors-for-elementor' ),
			'_disabled' => __( 'when it is unavailable', 'commerce-colors-for-elementor' ),
			'_active'   => __( 'when it is the current one', 'commerce-colors-for-elementor' ),
			'_normal'   => __( 'normally', 'commerce-colors-for-elementor' ),
		);

		$standalone = array(
			'_hover'    => __( 'When the mouse is over it', 'commerce-colors-for-elementor' ),
			'_focus'    => __( 'When reached by keyboard', 'commerce-colors-for-elementor' ),
			'_disabled' => __( 'When it is unavailable', 'commerce-colors-for-elementor' ),
			'_active'   => __( 'When it is the current one', 'commerce-colors-for-elementor' ),
			'_normal'   => __( 'Normally', 'commerce-colors-for-elementor' ),
		);

		foreach ( $states as $suffix => $phrase ) {
			if ( substr( $slot_id, -strlen( $suffix ) ) !== $suffix ) {
				continue;
			}

			// Name the thing the state belongs to whenever it is known. A card
			// can hold states for more than one sub-element — Checkout has
			// both a place-order button and input fields — and a bare "When
			// reached by keyboard" under a card titled Checkout reads as the
			// button while actually being the input border. That is a control
			// which looks broken: you tab to the button, and nothing changes.
			if ( '' !== $base_label ) {
				return sprintf(
					/* translators: 1: element name, e.g. "Input Fields". 2: state, e.g. "when reached by keyboard". */
					_x( '%1$s — %2$s', 'colour slot label', 'commerce-colors-for-elementor' ),
					$base_label,
					$phrase
				);
			}

			return $standalone[ $suffix ];
		}

		return '' !== $registry_label ? $registry_label : $slot_id;
	}

	/**
	 * Build a map of slot_id => default color token from the registry.
	 *
	 * @return array Associative array keyed by slot_id.
	 */
	private function get_slot_defaults() {
		$heuristic = new Heuristic_Engine();
		$defaults  = array();

		foreach ( Element_Registry::get_registry() as $definition ) {
			foreach ( $definition['slots'] as $slot ) {
				$defaults[ $slot['slot_id'] ] = $heuristic->determine_color( $slot['slot_id'] );
			}
		}

		return $defaults;
	}

	/**
	 * Build a map of slot_id => the colour tokens its chooser offers.
	 *
	 * The specification asks for a short list — the four brand tokens plus
	 * Custom — rather than every token the kit resolves. `Heuristic_Engine`
	 * decides which non-brand token a slot may additionally offer, so a button
	 * lists the kit's button colour and nothing lists all six heading colours.
	 *
	 * @return array Slot id => ordered token ids.
	 */
	private function get_slot_choices() {
		$heuristic = new Heuristic_Engine();
		$choices   = array();

		foreach ( Element_Registry::get_registry() as $definition ) {
			foreach ( $definition['slots'] as $slot ) {
				$choices[ $slot['slot_id'] ] = $heuristic->choices_for( $slot['slot_id'] );
			}
		}

		return $choices;
	}

	/**
	 * Count widgets whose status is "new".
	 *
	 * @param array $mappings Widget mappings.
	 * @return int
	 */
	private function count_new_widgets( $mappings ) {
		$count = 0;

		foreach ( $mappings as $widget_data ) {
			if ( isset( $widget_data['status'] ) && 'new' === $widget_data['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved            = get_option( 'eccw_colors_mappings', array() );
		$mappings         = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$settings         = Settings::all();
		$dequeue_settings = $settings; // Backwards compatibility for templates.
		$kit_colors       = CSS_Generator::get_kit_colors();
		$defaults         = $this->get_slot_defaults();
		$slot_choices     = $this->get_slot_choices();
		$new_count        = $this->count_new_widgets( $mappings );
		$dequeue_disabled = empty( $mappings );

		$shop_page = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : false;
		$shop_url  = $shop_page ? $shop_page : home_url();
		$features  = Features::all();
		$panels    = $this->get_panels();

		$kit_labels    = self::kit_color_labels();
		$descriptions  = self::element_descriptions();
		$kit_id        = get_option( 'elementor_active_kit' );
		$kit_edit_url  = $kit_id
			? admin_url( 'post.php?post=' . (int) $kit_id . '&action=elementor' )
			: admin_url( 'admin.php?page=elementor-app' );
		$has_own_kit   = CSS_Generator::has_kit_colors();
		$global_chrome = CSS_Generator::has_global_wc_chrome();
		$kit_name      = self::kit_name();
		$has_own_fonts = self::has_kit_typography();

		/**
		 * Fires at the top of the settings screen, inside the wrapper.
		 */
		do_action( 'eccw_settings_page_before' );

		include ECCW_PATH . 'admin/templates/settings-page.php';

		/**
		 * Fires at the bottom of the settings screen.
		 *
		 * @param array $panels Registered add-on panels.
		 */
		do_action( 'eccw_settings_page_after', $panels );
	}

	public function handle_form_save() {
		if ( ! isset( $_POST['eccw_save_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['eccw_save_nonce'] ) ), 'eccw_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'commerce-colors-for-elementor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'commerce-colors-for-elementor' ) );
		}

		// Checkboxes are absent from the POST body when unticked, so every one
		// is written explicitly rather than only when present.
		Settings::update(
			array(
				'enabled'          => isset( $_POST['eccw_enabled'] ),
				'force_important'  => isset( $_POST['eccw_force_important'] ),
				'auto_contrast'    => isset( $_POST['eccw_auto_contrast'] ),
				'derive_states'    => isset( $_POST['eccw_derive_states'] ),
				'apply_typography' => isset( $_POST['eccw_apply_typography'] ),
				'dequeue_blocks'   => isset( $_POST['eccw_dequeue_blocks'] ),
			)
		);

		$saved = get_option( 'eccw_colors_mappings', array() );

		if ( Mapping_Service::normalize_all( $saved ) ) {
			update_option( 'eccw_colors_mappings', $saved );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values validated against kit color IDs below.
		$posted_colors = isset( $_POST['eccw_colors'] ) && is_array( $_POST['eccw_colors'] )
			? wp_unslash( $_POST['eccw_colors'] )
			: array();
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$kit_colors = CSS_Generator::get_kit_colors();
		$valid_ids  = array_keys( $kit_colors );

		foreach ( $posted_colors as $widget_key => $slots ) {
			$widget_key = \ElementorColorChanger\Element_Registry::normalize_key( sanitize_key( $widget_key ) );

			if ( ! isset( $saved['widgets'][ $widget_key ] ) ) {
				continue;
			}

			if ( ! is_array( $slots ) ) {
				continue;
			}

			$changed = false;

			foreach ( $slots as $slot_id => $color_value ) {
				$color_value = strtolower( (string) $color_value );

				// Accept either a kit color token or a raw hex value
				// (custom colors chosen in the Live Editor).
				if ( ! in_array( $color_value, $valid_ids, true ) ) {
					$hex = ltrim( $color_value, '#' );

					if ( ! preg_match( '/^[0-9a-f]{6}$/', $hex ) ) {
						continue;
					}

					$color_value = '#' . $hex;
				}

				if ( ! isset( $saved['widgets'][ $widget_key ]['slots'][ $slot_id ] ) ) {
					continue;
				}

				$saved['widgets'][ $widget_key ]['slots'][ $slot_id ]['color'] = $color_value;
				$changed = true;
			}

			if ( $changed && 'configured' !== $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'configured';
			}
		}

		// Bump the mappings version so any cache key derived from it changes.
		// Without this the version stayed at 1 forever and the cache relied
		// solely on the explicit clear below.
		Mapping_Service::bump_version( $saved );

		update_option( 'eccw_colors_mappings', $saved );

		Cache_Manager::clear_css();

		wp_safe_redirect(
			add_query_arg( 'saved', '1', admin_url( 'admin.php?page=eccw-settings' ) )
		);

		exit;
	}
}
