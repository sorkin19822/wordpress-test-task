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
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class WP_Product_Plugin {

	/**
	 * The single instance of the class.
	 *
	 * @var WP_Product_Plugin
	 */
	protected static $instance = null;

	/**
	 * Custom Post Type handler.
	 *
	 * @var WP_Product_Plugin_CPT
	 */
	protected $cpt;

	/**
	 * API handler.
	 *
	 * @var WP_Product_Plugin_API
	 */
	protected $api;

	/**
	 * Admin handler.
	 *
	 * @var WP_Product_Plugin_Admin
	 */
	protected $admin;

	/**
	 * AJAX handler.
	 *
	 * @var WP_Product_Plugin_AJAX
	 */
	protected $ajax;

	/**
	 * Shortcodes handler.
	 *
	 * @var WP_Product_Plugin_Shortcodes
	 */
	protected $shortcodes;

	/**
	 * Main WP_Product_Plugin Instance.
	 *
	 * Ensures only one instance of WP_Product_Plugin is loaded or can be loaded.
	 *
	 * @return WP_Product_Plugin - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		// Custom Post Type.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-cpt.php';
		$this->cpt = new WP_Product_Plugin_CPT();

		// API handler.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-api.php';
		$this->api = new WP_Product_Plugin_API();

		// Admin area.
		if ( is_admin() ) {
			require_once WP_PRODUCT_PLUGIN_PATH . 'admin/class-wp-product-plugin-admin.php';
			$this->admin = new WP_Product_Plugin_Admin();
		}

		// Shortcodes.
		require_once WP_PRODUCT_PLUGIN_PATH . 'public/class-wp-product-plugin-shortcodes.php';
		$this->shortcodes = new WP_Product_Plugin_Shortcodes( $this->api );

		// AJAX handler.
		require_once WP_PRODUCT_PLUGIN_PATH . 'includes/class-wp-product-plugin-ajax.php';
		$this->ajax = new WP_Product_Plugin_AJAX( $this->api, $this->cpt, $this->shortcodes );
	}

	/**
	 * Register all hooks.
	 */
	private function define_hooks() {
		// CPT hooks.
		add_action( 'init', array( $this->cpt, 'register_post_type' ) );

		// Admin hooks.
		if ( is_admin() && $this->admin ) {
			add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		}

		// AJAX hooks.
		add_action( 'wp_ajax_wp_product_plugin_get_random', array( $this->ajax, 'handle_get_random_product' ) );
		add_action( 'wp_ajax_nopriv_wp_product_plugin_get_random', array( $this->ajax, 'handle_get_random_product' ) );

		// Shortcode hooks.
		add_action( 'init', array( $this->shortcodes, 'register_shortcodes' ) );

		// Enqueue scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
	}

	/**
	 * Enqueue public-facing assets.
	 */
	public function enqueue_public_assets() {
		// Get settings to check if enhanced styles are enabled.
		$settings = get_option( 'wp_product_plugin_settings', array() );
		$enhanced_styles = isset( $settings['enable_enhanced_styles'] ) ? $settings['enable_enhanced_styles'] : 1;

		// Enqueue appropriate stylesheet.
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

	/**
	 * Run the plugin.
	 */
	public function run() {
		// Plugin is running.
	}
}
