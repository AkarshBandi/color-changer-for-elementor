<?php
/**
 * Mutable state for WordPress function shims.
 *
 * Test-only helper; not a plugin global.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class WP_Shims {

	public static $options      = array();
	public static $post_meta    = array();
	public static $page_flags   = array();
	public static $current_time = '2026-01-01 00:00:00';
	public static $transients   = array();
	public static $filters      = array();
	public static $dequeued     = array();

	public static function reset() {
		self::$options      = array();
		self::$post_meta    = array();
		self::$page_flags   = array();
		self::$current_time = '2026-01-01 00:00:00';
		self::$transients   = array();
		self::$filters      = array();
		self::$dequeued     = array();
	}

	public static function set_page_flag( $flag, $value ) {
		self::$page_flags[ $flag ] = $value;
	}
}
