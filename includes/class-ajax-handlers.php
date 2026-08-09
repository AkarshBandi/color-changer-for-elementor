<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class AJAX_Handlers {

	public function init() {
		add_action( 'wp_ajax_eccw_save_preview', array( $this, 'save_preview' ) );
		add_action( 'wp_ajax_eccw_dismiss_new', array( $this, 'dismiss_new' ) );
		add_action( 'wp_ajax_eccw_rescan', array( $this, 'rescan' ) );

		add_action( 'wp_ajax_eccw_scan_progress', array( $this, 'scan_progress' ) );
		add_action( 'wp_ajax_eccw_live_update', array( $this, 'live_update' ) );
		add_action( 'wp_ajax_eccw_commit_live', array( $this, 'commit_live' ) );
		add_action( 'wp_ajax_eccw_undo_action', array( $this, 'undo_action' ) );
		add_action( 'wp_ajax_eccw_generate_share', array( $this, 'generate_share' ) );
		add_action( 'wp_ajax_eccw_reset_defaults', array( $this, 'reset_defaults' ) );
	}

	private function verify_request() {
		if ( ! check_ajax_referer( 'eccw_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'color-changer-for-elementor' ) ) );
		}
	}

	public function save_preview() {
		$this->verify_request();

		// Nonce and capability are verified in verify_request() above.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_POST['mappings'] ) || ! is_array( $_POST['mappings'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No mappings provided.', 'color-changer-for-elementor' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by sanitize_mappings() below.
		$mappings = isset( $_POST['mappings'] ) ? wp_unslash( $_POST['mappings'] ) : array();
		$mappings = is_array( $mappings ) ? $mappings : array();
		$mappings = $this->sanitize_mappings( $mappings );

		set_transient( 'eccw_preview_draft', $mappings, HOUR_IN_SECONDS );

		$preview_url = home_url( '/?eccw_preview=1' );

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_url = wc_get_page_permalink( 'shop' );

			if ( $shop_url ) {
				$preview_url = add_query_arg( 'eccw_preview', '1', $shop_url );
			}
		}

		wp_send_json_success( array( 'preview_url' => $preview_url ) );
		// phpcs:enable WordPress.Security.NonceVerification
	}

	public function dismiss_new() {
		$this->verify_request();

		// Nonce and capability are verified in verify_request() above.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_POST['widgets'] ) || ! is_array( $_POST['widgets'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No widgets specified.', 'color-changer-for-elementor' ) ) );
		}

		$saved = get_option( 'eccw_colors_mappings', array() );

		$this->migrate_widget_keys( $saved );

		$dismissed = isset( $saved['dismissed_new'] ) ? $saved['dismissed_new'] : array();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each key sanitized via sanitize_key() below.
		$widgets = isset( $_POST['widgets'] ) ? wp_unslash( $_POST['widgets'] ) : array();
		$widgets = is_array( $widgets ) ? $widgets : array();

		foreach ( $widgets as $widget_key ) {
			$widget_key = Element_Registry::normalize_key( sanitize_key( $widget_key ) );

			if ( ! in_array( $widget_key, $dismissed, true ) ) {
				$dismissed[] = $widget_key;
			}

			if ( isset( $saved['widgets'][ $widget_key ] ) && 'new' === $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'default';
			}
		}

		$saved['dismissed_new'] = $dismissed;

		update_option( 'eccw_colors_mappings', $saved );

		wp_send_json_success( array( 'message' => __( 'Dismissed.', 'color-changer-for-elementor' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification
	}

	public function rescan() {
		$this->verify_request();

		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages();

		$saved = get_option( 'eccw_colors_mappings', array() );

		$needs_update = Mapping_Service::normalize_all( $saved );

		$new_widgets = Mapping_Service::merge_new_widgets( $saved, $widget_types );

		if ( ! empty( $new_widgets ) ) {
			$needs_update = true;
		}

		if ( Mapping_Service::ensure_defaults( $saved ) ) {
			$needs_update = true;
		}

		if ( $needs_update ) {
			update_option( 'eccw_colors_mappings', $saved );
		}

		Cache_Manager::clear_css();

		wp_send_json_success(
			array(
				'new_count' => count( $new_widgets ),
				'message'   => sprintf(
				/* translators: %d: number of new widgets found */
					__( 'Scan complete. Found %d new widget(s).', 'color-changer-for-elementor' ),
					count( $new_widgets )
				),
			)
		);
	}

	public function scan_progress() {
		$this->verify_request();

		// Nonce and capability are verified in verify_request() above.
		// phpcs:disable WordPress.Security.NonceVerification
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch  = 5;
		// phpcs:enable WordPress.Security.NonceVerification

		$post_types = apply_filters( 'eccw_scan_post_types', array( 'page', 'product' ) );
		$post_types = array_map( 'sanitize_key', (array) $post_types );

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				// Intentional: scans Elementor's stored data for widget discovery.
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'       => '_elementor_data',
				'posts_per_page' => $batch,
				'offset'         => $offset,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$found       = array();
		$widget_keys = array();

		foreach ( $posts as $post_id ) {
			$elementor_data = get_post_meta( $post_id, '_elementor_data', true );

			if ( empty( $elementor_data ) ) {
				continue;
			}

			$data = json_decode( $elementor_data, true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$this->walk_elements( $data, $widget_keys );
		}

		$widget_keys = array_unique( $widget_keys );

		foreach ( $widget_keys as $wk ) {
			$definition = Element_Registry::lookup( $wk );
			$label      = $definition ? $definition['label'] : ucfirst( str_replace( array( 'wc-', 'woocommerce-', '-' ), array( '', '', ' ' ), $wk ) );

			if ( isset( $found[ $wk ] ) ) {
				++$found[ $wk ]['count'];
			} else {
				$found[ $wk ] = array(
					'widget_type' => $wk,
					'label'       => $label,
					'count'       => 1,
				);
			}
		}

		$done        = count( $posts ) < $batch;
		$next_offset = $offset + $batch;

		wp_send_json_success(
			array(
				'done'        => $done,
				'next_offset' => $next_offset,
				'found'       => array_values( $found ),
				'batch_count' => count( $posts ),
			)
		);
	}

	private function walk_elements( $elements, &$widget_keys ) {
		Mapping_Service::walk_elements( $elements, $widget_keys );
	}

	public function live_update() {
		$this->verify_request();

		// Nonce and capability are verified in verify_request() above.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_POST['widget_key'] ) || ! isset( $_POST['slot_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing parameters.', 'color-changer-for-elementor' ) ) );
		}

		$widget_key = sanitize_key( $_POST['widget_key'] );
		$slot_id    = sanitize_key( $_POST['slot_id'] );
		$remove     = isset( $_POST['remove'] ) && '1' === $_POST['remove'];

		$draft = get_transient( 'eccw_live_draft' );

		if ( ! is_array( $draft ) ) {
			$draft = array();
		}

		if ( $remove ) {
			if ( isset( $draft[ $widget_key ]['slots'][ $slot_id ] ) ) {
				unset( $draft[ $widget_key ]['slots'][ $slot_id ] );

				if ( empty( $draft[ $widget_key ]['slots'] ) ) {
					unset( $draft[ $widget_key ] );
				}
			}

			set_transient( 'eccw_live_draft', $draft, DAY_IN_SECONDS );

			wp_send_json_success(
				array(
					'removed' => true,
					'count'   => count( $draft ),
				)
			);
		}

		if ( ! isset( $_POST['hex'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing color.', 'color-changer-for-elementor' ) ) );
		}

		$hex = isset( $_POST['hex'] ) ? sanitize_hex_color( wp_unslash( $_POST['hex'] ) ) : '';

		if ( ! $hex ) {
			wp_send_json_error( array( 'message' => __( 'Invalid color.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! isset( $draft[ $widget_key ] ) ) {
			$draft[ $widget_key ] = array( 'slots' => array() );
		}

		$draft[ $widget_key ]['slots'][ $slot_id ] = array( 'color' => $hex );

		set_transient( 'eccw_live_draft', $draft, DAY_IN_SECONDS );

		$css = CSS_Generator::build_single_css( $widget_key, $slot_id, $hex );

		wp_send_json_success(
			array(
				'css'   => $css,
				'count' => count( $draft ),
			)
		);
		// phpcs:enable WordPress.Security.NonceVerification
	}

	public function commit_live() {
		$this->verify_request();

		// The client sends its in-memory draft so a color picked just before
		// Save is never lost to the debounce or a stale transient. Fall back
		// to the persisted live draft for safety.
		// Nonce and capability verified in verify_request() above.
		// phpcs:disable WordPress.Security.NonceVerification
		$draft = array();

		if ( isset( $_POST['draft'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by sanitize_mappings() below.
			$draft = json_decode( wp_unslash( $_POST['draft'] ), true );
			$draft = is_array( $draft ) ? $draft : array();
			$draft = Mapping_Service::sanitize_mappings( $draft );
		}

		if ( empty( $draft ) ) {
			$draft = get_transient( 'eccw_live_draft' );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		if ( empty( $draft ) ) {
			wp_send_json_error( array( 'message' => __( 'No draft changes to save.', 'color-changer-for-elementor' ) ) );
		}

		$saved = get_option( 'eccw_colors_mappings', array() );

		$user_id = get_current_user_id();
		$history = get_user_meta( $user_id, 'eccw_history_' . $user_id, true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		array_unshift( $history, $saved );
		$history = array_slice( $history, 0, 10 );

		update_user_meta( $user_id, 'eccw_history_' . $user_id, $history );

		$this->migrate_widget_keys( $saved );

		$kit_colors = CSS_Generator::get_kit_colors();
		$valid_ids  = array_keys( $kit_colors );

		foreach ( $draft as $widget_key => $widget_data ) {
			$widget_key = Element_Registry::normalize_key( $widget_key );

			if ( ! isset( $saved['widgets'][ $widget_key ] ) ) {
				$definition = Element_Registry::lookup( $widget_key );

				if ( null === $definition ) {
					continue;
				}

				$saved['widgets'][ $widget_key ] = array(
					'label'  => $definition['label'],
					'status' => 'configured',
					'slots'  => array(),
				);
			}

			if ( ! isset( $widget_data['slots'] ) || ! is_array( $widget_data['slots'] ) ) {
				continue;
			}

			$saved['widgets'][ $widget_key ]['slots'] = isset( $saved['widgets'][ $widget_key ]['slots'] ) && is_array( $saved['widgets'][ $widget_key ]['slots'] )
				? $saved['widgets'][ $widget_key ]['slots']
				: array();

			foreach ( $widget_data['slots'] as $slot_id => $slot_config ) {
				$slot_id = sanitize_key( (string) $slot_id );
				$hex     = isset( $slot_config['color'] ) ? strtolower( (string) $slot_config['color'] ) : '';

				$color_token = array_search( $hex, array_map( 'strtolower', $kit_colors ), true );

				if ( false === $color_token ) {
					// Custom color not present in the kit: store the raw hex
					// so the user's choice is preserved instead of being
					// silently coerced to the "text" token. Validate it first
					// so a malformed value can never reach the CSS output.
					$raw_hex = ltrim( $hex, '#' );

					if ( ! preg_match( '/^[0-9a-f]{6}$/', $raw_hex ) ) {
						continue;
					}

					$color_token = '#' . $raw_hex;
				}

				// Always write the slot, even for widgets created from the
				// live editor whose slot list was empty at creation time.
				$saved['widgets'][ $widget_key ]['slots'][ $slot_id ] = array( 'color' => $color_token );
			}

			if ( isset( $saved['widgets'][ $widget_key ] ) && 'configured' !== $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'configured';
			}
		}

		$saved['version']   = isset( $saved['version'] ) ? $saved['version'] + 1 : 2;
		$saved['last_scan'] = current_time( 'mysql' );

		update_option( 'eccw_colors_mappings', $saved );

		delete_transient( 'eccw_live_draft' );

		Cache_Manager::clear_css();

		wp_send_json_success(
			array(
				'message'     => __( 'Changes saved successfully.', 'color-changer-for-elementor' ),
				'new_version' => $saved['version'],
			)
		);
	}

	public function undo_action() {
		$this->verify_request();

		$user_id = get_current_user_id();
		$history = get_user_meta( $user_id, 'eccw_history_' . $user_id, true );

		if ( ! is_array( $history ) || empty( $history ) ) {
			wp_send_json_error( array( 'message' => __( 'No history to undo.', 'color-changer-for-elementor' ) ) );
		}

		$previous_state = array_shift( $history );

		update_option( 'eccw_colors_mappings', $previous_state );
		update_user_meta( $user_id, 'eccw_history_' . $user_id, $history );

		delete_transient( 'eccw_live_draft' );

		Cache_Manager::clear_css();

		wp_send_json_success( array( 'message' => __( 'Undo successful.', 'color-changer-for-elementor' ) ) );
	}

	public function generate_share() {
		$this->verify_request();

		$saved    = get_option( 'eccw_colors_mappings', array() );
		$mappings = isset( $saved['widgets'] ) ? $saved['widgets'] : array();

		if ( empty( $mappings ) ) {
			$draft = get_transient( 'eccw_live_draft' );
			if ( ! empty( $draft ) ) {
				$mappings = $draft;
			}
		}

		if ( empty( $mappings ) ) {
			wp_send_json_error( array( 'message' => __( 'No mappings to share.', 'color-changer-for-elementor' ) ) );
		}

		$nonce = wp_generate_password( 32, false );

		set_transient( 'eccw_share_' . $nonce, $mappings, HOUR_IN_SECONDS );

		$share_url = add_query_arg( 'eccw_share', $nonce, home_url( '/' ) );

		wp_send_json_success(
			array(
				'share_url' => $share_url,
				'nonce'     => $nonce,
			)
		);
	}

	public function reset_defaults() {
		$this->verify_request();

		$saved = get_option( 'eccw_colors_mappings', array() );

		$widget_types = array_keys( isset( $saved['widgets'] ) ? $saved['widgets'] : array() );

		$heuristic = new Heuristic_Engine();
		$mappings  = $heuristic->apply_defaults( $widget_types );

		$saved['widgets'] = $mappings;

		update_option( 'eccw_colors_mappings', $saved );

		delete_transient( 'eccw_live_draft' );

		Cache_Manager::clear_css();

		wp_send_json_success( array( 'message' => __( 'All elements reset to defaults.', 'color-changer-for-elementor' ) ) );
	}

	private function migrate_widget_keys( &$saved ) {
		if ( Mapping_Service::normalize_all( $saved ) ) {
			update_option( 'eccw_colors_mappings', $saved );
		}
	}

	private function sanitize_mappings( $raw ) {
		return Mapping_Service::sanitize_mappings( $raw );
	}
}
