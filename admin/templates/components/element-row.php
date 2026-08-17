<?php
/**
 * Store Design — one store element.
 *
 * The main colour is the whole card. The remaining states sit behind a
 * disclosure, because on this site twelve of the thirty-seven slots are hover,
 * focus and disabled shades that the plugin can derive on its own.
 *
 * Field names are unchanged from the table this replaced (`eccw_colors[key][slot]`)
 * so the save handler and every stored mapping keep working untouched.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var string $eccw_widget_key Current widget key. */
/** @var array $eccw_widget_data Current widget mapping. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $kit_labels Token id => human label. */
/** @var array $descriptions Widget key => plain description. */
/** @var array $defaults Per-slot default color tokens. */

$eccw_definition = ElementorColorChanger\Element_Registry::lookup( $eccw_widget_key );
$eccw_slots      = isset( $eccw_widget_data['slots'] ) ? $eccw_widget_data['slots'] : array();

$eccw_label = ( $eccw_definition && isset( $eccw_definition['label'] ) )
	? $eccw_definition['label']
	: ( isset( $eccw_widget_data['label'] ) ? $eccw_widget_data['label'] : $eccw_widget_key );

$eccw_description = isset( $descriptions[ $eccw_widget_key ] ) ? $descriptions[ $eccw_widget_key ] : '';

// Derived states are not offered as choices: the plugin works them out, and a
// control that is ignored is worse than no control.
$eccw_authored = ( $eccw_definition && isset( $eccw_definition['slots'] ) )
	? wp_list_pluck( ElementorColorChanger\CSS_Generator::author_slots( $eccw_definition['slots'] ), 'slot_id' )
	: array();

$eccw_rows = array();

foreach ( $eccw_slots as $eccw_slot_id => $eccw_slot_config ) {
	if ( ! empty( $eccw_authored ) && ! in_array( $eccw_slot_id, $eccw_authored, true ) ) {
		continue;
	}

	$eccw_registry_label = '';
	$eccw_derives_from   = '';
	$eccw_base_label     = '';

	if ( $eccw_definition && isset( $eccw_definition['slots'] ) ) {
		foreach ( $eccw_definition['slots'] as $eccw_def_slot ) {
			if ( $eccw_def_slot['slot_id'] === $eccw_slot_id ) {
				$eccw_registry_label = $eccw_def_slot['label'];
				$eccw_derives_from   = isset( $eccw_def_slot['derives_from'] ) ? $eccw_def_slot['derives_from'] : '';
				break;
			}
		}

		// A state slot records which slot it is a state of. That is the only
		// reliable way to say *what* is being hovered or focused, which
		// matters on cards holding more than one sub-element.
		if ( '' !== $eccw_derives_from ) {
			foreach ( $eccw_definition['slots'] as $eccw_def_slot ) {
				if ( $eccw_def_slot['slot_id'] === $eccw_derives_from ) {
					$eccw_base_label = $eccw_def_slot['label'];
					break;
				}
			}
		}
	}

	$eccw_current = isset( $eccw_slot_config['color'] ) ? $eccw_slot_config['color'] : 'text';
	$eccw_is_hex  = is_string( $eccw_current ) && preg_match( '/^#?[0-9a-fA-F]{6}$/', $eccw_current );
	$eccw_hex     = $eccw_is_hex
		? '#' . strtolower( ltrim( $eccw_current, '#' ) )
		: ( isset( $kit_colors[ $eccw_current ] ) ? $kit_colors[ $eccw_current ] : '#cccccc' );

	$eccw_rows[] = array(
		'slot_id' => $eccw_slot_id,
		'label'   => ElementorColorChanger\Admin_Interface::state_label( $eccw_slot_id, $eccw_registry_label, $eccw_base_label ),
		'current' => $eccw_current,
		'hex'     => $eccw_hex,
		'default' => isset( $defaults[ $eccw_slot_id ] ) ? $defaults[ $eccw_slot_id ] : 'text',
		'is_hex'  => (bool) $eccw_is_hex,
	);
}

// The first `_normal` slot is the element's identity; everything else is a
// variation on it.
$eccw_main  = null;
$eccw_extra = array();

foreach ( $eccw_rows as $eccw_row ) {
	if ( null === $eccw_main && substr( $eccw_row['slot_id'], -7 ) === '_normal' ) {
		$eccw_main = $eccw_row;
		continue;
	}

	$eccw_extra[] = $eccw_row;
}

if ( null === $eccw_main && ! empty( $eccw_extra ) ) {
	$eccw_main = array_shift( $eccw_extra );
}

/**
 * One colour chooser: a swatch, a select of the palette, and a reset.
 *
 * The select is the field that submits. The swatch beside it is painted by the
 * browser from the same value, so there is no second source of truth to keep in
 * step — and with JavaScript off the control still works.
 */
$eccw_chooser = static function ( $row, $widget_key, $kit_colors, $kit_labels, $is_main ) {
	$field = 'eccw_colors[' . $widget_key . '][' . $row['slot_id'] . ']';
	$id    = 'eccw-' . $widget_key . '-' . $row['slot_id'];
	?>
	<div class="eccw-choice<?php echo $is_main ? ' is-main' : ''; ?>">
		<label class="eccw-choice-label" for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $row['label'] ); ?>
		</label>

		<div class="eccw-choice-control">
			<span class="eccw-choice-chip" data-eccw-chip="<?php echo esc_attr( $id ); ?>" style="background-color:<?php echo esc_attr( $row['hex'] ); ?>;"></span>

			<select
				class="eccw-choice-select"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $field ); ?>"
				data-eccw-select
				data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
				data-state="<?php echo esc_attr( $row['slot_id'] ); ?>"
				data-default="<?php echo esc_attr( $row['default'] ); ?>">
				<?php foreach ( $kit_colors as $eccw_color_id => $eccw_color_hex ) : ?>
					<option
						value="<?php echo esc_attr( $eccw_color_id ); ?>"
						data-hex="<?php echo esc_attr( $eccw_color_hex ); ?>"
						<?php selected( $row['current'], $eccw_color_id ); ?>>
						<?php echo esc_html( isset( $kit_labels[ $eccw_color_id ] ) ? $kit_labels[ $eccw_color_id ] : ucfirst( str_replace( '_', ' ', $eccw_color_id ) ) ); ?>
					</option>
				<?php endforeach; ?>

				<?php
				// A stored custom colour keeps its own entry so it survives a
				// save untouched, whether or not it was chosen through the
				// picker below.
				?>
				<option
					value="<?php echo esc_attr( $row['is_hex'] ? $row['hex'] : '__custom__' ); ?>"
					data-eccw-custom-option
					data-hex="<?php echo esc_attr( $row['is_hex'] ? $row['hex'] : '' ); ?>"
					<?php selected( $row['is_hex'] ); ?>>
					<?php
					echo esc_html(
						$row['is_hex']
							? __( 'Custom colour', 'color-changer-for-elementor' ) . ' — ' . strtoupper( $row['hex'] )
							: __( 'Custom colour…', 'color-changer-for-elementor' )
					);
					?>
				</option>
			</select>

			<?php
			// The picker writes back into the select's custom option, so the
			// select stays the single field that submits. The server has always
			// accepted a six-digit hex here; there was simply no way to enter
			// one after the Live Editor was removed in 2.0.
			?>
			<input
				type="color"
				class="eccw-choice-picker"
				data-eccw-picker="<?php echo esc_attr( $id ); ?>"
				value="<?php echo esc_attr( $row['is_hex'] ? $row['hex'] : $row['hex'] ); ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %s: name of the colour being set. */ __( 'Pick a custom colour for %s', 'color-changer-for-elementor' ), $row['label'] ) ); ?>"
				<?php echo $row['is_hex'] ? '' : ' hidden'; ?>>

			<button
				type="button"
				class="eccw-reset"
				data-eccw-reset="<?php echo esc_attr( $id ); ?>"
				data-default="<?php echo esc_attr( $row['default'] ); ?>">
				<?php esc_html_e( 'Reset', 'color-changer-for-elementor' ); ?>
			</button>
		</div>
	</div>
	<?php
};
?>
<article class="eccw-element" data-widget-key="<?php echo esc_attr( $eccw_widget_key ); ?>">
	<header class="eccw-element-head">
		<h3><?php echo esc_html( $eccw_label ); ?></h3>
		<?php
		// No "New" tag. A newly discovered element is already styled with a
		// sensible default the moment it is found, so tagging it only asks the
		// owner to acknowledge something that needs no decision — which is the
		// discovery state the product spec says never to expose.
		?>
	</header>

	<?php if ( '' !== $eccw_description ) : ?>
		<p class="eccw-element-desc"><?php echo esc_html( $eccw_description ); ?></p>
	<?php endif; ?>

	<?php if ( null === $eccw_main ) : ?>
		<p class="eccw-element-desc">
			<?php esc_html_e( 'This part is styled automatically — there is nothing to choose.', 'color-changer-for-elementor' ); ?>
		</p>
	<?php else : ?>
		<?php $eccw_chooser( $eccw_main, $eccw_widget_key, $kit_colors, $kit_labels, true ); ?>

		<?php if ( ! empty( $eccw_extra ) ) : ?>
			<details class="eccw-details eccw-details-inline">
				<summary><?php esc_html_e( 'More options', 'color-changer-for-elementor' ); ?></summary>
				<?php
				// Said once per card, because the alternative is a support
				// question that reads "I changed it and nothing happened".
				// These colours are real and saved; they are simply invisible
				// until the shopper does the thing that triggers them.
				?>
				<p class="eccw-hint">
					<?php esc_html_e( 'These only show when a shopper triggers them — by hovering, tabbing with a keyboard, or reaching an unavailable button. Your store will not look different until then.', 'color-changer-for-elementor' ); ?>
				</p>
				<?php foreach ( $eccw_extra as $eccw_row ) : ?>
					<?php $eccw_chooser( $eccw_row, $eccw_widget_key, $kit_colors, $kit_labels, false ); ?>
				<?php endforeach; ?>
			</details>
		<?php endif; ?>
	<?php endif; ?>
</article>
