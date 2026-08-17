<?php
/**
 * Store Design — save bar.
 *
 * Sticks to the bottom of the viewport. The screen is taller than one screen on
 * most stores, and a save button that scrolls out of sight reads as "there is
 * nothing to save".
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="eccw-savebar">
	<span class="eccw-savebar-hint" data-eccw-dirty-hint hidden>
		<?php esc_html_e( 'You have unsaved changes.', 'color-changer-for-elementor' ); ?>
	</span>
	<?php
	// Not name="submit": a control named `submit` shadows the form's own
	// submit() method, so `document.getElementById('eccw-form').submit()`
	// throws "not a function". Nothing here calls it today, and the next
	// person to try would lose an hour to it. The save handler authenticates
	// on the nonce and never reads this name.
	?>
	<button type="submit" name="eccw_submit" class="eccw-btn eccw-btn-primary">
		<?php esc_html_e( 'Save changes', 'color-changer-for-elementor' ); ?>
	</button>
</div>
