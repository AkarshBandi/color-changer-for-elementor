<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class AJAX_Handlers {

	public function init() {
		add_action( 'wp_ajax_wooce_save_preview', array( $this, 'save_preview' ) );
		add_action( 'wp_ajax_wooce_dismiss_new', array( $this, 'dismiss_new' ) );
		add_action( 'wp_ajax_wooce_rescan', array( $this, 'rescan' ) );

		add_action( 'wp_ajax_wooce_scan_progress', array( $this, 'scan_progress' ) );
		add_action( 'wp_ajax_wooce_live_update', array( $this, 'live_update' ) );
		add_action( 'wp_ajax_wooce_commit_live', array( $this, 'commit_live' ) );
		add_action( 'wp_ajax_wooce_undo_action', array( $this, 'undo_action' ) );
		add_action( 'wp_ajax_wooce_generate_share', array( $this, 'generate_share' ) );
	}

	private function verify_request() {
		if ( ! check_ajax_referer( 'wooce_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) ) );
		}
	}

	public function save_preview() {
		$this->verify_request();

		if ( ! isset( $_POST['mappings'] ) || ! is_array( $_POST['mappings'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No mappings provided.', 'woocommerce-elementor-colors' ) ) );
		}

		$mappings = $this->sanitize_mappings( $_POST['mappings'] );

		set_transient( 'wooce_preview_draft', $mappings, HOUR_IN_SECONDS );

		$preview_url = home_url( '/?wooce_preview=1' );

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_url = wc_get_page_permalink( 'shop' );

			if ( $shop_url ) {
				$preview_url = add_query_arg( 'wooce_preview', '1', $shop_url );
			}
		}

		wp_send_json_success( array( 'preview_url' => $preview_url ) );
	}

	public function dismiss_new() {
		$this->verify_request();

		if ( ! isset( $_POST['widgets'] ) || ! is_array( $_POST['widgets'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No widgets specified.', 'woocommerce-elementor-colors' ) ) );
		}

		$saved = get_option( 'wooce_colors_mappings', array() );

		$this->migrate_widget_keys( $saved );

		$dismissed = isset( $saved['dismissed_new'] ) ? $saved['dismissed_new'] : array();

		foreach ( $_POST['widgets'] as $widget_key ) {
			$widget_key = Element_Registry::normalize_key( sanitize_key( $widget_key ) );

			if ( ! in_array( $widget_key, $dismissed, true ) ) {
				$dismissed[] = $widget_key;
			}

			if ( isset( $saved['widgets'][ $widget_key ] ) && 'new' === $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'default';
			}
		}

		$saved['dismissed_new'] = $dismissed;

		update_option( 'wooce_colors_mappings', $saved );

		wp_send_json_success( array( 'message' => __( 'Dismissed.', 'woocommerce-elementor-colors' ) ) );
	}

	public function rescan() {
		$this->verify_request();

		$discovery    = new Discovery_Engine();
		$widget_types = $discovery->scan_all_pages();

		$saved = get_option( 'wooce_colors_mappings', array() );

		$needs_update = Mapping_Service::normalize_all( $saved );

		$new_widgets = Mapping_Service::merge_new_widgets( $saved, $widget_types );

		if ( ! empty( $new_widgets ) ) {
			$needs_update = true;
		}

		if ( $needs_update ) {
			update_option( 'wooce_colors_mappings', $saved );
		}

		Cache_Manager::clear_all();

		wp_send_json_success( array(
			'new_count' => count( $new_widgets ),
			'message'   => sprintf(
				/* translators: %d: number of new widgets found */
				__( 'Scan complete. Found %d new widget(s).', 'woocommerce-elementor-colors' ),
				count( $new_widgets )
			),
		) );
	}

	public function scan_progress() {
		$this->verify_request();

		$offset   = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch    = 5;

		$post_types = apply_filters( 'wooce_scan_post_types', array( 'page', 'product' ) );
		$post_types = array_map( 'sanitize_key', (array) $post_types );

		$posts = get_posts( array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'meta_key'       => '_elementor_data',
			'posts_per_page' => $batch,
			'offset'         => $offset,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

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
				$found[ $wk ]['count']++;
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

		wp_send_json_success( array(
			'done'        => $done,
			'next_offset' => $next_offset,
			'found'       => array_values( $found ),
			'batch_count' => count( $posts ),
		) );
	}

	private function walk_elements( $elements, &$widget_keys ) {
		Mapping_Service::walk_elements( $elements, $widget_keys );
	}

	public function live_update() {
		$this->verify_request();

		if ( ! isset( $_POST['widget_key'] ) || ! isset( $_POST['slot_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing parameters.', 'woocommerce-elementor-colors' ) ) );
		}

		$widget_key = sanitize_key( $_POST['widget_key'] );
		$slot_id    = sanitize_key( $_POST['slot_id'] );
		$remove     = isset( $_POST['remove'] ) && '1' === $_POST['remove'];

		$draft = get_transient( 'wooce_live_draft' );

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

			set_transient( 'wooce_live_draft', $draft, DAY_IN_SECONDS );

			wp_send_json_success( array(
				'removed' => true,
				'count'   => count( $draft ),
			) );
		}

		if ( ! isset( $_POST['hex'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing color.', 'woocommerce-elementor-colors' ) ) );
		}

		$hex = sanitize_hex_color( $_POST['hex'] );

		if ( ! $hex ) {
			wp_send_json_error( array( 'message' => __( 'Invalid color.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! isset( $draft[ $widget_key ] ) ) {
			$draft[ $widget_key ] = array( 'slots' => array() );
		}

		$draft[ $widget_key ]['slots'][ $slot_id ] = array( 'color' => $hex );

		set_transient( 'wooce_live_draft', $draft, DAY_IN_SECONDS );

		$css = CSS_Generator::build_single_css( $widget_key, $slot_id, $hex );

		wp_send_json_success( array(
			'css'   => $css,
			'count' => count( $draft ),
		) );
	}

	public function commit_live() {
		$this->verify_request();

		$draft = get_transient( 'wooce_live_draft' );

		if ( empty( $draft ) ) {
			wp_send_json_error( array( 'message' => __( 'No draft changes to save.', 'woocommerce-elementor-colors' ) ) );
		}

		$saved = get_option( 'wooce_colors_mappings', array() );

		$user_id    = get_current_user_id();
		$history    = get_user_meta( $user_id, 'wooce_history_' . $user_id, true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		array_unshift( $history, $saved );
		$history = array_slice( $history, 0, 10 );

		update_user_meta( $user_id, 'wooce_history_' . $user_id, $history );

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

			foreach ( $widget_data['slots'] as $slot_id => $slot_config ) {
				$hex = isset( $slot_config['color'] ) ? $slot_config['color'] : '';

				$color_token = array_search( strtolower( $hex ), array_map( 'strtolower', $kit_colors ), true );

				if ( false === $color_token ) {
					$color_token = 'text';
				}

				if ( isset( $saved['widgets'][ $widget_key ]['slots'][ $slot_id ] ) ) {
					$saved['widgets'][ $widget_key ]['slots'][ $slot_id ]['color'] = $color_token;
				}
			}

			if ( isset( $saved['widgets'][ $widget_key ] ) && 'configured' !== $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'configured';
			}
		}

		$saved['version']   = isset( $saved['version'] ) ? $saved['version'] + 1 : 2;
		$saved['last_scan'] = current_time( 'mysql' );

		update_option( 'wooce_colors_mappings', $saved );

		delete_transient( 'wooce_live_draft' );

		Cache_Manager::clear_all();

		wp_send_json_success( array(
			'message'      => __( 'Changes saved successfully.', 'woocommerce-elementor-colors' ),
			'new_version'  => $saved['version'],
		) );
	}

	public function undo_action() {
		$this->verify_request();

		$user_id = get_current_user_id();
		$history = get_user_meta( $user_id, 'wooce_history_' . $user_id, true );

		if ( ! is_array( $history ) || empty( $history ) ) {
			wp_send_json_error( array( 'message' => __( 'No history to undo.', 'woocommerce-elementor-colors' ) ) );
		}

		$previous_state = array_shift( $history );

		update_option( 'wooce_colors_mappings', $previous_state );
		update_user_meta( $user_id, 'wooce_history_' . $user_id, $history );

		delete_transient( 'wooce_live_draft' );

		Cache_Manager::clear_all();

		wp_send_json_success( array( 'message' => __( 'Undo successful.', 'woocommerce-elementor-colors' ) ) );
	}

	public function generate_share() {
		$this->verify_request();

		$saved       = get_option( 'wooce_colors_mappings', array() );
		$mappings    = isset( $saved['widgets'] ) ? $saved['widgets'] : array();

		if ( empty( $mappings ) ) {
			$draft = get_transient( 'wooce_live_draft' );
			if ( ! empty( $draft ) ) {
				$mappings = $draft;
			}
		}

		if ( empty( $mappings ) ) {
			wp_send_json_error( array( 'message' => __( 'No mappings to share.', 'woocommerce-elementor-colors' ) ) );
		}

		$nonce = wp_generate_password( 32, false );

		set_transient( 'wooce_share_' . $nonce, $mappings, HOUR_IN_SECONDS );

		$share_url = add_query_arg( 'wooce_share', $nonce, home_url( '/' ) );

		wp_send_json_success( array(
			'share_url' => $share_url,
			'nonce'     => $nonce,
		) );
	}

	private function migrate_widget_keys( &$saved ) {
		if ( Mapping_Service::normalize_all( $saved ) ) {
			update_option( 'wooce_colors_mappings', $saved );
		}
	}

	private function sanitize_mappings( $raw ) {
		return Mapping_Service::sanitize_mappings( $raw );
	}
}
