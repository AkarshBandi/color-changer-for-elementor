<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wooce-wizard-step" data-step="4">
	<div class="wooce-launch-area">
		<span class="wooce-launch-icon">🎉</span>
		<div class="wooce-launch-title"><?php echo esc_html__( 'You\'re All Set!', 'woocommerce-elementor-colors' ); ?></div>
		<div class="wooce-launch-sub"><?php echo esc_html__( 'Your store is now styled with your Elementor brand colors. What would you like to do next?', 'woocommerce-elementor-colors' ); ?></div>
		<div class="wooce-launch-buttons">
			<button type="button" class="button button-primary button-hero wooce-launch-editor" data-editor-url="<?php echo esc_url( $live_editor_url ); ?>">
				<?php echo esc_html__( '🎨 Open Live Editor', 'woocommerce-elementor-colors' ); ?>
			</button>
			<button type="button" class="button button-secondary wooce-launch-settings" data-settings-url="<?php echo esc_url( $settings_url ); ?>">
				<?php echo esc_html__( '⚙️ Go to Advanced Settings', 'woocommerce-elementor-colors' ); ?>
			</button>
			<div style="margin-top:8px;">
				<p style="color:#50575e;font-size:13px;margin-bottom:8px;"><?php echo esc_html__( '📧 Get Pro Tips — receive our "WooCommerce Color Psychology" guide', 'woocommerce-elementor-colors' ); ?></p>
				<form class="wooce-email-form" style="display:flex;gap:8px;justify-content:center;">
					<input type="email" name="email" placeholder="<?php echo esc_attr__( 'your@email.com', 'woocommerce-elementor-colors' ); ?>" style="width:220px;">
					<button type="submit" class="button"><?php echo esc_html__( 'Subscribe', 'woocommerce-elementor-colors' ); ?></button>
				</form>
				<div class="wooce-email-thanks" style="display:none;color:#46b450;"><?php echo esc_html__( '✅ Thanks! We\'ll notify you about Pro features.', 'woocommerce-elementor-colors' ); ?></div>
			</div>
		</div>
	</div>
</div>
