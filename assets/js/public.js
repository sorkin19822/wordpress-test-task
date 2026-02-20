/**
 * WP Product Plugin - Public JavaScript
 *
 * @package WP_Product_Plugin
 */

( function ( $ ) {
	'use strict';

	/**
	 * Guard against concurrent AJAX requests for the same container.
	 * Stored on the container element via $.data() so multiple shortcode
	 * instances on the same page each have their own independent state.
	 */
	var LOADING_KEY = 'wppLoading';

	/**
	 * Handle random product button click.
	 *
	 * @this {HTMLElement} The clicked button element.
	 */
	function handleRandomProductClick() {
		var $button    = $( this );
		var $container = $button.closest( '.wp-product-plugin-random-container' );
		var $result    = $container.find( '.wp-product-plugin-random-result' );
		var $loading   = $container.find( '.wp-product-plugin-loading' );

		// Prevent concurrent requests for this container.
		if ( $container.data( LOADING_KEY ) ) {
			return;
		}
		$container.data( LOADING_KEY, true );

		// Disable button and show loading indicator.
		$button.prop( 'disabled', true );
		$loading.show();
		$result.empty();

		// Make AJAX request.
		$.ajax( {
			url:  wpProductPlugin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wp_product_plugin_get_random',
				nonce:  wpProductPlugin.nonce
			},
			success: function ( response ) {
				if ( response.success ) {
					// Trusted server-rendered HTML — inserted as markup intentionally.
					$result.html( response.data.html ).hide().fadeIn( 400 );
				} else {
					// Use .text() so the message is never interpreted as HTML,
					// guarding against any future XSS if error text ever includes
					// user-supplied or API-supplied content.
					var $error = $( '<div class="wp-product-plugin-error"></div>' );
					$error.text( response.data.message || 'An error occurred.' );
					$result.empty().append( $error ).hide().fadeIn( 400 );
				}
			},
			error: function ( xhr, status, error ) {
				var $error = $( '<div class="wp-product-plugin-error"></div>' );
				$error.text( 'Failed to load product. Please try again.' );
				$result.empty().append( $error ).hide().fadeIn( 400 );
				// Log technical details for developers without exposing them in the UI.
				window.console && console.error( 'WP Product Plugin AJAX error:', status, error );
			},
			complete: function () {
				$container.data( LOADING_KEY, false );
				$button.prop( 'disabled', false );
				$loading.hide();
			}
		} );
	}

	/**
	 * Initialise on document ready.
	 */
	$( document ).ready( function () {
		// Use event delegation so buttons inside dynamically loaded content also work.
		$( document ).on( 'click', '.wp-product-plugin-random-button', handleRandomProductClick );
	} );

} )( jQuery );
