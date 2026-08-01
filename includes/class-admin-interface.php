<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Admin_Interface {

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_wooce_save_settings', array( $this, 'handle_form_save' ) );

		$plugin_file = plugin_basename( WOOEC_PATH . 'woocommerce-elementor-colors.php' );
		add_filter( 'plugin_action_links_' . $plugin_file, array( $this, 'add_settings_link' ) );
	}

	public function add_submenu() {
		add_submenu_page(
			'woocommerce',
			__( 'Advanced Settings', 'woocommerce-elementor-colors' ),
			__( '⚙️ Advanced Settings', 'woocommerce-elementor-colors' ),
			'manage_options',
			'wooce-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wooce-settings' ) . '">' .
			esc_html__( 'Settings', 'woocommerce-elementor-colors' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( 'woocommerce_page_wooce-settings' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wooce-admin',
			WOOEC_URL . 'admin/css/admin-style.css',
			array(),
			WOOEC_VERSION
		);

		wp_enqueue_script(
			'wooce-admin-preview',
			WOOEC_URL . 'admin/js/admin-preview.js',
			array( 'jquery' ),
			WOOEC_VERSION,
			true
		);

		$kit_colors = CSS_Generator::get_kit_colors();
		$colors_js  = array();

		foreach ( $kit_colors as $id => $hex ) {
			$colors_js[] = array(
				'id'    => $id,
				'label' => ucfirst( str_replace( '_', ' ', $id ) ),
				'hex'   => $hex,
			);
		}

		wp_localize_script(
			'wooce-admin-preview',
			'wooceData',
			array(
				'kitColors' => $colors_js,
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wooce_admin_nonce' ),
				'siteUrl'   => home_url(),
			)
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved            = get_option( 'wooce_colors_mappings', array() );
		$mappings         = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$dequeue_settings = get_option( 'wooce_dequeue_settings', array() );
		$kit_colors       = CSS_Generator::get_kit_colors();
		$saved_version    = isset( $saved['version'] ) ? $saved['version'] : 1;

		include WOOEC_PATH . 'admin/templates/settings-page.php';
	}

	public function handle_form_save() {
		if ( ! isset( $_POST['wooce_save_nonce'] ) || ! wp_verify_nonce( $_POST['wooce_save_nonce'], 'wooce_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'woocommerce-elementor-colors' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) );
		}

		$dequeue_settings = array(
			'dequeue_core'   => isset( $_POST['wooce_dequeue_core'] ) && 'yes' === $_POST['wooce_dequeue_core'] ? 'yes' : 'no',
			'dequeue_blocks' => isset( $_POST['wooce_dequeue_blocks'] ) && 'yes' === $_POST['wooce_dequeue_blocks'] ? 'yes' : 'no',
		);

		update_option( 'wooce_dequeue_settings', $dequeue_settings );

		$saved = get_option( 'wooce_colors_mappings', array() );

		if ( Mapping_Service::normalize_all( $saved ) ) {
			update_option( 'wooce_colors_mappings', $saved );
		}

		if ( isset( $_POST['wooce_colors'] ) && is_array( $_POST['wooce_colors'] ) ) {
			$kit_colors = CSS_Generator::get_kit_colors();
			$valid_ids  = array_keys( $kit_colors );

			foreach ( $_POST['wooce_colors'] as $widget_key => $slots ) {
				$widget_key = \WooElementorColors\Element_Registry::normalize_key( sanitize_key( $widget_key ) );

				if ( ! isset( $saved['widgets'][ $widget_key ] ) ) {
					continue;
				}

				if ( ! is_array( $slots ) ) {
					continue;
				}

				$changed = false;

				foreach ( $slots as $slot_id => $color_token ) {
					$color_token = sanitize_key( $color_token );

					if ( ! in_array( $color_token, $valid_ids, true ) ) {
						continue;
					}

					if ( ! isset( $saved['widgets'][ $widget_key ]['slots'][ $slot_id ] ) ) {
						continue;
					}

					$saved['widgets'][ $widget_key ]['slots'][ $slot_id ]['color'] = $color_token;
					$changed = true;
				}

				if ( $changed && 'configured' !== $saved['widgets'][ $widget_key ]['status'] ) {
					$saved['widgets'][ $widget_key ]['status'] = 'configured';
				}
			}
		}

		update_option( 'wooce_colors_mappings', $saved );

		Cache_Manager::clear_all();

		wp_safe_redirect(
			add_query_arg( 'saved', '1', admin_url( 'admin.php?page=wooce-settings' ) )
		);

		exit;
	}
}
