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

		$live_draft = get_transient( 'wooce_live_draft' );

		// Coverage counter: how many of the registry widgets are currently
		// configured (have at least one explicitly set color), combining the
		// saved mappings and any in-progress live draft.
		$saved      = get_option( 'wooce_colors_mappings', array() );
		$configured = 0;
		$registry   = Element_Registry::get_registry();

		foreach ( $registry as $widget_key => $definition ) {
			$has_color = false;

			if ( isset( $saved['widgets'][ $widget_key ]['slots'] ) ) {
				foreach ( $saved['widgets'][ $widget_key ]['slots'] as $slot_data ) {
					if ( isset( $slot_data['color'] ) && ! empty( $slot_data['color'] ) ) {
						$has_color = true;
						break;
					}
				}
			}

			if ( ! $has_color && isset( $live_draft[ $widget_key ]['slots'] ) ) {
				foreach ( $live_draft[ $widget_key ]['slots'] as $slot_data ) {
					if ( isset( $slot_data['color'] ) && ! empty( $slot_data['color'] ) ) {
						$has_color = true;
						break;
					}
				}
			}

			if ( $has_color ) {
				++$configured;
			}
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
				'liveDraft'         => is_array( $live_draft ) ? $live_draft : array(),
				'coverage'          => array(
					'configured' => $configured,
					'total'      => count( $registry ),
				),
			)
		);
	}

	public function render_toolbar() {
		?>
		<div id="wooce-editor-toolbar">
			<div class="wooce-toolbar-inner">
				<div class="wooce-toolbar-idle-state">
					<span class="wooce-toolbar-text"><?php echo esc_html__( 'Click any button, price, or badge on this page to change its color.', 'woocommerce-elementor-colors' ); ?></span>
					<span class="wooce-coverage-badge" title="<?php echo esc_attr__( 'How many of the detected WooCommerce element types are styled with your brand colors', 'woocommerce-elementor-colors' ); ?>"></span>
					<span class="wooce-unsaved-badge" style="display:none;">● Unsaved changes</span>
				</div>
				<div class="wooce-toolbar-actions-global">
					<button type="button" class="button wooce-share-btn" title="<?php echo esc_attr__( 'Generate a temporary preview link to share with a client', 'woocommerce-elementor-colors' ); ?>"><?php echo esc_html__( 'Share', 'woocommerce-elementor-colors' ); ?></button>
					<button type="button" class="button wooce-undo-btn" title="<?php echo esc_attr__( 'Undo last save (Ctrl+Z)', 'woocommerce-elementor-colors' ); ?>"><?php echo esc_html__( 'Undo', 'woocommerce-elementor-colors' ); ?></button>
					<button type="button" class="button button-hero wooce-save-btn" disabled><?php echo esc_html__( 'Save Changes', 'woocommerce-elementor-colors' ); ?></button>
					<button type="button" class="button wooce-reset-btn" title="<?php echo esc_attr__( 'Reset all elements to defaults', 'woocommerce-elementor-colors' ); ?>"><?php echo esc_html__( 'Reset', 'woocommerce-elementor-colors' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wooce-settings' ) ); ?>" class="wooce-advanced-link"><?php echo esc_html__( 'Settings table view', 'woocommerce-elementor-colors' ); ?></a>
					<button type="button" class="button wooce-exit-btn"><?php echo esc_html__( 'Exit Editor', 'woocommerce-elementor-colors' ); ?></button>
				</div>
			</div>
		</div>

		<div id="wooce-editor-panel" class="wooce-editor-panel" aria-hidden="true">
			<div class="wooce-panel-header">
				<div class="wooce-panel-title">
					<span class="wooce-panel-kicker"><?php echo esc_html__( 'Customize', 'woocommerce-elementor-colors' ); ?></span>
					<span class="wooce-panel-element-name"></span>
				</div>
				<button type="button" class="wooce-panel-close" title="<?php echo esc_attr__( 'Close panel', 'woocommerce-elementor-colors' ); ?>" aria-label="<?php echo esc_attr__( 'Close panel', 'woocommerce-elementor-colors' ); ?>">&times;</button>
			</div>
			<div class="wooce-panel-body">
				<div class="wooce-panel-tabs"></div>
				<div class="wooce-panel-palette"></div>
				<div class="wooce-panel-color-row">
					<label class="wooce-panel-field-label" for="wooce-panel-picker"><?php echo esc_html__( 'Custom color', 'woocommerce-elementor-colors' ); ?></label>
					<div class="wooce-panel-color-controls">
						<input type="color" id="wooce-panel-picker" class="wooce-panel-picker" value="#000000">
						<input type="text" class="wooce-panel-hex-input" value="#000000" aria-label="<?php echo esc_attr__( 'Hex color code', 'woocommerce-elementor-colors' ); ?>">
					</div>
				</div>
				<div class="wooce-panel-contrast"></div>
				<div class="wooce-panel-actions">
					<button type="button" class="wooce-revert-btn"><?php echo esc_html__( 'Reset this element', 'woocommerce-elementor-colors' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
