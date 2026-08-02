<?php
/**
 * Advanced Settings - Header component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var int $new_count Number of widgets with status "new". */
?>
<div class="wooce-header">
	<h1><?php echo esc_html__( 'WooCommerce Elementor Colors', 'woocommerce-elementor-colors' ); ?></h1>
	<div class="wooce-actions">
		<button type="button" class="button wooce-rescan" data-wooce-action="rescan">
			<?php echo esc_html__( 'Rescan', 'woocommerce-elementor-colors' ); ?>
		</button>
		<button
			type="button"
			class="button wooce-dismiss-new"
			data-wooce-action="dismiss-new"
			<?php echo 0 === $new_count ? ' disabled' : ''; ?>
		>
			<?php echo esc_html__( 'Dismiss All New', 'woocommerce-elementor-colors' ); ?>
		</button>
		<button type="button" class="button button-secondary wooce-preview-on-site" data-wooce-action="preview-on-site">
			<?php echo esc_html__( 'Preview on Site', 'woocommerce-elementor-colors' ); ?>
		</button>
	</div>
</div>
