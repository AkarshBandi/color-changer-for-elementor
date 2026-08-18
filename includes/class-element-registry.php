<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Element_Registry {

	/**
	 * A fingerprint of everything in the registry that shapes generated CSS.
	 *
	 * Exists so a release that adds a slot or widens a selector is noticed
	 * without anyone having to remember to bump a version constant. Slots
	 * added in an update used to sit inert until the owner happened to press
	 * Rescan or re-save: the code shipped, the stylesheet did not change, and
	 * nothing anywhere said why. Keying off a version number would only move
	 * that dependency onto a human remembering to increment it; keying off the
	 * registry's own shape means the trigger is the change itself.
	 *
	 * Covers group keys, slot ids, selectors, properties and states, because
	 * each of those alters the emitted CSS. Deliberately includes anything a
	 * third party adds through `eccw_element_registry`, so an addon
	 * registering a slot is picked up on the same path as a core release.
	 *
	 * @return string
	 */
	public static function signature() {
		$shape = array();

		foreach ( self::get_registry() as $key => $group ) {
			$slots = array();

			foreach ( isset( $group['slots'] ) ? (array) $group['slots'] : array() as $slot ) {
				$slots[] = array(
					isset( $slot['slot_id'] ) ? $slot['slot_id'] : '',
					isset( $slot['selectors'] ) ? (array) $slot['selectors'] : array(),
					isset( $slot['properties'] ) ? (array) $slot['properties'] : array(),
					isset( $slot['states'] ) ? (array) $slot['states'] : array(),
					isset( $slot['derives_from'] ) ? $slot['derives_from'] : '',
				);
			}

			$shape[ $key ] = $slots;
		}

		ksort( $shape );

		return md5( (string) wp_json_encode( $shape ) );
	}

	public static function get_registry() {
		$registry = apply_filters(
			'eccw_element_registry',
			array(
				'wc-add-to-cart'    => self::widget_add_to_cart(),
				'wc-product-price'  => self::widget_product_price(),
				'wc-sale-badge'     => self::widget_sale_badge(),
				'wc-star-rating'    => self::widget_star_rating(),
				'wc-product-tabs'   => self::widget_product_tabs(),
				'wc-cart-table'     => self::widget_cart_table(),
				'wc-checkout'       => self::widget_checkout(),
				'wc-notices'        => self::widget_notices(),
				'wc-quantity-input' => self::widget_quantity_input(),
				'wc-account'        => self::widget_account(),
				'wc-general-links'  => self::widget_general_links(),
				'wc-loop-buttons'   => self::widget_loop_buttons(),
			)
		);

		return self::apply_state_policy( $registry );
	}

	/**
	 * Widget keys in the order their CSS should be written.
	 *
	 * The registry's own order is the order the settings screen shows cards in,
	 * which is chosen for reading, not for the cascade. Every rule this plugin
	 * emits carries `!important`, so between two `!important` rules of equal
	 * specificity the winner is simply whichever was written last — which made
	 * the cascade an accident of how the cards happened to be listed.
	 *
	 * A definition may declare an integer `weight`; lower is written earlier.
	 * Broad cards take a negative weight so an element-specific rule of the
	 * same specificity always wins. Ties keep registry order.
	 *
	 * @param array $widgets Widget key => mapping or definition.
	 * @return string[] Widget keys, in emission order.
	 */
	public static function emission_order( array $widgets ) {
		$registry = self::get_registry();
		$ordered  = array();
		$position = 0;

		foreach ( $widgets as $widget_key => $unused ) {
			$weight = isset( $registry[ $widget_key ]['weight'] )
				? (int) $registry[ $widget_key ]['weight']
				: 0;

			$ordered[] = array(
				'key'      => $widget_key,
				'weight'   => $weight,
				'position' => $position,
			);

			++$position;
		}

		usort(
			$ordered,
			static function ( $a, $b ) {
				if ( $a['weight'] === $b['weight'] ) {
					// Explicit, so the result does not depend on whether the
					// sort implementation happens to be stable.
					return $a['position'] <=> $b['position'];
				}

				return $a['weight'] <=> $b['weight'];
			}
		);

		return wp_list_pluck( $ordered, 'key' );
	}

	/**
	 * Restrict every slot to the states the site is allowed to style.
	 *
	 * The default list contains every state the registry defines, so this is a
	 * no-op unless something narrows it. It is the single seam through which
	 * state availability is controlled, rather than editing each widget
	 * definition.
	 *
	 * @param array $registry Element registry.
	 * @return array Registry with states filtered.
	 */
	private static function apply_state_policy( $registry ) {
		/**
		 * Filters which interaction states may be styled.
		 *
		 * @param string[] $states Allowed state names.
		 */
		$allowed = apply_filters(
			'eccw_slot_states',
			array( 'normal', 'hover', 'focus', 'active', 'disabled' )
		);

		if ( ! is_array( $allowed ) || empty( $allowed ) ) {
			return $registry;
		}

		foreach ( $registry as $widget_key => $definition ) {
			if ( ! isset( $definition['slots'] ) || ! is_array( $definition['slots'] ) ) {
				continue;
			}

			foreach ( $definition['slots'] as $index => $slot ) {
				if ( ! isset( $slot['states'] ) || ! is_array( $slot['states'] ) ) {
					continue;
				}

				$kept = array_values( array_intersect( $slot['states'], $allowed ) );

				// Never strip a slot back to nothing; 'normal' always survives.
				if ( empty( $kept ) ) {
					$kept = array( 'normal' );
				}

				$registry[ $widget_key ]['slots'][ $index ]['states'] = $kept;
			}
		}

		return $registry;
	}

	/**
	 * Map third-party addon widget types to registry keys.
	 *
	 * Elementor (free) does not register real WooCommerce widgets; sites
	 * build them with addons such as Essential Addons, Premium Addons or
	 * Happy Addons. This lets discovery/normalization recognize those widget
	 * types and route them to the matching registry element.
	 *
	 * Elementor Pro's own Menu Cart is in here too, which looks redundant next
	 * to the addons but is not: its widget type carries the woocommerce prefix,
	 * so it was always recognised as part of the family, yet key normalisation
	 * had nothing to map it to. It normalised to itself, and anything that then
	 * looked it up in the registry found nothing. Its drawer chrome is
	 * Pro-addon territory; the totals, prices and buttons inside it belong to
	 * the cart table's slots.
	 *
	 * @return array
	 */
	public static function get_addon_map() {
		// Free, and deliberately so. This map is not "extra widgets you may
		// style" — it is how discovery recognises a widget as a WooCommerce
		// element at all. Elementor free ships no WooCommerce widgets, so a
		// large share of sites build them with Essential Addons or Premium
		// Addons. Gating the map would mean those sites discover nothing and
		// experience the plugin as broken rather than as limited.
		//
		// The filter below is the seam for widening coverage, and needs no
		// gate of its own.
		return apply_filters(
			'eccw_addon_widget_map',
			array(
				// Elementor Pro's own WooCommerce widgets.
				//
				// These were missing, which is the wrong way round: the addon
				// entries below cover third-party stacks, while the widgets
				// this plugin is named after fell through to the prefix
				// heuristic in normalize_key(). That heuristic turns
				// `woocommerce-<x>` into `wc-<x>` and keeps it only if the
				// registry has that exact key — true for `product-price` and
				// `notices`, and false for everything else, because the
				// registry names cards after the thing being styled
				// (`wc-star-rating`, `wc-loop-buttons`) rather than after the
				// widget that renders it. So 18 of Elementor Pro's 21
				// WooCommerce widgets normalised to themselves and matched no
				// card at all.
				//
				// Only widgets an existing card actually styles are listed.
				// Product images and product title have no colour slots, so
				// they stay unmapped rather than being routed somewhere
				// plausible-looking.
			'woocommerce-products'                      => 'wc-loop-buttons',
				'woocommerce-archive-products'          => 'wc-loop-buttons',
				'wc-archive-products'                   => 'wc-loop-buttons',
				'woocommerce-product-related'           => 'wc-loop-buttons',
				'woocommerce-product-upsell'            => 'wc-loop-buttons',
				'woocommerce-product-add-to-cart'       => 'wc-add-to-cart',
				'woocommerce-product-rating'            => 'wc-star-rating',
				'woocommerce-product-data-tabs'         => 'wc-product-tabs',
				'woocommerce-cart'                      => 'wc-cart-table',
				'woocommerce-purchase-summary'          => 'wc-cart-table',
				'woocommerce-product-additional-information' => 'wc-cart-table',
				'woocommerce-checkout-page'             => 'wc-checkout',
				'woocommerce-my-account'                => 'wc-account',
				// Read off the widgets Elementor Pro actually registers on the
				// reference store rather than assumed. These were absent, so
				// they were recognised by the prefix heuristic but never
				// mapped to a styling group.
				'woocommerce-notices'                   => 'wc-notices',
				'wc-add-to-cart'                        => 'wc-add-to-cart',
				'wc-products'                           => 'wc-loop-buttons',
				'woocommerce-product-title'             => 'wc-general-links',
				'woocommerce-product-content'           => 'wc-general-links',
				'woocommerce-product-stock'             => 'wc-general-links',
				'woocommerce-archive-description'       => 'wc-general-links',
				'woocommerce-breadcrumb'                => 'wc-general-links',
				'woocommerce-product-meta'              => 'wc-general-links',
				'woocommerce-product-short-description' => 'wc-general-links',

				'eael-woo-add-to-cart'                  => 'wc-add-to-cart',
				'eael-woo-product-price'                => 'wc-product-price',
				'eael-woo-product-rating'               => 'wc-star-rating',
				'eael-woo-product-tabs'                 => 'wc-product-tabs',
				'eael-woo-cart'                         => 'wc-cart-table',
				'eael-woo-checkout'                     => 'wc-checkout',
				'eicon-woocommerce'                     => 'wc-loop-buttons',
				'premium-woo-products'                  => 'wc-loop-buttons',
				'premium-woo-cta'                       => 'wc-add-to-cart',
				'premium-mini-cart'                     => 'wc-cart-table',
				'premium-woo-categories'                => 'wc-general-links',
				'product-grid-new'                      => 'wc-loop-buttons',
				'product-carousel-new'                  => 'wc-loop-buttons',
				'product-category-grid-new'             => 'wc-general-links',
				'product-category-carousel-new'         => 'wc-general-links',
				'single-product-new'                    => 'wc-add-to-cart',
				'mini-cart'                             => 'wc-cart-table',
				'wc-cart'                               => 'wc-cart-table',
				'woocommerce-menu-cart'                 => 'wc-cart-table',
			)
		);
	}

	/**
	 * Option caching the widget types Elementor itself files under WooCommerce.
	 */
	const WC_WIDGETS_OPTION = 'eccw_wc_widget_types';

	/**
	 * Whether a widget type belongs to the WooCommerce element family.
	 *
	 * Deliberately asks no vendor's name. An earlier version matched
	 * `^eael-woo-`, `^premium-woo-`, `^hm-woo-` and friends, which is a list of
	 * plugins pretending to be a rule: it dates the moment a pack renames a
	 * prefix, and it silently excludes every addon nobody thought of. Support
	 * for a widget should never require a release of this plugin.
	 *
	 * Three source-agnostic layers, cheapest first:
	 *
	 * 1. The registry map, which is filterable — an integration or a site can
	 *    declare any widget type without touching core.
	 * 2. Elementor's own metadata. A widget registered into one of Elementor's
	 *    WooCommerce categories is a WooCommerce widget, whoever shipped it;
	 *    this catches third-party packs for free, and catches the ones that do
	 *    not exist yet. Read from a cached set, because instantiating every
	 *    registered widget is far too expensive for a front-end request.
	 * 3. A domain-token test for when Elementor is not loaded — cron, CLI, a
	 *    scan of stored data. It names the domain (`woocommerce`, `woo`, `wc`)
	 *    as a whole token, never a vendor, so `eael-woo-product-price` and
	 *    `acme-woo-anything` both match on the same rule. Bare `product`,
	 *    `price` and `cart` are excluded on purpose: Elementor ships a generic
	 *    `price-table` and `price-list`, and claiming those would put this
	 *    stylesheet on pages with no store on them.
	 *
	 * @param string $widget_type Elementor widget type.
	 * @return bool
	 */
	public static function is_woocommerce_widget_type( $widget_type ) {
		if ( ! is_string( $widget_type ) || '' === $widget_type ) {
			return false;
		}

		$addon_map = self::get_addon_map();

		if ( isset( $addon_map[ $widget_type ] ) ) {
			return true;
		}

		if ( in_array( $widget_type, self::woocommerce_widget_types(), true ) ) {
			return true;
		}

		return (bool) preg_match( '/(^|[-_])(woocommerce|woo|wc)([-_]|$)/i', $widget_type );
	}

	/**
	 * Widget types Elementor files under a WooCommerce category.
	 *
	 * Cached in an option and refreshed by discovery, never computed on a
	 * front-end request: `get_widget_types()` instantiates every registered
	 * widget, which is exactly the kind of work that must not happen while
	 * somebody is waiting for a page.
	 *
	 * @return string[]
	 */
	public static function woocommerce_widget_types() {
		$cached = get_option( self::WC_WIDGETS_OPTION, null );

		return is_array( $cached ) ? $cached : array();
	}

	/**
	 * Ask Elementor which widgets it considers WooCommerce, and cache it.
	 *
	 * Called from discovery and rescan, where the cost is acceptable and the
	 * answer is wanted fresh.
	 *
	 * @return string[] The widget types found.
	 */
	public static function refresh_woocommerce_widget_types() {
		$found = array();

		if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			/**
			 * Filters which Elementor widget categories denote WooCommerce.
			 *
			 * Matched as a substring so Elementor's own
			 * `woocommerce-elements`, `woocommerce-elements-single` and
			 * `woocommerce-elements-archive` are all covered by one entry, as
			 * is any category an addon registers alongside them.
			 *
			 * @param string[] $categories Category name fragments.
			 */
			$categories = (array) apply_filters( 'eccw_woocommerce_widget_categories', array( 'woocommerce' ) );

			$widgets = \Elementor\Plugin::$instance->widgets_manager->get_widget_types();

			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $name => $widget ) {
					if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_categories' ) ) {
						continue;
					}

					foreach ( (array) $widget->get_categories() as $category ) {
						foreach ( $categories as $needle ) {
							if ( '' !== $needle && false !== stripos( (string) $category, $needle ) ) {
								$found[] = $name;
								continue 3;
							}
						}
					}
				}
			}
		}

		$found = array_values( array_unique( $found ) );

		update_option( self::WC_WIDGETS_OPTION, $found, false );

		return $found;
	}

	public static function normalize_key( $widget_type ) {
		$registry = self::get_registry();

		if ( isset( $registry[ $widget_type ] ) ) {
			return $widget_type;
		}

		$addon_map = self::get_addon_map();

		if ( isset( $addon_map[ $widget_type ] ) ) {
			return $addon_map[ $widget_type ];
		}

		if ( strpos( $widget_type, 'woocommerce-' ) === 0 ) {
			$base   = substr( $widget_type, 12 );
			$wc_key = 'wc-' . $base;
			if ( isset( $registry[ $wc_key ] ) ) {
				return $wc_key;
			}
		}

		if ( strpos( $widget_type, 'wc-' ) === 0 ) {
			$base    = substr( $widget_type, 3 );
			$woo_key = 'woocommerce-' . $base;
			if ( isset( $registry[ $woo_key ] ) ) {
				return $woo_key;
			}
		}

		$woo_try = 'woocommerce-' . $widget_type;
		if ( isset( $registry[ $woo_try ] ) ) {
			return $woo_try;
		}

		$wc_try = 'wc-' . $widget_type;
		if ( isset( $registry[ $wc_try ] ) ) {
			return $wc_try;
		}

		return $widget_type;
	}

	public static function lookup( $widget_type ) {
		$registry = self::get_registry();

		if ( isset( $registry[ $widget_type ] ) ) {
			return $registry[ $widget_type ];
		}

		$addon_map = self::get_addon_map();

		if ( isset( $addon_map[ $widget_type ] ) ) {
			$registry_key = $addon_map[ $widget_type ];

			if ( isset( $registry[ $registry_key ] ) ) {
				return $registry[ $registry_key ];
			}
		}

		if ( strpos( $widget_type, 'woocommerce-' ) === 0 ) {
			$base   = substr( $widget_type, 12 );
			$wc_key = 'wc-' . $base;
			if ( isset( $registry[ $wc_key ] ) ) {
				return $registry[ $wc_key ];
			}
		}

		if ( strpos( $widget_type, 'wc-' ) === 0 ) {
			$base    = substr( $widget_type, 3 );
			$woo_key = 'woocommerce-' . $base;
			if ( isset( $registry[ $woo_key ] ) ) {
				return $registry[ $woo_key ];
			}
		}

		$woo_try = 'woocommerce-' . $widget_type;
		if ( isset( $registry[ $woo_try ] ) ) {
			return $registry[ $woo_try ];
		}

		$wc_try = 'wc-' . $widget_type;
		if ( isset( $registry[ $wc_try ] ) ) {
			return $registry[ $wc_try ];
		}

		return null;
	}

	private static function widget_add_to_cart() {
		$s = array( '.single_add_to_cart_button', '.woocommerce .button.alt', '.woocommerce button.button.alt', '.woocommerce a.button.alt', '#respond input#submit.alt', '.woocommerce .wc-block-components-button:not(.is-link)', '.woocommerce button[name="apply_coupon"]', '.woocommerce-form-login__submit', '.woocommerce-mini-cart__buttons a' );
		return array(
			'label' => __( 'Add to Cart Button', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'button_normal',
					'label'      => __( 'Normal', 'commerce-colors-for-elementor' ),
					'selectors'  => $s,
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color', 'fill' ),
				),
				array(
					'slot_id'      => 'button_hover',
					'derives_from' => 'button_normal',
					'label'        => __( 'Hover', 'commerce-colors-for-elementor' ),
					'selectors'    => $s,
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'button_focus',
					'derives_from' => 'button_normal',
					'label'        => __( 'Focus', 'commerce-colors-for-elementor' ),
					'selectors'    => $s,
					'states'       => array( 'focus' ),
					'properties'   => array( 'background-color', 'color', 'border-color' ),
				),
				array(
					'slot_id'      => 'button_disabled',
					'derives_from' => 'button_normal',
					'label'        => __( 'Disabled', 'commerce-colors-for-elementor' ),
					'selectors'    => $s,
					'states'       => array( 'disabled' ),
					'properties'   => array( 'background-color', 'color' ),
				),
			),
		);
	}

	private static function widget_product_price() {
		return array(
			'label' => __( 'Product Price', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'price_regular',
					'label'      => __( 'Regular Price', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce div.product p.price', '.woocommerce div.product span.price', '.woocommerce ul.products li.product .price', '.price', '.woocommerce-Price-amount', '.wc-block-components-product-price' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'price_sale',
					'label'      => __( 'Sale Price', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce div.product p.price ins', '.woocommerce ul.products li.product .price ins', '.price ins' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_sale_badge() {
		return array(
			'label' => __( 'Sale Badge', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'badge_normal',
					'label'      => __( 'Sale Badge', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce span.onsale', '.wc-block-components-product-sale-badge' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
			),
		);
	}

	private static function widget_star_rating() {
		return array(
			'label' => __( 'Star Rating', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'stars_filled',
					'label'      => __( 'Filled Stars', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.star-rating', '.star-rating span', '.star-rating span::before', '.wc-block-components-product-rating__stars' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'stars_empty',
					'label'      => __( 'Empty Stars', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.star-rating::before', '.wc-block-components-product-rating__stars::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_product_tabs() {
		return array(
			'label' => __( 'Product Tabs', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'tab_normal',
					'label'      => __( 'Tab', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce div.product .woocommerce-tabs ul.tabs li a', '.wc-tabs li a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'      => 'tab_active',
					'derives_from' => 'tab_normal',
					'label'        => __( 'Active Tab', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.woocommerce div.product .woocommerce-tabs ul.tabs li.active a', '.wc-tabs li.active a' ),
					'states'       => array( 'normal' ),
					'properties'   => array( 'color' ),
				),
			),
		);
	}

	private static function widget_cart_table() {
		return array(
			'label' => __( 'Cart Contents', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'table_header',
					'label'      => __( 'Column Headings', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce table.shop_table th', '.woocommerce table.cart th', '.wc-block-cart-items__header', '.elementor-menu-cart__subtotal' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					// Text only, deliberately.
					//
					// This slot used to also carry 'border-color' and target the
					// <table> element itself. The cart table's row and column
					// dividers *are* those borders, so choosing a text colour
					// repainted every structural line in the grid with it —
					// heavy and boxed-in with a dark colour, and completely
					// gone with a light one, which is what made the cart read
					// as unstructured text rather than a table. Nobody picking
					// a colour for something labelled "Table Rows" is asking to
					// redraw the table's structure, so WooCommerce keeps its
					// own dividers.
					'slot_id'    => 'table_cell',
					'label'      => __( 'Rows', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce table.shop_table td', '.wc-block-cart-items', '.elementor-menu-cart__products', '.elementor-menu-cart__product' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'proceed_button',
					'label'      => __( 'Checkout Button', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce .wc-proceed-to-checkout a.checkout-button', '.elementor-menu-cart__footer-buttons a.elementor-button--checkout' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'proceed_hover',
					'derives_from' => 'proceed_button',
					'label'        => __( 'Checkout Button (Hover)', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.woocommerce .wc-proceed-to-checkout a.checkout-button', '.elementor-menu-cart__footer-buttons a.elementor-button--checkout' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'    => 'coupon_input',
					'label'      => __( 'Coupon Box', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-cart-form .coupon .input-text', '.woocommerce form.checkout_coupon .input-text', '#coupon_code', '.wc-block-cart .wc-block-components-text-input input' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
				),
				array(
					// The cart button in the header, which on an Elementor Pro
					// site is the single most-seen store element: it renders on
					// every page, and it is the widget whose presence puts this
					// stylesheet on the site in the first place. It was
					// detected and then left unpainted, sitting at Elementor's
					// default grey next to a store styled in the owner's
					// colours. Elementor renders its own markup here rather
					// than a WooCommerce template, so no WooCommerce selector
					// could ever have reached it.
					'slot_id'    => 'menu_cart_toggle',
					'label'      => __( 'Cart Button in Header', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.elementor-menu-cart__toggle .elementor-button', '.elementor-menu-cart__toggle_button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'menu_cart_count',
					'label'      => __( 'Cart Item Count', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.elementor-button-icon-qty', '.elementor-menu-cart__toggle .elementor-button-icon-qty' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'    => 'update_cart',
					'label'      => __( 'Update Cart', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce button[name="update_cart"]' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'update_cart_hover',
					'derives_from' => 'update_cart',
					'label'        => __( 'Update Cart (Hover)', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.woocommerce button[name="update_cart"]' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
			),
		);
	}

	private static function widget_checkout() {
		return array(
			'label' => __( 'Checkout', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'place_order',
					'label'      => __( 'Place Order Button', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '#place_order', '.woocommerce .checkout .button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'place_order_hover',
					'derives_from' => 'place_order',
					'label'        => __( 'Place Order (Hover)', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '#place_order', '.woocommerce .checkout .button' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'    => 'input_text',
					'label'      => __( 'Boxes to Fill In', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce .input-text', '.woocommerce select', '.select2-selection--single', '.woocommerce .select2-container--default .select2-selection--single', '.wc-block-checkout .wc-block-components-text-input input' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
				),
				array(
					'slot_id'      => 'input_focus',
					'derives_from' => 'input_text',
					'label'        => __( 'Boxes to Fill In (Focus)', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.woocommerce .input-text', '.woocommerce select', '.select2-selection--single', '.woocommerce .select2-container--default .select2-selection--single' ),
					'states'       => array( 'focus' ),
					'properties'   => array( 'border-color' ),
				),
			),
		);
	}

	private static function widget_notices() {
		return array(
			'label' => __( 'Messages', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'success_border',
					'label'      => __( 'Success Border', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-message', '.wc-block-components-notice-banner.is-success' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
				),
				array(
					'slot_id'    => 'success_icon',
					'label'      => __( 'Success Icon', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-message::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'info_border',
					'label'      => __( 'Info Border', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-info', '.wc-block-components-notice-banner.is-info' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
				),
				array(
					'slot_id'    => 'info_icon',
					'label'      => __( 'Info Icon', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-info::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'error_border',
					'label'      => __( 'Error Border', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-error', '.wc-block-components-notice-banner.is-error' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
				),
				array(
					'slot_id'    => 'error_icon',
					'label'      => __( 'Error Icon', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-error::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_quantity_input() {
		return array(
			'label' => __( 'Quantity Box', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'qty_input',
					'label'      => __( 'Quantity Box', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.quantity .qty', 'input.qty', '.wc-block-components-quantity-selector input' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
				),
				array(
					'slot_id'      => 'qty_focus',
					'derives_from' => 'qty_input',
					'label'        => __( 'Quantity Box (Focus)', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.quantity .qty', 'input.qty' ),
					'states'       => array( 'focus' ),
					'properties'   => array( 'border-color' ),
				),
			),
		);
	}

	private static function widget_account() {
		return array(
			'label' => __( 'My Account', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'nav_link',
					'label'      => __( 'Menu Link', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-MyAccount-navigation-link a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'      => 'nav_active',
					'derives_from' => 'nav_link',
					'label'        => __( 'Active Menu Link', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.woocommerce-MyAccount-navigation-link.is-active a' ),
					'states'       => array( 'normal' ),
					'properties'   => array( 'color' ),
				),
			),
		);
	}

	/**
	 * Store content links.
	 *
	 * These selectors are deliberately enumerated rather than written as one
	 * catch-all. The slot used to be `.woocommerce a:not(.elementor-button)`,
	 * which reads as "links inside the store" but is not what it matches:
	 * WooCommerce puts a `woocommerce` class on `<body>` for every shop,
	 * product, cart, checkout and account page, so the rule matched *every*
	 * anchor in the document — 222 of them on one product page here, including
	 * the theme's header and footer navigation and the admin bar.
	 *
	 * That caused two separate failures. The store's own menu was repainted
	 * (hover included), which is not this plugin's business; and because the
	 * rule carries `!important` with specificity (0,2,1) and was emitted late,
	 * it silently defeated other cards' equally specific rules — the My Account
	 * "current menu link" colour among them, so that control saved correctly
	 * and then appeared to do nothing.
	 *
	 * Anything added here must name a store region. A bare element or a
	 * body-level class is the same bug again.
	 */
	private static function widget_general_links() {
		$link_selectors = array(
			'.woocommerce-breadcrumb a',
			'.woocommerce ul.products li.product a.woocommerce-loop-product__link',
			'.woocommerce ul.products li.product-category a',
			'.woocommerce-Tabs-panel a',
			'.product_meta a',
			'.woocommerce-pagination a',
			'.woocommerce-widget-layered-nav a',
			'.widget_product_categories a',
		);

		return array(
			'label'  => __( 'General Links', 'commerce-colors-for-elementor' ),
			// Emitted before the element-specific cards. Two rules of equal
			// specificity are decided by source order, and this card is the
			// broadest one here, so it must lose those ties rather than win
			// them. See Element_Registry::emission_order().
			'weight' => -10,
			'slots'  => array(
				array(
					'slot_id'    => 'link_normal',
					'label'      => __( 'Link', 'commerce-colors-for-elementor' ),
					'selectors'  => $link_selectors,
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'      => 'link_hover',
					'derives_from' => 'link_normal',
					'label'        => __( 'Link (Hover)', 'commerce-colors-for-elementor' ),
					'selectors'    => $link_selectors,
					'states'       => array( 'hover' ),
					'properties'   => array( 'color' ),
				),
				array(
					'slot_id'    => 'body_text',
					'label'      => __( 'Body Text', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce-mini-cart__total', '.woocommerce-Reviews', '.woocommerce-review__author', '.woocommerce-product-details__short-description' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_loop_buttons() {
		return array(
			'label' => __( 'Shop Page Buttons', 'commerce-colors-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'loop_button',
					'label'      => __( 'Add to Cart', 'commerce-colors-for-elementor' ),
					'selectors'  => array( '.woocommerce ul.products li.product .button', 'a.add_to_cart_button', '.woocommerce .products .button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'loop_hover',
					'derives_from' => 'loop_button',
					'label'        => __( 'Add to Cart (Hover)', 'commerce-colors-for-elementor' ),
					'selectors'    => array( '.woocommerce ul.products li.product .button', 'a.add_to_cart_button', '.woocommerce .products .button' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
			),
		);
	}
}
