<?php
/**
 * Store Design — status hero.
 *
 * The master switch, a one-line answer to "is this working", and a preview
 * built from the colours the store is actually serving right now.
 *
 * The preview is the point of this card. Everything else on the screen is a
 * claim about what the store looks like; this is the only place the owner can
 * check the claim without leaving the page. It is painted from the same saved
 * mappings the front-end stylesheet is generated from, so if it looks wrong
 * here it looks wrong there.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $settings Resolved plugin settings. */
/** @var array $mappings All widget mappings. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var bool $has_own_kit Whether the kit defines its own colours. */
/** @var bool $global_chrome Whether the site header/footer carries WooCommerce. */
/** @var bool $has_own_fonts Whether the kit defines typography of its own. */
/** @var string $kit_name Title of the active Elementor kit. */
/** @var string $kit_edit_url Deep link into the Elementor kit editor. */

$eccw_settings = isset( $settings ) && is_array( $settings )
	? $settings
	: ElementorColorChanger\Settings::all();

$eccw_is_on = ! empty( $eccw_settings['enabled'] );

// Resolved here rather than inherited. This partial is included into the
// caller's scope, and the previous version of this file defined it locally —
// so referencing it without defining it produced a checklist that reported
// "no Elementor kit found" on a site that has one.
$eccw_kit_id = get_option( 'elementor_active_kit' );

/**
 * Resolve the colour a store element is currently painted with.
 *
 * Reads the element's own saved mapping rather than guessing from the kit, so
 * the preview follows the choices made further down this page.
 */
$eccw_slot_color = static function ( $widget_key, $fallback_token ) use ( $mappings ) {
	$slots = isset( $mappings[ $widget_key ]['slots'] ) ? $mappings[ $widget_key ]['slots'] : array();

	foreach ( $slots as $slot_id => $config ) {
		if ( substr( $slot_id, -7 ) === '_normal' && ! empty( $config['color'] ) ) {
			return ElementorColorChanger\CSS_Generator::resolve_color( $config['color'] );
		}
	}

	foreach ( $slots as $config ) {
		if ( ! empty( $config['color'] ) ) {
			return ElementorColorChanger\CSS_Generator::resolve_color( $config['color'] );
		}
	}

	return ElementorColorChanger\CSS_Generator::resolve_color( $fallback_token );
};

$eccw_button_bg   = $eccw_slot_color( 'wc-add-to-cart', 'button_normal' );
$eccw_button_text = empty( $eccw_settings['auto_contrast'] )
	? ElementorColorChanger\CSS_Generator::resolve_color( 'button_text' )
	: ElementorColorChanger\CSS_Generator::contrast_text( $eccw_button_bg );
$eccw_price_color = $eccw_slot_color( 'wc-product-price', 'primary' );
$eccw_badge_bg    = $eccw_slot_color( 'wc-sale-badge', 'accent' );
$eccw_badge_text  = ElementorColorChanger\CSS_Generator::contrast_text( $eccw_badge_bg );
$eccw_star_color  = $eccw_slot_color( 'wc-star-rating', 'accent' );
$eccw_link_color  = $eccw_slot_color( 'wc-general-links', 'link_normal' );
$eccw_body_color  = ElementorColorChanger\CSS_Generator::resolve_color( 'text' );
$eccw_head_color  = ElementorColorChanger\CSS_Generator::resolve_color( 'h3' );

// Fonts only enter the preview when the store is actually using them.
$eccw_fonts     = array(
	'head'   => '',
	'body'   => '',
	'button' => '',
);
$eccw_use_fonts = ! empty( $eccw_settings['apply_typography'] );

if ( $eccw_use_fonts ) {
	$eccw_kit_type = ElementorColorChanger\Typography::kit_typography();

	foreach ( array(
		'head'   => 'h3',
		'body'   => 'body',
		'button' => 'button',
	) as $eccw_key => $eccw_token ) {
		if ( ! empty( $eccw_kit_type[ $eccw_token ]['family'] ) ) {
			$eccw_fonts[ $eccw_key ] = $eccw_kit_type[ $eccw_token ]['family'];
		}
	}
}

$eccw_font_style = static function ( $family ) {
	return '' === $family ? '' : 'font-family:' . $family . ',sans-serif;';
};
?>
<section class="eccw-hero<?php echo $eccw_is_on ? '' : ' is-off'; ?>">

	<div class="eccw-hero-main">
		<label class="eccw-switch-row">
			<span class="eccw-switch">
				<input
					type="checkbox"
					name="eccw_enabled"
					id="eccw-enabled"
					data-testid="eccw-toggle-enabled"
					data-eccw-live="enabled"
					value="yes"
					<?php checked( $eccw_is_on ); ?>>
				<span class="eccw-switch-track" aria-hidden="true"><span class="eccw-switch-knob"></span></span>
			</span>
			<span class="eccw-switch-text">
				<strong class="eccw-hero-state" data-eccw-on="<?php esc_attr_e( 'Your store is using your design', 'color-changer-for-elementor' ); ?>" data-eccw-off="<?php esc_attr_e( 'Your store is using default WooCommerce colours', 'color-changer-for-elementor' ); ?>">
					<?php
					echo $eccw_is_on
						? esc_html__( 'Your store is using your design', 'color-changer-for-elementor' )
						: esc_html__( 'Your store is using default WooCommerce colours', 'color-changer-for-elementor' );
					?>
				</strong>
				<span class="eccw-hero-sub">
					<?php esc_html_e( 'Turn this off to put the store back to normal at once. Nothing you have chosen is deleted.', 'color-changer-for-elementor' ); ?>
				</span>
			</span>
		</label>

		<?php
		// Four checks, as the product spec asks for, each either true or a link
		// to the one place that would make it true. A checklist that can only
		// ever say "yes" is decoration; these can all fail.
		$eccw_checks = array(
			array(
				'ok'  => (bool) $eccw_kit_id,
				'yes' => '' !== $kit_name
					/* translators: %s: name of the Elementor kit, e.g. "Default Kit". */
					? sprintf( __( 'Connected to your Elementor kit, “%s”.', 'color-changer-for-elementor' ), $kit_name )
					: __( 'Connected to your Elementor kit.', 'color-changer-for-elementor' ),
				'no'  => __( 'No Elementor kit found yet.', 'color-changer-for-elementor' ),
				'fix' => $kit_edit_url,
			),
			array(
				'ok'  => $has_own_kit,
				'yes' => __( 'Your brand colours were found.', 'color-changer-for-elementor' ),
				'no'  => __( 'No brand colours set — stand-in colours are being used.', 'color-changer-for-elementor' ),
				'fix' => $kit_edit_url,
			),
			array(
				'ok'  => $has_own_fonts,
				'yes' => empty( $eccw_settings['apply_typography'] )
					? __( 'Your fonts were found, but are not being applied.', 'color-changer-for-elementor' )
					: __( 'Your fonts were found and are being applied.', 'color-changer-for-elementor' ),
				'no'  => __( 'No fonts set in Elementor, so only colours are applied.', 'color-changer-for-elementor' ),
				'fix' => $kit_edit_url,
			),
			array(
				'ok'  => $eccw_is_on && ! empty( $mappings ),
				'yes' => $global_chrome
					? __( 'Styling is active. Your header shows a cart, so it applies across the whole site.', 'color-changer-for-elementor' )
					: __( 'Styling is active on your shop pages. The rest of your site is left alone.', 'color-changer-for-elementor' ),
				'no'  => __( 'Styling is switched off. Your store is showing default WooCommerce colours.', 'color-changer-for-elementor' ),
				'fix' => '',
			),
		);
		?>
		<ul class="eccw-facts">
			<?php foreach ( $eccw_checks as $eccw_check ) : ?>
				<li class="<?php echo $eccw_check['ok'] ? 'is-ok' : 'is-todo'; ?>">
					<span class="eccw-facts-mark" aria-hidden="true"><?php echo $eccw_check['ok'] ? '&#10003;' : '&#33;'; ?></span>
					<span class="screen-reader-text">
						<?php echo $eccw_check['ok'] ? esc_html__( 'Done:', 'color-changer-for-elementor' ) : esc_html__( 'Needs attention:', 'color-changer-for-elementor' ); ?>
					</span>
					<?php if ( ! $eccw_check['ok'] && '' !== $eccw_check['fix'] ) : ?>
						<a href="<?php echo esc_url( $eccw_check['fix'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $eccw_check['no'] ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $eccw_check['ok'] ? $eccw_check['yes'] : $eccw_check['no'] ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			<li class="is-ok">
				<span class="eccw-facts-mark" aria-hidden="true">&#10003;</span>
				<?php
				printf(
					/* translators: %s: number of store elements. */
					esc_html( _n( '%s part of your store is covered.', '%s parts of your store are covered.', count( $mappings ), 'color-changer-for-elementor' ) ),
					esc_html( number_format_i18n( count( $mappings ) ) )
				);
				?>
			</li>
		</ul>
	</div>

	<div class="eccw-hero-preview" aria-hidden="true">
		<span class="eccw-preview-caption"><?php esc_html_e( 'How your store looks', 'color-changer-for-elementor' ); ?></span>

		<div class="eccw-mock" data-eccw-mock>
			<div class="eccw-mock-image">
				<span class="eccw-mock-badge" data-eccw-paint="badge" style="background-color:<?php echo esc_attr( $eccw_badge_bg ); ?>;color:<?php echo esc_attr( $eccw_badge_text ); ?>;">
					<?php esc_html_e( 'Sale!', 'color-changer-for-elementor' ); ?>
				</span>
			</div>

			<div class="eccw-mock-body">
				<span class="eccw-mock-title" data-eccw-paint="heading" style="color:<?php echo esc_attr( $eccw_head_color ); ?>;<?php echo esc_attr( $eccw_font_style( $eccw_fonts['head'] ) ); ?>">
					<?php esc_html_e( 'Linen Shirt', 'color-changer-for-elementor' ); ?>
				</span>

				<span class="eccw-mock-stars" data-eccw-paint="stars" style="color:<?php echo esc_attr( $eccw_star_color ); ?>;">&#9733;&#9733;&#9733;&#9733;&#9734;</span>

				<span class="eccw-mock-price" data-eccw-paint="price" style="color:<?php echo esc_attr( $eccw_price_color ); ?>;">
					<s class="eccw-mock-was">&#36;79.00</s> &#36;59.00
				</span>

				<span class="eccw-mock-desc" data-eccw-paint="body" style="color:<?php echo esc_attr( $eccw_body_color ); ?>;<?php echo esc_attr( $eccw_font_style( $eccw_fonts['body'] ) ); ?>">
					<?php esc_html_e( 'Soft, breathable and cut a little loose.', 'color-changer-for-elementor' ); ?>
				</span>

				<span class="eccw-mock-button" data-eccw-paint="button" style="background-color:<?php echo esc_attr( $eccw_button_bg ); ?>;color:<?php echo esc_attr( $eccw_button_text ); ?>;<?php echo esc_attr( $eccw_font_style( $eccw_fonts['button'] ) ); ?>">
					<?php esc_html_e( 'Add to cart', 'color-changer-for-elementor' ); ?>
				</span>

				<span class="eccw-mock-link" data-eccw-paint="link" style="color:<?php echo esc_attr( $eccw_link_color ); ?>;">
					<?php esc_html_e( 'More in Shirts', 'color-changer-for-elementor' ); ?>
				</span>
			</div>
		</div>
	</div>
</section>
