<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WP_Product_Plugin
 */

// If uninstall was not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Post type slug — defined inline here because we cannot safely bootstrap
 * the full plugin in the uninstall context (plugin is being deleted).
 * This string must stay in sync with WP_PRODUCT_PLUGIN_POST_TYPE in
 * wp-product-plugin.php and WP_Product_Plugin_CPT::POST_TYPE.
 */
const WPP_UNINSTALL_POST_TYPE = 'wpp_product';

/**
 * Delete plugin options.
 */
delete_option( 'wp_product_plugin_settings' );

/**
 * Delete all CPT posts in batches to avoid loading every post into memory
 * at once, which could cause PHP memory exhaustion on large sites.
 */
$batch_size = 100;

do {
	$ids = get_posts(
		array(
			'post_type'      => WPP_UNINSTALL_POST_TYPE,
			'posts_per_page' => $batch_size,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $ids as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}
} while ( count( $ids ) === $batch_size );

/**
 * Flush rewrite rules so the CPT slug is removed from permalink structures.
 */
flush_rewrite_rules();
