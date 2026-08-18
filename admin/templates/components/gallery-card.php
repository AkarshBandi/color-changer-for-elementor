<?php
/**
 * Store Design — your palette.
 *
 * The colours the store draws from, under the names their owner gave them in
 * Elementor. Read-only on purpose: this is where the colours come from, and
 * the one place to change them is Elementor itself. A second editor here would
 * be a second source of truth.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $kit_labels Token id => human label. */
/** @var string $kit_edit_url Deep link into the Elementor kit editor. */

// The kit exposes its four brand colours plus whatever the owner added, and
// then a second set derived from the Button/Link/Heading/Body tabs. Splitting
// them matters: the first group is the palette people think they have, and the
// second is thirteen entries deep, which buries it if shown as one grid.
$eccw_derived_ids = array(
	'button_normal',
	'button_hover',
	'button_text',
	'button_hover_text',
	'link_normal',
	'link_hover',
	'body',
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
);

$eccw_palette = array();
$eccw_derived = array();

foreach ( $kit_colors as $eccw_id => $eccw_hex ) {
	if ( in_array( $eccw_id, $eccw_derived_ids, true ) ) {
		$eccw_derived[ $eccw_id ] = $eccw_hex;
		continue;
	}

	$eccw_palette[ $eccw_id ] = $eccw_hex;
}

$eccw_swatch = static function ( $id, $hex, $labels ) {
	$label = isset( $labels[ $id ] ) ? $labels[ $id ] : ucfirst( str_replace( '_', ' ', $id ) );
	?>
	<li class="eccw-swatch">
		<span class="eccw-swatch-chip" style="background-color:<?php echo esc_attr( $hex ); ?>;"></span>
		<span class="eccw-swatch-name"><?php echo esc_html( $label ); ?></span>
		<span class="eccw-swatch-hex"><?php echo esc_html( strtoupper( $hex ) ); ?></span>
	</li>
	<?php
};
?>
<section class="eccw-section">
	<h2 class="eccw-section-title"><?php esc_html_e( 'Your colours', 'commerce-colors-for-elementor' ); ?></h2>
	<p class="eccw-section-intro">
		<?php esc_html_e( 'These come from Elementor. Change them there and your store follows — you do not need to come back here.', 'commerce-colors-for-elementor' ); ?>
	</p>

	<div class="eccw-card">
		<?php if ( empty( $kit_colors ) ) : ?>
			<div class="eccw-note">
				<?php esc_html_e( 'No colours found yet.', 'commerce-colors-for-elementor' ); ?>
				<a href="<?php echo esc_url( $kit_edit_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Set your brand colours in Elementor', 'commerce-colors-for-elementor' ); ?>
				</a>
			</div>
		<?php else : ?>
			<ul class="eccw-swatches">
				<?php
				foreach ( $eccw_palette as $eccw_id => $eccw_hex ) {
					$eccw_swatch( $eccw_id, $eccw_hex, $kit_labels );
				}
				?>
			</ul>

			<?php if ( ! empty( $eccw_derived ) ) : ?>
				<details class="eccw-details">
					<summary><?php esc_html_e( 'Colours taken from your Elementor buttons, links and text', 'commerce-colors-for-elementor' ); ?></summary>
					<ul class="eccw-swatches eccw-swatches-compact">
						<?php
						foreach ( $eccw_derived as $eccw_id => $eccw_hex ) {
							$eccw_swatch( $eccw_id, $eccw_hex, $kit_labels );
						}
						?>
					</ul>
				</details>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>
