<?php
/**
 * Test bootstrap.
 *
 * These are unit tests, not integration tests: they load the plugin's classes
 * against a deliberately small set of WordPress stubs rather than booting
 * WordPress. That choice is honest about what it can and cannot cover.
 *
 * It CAN cover the logic that has actually broken in this plugin's history —
 * widget-type recognition, selector-map collisions, colour maths, colour
 * sanitisation, label construction. All of that is pure computation over
 * arrays and strings, and all of it shipped broken at least once.
 *
 * It CANNOT cover anything that depends on a real query, a real request or a
 * real database: page-type detection, enqueueing, or the option round-trip.
 * Two of the 2.1.x defects lived precisely there and were only ever going to
 * be found by fetching real URLs from a real store. See docs/SPECIFICATION.html
 * §12. Do not let a green suite here be mistaken for that.
 *
 * @package Color_Changer_For_Elementor
 */

/*
 * This file declares WordPress functions in the global namespace on purpose:
 * that is what a stub is. It is excluded from the distributed plugin by
 * .distignore and never loads at runtime — but Plugin Check scans an installed
 * directory rather than a built zip, so the sniffs are disabled here too, to
 * stop a real finding being buried under thirty false ones.
 *
 * Each one is disabled because the thing it objects to is the point:
 *
 * - PrefixAllGlobals: the stubs must carry WordPress's own names, or nothing
 *   under test would call them.
 * - AlternativeFunctions.json_encode_json_encode: this *is* the stub of
 *   wp_json_encode(); it cannot call itself.
 * - VariableAnalysis / unused parameters: the signatures mirror WordPress's, so
 *   a caller passing four arguments does not fail. Trimming them to what the
 *   tests happen to use would make the stubs lie about the real API.
 * - Reserved keyword "list": wp_list_pluck()'s own parameter name.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Test stubs must use WordPress's names.
// phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode -- This is the stub of wp_json_encode().
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter -- Signatures mirror WordPress's real ones.
// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames -- Mirrors wp_list_pluck()'s parameter name.

/*
 * Direct-access protection, in the only form that makes sense here.
 *
 * The usual `if ( ! defined( 'ABSPATH' ) ) exit;` guard is meaningless in this
 * file: it is the file that *defines* ABSPATH. Refusing to run outside the CLI
 * is the equivalent protection for a test bootstrap, and a stronger one — the
 * file cannot be reached over HTTP at all, guard constant or no guard constant.
 */
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );

/*
 * Canonical guard pattern, kept for WordPress.org's Plugin Check scanner.
 * ABSPATH is already defined above, so this never fires at runtime; the
 * scanner recognises the exact `if ( ! defined( 'ABSPATH' ) ) exit;` shape
 * and would otherwise flag this file for missing direct-access protection.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ECCW_VERSION', '2.1.4' );
define( 'ECCW_PATH', dirname( __DIR__ ) . '/' );
define( 'ECCW_URL', 'https://example.test/wp-content/plugins/color-changer-for-elementor/' );
define( 'DAY_IN_SECONDS', 86400 );

require_once __DIR__ . '/class-eccw-test-store.php';

/* ------------------------------------------------------------------ *
 * Hooks. apply_filters really runs registered callbacks, because the
 * filter seams are part of the public contract being tested.
 * ------------------------------------------------------------------ */

function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	ECCW_Test_Store::$filters[ $hook ][ $priority ][] = $callback;
	ksort( ECCW_Test_Store::$filters[ $hook ] );

	return true;
}

function remove_all_filters( $hook ) {
	unset( ECCW_Test_Store::$filters[ $hook ] );

	return true;
}

function has_filter( $hook, $callback = false ) {
	return ! empty( ECCW_Test_Store::$filters[ $hook ] );
}

function apply_filters( $hook, $value ) {
	$extra = array_slice( func_get_args(), 2 );

	if ( empty( ECCW_Test_Store::$filters[ $hook ] ) ) {
		return $value;
	}

	foreach ( ECCW_Test_Store::$filters[ $hook ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$value = call_user_func_array( $callback, array_merge( array( $value ), $extra ) );
		}
	}

	return $value;
}

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	return true;
}

function do_action( $hook ) {
	return null;
}

/* ------------------------------------------------------------------ *
 * Options and post meta.
 * ------------------------------------------------------------------ */

function get_option( $name, $default = false ) {
	return array_key_exists( $name, ECCW_Test_Store::$options )
		? ECCW_Test_Store::$options[ $name ]
		: $default;
}

function update_option( $name, $value, $autoload = null ) {
	ECCW_Test_Store::$options[ $name ] = $value;

	return true;
}

function add_option( $name, $value, $deprecated = '', $autoload = 'yes' ) {
	return update_option( $name, $value );
}

function delete_option( $name ) {
	unset( ECCW_Test_Store::$options[ $name ] );

	return true;
}

function get_post_meta( $post_id, $key, $single = false ) {
	$value = ECCW_Test_Store::$meta[ $post_id ][ $key ] ?? ( $single ? '' : array() );

	return $value;
}

function get_transient( $key ) {
	return false;
}

function set_transient( $key, $value, $ttl = 0 ) {
	return true;
}

function delete_transient( $key ) {
	return true;
}

function wp_using_ext_object_cache() {
	return false;
}

/* ------------------------------------------------------------------ *
 * Strings, escaping, i18n. Translation is identity; the tests assert on
 * the English source strings, which is what a default install renders.
 * ------------------------------------------------------------------ */

function __( $text, $domain = null ) {
	return $text;
}

function _x( $text, $context, $domain = null ) {
	return $text;
}

function esc_html__( $text, $domain = null ) {
	return $text;
}

function esc_attr__( $text, $domain = null ) {
	return $text;
}

function _n( $single, $plural, $number, $domain = null ) {
	return 1 === (int) $number ? $single : $plural;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return $url;
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_json_encode( $value, $flags = 0 ) {
	return json_encode( $value, $flags );
}

function trailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' ) . '/';
}

function wp_list_pluck( $list, $field ) {
	return array_map(
		static function ( $item ) use ( $field ) {
			return is_array( $item ) ? ( $item[ $field ] ?? null ) : ( $item->$field ?? null );
		},
		$list
	);
}

function number_format_i18n( $number, $decimals = 0 ) {
	return number_format( (float) $number, $decimals );
}

/**
 * Minimal has_shortcode.
 *
 * Faithful enough for the cases under test: it must match a bare tag and a tag
 * carrying attributes, and must not match a different tag that merely starts
 * with the same letters.
 */
function has_shortcode( $content, $tag ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return false;
	}

	return (bool) preg_match( '/\[' . preg_quote( $tag, '/' ) . '(?=[\s\]\/])/', $content );
}

function get_the_title( $post_id = 0 ) {
	return ECCW_Test_Store::$meta[ $post_id ]['__title'] ?? '';
}

function current_time( $type = 'mysql' ) {
	return '2026-08-17 00:00:00';
}

function wp_generate_password( $length = 12, $special = true ) {
	return substr( str_repeat( 'abcdef0123456789', 4 ), 0, $length );
}

/* ------------------------------------------------------------------ *
 * Load the classes under test. The autoloader is the plugin's own, so a
 * class that cannot be found here also cannot be found in production.
 * ------------------------------------------------------------------ */

require_once ECCW_PATH . 'includes/class-autoloader.php';
