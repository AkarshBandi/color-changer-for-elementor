<?php
/**
 * Elementor Colors - Notices component.
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
	<div class="eccw-notice eccw-notice-success">
		<?php echo esc_html__( 'Settings saved successfully.', 'color-changer-for-elementor' ); ?>
	</div>
<?php endif; ?>

<div class="eccw-notice eccw-notice-promo">
	<span><?php echo esc_html__( 'Try the new Live Editor — click any WooCommerce element on your site to customize colors instantly.', 'color-changer-for-elementor' ); ?></span>
	<a href="<?php echo esc_url( $live_editor_url ); ?>" class="button button-primary eccw-promo-cta">
		<?php echo esc_html__( 'Open Live Editor', 'color-changer-for-elementor' ); ?>
	</a>
</div>
