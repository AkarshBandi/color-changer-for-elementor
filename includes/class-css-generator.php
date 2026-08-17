<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Core engine: resolves kit colours, applies WCAG maths, derives interaction
 * states, builds the single site-wide stylesheet, and puts it on the page.
 */
class CSS_Generator {

	/**
	 * Contrast a text colour must clear before it is emitted unchanged.
	 *
	 * WCAG AA for normal text. Below this a colour is not "low contrast",
	 * it is unreadable, and applying it is worse than adjusting it. Large
	 * text only needs 3.0, but the generator cannot know a slot's rendered
	 * font size, so a single AA floor is the safe choice.
	 */
	const MIN_TEXT_CONTRAST = 4.5;

	/**
	 * Contrast an adjusted text colour aims for.
	 *
	 * WCAG AA for body text. Only reached for by colours that failed the floor
	 * above; anything already legible is left exactly as chosen.
	 */
	const TARGET_TEXT_CONTRAST = 4.5;

	/**
	 * Option caching whether the site's Elementor chrome carries WooCommerce.
	 *
	 * Autoloaded on purpose: it is read on every front-end page load, and it
	 * holds one character.
	 */
	const CHROME_OPTION = 'eccw_global_chrome';

	/**
	 * Per-request kit colour memo.
	 *
	 * @var array|null
	 */
	private static $kit_colors = null;

	/**
	 * Put the stylesheet on the page.
	 *
	 * One stylesheet, every element, every page. Until 2.0 this generated a
	 * different stylesheet per page type and inlined it, which cost the plugin
	 * its correctness twice over:
	 *
	 * - It had to know which WooCommerce page it was on, and WooCommerce only
	 *   recognises pages it routes itself. An Elementor-built shop page is a
	 *   plain page as far as `is_shop()` is concerned, so the plugin styled
	 *   nothing on it.
	 * - Slots were gated to a page type, so a mini-cart in the header or a
	 *   product strip on the home page got no styling at all.
	 *
	 * One hashed file, cached forever by the browser, is cheaper than six
	 * cache variants and cannot come back as a page-type bug: there is no
	 * page detection left here.
	 */
	public function enqueue_stylesheet() {
		if ( defined( 'ECCW_DISABLE' ) && ECCW_DISABLE ) {
			return;
		}

		if ( ! Settings::get( 'enabled' ) ) {
			return;
		}

		// WooCommerce pages only. The stylesheet exists to map the kit onto
		// WooCommerce elements; a site-wide enqueue leaks it onto pages that
		// never render any of those elements.
		if ( ! self::is_woocommerce_context() ) {
			return;
		}

		$css = self::current_css();

		if ( '' === $css ) {
			return;
		}

		$url = self::write_stylesheet( $css );

		if ( '' !== $url ) {
			// No version query string on purpose: the content hash is already in
			// the filename, so the URL changes whenever the CSS does.
			wp_enqueue_style( 'eccw-colors', $url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Hashed filename is the cache buster.
			return;
		}

		// Filesystem not writable — some managed hosts mount wp-content
		// read-only. Inline is the fallback, not the default.
		wp_register_style( 'eccw-colors', false, array(), ECCW_VERSION );
		wp_enqueue_style( 'eccw-colors' );
		wp_add_inline_style( 'eccw-colors', $css );
	}

	/**
	 * Whether the current request renders WooCommerce elements.
	 *
	 * Native WooCommerce routes (shop, product, cart, checkout, account,
	 * endpoints), pages embedding WooCommerce via shortcode, and Elementor
	 * pages built with WooCommerce widgets (whose widgets live in
	 * `_elementor_data`, invisible to `is_shop()` and `has_shortcode()`).
	 *
	 * @return bool
	 */
	private static function is_woocommerce_context() {
		if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
			return true;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
			return true;
		}

		// Site chrome. A Menu Cart in an Elementor header puts a cart total
		// and a button on every page of the site, including pages
		// WooCommerce does not route and whose own content holds no
		// WooCommerce widget — so no per-post check can ever see it.
		//
		// This is the bug 2.0 set out to kill ("mini-carts in headers got no
		// styling") and did not: the page-type gate was removed from slot
		// generation, but the surviving gate was here, one layer further out.
		// Answered for the whole site at once, because that is the shape of
		// the question.
		if ( self::has_global_wc_chrome() ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();

			if ( $post ) {
				foreach ( self::wc_shortcodes() as $shortcode ) {
					if ( has_shortcode( $post->post_content, $shortcode ) ) {
						return true;
					}
				}

				// Elementor pages: WooCommerce widgets live in _elementor_data,
				// not post_content, so a plain page built with the Products,
				// Cart or Checkout widget is invisible to the checks above.
				if ( self::layout_has_wc_widget( get_post_meta( $post->ID, '_elementor_data', true ) ) ) {
					return true;
				}
			}
		}

		/**
		 * Filters whether the current request is a WooCommerce context.
		 *
		 * @param bool $is_wc Whether the request renders WooCommerce elements.
		 */
		return (bool) apply_filters( 'eccw_is_woocommerce_context', false );
	}

	/**
	 * WooCommerce shortcodes that put store elements on an ordinary page.
	 *
	 * @return string[]
	 */
	private static function wc_shortcodes() {
		return array(
			'products',
			'product_page',
			'product_category',
			'product',
			'add_to_cart',
			'woocommerce_cart',
			'woocommerce_checkout',
			'woocommerce_my_account',
			'woocommerce_order_tracking',
		);
	}

	/**
	 * Whether an Elementor layout contains a WooCommerce widget.
	 *
	 * This used to be a `"widgetType":"woocommerce-*"` regex, which saw only
	 * Elementor's own WooCommerce widgets. Elementor free ships none of them,
	 * so on the very common Essential Addons / Premium Addons stack the check
	 * matched nothing and the page went unstyled. The registry already knows
	 * which widget types belong to the WooCommerce family, addon map
	 * included — ask it rather than re-deriving the answer from a prefix.
	 *
	 * @param mixed $elementor_data Raw `_elementor_data` meta.
	 * @return bool
	 */
	private static function layout_has_wc_widget( $elementor_data ) {
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

		// Shortcode widgets may embed WooCommerce shortcodes. The stored value
		// is JSON-escaped, so unescape before asking has_shortcode(): a
		// shortcode carrying an attribute (`[products ids="4,5"]`) is cut off
		// at the first escaped quote otherwise.
		if ( preg_match_all( '/"shortcode"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $elementor_data, $matches ) ) {
			foreach ( $matches[1] as $shortcode ) {
				$shortcode = str_replace( array( '\\"', '\\\\' ), array( '"', '\\' ), $shortcode );

				foreach ( self::wc_shortcodes() as $tag ) {
					if ( has_shortcode( $shortcode, $tag ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Whether the site's Elementor chrome renders WooCommerce elements on
	 * every page.
	 *
	 * Cached in an autoloaded option rather than recomputed: theme-builder
	 * templates change when somebody edits one, not per request, and this is
	 * consulted on every front-end page load. `Plugin` flushes it when an
	 * `elementor_library` post is saved or deleted.
	 *
	 * @return bool
	 */
	public static function has_global_wc_chrome() {
		$cached = get_option( self::CHROME_OPTION, null );

		if ( '1' === $cached || '0' === $cached ) {
			return '1' === $cached;
		}

		$answer = self::detect_global_wc_chrome();

		update_option( self::CHROME_OPTION, $answer ? '1' : '0' );

		return $answer;
	}

	/**
	 * Forget the cached global-chrome answer.
	 */
	public static function flush_global_chrome() {
		delete_option( self::CHROME_OPTION );
	}

	/**
	 * Scan Elementor theme-builder templates for WooCommerce widgets.
	 *
	 * Bounded like discovery is: a site with hundreds of saved templates
	 * should not turn one cache miss into an unbounded meta read.
	 *
	 * @return bool
	 */
	private static function detect_global_wc_chrome() {
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

			if ( self::layout_has_wc_widget( get_post_meta( $template_id, '_elementor_data', true ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Elementor template types that render as part of the site's chrome.
	 *
	 * Deliberately excludes `page` and `section` templates: those are inserted
	 * into one specific layout, which the per-post check already reads.
	 *
	 * @return string[]
	 */
	private static function chrome_template_types() {
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

	/**
	 * The generated stylesheet for this site's current state.
	 *
	 * @return string CSS, or '' when there is nothing to style.
	 */
	public static function current_css() {
		$saved    = get_option( 'eccw_colors_mappings', array() );
		$mappings = isset( $saved['widgets'] ) ? $saved['widgets'] : array();

		if ( empty( $mappings ) ) {
			return '';
		}

		$version   = isset( $saved['version'] ) ? $saved['version'] : '1';
		$cache_key = self::cache_key( $version );

		$cached = Cache_Manager::get( $cache_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$instance = new self();
		$css      = $instance->build_css( $instance->prepare( $mappings ) );

		if ( '' !== $css ) {
			Cache_Manager::set( $cache_key, $css );
		}

		return $css;
	}

	/**
	 * Write the stylesheet to a hashed filename and return its URL.
	 *
	 * The hash is the cache-buster: a changed palette produces a different
	 * filename, so the file can be served with a far-future expiry and never
	 * needs revalidating. Old files are removed as they are superseded.
	 *
	 * @param string $css Generated CSS.
	 * @return string URL, or '' when the filesystem will not take it.
	 */
	public static function write_stylesheet( $css ) {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return '';
		}

		$dir  = trailingslashit( $uploads['basedir'] ) . 'eccw';
		$hash = substr( md5( $css . ECCW_VERSION ), 0, 12 );
		$file = $dir . '/colors-' . $hash . '.css';

		if ( file_exists( $file ) ) {
			return trailingslashit( $uploads['baseurl'] ) . 'eccw/colors-' . $hash . '.css';
		}

		if ( ! wp_mkdir_p( $dir ) || ! wp_is_writable( $dir ) ) {
			return '';
		}

		// Old files are not deleted on sight: a browser, edge cache, or page
		// cache can keep serving HTML that links to the previous hash for days
		// (7-day caches are common), and a deleted stylesheet 404s the
		// plugin's styling on those pages — an edit that looks like it "did
		// not stick". The files are tiny and only written when the CSS
		// actually changes, so pruning by age bounds the accumulation.
		foreach ( (array) glob( $dir . '/colors-*.css' ) as $old ) {
			if ( filemtime( $old ) < time() - 30 * DAY_IN_SECONDS ) {
				wp_delete_file( $old );
			}
		}

		if ( false === file_put_contents( $file, $css ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem is unavailable on the front end without loading admin includes on every request.
			return '';
		}

		return trailingslashit( $uploads['baseurl'] ) . 'eccw/colors-' . $hash . '.css';
	}

	/**
	 * Cache key for one rendering of the generated stylesheet.
	 *
	 * Everything that can change the output is folded in: the mappings version,
	 * the kit colours, the settings that alter declarations, and the cache
	 * generation token. The token is what makes invalidation reliable — the
	 * other components only cover changes the writer remembered to record.
	 *
	 * @param string|int $mappings_ver Stored mappings version.
	 * @return string Transient key.
	 */
	public static function cache_key( $mappings_ver ) {
		// serialize() here only feeds an md5 cache key; no serialized data is persisted.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$kit_ver = md5( serialize( self::get_kit_colors() ) . serialize( Typography::kit_typography() ) );

		return 'eccw_css_' . md5(
			$mappings_ver
			. $kit_ver
			. Settings::css_signature()
			. Cache_Manager::generation()
		);
	}

	/**
	 * The palette available for mapping.
	 *
	 * Reads the active kit's global colours, falls back to a neutral palette
	 * when the kit defines none, and lets add-ons extend either through the
	 * `eccw_kit_colors` filter.
	 *
	 * @return array Colour id => hex.
	 */
	public static function get_kit_colors() {
		if ( null !== self::$kit_colors ) {
			return self::$kit_colors;
		}

		$colors = self::read_kit_colors();

		if ( empty( $colors ) ) {
			$colors = self::fallback_colors();
		}

		/**
		 * Filters the palette available for mapping.
		 *
		 * @param array $colors Colour id => hex.
		 */
		self::$kit_colors = (array) apply_filters( 'eccw_kit_colors', $colors );

		return self::$kit_colors;
	}

	/**
	 * Read the raw global colours from the active Elementor kit.
	 *
	 * @return array Colour id => hex. Empty when no kit or no colours.
	 */
	private static function read_kit_colors() {
		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return self::legacy_scheme_colors();
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $settings ) ) {
			return self::legacy_scheme_colors();
		}

		$colors        = array();
		$system_colors = isset( $settings['system_colors'] ) ? $settings['system_colors'] : array();
		$custom_colors = isset( $settings['custom_colors'] ) ? $settings['custom_colors'] : array();

		foreach ( $system_colors as $c ) {
			if ( ! empty( $c['_id'] ) && ! empty( $c['color'] ) ) {
				$colors[ $c['_id'] ] = $c['color'];
			}
		}

		foreach ( $custom_colors as $c ) {
			if ( ! empty( $c['_id'] ) && ! empty( $c['color'] ) ) {
				$colors[ $c['_id'] ] = $c['color'];
			}
		}

		// The kit's dedicated global settings are first-class palette entries.
		// WooCommerce slots map to these tokens, so WC elements follow the
		// global settings automatically — and because the palette feeds the
		// CSS cache key, changing any of them regenerates the stylesheet
		// without mapping edits.
		foreach (
			array(
				'button_normal'     => 'button_background_color',
				'button_hover'      => 'button_hover_background_color',
				'button_text'       => 'button_text_color',
				'button_hover_text' => 'button_hover_text_color',
				'link_normal'       => 'link_normal_color',
				'link_hover'        => 'link_hover_color',
				'body'              => 'body_color',
				'h1'                => 'h1_color',
				'h2'                => 'h2_color',
				'h3'                => 'h3_color',
				'h4'                => 'h4_color',
				'h5'                => 'h5_color',
				'h6'                => 'h6_color',
			) as $token => $setting
		) {
			if ( ! empty( $settings[ $setting ] ) ) {
				$colors[ $token ] = $settings[ $setting ];
			}
		}

		return $colors;
	}

	/**
	 * Elementor < 3.0 kept global colours in the scheme options rather than
	 * a Kit. Sites that never migrated have no `_elementor_page_settings` at
	 * all, so without this the plugin silently styled nothing for them.
	 *
	 * @return array Colour id => hex.
	 */
	private static function legacy_scheme_colors() {
		$scheme = get_option( 'elementor_scheme_color' );

		if ( ! is_array( $scheme ) ) {
			return array();
		}

		// 1 primary, 2 secondary, 3 text, 4 accent.
		$map    = array(
			1 => 'primary',
			2 => 'secondary',
			3 => 'text',
			4 => 'accent',
		);
		$colors = array();

		foreach ( $map as $index => $token ) {
			if ( ! empty( $scheme[ $index ] ) && is_string( $scheme[ $index ] ) ) {
				$colors[ $token ] = $scheme[ $index ];
			}
		}

		return $colors;
	}

	/**
	 * Neutral palette used when the active kit defines no global colours.
	 *
	 * @return array Colour id => hex.
	 */
	private static function fallback_colors() {
		return array(
			'primary'   => '#6EC1E4',
			'secondary' => '#54595F',
			'text'      => '#7A7A7A',
			'accent'    => '#61CE70',
		);
	}

	/**
	 * Whether the active Elementor kit defines its own global colors.
	 *
	 * Distinguishes a kit with real brand colours from the plugin's fallback
	 * palette, so the settings screen can tell the user their colours are
	 * stand-ins rather than reporting a count of fallbacks as if they were real.
	 *
	 * @return bool True when the active kit has at least one global color.
	 */
	public static function has_kit_colors() {
		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return false;
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $settings ) ) {
			return false;
		}

		$system_colors = isset( $settings['system_colors'] ) ? $settings['system_colors'] : array();
		$custom_colors = isset( $settings['custom_colors'] ) ? $settings['custom_colors'] : array();

		foreach ( array_merge( $system_colors, $custom_colors ) as $c ) {
			if ( ! empty( $c['_id'] ) && ! empty( $c['color'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Pick a readable text color for a given background hex.
	 *
	 * Uses WCAG relative luminance: dark backgrounds get white text,
	 * light backgrounds get near-black text.
	 *
	 * @param string $hex Background color hex (with or without #).
	 * @return string '#ffffff' or '#111111'.
	 */
	public static function contrast_text( $hex ) {
		$hex = ltrim( trim( (string) $hex ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return '#111111';
		}

		// Ask the question directly rather than through a threshold. This used
		// to be `luminance > 0.179 ? dark : light`, which is the standard sRGB
		// midpoint heuristic — but that constant is calibrated for a choice
		// between pure black and pure white, and the dark option here is
		// #111111. Being slightly lighter than black moves the real crossover
		// below 0.179, so for a band of mid greys the threshold returned the
		// *less* readable of the two.
		//
		// It was not academic: on the store this was developed against, the
		// kit's own Text colour #747880 got #111111 at 4.26:1 when #ffffff
		// would have given 4.43:1 — and #767676 got a label that fails WCAG AA
		// (4.16:1) when a passing one (4.54:1) was available. Comparing the two
		// ratios costs two multiplications and cannot be miscalibrated.
		$dark  = '#111111';
		$light = '#ffffff';

		return self::contrast_ratio( $dark, '#' . $hex ) >= self::contrast_ratio( $light, '#' . $hex )
			? $dark
			: $light;
	}

	/**
	 * WCAG relative luminance of a hex colour.
	 *
	 * @param string $hex Colour hex, with or without #.
	 * @return float 0.0 (black) to 1.0 (white).
	 */
	public static function relative_luminance( $hex ) {
		$rgb = self::hex_to_rgb( $hex );

		$linear = array_map(
			function ( $channel ) {
				$c = $channel / 255;

				if ( $c <= 0.03928 ) {
					return $c / 12.92;
				}

				return pow( ( $c + 0.055 ) / 1.055, 2.4 );
			},
			$rgb
		);

		return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
	}

	/**
	 * WCAG contrast ratio between two colours.
	 *
	 * @param string $hex1 First colour.
	 * @param string $hex2 Second colour.
	 * @return float 1.0 (identical) to 21.0 (black on white).
	 */
	public static function contrast_ratio( $hex1, $hex2 ) {
		$lum1 = self::relative_luminance( $hex1 );
		$lum2 = self::relative_luminance( $hex2 );

		$lighter = max( $lum1, $lum2 );
		$darker  = min( $lum1, $lum2 );

		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * Nudge a text colour until it is legible against a background.
	 *
	 * Slots that paint their own background already get a black-or-white label
	 * via contrast_text(). Slots that paint *only* text had no such protection:
	 * prices, links, tabs, table headings and account navigation. PHP cannot
	 * see the rendered page, so the background is an assumption — white unless
	 * a site says otherwise through `eccw_page_background`.
	 *
	 * Hue and saturation are preserved and only lightness moves. Colours that
	 * are already legible are returned untouched.
	 *
	 * @param string $hex        Chosen text colour.
	 * @param string $background Colour assumed to sit behind it.
	 * @return string Hex that clears the readability floor, with leading #.
	 */
	public static function ensure_readable( $hex, $background ) {
		// Returned verbatim rather than normalised: a colour that needs no help
		// should reach the stylesheet exactly as the user chose it.
		if ( self::contrast_ratio( $hex, $background ) >= self::MIN_TEXT_CONTRAST ) {
			return $hex;
		}

		list( $h, $s, $l ) = self::hex_to_hsl( $hex );

		// Move away from the background: darker on a light page, lighter on a
		// dark one. 2% steps keep the result close to what was asked for.
		$direction = self::relative_luminance( $background ) > 0.4 ? -0.02 : 0.02;
		$candidate = $hex;

		for ( $i = 0; $i < 50; $i++ ) {
			$l += $direction;

			if ( $l < 0 || $l > 1 ) {
				break;
			}

			$candidate = self::hsl_to_hex( $h, $s, $l );

			if ( self::contrast_ratio( $candidate, $background ) >= self::TARGET_TEXT_CONTRAST ) {
				return $candidate;
			}
		}

		// Ran out of lightness before reaching the target. Return the best
		// attempt if it beats the floor, otherwise fall back to plain text.
		if ( self::contrast_ratio( $candidate, $background ) >= self::MIN_TEXT_CONTRAST ) {
			return $candidate;
		}

		return self::contrast_text( $background );
	}

	/**
	 * Work out an interaction state's colour from the base colour.
	 *
	 * Twelve of the registry's thirty-six slots are interaction states. Almost
	 * nobody picks a hover colour by hand that beats "the same colour, a little
	 * darker", and plenty pick one that clashes with the base. Deriving keeps a
	 * store internally consistent: every interactive element moves the same way
	 * under the pointer.
	 *
	 * Lightness only, so hue and saturation survive. Direction follows the
	 * base: a dark colour lightens on hover because darkening further would be
	 * invisible.
	 *
	 * @param string $hex   Base colour.
	 * @param string $state One of hover, focus, active, disabled.
	 * @return string Derived hex.
	 */
	public static function derive_state_color( $hex, $state ) {
		$normalized = self::normalize_hex( $hex );

		list( $h, $sat, $l ) = self::hex_to_hsl( $normalized );

		// Away from wherever the base already is: darken a light colour,
		// lighten a dark one.
		$direction = $l > 0.5 ? -1 : 1;

		switch ( $state ) {
			case 'hover':
				$l += $direction * 0.10;
				break;

			case 'active':
				$l += $direction * 0.16;
				break;

			case 'focus':
				// Focus keeps the base colour. It is signalled by the outline
				// the slot already paints, not by a colour change.
				return $normalized;

			case 'disabled':
				// Washed out rather than merely paler: a disabled control
				// should read as inert, and dropping saturation says that more
				// clearly than lightness alone.
				$sat *= 0.35;
				$l    = $l + ( ( 0.62 - $l ) * 0.55 );
				break;

			default:
				return $normalized;
		}

		return self::hsl_to_hex( $h, $sat, min( 1.0, max( 0.0, $l ) ) );
	}

	/**
	 * The interaction state a slot represents.
	 *
	 * Read from the slot's own `states` list rather than guessed from its id.
	 *
	 * @param array $slot_def Slot definition.
	 * @return string State name, or 'normal'.
	 */
	private static function state_of_slot( array $slot_def ) {
		$states = isset( $slot_def['states'] ) ? (array) $slot_def['states'] : array();

		foreach ( array( 'hover', 'active', 'disabled', 'focus' ) as $state ) {
			if ( in_array( $state, $states, true ) ) {
				return $state;
			}
		}

		return 'normal';
	}

	/**
	 * Which slots the user still chooses, given the derive setting.
	 *
	 * @param array $slots Slot definitions from the registry.
	 * @return array Slots to show or store.
	 */
	public static function author_slots( array $slots ) {
		if ( ! Settings::get( 'derive_states' ) ) {
			return $slots;
		}

		return array_values(
			array_filter(
				$slots,
				function ( $slot ) {
					return empty( $slot['derives_from'] );
				}
			)
		);
	}

	/**
	 * The background text is assumed to sit on.
	 *
	 * @return string Hex colour.
	 */
	public static function page_background() {
		/**
		 * Filters the background colour used for readability checks.
		 *
		 * @param string $background Hex colour.
		 */
		return self::normalize_hex( apply_filters( 'eccw_page_background', '#ffffff' ) );
	}

	/**
	 * Coerce any accepted colour form to a six-digit lowercase hex.
	 *
	 * @param string $hex Colour hex, with or without #, 3 or 6 digits.
	 * @return string Hex with leading #.
	 */
	private static function normalize_hex( $hex ) {
		$hex = strtolower( ltrim( trim( (string) $hex ), '#' ) );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( ! preg_match( '/^[0-9a-f]{6}$/', $hex ) ) {
			return '#000000';
		}

		return '#' . $hex;
	}

	/**
	 * Hex to an RGB triple.
	 *
	 * @param string $hex Colour hex.
	 * @return int[] Red, green, blue, each 0-255.
	 */
	private static function hex_to_rgb( $hex ) {
		return array_map( 'hexdec', str_split( ltrim( self::normalize_hex( $hex ), '#' ), 2 ) );
	}

	/**
	 * Hex to hue, saturation and lightness.
	 *
	 * @param string $hex Colour hex.
	 * @return float[] Hue 0-1, saturation 0-1, lightness 0-1.
	 */
	private static function hex_to_hsl( $hex ) {
		list( $r, $g, $b ) = array_map(
			function ( $channel ) {
				return $channel / 255;
			},
			self::hex_to_rgb( $hex )
		);

		$max   = max( $r, $g, $b );
		$min   = min( $r, $g, $b );
		$l     = ( $max + $min ) / 2;
		$delta = $max - $min;

		if ( 0.0 === (float) $delta ) {
			return array( 0.0, 0.0, $l );
		}

		$s = $l > 0.5 ? $delta / ( 2 - $max - $min ) : $delta / ( $max + $min );

		if ( $max === $r ) {
			$h = ( ( $g - $b ) / $delta ) + ( $g < $b ? 6 : 0 );
		} elseif ( $max === $g ) {
			$h = ( ( $b - $r ) / $delta ) + 2;
		} else {
			$h = ( ( $r - $g ) / $delta ) + 4;
		}

		return array( $h / 6, $s, $l );
	}

	/**
	 * Hue, saturation and lightness back to hex.
	 *
	 * @param float $h Hue 0-1.
	 * @param float $s Saturation 0-1.
	 * @param float $l Lightness 0-1.
	 * @return string Hex with leading #.
	 */
	private static function hsl_to_hex( $h, $s, $l ) {
		$l = min( 1.0, max( 0.0, $l ) );

		if ( 0.0 === (float) $s ) {
			$channels = array( $l, $l, $l );
		} else {
			$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - ( $l * $s );
			$p = ( 2 * $l ) - $q;

			$channels = array(
				self::hue_to_channel( $p, $q, $h + ( 1 / 3 ) ),
				self::hue_to_channel( $p, $q, $h ),
				self::hue_to_channel( $p, $q, $h - ( 1 / 3 ) ),
			);
		}

		$hex = '';

		foreach ( $channels as $channel ) {
			$hex .= str_pad( dechex( (int) round( $channel * 255 ) ), 2, '0', STR_PAD_LEFT );
		}

		return '#' . $hex;
	}

	/**
	 * One RGB channel from an HSL triple.
	 *
	 * @param float $p Intermediate value.
	 * @param float $q Intermediate value.
	 * @param float $t Shifted hue.
	 * @return float Channel 0-1.
	 */
	private static function hue_to_channel( $p, $q, $t ) {
		if ( $t < 0 ) {
			++$t;
		}

		if ( $t > 1 ) {
			--$t;
		}

		if ( $t < 1 / 6 ) {
			return $p + ( ( $q - $p ) * 6 * $t );
		}

		if ( $t < 1 / 2 ) {
			return $q;
		}

		if ( $t < 2 / 3 ) {
			return $p + ( ( $q - $p ) * ( ( 2 / 3 ) - $t ) * 6 );
		}

		return $p;
	}

	/**
	 * Build declaration rules for a slot's property list.
	 *
	 * When a slot paints a background-color, its text color and SVG fill
	 * are auto-derived via contrast so button labels stay readable.
	 *
	 * @param array  $properties Property names from the registry.
	 * @param string $hex        The mapped color hex.
	 * @return array CSS declarations (e.g. array( 'color: #fff !important' )).
	 */
	private function build_rules( array $properties, $hex ) {
		$rules  = array();
		$has_bg = in_array( 'background-color', $properties, true );

		/**
		 * Filters whether label colour is auto-derived from the background.
		 *
		 * @param bool   $auto       Whether to derive the label colour.
		 * @param string $hex        Background colour being applied.
		 * @param array  $properties Properties the slot paints.
		 */
		$auto_contrast = (bool) apply_filters(
			'eccw_auto_contrast_text',
			Settings::get( 'auto_contrast' ),
			$hex,
			$properties
		);

		/**
		 * Filters whether declarations are emitted with !important.
		 *
		 * @param bool  $force      Whether to append !important.
		 * @param array $properties Properties the slot paints.
		 */
		$force = (bool) apply_filters(
			'eccw_force_important',
			Settings::get( 'force_important' ),
			$properties
		);

		$suffix = $force ? ' !important' : '';
		$derive = $has_bg && $auto_contrast;

		if ( $derive ) {
			$text_hex = self::contrast_text( $hex );
		} elseif ( $auto_contrast ) {
			$text_hex = self::ensure_readable( $hex, self::page_background() );
		} else {
			$text_hex = $hex;
		}

		foreach ( $properties as $prop ) {
			if ( 'color' === $prop || 'fill' === $prop ) {
				$rules[] = $prop . ': ' . $text_hex . $suffix;
				continue;
			}

			$rules[] = $prop . ': ' . $hex . $suffix;
		}

		return $rules;
	}

	/**
	 * Resolve a stored color value to a concrete hex.
	 *
	 * A stored value may be either a kit color token (e.g. "primary") or a
	 * raw hex string (e.g. "#FF5733"). Tokens are looked up in the active kit;
	 * raw hex is validated and returned.
	 *
	 * @param string $value Stored color token or hex.
	 * @return string Resolved hex (with leading #).
	 */
	public static function resolve_color( $value ) {
		$kit_colors = self::get_kit_colors();

		if ( isset( $kit_colors[ $value ] ) ) {
			// Validated rather than trusted: the resolved value is interpolated
			// straight into a stylesheet served on every front-end page.
			$safe = self::sanitize_css_color( $kit_colors[ $value ] );

			return '' !== $safe ? $safe : '#000000';
		}

		$hex = ltrim( (string) $value, '#' );

		if ( preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return '#' . strtolower( $hex );
		}

		return '#000000';
	}

	/**
	 * Accept a CSS colour, or nothing at all.
	 *
	 * An allowlist, deliberately: hex in its four lengths, and the rgb/hsl
	 * function forms with numeric arguments. Anything outside these shapes has
	 * no business in a colour slot and is rejected rather than escaped.
	 *
	 * @param mixed $value Candidate colour.
	 * @return string The colour, or '' when it is not one.
	 */
	public static function sanitize_css_color( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		if ( '' === $value || strlen( $value ) > 64 ) {
			return '';
		}

		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return $value;
		}

		// rgb()/rgba()/hsl()/hsla() with numbers, percentages, separators and
		// nothing else. No nested functions, no var(), no url().
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9%.,\s\/deg-]+\s*\)$/i', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Attach each stored slot to its registry definition.
	 *
	 * The registry definitions are indexed by slot id so each stored slot is
	 * matched in constant time rather than by scanning every definition.
	 *
	 * @param array $mappings Stored widget mappings.
	 * @return array Mappings with definitions attached.
	 */
	private function prepare( $mappings ) {
		$prepared = array();

		foreach ( $mappings as $widget_type => $widget ) {
			$definition = Element_Registry::lookup( $widget_type );

			if ( null === $definition || ! isset( $widget['slots'] ) ) {
				continue;
			}

			$by_id = array();

			foreach ( $definition['slots'] as $def ) {
				$by_id[ $def['slot_id'] ] = $def;
			}

			$slots = array();

			foreach ( $widget['slots'] as $slot_id => $slot_config ) {
				if ( ! isset( $by_id[ $slot_id ] ) ) {
					continue;
				}

				$slots[ $slot_id ] = array(
					'color'      => $slot_config['color'],
					'definition' => $by_id[ $slot_id ],
				);
			}

			// Merge registry slots missing from the saved mappings. Plugin
			// updates can add slots; without this, new elements would stay
			// unstyled until the user resets or resaves. Heuristic defaults
			// keep the merge consistent with a fresh apply_defaults().
			$heuristic = new Heuristic_Engine();

			foreach ( $by_id as $slot_id => $def ) {
				if ( isset( $slots[ $slot_id ] ) ) {
					continue;
				}

				$slots[ $slot_id ] = array(
					'color'      => $heuristic->determine_color( $slot_id ),
					'definition' => $def,
				);
			}

			if ( ! empty( $slots ) ) {
				$prepared[ $widget_type ] = array(
					'label' => isset( $widget['label'] ) ? $widget['label'] : $widget_type,
					'slots' => $slots,
				);
			}
		}

		return $prepared;
	}

	/**
	 * Build the complete stylesheet from prepared mappings.
	 *
	 * Base colours are resolved first so state slots can derive from them no
	 * matter what order the slots appear in. The kit typography rules and any
	 * add-on CSS via `eccw_generated_css` are appended, then everything is
	 * minified.
	 *
	 * @param array $mappings Prepared widget mappings.
	 * @return string CSS.
	 */
	private function build_css( $mappings ) {
		$css    = '';
		$derive = Settings::get( 'derive_states' );

		// Written in cascade order, not card order. Every rule here is
		// `!important`, so equal specificity is decided by source position;
		// broad cards are written first so they lose those ties.
		foreach ( Element_Registry::emission_order( $mappings ) as $widget_type ) {
			$widget = $mappings[ $widget_type ];

			$base_colors = array();

			foreach ( $widget['slots'] as $base_id => $base_data ) {
				if ( empty( $base_data['definition']['derives_from'] ) ) {
					$base_colors[ $base_id ] = self::resolve_color( $base_data['color'] );
				}
			}

			foreach ( $widget['slots'] as $slot_id => $slot_data ) {
				$slot_def = $slot_data['definition'];
				$hex      = self::resolve_color( $slot_data['color'] );

				$derives_from = isset( $slot_def['derives_from'] ) ? $slot_def['derives_from'] : '';

				if ( $derive && '' !== $derives_from && isset( $base_colors[ $derives_from ] ) ) {
					// The slot's own stored colour is ignored while deriving is
					// on, not discarded — switching the setting off brings back
					// whatever was chosen before.
					$state = self::state_of_slot( $slot_def );

					if ( 'focus' === $state && ! in_array( 'background-color', $slot_def['properties'], true ) ) {
						// A focused control must be visibly distinct from its idle
						// state (WCAG 2.4.7). Border-only slots (inputs, quantity
						// boxes) cannot show a shade change, so focus is signalled
						// with the brand primary rather than the base colour —
						// which made the focus border identical to the normal
						// border. Falls back to the derived shade when the kit
						// defines no primary.
						$kit = self::get_kit_colors();
						$hex = isset( $kit['primary'] ) && '' !== $kit['primary']
							? self::resolve_color( 'primary' )
							: self::derive_state_color( $base_colors[ $derives_from ], $state );
					} else {
						$hex = self::derive_state_color( $base_colors[ $derives_from ], $state );
					}
				}

				$css .= $this->build_slot_css( $slot_def, $hex );
			}
		}

		$css .= Typography::css();

		/**
		 * Filters the generated stylesheet before minification.
		 *
		 * @param string $css      Generated CSS.
		 * @param array  $mappings Filtered mappings the CSS was built from.
		 */
		$css = (string) apply_filters( 'eccw_generated_css', $css, $mappings );

		return $this->minify( $css );
	}

	/**
	 * Build the CSS rules for one slot across every selector and state.
	 *
	 * Shared by the full build and the single-slot preview builder so the two
	 * paths cannot drift apart.
	 *
	 * @param array  $slot_def Slot definition.
	 * @param string $hex      Resolved colour.
	 * @return string CSS.
	 */
	private function build_slot_css( array $slot_def, $hex ) {
		$css = '';

		foreach ( $slot_def['selectors'] as $selector ) {
			foreach ( $slot_def['states'] as $state ) {
				$state_selectors = $this->get_state_selectors( $selector, $state );

				foreach ( $state_selectors as $state_selector ) {
					$rules = $this->build_rules( $slot_def['properties'], $hex );

					if ( 'disabled' === $state ) {
						$rules[] = 'opacity: 0.5 !important';
					}

					if ( 'focus' === $state && in_array( 'background-color', $slot_def['properties'], true ) ) {
						// A focused control must stay visible even when its colour
						// matches its idle state (a dark button on a light page,
						// say). Draw a ring in a colour that contrasts with the
						// assumed page background, offset so it reads as a halo
						// rather than a thicker border.
						$ring    = self::contrast_text( self::page_background() );
						$rules[] = 'outline: 2px solid ' . $ring . ' !important';
						$rules[] = 'outline-offset: 2px !important';
					}

					$css .= $state_selector . ' { ' . implode( '; ', $rules ) . '; }' . "\n";
				}
			}
		}

		return $css;
	}

	/**
	 * The selectors a state emits for one base selector.
	 *
	 * @param string $selector Base selector.
	 * @param string $state    State name.
	 * @return string[] Selector forms.
	 */
	private function get_state_selectors( $selector, $state ) {
		switch ( $state ) {
			case 'normal':
				return array( $selector );
			case 'hover':
				return array( $selector . ':hover' );
			case 'focus':
				return array( $selector . ':focus' );
			case 'active':
				return array( $selector . ':active', $selector . '.active' );
			case 'disabled':
				return array( $selector . ':disabled', $selector . '.disabled' );
			default:
				return array( $selector . ':' . $state );
		}
	}

	/**
	 * Build CSS for an arbitrary set of mappings (used by rescan/preview paths).
	 *
	 * @param array $mappings Raw widget mappings.
	 * @return string CSS.
	 */
	public static function get_css_for_mappings( $mappings ) {
		$instance = new self();
		$prepared = $instance->prepare( $mappings );

		if ( empty( $prepared ) ) {
			return '';
		}

		return $instance->build_css( $prepared );
	}

	/**
	 * Build CSS for a single slot (used for live per-slot previews).
	 *
	 * @param string $widget_key Registry widget key.
	 * @param string $slot_id    Slot id.
	 * @param string $hex        Resolved colour.
	 * @return string CSS.
	 */
	public static function build_single_css( $widget_key, $slot_id, $hex ) {
		$definition = Element_Registry::lookup( $widget_key );

		if ( null === $definition ) {
			return '';
		}

		$slot_def = null;

		foreach ( $definition['slots'] as $def ) {
			if ( $def['slot_id'] === $slot_id ) {
				$slot_def = $def;
				break;
			}
		}

		if ( null === $slot_def ) {
			return '';
		}

		$instance = new self();

		return $instance->minify( $instance->build_slot_css( $slot_def, $hex ) );
	}

	/**
	 * Every selector the registry styles, per widget.
	 *
	 * @return array Widget key => selector list.
	 */
	public static function get_registry_selectors() {
		$registry = Element_Registry::get_registry();
		$map      = array();

		foreach ( $registry as $widget_key => $definition ) {
			$selectors = array();

			foreach ( $definition['slots'] as $slot ) {
				foreach ( $slot['selectors'] as $sel ) {
					if ( ! in_array( $sel, $selectors, true ) ) {
						$selectors[] = $sel;
					}
				}
			}

			$map[ $widget_key ] = $selectors;
		}

		return $map;
	}

	/**
	 * Crush whitespace out of generated CSS.
	 *
	 * Pure string work — no CSS parser — so it can never alter the meaning of
	 * a declaration it does not understand.
	 *
	 * @param string $css Generated CSS.
	 * @return string Minified CSS.
	 */
	private function minify( $css ) {
		$css = preg_replace( '/\s{2,}/', ' ', $css );
		$css = preg_replace( '/;\s*/', ';', $css );
		$css = preg_replace( '/\{\s*/', '{', $css );
		$css = preg_replace( '/\s*\}/', '}', $css );
		$css = preg_replace( '/\n/', '', $css );
		return trim( $css );
	}
}
