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
 * Responsibility: validate the incoming request, delegate to services,
 * and return a JSON response. Does not own business logic or rendering.
 */
class WP_Product_Plugin_AJAX {

	/**
	 * Maximum requests per IP per minute.
	 *
	 * @var int
	 */
	const RATE_LIMIT = 20;

	/**
	 * Rate-limit window in seconds.
	 *
	 * @var int
	 */
	const RATE_WINDOW = 60;

	/**
	 * API handler instance.
	 *
	 * @var WP_Product_Plugin_API
	 */
	private WP_Product_Plugin_API $api;

	/**
	 * CPT handler instance.
	 *
	 * @var WP_Product_Plugin_CPT
	 */
	private WP_Product_Plugin_CPT $cpt;

	/**
	 * Card renderer instance.
	 *
	 * @var WP_Product_Renderer
	 */
	private WP_Product_Renderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param WP_Product_Plugin_API $api      API handler.
	 * @param WP_Product_Plugin_CPT $cpt      CPT handler.
	 * @param WP_Product_Renderer   $renderer Card renderer.
	 */
	public function __construct(
		WP_Product_Plugin_API $api,
		WP_Product_Plugin_CPT $cpt,
		WP_Product_Renderer $renderer
	) {
		$this->api      = $api;
		$this->cpt      = $cpt;
		$this->renderer = $renderer;
	}

	/**
	 * Handle AJAX request for a random product.
	 *
	 * Registered for both authenticated users (wp_ajax_) and guests (wp_ajax_nopriv_).
	 */
	public function handle_get_random_product(): void {
		// Verify nonce to prevent CSRF.
		check_ajax_referer( 'wp_product_plugin_nonce', 'nonce' );

		// Enforce per-IP rate limit to prevent DoS via repeated unauthenticated requests.
		if ( ! $this->check_rate_limit() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Too many requests. Please try again later.', 'wp-product-plugin' ) ) );
			wp_die();
		}

		// Fetch a random product from the API.
		$product = $this->api->get_random_product();

		if ( is_wp_error( $product ) ) {
			// esc_html() on the error message before JSON-encoding prevents XSS
			// if network-layer error text contains attacker-influenced content.
			wp_send_json_error( array( 'message' => esc_html( $product->get_error_message() ) ) );
			wp_die();
		}

		// Persist as a CPT post (skips creation if already exists).
		$post_id = $this->cpt->create_product( $product );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => esc_html( $post_id->get_error_message() ) ) );
			wp_die();
		}

		// Render the product card HTML via the dedicated renderer.
		$html = $this->renderer->render_card( $product, true, $post_id );

		wp_send_json_success(
			array(
				'html'       => $html,
				'post_id'    => $post_id,
				'product_id' => $product->id,
			)
		);
	}

	/**
	 * Check whether the current request is within the rate limit.
	 *
	 * Uses a transient keyed on a hashed IP address. Returns true if the
	 * request should proceed, false if the limit has been exceeded.
	 *
	 * @return bool
	 */
	private function check_rate_limit(): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- REMOTE_ADDR is not user-controllable via HTTP body.
		$raw_ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$rate_key = 'wpp_rate_' . md5( $raw_ip );
		$count    = (int) get_transient( $rate_key );

		if ( $count >= self::RATE_LIMIT ) {
			return false;
		}

		// Increment counter; create the transient if it does not exist yet.
		set_transient( $rate_key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
