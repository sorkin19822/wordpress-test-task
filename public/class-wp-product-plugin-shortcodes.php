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
 * Registers and handles plugin shortcodes.
 */
class WP_Product_Plugin_Shortcodes {

	/**
	 * API handler instance.
	 *
	 * @var WP_Product_Plugin_API
	 */
	private $api;

	/**
	 * Constructor.
	 *
	 * @param WP_Product_Plugin_API $api API handler instance.
	 */
	public function __construct( $api ) {
		$this->api = $api;
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'product_display', array( $this, 'product_display_shortcode' ) );
		add_shortcode( 'random_product', array( $this, 'random_product_shortcode' ) );
	}

	/**
	 * Shortcode #1: Display product from settings.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function product_display_shortcode( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'id' => null,
			),
			$atts,
			'product_display'
		);

		// Get product ID from attribute or settings.
		if ( null !== $atts['id'] ) {
			$product_id = absint( $atts['id'] );
		} else {
			$settings = get_option( 'wp_product_plugin_settings', array() );
			$product_id = isset( $settings['product_id'] ) ? $settings['product_id'] : 1;
		}

		// Get product from API.
		$product = $this->api->get_product( $product_id );

		if ( is_wp_error( $product ) ) {
			return $this->render_error( $product->get_error_message() );
		}

		return $this->render_product_card( $product, false );
	}

	/**
	 * Shortcode #2: Random product with AJAX.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function random_product_shortcode( $atts ) {
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

	/**
	 * Render product card.
	 *
	 * @param array $product Product data.
	 * @param bool  $show_post_link Whether to show link to CPT post.
	 * @param int   $post_id Optional CPT post ID.
	 * @return string HTML output.
	 */
	public function render_product_card( $product, $show_post_link = false, $post_id = 0 ) {
		ob_start();
		?>
		<div class="wp-product-plugin-card">
			<?php if ( ! empty( $product['image'] ) ) : ?>
				<div class="wp-product-plugin-card-image">
					<img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>" />
				</div>
			<?php endif; ?>

			<div class="wp-product-plugin-card-content">
				<h3 class="wp-product-plugin-card-title">
					<?php echo esc_html( $product['title'] ); ?>
				</h3>

				<div class="wp-product-plugin-card-meta">
					<?php if ( ! empty( $product['category'] ) ) : ?>
						<div class="wp-product-plugin-card-category">
							<strong><?php esc_html_e( 'Category:', 'wp-product-plugin' ); ?></strong>
							<?php echo esc_html( ucfirst( $product['category'] ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $product['rating'] ) ) : ?>
						<div class="wp-product-plugin-card-rating">
							<strong><?php esc_html_e( 'Rating:', 'wp-product-plugin' ); ?></strong>
							⭐ <?php echo esc_html( $product['rating']['rate'] ); ?>/5
							(<?php echo esc_html( $product['rating']['count'] ); ?> <?php esc_html_e( 'reviews', 'wp-product-plugin' ); ?>)
						</div>
					<?php endif; ?>
				</div>

				<div class="wp-product-plugin-card-price">
					<strong><?php esc_html_e( 'Price:', 'wp-product-plugin' ); ?></strong>
					$<?php echo esc_html( number_format( $product['price'], 2 ) ); ?>
				</div>

				<div class="wp-product-plugin-card-description">
					<?php echo wp_kses_post( wpautop( $product['description'] ) ); ?>
				</div>

				<?php if ( $show_post_link && $post_id > 0 ) : ?>
					<div class="wp-product-plugin-card-link">
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="wp-product-plugin-view-post">
							<?php esc_html_e( 'View Product Post', 'wp-product-plugin' ); ?> &rarr;
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render error message.
	 *
	 * @param string $message Error message.
	 * @return string HTML output.
	 */
	private function render_error( $message ) {
		return sprintf(
			'<div class="wp-product-plugin-error">%s</div>',
			esc_html( $message )
		);
	}
}
