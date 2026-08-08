<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Live_Editor {

	public function init() {
		add_action( 'template_redirect', array( $this, 'maybe_start_editor' ) );
	}

	public function maybe_start_editor() {
		if ( ! Page_Context::is_woocommerce_page() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Read-only URL flag; capability checked above.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$show = isset( $_GET['wooce_editor'] ) && '1' === $_GET['wooce_editor'];

		if ( ! $show ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_toolbar' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'wooce-editor',
			WOOEC_URL . 'admin/css/wooce-editor.css',
			array(),
			WOOEC_VERSION
		);

		wp_enqueue_script(
			'wooce-editor',
			WOOEC_URL . 'admin/js/wooce-editor.js',
			array(),
			WOOEC_VERSION,
			true
		);

		$kit_colors = CSS_Generator::get_kit_colors();
		$colors_js  = array();

		foreach ( $kit_colors as $id => $hex ) {
			$colors_js[] = array(
				'id'    => $id,
				'label' => ucfirst( str_replace( '_', ' ', $id ) ),
				'hex'   => $hex,
			);
		}

		$settings_url = admin_url( 'admin.php?page=wooce-settings' );

		$registry     = Element_Registry::get_registry();
		$widget_slots = array();

		foreach ( $registry as $widget_key => $definition ) {
			$slots = array();

			foreach ( $definition['slots'] as $slot ) {
				$slots[] = array(
					'slot_id' => $slot['slot_id'],
					'label'   => $slot['label'],
					'states'  => $slot['states'],
					'props'   => $slot['properties'],
				);
			}

			$widget_slots[ $widget_key ] = array(
				'label' => $definition['label'],
				'slots' => $slots,
			);
		}

		wp_localize_script(
			'wooce-editor',
			'wooceData',
			array(
				'kitColors'         => $colors_js,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'wooce_admin_nonce' ),
				'siteUrl'           => home_url(),
				'registrySelectors' => CSS_Generator::get_registry_selectors(),
				'widgetSlots'       => $widget_slots,
				'settingsUrl'       => $settings_url,
			)
		);
	}

	public function render_toolbar() {
		?>
		<div id="wooce-editor-toolbar">
			<div class="wooce-toolbar-inner">
				<div class="wooce-toolbar-idle-state">
					<span class="wooce-toolbar-icon">🎨</span>
					<span class="wooce-toolbar-text"><?php echo esc_html__( 'Click any button, price, or badge on this page to change its color.', 'woocommerce-elementor-colors' ); ?></span>
					<span class="wooce-unsaved-badge" style="display:none;">● Unsaved changes</span>
				</div>
				<div class="wooce-toolbar-actions-global">
					<button type="button" class="button button-hero wooce-save-btn" disabled><?php echo esc_html__( '💾 Save Changes', 'woocommerce-elementor-colors' ); ?></button>
					<button type="button" class="button wooce-reset-btn" title="<?php echo esc_attr__( 'Reset all elements to defaults', 'woocommerce-elementor-colors' ); ?>"><?php echo esc_html__( '↺ Reset', 'woocommerce-elementor-colors' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wooce-settings' ) ); ?>" class="wooce-advanced-link"><?php echo esc_html__( 'Settings table view', 'woocommerce-elementor-colors' ); ?></a>
					<button type="button" class="button wooce-exit-btn"><?php echo esc_html__( '✕ Exit Editor', 'woocommerce-elementor-colors' ); ?></button>
				</div>
			</div>
		</div>

		<div id="wooce-editor-card" class="wooce-editor-card" style="display:none;">
			<div class="wooce-card-arrow"></div>
			<div class="wooce-card-header">
				<span class="wooce-card-element-name"></span>
				<button type="button" class="wooce-card-close">&times;</button>
			</div>
			<div class="wooce-card-body">
				<div class="wooce-card-tabs"></div>
				<div class="wooce-card-palette"></div>
				<div class="wooce-card-color-row">
					<input type="color" class="wooce-card-picker" value="#000000">
					<input type="text" class="wooce-card-hex-input" value="#000000">
				</div>
				<div class="wooce-card-contrast"></div>
				<div class="wooce-card-actions">
					<button type="button" class="button wooce-revert-btn"><?php echo esc_html__( '↺ Reset this element', 'woocommerce-elementor-colors' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
