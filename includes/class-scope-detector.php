<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether WooCommerce UI can render in the current request.
 *
 * The question this class answers is deliberately *not* "is this a WooCommerce
 * page?". It is "can WooCommerce UI render here?" — which is a different and
 * larger question, because WooCommerce UI arrives from at least eight places
 * that have nothing to do with the URL:
 *
 * - WooCommerce's own routes.
 * - An Elementor document whose widgets live in `_elementor_data`.
 * - Elementor theme-builder chrome rendered around every page.
 * - An Elementor popup.
 * - A WooCommerce shortcode in ordinary post content.
 * - A WooCommerce block in ordinary post content.
 * - A third-party addon's WooCommerce widget.
 * - An integration that knows something we cannot.
 *
 * Every one of those is a separate detector below, and any single one saying
 * yes is enough. They are not required to agree, because each sees a different
 * part of the page and none of them sees all of it.
 *
 * Two rules govern the design.
 *
 * The first is that a false negative and a false positive are both bugs, and
 * they are not symmetrical: a false negative visibly breaks the store, while a
 * false positive quietly breaks the promise that this stylesheet only loads
 * where it is needed. Loading globally to dodge the hard cases is therefore not
 * a shortcut, it is the failure mode.
 *
 * The second is that the detectors that can be pure functions of their input
 * are written that way — `content_has_wc_block()` takes content, not a request.
 * That is what makes them testable without booting WordPress, and it is why the
 * per-detector tests exist at all.
 */
class Scope_Detector {

	/**
	 * Autoloaded option caching the site-wide chrome answer.
	 */
	const CHROME_OPTION = 'eccw_global_chrome';

	/**
	 * Per-request memo, so a page with several enqueue points asks once.
	 *
	 * @var bool|null
	 */
	private static $memo = null;

	/**
	 * Whether WooCommerce UI can render in this request.
	 *
	 * @return bool
	 */
	public static function is_in_scope() {
		if ( null !== self::$memo ) {
			return self::$memo;
		}

		$in_scope = self::native_woocommerce()
			|| self::global_chrome()
			|| self::queried_document();

		/**
		 * Filters the final scope decision.
		 *
		 * The escape hatch for integrations this plugin cannot know about —
		 * bookings, subscriptions, memberships, custom checkout flows. It runs
		 * last and can only be reached when no detector matched, so an
		 * integration can add context but never remove it.
		 *
		 * @param bool $in_scope Whether WooCommerce UI can render here.
		 */
		self::$memo = (bool) apply_filters( 'eccw_is_woocommerce_context', $in_scope );

		return self::$memo;
	}

	/**
	 * Forget the per-request memo. For tests.
	 */
	public static function flush_memo() {
		self::$memo = null;
	}

	/* --------------------------------------------------------------------
	 * Request-dependent detectors
	 * ------------------------------------------------------------------ */

	/**
	 * WooCommerce's own routes.
	 *
	 * Deliberately several conditionals rather than one. `is_woocommerce()` is
	 * true for the shop and product archives and taxonomies but false for the
	 * cart, checkout and account pages, so any single call leaves holes.
	 *
	 * @return bool
	 */
	public static function native_woocommerce() {
		foreach ( array( 'is_woocommerce', 'is_shop', 'is_product', 'is_product_taxonomy', 'is_cart', 'is_checkout', 'is_account_page' ) as $conditional ) {
			if ( function_exists( $conditional ) && call_user_func( $conditional ) ) {
				return true;
			}
		}

		// Account endpoints (orders, downloads, edit-address…) are not
		// is_account_page() on every setup, and order-received is a checkout
		// endpoint rather than a page of its own.
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
			return true;
		}

		return false;
	}

	/**
	 * The queried post's own content and Elementor document.
	 *
	 * @return bool
	 */
	public static function queried_document() {
		if ( ! function_exists( 'is_singular' ) || ! is_singular() ) {
			return false;
		}

		// get_queried_object(), not get_post(). get_post() with no argument
		// reads the global $post, which WordPress only populates once the loop
		// runs — and `wp_enqueue_scripts` fires before the loop. Reading it
		// here returned null on a real front-end request, so this detector
		// could never fire, and on a site whose chrome carries no WooCommerce
		// the page would have gone unstyled with nothing to show why. The
		// queried object is set during query parsing and is available now.
		$post = null;

		if ( function_exists( 'get_queried_object' ) ) {
			$queried = get_queried_object();

			if ( $queried instanceof \WP_Post ) {
				$post = $queried;
			}
		}

		if ( ! $post && function_exists( 'get_post' ) ) {
			$post = get_post();
		}

		if ( ! $post || ! isset( $post->ID ) ) {
			return false;
		}

		$content = isset( $post->post_content ) ? $post->post_content : '';

		if ( self::content_has_wc_shortcode( $content ) ) {
			return true;
		}

		if ( self::content_has_wc_block( $content ) ) {
			return true;
		}

		return self::document_has_wc_widget( get_post_meta( $post->ID, '_elementor_data', true ) );
	}

	/**
	 * Whether the site's Elementor chrome puts WooCommerce UI on every page.
	 *
	 * A Menu Cart in a header is the case this exists for: it renders a cart
	 * total and a button on the home page, the about page and the blog, none
	 * of which WooCommerce routes and none of whose own content holds a
	 * WooCommerce widget. No per-post check can ever see it, so the question
	 * is answered once for the whole site and cached in an autoloaded option.
	 *
	 * Cached rather than recomputed because theme-builder templates change
	 * when somebody edits one, not per request, and this is consulted on every
	 * front-end page load.
	 *
	 * @return bool
	 */
	public static function global_chrome() {
		$cached = get_option( self::CHROME_OPTION, null );

		if ( '1' === $cached || '0' === $cached ) {
			return '1' === $cached;
		}

		$answer = self::detect_global_chrome();

		update_option( self::CHROME_OPTION, $answer ? '1' : '0' );

		return $answer;
	}

	/**
	 * Forget the cached chrome answer.
	 */
	public static function flush_global_chrome() {
		delete_option( self::CHROME_OPTION );
	}

	/**
	 * Scan Elementor theme-builder templates for WooCommerce UI.
	 *
	 * Bounded exactly as discovery is: a site with hundreds of saved templates
	 * must not turn one cache miss into an unbounded meta read on a front-end
	 * request.
	 *
	 * @return bool
	 */
	private static function detect_global_chrome() {
		if ( ! function_exists( 'get_posts' ) ) {
			return false;
		}

		$templates = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'cache_results'  => false,
			)
		);

		$chrome_types = self::chrome_template_types();

		foreach ( $templates as $template_id ) {
			$type = get_post_meta( $template_id, '_elementor_template_type', true );

			if ( ! in_array( $type, $chrome_types, true ) ) {
				continue;
			}

			if ( self::document_has_wc_widget( get_post_meta( $template_id, '_elementor_data', true ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Elementor template types that render as part of the site's chrome.
	 *
	 * Excludes `page` and `section` templates: those are inserted into one
	 * specific layout, which the per-post check already reads. A popup counts,
	 * but only because a popup carrying WooCommerce UI can be triggered from
	 * anywhere — an ordinary newsletter popup contains no WooCommerce widget
	 * and so never reaches this list's effect.
	 *
	 * @return string[]
	 */
	public static function chrome_template_types() {
		/**
		 * Filters which Elementor template types count as site-wide chrome.
		 *
		 * @param array $types Elementor `_elementor_template_type` values.
		 */
		return (array) apply_filters(
			'eccw_chrome_template_types',
			array( 'header', 'footer', 'popup', 'single', 'single-page', 'single-post', 'archive', 'product', 'product-archive', 'search-results', 'error-404' )
		);
	}

	/* --------------------------------------------------------------------
	 * Pure detectors — functions of their input, so they can be tested
	 * without a request, a query or a database.
	 * ------------------------------------------------------------------ */

	/**
	 * Whether content embeds a WooCommerce shortcode.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	public static function content_has_wc_shortcode( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return false;
		}

		foreach ( self::wc_shortcodes() as $tag ) {
			if ( has_shortcode( $content, $tag ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether content embeds a WooCommerce block.
	 *
	 * Blocks are an independent source of WooCommerce UI: a store can run a
	 * block cart and checkout with no shortcode, no Elementor document and, on
	 * a page that is not itself routed by WooCommerce, nothing else to see.
	 * They were previously undetected entirely.
	 *
	 * Matched on the block's namespace with its delimiter — `wp:woocommerce/`
	 * — rather than the bare word. The delimiter is what keeps
	 * `wp:my-plugin/woocommerce-teaser` out, which is the false positive this
	 * check would otherwise invite.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	public static function content_has_wc_block( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return false;
		}

		foreach ( self::wc_block_namespaces() as $namespace ) {
			if ( false !== strpos( $content, 'wp:' . $namespace . '/' ) ) {
				return true;
			}

			// The same string inside `_elementor_data` is JSON, and JSON
			// escapes the forward slash: a block stored through Elementor
			// reads `wp:woocommerce\/cart`, which the plain match above walks
			// straight past. This is the identical trap the shortcode reader
			// below already carries, one encoding layer along.
			if ( false !== strpos( $content, 'wp:' . $namespace . '\\/' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether an Elementor document contains WooCommerce UI.
	 *
	 * Reads the stored JSON rather than walking a decoded tree, which makes
	 * depth irrelevant: a widget nested six containers deep is the same string
	 * match as one at the top level. Elementor's structure has changed shape
	 * twice (sections and columns, then containers) and this survived both.
	 *
	 * Widget types are put to the registry rather than matched on a
	 * `woocommerce-` prefix. The prefix test was the original implementation
	 * and it failed on precisely the stacks that matter: Elementor free ships
	 * no WooCommerce widgets, so on an Essential Addons or Premium Addons
	 * store it matched nothing and every page went unstyled.
	 *
	 * @param mixed $elementor_data Raw `_elementor_data` meta.
	 * @return bool
	 */
	public static function document_has_wc_widget( $elementor_data ) {
		if ( is_array( $elementor_data ) ) {
			$elementor_data = wp_json_encode( $elementor_data );
		}

		if ( ! is_string( $elementor_data ) || '' === $elementor_data ) {
			return false;
		}

		if ( preg_match_all( '/"widgetType"\s*:\s*"([^"]+)"/', $elementor_data, $matches ) ) {
			foreach ( array_unique( $matches[1] ) as $widget_type ) {
				if ( Element_Registry::is_woocommerce_widget_type( $widget_type ) ) {
					return true;
				}
			}
		}

		// A shortcode widget can carry a WooCommerce shortcode. The stored
		// value is JSON-escaped, so unescape first: a shortcode with an
		// attribute (`[products ids="4,5"]`) is otherwise cut off at the first
		// escaped quote and never matches.
		if ( preg_match_all( '/"shortcode"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $elementor_data, $matches ) ) {
			foreach ( $matches[1] as $shortcode ) {
				$shortcode = str_replace( array( '\\"', '\\\\' ), array( '"', '\\' ), $shortcode );

				if ( self::content_has_wc_shortcode( $shortcode ) ) {
					return true;
				}
			}
		}

		// An Elementor document can also hold block markup, in an HTML widget
		// or a converted block. Elementor plus blocks is a real combination
		// and neither detector alone sees it.
		return self::content_has_wc_block( $elementor_data );
	}

	/**
	 * WooCommerce shortcodes that put store UI on an ordinary page.
	 *
	 * @return string[]
	 */
	public static function wc_shortcodes() {
		/**
		 * Filters the WooCommerce shortcodes that bring a page into scope.
		 *
		 * @param string[] $tags Shortcode tags.
		 */
		return (array) apply_filters(
			'eccw_wc_shortcodes',
			array(
				'products',
				'product_page',
				'product_category',
				'product_categories',
				'product',
				'add_to_cart',
				'add_to_cart_url',
				'recent_products',
				'featured_products',
				'sale_products',
				'best_selling_products',
				'top_rated_products',
				'related_products',
				'shop_messages',
				'woocommerce_cart',
				'woocommerce_checkout',
				'woocommerce_my_account',
				'woocommerce_order_tracking',
			)
		);
	}

	/**
	 * Block namespaces whose blocks render WooCommerce UI.
	 *
	 * A namespace rather than a list of block names, because WooCommerce ships
	 * dozens of blocks and adds more every release; enumerating them would be
	 * a list that silently falls behind.
	 *
	 * @return string[]
	 */
	public static function wc_block_namespaces() {
		/**
		 * Filters the block namespaces that bring a page into scope.
		 *
		 * @param string[] $namespaces Block namespaces, without the trailing slash.
		 */
		return (array) apply_filters(
			'eccw_wc_block_namespaces',
			array( 'woocommerce' )
		);
	}
}
