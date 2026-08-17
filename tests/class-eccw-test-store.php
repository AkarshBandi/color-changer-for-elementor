<?php
/**
 * In-memory options, post meta and hook storage for the unit tests.
 *
 * Lives in its own file, named to WordPress's convention, so the bootstrap does
 * not have to suppress the file-naming sniff. Everything a test writes here is
 * discarded by reset() in setUp(), which is what keeps the cases independent.
 *
 * @package Color_Changer_For_Elementor
 */

// The bootstrap defines ABSPATH before requiring this file, so the standard
// guard both works here and means what it says: loaded through the bootstrap,
// or not at all.
defined( 'ABSPATH' ) || exit;

/**
 * Test-only data store.
 */
final class ECCW_Test_Store {

	/**
	 * Fake options table.
	 *
	 * @var array
	 */
	public static $options = array();

	/**
	 * Fake post meta, keyed by post ID.
	 *
	 * @var array
	 */
	public static $meta = array();

	/**
	 * Registered filter callbacks, keyed by hook then priority.
	 *
	 * @var array
	 */
	public static $filters = array();

	/**
	 * Forget everything, so one test cannot leak into the next.
	 */
	public static function reset() {
		self::$options = array();
		self::$meta    = array();
		self::$filters = array();
	}
}
