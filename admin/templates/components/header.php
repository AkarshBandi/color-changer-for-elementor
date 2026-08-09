<?php
/**
 * Elementor Colors - Header component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var int $new_count Number of widgets with status "new". */
?>
<div class="eccw-header">
	<h1><?php echo esc_html__( 'Color Changer for Elementor and WooCommerce', 'color-changer-for-elementor' ); ?></h1>
	<div class="eccw-actions">
		<button type="button" class="button eccw-rescan" data-eccw-action="rescan">
			<?php echo esc_html__( 'Rescan', 'color-changer-for-elementor' ); ?>
		</button>
		<button
			type="button"
			class="button eccw-dismiss-new"
			data-eccw-action="dismiss-new"
			<?php echo 0 === $new_count ? ' disabled' : ''; ?>
		>
			<?php echo esc_html__( 'Dismiss All New', 'color-changer-for-elementor' ); ?>
		</button>
		<button type="button" class="button button-secondary eccw-preview-on-site" data-eccw-action="preview-on-site">
			<?php echo esc_html__( 'Preview on Site', 'color-changer-for-elementor' ); ?>
		</button>
	</div>
</div>
