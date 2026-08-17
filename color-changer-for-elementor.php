<?php
/**
 * Plugin Name: Color and Font Sync for Elementor and WooCommerce
 * Plugin URI:  https://github.com/AkarshBandi/color-changer-for-elementor
 * Description: Your Elementor colors and fonts, applied automatically to every WooCommerce button, price, badge and form. No page-by-page setup.
 * Version:     2.1.6
 * Author:      admin
 * Author URI:  https://github.com/AkarshBandi
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: color-changer-for-elementor
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.0
 * Requires Plugins: woocommerce, elementor
 * WC requires at least: 7.0
 * WC tested up to: 10.9
 */

defined( 'ABSPATH' ) || exit;

define( 'ECCW_VERSION', '2.1.6' );
define( 'ECCW_PATH', plugin_dir_path( __FILE__ ) );
define( 'ECCW_URL', plugin_dir_url( __FILE__ ) );

// A partial copy (missing the autoloader) must fail quietly, not fatal.
if ( ! file_exists( ECCW_PATH . 'includes/class-autoloader.php' ) ) {
	return;
}

require ECCW_PATH . 'includes/class-autoloader.php';

register_activation_hook( __FILE__, array( 'ElementorColorChanger\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ElementorColorChanger\\Plugin', 'deactivate' ) );

ElementorColorChanger\Plugin::init();
