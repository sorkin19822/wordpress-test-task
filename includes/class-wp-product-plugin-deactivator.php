<?php
/**
 * Fired during plugin deactivation.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class WP_Product_Plugin_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Flush rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
		self::remove_cache_rules();
	}

	/**
	 * Remove the cache rules block written by the activator.
	 */
	public static function remove_cache_rules(): void {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		insert_with_markers( get_home_path() . '.htaccess', 'WPP Cache-Control', array() );
	}
}
