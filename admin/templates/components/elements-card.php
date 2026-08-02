<?php
/**
 * Advanced Settings - Element Colors card component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $mappings All widget mappings. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $defaults Per-slot default color tokens. */
?>
<div class="wooce-card wooce-card-elements" data-wooce-component="elements">
	<div class="wooce-card-header">
		<h2><?php echo esc_html__( 'Element Colors', 'woocommerce-elementor-colors' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Map each WooCommerce element to an Elementor global color.', 'woocommerce-elementor-colors' ); ?>
		</p>
	</div>
	<div class="wooce-card-body">
		<table class="wp-list-table widefat fixed striped wooce-table">
			<thead>
				<tr>
					<th style="width:20%;"><?php echo esc_html__( 'Element', 'woocommerce-elementor-colors' ); ?></th>
					<th style="width:10%;"><?php echo esc_html__( 'Status', 'woocommerce-elementor-colors' ); ?></th>
					<th style="width:50%;"><?php echo esc_html__( 'Colors by State', 'woocommerce-elementor-colors' ); ?></th>
					<th style="width:20%;"><?php echo esc_html__( 'Preview', 'woocommerce-elementor-colors' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $mappings as $widget_key => $widget_data ) : ?>
					<?php
					include WOOEC_PATH . 'admin/templates/components/element-row.php';
					?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
