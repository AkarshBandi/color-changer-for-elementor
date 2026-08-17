<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Feature gate.
 *
 * Single place to ask "is this capability available?". The free plugin answers
 * from the defaults below; a paid add-on answers by hooking the matching filter
 * and returning true once it has validated a licence.
 *
 * The free plugin never contains licensing logic. It only asks the question.
 *
 * Two rules decide which side of the line something falls on:
 *
 * 1. Nothing that protects the user is ever paid — reset, the master switch,
 *    the readability guard. Charging for the safety net of a plugin that
 *    rewrites a live store is charging someone not to lose their work.
 * 2. Nothing that makes the tool feel broken is paid. A colour picker that
 *    cannot pick a colour is not a limited plugin, it is a bad one.
 *
 * Usage:
 *
 *     if ( Features::has( Features::PAGE_OVERRIDES ) ) { ... }
 *
 * An add-on enables a feature with:
 *
 *     add_filter( 'eccw_feature_page_overrides', '__return_true' );
 *
 * or enables everything at once with:
 *
 *     add_filter( 'eccw_is_pro', '__return_true' );
 */
class Features {

	/**
	 * Different colours per page type — shop, cart, checkout, account.
	 *
	 * Real added complexity, and only a professional wants the divergence.
	 * Not built yet; the constant exists so the seam is named in one place.
	 */
	const PAGE_OVERRIDES = 'page_overrides';

	/**
	 * Per-request memo so a feature check inside a loop is cheap.
	 *
	 * @var array<string,bool>
	 */
	private static $memo = array();

	/**
	 * Default availability in the free plugin.
	 *
	 * SHARE_LINKS was removed in 2.0 along with the Live Editor that produced
	 * the links. It is not gated; it is gone.
	 *
	 * @return array<string,bool>
	 */
	public static function defaults() {
		return array(
			self::PAGE_OVERRIDES => true,
		);
	}

	/**
	 * Whether a feature is available on this site.
	 *
	 * @param string $feature One of the class constants.
	 * @return bool
	 */
	public static function has( $feature ) {
		$feature = (string) $feature;

		if ( isset( self::$memo[ $feature ] ) ) {
			return self::$memo[ $feature ];
		}

		$defaults = self::defaults();
		$default  = isset( $defaults[ $feature ] ) ? $defaults[ $feature ] : false;

		if ( self::is_pro() ) {
			$default = true;
		}

		/**
		 * Filters availability of a single feature.
		 *
		 * The dynamic portion of the hook name is the feature key, e.g.
		 * `eccw_feature_page_overrides`.
		 *
		 * @param bool   $default Whether the feature is available.
		 * @param string $feature Feature key.
		 */
		$enabled = (bool) apply_filters( 'eccw_feature_' . $feature, $default, $feature );

		self::$memo[ $feature ] = $enabled;

		return $enabled;
	}

	/**
	 * Whether a paid add-on has declared itself active and licensed.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		/**
		 * Filters whether the site has an active paid licence.
		 *
		 * @param bool $is_pro
		 */
		return (bool) apply_filters( 'eccw_is_pro', false );
	}

	/**
	 * Resolved state of every feature, for the settings screen and JS.
	 *
	 * @return array<string,bool>
	 */
	public static function all() {
		$resolved = array();

		foreach ( array_keys( self::defaults() ) as $feature ) {
			$resolved[ $feature ] = self::has( $feature );
		}

		return $resolved;
	}

	/**
	 * Clear the per-request memo.
	 *
	 * Needed when a licence changes mid-request, and by the test suite.
	 */
	public static function flush() {
		self::$memo = array();
	}
}
