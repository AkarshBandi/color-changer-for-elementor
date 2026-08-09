<?php defined( 'ABSPATH' ) || exit; ?>
<div class="eccw-wizard-step" data-step="4">
	<div class="eccw-launch-area">
		<div class="eccw-launch-title"><?php echo esc_html__( 'You\'re All Set!', 'color-changer-for-elementor' ); ?></div>
		<div class="eccw-launch-sub"><?php echo esc_html__( 'Your store is now styled with your Elementor brand colors. What would you like to do next?', 'color-changer-for-elementor' ); ?></div>
		<div class="eccw-launch-buttons">
			<button type="button" class="button button-primary button-hero eccw-launch-editor" data-editor-url="<?php echo esc_url( $live_editor_url ); ?>">
				<?php echo esc_html__( 'Open Live Editor', 'color-changer-for-elementor' ); ?>
			</button>
			<button type="button" class="button button-secondary eccw-launch-settings" data-settings-url="<?php echo esc_url( $settings_url ); ?>">
				<?php echo esc_html__( 'Go to Elementor Colors', 'color-changer-for-elementor' ); ?>
			</button>
			<div style="margin-top:8px;">
				<p style="color:#50575e;font-size:13px;margin-bottom:8px;"><?php echo esc_html__( 'Get Pro Tips — receive our "WooCommerce Color Psychology" guide', 'color-changer-for-elementor' ); ?></p>
				<form class="eccw-email-form" style="display:flex;gap:8px;justify-content:center;">
					<input type="email" name="email" placeholder="<?php echo esc_attr__( 'your@email.com', 'color-changer-for-elementor' ); ?>" style="width:220px;">
					<button type="submit" class="button"><?php echo esc_html__( 'Subscribe', 'color-changer-for-elementor' ); ?></button>
				</form>
				<div class="eccw-email-thanks" style="display:none;color:#46b450;"><?php echo esc_html__( 'Thanks! We\'ll notify you about Pro features.', 'color-changer-for-elementor' ); ?></div>
			</div>
		</div>
	</div>
</div>
