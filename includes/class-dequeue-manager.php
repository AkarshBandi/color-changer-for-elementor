<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Dequeue_Manager {

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_styles' ), 999 );
	}

	/**
	 * Whether core/block WooCommerce styles should be removed on the frontend.
	 *
	 * Only dequeues when the plugin actually has mappings to replace them
	 * with; otherwise removing core styles would leave pages unstyled.
	 *
	 * @return bool
	 */
	public static function should_dequeue() {
		$saved    = get_option( 'wooce_colors_mappings', array() );
		$mappings = isset( $saved['widgets'] ) ? $saved['widgets'] : array();

		return ! empty( $mappings );
	}

	public function dequeue_styles() {
		if ( is_admin() ) {
			return;
		}

		if ( ! self::should_dequeue() ) {
			return;
		}

		$settings = get_option( 'wooce_dequeue_settings', array() );

		$dequeue_blocks = isset( $settings['dequeue_blocks'] ) ? $settings['dequeue_blocks'] : 'yes';

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
