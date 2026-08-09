<?php
/**
 * Advanced Settings - Page shell.
 *
 * Orchestrates all settings-page components inside a single settings form.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $mappings All widget mappings. */
/** @var array $dequeue_settings Saved dequeue settings. */
/** @var int $saved_version Current mapping version. */
/** @var bool $dequeue_disabled True when no mappings exist (dequeue is inert). */
/** @var array $defaults Per-slot default color tokens. */
/** @var int $new_count Number of widgets with status "new". */
/** @var string $live_editor_url URL to open the Live Editor. */

$kit_color_count = count( $kit_colors );
$mapping_count   = count( $mappings );
?>
<div class="wrap wooce-wrap">

	<?php require WOOEC_PATH . 'admin/templates/components/header.php'; ?>
	<?php require WOOEC_PATH . 'admin/templates/components/notices.php'; ?>
	<?php require WOOEC_PATH . 'admin/templates/components/status-bar.php'; ?>

	<form id="wooce-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wooce_save_settings">
		<?php wp_nonce_field( 'wooce_save_settings', 'wooce_save_nonce' ); ?>

		<?php if ( ! empty( $mappings ) ) : ?>
			<div class="wooce-card-grid">
				<div class="wooce-card-grid-wide">
					<?php include WOOEC_PATH . 'admin/templates/components/elements-card.php'; ?>
				</div>

				<?php require WOOEC_PATH . 'admin/templates/components/dequeue-card.php'; ?>

				<?php include WOOEC_PATH . 'admin/templates/components/gallery-card.php'; ?>
			</div>
		<?php else : ?>
			<div class="wooce-card-grid">
				<?php require WOOEC_PATH . 'admin/templates/components/dequeue-card.php'; ?>

				<div class="wooce-notice wooce-notice-info">
					<?php echo esc_html__( 'No WooCommerce Elementor widgets found. Add WooCommerce widgets to your Elementor pages and click Rescan.', 'woocommerce-elementor-colors' ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php require WOOEC_PATH . 'admin/templates/components/save-bar.php'; ?>
	</form>
</div>
