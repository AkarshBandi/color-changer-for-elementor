<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wooce-wizard-step" data-step="3">
	<h2 style="margin-bottom:8px;"><?php echo esc_html__( 'Your Colors, Shop Layout Safe', 'woocommerce-elementor-colors' ); ?></h2>
	<p style="color:#50575e;margin-bottom:20px;">
		<?php echo esc_html__( 'Your Elementor colors replace WooCommerce styling on top of the default stylesheet, so the product grid and button sizing stay intact. Nothing gets turned off that would break the layout.', 'woocommerce-elementor-colors' ); ?>
	</p>
	<div class="wooce-dequeue-visual">
		<div class="wooce-dequeue-form">
			<label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid #dcdcde;border-radius:4px;background:#fff;">
				<input type="checkbox" id="wooce_dequeue_all" name="wooce_dequeue_all" value="yes" checked>
				<span>
					<strong><?php echo esc_html__( 'Replace default styling with your brand colors', 'woocommerce-elementor-colors' ); ?></strong>
					<span style="display:block;color:#50575e;font-size:12px;margin-top:2px;">
						<?php echo esc_html__( 'Recommended. Your Elementor colors take over on buttons, prices, badges, and more.', 'woocommerce-elementor-colors' ); ?>
					</span>
				</span>
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
					<div class="wooce-diagram-warning"><?php echo esc_html__( '✓ Layout stays intact — grid and button sizing are preserved.', 'woocommerce-elementor-colors' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>