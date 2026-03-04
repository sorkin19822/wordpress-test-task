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

		// srcset/sizes for content images that lack responsive attributes (runs before lazy loading).
		add_filter( 'the_content', array( $this, 'add_srcset_to_content_images' ), 9 );

		// Lazy loading for content images missing width/height attributes.
		add_filter( 'the_content', array( $this, 'add_lazy_loading_to_content_images' ) );
	}

	/**
	 * Add srcset and sizes to images in post content that lack responsive attributes.
	 *
	 * WordPress generates multiple image sizes on upload but only injects srcset
	 * when images are output via wp_get_attachment_image(). Images hardcoded in
	 * post content (e.g. plain <img src="…-1024x768.jpg">) are skipped.
	 *
	 * This filter:
	 *  1. Strips the WP size suffix from the URL to find the original attachment.
	 *  2. Looks up the attachment ID (result cached per request via wp_cache).
	 *  3. Uses wp_get_attachment_image_srcset() to build the srcset string.
	 *  4. Applies a sizes attribute tuned to the auto-fill grid (minmax(180px,1fr)).
	 *
	 * @param string $content Post content HTML.
	 * @return string Modified HTML.
	 */
	public function add_srcset_to_content_images( string $content ): string {
		if ( ! str_contains( $content, '<img' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/<img\s[^>]*>/i',
			function ( array $matches ): string {
				$tag = $matches[0];

				// Skip images that already have responsive attributes.
				if ( preg_match( '/\bsrcset\s*=/i', $tag ) ) {
					return $tag;
				}

				// Extract src value.
				if ( ! preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/', $tag, $src_match ) ) {
					return $tag;
				}

				// Resolve relative URLs — attachment_url_to_postid() requires an absolute URL.
				$src = $src_match[1];
				if ( str_starts_with( $src, '/' ) && ! str_starts_with( $src, '//' ) ) {
					$src = home_url( $src );
				}

				// Strip the WP size suffix (e.g. -1024x768) to get the original URL
				// that attachment_url_to_postid() can resolve.
				$original_url  = preg_replace( '/-\d+x\d+(\.[^.?#]+)$/', '$1', $src );
				$cache_key     = md5( $original_url );
				$attachment_id = wp_cache_get( $cache_key, 'wpp_img_ids' );

				if ( false === $attachment_id ) {
					$attachment_id = attachment_url_to_postid( $original_url );
					wp_cache_set( $cache_key, $attachment_id, 'wpp_img_ids' );
				}

				if ( ! $attachment_id ) {
					return $tag;
				}

				$srcset = wp_get_attachment_image_srcset( $attachment_id );

				if ( ! $srcset ) {
					return $tag;
				}

				// sizes tuned for CSS grid: repeat(auto-fill, minmax(180px, 1fr)).
				// — large viewports  : ~204 px per cell (observed)
				// — medium viewports : ~25 vw (4 columns)
				// — small viewports  : ~50 vw (2 columns)
				$sizes = '(min-width: 1200px) 204px, (min-width: 640px) calc(25vw - 24px), calc(50vw - 24px)';

				$tag = preg_replace(
					'/(<img\s)/i',
					'$1srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '" ',
					$tag
				);

				return $tag;
			},
			$content
		);
	}

	/**
	 * Add loading="lazy" to images in post content that lack the attribute.
	 *
	 * WordPress 5.5+ sets lazy loading automatically, but only when both
	 * width and height attributes are present. Images without those attributes
	 * are skipped. This filter covers that gap.
	 *
	 * The very first <img> in the content is treated as a potential LCP element
	 * and gets loading="eager" + fetchpriority="high" instead.
	 *
	 * @param string $content Post content HTML.
	 * @return string Modified HTML.
	 */
	public function add_lazy_loading_to_content_images( string $content ): string {
		if ( ! str_contains( $content, '<img' ) ) {
			return $content;
		}

		$index = 0;

		return preg_replace_callback(
			'/<img\s[^>]*>/i',
			function ( array $matches ) use ( &$index ): string {
				$tag = $matches[0];

				// Skip images that already have a loading attribute set explicitly.
				if ( preg_match( '/\bloading\s*=/i', $tag ) ) {
					++$index;
					return $tag;
				}

				if ( 0 === $index ) {
					// First image: eager load as potential LCP element.
					$tag = preg_replace( '/(<img\s)/i', '$1loading="eager" fetchpriority="high" ', $tag );
				} else {
					$tag = preg_replace( '/(<img\s)/i', '$1loading="lazy" ', $tag );
				}

				++$index;
				return $tag;
			},
			$content
		);
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
