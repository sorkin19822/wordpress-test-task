<?php
/**
 * The core plugin class.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * Wires dependencies together and registers WordPress hooks.
 * Uses the Singleton pattern to guarantee a single instance.
 * The constructor only stores the instance reference; all real
 * initialisation happens in run() so that the object construction
 * and plugin execution are clearly separated.
 */
class WP_Product_Plugin {

	/**
	 * The single instance of the class.
	 * Private — subclasses must not bypass the Singleton invariant.
	 *
	 * @var WP_Product_Plugin|null
	 */
	private static ?WP_Product_Plugin $instance = null;

	/**
	 * @var WP_Product_Plugin_CPT
	 */
	private WP_Product_Plugin_CPT $cpt;

	/**
	 * @var WP_Product_Plugin_API
	 */
	private WP_Product_Plugin_API $api;

	/**
	 * @var WP_Product_Plugin_Admin|null
	 */
	private ?WP_Product_Plugin_Admin $admin = null;

	/**
	 * @var WP_Product_Plugin_AJAX
	 */
	private WP_Product_Plugin_AJAX $ajax;

	/**
	 * @var WP_Product_Plugin_Shortcodes
	 */
	private WP_Product_Plugin_Shortcodes $shortcodes;

	/**
	 * @var WP_Product_Renderer
	 */
	private WP_Product_Renderer $renderer;

	/**
	 * Return (and lazily create) the single plugin instance.
	 *
	 * @return static
	 */
	public static function get_instance(): static {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {}

	/**
	 * Bootstrap the plugin: load dependencies and register hooks.
	 *
	 * Called from the entry file (wp-product-plugin.php) on plugins_loaded.
	 */
	public function run(): void {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Instantiate all dependency classes.
	 */
	private function load_dependencies(): void {
		// Product model.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product.php';

		// Shared renderer (no external dependencies).
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-renderer.php';
		$this->renderer = new WP_Product_Renderer();

		// API handler.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-api.php';
		$this->api = new WP_Product_Plugin_API();

		// CPT handler.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-cpt.php';
		$this->cpt = new WP_Product_Plugin_CPT();

		// Admin area — loaded only in the admin context.
		if ( is_admin() ) {
			require_once WP_PRODUCT_PLUGIN_PATH . 'admin/class-wp-product-plugin-admin.php';
			$this->admin = new WP_Product_Plugin_Admin();
		}

		// Shortcodes.
		require_once WP_PRODUCT_PLUGIN_PATH . 'public/class-wp-product-plugin-shortcodes.php';
		$this->shortcodes = new WP_Product_Plugin_Shortcodes( $this->api, $this->renderer );

		// AJAX handler — renderer injected directly, no Shortcodes dependency.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-ajax.php';
		$this->ajax = new WP_Product_Plugin_AJAX( $this->api, $this->cpt, $this->renderer );
	}

	/**
	 * Register all WordPress hooks.
	 */
	private function define_hooks(): void {
		// CPT registration.
		add_action( 'init', array( $this->cpt, 'register_post_type' ) );

		// Admin hooks.
		if ( is_admin() && null !== $this->admin ) {
			add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this->admin, 'register_settings' ) );

			// Admin reacts to the product-created event fired by CPT.
			add_action( 'wp_product_plugin_product_created', array( $this->admin, 'record_product_created_timestamp' ), 10, 1 );
		}

		// AJAX hooks — available to both logged-in users and guests.
		add_action( 'wp_ajax_wp_product_plugin_get_random', array( $this->ajax, 'handle_get_random_product' ) );
		add_action( 'wp_ajax_nopriv_wp_product_plugin_get_random', array( $this->ajax, 'handle_get_random_product' ) );

		// Shortcode registration.
		add_action( 'init', array( $this->shortcodes, 'register_shortcodes' ) );

		// Frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
	}

	/**
	 * Enqueue public-facing assets.
	 */
	public function enqueue_public_assets(): void {
		$settings        = get_option( 'wp_product_plugin_settings', array() );
		$enhanced_styles = (bool) ( $settings['enable_enhanced_styles'] ?? true );

		if ( $enhanced_styles ) {
			wp_enqueue_style(
				'wp-product-plugin-enhanced',
				WP_PRODUCT_PLUGIN_URL . 'assets/css/enhanced.css',
				array(),
				WP_PRODUCT_PLUGIN_VERSION
			);
		} else {
			wp_enqueue_style(
				'wp-product-plugin-public',
				WP_PRODUCT_PLUGIN_URL . 'assets/css/public.css',
				array(),
				WP_PRODUCT_PLUGIN_VERSION
			);
		}

		wp_enqueue_script(
			'wp-product-plugin-public',
			WP_PRODUCT_PLUGIN_URL . 'assets/js/public.js',
			array( 'jquery' ),
			WP_PRODUCT_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'wp-product-plugin-public',
			'wpProductPlugin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_product_plugin_nonce' ),
			)
		);
	}
}
