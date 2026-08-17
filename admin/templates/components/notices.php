<?php
/**
 * Store Design — notices.
 *
 * The "saved" confirmation, and nothing else.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;
?>
<?php // Read-only admin notice flag set by the settings form redirect. ?>
<?php if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL flag for admin notice. ?>
	<div class="eccw-note eccw-note-good" data-testid="eccw-saved-notice">
		<?php esc_html_e( 'Saved. Your store is showing the new design.', 'color-changer-for-elementor' ); ?>
	</div>
<?php endif; ?>
