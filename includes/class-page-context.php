<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Page_Context {

	public static function get_current_page_type() {
		if ( is_product() ) {
			return 'is_product';
		}

		if ( is_cart() ) {
			return 'is_cart';
		}

		if ( is_checkout() ) {
			return 'is_checkout';
		}

		if ( is_account_page() ) {
			return 'is_account_page';
		}

		if ( is_shop() ) {
			return 'is_shop';
		}

		if ( is_product_category() ) {
			return 'is_product_category';
		}

		if ( is_product_tag() ) {
			return 'is_product_tag';
		}

		if ( is_woocommerce() ) {
			return 'is_woocommerce';
		}

		return 'generic';
	}

	public static function is_woocommerce_page() {
		return is_product()
			|| is_cart()
			|| is_checkout()
			|| is_account_page()
			|| is_shop()
			|| is_product_category()
			|| is_product_tag()
			|| is_woocommerce();
	}
}
