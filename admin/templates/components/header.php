<?php
/**
 * Store Design — page header.
 *
 * Title, one sentence saying what the screen does, and the two links people
 * leave this page for: their own store, and the kit where the colours live.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var string $shop_url Front-end shop URL. */
/** @var string $kit_edit_url Deep link into the Elementor kit editor. */
?>
<div class="eccw-header">
	<div class="eccw-header-text">
		<h1><?php esc_html_e( 'Store Design', 'commerce-colors-for-elementor' ); ?></h1>
		<p><?php esc_html_e( 'Your shop pages use the colours and fonts you set in Elementor. No page-by-page setup.', 'commerce-colors-for-elementor' ); ?></p>
	</div>
	<div class="eccw-header-actions">
		<a class="eccw-btn eccw-btn-quiet" href="<?php echo esc_url( $kit_edit_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Edit colours in Elementor', 'commerce-colors-for-elementor' ); ?>
			<span aria-hidden="true">&#8599;</span>
		</a>
		<a class="eccw-btn eccw-btn-quiet" href="<?php echo esc_url( $shop_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'View my store', 'commerce-colors-for-elementor' ); ?>
			<span aria-hidden="true">&#8599;</span>
		</a>
	</div>
</div>
