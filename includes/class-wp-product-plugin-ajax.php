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
	 * Shortcodes handler instance.
	 *
	 * @var WP_Product_Plugin_Shortcodes
	 */
	private $shortcodes;

	/**
	 * Constructor.
	 *
	 * @param WP_Product_Plugin_API        $api        API handler instance.
	 * @param WP_Product_Plugin_CPT        $cpt        CPT handler instance.
	 * @param WP_Product_Plugin_Shortcodes $shortcodes Shortcodes handler instance.
	 */
	public function __construct( $api, $cpt, $shortcodes ) {
		$this->api        = $api;
		$this->cpt        = $cpt;
		$this->shortcodes = $shortcodes;
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

		// Render product card with post link.
		$html = $this->shortcodes->render_product_card( $product, true, $post_id );

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
