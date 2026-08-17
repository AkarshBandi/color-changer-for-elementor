<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Cache for generated CSS.
 *
 * Invalidation works by generation token, not by deletion. Every cache key
 * carries the current token, so replacing the token orphans every existing
 * entry at once and the next request rebuilds.
 *
 * This is not a stylistic preference. Entries are stored with set_transient(),
 * and when a persistent object cache is active — Redis or Memcached, normal on
 * managed WordPress hosting — transients are written to that cache and never
 * reach the options table. A `DELETE FROM wp_options ... LIKE` sweep would
 * match nothing on those hosts and silently do nothing. A token bump costs one
 * option write, behaves identically whichever backend is in use, and cannot
 * half-succeed. The row sweep is kept as housekeeping for sites without an
 * object cache, where orphaned rows would otherwise sit until their TTL.
 */
class Cache_Manager {

	/**
	 * Option holding the current generation token.
	 *
	 * Autoloaded: every front-end request that generates CSS reads it.
	 */
	const GENERATION_OPTION = 'eccw_css_generation';

	/**
	 * How long a cache entry survives if nothing invalidates it first.
	 *
	 * Orphaned entries are the normal outcome of invalidation rather than an
	 * exception, so on sites without an object cache the TTL is what bounds
	 * how long they linger in the options table. Rebuilding is string
	 * concatenation over an in-memory registry, so a shorter window costs
	 * very little.
	 */
	const DEFAULT_TTL = 7 * DAY_IN_SECONDS;

	/**
	 * Per-request memo, so repeated key building does not re-read the option.
	 *
	 * @var string|null
	 */
	private static $generation = null;

	/**
	 * Read a cached stylesheet.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value, or false when absent.
	 */
	public static function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Store a stylesheet.
	 *
	 * @param string $key Cache key.
	 * @param string $css Generated CSS.
	 * @param int|null $ttl Lifetime in seconds.
	 */
	public static function set( $key, $css, $ttl = null ) {
		if ( null === $ttl ) {
			$ttl = self::DEFAULT_TTL;
		}

		set_transient( $key, $css, $ttl );
	}

	/**
	 * Current generation token, seeding one if the site has none yet.
	 *
	 * @return string
	 */
	public static function generation() {
		if ( null !== self::$generation ) {
			return self::$generation;
		}

		$token = get_option( self::GENERATION_OPTION, '' );

		if ( ! is_string( $token ) || '' === $token ) {
			return self::new_generation();
		}

		self::$generation = $token;

		return self::$generation;
	}

	/**
	 * Invalidate every cached stylesheet.
	 *
	 * Leaves user drafts (live editor, preview) untouched.
	 */
	public static function clear_css() {
		self::new_generation();
		self::sweep_rows( '_transient_eccw_css_', '_transient_timeout_eccw_css_' );
		self::purge_page_caches();
	}

	/**
	 * Purge the page caches the site is actually running.
	 *
	 * The stylesheet file changes on every palette or typography edit, but the
	 * HTML that links to it can be cached for days by the browser, the edge,
	 * or a page-cache plugin. A stale page pointing at the previous hash is
	 * why an edit can look like it "did not stick" — the old file is kept
	 * (see CSS_Generator::write_stylesheet) so those pages keep styling, and
	 * purging here makes them pick up the new one promptly.
	 *
	 * Every call is guarded, so a cache that is not present is simply skipped.
	 */
	private static function purge_page_caches() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		if ( function_exists( 'pantheon_wp_clear_edge_paths' ) ) {
			pantheon_wp_clear_edge_paths( array( home_url( '/' ) ) );
		}

		if ( function_exists( 'wp_cache_clean_cache' ) ) {
			global $cache_path;
			wp_cache_clean_cache( 'all', isset( $cache_path ) ? $cache_path : '' );
		}

		// Not ours to prefix: this is LiteSpeed Cache's own documented purge
		// hook, fired the same way the function_exists() calls above reach into
		// WP Rocket, Pantheon and WP Super Cache. A prefixed name would simply
		// go unheard.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party hook, intentionally invoked by its own name.
		do_action( 'litespeed_purge_all' );

		/**
		 * Fires after known page caches have been purged.
		 *
		 * Lets add-ons purge anything this plugin does not know about.
		 */
		do_action( 'eccw_after_clear_css' );
	}

	/**
	 * Invalidate cached stylesheets and drop stored share previews.
	 *
	 * Share entries are read back by their exact key rather than through a
	 * generation token, so they can only be removed by deletion. Under an
	 * external object cache there is no row to delete and no bulk API to use,
	 * so they expire on their own TTL instead; that is a bounded wait, not a
	 * leak, and share links are short-lived by design.
	 */
	public static function clear_all() {
		self::clear_css();
		self::sweep_rows( '_transient_eccw_share_', '_transient_timeout_eccw_share_' );
	}

	/**
	 * Write a fresh generation token.
	 *
	 * A random token rather than an incrementing counter: an increment is a
	 * read-modify-write, so two concurrent invalidations can both read the same
	 * value and one bump is lost — which would reintroduce exactly the stale
	 * serve this class exists to prevent. Two concurrent random writes both
	 * invalidate, whichever lands last.
	 *
	 * @return string The new token.
	 */
	private static function new_generation() {
		$token = wp_generate_password( 12, false );

		update_option( self::GENERATION_OPTION, $token, true );

		self::$generation = $token;

		return $token;
	}

	/**
	 * Delete transient rows for one key prefix.
	 *
	 * Housekeeping only — correctness comes from the generation token. Skipped
	 * entirely when an external object cache is in use, because the rows do not
	 * exist there and the query would be pure overhead.
	 *
	 * @param string $prefix         Transient option prefix.
	 * @param string $timeout_prefix Matching timeout option prefix.
	 */
	private static function sweep_rows( $prefix, $timeout_prefix ) {
		if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
			return;
		}

		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return;
		}

		// Intentional: bulk-delete plugin transients; WP has no bulk API.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%',
				$wpdb->esc_like( $timeout_prefix ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Clear the per-request memo.
	 *
	 * Needed by the test suite, and by any code that changes the token behind
	 * this class's back.
	 */
	public static function flush() {
		self::$generation = null;
	}
}
