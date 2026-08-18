<?php
/**
 * Store Design — advanced, folded away.
 *
 * Collapsed by default. Everything in here is either a maintenance action that
 * already runs on its own, or an option that can change a page's layout — and
 * neither belongs in the path of someone who came to change a colour.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $settings Resolved plugin settings. */
/** @var bool $dequeue_disabled True when no mappings exist (dequeue is inert). */
?>
<section class="eccw-section">
	<details class="eccw-details eccw-details-advanced">
		<summary><?php esc_html_e( 'Advanced', 'commerce-colors-for-elementor' ); ?></summary>

		<div class="eccw-card">
			<p class="eccw-section-intro">
				<?php esc_html_e( 'You should not need anything here. It is kept for the cases where you do.', 'commerce-colors-for-elementor' ); ?>
			</p>

			<label class="eccw-switch-row">
				<span class="eccw-switch">
					<input
						type="checkbox"
						name="eccw_dequeue_blocks"
						value="yes"
						<?php checked( ! empty( $settings['dequeue_blocks'] ) ); ?>
						<?php echo $dequeue_disabled ? ' disabled' : ''; ?>>
					<span class="eccw-switch-track" aria-hidden="true"><span class="eccw-switch-knob"></span></span>
				</span>
				<span class="eccw-switch-text">
					<strong><?php esc_html_e( 'Turn off WooCommerce\'s own cart and checkout styling', 'commerce-colors-for-elementor' ); ?></strong>
					<span class="eccw-hero-sub">
						<?php esc_html_e( 'Only matters if your Cart or Checkout uses WooCommerce\'s newer block layout. WooCommerce\'s own styling sets spacing and position as well as colour, so switching it off can move things around on those two pages. Have a look at them afterwards.', 'commerce-colors-for-elementor' ); ?>
					</span>
				</span>
			</label>

			<?php if ( $dequeue_disabled ) : ?>
				<div class="eccw-note">
					<?php esc_html_e( 'Not available yet: no colours are set, so there would be nothing to put in its place.', 'commerce-colors-for-elementor' ); ?>
				</div>
			<?php endif; ?>

			<hr class="eccw-rule">

			<p class="eccw-section-intro">
				<?php esc_html_e( 'Your store is checked for new parts automatically, on activation and once a day. These buttons only make it happen now.', 'commerce-colors-for-elementor' ); ?>
			</p>

			<div class="eccw-actions">
				<button type="button" class="eccw-btn eccw-btn-quiet" data-eccw-action="rescan" data-testid="eccw-rescan">
					<?php esc_html_e( 'Check for new parts of my store', 'commerce-colors-for-elementor' ); ?>
				</button>

				<?php
				// "Mark all as reviewed" is gone with the "New" tag it existed to
				// clear. Nothing needs reviewing: a discovered element is styled
				// on discovery. The AJAX endpoint stays for add-ons and for
				// anyone whose stored mappings still carry the flag.
				?>
				<button
					type="button"
					class="eccw-btn eccw-btn-danger"
					data-eccw-action="reset-defaults"
					data-testid="eccw-reset-defaults"
					data-confirm="<?php echo esc_attr__( 'Put every part of your store back to its suggested colour? Your own choices will be replaced, and this cannot be undone.', 'commerce-colors-for-elementor' ); ?>">
					<?php esc_html_e( 'Start over with suggested colours', 'commerce-colors-for-elementor' ); ?>
				</button>
			</div>
		</div>
	</details>
</section>
