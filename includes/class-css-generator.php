<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class CSS_Generator {

	private static $kit_colors   = null;
	private static $fallback_css = '';

	public function maybe_generate_and_inject() {
		if ( defined( 'ECCw_DISABLE' ) && ECCw_DISABLE ) {
			return;
		}

		if ( ! Page_Context::is_woocommerce_page() ) {
			return;
		}

		$page_type = Page_Context::get_current_page_type();
		$preview   = Preview_System::is_preview_mode();

		if ( $preview ) {
			$mappings_raw = get_transient( 'eccw_preview_draft' );

			if ( empty( $mappings_raw ) ) {
				return;
			}
		} else {
			$saved        = get_option( 'eccw_colors_mappings', array() );
			$mappings_raw = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		}

		$filtered = $this->filter_by_page_type( $mappings_raw, $page_type );

		if ( empty( $filtered ) ) {
			return;
		}

		$kit_colors   = self::get_kit_colors();
		$mappings_ver = $preview ? 'preview' : ( isset( $saved['version'] ) ? $saved['version'] : '1' );
		// serialize() here only feeds an md5 cache key; no serialized data is persisted.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$kit_ver   = md5( serialize( $kit_colors ) );
		$cache_key = 'eccw_css_' . md5( $page_type . $mappings_ver . $kit_ver );

		if ( ! $preview ) {
			$cached = Cache_Manager::get( $cache_key );

			if ( false !== $cached ) {
				$this->inject_css( $cached );
				return;
			}
		}

		$css = $this->build_css( $filtered );

		if ( empty( $css ) ) {
			return;
		}

		if ( ! $preview ) {
			Cache_Manager::set( $cache_key, $css );
		}

		$this->inject_css( $css );
	}

	private function filter_by_page_type( $mappings, $page_type ) {
		$filtered = array();

		foreach ( $mappings as $widget_type => $widget ) {
			$definition = Element_Registry::lookup( $widget_type );

			if ( null === $definition ) {
				continue;
			}

			$filtered_slots = array();

			foreach ( $widget['slots'] as $slot_id => $slot_config ) {
				$slot_def = null;

				foreach ( $definition['slots'] as $def ) {
					if ( $def['slot_id'] === $slot_id ) {
						$slot_def = $def;
						break;
					}
				}

				if ( null === $slot_def ) {
					continue;
				}

				if ( in_array( 'all', $slot_def['page_types'], true ) || in_array( $page_type, $slot_def['page_types'], true ) ) {
					$filtered_slots[ $slot_id ] = array(
						'color'      => $slot_config['color'],
						'definition' => $slot_def,
					);
				}
			}

			if ( ! empty( $filtered_slots ) ) {
				$filtered[ $widget_type ] = array(
					'label' => $widget['label'],
					'slots' => $filtered_slots,
				);
			}
		}

		return $filtered;
	}

	public static function get_kit_colors() {
		if ( null !== self::$kit_colors ) {
			return self::$kit_colors;
		}

		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			self::$kit_colors = self::fallback_colors();
			return self::$kit_colors;
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $settings ) ) {
			self::$kit_colors = self::fallback_colors();
			return self::$kit_colors;
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

		if ( empty( $colors ) ) {
			self::$kit_colors = self::fallback_colors();
		} else {
			self::$kit_colors = $colors;
		}

		return self::$kit_colors;
	}

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
	 * Distinguishes a kit with real brand colors from the plugin's fallback
	 * palette, so the onboarding wizard can prompt users to set their colors.
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

		$rgb = array_map( 'hexdec', str_split( $hex, 2 ) );

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

		$luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

		return $luminance > 0.179 ? '#111111' : '#ffffff';
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
		$rules    = array();
		$has_bg   = in_array( 'background-color', $properties, true );
		$text_hex = $has_bg ? self::contrast_text( $hex ) : $hex;

		foreach ( $properties as $prop ) {
			if ( $has_bg && ( 'color' === $prop || 'fill' === $prop ) ) {
				$rules[] = $prop . ': ' . $text_hex . ' !important';
				continue;
			}

			$rules[] = $prop . ': ' . $hex . ' !important';
		}

		return $rules;
	}

	/**
	 * Resolve a stored color value to a concrete hex.
	 *
	 * A stored value may be either a kit color token (e.g. "primary") or a
	 * raw hex string (e.g. "#FF5733") saved from the live editor. Tokens are
	 * looked up in the active kit; raw hex is returned as-is.
	 *
	 * @param string $value Stored color token or hex.
	 * @return string Resolved hex (with leading #).
	 */
	public static function resolve_color( $value ) {
		$kit_colors = self::get_kit_colors();

		if ( isset( $kit_colors[ $value ] ) ) {
			return $kit_colors[ $value ];
		}

		$hex = ltrim( (string) $value, '#' );

		if ( preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return '#' . strtolower( $hex );
		}

		return '#000000';
	}

	private function build_css( $mappings ) {
		$css = '';

		foreach ( $mappings as $widget_type => $widget ) {
			foreach ( $widget['slots'] as $slot_id => $slot_data ) {
				$slot_def = $slot_data['definition'];
				$hex      = self::resolve_color( $slot_data['color'] );

				foreach ( $slot_def['selectors'] as $selector ) {
					foreach ( $slot_def['states'] as $state ) {
						$state_selectors = $this->get_state_selectors( $selector, $state );

						foreach ( $state_selectors as $state_selector ) {
							$rules = $this->build_rules( $slot_def['properties'], $hex );

							if ( 'disabled' === $state ) {
								$rules[] = 'opacity: 0.5 !important';
							}

							$css .= $state_selector . ' { ' . implode( '; ', $rules ) . '; }' . "\n";
						}
					}
				}
			}
		}

		return $this->minify( $css );
	}

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

	public static function get_css_for_mappings( $mappings, $page_type ) {
		$instance = new self();
		$filtered = $instance->filter_by_page_type( $mappings, $page_type );

		if ( empty( $filtered ) ) {
			return '';
		}

		return $instance->build_css( $filtered );
	}

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

		$css      = '';
		$instance = new self();

		foreach ( $slot_def['selectors'] as $selector ) {
			foreach ( $slot_def['states'] as $state ) {
				$state_selectors = $instance->get_state_selectors( $selector, $state );

				foreach ( $state_selectors as $state_selector ) {
					$rules = $instance->build_rules( $slot_def['properties'], $hex );

					if ( 'disabled' === $state ) {
						$rules[] = 'opacity: 0.5 !important';
					}

					$css .= $state_selector . ' { ' . implode( '; ', $rules ) . '; }' . "\n";
				}
			}
		}

		return $instance->minify( $css );
	}

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

	private function minify( $css ) {
		$css = preg_replace( '/\s{2,}/', ' ', $css );
		$css = preg_replace( '/;\s*/', ';', $css );
		$css = preg_replace( '/\{\s*/', '{', $css );
		$css = preg_replace( '/\s*\}/', '}', $css );
		$css = preg_replace( '/\n/', '', $css );
		return trim( $css );
	}

	private function inject_css( $css ) {
		if ( empty( $css ) ) {
			return;
		}

		self::$fallback_css = $css;
		add_action( 'wp_head', array( __CLASS__, 'output_dynamic_css' ), 999 );
	}

	public static function output_dynamic_css() {
		if ( empty( self::$fallback_css ) ) {
			return;
		}

		// CSS is generated internally from sanitized kit colors and mappings.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<style id="eccw-dynamic-css">' . self::$fallback_css . '</style>';

		self::$fallback_css = '';
	}
}
