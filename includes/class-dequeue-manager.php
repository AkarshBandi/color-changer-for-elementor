<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Dequeue_Manager {

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_styles' ), 999 );
	}

	public function dequeue_styles() {
		if ( is_admin() ) {
			return;
		}

		$settings = get_option( 'wooce_dequeue_settings', array() );

		$dequeue_core   = isset( $settings['dequeue_core'] ) ? $settings['dequeue_core'] : 'yes';
		$dequeue_blocks = isset( $settings['dequeue_blocks'] ) ? $settings['dequeue_blocks'] : 'yes';

		if ( 'yes' === $dequeue_core ) {
			$core_handles = array(
				'woocommerce-general',
				'woocommerce-layout',
				'woocommerce-smallscreen',
			);

			foreach ( $core_handles as $handle ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}

		if ( 'yes' === $dequeue_blocks ) {
			$block_handles = array(
				'wc-blocks-style',
				'wc-blocks-vendors-style',
			);

			foreach ( $block_handles as $handle ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}
}
