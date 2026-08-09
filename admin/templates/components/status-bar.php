<?php
/**
 * Elementor Colors - Status bar component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var int $eccw_kit_color_count Number of detected kit colors. */
/** @var int $eccw_mapping_count Number of mapped widgets. */
/** @var int $saved_version Current mapping version. */
?>
<div class="eccw-status-bar">
	<span class="eccw-status-item">
		<strong><?php echo esc_html__( 'Detected Colors:', 'color-changer-for-elementor' ); ?></strong>
		<?php echo esc_html( (int) $eccw_kit_color_count ); ?>
	</span>
	<span class="eccw-status-item">
		<strong><?php echo esc_html__( 'Widgets:', 'color-changer-for-elementor' ); ?></strong>
		<?php echo esc_html( (int) $eccw_mapping_count ); ?>
	</span>
	<span class="eccw-status-item">
		<strong><?php echo esc_html__( 'Version:', 'color-changer-for-elementor' ); ?></strong>
		<?php echo esc_html( (int) $saved_version ); ?>
	</span>
</div>
