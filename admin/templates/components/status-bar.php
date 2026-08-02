<?php
/**
 * Advanced Settings - Status bar component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var int $kit_color_count Number of detected kit colors. */
/** @var int $mapping_count Number of mapped widgets. */
/** @var int $saved_version Current mapping version. */
?>
<div class="wooce-status-bar">
	<span class="wooce-status-item">
		<strong><?php echo esc_html__( 'Detected Colors:', 'woocommerce-elementor-colors' ); ?></strong>
		<?php echo esc_html( (int) $kit_color_count ); ?>
	</span>
	<span class="wooce-status-item">
		<strong><?php echo esc_html__( 'Widgets:', 'woocommerce-elementor-colors' ); ?></strong>
		<?php echo esc_html( (int) $mapping_count ); ?>
	</span>
	<span class="wooce-status-item">
		<strong><?php echo esc_html__( 'Version:', 'woocommerce-elementor-colors' ); ?></strong>
		<?php echo esc_html( (int) $saved_version ); ?>
	</span>
</div>
