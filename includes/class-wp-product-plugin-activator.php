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

		self::insert_cache_rules();
	}

	/**
	 * Insert browser-cache rules into .htaccess using WordPress markers.
	 *
	 * Uses insert_with_markers() so the block is clearly delimited and
	 * never conflicts with the WordPress rewrite block. Safe to call on
	 * every activation — existing rules are simply replaced.
	 */
	public static function insert_cache_rules(): void {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$htaccess = get_home_path() . '.htaccess';

		$rules = array(
			'<IfModule mod_expires.c>',
			'    ExpiresActive On',
			'    ExpiresByType image/jpeg            "access plus 1 year"',
			'    ExpiresByType image/png             "access plus 1 year"',
			'    ExpiresByType image/webp            "access plus 1 year"',
			'    ExpiresByType image/gif             "access plus 1 year"',
			'    ExpiresByType image/svg+xml         "access plus 1 year"',
			'    ExpiresByType image/x-icon          "access plus 1 year"',
			'    ExpiresByType font/woff2            "access plus 1 year"',
			'    ExpiresByType font/woff             "access plus 1 year"',
			'    ExpiresByType font/ttf              "access plus 1 year"',
			'    ExpiresByType application/font-woff2 "access plus 1 year"',
			'    ExpiresByType text/css              "access plus 1 year"',
			'    ExpiresByType application/javascript "access plus 1 year"',
			'    ExpiresByType text/javascript       "access plus 1 year"',
			'    ExpiresByType text/html             "access plus 0 seconds"',
			'</IfModule>',
			'<IfModule mod_headers.c>',
			'    <FilesMatch "\.(jpe?g|png|webp|gif|svg|ico)$">',
			'        Header set Cache-Control "public, max-age=31536000, immutable"',
			'    </FilesMatch>',
			'    <FilesMatch "\.(woff2?|ttf|otf|eot)$">',
			'        Header set Cache-Control "public, max-age=31536000, immutable"',
			'    </FilesMatch>',
			'    <FilesMatch "\.(css|js)$">',
			'        Header set Cache-Control "public, max-age=31536000, immutable"',
			'    </FilesMatch>',
			'</IfModule>',
		);

		insert_with_markers( $htaccess, 'WPP Cache-Control', $rules );
	}
}
