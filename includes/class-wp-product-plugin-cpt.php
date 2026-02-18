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
 * Registers and manages the Product custom post type.
 */
class WP_Product_Plugin_CPT {

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	const POST_TYPE = 'wpp_product';

	/**
	 * Register the Custom Post Type.
	 */
	public function register_post_type() {
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
	 * Create a product post from API data.
	 *
	 * @param array $product_data Product data from FakeStore API.
	 * @return int|WP_Error Post ID on success, WP_Error on failure.
	 */
	public function create_product( $product_data ) {
		if ( empty( $product_data ) || ! is_array( $product_data ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid product data', 'wp-product-plugin' ) );
		}

		// Check if product already exists by API ID.
		$existing_post = $this->get_product_by_api_id( $product_data['id'] );
		if ( $existing_post ) {
			return $existing_post->ID;
		}

		// Prepare post data.
		$post_title = isset( $product_data['title'] ) ? sanitize_text_field( $product_data['title'] ) : '';
		$post_content = isset( $product_data['description'] ) ? wp_kses_post( $product_data['description'] ) : '';

		// Create the post.
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_status'  => 'publish',
				'meta_input'   => array(
					'_product_api_id' => absint( $product_data['id'] ),
					'_product_price'  => isset( $product_data['price'] ) ? floatval( $product_data['price'] ) : 0,
					'_product_category' => isset( $product_data['category'] ) ? sanitize_text_field( $product_data['category'] ) : '',
					'_product_rating_rate' => isset( $product_data['rating']['rate'] ) ? floatval( $product_data['rating']['rate'] ) : 0,
					'_product_rating_count' => isset( $product_data['rating']['count'] ) ? absint( $product_data['rating']['count'] ) : 0,
				),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Download and set featured image.
		if ( ! empty( $product_data['image'] ) ) {
			$this->set_featured_image( $post_id, $product_data['image'] );
		}

		// Update last created timestamp.
		$this->update_last_created_timestamp();

		return $post_id;
	}

	/**
	 * Get product post by API ID.
	 *
	 * @param int $api_id API product ID.
	 * @return WP_Post|null Post object or null if not found.
	 */
	public function get_product_by_api_id( $api_id ) {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_product_api_id',
			'meta_value'     => absint( $api_id ),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			return $query->posts[0];
		}

		return null;
	}

	/**
	 * Download and set featured image from URL.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $image_url Image URL.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	private function set_featured_image( $post_id, $image_url ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download image to temp file.
		$temp_file = download_url( $image_url );

		if ( is_wp_error( $temp_file ) ) {
			return false;
		}

		// Prepare file array.
		$file = array(
			'name'     => basename( $image_url ),
			'tmp_name' => $temp_file,
		);

		// Upload the file.
		$attachment_id = media_handle_sideload( $file, $post_id );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			@unlink( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Update last created timestamp in settings.
	 */
	private function update_last_created_timestamp() {
		$settings = get_option( 'wp_product_plugin_settings', array() );
		$settings['last_created_at'] = current_time( 'mysql' );
		update_option( 'wp_product_plugin_settings', $settings );
	}
}
