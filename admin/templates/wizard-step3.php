<?php defined( 'ABSPATH' ) || exit; ?>
<div class="eccw-wizard-step" data-step="3">
	<h2 style="margin-bottom:8px;"><?php echo esc_html__( 'Your Colors, Shop Layout Safe', 'color-changer-for-elementor' ); ?></h2>
	<p style="color:#50575e;margin-bottom:20px;">
		<?php echo esc_html__( 'Your Elementor colors replace WooCommerce styling on top of the default stylesheet, so the product grid and button sizing stay intact. Nothing gets turned off that would break the layout.', 'color-changer-for-elementor' ); ?>
	</p>
	<div class="eccw-dequeue-visual">
		<div class="eccw-dequeue-form">
			<label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid #dcdcde;border-radius:4px;background:#fff;">
				<input type="checkbox" id="eccw_dequeue_all" name="eccw_dequeue_all" value="yes" checked>
				<span>
					<strong><?php echo esc_html__( 'Replace default styling with your brand colors', 'color-changer-for-elementor' ); ?></strong>
					<span style="display:block;color:#50575e;font-size:12px;margin-top:2px;">
						<?php echo esc_html__( 'Recommended. Your Elementor colors take over on buttons, prices, badges, and more.', 'color-changer-for-elementor' ); ?>
					</span>
				</span>
			</label>
		</div>
		<div class="eccw-dequeue-diagram">
			<div class="eccw-diagram-header"><?php echo esc_html__( 'Visual Preview', 'color-changer-for-elementor' ); ?></div>
			<div class="eccw-diagram-body">
				<div class="eccw-diagram-fake">
					<div style="margin-bottom:8px;"><strong><?php echo esc_html__( 'Shop Page', 'color-changer-for-elementor' ); ?></strong></div>
					<div style="display:flex;gap:8px;margin-bottom:8px;">
						<div style="flex:1;padding:8px;border:1px solid #dcdcde;border-radius:4px;text-align:center;">
							<div class="price">$29.99</div>
							<div class="btn" style="margin-top:6px;"><?php echo esc_html__( 'Add to Cart', 'color-changer-for-elementor' ); ?></div>
						</div>
						<div style="flex:1;padding:8px;border:1px solid #dcdcde;border-radius:4px;text-align:center;">
							<div class="price">$49.99</div>
							<div class="btn" style="margin-top:6px;"><?php echo esc_html__( 'Add to Cart', 'color-changer-for-elementor' ); ?></div>
						</div>
					</div>
				</div>
				<div class="eccw-diagram-overlay core">
					<div class="eccw-diagram-warning"><?php echo esc_html__( 'Layout stays intact — grid and button sizing are preserved.', 'color-changer-for-elementor' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>