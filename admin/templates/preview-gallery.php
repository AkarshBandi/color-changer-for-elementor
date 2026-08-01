<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wooce-gallery">
	<?php foreach ( $mappings as $widget_key => $widget_data ) : ?>
		<?php
		$definition = WooElementorColors\Element_Registry::lookup( $widget_key );
		$slots      = isset( $widget_data['slots'] ) ? $widget_data['slots'] : array();
		?>
		<div class="wooce-gallery-item" data-widget-key="<?php echo esc_attr( $widget_key ); ?>">
			<div class="wooce-gallery-item-header">
				<?php echo esc_html( $widget_data['label'] ); ?>
			</div>
			<div class="wooce-gallery-item-body">
				<?php foreach ( $slots as $slot_id => $slot_config ) : ?>
					<?php
					$current_color = isset( $slot_config['color'] ) ? $slot_config['color'] : 'text';
					$color_hex     = isset( $kit_colors[ $current_color ] ) ? $kit_colors[ $current_color ] : '#000';
					$slot_label    = $slot_id;

					if ( $definition && isset( $definition['slots'] ) ) {
						foreach ( $definition['slots'] as $def_slot ) {
							if ( $def_slot['slot_id'] === $slot_id ) {
								$slot_label = $def_slot['label'];
								break;
							}
						}
					}

					$is_button = false !== strpos( $slot_id, 'button' ) || false !== strpos( $slot_id, 'proceed' ) || false !== strpos( $slot_id, 'place_order' ) || false !== strpos( $slot_id, 'update_cart' ) || false !== strpos( $slot_id, 'loop' );
					?>
					<div>
						<div class="wooce-gallery-preview-label"><?php echo esc_html( $slot_label ); ?></div>
						<div class="wooce-gallery-preview"
							data-widget-key="<?php echo esc_attr( $widget_key ); ?>"
							data-state="<?php echo esc_attr( $slot_id ); ?>"
							style="<?php echo $is_button ? 'background-color:' . esc_attr( $color_hex ) . ';color:#fff;padding:8px 16px;border-radius:3px;display:inline-block;' : 'color:' . esc_attr( $color_hex ) . ';'; ?>">
							<?php if ( $is_button ) : ?>
								<?php echo esc_html__( 'Button', 'woocommerce-elementor-colors' ); ?>
							<?php elseif ( false !== strpos( $slot_id, 'price' ) ) : ?>
								$29.99
							<?php elseif ( false !== strpos( $slot_id, 'star' ) ) : ?>
								&#9733;&#9733;&#9733;&#9733;
							<?php elseif ( false !== strpos( $slot_id, 'badge' ) ) : ?>
								<span style="background-color:<?php echo esc_attr( $color_hex ); ?>;color:#fff;padding:2px 8px;border-radius:3px;"><?php echo esc_html__( 'SALE', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'link' ) ) : ?>
								<a href="#" style="color:<?php echo esc_attr( $color_hex ); ?>;"><?php echo esc_html__( 'Sample Link', 'woocommerce-elementor-colors' ); ?></a>
							<?php elseif ( false !== strpos( $slot_id, 'input' ) || false !== strpos( $slot_id, 'qty' ) || false !== strpos( $slot_id, 'coupon' ) ) : ?>
								<span style="border:1px solid <?php echo esc_attr( $color_hex ); ?>;padding:4px 8px;border-radius:3px;"><?php echo esc_html__( 'Input', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'nav' ) ) : ?>
								<span style="color:<?php echo esc_attr( $color_hex ); ?>;"><?php echo esc_html__( 'Navigation Link', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'tab' ) ) : ?>
								<span style="color:<?php echo esc_attr( $color_hex ); ?>;border-bottom:2px solid <?php echo esc_attr( $color_hex ); ?>;"><?php echo esc_html__( 'Tab', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'success' ) ) : ?>
								<span style="border-left:4px solid <?php echo esc_attr( $color_hex ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Success notice', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'info' ) ) : ?>
								<span style="border-left:4px solid <?php echo esc_attr( $color_hex ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Info notice', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'error' ) ) : ?>
								<span style="border-left:4px solid <?php echo esc_attr( $color_hex ); ?>;padding:4px 8px;"><?php echo esc_html__( 'Error notice', 'woocommerce-elementor-colors' ); ?></span>
							<?php elseif ( false !== strpos( $slot_id, 'header' ) || false !== strpos( $slot_id, 'cell' ) || false !== strpos( $slot_id, 'table' ) ) : ?>
								<span style="color:<?php echo esc_attr( $color_hex ); ?>;font-weight:bold;"><?php echo esc_html__( 'Table', 'woocommerce-elementor-colors' ); ?></span>
							<?php else : ?>
								<span style="color:<?php echo esc_attr( $color_hex ); ?>;"><?php echo esc_html( $slot_label ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
