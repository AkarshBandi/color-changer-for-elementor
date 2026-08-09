<?php
/**
 * Elementor Colors - CSS Dequeue card component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $dequeue_settings Saved dequeue settings. */
/** @var bool $dequeue_disabled True when no mappings exist (dequeue is inert). */
?>
<div class="eccw-card eccw-card-dequeue" data-eccw-component="dequeue">
	<div class="eccw-card-header">
		<h2><?php echo esc_html__( 'CSS Dequeue Settings', 'color-changer-for-elementor' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Choose which default WooCommerce stylesheets to disable. Your Elementor styles will replace them.', 'color-changer-for-elementor' ); ?>
		</p>
	</div>
	<div class="eccw-card-body">
		<?php if ( $dequeue_disabled ) : ?>
			<div class="eccw-notice eccw-notice-info">
				<?php echo esc_html__( 'Dequeue is inactive: no element mappings exist yet, so there is no replacement CSS to apply.', 'color-changer-for-elementor' ); ?>
			</div>
		<?php endif; ?>
		<p class="description">
			<?php echo esc_html__( 'Your brand colors replace WooCommerce colors on top of the default stylesheet, so the shop layout (product grid and buttons) stays intact. You can optionally remove the WooCommerce Blocks stylesheet used by block-based layouts.', 'color-changer-for-elementor' ); ?>
		</p>
		<label class="eccw-dequeue-toggle">
			<input type="checkbox" name="eccw_dequeue_blocks" value="yes" <?php checked( isset( $dequeue_settings['dequeue_blocks'] ) ? $dequeue_settings['dequeue_blocks'] : 'yes', 'yes' ); ?> <?php echo $dequeue_disabled ? ' disabled' : ''; ?>>
			<span><?php echo esc_html__( 'Dequeue WooCommerce Blocks CSS (block styles)', 'color-changer-for-elementor' ); ?></span>
		</label>
	</div>
</div>
