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
			<?php include WOOEC_PATH . 'admin/templates/element-row.php'; ?>
		<?php endforeach; ?>
	</tbody>
</table>
