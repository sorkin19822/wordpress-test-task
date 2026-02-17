<?php
/**
 * Admin area handler.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin area handler class.
 *
 * Handles all admin-specific functionality including settings page.
 */
class WP_Product_Plugin_Admin {

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'WP Product Plugin Settings', 'wp-product-plugin' ),
			__( 'Product Plugin', 'wp-product-plugin' ),
			'manage_options',
			'wp-product-plugin',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'wp_product_plugin_settings_group',
			'wp_product_plugin_settings',
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'wp_product_plugin_main_section',
			__( 'General Settings', 'wp-product-plugin' ),
			array( $this, 'render_main_section' ),
			'wp-product-plugin'
		);

		add_settings_field(
			'product_id',
			__( 'Product ID', 'wp-product-plugin' ),
			array( $this, 'render_product_id_field' ),
			'wp-product-plugin',
			'wp_product_plugin_main_section'
		);

		add_settings_section(
			'wp_product_plugin_info_section',
			__( 'Information', 'wp-product-plugin' ),
			array( $this, 'render_info_section' ),
			'wp-product-plugin'
		);

		add_settings_section(
			'wp_product_plugin_shortcodes_section',
			__( 'Shortcode Examples', 'wp-product-plugin' ),
			array( $this, 'render_shortcodes_section' ),
			'wp-product-plugin'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize product ID.
		if ( isset( $input['product_id'] ) ) {
			$product_id = absint( $input['product_id'] );

			// Validate range (1-20).
			if ( $product_id < 1 || $product_id > 20 ) {
				add_settings_error(
					'wp_product_plugin_settings',
					'invalid_product_id',
					__( 'Product ID must be between 1 and 20.', 'wp-product-plugin' ),
					'error'
				);
				// Keep old value.
				$old_settings = get_option( 'wp_product_plugin_settings', array() );
				$product_id = isset( $old_settings['product_id'] ) ? $old_settings['product_id'] : 1;
			}

			$sanitized['product_id'] = $product_id;
		}

		// Preserve last_created_at.
		$old_settings = get_option( 'wp_product_plugin_settings', array() );
		$sanitized['last_created_at'] = isset( $old_settings['last_created_at'] ) ? $old_settings['last_created_at'] : '';

		return $sanitized;
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show settings errors.
		settings_errors( 'wp_product_plugin_settings' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wp_product_plugin_settings_group' );
				do_settings_sections( 'wp-product-plugin' );
				submit_button( __( 'Save Settings', 'wp-product-plugin' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render main section description.
	 */
	public function render_main_section() {
		echo '<p>' . esc_html__( 'Configure the default product ID for the [product_display] shortcode.', 'wp-product-plugin' ) . '</p>';
	}

	/**
	 * Render product ID field.
	 */
	public function render_product_id_field() {
		$settings = get_option( 'wp_product_plugin_settings', array() );
		$product_id = isset( $settings['product_id'] ) ? $settings['product_id'] : 1;
		?>
		<input
			type="number"
			name="wp_product_plugin_settings[product_id]"
			id="product_id"
			value="<?php echo esc_attr( $product_id ); ?>"
			min="1"
			max="20"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter a product ID between 1 and 20 from FakeStore API.', 'wp-product-plugin' ); ?>
		</p>
		<?php
	}

	/**
	 * Render info section.
	 */
	public function render_info_section() {
		$settings = get_option( 'wp_product_plugin_settings', array() );
		$last_created_at = isset( $settings['last_created_at'] ) ? $settings['last_created_at'] : '';

		if ( empty( $last_created_at ) ) {
			$display_time = '<em>' . esc_html__( 'No products created yet', 'wp-product-plugin' ) . '</em>';
		} else {
			$timestamp = strtotime( $last_created_at );
			$display_time = sprintf(
				/* translators: 1: formatted date, 2: time ago */
				esc_html__( '%1$s (%2$s ago)', 'wp-product-plugin' ),
				date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
				human_time_diff( $timestamp, current_time( 'timestamp' ) )
			);
		}
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Last Product Created', 'wp-product-plugin' ); ?></th>
				<td><?php echo wp_kses_post( $display_time ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render shortcodes section.
	 */
	public function render_shortcodes_section() {
		?>
		<p><?php esc_html_e( 'Use these shortcodes in your posts or pages:', 'wp-product-plugin' ); ?></p>

		<h3><?php esc_html_e( 'Shortcode #1: Display Product', 'wp-product-plugin' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Displays the product configured in settings above.', 'wp-product-plugin' ); ?>
		</p>
		<code>[product_display]</code>

		<h3 style="margin-top: 20px;"><?php esc_html_e( 'Shortcode #2: Random Product with AJAX', 'wp-product-plugin' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Displays a button to load a random product via AJAX. Creates a CPT post and shows link to it.', 'wp-product-plugin' ); ?>
		</p>
		<code>[random_product]</code>

		<h3 style="margin-top: 20px;"><?php esc_html_e( 'Custom Attributes', 'wp-product-plugin' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'You can override the product ID for shortcode #1:', 'wp-product-plugin' ); ?>
		</p>
		<code>[product_display id="5"]</code>
		<?php
	}
}
