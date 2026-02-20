<?php
/**
 * Product card renderer.
 *
 * Responsible solely for converting a WP_Product model into HTML.
 * Both the Shortcodes handler and the AJAX handler depend on this class
 * directly, eliminating the previous cross-class coupling where AJAX
 * called into the Shortcodes object to generate HTML.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders product card HTML from a WP_Product model.
 */
class WP_Product_Renderer {

	/**
	 * Render a product card.
	 *
	 * @param WP_Product $product       Product model to render.
	 * @param bool       $show_post_link Whether to render the "View Post" link.
	 * @param int        $post_id        CPT post ID (required when $show_post_link is true).
	 * @return string HTML output.
	 */
	public function render_card( WP_Product $product, bool $show_post_link = false, int $post_id = 0 ): string {
		ob_start();
		?>
		<div class="wp-product-plugin-card">
			<?php if ( ! empty( $product->image ) ) : ?>
				<div class="wp-product-plugin-card-image">
					<img
						src="<?php echo esc_url( $product->image ); ?>"
						alt="<?php echo esc_attr( $product->title ); ?>"
					/>
				</div>
			<?php endif; ?>

			<div class="wp-product-plugin-card-content">
				<h3 class="wp-product-plugin-card-title">
					<?php echo esc_html( $product->title ); ?>
				</h3>

				<div class="wp-product-plugin-card-meta">
					<?php if ( ! empty( $product->category ) ) : ?>
						<div class="wp-product-plugin-card-category">
							<strong><?php esc_html_e( 'Category:', 'wp-product-plugin' ); ?></strong>
							<?php echo esc_html( ucfirst( $product->category ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $product->rating_rate > 0 ) : ?>
						<div class="wp-product-plugin-card-rating">
							<strong><?php esc_html_e( 'Rating:', 'wp-product-plugin' ); ?></strong>
							&#11088; <?php echo esc_html( $product->rating_rate ); ?>/5
							(<?php echo esc_html( $product->rating_count ); ?>
							<?php esc_html_e( 'reviews', 'wp-product-plugin' ); ?>)
						</div>
					<?php endif; ?>
				</div>

				<div class="wp-product-plugin-card-price">
					<strong><?php esc_html_e( 'Price:', 'wp-product-plugin' ); ?></strong>
					$<?php echo esc_html( number_format( $product->price, 2 ) ); ?>
				</div>

				<div class="wp-product-plugin-card-description">
					<?php echo wp_kses_post( wpautop( $product->description ) ); ?>
				</div>

				<?php if ( $show_post_link && $post_id > 0 ) : ?>
					<div class="wp-product-plugin-card-link">
						<a
							href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
							class="wp-product-plugin-view-post"
						>
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
	 * Render an error message.
	 *
	 * @param string $message Error message text.
	 * @return string HTML output.
	 */
	public function render_error( string $message ): string {
		return sprintf(
			'<div class="wp-product-plugin-error">%s</div>',
			esc_html( $message )
		);
	}
}
