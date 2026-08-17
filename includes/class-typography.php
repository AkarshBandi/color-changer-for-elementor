<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * The second design token.
 *
 * An Elementor kit defines global typography in exactly the same shape as its
 * global colours — `system_typography` alongside `system_colors`, same `_id`
 * keys, same storage. WooCommerce ignores both. This class maps the kit's
 * typography tokens onto WooCommerce selector groups.
 *
 * Deliberately conservative about what it touches. Font family and weight
 * carry brand; sizes and line heights carry layout, and overriding those is how
 * you break a checkout page. Sizes are read but not emitted.
 */
class Typography {

	/**
	 * Which kit typography token each group of selectors follows.
	 *
	 * Kit tokens are semantic — primary is the H1 style, secondary the H2, text
	 * the body, accent the highlight — so the mapping is a design decision, not
	 * a lookup. Product titles take the heading font because that is what they
	 * are; buttons take the kit's dedicated button typography when the kit
	 * defines one (accent is the fallback); everything else follows body. The
	 * kit's dedicated h1 and link typography settings are followed directly.
	 *
	 * @return array Token id => selectors.
	 */
	public static function groups() {
		return apply_filters(
			'eccw_typography_groups',
			array(
				'secondary'   => array(
					'.woocommerce ul.products li.product .woocommerce-loop-product__title',
					'.woocommerce div.product .product_title',
				),
				// No 'accent' group. It used to list seven button selectors,
				// six of which the 'button' group below also lists — so two
				// rules were emitted for the same selector with different
				// fonts, and which one won was decided by the order of this
				// array rather than by intent. On a kit whose Accent is
				// Futura 700 and whose Button tab is Inter 400, every
				// WooCommerce button silently got Inter.
				//
				// Elementor's Button tab is the authoritative source for how
				// a button reads, so buttons belong to 'button' alone. The
				// one selector 'accent' owned outright moved there.
				'text'        => array(
					'.woocommerce div.product p.price',
					'.woocommerce ul.products li.product .price',
					'.woocommerce table.shop_table th',
					'.woocommerce-MyAccount-navigation a',
					'.woocommerce-mini-cart__total',
					'.woocommerce-Reviews',
					'.woocommerce-review__author',
					'.woocommerce-product-details__short-description',
				),
				'h1'          => array(
					'.woocommerce-products-header__title',
				),
				'button'      => array(
					'.woocommerce a.button.alt',
					'.woocommerce button.button.alt',
					'.single_add_to_cart_button',
					'.woocommerce .wc-proceed-to-checkout a.checkout-button',
					'.woocommerce button[name="apply_coupon"]',
					'#place_order',
					'.woocommerce .checkout .button',
					'.woocommerce button[name="update_cart"]',
					'.woocommerce ul.products li.product .button',
					'a.add_to_cart_button',
					'.woocommerce .products .button',
					'.wc-block-components-button:not(.is-link)',
					'.woocommerce-form-login__submit',
					'.woocommerce-mini-cart__buttons a',
				),
				// Scoped to store content links, for the same reason the colour
				// registry's `wc-general-links` slot is: `.woocommerce` sits on
				// `<body>` on every shop, product, cart and account page, so
				// `.woocommerce a` is every anchor in the document. Here that
				// meant the theme's own header and footer navigation were being
				// given the store's link font and weight.
				//
				// Kept in step with Element_Registry::widget_general_links().
				'link_normal' => array(
					'.woocommerce-breadcrumb a',
					'.woocommerce ul.products li.product a.woocommerce-loop-product__link',
					'.woocommerce ul.products li.product-category a',
					'.woocommerce-Tabs-panel a',
					'.product_meta a',
					'.woocommerce-pagination a',
				),
				'link_hover'  => array(
					'.woocommerce-breadcrumb a:hover',
					'.woocommerce ul.products li.product a.woocommerce-loop-product__link:hover',
					'.woocommerce ul.products li.product-category a:hover',
					'.woocommerce-Tabs-panel a:hover',
					'.product_meta a:hover',
					'.woocommerce-pagination a:hover',
				),
			)
		);
	}

	/**
	 * Typography tokens from the active Elementor kit.
	 *
	 * Mirrors CSS_Generator::read_kit_colors(); the kit stores both under the
	 * same post meta.
	 *
	 * @return array Token id => array( 'family' => string, 'weight' => string ).
	 */
	public static function kit_typography() {
		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return self::legacy_scheme_typography();
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $settings ) ) {
			return self::legacy_scheme_typography();
		}

		$system = isset( $settings['system_typography'] ) ? $settings['system_typography'] : array();
		$custom = isset( $settings['custom_typography'] ) ? $settings['custom_typography'] : array();

		$tokens = array();

		foreach ( array_merge( (array) $system, (array) $custom ) as $entry ) {
			if ( empty( $entry['_id'] ) ) {
				continue;
			}

			$family = isset( $entry['typography_font_family'] ) ? self::sanitize_family( $entry['typography_font_family'] ) : '';
			$weight = isset( $entry['typography_font_weight'] ) ? self::sanitize_weight( $entry['typography_font_weight'] ) : '';

			if ( '' === $family && '' === $weight ) {
				continue;
			}

			$tokens[ $entry['_id'] ] = array(
				'family' => $family,
				'weight' => $weight,
			);
		}

		// The kit's dedicated global typography settings (Button, Links,
		// Headings, Body tabs) are tokens too, so WC groups can follow them.
		foreach (
			array(
				'button'      => 'button_typography',
				'link_normal' => 'link_normal_typography',
				'link_hover'  => 'link_hover_typography',
				'body'        => 'body_typography',
				'h1'          => 'h1_typography',
				'h2'          => 'h2_typography',
				'h3'          => 'h3_typography',
				'h4'          => 'h4_typography',
				'h5'          => 'h5_typography',
				'h6'          => 'h6_typography',
			) as $token => $prefix
		) {
			$family = isset( $settings[ $prefix . '_font_family' ] ) ? self::sanitize_family( $settings[ $prefix . '_font_family' ] ) : '';
			$weight = isset( $settings[ $prefix . '_font_weight' ] ) ? self::sanitize_weight( $settings[ $prefix . '_font_weight' ] ) : '';

			if ( '' === $family && '' === $weight ) {
				continue;
			}

			$tokens[ $token ] = array(
				'family' => $family,
				'weight' => $weight,
			);
		}

		/**
		 * Filters the typography tokens available for mapping.
		 *
		 * @param array $tokens Token id => family/weight.
		 */
		return (array) apply_filters( 'eccw_kit_typography', $tokens );
	}

	/**
	 * Elementor < 3.0 kept global typography in the scheme options rather
	 * than a Kit. Mirrors legacy_scheme_colors() so unmigrated sites still
	 * get font tokens.
	 *
	 * @return array Token id => family/weight.
	 */
	private static function legacy_scheme_typography() {
		$scheme = get_option( 'elementor_scheme_typography' );

		if ( ! is_array( $scheme ) ) {
			return array();
		}

		// 1 primary, 2 secondary, 3 text, 4 accent, 6 button.
		$map    = array(
			1 => 'primary',
			2 => 'secondary',
			3 => 'text',
			4 => 'accent',
			6 => 'button',
		);
		$tokens = array();

		foreach ( $map as $index => $token ) {
			if ( empty( $scheme[ $index ] ) || ! is_array( $scheme[ $index ] ) ) {
				continue;
			}

			$family = isset( $scheme[ $index ]['font_family'] ) ? self::sanitize_family( $scheme[ $index ]['font_family'] ) : '';
			$weight = isset( $scheme[ $index ]['font_weight'] ) ? self::sanitize_weight( $scheme[ $index ]['font_weight'] ) : '';

			if ( '' === $family && '' === $weight ) {
				continue;
			}

			$tokens[ $token ] = array(
				'family' => $family,
				'weight' => $weight,
			);
		}

		return $tokens;
	}

	/**
	 * Generated typography rules.
	 *
	 * @return string CSS, or '' when the kit defines no usable typography.
	 */
	public static function css() {
		if ( ! Settings::get( 'apply_typography' ) ) {
			return '';
		}

		$tokens = self::kit_typography();

		if ( empty( $tokens ) ) {
			return '';
		}

		// Typography always wins the cascade. The whole point of this feature
		// is to override the theme's font, so it is not governed by
		// force_important — that setting exists to let site CSS win over
		// colour declarations, and a half-applied font is a flash of the old
		// one, not a design choice.
		$force = ' !important';

		// One selector, one rule. Groups are filterable, so an add-on or a
		// future edit can put the same selector in two of them; emitting both
		// leaves the winner to source order, which is not a decision anybody
		// made. Resolving to a map keyed by selector makes the last group to
		// claim a selector win explicitly, and emits it once.
		$by_selector = array();

		foreach ( self::groups() as $token_id => $selectors ) {
			if ( empty( $tokens[ $token_id ] ) || empty( $selectors ) ) {
				continue;
			}

			$rules = array();

			if ( '' !== $tokens[ $token_id ]['family'] ) {
				$rules[] = 'font-family: ' . $tokens[ $token_id ]['family'] . $force;
			}

			if ( '' !== $tokens[ $token_id ]['weight'] ) {
				$rules[] = 'font-weight: ' . $tokens[ $token_id ]['weight'] . $force;
			}

			if ( empty( $rules ) ) {
				continue;
			}

			foreach ( (array) $selectors as $selector ) {
				$by_selector[ $selector ] = $rules;
			}
		}

		$output = '';

		foreach ( $by_selector as $selector => $rules ) {
			$output .= $selector . ' { ' . implode( '; ', $rules ) . '; }' . "\n";
		}

		return $output;
	}

	/**
	 * Accept a font family name, or nothing.
	 *
	 * Same reasoning as the colour allowlist: this string is interpolated into
	 * a stylesheet, and there is no correct escaping for arbitrary text inside
	 * a CSS value. Family names are letters, digits, spaces and hyphens; the
	 * result is quoted so a multi-word name stays one token.
	 *
	 * @param mixed $value Candidate family.
	 * @return string Quoted family, or ''.
	 */
	public static function sanitize_family( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		if ( '' === $value || strlen( $value ) > 64 ) {
			return '';
		}

		if ( ! preg_match( '/^[A-Za-z0-9 _-]+$/', $value ) ) {
			return '';
		}

		return '"' . $value . '"';
	}

	/**
	 * Accept a font weight, or nothing.
	 *
	 * @param mixed $value Candidate weight.
	 * @return string Weight, or ''.
	 */
	public static function sanitize_weight( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( in_array( $value, array( 'normal', 'bold', 'lighter', 'bolder' ), true ) ) {
			return $value;
		}

		if ( preg_match( '/^[1-9]00$/', $value ) ) {
			return $value;
		}

		return '';
	}
}
