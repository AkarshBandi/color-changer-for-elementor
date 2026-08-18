<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Scans Elementor layouts for WooCommerce widget types.
 *
 * Elementor stores each layout as a JSON blob in `_elementor_data`, which can
 * run to hundreds of kilobytes. An unbounded scan on a large store would load
 * and decode every one of them in a single request, slow enough to time out
 * plugin activation. A capped, newest-first scan finds the same widget types
 * in practice, because a store reuses a handful of WooCommerce widgets across
 * many pages.
 */
class Discovery_Engine {

	/**
	 * How many posts a single scan will read.
	 */
	const DEFAULT_LIMIT = 250;

	/**
	 * Posts read by the last scan.
	 *
	 * @var int
	 */
	private $scanned = 0;

	/**
	 * Scan published content for WooCommerce widget types.
	 *
	 * @param int|null $limit Maximum posts to read. Null uses the default;
	 *                        -1 scans everything (use only from background
	 *                        jobs, never during activation or a web request).
	 * @return array Unique widget type keys.
	 */
	public function scan_all_pages( $limit = null ) {
		/**
		 * `elementor_library` is scanned because on an Elementor Pro store the
		 * WooCommerce widgets are usually not on a page or a product at all.
		 * They live in theme-builder templates: one Single Product template
		 * renders every product, one header holds the menu cart. Scanning only
		 * `page` and `product` therefore missed the entire product page on a
		 * conventionally built store — here it found 3 widget types while the
		 * Single Product template held 6 more that were never seen.
		 *
		 * This is the same omission CSS_Generator::has_global_wc_chrome()
		 * already had to correct for the enqueue decision; discovery was still
		 * looking in the narrower place.
		 */
		$post_types = apply_filters(
			'eccw_scan_post_types',
			array( 'page', 'product', 'elementor_library' )
		);

		if ( null === $limit ) {
			/**
			 * Filters how many posts a discovery scan reads.
			 *
			 * @param int $limit Post count. -1 for unlimited.
			 */
			$limit = (int) apply_filters( 'eccw_scan_limit', self::DEFAULT_LIMIT );
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				// Intentional: Elementor stores widget data in this meta key.
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'       => '_elementor_data',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'cache_results'  => false,
			)
		);

		$widgets = array();

		$this->scanned = count( $posts );

		foreach ( $posts as $post_id ) {
			$elementor_data = get_post_meta( $post_id, '_elementor_data', true );

			if ( empty( $elementor_data ) ) {
				continue;
			}

			$data = json_decode( $elementor_data, true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$this->walk_elements( $data, $widgets );

			// Free the decoded blob before reading the next one; these are
			// large and the loop can run for hundreds of iterations.
			unset( $data, $elementor_data );
		}

		return array_unique( $widgets );
	}

	/**
	 * Scan, and report what was found in the shape the callers expect.
	 *
	 * This method did not exist. Both rescan paths -- the daily cron job and
	 * the Rescan button under Advanced -- called it, so both raised a fatal
	 * the moment they ran: discovery has never actually completed on any
	 * install. It is the same write-here-read-there split this codebase keeps
	 * producing, in its bluntest form, and no test caught it because nothing
	 * exercised either caller.
	 *
	 * `cards` are registry group keys, not widget types: the saved mappings
	 * are keyed by group, and merge_new_widgets() diffs against those keys.
	 * A widget type that resolves to no group is reported as unmapped instead,
	 * which is what lets the settings screen say "we found elements we do not
	 * style" rather than silently ignoring them.
	 *
	 * @param int|null $limit Maximum posts to read.
	 * @return array{cards:string[],unmapped:string[],scanned:int}
	 */
	public function scan( $limit = null ) {
		$widget_types = $this->scan_all_pages( $limit );

		$cards    = array();
		$unmapped = array();

		foreach ( $widget_types as $widget_type ) {
			$key = Element_Registry::normalize_key( $widget_type );

			if ( '' !== (string) $key && null !== Element_Registry::lookup( $key ) ) {
				$cards[] = $key;
				continue;
			}

			$unmapped[] = $widget_type;
		}

		return array(
			'cards'    => array_values( array_unique( $cards ) ),
			'unmapped' => array_values( array_unique( $unmapped ) ),
			'scanned'  => (int) $this->scanned,
		);
	}

	/**
	 * Collect widget types from a decoded Elementor layout.
	 *
	 * @param array $elements Decoded layout.
	 * @param array $widgets  Widget type accumulator (by reference).
	 */
	private function walk_elements( $elements, &$widgets ) {
		Mapping_Service::walk_elements( $elements, $widgets );
	}
}
