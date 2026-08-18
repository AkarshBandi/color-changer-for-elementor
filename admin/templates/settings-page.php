<?php
/**
 * Store Design — page shell.
 *
 * Four areas, in the order a shop owner actually thinks about them:
 *
 *   1. Is it on, and what does my store look like now?
 *   2. Which colours am I using?
 *   3. Which part of my store uses which colour?
 *   4. Everything else, folded away.
 *
 * The screen this replaced opened on a 4-column table of thirty-seven
 * dropdowns whose options read "2666acb8 - #FAFAFA", above a Status column
 * printing the words "default" and "configured". Every fact on it was true and
 * almost none of it was answerable by the person it was for.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $kit_labels Token id => human label. */
/** @var array $mappings All widget mappings. */
/** @var array $settings Resolved plugin settings. */
/** @var array $descriptions Widget key => plain description. */
/** @var array $defaults Per-slot default color tokens. */
/** @var bool $dequeue_disabled True when no mappings exist. */
/** @var bool $has_own_kit Whether the kit defines its own colours. */
/** @var bool $global_chrome Whether the site header/footer carries WooCommerce. */
/** @var string $kit_edit_url Deep link into the Elementor kit editor. */
/** @var string $shop_url Front-end shop URL. */
/** @var int $new_count Number of widgets with status "new". */
?>
<div class="wrap eccw">

	<?php require ECCW_PATH . 'admin/templates/components/header.php'; ?>
	<?php require ECCW_PATH . 'admin/templates/components/notices.php'; ?>

	<form id="eccw-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="eccw_save_settings">
		<?php wp_nonce_field( 'eccw_save_settings', 'eccw_save_nonce' ); ?>

		<?php require ECCW_PATH . 'admin/templates/components/status-bar.php'; ?>

		<?php require ECCW_PATH . 'admin/templates/components/gallery-card.php'; ?>

		<section class="eccw-section">
			<h2 class="eccw-section-title"><?php esc_html_e( 'What each part of your store uses', 'commerce-colors-for-elementor' ); ?></h2>
			<p class="eccw-section-intro">
				<?php esc_html_e( 'Each part already has a sensible colour picked from your palette. Change any of them if you want something different.', 'commerce-colors-for-elementor' ); ?>
			</p>

			<?php if ( ! empty( $mappings ) ) : ?>
				<?php require ECCW_PATH . 'admin/templates/components/elements-card.php'; ?>
			<?php else : ?>
				<div class="eccw-note">
					<?php esc_html_e( 'Nothing found to style yet. Once your store has WooCommerce content, it will be listed here.', 'commerce-colors-for-elementor' ); ?>
				</div>
			<?php endif; ?>
		</section>

		<?php require ECCW_PATH . 'admin/templates/components/controls-card.php'; ?>

		<?php require ECCW_PATH . 'admin/templates/components/advanced-card.php'; ?>

		<?php require ECCW_PATH . 'admin/templates/components/save-bar.php'; ?>
	</form>
</div>
