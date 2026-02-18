<?php
/**
 * Plugin Name: WP Product Plugin
 * Plugin URI: https://github.com/sorkin19822/wordpress-test-task
 * Description: Integration with FakeStore API to display products via shortcodes with AJAX support and Custom Post Type storage.
 * Version: 1.1.0
 * Author: Oleksandr
 * Author URI: https://github.com/sorkin19822
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-product-plugin
 * Domain Path: /languages
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WP_PRODUCT_PLUGIN_VERSION', '1.1.0' );

/**
 * Plugin directory path.
 */
define( 'WP_PRODUCT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'WP_PRODUCT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_wp_product_plugin() {
	require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-activator.php';
	WP_Product_Plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wp_product_plugin() {
	require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-deactivator.php';
	WP_Product_Plugin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_product_plugin' );
register_deactivation_hook( __FILE__, 'deactivate_wp_product_plugin' );

/**
 * The core plugin class.
 */
require WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin.php';

/**
 * Begins execution of the plugin.
 */
function run_wp_product_plugin() {
	$plugin = WP_Product_Plugin::get_instance();
	$plugin->run();
}
run_wp_product_plugin();
