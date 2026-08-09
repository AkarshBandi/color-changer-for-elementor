<?php
/**
 * PHPUnit bootstrap for logic-core tests.
 *
 * Loads the plugin classes in isolation and provides minimal WordPress
 * function shims so the pure logic (registry, mapping, heuristics, page
 * context, CSS generation) can be tested without a WordPress install.
 *
 * This file intentionally defines ABSPATH itself (it is a test bootstrap,
 * not a WordPress-loaded file), so the direct-access guard is not applicable.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
define( 'ECCw_PATH', dirname( __DIR__ ) . '/' );

define( 'ECCw_VERSION', 'test' );
define( 'ECCw_URL', 'http://example.test/plugins/color-changer-for-elementor/' );

require ECCw_PATH . 'includes/class-autoloader.php';
require __DIR__ . '/class-wp-shims.php';

// -- WordPress function shims ----------------------------------------------
// These intentionally mimic WordPress core functions so the pure logic can be
// tested without a WordPress install. They are not plugin globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, WordPress.WP.AlternativeFunctions.strip_tags_strip_tags

function get_option( $option, $default = false ) {
	return array_key_exists( $option, WP_Shims::$options ) ? WP_Shims::$options[ $option ] : $default;
}

function update_option( $option, $value, $autoload = null ) {
	WP_Shims::$options[ $option ] = $value;
	return true;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	if ( isset( WP_Shims::$post_meta[ $post_id ][ $key ] ) ) {
		$value = WP_Shims::$post_meta[ $post_id ][ $key ];
		return $single ? $value : array( $value );
	}
	return $single ? '' : array();
}

function current_time( $type ) {
	return WP_Shims::$current_time;
}

function get_transient( $key ) {
	return array_key_exists( $key, WP_Shims::$transients ) ? WP_Shims::$transients[ $key ] : false;
}

function set_transient( $key, $value, $expiration = 0 ) {
	WP_Shims::$transients[ $key ] = $value;
	return true;
}

function delete_transient( $key ) {
	unset( WP_Shims::$transients[ $key ] );
	return true;
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return true;
}

function apply_filters( $tag, $value, ...$args ) {
	return isset( WP_Shims::$filters[ $tag ] ) ? WP_Shims::$filters[ $tag ] : $value;
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function sanitize_key( $key ) {
	$key = strtolower( $key );
	$key = preg_replace( '/[^a-z0-9_\-]/', '', $key );
	return $key;
}

function sanitize_text_field( $str ) {
	$str = wp_strip_all_tags( $str );
	$str = trim( $str );
	return $str;
}

function wp_strip_all_tags( $string, $remove_breaks = false ) {
	$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
	$string = strip_tags( $string );
	return $string;
}

function is_product() {
	return ! empty( WP_Shims::$page_flags['is_product'] ); }
function is_cart() {
	return ! empty( WP_Shims::$page_flags['is_cart'] ); }
function is_admin() {
	return ! empty( WP_Shims::$page_flags['is_admin'] ); }
function wp_dequeue_style( $handle ) {
	WP_Shims::$dequeued[] = $handle;
	return true;
}
function wp_deregister_style( $handle ) {
	WP_Shims::$dequeued[] = $handle;
	return true;
}
function is_checkout() {
	return ! empty( WP_Shims::$page_flags['is_checkout'] ); }
function is_account_page() {
	return ! empty( WP_Shims::$page_flags['is_account_page'] ); }
function is_shop() {
	return ! empty( WP_Shims::$page_flags['is_shop'] ); }
function is_product_category() {
	return ! empty( WP_Shims::$page_flags['is_product_category'] ); }
function is_product_tag() {
	return ! empty( WP_Shims::$page_flags['is_product_tag'] ); }
function is_woocommerce() {
	return ! empty( WP_Shims::$page_flags['is_woocommerce'] ); }

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

// No plugin-specific polyfills are needed beyond this point.
