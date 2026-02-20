<?php
/**
 * Custom Post Type handler.
 *
 * @package WP_Product_Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Type handler class.
 *
 * Responsibilities: CPT registration and CPT post CRUD.
 * Settings writes and media-download orchestration belong elsewhere.
 */
class WP_Product_Plugin_CPT {

	/**
	 * Post type slug.
	 * Must match the constant in wp-product-plugin.php so uninstall.php
	 * can reference a single source of truth.
	 *
	 * @var string
	 */
	const POST_TYPE = WP_PRODUCT_PLUGIN_POST_TYPE;

	/**
	 * Register the Custom Post Type.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'                  => _x( 'Products', 'Post Type General Name', 'wp-product-plugin' ),
			'singular_name'         => _x( 'Product', 'Post Type Singular Name', 'wp-product-plugin' ),
			'menu_name'             => __( 'Products', 'wp-product-plugin' ),
			'name_admin_bar'        => __( 'Product', 'wp-product-plugin' ),
			'archives'              => __( 'Product Archives', 'wp-product-plugin' ),
			'attributes'            => __( 'Product Attributes', 'wp-product-plugin' ),
			'parent_item_colon'     => __( 'Parent Product:', 'wp-product-plugin' ),
			'all_items'             => __( 'All Products', 'wp-product-plugin' ),
			'add_new_item'          => __( 'Add New Product', 'wp-product-plugin' ),
			'add_new'               => __( 'Add New', 'wp-product-plugin' ),
			'new_item'              => __( 'New Product', 'wp-product-plugin' ),
			'edit_item'             => __( 'Edit Product', 'wp-product-plugin' ),
			'update_item'           => __( 'Update Product', 'wp-product-plugin' ),
			'view_item'             => __( 'View Product', 'wp-product-plugin' ),
			'view_items'            => __( 'View Products', 'wp-product-plugin' ),
			'search_items'          => __( 'Search Product', 'wp-product-plugin' ),
			'not_found'             => __( 'Not found', 'wp-product-plugin' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wp-product-plugin' ),
			'featured_image'        => __( 'Product Image', 'wp-product-plugin' ),
			'set_featured_image'    => __( 'Set product image', 'wp-product-plugin' ),
			'remove_featured_image' => __( 'Remove product image', 'wp-product-plugin' ),
			'use_featured_image'    => __( 'Use as product image', 'wp-product-plugin' ),
			'insert_into_item'      => __( 'Insert into product', 'wp-product-plugin' ),
			'uploaded_to_this_item' => __( 'Uploaded to this product', 'wp-product-plugin' ),
			'items_list'            => __( 'Products list', 'wp-product-plugin' ),
			'items_list_navigation' => __( 'Products list navigation', 'wp-product-plugin' ),
			'filter_items_list'     => __( 'Filter products list', 'wp-product-plugin' ),
		);

		$args = array(
			'label'               => __( 'Product', 'wp-product-plugin' ),
			'description'         => __( 'Products from FakeStore API', 'wp-product-plugin' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-products',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Create a product post from a WP_Product model.
	 *
	 * If a post for this API product already exists, returns its ID without
	 * creating a duplicate. On success fires the 'wp_product_plugin_product_created'
	 * action so other components (e.g. Admin) can react without CPT knowing about them.
	 *
	 * @param WP_Product $product Product model.
	 * @return int|WP_Error Post ID on success, WP_Error on failure.
	 */
	public function create_product( WP_Product $product ): int|WP_Error {
		// Return existing post ID to prevent duplicates.
		$existing_id = $this->get_post_id_by_api_id( $product->id );
		if ( $existing_id > 0 ) {
			return $existing_id;
		}

		// Insert the post.
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $product->title,
				'post_content' => $product->description,
				'post_status'  => 'publish',
				'meta_input'   => array(
					'_product_api_id'       => $product->id,
					'_product_price'        => $product->price,
					'_product_category'     => $product->category,
					'_product_rating_rate'  => $product->rating_rate,
					'_product_rating_count' => $product->rating_count,
				),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Download and attach the featured image.
		if ( ! empty( $product->image ) ) {
			$this->set_featured_image( $post_id, $product->image );
		}

		/**
		 * Fires after a new product post is created from the API.
		 *
		 * @param int        $post_id  The newly created post ID.
		 * @param WP_Product $product  The product model.
		 */
		do_action( 'wp_product_plugin_product_created', $post_id, $product );

		return $post_id;
	}

	/**
	 * Get the post ID for a given API product ID.
	 *
	 * Optimized with 'fields' => 'ids' (no full object fetch) and
	 * 'no_found_rows' => true (no SQL_CALC_FOUND_ROWS) since we only
	 * need to know whether a post exists.
	 *
	 * @param int $api_id API product ID.
	 * @return int Post ID, or 0 if not found.
	 */
	public function get_post_id_by_api_id( int $api_id ): int {
		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_product_api_id',
				'meta_value'     => absint( $api_id ),
			)
		);

		return ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
	}

	/**
	 * Download image from URL and set it as the post featured image.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $image_url Remote image URL.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	private function set_featured_image( int $post_id, string $image_url ): int|false {
		// Validate URL scheme to prevent SSRF via crafted image URLs in the API response.
		$scheme = wp_parse_url( $image_url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$temp_file = download_url( $image_url );

		if ( is_wp_error( $temp_file ) ) {
			return false;
		}

		$file = array(
			'name'     => basename( wp_parse_url( $image_url, PHP_URL_PATH ) ),
			'tmp_name' => $temp_file,
		);

		$attachment_id = media_handle_sideload( $file, $post_id );

		// Always clean up the temp file — use wp_delete_file() which handles
		// the filesystem abstraction and avoids the @ error-suppression operator.
		wp_delete_file( $temp_file );

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}
}
