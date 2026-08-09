<?php defined( 'ABSPATH' ) || exit; ?>
<div class="eccw-wizard-step" data-step="2">
	<h2 style="text-align:center;margin-bottom:8px;"><?php echo esc_html__( 'See the Difference', 'color-changer-for-elementor' ); ?></h2>
	<p style="text-align:center;color:#50575e;margin-bottom:20px;"><?php echo esc_html__( 'Compare your store before and after applying your brand colors.', 'color-changer-for-elementor' ); ?></p>
	<div class="eccw-split">
		<div class="eccw-split-column">
			<div class="eccw-split-header before"><?php echo esc_html__( 'Before (Default)', 'color-changer-for-elementor' ); ?></div>
			<div class="eccw-split-body before"></div>
		</div>
		<div class="eccw-toggle-wrap">
			<button type="button" class="eccw-toggle-btn" title="<?php echo esc_attr__( 'Toggle colors', 'color-changer-for-elementor' ); ?>">↔</button>
			<div class="eccw-toggle-label"><?php echo esc_html__( 'Toggle', 'color-changer-for-elementor' ); ?></div>
		</div>
		<div class="eccw-split-column after">
			<div class="eccw-split-header after"><?php echo esc_html__( 'After (Your Brand Colors)', 'color-changer-for-elementor' ); ?></div>
			<div class="eccw-split-body after"></div>
		</div>
	</div>
</div>
