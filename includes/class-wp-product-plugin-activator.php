<?php
/**
 * Fired during plugin activation.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class WP_Product_Plugin_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Register Custom Post Type and flush rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Register CPT (needed for flush_rewrite_rules to work properly).
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-cpt.php';
		$cpt = new WP_Product_Plugin_CPT();
		$cpt->register_post_type();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set default options.
		$default_settings = array(
			'product_id'     => 1,
			'last_created_at' => '',
		);
		add_option( 'wp_product_plugin_settings', $default_settings );
	}
}
