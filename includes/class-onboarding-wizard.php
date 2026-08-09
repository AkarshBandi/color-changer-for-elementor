<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

class Onboarding_Wizard {

	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_wizard' ) );
		add_action( 'admin_menu', array( $this, 'register_wizard_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		add_action( 'wp_ajax_eccw_wizard_ab_test', array( $this, 'ajax_ab_test' ) );
		add_action( 'wp_ajax_eccw_complete_onboarding', array( $this, 'ajax_complete_onboarding' ) );
		add_action( 'wp_ajax_eccw_save_email', array( $this, 'ajax_save_email' ) );
		add_action( 'wp_ajax_eccw_dismiss_onboarding', array( $this, 'ajax_dismiss_onboarding' ) );
	}

	public function maybe_redirect_to_wizard() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( get_option( 'eccw_onboarding_completed' ) ) {
			return;
		}

		if ( get_option( 'eccw_wizard_dismissed' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Read-only checks: don't redirect when already on the wizard page
		// or when a form is being submitted.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'eccw-wizard' === $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			return;
		}

		$redirect_once = get_transient( 'eccw_wizard_redirect' );

		if ( empty( $redirect_once ) ) {
			return;
		}

		delete_transient( 'eccw_wizard_redirect' );

		wp_safe_redirect( admin_url( 'admin.php?page=eccw-wizard' ) );
		exit;
	}

	public function admin_notice() {
		if ( get_option( 'eccw_onboarding_completed' ) ) {
			return;
		}

		if ( get_option( 'eccw_wizard_dismissed' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'admin_page_eccw-wizard' === $screen->id ) {
			return;
		}

		$wizard_url   = admin_url( 'admin.php?page=eccw-wizard' );
		$dismiss_url  = admin_url( 'admin-ajax.php?action=eccw_dismiss_onboarding&nonce=' . wp_create_nonce( 'eccw_admin_nonce' ) );
		$settings_url = admin_url( 'admin.php?page=eccw-settings' );

		echo '<div class="notice notice-info is-dismissible eccw-onboarding-notice">';
		echo '<p>';
		echo '<strong>' . esc_html__( 'Color Changer for Elementor and WooCommerce', 'color-changer-for-elementor' ) . '</strong> — ';
		echo esc_html__( 'Finish the 60-second setup to style your store with your Elementor colors.', 'color-changer-for-elementor' );
		echo ' <a href="' . esc_url( $wizard_url ) . '" class="button button-primary" style="margin:0 8px;">' . esc_html__( 'Finish Setup', 'color-changer-for-elementor' ) . '</a>';
		echo ' <a href="' . esc_url( $settings_url ) . '" style="margin-right:8px;">' . esc_html__( 'Go to Settings', 'color-changer-for-elementor' ) . '</a>';
		echo ' <a href="#" class="eccw-skip-onboarding" data-dismiss="' . esc_url( $dismiss_url ) . '" style="color:#b32d2e;">' . esc_html__( 'Skip for now', 'color-changer-for-elementor' ) . '</a>';
		echo '</p>';
		echo '</div>';

		echo '<script>';
		echo '(function($){$(function(){';
		echo '$(".eccw-skip-onboarding").on("click",function(e){e.preventDefault();var $t=$(this),notice=$t.closest(".eccw-onboarding-notice");$.post($t.data("dismiss")).done(function(){notice.fadeOut();}).fail(function(){notice.fadeOut();});});';
		echo '});})(jQuery);';
		echo '</script>';
	}

	public function ajax_dismiss_onboarding() {
		if ( ! check_ajax_referer( 'eccw_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'color-changer-for-elementor' ) ) );
		}

		update_option( 'eccw_wizard_dismissed', true );

		wp_send_json_success( array( 'message' => __( 'Onboarding skipped.', 'color-changer-for-elementor' ) ) );
	}

	public function register_wizard_page() {
		add_submenu_page(
			null,
			__( 'Onboarding Wizard', 'color-changer-for-elementor' ),
			__( 'Onboarding Wizard', 'color-changer-for-elementor' ),
			'manage_options',
			'eccw-wizard',
			array( $this, 'render' )
		);
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( 'admin_page_eccw-wizard' !== $hook_suffix ) {
			// Read-only admin page flag; hook suffix is checked above.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['page'] ) || 'eccw-wizard' !== $_GET['page'] ) {
				return;
			}
		}

		wp_enqueue_style(
			'eccw-wizard',
			ECCw_URL . 'admin/css/eccw-wizard.css',
			array(),
			ECCw_VERSION
		);

		wp_enqueue_script(
			'eccw-wizard',
			ECCw_URL . 'admin/js/eccw-wizard.js',
			array( 'jquery' ),
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

		wp_localize_script(
			'eccw-wizard',
			'eccwData',
			array(
				'kitColors' => $colors_js,
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'eccw_admin_nonce' ),
				'siteUrl'   => home_url(),
			)
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$dequeue_settings = get_option( 'eccw_dequeue_settings', array() );
		$settings_url     = admin_url( 'admin.php?page=eccw-settings' );

		$has_kit_colors = CSS_Generator::has_kit_colors();
		$kit_id         = get_option( 'elementor_active_kit' );
		$colors_url     = $kit_id
			? admin_url( 'post.php?post=' . (int) $kit_id . '&action=elementor' )
			: admin_url( 'admin.php?page=elementor-app' );

		$shop_url = home_url( '/' );
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_page = wc_get_page_permalink( 'shop' );
			if ( $shop_page ) {
				$shop_url = $shop_page;
			}
		}
		$live_editor_url = add_query_arg( 'eccw_editor', '1', $shop_url );

		?>
		<div class="eccw-wizard-wrap">
			<div class="eccw-wizard-header">
				<h1><?php echo esc_html__( 'Welcome to Color Changer for Elementor and WooCommerce', 'color-changer-for-elementor' ); ?></h1>
				<p><?php echo esc_html__( 'Let\'s get your store styled with your Elementor brand colors in just a few steps.', 'color-changer-for-elementor' ); ?></p>
			</div>

			<?php if ( ! $has_kit_colors ) : ?>
				<div class="eccw-wizard-colors-notice">
					<p>
						<strong><?php echo esc_html__( 'One quick thing first:', 'color-changer-for-elementor' ); ?></strong>
						<?php echo esc_html__( 'This plugin styles your store using your Elementor global colors. You haven\'t set any yet, so we\'ll use temporary defaults for now.', 'color-changer-for-elementor' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( $colors_url ); ?>" class="button button-secondary" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html__( 'Set my brand colors in Elementor', 'color-changer-for-elementor' ); ?>
						</a>
						<span style="color:#50575e;font-size:12px;margin-left:8px;">
							<?php echo esc_html__( 'You can do this anytime — your store keeps working either way.', 'color-changer-for-elementor' ); ?>
						</span>
					</p>
				</div>
			<?php endif; ?>

			<div class="eccw-wizard-progress">
				<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
					<span class="eccw-wizard-dot" data-step="<?php echo esc_attr( $i ); ?>"></span>
				<?php endfor; ?>
			</div>

			<?php
			include ECCw_PATH . 'admin/templates/wizard-step1.php';
			include ECCw_PATH . 'admin/templates/wizard-step2.php';
			include ECCw_PATH . 'admin/templates/wizard-step3.php';
			include ECCw_PATH . 'admin/templates/wizard-step4.php';
			?>

			<div class="eccw-wizard-footer">
				<div>
					<button type="button" class="button eccw-wizard-prev" style="visibility:hidden;"><?php echo esc_html__( '← Back', 'color-changer-for-elementor' ); ?></button>
				</div>
				<div class="eccw-wizard-progress-text"><?php echo esc_html__( 'Step 1 of 4', 'color-changer-for-elementor' ); ?></div>
				<div class="eccw-wizard-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=eccw-settings' ) ); ?>" class="eccw-wizard-skip-link"><?php echo esc_html__( 'Skip for now', 'color-changer-for-elementor' ); ?></a>
					<button type="button" class="button button-primary eccw-wizard-next"><?php echo esc_html__( 'Next →', 'color-changer-for-elementor' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_ab_test() {
		if ( ! check_ajax_referer( 'eccw_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! function_exists( 'wc_get_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce not active.', 'color-changer-for-elementor' ) ) );
		}

		$products = wc_get_products(
			array(
				'limit'  => 1,
				'status' => 'publish',
				'return' => 'ids',
			)
		);

		if ( empty( $products ) ) {
			$args     = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			);
			$products = get_posts( $args );
		}

		if ( empty( $products ) ) {
			wp_send_json_error( array( 'message' => __( 'No products found.', 'color-changer-for-elementor' ) ) );
		}

		$product_id = (int) $products[0];

		$product_html = do_shortcode( '[product id="' . $product_id . '" columns="1"]' );

		if ( empty( $product_html ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				// Intentional global $post override to render the single-product
				// template for the live preview; restored immediately after.
				// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
				global $post;
				$original_post = $post;
				$post          = get_post( $product_id );
				setup_postdata( $post );
				ob_start();
				wc_get_template_part( 'content', 'single-product' );
				$product_html = ob_get_clean();
				wp_reset_postdata();
				$post = $original_post;
				// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		$wc_css_links = '';
		if ( function_exists( 'WC' ) ) {
			$wc_url = WC()->plugin_url();
			// Inline stylesheet links are part of the captured preview HTML;
			// they cannot be enqueued because this output is not rendered via wp_head.
			// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
			$wc_css_links = '<link rel="stylesheet" href="' . esc_url( $wc_url . '/assets/css/woocommerce.css' ) . '" type="text/css" />'
				. '<link rel="stylesheet" href="' . esc_url( $wc_url . '/assets/css/woocommerce-layout.css' ) . '" type="text/css" />';
			// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		}

		$before_html = '<div id="eccw-before">' . $wc_css_links . $product_html . '</div>';

		$saved      = get_option( 'eccw_colors_mappings', array() );
		$mappings   = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$page_type  = 'is_product';
		$kit_colors = CSS_Generator::get_kit_colors();

		$css = '';

		if ( ! empty( $mappings ) && ! empty( $kit_colors ) ) {
			$css = CSS_Generator::get_css_for_mappings( $mappings, $page_type );
		}

		if ( ! empty( $css ) ) {
			$css = '<style id="eccw-ab-css">' . $css . '</style>';
		}

		$after_html = '<div id="eccw-after">' . $wc_css_links . $css . $product_html . '</div>';

		wp_send_json_success(
			array(
				'before' => $before_html,
				'after'  => $after_html,
			)
		);
	}

	public function ajax_complete_onboarding() {
		if ( ! check_ajax_referer( 'eccw_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'color-changer-for-elementor' ) ) );
		}

		update_option( 'eccw_onboarding_completed', true );

		$dequeue_settings = array(
			'dequeue_blocks' => isset( $_POST['dequeue_blocks'] ) && 'yes' === $_POST['dequeue_blocks'] ? 'yes' : 'no',
		);
		update_option( 'eccw_dequeue_settings', $dequeue_settings );

		wp_send_json_success( array( 'message' => __( 'Onboarding completed.', 'color-changer-for-elementor' ) ) );
	}

	public function ajax_save_email() {
		if ( ! check_ajax_referer( 'eccw_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'color-changer-for-elementor' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'color-changer-for-elementor' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'color-changer-for-elementor' ) ) );
		}

		update_option( 'eccw_pro_optin_email', $email );

		wp_send_json_success( array( 'message' => __( 'Email saved.', 'color-changer-for-elementor' ) ) );
	}
}
