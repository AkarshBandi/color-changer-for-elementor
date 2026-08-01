<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Element_Registry {

	public static function get_registry() {
		return apply_filters(
			'wooce_element_registry',
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
	}

	/**
	 * Map third-party addon widget types to registry keys.
	 *
	 * Elementor (free) does not register real WooCommerce widgets; sites
	 * build them with addons such as Essential Addons or Premium Addons.
	 * This lets discovery/normalization recognize those widget types and
	 * route them to the matching registry element.
	 *
	 * @return array
	 */
	public static function get_addon_map() {
		return apply_filters(
			'wooce_addon_widget_map',
			array(
				'eael-woo-add-to-cart'    => 'wc-add-to-cart',
				'eael-woo-product-price'  => 'wc-product-price',
				'eael-woo-product-rating' => 'wc-star-rating',
				'eael-woo-product-tabs'   => 'wc-product-tabs',
				'eael-woo-cart'           => 'wc-cart-table',
				'eael-woo-checkout'       => 'wc-checkout',
				'eicon-woocommerce'       => 'wc-loop-buttons',
				'premium-woo-products'    => 'wc-loop-buttons',
				'premium-woo-cta'         => 'wc-add-to-cart',
				'premium-mini-cart'       => 'wc-cart-table',
				'premium-woo-categories'  => 'wc-general-links',
			)
		);
	}

	/**
	 * Whether a widget type string belongs to the WooCommerce element family.
	 *
	 * Matches native Elementor widget types (woocommerce-*, wc-*) plus
	 * addon widget types from Essential Addons (eael-woo-*, eicon-woocommerce)
	 * and Premium Addons (premium-woo-*).
	 *
	 * @param string $widget_type
	 * @return bool
	 */
	public static function is_woocommerce_widget_type( $widget_type ) {
		if ( ! is_string( $widget_type ) || '' === $widget_type ) {
			return false;
		}

		return (bool) preg_match( '/woocommerce|^wc-|^eael-woo-|^premium-woo-|^eicon-woocommerce/', $widget_type );
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
		$s = array( '.single_add_to_cart_button', '.woocommerce .button.alt', '.woocommerce button.button.alt', '.woocommerce a.button.alt', '#respond input#submit.alt', '.woocommerce .wc-block-components-button:not(.is-link)' );
		return array(
			'label' => __( 'Add to Cart Button', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'button_normal',
					'label'      => __( 'Button', 'woocommerce-elementor-colors' ),
					'selectors'  => $s,
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color', 'fill' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'button_hover',
					'label'      => __( 'Button Hover', 'woocommerce-elementor-colors' ),
					'selectors'  => $s,
					'states'     => array( 'hover' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'button_focus',
					'label'      => __( 'Button Focus', 'woocommerce-elementor-colors' ),
					'selectors'  => $s,
					'states'     => array( 'focus' ),
					'properties' => array( 'background-color', 'color', 'border-color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'button_disabled',
					'label'      => __( 'Button Disabled', 'woocommerce-elementor-colors' ),
					'selectors'  => $s,
					'states'     => array( 'disabled' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_product_price() {
		return array(
			'label' => __( 'Product Price', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'price_regular',
					'label'      => __( 'Regular Price', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce div.product p.price', '.woocommerce div.product span.price', '.woocommerce ul.products li.product .price', '.price', '.woocommerce-Price-amount', '.wc-block-components-product-price' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'price_sale',
					'label'      => __( 'Sale Price', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce div.product p.price ins', '.woocommerce ul.products li.product .price ins', '.price ins' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_sale_badge() {
		return array(
			'label' => __( 'Sale Badge', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'badge_normal',
					'label'      => __( 'Badge', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce span.onsale', '.wc-block-components-product-sale-badge' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_star_rating() {
		return array(
			'label' => __( 'Star Rating', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'stars_filled',
					'label'      => __( 'Filled Stars', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.star-rating', '.star-rating span', '.wc-block-components-product-rating__stars' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'stars_empty',
					'label'      => __( 'Empty Stars', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.star-rating::before', '.wc-block-components-product-rating__stars::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_product_tabs() {
		return array(
			'label' => __( 'Product Tabs', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'tab_normal',
					'label'      => __( 'Tab Link', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce div.product .woocommerce-tabs ul.tabs li a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'is_product' ),
				),
				array(
					'slot_id'    => 'tab_active',
					'label'      => __( 'Active Tab', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce div.product .woocommerce-tabs ul.tabs li.active a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'is_product' ),
				),
			),
		);
	}

	private static function widget_cart_table() {
		return array(
			'label' => __( 'Cart Table', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'table_header',
					'label'      => __( 'Table Header', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce table.shop_table th', '.woocommerce table.cart th' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'is_cart' ),
				),
				array(
					'slot_id'    => 'table_cell',
					'label'      => __( 'Table Cell / Border', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce table.shop_table td', '.woocommerce table.shop_table' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
					'page_types' => array( 'is_cart' ),
				),
				array(
					'slot_id'    => 'proceed_button',
					'label'      => __( 'Proceed to Checkout', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce .wc-proceed-to-checkout a.checkout-button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_cart' ),
				),
				array(
					'slot_id'    => 'proceed_hover',
					'label'      => __( 'Proceed Hover', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce .wc-proceed-to-checkout a.checkout-button' ),
					'states'     => array( 'hover' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_cart' ),
				),
				array(
					'slot_id'    => 'coupon_input',
					'label'      => __( 'Coupon Input', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce .cart-collaterals .coupon .input-text', '.woocommerce form.checkout_coupon .input-text' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
					'page_types' => array( 'is_cart' ),
				),
				array(
					'slot_id'    => 'update_cart',
					'label'      => __( 'Update Cart Button', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce button[name="update_cart"]' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_cart' ),
				),
				array(
					'slot_id'    => 'update_cart_hover',
					'label'      => __( 'Update Cart Hover', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce button[name="update_cart"]' ),
					'states'     => array( 'hover' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_cart' ),
				),
			),
		);
	}

	private static function widget_checkout() {
		return array(
			'label' => __( 'Checkout', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'place_order',
					'label'      => __( 'Place Order Button', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '#place_order', '.woocommerce .checkout .button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_checkout' ),
				),
				array(
					'slot_id'    => 'place_order_hover',
					'label'      => __( 'Place Order Hover', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '#place_order', '.woocommerce .checkout .button' ),
					'states'     => array( 'hover' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_checkout' ),
				),
				array(
					'slot_id'    => 'input_text',
					'label'      => __( 'Input Fields', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce .input-text', '.woocommerce select', '.select2-selection--single', '.woocommerce .select2-container--default .select2-selection--single' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
					'page_types' => array( 'is_checkout' ),
				),
				array(
					'slot_id'    => 'input_focus',
					'label'      => __( 'Input Focus', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce .input-text', '.woocommerce select', '.select2-selection--single', '.woocommerce .select2-container--default .select2-selection--single' ),
					'states'     => array( 'focus' ),
					'properties' => array( 'border-color' ),
					'page_types' => array( 'is_checkout' ),
				),
			),
		);
	}

	private static function widget_notices() {
		return array(
			'label' => __( 'Notices', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'success_border',
					'label'      => __( 'Success Border', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-message' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'success_icon',
					'label'      => __( 'Success Icon', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-message::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'info_border',
					'label'      => __( 'Info Border', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-info' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'info_icon',
					'label'      => __( 'Info Icon', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-info::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'error_border',
					'label'      => __( 'Error Border', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-error' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'border-top-color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'error_icon',
					'label'      => __( 'Error Icon', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-error::before' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_quantity_input() {
		return array(
			'label' => __( 'Quantity Input', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'qty_input',
					'label'      => __( 'Input', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.quantity .qty', 'input.qty' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color', 'border-color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'qty_focus',
					'label'      => __( 'Input Focus', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.quantity .qty', 'input.qty' ),
					'states'     => array( 'focus' ),
					'properties' => array( 'border-color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_account() {
		return array(
			'label' => __( 'My Account', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'nav_link',
					'label'      => __( 'Navigation Link', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-MyAccount-navigation-link a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'is_account_page' ),
				),
				array(
					'slot_id'    => 'nav_active',
					'label'      => __( 'Active Navigation', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce-MyAccount-navigation-link.is-active a' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'is_account_page' ),
				),
			),
		);
	}

	private static function widget_general_links() {
		return array(
			'label' => __( 'General Links', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'link_normal',
					'label'      => __( 'Link', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce a:not(.elementor-button)' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
				array(
					'slot_id'    => 'link_hover',
					'label'      => __( 'Link Hover', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce a:not(.elementor-button)' ),
					'states'     => array( 'hover' ),
					'properties' => array( 'color' ),
					'page_types' => array( 'all' ),
				),
			),
		);
	}

	private static function widget_loop_buttons() {
		return array(
			'label' => __( 'Shop Loop Buttons', 'woocommerce-elementor-colors' ),
			'slots' => array(
				array(
					'slot_id'    => 'loop_button',
					'label'      => __( 'Loop Button', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce ul.products li.product .button', 'a.add_to_cart_button', '.woocommerce .products .button' ),
					'states'     => array( 'normal' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_shop', 'is_product_category', 'is_product_tag' ),
				),
				array(
					'slot_id'    => 'loop_hover',
					'label'      => __( 'Loop Button Hover', 'woocommerce-elementor-colors' ),
					'selectors'  => array( '.woocommerce ul.products li.product .button', 'a.add_to_cart_button', '.woocommerce .products .button' ),
					'states'     => array( 'hover' ),
					'properties' => array( 'background-color', 'color' ),
					'page_types' => array( 'is_shop', 'is_product_category', 'is_product_tag' ),
				),
			),
		);
	}
}
