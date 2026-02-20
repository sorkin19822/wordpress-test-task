<?php
/**
 * Shortcodes handler.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes handler class.
 *
 * Responsibility: register shortcodes and delegate rendering to WP_Product_Renderer.
 * Does not serve as a rendering utility for other classes.
 */
class WP_Product_Plugin_Shortcodes {

	/**
	 * API handler instance.
	 *
	 * @var WP_Product_Plugin_API
	 */
	private WP_Product_Plugin_API $api;

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
	 * @param WP_Product_Renderer   $renderer Card renderer.
	 */
	public function __construct( WP_Product_Plugin_API $api, WP_Product_Renderer $renderer ) {
		$this->api      = $api;
		$this->renderer = $renderer;
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'product_display', array( $this, 'product_display_shortcode' ) );
		add_shortcode( 'random_product', array( $this, 'random_product_shortcode' ) );
	}

	/**
	 * Shortcode #1: Display product from settings.
	 *
	 * Usage: [product_display] or [product_display id="5"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function product_display_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array( 'id' => null ),
			$atts,
			'product_display'
		);

		// Prefer shortcode attribute; fall back to the admin setting.
		if ( null !== $atts['id'] ) {
			$product_id = absint( $atts['id'] );
		} else {
			$settings   = get_option( 'wp_product_plugin_settings', array() );
			$product_id = absint( $settings['product_id'] ?? 1 );
		}

		$product = $this->api->get_product( $product_id );

		if ( is_wp_error( $product ) ) {
			return $this->renderer->render_error( $product->get_error_message() );
		}

		return $this->renderer->render_card( $product );
	}

	/**
	 * Shortcode #2: Random product button with AJAX loading.
	 *
	 * Usage: [random_product]
	 * The actual product fetch happens via AJAX after the user clicks the button.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string HTML output.
	 */
	public function random_product_shortcode( array $atts ): string {
		ob_start();
		?>
		<div class="wp-product-plugin-random-container">
			<button class="wp-product-plugin-random-button" type="button">
				<?php esc_html_e( 'Load Random Product', 'wp-product-plugin' ); ?>
			</button>
			<div class="wp-product-plugin-random-result"></div>
			<div class="wp-product-plugin-loading" style="display: none;">
				<?php esc_html_e( 'Loading...', 'wp-product-plugin' ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
