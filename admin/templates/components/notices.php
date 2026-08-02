<?php
/**
 * Advanced Settings - Notices component.
 *
 * Renders the "settings saved" success notice and the Live Editor promo banner.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var string $live_editor_url URL to open the Live Editor. */
?>
<?php // Read-only admin notice flag set by the settings form redirect. ?>
<?php if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL flag for admin notice. ?>
	<div class="wooce-notice wooce-notice-success">
		<?php echo esc_html__( 'Settings saved successfully.', 'woocommerce-elementor-colors' ); ?>
	</div>
<?php endif; ?>

<div class="wooce-notice wooce-notice-promo">
	<span><?php echo esc_html__( '🎨 Try the new Live Editor — click any WooCommerce element on your site to customize colors instantly.', 'woocommerce-elementor-colors' ); ?></span>
	<a href="<?php echo esc_url( $live_editor_url ); ?>" class="button button-primary wooce-promo-cta">
		<?php echo esc_html__( 'Open Live Editor', 'woocommerce-elementor-colors' ); ?>
	</a>
</div>
