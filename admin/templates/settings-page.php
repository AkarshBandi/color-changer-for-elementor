<div class="wrap wooce-wrap">
	<div class="wooce-header">
		<h1><?php echo esc_html__( 'WooCommerce Elementor Colors', 'woocommerce-elementor-colors' ); ?></h1>
		<div class="wooce-actions">
			<button type="button" class="button wooce-rescan"><?php echo esc_html__( 'Rescan', 'woocommerce-elementor-colors' ); ?></button>
			<button type="button" class="button wooce-dismiss-new"><?php echo esc_html__( 'Dismiss All New', 'woocommerce-elementor-colors' ); ?></button>
			<button type="button" class="button button-secondary wooce-preview-on-site"><?php echo esc_html__( 'Preview on Site', 'woocommerce-elementor-colors' ); ?></button>
		</div>
	</div>

	<?php if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : ?>
		<div class="wooce-notice wooce-notice-success">
			<?php echo esc_html__( 'Settings saved successfully.', 'woocommerce-elementor-colors' ); ?>
		</div>
	<?php endif; ?>

	<div class="wooce-notice" style="background:#e8f0fe;border:1px solid #c5d9f2;color:#1d2327;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
		<span><?php echo esc_html__( '🎨 Try the new Live Editor — click any WooCommerce element on your site to customize colors instantly.', 'woocommerce-elementor-colors' ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'wooce_editor', '1', function_exists( 'wc_get_page_permalink' ) && wc_get_page_permalink( 'shop' ) ? wc_get_page_permalink( 'shop' ) : home_url() ) ); ?>" class="button button-primary" style="margin-left:12px;flex-shrink:0;"><?php echo esc_html__( 'Open Live Editor', 'woocommerce-elementor-colors' ); ?></a>
	</div>

	<div class="wooce-status-bar">
		<span class="wooce-status-item">
			<strong><?php echo esc_html__( 'Detected Colors:', 'woocommerce-elementor-colors' ); ?></strong>
			<?php echo esc_html( count( $kit_colors ) ); ?>
		</span>
		<span class="wooce-status-item">
			<strong><?php echo esc_html__( 'Widgets:', 'woocommerce-elementor-colors' ); ?></strong>
			<?php echo esc_html( count( $mappings ) ); ?>
		</span>
		<span class="wooce-status-item">
			<strong><?php echo esc_html__( 'Version:', 'woocommerce-elementor-colors' ); ?></strong>
			<?php echo esc_html( $saved_version ); ?>
		</span>
	</div>

	<form id="wooce-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wooce_save_settings">
		<?php wp_nonce_field( 'wooce_save_settings', 'wooce_save_nonce' ); ?>

		<div class="wooce-dequeue-section">
			<h2><?php echo esc_html__( 'CSS Dequeue Settings', 'woocommerce-elementor-colors' ); ?></h2>
			<label class="wooce-dequeue-toggle">
				<input type="checkbox" name="wooce_dequeue_core" value="yes" <?php checked( isset( $dequeue_settings['dequeue_core'] ) ? $dequeue_settings['dequeue_core'] : 'yes', 'yes' ); ?>>
				<?php echo esc_html__( 'Dequeue WooCommerce core CSS (general, layout, smallscreen)', 'woocommerce-elementor-colors' ); ?>
			</label>
			<label class="wooce-dequeue-toggle">
				<input type="checkbox" name="wooce_dequeue_blocks" value="yes" <?php checked( isset( $dequeue_settings['dequeue_blocks'] ) ? $dequeue_settings['dequeue_blocks'] : 'yes', 'yes' ); ?>>
				<?php echo esc_html__( 'Dequeue WooCommerce Blocks CSS (block styles)', 'woocommerce-elementor-colors' ); ?>
			</label>
		</div>

		<?php if ( ! empty( $mappings ) ) : ?>
			<div class="wooce-section">
				<div class="wooce-section-header">
					<h2><?php echo esc_html__( 'Element Colors', 'woocommerce-elementor-colors' ); ?></h2>
				</div>
				<?php include WOOEC_PATH . 'admin/templates/elements-table.php'; ?>
			</div>

			<div class="wooce-section">
				<div class="wooce-section-header">
					<h2><?php echo esc_html__( 'Preview Gallery', 'woocommerce-elementor-colors' ); ?></h2>
					<span class="description"><?php echo esc_html__( 'Hover over preview elements to see hover state colors.', 'woocommerce-elementor-colors' ); ?></span>
				</div>
				<?php include WOOEC_PATH . 'admin/templates/preview-gallery.php'; ?>
			</div>
		<?php else : ?>
			<div class="wooce-notice wooce-notice-info">
				<?php echo esc_html__( 'No WooCommerce Elementor widgets found. Add WooCommerce widgets to your Elementor pages and click Rescan.', 'woocommerce-elementor-colors' ); ?>
			</div>
		<?php endif; ?>

		<div class="wooce-save-bar">
			<?php submit_button( __( 'Save Settings', 'woocommerce-elementor-colors' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>
