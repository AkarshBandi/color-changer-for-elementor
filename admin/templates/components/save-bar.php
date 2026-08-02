<?php
/**
 * Advanced Settings - Save bar component.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wooce-save-bar">
	<p class="description">
		<?php echo esc_html__( 'Unsaved changes will be lost if you leave this page.', 'woocommerce-elementor-colors' ); ?>
	</p>
	<?php submit_button( __( 'Save Settings', 'woocommerce-elementor-colors' ), 'primary', 'submit', false ); ?>
</div>
