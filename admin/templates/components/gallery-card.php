<?php
/**
 * Advanced Settings - Preview Gallery card component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $mappings All widget mappings. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
?>
<div class="wooce-card wooce-card-gallery" data-wooce-component="gallery">
	<div class="wooce-card-header">
		<h2><?php echo esc_html__( 'Preview Gallery', 'woocommerce-elementor-colors' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Hover over preview elements to see hover state colors.', 'woocommerce-elementor-colors' ); ?>
		</p>
	</div>
	<div class="wooce-card-body">
		<div class="wooce-gallery">
			<?php foreach ( $mappings as $widget_key => $widget_data ) : ?>
				<?php
				$definition = WooElementorColors\Element_Registry::lookup( $widget_key );
				$slots      = isset( $widget_data['slots'] ) ? $widget_data['slots'] : array();

				$gallery_slots = array();

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
					$color_hex     = isset( $kit_colors[ $current_color ] ) ? $kit_colors[ $current_color ] : '#000';
					$is_button     = false !== strpos( $slot_id, 'button' ) || false !== strpos( $slot_id, 'proceed' ) || false !== strpos( $slot_id, 'place_order' ) || false !== strpos( $slot_id, 'update_cart' ) || false !== strpos( $slot_id, 'loop' );
					$text_hex      = $is_button ? WooElementorColors\CSS_Generator::contrast_text( $color_hex ) : $color_hex;

					$gallery_slots[] = array(
						'slot_id'    => $slot_id,
						'slot_label' => $slot_label,
						'color_hex'  => $color_hex,
						'text_hex'   => $text_hex,
						'is_button'  => $is_button,
					);
				}
				?>
				<div class="wooce-gallery-item" data-widget-key="<?php echo esc_attr( $widget_key ); ?>">
					<div class="wooce-gallery-item-header">
						<?php echo esc_html( $widget_data['label'] ); ?>
					</div>
					<div class="wooce-gallery-item-body">
						<?php foreach ( $gallery_slots as $gs ) : ?>
							<div>
								<div class="wooce-gallery-preview-label"><?php echo esc_html( $gs['slot_label'] ); ?></div>
								<div class="wooce-gallery-preview"
									data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
									data-state="<?php echo esc_attr( $gs['slot_id'] ); ?>"
									data-is-button="<?php echo $gs['is_button'] ? '1' : '0'; ?>"
									<?php
									if ( $gs['is_button'] ) {
										echo 'style="background-color:' . esc_attr( $gs['color_hex'] ) . ';color:' . esc_attr( $gs['text_hex'] ) . ';padding:8px 16px;border-radius:3px;display:inline-block;"';
									}
									?>
								>
									<?php if ( $gs['is_button'] ) : ?>
										<?php echo esc_html__( 'Button', 'woocommerce-elementor-colors' ); ?>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'price' ) ) : ?>
										$29.99
									<?php elseif ( false !== strpos( $gs['slot_id'], 'star' ) ) : ?>
										&#9733;&#9733;&#9733;&#9733;
									<?php elseif ( false !== strpos( $gs['slot_id'], 'badge' ) ) : ?>
										<span style="background-color:<?php echo esc_attr( $gs['color_hex'] ); ?>;padding:2px 8px;border-radius:3px;"><?php echo esc_html__( 'SALE', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'link' ) ) : ?>
										<a href="#" style="color:<?php echo esc_attr( $gs['color_hex'] ); ?>;"><?php echo esc_html__( 'Sample Link', 'woocommerce-elementor-colors' ); ?></a>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'input' ) || false !== strpos( $gs['slot_id'], 'qty' ) || false !== strpos( $gs['slot_id'], 'coupon' ) ) : ?>
										<span style="border:1px solid <?php echo esc_attr( $gs['color_hex'] ); ?>;padding:4px 8px;border-radius:3px;"><?php echo esc_html__( 'Input', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'nav' ) ) : ?>
										<span style="color:<?php echo esc_attr( $gs['color_hex'] ); ?>;"><?php echo esc_html__( 'Navigation Link', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'tab' ) ) : ?>
										<span style="color:<?php echo esc_attr( $gs['color_hex'] ); ?>;border-bottom:2px solid <?php echo esc_attr( $gs['color_hex'] ); ?>;"><?php echo esc_html__( 'Tab', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'success' ) ) : ?>
										<span style="border-left:4px solid <?php echo esc_attr( $gs['color_hex'] ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Success notice', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'info' ) ) : ?>
										<span style="border-left:4px solid <?php echo esc_attr( $gs['color_hex'] ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Info notice', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'error' ) ) : ?>
										<span style="border-left:4px solid <?php echo esc_attr( $gs['color_hex'] ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Error notice', 'woocommerce-elementor-colors' ); ?></span>
									<?php elseif ( false !== strpos( $gs['slot_id'], 'header' ) || false !== strpos( $gs['slot_id'], 'cell' ) || false !== strpos( $gs['slot_id'], 'table' ) ) : ?>
										<span style="color:<?php echo esc_attr( $gs['color_hex'] ); ?>;font-weight:bold;"><?php echo esc_html__( 'Table', 'woocommerce-elementor-colors' ); ?></span>
									<?php else : ?>
										<span style="color:<?php echo esc_attr( $gs['color_hex'] ); ?>;"><?php echo esc_html( $gs['slot_label'] ); ?></span>
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
