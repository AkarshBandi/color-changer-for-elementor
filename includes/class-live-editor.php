<?php

namespace ElementorColorChanger;

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
		$show = isset( $_GET['eccw_editor'] ) && '1' === $_GET['eccw_editor'];

		if ( ! $show ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_toolbar' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'eccw-editor',
			ECCw_URL . 'admin/css/eccw-editor.css',
			array(),
			ECCw_VERSION
		);

		wp_enqueue_script(
			'eccw-editor',
			ECCw_URL . 'admin/js/eccw-editor.js',
			array(),
			ECCw_VERSION,
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

		$settings_url = admin_url( 'admin.php?page=eccw-settings' );

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

		$live_draft = get_transient( 'eccw_live_draft' );

		// Coverage counter: how many of the registry widgets are currently
		// configured (have at least one explicitly set color), combining the
		// saved mappings and any in-progress live draft.
		$saved      = get_option( 'eccw_colors_mappings', array() );
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
			'eccw-editor',
			'eccwData',
			array(
				'kitColors'         => $colors_js,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'eccw_admin_nonce' ),
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
		<div id="eccw-editor-toolbar">
			<div class="eccw-toolbar-inner">
				<div class="eccw-toolbar-idle-state">
					<span class="eccw-toolbar-text"><?php echo esc_html__( 'Click any button, price, or badge on this page to change its color.', 'color-changer-for-elementor' ); ?></span>
					<span class="eccw-coverage-badge" title="<?php echo esc_attr__( 'How many of the detected WooCommerce element types are styled with your brand colors', 'color-changer-for-elementor' ); ?>"></span>
					<span class="eccw-unsaved-badge" style="display:none;">● Unsaved changes</span>
				</div>
				<div class="eccw-toolbar-actions-global">
					<button type="button" class="button eccw-share-btn" title="<?php echo esc_attr__( 'Generate a temporary preview link to share with a client', 'color-changer-for-elementor' ); ?>"><?php echo esc_html__( 'Share', 'color-changer-for-elementor' ); ?></button>
					<button type="button" class="button eccw-undo-btn" title="<?php echo esc_attr__( 'Undo last save (Ctrl+Z)', 'color-changer-for-elementor' ); ?>"><?php echo esc_html__( 'Undo', 'color-changer-for-elementor' ); ?></button>
					<button type="button" class="button button-hero eccw-save-btn" disabled><?php echo esc_html__( 'Save Changes', 'color-changer-for-elementor' ); ?></button>
					<button type="button" class="button eccw-reset-btn" title="<?php echo esc_attr__( 'Reset all elements to defaults', 'color-changer-for-elementor' ); ?>"><?php echo esc_html__( 'Reset', 'color-changer-for-elementor' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=eccw-settings' ) ); ?>" class="eccw-advanced-link"><?php echo esc_html__( 'Settings table view', 'color-changer-for-elementor' ); ?></a>
					<button type="button" class="button eccw-exit-btn"><?php echo esc_html__( 'Exit Editor', 'color-changer-for-elementor' ); ?></button>
				</div>
			</div>
		</div>

		<div id="eccw-editor-panel" class="eccw-editor-panel" aria-hidden="true">
			<div class="eccw-panel-header">
				<div class="eccw-panel-title">
					<span class="eccw-panel-kicker"><?php echo esc_html__( 'Customize', 'color-changer-for-elementor' ); ?></span>
					<span class="eccw-panel-element-name"></span>
				</div>
				<button type="button" class="eccw-panel-close" title="<?php echo esc_attr__( 'Close panel', 'color-changer-for-elementor' ); ?>" aria-label="<?php echo esc_attr__( 'Close panel', 'color-changer-for-elementor' ); ?>">&times;</button>
			</div>
			<div class="eccw-panel-body">
				<div class="eccw-panel-tabs"></div>
				<div class="eccw-panel-palette"></div>
				<div class="eccw-panel-color-row">
					<label class="eccw-panel-field-label" for="eccw-panel-picker"><?php echo esc_html__( 'Custom color', 'color-changer-for-elementor' ); ?></label>
					<div class="eccw-panel-color-controls">
						<input type="color" id="eccw-panel-picker" class="eccw-panel-picker" value="#000000">
						<input type="text" class="eccw-panel-hex-input" value="#000000" aria-label="<?php echo esc_attr__( 'Hex color code', 'color-changer-for-elementor' ); ?>">
					</div>
				</div>
				<div class="eccw-panel-contrast"></div>
				<div class="eccw-panel-actions">
					<button type="button" class="eccw-revert-btn"><?php echo esc_html__( 'Reset this element', 'color-changer-for-elementor' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
