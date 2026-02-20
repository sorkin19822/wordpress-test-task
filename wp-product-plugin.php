<?php
/**
 * Plugin Name:       WP Product Plugin
 * Description:       Displays products from the FakeStore API via shortcodes with AJAX support and Custom Post Type storage.
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-product-plugin
 * Domain Path:       /languages
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'WP_PRODUCT_PLUGIN_VERSION', '1.2.0' );

/**
 * Absolute filesystem path to the plugin directory (with trailing slash).
 */
define( 'WP_PRODUCT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory (with trailing slash).
 */
define( 'WP_PRODUCT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Custom Post Type slug.
 *
 * Defined here (not inside WP_Product_Plugin_CPT) so that uninstall.php
 * can reference a single source of truth without bootstrapping the full plugin.
 */
define( 'WP_PRODUCT_PLUGIN_POST_TYPE', 'wpp_product' );

/**
 * The code that runs during plugin activation.
 */
function activate_wp_product_plugin(): void {
	require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-activator.php';
	WP_Product_Plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wp_product_plugin(): void {
	require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-deactivator.php';
	WP_Product_Plugin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_product_plugin' );
register_deactivation_hook( __FILE__, 'deactivate_wp_product_plugin' );

/**
 * Begins execution of the plugin.
 *
 * We intentionally defer full initialisation to `plugins_loaded` so that
 * all other plugins have been set up before ours hooks into WordPress.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin.php';
		WP_Product_Plugin::get_instance()->run();
	}
);
