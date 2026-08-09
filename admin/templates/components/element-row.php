<?php
/**
 * Elementor Colors - Element row component.
 *
 * Renders one row of the Element Colors table.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var string $eccw_widget_key Current widget key. */
/** @var array $eccw_widget_data Current widget mapping. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $defaults Per-slot default color tokens. */

$eccw_definition = ElementorColorChanger\Element_Registry::lookup( $eccw_widget_key );
$eccw_row_status = isset( $eccw_widget_data['status'] ) ? $eccw_widget_data['status'] : 'default';
$eccw_slots      = isset( $eccw_widget_data['slots'] ) ? $eccw_widget_data['slots'] : array();

// Prefer the friendly registry label so renames propagate to the table
// without requiring users to re-save their mappings.
$eccw_widget_label = ( $eccw_definition && isset( $eccw_definition['label'] ) ) ? $eccw_definition['label'] : ( isset( $eccw_widget_data['label'] ) ? $eccw_widget_data['label'] : $eccw_widget_key );

$eccw_rendered_slots = array();

foreach ( $eccw_slots as $eccw_slot_id => $eccw_slot_config ) {
	$eccw_slot_label = $eccw_slot_id;

	if ( $eccw_definition && isset( $eccw_definition['slots'] ) ) {
		foreach ( $eccw_definition['slots'] as $eccw_def_slot ) {
			if ( $eccw_def_slot['slot_id'] === $eccw_slot_id ) {
				$eccw_slot_label = $eccw_def_slot['label'];
				break;
			}
		}
	}

	$eccw_current_color = isset( $eccw_slot_config['color'] ) ? $eccw_slot_config['color'] : 'text';
	$eccw_is_button     = false !== strpos( $eccw_slot_id, 'button' ) || false !== strpos( $eccw_slot_id, 'proceed' ) || false !== strpos( $eccw_slot_id, 'place_order' ) || false !== strpos( $eccw_slot_id, 'update_cart' ) || false !== strpos( $eccw_slot_id, 'loop' );

	$eccw_is_hex    = is_string( $eccw_current_color ) && preg_match( '/^#?[0-9a-fA-F]{6}$/', $eccw_current_color );
	$eccw_hex_value = '';

	if ( $eccw_is_hex ) {
		$eccw_hex_value = '#' . strtolower( ltrim( $eccw_current_color, '#' ) );
	}

	$eccw_rendered_slots[] = array(
		'slot_id'       => $eccw_slot_id,
		'slot_label'    => $eccw_slot_label,
		'current_color' => $eccw_current_color,
		'default_color' => isset( $defaults[ $eccw_slot_id ] ) ? $defaults[ $eccw_slot_id ] : 'text',
		'color_hex'     => $eccw_is_hex ? $eccw_hex_value : ( isset( $kit_colors[ $eccw_current_color ] ) ? $kit_colors[ $eccw_current_color ] : '#000' ),
		'is_button'     => $eccw_is_button,
		'is_hex'        => $eccw_is_hex,
		'hex_value'     => $eccw_hex_value,
	);
}
?>
<tr data-widget-key="<?php echo esc_attr( $eccw_widget_key ); ?>">
	<td>
		<span class="eccw-widget-label"><?php echo esc_html( $eccw_widget_label ); ?></span>
	</td>
	<td>
		<span class="eccw-badge eccw-badge-<?php echo esc_attr( $eccw_row_status ); ?>">
			<?php echo esc_html( $eccw_row_status ); ?>
		</span>
	</td>
	<td>
		<div class="eccw-preview-chip-wrapper">
			<?php foreach ( $eccw_rendered_slots as $eccw_rs ) : ?>
				<div class="eccw-color-row">
					<label class="eccw-color-label"><?php echo esc_html( $eccw_rs['slot_label'] ); ?></label>
					<select class="eccw-color-select"
						name="eccw_colors[<?php echo esc_attr( $eccw_widget_key ); ?>][<?php echo esc_attr( $eccw_rs['slot_id'] ); ?>]"
						data-widget-key="<?php echo esc_attr( $eccw_widget_key ); ?>"
						data-state="<?php echo esc_attr( $eccw_rs['slot_id'] ); ?>">
						<?php foreach ( $kit_colors as $eccw_color_id => $eccw_color_hex ) : ?>
							<option value="<?php echo esc_attr( $eccw_color_id ); ?>" <?php selected( $eccw_rs['current_color'], $eccw_color_id ); ?>>
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $eccw_color_id ) ) . ' - ' . $eccw_color_hex ); ?>
							</option>
						<?php endforeach; ?>
						<?php if ( $eccw_rs['is_hex'] ) : ?>
							<option value="<?php echo esc_attr( $eccw_rs['hex_value'] ); ?>" selected="selected">
								<?php echo esc_html( __( 'Custom', 'color-changer-for-elementor' ) . ' - ' . strtoupper( $eccw_rs['hex_value'] ) ); ?>
							</option>
						<?php endif; ?>
					</select>
					<span class="eccw-swatch" style="background-color:<?php echo esc_attr( $eccw_rs['color_hex'] ); ?>;"></span>
					<button type="button" class="button-link eccw-reset-link" data-default="<?php echo esc_attr( $eccw_rs['default_color'] ); ?>">
						<?php echo esc_html__( 'Reset', 'color-changer-for-elementor' ); ?>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
	</td>
	<td>
		<div class="eccw-preview-chips">
			<?php foreach ( $eccw_rendered_slots as $eccw_rs ) : ?>
				<span class="eccw-preview-chip"
					data-widget-key="<?php echo esc_attr( $eccw_widget_key ); ?>"
					data-state="<?php echo esc_attr( $eccw_rs['slot_id'] ); ?>"
					data-is-button="<?php echo $eccw_rs['is_button'] ? '1' : '0'; ?>"
					title="<?php echo esc_attr( $eccw_rs['slot_label'] . ': ' . $eccw_rs['color_hex'] ); ?>"
					style="background-color:<?php echo esc_attr( $eccw_rs['color_hex'] ); ?>;"></span>
			<?php endforeach; ?>
		</div>
	</td>
</tr>
