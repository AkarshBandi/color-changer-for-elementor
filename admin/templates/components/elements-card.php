<?php
/**
 * Elementor Colors - Element Colors card component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $mappings All widget mappings. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $defaults Per-slot default color tokens. */
?>
<div class="eccw-card eccw-card-elements" data-eccw-component="elements">
	<div class="eccw-card-header">
		<h2><?php echo esc_html__( 'Element Colors', 'color-changer-for-elementor' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Map each WooCommerce element to an Elementor global color.', 'color-changer-for-elementor' ); ?>
		</p>
	</div>
	<div class="eccw-card-body">
		<table class="wp-list-table widefat fixed striped eccw-table">
			<thead>
				<tr>
					<th style="width:20%;"><?php echo esc_html__( 'Element', 'color-changer-for-elementor' ); ?></th>
					<th style="width:10%;"><?php echo esc_html__( 'Status', 'color-changer-for-elementor' ); ?></th>
					<th style="width:50%;"><?php echo esc_html__( 'Colors by State', 'color-changer-for-elementor' ); ?></th>
					<th style="width:20%;"><?php echo esc_html__( 'Preview', 'color-changer-for-elementor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $mappings as $eccw_widget_key => $eccw_widget_data ) : ?>
					<?php
					include ECCw_PATH . 'admin/templates/components/element-row.php';
					?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
