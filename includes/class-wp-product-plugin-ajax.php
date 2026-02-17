<?php
/**
 * AJAX handler.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler class.
 *
 * Handles AJAX requests for random product functionality.
 */
class WP_Product_Plugin_AJAX {

	/**
	 * API handler instance.
	 *
	 * @var WP_Product_Plugin_API
	 */
	private $api;

	/**
	 * CPT handler instance.
	 *
	 * @var WP_Product_Plugin_CPT
	 */
	private $cpt;

	/**
	 * Constructor.
	 *
	 * @param WP_Product_Plugin_API $api API handler instance.
	 * @param WP_Product_Plugin_CPT $cpt CPT handler instance.
	 */
	public function __construct( $api, $cpt ) {
		$this->api = $api;
		$this->cpt = $cpt;
	}

	/**
	 * Handle AJAX request for random product.
	 */
	public function handle_get_random_product() {
		// Verify nonce.
		check_ajax_referer( 'wp_product_plugin_nonce', 'nonce' );

		// Get random product from API.
		$product = $this->api->get_random_product();

		if ( is_wp_error( $product ) ) {
			wp_send_json_error(
				array(
					'message' => $product->get_error_message(),
				)
			);
		}

		// Create CPT post.
		$post_id = $this->cpt->create_product( $product );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => $post_id->get_error_message(),
				)
			);
		}

		// Load shortcodes class to use render method.
		require_once WP_PRODUCT_PLUGIN_PATH . 'public/class-wp-product-plugin-shortcodes.php';
		$shortcodes = new WP_Product_Plugin_Shortcodes( $this->api );

		// Render product card with post link.
		$html = $shortcodes->render_product_card( $product, true, $post_id );

		// Send success response.
		wp_send_json_success(
			array(
				'html'    => $html,
				'post_id' => $post_id,
				'product_id' => $product['id'],
			)
		);
	}
}
