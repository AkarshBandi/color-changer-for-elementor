<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Onboarding_Wizard {

	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_wizard' ) );
		add_action( 'admin_menu', array( $this, 'register_wizard_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		add_action( 'wp_ajax_wooce_wizard_ab_test', array( $this, 'ajax_ab_test' ) );
		add_action( 'wp_ajax_wooce_complete_onboarding', array( $this, 'ajax_complete_onboarding' ) );
		add_action( 'wp_ajax_wooce_save_email', array( $this, 'ajax_save_email' ) );
		add_action( 'wp_ajax_wooce_dismiss_onboarding', array( $this, 'ajax_dismiss_onboarding' ) );
	}

	public function maybe_redirect_to_wizard() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( get_option( 'wooce_onboarding_completed' ) ) {
			return;
		}

		if ( get_option( 'wooce_wizard_dismissed' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'wooce-wizard' === $_GET['page'] ) {
			return;
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			return;
		}

		$redirect_once = get_transient( 'wooce_wizard_redirect' );

		if ( empty( $redirect_once ) ) {
			return;
		}

		delete_transient( 'wooce_wizard_redirect' );

		wp_safe_redirect( admin_url( 'admin.php?page=wooce-wizard' ) );
		exit;
	}

	public function admin_notice() {
		if ( get_option( 'wooce_onboarding_completed' ) ) {
			return;
		}

		if ( get_option( 'wooce_wizard_dismissed' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'admin_page_wooce-wizard' === $screen->id ) {
			return;
		}

		$wizard_url    = admin_url( 'admin.php?page=wooce-wizard' );
		$dismiss_url   = admin_url( 'admin-ajax.php?action=wooce_dismiss_onboarding&nonce=' . wp_create_nonce( 'wooce_admin_nonce' ) );
		$settings_url  = admin_url( 'admin.php?page=wooce-settings' );

		echo '<div class="notice notice-info is-dismissible wooce-onboarding-notice">';
		echo '<p>';
		echo '<strong>' . esc_html__( 'WooCommerce Elementor Colors', 'woocommerce-elementor-colors' ) . '</strong> — ';
		echo esc_html__( 'Finish the 60-second setup to style your store with your Elementor colors.', 'woocommerce-elementor-colors' );
		echo ' <a href="' . esc_url( $wizard_url ) . '" class="button button-primary" style="margin:0 8px;">' . esc_html__( 'Finish Setup', 'woocommerce-elementor-colors' ) . '</a>';
		echo ' <a href="' . esc_url( $settings_url ) . '" style="margin-right:8px;">' . esc_html__( 'Go to Settings', 'woocommerce-elementor-colors' ) . '</a>';
		echo ' <a href="#" class="wooce-skip-onboarding" data-dismiss="' . esc_url( $dismiss_url ) . '" style="color:#b32d2e;">' . esc_html__( 'Skip for now', 'woocommerce-elementor-colors' ) . '</a>';
		echo '</p>';
		echo '</div>';

		echo '<script>';
		echo '(function($){$(function(){';
		echo '$(".wooce-skip-onboarding").on("click",function(e){e.preventDefault();var $t=$(this),notice=$t.closest(".wooce-onboarding-notice");$.post($t.data("dismiss")).done(function(){notice.fadeOut();}).fail(function(){notice.fadeOut();});});';
		echo '});})(jQuery);';
		echo '</script>';
	}

	public function ajax_dismiss_onboarding() {
		if ( ! check_ajax_referer( 'wooce_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) ) );
		}

		update_option( 'wooce_wizard_dismissed', true );

		wp_send_json_success( array( 'message' => __( 'Onboarding skipped.', 'woocommerce-elementor-colors' ) ) );
	}

	public function register_wizard_page() {
		add_submenu_page(
			null,
			__( 'Onboarding Wizard', 'woocommerce-elementor-colors' ),
			__( 'Onboarding Wizard', 'woocommerce-elementor-colors' ),
			'manage_options',
			'wooce-wizard',
			array( $this, 'render' )
		);
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( 'admin_page_wooce-wizard' !== $hook_suffix ) {
			global $admin_page_hooks;
			if ( ! isset( $_GET['page'] ) || 'wooce-wizard' !== $_GET['page'] ) {
				return;
			}
		}

		wp_enqueue_style(
			'wooce-wizard',
			WOOEC_URL . 'admin/css/wooce-wizard.css',
			array(),
			WOOEC_VERSION
		);

		wp_enqueue_script(
			'wooce-wizard',
			WOOEC_URL . 'admin/js/wooce-wizard.js',
			array( 'jquery' ),
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

		wp_localize_script(
			'wooce-wizard',
			'wooceData',
			array(
				'kitColors' => $colors_js,
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wooce_admin_nonce' ),
				'siteUrl'   => home_url(),
			)
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$dequeue_settings = get_option( 'wooce_dequeue_settings', array() );
		$settings_url     = admin_url( 'admin.php?page=wooce-settings' );

		$shop_url = home_url( '/' );
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_page = wc_get_page_permalink( 'shop' );
			if ( $shop_page ) {
				$shop_url = $shop_page;
			}
		}
		$live_editor_url = add_query_arg( 'wooce_editor', '1', $shop_url );

		?>
		<div class="wooce-wizard-wrap">
			<div class="wooce-wizard-header">
				<h1><?php echo esc_html__( 'Welcome to WooCommerce Elementor Colors', 'woocommerce-elementor-colors' ); ?></h1>
				<p><?php echo esc_html__( 'Let\'s get your store styled with your Elementor brand colors in just a few steps.', 'woocommerce-elementor-colors' ); ?></p>
			</div>

			<div class="wooce-wizard-progress">
				<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
					<span class="wooce-wizard-dot" data-step="<?php echo esc_attr( $i ); ?>"></span>
				<?php endfor; ?>
			</div>

			<?php
			include WOOEC_PATH . 'admin/templates/wizard-step1.php';
			include WOOEC_PATH . 'admin/templates/wizard-step2.php';
			include WOOEC_PATH . 'admin/templates/wizard-step3.php';
			include WOOEC_PATH . 'admin/templates/wizard-step4.php';
			?>

			<div class="wooce-wizard-footer">
				<div>
					<button type="button" class="button wooce-wizard-prev" style="visibility:hidden;"><?php echo esc_html__( '← Back', 'woocommerce-elementor-colors' ); ?></button>
				</div>
				<div class="wooce-wizard-progress-text"><?php echo esc_html__( 'Step 1 of 4', 'woocommerce-elementor-colors' ); ?></div>
				<div class="wooce-wizard-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wooce-settings' ) ); ?>" class="wooce-wizard-skip-link"><?php echo esc_html__( 'Skip for now', 'woocommerce-elementor-colors' ); ?></a>
					<button type="button" class="button button-primary wooce-wizard-next"><?php echo esc_html__( 'Next →', 'woocommerce-elementor-colors' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_ab_test() {
		if ( ! check_ajax_referer( 'wooce_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! function_exists( 'wc_get_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce not active.', 'woocommerce-elementor-colors' ) ) );
		}

		$products = wc_get_products( array(
			'limit'  => 1,
			'status' => 'publish',
			'return' => 'ids',
		) );

		if ( empty( $products ) ) {
			$args      = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			);
			$products = get_posts( $args );
		}

		if ( empty( $products ) ) {
			wp_send_json_error( array( 'message' => __( 'No products found.', 'woocommerce-elementor-colors' ) ) );
		}

		$product_id = (int) $products[0];

		$product_html = do_shortcode( '[product id="' . $product_id . '" columns="1"]' );

		if ( empty( $product_html ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				global $post;
				$original_post = $post;
				$post = get_post( $product_id );
				setup_postdata( $post );
				ob_start();
				wc_get_template_part( 'content', 'single-product' );
				$product_html = ob_get_clean();
				wp_reset_postdata();
				$post = $original_post;
			}
		}

		$wc_css_links = '';
		if ( function_exists( 'WC' ) ) {
			$wc_url       = WC()->plugin_url();
			$wc_css_links = '<link rel="stylesheet" href="' . esc_url( $wc_url . '/assets/css/woocommerce.css' ) . '" type="text/css" />'
				. '<link rel="stylesheet" href="' . esc_url( $wc_url . '/assets/css/woocommerce-layout.css' ) . '" type="text/css" />';
		}

		$before_html = '<div id="wooce-before">' . $wc_css_links . $product_html . '</div>';

		$saved       = get_option( 'wooce_colors_mappings', array() );
		$mappings    = isset( $saved['widgets'] ) ? $saved['widgets'] : array();
		$page_type   = 'is_product';
		$kit_colors  = CSS_Generator::get_kit_colors();

		$css = '';

		if ( ! empty( $mappings ) && ! empty( $kit_colors ) ) {
			$css = CSS_Generator::get_css_for_mappings( $mappings, $page_type );
		}

		if ( ! empty( $css ) ) {
			$css = '<style id="wooce-ab-css">' . $css . '</style>';
		}

		$after_html = '<div id="wooce-after">' . $wc_css_links . $css . $product_html . '</div>';

		wp_send_json_success( array(
			'before' => $before_html,
			'after'  => $after_html,
		) );
	}

	public function ajax_complete_onboarding() {
		if ( ! check_ajax_referer( 'wooce_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) ) );
		}

		update_option( 'wooce_onboarding_completed', true );

		$dequeue_settings = array(
			'dequeue_core'   => isset( $_POST['dequeue_core'] ) && 'yes' === $_POST['dequeue_core'] ? 'yes' : 'no',
			'dequeue_blocks' => isset( $_POST['dequeue_blocks'] ) && 'yes' === $_POST['dequeue_blocks'] ? 'yes' : 'no',
		);
		update_option( 'wooce_dequeue_settings', $dequeue_settings );

		wp_send_json_success( array( 'message' => __( 'Onboarding completed.', 'woocommerce-elementor-colors' ) ) );
	}

	public function ajax_save_email() {
		if ( ! check_ajax_referer( 'wooce_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'woocommerce-elementor-colors' ) ) );
		}

		if ( ! isset( $_POST['email'] ) || ! is_email( $_POST['email'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'woocommerce-elementor-colors' ) ) );
		}

		update_option( 'wooce_pro_optin_email', sanitize_email( $_POST['email'] ) );

		wp_send_json_success( array( 'message' => __( 'Email saved.', 'woocommerce-elementor-colors' ) ) );
	}
}
