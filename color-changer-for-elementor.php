<?php
/**
 * Plugin Name: Color Changer for Elementor and WooCommerce
 * Plugin URI:  https://github.com/AkarshBandi/color-changer-for-elementor
 * Description: Replaces WooCommerce default CSS with dynamic CSS that maps Elementor global colors to WooCommerce elements.
 * Version:     1.0.0
 * Author:      Akarsh Bandi
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

define( 'ECCw_VERSION', '1.0.0' );
define( 'ECCw_PATH', plugin_dir_path( __FILE__ ) );
define( 'ECCw_URL', plugin_dir_url( __FILE__ ) );

require ECCw_PATH . 'includes/class-autoloader.php';

register_activation_hook( __FILE__, array( 'ElementorColorChanger\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ElementorColorChanger\\Plugin', 'deactivate' ) );

ElementorColorChanger\Plugin::init();
