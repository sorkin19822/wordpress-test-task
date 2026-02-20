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
 * Handles all interactions with the FakeStore API and returns
 * typed WP_Product models instead of raw arrays.
 */
class WP_Product_Plugin_API {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	const API_BASE_URL = 'https://fakestoreapi.com';

	/**
	 * Minimum valid product ID.
	 *
	 * @var int
	 */
	const MIN_PRODUCT_ID = 1;

	/**
	 * Maximum valid product ID.
	 *
	 * @var int
	 */
	const MAX_PRODUCT_ID = 20;

	/**
	 * Cache expiration time in seconds (1 hour).
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = 3600;

	/**
	 * Get a product by ID.
	 *
	 * Returns a typed WP_Product model on success so callers never
	 * need to know the raw API array structure.
	 *
	 * @param int $product_id Product ID (1–20).
	 * @return WP_Product|WP_Error Product model on success, WP_Error on failure.
	 */
	public function get_product( int $product_id ): WP_Product|WP_Error {
		$product_id = absint( $product_id );

		// Validate product ID range using named constants.
		if ( $product_id < self::MIN_PRODUCT_ID || $product_id > self::MAX_PRODUCT_ID ) {
			return new WP_Error(
				'invalid_product_id',
				sprintf(
					/* translators: 1: minimum ID, 2: maximum ID */
					__( 'Product ID must be between %1$d and %2$d.', 'wp-product-plugin' ),
					self::MIN_PRODUCT_ID,
					self::MAX_PRODUCT_ID
				)
			);
		}

		// Check transient cache.
		$cache_key   = 'wp_product_plugin_product_' . $product_id;
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			return WP_Product::from_api_array( $cached_data );
		}

		// Make API request.
		$url      = self::API_BASE_URL . '/products/' . $product_id;
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for transport errors.
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

		// Check HTTP status.
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

		// Decode the response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error(
				'json_decode_error',
				__( 'Failed to decode API response.', 'wp-product-plugin' )
			);
		}

		// Validate minimum required fields.
		if ( empty( $data['id'] ) || empty( $data['title'] ) ) {
			return new WP_Error(
				'invalid_api_data',
				__( 'Invalid product data received from API.', 'wp-product-plugin' )
			);
		}

		// Cache the raw data array (arrays are lighter to serialize than objects).
		set_transient( $cache_key, $data, self::CACHE_EXPIRATION );

		return WP_Product::from_api_array( $data );
	}

	/**
	 * Get a random product.
	 *
	 * @return WP_Product|WP_Error Product model on success, WP_Error on failure.
	 */
	public function get_random_product(): WP_Product|WP_Error {
		$random_id = wp_rand( self::MIN_PRODUCT_ID, self::MAX_PRODUCT_ID );
		return $this->get_product( $random_id );
	}

	/**
	 * Clear product cache.
	 *
	 * Uses the transient API (cache-layer-agnostic) instead of raw SQL,
	 * so object-cache plugins are properly notified.
	 *
	 * @param int|null $product_id Specific product ID to clear, or null for all.
	 */
	public function clear_cache( ?int $product_id = null ): void {
		if ( null !== $product_id ) {
			delete_transient( 'wp_product_plugin_product_' . absint( $product_id ) );
			return;
		}

		// Clear all product caches by iterating the known ID range.
		for ( $i = self::MIN_PRODUCT_ID; $i <= self::MAX_PRODUCT_ID; $i++ ) {
			delete_transient( 'wp_product_plugin_product_' . $i );
		}
	}
}
