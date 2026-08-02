<?php
/**
 * Advanced Settings - CSS Dequeue card component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $dequeue_settings Saved dequeue settings. */
/** @var bool $dequeue_disabled True when no mappings exist (dequeue is inert). */
?>
<div class="wooce-card wooce-card-dequeue" data-wooce-component="dequeue">
	<div class="wooce-card-header">
		<h2><?php echo esc_html__( 'CSS Dequeue Settings', 'woocommerce-elementor-colors' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Choose which default WooCommerce stylesheets to disable. Your Elementor styles will replace them.', 'woocommerce-elementor-colors' ); ?>
		</p>
	</div>
	<div class="wooce-card-body">
		<?php if ( $dequeue_disabled ) : ?>
			<div class="wooce-notice wooce-notice-info">
				<?php echo esc_html__( 'Dequeue is inactive: no element mappings exist yet, so there is no replacement CSS to apply.', 'woocommerce-elementor-colors' ); ?>
			</div>
		<?php endif; ?>
		<label class="wooce-dequeue-toggle">
			<input type="checkbox" name="wooce_dequeue_core" value="yes" <?php checked( isset( $dequeue_settings['dequeue_core'] ) ? $dequeue_settings['dequeue_core'] : 'yes', 'yes' ); ?> <?php echo $dequeue_disabled ? ' disabled' : ''; ?>>
			<span><?php echo esc_html__( 'Dequeue WooCommerce core CSS (general, layout, smallscreen)', 'woocommerce-elementor-colors' ); ?></span>
		</label>
		<label class="wooce-dequeue-toggle">
			<input type="checkbox" name="wooce_dequeue_blocks" value="yes" <?php checked( isset( $dequeue_settings['dequeue_blocks'] ) ? $dequeue_settings['dequeue_blocks'] : 'yes', 'yes' ); ?> <?php echo $dequeue_disabled ? ' disabled' : ''; ?>>
			<span><?php echo esc_html__( 'Dequeue WooCommerce Blocks CSS (block styles)', 'woocommerce-elementor-colors' ); ?></span>
		</label>
	</div>
</div>
