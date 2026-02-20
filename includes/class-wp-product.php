<?php
/**
 * Product model (DTO).
 *
 * A typed value object that represents a single FakeStore API product.
 * All classes receive this model instead of raw associative arrays,
 * providing a single authoritative definition of what a "product" looks like.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Data Transfer Object.
 *
 * Encapsulates product data from the FakeStore API.
 * Use the static factory method `from_api_array()` to construct an instance.
 */
class WP_Product {

	/**
	 * FakeStore API product ID.
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Product title.
	 *
	 * @var string
	 */
	public string $title;

	/**
	 * Product description.
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * Product price.
	 *
	 * @var float
	 */
	public float $price;

	/**
	 * Product category.
	 *
	 * @var string
	 */
	public string $category;

	/**
	 * Product image URL.
	 *
	 * @var string
	 */
	public string $image;

	/**
	 * Average rating score (0–5).
	 *
	 * @var float
	 */
	public float $rating_rate;

	/**
	 * Number of ratings.
	 *
	 * @var int
	 */
	public int $rating_count;

	/**
	 * Private constructor — use the factory method.
	 */
	private function __construct() {}

	/**
	 * Create a WP_Product instance from a raw FakeStore API response array.
	 *
	 * This is the single place where all array-key access happens.
	 * If the API schema changes, only this method needs updating.
	 *
	 * @param array $data Decoded JSON array from the FakeStore API.
	 * @return self
	 */
	public static function from_api_array( array $data ): self {
		$product = new self();

		$product->id          = absint( $data['id'] ?? 0 );
		$product->title       = sanitize_text_field( $data['title'] ?? '' );
		$product->description = wp_kses_post( $data['description'] ?? '' );
		$product->price       = floatval( $data['price'] ?? 0 );
		$product->category    = sanitize_text_field( $data['category'] ?? '' );
		$product->image       = esc_url_raw( $data['image'] ?? '' );
		$product->rating_rate = floatval( $data['rating']['rate'] ?? 0 );
		$product->rating_count = absint( $data['rating']['count'] ?? 0 );

		return $product;
	}
}
