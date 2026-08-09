<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Admin_Interface {

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_eccw_save_settings', array( $this, 'handle_form_save' ) );

		$plugin_file = plugin_basename( ECCw_PATH . 'color-changer-for-elementor.php' );
		add_filter( 'plugin_action_links_' . $plugin_file, array( $this, 'add_settings_link' ) );
	}

	public function add_submenu() {
		add_submenu_page(
			'woocommerce',
			__( 'Elementor Colors', 'color-changer-for-elementor' ),
			__( 'Elementor Colors', 'color-changer-for-elementor' ),
			'manage_options',
			'eccw-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=eccw-settings' ) . '">' .
			esc_html__( 'Settings', 'color-changer-for-elementor' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( 'woocommerce_page_eccw-settings' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'eccw-admin-base',
			ECCw_URL . 'admin/css/base.css',
			array(),
			ECCw_VERSION
		);

		foreach ( array( 'dequeue', 'elements', 'gallery' ) as $component ) {
			wp_enqueue_style(
				'eccw-admin-' . $component,
				ECCw_URL . 'admin/css/components/' . $component . '.css',
				array( 'eccw-admin-base' ),
				ECCw_VERSION
			);
		}

		wp_enqueue_script(
			'eccw-admin-settings',
			ECCw_URL . 'admin/js/admin-settings.js',
			array( 'jquery' ),
			ECCw_VERSION,
			true
		);

		foreach ( array( 'element-row', 'gallery', 'dequeue-card', 'actions' ) as $component ) {
			wp_enqueue_script(
				'eccw-admin-' . $component,
				ECCw_URL . 'admin/js/components/' . $component . '.js',
				array( 'eccw-admin-settings' ),
				ECCw_VERSION,
				true
			);
		}

		$kit_colors = CSS_Generator::get_kit_colors();
		$colors_js  = array();

		foreach ( $kit_colors as $id => $hex ) {
			$colors_js[] = array(
				'id'    => $id,
				'label' => ucfirst( str_replace( '_', ' ', $id ) ),
				'hex'   => $hex,
			);
		}

		$defaults  = $this->get_slot_defaults();
		$saved     = get_option( 'eccw_colors_mappings', array() );
		$mappings  = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$new_count = $this->count_new_widgets( $mappings );

		wp_localize_script(
			'eccw-admin-settings',
			'eccwData',
			array(
				'kitColors' => $colors_js,
				'defaults'  => $defaults,
				'newCount'  => $new_count,
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'eccw_admin_nonce' ),
				'siteUrl'   => home_url(),
			)
		);
	}

	/**
	 * Build a map of slot_id => default color token from the registry.
	 *
	 * @return array Associative array keyed by slot_id.
	 */
	private function get_slot_defaults() {
		$heuristic = new Heuristic_Engine();
		$defaults  = array();

		foreach ( Element_Registry::get_registry() as $definition ) {
			foreach ( $definition['slots'] as $slot ) {
				$defaults[ $slot['slot_id'] ] = $heuristic->determine_color( $slot['slot_id'] );
			}
		}

		return $defaults;
	}

	/**
	 * Count widgets whose status is "new".
	 *
	 * @param array $mappings Widget mappings.
	 * @return int
	 */
	private function count_new_widgets( $mappings ) {
		$count = 0;

		foreach ( $mappings as $widget_data ) {
			if ( isset( $widget_data['status'] ) && 'new' === $widget_data['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved            = get_option( 'eccw_colors_mappings', array() );
		$mappings         = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$dequeue_settings = get_option( 'eccw_dequeue_settings', array() );
		$kit_colors       = CSS_Generator::get_kit_colors();
		$saved_version    = isset( $saved['version'] ) ? $saved['version'] : 1;
		$defaults         = $this->get_slot_defaults();
		$new_count        = $this->count_new_widgets( $mappings );
		$dequeue_disabled = empty( $mappings );

		$shop_page       = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : false;
		$shop_url        = $shop_page ? $shop_page : home_url();
		$live_editor_url = add_query_arg( 'eccw_editor', '1', $shop_url );

		include ECCw_PATH . 'admin/templates/settings-page.php';
	}

	public function handle_form_save() {
		if ( ! isset( $_POST['eccw_save_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['eccw_save_nonce'] ) ), 'eccw_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'color-changer-for-elementor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'color-changer-for-elementor' ) );
		}

		$dequeue_settings = array(
			'dequeue_blocks' => isset( $_POST['eccw_dequeue_blocks'] ) && 'yes' === $_POST['eccw_dequeue_blocks'] ? 'yes' : 'no',
		);

		update_option( 'eccw_dequeue_settings', $dequeue_settings );

		$saved = get_option( 'eccw_colors_mappings', array() );

		if ( Mapping_Service::normalize_all( $saved ) ) {
			update_option( 'eccw_colors_mappings', $saved );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values validated against kit color IDs below.
		$posted_colors = isset( $_POST['eccw_colors'] ) && is_array( $_POST['eccw_colors'] )
			? wp_unslash( $_POST['eccw_colors'] )
			: array();
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$kit_colors = CSS_Generator::get_kit_colors();
		$valid_ids  = array_keys( $kit_colors );

		foreach ( $posted_colors as $widget_key => $slots ) {
			$widget_key = \ElementorColorChanger\Element_Registry::normalize_key( sanitize_key( $widget_key ) );

			if ( ! isset( $saved['widgets'][ $widget_key ] ) ) {
				continue;
			}

			if ( ! is_array( $slots ) ) {
				continue;
			}

			$changed = false;

			foreach ( $slots as $slot_id => $color_value ) {
				$color_value = strtolower( (string) $color_value );

				// Accept either a kit color token or a raw hex value
				// (custom colors chosen in the Live Editor).
				if ( ! in_array( $color_value, $valid_ids, true ) ) {
					$hex = ltrim( $color_value, '#' );

					if ( ! preg_match( '/^[0-9a-f]{6}$/', $hex ) ) {
						continue;
					}

					$color_value = '#' . $hex;
				}

				if ( ! isset( $saved['widgets'][ $widget_key ]['slots'][ $slot_id ] ) ) {
					continue;
				}

				$saved['widgets'][ $widget_key ]['slots'][ $slot_id ]['color'] = $color_value;
				$changed = true;
			}

			if ( $changed && 'configured' !== $saved['widgets'][ $widget_key ]['status'] ) {
				$saved['widgets'][ $widget_key ]['status'] = 'configured';
			}
		}

		update_option( 'eccw_colors_mappings', $saved );

		Cache_Manager::clear_css();

		wp_safe_redirect(
			add_query_arg( 'saved', '1', admin_url( 'admin.php?page=eccw-settings' ) )
		);

		exit;
	}
}
