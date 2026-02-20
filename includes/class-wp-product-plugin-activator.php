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
 * Handles all code necessary to run during the plugin's activation.
 */
class WP_Product_Plugin_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Registers the CPT so flush_rewrite_rules() works correctly, then
	 * sets default option values (using add_option so existing values are preserved).
	 */
	public static function activate(): void {
		// Register CPT now so its rewrite rules are available for flushing.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-cpt.php';
		$cpt = new WP_Product_Plugin_CPT();
		$cpt->register_post_type();

		flush_rewrite_rules();

		// add_option is a no-op when the option already exists, which is correct:
		// we must not overwrite user-saved settings on reactivation.
		add_option(
			'wp_product_plugin_settings',
			array(
				'product_id'             => 1,
				'last_created_at'        => '',
				'enable_enhanced_styles' => 1,
			)
		);
	}
}
