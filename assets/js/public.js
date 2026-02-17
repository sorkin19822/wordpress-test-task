/**
 * WP Product Plugin - Public JavaScript
 *
 * @package WP_Product_Plugin
 */

(function($) {
	'use strict';

	/**
	 * Handle random product button click.
	 */
	function handleRandomProductClick() {
		const $button = $(this);
		const $container = $button.closest('.wp-product-plugin-random-container');
		const $result = $container.find('.wp-product-plugin-random-result');
		const $loading = $container.find('.wp-product-plugin-loading');

		// Disable button and show loading.
		$button.prop('disabled', true);
		$loading.show();
		$result.empty();

		// Make AJAX request.
		$.ajax({
			url: wpProductPlugin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wp_product_plugin_get_random',
				nonce: wpProductPlugin.nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html(response.data.html).hide().fadeIn(400);
				} else {
					$result.html(
						'<div class="wp-product-plugin-error">' +
						(response.data.message || 'An error occurred.') +
						'</div>'
					).hide().fadeIn(400);
				}
			},
			error: function(xhr, status, error) {
				$result.html(
					'<div class="wp-product-plugin-error">' +
					'Failed to load product. Please try again.' +
					'</div>'
				).hide().fadeIn(400);
				console.error('AJAX error:', status, error);
			},
			complete: function() {
				$button.prop('disabled', false);
				$loading.hide();
			}
		});
	}

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function() {
		// Bind click event to random product button.
		$(document).on('click', '.wp-product-plugin-random-button', handleRandomProductClick);
	});

})(jQuery);
