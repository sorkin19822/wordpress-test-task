<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WP_Product_Plugin
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options.
 */
delete_option( 'wp_product_plugin_settings' );

/**
 * Delete all products CPT posts.
 */
$products = get_posts(
	array(
		'post_type'      => 'wpp_product',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	)
);

foreach ( $products as $product ) {
	wp_delete_post( $product->ID, true );
}

/**
 * Flush rewrite rules.
 */
flush_rewrite_rules();
