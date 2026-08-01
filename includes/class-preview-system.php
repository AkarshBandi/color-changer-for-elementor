<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Preview_System {

	public function init() {
		add_action( 'template_redirect', array( $this, 'maybe_start_preview' ), 1 );
		add_action( 'wp_ajax_wooce_apply_preview', array( $this, 'apply_preview' ) );
	}

	public static function is_preview_mode() {
		if ( ! isset( $_GET['wooce_preview'] ) || '1' !== $_GET['wooce_preview'] ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$draft = get_transient( 'wooce_preview_draft' );

		if ( empty( $draft ) ) {
			return false;
		}

		return true;
	}

	public function maybe_start_preview() {
		if ( ! self::is_preview_mode() ) {
			return;
		}

		if ( ! isset( $_COOKIE['wooce_preview_active'] ) || '1' !== $_COOKIE['wooce_preview_active'] ) {
			setcookie( 'wooce_preview_active', '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}

		ob_start();
		register_shutdown_function( array( $this, 'prepend_banner' ) );
	}

	public function prepend_banner() {
		$content = '';

		while ( ob_get_level() > 0 ) {
			$content = ob_get_clean() . $content;
		}

		if ( empty( $content ) ) {
			return;
		}

		$apply_url    = admin_url( 'admin-ajax.php?action=wooce_apply_preview&nonce=' . wp_create_nonce( 'wooce_admin_nonce' ) );
		$settings_url = admin_url( 'admin.php?page=wooce-settings' );

		$adminbar_offset = is_admin_bar_showing() ? '32px' : '0';

		echo '<div id="wooce-preview-banner" style="position:fixed;top:' . esc_attr( $adminbar_offset ) . ';left:0;right:0;z-index:999999;background:#2271b1;color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:center;gap:16px;font-size:14px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">';
		echo '<span>' . esc_html__( 'You are previewing unsaved color changes.', 'woocommerce-elementor-colors' ) . '</span>';
		echo '<a href="' . esc_url( $apply_url ) . '" style="background:#fff;color:#2271b1;padding:6px 16px;border-radius:3px;text-decoration:none;font-weight:600;">' . esc_html__( 'Apply These Changes', 'woocommerce-elementor-colors' ) . '</a>';
		echo '<a href="' . esc_url( $settings_url ) . '" style="color:#fff;text-decoration:underline;">' . esc_html__( 'Go Back to Settings', 'woocommerce-elementor-colors' ) . '</a>';
		echo '</div>';
		$spacer_height = is_admin_bar_showing() ? '84px' : '52px';
		echo '<div style="margin-top:' . esc_attr( $spacer_height ) . ';"></div>';

		echo $content;
	}

	public function apply_preview() {
		if ( ! check_ajax_referer( 'wooce_admin_nonce', 'nonce', false ) ) {
			wp_die( esc_html__( 'Security check failed.', 'woocommerce-elementor-colors' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) );
		}

		$draft = get_transient( 'wooce_preview_draft' );

		if ( empty( $draft ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$saved = get_option( 'wooce_colors_mappings', array() );

		if ( isset( $saved['widgets'] ) && is_array( $saved['widgets'] ) ) {
			$needs_update = false;
			foreach ( $saved['widgets'] as $key => $data ) {
				$normalized = Element_Registry::normalize_key( $key );
				if ( $normalized !== $key ) {
					if ( ! isset( $saved['widgets'][ $normalized ] ) ) {
						$saved['widgets'][ $normalized ] = $data;
					}
					unset( $saved['widgets'][ $key ] );
					$needs_update = true;
				}
			}
			if ( $needs_update ) {
				update_option( 'wooce_colors_mappings', $saved );
			}
		}

		foreach ( $draft as $widget_key => $widget_data ) {
			$widget_key = Element_Registry::normalize_key( $widget_key );

			if ( isset( $saved['widgets'][ $widget_key ] ) && isset( $widget_data['slots'] ) ) {
				foreach ( $widget_data['slots'] as $slot_id => $slot_config ) {
					if ( isset( $saved['widgets'][ $widget_key ]['slots'][ $slot_id ] ) ) {
						$saved['widgets'][ $widget_key ]['slots'][ $slot_id ]['color'] = $slot_config['color'];
					}
				}

				if ( 'configured' !== $saved['widgets'][ $widget_key ]['status'] ) {
					$saved['widgets'][ $widget_key ]['status'] = 'configured';
				}
			}
		}

		$saved['version']   = isset( $saved['version'] ) ? $saved['version'] + 1 : 2;
		$saved['last_scan'] = current_time( 'mysql' );

		update_option( 'wooce_colors_mappings', $saved );

		delete_transient( 'wooce_preview_draft' );

		if ( isset( $_COOKIE['wooce_preview_active'] ) ) {
			setcookie( 'wooce_preview_active', '', time() - HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}

		Cache_Manager::clear_all();

		wp_safe_redirect( home_url() );
		exit;
	}
}
