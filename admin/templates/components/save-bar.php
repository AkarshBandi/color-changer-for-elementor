<?php
/**
 * Elementor Colors - Save bar component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="eccw-save-bar">
	<p class="description">
		<?php echo esc_html__( 'Unsaved changes will be lost if you leave this page.', 'color-changer-for-elementor' ); ?>
	</p>
	<?php submit_button( __( 'Save Settings', 'color-changer-for-elementor' ), 'primary', 'submit', false ); ?>
</div>
