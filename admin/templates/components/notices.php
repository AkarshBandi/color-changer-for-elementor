<?php
/**
 * Store Design — notices.
 *
 * The "saved" confirmation, plus the live region the page writes into.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;
?>
<?php // Read-only admin notice flag set by the settings form redirect. ?>
<?php if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL flag for admin notice. ?>
	<div class="eccw-note eccw-note-good" data-testid="eccw-saved-notice">
		<?php esc_html_e( 'Saved. Your store is showing the new design.', 'commerce-colors-for-elementor' ); ?>
	</div>
<?php endif; ?>

<?php
/*
 * Where a result from an action on this page appears.
 *
 * Empty until something fills it. It exists in the markup rather than being
 * created on demand so its position is fixed and so screen readers are already
 * watching it: `aria-live="polite"` announces the result without stealing
 * focus, which a browser alert() does by definition. The alert also had to be
 * dismissed before the page could be used again, to deliver one sentence of
 * good news.
 */
?>
<div class="eccw-live" id="eccw-live" role="status" aria-live="polite"></div>
