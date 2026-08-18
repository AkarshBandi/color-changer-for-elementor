<?php
/**
 * Store Design — how it behaves.
 *
 * Four switches, each written as a thing the store does rather than a thing the
 * plugin does. They sit below the colours because they are decisions people make
 * once, if ever — not the reason anyone opened this screen.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $settings Resolved plugin settings. */

$eccw_switches = array(
	array(
		'name'  => 'eccw_apply_typography',
		'key'   => 'apply_typography',
		'test'  => 'eccw-toggle-typography',
		'title' => __( 'Use my fonts as well as my colours', 'commerce-colors-for-elementor' ),
		'help'  => __( 'Product titles, prices and buttons pick up the fonts you chose in Elementor. Only the font and its weight — sizes and spacing are left alone, so nothing on your pages moves.', 'commerce-colors-for-elementor' ),
	),
	array(
		'name'  => 'eccw_auto_contrast',
		'key'   => 'auto_contrast',
		'test'  => 'eccw-toggle-contrast',
		'title' => __( 'Keep text readable for me', 'commerce-colors-for-elementor' ),
		'help'  => __( 'Button labels switch between black and white so they can always be read, and any text colour too faint against the page is darkened just enough. Turn this off to use your colours exactly as picked.', 'commerce-colors-for-elementor' ),
	),
	array(
		'name'  => 'eccw_derive_states',
		'key'   => 'derive_states',
		'test'  => 'eccw-toggle-derive',
		'title' => __( 'Work out hover colours for me', 'commerce-colors-for-elementor' ),
		'help'  => __( 'Shades for hovering, clicking and unavailable buttons are worked out from each part’s main colour, so the whole store reacts the same way. Turn this off to pick every one yourself under “More options”.', 'commerce-colors-for-elementor' ),
	),
	array(
		'name'  => 'eccw_force_important',
		'key'   => 'force_important',
		'test'  => 'eccw-toggle-important',
		'title' => __( 'Win against my theme', 'commerce-colors-for-elementor' ),
		'help'  => __( 'Leave this on if your colours are not showing up. Turn it off only if you write your own styling code and want yours to come first.', 'commerce-colors-for-elementor' ),
	),
);
?>
<section class="eccw-section">
	<h2 class="eccw-section-title"><?php esc_html_e( 'How it behaves', 'commerce-colors-for-elementor' ); ?></h2>
	<p class="eccw-section-intro">
		<?php esc_html_e( 'Sensible for most stores as they are. Change them if something does not look right.', 'commerce-colors-for-elementor' ); ?>
	</p>

	<div class="eccw-card eccw-card-switches">
		<?php foreach ( $eccw_switches as $eccw_switch ) : ?>
			<label class="eccw-switch-row">
				<span class="eccw-switch">
					<input
						type="checkbox"
						name="<?php echo esc_attr( $eccw_switch['name'] ); ?>"
						data-testid="<?php echo esc_attr( $eccw_switch['test'] ); ?>"
						value="yes"
						<?php checked( ! empty( $settings[ $eccw_switch['key'] ] ) ); ?>>
					<span class="eccw-switch-track" aria-hidden="true"><span class="eccw-switch-knob"></span></span>
				</span>
				<span class="eccw-switch-text">
					<strong><?php echo esc_html( $eccw_switch['title'] ); ?></strong>
					<span class="eccw-hero-sub"><?php echo esc_html( $eccw_switch['help'] ); ?></span>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
</section>
