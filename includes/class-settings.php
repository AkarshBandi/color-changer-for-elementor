<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Typed plugin settings.
 *
 * Behaviour flags used to be read ad hoc with `get_option()` and an inline
 * default at each call site, which meant the same setting could disagree with
 * itself in two files. Everything now resolves through here, with one set of
 * defaults, one sanitizer, and a signature the CSS cache can key on.
 */
class Settings {

	const OPTION = 'eccw_settings';

	/**
	 * Legacy option this class absorbed.
	 */
	const LEGACY_DEQUEUE_OPTION = 'eccw_dequeue_settings';

	/**
	 * Per-request memo.
	 *
	 * @var array|null
	 */
	private static $memo = null;

	/**
	 * Setting keys, their defaults, and how they are cast.
	 *
	 * @return array<string,bool>
	 */
	public static function defaults() {
		return array(
			// Master switch. When off the plugin stops touching the front end
			// entirely but keeps every saved colour, so it is a safe thing to
			// reach for when a store looks wrong.
			'enabled'          => true,

			// Append !important to generated declarations. On by default
			// because WooCommerce and most themes ship very specific
			// selectors; off lets site CSS win.
			'force_important'  => true,

			// Force button labels to black or white based on the background
			// for legibility. Off keeps an explicitly chosen label colour.
			'auto_contrast'    => true,

			// Remove the WooCommerce Blocks stylesheet. Off by default: that
			// sheet carries layout for block-based Cart and Checkout, not just
			// colour, so removing it can shift a working page.
			'dequeue_blocks'   => false,

			// Work out hover, focus, active and disabled from the base colour
			// instead of asking for each one. On by default because twelve of
			// the thirty-six slots were interaction states, and nobody sets a
			// hover colour by hand that is better than "the same colour, a bit
			// darker" — while plenty of people set one that clashes. Off puts
			// every state back under manual control.
			'derive_states'    => true,

			// Apply the kit's global typography as well as its colours. On by
			// default: a store carrying the site's brand colours in the theme's
			// unrelated font is not "styled", it is half-styled. Family and
			// weight only — sizes and line heights carry layout, and overriding
			// those is how a checkout page breaks.
			'apply_typography' => true,
		);
	}

	/**
	 * All settings, merged over defaults.
	 *
	 * @return array<string,bool>
	 */
	public static function all() {
		if ( null !== self::$memo ) {
			return self::$memo;
		}

		$defaults = self::defaults();
		$stored   = get_option( self::OPTION, null );

		if ( ! is_array( $stored ) ) {
			$stored = self::migrate_legacy();
		}

		$resolved = array();

		foreach ( $defaults as $key => $default ) {
			$resolved[ $key ] = array_key_exists( $key, $stored )
				? (bool) $stored[ $key ]
				: $default;
		}

		/**
		 * Filters the resolved settings.
		 *
		 * @param array $resolved Setting key => bool.
		 */
		self::$memo = (array) apply_filters( 'eccw_settings', $resolved );

		return self::$memo;
	}

	/**
	 * Read one setting.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function get( $key ) {
		$all = self::all();

		return array_key_exists( $key, $all ) ? (bool) $all[ $key ] : false;
	}

	/**
	 * Persist a partial set of settings.
	 *
	 * Unknown keys are discarded; missing keys keep their current value.
	 *
	 * @param array $values Setting key => value.
	 * @return array The stored settings.
	 */
	public static function update( array $values ) {
		$current = self::all();
		$clean   = self::sanitize( $values );
		$merged  = array_merge( $current, $clean );

		update_option( self::OPTION, $merged );

		self::flush();

		return $merged;
	}

	/**
	 * Cast raw input to the known keys.
	 *
	 * @param array $raw Unfiltered input.
	 * @return array<string,bool>
	 */
	public static function sanitize( array $raw ) {
		$clean = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}

			$value = $raw[ $key ];

			// Accept checkbox strings as well as real booleans.
			if ( is_string( $value ) ) {
				$value = in_array( strtolower( $value ), array( 'yes', '1', 'on', 'true' ), true );
			}

			$clean[ $key ] = (bool) $value;
		}

		return $clean;
	}

	/**
	 * A short signature of the settings that change generated CSS.
	 *
	 * Included in the CSS cache key so toggling one of these invalidates
	 * cached output instead of serving stale styles.
	 *
	 * @return string
	 */
	public static function css_signature() {
		$all = self::all();

		return ( $all['force_important'] ? 'i' : '-' )
			. ( $all['auto_contrast'] ? 'c' : '-' )
			. ( $all['derive_states'] ? 'd' : '-' )
			. ( $all['apply_typography'] ? 't' : '-' );
	}

	/**
	 * Adopt values from the pre-1.2 dequeue option.
	 *
	 * An existing site that deliberately enabled the blocks dequeue keeps that
	 * choice; only fresh installs pick up the safer default.
	 *
	 * @return array Stored settings after migration.
	 */
	private static function migrate_legacy() {
		$defaults = self::defaults();
		$legacy   = get_option( self::LEGACY_DEQUEUE_OPTION, null );

		if ( is_array( $legacy ) && array_key_exists( 'dequeue_blocks', $legacy ) ) {
			$defaults['dequeue_blocks'] = ( 'yes' === $legacy['dequeue_blocks'] );
		}

		update_option( self::OPTION, $defaults );

		return $defaults;
	}

	/**
	 * Clear the per-request memo.
	 */
	public static function flush() {
		self::$memo = null;
	}
}
