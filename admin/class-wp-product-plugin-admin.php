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
 * Handles all admin-specific functionality including the settings page.
 * Listens to the 'wp_product_plugin_product_created' action to update
 * the last-created timestamp — keeping settings concerns out of the CPT class.
 */
class WP_Product_Plugin_Admin {

	/**
	 * Add admin menu item.
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'WP Product Plugin Settings', 'wp-product-plugin' ),
			__( 'Product Plugin', 'wp-product-plugin' ),
			'manage_options',
			'wp-product-plugin',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings via the WordPress Settings API.
	 */
	public function register_settings(): void {
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

		add_settings_field(
			'enable_enhanced_styles',
			__( 'Enhanced Styles', 'wp-product-plugin' ),
			array( $this, 'render_enhanced_styles_field' ),
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
	 * Record the timestamp of the most recently created product post.
	 *
	 * Hooked to 'wp_product_plugin_product_created' so that the CPT class
	 * does not need to know about admin settings.
	 *
	 * @param int $post_id The newly created CPT post ID.
	 */
	public function record_product_created_timestamp( int $post_id ): void {
		$settings                   = get_option( 'wp_product_plugin_settings', array() );
		$settings['last_created_at'] = current_time( 'mysql' );
		update_option( 'wp_product_plugin_settings', $settings );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		// Validate product ID (must be within the API's valid range).
		if ( isset( $input['product_id'] ) ) {
			$product_id = absint( $input['product_id'] );

			if (
				$product_id < WP_Product_Plugin_API::MIN_PRODUCT_ID ||
				$product_id > WP_Product_Plugin_API::MAX_PRODUCT_ID
			) {
				add_settings_error(
					'wp_product_plugin_settings',
					'invalid_product_id',
					sprintf(
						/* translators: 1: minimum ID, 2: maximum ID */
						__( 'Product ID must be between %1$d and %2$d.', 'wp-product-plugin' ),
						WP_Product_Plugin_API::MIN_PRODUCT_ID,
						WP_Product_Plugin_API::MAX_PRODUCT_ID
					),
					'error'
				);
				// Revert to the previously saved value.
				$old_settings = get_option( 'wp_product_plugin_settings', array() );
				$product_id   = (int) ( $old_settings['product_id'] ?? WP_Product_Plugin_API::MIN_PRODUCT_ID );
			}

			$sanitized['product_id'] = $product_id;
		}

		$sanitized['enable_enhanced_styles'] = isset( $input['enable_enhanced_styles'] ) ? 1 : 0;

		// Preserve the last-created timestamp (it is written by record_product_created_timestamp, not this form).
		$old_settings                = get_option( 'wp_product_plugin_settings', array() );
		$sanitized['last_created_at'] = $old_settings['last_created_at'] ?? '';

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-product-plugin' ) );
		}

		// Third argument (true) clears notices from the buffer after display,
		// preventing duplicate notices on custom admin pages (WordPress quirk).
		settings_errors( 'wp_product_plugin_settings', false, true );
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
	public function render_main_section(): void {
		echo '<p>' . esc_html__( 'Configure the default product ID for the [product_display] shortcode.', 'wp-product-plugin' ) . '</p>';
	}

	/**
	 * Render product ID field.
	 */
	public function render_product_id_field(): void {
		$settings   = get_option( 'wp_product_plugin_settings', array() );
		$product_id = (int) ( $settings['product_id'] ?? 1 );
		?>
		<input
			type="number"
			name="wp_product_plugin_settings[product_id]"
			id="product_id"
			value="<?php echo esc_attr( $product_id ); ?>"
			min="<?php echo esc_attr( WP_Product_Plugin_API::MIN_PRODUCT_ID ); ?>"
			max="<?php echo esc_attr( WP_Product_Plugin_API::MAX_PRODUCT_ID ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: 1: minimum ID, 2: maximum ID */
				esc_html__( 'Enter a product ID between %1$d and %2$d from FakeStore API.', 'wp-product-plugin' ),
				WP_Product_Plugin_API::MIN_PRODUCT_ID,
				WP_Product_Plugin_API::MAX_PRODUCT_ID
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render enhanced styles toggle field.
	 */
	public function render_enhanced_styles_field(): void {
		$settings = get_option( 'wp_product_plugin_settings', array() );
		$enabled  = (int) ( $settings['enable_enhanced_styles'] ?? 1 );
		?>
		<label>
			<input
				type="checkbox"
				name="wp_product_plugin_settings[enable_enhanced_styles]"
				id="enable_enhanced_styles"
				value="1"
				<?php checked( $enabled, 1 ); ?>
			/>
			<?php esc_html_e( 'Enable enhanced WordPress 6 compatible styles', 'wp-product-plugin' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Modern card design with shadows, hover effects, and responsive layout.', 'wp-product-plugin' ); ?>
		</p>
		<?php
	}

	/**
	 * Render info section (last created timestamp).
	 */
	public function render_info_section(): void {
		$settings       = get_option( 'wp_product_plugin_settings', array() );
		$last_created_at = $settings['last_created_at'] ?? '';

		if ( empty( $last_created_at ) ) {
			$display_time = '<em>' . esc_html__( 'No products created yet', 'wp-product-plugin' ) . '</em>';
		} else {
			$timestamp    = strtotime( $last_created_at );
			$display_time = sprintf(
				/* translators: 1: formatted date-time, 2: human-readable time difference */
				esc_html__( '%1$s (%2$s ago)', 'wp-product-plugin' ),
				esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ),
				esc_html( human_time_diff( $timestamp, time() ) )
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
	 * Render shortcodes reference section.
	 */
	public function render_shortcodes_section(): void {
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
