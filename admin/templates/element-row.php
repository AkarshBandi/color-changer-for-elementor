<?php
$definition = WooElementorColors\Element_Registry::lookup( $widget_key );
$row_status = isset( $widget_data['status'] ) ? $widget_data['status'] : 'default';
$slots      = isset( $widget_data['slots'] ) ? $widget_data['slots'] : array();
?>
<tr data-widget-key="<?php echo esc_attr( $widget_key ); ?>">
	<td>
		<span class="wooce-widget-label"><?php echo esc_html( $widget_data['label'] ); ?></span>
	</td>
	<td>
		<span class="wooce-badge wooce-badge-<?php echo esc_attr( $row_status ); ?>">
			<?php echo esc_html( $row_status ); ?>
		</span>
	</td>
	<td>
		<div class="wooce-preview-chip-wrapper">
			<?php foreach ( $slots as $slot_id => $slot_config ) : ?>
				<?php
				$slot_label    = $slot_id;
				$current_color = isset( $slot_config['color'] ) ? $slot_config['color'] : 'text';

				if ( $definition && isset( $definition['slots'] ) ) {
					foreach ( $definition['slots'] as $def_slot ) {
						if ( $def_slot['slot_id'] === $slot_id ) {
							$slot_label = $def_slot['label'];
							break;
						}
					}
				}
				?>
				<div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
					<label style="min-width:80px;font-size:12px;color:#50575e;"><?php echo esc_html( $slot_label ); ?></label>
					<select class="wooce-color-select"
						name="wooce_colors[<?php echo esc_attr( $widget_key ); ?>][<?php echo esc_attr( $slot_id ); ?>]"
						data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
						data-state="<?php echo esc_attr( $slot_id ); ?>">
						<?php foreach ( $kit_colors as $color_id => $color_hex ) : ?>
							<option value="<?php echo esc_attr( $color_id ); ?>" <?php selected( $current_color, $color_id ); ?>>
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $color_id ) ) . ' - ' . $color_hex ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="wooce-swatch" style="background-color:<?php echo esc_attr( isset( $kit_colors[ $current_color ] ) ? $kit_colors[ $current_color ] : '#000' ); ?>;"></span>
				</div>
			<?php endforeach; ?>
		</div>
	</td>
	<td>
		<div style="display:flex;gap:4px;flex-wrap:wrap;">
			<?php foreach ( $slots as $slot_id => $slot_config ) : ?>
				<?php
				$current_color = isset( $slot_config['color'] ) ? $slot_config['color'] : 'text';
				$color_hex     = isset( $kit_colors[ $current_color ] ) ? $kit_colors[ $current_color ] : '#000';
				$is_hover      = false !== strpos( $slot_id, 'hover' );
				$chip_style    = 'background-color:' . esc_attr( $color_hex ) . ';';
				?>
				<span class="wooce-preview-chip"
					data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
					data-state="<?php echo esc_attr( $slot_id ); ?>"
					title="<?php echo esc_attr( $slot_label . ': ' . $color_hex ); ?>"
					style="<?php echo esc_attr( $chip_style ); ?>"></span>
			<?php endforeach; ?>
		</div>
	</td>
</tr>
