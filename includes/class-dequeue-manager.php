<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Optionally removes the WooCommerce Blocks stylesheets.
 *
 * Off by default: the blocks sheet carries layout for block-based Cart and
 * Checkout, not just colour, so removing it can shift a working page. When
 * enabled it only acts if the plugin actually has mappings to replace the
 * sheets with — otherwise pages would be left unstyled.
 */
class Dequeue_Manager {

	/**
	 * Hook the front-end dequeue.
	 */
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
		if ( ! Settings::get( 'enabled' ) ) {
			return false;
		}

		$saved    = get_option( 'eccw_colors_mappings', array() );
		$mappings = isset( $saved['widgets'] ) ? $saved['widgets'] : array();

		return ! empty( $mappings );
	}

	/**
	 * Remove the WooCommerce Blocks stylesheets when enabled.
	 */
	public function dequeue_styles() {
		if ( is_admin() ) {
			return;
		}

		if ( ! self::should_dequeue() ) {
			return;
		}

		if ( ! Settings::get( 'dequeue_blocks' ) ) {
			return;
		}

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
