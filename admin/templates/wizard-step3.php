<div class="wooce-wizard-step" data-step="3">
	<h2 style="margin-bottom:8px;"><?php echo esc_html__( 'CSS Dequeue Settings', 'woocommerce-elementor-colors' ); ?></h2>
	<p style="color:#50575e;margin-bottom:20px;"><?php echo esc_html__( 'Choose which default WooCommerce stylesheets to disable. Your Elementor styles will replace them.', 'woocommerce-elementor-colors' ); ?></p>
	<div class="wooce-dequeue-visual">
		<div class="wooce-dequeue-form">
			<label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:12px;border:1px solid #dcdcde;border-radius:4px;background:#fff;">
				<input type="checkbox" id="wooce_dequeue_core" name="wooce_dequeue_core" value="yes" <?php checked( isset( $dequeue_settings['dequeue_core'] ) ? $dequeue_settings['dequeue_core'] : 'yes', 'yes' ); ?>>
				<span><?php echo esc_html__( 'Dequeue WooCommerce core CSS (general, layout, smallscreen)', 'woocommerce-elementor-colors' ); ?></span>
			</label>
			<label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid #dcdcde;border-radius:4px;background:#fff;">
				<input type="checkbox" id="wooce_dequeue_blocks" name="wooce_dequeue_blocks" value="yes" <?php checked( isset( $dequeue_settings['dequeue_blocks'] ) ? $dequeue_settings['dequeue_blocks'] : 'yes', 'yes' ); ?>>
				<span><?php echo esc_html__( 'Dequeue WooCommerce Blocks CSS (block styles)', 'woocommerce-elementor-colors' ); ?></span>
			</label>
		</div>
		<div class="wooce-dequeue-diagram">
			<div class="wooce-diagram-header"><?php echo esc_html__( 'Visual Preview', 'woocommerce-elementor-colors' ); ?></div>
			<div class="wooce-diagram-body">
				<div class="wooce-diagram-fake">
					<div style="margin-bottom:8px;"><strong><?php echo esc_html__( 'Shop Page', 'woocommerce-elementor-colors' ); ?></strong></div>
					<div style="display:flex;gap:8px;margin-bottom:8px;">
						<div style="flex:1;padding:8px;border:1px solid #dcdcde;border-radius:4px;text-align:center;">
							<div class="price">$29.99</div>
							<div class="btn" style="margin-top:6px;"><?php echo esc_html__( 'Add to Cart', 'woocommerce-elementor-colors' ); ?></div>
						</div>
						<div style="flex:1;padding:8px;border:1px solid #dcdcde;border-radius:4px;text-align:center;">
							<div class="price">$49.99</div>
							<div class="btn" style="margin-top:6px;"><?php echo esc_html__( 'Add to Cart', 'woocommerce-elementor-colors' ); ?></div>
						</div>
					</div>
				</div>
				<div class="wooce-diagram-overlay core">
					<div class="wooce-diagram-warning"><?php echo esc_html__( '⚠ Without dequeuing, core styles may override your Elementor button colors.', 'woocommerce-elementor-colors' ); ?></div>
				</div>
				<div class="wooce-diagram-overlay blocks">
					<div class="wooce-diagram-warning"><?php echo esc_html__( '⚠ Block styles may conflict with your custom colors on cart and checkout pages.', 'woocommerce-elementor-colors' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>
