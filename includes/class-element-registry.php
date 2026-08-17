<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Element_Registry {

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
				'woocommerce-breadcrumb'                => 'wc-general-links',
				'woocommerce-product-meta'              => 'wc-general-links',
				'woocommerce-product-short-description' => 'wc-general-links',

				'eael-woo-add-to-cart'          => 'wc-add-to-cart',
				'eael-woo-product-price'        => 'wc-product-price',
				'eael-woo-product-rating'       => 'wc-star-rating',
				'eael-woo-product-tabs'         => 'wc-product-tabs',
				'eael-woo-cart'                 => 'wc-cart-table',
				'eael-woo-checkout'             => 'wc-checkout',
				'eicon-woocommerce'             => 'wc-loop-buttons',
				'premium-woo-products'          => 'wc-loop-buttons',
				'premium-woo-cta'               => 'wc-add-to-cart',
				'premium-mini-cart'             => 'wc-cart-table',
				'premium-woo-categories'        => 'wc-general-links',
				'product-grid-new'              => 'wc-loop-buttons',
				'product-carousel-new'          => 'wc-loop-buttons',
				'product-category-grid-new'     => 'wc-general-links',
				'product-category-carousel-new' => 'wc-general-links',
				'single-product-new'            => 'wc-add-to-cart',
				'mini-cart'                     => 'wc-cart-table',
				'wc-cart'                       => 'wc-cart-table',
				'woocommerce-menu-cart'         => 'wc-cart-table',
			)
		);
	}

	/**
	 * Whether a widget type string belongs to the WooCommerce element family.
	 *
	 * Matches native Elementor widget types (woocommerce-*, wc-*) plus
	 * addon widget types from Essential Addons (eael-woo-*, eicon-woocommerce),
	 * Premium Addons (premium-woo-*) and Happy Addons (product-grid-new,
	 * wc-cart, mini-cart, ...). Any widget type in the addon map counts too.
	 *
	 * @param string $widget_type
	 * @return bool
	 */
	public static function is_woocommerce_widget_type( $widget_type ) {
		if ( ! is_string( $widget_type ) || '' === $widget_type ) {
			return false;
		}

		if ( preg_match( '/woocommerce|^wc-|^eael-woo-|^premium-woo-|^eicon-woocommerce/', $widget_type ) ) {
			return true;
		}

		// Addon widgets are recognised by the addon map. The prefix list above
		// covers the families that predate the map; keeping the map as the
		// source of truth means widening coverage in get_addon_map() also
		// widens recognition here.
		$addon_map = self::get_addon_map();

		return isset( $addon_map[ $widget_type ] );
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
			'label' => __( 'Add to Cart Button', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'button_normal',
					'label'      => __( 'Normal', 'color-changer-for-elementor' ),
					'selectors'  => $s,
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color', 'fill' ),
				),
				array(
					'slot_id'      => 'button_hover',
					'derives_from' => 'button_normal',
					'label'        => __( 'Hover', 'color-changer-for-elementor' ),
					'selectors'    => $s,
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'button_focus',
					'derives_from' => 'button_normal',
					'label'        => __( 'Focus', 'color-changer-for-elementor' ),
					'selectors'    => $s,
					'states'       => array( 'focus' ),
					'properties'   => array( 'background-color', 'color', 'border-color' ),
				),
				array(
					'slot_id'      => 'button_disabled',
					'derives_from' => 'button_normal',
					'label'        => __( 'Disabled', 'color-changer-for-elementor' ),
					'selectors'    => $s,
					'states'       => array( 'disabled' ),
					'properties'   => array( 'background-color', 'color' ),
				),
			),
		);
	}

	private static function widget_product_price() {
		return array(
			'label' => __( 'Product Price', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'price_regular',
					'label'      => __( 'Regular Price', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce div.product p.price', '.woocommerce div.product span.price', '.woocommerce ul.products li.product .price', '.price', '.woocommerce-Price-amount', '.wc-block-components-product-price' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'price_sale',
					'label'      => __( 'Sale Price', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce div.product p.price ins', '.woocommerce ul.products li.product .price ins', '.price ins' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_sale_badge() {
		return array(
			'label' => __( 'Sale Badge', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'badge_normal',
					'label'      => __( 'Sale Badge', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce span.onsale', '.wc-block-components-product-sale-badge' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
			),
		);
	}

	private static function widget_star_rating() {
		return array(
			'label' => __( 'Star Rating', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'stars_filled',
					'label'      => __( 'Filled Stars', 'color-changer-for-elementor' ),
					'selectors'  => array( '.star-rating', '.star-rating span', '.star-rating span::before', '.wc-block-components-product-rating__stars' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'stars_empty',
					'label'      => __( 'Empty Stars', 'color-changer-for-elementor' ),
					'selectors'  => array( '.star-rating::before', '.wc-block-components-product-rating__stars::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_product_tabs() {
		return array(
			'label' => __( 'Product Tabs', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'tab_normal',
					'label'      => __( 'Tab', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce div.product .woocommerce-tabs ul.tabs li a', '.wc-tabs li a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'      => 'tab_active',
					'derives_from' => 'tab_normal',
					'label'        => __( 'Active Tab', 'color-changer-for-elementor' ),
					'selectors'    => array( '.woocommerce div.product .woocommerce-tabs ul.tabs li.active a', '.wc-tabs li.active a' ),
					'states'       => array( 'normal' ),
					'properties'   => array( 'color' ),
				),
			),
		);
	}

	private static function widget_cart_table() {
		return array(
			'label' => __( 'Cart Table', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'table_header',
					'label'      => __( 'Column Headings', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce table.shop_table th', '.woocommerce table.cart th', '.wc-block-cart-items__header' ),
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
					'label'      => __( 'Table Rows', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce table.shop_table td', '.wc-block-cart-items' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'proceed_button',
					'label'      => __( 'Checkout Button', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce .wc-proceed-to-checkout a.checkout-button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'proceed_hover',
					'derives_from' => 'proceed_button',
					'label'        => __( 'Checkout Button (Hover)', 'color-changer-for-elementor' ),
					'selectors'    => array( '.woocommerce .wc-proceed-to-checkout a.checkout-button' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'    => 'coupon_input',
					'label'      => __( 'Coupon Field', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-cart-form .coupon .input-text', '.woocommerce form.checkout_coupon .input-text', '#coupon_code', '.wc-block-cart .wc-block-components-text-input input' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
				),
				array(
					'slot_id'    => 'update_cart',
					'label'      => __( 'Update Cart', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce button[name="update_cart"]' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'update_cart_hover',
					'derives_from' => 'update_cart',
					'label'        => __( 'Update Cart (Hover)', 'color-changer-for-elementor' ),
					'selectors'    => array( '.woocommerce button[name="update_cart"]' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
			),
		);
	}

	private static function widget_checkout() {
		return array(
			'label' => __( 'Checkout', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'place_order',
					'label'      => __( 'Place Order Button', 'color-changer-for-elementor' ),
					'selectors'  => array( '#place_order', '.woocommerce .checkout .button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'place_order_hover',
					'derives_from' => 'place_order',
					'label'        => __( 'Place Order (Hover)', 'color-changer-for-elementor' ),
					'selectors'    => array( '#place_order', '.woocommerce .checkout .button' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'    => 'input_text',
					'label'      => __( 'Input Fields', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce .input-text', '.woocommerce select', '.select2-selection--single', '.woocommerce .select2-container--default .select2-selection--single', '.wc-block-checkout .wc-block-components-text-input input' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
				),
				array(
					'slot_id'      => 'input_focus',
					'derives_from' => 'input_text',
					'label'        => __( 'Input Fields (Focus)', 'color-changer-for-elementor' ),
					'selectors'    => array( '.woocommerce .input-text', '.woocommerce select', '.select2-selection--single', '.woocommerce .select2-container--default .select2-selection--single' ),
					'states'       => array( 'focus' ),
					'properties'   => array( 'border-color' ),
				),
			),
		);
	}

	private static function widget_notices() {
		return array(
			'label' => __( 'Notices', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'success_border',
					'label'      => __( 'Success Border', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-message', '.wc-block-components-notice-banner.is-success' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
				),
				array(
					'slot_id'    => 'success_icon',
					'label'      => __( 'Success Icon', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-message::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'info_border',
					'label'      => __( 'Info Border', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-info', '.wc-block-components-notice-banner.is-info' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
				),
				array(
					'slot_id'    => 'info_icon',
					'label'      => __( 'Info Icon', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-info::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'    => 'error_border',
					'label'      => __( 'Error Border', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-error', '.wc-block-components-notice-banner.is-error' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
				),
				array(
					'slot_id'    => 'error_icon',
					'label'      => __( 'Error Icon', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-error::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_quantity_input() {
		return array(
			'label' => __( 'Quantity Input', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'qty_input',
					'label'      => __( 'Quantity Box', 'color-changer-for-elementor' ),
					'selectors'  => array( '.quantity .qty', 'input.qty', '.wc-block-components-quantity-selector input' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
				),
				array(
					'slot_id'      => 'qty_focus',
					'derives_from' => 'qty_input',
					'label'        => __( 'Quantity Box (Focus)', 'color-changer-for-elementor' ),
					'selectors'    => array( '.quantity .qty', 'input.qty' ),
					'states'       => array( 'focus' ),
					'properties'   => array( 'border-color' ),
				),
			),
		);
	}

	private static function widget_account() {
		return array(
			'label' => __( 'My Account', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'nav_link',
					'label'      => __( 'Menu Link', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-MyAccount-navigation-link a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'      => 'nav_active',
					'derives_from' => 'nav_link',
					'label'        => __( 'Active Menu Link', 'color-changer-for-elementor' ),
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
			'label'  => __( 'General Links', 'color-changer-for-elementor' ),
			// Emitted before the element-specific cards. Two rules of equal
			// specificity are decided by source order, and this card is the
			// broadest one here, so it must lose those ties rather than win
			// them. See Element_Registry::emission_order().
			'weight' => -10,
			'slots'  => array(
				array(
					'slot_id'    => 'link_normal',
					'label'      => __( 'Link', 'color-changer-for-elementor' ),
					'selectors'  => $link_selectors,
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
				array(
					'slot_id'      => 'link_hover',
					'derives_from' => 'link_normal',
					'label'        => __( 'Link (Hover)', 'color-changer-for-elementor' ),
					'selectors'    => $link_selectors,
					'states'       => array( 'hover' ),
					'properties'   => array( 'color' ),
				),
				array(
					'slot_id'    => 'body_text',
					'label'      => __( 'Body Text', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce-mini-cart__total', '.woocommerce-Reviews', '.woocommerce-review__author', '.woocommerce-product-details__short-description' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
				),
			),
		);
	}

	private static function widget_loop_buttons() {
		return array(
			'label' => __( 'Shop Page Buttons', 'color-changer-for-elementor' ),
			'slots' => array(
				array(
					'slot_id'    => 'loop_button',
					'label'      => __( 'Add to Cart', 'color-changer-for-elementor' ),
					'selectors'  => array( '.woocommerce ul.products li.product .button', 'a.add_to_cart_button', '.woocommerce .products .button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
				),
				array(
					'slot_id'      => 'loop_hover',
					'derives_from' => 'loop_button',
					'label'        => __( 'Add to Cart (Hover)', 'color-changer-for-elementor' ),
					'selectors'    => array( '.woocommerce ul.products li.product .button', 'a.add_to_cart_button', '.woocommerce .products .button' ),
					'states'       => array( 'hover' ),
					'properties'   => array( 'background-color', 'color' ),
				),
			),
		);
	}
}
