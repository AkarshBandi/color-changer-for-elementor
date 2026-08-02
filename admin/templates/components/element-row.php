<?php
/**
 * Advanced Settings - Element row component.
 *
 * Renders one row of the Element Colors table.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var string $widget_key Current widget key. */
/** @var array $widget_data Current widget mapping. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $defaults Per-slot default color tokens. */

$definition = WooElementorColors\Element_Registry::lookup( $widget_key );
$row_status = isset( $widget_data['status'] ) ? $widget_data['status'] : 'default';
$slots      = isset( $widget_data['slots'] ) ? $widget_data['slots'] : array();

$rendered_slots = array();

foreach ( $slots as $slot_id => $slot_config ) {
	$slot_label = $slot_id;

	if ( $definition && isset( $definition['slots'] ) ) {
		foreach ( $definition['slots'] as $def_slot ) {
			if ( $def_slot['slot_id'] === $slot_id ) {
				$slot_label = $def_slot['label'];
				break;
			}
		}
	}

	$current_color = isset( $slot_config['color'] ) ? $slot_config['color'] : 'text';
	$is_button     = false !== strpos( $slot_id, 'button' ) || false !== strpos( $slot_id, 'proceed' ) || false !== strpos( $slot_id, 'place_order' ) || false !== strpos( $slot_id, 'update_cart' ) || false !== strpos( $slot_id, 'loop' );

	$rendered_slots[] = array(
		'slot_id'       => $slot_id,
		'slot_label'    => $slot_label,
		'current_color' => $current_color,
		'default_color' => isset( $defaults[ $slot_id ] ) ? $defaults[ $slot_id ] : 'text',
		'color_hex'     => isset( $kit_colors[ $current_color ] ) ? $kit_colors[ $current_color ] : '#000',
		'is_button'     => $is_button,
	);
}
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
			<?php foreach ( $rendered_slots as $rs ) : ?>
				<div class="wooce-color-row">
					<label class="wooce-color-label"><?php echo esc_html( $rs['slot_label'] ); ?></label>
					<select class="wooce-color-select"
						name="wooce_colors[<?php echo esc_attr( $widget_key ); ?>][<?php echo esc_attr( $rs['slot_id'] ); ?>]"
						data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
						data-state="<?php echo esc_attr( $rs['slot_id'] ); ?>">
						<?php foreach ( $kit_colors as $color_id => $color_hex ) : ?>
							<option value="<?php echo esc_attr( $color_id ); ?>" <?php selected( $rs['current_color'], $color_id ); ?>>
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $color_id ) ) . ' - ' . $color_hex ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="wooce-swatch" style="background-color:<?php echo esc_attr( $rs['color_hex'] ); ?>;"></span>
					<button type="button" class="button-link wooce-reset-link" data-default="<?php echo esc_attr( $rs['default_color'] ); ?>">
						<?php echo esc_html__( 'Reset', 'woocommerce-elementor-colors' ); ?>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
	</td>
	<td>
		<div class="wooce-preview-chips">
			<?php foreach ( $rendered_slots as $rs ) : ?>
				<span class="wooce-preview-chip"
					data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
					data-state="<?php echo esc_attr( $rs['slot_id'] ); ?>"
					data-is-button="<?php echo $rs['is_button'] ? '1' : '0'; ?>"
					title="<?php echo esc_attr( $rs['slot_label'] . ': ' . $rs['color_hex'] ); ?>"
					style="background-color:<?php echo esc_attr( $rs['color_hex'] ); ?>;"></span>
			<?php endforeach; ?>
		</div>
	</td>
</tr>
