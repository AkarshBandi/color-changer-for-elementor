<?php

use PHPUnit\Framework\TestCase;
use WooElementorColors\Page_Context;

/**
 * Tests for Page_Context.
 */
class PageContextTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WP_Shims::reset();
	}

	public function test_generic_page_type() {
		$this->assertSame( 'generic', Page_Context::get_current_page_type() );
		$this->assertFalse( Page_Context::is_woocommerce_page() );
	}

	public function test_product_page() {
		WP_Shims::set_page_flag( 'is_product', true );
		$this->assertSame( 'is_product', Page_Context::get_current_page_type() );
		$this->assertTrue( Page_Context::is_woocommerce_page() );
	}

	public function test_cart_page() {
		WP_Shims::set_page_flag( 'is_cart', true );
		$this->assertSame( 'is_cart', Page_Context::get_current_page_type() );
		$this->assertTrue( Page_Context::is_woocommerce_page() );
	}

	public function test_checkout_page() {
		WP_Shims::set_page_flag( 'is_checkout', true );
		$this->assertSame( 'is_checkout', Page_Context::get_current_page_type() );
	}

	public function test_account_page() {
		WP_Shims::set_page_flag( 'is_account_page', true );
		$this->assertSame( 'is_account_page', Page_Context::get_current_page_type() );
	}

	public function test_shop_page() {
		WP_Shims::set_page_flag( 'is_shop', true );
		$this->assertSame( 'is_shop', Page_Context::get_current_page_type() );
	}

	public function test_product_category() {
		WP_Shims::set_page_flag( 'is_product_category', true );
		$this->assertSame( 'is_product_category', Page_Context::get_current_page_type() );
	}

	public function test_product_tag() {
		WP_Shims::set_page_flag( 'is_product_tag', true );
		$this->assertSame( 'is_product_tag', Page_Context::get_current_page_type() );
	}

	public function test_woocommerce_archive() {
		WP_Shims::set_page_flag( 'is_woocommerce', true );
		$this->assertSame( 'is_woocommerce', Page_Context::get_current_page_type() );
		$this->assertTrue( Page_Context::is_woocommerce_page() );
	}

	public function test_precedence_product_over_woocommerce() {
		WP_Shims::set_page_flag( 'is_woocommerce', true );
		WP_Shims::set_page_flag( 'is_product', true );
		$this->assertSame( 'is_product', Page_Context::get_current_page_type() );
	}
}
