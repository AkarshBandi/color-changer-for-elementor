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
		<summary><?php esc_html_e( 'Advanced', 'color-changer-for-elementor' ); ?></summary>

		<div class="eccw-card">
			<p class="eccw-section-intro">
				<?php esc_html_e( 'You should not need anything here. It is kept for the cases where you do.', 'color-changer-for-elementor' ); ?>
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
					<strong><?php esc_html_e( 'Remove the WooCommerce Blocks stylesheet', 'color-changer-for-elementor' ); ?></strong>
					<span class="eccw-hero-sub">
						<?php esc_html_e( 'Only affects stores whose Cart or Checkout is built with WooCommerce blocks. That stylesheet carries spacing and layout as well as colour, so removing it can rearrange those two pages. Check them afterwards.', 'color-changer-for-elementor' ); ?>
					</span>
				</span>
			</label>

			<?php if ( $dequeue_disabled ) : ?>
				<div class="eccw-note">
					<?php esc_html_e( 'Unavailable: no colours are set yet, so there would be nothing to replace the removed stylesheet with.', 'color-changer-for-elementor' ); ?>
				</div>
			<?php endif; ?>

			<hr class="eccw-rule">

			<p class="eccw-section-intro">
				<?php esc_html_e( 'Your store is checked for new parts automatically, on activation and once a day. These buttons only make it happen now.', 'color-changer-for-elementor' ); ?>
			</p>

			<div class="eccw-actions">
				<button type="button" class="eccw-btn eccw-btn-quiet" data-eccw-action="rescan" data-testid="eccw-rescan">
					<?php esc_html_e( 'Check for new elements', 'color-changer-for-elementor' ); ?>
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
					data-confirm="<?php echo esc_attr__( 'Put every part of your store back to its suggested colour? Your own choices will be replaced, and this cannot be undone.', 'color-changer-for-elementor' ); ?>">
					<?php esc_html_e( 'Start over with suggested colours', 'color-changer-for-elementor' ); ?>
				</button>
			</div>
		</div>
	</details>
</section>
