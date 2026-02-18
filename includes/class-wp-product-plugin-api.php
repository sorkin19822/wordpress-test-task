<?php
/**
 * FakeStore API handler.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FakeStore API handler class.
 *
 * Handles all interactions with the FakeStore API.
 */
class WP_Product_Plugin_API {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	const API_BASE_URL = 'https://fakestoreapi.com';

	/**
	 * Cache expiration time in seconds (1 hour).
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = 3600;

	/**
	 * Get a product by ID.
	 *
	 * @param int $product_id Product ID (1-20).
	 * @return array|WP_Error Product data on success, WP_Error on failure.
	 */
	public function get_product( $product_id ) {
		$product_id = absint( $product_id );

		// Validate product ID range.
		if ( $product_id < 1 || $product_id > 20 ) {
			return new WP_Error(
				'invalid_product_id',
				__( 'Product ID must be between 1 and 20.', 'wp-product-plugin' )
			);
		}

		// Check cache.
		$cache_key = 'wp_product_plugin_product_' . $product_id;
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Make API request.
		$url = self::API_BASE_URL . '/products/' . $product_id;
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'API request failed: %s', 'wp-product-plugin' ),
					$response->get_error_message()
				)
			);
		}

		// Check response code.
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error(
				'api_response_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'API returned error code: %d', 'wp-product-plugin' ),
					$response_code
				)
			);
		}

		// Parse response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_decode_error',
				__( 'Failed to decode API response.', 'wp-product-plugin' )
			);
		}

		// Validate data structure.
		if ( ! isset( $data['id'] ) || ! isset( $data['title'] ) ) {
			return new WP_Error(
				'invalid_api_data',
				__( 'Invalid product data received from API.', 'wp-product-plugin' )
			);
		}

		// Cache the result.
		set_transient( $cache_key, $data, self::CACHE_EXPIRATION );

		return $data;
	}

	/**
	 * Get a random product.
	 *
	 * @return array|WP_Error Product data on success, WP_Error on failure.
	 */
	public function get_random_product() {
		// Generate random ID between 1 and 20.
		$random_id = wp_rand( 1, 20 );

		// Get product by random ID.
		return $this->get_product( $random_id );
	}

	/**
	 * Clear product cache.
	 *
	 * @param int|null $product_id Specific product ID to clear, or null for all.
	 */
	public function clear_cache( $product_id = null ) {
		if ( null !== $product_id ) {
			delete_transient( 'wp_product_plugin_product_' . absint( $product_id ) );
		} else {
			// Clear all product caches.
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$wpdb->esc_like( '_transient_wp_product_plugin_product_' ) . '%',
					$wpdb->esc_like( '_transient_timeout_wp_product_plugin_product_' ) . '%'
				)
			);
		}
	}
}
