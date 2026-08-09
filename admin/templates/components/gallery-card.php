<?php
/**
 * Elementor Colors - Preview Gallery card component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $mappings All widget mappings. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
?>
<div class="eccw-card eccw-card-gallery" data-eccw-component="gallery">
	<div class="eccw-card-header">
		<h2><?php echo esc_html__( 'Preview Gallery', 'color-changer-for-elementor' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Hover over preview elements to see hover state colors.', 'color-changer-for-elementor' ); ?>
		</p>
	</div>
	<div class="eccw-card-body">
		<div class="eccw-gallery">
			<?php foreach ( $mappings as $eccw_widget_key => $eccw_widget_data ) : ?>
				<?php
				$eccw_definition = ElementorColorChanger\Element_Registry::lookup( $eccw_widget_key );
				$eccw_slots      = isset( $eccw_widget_data['slots'] ) ? $eccw_widget_data['slots'] : array();

				$eccw_gallery_slots = array();

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
					$eccw_color_hex     = ElementorColorChanger\CSS_Generator::resolve_color( $eccw_current_color );
					$eccw_is_button     = false !== strpos( $eccw_slot_id, 'button' ) || false !== strpos( $eccw_slot_id, 'proceed' ) || false !== strpos( $eccw_slot_id, 'place_order' ) || false !== strpos( $eccw_slot_id, 'update_cart' ) || false !== strpos( $eccw_slot_id, 'loop' );
					$eccw_text_hex      = $eccw_is_button ? ElementorColorChanger\CSS_Generator::contrast_text( $eccw_color_hex ) : $eccw_color_hex;

					$eccw_gallery_slots[] = array(
						'slot_id'    => $eccw_slot_id,
						'slot_label' => $eccw_slot_label,
						'color_hex'  => $eccw_color_hex,
						'text_hex'   => $eccw_text_hex,
						'is_button'  => $eccw_is_button,
					);
				}
				?>
				<div class="eccw-gallery-item" data-widget-key="<?php echo esc_attr( $eccw_widget_key ); ?>">
					<div class="eccw-gallery-item-header">
						<?php echo esc_html( $eccw_widget_data['label'] ); ?>
					</div>
					<div class="eccw-gallery-item-body">
						<?php foreach ( $eccw_gallery_slots as $eccw_gs ) : ?>
							<div>
								<div class="eccw-gallery-preview-label"><?php echo esc_html( $eccw_gs['slot_label'] ); ?></div>
								<div class="eccw-gallery-preview"
									data-widget-key="<?php echo esc_attr( $eccw_widget_key ); ?>"
									data-state="<?php echo esc_attr( $eccw_gs['slot_id'] ); ?>"
									data-is-button="<?php echo $eccw_gs['is_button'] ? '1' : '0'; ?>"
									<?php
									if ( $eccw_gs['is_button'] ) {
										echo 'style="background-color:' . esc_attr( $eccw_gs['color_hex'] ) . ';color:' . esc_attr( $eccw_gs['text_hex'] ) . ';padding:8px 16px;border-radius:3px;display:inline-block;"';
									}
									?>
								>
									<?php if ( $eccw_gs['is_button'] ) : ?>
										<?php echo esc_html__( 'Button', 'color-changer-for-elementor' ); ?>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'price' ) ) : ?>
										$29.99
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'star' ) ) : ?>
										&#9733;&#9733;&#9733;&#9733;
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'badge' ) ) : ?>
										<span style="background-color:<?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;padding:2px 8px;border-radius:3px;"><?php echo esc_html__( 'SALE', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'link' ) ) : ?>
										<a href="#" style="color:<?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;"><?php echo esc_html__( 'Sample Link', 'color-changer-for-elementor' ); ?></a>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'input' ) || false !== strpos( $eccw_gs['slot_id'], 'qty' ) || false !== strpos( $eccw_gs['slot_id'], 'coupon' ) ) : ?>
										<span style="border:1px solid <?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;padding:4px 8px;border-radius:3px;"><?php echo esc_html__( 'Input', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'nav' ) ) : ?>
										<span style="color:<?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;"><?php echo esc_html__( 'Navigation Link', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'tab' ) ) : ?>
										<span style="color:<?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;border-bottom:2px solid <?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;"><?php echo esc_html__( 'Tab', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'success' ) ) : ?>
										<span style="border-left:4px solid <?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Success notice', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'info' ) ) : ?>
										<span style="border-left:4px solid <?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Info notice', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'error' ) ) : ?>
										<span style="border-left:4px solid <?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Error notice', 'color-changer-for-elementor' ); ?></span>
									<?php elseif ( false !== strpos( $eccw_gs['slot_id'], 'header' ) || false !== strpos( $eccw_gs['slot_id'], 'cell' ) || false !== strpos( $eccw_gs['slot_id'], 'table' ) ) : ?>
										<span style="color:<?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;font-weight:bold;"><?php echo esc_html__( 'Table', 'color-changer-for-elementor' ); ?></span>
									<?php else : ?>
										<span style="color:<?php echo esc_attr( $eccw_gs['color_hex'] ); ?>;"><?php echo esc_html( $eccw_gs['slot_label'] ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
